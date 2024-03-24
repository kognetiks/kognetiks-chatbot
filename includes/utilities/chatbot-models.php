<?php
/**
 * Kognetiks Chatbot for WordPress - Chatbot Models
 *
 * This file contains the code to retireve the list of available models
 * from OpenAI API and display them in the settings page.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Function to get the Assistant's name
function get_openai_models() {

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

    // Set the URL
    curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/models");
    // Include the headers
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/json",
        "Authorization: Bearer " . $api_key
    ));
    // Return the response as a string instead of directly outputting it
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    // Execute the request and decode the JSON response into an associative array
    $response = curl_exec($ch);
    curl_close($ch);

    // Decode the JSON response
    $data = json_decode($response, true);

    // Check for API errors
    if (isset($data['error'])) {
        return "Error: " . $data['error']['message'];
    }

    // Extract the models from the response
    $models = $data['data'];

    // Sort the models by name
    usort($models, function($a, $b) {
        return $a['id'] <=> $b['id'];
    });

    // Return the list of models
    return $models;

}