<?php
/**
 * Kognetiks Chatbot for WordPress - Chatbot Models
 *
 * This file contains the code to retrieve the list of available models
 * from OpenAI API and display them in the settings page.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Function to get the Assistant's name
function chatbot_chatgpt_get_openai_models() {

    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;
    global $kchat_settings;
    global $additional_instructions;
    global $model;
    global $voice;

    global $chatbot_chatgpt_display_style;
    global $chatbot_chatgpt_assistant_alias;
    
    $api_key = '';

    // Retrieve the API key
    $api_key = esc_attr(get_option('chatbot_chatgpt_api_key'));

    // Default model list
    $default_model_list = '';
    $default_model_list = array(
        array(
            'id' => 'dall-e-3',
            'object' => 'model',
            'created' => 1698785189,
            'owned_by' => 'system'
        ),
        array(
            'id' => 'gpt-3.5-turbo',
            'object' => 'model',
            'created' => 1677610602,
            'owned_by' => 'system'
        ),
        array(
            'id' => 'tts-1-hd',
            'object' => 'model',
            'created' => 1699053241,
            'owned_by' => 'system'
        ),
        array(
            'id' => 'whisper-1',
            'object' => 'model',
            'created' => 1677532384,
            'owned_by' => 'openai-internal'
        )
    );

    // See if the option exists, if not then create it and set the default
    if (get_option('chatbot_chatgpt_model_choice') === false) {
        update_option('chatbot_chatgpt_model_choice', 'gpt-3.5-turbo');
    }
    if (get_option('chatbot_chatgpt_image_model_option') === false) {
        update_option('chatbot_chatgpt_image_model_option', 'dall-e-3');
    }
    if (get_option('chatbot_chatgpt_voice_model_option') === false) {
        update_option('chatbot_chatgpt_voice_model_option', 'tts-1-hd');
    }
    if (get_option('chatbot_chatgpt_whisper_model_option') === false) {
        update_option('chatbot_chatgpt_whisper_model_option', 'whisper-1');
    }

    // Check if the API key is empty
    if (empty($api_key)) {
        return $default_model_list;
    }

    // Initialize cURL session
    $ch = curl_init();

    // Set the URL
    curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/models");
    // Include the headers
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/json",
        "Authorization: Bearer " . $api_key
    ));
    // Return the response as a string instead of directly outputting it
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    // Execute the request and decode the JSON response into an associative array
    $response = curl_exec($ch);
    curl_close($ch);

    // Decode the JSON response
    $data = json_decode($response, true);

    // Check for API errors
    if (isset($data['error'])) {
        // return "Error: " . $data['error']['message'];
        // On 1st install needs an API key
        // So return a short list of the base models until an API key is entered
        return $default_model_list;
    }

    // Extract the models from the response
    $models = $data['data'];

    // Ensure $models is an array
    if (!is_array($models)) {
        return $default_model_list;
    } else {
        // Sort the models by name
        usort($models, function($a, $b) {
            return $a['id'] <=> $b['id'];
        });
    }

    // DIAG - Diagnostics - Ver 2.0.2.1
    // back_trace( 'NOTICE' , '$models: ' . print_r($models, true));

    // Return the list of models
    return $models;

}
