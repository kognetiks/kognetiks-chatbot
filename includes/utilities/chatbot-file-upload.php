<?php
/**
 * Kognetiks Chatbot for WordPress - File Uploads - Ver 1.7.6 - Updated for Ver 2.0.1
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
function chatbot_chatgpt_upload_files() {
    global $session_id;

    $uploads_dir = CHATBOT_CHATGPT_PLUGIN_DIR_PATH . 'uploads/';

    // Ensure the directory exists or attempt to create it
    if (!file_exists($uploads_dir) && !wp_mkdir_p($uploads_dir)) {
        $responses[] = array(
            'status' => 'error',
            'message' => 'Oops! File upload failed.'
        );
        back_trace('ERROR', 'Failed to create upload directory.');
        http_response_code(500); // Send a 500 Internal Server Error status code
        exit;
    } else {
        $index_file_path = $uploads_dir . '/index.php';
        if (!file_exists($index_file_path)) {
            $file_content = "<?php\n// Silence is golden.\n\n// Load WordPress Environment\n\$wp_load_path = dirname(__FILE__, 5) . '/wp-load.php';\nif (file_exists(\$wp_load_path)) {\n    require_once(\$wp_load_path);\n} else {\n    exit('Could not find wp-load.php');\n}\n\n// Force a 404 error\nstatus_header(404);\nnocache_headers();\ninclude(get_404_template());\nexit;\n?>";
            file_put_contents($index_file_path, $file_content);
        }
    }
    chmod($uploads_dir, 0700);

    $api_key = esc_attr(get_option('chatbot_chatgpt_api_key'));
    if (empty($api_key)) {
        $responses[] = array(
            'status' => 'error',
            'message' => 'Oops! Your API key is missing. Please enter your API key in the Chatbot settings.'
        );
        back_trace('ERROR', 'API key is missing.');
        http_response_code(500); // Send a 500 Internal Server Error status code
        exit;
    }

    $responses = [];
    $error_flag = false;

    if (isset($_FILES['file']['name']) && is_array($_FILES['file']['name'])) {
        for ($i = 0; $i < count($_FILES['file']['name']); $i++) {
            $newFileName = generate_random_string() . '.' . pathinfo($_FILES['file']['name'][$i], PATHINFO_EXTENSION);
            $file_path = $uploads_dir . $newFileName;
            back_trace('NOTICE', '$file_path: ' . $file_path);

            if ($_FILES['file']['error'][$i] > 0) {
                $responses[] = array(
                    'status' => 'error',
                    'message' => "Oops! Something went wrong during the upload of {$_FILES['file']['name'][$i]}. Please try again later."
                );
                $error_flag = true;
                back_trace('NOTICE', 'Error during file upload.');
                http_response_code(415); // Send a 415 Unsupported Media Type status code
                exit;
            }

            $validation_result = upload_validation(array('name' => $_FILES['file']['name'][$i], 'tmp_name' => $_FILES['file']['tmp_name'][$i]));
            if (is_array($validation_result) && isset($validation_result['error'])) {
                $responses[] = array(
                    'status' => 'error',
                    'message' => $validation_result['error']
                );
                $error_flag = true;
                back_trace('NOTICE', $validation_result['error']);
                http_response_code(415); // Send a 415 Unsupported Media Type status code
                exit;
            }

            if (!move_uploaded_file($_FILES['file']['tmp_name'][$i], $file_path)) {
                $responses[] = array(
                    'status' => 'error',
                    'message' => "Oops! Something went wrong during the upload of {$_FILES['file']['name'][$i]}. Please try again later."
                );
                $error_flag = true;
                back_trace('NOTICE', 'Error during file upload.');
                http_response_code(415); // Send a 415 Unsupported Media Type status code
                exit;
            }

            // ***************************************************************************
            // DECIDE IF THE UPLOADED FILE IS AN IMAGE OR NON-IMAGE
            // ***************************************************************************
            
            $file_mime_type = mime_content_type($file_path);
            $purpose = strpos($file_mime_type, 'image/') === 0 ? 'vision' : 'assistants';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, get_files_api_url());
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $api_key));
            
            // Always send as multipart/form-data
            $post_fields = [
                'purpose' => $purpose, // Set purpose based on file type
                'file' => new CURLFile($file_path)
            ];
            
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);

            $response = curl_exec($ch);
            $http_status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            if (curl_errno($ch)) {
                $responses[] = array(
                    'status' => 'error',
                    'http_status' => $http_status,
                    'message' => 'Error:' . curl_error($ch)
                );
                back_trace('ERROR', 'CURL error: ' . curl_error($ch));
            } else {
                $responseData = json_decode($response, true);
                if ($http_status != 200 || isset($responseData['error'])) {
                    $errorMessage = $responseData['error']['message'] ?? 'Unknown error occurred.';
                    $responses[] = array(
                        'status' => 'error',
                        'http_status' => $http_status,
                        'message' => $errorMessage
                    );
                    back_trace('ERROR', 'API error: ' . $errorMessage);
                } else {
                    set_chatbot_chatgpt_transients_files('chatbot_chatgpt_assistant_file_id', $responseData['id'], $session_id, $i);
                    $responses[] = array(
                        'status' => 'success',
                        'http_status' => $http_status,
                        'id' => $responseData['id'],
                        'message' => 'File ' . $newFileName . ' uploaded successfully.'
                    );
                    back_trace('NOTICE', 'File ' . $newFileName . ' uploaded successfully. ID: ' . $responseData['id']);
                }
            }

            unlink($file_path); // Delete the file after successful upload
            curl_close($ch);
        }

        return $responses;

    } else {
        back_trace('ERROR', 'No files selected for upload.');
        return array(
            'status' => 'error',
            'message' => 'Oops! Please select a file to upload.'
        );
    }
}


// Handle Large Files - Ver 2.0.3
function upload_file_in_chunks($file_path, $api_key, $file_name, $file_type) {

    $chunk_size = 1024 * 1024; // 1MB
    $file_size = filesize($file_path);
    $handle = fopen($file_path, "rb");

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, get_files_api_url());
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $api_key));

    $chunk_number = 0;
    while (!feof($handle)) {
        $chunk_data = fread($handle, $chunk_size);
        $base64_encoded_chunk = base64_encode($chunk_data);
        $post_fields = [
            'purpose' => 'assistants',
            'file' => $base64_encoded_chunk,
            'file_name' => $file_name,
            'file_type' => $file_type,
            'chunk_number' => $chunk_number,
            'total_chunks' => ceil($file_size / $chunk_size)
        ];

        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);

        $response = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        if (curl_errno($ch)) {
            back_trace('ERROR', 'CURL error during chunk upload: ' . curl_error($ch));
            return false;
        }

        $responseData = json_decode($response, true);
        if ($http_status != 200 || isset($responseData['error'])) {
            $errorMessage = $responseData['error']['message'] ?? 'Unknown error occurred.';
            back_trace('ERROR', 'API error during chunk upload: ' . $errorMessage);
            return false;
        }

        $chunk_number++;
    }

    fclose($handle);
    curl_close($ch);

    return true;

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
        back_trace ( 'ERROR', 'Failed to create results directory.');
        $responses[] = array(
            'status' => 'error',
            'message' => 'Oops! File upload failed.'
        );
        back_trace ( 'ERROR', 'File upload failed');
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
            back_trace( 'NOTICE', '$file_path: ' . $file_path);

            if ($_FILES['file']['error'][$i] > 0) {
                $responses[] = array(
                    'status' => 'error',
                    'message' => "Oops! Something went wrong during the upload of {$_FILES['file']['name'][$i]}. Please try again later."
                );
                $error_flag = true;
                back_trace( 'NOTICE', 'Error during file upload.');
                http_response_code(415); // Send a 415 Unsupported Media Type status code
                exit;
            }

            // Check for allow video and audio file types
            // $video_file_types = array('video/mp4', 'video/ogg', 'video/webm');
            // $audio_file_types = array('audio/mpeg', 'audio/mp3', 'audio/ogg', 'audio/wav');

            // $allowed_file_types = array_merge($audio_file_types, $video_file_types);

            // if (!in_array($_FILES['file']['type'][$i], $allowed_file_types)) {
            //     $responses[] = array(
            //         'status' => 'error',
            //         'message' => 'Invalid file type. Please upload an MP3, WAV, MP4, or WEBM file.'
            //     );
            //     $error_flag = true;
            //     back_trace( 'NOTICE', 'Invalid file type.');
            //     http_response_code(415); // Send a 415 Unsupported Media Type status code
            //     exit;
            // }

            // Checked for valid upload file types
            $validation_result = upload_validation(array('name' => $_FILES['file']['name'][$i], 'tmp_name' => $_FILES['file']['tmp_name'][$i]));
            if (is_array($validation_result) && isset($validation_result['error'])) {
                $responses[] = array(
                    'status' => 'error',
                    'message' => $validation_result['error']
                );
                $error_flag = true;
                back_trace( 'ERROR', $validation_result['error']);
                http_response_code(415); // Send a 415 Unsupported Media Type status code
                exit;
            }

            if (!move_uploaded_file($_FILES['file']['tmp_name'][$i], $file_path)) {
                $responses[] = array(
                    'status' => 'error',
                    'message' => "Oops! Something went wrong during the upload of {$_FILES['file']['name'][$i]}. Please try again later."
                );
                $error_flag = true;
                back_trace( 'NOTICE', 'Error during file upload.');
                http_response_code(415); // Send a 415 Unsupported Media Type status code
                exit;

            }
        }

        if ($error_flag == true) {
            back_trace( 'NOTICE', '$error_flag: ' . $error_flag);
            http_response_code(403); // Send a 403 Forbidden status code
            return $responses;
        }

        // Save the file name for later
        // DIAG - Diagnostics - Ver 2.0.1
        back_trace( 'NOTICE', '$newFileName: ' . $newFileName);
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

function create_index_file($directory) {
    $index_file_path = $directory . 'index.php';

    // Check if the index.php file already exists
    if (!file_exists($index_file_path)) {
        // Create the index.php file
        $file = fopen($index_file_path, 'w');

        // Check if the file was successfully opened
        if ($file) {
            // Write a simple message to the file
            fwrite($file, "<?php\n// Silence is golden.\n");
            fclose($file);
        } else {
            // Handle the error
            error_log("Failed to create index.php file in directory: $directory");
        }
    }
}

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
        back_trace( 'ERROR', 'Invalid file type or extension.');
        return $file;
    }

    // Define file types for which to perform a deep content check
    $deep_check_types = array('text/csv', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain', 'application/xml', 'application/json', 'text/markdown');

    // Only perform deep content check for certain file types
    // if (in_array($file_type['type'], $deep_check_types)) {
        // $file_content = file_get_contents($file['tmp_name']);
        // $content_check_result = deep_content_check($file_path);
        $content_check_result = deep_content_check($file['tmp_name']);
        
        if ($content_check_result !== true) {
            $file['error'] = $content_check_result;
            back_trace( 'ERROR', $content_check_result);
            return $file;
        }
    back_trace( 'NOTICE', 'File type and extension are allowed.');

    // If there's no error, return the file without the 'error' key
    unset($file['error']);

    return $file;

}
add_filter('wp_handle_upload_prefilter', 'upload_validation');

// Deep content-based security checks
function deep_content_check($file_path) {
    // Define patterns to look for potentially dangerous content
    $patterns = [
        '/<\?php/i',                            // PHP opening tag
        '/<script\b[^>]*>(.*?)<\/script>/is',   // Script tags with content
        '/<svg\b[^>]*>(.*?)<\/svg>/is',         // SVG tags with potential content
        '/onerror\s*=/i',                       // Onerror attribute
        '/onload\s*=/i',                        // Onload attribute
        '/data:/i',                             // Data URIs
        '/eval\s*\(/i',                         // Eval function
        '/base64,/i',                           // Base64 data
        '/<iframe\b[^>]*>(.*?)<\/iframe>/is',   // Iframe tags
        '/<object\b[^>]*>(.*?)<\/object>/is',   // Object tags
        '/<embed\b[^>]*>(.*?)<\/embed>/is',     // Embed tags
        '/<applet\b[^>]*>(.*?)<\/applet>/is',   // Applet tags
        '/<meta\b[^>]*>/i',                     // Meta tags
    ];

    $handle = fopen($file_path, 'r');
    if ($handle === false) {
        return 'Security error: Unable to read the file.';
    }

    while (!feof($handle)) {
        $chunk = fread($handle, 8192);  // Read in 8KB chunks
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $chunk)) {
                fclose($handle);
                return 'Security error: Potentially dangerous content found.';
            }
        }
    }

    fclose($handle);

    return true;

}