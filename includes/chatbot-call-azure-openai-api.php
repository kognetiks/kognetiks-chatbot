<?php
/**
 * Kognetiks Chatbot - Azure OpenAI API - Ver 2.2.6
 *
 * This file contains the code for accessing the Azure OpenAI API.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Call the ChatGPT API
function chatbot_call_azure_openai_api($api_key, $message, $user_id = null, $page_id = null, $session_id = null, $assistant_id = null, $client_message_id = null) {

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
        // back_trace( 'NOTICE', 'Duplicate message UUID detected: ' . $message_uuid);
        return "Error: Duplicate request detected. Please try again.";
    }

    // Lock check removed - main send function handles locking
    set_transient($duplicate_key, true, 300); // 5 minutes to prevent duplicates

    // DIAG - Diagnostics - Ver 2.2.6
    // back_trace( 'NOTICE', 'chatbot_call_azure_openai_api()');
    // back_trace( 'NOTICE', 'BEGIN $user_id: ' . $user_id);
    // back_trace( 'NOTICE', 'BEGIN $page_id: ' . $page_id);
    // back_trace( 'NOTICE', 'BEGIN $session_id: ' . $session_id);
    // back_trace( 'NOTICE', 'BEGIN $thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', 'BEGIN $assistant_id: ' . $assistant_id);

    // The current Azure OpenAI API URL endpoint
    // $api_url = 'https://YOUR_RESOURCE_NAME.openai.azure.com/deployments/DEPLOYMENT_NAME/chat/completions?api-version=2024-08-01-preview';
    $api_url = get_chat_completions_api_url();

    // DIAG - Diagnostics - Ver 2.2.6
    // back_trace( 'NOTICE', '$api_url: ' . $api_url);

    $headers = array(
        'Content-Type' => 'application/json',
        'api-key'      => trim($api_key),
    );

    // Select the OpenAI Model
    // Get the saved model from the settings or default to "gpt-3.5-turbo"
    $model = esc_attr(get_option('chatbot_azure_model_choice', 'gpt-3.5-turbo'));
    // back_trace( 'NOTICE', '$model: ' . $model);
 
    // Max tokens - Ver 2.2.6
    $max_tokens = intval(esc_attr(get_option('chatbot_azure_max_tokens_setting', '1000')));

    // Conversation Context - Ver 2.2.6
    $context = esc_attr(get_option('chatbot_azure_conversation_context', 'You are a versatile, friendly, and helpful assistant designed to support me in a variety of tasks that responds in Markdown.'));

    // Temperature - Ver 2.2.6
    $temperature = floatval(esc_attr(get_option('chatbot_azure_temperature', '0.5')));

    // Top P - Ver 2.2.6
    $top_p = floatval(esc_attr(get_option('chatbot_azure_top_p', '1.0')));
 
    // Build conversation context using standardized function - Ver 2.3.9+
    // This function handles conversation history building, message cleaning, and conversation continuity
    // Note: Azure uses 'chatbot_azure_context_history' as the transient name
    $conversation_context = chatbot_chatgpt_build_conversation_context('standard', 10, $session_id, 'chatbot_azure_context_history');
    
    // Knowledge Navigator keyword append for context
    $chatbot_azure_kn_conversation_context = esc_attr(get_option('chatbot_azure_kn_conversation_context', ''));

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

    // ENHANCED CONTEXT - Select some context to send with the message - Ver 2.2.6
    $use_enhanced_content_search = esc_attr(get_option('chatbot_azure_use_advanced_content_search', 'No'));

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
        // back_trace( 'NOTICE', '$context: ' . $context);

    } else {

        // Original Context Instructions - No Enhanced Context
        $context = $sys_message . ' ' . $chatgpt_last_response . ' ' . $context . ' ' . $chatbot_azure_kn_conversation_context;

    }

    // Add session history to context if available (from conversation continuity)
    if (!empty($conversation_context['session_history'])) {
        // Session history is a concatenated string, so we'll add it to context
        $context = $conversation_context['session_history'] . ' ' . $context;
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

    // DIAG Diagnostics - Ver 2.2.6
    // back_trace( 'NOTICE', '$context: ' . $context);

    // Added Role, System, Content Static Variable - Ver 2.2.6
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
    
    // Determine which parameter to use based on model - Ver 2.3.9+
    // Newer models (gpt-5, o1, o3, etc.) require max_completion_tokens instead of max_tokens
    // Some models (o1, o3) don't support temperature/top_p parameters
    $body = array(
        'model' => $model,
        'messages' => $messages,
    );
    
    // Only add temperature and top_p if the model supports them
    if (!chatbot_openai_doesnt_support_temperature($model)) {
        $body['temperature'] = $temperature;
        $body['top_p'] = $top_p;
    }
    
    // Use max_completion_tokens for newer models, max_tokens for older models
    if (chatbot_openai_requires_max_completion_tokens($model)) {
        $body['max_completion_tokens'] = $max_tokens;
    } else {
        $body['max_tokens'] = $max_tokens;
    }

    // FIXME - Allow for file uploads here
    // $file = 'path/to/file';

    // Context History - Ver 2.2.6
    addEntry('chatbot_azure_context_history', $message);

    // DIAG Diagnostics - Ver 2.2.6
    // back_trace( 'NOTICE', '$storedc: ' . $chatbot_azure_kn_conversation_context);
    // back_trace( 'NOTICE', '$context: ' . $context);
    // back_trace( 'NOTICE', '$message: ' . $message);

    $chatbot_azure_timeout = intval(esc_attr(get_option('chatbot_azure_timeout_setting', '50')));

    $args = array(
        'headers' => $headers,
        'body' => json_encode($body),
        'method' => 'POST',
        'data_format' => 'body',
        'timeout' => $chatbot_azure_timeout, // Increase the timeout values to 15 seconds to wait just a bit longer for a response from the engine
    );

    // DIAG - Diagnostics - Ver 2.2.6
    // back_trace( 'NOTICE', '$args: ' . print_r($args, true));

    $response = wp_remote_post($api_url, $args);
 
    // DIAG - Diagnostics - Ver 2.2.6
    // back_trace( 'NOTICE', '$response: ' . print_r($response, true));

    // Handle any errors that are returned from the chat engine
    if (is_wp_error($response)) {
        // Clear locks on error
        // Lock clearing removed - main send function handles locking
        return 'Error: ' . $response->get_error_message().' Please check Settings for a valid API key or your OpenAI account for additional information.';
    }

    // DIAG - Diagnostics - Ver 2.2.6
    // back_trace( 'NOTICE', print_r($response, true));

    // Return json_decode(wp_remote_retrieve_body($response), true);
    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($response_body['message'])) {
        $response_body['message'] = trim($response_body['message']);
        if (!str_ends_with($response_body['message'], '.')) {
            $response_body['message'] .= '.';
        }
    }

    // DIAG - Diagnostics - Ver 2.2.6
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

    // DIAG - Diagnostics - Ver 2.2.6
    // back_trace( 'NOTICE', 'AFTER $user_id: ' . $user_id);
    // back_trace( 'NOTICE', 'AFTER $page_id: ' . $page_id);
    // back_trace( 'NOTICE', 'AFTER $session_id: ' . $session_id);
    // back_trace( 'NOTICE', 'AFTER $thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', 'AFTER $assistant_id: ' . $assistant_id);   

    // DIAG - Diagnostics - Ver 2.2.6
    // back_trace( 'NOTICE', 'Usage - Prompt Tokens: ' . $response_body["usage"]["prompt_tokens"]);
    // back_trace( 'NOTICE', 'Usage - Completion Tokens: ' . $response_body["usage"]["completion_tokens"]);
    // back_trace( 'NOTICE', 'Usage - Total Tokens: ' . $response_body["usage"]["total_tokens"]);

    // Add the usage to the conversation tracker
    if ($response['response']['code'] == 200) {
        append_message_to_conversation_log($session_id, $user_id, $page_id, 'Prompt Tokens', null, null, null, $response_body["usage"]["prompt_tokens"]);
        append_message_to_conversation_log($session_id, $user_id, $page_id, 'Completion Tokens', null, null, null, $response_body["usage"]["completion_tokens"]);
        append_message_to_conversation_log($session_id, $user_id, $page_id, 'Total Tokens', null, null, null, $response_body["usage"]["total_tokens"]);
    }
    
    if (!empty($response_body['choices'])) {
        // Handle the response from the chat engine
        // Context History - Ver 2.2.6
        addEntry('chatbot_azure_context_history', $response_body['choices'][0]['message']['content']);
        // Clear locks on success
        // Lock clearing removed - main send function handles locking
        return $response_body['choices'][0]['message']['content'];
    } else {
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
