<?php
/**
 * Kognetiks Chatbot for WordPress - Transients
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
function set_chatbot_chatgpt_transients( $transient_type , $transient_value , $user_id = null, $page_id = null, $session_id = null, $thread_id = null, $sequence_id = null, $step_id = null) {

    // Check if the user ID and page ID are set
    if (0 === $user_id || empty($user_id)) {
        $user_id = $session_id;
    }

    // check if the page ID is set
    if (0 === $page_id || empty($page_id)) {
        $page_id = get_the_id(); // Get current page ID
        if (empty($page_id)) {
            $page_id = get_queried_object_id();
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
    } elseif ( $transient_type == 'model' ) {
        $transient_key = 'chatbot_chatgpt_model_' . $page_id . '_' . $user_id;
    } elseif ( $transient_type == 'voice') {
        $transient_key = 'chatbot_chatgpt_voice_' . $page_id . '_' . $user_id;
    } elseif ( $transient_type == 'kflow_sequence' ) {
        $transient_key = 'chatbot_chatgpt_kflow_sequence_' . $session_id;
    } elseif ( $transient_type == 'kflow_step' ) {
        $transient_key = 'chatbot_chatgpt_kflow_step_' . $session_id;
    } elseif ( $transient_type == 'assistant_name' ) {
        $transient_key = 'chatbot_chatgpt_assistant_name_' . $page_id . '_' . $user_id;
    } elseif ( $transient_type == 'assistant_id' ) {
        $transient_key = 'chatbot_chatgpt_assistant_id_' . $page_id . '_' . $user_id;
    } elseif ( $transient_type == 'thread_id' ) {
        $transient_key = 'chatbot_chatgpt_thread_id_' . $page_id . '_' . $user_id;
    } elseif ( $transient_type == 'additional_instructions' ) {
        $transient_key = 'chatbot_chatgpt_additional_instructions_' . $page_id . '_' . $user_id;
    } else {
        return;
    }

    // Store the transient
    set_transient($transient_key, $transient_value, 60*60*24); // Store for 24 hours

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', '========================================');
    // back_trace( 'NOTICE', 'PUT - $transient_type: ' . $transient_type);
    // back_trace( 'NOTICE', 'PUT - $transient_value: ' . $transient_value);
    // back_trace( 'NOTICE', 'PUT - $user_id: ' . $user_id);
    // back_trace( 'NOTICE', 'PUT - $page_id: ' . $page_id);
    // back_trace( 'NOTICE', 'PUT - $session_id: ' . $session_id);

}

// Get the transients based on the type - Ver 1.8.1
function get_chatbot_chatgpt_transients( $transient_type, $user_id = null, $page_id = null, $session_id = null ): string {
    
    // Check if the user ID and page ID are set
    if (0 === $user_id || empty($user_id)) {
        $user_id = $session_id;
    }

    // check if the page ID is set
    if (0 === $page_id || empty($page_id)) {
        $page_id = get_the_id(); // Get current page ID
        if (empty($page_id)) {
            $page_id = get_queried_object_id();
        }
    }

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', '$transient_type ' . $transient_type);
    if ($transient_type == 'file_id' || $transient_type == 'chatbot_chatgpt_assistant_file_id') {
        // back_trace( 'NOTICE', '$session_id ' . $session_id);
    } else {
        // back_trace( 'NOTICE', '$user_id ' . $user_id);
        // back_trace( 'NOTICE', '$page_id ' . $page_id);
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
    } elseif ($transient_type == 'model') {
        $transient_key = 'chatbot_chatgpt_model_' . $page_id . '_' . $user_id;
    } elseif ($transient_type == 'voice') {
        $transient_key = 'chatbot_chatgpt_voice_' . $page_id . '_' . $user_id;
    } elseif ($transient_type == 'kflow_sequence') {
        $transient_key = 'chatbot_chatgpt_kflow_sequence_' . $session_id;
    } elseif ($transient_type == 'kflow_step') {
        $transient_key = 'chatbot_chatgpt_kflow_step_' . $session_id;
    } elseif ($transient_type == 'assistant_name') {
        $transient_key = 'chatbot_chatgpt_assistant_name_' . $page_id . '_' . $user_id;
    } elseif ($transient_type == 'assistant_id') {
        $transient_key = 'chatbot_chatgpt_assistant_id_' . $page_id . '_' . $user_id;
    } elseif ($transient_type == 'thread_id') {
        $transient_key = 'chatbot_chatgpt_thread_id_' . $page_id . '_' . $user_id;
    } elseif ($transient_type == 'additional_instructions') {
        $transient_key = 'chatbot_chatgpt_additional_instructions_' . $page_id . '_' . $user_id;
    } else {
        return '';
    }
    
    // Get the transient value
    $transient_value = get_transient($transient_key);

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', '========================================');
    // back_trace( 'NOTICE', 'GET - $transient_value: ' . $transient_value);
    // back_trace( 'NOTICE', 'GET - $transient_type: ' . $transient_type);
    // back_trace( 'NOTICE', 'GET - $user_id: ' . $user_id);
    // back_trace( 'NOTICE', 'GET - $page_id: ' . $page_id);
    // back_trace( 'NOTICE', 'GET - $session_id: ' . $session_id);

    // Return the transient value if it's found, or an empty string if not
    // return $transient_value !== false ? $transient_value : '';

    // Get the transient value
    $transient_value = get_transient($transient_key);

    // Ensure the return value is a string
    return is_string($transient_value) ? $transient_value : '';
   
}

// Delete the transients - Ver 1.7.9
function delete_chatbot_chatgpt_transients( $transient_type, $user_id = null, $page_id = null, $session_id = null ) {

    // FIXME - DECIDE - Should we delete the transients

    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;
    global $sequence_id;
    global $step_id;

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', '========================================');
    // back_trace( 'NOTICE', 'DEL - $transient_value: ' . $transient_value);
    // back_trace( 'NOTICE', 'DEL - $transient_type: ' . $transient_type);
    // back_trace( 'NOTICE', 'DEL - $user_id: ' . $user_id);
    // back_trace( 'NOTICE', 'DEL - $page_id: ' . $page_id);
    // back_trace( 'NOTICE', 'DEL - $session_id: ' . $session_id);

    // Check if the user ID and page ID are set
    if (0 === $user_id || empty($user_id)) {
        $user_id = $session_id;
    }

    // check if the page ID is set
    if (0 === $page_id || empty($page_id)) {
        $page_id = get_the_id(); // Get current page ID
        if (empty($page_id)) {
            $page_id = get_queried_object_id();
        }
    }

    if ( $transient_type == 'display_style' ) {
        $style_transient_key = 'chatbot_chatgpt_style_' . $user_id . '_' . $page_id;
        delete_transient($style_transient_key);
    } elseif ( $transient_type == 'assistant_alias' ) {
        $assistant_transient_key = 'chatbot_chatgpt_assistant_id_' . $user_id . '_' . $page_id;
        delete_transient($assistant_transient_key);
    } elseif ( $transient_type == 'chatbot_chatgpt_file_id' ) {
        $file_transient_key = 'chatbot_chatgpt_file_id_' . $session_id . '_' . $file_no;
        delete_transient($file_transient_key);
    } elseif ( $transient_type == 'chatbot_chatgpt_assistant_file_id') {
        $asst_file_transient_key = 'chatbot_chatgpt_assistant_file_id_' . $session_id;
        delete_transient($asst_file_transient_key);
    } elseif ( $transient_type == 'model' ) {
        $model_transient_key = 'chatbot_chatgpt_model_' . $user_id . '_' . $page_id;
        delete_transient($model_transient_key);
    } elseif ( $transient_type == 'voice' ) {
        $voice_transient_key = 'chatbot_chatgpt_voice_' . $user_id . '_' . $page_id;
        delete_transient($voice_transient_key);
    } elseif ( $transient_type == 'kflow_sequence' ) {
        $kflow_sequence_transient_key = 'chatbot_chatgpt_kflow_sequence_' . $session_id;
        delete_transient($kflow_sequence_transient_key);
    } elseif ( $transient_type == 'kflow_step' ) {
        $kflow_step_transient_key = 'chatbot_chatgpt_kflow_step_' . $session_id;
        delete_transient($kflow_step_transient_key);
    } elseif ( $transient_type == 'assistant_name' ) {
        $assistant_name_transient_key = 'chatbot_chatgpt_assistant_name_' . $user_id . '_' . $page_id;
        delete_transient($assistant_name_transient_key);
    } elseif ( $transient_type == 'assistant_id' ) {
        $assistant_id_transient_key = 'chatbot_chatgpt_assistant_id_' . $user_id . '_' . $page_id;
        delete_transient($assistant_id_transient_key);
    } elseif ( $transient_type == 'thread_id' ) {
        $thread_id_transient_key = 'chatbot_chatgpt_thread_id_' . $user_id . '_' . $page_id;
        delete_transient($thread_id_transient_key);
    } elseif ( $transient_type == 'additional_instructions' ) {
        $additional_instructions_transient_key = 'chatbot_chatgpt_additional_instructions_' . $user_id . '_' . $page_id;
        delete_transient($additional_instructions_transient_key);
    } else {
        return;
    }

}
