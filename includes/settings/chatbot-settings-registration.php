<?php
/**
 * Kognetiks Chatbot for WordPress - Registration
 *
 * This file contains the code for the Chatbot settings page.
 * It handles the registration of settings and other parameters.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Register settings
function chatbot_chatgpt_settings_init() {

    // Premium settings tab - Ver 1.3.0
    register_setting('chatbot_chatgpt_premium', 'chatgpt_premium_key');

    add_settings_section(
        'chatbot_chatgpt_premium_section',
        'Premium Settings',
        'chatbot_chatgpt_premium_section_callback',
        'chatbot_chatgpt_premium'
    );

    add_settings_field(
        'chatgpt_premium_key',
        'Premium Options',
        'chatbot_chatgpt_premium_key_callback',
        'chatbot_chatgpt_premium',
        'chatbot_chatgpt_premium_section'
    );

    // Custom Buttons settings tab - Ver 1.6.5
    register_setting('chatbot_chatgpt_custom_buttons', 'chatbot_chatgpt_enable_custom_buttons');
    register_setting('chatbot_chatgpt_custom_buttons', 'chatbot_chatgpt_custom_button_name_1');
    register_setting('chatbot_chatgpt_custom_buttons', 'chatbot_chatgpt_custom_button_url_1');
    register_setting('chatbot_chatgpt_custom_buttons', 'chatbot_chatgpt_custom_button_name_2');
    register_setting('chatbot_chatgpt_custom_buttons', 'chatbot_chatgpt_custom_button_url_2');
    register_setting('chatbot_chatgpt_custom_buttons', 'chatbot_chatgpt_custom_button_name_3');
    register_setting('chatbot_chatgpt_custom_buttons', 'chatbot_chatgpt_custom_button_url_3');
    register_setting('chatbot_chatgpt_custom_buttons', 'chatbot_chatgpt_custom_button_name_4');
    register_setting('chatbot_chatgpt_custom_buttons', 'chatbot_chatgpt_custom_button_url_4');

    add_settings_section(
        'chatbot_chatgpt_custom_button_section',
        'Custom Buttons',
        'chatbot_chatgpt_custom_button_section_callback',
        'chatbot_chatgpt_custom_buttons'
    );

    add_settings_field(
        'chatbot_chatgpt_enable_custom_buttons',
        'Custom Buttons (On/Off)',
        'chatbot_chatgpt_enable_custom_buttons_callback',
        'chatbot_chatgpt_custom_buttons',
        'chatbot_chatgpt_custom_button_section'
    );

    add_settings_field(
        'chatbot_chatgpt_custom_button_name_1',
        'Custom Button 1 Name',
        'chatbot_chatgpt_custom_button_name_1_callback',
        'chatbot_chatgpt_custom_buttons',
        'chatbot_chatgpt_custom_button_section'
    );

    add_settings_field(
        'chatbot_chatgpt_custom_button_url_1',
        'Custom Button 1 Link',
        'chatbot_chatgpt_custom_button_link_1_callback',
        'chatbot_chatgpt_custom_buttons',
        'chatbot_chatgpt_custom_button_section'
    );

    add_settings_field(
        'chatbot_chatgpt_custom_button_name_2',
        'Custom Button 2 Name',
        'chatbot_chatgpt_custom_button_name_2_callback',
        'chatbot_chatgpt_custom_buttons',
        'chatbot_chatgpt_custom_button_section'
    );

    add_settings_field(
        'chatbot_chatgpt_custom_button_url_2',
        'Custom Button 2 Link',
        'chatbot_chatgpt_custom_button_link_2_callback',
        'chatbot_chatgpt_custom_buttons',
        'chatbot_chatgpt_custom_button_section'
    );

    add_settings_field(
        'chatbot_chatgpt_custom_button_name_3',
        'Custom Button 3 Name',
        'chatbot_chatgpt_custom_button_name_3_callback',
        'chatbot_chatgpt_custom_buttons',
        'chatbot_chatgpt_custom_button_section'
    );

    add_settings_field(
        'chatbot_chatgpt_custom_button_url_3',
        'Custom Button 3 Link',
        'chatbot_chatgpt_custom_button_link_3_callback',
        'chatbot_chatgpt_custom_buttons',
        'chatbot_chatgpt_custom_button_section'
    );

    add_settings_field(
        'chatbot_chatgpt_custom_button_name_4',
        'Custom Button 4 Name',
        'chatbot_chatgpt_custom_button_name_4_callback',
        'chatbot_chatgpt_custom_buttons',
        'chatbot_chatgpt_custom_button_section'
    );

    add_settings_field(
        'chatbot_chatgpt_custom_button_url_4',
        'Custom Button 4 Link',
        'chatbot_chatgpt_custom_button_link_4_callback',
        'chatbot_chatgpt_custom_buttons',
        'chatbot_chatgpt_custom_button_section'
    );
    
}

add_action('admin_init', 'chatbot_chatgpt_settings_init');
