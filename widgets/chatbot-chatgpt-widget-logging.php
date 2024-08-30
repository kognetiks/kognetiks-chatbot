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
function chatbot_chatgpt_widget_logging($message) {

    error_log('chatbot_chatgpt_widget_logging: ' . $message);

    $chatbot_chatgpt_widget_logging = esc_attr(get_option('chatbot_chatgpt_widget_logging', 'No'));

    if ($chatbot_chatgpt_widget_logging === 'No') {
        return;
    }

    $date_time = (new DateTime())->format('d-M-Y H:i:s \U\T\C');

    chatbot_chatgpt_widget_log_usage($message);

}

// Log widget usage
function chatbot_chatgpt_widget_log_usage($message) {

    // Plugin directory path
    $chatbot_chatgpt_plugin_dir_path = dirname(plugin_dir_path( __FILE__ ));

    error_log('chatbot_chatgpt_widget_log_usage: ' . $message);

    global $chatbot_chatgpt_plugin_dir_path;

    $chatbot_chatgpt_widget_logs_dir = $chatbot_chatgpt_plugin_dir_path . 'widget-logs/';

    // Ensure the directory and index file exist
    create_directory_and_index_file($chatbot_chatgpt_widget_logs_dir);

    // Get the current date to create a daily log file
    $current_date = date('Y-m-d');
    $log_file = chatbot_chatgpt_widget_log_dir . 'chatbot-usage-log-' . $current_date . '.log';

    // Append the error message to the log file
    file_put_contents($log_file, $message . PHP_EOL, FILE_APPEND | LOCK_EX);

}

