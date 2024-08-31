<?php
/**
 * Kognetiks Chatbot for WordPress - Chatbot ChatGPT WIDGET LOGGING - Ver 2.1.3
 *
 * This file contains the code accessing the Chatbot ChatGPT endpoint.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
// if ( ! defined( 'WPINC' ) ) {
//     die;
// }

// Widget logging
function chatbot_chatgpt_widget_logging( $message, $referer = null, $request_ip = null ) {

    // Plugin directory path
    $chatbot_chatgpt_plugin_dir_path = dirname(plugin_dir_path( __FILE__ ));
    error_log('chatbot_chatgpt_plugin_dir_path: ' . $chatbot_chatgpt_plugin_dir_path);

    // Plugin directory URL
    $chatbot_chatgpt_plugin_dir_url = plugins_url( '/', __FILE__ );
    error_log('chatbot_chatgpt_plugin_dir_url: ' . $chatbot_chatgpt_plugin_dir_url);

    $chatbot_chatgpt_widget_logging = esc_attr(get_option('chatbot_chatgpt_widget_logging', 'No'));

    if ($chatbot_chatgpt_widget_logging === 'No') {
        return;
    }

    $date_time = (new DateTime())->format('d-M-Y H:i:s \U\T\C');

    $chatbot_chatgpt_widget_logs_dir = $chatbot_chatgpt_plugin_dir_path . '/widget-logs/';

    // Ensure the directory and index file exist
    create_directory_and_index_file($chatbot_chatgpt_widget_logs_dir);

    // Get the current date to create a daily log file
    $current_date = date('Y-m-d');
    $log_file = $chatbot_chatgpt_widget_logs_dir . 'chatbot-usage-log-' . $current_date . '.log';

    error_log( $message );

    $message = '[' . $date_time . '] [' . $request_ip . '] [' . $referer . '] ' .$message;

    // Append the error message to the log file
    file_put_contents($log_file, $message . PHP_EOL, FILE_APPEND | LOCK_EX);

}

// IP address logging
function getUserIP() {
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        // Check IP from shared internet
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Check IP passed from proxy
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        // In case of multiple IPs, take the first one
        $ip = explode(',', $ip)[0];
    } else {
        // Default fallback to REMOTE_ADDR
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    // If the IP is still localhost, you might want to handle it differently
    if ($ip === '::1' || $ip === '127.0.0.1') {
        // Handle localhost IP differently if needed
        $ip = '127.0.0.1'; // or any other fallback IP
    }

    return $ip;
}

