<?php
/**
 * Chatbot ChatGPT for WordPress - Registration
 *
 * This file contains the code for the Chatbot ChatGPT settings page.
 * It allows users to configure the API key and other parameters
 * required to access the ChatGPT API from their own account.
 *
 * @package chatbot-chatgpt
 */

 // Register settings
function chatbot_chatgpt_settings_init() {

    // API/Model settings tab - Ver 1.3.0
    // register_setting('chatbot_chatgpt_api_model', 'chatgpt_api_key');
    // Obfuscate the API key in settings registration - Ver 1.5.0
    register_setting('chatbot_chatgpt_api_model', 'chatgpt_api_key', 'sanitize_api_key');
    register_setting('chatbot_chatgpt_api_model', 'chatgpt_model_choice');
    // Max Tokens setting options - Ver 1.4.2
    register_setting('chatbot_chatgpt_api_model', 'chatgpt_max_tokens_setting');
    // Covnersation Context - Ver 1.6.1
    register_setting('chatbot_chatgpt_api_model', 'chatbot_chatgpt_conversation_context');

    add_settings_section(
        'chatbot_chatgpt_api_model_section',
        'API/Model Settings',
        'chatbot_chatgpt_api_model_section_callback',
        'chatbot_chatgpt_api_model'
    );

    add_settings_field(
        'chatgpt_api_key',
        'ChatGPT API Key',
        'chatbot_chatgpt_api_key_callback',
        'chatbot_chatgpt_api_model',
        'chatbot_chatgpt_api_model_section'
    );

    add_settings_field(
        'chatgpt_model_choice',
        'ChatGPT Model Choice',
        'chatbot_chatgpt_model_choice_callback',
        'chatbot_chatgpt_api_model',
        'chatbot_chatgpt_api_model_section'
    );
    
    // Setting to adjust in small increments the number of Max Tokens - Ver 1.4.2
    add_settings_field(
        'chatgpt_max_tokens_setting',
        'Maximum Tokens Setting',
        'chatgpt_max_tokens_setting_callback',
        'chatbot_chatgpt_api_model',
        'chatbot_chatgpt_api_model_section'
    );

    // Setting to adjust in small increments the number of Max Tokens - Ver 1.4.2
    add_settings_field(
        'chatbot_chatgpt_conversation_context',
        'Conversation Context',
        'chatbot_chatgpt_conversation_context_callback',
        'chatbot_chatgpt_api_model',
        'chatbot_chatgpt_api_model_section'
    );


    // Settings settings tab - Ver 1.3.0
    register_setting('chatbot_chatgpt_settings', 'chatgpt_bot_name');
    register_setting('chatbot_chatgpt_settings', 'chatgptStartStatus');
    register_setting('chatbot_chatgpt_settings', 'chatgptStartStatusNewVisitor');
    register_setting('chatbot_chatgpt_settings', 'chatgpt_initial_greeting');
    register_setting('chatbot_chatgpt_settings', 'chatgpt_subsequent_greeting');
    // Option to remove the OpenAI disclaimer - Ver 1.4.1
    register_setting('chatbot_chatgpt_settings', 'chatgpt_disclaimer_setting');
    // Option to select narrow or wide chatboat - Ver 1.4.2
    register_setting('chatbot_chatgpt_settings', 'chatgpt_width_setting');
    // Option to set diagnotics on/off - Ver 1.5.0
    register_setting('chatbot_chatgpt_settings', 'chatgpt_diagnostics');

    add_settings_section(
        'chatbot_chatgpt_settings_section',
        'Settings',
        'chatbot_chatgpt_settings_section_callback',
        'chatbot_chatgpt_settings'
    );

    add_settings_field(
        'chatgpt_bot_name',
        'Bot Name',
        'chatbot_chatgpt_bot_name_callback',
        'chatbot_chatgpt_settings',
        'chatbot_chatgpt_settings_section'
    );

    add_settings_field(
        'chatgptStartStatus',
        'Start Status',
        'chatbot_chatgptStartStatus_callback',
        'chatbot_chatgpt_settings',
        'chatbot_chatgpt_settings_section'
    );

    add_settings_field(
        'chatgptStartStatusNewVisitor',
        'Start Status New Visitor',
        'chatbot_chatgptStartStatusNewVisitor_callback',
        'chatbot_chatgpt_settings',
        'chatbot_chatgpt_settings_section'
    );

    add_settings_field(
        'chatgpt_initial_greeting',
        'Initial Greeting',
        'chatbot_chatgpt_initial_greeting_callback',
        'chatbot_chatgpt_settings',
        'chatbot_chatgpt_settings_section'
    );

    add_settings_field(
        'chatgpt_subsequent_greeting',
        'Subsequent Greeting',
        'chatbot_chatgpt_subsequent_greeting_callback',
        'chatbot_chatgpt_settings',
        'chatbot_chatgpt_settings_section'
    );

    // Option to remove the OpenAI disclaimer - Ver 1.4.1
    add_settings_field(
        'chatgpt_disclaimer_setting',
        'Include "As an AI language model" disclaimer',
        'chatgpt_disclaimer_setting_callback',
        'chatbot_chatgpt_settings',
        'chatbot_chatgpt_settings_section'
    );

    // Option to change the width of the bot from narrow to wide - Ver 1.4.2
    add_settings_field(
        'chatgpt_width_setting',
        'Chatbot Width Setting',
        'chatgpt_width_setting_callback',
        'chatbot_chatgpt_settings',
        'chatbot_chatgpt_settings_section'
    );

    // Option to set diagnostics on/off - Ver 1.5.0
    add_settings_field(
        'chatgpt_diagnostics',
        'Chatbot Diagnostics',
        'chatgpt_diagnostics_setting_callback',
        'chatbot_chatgpt_settings',
        'chatbot_chatgpt_settings_section'
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
    register_setting('chatbot_chatgpt_avatar', 'chatgpt_avatar_icon_setting');
    register_setting('chatbot_chatgpt_avatar', 'chatgpt_avatar_icon_url_setting');
    register_setting('chatbot_chatgpt_avatar', 'chatgpt_custom_avatar_icon_setting');
    register_setting('chatbot_chatgpt_avatar', 'chatgpt_avatar_greeting_setting');

    // Register a new section in the "chatbot_chatgpt" page
    add_settings_section(
        'chatbot_chatgpt_avatar_section', 
        'Avatar Settings', 
        'chatbot_chatgpt_avatar_section_callback', 
        'chatbot_chatgpt_avatar'
    );

    // Register new fields in the "chatbot_chatgpt_avatar_section" section, inside the "chatbot_chatgpt_avatar" page
    add_settings_field(
        'chatgpt_avatar_icon_setting',
        'Avatar Icon Setting',
        'chatbot_chatgpt_avatar_icon_callback',
        'chatbot_chatgpt_avatar',
        'chatbot_chatgpt_avatar_section'
    );

    // Coming in Ver 2.0.0
    // add_settings_field(
    //     'chatgpt_custom_avatar_icon_setting',
    //     'Custom Avatar URL',
    //     'chatbot_chatgpt_custom_avatar_callback',
    //     'chatbot_chatgpt_avatar',
    //     'chatbot_chatgpt_avatar_section'
    // );

    add_settings_field(
        'chatgpt_avatar_greeting_setting',
        'Avatar Greeting',
        'chatbot_chatgpt_avatar_greeting_callback',
        'chatbot_chatgpt_avatar',
        'chatbot_chatgpt_avatar_section'
    );

    // Support settings tab - Ver 1.6.1
    register_setting('chatbot_chatgpt_knowledge_navigator', 'chatbot_chatgpt_knowledge_navigator');
    register_setting('chatbot_chatgpt_knowledge_navigator', 'chatbot_chatgpt_kn_maximum_depth');
    register_setting('chatbot_chatgpt_knowledge_navigator', 'chatbot_chatgpt_kn_maximum_top_words');
    register_setting('chatbot_chatgpt_knowledge_navigator', 'chatbot_chatgpt_kn_results');
    register_setting('chatbot_chatgpt_knowledge_navigator', 'chatbot_chatgpt_kn_conversation_context');

    add_settings_section(
        'chatbot_chatgpt_knowledge_navigator_section',
        'Knowledge Navigator&trade;',
        'chatbot_chatgpt_knowledge_navigator_section_callback',
        'chatbot_chatgpt_knowledge_navigator'
    );

    add_settings_field(
        'chatbot_chatgpt_kn_maximum_depth',
        'Maximum Depth',
        'chatbot_chatgpt_kn_maximum_depth_callback',
        'chatbot_chatgpt_knowledge_navigator',
        'chatbot_chatgpt_knowledge_navigator_section'
    );

    add_settings_field(
        'chatbot_chatgpt_kn_maximum_top_words',
        'Maximum Top Words',
        'chatbot_chatgpt_kn_maximum_top_words_callback',
        'chatbot_chatgpt_knowledge_navigator',
        'chatbot_chatgpt_knowledge_navigator_section'
    );

    add_settings_field(
        'chatbot_chatgpt_knowledge_navigator',
        'Run Knowledge Navigator&trade;',
        'chatbot_chatgpt_knowledge_navigator_callback',
        'chatbot_chatgpt_knowledge_navigator',
        'chatbot_chatgpt_knowledge_navigator_section'
    );

}

add_action('admin_init', 'chatbot_chatgpt_settings_init');
