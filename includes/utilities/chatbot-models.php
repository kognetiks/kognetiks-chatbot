<?php
/**
 * Kognetiks Chatbot - Chatbot Models
 *
 * This file contains the code to retrieve the list of available models
 * from OpenAI API and display them in the settings page.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Chat model OpenAI names as the substitute for gpt-3.5-turbo (retired 23 October 2026).
function chatbot_chatgpt_resolve_openai_chat_model( $model = null ) {

    if ( null === $model ) {
        $stored = get_option( 'chatbot_chatgpt_model_choice', false );
        $model = ( false === $stored ) ? '' : (string) $stored;
    } else {
        $model = (string) $model;
    }

    $model = trim( $model );
    $key = strtolower( $model );

    if ( '' === $key ) {
        return 'gpt-5.6-terra';
    }

    // gpt-3.5-turbo and its snapshots.
    if ( 'gpt-3.5-turbo' === $key || str_starts_with( $key, 'gpt-3.5-turbo-' ) ) {
        return 'gpt-5.6-terra';
    }

    // gpt-4 ids retired the same day. Leave gpt-4o and gpt-4.1, which still answer.
    $retired_gpt4 = array(
        'gpt-4',
        'gpt-4-0613',
        'gpt-4-0613-completions',
        'gpt-4-completions',
        'gpt-4-1106-preview',
    );
    if ( in_array( $key, $retired_gpt4, true ) || str_starts_with( $key, 'gpt-4-turbo' ) ) {
        return 'gpt-5.6-sol';
    }

    return $model;

}

// Image model OpenAI names as the substitute for gpt-image-1 and DALL·E (retired).
function chatbot_chatgpt_resolve_openai_image_model( $model = null ) {

    if ( null === $model ) {
        $stored = get_option( 'chatbot_chatgpt_image_model_option', false );
        $model = ( false === $stored ) ? '' : (string) $stored;
    } else {
        $model = (string) $model;
    }

    $model = trim( $model );
    $key = strtolower( $model );

    // Exact gpt-image-1 only. gpt-image-1-mini and gpt-image-1.5 still answer until 1 December 2026.
    if ( '' === $key || str_starts_with( $key, 'dall' ) || 'gpt-image-1' === $key ) {
        return 'gpt-image-2.5-flare';
    }

    return $model;

}

// Hide models OpenAI has already retired, including ones List models still returns.
function chatbot_chatgpt_openai_model_is_unavailable( $model ) {

    if ( ! is_array( $model ) || empty( $model['id'] ) || ! is_string( $model['id'] ) ) {
        return true;
    }

    if ( ! empty( $model['shutdown_date'] ) && is_string( $model['shutdown_date'] ) ) {
        $shutdown = strtotime( $model['shutdown_date'] . ' UTC' );
        if ( false !== $shutdown && $shutdown <= time() ) {
            return true;
        }
    }

    $id = $model['id'];
    if ( chatbot_chatgpt_is_openai_image_model( $id ) ) {
        return chatbot_chatgpt_resolve_openai_image_model( $id ) !== $id;
    }

    $replaced = chatbot_chatgpt_resolve_openai_chat_model( $id );
    return strtolower( $replaced ) !== strtolower( $id );

}

// Function to get the Model names from OpenAI API
function chatbot_openai_get_models() {

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

    // Retrieve the API key
    $api_key = esc_attr(get_option('chatbot_chatgpt_api_key'));
    // Decrypt the API key - Ver 2.2.6
    $api_key = chatbot_chatgpt_decrypt_api_key($api_key);

    // Default model list
    $default_model_list = '';
    $default_model_list = array(
        array(
            'id' => 'gpt-image-2.5-flare',
            'object' => 'model',
            'created' => 1743465600,
            'owned_by' => 'system'
        ),
        array(
            'id' => 'gpt-image-2.5-sunburst',
            'object' => 'model',
            'created' => 1743465600,
            'owned_by' => 'system'
        ),
        array(
            'id' => 'gpt-5.6-terra',
            'object' => 'model',
            'created' => 1677610602,
            'owned_by' => 'system'
        ),
        array(
            'id' => 'gpt-5.6-sol',
            'object' => 'model',
            'created' => 1677610602,
            'owned_by' => 'system'
        ),
        array(
            'id' => 'tts-1-hd',
            'object' => 'model',
            'created' => 1699053241,
            'owned_by' => 'system'
        ),
        array(
            'id' => 'whisper-1',
            'object' => 'model',
            'created' => 1677532384,
            'owned_by' => 'openai-internal'
        )
    );

    // See if the option exists, if not then create it and set the default.
    // get_option() returns false when the option is missing. esc_attr() turns that into a string, so a string-to-false comparison never saved the default.
    $stored_chat_model = get_option( 'chatbot_chatgpt_model_choice' );
    if ( false === $stored_chat_model || '' === $stored_chat_model ) {
        update_option( 'chatbot_chatgpt_model_choice', chatbot_chatgpt_resolve_openai_chat_model( '' ) );
    }
    $stored_image_model = get_option( 'chatbot_chatgpt_image_model_option' );
    if ( false === $stored_image_model || '' === $stored_image_model ) {
        update_option( 'chatbot_chatgpt_image_model_option', chatbot_chatgpt_resolve_openai_image_model( '' ) );
    }
    if (esc_attr(get_option('chatbot_chatgpt_voice_model_option')) === false) {
        update_option('chatbot_chatgpt_voice_model_option', 'tts-1-hd');
    }
    if (esc_attr(get_option('chatbot_chatgpt_whisper_model_option')) === false) {
        update_option('chatbot_chatgpt_whisper_model_option', 'whisper-1');
    }

    // Check if the API key is empty
    if (empty($api_key)) {
        return $default_model_list;
    }

    $openai_models_url = esc_attr(get_option('chatbot_chatgpt_base_url'));
    $openai_models_url = rtrim($openai_models_url, '/') . '/models';

    // Set HTTP request arguments
    $args = array(
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        ),
        'timeout' => 15, // Set a timeout to avoid long waits
    );

    // Make the request using WP HTTP API
    $response = wp_remote_get($openai_models_url, $args);

    // Check for errors
    if (is_wp_error($response)) {
        prod_trace( 'ERROR' . 'Error fetching OpenAI models: ' . $response->get_error_message());
        return $default_model_list;
    }

    // Decode the JSON response
    $data = json_decode(wp_remote_retrieve_body($response), true);

    // Check for API errors
    if (isset($data['error'])) {
        // return "Error: " . $data['error']['message'];
        // On 1st install needs an API key
        // So return a short list of the base models until an API key is entered
        return $default_model_list;
    }

    // Extract the models from the response
    if (isset($data['data']) && !is_null($data['data'])) {
        $models = $data['data'];
    } else {
        // Handle the case where 'data' is not set or is null
        $models = []; // Empty array
        prod_trace( 'WARNING', 'Data key is not set or is null in the \$data array.');
    }

    // Ensure $models is an array
    if (!is_array($models)) {
        return $default_model_list;
    } else {
        // Sort the models by name
        usort($models, function($a, $b) {
            return $a['id'] <=> $b['id'];
        });
        $models = array_values( array_filter( $models, function( $model ) {
            return ! chatbot_chatgpt_openai_model_is_unavailable( $model );
        } ) );
        if ( empty( $models ) ) {
            return $default_model_list;
        }
    }

    // Return the list of models
    return $models;

}

// Whether a model should be sent to the Images API instead of Chat Completions.
function chatbot_chatgpt_is_openai_image_model( $model ) {

    $model = strtolower( (string) $model );

    return ( str_starts_with( $model, 'gpt-image' ) || str_starts_with( $model, 'dall' ) );

}

// Image models for the settings dropdown. OpenAI removed DALL·E from the API in May 2026.
function chatbot_chatgpt_get_openai_image_models() {

    $fallback_models = array(
        array( 'id' => 'gpt-image-2.5-flare' ),
        array( 'id' => 'gpt-image-2.5-sunburst' ),
        array( 'id' => 'gpt-image-2' ),
    );

    $models = chatbot_openai_get_models();

    if ( ! is_array( $models ) ) {
        return $fallback_models;
    }

    $image_models = array_values(
        array_filter(
            $models,
            function( $model ) {
                return ! empty( $model['id'] ) && chatbot_chatgpt_is_openai_image_model( $model['id'] );
            }
        )
    );

    return ! empty( $image_models ) ? $image_models : $fallback_models;

}

// Function to get the Model names from OpenAI API
function chatbot_azure_get_models() {

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

    // Retrieve the API key
    $api_key = esc_attr(get_option('chatbot_azure_api_key'));
    // Decrypt the API key - Ver 2.2.6
    $api_key = chatbot_chatgpt_decrypt_api_key($api_key);

    // Default model list
    $default_model_list = '';
    $default_model_list = array(
        array(
            'id' => 'dall-e-3',
            'object' => 'model',
            'created' => 1691712000,
            'owned_by' => 'system'
        ),
        array(
            'id' => 'gpt-3.5-turbo',
            'object' => 'model',
            'created' => 1707955200,
            'owned_by' => 'system'
        ),
        array(
            'id' => 'gpt-4o',
            'object' => 'model',
            'created' => 1715558400,
            'owned_by' => 'system'
        ),
        array(
            'id' => 'gpt-4o-mini',
            'object' => 'model',
            'created' => 1721347200,
            'owned_by' => 'system'
        ),
        array(
            'id' => 'gpt-4o-audio-preview',
            'object' => 'model',
            'created' => 1731369600,
            'owned_by' => 'system'
        ),
        array(
            'id' => 'text-embedding-ada-002',
            'object' => 'model',
            'created' => 1680480000,
            'owned_by' => 'system'
        ),
        array(
            'id' => 'whisper-1',
            'object' => 'model',
            'created' => 1677532384,
            'owned_by' => 'openai-internal'
        )
    );   

    // See if the option exists, if not then create it and set the default
    if (esc_attr(get_option('chatbot_azure_model_choice')) === false) {
        update_option('chatbot_azure_model_choice', 'gpt-3.5-turbo');
    }
    if (esc_attr(get_option('chatbot_azure_image_model_option')) === false) {
        update_option('chatbot_azure_image_model_option', 'dall-e-3');
    }
    if (esc_attr(get_option('chatbot_azure_voice_model_option')) === false) {
        update_option('chatbot_azure_voice_model_option', 'tts-1-hd');
    }
    if (esc_attr(get_option('chatbot_azure_whisper_model_option')) === false) {
        update_option('chatbot_azure_whisper_model_option', 'whisper-1');
    }

    // Check if the API key is empty
    if (empty($api_key)) {
        return $default_model_list;
    }

    // Assemble the URL from resource name and deployment name
    $chatbot_azure_resource_name = esc_attr(get_option('chatbot_azure_resource_name', 'YOUR_RESOURCE_NAME'));
    $chatbot_azure_deployment_name = esc_attr(get_option('chatbot_azure_deployment_name', 'DEPLOYMENT_NAME'));
    $chatbot_azure_api_version = esc_attr(get_option('chatbot_azure_api_version', '2024-08-01-preview'));
    $azure_models_url = 'https://' . $chatbot_azure_resource_name . '.openai.azure.com/openai/models?api-version=' . $chatbot_azure_api_version;

    // Set HTTP request arguments
    $args = array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'api-key'      => trim($api_key),
            'Accept'       => 'application/json',
        ),
        'timeout' => 15, // Set a timeout to avoid long waits
    );

    // Make the request using WP HTTP API
    $response = wp_remote_get($azure_models_url, $args);

    // Check for errors
    if (is_wp_error($response)) {
        prod_trace( 'ERROR' . 'Error fetching Azure OpenAI models: ' . $response->get_error_message());
        return $default_model_list;
    }

    // Decode the JSON response
    $data = json_decode(wp_remote_retrieve_body($response), true);

    // Check for API errors
    if (isset($data['error'])) {
        // return "Error: " . $data['error']['message'];
        // On 1st install needs an API key
        // So return a short list of the base models until an API key is entered
        return $default_model_list;
    }

    // Extract the models from the response
    if (isset($data['data']) && !is_null($data['data'])) {
        $models = $data['data'];
    } else {
        // Handle the case where 'data' is not set or is null
        $models = []; // Empty array
        prod_trace( 'WARNING', 'Data key is not set or is null in the \$data array.');
    }

    // Ensure $models is an array
    if (!is_array($models)) {
        return $default_model_list;
    } else {
        // Sort the models by name
        usort($models, function($a, $b) {
            return $a['id'] <=> $b['id'];
        });
    }

    // Return the list of models
    return $models;

}

// Function to get the Model names from NVIDIA API
function chatbot_nvidia_get_models() {

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

    // Retrieve the API key
    $api_key = esc_attr(get_option('chatbot_nvidia_api_key'));
    // Decrypt the API key - Ver 2.2.6
    $api_key = chatbot_chatgpt_decrypt_api_key($api_key);

    // Default model list
    $default_model_list = '';
    $default_model_list = array(
        array(
            'id' => 'nvidia/llama-3.1-nemotron-51b-instruct',
            'object' => 'model',
            'created' => 735790403,
            'owned_by' => 'nvidia'
        ),
    );

    // See if the option exists, if not then create it and set the default
    if (esc_attr(get_option('chatbot_nvidia_model_choice')) === false) {
        update_option('chatbot_nvidia_model_choice', 'nvidia/llama-3_1-nemotron-70b-instruct');
    }

    // Check if the API key is empty
    if (empty($api_key)) {
        return $default_model_list;
    }

    $nvidia_models_url = esc_attr(get_option('chatbot_nvidia_base_url'));
    $nvidia_models_url = rtrim($nvidia_models_url, '/') . '/models';

    // Set HTTP request arguments
    $args = array(
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        ),
        'timeout' => 15, // Avoid long waits
    );

    // Make the request using WP HTTP API
    $response = wp_remote_get($nvidia_models_url, $args);

    // Check for errors
    if (is_wp_error($response)) {
        prod_trace( 'ERROR' , 'Error fetching NVIDIA models: ' . $response->get_error_message());
        return $default_model_list;
    }

    // Retrieve response body
    $response_body = wp_remote_retrieve_body($response);

    // Decode JSON response
    $data = json_decode($response_body, true);

    // Validate JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        prod_trace( 'ERROR' , 'Invalid JSON response from NVIDIA API.');
        return $default_model_list;
    }

    // Extract the models from the response
    if (isset($data['data']) && !is_null($data['data'])) {
        $models = $data['data'];
    } else {
        // Handle the case where 'data' is not set or is null
        $models = []; // Empty array
        prod_trace( 'WARNING', 'Data key is not set or is null in the \$data array.');
    }

    // Ensure $models is an array
    if (!is_array($models)) {
        return $default_model_list;
    } else {
        // Sort the models by name
        usort($models, function($a, $b) {
            return $a['id'] <=> $b['id'];
        });
    }

    // Return the list of models
    return $models;

}

// Fallback when no Anthropic model has been saved. Sonnet is the balanced default.
function chatbot_anthropic_default_model() {

    return 'claude-sonnet-5-5';

}

// Shown until an API key can load the live list from GET /v1/models.
function chatbot_anthropic_fallback_models() {

    $ids = array(
        'claude-opus-5-5',
        'claude-sonnet-5-5',
        'claude-haiku-5-5',
        'claude-fable-5-1',
    );
    $models = array();

    foreach ( $ids as $id ) {
        $models[] = array(
            'id'       => $id,
            'object'   => 'model',
            'created'  => null,
            'owned_by' => 'anthropic',
        );
    }

    return $models;

}

// Model ids returned by the Anthropic Models API.
function chatbot_anthropic_model_id_is_valid( $model_id ) {

    return is_string( $model_id ) && preg_match( '/^[A-Za-z0-9._:-]+$/', $model_id );

}

// True after chatbot_anthropic_get_models() loaded ids from the Models API.
function chatbot_anthropic_models_are_live( $set = null ) {

    static $live = false;
    if ( null !== $set ) {
        $live = (bool) $set;
    }
    return $live;

}

// Standard base input price, USD per million tokens. Longer names match first.
function chatbot_anthropic_model_base_input_price( $model_id ) {

    if ( ! chatbot_anthropic_model_id_is_valid( $model_id ) ) {
        return null;
    }

    $id = strtolower( $model_id );
    $prices = array(
        'haiku-3-5'  => 0.80,
        'haiku-4-5'  => 1.00,
        'haiku-5-5'  => 0.10,
        'haiku-3'    => 0.25,
        'sonnet-4-5' => 3.00,
        'sonnet-4-6' => 3.00,
        'sonnet-5-5' => 2.00,
        'sonnet-5'   => 2.00,
        'opus-4-5'   => 5.00,
        'opus-4-6'   => 5.00,
        'opus-4-7'   => 5.00,
        'opus-4-8'   => 5.00,
        'opus-5-5'   => 4.00,
        'opus-5'     => 5.00,
        'fable-5-1'  => 10.00,
        'fable-5'    => 10.00,
    );

    uksort(
        $prices,
        function( $a, $b ) {
            return strlen( $b ) <=> strlen( $a );
        }
    );

    foreach ( $prices as $needle => $price ) {
        if ( false !== strpos( $id, $needle ) ) {
            return $price;
        }
    }

    return null;

}

// Cheapest model in a live list. Haiku is the cheap tier; the lowest base input price wins.
function chatbot_anthropic_cheapest_model_id( $models ) {

    if ( ! is_array( $models ) ) {
        return '';
    }

    $chosen = '';
    $best   = null;

    foreach ( array_values( $models ) as $index => $model ) {
        if ( ! is_array( $model ) || empty( $model['id'] ) || ! chatbot_anthropic_model_id_is_valid( $model['id'] ) ) {
            continue;
        }

        $id       = $model['id'];
        $is_haiku = false !== stripos( $id, 'haiku' );
        $price    = chatbot_anthropic_model_base_input_price( $id );

        if ( $is_haiku && null !== $price ) {
            $rank = array( 0, $price );
        } elseif ( $is_haiku ) {
            $rank = array( 1, -1 * $index );
        } elseif ( null !== $price ) {
            $rank = array( 2, $price );
        } else {
            $rank = array( 3, -1 * $index );
        }

        if ( null === $best || $rank < $best ) {
            $best   = $rank;
            $chosen = $id;
        }
    }

    return $chosen;

}

// Use the requested id when the live list includes it. Otherwise use the cheapest model.
function chatbot_anthropic_resolve_model( $model ) {

    if ( ! is_string( $model ) || '' === $model || ! chatbot_anthropic_model_id_is_valid( $model ) ) {
        $model = chatbot_anthropic_default_model();
    }

    $models = chatbot_anthropic_get_models();
    if ( ! chatbot_anthropic_models_are_live() || ! is_array( $models ) ) {
        return $model;
    }

    foreach ( $models as $entry ) {
        if ( is_array( $entry ) && isset( $entry['id'] ) && $entry['id'] === $model ) {
            return $model;
        }
    }

    $replacement = chatbot_anthropic_cheapest_model_id( $models );
    if ( chatbot_anthropic_model_id_is_valid( $replacement ) ) {
        return $replacement;
    }

    return $model;

}

// Anthropic rejects consecutive messages with the same role.
function chatbot_anthropic_normalize_messages( $messages ) {

    if ( ! is_array( $messages ) ) {
        return array();
    }

    $normalized = array();
    foreach ( $messages as $message ) {
        if ( ! is_array( $message ) || empty( $message['role'] ) || ! isset( $message['content'] ) || ! is_string( $message['content'] ) ) {
            continue;
        }
        $role = $message['role'];
        if ( 'user' !== $role && 'assistant' !== $role ) {
            continue;
        }
        $content = trim( $message['content'] );
        if ( '' === $content ) {
            continue;
        }
        $last = count( $normalized ) - 1;
        if ( $last >= 0 && $normalized[ $last ]['role'] === $role ) {
            $normalized[ $last ]['content'] .= "\n\n" . $content;
            continue;
        }
        $normalized[] = array(
            'role'    => $role,
            'content' => $content,
        );
    }

    while ( ! empty( $normalized ) && 'user' !== $normalized[0]['role'] ) {
        array_shift( $normalized );
    }

    return $normalized;

}

// Function to get the Model names from Anthropic API
// https://platform.claude.com/docs/en/api/models/list
function chatbot_anthropic_get_models( $refresh = false ) {

    chatbot_anthropic_models_are_live( false );
    $fallback = chatbot_anthropic_fallback_models();

    $api_key = get_option( 'chatbot_anthropic_api_key', '' );
    if ( ! is_string( $api_key ) || '' === $api_key ) {
        return $fallback;
    }

    $api_key = chatbot_chatgpt_decrypt_api_key( esc_attr( $api_key ) );
    if ( ! is_string( $api_key ) || '' === $api_key ) {
        return $fallback;
    }

    $base_url = get_option( 'chatbot_anthropic_base_url', 'https://api.anthropic.com/v1' );
    if ( ! is_string( $base_url ) || '' === $base_url ) {
        $base_url = 'https://api.anthropic.com/v1';
    }
    $base_url = esc_url_raw( untrailingslashit( $base_url ) );
    if ( '' === $base_url || ! wp_http_validate_url( $base_url ) ) {
        $base_url = 'https://api.anthropic.com/v1';
    }

    $cache_key = 'chatbot_anth_models_' . substr( hash( 'sha256', $api_key ), 0, 16 );
    if ( ! $refresh ) {
        $cached = get_transient( $cache_key );
        if ( is_array( $cached ) && ! empty( $cached ) ) {
            chatbot_anthropic_models_are_live( true );
            return $cached;
        }
    }

    $models   = array();
    $seen     = array();
    $after_id = '';
    $pages    = 0;

    do {
        $url = $base_url . '/models?limit=1000';
        if ( '' !== $after_id ) {
            $url .= '&after_id=' . rawurlencode( $after_id );
        }

        $response = wp_remote_get(
            $url,
            array(
                'headers' => array(
                    'x-api-key'         => $api_key,
                    'anthropic-version' => '2023-06-01',
                    'content-type'      => 'application/json',
                ),
                'timeout' => 15,
            )
        );

        if ( is_wp_error( $response ) ) {
            prod_trace( 'ERROR', 'Error fetching Anthropic models: ' . $response->get_error_message() );
            if ( empty( $models ) ) {
                return $fallback;
            }
            set_transient( $cache_key, $models, 5 * MINUTE_IN_SECONDS );
            chatbot_anthropic_models_are_live( true );
            return $models;
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code < 200 || $code >= 300 || ! is_array( $data ) || isset( $data['error'] ) || empty( $data['data'] ) || ! is_array( $data['data'] ) ) {
            $message = ( is_array( $data ) && isset( $data['error']['message'] ) ) ? $data['error']['message'] : 'HTTP ' . $code;
            prod_trace( 'ERROR', 'Anthropic models request failed: ' . $message );
            if ( empty( $models ) ) {
                return $fallback;
            }
            set_transient( $cache_key, $models, 5 * MINUTE_IN_SECONDS );
            chatbot_anthropic_models_are_live( true );
            return $models;
        }

        foreach ( $data['data'] as $model ) {
            if ( ! is_array( $model ) || empty( $model['id'] ) || ! chatbot_anthropic_model_id_is_valid( $model['id'] ) ) {
                continue;
            }
            if ( isset( $seen[ $model['id'] ] ) ) {
                continue;
            }
            $seen[ $model['id'] ] = true;

            $created = null;
            if ( ! empty( $model['created_at'] ) && is_string( $model['created_at'] ) ) {
                $timestamp = strtotime( $model['created_at'] );
                if ( false !== $timestamp ) {
                    $created = $timestamp;
                }
            }

            $models[] = array(
                'id'       => $model['id'],
                'object'   => 'model',
                'created'  => $created,
                'owned_by' => 'anthropic',
            );
        }

        $has_more = ! empty( $data['has_more'] );
        $next_id  = ( isset( $data['last_id'] ) && is_string( $data['last_id'] ) ) ? $data['last_id'] : '';
        if ( '' === $next_id || $next_id === $after_id || ! chatbot_anthropic_model_id_is_valid( $next_id ) ) {
            $has_more = false;
        }
        $after_id = $next_id;
        $pages++;
    } while ( $has_more && $pages < 10 );

    if ( empty( $models ) ) {
        return $fallback;
    }

    // The API already returns the most recently released model first.
    set_transient( $cache_key, $models, 5 * MINUTE_IN_SECONDS );
    chatbot_anthropic_models_are_live( true );
    return $models;

}

// Function to get the Model names from DeepSeek API
function chatbot_deepseek_get_models() {

    $api_key = '';

    // Retrieve the API key
    $api_key = esc_attr(get_option('chatbot_deepseek_api_key'));
    // Decrypt the API key - Ver 2.2.6
    $api_key = chatbot_chatgpt_decrypt_api_key($api_key);

    // https://api-docs.deepseek.com/
    // https://api-docs.deepseek.com/quick_start/pricing
    // https://api.deepseek.com/models

    // Default model list
    $default_model_list = '';
    $default_model_list = array(
        array(
            'id' => 'deepseek-chat',
            'object' => 'model',
            'created' => null,
            'owned_by' => 'deepseek'
        ),
    );

    // Check if the API key is empty
    if (empty($api_key)) {
        return $default_model_list;
    }

    $deepseek_models_url = esc_attr(get_option('chatbot_deepseek_base_url'));
    $deepseek_models_url = rtrim($deepseek_models_url, '/') . '/models';

    // Set headers
    $args = array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ),
    );

    // Perform the request
    $response = wp_remote_get($deepseek_models_url, $args);
    
    // Check for errors in the response
    if (is_wp_error($response)) {
        return $default_model_list;
    }

    // Decode the JSON response
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // Check if the response is valid and contains data
    if (isset($data['data']) && is_array($data['data'])) {
        $default_model_list = array_map(function($model) {
            return array(
                'id' => $model['id'],
                'object' => $model['object'],
                'created' => null, // Assuming 'created' is not provided in the response
                'owned_by' => $model['owned_by']
            );
        }, $data['data']);
    } else {
        // Handle the case where the response is not valid
        $default_model_list = array(
            array(
                'id' => 'deepseek-chat',
                'object' => 'model',
                'created' => null,
                'owned_by' => 'deepseek'
            ),
        );
    }

    // DeepSeek API does not have an endpoint for models
    return $default_model_list;

}

// Function to get the Model names from Mistral API
function chatbot_mistral_get_models() {


    $api_key = '';

    // Retrieve the API key
    $api_key = esc_attr(get_option('chatbot_mistral_api_key'));
    // Decrypt the API key - Ver 2.2.6
    $api_key = chatbot_chatgpt_decrypt_api_key($api_key);

    // Default model list
    $default_model_list = '';
    $default_model_list = array(
        array(
            'id' => 'mistral-small-latest',
            'object' => 'model',
            'created' => null,
            'owned_by' => 'mistral'
        ),
    );

    // Check if the API key is empty
    if (empty($api_key)) {
        return $default_model_list;
    }

    // Set the API endpoint
    $mistral_models_url = esc_attr(get_option('chatbot_mistral_base_url'));
    $mistral_models_url = rtrim($mistral_models_url, '/') . '/models';

    // Set headers
    $args = array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ),
    );

    // Perform the request
    $response = wp_remote_get($mistral_models_url, $args);

    // Check for errors in the response
    if (is_wp_error($response)) {
        return $default_model_list;
    }

    // Decode the JSON response
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // Check if the response is valid and contains data
    if (isset($data['data']) && is_array($data['data'])) {
        $default_model_list = array_map(function($model) {
            return array(
                'id' => $model['id'],
                'object' => $model['object'],
                'created' => null,
                'owned_by' => $model['owned_by']
            );
        }, $data['data']);
    } else {
        // Handle the case where the response is not valid
        $default_model_list = array(
            array(
                'id' => 'mistral-small-latest',
                'object' => 'model',
                'created' => null,
                'owned_by' => 'mistral'
            ),
        );
    }

    // Mistral API does not have an endpoint for models
    return $default_model_list;

}

// Function to get the Model names from Google API
function chatbot_google_get_models() {

    $api_key = '';

    // Retrieve the API key
    $api_key = esc_attr(get_option('chatbot_google_api_key'));
    // Decrypt the API key - Ver 2.3.9
    $api_key = chatbot_chatgpt_decrypt_api_key($api_key);

    // Default model list
    $default_model_list = '';
    $default_model_list = array(
        array(
            'id' => 'gemini-pro',
            'object' => 'model',
            'created' => null,
            'owned_by' => 'google'
        ),
        array(
            'id' => 'gemini-pro-vision',
            'object' => 'model',
            'created' => null,
            'owned_by' => 'google'
        ),
        array(
            'id' => 'gemini-1.5-pro',
            'object' => 'model',
            'created' => null,
            'owned_by' => 'google'
        ),
        array(
            'id' => 'gemini-1.5-flash',
            'object' => 'model',
            'created' => null,
            'owned_by' => 'google'
        ),
        array(
            'id' => 'gemini-2.0-flash',
            'object' => 'model',
            'created' => null,
            'owned_by' => 'google'
        ),
    );

    // See if the option exists, if not then create it and set the default
    if (esc_attr(get_option('chatbot_google_model_choice')) === false) {
        update_option('chatbot_google_model_choice', 'gemini-2.0-flash');
    }

    // Check if the API key is empty
    if (empty($api_key)) {
        return $default_model_list;
    }

    // Get the base URL from settings
    $google_base_url = esc_attr(get_option('chatbot_google_base_url', 'https://generativelanguage.googleapis.com/v1beta'));
    $google_models_url = rtrim($google_base_url, '/');
    
    // If the URL doesn't end with /models, add it
    if (substr($google_models_url, -7) !== '/models') {
        $google_models_url = rtrim($google_models_url, '/') . '/models';
    }

    // Google API uses query parameter for API key
    $google_models_url = add_query_arg('key', $api_key, $google_models_url);

    // Set HTTP request arguments
    $args = array(
        'headers' => array(
            'Content-Type'  => 'application/json'
        ),
        'timeout' => 15, // Set a timeout to avoid long waits
    );

    // Make the request using WP HTTP API
    $response = wp_remote_get($google_models_url, $args);

    // Check for errors
    if (is_wp_error($response)) {
        prod_trace( 'ERROR', 'Error fetching Google models: ' . $response->get_error_message());
        return $default_model_list;
    }

    // Decode the JSON response
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // Check for API errors
    if (isset($data['error'])) {
        // return "Error: " . $data['error']['message'];
        // On 1st install needs an API key
        // So return a short list of the base models until an API key is entered
        return $default_model_list;
    }

    // Extract the models from the response
    // Google API may return models in different formats
    if (isset($data['models']) && is_array($data['models'])) {
        $models = array_map(function($model) {
            // Google API returns model names as "models/gemini-pro" - extract just the model name
            $model_id = $model['name'] ?? $model['displayName'] ?? $model['id'] ?? '';
            // Remove "models/" prefix if present
            if (strpos($model_id, 'models/') === 0) {
                $model_id = substr($model_id, 7);
            }
            return array(
                'id' => $model_id,
                'object' => 'model',
                'created' => null,
                'owned_by' => 'google'
            );
        }, $data['models']);
    } elseif (isset($data['data']) && is_array($data['data'])) {
        $models = array_map(function($model) {
            // Google API returns model names as "models/gemini-pro" - extract just the model name
            $model_id = $model['id'] ?? $model['name'] ?? '';
            // Remove "models/" prefix if present
            if (strpos($model_id, 'models/') === 0) {
                $model_id = substr($model_id, 7);
            }
            return array(
                'id' => $model_id,
                'object' => $model['object'] ?? 'model',
                'created' => $model['created'] ?? null,
                'owned_by' => $model['owned_by'] ?? 'google'
            );
        }, $data['data']);
    } else {
        // Handle the case where models are not in expected format
        $models = [];
        prod_trace( 'WARNING', 'Models not found in expected format in Google API response.');
    }

    // Ensure $models is an array
    if (!is_array($models) || empty($models)) {
        return $default_model_list;
    } else {
        // Sort the models by name
        usort($models, function($a, $b) {
            return $a['id'] <=> $b['id'];
        });
    }

    // Return the list of models
    return $models;

}
