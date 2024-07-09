<?php
/**
 * Kognetiks Chatbot for WordPress - [chatbot_chatgpt] Shortcode Registration
 *
 * This file contains the code for registering the shortcode used
 * to display the Chatbot on the website.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

function chatbot_chatgpt_shortcode( $atts = [], $content = null, $tag = '' ) {
// function chatbot_chatgpt_shortcode( $atts ) {

    // if (!defined('DONOTCACHEPAGE')) {
    //     define('DONOTCACHEPAGE', true);
    // }

    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;
    global $script_data_array;
    global $additional_instructions;
    global $model;
    global $voice;

    global $chatbot_chatgpt_display_style;
    global $chatbot_chatgpt_assistant_alias;

    // Initialize $assistant_details as an empty array
    global $assistant_details;
    $assistant_details = [];

    // Initialize $chatbot_settings as an empty array
    global $chatbot_settings;
    $chatbot_settings = [];

    global $kflow_data;

    // Fetch the unique ID of the visitor or logged in user - Ver 2.0.4
    $session_id = kognetiks_get_unique_id();
    $user_id = get_current_user_id();
    if ($user_id == 0) {
        $user_id = $session_id;
    }

    // DIAG - Diagnostics - Ver 1.9.3
    // back_trace( 'NOTICE', 'Shortcode tag: ' . $tag);
    // back_trace( 'NOTICE', 'Shortcode atts: ' . print_r($atts, true));
    // back_trace( 'NOTICE', 'chatbot_chatgpt_shortcode - at the beginning of the function');
    // back_trace( 'NOTICE', '$user_id: ' . $user_id);
    // back_trace( 'NOTICE', '$page_id: ' . $page_id);
    // back_trace( 'NOTICE', '$session_id: ' . $session_id);
    // back_trace( 'NOTICE', '$thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', '$assistant_id: ' . $assistant_id);
    // back_trace( 'NOTICE', '$script_data_array: ' . print_r($script_data_array, true));
    // back_trace( 'NOTICE', 'Shortcode Attributes: ' . print_r($atts, true));
    // back_trace( 'NOTICE', 'get_the_id(): ' . get_the_id());
    // back_trace( 'NOTICE', '$model: ' . $model);
    // back_trace( 'NOTICE', 'Browser: ' . $_SERVER['HTTP_USER_AGENT']);
    // foreach ($atts as $key => $value) {
    //   back_trace('NOTICE', '$atts - Key: ' . $key . ' Value: ' . $value);
    // }

    
    // Initialize $script_data_array with global values
    // FIXME - LOOK AT MERGING $script_data_array, $assistant_details, AND $chatbot_settings - Ver 2.0.5 - 2024 07 01
    $script_data_array = array(
        'user_id' => $user_id,
        'page_id' => $page_id,
        'session_id' => $session_id,
        'thread_id' => $thread_id,
        'assistant_id' => $assistant_id,
        'additional_instructions' => $additional_instructions,
        'model' => $model,
        'voice' => $voice,
    );

    // BELT & SUSPENDERS - Ver 1.9.4
    $model_choice = esc_attr(get_option('chatbot_chatgpt_model_choice', 'gpt-3.5-turbo'));
    $voice_choice = esc_attr(get_option('chatbot_chatgpt_voice_option', 'alloy'));

    // Shortcode Attributes
    $chatbot_chatgpt_default_atts = array(
        'style' => 'floating', // Default value
        'assistant' => 'original', // Default value
        'audience' => 'all', // If not passed then default value
        'prompt' => '', // If not passed then default value
        'sequence' => '', // If not passed then default value
        'additional_instructions' => '', // If not passed then default value
        'model' => $model_choice, // If not passed then default value
        'voice' => $voice_choice, // If not passed then default value
    );

    // DIAG - Diagnostics - Ver 1.8.6
    // back_trace( 'NOTICE', 'chatbot_chatgpt_shortcode - at line 69 of the function');
    // back_trace( 'NOTICE', '$user_id: ' . $user_id);
    // back_trace( 'NOTICE', '$page_id: ' . $page_id);
    // back_trace( 'NOTICE', '$session_id: ' . $session_id);
    // back_trace( 'NOTICE', '$thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', '$assistant_id: ' . $assistant_id);
    // back_trace( 'NOTICE', '$script_data_array: ' . print_r($script_data_array, true));
    // back_trace( 'NOTICE', 'Shortcode Attributes: ' . print_r($atts, true));

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
    // [chatbot style="embedded" model="tts-1-1106" voice="fable"] - Embedded style using the TTS 1 model with the voice of Fable

    // Normalize attribute keys, lowercase
    $atts = array_change_key_case((array)$atts, CASE_LOWER);

    // Combine user attributes with the default attributes
    $atts = shortcode_atts($chatbot_chatgpt_default_atts, $atts);

    // Delete any parameters that are not in the default list
    $atts = array_intersect_key($atts, $chatbot_chatgpt_default_atts);

    // For each $atts, sanitize the shortcode data - Ver 1.9.9
    // Cross Site Scripting (XSS) vulnerability patch for 62801a58-b1ba-4c5a-bf93-7315d3553bb8
    foreach ($atts as $key => $value) {
        $atts[$key] = sanitize_text_field($value);
        $atts[$key] = htmlspecialchars(strip_tags($atts[$key] ?? ''), ENT_QUOTES, 'UTF-8');
    }

    // If (strpos($atts['assistant'], 'asst_') === false && $atts['assistant'] != 'original' && $atts['assistant'] != 'primary' && $atts['assistant'] != 'alternate') {
    // If (strpos($atts['assistant'], 'asst_') === false ) {

    // Tag Processing - Ver 2.0.6
    if (strpos($tag, 'chatbot-') !== false) {
        back_trace('NOTICE', 'Tag Processing: ' . $tag);
        // Extract the Assistant ID from the tag
        $assistant_key = str_replace('chatbot-', '', $tag);
        // Fetch the common name of the Assistant Common Name from the Assistant table
        $assistant_details = get_chatbot_chatgpt_assistant_by_key($assistant_key);
        // For each key in $assistant_details, set the $atts value
        foreach ($assistant_details as $key => $value) {
            $atts[$key] = $value;
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

        // back_trace('NOTICE', '$assistant_details: ' . print_r($atts['assistant'], true));
        // back_trace('NOTICE', '$assistant_details: ' . print_r($assistant_details, true));
    }

    // If the assistant is not set to 'original', 'primary', or 'alternate' then try to fetch the Assistant details
    if ( !empty($atts['assistant']) && strpos($atts['assistant'], 'asst_') === false ) {

        // Initialize the Assistant details
        $assistant_details = array();
        
        // Try to fetch the Assistant details from the Assistant table using the passed assistant $atts value
        $assistantCommonName = $atts['assistant'];

        // Utility
        $assistant_details = get_chatbot_chatgpt_assistant_by_common_name($assistantCommonName);

        // If no match is found, then the $assistant_details will be an empty array
        if (empty($assistant_details)) {

            // DIAG - Diagnostics - Ver 2.0.5
            // back_trace ( 'NOTICE', 'No match found for the Assistant: ' . $assistantCommonName);

            // Set to original
            $chatbot_chatgpt_assistant_alias = 'original'; // default value

        } else {

            // DIAG - Diagnostics - Ver 2.0.5
            // back_trace ( 'NOTICE', 'Match found for the Assistant: ' . $assistantCommonName);

            // DIAG - Diagnostics - Ver 2.0.4
            // back_trace ( 'NOTICE', '$assistant_details: ' . print_r($assistant_details, true));

            foreach ($assistant_details as $key => $value) {
                $atts[$key] = $value;
            }

            // DIAG - Diagnostics - Ver 2.0.4
            // back_trace ( 'NOTICE', 'AFTER $atts: ' . print_r($atts, true));

            // Set the assistant_id
            $atts['assistant'] = $assistant_details['assistant_id'];

            $chatbot_chatgpt_assistant_alias = $assistant_details['assistant_id'];

        }

    } elseif ( !empty($atts['assistant']) && strpos($atts['assistant'], 'asst_') !== false ) {

        // Set the assistant_id
        $chatbot_chatgpt_assistant_alias = $atts['assistant'];

    } else {
            
            // Default to 'original'
            $chatbot_chatgpt_assistant_alias = 'original'; // default value

    }

    // Validate and sanitize the style parameter - Ver 1.9.9
    $valid_styles = ['floating', 'embedded'];
    $chatbot_chatgpt_display_style = 'floating'; // default value
    if (array_key_exists('style', $atts) && !is_null($atts['style'])) {
        if (in_array($atts['style'], $valid_styles)) {
            $chatbot_chatgpt_display_style = sanitize_text_field($atts['style']);
            $chatbot_settings['chatbot_chatgpt_display_style'] = $chatbot_chatgpt_display_style;
            // back_trace('NOTICE', '$chatbot_chatgpt_display_style: ' . $chatbot_chatgpt_display_style);
        } else {
            $chatbot_chatgpt_display_style = $chatbot_chatgpt_display_style_global;
            $atts['style'] = $chatbot_chatgpt_display_style_global;
            $chatbot_settings['chatbot_chatgpt_display_style'] = $chatbot_chatgpt_display_style_global;
            // back_trace('ERROR', 'Invalid display style: ' . $atts['style']);
        }
    }

    // Validate and sanitize the assistant parameter and set the assistant_id - Ver 1.9.9
    $valid_ids = ['original', 'primary', 'alternate'];
    $chatbot_chatgpt_assistant_alias = 'original'; // default value
    if (array_key_exists('assistant', $atts)) {
        $sanitized_assistant = sanitize_text_field($atts['assistant']);
        if (in_array($sanitized_assistant, $valid_ids) || strpos($sanitized_assistant, 'asst_') === 0) {
            $chatbot_chatgpt_assistant_alias = $sanitized_assistant;
            // back_trace('NOTICE', '$assistant_id: ' . $chatbot_chatgpt_assistant_alias);
        } else {
            // back_trace('ERROR', 'Invalid $assistant_id: ' . $sanitized_assistant);
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
            $chatbot_settings['chatbot_chatgpt_audience_choice'] = $chatbot_chatgpt_audience_choice;
            // back_trace('NOTICE', '$chatbot_chatgpt_audience_choice: ' . $chatbot_chatgpt_audience_choice);
        } else {
            $chatbot_chatgpt_audience_choice = $chatbot_chatgpt_audience_choice_global;
            $atts['audience'] = $chatbot_chatgpt_audience_choice_global;
            $chatbot_settings['chatbot_chatgpt_audience_choice'] = $chatbot_chatgpt_audience_choice_global;
            // back_trace('ERROR', 'Invalid audience choice: ' . $sanitized_audience);
        }
    }
    
    // Validate and sanitize the prompt parameter - Ver 1.9.9
    $chatbot_chatgpt_hot_bot_prompt = ''; // default value
    if (array_key_exists('prompt', $atts)) {
        $chatbot_chatgpt_hot_bot_prompt = sanitize_text_field($atts['prompt']);
        // back_trace('NOTICE', 'chatbot_chatgpt_hot_bot_prompt: ' . $chatbot_chatgpt_hot_bot_prompt);
    } elseif (isset($_GET['chatbot_prompt'])) {
        $chatbot_chatgpt_hot_bot_prompt = sanitize_text_field($_GET['chatbot_prompt']);
        // back_trace('NOTICE', 'chatbot_chatgpt_hot_bot_prompt: ' . $chatbot_chatgpt_hot_bot_prompt);
    }
    
    // Validate and sanitize the additional_instructions parameter - Ver 1.9.9
    $additional_instructions = ''; // default value
    if (array_key_exists('additional_instructions', $atts)) {
        $additional_instructions = sanitize_text_field($atts['additional_instructions']);
        $chatbot_settings['chatbot_chatgpt_additional_instructions'] = $additional_instructions;
        // back_trace('NOTICE', '$additional_instructions: ' . $additional_instructions);
    }

    // Validate and sanitize the model parameter - Ver 1.9.9
    if (!isset($atts['model'])) {
        $model = esc_attr(get_option('chatbot_chatgpt_model_choice', 'gpt-3.5-turbo'));
        $script_data_array['model'] = $model;
        // DIAG - Diagnostics - Ver 1.9.4
        // back_trace('NOTICE', 'Model (defaulting): ' . $model);
    } else {
        $model = sanitize_text_field($atts['model']);
        $script_data_array['model'] = $model;
        // DIAG - Diagnostics - Ver 1.9.4
        // back_trace('NOTICE', 'Model: ' . $model);
    }

    // Validate and sanitize the voice parameter - Ver 1.9.9
    $valid_voices = ['alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer', 'none'];
    $voice = 'alloy'; // default value
    if (array_key_exists('voice', $atts)) {
        $sanitized_voice = sanitize_text_field($atts['voice']);
        if (in_array($sanitized_voice, $valid_voices)) {
            $voice = $sanitized_voice;
            $script_data_array['voice'] = $voice;
            $assistant_details['voice'] = $voice;
            $chatbot_settings['chatbot_chatgpt_voice_option'] = $voice;
            // back_trace('NOTICE', '$voice: ' . $voice);
        } else {
            $voice = esc_attr(get_option('chatbot_chatgpt_voice_option', 'alloy'));
            $script_data_array['voice'] = $voice;
            $assistant_details['voice'] = $voice;
            $chatbot_settings['chatbot_chatgpt_voice_option'] = $voice;
            // back_trace('NOTICE', 'Voice (defaulting): ' . $voice);
        }
    } else {
        $voice = esc_attr(get_option('chatbot_chatgpt_voice_option', 'alloy'));
        $script_data_array['voice'] = $voice;
        $assistant_details['voice'] = $voice;
        $chatbot_settings['chatbot_chatgpt_voice_option'] = $voice;
        // back_trace('NOTICE', 'Voice (defaulting): ' . $voice);
    }

    // DIAG - Diagnostics - Ver 1.9.0
    // back_trace( 'NOTICE', '$user_id: ' . $user_id);
    // back_trace( 'NOTICE', '$page_id: ' . $page_id);
    // back_trace( 'NOTICE', '$session_id: ' . $session_id);
    // back_trace( 'NOTICE', '$script_data_array: ' . print_r($script_data_array, true));

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

    // Get the current user ID and page ID for use with transients
    $user_id = get_current_user_id(); // Get current user ID
    if (empty($user_id)) {
        // Removed - Ver 1.9.0
        // $user_id = $session_id; // Get the session ID if $user_id is not set
    }
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
        $assistant_id = esc_attr(get_option('chatbot_chatgpt_assistant_id', ''));
        $additional_instructions = esc_attr(get_option('chatbot_chatgpt_additional_instructions', ''));
    } elseif ( $chatbot_chatgpt_assistant_alias == 'alternate' ) {
        $assistant_id = esc_attr(get_option('chatbot_chatgpt_assistant_id_alternate', ''));
        $additional_instructions = esc_attr(get_option('chatbot_chatgpt_additional_instructions_alternate', ''));
    } else {
        // Do nothing as either the assistant_id is set to the GPT Assistant ID or it is not set at all
        $additional_instructions = array_key_exists('instructions', $atts) ? sanitize_text_field($atts['instructions']) : '';
        $additional_details['additional_instructions'] = $additional_instructions;
    }

    // Fetch the Kognetiks cookie
    $session_id = kognetiks_get_unique_id();
    // back_trace( 'NOTICE', 'session_id: ' . $session_id);
    // if (empty($user_id)) {
        $user_id = $session_id;
    // }

    set_chatbot_chatgpt_transients( 'display_style' , $chatbot_chatgpt_display_style, $user_id, $page_id, null, null );
    set_chatbot_chatgpt_transients( 'assistant_alias' , $chatbot_chatgpt_assistant_alias, $user_id, $page_id, null, null );
    
    set_chatbot_chatgpt_transients( 'assistant_id', $assistant_id, $user_id, $page_id, null, null);
    // back_trace( 'NOTICE', 'assistant_id: ' . $assistant_id);
    set_chatbot_chatgpt_transients( 'thread_id', $thread_id, $user_id, $page_id, null, null);
    // back_trace( 'NOTICE', 'thread_id: ' . $thread_id);

    // DUPLICATE ADDED THIS HERE - VER 1.9.1
    $script_data_array = array(
        'user_id' => $user_id,
        'page_id' => $page_id,
        'session_id' => $session_id,
        'thread_id' => $thread_id,
        'assistant_id' => $assistant_id,
        'additional_instructions' => $additional_instructions,
        'model' => $model,
        'voice' => $voice,
    );

    // DIAG - Diagnostics - Ver 1.8.6
    // back_trace( 'NOTICE', 'chatbot_chatgpt_shortcode - at line 230 of the function');
    // back_trace( 'NOTICE', '$user_id: ' . $user_id);
    // back_trace( 'NOTICE', '$page_id: ' . $page_id);
    // back_trace( 'NOTICE', '$session_id: ' . $session_id);
    // back_trace( 'NOTICE', '$thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', '$assistant_id: ' . $assistant_id);
    // back_trace( 'NOTICE', '$script_data_array: ' . print_r($script_data_array, true));
    // back_trace( 'NOTICE', '$voice: ' . $voice);

    // Retrieve the bot name - Ver 2.0.5
    $use_assistant_name = esc_attr(get_option('chatbot_chatgpt_display_custom_gpt_assistant_name', 'Yes'));

    // Assistant's Table Override - Ver 2.0.4
    if (!empty($assistant_details['show_assistant_name'])) {
        $use_assistant_name = $assistant_details['show_assistant_name'];
    }

    // DIAG - Diagnostics - Ver 2.0.5
    // back_trace('NOTICE', '$use_assistant_name: ' . $use_assistant_name);

    if ($use_assistant_name == 'Yes' && !empty($assistant_id)) {
        // FIXME - CAN I AVOID THIS CALL TO OPENAI?
        $assistant_name = esc_attr(get_chatbot_chatgpt_assistant_name($assistant_id));
        $bot_name = !empty($assistant_name) ? $assistant_name : esc_attr(get_option('chatbot_chatgpt_bot_name', 'Kognetiks Chatbot'));
    } else {
        $bot_name = esc_attr(get_option('chatbot_chatgpt_bot_name', 'Kognetiks Chatbot'));
    }

    // Relocalize the $chatbot_settings array - Ver 2.0.5
    if (array_key_exists('bot_name', $assistant_details)) {
        $chatbot_settings['chatbot_chatgpt_bot_name'] = $assistant_details['bot_name'];
    } else {
        $chatbot_settings['chatbot_chatgpt_bot_name'] = $bot_name;
    }
    if (array_key_exists('initial_greeting', $assistant_details)) {
        $chatbot_settings['chatbot_chatgpt_initial_greeting'] = $assistant_details['initial_greeting'];
    } else {
        $chatbot_settings['chatbot_chatgpt_initial_greeting'] = esc_attr(get_option('chatbot_chatgpt_initial_greeting', 'Hello! How can I help you today?'));
    }
    if (array_key_exists('subsequent_greeting', $assistant_details)) {
        $chatbot_settings['chatbot_chatgpt_subsequent_greeting'] = $assistant_details['subsequent_greeting'];
    } else {
        $chatbot_settings['chatbot_chatgpt_subsequent_greeting'] = esc_attr(get_option('chatbot_chatgpt_subsequent_greeting', 'How can I help you further?'));
    }
    if (array_key_exists('style', $assistant_details)) {
        $chatbot_settings['chatbot_chatgpt_display_style'] = $assistant_details['style'];
    } else {
        $chatbot_settings['chatbot_chatgpt_display_style'] = esc_attr(get_option('chatbot_chatgpt_display_style', 'floating'));
    }
    if (array_key_exists('audience', $assistant_details)) {
        $chatbot_settings['chatbot_chatgpt_audience_choice'] = $assistant_details['audience'];
    } else {
        $chatbot_settings['chatbot_chatgpt_audience_choice'] = esc_attr(get_option('chatbot_chatgpt_audience_choice', 'all'));
    }
    if (array_key_exists('voice', $assistant_details)) {
        $chatbot_settings['chatbot_chatgpt_voice_option'] = $assistant_details['voice'];
    } else {
        $chatbot_settings['chatbot_chatgpt_voice_option'] = esc_attr(get_option('chatbot_chatgpt_voice_option', 'alloy'));
    }
    if (array_key_exists('allow_file_uploads', $assistant_details)) {
        $chatbot_settings['chatbot_chatgpt_allow_file_uploads'] = $assistant_details['allow_file_uploads'];
    } else {
        $chatbot_settings['chatbot_chatgpt_allow_file_uploads'] = esc_attr(get_option('chatbot_chatgpt_allow_file_uploads', 'No'));
    }
    if (array_key_exists('allow_download_transcript', $assistant_details)) {
        $chatbot_settings['chatbot_chatgpt_allow_download_transcript'] = $assistant_details['allow_download_transcript'];
    } else {
        $chatbot_settings['chatbot_chatgpt_allow_download_transcript'] = esc_attr(get_option('chatbot_chatgpt_allow_download_transcript', 'No'));
    }
    // log each $chatbot_settings key and value
    foreach ($chatbot_settings as $key => $value) {
        // back_trace('NOTICE', '$chatbot_settings - Key: ' . $key . ' Value: ' . $value);
    }
    // Original wp_localize_script call
    // wp_localize_script('chatbot-chatgpt-local', 'chatbotSettings', $chatbot_settings);
    // Refactored wp_localize_script call - Ver 2.0.5 - 2024 07 06
    $chatbot_settings_json = wp_json_encode($chatbot_settings);
    wp_add_inline_script('chatbot-chatgpt-local', 'if (typeof chatbotSettings === "undefined") { var chatbotSettings = ' . $chatbot_settings_json . '; } else { chatbotSettings = ' . $chatbot_settings_json . '; }', 'before');

    // DIAG - Diagnostics - Ver 2.0.5
    // back_trace('NOTICE', '$bot_name: ' . $bot_name);

    $chatbot_chatgpt_bot_prompt = esc_attr(get_option('chatbot_chatgpt_bot_prompt', 'Enter your question ...'));

    // Hot Prompt the Chatbot - Ver 1.9.0
    if (!empty($chatbot_chatgpt_hot_bot_prompt)) {
        wp_add_inline_script('chatbot-chatgpt', 'document.getElementById("chatbot-chatgpt-message").placeholder = "' . $chatbot_chatgpt_hot_bot_prompt . '";');
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

    // Assistant's Table Override - Ver 2.0.4
    // FIXME - FORCE PAGE RELOAD

    // Assume that the chatbot is NOT using KFlow - Ver 1.9.5
    $use_flow = 'No';

    // Retrieve the custom buttons on/off setting - Ver 1.6.5
    // $chatbot_chatgpt_enable_custom_buttons = esc_attr(get_option('chatbot_chatgpt_enable_custom_buttons', 'Off'));

    // KFlow - Call kflow_prompt_and_response() - Ver 1.9.5
    if (function_exists('kflow_prompt_and_response') and !empty($atts['sequence'])) {

        // BELT & SUSPENDERS - Ver 1.9.5
        $use_flow = 'Yes';

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

        // DIAG - Diagnostics - Ver 1.9.5
        // back_trace( 'NOTICE', '$kflow_data: ' . print_r($kflow_data, true));
        // back_trace( 'NOTICE', '$script_data_array: ' . print_r($script_data_array, true));
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
            $use_flow = 'No';

            // No prompt was returned
            // Use the default prompt
            $chatbot_chatgpt_hot_bot_prompt = '';

            // BELT & SUSPENDERS - Ver 1.9.5
            $model = esc_attr(get_option('chatbot_chatgpt_model_choice', 'gpt-3.5-turbo'));
            $script_data_array['model'] = $model;

        }

    } else {

        // Handle the case where the function does not exist
        // Throw an error or return a default value, etc.
        // DIAG - Diagnostics - Ver 1.9.5
        // back_trace( 'WARNING', 'kflow modules not installed');

    }
    
    // Miscellaneous Other Setting to pass to localStorage - Ver 2.0.5
    $chatbot_chatgpt_width_setting = esc_attr(get_option('chatbot_chatgpt_width_setting', 'Narrow'));
    // $assistant_details['chatbot_chatgpt_width_setting'] = $chatbot_chatgpt_width_setting;
    $chatbot_settings['chatbot_chatgpt_width_setting'] = $chatbot_chatgpt_width_setting;

    $chatbot_chatgpt_start_status = esc_attr(get_option('chatbot_chatgpt_start_status', 'open'));
    // $assistant_details['chatbot_chatgpt_start_status'] = $chatbot_chatgpt_start_status;
    $chatbot_settings['chatbot_chatgpt_start_status'] = $chatbot_chatgpt_start_status;

    $chatbot_chatgpt_start_status_new_visitor = esc_attr(get_option('chatbot_chatgpt_start_status_new_visitor', 'closed'));
    // $assistant_details['chatbot_chatgpt_start_status_new_visitor'] = $chatbot_chatgpt_start_status_new_visitor;
    $chatbot_settings['chatbot_chatgpt_start_status_new_visitor'] = $chatbot_chatgpt_start_status_new_visitor;

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

    // back_trace( 'NOTICE', 'AT 649 - $assistant_details[\'initial_greeting\']: ' . $assistant_details['initial_greeting']);
    // back_trace( 'NOTICE', 'AT 649 - $assistant_details[\'subsequent_greeting\']: ' . $assistant_details['subsequent_greeting']);

    // FIXME - WHY DO I NEED TO DO THIS? - THE KEY SHOULD PROBABLY BE 'chatbot_chatgpt_initial_greeting' ALL ALONG
    $assistant_details['chatbot_chatgpt_initial_greeting'] = $assistant_details['initial_greeting'];
    $chatbot_settings['chatbot_chatgpt_initial_greeting'] = $assistant_details['initial_greeting'];
    // FIXME - WHY DO I NEED TO DO THIS? - THE KEY SHOULD PROBABLY BE 'chatbot_chatgpt_subsequent_greeting' ALL ALONG
    $assistant_details['chatbot_chatgpt_subsequent_greeting'] = $assistant_details['subsequent_greeting'];
    $chatbot_settings['chatbot_chatgpt_subsequent_greeting'] = $assistant_details['subsequent_greeting'];

    // DIAG - Diagnostics - Ver 2.0.5
    // back_trace( 'NOTICE', '$modified_greetings: ' . print_r($modified_greetings, true));

    // REMOVED - Ver 2.0.5
    // chatbot_chatgpt_shortcode_enqueue_script();

    // Last chance to set localStorage - Ver 2.0.5
    $assistant_details['style'] = !empty($assistant_details['style']) ? $assistant_details['style'] : esc_attr(get_option('chatbot_chatgpt_display_style', 'floating'));
    $chatbot_settings['chatbot_chatgpt_display_style'] = $assistant_details['style'];

    $assistant_details['audience'] = !empty($assistant_details['audience']) ? $assistant_details['audience'] : esc_attr(get_option('chatbot_chatgpt_audience_choice', 'All'));
    $chatbot_settings['chatbot_chatgpt_audience_choice'] = $assistant_details['audience'];
    
    // DIAG - Diagnostics - Ver 2.0.5
    // back_trace( 'NOTICE', 'BEFORE: $assistant_details[\'voice\']: ' . $assistant_details['voice']);
    // back_trace( 'NOTICE', 'BEFORE: $chatbot_settings[\'chatbot_chatgpt_voice_option\']: ' . $chatbot_settings['chatbot_chatgpt_voice_option']);
    // back_trace( 'NOTICE', 'BEFORE: $script_data_array[\'voice\']: ' . $script_data_array['voice']);
    
    $assistant_details['voice'] = !empty($assistant_details['voice']) ? $assistant_details['voice'] : esc_attr(get_option('chatbot_chatgpt_voice_option', 'alloy'));
    $chatbot_settings['chatbot_chatgpt_voice_option'] = $assistant_details['voice'];
    $script_data_array['voice'] = $assistant_details['voice'];
    set_chatbot_chatgpt_transients('voice', $assistant_details['voice'], $user_id, $page_id, null, null);

    // DIAG - Diagnostics - Ver 2.0.5
    // back_trace( 'NOTICE', 'AFTER: $assistant_details[\'voice\']: ' . $assistant_details['voice']);
    // back_trace( 'NOTICE', 'AFTER: $chatbot_settings[\'chatbot_chatgpt_voice_option\']: ' . $chatbot_settings['chatbot_chatgpt_voice_option']);
    // back_trace( 'NOTICE', 'AFTER: $script_data_array[\'voice\']: ' . $script_data_array['voice']);

    $assistant_details['allow_file_uploads'] = !empty($assistant_details['allow_file_uploads']) ? $assistant_details['allow_file_uploads'] : esc_attr(get_option('chatbot_chatgpt_allow_file_uploads', 'No'));
    $chatbot_settings['chatbot_chatgpt_allow_file_uploads'] = $assistant_details['allow_file_uploads'];

    $assistant_details['allow_mp3_uploads'] = !empty($assistant_details['allow_mp3_uploads']) ? $assistant_details['allow_mp3_uploads'] : esc_attr(get_option('chatbot_chatgpt_allow_mp3_uploads', 'No'));
    $chatbot_settings['chatbot_chatgpt_allow_mp3_uploads'] = $assistant_details['allow_mp3_uploads'];

    $assistant_details['allow_read_aloud'] = !empty($assistant_details['allow_read_aloud']) ? $assistant_details['allow_read_aloud'] : esc_attr(get_option('chatbot_chatgpt_read_aloud_option', 'yes'));
    $chatbot_settings['chatbot_chatgpt_read_aloud_option'] = $assistant_details['allow_read_aloud'];

    $assistant_details['allow_transcript_downloads'] = !empty($assistant_details['allow_transcript_downloads']) ? $assistant_details['allow_transcript_downloads'] : esc_attr(get_option('chatbot_chatgpt_allow_download_transcript', 'Yes'));
    $chatbot_settings['chatbot_chatgpt_allow_download_transcript'] = $assistant_details['allow_transcript_downloads'];

    $assistant_details['additional_instructions'] = !empty($assistant_details['additional_instructions']) ? $assistant_details['additional_instructions'] : esc_attr(get_option('chatbot_chatgpt_additional_instructions', ''));
    $chatbot_settings['chatbot_chatgpt_additional_instructions'] = $assistant_details['additional_instructions'];

    $assistant_details['force_page_reload'] = !empty($assistant_details['force_page_reload']) ? $assistant_details['force_page_reload'] : esc_attr(get_option('chatbot_chatgpt_force_page_reload', 'No'));
    $chatbot_settings['chatbot_chatgpt_force_page_reload'] = $assistant_details['force_page_reload'];

    $assistant_details['width'] = !empty($assistant_details['width']) ? $assistant_details['width'] : esc_attr(get_option('chatbot_chatgpt_width_setting', '300'));
    $chatbot_settings['chatbot_chatgpt_width_setting'] = $assistant_details['width'];

    $assistant_details['common_name'] = !empty($assistant_details['common_name']) ? $assistant_details['common_name'] : esc_attr(get_option('chatbot_chatgpt_bot_name', 'Kognetiks Chatbot'));
    $chatbot_settings['chatbot_chatgpt_bot_name'] = $assistant_details['common_name'];
    $chatbot_settings['chatbot_chatgpt_bot_name'] = !empty($assistant_details['common_name']) ? $assistant_details['common_name'] : esc_attr(get_option('chatbot_chatgpt_bot_name', 'Kognetiks Chatbot'));
    
    // DIAG - Diagnostics - Ver 2.0.5
    // back_trace( 'NOTICE', '$chatbot_settings: ' . print_r($chatbot_settings, true));
    // back_trace( 'NOTICE', '$assistant_details: ' . print_r($assistant_details, true));

    // OUTSIDE OF THE IF STATEMENT - Ver 2.0.5 - 2024 07 05
    ob_start();

    // Push data to local storage for the Chatbot - Ver 2.0.5
    echo '<script type="text/javascript">
            window.onload = function() {
                // console.log("Chatbot: NOTICE: chatbot-shortcode.php - STARTED");
                // Encode the chatbot settings array into JSON format for use in JavaScript
                let chatbotSettings = ' . json_encode($chatbot_settings) . ';
                if (chatbotSettings && typeof chatbotSettings === "object") {
                    Object.keys(chatbotSettings).forEach(function(key) {
                        if (key === "assistant_id" || key === "chatbot_chatgpt_assistant_alias") {
                            return;
                        }
                        // DIAG - Diagnostics - Ver 2.0.5
                        // console.log("Chatbot: NOTICE: chatbot-shortcode.php - Key: " + key + " Value: " + chatbotSettings[key]);
                        localStorage.setItem(key, chatbotSettings[key]);
                    });
                }
                // console.log("Chatbot: NOTICE: chatbot-shortcode.php - FINISHED");
            };
        </script>';

    // Depending on the style, adjust the output - Ver 1.7.1
    if ($chatbot_chatgpt_display_style == 'embedded') {
        // Code for embed style ('embedded' is the alternative style)
        // Store the style and the assistant value - Ver 1.7.2
        set_chatbot_chatgpt_transients( 'display_style' , $chatbot_chatgpt_display_style, $user_id, $page_id, null, null );
        set_chatbot_chatgpt_transients( 'assistant_alias' , $chatbot_chatgpt_assistant_alias, $user_id, $page_id, null, null );
        set_chatbot_chatgpt_transients( 'model' , $model, $user_id, $page_id, null, null);
        set_chatbot_chatgpt_transients( 'voice' , $voice, $user_id, $page_id, null, null);
        set_chatbot_chatgpt_transients( 'assistant_name' , $bot_name, $user_id, $page_id, null, null);
        // OUTSIDE OF THE IF STATEMENT - Ver 2.0.5 - 2024 07 05
        // ob_start();
        ?>
        <div id="chatbot-chatgpt"  style="display: flex;" class="embedded-style chatbot-full">
        <!-- <script>
            $(document).ready(function() {
                $('#chatbot-chatgpt').removeClass('floating-style').addClass('embedded-style');
            });
        </script> -->
        <!-- REMOVED FOR EMBEDDED -->
        <?php
        if ( $use_assistant_name == 'Yes' ) {
            echo '<div id="chatbot-chatgpt-header-embedded">';
            echo '<div id="chatgptTitle" class="title">' . $bot_name . '</div>';
            echo '</div>';
        } else {
            echo '<div id="chatbot-chatgpt-header-embedded">';
            // DO NOTHING
            echo '</div>';
        }
        ?>
        <div id="chatbot-chatgpt-conversation"></div>
        <div id="chatbot-chatgpt-input" style="display: flex; justify-content: center; align-items: start; gap: 5px; width: 100%;">
            <div style="flex-grow: 1; max-width: 95%;">
                <label for="chatbot-chatgpt-message"></label>
                <?php
                    // FIXME - ADD THIS TO FLOATING STYLE BELOW - Ver 1.9.5
                    // Kick off Flow - Ver 1.9.5
                    if ($use_flow == 'Yes' and !empty($sequence_id)) {
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
                                    submitButton.trigger('click');
                                }, 500); // Delay of 1 second
                            }
                        });
                        </script>";
                    }
                    // Preload with a prompt if it is set - Ver 1.9.5
                    if ($use_flow != 'Yes' and !empty($chatbot_chatgpt_hot_bot_prompt)) {
                        // DIAG - Diagnostics - Ver 1.9.0
                        // back_trace( 'NOTICE', 'chatbot_chatgpt_bot_prompt: ' . $chatbot_chatgpt_bot_prompt);
                        $rows = esc_attr(get_option('chatbot_chatgpt_input_rows', '2'));
                        $chatbot_chatgpt_bot_prompt = esc_attr(sanitize_text_field($chatbot_chatgpt_bot_prompt));
                        $chatbot_chatgpt_hot_bot_prompt = esc_attr(sanitize_text_field($chatbot_chatgpt_hot_bot_prompt));
                        echo "<textarea id='chatbot-chatgpt-message' rows='$rows' placeholder='$chatbot_chatgpt_bot_prompt' style='width: 95%;'>$chatbot_chatgpt_hot_bot_prompt</textarea>";
                        echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            var textarea = document.getElementById('chatbot-chatgpt-message');
                            textarea.value += '\\n';
                            textarea.focus();

                            setTimeout(function() {
                                var submitButton = document.getElementById('chatbot-chatgpt-submit');
                                if (submitButton) {
                                    submitButton.trigger('click');
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
                        echo "<center><textarea id='chatbot-chatgpt-message' rows='$rows' placeholder='$chatbot_chatgpt_bot_prompt' style='width: 95%;'></textarea></center>";
                    }
                ?>
            </div>
        </div>
        <div id="chatbot-chatgpt-buttons-container" style="justify-content: center; flex-grow: 0; display: flex; flex-direction: row; align-items: center; gap: 5px;">
            <button id="chatbot-chatgpt-submit" title="Send Message">
                <img src="<?php echo plugins_url('../assets/icons/send_FILL0_wght400_GRAD0_opsz24.png', __FILE__); ?>" alt="Send">
            </button>
            <?php if ($chatbot_chatgpt_allow_file_uploads == 'Yes'): ?>
                <input type="file" id="chatbot-chatgpt-upload-file-input" name="file[]" style="display: none;" multiple="multiple" />
                <button id="chatbot-chatgpt-upload-file" title="Upload Files">
                    <img src="<?php echo plugins_url('../assets/icons/attach_file_FILL0_wght400_GRAD0_opsz24.png', __FILE__); ?>" alt="Upload File">
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
                    <img src="<?php echo plugins_url('../assets/icons/attach_file_FILL0_wght400_GRAD0_opsz24.png', __FILE__); ?>" alt="Upload MP3">
                </button>
                <script type="text/javascript">
                    document.getElementById('chatbot-chatgpt-upload-mp3').addEventListener('click', function() {
                        document.getElementById('chatbot-chatgpt-upload-mp3-input').click();
                    });
                </script>
            <?php endif; ?>
            <button id="chatbot-chatgpt-erase-btn" title="Clear Conversation">
                <img src="<?php echo plugins_url('../assets/icons/delete_FILL0_wght400_GRAD0_opsz24.png', __FILE__); ?>" alt="Erase Conversation">
            </button>
            <?php if ($chatbot_chatgpt_read_aloud_option == 'yes' && $voice != 'none'): ?>
                <button id="chatbot-chatgpt-text-to-speech-btn" title="Read Aloud">
                    <img src="<?php echo plugins_url('../assets/icons/text_to_speech_FILL0_wght400_GRAD0_opsz24.png', __FILE__); ?>" alt="Read Out Loud">
                </button>
            <?php endif; ?>
            <?php if ($chatbot_chatgpt_allow_download_transcript == 'Yes'): ?>
                <button id="chatbot-chatgpt-download-transcript-btn" title="Download Transcript">
                    <img src="<?php echo plugins_url('../assets/icons/download_FILL0_wght400_GRAD0_opsz24.png', __FILE__); ?>" alt="Download Transcript">
                </button>
            <?php endif; ?>
            </div>
            <!-- Custom buttons - Ver 1.6.5 -->
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
        <!-- <i class="dashicons dashicons-format-chat"></i> -->
        <i class="chatbot-open-icon"></i>
        </button>
        <!-- </div> -->
        <?php
        return ob_get_clean();
    } elseif ($chatbot_chatgpt_display_style == 'floating') {
        // Code for bot style ('floating' is the default style)
        // Store the style and the assistant value - Ver 1.7.2
        set_chatbot_chatgpt_transients( 'display_style' , $chatbot_chatgpt_display_style, $user_id, $page_id, null, null );
        set_chatbot_chatgpt_transients( 'assistant_alias' , $chatbot_chatgpt_assistant_alias, $user_id, $page_id, null, null );
        set_chatbot_chatgpt_transients( 'model' , $model, $user_id, $page_id, null, null);
        set_chatbot_chatgpt_transients( 'voice' , $voice, $user_id, $page_id, null, null);
        set_chatbot_chatgpt_transients( 'assistant_name' , $bot_name, $user_id, $page_id, null, null);
        // OUTSIDE OF THE IF STATEMENT - Ver 2.0.5 - 2024 07 05
        // ob_start();
        ?>
        <div id="chatbot-chatgpt">
            <div id="chatbot-chatgpt-header">
                <div id="chatgptTitle" class="title"><?php echo $bot_name; ?></div>
            </div>
            <div id="chatbot-chatgpt-conversation"></div>
            <div id="chatbot-chatgpt-input" style="display: flex; justify-content: center; align-items: start; gap: 5px; width: 95%;">
                <div style="flex-grow: 1; max-width: 95%;">
                    <label for="chatbot-chatgpt-message"></label>
                    <!-- <textarea id="chatbot-chatgpt-message" rows="2" placeholder="<?php echo esc_attr($chatbot_chatgpt_bot_prompt); ?>" style="width: 95%;"></textarea> -->
                    <?php
                        // Kick off Flow - Ver 1.9.5
                        if ($use_flow == 'Yes' and !empty($sequence_id)) {
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
                                        submitButton.trigger('click');
                                    }, 500); // Delay of 1 second
                                }
                            });
                            </script>";
                        }
                        // Preload with a prompt if it is set - Ver 1.9.5
                        if ($use_flow != 'Yes' and !empty($chatbot_chatgpt_hot_bot_prompt)) {
                            $rows = esc_attr(get_option('chatbot_chatgpt_input_rows', '2'));
                            $chatbot_chatgpt_bot_prompt = esc_attr(sanitize_text_field($chatbot_chatgpt_bot_prompt));
                            $chatbot_chatgpt_hot_bot_prompt = esc_attr(sanitize_text_field($chatbot_chatgpt_hot_bot_prompt));
                            echo "<center><textarea id='chatbot-chatgpt-message' rows='$rows' placeholder='$chatbot_chatgpt_bot_prompt' style='width: 95%;'>$chatbot_chatgpt_hot_bot_prompt</textarea></center>";
                            echo "<script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var textarea = document.getElementById('chatbot-chatgpt-message');
                                textarea.value += '\\n';
                                textarea.focus();
                                setTimeout(function() {
                                    var submitButton = document.getElementById('chatbot-chatgpt-submit');
                                    if (submitButton) {
                                        submitButton.trigger('click');
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
                            echo "<center><textarea id='chatbot-chatgpt-message' rows='$rows' placeholder='$chatbot_chatgpt_bot_prompt' style='width: 95%;'></textarea></center>";
                        }
                    ?>
                </div>
            </div>
            <div id="chatbot-chatgpt-buttons-container" style="justify-content: center; flex-grow: 0; display: flex; flex-direction: row; align-items: center; gap: 5px;">
                <button id="chatbot-chatgpt-submit" title="Send Message">
                    <img src="<?php echo plugins_url('../assets/icons/send_FILL0_wght400_GRAD0_opsz24.png', __FILE__); ?>" alt="Send">
                </button>
                <?php if ($chatbot_chatgpt_allow_file_uploads == 'Yes'): ?>
                    <input type="file" id="chatbot-chatgpt-upload-file-input" name="file[]" style="display: none;" multiple="multiple" />
                    <button id="chatbot-chatgpt-upload-file" title="Upload Files">
                        <img src="<?php echo plugins_url('../assets/icons/attach_file_FILL0_wght400_GRAD0_opsz24.png', __FILE__); ?>" alt="Upload File">
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
                        <img src="<?php echo plugins_url('../assets/icons/attach_file_FILL0_wght400_GRAD0_opsz24.png', __FILE__); ?>" alt="Upload MP3">
                    </button>
                    <script type="text/javascript">
                        document.getElementById('chatbot-chatgpt-upload-mp3').addEventListener('click', function() {
                            document.getElementById('chatbot-chatgpt-upload-mp3-input').click();
                        });
                    </script>
                <?php endif; ?>
                <button id="chatbot-chatgpt-erase-btn" title="Clear Conversation">
                    <img src="<?php echo plugins_url('../assets/icons/delete_FILL0_wght400_GRAD0_opsz24.png', __FILE__); ?>" alt="Erase Conversation">
                </button>
                <?php if ($chatbot_chatgpt_read_aloud_option == 'yes' && $voice != 'none'): ?>
                    <button id="chatbot-chatgpt-text-to-speech-btn" title="Read Aloud">
                        <img src="<?php echo plugins_url('../assets/icons/text_to_speech_FILL0_wght400_GRAD0_opsz24.png', __FILE__); ?>" alt="Read Out Loud">
                    </button>
                <?php endif; ?>
                <?php if ($chatbot_chatgpt_allow_download_transcript == 'Yes'): ?>
                    <button id="chatbot-chatgpt-download-transcript-btn" title="Download Transcript">
                        <img src="<?php echo plugins_url('../assets/icons/download_FILL0_wght400_GRAD0_opsz24.png', __FILE__); ?>" alt="Download Transcript">
                    </button>
                <?php endif; ?>
            </div>
            <!-- Custom buttons - Ver 1.6.5 -->
            <?php
            $chatbot_chatgpt_enable_custom_buttons = esc_attr(get_option('chatbot_chatgpt_enable_custom_buttons', 'Off'));
            if ($chatbot_chatgpt_enable_custom_buttons == 'On' || $chatbot_chatgpt_enable_custom_buttons == 'Floating' || $chatbot_chatgpt_enable_custom_buttons == 'Both') {
                chatbot_chatgpt_custom_buttons_display();
            }
            // Attribution - Ver 2.0.5
            chatbot_chatgpt_attribution();
            ?>
        </div>
        <button id="chatgpt-open-btn" style="display: none;">
        <!-- <i class="dashicons dashicons-format-chat"></i> -->
        <i class="chatbot-open-icon"></i>
        </button>
        <!-- </div> OMIT THE CLOSING /DIV STATEMENT FOR FLOATING -->
        <?php
        return ob_get_clean();
    }

}
// add_shortcode('chatbot', 'chatbot_chatgpt_shortcode');
// add_shortcode('chatbot_chatgpt', 'chatbot_chatgpt_shortcode');
// add_shortcode('kognetiks_chatbot', 'chatbot_chatgpt_shortcode');
// add_shortcode('chatbot-1', 'chatbot_chatgpt_shortcode');
// add_shortcode('chatbot-2', 'chatbot_chatgpt_shortcode');
// add_shortcode('chatbot-3', 'chatbot_chatgpt_shortcode');

// Dynamic Shortcode - Ver 2.0.6
function register_chatbot_shortcodes($number_of_shortcodes = null) {

    // Make sure the number of shortcodes is set
    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_assistants';

    // The $number_of_shortcodes is id of the highest assistant in the table
    $number_of_shortcodes = $wpdb->get_var("SELECT MAX(id) FROM $table_name");

    update_option('chatbot_chatgpt_number_of_shortcodes', $number_of_shortcodes);

    error_log('chabot-shortcode.php - Number of shortcodes: ' . $number_of_shortcodes);

    // Fetch the number of shortcodes to 
    $number_of_shortcodes = $number_of_shortcodes ?? esc_attr(get_option('chatbot_chatgpt_number_of_shortcodes', 1));

    // Base shortcode names
    $base_shortcodes = [
        'chatbot',
        'chatbot_chatgpt',
        'kognetiks_chatbot'
    ];

    // Register base shortcodes
    foreach ($base_shortcodes as $shortcode) {
        add_shortcode($shortcode, 'chatbot_chatgpt_shortcode');
    }

    // Register numbered shortcodes dynamically
    for ($i = 1; $i <= $number_of_shortcodes; $i++) {
        add_shortcode('chatbot-' . $i, 'chatbot_chatgpt_shortcode');
        error_log('chabot-shortcode.php - Registered shortcode: chatbot-' . $i);
        // back_trace( 'NOTICE', 'Registered shortcode: chatbot-' . $i);
    }
    
}
register_chatbot_shortcodes();

// Custom Buttons - Ver 2.0.5
function chatbot_chatgpt_custom_buttons_display() {
    ?>
    <div id="chatbot-chatgpt-custom-buttons" style="justify-content: center; flex-grow: 0; display: flex; flex-direction: row; align-items: center; gap: 5px; padding: 5px;">
        <?php
        $button_names = [];
        $button_urls = [];
        $button_count = 4; // Maximum number of buttons

        // Initialize and set button names and URLs
        for ($i = 1; $i <= $button_count; $i++) {
            $button_names[$i] = get_option("chatbot_chatgpt_custom_button_name_$i");
            $button_urls[$i] = get_option("chatbot_chatgpt_custom_button_url_$i");
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
function chatbot_chatgpt_attribution () {

    $chatbot_chatgpt_suppress_attribution = esc_attr(get_option('chatbot_chatgpt_suppress_attribution', 'Off'));
    // DIAG - Diagnostics - Ver 1.6.5
    // back_trace( 'NOTICE', 'chatbot_chatgpt_suppress_attribution: ' . $chatbot_chatgpt_suppress_attribution);
    if ($chatbot_chatgpt_suppress_attribution == 'Off') {
        ?>
        <div style="text-align: center;">
            <!-- <a href="https://kognetiks.com/wordpress-plugins/kognetiks-chatbot/?utm_source=chatbot&utm_medium=website&utm_campaign=powered_by&utm_id=plugin" target="_blank" rel="noopener noreferrer" style="text-decoration:none; font-size: 10px;"><?php echo esc_html('Chatbot & Knowledge Navigator by Kognetiks'); ?></a> -->
            <a href="https://kognetiks.com/wordpress-plugins/kognetiks-chatbot/?utm_source=chatbot&utm_medium=website&utm_campaign=powered_by&utm_id=plugin" target="_blank" rel="noopener noreferrer" style="text-decoration:none; font-size: 10px;"><?php echo esc_html('Chatbot WordPress plugin by Kognetiks'); ?></a>
        </div>
        <?php
    }

}

// Fix Updating failed. The response is not a valid JSON response. - Version 1.7.3
// Function to output the script
function chatbot_chatgpt_shortcode_enqueue_script() {

    // Added these lines to get the global variables - Ver 1.9.3 - 2024 03 16
    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;
    global $script_data_array;
    global $additional_instructions;
    global $model;
    global $voice;

    global $chatbot_chatgpt_display_style;
    global $chatbot_chatgpt_assistant_alias;

    // These were already here - Ver 1.9.3 - 2024 03 16
    global $chatbot_chatgpt_display_style;
    global $chatbot_chatgpt_assistant_alias;

    global $chatbot_settings;

    // Check if the variables are set and not empty
    $style = $chatbot_chatgpt_display_style ?? '';
    $assistant = $chatbot_chatgpt_assistant_alias ?? '';

    // Preload avatar - Ver 2.0.3
    $avatar_icon_setting = esc_attr(get_option('chatbot_chatgpt_avatar_icon_setting', ''));
    $custom_avatar_icon_setting = esc_attr(get_option('chatbot_chatgpt_custom_avatar_icon_setting', ''));

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
    // back_trace ( 'NOTICE', '$chatbot_chatgpt_display_style: ' . $chatbot_chatgpt_display_style);
    // back_trace( 'NOTICE', '$script_data_array: ' . print_r($script_data_array, true));

    ?>
    <script>

        function updateChatbotLocalStorage() {
            // Loop through the chatbot settings and set them in localStorage
            // for use in the Chatbot
            // Encode the chatbot settings array into JSON format for use in JavaScript
            chatbotSettings = <?php echo json_encode($chatbot_settings); ?>;
            if (chatbotSettings && typeof chatbotSettings === "object") {
                Object.keys(chatbotSettings).forEach(function(key) {
                    // DIAG - Diagnostics - Ver 2.0.4
                    // console.log("Chatbot: NOTICE: chatbot-shortcode.php - Key: " + key + " Value: " + chatbotSettings[key]);
                    localStorage.setItem(key, chatbotSettings[key]);
                });
            }

            // Check if the variables are not empty before setting them in localStorage
            if ('<?php echo $style; ?>' !== '') {
                localStorage.setItem('chatbot_chatgpt_display_style', '<?php echo $style; ?>');
            }
            if ('<?php echo $assistant; ?>' !== '') {
                localStorage.setItem('chatbot_chatgpt_assistant_alias', '<?php echo $assistant; ?>');
            }
            
            // Preload avatar - Ver 2.0.3
            if ('<?php echo $avatar_icon_setting; ?>' !== '') {
                localStorage.setItem('chatbot_chatgpt_avatar_icon_setting', '<?php echo $avatar_icon_setting; ?>');
            }
            if ('<?php echo $custom_avatar_icon_setting; ?>' !== '') {
                localStorage.setItem('chatbot_chatgpt_custom_avatar_icon_setting', '<?php echo $custom_avatar_icon_setting; ?>');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Update the localStorage with the chatbot settings
            updateChatbotLocalStorage();
        }); 
        
    </script>
    <?php

}
// Hook this function into the 'wp_footer' action
add_action('wp_footer', 'chatbot_chatgpt_shortcode_enqueue_script');
