<?php
/**
 * Kognetiks Chatbot for WordPress - Settings - Premium Page
 *
 * This file contains the code for the Chatbot settings page.
 * It handles the premium settings and other parameters.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

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
