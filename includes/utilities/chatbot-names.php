<?php
/**
 * Kognetiks Chatbot for WordPress - Retrieves the Name of the Assistant
 *
 * This file contains the code to retrieve the name of the Assistant
 * from the OpenAI platform.  It uses the Assistant ID to make the request.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Function to get the Assistant's name
function get_chatbot_chatgpt_assistant_name($assistant_id_lookup) {

    // Global variables
    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;
    global $chatbot_chatgpt_display_style;
    global $chatbot_chatgpt_assistant_alias;
    global $script_data_array;
    global $additional_instructions;

    $api_key = '';

    // Retrieve the API key
    $api_key = esc_attr(get_option('chatbot_chatgpt_api_key'));

    // Initialize cURL session
    $ch = curl_init();

    // Set the URL, including the Assistant ID
    curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/assistants/" . $assistant_id_lookup);
    // Set the HTTP request to GET
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    // Include the headers
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/json",
        "OpenAI-Beta: assistants=v1",
        "Authorization: Bearer " . $api_key
    ));
    // Return the response as a string instead of directly outputting it
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    // Execute the request and decode the JSON response into an associative array
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    // Check for errors during the execution or in the response
    if($response === false) {
        // echo 'cURL Error: ' . curl_error($ch);
        return false;
    } elseif(isset($response['error'])) {
        // echo 'API Error: ' . $response['error']['message'];
        return false;
    } else {
        // If no errors, print the Assistant's name
        // echo 'Assistant Name: ' . $response['name'];
        back_trace( 'NOTICE', 'Assistant Name: ' . $response['name']);
        return $response['name'];
    }

}