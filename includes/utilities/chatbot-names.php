<?php
/**
 * Kognetiks Chatbot - Retrieves the Name of the Assistant or Prompt
 *
 * This file contains the code to retrieve the display name of an Assistant
 * (asst_) or Prompt (pmpt_) from OpenAI, Azure, or the local assistants table.
 *
 * OpenAI Assistants API (GET /v1/assistants) was sunset 2026-08-26.
 * Prompt objects (GET /v1/prompts) remain available until 2026-11-30.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

/**
 * Return true when $id starts with $prefix (PHP 7.4-compatible).
 *
 * @param string $id
 * @param string $prefix
 * @return bool
 */
function chatbot_chatgpt_id_starts_with( $id, $prefix ) {

    $id_str = (string) $id;
    if ( function_exists( 'str_starts_with' ) ) {
        return str_starts_with( $id_str, $prefix );
    }
    return strpos( $id_str, $prefix ) === 0;

}

/**
 * Look up a display name from the local assistants table by ID.
 *
 * @param string $assistant_id_lookup
 * @return string|false
 */
function chatbot_chatgpt_get_local_assistant_name( $assistant_id_lookup ) {

    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_assistants';
    $name       = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT common_name FROM $table_name WHERE assistant_id = %s LIMIT 1",
            $assistant_id_lookup
        )
    );

    if ( empty( $name ) ) {
        return false;
    }

    return $name;

}

/**
 * Extract a human-readable name from a remote API payload.
 *
 * @param array $data
 * @return string|false
 */
function chatbot_chatgpt_extract_remote_object_name( $data ) {

    if ( ! is_array( $data ) ) {
        return false;
    }

    foreach ( array( 'name', 'title' ) as $key ) {
        if ( ! empty( $data[ $key ] ) && is_string( $data[ $key ] ) ) {
            return $data[ $key ];
        }
    }

    return false;

}

/**
 * GET a JSON object from OpenAI or Azure and return its name, or false.
 *
 * @param string $url
 * @param array  $args
 * @return string|false
 */
function chatbot_chatgpt_fetch_remote_object_name( $url, $args ) {

    $response = wp_remote_get( $url, $args );

    if ( is_wp_error( $response ) ) {
        prod_trace( 'ERROR', 'Error fetching Assistant/Prompt name: ' . $response->get_error_message() );
        return false;
    }

    $status_code = wp_remote_retrieve_response_code( $response );
    if ( $status_code < 200 || $status_code >= 300 ) {
        prod_trace( 'ERROR', 'Assistant/Prompt name lookup returned HTTP ' . $status_code . ' for ' . $url );
        return false;
    }

    $response_body = wp_remote_retrieve_body( $response );
    $data          = json_decode( $response_body, true );

    if ( json_last_error() !== JSON_ERROR_NONE ) {
        prod_trace( 'ERROR', 'Invalid JSON response while fetching Assistant/Prompt name.' );
        return false;
    }

    if ( isset( $data['error'] ) ) {
        $error_message = isset( $data['error']['message'] ) ? $data['error']['message'] : 'Unknown error';
        prod_trace( 'ERROR', 'API Error fetching Assistant/Prompt name: ' . $error_message );
        return false;
    }

    return chatbot_chatgpt_extract_remote_object_name( $data );

}

// Function to get the Assistant's or Prompt's name
function get_chatbot_chatgpt_assistant_name($assistant_id_lookup) {

    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;
    global $kchat_settings;
    global $additional_instructions;
    global $model;
    global $voice;

    global $chatbot_chatgpt_display_style;
    global $chatbot_chatgpt_assistant_alias;

    $api_key = '';

    $chatbot_ai_platform_choice = esc_attr(get_option('chatbot_ai_platform_choice'), 'OpenAI');

    $id_str = sanitize_text_field( (string) $assistant_id_lookup );
    if ( $id_str === '' ) {
        return false;
    }

    $is_prompt    = chatbot_chatgpt_id_starts_with( $id_str, 'pmpt_' );
    $is_assistant = chatbot_chatgpt_id_starts_with( $id_str, 'asst_' );

    $url  = '';
    $args = array();

    if ( $chatbot_ai_platform_choice == 'OpenAI' ) {

        // Retrieve the API key
        $api_key = esc_attr(get_option('chatbot_chatgpt_api_key'));
        // Decrypt the API key - Ver 2.2.6
        $api_key = chatbot_chatgpt_decrypt_api_key($api_key);

        // Ensure API key is set
        if (empty($api_key)) {
            return chatbot_chatgpt_get_local_assistant_name( $id_str );
        }

        if ( $is_prompt ) {

            // Prompt objects: GET /v1/prompts/{pmpt_...} (scheduled shutdown 2026-11-30).
            $url  = 'https://api.openai.com/v1/prompts/' . rawurlencode( $id_str );
            $args = array(
                'method'  => 'GET',
                'headers' => array(
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $api_key,
                ),
                'timeout' => 15, // Avoid long waits
            );

        } elseif ( $is_assistant ) {

            // Assistants API was sunset 2026-08-26. Do not call GET /v1/assistants/{asst_...}.
            // Display name comes from the local assistants table (Common Name).
            return chatbot_chatgpt_get_local_assistant_name( $id_str );

        } else {

            return chatbot_chatgpt_get_local_assistant_name( $id_str );

        }

    } elseif ( $chatbot_ai_platform_choice == 'Azure OpenAI' ) {

        // Retrieve the API key
        $api_key = esc_attr(get_option('chatbot_azure_api_key'));
        // Decrypt the API key - Ver 2.2.6
        $api_key = chatbot_chatgpt_decrypt_api_key($api_key);

        if ( empty( $api_key ) ) {
            return chatbot_chatgpt_get_local_assistant_name( $id_str );
        }

        $chatbot_azure_resource_name = esc_attr(get_option('chatbot_azure_resource_name', 'YOUR_RESOURCE_NAME'));
        $chatbot_azure_api_version = esc_attr(get_option('chatbot_azure_api_version', '2024-08-01-preview'));

        $azure_headers = array(
            'Content-Type' => 'application/json',
            'api-key'      => trim($api_key),
        );

        if ( $is_prompt ) {

            // Azure v1 Prompts retrieve (best effort; falls back to local name).
            $url  = 'https://' . $chatbot_azure_resource_name . '.openai.azure.com/openai/v1/prompts/' . rawurlencode( $id_str );
            $args = array(
                'method'  => 'GET',
                'headers' => $azure_headers,
                'timeout' => 15, // Avoid long waits
            );

        } elseif ( $is_assistant ) {

            // https://YOUR_RESOURCE_NAME.openai.azure.com/openai/assistants/{assistant_id}?api-version=2024-08-01-preview
            $url  = 'https://' . $chatbot_azure_resource_name . '.openai.azure.com/openai/assistants/' . rawurlencode( $id_str ) . '?api-version=' . $chatbot_azure_api_version;
            $args = array(
                'method'  => 'GET',
                'headers' => $azure_headers,
                'timeout' => 15, // Avoid long waits
            );

        } else {

            return chatbot_chatgpt_get_local_assistant_name( $id_str );

        }

    } elseif ( $chatbot_ai_platform_choice == 'Mistral' ) {

        return chatbot_chatgpt_get_local_assistant_name( $id_str );

    } else {

        return chatbot_chatgpt_get_local_assistant_name( $id_str );

    }

    $remote_name = chatbot_chatgpt_fetch_remote_object_name( $url, $args );
    if ( ! empty( $remote_name ) ) {
        return $remote_name;
    }

    return chatbot_chatgpt_get_local_assistant_name( $id_str );

}
