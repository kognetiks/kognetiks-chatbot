<?php
/**
 * Kognetiks Chatbot for WordPress - Download File from API - Ver 2.0.3
 *
 * This file contains the code for downloading a file generated by an Assistant.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

function download_openai_file($file_id, $filename) {

    global $chatbot_chatgpt_plugin_dir_path;

    global $session_id;

    $downloads_dir = $chatbot_chatgpt_plugin_dir_path . 'downloads/';

    // Ensure the directory exists or attempt to create it
    if (!create_directory_and_index_file($downloads_dir)) {
        // Error handling, e.g., log the error or handle the failure appropriately
        // DIAG - Diagnostic - Ver 2.0.3
        // back_trace( 'ERROR', 'Failed to create download directory.');
        // FIXME - Return an error message
        return false;
    }

    $api_key = esc_attr(get_option('chatbot_chatgpt_api_key'));
    if (empty($api_key)) {
        // DIAG - Diagnostic - Ver 2.0.3
        // back_trace( 'ERROR', 'API key is missing.');
        // FIXME - Return an error message
        return false;
    }

    // API endpoint to retrieve the file content
    $api_file_url = "https://api.openai.com/v1/files/$file_id/content";

    // Initialize cURL session
    $ch = curl_init($api_file_url);

    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $api_key"
    ]);

    // Execute cURL request
    $response = curl_exec($ch);

    // Check for cURL errors
    if (curl_errno($ch)) {
        // DIAG - Diagnostic - Ver 2.0.3
        // back_trace( 'ERROR', 'cURL error: ' . curl_error($ch));
        curl_close($ch);
        // FIXME - Return an error message
        return false;
    }

    // Get HTTP status code
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Close cURL session
    curl_close($ch);

    // Check if the request was successful
    if ($http_code == 200) {

        // Define the file path
        $file_path = $downloads_dir . $filename;

        // Save the file content locally
        file_put_contents($file_path, $response);

        // Return the file URL
        return content_url('plugins/' . basename($chatbot_chatgpt_plugin_dir_path) . '/downloads/' . $filename);

    } else {

        // DIAG - Diagnostic - Ver 2.0.3
        // back_trace( 'ERROR', 'Failed to retrieve the file: ' . $http_code);
        return false;
    }
}

// Delete old download files - Ver 2.0.3
function chatbot_chatgpt_cleanup_download_directory() {

    global $chatbot_chatgpt_plugin_dir_path;

    $download_dir = $chatbot_chatgpt_plugin_dir_path . 'downloads/';
    foreach (glob($download_dir . '*') as $file) {
        // Delete files older than 1 hour
        if (filemtime($file) < time() - 60 * 60 * 1) {
            unlink($file);
        }
    }
    // Create the index.php file if it does not exist
    create_directory_and_index_file($download_dir);
    
}
add_action('chatbot_chatgpt_cleanup_download_files', 'chatbot_chatgpt_cleanup_download_directory');
