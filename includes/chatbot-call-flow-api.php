<?php
/**
 * Kognetiks Chatbot for WordPress - Kflow API - Ver 1.9.5
 *
 * This file contains the code for table actions for reporting
 * to display the Chatbot on the website.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Call the ChatGPT API
function chatbot_chatgpt_call_flow_api($api_key, $message) {

    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;
    global $learningMessages;
    global $script_data_array;
    global $additional_instructions;
    global $model;
    
    global $errorResponses;

    global $kflow_data;

    // DIAG - Diagnostics - Ver 1.8.6
    // back_trace( 'NOTICE', 'chatbot_chatgpt_call_flow_api()');
    // back_trace( 'NOTICE', 'BEGIN $user_id: ' . $user_id);
    // back_trace( 'NOTICE', 'BEGIN $page_id: ' . $page_id);
    // back_trace( 'NOTICE', 'BEGIN $session_id: ' . $session_id);
    // back_trace( 'NOTICE', 'BEGIN $thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', 'BEGIN $assistant_id: ' . $assistant_id);

    // Fetch the KFlow data
    // $sequence_id = $script_data_array['sequence_id'];
    $kflow_sequence = get_chatbot_chatgpt_transients('kflow_sequence', null, null, $session_id);
    $kflow_step = (int) get_chatbot_chatgpt_transients('kflow_step', null, null, $session_id);

    $kflow_data = kflow_get_sequence_data($kflow_sequence);

    // DIAG - Diagnostics - Ver 1.9.5
    // back_trace ( 'NOTICE', '$script_data_array: ' . print_r($script_data_array, true));
    back_trace ( 'NOTICE', '$kflow_data: ' . print_r($kflow_data, true));

    // Add +1 to $script_data_array['next_step']
    // $script_data_array['next_step'] = $script_data_array['next_step'] + 1;
    $kflow_step = $kflow_step + 1;

    // Check if $script_data_array['next_step'] is greater than $script_data_array['total_steps']
    // if ($script_data_array['next_step'] > $kflow_data['total_steps']) {

    // Count the number of 'Steps' in the KFlow data
    $kflow_data['total_steps'] = count($kflow_data['Steps']);
    
    if ($kflow_step > (int) $kflow_data['total_steps']) {

        // REPLACE THE PLACEHOLDERS IN THE TEMPLATE WITH THE ANSWERS
        // [ANSWER_1], [ANSWER_2], [ANSWER_3], [ANSWER_4], [ANSWER_5], [ANSWER_6], [ANSWER_7], [ANSWER_8], [ANSWER_9], [ANSWER_10]

        // Get the template for the end of the script
        $message = $kflow_data['Templates'][1];

        // Call the ChatGPT Assistant API
        $message = chatbot_chatgpt_assistant_api($api_key, $message, $session_id, $user_id, $page_id, $thread_id, $assistant_id, $model);

    } else {

        // Get the next step in the script
        // $message = $kflow_data[$sequence_id]['next_step'];
        $kflow_prompt_id = $kflow_data['Steps'][$kflow_step];
        $message = $kflow_data['Prompts'][$kflow_prompt_id];

        // Update the transients
        set_chatbot_chatgpt_transients('kflow_sequence', $kflow_sequence, null, null, $session_id);
        set_chatbot_chatgpt_transients('kflow_step', $kflow_step, null, null, $session_id);

        // Call the ChatGPT Assistant API

    }

    // Context History
    addEntry('chatbot_chatgpt_context_history', $message);

    // DIAG Diagnostics
    back_trace( 'NOTICE', '$message: ' . $message);  

    // Add message to converation log
    append_message_to_conversation_log($session_id, $user_id, $page_id, 'Chatbot', $thread_id, $assistant_id, $message);

    // Post the kflow message to the UI as if it were a response
    // $response_body['message'] = $message;
    // $response_body['choices'][0]['message']['content'] = $message;

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', '$response: ' . print_r($response, true));

    // $response_body = json_decode(wp_remote_retrieve_body($response), true);
    // if (isset($response_body['message'])) {
    //     $response_body['message'] = trim($response_body['message']);
    //     if (!str_ends_with($response_body['message'], '.')) {
    //         $response_body['message'] .= '.';
    //     }
    // }

    // DIAG - Diagnostics - Ver 1.8.1
    // back_trace( 'NOTICE', '$response_body: ' . print_r($response_body))

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

    // DIAG - Diagnostics - Ver 1.8.6
    // back_trace( 'NOTICE', 'AFTER $user_id: ' . $user_id);
    // back_trace( 'NOTICE', 'AFTER $page_id: ' . $page_id);
    // back_trace( 'NOTICE', 'AFTER $session_id: ' . $session_id);
    // back_trace( 'NOTICE', 'AFTER $thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', 'AFTER $assistant_id: ' . $assistant_id);   

    // DIAG - Diagnostics - Ver 1.8.1
    // FIXME - ADD THE USAGE TO CONVERSATION TRACKER
    // back_trace( 'NOTICE', 'Usage - Prompt Tokens: ' . $response_body["usage"]["prompt_tokens"]);
    // back_trace( 'NOTICE', 'Usage - Completion Tokens: ' . $response_body["usage"]["completion_tokens"]);
    // back_trace( 'NOTICE', 'Usage - Total Tokens: ' . $response_body["usage"]["total_tokens"]);

    // There is no usage in the response
    $response_body["usage"]["prompt_tokens"] = 0;
    $response_body["usage"]["completion_tokens"] = 0;
    $response_body["usage"]["total_tokens"] = 0;

    // Add the usage to the conversation tracker
    append_message_to_conversation_log($session_id, $user_id, $page_id, 'Prompt Tokens', null, null, $response_body["usage"]["prompt_tokens"]);
    append_message_to_conversation_log($session_id, $user_id, $page_id, 'Completion Tokens', null, null, $response_body["usage"]["completion_tokens"]);
    append_message_to_conversation_log($session_id, $user_id, $page_id, 'Total Tokens', null, null, $response_body["usage"]["total_tokens"]);

    // if (!empty($response_body['choices'])) {
    //     // Handle the response from the chat engine
    //     // Context History - Ver 1.6.1
    //     addEntry('chatbot_chatgpt_context_history', $response_body['choices'][0]['message']['content']);
    //     return $response_body['choices'][0]['message']['content'];
    // } else {
    //     // FIXME - Decide what to return here - it's an error
    //     if (get_locale() !== "en_US") {
    //         $localized_errorResponses = get_localized_errorResponses(get_locale(), $errorResponses);
    //     } else {
    //         $localized_errorResponses = $errorResponses;
    //     }
    //     // Return a random error message
    //     return $localized_errorResponses[array_rand($localized_errorResponses)];
    // }

    // Set success and return $message
    return $message;
    
}