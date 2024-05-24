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
function chatbot_chatgpt_settings_init(): void {

    // Diagnostics settings tab - Ver 1.6.5
    register_setting('chatbot_chatgpt_diagnostics', 'chatbot_chatgpt_diagnostics');
    // Suppress Notices and Warnings
    register_setting('chatbot_chatgpt_diagnostics', 'chatbot_chatgpt_suppress_notices');
    register_setting('chatbot_chatgpt_diagnostics', 'chatbot_chatgpt_suppress_attribution');
    register_setting('chatbot_chatgpt_diagnostics', 'chatbot_chatgpt_delete_data');

    add_settings_section(
        'chatbot_chatgpt_diagnostics_section',
        'Diagnostics Settings',
        'chatbot_chatgpt_diagnostics_section_callback',
        'chatbot_chatgpt_diagnostics'
    );

    // Option to check API status - Ver 1.6.5
    add_settings_field(
        'chatbot_chatgpt_api_test',
        'API Test Results',
        'chatbot_chatgpt_api_test_callback',
        'chatbot_chatgpt_diagnostics',
        'chatbot_chatgpt_diagnostics_section'
    );

    // Option to set diagnostics on/off - Ver 1.5.0
    add_settings_field(
        'chatbot_chatgpt_diagnostics',
        'Chatbot Diagnostics',
        'chatbot_chatgpt_diagnostics_setting_callback',
        'chatbot_chatgpt_diagnostics',
        'chatbot_chatgpt_diagnostics_section'
    );

    // Option to suppress notices and warnings - Ver 1.6.5
    add_settings_field(
        'chatbot_chatgpt_suppress_notices',
        'Suppress Notices and Warnings',
        'chatbot_chatgpt_suppress_notices_callback',
        'chatbot_chatgpt_diagnostics',
        'chatbot_chatgpt_diagnostics_section'
    );

    // Option to suppress attribution - Ver 1.6.5
    add_settings_field(
        'chatbot_chatgpt_suppress_attribution',
        'Suppress Attribution',
        'chatbot_chatgpt_suppress_attribution_callback',
        'chatbot_chatgpt_diagnostics',
        'chatbot_chatgpt_diagnostics_section'
    );

    // Option to delete data on uninstall - Ver 1.9.9
    add_settings_field(
        'chatbot_chatgpt_delete_data',
        'Delete Plugin Data on Uninstall',
        'chatbot_chatgpt_delete_data_callback',
        'chatbot_chatgpt_diagnostics',
        'chatbot_chatgpt_diagnostics_section'
    );
    
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

    // Support settings tab - Ver 1.3.0
    register_setting('chatbot_chatgpt_support', 'chatgpt_support_key');

    add_settings_section(
        'chatbot_chatgpt_support_section',
        'Support',
        'chatbot_chatgpt_support_section_callback',
        'chatbot_chatgpt_support'
    );

    // Avatar settings tab - Ver 1.5.0
    register_setting('chatbot_chatgpt_avatar', 'chatbot_chatgpt_avatar_icon_setting');
    register_setting('chatbot_chatgpt_avatar', 'chatbot_chatgpt_avatar_icon_url_setting');
    register_setting('chatbot_chatgpt_avatar', 'chatbot_chatgpt_custom_avatar_icon_setting');
    register_setting('chatbot_chatgpt_avatar', 'chatbot_chatgpt_avatar_greeting_setting');
    register_setting('chatbot_chatgpt_avatar', 'chatbot_chatgpt_avatar_icon_set');

    // Register a new section in the "chatbot_chatgpt" page
    add_settings_section(
        'chatbot_chatgpt_avatar_section', 
        'Avatar Settings', 
        'chatbot_chatgpt_avatar_section_callback', 
        'chatbot_chatgpt_avatar'
    );

    // Avatar Greeting
    add_settings_field(
        'chatbot_chatgpt_avatar_greeting_setting',
        'Avatar Greeting',
        'chatbot_chatgpt_avatar_greeting_callback',
        'chatbot_chatgpt_avatar',
        'chatbot_chatgpt_avatar_section'
    );

    // Custom Avatar URL
    add_settings_field(
        'chatbot_chatgpt_custom_avatar_icon_setting',
        'Custom Avatar URL (60x60px)',
        'chatbot_chatgpt_custom_avatar_callback',
        'chatbot_chatgpt_avatar',
        'chatbot_chatgpt_avatar_section'
    );

    // Avatar Icon Set
    // add_settings_field(
    //     'chatbot_chatgpt_avatar_icon_set',
    //     'Avatar Icon Set',
    //     'chatbot_chatgpt_avatar_icon_set_callback',
    //     'chatbot_chatgpt_avatar',
    //     'chatbot_chatgpt_avatar_section'
    // );
    
    // Avatar Icon Selection - None, Custom, or one from the various sets
    add_settings_field(
        'chatbot_chatgpt_avatar_icon_setting',
        'Avatar Icon Options',
        'chatbot_chatgpt_avatar_icon_callback',
        'chatbot_chatgpt_avatar',
        'chatbot_chatgpt_avatar_section'
    );

    // Reporting settings tab - Ver 1.6.1
    register_setting('chatbot_chatgpt_reporting', 'chatbot_chatgpt_reporting_period');
    register_setting('chatbot_chatgpt_reporting', 'chatbot_chatgpt_enable_conversation_logging');
    register_setting('chatbot_chatgpt_reporting', 'chatbot_chatgpt_conversation_log_days_to_keep');

    add_settings_section(
        'chatbot_chatgpt_reporting_section',
        'Reporting',
        'chatbot_chatgpt_reporting_section_callback',
        'chatbot_chatgpt_reporting'
    );

    add_settings_field(
        'chatbot_chatgpt_reporting_period',
        'Reporting Period',
        'chatbot_chatgpt_reporting_period_callback',
        'chatbot_chatgpt_reporting',
        'chatbot_chatgpt_reporting_section'
    );

    add_settings_field(
        'chatbot_chatgpt_enable_conversation_logging',
        'Enable Conversation Logging',
        'chatbot_chatgpt_enable_conversation_logging_callback',
        'chatbot_chatgpt_reporting',
        'chatbot_chatgpt_reporting_section'
    );

    add_settings_field(
        'chatbot_chatgpt_conversation_log_days_to_keep',
        'Conversation Log Days to Keep',
        'chatbot_chatgpt_conversation_log_days_to_keep_callback',
        'chatbot_chatgpt_reporting',
        'chatbot_chatgpt_reporting_section'
    );

    // Custom Buttons settings tab - Ver 1.6.5
    register_setting('chatbot_chatgpt_custom_buttons', 'chatbot_chatgpt_enable_custom_buttons');
    register_setting('chatbot_chatgpt_custom_buttons', 'chatbot_chatgpt_custom_button_name_1');
    register_setting('chatbot_chatgpt_custom_buttons', 'chatbot_chatgpt_custom_button_url_1');
    register_setting('chatbot_chatgpt_custom_buttons', 'chatbot_chatgpt_custom_button_name_2');
    register_setting('chatbot_chatgpt_custom_buttons', 'chatbot_chatgpt_custom_button_url_2');

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

}

add_action('admin_init', 'chatbot_chatgpt_settings_init');
