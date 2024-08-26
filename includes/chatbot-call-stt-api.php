<?php
/**
 * Kognetiks Chatbot for WordPress - ChatGPT STT API - Ver 2.0.1
 *
 * This file contains the code for generating text using
 * the speech-to-text API.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Call the ChatGPT API
function chatbot_chatgpt_call_stt_api($api_key, $message, $stt_option = null) {

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

    // Return 'COMING SOON: STT API is not yet implemented.';

    // DIAG - Diagnostics - Ver 1.8.6
    // back_trace( 'NOTICE', 'chatbot_chatgpt_call_tts_api()');
    // back_trace( 'NOTICE', 'BEGIN $user_id: ' . $user_id);
    // back_trace( 'NOTICE', 'BEGIN $page_id: ' . $page_id);
    // back_trace( 'NOTICE', 'BEGIN $session_id: ' . $session_id);
    // back_trace( 'NOTICE', 'BEGIN $thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', 'BEGIN $assistant_id: ' . $assistant_id);

    // Check for the API key
    if (empty($api_key) or $api_key == '[private]') {
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

    // Check for the STT option
    // Default to 'transcribe' if the option is not set
    if ( empty($stt_option) or $stt_option == 'transcribe' or $stt_option == 'transcription-only') {
        // Transcription API URL
        $api_url = 'https://api.openai.com/v1/audio/transcriptions';
    } elseif ( $stt_option == 'translate' ) {
        // Translate API URL
        // For supported languages see:
        // https://platform.openai.com/docs/guides/speech-to-text/supported-languages
        $api_url = 'https://api.openai.com/v1/audio/translations';
    }

    // Get the audio file name
    $counter = 1;
    $audio_file_name = get_chatbot_chatgpt_transients_files('chatbot_chatgpt_assistant_file_ids', $session_id, $counter);

    // DIAG - Diagnostics - Ver 2.0.1
    // back_trace( 'NOTICE', '$audio_file_name: ' . $audio_file_name);

    $audio_file_name = CHATBOT_CHATGPT_PLUGIN_DIR_PATH . 'uploads/' . $audio_file_name;

    // Ensure the audio file exists
    if (!file_exists($audio_file_name)) {
        return 'Audio file does not exist.';
    }

    // Ensure that the file is an audio file
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $audio_file_name);

    // DIAG - Diagnostics - Ver 2.0.2.1
    // back_trace( 'NOTICE', '$audio_file_name: ' . $audio_file_name);
    // back_trace( 'NOTICE', '$finfo: ' . print_r($finfo, true));
    // back_trace( 'NOTICE', '$mime_type: ' . $mime_type);

    if (strpos($mime_type, 'audio/') === FALSE && strpos($mime_type, 'video/') === FALSE) {
        return "Error: The file is not an audio or video file. Please upload an audio or video file.";
    }

    // DIAG - Diagnostics - Ver 2.0.1
    // back_trace( 'NOTICE', '$audio_file_name: ' . $audio_file_name);

    // Create a CURLFile object for the audio file
    $audio_file = new CURLFile($audio_file_name, $mime_type, basename($audio_file_name));
    $audio_model = esc_attr(get_option('chatbot_chatgpt_whisper_model_option', 'whisper-1'));

    // DIAG - Diagnostics - Ver 2.0.1
    // back_trace( 'NOTICE', '$audio_model: ' . $audio_model);

    // Prepare the body for the POST request
    $body = array(
        'model' => $audio_model,
        'file' => $audio_file,
        'response_format' => 'text',
        'prompt' => $message
    );

    // Initialize cURL session
    $ch = curl_init($api_url);

    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_TIMEOUT, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: multipart/form-data',
        'Authorization: Bearer ' . $api_key
    ]);

    // Execute the cURL request and capture the response
    $response = curl_exec($ch);
    $error = curl_error($ch);

    // DIAG - Diagnostics - Ver 2.0.1
    // back_trace( 'NOTICE', '$response: ' . $response);
    // back_trace( 'NOTICE', '$error: ' . $error);

    // Close the cURL session
    curl_close($ch);
 
    // Delete the file
    unlink($audio_file_name);

    // Decode the JSON response
    $response_data = json_decode($response, true);
    
    // Check if the response contains an 'error' key
    if (isset($response_data['error'])) {
        $sanitized_message = htmlspecialchars($response_data['error']['message'], ENT_QUOTES, 'UTF-8');
        http_response_code(400); // Send a 400 Bad Request status code
        exit('Error: ' . $sanitized_message);
    }

    //
    // POST PROCESS THE TRANSCRIPTION
    //

    $transcription = $response;

    // Transcription only for OMNI
    if ( $stt_option == 'transcription-only' ) {
        return $transcription;
    }

    // The current ChatGPT API URL endpoint for gpt-3.5-turbo and gpt-4
    // $api_url = 'https://api.openai.com/v1/chat/completions';
    $api_url = get_chat_completions_api_url();

    $model = esc_attr(get_option('chatbot_chatgpt_model_choice', 'gpt-3.5-turbo'));
    $max_tokens = intval(esc_attr(get_option('chatbot_chatgpt_max_tokens_setting', '500')));

    $additional_instructions = 'You are a helpful assistant. Your task is to correct any spelling discrepancies in the transcribed text. Only add necessary punctuation such as periods, commas, and capitalization, and use only the context provided.';

    // Prepare the body for the POST request
    $body = array(
        'model' => $model,
        'max_tokens' => $max_tokens,
        'temperature' => 0.5,
        'messages' => [
            array(
                'role' => 'system',
                'content' => $message
            ),
            array(
                'role' => 'user',
                'content' => $transcription
            )],
    );
    
    // Convert the body array to a JSON string
    $body_string = json_encode($body);
    
    // Initialize cURL session
    $ch = curl_init($api_url);
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body_string);
    curl_setopt($ch, CURLOPT_TIMEOUT, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ]);

    // Execute the cURL request and capture the response
    $response = curl_exec($ch);
    $error = curl_error($ch);

    // DIAG - Diagnostics - Ver 2.0.1
    // back_trace( 'NOTICE', '$response: ' . $response);
    // back_trace( 'NOTICE', '$error: ' . $error);

    // Close the cURL session
    curl_close($ch);

    if ($error) {
        return 'Error in cURL: ' . $error;
    }

    //
    // END POST PROCESS THE TRANSCRIPTION
    //

    // DIAG - Diagnostics - Ver 2.0.1
    // back_trace( 'NOTICE', '$response: ' . $response);

    // Decode the JSON response into an array
    $response_array = json_decode($response, true);

    // Check if the decoding was successful and the required keys exist
    if (is_array($response_array) && isset($response_array['choices'][0]['message']['content'])) {
        $analysis = $response_array['choices'][0]['message']['content'];
    } else {
        // Handle the error case here
        $analysis = '';
    }

    $response = '**The transcription:** ' . $transcription . PHP_EOL . PHP_EOL . '**The analysis:** ' . $analysis;

    // if $response = '' then return 'No transcription text found.' else return the $response
    if ($response == '') {
        return 'No transcription text found.';
    } else {
        // FIXME - Delete the audio file after successful transcription?
        // unlink($audio_file_name); // Delete the audio file after successful transcription
        return $response;
    }

}
