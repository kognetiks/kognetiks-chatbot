<?php
/**
 * Kognetiks Chatbot for WordPress - Registration - API Settings
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
function chatbot_chatgpt_api_settings_init(): void {

    add_settings_section(
        'chatbot_chatgpt_model_settings_section',
        'API/Model Settings',
        'chatbot_chatgpt_model_settings_section_callback',
        'chatbot_chatgpt_model_settings_general'
    );

    // API/Model settings tab - Ver 1.3.0
    register_setting('chatbot_chatgpt_api_model', 'chatbot_chatgpt_api_key', 'sanitize_api_key');
    register_setting('chatbot_chatgpt_api_model', 'chatbot_chatgpt_message_limit_setting');
    register_setting('chatbot_chatgpt_api_model', 'chatbot_chatgpt_visitor_message_limit_setting');

    add_settings_section(
        'chatbot_chatgpt_api_model_general_section',
        'API Settings',
        'chatbot_chatgpt_api_model_general_section_callback',
        'chatbot_chatgpt_api_model_general'
    );

    add_settings_field(
        'chatbot_chatgpt_api_key',
        'ChatGPT API Key',
        'chatbot_chatgpt_api_key_callback',
        'chatbot_chatgpt_api_model_general',
        'chatbot_chatgpt_api_model_general_section'
    );

    add_settings_field(
        'chatbot_chatgpt_message_limit_setting',
        'Chatbot Daily Message Limit',
        'chatbot_chatgpt_message_limit_setting_callback',
        'chatbot_chatgpt_api_model_general',
        'chatbot_chatgpt_api_model_general_section'
    );

    add_settings_field(
        'chatbot_chatgpt_visitor_message_limit_setting',
        'Visitor Daily Message Limit',
        'chatbot_chatgpt_visitor_message_limit_setting_callback',
        'chatbot_chatgpt_api_model_general',
        'chatbot_chatgpt_api_model_general_section'
    );

    // Advanced Model Settings - Ver 1.9.5
    register_setting('chatbot_chatgpt_api_model', 'chatbot_chatgpt_base_url'); // Ver 1.8.1
    register_setting('chatbot_chatgpt_api_model', 'chatbot_chatgpt_timeout_setting'); // Ver 1.8.8

    add_settings_section(
        'chatbot_chatgpt_api_model_advanced_section',
        'Advanced API Settings',
        'chatbot_chatgpt_api_model_advanced_section_callback',
        'chatbot_chatgpt_api_model_advanced'
    );

    // Set the base URL for the API - Ver 1.8.1
    add_settings_field(
        'chatbot_chatgpt_base_url',
        'Base URL for API',
        'chatbot_chatgpt_base_url_callback',
        'chatbot_chatgpt_api_model_advanced',
        'chatbot_chatgpt_api_model_advanced_section'
    );

    // Timeout setting - Ver 1.8.8
    add_settings_field(
        'chatbot_chatgpt_timeout_setting',
        'Timeout Setting (in seconds)',
        'chatbot_chatgpt_timeout_setting_callback',
        'chatbot_chatgpt_api_model_advanced',
        'chatbot_chatgpt_api_model_advanced_section'
    );

    // Chat Options - Ver 1.9.5
    register_setting('chatbot_chatgpt_api_model', 'chatbot_chatgpt_model_choice');
    register_setting('chatbot_chatgpt_api_model', 'chatbot_chatgpt_max_tokens_setting'); // Max Tokens setting options - Ver 1.4.2
    register_setting('chatbot_chatgpt_api_model', 'chatbot_chatgpt_conversation_context'); // Conversation Context - Ver 1.6.1

    add_settings_section(
        'chatbot_chatgpt_api_model_chat_section',
        'Chat Settings',
        'chatbot_chatgpt_api_model_chat_section_callback',
        'chatbot_chatgpt_api_model_chat'
    );

    add_settings_field(
        'chatbot_chatgpt_model_choice',
        'ChatGPT Model Default',
        'chatbot_chatgpt_model_choice_callback',
        'chatbot_chatgpt_api_model_chat',
        'chatbot_chatgpt_api_model_chat_section'
    );

    // Setting to adjust in small increments the number of Max Tokens - Ver 1.4.2
    add_settings_field(
        'chatbot_chatgpt_max_tokens_setting',
        'Maximum Tokens Setting',
        'chatgpt_max_tokens_setting_callback',
        'chatbot_chatgpt_api_model_chat',
        'chatbot_chatgpt_api_model_chat_section'
    );

    // Setting to adjust the conversation context - Ver 1.4.2
    add_settings_field(
        'chatbot_chatgpt_conversation_context',
        'Conversation Context',
        'chatbot_chatgpt_conversation_context_callback',
        'chatbot_chatgpt_api_model_chat',
        'chatbot_chatgpt_api_model_chat_section'
    );

    // Voice Options - Ver 1.9.5
    register_setting('chatbot_chatgpt_api_model', 'chatbot_chatgpt_voice_model_option'); // Ver 1.9.5
    register_setting('chatbot_chatgpt_api_model', 'chatbot_chatgpt_voice_option'); // Ver 1.9.5
    register_setting('chatbot_chatgpt_api_model', 'chatbot_chatgpt_audio_output_format'); // Ver 1.9.5
    register_setting('chatbot_chatgpt_api_model', 'chatbot_chatgpt_read_aloud_option'); // Ver 2.0.0
    
    // Voice Options - Ver 1.9.5
    add_settings_section(
        'chatbot_chatgpt_api_model_voice_section',
        'Voice Settings (Text to Speech)',
        'chatbot_chatgpt_api_model_voice_section_callback',
        'chatbot_chatgpt_api_model_voice'
    );

    // Voice Option - Ver 1.9.5
    add_settings_field(
        'chatbot_chatgpt_voice_model_option',
        'Voice Model Default',
        'chatbot_chatgpt_voice_model_option_callback',
        'chatbot_chatgpt_api_model_voice',
        'chatbot_chatgpt_api_model_voice_section'
    );

    // Voice Option
    add_settings_field(
        'chatbot_chatgpt_voice_option',
        'Voice',
        'chatbot_chatgpt_voice_option_callback',
        'chatbot_chatgpt_api_model_voice',
        'chatbot_chatgpt_api_model_voice_section'
    );

    // Audio Output Options
    add_settings_field(
        'chatbot_chatgpt_audio_output_format',
        'Audio Output Option',
        'chatbot_chatgpt_audio_output_format_callback',
        'chatbot_chatgpt_api_model_voice',
        'chatbot_chatgpt_api_model_voice_section'
    );

    // Allow Read Aloud - Ver 2.0.0
    add_settings_field(
        'chatbot_chatgpt_read_aloud_option',
        'Allow Read Aloud',
        'chatbot_chatgpt_read_aloud_option_callback',
        'chatbot_chatgpt_api_model_voice',
        'chatbot_chatgpt_api_model_voice_section'
    );

    // Image Options - Ver 1.9.5
    register_setting('chatbot_chatgpt_api_model', 'chatbot_chatgpt_image_model_option'); // Ver 1.9.5
    register_setting('chatbot_chatgpt_api_model', 'chatbot_chatgpt_image_output_format'); // Ver 1.9.5
    register_setting('chatbot_chatgpt_api_model', 'chatbot_chatgpt_image_output_size'); // Ver 1.9.5
    register_setting('chatbot_chatgpt_api_model', 'chatbot_chatgpt_image_output_quantity'); // Ver 1.9.5
    register_setting('chatbot_chatgpt_api_model', 'chatbot_chatgpt_image_output_quality'); // Ver 1.9.5
    register_setting('chatbot_chatgpt_api_model', 'chatbot_chatgpt_image_style_output'); // Ver 1.9.5

    // Image Options - Ver 1.9.5
    add_settings_section(
        'chatbot_chatgpt_api_model_image_section',
        'Image Settings',
        'chatbot_chatgpt_api_model_image_section_callback',
        'chatbot_chatgpt_api_model_image'
    );

    add_settings_field(
        'chatbot_chatgpt_image_model_option',
        'Image Model Default',
        'chatbot_chatgpt_image_model_option_callback',
        'chatbot_chatgpt_api_model_image',
        'chatbot_chatgpt_api_model_image_section'
    );

    add_settings_field(
        'chatbot_chatgpt_image_output_format',
        'Image Output Option',
        'chatbot_chatgpt_image_output_format_callback',
        'chatbot_chatgpt_api_model_image',
        'chatbot_chatgpt_api_model_image_section'
    );

    add_settings_field(
        'chatbot_chatgpt_image_output_size',
        'Image Output Size',
        'chatbot_chatgpt_image_output_size_callback',
        'chatbot_chatgpt_api_model_image',
        'chatbot_chatgpt_api_model_image_section'
    );

    add_settings_field(
        'chatbot_chatgpt_image_output_quantity',
        'Image Quantity',
        'chatbot_chatgpt_image_output_quantity_callback',
        'chatbot_chatgpt_api_model_image',
        'chatbot_chatgpt_api_model_image_section'
    );

    add_settings_field(
        'chatbot_chatgpt_image_output_quality',
        'Image Quality',
        'chatbot_chatgpt_image_output_quality_callback',
        'chatbot_chatgpt_api_model_image',
        'chatbot_chatgpt_api_model_image_section'
    );

    add_settings_field(
        'chatbot_chatgpt_image_style_output',
        'Image Style Output',
        'chatbot_chatgpt_image_style_output_callback',
        'chatbot_chatgpt_api_model_image',
        'chatbot_chatgpt_api_model_image_section'
    );

    // Whisper Options - Ver 2.0.1
    register_setting('chatbot_chatgpt_api_model', 'chatbot_chatgpt_whisper_model_option');
    register_setting('chatbot_chatgpt_api_model', 'chatbot_chatgpt_whisper_response_format');

    // Image Options - Ver 1.9.5
    add_settings_section(
        'chatbot_chatgpt_api_model_whisper_section',
        'Whisper Settings (Speech to Text)',
        'chatbot_chatgpt_api_model_whisper_section_callback',
        'chatbot_chatgpt_api_model_whisper'
    );

    add_settings_field(
        'chatbot_chatgpt_whisper_model_option',
        'Whisper Model Default',
        'chatbot_chatgpt_whisper_model_option_callback',
        'chatbot_chatgpt_api_model_whisper',
        'chatbot_chatgpt_api_model_whisper_section'
    );

    add_settings_field(
        'chatbot_chatgpt_whisper_response_format',
        'Whisper Output Option',
        'chatbot_chatgpt_whisper_response_format_callback',
        'chatbot_chatgpt_api_model_whisper',
        'chatbot_chatgpt_api_model_whisper_section'
    );

}
add_action('admin_init', 'chatbot_chatgpt_api_settings_init');