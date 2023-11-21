<?php
/**
 * Chatbot ChatGPT for WordPress - Custom GPT - Ver 1.6.7
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
    $url = "https://api.openai.com/v1/threads/".$threadId."/runs";
    $headers = array(
        "Content-Type: application/json",
        "OpenAI-Beta: assistants=v1",
        "Authorization: Bearer " . $api_key
    );
    $data = array(
        "assistant_id" => $assistantId
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
        $status = $responseArray["status"];
        print_r($responseArray);
        
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
        $data = $responseArray["data"];

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
    // $api_key = esc_attr(get_option('chatgpt_api_key'));
    $assistantId = esc_attr(get_option('chatbot_chatgpt_assistant_id'));

    // DIAG - Step 1: Create an Assistant
    // error_log ('Step 1: Create an Assistant');
    $assistants_response = createAnAssistant($api_key);
    // DIAG - Print the response
    // error_log (print_r($assistants_response, true));

    // DIAG - Step 2: Get The Thread ID
    // error_log ('Step 2: Get The Thread ID');
    $threadId = $assistants_response["id"];
    // DIAG - Print the threadId
    // error_log ($threadId);

    // DIAG - Step 3: Add a Message to a Thread
    // error_log ('Step 3: Add a Message to a Thread');
    $prompt = $message;
    $assistants_response = addAMessage($threadId, $prompt, $api_key);
    // DIAG - Print the response
    // error_log (print_r($assistants_response, true));

    // DIAG - Step 4: Run the Assistant
    // error_log ('Step 4: Run the Assistant');
    $assistants_response = runTheAssistant($threadId, $assistantId, $api_key);
    $runId = $assistants_response["id"];
    // DIAG - Print the response
    // error_log (print_r($assistants_response, true));

    // DIAG - Step 5: Get the Run's Status
    // error_log ('Step 5: Get the Run\'s Status');
    getTheRunsStatus($threadId, $runId, $api_key);

    // DIAG - Step 6: Get the Run's Steps
    // error_log ('Step 6: Get the Run\'s Steps');
    $assistants_response = getTheRunsSteps($threadId, $runId, $api_key);
    // DIAG - Print the response
    // error_log(print_r($assistants_response, true));

    // DIAG - Step 7: Get the Step's Status
    // error_log ('Step 7: Get the Step\'s Status');
    getTheStepsStatus($threadId, $runId, $api_key);

    // DIAG - Step 8: Get the Message
    // error_log ('Step 8: Get the Message');
    $assistants_response = getTheMessage($threadId, $api_key);
    // error_log(print_r($assistants_response, true));

    return $assistants_response["data"][0]["content"][0]["text"]["value"];

}