<?php
/**
 * Kognetiks Chatbot - ChatGPT TTS API - Ver 1.9.4
 *
 * This file contains the code for generating images using
 * the text-to-speech API.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
use JetBrains\PhpStorm\NoReturn;

if ( ! defined( 'WPINC' ) ) {
    die();
}

// Call the ChatGPT API
function chatbot_chatgpt_call_tts_api($api_key, $message, $voice = null, $user_id = null, $page_id = null, $session_id = null, $assistant_id = null, $client_message_id = null) {

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
    // back_trace("NOTICE", "Starting OpenAI TTS API call");
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
    if (empty($api_key) or $api_key == '[private]') {
        $api_key = esc_attr(get_option('chatbot_chatgpt_api_key'));
        // Decrypt the API key - Ver 2.2.6
        $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
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

    // Generate directory path
    $audio_dir_path = $chatbot_chatgpt_plugin_dir_path . 'audio/';

    // Ensure the directory exists or attempt to create it
    if (!create_directory_and_index_file($audio_dir_path)) {
        // Error handling, e.g., log the error or handle the failure appropriately
        return;
    }

    // Get the audio format option
    $audio_format = esc_attr(get_option('chatbot_chatgpt_audio_output_format', 'mp3'));

    // $audio_file_name = $session_id . '_' . time() . '.' . $audio_format;
    $audio_file_name = 'audio_' . generate_random_string() . '_' . gmdate('Y-m-d_H-i-s') . '.' . $audio_format;
    $audio_file = $audio_dir_path . $audio_file_name;

    // Generate the URL of the audio file
    // $audio_file_url = $plugins_url . '/' . $plugin_name . '/audio/' . $audio_file_name;
    $audio_file_url = plugins_url('audio/' . $audio_file_name, $chatbot_chatgpt_plugin_dir_path . 'chatbot-chatgpt');

    $audio_output = null;

    // Select the OpenAI Model
    // One of tts-1, tts-1-1106, tts-1-hd, tts-1-hd-1106
    if ( !empty($kchat_settings['model']) ) {
        $model = $kchat_settings['model'];
    } else {
        $model = esc_attr(get_option('chatbot_chatgpt_model_choice', 'tts-1-hd'));
    }

    // Get the audio voice transient if it exists - Ver 1.9.5
    if ( empty($voice) ) {
        $voice = get_chatbot_chatgpt_transients( 'voice', $user_id, $page_id, $session_id);
    }

    if ( !empty($voice) ) {
        $voice = $voice;
    } elseif ( !empty($kchat_settings['voice'])) {
        $voice = $kchat_settings['voice'];
    } else {
        // Get the voice option from the settings (default is alloy)
        $voice = esc_attr(get_option('chatbot_chatgpt_voice_option', 'alloy'));
    }

    // Belt and Suspender - Ver 1.9.5
    if ( empty($model) ) {
        $model = 'tts-1-hd';
        update_option( 'chatbot_chatgpt_model_choice', 'tts-1-hd');
    }
    if ( empty($voice) ) {
        $voice = 'alloy';
        update_option( 'chatbot_chatgpt_voice_option', 'alloy');
    }

    // Build conversation context using standardized function - Ver 2.3.9+
    // Note: TTS API doesn't use conversation history in the API call itself,
    // but we build context for consistency and to maintain conversation history tracking
    $conversation_context = chatbot_chatgpt_build_conversation_context('standard', 10, $session_id);

    // API URL for the TTS service
    $api_url = 'https://api.openai.com/v1/audio/speech';

    // OpenAI speech input ceiling. Read-aloud applies a lower cap before this call.
    $message = chatbot_chatgpt_tts_cap_text( $message, 4096 );

    // Creating the array to be converted to JSON
    $body = [
        'model'  => $model,
        'voice'  => $voice,
        'input'  => $message,
    ];

    // Encode the array to JSON
    $json_body = json_encode($body);

    // Set up HTTP request arguments
    $args = [
        'method'    => 'POST',
        'body'      => $json_body,
        'headers'   => [
            'Content-Type'  => 'application/json',
            'Authorization'  => 'Bearer ' . $api_key,
        ],
        'timeout'   => 30,
    ];

    // Send request using WP HTTP API
    $response = wp_remote_post($api_url, $args);

    // Check for WP errors
    if (is_wp_error($response)) {
        // Clear locks on error
        // Lock clearing removed - main send function handles locking
        return 'Error: ' . $response->get_error_message();
    }

    // Retrieve HTTP response code
    $http_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);

    // Check the HTTP response code
    if ($http_code !== 200) {
        // Clear locks on error
        // Lock clearing removed - main send function handles locking
        return 'Error: API responded with HTTP code ' . $http_code . ': ' . $response_body;
    }

    // Write the audio to a file
    $result = file_put_contents($audio_file, $response_body);

    if ($result === false) {
        // Clear locks on error
        // Lock clearing removed - main send function handles locking
        return 'Error: Failed to write the audio file.';
    }

    // Generate audio output
    $audio_output = "<div><center><audio autoplay><source src='" . esc_url($audio_file_url) . "' type='audio/mpeg'></audio></center></div>";
    $audio_output .= "[Listen](" . esc_url($audio_file_url) . ")";

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

        // Add the usage to the conversation tracker
        // append_message_to_conversation_log($session_id, $user_id, $page_id, 'Prompt Tokens', null, null, null, $response_body["usage"]["prompt_tokens"]);
        // append_message_to_conversation_log($session_id, $user_id, $page_id, 'Completion Tokens', null, null, null, $response_body["usage"]["completion_tokens"]);
        // append_message_to_conversation_log($session_id, $user_id, $page_id, 'Total Tokens', null, null, null, $response_body["usage"]["total_tokens"]);

        // FIXME - BEFORE RETURNING THE AUDIO FILE
        // FIXME - ADD TRANSIENT TO DELETE AUDIO FILE AFTER 2 HOURS
       
        // Set transient to delete the audio file after 2 hours
        chatbot_chatgpt_delete_audio_file_id( $audio_file_url );

        // Clear locks on success
        // Lock clearing removed - main send function handles locking
        return $audio_output;

    } else {

        // FIXME - Decide what to return here - it's an error
        if (get_locale() !== "en_US") {
            $localized_errorResponses = get_localized_errorResponses(get_locale(), $errorResponses);
        } else {
            $localized_errorResponses = $errorResponses;
        }
        // Clear locks on error
        // Lock clearing removed - main send function handles locking
        // Return a random error message
        return $localized_errorResponses[array_rand($localized_errorResponses)];

    }

}

/**
 * Cap speech input without splitting a multibyte character when possible.
 *
 * @param string $text Text to cap.
 * @param int    $max  Maximum characters.
 * @return string
 */
function chatbot_chatgpt_tts_cap_text( $text, $max ) {

    $text = (string) $text;
    $max  = (int) $max;
    if ( $max < 1 || $text === '' ) {
        return $text;
    }

    if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) ) {
        if ( mb_strlen( $text ) > $max ) {
            return mb_substr( $text, 0, $max );
        }
        return $text;
    }

    if ( strlen( $text ) > $max ) {
        return substr( $text, 0, $max );
    }

    return $text;

}

/**
 * Transient key for the last assistant reply this session may read aloud.
 *
 * @param string $session_id Chat session id.
 * @return string
 */
function chatbot_chatgpt_tts_last_reply_key( $session_id ) {

    $session_id = (string) $session_id;
    $hash       = function_exists( 'wp_fast_hash' ) ? wp_fast_hash( $session_id ) : hash( 'sha256', $session_id );

    return 'chatbot_chatgpt_tts_last_' . $hash;

}

/**
 * Remember a server-produced assistant reply for a later read-aloud request.
 * Client-supplied text is never stored here.
 *
 * @param string $session_id Chat session id.
 * @param mixed  $message    Assistant reply.
 * @return void
 */
function chatbot_chatgpt_remember_last_assistant_message( $session_id, $message ) {

    $session_id = is_string( $session_id ) ? sanitize_text_field( $session_id ) : '';
    if ( $session_id === '' || ! is_string( $message ) ) {
        return;
    }

    $trim = ltrim( $message );
    if ( str_starts_with( $trim, 'Error:' ) || str_starts_with( $trim, 'Failed:' ) ) {
        return;
    }

    $text = trim( wp_strip_all_tags( $message ) );
    if ( $text === '' ) {
        return;
    }

    set_transient( chatbot_chatgpt_tts_last_reply_key( $session_id ), chatbot_chatgpt_tts_cap_text( $text, 4096 ), 12 * HOUR_IN_SECONDS );

}

/**
 * Latest assistant text this session is allowed to spend speech credits on.
 *
 * @param string $session_id Chat session id.
 * @return string
 */
function chatbot_chatgpt_get_last_assistant_message_for_read_aloud( $session_id ) {

    $stored = get_transient( chatbot_chatgpt_tts_last_reply_key( $session_id ) );
    if ( is_string( $stored ) && trim( $stored ) !== '' ) {
        return $stored;
    }

    global $wpdb;

    $table = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';
    $found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
    if ( $found !== $table ) {
        return '';
    }

    $text = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT message_text FROM {$table} WHERE session_id = %s AND user_type = %s ORDER BY id DESC LIMIT 1",
            $session_id,
            'Chatbot'
        )
    );

    return is_string( $text ) ? $text : '';

}

// Call the Text-to-Speech API
function chatbot_chatgpt_read_aloud($message) {

    // Security: Verify nonce for CSRF protection
    if (!isset($_POST['chatbot_nonce']) || !wp_verify_nonce($_POST['chatbot_nonce'], 'chatbot_tts_nonce')) {
        wp_send_json_error('Security check failed. Please refresh the page and try again.', 403);
        return;
    }

    // The button is hidden when this is off. The endpoint must follow the same switch.
    $read_aloud_option = strtolower( sanitize_text_field( (string) get_option( 'chatbot_chatgpt_read_aloud_option', 'yes' ) ) );
    if ( $read_aloud_option !== 'yes' ) {
        wp_send_json_error( 'Read aloud is turned off.', 403 );
        return;
    }

    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;
    global $kchat_settings;
    global $additional_instructions;
    global $model;
    global $voice;

    $session_id = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : '';
    if ( ! function_exists( 'verify_session_ownership' ) || ! verify_session_ownership( $session_id ) ) {
        wp_send_json_error( 'Session ID required.', 403 );
        return;
    }

    // Speak the last server-produced reply for this session. Ignore posted text.
    $message = sanitize_textarea_field( wp_unslash( (string) chatbot_chatgpt_get_last_assistant_message_for_read_aloud( $session_id ) ) );
    $message = trim( $message );
    if ( $message === '' ) {
        wp_send_json_error( 'There is no chatbot reply to read aloud yet.', 403 );
        return;
    }
    $message = chatbot_chatgpt_tts_cap_text( $message, 2000 );

    // A few paid speech calls per 10 minutes. Administrators are not limited.
    if ( ! ( is_user_logged_in() && current_user_can( 'manage_options' ) ) ) {
        $client_ip      = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
        $rate_limit_key = 'chatbot_chatgpt_tts_rl_' . ( function_exists( 'wp_fast_hash' ) ? wp_fast_hash( $client_ip ) : hash( 'sha256', $client_ip ) );
        $current_count  = (int) get_transient( $rate_limit_key );
        if ( $current_count >= 5 ) {
            wp_send_json_error( 'Rate limit exceeded. Please wait before reading another message aloud.', 429 );
            return;
        }
        set_transient( $rate_limit_key, $current_count + 1, 10 * MINUTE_IN_SECONDS );
    }

    // FIXME - GET THE DEFAULT TEXT-TO-SPEECH API KEY
    $api_key = esc_attr(get_option('chatbot_chatgpt_api_key'));
    // Decrypt the API key - Ver 2.2.6
    $api_key = chatbot_chatgpt_decrypt_api_key($api_key);

    $user_id = isset( $_POST['user_id'] ) ? sanitize_text_field( wp_unslash( $_POST['user_id'] ) ) : '';
    $page_id = isset( $_POST['page_id'] ) ? sanitize_text_field( wp_unslash( $_POST['page_id'] ) ) : '';

    // FIXME - DON'T OVERRIDE THE MODEL

    // Hold the model
    // $t_model = get_chatbot_chatgpt_transients( 'model', $user_id, $page_id, $session_id);
    // if ( empty($t_model) ) {
    //     $t_model = esc_attr(get_option( 'chatbot_chatgpt_voice_model_option', 'tts-1-hd'));
    // }
    // $model = $t_model;
    // set_chatbot_chatgpt_transients( 'model', $model, $user_id, $page_id, $session_id);
    $kchat_settings['model'] = esc_attr(get_option( 'chatbot_chatgpt_voice_model_option', 'tts-1-hd'));

    // FIXME - READ ALOUD PASSED FROM ASSISTANT MANAGEMENT

    // Hold the voice
    $t_voice = get_chatbot_chatgpt_transients( 'voice', $user_id, $page_id, $session_id);
    if ( empty($t_voice) ) {
        $t_voice = esc_attr(get_option( 'chatbot_chatgpt_voice_option', 'alloy') );
    }
    $voice = $t_voice;
    set_chatbot_chatgpt_transients( 'voice', $voice, $user_id, $page_id, $session_id);
    $kchat_settings['voice'] = $voice;

    if ( empty($voice) ) {
        $voice = esc_attr(get_option( 'chatbot_chatgpt_voice_option', 'alloy') );
    }
    $kchat_settings['voice'] = $voice;

    // Call the Text-to-Speech API
    $response = chatbot_chatgpt_call_tts_api($api_key, $message, $voice);

    // Reset the model - REMOVED - Ver 2.0.6 - 2024 07 11
    // set_chatbot_chatgpt_transients( 'model', $t_model, $user_id, $page_id, $session_id);
    // $kchat_settings['model'] = $t_model;
    // set_chatbot_chatgpt_transients( 'voice', $t_voice, $user_id, $page_id, $session_id);
    // $kchat_settings['voice'] = $t_voice;

    // Return the response
    wp_send_json_success($response);

    // Don't forget to stop execution afterward.
    wp_die();

}
// Guests may use read aloud when the setting is on. Limits live in the handler. - Ver 2.4.9
add_action('wp_ajax_chatbot_chatgpt_read_aloud', 'chatbot_chatgpt_read_aloud');
add_action('wp_ajax_nopriv_chatbot_chatgpt_read_aloud', 'chatbot_chatgpt_read_aloud');

// Set up the cron job to delete the audio file after 2 hours
function chatbot_chatgpt_delete_audio_file_id( $file_id ) {

    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;

    // Set a transient that expires in 2 hours
    $timeFrameForDelete = time() + 2 * 60 * 60;
    set_transient('chatbot_chatgpt_delete_audio_file_' . $file_id, $file_id, $timeFrameForDelete);

    // Set a cron job to delete the file in 1 hour 45 minutes
    $shorterTimeFrameForDelete = time() + 1 * 60 * 60 + 45 * 60;
    if (!wp_next_scheduled('delete_audio_file', $file_id)) {
        wp_schedule_single_event($shorterTimeFrameForDelete, 'chatbot_chatgpt_delete_audio_file', array($file_id));
    }

}

// Cleanup in Aisle 4 on OpenAI - Ver 1.7.9
function deleteAudioFile($file_id) {

    global $chatbot_chatgpt_plugin_dir_path;

    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;

    // Generate directory path
    $audio_dir_path = $chatbot_chatgpt_plugin_dir_path . 'audio/';

    // Ensure the directory exists or attempt to create it
    if (!create_directory_and_index_file($audio_dir_path)) {
        // Error handling, e.g., log the error or handle the failure appropriately
        return;
    }

    // Strip off just the file name
    $file_id = basename($file_id);

    // Add the $audio_dir_path to the $file_id
    $file_id = $audio_dir_path . $file_id;

    // Check if the file exists
    if (!file_exists($file_id)) {
        return;
    }

    // Try to delete the file
    if (!wp_delete_file($file_id)) {
        return;
    }

}
add_action( 'chatbot_chatgpt_delete_audio_file', 'deleteAudioFile' );

// Delete old audio files - Ver 1.9.9
function chatbot_chatgpt_cleanup_audio_directory() {

    global $chatbot_chatgpt_plugin_dir_path;
    
    $audio_dir = $chatbot_chatgpt_plugin_dir_path . 'audio/';
    foreach (glob($audio_dir . '*') as $file) {
        // Delete files older than 1 hour
        if (filemtime($file) < time() - 60 * 60 * 1) {
            wp_delete_file($file);
        }
    }
    // Create the index.php file if it does not exist
    create_directory_and_index_file($audio_dir);
}
add_action('chatbot_chatgpt_cleanup_audio_files', 'chatbot_chatgpt_cleanup_audio_directory');
