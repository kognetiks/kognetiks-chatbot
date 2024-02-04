<?php
/**
 * Chatbot ChatGPT for WordPress - Threads
 *
 * This file contains the code for managing the threads used
 * to display the Chatbot ChatGPT on the website.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Set the threads transient
function set_chatbot_chatgpt_threads($t_thread_id, $t_assistant_id, $user_id, $page_id): void {

    // Declare global variables
    global $session_id;

    // DIAG - Diagnostics
    // chatbot_chatgpt_back_trace( 'NOTICE', '$t_thread_id' . $t_thread_id);
    // chatbot_chatgpt_back_trace( 'NOTICE', '$t_assistant_id ' . $t_assistant_id);

    // $user_id = get_current_user_id(); // Get current user ID
    // $page_id = get_the_id(); // Get current page ID
    // if (empty($page_id)) {
    //     $page_id = get_queried_object_id(); // Get the ID of the queried object if $page_id is not set
    // }

    // if $user_id is empty or zero then set it to $session_id
    if (empty($user_id) || $user_id == 0) {
        $user_id = $session_id;
    }

    // DIAG - Diagnostics
    // error_log('put_chatbot_chatgpt_threads');
	// error_log('$user_id ' . $user_id);
	// error_log('$page_id ' . $page_id);
	// error_log('$t_thread_id ' . $t_thread_id);
	// error_log('$t_assistant_id ' . $t_assistant_id);

    // DIAG - Diagnostics
    // chatbot_chatgpt_back_trace( 'NOTICE', '$user_id ' . $user_id);
    // chatbot_chatgpt_back_trace( 'NOTICE', '$page_id ' . $page_id);

    // Create unique keys for transients
    $thread_id_thread_key = 'chatbot_chatgpt_thread_id_' . $user_id . '_' . $page_id;
    $assistant_id_thread_key = 'chatbot_chatgpt_assistant_id_' . $user_id . '_' . $page_id;

    // Store the style and the assistant value with unique keys
    // Store transients for 1 day
    set_transient($thread_id_thread_key, $t_thread_id, 60*60*24); // Store for 1 hour
    set_transient($assistant_id_thread_key, $t_assistant_id, 60*60*24); // Store for 1 hour

}

// Get the threads
function get_chatbot_chatgpt_threads($user_id, $page_id) {

    // Declare global variables
    global $session_id;

    // Pass the $user_id and $page_id values from the shortcode
    // $user_id = get_current_user_id(); // Get current user ID
    // $page_id = get_the_id(); // Get current page ID

    // DIAG - Diagnostics
    // chatbot_chatgpt_back_trace( 'NOTICE', '$user_id ' . $user_id);
    // chatbot_chatgpt_back_trace( 'NOTICE', '$page_id ' . $page_id);

    // if $user_id is empty or zero then set it to $session_id
    if (empty($user_id) || $user_id == 0) {
        $user_id = $session_id;
    }

    // Construct the unique keys
    $thread_id_thread_key = 'chatbot_chatgpt_thread_id_' . $user_id . '_' . $page_id;
    $assistant_id_thread_key = 'chatbot_chatgpt_assistant_id_' . $user_id . '_' . $page_id;

    // Retrieve the stored values
    $t_thread_id = get_transient($thread_id_thread_key);
    if ($t_thread_id === false) {
        $t_thread_id = '';
    }
    
    $t_assistant_id = get_transient($assistant_id_thread_key);
    if ($t_assistant_id === false) {
        $t_assistant_id = '';
    }

    // DIAG - Diagnostics
	// error_log('get_chatbot_chatgpt_threads');
	// error_log('$user_id ' . $user_id);
	// error_log('$page_id ' . $page_id);
	// error_log('$t_thread_id ' . $t_thread_id);
	// error_log('$t_assistant_id ' . $t_assistant_id);

    // DIAG - Diagnostics
    // chatbot_chatgpt_back_trace( 'NOTICE', '$t_thread_id ' . $t_thread_id);
    // chatbot_chatgpt_back_trace( 'NOTICE', '$t_assistant_id ' . $t_assistant_id);

    // Return the values, also handle the case where the transient might have expired
    return array(
        'thread_id' => $t_thread_id,
        'assistant_id' => $t_assistant_id
    );

}
