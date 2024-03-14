<?php
/**
 * Kognetiks Chatbot for WordPress - Shortcode Registration
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

// function chatbot_chatgpt_shortcode( $atts = [], $content = null, $tag = '' ) {
function chatbot_chatgpt_shortcode( $atts ) {

    // DIAG - Diagnostics - Ver 1.9.1
    // back_trace( 'NOTICE', 'chatbot_chatgpt_shortcode - at the beginning of the function');
    // back_trace( 'NOTICE', 'SHORTCODE ATTS: ' .  print_r($atts,true));

    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;
    global $chatbot_chatgpt_display_style;
    global $chatbot_chatgpt_assistant_alias;
    global $script_data_array;

    // KFlow - Ver 1.9.2
    global $kflow_data;


    // Script Attributes
    $script_data_array = array(
        'user_id' => $user_id,
        'page_id' => $page_id,
        'session_id' => $session_id,
        'thread_id' => $thread_id,
        'assistant_id' => $assistant_id
    );

    // Shortcode Attributes
    $chatbot_chatgpt_default_atts = array(
        'style' => 'floating', // Default value
        'assistant' => 'original', // Default value
        'audience' => '', // If not passed then default value
        'prompt' => '', // If not passed then default value
        'sequence' => '' // If not passed then default value
    );

    // DIAG - Diagnostics - Ver 1.8.6
    // back_trace( 'NOTICE', 'chatbot_chatgpt_shortcode - at line 55 of the function');
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

    // normalize attribute keys, lowercase
    $atts = array_change_key_case((array)$atts, CASE_LOWER);

    // Combine user attributes with default attributes
    $atts = shortcode_atts($chatbot_chatgpt_default_atts, $atts, 'chatbot_chatgpt');

    // Sanitize the 'style' attribute to ensure it contains safe data
    $chatbot_chatgpt_display_style = array_key_exists('style', $atts) ? sanitize_text_field($atts['style']) : 'floating';

    // Sanitize the 'assistant' attribute to ensure it contains safe data
    $chatbot_chatgpt_assistant_alias = array_key_exists('assistant', $atts) ? sanitize_text_field($atts['assistant']) : 'original';
    $assistant_id = $chatbot_chatgpt_assistant_alias;

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
        // back_trace( 'NOTICE', 'chatbot_chatgpt_hot_bot_prompt: ' . $chatbot_chatgpt_hot_bot_prompt);
    }

    // Check for KFlow parameters - Ver 1.9.2
    $kflow_sequence_id = array_key_exists('sequence', $atts) ? sanitize_text_field($atts['sequence']) : '';

    if (!empty($kflow_sequence_id)) {
    
        // DIAG - Diagnostics - Ver 1.9.2
        // back_trace( 'NOTICE', 'kflow_sequence_id: ' . $kflow_sequence_id);
    
        // Check to see if KFlow is enabled
        $kflow_enabled = esc_attr(get_option( 'kflow_flow_mode', false ));
    
        // DIAG - Diagnostics - Ver 1.9.2
        // back_trace( 'NOTICE', 'kflow_enabled: ' . $kflow_enabled);
    
        if ( $kflow_enabled == true ) {
            // If KFlow is enabled, then get the sequence ID and assemble the sequence, prompts, and template
            $kflow_data = fetchAndOrganizeData($kflow_sequence_id);
    
            if ( $kflow_data[$kflow_sequence_id]['SequenceStatus'] == 'active' ) {
                // If the sequence is active, then proceed to assemble the sequence, prompts, and template
                // Assemble the sequence, prompts, and template
                $kflow_sequence = $kflow_data[$kflow_sequence_id];
                $kflow_prompts = $kflow_data[$kflow_sequence_id]['Prompts'];
                $kflow_steps = $kflow_data[$kflow_sequence_id]['Steps'];
                $kflow_template = $kflow_data[$kflow_sequence_id]['Templates'];

                // Pass the values to the JavaScript
                wp_localize_script('chatbot-kflow-localize', 'kflow_data', array(
                    'kflow_enabled' => $kflow_enabled,
                    'kflow_sequence' => $kflow_sequence,
                    'kflow_prompts' => $kflow_prompts,
                    'kflow_steps' => $kflow_steps,
                    'kflow_template' => $kflow_template
                ));

            } else {
                // If the sequence is not active, then do not proceed
                // back_trace( 'NOTICE', 'The sequence is not active');
                $kflow_sequence = '';
                $kflow_prompts = '';
                $kflow_template = '';
            }
    
        } else {
    
            // If KFlow is not enabled, then do not proceed
            // back_trace( 'NOTICE', 'KFlow is not enabled');
            $kflow_sequence = '';
            $kflow_prompts = '';
            $kflow_template = '';
    
        }
    
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

    if ( $chatbot_chatgpt_assistant_alias == 'original' ) {
        $assistant_id = esc_attr(get_option('chatbot_chatgpt_assistant_id', ''));
    } elseif ( $chatbot_chatgpt_assistant_alias == 'alternate' ) {
        $assistant_id = esc_attr(get_option('chatbot_chatgpt_assistant_id_alternate', ''));
    } else {
        // Do nothing as either the assistant_id is set to the GPT Assistant ID or it is not set at all
    }

    set_chatbot_chatgpt_transients( 'display_style' , $chatbot_chatgpt_display_style, $user_id, $page_id, null, null );
    set_chatbot_chatgpt_transients( 'assistant_alias' , $chatbot_chatgpt_assistant_alias, $user_id, $page_id, null, null );

    // DUPLICATE ADDED THIS HERE - VER 1.9.1
    $script_data_array = array(
        'user_id' => $user_id,
        'page_id' => $page_id,
        'session_id' => $session_id,
        'thread_id' => $thread_id,
        'assistant_id' => $assistant_id
    );

    // DIAG - Diagnostics - Ver 1.8.6
    // back_trace( 'NOTICE', 'chatbot_chatgpt_shortcode - at line 234 of the function');
    // back_trace( 'NOTICE', '$user_id: ' . $user_id);
    // back_trace( 'NOTICE', '$page_id: ' . $page_id);
    // back_trace( 'NOTICE', '$session_id: ' . $session_id);
    // back_trace( 'NOTICE', '$thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', '$assistant_id: ' . $assistant_id);
    // back_trace( 'NOTICE', '$script_data_array: ' . print_r($script_data_array, true));

    // Retrieve the bot name - Ver 1.1.0
    // Add styling to the bot to ensure that it is not shown before it is needed Ver 1.2.0
    $bot_name = esc_attr(get_option('chatbot_chatgpt_bot_name', 'Kognetiks Chatbot'));

    $chatbot_chatgpt_bot_prompt = esc_attr(get_option('chatbot_chatgpt_bot_prompt', 'Enter your question ...'));

    // FIXME - NOT WORKING YET - Ver 1.9.0
    // if (empty($chatbot_chatgpt_hot_bot_prompt)) {
    //     // $chatbot_chatgpt_bot_prompt = esc_attr(get_option('chatbot_chatgpt_bot_prompt', 'Enter your question ...'));
    //     back_trace ( 'NOTICE', 'chatbot_chatgpt_bot_prompt: ' . $chatbot_chatgpt_bot_prompt);
    // } else {
    //     $chatbot_chatgpt_bot_prompt = $chatbot_chatgpt_hot_bot_prompt;
    //     back_trace ( 'NOTICE', 'chatbot_chatgpt_bot_prompt: ' . $chatbot_chatgpt_bot_prompt);
    // }

    // Localize the $chatbot_chatgpt_bot_prompt - Ver 1.9.0
    // Now push $chatbot_chatgpt_bot_prompt to the JavaScript
    // wp_localize_script('chatbot-chatgpt', 'chatbot_chatgpt_bot_prompt', array('chatbot_chatgpt_bot_prompt' => $chatbot_chatgpt_bot_prompt));

    // Maybe instead of localizing the data, I can append the the prompt to the css element (#chatbot-chatgpt-message)
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

    // Depending on the style, adjust the output - Ver 1.7.1
    if ($chatbot_chatgpt_display_style == 'embedded') {
        // Code for embed style ('embedded' is the alternative style)
        // Store the style and the assistant value - Ver 1.7.2
        set_chatbot_chatgpt_transients( 'display_style' , $chatbot_chatgpt_display_style, $user_id, $page_id, null, null );
        set_chatbot_chatgpt_transients( 'assistant_alias' , $chatbot_chatgpt_assistant_alias, $user_id, $page_id, null, null );   
        ob_start();
        ?>
        <div id="chatbot-chatgpt"  style="display: flex;" class="embedded-style chatbot-full">
        <!-- <script>
            $(document).ready(function() {
                $('#chatbot-chatgpt').removeClass('floating-style').addClass('embedded-style');
            });
        </script> -->
        <!-- REMOVED FOR EMBEDDED -->
        <!-- <div id="chatbot-chatgpt-header">
            <div id="chatgptTitle" class="title"><?php echo $bot_name; ?></div>
        </div> -->
        <div id="chatbot-chatgpt-conversation"></div>
        <div id="chatbot-chatgpt-input" style="display: flex; justify-content: center; align-items: start; gap: 5px; width: 95%;">
            <div style="flex-grow: 1; max-width: 95%;">
                <label for="chatbot-chatgpt-message"></label>
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
        <button id="chatgpt-open-btn" style="display: none;">
        <!-- <i class="dashicons dashicons-format-chat"></i> -->
        <i class="chatbot-open-icon"></i>
        </button>
        <?php
        return ob_get_clean();
    } else {
        // Code for bot style ('floating' is the default style)
        // Store the style and the assistant value - Ver 1.7.2
        set_chatbot_chatgpt_transients( 'display_style' , $chatbot_chatgpt_display_style, $user_id, $page_id, null, null );
        set_chatbot_chatgpt_transients( 'assistant_alias' , $chatbot_chatgpt_assistant_alias, $user_id, $page_id, null, null );   
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

    global $chatbot_chatgpt_display_style;
    global $chatbot_chatgpt_assistant_alias;

    // Check if the variables are set and not empty
    $style = $chatbot_chatgpt_display_style ?? '';
    $assistant = $chatbot_chatgpt_assistant_alias ?? '';

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
