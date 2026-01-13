<?php
/**
 * Kognetiks Chatbot - Settings - API/Anthropic Page
 *
 * This file contains the code for the Anthropic settings page.
 * It allows users to configure the API key and other parameters
 * required to access the ChatGPT API from their own account.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// API/Anthropic settings section callback - Ver 2.1.8
function chatbot_anthropic_model_settings_section_callback($args) {
    ?>
    <p>Configure the default settings for the Chatbot plugin to use Anthropic for chat generation.  Start by adding your API key then selecting your choices below.</p>
    <p>More information about Anthropic models and their capability can be found at <a href="https://docs.anthropic.com/en/docs/about-claude/models" target="_blank">https://docs.anthropic.com/en/docs/about-claude/models</a>.</p>
    <p><b><i>Don't forget to click </i><code>Save Settings</code><i> to save any changes your might make.</i></b></p>
    <p style="background-color: #e0f7fa; padding: 10px;"><b>For an explanation of the API/Anthropic settings and additional documentation please click <a href="?page=chatbot-chatgpt&tab=support&dir=api-anthropic-settings&file=api-anthropic-model-settings.md">here</a>.</b></p>                                                                                 
    <?php
}

function chatbot_anthropic_api_model_general_section_callback($args) {
    ?>
    <p>Configure the settings for the plugin by adding your API key. This plugin requires an API key from Anthropic to function. You can obtain an API key by signing up at <a href="https://console.anthropic.com/login" target="_blank">https://console.anthropic.com/login</a>.</p>
    <?php
}

// API key field callback
function chatbot_anthropic_api_key_callback($args) {
    $api_key = esc_attr(get_option('chatbot_anthropic_api_key'));
    // Decrypt the API key - Ver 2.2.6
    $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
    ?>
    <input type="password" id="chatbot_anthropic_api_key" name="chatbot_anthropic_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text"  autocomplete="off">
    <?php
}

function chatbot_anthropic_api_model_chat_settings_section_callback($args) {
    ?>
    <p>Configure the settings for the plugin when using chat models. Depending on the Anthropic model you choose, the maximum tokens may be as high as 4097. The default is 150. For more information about the maximum tokens parameter, please see <a href="https://docs.anthropic.com/en/docs/about-claude/models" target="_blank">https://docs.anthropic.com/en/docs/about-claude/models</a>. Enter a conversation context to help the model understand the conversation. See the default for ideas. Some example shortcodes include:</p>
    <p><b>NOTE:</b> Enter your API key (above), click <code>Save Settings</code> at the bottom of this page, in order to retrieve the full list of available models.</p>
    <ul style="list-style-type: disc; list-style-position: inside; padding-left: 1em;">
        <li><code>&#91;chatbot&#93;</code> - Default chat model, style is floating</li>
        <li><code>&#91;chatbot style="floating" model="claude-3-5-sonnet-latest"&#93;</code> - Style is floating, specific model</li>
        <li><code>&#91;chatbot style="embedded" model="claude-3-5-sonnet-latest"&#93;</code> - Style is embedded, default chat model</li>
    </ul>
    <?php
}

// Anthropic Model Settings Callback - Ver 2.1.8
function chatbot_anthropic_chat_model_choice_callback($args) {

    $model_choice = esc_attr(get_option('chatbot_anthropic_model_choice', 'claude-3-5-sonnet-latest'));
    $anthropic_api_enabled = esc_attr(get_option('chatbot_anthropic_api_enabled', 'Yes'));

    // Fetch models from the API
    $models = chatbot_anthropic_get_models();

    // Remove the models not owned by Anthropic
    $models = array_filter($models, function($model) {
        return $model['owned_by'] === 'anthropic';
    });

    // Check for errors
    if (is_string($models) && strpos($models, 'Error:') === 0) {
        // If there's an error, display the hardcoded list
        $model_choice = esc_attr(get_option('chatbot_anthropic_model_choice', 'claude-3-5-sonnet-latest'));
        ?>
        <select id="chatbot_chatgpt_model_choice" name="chatbot_chatgpt_model_choice">
            <option value="<?php echo esc_attr( 'claude-3-5-sonnet-latest' ); ?>" <?php selected( $model_choice, 'claude-3-5-sonnet-latest' ); ?>><?php echo esc_html( 'claude-3-5-sonnet-latest' ); ?></option>
        </select>
        <?php
    } else {
        // If models are fetched successfully, display them dynamically
        ?>
        <select id="chatbot_anthropic_model_choice" name="chatbot_anthropic_model_choice">
            <?php foreach ($models as $model): ?>
                <option value="<?php echo esc_attr($model['id']); ?>" <?php selected(esc_attr(get_option('chatbot_anthropic_model_choice')), $model['id']); ?>><?php echo esc_html($model['id']); ?></option>
            <?php endforeach; ?>
            ?>
        </select>
        <?php
    }

}

// Max Tokens choice - Ver 2.1.8
function chatgpt_anthropic_max_tokens_setting_callback($args) {
    // Get the saved chatbot_anthropic_max_tokens_setting or default to 1000
    $max_tokens = esc_attr(get_option('chatbot_anthropic_max_tokens_setting', '1000'));
    // Allow for a range of tokens between 100 and 8192 in 100-step increments - Ver 2.0.4
    ?>
    <select id="chatbot_anthropic_max_tokens_setting" name="chatbot_anthropic_max_tokens_setting">
        <?php
        for ($i=100; $i<=8100; $i+=100) {
            echo '<option value="' . esc_attr($i) . '" ' . selected($max_tokens, (string)$i, false) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php
}

// Conversation Context
function chatbot_anthropic_conversation_context_callback($args) {
    // Get the value of the setting we've registered with register_setting()
    $chatbot_anthropic_conversation_context = esc_attr(get_option('chatbot_anthropic_conversation_context'));

    // Check if the option has been set, if not, use a default value
    if (empty($chatbot_anthropic_conversation_context)) {
        $chatbot_anthropic_conversation_context = "You are a versatile, friendly, and helpful assistant designed to support me in a variety of tasks that responds in Markdown.";
        // Save the default value into the option
        update_option('chatbot_anthropic_conversation_context', $chatbot_anthropic_conversation_context);
    }

    ?>
    <!-- Define the textarea field. -->
    <textarea id='chatbot_anthropic_conversation_context' name='chatbot_anthropic_conversation_context' rows='5' cols='50' maxlength='12500'><?php echo esc_html(stripslashes($chatbot_anthropic_conversation_context)); ?></textarea>
    <?php
}

// Set chatbot_anthropic_temperature
function chatbot_anthropic_temperature_callback($args) {
    $temperature = esc_attr(get_option('chatbot_anthropic_temperature', 0.5));
    ?>
    <select id="chatbot_anthropic_temperature" name="chatbot_anthropic_temperature">
        <?php
        for ($i = 0.01; $i <= 2.01; $i += 0.01) {
            echo '<option value="' . $i . '" ' . selected($temperature, (string)$i) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php
}

// Set chatbot_anthropic_top_p
function chatbot_anthropic_top_p_callback($args) {
    $top_p = esc_attr(get_option('chatbot_anthropic_top_p', 1.00));
    ?>
    <select id="chatbot_anthropic_top_p" name="chatbot_anthropic_top_p">
        <?php
        for ($i = 0.01; $i <= 1.01; $i += 0.01) {
            echo '<option value="' . $i . '" ' . selected($top_p, (string)$i) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php
}

// API Advanced settings section callback
function chatbot_anthropic_api_model_advanced_section_callback($args) {
    ?>
    <p><strong>CAUTION</strong>: Configure the advanced settings for the plugin. Enter the base URL for the Anthropic API.  The default is <code>https://api.anthropic.com/v1</code>.</p>
    <?php
}

// Base URL for the Anthropic API
function chatbot_anthropic_base_url_callback($args) {
    $chatbot_anthropic_base_url = esc_attr(get_option('chatbot_anthropic_base_url', 'https://api.anthropic.com/v1'));
    ?>
    <input type="text" id="chatbot_anthropic_base_url" name="chatbot_anthropic_base_url" value="<?php echo esc_attr( $chatbot_anthropic_base_url ); ?>" class="regular-text">
    <?php
}

// Timeout Settings Callback
function chatbot_anthropic_timeout_setting_callback($args) {

    // Get the saved chatbot_anthropic_timeout value or default to 240
    $timeout = esc_attr(get_option('chatbot_anthropic_timeout_setting', 240));

    // Allow for a range of tokens between 5 and 500 in 5-step increments - Ver 1.8.8
    ?>
    <select id="chatbot_anthropic_timeout_setting" name="chatbot_anthropic_timeout_setting">
        <?php
        for ($i=5; $i<=500; $i+=5) {
            echo '<option value="' . esc_attr($i) . '" ' . selected($timeout, (string)$i, false) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php

}

// Register API settings
function chatbot_anthropic_api_settings_init() {

    add_settings_section(
        'chatbot_anthropic_settings_section',                   // ID used to identify this section and with which to register options
        'API/Anthropic Settings',                               // Title to be displayed on the administration page
        'chatbot_anthropic_model_settings_section_callback',    // Callback used to render the description of the section
        'chatbot_anthropic_model_settings_general'              // Page on which to add this section of options
    );

    // API/Anthropic settings tab - Ver 2.1.8
    register_setting('chatbot_anthropic_api_model', 'chatbot_anthropic_api_enabled');
    register_setting('chatbot_anthropic_api_model', 'chatbot_anthropic_api_key', 'chatbot_chatgpt_sanitize_api_key');
    register_setting('chatbot_anthropic_api_model', 'chatbot_anthropic_max_tokens_setting'); // Max Tokens setting options
    register_setting('chatbot_anthropic_api_model', 'chatbot_anthropic_conversation_context'); // Conversation Context
    register_setting('chatbot_anthropic_api_model', 'chatbot_anthropic_temperature'); // Temperature
    register_setting('chatbot_anthropic_api_model', 'chatbot_anthropic_top_p'); // Top P

    add_settings_section(
        'chatbot_anthropic_api_model_general_section',
        'Anthropic API Settings',
        'chatbot_anthropic_api_model_general_section_callback',
        'chatbot_anthropic_api_model_general'
    );

    add_settings_field(
        'chatbot_anthropic_api_key',
        'Anthropic API Key',
        'chatbot_anthropic_api_key_callback',
        'chatbot_anthropic_api_model_general',
        'chatbot_anthropic_api_model_general_section'
    );

    register_setting(
        'chatbot_anthropic_api_model',
        'chatbot_anthropic_model_choice',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        )
    );

    register_setting(
        'chatbot_anthropic_api_model',
        'chatbot_anthropic_max_tokens_setting',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        )
    );

    add_settings_section(
        'chatbot_anthropic_api_model_chat_settings_section',
        'Chat Settings',
        'chatbot_anthropic_api_model_chat_settings_section_callback',
        'chatbot_anthropic_api_model_chat_settings'
    );

    add_settings_field(
        'chatbot_anthropic_model_choice',
        'Anthropic Model Choice',
        'chatbot_anthropic_chat_model_choice_callback',
        'chatbot_anthropic_api_model_chat_settings',
        'chatbot_anthropic_api_model_chat_settings_section'
    );

    // Setting to adjust in small increments the number of Max Tokens - Ver 1.4.2
    add_settings_field(
        'chatbot_anthropic_max_tokens_setting',
        'Maximum Tokens Setting',
        'chatgpt_anthropic_max_tokens_setting_callback',
        'chatbot_anthropic_api_model_chat_settings',
        'chatbot_anthropic_api_model_chat_settings_section'
    );

    // Setting to adjust the conversation context
    add_settings_field(
        'chatbot_anthropic_conversation_context',
        'Conversation Context',
        'chatbot_anthropic_conversation_context_callback',
        'chatbot_anthropic_api_model_chat_settings',
        'chatbot_anthropic_api_model_chat_settings_section'
    );

    // Temperature
    add_settings_field(
        'chatbot_anthropic_temperature',
        'Temperature',
        'chatbot_anthropic_temperature_callback',
        'chatbot_anthropic_api_model_chat_settings',
        'chatbot_anthropic_api_model_chat_settings_section'
    );

    // Top P
    add_settings_field(
        'chatbot_anthropic_top_p',
        'Top P',
        'chatbot_anthropic_top_p_callback',
        'chatbot_anthropic_api_model_chat_settings',
        'chatbot_anthropic_api_model_chat_settings_section'
    );

    // Advanced Model Settings - Ver 1.9.5
    register_setting('chatbot_anthropic_api_model', 'chatbot_anthropic_base_url'); // Ver 1.8.1
    register_setting('chatbot_anthropic_api_model', 'chatbot_anthropic_timeout_setting'); // Ver 1.8.8

    add_settings_section(
        'chatbot_anthropic_api_model_advanced_section',
        'Advanced API Settings',
        'chatbot_anthropic_api_model_advanced_section_callback',
        'chatbot_anthropic_api_model_advanced'
    );

    // Set the base URL for the API
    add_settings_field(
        'chatbot_anthropic_base_url',
        'Base URL for API',
        'chatbot_anthropic_base_url_callback',
        'chatbot_anthropic_api_model_advanced',
        'chatbot_anthropic_api_model_advanced_section'
    );

    // Timeout setting
    add_settings_field(
        'chatbot_anthropic_timeout_setting',
        'Timeout Setting (in seconds)',
        'chatbot_anthropic_timeout_setting_callback',
        'chatbot_anthropic_api_model_advanced',
        'chatbot_anthropic_api_model_advanced_section'
    );
}
add_action('admin_init', 'chatbot_anthropic_api_settings_init');
