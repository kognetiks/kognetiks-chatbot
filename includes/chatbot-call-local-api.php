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
function chatbot_chatgpt_call_local_model_api($message, $user_id = null, $page_id = null, $session_id = null, $assistant_id = null, $client_message_id = null) {

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
    // back_trace("NOTICE", "Starting Local API call");
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

    // API key for the local server (required by Jan.ai)
    $api_key = esc_attr(get_option('chatbot_local_api_key', ''));
    // Decrypt the API key - Ver 2.2.6
    $api_key = chatbot_chatgpt_decrypt_api_key($api_key);

    if (empty($api_key)) {
        delete_transient($duplicate_key);
        return 'Error: Local API key is missing. Add your Jan.ai API key under API/Local settings and try again.';
    }

    $headers = array(
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type'  => 'application/json',
        'Connection'    => 'close',
    );

    // Retrieve model settings
    $model = esc_attr(get_option('chatbot_local_model_choice', 'llama3.2-3b-instruct'));
    $max_tokens = intval(get_option('chatbot_local_max_tokens_setting', 1000)); // Reduced from 10000 to 1000 for local models
    $temperature = floatval(get_option('chatbot_local_temperature', 0.8));
    $top_p = floatval(get_option('chatbot_local_top_p', 0.95));
    $context = esc_attr(get_option('chatbot_local_conversation_context', 'You are a versatile, friendly, and helpful assistant that responds using Markdown syntax.'));
    $timeout = min(max(5, intval(get_option('chatbot_local_timeout_setting', 120))), 180);

    // Jan.ai handles one completion at a time; hold off rapid follow-ups while the model finishes
    $jan_busy_key = 'chatbot_local_jan_busy_' . wp_hash((string) $assistant_id . '|' . (string) $user_id . '|' . (string) $page_id . '|' . (string) $session_id);
    $jan_cooldown_seconds = chatbot_local_get_jan_cooldown_seconds();
    if ($jan_cooldown_seconds > 0) {
        $jan_busy_since = get_transient($jan_busy_key);
        if ($jan_busy_since !== false && (time() - (int) $jan_busy_since) < $jan_cooldown_seconds) {
            delete_transient($duplicate_key);
            return 'Error: Jan.ai is still finishing the previous reply. Please wait ' . $jan_cooldown_seconds . ' seconds and try again.';
        }
    }
    set_transient($jan_busy_key, time(), $timeout + 60);

    // Keep local prompts small — only the last exchange, not full site-enhanced context
    $conversation_context = chatbot_chatgpt_build_conversation_context('standard', 2, $session_id);

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

    if (!empty($chatgpt_last_response)) {
        $context = 'Recent conversation: ' . $chatgpt_last_response . ' ' . $context;
    }

    // Context History - keep user/assistant pairs aligned (same as other API handlers)
    addEntry('chatbot_chatgpt_context_history', $message);

    // Check the length of the context and truncate if necessary - Ver 2.3.3 - 2025-08-13
    $context_length = intval(strlen($context) / 3);
    $max_context_length = 4096;
    
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

    }

    // Construct request body to match the expected schema
    // Note: Local servers with "context shift" disabled only support system + user messages
    // Conversation history is included in the system message context summary - Ver 2.3.9+
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

    prod_trace('NOTICE', 'Local API request model: ' . $model . ' context_chars: ' . strlen($context) . ' URL: ' . $api_url);

    $original_php_time_limit = chatbot_chatgpt_extend_php_execution_for_timeout($timeout);

    try {
        $response = wp_remote_post($api_url, $args);
    } finally {
        if ($jan_cooldown_seconds > 0) {
            set_transient($jan_busy_key, time(), $jan_cooldown_seconds);
        } else {
            delete_transient($jan_busy_key);
        }
        chatbot_chatgpt_restore_php_execution_time($original_php_time_limit);
    }

    // Handle transport errors before reading the body
    if (is_wp_error($response)) {
        delete_transient($duplicate_key);
        $error_message = $response->get_error_message();
        prod_trace('ERROR', 'Local API wp_remote_post failed: ' . $error_message);
        if (stripos($error_message, 'timed out') !== false || stripos($error_message, 'timeout') !== false) {
            return 'Error: The request to Jan.ai timed out after ' . $timeout . ' seconds. The model may still be loading — try again, increase the Local timeout setting, or pick a smaller model.';
        }
        return 'Error: ' . $error_message . ' Check API/Local settings (base URL, API key) and that Jan.ai is running.';
    }

    // Decode the response
    $raw_response_body = wp_remote_retrieve_body($response);
    $response_body_size = strlen($raw_response_body);
    // prod_trace('NOTICE', 'Response body size: ' . $response_body_size . ' bytes');
    
    $response_body = json_decode($raw_response_body, true);
    $response_code = wp_remote_retrieve_response_code($response);
    
    // Check for JSON decode errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        prod_trace('ERROR', 'JSON decode error: ' . json_last_error_msg() . '. Response size: ' . $response_body_size . ' bytes');
    }

    // Check for HTTP error status codes
    if ($response_code >= 400) {
        delete_transient($duplicate_key);
        $error_detail = chatbot_local_parse_api_error_message($raw_response_body, $response_code);
        prod_trace('ERROR', 'Local API HTTP ' . $response_code . ' model ' . $model . ': ' . $error_detail);
        return 'Error: ' . $error_detail;
    }

    if ($response_code === 0 || empty($raw_response_body)) {
        delete_transient($duplicate_key);
        prod_trace('ERROR', 'Local API empty response for model ' . $model);
        return 'Error: Jan.ai returned an empty response. Check Jan.ai server logs and confirm the model "' . $model . '" is available.';
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
    
    // Check for truncation indicators - Ver 2.3.4 - Diagnostic
    $finish_reason = $response_body['choices'][0]['finish_reason'] ?? 'unknown';
    $was_truncated = false;
    $truncation_reason = '';
    
    // Check if response was truncated due to max_tokens limit
    if ($output_tokens >= $max_tokens) {
        $was_truncated = true;
        $truncation_reason = 'max_tokens limit reached (' . $max_tokens . ' tokens)';
        // prod_trace('WARNING', 'Response may be truncated: ' . $truncation_reason . '. Consider increasing max_tokens setting.');
    }
    
    // Check finish_reason field (OpenAI-compatible API should include this)
    if ($finish_reason === 'length') {
        $was_truncated = true;
        $truncation_reason = 'Response truncated by AI server (finish_reason: length)';
        // prod_trace('WARNING', 'Response truncated by AI server. Max tokens setting may be too low.');
    } elseif ($finish_reason === 'stop') {
        // Normal completion - response finished naturally
        // No truncation
    } else {
        // DIAG - Log unknown finish reason
        prod_trace('NOTICE', 'Finish reason: ' . $finish_reason);
    }

    if ($response_code === 200) {

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
        
        // Log raw response length for diagnostics
        $raw_length = strlen($response_text);
        // prod_trace('NOTICE', 'Raw response length: ' . $raw_length . ' characters, ' . $output_tokens . ' tokens');
        
        // Clean up special tokens that local models often include
        $response_text = chatbot_local_clean_response_text($response_text);
        
        // Log cleaned response length for diagnostics
        $cleaned_length = strlen($response_text);
        // if ($raw_length != $cleaned_length) {
        //     // prod_trace('NOTICE', 'Response cleaned: ' . $raw_length . ' -> ' . $cleaned_length . ' characters');
        // }
        
        // Add warning to response if it was truncated
        if ($was_truncated) {
            $response_text .= "\n\n[Note: Response may have been truncated. " . $truncation_reason . "]";
        }
        
        addEntry('chatbot_chatgpt_context_history', $response_text);
        delete_transient($duplicate_key);
        return $response_text;
    } else {

        delete_transient($duplicate_key);
        $error_detail = chatbot_local_parse_api_error_message($raw_response_body, $response_code);
        prod_trace('WARNING', 'Local API no choices content for model ' . $model . ': ' . $error_detail);

        return 'Error: No response from Jan.ai for model "' . $model . '". ' . $error_detail;
    }

}

/**
 * Extract a readable error message from a Jan.ai / OpenAI-compatible JSON body.
 *
 * @param string $raw_body Raw response body.
 * @param int    $http_code HTTP status code.
 * @return string
 */
function chatbot_local_parse_api_error_message($raw_body, $http_code = 0) {

    $http_code = (int) $http_code;
    $fallback = $http_code > 0
        ? 'HTTP ' . $http_code . ' from Jan.ai.'
        : 'Unexpected response from Jan.ai.';

    if (empty($raw_body)) {
        return $fallback;
    }

    $json = json_decode($raw_body, true);
    if (!is_array($json)) {
        $snippet = substr(trim(wp_strip_all_tags($raw_body)), 0, 300);
        return $fallback . ($snippet ? ' ' . $snippet : '');
    }

    if (!empty($json['error'])) {
        if (is_string($json['error'])) {
            return $json['error'];
        }
        if (is_array($json['error']) && !empty($json['error']['message'])) {
            return (string) $json['error']['message'];
        }
    }

    if (!empty($json['message']) && is_string($json['message'])) {
        return $json['message'];
    }

    return $fallback;
}

// Clean up response text from local models - Ver 2.3.4 - Fixed aggressive cleaning
function chatbot_local_clean_response_text($text) {

    // DIAG - Diagnostics - Ver 2.4.5

    // Store original length for comparison
    $original_length = strlen($text);
    
    // First, check if the response contains special tokens that need cleaning
    // If it's a clean OpenAI-compatible response (like from Jan.ai), skip aggressive cleaning
    $has_special_tokens = preg_match('/<\|(?:start|end|channel|message)\|?>/', $text);
    
    if (!$has_special_tokens) {
        // No special tokens detected - return text as-is (just trim)
        // This preserves markdown formatting and full content
        return trim($text);
    }
    
    // Only apply aggressive cleaning if special tokens are actually present
    // First, try to extract just the final message content
    // Look for the pattern that indicates the final assistant response
    $final_pattern = '/<\|start\|>assistant<\|channel\|>final<\|message\|>(.*?)(?:<\||$)/s';
    
    if (preg_match($final_pattern, $text, $matches)) {
        $text = $matches[1]; // Extract just the content after the final message marker
    } else {
        // Remove only the specific special token patterns, not generic pipe patterns
        // This is more conservative and won't remove legitimate content
        $patterns = array(
            // Complete special token pairs (only these specific tokens)
            '/<\|channel\|>[^<]*<\/\|channel\|>/',           // <|channel|>content</|channel|>
            '/<\|message\|>[^<]*<\/\|message\|>/',           // <|message|>content</|message|>
            '/<\|start\|>[^<]*<\/\|start\|>/',               // <|start|>content</|start|>
            '/<\|end\|>[^<]*<\/\|end\|>/',                   // <|end|>content</|end|>
            
            // Incomplete special token pairs
            '/<\|channel\|>[^<]*<\|/',                       // <|channel|>content<|
            '/<\|message\|>[^<]*<\|/',                       // <|message|>content<|
            '/<\|start\|>[^<]*<\|/',                         // <|start|>content<|
            '/<\|end\|>[^<]*<\|/',                           // <|end|>content<|
            
            // Special tokens at end of text
            '/<\|channel\|>[^<]*$/',                         // <|channel|>content at end
            '/<\|message\|>[^<]*$/',                         // <|message|>content at end
            '/<\|start\|>[^<]*$/',                           // <|start|>content at end
            '/<\|end\|>[^<]*$/',                             // <|end|>content at end
            
            // Any <|token|> format (only at start of special tokens)
            '/<\|(?:start|end|channel|message)\|?>/',        // Only these specific tokens
        );
        
        $text = preg_replace($patterns, '', $text);
    }
    
    // Only clean up excessive whitespace (multiple spaces/newlines), but preserve markdown formatting
    // Don't collapse all whitespace - preserve single newlines for markdown
    $text = preg_replace('/[ \t]+/', ' ', $text);  // Collapse multiple spaces/tabs to single space
    $text = preg_replace('/\n{3,}/', "\n\n", $text); // Collapse 3+ newlines to 2 (preserve paragraph breaks)
    $text = trim($text);

    $cleaned_length = strlen($text);
    if ($original_length != $cleaned_length) {
        // prod_trace('NOTICE', 'Response cleaned: ' . $original_length . ' -> ' . $cleaned_length . ' characters (special tokens removed)');
    }
    
    return $text;
}

// Fetch the local models - Ver 2.3.3 - 2025-08-11
function chatbot_local_get_models() {

    $base    = esc_url_raw(get_option('chatbot_local_base_url', 'http://127.0.0.1:1337/v1'));
    $api_url = trailingslashit($base) . 'models';

    // API key (required by Jan.ai local server) - Ver 2.3.3 - 2025-08-13
    $api_key_enc = get_option('chatbot_local_api_key', '');
    $api_key     = chatbot_chatgpt_decrypt_api_key($api_key_enc);
    if (!$api_key) {
        // DIAG - Diagnostics - Ver 2.4.5
        prod_trace('ERROR', 'API key missing. Set one in plugin settings.');
        return array('llama3.2-3b-instruct'); // Safe fallback
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
        // DIAG - Diagnostics - Ver 2.4.5
        prod_trace('ERROR', 'Get local models failed: ' . $response->get_error_message());
        return array('llama3.2-3b-instruct');
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    if ($code < 200 || $code >= 300) {
        // DIAG - Diagnostics - Ver 2.4.5
        prod_trace('ERROR', sprintf('Get local models non-2xx (%d). Body: %s', $code, $body));
        return array('llama3.2-3b-instruct');
    }

    $json = json_decode($body, true);

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
        // DIAG - Diagnostics - Ver 2.4.5
        prod_trace('WARNING', 'No models parsed from endpoint /models. Body: ' . $body);
        // Friendly fallback so UI doesn't break
        $models = array('llama3.2-3b-instruct');
    }

    return array_values(array_unique($models));
    
}

