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

// Call the Anthropic API
function chatbot_chatgpt_call_markov_chain_api($message) {

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

    // DIAG - Diagnostics - Ver 1.8.6
    // back_trace( 'NOTICE', 'chatbot_chatgpt_mc_api()');
    // back_trace( 'NOTICE', 'BEGIN $user_id: ' . $user_id);
    // back_trace( 'NOTICE', 'BEGIN $page_id: ' . $page_id);
    // back_trace( 'NOTICE', 'BEGIN $session_id: ' . $session_id);
    // back_trace( 'NOTICE', 'BEGIN $thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', 'BEGIN $assistant_id: ' . $assistant_id);

    $model = esc_attr(get_option('chatbot_markov_chain_model_choice', 'markov-chain-flask'));
 
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
    $chatbot_chatgpt_kn_conversation_context = esc_attr(get_option('chatbot_chatgpt_kn_conversation_context', ''));

    // Append prior message, then context, then Knowledge Navigator - Ver 1.6.1
    // $context = $chatgpt_last_response . ' ' . $context . ' ' . $chatbot_chatgpt_kn_conversation_context;
    // Added "We previously have been talking about the following things: " - Ver 1.9.5 - 2024 04 12
    $sys_message = 'We previously have been talking about the following things: ';

    // DIAG Diagnostics - Ver 1.6.1
    // back_trace( 'NOTICE', '$context: ' . $context);

    //
    // ENHANCED CONTEXT - Select some context to send with the message - Ver 1.9.6
    //
    $useEnhancedContext = esc_attr(get_option('chatbot_chatgpt_use_enhanced_context', 'No'));

    // DIAG Diagnostics - Ver 1.9.6
    // back_trace( 'NOTICE', '$useEnhancedContext: ' . $useEnhancedContext);

    if ($useEnhancedContext == 'Yes') {

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

    
    // Conversation Continuity - Ver 2.1.8
    $chatbot_chatgpt_conversation_continuation = esc_attr(get_option('chatbot_chatgpt_conversation_continuation', 'Off'));

    // DIAG Diagnostics - Ver 2.1.8
    // back_trace( 'NOTICE', '$session_id: ' . $session_id);
    // back_trace( 'NOTICE', '$chatbot_chatgpt_conversation_continuation: ' . $chatbot_chatgpt_conversation_continuation);

    if ($chatbot_chatgpt_conversation_continuation == 'On') {
        $conversation_history = chatbot_chatgpt_get_converation_history($session_id);
        $context = $conversation_history . ' ' . $context;
    }

    // Context History - Ver 1.6.1
    addEntry('chatbot_chatgpt_context_history', $message);

    // DIAG Diagnostics - Ver 1.6.1
    // back_trace( 'NOTICE', '$storedc: ' . $chatbot_chatgpt_kn_conversation_context);
    // back_trace( 'NOTICE', '$context: ' . $context);
    // back_trace( 'NOTICE', '$message: ' . $message);

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
        // back_trace( 'NOTICE', '$response_body["choices"][0]["message"]["content"]: ' . $response_body['choices'][0]['message']['content']);
    
        // Remove any trailing comma, colon, semicolon, or spaces and replace them with a period
        $response_body['choices'][0]['message']['content'] = preg_replace('/[,;:\s]+$/', '.', $response_body['choices'][0]['message']['content']);
        // back_trace( 'NOTICE', '$response_body["choices"][0]["message"]["content"]: ' . $response_body['choices'][0]['message']['content']);
    
        // Ensure the message ends with a period, exclamation point, or question mark
        if (!preg_match('/[.!?]$/', $response_body['choices'][0]['message']['content'])) {
            $response_body['choices'][0]['message']['content'] .= '.';
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
        append_message_to_conversation_log($session_id, $user_id, $page_id, 'Prompt Tokens', null, null, $response_body["usage"]["prompt_tokens"]);
        append_message_to_conversation_log($session_id, $user_id, $page_id, 'Completion Tokens', null, null, $response_body["usage"]["completion_tokens"]);
        append_message_to_conversation_log($session_id, $user_id, $page_id, 'Total Tokens', null, null, $response_body["usage"]["total_tokens"]);
    }
    
    // Handle the response and return it
    if (!empty($response_body['choices'])) {

        // Handle the response from the chat engine
        addEntry('chatbot_chatgpt_context_history', $response_body['choices'][0]['message']['content']);
        return $response_body['choices'][0]['message']['content'];

    } else {

        // Decide what to return in case of an error
        if (get_locale() !== "en_US") {
            $localized_errorResponses = get_localized_errorResponses(get_locale(), $errorResponses);
        } else {
            $localized_errorResponses = $errorResponses;
        }
        // Return a random error message
        return $localized_errorResponses[array_rand($localized_errorResponses)];

    }
    
}
