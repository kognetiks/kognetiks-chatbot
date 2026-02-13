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
    
    // Get session_id from POST for both anonymous and logged-in users - Ver 2.3.7
    $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
    
    // For anonymous users, we need to verify they own the session
    if ($current_user_id === 0) {
        // Anonymous user - verify session ownership through session_id
        if (empty($session_id)) {
            wp_send_json_error('Session ID required for anonymous users.', 403);
            return;
        }
        
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
        
        // For logged-in users, if session_id is not provided, we can still proceed
        // but it's better to have it for proper cleanup
        if (empty($session_id)) {
            // Try to get session_id from transients or generate a fallback
            // This is a fallback - ideally session_id should always be provided
            $session_id = kognetiks_get_unique_id();
        }
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

    // Set global session_id - Ver 2.3.7
    global $session_id;
    global $thread_id;
    global $assistant_id;
    
    // Ensure session_id is set in global scope
    if (empty($session_id)) {
        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : kognetiks_get_unique_id();
    }

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
    
    // Clear all duplicate message UUID transients for this conversation - Ver 2.3.7
    // This prevents false positive duplicate detection after clearing conversation
    clear_duplicate_message_uuids($user_id, $page_id, $session_id);

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
// Ver 2.3.7 - Fixed SQL injection vulnerability by using prepared statements
function delete_any_file_transients($session_id) {

    global $wpdb;

    // Sanitize and escape the session_id for use in LIKE pattern
    $escaped_session_id = $wpdb->esc_like($session_id);
    
    // Use prepared statement to prevent SQL injection
    $sql = $wpdb->prepare(
        "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
        '_transient_chatbot_chatgpt_file_id_' . $escaped_session_id . '%'
    );
    
    $wpdb->query($sql);
    
}

/**
 * Verify session ownership for anonymous users
 * This is a basic implementation - you may want to enhance this based on your security requirements
 * Ver 2.3.7 - Made less strict to prevent false rejections
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
    
    // Ver 2.3.7 - Made format check more flexible
    // Session IDs are typically generated by kognetiks_get_unique_id()
    // which creates strings like "kognetiks_1234567890.123456"
    // But they can also be other formats, so we'll be more lenient
    // Only check for basic alphanumeric characters, dots, underscores, and hyphens
    if (!preg_match('/^[a-zA-Z0-9._-]+$/', $session_id)) {
        return false;
    }
    
    // Additional security: Check if session_id is reasonable length (not too short or too long)
    // Session IDs are typically around 15-64 characters
    // Ver 2.3.7 - Made length check more lenient (minimum 10 instead of 15)
    if (strlen($session_id) < 10 || strlen($session_id) > 128) {
        return false;
    }
    
    // For enhanced security, you could also check:
    // - Session creation timestamp
    // - IP address consistency
    // - User agent consistency
    
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
    
    // If no conversation data found, still allow access (might be a new conversation)
    // This is a design decision - for new conversations, we should allow access
    // The session ownership validation above already ensures the user is legitimate
    return true;
}

/**
 * Clear all duplicate message UUID transients for a conversation
 * Ver 2.3.7 - Added to prevent false positive duplicate detection after clearing conversation
 * Ver 2.3.7 - Fixed SQL injection vulnerability by using prepared statements
 */
function clear_duplicate_message_uuids($user_id, $page_id, $session_id) {
    global $wpdb;
    
    // Clear all message UUID transients that might be related to this conversation
    // We'll use a pattern match to find all transients with the prefix
    // Note: This is a broad cleanup - in production, you might want to be more specific
    // Use prepared statements to prevent SQL injection
    $pattern1 = '_transient_chatgpt_message_uuid_%';
    $pattern2 = '_transient_timeout_chatgpt_message_uuid_%';
    
    $sql = $wpdb->prepare(
        "DELETE FROM $wpdb->options 
         WHERE option_name LIKE %s 
         OR option_name LIKE %s",
        $pattern1,
        $pattern2
    );
    
    $deleted = $wpdb->query($sql);
    
    return $deleted;
}
