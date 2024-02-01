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

// Example usage - Version 1.7.6

// Set the transients - file id
// set_chatbot_chatgpt_transients( 'file_id', $chatbot_chatgpt_file_id);

// Set the transients - style and assistant alias
// set_chatbot_chatgpt_transients( 'style', $chatbot_chatgpt_display_style);
// set_chatbot_chatgpt_transients( 'assistant_alias', $chatbot_chatgpt_assistant_alias);
// set_chatbot_chatgpt_transients( 'file_id', $chatbot_chatgpt_assistant_alias);

function set_chatbot_chatgpt_transients( $transient_type , $transient_value): void {

    // DIAG - Diagnostics
    // chatbot_chatgpt_back_trace( 'NOTICE', '$transient_type ' . $transient_type);

    $user_id = get_current_user_id(); // Get current user ID
    $page_id = get_the_ID(); // Get current page ID
    if (empty($page_id)) {
        $page_id = get_queried_object_id(); // Get the ID of the queried object if $page_id is not set
    }

    // DIAG - Diagnostics
    // chatbot_chatgpt_back_trace( 'NOTICE', '$user_id ' . $user_id);
    // chatbot_chatgpt_back_trace( 'NOTICE', '$page_id ' . $page_id);

    // Create unique keys for transients
    // $style_transient_key = 'chatbot_chatgpt_style_' . $user_id . '_' . $page_id;
    // $assistant_transient_key = 'chatbot_chatgpt_assistant_' . $user_id . '_' . $page_id;

    if ( $transient_type == 'style' ) {
        $style_transient_key = 'chatbot_chatgpt_style_' . $user_id . '_' . $page_id;
        set_transient($style_transient_key, $transient_value, 60*60); // Store for 1 hour
    } elseif ( $transient_type == 'assistant_alias' ) {
        $assistant_transient_key = 'chatbot_chatgpt_assistant_' . $user_id . '_' . $page_id;
        set_transient($assistant_transient_key, $transient_value, 60*60); // Store for 1 hour
    } elseif ( $transient_type == 'file_id' ) {
        $file_transient_key = 'chatbot_chatgpt_file_id_' . $user_id . '_' . $page_id;
        set_transient($file_transient_key, $transient_value, 60*60); // Store for 1 hour
    } elseif ( $transient_type == 'asst_file_id') {
        $asst_file_transient_key = 'chatbot_chatgpt_asst_file_id_' . $user_id . '_' . $page_id;
        set_transient($asst_file_transient_key, $transient_value, 60*60); // Store for 1 hour
    }

    // DIAG - Diagnostics
    // chatbot_chatgpt_back_trace( 'NOTICE', 'Transient set $transient_key ' . $transient_value);

}

// Get the transient - example usage
// $chatbot_settings = get_chatbot_chatgpt_transients();
// $display_style = $chatbot_settings['display_style'];
// $assistant_alias = $chatbot_settings['assistant_alias'];
// $file_id = $chatbot_settings['file_id'];

// Get the transients
function get_chatbot_chatgpt_transients( $transient_type, $user_id, $page_id) {

    // Pass the $user_id and $page_id values from the shortcode
    // $user_id = get_current_user_id(); // Get current user ID
    // $page_id = get_the_ID(); // Get current page ID

    // DIAG - Diagnostics
    // chatbot_chatgpt_back_trace( 'NOTICE', '$transient_type ' . $transient_type);
    // chatbot_chatgpt_back_trace( 'NOTICE', '$user_id ' . $user_id);
    // chatbot_chatgpt_back_trace( 'NOTICE', '$page_id ' . $page_id);

    if ( $transient_type == 'style' ) {
        $style_transient_key = 'chatbot_chatgpt_style_' . $user_id . '_' . $page_id;
        $transient_value = get_transient($style_transient_key);
        if ($transient_value === false) {
            $transient_value = '';
        }
        return array('display_style' => $transient_value);
    } elseif ( $transient_type == 'assistant_alias' ) {
        $assistant_transient_key = 'chatbot_chatgpt_assistant_' . $user_id . '_' . $page_id;
        $transient_value = get_transient($assistant_transient_key);
        if ($transient_value === false) {
            $transient_value = '';
        }
        return array('assistant_alias' => $transient_value);
    } elseif ( $transient_type == 'file_id' ) {
        $file_transient_key = 'chatbot_chatgpt_file_id_' . $user_id . '_' . $page_id;
        $transient_value = get_transient($file_transient_key);
        if ($transient_value === false) {
            $transient_value = '';
        }
        return array('file_id' => $transient_value);
    } elseif ( $transient_type == 'asst_file_id') {
        $asst_file_transient_key = 'chatbot_chatgpt_asst_file_id_' . $user_id . '_' . $page_id;
        $transient_value = get_transient($asst_file_transient_key);
        if ($transient_value === false) {
            $transient_value = '';
        }
        return array('asst_file_id' => $transient_value);
    }

}


// Delete the transients - Ver 1.7.9
function delete_chatbot_chatgpt_transients( $transient_type, $user_id, $page_id): void {

    if ( $transient_type == 'style' ) {
        $style_transient_key = 'chatbot_chatgpt_style_' . $user_id . '_' . $page_id;
        delete_transient($style_transient_key);
    } elseif ( $transient_type == 'assistant_alias' ) {
        $assistant_transient_key = 'chatbot_chatgpt_assistant_' . $user_id . '_' . $page_id;
        delete_transient($assistant_transient_key);
    } elseif ( $transient_type == 'file_id' ) {
        $file_transient_key = 'chatbot_chatgpt_file_id_' . $user_id . '_' . $page_id;
        delete_transient($file_transient_key);
    } elseif ( $transient_type == 'asst_file_id') {
        $asst_file_transient_key = 'chatbot_chatgpt_asst_file_id_' . $user_id . '_' . $page_id;
        delete_transient($asst_file_transient_key);
    }

}
