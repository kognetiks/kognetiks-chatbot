<?php
/**
 * Kognetiks Chatbot for WordPress - ChatGPT API - Ver 1.6.9
 *
 * This file contains the code for accessing the ChatGPT API.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Call the ChatGPT API
function chatbot_chatgpt_call_api($api_key, $message) {

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

    // The current ChatGPT API URL endpoint for gpt-3.5-turbo and gpt-4
    // $api_url = 'https://api.openai.com/v1/chat/completions';
    $api_url = get_chat_completions_api_url();

    $headers = array(
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type' => 'application/json',
    );

    // Select the OpenAI Model
    // Get the saved model from the settings or default to "gpt-3.5-turbo"
    $model = esc_attr(get_option('chatbot_chatgpt_model_choice', 'gpt-3.5-turbo'));
 
    // Max tokens - Ver 1.4.2
    $max_tokens = intval(esc_attr(get_option('chatbot_chatgpt_max_tokens_setting', '500')));

    // Conversation Context - Ver 1.6.1
    $context = esc_attr(get_option('chatbot_chatgpt_conversation_context', 'You are a versatile, friendly, and helpful assistant designed to support me in a variety of tasks that responds in Markdown.'));

    // Temperature - Ver 2.1.8
    $temperature = floatval(esc_attr(get_option('chatbot_chatgpt_temperature', '0.5')));

    // Top P - Ver 2.1.8
    $top_p = floatval(esc_attr(get_option('chatbot_chatgpt_top_p', '1.0')));
 
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

    // Conversation Continuity - Ver 2.1.8
    $chatbot_chatgpt_conversation_continuation = esc_attr(get_option('chatbot_chatgpt_conversation_continuation', 'Off'));

    // DIAG Diagnostics - Ver 2.1.8
    // back_trace( 'NOTICE', '$session_id: ' . $session_id);
    // back_trace( 'NOTICE', '$chatbot_chatgpt_conversation_continuation: ' . $chatbot_chatgpt_conversation_continuation);

    if ($chatbot_chatgpt_conversation_continuation == 'On') {
        $conversation_history = chatbot_chatgpt_get_converation_history($session_id);
        $context = $conversation_history . ' ' . $context;
    }

    // DIAG Diagnostics - Ver 2.1.8
    // back_trace( 'NOTICE', '$context: ' . $context);

    // Added Role, System, Content Static Variable - Ver 1.6.0
    $body = array(
        'model' => $model,
        'max_tokens' => $max_tokens,
        'temperature' => $temperature,
        'top_p' => $top_p,
        'messages' => array(
            array('role' => 'system', 'content' => $context),
            array('role' => 'user', 'content' => $message)
            ),
    );

    // FIXME - Allow for file uploads here
    // $file = 'path/to/file';

    // Context History - Ver 1.6.1
    addEntry('chatbot_chatgpt_context_history', $message);

    // DIAG Diagnostics - Ver 1.6.1
    // back_trace( 'NOTICE', '$storedc: ' . $chatbot_chatgpt_kn_conversation_context);
    // back_trace( 'NOTICE', '$context: ' . $context);
    // back_trace( 'NOTICE', '$message: ' . $message);

    $chatbot_chatgpt_timeout = intval(esc_attr(get_option('chatbot_chatgpt_timeout_setting', '50')));

    $args = array(
        'headers' => $headers,
        'body' => json_encode($body),
        'method' => 'POST',
        'data_format' => 'body',
        'timeout' => $chatbot_chatgpt_timeout, // Increase the timeout values to 15 seconds to wait just a bit longer for a response from the engine
    );

    $response = wp_remote_post($api_url, $args);
    // DIAG - Diagnostics - Ver 1.6.7
    // back_trace( 'NOTICE', '$response: ' . print_r($response, true));

    // Handle any errors that are returned from the chat engine
    if (is_wp_error($response)) {
        return 'Error: ' . $response->get_error_message().' Please check Settings for a valid API key or your OpenAI account for additional information.';
    }

    // DIAG - Diagnostics - Ver 1.8.6
    // back_trace( 'NOTICE', print_r($response, true));

    // Return json_decode(wp_remote_retrieve_body($response), true);
    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($response_body['message'])) {
        $response_body['message'] = trim($response_body['message']);
        if (!str_ends_with($response_body['message'], '.')) {
            $response_body['message'] .= '.';
        }
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
