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
    $url = "https://api.openai.com/v1/threads";
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

// Step 3: Add a Message to a Thread
function addAMessage($thread_Id, $prompt, $context, $api_key) {

    // If $context is empty, set it to the default
    if (empty($context)) {
        $context = "You are a versatile, friendly, and helpful assistant designed to support me in a variety of tasks.";
    }

    $url = "https://api.openai.com/v1/threads/".$thread_Id."/messages";
    $headers = array(
        "Content-Type: application/json",
        "OpenAI-Beta: assistants=v1",
        "Authorization: Bearer " . $api_key
    );

    $data = array(
        "role" => "user",
        "content" => $prompt
    );

    $context = stream_context_create(array(
        'http' => array(
            'method' => 'POST',
            'header' => $headers,
            'content' => json_encode($data)
    )));
    $response = fetchDataUsingCurl($url, $context);

    return json_decode($response, true);
}

// Step 4: Run the Assistant
function runTheAssistant($thread_Id, $assistantId, $context, $api_key) {
    $url = "https://api.openai.com/v1/threads/" . $thread_Id . "/runs";
    $headers = array(
        "Content-Type: application/json",
        "OpenAI-Beta: assistants=v1",
        "Authorization: Bearer " . $api_key
    );
    $data = array(
        "assistant_id" => $assistantId
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
        // chatbot_chatgpt_back_trace( 'ERROR', 'HTTP response code: ' . http_response_code());
        return "Error: HTTP response code " . http_response_code();
    }

    return json_decode($response, true);
}

// Step 5: Get the Run's Status
function getTheRunsStatus($thread_Id, $runId, $api_key) {
    $status = "";
    while ($status != "completed") {
        $url = "https://api.openai.com/v1/threads/".$thread_Id."/runs/".$runId;
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
        // chatbot_chatgpt_back_trace( 'NOTICE', '$responseArray: ' . $responseArray);
        
        if ($status != "completed") {
            // Sleep for 0.5 (was 5 prior to v 1.7.6) seconds before polling again
            // sleep(5);
            usleep(500000);
        }
    }
}

// Step 6: Get the Run's Steps
function getTheRunsSteps($thread_Id, $runId, $api_key) {
    $url = "https://api.openai.com/v1/threads/".$thread_Id."/runs/".$runId."/steps";
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
function getTheStepsStatus($thread_Id, $runId, $api_key) {
    $status = false;
    while (!$status) {
        $url = "https://api.openai.com/v1/threads/".$thread_Id."/runs/".$runId."/steps";
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
                echo "Step completed\n";
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
function getTheMessage($thread_Id, $api_key) {
    $url = "https://api.openai.com/v1/threads/".$thread_Id."/messages";
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
function chatbot_chatgpt_custom_gpt_call_api($api_key, $message, $assistantId, $thread_Id, $user_id, $page_id) {

    // DIAG - Diagnostics
    // chatbot_chatgpt_back_trace( 'NOTICE', 'Using Assistant ID: ' . $assistantId);

    // Globals added for Ver 1.7.2
    global $chatbot_chatgpt_diagnostics;
    global $learningMessages;
    global $errorResponses;
    global $stopWords;
    global $file_contents;

    // If the threadId is not set, create a new thread
    if (empty($thread_Id)) {
        // Step 1: Create an Assistant
        // chatbot_chatgpt_back_trace( 'NOTICE', 'Step 1: Create an Assistant');
        $assistants_response = createAnAssistant($api_key);
        // DIAG - Print the response
        // chatbot_chatgpt_back_trace( 'NOTICE', $assistants_response);

        // Step 2: Get The Thread ID
        // chatbot_chatgpt_back_trace( 'NOTICE', 'Step 2: Get The Thread ID');
        $thread_Id = $assistants_response["id"];
        // DIAG - Diagnostics
        // chatbot_chatgpt_back_trace( 'NOTICE', '$thread_Id ' . $thread_Id);
        // chatbot_chatgpt_back_trace( 'NOTICE', '$assistantId ' . $assistantId);
        // chatbot_chatgpt_back_trace( 'NOTICE', '$user_id ' . $user_id);
        // chatbot_chatgpt_back_trace( 'NOTICE', '$page_id ' . $page_id);
        set_chatbot_chatgpt_threads($thread_Id, $assistantId, $user_id, $page_id);
    }

    // Step 1: Create an Assistant
    // chatbot_chatgpt_back_trace( 'NOTICE', 'Step 1: Create an Assistant');
    // $assistants_response = createAnAssistant($api_key);
    // DIAG - Print the response
    // chatbot_chatgpt_back_trace( 'NOTICE', $assistants_response);

    // Step 2: Get The Thread ID
    // chatbot_chatgpt_back_trace( 'NOTICE', 'Step 2: Get The Thread ID');
    // $thread_Id = $assistants_response["id"];
    // DIAG - Print the threadId
    // chatbot_chatgpt_back_trace( 'NOTICE', '$thread_Id ' . $thread_Id);
    // set_chatbot_chatgpt_threads($thread_Id, $assistantId);


    // Conversation Context - Ver 1.7.2.1
    $context = "";
    $context = esc_attr(get_option('chatbot_chatgpt_conversation_context', 'You are a versatile, friendly, and helpful assistant designed to support me in a variety of tasks.'));
 
    // // Context History - Ver 1.6.1 - Added here for Ver 1.7.2.1
    //  $chatgpt_last_response = concatenateHistory('context_history');
    // // DIAG Diagnostics - Ver 1.6.1
    // // chatbot_chatgpt_back_trace( 'NOTICE', '$chatgpt_last_response ' . $chatgpt_last_response);
    
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
    // chatbot_chatgpt_back_trace( 'NOTICE', 'Step 3: Add a Message to a Thread');
    $prompt = $message;
    
    // Add $file_contents to the prompt - Ver 1.7.9
    if (!empty($file_contents)) {
        $prompt = $prompt . ' ' . $file_contents;
    }
    
    $assistants_response = addAMessage($thread_Id, $prompt, $context, $api_key);
    // DIAG - Print the response
    // chatbot_chatgpt_back_trace( 'NOTICE', $assistants_response);

    // Step 4: Run the Assistant
    // chatbot_chatgpt_back_trace( 'NOTICE', 'Step 4: Run the Assistant');
    $assistants_response = runTheAssistant($thread_Id, $assistantId, $context, $api_key);

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
    getTheRunsStatus($thread_Id, $runId, $api_key);

    // Step 6: Get the Run's Steps
    // chatbot_chatgpt_back_trace( 'NOTICE', 'Step 6: Get the Run\'s Steps');
    $assistants_response = getTheRunsSteps($thread_Id, $runId, $api_key);
    // DIAG - Print the response
    // chatbot_chatgpt_back_trace( 'NOTICE', $assistants_response);

    // Step 7: Get the Step's Status
    // chatbot_chatgpt_back_trace( 'NOTICE', 'Step 7: Get the Step\'s Status');
    getTheStepsStatus($thread_Id, $runId, $api_key);

    // Step 8: Get the Message
    // chatbot_chatgpt_back_trace( 'NOTICE', 'Step 8: Get the Message');
    $assistants_response = getTheMessage($thread_Id, $api_key);
    // DIAG - Print the response
    // chatbot_chatgpt_back_trace( 'NOTICE', '$assistants_response: ' . $assistants_response);

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