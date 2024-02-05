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

// Set the chatbot text color
function chatbot_chatgpt_appearance_width_wide_callback(): void {
    // Get the value of the setting we've registered with register_setting()
    $chatbot_chatgpt_appearance_width_wide = esc_attr(get_option('chatbot_chatgpt_appearance_width_wide', '500px'));
    ?>
    <input type="text" id="chatbot_chatgpt_appearance_width_wide"
        name="chatbot_chatgpt_appearance_width_wide"
        value="<?php echo esc_attr($chatbot_chatgpt_appearance_width_wide); ?>"
    />
    <?php
    // check to make sure that the value ends with either 'px' or '%'
    if (substr($chatbot_chatgpt_appearance_width_wide, -2) !== 'px' && substr($chatbot_chatgpt_appearance_width_wide, -1) !== '%') {
        chatbot_chatgpt_general_admin_notice( 'Wide width must end with either "px" or "%".');
        update_option('chatbot_chatgpt_appearance_width_wide', '500px');
    } elseif (empty($chatbot_chatgpt_appearance_width_wide)) {
        // Show an error message or handle the error
        chatbot_chatgpt_general_admin_notice( 'Wide width cannot be blank.');
        update_option('chatbot_chatgpt_appearance_width_wide', '500px');
    } else {
        // Save the value
        update_option('chatbot_chatgpt_appearance_width_wide', $chatbot_chatgpt_appearance_width_wide);
    }
}

// Now override the css with the color chosen by the user
function chatbot_chatgpt_appearance_width_wide_custom_css_settings(): void {
    $chatbot_chatgpt_appearance_width_wide = esc_attr(get_option('chatbot_chatgpt_appearance_width_wide', '500px'));
    ?>
    <style>
        .chatbot-wide {
            width: <?php echo $chatbot_chatgpt_appearance_width_wide; ?> !important;
        }
        #chatbot-chatgpt.wide {
            width: <?php echo $chatbot_chatgpt_appearance_width_wide; ?> !important;
        }
    </style>
    <?php
}

// Set the chatbot user text background color
function chatbot_chatgpt_appearance_width_narrow_callback(): void {
    // Get the value of the setting we've registered with register_setting()
    $chatbot_chatgpt_appearance_width_narrow = esc_attr(get_option('chatbot_chatgpt_appearance_width_narrow', '300px'));
    ?>
    <input type="text" id="chatbot_chatgpt_appearance_width_narrow"
        name="chatbot_chatgpt_appearance_width_narrow"
        value="<?php echo esc_attr($chatbot_chatgpt_appearance_width_narrow); ?>"
    />
    <?php
    // check to make sure that the value ends with either 'px' or '%'
    if (substr($chatbot_chatgpt_appearance_width_narrow, -2) !== 'px' && substr($chatbot_chatgpt_appearance_width_narrow, -1) !== '%') {
        chatbot_chatgpt_general_admin_notice( 'Narrow width must end with either "px" or "%".');
        update_option('chatbot_chatgpt_appearance_width_narrow', '300px');
    } elseif(empty($chatbot_chatgpt_appearance_width_narrow)) {
        // Show an error message or handle the error
        chatbot_chatgpt_general_admin_notice( 'Narrow width cannot be blank.');
        update_option('chatbot_chatgpt_appearance_width_narrow', '300px');
    } else {
        // Save the value
        update_option('chatbot_chatgpt_appearance_width_narrow', $chatbot_chatgpt_appearance_width_narrow);
    }
}

// Now override the css with the color chosen by the user
function chatbot_chatgpt_appearance_width_narrow_custom_css_settings(): void {
    $chatbot_chatgpt_appearance_width_narrow = esc_attr(get_option('chatbot_chatgpt_appearance_width_narrow', '300px'));
    ?>
    <style>
        .chatbot-narrow {
            width: <?php echo $chatbot_chatgpt_appearance_width_narrow; ?> !important;
        }
        #chatbot-chatgpt.narrow {
            width: <?php echo $chatbot_chatgpt_appearance_width_narrow; ?> !important;
        }
    </style>
    <?php
}
