<?php
/**
 * AI Powered Chatbot for WordPress - Transients
 *
 * This file contains the code for managing the transients used
 * to display the Chatbot on the website.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Set the transients based on the type - Ver 1.8.1
function set_chatbot_chatgpt_transients( $transient_type , $transient_value , $user_id = null, $page_id = null, $session_id = null, $thread_id = null): void {

    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;

    // DIAG - Diagnostics - Ver 1.8.6
    // chatbot_chatgpt_back_trace( 'NOTICE', '$user_id: ' . $user_id);
    // chatbot_chatgpt_back_trace( 'NOTICE', '$page_id: ' . $page_id);
    // chatbot_chatgpt_back_trace( 'NOTICE', '$session_id: ' . $session_id);
    // chatbot_chatgpt_back_trace( 'NOTICE', '$thread_id: ' . $thread_id);
    // chatbot_chatgpt_back_trace( 'NOTICE', '$assistant_id: ' . $assistant_id);

    if (empty($user_id)) {
        $user_id = get_current_user_id(); // Get current user ID
    
        if (0 === $user_id) { // If user is not logged in, get_current_user_id() will return 0
            $user_id = $session_id; // Use session ID instead
        }
    }
    if (empty($page_id)) {
        $page_id = get_the_id(); // Get current page ID
        if (empty($page_id)) {
            $page_id = get_queried_object_id(); // Get the ID of the queried object if $page_id is not set
        }
    }

    // Set the transient based on the type
    if ( $transient_type == 'display_style' ) {
        $transient_key = 'chatbot_chatgpt_style_' . $page_id . '_' . $user_id;
    } elseif (  $transient_type== 'assistant_alias' ) {
        $transient_key = 'chatbot_chatgpt_assistant_' . $page_id . '_' . $user_id;
    } elseif ( $transient_type == 'file_id' ) {
        $transient_key = 'chatbot_chatgpt_file_id_' . $session_id;
    } elseif ( $transient_type == 'chatbot_chatgpt_assistant_file_id') {
        $transient_key = 'chatbot_chatgpt_assistant_file_id_' . $session_id;
    };

    // Store the transient
    set_transient($transient_key, $transient_value, 60*60*4); // Store for 4 hours

    // DIAG - Diagnostics
    // chatbot_chatgpt_back_trace( 'NOTICE', 'Transient SET - Begin');
    // chatbot_chatgpt_back_trace( 'NOTICE', '$transient_type ' . $transient_type);
    // chatbot_chatgpt_back_trace( 'NOTICE', '$user_id ' . $user_id);
    // chatbot_chatgpt_back_trace( 'NOTICE', '$page_id ' . $page_id);
    // chatbot_chatgpt_back_trace( 'NOTICE', '$session_id ' . $session_id);
    // chatbot_chatgpt_back_trace( 'NOTICE', '$transient_value ' . $transient_value);
    // chatbot_chatgpt_back_trace( 'NOTICE', 'Transient SET - End');

}

// Get the transients based on the type - Ver 1.8.1
function get_chatbot_chatgpt_transients( $transient_type, $user_id = null, $page_id = null, $session_id = null ): string {
    
    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;

    // DIAG - Diagnostics - Ver 1.8.6
    // chatbot_chatgpt_back_trace( 'NOTICE', '$user_id: ' . $user_id);
    // chatbot_chatgpt_back_trace( 'NOTICE', '$page_id: ' . $page_id);
    // chatbot_chatgpt_back_trace( 'NOTICE', '$session_id: ' . $session_id);
    // chatbot_chatgpt_back_trace( 'NOTICE', '$thread_id: ' . $thread_id);
    // chatbot_chatgpt_back_trace( 'NOTICE', '$assistant_id: ' . $assistant_id);

    if (empty($user_id)) {
        $user_id = get_current_user_id(); // Get current user ID
    
        if (0 === $user_id) { // If user is not logged in, get_current_user_id() will return 0
            $user_id = $session_id; // Use session ID instead
        }
    }
    if (empty($page_id)) {
        $page_id = get_the_id(); // Get current page ID
        if (empty($page_id)) {
            $page_id = get_queried_object_id(); // Get the ID of the queried object if $page_id is not set
        }
    }

    // DIAG - Diagnostics
    // chatbot_chatgpt_back_trace( 'NOTICE', 'Transient GET - Begin');
    // chatbot_chatgpt_back_trace( 'NOTICE', '$transient_type ' . $transient_type);
    if ($transient_type == 'file_id' || $transient_type == 'chatbot_chatgpt_assistant_file_id') {
        // chatbot_chatgpt_back_trace( 'NOTICE', '$session_id ' . $session_id);
    } else {
        // chatbot_chatgpt_back_trace( 'NOTICE', '$user_id ' . $user_id);
        // chatbot_chatgpt_back_trace( 'NOTICE', '$page_id ' . $page_id);
    }

    // Construct the transient key based on the transient type
    $transient_key = '';
    if ($transient_type == 'display_style') {
        $transient_key = 'chatbot_chatgpt_style_' . $page_id . '_' . $user_id;
    } elseif ($transient_type == 'assistant_alias') {
        $transient_key = 'chatbot_chatgpt_assistant_' . $page_id . '_' . $user_id;
    } elseif ($transient_type == 'file_id') {
        $transient_key = 'chatbot_chatgpt_file_id_'. $session_id;
    } elseif ($transient_type == 'chatbot_chatgpt_assistant_file_id') {
        $transient_key = 'chatbot_chatgpt_assistant_file_id_' . $session_id;
    }
    
    // Get the transient value
    $transient_value = get_transient($transient_key);

    // DIAG - Diagnostics
    // chatbot_chatgpt_back_trace( 'NOTICE', '$transient_value ' . $transient_value);
    // chatbot_chatgpt_back_trace( 'NOTICE', 'Transient GET - End');

    // Return the transient value if it's found, or an empty string if not
    return $transient_value !== false ? $transient_value : '';
   
}



// Delete the transients - Ver 1.7.9
function delete_chatbot_chatgpt_transients( $transient_type, $user_id = null, $page_id = null, $session_id = null): void {

    // FIXME - DECIDE - Should we delete the transients
    return;

    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;

    // DIAG - Diagnostics - Ver 1.8.6
    // chatbot_chatgpt_back_trace( 'NOTICE', '$user_id: ' . $user_id);
    // chatbot_chatgpt_back_trace( 'NOTICE', '$page_id: ' . $page_id);
    // chatbot_chatgpt_back_trace( 'NOTICE', '$session_id: ' . $session_id);
    // chatbot_chatgpt_back_trace( 'NOTICE', '$thread_id: ' . $thread_id);
    // chatbot_chatgpt_back_trace( 'NOTICE', '$assistant_id: ' . $assistant_id);

    if (empty($user_id)) {
        $user_id = get_current_user_id(); // Get current user ID
    
        if (0 === $user_id) { // If user is not logged in, get_current_user_id() will return 0
            $user_id = $session_id; // Use session ID instead
        }
    }
    if (empty($page_id)) {
        $page_id = get_the_id(); // Get current page ID
        if (empty($page_id)) {
            $page_id = get_queried_object_id(); // Get the ID of the queried object if $page_id is not set
        }
    }

    // DIAG - Diagnostics
    // chatbot_chatgpt_back_trace( 'NOTICE', 'Transient DELETE - BEGIN');
    // chatbot_chatgpt_back_trace( 'NOTICE', '$transient_type ' . $transient_type);
    // chatbot_chatgpt_back_trace( 'NOTICE', '$user_id ' . $user_id);
    // chatbot_chatgpt_back_trace( 'NOTICE', '$page_id ' . $page_id);
    // chatbot_chatgpt_back_trace( 'NOTICE', '$session_id ' . $session_id);
    // chatbot_chatgpt_back_trace( 'NOTICE', 'Transient DELETE - END');

    if ( $transient_type == 'display_style' ) {
        $style_transient_key = 'chatbot_chatgpt_style_' . $user_id . '_' . $page_id;
        delete_transient($style_transient_key);
    } elseif ( $transient_type == 'assistant_alias' ) {
        $assistant_transient_key = 'chatbot_chatgpt_assistant_id_' . $user_id . '_' . $page_id;
        delete_transient($assistant_transient_key);
    } elseif ( $transient_type == 'chatbot_chatgpt_file_id' ) {
        $file_transient_key = 'chatbot_chatgpt_file_id_' . $session_id;
        delete_transient($file_transient_key);
    } elseif ( $transient_type == 'chatbot_chatgpt_assistant_file_id') {
        $asst_file_transient_key = 'chatbot_chatgpt_assistant_file_id_' . $session_id;
        delete_transient($asst_file_transient_key);
    }

}
