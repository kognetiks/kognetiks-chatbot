<?php
/**
 * Kognetiks Chatbot for WordPress - Localize
 *
 * This file contains the code for the Chatbot settings page.
 * It localizes the settings and other parameters.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
};

function chatbot_chatgpt_localize(){

    $defaults = array(
        'chatbot_chatgpt_allow_file_uploads' => 'No',
        'chatbot_chatgpt_audience_choice' => 'all',
        'chatbot_chatgpt_avatar_greeting_setting' => 'Howdy!!! Great to see you today! How can I help you?',
        'chatbot_chatgpt_avatar_icon_setting' => 'icon-001.png',
        'chatbot_chatgpt_avatar_icon_url_setting' => '',
        'chatbot_chatgpt_bot_name' => 'Kognetiks Chatbot',
        'chatbot_chatgpt_bot_prompt' => 'Enter your question ...',
        'chatbot_chatgpt_conversation_context' => 'You are a versatile, friendly, and helpful assistant designed to support me in a variety of tasks.',
        'chatbot_chatgpt_custom_avatar_icon_setting' => '',
        'chatbot_chatgpt_custom_button_name_1' => '',
        'chatbot_chatgpt_custom_button_name_2' => '',
        'chatbot_chatgpt_custom_button_url_1' => '',
        'chatbot_chatgpt_custom_button_url_2' => '',
        'chatbot_chatgpt_disclaimer_setting' => 'No',
        'chatbot_chatgpt_enable_custom_buttons' => 'Off',
        'chatbot_chatgpt_initial_greeting' => 'Hello! How can I help you today?',
        'chatbot_chatgpt_model_choice' => 'gpt-3.5-turbo',
        'chatbot_chatgpt_start_status' => 'closed',
        'chatbot_chatgpt_start_status_new_visitor' => 'closed',
        'chatbot_chatgpt_subsequent_greeting' => 'Hello again! How can I help you?',
        'chatbot_chatgpt_width_setting' => 'Narrow',
        'chatbot_chatgpt_diagnostics' => 'Off'
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
        'chatbot_chatgpt_custom_button_url_1',
        'chatbot_chatgpt_custom_button_url_2',
        'chatbot_chatgpt_disclaimer_setting',
        'chatbot_chatgpt_enable_custom_buttons',
        'chatbot_chatgpt_initial_greeting',
        'chatbot_chatgpt_model_choice',
        'chatbot_chatgpt_start_status',
        'chatbot_chatgpt_start_status_new_visitor',
        'chatbot_chatgpt_subsequent_greeting',
        'chatbot_chatgpt_width_setting',
        'chatbot_chatgpt_diagnostics'
    );

    $chatbot_settings = array();
    foreach ($option_keys as $key) {
        $default_value = $defaults[$key] ?? '';
        $chatbot_settings[$key] = esc_attr(get_option($key, $default_value));
        // DIAG - Log key and value
        // back_trace( 'NOTICE', 'Key: ' . $key . ', Value: ' . $chatbot_settings[$key]);
    }

    // FIXME - WAS ADDED IN 1.6.1
    // Update localStorage - Ver 1.6.1
    echo "<script type=\"text/javascript\">
    //DIAG - Diagnostics
    // console.log('chatbot_chatgpt_settings_localize.php - start');
    document.addEventListener('DOMContentLoaded', (event) => {
        // Encode the chatbot settings array into JSON format for use in JavaScript
        let chatbotSettings = " . json_encode($chatbot_settings) . ";
        Object.keys(chatbotSettings).forEach((key) => {
            // DIAG - Diagnostics
            // console.log('VIA PHP LOCALIZE Setting ' + key + ' ' + chatbotSettings[key] + ' in localStorage');
            localStorage.setItem(key, chatbotSettings[key]);
        });
        // Moved inside the DOMContentLoaded event listener
        // console.log('chatbot_chatgpt_settings_localize.php - finish');
    });
    </script>";
    

}
