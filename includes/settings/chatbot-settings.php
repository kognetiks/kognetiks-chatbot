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
    if ( $chatbot_chatgpt_appearance_reset == 'Yes' ) {
        chatbot_chatgpt_appearance_restore_default_settings();
    }

    // DIAG - Diagnostics

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

       <script type="text/javascript">
            // Unsaved Changes Warning - Alert admin when navigating away without saving
            jQuery(document).ready(function($) {
                var formChanged = false;
                var formSubmitting = false;
                var settingsForm = $('#chatgpt-settings-form');
                
                if (!settingsForm.length) {
                    return; // Form not found, exit
                }
                
                // Store initial form values for comparison
                var initialFormData;
                
                // Capture initial form state after all JavaScript has finished initializing
                // This prevents false positives from dynamic content that loads after page load
                // Use a delay to ensure all tab-specific JavaScript (like conditional field toggling) has run
                setTimeout(function() {
                    initialFormData = settingsForm.serialize();
                    formChanged = false; // Ensure flag is false after capturing initial state
                }, 500);
                
                // Reset formChanged flag after successful save (when page reloads with settings-updated parameter)
                if (window.location.search.indexOf('settings-updated=true') !== -1) {
                    formChanged = false;
                }
                
                // Track form changes on all form inputs
                settingsForm.on('input change keyup', 'input, select, textarea', function() {
                    // Skip hidden inputs and buttons
                    var inputType = $(this).attr('type');
                    if (inputType !== 'hidden' && inputType !== 'submit' && inputType !== 'button') {
                        if (!formSubmitting) {
                            formChanged = true;
                        }
                    }
                });
                
                // Also check if form has actually changed by comparing current state to initial
                function checkFormChanged() {
                    // If initialFormData hasn't been set yet (e.g., after a save reload), no changes detected
                    if (typeof initialFormData === 'undefined') {
                        return false;
                    }
                    var currentFormData = settingsForm.serialize();
                    return currentFormData !== initialFormData;
                }
                
                // Reset flag when form is submitted
                settingsForm.on('submit', function() {
                    formSubmitting = true;
                    formChanged = false;
                    // Update initial form data on submit
                    initialFormData = settingsForm.serialize();
                });
                
                // Warn before leaving page (browser navigation, refresh, close tab)
                $(window).on('beforeunload', function(e) {
                    // Skip warning if this is a programmatic reload (e.g., from Test buttons)
                    if (window.programmaticReload) {
                        return;
                    }
                    // Only warn if form has actually changed and we're not submitting
                    // Check both the flag and actual form state to avoid false positives
                    var hasChanges = formChanged || checkFormChanged();
                    if (hasChanges && !formSubmitting) {
                        e.preventDefault();
                        // Modern browsers ignore custom messages, but we still need to set returnValue
                        e.returnValue = '';
                        return '';
                    }
                });
                
                // Warn before clicking tab navigation links
                // Use capture phase to catch the event before other handlers
                document.addEventListener('click', function(e) {
                    var target = e.target;
                    // Check if clicked element is a nav tab or inside one
                    var navTab = $(target).closest('.nav-tab');
                    if (navTab.length && navTab.closest('.nav-tab-wrapper').length) {
                        // Only check actual form state, not the flag (which can have false positives)
                        // If form hasn't actually changed, reset the flag to prevent future false positives
                        var hasChanges = checkFormChanged();
                        if (hasChanges && !formSubmitting) {
                            var href = navTab.attr('href');
                            // Only intercept if it's a settings page tab
                            if (href && (href.indexOf('page=chatbot-chatgpt') !== -1 || href.indexOf('?page=chatbot-chatgpt') !== -1)) {
                                if (!confirm('You have unsaved changes. Are you sure you want to leave this page? Your changes will be lost.')) {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    e.stopImmediatePropagation();
                                    return false;
                                }
                                // User confirmed, allow navigation
                                formChanged = false;
                                initialFormData = settingsForm.serialize(); // Update initial state
                            }
                        } else if (!hasChanges) {
                            // Form hasn't actually changed, reset the flag to prevent false positives
                            formChanged = false;
                        }
                    }
                }, true); // Use capture phase
            });
       </script>

       <h2 class="nav-tab-wrapper">
            <a href="?page=chatbot-chatgpt&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">General</a>
            <?php if (esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI')) == 'OpenAI') { ?><a href="?page=chatbot-chatgpt&tab=api_chatgpt" class="nav-tab <?php echo $active_tab == 'api_chatgpt' ? 'nav-tab-active' : ''; ?>">API/ChatGPT</a> <?php } ?>
            <?php if (esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI')) == 'OpenAI') { ?><a href="?page=chatbot-chatgpt&tab=gpt_assistants" class="nav-tab <?php echo $active_tab == 'gpt_assistants' ? 'nav-tab-active' : ''; ?>">GPT Assistants</a>  <?php } ?>
            <?php if (esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI')) == 'NVIDIA') { ?><a href="?page=chatbot-chatgpt&tab=api_nvidia" class="nav-tab <?php echo $active_tab == 'api_nvidia' ? 'nav-tab-active' : ''; ?>">API/NVIDIA</a> <?php } ?>
            <?php if (esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI')) == 'Anthropic') { ?><a href="?page=chatbot-chatgpt&tab=api_anthropic" class="nav-tab <?php echo $active_tab == 'api_anthropic' ? 'nav-tab-active' : ''; ?>">API/Anthropic</a> <?php } ?>
            <?php if (esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI')) == 'DeepSeek') { ?><a href="?page=chatbot-chatgpt&tab=api_deepseek" class="nav-tab <?php echo $active_tab == 'api_deepseek' ? 'nav-tab-active' : ''; ?>">API/DeepSeek</a> <?php } ?>
            <?php if (esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI')) == 'Google') { ?><a href="?page=chatbot-chatgpt&tab=api_google" class="nav-tab <?php echo $active_tab == 'api_google' ? 'nav-tab-active' : ''; ?>">API/Google</a> <?php } ?>
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
            <a href="?page=chatbot-chatgpt&tab=insights" class="nav-tab <?php echo $active_tab == 'insights' ? 'nav-tab-active' : ''; ?>">Insights</a>
            <a href="?page=chatbot-chatgpt&tab=tools" class="nav-tab <?php echo $active_tab == 'tools' ? 'nav-tab-active' : ''; ?>">Tools</a>
            <a href="?page=chatbot-chatgpt&tab=diagnostics" class="nav-tab <?php echo $active_tab == 'diagnostics' ? 'nav-tab-active' : ''; ?>">Messages</a>
            <a href="?page=chatbot-chatgpt&tab=support" class="nav-tab <?php echo $active_tab == 'support' ? 'nav-tab-active' : ''; ?>">Support</a>
       </h2>

       <form id="chatgpt-settings-form" action="options.php" method="post">
            <?php

            $chatbot_ai_platform_choice = esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI'));

            if ($active_tab == 'general') {

                settings_fields('chatbot_chatgpt_settings');

                // Breadcrumb info line - Ver 2.4.1
                // Only show to free users (Premium users already have access to these features)
                // Hide if admin notice was dismissed (linked to same discovery unit)
                $is_premium = function_exists('chatbot_chatgpt_is_premium') ? chatbot_chatgpt_is_premium() : false;
                $dismissed  = function_exists('chatbot_chatgpt_get_option')
                    ? chatbot_chatgpt_get_option('chatbot_chatgpt_reporting_notice_dismissed', '0')
                    : get_option('chatbot_chatgpt_reporting_notice_dismissed', '0');
                
                if ( ! $is_premium && $dismissed !== '1' ) {
                    $reporting_url = admin_url('admin.php?page=chatbot-chatgpt&tab=reporting');
                    echo '<p class="description" style="margin-bottom: 15px;">New: Conversation summaries and proof-of-value reports are now available under <a href="' . esc_url($reporting_url) . '">Reporting</a>.</p>';
                }

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

            } elseif ($active_tab == 'api_google' && $chatbot_ai_platform_choice == 'Google') {

                settings_fields('chatbot_google_api_model');

                // Google API Settings - Ver 2.3.9

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_google_model_settings_general');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_google_api_model_general');
                echo '</div>';

                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_google_api_model_chat_settings');
                echo '</div>';

                // Advanced Settings
                echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                do_settings_sections('chatbot_google_api_model_advanced');
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

                $selected_transformer_model = esc_attr(get_option('chatbot_transformer_model_choice', 'sentential-context-model'));
                if ('lexical-context-model' === $selected_transformer_model) {
                    echo '<div style="background-color: #f9f9f9; padding: 20px; margin-top: 10px; border: 1px solid #ccc;">';
                    do_settings_sections('chatbot_transformer_model_cache_info');
                    echo '</div>';
                }

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
                do_settings_sections('chatbot_chatgpt_conversation_digest');
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

            } elseif ($active_tab == 'insights') {

                // Check if user has premium access using the centralized helper function
                // This follows Freemius best practices for premium status checks
                $has_premium_access = function_exists( 'chatbot_chatgpt_is_premium' ) ? chatbot_chatgpt_is_premium() : false;

                if ( $has_premium_access ) {
                    // Ensure Insights files are loaded (in case they weren't loaded at plugin init)
                    // This handles the case where user upgraded but files weren't loaded yet
                    chatbot_chatgpt_load_insights_files();
                    
                    // Load the actual insights functionality
                    if (function_exists('kognetiks_insights_settings_page')) {
                        kognetiks_insights_settings_page();
                    } else {
                        echo '<div class="notice notice-error" style="padding: 20px; margin: 20px 0;">';
                        echo '<h2 style="margin-top: 0;">⚠️ Insights System Not Available</h2>';
                        echo '<p>The insights system is not properly loaded. Please check that all insights files are present.</p>';
                        echo '<div class="notice notice-error"><p>Insights functionality is not available. Please ensure the insights addon is activated.</p></div>';
                    }
                } else {
                    echo '<div class="kchat-insights-upgrade-notice" style="border: 1px solid #ccd0d4; background-color: #f9f9f9; padding: 20px; border-radius: 8px; margin-top: 20px;">';

                    echo '<div class="kchat-insights-upgrade-notice" style="border: 2px solid #ccd0d4; background-color: #f9f9f9; padding: 20px; border-radius: 8px; margin-top: 20px;">';
                    echo '<h2 style="margin-top: 0;">🚀 Unlock Conversation Insights <span>(Premium Feature)</span></h2>';
                    
                    echo '<p>Your chatbot is having conversations whether you are watching or not.</p>';
                    echo '<p><b>Insights help you understand when converations are helping and when they need attention.</b></p>';
                    echo '<p>Instead of digging through logs or guessing what\'s happening, Insights highlights the signals that matter most.</p>';
                    echo '<p><b>With Insights, you can:</b></p>';

                    echo '<ul style="margin-left: 20px;">';
                    echo '<li>📊 See how conversations are trending<br>';
                    echo 'Understand engagement patterns, drop-off points, and recurring issues.</li>';
                    echo '<li>⚠️ Detect when conversations need attention<br>';
                    echo 'Identify signs of confusion, frustration, or escalation risk.</li>';
                    echo '<li>🔍 Review conversations that matter<br>';
                    echo 'Focus on interactions that may require follow-up or improvement.</li>';
                    echo '<li>💡 Improve outcomes with confidence<br>';
                    echo 'Learn what is working, what is not, and where small changes can help.</li>';
                    echo '</ul>';
                    
                    echo '<hr style="margin: 20px 0;">';
                    
                    echo '<h3>🔒 Premium Insights Features</h3>';
                    echo '<p>Insights is part of the <strong>Kognetiks Premium</strong> plan.</br>';
                    echo 'Activate your license key to:</p>';
                    
                    echo '<ul style="margin-left: 20px; list-style-type:disc;">';
                    echo '<li>Automated and manual conversation health scoring</li>';
                    echo '<li>Real-time engagement and frustration signals</li>';
                    echo '<li>Token usage visibility to help manage AI costs</li>';
                    echo '<li>Tools designed to support smarter, safer chatbot improvements</li>';
                    echo '</ul>';

                    echo '<hr style="margin: 20px 0;">';
                    
                    echo '<h3>✅ Designed for Visibility, Not Guesswork</h3>';

                    echo '<p>Insights highlight what matters so you can decide when action is needed.<br>';
                    echo 'It doesn\'t replace judgment, it supports it.</p>';

                    echo '<hr style="margin: 20px 0;">';
                    
                    echo '<h3>✅ Ready to Upgrade?</h3>';
                    echo '<p>Reports delivered automatically. No dashboard monitoring required.</p>';
                    echo '<p>';
                    // Trial-first CTA with safety guards
                    if (function_exists('chatbot_chatgpt_freemius')) {
                        $fs = chatbot_chatgpt_freemius();
                        if (is_object($fs) && method_exists($fs, 'get_trial_url')) {
                            $trial_url = $fs->get_trial_url();
                            echo '<a href="' . esc_url($trial_url) . '" class="button button-primary" style="text-decoration: none; margin-right: 10px;">Start Free Trial</a>';
                        }
                        // Secondary "View Plans" link
                        if (is_object($fs) && method_exists($fs, 'get_upgrade_url')) {
                            $upgrade_url = $fs->get_upgrade_url();
                            echo '<a href="' . esc_url($upgrade_url) . '" class="button button-secondary" style="text-decoration: none; margin-right: 10px;">View Plans</a>';
                        }
                    }
                    echo '<a href="' . esc_url(admin_url('admin.php?page=chatbot-chatgpt&tab=support&dir=analytics-package&file=analytics-package.md')) . '" style="margin-right: 10px;">Learn more</a>';
                    echo '<a href="mailto:support@kognetiks.com">Contact Support</a>';
                    echo '</p>';

                    echo '</div>';
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

                // Breadcrumb info line - Ver 2.4.1
                // Only show to free users (Premium users already have access to these features)
                // Hide if admin notice was dismissed (linked to same discovery unit)
                $is_premium = function_exists('chatbot_chatgpt_is_premium') ? chatbot_chatgpt_is_premium() : false;
                $dismissed  = function_exists('chatbot_chatgpt_get_option')
                    ? chatbot_chatgpt_get_option('chatbot_chatgpt_reporting_notice_dismissed', '0')
                    : get_option('chatbot_chatgpt_reporting_notice_dismissed', '0');
                
                if ( ! $is_premium && $dismissed !== '1' ) {
                    $reporting_url = admin_url('admin.php?page=chatbot-chatgpt&tab=reporting');
                    echo '<p class="description" style="margin-bottom: 15px;">New: Conversation summaries and proof-of-value reports are now available under <a href="' . esc_url($reporting_url) . '">Reporting</a>.</p>';
                }

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
