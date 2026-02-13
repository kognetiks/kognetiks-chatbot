<?php
/**
 * Kognetiks Chatbot - Options Exporter - Ver 2.0.6
 *
 * This file contains the code for exporting the chatbot options.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

function chatbot_chatgpt_download_options_data() {

    global $chatbot_chatgpt_plugin_dir_path;

    global $wpdb;

    // Security: Check capability
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'chatbot-chatgpt' ), 403 );
    }

    // Security: Verify nonce for CSRF protection
    if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( $_REQUEST['_wpnonce'] ), 'chatbot_chatgpt_download_options_data' ) ) {
        wp_die( esc_html__( 'Security check failed. Please refresh the page and try again.', 'chatbot-chatgpt' ), 403 );
    }

    // Ensure no output is sent before headers
    if (headers_sent()) {
        wp_die( esc_html__( 'Headers already sent. Cannot proceed with the download.', 'chatbot-chatgpt' ) );
    }

    $debug_dir_path = $chatbot_chatgpt_plugin_dir_path . 'debug/';

    // Create debug directory if it doesn't exist
    if (!file_exists($debug_dir_path)) {
        if (!mkdir($debug_dir_path, 0777, true)) {
            wp_die( esc_html__( 'Failed to create debug directory.', 'chatbot-chatgpt' ) );
        }
    }

    $output_choice = strtolower(esc_attr(get_option('chatbot_chatgpt_options_exporter_extension', 'csv')));

    $options_file = $debug_dir_path . 'chatbot-chatgpt-options.' . $output_choice;

    // Fetch options from the database
    $options = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}options WHERE option_name LIKE 'chatbot%' AND option_name != 'chatbot_chatgpt_api_key'", ARRAY_A);

    // Write options to file
    if ($output_choice == 'json') {


        // Write options to JSON file
        $options_data = json_encode($options, JSON_PRETTY_PRINT);
        if (file_put_contents($options_file, $options_data) === false) {
            wp_die( esc_html__( 'Failed to write options to file.', 'chatbot-chatgpt' ) );
        }

    } elseif ($output_choice == 'csv') {


        // Open the file for writing
        $fileHandle = fopen($options_file, 'w');

        // Check if the file was opened successfully
        if ($fileHandle === false) {
            wp_die( esc_html__( 'Failed to open file for writing', 'chatbot-chatgpt' ) );
        }

        // Write the CSV header
        fputcsv($fileHandle, array('option_id', 'option_name', 'option_value', 'autoload'));

        // Write each option as a CSV row
        foreach ($options as $option) {
            fputcsv($fileHandle, $option);
        }

        // Close the file
        fclose($fileHandle);

    } else {
        $class = 'notice notice-error';
        $message = __('Invalid output choice.', 'chatbot-chatgpt');
        chatbot_chatgpt_general_admin_notice($message);
        return;
    }

    // Check if file is readable and writable
    if (!is_readable($options_file) || !is_writable($options_file)) {
        $class = 'notice notice-error';
        $message = __('Error with file permissions', 'chatbot-chatgpt');
        chatbot_chatgpt_general_admin_notice($message);
        return;
    }

    // Read file contents
    $options_data = file_get_contents($options_file);
    if ($options_data === false) {
        wp_die( esc_html__( 'Failed to read options file.', 'chatbot-chatgpt' ) );
    }

    // Deliver the file for download
    if ($output_choice === 'json') {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="chatbot-chatgpt-options.json"');
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON output for file download, escaping would corrupt the data.
        echo $options_data;
    } elseif ($output_choice === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="chatbot-chatgpt-options.csv"');
        readfile($options_file); // Directly output the CSV file content
    } else {
        $class = 'notice notice-error';
        $message = __('Invalid output choice.', 'chatbot-chatgpt');
        chatbot_chatgpt_general_admin_notice($message);
        return;
    }

    // Delete the file after download
    if (!unlink($options_file)) {
        $class = 'notice notice-error';
        $message = __('Failed to delete options file after download.', 'chatbot-chatgpt');
        chatbot_chatgpt_general_admin_notice($message);
    }
    exit;
}

// Hook the exporter function to admin_menu action
add_action('admin_post_chatbot_chatgpt_download_options_data', 'chatbot_chatgpt_download_options_data');
