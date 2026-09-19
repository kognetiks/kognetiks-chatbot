<?php
/**
 * Kognetiks Chatbot - Registration - API Settings
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

// Register API settings
function chatbot_chatgpt_api_settings_init() {

    add_settings_section(
        'chatbot_chatgpt_model_settings_section',
        'API/ChatGPT Settings',
        'chatbot_chatgpt_model_settings_section_callback',
        'chatbot_chatgpt_model_settings_general'
    );

    // API/ChatGPT settings tab - Ver 1.3.0
    register_setting('chatbot_chatgpt_api_chatgpt', 'chatbot_chatgpt_api_key', 'chatbot_chatgpt_sanitize_api_key');

    add_settings_section(
        'chatbot_chatgpt_api_chatgpt_general_section',
        'ChatGPT API Settings',
        'chatbot_chatgpt_api_chatgpt_general_section_callback',
        'chatbot_chatgpt_api_chatgpt_general'
    );

    add_settings_field(
        'chatbot_chatgpt_api_key',
        'ChatGPT API Key',
        'chatbot_chatgpt_api_key_callback',
        'chatbot_chatgpt_api_chatgpt_general',
        'chatbot_chatgpt_api_chatgpt_general_section'
    );

    // Advanced Model Settings - Ver 1.9.5
    register_setting(
        'chatbot_chatgpt_api_chatgpt',
        'chatbot_chatgpt_base_url',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
        )
    );
    register_setting(
        'chatbot_chatgpt_api_chatgpt',
        'chatbot_chatgpt_timeout_setting',
        array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 240,
        )
    );

    add_settings_section(
        'chatbot_chatgpt_api_chatgpt_advanced_section',
        'Advanced API Settings',
        'chatbot_chatgpt_api_chatgpt_advanced_section_callback',
        'chatbot_chatgpt_api_chatgpt_advanced'
    );

    // Set the base URL for the API - Ver 1.8.1
    add_settings_field(
        'chatbot_chatgpt_base_url',
        'Base URL for API',
        'chatbot_chatgpt_base_url_callback',
        'chatbot_chatgpt_api_chatgpt_advanced',
        'chatbot_chatgpt_api_chatgpt_advanced_section'
    );

    // Timeout setting - Ver 1.8.8
    add_settings_field(
        'chatbot_chatgpt_timeout_setting',
        'Timeout Setting (in seconds)',
        'chatbot_chatgpt_timeout_setting_callback',
        'chatbot_chatgpt_api_chatgpt_advanced',
        'chatbot_chatgpt_api_chatgpt_advanced_section'
    );

    // Chat Options - Ver 1.9.5
    register_setting(
        'chatbot_chatgpt_api_chatgpt',
        'chatbot_chatgpt_api_enabled',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'Yes',
        )
    );
    register_setting(
        'chatbot_chatgpt_api_chatgpt',
        'chatbot_chatgpt_model_choice',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        )
    );
    register_setting(
        'chatbot_chatgpt_api_chatgpt',
        'chatbot_chatgpt_max_tokens_setting',
        array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 1000,
        )
    );
    register_setting(
        'chatbot_chatgpt_api_chatgpt',
        'chatbot_chatgpt_conversation_context',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
        )
    );
    register_setting(
        'chatbot_chatgpt_api_chatgpt',
        'chatbot_chatgpt_temperature',
        array(
            'type'              => 'number',
            'sanitize_callback' => 'floatval',
            'default'           => 0.5,
        )
    );
    register_setting(
        'chatbot_chatgpt_api_chatgpt',
        'chatbot_chatgpt_top_p',
        array(
            'type'              => 'number',
            'sanitize_callback' => 'floatval',
            'default'           => 1.00,
        )
    );

    add_settings_section(
        'chatbot_chatgpt_api_chatgpt_chat_section',
        'Chat Settings',
        'chatbot_chatgpt_api_chatgpt_chat_section_callback',
        'chatbot_chatgpt_api_chatgpt_chat'
    );

    add_settings_field(
        'chatbot_chatgpt_model_choice',
        'ChatGPT Model Default',
        'chatbot_chatgpt_model_choice_callback',
        'chatbot_chatgpt_api_chatgpt_chat',
        'chatbot_chatgpt_api_chatgpt_chat_section'
    );

    // Setting to adjust in small increments the number of Max Tokens - Ver 1.4.2
    add_settings_field(
        'chatbot_chatgpt_max_tokens_setting',
        'Maximum Tokens Setting',
        'chatgpt_max_tokens_setting_callback',
        'chatbot_chatgpt_api_chatgpt_chat',
        'chatbot_chatgpt_api_chatgpt_chat_section'
    );

    // Setting to adjust the conversation context - Ver 1.4.2
    add_settings_field(
        'chatbot_chatgpt_conversation_context',
        'Conversation Context',
        'chatbot_chatgpt_conversation_context_callback',
        'chatbot_chatgpt_api_chatgpt_chat',
        'chatbot_chatgpt_api_chatgpt_chat_section'
    );

    // Temperature - Ver 2.0.1
    add_settings_field(
        'chatbot_chatgpt_temperature',
        'Temperature',
        'chatbot_chatgpt_temperature_callback',
        'chatbot_chatgpt_api_chatgpt_chat',
        'chatbot_chatgpt_api_chatgpt_chat_section'
    );

    // Top P - Ver 2.0.1
    add_settings_field(
        'chatbot_chatgpt_top_p',
        'Top P',
        'chatbot_chatgpt_top_p_callback',
        'chatbot_chatgpt_api_chatgpt_chat',
        'chatbot_chatgpt_api_chatgpt_chat_section'
    );

    // Voice Options - Ver 1.9.5
    register_setting(
        'chatbot_chatgpt_api_chatgpt',
        'chatbot_chatgpt_voice_model_option',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        )
    );
    register_setting(
        'chatbot_chatgpt_api_chatgpt',
        'chatbot_chatgpt_voice_option',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        )
    );
    register_setting(
        'chatbot_chatgpt_api_chatgpt',
        'chatbot_chatgpt_audio_output_format',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        )
    );
    register_setting(
        'chatbot_chatgpt_api_chatgpt',
        'chatbot_chatgpt_read_aloud_option',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        )
    );
    
    // Voice Options - Ver 1.9.5
    add_settings_section(
        'chatbot_chatgpt_api_chatgpt_voice_section',
        'Voice Settings (Text to Speech)',
        'chatbot_chatgpt_api_chatgpt_voice_section_callback',
        'chatbot_chatgpt_api_chatgpt_voice'
    );

    // Voice Option - Ver 1.9.5
    add_settings_field(
        'chatbot_chatgpt_voice_model_option',
        'Voice Model Default',
        'chatbot_chatgpt_voice_model_option_callback',
        'chatbot_chatgpt_api_chatgpt_voice',
        'chatbot_chatgpt_api_chatgpt_voice_section'
    );

    // Voice Option
    add_settings_field(
        'chatbot_chatgpt_voice_option',
        'Voice',
        'chatbot_chatgpt_voice_option_callback',
        'chatbot_chatgpt_api_chatgpt_voice',
        'chatbot_chatgpt_api_chatgpt_voice_section'
    );

    // Audio Output Options
    add_settings_field(
        'chatbot_chatgpt_audio_output_format',
        'Audio Output Option',
        'chatbot_chatgpt_audio_output_format_callback',
        'chatbot_chatgpt_api_chatgpt_voice',
        'chatbot_chatgpt_api_chatgpt_voice_section'
    );

    // Allow Read Aloud - Ver 2.0.0
    add_settings_field(
        'chatbot_chatgpt_read_aloud_option',
        'Allow Read Aloud',
        'chatbot_chatgpt_read_aloud_option_callback',
        'chatbot_chatgpt_api_chatgpt_voice',
        'chatbot_chatgpt_api_chatgpt_voice_section'
    );

    // Image Options - Ver 1.9.5
    register_setting(
        'chatbot_chatgpt_api_chatgpt',
        'chatbot_chatgpt_image_model_option',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        )
    );
    register_setting(
        'chatbot_chatgpt_api_chatgpt',
        'chatbot_chatgpt_image_output_format',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        )
    );
    register_setting(
        'chatbot_chatgpt_api_chatgpt',
        'chatbot_chatgpt_image_output_size',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        )
    );
    register_setting(
        'chatbot_chatgpt_api_chatgpt',
        'chatbot_chatgpt_image_output_quantity',
        array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 1,
        )
    );
    register_setting(
        'chatbot_chatgpt_api_chatgpt',
        'chatbot_chatgpt_image_output_quality',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        )
    );
    register_setting(
        'chatbot_chatgpt_api_chatgpt',
        'chatbot_chatgpt_image_style_output',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        )
    );

    // Image Options - Ver 1.9.5
    add_settings_section(
        'chatbot_chatgpt_api_chatgpt_image_section',
        'Image Settings',
        'chatbot_chatgpt_api_chatgpt_image_section_callback',
        'chatbot_chatgpt_api_chatgpt_image'
    );

    add_settings_field(
        'chatbot_chatgpt_image_model_option',
        'Image Model Default',
        'chatbot_chatgpt_image_model_option_callback',
        'chatbot_chatgpt_api_chatgpt_image',
        'chatbot_chatgpt_api_chatgpt_image_section'
    );

    add_settings_field(
        'chatbot_chatgpt_image_output_format',
        'Image Output Option',
        'chatbot_chatgpt_image_output_format_callback',
        'chatbot_chatgpt_api_chatgpt_image',
        'chatbot_chatgpt_api_chatgpt_image_section'
    );

    add_settings_field(
        'chatbot_chatgpt_image_output_size',
        'Image Output Size',
        'chatbot_chatgpt_image_output_size_callback',
        'chatbot_chatgpt_api_chatgpt_image',
        'chatbot_chatgpt_api_chatgpt_image_section'
    );

    add_settings_field(
        'chatbot_chatgpt_image_output_quantity',
        'Image Quantity',
        'chatbot_chatgpt_image_output_quantity_callback',
        'chatbot_chatgpt_api_chatgpt_image',
        'chatbot_chatgpt_api_chatgpt_image_section'
    );

    add_settings_field(
        'chatbot_chatgpt_image_output_quality',
        'Image Quality',
        'chatbot_chatgpt_image_output_quality_callback',
        'chatbot_chatgpt_api_chatgpt_image',
        'chatbot_chatgpt_api_chatgpt_image_section'
    );

    add_settings_field(
        'chatbot_chatgpt_image_style_output',
        'Image Style Output',
        'chatbot_chatgpt_image_style_output_callback',
        'chatbot_chatgpt_api_chatgpt_image',
        'chatbot_chatgpt_api_chatgpt_image_section'
    );

    // Whisper Options - Ver 2.0.1
    register_setting(
        'chatbot_chatgpt_api_chatgpt',
        'chatbot_chatgpt_whisper_model_option',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        )
    );
    register_setting(
        'chatbot_chatgpt_api_chatgpt',
        'chatbot_chatgpt_whisper_response_format',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'text',
        )
    );

    // Image Options - Ver 1.9.5
    add_settings_section(
        'chatbot_chatgpt_api_chatgpt_whisper_section',
        'Whisper Settings (Speech to Text)',
        'chatbot_chatgpt_api_chatgpt_whisper_section_callback',
        'chatbot_chatgpt_api_chatgpt_whisper'
    );

    add_settings_field(
        'chatbot_chatgpt_whisper_model_option',
        'Whisper Model Default',
        'chatbot_chatgpt_whisper_model_option_callback',
        'chatbot_chatgpt_api_chatgpt_whisper',
        'chatbot_chatgpt_api_chatgpt_whisper_section'
    );

    add_settings_field(
        'chatbot_chatgpt_whisper_response_format',
        'Whisper Output Option',
        'chatbot_chatgpt_whisper_response_format_callback',
        'chatbot_chatgpt_api_chatgpt_whisper',
        'chatbot_chatgpt_api_chatgpt_whisper_section'
    );

}
add_action('admin_init', 'chatbot_chatgpt_api_settings_init');
