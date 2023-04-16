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

            // Update the chatgpt_subsequent_greeting value in the local storage when the form is submitted - Ver 1.3.0
            chatgptSettingsForm.addEventListener('submit', function() {
                localStorage.setItem('chatgpt_name', chatgptNameInput.value);
                localStorage.setItem('chatgpt_initial_greeting', chatgptInitialGreetingInput.value);
                localStorage.setItem('chatgpt_subsequent_greeting', chatgptSubsequentGreetingInput.value);
                localStorage.setItem('chatgpt_open_status', chatgptOpenStatusInput.checked);

            });

        });
    </script>

        <h2 class="nav-tab-wrapper">
            <a href="?page=chatbot-chatgpt&tab=api_model" class="nav-tab <?php echo $active_tab == 'api_model' ? 'nav-tab-active' : ''; ?>">API/Model</a>
            <a href="?page=chatbot-chatgpt&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
            <!-- Coming Soon in Ver 2.0.0 -->
            <!-- <a href="?page=chatbot-chatgpt&tab=premium" class="nav-tab <?php echo $active_tab == 'premium' ? 'nav-tab-active' : ''; ?>">Premium</a> -->
            <a href="?page=chatbot-chatgpt&tab=support" class="nav-tab <?php echo $active_tab == 'support' ? 'nav-tab-active' : ''; ?>">Support</a>
        </h2>

        <form action="options.php" method="post">
            <?php
            if ($active_tab == 'settings') {
                settings_fields('chatbot_chatgpt_settings');
                do_settings_sections('chatbot_chatgpt_settings');
            } elseif ($active_tab == 'api_model') {
                settings_fields('chatbot_chatgpt_api_model');
                do_settings_sections('chatbot_chatgpt_api_model');
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
    <?php
}

// Register settings
function chatbot_chatgpt_settings_init() {

    // API/Model settings tab - Ver 1.3.0
    register_setting('chatbot_chatgpt_api_model', 'chatgpt_api_key');
    register_setting('chatbot_chatgpt_api_model', 'chatgpt_model_choice');

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

    // Settings settings tab - Ver 1.3.0
    register_setting('chatbot_chatgpt_settings', 'chatgpt_bot_name');
    register_setting('chatbot_chatgpt_settings', 'chatgpt_start_status');
    register_setting('chatbot_chatgpt_settings', 'chatgpt_initial_greeting');
    register_setting('chatbot_chatgpt_settings', 'chatgpt_subsequent_greeting');

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
        'chatgpt_start_status',
        'Start Status',
        'chatbot_chatgpt_start_status_callback',
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
    <p>Chatbot ChatGPT leverages the OpenAI platform using the gpt-3.5-turbo model brings it to life within your WordPress Website.</p>
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
    <li>Obtain your API key by signign up at <a href="https://platform.openai.com/account/api-keys" rel="nofollow ugc">https://platform.openai.com/account/api-keys</a>.</li>
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
    $api_key = get_option('chatgpt_api_key');
    ?>
    <input type="text" id="chatgpt_api_key" name="chatgpt_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text">
    <?php
}

// Model choice
function chatbot_chatgpt_model_choice_callback($args) {
    // Get the saved chatgpt_model_choice value or default to "gpt-3.5-turbo"
    $model_choice = get_option('chatgpt_model_choice', 'gpt-3.5-turbo');
    ?>
    <select id="chatgpt_model_choice" name="chatgpt_model_choice">
        <!-- Coming Soon in Ver 2.0.0
        <option value="<?php echo esc_attr( 'gpt-4' ); ?>" <?php selected( $model_choice, 'gpt-4' ); ?>><?php echo esc_html( 'gpt-4' ); ?></option>
        -->
        <option value="<?php echo esc_attr( 'gpt-3.5-turbo' ); ?>" <?php selected( $model_choice, 'gpt-3.5-turbo' ); ?>><?php echo esc_html( 'gpt-3.5-turbo' ); ?></option>
    </select>
    <?php
}

// Chatbot ChatGPT Name
function chatbot_chatgpt_bot_name_callback($args) {
    $bot_name = get_option('chatgpt_bot_name', 'Chatbot ChatGPT');
    ?>
    <input type="text" id="chatgpt_bot_name" name="chatgpt_bot_name" value="<?php echo esc_attr( $bot_name ); ?>" class="regular-text">
    <?php
}

function chatbot_chatgpt_start_status_callback($args) {
    $start_status = get_option('chatgpt_start_status', 'closed');
    ?>
    <select id="chatgpt_start_status" name="chatgpt_start_status">
        <option value="open" <?php selected( $start_status, 'open' ); ?>>Open</option>
        <option value="closed" <?php selected( $start_status, 'closed' ); ?>>Closed</option>
    </select>
    <?php
}

function chatbot_chatgpt_initial_greeting_callback($args) {
    $initial_greeting = get_option('chatgpt_initial_greeting', 'Hello! How can I help you today?');
    ?>
    <textarea id="chatgpt_initial_greeting" name="chatgpt_initial_greeting" rows="2" cols="50"><?php echo esc_textarea( $initial_greeting ); ?></textarea>
    <?php
}

function chatbot_chatgpt_subsequent_greeting_callback($args) {
    $subsequent_greeting = get_option('chatgpt_subsequent_greeting', 'Hello again! How can I help you?');
    ?>
    <textarea id="chatgpt_subsequent_greeting" name="chatgpt_subsequent_greeting" rows="2" cols="50"><?php echo esc_textarea( $subsequent_greeting ); ?></textarea>
    <?php
}

// Premium Key - Ver 1.3.0
function chatbot_chatgpt_premium_key_callback($args) {
    $premium_key = get_option('chatgpt_premium_key');
    ?>
    <input type="text" id="chatgpt_premium_key" name="chatgpt_premium_key" value="<?php echo esc_attr( $premium_key ); ?>" class="regular-text">
    <?php
}