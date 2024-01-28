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
function set_chatbot_chatgpt_threads($t_threadId, $t_assistantId, $user_id, $page_id): void {

    // Declare global variables
    global $sessionId;

    // DIAG - Diagnostics
    // chatbot_chatgpt_back_trace( 'NOTICE', '$t_threadId' . $t_threadId);
    // chatbot_chatgpt_back_trace( 'NOTICE', '$t_assistantId ' . $t_assistantId);

    // $user_id = get_current_user_id(); // Get current user ID
    // $page_id = get_the_ID(); // Get current page ID
    // if (empty($page_id)) {
    //     $page_id = get_queried_object_id(); // Get the ID of the queried object if $page_id is not set
    // }

    // if $user_id is empty or zero then set it to $sessionId
    if (empty($user_id) || $user_id == 0) {
        $user_id = $sessionId;
    }

    // DIAG - Diagnostics
    // error_log('put_chatbot_chatgpt_threads');
	// error_log('$user_id ' . $user_id);
	// error_log('$page_id ' . $page_id);
	// error_log('$t_threadId ' . $t_threadId);
	// error_log('$t_assistantId ' . $t_assistantId);

    // DIAG - Diagnostics
    // chatbot_chatgpt_back_trace( 'NOTICE', '$user_id ' . $user_id);
    // chatbot_chatgpt_back_trace( 'NOTICE', '$page_id ' . $page_id);

    // Create unique keys for transients
    $thread_Id_thread_key = 'chatbot_chatgpt_threadId_' . $user_id . '_' . $page_id;
    $assistantId_thread_key = 'chatbot_chatgpt_assistantId_' . $user_id . '_' . $page_id;

    // Store the style and the assistant value with unique keys
    // Store transients for 1 day
    set_transient($thread_Id_thread_key, $t_threadId, 60*60*24); // Store for 1 hour
    set_transient($assistantId_thread_key, $t_assistantId, 60*60*24); // Store for 1 hour

}

// Get the transient - example usage
// $chatbot_settings = get_chatbot_chatgpt_transients();
// $display_style = $chatbot_settings['display_style'];
// $assistant_alias = $chatbot_settings['assistant_alias'];

// Get the threads
function get_chatbot_chatgpt_threads($user_id, $page_id) {

    // Declare global variables
    global $sessionId;

    // Pass the $user_id and $page_id values from the shortcode
    // $user_id = get_current_user_id(); // Get current user ID
    // $page_id = get_the_ID(); // Get current page ID

    // DIAG - Diagnostics
    // chatbot_chatgpt_back_trace( 'NOTICE', '$user_id ' . $user_id);
    // chatbot_chatgpt_back_trace( 'NOTICE', '$page_id ' . $page_id);

    // if $user_id is empty or zero then set it to $sessionId
    if (empty($user_id) || $user_id == 0) {
        $user_id = $sessionId;
    }

    // Construct the unique keys
    $thread_Id_thread_key = 'chatbot_chatgpt_threadId_' . $user_id . '_' . $page_id;
    $assistantId_thread_key = 'chatbot_chatgpt_assistantId_' . $user_id . '_' . $page_id;

    // Retrieve the stored values
    $t_threadId = get_transient($thread_Id_thread_key);
    if ($t_threadId === false) {
        $t_threadId = '';
    }
    
    $t_assistantId = get_transient($assistantId_thread_key);
    if ($t_assistantId === false) {
        $t_assistantId = '';
    }

    // DIAG - Diagnostics
	// error_log('get_chatbot_chatgpt_threads');
	// error_log('$user_id ' . $user_id);
	// error_log('$page_id ' . $page_id);
	// error_log('$t_threadId ' . $t_threadId);
	// error_log('$t_assistantId ' . $t_assistantId);

    // DIAG - Diagnostics
    // chatbot_chatgpt_back_trace( 'NOTICE', '$t_threadId ' . $t_threadId);
    // chatbot_chatgpt_back_trace( 'NOTICE', '$t_assistantId ' . $t_assistantId);

    // Return the values, also handle the case where the transient might have expired
    return array(
        'threadID' => $t_threadId,
        'assistantID' => $t_assistantId
    );

}
