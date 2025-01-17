<?php
/**
 * Kognetiks Chatbot - API Endpoints - Ver 2.2.2
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

// Base URL for the OpenAI API calls - Ver 1.8.1
function get_openai_api_base_url() {

    return esc_attr(get_option('chatbot_chatgpt_base_url', 'https://api.openai.com/v1'));

}

// Base URL for the NVIDIA API calls - Ver 2.1.8
function get_nvidia_api_base_url() {

    return esc_attr(get_option('chatbot_nvidia_base_url', 'https://integrate.api.nvidia.com/v1'));

}

// Base URL for the Anthropic API calls - Ver 2.2.1
function get_anthropic_api_base_url() {

    return esc_attr(get_option('chatbot_anthropic_base_url', 'https://api.anthropic.com/v1'));

}

// Base URL for the DeepSeek API calls - Ver 2.2.2
function get_deepseek_api_base_url() {

    return esc_attr(get_option('chatbot_deepseek_base_url', 'https://api.deepseek.com'));

}

// Base URL for the ChatGPT API calls - Ver 2.2.2
function get_threads_api_url() {

    return get_openai_api_base_url() . "/threads";

}

// Base URL for the ChatGPT API calls - Ver 2.2.2
function get_files_api_url() {

    return get_openai_api_base_url() . "/files";

}

// Base URL for the ChatGPT API calls - Ver 2.2.2
function get_chat_completions_api_url() {

    // Enable for either ChatGPT or NVIDIA - Ver 2.1.8
    if (get_option('chatbot_nvidia_api_enabled') == 'Yes' || esc_attr(get_option('chatbot_ai_platform_choice')) == 'NVIDIA') {
        // DIAG - Diagnostics - Ver 2.1.8
        // back_trace( 'NOTICE', 'get_chat_completions_api_url: NVIDIA API' );
        return get_nvidia_api_base_url() . "/chat/completions";
    } else if (get_option('chatbot_anthropic_api_enabled') == 'Yes' || esc_attr(get_option('chatbot_ai_platform_choice')) == 'Anthropic') {
        // DIAG - Diagnostics - Ver 2.2.1
        // back_trace( 'NOTICE', 'get_chat_completions_api_url: Anthropic API' );
        return get_anthropic_api_base_url() . "/messages";
    } else if (get_option('chatbot_deepseek_api_enabled') == 'Yes' || esc_attr(get_option('chatbot_ai_platform_choice')) == 'DeepSeek') {
        // DIAG - Diagnostics - Ver 2.2.2
        // back_trace( 'NOTICE', 'get_chat_completions_api_url: DeepSeek API' );
        return get_deepseek_api_base_url() . "/chat/completions";
    } else {
        // DIAG - Diagnostics - Ver 2.1.8
        // back_trace( 'NOTICE', 'get_chat_completions_api_url: OpenAI API' );
        return get_openai_api_base_url() . "/chat/completions";
    }

}
