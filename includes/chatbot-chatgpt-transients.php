<?php
/**
 * Chatbot ChatGPT for WordPress - Transients
 *
 * This file contains the code for managing the transients used
 * to display the Chatbot ChatGPT on the website.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Set the transients based on the type - Ver 1.8.1
function set_chatbot_chatgpt_transients( $transient_type , $transient_value, $user_id = null, $page_id = null) {

    global $session_id;

    // Use session_id if user is not logged in
    if ( empty($user_id) || !is_user_logged_in() ) {
        $user_id = isset($session_id) ? $session_id : 'session_' . session_id(); // Use global session_id or generate a new one
        $page_id = $page_id ?: get_the_ID(); // Attempt to get the current page ID again
        if ( empty($page_id) || $page_id == 0 ) { // Check if page_id is still not set or is zero
            $page_id = 'global'; // Use a default placeholder if no specific page ID is found
        }
    } else {
        // For logged-in users, get the current user and page IDs as before
        $user_id = get_current_user_id(); // Get current user ID
        $page_id = $page_id ?: get_the_ID(); // Use provided page_id or get current page ID
        if ( empty($page_id) || $page_id == 0 ) { // Check if page_id is not set or is zero
            $page_id = get_queried_object_id(); // Try to get the ID of the queried object
        }
    }

    // Set the transient based on the type
    if ( $transient_type == 'display_style' ) {
        $transient_key = 'chatbot_chatgpt_style_';
    } elseif ( $transient_type == 'assistant_alias' ) {
        $transient_key = 'chatbot_chatgpt_assistant_';
    } elseif ( $transient_type == 'file_id' ) {
        $transient_key = 'chatbot_chatgpt_file_id_';
    } elseif ( $transient_type == 'chatbot_chatgpt_assistant_file_id') {
        $transient_key = 'chatbot_chatgpt_assistant_file_id_';
    };

    $transient_key = $transient_key . $user_id . '_' . $page_id;
    set_transient($transient_key, $transient_value, 60*60); // Store for 1 hour

    // DIAG - Diagnostics
    chatbot_chatgpt_back_trace( 'NOTICE', 'Transient set - Begin');
    chatbot_chatgpt_back_trace( 'NOTICE', '$user_id ' . $user_id);
    chatbot_chatgpt_back_trace( 'NOTICE', '$page_id ' . $page_id);
    chatbot_chatgpt_back_trace( 'NOTICE', '$transient_type ' . $transient_type);
    chatbot_chatgpt_back_trace( 'NOTICE', '$transient_value ' . $transient_value);
    chatbot_chatgpt_back_trace( 'NOTICE', 'Transient set - End');

}

// Get the transients based on the type - Ver 1.8.1
function get_chatbot_chatgpt_transients( $transient_type, $user_id = null, $page_id = null ): string {
    global $session_id; // Assume this global variable is defined elsewhere to track session ID

    // Check if user is logged in or not and adjust user_id and page_id accordingly
    if ( empty($user_id) || !is_user_logged_in() ) {
        $user_id = isset($session_id) ? $session_id : 'session_' . session_id(); // Use global session_id or generate a new one
        $page_id = $page_id ?: get_the_ID(); // Attempt to get the current page ID again
        if ( empty($page_id) || $page_id == 0 ) { // Check if page_id is still not set or is zero
            $page_id = 'global'; // Use a default placeholder if no specific page ID is found
        }
    } else {
        // For logged-in users, attempt to get the user and page IDs as before
        $user_id = get_current_user_id(); // Ensure user ID is set for logged-in users
        $page_id = $page_id ?: get_the_ID(); // Use provided page_id or try to get the current page ID
        if ( empty($page_id) || $page_id == 0 ) { // If page_id is not set or is zero
            $page_id = get_queried_object_id(); // Try to get the ID of the queried object
        }
    }

    // DIAG - Diagnostics
    chatbot_chatgpt_back_trace( 'NOTICE', 'Transient set - Begin');
    chatbot_chatgpt_back_trace( 'NOTICE', '$user_id ' . $user_id);
    chatbot_chatgpt_back_trace( 'NOTICE', '$page_id ' . $page_id);
    chatbot_chatgpt_back_trace( 'NOTICE', '$transient_type ' . $transient_type);

    // Construct the transient key based on the transient type and the resolved user and page IDs
    $transient_key = '';
    switch ($transient_type) {
        case 'display_style':
            $transient_key = 'chatbot_chatgpt_style_';
            break;
        case 'assistant_alias':
            $transient_key = 'chatbot_chatgpt_assistant_';
            break;
        case 'file_id':
            $transient_key = 'chatbot_chatgpt_file_id_';
            break;
        case 'chatbot_chatgpt_assistant_file_id':
            $transient_key = 'chatbot_chatgpt_assistant_file_id_';
            break;
        default:
            return ''; // Return an empty string if an unsupported transient type is provided
    }

    $transient_key .= $user_id . '_' . $page_id;
    $transient_value = get_transient($transient_key); // Retrieve the transient value

    // DIAG - Diagnostics
    chatbot_chatgpt_back_trace( 'NOTICE', '$transient_key ' . $transient_key);
    chatbot_chatgpt_back_trace( 'NOTICE', '$transient_value ' . $transient_value);
    chatbot_chatgpt_back_trace( 'NOTICE', 'Transient get - End');

    // Return the transient value if it's found, or an empty string if not
    return $transient_value !== false ? $transient_value : '';
}


// Delete the transients - Ver 1.7.9
function delete_chatbot_chatgpt_transients( $transient_type, $user_id, $page_id): void {

    // DIAG - Diagnostics
    chatbot_chatgpt_back_trace( 'NOTICE', '$user_id ' . $user_id);
    chatbot_chatgpt_back_trace( 'NOTICE', '$page_id ' . $page_id);
    chatbot_chatgpt_back_trace( 'NOTICE', '$transient_type ' . $transient_type);
    // FIXME - DECIDE - Should we delete the transients
    return;

    if ( $transient_type == 'display_style' ) {
        $style_transient_key = 'chatbot_chatgpt_style_' . $user_id . '_' . $page_id;
        delete_transient($style_transient_key);
    } elseif ( $transient_type == 'assistant_alias' ) {
        $assistant_transient_key = 'chatbot_chatgpt_assistant_id_' . $user_id . '_' . $page_id;
        delete_transient($assistant_transient_key);
    } elseif ( $transient_type == 'chatbot_chatgpt_file_id' ) {
        $file_transient_key = 'chatbot_chatgpt_file_id_' . $user_id . '_' . $page_id;
        delete_transient($file_transient_key);
    } elseif ( $transient_type == 'chatbot_chatgpt_assistant_file_id') {
        $asst_file_transient_key = 'chatbot_chatgpt_assistant_file_id_' . $user_id . '_' . $page_id;
        delete_transient($asst_file_transient_key);
    }

}
