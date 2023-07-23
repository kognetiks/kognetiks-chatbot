<?php
/**
 * Chatbot ChatGPT for WordPress - Settings - API/Model Page
 *
 * This file contains the code for the Chatbot ChatGPT settings page.
 * It allows users to configure the API key and other parameters
 * required to access the ChatGPT API from their own account.
 *
 * @package chatbot-chatgpt
 */

 // API/Model settings section callback - Ver 1.3.0
function chatbot_chatgpt_api_model_section_callback($args) {
    ?>
    <p>Configure settings for the Chatbot ChatGPT plugin by adding your API key and selection the GPT model of your choice.</p>
    <p>This plugin requires an API key from OpenAI to function. You can obtain an API key by signing up at <a href="https://platform.openai.com/account/api-keys" target="_blank">https://platform.openai.com/account/api-keys</a>.</p>
    <p>More information about ChatGPT models and their capability can be found at <a href="https://platform.openai.com/docs/models/overview" taget="_blank">https://platform.openai.com/docs/models/overview</a>.</p>
    <p>Enter your ChatGPT API key below and select the OpenAI model of your choice.</p>
    <p>As soon as the API for GPT-4 is available for general use, you will be able to select from the latest available models.</p>
    <?php
}

// API key field callback
function chatbot_chatgpt_api_key_callback($args) {
    $api_key = esc_attr(get_option('chatgpt_api_key'));
    ?>
    <!-- <input type="text" id="chatgpt_api_key" name="chatgpt_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text"> -->
    <!-- Obfuscate the API key - Ver 1.5.0 -->
    <input type="password" id="chatgpt_api_key" name="chatgpt_api_key"  value="<?php echo empty($api_key) ? '' : '********'; ?>">
    <?php
}

function sanitize_api_key($input) {
    // if input is '********', return the existing API key instead
    if ($input === '********') {
        return get_option('your_api_key_option_name');
    }
    // otherwise, save the new API key
    return $input;
}

// Model choice
function chatbot_chatgpt_model_choice_callback($args) {
    // Get the saved chatgpt_model_choice value or default to "gpt-3.5-turbo"
    $model_choice = esc_attr(get_option('chatgpt_model_choice', 'gpt-3.5-turbo'));
    ?>
    <select id="chatgpt_model_choice" name="chatgpt_model_choice">
        <!-- Allow for gpt-4 in Ver 1.4.2 -->
        <option value="<?php echo esc_attr( 'gpt-4' ); ?>" <?php selected( $model_choice, 'gpt-4' ); ?>><?php echo esc_html( 'gpt-4' ); ?></option>
        <option value="<?php echo esc_attr( 'gpt-3.5-turbo' ); ?>" <?php selected( $model_choice, 'gpt-3.5-turbo' ); ?>><?php echo esc_html( 'gpt-3.5-turbo' ); ?></option>
    </select>
    <?php
}

// Max Tokens choice - Ver 1.4.2
function chatgpt_max_tokens_setting_callback($args) {
    // Get the saved chatgpt_max_tokens_setting or default to 150
    $max_tokens = esc_attr(get_option('chatgpt_max_tokens_setting', '150'));
    ?>
    <select id="chatgpt_max_tokens_setting" name="chatgpt_max_tokens_setting">
        <option value="<?php echo esc_attr( '100' ); ?>" <?php selected( $max_tokens, '100' ); ?>><?php echo esc_html( '100' ); ?></option>
        <option value="<?php echo esc_attr( '150' ); ?>" <?php selected( $max_tokens, '150' ); ?>><?php echo esc_html( '150' ); ?></option>
        <option value="<?php echo esc_attr( '200' ); ?>" <?php selected( $max_tokens, '200' ); ?>><?php echo esc_html( '200' ); ?></option>
        <option value="<?php echo esc_attr( '250' ); ?>" <?php selected( $max_tokens, '250' ); ?>><?php echo esc_html( '250' ); ?></option>
        <option value="<?php echo esc_attr( '300' ); ?>" <?php selected( $max_tokens, '300' ); ?>><?php echo esc_html( '300' ); ?></option>
        <option value="<?php echo esc_attr( '350' ); ?>" <?php selected( $max_tokens, '350' ); ?>><?php echo esc_html( '350' ); ?></option>
        <option value="<?php echo esc_attr( '400' ); ?>" <?php selected( $max_tokens, '400' ); ?>><?php echo esc_html( '400' ); ?></option>
        <option value="<?php echo esc_attr( '450' ); ?>" <?php selected( $max_tokens, '450' ); ?>><?php echo esc_html( '450' ); ?></option>
        <option value="<?php echo esc_attr( '500' ); ?>" <?php selected( $max_tokens, '500' ); ?>><?php echo esc_html( '500' ); ?></option>
    </select>
    <?php
}

