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

    // API/Model settings tab - Ver 1.3.0
    // register_setting('chatbot_chatgpt_api_model', 'chatbot_chatgpt_api_key');
    // Obfuscate the API key in settings registration - Ver 1.5.0
    register_setting('chatbot_chatgpt_api_model', 'chatbot_chatgpt_api_key', 'sanitize_api_key');
    register_setting('chatbot_chatgpt_api_model', 'chatbot_chatgpt_model_choice');
    register_setting('chatbot_chatgpt_api_model', 'chatbot_chatgpt_max_tokens_setting'); // Max Tokens setting options - Ver 1.4.2
    register_setting('chatbot_chatgpt_api_model', 'chatbot_chatgpt_conversation_context'); // Conversation Context - Ver 1.6.1
    register_setting('chatbot_chatgpt_api_model', 'chatbot_chatgpt_base_url'); // Ver 1.8.1
    register_setting('chatbot_chatgpt_api_model', 'chatbot_chatgpt_timeout_setting'); // Ver 1.8.8

    add_settings_section(
        'chatbot_chatgpt_api_model_section',
        'API/Model Settings',
        'chatbot_chatgpt_api_model_section_callback',
        'chatbot_chatgpt_api_model'
    );

    add_settings_field(
        'chatbot_chatgpt_api_key',
        'ChatGPT API Key',
        'chatbot_chatgpt_api_key_callback',
        'chatbot_chatgpt_api_model',
        'chatbot_chatgpt_api_model_section'
    );

    add_settings_field(
        'chatbot_chatgpt_model_choice',
        'ChatGPT Model Choice',
        'chatbot_chatgpt_model_choice_callback',
        'chatbot_chatgpt_api_model',
        'chatbot_chatgpt_api_model_section'
    );
    
    // Setting to adjust in small increments the number of Max Tokens - Ver 1.4.2
    add_settings_field(
        'chatbot_chatgpt_max_tokens_setting',
        'Maximum Tokens Setting',
        'chatgpt_max_tokens_setting_callback',
        'chatbot_chatgpt_api_model',
        'chatbot_chatgpt_api_model_section'
    );

    // Setting to adjust the conversation context - Ver 1.4.2
    add_settings_field(
        'chatbot_chatgpt_conversation_context',
        'Conversation Context',
        'chatbot_chatgpt_conversation_context_callback',
        'chatbot_chatgpt_api_model',
        'chatbot_chatgpt_api_model_section'
    );

    // Set the base URL for the API - Ver 1.8.1
    add_settings_field(
        'chatbot_chatgpt_base_url',
        'Base URL',
        'chatbot_chatgpt_base_url_callback',
        'chatbot_chatgpt_api_model',
        'chatbot_chatgpt_api_model_section'
    );

    // Timeout setting - Ver 1.8.8
    add_settings_field(
        'chatbot_chatgpt_timeout_setting',
        'Timeout Setting (in seconds)',
        'chatbot_chatgpt_timeout_setting_callback',
        'chatbot_chatgpt_api_model',
        'chatbot_chatgpt_api_model_section'
    );

    
    // Settings Custom GPTs tab - Ver 1.7.2
    register_setting('chatbot_chatgpt_custom_gpts', 'chatbot_chatgpt_use_custom_gpt_assistant_id'); // Ver 1.6.7
    register_setting('chatbot_chatgpt_custom_gpts', 'chatbot_chatgpt_allow_file_uploads'); // Ver 1.7.6
    register_setting('chatbot_chatgpt_custom_gpts', 'chatbot_chatgpt_assistant_id'); // Ver 1.6.7
    register_setting('chatbot_chatgpt_custom_gpts', 'chatbot_chatgpt_assistant_id_alternate'); // Alternate Assistant - Ver 1.7.2

    add_settings_section(
        'chatbot_chatgpt_custom_gpts_section',
        'GPT Assistant Settings',
        'chatbot_chatgpt_gpt_assistants_section_callback',
        'chatbot_chatgpt_custom_gpts'
    );
    
    // Use GPT Assistant ID (Yes or No) - Ver 1.6.7
    add_settings_field(
        'chatbot_chatgpt_use_custom_gpt_assistant_id',
        'Use GPT Assistant Id',
        'chatbot_chatgpt_use_gpt_assistant_id_callback',
        'chatbot_chatgpt_custom_gpts',
        'chatbot_chatgpt_custom_gpts_section'
    );

    // Allow file uploads to the Assistant - Ver 1.7.6
    add_settings_field(
        'chatbot_chatgpt_allow_file_uploads',
        'Allow File Uploads',
        'chatbot_chatgpt_allow_file_uploads_callback',
        'chatbot_chatgpt_custom_gpts',
        'chatbot_chatgpt_custom_gpts_section'
    );

    // CustomGPT Assistant Id - Ver 1.6.7
    add_settings_field(
        'chatbot_chatgpt_assistant_id',
        'Primary GPT Assistant Id',
        'chatbot_chatgpt_assistant_id_callback',
        'chatbot_chatgpt_custom_gpts',
        'chatbot_chatgpt_custom_gpts_section'
    );

    // CustomGPT Assistant Id Alternate - Ver 1.7.2
    add_settings_field(
        'chatbot_chatgpt_assistant_id_alternate',
        'Alternate GPT Assistant Id',
        'chatbot_chatgpt_assistant_id_alternate_callback',
        'chatbot_chatgpt_custom_gpts',
        'chatbot_chatgpt_custom_gpts_section'
    );


    // Settings settings tab - Ver 1.3.0
    register_setting('chatbot_chatgpt_settings', 'chatbot_chatgpt_bot_name');
    register_setting('chatbot_chatgpt_settings', 'chatbot_chatgpt_start_status');
    register_setting('chatbot_chatgpt_settings', 'chatbot_chatgpt_start_status_new_visitor');
    register_setting('chatbot_chatgpt_settings', 'chatbot_chatgpt_bot_prompt');
    register_setting('chatbot_chatgpt_settings', 'chatbot_chatgpt_initial_greeting');
    register_setting('chatbot_chatgpt_settings', 'chatbot_chatgpt_subsequent_greeting');
    register_setting('chatbot_chatgpt_settings', 'chatbot_chatgpt_disclaimer_setting');
    register_setting('chatbot_chatgpt_settings', 'chatbot_chatgpt_audience_choice');
    register_setting('chatbot_chatgpt_settings', 'chatbot_chatgpt_diagnostics');

    add_settings_section(
        'chatbot_chatgpt_settings_section',
        'Settings',
        'chatbot_chatgpt_settings_section_callback',
        'chatbot_chatgpt_settings'
    );

    add_settings_field(
        'chatbot_chatgpt_bot_name',
        'Chatbot Name',
        'chatbot_chatgpt_bot_name_callback',
        'chatbot_chatgpt_settings',
        'chatbot_chatgpt_settings_section'
    );

    add_settings_field(
        'chatbot_chatgpt_start_status',
        'Start Status',
        'chatbot_chatgptStartStatus_callback',
        'chatbot_chatgpt_settings',
        'chatbot_chatgpt_settings_section'
    );

    add_settings_field(
        'chatbot_chatgpt_start_status_new_visitor',
        'Start Status New Visitor',
        'chatbot_chatbot_chatgpt_start_status_new_visitor_callback',
        'chatbot_chatgpt_settings',
        'chatbot_chatgpt_settings_section'
    );

        add_settings_field(
        'chatbot_chatgpt_bot_prompt',
        'Chatbot Prompt',
        'chatbot_chatgpt_bot_prompt_callback',
        'chatbot_chatgpt_settings',
        'chatbot_chatgpt_settings_section'
    );

    add_settings_field(
        'chatbot_chatgpt_initial_greeting',
        'Initial Greeting',
        'chatbot_chatgpt_initial_greeting_callback',
        'chatbot_chatgpt_settings',
        'chatbot_chatgpt_settings_section'
    );

    add_settings_field(
        'chatbot_chatgpt_subsequent_greeting',
        'Subsequent Greeting',
        'chatbot_chatgpt_subsequent_greeting_callback',
        'chatbot_chatgpt_settings',
        'chatbot_chatgpt_settings_section'
    );

    // Option to remove the OpenAI disclaimer - Ver 1.4.1
    add_settings_field(
        'chatbot_chatgpt_disclaimer_setting',
        'Include "As an AI language model" disclaimer',
        'chatgpt_disclaimer_setting_callback',
        'chatbot_chatgpt_settings',
        'chatbot_chatgpt_settings_section'
    );

    // Audience setting - Ver 1.9.0
    add_settings_field(
        'chatbot_chatgpt_audience_choice',
        'Audience for Chatbot',
        'chatbot_chatgpt_audience_choice_callback',
        'chatbot_chatgpt_settings',
        'chatbot_chatgpt_settings_section'
    );

    // Moved to Appearance tab in Ver 1.8.1
    // Option to change the width of the bot from narrow to wide - Ver 1.4.2
    // add_settings_field(
    //     'chatbot_chatgpt_width_setting',
    //     'Chatbot Width Setting',
    //     'chatbot_chatgpt_width_setting_callback',
    //     'chatbot_chatgpt_settings',
    //     'chatbot_chatgpt_settings_section'
    // );

    // Diagnostics settings tab - Ver 1.6.5
    register_setting('chatbot_chatgpt_diagnostics', 'chatbot_chatgpt_diagnostics');
    // Suppress Notices and Warnings
    register_setting('chatbot_chatgpt_diagnostics', 'chatbot_chatgpt_suppress_notices');
    register_setting('chatbot_chatgpt_diagnostics', 'chatbot_chatgpt_suppress_attribution');
    register_setting('chatbot_chatgpt_diagnostics', 'chatbot_chatgpt_suppress_learnings');
    register_setting('chatbot_chatgpt_diagnostics', 'chatbot_chatgpt_custom_learnings_message');

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

    // Option to suppress learnings messages - Ver 1.7.1
    add_settings_field(
        'chatbot_chatgpt_suppress_learnings',
        'Suppress Learnings Messages',
        'chatbot_chatgpt_suppress_learnings_callback',
        'chatbot_chatgpt_diagnostics',
        'chatbot_chatgpt_diagnostics_section'
    );

    // Option to set custom learnings message - Ver 1.7.1
    add_settings_field(
        'chatbot_chatgpt_custom_learnings_message',
        'Custom Learnings Message',
        'chatbot_chatgpt_custom_learnings_message_callback',
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

    // Knowledge Navigator settings tab - Ver 1.6.1
    register_setting('chatbot_chatgpt_kn_settings_section', 'chatbot_chatgpt_kn_schedule'); // Schedule Daily, Weekly, Monthly,etc.
    register_setting('chatbot_chatgpt_kn_settings_section', 'chatbot_chatgpt_kn_maximum_top_words');
    register_setting('chatbot_chatgpt_kn_settings_section', 'chatbot_chatgpt_kn_include_posts');
    register_setting('chatbot_chatgpt_kn_settings_section', 'chatbot_chatgpt_kn_include_pages');
    register_setting('chatbot_chatgpt_kn_settings_section', 'chatbot_chatgpt_kn_include_products');
    register_setting('chatbot_chatgpt_kn_settings_section', 'chatbot_chatgpt_kn_include_comments');

    add_settings_section(
        'chatbot_chatgpt_knowledge_navigator_settings_section',
        'Knowledge Navigator',
        'chatbot_chatgpt_knowledge_navigator_section_callback',
        'chatbot_chatgpt_knowledge_navigator'
    );

    add_settings_section(
        'chatbot_chatgpt_kn_settings_section',
        '<hr style="border-top: 2px solid;">Knowledge Navigator Settings',
        'chatbot_chatgpt_kn_settings_section_callback',
        'chatbot_chatgpt_knowledge_navigator'
    );

    add_settings_field(
        'chatbot_chatgpt_kn_schedule',
        'Select Run Schedule',
        'chatbot_chatgpt_kn_schedule_callback',
        'chatbot_chatgpt_knowledge_navigator',
        'chatbot_chatgpt_kn_settings_section'
    );

    add_settings_field(
        'chatbot_chatgpt_kn_maximum_top_words',
        'Maximum Top Words',
        'chatbot_chatgpt_kn_maximum_top_words_callback',
        'chatbot_chatgpt_knowledge_navigator',
        'chatbot_chatgpt_kn_settings_section'
    );

    add_settings_field(
        'chatbot_chatgpt_kn_include_posts',
        'Include Published Posts',
        'chatbot_chatgpt_kn_include_posts_callback',
        'chatbot_chatgpt_knowledge_navigator',
        'chatbot_chatgpt_kn_settings_section'
    );

    add_settings_field(
        'chatbot_chatgpt_kn_include_pages',
        'Include Published Pages',
        'chatbot_chatgpt_kn_include_pages_callback',
        'chatbot_chatgpt_knowledge_navigator',
        'chatbot_chatgpt_kn_settings_section'
    );

    add_settings_field(
        'chatbot_chatgpt_kn_include_products',
        'Include Published Products',
        'chatbot_chatgpt_kn_include_products_callback',
        'chatbot_chatgpt_knowledge_navigator',
        'chatbot_chatgpt_kn_settings_section'
    );

    add_settings_field(
        'chatbot_chatgpt_kn_include_comments',
        'Include Approved Comments',
        'chatbot_chatgpt_kn_include_comments_callback',
        'chatbot_chatgpt_knowledge_navigator',
        'chatbot_chatgpt_kn_settings_section'
    );

    // Knowledge Navigator Analysis settings tab - Ver 1.6.1
    register_setting('chatbot_chatgpt_kn_analysis', 'chatbot_chatgpt_kn_analysis_output');

    add_settings_section(
        'chatbot_chatgpt_kn_analysis_section',
        'Knowledge Navigator Analysis',
        'chatbot_chatgpt_kn_analysis_section_callback',
        'chatbot_chatgpt_kn_analysis'
    );

    add_settings_field(
        'chatbot_chatgpt_kn_analysis_output',
        'Output Format',
        'chatbot_chatgpt_kn_analysis_output_callback',
        'chatbot_chatgpt_kn_analysis',
        'chatbot_chatgpt_kn_analysis_section'
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
