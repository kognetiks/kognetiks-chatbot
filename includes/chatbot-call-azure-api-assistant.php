<?php
/**
 * Kognetiks Chatbot - Azure OpenAI Assistants - Ver 2.2.6
 *
 * This file contains the code for access the Azure OpenAI Assistants API.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// -------------------------------------------------------------------------
// Step 1: Create a thread
// -------------------------------------------------------------------------
function create_an_azure_assistant($api_key) {

    // DIAG - Diagnostics - Ver 2.2.6
    // back_trace( 'NOTICE', 'Step 1 - create_an_azure_assistant()');

    // Set your API key and assistant ID here:
    $api_key = esc_attr(get_option('chatbot_azure_api_key', ''));
    // Decrypt the API key - Ver 2.2.6
    $api_key = chatbot_chatgpt_decrypt_api_key($api_key);

    $chatbot_azure_resource_name = esc_attr(get_option('chatbot_azure_resource_name', 'YOUR_RESOURCE_NAME'));
    $chatbot_azure_deployment_name = esc_attr(get_option('chatbot_azure_deployment_name', 'DEPLOYMENT_NAME'));
    $chatbot_azure_api_version = esc_attr(get_option('chatbot_azure_api_version', '2024-08-01-preview'));

    // Assemble the URL
    $url = sprintf(
        'https://%s.openai.azure.com/openai/threads?api-version=%s',
        $chatbot_azure_resource_name,
        $chatbot_azure_api_version
    );

    // DIAG - Diagnostics - Ver 2.2.6
    // back_trace( 'NOTICE', '$url: ' . $url);

    // Prepare common headers
    $headers = array(
        'Content-Type' => 'application/json',
        'api-key'      => trim($api_key),
    );

    $response = wp_remote_post($url, [
        "headers"       => $headers,
        "timeout"       => 30,
    ]);

    // Retrieve API response
    $body = wp_remote_retrieve_body($response);
    // back_trace( 'NOTICE', 'Thread Response: ' . print_r($body, true));

    $thread_response = json_decode($body, true);

    // Handle API errors
    if (isset($thread_response['error'])) {
        // back_trace( 'ERROR', 'OpenAI API Error: ' . json_encode($thread_response['error'], JSON_PRETTY_PRINT));
        return "Error: " . $thread_response['error']['message'];
    }

    // Ensure thread ID is present
    if (!isset($thread_response["id"])) {
        // back_trace( 'ERROR', 'Thread ID Missing in Response: ' . print_r($thread_response, true));
        return "Error: Thread ID not returned.";
    }

    // DIAG - Diagnostics - Ver 2.2.6
    // back_trace( 'NOTICE', 'Step 1 - $thread_response["id"]: ' . $thread_response["id"]);

    return $thread_response;

}

// -------------------------------------------------------------------------
// Step 2: EMPTY STEP
// -------------------------------------------------------------------------

// -------------------------------------------------------------------------
// Step 3: Add a message
// -------------------------------------------------------------------------
function add_an_azure_message($thread_id, $prompt, $context, $api_key, $file_id = null, $message_uuid = null) {

    // DIAG - Diagnostics - Ver 2.2.6
    // back_trace( 'NOTICE', 'Step 3 - add_an_azure_message()');
    // back_trace( 'NOTICE', '$thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', '$prompt: ' . $prompt);
    // back_trace( 'NOTICE', '$context: ' . $context);
    // back_trace( 'NOTICE', '$file_id: ' . print_r($file_id, true));

    global $session_id;

    // Set your API key and assistant ID here:
    $api_key = esc_attr(get_option('chatbot_azure_api_key', ''));
    // Decrypt the API key - Ver 2.2.6
    $api_key = chatbot_chatgpt_decrypt_api_key($api_key);

    $chatbot_azure_resource_name = esc_attr(get_option('chatbot_azure_resource_name', 'YOUR_RESOURCE_NAME'));
    $chatbot_azure_deployment_name = esc_attr(get_option('chatbot_azure_deployment_name', 'DEPLOYMENT_NAME'));
    $chatbot_azure_api_version = esc_attr(get_option('chatbot_azure_api_version', '2024-08-01-preview'));
    
    // Assemble the URL
    $url = sprintf(
        'https://%s.openai.azure.com/openai/threads/%s/messages?api-version=%s',
        $chatbot_azure_resource_name,
        $thread_id,
        $chatbot_azure_api_version
    );

    // DIAG - Diagnostics - Ver 2.2.3
    // back_trace( 'NOTICE', '$url: ' . $url);

    // Prepare common headers
    $headers = array(
        'Content-Type' => 'application/json',
        'api-key'      => trim($api_key),
    );

    // *********************************************************************************
    // FILE ID IS NULL
    // *********************************************************************************
    if ( empty($file_id) ) {

        // DIAG - Diagnostics - Ver 2.2.6
        // back_trace( 'NOTICE', 'No files attached, just send the prompt');

        // No files attached, just send the prompt
        $data = [
            'role' => 'user',
            'content' => [
                [
                    'type' => 'text',
                    'text' => $prompt,
                ]
            ],
        ];

    }

    if ( !empty($file_id) ) {

        $assistant_beta_version = esc_attr(get_option('chatbot_azure_assistant_beta_version', 'v2'));
        if ( $assistant_beta_version == 'v2' ) {
            $beta_version = "assistants=v2";
        } else {
            $beta_version = "assistants=v1";
        }
    
        // *********************************************************************************
        // Decide which helper to use
        // *********************************************************************************

        // FIXME - Retrieve the first item file type - assumes they are all the same, not mixed
        $file_type = get_chatbot_chatgpt_transients_files('chatbot_chatgpt_assistant_file_type', $session_id, $file_id[0]);
        $file_type = $file_type ? $file_type : 'unknown';
    
        // DIAG - Diagnostics - Ver 2.0.3
        // back_trace( 'NOTICE', 'Files attached, $file_id[0]: ' . $file_id[0]);
        // back_trace( 'NOTICE', 'Files attached, $file_type: ' . $file_type);

        // *********************************************************************************
        // NON-IMAGE ATTACHMENTS - Ver 2.0.3
        // *********************************************************************************

        if ( $file_type == 'assistants' ) {
            $data = chatbot_chatgpt_text_attachment($prompt, $file_id, $beta_version);
            // DIAG - Diagnostics - Ver 2.2.6
            // back_trace( 'NOTICE', 'Text Attachment - $data: ' . print_r($data, true));
        }

        // *********************************************************************************
        // IMAGE ATTACHMENTS - Ver 2.0.3
        // *********************************************************************************

        if ( $file_type == 'vision' ) {
            $data = chatbot_chatgpt_image_attachment($prompt, $file_id, $beta_version);
            // DIAG - Diagnostics - Ver 2.2.6
            // back_trace( 'NOTICE', 'Image Attachment - $data: ' . print_r($data, true));
        }

    }

    // POST request using WordPress HTTP API
    $response = wp_remote_post($url, [
        'headers'       => $headers, 
        'body'          => json_encode($data), 
        'timeout'       => 30,
    ]);

    // Check for WP_Error
    if (is_wp_error($response)) {
        prod_trace('ERROR', 'WP_Error: ' . $response->get_error_message());
        return null;
    }

    // Retrieve response body
    $response_body = wp_remote_retrieve_body($response);

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', 'add_an_azure_message() - $response_body: ' . print_r($response_body, true));
    
    // Return the API response
    return json_decode($response_body, true);

}

// -------------------------------------------------------------------------
// Step 4: Run the Assistant
// -------------------------------------------------------------------------
function run_an_azure_assistant($thread_id, $assistant_id, $context, $api_key, $message_uuid = null) {

    // DIAG - Diagnostics - Ver 2.2.6
    // back_trace( 'NOTICE', 'Step 4 - run_an_azure_assistant()');
    // back_trace( 'NOTICE', 'Step 4 - $thread_id: ' . $thread_id);

    global $kchat_settings;
    
    $chatbot_azure_resource_name = esc_attr(get_option('chatbot_azure_resource_name', 'YOUR_RESOURCE_NAME'));
    $chatbot_azure_deployment_name = esc_attr(get_option('chatbot_azure_deployment_name', 'DEPLOYMENT_NAME'));
    $chatbot_azure_api_version = esc_attr(get_option('chatbot_azure_api_version', '2024-08-01-preview'));
    
    // Assemble the URL
    $url = sprintf(
        'https://%s.openai.azure.com/openai/threads/%s/runs?api-version=%s',
        $chatbot_azure_resource_name,
        $thread_id,
        $chatbot_azure_api_version
    );

    // DIAG - Diagnostics - Ver 2.2.6
    // back_trace( 'NOTICE', '$url: ' . $url);

    // Prepare common headers
    $headers = array(
        'Content-Type' => 'application/json',
        'api-key'      => trim($api_key),
    );

    $max_prompt_tokens = (int) esc_attr(get_option('chatbot_chatgpt_max_prompt_tokens', 20000));
    $max_completion_tokens = (int) esc_attr(get_option('chatbot_chatgpt_max_completion_tokens', 20000));
    $temperature = (float) esc_attr(get_option('chatbot_chatgpt_temperature', 0.5));
    $top_p = (float) esc_attr(get_option('chatbot_chatgpt_top_p', 1.0));

    // Additional instructions - Ver 2.2.6
    $additional_instruction = null;
    if (isset($kchat_settings['additional_instructions']) && $kchat_settings['additional_instructions'] !== null) {
        $additional_instructions = $kchat_settings['additional_instructions'];
        // back_trace( 'NOTICE', '$additional_instructions: ' . $additional_instructions);
    }

    // DIAG - Diagnostics - Ver 2.2.6
    // back_trace( 'NOTICE', '========================================');
    // back_trace( 'NOTICE', '$max_prompt_tokens: ' . $max_prompt_tokens);
    // back_trace( 'NOTICE', '$max_completion_tokens: ' . $max_completion_tokens);
    // back_trace( 'NOTICE', '$temperature: ' . $temperature);
    // back_trace( 'NOTICE', '$top_p: ' . $top_p);
    // back_trace( 'NOTICE', '$additional_instructions: ' . $additional_instructions);

    $data = array(
        "assistant_id" => $assistant_id,
        "max_prompt_tokens" => $max_prompt_tokens,
        "max_completion_tokens" => $max_completion_tokens,
        "temperature" => $temperature,
        "top_p" => $top_p,
        "truncation_strategy" => array(
            "type" => "auto",
            "last_messages" => null,
        ),
        "additional_instructions" => $additional_instructions,
    );

    $response = wp_remote_post($url, [
        "headers"       => $headers,
        "body"          => json_encode($data),
        "ignore_errors" => true,
        "timeout"       => 30,
    ]);
    
    // Log the full response for debugging
    // back_trace( 'NOTICE', 'Step 4 - Full Response: ' . print_r($response, true));
    
    // Ensure the response is valid
    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        prod_trace('ERROR', "Error fetching response: {$error_message}");
        return "Error: Unable to fetch response. {$error_message}";
    }
    
    // Retrieve the response body
    $response_body = wp_remote_retrieve_body($response);
    
    // Decode the JSON response
    $response_data = json_decode($response_body, true);
    
    // Log the decoded response
    // back_trace( 'NOTICE', 'Step 4 - Decoded Response: ' . print_r($response_data, true));
    
    // Retrieve the HTTP response code
    $http_code = wp_remote_retrieve_response_code($response);
    
    // Handle non-200 responses
    if (isset($response_data['error'])) {
        $errorMessage = $response_data['error']['message'] ?? 'Unknown error';
        // If the error indicates an active run, extract its ID
        if (strpos($errorMessage, 'already has an active run') !== false) {
            if (preg_match('/active run (\S+)\.?/', $errorMessage, $matches)) {
                $existingRunId = $matches[1];
                $existingRunId = rtrim($existingRunId, '.');
                // back_trace( 'NOTICE', "Using existing active run: {$existingRunId}");
                // Return a structure with the run id so the polling logic can use it
                return ['id' => $existingRunId, 'active' => true];
            }
        }
        prod_trace('ERROR', "OpenAI API Error: {$errorMessage}");
        return "Error: {$errorMessage}";
    }
    
    // If no errors, return the decoded response
    return $response_data;    

}

// -------------------------------------------------------------------------
// Step 5: Get the Run's Status
// -------------------------------------------------------------------------
function get_the_azure_run_status($thread_id, $runId, $api_key) {

    // DIAG - Diagnostics - Ver 2.2.6
    // back_trace( 'NOTICE', 'Step 5: get_the_azure_run_status');

    $chatbot_azure_resource_name = esc_attr(get_option('chatbot_azure_resource_name', 'YOUR_RESOURCE_NAME'));
    $chatbot_azure_deployment_name = esc_attr(get_option('chatbot_azure_deployment_name', 'DEPLOYMENT_NAME'));
    $chatbot_azure_api_version = esc_attr(get_option('chatbot_azure_api_version', '2024-08-01-preview'));

    // Assemble the URL
    $url = sprintf(
        'https://%s.openai.azure.com/openai/threads/%s/runs/%s?api-version=%s',
        $chatbot_azure_resource_name,
        $thread_id,
        $runId,
        $chatbot_azure_api_version
    );

    // DIAG - Diagnostics - Ver 2.2.6
    // back_trace( 'NOTICE', '$url: ' . $url);

    // Headers
    $headers = [
        'Content-Type' => 'application/json',
        'api-key'      => trim($api_key),
    ];

    // Make a single GET request to check status
    $response = wp_remote_get($url, ["headers" => $headers, "timeout" => 10]);

    if (is_wp_error($response)) {
        prod_trace('ERROR', 'HTTP Request failed: ' . $response->get_error_message());
        return json_encode(["error" => "API request failed"]);
    }

    $response_body = wp_remote_retrieve_body($response);
    $responseArray = json_decode($response_body, true);

    // Return the response immediately
    return json_encode($responseArray);

}

// -------------------------------------------------------------------------
// Step 6: Get the Run's Steps
// -------------------------------------------------------------------------
function get_the_azure_run_steps($thread_id, $runId, $api_key) {

    // DIAG - Diagnostics - Ver 2.2.3
    // back_trace( 'NOTICE', 'Step 6 - get_the_azure_run_steps()');
    // back_trace( 'NOTICE', 'Step 6 - $thread_id: ' . $thread_id);

    $chatbot_azure_resource_name = esc_attr(get_option('chatbot_azure_resource_name', 'YOUR_RESOURCE_NAME'));
    $chatbot_azure_deployment_name = esc_attr(get_option('chatbot_azure_deployment_name', 'DEPLOYMENT_NAME'));
    $chatbot_azure_api_version = esc_attr(get_option('chatbot_azure_api_version', '2024-08-01-preview'));
    
    // Assemble the URL
    $url = sprintf(
        'https://%s.openai.azure.com/openai/threads/%s/runs/%s/steps?api-version=%s',
        $chatbot_azure_resource_name,
        $thread_id,
        $runId,
        $chatbot_azure_api_version
    );

    // DIAG - Diagnostics - Ver 2.2.6
    // back_trace( 'NOTICE', '$url: ' . $url);

    // Prepare request headers
    $headers = array(
        'Content-Type' => 'application/json',
        'api-key'      => trim($api_key),
    );

    // Make the request
    $response = wp_remote_get($url, [
        "headers"       => $headers,
        "timeout"       => 30,
    ]);

    // Handle request errors
    if (is_wp_error($response)) {
        prod_trace('ERROR', 'HTTP Request failed: ' . $response->get_error_message());
        return "Error: Failed to communicate with the API.";
    }

    // Extract response body safely
    $response_body = wp_remote_retrieve_body($response);

    // Ensure response body is a valid string before decoding
    if (!is_string($response_body) || empty($response_body)) {
        prod_trace('ERROR', 'Error: API returned an empty or invalid response.');
        return "Error: Empty API response.";
    }

    // Decode JSON response safely
    $response_data = json_decode($response_body, true);

    // Handle JSON decoding errors explicitly
    if ($response_data === null && json_last_error() !== JSON_ERROR_NONE) {
        prod_trace('ERROR', 'JSON decode error: ' . json_last_error_msg());
        return "Error: Failed to parse API response.";
    }

    // DIAG - Diagnostic - Ver 2.2.6
    // back_trace( 'NOTICE', 'Step 6 - Decoded Response: ' . print_r($response_data, true));

    return $response_data;

}

// -------------------------------------------------------------------------
// Step 7: Get the Step's Status
// -------------------------------------------------------------------------
function get_the_azure_steps_status($thread_id, $runId, $api_key, $session_id, $user_id, $page_id, $assistant_id) {

    // DIAG - Diagnostics - Ver 2.2.6
    // back_trace( 'NOTICE', 'Step 7 - get_the_azure_steps_status()');
    // back_trace( 'NOTICE', 'Step 7 - $thread_id: ' . $thread_id);

    $chatbot_azure_resource_name = esc_attr(get_option('chatbot_azure_resource_name', 'YOUR_RESOURCE_NAME'));
    $chatbot_azure_deployment_name = esc_attr(get_option('chatbot_azure_deployment_name', 'DEPLOYMENT_NAME'));
    $chatbot_azure_api_version = esc_attr(get_option('chatbot_azure_api_version', '2024-08-01-preview'));

    // Construct API URL
    $url = sprintf(
        'https://%s.openai.azure.com/openai/threads/%s/runs/%s/steps?api-version=%s',
        $chatbot_azure_resource_name,
        $thread_id,
        $runId,
        $chatbot_azure_api_version
    );
    
    // DIAG - Diagnostics - Ver 2.2.6
    // back_trace( 'NOTICE', '$url: ' . $url);

    // Prepare request headers    
    $headers = array(
        'Content-Type' => 'application/json',
        'api-key'      => trim($api_key),
    );

    // Retry settings
    $max_retries = 30; // Max retries before giving up
    $retry_count = 0;
    $sleep_time = 500000; // 0.5 seconds

    while ($retry_count < $max_retries) {

        // Make the request
        $response = wp_remote_get($url, [
            "headers"       => $headers,
            "timeout"       => 30,
        ]);

        // Handle request errors
        if (is_wp_error($response)) {
            prod_trace('ERROR', 'HTTP Request failed: ' . $response->get_error_message());
            return "Error: Failed to communicate with the API.";
        }

        // Extract response body safely
        $response_body = wp_remote_retrieve_body($response);

        // Ensure response body is valid before decoding
        if (!is_string($response_body) || empty($response_body)) {
            prod_trace('ERROR', 'Error: API returned an empty or invalid response.');
            return "Error: Empty API response.";
        }

        // Decode JSON response safely
        $responseArray = json_decode($response_body, true);

        // Handle JSON decoding errors explicitly
        if ($responseArray === null && json_last_error() !== JSON_ERROR_NONE) {
            prod_trace('ERROR', 'JSON decode error: ' . json_last_error_msg());
            return "Error: Failed to parse API response.";
        }

        // DIAG - Diagnostics - Ver 2.2.6
        // back_trace( 'NOTICE', 'Step 7 - Decoded Response: ' . print_r($responseArray, true));

        // Updated check for "data" field
        if (isset($responseArray["data"][0]) && isset($responseArray["data"][0]["status"])) {
            if ($responseArray["data"][0]["status"] === "completed") {
                // DIAG - Diagnostics - Ver 2.2.7
                // back_trace( 'NOTICE', 'Step 7 - $responseArray: ' . print_r($responseArray, true));
                if (isset($responseArray["data"][0]["usage"])) {
                    $prompt_tokens = $responseArray["data"][0]["usage"]["prompt_tokens"] ?? 0;
                    $completion_tokens = $responseArray["data"][0]["usage"]["completion_tokens"] ?? 0;
                    $total_tokens = $responseArray["data"][0]["usage"]["total_tokens"] ?? 0;
                    // DIAG - Diagnostics - Ver 2.2.6
                    // back_trace( 'NOTICE' , 'Prompt Tokens: ' . $prompt_tokens );
                    // back_trace( 'NOTICE' , 'Completion Tokens: ' . $completion_tokens );
                    // back_trace( 'NOTICE' , 'Total Tokens: ' . $total_tokens );
                    if ( $total_tokens != 0 ) {
                        // back_trace( 'NOTICE', 'Step 7 - Logging token usage.');
                        // back_trace( 'NOTICE', 'Step 7 - $prompt_tokens: ' . $prompt_tokens );
                        // back_trace( 'NOTICE', 'Step 7 - $completion_tokens: ' . $completion_tokens );
                        // back_trace( 'NOTICE', 'Step 7 - $total_tokens: ' . $total_tokens );
                        append_message_to_conversation_log($session_id, $user_id, $page_id, 'Prompt Tokens', $thread_id, $assistant_id, null, $prompt_tokens);
                        append_message_to_conversation_log($session_id, $user_id, $page_id, 'Completion Tokens', $thread_id, $assistant_id, null, $completion_tokens);
                        append_message_to_conversation_log($session_id, $user_id, $page_id, 'Total Tokens', $thread_id, $assistant_id, null, $total_tokens);
                    }
                }                
                return "completed";
            } else if ($status === "in_progress" || $status === "pending") {
                // Status is still in progress: sleep and retry
                usleep($sleep_time);
                $retry_count++;
                continue;
            } else {
                prod_trace('ERROR', 'Error - GPT Assistant - Step 7: Unexpected status - ' . $status);
                return "Error: Unexpected step status: " . $status;
            }
        } else {
            // prod_trace('ERROR', 'Error - GPT Assistant - Step 7: Invalid API response - missing "data" or "status".');
            return "Error: Missing 'data' or step 'status' in API response.";
        }

        // Sleep before retrying
        // REMOVED - Ver 2.2.6 - 2025-03-10 - DEAD CODE CANNOT BE EXECUTED AFTER RETURN (ABOVE)
        // usleep($sleep_time);
        // $retry_count++;
        
    }

    // Log and return failure if retries exceeded
    prod_trace('ERROR', 'Error - GPT Assistant - Step 7: Maximum retries reached.');
    return "Error: Maximum retries reached.";

}

// -------------------------------------------------------------------------
// Step 8: Get the Message
// -------------------------------------------------------------------------
function get_the_azure_message($thread_id, $api_key, $run_id = null) {

    // DIAG - Diagnostics - Ver 2.2.6
    // back_trace( 'NOTICE', 'Step 8 - get_the_azure_message()');
    // back_trace( 'NOTICE', 'Step 8 - $thread_id: ' . $thread_id);

    $chatbot_azure_resource_name = esc_attr(get_option('chatbot_azure_resource_name', 'YOUR_RESOURCE_NAME'));
    $chatbot_azure_deployment_name = esc_attr(get_option('chatbot_azure_deployment_name', 'DEPLOYMENT_NAME'));
    $chatbot_azure_api_version = esc_attr(get_option('chatbot_azure_api_version', '2024-08-01-preview'));

    // Assemble the URL
    $url = sprintf(
        'https://%s.openai.azure.com/openai/threads/%s/messages?api-version=%s',
        $chatbot_azure_resource_name,
        $thread_id,
        $chatbot_azure_api_version
    );

    // DIAG - Diagnostics - Ver 2.2.6
    // back_trace( 'NOTICE', '$url: ' . $url);

    // Prepare request headers 
    $headers = array(
        'Content-Type' => 'application/json',
        'api-key'      => trim($api_key),
    );

    // Fetch the response
    $response = wp_remote_get($url, [
        "headers" => $headers,
        "timeout" => 30,
    ]);

    // Handle request errors
    if (is_wp_error($response)) {
        prod_trace('ERROR', 'HTTP Request failed: ' . $response->get_error_message());
        return "Error: Failed to communicate with the API.";
    }

    // Extract response body
    $response_body = wp_remote_retrieve_body($response);

    // Ensure the response is a valid JSON string before decoding
    $response_data = json_decode($response_body, true);
    if ($response_data === null) {
        prod_trace('ERROR', 'JSON Decode Error: ' . json_last_error_msg());
        return "Error: Invalid JSON response from API.";
    }

    // DIAG - Diagnostics - Ver 2.0.3
    // back_trace( 'NOTICE', 'Step 8 - $response_data: ' . print_r($response_data, true));

    // Download any file attachments - Ver 2.0.3
    if (isset($response_data['data']) && is_array($response_data['data'])) {
        foreach ($response_data['data'] as &$message) {
            // Check attachments
            if (isset($message['attachments']) && is_array($message['attachments'])) {
                foreach ($message['attachments'] as $attachment) {
                    if (isset($attachment['file_id'])) {
                        $file_id = $attachment['file_id'];

                        // If $annotation is not defined or not an array, skip this iteration
                        if (!isset($annotation) || !is_array($annotation)) {
                            continue;
                        }

                        // Access array offset here
                        if (isset($annotation['offset_key'])) {
                            $value = $annotation['offset_key'];
                        } else {
                            // Handle the error appropriately
                            // back_trace( 'NOTICE', '$annotation: offset_key does not exist');
                            continue;
                        }

                        // If $path is not defined or not a string, skip this iteration
                        if (!isset($path) || !is_string($path)) {
                            continue;
                        }

                        $basename = basename($path);

                        $file_name = 'download_' . generate_random_string() . '_' . basename($annotation['text']); // Extract the filename

                        // DIAG - Diagnostics - Ver 2.0.3
                        // back_trace( 'NOTICE', '$file_id: ' . $file_id);

                        // Call the function to download the file
                        $file_url = download_openai_file($file_id, $file_name);

                        // DIAG - Diagnostics - Ver 2.0.3
                        // back_trace( 'NOTICE', '$file_url: ' . $file_url);

                        if ($file_url) {
                            // Append the local URL to the message (modify as needed for your use case)
                            $message['file_url'] = $file_url;
                        }

                        // Set a transient that expires in 2 hours
                        $timeFrameForDelete = time() + 2 * 60 * 60;
                        set_transient('chatbot_chatgpt_delete_azure_uploaded_file_' . $file_id, $file_id, $timeFrameForDelete);

                        // Set a cron job to delete the file in 1 hour 45 minutes
                        $shorterTimeFrameForDelete = time() + 1 * 60 * 60 + 45 * 60;
                        if (!wp_next_scheduled('delete_azure_uploaded_file', array($file_id))) {
                            wp_schedule_single_event($shorterTimeFrameForDelete, 'delete_azure_uploaded_file', array($file_id));
                        }

                    }
                }
            }

            // Check content annotations
            if (isset($message['content']) && is_array($message['content'])) {
                foreach ($message['content'] as &$content) { // Note the change here to modify the content
                    if (isset($content['text']['annotations']) && is_array($content['text']['annotations'])) {
                        foreach ($content['text']['annotations'] as $annotation) {
                            if (isset($annotation['file_path']['file_id']) && isset($annotation['text'])) {
                                $file_id = $annotation['file_path']['file_id'];
                                $file_name = 'download_' . generate_random_string() . '_' . basename($annotation['text']); // Extract the filename

                                // DIAG - Diagnostics - Ver 2.0.3
                                // back_trace( 'NOTICE', '$file_id: ' . $file_id . ', $file_name: ' . $file_name);

                                // Call the function to download the file
                                $file_url = download_openai_file($file_id, $file_name);

                                // DIAG - Diagnostics - Ver 2.0.3
                                // back_trace( 'NOTICE', '$file_url: ' . $file_url);

                                if ($file_url) {
                                    // Replace the placeholder link with the actual URL
                                    $content['text']['value'] = str_replace($annotation['text'], $file_url, $content['text']['value']);
                                }
                                
                                // Set a transient that expires in 2 hours
                                $timeFrameForDelete = time() + 2 * 60 * 60;
                                set_transient('chatbot_chatgpt_delete_azure_uploaded_file_' . $file_id, $file_id, $timeFrameForDelete);

                                // Set a cron job to delete the file in 1 hour 45 minutes
                                $shorterTimeFrameForDelete = time() + 1 * 60 * 60 + 45 * 60;
                                if (!wp_next_scheduled('delete_azure_uploaded_file', array($file_id))) {
                                    wp_schedule_single_event($shorterTimeFrameForDelete, 'delete_azure_uploaded_file', array($file_id));
                                }
                        
                            }
                        }
                    }
                }
            }
        }
    } else {

        // DIAG - Diagnostics - Ver 2.0.3
        // back_trace( 'NOTICE', 'No data or attachments found in the response.');

    }

    return $response_data;

}

// CustomGPT - Assistants - Ver 1.7.2
function chatbot_azure_custom_gpt_call_api($api_key, $message, $assistant_id, $thread_id, $session_id, $user_id, $page_id, $client_message_id = null) {

    // DIAG - Diagnostics - Ver 1.8.6
    // back_trace( 'NOTICE', 'chatbot_azure_custom_gpt_call_api()' );
    // back_trace( 'NOTICE', '$user_id: ' . $user_id);
    // back_trace( 'NOTICE', '$page_id: ' . $page_id);
    // back_trace( 'NOTICE', '$session_id: ' . $session_id);
    // back_trace( 'NOTICE', '$assistant_id: ' . $assistant_id);
    // back_trace( 'NOTICE', '$thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', '$message: ' . $message);
    // back_trace( 'NOTICE', '$additional_instructions: ' . $additional_instructions);
    // back_trace( 'NOTICE', '$model: ' . $model);

    // Globals added for Ver 1.7.2
    global $learningMessages;
    global $errorResponses;
    global $stopWords;

    // Use client_message_id if provided, otherwise generate a unique message UUID for idempotency
    $message_uuid = $client_message_id ? $client_message_id : wp_generate_uuid4();

    // Lock the conversation BEFORE thread resolution to prevent empty-thread vs real-thread lock split
    $conv_lock = 'chatgpt_conv_lock_' . md5($assistant_id . '|' . $user_id . '|' . $page_id . '|' . $session_id);
    $lock_timeout = 60; // 60 seconds timeout

    // Check for duplicate message UUID in conversation log
    $duplicate_key = 'chatgpt_message_uuid_' . $message_uuid;
    if (get_transient($duplicate_key)) {
        // DIAG - Diagnostics - Ver 2.3.4
        // back_trace('NOTICE', 'Duplicate message UUID detected: ' . $message_uuid);
        return "Error: Duplicate request detected. Please try again.";
    }

    // Lock check removed - main send function handles locking
    set_transient($duplicate_key, true, 300); // 5 minutes to prevent duplicates

    // See if there is a $thread_id
    if (empty($thread_id)) {
        // back_trace( 'NOTICE', '$thread_id is empty');
        $thread_id = get_chatbot_chatgpt_threads($user_id, $session_id, $page_id, $assistant_id);
        if (empty($thread_id)) {
            // back_trace( 'NOTICE', '$thread_id was empty');
        } else {
            // back_trace( 'NOTICE', '$thread_id was empty but found a $thread_id: ' . $thread_id);
        }
    } else {
        // back_trace( 'NOTICE', '$thread_id was NOT empty but passed as $thread_id: ' . $thread_id);
    }

    // If the thread_id is not set, create a new thread
    if (empty($thread_id)) {

        // Step 1 - Create an Assistant
        // back_trace( 'NOTICE', 'Step 1: Create an Assistant');
        $api_key = esc_attr(get_option('chatbot_azure_api_key', ''));
        // Decrypt the API key - Ver 2.2.6
        $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
        $assistants_response = create_an_azure_assistant($api_key);
        // DIAG - Diagnostics - Ver 2.2.3
        // back_trace( 'NOTICE', '$assistants_response: ' . print_r($assistants_response, true));

        // Step 2 - Get The Thread ID
        // back_trace( 'NOTICE', 'Step 2: Get The Thread ID');
        $thread_id = $assistants_response["id"];
        $kchat_settings['thread_id'] = $thread_id; // ADDED FOR VER 2.1.1.1 - 2024-08-26
        // DIAG - Diagnostics - Ver 2.2.3
        // back_trace( 'NOTICE', '$thread_id ' . $thread_id);
        // back_trace( 'NOTICE', '$assistant_id ' . $assistant_id);
        // back_trace( 'NOTICE', '$user_id ' . $user_id);
        // back_trace( 'NOTICE', '$page_id ' . $page_id);
        set_chatbot_chatgpt_threads($thread_id, $assistant_id, $user_id, $page_id);
        
    } else {

        $thread_id = get_chatbot_chatgpt_threads($user_id, $session_id, $page_id, $assistant_id);

    }

    // Now that we have the thread_id, also set a per-thread lock
    $thread_lock = 'chatgpt_run_lock_' . $thread_id;
    if (get_transient($thread_lock)) {
        // Lock clearing removed - main send function handles locking
        prod_trace('NOTICE', 'Thread ' . $thread_id . ' is locked, skipping concurrent call');
        global $chatbot_chatgpt_fixed_literal_messages;
        $default_message = "I'm still working on your previous message—please send again in a moment.";
        $locked_message = isset($chatbot_chatgpt_fixed_literal_messages[19]) 
            ? $chatbot_chatgpt_fixed_literal_messages[19] 
            : $default_message;
        return $locked_message;
    }
    set_transient($thread_lock, $message_uuid, $lock_timeout);

    // Conversation Context - Ver 2.2.3
    $context = "";
    $context = esc_attr(get_option('chatbot_azure_conversation_context', 'You are a versatile, friendly, and helpful assistant designed to support me in a variety of tasks that responds in Markdown.'));
 
    // Step 3: Add a Message to a Thread
    // back_trace( 'NOTICE', 'Step 3 - Add a Message to a Thread');
    $prompt = $message;
        
    // Fetch the file id - Ver 2.23
    $file_id = chatbot_chatgpt_retrieve_file_id($user_id, $page_id);

    // DIAG - Diagnostics - Ver 2.2.3
    // back_trace( 'NOTICE', '$user_id: ' . $user_id);
    // back_trace( 'NOTICE', '$page_id: ' . $page_id);
    // back_trace( 'NOTICE', '$file_id: ' . print_r($file_id, true));

    // DIAG - Diagnostics - Ver 2.3.3
    for ($i = 0; $i < count($file_id); $i++) {
        if (isset($file_id[$i])) {
            // back_trace( 'NOTICE', '$file_id[' . $i . ']: ' . $file_id[$i]);
        } else {
            // Handle the error appropriately
            // back_trace( 'NOTICE', '$file_id[' . $i . ']: index does not exist');
            unset($file_id[$i]); // Remove the non-existent key
        }
    }

    // DIAG - Diagnostics - Ver 2.2.3
    // back_trace( 'NOTICE', 'RIGHT BEFORE CALL to add_an_azure_message - $thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', 'RIGHT BEFORE CALL to add_an_azure_message - $prompt: ' . $prompt);
    // back_trace( 'NOTICE', 'RIGHT BEFORE CALL to add_an_azure_message - $content: ' . $context);
    // back_trace( 'NOTICE', 'chatbot_chatgpt_retrieve_file_id(): ' . print_r($file_id, true));

    // ENHANCED CONTEXT - Select some context to send with the message - Ver 2.2.4 - Updated Ver 2.2.9
    if ($use_enhanced_content_search == 'Yes') {

        $search_results = chatbot_chatgpt_content_search($message);
        if (!empty($search_results) && isset($search_results['results'])) {
            // Format the search results into a readable string
            $formatted_results = '';
            foreach ($search_results['results'] as $result) {
                $formatted_results .= "\nTitle: " . $result['title'] . "\n";
                if (isset($result['excerpt'])) {
                    $formatted_results .= "Content: " . $result['excerpt'] . "\n";
                }
                $formatted_results .= "URL: " . $result['url'] . "\n";
            }
            // Append the formatted search results to the prompt
            $prompt = $prompt . ' When answering the prompt, please consider the following information: ' . $formatted_results;
        }
        // DIAG Diagnostics - Ver 2.2.4 - 2025-02-04
        // back_trace( 'NOTICE', '$prompt: ' . $prompt);

    }
    if (empty($file_id)) {
        // back_trace( 'NOTICE', 'No file to retrieve');
        $assistants_response = add_an_azure_message($thread_id, $prompt, $context, $api_key, '', $message_uuid);
    } else {
        //DIAG - Diagnostics - Ver 1.7.9
        // back_trace( 'NOTICE', 'File to retrieve');
        // back_trace( 'NOTICE', '$file_id ' . print_r($file_id, true));
        $assistants_response = add_an_azure_message($thread_id, $prompt, $context, $api_key, $file_id, $message_uuid);
        // DIAG - Print the response
        // back_trace( 'NOTICE', $assistants_response);
    }

    $retries = 0;
    $maxRetries = 5;
    global $sleepTime; 

    do {

        $run_status = '';

        // Step 4: Run the Assistant
        // back_trace( 'NOTICE', 'Step 4 - Run the Assistant');
        $assistants_response = run_an_azure_assistant($thread_id, $assistant_id, $context, $api_key, $message_uuid);

        // Check if the response is not an array or is a string indicating an error
        if (!is_array($assistants_response) || is_string($assistants_response)) {
            // back_trace( 'ERROR', 'Invalid response format or error occurred');
            return "Error: Invalid response format or error occurred.";
        }

        // Check if the 'id' key exists in the response
        if (isset($assistants_response["id"])) {
            $runId = $assistants_response["id"];
        } else {
            // back_trace( 'ERROR', 'runId key not found in response');
            return "Error: 'id' key not found in response.";
        }

        // DIAG - Print the response
        // back_trace( 'NOTICE', $assistants_response);

        // Step 5: Get the Run's Status
        // back_trace( 'NOTICE', 'Step 5 - Get the Run\'s Status');
        $run_status = get_the_azure_run_status($thread_id, $runId, $api_key);

        $retries++;

        if ($run_status == "failed" || $run_status == "incomplete") {
            // back_trace( 'ERROR', 'Error - INSIDE DO WHILE LOOP - GPT Assistant - Step 5: ' . $run_status);
            // return "Error: Step 5 - " . $run_status;
            // back_trace( 'NOTICE', 'ALERT INSIDE DO LOOP - Sleeping for ' . $sleepTime . ' microseconds');
            // back_trace( 'NOTICE', 'ALERT INSIDE DO LOOP - Retries: ' . $retries);
            usleep($sleepTime);
        }

    } while ($run_status != "completed" && $retries < $maxRetries);

    // Failed after multiple retries
    if ($run_status == "failed" || $run_status == "incomplete") {
        // back_trace( 'ERROR', 'Error - FAILED AFTER MULTIPLE RETRIES - GPT Assistant - Step 5: ' . $run_status);
        // Clear locks on error
        delete_transient($thread_lock);
        // Lock clearing removed - main send function handles locking
        return "Error: Step 5 - " . $run_status;
    }

    // Step 6: Get the Run's Steps
    // back_trace( 'NOTICE', 'Step 6 - Get the Run\'s Steps');
    $assistants_response = get_the_azure_run_steps($thread_id, $runId, $api_key);
    // DIAG - Diagnostics - Ver 2.2.6
    // back_trace( 'NOTICE', '$assistants_response: ' . print_r($assistants_response, true));

    // DIAG - Diagnostics - Ver 2.2.6
    // back_trace( 'NOTICE', 'Usage - Prompt Tokens: ' . $assistants_response["data"][0]["usage"]["prompt_tokens"]);
    // back_trace( 'NOTICE', 'Usage - Completion Tokens: ' . $assistants_response["data"][0]["usage"]["completion_tokens"]);
    // back_trace( 'NOTICE', 'Usage - Total Tokens: ' . $assistants_response["data"][0]["usage"]["total_tokens"]);

    // Add the usage to the conversation tracker
    // append_message_to_conversation_log($session_id, $user_id, $page_id, 'Prompt Tokens', $thread_id, $assistant_id, null, $assistants_response["data"][0]["usage"]["prompt_tokens"]);
    // append_message_to_conversation_log($session_id, $user_id, $page_id, 'Completion Tokens', $thread_id, $assistant_id, null, $assistants_response["data"][0]["usage"]["completion_tokens"]);
    // append_message_to_conversation_log($session_id, $user_id, $page_id, 'Total Tokens', $thread_id, $assistant_id, null, $assistants_response["data"][0]["usage"]["total_tokens"]);

    // Step 7: Get the Step's Status
    // back_trace( 'NOTICE', 'Step 7 - Get the Step\'s Status');
    // get_the_azure_steps_status($thread_id, $runId, $api_key, $session_id, $user_id, $page_id, $assistant_id);

    $max_retries = 10; // Max retries before giving up
    $retry_count = 0;
    $sleep_time = 500000; // 0.5 seconds

    while (($retry_count < $max_retries) && (($step_status = get_the_azure_steps_status($thread_id, $runId, $api_key, $session_id, $user_id, $page_id, $assistant_id)) !== "completed")) {
        $retry_count++;
        usleep($sleep_time); 
        // back_trace( 'NOTICE', 'Step 7: retry ' . $retry_count . ' - get_the_azure_steps_status() returned: ' . step_status);
    }

    if ($retry_count!=0) {
        if ($retry_count==$max_retries) {
            prod_trace('NOTICE', 'Failure after retry ' . $retry_count . ' - get_the_azure_steps_status()'. $step_status);
        } else  {
            prod_trace('NOTICE', 'Done after retry ' . $retry_count . ' - get_the_azure_steps_status()');
        }
    }

    // Step 8: Get the Message
    // back_trace( 'NOTICE', 'Step 8: Get the Message');
    $assistants_response = get_the_azure_message($thread_id, $api_key, $runId);

    // DIAG - Diagnostics - Ver 2.2.6
    // back_trace( 'NOTICE', '$assistants_response: ' . print_r($assistants_response, true));

    // DIAG - Diagnostics - Ver 2.2.3
    // back_trace( 'NOTICE', 'Usage - Prompt Tokens: ' . $assistants_response["data"][0]["usage"]["prompt_tokens"]);
    // back_trace( 'NOTICE', 'Usage - Completion Tokens: ' . $assistants_response["data"][0]["usage"]["completion_tokens"]);
    // back_trace( 'NOTICE', 'Usage - Total Tokens: ' . $assistants_response["data"][0]["usage"]["total_tokens"]);

    // Add the usage to the conversation tracker
    // append_message_to_conversation_log($session_id, $user_id, $page_id, 'Prompt Tokens', $thread_id, $assistant_id, null, $assistants_response["data"][0]["usage"]["prompt_tokens"]);
    // append_message_to_conversation_log($session_id, $user_id, $page_id, 'Completion Tokens', $thread_id, $assistant_id, null, $assistants_response["data"][0]["usage"]["completion_tokens"]);
    // append_message_to_conversation_log($session_id, $user_id, $page_id, 'Total Tokens', $thread_id, $assistant_id, null, $assistants_response["data"][0]["usage"]["total_tokens"]);

    // Interaction Tracking - Ver 1.6.3
    update_interaction_tracking();

    // Clear locks on success
    delete_transient($thread_lock);
    delete_transient($conv_lock);

    // Remove citations from the response
    if (isset($assistants_response["data"][0]["content"][0]["text"]["value"])) {
        $assistants_response["data"][0]["content"][0]["text"]["value"] = preg_replace('/\【.*?\】/', '', $assistants_response["data"][0]["content"][0]["text"]["value"]);
    }

    // Check for missing $thread_id in $kchat_settings
    if (!isset($kchat_settings['thread_id'])) {
        $kchat_settings['thread_id'] = $thread_id;
    }

    // DIAG - Diagnostics - Ver 2.2.1
    // back_trace( 'NOTICE', '$assistants_response: ' . print_r($assistants_response, true));

    // Verify that "data" exists and is an array.
    if (!isset($assistants_response["data"]) || !is_array($assistants_response["data"])) {
        prod_trace('ERROR', 'Error: "data" key is missing or not an array.');
        // Clear locks on error
        delete_transient($thread_lock);
        // Lock clearing removed - main send function handles locking
        return '';
    }

    // Initialize variables to track the latest assistant message.
    $max_created_at = 0;
    $latest_index = -1;

    // Loop through all messages to find the assistant message with the highest created_at.
    foreach ($assistants_response["data"] as $index => $msg) {
        if (isset($msg["role"]) && $msg["role"] === "assistant") {
            if (isset($msg["created_at"]) && $msg["created_at"] > $max_created_at) {
                $max_created_at = $msg["created_at"];
                $latest_index = $index;
            }
        }
    }

    if ($latest_index === -1) {
        prod_trace('ERROR', 'Error: No assistant messages found.');
        // Clear locks on error
        delete_transient($thread_lock);
        // Lock clearing removed - main send function handles locking
        return '';
    }

    prod_trace('ERROR', 'DEBUG: Latest assistant message found at index ' . $latest_index . ' with created_at: ' . $max_created_at);

    // Extract the text from the selected message. 
    if (isset($assistants_response["data"][$latest_index]["content"][1]["text"]["value"])) {
        $latest_response = $assistants_response["data"][$latest_index]["content"][1]["text"]["value"];
        prod_trace('ERROR', 'DEBUG: Extracted response from content[1]: ' . $latest_response);
        return $latest_response;
    } elseif (isset($assistants_response["data"][$latest_index]["content"][0]["text"]["value"])) {
        $latest_response = $assistants_response["data"][$latest_index]["content"][0]["text"]["value"];
        prod_trace('ERROR', 'DEBUG: Extracted response from content[0]: ' . $latest_response);
        return $latest_response;
    } else {
        prod_trace('ERROR', 'Error: No text value found in the latest assistant message.');
        // Clear locks on error
        delete_transient($thread_lock);
        // Lock clearing removed - main send function handles locking
        return '';
    }

}

// -------------------------------------------------------------------------
// Retrieve the first file id - Ver 2.2.3
// -------------------------------------------------------------------------
function chatbot_azure_retrieve_file_id( $user_id, $page_id ) {

    // DIAG - Diagnostics - Ver 2.2.3
    // back_trace( 'NOTICE', 'chatbot_chatgpt_retrieve_file_id(): ' . $user_id . ', ' . $page_id);

    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;

    $counter = 0;
    $file_ids = [];
    $file_types = [];

    $file_id = get_chatbot_chatgpt_transients_files('chatbot_chatgpt_assistant_file_ids', $session_id, $counter);
    $file_types = get_chatbot_chatgpt_transients_files('chatbot_chatgpt_assistant_file_types', $session_id, $file_id);

    // DIAG - Diagnostics - Ver 2.0.3
    // back_trace( 'NOTICE', '$file_id: ' . print_r($file_id, true));
    // back_trace( 'NOTICE', '$file_types: ' . print_r($file_types, true));

    while (!empty($file_id)) {
        // Delete the transient
        // delete_chatbot_chatgpt_transients_files( 'chatbot_chatgpt_assistant_file_id', $session_id, $counter);

        // Delete the file
        // delete_azure_uploaded_file($file_id);

        // Set a transient that expires in 2 hours
        $timeFrameForDelete = time() + 2 * 60 * 60;
        set_transient('chatbot_chatgpt_delete_azure_uploaded_file_' . $file_id, $file_id, $timeFrameForDelete);

        // Set a cron job to delete the file in 1 hour 45 minutes
        $shorterTimeFrameForDelete = time() + 1 * 60 * 60 + 45 * 60;
        if (!wp_next_scheduled('delete_azure_uploaded_file', array($file_id))) {
            wp_schedule_single_event($shorterTimeFrameForDelete, 'delete_azure_uploaded_file', array($file_id));
        }

        // Add the file id to the list
        $file_ids[] = $file_id;
        $file_ids[$file_id] = $file_types;

        // Increment the counter
        $counter++;

        // Retrieve the next file id
        $file_id = get_chatbot_chatgpt_transients_files('chatbot_chatgpt_assistant_file_ids', $session_id, $counter);
        $file_types = get_chatbot_chatgpt_transients_files('chatbot_chatgpt_assistant_file_types', $session_id, $file_id);

    }

    // Join the file ids into a comma-separated string and return it
    // DIAG - Diagnostics - Ver 2.2.3
    // back_trace( 'NOTICE', 'chatbot_chatgpt_retrieve_file_ids(): ' . implode(',', $file_ids));

    // return implode(',', $file_ids);

    // Join the file ids into a comma-separated string and return it
    // DIAG - Diagnostics - Ver 2.2.3
    // back_trace( 'NOTICE', 'chatbot_chatgpt_retrieve_file_ids(): ' . print_r($file_ids, true));

    return $file_ids;

}

// -------------------------------------------------------------------------
// Cleanup in Aisle 4 on OpenAI - Ver 2.2.6
// -------------------------------------------------------------------------
function delete_azure_uploaded_file($file_id) {

    // DIAG - Diagnostics - Ver 2.2.6
    // back_trace( 'NOTICE', 'delete_azure_uploaded_file(): ' . $file_id);

    // Get the Azure API key
    $api_key = esc_attr(get_option('chatbot_azure_api_key'));
    // Decrypt the API key - Ver 2.2.6
    $api_key = chatbot_chatgpt_decrypt_api_key($api_key);

    $chatbot_azure_resource_name = esc_attr(get_option('chatbot_azure_resource_name', 'YOUR_RESOURCE_NAME'));
    $chatbot_azure_api_version = esc_attr(get_option('chatbot_azure_api_version', '2024-08-01-preview'));

    // Assemble the URL for deletion
    $url = sprintf(
        'https://%s.openai.azure.com/openai/files/%s?api-version=%s',
        $chatbot_azure_resource_name,
        $file_id,
        $chatbot_azure_api_version
    );
    
    // DIAG - Diagnostics - Ver 2.2.6
    // back_trace( 'NOTICE', '$url: ' . $url);

    $headers = array(
        'Content-Type' => 'application/json',
        'api-key'      => trim($api_key),
    );
    
    // Send DELETE request using WP functions
    $response = wp_remote_request($url, array(
        'method'  => 'DELETE',
        'timeout' => 15,
        'headers' => $headers,
    ));
    
    // Handle errors
    if (is_wp_error($response)) {
        prod_trace('ERROR', 'Error deleting file from Azure OpenAI: ' . $response->get_error_message());
        return false;
    }
    
    // Get the HTTP status code
    $http_status_code = wp_remote_retrieve_response_code($response);
    
    if ($http_status_code == 200 || $http_status_code == 204) {
        // back_trace( 'NOTICE', 'File deleted successfully.');
        return true;
    } else {
        prod_trace('ERROR', 'HTTP status code: ' . $http_status_code );
        prod_trace('ERROR', 'Response: ' . print_r($response, true));        
        return false;
    }
}
add_action('delete_azure_uploaded_file', 'delete_azure_uploaded_file');