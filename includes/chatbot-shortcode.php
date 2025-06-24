<?php
/**
 * Kognetiks Chatbot - [chatbot_chatgpt] Shortcode Registration
 *
 * This file contains the code for registering the shortcode used
 * to display the Chatbot on the website.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Main Chatbot Shortcode
function chatbot_chatgpt_shortcode( $atts = [], $content = null, $tag = '' ) {

    ob_start();
    
    global $chatbot_chatgpt_plugin_dir_path;
    global $chatbot_chatgpt_plugin_dir_url;
    global $chatbot_chatgpt_plugin_version;

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

    // Initialize $assistant_details as an empty array
    global $assistant_details;
    $assistant_details = [];

    // Initialize $kchat_settings as an empty array
    global $kchat_settings;
    $kchat_settings = [];

    global $kflow_data;

    // header("Cache-Control: no-cache, must-revalidate, max-age=0");
    // header("Pragma: no-cache");

    // Fetch the User ID - Updated Ver 2.0.6 - 2024 07 11
    $user_id = get_current_user_id();
    // Fetch the Kognetiks cookie
    $session_id = kognetiks_get_unique_id();
    if (empty($user_id) || $user_id == 0) {
        $user_id = $session_id;
    }

    // DIAG - Diagnostics - Ver 2.1.0
    // back_trace( 'NOTICE', '$user_id: ' . $user_id);
    // back_trace( 'NOTICE', '$session_id: ' . $session_id);
    // back_trace( 'NOTICE', 'Shortcode tag: ' . $tag);
    // back_trace( 'NOTICE', 'Shortcode atts: ' . print_r($atts, true));

    // DIAG - Diagnostics - Ver 1.9.3
    // back_trace( 'NOTICE', '========================================');
    // back_trace( 'NOTICE', 'Shortcode tag: ' . $tag);
    // back_trace( 'NOTICE', 'Shortcode atts: ' . print_r($atts, true));
    // back_trace( 'NOTICE', '$user_id: ' . $user_id);
    // back_trace( 'NOTICE', '$page_id: ' . $page_id);
    // back_trace( 'NOTICE', '$session_id: ' . $session_id);
    // back_trace( 'NOTICE', '$thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', '$assistant_id: ' . $assistant_id);
    // back_trace( 'NOTICE', '$kchat_settings: ' . print_r($kchat_settings, true));
    // back_trace( 'NOTICE', 'Shortcode Attributes: ' . print_r($atts, true));
    // back_trace( 'NOTICE', 'get_the_id(): ' . get_the_id());
    // back_trace( 'NOTICE', '$model: ' . $model);
    // back_trace( 'NOTICE', 'Browser: ' . $_SERVER['HTTP_USER_AGENT']);
    // back_trace( 'NOTICE', '========================================');
    // foreach ($atts as $key => $value) {
    //     // back_trace( 'NOTICE', '$atts - Key: ' . $key . ' Value: ' . $value);
    // }
    // back_trace( 'NOTICE', '========================================');
   
    // BELT & SUSPENDERS - Ver 1.9.4 - Updated Ver 2.1.8 - 2024 10 26
    if (esc_attr(get_option('chatbot_nvidia_api_enabled', 'No')) == 'Yes') {
        // DIAG - Diagnostics - Ver 2.1.8
        // back_trace( 'NOTICE', 'NVIDIA chatbot is enabled');
        $model_choice = esc_attr(get_option('chatbot_nvidia_model_choice', 'nvidia/llama-3.1-nemotron-51b-instruct'));
        $kchat_settings['chatbot_chatgpt_model'] = $model_choice;
        $voice_choice = esc_attr(get_option('chatbot_nvidia_voice_option', 'none'));
    } elseif (esc_attr(get_option('chatbot_anthropic_api_enabled', 'No')) == 'Yes') {
        // DIAG - Diagnostics - Ver 2.1.8
        // back_trace( 'NOTICE', 'Anthropic chatbot is enabled');
        $model_choice = esc_attr(get_option('chatbot_anthropic_model_choice', 'claude-3-5-sonnet-latest'));
        $kchat_settings['chatbot_chatgpt_model'] = $model_choice;
        $voice_choice = esc_attr(get_option('chatbot_anthropic_voice_option', 'none'));
    } elseif (esc_attr(get_option('chatbot_deepseek_api_enabled', 'No')) == 'Yes') {
        // DIAG - Diagnostics - Ver 2.1.8
        // back_trace( 'NOTICE', 'DeepSeek chatbot is enabled');
        $model_choice = esc_attr(get_option('chatbot_deepseek_model_choice', 'deepseek-chat'));
        $kchat_settings['chatbot_chatgpt_model'] = $model_choice;
        $voice_choice = esc_attr(get_option('chatbot_deepseek_voice_option', 'none'));
    } elseif (esc_attr(get_option('chatbot_mistral_api_enabled', 'No')) == 'Yes') {
        // DIAG - Diagnostics - Ver 2.1.8
        // back_trace( 'NOTICE', 'Mistral chatbot is enabled');
        $model_choice = esc_attr(get_option('chatbot_mistral_model_choice', 'mistral-small-latest'));
        $kchat_settings['chatbot_chatgpt_model'] = $model_choice;
        $voice_choice = esc_attr(get_option('chatbot_mistral_voice_option', 'none'));
    } elseif (esc_attr(get_option('chatbot_markov_chain_api_enabled', 'No')) == 'Yes') {
        // DIAG - Diagnostics - Ver 2.1.8
        // back_trace( 'NOTICE', 'Markov Chain chatbot is enabled');
        $model_choice = esc_attr(get_option('chatbot_markov_chain_model_choice', 'markov-chain-flask'));
        $kchat_settings['chatbot_chatgpt_model'] = $model_choice;
        $voice_choice = esc_attr(get_option('chatbot_markov_chain_voice_option', 'none'));
    } elseif (esc_attr(get_option('chatbot_transformer_model_api_enabled', 'No')) == 'Yes') {
        // DIAG - Diagnostics - Ver 2.2.0
        // back_trace( 'NOTICE', 'Transformer chatbot is enabled');
        $model_choice = esc_attr(get_option('chatbot_transformer_model_choice', 'sentential-context-model'));
        $kchat_settings['chatbot_chatgpt_model'] = $model_choice;
        $voice_choice = esc_attr(get_option('chatbot_transformer_model_voice_option', 'none'));
    } elseif (esc_attr(get_option('chatbot_local_api_enabled', 'No')) == 'Yes') {
        // DIAG - Diagnostics - Ver 2.2.0
        // back_trace( 'NOTICE', 'Local API chatbot is enabled');
        $model_choice = esc_attr(get_option('chatbot_local_model_choice', ''));
        $kchat_settings['chatbot_chatgpt_model'] = $model_choice;
        $voice_choice = esc_attr(get_option('chatbot_local_voice_option', 'none'));
    } else {
        // DIAG - Diagnostics - Ver 2.1.8
        // back_trace( 'NOTICE', 'OpenAI chatbot is enabled');
        $model_choice = esc_attr(get_option('chatbot_chatgpt_model_choice', 'gpt-3.5-turbo'));
        $kchat_settings['chatbot_chatgpt_model'] = $model_choice;
        $voice_choice = esc_attr(get_option('chatbot_chatgpt_voice_option', 'alloy'));    
    }

    // DIAG - Diagnostics - Ver 2.0.6
    // back_trace( 'NOTICE', '========================================');
    // back_trace( 'NOTICE', '$user_id: ' . $user_id);
    // back_trace( 'NOTICE', '$page_id: ' . $page_id);
    // back_trace( 'NOTICE', '$session_id: ' . $session_id);
    // back_trace( 'NOTICE', '$thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', '$assistant_id: ' . $assistant_id);
    // back_trace( 'NOTICE', 'Shortcode Attributes: ' . print_r($atts, true));
    // back_trace( 'NOTICE', '$kchat_settings: ' . print_r($kchat_settings, true));
    // back_trace( 'NOTICE', '========================================');
    // foreach ($kchat_settings as $key => $value) {
    //     // back_trace( 'NOTICE', '$kchat_settings - Key: ' . $key . ' Value: ' . $value);
    // }
    // back_trace( 'NOTICE', '========================================');

    // EXAMPLE - Shortcode Attributes
    // [chatbot] - Default values, floating style, uses OpenAI's ChatGPT
    // [chatbot style="floating"] - Floating style, uses OpenAI's ChatGPT
    // [chatbot style="embedded"] - Embedded style, uses OpenAI's ChatGPT
    // [chatbot style="floating" assistant="primary"] - Floating style, GPT Assistant as set in Primary setting
    // [chatbot style="embedded" assistant="alternate"] - Embedded style, GPT Assistant as set in Alternate setting
    // [chatbot style-"floating" assistant="asst_xxxxxxxxxxxxxxxxxxxxxxxx"] - Floating style using a GPT Assistant ID
    // [chatbot style-"embedded" assistant="asst_xxxxxxxxxxxxxxxxxxxxxxxx"] - Embedded style using a GPT Assistant ID
    // [chatbot style="embedded" assistant="original" prompt="How do I install this plugin?"] - Floating style, uses OpenAI's ChatGPT
    // [chatbot style="floating" audience="all"] - Floating style for all audiences
    // [chatbot style="floating" audience="logged-in"] - Floating style for logged-in users only
    // [chatbot style="floating" audience="visitors"] - Floating style for visitors only
    // [chatbot style="floating" prompt="How do I install this plugin?"] - Floating style with a prompt
    // [chatbot style="embedded" prompt="How do I install this plugin?"] - Embedded style with a prompt
    // [chatbot style="floating" assistant="asst_xxxxxxxxxxxxxxxxxxxxxxxx" instructions="Please ensure that you ... "] - Floating style with additional instructions
    // [chatbot style="embedded" assistant="asst_xxxxxxxxxxxxxxxxxxxxxxxx" instructions="Please ensure that you ... "] - Embedded style with additional instructions
    //
    // Model Selection
    //
    // [chatbot style="floating" model="gpt-4-turbo-preview"] - Floating style using the GPT-4 Turbo Preview model
    // [chatbot style="embedded" model="dall-e-3"] - Embedded style using the DALL-E 3 model
    // [chatbot style="embedded" model="tts-1"] - Embedded style using the TTS 1 model
    // [chatbot style="embedded" model="tts-1-hd" voice="fable"] - Embedded style using the TTS 1 model with the voice of Fable

    // Normalize attribute keys, lowercase
    $atts = array_change_key_case((array)$atts, CASE_LOWER);

    // For each $atts, sanitize the shortcode data - Ver 1.9.9
    // Cross Site Scripting (XSS) vulnerability patch for 62801a58-b1ba-4c5a-bf93-7315d3553bb8
    foreach ($atts as $key => $value) {
        $atts[$key] = sanitize_text_field($value);
        $atts[$key] = htmlspecialchars(strip_tags($atts[$key] ?? ''), ENT_QUOTES, 'UTF-8');
        // DIAG - Diagnostics - Ver 2.0.6
        // back_trace( 'NOTICE', '$atts - Key: ' . $key . ' Value: ' . $value);
    }

    // DIAG - Diagnostics - Ver 2.0.8
    // back_trace( 'NOTICE', 'Tag Processing: ' . $tag);

    // Normalize [assistant-#] to [chatbot-#] - Ver 2.2.6 - 2025 03 07
    if (strpos($tag, 'assistant-') === 0) {
        $tag = str_replace('assistant-', 'chatbot-', $tag);
        // DIAG - Diagnostic - Ver 2.2.6
        // back_trace( 'NOTICE', 'Transformed assistant shortcode to: ' . $tag);
    }

    // Normalize [agent-#] to [chatbot-#] - Ver 2.2.6 - 2025 03 07
    if (strpos($tag, 'agent-') === 0) {
        $tag = str_replace('agent-', 'chatbot-', $tag);
        // DIAG - Diagnostic - Ver 2.2.6
        // back_trace( 'NOTICE', 'Transformed agent shortcode to: ' . $tag);
    }

    // Tag Processing - Ver 2.0.6
    if (strpos($tag, 'chatbot-') !== false) {
        
        // DIAG - Diagnostics - Ver 2.0.6
        // back_trace( 'NOTICE', 'Tag Processing: ' . $tag);

        // Extract the Assistant ID from the tag
        $assistant_key = str_replace('chatbot-', '', $tag);

        // Fetch the common name of the Assistant Common Name from the Assistant table
        $assistant_details = get_chatbot_chatgpt_assistant_by_key($assistant_key);

        // DIAG - Diagnostics - Ver 2.2.6
        // back_trace( 'NOTICE', 'Assistant Key: ' . $assistant_key);
        // back_trace( 'NOTICE', 'Assistant Details: ' . print_r($assistant_details, true));

        // For each key in $assistant_details, set the $atts value
        foreach ($assistant_details as $key => $value) {
            $atts[$key] = $value;
            // DIAG - Diagnostics - Ver 2.0.9
            // back_trace( 'ERROR', '$key: ' . $key . ' Value: ' . $value);
        }

        // If the assistant_id is null, then set it to original
        if (empty($assistant_details['assistant_id'])) {
            $assistant_details['assistant_id'] = 'original';
        }

        $atts['assistant'] = $assistant_details['assistant_id'];

        // Ensure $assistant_details is an array before proceeding
        if (!is_array($assistant_details)) {
            $assistant_details = []; // Initialize as an empty array if not an array
        }

        // For each key in $assistant_details, set the $atts value
        foreach ($assistant_details as $key => $value) {
            $atts[$key] = $value;
        }

        // Check if 'assistant_id' exists in $assistant_details before accessing it
        if (isset($assistant_details['assistant_id'])) {
            $atts['assistant'] = $assistant_details['assistant_id'];
        } else {
            $atts['assistant'] = null; // Or set a default value
        }

        // back_trace( 'NOTICE', '$assistant_details: ' . print_r($atts['assistant'], true));
        // back_trace( 'NOTICE', '$assistant_details: ' . print_r($assistant_details, true));
    }

    // If the assistant is not set to 'original', 'primary', or 'alternate' then try to fetch the Assistant details
    if ( !empty($atts['assistant']) && strpos($atts['assistant'], 'asst_') === false && strpos($atts['assistant'], 'ag:') === false && strpos($atts['assistant'], 'websearch') === false) {

        // Initialize the Assistant details
        $assistant_details = [];
        
        // Try to fetch the Assistant details from the Assistant table using the passed assistant $atts value
        $assistantCommonName = $atts['assistant'];

        // Utility
        $assistant_details = get_chatbot_chatgpt_assistant_by_common_name($assistantCommonName);

        // If no match is found, then the $assistant_details will be an empty array
        if (empty($assistant_details)) {

            // DIAG - Diagnostics - Ver 2.0.5
            // back_trace( 'NOTICE', 'No match found for the Assistant: ' . $assistantCommonName);

            // Set to original
            $chatbot_chatgpt_assistant_alias = 'original'; // default value

        } else {

            // DIAG - Diagnostics - Ver 2.0.5
            // back_trace( 'NOTICE', 'Match found for the Assistant: ' . $assistantCommonName);

            // DIAG - Diagnostics - Ver 2.0.4
            // back_trace( 'NOTICE', '$assistant_details: ' . print_r($assistant_details, true));

            foreach ($assistant_details as $key => $value) {
                $atts[$key] = $value;
            }

            // DIAG - Diagnostics - Ver 2.0.4
            // back_trace( 'NOTICE', 'AFTER $atts: ' . print_r($atts, true));

            // Set the assistant_id
            $atts['assistant'] = $assistant_details['assistant_id'];

            $chatbot_chatgpt_assistant_alias = $assistant_details['assistant_id'];

        }

    } elseif ( !empty($atts['assistant']) && (strpos($atts['assistant'], 'asst_') !== false || strpos($atts['assistant'], 'ag:') !== false || strpos($atts['assistant'], 'websearch') !== false) ) {

        // Set the assistant_id
        $chatbot_chatgpt_assistant_alias = $atts['assistant'];

    } else {
            
            // Default to 'original'
            $chatbot_chatgpt_assistant_alias = 'original'; // default value

    }

    // DIAG - Diagnostics - Ver 2.0.6
    // back_trace( 'NOTICE', '========================================');
    // back_trace( 'NOTICE', '$user_id: ' . $user_id);
    // back_trace( 'NOTICE', '$page_id: ' . $page_id);
    // back_trace( 'NOTICE', '$session_id: ' . $session_id);
    // back_trace( 'NOTICE', '$thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', '$assistant_id: ' . $assistant_id);
    // back_trace( 'NOTICE', '$atts: ' . print_r($atts, true));
    // back_trace( 'NOTICE', '$kchat_settings: ' . print_r($kchat_settings, true));
    // back_trace( 'NOTICE', '$assistant_details: ' . print_r($assistant_details, true));

    // Validate and sanitize the style parameter - Ver 1.9.9
    $valid_styles = ['floating', 'embedded'];
    $chatbot_chatgpt_display_style = 'floating'; // default value

    // Check if 'style' exists in $atts and is not null
    if (array_key_exists('style', $atts) && !is_null($atts['style'])) {
        // Check if the provided style is valid
        if (in_array($atts['style'], $valid_styles)) {
            // Sanitize and set the display style
            $chatbot_chatgpt_display_style = sanitize_text_field($atts['style']);
            $kchat_settings['chatbot_chatgpt_display_style'] = $chatbot_chatgpt_display_style;
            // back_trace( 'NOTICE', '$chatbot_chatgpt_display_style: ' . $chatbot_chatgpt_display_style);
        } else {
            // Handle invalid style by logging an error or taking other actions
            $chatbot_chatgpt_display_style = 'floating'; // default value
            $kchat_settings['chatbot_chatgpt_display_style'] = $chatbot_chatgpt_display_style;
            // back_trace( 'ERROR', 'Invalid display style: ' . sanitize_text_field($atts['style']));
            // back_trace( 'ERROR', 'Invalid display style: ' . $chatbot_chatgpt_display_style);
        }
        // Remove the 'style' key from the $atts array
        unset($atts['style']);
    }

    // See if there is a style transient already stored
    // $temp_chatbot_chatgpt_display_style = get_chatbot_chatgpt_transients('display_style', $user_id, $page_id, $session_id, null);

    // One bot per page, embedded over floating - Ver 2.1.6
    // if ($kchat_settings['chatbot_chatgpt_display_style'] == 'floating' && $temp_chatbot_chatgpt_display_style == 'embedded') {
    //     // back_trace( 'NOTICE', 'Embedded style selected over floating style');
    //     // End the shortcode processing
    //     // return;
    //     // $atts['style'] = 'embedded';
    //     $chatbot_chatgpt_display_style = 'embedded';
    //     $kchat_settings['chatbot_chatgpt_display_style'] = 'embedded';
    // }

    // Set the sanitized display style in $atts and $kchat_settings
    $atts['chatbot_chatgpt_display_style'] = $chatbot_chatgpt_display_style;
    $kchat_settings['chatbot_chatgpt_display_style'] = $chatbot_chatgpt_display_style;
    // back_trace( 'NOTICE', '$chatbot_chatgpt_display_style: ' . $chatbot_chatgpt_display_style);

    // Validate and sanitize the assistant parameter and set the assistant_id - Ver 1.9.9
    $valid_ids = ['original', 'primary', 'alternate'];
    $chatbot_chatgpt_assistant_alias = 'original'; // default value
    if (array_key_exists('assistant', $atts)) {
        $sanitized_assistant = sanitize_text_field($atts['assistant']);
        if (in_array($sanitized_assistant, $valid_ids) || strpos($sanitized_assistant, 'asst_') === 0 || strpos($sanitized_assistant, 'websearch') === 0 ) {
            $chatbot_chatgpt_assistant_alias = $sanitized_assistant;
            // back_trace( 'NOTICE', '$assistant_id: ' . $chatbot_chatgpt_assistant_alias);
        } else {
            // back_trace( 'ERROR', 'Invalid $assistant_id: ' . $sanitized_assistant);
        }
    }
    $assistant_id = $chatbot_chatgpt_assistant_alias;
    
    // Validate and sanitize the audience parameter - Ver 1.9.9
    $valid_audiences = ['all', 'logged-in', 'visitors'];
    $chatbot_chatgpt_audience_choice_global = esc_attr(get_option('chatbot_chatgpt_audience_choice', 'all'));
    $chatbot_chatgpt_audience_choice = $chatbot_chatgpt_audience_choice_global; // default value
    if (array_key_exists('audience', $atts)) {
        $sanitized_audience = sanitize_text_field($atts['audience']);
        if (in_array($sanitized_audience, $valid_audiences)) {
            $chatbot_chatgpt_audience_choice = $sanitized_audience;
            $kchat_settings['chatbot_chatgpt_audience_choice'] = $chatbot_chatgpt_audience_choice;
            // back_trace( 'NOTICE', '$chatbot_chatgpt_audience_choice: ' . $chatbot_chatgpt_audience_choice);
        } else {
            $chatbot_chatgpt_audience_choice = $chatbot_chatgpt_audience_choice_global;
            $atts['audience'] = $chatbot_chatgpt_audience_choice_global;
            $kchat_settings['chatbot_chatgpt_audience_choice'] = $chatbot_chatgpt_audience_choice_global;
            // back_trace( 'ERROR', 'Invalid audience choice: ' . $sanitized_audience);
        }
    }
    
    // Validate and sanitize the prompt parameter - Ver 1.9.9
    $chatbot_chatgpt_hot_bot_prompt = ''; // default value
    if (array_key_exists('prompt', $atts)) {
        $chatbot_chatgpt_hot_bot_prompt = sanitize_text_field($atts['prompt']);
        // back_trace( 'NOTICE', 'chatbot_chatgpt_hot_bot_prompt: ' . $chatbot_chatgpt_hot_bot_prompt);
    } elseif (isset($_GET['chatbot_prompt'])) {
        $chatbot_chatgpt_hot_bot_prompt = sanitize_text_field($_GET['chatbot_prompt']);
        // back_trace( 'NOTICE', 'chatbot_chatgpt_hot_bot_prompt: ' . $chatbot_chatgpt_hot_bot_prompt);
    }
    If (!empty($chatbot_chatgpt_hot_bot_prompt)) {
        $chatbot_chatgpt_hot_bot_prompt = preg_replace("/^\\\\'|\\\\'$/", '', $chatbot_chatgpt_hot_bot_prompt);
    }

    // Validate and sanitize the additional_instructions parameter - Ver 1.9.9
    $additional_instructions = ''; // default value
    if (array_key_exists('additional_instructions', $atts)) {
        $additional_instructions = sanitize_text_field($atts['additional_instructions']);
        $kchat_settings['chatbot_chatgpt_additional_instructions'] = $additional_instructions;
        // back_trace( 'NOTICE', '$additional_instructions: ' . $additional_instructions);
    }

    // Validate and sanitize the model parameter - Ver 1.9.9
    if (!isset($atts['model'])) {

        $chatbot_ai_platform_choice = esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI'));
        // DIAG - Diagnostics - Ver 2.2.1
        // back_trace( 'NOTICE', 'chatbot_ai_platform_choice: ' . $chatbot_ai_platform_choice);

        switch ($chatbot_ai_platform_choice) {
            case 'OpenAI':
                $model = esc_attr(get_option('chatbot_chatgpt_model_choice', 'gpt-3.5-turbo'));
                break;
            case 'NVIDIA':
                $model = esc_attr(get_option('chatbot_nvidia_model_choice', 'nvidia/llama-3.1-nemotron-51b-instruct'));
                break;
            case 'Anthropic':
                $model = esc_attr(get_option('chatbot_anthropic_model_choice', 'claude-3-5-sonnet-latest'));
                break;
            case 'DeepSeek':
                $model = esc_attr(get_option('chatbot_deepseek_model_choice', 'deepseek-chat'));
                break;
            case 'Mistral':
                $model = esc_attr(get_option('chatbot_mistral_model_choice', 'mistral-small-latest'));
                break;
            case 'Markov Chain':
                $model = esc_attr(get_option('chatbot_markov_chain_model_choice', 'markov-chain-flask'));
                break;
            case 'Transformer':
                $model = esc_attr(get_option('chatbot_transformer_model_choice', 'lexical-context-model'));
                break;
            case 'Local Server':
                $model = esc_attr(get_option('chatbot_local_model_choice', 'llama3.2-3b-instruct'));
                break;
            default:
                $model = esc_attr(get_option('chatbot_chatgpt_model_choice', 'gpt-3.5-turbo'));
        }

        $kchat_settings['model'] = $model;
        $kchat_settings['chatbot_chatgpt_model_choice'] = $model;
        $assistant_details['model'] = $model;
        $assistant_details['chatbot_chatgpt_model_choice'] = $model;
        $kchat_settings['model'] = $model;
        $kchat_settings['chatbot_chatgpt_model_choice'] = $model;
        // back_trace( 'NOTICE', 'Model (defaulting): ' . $model);

    } else {

        $model = sanitize_text_field($atts['model']);
        $kchat_settings['model'] = $model;
        $kchat_settings['chatbot_chatgpt_model_choice'] = $model;
        $assistant_details['model'] = $model;
        $assistant_details['chatbot_chatgpt_model_choice'] = $model;
        $kchat_settings['model'] = $model;
        $kchat_settings['chatbot_chatgpt_model_choice'] = $model;
        // back_trace( 'NOTICE', 'Model (paramater): ' . $model);

    }

    // Validate and sanitize the voice parameter - Ver 1.9.9
    $valid_voices = ['alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer', 'none'];
    $voice = 'alloy'; // default value
    if (array_key_exists('voice', $atts)) {
        $sanitized_voice = sanitize_text_field($atts['voice']);
        if (in_array($sanitized_voice, $valid_voices)) {
            $voice = $sanitized_voice;
            $kchat_settings['voice'] = $voice;
            $kchat_settings['chatbot_chatgpt_voice_option'] = $voice;
            $assistant_details['voice'] = $voice;
            $assistant_details['chatbot_chatgpt_voice_option'] = $voice;
            $kchat_settings['voice'] = $voice;
            $kchat_settings['chatbot_chatgpt_voice_option'] = $voice;
            // back_trace( 'NOTICE', '$voice: ' . $voice);
        } else {
            $voice = esc_attr(get_option('chatbot_chatgpt_voice_option', 'alloy'));
            $kchat_settings['voice'] = $voice;
            $kchat_settings['chatbot_chatgpt_voice_option'] = $voice;
            $assistant_details['voice'] = $voice;
            $assistant_details['chatbot_chatgpt_voice_option'] = $voice;
            $kchat_settings['voice'] = $voice;
            $kchat_settings['chatbot_chatgpt_voice_option'] = $voice;
            // back_trace( 'NOTICE', 'Voice (defaulting): ' . $voice);
        }
    } else {
        $voice = esc_attr(get_option('chatbot_chatgpt_voice_option', 'alloy'));
        $kchat_settings['voice'] = $voice;
        $kchat_settings['chatbot_chatgpt_voice_option'] = $voice;
        $assistant_details['voice'] = $voice;
        $assistant_details['chatbot_chatgpt_voice_option'] = $voice;
        $kchat_settings['voice'] = $voice;
        $kchat_settings['chatbot_chatgpt_voice_option'] = $voice;
        // back_trace( 'NOTICE', 'Voice (defaulting): ' . $voice);
    }

    // DIAG - Diagnostics - Ver 2.0.6
    // back_trace( 'NOTICE', '========================================');
    // back_trace( 'NOTICE', '$user_id: ' . $user_id);
    // back_trace( 'NOTICE', '$page_id: ' . $page_id);
    // back_trace( 'NOTICE', '$session_id: ' . $session_id);
    // back_trace( 'NOTICE', '$thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', '$assistant_id: ' . $assistant_id);
    // back_trace( 'NOTICE', '$chatbot_chatgpt_assistant_alias: ' . $chatbot_chatgpt_assistant_alias);
    // back_trace( 'NOTICE', '$kchat_settings: ' . print_r($kchat_settings, true));
    // back_trace( 'NOTICE', '$kchat_settings: ' . print_r($kchat_settings, true));

    // Determine if the user is logged in
    $user_logged_in = is_user_logged_in();
    If ($user_logged_in) {
        if ($chatbot_chatgpt_audience_choice == 'all' || $chatbot_chatgpt_audience_choice == 'logged-in') {
            // Ok to proceed
        } else {
            // Not ok to proceed
            // back_trace( 'NOTICE', 'User is logged in but the audience choice is not set to "all"');
            return;
        }
    } else {
        if ($chatbot_chatgpt_audience_choice == 'all' || $chatbot_chatgpt_audience_choice == 'visitors') {
            // Ok to proceed
        } else {
            // Not ok to proceed
            // back_trace( 'NOTICE', 'User is not logged in but the audience choice is not set to "all"');
            return;
        }
    }

    // Fetch the User ID - Updated Ver 2.0.6 - 2024 07 11
    $user_id = get_current_user_id();
    // Fetch the Kognetiks cookie
    $session_id = kognetiks_get_unique_id();
    if (empty($user_id) || $user_id == 0) {
        $user_id = $session_id;
    }
    // back_trace( 'NOTICE', '$user_id: ' . $user_id);
    // back_trace( 'NOTICE', '$session_id: ' . $session_id);

    // Fetch the Page ID
    $page_id = get_the_id(); // Get current page ID
    if (empty($page_id)) {
        // $page_id = get_queried_object_id(); // Get the ID of the queried object if $page_id is not set
        // CHANGED - Ver 1.9.1 - 2024 03 05
        $page_id = get_the_id(); // Get current page ID
    }

    // Fetch the Kognetiks cookie
    $session_id = kognetiks_get_unique_id();

    // Set the display style and the assistant alias
    if ( $chatbot_chatgpt_assistant_alias == 'primary' ) {
        $assistant_id = esc_attr(get_option('assistant_id', ''));
        $additional_instructions = esc_attr(get_option('chatbot_chatgpt_additional_instructions', ''));
    } elseif ( $chatbot_chatgpt_assistant_alias == 'alternate' ) {
        $assistant_id = esc_attr(get_option('chatbot_chatgpt_assistant_id_alternate', ''));
        $additional_instructions = esc_attr(get_option('chatbot_chatgpt_additional_instructions_alternate', ''));
    } else {
        // Do nothing as either the assistant_id is set to the GPT Assistant ID or it is not set at all
        $additional_instructions = array_key_exists('instructions', $atts) ? sanitize_text_field($atts['instructions']) : '';
        $additional_instructions = array_key_exists('additional_instructions', $atts) ? sanitize_text_field($atts['additional_instructions']) : '';
        $additional_details['additional_instructions'] = $additional_instructions;
        $kchat_settings['additional_instructions'] = $additional_instructions;
    }

    //Do this for additional instructions - Ver 2.0.9
    if (array_key_exists('additional_instructions', $atts)) {

        // DIAG - Diagnostics - Ver 2.0.9
        // back_trace( 'NOTICE', 'additional_instructions: ' . $atts['additional_instructions']);

        $sanitized_additional_instructions = sanitize_text_field($atts['additional_instructions']);

        if (is_user_logged_in()) {

            $current_user_id = get_current_user_id();
            $current_user = get_userdata($current_user_id);

            // Determine what the field name is between the brackets
            $user_field_name = '';
            $user_field_name = substr($sanitized_additional_instructions, strpos($sanitized_additional_instructions, '[') + 1, strpos($sanitized_additional_instructions, ']') - strpos($sanitized_additional_instructions, '[') - 1);
            // back_trace( 'NOTICE', '$user_field_name: ' . $user_field_name);
            // If $additional_instructions contains "[$user_field_name]" then replace with field from DB
            if (strpos($sanitized_additional_instructions, '[' . $user_field_name . ']') !== false) {
                $sanitized_additional_instructions = str_replace('[' . $user_field_name . ']', $current_user->$user_field_name, $sanitized_additional_instructions);
            } else {
                $sanitized_additional_instructions = str_replace('[' . $user_field_name . ']', '', $sanitized_additional_instructions);
                // Remove the extra space when two spaces are present
                $sanitized_additional_instructions = str_replace('  ', ' ', $sanitized_additional_instructions);
                // Remove the extra space before punctuation including period, comma, exclamation mark, and question mark
                $sanitized_additional_instructions = preg_replace('/\s*([.,!?])/', '$1', $sanitized_additional_instructions);
            }

        } else {

            $user_field_name = '';
            $user_field_name = substr($sanitized_additional_instructions, strpos($sanitized_additional_instructions, '[') + 1, strpos($sanitized_additional_instructions, ']') - strpos($sanitized_additional_instructions, '[') - 1);
            // back_trace( 'NOTICE', '$user_field_name: ' . $user_field_name);
            $sanitized_additional_instructions = str_replace('[' . $user_field_name . ']', '', $sanitized_additional_instructions);
            // Remove the extra space when two spaces are present
            $sanitized_additional_instructions = str_replace('  ', ' ', $sanitized_additional_instructions);
            // Remove the extra space before punctuation including period, comma, exclamation mark, and question mark
            $sanitized_additional_instructions = preg_replace('/\s*([.,!?])/', '$1', $sanitized_additional_instructions);

        }

        $kchat_settings['additional_instructions'] = $sanitized_additional_instructions;
        $assistant_details['additional_instructions'] = $sanitized_additional_instructions;
        $kchat_settings['additional_instructions'] = $sanitized_additional_instructions;
        $additional_instructions = $sanitized_additional_instructions;

        // DIAG - Diagnostics - Ver 2.0.9
        // back_trace( 'NOTICE', '$sanitized_additional_instructions: ' . $sanitized_additional_instructions);
    
    }    

    // Fetch the User ID - Updated Ver 2.0.6 - 2024 07 11
    $user_id = get_current_user_id();
    // Fetch the Kognetiks cookie
    $session_id = kognetiks_get_unique_id();
    if (empty($user_id) || $user_id == 0) {
        $user_id = $session_id;
    }
    // back_trace( 'NOTICE', '$user_id: ' . $user_id);
    // back_trace( 'NOTICE', '$session_id: ' . $session_id);

    // Check that $page_id is not empty or null - Ver 2.1.1.1
    if (empty($page_id) || is_null($page_id)) {
        $page_id = 999999;
    }

    // FIXME - Check for the presence of an embedded chatbot - Ver 2.1.7
    // back_trace( 'NOTICE', 'get_chatbot_chatgpt_transients: ' . get_chatbot_chatgpt_transients('display_style', $user_id, $page_id, $session_id));
    // if (get_chatbot_chatgpt_transients('display_style', $user_id, $page_id, $session_id) == 'embedded') {
    //     // back_trace( 'NOTICE', 'Embedded chatbot detected');
    //     $chatbot_chatgpt_display_style = 'embedded';
    //     return;
    // }

    set_chatbot_chatgpt_transients( 'display_style' , $chatbot_chatgpt_display_style, $user_id, $page_id, $session_id, null );
    set_chatbot_chatgpt_transients( 'assistant_alias' , $chatbot_chatgpt_assistant_alias, $user_id, $page_id, $session_id, null );
    
    set_chatbot_chatgpt_transients( 'assistant_id', $assistant_id, $user_id, $page_id, $session_id, null);
    set_chatbot_chatgpt_transients( 'thread_id', $thread_id, $user_id, $page_id, $session_id, null);

    set_chatbot_chatgpt_transients( 'additional_instructions', $additional_instructions, $user_id, $page_id, $session_id, null);

    // back_trace( 'NOTICE', '$chatbot_chatgpt_display_style: ' . $chatbot_chatgpt_display_style);

    // Set visitor and logged in user limits - Ver 2.0.1
    if (is_user_logged_in()) {
        // back_trace( 'NOTICE', 'User is logged in');
        $kchat_settings['chatbot_chatgpt_message_limit_setting'] = esc_attr(get_option('chatbot_chatgpt_user_message_limit_setting', '999'));
        $kchat_settings['chatbot_chatgpt_message_limit_period_setting'] = esc_attr(get_option('chatbot_chatgpt_user_message_limit_period_setting', 'Lifetime'));
        $kchat_settings['chatbot_chatgpt_display_message_count'] = esc_attr(get_option('chatbot_chatgpt_display_message_count', 'No'));
    } else {
        // back_trace( 'NOTICE', 'User is NOT logged in');
        $kchat_settings['chatbot_chatgpt_message_limit_setting'] = esc_attr(get_option('chatbot_chatgpt_visitor_message_limit_setting', '999'));
        $kchat_settings['chatbot_chatgpt_message_limit_period_setting'] = esc_attr(get_option('chatbot_chatgpt_visitor_message_limit_period_setting', 'Lifetime'));
        $kchat_settings['chatbot_chatgpt_display_message_count'] = esc_attr(get_option('chatbot_chatgpt_display_message_count', 'No'));
    }

    // Localize the data for the chatbot - Ver 2.1.1.1 - 2024 08 28 - THIS IS THE SPOT
    $kchat_settings = array_merge($kchat_settings, array(
        'chatbot_chatgpt_display_style' => $chatbot_chatgpt_display_style,
        'chatbot_chatgpt_version' => $chatbot_chatgpt_plugin_version,
        'plugins_url' => $chatbot_chatgpt_plugin_dir_url,
        'ajax_url' => admin_url('admin-ajax.php'),
        'user_id' => $user_id,
        'page_id' => $page_id,
        'session_id' => $session_id,
        'thread_id' => $thread_id,
        'assistant_id' => $assistant_id,
        'additional_instructions' => $additional_instructions,
        'model' => $model,
        'voice' => $voice,
        'chatbot_chatgpt_timeout_setting' => esc_attr(get_option('chatbot_chatgpt_timeout_setting', '240')),
        'chatbot_chatgpt_avatar_icon_setting' => esc_attr(get_option('chatbot_chatgpt_avatar_icon_setting', '')),
        'chatbot_chatgpt_custom_avatar_icon_setting' => esc_attr(get_option('chatbot_chatgpt_custom_avatar_icon_setting', '')),
        'chatbot_chatgpt_avatar_greeting_setting' => esc_attr(get_option('chatbot_chatgpt_avatar_greeting_setting', 'Howdy!!! Great to see you today! How can I help you?')),
        'chatbot_chatgpt_force_page_reload' => esc_attr(get_option('chatbot_chatgpt_force_page_reload', 'No')),
        'chatbot_chatgpt_custom_error_message' => esc_attr(get_option('chatbot_chatgpt_custom_error_message', 'Your custom error message goes here.')),
        'chatbot_chatgpt_appearance_open_icon' => esc_attr(get_option('chatbot_chatgpt_appearance_open_icon', '')),
        'chatbot_chatgpt_appearance_collapse_icon' => esc_attr(get_option('chatbot_chatgpt_appearance_collapse_icon', '')),
        'chatbot_chatgpt_appearance_erase_icon' => esc_attr(get_option('chatbot_chatgpt_appearance_erase_icon', '')),
        'chatbot_chatgpt_appearance_mic_enabled_icon' => esc_attr(get_option('chatbot_chatgpt_appearance_mic_enabled_icon', '')),
        'chatbot_chatgpt_appearance_mic_disabled_icon' => esc_attr(get_option('chatbot_chatgpt_appearance_mic_disabled_icon', '')),
    ));

    // back_trace( 'NOTICE', '$kchat_settings after array_merge: ' . print_r($kchat_settings, true));

    // DIAG - Diagnostics - Ver 1.8.6
    // back_trace( 'NOTICE', '========================================');
    // back_trace( 'NOTICE', '$user_id: ' . $user_id);
    // back_trace( 'NOTICE', '$page_id: ' . $page_id);
    // back_trace( 'NOTICE', '$session_id: ' . $session_id);
    // back_trace( 'NOTICE', '$thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', '$assistant_id: ' . $assistant_id);
    // back_trace( 'NOTICE', '$chatbot_chatgpt_assistant_alias: ' . $chatbot_chatgpt_assistant_alias);
    // back_trace( 'NOTICE', '$kchat_settings: ' . print_r($kchat_settings, true));
    // back_trace( 'NOTICE', '$kchat_settings: ' . print_r($kchat_settings, true));
    // back_trace( 'NOTICE', '$voice: ' . $voice);
    // back_trace( 'NOTICE', '$model: ' . $model);

    // Retrieve the bot name - Ver 2.0.5
    $chatbot_ai_platform_choice = esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI'));
    if ($chatbot_ai_platform_choice == 'OpenAI'){
        $use_assistant_name = esc_attr(get_option('chatbot_chatgpt_display_custom_gpt_assistant_name', 'Yes'));
    } elseif ($chatbot_ai_platform_choice == 'Azure OpenAI'){
        $use_assistant_name = esc_attr(get_option('chatbot_azure_display_custom_gpt_assistant_name', 'Yes'));
    } elseif ($chatbot_ai_platform_choice == 'Claude'){
    } else {
        $use_assistant_name = 'No';
    }

    // Assistant's Table Override - Ver 2.0.4
    if (!empty($assistant_details['show_assistant_name'])) {
        $use_assistant_name = $assistant_details['show_assistant_name'];
    }

    // DIAG - Diagnostics - Ver 2.0.5
    // back_trace( 'NOTICE', '$use_assistant_name: ' . $use_assistant_name);
    // back_trace( 'NOTICE', '$assistant_id: ' . $assistant_id);
    // back_trace( 'NOTICE', '$assistant_details: ' . print_r($assistant_details, true));

    // FIXME - $assistant_id is empty - Ver 2.2.6
    if ($use_assistant_name == 'Yes' && !empty($assistant_id) && $assistant_id !== 'original') {
        // DIAG - Diagnostics - Ver 2.2.6
        // back_trace( 'NOTICE', '$assistant_id: ' . $assistant_id);
        // back_trace( 'NOTICE', '$use_assistant_name: ' . $use_assistant_name);
        $assistant_name = esc_attr(get_chatbot_chatgpt_assistant_name($assistant_id));
        // back_trace( 'NOTICE', '$assistant_name: ' . $assistant_name);
        $bot_name = !empty($assistant_name) ? $assistant_name : esc_attr(get_option('chatbot_chatgpt_bot_name', 'Kognetiks Chatbot'));
    } else {
        $bot_name = esc_attr(get_option('chatbot_chatgpt_bot_name', 'Kognetiks Chatbot'));
    }

    // MOVED FURTHER DOWN - Ver 2.1.2 - 2024 08 28
    // $kchat_settings['chatbot_chatgpt_version'] = $chatbot_chatgpt_plugin_version;
    // $kchat_settings_json = wp_json_encode($kchat_settings);
    // $escaped_kchat_settings_json = esc_js($kchat_settings_json);
    // wp_add_inline_script('chatbot-chatgpt-local-js', 'if (typeof kchat_settings === "undefined") { var kchat_settings = ' . $escaped_kchat_settings_json . '; } else { kchat_settings = ' . $escaped_kchat_settings_json . '; }', 'before');
    // wp_add_inline_script('chatbot-chatgpt-js', 'if (typeof kchat_settings === "undefined") { var kchat_settings = ' . $escaped_kchat_settings_json . '; } else { kchat_settings = ' . $escaped_kchat_settings_json . '; }', 'before');

    $chatbot_chatgpt_bot_prompt = esc_attr(get_option('chatbot_chatgpt_bot_prompt', 'Enter your question ...'));

    // back_trace( 'NOTICE', '$chatbot_chatgpt_hot_bot_prompt: ' . $chatbot_chatgpt_hot_bot_prompt);

    // Hot Prompt the Chatbot - Ver 1.9.0
    if (!empty($chatbot_chatgpt_hot_bot_prompt)) {
        // back_trace( 'NOTICE', 'Hot Prompting the Chatbot');
            wp_add_inline_script('chatbot-chatgpt-js', '
            if (typeof kchat_settings === "undefined") { 
                var kchat_settings = ' . $kchat_settings_json . '; 
            } else { 
                kchat_settings = ' . $kchat_settings_json . '; 
            }
            document.getElementById("chatbot-chatgpt-message").placeholder = "' . $chatbot_chatgpt_hot_bot_prompt . '";
        ', 'before');
    }

    // Assistant's Table Override - Ver 2.0.4
    // FIXME - HOT BOT PROMPT

    // Allow File Uploads - Ver 1.9.0
    $chatbot_chatgpt_allow_file_uploads = 'No';
    $chatbot_chatgpt_allow_mp3_uploads = 'No';

    if ($chatbot_chatgpt_assistant_alias == 'original') {
        $chatbot_chatgpt_allow_file_uploads = 'No';
        $chatbot_chatgpt_allow_mp3_uploads = 'No';
    }

    if (strpos($chatbot_chatgpt_assistant_alias,'asst_') !== false) {
        $chatbot_chatgpt_allow_file_uploads = esc_attr(get_option('chatbot_chatgpt_allow_file_uploads', 'No'));

        // Assistant's Table Override - Ver 2.0.4
        if ( !empty($assistant_details['allow_file_uploads']) ) {
            $chatbot_chatgpt_allow_file_uploads = $assistant_details['allow_file_uploads'];
        }

        $chatbot_chatgpt_allow_mp3_uploads = 'No';
    }

    if (strpos($model, 'whisper') !== false) {
        $chatbot_chatgpt_allow_file_uploads = 'No';
        $chatbot_chatgpt_allow_mp3_uploads = 'Yes';
    }

    if (strpos($model, 'gpt-4o') !== false && strpos($chatbot_chatgpt_assistant_alias, 'asst_') === false && $chatbot_chatgpt_assistant_alias !== 'original') {
        // $chatbot_chatgpt_allow_file_uploads = esc_attr(get_option('chatbot_chatgpt_allow_file_uploads', 'No'));
        $chatbot_chatgpt_allow_file_uploads = 'No';
        $chatbot_chatgpt_allow_mp3_uploads = 'No';
    }

    if (strpos($model, 'gpt-4o') !== false) {
        $chatbot_chatgpt_allow_file_uploads = esc_attr(get_option('chatbot_chatgpt_allow_file_uploads', 'No'));
        $chatbot_chatgpt_allow_mp3_uploads = 'No';
    }

    if (strpos($chatbot_chatgpt_assistant_alias, 'asst_') !== false) {
        $chatbot_chatgpt_allow_file_uploads = esc_attr(get_option('chatbot_chatgpt_allow_file_uploads', 'No'));
        $chatbot_chatgpt_allow_mp3_uploads = 'No';
    }

    // FIXME - Allow File Uploads - Ver 2.2.6
    // back_trace( 'NOTICE', '$chatbot_chatgpt_allow_file_uploads: ' . $chatbot_chatgpt_allow_file_uploads);
    // $chatbot_chatgpt_allow_file_uploads = 'Yes';

    // Allow Upload Files - Ver 2.0.4
    $chatbot_chatgpt_allow_file_uploads = !empty($assistant_details['allow_file_uploads']) ? $assistant_details['allow_file_uploads'] : $chatbot_chatgpt_allow_file_uploads;

    // Allow Read Aloud - Ver 2.0.5
    $chatbot_chatgpt_read_aloud_option = esc_attr(get_option('chatbot_chatgpt_read_aloud_option', 'yes'));
    // Assistant's Table Override - Ver 2.0.4
    $chatbot_chatgpt_read_aloud_option = !empty($assistant_details['allow_read_allow']) ? $assistant_details['allow_read_allow'] : $chatbot_chatgpt_read_aloud_option;

    // Allow Download Transcript - Ver 2.0.5
    $chatbot_chatgpt_allow_download_transcript = esc_attr(get_option('chatbot_chatgpt_allow_download_transcript', 'Yes'));
    // Check if the key exists and is not empty, otherwise use default
    $chatbot_chatgpt_allow_download_transcript = isset($assistant_details['allow_transcript_downloads']) && !empty($assistant_details['allow_transcript_downloads']) ? $assistant_details['allow_transcript_downloads'] : $chatbot_chatgpt_allow_download_transcript;

    // Force Page Reload on Conversation Clear - Ver 2.0.4
    $chatbot_chatgpt_force_page_reload = esc_attr(get_option('chatbot_chatgpt_force_page_reload', 'No'));

    // Conversation Continuation - Ver 2.0.7
    $chatbot_chatgpt_conversation_continuation = esc_attr(get_option('chatbot_chatgpt_conversation_continuation', 'Off'));

    // Assistant's Table Override - Ver 2.0.4
    // FIXME - FORCE PAGE RELOAD

    // Assume that the chatbot is NOT using KFlow - Ver 1.9.5
    $kflow_enabled = false;
    $kflow_enabled = esc_attr(get_option( 'kflow_flow_mode', false ));
    // back_trace( 'NOTICE', '$kflow_enabled: ' . esc_attr(get_option( 'kflow_flow_mode', false )));

    // Retrieve the custom buttons on/off setting - Ver 1.6.5
    // $chatbot_chatgpt_enable_custom_buttons = esc_attr(get_option('chatbot_chatgpt_enable_custom_buttons', 'Off'));

    // DIAG - Diagnostics - Ver 2.0.9
    // back_trace( 'NOTICE', 'kflow_prompt_and_response function status: ' . function_exists('kflow_prompt_and_response'));
    // back_trace( 'NOTICE', '$atts[\'sequence\']: ' . $atts['sequence']);

    // KFlow - Call kflow_prompt_and_response() - Ver 1.9.5
    if (function_exists('kflow_prompt_and_response') && !empty($atts['sequence']) && $kflow_enabled) {

        // BELT & SUSPENDERS - Ver 1.9.5
        $kflow_enabled = true;

        // Get the sequence ID
        $sequence_id = array_key_exists('sequence', $atts) ? sanitize_text_field($atts['sequence']) : '';

        // Fetch the KFlow data
        $kflow_data = kflow_get_sequence_data($sequence_id);

        // Set up the sequence
        set_transient('kflow_sequence', $sequence_id);
        set_transient('kflow_step', 0);

        // Set transients
        set_chatbot_chatgpt_transients('kflow_sequence', $sequence_id, null, null, $session_id);
        set_chatbot_chatgpt_transients('kflow_step', 0, null, null, $session_id);

        // Get the first prompt
        $kflow_prompt = $kflow_data['Prompts'][0];

        // // DIAG - Diagnostics - Ver 1.9.5
        // back_trace( 'NOTICE', '$kflow_data: ' . print_r($kflow_data, true));
        // back_trace( 'NOTICE', '$kchat_settings: ' . print_r($kchat_settings, true));
        // back_trace( 'NOTICE', '$kflow_prompt: ' . $kflow_prompt);

        // A prompt was returned
        if ( $kflow_prompt != '' ) {

            // Set transients
            set_chatbot_chatgpt_transients('kflow_sequence', $sequence_id, null, null, $session_id);
            set_chatbot_chatgpt_transients('kflow_step', -1, null, null, $session_id); // Start at -1 not 0

            // Get the first prompt
            $kflow_prompt = $kflow_data['Prompts'][0];

            // A prompt was returned
            // Pass to the Chatbot
            // To ask the visitor to complete the prompt
            $chatbot_chatgpt_hot_bot_prompt = '[Chatbot]' . $kflow_prompt;

            // Override the $model and set it to 'flow'
            $model = 'flow';

        } else {

            // BELT & SUSPENDERS - Ver 1.9.5
            $kflow_enabled = false;

            // No prompt was returned
            // Use the default prompt
            $chatbot_chatgpt_hot_bot_prompt = '';

            // BELT & SUSPENDERS - Ver 1.9.5
            // $model = esc_attr(get_option('chatbot_chatgpt_model_choice', 'gpt-3.5-turbo'));
            // $kchat_settings['model'] = $model;

        }

    } else {

        // Handle the case where the function does not exist
        // Throw an error or return a default value, etc.
        // DIAG - Diagnostics - Ver 1.9.5
        // back_trace( 'ERROR', 'kflow modules not installed');

    }
    
    // Miscellaneous Other Setting to pass to localStorage - Ver 2.0.5
    $chatbot_chatgpt_width_setting = esc_attr(get_option('chatbot_chatgpt_width_setting', 'Narrow'));
    // $assistant_details['chatbot_chatgpt_width_setting'] = $chatbot_chatgpt_width_setting;
    $kchat_settings['chatbot_chatgpt_width_setting'] = $chatbot_chatgpt_width_setting;

    $chatbot_chatgpt_start_status = esc_attr(get_option('chatbot_chatgpt_start_status', 'open'));
    // $assistant_details['chatbot_chatgpt_start_status'] = $chatbot_chatgpt_start_status;
    $kchat_settings['chatbot_chatgpt_start_status'] = $chatbot_chatgpt_start_status;

    $chatbot_chatgpt_start_status_new_visitor = esc_attr(get_option('chatbot_chatgpt_start_status_new_visitor', 'closed'));
    // $assistant_details['chatbot_chatgpt_start_status_new_visitor'] = $chatbot_chatgpt_start_status_new_visitor;
    $kchat_settings['chatbot_chatgpt_start_status_new_visitor'] = $chatbot_chatgpt_start_status_new_visitor;

    // Fetch and update initial greeting
    $assistant_details = options_helper($assistant_details, 'initial_greeting', 'Hello! How can I help you today?');
    // Fetch and update subsequent greeting
    $assistant_details = options_helper($assistant_details, 'subsequent_greeting', 'Hello again! How can I help you?');

    // Use enqueue_greetings_script and handle its return
    $modified_greetings = enqueue_greetings_script($assistant_details['initial_greeting'], $assistant_details['subsequent_greeting']);

    // Assuming enqueue_greetings_script returns an associative array with 'initial_greeting' and 'subsequent_greeting' keys
    if (is_array($modified_greetings) && isset($modified_greetings['initial_greeting']) && isset($modified_greetings['subsequent_greeting'])) {
        $assistant_details['initial_greeting'] = $modified_greetings['initial_greeting'];
        $assistant_details['subsequent_greeting'] = $modified_greetings['subsequent_greeting'];
    }

    $assistant_details['chatbot_chatgpt_initial_greeting'] = $assistant_details['initial_greeting'];
    $kchat_settings['chatbot_chatgpt_initial_greeting'] = $assistant_details['initial_greeting'];

    $assistant_details['chatbot_chatgpt_subsequent_greeting'] = $assistant_details['subsequent_greeting'];
    $kchat_settings['chatbot_chatgpt_subsequent_greeting'] = $assistant_details['subsequent_greeting'];

    // DIAG - Diagnostics - Ver 2.0.5
    // back_trace( 'NOTICE', '$modified_greetings: ' . print_r($modified_greetings, true));

    // DiAG - Diagnostics - Ver 2.0.9
    // back_trace( 'ERROR', '========================================');
    // back_trace( 'ERROR', '$assistant_details[\'style\']: ' . $assistant_details['style']);
    // back_trace( 'ERROR', '$kchat_settings[\'chatbot_chatgpt_display_style\']: ' . $kchat_settings['chatbot_chatgpt_display_style']);
    // back_trace( 'ERROR', '$assistant_details[\'audience\']: ' . $assistant_details['audience']);
    // back_trace( 'ERROR', '$kchat_settings[\'chatbot_chatgpt_audience_choice\']: ' . $kchat_settings['chatbot_chatgpt_audience_choice']);

    // Last chance to set localStorage - Ver 2.0.5
    // back_trace( 'NOTICE', 'BEFORE: $assistant_details[\'style\']: ' . $assistant_details['style']);
    // $assistant_details['style'] = !empty($assistant_details['style']) ? $assistant_details['style'] : esc_attr(get_option('chatbot_chatgpt_display_style', 'floating'));
    // back_trace( 'NOTICE', 'AFTER: $assistant_details[\'style\']: ' . $assistant_details['style']);
    // $kchat_settings['chatbot_chatgpt_display_style'] = $assistant_details['style'];

    $assistant_details['audience'] = !empty($assistant_details['audience']) ? $assistant_details['audience'] : esc_attr(get_option('chatbot_chatgpt_audience_choice', 'All'));
    $kchat_settings['chatbot_chatgpt_audience_choice'] = $assistant_details['audience'];
    
    // DIAG - Diagnostics - Ver 2.0.5
    // back_trace( 'NOTICE', 'BEFORE: $assistant_details[\'voice\']: ' . $assistant_details['voice']);
    // back_trace( 'NOTICE', 'BEFORE: $kchat_settings[\'chatbot_chatgpt_voice_option\']: ' . $kchat_settings['chatbot_chatgpt_voice_option']);
    // back_trace( 'NOTICE', 'BEFORE: $kchat_settings[\'voice\']: ' . $kchat_settings['voice']);
    
    $assistant_details['voice'] = !empty($assistant_details['voice']) ? $assistant_details['voice'] : esc_attr(get_option('chatbot_chatgpt_voice_option', 'alloy'));
    $kchat_settings['chatbot_chatgpt_voice_option'] = $assistant_details['voice'];
    $kchat_settings['voice'] = $assistant_details['voice'];
    set_chatbot_chatgpt_transients('voice', $assistant_details['voice'], $user_id, $page_id, $session_id, null);

    // DIAG - Diagnostics - Ver 2.0.5
    // back_trace( 'NOTICE', 'AFTER: $assistant_details[\'voice\']: ' . $assistant_details['voice']);
    // back_trace( 'NOTICE', 'AFTER: $kchat_settings[\'chatbot_chatgpt_voice_option\']: ' . $kchat_settings['chatbot_chatgpt_voice_option']);
    // back_trace( 'NOTICE', 'AFTER: $kchat_settings[\'voice\']: ' . $kchat_settings['voice']);

    $assistant_details['allow_file_uploads'] = !empty($assistant_details['allow_file_uploads']) ? $assistant_details['allow_file_uploads'] : esc_attr(get_option('chatbot_chatgpt_allow_file_uploads', 'No'));
    $kchat_settings['chatbot_chatgpt_allow_file_uploads'] = $assistant_details['allow_file_uploads'];
    // DIAG - Diagnostics - Ver 2.2.6
    // back_trace( 'NOTICE', '$assistant_details[\'allow_file_uploads\']: ' . $assistant_details['allow_file_uploads']);
    $chatbot_chatgpt_allow_file_uploads = $assistant_details['allow_file_uploads'];

    $assistant_details['allow_mp3_uploads'] = !empty($assistant_details['allow_mp3_uploads']) ? $assistant_details['allow_mp3_uploads'] : esc_attr(get_option('chatbot_chatgpt_allow_mp3_uploads', 'No'));
    $kchat_settings['chatbot_chatgpt_allow_mp3_uploads'] = $assistant_details['allow_mp3_uploads'];

    $assistant_details['allow_read_aloud'] = !empty($assistant_details['allow_read_aloud']) ? $assistant_details['allow_read_aloud'] : esc_attr(get_option('chatbot_chatgpt_read_aloud_option', 'yes'));
    $kchat_settings['chatbot_chatgpt_read_aloud_option'] = $assistant_details['allow_read_aloud'];

    $assistant_details['allow_transcript_downloads'] = !empty($assistant_details['allow_transcript_downloads']) ? $assistant_details['allow_transcript_downloads'] : esc_attr(get_option('chatbot_chatgpt_allow_download_transcript', 'Yes'));
    $kchat_settings['chatbot_chatgpt_allow_download_transcript'] = $assistant_details['allow_transcript_downloads'];

    $assistant_details['additional_instructions'] = !empty($assistant_details['additional_instructions']) ? $assistant_details['additional_instructions'] : esc_attr(get_option('chatbot_chatgpt_additional_instructions', ''));
    $kchat_settings['chatbot_chatgpt_additional_instructions'] = $assistant_details['additional_instructions'];

    $assistant_details['force_page_reload'] = !empty($assistant_details['force_page_reload']) ? $assistant_details['force_page_reload'] : esc_attr(get_option('chatbot_chatgpt_force_page_reload', 'No'));
    $kchat_settings['chatbot_chatgpt_force_page_reload'] = $assistant_details['force_page_reload'];

    $assistant_details['conversation_continuation'] = !empty($assistant_details['conversation_continuation']) ? $assistant_details['conversation_continuation'] : esc_attr(get_option('chatbot_chatgpt_conversation_continuation', 'Off'));
    $kchat_settings['chatbot_chatgpt_conversation_continuation'] = $assistant_details['conversation_continuation'];

    $assistant_details['width'] = !empty($assistant_details['width']) ? $assistant_details['width'] : esc_attr(get_option('chatbot_chatgpt_width_setting', '300'));
    $kchat_settings['chatbot_chatgpt_width_setting'] = $assistant_details['width'];

    $assistant_details['common_name'] = !empty($assistant_details['common_name']) ? $assistant_details['common_name'] : esc_attr(get_option('chatbot_chatgpt_bot_name', 'Kognetiks Chatbot'));
    $kchat_settings['chatbot_chatgpt_bot_name'] = $assistant_details['common_name'];
    $kchat_settings['chatbot_chatgpt_bot_name'] = !empty($assistant_details['common_name']) ? $assistant_details['common_name'] : esc_attr(get_option('chatbot_chatgpt_bot_name', 'Kognetiks Chatbot'));

    // THIS WAS HIGHER UP
    $kchat_settings['chatbot_chatgpt_version'] = $chatbot_chatgpt_plugin_version;
    $kchat_settings_json = wp_json_encode($kchat_settings);
    wp_add_inline_script('chatbot-chatgpt-local-js', 'if (typeof kchat_settings === "undefined") { var kchat_settings = ' . $kchat_settings_json . '; } else { kchat_settings = ' . $kchat_settings_json . '; }', 'before');
    wp_add_inline_script('chatbot-chatgpt-js', 'if (typeof kchat_settings === "undefined") { var kchat_settings = ' . $kchat_settings_json . '; } else { kchat_settings = ' . $kchat_settings_json . '; }', 'before');
    
    // DIAG - Diagnostics - Ver 2.1.0
    // back_trace( 'NOTICE', '========================================');
    // back_trace( 'NOTICE', '$atts: ' . print_r($atts, true));
    // back_trace( 'NOTICE', '$assistant_details: ' . print_r($assistant_details, true));
    // back_trace( 'NOTICE', '$kchat_settings: ' . print_r($kchat_settings, true));

    $kchat_settings['chatbot_chatgpt_avatar_icon_setting'] = esc_attr(get_option('chatbot_chatgpt_avatar_icon_setting', ''));
    $kchat_settings['chatbot_chatgpt_custom_avatar_icon_setting'] = esc_attr(get_option('chatbot_chatgpt_custom_avatar_icon_setting', ''));

    ?>
    <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function() {
            let kchat_settings = <?php echo json_encode($kchat_settings); ?>;
            if (kchat_settings && typeof kchat_settings === "object") {
                // Resolve LocalStorage - Ver 2.1.1.1.R2
                const includeKeys = [
                    'chatbot_chatgpt_last_reset',
                    'chatbot_chatgpt_message_count',
                    'chatbot_chatgpt_display_message_count',
                    'chatbot_chatgpt_message_limit_setting',
                    'chatbot_chatgpt_message_limit_period_setting',
                    'chatbot_chatgpt_start_status',
                    'chatbot_chatgpt_start_status_new_visitor',
                    'chatbot_chatgpt_opened',
                    'chatbot_chatgpt_last_reset'
                ];
                Object.keys(kchat_settings).forEach(function(key) {
                    if (includeKeys.includes(key)) {
                        localStorage.setItem(key, kchat_settings[key]);
                        // DiAG - Ver 2.1.1.1
                        // console.log("Chatbot: NOTICE: chatbot-shortcode.php - Key: " + key + " Value: " + kchat_settings[key]);
                    }
                });
                // Dispatch custom event after setting localStorage keys
                document.dispatchEvent(new Event('kchat_settingsSet'));
            }
        });
    </script>
    <?php

    // Fetch the User ID - Updated Ver 2.0.6 - 2024 07 11
    $user_id = get_current_user_id();
    // Fetch the Kognetiks cookie
    $session_id = kognetiks_get_unique_id();
    if (empty($user_id) || $user_id == 0) {
        $user_id = $session_id;
    }
    // back_trace( 'NOTICE', '$user_id: ' . $user_id);
    // back_trace( 'NOTICE', '$session_id: ' . $session_id);

    // Generate a unique cache-busting parameter
    $cache_buster = '?cb=' . time();

    // Speech Recognition - Ver 2.1.5.1
    $chatbot_chatgpt_speech_recognition = esc_attr(get_option('chatbot_chatgpt_speech_recognition', 'No'));

    // back_trace( 'NOTICE', '$chatbot_chatgpt_display_style: ' . $chatbot_chatgpt_display_style);

    // Depending on the style, adjust the output - Ver 1.7.1
    if ($chatbot_chatgpt_display_style == 'embedded') {
        // Code for embed style ('embedded' is the alternative style)
        // Store the style and the assistant value - Ver 1.7.2
        set_chatbot_chatgpt_transients( 'display_style' , $chatbot_chatgpt_display_style, $user_id, $page_id, $session_id, null );
        set_chatbot_chatgpt_transients( 'assistant_alias' , $chatbot_chatgpt_assistant_alias, $user_id, $page_id, $session_id, null );
        set_chatbot_chatgpt_transients( 'model' , $model, $user_id, $page_id, $session_id, null);
        set_chatbot_chatgpt_transients( 'voice' , $voice, $user_id, $page_id, $session_id, null);
        set_chatbot_chatgpt_transients( 'assistant_name' , $bot_name, $user_id, $page_id, $session_id, null);
        // OUTSIDE THE IF STATEMENT - Ver 2.0.5 - 2024 07 05
        // ob_start();
        ?>
        <div id="chatbot-chatgpt" style="display: flex;" class="chatbot-embedded-style chatbot-full" data-cache-buster="<?php echo time(); ?>">
        <script>
            jQuery(document).ready(function($) {
                $('#chatbot-chatgpt').removeClass('chatbot-floating-style').addClass('chatbot-embedded-style');
            });
        </script>
        <!-- REMOVED FOR EMBEDDED -->
        <?php
        // if ( $use_assistant_name == 'Yes' ) {
        //     echo '<div id="chatbot-chatgpt-header-embedded">';
        //     echo '<div id="chatbot-chatgpt-title" class="title">' . strip_tags($bot_name) . '</div>';
        //     echo '</div>';
        // } else {
            echo '<div id="chatbot-chatgpt-header-embedded">';
            echo '<div id="chatbot-chatgpt-title" class="title">' . strip_tags($bot_name) . '</div>';
            echo '</div>';
        // }
        ?>
        <div id="chatbot-chatgpt-conversation"></div>
        <div id="chatbot-chatgpt-input">
            <div id="chatbot-chatgpt-input-area">
                <label for="chatbot-chatgpt-message"></label>
                <?php
                    // FIXME - ADD THIS TO FLOATING STYLE BELOW - Ver 1.9.5
                    // Kick off Flow - Ver 1.9.5
                    if ($kflow_enabled == true and !empty($sequence_id)) {
                        // back_trace( 'NOTICE', 'Kick off Flow');
                        // back_trace( 'NOTICE', 'chatbot_chatgpt_hot_bot_prompt: ' . $chatbot_chatgpt_hot_bot_prompt);
                        // Store the prompt in a hidden input instead of directly in the textarea
                        echo "<input type='hidden' id='chatbot-chatgpt-message' value='" . htmlspecialchars($chatbot_chatgpt_hot_bot_prompt, ENT_QUOTES) . "'>";
                        // echo "<textarea id='chatbot-chatgpt-message' rows='2' placeholder='$chatbot_chatgpt_bot_prompt' style='width: 95%;'></textarea>";
                        echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            var hiddenInput = document.getElementById('chatbot-chatgpt-message');
                            var submitButton = document.getElementById('chatbot-chatgpt-submit');
                            if (submitButton) {
                                submitButton.addEventListener('click', function() {
                                    // Use the value from the hidden input when submitting
                                    var promptToSubmit = hiddenInput.value;

                                });
                                // Optionally trigger the click if you need to automatically submit on page load
                                setTimeout(function() {
                                    submitButton.click(); // Use plain JS click
                                }, 500); // Delay of 1 second
                            }
                        });
                        </script>";
                    }
                    // Preload with a prompt if it is set - Ver 1.9.5
                    if ($kflow_enabled != true and !empty($chatbot_chatgpt_hot_bot_prompt)) {
                        // DIAG - Diagnostics - Ver 1.9.0
                        // back_trace( 'NOTICE', 'PRELOAD: $chatbot_chatgpt_hot_bot_prompt: ' . $chatbot_chatgpt_hot_bot_prompt);
                        $rows = esc_attr(get_option('chatbot_chatgpt_input_rows', '2'));
                        $chatbot_chatgpt_bot_prompt = esc_attr(sanitize_text_field($chatbot_chatgpt_bot_prompt));
                        $chatbot_chatgpt_hot_bot_prompt = esc_attr(sanitize_text_field($chatbot_chatgpt_hot_bot_prompt));
                        echo "<textarea id='chatbot-chatgpt-message' rows='". htmlspecialchars($rows) . "' placeholder='" . htmlspecialchars($chatbot_chatgpt_bot_prompt) . "' style='width: 95%;'>" . $chatbot_chatgpt_hot_bot_prompt . "</textarea>";
                        echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            var textarea = document.getElementById('chatbot-chatgpt-message');
                            textarea.value += '\\n';
                            textarea.focus();

                            setTimeout(function() {
                                var submitButton = document.getElementById('chatbot-chatgpt-submit');
                                if (submitButton) {
                                    submitButton.click(); // Use plain JS click
                                }
                            }, 500); // Delay of 1 second
                        });
                        </script>";
                    } else {
                        // DIAG - Diagnostics - Ver 1.9.5
                        // back_trace( 'NOTICE', 'chatbot_chatgpt_bot_prompt: ' . $chatbot_chatgpt_bot_prompt);
                        $rows = esc_attr(get_option('chatbot_chatgpt_input_rows', '2'));
                        $chatbot_chatgpt_bot_prompt = esc_attr(sanitize_text_field($chatbot_chatgpt_bot_prompt));
                        // Assistant's Table Override - Ver 2.0.4
                        if ( !empty($assistant_details['placeholder_prompt']) ) {
                            $chatbot_chatgpt_bot_prompt = $assistant_details['placeholder_prompt'];
                        }
                        $chatbot_chatgpt_hot_bot_prompt = esc_attr(sanitize_text_field($chatbot_chatgpt_hot_bot_prompt));
                        echo "<center><textarea id='chatbot-chatgpt-message' rows='" . htmlspecialchars($rows) . "' placeholder='" . htmlspecialchars($chatbot_chatgpt_bot_prompt) . "' style='width: 95%;'></textarea></center>";
                    }
                ?>
            </div>
        </div>
        <div id="chatbot-chatgpt-buttons-container">
            <button id="chatbot-chatgpt-submit" title="Send Message">
                <img decoding="async" src="<?php echo chatbot_chatgpt_appearance_icon_path('send_icon'); ?>" alt="Send">
            </button>
            <?php if ($chatbot_chatgpt_allow_file_uploads == 'Yes'): ?>
                <input type="file" id="chatbot-chatgpt-upload-file-input" name="file[]" style="display: none;" multiple="multiple" />
                <button id="chatbot-chatgpt-upload-file" title="Upload Files">
                    <img decoding="async" src="<?php echo chatbot_chatgpt_appearance_icon_path('attach_icon'); ?>" alt="Upload File">
                </button>
                <script type="text/javascript">
                    document.getElementById('chatbot-chatgpt-upload-file').addEventListener('click', function() {
                        document.getElementById('chatbot-chatgpt-upload-file-input').click();
                    });
                </script>
            <?php endif; ?>
            <?php if ($chatbot_chatgpt_allow_mp3_uploads == 'Yes'): ?>
                <input type="file" id="chatbot-chatgpt-upload-mp3-input" name="file[]" style="display: none;" />
                <button id="chatbot-chatgpt-upload-mp3" title="Upload an Audio/Video">
                    <img decoding="async" src="<?php echo chatbot_chatgpt_appearance_icon_path('attach_icon'); ?>" alt="Upload MP3">
                </button>
                <script type="text/javascript">
                    document.getElementById('chatbot-chatgpt-upload-mp3').addEventListener('click', function() {
                        document.getElementById('chatbot-chatgpt-upload-mp3-input').click();
                    });
                </script>
            <?php endif; ?>
            <button id="chatbot-chatgpt-erase-btn" title="Clear Conversation">
                <img decoding="async" src="<?php echo chatbot_chatgpt_appearance_icon_path('erase_icon'); ?>" alt="Erase Conversation">
            </button>
            <?php if ($chatbot_chatgpt_read_aloud_option == 'yes' && $voice != 'none'): ?>
                <button id="chatbot-chatgpt-text-to-speech-btn" title="Read Aloud">
                    <img decoding="async" src="<?php echo chatbot_chatgpt_appearance_icon_path('read_aloud_icon'); ?>" alt="Read Out Loud">
                </button>
            <?php endif; ?>
            <?php if ($chatbot_chatgpt_speech_recognition == 'Yes'): ?>
                <button id="chatbot-chatgpt-speech-recognition-btn" title="Use your microphone">
                    <img decoding="async" src="<?php echo chatbot_chatgpt_appearance_icon_path('mic_enabled_icon'); ?>" alt="Speech Recognition">
                </button>
            <?php endif; ?>
            <?php if ($chatbot_chatgpt_allow_download_transcript == 'Yes'): ?>
                <button id="chatbot-chatgpt-download-transcript-btn" title="Download Transcript">
                    <img decoding="async" src="<?php echo chatbot_chatgpt_appearance_icon_path('download_icon'); ?>" alt="Download Transcript">
                </button>
            <?php endif; ?>
            </div>
            <?php
            $chatbot_chatgpt_enable_custom_buttons = esc_attr(get_option('chatbot_chatgpt_enable_custom_buttons', 'Off'));
            if ($chatbot_chatgpt_enable_custom_buttons == 'Embedded' || $chatbot_chatgpt_enable_custom_buttons == 'Both') {
                chatbot_chatgpt_custom_buttons_display();
            }
            // Attribution - Ver 2.0.5
            chatbot_chatgpt_attribution();
            ?>
        </div>
        <button id="chatgpt-open-btn" style="display: none;">
        <i class="chatbot-open-icon"></i>
        </button>
        <?php
        return ob_get_clean();
    } elseif ($chatbot_chatgpt_display_style == 'floating') {
        // Code for bot style ('floating' is the default style)
        // Store the style and the assistant value - Ver 1.7.2
        set_chatbot_chatgpt_transients( 'display_style' , $chatbot_chatgpt_display_style, $user_id, $page_id, $session_id, null );
        set_chatbot_chatgpt_transients( 'assistant_alias' , $chatbot_chatgpt_assistant_alias, $user_id, $page_id, $session_id, null );
        set_chatbot_chatgpt_transients( 'model' , $model, $user_id, $page_id, $session_id, null);
        set_chatbot_chatgpt_transients( 'voice' , $voice, $user_id, $page_id, $session_id, null);
        set_chatbot_chatgpt_transients( 'assistant_name' , $bot_name, $user_id, $page_id, $session_id, null);
        // OUTSIDE THE IF STATEMENT - Ver 2.0.5 - 2024 07 05
        // ob_start();
        ?>
        <div id="chatbot-chatgpt">
            <div id="chatbot-chatgpt-header" data-cache-buster="<?php echo time(); ?>">
                <div id="chatbot-chatgpt-title" class="title"><?php echo htmlspecialchars($bot_name); ?></div>
            </div>
            <div id="chatbot-chatgpt-conversation"></div>
            <div id="chatbot-chatgpt-input">
                <div id="chatbot-chatgpt-input-area">
                    <label for="chatbot-chatgpt-message"></label>
                    <?php
                        // Kick off Flow - Ver 1.9.5
                        if ($kflow_enabled == true and !empty($sequence_id)) {
                            // back_trace( 'NOTICE', 'Kick off Flow');
                            // back_trace( 'NOTICE', 'chatbot_chatgpt_hot_bot_prompt: ' . $chatbot_chatgpt_hot_bot_prompt);
                            // Store the prompt in a hidden input instead of directly in the textarea
                            echo "<input type='hidden' id='chatbot-chatgpt-message' value='" . htmlspecialchars($chatbot_chatgpt_hot_bot_prompt, ENT_QUOTES) . "'>";
                            // echo "<textarea id='chatbot-chatgpt-message' rows='2' placeholder='$chatbot_chatgpt_bot_prompt' style='width: 95%;'></textarea>";
                            echo "<script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var hiddenInput = document.getElementById('chatbot-chatgpt-message');
                                var submitButton = document.getElementById('chatbot-chatgpt-submit');
                                if (submitButton) {
                                    submitButton.addEventListener('click', function() {
                                        // Use the value from the hidden input when submitting
                                        var promptToSubmit = hiddenInput.value;
    
                                    });
                                    // Optionally trigger the click if you need to automatically submit on page load
                                    setTimeout(function() {
                                        submitButton.click(); // Use plain JS click
                                    }, 500); // Delay of 1 second
                                }
                            });
                            </script>";
                        }
                        // Preload with a prompt if it is set - Ver 1.9.5
                        if ($kflow_enabled != true and !empty($chatbot_chatgpt_hot_bot_prompt)) {
                            // back_trace( 'NOTICE', 'PRELOAD: $chatbot_chatgpt_hot_bot_prompt: ' . $chatbot_chatgpt_hot_bot_prompt);
                            $rows = esc_attr(get_option('chatbot_chatgpt_input_rows', '2'));
                            $chatbot_chatgpt_bot_prompt = esc_attr(sanitize_text_field($chatbot_chatgpt_bot_prompt));
                            $chatbot_chatgpt_hot_bot_prompt = esc_attr(sanitize_text_field($chatbot_chatgpt_hot_bot_prompt));
                            echo "<center><textarea id='chatbot-chatgpt-message' rows='". htmlspecialchars($rows) . "' placeholder='" . htmlspecialchars($chatbot_chatgpt_bot_prompt) . "' style='width: 95%;'>" . $chatbot_chatgpt_hot_bot_prompt . "</textarea>";
                            echo "<script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var textarea = document.getElementById('chatbot-chatgpt-message');
                                textarea.value += '\\n';
                                textarea.focus();
                                setTimeout(function() {
                                    var submitButton = document.getElementById('chatbot-chatgpt-submit');
                                    if (submitButton) {
                                        submitButton.click(); // Use plain JS click
                                    }
                                }, 500); // Delay of 1 second
                            });
                            </script>";
                        } else {
                            $rows = esc_attr(get_option('chatbot_chatgpt_input_rows', '2'));
                            // Assistant's Table Override - Ver 2.0.4
                            if ( !empty($assistant_details['placeholder_prompt']) ) {
                                $chatbot_chatgpt_bot_prompt = $assistant_details['placeholder_prompt'];
                            }
                            echo "<center><textarea id='chatbot-chatgpt-message' rows='" . htmlspecialchars($rows) . "' placeholder='" . htmlspecialchars($chatbot_chatgpt_bot_prompt) . "' style='width: 95%;'></textarea></center>";
                        }
                    ?>
                </div>
            </div>
            <div id="chatbot-chatgpt-buttons-container">
                <button id="chatbot-chatgpt-submit" title="Send Message">
                <img decoding="async" src="<?php echo chatbot_chatgpt_appearance_icon_path('send_icon'); ?>" alt="Send">
                </button>
                <?php if ($chatbot_chatgpt_allow_file_uploads == 'Yes'): ?>
                    <input type="file" id="chatbot-chatgpt-upload-file-input" name="file[]" style="display: none;" multiple="multiple" />
                    <button id="chatbot-chatgpt-upload-file" title="Upload Files">
                        <img decoding="async" src="<?php echo chatbot_chatgpt_appearance_icon_path('attach_icon'); ?>" alt="Upload File">
                    </button>
                    <script type="text/javascript">
                        document.getElementById('chatbot-chatgpt-upload-file').addEventListener('click', function() {
                            document.getElementById('chatbot-chatgpt-upload-file-input').click();
                        });
                    </script>
                <?php endif; ?>
                <?php if ($chatbot_chatgpt_allow_mp3_uploads == 'Yes'): ?>
                    <input type="file" id="chatbot-chatgpt-upload-mp3-input" name="file[]" style="display: none;" />
                    <button id="chatbot-chatgpt-upload-mp3" title="Upload MP3">
                        <img decoding="async" src="<?php echo chatbot_chatgpt_appearance_icon_path('attach_icon'); ?>" alt="Upload MP3">
                    </button>
                    <script type="text/javascript">
                        document.getElementById('chatbot-chatgpt-upload-mp3').addEventListener('click', function() {
                            document.getElementById('chatbot-chatgpt-upload-mp3-input').click();
                        });
                    </script>
                <?php endif; ?>
                <button id="chatbot-chatgpt-erase-btn" title="Clear Conversation">
                    <img decoding="async" src="<?php echo chatbot_chatgpt_appearance_icon_path('erase_icon'); ?>" alt="Erase Conversation">
                </button>
                <?php if ($chatbot_chatgpt_read_aloud_option == 'yes' && $voice != 'none'): ?>
                    <button id="chatbot-chatgpt-text-to-speech-btn" title="Read Aloud">
                        <img decoding="async" src="<?php echo chatbot_chatgpt_appearance_icon_path('read_aloud_icon'); ?>" alt="Read Out Loud">
                    </button>
                <?php endif; ?>
                <?php if ($chatbot_chatgpt_speech_recognition == 'Yes'): ?>
                    <button id="chatbot-chatgpt-speech-recognition-btn" title="Use your microphone">
                        <img decoding="async" src="<?php echo chatbot_chatgpt_appearance_icon_path('mic_enabled_icon'); ?>" alt="Speech Recognition">
                    </button>
                <?php endif; ?>
                <?php if ($chatbot_chatgpt_allow_download_transcript == 'Yes'): ?>
                    <button id="chatbot-chatgpt-download-transcript-btn" title="Download Transcript">
                        <img decoding="async" src="<?php echo chatbot_chatgpt_appearance_icon_path('download_icon'); ?>" alt="Download Transcript">
                    </button>
                <?php endif; ?>
            </div>
            <?php
            $chatbot_chatgpt_enable_custom_buttons = esc_attr(get_option('chatbot_chatgpt_enable_custom_buttons', 'Off'));
            if ($chatbot_chatgpt_enable_custom_buttons == 'On' || $chatbot_chatgpt_enable_custom_buttons == 'Floating' || $chatbot_chatgpt_enable_custom_buttons == 'Both') {
                chatbot_chatgpt_custom_buttons_display();
            }
            // Attribution - Ver 2.0.5
            chatbot_chatgpt_attribution();
            ?>
        </div>
        <div>
            <button id="chatgpt-open-btn" style="display: none;" aria-hidden="true">
                <i class="chatbot-open-icon"></i>
            </button>
        </div>
        <?php
        return ob_get_clean();
    }

}

// Dynamic Shortcode - Ver 2.0.6 - - Ver 2.3.0 Update 2025 04 23
function register_chatbot_shortcodes($number_of_shortcodes = null) {

    // Make sure the number of shortcodes is set
    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_assistants';

    // Check if the table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;

    if ($table_exists) {
        // The table exists, proceed with the original query
        $number_of_shortcodes = $wpdb->get_var("SELECT MAX(id) FROM $table_name");

        // If the query fails for any other reason, set $number_of_shortcodes to 0
        if ($number_of_shortcodes === NULL || $number_of_shortcodes === FALSE) {
            $number_of_shortcodes = 0;
        }
    } else {
        // The table doesn't exist, set $number_of_shortcodes to 0 directly
        $number_of_shortcodes = 0;
    }

    // Update the option with the number of shortcodes
    update_option('chatbot_chatgpt_number_of_shortcodes', $number_of_shortcodes);

    // Base shortcode names - only register if not already registered
    $base_shortcodes = [
        'chatbot',
        'chatbot_chatgpt',
        'kognetiks_chatbot'
    ];

    // Register base shortcodes only if not already registered
    foreach ($base_shortcodes as $shortcode) {
        if (!shortcode_exists($shortcode)) {
            add_shortcode($shortcode, 'chatbot_chatgpt_shortcode');
        }
    }

    // Register numbered shortcodes dynamically using [chatbot-#] syntax
    // Only register if not already registered and within the valid range
    for ($i = 1; $i <= $number_of_shortcodes; $i++) {
        $shortcode = 'chatbot-' . $i;
        if (!shortcode_exists($shortcode)) {
            add_shortcode($shortcode, 'chatbot_chatgpt_shortcode');
            // error_log('Registered shortcode: ' . $shortcode);
        }
    }

    // Register numbered shortcodes dynamically using [assistant-#] syntax
    // Only register if not already registered and within the valid range
    for ($i = 1; $i <= $number_of_shortcodes; $i++) {
        $shortcode = 'assistant-' . $i;
        if (!shortcode_exists($shortcode)) {
            add_shortcode($shortcode, 'chatbot_chatgpt_shortcode');
            // error_log('Registered shortcode: ' . $shortcode);
        }
    }

    // Register numbered shortcodes dynamically using [agent-#] syntax
    // Only register if not already registered and within the valid range
    for ($i = 1; $i <= $number_of_shortcodes; $i++) {
        $shortcode = 'agent-' . $i;
        if (!shortcode_exists($shortcode)) {
            add_shortcode($shortcode, 'chatbot_chatgpt_shortcode');
            // error_log('Registered shortcode: ' . $shortcode);
        }
    }
    
}
// Try to register the shortcodes on init - Ver 2.0.6 - 2024 07 11
add_action('init', 'register_chatbot_shortcodes');

// Custom Buttons - Ver 2.0.5
function chatbot_chatgpt_custom_buttons_display() {
    ?>
    <div id="chatbot-chatgpt-custom-buttons">
        <?php
        $button_names = [];
        $button_urls = [];
        $button_count = 4; // Maximum number of buttons

        // Initialize and set button names and URLs
        for ($i = 1; $i <= $button_count; $i++) {
            $button_names[$i] = esc_attr(get_option("chatbot_chatgpt_custom_button_name_$i"));
            $button_urls[$i] = esc_attr(get_option("chatbot_chatgpt_custom_button_url_$i"));
        }

        // Generate buttons
        for ($i = 1; $i <= $button_count; $i++) {
            if (!empty($button_names[$i]) && !empty($button_urls[$i])) {
                ?>
                <button class="chatbot-chatgpt-custom-button-class">
                    <a href="<?php echo esc_url($button_urls[$i]); ?>" target="_blank"><?php echo esc_html($button_names[$i]); ?></a>
                </button>
                <?php
            }
        }
        ?>
    </div>
    <?php
}

// Attribution - Ver 2.0.5
function chatbot_chatgpt_attribution() {

    $chatbot_chatgpt_suppress_attribution = esc_attr(get_option('chatbot_chatgpt_suppress_attribution', 'On'));
    $chatbot_chatgpt_custom_attribution = esc_attr(get_option('chatbot_chatgpt_custom_attribution', 'Your custom attribution message goes here.'));
    // DIAG - Diagnostics - Ver 1.6.5
    // back_trace( 'NOTICE', 'chatbot_chatgpt_suppress_attribution: ' . $chatbot_chatgpt_suppress_attribution);
    
    if ($chatbot_chatgpt_suppress_attribution == 'Off') {
        if ($chatbot_chatgpt_custom_attribution == 'Your custom attribution message goes here.' || empty($chatbot_chatgpt_custom_attribution)) { 
            ?>
            <div class="chatbot-attribution">
                <a href="https://kognetiks.com/wordpress-plugins/kognetiks-chatbot/?utm_source=chatbot&utm_medium=website&utm_campaign=powered_by&utm_id=plugin" target="_blank" rel="noopener noreferrer" class="chatbot-attribution-link"><?php echo esc_html('Chatbot plugin by Kognetiks'); ?></a>
            </div>
            <?php
        } else {
            ?>
            <div class="chatbot-attribution">
                <p class="chatbot-attribution-text"><?php echo $chatbot_chatgpt_custom_attribution; ?></p>
            </div>
            <?php
        }
    }
}


// Fix Updating failed. The response is not a valid JSON response. - Version 1.7.3
// Function to output the script
function chatbot_chatgpt_shortcode_enqueue_script() {

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

    // These were already here - Ver 1.9.3 - 2024 03 16
    global $chatbot_chatgpt_display_style;
    global $chatbot_chatgpt_assistant_alias;

    global $kchat_settings;

    // Check if the variables are set and not empty
    $style = $chatbot_chatgpt_display_style ?? '';
    $assistant = $chatbot_chatgpt_assistant_alias ?? '';

    // Preload avatar - Ver 2.0.3
    $avatar_icon_setting = esc_attr(get_option('chatbot_chatgpt_avatar_icon_setting', ''));
    $kchat_settings['chatbot_chatgpt_avatar_icon_setting'] = $avatar_icon_setting;
    $custom_avatar_icon_setting = esc_attr(get_option('chatbot_chatgpt_custom_avatar_icon_setting', ''));
    $kchat_settings['chatbot_chatgpt_custom_avatar_icon_setting'] = $custom_avatar_icon_setting;

    // DIAG - Diagnostics - Ver 1.9.3
    // back_trace( 'NOTICE', 'chatbot_chatgpt_shortcode_enqueue_script - at the beginning of the function');
    // back_trace( 'NOTICE', 'get_the_id(): ' . get_the_id() );
    // back_trace( 'NOTICE', '$user_id: ' . $user_id);
    // back_trace( 'NOTICE', '$page_id: ' . $page_id);
    // back_trace( 'NOTICE', '$session_id: ' . $session_id);
    // back_trace( 'NOTICE', '$thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', '$assistant_id: ' . $assistant_id);
    // back_trace( 'NOTICE', '$model: ' . $model);
    // back_trace( 'NOTICE', '$voice: ' . $voice);
    // back_trace( 'NOTICE', '$chatbot_chatgpt_display_style: ' . $chatbot_chatgpt_display_style);
    // back_trace( 'NOTICE', '$kchat_settings: ' . print_r($kchat_settings, true));

    ?>
        <script type="text/javascript">
            document.addEventListener("DOMContentLoaded", function() {
                let kchat_settings = <?php echo json_encode($kchat_settings); ?>;
                if (kchat_settings && typeof kchat_settings === "object") {
                    // Resolve LocalStorage - Ver 2.1.1.1.R1
                    const includeKeys = [
                        'chatbot_chatgpt_last_reset',
                        'chatbot_chatgpt_message_count',
                        'chatbot_chatgpt_message_limit_setting',
                        'chatbot_chatgpt_message_limit_period_setting',
                        'chatbot_chatgpt_start_status',
                        'chatbot_chatgpt_start_status_new_visitor',
                        'chatbot_chatgpt_opened',
                        'chatbot_chatgpt_last_reset'
                    ];
                    // Iterate over kchat_settings and add to localStorage if key is included
                    Object.keys(kchat_settings).forEach(function(key) {
                        if (includeKeys.includes(key)) {
                            localStorage.setItem(key, kchat_settings[key]);
                            // DiAG - Ver 2.1.1.1
                            // console.log("Chatbot: NOTICE: chatbot-shortcode.php - Key: " + key + " Value: " + kchat_settings[key]);
                        }
                    });
                    // Dispatch custom event after setting localStorage keys
                    document.dispatchEvent(new Event('kchat_settingsSet'));
                }
            });
        </script>
    <?php

}
// Hook this function into the 'wp_footer' action
add_action('wp_footer', 'chatbot_chatgpt_shortcode_enqueue_script');
