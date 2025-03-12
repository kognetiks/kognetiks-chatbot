<?php
/**
 * Kognetiks Chatbot - DeepSeek API - Ver 2.2.2
 *
 * This file contains the code accessing the DeepSeek's API.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Call the DeepSeek API
function chatbot_call_deepseek_api($api_key, $message) {

    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;
    global $learningMessages;
    global $kchat_settings;
    global $additional_instructions;
    global $model;
    global $voice;
    
    global $errorResponses;

    // DIAG - Diagnostics - Ver 2.2.2
    // back_trace( 'NOTICE', 'chatbot_call_deepseek_api - start');
    // back_trace( 'NOTICE', 'chatbot_call_deepseek_api - $api_key: ' . $api_key);
    // back_trace( 'NOTICE', 'chatbot_call_deepseek_api - $message: ' . $message);
    // back_trace( 'NOTICE', 'BEGIN $user_id: ' . $user_id);
    // back_trace( 'NOTICE', 'BEGIN $page_id: ' . $page_id);
    // back_trace( 'NOTICE', 'BEGIN $session_id: ' . $session_id);
    // back_trace( 'NOTICE', 'BEGIN $thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', 'BEGIN $assistant_id: ' . $assistant_id);

    // DeepSeek.com API Documentation
    // https://api.deepseek.com/chat/completions

    // The current DeepSeek API URL endpoint for deepseek-chat
    // $api_url = 'https://api.deepseek.com/chat/completions';
    $api_url = get_chat_completions_api_url();

    // DIAG - Diagnostics - Ver 2.2.2
    // back_trace( 'NOTICE', '$api_url: ' . $api_url);

    // Select the DeepSeek Model
    // https://api-docs.deepseek.com/quick_start/pricing
    // 
    // DeepSeek Chat - deepseek-chat
    //

    // Get the saved model from the settings or default to "deepseek-chat"
    $model = esc_attr(get_option('chatbot_deepseek_model_choice', 'deepseek-chat'));
    // FIXME - THIS SHOULD BE USING THE $kchat_settings['model'] - Ver 2.2.1
    // $model = $kchat_settings['model'];
 
    // Max tokens
    $max_tokens = intval(esc_attr(get_option('chatbot_deepseek_max_tokens_setting', '1024')));

    // Conversation Context - Ver 1.6.1
    $context = esc_attr(get_option('chatbot_deepseek_conversation_context', 'You are a versatile, friendly, and helpful assistant designed to support me in a variety of tasks that responds in Markdown.'));
    $raw_context = $context;
 
    // Context History - Ver 1.6.1
    $chatgpt_last_response = concatenateHistory('chatbot_chatgpt_context_history');
    // DIAG Diagnostics - Ver 1.6.1
    // back_trace( 'NOTICE', '$chatgpt_last_response: ' . $chatgpt_last_response);
    
    // IDEA Strip any href links and text from the $chatgpt_last_response
    $chatgpt_last_response = preg_replace('/\[URL:.*?\]/', '', $chatgpt_last_response);

    // IDEA Strip any $learningMessages from the $chatgpt_last_response
    if (get_locale() !== "en_US") {
        $localized_learningMessages = get_localized_learningMessages(get_locale(), $learningMessages);
    } else {
        $localized_learningMessages = $learningMessages;
    }
    $chatgpt_last_response = str_replace($localized_learningMessages, '', $chatgpt_last_response);

    // IDEA Strip any $errorResponses from the $chatgpt_last_response
    if (get_locale() !== "en_US") {
        $localized_errorResponses = get_localized_errorResponses(get_locale(), $errorResponses);
    } else {
        $localized_errorResponses = $errorResponses;
    }
    $chatgpt_last_response = str_replace($localized_errorResponses, '', $chatgpt_last_response);
    
    // Knowledge Navigator keyword append for context
    $chatbot_chatgpt_kn_conversation_context = esc_attr(get_option('chatbot_chatgpt_kn_conversation_context', 'Yes'));

    $sys_message = 'We previously have been talking about the following things: ';

    // ENHANCED CONTEXT - Select some context to send with the message - Ver 2.2.4
    $use_enhanced_content_search = esc_attr(get_option('chatbot_chatgpt_use_advanced_content_search', 'No'));

    if ($use_enhanced_content_search == 'Yes') {

        $search_results = ' When answering the prompt, please consider the following information: ' . chatbot_chatgpt_content_search($message);
        If ( !empty ($search_results) ) {
            // Append the transformer context to the prompt
            $context = $search_results;
        }
        // DIAG Diagnostics - Ver 2.2.4 - 2025-02-04
        // back_trace( 'NOTICE', '$context: ' . $context);

    } else {

        // Original Context Instructions - No Enhanced Context
        $context = $sys_message . ' ' . $chatgpt_last_response . ' ' . $context . ' ' . $chatbot_chatgpt_kn_conversation_context;

    }

    // Conversation Continuity - Ver 2.1.8
    $chatbot_chatgpt_conversation_continuation = esc_attr(get_option('chatbot_chatgpt_conversation_continuation', 'Off'));

    // DIAG Diagnostics - Ver 2.1.8
    // back_trace( 'NOTICE', '$session_id: ' . $session_id);
    // back_trace( 'NOTICE', '$chatbot_chatgpt_conversation_continuation: ' . $chatbot_chatgpt_conversation_continuation);

    if ($chatbot_chatgpt_conversation_continuation == 'On') {
        $conversation_history = chatbot_chatgpt_get_converation_history($session_id);
        $context = $conversation_history . ' ' . $context;
    }

    // Check the length of the context and truncate if necessary - Ver 2.2.6
    $context_length = intval(strlen($context) / 4); // Assuming 1 token ≈ 4 characters
    // back_trace('NOTICE', '$context_length: ' . $context_length);
    // FIXME - Define max context length (adjust based on model requirements)
    $max_context_length = 65536; // Example: 65536 characters ≈ 16384 tokens
    if ($context_length > $max_context_length) {
        // Truncate to the max length
        $truncated_context = substr($context, 0, $max_context_length);
        // Ensure truncation happens at the last complete word
        $truncated_context = preg_replace('/\s+[^\s]*$/', '', $truncated_context);
        // Fallback if regex fails (e.g., no spaces in the string)
        if (empty($truncated_context)) {
            $truncated_context = substr($context, 0, $max_context_length);
        }
        $context = $truncated_context;
        // back_trace('NOTICE', 'Context truncated to ' . strlen($context) . ' characters.');
    } else {
        // back_trace('NOTICE', 'Context length is within limits.');
    }

    // FIXME - Set $context to null - Ver 2.2.2 - 2025-01-16
    // $context = $raw_context;

    // Build the DeepSeek API request body

    // Define the header
    $headers = array(
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . $api_key
    );

    // Define the request body
    $body = json_encode(array(
        'model' => $model,
        'max_tokens' => $max_tokens,
        'messages' => array(
            array(
                'role' => 'system',
                'content' => $context, // System input
            ),
            array(
                'role' => 'user',
                'content' => $message, // User input
            ),
        ),
        'stream' => false,
    ));

    $timeout = esc_attr(get_option('chatbot_deepseek_timeout_setting', 240 ));

    // Context History - Ver 1.6.1
    addEntry('chatbot_chatgpt_context_history', $message);

    // DIAG - Diagnostics - Ver 2.2.2
    // back_trace( 'NOTICE', '$body: ' . $body);

    // Convert the body array to JSON
    $body_json = json_encode($body);

    // DIAG Diagnostics - Ver 1.6.1
    // back_trace( 'NOTICE', '$storedc: ' . $chatbot_chatgpt_kn_conversation_context);
    // back_trace( 'NOTICE', '$context: ' . $context);
    // back_trace( 'NOTICE', '$message: ' . $message);  

    // API Call
    $response = wp_remote_post($api_url, array(
        'headers' => $headers,
        'body' => $body,
        'timeout' => $timeout,
    ));

    // Handle WP Error
    if (is_wp_error($response)) {
    
        // DIAG - Diagnostics
        prod_trace( 'ERROR', 'Error: ' . $response->get_error_message());
        return isset($errorResponses['api_error']) ? $errorResponses['api_error'] : 'An API error occurred.';
    
    }
    
    // Retrieve and Decode Response
    $response_body = json_decode(wp_remote_retrieve_body($response));
    
    // Handle API Errors
    if (isset($response_body->error)) {
    
        // Extract error type and message safely
        $error_type = $response_body->error->type ?? 'Unknown Error Type';
        $error_message = $response_body->error->message ?? 'No additional information.';
    
        // DIAG - Diagnostics
        prod_trace( 'ERROR', 'Error: Type: ' . $error_type . ' Message: ' . $error_message);
        return isset($errorResponses['api_error']) ? $errorResponses['api_error'] : 'An error occurred.';
    
    }

    // DIAG - Diagnostics - Ver 1.8.1
    // back_trace( 'NOTICE', '$response_body: ' . print_r($response_body, true));

    // Get the user ID and page ID
    if (empty($user_id)) {
        $user_id = get_current_user_id(); // Get current user ID
    }
    if (empty($page_id)) {
        $page_id = get_the_id(); // Get current page ID
        if (empty($page_id)) {
            // $page_id = get_queried_object_id(); // Get the ID of the queried object if $page_id is not set
            // Changed - Ver 1.9.1 - 2024 03 05
            $page_id = get_the_ID(); // Get the ID of the queried object if $page_id is not set
        }
    }

    // DIAG - Diagnostics - Ver 2.2.2
    // back_trace( 'NOTICE', 'AFTER $user_id: ' . $user_id);
    // back_trace( 'NOTICE', 'AFTER $page_id: ' . $page_id);
    // back_trace( 'NOTICE', 'AFTER $session_id: ' . $session_id);
    // back_trace( 'NOTICE', 'AFTER $thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', 'AFTER $assistant_id: ' . $assistant_id);   

    // DIAG - Diagnostics - Ver 1.8.1
    // back_trace( 'NOTICE', '$response_body: ' . print_r($response_body, true));

    // Extract input and output tokens
    $input_tokens = $response_body->usage->prompt_tokens ?? 0;
    $output_tokens = $response_body->usage->completion_tokens ?? 0;
    $total_tokens = $input_tokens + $output_tokens;

    // DIAG - Diagnostics - Ver 1.8.1
    // back_trace( 'NOTICE', 'Usage - Prompt Tokens: ' . $input_tokens);
    // back_trace( 'NOTICE', 'Usage - Completion Tokens: ' . $output_tokens);
    // back_trace( 'NOTICE', 'Usage - Total Tokens: ' . $total_tokens);

    // Check if the response content is not empty
    if (!empty($response_body->choices[0]->message->content)) {
        if ($input_tokens > 0) {
            append_message_to_conversation_log($session_id, $user_id, $page_id, 'Prompt Tokens', null, null, $input_tokens);
        }

        if ($output_tokens > 0) {
            append_message_to_conversation_log($session_id, $user_id, $page_id, 'Completion Tokens', null, null, $output_tokens);
        }

        if ($total_tokens > 0) {
            append_message_to_conversation_log($session_id, $user_id, $page_id, 'Total Tokens', null, null, $total_tokens);
        }
    }

    // Access response content properly
    if (isset($response_body->choices[0]->message->content) && !empty($response_body->choices[0]->message->content)) {
        $response_text = $response_body->choices[0]->message->content;
        addEntry('chatbot_chatgpt_context_history', $response_text);
        return $response_text;
    } else {
        prod_trace( 'WARNING', 'No valid response text found in API response.');

        $localized_errorResponses = (get_locale() !== "en_US") 
            ? get_localized_errorResponses(get_locale(), $errorResponses) 
            : $errorResponses;

        return $localized_errorResponses[array_rand($localized_errorResponses)];
    }
    
}