<?php
/**
 * Kognetiks Chatbot for WordPress - Assistants - Ver 1.6.9
 *
 * This file contains the code for access the OpenAI Assistants API.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Step 1: Create an Assistant
function createAnAssistant($api_key) {

    // $url = "https://api.openai.com/v1/threads";
    $url = get_threads_api_url();

    $assistant_beta_version = esc_attr(get_option('chatbot_chatgpt_assistant_beta_version', 'v2'));
    if ( $assistant_beta_version == 'v2' ) {
        $beta_version = "assistants=v2";
    } else {
        $beta_version = "assistants=v1";
    }
    // DIAG - Diagnostics - Ver 1.9.6
    // back_trace( 'NOTICE', '$beta_version: ' . $beta_version);

    $headers = array(
        "Content-Type: application/json",
        "OpenAI-Beta: " . $beta_version,
        "Authorization: Bearer " . $api_key
    );

    $context = stream_context_create(array(
        'http' => array(
            'method' => 'POST',
            'header' => $headers,
            'ignore_errors' => true // This allows the function to proceed even if there's an HTTP error
        )
    ));
    $response = fetchDataUsingCurl($url, $context);

    return json_decode($response, true);
}

// Step 2: EMPTY STEP

// Step 3: Add a Message to a Thread
function addAMessage($thread_id, $prompt, $context, $api_key, $file_id = null) {

    global $session_id;

    // Set the URL
    $url = get_threads_api_url() . '/' . $thread_id . '/messages';

    $assistant_beta_version = esc_attr(get_option('chatbot_chatgpt_assistant_beta_version', 'v2'));
    if ( $assistant_beta_version == 'v2' ) {
        $beta_version = "assistants=v2";
    } else {
        $beta_version = "assistants=v1";
    }
    // DIAG - Diagnostics - Ver 1.9.6
    // back_trace( 'NOTICE', '$beta_version: ' . $beta_version);

    $headers = [
        'Content-Type: application/json',
        'OpenAI-Beta: ' . $beta_version,
        'Authorization: Bearer ' . $api_key
    ];

    // DIAG - Diagnostics - Ver 1.9.3
    // back_trace( 'NOTICE', '========================================');
    // back_trace( 'NOTICE', '$url: ' . $url);
    // back_trace( 'NOTICE', '$headers: ' . ' PRIVATE DATA ');
    // back_trace( 'NOTICE', '$thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', '$prompt: ' . $prompt);
    // back_trace( 'NOTICE', '$context: ' . $context);
    // back_trace( 'NOTICE', '$file_id: ' . print_r($file_id, true));

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
        // back_trace('NOTICE', '$file_type: ' . $file_type);

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

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', '========================================');
    // back_trace( 'NOTICE', '$file_id: ' . gettype($file_id));
    // back_trace( 'NOTICE', '$file_id: ' . gettype([$file_id]));
    // back_trace( 'NOTICE', '$file_id: ' . print_r([$file_id], true));
    // back_trace('NOTICE', 'addAMessage() - $data: ' . print_r($data, true));

    // Initialize cURL session
    $ch = curl_init();

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    // Execute cURL session
    $response = curl_exec($ch);

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', 'addAMessage() - $response: ' . print_r($response, true));

    // Check for cURL errors
    if (curl_errno($ch)) {
        // DIAG - Diagnostics
        // back_trace( 'ERROR', 'Curl error: ' . curl_error($ch));
        curl_close($ch);
        return null;
    }

    // Close cURL session
    curl_close($ch);

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', 'addAMessage() - $response: ' . print_r($response, true));
    
    // Return the API response
    return json_decode($response, true);

}

// Step 4: Run the Assistant
function runTheAssistant($thread_id, $assistant_id, $context, $api_key) {
    
    // $url = "https://api.openai.com/v1/threads/" . $thread_id . "/runs";
    $url = get_threads_api_url() . '/' . $thread_id . '/runs';

    $assistant_beta_version = esc_attr(get_option('chatbot_chatgpt_assistant_beta_version', 'v2'));
    if ( $assistant_beta_version == 'v2' ) {
        $beta_version = "assistants=v2";
    } else {
        $beta_version = "assistants=v1";
    }
    // DIAG - Diagnostics - Ver 1.9.6
    // back_trace( 'NOTICE', '$beta_version: ' . $beta_version);

    // Get the max prompt and completion tokens - Ver 2.0.1
    // https://platform.openai.com/docs/assistants/how-it-works/max-completion-and-max-prompt-tokens
    $max_prompt_tokens = (int) esc_attr(get_option('chatbot_chatgpt_max_prompt_tokens', 20000));
    $max_completion_tokens = (int) esc_attr(get_option('chatbot_chatgpt_max_completion_tokens', 20000));
    $temperature = (float) esc_attr(get_option('chatbot_chatgpt_temperature', 1.0));
    $top_p = (float) esc_attr(get_option('chatbot_chatgpt_top_p', 1.0));

    // DIAG - Diagnostics - Ver 2.0.1
    // back_trace( 'NOTICE', '$max_prompt_tokens: ' . $max_prompt_tokens);

    $headers = array(
        "Content-Type: application/json",
        "OpenAI-Beta: " . $beta_version,
        "Authorization: Bearer " . $api_key
    );
    $data = array(
        "assistant_id" => $assistant_id,
        "max_prompt_tokens" => $max_prompt_tokens,
        "max_completion_tokens" => $max_completion_tokens,
        "temperature" => $temperature,
        "top_p" => $top_p,
    );

    $context = stream_context_create(array(
        'http' => array(
            'method' => 'POST',
            'header' => $headers,
            'content' => json_encode($data),
            'ignore_errors' => true // This allows the function to proceed even if there's an HTTP error
        )
    ));

    $response = fetchDataUsingCurl($url, $context);

    // Check for false response
    if ($response === FALSE) {
        // DIAG - Diagnostics
        // back_trace( 'ERROR', 'Error unable to fetch response');
        return "Error: Unable to fetch response.";
    }

    // Check HTTP response code
    if (http_response_code() != 200) {
        // DIAG - Diagnostics
        // back_trace( 'ERROR', 'HTTP response code: ' . print_r(http_response_code()));
        // return "Error: HTTP response code " . http_response_code();
    }

    // DIAG - Diagnostics  Ver 2.0.1
    // back_trace( 'NOTICE', '$response: ' . print_r($response, true));

    return json_decode($response, true);
}

// Step 5: Get the Run's Status
function getTheRunsStatus($thread_id, $runId, $api_key) {

    $status = "";

    while ($status != "completed") {
    
        // $url = "https://api.openai.com/v1/threads/" . $thread_id . "/runs/".$runId;
        $url = get_threads_api_url() . '/' . $thread_id . '/runs/' . $runId;

        $assistant_beta_version = esc_attr(get_option('chatbot_chatgpt_assistant_beta_version', 'v2'));
        if ( $assistant_beta_version == 'v2' ) {
            $beta_version = "assistants=v2";
        } else {
            $beta_version = "assistants=v1";
        }
        // DIAG - Diagnostics - Ver 1.9.6
        // back_trace( 'NOTICE', '$beta_version: ' . $beta_version);
    
        $headers = array(
            "Content-Type: application/json",
            "OpenAI-Beta: " . $beta_version,
            "Authorization: Bearer " . $api_key
        );

        $context = stream_context_create(array(
            'http' => array(
                'method' => 'GET',
                'header' => $headers
        )));
        $response = fetchDataUsingCurl($url, $context);

        $responseArray = json_decode($response, true);

        if (array_key_exists("status", $responseArray)) {
            $status = $responseArray["status"];
        } else {
            // Handle error here
            $status = "failed";
            // DIAG - Diagnostics
            // back_trace( 'ERROR', "Error - GPT Assistant - Step 5");
            exit;
        }

        // DIAG - Diagnostics
        // back_trace( 'NOTICE', '$responseArray: ' . print_r($responseArray));
        
        if ($status != "completed") {
            // Sleep for 0.5 (was 5 prior to v 1.7.6) seconds before polling again
            // sleep(5);
            usleep(500000);
        }
    }
}

// Step 6: Get the Run's Steps
function getTheRunsSteps($thread_id, $runId, $api_key) {

    // $url = "https://api.openai.com/v1/threads/" . $thread_id ."/runs/" . $runId ."/steps";
    $url = get_threads_api_url() . '/' . $thread_id . '/runs/' . $runId . '/steps';

    $assistant_beta_version = esc_attr(get_option('chatbot_chatgpt_assistant_beta_version', 'v2'));
    if ( $assistant_beta_version == 'v2' ) {
        $beta_version = "assistants=v2";
    } else {
        $beta_version = "assistants=v1";
    }
    // DIAG - Diagnostics - Ver 1.9.6
    // back_trace( 'NOTICE', '$beta_version: ' . $beta_version);

    $headers = array(
        "Content-Type: application/json",
        "OpenAI-Beta: " . $beta_version,
        "Authorization: Bearer " . $api_key
    );

    $context = stream_context_create(array(
        'http' => array(
            'method' => 'GET',
            'header' => $headers
    )));
    $response = fetchDataUsingCurl($url, $context);

    return json_decode($response, true);
}

// Step 7: Get the Step's Status
function getTheStepsStatus($thread_id, $runId, $api_key) {

    $status = false;

    while (!$status) {

        // $url = "https://api.openai.com/v1/threads/" . $thread_id . "/runs/" . $runId . "/steps";
        $url = get_threads_api_url() . '/' . $thread_id . '/runs/' . $runId . '/steps';

        $assistant_beta_version = esc_attr(get_option('chatbot_chatgpt_assistant_beta_version', 'v2'));
        if ( $assistant_beta_version == 'v2' ) {
            $beta_version = "assistants=v2";
        } else {
            $beta_version = "assistants=v1";
        }
        // DIAG - Diagnostics - Ver 1.9.6
        // back_trace( 'NOTICE', '$beta_version: ' . $beta_version);
        
        $headers = array(
            "Content-Type: application/json",
            "OpenAI-Beta: " . $beta_version,
            "Authorization: Bearer " . $api_key
        );

        $context = stream_context_create(array(
            'http' => array(
                'method' => 'GET',
                'header' => $headers
        )));
        $response = fetchDataUsingCurl($url, $context);

        $responseArray = json_decode($response, true);

        if (array_key_exists("data", $responseArray) && !is_null($responseArray["data"])) {
            $data = $responseArray["data"];
        } else {
            // DIAG - Handle error here
            $status = "failed";
            // DIAG - Diagnostics
            // back_trace( 'ERROR', "Error - GPT Assistant - Step 7.");
            exit;
        }

        foreach ($data as $item) {
            if ($item["status"] == "completed") {
                $status = true;
                break;
            }
        }

        if (!$status) {
            print_r($responseArray);
            // Sleep for 0.5 (was 5 prior to v 1.7.6) seconds before polling again
            // sleep(5);
            usleep(500000);
        }
    }
}

// Step 8: Get the Message
function getTheMessage($thread_id, $api_key) {
    $url = get_threads_api_url() . '/' . $thread_id . '/messages';

    $assistant_beta_version = esc_attr(get_option('chatbot_chatgpt_assistant_beta_version', 'v2'));
    $beta_version = $assistant_beta_version == 'v2' ? "assistants=v2" : "assistants=v1";

    $headers = array(
        "Content-Type: application/json",
        "OpenAI-Beta: " . $beta_version,
        "Authorization: Bearer " . $api_key
    );

    $context = stream_context_create(array(
        'http' => array(
            'method' => 'GET',
            'header' => $headers
    )));
    $response = fetchDataUsingCurl($url, $context);
    $response_data = json_decode($response, true);

    // DIAG - Diagnostics - Ver 2.0.3
    // back_trace('NOTICE', '$response_data: ' . print_r($response_data, true));

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
                            // back_trace('NOTICE', '$annotation: offset_key does not exist');
                            continue;
                        }

                        // If $path is not defined or not a string, skip this iteration
                        if (!isset($path) || !is_string($path)) {
                            continue;
                        }

                        $basename = basename($path);

                        $file_name = 'download_' . generate_random_string() . '_' . basename($annotation['text']); // Extract the filename

                        // DIAG - Diagnostics - Ver 2.0.3
                        // back_trace('NOTICE', '$file_id: ' . $file_id);

                        // Call the function to download the file
                        $file_url = download_openai_file($file_id, $file_name);

                        // DIAG - Diagnostics - Ver 2.0.3
                        // back_trace('NOTICE', '$file_url: ' . $file_url);

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
                                // back_trace('NOTICE', '$file_id: ' . $file_id . ', $file_name: ' . $file_name);

                                // Call the function to download the file
                                $file_url = download_openai_file($file_id, $file_name);

                                // DIAG - Diagnostics - Ver 2.0.3
                                // back_trace('NOTICE', '$file_url: ' . $file_url);

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
        // back_trace('NOTICE', 'No data or attachments found in the response.');

    }

    return $response_data;

}

// CustomGPT - Assistants - Ver 1.7.2
function chatbot_chatgpt_custom_gpt_call_api($api_key, $message, $assistant_id, $thread_id, $user_id, $page_id) {

    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;
    global $script_data_array;
    global $additional_instructions;
    global $model;
    global $voice;

    // DIAG - Diagnostics - Ver 1.8.6
    // back_trace( 'NOTICE', 'chatbot_chatgpt_custom_gpt_call_api()' );
    // back_trace( 'NOTICE', '$user_id: ' . $user_id);
    // back_trace( 'NOTICE', '$page_id: ' . $page_id);
    // back_trace( 'NOTICE', '$session_id: ' . $session_id);
    // back_trace( 'NOTICE', '$thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', '$assistant_id: ' . $assistant_id);
    // back_trace( 'NOTICE', '$message: ' . $message);
    // back_trace( 'NOTICE', '$additional_instructions: ' . $additional_instructions);
    // back_trace( 'NOTICE', '$model: ' . $model);

    // Globals added for Ver 1.7.2
    global $learningMessages;
    global $errorResponses;
    global $stopWords;

    // If the threadId is not set, create a new thread
    if (empty($thread_id)) {
        // Step 1: Create an Assistant
        // back_trace( 'NOTICE', 'Step 1: Create an Assistant');
        $api_key = get_option('chatbot_chatgpt_api_key', '');
        $assistants_response = createAnAssistant($api_key);
        // DIAG - Print the response
        // back_trace( 'NOTICE', '$assistants_response: ' . print_r($assistants_response, true));

        // Step 2: Get The Thread ID
        // back_trace( 'NOTICE', 'Step 2: Get The Thread ID');
        $thread_id = $assistants_response["id"];
        // DIAG - Diagnostics
        // back_trace( 'NOTICE', '$thread_id ' . $thread_id);
        // back_trace( 'NOTICE', '$assistant_id ' . $assistant_id);
        // back_trace( 'NOTICE', '$user_id ' . $user_id);
        // back_trace( 'NOTICE', '$page_id ' . $page_id);
        set_chatbot_chatgpt_threads($thread_id, $assistant_id, $user_id, $page_id);
    }

    // Localize the data for user id and page id
    // REMOVED FOR TESTING - VER 1.9.1 - 2024 03 04
    // $user_id = get_current_user_id();
    // $page_id = get_the_id();

    // DIAG - Diagnostics - Ver 1.9.1
    // back_trace( 'NOTICE', '$user_id: ' . $user_id);
    // back_trace( 'NOTICE', '$page_id: ' . $page_id);
    
    $script_data_array = array(
        'user_id' => $user_id,
        'page_id' => $page_id,
        'session_id' => $session_id,
        'thread_id' => $thread_id,
        'assistant_id' => $assistant_id,
        'additional_instructions' => $additional_instructions,
        'model' => $model,
        'voice' => $voice,
    );

    // Step 1: Create an Assistant
    // back_trace( 'NOTICE', 'Step 1: Create an Assistant');
    // $assistants_response = createAnAssistant($api_key);
    // // DIAG - Print the response
    // back_trace( 'NOTICE', $assistants_response);

    // Step 2: Get The Thread ID
    // back_trace( 'NOTICE', 'Step 2: Get The Thread ID');
    // $thread_id = $assistants_response["id"];
    // // DIAG - Print the threadId
    // back_trace( 'NOTICE', '$thread_id ' . $thread_id);
    // set_chatbot_chatgpt_threads($thread_id, $assistant_id);

    // Conversation Context - Ver 1.7.2.1
    $context = "";
    $context = esc_attr(get_option('chatbot_chatgpt_conversation_context', 'You are a versatile, friendly, and helpful assistant designed to support me in a variety of tasks that responds in Markdown.'));
 
    // // Context History - Ver 1.6.1 - Added here for Ver 1.7.2.1
    //  $chatgpt_last_response = concatenateHistory('chatbot_chatgpt_context_history');
    // // DIAG Diagnostics - Ver 1.6.1
    // back_trace( 'NOTICE', '$chatgpt_last_response ' . $chatgpt_last_response);
    
    // // IDEA Strip any href links and text from the $chatgpt_last_response
    // $chatgpt_last_response = preg_replace('/\[URL:.*?\]/', '', $chatgpt_last_response);

    // // IDEA Strip any $learningMessages from the $chatgpt_last_response
    // $chatgpt_last_response = str_replace($learningMessages, '', $chatgpt_last_response);

    // // IDEA Strip any $errorResponses from the $chatgpt_last_response
    // $chatgpt_last_response = str_replace($errorResponses, '', $chatgpt_last_response);
    
    // // Knowledge Navigator keyword append for context
    // $chatbot_chatgpt_kn_conversation_context = get_option('chatbot_chatgpt_kn_conversation_context', '');

    // // Append prior message, then context, then Knowledge Navigator - Ver 1.6.1
    // $context = $chatgpt_last_response . ' ' . $context . ' ' . $chatbot_chatgpt_kn_conversation_context;

    // Step 3: Add a Message to a Thread
    // back_trace( 'NOTICE', 'Step 3: Add a Message to a Thread');
    // Add additional instructions to the prompt - Ver 1.9.3
    if (empty($additional_instructions)) {
        $prompt = $message;
        // back_trace('NOTICE', 'No additional instructions provided: ' . $prompt);
    } else {
        $prompt = $additional_instructions . ' ' . $message;
        // back_trace('NOTICE', 'Additional instructions provided: ' . $prompt);
    }
    // $prompt = $message;
    
    // Fetch the file id - Ver 1.7.9
    // FIXME - FETCH ALL FILE IDS AND ADD THEM TO THE MESSAGE - Ver 1.9.2 - 2024 03 06
    $file_id = chatbot_chatgpt_retrieve_file_id($user_id, $page_id);

    // DIAG - Diagnostics - Ver 2.0.3
    // back_trace( 'NOTICE', '$user_id: ' . $user_id);
    // back_trace( 'NOTICE', '$page_id: ' . $page_id);

    // DIAG - Diagnostics - Ver 2.0.3
    for ($i = 0; $i < count($file_id); $i++) {
        if (isset($file_id[$i])) {
            // back_trace('NOTICE', '$file_id[' . $i . ']: ' . $file_id[$i]);
        } else {
            // Handle the error appropriately
            // back_trace('NOTICE', '$file_id[' . $i . ']: index does not exist');
            unset($file_id[$i]); // Remove the non-existent key
        }
    }

    // DIAG - Diagnostics - Ver 1.8.1
    // back_trace( 'NOTICE', 'chatbot_chatgpt_retrieve_file_id(): ' . print_r($file_id, true));

    if (empty($file_id)) {
        // back_trace( 'NOTICE', 'No file to retrieve');
        $assistants_response = addAMessage($thread_id, $prompt, $context, $api_key, '');
    } else {
        //DIAG - Diagnostics - Ver 1.7.9
        // back_trace( 'NOTICE', 'File to retrieve');
        // back_trace( 'NOTICE', '$file_id ' . print_r($file_id, true));
        $assistants_response = addAMessage($thread_id, $prompt, $context, $api_key, $file_id);
        // DIAG - Print the response
        // back_trace( 'NOTICE', $assistants_response);
    }

    // Step 4: Run the Assistant
    // back_trace( 'NOTICE', 'Step 4: Run the Assistant');
    $assistants_response = runTheAssistant($thread_id, $assistant_id, $context, $api_key);

    // Check if the response is not an array or is a string indicating an error
    if (!is_array($assistants_response) || is_string($assistants_response)) {
        // back_trace( 'ERROR', 'Invalid response format or error occurred');
        return "Error: Invalid response format or error occurred.";
    }
    // Check if the 'id' key exists in the response
    if (isset($assistants_response["id"])) {
        $runId = $assistants_response["id"];
    } else {
        // back_trace( 'ERROR', '\'$runId\' key not found in response');
        return "Error: 'id' key not found in response.";
    }
    // DIAG - Print the response
    // back_trace( 'NOTICE', $assistants_response);

    // Step 5: Get the Run's Status
    // back_trace( 'NOTICE', 'Step 5: Get the Run\'s Status');
    getTheRunsStatus($thread_id, $runId, $api_key);

    // Step 6: Get the Run's Steps
    // back_trace( 'NOTICE', 'Step 6: Get the Run\'s Steps');
    $assistants_response = getTheRunsSteps($thread_id, $runId, $api_key);
    // DIAG - Print the response
    // back_trace( 'NOTICE', $assistants_response);

    // DIAG - Diagnostics - Ver 1.8.1
    // back_trace( 'NOTICE', 'Usage - Prompt Tokens: ' . $assistants_response["data"][0]["usage"]["prompt_tokens"]);
    // back_trace( 'NOTICE', 'Usage - Completion Tokens: ' . $assistants_response["data"][0]["usage"]["completion_tokens"]);
    // back_trace( 'NOTICE', 'Usage - Total Tokens: ' . $assistants_response["data"][0]["usage"]["total_tokens"]);

    // Add the usage to the conversation tracker
    append_message_to_conversation_log($session_id, $user_id, $page_id, 'Prompt Tokens', $thread_id, $assistant_id, $assistants_response["data"][0]["usage"]["prompt_tokens"]);
    append_message_to_conversation_log($session_id, $user_id, $page_id, 'Completion Tokens', $thread_id, $assistant_id, $assistants_response["data"][0]["usage"]["completion_tokens"]);
    append_message_to_conversation_log($session_id, $user_id, $page_id, 'Total Tokens', $thread_id, $assistant_id, $assistants_response["data"][0]["usage"]["total_tokens"]);

    // Step 7: Get the Step's Status
    // back_trace( 'NOTICE', 'Step 7: Get the Step\'s Status');
    getTheStepsStatus($thread_id, $runId, $api_key);

    // Step 8: Get the Message
    // back_trace( 'NOTICE', 'Step 8: Get the Message');
    $assistants_response = getTheMessage($thread_id, $api_key);

    // Interaction Tracking - Ver 1.6.3
    update_interaction_tracking();

    // Remove citations from the response
    $assistants_response["data"][0]["content"][0]["text"]["value"] = preg_replace('/\【.*?\】/', '', $assistants_response["data"][0]["content"][0]["text"]["value"]);

    return $assistants_response["data"][0]["content"][0]["text"]["value"];

}

// Fetch data with cURL - Ver 1.7.6
function fetchDataUsingCurl($url, $context) {
    // Initialize a cURL session
    $curl = curl_init($url);

    // Set cURL options
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    // If there is a context (which usually contains stream context options), set the corresponding cURL options
    if ($context) {
        $context_options = stream_context_get_options($context);
        if (isset($context_options['http']['method'])) {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $context_options['http']['method']);
        }
        if (isset($context_options['http']['header'])) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $context_options['http']['header']);
        }
        if (isset($context_options['http']['content'])) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $context_options['http']['content']);
        }
    }

    // Execute cURL session and close it
    $response = curl_exec($curl);
    curl_close($curl);

    return $response;

}

// Retrieve the first file id - Ver 1.9.2
function chatbot_chatgpt_retrieve_file_id( $user_id, $page_id) {

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
        // deleteUploadedFile($file_id);

        // Set a transient that expires in 2 hours
        $timeFrameForDelete = time() + 2 * 60 * 60;
        set_transient('chatbot_chatgpt_delete_uploaded_file_' . $file_id, $file_id, $timeFrameForDelete);

        // Set a cron job to delete the file in 1 hour 45 minutes
        $shorterTimeFrameForDelete = time() + 1 * 60 * 60 + 45 * 60;
        if (!wp_next_scheduled('delete_uploaded_file', array($file_id))) {
            wp_schedule_single_event($shorterTimeFrameForDelete, 'delete_uploaded_file', array($file_id));
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
    // DIAG - Diagnostics - Ver 1.9.2
    // back_trace( 'NOTICE', 'chatbot_chatgpt_retrieve_file_ids(): ' . implode(',', $file_ids));

    // return implode(',', $file_ids);

    // Join the file ids into a comma-separated string and return it
    // DIAG - Diagnostics - Ver 1.9.2
    // back_trace( 'NOTICE', 'chatbot_chatgpt_retrieve_file_ids(): ' . print_r($file_ids, true));

    return $file_ids;

}

// Cleanup in Aisle 4 on OpenAI - Ver 1.7.9
function deleteUploadedFile($file_id) {

    // Get the API key
    $apiKey = esc_attr(get_option('chatbot_chatgpt_api_key'));

    // $url = 'https://api.openai.com/v1/files/' . $file_id;
    $url = get_files_api_url() . '/' . $file_id;
    
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
      'Authorization: Bearer ' . $apiKey,
      'Content-Type: application/json'
    ]);
    
    $response = curl_exec($curl);
    $http_status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($http_status_code == 200 || $http_status_code == 204) {
        // DIAG - Diagnostics - Ver 1.7.9
        // back_trace( 'SUCCESS', "File deleted successfully.\n");
    } else {
        // If the request was not successful, you may want to handle it differently,
        // such as logging an error or retrying the request.
        // DIAG - Diagnostics - Ver 1.7.9
        // back_trace( 'ERROR', "HTTP status code: $http_status_code\n");
        // back_trace( 'ERROR', "Response: $response\n");
    }

}
add_action( 'delete_uploaded_file', 'deleteUploadedFile' );
