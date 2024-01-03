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

// Set the transitent - example usage
// set_chatbot_chatgpt_transients($chatbot_chatgpt_display_style, $chatbot_chatgpt_assistant_alias);

// Set the transients
function set_chatbot_chatgpt_transients($t_chatbot_chatgpt_display_style, $t_chatbot_chatgpt_assistant_alias) {

    // DIAG - Diagnostics
    // chatbot_chatgpt_back_trace( 'NOTICE', '$t_chatbot_chatgpt_display_style ' . $t_chatbot_chatgpt_display_style);
    // chatbot_chatgpt_back_trace( 'NOTICE', '$t_chatbot_chatgpt_assistant_alias ' . $t_chatbot_chatgpt_assistant_alias);

    $user_id = get_current_user_id(); // Get current user ID
    $page_id = get_the_ID(); // Get current page ID
    if (empty($page_id)) {
        $page_id = get_queried_object_id(); // Get the ID of the queried object if $page_id is not set
    }

    // DIAG - Diagnostics
    // chatbot_chatgpt_back_trace( 'NOTICE', '$user_id ' . $user_id);
    // chatbot_chatgpt_back_trace( 'NOTICE', '$page_id ' . $page_id);

    // Create unique keys for transients
    $style_transient_key = 'chatbot_chatgpt_style_' . $user_id . '_' . $page_id;
    $assistant_transient_key = 'chatbot_chatgpt_assistant_' . $user_id . '_' . $page_id;

    // Store the style and the assistant value with unique keys
    set_transient($style_transient_key, $t_chatbot_chatgpt_display_style, 60*60); // Store for 1 hour
    set_transient($assistant_transient_key, $t_chatbot_chatgpt_assistant_alias, 60*60); // Store for 1 hour

}

// Get the transient - example usage
// $chatbot_settings = get_chatbot_chatgpt_transients();
// $display_style = $chatbot_settings['display_style'];
// $assistant_alias = $chatbot_settings['assistant_alias'];

// Get the transients
function get_chatbot_chatgpt_transients($user_id, $page_id) {

    // Pass the $user_id and $page_id values from the shortcode
    // $user_id = get_current_user_id(); // Get current user ID
    // $page_id = get_the_ID(); // Get current page ID

    // DIAG - Diagnostics
    // chatbot_chatgpt_back_trace( 'NOTICE', '$user_id ' . $user_id);
    // chatbot_chatgpt_back_trace( 'NOTICE', '$page_id ' . $page_id);

    // Construct the unique keys
    $style_transient_key = 'chatbot_chatgpt_style_' . $user_id . '_' . $page_id;
    $assistant_transient_key = 'chatbot_chatgpt_assistant_' . $user_id . '_' . $page_id;

    // Retrieve the stored values
    $t_chatbot_chatgpt_display_style = get_transient($style_transient_key);
    if ($t_chatbot_chatgpt_display_style === false) {
        $t_chatbot_chatgpt_display_style = '';
    }
    
    $t_chatbot_chatgpt_assistant_alias = get_transient($assistant_transient_key);
    if ($t_chatbot_chatgpt_assistant_alias === false) {
        $t_chatbot_chatgpt_assistant_alias = '';
    }

    // DIAG - Diagnostics
    // chatbot_chatgpt_back_trace( 'NOTICE', '$t_chatbot_chatgpt_display_style ' . $t_chatbot_chatgpt_display_style);
    // chatbot_chatgpt_back_trace( 'NOTICE', '$t_chatbot_chatgpt_assistant_alias ' . $t_chatbot_chatgpt_assistant_alias);

    // Return the values, also handle the case where the transient might have expired
    return array(
        'display_style' => $t_chatbot_chatgpt_display_style,
        'assistant_alias' => $t_chatbot_chatgpt_assistant_alias
    );

}
