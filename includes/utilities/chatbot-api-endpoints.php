<?php
/**
 * Kognetiks Chatbot - API Endpoints - Ver 2.2.2  - Revised - Ver 2.2.6
 *
 * This file contains the code for managing the API endpoints.
 *
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Base URL for the API calls - Ver 2.2.6
function get_api_base_url() {

    $chatbot_ai_platform_choice = esc_attr(get_option('chatbot_ai_platform_choice'), 'OpenAI');

    switch ($chatbot_ai_platform_choice) {

        // Base URL for the OpenAI API calls - Ver 1.8.1
        case 'OpenAI':

            return esc_attr(get_option('chatbot_chatgpt_base_url', 'https://api.openai.com/v1'));
            break;

        // Base URL for the NVIDIA API calls - Ver 2.1.8
        case 'NVIDIA':

            return esc_attr(get_option('chatbot_nvidia_base_url', 'https://integrate.api.nvidia.com/v1'));
            break;

        // Base URL for the Anthropic API calls - Ver 2.2.1
        case 'Anthropic':

            return esc_attr(get_option('chatbot_anthropic_base_url', 'https://api.anthropic.com/v1'));
            break;

        // Base URL for the DeepSeek API calls - Ver 2.2.2
        case 'DeepSeek':

            return esc_attr(get_option('chatbot_deepseek_base_url', 'https://api.deepseek.com'));
            break;

        // Base URL for the Local API calls - Ver 2.2.6
        case 'Local':

            return esc_attr(get_option('chatbot_local_base_url', 'http://127.0.0.1:1337/v1'));
            break;

        default:

            return get_api_base_url();
            break;

    }

}

// Base URL for the ChatGPT API calls - Ver 2.2.2
function get_threads_api_url() {

    return get_api_base_url() . "/threads";

}

// Base URL for the ChatGPT API calls - Ver 2.2.2
function get_files_api_url() {

    return get_api_base_url() . "/files";

}

// Base URL for the ChatGPT API calls - Ver 2.2.2 - Revised - Ver 2.2.6
function get_chat_completions_api_url() {

    $chatbot_ai_platform_choice = esc_attr(get_option('chatbot_ai_platform_choice'), 'OpenAI');

    switch ($chatbot_ai_platform_choice) {

        // Base URL for the OpenAI API calls - Ver 1.8.1
        case 'OpenAI':

            return get_api_base_url() . "/chat/completions";
            break;

        // Base URL for the NVIDIA API calls - Ver 2.1.8
        case 'NVIDIA':

            return get_api_base_url() . "/chat/completions";
            break;

        // Base URL for the Anthropic API calls - Ver 2.2.1
        case 'Anthropic':

            return get_api_base_url() . "/messages";
            break;

        // Base URL for the DeepSeek API calls - Ver 2.2.2
        case 'DeepSeek':

            return get_api_base_url() . "/chat/completions";
            break;

        // Base URL for the Local API calls - Ver 2.2.6
        case 'Local':

            return get_api_base_url() . "/chat/completions";
            break;

        default:

            return get_api_base_url() . "/chat/completions";
            break;
    }

}
