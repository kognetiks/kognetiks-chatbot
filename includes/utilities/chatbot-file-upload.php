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

    global $session_id;

    $upload_dir = CHATBOT_CHATGPT_PLUGIN_DIR_PATH . 'uploads/';

    // Ensure the directory exists or attempt to create it
    if (!file_exists($upload_dir) && !wp_mkdir_p($upload_dir)) {
        // Error handling, e.g., log the error or handle the failure appropriately
        // back_trace ( 'ERROR', 'Failed to create results directory.')
        return array(
            'status' => 'error',
            'message' => 'Oops! File upload failed.'
        );
    }
    // Protect the directory - Ver 2.0.0
    chmod($upload_dir, 0700);

    $api_key = esc_attr(get_option('chatbot_chatgpt_api_key'));
    if (empty($api_key)) {
        return array(
            'status' => 'error',
            'message' => 'Oops! Your API key is missing. Please enter your API key in the Chatbot settings.'
        );
    }

    $responses = [];

    // Check if files were uploaded
    if (isset($_FILES['file']['name']) && is_array($_FILES['file']['name'])) {
        for ($i = 0; $i < count($_FILES['file']['name']); $i++) {
            // Generate a random file name
            $newFileName = generate_random_string() . '.' . pathinfo($_FILES['file']['name'][$i], PATHINFO_EXTENSION);
            $file_path = $upload_dir . $newFileName;

            if ($_FILES['file']['error'][$i] > 0) {
                $responses[] = array(
                    'status' => 'error',
                    'message' => "Oops! Something went wrong during the upload of {$_FILES['file']['name'][$i]}. Please try again later."
                );
                continue;
            }

            if (!move_uploaded_file($_FILES['file']['tmp_name'][$i], $file_path)) {
                $responses[] = array(
                    'status' => 'error',
                    'message' => "Oops! Something went wrong during the upload of {$_FILES['file']['name'][$i]}. Please try again later."
                );
                continue;
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, get_files_api_url());
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $api_key));
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                'purpose' => 'assistants',
                'file' => new CURLFile($file_path)
            ]);

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
                    set_chatbot_chatgpt_transients_files('chatbot_chatgpt_assistant_file_id', $responseData['id'], $session_id, $i);
                    $responses[] = array(
                        'status' => 'success',
                        'http_status' => $http_status,
                        'id' => $responseData['id'],
                        'message' => "File {$newFileName} uploaded successfully."
                    );
                }
            }

            unlink($file_path); // Delete the file after successful upload
            curl_close($ch);
        }

        return $responses;
    } else {
        return array(
            'status' => 'error',
            'message' => 'Oops! Please select a file to upload.'
        );
    }
}

// Function to generate a random alphanumeric string - Ver 1.9.9
function generate_random_string($length = 26) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

// Delete old upload files - Ver 1.9.9
function chatbot_chatgpt_cleanup_uploads_directory() {
    $uploads_dir = CHATBOT_CHATGPT_PLUGIN_DIR_PATH . 'uploads/';
    foreach (glob($uploads_dir . '*') as $file) {
        // Delete files older than 1 hour
        if (filemtime($file) < time() - 60 * 60 * 1) {
            unlink($file);
        }
    }
}
add_action('chatbot_chatgpt_cleanup_upload_files', 'chatbot_chatgpt_cleanup_uploads_directory');