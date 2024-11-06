<?php
/**
 * Kognetiks Chatbot for WordPress - Chatbot Conversation History
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

// Shortcode to display the chatbot conversation history for the logged-in user
// Usage: [chat_history] or [chatbot_conversation] or [chatbot_chatgpt_history]
function interactive_chat_history() {

    if (!is_user_logged_in()) {
        return 'You need to be logged in to view your conversations.';
    }

    global $wpdb;

    $current_user_id = get_current_user_id();
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log'; // Adjust the table name as necessary

    // New query with sub-query for correct sorting
    $query = $wpdb->prepare("SELECT c.message_text, c.user_type, c.thread_id, c.interaction_time, c.assistant_id, c.assistant_name, DATE(c.interaction_time) as interaction_date
    FROM $table_name c
    WHERE c.user_id = %d 
    AND c.user_type IN ('Chatbot', 'Visitor')
    ORDER BY interaction_date DESC, c.interaction_time ASC", 
    $current_user_id);

    $conversations = $wpdb->get_results($query);

    if (empty($conversations)) {
    return 'No conversations found.';
    }

    // Group messages by interaction_date
    $grouped_conversations = [];
    foreach ($conversations as $conversation) {
    $grouped_conversations[$conversation->interaction_date][] = $conversation;
    }

    $output = '<div class="chatbot-chatgpt-chatbot-history">';
    foreach ($grouped_conversations as $thread_id => $messages) {
        $first_message = reset($messages); // Get the first message to use its date
        $date_label = date("F j, Y, g:i a", strtotime($first_message->interaction_time)); // Format the date

        $output .= sprintf('<div class="chatbot-chatgpt-chatbot-history" id="thread-%s">', esc_attr($thread_id));
        $output .= '<a href="#" onclick="toggleThread(\'' . esc_attr($thread_id) . '\');return false;">' . esc_html($date_label) . '</a>';
        $output .= '<div class="thread-messages" style="display:none;">';
        foreach ($messages as $message) {
            $assistant_name = $message->assistant_name;
            if (empty($assistant_name)) {
                $assistant_name = esc_attr(get_option('chatbot_chatgpt_bot_name'));
            }
            $user_type = $message->user_type === 'Chatbot' ? 'Chatbot' : 'You';
            if ($user_type == 'You') {
                $output .= sprintf('<b>%s</b><br>%s<br>', esc_html($user_type), stripslashes(esc_html($message->message_text)));
            } else {
                $output .= sprintf('<b>%s</b><br>%s<br>', esc_html($assistant_name), stripslashes(esc_html($message->message_text)));
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
