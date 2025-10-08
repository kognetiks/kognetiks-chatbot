<?php
/**
 * Kognetiks Chatbot - Assistants - Ver 1.6.9
 *
 * This file contains the code for access the OpenAI Assistants API.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Helper function to get appropriate timeout for assistant operations
function chatbot_chatgpt_get_assistant_timeout() {
    $base_timeout = intval(esc_attr(get_option('chatbot_chatgpt_timeout_setting', '30')));
    
    // Assistant operations often take longer, so increase the base timeout
    // but cap it at a reasonable maximum to prevent extremely long waits
    $assistant_timeout = min($base_timeout + 60, 180); // Add 60s but max 180s (3 minutes)
    
    return $assistant_timeout;
}

// -------------------------------------------------------------------------
// Step 1: Create a thread
// -------------------------------------------------------------------------
function createAnAssistant($api_key) {

    // DIAG - Diagnostics - Ver 2.2.3
    // back_trace( 'NOTICE', 'Step 1 - createAnAssistant()');

    // Set your API key and assistant ID here:
    $api_key = esc_attr(get_option('chatbot_chatgpt_api_key', ''));
    // Decrypt the API key - Ver 2.2.6
    $api_key = chatbot_chatgpt_decrypt_api_key($api_key);

    // Base URL for the beta threads endpoints
    // $url = "https://api.openai.com/v1/threads";
    $url = get_threads_api_url();

    // DIAG - Diagnostics - Ver 2.2.3
    // back_trace( 'NOTICE', '$url: ' . $url);

    $assistant_beta_version = esc_attr(get_option('chatbot_chatgpt_assistant_beta_version', 'v2'));
    if ( $assistant_beta_version == 'v2' ) {
        $beta_version = "assistants=v2";
    } else {
        $beta_version = "assistants=v1";
    }

    // Prepare common headers
    $headers = [
        "Content-Type"  => "application/json",
        "OpenAI-Beta"   => $beta_version,
        "Authorization" => "Bearer " . $api_key,
    ];

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

    // DIAG - Diagnostics - Ver 2.2.3
    // back_trace( 'NOTICE', 'Step 1 - $thread_response["id"]: ' . $thread_response["id"]);

    return $thread_response;

}

// -------------------------------------------------------------------------
// Step 2: EMPTY STEP
// -------------------------------------------------------------------------

// -------------------------------------------------------------------------
// Step 3: Add a message
// -------------------------------------------------------------------------
function addAMessage($thread_id, $prompt, $context, $api_key, $file_id = null, $message_uuid = null) {

    // DIAG - Diagnostics - Ver 2.2.3
    // back_trace( 'NOTICE', 'Step 3 - addAMessage()');
    // back_trace( 'NOTICE', '$thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', '$prompt: ' . $prompt);
    // back_trace( 'NOTICE', '$context: ' . $context);
    // back_trace( 'NOTICE', '$file_id: ' . print_r($file_id, true));

    global $session_id;

    // Set your API key and assistant ID here:
    $api_key = esc_attr(get_option('chatbot_chatgpt_api_key', ''));
    // Decrypt the API key - Ver 2.2.6
    $api_key = chatbot_chatgpt_decrypt_api_key($api_key);

    // Base URL for the beta threads endpoints
    // $url = "https://api.openai.com/v1/threads";
    $url = get_threads_api_url() . '/' . $thread_id . '/messages';

    // DIAG - Diagnostics - Ver 2.2.3
    // back_trace( 'NOTICE', '$url: ' . $url);

    $assistant_beta_version = esc_attr(get_option('chatbot_chatgpt_assistant_beta_version', 'v2'));
    if ( $assistant_beta_version == 'v2' ) {
        $beta_version = "assistants=v2";
    } else {
        $beta_version = "assistants=v1";
    }

    // Prepare common headers
    $headers = [
        "Content-Type"  => "application/json",
        "OpenAI-Beta"   => $beta_version,
        "Authorization" => "Bearer " . $api_key,
    ];

    // *********************************************************************************
    // FILE ID IS NULL
    // *********************************************************************************
    if ( empty($file_id) ) {

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
        
        // Add message UUID to metadata if provided
        if ($message_uuid) {
            $data['metadata'] = ['message_uuid' => $message_uuid];
        }

    }

    if ( !empty($file_id) ) {

        // *********************************************************************************
        // Decide which helper to use
        // *********************************************************************************

        // FIXME - Retrieve the first item file type - assumes they are all the same, not mixed
        $file_type = get_chatbot_chatgpt_transients_files('chatbot_chatgpt_assistant_file_types', $session_id, 0);
        $file_type = $file_type ? $file_type : 'unknown';
        
        // DIAG - Diagnostics - Version 2.3.5.2 - Log file type retrieval
        // back_trace( 'NOTICE', 'FILE TYPE DEBUG: Session ID: ' . $session_id);
        // back_trace( 'NOTICE', 'FILE TYPE DEBUG: Retrieved file_type: ' . $file_type);
    
        // DIAG - Diagnostics - Ver 2.0.3
        // back_trace( 'NOTICE', '========================================');
        // back_trace( 'NOTICE', '$file_type: ' . $file_type);

        // *********************************************************************************
        // NON-IMAGE ATTACHMENTS - Ver 2.0.3
        // *********************************************************************************

        if ( $file_type == 'assistants' ) {
            $data = chatbot_chatgpt_text_attachment($prompt, $file_id, $beta_version);
        }

        // *********************************************************************************
        // IMAGE ATTACHMENTS - Ver 2.0.3
        // *********************************************************************************

        if ( $file_type == 'vision' ) {
            $data = chatbot_chatgpt_image_attachment($prompt, $file_id, $beta_version);
        }
        
        // Add message UUID to metadata if provided
        if ($message_uuid && isset($data)) {
            $data['metadata'] = ['message_uuid' => $message_uuid];
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
    // back_trace( 'NOTICE', 'addAMessage() - $response_body: ' . print_r($response_body, true));
    
    // Return the API response
    return json_decode($response_body, true);

}

// -------------------------------------------------------------------------
// Step 4: Run the Assistant
// -------------------------------------------------------------------------
function runTheAssistant($thread_id, $assistant_id, $context, $api_key, $message_uuid = null) {

    // DIAG - Diagnostics - Ver 2.2.3
    // back_trace( 'NOTICE', 'Step 4 - runTheAssistant()');
    // back_trace( 'NOTICE', 'Step 4 - $thread_id: ' . $thread_id);

    global $kchat_settings;
    
    // $url = "https://api.openai.com/v1/threads/" . $thread_id . "/runs";
    $url = get_threads_api_url() . '/' . $thread_id . '/runs';

    // DIAG - Diagnostics - Ver 2.2.3
    // back_trace( 'NOTICE', '$url: ' . $url);

    $assistant_beta_version = esc_attr(get_option('chatbot_chatgpt_assistant_beta_version', 'v2'));
    if ( $assistant_beta_version == 'v2' ) {
        $beta_version = "assistants=v2";
    } else {
        $beta_version = "assistants=v1";
    }

    // Prepare common headers
    $headers = [
        "Content-Type"  => "application/json",
        "OpenAI-Beta"   => $beta_version,
        "Authorization" => "Bearer " . $api_key,
    ];

    // Get the max prompt and completion tokens - Ver 2.0.1
    // https://platform.openai.com/docs/assistants/how-it-works/max-completion-and-max-prompt-tokens
    
    // Request body additional features - Ver 2.2.3
    // https://platform.openai.com/docs/api-reference/runs/createRun

    $max_prompt_tokens = (int) esc_attr(get_option('chatbot_chatgpt_max_prompt_tokens', 20000));
    $max_completion_tokens = (int) esc_attr(get_option('chatbot_chatgpt_max_completion_tokens', 20000));
    $temperature = (float) esc_attr(get_option('chatbot_chatgpt_temperature', 0.5));
    $top_p = (float) esc_attr(get_option('chatbot_chatgpt_top_p', 1.0));

    // Additional instructions - Ver 2.2.3
    $additional_instruction = null;
    if (isset($kchat_settings['additional_instructions']) && $kchat_settings['additional_instructions'] !== null) {
        $additional_instructions = $kchat_settings['additional_instructions'];
        // back_trace( 'NOTICE', '$additional_instructions: ' . $additional_instructions);
    }

    // DIAG - Diagnostics - Ver 2.2.3
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
    
    // Add message UUID to run metadata for end-to-end idempotency
    if (isset($message_uuid)) {
        $data["metadata"] = ["message_uuid" => $message_uuid];
    }

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
    // if ($http_code !== 200) {
    //     prod_trace('ERROR', "HTTP response code: {$http_code}");
    //     return "Error: HTTP response code {$http_code}";
    // }
    
    // Check if an error exists in the API response
    if (isset($response_data['error'])) {
        $errorMessage = $response_data['error']['message'] ?? 'Unknown error';
        $errorType = $response_data['error']['type'] ?? 'Unknown type';
    
        prod_trace('ERROR', "OpenAI API Error: {$errorMessage}");
        prod_trace('ERROR', "Error Type: {$errorType}");
    
        // Return user-friendly message for "already has an active run" error
        if (strpos($errorMessage, 'already has an active run') !== false) {
            global $chatbot_chatgpt_fixed_literal_messages;
            $default_message = "The system is currently busy processing requests. Please try again in a few moments.";
            $locked_message = isset($chatbot_chatgpt_fixed_literal_messages[19]) 
                ? $chatbot_chatgpt_fixed_literal_messages[19] 
                : $default_message;
            return $locked_message;
        }
    
        return "Error: {$errorMessage}";
    }
    
    // ✅ If no errors, return the decoded response
    return $response_data;    

}

// -------------------------------------------------------------------------
// Step 5: Get the Run's Status
// -------------------------------------------------------------------------
function getTheRunsStatus($thread_id, $runId, $api_key) {

    // DIAG - Diagnostics - Ver 2.2.3
    // back_trace( 'NOTICE', 'Step 5: getTheRunsStatus()');

    global $sleepTime;

    // Build the API URL
    $url = get_threads_api_url() . '/' . $thread_id . '/runs/' . $runId;

    // DIAG - Diagnostics - Ver 2.2.3
    // back_trace( 'NOTICE', '$url: ' . $url);

    $assistant_beta_version = esc_attr(get_option('chatbot_chatgpt_assistant_beta_version', 'v2'));
    if ( $assistant_beta_version == 'v2' ) {
        $beta_version = "assistants=v2";
    } else {
        $beta_version = "assistants=v1";
    }

    // Prepare common headers
    $headers = [
        "Content-Type"  => "application/json",
        "OpenAI-Beta"   => $beta_version,
        "Authorization" => "Bearer " . $api_key,
    ];

    $status = "";

    // Exponential backoff parameters - Ver 2.2.0 (Enhanced for slow servers)
    $initialSleep = 2000000;       // Initial sleep time in microseconds (2 seconds) - increased for slow servers
    $maxSleep = 30000000;          // Maximum sleep time in microseconds (30 seconds) - increased for slow servers
    $sleepTime = $initialSleep;
    $retryCount = 0;
    $maxRetriesBeforeReset = 3;    // Number of retries before resetting the sleep time (reduced for faster adaptation)
    $resetRangeMin = 2000000;      // Minimum reset sleep time in microseconds (2 seconds) - increased
    $resetRangeMax = 5000000;      // Maximum reset sleep time in microseconds (5 seconds) - increased
    $maxTotalRetries = 100;        // Maximum total retries to prevent infinite loops (increased for slow servers)
    $totalRetryCount = 0;          // Total retry counter

    while ($status != "completed" && $totalRetryCount < $maxTotalRetries) {

        $response = wp_remote_get($url, [
            "headers"       => $headers,
            "timeout"       => chatbot_chatgpt_get_assistant_timeout(),
        ]);
    
        // ✅ Check if `wp_remote_post()` returned an error
        if (is_wp_error($response)) {
            prod_trace('ERROR', 'HTTP Request failed: ' . $response->get_error_message());
            return "Error: Failed to communicate with the API.";
        }
    
        // ✅ Extract the body safely
        $response_body = wp_remote_retrieve_body($response);
    
        // ✅ Ensure response body is a string before decoding
        if (!is_string($response_body) || empty($response_body)) {
            prod_trace('ERROR', 'Error: API returned an empty or invalid response.');
            return "Error: Empty API response.";
        }
    
        // ✅ Decode JSON response safely
        $responseArray = json_decode($response_body, true);
    
        // ✅ Handle JSON decoding errors explicitly
        if ($responseArray === null && json_last_error() !== JSON_ERROR_NONE) {
            prod_trace('ERROR', 'JSON decode error: ' . json_last_error_msg());
            return "Error: Failed to parse API response.";
        }
    
        // ✅ Debugging: Log the decoded response
        // back_trace( 'NOTICE', 'Step 5 - Decoded Response: ' . print_r($responseArray, true));
    
        // ✅ Check if 'status' exists in the response
        if (isset($responseArray["status"])) {
            $status = $responseArray["status"];
    
            // Handle 'failed' status indicating rate limit reached
            if ($status == "failed") {
                prod_trace('ERROR', "Error - Step 5: " . $status);
                prod_trace('ERROR', '$responseArray: ' . print_r($responseArray, true));
    
                // ✅ Handle rate limiting
                if (isset($responseArray['last_error']) && $responseArray['last_error']['code'] === 'rate_limit_exceeded') {
                    $message = $responseArray['last_error']['message'] ?? '';
    
                    if (preg_match('/Please try again in (\d+\.\d+)s/', $message, $matches)) {
                        $sleepTime = (int) ceil(($matches[1] + 0.5) * 1000000);
                        prod_trace('ERROR', 'ALERT - RATE LIMIT REACHED - Sleeping for ' . $sleepTime . ' microseconds');
                        break;
                    } else {
                        prod_trace('ERROR', 'Exiting Step 5 - UNABLE TO PARSE RETRY TIME');
                        break;
                    }
                }
            }
    
            // Handle 'incomplete' status
            if ($status == "incomplete") {
                if (isset($responseArray["incomplete_details"])) {
                    prod_trace('ERROR', "Error - Step 5: " . print_r($responseArray["incomplete_details"], true));
                    break;
                }
            }
    
        }
    
        // ✅ Handle exponential backoff if status is not "completed"
        if ($status != "completed") {
            usleep($sleepTime);
            $retryCount++;
            $totalRetryCount++;
    
            if ($retryCount >= $maxRetriesBeforeReset) {
                $sleepTime = rand($resetRangeMin, $resetRangeMax);
                $retryCount = 0;
            } else {
                $sleepTime = min($sleepTime * 2, $maxSleep);
            }
    
            if ($totalRetryCount >= $maxTotalRetries) {
                prod_trace('ERROR', 'Error - GPT Assistant - Step 5: Maximum retries reached. Exiting loop.');
                break;
            }
        }
    }
    
    return $status;
    

}

// -------------------------------------------------------------------------
// Step 6: Get the Run's Steps
// -------------------------------------------------------------------------
function getTheRunsSteps($thread_id, $runId, $api_key) {

    // DIAG - Diagnostics - Ver 2.2.3
    // back_trace( 'NOTICE', 'Step 6 - getTheRunsSteps()');
    // back_trace( 'NOTICE', 'Step 6 - $thread_id: ' . $thread_id);

    // Construct the API URL
    $url = get_threads_api_url() . '/' . $thread_id . '/runs/' . $runId . '/steps';

    // Determine API version
    $assistant_beta_version = esc_attr(get_option('chatbot_chatgpt_assistant_beta_version', 'v2'));
    if ( $assistant_beta_version == 'v2' ) {
        $beta_version = "assistants=v2";
    } else {
        $beta_version = "assistants=v1";
    }

    // Prepare request headers
    $headers = [
        "Content-Type"  => "application/json",
        "OpenAI-Beta"   => $beta_version,
        "Authorization" => "Bearer " . $api_key
    ];

    $response = wp_remote_get($url, [
        "headers"       => $headers,
        "timeout"       => chatbot_chatgpt_get_assistant_timeout(),
    ]);

    // ✅ Handle request errors
    if (is_wp_error($response)) {
        prod_trace('ERROR', 'HTTP Request failed: ' . $response->get_error_message());
        return "Error: Failed to communicate with the API.";
    }

    // ✅ Extract response body safely
    $response_body = wp_remote_retrieve_body($response);

    // ✅ Ensure response body is a valid string before decoding
    if (!is_string($response_body) || empty($response_body)) {
        prod_trace('ERROR', 'Error: API returned an empty or invalid response.');
        return "Error: Empty API response.";
    }

    // ✅ Decode JSON response safely
    $response_data = json_decode($response_body, true);

    // ✅ Handle JSON decoding errors explicitly
    if ($response_data === null && json_last_error() !== JSON_ERROR_NONE) {
        prod_trace('ERROR', 'JSON decode error: ' . json_last_error_msg());
        return "Error: Failed to parse API response.";
    }

    // ✅ Debugging: Log the decoded response
    // back_trace( 'NOTICE', 'Step 6 - Decoded Response: ' . print_r($response_data, true));

    return $response_data;

}

// -------------------------------------------------------------------------
// Step 7: Get the Step's Status
// -------------------------------------------------------------------------
function getTheStepsStatus($thread_id, $runId, $api_key) {

    // DIAG - Diagnostics - Ver 2.2.3
    // back_trace( 'NOTICE', 'Step 7 - getTheStepsStatus()');
    // back_trace( 'NOTICE', 'Step 7 - $thread_id: ' . $thread_id);

    // $url = "https://api.openai.com/v1/threads/" . $thread_id . "/runs/" . $runId . "/steps";
    $url = get_threads_api_url() . '/' . $thread_id . '/runs/' . $runId . '/steps';

    // DIAG - Diagnostics - Ver 2.2.3
    // back_trace( 'NOTICE', '$url: ' . $url);

    $assistant_beta_version = esc_attr(get_option('chatbot_chatgpt_assistant_beta_version', 'v2'));
    if ( $assistant_beta_version == 'v2' ) {
        $beta_version = "assistants=v2";
    } else {
        $beta_version = "assistants=v1";
    }
    // DIAG - Diagnostics - Ver 1.9.6
    // back_trace( 'NOTICE', '$beta_version: ' . $beta_version);

    // Prepare request headers
    $headers = [
        "Content-Type"  => "application/json",
        "OpenAI-Beta"   => $beta_version,
        "Authorization" => "Bearer " . $api_key
    ];

    // Retry settings (Enhanced for slow servers)
    $max_retries = 60; // Max retries before giving up (increased for slow servers)
    $retry_count = 0;
    $sleep_time = 2000000; // 2 seconds (increased for slow servers)

    while ($retry_count < $max_retries) {

        $response = wp_remote_get($url, [
            "headers"       => $headers,
            "timeout"       => chatbot_chatgpt_get_assistant_timeout(),
        ]);

        // ✅ Handle request errors
        if (is_wp_error($response)) {
            prod_trace('ERROR', 'HTTP Request failed: ' . $response->get_error_message());
            return "Error: Failed to communicate with the API.";
        }

        // ✅ Extract response body safely
        $response_body = wp_remote_retrieve_body($response);

        // ✅ Ensure response body is valid before decoding
        if (!is_string($response_body) || empty($response_body)) {
            prod_trace('ERROR', 'Error: API returned an empty or invalid response.');
            return "Error: Empty API response.";
        }

        // ✅ Decode JSON response safely
        $responseArray = json_decode($response_body, true);

        // ✅ Handle JSON decoding errors explicitly
        if ($responseArray === null && json_last_error() !== JSON_ERROR_NONE) {
            prod_trace('ERROR', 'JSON decode error: ' . json_last_error_msg());
            return "Error: Failed to parse API response.";
        }

        // ✅ Debugging: Log the decoded response
        // back_trace( 'NOTICE', 'Step 7 - Decoded Response: ' . print_r($responseArray, true));

        // ✅ Check for "data" field
        if (isset($responseArray["data"]) && is_array($responseArray["data"])) {
            foreach ($responseArray["data"] as $item) {
                if (isset($item["status"]) && $item["status"] === "completed") {
                    return "completed";
                }
            }
        } else {
            // ✅ Log and return failure if "data" field is missing
            prod_trace('ERROR', 'Error - GPT Assistant - Step 7: Invalid API response.');
            return "Error: Missing 'data' in API response.";
        }

        // Sleep before retrying with progressive increase for slow servers
        usleep($sleep_time);
        $retry_count++;
        
        // Increase sleep time progressively (2s, 4s, 6s, 8s, 10s max)
        $sleep_time = min($sleep_time + 2000000, 10000000); // Add 2s each time, max 10s
    }

    // ✅ Log and return failure if retries exceeded
    prod_trace('ERROR', 'Error - GPT Assistant - Step 7: Maximum retries reached.');
    return "Error: Maximum retries reached.";

}

// -------------------------------------------------------------------------
// Step 8: Get the Message
// -------------------------------------------------------------------------
function getTheMessage($thread_id, $api_key, $run_id = null) {

    // DIAG - Diagnostics - Ver 2.2.3
    // back_trace( 'NOTICE', 'Step 8 - getTheMessage()');
    // back_trace( 'NOTICE', 'Step 8 - $thread_id: ' . $thread_id);

    $url = get_threads_api_url() . '/' . $thread_id . '/messages';
    
    // Add run_id filter if provided to only get messages from the current run
    if ($run_id) {
        $url .= '?run_id=' . $run_id . '&order=asc';
    }

    // DIAG - Diagnostics - Ver 2.2.3
    // back_trace( 'NOTICE', '$url: ' . $url);

    $assistant_beta_version = esc_attr(get_option('chatbot_chatgpt_assistant_beta_version', 'v2'));
    if ( $assistant_beta_version == 'v2' ) {
        $beta_version = "assistants=v2";
    } else {
        $beta_version = "assistants=v1";
    }
    // DIAG - Diagnostics - Ver 1.9.6
    // back_trace( 'NOTICE', '$beta_version: ' . $beta_version);

    $headers = [
        'Content-Type'  => 'application/json',
        'OpenAI-Beta'   => $beta_version,
        'Authorization' => 'Bearer ' . $api_key
    ];

    $response = wp_remote_get($url, [
        "headers"       => $headers,
        "timeout"       => chatbot_chatgpt_get_assistant_timeout(),
    ]);

    // ✅ Handle request errors
    if (is_wp_error($response)) {
        prod_trace('ERROR', 'HTTP Request failed: ' . $response->get_error_message());
        return "Error: Failed to communicate with the API.";
    }

    // ✅ Extract response body
    $response_body = wp_remote_retrieve_body($response);

    // ✅ Ensure the response is a valid JSON string before decoding
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
                        set_transient('chatbot_chatgpt_delete_uploaded_file_' . $file_id, $file_id, $timeFrameForDelete);

                        // Set a cron job to delete the file in 1 hour 45 minutes
                        $shorterTimeFrameForDelete = time() + 1 * 60 * 60 + 45 * 60;
                        if (!wp_next_scheduled('delete_uploaded_file', array($file_id))) {
                            wp_schedule_single_event($shorterTimeFrameForDelete, 'delete_uploaded_file', array($file_id));
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
                                set_transient('chatbot_chatgpt_delete_uploaded_file_' . $file_id, $file_id, $timeFrameForDelete);

                                // Set a cron job to delete the file in 1 hour 45 minutes
                                $shorterTimeFrameForDelete = time() + 1 * 60 * 60 + 45 * 60;
                                if (!wp_next_scheduled('delete_uploaded_file', array($file_id))) {
                                    wp_schedule_single_event($shorterTimeFrameForDelete, 'delete_uploaded_file', array($file_id));
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

// Add this new function before chatbot_chatgpt_custom_gpt_call_api
function cancel_active_run($thread_id, $api_key) {
  // back_trace( 'NOTICE', 'Checking for active runs on thread: ' . $thread_id);
    
    $url = get_threads_api_url() . '/' . $thread_id . '/runs';
    $assistant_beta_version = esc_attr(get_option('chatbot_chatgpt_assistant_beta_version', 'v2'));
    $beta_version = ($assistant_beta_version == 'v2') ? "assistants=v2" : "assistants=v1";
    
    $headers = [
        "Content-Type"  => "application/json",
        "OpenAI-Beta"   => $beta_version,
        "Authorization" => "Bearer " . $api_key
    ];

    // First, try to get the current run status
    $current_run_url = $url . '?limit=1&order=desc';
    $response = wp_remote_get($current_run_url, [
        "headers" => $headers,
        "timeout" => 30,
    ]);

    if (is_wp_error($response)) {
      // back_trace( 'ERROR', 'Failed to get current run: ' . $response->get_error_message());
        return false;
    }

    $response_data = json_decode(wp_remote_retrieve_body($response), true);
    
    if (!isset($response_data['data']) || !is_array($response_data['data']) || empty($response_data['data'])) {
      // back_trace( 'NOTICE', 'No runs found for thread');
        return true;
    }

    // Get the most recent run
    $current_run = $response_data['data'][0];
    
    if (isset($current_run['status']) && in_array($current_run['status'], ['in_progress', 'queued'])) {
      // back_trace( 'NOTICE', 'Found active run: ' . $current_run['id']);
        
        // Cancel the run
        $cancel_url = $url . '/' . $current_run['id'] . '/cancel';
        $cancel_response = wp_remote_post($cancel_url, [
            "headers" => $headers,
            "timeout" => 30,
        ]);

        if (is_wp_error($cancel_response)) {
          // back_trace( 'ERROR', 'Failed to cancel run: ' . $cancel_response->get_error_message());
            return false;
        }

        // Wait for the cancellation to take effect
        $max_wait = 5;
        $wait_count = 0;
        $wait_time = 1000000; // 1 second in microseconds

        while ($wait_count < $max_wait) {
            usleep($wait_time);
            
            // Check if the run is cancelled by polling the run status endpoint
            $run_status_url = get_threads_api_url() . '/' . $thread_id . '/runs/' . $current_run['id'];
            $check_response = wp_remote_get($run_status_url, [
                "headers" => $headers,
                "timeout" => 30,
            ]);
            
            if (!is_wp_error($check_response)) {
                $check_data = json_decode(wp_remote_retrieve_body($check_response), true);
                if (isset($check_data['status']) && $check_data['status'] === 'cancelled') {
                  // back_trace( 'NOTICE', 'Run successfully cancelled');
                    return true;
                }
            }
            
            $wait_count++;
          // back_trace( 'NOTICE', 'Waiting for run cancellation. Attempt ' . $wait_count . ' of ' . $max_wait);
        }

      // back_trace( 'ERROR', 'Run cancellation timed out');
        return false;
    }

  // back_trace( 'NOTICE', 'No active runs found to cancel');
    return true;
}

// CustomGPT - Assistants - Ver 1.7.2
function chatbot_chatgpt_custom_gpt_call_api($api_key, $message, $assistant_id, $thread_id, $session_id, $user_id, $page_id, $client_message_id = null) {

    // DIAG - Diagnostics - Ver 1.8.6
    // back_trace( 'NOTICE', 'chatbot_chatgpt_custom_gpt_call_api()' );
    // back_trace( 'NOTICE', '$user_id: ' . $user_id);
    // back_trace( 'NOTICE', '$page_id: ' . $page_id);
    // back_trace( 'NOTICE', '$session_id: ' . $session_id);
    // back_trace( 'NOTICE', '$assistant_id: ' . $assistant_id);
    // back_trace( 'NOTICE', '$thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', '$message: ' . $message);
    // back_trace( 'NOTICE', '$additional_instructions: ' . $additional_instructions);

    // Use client_message_id if provided, otherwise generate a unique message UUID for idempotency
    $message_uuid = $client_message_id ? $client_message_id : wp_generate_uuid4();
    
    // Lock the conversation BEFORE thread resolution to prevent empty-thread vs real-thread lock split
    $conv_lock = 'chatgpt_conv_lock_' . wp_hash($assistant_id . '|' . $user_id . '|' . $page_id . '|' . $session_id);
    $lock_timeout = 60; // 60 seconds timeout
    
    // Check for duplicate message UUID in conversation log
    $duplicate_key = 'chatgpt_message_uuid_' . $message_uuid;
    if (get_transient($duplicate_key)) {
        // DIAG - Diagnostics - Ver 2.3.4
        // back_trace( 'NOTICE', 'Duplicate message UUID detected: ' . $message_uuid);
        return "Error: Duplicate request detected. Please try again.";
    }
    
    // Lock check removed - main send function handles locking
    
    // Lock setting removed - main send function handles locking
    set_transient($duplicate_key, true, 300); // 5 minutes to prevent duplicates
    
    // Log the start of the request
    // DIAG - Diagnostics - Ver 2.3.5
    // prod_trace('NOTICE', 'Starting API call - Assistant: ' . $assistant_id . ', User: ' . $user_id . ', Page: ' . $page_id . ', Session: ' . $session_id . ', Message UUID: ' . $message_uuid);

    // Globals added for Ver 1.7.2
    global $learningMessages;
    global $errorResponses;
    global $stopWords;

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
        // back_trace( 'NOTICE', '$thread_id was NOT empty');
        // back_trace( 'NOTICE', '$thread_id was NOT empty but passed as $thread_id: ' . $thread_id);
    }

    // If the thread_id is not set, create a new thread
    if (empty($thread_id)) {

        // Step 1 - Create an Assistant
        // back_trace( 'NOTICE', 'Step 1: Create an Assistant');
        $api_key = esc_attr(get_option('chatbot_chatgpt_api_key', ''));
        // Decrypt the API key - Ver 2.2.6
        $api_key = chatbot_chatgpt_decrypt_api_key($api_key);

        $assistants_response = createAnAssistant($api_key);
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
        
        // Thread lock check removed - main send function handles locking
        
    } else {

        $thread_id = get_chatbot_chatgpt_threads($user_id, $session_id, $page_id, $assistant_id);
        
        // Thread lock check removed - main send function handles locking

    }

    // Conversation Context - Ver 2.2.3
    $context = "";
    $context = esc_attr(get_option('chatbot_chatgpt_conversation_context', 'You are a versatile, friendly, and helpful assistant designed to support me in a variety of tasks that responds in Markdown.'));
 
    // Step 3: Add a Message to a Thread
    // back_trace( 'NOTICE', 'Step 3 - Add a Message to a Thread');
    $prompt = $message;
        
    // Fetch the file id - Ver 2.23
    $file_id = chatbot_chatgpt_retrieve_file_id($user_id, $page_id);
    
    // DIAG - Diagnostics - Version 2.3.5.2 - Log what files are being retrieved for the prompt
    // back_trace( 'NOTICE', 'PROMPT DEBUG: Session ID: ' . $session_id);
    // back_trace( 'NOTICE', 'PROMPT DEBUG: Retrieved file_id for prompt: ' . print_r($file_id, true));

    // DIAG - Diagnostics - Ver 2.2.3
    // back_trace( 'NOTICE', '$user_id: ' . $user_id);
    // back_trace( 'NOTICE', '$page_id: ' . $page_id);

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
    // back_trace( 'NOTICE', 'RIGHT BEFORE CALL to addAMessage - $thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', 'RIGHT BEFORE CALL to addAMessage - $prompt: ' . $prompt);
    // back_trace( 'NOTICE', 'RIGHT BEFORE CALL to addAMessage - $content: ' . $context);
    // back_trace( 'NOTICE', 'chatbot_chatgpt_retrieve_file_id(): ' . print_r($file_id, true));

    // ENHANCED CONTEXT - Select some context to send with the message - Ver 2.2.4
    $use_enhanced_content_search = esc_attr(get_option('chatbot_chatgpt_use_advanced_content_search', 'No'));

    // ENHANCED CONTEXT - Select some context to send with the message - Ver 2.2.4 - Updated Ver 2.2.9
    // if ($use_enhanced_content_search == 'Yes') {

    //     $search_results = chatbot_chatgpt_content_search($message);
    //     if (!empty($search_results) && isset($search_results['results'])) {
    //         // Format the search results into a readable string
    //         $formatted_results = '';
    //         foreach ($search_results['results'] as $result) {
    //             $formatted_results .= "\nTitle: " . $result['title'] . "\n";
    //             if (isset($result['excerpt'])) {
    //                 $formatted_results .= "Content: " . $result['excerpt'] . "\n";
    //             }
    //             $formatted_results .= "URL: " . $result['url'] . "\n";
    //         }
    //         // Append the formatted search results to the prompt
    //         $prompt = $prompt . ' When answering the prompt, please consider the following information: ' . $formatted_results;
    //     }
    //     // DIAG Diagnostics - Ver 2.2.4 - 2025-02-04
    //     // back_trace( 'NOTICE', '$prompt: ' . $prompt);

    // }

    if ($use_enhanced_content_search == 'Yes') {

        $search_results = chatbot_chatgpt_content_search($message);
        If ( !empty ($search_results) ) {
            // Extract relevant content from search results array
            $content_texts = [];
            foreach ($search_results['results'] as $result) {
                if (!empty($result['excerpt'])) {
                    $content_texts[] = $result['excerpt'];
                }
            }
            // Join the content texts and append to context
            if (!empty($content_texts)) {
                $prompt = $prompt . ' When answering the prompt, please consider the following information: ' . implode(' ', $content_texts);
            }
        }
        // DIAG Diagnostics - Ver 2.2.4 - 2025-02-04
        // back_trace( 'NOTICE', '$context: ' . $context);

    } else {

        // When Advanced Content Search is disabled, send only the basic context - Ver 2.3.5.2
        // Initialize variables to prevent undefined variable warnings
        $sys_message = '';
        $chatgpt_last_response = '';
        $context = $sys_message . ' ' . $chatgpt_last_response . ' ' . $context;
        
        // DIAG - Diagnostics - Version 2.3.5.2 - Log that Knowledge Navigator context is being excluded
        // back_trace( 'NOTICE', 'KNOWLEDGE NAVIGATOR DEBUG: Advanced Content Search disabled - excluding Knowledge Navigator context');

    }

    if (empty($file_id)) {
        // back_trace( 'NOTICE', 'No file to retrieve');
        $assistants_response = addAMessage($thread_id, $prompt, $context, $api_key, '', $message_uuid);
    } else {
        //DIAG - Diagnostics - Ver 1.7.9
        // back_trace( 'NOTICE', 'File to retrieve');
        // back_trace( 'NOTICE', '$file_id ' . print_r($file_id, true));
        $assistants_response = addAMessage($thread_id, $prompt, $context, $api_key, $file_id, $message_uuid);
        // DIAG - Print the response
        // back_trace( 'NOTICE', $assistants_response);
    }

    $retries = 0;
    $maxRetries = 10;
    $sleepTime = $sleepTime ?? 500000; // Default to 500 milliseconds if not set

    do {

        $run_status = '';

        // Step 4: Run the Assistant
      // back_trace( 'NOTICE', 'Step 4 - Run the Assistant');
        
        // Check for active runs before creating a new one
        $run_status_url = get_threads_api_url() . '/' . $thread_id . '/runs?limit=1&order=desc';
        $assistant_beta_version = esc_attr(get_option('chatbot_chatgpt_assistant_beta_version', 'v2'));
        $beta_version = ($assistant_beta_version == 'v2') ? "assistants=v2" : "assistants=v1";
        
        $headers = [
            "Content-Type"  => "application/json",
            "OpenAI-Beta"   => $beta_version,
            "Authorization" => "Bearer " . $api_key
        ];
        
        $latest = wp_remote_get($run_status_url, [
            'headers' => $headers,
            'timeout' => 30
        ]);
        
        $active = false;
        if (!is_wp_error($latest)) {
            $j = json_decode(wp_remote_retrieve_body($latest), true);
            $active = !empty($j['data'][0]) && in_array($j['data'][0]['status'], ['in_progress', 'queued', 'requires_action']);
        }
        
        if ($active) {
            // Clear locks and return friendly message
            $thread_lock = 'chatgpt_run_lock_' . $thread_id;
            delete_transient($thread_lock);
            delete_transient($conv_lock);
            // DIAG - Diagnostics - Ver 2.3.5
            // prod_trace('NOTICE', 'Active run detected, returning friendly message');
            global $chatbot_chatgpt_fixed_literal_messages;
            $default_message = "The system is currently busy processing requests. Please try again in a few moments.";
            $locked_message = isset($chatbot_chatgpt_fixed_literal_messages[19]) 
                ? $chatbot_chatgpt_fixed_literal_messages[19] 
                : $default_message;
            return $locked_message;
        }
        
        $assistants_response = runTheAssistant($thread_id, $assistant_id, $context, $api_key, $message_uuid);

        // Check if the response is not an array or is a string indicating an error
        if (!is_array($assistants_response) || is_string($assistants_response)) {
          // back_trace( 'ERROR', 'Invalid response format or error occurred');
            // Clear both locks before returning error
            $thread_lock = 'chatgpt_run_lock_' . $thread_id;
            delete_transient($thread_lock);
            delete_transient($conv_lock);
            return "Error: Invalid response format or error occurred.";
        }

        // Check if the 'id' key exists in the response
        if (isset($assistants_response["id"])) {
            $runId = $assistants_response["id"];
            // Log the run creation
            // DIAG - Diagnostics - Ver 2.3.5
            // prod_trace('NOTICE', 'Run created - Thread: ' . $thread_id . ', Run ID: ' . $runId . ', Message UUID: ' . $message_uuid);
        } else {
          // back_trace( 'ERROR', 'runId key not found in response');
            // Clear both locks before returning error
            $thread_lock = 'chatgpt_run_lock_' . $thread_id;
            delete_transient($thread_lock);
            delete_transient($conv_lock);
            return "Error: 'id' key not found in response.";
        }

        // Monitor the run and handle tool calls if needed
        $max_status_checks = 30; // Maximum number of status checks
        $check_count = 0;
        $run_completed = false;
        $tool_used = false;

        while (!$run_completed && $check_count < $max_status_checks) {
            // Wait before checking status again
            usleep($sleepTime);
            $check_count++;
            
            // Get the current run status
            $run_status_url = get_threads_api_url() . '/' . $thread_id . '/runs/' . $runId;
            
            $assistant_beta_version = esc_attr(get_option('chatbot_chatgpt_assistant_beta_version', 'v2'));
            $beta_version = ($assistant_beta_version == 'v2') ? "assistants=v2" : "assistants=v1";
            
            $headers = [
                "Content-Type"  => "application/json",
                "OpenAI-Beta"   => $beta_version,
                "Authorization" => "Bearer " . $api_key
            ];
            
            $status_response = wp_remote_get($run_status_url, [
                "headers" => $headers,
                "timeout" => 30,
            ]);
            
            if (is_wp_error($status_response)) {
              // back_trace( 'ERROR', 'Failed to check run status: ' . $status_response->get_error_message());
                continue;
            }
            
            $status_body = wp_remote_retrieve_body($status_response);
            $status_data = json_decode($status_body, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
              // back_trace( 'ERROR', 'Failed to parse run status: ' . json_last_error_msg());
                continue;
            }
            
          // back_trace( 'NOTICE', 'Run status check ' . $check_count . ': ' . ($status_data['status'] ?? 'unknown'));
            
            // Check if the run requires action (tool execution)
            if (isset($status_data['status']) && $status_data['status'] === 'requires_action') {
              // back_trace( 'NOTICE', 'Run requires action - calling check_assistant_tool_usage');
                $tool_used = check_assistant_tool_usage($assistant_id, $thread_id, $runId, $api_key);
                if ($tool_used) {
                  // back_trace( 'NOTICE', 'Tool was used, continuing run monitoring');
                }
            }
            // Check if the run has completed
            else if (isset($status_data['status']) && $status_data['status'] === 'completed') {
              // back_trace( 'NOTICE', 'Run completed successfully');
                $run_completed = true;
                $run_status = "completed";
                // Log run completion
                // DIAG - Diagnostics - Ver 2.3.5
                // prod_trace('NOTICE', 'Run completed - Thread: ' . $thread_id . ', Run ID: ' . $runId . ', Message UUID: ' . $message_uuid);
                break;
            }
            // Check if the run has failed
            else if (isset($status_data['status']) && ($status_data['status'] === 'failed' || $status_data['status'] === 'expired')) {
              // back_trace( 'ERROR', 'Run ' . $status_data['status'] . ': ' . print_r($status_data['last_error'] ?? [], true));
                $run_status = $status_data['status'];
                break;
            }
        }

        // Check if we reached the maximum number of status checks
        if ($check_count >= $max_status_checks && !$run_completed) {
          // back_trace( 'ERROR', 'Run monitoring timed out after ' . $max_status_checks . ' checks');
            $run_status = "timeout";
        }

        $retries++;

        if ($run_status == "failed" || $run_status == "incomplete" || $run_status == "timeout") {
          // back_trace( 'NOTICE', 'Run not completed. Status: ' . $run_status);
          // back_trace( 'NOTICE', 'Retry ' . $retries . ' of ' . $maxRetries);
            usleep($sleepTime);
        }

    } while ($run_status != "completed" && $retries < $maxRetries);

    // Failed after multiple retries
    if ($run_status != "completed") {
      // back_trace( 'ERROR', 'Run failed after ' . $maxRetries . ' retries. Status: ' . $run_status);
        // Clear both locks before returning error
        // Lock clearing removed - main send function handles locking
        return "Error: Run failed after maximum retries. Status: " . $run_status;
    }

    // Step 6: Get the Run's Steps
  // back_trace( 'NOTICE', 'Step 6 - Get the Run\'s Steps');
    $assistants_response = getTheRunsSteps($thread_id, $runId, $api_key);
    
    // Add the usage to the conversation tracker
    if (is_array($assistants_response) && 
        isset($assistants_response["data"]) && 
        is_array($assistants_response["data"]) && 
        isset($assistants_response["data"][0]) && 
        isset($assistants_response["data"][0]["usage"])) {
        append_message_to_conversation_log($session_id, $user_id, $page_id, 'Prompt Tokens', $thread_id, $assistant_id, null, $assistants_response["data"][0]["usage"]["prompt_tokens"]);
        append_message_to_conversation_log($session_id, $user_id, $page_id, 'Completion Tokens', $thread_id, $assistant_id, null, $assistants_response["data"][0]["usage"]["completion_tokens"]);
        append_message_to_conversation_log($session_id, $user_id, $page_id, 'Total Tokens', $thread_id, $assistant_id, null, $assistants_response["data"][0]["usage"]["total_tokens"]);
    }

    // Step 7: Get the Step's Status
  // back_trace( 'NOTICE', 'Step 7 - Get the Step\'s Status');
    getTheStepsStatus($thread_id, $runId, $api_key);

    // Step 8: Get the Message - Filter to only show current run results
  // back_trace( 'NOTICE', 'Step 8: Get the Message');
    $assistants_response = getTheMessage($thread_id, $api_key, $runId);
    
    // Log message retrieval
    if (isset($assistants_response["data"][0]["id"])) {
        $message_id = $assistants_response["data"][0]["id"];
        // DIAG - Diagnostics - Ver 2.3.5
        // prod_trace('NOTICE', 'Message retrieved - Thread: ' . $thread_id . ', Run ID: ' . $runId . ', Message ID: ' . $message_id . ', Message UUID: ' . $message_uuid);
    }

    // Interaction Tracking - Ver 1.6.3
    update_interaction_tracking();

    // Remove citations from the response
    if (is_array($assistants_response) && 
        isset($assistants_response["data"]) && 
        is_array($assistants_response["data"]) && 
        isset($assistants_response["data"][0]) && 
        isset($assistants_response["data"][0]["content"]) && 
        is_array($assistants_response["data"][0]["content"]) && 
        isset($assistants_response["data"][0]["content"][0]) && 
        isset($assistants_response["data"][0]["content"][0]["text"]) && 
        isset($assistants_response["data"][0]["content"][0]["text"]["value"])) {
        $assistants_response["data"][0]["content"][0]["text"]["value"] = preg_replace('/\【.*?\】/', '', $assistants_response["data"][0]["content"][0]["text"]["value"]);
    }

    // Check for missing $thread_id in $kchat_settings
    if (!isset($kchat_settings['thread_id'])) {
        $kchat_settings['thread_id'] = $thread_id;
    }

    // DIAG - Diagnostics - Ver 2.2.1
    // back_trace( 'NOTICE', '$assistants_response: ' . print_r($assistants_response, true));

    // Add a check here to see if the response [data][0][content][0][text][value] contains the string "[conversation_transcript]"
    // First validate that $assistants_response is an array with the expected structure
    if (is_array($assistants_response) && 
        isset($assistants_response["data"]) && 
        is_array($assistants_response["data"]) && 
        isset($assistants_response["data"][0]) && 
        isset($assistants_response["data"][0]["content"]) && 
        is_array($assistants_response["data"][0]["content"]) && 
        isset($assistants_response["data"][0]["content"][0]) && 
        isset($assistants_response["data"][0]["content"][0]["text"]) && 
        isset($assistants_response["data"][0]["content"][0]["text"]["value"]) && 
        strpos($assistants_response["data"][0]["content"][0]["text"]["value"], "[conversation_transcript]") !== false) {
        // back_trace( 'NOTICE', 'The response contains the string "[conversation_transcript]"');
        
        // Build the conversation transcript by gathering all messages in reverse order
        $conversation_transcript = '';
        if (isset($assistants_response['data']) && is_array($assistants_response['data'])) {
            // Reverse the array to get messages in chronological order (oldest to newest)
            $messages = array_reverse($assistants_response['data']);
            
            foreach ($messages as $message) {
                if (isset($message['content'][0]['text']['value'])) {
                    $role = isset($message['role']) ? ucfirst($message['role']) : 'Unknown';
                    $content = $message['content'][0]['text']['value'];
                    $conversation_transcript .= "[{$role}]: {$content}\n\n";
                }
            }
        }
        
        // DIAG - Diagnostics - Ver 2.2.7
        // back_trace( 'NOTICE', '$conversation_transcript: ' . $conversation_transcript);
        
        // Now send the $conversation_transcript via email to the email address specified in the option
        $email_address = esc_attr(get_option('chatbot_chatgpt_conversation_transcript_email', ''));
        if (!empty($email_address)) {
            wp_mail($email_address, 'Conversation Transcript', $conversation_transcript);
        }
        
        // Then remove the "[conversation_transcript]" from the response
        $assistants_response["data"][0]["content"][0]["text"]["value"] = str_replace("[conversation_transcript]", '', $assistants_response["data"][0]["content"][0]["text"]["value"]);
    } else {
        // back_trace( 'NOTICE', 'The response does not contain the string "[conversation_transcript]"');
    }

    // Clear both locks before returning
    // Lock clearing removed - main send function handles locking
    
    // Mark uploaded files for deletion after successful processing - Ver 2.3.5.2
    if (!empty($file_id)) {
        $counter = 0;
        $current_file_id = get_chatbot_chatgpt_transients_files('chatbot_chatgpt_assistant_file_ids', $session_id, $counter);
        
        while (!empty($current_file_id)) {
            // Set a transient that expires in 2 hours
            $timeFrameForDelete = time() + 2 * 60 * 60;
            set_transient('chatbot_chatgpt_delete_uploaded_file_' . $current_file_id, $current_file_id, $timeFrameForDelete);

            // Set a cron job to delete the file in 1 hour 45 minutes
            $shorterTimeFrameForDelete = time() + 1 * 60 * 60 + 45 * 60;
            if (!wp_next_scheduled('delete_uploaded_file', array($current_file_id))) {
                wp_schedule_single_event($shorterTimeFrameForDelete, 'delete_uploaded_file', array($current_file_id));
            }
            
            // Increment counter and get next file
            $counter++;
            $current_file_id = get_chatbot_chatgpt_transients_files('chatbot_chatgpt_assistant_file_ids', $session_id, $counter);
        }
    }
    
    // Log the completion of the request
    // prod_trace('NOTICE', 'Completed API call - Thread: ' . $thread_id . ', Message UUID: ' . $message_uuid);

    // Return the response text, checking for the fallback content[1][text] if available
    if (is_array($assistants_response) && 
        isset($assistants_response["data"]) && 
        is_array($assistants_response["data"]) && 
        isset($assistants_response["data"][0]) && 
        isset($assistants_response["data"][0]["content"]) && 
        is_array($assistants_response["data"][0]["content"])) {
        
        // Check for content[1][text] first (fallback)
        if (isset($assistants_response["data"][0]["content"][1]["text"]["value"])) {
            return $assistants_response["data"][0]["content"][1]["text"]["value"];
        } 
        // Check for content[0][text] (primary)
        elseif (isset($assistants_response["data"][0]["content"][0]["text"]["value"])) {
            return $assistants_response["data"][0]["content"][0]["text"]["value"];
        }
    }
    
    // If $assistants_response is not an array or doesn't have the expected structure, 
    // it might be an error message string, so return it
    if (is_string($assistants_response)) {
        return $assistants_response;
    }
    
    // Return a default value if none exist
    return 'Error: Unable to retrieve response from API.';

}

// -------------------------------------------------------------------------
// Retrieve the first file id - Ver 2.2.3
// -------------------------------------------------------------------------
function chatbot_chatgpt_retrieve_file_id( $user_id, $page_id ) {

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
    $file_types = get_chatbot_chatgpt_transients_files('chatbot_chatgpt_assistant_file_types', $session_id, $counter);

    // DIAG - Diagnostics - Ver 2.0.3
    // back_trace( 'NOTICE', 'chatbot_chatgpt_retrieve_file_id(): ' . print_r($file_id, true));
    // back_trace( 'NOTICE', 'chatbot_chatgpt_retrieve_file_id(): ' . print_r($file_types, true));
    
    // DIAG - Diagnostics - Version 2.3.5.2 - Log what files are being retrieved
    // back_trace( 'NOTICE', 'FILE RETRIEVAL DEBUG: Session ID: ' . $session_id);
    // back_trace( 'NOTICE', 'FILE RETRIEVAL DEBUG: Counter: ' . $counter);
    // back_trace( 'NOTICE', 'FILE RETRIEVAL DEBUG: Retrieved file_id: ' . $file_id);
    // back_trace( 'NOTICE', 'FILE RETRIEVAL DEBUG: Retrieved file_types: ' . $file_types);

    while (!empty($file_id)) {
        // Add the file id to the list
        $file_ids[] = $file_id;
        $file_ids[$file_id] = $file_types;

        // Increment the counter
        $counter++;

        // Retrieve the next file id
        $file_id = get_chatbot_chatgpt_transients_files('chatbot_chatgpt_assistant_file_ids', $session_id, $counter);
        $file_types = get_chatbot_chatgpt_transients_files('chatbot_chatgpt_assistant_file_types', $session_id, $counter);

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
// Cleanup in Aisle 4 on OpenAI - Ver 2.2.3
// -------------------------------------------------------------------------
function deleteUploadedFile($file_id) {

    // DIAG - Diagnostics - Ver 2.2.3
    // back_trace( 'NOTICE', 'deleteUploadedFile(): ' . $file_id);

    // Get the API key
    $api_key = esc_attr(get_option('chatbot_chatgpt_api_key'));
    // Decrypt the API key - Ver 2.2.6
    $api_key = chatbot_chatgpt_decrypt_api_key($api_key);

    // Construct the API URL
    // $url = 'https://api.openai.com/v1/files/' . $file_id;
    $url = get_files_api_url() . '/' . $file_id;

    // Send DELETE request using WP functions
    $response = wp_remote_request($url, [
        'method'    => 'DELETE',
        'timeout'   => 15,
        'headers'   => [
            'Authorization'  => 'Bearer ' . $api_key,
            'Content-Type'   => 'application/json',
        ]
    ]);

    // Handle errors
    if (is_wp_error($response)) {
        prod_trace( 'ERROR', 'Error deleting file from OpenAI: ' . $response->get_error_message());
        return false;
    }

    // Get the HTTP status code
    $http_status_code = wp_remote_retrieve_response_code($response);
    
    if ($http_status_code == 200 || $http_status_code == 204) {
        // DIAG - Diagnostics - Ver 1.7.9
        // back_trace( 'SUCCESS', "File deleted successfully.\n");
    } else {
        // DIAG - Diagnostics - Ver 1.7.9
        prod_trace( 'ERROR', 'HTTP status code: ' . $http_status_code );
        prod_trace( 'ERROR', 'Response: ' . print_r($response, true) );
    }

}
add_action( 'delete_uploaded_file', 'deleteUploadedFile' );

// Check for Tool Usage by OpenAI Assistant - Ver 2.2.7
function check_assistant_tool_usage($assistant_id, $thread_id, $run_id, $api_key) {
    
    // DIAG - Diagnostics - Ver 2.2.7
    // back_trace( 'NOTICE', 'Starting tool usage check');
    // back_trace( 'NOTICE', 'Parameters:');
    // back_trace( 'NOTICE', '- Assistant ID: ' . $assistant_id);
    // back_trace( 'NOTICE', '- Thread ID: ' . $thread_id);
    // back_trace( 'NOTICE', '- Run ID: ' . $run_id);

    try {

        // Construct the API URL for run steps
        $url = get_threads_api_url() . '/' . $thread_id . '/runs/' . $run_id;

        // DIAG - Diagnostics - Ver 2.2.7
        // back_trace( 'NOTICE', 'Checking run status from: ' . $url);

        // Determine API version
        $assistant_beta_version = esc_attr(get_option('chatbot_chatgpt_assistant_beta_version', 'v2'));
        $beta_version = ($assistant_beta_version == 'v2') ? "assistants=v2" : "assistants=v1";

        // Prepare request headers
        $headers = [
            "Content-Type"  => "application/json",
            "OpenAI-Beta"   => $beta_version,
            "Authorization" => "Bearer " . $api_key
        ];

        // Get the run status first
        $response = wp_remote_get($url, [
            "headers" => $headers,
            "timeout" => 30,
        ]);

        // Handle request errors
        if (is_wp_error($response)) {

            // DIAG - Diagnostics - Ver 2.2.7
            // back_trace( 'ERROR', 'Failed to get run status: ' . $response->get_error_message());
            return false;

        }

        // Extract response body
        $response_body = wp_remote_retrieve_body($response);
        $run_data = json_decode($response_body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {

            // DIAG - Diagnostics - Ver 2.2.7
            // back_trace( 'ERROR', 'JSON decode error: ' . json_last_error_msg());
            return false;

        }

        // DIAG - Diagnostics - Ver 2.2.7
        // back_trace( 'NOTICE', 'Run status: ' . ($run_data['status'] ?? 'unknown'));

        // Check if the run requires action
        if (isset($run_data['status']) && $run_data['status'] === 'requires_action') {

            // DIAG - Diagnostics - Ver 2.2.7
            // back_trace( 'NOTICE', 'Run requires action - processing tool calls');
            
            if (isset($run_data['required_action']) && 
                isset($run_data['required_action']['type']) && 
                $run_data['required_action']['type'] === 'submit_tool_outputs') {
                
                $tool_calls = $run_data['required_action']['submit_tool_outputs']['tool_calls'] ?? [];
                
                if (empty($tool_calls)) {

                    // DIAG - Diagnostics - Ver 2.2.7
                    // back_trace( 'NOTICE', 'No tool calls found in required action');
                    return false;

                }
                
                // Process each tool call
                $tool_outputs = [];
                
                foreach ($tool_calls as $tool_call) {
                    if (isset($tool_call['function']['name']) && $tool_call['function']['name'] === 'query_wordpress_api') {

                        // DIAG - Diagnostics - Ver 2.2.7
                        // back_trace( 'NOTICE', 'Found WordPress search tool call with ID: ' . $tool_call['id']);
                        
                        try {
                            // Parse the function arguments
                            $args = json_decode($tool_call['function']['arguments'], true);
                            if (json_last_error() !== JSON_ERROR_NONE) {
                                // back_trace( 'ERROR', 'Failed to parse tool arguments: ' . json_last_error_msg());
                                continue;
                            }
                            
                            // DIAG - Diagnostics - Ver 2.2.7
                            // back_trace( 'NOTICE', 'Tool arguments parsed successfully');
                            // back_trace( 'NOTICE', 'Query: ' . ($args['query'] ?? 'None provided'));
                            
                            // Set default values if not provided
                            $query = isset($args['query']) ? $args['query'] : '';
                            $include_excerpt = isset($args['include_excerpt']) ? (bool)$args['include_excerpt'] : true;
                            $page = isset($args['page']) ? (int)$args['page'] : 1;
                            $per_page = isset($args['per_page']) ? (int)$args['per_page'] : 5;
                            
                            // Make the WordPress API call directly to our endpoint
                            $request_url = rest_url('assistant/v1/search');
                            $request_args = [
                                'method' => 'GET',
                                'timeout' => 30,
                                'headers' => [
                                    'Content-Type' => 'application/json',
                                    'X-Assistant-ID' => $assistant_id, // Added header for endpoint security
                                ],
                                // FIXME - REMOVE THIS FOR PRODUCTION
                                'sslverify' => false,
                            ];
                            
                            // Add the query parameters
                            $request_url = add_query_arg([
                                'endpoint' => 'search',
                                'query' => $query,
                                'include_excerpt' => $include_excerpt ? 'true' : 'false',
                                'page' => $page,
                                'per_page' => $per_page
                            ], $request_url);
                            
                            // DIAG - Diagnostics - Ver 2.2.7
                            // back_trace( 'NOTICE', 'Making search request to: ' . $request_url);
                            
                            // Execute the request
                            $search_response = wp_remote_get($request_url, $request_args);
                            
                            if (is_wp_error($search_response)) {

                                // DIAG - Diagnostics - Ver 2.2.7
                              // back_trace( 'ERROR', 'Search request failed: ' . $search_response->get_error_message());
                                $tool_outputs[] = [
                                    'tool_call_id' => $tool_call['id'],
                                    'output' => json_encode(['error' => 'Search request failed', 'message' => $search_response->get_error_message()])
                                ];
                                continue;

                            }
                            
                            $search_body = wp_remote_retrieve_body($search_response);
                            $search_results = json_decode($search_body, true);
                            
                            if (json_last_error() !== JSON_ERROR_NONE) {

                                // DIAG - Diagnostics - Ver 2.2.7
                                // back_trace( 'ERROR', 'Failed to parse search results: ' . json_last_error_msg());

                                $tool_outputs[] = [
                                    'tool_call_id' => $tool_call['id'],
                                    'output' => json_encode(['error' => 'Failed to parse search results'])
                                ];
                                continue;

                            }

                            // DIAG - Diagnostics - Ver 2.2.7
                            // back_trace( 'NOTICE', 'Search returned ' . (isset($search_results['total_posts']) ? $search_results['total_posts'] : '0') . ' results');
                            
                            // Add this tool output to our collection
                            $tool_outputs[] = [
                                'tool_call_id' => $tool_call['id'],
                                'output' => json_encode($search_results)
                            ];
                            
                        } catch (Exception $e) {

                            // DIAG - Diagnostics - Ver 2.2.7
                            // back_trace( 'ERROR', 'Exception processing tool call: ' . $e->getMessage());
                            $tool_outputs[] = [
                                'tool_call_id' => $tool_call['id'],
                                'output' => json_encode(['error' => 'Exception processing tool call', 'message' => $e->getMessage()])
                            ];

                        }

                    } else {

                        // DIAG - Diagnostics - Ver 2.2.7
                        // back_trace( 'NOTICE', 'Unknown tool type: ' . ($tool_call['function']['name'] ?? 'undefined'));

                        $tool_outputs[] = [
                            'tool_call_id' => $tool_call['id'],
                            'output' => json_encode(['error' => 'Unknown tool type'])
                        ];
                    }

                }
                
                // Submit all tool outputs at once
                if (!empty($tool_outputs)) {
                    $submit_url = get_threads_api_url() . '/' . $thread_id . '/runs/' . $run_id . '/submit_tool_outputs';
                    $submit_data = [
                        'tool_outputs' => $tool_outputs
                    ];
                    
                    // DIAG - Diagnostics - Ver 2.2.7
                    // back_trace( 'NOTICE', 'Submitting tool outputs to: ' . $submit_url);
                    // back_trace( 'NOTICE', 'Tool outputs: ' . json_encode($submit_data));
                    
                    $submit_response = wp_remote_post($submit_url, [
                        'headers' => $headers,
                        'body' => json_encode($submit_data),
                        'timeout' => 30,
                    ]);
                    
                    if (is_wp_error($submit_response)) {

                        // DIAG - Diagnostics - Ver 2.2.7
                        // back_trace( 'ERROR', 'Failed to submit tool outputs: ' . $submit_response->get_error_message());
                        return false;

                    }
                    
                    $submit_body = wp_remote_retrieve_body($submit_response);
                    $submit_data = json_decode($submit_body, true);
                    $submit_status = wp_remote_retrieve_response_code($submit_response);
                    
                    // DIAG - Diagnostics - Ver 2.2.7
                    // back_trace( 'NOTICE', 'Tool output submission status: ' . $submit_status);
                    // back_trace( 'NOTICE', 'Tool output submission response: ' . print_r($submit_data, true));
                    
                    if ($submit_status >= 400) {

                        // DIAG - Diagnostics - Ver 2.2.7
                      // back_trace( 'ERROR', 'Tool output submission error: ' . $submit_body);
                        return false;

                    } else {

                        // DIAG - Diagnostics - Ver 2.2.7
                        // back_trace( 'NOTICE', 'Tool output submitted successfully');
                        return true;

                    }
                }
            }
        }

        // DIAG - Diagnostics - Ver 2.2.7
        // back_trace( 'NOTICE', 'No action required for this run');
        return false;

    } catch (Exception $e) {

        // DIAG - Diagnostics - Ver 2.2.7
        // back_trace( 'ERROR', 'Exception caught: ' . $e->getMessage());
        // back_trace( 'ERROR', 'Stack trace: ' . $e->getTraceAsString());
        return false;
        
    }

}