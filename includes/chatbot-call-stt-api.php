<?php
/**
 * Kognetiks Chatbot for WordPress - ChatGPT TTS API - Ver 1.9.4
 *
 * This file contains the code for generating images using the 
 * the DALL-2 or DALL-3 API.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Call the ChatGPT API
function chatbot_chatgpt_call_stt_api ($api_key, $message) {

    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;
    global $script_data_array;
    global $additional_instructions;
    global $model;
    global $voice;

    global $learningMessages;
    global $errorResponses;

    Return 'COMING SOON: STT API is not yet implemented.';

}


