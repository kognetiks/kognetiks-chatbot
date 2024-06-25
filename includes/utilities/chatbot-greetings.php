<?php
/**
 * Kognetiks Chatbot for WordPress - Filter out HTML Tags from Content
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

// Greetings helper function
function get_and_update_greeting($assistant_details, $option_name, $default_value) {
    if (!isset($assistant_details[$option_name]) || $assistant_details[$option_name] == '') {
        $assistant_details[$option_name] = esc_attr(get_option("chatbot_chatgpt_{$option_name}", $default_value));
    }
    return $assistant_details;
}

