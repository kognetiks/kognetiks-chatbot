<?php
/*
 * Plugin Name: Chatbot ChatGPT
 * Plugin URI:  https://github.com/kognetiks/chatbot-chatgpt
 * Description: A simple plugin to add a Chatbot ChatGPT to your Wordpress Website.
 * Version:     1.4.1
 * Author:      Kognetiks.com
 * Author URI:  https://www.kognetiks.com
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *  
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU
 * General Public License version 2, as published by the Free Software Foundation. You may NOT assume
 * that you can use any other version of the GPL.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Chatbot ChatGPT. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
 * 
*/

// If this file is called directly, die.
defined( 'WPINC' ) || die;

// If this file is called directly, die.
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

// Include necessary files
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-chatgpt-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-chatgpt-shortcode.php';

// Enqueue plugin scripts and styles
function chatbot_chatgpt_enqueue_scripts() {
    // Ensure the Dashicons font is properly enqueued - Ver 1.1.0
    wp_enqueue_style( 'dashicons' );
    wp_enqueue_style('chatbot-chatgpt-css', plugins_url('assets/css/chatbot-chatgpt.css', __FILE__));
    wp_enqueue_script('chatbot-chatgpt-js', plugins_url('assets/js/chatbot-chatgpt.js', __FILE__), array('jquery'), '1.0', true);

    // Ver 1.4.1
    // Enqueue the chatbot-chatgpt-local.js file
    wp_enqueue_script('chatbot-chatgpt-local', plugins_url('assets/js/chatbot-chatgpt-local.js', __FILE__), array('jquery'), '1.0', true);
    $chatbot_settings = array(
        'chatgpt_bot_name' => esc_attr(get_option('chatgpt_bot_name')),
        'chatgpt_initial_greeting' => esc_attr(get_option('chatgpt_initial_greeting')),
        'chatgpt_subsequent_greeting' => esc_attr(get_option('chatgpt_subsequent_greeting')),
        'chatGPTChatBotStatus' => esc_attr(get_option('chatGPTChatBotStatus')),
        'chatgpt_disclaimer_setting' => esc_attr(get_option('chatgpt_disclaimer_setting')),
    );
    wp_localize_script('chatbot-chatgpt-localize', 'chatbotSettings', $chatbot_settings);

    wp_localize_script('chatbot-chatgpt-js', 'chatbot_chatgpt_params', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'api_key' => esc_attr(get_option('chatgpt_api_key')),
    ));
}
add_action('wp_enqueue_scripts', 'chatbot_chatgpt_enqueue_scripts');

// Handle Ajax requests
function chatbot_chatgpt_send_message() {
    // Get the save API key
    $api_key = esc_attr(get_option('chatgpt_api_key'));
    // Get the saved model from the settings or default to gpt-3.5-turbo
    $model = get_option('chatgpt_model_choice', 'gpt-3.5-turbo');
    // Send only clean text via the API
    $message = sanitize_text_field($_POST['message']);

    // Check API key and message
    if (!$api_key || !$message) {
        wp_send_json_error('Invalid API key or message');
    }

    // Send message to ChatGPT API
    $response = chatbot_chatgpt_call_api($api_key, $message);

    // Return response
    wp_send_json_success($response);
}

// Add link to chatgtp options - setting page
function chatbot_chatgpt_plugin_action_links($links) {
    $settings_link = '<a href="../wp-admin/options-general.php?page=chatbot-chatgpt">' . __('Settings', 'chatbot-chatgpt') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

add_action('wp_ajax_chatbot_chatgpt_send_message', 'chatbot_chatgpt_send_message');
add_action('wp_ajax_nopriv_chatbot_chatgpt_send_message', 'chatbot_chatgpt_send_message');
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'chatbot_chatgpt_plugin_action_links');

// Call the ChatGPT API
function chatbot_chatgpt_call_api($api_key, $message) {
    // The current ChatGPT API URL endpoint for gpt-3.5-turbo and gpt-4
    $api_url = 'https://api.openai.com/v1/chat/completions';

    $headers = array(
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type' => 'application/json',
    );

    // Select the OpenAI Model
    // $model = "gpt-3.5-turbo";
    // Get the saved model from the settings or default to "gpt-3.5-turbo"
    $model = get_option('chatgpt_model_choice', 'gpt-3.5-turbo');
    // console.log($model);

    $body = array(
        'max_tokens' => 150,
        'model' => $model,
        'temperature' => 0.5,
        'messages' => array(array('role' => 'user', 'content' => $message)),
    );

    $args = array(
        'headers' => $headers,
        'body' => json_encode($body),
        'method' => 'POST',
        'data_format' => 'body',
        'timeout' => 15, // Increase the timeout values to 15 seconds to wait just a bit longer for a response from the engine
    );

    $response = wp_remote_post($api_url, $args);

    // Handle any errors that are returned from the chat engine
    if (is_wp_error($response)) {
        return 'Error: ' . $response->get_error_message().' Please check Settings for a valid API key or your OpenAI account for additional information.';
    }

    // Return json_decode(wp_remote_retrieve_body($response), true);
    $response_body = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($response_body['choices']) && !empty($response_body['choices'])) {
        // Handle the response from the chat engine
        return $response_body['choices'][0]['message']['content'];
    } else {
        // Handle any errors that are returned from the chat engine
        return 'Error: Unable to fetch response from ChatGPT API. Please check Settings for a valid API key or your OpenAI account for additional information.';
    }
}


function enqueue_greetings_script() {
    wp_enqueue_script('greetings', plugin_dir_url(__FILE__) . 'assets/js/greetings.js', array('jquery'), null, true);

    $greetings = array(
        'initial_greeting' => get_option('chatgpt_initial_greeting', 'Hello! How can I help you today?'),
        'subsequent_greeting' => get_option('chatgpt_subsequent_greeting', 'Hello again! How can I help you?'),
    );

    wp_localize_script('greetings', 'greetings_data', $greetings);
}
add_action('wp_enqueue_scripts', 'enqueue_greetings_script');