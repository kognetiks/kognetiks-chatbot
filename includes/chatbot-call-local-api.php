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
    back_trace( 'NOTICE', 'chatbot_call_local_api - start');
    back_trace( 'NOTICE', 'chatbot_call_local_api - $api_key: ' . $api_key);
    back_trace( 'NOTICE', 'chatbot_call_local_api - $message: ' . $message);
    back_trace( 'NOTICE', 'BEGIN $user_id: ' . $user_id);
    back_trace( 'NOTICE', 'BEGIN $page_id: ' . $page_id);
    back_trace( 'NOTICE', 'BEGIN $session_id: ' . $session_id);
    back_trace( 'NOTICE', 'BEGIN $thread_id: ' . $thread_id);
    back_trace( 'NOTICE', 'BEGIN $assistant_id: ' . $assistant_id);

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
    back_trace( 'NOTICE', '$api_url: ' . $api_url);

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
    back_trace( 'NOTICE', '$model: ' . $model);
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
    back_trace( 'NOTICE', '$chatgpt_last_response: ' . $chatgpt_last_response);
    
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
    back_trace( 'NOTICE', '$chatgpt_last_response: ' . $chatgpt_last_response);
    
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
        back_trace( 'NOTICE', '$context: ' . $context);

    } else {

        // Original Context Instructions - No Enhanced Context
        $context = $sys_message . ' ' . $chatgpt_last_response . ' ' . $context . ' ' . $chatbot_chatgpt_kn_conversation_context;

    }

    // Conversation Continuity - Ver 2.1.8
    $chatbot_chatgpt_conversation_continuation = esc_attr(get_option('chatbot_chatgpt_conversation_continuation', 'Off'));

    // DIAG Diagnostics - Ver 2.1.8
    back_trace( 'NOTICE', '$session_id: ' . $session_id);
    back_trace( 'NOTICE', '$chatbot_chatgpt_conversation_continuation: ' . $chatbot_chatgpt_conversation_continuation);

    if ($chatbot_chatgpt_conversation_continuation == 'On') {
        $conversation_history = chatbot_chatgpt_get_converation_history($session_id);
        $context = $conversation_history . ' ' . $context;
    }

    // Check the length of the context and truncate if necessary - Ver 2.2.6
    $context_length = intval(strlen($context) / 4); // Assuming 1 token ≈ 4 characters
    back_trace( 'NOTICE', '$context_length: ' . $context_length);
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
        back_trace( 'NOTICE', 'Context truncated to ' . strlen($context) . ' characters.');
    } else {
        back_trace( 'NOTICE', 'Context length is within limits.');
    }

    // DIAG Diagnostics - Ver 2.1.8
    back_trace( 'NOTICE', '$context: ' . $context);

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
    back_trace( 'NOTICE', '$api_url: ' . $api_url);
    back_trace( 'NOTICE', '$args: ' . print_r($args, true));

    // Send request
    $response = wp_remote_post($api_url, $args);
    
    // Log response for debugging
    back_trace( 'NOTICE', '$response: ' . print_r($response, true));

    // Handle request errors
    if (is_wp_error($response)) {
        return 'Error: ' . $response->get_error_message() . ' Please check Settings for a valid API key.';
    }

    // Decode the response
    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    $response_code = wp_remote_retrieve_response_code($response);
    $raw_response = wp_remote_retrieve_body($response);

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
    back_trace( 'NOTICE', 'AFTER $user_id: ' . $user_id);
    back_trace( 'NOTICE', 'AFTER $page_id: ' . $page_id);
    back_trace( 'NOTICE', 'AFTER $session_id: ' . $session_id);
    back_trace( 'NOTICE', 'AFTER $thread_id: ' . $thread_id);
    back_trace( 'NOTICE', 'AFTER $assistant_id: ' . $assistant_id);   

    // DIAG - Diagnostics - Ver 1.8.1
    back_trace( 'NOTICE', 'Usage - Prompt Tokens: ' . $response_body["usage"]["prompt_tokens"]);
    back_trace( 'NOTICE', 'Usage - Completion Tokens: ' . $response_body["usage"]["completion_tokens"]);
    back_trace( 'NOTICE', 'Usage - Total Tokens: ' . $response_body["usage"]["total_tokens"]);

    // Add the usage to the conversation tracker

    back_trace( 'NOTICE', '$response_body: ' . print_r($response_body, true));

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
    back_trace( 'NOTICE', 'chatbot_local_start_model');

    global $chatbot_local_model_status;

    // Get the model choice
    $model = esc_attr(get_option('chatbot_local_model_choice', 'llama3.2-3b-instruct'));

    // DiAG - Diagnostics
    back_trace( 'NOTICE', '$model: ' . $model);

    // Set the API URL
    $api_url = esc_attr(get_option('chatbot_local_base_url','http://127.0.0.1:1337/v1')) . '/models/start';

    // Get API key for authorization - Ver 2.2.6
    $api_key = esc_attr(get_option('chatbot_local_api_key', ''));
    // Decrypt the API key - Ver 2.2.6
    $api_key = chatbot_chatgpt_decrypt_api_key($api_key);

    // Prepare headers with authorization
    $headers = array(
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type'  => 'application/json',
    );

    // Prepare the data
    $data = array(
        'model' => $model
    );

    // Send the request
    $response = wp_remote_post($api_url, array(
        'headers' => $headers,
        'body' => json_encode(array(
            'model' => $model
        )),
    ));

    // Check for errors
    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        // Log the error
        prod_trace( 'ERROR', $error_message);
        // Set the model status
        $chatbot_local_model_status = 'error';
        return $response;
    }

    // Get the response body
    $response_body = wp_remote_retrieve_body($response);

    // DiAG - Diagnostics
    back_trace( 'NOTICE', '$response_body: ' . $response_body);

    // Set the model status
    $chatbot_local_model_status = 'started';

    return $response_body;
    
}

// Fetch the local models
function chatbot_local_get_models() {
    
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
            if (isset($model['status']) && $model['status'] == 'downloaded') {
                $models[] = $model['id'];
            }
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
