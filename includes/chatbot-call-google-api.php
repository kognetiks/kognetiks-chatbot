<?php
/**
 * Kognetiks Chatbot - Google API - Ver 2.3.9
 *
 * This file contains the code accessing the Google's API.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Call the Google API - Ver 2.3.9 - Updated with Gemini 3 Pro recommendations
function chatbot_call_google_api($api_key, $message, $user_id = null, $page_id = null, $session_id = null, $assistant_id = null, $client_message_id = null, $image_data = null) {

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
    $conv_lock = 'chatgpt_conv_lock_' . wp_hash($assistant_id . '|' . $user_id . '|' . $page_id . '|' . $session_id);
    $lock_timeout = 60; // 60 seconds timeout

    // Check for duplicate message UUID in conversation log
    $duplicate_key = 'chatgpt_message_uuid_' . $message_uuid;
    if (get_transient($duplicate_key)) {
        // DIAG - Diagnostics - Ver 2.3.9
        // back_trace( 'NOTICE', 'Duplicate message UUID detected: ' . $message_uuid);
        return "Error: Duplicate request detected. Please try again.";
    }

    // Lock check removed - main send function handles locking
    set_transient($duplicate_key, true, 300); // 5 minutes to prevent duplicates

    // DIAG - Diagnostics - Ver 2.3.9
    back_trace( 'NOTICE', 'chatbot_call_google_api()');
    // back_trace( 'NOTICE', 'BEGIN $user_id: ' . $user_id);
    // back_trace( 'NOTICE', 'BEGIN $page_id: ' . $page_id);
    // back_trace( 'NOTICE', 'BEGIN $session_id: ' . $session_id);
    // back_trace( 'NOTICE', 'BEGIN $thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', 'BEGIN $assistant_id: ' . $assistant_id);

    // Google Generative AI API Documentation
    // https://ai.google.dev/gemini-api/docs

    // Get the base URL directly from Google settings (don't rely on platform choice)
    $google_base_url = esc_attr(get_option('chatbot_google_base_url', 'https://generativelanguage.googleapis.com/v1beta'));
    $google_base_url = rtrim($google_base_url, '/');
    
    // Remove /models if present to ensure we have just the base URL
    if (substr($google_base_url, -7) === '/models') {
        $google_base_url = substr($google_base_url, 0, -7);
    }

    // Get the saved model from the settings or default to "gemini-2.0-flash"
    if (!isset($kchat_settings['model'])) { 
        $model = esc_attr(get_option('chatbot_google_model_choice', 'gemini-2.0-flash'));
    } else {
        $model = $kchat_settings['model'];
    }

    // Check if this is a "Thinking" model to adjust behaviors later
    $is_thinking_model = (strpos($model, 'thinking') !== false);

    // DIAG - Diagnostics - Ver 2.3.9
    // back_trace( 'NOTICE', '$kchat_settings: ' . print_r($kchat_settings, true));
    // back_trace( 'NOTICE', '$model: ' . $model);

    // Google API endpoint format: {base}/models/{model}:generateContent
    $api_url = $google_base_url . '/models/' . $model . ':generateContent';
    
    // Add API key as query parameter
    $api_url = add_query_arg('key', $api_key, $api_url);

    // Max tokens - Increased default for Gemini models (Ver 2.3.9+)
    $max_tokens = intval(esc_attr(get_option('chatbot_google_max_tokens_setting', '1000')));

    // Conversation Context - Ver 2.3.9
    $context = esc_attr(get_option('chatbot_google_conversation_context', 'You are a versatile, friendly, and helpful assistant designed to support me in a variety of tasks that responds in Markdown.'));

    // Context History - Ver 2.3.9
    $chatgpt_last_response = concatenateHistory('chatbot_chatgpt_context_history');
    // DIAG Diagnostics - Ver 2.3.9
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

    // ENHANCED CONTEXT - Select some context to send with the message - Ver 2.3.9
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
        // DIAG Diagnostics - Ver 2.3.9
        // back_trace( 'NOTICE', '$context: ' . $context);

    } else {

        // Original Context Instructions - No Enhanced Context
        $context = $sys_message . ' ' . $chatgpt_last_response . ' ' . $context . ' ' . $chatbot_chatgpt_kn_conversation_context;

    }

    // Conversation Continuity - Ver 2.3.9
    $chatbot_chatgpt_conversation_continuation = esc_attr(get_option('chatbot_chatgpt_conversation_continuation', 'Off'));

    // DIAG Diagnostics - Ver 2.3.9
    // back_trace( 'NOTICE', '$session_id: ' . $session_id);
    // back_trace( 'NOTICE', '$chatbot_chatgpt_conversation_continuation: ' . $chatbot_chatgpt_conversation_continuation);

    if ($chatbot_chatgpt_conversation_continuation == 'On') {
        $conversation_history = chatbot_chatgpt_get_converation_history($session_id);
        $context = $conversation_history . ' ' . $context;
    }

    // Check the length of the context and truncate if necessary - Ver 2.3.9
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

    // DIAG Diagnostics - Ver 2.3.9
    // back_trace( 'NOTICE', '$context: ' . $context);

    // Build the Google API request body

    // Define the header
    $headers = array(
        'Content-Type' => 'application/json'
    );

    // Temperature - Ver 2.3.9
    $temperature = floatval(esc_attr(get_option('chatbot_google_temperature', '0.50')));

    // Top P - Ver 2.3.9
    $top_p = floatval(esc_attr(get_option('chatbot_google_top_p', '1.00')));

    // Generation Configuration
    // BEST PRACTICE: If using a Thinking model, Google often recommends standard or specific temperatures.
    $generationConfig = array(
        'maxOutputTokens' => $max_tokens,
        'temperature'     => $temperature,
        'topP'            => $top_p
    );

    // Optional: Force JSON response if needed (Gemini 1.5+ supports this natively)
    // $generationConfig['responseMimeType'] = 'application/json';

    // BEST PRACTICE: SYSTEM INSTRUCTIONS
    // Instead of faking a user message, use the native systemInstruction field.
    $systemInstruction = null;
    if (!empty($context)) {
        $systemInstruction = array(
            'parts' => array(
                array('text' => $context)
            )
        );
    }

    // BEST PRACTICE: MEDIA RESOLUTION & MULTIMODAL
    // Gemini does not have a "resolution" parameter (Low/High).
    // It analyzes the raw tokens of the image. You must send base64 data.
    // The $image_data variable assumes an array ['mime_type' => 'image/jpeg', 'base64' => '...']
    $user_message_parts = array();

    if (!empty($image_data) && is_array($image_data)) {
        $user_message_parts[] = array(
            'inlineData' => array(
                'mimeType' => $image_data['mime_type'],
                'data'     => $image_data['base64']
            )
        );
    }

    // Add text to the parts
    $user_message_parts[] = array(
        'text' => $message
    );

    // Build the Contents Array
    $contents = array(
        array(
            'role' => 'user',
            'parts' => $user_message_parts
        )
    );

    // Assemble Final Body
    $body = array(
        'contents'         => $contents,
        'generationConfig' => $generationConfig,
        'safetySettings'   => array(
            // BEST PRACTICE: SAFETY SETTINGS
            // Default Gemini settings are strict. This prevents "FinishReason: SAFETY" blocks.
            array(
                'category' => 'HARM_CATEGORY_HARASSMENT',
                'threshold' => 'BLOCK_ONLY_HIGH'
            ),
            array(
                'category' => 'HARM_CATEGORY_HATE_SPEECH',
                'threshold' => 'BLOCK_ONLY_HIGH'
            ),
            array(
                'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                'threshold' => 'BLOCK_ONLY_HIGH'
            ),
            array(
                'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                'threshold' => 'BLOCK_ONLY_HIGH'
            )
        )
    );

    // Add System Instruction if it exists (Supported in v1beta)
    if ($systemInstruction) {
        $body['systemInstruction'] = $systemInstruction;
    }

    // BEST PRACTICE: THINKING CONFIG
    // If this is a Gemini 2.0 Thinking model, we can enable thought visibility.
    // Currently, Google controls the "Level" internally, but we can ask to see the thoughts.
    // Note: This API shape is experimental and subject to change for the 'exp' models.
    /*
    if ($is_thinking_model) {
       // Code to handle thinking config if Google releases specific params for it
       // Currently, it is implicit in the model name.
    }
    */

    // DIAG Diagnostics - Ver 2.3.9
    // back_trace( 'NOTICE', '$body: ' . print_r($body, true));

    $timeout = intval(esc_attr(get_option('chatbot_google_timeout_setting', 240)));

    // Context History - Ver 2.3.9
    addEntry('chatbot_chatgpt_context_history', $message);

    // DIAG Diagnostics - Ver 2.3.9
    // back_trace( 'NOTICE', '$storedc: ' . $chatbot_chatgpt_kn_conversation_context);
    // back_trace( 'NOTICE', '$context: ' . $context);
    // back_trace( 'NOTICE', '$message: ' . $message);  

    // API Call
    $response = wp_remote_post($api_url, array(
        'headers' => $headers,
        'body'    => json_encode($body),
        'timeout' => $timeout,
    ));

    // Handle WP Error
    if (is_wp_error($response)) {

        // DIAG - Diagnostics - Ver 2.3.9
        // back_trace( 'ERROR', 'Error: ' . $response->get_error_message());
        // Clear locks on error
        // Lock clearing removed - main send function handles locking
        return isset($errorResponses['api_error']) ? $errorResponses['api_error'] : 'API Connection Error: ' . $response->get_error_message();

    }

    // Retrieve and Decode Response
    $response_body = json_decode(wp_remote_retrieve_body($response), true);

    // Handle API Errors
    if (isset($response_body['error'])) {

        // Extract error type and message safely
        $error_msg = $response_body['error']['message'] ?? 'Unknown API Error';

        // DIAG - Diagnostics - Ver 2.3.9
        back_trace( 'ERROR', 'Gemini API Error: ' . $error_msg);
        // Clear locks on error
        // Lock clearing removed - main send function handles locking
        return isset($errorResponses['api_error']) ? $errorResponses['api_error'] : 'Error: ' . $error_msg;

    }

    // DIAG - Diagnostics - Ver 2.3.9
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

    // DIAG - Diagnostics - Ver 2.3.9
    // back_trace( 'NOTICE', 'AFTER $user_id: ' . $user_id);
    // back_trace( 'NOTICE', 'AFTER $page_id: ' . $page_id);
    // back_trace( 'NOTICE', 'AFTER $session_id: ' . $session_id);
    // back_trace( 'NOTICE', 'AFTER $thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', 'AFTER $assistant_id: ' . $assistant_id);   

    // Add the usage to the conversation tracker
    // Google API may return usage information in different format
    // back_trace( 'NOTICE', '$response_body: ' . print_r($response_body, true));

    // Extract input and output tokens if available
    $input_tokens = $response_body['usageMetadata']['promptTokenCount'] ?? 0;
    $output_tokens = $response_body['usageMetadata']['candidatesTokenCount'] ?? 0;
    $total_tokens = $response_body['usageMetadata']['totalTokenCount'] ?? 0;

    // DIAG - Diagnostics - Ver 2.3.9
    // back_trace( 'NOTICE', 'Usage - Prompt Tokens: ' . $input_tokens);
    // back_trace( 'NOTICE', 'Usage - Completion Tokens: ' . $output_tokens);
    // back_trace( 'NOTICE', 'Usage - Total Tokens: ' . $total_tokens);

    // Log Tokens
    if (isset($response['response']['code']) && $response['response']['code'] == 200) {
        if ($input_tokens > 0) {
            append_message_to_conversation_log($session_id, $user_id, $page_id, 'Prompt Tokens', null, null, null, $input_tokens);
        }
        if ($output_tokens > 0) {
            append_message_to_conversation_log($session_id, $user_id, $page_id, 'Completion Tokens', null, null, null, $output_tokens);
        }
        if ($total_tokens > 0) {
            append_message_to_conversation_log($session_id, $user_id, $page_id, 'Total Tokens', null, null, null, $total_tokens);
        }
    }

    // Google API uses 'candidates' instead of 'choices'
    if (isset($response_body['candidates'][0])) {
        $candidate = $response_body['candidates'][0];
        $finish_reason = $candidate['finishReason'] ?? 'UNKNOWN';
        
        if ($finish_reason === 'SAFETY') {
            return isset($errorResponses['safety_block']) ? $errorResponses['safety_block'] : 'Blocked by safety filters.';
        }

        // Logic to grab text. Note: In Thinking models, thoughts might be in a separate part
        // but usually, they are concatenated in the first text part or separated.
        // We iterate through parts to ensure we get all text.
        $full_response_text = '';
        if (isset($candidate['content']['parts'])) {
            foreach ($candidate['content']['parts'] as $part) {
                if (isset($part['text'])) {
                    $full_response_text .= $part['text'];
                }
            }
        }

        if (!empty($full_response_text)) {
            // Context History - Ver 2.3.9
            addEntry('chatbot_chatgpt_context_history', $full_response_text);
            // Clear locks on success
            // Lock clearing removed - main send function handles locking
            return $full_response_text;
        }
    }
    
    // If we get here, there was no valid response
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