<?php
/**
 * Kognetiks Chatbot - ChatGPT STT API - Ver 2.2.3
 *
 * This file contains the code for generating text using
 * the speech-to-text API.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Call the ChatGPT API for Speech-to-Text (STT)
function chatbot_chatgpt_call_stt_api($api_key, $message, $stt_option = null) {

    // DIAG - Diagnostics
    back_trace( 'NOTICE', 'chatbot_chatgpt_call_stt_api()' );

    global $chatbot_chatgpt_plugin_dir_path;
    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;
    global $kchat_settings;
    global $additional_instructions;
    global $model;
    global $voice;
    global $learningMessages;
    global $errorResponses;

    // Check for the API key
    if (empty($api_key) || $api_key == '[private]') {
        $api_key = esc_attr(get_option('chatbot_chatgpt_api_key'));
        if (empty($api_key)) {
            // Return an error message if the API key is not set
            if (get_locale() !== "en_US") {
                $localized_errorResponses = get_localized_errorResponses(get_locale(), $errorResponses);
            } else {
                $localized_errorResponses = $errorResponses;
            }
            return $localized_errorResponses[array_rand($localized_errorResponses)];
        }
    }

    // Determine the correct API URL based on STT option
    $api_url = 'https://api.openai.com/v1/audio/transcriptions';
    if ($stt_option == 'translate') {
        $api_url = 'https://api.openai.com/v1/audio/translations';
    }

    // Get the audio file name
    $counter = 1;
    $audio_file_name = get_chatbot_chatgpt_transients_files('chatbot_chatgpt_assistant_file_ids', $session_id, $counter);
    $audio_file_name = $chatbot_chatgpt_plugin_dir_path . 'uploads/' . $audio_file_name;

    // Ensure the audio file exists
    if (!file_exists($audio_file_name)) {
        return 'Audio file does not exist.';
    }

    // Validate the file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $audio_file_name);
    finfo_close($finfo);

    if (strpos($mime_type, 'audio/') === false && strpos($mime_type, 'video/') === false) {
        return "Error: The file is not an audio or video file. Please upload an audio or video file.";
    }

    // Prepare the request body
    $body = array(
        'model'           => esc_attr(get_option('chatbot_chatgpt_whisper_model_option', 'whisper-1')),
        'file'            => new CURLFile($audio_file_name, $mime_type, basename($audio_file_name)),
        'response_format' => 'text',
        'prompt'          => $message
    );

    // Set up request headers
    $headers = array(
        'Content-Type: multipart/form-data',
        'Authorization' => 'Bearer ' . $api_key,
    );

    // Make the request using wp_remote_post()
    $response = wp_remote_post($api_url, array(
        'method'    => 'POST',
        'timeout'   => 30,
        'body'      => $body,
        'headers'   => $headers
    ));

    // Handle errors
    if (is_wp_error($response)) {
        // DIAG - Diagnostics
        back_trace( 'ERROR', 'WP error: ' . $response->get_error_message() );
        return 'Error: ' . $response->get_error_message();
    }

    // Retrieve response body
    $response_body = wp_remote_retrieve_body($response);
    $response_data = json_decode($response_body, true);

    // Delete the uploaded file
    unlink($audio_file_name);

    // Handle API errors
    if (isset($response_data['error'])) {
        http_response_code(400);
        // DIAG - Diagnostics
        back_trace( 'ERROR', 'API error: ' . $response_data['error']['message'] );
        return 'Error: ' . esc_html($response_data['error']['message']);
    }

    // Store transcription result
    $transcription = $response_body;

    // Return early if only transcription is needed
    if ($stt_option === 'transcription-only') {
        return $transcription;
    }

    // Post-process the transcription with ChatGPT
    return chatbot_chatgpt_post_process_transcription($api_key, $message, $transcription);

}

// Process the transcription using ChatGPT to correct spelling and formatting.
function chatbot_chatgpt_post_process_transcription($api_key, $message, $transcription) {

    // DIAG - Diagnostics
    back_trace( 'NOTICE', 'chatbot_chatgpt_post_process_transcription()' );

    // Get API URL for text processing
    $api_url = get_chat_completions_api_url();
    $model = esc_attr(get_option('chatbot_chatgpt_model_choice', 'gpt-3.5-turbo'));
    $max_tokens = intval(get_option('chatbot_chatgpt_max_tokens_setting', 500));

    // Instructions for AI
    $additional_instructions = 'You are a helpful assistant. Your task is to correct any spelling discrepancies in the transcribed text. Only add necessary punctuation such as periods, commas, and capitalization, and use only the context provided.';

    // Prepare the request body
    $body = array(
        'model'       => $model,
        'max_tokens'  => $max_tokens,
        'temperature' => 0.5,
        'messages'    => array(
            array(
                'role'    => 'system',
                'content' => $message
            ),
            array(
                'role'    => 'user',
                'content' => $transcription
            )
        )
    );

    // Convert the body array to JSON
    $body_string = wp_json_encode($body);

    // Set up request headers
    $headers = array(
        'Content-Type'  => 'application/json',
        'Authorization' => 'Bearer ' . $api_key
    );

    // Make the request using wp_remote_post()
    $response = wp_remote_post($api_url, array(
        'method'    => 'POST',
        'timeout'   => 0,
        'body'      => $body_string,
        'headers'   => $headers
    ));

    // Handle errors
    if (is_wp_error($response)) {
        // DIAG - Diagnostics
        back_trace( 'ERROR', 'WP error: ' . $response->get_error_message() );
        return 'Error in API request: ' . $response->get_error_message();
    }

    // Retrieve response body
    $response_body = wp_remote_retrieve_body($response);
    $response_data = json_decode($response_body, true);

    // Extract AI response
    $analysis = '';
    if (is_array($response_data) && isset($response_data['choices'][0]['message']['content'])) {
        $analysis = $response_data['choices'][0]['message']['content'];
    }

    // Final formatted response
    $final_response = '**The transcription:** ' . $transcription . PHP_EOL . PHP_EOL . '**The analysis:** ' . $analysis;

    return !empty($final_response) ? $final_response : 'No transcription text found.';
}
