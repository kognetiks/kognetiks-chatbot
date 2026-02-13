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

// Function to convert markdown bold to HTML - Ver 2.3.9
// Converts **text** to <b>text</b>
function chatbot_chatgpt_convert_bold_markdown($text) {
    if (empty($text)) {
        return $text;
    }
    
    // First, normalize any whitespace issues - remove any CR/LF/LF between "1." and "**"
    // This handles cases where line breaks might be inserted between numbers and bold markers
    $text = preg_replace('/(\d+\.)\s*[\r\n]+\s*(\*\*)/', '$1 $2', $text);
    
    // Remove quotes that might be separating numbered items from bold markers
    // Pattern: "1." followed by optional quotes and then "**"
    $text = preg_replace('/(\d+\.)\s*["\']+\s*(\*\*)/', '$1 $2', $text);
    
    // Convert bold: **text** to <b>text</b>
    // Use non-greedy matching to handle multiple bold sections
    // Important: This must be done BEFORE italics conversion to avoid conflicts
    $text = preg_replace('/\*\*([^*]+)\*\*/', '<b>$1</b>', $text);
    
    // Also clean up any whitespace issues between "1." and "<b>" after conversion
    // Remove any line breaks, quotes, or extra spaces between number and opening bold tag
    $text = preg_replace('/(\d+\.)\s*["\']*\s*[\r\n]+\s*["\']*\s*(<b>)/', '$1 $2', $text);
    $text = preg_replace('/(\d+\.)\s*["\']+\s*(<b>)/', '$1 $2', $text);
    
    return $text;
}

// Function to convert markdown links to HTML anchor tags - Ver 2.3.9
// Converts [text](url) to <a title="text" href="url" target="_blank">text</a>
function chatbot_chatgpt_convert_markdown_links($text) {
    if (empty($text)) {
        return $text;
    }
    
    // Pattern to match markdown links: [text](url)
    // Matches: [link text](https://example.com)
    // Captures: $1 = link text, $2 = URL
    $pattern = '/\[([^\]]+)\]\(([^\)]+)\)/';
    
    // Replace with HTML anchor tag
    // Format: <a title="text" href="url" target="_blank">text</a>
    $text = preg_replace_callback($pattern, function($matches) {
        $link_text = $matches[1];  // The text inside the square brackets
        $url = $matches[2];         // The URL inside the parentheses
        
        // Escape attributes to prevent XSS
        $link_text_escaped = esc_attr($link_text);
        $url_escaped = esc_url($url);
        $link_text_html = esc_html($link_text);
        
        // Return HTML anchor tag with title, href, and target attributes
        return '<a title="' . $link_text_escaped . '" href="' . $url_escaped . '" target="_blank">' . $link_text_html . '</a>';
    }, $text);
    
    return $text;
}

// Function to convert markdown headers to HTML heading tags - Ver 2.3.9
// Converts # Header to <h1>Header</h1>, ## Header to <h2>Header</h2>, etc.
function chatbot_chatgpt_convert_markdown_headers($text) {
    if (empty($text)) {
        return $text;
    }
    
    // Pattern to match markdown headers at the start of a line
    // Matches: # Header, ## Header, ### Header, etc. (up to 6 # symbols)
    // Pattern: ^#{1,6}\s+(.+)$
    // - ^ = start of line (or start of string after newline)
    // - #{1,6} = 1 to 6 hash symbols
    // - \s+ = one or more whitespace characters
    // - (.+) = the header text (captured)
    // - $ = end of line
    
    // Process line by line to handle headers properly
    $lines = explode("\n", $text);
    $processed_lines = [];
    
    foreach ($lines as $line) {
        // Check if line starts with 1-6 hash symbols followed by space
        if (preg_match('/^(#{1,6})\s+(.+)$/', $line, $matches)) {
            $hash_count = strlen($matches[1]);  // Count of # symbols (1-6)
            $header_text = trim($matches[2]);   // The header text
            
            // Limit to h1-h6 (hash_count should already be 1-6 from regex)
            $level = min($hash_count, 6);
            
            // Escape the header text for HTML
            $header_text_escaped = esc_html($header_text);
            
            // Create the HTML heading tag
            $processed_lines[] = '<h' . $level . '>' . $header_text_escaped . '</h' . $level . '>';
        } else {
            // Not a header line, keep as-is
            $processed_lines[] = $line;
        }
    }
    
    // Join lines back together
    return implode("\n", $processed_lines);
}

// Function to balance unmatched HTML tags - Ver 2.3.9
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

    // Fixed Ver 2.3.9: Query by user_id as string to match VARCHAR column type
    // The logging function has been fixed to preserve user_id for logged-in users
    $user_id_str = (string) $current_user_id;
    
    // Query conversations by user_id (as string to match VARCHAR column)
    // Fixed Ver 2.3.9: Added DISTINCT and id to prevent duplicate entries
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
    // Fixed Ver 2.3.9: Use unique message IDs to prevent duplicates
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
            
            // Fixed Ver 2.3.9: Properly render HTML content instead of escaping it
            // Processing order: markdown headers -> markdown bold -> markdown links -> balance tags -> sanitize -> format paragraphs
            $message_text = stripslashes($message->message_text);
            
            // Step 1: Convert markdown headers (# Header) to HTML heading tags - Ver 2.3.9
            // Format: # → <h1>, ## → <h2>, ### → <h3>, etc.
            $message_text = chatbot_chatgpt_convert_markdown_headers($message_text);
            
            // Step 2: Convert markdown bold (**text**) to HTML (<b>text</b>) - Ver 2.3.9
            // This function also cleans up any line breaks between numbers and bold markers
            $message_text = chatbot_chatgpt_convert_bold_markdown($message_text);
            
            // Step 3: Convert markdown links [text](url) to HTML anchor tags - Ver 2.3.9
            // Format: <a title="text" href="url" target="_blank">text</a>
            $message_text = chatbot_chatgpt_convert_markdown_links($message_text);
            
            // Step 4: Balance unmatched HTML tags to prevent formatting issues - Ver 2.3.9
            // This closes any unclosed tags (especially inline tags like <i>, <b>, <a>)
            $message_text = chatbot_chatgpt_balance_html_tags($message_text);
            
            // Step 5: Sanitize to allow only safe HTML tags - Ver 2.3.9
            // wp_kses_post allows anchor tags with href attribute and heading tags
            $message_text = wp_kses_post($message_text);
            
            // Step 6: Convert line breaks to paragraphs (this should be done last) - Ver 2.3.9
            // wpautop will handle double line breaks as paragraphs, but single breaks in lists are now spaces
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

    // Filter results to stay within the 2.5 MB limit
    $limited_results = [];
    $max_size = 2.5 * 1024 * 1024; // 2.5 MB in bytes
    $total_size = 0;

    foreach ($results as $result) {
        $message_size = strlen($result->message_text);
        if (($total_size + $message_size) > $max_size) break;
        
        $limited_results[] = $result;
        $total_size += $message_size;
    }

    $conversation_history = '';
    foreach ($results as $result) {
        $conversation_history .= esc_html($result->message_text) . ' ';        
    }

    // Remove extra spaces from $conversation_history
    $conversation_history = preg_replace('/\s+/', ' ', $conversation_history);

    // Return the HTML output as a JSON response
    return($conversation_history);

}
