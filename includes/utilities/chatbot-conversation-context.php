<?php
/**
 * Kognetiks Chatbot - Chatbot Conversation Context
 *
 * This file contains the code for building the conversation context for the chatbot.
 *
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

/**
 * Build conversation context from history - Ver 2.3.9
 * 
 * Standardizes conversation history building across all API implementations.
 * Returns an array of messages in a standardized format that can be adapted
 * for different APIs (OpenAI uses 'content', Google uses 'parts', etc.)
 *
 * @param string $format Optional. Format type: 'standard' (default) or 'google'.
 *                       'standard' returns ['role' => 'user', 'content' => 'message']
 *                       'google' returns ['role' => 'user', 'parts' => [['text' => 'message']]]
 * @param int $max_pairs Optional. Maximum number of conversation pairs to include. Default 10.
 * @param string $session_id Optional. Session ID for conversation continuity. Default null.
 * @param string $transient_name Optional. Transient name for context history. Default 'chatbot_chatgpt_context_history'.
 * @return array Array of conversation messages in the specified format
 */

function chatbot_chatgpt_build_conversation_context($format = 'standard', $max_pairs = 10, $session_id = null, $transient_name = 'chatbot_chatgpt_context_history') {
    
    global $learningMessages;
    global $errorResponses;
    
    // Get conversation history array (not concatenated) - Ver 2.3.9+
    // The context_history transient stores messages in chronological order
    // Entries alternate: user message, model response, user message, model response, etc.
    $context_history_array = get_transient($transient_name);
    if (!$context_history_array) {
        $context_history_array = [];
    }

    // Build conversation history array
    $conversation_messages = array();
    $history_count = count($context_history_array);
    
    // Limit conversation history to last N pairs (2N messages) to avoid token limits - Ver 2.3.9+
    // Keep the most recent conversation pairs
    $max_history_entries = $max_pairs * 2;
    if ($history_count > $max_history_entries) {
        // Keep only the most recent entries
        $context_history_array = array_slice($context_history_array, -$max_history_entries);
        $history_count = count($context_history_array);
    }
    
    // Get localized messages if needed
    if (get_locale() !== "en_US") {
        $localized_learningMessages = get_localized_learningMessages(get_locale(), $learningMessages);
        $localized_errorResponses = get_localized_errorResponses(get_locale(), $errorResponses);
    } else {
        $localized_learningMessages = $learningMessages;
        $localized_errorResponses = $errorResponses;
    }
    
    // Process history in pairs (user, model) - Ver 2.3.9+
    // Start from the beginning and pair up messages
    for ($i = 0; $i < $history_count; $i += 2) {
        // User message (even indices)
        if (isset($context_history_array[$i])) {
            $user_msg = $context_history_array[$i];
            // Strip unwanted content
            $user_msg = preg_replace('/\[URL:.*?\]/', '', $user_msg);
            $user_msg = str_replace($localized_learningMessages, '', $user_msg);
            $user_msg = str_replace($localized_errorResponses, '', $user_msg);
            $user_msg = trim($user_msg);
            
            if (!empty($user_msg)) {
                if ($format === 'google') {
                    $conversation_messages[] = array(
                        'role' => 'user',
                        'parts' => array(
                            array('text' => $user_msg)
                        )
                    );
                } else {
                    // Standard format (OpenAI, Anthropic, etc.)
                    $conversation_messages[] = array(
                        'role' => 'user',
                        'content' => $user_msg
                    );
                }
            }
        }
        
        // Model response (odd indices)
        if (isset($context_history_array[$i + 1])) {
            $model_msg = $context_history_array[$i + 1];
            // Strip unwanted content
            $model_msg = preg_replace('/\[URL:.*?\]/', '', $model_msg);
            $model_msg = str_replace($localized_learningMessages, '', $model_msg);
            $model_msg = str_replace($localized_errorResponses, '', $model_msg);
            $model_msg = trim($model_msg);
            
            if (!empty($model_msg)) {
                if ($format === 'google') {
                    $conversation_messages[] = array(
                        'role' => 'model',
                        'parts' => array(
                            array('text' => $model_msg)
                        )
                    );
                } else {
                    // Standard format (OpenAI, Anthropic, etc.)
                    $conversation_messages[] = array(
                        'role' => 'assistant',
                        'content' => $model_msg
                    );
                }
            }
        }
    }

    // Conversation Continuity - Ver 2.3.9
    // If enabled, also add session-based conversation history
    // Note: This returns the session history as a string for appending to base context
    // The structured messages array is separate and doesn't include session history
    $session_history_string = '';
    $chatbot_chatgpt_conversation_continuation = esc_attr(get_option('chatbot_chatgpt_conversation_continuation', 'Off'));
    if ($chatbot_chatgpt_conversation_continuation == 'On' && !empty($session_id)) {
        $session_history = chatbot_chatgpt_get_converation_history($session_id);
        if (!empty($session_history)) {
            // Session history is a concatenated string, so we'll return it separately
            // This provides additional context without breaking the structured messages array
            $session_history_string = $session_history;
        }
    }
    
    // Return both the structured messages and session history string
    return array(
        'messages' => $conversation_messages,
        'session_history' => $session_history_string
    );
    
}
