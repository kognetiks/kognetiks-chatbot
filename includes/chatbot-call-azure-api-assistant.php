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

    $thread_response = json_decode($body, true);

    // Handle API errors
    if (isset($thread_response['error'])) {
        return "Error: " . $thread_response['error']['message'];
    }

    // Ensure thread ID is present
    if (!isset($thread_response["id"])) {
        return "Error: Thread ID not returned.";
    }

    return $thread_response;

}

// -------------------------------------------------------------------------
// Step 2: EMPTY STEP
// -------------------------------------------------------------------------

// -------------------------------------------------------------------------
// Step 3: Add a message
// -------------------------------------------------------------------------
function add_an_azure_message($thread_id, $prompt, $context, $api_key, $file_id = null, $message_uuid = null) {

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

    // Prepare common headers
    $headers = array(
        'Content-Type' => 'application/json',
        'api-key'      => trim($api_key),
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
    
    // Return the API response
    return json_decode($response_body, true);

}

// -------------------------------------------------------------------------
// Step 4: Run the Assistant
// -------------------------------------------------------------------------
function run_an_azure_assistant($thread_id, $assistant_id, $context, $api_key, $message_uuid = null) {

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
    }

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

    return $response_data;

}

// -------------------------------------------------------------------------
// Step 7: Get the Step's Status
// -------------------------------------------------------------------------
function get_the_azure_steps_status($thread_id, $runId, $api_key, $session_id, $user_id, $page_id, $assistant_id) {

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

        // Updated check for "data" field
        if (isset($responseArray["data"][0]) && isset($responseArray["data"][0]["status"])) {
            if ($responseArray["data"][0]["status"] === "completed") {
                if (isset($responseArray["data"][0]["usage"])) {
                    $prompt_tokens = $responseArray["data"][0]["usage"]["prompt_tokens"] ?? 0;
                    $completion_tokens = $responseArray["data"][0]["usage"]["completion_tokens"] ?? 0;
                    $total_tokens = $responseArray["data"][0]["usage"]["total_tokens"] ?? 0;
                    if ( $total_tokens != 0 ) {
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
                            continue;
                        }

                        // If $path is not defined or not a string, skip this iteration
                        if (!isset($path) || !is_string($path)) {
                            continue;
                        }

                        $basename = basename($path);

                        // Extract the filename
                        $file_name = 'download_' . generate_random_string() . '_' . basename($annotation['text']);

                        // Call the function to download the file
                        $file_url = download_openai_file($file_id, $file_name);

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

                                // Call the function to download the file
                                $file_url = download_openai_file($file_id, $file_name);

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
        // Do nothing
    }

    return $response_data;

}

// CustomGPT - Assistants - Ver 1.7.2
function chatbot_azure_custom_gpt_call_api($api_key, $message, $assistant_id, $thread_id, $session_id, $user_id, $page_id, $client_message_id = null) {

    // Globals added for Ver 1.7.2
    global $learningMessages;
    global $errorResponses;
    global $stopWords;

    // DIAG - Diagnostics - Ver 2.4.5
    // back_trace("NOTICE", "Starting Azure AssistantAPI call");
    // back_trace("NOTICE", "Message: " . $message);
    // back_trace("NOTICE", "User ID: " . $user_id);
    // back_trace("NOTICE", "Page ID: " . $page_id);
    // back_trace("NOTICE", "Session ID: " . $session_id);
    // back_trace("NOTICE", "Assistant ID: " . $assistant_id);
    // back_trace("NOTICE", "Client Message ID: " . $client_message_id);
    
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

    // See if there is a $thread_id
    if (empty($thread_id)) {
        $thread_id = get_chatbot_chatgpt_threads($user_id, $session_id, $page_id, $assistant_id);
        if (empty($thread_id)) {
        } else {
        }
    } else {
    }

    // If the thread_id is not set, create a new thread
    if (empty($thread_id)) {

        // Step 1 - Create an Assistant
        $api_key = esc_attr(get_option('chatbot_azure_api_key', ''));
        // Decrypt the API key - Ver 2.2.6
        $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
        $assistants_response = create_an_azure_assistant($api_key);

        // Step 2 - Get The Thread ID
        $thread_id = $assistants_response["id"];
        $kchat_settings['thread_id'] = $thread_id;
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
        $default_message = "The system is currently busy processing requests. Please try again in a few moments.";
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
    $prompt = $message;
        
    // Fetch the file id - Ver 2.23
    $file_id = chatbot_chatgpt_retrieve_file_id($user_id, $page_id);

    for ($i = 0; $i < count($file_id); $i++) {
        if (isset($file_id[$i])) {
        } else {
            // Handle the error appropriately
            unset($file_id[$i]); // Remove the non-existent key
        }
    }

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

    }

    if (empty($file_id)) {
        $assistants_response = add_an_azure_message($thread_id, $prompt, $context, $api_key, '', $message_uuid);
    } else {
        $assistants_response = add_an_azure_message($thread_id, $prompt, $context, $api_key, $file_id, $message_uuid);
    }

    $retries = 0;
    $maxRetries = 5;
    $consecutive_failures = 0; // Track consecutive failures to prevent infinite loops - Ver 2.4.0
    $max_consecutive_failures = 3; // Stop after 3 consecutive failures - Ver 2.4.0
    global $sleepTime; 

    do {

        $run_status = '';

        // Step 4: Run the Assistant
        $assistants_response = run_an_azure_assistant($thread_id, $assistant_id, $context, $api_key, $message_uuid);

        // Check if the response is not an array or is a string indicating an error
        if (!is_array($assistants_response) || is_string($assistants_response)) {
            return "Error: Invalid response format or error occurred.";
        }

        // Check if the 'id' key exists in the response
        if (isset($assistants_response["id"])) {
            $runId = $assistants_response["id"];
        } else {
            return "Error: 'id' key not found in response.";
        }

        // Step 5: Get the Run's Status
        $run_status = get_the_azure_run_status($thread_id, $runId, $api_key);

        $retries++;

        // Check for consecutive failures to prevent infinite loops - Ver 2.4.0
        if ($run_status == "failed") {
            $consecutive_failures++; // Increment consecutive failures
            if ($consecutive_failures >= $max_consecutive_failures) {
                // Clear locks on error
                delete_transient($thread_lock);
                return "Error: Run failed after " . $consecutive_failures . " consecutive failures. Status: " . $run_status;
            }
        } else if ($run_status == "completed") {
            $consecutive_failures = 0; // Reset consecutive failures on success - Ver 2.4.0
        } else {
            // Reset consecutive failures for incomplete or other non-failed statuses
            $consecutive_failures = 0;
        }

        if ($run_status == "failed" || $run_status == "incomplete") {
            // return "Error: Step 5 - " . $run_status;
            usleep($sleepTime);
        }

    } while ($run_status != "completed" && $retries < $maxRetries);

    // Failed after multiple retries
    if ($run_status == "failed" || $run_status == "incomplete") {
        // Clear locks on error
        delete_transient($thread_lock);
        // Lock clearing removed - main send function handles locking
        return "Error: Step 5 - " . $run_status;
    }

    // Step 6: Get the Run's Steps
    $assistants_response = get_the_azure_run_steps($thread_id, $runId, $api_key);
    // Add the usage to the conversation tracker
    // append_message_to_conversation_log($session_id, $user_id, $page_id, 'Prompt Tokens', $thread_id, $assistant_id, null, $assistants_response["data"][0]["usage"]["prompt_tokens"]);
    // append_message_to_conversation_log($session_id, $user_id, $page_id, 'Completion Tokens', $thread_id, $assistant_id, null, $assistants_response["data"][0]["usage"]["completion_tokens"]);
    // append_message_to_conversation_log($session_id, $user_id, $page_id, 'Total Tokens', $thread_id, $assistant_id, null, $assistants_response["data"][0]["usage"]["total_tokens"]);

    // Step 7: Get the Step's Status
    // get_the_azure_steps_status($thread_id, $runId, $api_key, $session_id, $user_id, $page_id, $assistant_id);

    $max_retries = 10; // Max retries before giving up
    $retry_count = 0;
    $sleep_time = 500000; // 0.5 seconds

    while (($retry_count < $max_retries) && (($step_status = get_the_azure_steps_status($thread_id, $runId, $api_key, $session_id, $user_id, $page_id, $assistant_id)) !== "completed")) {
        $retry_count++;
        usleep($sleep_time); 
    }

    if ($retry_count!=0) {
        if ($retry_count==$max_retries) {
            prod_trace('NOTICE', 'Failure after retry ' . $retry_count . ' - get_the_azure_steps_status()'. $step_status);
        } else  {
            prod_trace('NOTICE', 'Done after retry ' . $retry_count . ' - get_the_azure_steps_status()');
        }
    }

    // Step 8: Get the Message
    $assistants_response = get_the_azure_message($thread_id, $api_key, $runId);
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

    // DIAG - Diagnostics - Ver 2.4.5
    prod_trace('ERROR', 'Latest assistant message found at index ' . $latest_index . ' with created_at: ' . $max_created_at);

    // Extract the text from the selected message. 
    if (isset($assistants_response["data"][$latest_index]["content"][1]["text"]["value"])) {
        $latest_response = $assistants_response["data"][$latest_index]["content"][1]["text"]["value"];
        prod_trace('ERROR', 'Extracted response from content[1]: ' . $latest_response);
        return $latest_response;
    } elseif (isset($assistants_response["data"][$latest_index]["content"][0]["text"]["value"])) {
        $latest_response = $assistants_response["data"][$latest_index]["content"][0]["text"]["value"];
        prod_trace('ERROR', 'Extracted response from content[0]: ' . $latest_response);
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
    // return implode(',', $file_ids);

    return $file_ids;

}

// -------------------------------------------------------------------------
// Cleanup in Aisle 4 on OpenAI - Ver 2.2.6
// -------------------------------------------------------------------------
function delete_azure_uploaded_file($file_id) {

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
        return true;
    } else {
        prod_trace('ERROR', 'HTTP status code: ' . $http_status_code );
        prod_trace('ERROR', 'Response: ' . print_r($response, true));        
        return false;
    }
}
add_action('delete_azure_uploaded_file', 'delete_azure_uploaded_file');
