<?php
/**
 * Kognetiks Chatbot for WordPress - ChatGPT TTS API - Ver 1.9.4
 *
 * This file contains the code for generating images using the 
 * the DALL-2 or DALL-3 API.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Call the ChatGPT API
function chatbot_chatgpt_call_tts_api($api_key, $message) {

    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;
    global $script_data_array;
    global $additional_instructions;
    global $model;

    global $learningMessages;
    global $errorResponses;


    // Generate directory path
    $audio_dir_path = CHATBOT_CHATGPT_PLUGIN_DIR_PATH . 'audio/';
    // back_trace( 'NOTICE', '$audio_dir_path: ' . $audio_dir_path);

    // Create directory if it doesn't exist
    if (!file_exists($audio_dir_path)) {
        mkdir($audio_dir_path, 0755, true);
    }

    $audio_file_name = $session_id . '_' . time() . '.mp3';
    $audio_file = $audio_dir_path . $audio_file_name;

    // Get the URL of the plugins directory
    $plugins_url = plugins_url();

    // Generate the URL of the audio file
    $audio_file_url = $plugins_url . '/chatbot-chatgpt/audio/' . $audio_file_name;

    $audio_output = null;

    // DIAG - Diagnostics - Ver 1.8.6
    // back_trace( 'NOTICE', 'chatbot_chatgpt_call_api()');
    // back_trace( 'NOTICE', 'BEGIN $user_id: ' . $user_id);
    // back_trace( 'NOTICE', 'BEGIN $page_id: ' . $page_id);
    // back_trace( 'NOTICE', 'BEGIN $session_id: ' . $session_id);
    // back_trace( 'NOTICE', 'BEGIN $thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', 'BEGIN $assistant_id: ' . $assistant_id);

    // Select the OpenAI Model
    // One of tts-1-1106, tts-1-hd, tts-1-hd-1106
    if ( !empty($script_data_array['model']) ) {
        $model = $script_data_array['model'];
        // DIAG - Diagnostics - Ver 1.9.4
        // back_trace( 'NOTICE', '$model from script_data_array: ' . $model);
    } else {
        $model = esc_attr(get_option('chatbot_chatgpt_model_choice', 'tts-1-1106'));
        // DIAG - Diagnostics - Ver 1.9.4
        // back_trace( 'NOTICE', '$model from get_option: ' . $model);
    }

    // Specify the voice you want to use
    // One of: alloy, echo, fable, onyx, nova, or shimmer
    $voice = "alloy"; // Specified voice
    
    // API URL for the TTS service
    $api_url = 'https://api.openai.com/v1/audio/speech';
    
    // Creating the array to be converted to JSON
    $body = array(
      'model' => $model,
      'voice' => $voice,
      'input' => $message
    );
    
    // Encoding the array to JSON
    $json_body = json_encode($body);
    
    // Initialize cURL session
    $ch = curl_init($api_url);
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ));
    
    // Execute the cURL request and capture the response
    $response = curl_exec($ch);

    // Write the audio to a file
    $result = file_put_contents($audio_file, $response);

    // DIAG - Diagnostics - Ver 1.9.4
    // back_trace( 'NOTICE', '$response: ' . $response );
    
    // Check for errors
    if (curl_errno($ch)) {
        // DIAG - Diagnostics - Ver 1.9.4
        // back_trace( 'NOTICE', 'Error: ' . curl_error($ch));
        echo 'Error in cURL: ' . curl_error($ch);
    } else {
        // Process the response
        $audio_output = "[Listen here](" . $audio_file_url .")";
    }
    
    // Close the cURL session
    curl_close($ch);

        // DIAG - Diagnostics - Ver 1.6.7
    // back_trace( 'NOTICE', '$decoded: ' . $decoded);

    // Get the user ID and page ID
    if (empty($user_id)) {
        $user_id = get_current_user_id(); // Get current user ID
    }
    if (empty($page_id)) {
        $page_id = get_the_id(); // Get current page ID
        if (empty($page_id)) {
            // $page_id = get_queried_object_id(); // Get the ID of the queried object if $page_id is not set
            // Changed - Ver 1.9.1 - 2024 03 05
            $page_id = get_the_ID(); // Get the ID of the queried object if $page_id is not set
        }
    }

    if (!empty($audio_output)) {

        // DIAG - Diagnostics - Ver 1.9.4
        // back_trace( 'NOTICE', 'Usage - Prompt Tokens: ' . $response_body["usage"]["prompt_tokens"]);
        // back_trace( 'NOTICE', 'Usage - Completion Tokens: ' . $response_body["usage"]["completion_tokens"]);
        // back_trace( 'NOTICE', 'Usage - Total Tokens: ' . $response_body["usage"]["total_tokens"]);

        // Add the usage to the conversation tracker
        // append_message_to_conversation_log($session_id, $user_id, $page_id, 'Prompt Tokens', null, null, $response_body["usage"]["prompt_tokens"]);
        // append_message_to_conversation_log($session_id, $user_id, $page_id, 'Completion Tokens', null, null, $response_body["usage"]["completion_tokens"]);
        // append_message_to_conversation_log($session_id, $user_id, $page_id, 'Total Tokens', null, null, $response_body["usage"]["total_tokens"]);

        // FIXME - BEFORE RETURNING THE AUDIO FILE
        // FIXME - ADD TRANSIENT TO DELETE AUDIO FILE AFTER 2 HOURS

        // FIXME - TRY TO PLAY IT HERE
        // $file = $audio_file; // Specify the path to your MP3 file
        // $mime = 'audio/mpeg'; // MIME type of the audio file. For MP3, it's audio/mpeg

        // if (file_exists($file)) {
        //     header('Content-Type: '.$mime);
        //     header('Content-Length: ' . filesize($file));
        //     header('Content-Disposition: inline; filename="' . basename($file) . '"');
        //     header('X-Pad: avoid browser bug');
        //     header('Cache-Control: no-cache');
        //     header('Accept-Ranges: bytes');
        //     if (ob_get_level()) {
        //         ob_end_clean();
        //     }
        //     @readfile($file); // @ is used to suppress errors, you might want to handle errors differently
        //     exit;
        // } else {
        //     echo "File not found.";
        // }
        
        // Set transient to delete the audio file after 2 hours
        chatbot_chatgpt_delete_audio_file_id( $audio_file_url );

        return $audio_output;

    } else {
        // FIXME - Decide what to return here - it's an error
        // back_trace( 'ERROR', 'API ERROR ' . print_r($response_body, true));
        if (get_locale() !== "en_US") {
            $localized_errorResponses = get_localized_errorResponses(get_locale(), $errorResponses);
        } else {
            $localized_errorResponses = $errorResponses;
        }
        // Return a random error message
        return $localized_errorResponses[array_rand($localized_errorResponses)];
    }

}

// Setup the cron job to delete the audio file after 2 hours
function chatbot_chatgpt_delete_audio_file_id( $file_id ) {

    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;

    // DIAG - Diagnostics - Ver 1.9.2
    // back_trace( 'NOTICE', 'Setup deleting audio file after 2 hours: ' . $file_id);

    // Set a transient that expires in 2 hours
    $timeFrameForDelete = time() + 2 * 60 * 60;
    // FIXME - Override the transient to expire in 2 minutes
    // $timeFrameForDelete = time() + 2 * 60;
    set_transient('chatbot_chatgpt_delete_audio_file_' . $file_id, $file_id, $timeFrameForDelete);

    // Set a cron job to delete the file in 1 hour 45 minutes
    $shorterTimeFrameForDelete = time() + 1 * 60 * 60 + 45 * 60;
    // FIXME - Override the cron job to delete the file in 1 minute 45 seconds
    // $shorterTimeFrameForDelete = time() + 1 * 60 + 45;
    if (!wp_next_scheduled('delete_audio_file', $file_id)) {
        wp_schedule_single_event($shorterTimeFrameForDelete, 'delete_audio_file', array($file_id));
    }

}

// Cleanup in Aisle 4 on OpenAI - Ver 1.7.9
function deleteAudioFile($file_id) {

    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;

    // DIAG - Diagnostics - Ver 1.9.2
    // back_trace( 'NOTICE', 'Delete the audio file: ' . $file_id);

    // Which file on the server
    // $audio_dir_path = CHATBOT_CHATGPT_PLUGIN_DIR_PATH . 'audio/';
    // $audio_file = $audio_dir_path . $file_id;
    // Now delete the file
    if (file_exists($file_id)) {
        unlink($file_id);
    }
    
    // if ($http_status_code == 200 || $http_status_code == 204) {
    //     // DIAG - Diagnostics - Ver 1.7.9
    //     // back_trace( 'SUCCESS', "File deleted successfully.\n");
    // } else {
    //     // If the request was not successful, you may want to handle it differently,
    //     // such as logging an error or retrying the request.
    //     // DIAG - Diagnostics - Ver 1.7.9
    //     // back_trace( 'ERROR', "HTTP status code: $http_status_code\n");
    //     // back_trace( 'ERROR', "Response: $response\n");
    // }

}
add_action( 'delete_audio_file', 'deleteAudioFile' );
