<?php
/**
 * Kognetiks Chatbot for WordPress - Settings - Setup Page
 *
 * This file contains the code for the Chatbot settings page.
 * It handles the setup settings and other parameters.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Settings section callback - Ver 1.3.0
function chatbot_chatgpt_settings_section_callback($args) {
    ?>
    <p>Configure settings for the Chatbot plugin, including the bot name, start status, and greetings.</p>
    <?php
}

// Chatbot Name
function chatbot_chatgpt_bot_name_callback($args): void {
    $bot_name = esc_attr(get_option('chatbot_chatgpt_bot_name', 'Kognetiks Chatbot'));
    ?>
    <input type="text" id="chatbot_chatgpt_bot_name" name="chatbot_chatgpt_bot_name" value="<?php echo esc_attr( $bot_name ); ?>" class="regular-text">
    <?php
}

function chatbot_chatgptStartStatus_callback($args) {
    $start_status = esc_attr(get_option('chatbot_chatgpt_start_status', 'closed'));
    ?>
    <select id="chatbot_chatgpt_start_status" name="chatbot_chatgpt_start_status">
        <option value="open" <?php selected( $start_status, 'open' ); ?>><?php echo esc_html( 'Open' ); ?></option>
        <option value="closed" <?php selected( $start_status, 'closed' ); ?>><?php echo esc_html( 'Closed' ); ?></option>
    </select>
    <?php
}

function chatbot_chatbot_chatgpt_start_status_new_visitor_callback($args) {
    $start_status = esc_attr(get_option('chatbot_chatgpt_start_status_new_visitor', 'closed'));
    ?>
    <select id="chatbot_chatgpt_start_status_new_visitor" name="chatbot_chatgpt_start_status_new_visitor">
        <option value="open" <?php selected( $start_status, 'open' ); ?>><?php echo esc_html( 'Open' ); ?></option>
        <option value="closed" <?php selected( $start_status, 'closed' ); ?>><?php echo esc_html( 'Closed' ); ?></option>
    </select>
    <?php
}

// Added in Ver 1.6.6
function chatbot_chatgpt_bot_prompt_callback($args) {
    $chatbot_chatgpt_bot_prompt = esc_attr(get_option('chatbot_chatgpt_bot_prompt', 'Enter your question ...'));
    ?>
    <input type="text" id="chatbot_chatgpt_bot_prompt" name="chatbot_chatgpt_bot_prompt" value="<?php echo esc_attr( $chatbot_chatgpt_bot_prompt ); ?>" class="regular-text">
    <?php
}

function chatbot_chatgpt_initial_greeting_callback($args) {
    $initial_greeting = esc_attr(get_option('chatbot_chatgpt_initial_greeting', 'Hello! How can I help you today?'));
    ?>
    <textarea id="chatbot_chatgpt_initial_greeting" name="chatbot_chatgpt_initial_greeting" rows="2" cols="50"><?php echo esc_textarea( $initial_greeting ); ?></textarea>
    <?php
}

function chatbot_chatgpt_subsequent_greeting_callback($args) {
    $subsequent_greeting = esc_attr(get_option('chatbot_chatgpt_subsequent_greeting', 'Hello again! How can I help you?'));
    ?>
    <textarea id="chatbot_chatgpt_subsequent_greeting" name="chatbot_chatgpt_subsequent_greeting" rows="2" cols="50"><?php echo esc_textarea( $subsequent_greeting ); ?></textarea>
    <?php
}

// Option to remove OpenAI disclaimer - Ver 1.4.1
function chatgpt_disclaimer_setting_callback($args) {
    $chatbot_chatgpt_disclaimer_setting = esc_attr(get_option('chatbot_chatgpt_disclaimer_setting', 'Yes'));
    ?>
    <select id="chatbot_chatgpt_disclaimer_setting" name="chatbot_chatgpt_disclaimer_setting">
        <option value="Yes" <?php selected( $chatbot_chatgpt_disclaimer_setting, 'Yes' ); ?>><?php echo esc_html( 'Yes' ); ?></option>
        <option value="No" <?php selected( $chatbot_chatgpt_disclaimer_setting, 'No' ); ?>><?php echo esc_html( 'No' ); ?></option>
    </select>
    <?php    
}

// Audience Choice - Ver 1.9.0
function chatbot_chatgpt_audience_choice_callback($args) {
    $chatbot_chatgpt_audience_choice = esc_attr(get_option('chatbot_chatgpt_audience_choice', 'all'));
    ?>
    <select id="chatbot_chatgpt_audience_choice" name="chatbot_chatgpt_audience_choice">
        <option value="all" <?php selected( $chatbot_chatgpt_audience_choice, 'all' ); ?>><?php echo esc_html( 'All Audiences' ); ?></option>
        <option value="logged-in" <?php selected( $chatbot_chatgpt_audience_choice, 'logged-in' ); ?>><?php echo esc_html( 'Logged-in Only' ); ?></option>
        <option value="visitors" <?php selected( $chatbot_chatgpt_audience_choice, 'visitors' ); ?>><?php echo esc_html( 'Visitors Only' ); ?></option>
    </select>
    <?php
}
