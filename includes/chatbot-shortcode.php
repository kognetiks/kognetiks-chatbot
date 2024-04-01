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
        'additiona_instructions' => $additional_instructions,
        'model' => $model
    );

    // BELT & SUSPENDERS - Ver 1.9.4
    $model_choice = esc_attr(get_option('chatbot_chatgpt_model_choice', 'gpt-3.5-turbo'));

    // Shortcode Attributes
    $chatbot_chatgpt_default_atts = array(
        'style' => 'floating', // Default value
        'assistant' => 'original', // Default value
        'audience' => '', // If not passed then default value
        'prompt' => '', // If not passed then default value
        'sequence' => '', // If not passed then default value
        'additional_instructions' => '', // If not passed then default value
        'model' => $model_choice // If not passed then default value
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
    // [chatbot style="embbeded" prompt="How do I install this plugin?"] - Embedded style with a prompt
    // [chatbot style="floating" assistant="asst_xxxxxxxxxxxxxxxxxxxxxxxx" instructions="Please ensure that you ... "] - Floating style with additional instructions
    // [chatbot style="embedded" assistant="asst_xxxxxxxxxxxxxxxxxxxxxxxx" instructions="Please ensure that you ... "] - Embedded style with additional instructions
    //
    // Model Selection
    //
    // [chatbot style="floating" model="gpt-4-turbo-preview"] - Floating style using the GPT-4 Turbo Preview model
    // [chatbot style="embedded" model="dall-e-3"] - Embedded style using the DALL-E 3 model

    // normalize attribute keys, lowercase
    $atts = array_change_key_case((array)$atts, CASE_LOWER);

    // Combine user attributes with default attributes
    $atts = shortcode_atts($chatbot_chatgpt_default_atts, $atts, 'chatbot_chatgpt');

    // Sanitize the 'style' attribute to ensure it contains safe data
    $chatbot_chatgpt_display_style = array_key_exists('style', $atts) ? sanitize_text_field($atts['style']) : 'floating';

    // Sanitize the 'assistant' attribute to ensure it contains safe data
    $chatbot_chatgpt_assistant_alias = array_key_exists('assistant', $atts) ? sanitize_text_field($atts['assistant']) : 'original';
    $assistant_id = $chatbot_chatgpt_assistant_alias;

    // DIAG - Diagnostics - Ver 1.9.4
    // back_trace( 'NOTICE', '$assistant_id: ' . $assistant_id);
    // back_trace( 'NOTICE', '$chatbot_chatgpt_assistant_alias: ' . $chatbot_chatgpt_assistant_alias);
    if ( $assistant_id == 'original' ) {
        // No need to do anything
    }
    
    // Sanitize the 'audience' attribute to ensure it contains safe data
    $chatbot_chatgpt_audience_choice = array_key_exists('audience', $atts) ? sanitize_text_field($atts['audience']) : ''; // if not set, it will be set later

    // check for global audience setting
    $chatbot_chatgpt_audience_choice_global = esc_attr(get_option('chatbot_chatgpt_audience_choice', 'all'));
    if (empty($chatbot_chatgpt_audience_choice)) {
        $chatbot_chatgpt_audience_choice = $chatbot_chatgpt_audience_choice_global;
    }
    
    // Sanitize the 'prompt' attribute to ensure it contains safe data
    $chatbot_chatgpt_hot_bot_prompt = array_key_exists('prompt', $atts) ? sanitize_text_field($atts['prompt']) : '';
    if (!empty($chatbot_chatgpt_hot_bot_prompt)) {
        $chatbot_chatgpt_hot_bot_prompt = esc_attr($chatbot_chatgpt_hot_bot_prompt);
    }

    // Prompt passed as a parameter to the page - Ver 1.9.1
    if (isset($_GET['chatbot_prompt'])) {
        $chatbot_chatgpt_hot_bot_prompt = sanitize_text_field($_GET['chatbot_prompt']);
        // DIAG - Diagnostics - Ver 1.9.1
        // back_trace( 'NOTICE', 'chatbot_chatgpt_hot_bot_prompt: ' . $chatbot_chatgpt_hot_bot_prompt);
    }

    // Model not passed as parameter - Ver 1.9.4
    if (!isset($atts['model'])) {
        $model = esc_attr(get_option('chatbot_chatgpt_model_choice', 'gpt-3.5-turbo'));
        $script_data_array['model'] = $model;
        // DIAG - Diagnostics - Ver 1.9.4
        // back_trace('NOTICE', 'Model not passed as a parameter: ' . $model);
    } else {
        $model = sanitize_text_field($atts['model']);
        $script_data_array['model'] = $model;
        // DIAG - Diagnostics - Ver 1.9.4
        // back_trace('NOTICE', 'Model passed as a parameter: ' . $model);
    }

    // DIAG - Diagnostics - Ver 1.9.0
    // back_trace( 'NOTICE', 'chatbot_chatgpt_shortcode - at line 167 of the function');
    // back_trace( 'NOTICE', '$user_id: ' . $user_id);
    // back_trace( 'NOTICE', '$page_id: ' . $page_id);
    // back_trace( 'NOTICE', '$session_id: ' . $session_id);
    // back_trace( 'NOTICE', '$chatbot_chatgpt_display_style: ' . $chatbot_chatgpt_display_style);
    // back_trace( 'NOTICE', '$chatbot_chatgpt_assistant_alias: ' . $chatbot_chatgpt_assistant_alias);
    // back_trace( 'NOTICE', '$chatbot_chatgpt_audience_choice: ' . $chatbot_chatgpt_audience_choice);
    // back_trace( 'NOTICE', '$chatbot_chatgpt_hot_bot_prompt: ' . $chatbot_chatgpt_hot_bot_prompt);
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

    // DUPLICATE ADDED THIS HERE - VER 1.9.1
    $script_data_array = array(
        'user_id' => $user_id,
        'page_id' => $page_id,
        'session_id' => $session_id,
        'thread_id' => $thread_id,
        'assistant_id' => $assistant_id,
        '_additional_instructions' => $additional_instructions,
        'model' => $model
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
    
    $chatbot_chatgpt_allow_file_uploads = esc_attr(get_option('chatbot_chatgpt_allow_file_uploads', 'No'));

    // If assistant is set to 'original' then do not allow file uploads - Ver 1.7.9
    if ($chatbot_chatgpt_assistant_alias == 'original') {
        $chatbot_chatgpt_allow_file_uploads = 'No';
    }

    // Retrieve the custom buttons on/off setting - Ver 1.6.5
    // $chatbot_chatgpt_enable_custom_buttons = esc_attr(get_option('chatbot_chatgpt_enable_custom_buttons', 'Off'));

    // KFlow - Call kflow_prompt_and_response() - Ver 1.9.5
    if (function_exists('kflow_prompt_and_response') and !empty($atts['sequence'])) {

        // Get the sequence ID
        $sequence_id = array_key_exists('sequence', $atts) ? sanitize_text_field($atts['sequence']) : '';

        // Fetch the KFlow data
        $kflow_data = kflow_get_sequence_data($sequence_id);

        // Set up the sequence
        set_transient('kflow_sequence', $sequence_id);
        set_transient('kflow_step', 0);

        // FIXME - REMOVED 2024 04 01
        // FIXME - REPLACED BY TRANSIENTS - Ver 1.9.5
        // Setup the sequence
        // $script_data_array['sequence_id'] = $sequence_id;
        // $script_data_array['next_step'] = 1;
        // $script_data_array['total_steps'] = count($kflow_data['Steps']);

        // Set transients
        set_chatbot_chatgpt_transients('kflow_sequence', $sequence_id, null, null, $session_id);
        set_chatbot_chatgpt_transients('kflow_step', 0, null, null, $session_id);

        // Get the first prompt
        $kflow_prompt = $kflow_data['Prompts'][0];

        // DIAG - Diagnostics - Ver 1.9.5
        // back_trace( 'NOTICE', '$kflow_data: ' . print_r($kflow_data, true));
        // back_trace( 'NOTICE', '$script_data_array: ' . print_r($script_data_array, true));
        // back_trace( 'NOTICE', '$kflow_prompt: ' . $kflow_prompt);

        // FIXME - REMOVED 2024 04 01
        // Add +1 to the next step
        // $script_data_array['next_step'] = $script_data_array['next_step'] + 1;

        if ( $kflow_prompt != '' ) {

            // FIXME - REMOVED 2024 04 01
            // Set up the sequence
            // set_transient('kflow_sequence', $sequence_id);
            // set_transient('kflow_step', 0);

            // FIXME - REMOVED 2024 04 01
            // FIXME - REPLACED BY TRANSIENTS - Ver 1.9.5
            // Setup the sequence
            // $script_data_array['sequence_id'] = $sequence_id;
            // $script_data_array['next_step'] = 1;
            // $script_data_array['total_steps'] = count($kflow_data['Steps']);

            // Set transients
            set_chatbot_chatgpt_transients('kflow_sequence', $sequence_id, null, null, $session_id);
            set_chatbot_chatgpt_transients('kflow_step', -1, null, null, $session_id); // Start at -1 not 0

            // Get the first prompt
            $kflow_prompt = $kflow_data['Prompts'][0];

            // A prompt was returned
            // Pass to the Chatbot
            // To ask the visitor to complete the prompt
            $chatbot_chatgpt_hot_bot_prompt = $kflow_prompt;

            // Override the $model and set it to 'flow'
            $model = 'flow';

        } else {

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
        back_trace( 'WARNING', 'kflow modules not installed');

    }

    // Depending on the style, adjust the output - Ver 1.7.1
    if ($chatbot_chatgpt_display_style == 'embedded') {
        // Code for embed style ('embedded' is the alternative style)
        // Store the style and the assistant value - Ver 1.7.2
        set_chatbot_chatgpt_transients( 'display_style' , $chatbot_chatgpt_display_style, $user_id, $page_id, null, null );
        set_chatbot_chatgpt_transients( 'assistant_alias' , $chatbot_chatgpt_assistant_alias, $user_id, $page_id, null, null );
        set_chatbot_chatgpt_transients( 'model' , $model, $user_id, $page_id, null, null);

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
        <div id="chatbot-chatgpt-input" style="display: flex; justify-content: center; align-items: start; gap: 5px; width: 95%;">
            <div style="flex-grow: 1; max-width: 95%;">
                <label for="chatbot-chatgpt-message"></label>
                <?php
                    // Kick off Flow - Ver 1.9.5
                    if (!empty($sequence_id)){
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
                            }, 1000); // Delay of 1 second
                        });
                        </script>";
                    }
                    // Preload with a prompt if it is set - Ver 1.9.0
                    if (!empty($chatbot_chatgpt_hot_bot_prompt)) {
                        echo "<textarea id='chatbot-chatgpt-message' rows='3' placeholder='$chatbot_chatgpt_bot_prompt' style='width: 95%;'>$chatbot_chatgpt_hot_bot_prompt</textarea>";
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
                            }, 1000); // Delay of 1 second
                        });
                        </script>";
                    } else {
                        echo "<textarea id='chatbot-chatgpt-message' rows='3' placeholder='$chatbot_chatgpt_bot_prompt' style='width: 95%;'></textarea>";
                    }
                ?>
            </div>   
            <div id="chatbot-chatgpt-buttons-container" style="flex-grow: 0; display: flex; flex-direction: column; align-items: center; gap: 5px;">
                <button id="chatbot-chatgpt-submit">
                    <img src="<?php echo plugins_url('../assets/icons/send_FILL0_wght400_GRAD0_opsz24.png', __FILE__); ?>" alt="Send">
                </button>
                <?php if ($chatbot_chatgpt_allow_file_uploads == 'Yes'): ?>
                    <!-- <input type="file" id="chatbot-chatgpt-upload-file-input" style="display: none;" /> -->
                    <input type="file" id="chatbot-chatgpt-upload-file-input" name="file[]" style="display: none;" multiple="multiple" />
                    <button id="chatbot-chatgpt-upload-file">
                        <img src="<?php echo plugins_url('../assets/icons/attach_file_FILL0_wght400_GRAD0_opsz24.png', __FILE__); ?>" alt="Upload File">
                    </button>
                    <script type="text/javascript">
                        document.getElementById('chatbot-chatgpt-upload-file').addEventListener('click', function() {
                            document.getElementById('chatbot-chatgpt-upload-file-input').click();
                        });
                    </script>
                <?php endif; ?>
                <button id="chatbot-chatgpt-erase-btn">
                    <img src="<?php echo plugins_url('../assets/icons/delete_FILL0_wght400_GRAD0_opsz24.png', __FILE__); ?>" alt="Erase Conversation">
                </button>
            </div>
        </div>
        <button id="chatgpt-open-btn" style="display: none;">
        <!-- <i class="dashicons dashicons-format-chat"></i> -->
        <i class="chatbot-open-icon"></i>
        </button>
        <?php
        return ob_get_clean();
    } elseif ($chatbot_chatgpt_display_style == 'floating') {
        // Code for bot style ('floating' is the default style)
        // Store the style and the assistant value - Ver 1.7.2
        set_chatbot_chatgpt_transients( 'display_style' , $chatbot_chatgpt_display_style, $user_id, $page_id, null, null );
        set_chatbot_chatgpt_transients( 'assistant_alias' , $chatbot_chatgpt_assistant_alias, $user_id, $page_id, null, null );
        set_chatbot_chatgpt_transients( 'model' , $model, $user_id, $page_id, null, null);
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
                        <!-- <textarea id="chatbot-chatgpt-message" rows="3" placeholder="<?php echo esc_attr($chatbot_chatgpt_bot_prompt); ?>" style="width: 95%;"></textarea> -->
                        <?php
                            // Preload with a prompt if it is set - Ver 1.9.0
                            if (!empty($chatbot_chatgpt_hot_bot_prompt)) {
                                echo "<textarea id='chatbot-chatgpt-message' rows='3' placeholder='$chatbot_chatgpt_bot_prompt' style='width: 95%;'>$chatbot_chatgpt_hot_bot_prompt</textarea>";
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
                                    }, 1000); // Delay of 1 second
                                });
                                </script>";
                            } else {
                                echo "<textarea id='chatbot-chatgpt-message' rows='3' placeholder='$chatbot_chatgpt_bot_prompt' style='width: 95%;'></textarea>";
                            }
                        ?>
                    </div>
                    <div id="chatbot-chatgpt-buttons-container" style="flex-grow: 0; display: flex; flex-direction: column; align-items: center; gap: 5px;">
                        <button id="chatbot-chatgpt-submit">
                            <img src="<?php echo plugins_url('../assets/icons/send_FILL0_wght400_GRAD0_opsz24.png', __FILE__); ?>" alt="Send">
                        </button>
                        <?php if ($chatbot_chatgpt_allow_file_uploads == 'Yes'): ?>
                            <!-- <input type="file" id="chatbot-chatgpt-upload-file-input" style="display: none;" /> -->
                            <input type="file" id="chatbot-chatgpt-upload-file-input" name="file[]" style="display: none;" multiple="multiple" />
                            <button id="chatbot-chatgpt-upload-file">
                                <img src="<?php echo plugins_url('../assets/icons/attach_file_FILL0_wght400_GRAD0_opsz24.png', __FILE__); ?>" alt="Upload File">
                            </button>
                            <script type="text/javascript">
                                document.getElementById('chatbot-chatgpt-upload-file').addEventListener('click', function() {
                                    document.getElementById('chatbot-chatgpt-upload-file-input').click();
                                });
                            </script>
                        <?php endif; ?>
                        <button id="chatbot-chatgpt-erase-btn">
                            <img src="<?php echo plugins_url('../assets/icons/delete_FILL0_wght400_GRAD0_opsz24.png', __FILE__); ?>" alt="Erase Conversation">
                        </button>
                    </div>
                </div>
            <!-- Custom buttons - Ver 1.6.5 -->
            <?php
            $chatbot_chatgpt_enable_custom_buttons = esc_attr(get_option('chatbot_chatgpt_enable_custom_buttons', 'Off'));
            // DIAG - Diagnostics - Ver 1.6.5
            // back_trace( 'NOTICE', '$chatbot_chatgpt_enable_custom_buttons: ' . $chatbot_chatgpt_enable_custom_buttons);
            if ($chatbot_chatgpt_enable_custom_buttons == 'On') {
                ?>
                <div id="chatbot-chatgpt-custom-buttons" style="text-align: center;">
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
                    <a href="https://kognetiks.com/wordpress-plugins/kognetiks-chatbot/?utm_source=chatbot&utm_medium=website&utm_campaign=powered_by&utm_id=plugin" target="_blank" rel="noopener noreferrer" style="text-decoration:none; font-size: 10px;"><?php echo esc_html('Chatbot & Knowledge Navigator by Kognetiks'); ?></a>
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

    global $chatbot_chatgpt_display_style;
    global $chatbot_chatgpt_assistant_alias;

    // These were already here - Ver 1.9.3 - 2024 03 16
    global $chatbot_chatgpt_display_style;
    global $chatbot_chatgpt_assistant_alias;

    // Check if the variables are set and not empty
    $style = $chatbot_chatgpt_display_style ?? '';
    $assistant = $chatbot_chatgpt_assistant_alias ?? '';

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
    </script>
    <?php

}
// Hook this function into the 'wp_footer' action
add_action('wp_footer', 'chatbot_chatgpt_shortcode_enqueue_script');
