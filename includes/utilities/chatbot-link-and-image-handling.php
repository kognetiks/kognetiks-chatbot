<?php
/**
 * Kognetiks Chatbot - Link and Image Handling
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

function chatbot_chatgpt_check_for_links_and_images( $response ) {

    // DIAG - Diagnostics - Ver 1.9.1

    // Get stored image width
    $img_width = esc_attr(get_option('chatbot_chatgpt_image_width_setting', '100%'));
    if (!str_ends_with($img_width, 'px') && !str_ends_with($img_width, '%')) {
        $img_width = 'auto';
    } else if ($img_width === '100%') {
        $img_width = 'auto';
    }

    $response = preg_replace_callback('/(!)?\[([^\]]+)\]\(([^)]+)\)/', function($matches) use ($img_width) {
        // If the first character is "!", it's an image
        if ($matches[1] === "!") {
            return "<span><center><img src=\"" . $matches[3] . "\" alt=\"" . $matches[2] . "\" style=\"max-width: 95%; width: " . $img_width . ";\" /></center></span>";
        } else {
            // Otherwise, it's a link
            return "<span><a href=\"" . $matches[3] . "\" target=\"_blank\">" . $matches[2] . "</a></span>"; 
        }
    }, $response);

    return $response;
}

