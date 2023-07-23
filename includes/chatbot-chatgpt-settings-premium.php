<?php
/**
 * Chatbot ChatGPT for WordPress - Settings - Premium Page
 *
 * This file contains the code for the Chatbot ChatGPT settings page.
 * It allows users to configure the API key and other parameters
 * required to access the ChatGPT API from their own account.
 *
 * @package chatbot-chatgpt
 */

// Premium settings section callback - Ver 1.3.0
function chatbot_chatgpt_premium_section_callback($args) {
    ?>
    <p>Enter your premium key here.</p>
    <?php
}

// Premium Key - Ver 1.3.0
function chatbot_chatgpt_premium_key_callback($args) {
    $premium_key = esc_attr(get_option('chatgpt_premium_key'));
    ?>
    <input type="text" id="chatgpt_premium_key" name="chatgpt_premium_key" value="<?php echo esc_attr( $premium_key ); ?>" class="regular-text">
    <?php
}
