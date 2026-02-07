<?php
/**
 * Kognetiks Chatbot - Kflow API - Ver 1.9.5
 *
 * This file contains the code accessing the Kflow API.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Call the ChatGPT API
function chatbot_chatgpt_call_flow_api($api_key, $message, $user_id = null, $page_id = null, $session_id = null, $assistant_id = null, $client_message_id = null) {

    global $wpdb;

    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;
    global $learningMessages;
    global $kchat_settings;
    global $additional_instructions;
    global $model;
    global $voice;
    
    global $errorResponses;

    global $kflow_data;

    // DIAG - Diagnostics - Ver 2.4.4
    // back_trace("NOTICE", "Starting OpenAI KFlow API call");
    // back_trace("NOTICE", "Message: " . $message);
    // back_trace("NOTICE", "User ID: " . $user_id);
    // back_trace("NOTICE", "Page ID: " . $page_id);
    // back_trace("NOTICE", "Session ID: " . $session_id);
    // back_trace("NOTICE", "Assistant ID: " . $assistant_id);
    // back_trace("NOTICE", "Client Message ID: " . $client_message_id);

    // Use client_message_id if provided, otherwise generate a unique message UUID for idempotency
    $message_uuid = $client_message_id ? $client_message_id : wp_generate_uuid4();

    // Lock the conversation BEFORE thread resolution to prevent empty-thread vs real-thread lock split
    $conv_lock = 'chatgpt_conv_lock_' . wp_hash($assistant_id . '|' . $user_id . '|' . $page_id . '|' . $session_id);
    $lock_timeout = 60; // 60 seconds timeout

    // Check for duplicate message UUID in conversation log
    $duplicate_key = 'chatgpt_message_uuid_' . $message_uuid;
    if (get_transient($duplicate_key)) {
        // DIAG - Diagnostics - Ver 2.3.4
        return "Error: Duplicate request detected. Please try again.";
    }

    // Lock check removed - main send function handles locking
    set_transient($duplicate_key, true, 120); // 2 minutes to prevent duplicates - Ver 2.3.7

    // DIAG - Diagnostics - Ver 1.8.6

    // Build conversation context using standardized function - Ver 2.3.9+
    // This function handles conversation history building, message cleaning, and conversation continuity
    // Note: Kflow is a flow controller, but we build context for consistency and potential use in custom GPT API calls
    $conversation_context = chatbot_chatgpt_build_conversation_context('standard', 10, $session_id);

    // Fetch the KFlow data
    // $sequence_id = $kchat_settings['sequence_id'];
    $kflow_sequence = get_chatbot_chatgpt_transients('kflow_sequence', null, null, $session_id);
    $kflow_step = (int) get_chatbot_chatgpt_transients('kflow_step', null, null, $session_id);

    $kflow_data = kflow_get_sequence_data($kflow_sequence);

    // DIAG - Diagnostics - Ver 1.9.5

    // Count the number of 'Steps' in the KFlow data
    $kflow_data['total_steps'] = count($kflow_data['Steps']);
    $max_answers = (int) $kflow_data['total_steps'] - 1;

    // Minus 1 from the total steps to get the last step
    if ($kflow_step > $max_answers) {

        // REPLACE THE PLACEHOLDERS IN THE TEMPLATE WITH THE ANSWERS
        // [ANSWER=1], [ANSWER=2], ..., [ANSWER=nn]

        // Get the template for the end of the script
        $template = $kflow_data['Templates'][1];

        // Get the answers from the conversation log
        $answers = [];
        $answers = chatbot_chatgpt_retrieve_answers($session_id, $user_id, $page_id, $assistant_id, $max_answers);

        // Parse the template inserting the answers
        $message = chatbot_chatgpt_parse_template($template, $answers);

        // Call the ChatGPT Assistant API
        $api_key = ''; // Not needed as this is stored in the assistant
        $thread_id = ''; // Not needed as this is the end of the script so no thread_id
        $message = chatbot_chatgpt_custom_gpt_call_api($api_key, $message, $assistant_id, $thread_id, $session_id, $user_id, $page_id);

    } else {

        // Get the next step in the script
        // $message = $kflow_data[$sequence_id]['next_step'];
        $kflow_prompt_id = $kflow_data['Steps'][$kflow_step];
        $message = $kflow_data['Prompts'][$kflow_prompt_id - 1];

        // Strip $message of any escaped characters
        $message = stripslashes($message);

        // $thread_id
        // $thread_id = '[answer=' . $kflow_step . ']';
        
        // Add +1 to $kchat_settings['next_step']
        // $kflow_step = $kflow_step + 1;

        // Update the transients
        set_chatbot_chatgpt_transients('kflow_sequence', $kflow_sequence, null, null, $session_id);
        set_chatbot_chatgpt_transients('kflow_step', $kflow_step, null, null, $session_id);

        // Return from more answers

    }

    // This function doesn't use any tokens - so set them to 0 and log them anyway
    $response_body["usage"]["prompt_tokens"] = 0;
    $response_body["usage"]["completion_tokens"] = 0;
    $response_body["usage"]["total_tokens"] = 0;

    // Add the usage to the conversation tracker
    append_message_to_conversation_log($session_id, $user_id, $page_id, 'Prompt Tokens', null, null, null, $response_body["usage"]["prompt_tokens"]);
    append_message_to_conversation_log($session_id, $user_id, $page_id, 'Completion Tokens', null, null, null, $response_body["usage"]["completion_tokens"]);
    append_message_to_conversation_log($session_id, $user_id, $page_id, 'Total Tokens', null, null, null, $response_body["usage"]["total_tokens"]);
    
    // Context History
    addEntry('chatbot_chatgpt_context_history', $message);

    // Add message to conversation log
    // DIAG Diagnostics
    $thread_id = get_chatbot_chatgpt_threads($user_id, $session_id, $page_id, $assistant_id);
    append_message_to_conversation_log($session_id, $user_id, $page_id, 'Chatbot', $thread_id, $assistant_id, null, $message);

    // Get the user ID and page ID
    if (empty($user_id)) {
        $user_id = get_current_user_id(); // Get current user ID
    }
    if (empty($page_id)) {
        $page_id = get_the_id(); // Get current page ID
        if (empty($page_id)) {
            // $page_id = get_queried_object_id(); // Get the ID of the queried object if $page_id is not set
            // Changed - Ver 1.9.1 - 2024 03 05
            $page_id = get_the_ID(); // Get the ID of the queried object if $page_id is not set
        }
    }

    // Set success and return $message
    // Clear locks on success
    // Lock clearing removed - main send function handles locking
    return $message;
    
}

// Get the Answers from the Conversation Log
function chatbot_chatgpt_retrieve_answers($session_id, $user_id, $page_id, $assistant_id, $max_answers) {

    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';

    // Get the answers from the conversation log
    $answers = $wpdb->get_results("SELECT message_text
                                    FROM $table_name
                                    WHERE session_id = '$session_id'
                                    AND user_id = '$user_id'
                                    AND page_id = '$page_id'
                                    AND assistant_id = '$assistant_id'
                                    AND user_type = 'Visitor'
                                    ORDER BY thread_id DESC
                                    LIMIT $max_answers;
                                ");

    // Initialize the answers array
    $answers_array = array();

    // if $answers is empty, return an empty array
    if (empty($answers)) {
        // DIAG - Diagnostics
        return $answers_array;
    }

    // if $answer is an error, return an empty array
    if (is_wp_error($answers)) {
        // DIAG - Diagnostics
        return $answers_array;
    }

    // Loop through the answers and add them to the answers array
    foreach ($answers as $answer) {
        $answers_array[] = $answer->message_text;
    }

    // Return the answers array
    return $answers_array;

}

// Parse template and replace placeholders with answers
function chatbot_chatgpt_parse_template($template, $answers) {

    // Initialize the message
    $message = '';

    // if $template is empty, return an empty string
    if (empty($template)) {
        // DIAG - Diagnostics
        return $message;
    }

    // Replace the placeholders in the template with the answers
    $message = $template;
    $i = 1;
    foreach ($answers as $answer) {
        $message = str_replace('[answer=' . $i . ']', $answer, $message);
        $i++;
    }

    // Return the message
    return $message;

}
