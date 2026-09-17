<?php
/**
 * Kognetiks Chatbot - ChatGPT IMAGE API - Ver 1.9.4
 *
 * This file contains the code for generating images using
 * the image API.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Call the ChatGPT Image API using WP functions
function chatbot_chatgpt_call_image_api($api_key, $message, $user_id = null, $page_id = null, $session_id = null, $assistant_id = null, $client_message_id = null) {

    // Fixed Ver 2.3.6: Store parameters before declaring globals to preserve logged-in user IDs
    $param_user_id = $user_id;
    $param_page_id = $page_id;
    $param_session_id = $session_id;
    $param_assistant_id = $assistant_id;
    
    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;
    global $kchat_settings;
    global $additional_instructions;
    global $model;
    global $voice;

    // DIAG - Diagnostics - Ver 2.4.5
    // back_trace("NOTICE", "Starting OpenAI Image API call");
    // back_trace("NOTICE", "Message: " . $message);
    // back_trace("NOTICE", "User ID: " . $user_id);
    // back_trace("NOTICE", "Page ID: " . $page_id);
    // back_trace("NOTICE", "Session ID: " . $session_id);
    // back_trace("NOTICE", "Assistant ID: " . $assistant_id);
    // back_trace("NOTICE", "Client Message ID: " . $client_message_id);
    
    // Use parameter if provided (not null), otherwise use global
    if ($param_user_id !== null) {
        $user_id = $param_user_id;
    }
    if ($param_page_id !== null) {
        $page_id = $param_page_id;
    }
    if ($param_session_id !== null) {
        $session_id = $param_session_id;
    }
    if ($param_assistant_id !== null) {
        $assistant_id = $param_assistant_id;
    }

    // Use client_message_id if provided, otherwise generate a unique message UUID for idempotency
    $message_uuid = $client_message_id ? $client_message_id : wp_generate_uuid4();

    // Lock the conversation BEFORE thread resolution to prevent empty-thread vs real-thread lock split
    $conv_lock = 'chatgpt_conv_lock_' . wp_hash($assistant_id . '|' . $user_id . '|' . $page_id . '|' . $session_id);
    $lock_timeout = 60; // 60 seconds timeout

    // Check for duplicate message UUID in conversation log
    $duplicate_key = 'chatgpt_message_uuid_' . $message_uuid;
    if (get_transient($duplicate_key)) {
        return "Error: Duplicate request detected. Please try again.";
    }

    // Lock check removed - main send function handles locking
    set_transient($duplicate_key, true, 120); // 2 minutes to prevent duplicates - Ver 2.3.7

    // Ensure API key is set
    if (empty($api_key)) {
        $api_key = esc_attr(get_option('chatbot_chatgpt_api_key'));
        // Decrypt the API key - Ver 2.2.6
        $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
        if (empty($api_key)) {
            global $chatbot_chatgpt_fixed_literal_messages;
            // Define a default fallback message
            $default_message = 'Oops! Something went wrong on our end. Please try again later!';
            $error_message = isset($chatbot_chatgpt_fixed_literal_messages[15]) 
                ? $chatbot_chatgpt_fixed_literal_messages[15] 
                : $default_message;
            // Clear locks on error
            // Lock clearing removed - main send function handles locking
            return $error_message;
        }
    }

    // OpenAI Image API endpoint
    $api_url = 'https://api.openai.com/v1/images/generations';

    // Select the OpenAI image model (GPT Image, or legacy DALL·E if still configured)
    if ( !empty($kchat_settings['model']) ) {
        $model = $kchat_settings['model'];
    } else {
        $model = get_option('chatbot_chatgpt_image_model_option', 'gpt-image-1');
    }

    if ( empty( $model ) || str_starts_with( (string) $model, 'dall' ) ) {
        $model = 'gpt-image-1';
    }

    $is_gpt_image = str_starts_with( (string) $model, 'gpt-image' );

    // Enforce message length constraints based on model
    if ($model === 'dall-e-2' && strlen($message) > 1000) {
        $message = substr($message, 0, 1000);
    } elseif (strlen($message) > 10000) {
        $message = substr($message, 0, 10000);
    }

    // Set number of images to generate
    $quantity = intval(get_option('chatbot_chatgpt_image_output_quantity', '1'));
    if ($is_gpt_image || $model === 'dall-e-3') {
        $quantity = 1;
    }

    $size = get_option('chatbot_chatgpt_image_output_size', '1024x1024');
    if ($is_gpt_image) {
        $allowed_sizes = array( '1024x1024', '1536x1024', '1024x1536', 'auto' );
    } elseif ($model === 'dall-e-2') {
        $allowed_sizes = array( '256x256', '512x512', '1024x1024' );
    } else {
        $allowed_sizes = array( '1024x1024', '1792x1024', '1024x1792' );
    }
    if (!in_array($size, $allowed_sizes, true)) {
        $size = '1024x1024';
    }

    $quality = get_option('chatbot_chatgpt_image_output_quality', $is_gpt_image ? 'auto' : 'standard');
    if ($is_gpt_image) {
        if ($quality === 'standard') {
            $quality = 'medium';
        } elseif ($quality === 'hd') {
            $quality = 'high';
        }
        if (!in_array($quality, array( 'auto', 'low', 'medium', 'high' ), true)) {
            $quality = 'auto';
        }
    }

    $style = get_option('chatbot_chatgpt_image_style_output', 'vivid');
    $output_format = get_option('chatbot_chatgpt_image_output_format', 'png');

    // User tracking data
    $user_tracking = implode('-', [$session_id, $user_id, $page_id, $thread_id, $assistant_id]);

    // Prepare the request body
    $body = [
        'model'   => $model,
        'prompt'  => $message,
        'n'       => $quantity,
        'size'    => $size,
        'user'    => $user_tracking
    ];

    if ($is_gpt_image) {
        $body['quality'] = $quality;
        if (in_array($output_format, array( 'png', 'jpeg', 'webp' ), true)) {
            $body['output_format'] = $output_format;
        }
    } elseif ($model === 'dall-e-3') {
        $body['quality'] = $quality;
        $body['style'] = $style;
    }

    // Send the API request using WordPress HTTP API
    $response = wp_remote_post($api_url, [
        'method'    => 'POST',
        'timeout'   => $is_gpt_image ? 120 : 30,
        'headers'   => [
            'Authorization'  => 'Bearer ' . $api_key,
            'Content-Type'   => 'application/json'
        ],
        'body'      => wp_json_encode($body)
    ]);

    // Handle errors
    if (is_wp_error($response)) {
        prod_trace( 'ERROR', 'chatbot_chatgpt_call_image_api() - Error: ' . $response->get_error_message());
        // Clear locks on error
        // Lock clearing removed - main send function handles locking
        return 'Error: ' . $response->get_error_message();
    }

    $http_code = wp_remote_retrieve_response_code($response);
    $response_body = json_decode(wp_remote_retrieve_body($response), true);

    // Handle API errors
    if ($http_code !== 200 || isset($response_body['error'])) {
        $error_message = $response_body['error']['message'] ?? 'Unknown API Error';
        prod_trace( 'ERROR', 'chatbot_chatgpt_call_image_api() - Error: API responded with HTTP code ' . $http_code . ': ' . $error_message);
        // Clear locks on error
        // Lock clearing removed - main send function handles locking
        return 'Error: API responded with HTTP code ' . $http_code . ': ' . $error_message;
    }

    // Process the response and return generated image URLs
    if (!empty($response_body['data'])) {
        $image_urls = '';
        foreach ($response_body['data'] as $image_data) {
            if (!empty($image_data['url'])) {
                $image_urls .= '![Generated Image](' . esc_url_raw( $image_data['url'] ) . ")\n";
            } elseif (!empty($image_data['b64_json'])) {
                $saved_url = chatbot_chatgpt_save_generated_image( $image_data['b64_json'], $output_format );
                if ( ! empty( $saved_url ) ) {
                    $image_urls .= '![Generated Image](' . esc_url_raw( $saved_url ) . ")\n";
                }
            }
        }
        if ( $image_urls !== '' ) {
            return $image_urls;
        }
    }

    // Return a localized error message if no images were generated
    // Clear locks on error
    delete_transient($conv_lock);
    return $errorResponses[array_rand($errorResponses)] ?? 'Error: No images generated.';

}

// Save a GPT Image base64 payload to the WordPress uploads directory and return its URL.
function chatbot_chatgpt_save_generated_image( $b64_json, $output_format = 'png' ) {

    $bytes = base64_decode( $b64_json, true );
    if ( $bytes === false || $bytes === '' ) {
        return '';
    }

    $ext = in_array( $output_format, array( 'png', 'jpeg', 'jpg', 'webp' ), true ) ? $output_format : 'png';
    if ( $ext === 'jpeg' ) {
        $ext = 'jpg';
    }

    $filename = 'chatbot-generated-' . wp_generate_uuid4() . '.' . $ext;
    $uploaded = wp_upload_bits( $filename, null, $bytes );

    if ( ! empty( $uploaded['error'] ) || empty( $uploaded['url'] ) ) {
        return '';
    }

    return $uploaded['url'];

}
