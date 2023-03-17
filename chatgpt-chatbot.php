<?php
/*
 * Plugin Name: ChatGPT Chatbot
 * Plugin URI:  https://www.kognetiks.com/chatgpt-chatbot
 * Description: A simple plugin to add a ChatGPT Chatbot to your Wordpress Website.
 * Version:     1.0.0
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
 * along with ChatGPT Chatbot. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
 * 
*/

// // If this file is called directly, die.
defined( 'WPINC' ) || die;

// If this file is called directly, die.
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

// Include necessary files
require_once plugin_dir_path(__FILE__) . 'includes/chatgpt-chatbot-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/chatgpt-chatbot-shortcode.php';

// Enqueue plugin scripts and styles
function chatgpt_chatbot_enqueue_scripts() {
    wp_enqueue_style('chatgpt-chatbot-css', plugins_url('assets/css/chatgpt-chatbot.css', __FILE__));
    wp_enqueue_script('chatgpt-chatbot-js', plugins_url('assets/js/chatgpt-chatbot.js', __FILE__), array('jquery'), '1.0', true);

    wp_localize_script('chatgpt-chatbot-js', 'chatgpt_chatbot_params', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'api_key' => esc_attr(get_option('chatgpt_api_key')),
    ));
}
add_action('wp_enqueue_scripts', 'chatgpt_chatbot_enqueue_scripts');

// Handle Ajax requests
function chatgpt_chatbot_send_message() {
    $api_key = esc_attr(get_option('chatgpt_api_key'));
    $message = sanitize_text_field($_POST['message']);

    // Check API key and message
    if (!$api_key || !$message) {
        wp_send_json_error('Invalid API key or message');
    }

    // Send message to ChatGPT API
    $response = chatgpt_chatbot_call_api($api_key, $message);

    // Return response
    wp_send_json_success($response);
}

// Add link to chatgtp options - setting page
function chatgpt_chatbot_plugin_action_links($links) {
    $settings_link = '<a href="../wp-admin/options-general.php?page=chatgpt-chatbot">' . __('Settings', 'chatgpt-chatbot') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

add_action('wp_ajax_chatgpt_chatbot_send_message', 'chatgpt_chatbot_send_message');
add_action('wp_ajax_nopriv_chatgpt_chatbot_send_message', 'chatgpt_chatbot_send_message');
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'chatgpt_chatbot_plugin_action_links');

// Call the ChatGPT API
function chatgpt_chatbot_call_api($api_key, $message) {
    // $api_url = 'https://api.openai.com/v1/engines/davinci-codex/completions';
    // Replaced with the current ChatGPT API URL
    $api_url = 'https://api.openai.com/v1/chat/completions';

    $headers = array(
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type' => 'application/json',
    );

    // Select the OpenAI Model
    $model = "gpt-3.5-turbo";

    $body = array(
        'max_tokens' => 150,
        'model' => 'gpt-3.5-turbo',
        'temperature' => 0.5,
        'messages' => array(array('role' => 'user', 'content' => $message)),
    );

    $args = array(
        'headers' => $headers,
        'body' => json_encode($body),
        'method' => 'POST',
        'data_format' => 'body',
        'timeout' => 15, // Increase the timeout values to 15 seconds
    );

    $response = wp_remote_post($api_url, $args);

    if (is_wp_error($response)) {
        return 'Error Line 86: ' . $response->get_error_message();
    }

    // Return json_decode(wp_remote_retrieve_body($response), true);
    $response_body = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($response_body['choices']) && !empty($response_body['choices'])) {

        return $response_body['choices'][0]['message']['content'];
    } else {
        return 'Error Line 94: Unable to fetch response from ChatGPT API';
    }
}
