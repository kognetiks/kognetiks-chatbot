<?php
/**
 * Chatbot ChatGPT for WordPress - Settings - Diagnostics
 *
 * This file contains the code for the Chatbot ChatGPT settings page.
 * It allows users to configure the API key and other parameters
 * required to access the ChatGPT API from their own account.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) )
die;

// Diagnostics settings section callback - Ver 1.6.5
function chatbot_chatgpt_diagnostics_section_callback($args) {
    ?>
    <p>The Diagnostics tab checks the API status and set options for diagostics and notices.</p>
    <p>You can turn on/off console and error logging (as of Version 1.6.5 most if now commented out).</p>
    <p>You can also suppress attribution ('Chatbot & Knowledge Navigator by Kognetiks') and notices by setting the value to 'On' (suppress) or 'Off' (no suppression).</p>
    <p>Other settings:</p>
    <?php
    // Get PHP version
    $php_version = phpversion();

    // Get WordPress version
    global $wp_version;

    echo '<p>PHP Version: <b>' . $php_version . '</b><br>';
    echo 'WordPress Version: <b>' . $wp_version . '</b><br>';
    echo 'Chatbot ChatGPT Version: <b>' . esc_attr(get_option('chatbot_chatgpt_plugin_version')) . '</b></p>';
}

// Call the api-test.php file to test the API
function chatbot_chatgpt_api_test_callback($args) {
    $api_key = get_option('chatgpt_api_key');
    test_chatgpt_api($api_key);
    $updated_status = esc_attr(get_option('chatbot_chatgpt_api_status', 'NOT SET'));
    ?>
    <p>API STATUS: <?php echo esc_html( $updated_status ); ?></p>
    <?php
}

// Diagnostics On/Off - Ver 1.6.5
function chatbot_chatgpt_diagnostics_setting_callback($args) {
    global $chatbot_chatgpt_diagnostics;
    $chatbot_chatgpt_diagnostics = esc_attr(get_option('chatbot_chatgpt_diagnostics', 'Off'));
    ?>
    <select id="chatgpt_diagnostics_setting" name = "chatbot_chatgpt_diagnostics">
        <option value="On" <?php selected( $chatbot_chatgpt_diagnostics, 'On' ); ?>><?php echo esc_html( 'On' ); ?></option>
        <option value="Off" <?php selected( $chatbot_chatgpt_diagnostics, 'Off' ); ?>><?php echo esc_html( 'Off' ); ?></option>
    </select>
    <?php
}

// Suppress Notices On/Off - Ver 1.6.5
function chatbot_chatgpt_suppress_notices_callback($args) {
    global $chatbot_chatgpt_suppress_notices;
    $chatbot_chatgpt_suppress_notices = esc_attr(get_option('chatbot_chatgpt_suppress_notices', 'Off'));
    ?>
    <select id="chatgpt_suppress_notices_setting" name = "chatbot_chatgpt_suppress_notices">
        <option value="On" <?php selected( $chatbot_chatgpt_suppress_notices, 'On' ); ?>><?php echo esc_html( 'On' ); ?></option>
        <option value="Off" <?php selected( $chatbot_chatgpt_suppress_notices, 'Off' ); ?>><?php echo esc_html( 'Off' ); ?></option>
    </select>
    <?php
}

// Suppress Attribution On/Off - Ver 1.6.5
function chatbot_chatgpt_suppress_attribution_callback($args) {
    global $chatbot_chatgpt_suppress_attribution;
    $chatbot_chatgpt_suppress_attribution = esc_attr(get_option('chatbot_chatgpt_suppress_attribution', 'Off'));
    ?>
    <select id="chatgpt_suppress_attribution_setting" name = "chatbot_chatgpt_suppress_attribution">
        <option value="On" <?php selected( $chatbot_chatgpt_suppress_attribution, 'On' ); ?>><?php echo esc_html( 'On' ); ?></option>
        <option value="Off" <?php selected( $chatbot_chatgpt_suppress_attribution, 'Off' ); ?>><?php echo esc_html( 'Off' ); ?></option>
    </select>
    <?php
}
