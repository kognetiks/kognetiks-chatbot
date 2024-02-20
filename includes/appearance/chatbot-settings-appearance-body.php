<?php
/**
 * Kognetiks Chatbot for WordPress - Settings - Appearance - Ver 1.8.1
 *
 * This file contains the code for the Chatbot settings page.
 * It handles the appearance settings and other parameters.
 * 
 *
 * @package chatbot-chatgpt
 */

// IDEA - COMING SOON - Ver 1.6.8

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Set the chatbot background color
function chatbot_chatgpt_appearance_background_color_callback(): void {
    // Get the value of the setting we've registered with register_setting()
    $chatbot_chatgpt_appearance_background_color = esc_attr(get_option('chatbot_chatgpt_appearance_background_color', '#f1f1f1'));
    ?>
    <input type="text" id="chatbot_chatgpt_appearance_background_color"
        name="chatbot_chatgpt_appearance_background_color"
        value="<?php echo esc_attr($chatbot_chatgpt_appearance_background_color); ?>"
        class="my-color-field"
    />
    <?php
    if(empty($chatbot_chatgpt_appearance_background_color)) {
        // Show an error message or handle the error
        chatbot_chatgpt_general_admin_notice( 'Background color cannot be blank.');
        update_option('chatbot_chatgpt_appearance_background_color', '#f1f1f1');
    } else {
        // Save the value
        update_option('chatbot_chatgpt_appearance_background_color', $chatbot_chatgpt_appearance_background_color);
    }
}

// Now override the css with the color chosen by the user
function chatbot_chatgpt_appearance_background_custom_css_settings(): void {
    $chatbot_chatgpt_appearance_background_color = esc_attr(get_option('chatbot_chatgpt_appearance_background_color', '#f1f1f1'));
    $chatbot_chatgpt_appearance_greeting_text_color = esc_attr(get_option('chatbot_chatgpt_appearance_greeting_text_color', '#ffffff'));

    // Define CSS styles as global variables
    $GLOBALS['chatbotChatGPTAppearanceCSS']['chatbot-bubble'] = ".chatbot-bubble { background-color: {$chatbot_chatgpt_appearance_background_color} !important; color: {$chatbot_chatgpt_appearance_greeting_text_color} !important;";
    $GLOBALS['chatbotChatGPTAppearanceCSS']['floating-style'] = ".floating-style { background-color: {$chatbot_chatgpt_appearance_background_color} !important; }";
    $GLOBALS['chatbotChatGPTAppearanceCSS']['embedded-style'] = ".embedded-style { background-color: {$chatbot_chatgpt_appearance_background_color} !important; }";
    $GLOBALS['chatbotChatGPTAppearanceCSS']['chatbot-chatgpt-submit'] = "#chatbot-chatgpt-submit { background-color: {$chatbot_chatgpt_appearance_background_color} !important; }";
    $GLOBALS['chatbotChatGPTAppearanceCSS']['chatbot-upload-file'] = "#chatbot-chatgpt-upload-file { background-color: {$chatbot_chatgpt_appearance_background_color} !important; }";
    $GLOBALS['chatbotChatGPTAppearanceCSS']['chatbot-erase-conversation'] = "#chatbot-chatgpt-erase-btn { background-color: {$chatbot_chatgpt_appearance_background_color} !important; }";
    
}
add_action('wp_head', 'chatbot_chatgpt_appearance_background_custom_css_settings');

// Set the chatbot background color
function chatbot_chatgpt_appearance_header_background_color_callback(): void {
    // Get the value of the setting we've registered with register_setting()
    $chatbot_chatgpt_appearance_header_background_color = esc_attr(get_option('chatbot_chatgpt_appearance_header_background_color', '#222222'));
    ?>
    <input type="text" id="chatbot_chatgpt_appearance_header_background_color"
        name="chatbot_chatgpt_appearance_header_background_color"
        value="<?php echo esc_attr($chatbot_chatgpt_appearance_header_background_color); ?>"
        class="my-color-field"
    />
    <?php
    if(empty($chatbot_chatgpt_appearance_header_background_color)) {
        // Show an error message or handle the error
        chatbot_chatgpt_general_admin_notice( 'Header background color cannot be blank.');
        update_option('chatbot_chatgpt_appearance_header_background_color', '#222222');
    } else {
        // Save the value
        update_option('chatbot_chatgpt_appearance_header_background_color', $chatbot_chatgpt_appearance_header_background_color);
    }
}

// Now override the css with the color chosen by the user
function chatbot_chatgpt_appearance_header_background_custom_css_settings(): void {
    $chatbot_chatgpt_appearance_header_background_color = esc_attr(get_option('chatbot_chatgpt_appearance_header_background_color', '#222222'));

    // Define CSS styles as global variables
    $GLOBALS['chatbotChatGPTAppearanceCSS']['chatbot-chatgpt-header'] = "#chatbot-chatgpt-header { background-color: {$chatbot_chatgpt_appearance_header_background_color} !important; }";

}
add_action('wp_head', 'chatbot_chatgpt_appearance_header_background_custom_css_settings');

// Set the chatbot width
function chatbot_chatgpt_width_setting_callback($args) {
    $chatgpt_width = esc_attr(get_option('chatbot_chatgpt_width_setting', 'Narrow'));
    ?>
    <select id="chatbot_chatgpt_width_setting" name = "chatbot_chatgpt_width_setting">
        <option value="Narrow" <?php selected( $chatgpt_width, 'Narrow' ); ?>><?php echo esc_html( 'Narrow' ); ?></option>
        <option value="Wide" <?php selected( $chatgpt_width, 'Wide' ); ?>><?php echo esc_html( 'Wide' ); ?></option>
    </select>
    <?php

    update_option ('chatbot_chatgpt_width_setting', $chatgpt_width);
    
    $chatbot_chatgpt_appearance_width_narrow = esc_attr(get_option('chatbot_chatgpt_appearance_width_narrow', '300px'));

    // Update the $GLOBALS for wide and narrow
    if ($chatgpt_width == 'Wide') {
        chatbot_chatgpt_appearance_width_wide_custom_css_settings();
    } else {
        chatbot_chatgpt_appearance_width_narrow_custom_css_settings();
    }

}