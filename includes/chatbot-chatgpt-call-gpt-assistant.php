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
if ( ! defined( 'WPINC' ) )
	die;

// Step 1: Create an Assistant
function createAnAssistant($api_key) {
    $url = "https://api.openai.com/v1/threads";
    $headers = array(
        "Content-Type: application/json",
        "OpenAI-Beta: assistants=v1",
        "Authorization: Bearer " . $api_key
    );

    $response = file_get_contents($url, false, stream_context_create(array(
        'http' => array(
            'method' => 'POST',
            'header' => $headers,
        )
    )));

    return json_decode($response, true);
}

// Step 3: Add a Message to a Thread
function addAMessage($threadId, $prompt, $api_key) {
    $url = "https://api.openai.com/v1/threads/".$threadId."/messages";
    $headers = array(
        "Content-Type: application/json",
        "OpenAI-Beta: assistants=v1",
        "Authorization: Bearer " . $api_key
    );
    $data = array(
        "role" => "user",
        "content" => $prompt
    );

    $response = file_get_contents($url, false, stream_context_create(array(
        'http' => array(
            'method' => 'POST',
            'header' => $headers,
            'content' => json_encode($data)
        )
    )));

    return json_decode($response, true);
}

// Step 4: Run the Assistant
function runTheAssistant($threadId, $assistantId, $api_key) {
    $url = "https://api.openai.com/v1/threads/" . $threadId . "/runs";
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

    $response = file_get_contents($url, false, $context);

    // Check for false response
    if ($response === FALSE) {
        // Handle the error, for example:
        // chatbot_chatgpt_back_trace( "NOTICE", "Error unable to fetch response");
        return "Error: Unable to fetch response.";
    }

    // Check HTTP response code
    if (http_response_code() != 200) {
        // Handle the error, for example:
        // chatbot_chatgpt_back_trace( "NOTICE", "HTTP response code " . http_response_code());
        return "Error: HTTP response code " . http_response_code();
    }

    return json_decode($response, true);
}

// Step 5: Get the Run's Status
function getTheRunsStatus($threadId, $runId, $api_key) {
    $status = "";
    while ($status != "completed") {
        $url = "https://api.openai.com/v1/threads/".$threadId."/runs/".$runId;
        $headers = array(
            "Content-Type: application/json",
            "OpenAI-Beta: assistants=v1",
            "Authorization: Bearer " . $api_key
        );

        $response = file_get_contents($url, false, stream_context_create(array(
            'http' => array(
                'method' => 'GET',
                'header' => $headers
            )
        )));

        $responseArray = json_decode($response, true);

        if (array_key_exists("status", $responseArray)) {
            $status = $responseArray["status"];
        } else {
            // Handle error here
            $status = "failed";
            // chatbot_chatgpt_back_trace( "NOTICE", "Error - Custom GPT Assistant - Step 5");
            exit;
        }

        // chatbot_chatgpt_back_trace( "NOTICE", $responseArray);
        
        if ($status != "completed") {
            // Sleep for 5 seconds before polling again
            sleep(5);
        }
    }
}

// Step 6: Get the Run's Steps
function getTheRunsSteps($threadId, $runId, $api_key) {
    $url = "https://api.openai.com/v1/threads/".$threadId."/runs/".$runId."/steps";
    $headers = array(
        "Content-Type: application/json",
        "OpenAI-Beta: assistants=v1",
        "Authorization: Bearer " . $api_key
    );

    $response = file_get_contents($url, false, stream_context_create(array(
        'http' => array(
            'method' => 'GET',
            'header' => $headers
        )
    )));

    return json_decode($response, true);
}

// Step 7: Get the Step's Status
function getTheStepsStatus($threadId, $runId, $api_key) {
    $status = false;
    while (!$status) {
        $url = "https://api.openai.com/v1/threads/".$threadId."/runs/".$runId."/steps";
        $headers = array(
            "Content-Type: application/json",
            "OpenAI-Beta: assistants=v1",
            "Authorization: Bearer " . $api_key
        );

        $response = file_get_contents($url, false, stream_context_create(array(
            'http' => array(
                'method' => 'GET',
                'header' => $headers
            )
        )));

        $responseArray = json_decode($response, true);

        if (array_key_exists("data", $responseArray) && !is_null($responseArray["data"])) {
            $data = $responseArray["data"];
        } else {
            // DIAG - Handle error here
            $status = "failed";
            // chatbot_chatgpt_back_trace( "NOTICE", "Chatbot ChatGPT: Error - Custom GPT Assistant - Step 7.");
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
            // Sleep for 5 seconds before polling again
            sleep(5);
        }
    }
}

// Step 8: Get the Message
function getTheMessage($threadId, $api_key) {
    $url = "https://api.openai.com/v1/threads/".$threadId."/messages";
    $headers = array(
        "Content-Type: application/json",
        "OpenAI-Beta: assistants=v1",
        "Authorization: Bearer " . $api_key
    );

    $response = file_get_contents($url, false, stream_context_create(array(
        'http' => array(
            'method' => 'GET',
            'header' => $headers
        )
    )));

    return json_decode($response, true);
}

// CustomerGPT - Assistants - Ver 1.7.7
function chatbot_chatgpt_custom_gpt_call_api($api_key, $message) {

    
    // Get the authorization token and assistant ID
    // $assistantId = esc_attr(get_option('chatbot_chatgpt_assistant_id'));
    // Retrieve the Assistant Alias from localStorage- Ver 1.7.2
    // FIXME = Start the session at the beginning of the script - IS THIS THE RIGHT PLACE FOR THIS?
    session_start();
    if (!isset($_SESSION['chatbot_chatgpt_assistant_alias'])) {
        $_SESSION['chatbot_chatgpt_assistant_alias'] = 'primary';
    }
    $chatbot_chatgpt_assistant_alias = $_SESSION['chatbot_chatgpt_assistant_alias'];
    // DIAG - Diagnostics - Ver 1.7.2
    chatbot_chatgpt_back_trace( "NOTICE", '$chatbot_chatgpt_assistant_alias: ' . $chatbot_chatgpt_assistant_alias);
    if ($chatbot_chatgpt_assistant_alias === 'primary') {
        // Retrieve the Assistant ID
        $assistantId = esc_attr(get_option('chatbot_chatgpt_assistant_id'));
        // DIAG - Diagnostics - Ver 1.7.2
        chatbot_chatgpt_back_trace( "NOTICE", '$assistantId: ' . $assistantId);
    } elseif ($chatbot_chatgpt_assistant_alias === 'alternate') {
        // Fetch the Assistant Id Alternate based on Alias - Ver 1.7.2
        $assistantId = esc_attr(get_option('chatbot_chatgpt_assistant_id_alternate'));
        // DIAG - Diagnostics - Ver 1.7.2
        chatbot_chatgpt_back_trace( "NOTICE", '$assistantId: ' . $assistantId);
    } else {
        return "Error: Assistant ID Missing.";
        // DIAG - Diagnostics - Ver 1.7.2
        chatbot_chatgpt_back_trace( "ERROR", 'Assistant ID is missing.');
    }

    // DIAG - Diagnostics
    chatbot_chatgpt_back_trace( "NOTICE", 'Using Assistant Alias: ' . $chatbot_chatgpt_assistant_alias);
    chatbot_chatgpt_back_trace( "NOTICE", 'Using Assistant ID: ' . $assistantId);

    // Step 1: Create an Assistant
    // chatbot_chatgpt_back_trace( "NOTICE", 'Chatbot ChatGPT: Step 1: Create an Assistant');
    $assistants_response = createAnAssistant($api_key);
    // DIAG - Print the response
    // chatbot_chatgpt_back_trace( "NOTICE", $assistants_response);

    // Step 2: Get The Thread ID
    // chatbot_chatgpt_back_trace( "NOTICE", 'Chatbot ChatGPT: Step 2: Get The Thread ID');
    $threadId = $assistants_response["id"];
    // DIAG - Print the threadId
    // chatbot_chatgpt_back_trace( "NOTICE", '$threadId ' . $threadId);

    // Step 3: Add a Message to a Thread
    // chatbot_chatgpt_back_trace( "NOTICE", 'Chatbot ChatGPT: Step 3: Add a Message to a Thread');
    $prompt = $message;
    $assistants_response = addAMessage($threadId, $prompt, $api_key);
    // DIAG - Print the response
    // chatbot_chatgpt_back_trace( "NOTICE", $assistants_response);

    // Step 4: Run the Assistant
    // chatbot_chatgpt_back_trace( "NOTICE", 'Chatbot ChatGPT: Step 4: Run the Assistant');
    $assistants_response = runTheAssistant($threadId, $assistantId, $api_key);

    // Check if the response is not an array or is a string indicating an error
    if (!is_array($assistants_response) || is_string($assistants_response)) {
        // chatbot_chatgpt_back_trace( "NOTICE", 'Error: Invalid response format or error occurred.');
        return "Error: Invalid response format or error occurred.";
    }
    // Check if the 'id' key exists in the response
    if (isset($assistants_response["id"])) {
        $runId = $assistants_response["id"];
    } else {
        // chatbot_chatgpt_back_trace( "NOTICE", 'Error: \'$runId\' key not found in response.');
        return "Error: 'id' key not found in response.";
    }
    // DIAG - Print the response
    // chatbot_chatgpt_back_trace( "NOTICE", $assistants_response);

    // Step 5: Get the Run's Status
    // chatbot_chatgpt_back_trace( "NOTICE", 'Chatbot ChatGPT: Step 5: Get the Run\'s Status');
    getTheRunsStatus($threadId, $runId, $api_key);

    // Step 6: Get the Run's Steps
    // chatbot_chatgpt_back_trace( "NOTICE", 'Chatbot ChatGPT: Step 6: Get the Run\'s Steps');
    $assistants_response = getTheRunsSteps($threadId, $runId, $api_key);
    // DIAG - Print the response
    // chatbot_chatgpt_back_trace( "NOTICE", $assistants_response);

    // Step 7: Get the Step's Status
    // chatbot_chatgpt_back_trace( "NOTICE", 'Chatbot ChatGPT: Step 7: Get the Step\'s Status');
    getTheStepsStatus($threadId, $runId, $api_key);

    // Step 8: Get the Message
    // chatbot_chatgpt_back_trace( "NOTICE", 'Chatbot ChatGPT: Step 8: Get the Message');
    $assistants_response = getTheMessage($threadId, $api_key);
    // DIAG - Print the response
    // chatbot_chatgpt_back_trace( "NOTICE", '$assistants_response: ' . $assistants_response);

    // Interaction Tracking - Ver 1.6.3
    update_interaction_tracking();

    // Remove citations from the response
    $assistants_response["data"][0]["content"][0]["text"]["value"] = preg_replace('/\【.*?\】/', '', $assistants_response["data"][0]["content"][0]["text"]["value"]);

    // FIXME - REMOVE THIS EXAMPLE >>> $response_body['choices'][0]['message']['content'];
    return $assistants_response["data"][0]["content"][0]["text"]["value"];

}