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
    die();
}

// Test OpenAI Model for any errors - Ver 1.6.3
 function test_api_status() {

    $chatbot_ai_platform_choice = esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI'));

    // Reset Status and Error
    if ($chatbot_ai_platform_choice == 'OpenAI') {
        update_option('chatbot_chatgpt_api_status', 'API Error Type: Status Unknown');
        $api_key = esc_attr(get_option('chatbot_chatgpt_api_key', 'NOT SET'));
        // Model and message for testing
        $model = esc_attr(get_option('chatbot_chatgpt_model_choice', 'gpt-3.5-turbo'));
    } elseif ($chatbot_ai_platform_choice == 'NVIDIA') {
        update_option('chatbot_nvidia_api_status', 'API Error Type: Status Unknown');
        $api_key = esc_attr(get_option('chatbot_nvidia_api_key', 'NOT SET'));
        // Model and message for testing
        $model = esc_attr(get_option('chatbot_nvidia_model_choice', 'nvidia/llama-3.1-nemotron-51b-instruct'));
    } elseif ($chatbot_ai_platform_choice == 'Markov Chain') {
        update_option('chatbot_markov_chain_api_status', 'API Error Type: Status Unknown');
    }

    // The current API URL endpoint
    // $api_url = 'https://api.openai.com/v1/chat/completions';
    $api_url = get_chat_completions_api_url();

    // DIAG - Diagnostics - Ver 2.1.8
    // back_trace( 'NOTICE', 'API URL: ' . $api_url);

    $headers = array(
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type' => 'application/json',
    );

    // $message = 'Translate the following English text to French: Hello, world!';
    $message = 'Test message.';

    $body = array(
        'model' => $model,
        'max_tokens' => 100,
        'temperature' => 0.5,
        'messages' => array(
            array('role' => 'system', 'content' => 'You are a test function for Chat.'),
            array('role' => 'user', 'content' => $message)
        ),
    );

    // DIAG - Diagnostics - Ver 2.1.8
    // back_trace( 'NOTICE', 'API Body: ' . print_r(json_encode($body),true));

    $args = array(
        'headers' => $headers,
        'body' => json_encode($body),
        'method' => 'POST',
        'data_format' => 'body',
        'timeout' => 50,
    );

    $response = wp_remote_post($api_url, $args);

    // DIAG - Diagnostics - Ver 2.1.8
    // back_trace( 'NOTICE', 'API Response: ' . print_r(json_encode($response),true));

    if (is_wp_error($response)) {
        return 'WP_Error: ' . $response->get_error_message() . '. Please check Settings for a valid API key or your AI Platform vendor account for additional information.';
            // DIAG - Log the response body
            // back_trace( 'ERROR', $response->get_error_message());
    }

    $response_body = json_decode(wp_remote_retrieve_body($response), true);

    // DIAG - Log the response body
    // back_trace( 'NOTICE', '$response_body: ' . print_r($response_body,true));

    // Check for API-specific errors
    //
    // https://platform.openai.com/docs/guides/error-codes/api-errors
    //
    if (isset($response_body['error'])) {
        $error_type = $response_body['error']['type'] ?? 'Unknown';
        $error_message = $response_body['error']['message'] ?? 'No additional information.';
        $updated_status = 'API Error Type: ' . $error_type . ' Message: ' . $error_message;
    } elseif (!empty($response_body['choices'])) {
        $updated_status = 'Success: Connection to the ' . $chatbot_ai_platform_choice . ' API was successful!';
        // back_trace( 'SUCCESS', 'API Status: ' . $updated_status);
    } else {
        $updated_status = 'Error: Unable to fetch response from the ' . $chatbot_ai_platform_choice . ' API. Please check Settings for a valid API key or your ' . $chatbot_ai_platform_choice . ' account for additional information.';
        // back_trace( 'ERROR', 'API Status: ' . $updated_status);
    }

    if ($chatbot_ai_platform_choice == 'OpenAI') {
        update_option('chatbot_chatgpt_api_status', $updated_status);
    } elseif ($chatbot_ai_platform_choice == 'NVIDIA') {
        update_option('chatbot_nvidia_api_status', $updated_status);
    } elseif ($chatbot_ai_platform_choice == 'Markov Chain') {
        update_option('chatbot_markov_chain_api_status', $updated_status);
    }

    return $updated_status;

}

// FIXME - TEST THE ASSISTANT IF PROVIDED - Ver 1.6.7

// This function is executed whenever any option is updated
function chatgpt_option_updated($option_name, $old_value, $new_value) {

    // Check if the "Diagnostics" tab is active
    if ($option_name !== 'chatgpt_model_choice') {
        return;
    } elseif ($option_name !== 'chatbot_chatgpt_api_key') {
        return;
    }

    // DIAG - Log Function Call
    // back_trace( 'NOTICE', 'chatgpt_option_updated() called');

    // FIXME Retrieve the current value of the chatbot_chatgpt_api_status option
    $chatbot_chatgpt_api_status = get_option('chatbot_chatgpt_api_status', 'NOT SET');
    // DIAG - Log the current value of the chatbot_chatgpt_api_status option
    // back_trace( 'NOTICE', $chatbot_chatgpt_api_status);
    
    // Check if the option updated is related to your plugin settings
    // if ($option_name === 'chatbot_chatgpt_model_choice' || $option_name === 'chatbot_chatgpt_api_key' || empty($chatbot_chatgpt_api_status)) {
    if ($option_name === 'chatbot_chatgpt_model_choice' || $option_name === 'chatbot_chatgpt_api_key') {
        $api_key = get_option('chatbot_chatgpt_api_key', 'NOT SET');

        // Call your test function
        $test_result = test_api_status($api_key);
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
