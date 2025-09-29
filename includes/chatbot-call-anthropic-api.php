<?php
/**
 * Kognetiks Chatbot - Anthropic API - Ver 2.0.8
 *
 * This file contains the code accessing the Anthropic's API.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Call the Anthropic API
function chatbot_call_anthropic_api($api_key, $message, $user_id = null, $page_id = null, $session_id = null, $assistant_id = null, $client_message_id = null) {

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

    // Use client_message_id if provided, otherwise generate a unique message UUID for idempotency
    $message_uuid = $client_message_id ? $client_message_id : wp_generate_uuid4();

    // Lock the conversation BEFORE thread resolution to prevent empty-thread vs real-thread lock split
    $conv_lock = 'chatgpt_conv_lock_' . md5($assistant_id . '|' . $user_id . '|' . $page_id . '|' . $session_id);
    $lock_timeout = 60; // 60 seconds timeout

    // Check for duplicate message UUID in conversation log
    $duplicate_key = 'chatgpt_message_uuid_' . $message_uuid;
    if (get_transient($duplicate_key)) {
        // DIAG - Diagnostics - Ver 2.3.4
        // back_trace( 'NOTICE', 'Duplicate message UUID detected: ' . $message_uuid);
        return "Error: Duplicate request detected. Please try again.";
    }

    // Lock check removed - main send function handles locking
    set_transient($duplicate_key, true, 300); // 5 minutes to prevent duplicates

    // DIAG - Diagnostics - Ver 1.8.6
  // back_trace( 'NOTICE', 'chatbot_call_anthropic_api()');
  // back_trace( 'NOTICE', 'BEGIN $user_id: ' . $user_id);
  // back_trace( 'NOTICE', 'BEGIN $page_id: ' . $page_id);
  // back_trace( 'NOTICE', 'BEGIN $session_id: ' . $session_id);
  // back_trace( 'NOTICE', 'BEGIN $thread_id: ' . $thread_id);
  // back_trace( 'NOTICE', 'BEGIN $assistant_id: ' . $assistant_id);

    // Anthropic.com API Documentation
    // https://docs.anthropic.com/en/api/messages

    // The current Anthropic API URL endpoint for claude-3-5-sonnet-latest
    // $api_url = 'https://api.anthropic.com/v1/messages';
    $api_url = get_chat_completions_api_url();

    // Select the Anthropic Model
    // https://docs.anthropic.com/en/docs/about-claude/models
    // 
    // Claude 3.5 Sonnet - claude-3-5-sonnet-20241022 or claude-3-5-sonnet-latest
    // Claude 3 Opus - claude-3-opus-20240229 or claude-3-opus-latest
    // Claude 3 Sonnet - claude-3-sonnet-20240229
    // Claude 3 Haiku - claude-3-haiku-20240307
    //

    // Get the saved model from the settings or default to "claude-3-5-sonnet-latest"
    if (!isset($kchat_settings['model'])) { 
        $model = esc_attr(get_option('chatbot_anthropic_model_choice', 'claude-3-5-sonnet-latest'));
    } else {
        $model = $kchat_settings['model'];
    }

    // DIAG - Diagnostics - Ver 2.2.9
    // back_trace( 'NOTICE', '$kchat_settings: ' . print_r($kchat_settings, true));
    // back_trace( 'NOTICE', '$model: ' . $model);
 
    // Max tokens
    $max_tokens = intval(esc_attr(get_option('chatbot_anthropic_max_tokens_setting', '1024')));

    // Conversation Context - Ver 1.6.1
    $context = esc_attr(get_option('chatbot_chatgpt_conversation_context', 'You are a versatile, friendly, and helpful assistant designed to support me in a variety of tasks that responds in Markdown.'));
 
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

    // DIAG Diagnostics - Ver 2.2.9
    // back_trace( 'NOTICE', '$chatgpt_last_response: ' . $chatgpt_last_response);
    
    // Knowledge Navigator keyword append for context
    $chatbot_chatgpt_kn_conversation_context = esc_attr(get_option('chatbot_chatgpt_kn_conversation_context', 'Yes'));

    $sys_message = 'We previously have been talking about the following things: ';

    // ENHANCED CONTEXT - Select some context to send with the message - Ver 2.2.4
    $use_enhanced_content_search = esc_attr(get_option('chatbot_chatgpt_use_advanced_content_search', 'No'));

    if ($use_enhanced_content_search == 'Yes') {

        $search_results = chatbot_chatgpt_content_search($message);
        If ( !empty ($search_results) ) {
            // Extract relevant content from search results array
            $content_texts = [];
            foreach ($search_results['results'] as $result) {
                if (!empty($result['excerpt'])) {
                    $content_texts[] = $result['excerpt'];
                }
            }
            // Join the content texts and append to context
            if (!empty($content_texts)) {
                $context = ' When answering the prompt, please consider the following information: ' . implode(' ', $content_texts);
            }
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
    // back_trace( 'NOTICE', '$context_length: ' . $context_length);
    // FIXME - Define max context length (adjust based on model requirements)
    $max_context_length = 100000; // Estimate at 65536 characters ≈ 16384 tokens
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
        // back_trace( 'NOTICE', 'Context truncated to ' . strlen($context) . ' characters.');
    } else {
        // back_trace( 'NOTICE', 'Context length is within limits.');
    }

    // DIAG Diagnostics - Ver 2.1.8
    // back_trace( 'NOTICE', '$context: ' . $context);

    // Build the Anthropic API request body

    // Define the header
    $headers = array(
        'x-api-key' => $api_key,
        'anthropic-version' => '2023-06-01',
        'content-type' => 'application/json'
    );

    // https://docs.anthropic.com/en/docs/about-claude/models#model-names
    // 8192 output tokens is in beta and requires the header anthropic-beta: max-tokens-3-5-sonnet-2024-07-15. If the header is not specified, the limit is 10000 tokens.

    // Conversation Context - Ver 1.6.1
    $additional_instructions = esc_attr(get_option('chatbot_anthropic_conversation_context', 'You are a versatile, friendly, and helpful assistant designed to support me in a variety of tasks that responds in Markdown.'));
    // DIAG Diagnostics - Ver 2.2.6
    // back_trace( 'NOTICE', '$additional_instructions: ' . $additional_instructions);

    // Revise prompt to include the context, i.e., system instructions
    $prompt = $additional_instructions . ' ' . $context . ' Human: ' . $message . ' Assistant: ';

    // Define the request body
    $body = json_encode(array(
        'model' => $model,
        'max_tokens' => $max_tokens,
        // 'system' => $context, // Top-level parameter for system message
        'messages' => array(
            array(
                'role' => 'user',
                'content' => $prompt, // User input
            ),
        ),
    ));

    // DIAG Diagnostics - Ver 2.2.6
    // back_trace( 'NOTICE', '$body: ' . print_r($body, true));

    $timeout = esc_attr(get_option('chatbot_anthropic_timeout_setting', 240 ));

    // Context History - Ver 1.6.1
    addEntry('chatbot_chatgpt_context_history', $message);

    // Convert the body array to JSON
    $body_json = json_encode($body);

    // DIAG Diagnostics - Ver 1.6.1
    // back_trace( 'NOTICE', '$storedc: ' . $chatbot_chatgpt_kn_conversation_context);
    // back_trace( 'NOTICE', '$context: ' . $context);
    // back_trace( 'NOTICE', '$message: ' . $message);  

    // API Call
    $response = wp_remote_post($api_url, array(
        'headers' => $headers,
        'body'    => $body,
        'timeout' => $timeout,
    ));

    // Handle WP Error
    if (is_wp_error($response)) {

        // DIAG - Diagnostics - Ver 2.3.4
        // back_trace( 'ERROR', 'Error: ' . $response->get_error_message());
        // Clear locks on error
        // Lock clearing removed - main send function handles locking
        return isset($errorResponses['api_error']) ? $errorResponses['api_error'] : 'An API error occurred.';

    }

    // Retrieve and Decode Response
    $response_body = json_decode(wp_remote_retrieve_body($response), true);

    // Handle API Errors
    if (isset($response_body['error'])) {

        // Extract error type and message safely
        $error_type = $response_body['error']['type'] ?? 'Unknown Error Type';
        $error_message = $response_body['error']['message'] ?? 'No additional information.';

        // DIAG - Diagnostics - Ver 2.3.4
        // back_trace( 'ERROR', 'Error: Type: Type: ' . $error_type . ' Message: ' . $error_message);
        // Clear locks on error
        // Lock clearing removed - main send function handles locking
        return isset($errorResponses['api_error']) ? $errorResponses['api_error'] : 'An error occurred.';

    }

    // DIAG - Diagnostics - Ver 1.8.1
    // back_trace( 'NOTICE', '$response_body: ' . print_r($response_body));

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

    // DIAG - Diagnostics - Ver 1.8.6
    // back_trace( 'NOTICE', 'AFTER $user_id: ' . $user_id);
    // back_trace( 'NOTICE', 'AFTER $page_id: ' . $page_id);
    // back_trace( 'NOTICE', 'AFTER $session_id: ' . $session_id);
    // back_trace( 'NOTICE', 'AFTER $thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', 'AFTER $assistant_id: ' . $assistant_id);   

    // Add the usage to the conversation tracker
    // back_trace( 'NOTICE', '$response_body: ' . print_r($response_body, true));

    // Extract input and output tokens
    $input_tokens = $response_body['usage']['input_tokens'] ?? 0;
    $output_tokens = $response_body['usage']['output_tokens'] ?? 0;
    $total_tokens = $input_tokens + $output_tokens;

    // DIAG - Diagnostics - Ver 1.8.1
    // back_trace( 'NOTICE', 'Usage - Prompt Tokens: ' . $input_tokens);
    // back_trace( 'NOTICE', 'Usage - Completion Tokens: ' . $output_tokens);
    // back_trace( 'NOTICE', 'Usage - Total Tokens: ' . $total_tokens);

    if ($response['response']['code'] == 200) {
        append_message_to_conversation_log($session_id, $user_id, $page_id, 'Prompt Tokens', null, null, null, $input_tokens);
        append_message_to_conversation_log($session_id, $user_id, $page_id, 'Completion Tokens', null, null, null, $output_tokens);
        append_message_to_conversation_log($session_id, $user_id, $page_id, 'Total Tokens', null, null, null, $total_tokens);
    }

    if (isset($response_body['content'][0]['text']) && !empty($response_body['content'][0]['text'])) {
        // Handle the response from the chat engine
        // Context History - Ver 1.6.1
        addEntry('chatbot_chatgpt_context_history', $response_body['content'][0]['text']);
        // Clear locks on success
        // Lock clearing removed - main send function handles locking
        return $response_body['content'][0]['text'];
    } else {
        // FIXME - Decide what to return here - it's an error
        if (get_locale() !== "en_US") {
            $localized_errorResponses = get_localized_errorResponses(get_locale(), $errorResponses);
        } else {
            $localized_errorResponses = $errorResponses;
        }
        // Clear locks on error
        // Lock clearing removed - main send function handles locking
        // Return a random error message
        return $localized_errorResponses[array_rand($localized_errorResponses)];
    }
    
}
