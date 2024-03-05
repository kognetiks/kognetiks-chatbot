<?php
/**
 * Kognetiks Chatbot for WordPress - File Uploads - Ver 1.7.6
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

function chatbot_chatgpt_check_for_links_and_images( $response ) {

    // DIAG - Diagnostic - Ver 1.9.1
    // back_trace( 'NOTICE', "Entering chatbot_chatgpt_check_for_links_and_images()" );
    // back_trace( 'NOTICE', "Response: " . print_r($response, true) );

    $response = preg_replace_callback('/(!)?\[([^\]]+)\]\(([^)]+)\)/', function($matches) {
        // If the first character is "!", it's an image
        if ($matches[1] === "!") {
            return "<span><img src='" . $matches[3] . "' alt='" . $matches[2] . "' style='max-width: 95%;' /></span>";
        } else {
            // Otherwise, it's a link
            return "<span><a href='" . $matches[3] . "' target='_blank'>" . $matches[2] . "</a></span>";
        }
    }, $response);

    return $response;

}