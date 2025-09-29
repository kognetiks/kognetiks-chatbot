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

    // FIXME - This is not working - Ver 1.8.6
    // THIS IS NOT CONFIRMED WORKING YET FOR ASSISTANTS
    // ITS NOT SETTING THE $page_id CORRECTLY
    // THEREFORE ITS NOT DELETING THE CORRECT TRANSIENT (ANY TRANSIENT)

    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;

    if (isset($_POST['user_id']) && isset($_POST['page_id'])) {
        $user_id = $_POST['user_id'];
        $page_id = $_POST['page_id'];
        $chatbot_chatgpt_force_page_reload = $_POST['chatbot_chatgpt_force_page_reload'];
    }

    // DIAG - Diagnostics - Ver 1.9.1
    // back_trace( 'NOTICE', '$user_id: ' . $user_id);
    // back_trace( 'NOTICE', '$page_id: ' . $page_id);
    // back_trace( 'NOTICE', '$session_id: ' . $session_id);
    // back_trace( 'NOTICE', '$thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', '$assistant_id: ' . $assistant_id);

    // $user_id = get_current_user_id();
    if ( $user_id !== '' ) {
        // DIAG = Diagnostics
        // back_trace( 'NOTICE', '$user_id: ' . $user_id);
    } else {
        // DIAG = Diagnostics
        // back_trace( 'NOTICE', 'No user is currently logged in.');
        // back_trace( 'NOTICE', '$session_id: ' . $session_id);
        // Removed - Ver 1.9.0
        // $user_id = $session_id;
        // Add back - Ver 1.9.3 - 2024 03 18
        $user_id = $_POST['user_id'];
    }

    if ( $page_id !== '') {
        // DIAG = Diagnostics
        // back_trace( 'NOTICE', '$page_id: ' . $page_id);
    } else {
        // DIAG = Diagnostics
        // back_trace( 'NOTICE', 'No page is currently set.');
        // back_trace( 'NOTICE', '$post: ' . $post);
        $page_id = $_POST['page_id'];
    }

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
    
    // if ( $reset_type == 'original') {
    //     // Delete transient data
    //     delete_transient( 'chatbot_chatgpt_context_history' );
    //     wp_send_json_success('Conversation cleared - Original.');
    // } else {
    //     $thread_id = ''; // Nullify the thread_id
    //     // Wipe the Context
    //     update_option( 'chatbot_chatgpt_conversation_context' ,'' , true);
    //     // Delete transient data - Assistants
    //     // delete_chatbot_chatgpt_transients( $transient_type, $user_id, $page_id, $session_id);
    //     // Delete the threads
    //     delete_chatbot_chatgpt_threads($user_id, $page_id);
    //     delete_any_file_transients($session_id);
    //     wp_send_json_success('Conversation cleared - Assistant.');
    // }

    delete_transient( 'chatbot_chatgpt_context_history' );

    $thread_id = ''; // Nullify the thread_id
    // Wipe the Context
    update_option( 'chatbot_chatgpt_conversation_context' ,'' , true);

    delete_chatbot_chatgpt_threads($user_id, $page_id);
    delete_any_file_transients($session_id);
    
    // Clear the message queue for this conversation
    $queue_key = 'chatbot_message_queue_' . md5($assistant_id . '|' . $user_id . '|' . $page_id . '|' . $session_id);
    delete_transient($queue_key);
    
    // Clear any conversation locks
    $conv_lock = 'chatgpt_conv_lock_' . md5($assistant_id . '|' . $user_id . '|' . $page_id . '|' . $session_id);
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
