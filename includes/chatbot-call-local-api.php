<?php
/**
 * Kognetiks Chatbot - Local API - Ver 2.3.3
 *
 * This file contains the code accessing the Jan.ai local API server.
 * Models are started manually in Jan.ai - no automatic starting needed.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Call the Local API
function chatbot_chatgpt_call_local_model_api($message) {

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

    // Jan.ai Download
    // https://jan.ai/download

    // Jan.ai API Documentation
    // https://jan.ai/docs

    // Jan.ai Quick Start Guide
    // https://jan.ai/docs/quickstart

    // The current Local API URL endpoint
    $api_url = get_chat_completions_api_url();

    // In Jan.ai, models start automatically on first chat request
    // No need for manual model starting or seeding - Ver 2.3.3 - 2025-08-13
    $model = esc_attr(get_option('chatbot_local_model_choice', 'llama3.2-3b-instruct'));
    prod_trace('NOTICE', 'Using model: ' . $model . ' - will start automatically on first use');

    // API key for the local server - Typically not needed
    $api_key = esc_attr(get_option('chatbot_local_api_key', ''));
    // Decrypt the API key - Ver 2.2.6
    $api_key = chatbot_chatgpt_decrypt_api_key($api_key);

    $headers = array(
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type'  => 'application/json',
    );

    // Retrieve model settings
    $model = esc_attr(get_option('chatbot_local_model_choice', 'llama3.2-3b-instruct'));
    $max_tokens = intval(get_option('chatbot_local_max_tokens_setting', 2000)); // Reduced from 10000 to 2000 for local models
    $temperature = floatval(get_option('chatbot_local_temperature', 0.8));
    $top_p = floatval(get_option('chatbot_local_top_p', 0.95));
    $context = esc_attr(get_option('chatbot_local_conversation_context', 'You are a versatile, friendly, and helpful assistant that responds using Markdown syntax.'));
    $timeout = intval(get_option('chatbot_local_timeout_setting', 360));

    // Conversation Context - Ver 1.6.1
    $context = esc_attr(get_option('chatbot_chatgpt_conversation_context', 'You are a versatile, friendly, and helpful assistant designed to support me in a variety of tasks that responds in Markdown.'));
 
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

    } else {

        // Original Context Instructions - No Enhanced Context
        $context = $sys_message . ' ' . $chatgpt_last_response . ' ' . $context . ' ' . $chatbot_chatgpt_kn_conversation_context;

    }

    // Conversation Continuity - Ver 2.1.8
    $chatbot_chatgpt_conversation_continuation = esc_attr(get_option('chatbot_chatgpt_conversation_continuation', 'Off'));

    if ($chatbot_chatgpt_conversation_continuation == 'On') {
        $conversation_history = chatbot_chatgpt_get_converation_history($session_id);
        $context = $conversation_history . ' ' . $context;
    }

    // Check the length of the context and truncate if necessary - Ver 2.3.3 - 2025-08-13
    // More conservative token estimation for local models
    $context_length = intval(strlen($context) / 3); // Assuming 1 token ≈ 3 characters (more conservative)
    
    // For local models, use a much smaller context to avoid "context size exceeded" errors
    // Most local models have 4K-8K context windows, so we'll be very conservative
    $max_context_length = 8000; // Conservative estimate for local models
    
    if ($context_length > $max_context_length) {
        // Truncate to the max length
        $truncated_context = substr($context, 0, $max_context_length * 3); // Convert back to characters
        
        // Ensure truncation happens at the last complete word
        $truncated_context = preg_replace('/\s+[^\s]*$/', '', $truncated_context);
        
        // Fallback if regex fails (e.g., no spaces in the string)
        if (empty($truncated_context)) {
            $truncated_context = substr($context, 0, $max_context_length * 3);
        }
        
        // Add a note that context was truncated
        $context = $truncated_context . ' [Context truncated due to length limits]';
        
        prod_trace('NOTICE', 'Context truncated from ' . $context_length . ' to ' . $max_context_length . ' estimated tokens');
    }

    // Construct request body to match the expected schema
    $body = array(
        'model' => $model,
        'messages' => array(
            array('role' => 'system', 'content' => $context),
            array('role' => 'user', 'content' => $message)
        ),
        'max_tokens' => $max_tokens,
        'temperature' => $temperature,
        'top_p' => $top_p,
        'stream' => false,
        'n' => 1
    );

    // Remove any null or empty values that might cause issues
    $body = array_filter($body, function($value) {
        return $value !== null && $value !== '';
    });

    // API request arguments
    $args = array(
        'headers' => $headers,
        'body'    => json_encode($body),
        'method'  => 'POST',
        'timeout' => $timeout,
        'data_format' => 'body',
    );

    // Send request
    $response = wp_remote_post($api_url, $args);

    // Decode the response
    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    $response_code = wp_remote_retrieve_response_code($response);

    // Handle request errors
    if (is_wp_error($response)) {
        return 'Error: ' . $response->get_error_message() . ' Please check Settings for a valid API key.';
    }

    // Check for HTTP error status codes
    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code >= 400) {
        $error_body = wp_remote_retrieve_body($response);
        $error_message = 'HTTP ' . $response_code . ' Error: ' . $error_body;
        
        // Log the error details for debugging
        prod_trace('ERROR', 'Jan.ai API Error: ' . $error_message);
        prod_trace('ERROR', 'Request URL: ' . $api_url);
        prod_trace('ERROR', 'Request Body: ' . json_encode($body));
        
        return 'Error: ' . $error_message . ' Please check the request format and try again.';
    }

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

    // Extract input and output tokens
    $input_tokens = $response_body['usage']['prompt_tokens'] ?? 0;
    $output_tokens = $response_body['usage']['completion_tokens'] ?? 0;
    $total_tokens = $input_tokens + $output_tokens;

    if ($response['response']['code'] == 200) {

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

    if (isset($response_body['choices'][0]['message']['content']) && !empty($response_body['choices'][0]['message']['content'])) {
        $response_text = $response_body['choices'][0]['message']['content'];
        
        // Clean up special tokens that local models often include
        $response_text = chatbot_local_clean_response_text($response_text);
        
        addEntry('chatbot_chatgpt_context_history', $response_text);
        return $response_text;
    } else {
        prod_trace('WARNING', 'No valid response text found in API response.');
    
        $localized_errorResponses = (get_locale() !== "en_US") 
            ? get_localized_errorResponses(get_locale(), $errorResponses) 
            : $errorResponses;
    
        return $localized_errorResponses[array_rand($localized_errorResponses)];
    }

}

// Clean up response text from local models - Ver 2.3.3 - 2025-08-13
function chatbot_local_clean_response_text($text) {

    // DiAG - Diagnostics
    back_trace('NOTICE', 'raw $text: ' . $text);

    // First, try to extract just the final message content
    // Look for the pattern that indicates the final assistant response
    $final_pattern = '/<\|start\|>assistant<\|channel\|>final<\|message\|>(.*?)(?:<\||$)/s';
    
    if (preg_match($final_pattern, $text, $matches)) {
        $text = $matches[1]; // Extract just the content after the final message marker
        back_trace('NOTICE', 'extracted final message: ' . $text);
    } else {
        // Fallback: if we can't find the final message pattern, clean up the whole text
        back_trace('NOTICE', 'final message pattern not found, cleaning entire text');
        
        // Remove common special tokens that local models include - more aggressive cleaning
        $patterns = array(
            // Complete token pairs
            '/<\|channel\|>[^<]*<\/\|channel\|>/',           // <|channel|>content</|channel|>
            '/<\|message\|>[^<]*<\/\|message\|>/',           // <|message|>content</|message|>
            '/<\|start\|>[^<]*<\/\|start\|>/',               // <|start|>content</|start|>
            '/<\|end\|>[^<]*<\/\|end\|>/',                   // <|end|>content</|end|>
            
            // Incomplete token pairs
            '/<\|channel\|>[^<]*<\|/',                       // <|channel|>content<|
            '/<\|message\|>[^<]*<\|/',                       // <|message|>content<|
            '/<\|start\|>[^<]*<\|/',                         // <|start|>content<|
            '/<\|end\|>[^<]*<\|/',                           // <|end|>content<|
            
            // Tokens at end of text
            '/<\|channel\|>[^<]*$/',                         // <|channel|>content at end
            '/<\|message\|>[^<]*$/',                         // <|message|>content at end
            '/<\|start\|>[^<]*$/',                           // <|start|>content at end
            '/<\|end\|>[^<]*$/',                             // <|end|>content at end
            
            // Any <|token|> format
            '/<\|[^>]*\|>/',                                 // Any <|token|> format
            
            // Partial tokens at end
            '/<\|[^>]*$/',                                   // Any <|token at end
            
            // Additional patterns for partial tokens
            '/message\|>[^<]*/',                             // message|>content
            '/start\|>[^<]*/',                               // start|>content
            '/channel\|>[^<]*/',                             // channel|>content
            '/end\|>[^<]*/',                                 // end|>content
            
            // Catch any remaining partial tokens
            '/[a-z]+\|[^<]*/',                               // word|content
            '/[a-z]+\|[^>]*/',                               // word|content>
            
            // Remove any remaining pipe patterns
            '/\|[^<]*/',                                     // |content
            '/\|[^>]*/',                                     // |content>
        );
        
        $text = preg_replace($patterns, '', $text);
    }
    
    // Clean up extra whitespace and newlines
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);
    
    // DiAG - Diagnostics
    back_trace('NOTICE', 'cleaned $text: ' . $text);
    
    return $text;
}

// Fetch the local models - Ver 2.3.3 - 2025-08-11
function chatbot_local_get_models() {

    // DiAG - Diagnostics
    back_trace('NOTICE', 'chatbot_local_get_models');

    $base    = esc_url_raw(get_option('chatbot_local_base_url', 'http://127.0.0.1:1337/v1'));
    $api_url = trailingslashit($base) . 'models';

    // API key (required by Jan local server)
    $api_key_enc = get_option('chatbot_local_api_key', '');
    $api_key     = chatbot_chatgpt_decrypt_api_key($api_key_enc);
    if (!$api_key) {
        // DiAG - Diagnostics
        prod_trace('ERROR', 'JAN API key missing. Set one in Jan (Settings → Local API Server) and in plugin settings.');
        return array('llama3.2-3b-instruct'); // safe fallback
    }

    $headers = array(
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type'  => 'application/json',
    );

    $response = wp_remote_get($api_url, array(
        'headers'  => $headers,
        'timeout'  => 15,
        'blocking' => true,
        // 'sslverify' => false, // only if you're using self-signed HTTPS
    ));

    if (is_wp_error($response)) {
        prod_trace('ERROR', 'JAN /models failed: ' . $response->get_error_message());
        return array('llama3.2-3b-instruct');
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    if ($code < 200 || $code >= 300) {
        // DiAG - Diagnostics
        prod_trace('ERROR', sprintf('JAN /models non-2xx (%d). Body: %s', $code, $body));
        return array('llama3.2-3b-instruct');
    }

    $json = json_decode($body, true);

    // DiAG - Diagnostics
    back_trace('NOTICE', '$response_body: ' . print_r($json, true));

    $models = array();

    // Accept either {"data":[...]} or [...]
    $list = (is_array($json) && isset($json['data']) && is_array($json['data'])) ? $json['data'] : $json;

    if (is_array($list)) {
        foreach ($list as $m) {
            // OpenAI-compatible shape: { id: "name", object: "model", ... }
            if (is_array($m) && isset($m['id']) && is_string($m['id'])) {
                $models[] = $m['id'];
            } elseif (is_string($m)) {
                // super-permissive fallback if server returns ["id1","id2"]
                $models[] = $m;
            }
        }
    }

    if (empty($models)) {
        // DiAG - Diagnostics
        prod_trace('WARNING', 'No models parsed from /models. Body: ' . $body);
        // Friendly fallback so UI doesn't break
        $models = array('llama3.2-3b-instruct');
    }

    return array_values(array_unique($models));
    
}

