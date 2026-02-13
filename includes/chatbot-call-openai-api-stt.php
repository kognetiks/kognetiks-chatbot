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
function chatbot_chatgpt_call_stt_api($api_key, $message, $stt_option = null, $user_id = null, $page_id = null, $session_id = null, $assistant_id = null, $client_message_id = null) {

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

    // DIAG - Diagnostics - Ver 2.4.5
    // back_trace("NOTICE", "Starting OpenAI STT API call");
    // back_trace("NOTICE", "Message: " . $message);
    // back_trace("NOTICE", "User ID: " . $user_id);
    // back_trace("NOTICE", "Page ID: " . $page_id);
    // back_trace("NOTICE", "Session ID: " . $session_id);
    // back_trace("NOTICE", "Assistant ID: " . $assistant_id);
    // back_trace("NOTICE", "Client Message ID: " . $client_message_id);

    // Use client_message_id if provided, otherwise generate a unique message UUID for idempotency
    $message_uuid = $client_message_id ? $client_message_id : wp_generate_uuid4();

    // Lock the conversation BEFORE thread resolution to prevent empty-thread vs real-thread lock split
    $conv_lock = 'chatgpt_conv_lock_' . wp_hash($assistant_id . '|' . $user_id . '|' . $page_id . '|' . $session_id);
    $lock_timeout = 60; // 60 seconds timeout

    // Check for duplicate message UUID in conversation log
    $duplicate_key = 'chatgpt_message_uuid_' . $message_uuid;
    if (get_transient($duplicate_key)) {
        return "Error: Duplicate request detected. Please try again.";
    }

    // Lock check removed - main send function handles locking
    set_transient($duplicate_key, true, 120); // 2 minutes to prevent duplicates - Ver 2.3.7

    // Check for the API key
    if (empty($api_key) || $api_key == '[private]') {
        $api_key = esc_attr(get_option('chatbot_chatgpt_api_key'));
        // Decrypt the API key - Ver 2.2.6
        $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
        if (empty($api_key)) {
            // Return an error message if the API key is not set
            $localized_errorResponses = (get_locale() !== "en_US") 
                ? get_localized_errorResponses(get_locale(), $errorResponses) 
                : $errorResponses;
            // Clear locks on error
            // Lock clearing removed - main send function handles locking
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

    // Read the file content
    $file_data = file_get_contents($audio_file_name);
    $boundary = wp_generate_password(24, false);

    // Construct multipart request body manually
    $body = "--{$boundary}\r\n";
    $body .= "Content-Disposition: form-data; name=\"model\"\r\n\r\n";
    $body .= esc_attr(get_option('chatbot_chatgpt_whisper_model_option', 'whisper-1')) . "\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Disposition: form-data; name=\"response_format\"\r\n\r\n";
    $body .= "text\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Disposition: form-data; name=\"prompt\"\r\n\r\n";
    $body .= "{$message}\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Disposition: form-data; name=\"file\"; filename=\"" . basename($audio_file_name) . "\"\r\n";
    $body .= "Content-Type: {$mime_type}\r\n\r\n";
    $body .= $file_data . "\r\n";
    $body .= "--{$boundary}--\r\n";

    // Set up request headers
    $headers = array(
        'Authorization'   => 'Bearer ' . $api_key,
        'Content-Type'    => 'multipart/form-data; boundary=' . $boundary
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
        prod_trace( 'ERROR', 'WP error: ' . $response->get_error_message() );
        // Clear locks on error
        // Lock clearing removed - main send function handles locking
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
        // DIAG - Diagnostics - Ver 2.4.5
        prod_trace( 'ERROR', 'API error: ' . $response_data['error']['message'] );
        // Clear locks on error
        // Lock clearing removed - main send function handles locking
        return 'Error: ' . esc_html($response_data['error']['message']);
    }

    // Store transcription result
    $transcription = $response_body;

    // Return early if only transcription is needed
    if ($stt_option === 'transcription-only') {
        // Clear locks on success
        // Lock clearing removed - main send function handles locking
        return $transcription;
    }

    // Post-process the transcription with ChatGPT
    $result = chatbot_chatgpt_post_process_transcription($api_key, $message, $transcription, $session_id);
    // Clear locks on success
    delete_transient($conv_lock);
    return $result;

}

//Process the transcription using ChatGPT to correct spelling and formatting.
function chatbot_chatgpt_post_process_transcription($api_key, $message, $transcription, $session_id = null) {

    // Get API URL for text processing
    $api_url = get_chat_completions_api_url();
    $model = esc_attr(get_option('chatbot_chatgpt_model_choice', 'gpt-3.5-turbo'));
    $max_tokens = intval(get_option('chatbot_chatgpt_max_tokens_setting', 1000));

    // Build conversation context using standardized function - Ver 2.3.9+
    // This function handles conversation history building, message cleaning, and conversation continuity
    $conversation_context = chatbot_chatgpt_build_conversation_context('standard', 10, $session_id);

    // Instructions for AI
    $additional_instructions = 'You are a helpful assistant. Your task is to correct any spelling discrepancies in the transcribed text. Only add necessary punctuation such as periods, commas, and capitalization, and use only the context provided.';

    // Build system message with instructions and context
    // $message contains the original system instructions, combine with additional instructions
    $system_content = $additional_instructions . ' ' . $message;
    
    // Add session history to system message if available (from conversation continuity)
    if (!empty($conversation_context['session_history'])) {
        $system_content = $conversation_context['session_history'] . ' ' . $system_content;
    }

    // Build messages array with system message, conversation history, and transcription - Ver 2.3.9+
    $messages = array(
        array('role' => 'system', 'content' => $system_content)
    );
    
    // Add conversation history messages (structured format for better context) - Ver 2.3.9+
    if (!empty($conversation_context['messages'])) {
        $messages = array_merge($messages, $conversation_context['messages']);
    }
    
    // Add transcription as user message
    $messages[] = array('role' => 'user', 'content' => $transcription);

    // Prepare the request body
    $body = array(
        'model'       => $model,
        'messages'    => $messages
    );
    
    // Only add temperature if the model supports it
    if (!chatbot_openai_doesnt_support_temperature($model)) {
        $body['temperature'] = 0.5;
    }
    
    // Use max_completion_tokens for newer models, max_tokens for older models
    if (chatbot_openai_requires_max_completion_tokens($model)) {
        $body['max_completion_tokens'] = $max_tokens;
    } else {
        $body['max_tokens'] = $max_tokens;
    }

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
        'timeout'   => 30,
        'body'      => $body_string,
        'headers'   => $headers
    ));

    // Handle errors
    if (is_wp_error($response)) {
        // DIAG - Diagnostics - Ver 2.4.5
        prod_trace( 'ERROR', 'WP error: ' . $response->get_error_message() );
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
