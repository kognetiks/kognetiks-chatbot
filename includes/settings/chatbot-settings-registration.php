<?php
/**
 * Kognetiks Chatbot - Registration
 *
 * This file contains the code for the Chatbot settings page.
 * It handles the registration of settings and other parameters.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Register settings
function chatbot_chatgpt_settings_init() {

    
}

add_action('admin_init', 'chatbot_chatgpt_settings_init');
