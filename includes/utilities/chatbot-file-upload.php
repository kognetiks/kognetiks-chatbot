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
function chatbot_chatgpt_upload_files(): array {

    global $session_id;

    $uploads_dir = CHATBOT_CHATGPT_PLUGIN_DIR_PATH . 'uploads/';

    // Ensure the directory exists or attempt to create it
    if (!file_exists($uploads_dir) && !wp_mkdir_p($uploads_dir)) {
        // Error handling, e.g., log the error or handle the failure appropriately
        // back_trace ( 'ERROR', 'Failed to create results directory.')
        $responses[] = array(
            'status' => 'error',
            'message' => 'Oops! File upload failed.'
        );
        // back_trace ( 'ERROR', 'File upload failed');
        http_response_code(500); // Send a 500 Internal Server Error status code
        exit;
    } else {
        $index_file_path = $uploads_dir . '/index.php';
        if (!file_exists($index_file_path)) {
            $file_content = "<?php\n// Silence is golden.\n?>";
            file_put_contents($index_file_path, $file_content);
        }
    }
    // Protect the directory - Ver 2.0.0
    chmod($uploads_dir, 0700);

    $api_key = esc_attr(get_option('chatbot_chatgpt_api_key'));
    if (empty($api_key)) {
        $responses[] = array(
            'status' => 'error',
            'message' => 'Oops! Your API key is missing. Please enter your API key in the Chatbot settings.'
        );
        // back_trace ( 'ERROR', 'API key is missing.');
        http_response_code(500); // Send a 500 Internal Server Error status code
        exit;
    }

    $responses = [];
    $error_flag = false;

    // Check if files were uploaded
    if (isset($_FILES['file']['name']) && is_array($_FILES['file']['name'])) {
        for ($i = 0; $i < count($_FILES['file']['name']); $i++) {
            // Generate a random file name
            $newFileName = generate_random_string() . '.' . pathinfo($_FILES['file']['name'][$i], PATHINFO_EXTENSION);
            $file_path = $uploads_dir . $newFileName;

            // DIAG - Diagnostics - Ver 2.0.1
            // back_trace( 'NOTICE', '$file_path: ' . $file_path);

            if ($_FILES['file']['error'][$i] > 0) {
                $responses[] = array(
                    'status' => 'error',
                    'message' => "Oops! Something went wrong during the upload of {$_FILES['file']['name'][$i]}. Please try again later."
                );
                $error_flag = true;
                // back_trace( 'NOTICE', 'Error during file upload.');
                http_response_code(415); // Send a 415 Unsupported Media Type status code
                exit;
            }

            // Checked for valid upload file types
            $validation_result = upload_validation(array('name' => $_FILES['file']['name'][$i], 'tmp_name' => $_FILES['file']['tmp_name'][$i]));
            if (is_array($validation_result) && isset($validation_result['error'])) {
                $responses[] = array(
                    'status' => 'error',
                    'message' => $validation_result['error']
                );
                $error_flag = true;
                // back_trace( 'NOTICE', $validation_result['error']);
                http_response_code(415); // Send a 415 Unsupported Media Type status code
                exit;
            }

            if (!move_uploaded_file($_FILES['file']['tmp_name'][$i], $file_path)) {
                $responses[] = array(
                    'status' => 'error',
                    'message' => "Oops! Something went wrong during the upload of {$_FILES['file']['name'][$i]}. Please try again later."
                );
                $error_flag = true;
                // back_trace( 'NOTICE', 'Error during file upload.');
                http_response_code(415); // Send a 415 Unsupported Media Type status code
                exit;
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
                        'message' => 'File ' . $newFileName . 'uploaded successfully.'
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

// Upload files - Ver 2.0.1
function chatbot_chatgpt_upload_mp3() {

    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;
    global $script_data_array;
    global $additional_instructions;
    global $model;
    global $voice;

    global $chatbot_chatgpt_display_style;
    global $chatbot_chatgpt_assistant_alias;

    $uploads_dir = CHATBOT_CHATGPT_PLUGIN_DIR_PATH . 'uploads/';

    // Ensure the directory exists or attempt to create it
    if (!file_exists($uploads_dir) && !wp_mkdir_p($uploads_dir)) {
        // Error handling, e.g., log the error or handle the failure appropriately
        // back_trace ( 'ERROR', 'Failed to create results directory.')
        $responses[] = array(
            'status' => 'error',
            'message' => 'Oops! File upload failed.'
        );
        // back_trace ( 'ERROR', 'File upload failed');
        http_response_code(500); // Send a 500 Internal Server Error status code
        exit;
    } else {
        $index_file_path = $uploads_dir . '/index.php';
        if (!file_exists($index_file_path)) {
            $file_content = "<?php\n// Silence is golden.\n?>";
            file_put_contents($index_file_path, $file_content);
        }
    }
    // Protect the directory - Ver 2.0.0
    chmod($uploads_dir, 0700);

    $responses = [];
    $error_flag = false;

    // Check if files were uploaded
    if (isset($_FILES['file']['name']) && is_array($_FILES['file']['name'])) {
        for ($i = 0; $i < count($_FILES['file']['name']); $i++) {
            // Generate a random file name
            $newFileName = generate_random_string() . '.' . pathinfo($_FILES['file']['name'][$i], PATHINFO_EXTENSION);
            $file_path = $uploads_dir . $newFileName;

            // DIAG - Diagnostics - Ver 2.0.1
            // back_trace( 'NOTICE', '$file_path: ' . $file_path);

            if ($_FILES['file']['error'][$i] > 0) {
                $responses[] = array(
                    'status' => 'error',
                    'message' => "Oops! Something went wrong during the upload of {$_FILES['file']['name'][$i]}. Please try again later."
                );
                $error_flag = true;
                // back_trace( 'NOTICE', 'Error during file upload.');
                http_response_code(415); // Send a 415 Unsupported Media Type status code
                exit;
            }

            // Check for allow video and audio file types
            $video_file_types = array('video/mp4', 'video/ogg', 'video/webm');
            $audio_file_types = array('audio/mpeg', 'audio/mp3', 'audio/ogg', 'audio/wav');

            $allowed_file_types = array_merge($audio_file_types, $video_file_types);

            if (!in_array($_FILES['file']['type'][$i], $allowed_file_types)) {
                $responses[] = array(
                    'status' => 'error',
                    'message' => 'Invalid file type. Please upload an MP3, WAV, MP4, or WEBM file.'
                );
                $error_flag = true;
                // back_trace( 'NOTICE', 'Invalid file type.');
                http_response_code(415); // Send a 415 Unsupported Media Type status code
                exit;
            }

            // Checked for valid upload file types
            $validation_result = upload_validation(array('name' => $_FILES['file']['name'][$i], 'tmp_name' => $_FILES['file']['tmp_name'][$i]));
            if (is_array($validation_result) && isset($validation_result['error'])) {
                $responses[] = array(
                    'status' => 'error',
                    'message' => $validation_result['error']
                );
                $error_flag = true;
                // back_trace( 'ERROR', $validation_result['error']);
                http_response_code(415); // Send a 415 Unsupported Media Type status code
                exit;
            }

            if (!move_uploaded_file($_FILES['file']['tmp_name'][$i], $file_path)) {
                $responses[] = array(
                    'status' => 'error',
                    'message' => "Oops! Something went wrong during the upload of {$_FILES['file']['name'][$i]}. Please try again later."
                );
                $error_flag = true;
                // back_trace( 'NOTICE', 'Error during file upload.');
                http_response_code(415); // Send a 415 Unsupported Media Type status code
                exit;

            }
        }

        if ($error_flag == true) {
            // back_trace( 'NOTICE', '$error_flag: ' . $error_flag);
            http_response_code(403); // Send a 403 Forbidden status code
            return $responses;
        }

        // Save the file name for later
        // DIAG - Diagnostics - Ver 2.0.1
        // back_trace( 'NOTICE', '$newFileName: ' . $newFileName);
        set_chatbot_chatgpt_transients_files('chatbot_chatgpt_assistant_file_id', $newFileName, $session_id, $i);
        $responses[] = array(
            'status' => 'success',
            'message' => "File uploaded successfully."
        );
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
    // Create the index.php file if it does not exist
    create_index_file($uploads_dir);
}
add_action('chatbot_chatgpt_cleanup_upload_files', 'chatbot_chatgpt_cleanup_uploads_directory');

// File type validation - Ver 2.0.1
function upload_validation($file) {

    // Get the file type from the file name.
    $file_type = wp_check_filetype($file['name']);

    // Whisper
    // File uploads are currently limited to 25 MB and the following input file types are supported: 
    // mp3, mp4, mpeg, mpga, m4a, wav, and webm.

    // Supported file types
    // https://platform.openai.com/docs/assistants/tools/file-search/supported-files

    // Extended allowed file extensions and MIME types
    $allowed_types = array(
        'csv' => 'text/csv',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'gif' => 'image/gif',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'mp3' => 'audio/mpeg',
        'mp4' => 'video/mp4',
        'mpeg' => 'video/mpeg',
        'mpga' => 'audio/mpeg',
        'm4a' => 'audio/m4a',
        'pdf' => 'application/pdf',
        'png' => 'image/png',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'rtf' => 'application/rtf',
        'svg' => 'image/svg+xml',
        'txt' => 'text/plain',
        'wav' => 'audio/wav',
        'webm' => 'video/webm',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xml' => 'application/xml',
        'json' => 'application/json',
        'md' => 'text/markdown',
        'zip' => 'application/zip',
    );

    // Check if the file type and extension are allowed
    if (!array_key_exists($file_type['ext'], $allowed_types) || $allowed_types[$file_type['ext']] != $file_type['type']) {
        $file['error'] = 'Invalid file type or extension.';
        // back_trace( 'ERROR', 'Invalid file type or extension.');
        return $file;
    }

    // Define file types for which to perform a deep content check
    $deep_check_types = array('text/csv', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain', 'application/xml', 'application/json', 'text/markdown');

    // Only perform deep content check for certain file types
    if (in_array($file_type['type'], $deep_check_types)) {
        $file_content = file_get_contents($file['tmp_name']);
        $content_check_result = deep_content_check($file_content);
        
        if ($content_check_result !== true) {
            $file['error'] = $content_check_result;
            // back_trace( 'ERROR', $content_check_result);
            return $file;
        }
    }

    // back_trace( 'NOTICE', 'File type and extension are allowed.');

    // If there's no error, return the file without the 'error' key
    unset($file['error']);

    return $file;

}
add_filter('wp_handle_upload_prefilter', 'upload_validation');

// Deep content-based security checks
function deep_content_check($file_content) {

    $patterns = ['/\<\?php/i', '/\<script\>/i', '/\<svg/i', '/onerror/i', '/onload/i', '/data:/i', '/eval\(/i'];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $file_content)) {
            return 'Security error: Dangerous content detected!';
        }
    }

    return true;
    
}
