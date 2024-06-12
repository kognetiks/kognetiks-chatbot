<?php
/**
 * Kognetiks Chatbot for WordPress - Settings - Diagnostics
 *
 * This file contains the code for the Chatbot settings page.
 * It allows users to configure the reporting and other parameters
 * required to access the ChatGPT API from their own account.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Diagnostics settings section callback - Ver 1.6.5
function chatbot_chatgpt_diagnostics_section_callback($args) {
    ?>
    <p>The Diagnostics tab checks the API status and set options for diagnostics and notices.</p>
    <p>You can turn on/off console and error logging (as of Version 1.6.5 most are now commented out).</p>
    <!-- <p>You can also suppress attribution ('Chatbot & Knowledge Navigator by Kognetiks') and notices by setting the value to 'On' (suppress) or 'Off' (no suppression).</p> -->
    <p>You can also suppress attribution ('Chatbot WordPress plugin by Kognetiks') and notices by setting the value to 'On' (suppress) or 'Off' (no suppression).</p>
    <p style="background-color: #e0f7fa; padding: 10px;"><b>For an explanation on how to use the diagnostics, messages, and additional documentation please click <a href="?page=chatbot-chatgpt&tab=support&dir=messages&file=messages.md">here</a>.</b></p>
    <h2>System and Plugin Information</h2>
    <?php
    // Get PHP version
    $php_version = phpversion();

    // Get WordPress version
    global $wp_version;

    echo '<p>PHP Version: <b>' . $php_version . '</b><br>';
    echo 'WordPress Version: <b>' . $wp_version . '</b><br>';
    echo 'Chatbot Version: <b>' . kchat_get_plugin_version() . '</b><br>';
    echo 'WordPress Language Code: <b>' . get_locale() . '</b></p>';
    echo '<h2>API Status and Other Settings</h2>';
}

// Call the api-test.php file to test the API
function chatbot_chatgpt_api_test_callback($args) {
    $api_key = get_option('chatbot_chatgpt_api_key');
    test_chatgpt_api($api_key);
    $updated_status = esc_attr(get_option('chatbot_chatgpt_api_status', 'NOT SET'));
    ?>
    <p>API STATUS: <b><?php echo esc_html( $updated_status ); ?></b></p>
    <?php
}

// Diagnostics On/Off - Ver 1.6.5
function chatbot_chatgpt_diagnostics_setting_callback($args) {
    $chatbot_chatgpt_diagnostics = esc_attr(get_option('chatbot_chatgpt_diagnostics', 'Off'));
    ?>
    <select id="chatbot_chatgpt_diagnostics" name = "chatbot_chatgpt_diagnostics">
        <option value="Off" <?php selected( $chatbot_chatgpt_diagnostics, 'Off' ); ?>><?php echo esc_html( 'Off' ); ?></option>
        <option value="Success" <?php selected( $chatbot_chatgpt_diagnostics, 'Success' ); ?>><?php echo esc_html( 'Success' ); ?></option>
        <option value="Notice" <?php selected( $chatbot_chatgpt_diagnostics, 'Notice' ); ?>><?php echo esc_html( 'Notice' ); ?></option>
        <option value="Failure" <?php selected( $chatbot_chatgpt_diagnostics, 'Failure' ); ?>><?php echo esc_html( 'Failure' ); ?></option>
        <option value="Warning" <?php selected( $chatbot_chatgpt_diagnostics, 'Warning' ); ?>><?php echo esc_html( 'Warning' ); ?></option>
        <option value="Error" <?php selected( $chatbot_chatgpt_diagnostics, 'Error' ); ?>><?php echo esc_html( 'Error' ); ?></option>
     </select>
    <?php
}

// Custom Error Message - Ver 2.0.3
function chatbot_chatgpt_custom_error_message_callback($args) {
    $chatbot_chatgpt_custom_error_message = esc_attr(get_option('chatbot_chatgpt_custom_error_message', 'Your custom error message goes here.'));
    if ( $chatbot_chatgpt_custom_error_message === null || $chatbot_chatgpt_custom_error_message === '' ) {
        $chatbot_chatgpt_custom_error_message = 'Your custom error message goes here.';
    }
    ?>
    <input type="text" id="chatbot_chatgpt_custom_error_message" name="chatbot_chatgpt_custom_error_message" value="<?php echo esc_html( $chatbot_chatgpt_custom_error_message ); ?>" size="50">
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

// Delete Plugin Data on Uninstall - Ver 1.9.9
function chatbot_chatgpt_delete_data_callback($args) {
    global $chatbot_chatgpt_delete_data;
    $chatbot_chatgpt_delete_data = esc_attr(get_option('chatbot_chatgpt_delete_data', 'no'));
    ?>
    <select id="chatbot_chatgpt_delete_data_setting" name = "chatbot_chatgpt_delete_data">
        <option value="no" <?php selected( $chatbot_chatgpt_delete_data, 'no' ); ?>><?php echo esc_html( 'No' ); ?></option>
        <option value="yes" <?php selected( $chatbot_chatgpt_delete_data, 'yes' ); ?>><?php echo esc_html( 'Yes' ); ?></option>
    </select>
    <?php
}

// Enhanced Error Logging if Diagnostic Mode is On - Ver 1.6.9
// Call this function using back_trace( 'NOTICE', $message);
// [ERROR], [WARNING], [NOTICE], or [SUCCESS]
// back_trace( 'ERROR', 'Some message');
// back_trace( 'WARNING', 'Some message');
// back_trace( 'NOTICE', 'Some message');
// back_trace( 'SUCCESS', 'Some message');
function back_trace($message_type = "NOTICE", $message = "No message") {

    // Check if diagnostics is On
    $chatbot_chatgpt_diagnostics = esc_attr(get_option('chatbot_chatgpt_diagnostics', 'ERROR'));
    if ('Off' === $chatbot_chatgpt_diagnostics) {
        return;
    }

    // Belt and suspenders - make sure the value is either Off or Error
    if ('On' === $chatbot_chatgpt_diagnostics) {
        $chatbot_chatgpt_diagnostics = 'Error';
        update_option('chatbot_chatgpt_diagnostics', $chatbot_chatgpt_diagnostics);
    }

    $backtrace = debug_backtrace();
    // $caller = array_shift($backtrace);
    $caller = $backtrace[1]; // Get the second element from the backtrace array

    $file = basename($caller['file']); // Gets the file name
    $function = $caller['function']; // Gets the function name
    $line = $caller['line']; // Gets the line number

    if ($message === null || $message === '') {
        $message = "No message";
    }
    if ($message_type === null || $message_type === '') {
        $message_type = "NOTICE";
    }

    // Convert the message to a string if it's an array
    if (is_array($message)) {
        $message = print_r($message, true); // Return the output as a string
    }

    // Upper case the message type
    $message_type = strtoupper($message_type);

    // Message Type: Indicating whether the log is an error, warning, notice, or success message.
    // Prefix the message with [ERROR], [WARNING], [NOTICE], or [SUCCESS].
    // Check for other levels and print messages accordingly
    if ('Error' === $chatbot_chatgpt_diagnostics) {
        // Print all types of messages
        error_log("[Chatbot] [$file] [$function] [$line] [$message_type] [$message]");
    } elseif ('Success' === $chatbot_chatgpt_diagnostics || 'Failure' === $chatbot_chatgpt_diagnostics) {
        // Print only SUCCESS and FAILURE messages
        if (in_array($message_type, ['SUCCESS', 'FAILURE'])) {
            error_log("[Chatbot] [$file] [$function] [$line] [$message_type] [$message]");
        }
    } elseif ('Warning' === $chatbot_chatgpt_diagnostics) {
        // Print only ERROR and WARNING messages
        if (in_array($message_type, ['ERROR', 'WARNING'])) {
            error_log("[Chatbot] [$file] [$function] [$line] [$message_type] [$message]");
        }
    } elseif ('Notice' === $chatbot_chatgpt_diagnostics) {
        // Print ERROR, WARNING, and NOTICE messages
        if (in_array($message_type, ['ERROR', 'WARNING', 'NOTICE'])) {
            error_log("[Chatbot] [$file] [$function] [$line] [$message_type] [$message]");
        }
    } elseif ('Debug' === $chatbot_chatgpt_diagnostics) {
        // Print all types of messages
        error_log("[Chatbot] [$file] [$function] [$line] [$message_type] [$message]");
    } else {
        // Exit if none of the conditions are met
        return;
    }

}

// Log Chatbot Errors to the Server - Ver 2.0.3
function log_chatbot_error() {
    if (isset($_POST['error_message'])) {
        $error_message = sanitize_text_field($_POST['error_message']);
        error_log('[Chatbot] [ERROR] [' . $error_message . ']');
    }
    wp_die(); // this is required to terminate immediately and return a proper response
}

// Register AJAX actions
add_action('wp_ajax_log_chatbot_error', 'log_chatbot_error');
add_action('wp_ajax_nopriv_log_chatbot_error', 'log_chatbot_error');
