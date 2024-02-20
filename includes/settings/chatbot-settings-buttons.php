<?php
/**
 * Kognetiks Chatbot for WordPress - Settings - Custom Buttons
 * This file contains the code for the Chatbot settings page.
 * It allows users to configure the custom buttons.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Custom buttons settings section callback - Ver 1.6.5
function chatbot_chatgpt_custom_button_section_callback($args) {
    ?>
    <p>Enter the names and links for your custom buttons below.</p>
    <p>Set Custom Buttons to 'On' to display one or both custom buttons.</p>
    <p>Set Custom Buttons to 'Off' to hide both custom buttons.</p>
    <?php
}

// Custom buttons on/off settings field callback - Ver 1.6.5
function chatbot_chatgpt_enable_custom_buttons_callback($args) {
    $chatbot_chatgpt_enable_custom_buttons = esc_attr(get_option('chatbot_chatgpt_enable_custom_buttons', 'Off'));
    ?>
    <select id="chatbot_chatgpt_enable_custom_buttons" name="chatbot_chatgpt_enable_custom_buttons">
        <option value="On" <?php selected( $chatbot_chatgpt_enable_custom_buttons, 'On' ); ?>><?php echo esc_html( 'On' ); ?></option>
        <option value="Off" <?php selected( $chatbot_chatgpt_enable_custom_buttons, 'Off' ); ?>><?php echo esc_html( 'Off' ); ?></option>
    </select>
    <?php
}

// Custom buttons settings fields callback - Ver 1.6.5
function chatbot_chatgpt_custom_button_name_1_callback($args) {
    $chatbot_chatgpt_custom_button_name_1 = get_option('chatbot_chatgpt_custom_button_name_1');
    $value = isset($chatbot_chatgpt_custom_button_name_1) ? sanitize_text_field($chatbot_chatgpt_custom_button_name_1) : '';
    ?>
    <input type="text" id="chatbot_chatgpt_custom_button_name_1" name="chatbot_chatgpt_custom_button_name_1" value="<?php echo esc_attr($value); ?>" />
    <?php
}

function chatbot_chatgpt_custom_button_link_1_callback($args) {
    $chatbot_chatgpt_custom_button_url_1 = get_option('chatbot_chatgpt_custom_button_url_1');
    $value = isset($chatbot_chatgpt_custom_button_url_1) ? esc_url($chatbot_chatgpt_custom_button_url_1) : '';
    ?>
    <input type="text" id="chatbot_chatgpt_custom_button_url_1" name="chatbot_chatgpt_custom_button_url_1" value="<?php echo esc_attr($value); ?>" style="width: 400px;" />
    <?php
}

function chatbot_chatgpt_custom_button_name_2_callback($args) {
    $chatbot_chatgpt_custom_button_name_2 = get_option('chatbot_chatgpt_custom_button_name_2');
    $value = isset($chatbot_chatgpt_custom_button_name_2) ? sanitize_text_field($chatbot_chatgpt_custom_button_name_2) : '';
    ?>
    <input type="text" id="chatbot_chatgpt_custom_button_name_2" name="chatbot_chatgpt_custom_button_name_2" value="<?php echo esc_attr($value); ?>" />
    <?php
}

function chatbot_chatgpt_custom_button_link_2_callback($args) {
    $chatbot_chatgpt_custom_button_url_2 = get_option('chatbot_chatgpt_custom_button_url_2');
    $value = isset($chatbot_chatgpt_custom_button_url_2) ? esc_url($chatbot_chatgpt_custom_button_url_2) : '';
    ?>
    <input type="text" id="chatbot_chatgpt_custom_button_url_2" name="chatbot_chatgpt_custom_button_url_2" value="<?php echo esc_attr($value); ?>" style="width: 400px;" />
    <?php
}