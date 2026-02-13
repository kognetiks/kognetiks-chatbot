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
function chatbot_call_deepseek_api($api_key, $message, $user_id = null, $page_id = null, $session_id = null, $assistant_id = null, $client_message_id = null) {

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

    // DIAG - Diagnostics - Ver 2.4.5
    // back_trace("NOTICE", "Starting DeepSeek API call");
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
        return "Error: Duplicate request detected. Please try again.";
    }

    // Lock check removed - main send function handles locking
    set_transient($duplicate_key, true, 120); // 2 minutes to prevent duplicates - Ver 2.3.7

    // DeepSeek.com API Documentation
    // https://api.deepseek.com/chat/completions

    // The current DeepSeek API URL endpoint for deepseek-chat
    // $api_url = 'https://api.deepseek.com/chat/completions';
    $api_url = get_chat_completions_api_url();

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
    $max_tokens = intval(esc_attr(get_option('chatbot_deepseek_max_tokens_setting', '100')));

    // Conversation Context - Ver 1.6.1
    $context = esc_attr(get_option('chatbot_deepseek_conversation_context', 'You are a versatile, friendly, and helpful assistant designed to support me in a variety of tasks that responds in Markdown.'));
    $raw_context = $context;
 
    // Build conversation context using standardized function - Ver 2.3.9+
    // This function handles conversation history building, message cleaning, and conversation continuity
    $conversation_context = chatbot_chatgpt_build_conversation_context('standard', 10, $session_id);
    
    // Knowledge Navigator keyword append for context
    $chatbot_chatgpt_kn_conversation_context = esc_attr(get_option('chatbot_chatgpt_kn_conversation_context', 'Yes'));

    // Build a summary of conversation history for system message (backward compatibility)
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
                $context = ' When answering the prompt, please consider the following information: ' . implode(' ', $content_texts) . ' ' . $context;
            }
        }

    } else {

        // Original Context Instructions - No Enhanced Context
        $context = $sys_message . ' ' . $chatgpt_last_response . ' ' . $context . ' ' . $chatbot_chatgpt_kn_conversation_context;

    }

    // Add session history to context if available (from conversation continuity)
    if (!empty($conversation_context['session_history'])) {
        // Session history is a concatenated string, so we'll add it to context
        $context = $conversation_context['session_history'] . ' ' . $context;
    }

    // Check the length of the context and truncate if necessary - Ver 2.2.6
    $context_length = intval(strlen($context) / 4); // Assuming 1 token ≈ 4 characters
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
    } else {
    }

    // FIXME - Set $context to null - Ver 2.2.2 - 2025-01-16
    // $context = $raw_context;

    // Build the DeepSeek API request body

    // Define the header
    $headers = array(
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . $api_key
    );

    // Build messages array with system message, conversation history, and current user message - Ver 2.3.9+
    $messages = array(
        array('role' => 'system', 'content' => $context)
    );
    
    // Add conversation history messages (structured format for better context) - Ver 2.3.9+
    if (!empty($conversation_context['messages'])) {
        $messages = array_merge($messages, $conversation_context['messages']);
    }
    
    // Add current user message
    $messages[] = array('role' => 'user', 'content' => $message);
    
    // Define the request body
    $body = json_encode(array(
        'model' => $model,
        'max_tokens' => $max_tokens,
        'messages' => $messages,
        'stream' => false,
    ));

    $timeout = esc_attr(get_option('chatbot_deepseek_timeout_setting', 240 ));

    // Context History - Ver 1.6.1
    addEntry('chatbot_chatgpt_context_history', $message);

    // API Call
    $response = wp_remote_post($api_url, array(
        'headers' => $headers,
        'body' => $body,
        'timeout' => $timeout,
    ));

    // Handle WP Error
    if (is_wp_error($response)) {
    
        // DIAG - Diagnostics - Ver 2.4.5
        prod_trace( 'ERROR', 'Error: ' . $response->get_error_message());
        // Clear locks on error
        // Lock clearing removed - main send function handles locking
        return isset($errorResponses['api_error']) ? $errorResponses['api_error'] : 'An API error occurred.';
    
    }
    
    // Retrieve and Decode Response
    $response_body = json_decode(wp_remote_retrieve_body($response));
    
    // Handle API Errors
    if (isset($response_body->error)) {
    
        // Extract error type and message safely
        $error_type = $response_body->error->type ?? 'Unknown Error Type';
        $error_message = $response_body->error->message ?? 'No additional information.';
    
        // DIAG - Diagnostics - Ver 2.4.5
        prod_trace( 'ERROR', 'Error: Type: ' . $error_type . ' Message: ' . $error_message);
        // Clear locks on error
        // Lock clearing removed - main send function handles locking
        return isset($errorResponses['api_error']) ? $errorResponses['api_error'] : 'An error occurred.';
    
    }

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

    // Extract input and output tokens
    $input_tokens = $response_body->usage->prompt_tokens ?? 0;
    $output_tokens = $response_body->usage->completion_tokens ?? 0;
    $total_tokens = $input_tokens + $output_tokens;

    // Check if the response content is not empty
    if (!empty($response_body->choices[0]->message->content)) {
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

    // Access response content properly
    if (isset($response_body->choices[0]->message->content) && !empty($response_body->choices[0]->message->content)) {
        $response_text = $response_body->choices[0]->message->content;
        addEntry('chatbot_chatgpt_context_history', $response_text);
        // Clear locks on success
        // Lock clearing removed - main send function handles locking
        return $response_text;
    } else {
        prod_trace( 'WARNING', 'No valid response text found in API response.');

        $localized_errorResponses = (get_locale() !== "en_US") 
            ? get_localized_errorResponses(get_locale(), $errorResponses) 
            : $errorResponses;

        // Clear locks on error
        // Lock clearing removed - main send function handles locking
        return $localized_errorResponses[array_rand($localized_errorResponses)];
    }
    
}
