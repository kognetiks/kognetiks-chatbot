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
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;

    global $post;

    $user_id = get_current_user_id();
    if ( $user_id !== '' ) {
        // DIAG = Diagnostics
        chatbot_chatgpt_back_trace( 'NOTICE', '$user_id: ' . $user_id);
    } else {
        // DIAG = Diagnostics
        chatbot_chatgpt_back_trace( 'NOTICE', 'No user is currently logged in.');
        chatbot_chatgpt_back_trace( 'NOTICE', '$session_id: ' . $session_id);
        $user_id = $session_id;
    }

    if ( $page_id !== '') {
        // DIAG = Diagnostics
        chatbot_chatgpt_back_trace( 'NOTICE', '$page_id: ' . $page_id);
    } else {
        // DIAG = Diagnostics
        chatbot_chatgpt_back_trace( 'NOTICE', 'No page is currently set.');
        chatbot_chatgpt_back_trace( 'NOTICE', '$post: ' . $post);
        $page_id = $post->ID;
    }

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
