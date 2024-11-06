<?php
/**
 * Kognetiks Chatbot for WordPress - Settings - API/NVIDIA Page
 *
 * This file contains the code for the Chatbot settings page.
 * It allows users to configure the API key and other parameters
 * required to access the NVIDIA API from their own account.
 *
 * @package chatbot-nvidia
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// API/NVIDIA settings section callback - Ver 2.1.8
function chatbot_nvidia_model_settings_section_callback($args) {
    ?>
    <p>Configure the default settings for the Chatbot plugin for chat, voice, and image generation.  Start by adding your API key then selecting your choices below.  Don't forget to click "Save Settings" at the very bottom of this page.</p>
    <p>More information about NVIDIA models and their capability can be found at <a href="https://build.nvidia.com/explore/discover" target="_blank">https://build.nvidia.com/explore/discover</a>.</p>
    <p><b><i>Don't forget to click </i><code>Save Settings</code><i> to save any changes your might make.</i></b></p>
    <p style="background-color: #e0f7fa; padding: 10px;"><b>For an explanation of the API/NVIDIA settings and additional documentation please click <a href="?page=chatbot-nvidia&tab=support&dir=api-nvidia-settings&file=api-nvidia-settings.md">here</a>.</b></p>
    <?php
}

function chatbot_nvidia_api_model_general_section_callback($args) {
    ?>
    <p>Configure the settings for the plugin by adding your API key. This plugin requires an API key from NVIDIA to function. You can obtain an API key by signing up at <a href="https://build.nvidia.com/nim?signin=true" target="_blank">https://build.nvidia.com/nim?signin=true</a>.</p>
    <?php
}

// API key field callback
function chatbot_nvidia_api_key_callback($args) {
    $api_key = get_option('chatbot_nvidia_api_key');
    ?>
    <input type="password" id="chatbot_nvidia_api_key" name="chatbot_nvidia_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text"  autocomplete="off">
    <?php
}

function chatbot_nvidia_api_model_chat_settings_section_callback($args) {
    ?>
    <p>Configure the settings for the plugin when using chat models. Depending on the NVIDIA model you choose, the maximum tokens may be as high as 4097. The default is 150. For more information about the maximum tokens parameter, please see <a href="https://docs.api.nvidia.com/nim/reference/models-1" target="_blank">https://docs.api.nvidia.com/nim/reference/models-1</a>. Enter a conversation context to help the model understand the conversation. See the default for ideas. Some example shortcodes include:</p>
    <ul style="list-style-type: disc; list-style-position: inside; padding-left: 1em;">
        <li><code>&#91;chatbot&#93;</code> - Default chat model, style is floating</li>
        <li><code>&#91;chatbot style="floating" model="nvidia/llama-3.1-nemotron-51b-instruct"&#93;</code> - Style is floating, specific model</li>
        <li><code>&#91;chatbot style="embedded" model="nvidia/llama-3.1-nemotron-51b-instruct"&#93;</code> - Style is embedded, default chat model</li>
    </ul>
    <?php
}

// NVIDIA Model Settings Callback - Ver 2.1.8
function chatbot_nvidia_chat_model_choice_callback($args) {

    $model_choice = esc_attr(get_option('chatbot_nvidia_model_choice', 'nvidia/llama-3.1-nemotron-51b-instruct'));
    $nvidia_api_enabled = esc_attr(get_option('chatbot_nvidia_api_enabled', 'Yes'));

    // Fetch models from the API
    $models = chatbot_nvidia_get_models();

    // Remove the models not owned by NVIDIA
    $models = array_filter($models, function($model) {
        return $model['owned_by'] === 'nvidia';
    });

    // Check for errors
    if (is_string($models) && strpos($models, 'Error:') === 0) {
        // If there's an error, display the hardcoded list
        $model_choice = esc_attr(get_option('chatbot_nvidia_model_choice', 'nvidia/llama-3.1-nemotron-51b-instruct'));
        ?>
        <select id="chatbot_chatgpt_model_choice" name="chatbot_chatgpt_model_choice">
            <option value="<?php echo esc_attr( 'nvidia/llama-3.1-nemotron-51b-instruct' ); ?>" <?php selected( $model_choice, 'nvidia/llama-3.1-nemotron-51b-instruct' ); ?>><?php echo esc_html( 'nvidia/llama-3.1-nemotron-51b-instruct' ); ?></option>
        </select>
        <?php
    } else {
        // If models are fetched successfully, display them dynamically
        ?>
        <select id="chatbot_nvidia_model_choice" name="chatbot_nvidia_model_choice">
            <?php foreach ($models as $model): ?>
                <option value="<?php echo esc_attr($model['id']); ?>" <?php selected(get_option('chatbot_nvidia_model_choice'), $model['id']); ?>><?php echo esc_html($model['id']); ?></option>
            <?php endforeach; ?>
            ?>
        </select>
        <?php
    }

}

// Max Tokens choice - Ver 2.1.8
function chatgpt_nvidia_max_tokens_setting_callback($args) {
    // Get the saved chatbot_nvidia_max_tokens_setting or default to 500
    $max_tokens = esc_attr(get_option('chatbot_nvidia_max_tokens_setting', '500'));
    // Allow for a range of tokens between 100 and 4096 in 100-step increments - Ver 2.0.4
    ?>
    <select id="chatbot_nvidia_max_tokens_setting" name="chatbot_nvidia_max_tokens_setting">
        <?php
        for ($i=100; $i<=4000; $i+=100) {
            echo '<option value="' . esc_attr($i) . '" ' . selected($max_tokens, (string)$i, false) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php
}

// Conversation Context
function chatbot_nvidia_conversation_context_callback($args) {
    // Get the value of the setting we've registered with register_setting()
    $chatbot_nvidia_conversation_context = esc_attr(get_option('chatbot_nvidia_conversation_context'));

    // Check if the option has been set, if not, use a default value
    if (empty($chatbot_nvidia_conversation_context)) {
        $chatbot_nvidia_conversation_context = "You are a versatile, friendly, and helpful assistant designed to support me in a variety of tasks that responds in Markdown.";
        // Save the default value into the option
        update_option('chatbot_nvidia_conversation_context', $chatbot_nvidia_conversation_context);
    }

    ?>
    <!-- Define the textarea field. -->
    <textarea id='chatbot_nvidia_conversation_context' name='chatbot_nvidia_conversation_context' rows='5' cols='50' maxlength='12500'><?php echo esc_html(stripslashes($chatbot_nvidia_conversation_context)); ?></textarea>
    <?php
}

// Set chatbot_nvidia_temperature
function chatbot_nvidia_temperature_callback($args) {
    $temperature = esc_attr(get_option('chatbot_nvidia_temperature', 0.5));
    ?>
    <select id="chatbot_nvidia_temperature" name="chatbot_nvidia_temperature">
        <?php
        for ($i = 0.01; $i <= 2.01; $i += 0.01) {
            echo '<option value="' . $i . '" ' . selected($temperature, (string)$i) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php
}

// Set chatbot_nvidia_top_p
function chatbot_nvidia_top_p_callback($args) {
    $top_p = esc_attr(get_option('chatbot_nvidia_top_p', 1.00));
    ?>
    <select id="chatbot_nvidia_top_p" name="chatbot_nvidia_top_p">
        <?php
        for ($i = 0.01; $i <= 1.01; $i += 0.01) {
            echo '<option value="' . $i . '" ' . selected($top_p, (string)$i) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php
}

// API Advanced settings section callback
function chatbot_nvidia_api_model_advanced_section_callback($args) {
    ?>
    <p>CAUTION: Configure the advanced settings for the plugin. Enter the base URL for the NVIDIA API.  The default is <code>https://integrate.api.nvidia.com/v1</code>.</p>
    <?php
}

// Base URL for the NVIDIA API
function chatbot_nvidia_base_url_callback($args) {
    $chatbot_nvidia_base_url = esc_attr(get_option('chatbot_nvidia_base_url', 'https://integrate.api.nvidia.com/v1'));
    ?>
    <input type="text" id="chatbot_nvidia_base_url" name="chatbot_nvidia_base_url" value="<?php echo esc_attr( $chatbot_nvidia_base_url ); ?>" class="regular-text">
    <?php
}

// Timeout Settings Callback
function chatbot_nvidia_timeout_setting_callback($args) {

    // Get the saved chatbot_nvidia_timeout value or default to 240
    $timeout = esc_attr(get_option('chatbot_nvidia_timeout_setting', 240));

    // Allow for a range of tokens between 5 and 500 in 5-step increments - Ver 1.8.8
    ?>
    <select id="chatbot_nvidia_timeout_setting" name="chatbot_nvidia_timeout_setting">
        <?php
        for ($i=5; $i<=500; $i+=5) {
            echo '<option value="' . esc_attr($i) . '" ' . selected($timeout, (string)$i, false) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php

}

// Register API settings
function chatbot_nvidia_api_settings_init() {

    add_settings_section(
        'chatbot_nvidia_settings_section',
        'API/NVIDIA Settings',
        'chatbot_nvidia_model_settings_section_callback',
        'chatbot_nvidia_model_settings_general'
    );

    // API/NVIDIA settings tab - Ver 2.1.8
    register_setting('chatbot_nvidia_api_model', 'chatbot_nvidia_api_enabled');
    register_setting('chatbot_nvidia_api_model', 'chatbot_nvidia_api_key');
    register_setting('chatbot_nvidia_api_model', 'chatbot_nvidia_max_tokens_setting'); // Max Tokens setting options
    register_setting('chatbot_nvidia_api_model', 'chatbot_nvidia_conversation_context'); // Conversation Context
    register_setting('chatbot_nvidia_api_model', 'chatbot_nvidia_temperature'); // Temperature
    register_setting('chatbot_nvidia_api_model', 'chatbot_nvidia_top_p'); // Top P

    add_settings_section(
        'chatbot_nvidia_api_model_general_section',
        'NVIDIA API Settings',
        'chatbot_nvidia_api_model_general_section_callback',
        'chatbot_nvidia_api_model_general'
    );

    add_settings_field(
        'chatbot_nvidia_api_key',
        'NVIDIA API Key',
        'chatbot_nvidia_api_key_callback',
        'chatbot_nvidia_api_model_general',
        'chatbot_nvidia_api_model_general_section'
    );

    register_setting('chatbot_nvidia_api_model', 'chatbot_nvidia_model_choice', 'sanitize_nvidia_model_choice');
    register_setting('chatbot_nvidia_api_model', 'chatbot_nvidia_max_tokens_setting', 'sanitize_nvidia_max_tokens_setting');

    add_settings_section(
        'chatbot_nvidia_api_model_chat_settings_section',
        'Chat Settings',
        'chatbot_nvidia_api_model_chat_settings_section_callback',
        'chatbot_nvidia_api_model_chat_settings'
    );

    add_settings_field(
        'chatbot_nvidia_model_choice',
        'NVIDIA Model Choice',
        'chatbot_nvidia_chat_model_choice_callback',
        'chatbot_nvidia_api_model_chat_settings',
        'chatbot_nvidia_api_model_chat_settings_section'
    );

    // Setting to adjust in small increments the number of Max Tokens - Ver 1.4.2
    add_settings_field(
        'chatbot_nvidia_max_tokens_setting',
        'Maximum Tokens Setting',
        'chatgpt_nvidia_max_tokens_setting_callback',
        'chatbot_nvidia_api_model_chat_settings',
        'chatbot_nvidia_api_model_chat_settings_section'
    );

    // Setting to adjust the conversation context
    add_settings_field(
        'chatbot_nvidia_conversation_context',
        'Conversation Context',
        'chatbot_nvidia_conversation_context_callback',
        'chatbot_nvidia_api_model_chat_settings',
        'chatbot_nvidia_api_model_chat_settings_section'
    );

    // Temperature
    add_settings_field(
        'chatbot_nvidia_temperature',
        'Temperature',
        'chatbot_nvidia_temperature_callback',
        'chatbot_nvidia_api_model_chat_settings',
        'chatbot_nvidia_api_model_chat_settings_section'
    );

    // Top P
    add_settings_field(
        'chatbot_nvidia_top_p',
        'Top P',
        'chatbot_nvidia_top_p_callback',
        'chatbot_nvidia_api_model_chat_settings',
        'chatbot_nvidia_api_model_chat_settings_section'
    );

    // Advanced Model Settings - Ver 1.9.5
    register_setting('chatbot_nvidia_api_model', 'chatbot_nvidia_base_url'); // Ver 1.8.1
    register_setting('chatbot_nvidia_api_model', 'chatbot_nvidia_timeout_setting'); // Ver 1.8.8

    add_settings_section(
        'chatbot_nvidia_api_model_advanced_section',
        'Advanced API Settings',
        'chatbot_nvidia_api_model_advanced_section_callback',
        'chatbot_nvidia_api_model_advanced'
    );

    // Set the base URL for the API
    add_settings_field(
        'chatbot_nvidia_base_url',
        'Base URL for API',
        'chatbot_nvidia_base_url_callback',
        'chatbot_nvidia_api_model_advanced',
        'chatbot_nvidia_api_model_advanced_section'
    );

    // Timeout setting
    add_settings_field(
        'chatbot_nvidia_timeout_setting',
        'Timeout Setting (in seconds)',
        'chatbot_nvidia_timeout_setting_callback',
        'chatbot_nvidia_api_model_advanced',
        'chatbot_nvidia_api_model_advanced_section'
    );
}
add_action('admin_init', 'chatbot_nvidia_api_settings_init');