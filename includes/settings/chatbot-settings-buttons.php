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
    die();
}

// Register Diagnostics settings - Ver 2.0.7
function chatbot_chatgpt_button_settings_init() {

    // Custom Buttons settings tab - Ver 1.6.5
    register_setting('chatbot_chatgpt_custom_buttons', 'chatbot_chatgpt_enable_custom_buttons');
    register_setting('chatbot_chatgpt_custom_buttons', 'chatbot_chatgpt_custom_button_name_1');
    register_setting('chatbot_chatgpt_custom_buttons', 'chatbot_chatgpt_custom_button_url_1');
    register_setting('chatbot_chatgpt_custom_buttons', 'chatbot_chatgpt_custom_button_name_2');
    register_setting('chatbot_chatgpt_custom_buttons', 'chatbot_chatgpt_custom_button_url_2');
    register_setting('chatbot_chatgpt_custom_buttons', 'chatbot_chatgpt_custom_button_name_3');
    register_setting('chatbot_chatgpt_custom_buttons', 'chatbot_chatgpt_custom_button_url_3');
    register_setting('chatbot_chatgpt_custom_buttons', 'chatbot_chatgpt_custom_button_name_4');
    register_setting('chatbot_chatgpt_custom_buttons', 'chatbot_chatgpt_custom_button_url_4');

    add_settings_section(
        'chatbot_chatgpt_custom_button_overview_section',
        'Custom Buttons Overview',
        'chatbot_chatgpt_custom_button_overview_section_callback',
        'chatbot_chatgpt_custom_buttons_overview'
    );

    add_settings_section(
        'chatbot_chatgpt_custom_button_section',
        'Custom Buttons Settings',
        'chatbot_chatgpt_custom_button_section_callback',
        'chatbot_chatgpt_custom_buttons'
    );

    add_settings_field(
        'chatbot_chatgpt_enable_custom_buttons',
        'Custom Buttons (On/Off)',
        'chatbot_chatgpt_enable_custom_buttons_callback',
        'chatbot_chatgpt_custom_buttons',
        'chatbot_chatgpt_custom_button_section'
    );

    add_settings_field(
        'chatbot_chatgpt_custom_button_name_1',
        'Custom Button 1 Name',
        'chatbot_chatgpt_custom_button_name_1_callback',
        'chatbot_chatgpt_custom_buttons',
        'chatbot_chatgpt_custom_button_section'
    );

    add_settings_field(
        'chatbot_chatgpt_custom_button_url_1',
        'Custom Button 1 Link',
        'chatbot_chatgpt_custom_button_link_1_callback',
        'chatbot_chatgpt_custom_buttons',
        'chatbot_chatgpt_custom_button_section'
    );

    add_settings_field(
        'chatbot_chatgpt_custom_button_name_2',
        'Custom Button 2 Name',
        'chatbot_chatgpt_custom_button_name_2_callback',
        'chatbot_chatgpt_custom_buttons',
        'chatbot_chatgpt_custom_button_section'
    );

    add_settings_field(
        'chatbot_chatgpt_custom_button_url_2',
        'Custom Button 2 Link',
        'chatbot_chatgpt_custom_button_link_2_callback',
        'chatbot_chatgpt_custom_buttons',
        'chatbot_chatgpt_custom_button_section'
    );

    add_settings_field(
        'chatbot_chatgpt_custom_button_name_3',
        'Custom Button 3 Name',
        'chatbot_chatgpt_custom_button_name_3_callback',
        'chatbot_chatgpt_custom_buttons',
        'chatbot_chatgpt_custom_button_section'
    );

    add_settings_field(
        'chatbot_chatgpt_custom_button_url_3',
        'Custom Button 3 Link',
        'chatbot_chatgpt_custom_button_link_3_callback',
        'chatbot_chatgpt_custom_buttons',
        'chatbot_chatgpt_custom_button_section'
    );

    add_settings_field(
        'chatbot_chatgpt_custom_button_name_4',
        'Custom Button 4 Name',
        'chatbot_chatgpt_custom_button_name_4_callback',
        'chatbot_chatgpt_custom_buttons',
        'chatbot_chatgpt_custom_button_section'
    );

    add_settings_field(
        'chatbot_chatgpt_custom_button_url_4',
        'Custom Button 4 Link',
        'chatbot_chatgpt_custom_button_link_4_callback',
        'chatbot_chatgpt_custom_buttons',
        'chatbot_chatgpt_custom_button_section'
    );
    
}
add_action('admin_init', 'chatbot_chatgpt_button_settings_init');

// Custom buttons settings section callback - Ver 1.6.5
function chatbot_chatgpt_custom_button_overview_section_callback($args) {
    ?>
    <p>Enter the names and links for your custom buttons below.</p>
    <p>Set Custom Buttons to <code>Floating Only</code>, <code>Embedded Only</code> or <code>Both</code> to display your custom buttons.</p>
    <p>Set Custom Buttons to <code>Off</code> to hide both custom buttons.</p>
    <p><b><i>Don't forget to click </i><code>Save Settings</code><i> to save any changes your might make.</i></b></p>
    <p style="background-color: #e0f7fa; padding: 10px;"><b>For an explanation on how to use custom Buttons and additional documentation please click <a href="?page=chatbot-chatgpt&tab=support&dir=buttons&file=buttons.md">here</a>.</b></p>
    <?php
}

// Custom buttons settings section callback - Ver 1.6.5
function chatbot_chatgpt_custom_button_section_callback($args) {

    // PLACEHOLDER - VER 2.0.7

}

// Custom buttons on/off settings field callback - Ver 1.6.5 - Updated in Ver 2.0.5
function chatbot_chatgpt_enable_custom_buttons_callback($args) {
    $chatbot_chatgpt_enable_custom_buttons = esc_attr(get_option('chatbot_chatgpt_enable_custom_buttons', 'Off'));
    if ($chatbot_chatgpt_enable_custom_buttons == 'On') {
        $chatbot_chatgpt_enable_custom_buttons = 'Floating Only';
        update_option('chatbot_chatgpt_enable_custom_buttons', 'Floating Only');
    } elseif ($chatbot_chatgpt_enable_custom_buttons == '') {
        $chatbot_chatgpt_enable_custom_buttons = 'Off';
    }
    ?>
    <select id="chatbot_chatgpt_enable_custom_buttons" name="chatbot_chatgpt_enable_custom_buttons">
        <option value="Floating" <?php selected( $chatbot_chatgpt_enable_custom_buttons, 'Floating' ); ?>><?php echo esc_html( 'Floating Only' ); ?></option>
        <option value="Embedded" <?php selected( $chatbot_chatgpt_enable_custom_buttons, 'Embedded' ); ?>><?php echo esc_html( 'Embedded Only' ); ?></option>
        <option value="Both" <?php selected( $chatbot_chatgpt_enable_custom_buttons, 'Both' ); ?>><?php echo esc_html( 'Both' ); ?></option>
        <option value="Off" <?php selected( $chatbot_chatgpt_enable_custom_buttons, 'Off' ); ?>><?php echo esc_html( 'Off' ); ?></option>
    </select>
    <?php
}

// Custom buttons settings fields callback - Ver 1.6.5
function chatbot_chatgpt_custom_button_name_1_callback($args) {
    $chatbot_chatgpt_custom_button_name_1 = esc_attr(get_option('chatbot_chatgpt_custom_button_name_1'));
    $value = isset($chatbot_chatgpt_custom_button_name_1) ? sanitize_text_field($chatbot_chatgpt_custom_button_name_1) : '';
    ?>
    <input type="text" id="chatbot_chatgpt_custom_button_name_1" name="chatbot_chatgpt_custom_button_name_1" value="<?php echo esc_attr($value); ?>" />
    <?php
}

function chatbot_chatgpt_custom_button_link_1_callback($args) {
    $chatbot_chatgpt_custom_button_url_1 = esc_attr(get_option('chatbot_chatgpt_custom_button_url_1'));
    $value = isset($chatbot_chatgpt_custom_button_url_1) ? esc_url($chatbot_chatgpt_custom_button_url_1) : '';
    ?>
    <input type="text" id="chatbot_chatgpt_custom_button_url_1" name="chatbot_chatgpt_custom_button_url_1" value="<?php echo esc_attr($value); ?>" style="width: 400px;" />
    <?php
}

function chatbot_chatgpt_custom_button_name_2_callback($args) {
    $chatbot_chatgpt_custom_button_name_2 = esc_attr(get_option('chatbot_chatgpt_custom_button_name_2'));
    $value = isset($chatbot_chatgpt_custom_button_name_2) ? sanitize_text_field($chatbot_chatgpt_custom_button_name_2) : '';
    ?>
    <input type="text" id="chatbot_chatgpt_custom_button_name_2" name="chatbot_chatgpt_custom_button_name_2" value="<?php echo esc_attr($value); ?>" />
    <?php
}

function chatbot_chatgpt_custom_button_link_2_callback($args) {
    $chatbot_chatgpt_custom_button_url_2 = esc_attr(get_option('chatbot_chatgpt_custom_button_url_2'));
    $value = isset($chatbot_chatgpt_custom_button_url_2) ? esc_url($chatbot_chatgpt_custom_button_url_2) : '';
    ?>
    <input type="text" id="chatbot_chatgpt_custom_button_url_2" name="chatbot_chatgpt_custom_button_url_2" value="<?php echo esc_attr($value); ?>" style="width: 400px;" />
    <?php
}

function chatbot_chatgpt_custom_button_name_3_callback($args) {
    $chatbot_chatgpt_custom_button_name_3 = esc_attr(get_option('chatbot_chatgpt_custom_button_name_3'));
    $value = isset($chatbot_chatgpt_custom_button_name_3) ? sanitize_text_field($chatbot_chatgpt_custom_button_name_3) : '';
    ?>
    <input type="text" id="chatbot_chatgpt_custom_button_name_3" name="chatbot_chatgpt_custom_button_name_3" value="<?php echo esc_attr($value); ?>" />
    <?php
}

function chatbot_chatgpt_custom_button_link_3_callback($args) {
    $chatbot_chatgpt_custom_button_url_3 = esc_attr(get_option('chatbot_chatgpt_custom_button_url_3'));
    $value = isset($chatbot_chatgpt_custom_button_url_3) ? esc_url($chatbot_chatgpt_custom_button_url_3) : '';
    ?>
    <input type="text" id="chatbot_chatgpt_custom_button_url_3" name="chatbot_chatgpt_custom_button_url_3" value="<?php echo esc_attr($value); ?>" style="width: 400px;" />
    <?php
}

function chatbot_chatgpt_custom_button_name_4_callback($args) {
    $chatbot_chatgpt_custom_button_name_4 = esc_attr(get_option('chatbot_chatgpt_custom_button_name_4'));
    $value = isset($chatbot_chatgpt_custom_button_name_4) ? sanitize_text_field($chatbot_chatgpt_custom_button_name_4) : '';
    ?>
    <input type="text" id="chatbot_chatgpt_custom_button_name_4" name="chatbot_chatgpt_custom_button_name_4" value="<?php echo esc_attr($value); ?>" />
    <?php
}

function chatbot_chatgpt_custom_button_link_4_callback($args) {
    $chatbot_chatgpt_custom_button_url_4 = esc_attr(get_option('chatbot_chatgpt_custom_button_url_4'));
    $value = isset($chatbot_chatgpt_custom_button_url_4) ? esc_url($chatbot_chatgpt_custom_button_url_4) : '';
    ?>
    <input type="text" id="chatbot_chatgpt_custom_button_url_4" name="chatbot_chatgpt_custom_button_url_4" value="<?php echo esc_attr($value); ?>" style="width: 400px;" />
    <?php
}
