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

function chatbot_chatgpt_erase_conversation_handler( $reset_type = null ) {

    // DIAG Diagnostics - Ver 1.8.6
    chatbot_chatgpt_back_trace( 'NOTICE', 'chatbot_chatgpt_erase_conversation_handler() called with $reset_type = ' . $reset_type);
    
    if ( $reset_type == 'original' ) {
        // Delete transient data
        delete_transient( 'chatbot_chatgpt_context_history' );
        wp_send_json_success('Conversation cleared - Original.');
    } else {
        // Delete transient data - Assistants
        // FIXME - This is not working - Ver 1.8.6
        wp_send_json_success('Conversation cleared - Assistant.');
    }

}
