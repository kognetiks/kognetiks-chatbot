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
    // back_trace( 'NOTICE', 'chatbot_call_google_api()');
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

    // Detect model version and type for compatibility with Gemini 2.0, 3.0, thinking, and non-thinking models
    // Check if this is a "Thinking" model - supports both Gemini 2.0 and 3.0 thinking models
    $is_thinking_model = (
        stripos($model, 'thinking') !== false || 
        stripos($model, 'exp-thinking') !== false ||
        preg_match('/gemini-[23]\.0.*thinking/i', $model)
    );
    
    // Detect Gemini version (2.0 or 3.0) for potential version-specific handling
    $is_gemini_3 = (stripos($model, 'gemini-3') !== false || stripos($model, 'gemini-3.0') !== false);
    $is_gemini_2 = (stripos($model, 'gemini-2') !== false || stripos($model, 'gemini-2.0') !== false);
    
    // DIAG - Diagnostics - Ver 2.3.9+
    // back_trace( 'NOTICE', 'Model: ' . $model . ' | Thinking: ' . ($is_thinking_model ? 'Yes' : 'No') . ' | Version: ' . ($is_gemini_3 ? '3.0' : ($is_gemini_2 ? '2.0' : 'Unknown')));

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
    // Base context for systemInstruction (without conversation history)
    $base_context = esc_attr(get_option('chatbot_google_conversation_context', 'You are a versatile, friendly, and helpful assistant designed to support me in a variety of tasks that responds in Markdown.'));

    // Knowledge Navigator keyword append for context
    $chatbot_chatgpt_kn_conversation_context = esc_attr(get_option('chatbot_chatgpt_kn_conversation_context', 'Yes'));

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
                $base_context = ' When answering the prompt, please consider the following information: ' . implode(' ', $content_texts) . ' ' . $base_context;
            }
        }
    }

    // Build conversation context using standardized function - Ver 2.3.9+
    // This function handles conversation history building, message cleaning, and conversation continuity
    $conversation_context = chatbot_chatgpt_build_conversation_context('google', 10, $session_id);
    $conversation_contents = $conversation_context['messages'];
    
    // Add session history to base context if available (from conversation continuity)
    if (!empty($conversation_context['session_history'])) {
        // Session history is a concatenated string, so we'll add it to base context
        // This provides additional context without breaking the structured contents array
        $base_context = $conversation_context['session_history'] . ' ' . $base_context;
    }

    // Final system context (base instructions only, not conversation history)
    $context = $base_context . ' ' . $chatbot_chatgpt_kn_conversation_context;
    
    // Check the length of the context and truncate if necessary - Ver 2.3.9
    $context_length = intval(strlen($context) / 4); // Assuming 1 token ≈ 4 characters
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
    }

    // DIAG Diagnostics - Ver 2.3.9
    // back_trace( 'NOTICE', '$context: ' . $context);
    // back_trace( 'NOTICE', 'Conversation history pairs: ' . count($conversation_contents) / 2);

    // Build the Google API request body

    // Define the header
    $headers = array(
        'Content-Type' => 'application/json'
    );

    // Temperature - Ver 2.3.9
    $temperature = floatval(esc_attr(get_option('chatbot_google_temperature', '0.50')));

    // Media Resolution - Ver 2.3.9+
    // Note: Google's API doesn't have a direct "resolution" parameter.
    // The resolution is determined by the image data itself (base64 encoded).
    // This setting is stored for potential future use or documentation.
    $media_resolution = esc_attr(get_option('chatbot_google_media_resolution', 'Default'));

    // Thinking Level - Ver 2.3.9+
    $thinking_level = esc_attr(get_option('chatbot_google_thinking_level', 'Low'));

    // Generation Configuration
    // BEST PRACTICE: If using a Thinking model, Google often recommends standard or specific temperatures.
    $generationConfig = array(
        'maxOutputTokens' => $max_tokens,
        'temperature'     => $temperature
    );

    // Optional: Force JSON response if needed (Gemini 1.5+ supports this natively)
    // $generationConfig['responseMimeType'] = 'application/json';

    // BEST PRACTICE: SYSTEM INSTRUCTIONS
    // Use the native systemInstruction field.
    $systemInstruction = null;
    if (!empty($context)) {
        $systemInstruction = array(
            'parts' => array(
                array('text' => $context)
            )
        );
    }

    // BEST PRACTICE: MEDIA RESOLUTION & MULTIMODAL
    // Gemini does not have a direct "resolution" parameter in the API.
    // It analyzes the raw tokens of the image based on the base64 data sent.
    // The $image_data variable assumes an array ['mime_type' => 'image/jpeg', 'base64' => '...']
    // The $media_resolution setting (Default, Low, Medium, High) is stored for reference
    // and potential future use in image preprocessing or documentation.
    $user_message_parts = array();

    if (!empty($image_data) && is_array($image_data)) {
        // DIAG - Diagnostics - Ver 2.3.9+
        // back_trace( 'NOTICE', 'Media Resolution setting: ' . $media_resolution);
        
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

    // Build the Contents Array - Ver 2.3.9+
    // Include conversation history (previous user/model pairs) + current user message
    $contents = $conversation_contents; // Start with conversation history
    
    // Add the current user message at the end
    $contents[] = array(
        'role' => 'user',
        'parts' => $user_message_parts
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
    // Support for both Gemini 2.0 and 3.0 Thinking models
    // The thinking level (Low/High) controls the depth of reasoning.
    // Note: API support for thinking level may vary between versions.
    if ($is_thinking_model) {
        // DIAG - Diagnostics - Ver 2.3.9+
        // back_trace( 'NOTICE', 'Thinking model detected: ' . $model . ' | Thinking Level: ' . $thinking_level);
        
        // For thinking models, we can potentially add thinking-specific configuration
        // Gemini 2.0 and 3.0 thinking models may have different API support
        // Currently, Google controls the thinking behavior internally, but we store
        // the setting for future API support or documentation purposes.
        
        // Gemini 3.0+ may support explicit thinking configuration
        if ($is_gemini_3) {
            // Future: When Gemini 3.0+ API supports explicit thinking level configuration
            // if ($thinking_level === 'High') {
            //     $generationConfig['thinkingConfig'] = array('level' => 'HIGH');
            // } else {
            //     $generationConfig['thinkingConfig'] = array('level' => 'LOW');
            // }
        }
        
        // For Gemini 2.0 thinking models, the thinking behavior is typically implicit
        // but the setting is stored for reference and potential future use
    }

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
        // back_trace( 'ERROR', 'Gemini API Error: ' . $error_msg);
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
    // Compatible with both Gemini 2.0 and 3.0, thinking and non-thinking models
    if (isset($response_body['candidates'][0])) {
        $candidate = $response_body['candidates'][0];
        $finish_reason = $candidate['finishReason'] ?? 'UNKNOWN';
        
        if ($finish_reason === 'SAFETY') {
            return isset($errorResponses['safety_block']) ? $errorResponses['safety_block'] : 'Blocked by safety filters.';
        }

        // Logic to grab text - handles both thinking and non-thinking models
        // For thinking models (Gemini 2.0/3.0), thoughts might be in separate parts
        // or concatenated. We iterate through all parts to ensure we get complete text.
        // For non-thinking models, typically only one text part exists.
        $full_response_text = '';
        if (isset($candidate['content']['parts'])) {
            foreach ($candidate['content']['parts'] as $part) {
                // Extract text from standard text parts
                if (isset($part['text'])) {
                    $full_response_text .= $part['text'];
                }
                
                // For thinking models, handle potential thinking-specific parts
                // (Future: if Google adds explicit thinking part types)
                if ($is_thinking_model && isset($part['thinking'])) {
                    // Thinking models may have separate thinking parts
                    // For now, we include them if present
                    if (isset($part['thinking']['text'])) {
                        // Optionally include thinking process (can be filtered out if needed)
                        // $full_response_text .= "\n[Thinking: " . $part['thinking']['text'] . "]";
                    }
                }
            }
        }

        // Handle alternative response structures (for compatibility with different API versions)
        // Some responses might have text directly in content
        if (empty($full_response_text) && isset($candidate['content']['text'])) {
            $full_response_text = $candidate['content']['text'];
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