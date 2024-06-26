<?php
/**
 * Kognetiks Chatbot for WordPress - Options Helper Function - Ver 2.0.5
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

// Options helper function - Ve 2.0.5
function options_helper($assistant_details, $option_name, $default_value) {

    if (!isset($assistant_details[$option_name]) || $assistant_details[$option_name] == '') {
        $assistant_details[$option_name] = esc_attr(get_option("chatbot_chatgpt_{$option_name}", $default_value));

        // DIAG - Diagnotics - Ver 2.0.5
        back_trace( 'NOTICE', 'Option: ' . $option_name . ' - Value: ' . $assistant_details[$option_name] . ' - Option Not Set');

    }

    back_trace( 'NOTICE', 'Option: ' . $option_name . ' - Value: ' . $assistant_details[$option_name] . ' - Option Set');

    return $assistant_details;
}

