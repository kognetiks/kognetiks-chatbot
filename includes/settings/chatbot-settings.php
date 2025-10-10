<?php
/**
 * Kognetiks Chatbot - Settings Page
 *
 * This file contains the code for the Chatbot settings page.
 * It allows users to configure the bot name, start status, and greetings.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Set up the Chatbot Main Menu Page - Ver 1.9.0
// function chatbot_chatgpt_menu_page() {

//     add_menu_page(
//         'Chatbot Settings',                     // Page title
//         'Kognetiks Chatbot',                    // Menu title
//         'manage_options',                       // Capability
//         'chatbot-chatgpt',                      // Menu slug
//         'chatbot_chatgpt_settings_page',        // Callback function
//         'dashicons-format-chat'                 // Icon URL (optional)
//     );

// }
// add_action('admin_menu', 'chatbot_chatgpt_menu_page');

// Settings page HTML - Ver 1.3.0
function chatbot_chatgpt_settings_page() {
    
    if (!current_user_can('manage_options')) {
        return;
    }

    global $chatbot_chatgpt_plugin_version;

    global $kchat_settings;

    $kchat_settings['chatbot_chatgpt_version'] = $chatbot_chatgpt_plugin_version;
    $kchat_settings_json = wp_json_encode($kchat_settings);
    $escaped_kchat_settings_json = esc_js($kchat_settings_json);   
    wp_add_inline_script('chatbot-chatgpt-local', 'if (typeof kchat_settings === "undefined") { var kchat_settings = ' . $escaped_kchat_settings_json . '; } else { kchat_settings = ' . $escaped_kchat_settings_json . '; }', 'before');
    
    // Localize the settings - Added back in for Ver 1.8.5
    chatbot_chatgpt_localize();

    $active_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'general';
   
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

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', 'chatbot_chatgpt_settings_page() - $active_tab: ' . $active_tab );
    // back_trace( 'NOTICE', 'Current Page: ' . $_GET['page']);
    // back_trace( 'NOTICE', 'Current Tab: ' . ($_GET['tab'] ?? 'No Tab Set'));
    // back_trace( 'NOTICE', 'chatbot_ai_platform_choice: ' . esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI')));

    ?>
    <div id="chatbot-chatgpt-settings" class="wrap">
        <h1><span class="dashicons dashicons-format-chat" style="font-size: 25px;"></span> Kognetiks Chatbot</h1>

       <script>
            window.onload = function() {
                // Assign the function to the window object to make it globally accessible
                window.selectIcon = function(id) {
                    let chatbot_chatgpt_Element = document.getElementById('chatbot_chatgpt_avatar_icon_setting');
                    if(chatbot_chatgpt_Element) {
                        // Clear border from previously selected icon
                        let previousIconId = chatbot_chatgpt_Element.value;
                        let previousIcon = document.getElementById(previousIconId);
                        if(previousIcon) previousIcon.style.border = "none";  // Change "" to "none"

                        // Set border for new selected icon
                        let selectedIcon = document.getElementById(id);
                        if(selectedIcon) selectedIcon.style.border = "2px solid red";

                        // Set selected icon value in hidden input
                        chatbot_chatgpt_Element.value = id;

                        // Save selected icon in local storage
                        localStorage.setItem('chatbot_chatgpt_avatar_icon_setting', id);
                    }
                }

                // If no icon has been selected, select the first one by default
                let iconFromStorage = localStorage.getItem('chatbot_chatgpt_avatar_icon_setting');
                let chatbot_chatgpt_Element = document.getElementById('chatbot_chatgpt_avatar_icon_setting');
                if(chatbot_chatgpt_Element) {
                    if (iconFromStorage) {
                        window.selectIcon(iconFromStorage);
                    } else if (chatbot_chatgpt_Element.value === '') {
                        window.selectIcon('icon-001.png');
                    }
                }
            }
       </script>

       <h2 class="nav-tab-wrapper">
            <a href="?page=chatbot-chatgpt&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">General</a>
            <?php if (esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI')) == 'OpenAI') { ?><a href="?page=chatbot-chatgpt&tab=api_chatgpt" class="nav-tab <?php echo $active_tab == 'api_chatgpt' ? 'nav-tab-active' : ''; ?>">API/ChatGPT</a> <?php } ?>
            <?php if (esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI')) == 'OpenAI') { ?><a href="?page=chatbot-chatgpt&tab=gpt_assistants" class="nav-tab <?php echo $active_tab == 'gpt_assistants' ? 'nav-tab-active' : ''; ?>">GPT Assistants</a>  <?php } ?>
            <?php if (esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI')) == 'NVIDIA') { ?><a href="?page=chatbot-chatgpt&tab=api_nvidia" class="nav-tab <?php echo $active_tab == 'api_nvidia' ? 'nav-tab-active' : ''; ?>">API/NVIDIA</a> <?php } ?>
            <?php if (esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI')) == 'Anthropic') { ?><a href="?page=chatbot-chatgpt&tab=api_anthropic" class="nav-tab <?php echo $active_tab == 'api_anthropic' ? 'nav-tab-active' : ''; ?>">API/Anthropic</a> <?php } ?>
            <?php if (esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI')) == 'DeepSeek') { ?><a href="?page=chatbot-chatgpt&tab=api_deepseek" class="nav-tab <?php echo $active_tab == 'api_deepseek' ? 'nav-tab-active' : ''; ?>">API/DeepSeek</a> <?php } ?>
            <?php if (esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI')) == 'Mistral') { ?><a href="?page=chatbot-chatgpt&tab=api_mistral" class="nav-tab <?php echo $active_tab == 'api_mistral' ? 'nav-tab-active' : ''; ?>">API/Mistral</a> <?php } ?>
            <?php if (esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI')) == 'Mistral') { ?><a href="?page=chatbot-chatgpt&tab=mistral_agent" class="nav-tab <?php echo $active_tab == 'mistral_agent' ? 'nav-tab-active' : ''; ?>">Mistral Agent</a> <?php } ?>
            <?php if (esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI')) == 'Local Server') { ?><a href="?page=chatbot-chatgpt&tab=api_local" class="nav-tab <?php echo $active_tab == 'api_local' ? 'nav-tab-active' : ''; ?>">API/Local</a> <?php } ?>
            <?php if (esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI')) == 'Markov Chain') { ?><a href="?page=chatbot-chatgpt&tab=api_markov" class="nav-tab <?php echo $active_tab == 'api_markov' ? 'nav-tab-active' : ''; ?>">API/Markov</a> <?php } ?>
            <?php if (esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI')) == 'Transformer') { ?><a href="?page=chatbot-chatgpt&tab=api_transformer" class="nav-tab <?php echo $active_tab == 'api_transformer' ? 'nav-tab-active' : ''; ?>">API/Transformer</a> <?php } ?>
            <?php if (esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI')) == 'Azure OpenAI') { ?><a href="?page=chatbot-chatgpt&tab=api_azure" class="nav-tab <?php echo $active_tab == 'api_azure' ? 'nav-tab-active' : ''; ?>">API/Azure OpenAI</a> <?php } ?>
            <?php if (esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI')) == 'Azure OpenAI') { ?><a href="?page=chatbot-chatgpt&tab=gpt_azure_assistants" class="nav-tab <?php echo $active_tab == 'gpt_azure_assistants' ? 'nav-tab-active' : ''; ?>">GPT Assistants</a>  <?php } ?>
            <a href="?page=chatbot-chatgpt&tab=avatar" class="nav-tab <?php echo $active_tab == 'avatar' ? 'nav-tab-active' : ''; ?>">Avatars</a>
            <a href="?page=chatbot-chatgpt&tab=appearance" class="nav-tab <?php echo $active_tab == 'appearance' ? 'nav-tab-active' : ''; ?>">Appearance</a>
            <a href="?page=chatbot-chatgpt&tab=custom_buttons" class="nav-tab <?php echo $active_tab == 'custom_buttons' ? 'nav-tab-active' : ''; ?>">Buttons</a>
            <a href="?page=chatbot-chatgpt&tab=kn_acquire" class="nav-tab <?php echo $active_tab == 'kn_acquire' ? 'nav-tab-active' : ''; ?>">Knowledge Navigator</a>
            <a href="?page=chatbot-chatgpt&tab=reporting" class="nav-tab <?php echo $active_tab == 'reporting' ? 'nav-tab-active' : ''; ?>">Reporting</a>
            <a href="?page=chatbot-chatgpt&tab=analytics" class="nav-tab <?php echo $active_tab == 'analytics' ? 'nav-tab-active' : ''; ?>">Analytics</a>
            <a href="?page=chatbot-chatgpt&tab=tools" class="nav-tab <?php echo $active_tab == 'tools' ? 'nav-tab-active' : ''; ?>">Tools</a>
            <a href="?page=chatbot-chatgpt&tab=diagnostics" class="nav-tab <?php echo $active_tab == 'diagnostics' ? 'nav-tab-active' : ''; ?>">Messages</a>
            <a href="?page=chatbot-chatgpt&tab=support" class="nav-tab <?php echo $active_tab == 'support' ? 'nav-tab-active' : ''; ?>">Support</a>
       </h2>

       <form id="chatgpt-settings-form" action="options.php" method="post">
            <?php

            $chatbot_ai_platform_choice = esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI'));

            if ($active_tab == 'general') {

                settings_fields('chatbot_chatgpt_settings');

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_bot_settings_general');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_ai_engine_settings');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_name_settings');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_message_limits_settings');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_greetings_settings');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_additional_setup_settings');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_advanced_search_settings');
                echo '</div>';

            } elseif ($active_tab == 'api_chatgpt' && $chatbot_ai_platform_choice == 'OpenAI') {

                settings_fields('chatbot_chatgpt_api_chatgpt');

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_model_settings_general');
                echo '</div>';

                // API Settings - Ver 1.9.5
                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_api_chatgpt_general');
                echo '</div>';

                // ChatGPT API Settings - Ver 1.9.5
                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_api_chatgpt_chat');
                echo '</div>';

                // Voice Settings - Ver 1.9.5
                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_api_chatgpt_voice');
                echo '</div>';

                // Whisper Settings - Ver 2.0.1
                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_api_chatgpt_whisper');
                echo '</div>';

                // Image Settings - Ver 1.9.5
                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_api_chatgpt_image');
                echo '</div>';

                // Advanced Settings - Ver 1.9.5
                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_api_chatgpt_advanced');
                echo '</div>';

            } elseif ($active_tab == 'gpt_assistants' && $chatbot_ai_platform_choice == 'OpenAI') {

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

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_remote_widget_settings');
                echo '</div>';

            } elseif ($active_tab == 'api_azure' && $chatbot_ai_platform_choice == 'Azure OpenAI') {

                settings_fields('chatbot_azure_api_model');

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_azure_model_settings_general');
                echo '</div>';

                // API Settings - Ver 1.9.5
                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_azure_api_general');
                echo '</div>';

                // ChatGPT API Settings - Ver 1.9.5
                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_azure_api_chat');
                echo '</div>';

                // Voice Settings - Ver 1.9.5
                // echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                // do_settings_sections('chatbot_azure_api_voice');
                // echo '</div>';

                // Whisper Settings - Ver 2.0.1
                // echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                // do_settings_sections('chatbot_azure_api_whisper');
                // echo '</div>';

                // Image Settings - Ver 1.9.5
                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_azure_api_image');
                echo '</div>';

                // Advanced Settings - Ver 1.9.5
                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_azure_api_advanced');
                echo '</div>';

            } elseif ($active_tab == 'gpt_azure_assistants' && $chatbot_ai_platform_choice == 'Azure OpenAI') {

                settings_fields('chatbot_azure_custom_gpts');

                // Manage Assistants - Ver 2.0.4
                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_azure_assistant_settings');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                // do_settings_sections('chatbot_azure_assistants_management');
                display_chatbot_azure_assistants_table();
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_azure_gpt_assistants_settings');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_azure_additional_assistant_settings');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_azure_remote_widget_settings');
                echo '</div>';

            } elseif ($active_tab == 'api_nvidia' && $chatbot_ai_platform_choice == 'NVIDIA') {

                settings_fields('chatbot_nvidia_api_model');

                // NVIDIA API Settings - Ver 2.1.8

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_nvidia_model_settings_general');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_nvidia_api_model_general');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_nvidia_api_model_chat_settings');
                echo '</div>';

                // Advanced Settings
                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_nvidia_api_model_advanced');
                echo '</div>';
            
            } elseif ($active_tab == 'api_anthropic' && $chatbot_ai_platform_choice == 'Anthropic') {

                settings_fields('chatbot_anthropic_api_model');

                // NVIDIA API Settings - Ver 2.1.8

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_anthropic_model_settings_general');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_anthropic_api_model_general');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_anthropic_api_model_chat_settings');
                echo '</div>';

                // Advanced Settings
                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_anthropic_api_model_advanced');
                echo '</div>';

            } elseif ($active_tab == 'api_deepseek' && $chatbot_ai_platform_choice == 'DeepSeek') {

                settings_fields('chatbot_deepseek_api_model');

                // NVIDIA API Settings - Ver 2.1.8

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_deepseek_model_settings_general');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_deepseek_api_model_general');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_deepseek_api_model_chat_settings');
                echo '</div>';

                // Advanced Settings
                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_deepseek_api_model_advanced');
                echo '</div>';

            } elseif ($active_tab == 'api_mistral' && $chatbot_ai_platform_choice == 'Mistral') {

                settings_fields('chatbot_mistral_api_model');

                // Mistral API Settings

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_mistral_model_settings_general');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_mistral_api_model_general');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_mistral_api_model_chat_settings');
                echo '</div>';

                // Advanced Settings
                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_mistral_api_model_advanced');
                echo '</div>';

            } elseif ($active_tab == 'mistral_agent' && $chatbot_ai_platform_choice == 'Mistral') {

                settings_fields('chatbot_mistral_agents');

                // Manage Agents - Ver 2.3.0
                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_mistral_agent_settings');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                display_chatbot_mistral_assistants_table();
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_mistral_agents_settings');
                echo '</div>';

                // NO ADVANCED SETTINGS FOR MISTRAL AGENTS = Ver 2.3.0
                // echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                // do_settings_sections('chatbot_mistral_additional_assistant_settings');
                // echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_mistral_remote_widget_settings');
                echo '</div>';

            } elseif ($active_tab == 'api_local' && $chatbot_ai_platform_choice == 'Local Server') {

                settings_fields('chatbot_local_api_model');

                // NVIDIA API Settings - Ver 2.1.8

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_local_model_settings_general');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_local_api_model_general');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_local_api_model_chat_settings');
                echo '</div>';

                // Advanced Settings
                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_local_api_model_advanced');
                echo '</div>';

            } elseif ($active_tab == 'api_markov' && $chatbot_ai_platform_choice == 'Markov Chain') {

                settings_fields('chatbot_markov_chain_api_model');

                // Markov Chain Settings - Ver 2.1.6

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_markov_chain_model_settings_general');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_markov_chain_api_model_general');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_markov_chain_status');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_markov_chain_advanced_settings');
                echo '</div>';

            } elseif ($active_tab == 'api_transformer' && $chatbot_ai_platform_choice == 'Transformer') {

                settings_fields('chatbot_transformer_model_api_model');

                // Transformer Settings - Ver 2.2.0

                // Transformer Model Settings - Ver 2.2.0
                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_transformer_model_settings_general');
                echo '</div>';

                // Transformer API Settings - Ver 2.2.0
                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_transformer_model_api_model_general');
                echo '</div>';

                // Transformer Chat Settings - Ver 2.2.1
                // echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                // do_settings_sections('chatbot_transformer_model_status');
                // echo '</div>';

                // Transformer Advanced Settings - Ver 2.2.0
                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_transformer_model_advanced_settings');
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

            } elseif ($active_tab == 'diagnostics') { // AKA Messages tab

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

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_advanced');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_beta_features');
                echo '</div>';

            } elseif ($active_tab == 'appearance') {

                settings_fields('chatbot_chatgpt_appearance');

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_appearance_overview');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_appearance');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_appearance_icons_overview');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_chatgpt_appearance_icons');
                echo '</div>';

            } elseif ($active_tab == 'analytics') {

                // DIAG - Diagnostics - Ver 3.2.1.1
                // back_trace( 'NOTICE', 'Analytics Tab');
                // back_trace( 'NOTICE', chatbot_chatgpt_freemius()->is_plan( 'premium' ));
                // back_trace( 'NOTICE', chatbot_chatgpt_freemius()->can_use_premium_code__premium_only());

                if ( chatbot_chatgpt_freemius()->can_use_premium_code__premium_only() ) {
                    if ( chatbot_chatgpt_freemius()->is_plan( 'premium' ) ) {
                        if (function_exists('kognetiks_analytics_settings_page')) {
                            kognetiks_analytics_settings_page();
                        } else {
                            echo '<div class="notice notice-error"><p>Analytics functionality is not available. Please ensure the analytics addon is activated.</p></div>';
                        }
                    }
                } else {
                    echo '<div class="kchat-analytics-upgrade-notice" style="border: 1px solid #ccd0d4; background-color: #f9f9f9; padding: 20px; border-radius: 8px; margin-top: 20px;">';

                    echo '<div class="kchat-analytics-upgrade-notice" style="border: 2px solid #ccd0d4; background-color: #f9f9f9; padding: 20px; border-radius: 8px; margin-top: 20px;">';
                    echo '<h2 style="margin-top: 0;">🚀 Unlock Powerful Insights with Analytics <span style="color: #d54e21;">(Premium Feature)</span></h2>';
                    
                    echo '<p>Curious how visitors <em>really</em> interact with your chatbot?</p>';
                    
                    echo '<ul style="margin-left: 20px;">';
                    echo '<li>📊 Track total conversations, engagement rates, and drop-off points</li>';
                    echo '<li>😊 Understand visitor <strong>sentiment</strong>—who\'s happy, who\'s frustrated, and why</li>';
                    echo '<li>🔍 Filter by visitor or chatbot to see both sides of every interaction</li>';
                    echo '<li>💡 Discover what\'s working (and what\'s not) so you can improve outcomes</li>';
                    echo '</ul>';
                    
                    echo '<hr style="margin: 20px 0;">';
                    
                    echo '<h3>🔒 Premium Features</h3>';
                    echo '<p>This feature is part of the <strong>Kognetiks Premium</strong> plan. Activate your license key to:</p>';
                    
                    echo '<ul style="margin-left: 20px;">';
                    echo '<li>- Enable automated and manual sentiment scoring</li>';
                    echo '<li>- Monitor real-time engagement trends</li>';
                    echo '<li>- Track token usage to manage OpenAI (and other LLM) costs</li>';
                    echo '<li>- Make data-driven improvements to your chatbot experience</li>';
                    echo '</ul>';

                    echo '</div>';

                    // echo '<div style="border: 2px solid #d54e21; background-color: #fff8f6; padding: 15px; margin: 15px 0; border-radius: 5px;">';
                    // echo '<h3>🛑 BEFORE ACTIVATING THE PREMIUM VERSION</h3>';
                    // echo '<p>You will need to follow these steps:</p>';
                    // echo '<ul style="margin-left: 20px;">';
                    // echo '<li>1. DEACTIVATE the free version of the Kognetiks Chatbot plugin.</li>';
                    // echo '<li>2. From your WordPress Dashboard, click on Plugins > Installed Plugins > Kognetiks Chatbot > Deactivate</li>';
                    // echo '<li>3. Then return here, click the Upgrade Now button to select the premium subscription.</li>';
                    // echo '<li>4. After purchase, you will recieve an email with instructions to activated your account, you can download then download the premium version of the plugin.</li>';
                    // echo '<li>5. From your WordPress Dashboard, click Plugins > Add Plugin > Upload Plugin > Choose File > Install Now > Activate</li>';
                    // echo '<li>NOTE: You can choose to delete the free version of the plugin after you have upgraded to the premium version.</li>';
                    // echo '</ul>';
                    // echo '</div>';

                    echo '<hr style="margin: 20px 0;">';
                    
                    echo '<h3>✅ Ready to Upgrade?</h3>';
                    echo '<p>';
                    echo '<a href="' . chatbot_chatgpt_freemius()->get_upgrade_url() . '" class="button button-primary" style="text-decoration: none; margin-right: 10px;">Upgrade Now</a>';
                    echo '<a href="' . esc_url(admin_url('admin.php?page=chatbot-chatgpt&tab=support&dir=analytics-package&file=analytics-package.md')) . '" style="margin-right: 10px;">Learn more</a>';
                    echo '<a href="mailto:support@kognetiks.com">Contact Support</a>';
                    echo '</p>';
                    echo '</div>';
                }
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
                do_settings_sections('chatbot_manage_widget_logs');
                echo '</div>';

                // echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                // do_settings_sections('chatbot_chatgpt_shortcode_tools');
                // echo '</div>';

                // echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                // do_settings_sections('chatbot_chatgpt_capability_tools');
                // echo '</div>';

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
