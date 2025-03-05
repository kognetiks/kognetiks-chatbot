<?php
/**
 * Kognetiks Chatbot - File Uploads - Ver 1.7.6 - Updated for Ver 2.0.1
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

// Upload Multiple files to the Assistant
function chatbot_chatgpt_upload_files() {

    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;

    global $kchat_settings;
    global $additional_instructions;
    global $model;
    global $voice;
    
    if (empty($session_id) || $session_id == 0) {
        $session_id = isset($_POST['session_id']) ? $_POST['session_id'] : null;
        $user_id = isset($_POST['user_id']) ? $_POST['user_id'] : null;
    }
    
    global $chatbot_chatgpt_display_style;
    global $chatbot_chatgpt_assistant_alias;

    global $chatbot_chatgpt_plugin_dir_path;

    $uploads_dir = $chatbot_chatgpt_plugin_dir_path . 'uploads/';

    // DIAG - Diagnostics - Ver 2.2.6
    back_trace( 'NOTICE', '$uploads_dir: ' . $uploads_dir );

    // Ensure the directory exists or attempt to create it
    if (!file_exists($uploads_dir) && !wp_mkdir_p($uploads_dir)) {
        $default_message = 'Oops! File upload failed.';
        $error_message = !empty($chatbot_chatgpt_fixed_literal_messages[2]) 
            ? $chatbot_chatgpt_fixed_literal_messages[2] 
            : $default_message;
        $responses[] = array(
            'status' => 'error',
            'message' => $error_message
        );
        // back_trace( 'ERROR', 'Failed to create upload directory.');
        http_response_code(500); // Send a 500 Internal Server Error status code
        exit;
    } else {
        $index_file_path = $uploads_dir . '/index.php';
        if (!file_exists($index_file_path)) {
            $file_content = "<?php\n// Silence is golden.\n\n";
            file_put_contents($index_file_path, $file_content);
        }
    }
    chmod($uploads_dir, 0700);

    // Which API key to use?
    $ai_platform_choice = esc_attr(get_option('chatbot_ai_platform_choice'), 'OpenAI');
    if ($ai_platform_choice == 'OpenAI') {
        $api_key = esc_attr(get_option('chatbot_chatgpt_api_key'));
    } elseif ($ai_platform_choice == 'Azure OpenAI') {
        $api_key = esc_attr(get_option('chatbot_azure_api_key'));
    } elseif ($ai_platform_choice == 'NVIDIA') {
        $api_key = esc_attr(get_option('chatbot_nvidia_api_key'));
    } elseif ($ai_platform_choice == 'Anthropic') {
        $api_key = esc_attr(get_option('chatbot_anthropic_api_key'));
    } elseif ($ai_platform_choice == 'DeepSeek') {
        $api_key = esc_attr(get_option('chatbot_deepseek_api_key'));
    } elseif ($ai_platform_choice == 'Local Server') {
        $api_key = esc_attr(get_option('chatbot_local_api_key'));
    }
    if (empty($api_key)) {
        $default_message = 'Oops! Your API key is missing. Please enter your API key in the Chatbot settings.';
        $error_message = !empty($chatbot_chatgpt_fixed_literal_messages[3]) 
            ? $chatbot_chatgpt_fixed_literal_messages[3] 
            : $default_message;
        $responses[] = array(
            'status' => 'error',
            'message' => $error_message
        );
        // back_trace( 'ERROR', 'API key is missing.');
        http_response_code(500); // Send a 500 Internal Server Error status code
        exit;
    }

    $responses = [];
    $error_flag = false;

    if (isset($_FILES['file']['name']) && is_array($_FILES['file']['name'])) {
        for ($i = 0; $i < count($_FILES['file']['name']); $i++) {
            $newFileName = generate_random_string() . '.' . pathinfo($_FILES['file']['name'][$i], PATHINFO_EXTENSION);
            $file_path = $uploads_dir . $newFileName;
            // back_trace( 'NOTICE', '$file_path: ' . $file_path);

            if ($_FILES['file']['error'][$i] > 0) {
                $error_message = !empty($chatbot_chatgpt_fixed_literal_messages[4]) 
                    ? $chatbot_chatgpt_fixed_literal_messages[4] 
                    : "Oops! Something went wrong during the upload of {$_FILES['file']['name'][$i]}. Please try again later.";

                $responses[] = [
                    'status' => 'error',
                    'message' => $error_message
                ];
                $error_flag = true;
                // back_trace( 'ERROR', 'Error during file upload.');
                // Send a 415 Unsupported Media Type status code
                wp_send_json_error($responses, 415);
            }

            // Validate file
            $validation_result = upload_validation([
                'name' => basename($_FILES['file']['name'][$i]),
                'tmp_name' => $_FILES['file']['tmp_name'][$i]
            ]);

            if (is_array($validation_result) && isset($validation_result['error'])) {
                $responses[] = [
                    'status' => 'error',
                    'message' => $validation_result['error']
                ];
                $error_flag = true;
                // back_trace( 'NOTICE', $validation_result['error']);
                // Send a 415 Unsupported Media Type status code
                wp_send_json_error($responses, 415);
            }

            // Move file to uploads directory
            if (!move_uploaded_file($_FILES['file']['tmp_name'][$i], $file_path)) {
                $error_message = !empty($chatbot_chatgpt_fixed_literal_messages[4]) 
                    ? $chatbot_chatgpt_fixed_literal_messages[4] 
                    : "Oops! Something went wrong during the upload of {$_FILES['file']['name'][$i]}. Please try again later.";

                $responses[] = [
                    'status' => 'error',
                    'message' => $error_message
                ];
                $error_flag = true;
                // back_trace( 'NOTICE', 'Error during file upload.');
                // Send a 415 Unsupported Media Type status code
                wp_send_json_error($responses, 415);
            }

            // Determine file type
            $file_mime_type = mime_content_type($file_path);
            $purpose = (strpos($file_mime_type, 'image/') === 0) ? 'vision' : 'assistants';

            // Prepare API request
            $api_url = get_files_api_url();

            // DIAG - Diagnostics - Ver 2.2.6
            back_trace( 'NOTICE', '$api_url: ' . $api_url );

            // Open file in a way that works with WP HTTP API
            $filename = basename($file_path);
            $file_mime_type = mime_content_type($file_path);
            $file_data = file_get_contents($file_path);

            $boundary = wp_generate_password(24); // Generate a unique boundary for multipart encoding

            // Construct multipart body manually
            $body = "--{$boundary}\r\n";
            $body .= "Content-Disposition: form-data; name=\"purpose\"\r\n\r\n";
            $body .= "{$purpose}\r\n";
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Disposition: form-data; name=\"file\"; filename=\"{$filename}\"\r\n";
            $body .= "Content-Type: {$file_mime_type}\r\n\r\n";
            $body .= $file_data . "\r\n";
            $body .= "--{$boundary}--\r\n";

            // Set up HTTP request arguments
            $args = [
                'method'    => 'POST',
                'headers'   => [
                    'Authorization'  => 'Bearer ' . $api_key,
                    'Content-Type'   => 'multipart/form-data; boundary=' . $boundary
                ],
                'body'      => $body,
                'timeout'   => 30,
            ];

            // Send request using WP HTTP API
            $response = wp_remote_post($api_url, $args);

            // Check for errors
            if (is_wp_error($response)) {
                $responses[] = [
                    'status' => 'error',
                    'message' => 'API Error: ' . $response->get_error_message()
                ];
                $error_flag = true;
                unlink($file_path); // Cleanup file
                continue;
            }

            $http_status = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            $responseData = json_decode($response_body, true);

            // Handle API errors
            if ($http_status != 200 || isset($responseData['error'])) {
                $errorMessage = $responseData['error']['message'] ?? 'Unknown error occurred.';
                $responses[] = [
                    'status' => 'error',
                    'http_status' => $http_status,
                    'message' => $errorMessage
                ];
                unlink($file_path); // Cleanup file
                continue;
            }

            // Store API response
            set_chatbot_chatgpt_transients_files('chatbot_chatgpt_assistant_file_id', $responseData['id'], $session_id, $i);
            set_chatbot_chatgpt_transients_files('chatbot_chatgpt_assistant_file_id', $purpose, $session_id, $responseData['id']);

            $responses[] = [
                'status' => 'success',
                'http_status' => $http_status,
                'id' => $responseData['id'],
                'message' => 'File ' . $newFileName . ' uploaded successfully.'
            ];

            // Delete file after successful upload
            unlink($file_path);
        }

        
        return $responses;

    } else {

        // back_trace( 'ERROR', 'No files selected for upload.');
        global $chatbot_chatgpt_fixed_literal_messages;
        // Define a default fallback message
        $default_message = 'Oops! Please select a file to upload.';
        $error_message = isset($chatbot_chatgpt_fixed_literal_messages[5]) 
            ? $chatbot_chatgpt_fixed_literal_messages[5] 
            : $default_message;
        return array(
            'status' => 'error',
            'message' => $error_message
        );

    }

}

// Handle Large Files - Ver 2.0.3
function upload_file_in_chunks($file_path, $api_key, $file_name, $file_type) {

    $chunk_size = 1024 * 1024; // 1MB
    $file_size = filesize($file_path);
    $handle = fopen($file_path, "rb");

    if (!$handle) {
        prod_trace( 'ERROR', 'Unable to open file for reading.');
        return false;
    }

    // Get the API URL
    $url = get_files_api_url();

    // DIAG - Diagnostics - Ver 2.2.6
    back_trace( 'NOTICE', '$url: ' . $url );

    $chunk_number = 0;
    $total_chunks = ceil($file_size / $chunk_size);

    while (!feof($handle)) {
        // Read chunk of data
        $chunk_data = fread($handle, $chunk_size);
        if ($chunk_data === false) {
            prod_trace( 'ERROR', 'Failed to read file chunk.');
            fclose($handle);
            return false;
        }

        // Base64 encode the chunk
        $base64_encoded_chunk = base64_encode($chunk_data);

        // Prepare POST fields
        $post_fields = [
            'purpose'       => 'assistants',
            'file'          => $base64_encoded_chunk,
            'file_name'     => $file_name,
            'file_type'     => $file_type,
            'chunk_number'  => $chunk_number,
            'total_chunks'  => $total_chunks
        ];

        // Set up HTTP request arguments
        $args = [
            'method'    => 'POST',
            'headers'   => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json'
            ],
            'body'      => json_encode($post_fields),
            'timeout'   => 30 // Prevent long wait times
        ];

        // Send request
        $response = wp_remote_post($url, $args);

        // Check for errors
        if (is_wp_error($response)) {
            prod_trace( 'ERROR', 'Error during chunk upload: ' . $response->get_error_message());
            fclose($handle);
            return false;
        }

        // Retrieve HTTP response code
        $http_status = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $responseData = json_decode($response_body, true);

        // Check if the API returned an error
        if ($http_status != 200 || isset($responseData['error'])) {
            $errorMessage = $responseData['error']['message'] ?? 'Unknown error occurred.';
            prod_trace( 'ERROR', 'API error during chunk upload: ' . $errorMessage);
            fclose($handle);
            return false;
        }

        $chunk_number++;

    }

    fclose($handle);

    return true;

}

// Upload files - Ver 2.0.1
function chatbot_chatgpt_upload_mp3() {

    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;

    global $kchat_settings;
    global $additional_instructions;
    global $model;
    global $voice;
    
    // Fetch the User ID - Updated Ver 2.0.6 - 2024 07 11
    $user_id = get_current_user_id();
    // Fetch the Kognetiks cookie
    if (empty($session_id) || $session_id == 0) {
        $session_id = kognetiks_get_unique_id();
    }
    // $session_id = kognetiks_get_unique_id();
    if (empty($user_id) || $user_id == 0) {
        $user_id = $session_id;
    }

    global $chatbot_chatgpt_display_style;
    global $chatbot_chatgpt_assistant_alias;

    global $chatbot_chatgpt_plugin_dir_path;

    $uploads_dir = $chatbot_chatgpt_plugin_dir_path . 'uploads/';

    // Ensure the directory exists or attempt to create it
    if (!file_exists($uploads_dir) && !wp_mkdir_p($uploads_dir)) {
        // Error handling, e.g., log the error or handle the failure appropriately
        // back_trace( 'ERROR', 'Failed to create results directory.');
        $responses[] = array(
            'status' => 'error',
            'message' => 'Oops! File upload failed.'
        );
        // back_trace( 'ERROR', 'File upload failed');
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
                global $chatbot_chatgpt_fixed_literal_messages;
                // Define a default fallback message
                $default_message = "Oops! Something went wrong during the upload of {$_FILES['file']['name'][$i]}. Please try again later.";
                $error_message = isset($chatbot_chatgpt_fixed_literal_messages[4]) 
                    ? $chatbot_chatgpt_fixed_literal_messages[4] 
                    : $default_message;
                $responses[] = array(
                    'status' => 'error',
                    'message' => $error_message
                );
                $error_flag = true;
                // back_trace( 'NOTICE', 'Error during file upload.');
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
            //     // back_trace( 'NOTICE', 'Invalid file type.');
            //     http_response_code(415); // Send a 415 Unsupported Media Type status code
            //     exit;
            // }

            // Checked for valid upload file types
            // $validation_result = upload_validation(array('name' => $_FILES['file']['name'][$i], 'tmp_name' => $_FILES['file']['tmp_name'][$i]));
            $validation_result = upload_validation(array('name' => basename($_FILES['file']['name'][$i]), 'tmp_name' => $_FILES['file']['tmp_name'][$i]));
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
        // back_trace( 'ERROR', 'No files selected for upload.');
        global $chatbot_chatgpt_fixed_literal_messages;
        // Define a default fallback message
        $default_message = 'Oops! Please select a file to upload.';
        $error_message = isset($chatbot_chatgpt_fixed_literal_messages[5]) 
            ? $chatbot_chatgpt_fixed_literal_messages[5] 
            : $default_message;
        return array(
            'status' => 'error',
            'message' => $error_message
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

    global $chatbot_chatgpt_plugin_dir_path;
    
    $uploads_dir = $chatbot_chatgpt_plugin_dir_path . 'uploads/';
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
    // Ensure the directory ends with a slash
    $directory = rtrim($directory, '/') . '/';

    // Check if the directory exists, if not, create it
    if (!is_dir($directory)) {
        if (!mkdir($directory, 0755, true)) {
            // If the directory could not be created, log an error and exit the function
            error_log("Chatbot-Chatgpt - Failed to create directory: " . $directory);
            return;
        }
    }

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
            error_log("Chatbot-Chatgpt - Failed to create index.php file in directory: " . $directory);
        }
    }
}

// File type validation - Ver 2.0.1
function upload_validation($file) {


    // DIAG - Diagnostics - Ver 2.0.7
    // back_trace( 'NOTICE', 'File name: ' . $file['name']);
    // back_trace( 'NOTICE', 'basename: ' . basename($file['name']));

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
        'webp' => 'image/webp',
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
    // if (in_array($file_type['type'], $deep_check_types)) {
        // $file_content = file_get_contents($file['tmp_name']);
        // $content_check_result = deep_content_check($file_path);
        $content_check_result = deep_content_check($file['tmp_name']);
        
        if ($content_check_result !== true) {
            $file['error'] = $content_check_result;
            // back_trace( 'ERROR', $content_check_result);
            return $file;
        }
    // back_trace( 'NOTICE', 'File type and extension are allowed.');

    // If there's no error, return the file without the 'error' key
    unset($file['error']);

    return $file;

}
// add_filter('wp_handle_upload_prefilter', 'upload_validation'); // REMOVED IN VER 2.0.7 - THE FILTER INTERFERES WITH WP CORE FUNCTIONS

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
