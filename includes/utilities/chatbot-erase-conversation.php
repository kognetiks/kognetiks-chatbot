<?php
/**
 * Kognetiks Chatbot - Clear Conversation - Ver 1.8.6
 *
 * This file contains the code for uploading files as part
 * in support of Custom GPT Assistants via the Chatbot.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

function chatbot_chatgpt_erase_conversation_handler() {

    // Security: Verify nonce for CSRF protection
    if (!isset($_POST['chatbot_nonce']) || !wp_verify_nonce($_POST['chatbot_nonce'], 'chatbot_erase_nonce')) {
        wp_send_json_error('Security check failed. Please refresh the page and try again.', 403);
        return;
    }

    // Security: Get current user and verify authorization
    $current_user = wp_get_current_user();
    $current_user_id = $current_user->ID;
    
    // For anonymous users, we need to verify they own the session
    if ($current_user_id === 0) {
        // Anonymous user - verify session ownership through session_id
        if (!isset($_POST['session_id'])) {
            wp_send_json_error('Session ID required for anonymous users.', 403);
            return;
        }
        $session_id = sanitize_text_field($_POST['session_id']);
        
        // Verify the session belongs to the current request
        // This is a basic check - in a more secure implementation, you might want to
        // store session ownership in a more secure way
        if (!verify_session_ownership($session_id)) {
            wp_send_json_error('Unauthorized access to conversation.', 403);
            return;
        }
        
        // For anonymous users, use session_id as user_id
        $user_id = $session_id;
    } else {
        // Logged-in user - use their actual user ID
        $user_id = $current_user_id;
    }

    // Get page_id from POST (this is safe as it's just identifying the page)
    $page_id = isset($_POST['page_id']) ? sanitize_text_field($_POST['page_id']) : '';
    $chatbot_chatgpt_force_page_reload = isset($_POST['chatbot_chatgpt_force_page_reload']) ? sanitize_text_field($_POST['chatbot_chatgpt_force_page_reload']) : 'No';
    
    if (empty($page_id)) {
        wp_send_json_error('Page ID is required.', 400);
        return;
    }

    // Additional security: Verify the conversation belongs to this user
    if (!verify_conversation_ownership($user_id, $page_id)) {
        wp_send_json_error('Unauthorized access to conversation.', 403);
        return;
    }

    global $session_id;
    global $thread_id;
    global $assistant_id;

    // DIAG - Diagnostics - Ver 1.9.1
    // back_trace( 'NOTICE', '$user_id: ' . $user_id);
    // back_trace( 'NOTICE', '$page_id: ' . $page_id);

    $transient_type = 'assistant_alias';
    
    $assistant_id = get_chatbot_chatgpt_transients( $transient_type , $user_id, $page_id, $session_id);
    $voice = get_chatbot_chatgpt_transients( 'voice', $user_id, $page_id, $session_id);

    if ( $assistant_id == '' ) {
        $reset_type = 'original';
    } else {
        $reset_type = 'assistant';
    }

    delete_transient( 'chatbot_chatgpt_context_history' );

    $thread_id = ''; // Nullify the thread_id
    // Wipe the Context
    update_option( 'chatbot_chatgpt_conversation_context' ,'' , true);

    delete_chatbot_chatgpt_threads($user_id, $page_id);
    delete_any_file_transients($session_id);
    
    // Clear the message queue for this conversation
    $queue_key = 'chatbot_message_queue_' . wp_hash($assistant_id . '|' . $user_id . '|' . $page_id . '|' . $session_id);
    delete_transient($queue_key);
    
    // Clear any conversation locks
    $conv_lock = 'chatgpt_conv_lock_' . wp_hash($assistant_id . '|' . $user_id . '|' . $page_id . '|' . $session_id);
    delete_transient($conv_lock);

    // DIAG - Diagnostics - Ver 2.0.4
    // back_trace( 'NOTICE', '$chatbot_chatgpt_force_page_reload: ' . $chatbot_chatgpt_force_page_reload);

    if ($chatbot_chatgpt_force_page_reload == 'Yes') {
        global $chatbot_chatgpt_fixed_literal_messages;
        // Define a default fallback message
        $default_message = 'Conversation cleared. Please wait while the page reloads.';
        $success_message = isset($chatbot_chatgpt_fixed_literal_messages[16])
            ? $chatbot_chatgpt_fixed_literal_messages[16]
            : $default_message;
        // Send error response
        wp_send_json_success($success_message);
    } else {
        global $chatbot_chatgpt_fixed_literal_messages;
        // Define a default fallback message
        $default_message = 'Conversation cleared.';
        $success_message = isset($chatbot_chatgpt_fixed_literal_messages[17])
            ? $chatbot_chatgpt_fixed_literal_messages[17]
            : $default_message;
        // Send error response
        wp_send_json_success($success_message);
    }


    // DIAG - Diagnostics - Ver 1.8.6
    // back_trace( 'NOTICE', '$reset_type: ' . $reset_type);
    // back_trace( 'NOTICE', '$assistant_id: ' . $assistant_id);
    // back_trace( 'NOTICE', '$user_id: ' . $user_id);
    // back_trace( 'NOTICE', '$page_id: ' . $page_id);
    // back_trace( 'NOTICE', '$transient_type: ' . $transient_type);
    // back_trace( 'NOTICE', '$session_id: ' . $session_id);

    global $chatbot_chatgpt_fixed_literal_messages;
        
    // Define a default fallback message
    $default_message = 'Conversation not cleared.';
    $error_message = isset($chatbot_chatgpt_fixed_literal_messages[18])
        ? $chatbot_chatgpt_fixed_literal_messages[18]
        : $default_message;
    // Send error response
    wp_send_json_error($error_message);

}

// Delete any file transients - Ver 1.9.3
// THIS IS VERY AGGRESSIVE - USE WITH CAUTION
function delete_any_file_transients($session_id) {

    global $wpdb;

    $sql = "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_chatbot_chatgpt_file_id_$session_id%'";
    $wpdb->query($sql);
    
}

/**
 * Verify session ownership for anonymous users
 * This is a basic implementation - you may want to enhance this based on your security requirements
 */
function verify_session_ownership($session_id) {
    // Basic validation - ensure session_id is not empty and follows expected format
    if (empty($session_id) || !is_string($session_id)) {
        return false;
    }
    
    // Additional checks could include:
    // - Verifying the session was created from the same IP
    // - Checking session creation time
    // - Validating session format/pattern
    
    // For now, we'll do a basic format check
    // Session IDs are typically generated by kognetiks_get_unique_id()
    // which creates alphanumeric strings
    if (!preg_match('/^[a-zA-Z0-9]+$/', $session_id)) {
        return false;
    }
    
    return true;
}

/**
 * Verify conversation ownership
 * This function checks if the user actually owns the conversation they're trying to delete
 */
function verify_conversation_ownership($user_id, $page_id) {
    global $wpdb;
    
    // Check if there are any conversation records for this user/page combination
    // This is a basic check - you might want to enhance this based on your data structure
    
    // Check for conversation logs
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %s AND page_id = %s",
            $user_id,
            $page_id
        ));
        
        // If there are conversation records, allow deletion
        if ($count > 0) {
            return true;
        }
    }
    
    // Check for transients related to this conversation
    $transient_keys = [
        'chatbot_chatgpt_thread_id_' . $user_id . '_' . $page_id,
        'chatbot_chatgpt_assistant_id_' . $user_id . '_' . $page_id,
    ];
    
    foreach ($transient_keys as $key) {
        if (get_transient($key) !== false) {
            return true;
        }
    }
    
    // If no conversation data found, still allow deletion (might be a new conversation)
    // This is a design decision - you might want to be more restrictive
    return true;
}
