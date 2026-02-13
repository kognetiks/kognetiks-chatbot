<?php
/**
 * Kognetiks Chatbot - Download Transcript - Ver 1.9.9
 *
 * This file contains the code for uploading files as part
 * in support of Custom GPT Assistants via the Chatbot.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

function chatbot_chatgpt_download_transcript() {

    // Security: Verify nonce for CSRF protection
    if (!isset($_POST['chatbot_nonce']) || !wp_verify_nonce($_POST['chatbot_nonce'], 'chatbot_transcript_nonce')) {
        wp_send_json_error('Security check failed. Please refresh the page and try again.', 403);
        return;
    }

    global $chatbot_chatgpt_plugin_dir_path;

    if (!isset($_POST['user_id'], $_POST['page_id'], $_POST['conversation_content'])) {
        wp_send_json_error('Missing required POST fields');
        return;
    }

    $user_id = sanitize_text_field($_POST['user_id']);
    $page_id = sanitize_text_field($_POST['page_id']);

    // $conversation_content = sanitize_textarea_field($_POST['conversation_content']);

    // Strip HTML tags from the conversation content and replace </div> with \n\n
    $conversation_content = str_replace('</div>', "\n", $_POST['conversation_content']);
    $conversation_content = wp_strip_all_tags($conversation_content);

    // Define the path to the transcripts directory
    $transcript_dir = $chatbot_chatgpt_plugin_dir_path . 'transcripts/';

    // Ensure the directory exists or attempt to create it
    if (!create_directory_and_index_file($transcript_dir)) {
        // Error handling, e.g., log the error or handle the failure appropriately
        return;
    }

    // Create the filename
    $transcriptFileName = 'transcript_' . generate_random_string() . '_' . date('Y-m-d_H-i-s') . '.txt';
    $transcriptFile = $transcript_dir . $transcriptFileName;

    // Attempt to write the content to the file
    if (file_put_contents($transcriptFile, $conversation_content) === false) {
        wp_send_json_error('Failed to write to file');
        return;
    }

    // Construct the URL for download
    $url = plugins_url('transcripts/' . $transcriptFileName, $chatbot_chatgpt_plugin_dir_path . 'chatbot-chatgpt');

    wp_send_json_success($url);

}
add_action('wp_ajax_chatbot_chatgpt_download_transcript', 'chatbot_chatgpt_download_transcript');
add_action('wp_ajax_nopriv_chatbot_chatgpt_download_transcript', 'chatbot_chatgpt_download_transcript');

// Delete old transcripts - Ver 1.9.9   
function chatbot_chatgpt_cleanup_transcripts_directory() {

    global $chatbot_chatgpt_plugin_dir_path;

    $transcripts_dir = $chatbot_chatgpt_plugin_dir_path . 'transcripts/';
    foreach (glob($transcripts_dir . '*') as $file) {
        // Delete files older than 1 hour
        if (filemtime($file) < time() - 60 * 60 * 1) {
            unlink($file);
        }
    }
    // Create the index.php file if it does not exist
    create_directory_and_index_file($transcripts_dir);
}
add_action('chatbot_chatgpt_cleanup_transcript_files', 'chatbot_chatgpt_cleanup_transcripts_directory');
