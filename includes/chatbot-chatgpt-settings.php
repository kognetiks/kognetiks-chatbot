<?php
/**
 * Chatbot ChatGPT for WordPress - Settings Page
 *
 * This file contains the code for the Chatbot ChatGPT settings page.
 * It allows users to configure the API key and other parameters
 * required to access the ChatGPT API from their own account.
 *
 * @package chatbot-chatgpt
 */

function chatbot_chatgpt_settings_page() {
    add_options_page('Chatbot ChatGPT Settings', 'Chatbot ChatGPT', 'manage_options', 'chatbot-chatgpt', 'chatbot_chatgpt_settings_page_html');
}
add_action('admin_menu', 'chatbot_chatgpt_settings_page');

// Settings page HTML
function chatbot_chatgpt_settings_page_html() {
 
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_GET['settings-updated'])) {
        add_settings_error('chatbot_chatgpt_messages', 'chatbot_chatgpt_message', 'Settings Saved', 'updated');
    }

    settings_errors('chatbot_chatgpt_messages');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('chatbot_chatgpt');
            do_settings_sections('chatbot_chatgpt');
            submit_button('Save Settings');
            ?>
        </form>
    </div>
    <?php
}

// Register settings
function chatbot_chatgpt_settings_init() {
    register_setting('chatbot_chatgpt', 'chatgpt_api_key');
    register_setting('chatbot_chatgpt', 'chatgpt_model_choice');
    register_setting('chatbot_chatgpt', 'chatgpt_bot_name');
    register_setting('chatbot_chatgpt', 'chatgpt_start_status');
    register_setting('chatbot_chatgpt', 'chatgpt_initial_greeting');
    register_setting('chatbot_chatgpt', 'chatgpt_subsequent_greeting');


    add_settings_section(
        'chatbot_chatgpt_section',
        'API Settings',
        'chatbot_chatgpt_section_callback',
        'chatbot_chatgpt'
    );

    add_settings_field(
        'chatgpt_api_key',
        'ChatGPT API Key',
        'chatbot_chatgpt_api_key_callback',
        'chatbot_chatgpt',
        'chatbot_chatgpt_section'
    );

    add_settings_field(
        'chatgpt_model_choice',
        'ChatGPT Model Choice',
        'chatbot_chatgpt_model_choice_callback',
        'chatbot_chatgpt',
        'chatbot_chatgpt_section'
    );

    // Added options to change Bot Name, Start Open/CLosed, and personalize Initial and Subsequent Greetings - Ver 1.1.0
    add_settings_field(
        'chatgpt_bot_name',
        'Bot Name',
        'chatbot_chatgpt_bot_name_callback',
        'chatbot_chatgpt',
        'chatbot_chatgpt_section'
    );

    add_settings_field(
        'chatgpt_start_status',
        'Start Status',
        'chatbot_chatgpt_start_status_callback',
        'chatbot_chatgpt',
        'chatbot_chatgpt_section'
    );

    add_settings_field(
        'chatgpt_initial_greeting',
        'Initial Greeting',
        'chatbot_chatgpt_initial_greeting_callback',
        'chatbot_chatgpt',
        'chatbot_chatgpt_section'
    );

    add_settings_field(
        'chatgpt_subsequent_greeting',
        'Subsequent Greeting',
        'chatbot_chatgpt_subsequent_greeting_callback',
        'chatbot_chatgpt',
        'chatbot_chatgpt_section'
    );

}

add_action('admin_init', 'chatbot_chatgpt_settings_init');

// Settings section callback
function chatbot_chatgpt_section_callback($args) {
    ?>
    <p>This plugin requires an API key from OpenAI to function. You can obtain an API key by signing up at <a href="https://platform.openai.com/account/api-keys" target="_blank">https://platform.openai.com/account/api-keys</a>.</p>
    <p>More information about ChatGPT models and their capability can be found at <a href="https://platform.openai.com/docs/models/overview" taget="_blank">https://platform.openai.com/docs/models/overview</a>.</p>
    <p>Enter your ChatGPT API key below and select the OpenAI model of your choice.</p>
    <?php
}

// API key field callback
function chatbot_chatgpt_api_key_callback($args) {
    $api_key = esc_attr(get_option('chatgpt_api_key'));
    ?>
    <input type="text" id="chatgpt_api_key" name="chatgpt_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text">
    <?php
}

// Model choice
function chatbot_chatgpt_model_choice_callback($args) {
    // Get the saved chatgpt_model_choice value or default to "gpt-3.5-turbo"
    $model_choice = esc_attr(get_option('chatgpt_model_choice', 'gpt-3.5-turbo'));
    ?>
    <select id="chatgpt_model_choice" name="chatgpt_model_choice">
        <!-- <option value="<?php echo esc_attr( 'gpt-4' ); ?>" <?php selected( $model_choice, 'gpt-4' ); ?>><?php echo esc_html( 'gpt-4' ); ?></option> -->
        <option value="<?php echo esc_attr( 'gpt-3.5-turbo' ); ?>" <?php selected( $model_choice, 'gpt-3.5-turbo' ); ?>><?php echo esc_html( 'gpt-3.5-turbo' ); ?></option>
    </select>
    <?php
}

// 
function chatbot_chatgpt_bot_name_callback($args) {
    $bot_name = esc_attr(get_option('chatgpt_bot_name', 'Chatbot ChatGPT'));
    ?>
    <input type="text" id="chatgpt_bot_name" name="chatgpt_bot_name" value="<?php echo esc_attr( $bot_name ); ?>" class="regular-text">
    <?php
}

function chatbot_chatgpt_start_status_callback($args) {
    $start_status = esc_attr(get_option('chatgpt_start_status', 'closed'));
    ?>
    <select id="chatgpt_start_status" name="chatgpt_start_status">
        <option value="open" <?php selected( $start_status, 'open' ); ?>>Open</option>
        <option value="closed" <?php selected( $start_status, 'closed' ); ?>>Closed</option>
    </select>
    <?php
}

function chatbot_chatgpt_initial_greeting_callback($args) {
    $initial_greeting = esc_attr(get_option('chatgpt_initial_greeting', 'Hello! How can I help you today?'));
    ?>
    <textarea id="chatgpt_initial_greeting" name="chatgpt_initial_greeting" rows="2" cols="50"><?php echo esc_textarea( $initial_greeting ); ?></textarea>
    <?php
}

function chatbot_chatgpt_subsequent_greeting_callback($args) {
    $subsequent_greeting = esc_attr(get_option('chatgpt_subsequent_greeting', 'Hello again! How can I help you?'));
    ?>
    <textarea id="chatgpt_subsequent_greeting" name="chatgpt_subsequent_greeting" rows="2" cols="50"><?php echo esc_textarea( $subsequent_greeting ); ?></textarea>
    <?php
}