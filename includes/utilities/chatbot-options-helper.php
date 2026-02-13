<?php
/**
 * Kognetiks Chatbot - Options Helper Function - Ver 2.0.5
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

// Options helper function - Ve 2.0.5
function options_helper($assistant_details, $option_name, $default_value) {

    if (!isset($assistant_details[$option_name]) || $assistant_details[$option_name] == '') {
        $assistant_details[$option_name] = esc_attr(get_option("chatbot_chatgpt_{$option_name}", $default_value));
    }

    return $assistant_details;
}

