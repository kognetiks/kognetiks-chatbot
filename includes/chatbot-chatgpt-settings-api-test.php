<?php
/**
 * Chatbot ChatGPT for WordPress - Settings - API/Model Test
 *
 * This file contains the code for the Chatbot ChatGPT settings page.
 * It allows users to configure the API key and other parameters
 * required to access the ChatGPT API from their own account.
 *
 * @package chatbot-chatgpt
 */

 // If this file is called directly, abort.
if ( ! defined( 'WPINC' ) )
die;

// Test OpenAI Model for any errors - Ver 1.6.3
 function test_chatgpt_api($api_key) {

    // FIXME - ADD AN OPTION TO USE WITHOUT OPENAI API KEY?

    // Reset Status and Error
    update_option('chatbot_chatgpt_api_status','API Error Type: Status Unknown');

    // The current ChatGPT API URL endpoint for GPT-3.5-Turbo and GPT-4
    $api_url = 'https://api.openai.com/v1/chat/completions';

    $headers = array(
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type' => 'application/json',
    );

    // Model and message for testing
    $model = esc_attr(get_option('chatgpt_model_choice', 'gpt-3.5-turbo'));
    // $message = 'Translate the following English text to French: "Hello, world!"';
    $message = 'Test message.';

    $body = array(
        'model' => $model,
        'max_tokens' => 10,
        'temperature' => 0.5,
        'messages' => array(
            array('role' => 'system', 'content' => 'You are a test function for ChatGPT.'),
            array('role' => 'user', 'content' => $message)
        ),
    );

    $args = array(
        'headers' => $headers,
        'body' => json_encode($body),
        'method' => 'POST',
        'data_format' => 'body',
        'timeout' => 50,
    );

    $response = wp_remote_post($api_url, $args);

    if (is_wp_error($response)) {
        return 'WP_Error: ' . $response->get_error_message() . '. Please check Settings for a valid API key or your OpenAI account for additional information.';
    }

    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    // DIAG - Log the response body
    // error_log('response_body: ' . print_r($response_body, true));

    // Check for API-specific errors
    //
    // https://platform.openai.com/docs/guides/error-codes/api-errors
    //
    if (isset($response_body['error'])) {
        $error_type = isset($response_body['error']['type']) ? $response_body['error']['type'] : 'Unknown';
        $error_message = isset($response_body['error']['message']) ? $response_body['error']['message'] : 'No additional information.';
        $updated_status = 'API Error Type: ' . $error_type . ' Message: ' . $error_message;
    } elseif (isset($response_body['choices']) && !empty($response_body['choices'])) {
        $updated_status = 'Success: Connection to ChatGPT API was successful!';
    } else {
        $updated_status = 'Error: Unable to fetch response from ChatGPT API. Please check Settings for a valid API key or your OpenAI account for additional information.';
    }

    update_option('chatbot_chatgpt_api_status', $updated_status);
    $updated_status = get_option('chatbot_chatgpt_api_status', 'NOT SET');
    // TODO - Monitor the chatbot_chatgpt_api_status option for changes
    if('On' === get_option('chatbot_chatgpt_diagnostics')) {
        error_log('chatbot_chatgpt_api_status: ' . esc_html($updated_status));
    }

}


// This function is executed whenever any option is updated
function chatgpt_option_updated($option_name, $old_value, $new_value) {

    // DIAG - Log Function Call
    // error_log('chatgpt_option_updated() called');

    // FIXME Retrieve the current value of the chatbot_chatgpt_api_status option
    // $chatbot_chatgpt_api_status = get_option('chatbot_chatgpt_api_status', 'NOT SET');
    // DIAG - Log the current value of the chatbot_chatgpt_api_status option
    // error_log('chatbot_chatgpt_api_status: ' . $chatbot_chatgpt_api_status);
    
    // Check if the option updated is related to your plugin settings
    // if ($option_name === 'chatgpt_model_choice' || $option_name === 'chatgpt_api_key' || empty($chatbot_chatgpt_api_status)) {
    if ($option_name === 'chatgpt_model_choice' || $option_name === 'chatgpt_api_key') {
        $api_key = get_option('chatgpt_api_key', 'NOT SET');

        // Call your test function
        $test_result = test_chatgpt_api($api_key);
        // DIAG - Log the test result
        // error_log('test_result: ' . $test_result);        

        // DIAG - Set the option in the admin_notice function uses to display messages
        // error_log('$test_result' . $test_result);
        update_option('chatbot_chatgpt_api_status', $test_result);

        // I could directly call display_option_value_admin_notice() here, but
        // that's generally not a good practice unless absolutely necessary
        // display_option_value_admin_notice();
    }
}

// Hook into the updated_option action
add_action('updated_option', 'chatgpt_option_updated', 10, 3);
