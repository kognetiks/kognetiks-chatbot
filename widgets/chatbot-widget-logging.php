<?php
/**
 * Kognetiks Chatbot - Chatbot WIDGET LOGGING - Ver 2.1.3 - Updated Ver 2.2.7 - 2025-03-21
 *
 * This file contains the code accessing the Chatbot Widget endpoint.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

/**
 * Log widget interactions with proper error handling
 *
 * @param string $message The message to log
 * @param string|null $referer The referer URL
 * @param string|null $request_ip The IP address of the request
 * @return bool Whether the logging was successful
 */
function chatbot_widget_logging( $message, $referer = null, $request_ip = null ) {
    try {
        // Plugin directory path
        $chatbot_plugin_dir_path = dirname(plugin_dir_path( __FILE__ ));

        // Which platform is in use
        $chatbot_ai_platform_choice = esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI'));

        if ($chatbot_ai_platform_choice == 'Azure OpenAI') {
            $widget_logging = esc_attr(get_option('chatbot_azure_widget_logging', 'No'));
        } else {
            $widget_logging = esc_attr(get_option('chatbot_chatgpt_widget_logging', 'No'));
        }

        if ($widget_logging === 'No') {
            return true;
        }

        $date_time = (new DateTime())->format('d-M-Y H:i:s \U\T\C');
        $chatbot_widget_logs_dir = $chatbot_plugin_dir_path . '/widget-logs/';

        // Ensure the directory and index file exist
        if (!create_directory_and_index_file($chatbot_widget_logs_dir)) {
            error_log('[Chatbot] [chatbot-widget-logging.php] Failed to create or verify widget logs directory');
            return false;
        }

        // Get the current date to create a daily log file
        $current_date = date('Y-m-d');
        $log_file = $chatbot_widget_logs_dir . 'chatbot-widget-access-' . $current_date . '.log';

        // Sanitize inputs
        $referer = $referer ? esc_url($referer) : 'unknown';
        $request_ip = $request_ip ? sanitize_text_field($request_ip) : 'unknown';
        $message = sanitize_text_field($message);

        $log_entry = '[' . $date_time . '] [' . $request_ip . '] [' . $referer . '] ' . $message;

        // Append the error message to the log file with error handling
        if (file_put_contents($log_file, $log_entry . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
            error_log('[Chatbot] [chatbot-widget-logging.php] Failed to write to widget log file: ' . $log_file);
            return false;
        }

        return true;
    } catch (Exception $e) {
        error_log('[Chatbot] [chatbot-widget-logging.php] Error in chatbot_widget_logging: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get the real IP address of the user with proper validation
 *
 * @return string The validated IP address
 */
function getUserIP() {
    $ip = '';

    // Check IP from shared internet
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    // Check IP passed from proxy
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        // In case of multiple IPs, take the first one
        $ip = explode(',', $ip)[0];
    }
    // Default fallback to REMOTE_ADDR
    elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    // Handle localhost IPs
    if ($ip === '::1' || $ip === '127.0.0.1') {
        $ip = '127.0.0.1';
    }

    // Validate IP address format
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        $ip = '0.0.0.0'; // Invalid IP fallback
    }

    return $ip;
}
