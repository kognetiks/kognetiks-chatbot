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

    if ($response === FALSE) {
        // DIAG - Handle error here
        // error_log ("Chatbot ChatGPT: Error - Custom GPT Assistant - Step 4");
        $status = "failed";
        exit;
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
            // error_log ("Chatbot ChatGPT: Error - Custom GPT Assistant - Step 57");
            exit;
        }

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

        if (array_key_exists("data", $responseArray) && !is_null($responseArray["data"])) {
            $data = $responseArray["data"];
        } else {
            // DIAG - Handle error here
            $status = "failed";
            // error_log ("Chatbot ChatGPT: Error - Custom GPT Assistant - Step 7.");
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
    // $api_key = esc_attr(get_option('chatgpt_api_key'));
    $assistantId = esc_attr(get_option('chatbot_chatgpt_assistant_id'));

    // Step 1: Create an Assistant
    // error_log ('Chatbot ChatGPT: Step 1: Create an Assistant');
    $assistants_response = createAnAssistant($api_key);
    // DIAG - Print the response
    // error_log (print_r($assistants_response, true));

    // Step 2: Get The Thread ID
    // error_log ('Chatbot ChatGPT: Step 2: Get The Thread ID');
    $threadId = $assistants_response["id"];
    // DIAG - Print the threadId
    // error_log ($threadId);

    // Step 3: Add a Message to a Thread
    // error_log ('Chatbot ChatGPT: Step 3: Add a Message to a Thread');
    $prompt = $message;
    $assistants_response = addAMessage($threadId, $prompt, $api_key);
    // DIAG - Print the response
    // error_log (print_r($assistants_response, true));

    // Step 4: Run the Assistant
    // error_log ('Chatbot ChatGPT: Step 4: Run the Assistant');
    $assistants_response = runTheAssistant($threadId, $assistantId, $api_key);
    $runId = $assistants_response["id"];
    // DIAG - Print the response
    // error_log (print_r($assistants_response, true));

    // Step 5: Get the Run's Status
    // error_log ('Chatbot ChatGPT: Step 5: Get the Run\'s Status');
    getTheRunsStatus($threadId, $runId, $api_key);

    // Step 6: Get the Run's Steps
    // error_log ('Chatbot ChatGPT: Step 6: Get the Run\'s Steps');
    $assistants_response = getTheRunsSteps($threadId, $runId, $api_key);
    // DIAG - Print the response
    // error_log(print_r($assistants_response, true));

    // Step 7: Get the Step's Status
    // error_log ('Chatbot ChatGPT: Step 7: Get the Step\'s Status');
    getTheStepsStatus($threadId, $runId, $api_key);

    // Step 8: Get the Message
    // error_log ('Chatbot ChatGPT: Step 8: Get the Message');
    $assistants_response = getTheMessage($threadId, $api_key);
    // DIAG - Print the response
    // error_log(print_r($assistants_response, true));

    return $assistants_response["data"][0]["content"][0]["text"]["value"];

}