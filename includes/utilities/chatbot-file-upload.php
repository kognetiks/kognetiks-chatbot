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

// Debug helper for file upload to OpenAI
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

/**
 * Allowlisted upload types. SVG and ZIP are omitted.
 * Keys are extensions. mime is passed to wp_check_filetype_and_ext(). finfo lists acceptable content types.
 *
 * @return array<string, array{mime: string, finfo: string[]}>
 */
function chatbot_chatgpt_allowed_upload_types() {
    return array(
        'csv'  => array( 'mime' => 'text/csv', 'finfo' => array( 'text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel' ) ),
        'doc'  => array( 'mime' => 'application/msword', 'finfo' => array( 'application/msword', 'application/vnd.ms-office', 'application/octet-stream' ) ),
        'docx' => array( 'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'finfo' => array( 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream' ) ),
        'gif'  => array( 'mime' => 'image/gif', 'finfo' => array( 'image/gif' ) ),
        'jpeg' => array( 'mime' => 'image/jpeg', 'finfo' => array( 'image/jpeg' ) ),
        'jpg'  => array( 'mime' => 'image/jpeg', 'finfo' => array( 'image/jpeg' ) ),
        'mp3'  => array( 'mime' => 'audio/mpeg', 'finfo' => array( 'audio/mpeg', 'audio/mp3' ) ),
        'mp4'  => array( 'mime' => 'video/mp4', 'finfo' => array( 'video/mp4' ) ),
        'mpeg' => array( 'mime' => 'video/mpeg', 'finfo' => array( 'video/mpeg' ) ),
        'mpga' => array( 'mime' => 'audio/mpeg', 'finfo' => array( 'audio/mpeg', 'audio/mp3' ) ),
        'm4a'  => array( 'mime' => 'audio/mp4', 'finfo' => array( 'audio/mp4', 'audio/x-m4a', 'audio/m4a' ) ),
        'pdf'  => array( 'mime' => 'application/pdf', 'finfo' => array( 'application/pdf' ) ),
        'png'  => array( 'mime' => 'image/png', 'finfo' => array( 'image/png' ) ),
        'ppt'  => array( 'mime' => 'application/vnd.ms-powerpoint', 'finfo' => array( 'application/vnd.ms-powerpoint', 'application/vnd.ms-office', 'application/octet-stream' ) ),
        'pptx' => array( 'mime' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'finfo' => array( 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip', 'application/octet-stream' ) ),
        'rtf'  => array( 'mime' => 'application/rtf', 'finfo' => array( 'application/rtf', 'text/rtf', 'text/plain' ) ),
        'txt'  => array( 'mime' => 'text/plain', 'finfo' => array( 'text/plain' ) ),
        'wav'  => array( 'mime' => 'audio/wav', 'finfo' => array( 'audio/wav', 'audio/x-wav' ) ),
        'webm' => array( 'mime' => 'video/webm', 'finfo' => array( 'video/webm', 'audio/webm' ) ),
        'webp' => array( 'mime' => 'image/webp', 'finfo' => array( 'image/webp' ) ),
        'xls'  => array( 'mime' => 'application/vnd.ms-excel', 'finfo' => array( 'application/vnd.ms-excel', 'application/vnd.ms-office', 'application/octet-stream' ) ),
        'xlsx' => array( 'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'finfo' => array( 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream' ) ),
        'xml'  => array( 'mime' => 'application/xml', 'finfo' => array( 'application/xml', 'text/xml' ) ),
        'json' => array( 'mime' => 'application/json', 'finfo' => array( 'application/json', 'text/plain', 'text/json' ) ),
        'md'   => array( 'mime' => 'text/plain', 'finfo' => array( 'text/plain', 'text/markdown' ) ),
    );
}

/**
 * Final extension, lowercased and limited to [a-z0-9]. Rejects dangerous earlier extensions.
 *
 * @param string $filename Client filename.
 * @return string
 */
function chatbot_chatgpt_upload_extension( $filename ) {
    $filename = wp_basename( str_replace( '\\', '/', (string) $filename ) );
    $filename = strtolower( $filename );
    if ( '' === $filename || preg_match( '/[\x00-\x1F]/', $filename ) ) {
        return '';
    }

    $parts = explode( '.', $filename );
    if ( count( $parts ) < 2 ) {
        return '';
    }

    $ext = array_pop( $parts );
    if ( ! preg_match( '/^[a-z0-9]+$/', $ext ) ) {
        return '';
    }

    $dangerous = array( 'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'pht', 'phar', 'phps', 'cgi', 'pl', 'asp', 'aspx', 'shtml', 'htaccess', 'ini' );
    if ( in_array( $ext, $dangerous, true ) ) {
        return '';
    }
    foreach ( $parts as $part ) {
        if ( in_array( $part, $dangerous, true ) ) {
            return '';
        }
    }

    return $ext;
}

/**
 * True when the active platform, or an assistant row, allows file uploads.
 *
 * @return bool
 */
function chatbot_chatgpt_file_uploads_are_enabled() {
    $platform = get_option( 'chatbot_ai_platform_choice', 'OpenAI' );
    $option_keys = array(
        'OpenAI'       => 'chatbot_chatgpt_allow_file_uploads',
        'Azure OpenAI' => 'chatbot_azure_allow_file_uploads',
        'Mistral'      => 'chatbot_mistral_allow_file_uploads',
    );
    $option_key = isset( $option_keys[ $platform ] ) ? $option_keys[ $platform ] : 'chatbot_chatgpt_allow_file_uploads';
    if ( 'Yes' === get_option( $option_key, 'No' ) ) {
        return true;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'chatbot_chatgpt_assistants';
    $like  = $wpdb->esc_like( $table );
    $found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $like ) );
    if ( $found !== $table ) {
        return false;
    }

    $allowed = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM {$table} WHERE allow_file_uploads = %s LIMIT 1",
            'Yes'
        )
    );

    return ! empty( $allowed );
}

/**
 * Logged-in users who can upload files, and only when the feature is turned on.
 *
 * @return bool
 */
function chatbot_chatgpt_user_may_upload_files() {
    if ( ! is_user_logged_in() || ! current_user_can( 'upload_files' ) ) {
        return false;
    }

    return chatbot_chatgpt_file_uploads_are_enabled();
}

/**
 * Reject active content in text-like uploads.
 *
 * @param string $path File path.
 * @return bool
 */
function chatbot_chatgpt_upload_has_active_content( $path ) {
    $handle = fopen( $path, 'rb' );
    if ( false === $handle ) {
        return true;
    }

    $carry = '';
    $patterns = array(
        '/<\?php/i',
        '/<\?=/i',
        '/<script\b/i',
        '/<svg\b/i',
    );

    while ( ! feof( $handle ) ) {
        $chunk = fread( $handle, 8192 );
        if ( ! is_string( $chunk ) || '' === $chunk ) {
            break;
        }
        $window = $carry . $chunk;
        foreach ( $patterns as $pattern ) {
            if ( preg_match( $pattern, $window ) ) {
                fclose( $handle );
                return true;
            }
        }
        $carry = substr( $window, -32 );
    }

    fclose( $handle );
    return false;
}

/**
 * Validate an uploaded temp file. Returns the allowlisted extension and content MIME.
 *
 * @param string   $tmp_name     PHP upload tmp path.
 * @param string   $original     Client filename.
 * @param string[] $only_exts    Optional extension subset.
 * @return array|WP_Error
 */
function chatbot_chatgpt_inspect_uploaded_file( $tmp_name, $original, $only_exts = array() ) {
    if ( ! is_string( $tmp_name ) || ! is_uploaded_file( $tmp_name ) ) {
        return new WP_Error( 'chatbot_upload', 'Invalid upload.' );
    }

    $types = chatbot_chatgpt_allowed_upload_types();
    if ( ! empty( $only_exts ) ) {
        $types = array_intersect_key( $types, array_flip( $only_exts ) );
    }

    $ext = chatbot_chatgpt_upload_extension( $original );
    if ( '' === $ext || ! isset( $types[ $ext ] ) ) {
        return new WP_Error( 'chatbot_upload', 'Invalid file type or extension.' );
    }

    if ( ! function_exists( 'wp_check_filetype_and_ext' ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    $mime_map = array();
    foreach ( $types as $type_ext => $info ) {
        $mime_map[ $type_ext ] = $info['mime'];
    }

    $named = wp_check_filetype( 'upload.' . $ext, $mime_map );
    $named_ext = isset( $named['ext'] ) ? strtolower( (string) $named['ext'] ) : '';
    if ( $named_ext !== $ext ) {
        return new WP_Error( 'chatbot_upload', 'Invalid file type or extension.' );
    }

    $checked = wp_check_filetype_and_ext( $tmp_name, 'upload.' . $ext, $mime_map );
    $checked_ext = isset( $checked['ext'] ) ? strtolower( (string) $checked['ext'] ) : '';
    if ( '' !== $checked_ext && $checked_ext !== $ext ) {
        return new WP_Error( 'chatbot_upload', 'Invalid file type or extension.' );
    }

    $real_mime = chatbot_chatgpt_finfo_mime( $tmp_name );
    $blocked_mimes = array( 'text/x-php', 'application/x-httpd-php', 'application/x-php', 'application/x-httpd-php-source' );
    if ( '' === $real_mime || in_array( $real_mime, $blocked_mimes, true ) || ! in_array( $real_mime, $types[ $ext ]['finfo'], true ) ) {
        return new WP_Error( 'chatbot_upload', 'Invalid file type or extension.' );
    }

    $text_exts = array( 'csv', 'txt', 'xml', 'json', 'md', 'rtf' );
    if ( in_array( $ext, $text_exts, true ) && chatbot_chatgpt_upload_has_active_content( $tmp_name ) ) {
        return new WP_Error( 'chatbot_upload', 'Security error: Potentially dangerous content found.' );
    }

    return array(
        'ext'  => $ext,
        'mime' => $real_mime,
    );
}

/**
 * Copy a validated upload into the system temp directory.
 *
 * @param string   $tmp_name  PHP upload tmp path.
 * @param string   $original  Client filename.
 * @param string   $prefix    wp_tempnam prefix, kchat-upload or kchat-voice.
 * @param string[] $only_exts Optional extension subset.
 * @return array|WP_Error
 */
function chatbot_chatgpt_stage_uploaded_file( $tmp_name, $original, $prefix, $only_exts = array() ) {
    $inspected = chatbot_chatgpt_inspect_uploaded_file( $tmp_name, $original, $only_exts );
    if ( is_wp_error( $inspected ) ) {
        return $inspected;
    }

    if ( ! function_exists( 'wp_tempnam' ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    $temp_path = wp_tempnam( $prefix );
    if ( ! is_string( $temp_path ) || '' === $temp_path ) {
        return new WP_Error( 'chatbot_upload', 'Upload failed: could not create a temporary file.' );
    }

    wp_delete_file( $temp_path );
    if ( ! move_uploaded_file( $tmp_name, $temp_path ) ) {
        return new WP_Error( 'chatbot_upload', 'Upload failed: could not store the temporary file.' );
    }

    $inspected['path']     = $temp_path;
    $inspected['basename'] = wp_basename( $temp_path );

    return $inspected;
}

/**
 * Delete a staged temp file only when the basename matches a file this plugin created.
 *
 * @param string $basename Temp basename.
 * @return void
 */
function chatbot_chatgpt_delete_staged_temp_file( $basename ) {
    $path = chatbot_chatgpt_staged_temp_path( $basename );
    if ( is_string( $path ) ) {
        wp_delete_file( $path );
    }
}

/**
 * Resolve a staged basename to a real path inside the system temp directory.
 *
 * @param string $basename Temp basename.
 * @return string|null
 */
function chatbot_chatgpt_staged_temp_path( $basename ) {
    $basename = wp_basename( (string) $basename );
    if ( ! preg_match( '/^kchat-(upload|voice|download)-[A-Za-z0-9]+(?:-[0-9]+)?\.tmp$/', $basename ) ) {
        return null;
    }

    if ( ! function_exists( 'get_temp_dir' ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    $temp_dir = wp_normalize_path( trailingslashit( get_temp_dir() ) );
    $candidate = wp_normalize_path( $temp_dir . $basename );
    if ( ! file_exists( $candidate ) ) {
        return null;
    }

    $real = realpath( $candidate );
    if ( false === $real ) {
        return null;
    }

    $real = wp_normalize_path( $real );
    if ( 0 !== stripos( $real, $temp_dir ) ) {
        return null;
    }

    return $real;
}

/**
 * Resolve the voice file stored for this session.
 *
 * @param string $session_id Session id.
 * @return string|WP_Error Absolute path.
 */
function chatbot_chatgpt_resolve_staged_voice_file( $session_id ) {
    $stored = get_chatbot_chatgpt_transients_files( 'chatbot_chatgpt_assistant_file_ids', $session_id, 1 );
    $path   = chatbot_chatgpt_staged_temp_path( $stored );
    if ( null === $path ) {
        return new WP_Error( 'chatbot_upload', 'Audio file does not exist.' );
    }

    return $path;
}

/**
 * Remove staged temp files older than one hour.
 *
 * @return void
 */
function chatbot_chatgpt_cleanup_staged_temp_files() {
    if ( ! function_exists( 'get_temp_dir' ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    $temp_dir = trailingslashit( get_temp_dir() );
    $matches  = glob( $temp_dir . 'kchat-*.tmp' );
    if ( ! is_array( $matches ) ) {
        return;
    }

    $cutoff = time() - HOUR_IN_SECONDS;
    foreach ( $matches as $file ) {
        $basename = wp_basename( $file );
        if ( null === chatbot_chatgpt_staged_temp_path( $basename ) ) {
            continue;
        }
        $mtime = filemtime( $file );
        if ( false !== $mtime && $mtime < $cutoff ) {
            wp_delete_file( $file );
        }
    }
}

// Upload Multiple files to the Assistant
function chatbot_chatgpt_upload_files() {

    // Security: logged-in users with upload_files only. Guests have no nopriv hook.
    if ( ! chatbot_chatgpt_user_may_upload_files() ) {
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
            $original_name = isset($_FILES['file']['name'][$i]) ? wp_unslash($_FILES['file']['name'][$i]) : '';
            if (!is_string($original_name)) {
                $original_name = '';
            }

            if ($_FILES['file']['error'][$i] > 0) {
                $error_message = !empty($chatbot_chatgpt_fixed_literal_messages[4]) 
                    ? $chatbot_chatgpt_fixed_literal_messages[4] 
                    : "Oops! Something went wrong during the upload of {$original_name}. Please try again later.";

                $responses[] = [
                    'status' => 'error',
                    'message' => $error_message
                ];
                $error_flag = true;
                // Send a 415 Unsupported Media Type status code
                wp_send_json_error($responses, 415);
            }

            $staged = chatbot_chatgpt_stage_uploaded_file(
                isset($_FILES['file']['tmp_name'][$i]) ? $_FILES['file']['tmp_name'][$i] : '',
                $original_name,
                'kchat-upload'
            );
            if (is_wp_error($staged)) {
                $responses[] = [
                    'status' => 'error',
                    'message' => $staged->get_error_message()
                ];
                $error_flag = true;
                wp_send_json_error($responses, 415);
            }

            $file_path = $staged['path'];
            $validated_ext = $staged['ext'];
            $newFileName = 'upload-' . generate_random_string() . '.' . $validated_ext;

            try {
            // Content type was verified with finfo before the file was staged.
            $file_mime_type = $staged['mime'];
            $purpose = 'assistants';

            // Pre-checks before calling OpenAI: file must exist and have size
            if ( ! file_exists( $file_path ) || filesize( $file_path ) <= 0 ) {
                $responses[] = [
                    'status'  => 'error',
                    'message' => 'Upload failed: file is missing or empty.',
                ];
                $error_flag = true;
                if ( file_exists( $file_path ) ) {
                    wp_delete_file( $file_path );
                }
                continue;
            }
            $file_size = filesize( $file_path );
            $filename  = $newFileName;

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
                    wp_delete_file( $file_path );
                    continue;
                }
                $ch = curl_init( $api_url );
                if ( $ch === false ) {
                    $responses[] = [ 'status' => 'error', 'message' => 'Upload failed: could not initialize request.' ];
                    $error_flag = true;
                    wp_delete_file( $file_path );
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
                    wp_delete_file( $file_path );
                    chatbot_file_upload_debug_log( $api_url, 0, $response_body, $payload_keys_log, $file_path, $file_size, $file_mime_type );
                    continue;
                }
            } else {
                $responses[] = [
                    'status'  => 'error',
                    'message' => 'Unsupported AI platform for file uploads.',
                ];
                $error_flag = true;
                wp_delete_file( $file_path );
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
                wp_delete_file( $file_path );
                continue;
            }

            // Store API response
            set_chatbot_chatgpt_transients_files( 'chatbot_chatgpt_assistant_file_ids', $responseData['id'], $session_id, $i );
            set_chatbot_chatgpt_transients_files( 'chatbot_chatgpt_assistant_file_types', $purpose, $session_id, $i );
            // Cache text-like file content for Responses API (OpenAI does not allow GET /files/{id}/content for purpose=assistants).
            $ext = $validated_ext;
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
            wp_delete_file( $file_path );

            } finally {
                if ( ! empty( $file_path ) && file_exists( $file_path ) ) {
                    wp_delete_file( $file_path );
                }
            }

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

    // Security: logged-in users with upload_files, and only when file uploads are enabled.
    if ( ! chatbot_chatgpt_user_may_upload_files() ) {
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

    $responses = [];
    $error_flag = false;

    // Voice files stay in the system temp directory until speech-to-text sends them.
    // chatbot_chatgpt_call_stt_api() deletes the temp file in a finally block.
    $voice_extensions = array( 'mp3', 'mp4', 'mpeg', 'mpga', 'm4a', 'wav', 'webm' );
    $staged_basename = '';
    $staged = array( 'ext' => 'mp3' );

    // Check if files were uploaded
    if (isset($_FILES['file']['name']) && is_array($_FILES['file']['name'])) {
        for ($i = 0; $i < count($_FILES['file']['name']); $i++) {
            $original_name = isset($_FILES['file']['name'][$i]) ? wp_unslash($_FILES['file']['name'][$i]) : '';
            if (!is_string($original_name)) {
                $original_name = '';
            }

            if ($_FILES['file']['error'][$i] > 0) {
                global $chatbot_chatgpt_fixed_literal_messages;
                $default_message = "Oops! Something went wrong during the upload of {$original_name}. Please try again later.";
                $error_message = isset($chatbot_chatgpt_fixed_literal_messages[4]) 
                    ? $chatbot_chatgpt_fixed_literal_messages[4] 
                    : $default_message;
                chatbot_chatgpt_delete_staged_temp_file( $staged_basename );
                $responses[] = array(
                    'status' => 'error',
                    'message' => $error_message
                );
                $error_flag = true;
                http_response_code(415); // Send a 415 Unsupported Media Type status code
                exit;
            }

            $staged = chatbot_chatgpt_stage_uploaded_file(
                isset($_FILES['file']['tmp_name'][$i]) ? $_FILES['file']['tmp_name'][$i] : '',
                $original_name,
                'kchat-voice',
                $voice_extensions
            );
            if (is_wp_error($staged)) {
                chatbot_chatgpt_delete_staged_temp_file( $staged_basename );
                $responses[] = array(
                    'status' => 'error',
                    'message' => $staged->get_error_message()
                );
                $error_flag = true;
                http_response_code(415); // Send a 415 Unsupported Media Type status code
                exit;
            }

            chatbot_chatgpt_delete_staged_temp_file( $staged_basename );
            $staged_basename = $staged['basename'];
        }

        if ($error_flag == true) {
            chatbot_chatgpt_delete_staged_temp_file( $staged_basename );
            http_response_code(403); // Send a 403 Forbidden status code
            return $responses;
        }

        // Save the temp basename for speech-to-text. The client filename is not stored.
        set_chatbot_chatgpt_transients_files('chatbot_chatgpt_assistant_file_ids', $staged_basename, $session_id, $i);
        set_chatbot_chatgpt_transients_files('chatbot_chatgpt_assistant_file_types', $staged['ext'], $session_id, $i);
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
    $upload_files = glob($uploads_dir . '*');
    if (!is_array($upload_files)) {
        $upload_files = array();
    }
    foreach ($upload_files as $file) {
        if (chatbot_chatgpt_is_static_guard_file($file)) {
            continue;
        }
        // Delete files older than 1 hour
        if (filemtime($file) < time() - 60 * 60 * 1) {
            wp_delete_file($file);
        }
    }
    // Create the index.php file if it does not exist
    create_index_file($uploads_dir);
    chatbot_chatgpt_cleanup_staged_temp_files();
}
add_action('chatbot_chatgpt_cleanup_upload_files', 'chatbot_chatgpt_cleanup_uploads_directory');

function create_index_file($directory) {
    // Ensure the directory ends with a slash
    $directory = rtrim($directory, '/') . '/';

    // Check if the directory exists, if not, create it
    if (!is_dir($directory)) {
        if (!create_directory_and_index_file($directory)) {
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
// Delegates to the allowlist, finfo, and extension checks used by the upload handlers.
function upload_validation($file) {

    $name = isset($file['name']) ? $file['name'] : '';
    $tmp  = isset($file['tmp_name']) ? $file['tmp_name'] : '';
    $inspected = chatbot_chatgpt_inspect_uploaded_file($tmp, $name);
    if (is_wp_error($inspected)) {
        $file['error'] = $inspected->get_error_message();
        return $file;
    }

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
