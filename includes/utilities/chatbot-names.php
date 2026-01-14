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

    // DIAG - Diagnostics - Ver 2.2.6

    $api_key = '';

    $chatbot_ai_platform_choice = esc_attr(get_option('chatbot_ai_platform_choice'), 'OpenAI');
    // DIAG - Diagnostics - Ver 2.2.6

    if ( $chatbot_ai_platform_choice == 'OpenAI' ) {

        // Retrieve the API key
        $api_key = esc_attr(get_option('chatbot_chatgpt_api_key'));
        // Decrypt the API key - Ver 2.2.6
        $api_key = chatbot_chatgpt_decrypt_api_key($api_key);

        // Ensure API key is set
        if (empty($api_key)) {
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

    } elseif ( $chatbot_ai_platform_choice == 'Azure OpenAI' ) {

        // DIAG - Diagnostics - Ver 2.2.6

        // Retrieve the API key
        $api_key = esc_attr(get_option('chatbot_azure_api_key'));
        // Decrypt the API key - Ver 2.2.6
        $api_key = chatbot_chatgpt_decrypt_api_key($api_key);

        // Set the Azure OpenAI API URL
        // https://YOUR_RESOURCE_NAME.openai.azure.com/openai/assistants/{assistant_id}?api-version=2024-08-01-preview

        $chatbot_azure_resource_name = esc_attr(get_option('chatbot_azure_resource_name', 'YOUR_RESOURCE_NAME'));
        $chatbot_azure_api_version = esc_attr(get_option('chatbot_azure_api_version', '2024-08-01-preview'));
    
        // Assemble the URL for deletion
        $url = 'https://' . $chatbot_azure_resource_name . '.openai.azure.com/openai/assistants/' . urlencode($assistant_id_lookup) . '?api-version=' . $chatbot_azure_api_version;

        // Set HTTP request arguments
        $args = array(
            'method'  => 'GET',
            'headers' => array(
                'Content-Type' => 'application/json',
                'api-key'      => trim($api_key),
            ),
            'timeout' => 15, // Avoid long waits
        );

    } elseif ( $chatbot_ai_platform_choice == 'Mistral' ) {

        // Look up the Agent's name in the Assistants table
        // DIAG - Diagnostics - Ver 2.3.0
        
        // Prepare the query of the database
        global $wpdb;
        $table_name = $wpdb->prefix . 'chatbot_chatgpt_assistants';
        $query = $wpdb->prepare("SELECT * FROM $table_name WHERE assistant_id = %s", $assistant_id_lookup);
        
        // DIAG - Diagnostics - Ver 2.3.0
        
        $result = $wpdb->get_results($query);
        
        // DIAG - Diagnostics - Ver 2.3.0

        // Return the Agent's name
        return $result[0]->common_name;

    } else {

        return false;

    }

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
    // DIAG - Diagnostics - Ver 2.2.6

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
