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

// Set up the Setting Pages - Ver 1.9.0
function chatbot_chatgpt_settings_page(): void {
    add_options_page('Chatbot Settings', 'Kognetiks Chatbot', 'manage_options', 'chatbot-chatgpt', 'chatbot_chatgpt_settings_page_html');
}
add_action('admin_menu', 'chatbot_chatgpt_settings_page');

// Settings page HTML - Ver 1.3.0
function chatbot_chatgpt_settings_page_html(): void {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Localize the settings - Added back in for Ver 1.8.5
    chatbot_chatgpt_localize();

    $active_tab = $_GET['tab'] ?? 'bot_settings';

    if (isset($_GET['settings-updated'])) {
        add_settings_error('chatbot_chatgpt_messages', 'chatbot_chatgpt_message', 'Settings Saved', 'updated');
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
    // back_trace( 'NOTICE', 'Appearance Restore Defaults: ' . $chatbot_chatgpt_appearance_reset);
    if ( $chatbot_chatgpt_appearance_reset == 'Yes' ) {
        chatbot_chatgpt_appearance_restore_default_settings();
    }

    ?>
    <div class="wrap">
        <h1><span class="dashicons dashicons-format-chat"></span> <?php echo esc_html(get_admin_page_title()); ?></h1>

       <script>
            jQuery(document).ready(function($) {
                let chatgptSettingsForm = document.getElementById('chatgpt-settings-form');

                if (chatgptSettingsForm) {

                    chatgptSettingsForm.addEventListener('submit', function() {

                        // Changed const to var - Ver 1.5.0
                        // Get the input elements by their ids
                        let chatgptNameInput = document.getElementById('chatbot_chatgpt_bot_name');
                        let chatgpt_chatbot_bot_promptInput = document.getElementById('chatbot_chatgpt_bot_prompt');
                        let chatgptInitialGreetingInput = document.getElementById('chatbot_chatgpt_initial_greeting');
                        let chatgptSubsequentGreetingInput = document.getElementById('chatbot_chatgpt_subsequent_greeting');
                        let chatgptStartStatusInput = document.getElementById('chatbot_chatgpt_start_status');
                        let chatbot_chatgpt_start_status_new_visitorInput = document.getElementById('chatbot_chatgpt_start_status_new_visitor');
                        let chatgptDisclaimerSettingInput = document.getElementById('chatbot_chatgpt_disclaimer_setting');
                        // New options for max tokens and width - Ver 1.4.2
                        let chatgptMaxTokensSettingInput = document.getElementById('chatbot_chatgpt_max_tokens_setting');
                        let chatgptWidthSettingInput = document.getElementById('chatbot_chatgpt_width_setting');
                        // New options for diagnostics on/off - Ver 1.5.0
                        let chatgptDiagnosticsSettingInput = document.getElementById('chatbot_chatgpt_diagnostics');
                        // Avatar Settings - Ver 1.4.3
                        let chatgptAvatarIconSettingInput = document.getElementById('chatbot_chatgpt_avatar_icon_setting');
                        let chatgptCustomAvatarIconSettingInput = document.getElementById('chatbot_chatgpt_custom_avatar_icon_setting');
                        let chatgptAvatarGreetingSettingInput = document.getElementById('chatbot_chatgpt_avatar_greeting_setting');
                        // Custom Buttons - Ver 1.6.5
                        let chatgptEnableCustomButtonsInput = document.getElementById('chatbot_chatgpt_enable_custom_buttons');
                        let chatgptCustomButtonName1Input = document.getElementById('chatbot_chatgpt_custom_button_name_1');
                        let chatgptCustomButtonURL1Input = document.getElementById('chatbot_chatgpt_custom_button_url_1');
                        let chatgptCustomButtonName2Input = document.getElementById('chatbot_chatgpt_custom_button_name_2');
                        let chatgptCustomButtonURL2Input = document.getElementById('chatbot_chatgpt_custom_button_url_2');
                        // Allow File Uploads - Ver 1.7.6
                        let chatgptAllowFileUploadsInput = document.getElementById('chatbot_chatgpt_allow_file_uploads');

                        // Update the local storage with the input values, if inputs exist
                        if(chatgptNameInput) localStorage.setItem('chatbot_chatgpt_bot_name', chatgptNameInput.value);
                        if(chatgpt_chatbot_bot_promptInput) localStorage.setItem('chatbot_chatgpt_bot_prompt', chatgpt_chatbot_bot_promptInput.value);
                        if(chatgptInitialGreetingInput) localStorage.setItem('chatbot_chatgpt_initial_greeting', chatgptInitialGreetingInput.value);
                        if(chatgptSubsequentGreetingInput) localStorage.setItem('chatbot_chatgpt_subsequent_greeting', chatgptSubsequentGreetingInput.value);
                        if(chatgptStartStatusInput) localStorage.setItem('chatbot_chatgpt_start_status', chatgptStartStatusInput.value);
                        if(chatbot_chatgpt_start_status_new_visitorInput) localStorage.setItem('chatbot_chatgpt_start_status_new_visitor', chatbot_chatgpt_start_status_new_visitorInput.value);
                        if(chatgptDisclaimerSettingInput) localStorage.setItem('chatbot_chatgpt_disclaimer_setting', chatgptDisclaimerSettingInput.value);
                        // New options for max tokens and width - Ver 1.4.2
                        if(chatgptMaxTokensSettingInput) localStorage.setItem('chatbot_chatgpt_max_tokens_setting', chatgptMaxTokensSettingInput.value);
                        if(chatgptWidthSettingInput) localStorage.setItem('chatbot_chatgpt_width_setting', chatgptWidthSettingInput.value);
                        // Avatar Settings - Ver 1.5.0
                        if(chatgptAvatarIconSettingInput) localStorage.setItem('chatbot_chatgpt_avatar_icon_setting', chatgptAvatarIconSettingInput.value);
                        if(chatgptCustomAvatarIconSettingInput) localStorage.setItem('chatbot_chatgpt_custom_avatar_icon_setting', chatgptCustomAvatarIconSettingInput.value);
                        if(chatgptAvatarGreetingSettingInput) localStorage.setItem('chatbot_chatgpt_avatar_greeting_setting', chatgptAvatarGreetingSettingInput.value);
                        // Custom Buttons - Ver 1.6.5
                        if(chatgptEnableCustomButtonsInput) localStorage.setItem('chatbot_chatgpt_enable_custom_buttons', chatgptEnableCustomButtonsInput.value);
                        if(chatgptCustomButtonName1Input) localStorage.setItem('chatbot_chatgpt_custom_button_name_1', chatgptCustomButtonName1Input.value);
                        if(chatgptCustomButtonURL1Input) localStorage.setItem('chatbot_chatgpt_custom_button_url_1', chatgptCustomButtonURL1Input.value);
                        if(chatgptCustomButtonName2Input) localStorage.setItem('chatbot_chatgpt_custom_button_name_2', chatgptCustomButtonName2Input.value);
                        if(chatgptCustomButtonURL2Input) localStorage.setItem('chatbot_chatgpt_custom_button_url_2', chatgptCustomButtonURL2Input.value);
                        // Allow File Uploads - Ver 1.7.6
                        if(chatgptAllowFileUploadsInput) localStorage.setItem('chatbot_chatgpt_allow_file_uploads', chatgptAllowFileUploadsInput.value);
                    });
                }
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
            <a href="?page=chatbot-chatgpt&tab=kn_analysis" class="nav-tab <?php echo $active_tab == 'kn_analysis' ? 'nav-tab-active' : ''; ?>">Analysis</a>
            <a href="?page=chatbot-chatgpt&tab=reporting" class="nav-tab <?php echo $active_tab == 'reporting' ? 'nav-tab-active' : ''; ?>">Reporting</a>
            <a href="?page=chatbot-chatgpt&tab=diagnostics" class="nav-tab <?php echo $active_tab == 'diagnostics' ? 'nav-tab-active' : ''; ?>">Messages</a>
            <a href="?page=chatbot-chatgpt&tab=support" class="nav-tab <?php echo $active_tab == 'support' ? 'nav-tab-active' : ''; ?>">Support</a>
            <!-- Coming Soon in Ver 2.0.0 -->
            <!-- <a href="?page=chatbot-chatgpt&tab=premium" class="nav-tab <?php echo $active_tab == 'premium' ? 'nav-tab-active' : ''; ?>">Premium</a> -->
       </h2>

       <!-- Updated id - Ver 1.4.1 -->
       <form id="chatgpt-settings-form" action="options.php" method="post">
            <?php
            if ($active_tab == 'bot_settings') {
                settings_fields('chatbot_chatgpt_settings');
                do_settings_sections('chatbot_chatgpt_settings');

            } elseif ($active_tab == 'api_model') {
                settings_fields('chatbot_chatgpt_api_model');
                do_settings_sections('chatbot_chatgpt_api_model');

            } elseif ($active_tab == 'gpt_assistants') {
                settings_fields('chatbot_chatgpt_custom_gpts');
                do_settings_sections('chatbot_chatgpt_custom_gpts');

            } elseif ($active_tab == 'avatar') {
                settings_fields('chatbot_chatgpt_avatar');
                do_settings_sections('chatbot_chatgpt_avatar');

            } elseif ($active_tab == 'custom_buttons') {
                settings_fields('chatbot_chatgpt_custom_buttons');
                do_settings_sections('chatbot_chatgpt_custom_buttons');

            } elseif ($active_tab == 'kn_acquire') {
                settings_fields('chatbot_chatgpt_knowledge_navigator');
                do_settings_sections('chatbot_chatgpt_knowledge_navigator');
                settings_fields('chatbot_chatgpt_kn_settings_section');
                do_settings_sections('chatbot_chatgpt_kn_settings_section');
            } elseif ($active_tab == 'kn_analysis') {
                settings_fields('chatbot_chatgpt_kn_analysis');
                do_settings_sections('chatbot_chatgpt_kn_analysis');

            } elseif ($active_tab == 'reporting') {
                settings_fields('chatbot_chatgpt_reporting');
                do_settings_sections('chatbot_chatgpt_reporting');

            } elseif ($active_tab == 'diagnostics') {
                settings_fields('chatbot_chatgpt_diagnostics');
                do_settings_sections('chatbot_chatgpt_diagnostics');

            } elseif ($active_tab == 'appearance') {
                settings_fields('chatbot_chatgpt_appearance');
                do_settings_sections('chatbot_chatgpt_appearance');

            // IDEA Coming Soon in Ver 2.0.0
            // } elseif ($active_tab == 'premium') {
            //     settings_fields('chatbot_chatgpt_premium');
            //     do_settings_sections('chatbot_chatgpt_premium');

            } elseif ($active_tab == 'support') {
                settings_fields('chatbot_chatgpt_support');
                do_settings_sections('chatbot_chatgpt_support');
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
