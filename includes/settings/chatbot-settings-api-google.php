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
    <p>Configure the default settings for the Chatbot plugin to use Google for chat generation.  Start by adding your API key then selecting your choices below.  Don't forget to click "Save Settings" at the very bottom of this page.</p>
    <p>More information about Google models and their capability can be found at <a href="https://aistudio.google.com/api-keys" target="_blank">https://aistudio.google.com/api-keys</a>.</p>
    <p><b><i>Don't forget to click </i><code>Save Settings</code><i> to save any changes your might make.</i></b></p>
    <p style="background-color: #e0f7fa; padding: 10px;"><b>For an explanation of the API/Google Settings and additional documentation please click <a href="?page=chatbot-chatgpt&tab=support&dir=api-google-settings&file=api-google-model-settings.md">here</a>.</b></p>
    <?php

}

function chatbot_google_api_model_general_section_callback($args) {

    ?>
    <p>Configure the settings for the plugin by adding your API key. This plugin requires an API key from Google to function. You can obtain an API key by signing up at <a href="https://aistudio.google.com/api-keys" target="_blank">https://aistudio.google.com/api-keys</a>.</p>
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
    <p>Configure the settings for the plugin when using chat models. Depending on the Google model you choose, the maximum tokens may be as high as 4097. The default is 500. For more information about the maximum tokens parameter, please see <a href="https://ai.google.dev/docs" target="_blank">https://ai.google.dev/docs</a>. Enter a conversation context to help the model understand the conversation. See the default for ideas. Some example shortcodes include:</p>
    <ul style="list-style-type: disc; list-style-position: inside; padding-left: 1em;">
        <li><code>&#91;chatbot&#93;</code> - Default chat model, style is floating</li>
        <li><code>&#91;chatbot style="floating" model="gemini-2.0-flash"&#93;</code> - Style is floating, specific model</li>
        <li><code>&#91;chatbot style="embedded" model="gemini-2.0-flash"&#93;</code> - Style is embedded, default chat model</li>
    </ul>
    <?php

}

// Google Model choice - Ver 2.3.9
function chatbot_google_chat_model_choice_callback($args) {
  
    // Get the saved chatbot_google_model_choice value or default to "gemini-2.0-flash"
    $model_choice = esc_attr(get_option('chatbot_google_model_choice', 'gemini-2.0-flash'));

    // Fetch models from the API
    $models = chatbot_google_get_models();

    // DIAG - Ver 2.3.9
    // back_trace( 'NOTICE', '$models: ' . print_r($models, true) );

    // Limit the models to Google/Gemini models
    $models = array_filter($models, function($model) {
        return strpos($model['id'], 'gemini') !== false;
    });

    // Check for errors
    if (is_string($models) && strpos($models, 'Error:') === 0) {
        // If there's an error, display the hardcoded list
        $model_choice = esc_attr(get_option('chatbot_google_model_choice', 'gemini-2.0-flash'));
        ?>
        <select id="chatbot_google_model_choice" name="chatbot_google_model_choice">
            <option value="<?php echo esc_attr( 'gemini-2.0-flash' ); ?>" <?php selected( $model_choice, 'gemini-2.0-flash' ); ?>><?php echo esc_html( 'gemini-2.0-flash' ); ?></option>
            <option value="<?php echo esc_attr( 'gemini-2.0-flash-vision' ); ?>" <?php selected( $model_choice, 'gemini-2.0-flash-vision' ); ?>><?php echo esc_html( 'gemini-2.0-flash-vision' ); ?></option>
            <option value="<?php echo esc_attr( 'gemini-1.5-pro' ); ?>" <?php selected( $model_choice, 'gemini-1.5-pro' ); ?>><?php echo esc_html( 'gemini-1.5-pro' ); ?></option>
            <option value="<?php echo esc_attr( 'gemini-1.5-flash' ); ?>" <?php selected( $model_choice, 'gemini-1.5-flash' ); ?>><?php echo esc_html( 'gemini-1.5-flash' ); ?></option>
            <option value="<?php echo esc_attr( 'gemini-pro' ); ?>" <?php selected( $model_choice, 'gemini-pro' ); ?>><?php echo esc_html( 'gemini-pro' ); ?></option>
            <option value="<?php echo esc_attr( 'gemini-pro-vision' ); ?>" <?php selected( $model_choice, 'gemini-pro-vision' ); ?>><?php echo esc_html( 'gemini-pro-vision' ); ?></option>
        </select>
        <?php
    } else {
        // If models are fetched successfully, display them dynamically
        ?>
        <select id="chatbot_google_model_choice" name="chatbot_google_model_choice">
            <?php foreach ($models as $model): ?>
                <option value="<?php echo esc_attr($model['id']); ?>" <?php selected(esc_attr(get_option('chatbot_google_model_choice')), $model['id']); ?>><?php echo esc_html($model['id']); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }

}

// Max Tokens choice - Ver 2.3.9
function chatbot_google_max_tokens_setting_callback($args) {

    // Get the saved chatbot_google_max_tokens_setting or default to 500
    $max_tokens = esc_attr(get_option('chatbot_google_max_tokens_setting', '500'));
    // Allow for a range of tokens between 100 and 10000 in 100-step increments - Ver 2.0.4
    ?>
    <select id="chatbot_google_max_tokens_setting" name="chatbot_google_max_tokens_setting">
        <?php
        for ($i=100; $i<=10000; $i+=100) {
            echo '<option value="' . esc_attr($i) . '" ' . selected($max_tokens, (string)$i, false) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php

}

// Conversation Context - Ver 2.3.9
function chatbot_google_conversation_context_callback($args) {

    // Get the value of the setting we've registered with register_setting()
    $chatbot_google_conversation_context = esc_attr(get_option('chatbot_google_conversation_context'));

    // Check if the option has been set, if not, use a default value
    if (empty($chatbot_google_conversation_context)) {
        $chatbot_google_conversation_context = "You are a versatile, friendly, and helpful assistant designed to support me in a variety of tasks that responds in Markdown.";
        // Save the default value into the option
        update_option('chatbot_google_conversation_context', $chatbot_google_conversation_context);
    }

    ?>
    <!-- Define the textarea field. -->
    <textarea id='chatbot_google_conversation_context' name='chatbot_google_conversation_context' rows='5' cols='50' maxlength='12500'><?php echo esc_html(stripslashes($chatbot_google_conversation_context)); ?></textarea>
    <?php

}

// Set chatbot_google_temperature - Ver 2.3.9
// https://ai.google.dev/docs
function chatbot_google_temperature_callback($args) {

    $temperature = esc_attr(get_option('chatbot_google_temperature', 0.50));
    ?>
    <select id="chatbot_google_temperature" name="chatbot_google_temperature">
        <?php
        for ($i = 0.01; $i <= 2.01; $i += 0.01) {
            echo '<option value="' . $i . '" ' . selected($temperature, (string)$i) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php

}

// Set chatbot_google_top_p - Ver 2.3.9
// https://ai.google.dev/docs
function chatbot_google_top_p_callback($args) {

    $top_p = esc_attr(get_option('chatbot_google_top_p', 1.00));
    ?>
    <select id="chatbot_google_top_p" name="chatbot_google_top_p">
        <?php
        for ($i = 0.01; $i <= 1.01; $i += 0.01) {
            echo '<option value="' . $i . '" ' . selected($top_p, (string)$i) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php

}

function chatbot_google_api_model_advanced_section_callback($args) {
    ?>
    <p><strong>CAUTION</strong>: Configure the advanced settings for the plugin. Enter the base URL for the Google API.  The default is <code>https://generativelanguage.googleapis.com/v1beta/models/</code>.</p>
    <?php
}

// Base URL for the Google API - Ver 2.3.9
function chatbot_google_base_url_callback($args) {

    $chatbot_google_base_url = esc_attr(get_option('chatbot_google_base_url', 'https://generativelanguage.googleapis.com/v1beta/models/'));
    ?>
    <input type="text" id="chatbot_google_base_url" name="chatbot_google_base_url" value="<?php echo esc_attr( $chatbot_google_base_url ); ?>" class="regular-text">
    <?php

}

// Timeout Settings Callback - Ver 2.3.9
function chatbot_google_timeout_setting_callback($args) {

    // Get the saved chatbot_google_timeout value or default to 240
    $timeout = esc_attr(get_option('chatbot_google_timeout_setting', 240));

    // Allow for a range of tokens between 5 and 500 in 5-step increments - Ver 1.8.8
    ?>
    <select id="chatbot_google_timeout_setting" name="chatbot_google_timeout_setting">
        <?php
        for ($i=5; $i<=500; $i+=5) {
            echo '<option value="' . esc_attr($i) . '" ' . selected($timeout, (string)$i, false) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php
    
}

// Register API settings - Ver 2.3.9
function chatbot_google_api_settings_init() {

    add_settings_section(
        'chatbot_google_settings_section',
        'API/Google Settings',
        'chatbot_google_model_settings_section_callback',
        'chatbot_google_model_settings_general'
    );

    // API/Google settings tab - Ver 2.3.9
    register_setting('chatbot_google_api_model', 'chatbot_google_api_enabled');
    register_setting('chatbot_google_api_model', 'chatbot_google_api_key', 'chatbot_chatgpt_sanitize_api_key');
    register_setting('chatbot_google_api_model', 'chatbot_google_max_tokens_setting');
    register_setting('chatbot_google_api_model', 'chatbot_google_conversation_context');
    register_setting('chatbot_google_api_model', 'chatbot_google_temperature');
    register_setting('chatbot_google_api_model', 'chatbot_google_top_p');

    add_settings_section(
        'chatbot_google_api_model_general_section',
        'Google API Settings',
        'chatbot_google_api_model_general_section_callback',
        'chatbot_google_api_model_general'
    );

    add_settings_field(
        'chatbot_google_api_key',
        'Google API Key',
        'chatbot_google_api_key_callback',
        'chatbot_google_api_model_general',
        'chatbot_google_api_model_general_section'
    );

    register_setting(
        'chatbot_google_api_model',
        'chatbot_google_model_choice',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        )
    );

    register_setting(
        'chatbot_google_api_model',
        'chatbot_google_max_tokens_setting',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        )
    );

    add_settings_section(
        'chatbot_google_api_model_chat_settings_section',
        'Chat Settings',
        'chatbot_google_api_model_chat_settings_section_callback',
        'chatbot_google_api_model_chat_settings'
    );

    add_settings_field(
        'chatbot_google_model_choice',
        'Google Model Choice',
        'chatbot_google_chat_model_choice_callback',
        'chatbot_google_api_model_chat_settings',
        'chatbot_google_api_model_chat_settings_section'
    );

    // Setting to adjust in small increments the number of Max Tokens - Ver 2.3.9
    add_settings_field(
        'chatbot_google_max_tokens_setting',
        'Maximum Tokens Setting',
        'chatbot_google_max_tokens_setting_callback',
        'chatbot_google_api_model_chat_settings',
        'chatbot_google_api_model_chat_settings_section'
    );

    // Setting to adjust the conversation context - Ver 2.3.9
    add_settings_field(
        'chatbot_google_conversation_context',
        'Conversation Context',
        'chatbot_google_conversation_context_callback',
        'chatbot_google_api_model_chat_settings',
        'chatbot_google_api_model_chat_settings_section'
    );

    // Temperature - Ver 2.3.9
    add_settings_field(
        'chatbot_google_temperature',
        'Temperature',
        'chatbot_google_temperature_callback',
        'chatbot_google_api_model_chat_settings',
        'chatbot_google_api_model_chat_settings_section'
    );

    // Top P - Ver 2.3.9
    add_settings_field(
        'chatbot_google_top_p',
        'Top P',
        'chatbot_google_top_p_callback',
        'chatbot_google_api_model_chat_settings',
        'chatbot_google_api_model_chat_settings_section'
    );

    // Advanced Model Settings - Ver 2.3.9
    register_setting('chatbot_google_api_model', 'chatbot_google_base_url');
    register_setting('chatbot_google_api_model', 'chatbot_google_timeout_setting');

    add_settings_section(
        'chatbot_google_api_model_advanced_section',
        'Advanced API Settings',
        'chatbot_google_api_model_advanced_section_callback',
        'chatbot_google_api_model_advanced'
    );

    // Set the base URL for the API - Ver 2.3.9
    add_settings_field(
        'chatbot_google_base_url',
        'Base URL for API',
        'chatbot_google_base_url_callback',
        'chatbot_google_api_model_advanced',
        'chatbot_google_api_model_advanced_section'
    );

    // Timeout setting - Ver 2.3.9
    add_settings_field(
        'chatbot_google_timeout_setting',
        'Timeout Setting (in seconds)',
        'chatbot_google_timeout_setting_callback',
        'chatbot_google_api_model_advanced',
        'chatbot_google_api_model_advanced_section'
    );

}
add_action('admin_init', 'chatbot_google_api_settings_init');

