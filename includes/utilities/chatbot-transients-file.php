<?php
/**
 * Kognetiks Chatbot - Transients - Files
 *
 * This file contains the code for managing the transients used
 * to display the Chatbot on the website.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Set the transients based on the type - Ver 1.8.1
function set_chatbot_chatgpt_transients_files( $transient_type, $transient_value, $session_id, $file_no ) {

    // global $session_id;
    // global $user_id;
    // global $page_id;
    // global $thread_id;
    // global $assistant_id;

    // DIAG - Diagnostics - Ver 1.9.2
    // back_trace( 'NOTICE', 'SET $transient_type: ' . $transient_type);
    // back_trace( 'NOTICE', 'SET $transient_value: ' . $transient_value);
    // back_trace( 'NOTICE', 'SET $file_no: ' . $file_no);

    // Set the transient based on the type
    if ($transient_type == 'chatbot_chatgpt_assistant_file_ids') {
        $transient_key = 'chatbot_chatgpt_assistant_file_ids_' . $session_id . '_' . $file_no;
    } elseif ($transient_type == 'chatbot_chatgpt_assistant_file_types') {
        $transient_key = 'chatbot_chatgpt_assistant_file_types_' . $session_id . '_' . $file_no;
    } else {
        $transient_key = 'chatbot_chatgpt_file_id_' . $session_id . '_' . $file_no;
    }

    // Store the transient
    set_transient($transient_key, $transient_value, 60*60*4); // Store for 4 hours

}

// Get the transients based on the type - Ver 1.8.1
function get_chatbot_chatgpt_transients_files( $transient_type, $session_id, $file_no ): string {
    
    // global $session_id;
    // global $user_id;
    // global $page_id;
    // global $thread_id;
    // global $assistant_id;

    // DIAG - Diagnostics - Ver 1.9.2
    // back_trace( 'NOTICE', 'GET $transient_type: ' . $transient_type);
    // back_trace( 'NOTICE', 'GET $session_id: ' . $session_id);
    // back_trace( 'NOTICE', 'GET $file_no: ' . $file_no);

    // Construct the transient key based on the transient type
    $transient_key = '';
    if ($transient_type == 'chatbot_chatgpt_assistant_file_ids') {
        $transient_key = 'chatbot_chatgpt_assistant_file_ids_' . $session_id . '_' . $file_no;
    } elseif ($transient_type == 'chatbot_chatgpt_assistant_file_types') {
        $transient_key = 'chatbot_chatgpt_assistant_file_types_' . $session_id . '_' . $file_no;
    } else {
        $transient_key = 'chatbot_chatgpt_file_id_' . $session_id . '_' . $file_no;
    }
    
    // Get the transient value
    $transient_value = get_transient($transient_key);

    // Return the transient value if it's found, or an empty string if not
    return $transient_value !== false ? $transient_value : '';
   
}

// Delete the transients - Ver 1.7.9
function delete_chatbot_chatgpt_transients_files( $transient_type, $session_id, $file_no ) {

    // FIXME - DECIDE - Should we delete the transients

    // global $session_id;
    // global $user_id;
    // global $page_id;
    // global $thread_id;
    // global $assistant_id;

    // DIAG - Diagnostics - Ver 1.9.2
    // back_trace( 'NOTICE', 'DEL $transient_type: ' . $transient_type);
    // back_trace( 'NOTICE', 'DEL $file_no: ' . $file_no);

    // Construct the transient key based on the transient type
    if ($transient_type == 'chatbot_chatgpt_assistant_file_ids') {
        $file_transient_key = 'chatbot_chatgpt_assistant_file_ids_' . $session_id . '_' . $file_no;
    } elseif ($transient_type == 'chatbot_chatgpt_assistant_file_types') {
        $file_transient_key = 'chatbot_chatgpt_assistant_file_types_' . $session_id . '_' . $file_no;
    } else {
        $file_transient_key = 'chatbot_chatgpt_file_id_' . $session_id . '_' . $file_no;
    }
    delete_transient($file_transient_key);

}

// Clean up old transient keys - Ver 2.3.5.2
function chatbot_chatgpt_cleanup_old_file_transients($session_id) {
    
    // Delete old format transients for this session
    for ($i = 0; $i < 10; $i++) {
        $old_key = 'chatbot_chatgpt_file_id_' . $session_id . '_' . $i;
        delete_transient($old_key);
    }
    
    // Also clean up any orphaned transients
    global $wpdb;
    $wpdb->query($wpdb->prepare("
        DELETE FROM {$wpdb->options} 
        WHERE option_name LIKE %s 
        AND option_name LIKE %s
    ", '_transient_chatbot_chatgpt_file_id_%', '%' . $session_id . '%'));
}
