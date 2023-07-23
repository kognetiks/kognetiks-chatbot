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


// Register settings
function chatbot_chatgpt_settings_init() {

    // API/Model settings tab - Ver 1.3.0
    register_setting('chatbot_chatgpt_api_model', 'chatgpt_api_key');
    register_setting('chatbot_chatgpt_api_model', 'chatgpt_model_choice');
    // Max Tokens setting options - Ver 1.4.2
    register_setting('chatbot_chatgpt_api_model', 'chatgpt_max_tokens_setting');

    add_settings_section(
        'chatbot_chatgpt_api_model_section',
        'API/Model Settings',
        'chatbot_chatgpt_api_model_section_callback',
        'chatbot_chatgpt_api_model'
    );

    add_settings_field(
        'chatgpt_api_key',
        'ChatGPT API Key',
        'chatbot_chatgpt_api_key_callback',
        'chatbot_chatgpt_api_model',
        'chatbot_chatgpt_api_model_section'
    );

    add_settings_field(
        'chatgpt_model_choice',
        'ChatGPT Model Choice',
        'chatbot_chatgpt_model_choice_callback',
        'chatbot_chatgpt_api_model',
        'chatbot_chatgpt_api_model_section'
    );
    
    // Setting to adjust in small increments the number of Max Tokens - Ver 1.4.2
    add_settings_field(
        'chatgpt_max_tokens_setting',
        'Maximum Tokens Setting',
        'chatgpt_max_tokens_setting_callback',
        'chatbot_chatgpt_api_model',
        'chatbot_chatgpt_api_model_section'
    );


    // Settings settings tab - Ver 1.3.0
    register_setting('chatbot_chatgpt_settings', 'chatgpt_bot_name');
    register_setting('chatbot_chatgpt_settings', 'chatgptStartStatus');
    register_setting('chatbot_chatgpt_settings', 'chatgptStartStatusNewVisitor');
    register_setting('chatbot_chatgpt_settings', 'chatgpt_initial_greeting');
    register_setting('chatbot_chatgpt_settings', 'chatgpt_subsequent_greeting');
    // Option to remove the OpenAI disclaimer - Ver 1.4.1
    register_setting('chatbot_chatgpt_settings', 'chatgpt_disclaimer_setting');
    // Option to select narrow or wide chatboat - Ver 1.4.2
    register_setting('chatbot_chatgpt_settings', 'chatgpt_width_setting');
    // Option to set diagnotics on/off - Ver 1.5.0
    register_setting('chatbot_chatgpt_settings', 'chatgpt_diagnostics');

    add_settings_section(
        'chatbot_chatgpt_settings_section',
        'Settings',
        'chatbot_chatgpt_settings_section_callback',
        'chatbot_chatgpt_settings'
    );

    add_settings_field(
        'chatgpt_bot_name',
        'Bot Name',
        'chatbot_chatgpt_bot_name_callback',
        'chatbot_chatgpt_settings',
        'chatbot_chatgpt_settings_section'
    );

    add_settings_field(
        'chatgptStartStatus',
        'Start Status',
        'chatbot_chatgptStartStatus_callback',
        'chatbot_chatgpt_settings',
        'chatbot_chatgpt_settings_section'
    );

    add_settings_field(
        'chatgptStartStatusNewVisitor',
        'Start Status New Visitor',
        'chatbot_chatgptStartStatusNewVisitor_callback',
        'chatbot_chatgpt_settings',
        'chatbot_chatgpt_settings_section'
    );

    add_settings_field(
        'chatgpt_initial_greeting',
        'Initial Greeting',
        'chatbot_chatgpt_initial_greeting_callback',
        'chatbot_chatgpt_settings',
        'chatbot_chatgpt_settings_section'
    );

    add_settings_field(
        'chatgpt_subsequent_greeting',
        'Subsequent Greeting',
        'chatbot_chatgpt_subsequent_greeting_callback',
        'chatbot_chatgpt_settings',
        'chatbot_chatgpt_settings_section'
    );

    // Option to remove the OpenAI disclaimer - Ver 1.4.1
    add_settings_field(
        'chatgpt_disclaimer_setting',
        'Include "As an AI language model" disclaimer',
        'chatgpt_disclaimer_setting_callback',
        'chatbot_chatgpt_settings',
        'chatbot_chatgpt_settings_section'
    );

    // Option to change the width of the bot from narrow to wide - Ver 1.4.2
    add_settings_field(
        'chatgpt_width_setting',
        'Chatbot Width Setting',
        'chatgpt_width_setting_callback',
        'chatbot_chatgpt_settings',
        'chatbot_chatgpt_settings_section'
    );

    // Option to set diagnostics on/off - Ver 1.5.0
    add_settings_field(
        'chatgpt_diagnostics',
        'Chatbot Diagnostics',
        'chatgpt_diagnostics_setting_callback',
        'chatbot_chatgpt_settings',
        'chatbot_chatgpt_settings_section'
    );
    
    // Premium settings tab - Ver 1.3.0
    register_setting('chatbot_chatgpt_premium', 'chatgpt_premium_key');

    add_settings_section(
        'chatbot_chatgpt_premium_section',
        'Premium Settings',
        'chatbot_chatgpt_premium_section_callback',
        'chatbot_chatgpt_premium'
    );

    add_settings_field(
        'chatgpt_premium_key',
        'Premium Options',
        'chatbot_chatgpt_premium_key_callback',
        'chatbot_chatgpt_premium',
        'chatbot_chatgpt_premium_section'
    );

    // Support settings tab - Ver 1.3.0
    register_setting('chatbot_chatgpt_support', 'chatgpt_support_key');

    add_settings_section(
        'chatbot_chatgpt_support_section',
        'Support',
        'chatbot_chatgpt_support_section_callback',
        'chatbot_chatgpt_support'
    );

    // Avatar settings tab - Ver 1.5.0
    register_setting('chatbot_chatgpt_avatar', 'chatgpt_avatar_icon_setting');
    register_setting('chatbot_chatgpt_avatar', 'chatgpt_avatar_icon_url_setting');
    register_setting('chatbot_chatgpt_avatar', 'chatgpt_custom_avatar_icon_setting');
    register_setting('chatbot_chatgpt_avatar', 'chatgpt_avatar_greeting_setting');

    // Register a new section in the "chatbot_chatgpt" page
    add_settings_section(
        'chatbot_chatgpt_avatar_section', 
        'Avatar Settings', 
        'chatbot_chatgpt_avatar_section_callback', 
        'chatbot_chatgpt_avatar'
    );

    // Register new fields in the "chatbot_chatgpt_avatar_section" section, inside the "chatbot_chatgpt_avatar" page
    add_settings_field(
        'chatgpt_avatar_icon_setting',
        'Avatar Icon Setting',
        'chatbot_chatgpt_avatar_icon_callback',
        'chatbot_chatgpt_avatar',
        'chatbot_chatgpt_avatar_section'
    );

    add_settings_field(
        'chatgpt_custom_avatar_icon_setting',
        'Custom Avatar URL',
        'chatbot_chatgpt_custom_avatar_callback',
        'chatbot_chatgpt_avatar',
        'chatbot_chatgpt_avatar_section'
    );

    add_settings_field(
        'chatgpt_avatar_greeting_setting',
        'Avatar Greeting',
        'chatbot_chatgpt_avatar_greeting_callback',
        'chatbot_chatgpt_avatar',
        'chatbot_chatgpt_avatar_section'
    );

}

add_action('admin_init', 'chatbot_chatgpt_settings_init');

// API/Model settings section callback - Ver 1.3.0
function chatbot_chatgpt_api_model_section_callback($args) {
    ?>
    <p>Configure settings for the Chatbot ChatGPT plugin by adding your API key and selection the GPT model of your choice.</p>
    <p>This plugin requires an API key from OpenAI to function. You can obtain an API key by signing up at <a href="https://platform.openai.com/account/api-keys" target="_blank">https://platform.openai.com/account/api-keys</a>.</p>
    <p>More information about ChatGPT models and their capability can be found at <a href="https://platform.openai.com/docs/models/overview" taget="_blank">https://platform.openai.com/docs/models/overview</a>.</p>
    <p>Enter your ChatGPT API key below and select the OpenAI model of your choice.</p>
    <p>As soon as the API for GPT-4 is available for general use, you will be able to select from the latest available models.</p>
    <?php
}

// Settings section callback - Ver 1.3.0
function chatbot_chatgpt_settings_section_callback($args) {
    ?>
    <p>Configure settings for the Chatbot ChatGPT plugin, including the bot name, start status, and greetings.</p>
    <?php
}

// Premium settings section callback - Ver 1.3.0
function chatbot_chatgpt_premium_section_callback($args) {
    ?>
    <p>Enter your premium key here.</p>
    <?php
}

// Support settings section callback - Ver 1.3.0
function chatbot_chatgpt_support_section_callback($args) {
    ?>
    <div>
	<h3>Description</h3>
    <p>Chatbot ChatGPT for WordPress is a plugin that allows you to effortlessly integrate OpenAI&#8217;s ChatGPT API into your website, providing a powerful, AI-driven chatbot for enhanced user experience and personalized support.</p>
    <p>ChatGPT is a conversational AI platform that uses natural language processing and machine learning algorithms to interact with users in a human-like manner. It is designed to answer questions, provide suggestions, and engage in conversations with users. ChatGPT is important because it can provide assistance and support to people who need it, especially in situations where human support is not available or is limited. It can also be used to automate customer service, reduce response times, and improve customer satisfaction. Moreover, ChatGPT can be used in various fields such as healthcare, education, finance, and many more.</p>
    <p>Chatbot ChatGPT leverages the OpenAI platform using the gpt-3.5-turbo and gpt-4 model brings it to life within your WordPress Website.</p>
    <p><b>Important Note:</b> This plugin requires an API key from OpenAI to function correctly. You can obtain an API key by signing up at <a href="https://platform.openai.com/account/api-keys" rel="nofollow ugc" target="_blank">https://platform.openai.com/account/api-keys</a>.<p>
    <h3>Official Sites:</h3>
    <ul style="list-style-type: disc; list-style-position: inside; padding-left: 1em;"> 
    <li><a href="https://kognetiks.com/wordpress-plugins/chatbot-chatgpt/" rel="nofollow ugc" target="_blank">Kognetiks.com</a></li>
    <li><a href="https://github.com/kognetiks/chatbot-chatgpt" target="_blank">https://github.com/kognetiks/chatbot-chatgpt</a></li>
    <li><a href="https://wordpress.org/plugins/chatbot-chatgpt/" target="_blank">https://wordpress.org/plugins/chatbot-chatgpt/</a></li>
    </ul>
    <h3>Features</h3>
    <ul style="list-style-type: disc; list-style-position: inside; padding-left: 1em;">
    <li>Easy setup and integration with OpenAI&#8217;s ChatGPT API</li>
    <li>Support for gpt-3.5-turbo</li>
    <li>Support for gpt-4 (Learn how to access the gpt-4 API at <a href="https://help.openai.com/en/articles/7102672-how-can-i-access-gpt-4" rel="nofollow ugc" target="_blank">https://help.openai.com/en/articles/7102672-how-can-i-access-gpt-4</a></li>
    <li>Floating chatbot interface with customizable appearance</li>
    <li>User-friendly settings page for managing API key and other parameters</li>
    <li>Collapsible chatbot interface when not in use</li>
    <li>Initial greeting message for first-time users</li>
    <li>Shortcode to embed the chatbot on any page or post</li>
    <li>Setting to determine if chatbot should start opened or closed</li>
    <li>Chatbot maintains state when navigating between pages</li>
    <li>Chatbot name and initial and subsequent greetings are configurable</li>
    </ul>
    <h3>Getting Started</h3>
    <ol>
    <li>Obtain your API key by signign up at <a href="https://platform.openai.com/account/api-keys" rel="nofollow ugc" target="_blank">https://platform.openai.com/account/api-keys</a>.</li>
    <li>Install and activate the Chatbot ChatGPT plugin.</li>
    <li>Navigate to the settings page (Settings &gt; API/Model) and enter your API key.</li>
    <li>Customize the chatbot appearance and other parameters as needed.</li>
    <li>Add the chatbot to any page or post using the provided shortcode: [chatbot_chatgpt]</li>
    </ol>
    <p>Now your website visitors can enjoy a seamless and personalized chat experience powered by OpenAI&#8217;s ChatGPT API.</p>
    <h2>Installation</h2>
	<ol>
    <li>Upload the &#8216;chatbot-chatgpt&#8217; folder to the &#8216;/wp-content/plugins/&#8217; directory.</li>
    <li>Activate the plugin through the &#8216;Plugins&#8217; menu in WordPress.</li>
    <li>Go to the &#8216;Settings &gt; Chatbot ChatGPT&#8217; page and enter your OpenAI API key.</li>
    <li>Customize the chatbot appearance and other parameters as needed.</li>
    <li>Add the chatbot to any page or post using the provided shortcode: [chatbot_chatgpt]</li>
    </ol>
    </div>
    <?php
}

// API key field callback
function chatbot_chatgpt_api_key_callback($args) {
    $api_key = esc_attr(get_option('chatgpt_api_key'));
    ?>
    <input type="text" id="chatgpt_api_key" name="chatgpt_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text">
    <?php
}

// Model choice
function chatbot_chatgpt_model_choice_callback($args) {
    // Get the saved chatgpt_model_choice value or default to "gpt-3.5-turbo"
    $model_choice = esc_attr(get_option('chatgpt_model_choice', 'gpt-3.5-turbo'));
    ?>
    <select id="chatgpt_model_choice" name="chatgpt_model_choice">
        <!-- Allow for gpt-4 in Ver 1.4.2 -->
        <option value="<?php echo esc_attr( 'gpt-4' ); ?>" <?php selected( $model_choice, 'gpt-4' ); ?>><?php echo esc_html( 'gpt-4' ); ?></option>
        <option value="<?php echo esc_attr( 'gpt-3.5-turbo' ); ?>" <?php selected( $model_choice, 'gpt-3.5-turbo' ); ?>><?php echo esc_html( 'gpt-3.5-turbo' ); ?></option>
    </select>
    <?php
}

// Chatbot ChatGPT Name
function chatbot_chatgpt_bot_name_callback($args) {
    $bot_name = esc_attr(get_option('chatgpt_bot_name', 'Chatbot ChatGPT'));
    ?>
    <input type="text" id="chatgpt_bot_name" name="chatgpt_bot_name" value="<?php echo esc_attr( $bot_name ); ?>" class="regular-text">
    <?php
}

function chatbot_chatgptStartStatus_callback($args) {
    $start_status = esc_attr(get_option('chatgptStartStatus', 'closed'));
    ?>
    <select id="chatgptStartStatus" name="chatgptStartStatus">
        <option value="open" <?php selected( $start_status, 'open' ); ?>><?php echo esc_html( 'Open' ); ?></option>
        <option value="closed" <?php selected( $start_status, 'closed' ); ?>><?php echo esc_html( 'Closed' ); ?></option>
    </select>
    <?php
}

function chatbot_chatgptStartStatusNewVisitor_callback($args) {
    $start_status = esc_attr(get_option('chatgptStartStatusNewVisitor', 'closed'));
    ?>
    <select id="chatgptStartStatusNewVisitor" name="chatgptStartStatusNewVisitor">
        <option value="open" <?php selected( $start_status, 'open' ); ?>><?php echo esc_html( 'Open' ); ?></option>
        <option value="closed" <?php selected( $start_status, 'closed' ); ?>><?php echo esc_html( 'Closed' ); ?></option>
    </select>
    <?php
}

function chatbot_chatgpt_initial_greeting_callback($args) {
    $initial_greeting = esc_attr(get_option('chatgpt_initial_greeting', 'Hello! How can I help you today?'));
    ?>
    <textarea id="chatgpt_initial_greeting" name="chatgpt_initial_greeting" rows="2" cols="50"><?php echo esc_textarea( $initial_greeting ); ?></textarea>
    <?php
}

function chatbot_chatgpt_subsequent_greeting_callback($args) {
    $subsequent_greeting = esc_attr(get_option('chatgpt_subsequent_greeting', 'Hello again! How can I help you?'));
    ?>
    <textarea id="chatgpt_subsequent_greeting" name="chatgpt_subsequent_greeting" rows="2" cols="50"><?php echo esc_textarea( $subsequent_greeting ); ?></textarea>
    <?php
}

// Option to remove OpenAI disclaimer - Ver 1.4.1
function chatgpt_disclaimer_setting_callback($args) {
    $chatgpt_disclaimer_setting = esc_attr(get_option('chatgpt_disclaimer_setting', 'Yes'));
    ?>
    <select id="chatgpt_disclaimer_setting" name="chatgpt_disclaimer_setting">
        <option value="Yes" <?php selected( $chatgpt_disclaimer_setting, 'Yes' ); ?>><?php echo esc_html( 'Yes' ); ?></option>
        <option value="No" <?php selected( $chatgpt_disclaimer_setting, 'No' ); ?>><?php echo esc_html( 'No' ); ?></option>
    </select>
    <?php    
}

// Max Tokens choice - Ver 1.4.2
function chatgpt_max_tokens_setting_callback($args) {
    // Get the saved chatgpt_max_tokens_setting or default to 150
    $max_tokens = esc_attr(get_option('chatgpt_max_tokens_setting', '150'));
    ?>
    <select id="chatgpt_max_tokens_setting" name="chatgpt_max_tokens_setting">
        <option value="<?php echo esc_attr( '100' ); ?>" <?php selected( $max_tokens, '100' ); ?>><?php echo esc_html( '100' ); ?></option>
        <option value="<?php echo esc_attr( '150' ); ?>" <?php selected( $max_tokens, '150' ); ?>><?php echo esc_html( '150' ); ?></option>
        <option value="<?php echo esc_attr( '200' ); ?>" <?php selected( $max_tokens, '200' ); ?>><?php echo esc_html( '200' ); ?></option>
        <option value="<?php echo esc_attr( '250' ); ?>" <?php selected( $max_tokens, '250' ); ?>><?php echo esc_html( '250' ); ?></option>
        <option value="<?php echo esc_attr( '300' ); ?>" <?php selected( $max_tokens, '300' ); ?>><?php echo esc_html( '300' ); ?></option>
        <option value="<?php echo esc_attr( '350' ); ?>" <?php selected( $max_tokens, '350' ); ?>><?php echo esc_html( '350' ); ?></option>
        <option value="<?php echo esc_attr( '400' ); ?>" <?php selected( $max_tokens, '400' ); ?>><?php echo esc_html( '400' ); ?></option>
        <option value="<?php echo esc_attr( '450' ); ?>" <?php selected( $max_tokens, '450' ); ?>><?php echo esc_html( '450' ); ?></option>
        <option value="<?php echo esc_attr( '500' ); ?>" <?php selected( $max_tokens, '500' ); ?>><?php echo esc_html( '500' ); ?></option>
    </select>
    <?php
}

// Option for narrow or wide chatbot - Ver 1.4.2
function chatgpt_width_setting_callback($args) {
    $chatgpt_width = esc_attr(get_option('chatgpt_width_setting', 'Narrow'));
    ?>
    <select id="chatgpt_width_setting" name = "chatgpt_width_setting">
        <option value="Narrow" <?php selected( $chatgpt_width, 'Narrow' ); ?>><?php echo esc_html( 'Narrow' ); ?></option>
        <option value="Wide" <?php selected( $chatgpt_width, 'Wide' ); ?>><?php echo esc_html( 'Wide' ); ?></option>
    </select>
    <?php
}

// Diagnostics On/Off - Ver 1.5.0
function chatgpt_diagnostics_setting_callback($args) {
    $chatgpt_diagnostics = esc_attr(get_option('chatgpt_diagnostics', 'Off'));
    ?>
    <select id="chatgpt_diagnostics_setting" name = "chatgpt_diagnostics">
        <option value="On" <?php selected( $chatgpt_diagnostics, 'On' ); ?>><?php echo esc_html( 'On' ); ?></option>
        <option value="Off" <?php selected( $chatgpt_diagnostics, 'Off' ); ?>><?php echo esc_html( 'Off' ); ?></option>
    </select>
    <?php
}

// Premium Key - Ver 1.3.0
function chatbot_chatgpt_premium_key_callback($args) {
    $premium_key = esc_attr(get_option('chatgpt_premium_key'));
    ?>
    <input type="text" id="chatgpt_premium_key" name="chatgpt_premium_key" value="<?php echo esc_attr( $premium_key ); ?>" class="regular-text">
    <?php
}