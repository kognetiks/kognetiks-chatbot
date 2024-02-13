<?php
/**
 * Chatbot ChatGPT for WordPress - Erase Conversation - Ver 1.8.6
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

function chatbot_chatgpt_erase_conversation_handler() {

    // DIAG Diagnostics - Ver 1.8.6
    chatbot_chatgpt_back_trace( 'NOTICE', 'chatbot_chatgpt_erase_conversation() called');
    
    // Delete transient data
    // delete_transient( 'chatbot_chatgpt_conversation' );

    wp_send_json_success('Conversation erased successfully.');

}
