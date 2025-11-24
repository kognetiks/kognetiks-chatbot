<?php
/**
 * Kognetiks Chatbot - Settings - API/Google Page
 *
 * This file contains the code for the Google settings page.
 * It allows users to configure the API key and other parameters
 * required to access the Google API from their own account.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// API/Google settings section callback - Ver 2.3.9
function chatbot_google_model_settings_section_callback($args) {

    ?>
    <p>Configure the default settings for the Chatbot plugin to use Google for chat generation.  Start by adding your API key then selecting your choices below.</p>
    <p>More information about Google models and their capability can be found at <a href="https://aistudio.google.com/api-keys" target="_blank">https://aistudio.google.com/api-keys</a>.</p>
    <p><b><i>Don't forget to click </i><code>Save Settings</code><i> to save any changes your might make.</i></b></p>
    <p style="background-color: #e0f7fa; padding: 10px;"><b>For an explanation of the API/Google settings and additional documentation please click <a href="?page=chatbot-chatgpt&tab=support&dir=api-google-settings&file=api-google-model-settings.md">here</a>.</b></p>
    <?php
}

function chatbot_google_api_model_general_section_callback($args) {
    ?>
    <p>Configure the settings for the plugin by adding your API key. This plugin requires an API key from Google to function. You can obtain an API key by signing up at <a href="https://console.cloud.google.com/" target="_blank">https://console.cloud.google.com/</a>.</p>
    <?php
}

// API key field callback
function chatbot_google_api_key_callback($args) {
    $api_key = esc_attr(get_option('chatbot_google_api_key'));
    // Decrypt the API key - Ver 2.2.6
    $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
    ?>
    <input type="password" id="chatbot_google_api_key" name="chatbot_google_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text"  autocomplete="off">
    <?php
}

function chatbot_google_api_model_chat_settings_section_callback($args) {
    ?>
    <p>Configure the settings for the plugin when using chat models. Depending on the Google model you choose, the maximum tokens may be as high as 4097. The default is 150. For more information about the maximum tokens parameter, please see <a href="https://jan.ai/docs" target="_blank">https://jan.ai/docs</a>. Enter a conversation context to help the model understand the conversation. See the default for ideas. Some example shortcodes include:</p>
    <?php
}

function chatbot_google_chat_model_choice_callback($args) {
    ?>
    <p>Select the Google model you want to use for chat generation.</p>
    <?php
}

function chatbot_google_max_tokens_setting_callback($args) {
    ?>
    <p>Configure the maximum number of tokens for the Google model. The default is 150.</p>
    <?php
}

function chatbot_google_conversation_context_callback($args) {
    ?>
    <p>Enter a conversation context to help the model understand the conversation. See the default for ideas. Some example shortcodes include:</p>
    <?php
}

function chatbot_google_temperature_callback($args) {
    ?>
    <p>Configure the temperature for the Google model. The default is 0.7.</p>
    <?php
}

function chatbot_google_top_p_callback($args) {
    ?>
    <p>Configure the top p for the Google model. The default is 1.</p>
    <?php
}

function chatbot_google_api_model_advanced_section_callback($args) {
    ?>
    <p>Configure the advanced settings for the Google model.</p>
    <?php
}

function chatbot_google_base_url_callback($args) {
    $chatbot_google_base_url = esc_attr(get_option('chatbot_google_base_url', 'https://generativelanguage.googleapis.com/v1beta/models/'));
    ?>
    <input type="text" id="chatbot_google_base_url" name="chatbot_google_base_url" value="<?php echo esc_attr( $chatbot_google_base_url ); ?>" class="regular-text">
    <?php
}

function chatbot_google_timeout_setting_callback($args) {
    ?>
    <p>Configure the timeout for the Google model. The default is 240 seconds.</p>
    <?php
}


