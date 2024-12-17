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

// Call the ChatGPT API
function chatbot_chatgpt_call_image_api($api_key, $message) {

    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;
    global $kchat_settings;
    global $additional_instructions;
    global $model;
    global $voice;

    global $learningMessages;
    global $errorResponses;

    // DIAG - Diagnostics - Ver 1.8.6
    // back_trace( 'NOTICE', 'chatbot_call_api()');
    // back_trace( 'NOTICE', 'BEGIN $user_id: ' . $user_id);
    // back_trace( 'NOTICE', 'BEGIN $page_id: ' . $page_id);
    // back_trace( 'NOTICE', 'BEGIN $session_id: ' . $session_id);
    // back_trace( 'NOTICE', 'BEGIN $thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', 'BEGIN $assistant_id: ' . $assistant_id);

    // The current ChatGPT API URL endpoint for image generation
    $api_url = 'https://api.openai.com/v1/images/generations';

    $headers = array(
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type' => 'application/json',
    );

    // Select the OpenAI Model
    // One of dall-e-2, dall-e-3
    if ( !empty($kchat_settings['model']) ) {
        $model = $kchat_settings['model'];
        // DIAG - Diagnostics - Ver 1.9.4
        // back_trace( 'NOTICE', '$model from script_data_array: ' . $model);
    } else {
        $model = esc_attr(get_option('chatbot_chatgpt_image_model_option', 'dall-e-2'));
        // DIAG - Diagnostics - Ver 1.9.4
        // back_trace( 'NOTICE', '$model from get_option: ' . $model);
    }

    // Rules
    // https://platform.openai.com/docs/api-reference/images/create

    // If $message length is greater than 1000 characters and model is dall-e-2, truncate the message to 1000 characters
    if (strlen($message) > 1000 && $model == 'dall-e-2') {
        $message = substr($message, 0, 1000);
    }
    // If $message length is greater than 4000 characters and model is dall-e-3, truncate the message to 4000 characters
    if (strlen($message) > 4000 && $model == 'dall-e-3') {
        $message = substr($message, 0, 4000);
    }

    $quantity = intval(esc_attr(get_option('chatbot_chatgpt_image_output_quantity', '1')));
    // The number of images to generate. Must be between 1 and 10. For dall-e-3, only n=1 is supported.
    if ($model == 'dall-e-3') {
        $quantity = 1;
    }

    $size = esc_attr(get_option('chatbot_chatgpt_image_output_size', '1024x1024'));
    // If the $model is dall-e-2, then size muss be one of 256x256, 512x512, or 1024x1024
    if ($model == 'dall-e-2') {
        if ($size != '256x256' && $size != '512x512' && $size != '1024x1024') {
            $size = '1024x1024';
        }
    }
    // If the $model is dall-e-3, then size muss be one of 1024x1024, 1792x1024, or 1024x1792
    if ($model == 'dall-e-3') {
        if ($size != '1024x1024' && $size != '1792x1024' && $size != '1024x1792') {
            $size = '1024x1024';
        }
    }

    $quality = esc_attr(get_option('chatbot_chatgpt_image_quality_output', 'standard'));

    $style = esc_attr(get_option('chatbot_chatgpt_image_style_output', 'vivid'));

    $user_tracking = $session_id . '-' . $user_id . '-' . $page_id . '-' . $thread_id . '-' . $assistant_id;

    // Diagnostic - Ver 1.9.5
    // back_trace( 'NOTICE', 'chatbot_calll_image_api()');
    // back_trace( 'NOTICE', 'BEGIN $message: ' . $message);
    // back_trace( 'NOTICE', 'BEGIN $model: ' . $model);
    // back_trace( 'NOTICE', 'BEGIN $quantity: ' . $quantity);
    // back_trace( 'NOTICE', 'BEGIN $size: ' . $size);
    // back_trace( 'NOTICE', 'BEGIN $quality: ' . $quality);
    // back_trace( 'NOTICE', 'BEGIN $style: ' . $style);


    if ( $model = 'dall-e-2' ) {
        // Prepare the request body
        $body = json_encode(array(
            'model' => $model,
            'prompt' => $message,
            'n' => $quantity,
            'size' => $size,
            'user' => $user_tracking,
        ));
    } elseif ( $model = 'dall-e-3' ) {
        // Prepare the request body
        $body = json_encode(array(
            'model' => $model,
            'prompt' => $message,
            'n' => $quantity,
            'size' => $size,
            'quality' => $quality,
            'style' => $style,
            'user' => $user_tracking,
        ));
    }

    // Initialize cURL session
    $ch = curl_init();

    // Set the options for the cURL request
    curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/images/generations");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ));

    // Execute the request and capture the response
    $response = curl_exec($ch);

    // Check for errors
    if(curl_errno($ch)) {
        // DIAG - Diagnostics - Ver 1.9.4
        // back_trace( 'NOTICE', 'Error: ' . curl_error($ch));
        return 'Error: ' . curl_errno($ch).' Please check Settings for a valid API key or your OpenAI account for additional information.';
    } else {
        $response_body = json_decode($response, true);
        // Process the response, which includes image data
        // Return the URL of the generated image (if applicable)
        // if (isset($response_body['data'][0]['url'])) {
        //     // back_trace( 'NOTICE', 'Generated Image URL: ' . $decoded['data'][0]['url']);
        //     $image_url = $response_body['data'][0]['url'];
        // }
    }

    // Close cURL session
    curl_close($ch);

    // DIAG - Diagnostics - Ver 1.6.7
    // back_trace( 'NOTICE', '$decoded: ' . $decoded);

    // Get the user ID and page ID
    if (empty($user_id)) {
        $user_id = get_current_user_id(); // Get current user ID
    }
    if (empty($page_id)) {
        $page_id = get_the_id(); // Get current page ID
        if (empty($page_id)) {
            // $page_id = get_queried_object_id(); // Get the ID of the queried object if $page_id is not set
            // Changed - Ver 1.9.1 - 2024 03 05
            $page_id = get_the_ID(); // Get the ID of the queried object if $page_id is not set
        }
    }


    if (!empty($response_body['data'][0]['url'])) {

        // DIAG - Diagnostics - Ver 1.9.4
        // back_trace( 'NOTICE', 'Usage - Prompt Tokens: ' . $response_body["usage"]["prompt_tokens"]);
        // back_trace( 'NOTICE', 'Usage - Completion Tokens: ' . $response_body["usage"]["completion_tokens"]);
        // back_trace( 'NOTICE', 'Usage - Total Tokens: ' . $response_body["usage"]["total_tokens"]);

        // Add the usage to the conversation tracker
        // append_message_to_conversation_log($session_id, $user_id, $page_id, 'Prompt Tokens', null, null, $response_body["usage"]["prompt_tokens"]);
        // append_message_to_conversation_log($session_id, $user_id, $page_id, 'Completion Tokens', null, null, $response_body["usage"]["completion_tokens"]);
        // append_message_to_conversation_log($session_id, $user_id, $page_id, 'Total Tokens', null, null, $response_body["usage"]["total_tokens"]);

        // return $response_body['data'][0]['url'];
        // return "![Your generated image]($image_url)";

        $image_url = '';
        $image_urls = '';

        // Check the array for the number of images generated
        for ($i = 0; $i < $quantity; $i++) {
            if (isset($response_body['data'][$i]['url'])) {
                // DIAG - Diagnostics - Ver 1.9.5
                // back_trace( 'NOTICE', 'Generated Image URL: ' . $response_body['data'][0]['url']);
                $image_url = $response_body['data'][$i]['url'];
                $image_urls .= "![Your generated image]($image_url)\n";
            }
        }
        return $image_urls;
    } else {
        // FIXME - Decide what to return here - it's an error
        // back_trace( 'ERROR', 'API ERROR ' . print_r($response_body, true));
        if (get_locale() !== "en_US") {
            $localized_errorResponses = get_localized_errorResponses(get_locale(), $errorResponses);
        } else {
            $localized_errorResponses = $errorResponses;
        }
        // Return a random error message
        return $localized_errorResponses[array_rand($localized_errorResponses)];
    }
    
}
