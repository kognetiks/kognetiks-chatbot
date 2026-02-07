<?php
/**
 * Kognetiks Chatbot - Markov Chain API - Ver 2.0.8
 *
 * This file contains the code accessing the Markov Chain API.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Call the Markov Chain API
function chatbot_chatgpt_call_markov_chain_api($message, $user_id = null, $page_id = null, $session_id = null, $assistant_id = null, $client_message_id = null) {

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

    // DIAG - Diagnostics - Ver 2.4.4
    // back_trace("NOTICE", "Starting Markov Chain API call");
    // back_trace("NOTICE", "Message: " . $message);
    // back_trace("NOTICE", "User ID: " . $user_id);
    // back_trace("NOTICE", "Page ID: " . $page_id);
    // back_trace("NOTICE", "Session ID: " . $session_id);
    // back_trace("NOTICE", "Assistant ID: " . $assistant_id);
    // back_trace("NOTICE", "Client Message ID: " . $client_message_id);

    // Use client_message_id if provided, otherwise generate a unique message UUID for idempotency
    $message_uuid = $client_message_id ? $client_message_id : wp_generate_uuid4();

    // Lock the conversation BEFORE thread resolution to prevent empty-thread vs real-thread lock split
    $conv_lock = 'chatgpt_conv_lock_' . wp_hash($assistant_id . '|' . $user_id . '|' . $page_id . '|' . $session_id);
    $lock_timeout = 60; // 60 seconds timeout

    // Check for duplicate message UUID in conversation log
    $duplicate_key = 'chatgpt_message_uuid_' . $message_uuid;
    if (get_transient($duplicate_key)) {
        // DIAG - Diagnostics - Ver 2.3.4
        return "Error: Duplicate request detected. Please try again.";
    }

    // Lock check removed - main send function handles locking
    set_transient($duplicate_key, true, 120); // 2 minutes to prevent duplicates - Ver 2.3.7

    // DIAG - Diagnostics - Ver 1.8.6

    $model = esc_attr(get_option('chatbot_markov_chain_model_choice', 'markov-chain-flask'));
 
    // Max tokens
    $max_tokens = intval(esc_attr(get_option('chatbot_chatgpt_max_tokens_setting', '1000')));

    // Conversation Context - Ver 1.6.1
    $context = esc_attr(get_option('chatbot_chatgpt_conversation_context', 'You are a versatile, friendly, and helpful assistant designed to support me in a variety of tasks that responds in Markdown.'));
 
    // Build conversation context using standardized function - Ver 2.3.9+
    // This function handles conversation history building, message cleaning, and conversation continuity
    // Note: Markov Chain API uses a simple context string, not structured messages
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

    //
    // ENHANCED CONTEXT - Select some context to send with the message - Ver 1.9.6
    //
    $use_enhanced_content_search = esc_attr(get_option('chatbot_chatgpt_use_advanced_content_search', 'No'));

    // DIAG Diagnostics - Ver 1.9.6

    if ($use_enhanced_content_search == 'Yes') {

        // DIAG Diagnostics - Ver 1.9.6

        // Focus the content based on the message from the user
        $enhancedContext = kn_enhance_context($message);

        // Add Context Instructions
        $contextInstructions = ' Use this information to help guide your response. ';
        $context = $contextInstructions . ' ' . $enhancedContext . ' ' . $context . ' ' . $chatbot_chatgpt_kn_conversation_context;

        // DIAG Diagnostics - Ver 1.9.6

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

    // DIAG Diagnostics - Ver 1.6.1

    // Convert $message to an array (this will be used as a starting point)
    $mc_message = explode(' ', $message);

    // Remove the stop words from the message
    // $mc_message = array_diff($mc_message, $stopWords);

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
    $max_tokens = intval(esc_attr(get_option('chatbot_markov_chain_max_tokens', '500')));

    // Check if the Markov Chain exists
    // if (empty($markovChain)) {
    //     // If no Markov Chain found, return an error code and message
    //     $response_body['choices'][0]['message']['content'] = 'No Markov Chain found.';
    //     $response_body['response']['code'] = 500; // Internal server error
    // } else {
    //     // Call the Markov Chain generator using the retrieved Markov Chain and user input
    //     $response = generate_markov_text_beaker_model($mc_message, $max_tokens);

    //     // Prepare the response body
    //     $response_body['choices'][0]['message']['content'] = trim($response);

    //     // Ensure the message ends with a period
    //     if (!str_ends_with($response_body['choices'][0]['message']['content'], '.')) {
    //         $response_body['choices'][0]['message']['content'] .= '.';
    //     }

    //     // Set the success response code
    //     $response_body['response']['code'] = 200; // Success code
    // }

    // Markov Model Names - 2024 11 24
    // Flask: Precursor stage for foundational elements.
    // Beaker: Small-scale, foundational stage—perfect for initial lexical analysis or simple models.
    // Bucket: A step up, handling larger datasets or more complex lexical processes.
    // Barrel: Substantially greater capacity, signaling robust intermediate processing or models.
    // Vat: The pinnacle of processing—handling massive, industrial-scale lexical or sentential progression.
    // Tank: For even larger or more advanced processes.
    // Reservoir: Denoting a vast storage or synthesis capability.

    // Call the Markov Chain generator using the retrieved Markov Chain and user input
    $response = chatbot_markov_chain_decode($mc_message, $max_tokens);

    if (!empty($response)) {
        // Prepare the response body
        $response_body['choices'][0]['message']['content'] = trim($response);
    
        // Remove any trailing comma, colon, semicolon, or spaces and replace them with a period
        $response_body['choices'][0]['message']['content'] = preg_replace('/[,;:\s]+$/', '.', $response_body['choices'][0]['message']['content']);
    
        // Ensure the message ends with a period, exclamation point, or question mark
        if (!preg_match('/[.!?]$/', $response_body['choices'][0]['message']['content'])) {
            $response_body['choices'][0]['message']['content'] = rtrim($response_body['choices'][0]['message']['content']) . '.';
        }
    
        // Set the success response code
        $response_body['response']['code'] = 200; // Success code
    
    } else {
        // Set the error response code
        $response_body['response']['code'] = 500; // Internal server error
    }
        
    // DIAG - Diagnostics - Ver 1.8.1

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
