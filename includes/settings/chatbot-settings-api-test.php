<?php
/**
 * Kognetiks Chatbot - Settings - API/Model Test
 *
 * This file contains the code for the Chatbot settings page.
 * It allows users to configure the API key and other parameters
 * required to access the ChatGPT API from their own account.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Test API for status and errors - Ver 1.6.3 - Update Ver 2.2.1
 function kchat_test_api_status() {

    $chatbot_chatbot_ai_platform_choice = esc_attr(get_option('chatbot_chatbot_ai_platform_choice', 'OpenAI'));

    // Reset Status and Error
    if ($chatbot_chatbot_ai_platform_choice == 'OpenAI') {
        update_option('chatbot_chatgpt_api_status', 'API Error Type: Status Unknown');
        $api_key = esc_attr(get_option('chatbot_chatgpt_api_key', 'NOT SET'));
        // Decrypt the API key - Ver 2.2.6
        $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
        // Model and message for testing
        $model = esc_attr(get_option('chatbot_chatgpt_model_choice', 'gpt-3.5-turbo'));
        $updated_status = kchat_fetch_api_status($api_key, $model);
        update_option('chatbot_chatgpt_api_status', $updated_status);
    } elseif ($chatbot_chatbot_ai_platform_choice == 'NVIDIA') {
        update_option('chatbot_nvidia_api_status', 'API Error Type: Status Unknown');
        $api_key = esc_attr(get_option('chatbot_nvidia_api_key', 'NOT SET'));
        // Decrypt the API key - Ver 2.2.6
        $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
        // Model and message for testing
        $model = esc_attr(get_option('chatbot_nvidia_model_choice', 'nvidia/llama-3.1-nemotron-51b-instruct'));
        $updated_status = kchat_fetch_api_status($api_key, $model);
        update_option('chatbot_nvidia_api_status', $updated_status);
    } elseif ($chatbot_chatbot_ai_platform_choice == 'Anthropic') {
        update_option('chatbot_anthropic_api_status', 'API Error Type: Status Unknown');
        $api_key = esc_attr(get_option('chatbot_anthropic_api_key', 'NOT SET'));
        // Decrypt the API key - Ver 2.2.6
        $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
        // Model and message for testing
        $model = esc_attr(get_option('chatbot_anthropic_model_choice', 'claude-3-5-sonnet-latest'));
        $updated_status = kchat_fetch_api_status($api_key, $model);
        update_option('chatbot_anthropic_api_status', $updated_status);
    } elseif ($chatbot_chatbot_ai_platform_choice == 'Mistral') {
        update_option('chatbot_mistral_api_status', 'API Error Type: Status Unknown');
        $api_key = esc_attr(get_option('chatbot_mistral_api_key', 'NOT SET'));
        // Decrypt the API key - Ver 2.2.6
        $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
        // Model and message for testing
        $model = esc_attr(get_option('chatbot_mistral_model_choice', 'mistral-small-latest'));
        $updated_status = kchat_fetch_api_status($api_key, $model);
        update_option('chatbot_mistral_api_status', $updated_status);
    } elseif ($chatbot_chatbot_ai_platform_choice == 'DeepSeek') {
        update_option('chatbot_deepseek_api_status', 'API Error Type: Status Unknown');
        $api_key = esc_attr(get_option('chatbot_deepseek_api_key', 'NOT SET'));
        // Decrypt the API key - Ver 2.2.6
        $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
        // Model and message for testing
        $model = esc_attr(get_option('chatbot_deepseek_model_choice', 'deepseek-chat'));
        $updated_status = kchat_fetch_api_status($api_key, $model);
        update_option('chatbot_deepseek_api_status', $updated_status);
    } elseif ($chatbot_chatbot_ai_platform_choice == 'Google') {
        update_option('chatbot_google_api_status', 'API Error Type: Status Unknown');
        $api_key = esc_attr(get_option('chatbot_google_api_key', 'NOT SET'));
        // Decrypt the API key - Ver 2.2.6
        $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
        // Model and message for testing
        $model = esc_attr(get_option('chatbot_google_model_choice', 'gemini-2.0-flash'));
        $updated_status = kchat_fetch_api_status($api_key, $model);
        update_option('chatbot_google_api_status', $updated_status);
    } elseif ($chatbot_chatbot_ai_platform_choice == 'Markov Chain') {
        $updated_status = 'API Testing Not Required';
        update_option('chatbot_markov_chain_api_status', 'API Error Type: Status Unknown');
        update_option('chatbot_markov_chain_api_status', $updated_status);
    } elseif ($chatbot_chatbot_ai_platform_choice == 'Transformer') {
        $updated_status = 'API Testing Not Required';
        update_option('chatbot_transformer_model_api_status', 'API Error Type: Status Unknown');
        update_option('chatbot_transformer_model_api_status', $updated_status);
    } elseif ($chatbot_chatbot_ai_platform_choice == 'Local Server') {
        $updated_status = 'API Testing Not Required';
        $api_key = esc_attr(get_option('chatbot_local_server_api_key', 'NOT SET'));
        // Decrypt the API key - Ver 2.2.6
        $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
        // Model and message for testing
        $model = esc_attr(get_option('chatbot_local_server_model_choice', 'local-server'));
        $updated_status = kchat_fetch_api_status($api_key, $model);
        update_option('chatbot_local_server_api_status', 'API Error Type: Status Unknown');
        update_option('chatbot_local_server_api_status', $updated_status);
    } else {
        $updated_status = 'API Error Type: Platform Choice Invalid';
    }

    return $updated_status;

}

// Test API for status and errors
function kchat_fetch_api_status($api_key, $model) {

    $chatbot_ai_platform_choice = esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI'));

    $test_message = 'Write a one sentence response to this test message.';

    switch ($chatbot_ai_platform_choice) {

        case 'OpenAI':

            update_option('chatbot_chatgpt_api_status', 'API Error Type: Status Unknown');
            $api_key = esc_attr(get_option('chatbot_chatgpt_api_key', 'NOT SET'));
            // Decrypt the API key - Ver 2.2.6
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);

            // Model and message for testing
            $model = esc_attr(get_option('chatbot_chatgpt_model_choice', 'chatgpt-4o-latest'));

            // The current API URL endpoint
            // $api_url = 'https://api.openai.com/v1/chat/completions';
            $api_url = get_chat_completions_api_url();

            $headers = array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            );

            $body = array(
                'model' => $model,
                'max_tokens' => 100,
                'temperature' => 0.5,
                'messages' => array(
                    array('role' => 'system', 'content' => 'You are a test function for Chat.'),
                    array('role' => 'user', 'content' => $test_message)
                ),
            );

            // DIAG - Diagnostics - Ver 2.1.8
            // back_trace( 'NOTICE', 'API Body: ' . print_r(json_encode($body),true));

            $args = array(
                'headers' => $headers,
                'body' => json_encode($body),
                'method' => 'POST',
                'data_format' => 'body',
                'timeout' => 50,
            );

            $response = wp_remote_post($api_url, $args);

            // DIAG - Diagnostics - Ver 2.1.8
            // back_trace( 'NOTICE', 'API Response: ' . print_r(json_encode($response),true));

            if (is_wp_error($response)) {
                // DIAG - Log the response body
                // back_trace( 'ERROR', $response->get_error_message());
                return 'WP_Error: ' . $response->get_error_message() . '. Please check Settings for a valid API key or your AI Platform vendor account for additional information.';
            }

            $response_body = json_decode(wp_remote_retrieve_body($response), true);

            // DIAG - Log the response body
            // back_trace( 'NOTICE', '$response_body: ' . print_r($response_body,true));

            // Check for API-specific errors
            //
            // https://platform.openai.com/docs/guides/error-codes/api-errors
            //
            $chatbot_ai_platform_choice = esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI'));

            if (isset($response_body['error'])) {

                $error_type = $response_body['error']['type'] ?? 'Unknown';
                $error_message = $response_body['error']['message'] ?? 'No additional information.';
                $updated_status = 'API Error Type: ' . $error_type . ' Message: ' . $error_message;

            } elseif (!empty($response_body['choices'])) {

                $updated_status = 'Success: Connection to the ' . $chatbot_ai_platform_choice . ' API was successful!';
                // back_trace( 'SUCCESS', 'API Status: ' . $updated_status);

            
            } else {

                $updated_status = 'Error: Unable to fetch response from the ' . $chatbot_ai_platform_choice . ' API. Please check Settings for a valid API key or your ' . $chatbot_ai_platform_choice . ' account for additional information.';
                // back_trace( 'ERROR', 'API Status: ' . $updated_status);

            }

            update_option('chatbot_chatgpt_api_status', $updated_status);

            return $updated_status;
            
            break;

        case 'NVIDIA':

            update_option('chatbot_nvidia_api_status', 'API Error Type: Status Unknown');
            $api_key = esc_attr(get_option('chatbot_nvidia_api_key', 'NOT SET'));
            // Decrypt the API key - Ver 2.2.6
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);

            // Model and message for testing
            $model = esc_attr(get_option('chatbot_nvidia_model_choice', 'nvidia/llama-3.1-nemotron-51b-instruct'));

            // The current API URL endpoint
            // $api_url = 'https://api.openai.com/v1/chat/completions';
            $api_url = get_chat_completions_api_url();

            $headers = array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            );

            $body = array(
                'model' => $model,
                'max_tokens' => 100,
                'temperature' => 0.5,
                'messages' => array(
                    array('role' => 'system', 'content' => 'You are a test function for Chat.'),
                    array('role' => 'user', 'content' => $test_message)
                ),
            );

            // DIAG - Diagnostics - Ver 2.1.8
            // back_trace( 'NOTICE', 'API Body: ' . print_r(json_encode($body),true));

            $args = array(
                'headers' => $headers,
                'body' => json_encode($body),
                'method' => 'POST',
                'data_format' => 'body',
                'timeout' => 50,
            );

            $response = wp_remote_post($api_url, $args);

            // DIAG - Diagnostics - Ver 2.1.8
            // back_trace( 'NOTICE', 'API Response: ' . print_r(json_encode($response),true));

            if (is_wp_error($response)) {
                // DIAG - Log the response body
                // back_trace( 'ERROR', $response->get_error_message());
                return 'WP_Error: ' . $response->get_error_message() . '. Please check Settings for a valid API key or your AI Platform vendor account for additional information.';
            }

            $response_body = json_decode(wp_remote_retrieve_body($response), true);

            // DIAG - Log the response body
            // back_trace( 'NOTICE', '$response_body: ' . print_r($response_body,true));

            // Check for API-specific errors
            //
            // https://platform.openai.com/docs/guides/error-codes/api-errors
            //
            $chatbot_ai_platform_choice = esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI'));

            if (isset($response_body['error'])) {

                $error_type = $response_body['error']['type'] ?? 'Unknown';
                $error_message = $response_body['error']['message'] ?? 'No additional information.';
                $updated_status = 'API Error Type: ' . $error_type . ' Message: ' . $error_message;

            } elseif (!empty($response_body['choices'])) {

                $updated_status = 'Success: Connection to the ' . $chatbot_ai_platform_choice . ' API was successful!';
                // back_trace( 'SUCCESS', 'API Status: ' . $updated_status);

            
            } else {

                $updated_status = 'Error: Unable to fetch response from the ' . $chatbot_ai_platform_choice . ' API. Please check Settings for a valid API key or your ' . $chatbot_ai_platform_choice . ' account for additional information.';
                // back_trace( 'ERROR', 'API Status: ' . $updated_status);
                
            }

            update_option('chatbot_chatgpt_api_status', $updated_status);

            return $updated_status;
            
            break;

        case 'Anthropic':

            update_option('chatbot_anthropic_api_status', 'API Error Type: Status Unknown');
            $api_key = esc_attr(get_option('chatbot_anthropic_api_key', 'NOT SET'));
            
            // Model and message for testing
            $model = esc_attr(get_option('chatbot_anthropic_model_choice', 'claude-3-5-sonnet-latest'));
            
            // The current ChatGPT API URL endpoint for chatgpt-4o-latest
            $api_url = ' https://api.anthropic.com/v1/messages';

            // Set the headers
            $headers = array(
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json'
            );

            // Set the body
            $body = array(
                'model' => $model,
                'max_tokens' => 100,
                'system' => 'You are a helpful assistant.',
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => $test_message
                    )
                )
            );

            // Encode the body
            $body = json_encode($body);

            $timeout = esc_attr(get_option('chatbot_anthropic_timeout_setting', 240 ));

            // DIAG - Diagnostics
            // back_trace( 'NOTICE', 'URL: ' . $api_url);
            // back_trace( 'NOTICE', 'Headers: ' . print_r($headers, true));
            // back_trace( 'NOTICE', 'Body: ' . $body);

            // Call the API
            $response = wp_remote_post($api_url, array(
                'headers' => $headers,
                'body' => $body,
                'timeout' => $timeout,
            ));

            // Get the response body
            // $response_data = json_decode(wp_remote_retrieve_body($response));

            // API Call
            $response = wp_remote_post($api_url, array(
                'headers' => $headers,
                'body'    => $body,
                'timeout' => $timeout,
            ));


            // Handle WP Error
            if (is_wp_error($response)) {

                // DIAG - Diagnostics
                prod_trace( 'ERROR', 'Error: ' . $response->get_error_message());
                return isset($errorResponses['api_error']) ? $errorResponses['api_error'] : 'An API error occurred.';

            }

            // Retrieve and Decode Response
            $response_data = json_decode(wp_remote_retrieve_body($response), true);

            // Check for API-specific errors
            
            if (isset($response_data['error'])) {

                // Extract error type and message safely
                $error_type = $response_data['error']['type'] ?? 'Unknown Error Type';
                $error_message = $response_data['error']['message'] ?? 'No additional information.';
            
                // Handle error response
                $updated_status = 'API Error Type: ' . $error_type . ' Message: ' . $error_message;
                // back_trace( 'ERROR', 'API Status: ' . $updated_status);
            
            } elseif (isset($response_data['type']) && $response_data['type'] === 'message') {

                // Handle successful response
                $content_type = $response_data['content'][0]['type'] ?? 'Unknown Content Type';
                $content_text = $response_data['content'][0]['text'] ?? 'No content available.';
            
                // Handle successful response
                $updated_status = 'Success: Connection to the ' . $chatbot_ai_platform_choice . ' API was successful!';
                // back_trace( 'SUCCESS', 'API Status: ' . $updated_status);

            } else {

                // Handle unexpected response structure
                $updated_status = 'Error: Unexpected response format from the ' . $chatbot_ai_platform_choice . ' API. Please check Settings for a valid API key or your ' . $chatbot_ai_platform_choice . ' account for additional information.';
                // back_trace( 'ERROR', 'API Status: ' . $updated_status);

            }
            
            update_option('chatbot_anthropic_api_status', $updated_status);

            return $updated_status;

            break;

        case 'DeepSeek':

            update_option('chatbot_deepseek_api_status', 'API Error Type: Status Unknown');
            $api_key = esc_attr(get_option('chatbot_deepseek_api_key', 'NOT SET'));
            // Decrypt the API key - Ver 2.2.6
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            
            // Model and message for testing
            $model = esc_attr(get_option('chatbot_deepseek_model_choice', 'deepseek-chat'));
            
            // The current DeepSeek API URL endpoint
            $api_url = 'https://api.deepseek.com/chat/completions';

            // Set the headers
            $headers = array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            );

            // Set the body
            $body = array(
                'model' => $model,
                'messages' => array(
                    array(
                        'role' => 'system',
                        'content' => 'You are a helpful assistant.'
                    ),
                    array(
                        'role' => 'user',
                        'content' => $test_message
                    )
                ),
                'stream' => false,
            );

            // Encode the body
            $body = json_encode($body);

            // DIAG - Diagnostics
            // back_trace( 'NOTICE', 'URL: ' . $api_url);
            // back_trace( 'NOTICE', 'Headers: ' . print_r($headers, true));
            // back_trace( 'NOTICE', 'Body: ' . $body);

            // Call the API
            $response = wp_remote_post($api_url, array(
                'headers' => $headers,
                'body' => $body
            ));

            // Get the response body
            $response_data = json_decode(wp_remote_retrieve_body($response));

            // DIAG - Diagnostics
            // back_trace( 'NOTICE', 'Response: ' . print_r($response_data, true));

            // Check for API-specific errors
            if (isset($response_data->error)) {

                // Extract error type and message safely
                $error_type = $response_data->error->type ?? 'Unknown Error Type';
                $error_message = $response_data->error->message ?? 'No additional information.';
            
                // Handle error response
                $updated_status = 'API Error Type: ' . $error_type . ' Message: ' . $error_message;
                // back_trace( 'ERROR', 'API Status: ' . $updated_status);
            
            } elseif (isset($response_data->choices[0]->message)) {

                // Handle successful response
                $content_type = $response_data->choices[0]->message->role ?? 'Unknown Content Type';
                $content_text = $response_data->choices[0]->message->content ?? 'No content available.';
            
                // Handle successful response
                $updated_status = 'Success: Connection to the ' . $chatbot_ai_platform_choice . ' API was successful!';
                // back_trace( 'SUCCESS', 'API Status: ' . $updated_status);

            } else {

                // Handle unexpected response structure
                $updated_status = 'Error: Unexpected response format from the ' . $chatbot_ai_platform_choice . ' API. Please check Settings for a valid API key or your ' . $chatbot_ai_platform_choice . ' account for additional information.';
                // back_trace( 'ERROR', 'API Status: ' . $updated_status);

            }
            
            update_option('chatbot_deepseek_api_status', $updated_status);

            return $updated_status;

            break;

        case 'Mistral':

            update_option('chatbot_mistral_api_status', 'API Error Type: Status Unknown');
            $api_key = esc_attr(get_option('chatbot_mistral_api_key', 'NOT SET'));
            // Decrypt the API key - Ver 2.2.6
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            
            // Model and message for testing
            $model = esc_attr(get_option('chatbot_mistral_model_choice', 'mistral-small-latest'));
            
            // The current DeepSeek API URL endpoint
            $api_url = 'https://api.mistral.ai/v1/chat/completions';

            // Set the headers
            $headers = array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            );

            // Set the body
            $body = array(
                'model' => $model,
                'messages' => array(
                    array(
                        'role' => 'system',
                        'content' => 'You are a helpful assistant.'
                    ),
                    array(
                        'role' => 'user',
                        'content' => $test_message
                    )
                ),
                'stream' => false,
            );

            // Encode the body
            $body = json_encode($body);

            // DIAG - Diagnostics
            // back_trace( 'NOTICE', 'URL: ' . $api_url);
            // back_trace( 'NOTICE', 'Headers: ' . print_r($headers, true));
            // back_trace( 'NOTICE', 'Body: ' . $body);

            // Call the API
            $response = wp_remote_post($api_url, array(
                'headers' => $headers,
                'body' => $body
            ));

            // Get the response body
            $response_data = json_decode(wp_remote_retrieve_body($response));

            // DIAG - Diagnostics
            // back_trace( 'NOTICE', 'Response: ' . print_r($response_data, true));

            // Check for API-specific errors
            if (isset($response_data->error)) {

                // Extract error type and message safely
                $error_type = $response_data->error->type ?? 'Unknown Error Type';
                $error_message = $response_data->error->message ?? 'No additional information.';
            
                // Handle error response
                $updated_status = 'API Error Type: ' . $error_type . ' Message: ' . $error_message;
                // back_trace( 'ERROR', 'API Status: ' . $updated_status);
            
            } elseif (isset($response_data->choices[0]->message)) {

                // Handle successful response
                $content_type = $response_data->choices[0]->message->role ?? 'Unknown Content Type';
                $content_text = $response_data->choices[0]->message->content ?? 'No content available.';
            
                // Handle successful response
                $updated_status = 'Success: Connection to the ' . $chatbot_ai_platform_choice . ' API was successful!';
                // back_trace( 'SUCCESS', 'API Status: ' . $updated_status);

            } else {

                // Handle unexpected response structure
                $updated_status = 'Error: Unexpected response format from the ' . $chatbot_ai_platform_choice . ' API. Please check Settings for a valid API key or your ' . $chatbot_ai_platform_choice . ' account for additional information.';
                // back_trace( 'ERROR', 'API Status: ' . $updated_status);

            }
            
            update_option('chatbot_mistral_api_status', $updated_status);

            return $updated_status;

            break;

        case 'Google':

            update_option('chatbot_google_api_status', 'API Error Type: Status Unknown');
            $api_key = esc_attr(get_option('chatbot_google_api_key', 'NOT SET'));
            // Decrypt the API key - Ver 2.2.6
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            
            // Model and message for testing
            $model = esc_attr(get_option('chatbot_google_model_choice', 'gemini-2.0-flash'));
            
            // Get the base URL from settings
            $google_base_url = esc_attr(get_option('chatbot_google_base_url', 'https://generativelanguage.googleapis.com/v1beta/models/'));
            $google_base_url = rtrim($google_base_url, '/');
            
            // Google API endpoint format: https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent
            $api_url = $google_base_url . '/' . $model . ':generateContent';
            
            // Add API key as query parameter
            $api_url = add_query_arg('key', $api_key, $api_url);

            // Set the headers
            $headers = array(
                'Content-Type' => 'application/json'
            );

            // Set the body - Google API uses 'contents' instead of 'messages'
            $body = array(
                'contents' => array(
                    array(
                        'parts' => array(
                            array(
                                'text' => $test_message
                            )
                        )
                    )
                )
            );

            // Get timeout setting
            $timeout = intval(esc_attr(get_option('chatbot_google_timeout_setting', 240)));

            // Encode the body
            $body = json_encode($body);

            // DIAG - Diagnostics
            // back_trace( 'NOTICE', 'URL: ' . $api_url);
            // back_trace( 'NOTICE', 'Headers: ' . print_r($headers, true));
            // back_trace( 'NOTICE', 'Body: ' . $body);

            // Call the API
            $response = wp_remote_post($api_url, array(
                'headers' => $headers,
                'body' => $body,
                'timeout' => $timeout
            ));

            // Handle WP Error
            if (is_wp_error($response)) {
                // DIAG - Diagnostics
                prod_trace( 'ERROR', 'Error: ' . $response->get_error_message());
                return 'WP_Error: ' . $response->get_error_message() . '. Please check Settings for a valid API key or your Google account for additional information.';
            }

            // Retrieve and Decode Response
            $response_data = json_decode(wp_remote_retrieve_body($response), true);

            // DIAG - Diagnostics
            // back_trace( 'NOTICE', 'Response: ' . print_r($response_data, true));

            // Check for API-specific errors
            if (isset($response_data['error'])) {
                // Extract error type and message safely
                $error_type = $response_data['error']['status'] ?? 'Unknown Error Type';
                $error_message = $response_data['error']['message'] ?? 'No additional information.';
            
                // Handle error response
                $updated_status = 'API Error Type: ' . $error_type . ' Message: ' . $error_message;
                // back_trace( 'ERROR', 'API Status: ' . $updated_status);
            
            } elseif (isset($response_data['candidates']) && !empty($response_data['candidates'])) {
                // Google API uses 'candidates' instead of 'choices'
                // Handle successful response
                $updated_status = 'Success: Connection to the ' . $chatbot_ai_platform_choice . ' API was successful!';
                // back_trace( 'SUCCESS', 'API Status: ' . $updated_status);

            } else {
                // Handle unexpected response structure
                $updated_status = 'Error: Unexpected response format from the ' . $chatbot_ai_platform_choice . ' API. Please check Settings for a valid API key or your ' . $chatbot_ai_platform_choice . ' account for additional information.';
                // back_trace( 'ERROR', 'API Status: ' . $updated_status);

            }
            
            update_option('chatbot_google_api_status', $updated_status);

            return $updated_status;

            break;

        case 'Local Server':

            update_option('chatbot_mistral_api_status', 'API Error Type: Status Unknown');
            $api_key = esc_attr(get_option('chatbot_local_server_api_key', 'NOT SET'));
            // Decrypt the API key - Ver 2.2.6
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            
            // Model and message for testing
            $model = esc_attr(get_option('chatbot_local_server_model_choice', 'llama3.2-3b-instruct'));
            
            // The current DeepSeek API URL endpoint
            // $api_url = 'https://127.0.0.1:1337/v1/chat/completions';
            $api_url = get_chat_completions_api_url();

            // API key for the local server - Typically not needed
            $api_key = esc_attr(get_option('chatbot_local_api_key', ''));
            // Decrypt the API key - Ver 2.2.6
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);

            // Set the headers
            $headers = array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            );

            // Retrieve model settings
            $model = esc_attr(get_option('chatbot_local_model_choice', 'llama3.2-3b-instruct'));
            // DIAG - Diagnostics
            // back_trace( 'NOTICE', '$model: ' . $model);
            $max_tokens = intval(get_option('chatbot_local_max_tokens_setting', 10000));
            $temperature = floatval(get_option('chatbot_local_temperature', 0.8));
            $top_p = floatval(get_option('chatbot_local_top_p', 0.95));
            $context = esc_attr(get_option('chatbot_local_conversation_context', 'You are a versatile, friendly, and helpful assistant that responds using Markdown syntax.'));
            $timeout = intval(get_option('chatbot_local_timeout_setting', 360));

            $message = 'Test message';

            // Construct request body to match the expected schema
            $body = array(
                'model' => $model,
                'stream' => null,
                'max_tokens' => $max_tokens,
                'stop' => array("End"),
                'frequency_penalty' => 0.2,
                'presence_penalty' => 0.6,
                'temperature' => $temperature,
                'top_p' => $top_p,
                'modalities' => array("text"),
                'audio' => array(
                    'voice' => 'default',
                    'format' => 'mp3'
                ),
                'store' => null,
                'metadata' => array(
                    'type' => 'conversation'
                ),
                'logit_bias' => array(
                    "15496" => -100,
                    "51561" => -100
                ),
                'logprobs' => null,
                'n' => 1,
                'response_format' => array('type' => 'text'),
                'seed' => 123,
                'stream_options' => null,
                // 'tools' => array(
                //     array(
                //         'type' => 'function',
                //         'function' => array(
                //             'name' => '',
                //             'parameters' => array(),
                //             'strict' => null
                //         )
                //     )
                // ),
                'tools' => null,
                'parallel_tool_calls' => null,
                'messages' => array(
                    array('role' => 'system', 'content' => $context),
                    array('role' => 'user', 'content' => $message)
                )
            );

            // API request arguments
            $args = array(
                'headers' => $headers,
                'body'    => json_encode($body),
                'method'  => 'POST',
                'timeout' => $timeout,
                'data_format' => 'body',
            );

            // DIAG - Diagnostics
            // back_trace( 'NOTICE', 'URL: ' . $api_url);
            // back_trace( 'NOTICE', 'Headers: ' . print_r($headers, true));
            // back_trace( 'NOTICE', 'Body: ' . $body);

            // Send request
            $response = wp_remote_post($api_url, $args);

            // Get the response body
            $response_data = json_decode(wp_remote_retrieve_body($response));

            // DIAG - Diagnostics
            // back_trace( 'NOTICE', 'Response: ' . print_r($response_data, true));

            // Handle request errors
            if (is_wp_error($response)) {
                return 'Error: ' . $response->get_error_message() . ' Please check Settings for a valid API key.';
            }

            // Check for API-specific errors
            if (isset($response_data->error)) {

                // Extract error type and message safely
                $error_type = $response_data->error->type ?? 'Unknown Error Type';
                $error_message = $response_data->error->message ?? 'No additional information.';
            
                // Handle error response
                $updated_status = 'API Error Type: ' . $error_type . ' Message: ' . $error_message;
                // back_trace( 'ERROR', 'API Status: ' . $updated_status);
            
            } elseif (isset($response_data->choices[0]->message)) {

                // Handle successful response
                $content_type = $response_data->choices[0]->message->role ?? 'Unknown Content Type';
                $content_text = $response_data->choices[0]->message->content ?? 'No content available.';
            
                // Handle successful response
                $updated_status = 'Success: Connection to the ' . $chatbot_ai_platform_choice . ' API was successful!';
                // back_trace( 'SUCCESS', 'API Status: ' . $updated_status);

            } else {

                // Handle unexpected response structure
                $updated_status = 'Error: Unexpected response format from the ' . $chatbot_ai_platform_choice . ' API. Please check Settings for a valid API key or your ' . $chatbot_ai_platform_choice . ' account for additional information.';
                // back_trace( 'ERROR', 'API Status: ' . $updated_status);

            }
            
            update_option('chatbot_mistral_api_status', $updated_status);

            return $updated_status;

            break;

        case 'Transformer':

            $updated_status = 'API Testing Not Required';
            update_option('chatbot_transformer_model_api_status', 'API Testing Not Required');
            update_option('chatbot_transformer_model_api_status', $updated_status);

            break;

        default:

            $updated_status = 'API Error Type: Platform Choice Invalid';

            update_option('api_status', $updated_status);
            
            break;

    }

    return $updated_status;

}

// This function is executed whenever any option is updated
function chatgpt_option_updated($option_name, $old_value, $new_value) {

    // Check if the "Diagnostics" tab is active
    if ($option_name !== 'chatgpt_model_choice') {
        return;
    } elseif ($option_name !== 'chatbot_chatgpt_api_key') {
        return;
    }

    // DIAG - Log Function Call
    // back_trace( 'NOTICE', 'chatgpt_option_updated() called');

    // FIXME Retrieve the current value of the chatbot_chatgpt_api_status option
    $chatbot_chatgpt_api_status = esc_attr(get_option('chatbot_chatgpt_api_status', 'NOT SET'));
    // DIAG - Log the current value of the chatbot_chatgpt_api_status option
    // back_trace( 'NOTICE', $chatbot_chatgpt_api_status);
    
    // Check if the option updated is related to your plugin settings
    // if ($option_name === 'chatbot_chatgpt_model_choice' || $option_name === 'chatbot_chatgpt_api_key' || empty($chatbot_chatgpt_api_status)) {
    if ($option_name === 'chatbot_chatgpt_model_choice' || $option_name === 'chatbot_chatgpt_api_key') {
        $api_key = esc_attr(get_option('chatbot_chatgpt_api_key', 'NOT SET'));
        // Decrypt the API key - Ver 2.2.6
        $api_key = chatbot_chatgpt_decrypt_api_key($api_key);

        // Call your test function
        $test_result = kchat_test_api_status($api_key);
        // DIAG - Log the test result
        // back_trace( 'WARNING', '$test_result' . $test_result);        

        // DIAG - Set the option in the admin_notice function uses to display messages
        update_option('chatbot_chatgpt_api_status', $test_result);

        // I could directly call display_option_value_admin_notice() here, but
        // that's generally not a good practice unless absolutely necessary
        // display_option_value_admin_notice();

    }
}

// Hook into the updated_option action
add_action('updated_option', 'chatgpt_option_updated', 10, 3);
