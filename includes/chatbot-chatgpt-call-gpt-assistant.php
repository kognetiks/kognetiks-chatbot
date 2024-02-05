<?php
/**
 * Chatbot ChatGPT for WordPress - Custom GPT - Ver 1.6.9
 *
 * This file contains the code for table actions for reporting
 * to display the Chatbot ChatGPT on the website.
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
    $headers = array(
        "Content-Type: application/json",
        "OpenAI-Beta: assistants=v1",
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

// Setp 2: EMPTY STEP

// Step 3: Add a Message to a Thread
function addAMessage($thread_id, $prompt, $context, $api_key, $file_id = null) {

    // Set the URL
    $url = get_threads_api_url() . '/' . $thread_id . '/messages';

    $headers = [
        'Content-Type: application/json',
        'OpenAI-Beta: assistants=v1',
        'Authorization: Bearer ' . $api_key
    ];

    // Set up the data payload
    $data = [
        'role' => 'user',
        'content' => $prompt,
    ];

    // Add the file reference if file_id is provided
    if (!empty($file_id)) {
        $data['file'] = $file_id;
        // $data['file_ids'] = ['file' => $file_id];
    }

    // DIAG - Diagnostics
    chatbot_chatgpt_back_trace( 'NOTICE', 'addAMessage() - $data: ' . print_r($data));

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

    // Check for cURL errors
    if (curl_errno($ch)) {
        // DIAG - Diagnostics
        chatbot_chatgpt_back_trace( 'ERROR', 'Curl error: ' . curl_error($ch));
        curl_close($ch);
        return null;
    }

    // Close cURL session
    curl_close($ch);

    // DIAG - Diagnostics
    chatbot_chatgpt_back_trace( 'NOTICE', 'addAMessage() - $response: ' . print_r(json_decode($response, true)));
    
    // Return the API response
    return json_decode($response, true);

}

// Step 4: Run the Assistant
function runTheAssistant($thread_id, $assistant_id, $context, $api_key) {
    // $url = "https://api.openai.com/v1/threads/" . $thread_id . "/runs";
    $url = get_threads_api_url() . '/' . $thread_id . '/runs';
    $headers = array(
        "Content-Type: application/json",
        "OpenAI-Beta: assistants=v1",
        "Authorization: Bearer " . $api_key
    );
    $data = array(
        "assistant_id" => $assistant_id
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
        // chatbot_chatgpt_back_trace( 'ERROR', 'Error unable to fetch response');
        return "Error: Unable to fetch response.";
    }

    // Check HTTP response code
    if (http_response_code() != 200) {
        // DIAG - Diagnostics
        // chatbot_chatgpt_back_trace( 'ERROR', 'HTTP response code: ' . print_r(http_response_code()));
        return "Error: HTTP response code " . http_response_code();
    }

    return json_decode($response, true);
}

// Step 5: Get the Run's Status
function getTheRunsStatus($thread_id, $runId, $api_key): void {
    $status = "";
    while ($status != "completed") {
        // $url = "https://api.openai.com/v1/threads/" . $thread_id . "/runs/".$runId;
        $url = get_threads_api_url() . '/' . $thread_id . '/runs/' . $runId;
        $headers = array(
            "Content-Type: application/json",
            "OpenAI-Beta: assistants=v1",
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
            // chatbot_chatgpt_back_trace( 'ERROR', "Error - GPT Assistant - Step 5");
            exit;
        }

        // DIAG - Diagnostics
        // chatbot_chatgpt_back_trace( 'NOTICE', '$responseArray: ' . print_r($responseArray));
        
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
    $headers = array(
        "Content-Type: application/json",
        "OpenAI-Beta: assistants=v1",
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
function getTheStepsStatus($thread_id, $runId, $api_key): void {
    $status = false;
    while (!$status) {
        // $url = "https://api.openai.com/v1/threads/" . $thread_id . "/runs/" . $runId . "/steps";
        $url = get_threads_api_url() . '/' . $thread_id . '/runs/' . $runId . '/steps';
        $headers = array(
            "Content-Type: application/json",
            "OpenAI-Beta: assistants=v1",
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
            // chatbot_chatgpt_back_trace( 'ERROR', "Error - GPT Assistant - Step 7.");
            exit;
        }

        foreach ($data as $item) {
            if ($item["status"] == "completed") {
                // echo "Step completed\n";
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
    // $url = "https://api.openai.com/v1/threads/" . $thread_id . "/messages";
    $url = get_threads_api_url() . '/' . $thread_id . '/messages';
    $headers = array(
        "Content-Type: application/json",
        "OpenAI-Beta: assistants=v1",
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

// CustomerGPT - Assistants - Ver 1.7.2
function chatbot_chatgpt_custom_gpt_call_api($api_key, $message, $assistant_id, $thread_id, $user_id, $page_id) {

    global $session_id;

    // DIAG - Diagnostics
    // chatbot_chatgpt_back_trace( 'NOTICE', 'Using Assistant ID: ' . $assistant_id);

    // Globals added for Ver 1.7.2
    global $chatbot_chatgpt_diagnostics;
    global $learningMessages;
    global $errorResponses;
    global $stopWords;

    // If the threadId is not set, create a new thread
    if (empty($thread_id)) {
        // Step 1: Create an Assistant
        chatbot_chatgpt_back_trace( 'NOTICE', 'Step 1: Create an Assistant');
        $assistants_response = createAnAssistant($api_key);
        // DIAG - Print the response
        chatbot_chatgpt_back_trace( 'NOTICE', $assistants_response);

        // Step 2: Get The Thread ID
        chatbot_chatgpt_back_trace( 'NOTICE', 'Step 2: Get The Thread ID');
        $thread_id = $assistants_response["id"];
        // DIAG - Diagnostics
        chatbot_chatgpt_back_trace( 'NOTICE', '$thread_id ' . $thread_id);
        chatbot_chatgpt_back_trace( 'NOTICE', '$assistant_id ' . $assistant_id);
        chatbot_chatgpt_back_trace( 'NOTICE', '$user_id ' . $user_id);
        chatbot_chatgpt_back_trace( 'NOTICE', '$page_id ' . $page_id);
        set_chatbot_chatgpt_threads($thread_id, $assistant_id, $user_id, $page_id);
    }

    // Step 1: Create an Assistant
    // chatbot_chatgpt_back_trace( 'NOTICE', 'Step 1: Create an Assistant');
    // $assistants_response = createAnAssistant($api_key);
    // DIAG - Print the response
    // chatbot_chatgpt_back_trace( 'NOTICE', $assistants_response);

    // Step 2: Get The Thread ID
    // chatbot_chatgpt_back_trace( 'NOTICE', 'Step 2: Get The Thread ID');
    // $thread_id = $assistants_response["id"];
    // DIAG - Print the threadId
    // chatbot_chatgpt_back_trace( 'NOTICE', '$thread_id ' . $thread_id);
    // set_chatbot_chatgpt_threads($thread_id, $assistant_id);

    // Conversation Context - Ver 1.7.2.1
    $context = "";
    $context = esc_attr(get_option('chatbot_chatgpt_conversation_context', 'You are a versatile, friendly, and helpful assistant designed to support me in a variety of tasks.'));
 
    // // Context History - Ver 1.6.1 - Added here for Ver 1.7.2.1
    //  $chatgpt_last_response = concatenateHistory('chatbot_chatgpt_context_history');
    // // DIAG Diagnostics - Ver 1.6.1
    // chatbot_chatgpt_back_trace( 'NOTICE', '$chatgpt_last_response ' . $chatgpt_last_response);
    
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
    chatbot_chatgpt_back_trace( 'NOTICE', 'Step 3: Add a Message to a Thread');
    $prompt = $message;
    
    // Fetch the file id - Ver 1.7.9
    $file_id = chatbot_chatgpt_retrieve_file_id($user_id, $page_id);

    // DIAG - Diagnostics - Ver 1.8.1
    chatbot_chatgpt_back_trace( 'NOTICE', 'chatbot_chatgpt_retrieve_file_id(): ' . $file_id);

    if (empty($file_id)) {
        chatbot_chatgpt_back_trace( 'NOTICE', 'No file to retrieve');
        $assistants_response = addAMessage($thread_id, $prompt, $context, $api_key);
    } else {
        //DIAG - Diagnostics - Ver 1.7.9
        chatbot_chatgpt_back_trace( 'NOTICE', 'File to retrieve');
        chatbot_chatgpt_back_trace( 'NOTICE', '$file_id ' . $file_id);
        $assistants_response = addAMessage($thread_id, $prompt, $context, $api_key, $file_id);
        // DIAG - Print the response
        chatbot_chatgpt_back_trace( 'NOTICE', $assistants_response);
    }

    // Step 4: Run the Assistant
    // chatbot_chatgpt_back_trace( 'NOTICE', 'Step 4: Run the Assistant');
    $assistants_response = runTheAssistant($thread_id, $assistant_id, $context, $api_key);

    // Check if the response is not an array or is a string indicating an error
    if (!is_array($assistants_response) || is_string($assistants_response)) {
        // chatbot_chatgpt_back_trace( 'ERROR', 'Invalid response format or error occurred');
        return "Error: Invalid response format or error occurred.";
    }
    // Check if the 'id' key exists in the response
    if (isset($assistants_response["id"])) {
        $runId = $assistants_response["id"];
    } else {
        // chatbot_chatgpt_back_trace( 'ERROR', '\'$runId\' key not found in response');
        return "Error: 'id' key not found in response.";
    }
    // DIAG - Print the response
    // chatbot_chatgpt_back_trace( 'NOTICE', $assistants_response);

    // Step 5: Get the Run's Status
    // chatbot_chatgpt_back_trace( 'NOTICE', 'Step 5: Get the Run\'s Status');
    getTheRunsStatus($thread_id, $runId, $api_key);

    // Step 6: Get the Run's Steps
    // chatbot_chatgpt_back_trace( 'NOTICE', 'Step 6: Get the Run\'s Steps');
    $assistants_response = getTheRunsSteps($thread_id, $runId, $api_key);
    // DIAG - Print the response
    // chatbot_chatgpt_back_trace( 'NOTICE', $assistants_response);

    // DIAG - Diagnostics - Ver 1.8.1
    chatbot_chatgpt_back_trace( 'NOTICE', 'Usage - Prompt Tokens: ' . $assistants_response["data"][0]["usage"]["prompt_tokens"]);
    chatbot_chatgpt_back_trace( 'NOTICE', 'Usage - Completion Tokens: ' . $assistants_response["data"][0]["usage"]["completion_tokens"]);
    chatbot_chatgpt_back_trace( 'NOTICE', 'Usage - Total Tokens: ' . $assistants_response["data"][0]["usage"]["total_tokens"]);

    // Add the usage to the conversation tracker
    append_message_to_conversation_log($session_id, $user_id, $page_id, 'Prompt Tokens', $thread_id, $assistant_id, $assistants_response["data"][0]["usage"]["prompt_tokens"]);
    append_message_to_conversation_log($session_id, $user_id, $page_id, 'Completion Tokens', $thread_id, $assistant_id, $assistants_response["data"][0]["usage"]["completion_tokens"]);
    append_message_to_conversation_log($session_id, $user_id, $page_id, 'Total Tokens', $thread_id, $assistant_id, $assistants_response["data"][0]["usage"]["total_tokens"]);

    // Step 7: Get the Step's Status
    // chatbot_chatgpt_back_trace( 'NOTICE', 'Step 7: Get the Step\'s Status');
    getTheStepsStatus($thread_id, $runId, $api_key);

    // Step 8: Get the Message
    // chatbot_chatgpt_back_trace( 'NOTICE', 'Step 8: Get the Message');
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

// Retrieve the file id - Ver 1.7.9
function chatbot_chatgpt_retrieve_file_id() {

    // Retrieve the file
    $file_id = get_chatbot_chatgpt_transients( 'chatbot_chatgpt_assistant_file_id' );

    // DIAG - Diagnostics - Ver 1.8.1
    chatbot_chatgpt_back_trace( 'NOTICE', 'chatbot_chatgpt_assistant_file_id: ' . $file_id);

    // If the file id is empty, return an empty string
    if (empty($file_id)) {
        return '';
    }

    // Delete the transient
    // FIXME - DECIDE - SHOULD WE DELETE THE TRANSIENT OR JUST LET IT EXPIRE
    // delete_chatbot_chatgpt_transients( 'chatbot_chatgpt_assistant_file_id', $user_id, $page_id);

    // Delete the file
    // deleteUploadedFile($file_id);

    // Set a transient that expires in 2 hours
    set_transient('chatbot_chatgpt_delete_uploaded_file_' . $file_id, $file_id, 2 * 60 * 60);

    // Set a cron job to delete the file in 1 hour 59 minutes
    if (!wp_next_scheduled('delete_uploaded_file', array($file_id))) {
        wp_schedule_single_event(time() + 59 * 60 + 1 * 60 * 60, 'delete_uploaded_file', array($file_id));
    }

    return $file_id;

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
        chatbot_chatgpt_back_trace( 'SUCCESS', "File deleted successfully.\n");
    } else {
        // If the request was not successful, you may want to handle it differently,
        // such as logging an error or retrying the request.
        // DIAG - Diagnostics - Ver 1.7.9
        chatbot_chatgpt_back_trace( 'ERROR', "HTTP status code: $http_status_code\n");
        chatbot_chatgpt_back_trace( 'ERROR', "Response: $response\n");
    }

}
add_action( 'delete_uploaded_file', 'deleteUploadedFile' );
