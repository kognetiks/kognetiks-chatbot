<?php
/**
 * Kognetiks Chatbot - Local API - Ver 2.2.2
 *
 * This file contains the code accessing the Jan.ai local API server.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Call the DeepSeek API
function chatbot_call_local_api($api_key, $message) {

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

    // DIAG - Diagnostics - Ver 2.2.2
    // back_trace( 'NOTICE', 'chatbot_call_local_api - start');
    // back_trace( 'NOTICE', 'chatbot_call_local_api - $api_key: ' . $api_key);
    // back_trace( 'NOTICE', 'chatbot_call_local_api - $message: ' . $message);
    // back_trace( 'NOTICE', 'BEGIN $user_id: ' . $user_id);
    // back_trace( 'NOTICE', 'BEGIN $page_id: ' . $page_id);
    // back_trace( 'NOTICE', 'BEGIN $session_id: ' . $session_id);
    // back_trace( 'NOTICE', 'BEGIN $thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', 'BEGIN $assistant_id: ' . $assistant_id);

    // Jan.ai Download
    // https://jan.ai/download

    // Jan.ai API Documentation
    // https://jan.ai/docs

    // Jan.ai Quick Start Guide
    // https://jan.ai/docs/quickstart

    // The current DeepSeek API URL endpoint for deepseek-chat
    // $api_url = 'https://127.0.0.1:1337/chat/completions';
    $api_url = get_api_base_url();

    // DIAG - Diagnostics - Ver 2.2.2
    // back_trace( 'NOTICE', '$api_url: ' . $api_url);

    // 
    // Select an Open-source Model
    //
    // Hugging Face Models
    // Libraries = GGUF
    // Sort by Most Downloads
    // https://huggingface.co/models?library=gguf&sort=downloads
    //

    // Set the model choice
    // update_option('chatbot_local_model_choice', 'llama3.2-3b-instruct');

    // Start the model
    chatbot_chatgpt_local_start_model();

    // API key for the local server - Typically not needed
    $api_key = esc_attr(get_option('chatbot_local_api_key', ''));

    $headers = array(
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type'  => 'application/json',
    );

    // Retrieve model settings
    $model = esc_attr(get_option('chatbot_local_model_choice', 'llama3.2-3b-instruct'));
    // DIAG - Diagnostics
    back_trace( 'NOTICE', '$model: ' . $model);
    $max_tokens = intval(get_option('chatbot_local_max_tokens_setting', 4096));
    $temperature = floatval(get_option('chatbot_local_temperature', 0.8));
    $top_p = floatval(get_option('chatbot_local_top_p', 0.95));
    $context = esc_attr(get_option('chatbot_local_conversation_context', 'You are a versatile, friendly, and helpful assistant that responds using Markdown syntax.'));
    $timeout = intval(get_option('chatbot_local_timeout_setting', 360));

    // Construct request body to match the expected schema
    $body = array(
        'model' => $model,
        'stream' => null,
        'max_tokens' => $max_tokens,
        'stop' => array("End"),
        'frequency_penalty' => 0.2,
        'presence_penalty' => 0.6,
        'temperature' => $temperature,
        'top_p' => $top_p,
        'modalities' => array("text"),
        'audio' => array(
            'voice' => 'default',
            'format' => 'mp3'
        ),
        'store' => null,
        'metadata' => array(
            'type' => 'conversation'
        ),
        'logit_bias' => array(
            "15496" => -100,
            "51561" => -100
        ),
        'logprobs' => null,
        'n' => 1,
        'response_format' => array('type' => 'text'),
        'seed' => 123,
        'stream_options' => null,
        // 'tools' => array(
        //     array(
        //         'type' => 'function',
        //         'function' => array(
        //             'name' => '',
        //             'parameters' => array(),
        //             'strict' => null
        //         )
        //     )
        // ),
        'tools' => null,
        'parallel_tool_calls' => null,
        'messages' => array(
            array('role' => 'system', 'content' => $context),
            array('role' => 'user', 'content' => $message)
        )
    );

    // API request arguments
    $args = array(
        'headers' => $headers,
        'body'    => json_encode($body),
        'method'  => 'POST',
        'timeout' => $timeout,
        'data_format' => 'body',
    );

    // Log message for debugging
    back_trace( 'NOTICE', '$api_url: ' . $api_url);
    back_trace( 'NOTICE', '$args: ' . print_r($args, true));

    // Send request
    $response = wp_remote_post($api_url, $args);
    
    // Log response for debugging
    back_trace( 'NOTICE', '$response: ' . print_r($response, true));

    // Handle request errors
    if (is_wp_error($response)) {
        return 'Error: ' . $response->get_error_message() . ' Please check Settings for a valid API key.';
    }

    // Decode the response
    $response_body = json_decode(wp_remote_retrieve_body($response), true);

    // Check for content in response
    if (!empty($response_body['choices'][0]['message']['content'])) {
        return trim($response_body['choices'][0]['message']['content']);
    } else {
        // Ensure $error_responses is an array
        if (!is_array($error_responses)) {
            $error_responses = array('An unknown error occurred. Please try again later.');
        }
        return $error_responses[array_rand($error_responses)];
    }

    // Check for response code and message
    if (isset($response['response']['code']) && $response['response']['code'] == 200) {
        if (!empty($response_body['choices'][0]['message']['content'])) {
            return trim($response_body['choices'][0]['message']['content']);
        } else {
            // Ensure $error_responses is an array
            if (!is_array($error_responses)) {
                $error_responses = array('An unknown error occurred. Please try again later.');
            }
            return $error_responses[array_rand($error_responses)];
        }
    } else {
        return 'Error: Invalid response from the server.';
    }

}


// Start the chat completions model
function chatbot_chatgpt_local_start_model() {

    // DiAG - Diagnostics
    back_trace( 'NOTICE', 'clockwork_cortext_start_model');

    global $chatbot_chatgpt_local_model_status;

    // Fetch the models
    // chatbot_chatgpt_local_fetch_models();

    // Get the model choice
    update_option('chatbot_local_model_choice', 'llama3.2-3b-instruct');
    // update_option('chatbot_local_model_choice', 'DeepSeek-R1-Distill-Qwen-14B-Q5_K_S.gguf');
    // update_option('chatbot_local_model_choice', 'Mistral-Small-24B-Instruct-2501-Q4_K_M.gguf');
    // update_option('chatbot_local_model_choice', 'Llama-3.2-3B-Instruct-uncensored-Q6_K_L.gguf');

    $ai_model_choice = esc_attr(get_option('chatbot_local_model_choice', 'llama3.2-3b-instruct'));

    // switch ($ai_model_choice) {
    //     case 'llama3.2-3':
    //         $model = 'llama3.2-3b-instruct';
    //         break;
    //     case 'DeepSeek-R1':
    //         $model = 'DeepSeek-R1-Distill-Qwen-14B-Q5_K_S.gguf';
    //         break;
    //     // Mistral-Small-24B-Instruct-2501-Q4_K_M.gguf
    //     case 'Mistral-Small':
    //         $model = 'Mistral-Small-24B-Instruct-2501-Q4_K_M.gguf';
    //         break;
    //     // Llama-3.2-3B-Instruct-uncensored-GGUF
    //     case 'Llama-3.2-3B-Instruct-uncensored-Q6_K_L.gguf':
    //         $model = 'Llama-3.2-3B-Instruct-uncensored-Q6_K_L.gguf';
    //         break;
    //     default:
    //         $model = 'llama3.2-3b-instruct';
    // }

    // DiAG - Diagnostics
    back_trace( 'NOTICE', '$model: ' . $model);

    // Set the API URL
    $api_url = esc_attr(get_option('clockwork_cortext_chat_completions_api_url','http://127.0.0.1:1337/v1')) . '/models/start';

    // Prepare the data
    $data = array(
        'model' => $model
    );

    // Send the request
    $response = wp_remote_post($api_url, array(
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode(array(
            'model' => $model
        )),
    ));

    // Check for errors
    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        // Log the error
        prod_trace( 'ERROR', $error_message);
        // Set the model status
        $clockwork_cortext_model_status = 'error';
        return $response;
    }

    // Get the response body
    $response_body = wp_remote_retrieve_body($response);

    // DiAG - Diagnostics
    back_trace( 'NOTICE', '$response_body: ' . $response_body);

    // Set the model status
    $chatbot_chatgpt_local_model_status = 'started';

    return $response_body;
    
}
