<?php
/**
 * Kognetiks Chatbot for WordPress - Anthropic API - Ver 2.0.8
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
function chatbot_call_ant_api($api_key, $message) {

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

    // DIAG - Diagnostics - Ver 1.8.6
    // back_trace( 'NOTICE', 'chatbot_chatgpt_call_api()');
    // back_trace( 'NOTICE', 'BEGIN $user_id: ' . $user_id);
    // back_trace( 'NOTICE', 'BEGIN $page_id: ' . $page_id);
    // back_trace( 'NOTICE', 'BEGIN $session_id: ' . $session_id);
    // back_trace( 'NOTICE', 'BEGIN $thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', 'BEGIN $assistant_id: ' . $assistant_id);

    // Anthropic.com API Documentation
    // https://docs.anthropic.com/en/api/messages

    // The current Anthropic API URL endpoint for claude-3-5-sonnet-20240620
    // $api_url = get_chat_completions_api_url();
    // FIXME - TEMP OVERRIDE
    $api_url = 'https://api.anthropic.com/v1/messages';

    $headers = array(
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type' => 'application/json',
    );

    // Select the Anthropic Model
    // https://docs.anthropic.com/en/docs/about-claude/models
    // 
    // Claude 3.5 Sonnet - claude-3-5-sonnet-20240620
    // Claude 3 Opus - claude-3-opus-20240229
    // Claude 3 Sonnet - claude-3-sonnet-20240229
    // Claude 3 Haiku - claude-3-haiku-20240307
    //

    // Get the saved model from the settings or default to "claude-3-5-sonnet-20240620"
    // $model = esc_attr(get_option('chatbot_chatgpt_model_choice', 'claude-3-5-sonnet-20240620'));
    // FIXME - TEMP OVERRIDE
    $model = 'claude-3-5-sonnet-20240620';
 
    // Max tokens
    $max_tokens = intval(esc_attr(get_option('chatbot_chatgpt_max_tokens_setting', '1024')));

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
    
    // Knowledge Navigator keyword append for context
    $chatbot_chatgpt_kn_conversation_context = get_option('chatbot_chatgpt_kn_conversation_context', '');

    // Append prior message, then context, then Knowledge Navigator - Ver 1.6.1
    // $context = $chatgpt_last_response . ' ' . $context . ' ' . $chatbot_chatgpt_kn_conversation_context;
    // Added "We previously have been talking about the following things: " - Ver 1.9.5 - 2024 04 12
    $sys_message = 'We previously have been talking about the following things: ';

        // DIAG Diagnostics - Ver 1.6.1
    // back_trace( 'NOTICE', '$context: ' . $context);

    //
    // ENHANCED CONTEXT - Select some context to send with the message - Ver 1.9.6
    //
    $useEnhancedContext = esc_attr(get_option('chatbot_chatgpt_use_enhanced_context'), '');

    // DIAG Diagnostics - Ver 1.9.6
    // back_trace( 'NOTICE', '$useEnhancedContext: ' . $useEnhancedContext);

    if ($useEnhancedContext == 'Yes') {

        // DIAG Diagnostics - Ver 1.9.6
        // back_trace( 'NOTICE', '$enhancedContext: ' . $enhancedContext);

        // Focus the content based on the message from the user
        $enhancedContext = kn_enhance_context($message);

        // Original Context Instructions
        // $context = $sys_message . ' Here is some information that might be helpful in responding: ' . $enhancedContext . ' ' . $chatgpt_last_response . ' ' . $context . ' ' . $chatbot_chatgpt_kn_conversation_context;

        // Second attempt at Context Instructions
        // $contextInstructions = ' Here is some information that might be helpful in your response: ';
        // $context = $contextInstructions . ' ' . $enhancedContext . ' ' . $sys_message. ' ' . $chatgpt_last_response . ' ' . $context . ' ' . $chatbot_chatgpt_kn_conversation_context;

        // Third attempt at Context Instructions
        // $contextInstructions = ' Try to only use this information in responding to input. ';
        // $contextInstructions = ' Incorporate this information into your response. ';
        // $context = $contextInstructions . ' ' . $enhancedContext . ' ' . $sys_message. ' ' . $chatgpt_last_response . ' ' . $context . ' ' . $chatbot_chatgpt_kn_conversation_context;

        // Fourth attempt at Context Instructions
        // $contextInstructions = ' Use this information to help guide your response. ';
        // $context = $contextInstructions . ' ' . $enhancedContext . ' ' . $context . ' ' . $chatbot_chatgpt_kn_conversation_context;

        // Fifth attempt at Context Instructions
        $contextInstructions = ' Use this information to help guide your response. ';
        $context = $contextInstructions . ' ' . $enhancedContext . ' ' . $context . ' ' . $chatbot_chatgpt_kn_conversation_context;

        // DIAG Diagnostics - Ver 1.9.6
        // back_trace( 'NOTICE', '$chatbot_chatgpt_kn_conversation_context: ' . $chatbot_chatgpt_kn_conversation_context);

    } else {

        // Original Context Instructions - No Enhanced Context
        $context = $sys_message . ' ' . $chatgpt_last_response . ' ' . $context . ' ' . $chatbot_chatgpt_kn_conversation_context;

    }

    // Build the Anthropic API request body

    // Define the header
    $headers = array(
        'x-api-key' => $api_key,
        'anthropic-version' => '2023-06-01',
        'content-type' => 'application/json'
    );

    // https://docs.anthropic.com/en/docs/about-claude/models#model-names
    // 8192 output tokens is in beta and requires the header anthropic-beta: max-tokens-3-5-sonnet-2024-07-15. If the header is not specified, the limit is 4096 tokens.

    // Define the request body
    $body = array(
        'model' => $model,
        'max_tokens' => $max_tokens,
        'messages' => array(
            array('role' => 'system', 'content' => $context),
            array('role' => 'user', 'content' => $message)
        )
    );

    // Context History - Ver 1.6.1
    addEntry('chatbot_chatgpt_context_history', $message);

    // Convert the body array to JSON
    $body_json = json_encode($body);

    // DIAG Diagnostics - Ver 1.6.1
    // back_trace( 'NOTICE', '$storedc: ' . $chatbot_chatgpt_kn_conversation_context);
    // back_trace( 'NOTICE', '$context: ' . $context);
    // back_trace( 'NOTICE', '$message: ' . $message);  

    // Define the request arguments
    $args = array(
        'headers' => $headers,
        'body' => $body_json,
        'method' => 'POST',
        'data_format' => 'body',
        'timeout' => 50 // Increase the timeout value as needed
    );

    $response = wp_remote_post($api_url, $args);

    // Handle any errors that are returned from the chat engine
    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        // back_trace( 'ERROR', 'Request failed: ' . $error_message);
    } else {
        $response_body = wp_remote_retrieve_body($response);
        // back_trace( 'NOTICE', '$response: ' . print_r($response, true));
    }

    // Return json_decode(wp_remote_retrieve_body($response), true);
    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($response_body['message'])) {
        $response_body['message'] = trim($response_body['message']);
        if (!str_ends_with($response_body['message'], '.')) {
            $response_body['message'] .= '.';
        }
    }

    // DIAG - Diagnostics - Ver 1.8.1
    // back_trace( 'NOTICE', '$response_body: ' . print_r($response_body))

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

    // DIAG - Diagnostics - Ver 1.8.1
    // FIXME - ADD THE USAGE TO CONVERSATION TRACKER
    // back_trace( 'NOTICE', 'Usage - Prompt Tokens: ' . $response_body["usage"]["prompt_tokens"]);
    // back_trace( 'NOTICE', 'Usage - Completion Tokens: ' . $response_body["usage"]["completion_tokens"]);
    // back_trace( 'NOTICE', 'Usage - Total Tokens: ' . $response_body["usage"]["total_tokens"]);

    // Add the usage to the conversation tracker
    if ($response['response']['code'] == 200) {
        append_message_to_conversation_log($session_id, $user_id, $page_id, 'Prompt Tokens', null, null, $response_body["usage"]["prompt_tokens"]);
        append_message_to_conversation_log($session_id, $user_id, $page_id, 'Completion Tokens', null, null, $response_body["usage"]["completion_tokens"]);
        append_message_to_conversation_log($session_id, $user_id, $page_id, 'Total Tokens', null, null, $response_body["usage"]["total_tokens"]);
    }
    
    if (!empty($response_body['choices'])) {
        // Handle the response from the chat engine
        // Context History - Ver 1.6.1
        addEntry('chatbot_chatgpt_context_history', $response_body['choices'][0]['message']['content']);
        return $response_body['choices'][0]['message']['content'];
    } else {
        // FIXME - Decide what to return here - it's an error
        if (get_locale() !== "en_US") {
            $localized_errorResponses = get_localized_errorResponses(get_locale(), $errorResponses);
        } else {
            $localized_errorResponses = $errorResponses;
        }
        // Return a random error message
        return $localized_errorResponses[array_rand($localized_errorResponses)];
    }
    
}
