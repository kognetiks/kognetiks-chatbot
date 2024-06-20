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

    global $kflow_data;

    // DIAG - Diagnostics - Ver 1.9.3
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

    // Script Attributes
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

    // Shortcode Attributes
    $chatbot_chatgpt_default_atts = array(
        'style' => 'floating', // Default value
        'assistant' => 'original', // Default value
        'audience' => 'all', // If not passed then default value
        'prompt' => '', // If not passed then default value
        'sequence' => '', // If not passed then default value
        'additional_instructions' => '', // If not passed then default value
        'model' => $model_choice, // If not passed then default value
        'voice' => 'alloy', // If not passed then default value
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

    // Validate and sanitize the style parameter - Ver 1.9.9
    $valid_styles = ['floating', 'embedded'];
    $chatbot_chatgpt_display_style = 'floating'; // default value
    if (array_key_exists('style', $atts) && !is_null($atts['style'])) {
        if (in_array($atts['style'], $valid_styles)) {
            $chatbot_chatgpt_display_style = sanitize_text_field($atts['style']);
            // back_trace('NOTICE', '$chatbot_chatgpt_display_style: ' . $chatbot_chatgpt_display_style);
        } else {
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
            // back_trace('NOTICE', '$chatbot_chatgpt_audience_choice: ' . $chatbot_chatgpt_audience_choice);
        } else {
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
    $valid_voices = ['alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer'];
    $voice = 'alloy'; // default value
    if (array_key_exists('voice', $atts)) {
        $sanitized_voice = sanitize_text_field($atts['voice']);
        if (in_array($sanitized_voice, $valid_voices)) {
            $voice = $sanitized_voice;
            // back_trace('NOTICE', '$voice: ' . $voice);
        } else {
            $voice = esc_attr(get_option('chatbot_chatgpt_voice_option', 'alloy'));
            // back_trace('NOTICE', 'Voice (defaulting): ' . $voice);
        }
    } else {
        $voice = esc_attr(get_option('chatbot_chatgpt_voice_option', 'alloy'));
        // back_trace('NOTICE', 'Voice (defaulting): ' . $voice);
    }
    $script_data_array['voice'] = $voice;

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
   
    // Store the style and the assistant value - Ver 1.7.2
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

    // DIAG - Diagnostics - Ver 1.9.1
    // back_trace( 'NOTICE', 'LINE 145 $user_id: ' . $user_id);
    // back_trace( 'NOTICE', 'LINE 146 $page_id: ' . $page_id);

    if ( $chatbot_chatgpt_assistant_alias == 'primary' ) {
        $assistant_id = esc_attr(get_option('chatbot_chatgpt_assistant_id', ''));
        $additional_instructions = esc_attr(get_option('chatbot_chatgpt_additional_instructions', ''));
    } elseif ( $chatbot_chatgpt_assistant_alias == 'alternate' ) {
        $assistant_id = esc_attr(get_option('chatbot_chatgpt_assistant_id_alternate', ''));
        $additional_instructions = esc_attr(get_option('chatbot_chatgpt_additional_instructions_alternate', ''));
    } else {
        // Do nothing as either the assistant_id is set to the GPT Assistant ID or it is not set at all
        $additional_instructions = array_key_exists('instructions', $atts) ? sanitize_text_field($atts['instructions']) : '';
    }

    set_chatbot_chatgpt_transients( 'display_style' , $chatbot_chatgpt_display_style, $user_id, $page_id, null, null );
    set_chatbot_chatgpt_transients( 'assistant_alias' , $chatbot_chatgpt_assistant_alias, $user_id, $page_id, null, null );
    set_chatbot_chatgpt_transients( 'model' , $model, $user_id, $page_id, null, null);
    set_chatbot_chatgpt_transients( 'voice' , $voice, $user_id, $page_id, null, null);

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

    // Retrieve the bot name - Ver 1.1.0
    // Get the Assistant's name - Ver 1.9.4
    $use_assistant_name = esc_attr(get_option('chatbot_chatgpt_display_custom_gpt_assistant_name', 'No'));
    if ($use_assistant_name == 'Yes' && $assistant_id != ''){
        $assistant_name = esc_attr(get_chatbot_chatgpt_assistant_name($assistant_id));
        if (!empty($assistant_name)) {
            $bot_name = $assistant_name;
        } else {
            $bot_name = esc_attr(get_option('chatbot_chatgpt_bot_name', 'Kognetiks Chatbot'));
        }
    } else {
        $bot_name = esc_attr(get_option('chatbot_chatgpt_bot_name', 'Kognetiks Chatbot'));
    }

    $chatbot_chatgpt_bot_prompt = esc_attr(get_option('chatbot_chatgpt_bot_prompt', 'Enter your question ...'));

    // Hot Prompt the Chatbot - Ver 1.9.0
    if (!empty($chatbot_chatgpt_hot_bot_prompt)) {
        wp_add_inline_script('chatbot-chatgpt', 'document.getElementById("chatbot-chatgpt-message").placeholder = "' . $chatbot_chatgpt_hot_bot_prompt . '";');
    }

    // Allow File Uploads - Ver 1.9.0
    $chatbot_chatgpt_allow_file_uploads = 'No';
    $chatbot_chatgpt_allow_mp3_uploads = 'No';

    if ($chatbot_chatgpt_assistant_alias == 'original') {
        $chatbot_chatgpt_allow_file_uploads = 'No';
        $chatbot_chatgpt_allow_mp3_uploads = 'No';
    }

    if (strpos($chatbot_chatgpt_assistant_alias,'asst_') !== false) {
        $chatbot_chatgpt_allow_file_uploads = esc_attr(get_option('chatbot_chatgpt_allow_file_uploads', 'No'));
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


    // Allow Read Aloud - Ver 1.9.0
    $chatbot_chatgpt_read_aloud_option = esc_attr(get_option('chatbot_chatgpt_read_aloud_option', 'yes'));

    // Allo Download Transcript - Ver 2.0.3
    $chatbot_chatgpt_allow_download_transcript = esc_attr(get_option('chatbot_chatgpt_allow_download_transcript', 'Yes'));

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

    // Depending on the style, adjust the output - Ver 1.7.1
    if ($chatbot_chatgpt_display_style == 'embedded') {
        // Code for embed style ('embedded' is the alternative style)
        // Store the style and the assistant value - Ver 1.7.2
        set_chatbot_chatgpt_transients( 'display_style' , $chatbot_chatgpt_display_style, $user_id, $page_id, null, null );
        set_chatbot_chatgpt_transients( 'assistant_alias' , $chatbot_chatgpt_assistant_alias, $user_id, $page_id, null, null );
        set_chatbot_chatgpt_transients( 'model' , $model, $user_id, $page_id, null, null);
        set_chatbot_chatgpt_transients( 'voice' , $voice, $user_id, $page_id, null, null);
        set_chatbot_chatgpt_transients( 'assistant_name' , $bot_name, $user_id, $page_id, null, null);
        ob_start();
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
            // DO NOTHING
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
                                    // Now, add your logic here to handle promptToSubmit
                                    // For example, you might want to call an AJAX function and pass promptToSubmit as data
                                });
                    
                                // Optionally trigger the click if you need to automatically submit on page load
                                setTimeout(function() {
                                    submitButton.click();
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
                                    submitButton.click();
                                }
                            }, 500); // Delay of 1 second
                        });
                        </script>";
                    } else {
                        // DIAG - Diagnostics - Ver 1.9.5
                        // back_trace( 'NOTICE', 'chatbot_chatgpt_bot_prompt: ' . $chatbot_chatgpt_bot_prompt);
                        $rows = esc_attr(get_option('chatbot_chatgpt_input_rows', '2'));
                        $chatbot_chatgpt_bot_prompt = esc_attr(sanitize_text_field($chatbot_chatgpt_bot_prompt));
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
            <?php if ($chatbot_chatgpt_read_aloud_option == 'yes'): ?>
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
        <button id="chatgpt-open-btn" style="display: none;">
        <!-- <i class="dashicons dashicons-format-chat"></i> -->
        <i class="chatbot-open-icon"></i>
        </button>
        </div>
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
        ob_start();
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
                                        // Now, add your logic here to handle promptToSubmit
                                        // For example, you might want to call an AJAX function and pass promptToSubmit as data
                                    });
                        
                                    // Optionally trigger the click if you need to automatically submit on page load
                                    setTimeout(function() {
                                        submitButton.click();
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
                                        submitButton.click();
                                    }
                                }, 500); // Delay of 1 second
                            });
                            </script>";
                        } else {
                            $rows = esc_attr(get_option('chatbot_chatgpt_input_rows', '2'));
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
                <?php if ($chatbot_chatgpt_read_aloud_option == 'yes'): ?>
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
            // DIAG - Diagnostics - Ver 1.6.5
            // back_trace( 'NOTICE', '$chatbot_chatgpt_enable_custom_buttons: ' . $chatbot_chatgpt_enable_custom_buttons);
            if ($chatbot_chatgpt_enable_custom_buttons == 'On') {
                ?>
                <div id="chatbot-chatgpt-custom-buttons" style="justify-content: center; flex-grow: 0; display: flex; flex-direction: row; align-items: center; gap: 5px; padding: 5px;">
                    <?php
                    $chatbot_chatgpt_custom_button_name_1 = '';
                    $chatbot_chatgpt_custom_button_url_1 = '';
                    $chatbot_chatgpt_custom_button_name_2 = '';
                    $chatbot_chatgpt_custom_button_url_2 = '';
                    $chatbot_chatgpt_custom_button_name_1 = get_option('chatbot_chatgpt_custom_button_name_1');
                    $chatbot_chatgpt_custom_button_url_1 = get_option('chatbot_chatgpt_custom_button_url_1');
                    $chatbot_chatgpt_custom_button_name_2 = get_option('chatbot_chatgpt_custom_button_name_2');
                    $chatbot_chatgpt_custom_button_url_2 = get_option('chatbot_chatgpt_custom_button_url_2');
                    // DIAG - Diagnostics - Ver 1.6.5
                    // back_trace( 'NOTICE', 'chatbot_chatgpt_custom_button_name_1: ' . $chatbot_chatgpt_custom_button_name_1);
                    // back_trace( 'NOTICE', 'chatbot_chatgpt_custom_button_url_1: ' . $chatbot_chatgpt_custom_button_url_1);
                    // back_trace( 'NOTICE', 'chatbot_chatgpt_custom_button_name_2: ' . $chatbot_chatgpt_custom_button_name_2);
                    // back_trace( 'NOTICE', 'chatbot_chatgpt_custom_button_url_2: ' . $chatbot_chatgpt_custom_button_url_2);
                    if (!empty($chatbot_chatgpt_custom_button_name_1) && !empty($chatbot_chatgpt_custom_button_url_1)) {
                        ?>
                        <button class="chatbot-chatgpt-custom-button-class">
                        <a href="<?php echo esc_url($chatbot_chatgpt_custom_button_url_1); ?>" target="_blank"><?php echo esc_html($chatbot_chatgpt_custom_button_name_1); ?></a>
                        </button>
                        <?php
                    }
                    if (!empty($chatbot_chatgpt_custom_button_name_2) && !empty($chatbot_chatgpt_custom_button_url_2)) {
                        ?>
                        <button class="chatbot-chatgpt-custom-button-class">
                        <a href="<?php echo esc_url($chatbot_chatgpt_custom_button_url_2); ?>" target="_blank"><?php echo esc_html($chatbot_chatgpt_custom_button_name_2); ?></a>
                        </button>
                        <?php
                    }
                    ?>
                </div>
                <?php
            }
            $chatbot_chatgpt_suppress_attribution = 'Off'; // 'On' or 'Off'
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
            ?>
        </div>
        <button id="chatgpt-open-btn" style="display: none;">
        <!-- <i class="dashicons dashicons-format-chat"></i> -->
        <i class="chatbot-open-icon"></i>
        </button>
        <?php
        return ob_get_clean();
    }

}
add_shortcode('chatbot', 'chatbot_chatgpt_shortcode');
add_shortcode('chatbot_chatgpt', 'chatbot_chatgpt_shortcode');
add_shortcode('kognetiks_chatbot', 'chatbot_chatgpt_shortcode');


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

    // Check if the variables are set and not empty
    $style = $chatbot_chatgpt_display_style ?? '';
    $assistant = $chatbot_chatgpt_assistant_alias ?? '';

    // Preload avatar - Ver 2.0.3
    $avatar_icon_setting = esc_attr(get_option('chatbot_chatgpt_avatar_icon_setting', ''));
    $custom_avartar_icon_setting = esc_attr(get_option('chatbot_chatgpt_custom_avatar_icon_setting', ''));

    // DIAG - Diagnostics - Ver 1.9.3
    // back_trace( 'NOTICE', 'chatbot_chatgpt_shortcode_enqueue_script - at the beginning of the function');
    // back_trace( 'NOTICE', 'get_the_id(): ' . get_the_id() );
    // back_trace( 'NOTICE', '$user_id: ' . $user_id);
    // back_trace( 'NOTICE', '$page_id: ' . $page_id);
    // back_trace( 'NOTICE', '$session_id: ' . $session_id);
    // back_trace( 'NOTICE', '$thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', '$assistant_id: ' . $assistant_id);
    // back_trace( 'NOTICE', '$model: ' . $model);
    // back_trace( 'NOTICE', '$script_data_array: ' . print_r($script_data_array, true));

    ?>
    <script>
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
        if ('<?php echo $custom_avartar_icon_setting; ?>' !== '') {
            localStorage.setItem('chatbot_chatgpt_custom_avatar_icon_setting', '<?php echo $custom_avartar_icon_setting; ?>');
        }
    </script>
    <?php

}
// Hook this function into the 'wp_footer' action
add_action('wp_footer', 'chatbot_chatgpt_shortcode_enqueue_script');
