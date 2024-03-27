<?php
/**
 * Kognetiks Chatbot for WordPress - ChatGPT IMAGE API - Ver 1.9.4
 *
 * This file contains the code for generating images using the 
 * the DALL-2 or DALL-3 API.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Call the ChatGPT API
function chatbot_chatgpt_call_image_api($api_key, $message) {

    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;
    global $script_data_array;
    global $additional_instructions;
    global $model;

    global $learningMessages;
    global $errorResponses;

    // DIAG - Diagnostics - Ver 1.8.6
    // back_trace( 'NOTICE', 'chatbot_chatgpt_call_api()');
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
    if ( !empty($script_data_array['model']) ) {
        $model = $script_data_array['model'];
        // DIAG - Diagnostics - Ver 1.9.4
        // back_trace( 'NOTICE', '$model from script_data_array: ' . $model);
    } else {
        $model = esc_attr(get_option('chatbot_chatgpt_model_choice', 'dall-e-2'));
        // DIAG - Diagnostics - Ver 1.9.4
        // back_trace( 'NOTICE', '$model from get_option: ' . $model);
    }

    // Prepare the request body
    $body = json_encode(array(
        'model' => $model,
        'prompt' => $message,
        'n' => 1, // Number of images to generate
        'size' => '1024x1024' // Optional: specify size (for DALL-E 2)
    ));

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
        if (isset($response_body['data'][0]['url'])) {
            // back_trace( 'NOTICE', 'Generated Image URL: ' . $decoded['data'][0]['url']);
            $image_url = $response_body['data'][0]['url'];
        }
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
        return "![Your generated image]($image_url)";

    } else {
        // FIXME - Decide what to return here - it's an error
        back_trace( 'ERROR', 'API ERROR ' . print_r($response_body, true));
        if (get_locale() !== "en_US") {
            $localized_errorResponses = get_localized_errorResponses(get_locale(), $errorResponses);
        } else {
            $localized_errorResponses = $errorResponses;
        }
        // Return a random error message
        return $localized_errorResponses[array_rand($localized_errorResponses)];
    }
    
}


