<?php
/**
 * Kognetiks Chatbot for WordPress - File Uploads - Ver 1.7.6
 *
 * This file contains the code for uploading files as part
 * in support of Custom GPT Assistants via the Chatbot.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Upload Multiple files to the Assistant
function chatbot_chatgpt_upload_file_to_assistant(): array {
    
    // DIAG - Diagnostic - Ver 1.9.2
    // back_trace( 'NOTICE', 'Entering chatbot_chatgpt_upload_file_to_assistant()' );
    // back_trace( 'NOTICE', '$_FILES', print_r($_FILES, true));

    global $session_id;

    // FIXME - SO THAT THE PLUGIN ISN'T HARD CODED - V1.9.2 - 2024 03 05
    $upload_dir = WP_CONTENT_DIR . '/plugins/chatbot-chatgpt/uploads/';
    
    // Ensure the upload directory exists
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true); // Create directory if it doesn't exist
    }

    $api_key = esc_attr(get_option('chatbot_chatgpt_api_key'));
    if (empty($api_key)) {
        return array(
            'status' => 'error',
            'message' => 'Oops! Your API key is missing. Please enter your API key in the Chatbot settings.'
        );
    }

    // Initialize the response array to collect upload status of each file
    $responses = [];

    // Check if files were uploaded
    if (isset($_FILES['file']['name']) && is_array($_FILES['file']['name'])) {
        // Loop through each file
        for ($i = 0; $i < count($_FILES['file']['name']); $i++) {
            $file_path = $upload_dir . basename($_FILES['file']['name'][$i]);

            // Check for upload errors
            if ($_FILES['file']['error'][$i] > 0) {
                $responses[] = array(
                    'status' => 'error',
                    'message' => "Oops! Something went wrong during the upload of {$_FILES['file']['name'][$i]}. Please try again later."
                );
                continue;
            }

            // Move the uploaded file
            if (!move_uploaded_file($_FILES['file']['tmp_name'][$i], $file_path)) {
                $responses[] = array(
                    'status' => 'error',
                    'message' => "Oops! Something went wrong during the upload of {$_FILES['file']['name'][$i]}. Please try again later."
                );
                continue;
            }

            // Prepare CURL request for each file
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, get_files_api_url());
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $api_key));

            $postFields = array(
                'purpose' => 'assistants',
                'file' => new CURLFile($file_path)
            );
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

            // Execute CURL
            $response = curl_exec($ch);
            $http_status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            if (curl_errno($ch)) {
                $responses[] = array(
                    'status' => 'error',
                    'http_status' => $http_status,
                    'message' => 'Error:' . curl_error($ch)
                );
            } else {
                $responseData = json_decode($response, true);
                if ($http_status != 200 || isset($responseData['error'])) {
                    $errorMessage = $responseData['error']['message'] ?? 'Unknown error occurred.';
                    $responses[] = array(
                        'status' => 'error',
                        'http_status' => $http_status,
                        'message' => $errorMessage
                    );
                } else {
                    // Assuming the API does not support batch uploads, each successful upload will be recorded individually
                    set_chatbot_chatgpt_transients_files('chatbot_chatgpt_assistant_file_id', $responseData['id'], $session_id, $i);

                    // DIAG - Diagnostic - Ver 1.9.2
                    // back_trace( 'NOTICE', 'asst_file_id ' . $responseData['id'] );

                    // MOVED TO OUTSIDE THE IF TEST - VER 1.9.9
                    // unlink($file_path); // Optionally delete the file after successful upload

                    $responses[] = array(
                        'status' => 'success',
                        'http_status' => $http_status,
                        'id' => $responseData['id'],
                        'message' => "File {$_FILES['file']['name'][$i]} uploaded successfully."
                    );

                    // DIAG - Diagnostic - Ver 1.9.2
                    // back_trace( 'NOTICE', 'responses', print_r($responses, true));

                }
            }

            // DELETE THE FILE AFTER UPLOAD - VER 1.9.9
            // b2711c63-b1d9-430e-bbab-d077d93442c8
            unlink($file_path); // Optionally delete the file after successful upload

            curl_close($ch);
            
        }

        // DIAG - Diagnostic - Ver 1.9.2
        // back_trace( 'NOTICE', '$responses', print_r($responses, true));

        return $responses;

    } else {
        return array(
            'status' => 'error',
            'message' => 'Oops! Please select a file to upload.'
        );
    }
}

// Delete old audio files - Ver 1.9.9
function chatbot_chatgpt_cleanup_old_file_uploads() {
    $audio_dir = CHATBOT_CHATGPT_PLUGIN_DIR_PATH . 'uploads/';
    foreach (glob($audio_dir . '*') as $file) {
        // Delete files older than 1 hour
        if (filemtime($file) < time() - 60 * 60 * 1) {
            unlink($file);
        }
    }
}
add_action('chatbot_chatgpt_cleanup_upload_files', 'chatbot_chatgpt_cleanup_old_file_uploads');