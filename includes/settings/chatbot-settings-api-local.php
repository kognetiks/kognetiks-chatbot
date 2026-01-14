<?php
/**
 * Kognetiks Chatbot - Settings - API/Local Page
 *
 * This file contains the code for the Local settings page.
 * It allows users to configure the API key and other parameters
 * required to access the ChatGPT API from their own account.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// API/Local settings section callback - Ver 2.2.6
function chatbot_local_model_settings_section_callback($args) {
    ?>
    <p>Configure the default settings for the Chatbot plugin to use a Local Server for chat generation.  Start by adding your API key then selecting your choices below.  <strong>NOTE</strong>: This may not be required in your configuration.</p>
    <p>More information about Local models and their capability can be found at <a href="https://huggingface.co/models?library=gguf&sort=downloads" target="_blank">https://huggingface.co/models?library=gguf&sort=downloads</a>.</p>
    <p><b><i>Don't forget to click </i><code>Save Settings</code><i> to save any changes your might make.</i></b></p>
    <p><strong>IMPORTANT</strong>: If you are using the Local API, you will need to have installed and configured the JAN.AI engine on your own server.  This plugin does not provide a server or an AI engine. The JAN.AI server is an open source ChatGTP-alternatvie that runs 100% on your systems.  Find details here: <a href="https://jan.ai/" target="_blank">https://jan.ai/</a>.</p>
    <p style="background-color: #e0f7fa; padding: 10px;"><b>For an explanation of the API/Local settings and additional documentation please click <a href="?page=chatbot-chatgpt&tab=support&dir=api-local-settings&file=api-local-model-settings.md">here</a>.</b></p>
    <?php
}

function chatbot_local_api_model_general_section_callback($args) {
    ?>
    <p>Configure the settings for the plugin by adding your API key. An API key for your Local Server many not be required to. Consult your Local Server configuration setup and details.</p>
    <p><strong>NOTE</strong>: Leave blank if no API key is required.</p>
    <?php
}

// API key field callback
function chatbot_local_api_key_callback($args) {
    $api_key = esc_attr(get_option('chatbot_local_api_key'));
    // Decrypt the API key - Ver 2.2.6
    $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
    ?>
    <input type="password" id="chatbot_local_api_key" name="chatbot_local_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text"  autocomplete="off">
    <?php
}

function chatbot_local_api_model_chat_settings_section_callback($args) {
    ?>
    <p>Configure the settings for the plugin when using chat models. Depending on the Local model you choose, the maximum tokens may be as high as 4097. The default is 150. For more information about the maximum tokens parameter, please see <a href="https://jan.ai/docs" target="_blank">https://jan.ai/docs</a>. Enter a conversation context to help the model understand the conversation. See the default for ideas. Some example shortcodes include:</p>
        <p><b>NOTE:</b> Enter your API key (above), click <code>Save Settings</code> at the bottom of this page, in order to retrieve the full list of available models.</p>
        <p><strong>IMPORTANT</strong>: You can download and use any GGUF model from sources like <a href="https://huggingface.co/models?library=gguf&sort=downloads" target="_blank">Hugging Face</a>.  The plugin will work with any model that is compatible with the JAN.AI engine.</p>
    <ul style="list-style-type: disc; list-style-position: inside; padding-left: 1em;">
        <li><code>&#91;chatbot&#93;</code> - Default chat model, style is floating</li>
        <li><code>&#91;chatbot style="floating" model="llama3.2-3b-instruct"&#93;</code> - Style is floating, specific model</li>
        <li><code>&#91;chatbot style="embedded" model="llama3.2-3b-instruct"&#93;</code> - Style is embedded, default chat model</li>
    </ul>
    <?php
}

// Local Model Settings Callback - Ver 2.3.3
function chatbot_local_chat_model_choice_callback($args) {

    $model_choice = esc_attr(get_option('chatbot_local_model_choice', 'llama3.2-3b-instruct'));
    $local_api_enabled = esc_attr(get_option('chatbot_local_api_enabled', 'Yes'));

    // Fetch models from the API
    $models = chatbot_local_get_models();

    // Auto-sync: Update chatbot setting to match Jan.ai active model - Ver 2.3.3
    if (!empty($models) && is_array($models)) {
        $active_model = $models[0]; // Jan.ai API now returns only the active model
        $current_setting = get_option('chatbot_local_model_choice', '');
        
        // If the active model is different from our setting, update it
        if ($current_setting !== $active_model) {
            update_option('chatbot_local_model_choice', $active_model);
            $model_choice = $active_model;
            
            // Show sync notification
            echo '<div style="background-color: #e7f3ff; padding: 8px; margin-bottom: 10px; border-left: 4px solid #0073aa;">';
            echo '<strong>Auto-Sync:</strong> Model setting updated to match Jan.ai active model: <code>' . esc_html($active_model) . '</code>';
            echo '</div>';
        }
    }

    // Check for errors
    if (is_string($models) && strpos($models, 'Error:') === 0) {
        // If there's an error, display the hardcoded list
        $model_choice = esc_attr(get_option('chatbot_local_model_choice', 'llama3.2-3b-instruct'));
        ?>
        <select id="chatbot_chatgpt_model_choice" name="chatbot_chatgpt_model_choice">
            <option value="<?php echo esc_attr( 'llama3.2-3b-instruct' ); ?>" <?php selected( $model_choice, 'llama3.2-3b-instruct' ); ?>><?php echo esc_html( 'llama3.2-3b-instruct' ); ?></option>
        </select>
        <p class="description" style="color: #d63638;">
            <strong>Connection Error:</strong> Cannot connect to Jan.ai server. Using fallback model selection.
        </p>
        <?php
    } else {
        // If models are fetched successfully, display them dynamically
        ?>
        <select id="chatbot_local_model_choice" name="chatbot_local_model_choice">
            <?php foreach ($models as $model): ?>
                <option value="<?php echo esc_attr($model); ?>" <?php selected($model_choice, $model); ?>><?php echo esc_html($model); ?></option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <strong>Active Model:</strong> This reflects the currently active model in Jan.ai. 
            To use a different model, activate it in Jan.ai first, then refresh this page.
        </p>
        <?php
    }

}

// Max Tokens choice - Ver 2.2.6
function chatgpt_local_max_tokens_setting_callback($args) {
    // Get the saved chatbot_local_max_tokens_setting or default to 1000
    $max_tokens = esc_attr(get_option('chatbot_local_max_tokens_setting', '1000'));
    // Allow for a range of tokens between 100 and 10000 in 100-step increments - Ver 2.2.6
    ?>
    <select id="chatbot_local_max_tokens_setting" name="chatbot_local_max_tokens_setting">
        <?php
        for ($i=100; $i<=10000; $i+=100) {
            echo '<option value="' . esc_attr($i) . '" ' . selected($max_tokens, (string)$i, false) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php
}

// Conversation Context
function chatbot_local_conversation_context_callback($args) {
    // Get the value of the setting we've registered with register_setting()
    $chatbot_local_conversation_context = esc_attr(get_option('chatbot_local_conversation_context'));

    // Check if the option has been set, if not, use a default value
    if (empty($chatbot_local_conversation_context)) {
        $chatbot_local_conversation_context = "You are a versatile, friendly, and helpful assistant designed to support me in a variety of tasks that responds in Markdown.";
        // Save the default value into the option
        update_option('chatbot_local_conversation_context', $chatbot_local_conversation_context);
    }

    ?>
    <!-- Define the textarea field. -->
    <textarea id='chatbot_local_conversation_context' name='chatbot_local_conversation_context' rows='5' cols='50' maxlength='12500'><?php echo esc_html(stripslashes($chatbot_local_conversation_context)); ?></textarea>
    <?php
}

// Set chatbot_local_temperature
function chatbot_local_temperature_callback($args) {
    $temperature = esc_attr(get_option('chatbot_local_temperature', 0.5));
    ?>
    <select id="chatbot_local_temperature" name="chatbot_local_temperature">
        <?php
        for ($i = 0.01; $i <= 2.01; $i += 0.01) {
            echo '<option value="' . $i . '" ' . selected($temperature, (string)$i) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php
}

// Set chatbot_local_top_p
function chatbot_local_top_p_callback($args) {
    $top_p = esc_attr(get_option('chatbot_local_top_p', 1.00));
    ?>
    <select id="chatbot_local_top_p" name="chatbot_local_top_p">
        <?php
        for ($i = 0.01; $i <= 1.01; $i += 0.01) {
            echo '<option value="' . $i . '" ' . selected($top_p, (string)$i) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php
}

// API Advanced settings section callback
function chatbot_local_api_model_advanced_section_callback($args) {
    ?>
    <p><strong>CAUTION</strong>: Configure the advanced settings for the plugin. Enter the base URL for the Local API.  The default is <code>https://127.0.0.1:1337/v1</code> where 127.0.0.1 is the server address, 1337 is the port, and /v1 is the prefix.</p>
    <?php
}

// Base URL for the Local API
function chatbot_local_base_url_callback($args) {
    $chatbot_local_base_url = esc_attr(get_option('chatbot_local_base_url', 'http://127.0.0.1:1337/v1'));
    ?>
    <input type="text" id="chatbot_local_base_url" name="chatbot_local_base_url" value="<?php echo esc_attr( $chatbot_local_base_url ); ?>" class="regular-text">
    <?php
}

// Timeout Settings Callback
function chatbot_local_timeout_setting_callback($args) {

    // Get the saved chatbot_local_timeout value or default to 240
    $timeout = esc_attr(get_option('chatbot_local_timeout_setting', 240));

    // Allow for a range of tokens between 5 and 500 in 5-step increments - Ver 2.2.6
    ?>
    <select id="chatbot_local_timeout_setting" name="chatbot_local_timeout_setting">
        <?php
        for ($i=5; $i<=500; $i+=5) {
            echo '<option value="' . esc_attr($i) . '" ' . selected($timeout, (string)$i, false) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php

}

// Register API settings
function chatbot_local_api_settings_init() {

    add_settings_section(
        'chatbot_local_settings_section',                   // ID used to identify this section and with which to register options
        'API/Local Settings',                               // Title to be displayed on the administration page
        'chatbot_local_model_settings_section_callback',    // Callback used to render the description of the section
        'chatbot_local_model_settings_general'              // Page on which to add this section of options
    );

    // API/Local settings tab - Ver 2.2.6
    register_setting('chatbot_local_api_model', 'chatbot_local_api_enabled');
    register_setting('chatbot_local_api_model', 'chatbot_local_api_key', 'chatbot_chatgpt_sanitize_api_key'); // API key
    register_setting('chatbot_local_api_model', 'chatbot_local_max_tokens_setting'); // Max Tokens setting options
    register_setting('chatbot_local_api_model', 'chatbot_local_conversation_context'); // Conversation Context
    register_setting('chatbot_local_api_model', 'chatbot_local_temperature'); // Temperature
    register_setting('chatbot_local_api_model', 'chatbot_local_top_p'); // Top P

    add_settings_section(
        'chatbot_local_api_model_general_section',
        'Local API Settings',
        'chatbot_local_api_model_general_section_callback',
        'chatbot_local_api_model_general'
    );

    add_settings_field(
        'chatbot_local_api_key',
        'Local API Key',
        'chatbot_local_api_key_callback',
        'chatbot_local_api_model_general',
        'chatbot_local_api_model_general_section'
    );

    register_setting(
        'chatbot_local_api_model',
        'chatbot_local_model_choice',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        )
    );

    register_setting(
        'chatbot_local_api_model',
        'chatbot_local_max_tokens_setting',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        )
    );

    add_settings_section(
        'chatbot_local_api_model_chat_settings_section',
        'Chat Settings',
        'chatbot_local_api_model_chat_settings_section_callback',
        'chatbot_local_api_model_chat_settings'
    );

    add_settings_field(
        'chatbot_local_model_choice',
        'Local Model Choice',
        'chatbot_local_chat_model_choice_callback',
        'chatbot_local_api_model_chat_settings',
        'chatbot_local_api_model_chat_settings_section'
    );

    // Setting to adjust in small increments the number of Max Tokens - Ver 2.2.6
    add_settings_field(
        'chatbot_local_max_tokens_setting',
        'Maximum Tokens Setting',
        'chatgpt_local_max_tokens_setting_callback',
        'chatbot_local_api_model_chat_settings',
        'chatbot_local_api_model_chat_settings_section'
    );

    // Setting to adjust the conversation context
    add_settings_field(
        'chatbot_local_conversation_context',
        'Conversation Context',
        'chatbot_local_conversation_context_callback',
        'chatbot_local_api_model_chat_settings',
        'chatbot_local_api_model_chat_settings_section'
    );

    // Temperature
    add_settings_field(
        'chatbot_local_temperature',
        'Temperature',
        'chatbot_local_temperature_callback',
        'chatbot_local_api_model_chat_settings',
        'chatbot_local_api_model_chat_settings_section'
    );

    // Top P
    add_settings_field(
        'chatbot_local_top_p',
        'Top P',
        'chatbot_local_top_p_callback',
        'chatbot_local_api_model_chat_settings',
        'chatbot_local_api_model_chat_settings_section'
    );

    // Advanced Model Settings - Ver 2.2.6
    register_setting('chatbot_local_api_model', 'chatbot_local_base_url'); // Ver 2.2.6
    register_setting('chatbot_local_api_model', 'chatbot_local_timeout_setting'); // Ver 2.2.6

    add_settings_section(
        'chatbot_local_api_model_advanced_section',
        'Advanced API Settings',
        'chatbot_local_api_model_advanced_section_callback',
        'chatbot_local_api_model_advanced'
    );

    // Set the base URL for the API
    add_settings_field(
        'chatbot_local_base_url',
        'Base URL for API',
        'chatbot_local_base_url_callback',
        'chatbot_local_api_model_advanced',
        'chatbot_local_api_model_advanced_section'
    );

    // Timeout setting
    add_settings_field(
        'chatbot_local_timeout_setting',
        'Timeout Setting (in seconds)',
        'chatbot_local_timeout_setting_callback',
        'chatbot_local_api_model_advanced',
        'chatbot_local_api_model_advanced_section'
    );
}
add_action('admin_init', 'chatbot_local_api_settings_init');
