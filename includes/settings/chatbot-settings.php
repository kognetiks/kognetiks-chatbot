<?php
/**
 * Kognetiks Chatbot for WordPress - Settings Page
 *
 * This file contains the code for the Chatbot settings page.
 * It allows users to configure the bot name, start status, and greetings.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Set up the Chatbot Main Menu Page - Ver 1.9.0
function chatbot_chatgpt_menu_page() {

    add_menu_page(
        'Chatbot Settings',                     // Page title
        'Kognetiks Chatbot',                    // Menu title
        'manage_options',                       // Capability
        'chatbot-chatgpt',                      // Menu slug
        'chatbot_chatgpt_settings_page_html',   // Callback function
        'dashicons-format-chat'                 // Icon URL (optional)
    );

}
add_action('admin_menu', 'chatbot_chatgpt_menu_page');

// Settings page HTML - Ver 1.3.0
function chatbot_chatgpt_settings_page_html() {
    
    if (!current_user_can('manage_options')) {
        return;
    }

    global $kchat_settings;

    $kchat_settings['chatbot-chatgpt-version'] = CHATBOT_CHATGPT_VERSION;
    $kchat_settings_json = wp_json_encode($kchat_settings);
    $escaped_kchat_settings_json = esc_js($kchat_settings_json);   
    wp_add_inline_script('chatbot-chatgpt-local', 'if (typeof kchat_settings === "undefined") { var kchat_settings = ' . $escaped_kchat_settings_json . '; } else { kchat_settings = ' . $escaped_kchat_settings_json . '; }', 'before');
    
    // Localize the settings - Added back in for Ver 1.8.5
    chatbot_chatgpt_localize();

    $active_tab = $_GET['tab'] ?? 'bot_settings';
   
    if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
        add_settings_error('chatbot_chatgpt_messages', 'chatbot_chatgpt_message', 'Settings Saved', 'updated');
        settings_errors('chatbot_chatgpt_messages');
    }

    // Check reminderCount in local storage - Ver 1.8.1
    $reminderCount = intval(esc_attr(get_option('chatbot_chatgpt_reminder_count', 0)));
    if ($reminderCount % 100 === 0 && $reminderCount <= 500) {
        $message = 'If you and your visitors are enjoying having this chatbot on your site, please take a moment to <a href="https://wordpress.org/support/plugin/chatbot-chatgpt/reviews/" target="_blank">rate and review this plugin</a>. Thank you!';
        chatbot_chatgpt_general_admin_notice($message);
    }
    // Add 1 to reminderCount and update localStorage
    if ($reminderCount < 501) {
        $reminderCount++;
        update_option('chatbot_chatgpt_reminder_count', $reminderCount);
    }

    // Check if the user wants to reset the appearance settings to default - Ver 1.8.1
    $chatbot_chatgpt_appearance_reset = esc_attr(get_option('chatbot_chatgpt_appearance_reset', 'No'));
    // DIAG - Diagnostics
    // back_trace( 'NOTICE', '$chatbot_chatgpt_appearance_reset: ' . $chatbot_chatgpt_appearance_reset);
    if ( $chatbot_chatgpt_appearance_reset == 'Yes' ) {
        chatbot_chatgpt_appearance_restore_default_settings();
    }

    ?>
    <div class="wrap">
        <h1><span class="dashicons dashicons-format-chat"></span> <?php echo esc_html(get_admin_page_title()); ?></h1>

       <script>
            jQuery(document).ready(function($) {
                let chatgptSettingsForm = document.getElementById('chatgpt-settings-form');

                // REMOVED IN VER 2.1.1.1.1 - 2024-08-26
                // if (chatgptSettingsForm) {
                //     chatgptSettingsForm.addEventListener('submit', function() {
                //         let chatgptNameInput = document.getElementById('chatbot_chatgpt_bot_name');
                //         let chatgpt_chatbot_bot_promptInput = document.getElementById('chatbot_chatgpt_bot_prompt');
                //         let chatgptInitialGreetingInput = document.getElementById('chatbot_chatgpt_initial_greeting');
                //         let chatgptSubsequentGreetingInput = document.getElementById('chatbot_chatgpt_subsequent_greeting');
                //         let chatgptStartStatusInput = document.getElementById('chatbot_chatgpt_start_status');
                //         let chatbot_chatgpt_start_status_new_visitorInput = document.getElementById('chatbot_chatgpt_start_status_new_visitor');
                //         let chatgptDisclaimerSettingInput = document.getElementById('chatbot_chatgpt_disclaimer_setting');
                //         let chatgptMaxTokensSettingInput = document.getElementById('chatbot_chatgpt_max_tokens_setting');
                //         let chatgptMessageLimitSettingInput = document.getElementById('chatbot_chatgpt_message_limit_setting');
                //         let chatgptVisitorMessageLimitSettingInput = document.getElementById('chatbot_chatgpt_visitor_message_limit_setting');
                //         let chatgptWidthSettingInput = document.getElementById('chatbot_chatgpt_width_setting');
                //         let chatgptDiagnosticsSettingInput = document.getElementById('chatbot_chatgpt_diagnostics');
                //         let chatgptAvatarIconSettingInput = document.getElementById('chatbot_chatgpt_avatar_icon_setting');
                //         let chatgptCustomAvatarIconSettingInput = document.getElementById('chatbot_chatgpt_custom_avatar_icon_setting');
                //         let chatgptAvatarGreetingSettingInput = document.getElementById('chatbot_chatgpt_avatar_greeting_setting');
                //         let chatgptEnableCustomButtonsInput = document.getElementById('chatbot_chatgpt_enable_custom_buttons');
                //         let chatgptCustomButtonName1Input = document.getElementById('chatbot_chatgpt_custom_button_name_1');
                //         let chatgptCustomButtonURL1Input = document.getElementById('chatbot_chatgpt_custom_button_url_1');
                //         let chatgptCustomButtonName2Input = document.getElementById('chatbot_chatgpt_custom_button_name_2');
                //         let chatgptCustomButtonURL2Input = document.getElementById('chatbot_chatgpt_custom_button_url_2');
                //         let chatgptAllowFileUploadsInput = document.getElementById('chatbot_chatgpt_allow_file_uploads');

                //         // REMOVED IN 2.1.1.1 - 2024-08-26
                //         // Update the local storage with the input values, if inputs exist
                //         // if(chatgptNameInput) localStorage.setItem('chatbot_chatgpt_bot_name', chatgptNameInput.value);
                //         // if(chatgpt_chatbot_bot_promptInput) localStorage.setItem('chatbot_chatgpt_bot_prompt', chatgpt_chatbot_bot_promptInput.value);
                //         // if(chatgptInitialGreetingInput) localStorage.setItem('chatbot_chatgpt_initial_greeting', chatgptInitialGreetingInput.value);
                //         // if(chatgptSubsequentGreetingInput) localStorage.setItem('chatbot_chatgpt_subsequent_greeting', chatgptSubsequentGreetingInput.value);
                //         // if(chatgptStartStatusInput) localStorage.setItem('chatbot_chatgpt_start_status', chatgptStartStatusInput.value);
                //         // if(chatbot_chatgpt_start_status_new_visitorInput) localStorage.setItem('chatbot_chatgpt_start_status_new_visitor', chatbot_chatgpt_start_status_new_visitorInput.value);
                //         // if(chatgptDisclaimerSettingInput) localStorage.setItem('chatbot_chatgpt_disclaimer_setting', chatgptDisclaimerSettingInput.value);
                //         // if(chatgptMaxTokensSettingInput) localStorage.setItem('chatbot_chatgpt_max_tokens_setting', chatgptMaxTokensSettingInput.value);
                //         // if(chatgptMessageLimitSettingInput) localStorage.setItem('chatbot_chatgpt_message_limit_setting', chatgptMessageLimitSettingInput.value);
                //         // if(chatgptVisitorMessageLimitSettingInput) localStorage.setItem('chatbot_chatgpt_visitor_message_limit_setting', chatgptVisitorMessageLimitSettingInput.value);
                //         // if(chatgptWidthSettingInput) localStorage.setItem('chatbot_chatgpt_width_setting', chatgptWidthSettingInput.value);
                //         // if(chatgptAvatarIconSettingInput) localStorage.setItem('chatbot_chatgpt_avatar_icon_setting', chatgptAvatarIconSettingInput.value);
                //         // if(chatgptCustomAvatarIconSettingInput) localStorage.setItem('chatbot_chatgpt_custom_avatar_icon_setting', chatgptCustomAvatarIconSettingInput.value);
                //         // if(chatgptAvatarGreetingSettingInput) localStorage.setItem('chatbot_chatgpt_avatar_greeting_setting', chatgptAvatarGreetingSettingInput.value);
                //         // if(chatgptEnableCustomButtonsInput) localStorage.setItem('chatbot_chatgpt_enable_custom_buttons', chatgptEnableCustomButtonsInput.value);
                //         // if(chatgptCustomButtonName1Input) localStorage.setItem('chatbot_chatgpt_custom_button_name_1', chatgptCustomButtonName1Input.value);
                //         // if(chatgptCustomButtonURL1Input) localStorage.setItem('chatbot_chatgpt_custom_button_url_1', chatgptCustomButtonURL1Input.value);
                //         // if(chatgptCustomButtonName2Input) localStorage.setItem('chatbot_chatgpt_custom_button_name_2', chatgptCustomButtonName2Input.value);
                //         // if(chatgptCustomButtonURL2Input) localStorage.setItem('chatbot_chatgpt_custom_button_url_2', chatgptCustomButtonURL2Input.value);
                //         // if(chatgptAllowFileUploadsInput) localStorage.setItem('chatbot_chatgpt_allow_file_uploads', chatgptAllowFileUploadsInput.value);
                //     });
                // }

            });
       </script>

       <script>
            window.onload = function() {
                // Assign the function to the window object to make it globally accessible
                window.selectIcon = function(id) {
                    let chatgptElement = document.getElementById('chatbot_chatgpt_avatar_icon_setting');
                    if(chatgptElement) {
                        // Clear border from previously selected icon
                        let previousIconId = chatgptElement.value;
                        let previousIcon = document.getElementById(previousIconId);
                        if(previousIcon) previousIcon.style.border = "none";  // Change "" to "none"

                        // Set border for new selected icon
                        let selectedIcon = document.getElementById(id);
                        if(selectedIcon) selectedIcon.style.border = "2px solid red";

                        // Set selected icon value in hidden input
                        chatgptElement.value = id;

                        // Save selected icon in local storage
                        localStorage.setItem('chatbot_chatgpt_avatar_icon_setting', id);
                    }
                }

                // If no icon has been selected, select the first one by default
                let iconFromStorage = localStorage.getItem('chatbot_chatgpt_avatar_icon_setting');
                let chatgptElement = document.getElementById('chatbot_chatgpt_avatar_icon_setting');
                if(chatgptElement) {
                    if (iconFromStorage) {
                        window.selectIcon(iconFromStorage);
                    } else if (chatgptElement.value === '') {
                        window.selectIcon('icon-001.png');
                    }
                }
            }
       </script>

       <h2 class="nav-tab-wrapper">
            <a href="?page=chatbot-chatgpt&tab=bot_settings" class="nav-tab <?php echo $active_tab == 'bot_settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
            <a href="?page=chatbot-chatgpt&tab=api_model" class="nav-tab <?php echo $active_tab == 'api_model' ? 'nav-tab-active' : ''; ?>">API/Model</a>
            <a href="?page=chatbot-chatgpt&tab=gpt_assistants" class="nav-tab <?php echo $active_tab == 'gpt_assistants' ? 'nav-tab-active' : ''; ?>">GPT Assistants</a>
            <a href="?page=chatbot-chatgpt&tab=avatar" class="nav-tab <?php echo $active_tab == 'avatar' ? 'nav-tab-active' : ''; ?>">Avatars</a>
            <a href="?page=chatbot-chatgpt&tab=appearance" class="nav-tab <?php echo $active_tab == 'appearance' ? 'nav-tab-active' : ''; ?>">Appearance</a>
            <a href="?page=chatbot-chatgpt&tab=custom_buttons" class="nav-tab <?php echo $active_tab == 'custom_buttons' ? 'nav-tab-active' : ''; ?>">Buttons</a>
            <a href="?page=chatbot-chatgpt&tab=kn_acquire" class="nav-tab <?php echo $active_tab == 'kn_acquire' ? 'nav-tab-active' : ''; ?>">Knowledge Navigator</a>
            <a href="?page=chatbot-chatgpt&tab=reporting" class="nav-tab <?php echo $active_tab == 'reporting' ? 'nav-tab-active' : ''; ?>">Reporting</a>
            <a href="?page=chatbot-chatgpt&tab=diagnostics" class="nav-tab <?php echo $active_tab == 'diagnostics' ? 'nav-tab-active' : ''; ?>">Messages</a>
            <a href="?page=chatbot-chatgpt&tab=support" class="nav-tab <?php echo $active_tab == 'support' ? 'nav-tab-active' : ''; ?>">Support</a>
            <!-- Coming Soon in Ver 2.0.0 -->
            <!-- <a href="?page=chatbot-chatgpt&tab=premium" class="nav-tab <?php echo $active_tab == 'premium' ? 'nav-tab-active' : ''; ?>">Premium</a> -->
            <!-- Tools - Ver 2.0.6 -->
            <?php 
            // if diagnostics is enabled, then show the tools tab
            if (get_option('chatbot_chatgpt_diagnostics', 'Off') != 'Off') {
                echo '<a href="?page=chatbot-chatgpt&tab=tools" class="nav-tab ' . ($active_tab == 'tools' ? 'nav-tab-active' : '') . '">Tools</a>';
            }
            ?>
       </h2>

       <form id="chatgpt-settings-form" action="options.php" method="post">
            <?php
            if ($active_tab == 'bot_settings') {

                settings_fields('chatbot_chatgpt_settings');

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_bot_settings_general');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_name_settings');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                // settings_fields('chatbot_chatgpt_settings');
                do_settings_sections('chatbot_chatgpt_greetings_settings');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                // settings_fields('chatbot_chatgpt_settings');
                do_settings_sections('chatbot_chatgpt_additional_setup_settings');
                echo '</div>';

            } elseif ($active_tab == 'api_model') {

                settings_fields('chatbot_chatgpt_api_model');

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_model_settings_general');
                echo '</div>';

                // API Settings - Ver 1.9.5
                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_api_model_general');
                echo '</div>';

                // ChatGPT API Settings - Ver 1.9.5
                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_api_model_chat');
                echo '</div>';

                // Voice Settings - Ver 1.9.5
                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_api_model_voice');
                echo '</div>';

                // Whisper Settings - Ver 2.0.1
                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_api_model_whisper');
                echo '</div>';

                // Image Settings - Ver 1.9.5
                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_api_model_image');
                echo '</div>';

                // Advanced Settings - Ver 1.9.5
                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_api_model_advanced');
                echo '</div>';

            } elseif ($active_tab == 'gpt_assistants') {

                settings_fields('chatbot_chatgpt_custom_gpts');

                // Manage Assistants - Ver 2.0.4
                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_assistant_settings');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                // do_settings_sections('chatbot_chatgpt_assistants_management');
                display_chatbot_chatgpt_assistants_table();
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_gpt_assistants_settings');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_additional_assistant_settings');
                echo '</div>';

            } elseif ($active_tab == 'avatar') {

                settings_fields('chatbot_chatgpt_avatar');

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_avatar_overview');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_avatar');
                echo '</div>';

            } elseif ($active_tab == 'custom_buttons') {

                settings_fields('chatbot_chatgpt_custom_buttons');

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_custom_buttons_overview');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_custom_buttons');
                echo '</div>';

            } elseif ($active_tab == 'kn_acquire') {

                settings_fields('chatbot_chatgpt_knowledge_navigator');

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_knowledge_navigator');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_kn_status');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_kn_scheduling');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_kn_include_exclude');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_kn_enhanced_response');
                echo '</div>';
              
                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_kn_analysis');
                echo '</div>';

            } elseif ($active_tab == 'reporting') {

                settings_fields('chatbot_chatgpt_reporting');

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_reporting_overview');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_conversation_reporting');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_interaction_reporting');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_token_reporting');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_reporting');
                echo '</div>';

            } elseif ($active_tab == 'diagnostics') {

                settings_fields('chatbot_chatgpt_diagnostics');

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_diagnostics_overview');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_diagnostics_system_settings');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_diagnostics_api_status');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_diagnostics');
                echo '</div>';

            } elseif ($active_tab == 'appearance') {

                settings_fields('chatbot_chatgpt_appearance');

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_appearance_overview');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_appearance');
                echo '</div>';

            // IDEA Coming Soon in Ver 3.0.0
            // } elseif ($active_tab == 'premium') {
            //     settings_fields('chatbot_chatgpt_premium');
            //     do_settings_sections('chatbot_chatgpt_premium');

            } elseif ($active_tab == 'tools') {

                settings_fields('chatbot_chatgpt_tools');

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_tools_overview');
                echo '</div>';

                // echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                // do_settings_sections('chatbot_chatgpt_tools');
                // echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_tools_exporter_button');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_manage_error_logs');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_shortcode_tools');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_capability_tools');
                echo '</div>';

            } elseif ($active_tab == 'support') {

                settings_fields('chatbot_chatgpt_support');

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_support');
                echo '</div>';

            } elseif ($active_tab == 'support') {

                settings_fields('chatbot_chatgpt_support');

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_support');
                echo '</div>';

            }

            submit_button('Save Settings');
            ?>
       </form>
    </div>
    <!-- Added closing tags for body and html - Ver 1.4.1 -->
    </body>
    </html>
    <?php
}
