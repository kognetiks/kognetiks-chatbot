<?php
/**
 * Kognetiks Chatbot - Local API - Ver 2.2.2
 *
 * This file contains the code accessing the Jan.ai local API server.
 * 
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

    // DIAG - Diagnostics - Ver 2.2.2
    // back_trace( 'NOTICE', 'chatbot_call_local_api - start');
    // back_trace( 'NOTICE', 'chatbot_call_local_api - $api_key: ' . $api_key);
    // back_trace( 'NOTICE', 'chatbot_call_local_api - $message: ' . $message);
    // back_trace( 'NOTICE', 'BEGIN $user_id: ' . $user_id);
    // back_trace( 'NOTICE', 'BEGIN $page_id: ' . $page_id);
    // back_trace( 'NOTICE', 'BEGIN $session_id: ' . $session_id);
    // back_trace( 'NOTICE', 'BEGIN $thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', 'BEGIN $assistant_id: ' . $assistant_id);

    // Jan.ai Download
    // https://jan.ai/download

    // Jan.ai API Documentation
    // https://jan.ai/docs

    // Jan.ai Quick Start Guide
    // https://jan.ai/docs/quickstart

    // The current Local API URL endpoint
    // $api_url = 'https://127.0.0.1:1337/v1/chat/completions';
    $api_url = get_chat_completions_api_url();

    // DIAG - Diagnostics - Ver 2.2.2
    // back_trace( 'NOTICE', '$api_url: ' . $api_url);

    // 
    // Select an Open-source Model
    //
    // Hugging Face Models
    // Libraries = GGUF
    // Sort by Most Downloads
    // https://huggingface.co/models?library=gguf&sort=downloads
    //

    // Set the model choice
    // update_option('chatbot_local_model_choice', 'llama3.2-3b-instruct');

    // Start the model
    chatbot_local_start_model();

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
    // DIAG - Diagnostics
    // back_trace( 'NOTICE', '$model: ' . $model);
    $max_tokens = intval(get_option('chatbot_local_max_tokens_setting', 10000));
    $temperature = floatval(get_option('chatbot_local_temperature', 0.8));
    $top_p = floatval(get_option('chatbot_local_top_p', 0.95));
    $context = esc_attr(get_option('chatbot_local_conversation_context', 'You are a versatile, friendly, and helpful assistant that responds using Markdown syntax.'));
    $timeout = intval(get_option('chatbot_local_timeout_setting', 360));


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

    // DIAG Diagnostics - Ver 2.2.9
    // back_trace( 'NOTICE', '$chatgpt_last_response: ' . $chatgpt_last_response);
    
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
        // DIAG Diagnostics - Ver 2.2.4 - 2025-02-04
        // back_trace( 'NOTICE', '$context: ' . $context);

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

    // Check the length of the context and truncate if necessary - Ver 2.2.6
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

    // DIAG Diagnostics - Ver 2.1.8
    // back_trace( 'NOTICE', '$context: ' . $context);

    // Construct request body to match the expected schema
    $body = array(
        'model' => $model,
        'stream' => null,
        'max_tokens' => $max_tokens,
        'stop' => array("End"),
        'frequency_penalty' => 0.2,
        'presence_penalty' => 0.6,
        'temperature' => $temperature,
        'top_p' => $top_p,
        'modalities' => array("text"),
        'audio' => array(
            'voice' => 'default',
            'format' => 'mp3'
        ),
        'store' => null,
        'metadata' => array(
            'type' => 'conversation'
        ),
        'logit_bias' => array(
            "15496" => -100,
            "51561" => -100
        ),
        'logprobs' => null,
        'n' => 1,
        'response_format' => array('type' => 'text'),
        'seed' => 123,
        'stream_options' => null,
        // 'tools' => array(
        //     array(
        //         'type' => 'function',
        //         'function' => array(
        //             'name' => '',
        //             'parameters' => array(),
        //             'strict' => null
        //         )
        //     )
        // ),
        'tools' => null,
        'parallel_tool_calls' => null,
        'messages' => array(
            array('role' => 'system', 'content' => $context),
            array('role' => 'user', 'content' => $message)
        )
    );

    // API request arguments
    $args = array(
        'headers' => $headers,
        'body'    => json_encode($body),
        'method'  => 'POST',
        'timeout' => $timeout,
        'data_format' => 'body',
    );

    // Log message for debugging
    // back_trace( 'NOTICE', '$api_url: ' . $api_url);
    // back_trace( 'NOTICE', '$args: ' . print_r($args, true));

    // Send request
    $response = wp_remote_post($api_url, $args);
    
    // Log response for debugging
    // back_trace( 'NOTICE', '$response: ' . print_r($response, true));

    // Handle request errors
    if (is_wp_error($response)) {
        return 'Error: ' . $response->get_error_message() . ' Please check Settings for a valid API key.';
    }

    // Decode the response
    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    $response_code = wp_remote_retrieve_response_code($response);
    $raw_response = wp_remote_retrieve_body($response);

    // Enhanced debugging for Jan.ai - Ver 2.3.3 - 2025-08-13
    prod_trace('NOTICE', 'Jan.ai API Response Code: ' . $response_code);
    prod_trace('NOTICE', 'Jan.ai API Raw Response: ' . substr($raw_response, 0, 500) . (strlen($raw_response) > 500 ? '...' : ''));
    
    if ($response_body) {
        prod_trace('NOTICE', 'Jan.ai API Response Body: ' . print_r($response_body, true));
    } else {
        prod_trace('WARNING', 'Jan.ai API Response Body is null or invalid JSON');
    }

    // // Check for content in response
    // if (!empty($response_body['choices'][0]['message']['content'])) {

    //     // Remove <|eom_id|> from $response_body
    //     $response_body['choices'][0]['message']['content'] = str_replace('<|eom_id|>', '', $response_body['choices'][0]['message']['content']);

    //     // Remove <|eot_id|> from $response_body
    //     $response_body['choices'][0]['message']['content'] = str_replace('<|eot_id|>', '', $response_body['choices'][0]['message']['content']);

    //     return trim($response_body['choices'][0]['message']['content']);
    // } else {
    //     // Ensure $error_responses is an array
    //     if (!is_array($error_responses)) {
    //         $error_responses = array('An unknown error occurred. Please try again later.');
    //     }
    //     return $error_responses[array_rand($error_responses)];
    // }

    // // Check for response code and message
    // if (isset($response['response']['code']) && $response['response']['code'] == 200) {
    //     if (!empty($response_body['choices'][0]['message']['content'])) {
    //         return trim($response_body['choices'][0]['message']['content']);
    //     } else {
    //         // Ensure $error_responses is an array
    //         if (!is_array($error_responses)) {
    //             $error_responses = array('An unknown error occurred. Please try again later.');
    //         }
    //         return $error_responses[array_rand($error_responses)];
    //     }
    // } else {
    //     return 'Error: Invalid response from the server.';
    // }

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
    // back_trace( 'NOTICE', 'Usage - Prompt Tokens: ' . $response_body["usage"]["prompt_tokens"]);
    // back_trace( 'NOTICE', 'Usage - Completion Tokens: ' . $response_body["usage"]["completion_tokens"]);
    // back_trace( 'NOTICE', 'Usage - Total Tokens: ' . $response_body["usage"]["total_tokens"]);

    // Add the usage to the conversation tracker

    // back_trace( 'NOTICE', '$response_body: ' . print_r($response_body, true));

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


// Start the chat completions model
function chatbot_local_start_model() {

    // DiAG - Diagnostics
    // back_trace( 'NOTICE', 'chatbot_local_start_model');

    global $chatbot_local_model_status;

    // Get the model choice
    $model = esc_attr(get_option('chatbot_local_model_choice', 'llama3.2-3b-instruct'));

    // DiAG - Diagnostics
    // back_trace( 'NOTICE', '$model: ' . $model);

    // In Jan.ai, models are started automatically when you make a chat request
    // There's no separate /models/start endpoint - Ver 2.3.3 - 2025-08-13
    // We'll just set the status and let the first chat request start the model
    
    // Set the model status
    $chatbot_local_model_status = 'ready';

    prod_trace('NOTICE', 'Model status set to ready for: ' . $model . ' - will start automatically on first chat request');

    return 'Model ready - will start automatically on first chat request';
    
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
        // 'sslverify' => false, // only if you’re using self-signed HTTPS
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
        // Friendly fallback so UI doesn’t break
        $models = array('llama3.2-3b-instruct');
    }

    return array_values(array_unique($models));
    
}

// Fetch the local models
function chatbot_local_get_models_OLD() {
    
    // DiAG - Diagnostics
    back_trace( 'NOTICE', 'chatbot_local_get_models');

    // Set the API URL
    $api_url = esc_attr(get_option('chatbot_local_base_url','http://127.0.0.1:1337/v1')) . '/models';

    // Get API key for authorization - Ver 2.2.6
    $api_key = esc_attr(get_option('chatbot_local_api_key', ''));
    // Decrypt the API key - Ver 2.2.6
    $api_key = chatbot_chatgpt_decrypt_api_key($api_key);

    // Prepare headers with authorization
    $headers = array(
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type'  => 'application/json',
    );

    // Send the request
    $response = wp_remote_get($api_url, array(
        'headers' => $headers,
    ));

    // Check for errors
    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        // Log the error
        prod_trace( 'ERROR', $error_message);
        // Return a default model in teh $models array
        $models = array('llama3.2-3b-instruct');
        return $models;
    }

    // Get the response body
    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    $response_code = wp_remote_retrieve_response_code($response);
    $raw_response = wp_remote_retrieve_body($response);

    // DiAG - Diagnostics
    back_trace( 'NOTICE', '$response_body: ' . print_r($response_body, true));

    // For each model in the $response_body, add the model to return array
    $models = array();
    
    // Check if $response_body is not null and has 'data' key before iterating
    if ($response_body && isset($response_body['data']) && is_array($response_body['data'])) {
        foreach ($response_body['data'] as $model) {
            // Check for new Jan.ai API format (no status field) or old format (with status field)
            if (isset($model['id'])) {
                // New format: if model has an ID, assume it's available
                if (!isset($model['status'])) {
                    $models[] = $model['id'];
                }
                // Old format: only include if status is 'downloaded'
                elseif (isset($model['status']) && $model['status'] == 'downloaded') {
                    $models[] = $model['id'];
                }
            }
        }
        
        // If no models found with the new logic, log for debugging
        if (empty($models)) {
            $debug_info = array(
                'response_code' => $response_code,
                'response_body' => $response_body,
                'message' => 'No models found with valid ID or downloaded status'
            );
            prod_trace('WARNING', 'No valid models found in API response. Debug info: ' . json_encode($debug_info));
        }
    } else {
        // Enhanced logging with more details
        $debug_info = array(
            'response_code' => $response_code,
            'response_body' => $response_body,
            'raw_response' => $raw_response,
            'api_url' => $api_url
        );
        prod_trace('WARNING', 'Invalid response body or missing data key in API response. Debug info: ' . json_encode($debug_info));
        $models = array('llama3.2-3b-instruct');
    }

    return $models;
    
}

// Seed the model - Ver 2.3.3 - 2025-08-11
function chatbot_local_seed_model( $model_id ) {
    // First check if the Jan.ai server is available
    $base = esc_url_raw( get_option('chatbot_local_base_url', 'http://127.0.0.1:1337/v1') );
    $health_url = trailingslashit($base) . 'models';
    
    // Get API key for authentication
    $api_key_enc = get_option('chatbot_local_api_key', '');
    $api_key = chatbot_chatgpt_decrypt_api_key($api_key_enc);
    
    $headers = array(
        'Content-Type' => 'application/json',
    );
    if ($api_key) {
        $headers['Authorization'] = 'Bearer ' . $api_key;
    }
    
    // Quick health check - just see if the server responds
    $health_response = wp_remote_get($health_url, array(
        'headers' => $headers,
        'timeout' => 5,
        'blocking' => true,
    ));
    
    if (is_wp_error($health_response)) {
        prod_trace('NOTICE', 'Jan.ai server not available for seeding, skipping: ' . $health_response->get_error_message());
        return false;
    }
    
    $health_code = wp_remote_retrieve_response_code($health_response);
    if ($health_code < 200 || $health_code >= 300) {
        prod_trace('NOTICE', 'Jan.ai server not ready for seeding (HTTP ' . $health_code . '), skipping');
        return false;
    }
    
    // Server is available, proceed with seeding
    $api_url = trailingslashit($base) . 'chat/completions';

    $payload = array(
        'model' => sanitize_text_field($model_id),
        'messages' => array(
            array('role' => 'user', 'content' => 'ping'),
        ),
        'max_tokens' => 1,
        'stream' => false,
    );

    $res = wp_remote_post($api_url, array(
        'headers' => $headers,
        'body'    => wp_json_encode($payload),
        'timeout' => 20,
    ));

    if (is_wp_error($res)) {
        prod_trace('ERROR', 'Seed model failed for ' . $model_id . ': ' . $res->get_error_message());
        return false;
    }

    $code = wp_remote_retrieve_response_code($res);
    if ($code < 200 || $code >= 300) {
        prod_trace('ERROR', 'Seed model non-2xx for ' . $model_id . ': ' . wp_remote_retrieve_body($res));
        return false;
    }

    return true;
}

// Simple test function to debug Jan.ai connection - Ver 2.3.3 - 2025-08-13
function chatbot_local_test_connection() {
    $base = esc_url_raw( get_option('chatbot_local_base_url', 'http://127.0.0.1:1337/v1') );
    $api_key_enc = get_option('chatbot_local_api_key', '');
    $api_key = chatbot_chatgpt_decrypt_api_key($api_key_enc);
    
    if (!$api_key) {
        return array(
            'success' => false,
            'message' => 'No API key configured'
        );
    }
    
    $headers = array(
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type' => 'application/json',
    );
    
    // Test 1: Check if server responds to /models
    $models_url = trailingslashit($base) . 'models';
    $models_response = wp_remote_get($models_url, array(
        'headers' => $headers,
        'timeout' => 10,
    ));
    
    if (is_wp_error($models_response)) {
        return array(
            'success' => false,
            'message' => 'Server not responding: ' . $models_response->get_error_message()
        );
    }
    
    $models_code = wp_remote_retrieve_response_code($models_response);
    $models_body = wp_remote_retrieve_body($models_response);
    
    if ($models_code != 200) {
        return array(
            'success' => false,
            'message' => 'Models endpoint error (HTTP ' . $models_code . '): ' . $models_body
        );
    }
    
    // Test 2: Test model availability with minimal chat request
    // In Jan.ai, models start automatically on first chat request
    $model_id = esc_attr(get_option('chatbot_local_model_choice', 'llama3.2-3b-instruct'));
    $test_url = trailingslashit($base) . 'chat/completions';
    
    $test_payload = array(
        'model' => $model_id,
        'messages' => array(
            array('role' => 'user', 'content' => 'test'),
        ),
        'max_tokens' => 1,
        'stream' => false,
    );
    
    $test_response = wp_remote_post($test_url, array(
        'headers' => $headers,
        'body' => json_encode($test_payload),
        'timeout' => 30,
    ));
    
    if (is_wp_error($test_response)) {
        return array(
            'success' => false,
            'message' => 'Test chat request failed: ' . $test_response->get_error_message()
        );
    }
    
    $test_code = wp_remote_retrieve_response_code($test_response);
    $test_body = wp_remote_retrieve_body($test_response);
    
    return array(
        'success' => true,
        'message' => 'Connection test completed',
        'models_response' => array(
            'code' => $models_code,
            'body' => $models_body
        ),
        'test_response' => array(
            'code' => $test_code,
            'body' => $test_body,
            'model' => $model_id
        )
    );
}

// Manual model backend start function - Ver 2.3.3 - 2025-08-13
function chatbot_local_manual_start_model_backend($model_id = null) {
    if (!$model_id) {
        $model_id = esc_attr(get_option('chatbot_local_model_choice', 'llama3.2-3b-instruct'));
    }
    
    // In Jan.ai, models are started automatically when you make a chat request
    // There's no separate /models/start endpoint - Ver 2.3.3 - 2025-08-13
    
    $base = esc_url_raw( get_option('chatbot_local_base_url', 'http://127.0.0.1:1337/v1') );
    
    // Get API key for authentication
    $api_key_enc = get_option('chatbot_local_api_key', '');
    $api_key = chatbot_chatgpt_decrypt_api_key($api_key_enc);
    
    if (!$api_key) {
        prod_trace('ERROR', 'No API key configured for Jan.ai server');
        return array(
            'success' => false,
            'message' => 'No API key configured. Please set an API key in your plugin settings.'
        );
    }
    
    prod_trace('NOTICE', 'Jan.ai models start automatically on first chat request for: ' . $model_id);
    
    // Test if the model is available by making a minimal chat request
    $test_url = trailingslashit($base) . 'chat/completions';
    
    $headers = array(
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type' => 'application/json',
    );
    
    $test_payload = array(
        'model' => $model_id,
        'messages' => array(
            array('role' => 'user', 'content' => 'test'),
        ),
        'max_tokens' => 1,
        'stream' => false,
    );
    
    prod_trace('NOTICE', 'Testing model availability with minimal chat request for: ' . $model_id);
    
    $test_response = wp_remote_post($test_url, array(
        'headers' => $headers,
        'body' => json_encode($test_payload),
        'timeout' => 30,
    ));
    
    if (is_wp_error($test_response)) {
        $error_msg = 'Failed to test model: ' . $test_response->get_error_message();
        prod_trace('ERROR', $error_msg);
        return array(
            'success' => false,
            'message' => $error_msg
        );
    }
    
    $test_code = wp_remote_retrieve_response_code($test_response);
    $test_body = wp_remote_retrieve_body($test_response);
    
    if ($test_code == 200) {
        prod_trace('NOTICE', 'Model is available and ready for: ' . $model_id);
        return array(
            'success' => true,
            'message' => 'Model is available and ready for ' . $model_id,
            'note' => 'Jan.ai models start automatically on first use - no manual start needed'
        );
    } else {
        prod_trace('ERROR', 'Model test failed (HTTP ' . $test_code . '): ' . $test_body);
        return array(
            'success' => false,
            'message' => 'Model test failed (HTTP ' . $test_code . '): ' . $test_body
        );
    }
}

// Check and start model backend if needed - Ver 2.3.3 - 2025-08-13
function chatbot_local_ensure_model_backend_running($model_id) {
    // In Jan.ai, models are started automatically when you make a chat request
    // There's no separate /models/start endpoint - Ver 2.3.3 - 2025-08-13
    // We'll just check if the server is available and assume the model will start on first use
    
    $base = esc_url_raw( get_option('chatbot_local_base_url', 'http://127.0.0.1:1337/v1') );
    
    // Get API key for authentication
    $api_key_enc = get_option('chatbot_local_api_key', '');
    $api_key = chatbot_chatgpt_decrypt_api_key($api_key_enc);
    
    $headers = array(
        'Content-Type' => 'application/json',
    );
    if ($api_key) {
        $headers['Authorization'] = 'Bearer ' . $api_key;
    }
    
    // Just check if the server is available by calling /models
    $models_url = trailingslashit($base) . 'models';
    
    prod_trace('NOTICE', 'Checking if Jan.ai server is available for: ' . $model_id);
    
    $models_response = wp_remote_get($models_url, array(
        'headers' => $headers,
        'timeout' => 10,
    ));
    
    if (is_wp_error($models_response)) {
        prod_trace('ERROR', 'Jan.ai server not available: ' . $models_response->get_error_message());
        return false;
    }
    
    $models_code = wp_remote_retrieve_response_code($models_response);
    
    if ($models_code == 200) {
        prod_trace('NOTICE', 'Jan.ai server is available - model will start automatically on first chat request');
        return true;
    } else {
        prod_trace('ERROR', 'Jan.ai server error (HTTP ' . $models_code . ')');
        return false;
    }
}

// Simple debug function to test Jan.ai connection - Ver 2.3.3 - 2025-08-13
function chatbot_local_debug_connection() {
    $result = chatbot_local_test_connection();
    
    if ($result['success']) {
        echo '<div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; margin: 10px 0; border-radius: 4px;">';
        echo '<strong>✅ Jan.ai Connection Test Successful</strong><br>';
        echo 'Models endpoint: HTTP ' . $result['models_response']['code'] . '<br>';
        echo 'Test chat request: HTTP ' . $result['test_response']['code'] . '<br>';
        echo 'Model: ' . $result['test_response']['model'] . '<br>';
        echo '<small>Note: Jan.ai models start automatically on first chat request</small>';
        echo '</div>';
    } else {
        echo '<div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 4px;">';
        echo '<strong>❌ Jan.ai Connection Test Failed</strong><br>';
        echo 'Error: ' . $result['message'];
        echo '</div>';
    }
    
    return $result;
}

// Step-by-step Jan.ai test function - Ver 2.3.3 - 2025-08-13
function chatbot_local_step_by_step_test() {
    echo '<div style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; margin: 10px 0; border-radius: 4px;">';
    echo '<h3>🔍 Jan.ai Step-by-Step Connection Test</h3>';
    
    $base = esc_url_raw( get_option('chatbot_local_base_url', 'http://127.0.0.1:1337/v1') );
    $api_key_enc = get_option('chatbot_local_api_key', '');
    $api_key = chatbot_chatgpt_decrypt_api_key($api_key_enc);
    
    if (!$api_key) {
        echo '<div style="color: #721c24; margin: 10px 0;">❌ <strong>Step 1: API Key</strong> - No API key configured</div>';
        echo '</div>';
        return false;
    }
    echo '<div style="color: #155724; margin: 10px 0;">✅ <strong>Step 1: API Key</strong> - API key found</div>';
    
    $headers = array(
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type' => 'application/json',
    );
    
    // Test 1: Check if server responds to /models
    echo '<div style="margin: 10px 0;"><strong>Step 2: Server Availability</strong></div>';
    $models_url = trailingslashit($base) . 'models';
    $models_response = wp_remote_get($models_url, array(
        'headers' => $headers,
        'timeout' => 10,
    ));
    
    if (is_wp_error($models_response)) {
        echo '<div style="color: #721c24; margin: 10px 0;">❌ Server not responding: ' . $models_response->get_error_message() . '</div>';
        echo '</div>';
        return false;
    }
    
    $models_code = wp_remote_retrieve_response_code($models_response);
    $models_body = wp_remote_retrieve_body($models_response);
    
    if ($models_code == 200) {
        echo '<div style="color: #155724; margin: 10px 0;">✅ Server responding (HTTP ' . $models_code . ')</div>';
        echo '<div style="font-size: 12px; color: #6c757d; margin: 5px 0;">Models response: ' . substr($models_body, 0, 200) . '...</div>';
    } else {
        echo '<div style="color: #721c24; margin: 10px 0;">❌ Server error (HTTP ' . $models_code . '): ' . $models_body . '</div>';
        echo '</div>';
        return false;
    }
    
    // Test 2: Try a minimal chat request
    echo '<div style="margin: 10px 0;"><strong>Step 3: Model Chat Test</strong></div>';
    $model_id = esc_attr(get_option('chatbot_local_model_choice', 'llama3.2-3b-instruct'));
    $test_url = trailingslashit($base) . 'chat/completions';
    
    $test_payload = array(
        'model' => $model_id,
        'messages' => array(
            array('role' => 'user', 'content' => 'Hello'),
        ),
        'max_tokens' => 10,
        'stream' => false,
    );
    
    echo '<div style="font-size: 12px; color: #6c757d; margin: 5px 0;">Testing model: ' . $model_id . '</div>';
    echo '<div style="font-size: 12px; color: #6c757d; margin: 5px 0;">URL: ' . $test_url . '</div>';
    
    $test_response = wp_remote_post($test_url, array(
        'headers' => $headers,
        'body' => json_encode($test_payload),
        'timeout' => 60,
    ));
    
    if (is_wp_error($test_response)) {
        echo '<div style="color: #721c24; margin: 10px 0;">❌ Chat test failed: ' . $test_response->get_error_message() . '</div>';
        echo '</div>';
        return false;
    }
    
    $test_code = wp_remote_retrieve_response_code($test_response);
    $test_body = wp_remote_retrieve_body($test_response);
    
    if ($test_code == 200) {
        echo '<div style="color: #155724; margin: 10px 0;">✅ Chat test successful (HTTP ' . $test_code . ')</div>';
        echo '<div style="font-size: 12px; color: #6c757d; margin: 5px 0;">Response: ' . substr($test_body, 0, 300) . '...</div>';
        
        // Try to parse the response
        $test_json = json_decode($test_body, true);
        if ($test_json && isset($test_json['choices'][0]['message']['content'])) {
            echo '<div style="color: #155724; margin: 10px 0;">✅ Response content found: ' . substr($test_json['choices'][0]['message']['content'], 0, 100) . '...</div>';
        } else {
            echo '<div style="color: #856404; margin: 10px 0;">⚠️ Response content missing or invalid</div>';
            echo '<div style="font-size: 12px; color: #6c757d; margin: 5px 0;">Parsed response: ' . print_r($test_json, true) . '</div>';
        }
    } else {
        echo '<div style="color: #721c24; margin: 10px 0;">❌ Chat test failed (HTTP ' . $test_code . '): ' . $test_body . '</div>';
        echo '</div>';
        return false;
    }
    
    echo '<div style="color: #155724; margin: 15px 0; font-weight: bold;">🎉 All tests passed! Jan.ai is working correctly.</div>';
    echo '</div>';
    return true;
}

