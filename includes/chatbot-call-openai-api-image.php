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

    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;
    global $kchat_settings;
    global $additional_instructions;
    global $model;
    global $voice;

    // Use client_message_id if provided, otherwise generate a unique message UUID for idempotency
    $message_uuid = $client_message_id ? $client_message_id : wp_generate_uuid4();

    // Lock the conversation BEFORE thread resolution to prevent empty-thread vs real-thread lock split
    $conv_lock = 'chatgpt_conv_lock_' . md5($assistant_id . '|' . $user_id . '|' . $page_id . '|' . $session_id);
    $lock_timeout = 60; // 60 seconds timeout

    // Check for duplicate message UUID in conversation log
    $duplicate_key = 'chatgpt_message_uuid_' . $message_uuid;
    if (get_transient($duplicate_key)) {
        // DIAG - Diagnostics - Ver 2.3.4
        // back_trace( 'NOTICE', 'Duplicate message UUID detected: ' . $message_uuid);
        return "Error: Duplicate request detected. Please try again.";
    }

    // Lock check removed - main send function handles locking
    set_transient($duplicate_key, true, 300); // 5 minutes to prevent duplicates

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

    // Select the OpenAI Model (dall-e-2 or dall-e-3)
    if ( !empty($kchat_settings['model']) ) {
        $model = $kchat_settings['model'];
        // DIAG - Diagnostics - Ver 1.9.4
        // back_trace( 'NOTICE', '$model from script_data_array: ' . $model);
    } else {
        $model = esc_attr(get_option('chatbot_chatgpt_image_model_option', 'dall-e-2'));
        // DIAG - Diagnostics - Ver 1.9.4
        // back_trace( 'NOTICE', '$model from get_option: ' . $model);
    }

    // Enforce message length constraints based on model
    if ($model === 'dall-e-2' && strlen($message) > 1000) {
        $message = substr($message, 0, 1000);
    } elseif ($model === 'dall-e-3' && strlen($message) > 10000) {
        $message = substr($message, 0, 10000);
    }

    // Set number of images to generate
    $quantity = intval(esc_attr(get_option('chatbot_chatgpt_image_output_quantity', '1')));
    // The number of images to generate. Must be between 1 and 10. For dall-e-3, only n=1 is supported.
    if ($model === 'dall-e-3') {
        $quantity = 1; // dall-e-3 only supports `n=1`
    }

    // Define allowed image sizes based on the model
    $size = esc_attr(get_option('chatbot_chatgpt_image_output_size', '1024x1024'));
        // If the $model is dall-e-2, then size muss be one of 256x256, 512x512, or 1024x1024
    $allowed_sizes = ($model === 'dall-e-2') ? ['256x256', '512x512', '1024x1024'] : ['1024x1024', '1792x1024', '1024x1792'];
    if (!in_array($size, $allowed_sizes)) {
        $size = '1024x1024';
    }

    // Additional image parameters (for dall-e-3)
    $quality = esc_attr(get_option('chatbot_chatgpt_image_quality_output', 'standard'));
    $style = esc_attr(get_option('chatbot_chatgpt_image_style_output', 'vivid'));

    // User tracking data
    $user_tracking = implode('-', [$session_id, $user_id, $page_id, $thread_id, $assistant_id]);

    // Diagnostics - Ver 1.9.5
    // back_trace( 'NOTICE', 'chatbot_calll_image_api()');
    // back_trace( 'NOTICE', 'BEGIN $message: ' . $message);
    // back_trace( 'NOTICE', 'BEGIN $model: ' . $model);
    // back_trace( 'NOTICE', 'BEGIN $quantity: ' . $quantity);
    // back_trace( 'NOTICE', 'BEGIN $size: ' . $size);
    // back_trace( 'NOTICE', 'BEGIN $quality: ' . $quality);
    // back_trace( 'NOTICE', 'BEGIN $style: ' . $style);

    // Prepare the request body
    $body = [
        'model'   => $model,
        'prompt'  => $message,
        'n'       => $quantity,
        'size'    => $size,
        'user'    => $user_tracking
    ];

    // Include additional parameters for dall-e-3
    if ($model === 'dall-e-3') {
        $body['quality'] = $quality;
        $body['style'] = $style;
    }

    // Send the API request using WordPress HTTP API
    $response = wp_remote_post($api_url, [
        'method'    => 'POST',
        'timeout'   => 30,
        'headers'   => [
            'Authorization'  => 'Bearer ' . $api_key,
            'Content-Type'   => 'application/json'
        ],
        'body'      => json_encode($body)
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
                $image_url = $image_data['url'];
                $image_urls .= "![Generated Image]($image_url)\n";
            }
        }
        // Clear locks on success
        // Lock clearing removed - main send function handles locking
        return $image_urls;
    }

    // Return a localized error message if no images were generated
    // Clear locks on error
    delete_transient($conv_lock);
    return $errorResponses[array_rand($errorResponses)] ?? 'Error: No images generated.';

}
