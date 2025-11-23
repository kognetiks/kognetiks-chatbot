<?php
/**
 * Kognetiks Chatbot - Chatbot Conversation History
 *
 * This file contains the code for table actions for reporting
 * to display the chatbot conversation on a page on the website.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Function to balance unmatched HTML tags - Ver 2.3.6
// This ensures that opening tags have matching closing tags to prevent formatting issues
// Simplified approach that closes unclosed tags at the end of each message
function chatbot_chatgpt_balance_html_tags($html) {
    if (empty($html)) {
        return $html;
    }
    
    // List of self-closing tags that don't need closing tags
    $self_closing_tags = ['br', 'hr', 'img', 'input', 'meta', 'link', 'area', 'base', 'col', 'embed', 'source', 'track', 'wbr'];
    
    // Simple regex-based approach for basic tag balancing
    // Extract all opening and closing tags
    preg_match_all('/<(\/?)([a-z0-9]+)(?:\s[^>]*)?(?:\s*\/)?>/i', $html, $matches, PREG_OFFSET_CAPTURE);
    
    $tag_stack = [];
    $result = '';
    $last_pos = 0;
    
    foreach ($matches[0] as $index => $match) {
        $tag_full = $match[0];
        $tag_pos = $match[1];
        $is_closing = !empty($matches[1][$index][0]);
        $tag_name = strtolower(trim($matches[2][$index][0]));
        $is_self_closing = substr(trim($tag_full), -2) === '/>' || substr(trim($tag_full), -1) === '/';
        
        // Append text before this tag
        $result .= substr($html, $last_pos, $tag_pos - $last_pos);
        
        if ($is_closing) {
            // Find matching opening tag
            $found = false;
            for ($j = count($tag_stack) - 1; $j >= 0; $j--) {
                if ($tag_stack[$j] === $tag_name) {
                    // Close all tags between
                    for ($k = count($tag_stack) - 1; $k > $j; $k--) {
                        $unclosed = array_pop($tag_stack);
                        $result .= '</' . $unclosed . '>';
                    }
                    array_pop($tag_stack);
                    $result .= $tag_full;
                    $found = true;
                    break;
                }
            }
            // Ignore orphaned closing tags - don't append them
        } elseif (in_array($tag_name, $self_closing_tags) || $is_self_closing) {
            // Self-closing tag
            $result .= $tag_full;
        } else {
            // Opening tag - add to stack
            $tag_stack[] = $tag_name;
            $result .= $tag_full;
        }
        
        $last_pos = $tag_pos + strlen($tag_full);
    }
    
    // Append remaining text
    $result .= substr($html, $last_pos);
    
    // Close any remaining unclosed tags (in reverse order)
    while (!empty($tag_stack)) {
        $unclosed = array_pop($tag_stack);
        $result .= '</' . $unclosed . '>';
    }
    
    return $result;
}

// Shortcode to display the chatbot conversation history for the logged-in user
// Usage: [chat_history] or [chatbot_conversation] or [chatbot_chatgpt_history]
function interactive_chat_history() {

    if (!is_user_logged_in()) {
        return 'You need to be logged in to view your conversations.';
    }

    global $wpdb;

    $current_user_id = get_current_user_id();
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log'; // Adjust the table name as necessary

    // Fixed Ver 2.3.6: Query by user_id as string to match VARCHAR column type
    // The logging function has been fixed to preserve user_id for logged-in users
    $user_id_str = (string) $current_user_id;
    
    // Query conversations by user_id (as string to match VARCHAR column)
    // Fixed Ver 2.3.6: Added DISTINCT and id to prevent duplicate entries
    $query = $wpdb->prepare("SELECT DISTINCT c.id, c.message_text, c.user_type, c.thread_id, c.interaction_time, c.assistant_id, c.assistant_name, DATE(c.interaction_time) as interaction_date
    FROM $table_name c
    WHERE c.user_id = %s 
    AND c.user_type IN ('Chatbot', 'Visitor')
    ORDER BY interaction_date DESC, c.interaction_time ASC", 
    $user_id_str);

    $conversations = $wpdb->get_results($query);

    if (empty($conversations)) {
    return 'No conversations found.';
    }

    // Group messages by interaction_date
    // Fixed Ver 2.3.6: Use unique message IDs to prevent duplicates
    $grouped_conversations = [];
    $seen_message_ids = []; // Track seen message IDs to prevent duplicates
    
    foreach ($conversations as $conversation) {
        // Use the database ID to prevent duplicates
        $message_id = isset($conversation->id) ? $conversation->id : null;
        
        if ($message_id && isset($seen_message_ids[$message_id])) {
            // Skip duplicate message
            continue;
        }
        
        if ($message_id) {
            $seen_message_ids[$message_id] = true;
        }
        
        $grouped_conversations[$conversation->interaction_date][] = $conversation;
    }

    $output = '<div class="chatbot-chatgpt-chatbot-history-wrapper">';
    foreach ($grouped_conversations as $interaction_date => $messages) {
        $first_message = reset($messages); // Get the first message to use its date
        $date_label = date("F j, Y, g:i a", strtotime($first_message->interaction_time)); // Format the date
        // Create a unique ID based on the date (sanitize for use in HTML ID attribute)
        $date_id = sanitize_html_class($interaction_date);

        $output .= sprintf('<div class="chatbot-chatgpt-chatbot-history" id="thread-%s">', esc_attr($date_id));
        $output .= '<a href="#" onclick="toggleThread(\'' . esc_attr($date_id) . '\');return false;">' . esc_html($date_label) . '</a>';
        $output .= '<div class="thread-messages" style="display:none;">';
        foreach ($messages as $message) {
            $assistant_name = $message->assistant_name;
            if (empty($assistant_name)) {
                $assistant_name = esc_attr(get_option('chatbot_chatgpt_bot_name'));
            }
            $user_type = $message->user_type === 'Chatbot' ? 'Chatbot' : 'You';
            
            // Fixed Ver 2.3.6: Properly render HTML content instead of escaping it
            // Processing order: balance tags -> sanitize -> format paragraphs
            $message_text = stripslashes($message->message_text);
            
            // Step 1: Balance unmatched HTML tags to prevent formatting issues
            // This closes any unclosed tags (especially inline tags like <i>, <b>, <a>)
            $message_text = chatbot_chatgpt_balance_html_tags($message_text);
            
            // Step 2: Sanitize to allow only safe HTML tags
            // wp_kses_post allows anchor tags with href attribute
            $message_text = wp_kses_post($message_text);
            
            // Step 3: Convert line breaks to paragraphs (this should be done last)
            $message_text = wpautop($message_text);
            
            if ($user_type == 'You') {
                $output .= sprintf('<div class="history-message user-message"><b>%s</b><div class="message-content">%s</div></div>', esc_html($user_type), $message_text);
            } else {
                $output .= sprintf('<div class="history-message bot-message"><b>%s</b><div class="message-content">%s</div></div>', esc_html($assistant_name), $message_text);
            }
        }
        $output .= '</div></div>';
    }
    $output .= '</div>';
    
    // Include JavaScript for toggling
    $output .= "<script>
                    function toggleThread(threadId) {
                        var element = document.getElementById('thread-' + threadId);
                        var display = element.querySelector('.thread-messages').style.display;
                        element.querySelector('.thread-messages').style.display = display === 'none' ? 'block' : 'none';
                    }
                </script>";

    return $output;

}
add_shortcode('chatbot_chatgpt_history', 'interactive_chat_history');
add_shortcode('chatbot_conversation', 'interactive_chat_history');
add_shortcode('chat_history', 'interactive_chat_history');

// Function to get the conversation history for a given session ID
function chatbot_chatgpt_get_converation_history($session_id) {

    global $wpdb;

    // If $session_id is null return an empty string
    if (empty($session_id)) {
        return '';
    }

    // If $session_id doesn't start with "kogentiks_" return an empty string
    if (strpos($session_id, 'kognetiks_') !== 0) {
        return '';
    }

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';

    // Query to get message_text ordered by timestamp
    $results = $wpdb->get_results($wpdb->prepare("
        SELECT message_text, user_type
        FROM $table_name 
        WHERE session_id = %s AND user_type IN ('Chatbot', 'Visitor')
        ORDER BY interaction_time ASC", $session_id));

    // DIAG - Diagnostics - Ver 2.1.8
    // back_trace( 'NOTICE', 'Query Results: ' . print_r($results, true));

    // Filter results to stay within the 2.5 MB limit
    $limited_results = [];
    $max_size = 2.5 * 1024 * 1024; // 2.5 MB in bytes
    $total_size = 0;

    foreach ($results as $result) {
        $message_size = strlen($result->message_text);
        if (($total_size + $message_size) > $max_size) break;
        
        $limited_results[] = $result;
        $total_size += $message_size;
        // back_trace( 'NOTICE', 'Total Size: ' . $total_size);
    }

    $conversation_history = '';
    foreach ($results as $result) {
        $conversation_history .= esc_html($result->message_text) . ' ';        
    }

    // Remove extra spaces from $conversation_history
    $conversation_history = preg_replace('/\s+/', ' ', $conversation_history);

    // DIAG - Diagnostics - Ver 2.1.8
    // back_trace( 'NOTICE', '$conversation_history: ' . $conversation_history);

    // Return the HTML output as a JSON response
    return($conversation_history);

}
