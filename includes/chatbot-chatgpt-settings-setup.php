<?php
/**
 * Chatbot ChatGPT for WordPress - Settings - Setup Page
 *
 * This file contains the code for the Chatbot ChatGPT settings page.
 * It allows users to configure the API key and other parameters
 * required to access the ChatGPT API from their own account.
 *
 * @package chatbot-chatgpt
 */

// TODO If this file is called directly, abort.
if ( ! defined( 'WPINC' ) )
die;

// Settings section callback - Ver 1.3.0
function chatbot_chatgpt_settings_section_callback($args) {
    ?>
    <p>Configure settings for the Chatbot ChatGPT plugin, including the bot name, start status, and greetings.</p>
    <?php
}

// Chatbot ChatGPT Name
function chatbot_chatgpt_bot_name_callback($args) {
    $bot_name = esc_attr(get_option('chatgpt_bot_name', 'Chatbot ChatGPT'));
    ?>
    <input type="text" id="chatgpt_bot_name" name="chatgpt_bot_name" value="<?php echo esc_attr( $bot_name ); ?>" class="regular-text">
    <?php
}

function chatbot_chatgptStartStatus_callback($args) {
    $start_status = esc_attr(get_option('chatgptStartStatus', 'closed'));
    ?>
    <select id="chatgptStartStatus" name="chatgptStartStatus">
        <option value="open" <?php selected( $start_status, 'open' ); ?>><?php echo esc_html( 'Open' ); ?></option>
        <option value="closed" <?php selected( $start_status, 'closed' ); ?>><?php echo esc_html( 'Closed' ); ?></option>
    </select>
    <?php
}

function chatbot_chatgptStartStatusNewVisitor_callback($args) {
    $start_status = esc_attr(get_option('chatgptStartStatusNewVisitor', 'closed'));
    ?>
    <select id="chatgptStartStatusNewVisitor" name="chatgptStartStatusNewVisitor">
        <option value="open" <?php selected( $start_status, 'open' ); ?>><?php echo esc_html( 'Open' ); ?></option>
        <option value="closed" <?php selected( $start_status, 'closed' ); ?>><?php echo esc_html( 'Closed' ); ?></option>
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

// Option to remove OpenAI disclaimer - Ver 1.4.1
function chatgpt_disclaimer_setting_callback($args) {
    $chatgpt_disclaimer_setting = esc_attr(get_option('chatgpt_disclaimer_setting', 'Yes'));
    ?>
    <select id="chatgpt_disclaimer_setting" name="chatgpt_disclaimer_setting">
        <option value="Yes" <?php selected( $chatgpt_disclaimer_setting, 'Yes' ); ?>><?php echo esc_html( 'Yes' ); ?></option>
        <option value="No" <?php selected( $chatgpt_disclaimer_setting, 'No' ); ?>><?php echo esc_html( 'No' ); ?></option>
    </select>
    <?php    
}

// Option for narrow or wide chatbot - Ver 1.4.2
function chatgpt_width_setting_callback($args) {
    $chatgpt_width = esc_attr(get_option('chatgpt_width_setting', 'Narrow'));
    ?>
    <select id="chatgpt_width_setting" name = "chatgpt_width_setting">
        <option value="Narrow" <?php selected( $chatgpt_width, 'Narrow' ); ?>><?php echo esc_html( 'Narrow' ); ?></option>
        <option value="Wide" <?php selected( $chatgpt_width, 'Wide' ); ?>><?php echo esc_html( 'Wide' ); ?></option>
    </select>
    <?php
}

// Diagnostics On/Off - Ver 1.5.0
function chatgpt_diagnostics_setting_callback($args) {
    global $chatgpt_diagnostics;
    $chatgpt_diagnostics = esc_attr(get_option('chatgpt_diagnostics', 'Off'));
    ?>
    <select id="chatgpt_diagnostics_setting" name = "chatgpt_diagnostics">
        <option value="On" <?php selected( $chatgpt_diagnostics, 'On' ); ?>><?php echo esc_html( 'On' ); ?></option>
        <option value="Off" <?php selected( $chatgpt_diagnostics, 'Off' ); ?>><?php echo esc_html( 'Off' ); ?></option>
    </select>
    <?php
}
