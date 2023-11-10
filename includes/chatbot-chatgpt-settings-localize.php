<?php
/**
 * Chatbot ChatGPT for WordPress - Localize
 *
 * This file contains the code for the Chatbot ChatGPT settings page.
 * It allows users to configure the API key and other parameters
 * required to access the ChatGPT API from their own account.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) )
die;

function chatbot_chatgpt_localize(){

    $defaults = array(
        'chatgpt_bot_name' => 'Chatbot ChatGPT',
        'chatgpt_chatbot_bot_prompt' => 'Enter your message ...',
        'chatgpt_initial_greeting' => 'Hello! How can I help you today?',
        'chatgpt_subsequent_greeting' => 'Hello again! How can I help you?',
        'chatgptStartStatus' => 'closed',
        'chatgptStartStatusNewVisitor' => 'closed',
        'chatgpt_disclaimer_setting' => 'No',
        'chatgpt_max_tokens_setting' => '150',
        'chatgpt_width_setting' => 'Narrow',
        'chatbot_chatgpt_diagnostics' => 'Off',
        'chatgpt_avatar_icon_setting' => 'icon-001.png',
        'chatgpt_avatar_icon_url_setting' => '',
        'chatgpt_custom_avatar_icon_setting' => 'icon-001.png',
        'chatgpt_avatar_greeting_setting' => 'Howdy!!! Great to see you today! How can I help you?',
        'chatgpt_model_choice' => 'gpt-3.5-turbo',
        'chatgpt_max_tokens_setting' => 150,
        'chatbot_chatgpt_conversation_context' => 'You are a versatile, friendly, and helpful assistant designed to support me in a variety of tasks.',
        'chatbot_chatgpt_enable_custom_buttons' => 'Off',
        'chatbot_chatgpt_custom_button_name_1' => '',
        'chatbot_chatgpt_custom_button_url_1' => '',
        'chatbot_chatgpt_custom_button_name_2' => '',
        'chatbot_chatgpt_custom_button_url_2' => ''
    );

    // Revised for Ver 1.5.0 
    $option_keys = array(
        'chatgpt_bot_name',
        'chatgpt_chatbot_bot_prompt',
        'chatgpt_initial_greeting',
        'chatgpt_subsequent_greeting',
        'chatgptStartStatus',
        'chatgptStartStatusNewVisitor',
        'chatgpt_disclaimer_setting',
        'chatgpt_max_tokens_setting',
        'chatgpt_width_setting',
        'chatbot_chatgpt_diagnostics',
        'chatgpt_avatar_icon_setting',
        'chatgpt_avatar_icon_url_setting',
        'chatgpt_custom_avatar_icon_setting',
        'chatgpt_avatar_greeting_setting',
        'chatbot_chatgpt_enable_custom_buttons',
        'chatbot_chatgpt_custom_button_name_1',
        'chatbot_chatgpt_custom_button_url_1',
        'chatbot_chatgpt_custom_button_name_2',
        'chatbot_chatgpt_custom_button_url_2'
    );

    $chatbot_settings = array();
    foreach ($option_keys as $key) {
        $default_value = isset($defaults[$key]) ? $defaults[$key] : '';
        $chatbot_settings[$key] = esc_attr(get_option($key, $default_value));
        // DIAG - Log key and value
        // error_log('chatbot-chatgpt-settings-localize Key: ' . $key . ', Value: ' . $chatbot_settings[$key]);
    }

    // Update localStorage - Ver 1.6.1
    echo "<script type=\"text/javascript\">
    document.addEventListener('DOMContentLoaded', (event) => {
        // Encode the chatbot settings array into JSON format for use in JavaScript
        let chatbotSettings = " . json_encode($chatbot_settings) . ";

        Object.keys(chatbotSettings).forEach((key) => {
            if(!localStorage.getItem(key)) {
                // DIAG - Log key and value
                // console.log('Setting ' + key + ' in localStorage');
                localStorage.setItem(key, chatbotSettings[key]);
            } else {
                // DIAG - Log key and value
                // console.log(key + ' is already set in localStorage');
            }
        });
    });
    </script>";

}
