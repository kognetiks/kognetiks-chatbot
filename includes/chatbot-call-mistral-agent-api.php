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

// Create a Mistral agent with web search capabilities
function create_mistral_websearch_agent($api_key) {

    // Mistral API Documentation
    // https://docs.mistral.ai/agents/connectors/websearch/

    // DIAG - Diagnostics - Ver 3.2.1
    back_trace( 'NOTICE', 'create_mistral_websearch_agent - start');

    // $api_url = 'https://api.mistral.ai/v1/agents/completions';
    $api_url = 'https://api.mistral.ai/v1/agents';

    // DIAG - Diagnostics - Ver 3.2.1
    back_trace( 'NOTICE', '$api_url: ' . $api_url);
    
    // Get the model from settings or use default
    $model = esc_attr(get_option('chatbot_mistral_model_choice', 'mistral-small-latest'));
    
    $agent_data = array(
        'name' => 'Websearch Agent',
        'description' => 'Agent able to search information over the web, such as news, weather, sport results...',
        'instructions' => 'You have the ability to perform web searches with `web_search` to find up-to-date information.',
        'model' => $model,
        'tools' => array(
            array(
                'type' => 'web_search'
            )
        ),
        'completion_args' => array(
            'temperature' => 0.3,
            'top_p' => 0.95
        )
    );
    
    $args = array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        ),
        'body' => json_encode($agent_data),
        'timeout' => 30
    );
    
    $response = wp_remote_post($api_url, $args);
    
    if (is_wp_error($response)) {
        prod_trace('ERROR', 'Failed to create Mistral agent: ' . $response->get_error_message());
        return false;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (isset($data['error'])) {
        back_trace('ERROR', 'Mistral API Error creating agent: ' . $data['error']['message']);
        return false;
    }
    
    if (isset($data['id'])) {
        back_trace('NOTICE', 'Mistral agent created successfully with ID: ' . $data['id']);
        return $data['id'];
    }
    
    back_trace('ERROR', 'Failed to get agent ID from response: ' . print_r($data, true));
    return false;

}

// Call the Mistral API
function chatbot_mistral_agent_call_api($api_key, $message, $assistant_id, $thread_id, $session_id, $user_id, $page_id) {

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
    back_trace( 'NOTICE', 'chatbot_call_mistral_api - start');
    back_trace( 'NOTICE', 'chatbot_call_mistral_api - $message: ' . $message);
    back_trace( 'NOTICE', 'BEGIN $user_id: ' . $user_id);
    back_trace( 'NOTICE', 'BEGIN $page_id: ' . $page_id);
    back_trace( 'NOTICE', 'BEGIN $session_id: ' . $session_id);
    back_trace( 'NOTICE', 'BEGIN $thread_id: ' . $thread_id);
    back_trace( 'NOTICE', 'BEGIN $assistant_id: ' . $assistant_id);

    // Check if we have a valid assistant_id, if not create a web search agent
    if (empty($assistant_id) || $assistant_id === 'Please provide the Agent Id.' || $assistant_id === 'Please provide the Agent Id, if any.' || $assistant_id === 'websearch') {
        if ($assistant_id === 'websearch') {
            back_trace( 'NOTICE', 'Websearch agent requested, creating web search agent...');
        } else {
            back_trace( 'NOTICE', 'No valid assistant_id provided, creating web search agent...');
        }
        $assistant_id = create_mistral_websearch_agent($api_key);
        if (!$assistant_id) {
            return 'Error: Failed to create Mistral agent with web search capabilities';
        }
        // Store the new agent ID for future use
        update_option('assistant_id', $assistant_id);
        back_trace( 'NOTICE', 'New agent ID stored: ' . $assistant_id);
    }

    // Mistral.com API Documentation
    // https://api.mistral.ai/v1/agents/completions

    // The current Mistral API URL endpoint for agents
    // $api_url = 'https://api.mistral.ai/v1/agents/completions';
    $api_url = 'https://api.mistral.ai/v1/agents';

    // DIAG - Diagnostics - Ver 2.2.2
    back_trace( 'NOTICE', '$api_url: ' . $api_url);

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
        back_trace( 'NOTICE', 'Context truncated to ' . strlen($context) . ' characters.');
    } else {
        back_trace( 'NOTICE', 'Context length is within limits.');
    }

    // FIXME - Set $context to null - Ver 2.2.2 - 2025-01-16
    // $context = $raw_context;

    // Regular LLM
    // Prepare the request body for Mistral agent API
    $request_body = array(
        'messages' => array(
            array(
                'role' => 'system',
                'content' => $context
            ),
            array(
                'role' => 'user',
                'content' => $message
            )
        ),
        'max_tokens' => $max_tokens,
        'agent_id' => $assistant_id
    );
    
    // Search Tool with Regular LLM - Ver 2.3.1
    // Prepare the request body for Mistral agent API
    // $request_body = array(
    //     'messages' => array(
    //         array(
    //             'role' => 'system',
    //             'content' => $context
    //         ),
    //         array(
    //             'role' => 'user',
    //             'content' => $message
    //         )
    //     ),
    //     'max_tokens' => $max_tokens,
    //     'agent_id' => $assistant_id,
    //     'tools' => array(
    //         array(
    //             'type' => 'web_search'
    //         )
    //     )
    // );

    // // Add thread_id if available
    // if (!empty($thread_id)) {
    //     $request_body['thread_id'] = $thread_id;
    // }

    // Set up the API request
    $args = array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        ),
        'body' => json_encode($request_body),
        'timeout' => 30
    );

    // Make the API call
    $response = wp_remote_post($api_url, $args);

    // Check for errors
    if (is_wp_error($response)) {
        back_trace( 'ERROR', 'Mistral API Error: ' . $response->get_error_message());
        return 'Error: ' . $response->get_error_message();
    }

    // Get the response body
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // Check for API errors
    if (isset($data['error'])) {
        back_trace( 'ERROR', 'Mistral API Error: ' . $data['error']['message']);
        return 'Error: ' . $data['error']['message'];
    }

    // DIAG - Diagnostics - Ver 2.3.0
    back_trace( 'NOTICE', '$data: ' . print_r($data, true));

    // Check if the response has the expected structure before processing
    if (!isset($data['choices']) || !is_array($data['choices']) || empty($data['choices'])) {
        back_trace( 'ERROR', 'Mistral API response missing choices array');
        return 'Error: Invalid response structure from Mistral API - missing choices';
    }

    if (!isset($data['choices'][0]['message']['content'])) {
        back_trace( 'ERROR', 'Mistral API response missing message content');
        return 'Error: Invalid response structure from Mistral API - missing message content';
    }

    // Add a check to see if the response contains the the string "[conversation_transcript]"
    if (strpos($data['choices'][0]['message']['content'], '[conversation_transcript]') !== false) {

        // DIAG - Diagnostics - Ver 2.3.0
        back_trace( 'NOTICE', 'The response contains the string "[conversation_transcript]"');

        // Extract the conversation transcript
        $conversation_transcript = '';

        if (isset($data['choices'][0]['message']['content']) && is_array($data['choices'][0]['message']['content'])) {
            // Reverse the array to get messages in chronological order (oldest to newest)
            $messages = array_reverse($data['choices'][0]['message']['content']);
            
            foreach ($messages as $message) {
                if (isset($message['content']) && is_array($message['content'])) {
                    $role = isset($message['role']) ? $message['role'] : 'unknown';
                    $content = isset($message['content']) ? $message['content'] : '';
                    $conversation_transcript .= $role . ': ' . $content . "\n\n";
                }
            }
        }

        back_trace( 'NOTICE', '$conversation_transcript: ' . $conversation_transcript);
        
        // Now send the $conversation_transcript via email to the email address specified in the option
        $email_address = esc_attr(get_option('chatbot_mistral_conversation_transcript_email', ''));

        if (!empty($email_address)) {
            wp_mail($email_address, 'Conversation Transcript', $conversation_transcript);
            back_trace( 'NOTICE', 'Conversation transcript sent to ' . $email_address);
        } else {
            back_trace( 'NOTICE', 'No email address specified in the option');
        }

        // Then remove the "[conversation_transcript]" from the response
        $data['choices'][0]['message']['content'] = str_replace('[conversation_transcript]', '', $data['choices'][0]['message']['content']);

    } else {

        // DIAG - Diagnostics - Ver 2.3.0
        back_trace( 'NOTICE', 'The response does not contain the string "[conversation_transcript]"');

    }

    // Extract the response text
    if (isset($data['choices'][0]['message']['content'])) {
        $response_text = $data['choices'][0]['message']['content'];
        
        // Store the thread_id if provided in the response
        if (isset($data['thread_id'])) {
            $thread_id = $data['thread_id'];
            set_chatbot_chatgpt_transients('thread_id', $thread_id, $user_id, $page_id, $session_id, null);
        }

        return $response_text;
    }

    return 'Error: Invalid response from Mistral API';

}