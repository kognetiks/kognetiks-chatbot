<?php
/**
 * Kognetiks Chatbot for WordPress - Chatbot Converation History
 *
 * This file contains the code for table actions for reporting
 * to display the chatbot converation on a page on the website.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
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

    // New query with subquery for correct sorting
    $query = $wpdb->prepare("SELECT c.message_text, c.user_type, c.thread_id, c.interaction_time
                            FROM $table_name c
                            INNER JOIN (
                                SELECT thread_id, MIN(interaction_time) as first_interaction_time
                                FROM $table_name 
                                WHERE user_id = %d 
                                AND user_type IN ('Chatbot', 'Visitor')
                                GROUP BY thread_id
                            ) t ON c.thread_id = t.thread_id
                            WHERE c.user_id = %d 
                            AND c.user_type IN ('Chatbot', 'Visitor')
                            ORDER BY t.first_interaction_time DESC, c.interaction_time ASC", 
                            $current_user_id, $current_user_id);

    $conversations = $wpdb->get_results($query);

    if (empty($conversations)) {
        return 'No conversations found.';
    }

    // Group messages by thread_id
    $grouped_conversations = [];
    foreach ($conversations as $conversation) {
        $grouped_conversations[$conversation->thread_id][] = $conversation;
    }

    $output = '<div class="chatbot-chatgpt-chatbot-history">';
    foreach ($grouped_conversations as $thread_id => $messages) {
        $first_message = reset($messages); // Get the first message to use its date
        $date_label = date("F j, Y, g:i a", strtotime($first_message->interaction_time)); // Format the date

        $output .= sprintf('<div class="chatbot-chatgpt-chatbot-history" id="thread-%s">', esc_attr($thread_id));
        $output .= '<a href="#" onclick="toggleThread(\'' . esc_attr($thread_id) . '\');return false;">' . esc_html($date_label) . '</a>';
        $output .= '<div class="thread-messages" style="display:none;">';
        foreach ($messages as $message) {
            $user_type = $message->user_type === 'Chatbot' ? 'Chatbot' : 'You';
            $output .= sprintf('<b>%s</b><br>%s<br>', esc_html($user_type), stripslashes(esc_html($message->message_text)));
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