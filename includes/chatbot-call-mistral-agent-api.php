<?php
/**
 * Kognetiks Chatbot - Mistral API - Ver 2.2.2
 *
 * This file contains the code accessing the Mistral's API.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Mistral API Documentation
//
// Agent Creation
// https://docs.mistral.ai/agents/agents_basics/#agent-creation
// 
// Update an Agent
//https://docs.mistral.ai/agents/agents_basics/#updating-an-agent
//

// Mistral API Documentation
//
// Start a Conversation
// https://docs.mistral.ai/agents/agents_basics/#continue-a-conversation
//
// Continue a Conversation
// https://docs.mistral.ai/agents/agents_basics/#continue-a-conversation
//
// Retrieve a Conversation
// https://docs.mistral.ai/agents/agents_basics/#retrieve-a-conversation
//
// Restart a Conversation
// https://docs.mistral.ai/agents/agents_basics/#restart-conversation
//
// Stream the Output
// https://docs.mistral.ai/agents/agents_basics/#streaming-output
//


// Create a Mistral agent with web search capabilities
function create_mistral_websearch_agent($api_key) {

    // DIAG - Diagnostics - Ver 3.2.1
    // back_trace( 'NOTICE', 'create_mistral_websearch_agent - start');

    // $api_url = 'https://api.mistral.ai/v1/agents/completions';
    $api_url = 'https://api.mistral.ai/v1/agents';

    // DIAG - Diagnostics - Ver 3.2.1
    // back_trace( 'NOTICE', '$api_url: ' . $api_url);

    // Get the model from settings or use default
    $model = esc_attr(get_option('chatbot_mistral_model_choice', 'mistral-medium-latest'));

    $agent_data = array(
        'name' => 'Websearch Agent',
        'description' => 'Agent able to search information over the web, such as news, weather, sport results...',
        'instructions' => 'You have the ability to perform web searches with `web_search` to find up-to-date information.',
        'model' => $model,
        'completion_args' => array(
            'temperature' => 0.3,
            'top_p' => 0.95
        )
    );
    
    // Conditionally add web_search tools if supported by the model
    $supported_tool_models = array('mistral-medium-latest', 'mistral-large-latest'); // Update as needed
    
    if (in_array($model, $supported_tool_models)) {
        $agent_data['tools'] = array(
            array('type' => 'web_search')
        );
    } else {
        // DIAG - Diagnostics - Ver 2.3.1
        // back_trace( 'WARNING', 'Model ' . $model . ' does not support tools, skipping tool registration.');
    }
    
    $timeout = esc_attr(get_option('chatbot_anthropic_timeout_setting', 240 ));

    $args = array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        ),
        'body' => json_encode($agent_data),
        'timeout' => $timeout
    );
    
    $response = wp_remote_post($api_url, $args);
    
    if (is_wp_error($response)) {
        prod_trace('ERROR', 'Failed to create Mistral agent: ' . $response->get_error_message());
        return false;
    }
    
    // Check for HTTP error status codes
    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code >= 400) {
        $error_body = wp_remote_retrieve_body($response);
        $error_data = json_decode($error_body, true);
        
        $error_message = 'HTTP ' . $response_code . ' Error';
        if (isset($error_data['message'])) {
            $error_message .= ': ' . $error_data['message'];
        } elseif (isset($error_data['error']['message'])) {
            $error_message .= ': ' . $error_data['error']['message'];
        }
        
        prod_trace('ERROR', 'Failed to create Mistral agent: ' . $error_message);
        return false;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    // Check for Mistral error response format (object => 'error')
    if (isset($data['object']) && $data['object'] === 'error') {
        $error_message = isset($data['message']) ? $data['message'] : 'Unknown error occurred';
        prod_trace('ERROR', 'Mistral API Error creating agent: ' . $error_message);
        return false;
    }
    
    // Also check for nested error structure (backward compatibility)
    if (isset($data['error'])) {
        // DIAG - Diagnostics - Ver 2.3.1
        // back_trace( 'ERROR', 'Mistral API Error creating agent: ' . $data['error']['message']);
        $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error occurred';
        prod_trace('ERROR', 'Mistral API Error creating agent: ' . $error_message);
        return false;
    }
    
    if (isset($data['id'])) {
        // DIAG - Diagnostics - Ver 2.3.1
        // back_trace( 'NOTICE', 'Mistral agent created successfully with ID: ' . $data['id']);
        return $data['id'];
    }
    
    // DIAG - Diagnostics - Ver 2.3.1
    // back_trace( 'ERROR', 'Failed to get agent ID from response: ' . print_r($data, true));
    return false;

}

// Call the Mistral API
function chatbot_mistral_agent_call_api($api_key, $message, $assistant_id, $thread_id, $session_id, $user_id, $page_id, $client_message_id = null) {

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

    // DIAG - Diagnostics - Ver 2.2.2
    // back_trace( 'NOTICE', 'chatbot_call_mistral_api - start');
    // back_trace( 'NOTICE', 'chatbot_call_mistral_api - $message: ' . $message);
    // back_trace( 'NOTICE', 'BEGIN $user_id: ' . $user_id);
    // back_trace( 'NOTICE', 'BEGIN $page_id: ' . $page_id);
    // back_trace( 'NOTICE', 'BEGIN $session_id: ' . $session_id);
    // back_trace( 'NOTICE', 'BEGIN $thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', 'BEGIN $assistant_id: ' . $assistant_id);

    // Check if we have a valid assistant_id, otherwise create a websearch agent
    if (
        empty($assistant_id) ||
        in_array($assistant_id, array(
            'Please provide the Agent Id.',
            'Please provide the Agent Id, if any.',
            'websearch'
        ))
    ) {
        // DIAG - Diagnostics - Ver 2.3.1
        // back_trace( 'NOTICE', 'Assistant ID is missing or default, creating new websearch agent...');

        $assistant_id = create_mistral_websearch_agent($api_key);

        if (!$assistant_id) {
            return 'Error: Failed to create Mistral agent with web search capabilities';
        }
    } else {
        // DIAG - Diagnostics - Ver 2.3.1
        // back_trace( 'NOTICE', 'Using provided assistant_id: ' . $assistant_id);
    }

    // Mistral.com API Documentation
    // https://api.mistral.ai/v1/agents/completions

    // The current Mistral API URL endpoint for agents
    if (strpos($assistant_id, 'ag:') === 0) {
        $api_url = 'https://api.mistral.ai/v1/agents/completions';
        // DIAG - Diagnostics - Ver 2.3.1
        // back_trace( 'NOTICE', '$api_url: ' . $api_url);
    } else {
        $api_url = 'https://api.mistral.ai/v1/conversations';
        // DIAG - Diagnostics - Ver 2.3.1
        // back_trace( 'NOTICE', '$api_url: ' . $api_url);
    }

    // DIAG - Diagnostics - Ver 2.2.2
    // back_trace( 'NOTICE', '$api_url: ' . $api_url);

    // Get the saved model from the settings or default to "mistral-small-latest"
    $model = esc_attr(get_option('chatbot_mistral_model_choice', 'mistral-small-latest'));
 
    // Max tokens
    $max_tokens = intval(esc_attr(get_option('chatbot_mistral_max_tokens_setting', '5000')));

    // Conversation Context - Ver 1.6.1
    $context = esc_attr(get_option('chatbot_mistral_conversation_context', 'You are a versatile, friendly, and helpful assistant designed to support me in a variety of tasks that responds in Markdown.'));
    $raw_context = $context;
 
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
        // DIAG - Diagnostics - Ver 2.3.1
        // back_trace( 'NOTICE', '$context: ' . $context);

    } else {

        // Original Context Instructions - No Enhanced Context
        $context = $sys_message . ' ' . $chatgpt_last_response . ' ' . $context . ' ' . $chatbot_chatgpt_kn_conversation_context;

    }

    // Conversation Continuity - Ver 2.1.8
    $chatbot_chatgpt_conversation_continuation = esc_attr(get_option('chatbot_chatgpt_conversation_continuation', 'Off'));
    
    // DIAG - Diagnostics - Ver 2.3.1
    // back_trace( 'NOTICE', '$session_id: ' . $session_id);
    // back_trace( 'NOTICE', '$chatbot_chatgpt_conversation_continuation: ' . $chatbot_chatgpt_conversation_continuation);

    if ($chatbot_chatgpt_conversation_continuation == 'On') {
        $conversation_history = chatbot_chatgpt_get_converation_history($session_id);
        $context = $conversation_history . ' ' . $context;
    }

    // Check the length of the context and truncate if necessary - Ver 2.2.6
    $context_length = intval(strlen($context) / 4); // Assuming 1 token ≈ 4 characters
    // DIAG - Diagnostics - Ver 2.3.1
    // back_trace( 'NOTICE', '$context_length: ' . $context_length);
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
        // DIAG - Diagnostics - Ver 2.3.1
        // back_trace( 'NOTICE', 'Context truncated to ' . strlen($context) . ' characters.');
    } else {
        // DIAG - Diagnostics - Ver 2.3.1
        // back_trace( 'NOTICE', 'Context length is within limits.');
    }

    // FIXME - Set $context to null - Ver 2.2.2 - 2025-01-16
    // $context = $raw_context;

    // FIXME - Overriding Context - Ver 2.3.1 - 2025-06-25
    $context = 'You are a versatile, friendly, and helpful assistant designed to support me in a variety of tasks that responds in Markdown.';

    // DIAG - Diagnostics - Ver 2.3.1 - 2025-06-25
    // back_trace( 'NOTICE', '$assistant_id: ' . $assistant_id);

    // Prepare the request body for Mistral agent API
    if (strpos($assistant_id, 'ag:') === 0) {
        // Mistral Agent API
        // DIAG - Diagnostics - Ver 2.3.1
        // back_trace( 'NOTICE', 'Mistral Agent API');
        $request_body = array(
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $message
                )
            ),
            'agent_id' => $assistant_id
        );
    } else {
        // Mistral Websearch API
        // DIAG - Diagnostics - Ver 2.3.1
        // back_trace( 'NOTICE', 'Mistral Websearch API');
        $request_body = array(
            'inputs' => $message,
            'agent_id' => $assistant_id,
            'stream' => false,
        );
    }

    $timeout = esc_attr(get_option('chatbot_anthropic_timeout_setting', 240 ));

    // Set up the API request
    $args = array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        ),
        'body' => json_encode($request_body),
        'timeout' => $timeout
    );

    // Make the API call
    $response = wp_remote_post($api_url, $args);

    // Check for errors
    if (is_wp_error($response)) {
        // DIAG - Diagnostics - Ver 2.3.1
        // back_trace( 'ERROR', 'Mistral API Error: ' . $response->get_error_message());
        // Clear locks on error
        // Lock clearing removed - main send function handles locking
        return 'Error: ' . $response->get_error_message();
    }

    // Check for HTTP error status codes
    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code >= 400) {
        $error_body = wp_remote_retrieve_body($response);
        $error_data = json_decode($error_body, true);
        
        // Extract error message from Mistral error response
        $error_message = 'HTTP ' . $response_code . ' Error';
        if (isset($error_data['message'])) {
            $error_message .= ': ' . $error_data['message'];
        } elseif (isset($error_data['error']['message'])) {
            $error_message .= ': ' . $error_data['error']['message'];
        } elseif (!empty($error_body)) {
            $error_message .= ': ' . $error_body;
        }
        
        prod_trace('ERROR', 'Mistral API HTTP Error: ' . $error_message);
        // Clear locks on error
        // Lock clearing removed - main send function handles locking
        return 'Error: ' . $error_message;
    }

    // Get the response body
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // Check for API errors in response body (Mistral uses 'object' => 'error' format)
    if (isset($data['object']) && $data['object'] === 'error') {
        // Mistral error response format: {object: 'error', message: '...', type: '...', code: ...}
        $error_message = isset($data['message']) ? $data['message'] : 'Unknown error occurred';
        $error_type = isset($data['type']) ? $data['type'] : 'unknown';
        $error_code = isset($data['code']) ? $data['code'] : '';
        
        prod_trace('ERROR', 'Mistral API Error - Type: ' . $error_type . ', Code: ' . $error_code . ', Message: ' . $error_message);
        // Clear locks on error
        // Lock clearing removed - main send function handles locking
        return 'Error: ' . $error_message;
    }

    // Also check for nested error structure (backward compatibility)
    if (isset($data['error'])) {
        // DIAG - Diagnostics - Ver 2.3.1
        // back_trace( 'ERROR', 'Mistral API Error: ' . $data['error']['message']);
        $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error occurred';
        prod_trace('ERROR', 'Mistral API Error: ' . $error_message);
        // Clear locks on error
        // Lock clearing removed - main send function handles locking
        return 'Error: ' . $error_message;
    }

    // DIAG - Diagnostics - Ver 2.3.1
    // back_trace( 'NOTICE', '$data: ' . print_r($data, true));

    // Empty response text
    $response_text = '';

    // Handle both standard Mistral Chat API format and Agent API format
    if (strpos($assistant_id, 'ag:') === 0) {
        // Check for standard chat completion format first (choices array)
        if (isset($data['choices']) && is_array($data['choices']) && !empty($data['choices'])) {
            if (isset($data['choices'][0]['message']['content']) && !empty($data['choices'][0]['message']['content'])) {
                $response_text = $data['choices'][0]['message']['content'];
                // DIAG - Diagnostics - Ver 2.3.1
                // back_trace( 'NOTICE', 'Extracted response from choices array');
            }
        }
        // Check for Mistral Agent API format (outputs array)
        elseif (isset($data['outputs']) && is_array($data['outputs'])) {
            foreach ($data['outputs'] as $output) {
                if (isset($output['type']) && $output['type'] === 'message.output' && isset($output['content'])) {
                    if (is_string($output['content'])) {
                        $response_text .= $output['content'];
                    } elseif (is_array($output['content'])) {
                        foreach ($output['content'] as $segment) {
                            if (
                                isset($segment['type']) &&
                                $segment['type'] === 'text' &&
                                isset($segment['text']) &&
                                is_string($segment['text'])
                            ) {
                                $response_text .= $segment['text'];
                            }
                        }
                    }
                }
            }
            // DIAG - Diagnostics - Ver 2.3.1
            // back_trace( 'NOTICE', 'Extracted response from outputs array');
        }
    } else {
        // Websearch Agent Response handling (ag_ format)
        if (isset($data['outputs']) && is_array($data['outputs'])) {
            foreach ($data['outputs'] as $output) {
                if (isset($output['type']) && $output['type'] === 'message.output' && isset($output['content'])) {
                    if (is_string($output['content'])) {
                        $response_text .= $output['content'];
                    } elseif (is_array($output['content'])) {
                        foreach ($output['content'] as $segment) {
                            if (
                                isset($segment['type']) &&
                                $segment['type'] === 'text' &&
                                isset($segment['text']) &&
                                is_string($segment['text'])
                            ) {
                                $response_text .= $segment['text'];
                            }
                        }
                    }
                }
            }
            // DIAG - Diagnostics - Ver 2.3.1
            // back_trace( 'NOTICE', 'Extracted response from websearch outputs array');
        }
    }

    if (empty($response_text)) {
        // Check if this might be an error response that wasn't caught earlier
        if (isset($data['object']) && $data['object'] === 'error') {
            $error_message = isset($data['message']) ? $data['message'] : 'Unknown error occurred';
            prod_trace('ERROR', 'Mistral error response detected (missed earlier): ' . $error_message);
            return 'Error: ' . $error_message;
        }
        
        // DIAG - Diagnostics - Ver 2.3.1
        prod_trace( 'ERROR', 'Mistral response found but content is empty or malformed. Response structure: ' . print_r($data, true));
        // Clear locks on error
        // Lock clearing removed - main send function handles locking
        
        // Provide more helpful error message
        $localized_errorResponses = (get_locale() !== "en_US") 
            ? get_localized_errorResponses(get_locale(), $errorResponses) 
            : $errorResponses;
        
        return !empty($localized_errorResponses) 
            ? $localized_errorResponses[array_rand($localized_errorResponses)]
            : 'Error: Assistant responded with no text. Please try again.';
    }
    
    // Clear locks on success
    delete_transient($conv_lock);
    
    return $response_text;

}