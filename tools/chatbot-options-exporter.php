<?php
/**
 * Kognetiks Chatbot for WordPress - Options Exporter - Ver 2.0.6
 *
 * This file contains the code for exporting the chatbot options.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Export the chatbot options to a file
function chatbot_chatgpt_options_exporter() {

    ?>
    <div>
    <p>Use the button (below) to retrieve the chatbot options and download as a TXT file.</p>
    <?php
        if (is_admin()) {
            $header = " ";
            $header .= '<a class="button button-primary" href="' . esc_url(admin_url('admin-post.php?action=chatbot_chatgpt_download_options_data')) . '">Download Options Data</a>';
            echo $header;
        }
    ?>
    </div>
    <?php

}

function chatbot_chatgpt_download_options_data() {

    // Ensure the current user has the capability to export options
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'chatbot-chatgpt'));
    }

    // Ensure no output is sent before headers
    if (headers_sent()) {
        wp_die(__('Headers already sent. Cannot proceed with the download.', 'chatbot-chatgpt'));
    }

    $debug_dir_path = CHATBOT_CHATGPT_PLUGIN_DIR_PATH . 'debug/';

    // Create debug directory if it doesn't exist
    if (!file_exists($debug_dir_path)) {
        if (!mkdir($debug_dir_path, 0777, true)) {
            wp_die(__('Failed to create debug directory.', 'chatbot-chatgpt'));
        }
    }

    global $wpdb;

    // Fetch options from the database
    $options = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}options WHERE option_name LIKE 'chatbot%' AND option_name != 'chatbot_chatgpt_api_key'", ARRAY_A);

    $options_file = $debug_dir_path . 'chatbot-chatgpt-options.txt';

    // Write options to file
    if (file_put_contents($options_file, print_r($options, true)) === false) {
        wp_die(__('Failed to write options to file.', 'chatbot-chatgpt'));
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
        wp_die(__('Failed to read options file.', 'chatbot-chatgpt'));
    }

    // Deliver the file for download
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="chatbot-chatgpt-options.txt"');
    echo $options_data;

    // Delete the file after download
    if (!unlink($options_file)) {
        $class = 'notice notice-error';
        $message = __('Failed to delete options file after download.', 'chatbot-chatgpt');
        chatbot_chatgpt_general_admin_notice($message);
    }
    exit;
    
}
// Hook the exporter function to admin_menu action
// add_action('admin_menu', 'chatbot_chatgpt_download_options');
add_action('admin_post_chatbot_chatgpt_download_options_data', 'chatbot_chatgpt_download_options_data');