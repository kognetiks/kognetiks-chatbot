<?php
/**
 * Kognetiks Chatbot for WordPress - Settings - Appearance - Ver 1.8.6
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

// User CSS Settings
function chatbot_chatgpt_appearance_user_css_setting_callback(): void {
    // Get the value of the setting we've registered with register_setting()
    $chatbot_chatgpt_appearance_user_css_setting = esc_attr(get_option('chatbot_chatgpt_appearance_user_css_setting', ''));
    ?>
    <textarea id="chatbot_chatgpt_appearance_user_css_setting"
        name="chatbot_chatgpt_appearance_user_css_setting"
        class="medium-text"
        rows="10"
        cols="50"
        ><?php echo esc_attr($chatbot_chatgpt_appearance_user_css_setting); ?>
    </textarea>
    <?php
    update_option('chatbot_chatgpt_appearance_user_css_setting', $chatbot_chatgpt_appearance_user_css_setting);
}