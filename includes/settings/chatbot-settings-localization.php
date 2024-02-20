<?php
/**
 * Kognetiks Chatbot for WordPress - Localization
 *
 * This file contains the code for localization of the Chatbot globals.
 * It uses the ChatGPT API to translate the global variables into your language
 * of choice based on the Site Language setting found under Settings > General.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Cache the stopwords for the language - Ver 1.7.2
function get_localized_stopwords($language_code, $stopWords) {
    // Check if the stopwords for this language are already cached
    $cached_stopwords = get_transient('chatbot_chatgpt_stopwords_' . $language_code);

    if ($cached_stopwords === false) {
        // Stopwords not in cache, so call the function
        $cached_stopwords = localize_global_stopwords($language_code, $stopWords);
        // Store the stopwords in the cache, set an appropriate expiration time
        set_transient('chatbot_chatgpt_stopwords_' . $language_code, $cached_stopwords, 31536000);
    }

    return $cached_stopwords;
}

// Use ChatGPT to translate global variables - Ver 1.7.2
function localize_global_stopwords($language_code, $stopWords) {

    // DIAG - Log the language code
    // back_trace( 'NOTICE', '$language_code: ' . $language_code);
    // DIAG - Log the message
    // back_trace( 'NOTICE', '$stopWords: ' . print_r($stopWords, true));

    $stopWordsTemp = $stopWords;

    // Get the API key
    $api_key = get_option('chatbot_chatgpt_api_key');
    if (empty($api_key)) {
        $stopWords_string = implode("\n",$stopWords);
        $translated_array = explode("\n", $stopWords_string);
        return $translated_array;
    }

    // The current ChatGPT API URL endpoint for GPT-3.5-Turbo and GPT-4
    // $api_url = 'https://api.openai.com/v1/chat/completions';
    $api_url = get_chat_completions_api_url();

    $headers = array(
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type' => 'application/json',
    );

    // Model and message for testing
    $model = esc_attr(get_option('chatbot_chatgpt_model_choice', 'gpt-3.5-turbo'));
    // FIXME - For now switch gpt-4-turbo back got gpt-4-1106-preview
    if ($model == 'gpt-4-turbo') {
        $model = 'gpt-4-1106-preview';
    }

    $stopWords_string = implode(", ", $stopWords);
    $stopWords = "Translate the global variables into " . $language_code . ":\n\n" . $stopWords_string;
    // DIAG - Log the message
    // back_trace( 'NOTICE', '$stopWords ' . $stopWords);

    $body = array(
        'model' => $model,
        'max_tokens' => 4000,
        'temperature' => 0.5,
        'messages' => array(
            array('role' => 'system', 'content' => 'You are a master translator whose job it is to translate word-for-word anything sent to you.  The language code will be provided in the format of [language code]_[COUNTRY CODE], where the language code is a two-letter code based on the ISO 639-1 standard.  Return only the list of translated words without the English.'),
            array('role' => 'user', 'content' => $stopWords)
        ),
    );

    $args = array(
        'headers' => $headers,
        'body' => json_encode($body),
        'method' => 'POST',
        'data_format' => 'body',
        'timeout' => 50,
    );

    $response = wp_remote_post($api_url, $args);
    // DIAG - Log the response
    // back_trace( 'NOTICE', 'localize_global_variables - $response: ' . print_r($response, true));

    if (is_wp_error($response)) {
        // DIAG - Log the error message
        // back_trace( 'NOTICE', '$response->get_error_message(): ' . $response->get_error_message());
        return 'WP_Error: ' . $response->get_error_message() . '. Please check Settings for a valid API key or your OpenAI account for additional information.';
    }

    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    // DIAG - Log the response body
    // back_trace( 'NOTICE', '$response_body ' . print_r($response_body, true));

    // Convert the translated string back to an array
    if (!empty($response_body['choices'][0]['message']['content'])) {
        // Convert the translated string back to an array
        $translated_array = explode(", ", $response_body['choices'][0]['message']['content']);
        // DIAG - Log the translations
        // back_trace( 'NOTICE', 'STOP WORDS TRANSLATION ' . print_r($translated_array, true));
    } else {
        $translated_array = $stopWordsTemp;
    }

    // Return the translated message
    return $translated_array;

}

// Cache the learningMessages for the language - Ver 1.7.2
function get_localized_learningMessages($language_code, $learningMessages) {

    // Check if the learningMessages for this language are already cached
    $cached_learningMessages = get_transient('chatbot_chatgpt_learningMessages_' . $language_code);

    if ($cached_learningMessages === false) {
        // learningMessages not in cache, so call the function
        $cached_learningMessages = localize_global_learningMessages($language_code, $learningMessages);
        // Store the learningMessages in the cache, set an appropriate expiration time
        set_transient('chatbot_chatgpt_learningMessages_' . $language_code, $cached_learningMessages, 31536000);
    }

    return $cached_learningMessages;

}

// Use ChatGPT to translate global variables - Ver 1.7.2
function localize_global_learningMessages($language_code, $learningMessages) {

    // DIAG - Log the language code
    // back_trace( 'NOTICE', '$language_code: ' . $language_code);
    // DIAG - Log the message
    // back_trace( 'NOTICE', '$learningMessages: ' . print_r($learningMessages, true));

    $learningMessagesTemp = $learningMessages;

    // Get the API key
    $api_key = get_option('chatbot_chatgpt_api_key');
    if (empty($api_key)) {
        $learningMessages_string = implode("\n", $learningMessages);
        $translated_array = explode("\n", $learningMessages_string);
        return $translated_array;
    }

    // The current ChatGPT API URL endpoint for GPT-3.5-Turbo and GPT-4
    // $api_url = 'https://api.openai.com/v1/chat/completions';
    $api_url = get_chat_completions_api_url();

    $headers = array(
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type' => 'application/json',
    );

    // Model and message for testing
    $model = esc_attr(get_option('chatbot_chatgpt_model_choice', 'gpt-3.5-turbo'));
    // FIXME - For now switch gpt-4-turbo back got gpt-4-1106-preview
    if ($model == 'gpt-4-turbo') {
        $model = 'gpt-4-1106-preview';
    }

    $learningMessages_string = implode("\n", $learningMessages);
    $learningMessages = "Translate the global variables into " . $language_code . ":\n\n" . $learningMessages_string;
    // DIAG - Log the message
    // back_trace( 'NOTICE', '$learningMessages ' . $learningMessages);

    $body = array(
        'model' => $model,
        'max_tokens' => 1000,
        'temperature' => 0.5,
        'messages' => array(
            array('role' => 'system', 'content' => 'You are a master translator whose job it is to translate the phrases sent to you.  The language code will be provided in the format of [language code]_[COUNTRY CODE], where the language code is a two-letter code based on the ISO 639-1 standard.  Return only the translated phrases without the English.'),
            array('role' => 'user', 'content' => $learningMessages)
        ),
    );

    $args = array(
        'headers' => $headers,
        'body' => json_encode($body),
        'method' => 'POST',
        'data_format' => 'body',
        'timeout' => 50,
    );

    $response = wp_remote_post($api_url, $args);
    // DIAG - Log the response
    // back_trace( 'NOTICE', 'localize_global_variables - $response: ' . print_r($response, true));

    if (is_wp_error($response)) {
        // DIAG - Log the error message
        // back_trace( 'NOTICE', '$response->get_error_message(): ' . $response->get_error_message());
        return 'WP_Error: ' . $response->get_error_message() . '. Please check Settings for a valid API key or your OpenAI account for additional information.';
    }

    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    // DIAG - Log the response body
    // back_trace( 'NOTICE', print_r($response_body, true));

    // Convert the translated string back to an array
    if (!empty($response_body['choices'][0]['message']['content'])) {
        // Convert the translated string back to an array
        $translated_array = explode("\n", $response_body['choices'][0]['message']['content']);
        $translated_array = array_map(function($phrase) { return $phrase . ' '; }, $translated_array);
    } else {
        $translated_array = $learningMessagesTemp;
    }

    // Convert the translated string back to an array
    // $translated_array = explode("\n", $response_body['choices'][0]['message']['content']);
    // $translated_array = array_map(function($phrase) { return $phrase . ' '; }, $translated_array);

    // Return the translated message
    return $translated_array;

}

// Cache the errorResponses for the language - Ver 1.7.2
function get_localized_errorResponses($language_code, $errorResponses) {

    // Check if the errorResponses for this language are already cached
    $cached_errorResponses = get_transient('chatbot_chatgpt_errorResponses_' . $language_code);

    if ($cached_errorResponses === false) {
        // errorResponses not in cache, so call the function
        $cached_errorResponses = localize_global_errorResponses($language_code, $errorResponses);
        // Store the errorResponses in the cache, set an appropriate expiration time
        set_transient('chatbot_chatgpt_errorResponses_' . $language_code, $cached_errorResponses, 31536000);
    }

    return $cached_errorResponses;

}

// Use ChatGPT to translate global variables - Ver 1.7.2
function localize_global_errorResponses($language_code, $errorResponses) {

    // DIAG - Log the language code
    // back_trace( 'NOTICE', '$language_code: ' . $language_code);
    // DIAG - Log the message
    // back_trace( 'NOTICE', '$errorResponses: ' . print_r($errorResponses, true));

    $errorResponsesTemp = $errorResponses;

    // Get the API key
    $api_key = get_option('chatbot_chatgpt_api_key');
    if (empty($api_key)) {
        $errorResponses_string = implode("\n", $errorResponses);
        $translated_array = explode("\n", $errorResponses_string);
        return $translated_array;
    }

    // The current ChatGPT API URL endpoint for GPT-3.5-Turbo and GPT-4
    // $api_url = 'https://api.openai.com/v1/chat/completions';
    $api_url = get_chat_completions_api_url();

    $headers = array(
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type' => 'application/json',
    );

    // Model and message for testing
    $model = esc_attr(get_option('chatbot_chatgpt_model_choice', 'gpt-3.5-turbo'));
    // FIXME - For now switch gpt-4-turbo back got gpt-4-1106-preview
    if ($model == 'gpt-4-turbo') {
        $model = 'gpt-4-1106-preview';
    }

    $errorResponses_string = implode("\n", $errorResponses);
    $errorResponses = "Translate the global variables into " . $language_code . ":\n\n" . $errorResponses_string;
    // DIAG - Log the message
    // back_trace( 'NOTICE', '$errorResponses ' . $errorResponses);

    $body = array(
        'model' => $model,
        'max_tokens' => 1000,
        'temperature' => 0.5,
        'messages' => array(
            array('role' => 'system', 'content' => 'You are a master translator whose job it is to translate the phrases sent to you.  The language code will be provided in the format of [language code]_[COUNTRY CODE], where the language code is a two-letter code based on the ISO 639-1 standard.  Return only the translated phrases without the English.'),
            array('role' => 'user', 'content' => $errorResponses)
        ),
    );

    $args = array(
        'headers' => $headers,
        'body' => json_encode($body),
        'method' => 'POST',
        'data_format' => 'body',
        'timeout' => 50,
    );

    $response = wp_remote_post($api_url, $args);
    // DIAG - Log the response
    // back_trace( 'NOTICE', 'localize_global_variables - $response: ' . print_r($response, true));

    if (is_wp_error($response)) {
        // DIAG - Log the error message
        // back_trace( 'NOTICE', '$response->get_error_message(): ' . $response->get_error_message());
        return 'WP_Error: ' . $response->get_error_message() . '. Please check Settings for a valid API key or your OpenAI account for additional information.';
    }

    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    // DIAG - Log the response body
    // back_trace( 'NOTICE', print_r($response_body, true));

    // Convert the translated string back to an array
    if (!empty($response_body['choices'][0]['message']['content'])) {
        // Convert the translated string back to an array
        $translated_array = explode("\n", $response_body['choices'][0]['message']['content']);
        $translated_array = array_map(function($phrase) { return $phrase . ' '; }, $translated_array);
    } else {
        $translated_array = $errorResponsesTemp;
    }

    // Convert the translated string back to an array
    // $translated_array = explode("\n", $response_body['choices'][0]['message']['content']);
    // $translated_array = array_map(function($phrase) { return $phrase . ' '; }, $translated_array);

    // Return the translated message
    return $translated_array;

}
