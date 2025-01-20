<?php
/**
 * Kognetiks Chatbot - Localize
 *
 * This file contains the code for the Chatbot settings page.
 * It localizes the settings and other parameters.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

function chatbot_chatgpt_localize(){

    $defaults = array(
        'chatbot_chatgpt_allow_file_uploads' => 'No',
        'chatbot_chatgpt_audience_choice' => 'all',
        'chatbot_chatgpt_avatar_greeting_setting' => 'Howdy!!! Great to see you today! How can I help you?',
        'chatbot_chatgpt_avatar_icon_setting' => 'icon-001.png',
        'chatbot_chatgpt_avatar_icon_url_setting' => '',
        'chatbot_chatgpt_bot_name' => 'Kognetiks Chatbot',
        'chatbot_chatgpt_bot_prompt' => 'Enter your question ...',
        'chatbot_chatgpt_conversation_context' => 'You are a versatile, friendly, and helpful assistant designed to support me in a variety of tasks that responds in Markdown.',
        'chatbot_chatgpt_custom_avatar_icon_setting' => '',
        'chatbot_chatgpt_custom_button_name_1' => '',
        'chatbot_chatgpt_custom_button_name_2' => '',
        'chatbot_chatgpt_custom_button_name_3' => '',
        'chatbot_chatgpt_custom_button_name_4' => '',
        'chatbot_chatgpt_custom_button_url_1' => '',
        'chatbot_chatgpt_custom_button_url_2' => '',
        'chatbot_chatgpt_custom_button_url_3' => '',
        'chatbot_chatgpt_custom_button_url_4' => '',
        'chatbot_chatgpt_disclaimer_setting' => 'No',
        'chatbot_chatgpt_display_style' => 'floating',
        'chatbot_chatgpt_enable_custom_buttons' => 'Off',
        'chatbot_chatgpt_initial_greeting' => 'Hello! How can I help you today?',
        'chatbot_chatgpt_model_choice' => 'gpt-3.5-turbo',
        'chatbot_chatgpt_start_status' => 'closed',
        'chatbot_chatgpt_start_status_new_visitor' => 'closed',
        'chatbot_chatgpt_subsequent_greeting' => 'Hello again! How can I help you?',
        'chatbot_chatgpt_width_setting' => 'Narrow',
        'chatbot_chatgpt_force_page_reload' => 'No',
        'chatbot_chatgpt_conversation_continuation' => 'Off',
        'chatbot_chatgpt_diagnostics' => 'Off',
        'chatbot_chatgpt_appearance_open_icon' => '',
        'chatbot_chatgpt_appearance_collapse_icon' => '',
        'chatbot_chatgpt_appearance_erase_icon' => '',
        'chatbot_chatgpt_appearance_mic_enabled_icon' => '',
        'chatbot_chatgpt_appearance_mic_disabled_icon' => '',
    );

    // Revised for Ver 1.5.0 
    $option_keys = array(
        'chatbot_chatgpt_allow_file_uploads',
        'chatbot_chatgpt_audience_choice',
        'chatbot_chatgpt_avatar_greeting_setting',
        'chatbot_chatgpt_avatar_icon_setting',
        'chatbot_chatgpt_avatar_icon_url_setting',
        'chatbot_chatgpt_bot_name',
        'chatbot_chatgpt_bot_prompt',
        'chatbot_chatgpt_conversation_context',
        'chatbot_chatgpt_custom_avatar_icon_setting',
        'chatbot_chatgpt_custom_button_name_1',
        'chatbot_chatgpt_custom_button_name_2',
        'chatbot_chatgpt_custom_button_name_3',
        'chatbot_chatgpt_custom_button_name_4',
        'chatbot_chatgpt_custom_button_url_1',
        'chatbot_chatgpt_custom_button_url_2',
        'chatbot_chatgpt_custom_button_url_3',
        'chatbot_chatgpt_custom_button_url_4',
        'chatbot_chatgpt_disclaimer_setting',
        'chatbot_chatgpt_display_style',
        'chatbot_chatgpt_enable_custom_buttons',
        'chatbot_chatgpt_initial_greeting',
        'chatbot_chatgpt_model_choice',
        'chatbot_chatgpt_start_status',
        'chatbot_chatgpt_start_status_new_visitor',
        'chatbot_chatgpt_subsequent_greeting',
        'chatbot_chatgpt_width_setting',
        'chatbot_chatgpt_force_page_reload',
        'chatbot_chatgpt_conversation_continuation',
        'chatbot_chatgpt_diagnostics',
        'chatbot_chatgpt_appearance_open_icon',
        'chatbot_chatgpt_appearance_collapse_icon',
        'chatbot_chatgpt_appearance_erase_icon',
        'chatbot_chatgpt_appearance_mic_enabled_icon',
        'chatbot_chatgpt_appearance_mic_disabled_icon',
    );

    $kchat_settings = [];
    foreach ($option_keys as $key) {
        $default_value = $defaults[$key] ?? '';
        $kchat_settings[$key] = esc_attr(get_option($key, $default_value));
        // DIAG - Diagnostics - Ver 1.6.1
        back_trace( 'NOTICE', 'Key: ' . $key . ', Value: ' . $kchat_settings[$key]);
    }


}
