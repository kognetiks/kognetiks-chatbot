<?php
/**
 * Chatbot ChatGPT for WordPress - Settings Page
 *
 * This file contains the code for the Chatbot ChatGPT settings page.
 * It allows users to configure the API key and other parameters
 * required to access the ChatGPT API from their own account.
 *
 * @package chatbot-chatgpt
 */

function chatbot_chatgpt_settings_page() {
    add_options_page('Chatbot ChatGPT Settings', 'Chatbot ChatGPT', 'manage_options', 'chatbot-chatgpt', 'chatbot_chatgpt_settings_page_html');
}
add_action('admin_menu', 'chatbot_chatgpt_settings_page');

// Settings page HTML - Ver 1.3.0
function chatbot_chatgpt_settings_page_html() {
    if (!current_user_can('manage_options')) {
        return;
    }

    chatbot_chatgpt_localize();

    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'api_model';

    if (isset($_GET['settings-updated'])) {
        add_settings_error('chatbot_chatgpt_messages', 'chatbot_chatgpt_message', 'Settings Saved', 'updated');
    }

    // REMOVED Ver 1.3.0
    // settings_errors('chatbot_chatgpt_messages');
    
    ?>
    <div class="wrap">
        <h1><span class="dashicons dashicons-format-chat"></span> <?php echo esc_html(get_admin_page_title()); ?></h1>

        <!-- Message Box - Ver 1.3.0 -->
        <div id="message-box-container"></div>

        <!-- Message Box - Ver 1.3.0 -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const chatgptSettingsForm = document.getElementById('chatgpt-settings-form');
                // Read the start status - Ver 1.4.1
                const chatgptStartStatusInput = document.getElementById('chatgptStartStatus');
                const chatgptStartStatusNewVisitorInput = document.getElementById('chatgptStartStatusNewVisitor');
                const reminderCount = localStorage.getItem('reminderCount') || 0;

                if (reminderCount < 5) {
                    const messageBox = document.createElement('div');
                    messageBox.id = 'rateReviewMessageBox';
                    messageBox.innerHTML = `
                    <div id="rateReviewMessageBox" style="background-color: white; border: 1px solid black; padding: 10px; position: relative;">
                        <div class="message-content" style="display: flex; justify-content: space-between; align-items: center;">
                            <span>If you and your visitors are enjoying having this chatbot on your site, please take a moment to <a href="https://wordpress.org/support/plugin/chatbot-chatgpt/reviews/" target="_blank">rate and review this plugin</a>. Thank you!</span>
                            <button id="closeMessageBox" class="dashicons dashicons-dismiss" style="background: none; border: none; cursor: pointer; outline: none; padding: 0; margin-left: 10px;"></button>
                            
                        </div>
                    </div>
                    `;

                    document.querySelector('#message-box-container').insertAdjacentElement('beforeend', messageBox);

                    document.getElementById('closeMessageBox').addEventListener('click', function() {
                        messageBox.style.display = 'none';
                        localStorage.setItem('reminderCount', parseInt(reminderCount, 10) + 1);
                    });
                }
            });
        </script>
    
        <script>
            jQuery(document).ready(function($) {
                var chatgptSettingsForm = document.getElementById('chatgpt-settings-form');

                if (chatgptSettingsForm) {

                    chatgptSettingsForm.addEventListener('submit', function() {

                        // Changed const to var - Ver 1.5.0
                        // Get the input elements by their ids
                        var chatgptNameInput = document.getElementById('chatgpt_bot_name');
                        var chatgptInitialGreetingInput = document.getElementById('chatgpt_initial_greeting');
                        var chatgptSubsequentGreetingInput = document.getElementById('chatgpt_subsequent_greeting');
                        var chatgptStartStatusInput = document.getElementById('chatgptStartStatus');
                        var chatgptStartStatusNewVisitorInput = document.getElementById('chatgptStartStatusNewVisitor');
                        var chatgptDisclaimerSettingInput = document.getElementById('chatgpt_disclaimer_setting');
                        // New options for max tokens and width - Ver 1.4.2
                        var chatgptMaxTokensSettingInput = document.getElementById('chatgpt_max_tokens_setting');
                        var chatgptWidthSettingInput = document.getElementById('chatgpt_width_setting');
                        // New options for diagnostics on/off - Ver 1.5.0
                        var chatgptDiagnosticsSettingInput = document.getElementById('chatgpt_diagnostics_setting');
                        // Avatar Settings - Ver 1.4.3
                        let chatgptAvatarIconSettingInput = document.getElementById('chatgpt_avatar_icon_setting');
                        let chatgptCustomAvatarIconSettingInput = document.getElementById('chatgpt_custom_avatar_icon_setting');
                        let chatgptAvatarGreetingSettingInput = document.getElementById('chatgpt_avatar_greeting_setting');

                        // Update the local storage with the input values, if inputs exist
                        if(chatgptNameInput) localStorage.setItem('chatgpt_bot_name', chatgptNameInput.value);
                        if(chatgptInitialGreetingInput) localStorage.setItem('chatgpt_initial_greeting', chatgptInitialGreetingInput.value);
                        if(chatgptSubsequentGreetingInput) localStorage.setItem('chatgpt_subsequent_greeting', chatgptSubsequentGreetingInput.value);
                        if(chatgptStartStatusInput) localStorage.setItem('chatgptStartStatus', chatgptStartStatusInput.value);
                        if(chatgptStartStatusNewVisitorInput) localStorage.setItem('chatgptStartStatusNewVisitor', chatgptStartStatusNewVisitorInput.value);
                        if(chatgptDisclaimerSettingInput) localStorage.setItem('chatgpt_disclaimer_setting', chatgptDisclaimerSettingInput.value);
                        // New options for max tokens and width - Ver 1.4.2
                        if(chatgptMaxTokensSettingInput) localStorage.setItem('chatgpt_max_tokens_setting', chatgptMaxTokensSettingInput.value);
                        if(chatgptWidthSettingInput) localStorage.setItem('chatgpt_width_setting', chatgptWidthSettingInput.value);
                        // New options for diagnostics on/off - Ver 1.5.0
                        if(chatgptDiagnosticsSettingInput) localStorage.setItem('chatgpt_diagnostics', chatgptDiagnosticsSettingInput.value);
                        // Avatar Settings - Ver 1.5.0
                        if(chatgptAvatarIconSettingInput) localStorage.setItem('chatgpt_avatar_icon_setting', chatgptAvatarIconSettingInput.value);
                        if(chatgptCustomAvatarIconSettingInput) localStorage.setItem('chatgpt_custom_avatar_icon_setting', chatgptCustomAvatarIconSettingInput.value);
                        if(chatgptAvatarGreetingSettingInput) localStorage.setItem('chatgpt_avatar_greeting_setting', chatgptAvatarGreetingSettingInput.value);
                    });
                }
            });
        </script>

        <script>
            window.onload = function() {
                // Assign the function to the window object to make it globally accessible
                window.selectIcon = function(id) {
                    var chatgptElement = document.getElementById('chatgpt_avatar_icon_setting');
                    if(chatgptElement) {
                        // Clear border from previously selected icon
                        var previousIconId = chatgptElement.value;
                        var previousIcon = document.getElementById(previousIconId);
                        if(previousIcon) previousIcon.style.border = "none";  // Change "" to "none"

                        // Set border for new selected icon
                        var selectedIcon = document.getElementById(id);
                        if(selectedIcon) selectedIcon.style.border = "2px solid red";

                        // Set selected icon value in hidden input
                        chatgptElement.value = id;

                        // Save selected icon in local storage
                        localStorage.setItem('chatgpt_avatar_icon_setting', id);
                    }
                }

                // If no icon has been selected, select the first one by default
                var iconFromStorage = localStorage.getItem('chatgpt_avatar_icon_setting');
                var chatgptElement = document.getElementById('chatgpt_avatar_icon_setting');
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
            <a href="?page=chatbot-chatgpt&tab=api_model" class="nav-tab <?php echo $active_tab == 'api_model' ? 'nav-tab-active' : ''; ?>">API/Model</a>
            <a href="?page=chatbot-chatgpt&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
            <!-- Avatar Settings - Ver 1.5.0 -->
            <a href="?page=chatbot-chatgpt&tab=avatar" class="nav-tab <?php echo $active_tab == 'avatar' ? 'nav-tab-active' : ''; ?>">Avatar</a>
            <!-- Coming Soon in Ver 2.0.0 -->
            <!-- <a href="?page=chatbot-chatgpt&tab=premium" class="nav-tab <?php echo $active_tab == 'premium' ? 'nav-tab-active' : ''; ?>">Premium</a> -->
            <!-- Knowledge Navigator - Ver 1.6.1 -->
            <a href="?page=chatbot-chatgpt&tab=crawler" class="nav-tab <?php echo $active_tab == 'crawler' ? 'nav-tab-active' : ''; ?>">Knowledge Navigator</a>
            <a href="?page=chatbot-chatgpt&tab=support" class="nav-tab <?php echo $active_tab == 'support' ? 'nav-tab-active' : ''; ?>">Support</a>
        </h2>

        <!-- Updated id - Ver 1.4.1 -->
        <form id="chatgpt-settings-form" action="options.php" method="post">
            <?php
            if ($active_tab == 'settings') {
                settings_fields('chatbot_chatgpt_settings');
                do_settings_sections('chatbot_chatgpt_settings');
            } elseif ($active_tab == 'api_model') {
                settings_fields('chatbot_chatgpt_api_model');
                do_settings_sections('chatbot_chatgpt_api_model');
            } elseif ($active_tab == 'avatar') {
                settings_fields('chatbot_chatgpt_avatar');
                do_settings_sections('chatbot_chatgpt_avatar');
            // Coming Soon in Ver 2.0.0
            // } elseif ($active_tab == 'premium') {
            //     settings_fields('chatbot_chatgpt_premium');
            //     do_settings_sections('chatbot_chatgpt_premium');
            } elseif ($active_tab == 'support') {
                settings_fields('chatbot_chatgpt_support');
                do_settings_sections('chatbot_chatgpt_support');
            } elseif ($active_tab == 'crawler') {
                settings_fields('chatbot_chatgpt_knowledge_navigator');
                do_settings_sections('chatbot_chatgpt_knowledge_navigator');
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

