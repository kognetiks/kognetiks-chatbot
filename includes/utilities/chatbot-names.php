<?php
/**
 * Kognetiks Chatbot - Retrieves the Name of the Assistant
 *
 * This file contains the code to retrieve the name of the Assistant
 * from the OpenAI platform.  It uses the Assistant ID to make the request.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Function to get the Assistant's name
function get_chatbot_chatgpt_assistant_name($assistant_id_lookup) {

    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;
    global $kchat_settings;
    global $additional_instructions;
    global $model;
    global $voice;

    global $chatbot_chatgpt_display_style;
    global $chatbot_chatgpt_assistant_alias;

    $api_key = '';

    // Retrieve the API key
    $api_key = esc_attr(get_option('chatbot_chatgpt_api_key'));

    // Ensure API key is set
    if (empty($api_key)) {
        // back_trace( 'ERROR', 'Missing API key for retrieving assistant name.');
        return false;
    }

    // Set the OpenAI API URL
    $url = "https://api.openai.com/v1/assistants/" . urlencode($assistant_id_lookup);

    // Set HTTP request arguments
    $args = array(
        'method'  => 'GET',
        'headers' => array(
            'Content-Type'  => 'application/json',
            'OpenAI-Beta'   => 'assistants=v2',
            'Authorization' => 'Bearer ' . $api_key
        ),
        'timeout' => 15, // Avoid long waits
    );

    // Make the request using WP HTTP API
    $response = wp_remote_get($url, $args);

    // Check for errors
    if (is_wp_error($response)) {
        prod_trace( 'ERROR', 'Error fetching Assistant name: ' . $response->get_error_message());
        return false;
    }

    // Retrieve response body
    $response_body = wp_remote_retrieve_body($response);

    // Decode JSON response
    $data = json_decode($response_body, true);

    // Validate JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        prod_trace( 'ERROR', 'Invalid JSON response from OpenAI API.');
        return false;
    }

    // Check for API errors in the response
    if (isset($data['error'])) {
        prod_trace( 'ERROR', 'OpenAI API Error: ' . $data['error']['message']);
        return false;
    }

    // Return the Assistant's name if it exists
    return $data['name'] ?? false;

}
