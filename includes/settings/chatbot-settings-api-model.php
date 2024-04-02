<?php
/**
 * Kognetiks Chatbot for WordPress - Settings - API/Model Page
 *
 * This file contains the code for the Chatbot settings page.
 * It allows users to configure the API key and other parameters
 * required to access the ChatGPT API from their own account.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// API/Model settings section callback - Ver 1.3.0
function chatbot_chatgpt_api_model_section_callback($args) {
    ?>
    <p>Configure the settings for the Chatbot plugin by adding your API key and selecting the GPT model of your choice.</p>
    <h3>ChatGPT API Key</h3>
    <p>This plugin requires an API key from OpenAI to function. You can obtain an API key by signing up at <a href="https://platform.openai.com/account/api-keys" target="_blank">https://platform.openai.com/account/api-keys</a>.</p>
    <h3>ChatGPT Model Choice</h3>
    <p>Enter your ChatGPT API key below and select the OpenAI model of your choice.</p>
    <p>As soon as the API for new models become available for general use, you will be able to select from the list of latest available models.</p>
    <h3>Maximum Tokens</h3>
    <p>Depending on the OpenAI model you choose, the maximum tokens may be as high as 4097. The default is 150.</p>
    <p>For more information about the maximum tokens parameter, please see <a href="https://help.openai.com/en/articles/4936856-what-are-tokens-and-how-to-count-them" target="_blank">https://help.openai.com/en/articles/4936856-what-are-tokens-and-how-to-count-them</a>.</p>
    <h3>Conversation Context</h3>
    <p>Enter a conversation context to help the model understand the conversation.  See the default for ideas.</p>
    <h3>Base URL</h3>
    <p>Enter the base URL for the OpenAI API.  The default is <code>https://api.openai.com/v1</code>.</p>
    <h3>More Information</h3>
    <p>More information about ChatGPT models and their capability can be found at <a href="https://platform.openai.com/docs/models/overview" target="_blank">https://platform.openai.com/docs/models/overview</a>.</p>
    <?php
}


// API key field callback
function chatbot_chatgpt_api_key_callback($args) {
    $api_key = get_option('chatbot_chatgpt_api_key');
    ?>
    <input type="password" id="chatbot_chatgpt_api_key" name="chatbot_chatgpt_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text">
    <?php
}

// OpenAI Models
// https://platform.openai.com/docs/models
// EXPAND THE LIST OF MODELS STARTING WITH V1.9.4 - 2024 03 24
// https://platform.openai.com/docs/models/gpt-4-and-gpt-4-turbo
// Model choice
function chatbot_chatgpt_model_choice_callback($args) {
    // Get the saved chatbot_chatgpt_model_choice value or default to "gpt-3.5-turbo"
    $model_choice = esc_attr(get_option('chatbot_chatgpt_model_choice', 'gpt-3.5-turbo'));

    // Fetch models from the API
    $models = get_openai_models();

    // Check for errors
    if (is_string($models) && strpos($models, 'Error:') === 0) {
        // If there's an error, display the hardcoded list
        $model_choice = esc_attr(get_option('chatbot_chatgpt_model_choice', 'gpt-3.5-turbo'));
        ?>
        <select id="chatbot_chatgpt_model_choice" name="chatbot_chatgpt_model_choice">
            <option value="<?php echo esc_attr( 'gpt-4-1106-preview' ); ?>" <?php selected( $model_choice, 'gpt-4-1106-preview' ); ?>><?php echo esc_html( 'gpt-4-1106-preview' ); ?></option>
            <option value="<?php echo esc_attr( 'gpt-4' ); ?>" <?php selected( $model_choice, 'gpt-4' ); ?>><?php echo esc_html( 'gpt-4' ); ?></option>
            <option value="<?php echo esc_attr( 'gpt-3.5-turbo' ); ?>" <?php selected( $model_choice, 'gpt-3.5-turbo' ); ?>><?php echo esc_html( 'gpt-3.5-turbo' ); ?></option>
        </select>
        <?php
    } else {
        // If models are fetched successfully, display them dynamically
        ?>
        <select id="chatbot_chatgpt_model_choice" name="chatbot_chatgpt_model_choice">
            <?php foreach ($models as $model): ?>
                <option value="<?php echo esc_attr($model['id']); ?>" <?php selected(get_option('chatbot_chatgpt_model_choice'), $model['id']); ?>><?php echo esc_html($model['id']); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }

}

// Max Tokens choice - Ver 1.4.2
function chatgpt_max_tokens_setting_callback($args) {
    // Get the saved chatbot_chatgpt_max_tokens_setting or default to 150
    $max_tokens = esc_attr(get_option('chatbot_chatgpt_max_tokens_setting', '150'));
    // Allow for a range of tokens between 100 and 4096 in 50-step increments - Ver 1.6.1
    ?>
    <select id="chatbot_chatgpt_max_tokens_setting" name="chatbot_chatgpt_max_tokens_setting">
        <?php
        for ($i=100; $i<=4096; $i+=50) {
            echo '<option value="' . esc_attr($i) . '" ' . selected($max_tokens, (string)$i, false) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php
}

// Conversation Context - Ver 1.6.1
function chatbot_chatgpt_conversation_context_callback($args) {
    // Get the value of the setting we've registered with register_setting()
    $chatbot_chatgpt_conversation_context = get_option('chatbot_chatgpt_conversation_context');

    // Check if the option has been set, if not, use a default value
    if (empty($chatbot_chatgpt_conversation_context)) {
        $chatbot_chatgpt_conversation_context = "You are a versatile, friendly, and helpful assistant designed to support me in a variety of tasks.";
        // Save the default value into the option
        update_option('chatbot_chatgpt_conversation_context', $chatbot_chatgpt_conversation_context);
    }

    ?>
    <!-- Define the textarea field. -->
    <textarea id='chatbot_chatgpt_conversation_context' name='chatbot_chatgpt_conversation_context' rows='5' cols='50' maxlength='12500'><?php echo esc_html(stripslashes($chatbot_chatgpt_conversation_context)); ?></textarea>
    <?php
}

// Base URL for the OpenAI API - Ver 1.8.1
function chatbot_chatgpt_base_url_callback($args) {
    $chatbot_chatgpt_base_url = esc_attr(get_option('chatbot_chatgpt_base_url', 'https://api.openai.com/v1'));
    ?>
    <input type="text" id="chatbot_chatgpt_base_url" name="chatbot_chatgpt_base_url" value="<?php echo esc_attr( $chatbot_chatgpt_base_url ); ?>" class="regular-text">
    <?php
}

// Base URL function calls - Ver 1.8.1
function get_openai_api_base_url() {
    return esc_attr(get_option('chatbot_chatgpt_base_url', 'https://api.openai.com/v1'));
}

function get_threads_api_url() {
    return get_openai_api_base_url() . "/threads";
}

function get_files_api_url() {
    return get_openai_api_base_url() . "/files";
}

function get_chat_completions_api_url() {
    return get_openai_api_base_url() . "/chat/completions";
}

// Timeout Settings Callback - Ver 1.8.8
function chatbot_chatgpt_timeout_setting_callback($args) {
    // Get the saved chatbot_chatgpt_timeout value or default to 240
    $timeout = esc_attr(get_option('chatbot_chatgpt_timeout_setting', '240'));
    // Allow for a range of tokens between 5 and 500 in 5-step increments - Ver 1.8.8
    ?>
    <select id="chatbot_chatgpt_timeout_setting" name="chatbot_chatgpt_timeout_setting">
        <?php
        for ($i=5; $i<=500; $i+=5) {
            echo '<option value="' . esc_attr($i) . '" ' . selected($timeout, (string)$i, false) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php
}

// Voice Options Callback - Ver 1.9.5
function chatbot_chatgpt_voice_option_callback($args) {

    // https://platform.openai.com/docs/guides/text-to-speech
    // Options include Alloy, Echo, Fable, Onyx, Nova, and Shimmer

    // Get the saved chatbot_chatgpt_voice_options value or default to "Alloy"
    $voice_option = esc_attr(get_option('chatbot_chatgpt_voice_option', 'alloy'));
    ?>
    <select id="chatbot_chatgpt_voice_option" name="chatbot_chatgpt_voice_option">
        <option value="alloy" <?php selected($voice_option, 'alloy'); ?>>Alloy</option>
        <option value="echo" <?php selected($voice_option, 'echo'); ?>>Echo</option>
        <option value="fable" <?php selected($voice_option, 'fable'); ?>>Fable</option>
        <option value="onyx" <?php selected($voice_option, 'onyx'); ?>>Onyx</option>
        <option value="nova" <?php selected($voice_option, 'nova'); ?>>Nova</option>
        <option value="shimmer" <?php selected($voice_option, 'shimmer'); ?>>Shimmer</option>
    </select>
    <?php
}

// Voice Output Options Callback - Ver 1.9.5
function chatbot_chatgpt_audio_output_format_callback($args) {

    // https://platform.openai.com/docs/guides/text-to-speech
    // Options include mp3, opus, aac, flac, wav, and pcm

    // Get the saved chatbot_chatgpt_voice_output_options value or default to "mp3"
    $audio_output_format = esc_attr(get_option('chatbot_chatgpt_audio_output_format', 'mp3'));
    ?>
    <select id="chatbot_chatgpt_audio_output_format" name="chatbot_chatgpt_audio_output_format">
        <option value="mp3" <?php selected($audio_output_format, 'mp3'); ?>>MP3</option>
        <option value="opus" <?php selected($audio_output_format, 'opus'); ?>>OPUS</option>
        <option value="aac" <?php selected($audio_output_format, 'aac'); ?>>AAC</option>
        <option value="flac" <?php selected($audio_output_format, 'flac'); ?>>FLAC</option>
        <option value="wav" <?php selected($audio_output_format, 'wav'); ?>>WAV</option>
        <option value="pcm" <?php selected($audio_output_format, 'pcm'); ?>>PCM</option>
    </select>
    <?php
}