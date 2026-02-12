<?php
/**
 * Kognetiks Chatbot - Responses API - Ver 2.4.5
 *
 * This file contains the code for access the OpenAI Responses API.
 * 
 * OpenAI now has Assistant-like and Thread-like objects in the Responses API. Learn more in the migration guide.
 * As of August 26th, 2025, we’re deprecating the Assistants API, with a sunset date of August 26, 2026.
 * 
 * https://developers.openai.com/api/reference/responses/overview
 * 
 * End points
 * https://api.openai.com/v1/responses
 * https://api.openai.com/v1/conversations
 * 
 * Responses/Conversations now start with "pmpt_" instead of "asst_"
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

/* -------------------------------------------------------------------------
 * Timeouts and execution-time helpers (kept for compatibility)
 * ------------------------------------------------------------------------- */
function chatbot_chatgpt_get_timeout_responses() {

    // Keep the existing option name for compatibility, but treat it as a Responses timeout.
    $base_timeout = intval( get_option( 'chatbot_chatgpt_assistant_timeout', 45 ) );
    $base_timeout = max( 15, min( 180, $base_timeout ) );
    return $base_timeout;

}

function chatbot_chatgpt_increase_execution_time_responses() {

    // Best effort; WordPress hosting may ignore.
    @set_time_limit( 0 );
    @ini_set( 'max_execution_time', '360' );

}

function chatbot_chatgpt_restore_execution_time_responses() {

    // No-op: we can’t reliably restore prior ini values in shared hosting.
    // Best effort; WordPress hosting may ignore.
    @set_time_limit( 0 );
    @ini_set( 'max_execution_time', '360' );

}

/* -------------------------------------------------------------------------
 * OpenAI endpoints (Responses + Conversations)
 * ------------------------------------------------------------------------- */
function kchat_openai_conversations_url() {
    return 'https://api.openai.com/v1/conversations';
}

function kchat_openai_responses_url() {
    return 'https://api.openai.com/v1/responses';
}

/* -------------------------------------------------------------------------
 * Minimal HTTP helper using WP HTTP API
 * ------------------------------------------------------------------------- */
function kchat_openai_http_post_json( $url, $api_key, $payload, $timeout = 45, $idempotency_key = '' ) {

    // DIAG - Diagnostics - Ver 2.4.5
    if ( defined('WP_DEBUG') && WP_DEBUG ) {
        back_trace('NOTICE', 'kchat_openai_http_post_json');
        back_trace('NOTICE', 'URL: ' . $url);
        back_trace('NOTICE', 'Payload: ' . print_r($payload, true));
        back_trace('NOTICE', 'Timeout: ' . $timeout);
        back_trace('NOTICE', 'Idempotency key: ' . $idempotency_key);
    }

    $headers = array(
        'Content-Type'  => 'application/json',
        'Authorization' => 'Bearer ' . $api_key,
    );

    // Optional idempotency key (helps avoid duplicate charges on retries).
    if ( ! empty( $idempotency_key ) ) {
        $headers['Idempotency-Key'] = $idempotency_key;
    }

    $args = array(
        'headers' => $headers,
        'body'    => wp_json_encode( $payload ),
        'timeout' => $timeout,
    );

    $resp = wp_remote_post( $url, $args );

    if ( is_wp_error( $resp ) ) {
        // DIAG - Diagnostics - Ver 2.4.5
        if ( defined('WP_DEBUG') && WP_DEBUG ) {
            back_trace('NOTICE', 'OpenAI Responses API error: ' . $resp->get_error_message());
        }
        return array( 'error' => array( 'message' => $resp->get_error_message() ) );
    }

    $code = wp_remote_retrieve_response_code( $resp );
    $body = wp_remote_retrieve_body( $resp );

    $json = json_decode( $body, true );

    // DIAG - Log HTTP result so we can see API errors.
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        back_trace( 'NOTICE', 'HTTP response code: ' . $code );
        if ( $code < 200 || $code >= 300 ) {
            back_trace( 'NOTICE', 'HTTP error body: ' . substr( $body, 0, 1000 ) );
        }
    }

    if ( $code < 200 || $code >= 300 ) {
        // Preserve OpenAI-style error shape when possible.
        if ( is_array( $json ) ) { return $json; }
        return array( 'error' => array( 'message' => 'HTTP ' . $code . ': ' . $body ) );
    }

    return is_array( $json ) ? $json : array( 'error' => array( 'message' => 'Invalid JSON response from OpenAI.' ) );

}

/* -------------------------------------------------------------------------
 * Conversation creation
 * ------------------------------------------------------------------------- */
function kchat_openai_create_conversation( $api_key, $meta = array(), $timeout = 45 ) {

    // DIAG - Diagnostics - Ver 2.4.5
    if ( defined('WP_DEBUG') && WP_DEBUG ) {
        back_trace('NOTICE', 'kchat_openai_create_conversation');
        back_trace('NOTICE', 'Meta: ' . print_r($meta, true));
        back_trace('NOTICE', 'Timeout: ' . $timeout);
    }

    $payload = array();

    // The Conversations API supports initial items; we start mostly empty.
    // If you want traceability, we store metadata as a developer message.
    if ( ! empty( $meta ) ) {
        $payload['items'] = array(
            array(
                'type'    => 'message',
                'role'    => 'developer',
                'content' => 'Conversation metadata: ' . wp_json_encode( $meta ),
            ),
        );
    }

    return kchat_openai_http_post_json( kchat_openai_conversations_url(), $api_key, $payload, $timeout );

}

/* -------------------------------------------------------------------------
 * Extract text from a Responses API payload
 * ------------------------------------------------------------------------- */
function kchat_openai_extract_output_text( $response_json ) {

    // DIAG - Diagnostics - Ver 2.4.5
    if ( defined('WP_DEBUG') && WP_DEBUG ) {
        back_trace('NOTICE', 'kchat_openai_extract_output_text');
        back_trace('NOTICE', 'Response JSON: ' . print_r($response_json, true));
    }

    if ( ! is_array( $response_json ) ) {
        return '';
    }

    $out = '';

    // Responses return an "output" array with typed items; text appears in:
    // message items -> content[] -> {type:"output_text", text:"..."}.
    if ( isset( $response_json['output'] ) && is_array( $response_json['output'] ) ) {
        foreach ( $response_json['output'] as $item ) {
            if ( ! is_array( $item ) ) { continue; }

            if ( ( $item['type'] ?? '' ) === 'message' && isset( $item['content'] ) && is_array( $item['content'] ) ) {
                foreach ( $item['content'] as $c ) {
                    if ( is_array( $c ) && ( $c['type'] ?? '' ) === 'output_text' && isset( $c['text'] ) ) {
                        $out .= (string) $c['text'];
                    }
                }
            }

            // Some models may emit a top-level "output_text" item type.
            if ( ( $item['type'] ?? '' ) === 'output_text' && isset( $item['text'] ) ) {
                $out .= (string) $item['text'];
            }
        }
    }

    return trim( $out );

}

/* -------------------------------------------------------------------------
 * Tool-call detection (compatibility: the old Assistants file checked tools)
 * ------------------------------------------------------------------------- */
function check_assistant_tool_usage_responses( $response_json ) {

    // DIAG - Diagnostics - Ver 2.4.5
    if ( defined('WP_DEBUG') && WP_DEBUG ) {
        back_trace('NOTICE', 'check_assistant_tool_usage_responses');
        back_trace('NOTICE', 'Response JSON: ' . print_r($response_json, true));
    }

    if ( ! is_array( $response_json ) ) { return false; }
    if ( empty( $response_json['output'] ) || ! is_array( $response_json['output'] ) ) { return false; }

    foreach ( $response_json['output'] as $item ) {
        if ( ! is_array( $item ) ) { continue; }
        $t = $item['type'] ?? '';
        if ( strpos( $t, 'tool' ) !== false || strpos( $t, 'call' ) !== false ) {
            return true;
        }
        if ( $t === 'function_call' ) { return true; }
    }
    return false;

}

/* -------------------------------------------------------------------------
 * Main call used by the plugin (signature preserved)
 * ------------------------------------------------------------------------- */
function chatbot_chatgpt_custom_pmpt_call_api( $api_key, $message, $assistant_id, $thread_id, $session_id, $user_id, $page_id, $client_message_id = null ) {

    // DIAG - Diagnostics - Ver 2.4.5
    if ( defined('WP_DEBUG') && WP_DEBUG ) {
        back_trace('NOTICE', 'chatbot_chatgpt_custom_pmpt_call_api');
        back_trace('NOTICE', 'Message: ' . $message);
        back_trace('NOTICE', 'Assistant ID: ' . $assistant_id);
    }
    // Migration behavior:
    // - $assistant_id => Prompt ID (pmpt_...)
    // - $thread_id    => Conversation ID (cnv_...)
    // Variable names preserved for compatibility with the rest of the plugin.

    // Decrypt the API key if the plugin provides a helper.
    if ( function_exists( 'chatbot_chatgpt_decrypt_api_key' ) ) {
        $api_key = chatbot_chatgpt_decrypt_api_key( $api_key );
    }

    $api_key = trim( (string) $api_key );
    if ( empty( $api_key ) ) {
        return 'Error: Missing OpenAI API key.';
    }

    $prompt_id = trim( (string) $assistant_id );
    if ( empty( $prompt_id ) ) {
        return 'Error: Missing OpenAI Prompt ID (pmpt_...). Create a Prompt from your Assistant in the OpenAI dashboard and store its ID.';
    }

    $message = (string) $message;
    if ( $message === '' ) {
        return 'Error: Empty message.';
    }

    // Idempotency: reuse client_message_id if provided, else generate UUID.
    $message_uuid = $client_message_id ? (string) $client_message_id : ( function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'kchat_', true ) );

    // Lock key: prevent double-submit races across concurrent AJAX calls.
    $conv_lock     = 'kchat_conv_lock_' . wp_hash( $prompt_id . '|' . $user_id . '|' . $page_id . '|' . $session_id );
    $lock_timeout  = 60;

    if ( function_exists( 'get_transient' ) && function_exists( 'set_transient' ) ) {
        if ( get_transient( $conv_lock ) ) {
            return 'Error: Conversation is busy. Please retry.';
        }
        set_transient( $conv_lock, 1, $lock_timeout );
    }

    chatbot_chatgpt_increase_execution_time_responses();
    $timeout = chatbot_chatgpt_get_timeout_responses();

    try {

        // -----------------------------------------------------------------
        // Step 1: Resolve or create the conversation (cnv_...)
        // -----------------------------------------------------------------

        // DIAG - Diagnostics - Ver 2.4.5
        if ( defined('WP_DEBUG') && WP_DEBUG ) {
            back_trace('NOTICE', 'Step 1: Resolve or create the conversation (cnv_...)');
            back_trace('NOTICE', 'Message: ' . $message);
            back_trace('NOTICE', 'User ID: ' . $user_id);
            back_trace('NOTICE', 'Page ID: ' . $page_id);
            back_trace('NOTICE', 'Session ID: ' . $session_id);
            back_trace('NOTICE', 'Assistant ID: ' . $assistant_id);
            back_trace('NOTICE', 'Client Message ID: ' . $client_message_id);
        }

        if ( empty( $thread_id ) ) {

            // If the plugin maintains a stored thread_id/conversation_id, reuse it.
            if ( function_exists( 'get_chatbot_chatgpt_threads' ) ) {
                $stored = get_chatbot_chatgpt_threads( $user_id, $session_id, $page_id, $prompt_id );
                if ( ! empty( $stored ) ) {
                    $thread_id = $stored;
                }
            }
        }

        if ( empty( $thread_id ) ) {

            $meta = array(
                'session_id' => (string) $session_id,
                'user_id'    => (string) $user_id,
                'page_id'    => (string) $page_id,
                'prompt_id'  => (string) $prompt_id,
            );

            $conv = kchat_openai_create_conversation( $api_key, $meta, $timeout );

            if ( isset( $conv['error'] ) ) {
                $msg = is_array( $conv['error'] ) ? ( $conv['error']['message'] ?? 'Conversation create failed.' ) : 'Conversation create failed.';
                return 'Error: ' . $msg;
            }

            $thread_id = $conv['id'] ?? '';
            if ( empty( $thread_id ) ) {
                return 'Error: Conversation created but missing ID.';
            }

            // Persist the new conversation id using existing helper (kept for compatibility).
            if ( function_exists( 'set_chatbot_chatgpt_threads' ) ) {
                set_chatbot_chatgpt_threads( $thread_id, $prompt_id, $user_id, $page_id );
            }

        }

        // -----------------------------------------------------------------
        // Step 2: Create the model response (Responses API)
        // -----------------------------------------------------------------

        // DIAG - Diagnostics - Ver 2.4.5
        if ( defined('WP_DEBUG') && WP_DEBUG ) {
            back_trace('NOTICE', 'Step 2: Create the model response (Responses API)');
            back_trace('NOTICE', 'Message: ' . $message);
            back_trace('NOTICE', 'User ID: ' . $user_id);
            back_trace('NOTICE', 'Page ID: ' . $page_id);
            back_trace('NOTICE', 'Session ID: ' . $session_id);
            back_trace('NOTICE', 'Assistant ID: ' . $assistant_id);
            back_trace('NOTICE', 'Client Message ID: ' . $client_message_id);
        }

        $payload = array(
            // The conversation that this response belongs to. Conversation items are
            // prepended automatically and the new items are appended after completion.
            'conversation' => $thread_id,
            // Reference your migrated Prompt (created from the former Assistant).
            // API expects prompt.id, not prompt.prompt_id.
            'prompt'       => array(
                'id' => $prompt_id,
            ),
            // The new user input for this turn.
            'input'        => array(
                array(
                    'role'    => 'user',
                    'content' => $message,
                ),
            ),
            // Helpful for abuse detection without sending PII.
            'safety_identifier' => wp_hash( (string) $user_id ),
            // Let the API auto-truncate old items if context would overflow.
            'truncation'   => 'auto',
        );

        $resp = kchat_openai_http_post_json( kchat_openai_responses_url(), $api_key, $payload, $timeout, $message_uuid );

        // DIAG - Log API response so we can see success vs error and payload shape.
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            if ( isset( $resp['error'] ) ) {
                back_trace( 'NOTICE', 'Responses API returned error: ' . print_r( $resp['error'], true ) );
                back_trace( 'NOTICE', 'Full response: ' . print_r( $resp, true ) );
            } else {
                back_trace( 'NOTICE', 'Responses API success. Has output: ' . ( isset( $resp['output'] ) ? 'yes' : 'no' ) );
            }
        }

        if ( isset( $resp['error'] ) ) {
            $msg = is_array( $resp['error'] ) ? ( $resp['error']['message'] ?? 'OpenAI error.' ) : 'OpenAI error.';
            return 'Error: ' . $msg;
        }

        // Add the usage to the conversation tracker (Responses API: input_tokens = Prompt, output_tokens = Completion)
        if ( is_array( $resp ) && isset( $resp['usage'] ) && is_array( $resp['usage'] ) && function_exists( 'append_message_to_conversation_log' ) ) {
            $usage = $resp['usage'];
            if ( isset( $usage['input_tokens'] ) ) {
                append_message_to_conversation_log( $session_id, $user_id, $page_id, 'Prompt Tokens', $thread_id, $assistant_id, null, $usage['input_tokens'] );
            }
            if ( isset( $usage['output_tokens'] ) ) {
                append_message_to_conversation_log( $session_id, $user_id, $page_id, 'Completion Tokens', $thread_id, $assistant_id, null, $usage['output_tokens'] );
            }
            if ( isset( $usage['total_tokens'] ) ) {
                append_message_to_conversation_log( $session_id, $user_id, $page_id, 'Total Tokens', $thread_id, $assistant_id, null, $usage['total_tokens'] );
            }
        }

        // -----------------------------------------------------------------
        // Step 3: Extract assistant output
        // -----------------------------------------------------------------

        // DIAG - Diagnostics - Ver 2.4.5
        if ( defined('WP_DEBUG') && WP_DEBUG ) {
            back_trace('NOTICE', 'Step 3: Extract assistant output');
            back_trace('NOTICE', 'Response: ' . print_r($resp, true));
        }

        // Extract text first. Responses API runs tools (e.g. file_search) server-side
        // and returns the final assistant message, so we use it when present.
        $text = kchat_openai_extract_output_text( $resp );

        if ( $text !== '' ) {
            return $text;
        }

        // No text: if the response included tool calls we can't fulfill client-side, explain.
        if ( check_assistant_tool_usage_responses( $resp ) ) {
            return 'Error: This Prompt triggered tool calls. Update the Prompt to disable tools for this chat endpoint, or implement a tool-call orchestration loop for Responses.';
        }

        return 'Error: Empty response from OpenAI.';

    } finally {

        // DIAG - Diagnostics - Ver 2.4.5
        if ( defined('WP_DEBUG') && WP_DEBUG ) {
            back_trace('NOTICE', 'Restore execution time');
        }

        chatbot_chatgpt_restore_execution_time_responses();

        if ( function_exists( 'delete_transient' ) ) {
            delete_transient( $conv_lock );
        }
    }
}

/* -------------------------------------------------------------------------
 * Back-compat stubs for older code paths that may still call these helpers.
 * ------------------------------------------------------------------------- */

// function createAnAssistant() {
//     return array( 'error' => array( 'message' => 'Assistants API deprecated. Create a Prompt in the dashboard and use Responses API.' ) );
// }

// function addAMessage() {
//     return array( 'error' => array( 'message' => 'Assistants API deprecated. Use Responses API with conversation + input.' ) );
// }

// function runTheAssistant() {
//     return array( 'error' => array( 'message' => 'Assistants API deprecated. Use Responses API.' ) );
// }

// function getTheRunsStatus() {
//     return array( 'error' => array( 'message' => 'Assistants API deprecated. Use Responses API.' ) );
// }

// function getTheRunsSteps() {
//     return array( 'error' => array( 'message' => 'Assistants API deprecated. Use Responses API.' ) );
// }

// function getTheStepsStatus() {
//     return array( 'error' => array( 'message' => 'Assistants API deprecated. Use Responses API.' ) );
// }

// function getTheMessage() {
//     return array( 'error' => array( 'message' => 'Assistants API deprecated. Use Responses API.' ) );
// }

// function cancel_active_run() {
//     return false; // no runs in this pathway
// }

/* -------------------------------------------------------------------------
 * File utilities (kept for compatibility with other plugin modules)
 * ------------------------------------------------------------------------- */

function chatbot_chatgpt_retrieve_file_id_responses( $api_key ) {

    if ( function_exists( 'chatbot_chatgpt_decrypt_api_key' ) ) {
        $api_key = chatbot_chatgpt_decrypt_api_key( $api_key );
    }

    $api_key = trim( (string) $api_key );
    if ( empty( $api_key ) ) {
        return '';
    }

    $url = 'https://api.openai.com/v1/files';
    $headers = array(
        'Content-Type'  => 'application/json',
        'Authorization' => 'Bearer ' . $api_key,
    );

    $resp = wp_remote_get( $url, array( 'headers' => $headers, 'timeout' => 45 ) );
    if ( is_wp_error( $resp ) ) { return ''; }

    $json = json_decode( wp_remote_retrieve_body( $resp ), true );
    if ( ! is_array( $json ) ) { return ''; }

    if ( isset( $json['data'][0]['id'] ) ) {
        return (string) $json['data'][0]['id'];
    }

    return '';
}

function delete_uploaded_file_responses( $api_key, $file_id ) {

    if ( function_exists( 'chatbot_chatgpt_decrypt_api_key' ) ) {
        $api_key = chatbot_chatgpt_decrypt_api_key( $api_key );
    }

    $api_key  = trim( (string) $api_key );
    $file_id  = trim( (string) $file_id );

    if ( empty( $api_key ) || empty( $file_id ) ) {
        return false;
    }

    $url = 'https://api.openai.com/v1/files/' . rawurlencode( $file_id );

    $resp = wp_remote_request( $url, array(
        'method'  => 'DELETE',
        'timeout' => 45,
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
        ),
    ) );

    if ( is_wp_error( $resp ) ) {
        return false;
    }

    $code = wp_remote_retrieve_response_code( $resp );
    return ( $code >= 200 && $code < 300 );
}
