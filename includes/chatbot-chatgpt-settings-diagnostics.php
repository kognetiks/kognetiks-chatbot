<?php
/**
 * Chatbot ChatGPT for WordPress - Settings - Diagnostics
 *
 * This file contains the code for the Chatbot ChatGPT settings page.
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
    <p>The Diagnostics tab checks the API status and set options for diagostics and notices.</p>
    <p>You can turn on/off console and error logging (as of Version 1.6.5 most are now commented out).</p>
    <p>You can also suppress attribution ('Chatbot & Knowledge Navigator by Kognetiks') and notices by setting the value to 'On' (suppress) or 'Off' (no suppression).</p>
    <h2>System and Plugin Information</h2>
    <?php
    // Get PHP version
    $php_version = phpversion();

    // Get WordPress version
    global $wp_version;

    echo '<p>PHP Version: <b>' . $php_version . '</b><br>';
    echo 'WordPress Version: <b>' . $wp_version . '</b><br>';
    echo 'Chatbot ChatGPT Version: <b>' . get_plugin_version() . '</b><br>';
    echo 'WordPress Language Code: <b>' . get_locale() . '</b></p>';
    echo '<h2>API Status and Other Settings</h2>';
}

// Moved to chatbot-chatgpt-upgrade.php - Ver 1.7.6
// Return the version of the plugin
// function get_plugin_version() {
//     if (!function_exists('get_plugin_data')) {
//         require_once(ABSPATH . 'wp-admin/includes/plugin.php');
//     }

//     $plugin_data = get_plugin_data(plugin_dir_path(__FILE__) . '../chatbot-chatgpt.php');
//     $plugin_version = $plugin_data['Version'];
//     // Set the plugin version in the database
//     update_option('chatbot_chatgpt_plugin_version', $plugin_version);

//     return $plugin_version;

// }

// Call the api-test.php file to test the API
function chatbot_chatgpt_api_test_callback($args) {
    $api_key = get_option('chatgpt_api_key');
    test_chatgpt_api($api_key);
    $updated_status = esc_attr(get_option('chatbot_chatgpt_api_status', 'NOT SET'));
    ?>
    <p>API STATUS: <b><?php echo esc_html( $updated_status ); ?></b></p>
    <?php
    $status = ini_get('allow_url_fopen');
    if ($status == 1) {
        ?>
        <p>PHP OPTION: <b>allow_url_fopen is enabled.</b></p>
        <?php
    } else {
        ?>
        <p>PHP OPTION: <b>allow_url_fopen is disabled.</b></p>
        <?php
    }
}

// Diagnostics On/Off - Ver 1.6.5
function chatbot_chatgpt_diagnostics_setting_callback($args) {
    global $chatbot_chatgpt_diagnostics;
    $chatbot_chatgpt_diagnostics = esc_attr(get_option('chatbot_chatgpt_diagnostics', 'Off'));
    ?>
    <select id="chatgpt_diagnostics_setting" name = "chatbot_chatgpt_diagnostics">
        <option value="Off" <?php selected( $chatbot_chatgpt_diagnostics, 'Off' ); ?>><?php echo esc_html( 'Off' ); ?></option>
        <option value="Success" <?php selected( $chatbot_chatgpt_diagnostics, 'Success' ); ?>><?php echo esc_html( 'Success' ); ?></option>
        <option value="Notice" <?php selected( $chatbot_chatgpt_diagnostics, 'Notice' ); ?>><?php echo esc_html( 'Notice' ); ?></option>
        <option value="Failure" <?php selected( $chatbot_chatgpt_diagnostics, 'Failure' ); ?>><?php echo esc_html( 'Failure' ); ?></option>
        <option value="Warning" <?php selected( $chatbot_chatgpt_diagnostics, 'Warning' ); ?>><?php echo esc_html( 'Warning' ); ?></option>
        <option value="Error" <?php selected( $chatbot_chatgpt_diagnostics, 'Error' ); ?>><?php echo esc_html( 'Error' ); ?></option>
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

// Suppress Learnings Message - Ver 1.7.1
function chatbot_chatgpt_suppress_learnings_callback($args) {
    global $chatbot_chatgpt_suppress_learnings;
    $chatbot_chatgpt_suppress_learnings = esc_attr(get_option('chatbot_chatgpt_suppress_learnings', 'Random'));
    ?>
    <select id="chatgpt_suppress_learnings_setting" name = "chatbot_chatgpt_suppress_learnings">
        <option value="None" <?php selected( $chatbot_chatgpt_suppress_learnings, 'None' ); ?>><?php echo esc_html( 'None' ); ?></option>
        <option value="Random" <?php selected( $chatbot_chatgpt_suppress_learnings, 'Random' ); ?>><?php echo esc_html( 'Random' ); ?></option>
        <option value="Custom" <?php selected( $chatbot_chatgpt_suppress_learnings, 'Custom' ); ?>><?php echo esc_html( 'Custom' ); ?></option>
    </select>
    <?php
}

// Suppress Learnings Message - Ver 1.7.1
function chatbot_chatgpt_custom_learnings_message_callback($args) {
    global $chatbot_chatgpt_custom_learnings_message;
    $chatbot_chatgpt_custom_learnings_message = esc_attr(get_option('chatbot_chatgpt_custom_learnings_message', 'More information may be found here ...'));
    ?>
    <input type="text" style="width: 50%;" id="chatbot_chatgpt_custom_learnings_message" name = "chatbot_chatgpt_custom_learnings_message" value="<?php echo esc_attr( $chatbot_chatgpt_custom_learnings_message ); ?>">
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

// Enhanced Error Logging if Diagnostic Mode is On - Ver 1.6.9
// Call this function using chatbot_chatgpt_back_trace( 'NOTICE', $message);
// [ERROR], [WARNING], [NOTICE], or [SUCCESS]
// chatbot_chatgpt_back_trace( 'ERROR', 'Some message');
// chatbot_chatgpt_back_trace( 'WARNING', 'Some message');
// chatbot_chatgpt_back_trace( 'NOTICE', 'Some message');
// chatbot_chatgpt_back_trace( 'SUCCESS', 'Some message');
function chatbot_chatgpt_back_trace($message_type = "NOTICE", $message = "No message") {

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
        error_log("[Chatbot ChatGPT] [$file] [$function] [$line] [$message_type] [$message]");
    } elseif ('Success' === $chatbot_chatgpt_diagnostics || 'Failure' === $chatbot_chatgpt_diagnostics) {
        // Print only SUCCESS and FAILURE messages
        if (in_array($message_type, ['SUCCESS', 'FAILURE'])) {
            error_log("[Chatbot ChatGPT] [$file] [$function] [$line] [$message_type] [$message]");
        }
    } elseif ('Warning' === $chatbot_chatgpt_diagnostics) {
        // Print only ERROR and WARNING messages
        if (in_array($message_type, ['ERROR', 'WARNING'])) {
            error_log("[Chatbot ChatGPT] [$file] [$function] [$line] [$message_type] [$message]");
        }
    } elseif ('Notice' === $chatbot_chatgpt_diagnostics) {
        // Print ERROR, WARNING, and NOTICE messages
        if (in_array($message_type, ['ERROR', 'WARNING', 'NOTICE'])) {
            error_log("[Chatbot ChatGPT] [$file] [$function] [$line] [$message_type] [$message]");
        }
    } else {
        // Exit if none of the conditions are met
        return;
    }

}