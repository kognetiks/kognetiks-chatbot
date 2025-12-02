<?php
/**
 * Kognetiks Chatbot - Transformer Model API - Ver 2.2.0
 *
 * This file contains the code accessing the Transformer API.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Call the Transformer Model API
function chatbot_chatgpt_call_transformer_model_api($message, $user_id = null, $page_id = null, $session_id = null, $assistant_id = null, $client_message_id = null) {

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

    global $stopWords;
    
    global $errorResponses;

    // Use client_message_id if provided, otherwise generate a unique message UUID for idempotency
    $message_uuid = $client_message_id ? $client_message_id : wp_generate_uuid4();

    // Lock the conversation BEFORE thread resolution to prevent empty-thread vs real-thread lock split
    $conv_lock = 'chatgpt_conv_lock_' . wp_hash($assistant_id . '|' . $user_id . '|' . $page_id . '|' . $session_id);
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

    // DIAG - Diagnostics - Ver 2.2.0
    // back_trace( 'NOTICE', 'chatbot_call_transformer_model_api()');
    // back_trace( 'NOTICE', 'BEGIN $user_id: ' . $user_id);
    // back_trace( 'NOTICE', 'BEGIN $page_id: ' . $page_id);
    // back_trace( 'NOTICE', 'BEGIN $session_id: ' . $session_id);
    // back_trace( 'NOTICE', 'BEGIN $thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', 'BEGIN $assistant_id: ' . $assistant_id);

    $model = esc_attr(get_option('chatbot_transformer_model_choice', 'sentential-context-model'));
 
    // Max tokens
    $max_tokens = intval(esc_attr(get_option('chatbot_chatgpt_max_tokens_setting', '1000')));

    // Conversation Context - Ver 1.6.1
    $context = esc_attr(get_option('chatbot_chatgpt_conversation_context', 'You are a versatile, friendly, and helpful assistant designed to support me in a variety of tasks that responds in Markdown.'));
 
    // Build conversation context using standardized function - Ver 2.3.9+
    // This function handles conversation history building, message cleaning, and conversation continuity
    // Note: Transformer Model API uses a simple context string, not structured messages
    $conversation_context = chatbot_chatgpt_build_conversation_context('standard', 10, $session_id);
    
    // Knowledge Navigator keyword append for context
    $chatbot_chatgpt_kn_conversation_context = esc_attr(get_option('chatbot_chatgpt_kn_conversation_context', ''));

    // Build a summary of conversation history for context (backward compatibility)
    // Extract text content from structured messages to create a summary string
    $chatgpt_last_response = '';
    if (!empty($conversation_context['messages'])) {
        $message_texts = [];
        foreach ($conversation_context['messages'] as $msg) {
            if (isset($msg['content'])) {
                $message_texts[] = $msg['content'];
            }
        }
        if (!empty($message_texts)) {
            $chatgpt_last_response = implode(' ', $message_texts);
        }
    }

    // Added "We previously have been talking about the following things: " - Ver 1.9.5 - 2024 04 12
    $sys_message = 'We previously have been talking about the following things: ';

    // DIAG Diagnostics - Ver 1.6.1
    // back_trace( 'NOTICE', '$context: ' . $context);

    //
    // ENHANCED CONTEXT - Select some context to send with the message - Ver 1.9.6
    //
    $use_enhanced_content_search = esc_attr(get_option('chatbot_chatgpt_use_advanced_content_search', 'No'));

    // DIAG Diagnostics - Ver 1.9.6
    // back_trace( 'NOTICE', '$use_enhanced_content_search: ' . $use_enhanced_content_search);

    if ($use_enhanced_content_search == 'Yes') {

        // DIAG Diagnostics - Ver 1.9.6
        // back_trace( 'NOTICE', '$enhancedContext: ' . $enhancedContext);

        // Focus the content based on the message from the user
        $enhancedContext = kn_enhance_context($message);

        // Add Context Instructions
        $contextInstructions = ' Use this information to help guide your response. ';
        $context = $contextInstructions . ' ' . $enhancedContext . ' ' . $context . ' ' . $chatbot_chatgpt_kn_conversation_context;

        // DIAG Diagnostics - Ver 1.9.6
        // back_trace( 'NOTICE', '$chatbot_chatgpt_kn_conversation_context: ' . $chatbot_chatgpt_kn_conversation_context);

    } else {

        // Original Context Instructions - No Enhanced Context
        $context = $sys_message . ' ' . $chatgpt_last_response . ' ' . $context . ' ' . $chatbot_chatgpt_kn_conversation_context;

    }

    // Add session history to context if available (from conversation continuity)
    if (!empty($conversation_context['session_history'])) {
        // Session history is a concatenated string, so we'll add it to context
        $context = $conversation_context['session_history'] . ' ' . $context;
    }

    // Context History - Ver 1.6.1
    addEntry('chatbot_chatgpt_context_history', $message);

    // FIXME - LETS OVERRIDE $CONTEXT FOR NOW - Ver 2.2.1 - 2024-12-27
    $context = '';
    $chatbot_chatgpt_kn_conversation_context = '';

    // DIAG Diagnostics - Ver 1.6.1
    // back_trace( 'NOTICE', '$storedc: ' . $chatbot_chatgpt_kn_conversation_context);
    // back_trace( 'NOTICE', '$context: ' . $context);
    // back_trace( 'NOTICE', '$message: ' . $message);

    // Convert $message to an array (this will be used as a starting point)
    // $transformer_model_message = explode(' ', $message);
    $transformer_model_message = $message;

    // Remove the stop words from the message
    // $transformer_model_message = array_diff($transformer_model_message, $stopWords);


    // Initialize the $response_body array to hold the API response
    $response_body = [
        'choices' => [
            [
                'message' => [
                    'content' => ''
                ]
            ]
        ],
        'response' => [
            'code' => 500 // Set a default error code
        ]
    ];

    // Retrieve max tokens from the settings
    $max_tokens = intval(esc_attr(get_option('chatbot_transformer_model_max_tokens', '10000')));

    // DIAG - Diagnostics - Ver 2.2.1
    // back_trace( 'NOTICE', '$model: ' . $model);

    // Call the transformer model with the user input
    if ($model == 'lexical-context-model') {
        // Call the transformer model with the user input - transformer-word-based
        $response = transformer_model_lexical_context_response($transformer_model_message, $max_tokens);
    } elseif ($model == 'sentential-context-model-lite') {
        // Call the transformer model with the user input - transformer-sentence-based
        $response = transformer_model_sentential_context_model_response_lite($transformer_model_message, $max_tokens);

    } elseif ($model == 'sentential-context-model') {
        // Call the transformer model with the user input - transformer-sentence-based
        $response = transformer_model_sentential_context_model_response($transformer_model_message, $max_tokens);
    } else {
        // Incorrect model selected
        $response = 'ERROR: Incorrect model selected. Please check the settings.';
        // Clear locks on error
        // Lock clearing removed - main send function handles locking
    }

    if (!empty($response)) {
        // Prepare the response body
        $response_body['choices'][0]['message']['content'] = trim($response);
        // back_trace( 'NOTICE', '$response_body["choices"][0]["message"]["content"]: ' . $response_body['choices'][0]['message']['content']);
    
        // Remove any trailing comma, colon, semicolon, or spaces and replace them with a period
        $response_body['choices'][0]['message']['content'] = preg_replace('/[,;:\s]+$/', '.', $response_body['choices'][0]['message']['content']);
        // back_trace( 'NOTICE', '$response_body["choices"][0]["message"]["content"]: ' . $response_body['choices'][0]['message']['content']);
    
        // Ensure the message ends with a period, exclamation point, or question mark
        if (!preg_match('/[.!?]$/', $response_body['choices'][0]['message']['content'])) {
            $response_body['choices'][0]['message']['content'] = rtrim($response_body['choices'][0]['message']['content']) . '.';
        }
        // back_trace( 'NOTICE', '$response_body["choices"][0]["message"]["content"]: ' . $response_body['choices'][0]['message']['content']);
            // Set the success response code
        $response_body['response']['code'] = 200; // Success code
    
    } else {
        // Set the error response code
        $response_body['response']['code'] = 500; // Internal server error
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
            $page_id = get_the_ID(); // Get the ID of the queried object if $page_id is not set
        }

    }

    // Before returning count the input words and the generated words
    $word_count = str_word_count($message);
    $response_body["usage"]["prompt_tokens"] = $word_count;
    $word_count = str_word_count($response_body['choices'][0]['message']['content']);
    $response_body["usage"]["completion_tokens"] = $word_count;
    $response_body["usage"]["total_tokens"] = $response_body["usage"]["prompt_tokens"] + $response_body["usage"]["completion_tokens"];

    // DIAG - Diagnostics - Ver 2.1.6
    // back_trace( 'NOTICE', '$response_body["usage"]["prompt_tokens"]: ' . $response_body["usage"]["prompt_tokens"]);
    // back_trace( 'NOTICE', '$response_body["usage"]["completion_tokens"]: ' . $response_body["usage"]["completion_tokens"]);
    
    // Add the usage to the conversation tracker
    if ($response_body['response']['code'] == 200) {
        append_message_to_conversation_log($session_id, $user_id, $page_id, 'Prompt Tokens', null, null, null, $response_body["usage"]["prompt_tokens"]);
        append_message_to_conversation_log($session_id, $user_id, $page_id, 'Completion Tokens', null, null, null, $response_body["usage"]["completion_tokens"]);
        append_message_to_conversation_log($session_id, $user_id, $page_id, 'Total Tokens', null, null, null, $response_body["usage"]["total_tokens"]);
    }
    
    // Handle the response and return it
    if (!empty($response_body['choices'])) {

        // Handle the response from the chat engine
        addEntry('chatbot_chatgpt_context_history', $response_body['choices'][0]['message']['content']);
        // Clear locks on success
        // Lock clearing removed - main send function handles locking
        return $response_body['choices'][0]['message']['content'];

    } else {

        // Decide what to return in case of an error
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
