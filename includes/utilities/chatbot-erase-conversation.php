<?php
/**
 * Kognetiks Chatbot for WordPress - Clear Conversation - Ver 1.8.6
 *
 * This file contains the code for uploading files as part
 * in support of Custom GPT Assistants via the Chatbot.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

function chatbot_chatgpt_erase_conversation_handler(): void {

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
    }

    // DIAG - Diagnostics - Ver 1.9.1
    // back_trace( 'NOTICE', 'LINE 35 $user_id: ' . $user_id);
    // back_trace( 'NOTICE', 'LINE 36 $page_id: ' . $page_id);

    // DIAG - Diagnostics - Ver 1.8.6
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
    // back_trace( 'NOTICE', 'LINE 68 $user_id: ' . $user_id);
    // back_trace( 'NOTICE', 'LINE 69 $page_id: ' . $page_id);

    $transient_type = 'assistant_alias';
    
    $assistant_id = get_chatbot_chatgpt_transients( $transient_type , $user_id, $page_id);

    if ( $assistant_id == '' ) {
        $reset_type = 'original';
    } else {
        $reset_type = 'assistant';
    }
    
    if ( $reset_type == 'original') {
        // Delete transient data
        delete_transient( 'chatbot_chatgpt_context_history' );
        wp_send_json_success('Conversation cleared - Original.');
    } else {
        $thread_id = ''; // Nullify the thread_id
        // Wipe the Context
        update_option( 'chatbot_chatgpt_conversation_context' ,'' , true);
        // Delete transient data - Assistants
        // delete_chatbot_chatgpt_transients( $transient_type, $user_id, $page_id, $session_id);
        // Delete the threads
        delete_chatbot_chatgpt_threads($user_id, $page_id);
        wp_send_json_success('Conversation cleared - Assistant.');
    }

    // DIAG - Diagnostics - Ver 1.8.6
    // back_trace( 'NOTICE', '$reset_type: ' . $reset_type);
    // back_trace( 'NOTICE', '$assistant_id: ' . $assistant_id);
    // back_trace( 'NOTICE', '$user_id: ' . $user_id);
    // back_trace( 'NOTICE', '$page_id: ' . $page_id);
    // back_trace( 'NOTICE', '$transient_type: ' . $transient_type);
    // back_trace( 'NOTICE', '$session_id: ' . $session_id);

    wp_send_json_error('Conversation not cleared.');

}
