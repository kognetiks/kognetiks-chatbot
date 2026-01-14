<?php
/**
 * Kognetiks Chatbot - NVIDIA API - Ver 1.6.9
 *
 * This file contains the code for accessing the NVIDIA API.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Call the ChatGPT API
function chatbot_nvidia_call_api($api_key, $message, $user_id = null, $page_id = null, $session_id = null, $assistant_id = null, $client_message_id = null) {

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
        // DIAG - Diagnostics - Ver 2.3.4
        return "Error: Duplicate request detected. Please try again.";
    }

    // Lock check removed - main send function handles locking
    set_transient($duplicate_key, true, 120); // 2 minutes to prevent duplicates - Ver 2.3.7

    // DIAG - Diagnostics - Ver 1.8.6

    // The current NVIDIA API URL endpoint for chat completions
    // $api_url = 'https://integrate.api.nvidia.com/v1';
    $api_url = get_chat_completions_api_url();

    // DIAG - Diagnostics - Ver 2.1.8

    $headers = array(
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type' => 'application/json',
    );

    // Select the NVIDIA Model
    // Get the saved model from the settings or default to "nvidia/llama-3.1-nemotron-51b-instruct"
    $model = esc_attr(get_option('chatbot_nvidia_model_choice', 'nvidia/llama-3.1-nemotron-51b-instruct'));
 
    // Max tokens
    $max_tokens = intval(esc_attr(get_option('chatbot_nvidia_max_tokens_setting', '1000')));

    // Conversation Context
    $context = esc_attr(get_option('chatbot_nvidia_conversation_context', 'You are a versatile, friendly, and helpful assistant designed to support me in a variety of tasks that responds in Markdown.'));

    // Temperature - Ver 2.1.8
    $temperature = floatval(esc_attr(get_option('chatbot_nvidia_temperature', '0.5')));

    // Top P - Ver 2.1.8
    $top_p = floatval(esc_attr(get_option('chatbot_nvidia_top_p', '1.0')));

    // Build conversation context using standardized function - Ver 2.3.9+
    // This function handles conversation history building, message cleaning, and conversation continuity
    $conversation_context = chatbot_chatgpt_build_conversation_context('standard', 10, $session_id);
    
    // Knowledge Navigator keyword append for context
    $chatbot_chatgpt_kn_conversation_context = esc_attr(get_option('chatbot_chatgpt_kn_conversation_context', ''));

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
        // DIAG Diagnostics - Ver 2.2.4 - 2025-02-04

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
    } else {
    }
    
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
    
    // Added Role, System, Content Static Variable - Ver 1.6.0
    $body = array(
        'model' => $model,
        'max_tokens' => $max_tokens,
        'temperature' => $temperature,
        'top_p' => $top_p,
        'messages' => $messages,
    );

    // DIAG - Diagnostics - Ver 2.1.8

    // FIXME - Allow for file uploads here
    // $file = 'path/to/file';

    // Context History - Ver 1.6.1
    addEntry('chatbot_chatgpt_context_history', $message);

    // DIAG Diagnostics - Ver 1.6.1

    $chatbot_nvidia_timeout = intval(esc_attr(get_option('chatbot_nvidia_timeout_setting', '50')));

    $args = array(
        'headers' => $headers,
        'body' => json_encode($body),
        'method' => 'POST',
        'data_format' => 'body',
        'timeout' => $chatbot_nvidia_timeout, // Increase the timeout values to 15 seconds to wait just a bit longer for a response from the engine
        );

    // DIAG - Diagnostics - Ver 2.1.8
    
    $response = wp_remote_post($api_url, $args);

    // DIAG - Diagnostics - Ver 2.1.8

    // Handle any errors that are returned from the chat engine
    if (is_wp_error($response)) {
        // Clear locks on error
        // Lock clearing removed - main send function handles locking
        return 'Error: ' . $response->get_error_message().' Please check Settings for a valid API key or your NVIDIA account for additional information.';
    }

    // Check for HTTP error status codes
    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code >= 400) {
        $error_body = wp_remote_retrieve_body($response);
        
        // Try to parse JSON error response for better error messages
        $error_data = json_decode($error_body, true);
        
        if (isset($error_data['error']['message'])) {
            $error_message = $error_data['error']['message'];
            $error_type = $error_data['error']['type'] ?? 'unknown';
            
            // Handle specific error types with user-friendly messages
            if ($response_code == 429) {
                $user_message = 'NVIDIA API is currently at capacity. Please try again in a few moments.';
            } elseif ($response_code == 401) {
                $user_message = 'NVIDIA API authentication failed. Please check your API key in settings.';
            } elseif ($response_code == 403) {
                $user_message = 'NVIDIA API access forbidden. Please check your API permissions.';
            } else {
                $user_message = 'NVIDIA API error: ' . $error_message;
            }
            
            // DIAG - Diagnostics
            prod_trace('ERROR', 'NVIDIA API Error (HTTP ' . $response_code . '): ' . $error_type . ' - ' . $error_message);
            
            // Clear locks on error
            // Lock clearing removed - main send function handles locking
            return $user_message;
        } else {
            // Fallback for non-JSON error responses
            $error_message = 'HTTP ' . $response_code . ' Error: ' . $error_body;
            
            // DIAG - Diagnostics
            prod_trace('ERROR', 'NVIDIA API Error: ' . $error_message);
            
            // Clear locks on error
            // Lock clearing removed - main send function handles locking
            return 'NVIDIA API error occurred. Please check Settings for a valid API key or your NVIDIA account for additional information.';
        }
    }

    // DIAG - Diagnostics - Ver 1.8.6

    // Return json_decode(wp_remote_retrieve_body($response), true);
    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    
    // Check for API errors in response body
    if (isset($response_body['error'])) {
        $error_message = $response_body['error']['message'] ?? 'Unknown error';
        $error_type = $response_body['error']['type'] ?? 'unknown';
        
        // DIAG - Diagnostics
        prod_trace('ERROR', 'NVIDIA API Error: ' . $error_type . ' - ' . $error_message);
        
        // Clear locks on error
        // Lock clearing removed - main send function handles locking
        return 'NVIDIA API error: ' . $error_message;
    }

    // if (isset($response_body['message'])) {
    //     $response_body['message'] = trim($response_body['message']);
    //     if (!str_ends_with($response_body['message'], '.')) {
    //         $response_body['message'] .= '.';
    //     }
    // }

    if (isset($response_body['choices'][0]['message']['content'])) {
        // Extract the assistant's message content
        $message_content = trim($response_body['choices'][0]['message']['content']);   
        // Ensure the response ends with a period
        if (!str_ends_with($message_content, '.')) {
            $message_content .= '.';
        }
    }

    // DIAG - Diagnostics - Ver 1.8.1

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

    // DIAG - Diagnostics - Ver 1.8.1
    // FIXME - ADD THE USAGE TO CONVERSATION TRACKER

    // Add the usage to the conversation tracker
    if ($response_code == 200 && isset($response_body['usage'])) {
        $input_tokens = $response_body['usage']['prompt_tokens'] ?? 0;
        $output_tokens = $response_body['usage']['completion_tokens'] ?? 0;
        $total_tokens = $response_body['usage']['total_tokens'] ?? 0;
        
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
    
    // Debug: Log response structure if no content found
    if (!isset($response_body['choices']) || !isset($response_body['choices'][0])) {
        prod_trace('WARNING', 'NVIDIA API response structure unexpected. Response code: ' . $response_code . ', Response: ' . print_r($response_body, true));
    }
    
    if (!empty($response_body['choices']) && isset($response_body['choices'][0]['message']['content'])) {
        // Handle the response from the chat engine
        $response_text = $response_body['choices'][0]['message']['content'];
        
        // Context History - Ver 1.6.1
        addEntry('chatbot_chatgpt_context_history', $response_text);
        // Clear locks on success
        // Lock clearing removed - main send function handles locking
        return $response_text;
    
    } else {
        // Log the issue for debugging
        prod_trace('WARNING', 'No valid response text found in NVIDIA API response. Response code: ' . $response_code . ', Response: ' . print_r($response_body, true));
        
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
