<?php
/**
 * ChatGPT Chatbot for WordPress - Settings Page
 *
 * This file contains the code for the ChatGPT Chatbot settings page.
 * It allows users to configure the API key and other parameters
 * required to access the ChatGPT API from their own account.
 *
 * @package chatgpt-chatbot
 */

function chatgpt_chatbot_settings_page() {
    add_options_page('ChatGPT Chatbot Settings', 'ChatGPT Chatbot', 'manage_options', 'chatgpt-chatbot', 'chatgpt_chatbot_settings_page_html');
}
add_action('admin_menu', 'chatgpt_chatbot_settings_page');

// Settings page HTML
function chatgpt_chatbot_settings_page_html() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_GET['settings-updated'])) {
        add_settings_error('chatgpt_chatbot_messages', 'chatgpt_chatbot_message', 'Settings Saved', 'updated');
    }

    settings_errors('chatgpt_chatbot_messages');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('chatgpt_chatbot');
            do_settings_sections('chatgpt_chatbot');
            submit_button('Save Settings');
            ?>
        </form>
    </div>
    <?php
}

// Register settings
function chatgpt_chatbot_settings_init() {
    register_setting('chatgpt_chatbot', 'chatgpt_api_key');

    add_settings_section(
        'chatgpt_chatbot_section',
        'API Settings',
        'chatgpt_chatbot_section_callback',
        'chatgpt_chatbot'
    );

    add_settings_field(
        'chatgpt_api_key',
        'ChatGPT API Key',
        'chatgpt_chatbot_api_key_callback',
        'chatgpt_chatbot',
        'chatgpt_chatbot_section'
    );
}
add_action('admin_init', 'chatgpt_chatbot_settings_init');

// Settings section callback
function chatgpt_chatbot_section_callback($args) {
    ?>
    <p>This plugin requires an API key from OpenAI to function. You can obtain an API key by signing up at <a href="https://platform.openai.com/account/api-keys" target="_blank">https://platform.openai.com/account/api-keys</a>.</p>
    <p>Enter your ChatGPT API key below:</p>
    <?php
}

// API key field callback
function chatgpt_chatbot_api_key_callback($args) {
    $api_key = esc_attr(get_option('chatgpt_api_key'));
    ?>
    <input type="text" id="chatgpt_api_key" name="chatgpt_api_key" value="<?php echo $api_key; ?>" class="regular-text">
    <?php
}
