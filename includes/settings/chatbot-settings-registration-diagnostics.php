<?php
/**
 * Kognetiks Chatbot for WordPress - Registration - Diagnostic Settings - Ver 2.0.7
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

// Register Diagnostics settings
function chatbot_chatgpt_diagnostics_settings_init() {

    register_setting('chatbot_chatgpt_diagnostics', 'chatbot_chatgpt_diagnostics');
    register_setting('chatbot_chatgpt_diagnostics', 'chatbot_chatgpt_custom_error_message');
    register_setting('chatbot_chatgpt_diagnostics', 'chatbot_chatgpt_suppress_notices');
    register_setting('chatbot_chatgpt_diagnostics', 'chatbot_chatgpt_suppress_attribution');
    register_setting('chatbot_chatgpt_diagnostics', 'chatbot_chatgpt_delete_data');

    add_settings_section(
        'chatbot_chatgpt_diagnostics_overview_section',
        'Messages and Diagnostics Overview',
        'chatbot_chatgpt_diagnostics_overview_section_callback',
        'chatbot_chatgpt_diagnostics_overview'
    );

    add_settings_section(
        'chatbot_chatgpt_diagnostics_system_settings_section',
        'Platform Settings',
        'chatbot_chatgpt_diagnostics_system_settings_section_callback',
        'chatbot_chatgpt_diagnostics_system_settings'
    );

    // Diagnotics API Status
    add_settings_section(
        'chatbot_chatgpt_diagnostics_api_status_section',
        'API Status and Results',
        'chatbot_chatgpt_diagnostics_api_status_section_callback',
        'chatbot_chatgpt_diagnostics_api_status'
    );

    add_settings_field(
        'chatbot_chatgpt_api_test',
        'API Test Results',
        'chatbot_chatgpt_api_test_callback',
        'chatbot_chatgpt_diagnostics',
        'chatbot_chatgpt_diagnostics_api_status_section'
    );

    // Diagnostic Settings Section
    add_settings_section(
        'chatbot_chatgpt_diagnostics_section',
        'Messages and Diagnostics Settings',
        'chatbot_chatgpt_diagnostics_section_callback',
        'chatbot_chatgpt_diagnostics'
    );

    // Option to set diagnostics on/off - Ver 1.5.0
    add_settings_field(
        'chatbot_chatgpt_diagnostics',
        'Chatbot Diagnostics',
        'chatbot_chatgpt_diagnostics_setting_callback',
        'chatbot_chatgpt_diagnostics',
        'chatbot_chatgpt_diagnostics_section'
    );

    // Custom Error Message - Ver 2.0.3
    add_settings_field(
        'chatbot_chatgpt_custom_error_message',
        'Custom Error Message',
        'chatbot_chatgpt_custom_error_message_callback',
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
    
}
add_action('admin_init', 'chatbot_chatgpt_diagnostics_settings_init');