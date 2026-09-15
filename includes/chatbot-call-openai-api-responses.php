<?php
/**
 * Kognetiks Chatbot - Responses API - Ver 2.4.5
 *
 * This file contains the code for access the OpenAI Responses API.
 * 
 * OpenAI now has Assistant-like and Thread-like objects in the Responses API. Learn more in the migration guide.
 * Assistants API was sunset on August 26, 2026. OpenAI prompt objects (pmpt_)
 * remain a temporary bridge until November 30, 2026. This file sends both
 * asst_ and pmpt_ traffic through Responses + Conversations, overlaying
 * Common Name and Additional Instructions stored in WordPress.
 * 
 * https://developers.openai.com/api/reference/responses/overview
 * 
 * End points
 * https://api.openai.com/v1/responses
 * https://api.openai.com/v1/conversations
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
    // back_trace('NOTICE', 'kchat_openai_http_post_json');
    // back_trace('NOTICE', 'URL: ' . $url);
    // back_trace('NOTICE', 'Payload: ' . print_r($payload, true));
    // back_trace('NOTICE', 'Timeout: ' . $timeout);
    // back_trace('NOTICE', 'Idempotency key: ' . $idempotency_key);

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
        // back_trace('NOTICE', 'OpenAI Responses API error: ' . $resp->get_error_message());
        return array( 'error' => array( 'message' => $resp->get_error_message() ) );
    }

    $code = wp_remote_retrieve_response_code( $resp );
    $body = wp_remote_retrieve_body( $resp );

    $json = json_decode( $body, true );

    // DIAG - Diagnostics - Ver 2.4.5
    // back_trace( 'NOTICE', 'HTTP response code: ' . $code );
    // if ( $code < 200 || $code >= 300 ) {
    //     // DIAG - Diagnostics - Ver 2.4.5
    //     back_trace( 'NOTICE', 'HTTP error body: ' . substr( $body, 0, 1000 ) );
    // }

    if ( $code < 200 || $code >= 300 ) {
        // Preserve OpenAI-style error shape when possible.
        if ( is_array( $json ) ) { return $json; }
        return array( 'error' => array( 'message' => 'HTTP ' . $code . ': ' . $body ) );
    }

    return is_array( $json ) ? $json : array( 'error' => array( 'message' => 'Invalid JSON response from OpenAI.' ) );

}

/* -------------------------------------------------------------------------
 * OpenAI file metadata (filename) and content - for PDF vs text-embed path
 * ------------------------------------------------------------------------- */
/** Max characters for inlined file content (non-PDF) to avoid huge prompts. */
const CHATBOT_OPENAI_RESPONSES_FILE_CONTENT_MAX_CHARS = 20000;

/**
 * Get OpenAI file metadata (e.g. filename) via GET /v1/files/{file_id}.
 * Used to infer PDF vs non-PDF when building Responses input.
 *
 * @param string $api_key Decrypted API key.
 * @param string $file_id OpenAI file id (e.g. file-xxx).
 * @return array{filename?: string} Non-empty with 'filename' key on success, empty on failure.
 */
function chatbot_openai_get_file_metadata( $api_key, $file_id ) {

    $api_key = trim( (string) $api_key );
    $file_id = trim( (string) $file_id );
    if ( empty( $api_key ) || empty( $file_id ) ) {
        return array();
    }

    $url = 'https://api.openai.com/v1/files/' . rawurlencode( $file_id );
    $resp = wp_remote_get( $url, array(
        'headers' => array( 'Authorization' => 'Bearer ' . $api_key ),
        'timeout' => 30,
    ) );

    if ( is_wp_error( $resp ) ) {
        return array();
    }
    $code = wp_remote_retrieve_response_code( $resp );
    if ( $code < 200 || $code >= 300 ) {
        return array();
    }
    $body = wp_remote_retrieve_body( $resp );
    $json = json_decode( $body, true );
    if ( ! is_array( $json ) || empty( $json['filename'] ) ) {
        return array();
    }
    return array( 'filename' => (string) $json['filename'] );
}

/**
 * Fetch file content from OpenAI via GET /v1/files/{file_id}/content.
 * Used for non-PDF files (e.g. txt, md, json) to inline as input_text.
 *
 * @param string $api_key Decrypted API key.
 * @param string $file_id OpenAI file id (e.g. file-xxx).
 * @return string|WP_Error File content as string, or WP_Error on failure.
 */
function chatbot_openai_get_file_content( $api_key, $file_id ) {

    $api_key = trim( (string) $api_key );
    $file_id = trim( (string) $file_id );
    if ( empty( $api_key ) || empty( $file_id ) ) {
        return new WP_Error( 'missing_params', __( 'Missing API key or file ID.', 'chatbot-chatgpt' ) );
    }

    $url = 'https://api.openai.com/v1/files/' . rawurlencode( $file_id ) . '/content';
    $resp = wp_remote_get( $url, array(
        'headers' => array( 'Authorization' => 'Bearer ' . $api_key ),
        'timeout' => 30,
    ) );

    if ( is_wp_error( $resp ) ) {
        return $resp;
    }
    $code = wp_remote_retrieve_response_code( $resp );
    if ( $code < 200 || $code >= 300 ) {
        $body = wp_remote_retrieve_body( $resp );
        $msg = sprintf(
            /* translators: 1: HTTP code, 2: response body snippet */
            __( 'Failed to fetch file content (HTTP %1$s): %2$s', 'chatbot-chatgpt' ),
            $code,
            substr( $body, 0, 200 )
        );
        return new WP_Error( 'file_content_fetch_failed', $msg );
    }
    return wp_remote_retrieve_body( $resp );
}

/* -------------------------------------------------------------------------
 * Conversation creation
 * ------------------------------------------------------------------------- */
function kchat_openai_create_conversation( $api_key, $meta = array(), $timeout = 45 ) {

    // DIAG - Diagnostics - Ver 2.4.5
    // back_trace('NOTICE', 'kchat_openai_create_conversation');
    // back_trace('NOTICE', 'Meta: ' . print_r($meta, true));
    // back_trace('NOTICE', 'Timeout: ' . $timeout);

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
    // back_trace('NOTICE', 'kchat_openai_extract_output_text');
    // back_trace('NOTICE', 'Response JSON: ' . print_r($response_json, true));

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
    // back_trace('NOTICE', 'check_assistant_tool_usage_responses');
    // back_trace('NOTICE', 'Response JSON: ' . print_r($response_json, true));

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
 * Local Common Name + Additional Instructions for Responses calls
 * ------------------------------------------------------------------------- */
function chatbot_chatgpt_get_responses_local_row( $assistant_id ) {

    if ( ! function_exists( 'get_chatbot_chatgpt_assistant_by_assistant_id' ) ) {
        return array();
    }

    $row = get_chatbot_chatgpt_assistant_by_assistant_id( $assistant_id );
    return is_array( $row ) ? $row : array();

}

/**
 * Resolve extra instructions: session transient, then kchat_settings, then the assistants table.
 *
 * @param string $assistant_id
 * @param mixed  $user_id
 * @param mixed  $page_id
 * @param mixed  $session_id
 * @param array  $local_row
 * @return string
 */
function chatbot_chatgpt_resolve_local_additional_instructions( $assistant_id, $user_id, $page_id, $session_id, $local_row = array() ) {

    if ( function_exists( 'get_chatbot_chatgpt_transients' ) ) {
        $from_transient = get_chatbot_chatgpt_transients( 'additional_instructions', $user_id, $page_id, $session_id );
        if ( is_string( $from_transient ) && trim( $from_transient ) !== '' ) {
            return trim( wp_unslash( $from_transient ) );
        }
    }

    global $kchat_settings;
    if ( isset( $kchat_settings['additional_instructions'] ) && is_string( $kchat_settings['additional_instructions'] ) && trim( $kchat_settings['additional_instructions'] ) !== '' ) {
        return trim( wp_unslash( $kchat_settings['additional_instructions'] ) );
    }

    if ( ! empty( $local_row['additional_instructions'] ) && is_string( $local_row['additional_instructions'] ) ) {
        return trim( $local_row['additional_instructions'] );
    }

    return '';

}

/**
 * Build Responses `instructions` from Common Name + additional instructions.
 * Used for asst_ IDs (no hosted prompt object).
 *
 * @param array  $local_row
 * @param string $additional_instructions
 * @return string
 */
function chatbot_chatgpt_build_responses_instructions( $local_row, $additional_instructions ) {

    $parts = array();

    $common_name = '';
    if ( ! empty( $local_row['common_name'] ) && is_string( $local_row['common_name'] ) ) {
        $common_name = trim( $local_row['common_name'] );
    }

    $reserved_names = array( 'primary', 'alternate' );
    if ( $common_name !== '' && ! in_array( strtolower( $common_name ), $reserved_names, true ) ) {
        $parts[] = sprintf( 'You are %s.', $common_name );
    }

    if ( is_string( $additional_instructions ) && trim( $additional_instructions ) !== '' ) {
        $parts[] = trim( $additional_instructions );
    }

    if ( function_exists( 'chatbot_chatgpt_parse_vector_store_ids' ) && ! empty( $local_row['vector_store_id'] ) ) {
        $vs_ids = chatbot_chatgpt_parse_vector_store_ids( $local_row['vector_store_id'] );
        if ( ! empty( $vs_ids ) ) {
            $parts[] = 'When answering, use the file_search tool against the attached documentation. If the documentation does not contain the answer, say so.';
        }
    }

    if ( empty( $parts ) ) {
        $fallback = get_option( 'chatbot_chatgpt_conversation_context', 'You are a versatile, friendly, and helpful assistant designed to support me in a variety of tasks that responds in Markdown.' );
        if ( is_string( $fallback ) && trim( $fallback ) !== '' ) {
            $parts[] = trim( $fallback );
        }
    }

    return implode( "\n\n", $parts );

}

/**
 * Model for asst_ Responses calls (prompt objects already include a model).
 *
 * @param mixed $user_id
 * @param mixed $page_id
 * @param mixed $session_id
 * @return string
 */
function chatbot_chatgpt_resolve_responses_model( $user_id, $page_id, $session_id ) {

    $model = '';
    if ( function_exists( 'get_chatbot_chatgpt_transients' ) ) {
        $model = get_chatbot_chatgpt_transients( 'model', $user_id, $page_id, $session_id );
    }
    if ( ! is_string( $model ) || trim( $model ) === '' ) {
        $model = get_option( 'chatbot_chatgpt_model_choice', 'gpt-3.5-turbo' );
    }

    return sanitize_text_field( (string) $model );

}

/**
 * Vector store IDs for Responses file_search (vs_...).
 *
 * @param array $local_row Assistants table row.
 * @return string[]
 */
function chatbot_chatgpt_resolve_vector_store_ids( $local_row ) {

    $raw = '';
    if ( is_array( $local_row ) && ! empty( $local_row['vector_store_id'] ) && is_string( $local_row['vector_store_id'] ) ) {
        $raw = $local_row['vector_store_id'];
    }

    if ( function_exists( 'chatbot_chatgpt_parse_vector_store_ids' ) ) {
        return chatbot_chatgpt_parse_vector_store_ids( $raw );
    }

    return array();

}

/* -------------------------------------------------------------------------
 * Main call used by the plugin (signature preserved)
 * ------------------------------------------------------------------------- */
function chatbot_chatgpt_custom_pmpt_call_api( $api_key, $message, $assistant_id, $thread_id, $session_id, $user_id, $page_id, $client_message_id = null ) {

    // DIAG - Diagnostics - Ver 2.4.5
    // back_trace('NOTICE', 'chatbot_chatgpt_custom_pmpt_call_api');
    // back_trace('NOTICE', 'Message: ' . $message);
    // back_trace('NOTICE', 'Assistant ID: ' . $assistant_id);

    // Migration behavior:
    // - $assistant_id => Prompt ID (pmpt_...) or former Assistant ID (asst_...)
    // - $thread_id    => Conversation ID (cnv_...)
    // Variable names preserved for compatibility with the rest of the plugin.
    // asst_ uses model + local instructions. pmpt_ still references the hosted
    // prompt object until OpenAI retires v1/prompts (2026-11-30), with local
    // Additional Instructions added as a developer message.

    // Decrypt the API key if the plugin provides a helper.
    if ( function_exists( 'chatbot_chatgpt_decrypt_api_key' ) ) {
        $api_key = chatbot_chatgpt_decrypt_api_key( $api_key );
    }

    $api_key = trim( (string) $api_key );
    if ( empty( $api_key ) ) {
        return __( 'Error: Missing OpenAI API key.', 'chatbot-chatgpt' );
    }

    $prompt_id = trim( (string) $assistant_id );
    if ( empty( $prompt_id ) ) {
        return __( 'Error: Missing OpenAI Prompt ID (pmpt_...) or Assistant ID (asst_...). Store the ID in GPT Assistants and copy important instructions into Additional Instructions.', 'chatbot-chatgpt' );
    }

    $message = (string) $message;
    if ( $message === '' ) {
        return __( 'Error: Empty message.', 'chatbot-chatgpt' );
    }

    // Idempotency: reuse client_message_id if provided, else generate UUID.
    $message_uuid = $client_message_id ? (string) $client_message_id : ( function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'kchat_', true ) );

    // Lock key: prevent double-submit races across concurrent AJAX calls.
    $conv_lock     = 'kchat_conv_lock_' . wp_hash( $prompt_id . '|' . $user_id . '|' . $page_id . '|' . $session_id );
    $lock_timeout  = 60;

    if ( function_exists( 'get_transient' ) && function_exists( 'set_transient' ) ) {
        if ( get_transient( $conv_lock ) ) {
            return __( 'Error: Conversation is busy. Please retry.', 'chatbot-chatgpt' );
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
        // back_trace('NOTICE', 'Step 1: Resolve or create the conversation (cnv_...)');
        // back_trace('NOTICE', 'Message: ' . $message);
        // back_trace('NOTICE', 'User ID: ' . $user_id);
        // back_trace('NOTICE', 'Page ID: ' . $page_id);
        // back_trace('NOTICE', 'Session ID: ' . $session_id);
        // back_trace('NOTICE', 'Assistant ID: ' . $assistant_id);
        // back_trace('NOTICE', 'Client Message ID: ' . $client_message_id);

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
                $msg = is_array( $conv['error'] ) ? ( $conv['error']['message'] ?? __( 'Conversation create failed.', 'chatbot-chatgpt' ) ) : __( 'Conversation create failed.', 'chatbot-chatgpt' );
                return sprintf(
                    /* translators: %s: API error message */
                    __( 'Error: %s', 'chatbot-chatgpt' ),
                    $msg
                );
            }

            $thread_id = $conv['id'] ?? '';
            if ( empty( $thread_id ) ) {
                return __( 'Error: Conversation created but missing ID.', 'chatbot-chatgpt' );
            }

            // Persist the new conversation id using existing helper (kept for compatibility).
            if ( function_exists( 'set_chatbot_chatgpt_threads' ) ) {
                set_chatbot_chatgpt_threads( $thread_id, $prompt_id, $user_id, $page_id );
            }

        }

        // -----------------------------------------------------------------
        // Step 2: Create the model response (Responses API)
        // -----------------------------------------------------------------

        // Retrieve session file IDs (same transient store as Assistants API) so we can
        // include uploaded files in the request. chatbot_chatgpt_retrieve_file_id_responses()
        // is not used here because it returns the first file from the account (/v1/files);
        // for chat uploads we need per-session files from transients.
        $file_ids_for_input = array();
        if ( function_exists( 'chatbot_chatgpt_retrieve_file_id' ) ) {
            $file_ids_for_input = chatbot_chatgpt_retrieve_file_id( $user_id, $page_id );
        }

        // Build input: text plus optional file/image attachments (Responses API input_items format).
        // Only use input_file for PDFs; for non-PDF (txt, md, json, etc.) fetch content and add as input_text.
        $input_content = array(
            array(
                'type' => 'input_text',
                'text' => $message,
            ),
        );
        if ( ! empty( $file_ids_for_input ) && is_array( $file_ids_for_input ) ) {
            foreach ( $file_ids_for_input as $idx => $fid ) {
                if ( ! is_int( $idx ) || empty( $fid ) || ! is_string( $fid ) ) {
                    continue;
                }
                $file_type = isset( $file_ids_for_input[ $fid ] ) ? $file_ids_for_input[ $fid ] : 'assistants';
                if ( $file_type === 'vision' ) {
                    $input_content[] = array(
                        'type'     => 'input_image',
                        'file_id'  => $fid,
                        'detail'   => 'auto',
                    );
                    continue;
                }
                // Assistants file: PDF => input_file; non-PDF => use cached text transient (OpenAI does not allow GET /files/{id}/content for purpose=assistants).
                $meta = chatbot_openai_get_file_metadata( $api_key, $fid );
                $filename = isset( $meta['filename'] ) ? $meta['filename'] : '';
                $is_pdf = ( $filename !== '' && preg_match( '/\.pdf$/i', $filename ) );
                if ( $is_pdf ) {
                    $input_content[] = array(
                        'type'    => 'input_file',
                        'file_id' => $fid,
                    );
                    // DIAG - Diagnostics - Ver 2.4.5
                    // back_trace( 'NOTICE', 'Responses file input: using PDF path (input_file) for file_id ' . $fid );
                } else {
                    $content = function_exists( 'get_chatbot_chatgpt_transients_files' )
                        ? get_chatbot_chatgpt_transients_files( 'chatbot_chatgpt_assistant_file_text', $session_id, $idx )
                        : '';
                    if ( $content === '' ) {
                        return __( 'Error: Could not read the uploaded text content. Please re-upload as PDF or try uploading again.', 'chatbot-chatgpt' );
                    }
                    $display_name = $filename !== '' ? $filename : ( 'file_' . $idx );
                    $text_block = "BEGIN FILE: " . $display_name . "\n\n" . $content . "\n\nEND FILE: " . $display_name;
                    $input_content[] = array(
                        'type' => 'input_text',
                        'text' => $text_block,
                    );
                    // DIAG - Diagnostics - Ver 2.4.5
                    // back_trace( 'NOTICE', 'Responses file input: embedded cached text transient for file_id ' . $fid );
                }
            }
        }

        $input_payload = array(
            array(
                'type'    => 'message',
                'role'    => 'user',
                'content' => $input_content,
            ),
        );

        // DIAG - Diagnostics - Ver 2.4.5
        // back_trace('NOTICE', 'Step 2: Create the model response (Responses API)');
        // back_trace('NOTICE', 'Message: ' . $message);

        $is_prompt = function_exists( 'chatbot_chatgpt_id_starts_with' )
            ? chatbot_chatgpt_id_starts_with( $prompt_id, 'pmpt_' )
            : ( strpos( $prompt_id, 'pmpt_' ) === 0 );

        $local_row = chatbot_chatgpt_get_responses_local_row( $prompt_id );
        $additional_instructions = chatbot_chatgpt_resolve_local_additional_instructions( $prompt_id, $user_id, $page_id, $session_id, $local_row );

        // Prompt objects already carry dashboard instructions. Extra WordPress
        // instructions are additive so they do not replace the hosted prompt.
        if ( $is_prompt && $additional_instructions !== '' ) {
            array_unshift(
                $input_payload,
                array(
                    'type'    => 'message',
                    'role'    => 'developer',
                    'content' => $additional_instructions,
                )
            );
        }

        $payload = array(
            // The conversation that this response belongs to. Conversation items are
            // prepended automatically and the new items are appended after completion.
            'conversation'      => $thread_id,
            // The new user input for this turn (text + optional file/image attachments).
            'input'             => $input_payload,
            // Helpful for abuse detection without sending PII.
            'safety_identifier' => wp_hash( (string) $user_id ),
            // Let the API auto-truncate old items if context would overflow.
            'truncation'        => 'auto',
        );

        if ( $is_prompt ) {
            // Temporary bridge: hosted prompt objects shut down 2026-11-30.
            $payload['prompt'] = array(
                'id' => $prompt_id,
            );
        } else {
            $model = chatbot_chatgpt_resolve_responses_model( $user_id, $page_id, $session_id );
            if ( $model === '' ) {
                return __( 'Error: Missing model. Set a ChatGPT model in Settings before using an Assistant ID (asst_...).', 'chatbot-chatgpt' );
            }
            $payload['model'] = $model;
            $instructions = chatbot_chatgpt_build_responses_instructions( $local_row, $additional_instructions );
            if ( $instructions !== '' ) {
                $payload['instructions'] = $instructions;
            }
        }

        $vector_store_ids = chatbot_chatgpt_resolve_vector_store_ids( $local_row );
        if ( ! empty( $vector_store_ids ) ) {
            $payload['tools'] = array(
                array(
                    'type'             => 'file_search',
                    'vector_store_ids' => $vector_store_ids,
                    'max_num_results'  => 8,
                ),
            );
        }

        // DIAG - Diagnostics - Ver 2.4.5
        // back_trace( 'NOTICE', 'Step 2: Payload: ' . print_r( $payload, true ) );
        // back_trace( 'NOTICE', 'Input Payload: ' . print_r( $input_payload, true ) );

        $resp = kchat_openai_http_post_json( kchat_openai_responses_url(), $api_key, $payload, $timeout, $message_uuid );

        // DIAG - Log API response so we can see success vs error and payload shape.
        // DIAG - Diagnostics - Ver 2.4.5
        // if ( isset( $resp['error'] ) ) {
        //     back_trace( 'NOTICE', 'Responses API returned error: ' . print_r( $resp['error'], true ) );
        //     back_trace( 'NOTICE', 'Full response: ' . print_r( $resp, true ) );
        // } else {
        //     back_trace( 'NOTICE', 'Responses API success. Has output: ' . ( isset( $resp['output'] ) ? 'yes' : 'no' ) );
        // }

        if ( isset( $resp['error'] ) ) {
            $msg = is_array( $resp['error'] ) ? ( $resp['error']['message'] ?? __( 'OpenAI error.', 'chatbot-chatgpt' ) ) : __( 'OpenAI error.', 'chatbot-chatgpt' );
            return sprintf(
                /* translators: %s: API error message */
                __( 'Error: %s', 'chatbot-chatgpt' ),
                $msg
            );
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
        // back_trace('NOTICE', 'Step 3: Extract assistant output');
        // back_trace('NOTICE', 'Response: ' . print_r($resp, true));

        // Extract text first. Responses API runs tools (e.g. file_search) server-side
        // and returns the final assistant message, so we use it when present.
        $text = kchat_openai_extract_output_text( $resp );

        if ( $text !== '' ) {
            return $text;
        }

        // No text: if the response included tool calls we can't fulfill client-side, explain.
        if ( check_assistant_tool_usage_responses( $resp ) ) {
            return __( 'Error: This Prompt triggered tool calls. Update the Prompt to disable tools for this chat endpoint, or implement a tool-call orchestration loop for Responses.', 'chatbot-chatgpt' );
        }

        return __( 'Error: Empty response from OpenAI.', 'chatbot-chatgpt' );

    } finally {

        // DIAG - Diagnostics - Ver 2.4.5
        // back_trace('NOTICE', 'Restore execution time');

        chatbot_chatgpt_restore_execution_time_responses();

        if ( function_exists( 'delete_transient' ) ) {
            delete_transient( $conv_lock );
        }
    }
}

// File utilities - Retrieve the first file id from the account (GET /v1/files) - Ver 2.4.5
// Use this when you need a single "first uploaded" file by API key. For chat uploads,
// the pmpt_ path uses session-based file IDs via chatbot_chatgpt_retrieve_file_id()
// (same transients as the Assistants API) and includes them in the request payload.
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

// File utilities - Delete a file - Ver 2.4.5
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
