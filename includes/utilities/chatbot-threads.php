<?php
/**
 * Kognetiks Chatbot - Threads
 *
 * This file contains the code for managing the threads used
 * to display the Chatbot on the website.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Set the threads transient
function set_chatbot_chatgpt_threads($thread_id, $assistant_id, $user_id, $page_id) {

    // Create unique keys for transients
    $thread_id_thread_key = 'chatbot_chatgpt_thread_id_' . $user_id . '_' . $page_id;
    $assistant_id_thread_key = 'chatbot_chatgpt_assistant_id_' . $user_id . '_' . $page_id;

    // Retrieve the chatbot_chatgpt_thread_retention_period option
    $thread_retention_period = (int) esc_attr(get_option('chatbot_chatgpt_thread_retention_period', 36));

    // Store the style and the assistant value with unique keys
    // Store transients for 1 day 12 hours (60 seconds * 60 minutes * 36 hours)
    set_transient($thread_id_thread_key, $thread_id, 60*60*$thread_retention_period); // Store for 36 hours
    set_transient($assistant_id_thread_key, $assistant_id, 60*60*$thread_retention_period); // Store for 36 hours

}

// Get the threads
function get_chatbot_chatgpt_threads($user_id, $session_id, $page_id, $assistant_id) {

    // Construct the unique keys
    $thread_id_thread_key = 'chatbot_chatgpt_thread_id_' . $user_id . '_' . $page_id;
    $assistant_id_thread_key = 'chatbot_chatgpt_assistant_id_' . $user_id . '_' . $page_id;

    // Retrieve the stored values
    $thread_id = get_transient($thread_id_thread_key);
    if ($thread_id === false) {
        $thread_id = '';
    } else {
    }
    
    $assistant_id = get_transient($assistant_id_thread_key);
    if ($assistant_id === false) {
        $assistant_id = '';
    } else {
    }

    // Initialize $kchat_settings if it is null
    // if (!is_array($kchat_settings)) {
    //     $kchat_settings = [];
    // }

    // Return the values, also handle the case where the transient might have expired
    // return array(
    //     'thread_id' => $thread_id,
    //     'assistant_id' => $assistant_id
    // );
    return $thread_id;

}

// Delete the threads
function delete_chatbot_chatgpt_threads($user_id, $page_id) {

    // Construct the unique keys
    $thread_id_thread_key = 'chatbot_chatgpt_thread_id_' . $user_id . '_' . $page_id;
    $assistant_id_thread_key = 'chatbot_chatgpt_assistant_id_' . $user_id . '_' . $page_id;

    // Delete the stored values
    delete_transient($thread_id_thread_key);
    delete_transient($assistant_id_thread_key);
    
}
