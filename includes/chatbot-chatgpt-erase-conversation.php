<?php
/**
 * Chatbot ChatGPT for WordPress - Clear Conversation - Ver 1.8.6
 *
 * This file contains the code for uploading files as part
 * in support of Custom GPT Assistants via the Chatbot ChatGPT.
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

    $user_id = get_current_user_id();
    $page_id = isset($_POST['page_id']) ? intval($_POST['page_id']) : 0;
    $transient_type = 'assistant_alias';

    $assistant_id = get_chatbot_chatgpt_transients( $transient_type , $user_id, $page_id);

    if ( $assistant_id == '' ) {
        $reset_type = 'original';
    } else {
        $reset_type = 'assistant';
    }
    
    // DIAG - Diagnostics - Ver 1.8.6
    chatbot_chatgpt_back_trace( 'NOTICE', '$reset_type: ' . $reset_type);
    chatbot_chatgpt_back_trace( 'NOTICE', '$assistant_id: ' . $assistant_id);
    chatbot_chatgpt_back_trace( 'NOTICE', '$user_id: ' . $user_id);
    chatbot_chatgpt_back_trace( 'NOTICE', '$page_id: ' . $page_id);
    chatbot_chatgpt_back_trace( 'NOTICE', '$transient_type: ' . $transient_type);
    chatbot_chatgpt_back_trace( 'NOTICE', '$session_id: ' . $session_id);
    
    if ( $reset_type == 'original') {
        // Delete transient data
        delete_transient( 'chatbot_chatgpt_context_history' );
        wp_send_json_success('Conversation cleared - Original.');
    } else {
        // Delete transient data - Assistants
        delete_chatbot_chatgpt_transients( $transient_type, $user_id, $page_id, $session_id);
        // FIXME - This is not working - Ver 1.8.6
        wp_send_json_success('Conversation cleared - Assistant.');
    }

    wp_send_json_error('Conversation not cleared.');

}
