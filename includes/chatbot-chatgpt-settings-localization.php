<?php
/**
 * Chatbot ChatGPT for WordPress - Localization
 *
 * This file contains the code for localization of the Chatbot ChatGPT globals.
 * 
 * 
 *
 * @package chatbot-chatgpt
 */

 // If this file is called directly, abort.
if ( ! defined( 'WPINC' ) )
die;

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
    alt_chatbot_chatgpt_back_trace( 'NOTICE', '$language_code: ' . $language_code);
    // DIAG - Log the message
    alt_chatbot_chatgpt_back_trace( 'NOTICE', '$stopWords: ' . print_r($stopWords, true));

    // Get the API key
    $api_key = get_option('chatgpt_api_key');

    // The current ChatGPT API URL endpoint for GPT-3.5-Turbo and GPT-4
    $api_url = 'https://api.openai.com/v1/chat/completions';

    $headers = array(
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type' => 'application/json',
    );

    // Model and message for testing
    $model = esc_attr(get_option('chatgpt_model_choice', 'gpt-3.5-turbo'));
    // FIXME - For now switch gpt-4-turbo back got gpt-4-1106-preview
    if ($model == 'gpt-4-turbo') {
        $model = 'gpt-4-1106-preview';
    }

    $stopWords_string = implode(", ", $stopWords);
    $stopWords = "Translate the global variables into " . $language_code . ":\n\n" . $stopWords_string;
    // DIAG - Log the message
    alt_chatbot_chatgpt_back_trace( 'NOTICE', '$stopWords ' . $stopWords);

    $body = array(
        'model' => $model,
        'max_tokens' => 1000,
        'temperature' => 0.5,
        'messages' => array(
            array('role' => 'system', 'content' => 'You are a master translator whose job it is to translate word-for-word anything sent to you.  The language code will be provided in the format of [language code]_[COUNTRY CODE], where the language code is a two-letter code based on the ISO 639-1 standard.  Return only the list of translated words wihtout the English.'),
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
    alt_chatbot_chatgpt_back_trace( 'NOTICE', 'localize_global_variables - $response: ' . print_r($response, true));

    if (is_wp_error($response)) {
        // DIAG - Log the error message
        alt_chatbot_chatgpt_back_trace( 'NOTICE', '$response->get_error_message(): ' . $response->get_error_message());
        return 'WP_Error: ' . $response->get_error_message() . '. Please check Settings for a valid API key or your OpenAI account for additional information.';
    }

    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    // DIAG - Log the response body
    alt_chatbot_chatgpt_back_trace( 'NOTICE', print_r($response_body, true));

    // Convert the translated string back to an array
    $translated_array = explode(", ", $response_body['choices'][0]['message']['content']);

    // Return the translated message
    return $translated_array;

}

// Cache the learningMessages for the language - Ver 1.7.2
function get_localized_learningMessages($language_code, $learningMessages) {

    // Check if the learningMessages for this language are already cached
    $cached_learningMessages = get_transient('chatbot_chatgpt_learningMessages_' . $language_code);

    if ($cached_learningMessages === false) {
        // learningMessagesnot in cache, so call the function
        $cached_learningMessages = localize_global_learningMessages($language_code, $learningMessages);
        // Store the learningMessages in the cache, set an appropriate expiration time
        set_transient('chatbot_chatgpt_learningMessages_' . $language_code, $cached_learningMessages, 31536000);
    }

    return $cached_learningMessages;

}

// Use ChatGPT to translate global variables - Ver 1.7.2
function localize_global_learningMessages($language_code, $learningMessages) {

    // DIAG - Log the language code
    alt_chatbot_chatgpt_back_trace( 'NOTICE', '$language_code: ' . $language_code);
    // DIAG - Log the message
    alt_chatbot_chatgpt_back_trace( 'NOTICE', '$learningMessages: ' . print_r($learningMessages, true));

    // Get the API key
    $api_key = get_option('chatgpt_api_key');

    // The current ChatGPT API URL endpoint for GPT-3.5-Turbo and GPT-4
    $api_url = 'https://api.openai.com/v1/chat/completions';

    $headers = array(
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type' => 'application/json',
    );

    // Model and message for testing
    $model = esc_attr(get_option('chatgpt_model_choice', 'gpt-3.5-turbo'));
    // FIXME - For now switch gpt-4-turbo back got gpt-4-1106-preview
    if ($model == 'gpt-4-turbo') {
        $model = 'gpt-4-1106-preview';
    }

    $learningMessages_string = implode("\n", $learningMessages);
    $learningMessages = "Translate the global variables into " . $language_code . ":\n\n" . $learningMessages_string;
    // DIAG - Log the message
    alt_chatbot_chatgpt_back_trace( 'NOTICE', '$learningMessages ' . $learningMessages);

    $body = array(
        'model' => $model,
        'max_tokens' => 1000,
        'temperature' => 0.5,
        'messages' => array(
            array('role' => 'system', 'content' => 'You are a master translator whose job it is to translate these phrasea sent to you.  The language code will be provided in the format of [language code]_[COUNTRY CODE], where the language code is a two-letter code based on the ISO 639-1 standard.  Return only the translated phrases wihtout the English.'),
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
    alt_chatbot_chatgpt_back_trace( 'NOTICE', 'localize_global_variables - $response: ' . print_r($response, true));

    if (is_wp_error($response)) {
        // DIAG - Log the error message
        alt_chatbot_chatgpt_back_trace( 'NOTICE', '$response->get_error_message(): ' . $response->get_error_message());
        return 'WP_Error: ' . $response->get_error_message() . '. Please check Settings for a valid API key or your OpenAI account for additional information.';
    }

    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    // DIAG - Log the response body
    alt_chatbot_chatgpt_back_trace( 'NOTICE', print_r($response_body, true));

    // Convert the translated string back to an array
    $translated_array = explode("\n", $response_body['choices'][0]['message']['content']);
    $translated_array = array_map(function($phrase) { return $phrase . ' '; }, $translated_array);

    // Return the translated message
    return $translated_array;

}

// Enhanced Error Logging if Diagnostic Mode is On - Ver 1.6.9
// Call this function using chatbot_chatgpt_back_trace( 'NOTICE', $stopWords);
// [ERROR], [WARNING], [NOTICE], or [SUCCESS]
// chatbot_chatgpt_back_trace( 'ERROR', 'Some message');
// chatbot_chatgpt_back_trace( 'WARNING', 'Some message');
// chatbot_chatgpt_back_trace( 'NOTICE', 'Some message');
// chatbot_chatgpt_back_trace( 'SUCCESS', 'Some message');
function alt_chatbot_chatgpt_back_trace($stopWords_type = "NOTICE", $stopWords = "No message") {

    // Check if diagnostics is On
    $chatbot_chatgpt_diagnostics = esc_attr(get_option('chatbot_chatgpt_diagnostics', 'ERROR'));

    $chatbot_chatgpt_diagnostics = "On";

    if ('Off' === $chatbot_chatgpt_diagnostics) {
        return;
    }

    // Belt and suspenders - make sure the value is either Off or Error
    if ('On' === $chatbot_chatgpt_diagnostics) {
        $chatbot_chatgpt_diagnostics = 'Error';
        update_option('chatbot_chatgpt_diagnostics', $chatbot_chatgpt_diagnostics);
    }

    $backtrace = debug_backtrace();
    // $caller = array_shift($backtrace);
    $caller = $backtrace[1]; // Get the second element from the backtrace array

    $file = basename($caller['file']); // Gets the file name
    $function = $caller['function']; // Gets the function name
    $line = $caller['line']; // Gets the line number

    if ($stopWords === null || $stopWords === '') {
        $stopWords = "No message";
    }
    if ($stopWords_type === null || $stopWords_type === '') {
        $stopWords_type = "NOTICE";
    }

    // Convert the message to a string if it's an array
    if (is_array($stopWords)) {
        $stopWords = print_r($stopWords, true); // Return the output as a string
    }

    // Upper case the message type
    $stopWords_type = strtoupper($stopWords_type);

    // Message Type: Indicating whether the log is an error, warning, notice, or success message.
    // Prefix the message with [ERROR], [WARNING], [NOTICE], or [SUCCESS].
    // Check for other levels and print messages accordingly
    if ('Error' === $chatbot_chatgpt_diagnostics) {
        // Print all types of messages
        error_log("[Chatbot ChatGPT] [$file] [$function] [$line] [$stopWords_type] [$stopWords]");
    } elseif ('Success' === $chatbot_chatgpt_diagnostics || 'Failure' === $chatbot_chatgpt_diagnostics) {
        // Print only SUCCESS and FAILURE messages
        if (in_array($stopWords_type, ['SUCCESS', 'FAILURE'])) {
            error_log("[Chatbot ChatGPT] [$file] [$function] [$line] [$stopWords_type] [$stopWords]");
        }
    } elseif ('Warning' === $chatbot_chatgpt_diagnostics) {
        // Print only ERROR and WARNING messages
        if (in_array($stopWords_type, ['ERROR', 'WARNING'])) {
            error_log("[Chatbot ChatGPT] [$file] [$function] [$line] [$stopWords_type] [$stopWords]");
        }
    } elseif ('Notice' === $chatbot_chatgpt_diagnostics) {
        // Print ERROR, WARNING, and NOTICE messages
        if (in_array($stopWords_type, ['ERROR', 'WARNING', 'NOTICE'])) {
            error_log("[Chatbot ChatGPT] [$file] [$function] [$line] [$stopWords_type] [$stopWords]");
        }
    } else {
        // Exit if none of the conditions are met
        return;
    }

}