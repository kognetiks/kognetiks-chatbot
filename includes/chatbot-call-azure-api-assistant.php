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

    // Base URL for the beta threads endpoints
    // $url = "https://<resource>.openai.azure.com/openai/threads?api-version=2024-03-01-preview";
    $url = get_threads_api_url();

    $chatbot_azure_resource_name = esc_attr(get_option('chatbot_azure_resource_name', 'YOUR_RESOURCE_NAME'));
    $chatbot_azure_deployment_name = esc_attr(get_option('chatbot_azure_deployment_name', 'DEPLOYMENT_NAME'));
    $chatbot_azure_api_version = esc_attr(get_option('chatbot_azure_api_version', 'YYYY-MM-DD'));
    // Assemble the URL
    // $url = 'https://RESOURCE_NAME_GOES_HERE.openai.azure.com/openai/threads?api-version=2024-03-01-preview';
    $url = 'https://' . $chatbot_azure_resource_name . '.openai.azure.com/openai/threads?api-version=' . $chatbot_azure_api_version;

    // DIAG - Diagnostics - Ver 2.2.6
    back_trace( 'NOTICE', '$url: ' . $url);

    $assistant_beta_version = esc_attr(get_option('chatbot_chatgpt_assistant_beta_version', 'v2'));
    if ( $assistant_beta_version == 'v2' ) {
        $beta_version = "assistants=v2";
    } else {
        $beta_version = "assistants=v1";
    }

    // Prepare common headers
    // $headers = [
    //     "Content-Type"  => "application/json",
    //     "OpenAI-Beta"   => $beta_version,
    //     "Authorization" => "Bearer " . $api_key,
    // ];

    $headers = array(
        'Content-Type' => 'application/json',
        'api-key'      => trim($api_key),
        // 'Accept'       => 'application/json',
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
        back_trace( 'ERROR', 'OpenAI API Error: ' . json_encode($thread_response['error'], JSON_PRETTY_PRINT));
        return "Error: " . $thread_response['error']['message'];
    }

    // Ensure thread ID is present
    if (!isset($thread_response["id"])) {
        back_trace( 'ERROR', 'Thread ID Missing in Response: ' . print_r($thread_response, true));
        return "Error: Thread ID not returned.";
    }

    // DIAG - Diagnostics - Ver 2.2.6
    back_trace( 'NOTICE', 'Step 1 - $thread_response["id"]: ' . $thread_response["id"]);

    return $thread_response;

}

// -------------------------------------------------------------------------
// Step 2: EMPTY STEP
// -------------------------------------------------------------------------

// -------------------------------------------------------------------------
// Step 3: Add a message
// -------------------------------------------------------------------------
function add_an_azure_message($thread_id, $prompt, $context, $api_key, $file_id = null) {

    // DIAG - Diagnostics - Ver 2.2.6
    // back_trace( 'NOTICE', 'Step 3 - add_an_azure_message()');
    // back_trace( 'NOTICE', '$thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', '$prompt: ' . $prompt);
    // back_trace( 'NOTICE', '$context: ' . $context);
    // back_trace( 'NOTICE', '$file_id: ' . print_r($file_id, true));

    global $session_id;

    // Set your API key and assistant ID here:
    $api_key = esc_attr(get_option('chatbot_cazure_api_key', ''));

    // Base URL for the beta threads endpoints
    // $url = "https://<resource>.openai.azure.com/openai/threads?api-version=2024-03-01-preview";

    $chatbot_azure_resource_name = esc_attr(get_option('chatbot_azure_resource_name', 'YOUR_RESOURCE_NAME'));
    $chatbot_azure_deployment_name = esc_attr(get_option('chatbot_azure_deployment_name', 'DEPLOYMENT_NAME'));
    $chatbot_azure_api_version = esc_attr(get_option('chatbot_azure_api_version', 'YYYY-MM-DD'));
    // Assemble the URL
    // $url = 'https://RESOURCE_NAME_GOES_HERE.openai.azure.com/openai/threads/' . $thread_id . '/messages?api-version=2024-03-01-preview';
    $url = 'https://' . $chatbot_azure_resource_name . '.openai.azure.com/openai/threads/' . $thread_id . '/messages?api-version=' . $chatbot_azure_api_version;

    // DIAG - Diagnostics - Ver 2.2.3
    back_trace( 'NOTICE', '$url: ' . $url);

    $assistant_beta_version = esc_attr(get_option('chatbot_chatgpt_assistant_beta_version', 'v2'));
    if ( $assistant_beta_version == 'v2' ) {
        $beta_version = "assistants=v2";
    } else {
        $beta_version = "assistants=v1";
    }

    // Prepare common headers
    // $headers = [
    //     "Content-Type"  => "application/json",
    //     "OpenAI-Beta"   => $beta_version,
    //     "Authorization" => "Bearer " . $api_key,
    // ];

    $headers = array(
        'Content-Type' => 'application/json',
        'api-key'      => trim($api_key),
        // 'Accept'       => 'application/json',
    );

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

    }

    if ( !empty($file_id) ) {

        // *********************************************************************************
        // Decide which helper to use
        // *********************************************************************************

        // FIXME - Retrieve the first item file type - assumes they are all the same, not mixed
        $file_type = get_chatbot_chatgpt_transients_files('chatbot_chatgpt_assistant_file_type', $session_id, $file_id[0]);
        $file_type = $file_type ? $file_type : 'unknown';
    
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
    back_trace( 'NOTICE', 'add_an_azure_message() - $response_body: ' . print_r($response_body, true));
    
    // Return the API response
    return json_decode($response_body, true);

}

// -------------------------------------------------------------------------
// Step 4: Run the Assistant
// -------------------------------------------------------------------------
function run_an_azure_assistant($thread_id, $assistant_id, $context, $api_key) {

    // DIAG - Diagnostics - Ver 2.2.6
    back_trace( 'NOTICE', 'Step 4 - run_an_azure_assistant()');
    back_trace( 'NOTICE', 'Step 4 - $thread_id: ' . $thread_id);

    global $kchat_settings;
    
    // $url = "https://<resource>.openai.azure.com/openai/threads/" . $thread_id . "/runs?api-version=2024-03-01-preview";
    $url = get_threads_api_url() . '/' . $thread_id . '/runs';

    $chatbot_azure_resource_name = esc_attr(get_option('chatbot_azure_resource_name', 'YOUR_RESOURCE_NAME'));
    $chatbot_azure_deployment_name = esc_attr(get_option('chatbot_azure_deployment_name', 'DEPLOYMENT_NAME'));
    $chatbot_azure_api_version = esc_attr(get_option('chatbot_azure_api_version', 'YYYY-MM-DD'));
    // Assemble the URL
    // $url = 'https://RESOURCE_NAME_GOES_HERE.openai.azure.com/openai/threads?' . $thread_id . '/runs?api-version=2024-03-01-preview';
    $url = 'https://' . $chatbot_azure_resource_name . '.openai.azure.com/openai/threads/' . $thread_id . '/runs?api-version=' . $chatbot_azure_api_version;

    // DIAG - Diagnostics - Ver 2.2.6
    back_trace( 'NOTICE', '$url: ' . $url);

    $assistant_beta_version = esc_attr(get_option('chatbot_chatgpt_assistant_beta_version', 'v2'));
    if ( $assistant_beta_version == 'v2' ) {
        $beta_version = "assistants=v2";
    } else {
        $beta_version = "assistants=v1";
    }

    // Prepare common headers
    // $headers = [
    //     "Content-Type"  => "application/json",
    //     "OpenAI-Beta"   => $beta_version,
    //     "Authorization" => "Bearer " . $api_key,
    // ];

    $headers = array(
        'Content-Type' => 'application/json',
        'api-key'      => trim($api_key),
        // 'Accept'       => 'application/json',
    );

    // Get the max prompt and completion tokens - Ver 2.0.1
    // https://platform.openai.com/docs/assistants/how-it-works/max-completion-and-max-prompt-tokens
    
    // Request body additional features - Ver 2.2.3
    // https://platform.openai.com/docs/api-reference/runs/createRun

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
    back_trace( 'NOTICE', 'Step 4 - Decoded Response: ' . print_r($response_data, true));
    
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
    
        return "Error: {$errorMessage}";
    }
    
    // ✅ If no errors, return the decoded response
    return $response_data;    

}

// -------------------------------------------------------------------------
// Step 5: Get the Run's Status
// -------------------------------------------------------------------------
function OLD_run_the_azure_run_status($thread_id, $runId, $api_key) {

    // DIAG - Diagnostics - Ver 2.2.6
    back_trace( 'NOTICE', 'Step 5: run_the_azure_run_status()');

    global $sleepTime;

    // Examle https://<resource>.openai.azure.com/openai/threads/<thread_id>/runs/<run_id>
    // Build the API URL
    $url = get_threads_api_url() . '/' . $thread_id . '/runs/' . $runId;

    $chatbot_azure_resource_name = esc_attr(get_option('chatbot_azure_resource_name', 'YOUR_RESOURCE_NAME'));
    $chatbot_azure_deployment_name = esc_attr(get_option('chatbot_azure_deployment_name', 'DEPLOYMENT_NAME'));
    $chatbot_azure_api_version = esc_attr(get_option('chatbot_azure_api_version', 'YYYY-MM-DD'));
    // Assemble the URL
    // $url = 'https://RESOURCE_NAME_GOES_HERE.openai.azure.com/openai' . $thread_id . '/runs/' . $runId . '?api-version=2024-03-01-preview';
    $url = 'https://' . $chatbot_azure_resource_name . '.openai.azure.com/openai/' . $thread_id . '/runs/' . $runId . '?api-version=' . $chatbot_azure_api_version;

    // DIAG - Diagnostics - Ver 2.2.6
    back_trace( 'NOTICE', '$url: ' . $url);

    $assistant_beta_version = esc_attr(get_option('chatbot_chatgpt_assistant_beta_version', 'v2'));
    if ( $assistant_beta_version == 'v2' ) {
        $beta_version = "assistants=v2";
    } else {
        $beta_version = "assistants=v1";
    }

    // Prepare common headers
    // $headers = [
    //     "Content-Type"  => "application/json",
    //     "OpenAI-Beta"   => $beta_version,
    //     "Authorization" => "Bearer " . $api_key,
    // ];

    $headers = array(
        'Content-Type' => 'application/json',
        'api-key'      => trim($api_key),
        // 'Accept'       => 'application/json',
    );

    $status = "";

    // Exponential backoff parameters - Ver 2.2.6
    $initialSleep = 500000;        // Initial sleep time in microseconds (0.5 seconds)
    $maxSleep = 20000000;          // Maximum sleep time in microseconds (20 seconds)
    $sleepTime = $initialSleep;
    $retryCount = 0;
    $maxRetriesBeforeReset = 5;    // Number of retries before resetting the sleep time
    $resetRangeMin = 500000;       // Minimum reset sleep time in microseconds (0.5 seconds)
    $resetRangeMax = 2000000;      // Maximum reset sleep time in microseconds (2 seconds)
    $maxTotalRetries = 50;         // Maximum total retries to prevent infinite loops
    $totalRetryCount = 0;          // Total retry counter

    while ($status != "completed" && $totalRetryCount < $maxTotalRetries) {

        $response = wp_remote_post($url, [
            "headers"       => $headers,
            "timeout"       => 30,
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

function run_the_azure_run_status($thread_id, $runId, $api_key) {

    // DIAG - Diagnostics - Ver 2.2.6
    back_trace( 'NOTICE', 'Step 5: run_the_azure_run_status()');

    // Examle https://<resource>.openai.azure.com/openai/threads/<thread_id>/runs/<run_id>?api-version=2024-03-01-preview
    // Build the API URL
    $url = get_threads_api_url() . '/' . $thread_id . '/runs/' . $runId;

    $chatbot_azure_resource_name = esc_attr(get_option('chatbot_azure_resource_name', 'YOUR_RESOURCE_NAME'));
    $chatbot_azure_deployment_name = esc_attr(get_option('chatbot_azure_deployment_name', 'DEPLOYMENT_NAME'));
    $chatbot_azure_api_version = esc_attr(get_option('chatbot_azure_api_version', 'YYYY-MM-DD'));
    // Assemble the URL
    // $url = 'https://RESOURCE_NAME_GOES_HERE.openai.azure.com/openai' . $thread_id . '/runs/' . $runId . '?api-version=2024-03-01-preview';
    $url = 'https://' . $chatbot_azure_resource_name . '.openai.azure.com/openai/' . $thread_id . '/runs/' . $runId . '?api-version=' . $chatbot_azure_api_version;

    // DIAG - Diagnostics - Ver 2.2.6
    back_trace( 'NOTICE', '$url: ' . $url);

    // Headers
    $headers = [
        'Content-Type' => 'application/json',
        'api-key'      => trim($api_key),
    ];

    // ✅ **Make a single GET request to check status**
    $response = wp_remote_get($url, ["headers" => $headers, "timeout" => 10]);

    if (is_wp_error($response)) {
        prod_trace('ERROR', 'HTTP Request failed: ' . $response->get_error_message());
        return json_encode(["error" => "API request failed"]);
    }

    $response_body = wp_remote_retrieve_body($response);
    $responseArray = json_decode($response_body, true);

    // ✅ **Return the response immediately**
    return json_encode($responseArray);
}


// -------------------------------------------------------------------------
// Step 6: Get the Run's Steps
// -------------------------------------------------------------------------
function get_the_azure_run_steps($thread_id, $runId, $api_key) {

    // DIAG - Diagnostics - Ver 2.2.3
    // back_trace( 'NOTICE', 'Step 6 - get_the_azure_run_steps()');
    // back_trace( 'NOTICE', 'Step 6 - $thread_id: ' . $thread_id);

    // Example https://<resource>.openai.azure.com/openai/threads/<thread_id>/runs/<run_id>/steps?api-version=2024-03-01-preview
    // Construct the API URL
    $url = get_threads_api_url() . '/' . $thread_id . '/runs/' . $runId . '/steps';

    $chatbot_azure_resource_name = esc_attr(get_option('chatbot_azure_resource_name', 'YOUR_RESOURCE_NAME'));
    $chatbot_azure_deployment_name = esc_attr(get_option('chatbot_azure_deployment_name', 'DEPLOYMENT_NAME'));
    $chatbot_azure_api_version = esc_attr(get_option('chatbot_azure_api_version', 'YYYY-MM-DD'));
    // Assemble the URL
    // $url = 'https://RESOURCE_NAME_GOES_HERE.openai.azure.com/openai' . $thread_id . '/runs/' . $runId . '/steps?api-version=2024-03-01-preview';
    $url = 'https://' . $chatbot_azure_resource_name . '.openai.azure.com/openai/threads/' . $thread_id . '/runs/' . $runId . '/steps?api-version=' . $chatbot_azure_api_version;

    // DIAG - Diagnostics - Ver 2.2.6
    back_trace( 'NOTICE', '$url: ' . $url);

    // Determine API version
    $assistant_beta_version = esc_attr(get_option('chatbot_chatgpt_assistant_beta_version', 'v2'));
    if ( $assistant_beta_version == 'v2' ) {
        $beta_version = "assistants=v2";
    } else {
        $beta_version = "assistants=v1";
    }

    // Prepare request headers
    // $headers = [
    //     "Content-Type"  => "application/json",
    //     "OpenAI-Beta"   => $beta_version,
    //     "Authorization" => "Bearer " . $api_key
    // ];

    $headers = array(
        'Content-Type' => 'application/json',
        'api-key'      => trim($api_key),
        // 'Accept'       => 'application/json',
    );

    // 🚀 ✅ FIX: Change from `wp_remote_post()` to `wp_remote_get()`
    $response = wp_remote_get($url, [
        "headers"       => $headers,
        "timeout"       => 30,
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
function get_the_azure_steps_status($thread_id, $runId, $api_key) {

    // DIAG - Diagnostics - Ver 2.2.6
    back_trace( 'NOTICE', 'Step 7 - get_the_azure_steps_status()');
    back_trace( 'NOTICE', 'Step 7 - $thread_id: ' . $thread_id);

    // $url = "https://api.openai.com/v1/threads/" . $thread_id . "/runs/" . $runId . "/steps";
    $url = get_threads_api_url() . '/' . $thread_id . '/runs/' . $runId . '/steps';

    $chatbot_azure_resource_name = esc_attr(get_option('chatbot_azure_resource_name', 'YOUR_RESOURCE_NAME'));
    $chatbot_azure_deployment_name = esc_attr(get_option('chatbot_azure_deployment_name', 'DEPLOYMENT_NAME'));
    $chatbot_azure_api_version = esc_attr(get_option('chatbot_azure_api_version', 'YYYY-MM-DD'));
    // Assemble the URL
    // $url = 'https://RESOURCE_NAME_GOES_HERE.openai.azure.com/openai' . $thread_id . '/runs/' . $runId . '/steps?api-version=2024-03-01-preview';
    $url = 'https://' . $chatbot_azure_resource_name . '.openai.azure.com/openai/threads/' . $thread_id . '/runs/' . $runId . '/steps?api-version=' . $chatbot_azure_api_version;

    // DIAG - Diagnostics - Ver 2.2.6
    back_trace( 'NOTICE', '$url: ' . $url);

    $assistant_beta_version = esc_attr(get_option('chatbot_chatgpt_assistant_beta_version', 'v2'));
    if ( $assistant_beta_version == 'v2' ) {
        $beta_version = "assistants=v2";
    } else {
        $beta_version = "assistants=v1";
    }
    // DIAG - Diagnostics - Ver 1.9.6
    // back_trace( 'NOTICE', '$beta_version: ' . $beta_version);

    // Prepare request headers
    // $headers = [
    //     "Content-Type"  => "application/json",
    //     "OpenAI-Beta"   => $beta_version,
    //     "Authorization" => "Bearer " . $api_key
    // ];
    
    $headers = array(
        'Content-Type' => 'application/json',
        'api-key'      => trim($api_key),
        // 'Accept'       => 'application/json',
    );

    // Retry settings
    $max_retries = 30; // Max retries before giving up
    $retry_count = 0;
    $sleep_time = 500000; // 0.5 seconds

    while ($retry_count < $max_retries) {

        // 🚀 ✅ FIX: Changed from `wp_remote_post()` to `wp_remote_get()`
        $response = wp_remote_get($url, [
            "headers"       => $headers,
            "timeout"       => 30,
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

        // Sleep before retrying
        usleep($sleep_time);
        $retry_count++;
    }

    // ✅ Log and return failure if retries exceeded
    prod_trace('ERROR', 'Error - GPT Assistant - Step 7: Maximum retries reached.');
    return "Error: Maximum retries reached.";

}

// -------------------------------------------------------------------------
// Step 8: Get the Message
// -------------------------------------------------------------------------
function get_the_azure_message($thread_id, $api_key) {

    // DIAG - Diagnostics - Ver 2.2.6
    // back_trace( 'NOTICE', 'Step 8 - get_the_azure_message()');
    back_trace( 'NOTICE', 'Step 8 - $thread_id: ' . $thread_id);

    // Example https://<resource>.openai.azure.com/openai/threads/<thread_id>/messages
    // Build the URL
    $url = get_threads_api_url() . '/' . $thread_id . '/messages';

    $chatbot_azure_resource_name = esc_attr(get_option('chatbot_azure_resource_name', 'YOUR_RESOURCE_NAME'));
    $chatbot_azure_deployment_name = esc_attr(get_option('chatbot_azure_deployment_name', 'DEPLOYMENT_NAME'));
    $chatbot_azure_api_version = esc_attr(get_option('chatbot_azure_api_version', 'YYYY-MM-DD'));
    // Assemble the URL
    // $url = 'https://RESOURCE_NAME_GOES_HERE.openai.azure.com/openai' . $thread_id . '/messages?api-version=2024-03-01-preview';
    $url = 'https://' . $chatbot_azure_resource_name . '.openai.azure.com/openai/threads/' . $thread_id . '/messages?api-version=' . $chatbot_azure_api_version;

    // DIAG - Diagnostics - Ver 2.2.6
    back_trace( 'NOTICE', '$url: ' . $url);

    $assistant_beta_version = esc_attr(get_option('chatbot_chatgpt_assistant_beta_version', 'v2'));
    if ( $assistant_beta_version == 'v2' ) {
        $beta_version = "assistants=v2";
    } else {
        $beta_version = "assistants=v1";
    }
    // DIAG - Diagnostics - Ver 1.9.6
    // back_trace( 'NOTICE', '$beta_version: ' . $beta_version);

    // $headers = [
    //     'Content-Type'  => 'application/json',
    //     'OpenAI-Beta'   => $beta_version,
    //     'Authorization' => 'Bearer ' . $api_key
    // ];
  
    $headers = array(
        'Content-Type' => 'application/json',
        'api-key'      => trim($api_key),
        // 'Accept'       => 'application/json',
    );

    // Fetch the response
    $response = wp_remote_get($url, [
        "headers" => $headers,
        "timeout" => 30,
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
function chatbot_azure_custom_gpt_call_api($api_key, $message, $assistant_id, $thread_id, $session_id, $user_id, $page_id) {

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
        $api_key = esc_attr(get_option('chatbot_azure_api_key', ''));
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

    // Conversation Context - Ver 2.2.3
    $context = "";
    $context = esc_attr(get_option('chatbot_chatgpt_conversation_context', 'You are a versatile, friendly, and helpful assistant designed to support me in a variety of tasks that responds in Markdown.'));
 
    // Step 3: Add a Message to a Thread
    // back_trace( 'NOTICE', 'Step 3 - Add a Message to a Thread');
    $prompt = $message;
        
    // Fetch the file id - Ver 2.23
    $file_id = chatbot_chatgpt_retrieve_file_id($user_id, $page_id);

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
    // back_trace( 'NOTICE', 'RIGHT BEFORE CALL to add_an_azure_message - $thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', 'RIGHT BEFORE CALL to add_an_azure_message - $prompt: ' . $prompt);
    // back_trace( 'NOTICE', 'RIGHT BEFORE CALL to add_an_azure_message - $content: ' . $context);
    // back_trace( 'NOTICE', 'chatbot_chatgpt_retrieve_file_id(): ' . print_r($file_id, true));

    // ENHANCED CONTEXT - Select some context to send with the message - Ver 2.2.4
    $use_enhanced_content_search = esc_attr(get_option('chatbot_chatgpt_use_advanced_content_search', 'No'));

    if ($use_enhanced_content_search == 'Yes') {

        $search_results = ' When answering the prompt, please consider the following information: ' . chatbot_chatgpt_content_search($message);
        If ( !empty ($search_results) ) {
            // Append the transformer context to the prompt
            $prompt = $prompt . ' ' . $search_results;
        }
        // DIAG Diagnostics - Ver 2.2.4 - 2025-02-04
        // back_trace( 'NOTICE', '$prompt: ' . $prompt);

    }

    if (empty($file_id)) {
        // back_trace( 'NOTICE', 'No file to retrieve');
        $assistants_response = add_an_azure_message($thread_id, $prompt, $context, $api_key, '');
    } else {
        //DIAG - Diagnostics - Ver 1.7.9
        // back_trace( 'NOTICE', 'File to retrieve');
        // back_trace( 'NOTICE', '$file_id ' . print_r($file_id, true));
        $assistants_response = add_an_azure_message($thread_id, $prompt, $context, $api_key, $file_id);
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
        $assistants_response = run_an_azure_assistant($thread_id, $assistant_id, $context, $api_key);

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
        $run_status = run_the_azure_run_status($thread_id, $runId, $api_key);

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
        return "Error: Step 5 - " . $run_status;
    }

    // Step 6: Get the Run's Steps
    // back_trace( 'NOTICE', 'Step 6 - Get the Run\'s Steps');
    $assistants_response = get_the_azure_run_steps($thread_id, $runId, $api_key);
    // DIAG - Print the response
    // back_trace( 'NOTICE', $assistants_response);

    // DIAG - Diagnostics - Ver 2.2.3
    // back_trace( 'NOTICE', 'Usage - Prompt Tokens: ' . $assistants_response["data"][0]["usage"]["prompt_tokens"]);
    // back_trace( 'NOTICE', 'Usage - Completion Tokens: ' . $assistants_response["data"][0]["usage"]["completion_tokens"]);
    // back_trace( 'NOTICE', 'Usage - Total Tokens: ' . $assistants_response["data"][0]["usage"]["total_tokens"]);

    // Add the usage to the conversation tracker
    append_message_to_conversation_log($session_id, $user_id, $page_id, 'Prompt Tokens', $thread_id, $assistant_id, $assistants_response["data"][0]["usage"]["prompt_tokens"]);
    append_message_to_conversation_log($session_id, $user_id, $page_id, 'Completion Tokens', $thread_id, $assistant_id, $assistants_response["data"][0]["usage"]["completion_tokens"]);
    append_message_to_conversation_log($session_id, $user_id, $page_id, 'Total Tokens', $thread_id, $assistant_id, $assistants_response["data"][0]["usage"]["total_tokens"]);

    // Step 7: Get the Step's Status
    // back_trace( 'NOTICE', 'Step 7 - Get the Step\'s Status');
    get_the_azure_steps_status($thread_id, $runId, $api_key);

    // Step 8: Get the Message
    // back_trace( 'NOTICE', 'Step 8: Get the Message');
    $assistants_response = get_the_azure_message($thread_id, $api_key);

    // Interaction Tracking - Ver 1.6.3
    update_interaction_tracking();

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

    // Return the response text, checking for the fallback content[1][text] if available
    if (isset($assistants_response["data"][0]["content"][1]["text"]["value"])) {
        return $assistants_response["data"][0]["content"][1]["text"]["value"];
    } elseif (isset($assistants_response["data"][0]["content"][0]["text"]["value"])) {
        return $assistants_response["data"][0]["content"][0]["text"]["value"];
    } else {
        // Return a default value or an empty string if none exist
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
    // back_trace( 'NOTICE', 'chatbot_chatgpt_retrieve_file_id(): ' . print_r($file_id, true));
    // back_trace( 'NOTICE', 'chatbot_chatgpt_retrieve_file_id(): ' . print_r($file_types, true));

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
// Cleanup in Aisle 4 on OpenAI - Ver 2.2.3
// -------------------------------------------------------------------------
function delete_azure_uploaded_file($file_id) {

    // DIAG - Diagnostics - Ver 2.2.3
    // back_trace( 'NOTICE', 'delete_azure_uploaded_file(): ' . $file_id);

    // Get the API key
    $apiKey = esc_attr(get_option('chatbot_chatgpt_api_key'));

    // Construct the API URL
    // $url = 'https://api.openai.com/v1/files/' . $file_id;
    $url = get_files_api_url() . '/' . $file_id;

    $chatbot_azure_resource_name = esc_attr(get_option('chatbot_azure_resource_name', 'YOUR_RESOURCE_NAME'));
    $chatbot_azure_deployment_name = esc_attr(get_option('chatbot_azure_deployment_name', 'DEPLOYMENT_NAME'));
    $chatbot_azure_api_version = esc_attr(get_option('chatbot_azure_api_version', 'YYYY-MM-DD'));
    // Assemble the URL
    // $url = 'https://RESOURCE_NAME_GOES_HERE.openai.azure.com/openai/' . $file_id . '?api-version=2024-03-01-preview';
    $url = 'https://' . $chatbot_azure_resource_name . '.openai.azure.com/openai/' . $file_id . '?api-version=' . $chatbot_azure_api_version;

    // DIAG - Diagnostics - Ver 2.2.6
    back_trace( 'NOTICE', '$url: ' . $url);

        $headers = array(
        'Content-Type' => 'application/json',
        'api-key'      => trim($api_key),
        // 'Accept'       => 'application/json',
    );

    $headers = array(
        'Content-Type' => 'application/json',
        'api-key'      => trim($api_key),
        // 'Accept'       => 'application/json',
    );
    
    // Send DELETE request using WP functions
    $response = wp_remote_request($url, [
        'method'    => 'DELETE',
        'timeout'   => 15,
        // 'headers'   => [
        //     'Authorization'  => 'Bearer ' . $apiKey,
        //     'Content-Type'   => 'application/json',
        // ]
        'headers'   => $headers
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
        prod_trace( 'ERROR', "HTTP status code: $http_status_code\n");
        prod_trace( 'ERROR', "Response: $response\n");
    }

}
add_action( 'delete_azure_uploaded_file', 'delete_azure_uploaded_file' );
