<?php
/**
 * Kognetiks Chatbot for WordPress - Settings - API/Model Test
 *
 * This file contains the code for the Chatbot settings page.
 * It allows users to configure the API key and other parameters
 * required to access the ChatGPT API from their own account.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Test OpenAI Model for any errors - Ver 1.6.3
 function test_chatgpt_api($api_key) {

    // FIXME - ADD AN OPTION TO USE WITHOUT OPENAI API KEY?

    // Reset Status and Error
    update_option('chatbot_chatgpt_api_status','API Error Type: Status Unknown');

    // The current ChatGPT API URL endpoint for GPT-3.5-Turbo and GPT-4
    // $api_url = 'https://api.openai.com/v1/chat/completions';
    $api_url = get_chat_completions_api_url();

    $headers = array(
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type' => 'application/json',
    );

    // Model and message for testing
    $model = esc_attr(get_option('chatbot_chatgpt_model_choice', 'gpt-3.5-turbo'));
    // FIXME - For now switch gpt-4-turbo back got gpt-4-1106-preview
    if ($model == 'gpt-4-turbo') {
        $model = 'gpt-4-1106-preview';
    } 
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
            // DIAG - Log the response body
            // back_trace( 'ERROR', $response->get_error_message());
    }

    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    // DIAG - Log the response body
    // back_trace( 'NOTICE', $response_body);

    // Check for API-specific errors
    //
    // https://platform.openai.com/docs/guides/error-codes/api-errors
    //
    if (isset($response_body['error'])) {
        $error_type = $response_body['error']['type'] ?? 'Unknown';
        $error_message = $response_body['error']['message'] ?? 'No additional information.';
        $updated_status = 'API Error Type: ' . $error_type . ' Message: ' . $error_message;
    } elseif (!empty($response_body['choices'])) {
        $updated_status = 'Success: Connection to the OpenAI API was successful!';
        // back_trace( 'SUCCESS', 'chatbot_chatgpt_api_status' . $updated_status);
    } else {
        $updated_status = 'Error: Unable to fetch response from ChatGPT API. Please check Settings for a valid API key or your OpenAI account for additional information.';
        // back_trace( 'ERROR', $updated_status);
    }

    update_option('chatbot_chatgpt_api_status', $updated_status);
    $updated_status = get_option('chatbot_chatgpt_api_status', 'NOT SET');
    // TODO - Monitor the chatbot_chatgpt_api_status option for changes
    // back_trace( 'WARNING', 'chatbot_chatgpt_api_status: ' . esc_html($updated_status));

    return $updated_status;

}

// FIXME - TEST THE ASSISTANT IF PROVIDED - Ver 1.6.7

// This function is executed whenever any option is updated
function chatgpt_option_updated($option_name, $old_value, $new_value): void {

    // Check if the "Diagnostics" tab is active
    if ($option_name !== 'chatgpt_model_choice') {
        return;
    } elseif ($option_name !== 'chatbot_chatgpt_api_key') {
        return;
    }

    // DIAG - Log Function Call
    // back_trace( 'NOTICE, 'chatgpt_option_updated() called');

    // FIXME Retrieve the current value of the chatbot_chatgpt_api_status option
    $chatbot_chatgpt_api_status = get_option('chatbot_chatgpt_api_status', 'NOT SET');
    // DIAG - Log the current value of the chatbot_chatgpt_api_status option
    // back_trace( 'NOTICE', $chatbot_chatgpt_api_status);
    
    // Check if the option updated is related to your plugin settings
    // if ($option_name === 'chatbot_chatgpt_model_choice' || $option_name === 'chatbot_chatgpt_api_key' || empty($chatbot_chatgpt_api_status)) {
    if ($option_name === 'chatbot_chatgpt_model_choice' || $option_name === 'chatbot_chatgpt_api_key') {
        $api_key = get_option('chatbot_chatgpt_api_key', 'NOT SET');

        // Call your test function
        $test_result = test_chatgpt_api($api_key);
        // DIAG - Log the test result
        // back_trace( 'WARNING', '$test_result' . $test_result);        

        // DIAG - Set the option in the admin_notice function uses to display messages
        update_option('chatbot_chatgpt_api_status', $test_result);

        // I could directly call display_option_value_admin_notice() here, but
        // that's generally not a good practice unless absolutely necessary
        // display_option_value_admin_notice();

    }
}

// Hook into the updated_option action
add_action('updated_option', 'chatgpt_option_updated', 10, 3);
