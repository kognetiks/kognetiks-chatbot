<?php
/**
 * Kognetiks Chatbot - Mistral API - Ver 2.2.2
 *
 * This file contains the code accessing the Mistral's API.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Call the Mistral API
function chatbot_mistral_agent_call_api($api_key, $message, $assistant_id, $thread_id, $session_id, $user_id, $page_id) {

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
    back_trace( 'NOTICE', 'chatbot_call_mistral_api - start');
    back_trace( 'NOTICE', 'chatbot_call_mistral_api - $api_key: ' . $api_key);
    back_trace( 'NOTICE', 'chatbot_call_mistral_api - $message: ' . $message);
    back_trace( 'NOTICE', 'BEGIN $user_id: ' . $user_id);
    back_trace( 'NOTICE', 'BEGIN $page_id: ' . $page_id);
    back_trace( 'NOTICE', 'BEGIN $session_id: ' . $session_id);
    back_trace( 'NOTICE', 'BEGIN $thread_id: ' . $thread_id);
    back_trace( 'NOTICE', 'BEGIN $assistant_id: ' . $assistant_id);

    // Mistral.com API Documentation
    // https://api.mistral.ai/v1/agents/completions

    // The current Mistral API URL endpoint for agents
    $api_url = 'https://api.mistral.ai/v1/agents/completions';

    // DIAG - Diagnostics - Ver 2.2.2
    back_trace( 'NOTICE', '$api_url: ' . $api_url);

    // Get the saved model from the settings or default to "mistral-small-latest"
    $model = esc_attr(get_option('chatbot_mistral_model_choice', 'mistral-small-latest'));
 
    // Max tokens
    $max_tokens = intval(esc_attr(get_option('chatbot_mistral_max_tokens_setting', '1024')));

    // Conversation Context - Ver 1.6.1
    $context = esc_attr(get_option('chatbot_mistral_conversation_context', 'You are a versatile, friendly, and helpful assistant designed to support me in a variety of tasks that responds in Markdown.'));
    $raw_context = $context;
 
    // Context History - Ver 1.6.1
    $chatgpt_last_response = concatenateHistory('chatbot_chatgpt_context_history');
    
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
        // back_trace( 'NOTICE', 'Context truncated to ' . strlen($context) . ' characters.');
    } else {
        // back_trace( 'NOTICE', 'Context length is within limits.');
    }

    // FIXME - Set $context to null - Ver 2.2.2 - 2025-01-16
    // $context = $raw_context;

    // Prepare the request body for Mistral agent API
    $request_body = array(
        'model' => $model,
        'messages' => array(
            array(
                'role' => 'system',
                'content' => $context
            ),
            array(
                'role' => 'user',
                'content' => $message
            )
        ),
        'max_tokens' => $max_tokens,
        'temperature' => 0.7,
        'agent_id' => $assistant_id
    );

    // Add thread_id if available
    if (!empty($thread_id)) {
        $request_body['thread_id'] = $thread_id;
    }

    // Set up the API request
    $args = array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        ),
        'body' => json_encode($request_body),
        'timeout' => 30
    );

    // Make the API call
    $response = wp_remote_post($api_url, $args);

    // Check for errors
    if (is_wp_error($response)) {
        back_trace( 'ERROR', 'Mistral API Error: ' . $response->get_error_message());
        return 'Error: ' . $response->get_error_message();
    }

    // Get the response body
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // Check for API errors
    if (isset($data['error'])) {
        back_trace( 'ERROR', 'Mistral API Error: ' . $data['error']['message']);
        return 'Error: ' . $data['error']['message'];
    }

    // Extract the response text
    if (isset($data['choices'][0]['message']['content'])) {
        $response_text = $data['choices'][0]['message']['content'];
        
        // Store the thread_id if provided in the response
        if (isset($data['thread_id'])) {
            $thread_id = $data['thread_id'];
            set_chatbot_chatgpt_transients('thread_id', $thread_id, $user_id, $page_id, $session_id, null);
        }

        return $response_text;
    }

    return 'Error: Invalid response from Mistral API';
}