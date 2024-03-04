<?php
/**
 * Kognetiks Chatbot for WordPress - Threads
 *
 * This file contains the code for managing the threads used
 * to display the Chatbot on the website.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Set the threads transient
function set_chatbot_chatgpt_threads($thread_id, $assistant_id, $user_id, $page_id): void {

    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;
    global $script_data_array;

    // DIAG - Diagnostics - Ver 1.8.6
    // back_trace( 'NOTICE', 'set_chatbot_chatgpt_threads');
    // back_trace( 'NOTICE', 'SET $user_id: ' . $user_id);
    // back_trace( 'NOTICE', 'SET $page_id: ' . $page_id);
    // back_trace( 'NOTICE', 'SET $session_id: ' . $session_id);
    // back_trace( 'NOTICE', 'SET $thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', 'SET $assistant_id: ' . $assistant_id);

    $script_data_array = array(
        'user_id' => $user_id,
        'page_id' => $page_id,
        'session_id' => $session_id,
        'thread_id' => $thread_id,
        'assistant_id' => $assistant_id
    );

    // Create unique keys for transients
    $thread_id_thread_key = 'chatbot_chatgpt_thread_id_' . $user_id . '_' . $page_id;
    $assistant_id_thread_key = 'chatbot_chatgpt_assistant_id_' . $user_id . '_' . $page_id;

    // Store the style and the assistant value with unique keys
    // Store transients for 1 day
    set_transient($thread_id_thread_key, $thread_id, 60*60*4); // Store for 4 hours
    set_transient($assistant_id_thread_key, $assistant_id, 60*60*4); // Store for 4 hours

}

// Get the threads
function get_chatbot_chatgpt_threads($user_id, $page_id) {

    // Declare global variables
    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;
    global $script_data_array;

    // DIAG - Diagnostics - Ver 1.8.6
    // back_trace( 'NOTICE', 'get_chatbot_chatgpt_thread');
    // back_trace( 'NOTICE', 'GET PART 1 $user_id: ' . $user_id);
    // back_trace( 'NOTICE', 'GET PART 1 $page_id: ' . $page_id);
    // back_trace( 'NOTICE', 'GET PART 1 $session_id: ' . $session_id);
    // back_trace( 'NOTICE', 'GET PART 1 $thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', 'GET PART 1 $assistant_id: ' . $assistant_id);

    $script_data_array = array(
        'user_id' => $user_id,
        'page_id' => $page_id,
        'session_id' => $session_id,
        'thread_id' => $thread_id,
        'assistant_id' => $assistant_id
    );
    
    // if $user_id is empty or zero then set it to $session_id
    if (empty($user_id) || $user_id == 0) {
        $user_id = $session_id;
    }

    // Construct the unique keys
    $thread_id_thread_key = 'chatbot_chatgpt_thread_id_' . $user_id . '_' . $page_id;
    $assistant_id_thread_key = 'chatbot_chatgpt_assistant_id_' . $user_id . '_' . $page_id;

    // Retrieve the stored values
    $thread_id = get_transient($thread_id_thread_key);
    if ($thread_id === false) {
        $thread_id = '';
    }
    
    $assistant_id = get_transient($assistant_id_thread_key);
    if ($assistant_id === false) {
        $assistant_id = '';
    }

    // Return the values, also handle the case where the transient might have expired
    return array(
        'thread_id' => $thread_id,
        'assistant_id' => $assistant_id
    );

}

// Delete the threads
function delete_chatbot_chatgpt_threads($user_id, $page_id) {

    // Declare global variables
    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;
    global $script_data_array;

    // DIAG - Diagnostics - Ver 1.8.6
    // back_trace( 'NOTICE', 'delete_chatbot_chatgpt_threads');
    // back_trace( 'NOTICE', 'DEL $user_id: ' . $user_id);
    // back_trace( 'NOTICE', 'DEL $page_id: ' . $page_id);
    // back_trace( 'NOTICE', 'DEL $session_id: ' . $session_id);
    // back_trace( 'NOTICE', 'DEL $thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', 'DEL $assistant_id: ' . $assistant_id);

    $script_data_array = array(
        'user_id' => $user_id,
        'page_id' => $page_id,
        'session_id' => $session_id,
        'thread_id' => $thread_id,
        'assistant_id' => $assistant_id
    );

    // Construct the unique keys
    $thread_id_thread_key = 'chatbot_chatgpt_thread_id_' . $user_id . '_' . $page_id;
    $assistant_id_thread_key = 'chatbot_chatgpt_assistant_id_' . $user_id . '_' . $page_id;

    // Delete the stored values
    delete_transient($thread_id_thread_key);
    delete_transient($assistant_id_thread_key);

    // DIAG - Diagnostics - Ver 1.8.6
    // back_trace( 'NOTICE', 'delete_chatbot_chatgpt_threads');
    // back_trace( 'NOTICE', '$user_id: ' . $user_id);
    // back_trace( 'NOTICE', '$page_id: ' . $page_id);
    // back_trace( 'NOTICE', '$session_id: ' . $session_id);
    // back_trace( 'NOTICE', '$thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', '$assistant_id: ' . $assistant_id);

    $script_data_array = array(
        'user_id' => $user_id,
        'page_id' => $page_id,
        'session_id' => $session_id,
        'thread_id' => $thread_id,
        'assistant_id' => $assistant_id
    );
    
}