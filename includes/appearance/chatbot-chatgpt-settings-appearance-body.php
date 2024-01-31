<?php
/**
 * Chatbot ChatGPT for WordPress - Settings - Appearance - Ver 1.8.1
 *
 * This file contains the code for the Chatbot ChatGPT settings page.
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
    ?>
    <style>
        .chatbot-bubble {
            background-color: <?php echo $chatbot_chatgpt_appearance_background_color; ?> !important;
        }
        .floating-style {
            background-color: <?php echo $chatbot_chatgpt_appearance_background_color; ?> !important;
        }
        .embedded-style {
            background-color: <?php echo $chatbot_chatgpt_appearance_background_color; ?> !important;
        }
        #chatbot-chatgpt-submit {
            background-color: <?php echo $chatbot_chatgpt_appearance_background_color; ?> !important;
        }
        #chatbot-chatgpt-upload-file {
            background-color: <?php echo $chatbot_chatgpt_appearance_background_color; ?> !important;
        }
    </style>
    <?php
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
    ?>
    <style>
        #chatbot-chatgpt-header {
            background-color: <?php echo $chatbot_chatgpt_appearance_header_background_color; ?> !important;
        }
    </style>
    <?php
}
add_action('wp_head', 'chatbot_chatgpt_appearance_header_background_custom_css_settings');
