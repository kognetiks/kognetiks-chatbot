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
    
    // Named apart from $session_id because `global $session_id` below rebinds that name.
    $verified_session_id = isset($_POST['session_id']) ? sanitize_text_field(wp_unslash($_POST['session_id'])) : '';
    
    // Anonymous visitors stay user 0. The signed cookie, not the posted id alone, proves ownership.
    if ($current_user_id === 0) {
        if ($verified_session_id === '') {
            wp_send_json_error('Session ID required for anonymous users.', 403);
            return;
        }
        
        if (!verify_session_ownership($verified_session_id)) {
            wp_send_json_error('Unauthorized access to conversation.', 403);
            return;
        }
        
        $user_id = 0;
    } else {
        $user_id = $current_user_id;
        
        if ($verified_session_id === '') {
            $fallback_session = kognetiks_get_unique_id();
            $verified_session_id = is_string($fallback_session) ? $fallback_session : '';
        }
    }

    // Get page_id from POST (this is safe as it's just identifying the page)
    $page_id = isset($_POST['page_id']) ? sanitize_text_field(wp_unslash($_POST['page_id'])) : '';
    $assistant_id_from_request = isset($_POST['assistant_id']) ? sanitize_text_field(wp_unslash($_POST['assistant_id'])) : '';
    $chatbot_chatgpt_force_page_reload = isset($_POST['chatbot_chatgpt_force_page_reload']) ? sanitize_text_field(wp_unslash($_POST['chatbot_chatgpt_force_page_reload'])) : 'No';
    
    if (empty($page_id)) {
        wp_send_json_error('Page ID is required.', 400);
        return;
    }

    // Additional security: Verify the conversation belongs to this user
    if (!verify_conversation_ownership($user_id, $page_id, $verified_session_id)) {
        wp_send_json_error('Unauthorized access to conversation.', 403);
        return;
    }

    // Set global session_id - Ver 2.3.7
    global $session_id;
    global $thread_id;
    global $assistant_id;
    $session_id = $verified_session_id;

    $transient_type = 'assistant_alias';
    
    $assistant_id = $assistant_id_from_request;
    if ( $assistant_id === '' ) {
        $assistant_id = get_chatbot_chatgpt_transients( $transient_type , $user_id, $page_id, $session_id);
    }
    $voice = get_chatbot_chatgpt_transients( 'voice', $user_id, $page_id, $session_id);

    if ( $assistant_id == '' ) {
        $reset_type = 'original';
    } else {
        $reset_type = 'assistant';
    }

    if ( $session_id !== '' && function_exists( 'chatbot_chatgpt_tts_last_reply_key' ) ) {
        delete_transient( chatbot_chatgpt_tts_last_reply_key( $session_id ) );
    }

    $thread_id = ''; // Nullify the thread_id

    $erased = chatbot_chatgpt_erase_visitor_conversation( $user_id, $page_id, $session_id, $assistant_id );
    if ( is_wp_error( $erased ) ) {
        wp_send_json_error( $erased->get_error_message(), 403 );
        return;
    }
    delete_any_file_transients($session_id);
    
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

    if ( ! is_string( $session_id ) || ! preg_match( '/^[a-f0-9]{32}$/', $session_id ) ) {
        return;
    }

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
