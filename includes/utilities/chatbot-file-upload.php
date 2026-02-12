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

/**
 * Debug helper for file upload to OpenAI: logs endpoint, status, body, payload keys (not file contents), path, size, mime.
 * Call only when WP_DEBUG is enabled to avoid leaking paths in production.
 *
 * @param string $endpoint  URL posted to.
 * @param int    $status    HTTP status code.
 * @param string $body      Response body (e.g. from wp_remote_retrieve_body).
 * @param array  $payload_keys Keys sent in the request (e.g. ['purpose', 'file' => 'filename.ext']). Do not log file contents.
 * @param string $file_path  Local path to the uploaded file.
 * @param int    $filesize   filesize($file_path).
 * @param string $mime       MIME type of the file.
 */
function chatbot_file_upload_debug_log( $endpoint, $status, $body, $payload_keys, $file_path, $filesize, $mime ) {
    if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG || ! function_exists( 'back_trace' ) ) {
        return;
    }
    $safe_keys = $payload_keys;
    if ( isset( $safe_keys['file'] ) && $safe_keys['file'] instanceof \CURLFile ) {
        $safe_keys['file'] = '[CURLFile: ' . basename( $safe_keys['file']->getFilename() ) . ', mime=' . $safe_keys['file']->getMimeType() . ']';
    }
    // back_trace( 'NOTICE', sprintf(
    //     'OpenAI file upload: endpoint=%s, status=%s, body_length=%d, payload_keys=%s, file_path=%s, filesize=%d, mime=%s',
    //     $endpoint,
    //     (string) $status,
    //     strlen( $body ),
    //     wp_json_encode( $safe_keys ),
    //     $file_path,
    //     (int) $filesize,
    //     $mime
    // ) );
    // back_trace( 'NOTICE', 'OpenAI file upload response body (first 500 chars): ' . substr( $body, 0, 500 ) );
}

// Upload Multiple files to the Assistant
function chatbot_chatgpt_upload_files() {

    // Security: Check if user has permission to upload files
    if (!current_user_can('upload_files')) {
        wp_send_json_error('Insufficient permissions to upload files.', 403);
        return;
    }

    // Security: Verify nonce for CSRF protection
    if (!isset($_POST['chatbot_nonce']) || !wp_verify_nonce($_POST['chatbot_nonce'], 'chatbot_upload_nonce')) {
        wp_send_json_error('Security check failed. Please refresh the page and try again.', 403);
        return;
    }

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
        $session_id = isset($_POST['session_id']) ? sanitize_text_field(wp_unslash($_POST['session_id'])) : null;
        $user_id = isset($_POST['user_id']) ? sanitize_text_field(wp_unslash($_POST['user_id'])) : null;
    }
    
    global $chatbot_chatgpt_display_style;
    global $chatbot_chatgpt_assistant_alias;

    global $chatbot_chatgpt_plugin_dir_path;

    $uploads_dir = $chatbot_chatgpt_plugin_dir_path . 'uploads/';

    // DIAG - Diagnostics - Ver 2.2.6

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
        // Decrypt the API key - Ver 2.2.6
        $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
    } elseif ($ai_platform_choice == 'Azure OpenAI') {
        $api_key = esc_attr(get_option('chatbot_azure_api_key'));
        // Decrypt the API key - Ver 2.2.6
        $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
    } elseif ($ai_platform_choice == 'NVIDIA') {
        $api_key = esc_attr(get_option('chatbot_nvidia_api_key'));
        // Decrypt the API key - Ver 2.2.6
        $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
    } elseif ($ai_platform_choice == 'Anthropic') {
        $api_key = esc_attr(get_option('chatbot_anthropic_api_key'));
        // Decrypt the API key - Ver 2.2.6
        $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
    } elseif ($ai_platform_choice == 'DeepSeek') {
        $api_key = esc_attr(get_option('chatbot_deepseek_api_key'));
        // Decrypt the API key - Ver 2.2.6
        $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
    } elseif ($ai_platform_choice == 'Google') {
        $api_key = esc_attr(get_option('chatbot_google_api_key'));
        // Decrypt the API key - Ver 2.3.9
        $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
    } elseif ($ai_platform_choice == 'Mistral') {
        $api_key = esc_attr(get_option('chatbot_mistral_api_key'));
        // Decrypt the API key - Ver 2.2.6
        $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
    } elseif ($ai_platform_choice == 'Local Server') {
        $api_key = esc_attr(get_option('chatbot_local_api_key'));
        // Decrypt the API key - Ver 2.2.6
        $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
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
        http_response_code(500); // Send a 500 Internal Server Error status code
        exit;
    }

    $responses = [];
    $error_flag = false;

    if (isset($_FILES['file']['name']) && is_array($_FILES['file']['name'])) {
        for ($i = 0; $i < count($_FILES['file']['name']); $i++) {
            $newFileName = generate_random_string() . '.' . pathinfo($_FILES['file']['name'][$i], PATHINFO_EXTENSION);
            $file_path = $uploads_dir . $newFileName;

            if ($_FILES['file']['error'][$i] > 0) {
                $error_message = !empty($chatbot_chatgpt_fixed_literal_messages[4]) 
                    ? $chatbot_chatgpt_fixed_literal_messages[4] 
                    : "Oops! Something went wrong during the upload of {$_FILES['file']['name'][$i]}. Please try again later.";

                $responses[] = [
                    'status' => 'error',
                    'message' => $error_message
                ];
                $error_flag = true;
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
                // Send a 415 Unsupported Media Type status code
                wp_send_json_error($responses, 415);
            }

            // Determine file type
            $file_mime_type = mime_content_type($file_path);
            $purpose = 'assistants';

            // Pre-checks before calling OpenAI: file must exist and have size
            if ( ! file_exists( $file_path ) || filesize( $file_path ) <= 0 ) {
                $responses[] = [
                    'status'  => 'error',
                    'message' => 'Upload failed: file is missing or empty.',
                ];
                $error_flag = true;
                if ( file_exists( $file_path ) ) {
                    unlink( $file_path );
                }
                continue;
            }
            $file_size = filesize( $file_path );
            $filename  = basename( $file_path );

            // Prepare API request
            $api_url = get_files_api_url();

            // Which API key to use?
            $ai_platform_choice = esc_attr(get_option('chatbot_ai_platform_choice'), 'OpenAI');
            if ($ai_platform_choice == 'OpenAI') {
                $api_key = esc_attr(get_option('chatbot_chatgpt_api_key'));
                $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            } elseif ($ai_platform_choice == 'Azure OpenAI') {
                $api_key = esc_attr(get_option('chatbot_azure_api_key'));
                $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            } elseif ($ai_platform_choice == 'NVIDIA') {
                $api_key = esc_attr(get_option('chatbot_nvidia_api_key'));
                $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            } elseif ($ai_platform_choice == 'Anthropic') {
                $api_key = esc_attr(get_option('chatbot_anthropic_api_key'));
                $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            } elseif ($ai_platform_choice == 'DeepSeek') {
                $api_key = esc_attr(get_option('chatbot_deepseek_api_key'));
                $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            } elseif ($ai_platform_choice == 'Google') {
                $api_key = esc_attr(get_option('chatbot_google_api_key'));
                $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            } elseif ($ai_platform_choice == 'Local Server') {
                $api_key = esc_attr(get_option('chatbot_local_api_key'));
                $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            }

            // Build multipart with CURLFile so the API reliably receives the 'file' field (wp_remote_post + raw body can fail for some file types)
            $post_fields = [
                'purpose' => $purpose,
                'file'   => new \CURLFile( $file_path, $file_mime_type, $filename ),
            ];
            $payload_keys_log = [ 'purpose' => $purpose, 'file' => $post_fields['file'] ];

            $http_status = 0;
            $response_body = '';

            if ( $ai_platform_choice === 'OpenAI' || $ai_platform_choice === 'Azure OpenAI' ) {
                if ( ! function_exists( 'curl_init' ) ) {
                    $responses[] = [
                        'status'  => 'error',
                        'message' => 'Upload failed: server does not support cURL.',
                    ];
                    $error_flag = true;
                    unlink( $file_path );
                    continue;
                }
                $ch = curl_init( $api_url );
                if ( $ch === false ) {
                    $responses[] = [ 'status' => 'error', 'message' => 'Upload failed: could not initialize request.' ];
                    $error_flag = true;
                    unlink( $file_path );
                    continue;
                }
                $headers = [
                    'Authorization: Bearer ' . trim( $api_key ),
                ];
                if ( $ai_platform_choice === 'Azure OpenAI' ) {
                    $headers = [ 'api-key: ' . trim( $api_key ) ];
                }
                curl_setopt_array( $ch, [
                    CURLOPT_POST            => true,
                    CURLOPT_POSTFIELDS      => $post_fields,
                    CURLOPT_HTTPHEADER     => $headers,
                    CURLOPT_TIMEOUT        => 30,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HEADER         => false,
                ] );
                $response_body = (string) curl_exec( $ch );
                $http_status   = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
                $curl_err      = curl_error( $ch );
                curl_close( $ch );
                if ( $response_body === false && $curl_err !== '' ) {
                    $response_body = '';
                    $responses[] = [
                        'status'  => 'error',
                        'message' => 'API Error: ' . $curl_err,
                    ];
                    $error_flag = true;
                    unlink( $file_path );
                    chatbot_file_upload_debug_log( $api_url, 0, $response_body, $payload_keys_log, $file_path, $file_size, $file_mime_type );
                    continue;
                }
            } else {
                $responses[] = [
                    'status'  => 'error',
                    'message' => 'Unsupported AI platform for file uploads.',
                ];
                $error_flag = true;
                unlink( $file_path );
                continue;
            }

            chatbot_file_upload_debug_log( $api_url, $http_status, $response_body, $payload_keys_log, $file_path, $file_size, $file_mime_type );

            $responseData = json_decode( $response_body, true );

            // Success only when HTTP 200 AND response has id matching OpenAI file id pattern
            $file_id = isset( $responseData['id'] ) ? $responseData['id'] : '';
            $is_success = ( $http_status === 200 && is_string( $file_id ) && preg_match( '/^file-/', $file_id ) && ! isset( $responseData['error'] ) );

            if ( ! $is_success ) {
                $api_message = isset( $responseData['error']['message'] ) ? $responseData['error']['message'] : 'Unknown error occurred.';
                if ( is_string( $api_message ) && strpos( $api_message, "'file' is a required" ) !== false ) {
                    $errorMessage = __( 'Upload failed: OpenAI did not receive a file.', 'chatbot-chatgpt' );
                } else {
                    $errorMessage = $api_message;
                }
                $responses[] = [
                    'status'      => 'error',
                    'http_status' => $http_status,
                    'message'     => $errorMessage,
                ];
                $error_flag = true;
                unlink( $file_path );
                continue;
            }

            // Store API response
            set_chatbot_chatgpt_transients_files( 'chatbot_chatgpt_assistant_file_ids', $responseData['id'], $session_id, $i );
            set_chatbot_chatgpt_transients_files( 'chatbot_chatgpt_assistant_file_types', $purpose, $session_id, $i );
            // Cache text-like file content for Responses API (OpenAI does not allow GET /files/{id}/content for purpose=assistants).
            $ext = strtolower( pathinfo( $_FILES['file']['name'][ $i ], PATHINFO_EXTENSION ) );
            $text_exts = array( 'txt', 'md', 'csv', 'json', 'xml' );
            $is_text_like = in_array( $ext, $text_exts, true )
                || strpos( $file_mime_type, 'text/' ) === 0
                || $file_mime_type === 'application/json'
                || $file_mime_type === 'application/xml';
            if ( $is_text_like ) {
                $content = file_get_contents( $file_path );
                $content = is_string( $content ) ? substr( $content, 0, 20000 ) : '';
                set_chatbot_chatgpt_transients_files( 'chatbot_chatgpt_assistant_file_text', $content, $session_id, $i );
            }
            chatbot_chatgpt_cleanup_old_file_transients( $session_id );

            $responses[] = [
                'status'      => 'success',
                'http_status' => $http_status,
                'id'         => $responseData['id'],
                'message'    => 'File ' . $newFileName . ' uploaded successfully.',
            ];
            unlink( $file_path );

        }

        // Send JSON so the client can show per-file success/error (do not just return; AJAX handler must output)
        $has_errors = false;
        foreach ( $responses as $r ) {
            if ( isset( $r['status'] ) && $r['status'] === 'error' ) {
                $has_errors = true;
                break;
            }
        }
        if ( $has_errors ) {
            wp_send_json_error( $responses );
        } else {
            wp_send_json_success( $responses );
        }
        return;

    } else {

        global $chatbot_chatgpt_fixed_literal_messages;
        $default_message = 'Oops! Please select a file to upload.';
        $error_message = isset($chatbot_chatgpt_fixed_literal_messages[5]) 
            ? $chatbot_chatgpt_fixed_literal_messages[5] 
            : $default_message;
        wp_send_json_error( array( 'status' => 'error', 'message' => $error_message ) );
        return;

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

    // Security: Check if user has permission to upload files
    if (!current_user_can('upload_files')) {
        wp_send_json_error('Insufficient permissions to upload files.', 403);
        return;
    }

    // Security: Verify nonce for CSRF protection
    if (!isset($_POST['chatbot_nonce']) || !wp_verify_nonce($_POST['chatbot_nonce'], 'chatbot_upload_nonce')) {
        wp_send_json_error('Security check failed. Please refresh the page and try again.', 403);
        return;
    }

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
        $responses[] = array(
            'status' => 'error',
            'message' => 'Oops! File upload failed.'
        );
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
                http_response_code(415); // Send a 415 Unsupported Media Type status code
                exit;
            }

            if (!move_uploaded_file($_FILES['file']['tmp_name'][$i], $file_path)) {
                $responses[] = array(
                    'status' => 'error',
                    'message' => "Oops! Something went wrong during the upload of {$_FILES['file']['name'][$i]}. Please try again later."
                );
                $error_flag = true;
                http_response_code(415); // Send a 415 Unsupported Media Type status code
                exit;

            }
        }

        if ($error_flag == true) {
            http_response_code(403); // Send a 403 Forbidden status code
            return $responses;
        }

        // Save the file name for later
        // DIAG - Diagnostics - Ver 2.0.1
        set_chatbot_chatgpt_transients_files('chatbot_chatgpt_assistant_file_ids', $newFileName, $session_id, $i);
        set_chatbot_chatgpt_transients_files('chatbot_chatgpt_assistant_file_types', 'mp3', $session_id, $i);
        $responses[] = array(
            'status' => 'success',
            'message' => "File uploaded successfully."
        );
        return $responses;

    } else {
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
        $randomString .= $characters[wp_rand( 0, $charactersLength - 1 )];
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
            prod_trace('ERROR', 'Failed to create directory: ' . $directory);
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
            prod_trace('ERROR', 'Failed to create index.php file in directory: ' . $directory);
        }
    }
}

// File type validation - Ver 2.0.1
function upload_validation($file) {


    // DIAG - Diagnostics - Ver 2.0.7

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
            return $file;
        }

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
