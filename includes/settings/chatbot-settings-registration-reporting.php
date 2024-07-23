<?php
/**
 * Kognetiks Chatbot for WordPress - Registration - Reporting Settings - Ver 2.0.7
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

// Register Reporting settings
function chatbot_chatgpt_reporting_settings_init() {

        // Register settings for Reporting
        register_setting('chatbot_chatgpt_reporting', 'chatbot_chatgpt_reporting_period');
        register_setting('chatbot_chatgpt_reporting', 'chatbot_chatgpt_enable_conversation_logging');
        register_setting('chatbot_chatgpt_reporting', 'chatbot_chatgpt_conversation_log_days_to_keep');
    
        // Reporting Overview Section
        add_settings_section(
            'chatbot_chatgpt_reporting_overview_section',
            'Reporting Overview',
            'chatbot_chatgpt_reporting_overview_section_callback',
            'chatbot_chatgpt_reporting_overview'
        );
    
        // Reporting Settings Section
        add_settings_section(
            'chatbot_chatgpt_reporting_section',
            'Reporting Settings',
            'chatbot_chatgpt_reporting_section_callback',
            'chatbot_chatgpt_reporting'
        );
    
        // Reporting Settings Field - Reporting Period
        add_settings_field(
            'chatbot_chatgpt_reporting_period',
            'Reporting Period',
            'chatbot_chatgpt_reporting_period_callback',
            'chatbot_chatgpt_reporting',
            'chatbot_chatgpt_reporting_section'
        );
    
        // Reporting Settings Field - Enable Conversation Logging
        add_settings_field(
            'chatbot_chatgpt_enable_conversation_logging',
            'Enable Conversation Logging',
            'chatbot_chatgpt_enable_conversation_logging_callback',
            'chatbot_chatgpt_reporting',
            'chatbot_chatgpt_reporting_section'
        );
    
        // Reporting Settings Field - Conversation Log Days to Keep
        add_settings_field(
            'chatbot_chatgpt_conversation_log_days_to_keep',
            'Conversation Log Days to Keep',
            'chatbot_chatgpt_conversation_log_days_to_keep_callback',
            'chatbot_chatgpt_reporting',
            'chatbot_chatgpt_reporting_section'
        );
    
        // Conversation Data Section
        add_settings_section(
            'chatbot_chatgpt_conversation_reporting_section',
            'Conversation Data',
            'chatbot_chatgpt_conversation_reporting_section_callback',
            'chatbot_chatgpt_conversation_reporting'
        );
    
        add_settings_field(
            'chatbot_chatgpt_conversation_reporting_field',
            'Conversation Data',
            'chatbot_chatgpt_conversation_reporting_callback',
            'chatbot_chatgpt_reporting',
            'chatbot_chatgpt_conversation_reporting_section'
        );
    
        // Interaction Data Section
        add_settings_section(
            'chatbot_chatgpt_interaction_reporting_section',
            'Interaction Data',
            'chatbot_chatgpt_interaction_reporting_section_callback',
            'chatbot_chatgpt_interaction_reporting'
        );
    
        add_settings_field(
            'chatbot_chatgpt_interaction_reporting_field',
            'Interaction Data',
            'chatbot_chatgpt_interaction_reporting_callback',
            'chatbot_chatgpt_reporting',
            'chatbot_chatgpt_interaction_reporting_section'
        );
    
        // // Token Data Section
        add_settings_section(
            'chatbot_chatgpt_token_reporting_section',
            'Token Data',
            'chatbot_chatgpt_token_reporting_section_callback',
            'chatbot_chatgpt_token_reporting'
        );
    
        add_settings_field(
            'chatbot_chatgpt_token_reporting_field',
            'Token Data',
            'chatbot_chatgpt_token_reporting_callback',
            'chatbot_chatgpt_reporting',
            'chatbot_chatgpt_token_reporting_section'
        );
       
}
add_action('admin_init', 'chatbot_chatgpt_reporting_settings_init');