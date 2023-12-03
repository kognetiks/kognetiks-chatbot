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

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) )
die;

// API/Model settings section callback - Ver 1.3.0
function chatbot_chatgpt_api_model_section_callback($args) {
    ?>
    <p>Configure settings for the Chatbot ChatGPT plugin by adding your API key and selection the GPT model of your choice.</p>
    <p>This plugin requires an API key from OpenAI to function. You can obtain an API key by signing up at <a href="https://platform.openai.com/account/api-keys" target="_blank">https://platform.openai.com/account/api-keys</a>.</p>
    <p>More information about ChatGPT models and their capability can be found at <a href="https://platform.openai.com/docs/models/overview" taget="_blank">https://platform.openai.com/docs/models/overview</a>.</p>
    <p>Enter your ChatGPT API key below and select the OpenAI model of your choice.</p>
    <p>As soon as the API for GPT-4 is available for general use, you will be able to select from the latest available models.</p>
    <h2>CustomGTPs</h2>
    <p><i>If you have developed a Custom GPT Assistant, you will need the id of the assistant.</p>
    <p>Enter your Custom GPT Assistant Id instead of ChatGPT.  Set the 'Use Custom GPT Assistant ID' to 'Yes'.</p>
    <p>Otherwise, you can leave the Custom GPT Assistant ID field blank and set the usage to 'No'.</p>
    <p>More information can be found here <a href="https://platform.openai.com/playground?mode=assistant" target="_blank">https://platform.openai.com/playground?mode=assistant</a>.</i></p>
    <?php
}


// API key field callback
function chatbot_chatgpt_api_key_callback($args) {
    $api_key = get_option('chatgpt_api_key');
    ?>
    <input type="password" id="chatgpt_api_key" name="chatgpt_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text">
    <?php
}

// Use Custom GPT Assistant Id field callback - Ver 1.6.7
function chatbot_chatgpt_use_custom_gpt_assistant_id_callback($args) {
    $use_assistant_id = esc_attr(get_option('chatbot_chatgpt_use_custom_gpt_assistant_id', 'No'));
    ?>
    <select id="chatbot_chatgpt_use_custom_gpt_assistant_id" name="chatbot_chatgpt_use_custom_gpt_assistant_id">
        <option value="Yes" <?php selected( $use_assistant_id, 'Yes' ); ?>><?php echo esc_html( 'Yes' ); ?></option>
        <option value="No" <?php selected( $use_assistant_id, 'No' ); ?>><?php echo esc_html( 'No' ); ?></option>
    </select>
    <?php
    if ($use_assistant_id == 'No') {
        update_option('chatbot_chatgpt_assistant_id', '');
    }
}

// CustomGPT Assistant Id field callback - Ver 1.6.7
function chatbot_chatgpt_assistant_id_callback($args) {
    $assistant_id = esc_attr(get_option('chatbot_chatgpt_assistant_id', 'Please provide the Custom GPT Assistant Id.'));
    $use_assistant_id = esc_attr(get_option('chatbot_chatgpt_use_custom_gpt_assistant_id', 'No'));
    if ($use_assistant_id == 'Yes' && ($assistant_id == 'Please provide the Custom GPT Assistant Id.' or empty($assistant_id))) {
        $assistant_id = 'Please provide the Custom GPT Assistant Id.';
    }
    // Set default value if empty
    // $assistant_id = empty($assistant_id) ? 'Please provide the Custom GPT Assistant Id.': $assistant_id;
    ?>
    <input type="text" id="chatbot_chatgpt_assistant_id" name="chatbot_chatgpt_assistant_id" value="<?php echo esc_attr( $assistant_id ); ?>" class="regular-text">
    <?php
}

// OpenAI Models
// https://platform.openai.com/docs/models
// TODO EXPAND THE LIST OF MODELS
// https://platform.openai.com/docs/models/gpt-4-and-gpt-4-turbo
// Model choice
function chatbot_chatgpt_model_choice_callback($args) {
    // Get the saved chatgpt_model_choice value or default to "gpt-3.5-turbo"
    $model_choice = esc_attr(get_option('chatgpt_model_choice', 'gpt-3.5-turbo'));
    ?>
    <select id="chatgpt_model_choice" name="chatgpt_model_choice">
        <option value="<?php echo esc_attr( 'gpt-4-1106-preview' ); ?>" <?php selected( $model_choice, 'gpt-4-1106-preview' ); ?>><?php echo esc_html( 'gpt-4-1106-preview' ); ?></option>
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
    // Allow for a range of tokens between 100 and 1000 in 50 step increments - Ver 1.6.1
    ?>
    <select id="chatgpt_max_tokens_setting" name="chatgpt_max_tokens_setting">
        <?php
        for ($i=100; $i<=1000; $i+=50) {
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
    <textarea id='chatbot_chatgpt_conversation_context' name='chatbot_chatgpt_conversation_context' rows='5' cols='50' maxlength='2500'><?php echo esc_html(stripslashes($chatbot_chatgpt_conversation_context)); ?></textarea>
    <?php
}
