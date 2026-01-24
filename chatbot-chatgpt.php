<?php
/*
 * Plugin Name: Kognetiks Chatbot
 * Plugin URI:  https://github.com/kognetiks/kognetiks-chatbot
 * Description: This simple plugin adds an AI powered chatbot to your WordPress website.
 * Version:     2.4.3
 * Author:      Kognetiks.com
 * Author URI:  https://www.kognetiks.com
 * License:     GPLv3 or later
 * License URI: https://www.gnu.org/licenses/gpl-30.html
 * 
 * Copyright (c) 2023-2025 Stephen Howell
 *  
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU
 * General Public License version 3, as published by the Free Software Foundation. You may NOT assume
 * that you can use any other version of the GPL.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Kognetiks Chatbot. If not, see https://www.gnu.org/licenses/gpl-3.0.html.
 * 
 * @package chatbot-chatgpt
 * @version 2.4.3
 * @author Kognetiks.com
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( function_exists( 'chatbot_chatgpt_freemius' ) ) {
    chatbot_chatgpt_freemius()->set_basename( true, __FILE__ );
} else {
    /**
     * DO NOT REMOVE THIS IF, IT IS ESSENTIAL FOR THE
     * `function_exists` CALL ABOVE TO PROPERLY WORK.
     */
    if ( ! function_exists( 'chatbot_chatgpt_freemius' ) ) {

        function chatbot_chatgpt_freemius() {

            global $chatbot_chatgpt_freemius;

            if ( ! isset( $chatbot_chatgpt_freemius ) ) {
                // Include Freemius SDK
                require_once dirname(__FILE__) . '/vendor/freemius/start.php';

                $chatbot_chatgpt_freemius = fs_dynamic_init( array(
                    'id'                  => '18850',
                    'slug'                => 'chatbot-chatgpt',
                    'type'                => 'plugin',
                    'public_key'          => 'pk_ea667ce516b3acd5d3756a0c2530b',
                    'is_premium'          => false,
                    'has_premium_version' => true,
                    'premium_suffix'      => 'Premium',
                    'has_paid_plans'      => true,
                    'trial'               => array(
                        'days'               => 7,
                        'is_require_payment' => false,
                    ),
                    'menu' => array(
                        'slug'       => 'chatbot-chatgpt',
                        'first-path' => 'admin.php?page=chatbot-chatgpt&tab=support',
                        'network'    => true,
                    ),
                ) );
            }

            return $chatbot_chatgpt_freemius;

        }

        // Initialize Freemius
        chatbot_chatgpt_freemius();
        do_action( 'chatbot_chatgpt_freemius_loaded' );

    }

// ADD THIS TO THE END OF THE FILE - NOT HERE
// }

// Start output buffering earlier to prevent "headers already sent" issues - Ver 2.1.8
ob_start();

// Plugin version
global $chatbot_chatgpt_plugin_version;
$chatbot_chatgpt_plugin_version = '2.4.3';

// Plugin directory path
global $chatbot_chatgpt_plugin_dir_path;
$chatbot_chatgpt_plugin_dir_path = plugin_dir_path( __FILE__ );

// Plugin directory URL
global $chatbot_chatgpt_plugin_dir_url;
$chatbot_chatgpt_plugin_dir_url = plugins_url( '/', __FILE__ );

// Declare Globals
global $wpdb;

// Uniquely Identify the Visitor
global $session_id;
global $user_id;

// Assign a unique ID to the visitor and logged-in users - Ver 2.0.4
// Ver 2.3.7 - Fixed headers already sent warning by checking if headers can be sent
// Ver 2.4.0 - Added @ operator to suppress warnings as additional safety measure
function kognetiks_assign_unique_id() {
    if (!isset($_COOKIE['kognetiks_unique_id'])) {
        $unique_id = uniqid('kognetiks_', true);
        
        // Check if headers have already been sent before trying to set cookie
        // Ver 2.3.7 - Prevent "headers already sent" warning
        // Ver 2.4.0 - Use @ operator to suppress any warnings as additional safety measure
        if (!headers_sent()) {
            // Set a cookie using the built-in setcookie function
            // Using @ operator to suppress warnings in case headers are sent between check and setcookie
            @setcookie('kognetiks_unique_id', $unique_id, time() + (86400 * 30), "/", "", true, true); // HttpOnly and Secure flags set to true
        }
                
        // Ensure the cookie is set for the current request (works even if headers were already sent)
        $_COOKIE['kognetiks_unique_id'] = $unique_id;
    }
}
add_action('init', 'kognetiks_assign_unique_id', 1); // Set higher priority

// Get the unique ID of the visitor or logged-in user - Ver 2.0.4
function kognetiks_get_unique_id() {
    if (isset($_COOKIE['kognetiks_unique_id'])) {
        // error_log('[Chatbot] [chatbot-chatgpt.php] Unique ID found: ' . $_COOKIE['kognetiks_unique_id']);
        return sanitize_text_field($_COOKIE['kognetiks_unique_id']);
    }
    // error_log('[Chatbot] [chatbot-chatgpt.php] Unique ID not found');
    return null;
}

// Fetch the User ID - Updated Ver 2.0.6 - 2024 07 11
$user_id = get_current_user_id();
// Fetch the Kognetiks cookie
$session_id = kognetiks_get_unique_id();
if (empty($user_id) || $user_id == 0) {
    $user_id = $session_id;
}

ob_end_flush(); // End output buffering and send the buffer to the browser

// Include necessary files - Utilities (must be loaded before API files that use them)
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-utilities.php';                    // Ver 1.8.6

// Include necessary files - Main files
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-anthropic-api.php';         // ANT API - Ver 2.0.7
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-azure-openai-api.php';      // Azure OpenAI API - Ver 2.2.6
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-azure-api-assistant.php';   // Azure OpenAI Assistants API - Ver 2.2.6
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-deepseek-api.php';          // ChatGPT API - Ver 2.2.2
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-google-api.php';            // Google API - Ver 2.3.9
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-kognetiks-api-mc.php';      // Kognetiks - Markov Chain API - Ver 2.1.6
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-kognetiks-api-tm.php';      // Kognetiks - Transformer Model API - Ver 2.2.0
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-local-api.php';             // Local API - Ver 2.2.6
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-mistral-api.php';           // Mistral API - Ver 2.3.0
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-mistral-agent-api.php';     // Mistral Agent API - Ver 2.3.0
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-nvidia-api.php';            // NVIDIA API - Ver 2.1.8
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-openai-api-assistant.php';  // GPT Assistants - Ver 1.6.9
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-openai-api-chatgpt.php';    // ChatGPT API - Ver 1.6.9
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-openai-api-image.php';      // Image API - Ver 1.9.4
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-openai-api-kflow.php';      // Kognetiks - Flow API - Ver 1.9.5
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-openai-api-omni.php';       // ChatGPT API - Ver 2.0.2.1
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-openai-api-stt.php';        // STT API - Ver 2.0.1
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-openai-api-tts.php';    // TTS API - Ver 1.9.4
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-globals.php';                    // Globals - Ver 1.6.5
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-shortcode.php';                  // Shortcode - Ver 1.6.5

// Include necessary files - Appearance - Ver 1.8.1
require_once plugin_dir_path(__FILE__) . 'includes/appearance/chatbot-settings-appearance-body.php';
require_once plugin_dir_path(__FILE__) . 'includes/appearance/chatbot-settings-appearance-dimensions.php';
require_once plugin_dir_path(__FILE__) . 'includes/appearance/chatbot-settings-appearance-text.php';
require_once plugin_dir_path(__FILE__) . 'includes/appearance/chatbot-settings-appearance-user-css.php';

// Include necessary files - Knowledge Navigator
require_once plugin_dir_path(__FILE__) . 'includes/knowledge-navigator/chatbot-kn-acquire-controller.php';  // Knowledge Navigator Acquisition - Ver 1.9.6
require_once plugin_dir_path(__FILE__) . 'includes/knowledge-navigator/chatbot-kn-acquire-words.php';       // Knowledge Navigator Acquisition - Ver 1.9.6
require_once plugin_dir_path(__FILE__) . 'includes/knowledge-navigator/chatbot-kn-analysis.php';            // Knowledge Navigator Analysis- Ver 1.6.2
require_once plugin_dir_path(__FILE__) . 'includes/knowledge-navigator/chatbot-kn-db.php';                  // Knowledge Navigator - Database Management - Ver 1.6.3
require_once plugin_dir_path(__FILE__) . 'includes/knowledge-navigator/chatbot-kn-enhance-context.php';     // Knowledge Navigator - Enhance Context - Ver 1.6.9
require_once plugin_dir_path(__FILE__) . 'includes/knowledge-navigator/chatbot-kn-enhance-response.php';    // Knowledge Navigator - TD-IDF Response Enhancement - Ver 1.6.9
require_once plugin_dir_path(__FILE__) . 'includes/knowledge-navigator/chatbot-kn-scheduler.php';           // Knowledge Navigator - Scheduler - Ver 1.6.3
require_once plugin_dir_path(__FILE__) . 'includes/knowledge-navigator/chatbot-kn-settings.php';            // Knowledge Navigator - Settings - Ver 1.6.1

// Include necessary files - Markov Chain - Ver 2.1.9
require_once plugin_dir_path(__FILE__) . 'includes/markov-chain/chatbot-markov-chain-decode.php';           // Functions - Ver 2.1.9
require_once plugin_dir_path(__FILE__) . 'includes/markov-chain/chatbot-markov-chain-decode-beaker.php';    // Functions - Ver 2.2.0
require_once plugin_dir_path(__FILE__) . 'includes/markov-chain/chatbot-markov-chain-encode.php';           // Functions - Ver 2.1.9
require_once plugin_dir_path(__FILE__) . 'includes/markov-chain/chatbot-markov-chain-scheduler.php';        // Functions - Ver 2.1.9

// Include necessary files - Transformer Model - Ver 2.2.0
require_once plugin_dir_path(__FILE__) . 'includes/transformers/lexical-context-model.php';                 // Functions - Ver 2.2.0
require_once plugin_dir_path(__FILE__) . 'includes/transformers/sentential-context-model.php';              // Functions - Ver 2.2.0
require_once plugin_dir_path(__FILE__) . 'includes/transformers/transformer-model-scheduler.php';           // Functions - Ver 2.2.0

// Include necessary files - Settings
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-api-anthropic.php';            // Anthropic
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-api-azure-assistants.php';     // Azure Assistants - Ver 2.2.6
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-api-azure.php';                // Azure - Ver 2.2.6
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-api-deepseek.php';             // DeepSeek
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-api-google.php';               // Google - Ver 2.3.9
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-api-nvidia.php';               // NVIDIA
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-api-local.php';                // Local Server - Ver 2.2.6
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-api-mistral-agents.php';       // Mistral Agents - Ver 2.3.0
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-api-mistral.php';              // Mistral
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-api-openai-assistants.php';    // OpenAI ChatGPT
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-api-openai.php';               // OpenAI ChatGPT
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-api-test.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-appearance.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-avatar.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-buttons.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-diagnostics.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-general.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-links.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-localization.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-localize.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-markov-chain.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-menus.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-notices.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-registration-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-registration-kn.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-registration.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-reporting.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-support.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-tools.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-transformers.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings.php';

// Include necessary files - Utilities - Ver 1.9.0
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-agents-mistral.php';               // Mistral Agents - Ver 2.3.0
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-api-endpoints.php';                // API Endpoints - Ver 2.2.2
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-assistants.php';                   // Assistants Management for OpenAI - Ver 2.0.4
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-assistants-azure.php';             // Assistants Management for Azure - Ver 2.2.6
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-assisted-search.php';              // Assisted Search - Ver 2.2.7 - 2025-03-26
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-conversation-context.php';         // Conversation Context - Ver 2.3.9
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-conversation-digest.php';          // Conversation Digest - Ver 2.3.9
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-conversation-history.php';         // Ver 1.9.2
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-content-search.php';               // Functions - Ver 2.2.4
require_once plugin_dir_path(__FILE__) . 'includes/dashboard/chatbot-chatgpt-dashboard-widget.php';     // Dashboard Widget - Ver 2.2.7
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-db-management.php';                // Database Management for Reporting - Ver 1.6.3
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-deactivate.php';                   // Deactivation - Ver 1.9.9
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-download-transcript.php';          // Functions - Ver 1.9.9
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-erase-conversation.php';           // Functions - Ver 1.8.6
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-file-download.php';                // Download a file via the API - Ver 2.0.3
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-file-helper.php';                  // Functions - Ver 2.0.3
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-file-upload.php';                  // Functions - Ver 1.7.6
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-filter-out-html-tags.php';         // Functions - Ver 1.9.6
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-helper-functions.php';             // Functions - Ver 1.0.0
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-keyguard.php';                     // Functions - Ver 2.2.6
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-link-and-image-handling.php';      // Globals - Ver 1.9.1
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-models.php';                       // Functions - Ver 1.9.4
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-names.php';                        // Functions - Ver 1.9.4
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-options-helper.php';               // Functions - Ver 2.0.5
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-threads.php';                      // Ver 1.7.2.1
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-transients-file.php';              // Ver 1.9.2
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-transients.php';                   // Ver 1.7.2
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-upgrade.php';                      // Ver 1.6.7
// chatbot-utilities.php moved above to be loaded before API files - Ver 2.3.9

// Third-party libraries
require_once plugin_dir_path(__FILE__) . 'includes/utilities/parsedown.php';                            // Version 2.0.2.1

// Include necessary files - Tools - Ver 2.0.6
require_once plugin_dir_path(__FILE__) . 'tools/chatbot-capability-tester.php';
require_once plugin_dir_path(__FILE__) . 'tools/chatbot-manage-error-logs.php';
require_once plugin_dir_path(__FILE__) . 'tools/chatbot-options-exporter.php';
require_once plugin_dir_path(__FILE__) . 'tools/chatbot-shortcode-tester.php';
require_once plugin_dir_path(__FILE__) . 'tools/chatbot-shortcode-tester-tool.php';

// Include necessary files - Insights - Ver 2.3.6
// require_once plugin_dir_path(__FILE__) . 'includes/insights/insights-settings.php';
// require_once plugin_dir_path(__FILE__) . 'includes/insights/chatbot-insights.php';
// require_once plugin_dir_path(__FILE__) . 'includes/insights/languages/en_US.php';
// require_once plugin_dir_path(__FILE__) . 'includes/insights/scoring-models/sentiment-analysis.php';
// require_once plugin_dir_path(__FILE__) . 'includes/insights/utilities.php';
// require_once plugin_dir_path(__FILE__) . 'includes/insights/globals.php';

/**
 * Dynamically load Insights files when premium status is detected
 * This function can be called on-demand to load Insights files after plan upgrades
 * 
 * NOTE: Uses chatbot_chatgpt_is_premium() which follows Freemius best practices.
 * 
 * @return bool True if files were loaded, false otherwise
 * @since 2.4.2
 */
function chatbot_chatgpt_load_insights_files() {
    // Check if files are already loaded
    if ( function_exists( 'kognetiks_insights_settings_page' ) ) {
        return true;
    }
    
    // Check if user has premium access using the centralized helper function
    // This follows Freemius best practices
    if ( ! function_exists( 'chatbot_chatgpt_is_premium' ) ) {
        return false;
    }
    
    $has_premium_access = chatbot_chatgpt_is_premium();
    
    // Load files if user has premium access
    if ( $has_premium_access ) {
        $plugin_dir = plugin_dir_path(__FILE__);
        $insights_files = array(
            'includes/insights/scoring-models/sentiment-analysis.php',
            'includes/insights/automated-emails.php',
            'includes/insights/chatbot-insights.php',
            'includes/insights/globals.php',
            'includes/insights/insights-settings.php',
            'includes/insights/languages/en_US.php',
            'includes/insights/utilities.php'
        );
        
        foreach ( $insights_files as $file ) {
            $file_path = $plugin_dir . $file;
            if ( file_exists( $file_path ) ) {
                require_once $file_path;
            }
        }
        
        // Register admin_init action for period filter if not already registered
        if ( ! has_action( 'admin_init', 'chatbot_chatgpt_insights_period_filter_handler' ) ) {
            add_action('admin_init', 'chatbot_chatgpt_insights_period_filter_handler');
        }
        
        return true;
    }
    
    return false;
}

/**
 * Handle Insights period filter form submission
 * 
 * @since 2.4.2
 */
function chatbot_chatgpt_insights_period_filter_handler() {
    if (
        isset($_POST['chatbot_chatgpt_insights_action']) &&
        $_POST['chatbot_chatgpt_insights_action'] === 'period_filter' &&
        isset($_POST['chatbot_chatgpt_insights_period_filter_nonce']) &&
        wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST['chatbot_chatgpt_insights_period_filter_nonce'])),
            'chatbot_chatgpt_insights_period_filter_action'
        )
    ) {
        // Handle the period filter logic here
        $selected_period = isset($_POST['chatbot_chatgpt_insights_period_filter'])
            ? sanitize_text_field(wp_unslash($_POST['chatbot_chatgpt_insights_period_filter']))
            : 'Today';
        // Store in a transient or option, or pass as needed
        set_transient('chatbot_chatgpt_selected_period', $selected_period, 60*5);
        wp_redirect(admin_url('admin.php?page=chatbot-chatgpt&tab=insights'));
        exit;
    }
}

// Include Insights library - Premium Only (at plugin init)
// Check for premium access including trial status (trial users should have premium access)
if ( function_exists( 'chatbot_chatgpt_is_premium' ) && chatbot_chatgpt_is_premium() ) {
    chatbot_chatgpt_load_insights_files();
} elseif ( function_exists( 'chatbot_chatgpt_freemius' ) ) {
    // Fallback: use same logic as chatbot_chatgpt_is_premium()
    $fs = chatbot_chatgpt_freemius();
    if ( is_object( $fs ) ) {
        // Detect whether we are running inside the premium build
        $running_premium_build = false;
        if ( method_exists( $fs, 'is__premium_only' ) ) {
            $running_premium_build = $fs->is__premium_only();
        }
        
        // Check if paying
        if ( method_exists( $fs, 'is_paying' ) && $fs->is_paying() ) {
            chatbot_chatgpt_load_insights_files();
        }
        // Check if has active valid license
        elseif ( method_exists( $fs, 'has_active_valid_license' ) && $fs->has_active_valid_license() ) {
            chatbot_chatgpt_load_insights_files();
        }
        // Check if in trial (only grant access if running premium build)
        elseif ( method_exists( $fs, 'is_trial' ) && $fs->is_trial() && $running_premium_build ) {
            chatbot_chatgpt_load_insights_files();
        }
    }
}

// Handle plan changes and premium activation - Ver 2.4.2
// This ensures Insights files are loaded after plan upgrades
add_action( 'fs_after_account_plan_sync_chatbot-chatgpt', function() {
    // When plan is synced, try to load Insights files if premium status is detected
    chatbot_chatgpt_load_insights_files();
}, 10 );

add_action( 'fs_after_license_change_chatbot-chatgpt', function() {
    // When license changes, try to load Insights files if premium status is detected
    chatbot_chatgpt_load_insights_files();
}, 10 );

// Also hook into Freemius after_premium_version_activation if available
if ( function_exists( 'chatbot_chatgpt_freemius' ) ) {
    chatbot_chatgpt_freemius()->add_action( 'after_premium_version_activation', function() {
        chatbot_chatgpt_load_insights_files();
    } );
}

// Include necessary files - Widgets - Ver 2.1.3
require_once plugin_dir_path(__FILE__) . 'widgets/chatbot-manage-widget-logs.php';

// Log the User ID and Session ID - Ver 2.0.6 - 2024 07 11

// Check for Upgrades - Ver 1.7.7
if (!esc_attr(get_option('chatbot_chatgpt_upgraded'))) {
    chatbot_chatgpt_upgrade();
    update_option('chatbot_chatgpt_upgraded', 'Yes');
}

// Diagnotics on/off setting can be found on the Settings tab - Ver 1.5.0
$chatbot_chatgpt_diagnostics = esc_attr(get_option('chatbot_chatgpt_diagnostics', 'Off'));

global $chatbot_ai_platform_choice;
global $model;
global $voice;

// FIXME - SEE AI Platform Selection setting - Ver 2.1.8
$chatbot_ai_platform_choice = esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI'));

// DIAG - Diagnostics
// ( 'NOTICE', 'AI Platform: ' . $chatbot_ai_platform_choice);

switch ($chatbot_ai_platform_choice) {

    case 'OpenAI':

        update_option('chatbot_ai_platform_choice', 'OpenAI');

        $chatbot_chatgpt_api_enabled = 'Yes';
        update_option('chatbot_chatgpt_api_enabled', 'Yes');
    
        $chatbot_azure_api_enabled = 'No';
        update_option('chatbot_azure_api_enabled', 'No');
    
        $chatbot_nvidia_api_enabled = 'No';
        update_option('chatbot_nvidia_api_enabled', 'No');
    
        $chatbot_anthropic_api_enabled = 'No';
        update_option('chatbot_anthropic_api_enabled', 'No');

        $chatbot_deepseek_api_enabled = 'No';
        update_option('chatbot_deepseek_api_enabled', 'No');

        $chatbot_markov_chain_api_enabled = 'No';
        update_option('chatbot_markov_chain_api_enabled', 'No');
    
        $chatbot_transformer_model_api_enabled = 'No';
        update_option('chatbot_transformer_model_api_enabled', 'No');

        $chatbot_local_api_enabled = 'No';
        update_option('chatbot_local_api_enabled', 'No');

        $chatbot_mistral_api_enabled = 'No';
        update_option('chatbot_mistral_api_enabled', 'No');

        $chatbot_google_api_enabled = 'No';
        update_option('chatbot_google_api_enabled', 'No');
        
        // Model choice - Ver 1.9.4
        if (esc_attr(get_option('chatbot_chatgpt_model_choice')) === null) {
            $model = 'gpt-4-1106-preview';
            update_option('chatbot_chatgpt_model_choice', $model);
            // DIAG - Diagnostics
        } elseif (empty($model)) {
            $model = 'gpt-4-1106-preview';
        }
    
        // Voice choice - Ver 1.9.5
        if (esc_attr(get_option('chatbot_chatgpt_voice_option')) === null) {
            $voice = 'alloy';
            update_option('chatbot_chatgpt_voice_option', $voice);
            // DIAG - Diagnostics
        }

        break;

    case 'Azure OpenAI':

        update_option('chatbot_ai_platform_choice', 'Azure OpenAI');

        $chatbot_chatgpt_api_enabled = 'No';
        update_option('chatbot_chatgpt_api_enabled', 'No');

        $chatbot_azure_api_enabled = 'Yes';
        update_option('chatbot_azure_api_enabled', 'Yes');
    
        $chatbot_nvidia_api_enabled = 'No';
        update_option('chatbot_nvidia_api_enabled', 'No');
    
        $chatbot_anthropic_api_enabled = 'No';
        update_option('chatbot_anthropic_api_enabled', 'No');

        $chatbot_deepseek_api_enabled = 'No';
        update_option('chatbot_deepseek_api_enabled', 'No');

        $chatbot_markov_chain_api_enabled = 'No';
        update_option('chatbot_markov_chain_api_enabled', 'No');
    
        $chatbot_transformer_model_api_enabled = 'No';
        update_option('chatbot_transformer_model_api_enabled', 'No');

        $chatbot_local_api_enabled = 'No';
        update_option('chatbot_local_api_enabled', 'No');

        $chatbot_mistral_api_enabled = 'No';
        update_option('chatbot_mistral_api_enabled', 'No');

        $chatbot_google_api_enabled = 'No';
        update_option('chatbot_google_api_enabled', 'No');
        
        // Model choice - Ver 1.9.4
        if (esc_attr(get_option('chatbot_azure_model_choice')) === null) {
            $model = 'gpt-4-1106-preview';
            update_option('chatbot_azure_model_choice', $model);
            // DIAG - Diagnostics
        } elseif (empty($model)) {
            $model = 'gpt-4-1106-preview';
        }
    
        // FIXME - TEMPORARILY DISABLED - 2025-03-07
        // Disable Read Aloud - Ver 2.2.6
        update_option('chatbot_chatgpt_read_aloud_option', 'no');
        // Voice choice - Ver 1.9.5
        // if (esc_attr(get_option('chatbot_azure_voice_option')) === null) {
        //     $voice = 'alloy';
        //     update_option('chatbot_azure_voice_option', $voice);
        //     // DIAG - Diagnostics
        // }

        break;

    case 'NVIDIA':

        update_option('chatbot_ai_platform_choice', 'NVIDIA');
        
        $chatbot_chatgpt_api_enabled = 'No';
        update_option('chatbot_chatgpt_api_enabled', 'No');

        $chatbot_azure_api_enabled = 'No';
        update_option('chatbot_azure_api_enabled', 'No');
   
        $chatbot_nvidia_api_enabled = 'Yes';
        update_option('chatbot_nvidia_api_enabled', 'Yes');
    
        $chatbot_anthropic_api_enabled = 'No';
        update_option('chatbot_anthropic_api_enabled', 'No');

        $chatbot_deepseek_api_enabled = 'No';
        update_option('chatbot_deepseek_api_enabled', 'No');

        $chatbot_markov_chain_api_enabled = 'No';
        update_option('chatbot_markov_chain_api_enabled', 'No');

        $chatbot_transformer_model_api_enabled = 'No';
        update_option('chatbot_transformer_model_api_enabled', 'No');

        $chatbot_local_api_enabled = 'No';
        update_option('chatbot_local_api_enabled', 'No');

        $chatbot_mistral_api_enabled = 'No';
        update_option('chatbot_mistral_api_enabled', 'No');

        $chatbot_google_api_enabled = 'No';
        update_option('chatbot_google_api_enabled', 'No');

        // Model choice - Ver 2.1.8
        if (esc_attr(get_option('chatbot_nvidia_model_choice')) === null) {
            $model = 'nvidia/llama-3.1-nemotron-51b-instruct';
            update_option('chatbot_nvidia_model_choice', $model);
            // DIAG - Diagnostics
        } elseif (empty($model)) {
            $model = 'nvidia/llama-3.1-nemotron-51b-instruct';
        }

        // Disable Read Aloud - Ver 2.2.1
        update_option('chatbot_chatgpt_read_aloud_option', 'no');
        // Disable File Uploads - Ver 2.2.1
        update_option('chatbot_chatgpt_allow_file_uploads', 'No');
        // Disable MP3 Uploads - Ver 2.2.1
        update_option('chatbot_chatgpt_allow_mp3_uploads', 'No');

        break;

    case 'Anthropic':

        update_option('chatbot_ai_platform_choice', 'Anthropic');

        $chatbot_chatgpt_api_enabled = 'No';
        update_option('chatbot_chatgpt_api_enabled', 'No');

        $chatbot_azure_api_enabled = 'No';
        update_option('chatbot_azure_api_enabled', 'No');

        $chatbot_nvidia_api_enabled = 'No';
        update_option('chatbot_nvidia_api_enabled', 'No');

        $chatbot_anthropic_api_enabled = 'Yes';
        update_option('chatbot_anthropic_api_enabled', 'Yes');

        $chatbot_deepseek_api_enabled = 'No';
        update_option('chatbot_deepseek_api_enabled', 'No');

        $chatbot_markov_chain_api_enabled = 'No';
        update_option('chatbot_markov_chain_api_enabled', 'No');

        $chatbot_transformer_model_api_enabled = 'No';
        update_option('chatbot_transformer_model_api_enabled', 'No');

        $chatbot_local_api_enabled = 'No';
        update_option('chatbot_local_api_enabled', 'No');

        $chatbot_mistral_api_enabled = 'No';
        update_option('chatbot_mistral_api_enabled', 'No');

        $chatbot_google_api_enabled = 'No';
        update_option('chatbot_google_api_enabled', 'No');
        
        // Model choice - Ver 2.2.1
        if (esc_attr(get_option('chatbot_anthropic_model_choice')) === null) {
            $model = 'claude-3-5-sonnet-latest';
            update_option('chatbot_anthropic_model_choice', $model);
            // DIAG - Diagnostics
        } elseif (empty($model)) {
            $model = 'claude-3-5-sonnet-latest';
        }

        // Disable Read Aloud - Ver 2.2.1
        update_option('chatbot_chatgpt_read_aloud_option', 'no');
        // Disable File Uploads - Ver 2.2.1
        update_option('chatbot_chatgpt_allow_file_uploads', 'No');
        // Disable MP3 Uploads - Ver 2.2.1
        update_option('chatbot_chatgpt_allow_mp3_uploads', 'No');

        break;

    case 'DeepSeek':

        update_option('chatbot_ai_platform_choice', 'DeepSeek');

        $chatbot_chatgpt_api_enabled = 'No';
        update_option('chatbot_chatgpt_api_enabled', 'No');

        $chatbot_azure_api_enabled = 'No';
        update_option('chatbot_azure_api_enabled', 'No');

        $chatbot_nvidia_api_enabled = 'No';
        update_option('chatbot_nvidia_api_enabled', 'No');

        $chatbot_anthropic_api_enabled = 'No';
        update_option('chatbot_anthropic_api_enabled', 'No');

        $chatbot_deepseek_api_enabled = 'Yes';
        update_option('chatbot_deepseek_api_enabled', 'Yes');

        $chatbot_markov_chain_api_enabled = 'No';
        update_option('chatbot_markov_chain_api_enabled', 'No');

        $chatbot_transformer_model_api_enabled = 'No';
        update_option('chatbot_transformer_model_api_enabled', 'No');
        
        $chatbot_local_api_enabled = 'No';
        update_option('chatbot_local_api_enabled', 'No');

        $chatbot_mistral_api_enabled = 'No';
        update_option('chatbot_mistral_api_enabled', 'No');

        $chatbot_google_api_enabled = 'No';
        update_option('chatbot_google_api_enabled', 'No');
        
        // Model choice - Ver 2.2.1
        if (esc_attr(get_option('chatbot_deepseek_model_choice')) === null) {
            $model = 'deepseek-chat';
            update_option('chatbot_deepseek_model_choice', $model);
            // DIAG - Diagnostics
        } elseif (empty($model)) {
            $model = 'deepseek-chat';
        }

        // Disable Read Aloud - Ver 2.2.1
        update_option('chatbot_chatgpt_read_aloud_option', 'no');
        // Disable File Uploads - Ver 2.2.1
        update_option('chatbot_chatgpt_allow_file_uploads', 'No');
        // Disable MP3 Uploads - Ver 2.2.1
        update_option('chatbot_chatgpt_allow_mp3_uploads', 'No');

        break;

    case 'Mistral':

        update_option('chatbot_ai_platform_choice', 'Mistral');

        $chatbot_chatgpt_api_enabled = 'No';
        update_option('chatbot_chatgpt_api_enabled', 'No');

        $chatbot_azure_api_enabled = 'No';
        update_option('chatbot_azure_api_enabled', 'No');

        $chatbot_nvidia_api_enabled = 'No';
        update_option('chatbot_nvidia_api_enabled', 'No');

        $chatbot_anthropic_api_enabled = 'No';
        update_option('chatbot_anthropic_api_enabled', 'No');

        $chatbot_deepseek_api_enabled = 'No';
        update_option('chatbot_deepseek_api_enabled', 'No');

        $chatbot_markov_chain_api_enabled = 'No';
        update_option('chatbot_markov_chain_api_enabled', 'No');

        $chatbot_transformer_model_api_enabled = 'No';
        update_option('chatbot_transformer_model_api_enabled', 'No');

        $chatbot_local_api_enabled = 'No';
        update_option('chatbot_local_api_enabled', 'No');

        $chatbot_mistral_api_enabled = 'Yes';
        update_option('chatbot_mistral_api_enabled', 'Yes');

        $chatbot_google_api_enabled = 'No';
        update_option('chatbot_google_api_enabled', 'No');

        // Model choice - Ver 2.2.1
        if (esc_attr(get_option('chatbot_mistral_model_choice')) === null) {
            $model = 'mistral-small-latest';
            update_option('chatbot_mistral_model_choice', $model);
            // DIAG - Diagnostics
        } elseif (empty($model)) {
            $model = 'mistral-small-latest';
        }

        // Disable Read Aloud - Ver 2.2.1
        update_option('chatbot_chatgpt_read_aloud_option', 'no');
        // Disable File Uploads - Ver 2.2.1
        update_option('chatbot_chatgpt_allow_file_uploads', 'No');
        // Disable MP3 Uploads - Ver 2.2.1
        update_option('chatbot_chatgpt_allow_mp3_uploads', 'No');

        break;

    case 'Google':

        update_option('chatbot_ai_platform_choice', 'Google');

        $chatbot_chatgpt_api_enabled = 'No';
        update_option('chatbot_chatgpt_api_enabled', 'No');

        $chatbot_azure_api_enabled = 'No';
        update_option('chatbot_azure_api_enabled', 'No');

        $chatbot_nvidia_api_enabled = 'No';
        update_option('chatbot_nvidia_api_enabled', 'No');

        $chatbot_anthropic_api_enabled = 'No';
        update_option('chatbot_anthropic_api_enabled', 'No');

        $chatbot_deepseek_api_enabled = 'No';
        update_option('chatbot_deepseek_api_enabled', 'No');

        $chatbot_markov_chain_api_enabled = 'No';
        update_option('chatbot_markov_chain_api_enabled', 'No');

        $chatbot_transformer_model_api_enabled = 'No';
        update_option('chatbot_transformer_model_api_enabled', 'No');

        $chatbot_local_api_enabled = 'No';
        update_option('chatbot_local_api_enabled', 'No');

        $chatbot_mistral_api_enabled = 'No';
        update_option('chatbot_mistral_api_enabled', 'No');

        $chatbot_google_api_enabled = 'Yes';
        update_option('chatbot_google_api_enabled', 'Yes');
        
        // Model choice - Ver 2.3.9
        if (esc_attr(get_option('chatbot_google_model_choice')) === null) {
            $model = 'gemini-2.0-flash';
            update_option('chatbot_google_model_choice', $model);
            // DIAG - Diagnostics
        } elseif (empty($model)) {
            $model = 'gemini-2.0-flash';
        }

        // Disable Read Aloud - Ver 2.3.9
        update_option('chatbot_chatgpt_read_aloud_option', 'no');
        // Disable File Uploads - Ver 2.3.9
        update_option('chatbot_chatgpt_allow_file_uploads', 'No');
        // Disable MP3 Uploads - Ver 2.3.9
        update_option('chatbot_chatgpt_allow_mp3_uploads', 'No');

        break;

    case 'Markov Chain':

        update_option('chatbot_ai_platform_choice', 'Markov Chain');
        
        $chatbot_chatgpt_api_enabled = 'No';
        update_option('chatbot_chatgpt_api_enabled', 'No');

        $chatbot_azure_api_enabled = 'No';
        update_option('chatbot_azure_api_enabled', 'No');

        $chatbot_nvidia_api_enabled = 'No';
        update_option('chatbot_nvidia_api_enabled', 'No');

        $chatbot_anthropic_api_enabled = 'No';
        update_option('chatbot_anthropic_api_enabled', 'No');

        $chatbot_deepseek_api_enabled = 'No';
        update_option('chatbot_deepseek_api_enabled', 'No');

        $chatbot_markov_chain_api_enabled = 'Yes';
        update_option('chatbot_markov_chain_api_enabled', 'Yes');

        $chatbot_transformer_model_api_enabled = 'No';
        update_option('chatbot_transformer_model_api_enabled', 'No');
        
        $chatbot_local_api_enabled = 'No';
        update_option('chatbot_local_api_enabled', 'No');

        $chatbot_mistral_api_enabled = 'No';
        update_option('chatbot_mistral_api_enabled', 'No');

        $chatbot_google_api_enabled = 'No';
        update_option('chatbot_google_api_enabled', 'No');
                 	
        // Model choice - Ver 2.1.8
        if (esc_attr(get_option('chatbot_markov_chain_model_choice')) === null) {
            $model = 'markov-chain-flask';
            update_option('chatbot_markov_chain_model_choice', $model);
            // DIAG - Diagnostics
        } elseif (empty($model)) {
            $model = 'markov-chain-flask';
        }

        // Disable Read Aloud - Ver 2.2.1
        update_option('chatbot_chatgpt_read_aloud_option', 'no');
        // Disable File Uploads - Ver 2.2.1
        update_option('chatbot_chatgpt_allow_file_uploads', 'No');
        // Disable MP3 Uploads - Ver 2.2.1
        update_option('chatbot_chatgpt_allow_mp3_uploads', 'No');

        break;

    case 'Transformer':

        update_option('chatbot_ai_platform_choice', 'Transformer');

        $chatbot_chatgpt_api_enabled = 'No';
        update_option('chatbot_chatgpt_api_enabled', 'No');

        $chatbot_azure_api_enabled = 'No';
        update_option('chatbot_azure_api_enabled', 'No');
    
        $chatbot_nvidia_api_enabled = 'No';
        update_option('chatbot_nvidia_api_enabled', 'No');

        $chatbot_anthropic_api_enabled = 'No';
        update_option('chatbot_anthropic_api_enabled', 'No');

        $chatbot_deepseek_api_enabled = 'No';
        update_option('chatbot_deepseek_api_enabled', 'No');
   
        $chatbot_markov_chain_api_enabled = 'No';
        update_option('chatbot_markov_chain_api_enabled', 'No');

        $chatbot_transformer_model_api_enabled = 'Yes';
        update_option('chatbot_transformer_model_api_enabled', 'Yes');
        
        $chatbot_local_api_enabled = 'No';
        update_option('chatbot_local_api_enabled', 'No');
        
        $chatbot_mistral_api_enabled = 'No';
        update_option('chatbot_mistral_api_enabled', 'No');

        $chatbot_google_api_enabled = 'No';
        update_option('chatbot_google_api_enabled', 'No');
    
        // Model choice - Ver 2.2.0
        if (esc_attr(get_option('chatbot_transformer_model_choice')) === null) {
            $model = 'sentential-context-model';
            update_option('chatbot_transformer_model_choice', $model);
            // DIAG - Diagnostics
        } elseif (empty($model)) {
            $model = 'sentential-context-model';
        }

        // Disable Read Aloud - Ver 2.2.1
        update_option('chatbot_chatgpt_read_aloud_option', 'no');
        // Disable File Uploads - Ver 2.2.1
        update_option('chatbot_chatgpt_allow_file_uploads', 'No');
        // Disable MP3 Uploads - Ver 2.2.1
        update_option('chatbot_chatgpt_allow_mp3_uploads', 'No');

        break;

    case 'Local Server':

        // DIAG - Diagnostics

        update_option('chatbot_ai_platform_choice', 'Local Server');

        $chatbot_chatgpt_api_enabled = 'No';
        update_option('chatbot_chatgpt_api_enabled', 'No');

        $chatbot_azure_api_enabled = 'No';
        update_option('chatbot_azure_api_enabled', 'No');

        $chatbot_nvidia_api_enabled = 'No';
        update_option('chatbot_nvidia_api_enabled', 'No');

        $chatbot_anthropic_api_enabled = 'No';
        update_option('chatbot_anthropic_api_enabled', 'No');

        $chatbot_deepseek_api_enabled = 'No';
        update_option('chatbot_deepseek_api_enabled', 'No');
    
        $chatbot_markov_chain_api_enabled = 'No';
        update_option('chatbot_markov_chain_api_enabled', 'No');

        $chatbot_transformer_model_api_enabled = 'No';
        update_option('chatbot_transformer_model_api_enabled', 'No');
        
        $chatbot_local_api_enabled = 'Yes';
        update_option('chatbot_local_api_enabled', 'Yes');

        $chatbot_mistral_api_enabled = 'No';
        update_option('chatbot_mistral_api_enabled', 'No');

        $chatbot_google_api_enabled = 'No';
        update_option('chatbot_google_api_enabled', 'No');
    
        // Model choice - Ver 2.2.0
        if (esc_attr(get_option('chatbot_local_model_choice')) === null) {
            $model = 'llama3.2-3b-instruct';
            update_option('chatbot_local_model_choice', $model);
            // DIAG - Diagnostics
        } elseif (empty($model)) {
            $model = 'llama3.2-3b-instruct';
        }

        // Disable Read Aloud - Ver 2.2.1
        update_option('chatbot_chatgpt_read_aloud_option', 'no');
        // Disable File Uploads - Ver 2.2.1
        update_option('chatbot_chatgpt_allow_file_uploads', 'No');
        // Disable MP3 Uploads - Ver 2.2.1
        update_option('chatbot_chatgpt_allow_mp3_uploads', 'No');

        break;

    default:

        update_option('chatbot_ai_platform_choice', 'OpenAI');

        $chatbot_chatgpt_api_enabled = 'Yes';
        update_option('chatbot_chatgpt_api_enabled', 'Yes');

        $chatbot_azure_api_enabled = 'No';
        update_option('chatbot_azure_api_enabled', 'No');

        $chatbot_nvidia_api_enabled = 'No';
        update_option('chatbot_nvidia_api_enabled', 'No');

        $chatbot_anthropic_api_enabled = 'No';
        update_option('chatbot_anthropic_api_enabled', 'No');

        $chatbot_deepseek_api_enabled = 'No';
        update_option('chatbot_deepseek_api_enabled', 'No');

        $chatbot_markov_chain_api_enabled = 'No';
        update_option('chatbot_markov_chain_api_enabled', 'No');

        $chatbot_transformer_model_api_enabled = 'No';
        update_option('chatbot_transformer_model_api_enabled', 'No');

        $chatbot_mistral_api_enabled = 'No';
        update_option('chatbot_mistral_api_enabled', 'No');

        $chatbot_google_api_enabled = 'No';
        update_option('chatbot_google_api_enabled', 'No');

        // Model choice - Ver 1.9.4
        if (esc_attr(get_option('chatbot_chatgpt_model_choice')) === null) {
            $model = 'gpt-4-1106-preview';
            update_option('chatbot_chatgpt_model_choice', $model);
            // DIAG - Diagnostics
        } elseif (empty($model)) {
            $model = 'gpt-4-1106-preview';
        }

        // Voice choice - Ver 1.9.5
        if (esc_attr(get_option('chatbot_chatgpt_voice_option')) === null) {
            $voice = 'alloy';
            update_option('chatbot_chatgpt_voice_option', $voice);
            // DIAG - Diagnostics
        }

        break;

}

// Custom buttons on/off setting can be found on the Settings tab - Ver 1.6.5
$chatbot_chatgpt_enable_custom_buttons = esc_attr(get_option('chatbot_chatgpt_enable_custom_buttons', 'Off'));

// Allow file uploads on/off setting can be found on the Settings tab - Ver 1.7.6
global $chatbot_chatgpt_allow_file_uploads;
$chatbot_ai_platform_choice = esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI'));
if ($chatbot_ai_platform_choice == 'OpenAI') {
    $chatbot_chatgpt_allow_file_uploads = esc_attr(get_option('chatbot_chatgpt_allow_file_uploads', 'No'));
    // DIAG - Diagnostics - Ver 2.2.6
} elseif ($chatbot_ai_platform_choice == 'Azure OpenAI') {
    $chatbot_chatgpt_allow_file_uploads = esc_attr(get_option('chatbot_azure_allow_file_uploads', 'No'));
    // DIAG - Diagnostics - Ver 2.2.6
} else {
    $chatbot_chatgpt_allow_file_uploads = esc_attr(get_option('chatbot_chatgpt_allow_file_uploads', 'No'));
    // DIAG - Diagnostics - Ver 2.2.6
}

// Suppress Notices on/off setting can be found on the Settings tab - Ver 1.6.5
global $chatbot_chatgpt_suppress_notices;
$chatbot_chatgpt_suppress_notices = esc_attr(get_option('chatbot_chatgpt_suppress_notices', 'Off'));

// Suppress Attribution on/off setting can be found on the Settings tab - Ver 1.6.5
global $chatbot_chatgpt_suppress_attribution;
$chatbot_chatgpt_suppress_attribution = esc_attr(get_option('chatbot_chatgpt_suppress_attribution', 'On'));

// Suppress Learnings Message - Ver 1.7.1
global $chatbot_chatgpt_suppress_learnings;
$chatbot_chatgpt_suppress_learnings = esc_attr(get_option('chatbot_chatgpt_suppress_learnings', 'Random'));

// Context History - Ver 1.6.1
$context_history = [];

function chatbot_chatgpt_enqueue_admin_scripts() {

    global $chatbot_chatgpt_plugin_version;

    wp_enqueue_script('jquery'); // Ensure jQuery is enqueued
    wp_enqueue_script('chatbot_chatgpt_admin', plugins_url('assets/js/chatbot-chatgpt-admin.js', __FILE__), array('jquery'), $chatbot_chatgpt_plugin_version, true);

}
add_action('admin_enqueue_scripts', 'chatbot_chatgpt_enqueue_admin_scripts');

// Activation, deactivation, and uninstall functions
register_activation_hook(__FILE__, 'chatbot_chatgpt_activate');
register_activation_hook(__FILE__, 'create_chatbot_chatgpt_assistants_table');
register_activation_hook(__FILE__, 'create_chatbot_azure_assistants_table');
register_deactivation_hook(__FILE__, 'chatbot_chatgpt_deactivate');
register_uninstall_hook(__FILE__, 'chatbot_chatgpt_uninstall');
add_action('upgrader_process_complete', 'chatbot_chatgpt_upgrade_completed', 10, 2);

// Enqueue plugin scripts and styles
function chatbot_chatgpt_enqueue_scripts() {

    global $chatbot_chatgpt_plugin_dir_url;
    global $chatbot_chatgpt_plugin_version;

    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;
    global $kchat_settings;
    global $additional_instructions;
    global $model;
    global $voice;

    global $chatbot_chatgpt_display_style;

    // Enqueue the styles
    wp_enqueue_style('dashicons');
    wp_enqueue_style('chatbot-chatgpt-css', plugins_url('assets/css/chatbot-chatgpt.css', __FILE__), array(), $chatbot_chatgpt_plugin_version, 'all');

    // Now override the default styles with the custom styles - Ver 1.8.1
    chatbot_chatgpt_appearance_custom_css_settings();

    // Enqueue the scripts
    wp_enqueue_script('jquery');
    wp_enqueue_script('chatbot-chatgpt-js', plugins_url('assets/js/chatbot-chatgpt.js', __FILE__), array('jquery'), $chatbot_chatgpt_plugin_version, true);

    // Enqueue DOMPurify - Ver 1.8.1
    // https://raw.githubusercontent.com/cure53/DOMPurify/main/dist/purify.min.js
    // https://chat.openai.com/c/275770c1-fa72-404b-97c2-2dad2e8a0230
    wp_enqueue_script( 'dompurify', plugin_dir_url(__FILE__) . 'assets/js/purify.min.js', array(), $chatbot_chatgpt_plugin_version, true );

    // Localize the data for user id and page id
    $user_id = get_current_user_id();
    $page_id = get_the_id();

    // Fetch the User ID - Updated Ver 2.0.6 - 2024 07 11
    $user_id = get_current_user_id();
    // Fetch the Kognetiks cookie
    if (empty($session_id) || $session_id == 0) {
        $session_id = kognetiks_get_unique_id();
    }
    // $session_id = kognetiks_get_unique_id();
    if (empty($user_id) || $user_id == 0) {
        $user_id = $session_id;
    }

    // Check if the $kchat_settings array is empty
    if (!$kchat_settings || !is_array($kchat_settings)) {
        $kchat_settings = [];
    }

    // Initial settings
    $kchat_settings = array_merge($kchat_settings, array(
        'user_id' => $user_id,
        'page_id' => $page_id,
        'session_id' => $session_id,
        'thread_id' => $thread_id,
        'assistant_id' => $assistant_id,
        'additional_instructions' => $additional_instructions,
        'model' => $model,
        'voice' => $voice,
        // Security: Generate nonces for AJAX requests
        'chatbot_message_nonce' => wp_create_nonce('chatbot_message_nonce'),
        'chatbot_upload_nonce' => wp_create_nonce('chatbot_upload_nonce'),
        'chatbot_erase_nonce' => wp_create_nonce('chatbot_erase_nonce'),
        'chatbot_unlock_nonce' => wp_create_nonce('chatbot_unlock_nonce'),
        'chatbot_reset_nonce' => wp_create_nonce('chatbot_reset_nonce'),
        'chatbot_queue_nonce' => wp_create_nonce('chatbot_queue_nonce'),
        'chatbot_tts_nonce' => wp_create_nonce('chatbot_tts_nonce'),
        'chatbot_transcript_nonce' => wp_create_nonce('chatbot_transcript_nonce'),
        'nonce_timestamp' => time() * 1000, // JavaScript timestamp format
    ));

    // DIAG - Diagnostics - Ver 1.8.6
    
    // Set visitor and logged in user limits - Ver 2.0.1
    if (is_user_logged_in()) {
        $kchat_settings['chatbot_chatgpt_message_limit_setting'] = esc_attr(get_option('chatbot_chatgpt_user_message_limit_setting', '999'));
        $kchat_settings['chatbot_chatgpt_message_limit_period_setting'] = esc_attr(get_option('chatbot_chatgpt_user_message_limit_period_setting', 'Lifetime'));
        $kchat_settings['chatbot_chatgpt_display_message_count'] = esc_attr(get_option('chatbot_chatgpt_display_message_count', 'No'));
    } else {
        $kchat_settings['chatbot_chatgpt_message_limit_setting'] = esc_attr(get_option('chatbot_chatgpt_visitor_message_limit_setting', '999'));
        $kchat_settings['chatbot_chatgpt_message_limit_period_setting'] = esc_attr(get_option('chatbot_chatgpt_visitor_message_limit_period_setting', 'Lifetime'));
        $kchat_settings['chatbot_chatgpt_display_message_count'] = esc_attr(get_option('chatbot_chatgpt_display_message_count', 'No'));
    }

    // Localize the data for the chatbot - Ver 2.1.1.1
    $kchat_settings = array_merge($kchat_settings, array(
        'chatbot_chatgpt_display_style' => $chatbot_chatgpt_display_style,
        'chatbot_chatgpt_version' => $chatbot_chatgpt_plugin_version,
        'plugins_url' => $chatbot_chatgpt_plugin_dir_url,
        'ajax_url' => admin_url('admin-ajax.php'),
        'user_id' => $user_id,
        'page_id' => $page_id,
        'session_id' => $session_id,
        'thread_id' => $thread_id,
        'assistant_id' => $assistant_id,
        'additional_instructions' => $additional_instructions,
        'model' => $model,
        'voice' => $voice,
        'chatbot_chatgpt_timeout_setting' => esc_attr(get_option('chatbot_chatgpt_timeout_setting', '240')),
        'chatbot_chatgpt_avatar_icon_setting' => esc_attr(get_option('chatbot_chatgpt_avatar_icon_setting', '')),
        'chatbot_chatgpt_custom_avatar_icon_setting' => esc_attr(get_option('chatbot_chatgpt_custom_avatar_icon_setting', '')),
        'chatbot_chatgpt_avatar_greeting_setting' => esc_attr(get_option('chatbot_chatgpt_avatar_greeting_setting', 'Howdy!!! Great to see you today! How can I help you?')),
        'chatbot_chatgpt_force_page_reload' => esc_attr(get_option('chatbot_chatgpt_force_page_reload', 'No')),
        'chatbot_chatgpt_custom_error_message' => esc_attr(get_option('chatbot_chatgpt_custom_error_message', 'Your custom error message goes here.')),
    ));
    
    $kchat_settings_json = wp_json_encode($kchat_settings);

    // Enqueue the main chatbot script - CHANGED TO LOAD ORDER IN V2.1.2
    wp_enqueue_script('chatbot-chatgpt-js', plugins_url('assets/js/chatbot-chatgpt.js', __FILE__), array(), $chatbot_chatgpt_plugin_version, true);

    // Add the inline script to define kchat_settings before the main script runs - CHANGED THE LOAD ORDER IN VER 2.1.2
    wp_add_inline_script('chatbot-chatgpt-js', 'if (typeof kchat_settings === "undefined") { var kchat_settings = ' . $kchat_settings_json . '; } else { kchat_settings = ' . $kchat_settings_json . '; }', 'before');
    // FIXME - REMOVED IN V2.1.2
    // wp_add_inline_script('chatbot-chatgpt-local', 'if (typeof kchat_settings === "undefined") { var kchat_settings = ' . $kchat_settings_json . '; } else { kchat_settings = ' . $kchat_settings_json . '; }', 'before');

}
add_action('wp_enqueue_scripts', 'chatbot_chatgpt_enqueue_scripts');

// Enqueue MathJax with custom configuration - Ver 2.1.2
// https://docs.mathjax.org/en/latest/
// https://github.com/mathjax/MathJax/blob/master/LICENSE
// https://cdnjs.cloudflare.com/ajax/libs/mathjax/3.2.0/es5/tex-mml-chtml.js
// https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-chtml.js
function enqueue_mathjax_with_custom_config() {
    
    // Check to see if MathJax is enabled
    if (esc_attr(get_option('chatbot_chatgpt_enable_mathjax','Yes')) === 'Yes') {

        global $chatbot_chatgpt_plugin_version;
        global $chatbot_chatgpt_plugin_dir_url;

        // Add the MathJax configuration script
        $mathjax_config = "
        window.MathJax = {
            tex: {
                inlineMath: [['$', '$'], ['\\\\(', '\\\\)']],
                displayMath: [['$$', '$$'], ['\\\\[', '\\\\]']],
            },
            chtml: {
                fontURL: '" . plugin_dir_url(__FILE__) . "assets/fonts/woff-v2'
            }
        };
        ";

        // Enqueue the MathJax script
        // wp_enqueue_script( 'mathjax', plugin_dir_url(__FILE__) . 'assets/js/tex-chtml.js', array(), $chatbot_chatgpt_plugin_version, true );
        wp_enqueue_script( 'mathjax', $chatbot_chatgpt_plugin_dir_url . 'assets/js/tex-mml-chtml.js', array(), $chatbot_chatgpt_plugin_version, true );

        // Add the inline script before MathJax script
        wp_add_inline_script( 'mathjax', $mathjax_config, 'before' );

    }

}
add_action( 'wp_enqueue_scripts', 'enqueue_mathjax_with_custom_config' );

// CORS - Cross Origin Resource Sharing - CAUTION: This allows all domains to access the end point - Ver 2.1.2
// function allow_cross_domain_requests() {
//     header("Access-Control-Allow-Origin: *");
//     header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
//     header("Access-Control-Allow-Headers: X-Requested-With");
// }
// add_action('init', 'allow_cross_domain_requests');

// Settings and Deactivation Links - Ver - 1.5.0
if (!function_exists('enqueue_jquery_ui')) {
    function enqueue_jquery_ui() {
        wp_enqueue_style('wp-jquery-ui-dialog');
        wp_enqueue_script('jquery-ui-dialog');
    }
    add_action( 'admin_enqueue_scripts', 'enqueue_jquery_ui' );
}

// Schedule Cleanup of Expired Transients
if (!wp_next_scheduled('chatbot_chatgpt_cleanup_event')) {
    wp_schedule_event(time(), 'daily', 'chatbot_chatgpt_cleanup_event');
}
add_action('chatbot_chatgpt_cleanup_event', 'clean_specific_expired_transients');

// Schedule Conversation Log Cleanup - Ver 1.6.7
if (!wp_next_scheduled('chatbot_chatgpt_conversation_log_cleanup_event')) {
    wp_schedule_event(time(), 'daily', 'chatbot_chatgpt_conversation_log_cleanup_event');
}
add_action('chatbot_chatgpt_conversation_log_cleanup_event', 'chatbot_chatgpt_conversation_log_cleanup');

// Schedule the transcript file cleanup event if it's not already scheduled - Ver 1.9.9
// Schedule the cleanup event if it's not already scheduled
if (!wp_next_scheduled('chatbot_chatgpt_cleanup_transcript_files')) {
    wp_schedule_event(time(), 'hourly', 'chatbot_chatgpt_cleanup_transcript_files');
}

// Deactivate old hooks - Ver 2.0.1
if(esc_attr(get_option('chatbot_chatgpt_cleanup_old_hooks') !== 'Completed')) {
    // Deactivate old hooks - Ver 2.0.1
    wp_clear_scheduled_hook('chatbot_chatgpt_cleanup_transcripts');
    // Then update the option
    update_option('chatbot_chatgpt_cleanup_old_hooks', 'Completed');
}

// Schedule the audio file cleanup event if it's not already scheduled - Ver 1.9.9
// Schedule the cleanup event if it's not already scheduled
if (!wp_next_scheduled('chatbot_chatgpt_cleanup_audio_files')) {
    wp_schedule_event(time(), 'hourly', 'chatbot_chatgpt_cleanup_audio_files');
}

// Schedule the upload file cleanup event if it's not already scheduled - Ver 1.9.9
// Schedule the cleanup event if it's not already scheduled
if (!wp_next_scheduled('chatbot_chatgpt_cleanup_upload_files')) {
    wp_schedule_event(time(), 'hourly', 'chatbot_chatgpt_cleanup_upload_files');
}

// Schedule the download file cleanup event if it's not already scheduled - Ver 2.0.3
// Schedule the cleanup event if it's not already scheduled
if (!wp_next_scheduled('chatbot_chatgpt_cleanup_download_files')) {
    wp_schedule_event(time(), 'hourly', 'chatbot_chatgpt_cleanup_download_files');
}

// Add the Opean AI Assistant table to the database - Ver 2.0.4
// REMOVED Ver 2.2.7 and MOVED to the activation hook
// create_chatbot_chatgpt_assistants_table();

// Add the Azure Assistants table to the database - Ver 2.2.6
// REMOVED Ver 2.2.7 and MOVED to the activation hook
// create_chatbot_azure_assistants_table();

// Message Queue Management Functions
function chatbot_chatgpt_enqueue_message($user_id, $page_id, $session_id, $assistant_id, $message, $client_message_id = null) {
    $queue_key = 'chatbot_message_queue_' . wp_hash($assistant_id . '|' . $user_id . '|' . $page_id . '|' . $session_id);
    
    $queue = get_transient($queue_key);
    if (!$queue) {
        $queue = [];
    }
    
    $queue_item = [
        'message' => $message,
        'client_message_id' => $client_message_id ?: wp_generate_uuid4(),
        'timestamp' => time(),
        'user_id' => $user_id,
        'page_id' => $page_id,
        'session_id' => $session_id,
        'assistant_id' => $assistant_id
    ];
    
    $queue[] = $queue_item;
    set_transient($queue_key, $queue, 3600); // 1 hour expiry
    
    return $queue_item['client_message_id'];
}

function chatbot_chatgpt_dequeue_message($user_id, $page_id, $session_id, $assistant_id) {
    $queue_key = 'chatbot_message_queue_' . wp_hash($assistant_id . '|' . $user_id . '|' . $page_id . '|' . $session_id);
    
    $queue = get_transient($queue_key);
    if (!$queue || empty($queue)) {
        return null;
    }
    
    $message = array_shift($queue);
    
    if (empty($queue)) {
        delete_transient($queue_key);
    } else {
        set_transient($queue_key, $queue, 3600);
    }
    
    return $message;
}

function chatbot_chatgpt_get_queue_status($user_id, $page_id, $session_id, $assistant_id) {
    $queue_key = 'chatbot_message_queue_' . wp_hash($assistant_id . '|' . $user_id . '|' . $page_id . '|' . $session_id);
    $queue = get_transient($queue_key);
    
    return [
        'has_messages' => !empty($queue),
        'count' => $queue ? count($queue) : 0,
        'next_message' => $queue ? $queue[0] : null
    ];
}

function chatbot_chatgpt_process_queue($user_id, $page_id, $session_id, $assistant_id) {

    // DIAG - Diagnostics - Ver 2.3.4
    
    $queue_status = chatbot_chatgpt_get_queue_status($user_id, $page_id, $session_id, $assistant_id);
    
    // DIAG - Diagnostics - Ver 2.3.4
    
    if (!$queue_status['has_messages']) {
        return false;
    }
    
    $message_data = chatbot_chatgpt_dequeue_message($user_id, $page_id, $session_id, $assistant_id);
    if (!$message_data) {
        return false;
    }
    
    // Set conversation lock for the queued message
    $conv_lock = 'chatgpt_conv_lock_' . wp_hash($assistant_id . '|' . $user_id . '|' . $page_id . '|' . $session_id);
    set_transient($conv_lock, true, 60);
    
    // DIAG - Diagnostics - Ver 2.3.4
    
    // Process the message using the existing logic
    $response = chatbot_chatgpt_process_queued_message($message_data);
    
    // Clear conversation lock
    delete_transient($conv_lock);
    
    // DIAG - Diagnostics - Ver 2.3.4
    
    // Recursively process the next message in queue
    chatbot_chatgpt_process_queue($user_id, $page_id, $session_id, $assistant_id);
    
    return true;
    
}

// AJAX handler to get queue status
function chatbot_chatgpt_get_queue_status_ajax() {
    // Security: Verify nonce for CSRF protection
    if (!isset($_POST['chatbot_nonce']) || !wp_verify_nonce($_POST['chatbot_nonce'], 'chatbot_queue_nonce')) {
        wp_send_json_error('Security check failed. Please refresh the page and try again.', 403);
        return;
    }

    $user_id = sanitize_text_field($_POST['user_id']);
    $page_id = sanitize_text_field($_POST['page_id']);
    $session_id = sanitize_text_field($_POST['session_id']);
    $assistant_id = sanitize_text_field($_POST['assistant_id']);
    
    if (!$user_id || !$page_id || !$session_id || !$assistant_id) {
        wp_send_json_error('Missing required parameters');
        return;
    }
    
    $queue_status = chatbot_chatgpt_get_queue_status($user_id, $page_id, $session_id, $assistant_id);
    wp_send_json_success($queue_status);
}

function chatbot_chatgpt_process_queued_message($message_data) {

    // This function processes a queued message using the same logic as the main handler
    // but without the AJAX response handling
    
    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;
    global $chatbot_chatgpt_assistant_alias;
    global $kchat_settings;
    global $additional_instructions;
    global $model;
    global $voice;
    global $flow_data;

    $api_key = '';
    $message = $message_data['message'];
    $user_id = $message_data['user_id'];
    $page_id = $message_data['page_id'];
    $session_id = $message_data['session_id'];
    $assistant_id = $message_data['assistant_id'];
    $client_message_id = $message_data['client_message_id'];

    $chatbot_ai_platform_choice = esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI'));

    // Get API key and model based on platform choice
    switch ($chatbot_ai_platform_choice) {
        case 'OpenAI':
            $api_key = esc_attr(get_option('chatbot_chatgpt_api_key'));
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            $model = esc_attr(get_option('chatbot_chatgpt_model_choice', 'gpt-4-1106-preview'));
            break;
        case 'Azure OpenAI':
            $api_key = esc_attr(get_option('chatbot_azure_api_key'));
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            $model = esc_attr(get_option('chatbot_azure_model_choice', 'gpt-4-1106-preview'));
            break;
        case 'NVIDIA':
            $api_key = esc_attr(get_option('chatbot_nvidia_api_key'));
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            $model = esc_attr(get_option('chatbot_nvidia_model_choice', 'nvidia/llama-3.1-nemotron-51b-instruct'));
            break;
        case 'Anthropic':
            $api_key = esc_attr(get_option('chatbot_anthropic_api_key'));
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            $model = esc_attr(get_option('chatbot_anthropic_model_choice', 'claude-3-5-sonnet-latest'));
            break;
        case 'DeepSeek':
            $api_key = esc_attr(get_option('chatbot_deepseek_api_key'));
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            $model = esc_attr(get_option('chatbot_deepseek_model_choice', 'deepseek-chat'));
            break;
        case 'Google':
            $api_key = esc_attr(get_option('chatbot_google_api_key'));
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            $model = esc_attr(get_option('chatbot_google_model_choice', 'gemini-2.0-flash'));
            break;
        case 'Markov Chain':
            $api_key = esc_attr(get_option('chatbot_markov_chain_api_key', 'NOT REQUIRED'));
            $model = esc_attr(get_option('chatbot_markov_chain_model_choice', 'markov-chain-flask'));
            break;
        case 'Transformer':
            $api_key = esc_attr(get_option('chatbot_transformer_api_key', 'NOT REQUIRED'));
            $model = esc_attr(get_option('chatbot_transformer_model_choice', 'lexical-context-model'));
            break;
        case 'Local Server':
            $api_key = esc_attr(get_option('chatbot_local_api_key', 'NOT REQUIRED'));
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            $model = esc_attr(get_option('chatbot_local_model_choice', 'llama3.2-3b-instruct'));
            break;
        case 'Mistral':
            $api_key = esc_attr(get_option('chatbot_mistral_api_key'));
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            $model = esc_attr(get_option('chatbot_mistral_model_choice', 'mistral-small-latest'));
            break;
        default:
            $api_key = esc_attr(get_option('chatbot_chatgpt_api_key'));
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            $model = esc_attr(get_option('chatbot_chatgpt_model_choice', 'gpt-3.5-turbo'));
            break;
    }

    // Retrieve settings from transients (same as main handler) - Ver 2.3.6
    $kchat_settings['chatbot_chatgpt_display_style'] = get_chatbot_chatgpt_transients( 'display_style', $user_id, $page_id, $session_id);
    $kchat_settings['chatbot_chatgpt_assistant_alias'] = get_chatbot_chatgpt_transients( 'assistant_alias', $user_id, $page_id, $session_id);
    $kchat_settings['assistant_id'] = get_chatbot_chatgpt_transients( 'assistant_id', $user_id, $page_id, $session_id);
    $kchat_settings['thread_id'] = get_chatbot_chatgpt_transients( 'thread_id', $user_id, $page_id, $session_id);
    $kchat_settings['chatbot_chatgpt_model'] = get_chatbot_chatgpt_transients( 'model', $user_id, $page_id, $session_id);
    $kchat_settings['model'] = $kchat_settings['chatbot_chatgpt_model'] ?: $model;
    $kchat_settings['chatbot_chatgpt_voice_option'] = get_chatbot_chatgpt_transients( 'voice', $user_id, $page_id, $session_id);
    $kchat_settings['additional_instructions'] = get_chatbot_chatgpt_transients( 'additional_instructions', $user_id, $page_id, $session_id);
    
    $voice = $kchat_settings['chatbot_chatgpt_voice_option'];
    $model = $kchat_settings['model'] ?: $model;
    $additional_instructions = $kchat_settings['additional_instructions'];
    $chatbot_chatgpt_assistant_alias = $kchat_settings['chatbot_chatgpt_assistant_alias'];
    
    // Override assistant_id from transient if available - Ver 2.3.6
    if (!empty($kchat_settings['assistant_id'])) {
        $assistant_id = $kchat_settings['assistant_id'];
    }

    // Get thread information
    $thread_id = get_chatbot_chatgpt_threads($user_id, $session_id, $page_id, $assistant_id);
    
    // Log the message
    append_message_to_conversation_log($session_id, $user_id, $page_id, 'Visitor', $thread_id, $assistant_id, null, $message);

    // Determine whether to use assistant_id or regular ChatGPT API (same logic as main handler) - Ver 2.3.6
    // Which Assistant ID to use - Ver 1.7.2
    if ($chatbot_chatgpt_assistant_alias == 'original') {

        $use_assistant_id = 'No';
        // DIAG - Diagnostics - Ver 2.3.6

    } elseif ($chatbot_chatgpt_assistant_alias == 'primary') {

        $assistant_id = esc_attr(get_option('assistant_id'));
        $use_assistant_id = 'Yes';

        // DIAG - Diagnostics - Ver 2.3.6
        
        // Check if the GPT Assistant ID is blank, null, or "Please provide the GPT Assistant ID."
        if (empty($assistant_id) || $assistant_id == "Please provide the Assistant Id.") {
        
            // Primary assistant_id not set
            $chatbot_chatgpt_assistant_alias = 'original';
            $use_assistant_id = 'No';
        
            // DIAG - Diagnostics - Ver 2.3.6
        }

    } elseif ($chatbot_chatgpt_assistant_alias == 'alternate') {

        $assistant_id = esc_attr(get_option('chatbot_chatgpt_assistant_id_alternate'));
        $use_assistant_id = 'Yes';

        // DIAG - Diagnostics - Ver 2.3.6

        // Check if the GPT Assistant ID is blank, null, or "Please provide the GPT Assistant ID."
        if (empty($assistant_id) || $assistant_id == "Please provide the Assistant Id.") {

            /// Alternate assistant_id not set
            $chatbot_chatgpt_assistant_alias = 'original';
            $use_assistant_id = 'No';

            // DIAG - Diagnostics - Ver 2.3.6
        
        }

    } elseif (str_starts_with($assistant_id, 'asst_')) {

        $chatbot_chatgpt_assistant_alias = $assistant_id; // Belt & Suspenders
        $use_assistant_id = 'Yes';

        // DIAG - Diagnostics - Ver 2.3.6

    } elseif (str_starts_with($assistant_id, 'ag:')) {

        $chatbot_chatgpt_assistant_alias = $assistant_id; // Belt & Suspenders
        $use_assistant_id = 'Yes';

        // DIAG - Diagnostics - Ver 2.3.6

    } elseif (str_starts_with($assistant_id, 'websearch')) {

        $chatbot_chatgpt_assistant_alias = $assistant_id; // Belt & Suspenders
        $use_assistant_id = 'Yes';

        // DIAG - Diagnostics - Ver 2.3.6

    } else {

        // Reference GPT Assistant IDs directly - Ver 1.7.3
        // Check both $chatbot_chatgpt_assistant_alias and $assistant_id - Ver 2.3.6
        if (!empty($chatbot_chatgpt_assistant_alias) && (str_starts_with($chatbot_chatgpt_assistant_alias, 'asst_') || str_starts_with($chatbot_chatgpt_assistant_alias, 'ag:') || str_starts_with($chatbot_chatgpt_assistant_alias, 'websearch'))) {
            
            // DIAG - Diagnostics - Ver 2.3.6

            // Override the $assistant_id with the GPT Assistant ID
            $assistant_id = $chatbot_chatgpt_assistant_alias;
            $use_assistant_id = 'Yes';

            // DIAG - Diagnostics - Ver 2.3.6

        } elseif (!empty($assistant_id) && (str_starts_with($assistant_id, 'asst_') || str_starts_with($assistant_id, 'ag:') || str_starts_with($assistant_id, 'websearch'))) {
            
            // Check $assistant_id directly if $chatbot_chatgpt_assistant_alias is empty - Ver 2.3.6
            // DIAG - Diagnostics - Ver 2.3.6

            // Set the alias to match the assistant_id
            $chatbot_chatgpt_assistant_alias = $assistant_id;
            $use_assistant_id = 'Yes';

            // DIAG - Diagnostics - Ver 2.3.6

        } else {

            // DIAG - Diagnostics - Ver 2.3.6

            // Override the $use_assistant_id and set it to 'No'
            $use_assistant_id = 'No';
            
            // DIAG - Diagnostics - Ver 2.3.6

        }

    }

    // Process the message based on platform and use_assistant_id - Ver 2.3.6
    // DIAG - Diagnostics - Ver 2.3.6
    
    // Check if we should use assistant_id or regular API
    if ($use_assistant_id == 'Yes' && $chatbot_ai_platform_choice == 'OpenAI') {
        // DIAG - Diagnostics - Ver 2.3.6
        $response = chatbot_chatgpt_custom_gpt_call_api($api_key, $message, $assistant_id, $thread_id, $session_id, $user_id, $page_id, $client_message_id);
    } elseif ($use_assistant_id == 'Yes' && $chatbot_ai_platform_choice == 'Azure OpenAI') {
        // DIAG - Diagnostics - Ver 2.3.6
        $response = chatbot_azure_custom_gpt_call_api($api_key, $message, $assistant_id, $thread_id, $session_id, $user_id, $page_id, $client_message_id);
    } elseif ($use_assistant_id == 'Yes' && $chatbot_ai_platform_choice == 'Mistral') {
        // DIAG - Diagnostics - Ver 2.3.6
        $response = chatbot_mistral_agent_call_api($api_key, $message, $assistant_id, $thread_id, $session_id, $user_id, $page_id, $client_message_id);
    } else {
        // Use regular API calls (not assistant_id) - Ver 2.3.6
        switch ($chatbot_ai_platform_choice) {
            case 'OpenAI':
                // Determine which OpenAI API to call based on model
                if (str_starts_with($model, 'gpt-4o')) {
                    $response = chatbot_chatgpt_call_omni($api_key, $message, $user_id, $page_id, $session_id, $assistant_id, $client_message_id);
                } elseif (str_starts_with($model, 'gpt')) {
                    $response = chatbot_chatgpt_call_api($api_key, $message, $user_id, $page_id, $session_id, $assistant_id, $client_message_id);
                } elseif (str_starts_with($model, 'dall')) {
                    $response = chatbot_chatgpt_call_image_api($api_key, $message, $user_id, $page_id, $session_id, $assistant_id, $client_message_id);
                } elseif (str_starts_with($model, 'tts')) {
                    $response = chatbot_chatgpt_call_tts_api($api_key, $message, $voice, $user_id, $page_id, $session_id, $assistant_id, $client_message_id);
                } elseif (str_starts_with($model, 'whisper')) {
                    $response = chatbot_chatgpt_call_stt_api($api_key, $message);
                } else {
                    $response = chatbot_chatgpt_call_api($api_key, $message, $user_id, $page_id, $session_id, $assistant_id, $client_message_id);
                }
                break;
                
            case 'Azure OpenAI':
                $response = chatbot_call_azure_openai_api($api_key, $message);
                break;
                
            case 'NVIDIA':
                $response = chatbot_nvidia_call_api($api_key, $message);
                break;
                
            case 'Anthropic':
                $response = chatbot_call_anthropic_api($api_key, $message);
                break;
                
            case 'DeepSeek':
                $response = chatbot_call_deepseek_api($api_key, $message);
                break;

            case 'Google':
                $response = chatbot_call_google_api($api_key, $message);
                break;
                
            case 'Markov Chain':
                $response = chatbot_chatgpt_call_markov_chain_api($message);
                break;
                
            case 'Transformer':
                $response = chatbot_chatgpt_call_transformer_model_api($message);
                break;
                
            case 'Local Server':
                $response = chatbot_chatgpt_call_local_model_api($message);
                break;
                
            case 'Mistral':
                // If use_assistant_id is No, use regular Mistral API
                $response = chatbot_chatgpt_call_mistral_api($api_key, $message);
                break;
                
            default:
                $response = chatbot_chatgpt_call_api($api_key, $message, $user_id, $page_id, $session_id, $assistant_id, $client_message_id);
                break;
        }
    }

    // Log the response
    append_message_to_conversation_log($session_id, $user_id, $page_id, 'Chatbot', $thread_id, $assistant_id, null, $response);

    return $response;
}

function chatbot_chatgpt_process_single_message($message_data) {
    // This function contains the core message processing logic
    // It's extracted from the main send_message function
    
    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;
    global $chatbot_chatgpt_assistant_alias;
    global $kchat_settings;
    global $additional_instructions;
    global $model;
    global $voice;
    global $flow_data;

    $api_key  = '';
    $message  = $message_data['message'];
    $user_id  = $message_data['user_id'];
    $page_id  = $message_data['page_id'];
    $session_id        = $message_data['session_id'];
    $assistant_id      = $message_data['assistant_id'];
    $client_message_id = $message_data['client_message_id'];

    $chatbot_ai_platform_choice = esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI'));

    // If your core pipeline expects a nonce in $_POST, we can safely provide one here:
    if (!isset($_POST['chatbot_nonce'])) {
        $_POST['chatbot_nonce'] = wp_create_nonce('chatbot_message_nonce');
    }

    // IMPORTANT: pass the full array, not just $message
    $response = chatbot_chatgpt_send_message($message_data);

    return $response;
}

// Handle Ajax requests
function chatbot_chatgpt_send_message() {

    // Security: Verify nonce for CSRF protection
    $nonce_present = isset($_POST['chatbot_nonce']);
    $nonce_valid = false;
    
    if ($nonce_present) {
        $nonce_valid = wp_verify_nonce($_POST['chatbot_nonce'], 'chatbot_message_nonce');
    }
    
    if (!$nonce_present || !$nonce_valid) {
        // Log the security failure for debugging with more detail
        $nonce_status = !$nonce_present ? 'missing' : 'invalid';
        $post_data_sanitized = array();
        foreach ($_POST as $key => $value) {
            // Sanitize POST data for logging (exclude sensitive data)
            if ($key !== 'chatbot_nonce') {
                $post_data_sanitized[$key] = is_string($value) ? substr($value, 0, 100) : $value;
            } else {
                $post_data_sanitized[$key] = $nonce_present ? (strlen($value) > 0 ? '[present]' : '[empty]') : '[missing]';
            }
        }
        
        prod_trace('ERROR', 'Chatbot Security Check Failed - Nonce ' . $nonce_status . '. POST data: ' . print_r($post_data_sanitized, true));
        
        // Enhanced error response with nonce refresh suggestion
        wp_send_json_error(array(
            'message' => 'Security check failed. Please refresh the page and try again.',
            'code' => 'nonce_failed',
            'suggestion' => 'refresh_nonce',
            'nonce_status' => $nonce_status
        ), 403);
        return;
    }

    // Security: Get current user and verify authorization
    $current_user = wp_get_current_user();
    $current_user_id = $current_user->ID;
    
    // For anonymous users, we need to verify they own the session
    if ($current_user_id === 0) {
        // Anonymous user - verify session ownership through session_id
        if (!isset($_POST['session_id'])) {
            wp_send_json_error('Session ID required for anonymous users.', 403);
            return;
        }
        $session_id = sanitize_text_field($_POST['session_id']);
        
        // Verify the session belongs to the current request
        if (!verify_session_ownership($session_id)) {
            // Log the session validation failure for debugging
            prod_trace( 'ERROR', 'Chatbot Session Validation Failed - Session ID: ' . $session_id . ', Length: ' . strlen($session_id));
            wp_send_json_error('Unauthorized access to conversation.', 403);
            return;
        }
        
        // For anonymous users, user_id should be 0 (session_id is used in transient key via get_chatbot_chatgpt_transients)
        $user_id = 0;
        
        // Rate limiting for unauthenticated users to prevent API abuse
        $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $rate_limit_key = 'chatbot_rate_limit_' . (function_exists('wp_fast_hash') ? wp_fast_hash($client_ip) : hash('sha256', $client_ip));
        
        // Check rate limit (max 10 requests per minute for unauthenticated users)
        $current_count = get_transient($rate_limit_key) ?: 0;
        if ($current_count >= 10) {
            wp_send_json_error('Rate limit exceeded. Please wait before sending another message.', 429);
            return;
        }
        
        // Increment rate limit counter
        set_transient($rate_limit_key, $current_count + 1, 60); // 60 seconds
    } else {
        // Logged-in user - use their actual user ID
        // Get session_id for logged-in users (needed for transient keys) - Ver 2.3.6
        if (!isset($_POST['session_id'])) {
            $session_id = kognetiks_get_unique_id();
        } else {
            $session_id = sanitize_text_field($_POST['session_id']);
        }
    }

    // Global variables
    // Fixed Ver 2.3.6: Set global variables AFTER determining the correct values
    // This ensures the global $user_id is set correctly for logged-in users
    global $session_id;
    global $user_id;
    global $page_id;
    
    // Set the global $user_id to the correct value (don't let global declaration overwrite it)
    if ($current_user_id === 0) {
        $user_id = 0; // Anonymous user
    } else {
        $user_id = $current_user_id; // Logged-in user - preserve their WordPress user ID
    }
    global $thread_id;
    global $assistant_id;
    global $chatbot_chatgpt_assistant_alias;
    global $kchat_settings;
    global $additional_instructions;
    global $model;
    global $voice;

    global $flow_data;

    $api_key = '';

    $chatbot_ai_platform_choice = esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI'));

    // DIAG - Diagnostics - Ver 2.2.1

    switch ($chatbot_ai_platform_choice) {

        case 'OpenAI':

            $api_key = esc_attr(get_option('chatbot_chatgpt_api_key'));
            // Decrypt the API key - Ver 2.2.6
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            $model = esc_attr(get_option('chatbot_chatgpt_model_choice', 'gpt-4-1106-preview'));
            $kchat_settings['chatbot_chatgpt_model'] = $model;
            $kchat_settings['model'] = $model;
            // DIAG - Diagnostics - Ver 2.1.8
            break;

        case 'Azure OpenAI':

            $api_key = esc_attr(get_option('chatbot_azure_api_key'));
            // Decrypt the API key - Ver 2.2.6
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            $model = esc_attr(get_option('chatbot_azure_model_choice', 'gpt-4-1106-preview'));
            $kchat_settings['chatbot_chatgpt_model'] = $model;
            $kchat_settings['model'] = $model;
            // DIAG - Diagnostics - Ver 2.2.6
            break;

        case 'NVIDIA':

            $api_key = esc_attr(get_option('chatbot_nvidia_api_key'));
            $model = esc_attr(get_option('chatbot_nvidia_model_choice', 'nvidia/llama-3.1-nemotron-51b-instruct'));
            // Decrypt the API key - Ver 2.2.6
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            $kchat_settings['chatbot_chatgpt_model'] = $model;
            $kchat_settings['model'] = $model;
            // DIAG - Diagnostics - Ver 2.1.8
            break;

        case 'Anthropic':

            $api_key = esc_attr(get_option('chatbot_anthropic_api_key'));
            // Decrypt the API key - Ver 2.2.6
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            $model = esc_attr(get_option('chatbot_anthropic_model_choice', 'claude-3-5-sonnet-latest'));
            $kchat_settings['chatbot_chatgpt_model'] = $model;
            $kchat_settings['model'] = $model;
            // DIAG - Diagnostics - Ver 2.1.8
            break;

        case 'DeepSeek':

            $api_key = esc_attr(get_option('chatbot_deepseek_api_key'));
            // Decrypt the API key - Ver 2.2.6
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            $model = esc_attr(get_option('chatbot_deepseek_model_choice', 'deepseek-chat'));
            $kchat_settings['chatbot_chatgpt_model'] = $model;
            $kchat_settings['model'] = $model;
            // DIAG - Diagnostics - Ver 2.2.2
            break;
        
        case 'Google':

            $api_key = esc_attr(get_option('chatbot_google_api_key'));
            // Decrypt the API key - Ver 2.3.9
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            $model = esc_attr(get_option('chatbot_google_model_choice', 'gemini-2.0-flash'));
            $kchat_settings['chatbot_chatgpt_model'] = $model;
            $kchat_settings['model'] = $model;
            // DIAG - Diagnostics - Ver 2.3.9
            break;

        case 'Markov Chain':

            $api_key = esc_attr(get_option('chatbot_markov_chain_api_key', 'NOT REQUIRED'));
            $model = esc_attr(get_option('chatbot_markov_chain_model_choice', 'markov-chain-flask'));
            $kchat_settings['chatbot_chatgpt_model'] = $model;
            $kchat_settings['model'] = $model;
            // DIAG - Diagnostics - Ver 2.1.8
            break;

        case 'Transformer':

            $api_key = esc_attr(get_option('chatbot_transformer_api_key', 'NOT REQUIRED'));
            $model = esc_attr(get_option('chatbot_transformer_model_choice', 'lexical-context-model'));
            $kchat_settings['chatbot_chatgpt_model'] = $model;
            $kchat_settings['model'] = $model;
            // DIAG - Diagnostics - Ver 2.1.8
            break;

        case 'Local Server':

            $api_key = esc_attr(get_option('chatbot_local_api_key', 'NOT REQUIRED'));
            // Decrypt the API key - Ver 2.2.6
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            $model = esc_attr(get_option('chatbot_local_model_choice', 'llama3.2-3b-instruct'));
            $kchat_settings['chatbot_chatgpt_model'] = $model;
            $kchat_settings['model'] = $model;
            // DIAG - Diagnostics - Ver 2.2.6
            break;

        case 'Mistral':

            $api_key = esc_attr(get_option('chatbot_mistral_api_key'));
            // Decrypt the API key - Ver 2.2.6
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            $model = esc_attr(get_option('chatbot_mistral_model_choice', 'mistral-small-latest'));
            $kchat_settings['chatbot_chatgpt_model'] = $model;
            $kchat_settings['model'] = $model;
            // DIAG - Diagnostics - Ver 2.2.2
            break;

        default:

            $api_key = esc_attr(get_option('chatbot_chatgpt_api_key'));
            // Decrypt the API key - Ver 2.2.6
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            $model = esc_attr(get_option('chatbot_chatgpt_model_choice', 'gpt-3.5-turbo'));
            $kchat_settings['chatbot_chatgpt_model'] = $model;
            $kchat_settings['model'] = $model;
            // DIAG - Diagnostics - Ver 2.1.8
            break;

    }

    // DIAG - Diagnostics - Ver 2.1.8

    // Send only clean text via the API - Ver 2.3.7
    // Validate message exists in POST before sanitizing
    if (!isset($_POST['message']) || empty($_POST['message'])) {
        prod_trace('ERROR', 'Chatbot: Message is missing from POST data. POST keys: ' . implode(', ', array_keys($_POST)));
        global $chatbot_chatgpt_fixed_literal_messages;
        $default_message = 'Error: Message is required. Please enter a message and try again.';
        $error_message = isset($chatbot_chatgpt_fixed_literal_messages[15]) 
            ? $chatbot_chatgpt_fixed_literal_messages[15] 
            : $default_message;
        wp_send_json_error($error_message);
        return;
    }
    
    $message = sanitize_text_field($_POST['message']);
    
    // Additional validation - ensure message is not empty after sanitization
    if (empty(trim($message))) {
        prod_trace('ERROR', 'Chatbot: Message is empty after sanitization. Original POST message length: ' . strlen($_POST['message']));
        global $chatbot_chatgpt_fixed_literal_messages;
        $default_message = 'Error: Message cannot be empty. Please enter a message and try again.';
        $error_message = isset($chatbot_chatgpt_fixed_literal_messages[15]) 
            ? $chatbot_chatgpt_fixed_literal_messages[15] 
            : $default_message;
        wp_send_json_error($error_message);
        return;
    }
    
    // Log the message being processed for debugging - Ver 2.3.7
    // DIAG - Diagnostics - Uncomment for debugging
    // prod_trace('NOTICE', 'Chatbot: Processing message - Length: ' . strlen($message) . ', First 50 chars: ' . substr($message, 0, 50));
    
    // Get client message ID if provided
    $client_message_id = isset($_POST['client_message_id']) ? sanitize_text_field($_POST['client_message_id']) : null;

    // Check for missing API key or message
    // if (!$api_key || !$message) {
    if ( !$message ) {
        // DIAG - Diagnostics
        global $chatbot_chatgpt_fixed_literal_messages;
        // Define a default fallback message
        $default_message = 'Error: Invalid API key or Message. Please check the plugin settings.';
        $error_message = isset($chatbot_chatgpt_fixed_literal_messages[15]) 
            ? $chatbot_chatgpt_fixed_literal_messages[15] 
            : $default_message;
        // Send error response
        wp_send_json_error($error_message);
        return;
    }
    
    // Removed in Ver 1.8.6 - 2024 02 15
    // $thread_id = '';
    // $assistant_id = '';
    // $user_id = '';
    // $page_id = '';
    
    // Check the transient for the Assistant ID - Ver 1.7.2
    // Security: Get page_id from POST (this is safe as it's just identifying the page)
    $page_id = isset($_POST['page_id']) ? sanitize_text_field($_POST['page_id']) : '';
    
    // For logged-in users, session_id should come from the secure session
    // For anonymous users, session_id was already validated above
    if ($current_user_id === 0) {
        // session_id was already validated and set above for anonymous users
        // No need to overwrite it
    } else {
        // For logged-in users, get session_id from POST but validate it
        if (isset($_POST['session_id'])) {
            $session_id = sanitize_text_field($_POST['session_id']);
        } else {
            // Fallback to generating a new session ID
            $session_id = kognetiks_get_unique_id();
        }
    }

    // Additional security: Verify the conversation belongs to this user
    if (!verify_conversation_ownership($user_id, $page_id)) {
        // Log the conversation ownership validation failure for debugging
        prod_trace( 'ERROR', 'Chatbot Conversation Ownership Validation Failed - User ID: ' . $user_id . ', Page ID: ' . $page_id);
        wp_send_json_error('Unauthorized access to conversation.', 403);
        return;
    }

    // DIAG - Diagnostics - Ver 1.8.6

    $kchat_settings['chatbot_chatgpt_display_style'] = get_chatbot_chatgpt_transients( 'display_style', $user_id, $page_id, $session_id);
    $kchat_settings['chatbot_chatgpt_assistant_alias'] = get_chatbot_chatgpt_transients( 'assistant_alias', $user_id, $page_id, $session_id);
    $kchat_settings['assistant_id'] = get_chatbot_chatgpt_transients( 'assistant_id', $user_id, $page_id, $session_id);
    $kchat_settings['thread_id'] = get_chatbot_chatgpt_transients( 'thread_id', $user_id, $page_id, $session_id);
    $kchat_settings['chatbot_chatgpt_model'] = get_chatbot_chatgpt_transients( 'model', $user_id, $page_id, $session_id);
    $kchat_settings['model'] = $kchat_settings['chatbot_chatgpt_model'];
    $kchat_settings['chatbot_chatgpt_voice_option'] = get_chatbot_chatgpt_transients( 'voice', $user_id, $page_id, $session_id);
    $kchat_settings['additional_instructions'] = get_chatbot_chatgpt_transients( 'additional_instructions', $user_id, $page_id, $session_id);
    $voice = $kchat_settings['chatbot_chatgpt_voice_option'];
    $model = $kchat_settings['chatbot_chatgpt_model'];

    // FIXME - TESTING - Ver 2.1.8
    
    $additional_instructions = $kchat_settings['additional_instructions'];
    $chatbot_chatgpt_assistant_alias = $kchat_settings['chatbot_chatgpt_assistant_alias'];

    // Get the thread information - Ver 2.0.7
    $thread_id = get_chatbot_chatgpt_threads($user_id, $session_id, $page_id, $assistant_id);
    $kchat_settings['thread_id'] = $thread_id;
    // $kchat_settings = array_merge($kchat_settings, get_chatbot_chatgpt_threads($user_id, $page_id));

    $assistant_id = isset($kchat_settings['assistant_id']) ? $kchat_settings['assistant_id'] : '';
    $thread_Id = isset($kchat_settings['thread_id']) ? $kchat_settings['thread_id'] : '';
    $model = isset($kchat_settings['chatbot_chatgpt_model']) ? $kchat_settings['chatbot_chatgpt_model'] : '';

    // FIXME - TESTING - Ver 2.1.8

    $voice = isset($kchat_settings['chatbot_chatgpt_voice_option']) ? $kchat_settings['chatbot_chatgpt_voice_option'] : '';
    
    // Check if there's already a conversation lock (active processing)
    $conv_lock = 'chatgpt_conv_lock_' . wp_hash($assistant_id . '|' . $user_id . '|' . $page_id . '|' . $session_id);
    $is_processing = get_transient($conv_lock);
    
    // Debug logging for lock check
    // DIAG - Diagnostics - Ver 2.3.4
    
    // For visitors, add additional lock validation to prevent stuck locks
    if ($is_processing && $current_user_id === 0) {
        // Check if the lock is older than 2 minutes (120 seconds) - likely stuck
        $lock_timeout_key = '_transient_timeout_' . $conv_lock;
        $lock_timeout = get_option($lock_timeout_key);
        
        if ($lock_timeout && (time() - ($lock_timeout - 60)) > 120) {
            // Lock is older than 2 minutes, clear it
            delete_transient($conv_lock);
            $is_processing = false;
            // DIAG - Diagnostics - Ver 2.3.6
        }
    }
    
    if ($is_processing) {
        // If already processing, enqueue the message
        $enqueued_id = chatbot_chatgpt_enqueue_message($user_id, $page_id, $session_id, $assistant_id, $message, $client_message_id);
        
        // Return queue status
        global $chatbot_chatgpt_fixed_literal_messages;
        $default_message = 'Message queued. Processing...';
        $queued_message = isset($chatbot_chatgpt_fixed_literal_messages[20]) 
            ? $chatbot_chatgpt_fixed_literal_messages[20] 
            : $default_message;
            
        wp_send_json_success([
            'queued' => true,
            'client_message_id' => $enqueued_id,
            'message' => $queued_message
        ]);
    }
    
    // Set conversation lock with shorter timeout for visitors to prevent stuck locks
    $lock_timeout = ($current_user_id === 0) ? 30 : 60; // 30 seconds for visitors, 60 for logged-in users
    set_transient($conv_lock, true, $lock_timeout);
    
    // Debug logging for lock setting
    // DIAG - Diagnostics - Ver 2.3.4

    // DIAG - Diagnostics - Ver 1.8.6
    // DIAG - Diagnostics - Ver 2.0.9
    foreach ($kchat_settings as $key => $value) {
    }

    // Assistants
    // $chatbot_chatgpt_assistant_alias == 'original'; // Default
    // $chatbot_chatgpt_assistant_alias == 'primary';
    // $chatbot_chatgpt_assistant_alias == 'alternate';
    // $chatbot_chatgpt_assistant_alias == 'asst_xxxxxxxxxxxxxxxxxxxxxxxx'; // GPT Assistant Id
    // $chatbot_chatgpt_assistant_alias == 'ag:xxxxxxxxxxxxxxxxxxxxxxxx'; // MistralAgent Id
    // $chatbot_chatgpt_assistant_alias == 'websearch'; // Mistral Websearch Id
  
    // Which Assistant ID to use - Ver 1.7.2
    if ($chatbot_chatgpt_assistant_alias == 'original') {

        // DIAG - Diagnostics - Ver 2.3.6

        // DIAG - Diagnostics - Ver 2.3.6

        $use_assistant_id = 'No';
        // DIAG - Diagnostics - Ver 2.0.5

    } elseif ($chatbot_chatgpt_assistant_alias == 'primary') {

        // DIAG - Diagnostics - Ver 2.3.6

        $assistant_id = esc_attr(get_option('assistant_id'));
        // $additional_instructions = esc_attr(get_option('chatbot_chatgpt_assistant_instructions', '')); // REMOVED VER 2.0.9
        $use_assistant_id = 'Yes';

        // DIAG - Diagnostics - Ver 2.0.5
        
        // Check if the GPT Assistant ID is blank, null, or "Please provide the GPT Assistant ID."
        if (empty($assistant_id) || $assistant_id == "Please provide the Assistant Id.") {
        
            // Primary assistant_id not set
            $chatbot_chatgpt_assistant_alias = 'original';
            $use_assistant_id = 'No';
        
            // DIAG - Diagnostics - Ver 2.0.5

        }

    } elseif ($chatbot_chatgpt_assistant_alias == 'alternate') {

        // DIAG - Diagnostics - Ver 2.3.6

        $assistant_id = esc_attr(get_option('chatbot_chatgpt_assistant_id_alternate'));
        // $additional_instructions = esc_attr(get_option('chatbot_chatgpt_assistant_instructions_alternate', '')); // REMOVED VER 2.0.9
        $use_assistant_id = 'Yes';

        // DIAG - Diagnostics - Ver 2.0.5

        // Check if the GPT Assistant ID is blank, null, or "Please provide the GPT Assistant ID."
        if (empty($assistant_id) || $assistant_id == "Please provide the Assistant Id.") {

            /// Alternate assistant_id not set
            $chatbot_chatgpt_assistant_alias = 'original';
            $use_assistant_id = 'No';

            // DIAG - Diagnostics - Ver 2.0.5
        
        }

    } elseif (str_starts_with($assistant_id, 'asst_')) {

        // DIAG - Diagnostics - Ver 2.3.6

        $chatbot_chatgpt_assistant_alias = $assistant_id; // Belt & Suspenders
        $use_assistant_id = 'Yes';

        // DIAG - Diagnostics - Ver 2.0.5

    } elseif (str_starts_with($assistant_id, 'ag:')) {

        // DIAG - Diagnostics - Ver 2.3.6

        $chatbot_chatgpt_assistant_alias = $assistant_id; // Belt & Suspenders
        $use_assistant_id = 'Yes';

        // DIAG - Diagnostics - Ver 2.0.5

    } elseif (str_starts_with($assistant_id, 'websearch')) {

        // DIAG - Diagnostics - Ver 2.3.6

        $chatbot_chatgpt_assistant_alias = $assistant_id; // Belt & Suspenders
        $use_assistant_id = 'Yes';

        // DIAG - Diagnostics - Ver 3.2.1

    } else {

        // Reference GPT Assistant IDs directly - Ver 1.7.3
        // Check both $chatbot_chatgpt_assistant_alias and $assistant_id - Ver 2.3.6
        if (!empty($chatbot_chatgpt_assistant_alias) && (str_starts_with($chatbot_chatgpt_assistant_alias, 'asst_') || str_starts_with($chatbot_chatgpt_assistant_alias, 'ag:') || str_starts_with($chatbot_chatgpt_assistant_alias, 'websearch'))) {

            // DIAG - Diagnostics - 2.0.5

            // Override the $assistant_id with the GPT Assistant ID
            $assistant_id = $chatbot_chatgpt_assistant_alias;
            $use_assistant_id = 'Yes';

            // DIAG - Diagnostics - Ver 2.0.5

        } elseif (!empty($assistant_id) && (str_starts_with($assistant_id, 'asst_') || str_starts_with($assistant_id, 'ag:') || str_starts_with($assistant_id, 'websearch'))) {
            
            // Check $assistant_id directly if $chatbot_chatgpt_assistant_alias is empty - Ver 2.3.6
            // DIAG - Diagnostics - Ver 2.3.6

            // Set the alias to match the assistant_id
            $chatbot_chatgpt_assistant_alias = $assistant_id;
            $use_assistant_id = 'Yes';

            // DIAG - Diagnostics - Ver 2.3.6

        } else {

            // DIAG - Diagnostics - Ver 2.0.5

            // Override the $use_assistant_id and set it to 'No'
            $use_assistant_id = 'No';
            
            // DIAG - Diagnostics - Ver 1.8.1

        }

    }

    // Get any additional instructions - Ver 2.0.9
    $additional_instructions = get_chatbot_chatgpt_transients( 'additional_instructions', $user_id, $page_id, $session_id);

    // Decide whether to use Flow, Assistant or Original ChatGPT
    // DIAG - Diagnostics - Ver 2.3.4
    
    if ($model == 'flow'){
        
        // DIAG - Diagnostics - Ver 2.1.1.1

        // Reload the model - BELT & SUSPENDERS
        $kchat_settings['model'] = $model;

        // Get the step from the transient
        $kflow_step = get_chatbot_chatgpt_transients( 'kflow_step', null, null, $session_id);
        if (empty($kflow_step)) {
            $kflow_step = 0; // FIXME - Set to 1 or to zero?
        }

        // $thread_id
        $thread_id = '[answer=' . $kflow_step + 1 . ']';
        
        // Add +1 to $kchat_settings['next_step']
        $kflow_step = $kflow_step + 1;

        // Set the next step
        set_chatbot_chatgpt_transients( 'kflow_step', $kflow_step, null, null, $session_id);

        // DIAG - Diagnostics
        $thread_id = get_chatbot_chatgpt_threads($user_id, $session_id, $page_id, $assistant_id);
        append_message_to_conversation_log($session_id, $user_id, $page_id, 'Visitor', $thread_id, $assistant_id, null, $message);

        // BELT & SUSPENDERS
        $thread_id = '';

        // Send message to ChatGPT API - Ver 1.6.7
        $response = chatbot_chatgpt_call_flow_api($api_key, $message);

        wp_send_json_success($response);

    } elseif ($use_assistant_id == 'Yes') {

        // DIAG - Diagnostics - Ver 2.3.4
        // DIAG - Diagnostics - Ver 2.1.1.1


        // DIAG - Diagnostics
        $thread_id = get_chatbot_chatgpt_threads($user_id, $session_id, $page_id, $assistant_id);
        append_message_to_conversation_log($session_id, $user_id, $page_id, 'Visitor', $thread_id, $assistant_id, null, $message);

        // DIAG - Diagnostics - Ver 2.0.9

        // Send message to Custom GPT API - Ver 1.6.7
        $chatbot_ai_platform_choice = esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI'));

        // Route based on chatbot_ai_platform_choice setting - Ver 2.3.6
        if ($chatbot_ai_platform_choice == 'OpenAI') {
            // Send message to OpenAI Assitant API - Ver 1.6.7
            // DIAG - Diagnostics
            $response = chatbot_chatgpt_custom_gpt_call_api($api_key, $message, $assistant_id, $thread_id, $session_id, $user_id, $page_id, $client_message_id);
        } elseif ($chatbot_ai_platform_choice == 'Azure OpenAI') {
            // Send message to Azure Assistant API - Ver 2.2.6
            // DIAG - Diagnostics
            $response = chatbot_azure_custom_gpt_call_api($api_key, $message, $assistant_id, $thread_id, $session_id, $user_id, $page_id, $client_message_id);
        } elseif ($chatbot_ai_platform_choice == 'Mistral') {
            // Send message to Mistral Assistant API - Ver 2.2.6
            // DIAG - Diagnostics
            $response = chatbot_mistral_agent_call_api($api_key, $message, $assistant_id, $thread_id, $session_id, $user_id, $page_id, $client_message_id);
        } else {
            return 'ERROR: Invalid AI Platform';
        }

        // Replace " ." at the end of $response with "."
        $response = str_replace(" .", ".", $response);

        // Use TF-IDF to enhance response
        $chatbot_chatgpt_suppress_learnings = esc_attr(get_option('chatbot_chatgpt_suppress_learnings', 'Random'));
        if ( $chatbot_chatgpt_suppress_learnings != 'None') {
            $response = $response . '<br><br>' . chatbot_chatgpt_enhance_with_tfidf($message);
        }

        // DIAG - Diagnostics
        $thread_id = get_chatbot_chatgpt_threads($user_id, $session_id, $page_id, $assistant_id);
        append_message_to_conversation_log($session_id, $user_id, $page_id, 'Chatbot', $thread_id, $assistant_id, null, $response);

        // Clean (erase) the output buffer - Ver 1.6.8
        // Check if output buffering is active before attempting to clean it
        if (ob_get_level() > 0) {
            ob_clean();
            // DIAG - Diagnostics
        } else {
            // Optionally start output buffering if needed for your application
            // ob_start();
            // DIAG - Diagnostics
        }

        if (str_starts_with($response, 'Error:') || str_starts_with($response, 'Failed:')) {

            global $chatbot_chatgpt_fixed_literal_messages;
            // Define a default fallback message
            $default_message = 'Oops! Something went wrong on our end. Please try again later!';
            $error_message = isset($chatbot_chatgpt_fixed_literal_messages[0]) 
                ? $chatbot_chatgpt_fixed_literal_messages[0] 
                : $default_message;
        
            // Send error response
            wp_send_json_error($error_message);

        } else {

            // Process response for links and images
            $response = chatbot_chatgpt_check_for_links_and_images($response);
        
            // Append any extra message if configured
            $extra_message = esc_attr(get_option('chatbot_chatgpt_extra_message', ''));
            $response = chatbot_chatgpt_append_extra_message($response, $extra_message);
        
            // Clear conversation lock and process queue BEFORE sending response
            delete_transient($conv_lock);
            // DIAG - Diagnostics - Ver 2.3.4
            chatbot_chatgpt_process_queue($user_id, $page_id, $session_id, $assistant_id);
            
            // Send success response
            wp_send_json_success($response);

        }

    } else {

        // DIAG - Diagnostics - Ver 2.3.4

        // Belt & Suspenders - Ver 2.1.5.1
        if (!isset($kchat_settings['model'])) {
            $kchat_settings['model'] = $model;
        };

        // FIXME - TESTING - Ver 2.1.8

        // if (str_starts_with($model,'dall')) {
        // } else {
        // }

        $thread_id = get_chatbot_chatgpt_threads($user_id, $session_id, $page_id, $assistant_id);
        append_message_to_conversation_log($session_id, $user_id, $page_id, 'Visitor', $thread_id, $assistant_id, null, $message);
        
        // If $model starts with 'gpt' then the chatbot_chatgpt_call_api or 'dall' then chatbot_chatgpt_call_image_api
        // TRY NOT TO FETCH MODEL AGAIN
        // $model = esc_attr(get_option('chatbot_chatgpt_model_choice', 'gpt-3.5-turbo'));
        $model = isset($kchat_settings['model']) ? $kchat_settings['model'] : null;
        $voice = isset($kchat_settings['voice']) ? $kchat_settings['voice'] : null;

        // FIXME - TESTING - Ver 2.1.8

        $chatbot_ai_platform_choice = esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI'));

        // DIAG - Diagnostics - Ver 2.2.6

        switch ($chatbot_ai_platform_choice) {

            case 'OpenAI':

                switch ($model) {

                    case str_starts_with($model, 'gpt-4o'):

                        // The string 'gpt-4o' is found in $model
                        // Reload the model - BELT & SUSPENDERS
                        $kchat_settings['model'] = $model;
                        // DIAG - Diagnostics - Ver 2.1.8
                        // Send message to ChatGPT API - Ver 1.6.7
                        $response = chatbot_chatgpt_call_omni($api_key, $message, $user_id, $page_id, $session_id, $assistant_id, $client_message_id);

                        break;

                    case str_starts_with($model, 'gpt'):
                        
                        // Reload the model - BELT & SUSPENDERS
                        $kchat_settings['model'] = $model;
                        // DIAG - Diagnostics - Ver 2.1.8
                        // Send message to ChatGPT API - Ver 1.6.7
                        $response = chatbot_chatgpt_call_api($api_key, $message, $user_id, $page_id, $session_id, $assistant_id, $client_message_id);

                        break;

                    case str_starts_with($model, 'dall'):

                        // Reload the model - BELT & SUSPENDERS
                        $kchat_settings['model'] = $model;
                        // DIAG - Diagnostics - Ver 2.1.8
                        // Send message to Image API - Ver 1.9.4
                        $response = chatbot_chatgpt_call_image_api($api_key, $message, $user_id, $page_id, $session_id, $assistant_id, $client_message_id);

                        break;

                    case str_starts_with($model, 'tts'):

                        // Reload the model - BELT & SUSPENDERS
                        $kchat_settings['model'] = $model;
                        $kchat_settings['voice'] = $voice;
                        // DIAG - Diagnostics - Ver 2.1.8
                        // Send message to TTS API - Text-to-speech - Ver 1.9.5
                        $response = chatbot_chatgpt_call_tts_api($api_key, $message, $voice, $user_id, $page_id, $session_id, $assistant_id, $client_message_id);

                        break;

                    case str_starts_with($model, 'whisper'):
                        
                        // Reload the model - BELT & SUSPENDERS
                        $kchat_settings['model'] = $model;
                        // DIAG - Diagnostics - Ver 2.1.8
                        // Send message to STT API - Speech-to-text - Ver 1.9.6
                        $response = chatbot_chatgpt_call_stt_api($api_key, $message);

                        break;

                }

                break;

            case 'Azure OpenAI':

                $kchat_settings['model'] = $model;
                // DIAG - Diagnostics - Ver 2.2.6
                // Send message to Azure OpenAI API - Ver 2.2.6
                $response = chatbot_call_azure_openai_api($api_key, $message);

                break;

            case 'NVIDIA':

                $kchat_settings['model'] = $model;
                // DIAG - Diagnostics - Ver 2.1.8
                // Send message to NVIDIA API - Ver 2.1.8
                $response = chatbot_nvidia_call_api($api_key, $message);

                break;

            case 'Anthropic':
            
                $kchat_settings['model'] = $model;
                // DIAG - Diagnostics - Ver 2.1.8
                // Send message to Claude API - Ver 2.1.8
                $response = chatbot_call_anthropic_api($api_key, $message);

                break;

            case 'DeepSeek':

                $kchat_settings['model'] = $model;
                // DIAG - Diagnostics - Ver 2.2.2
                // Send message to DeepSeek API - Ver 2.2.2
                $response = chatbot_call_deepseek_api($api_key, $message);

                break;

            case 'Google':

                $kchat_settings['model'] = $model;
                // DIAG - Diagnostics - Ver 2.3.9
                // Send message to Google API - Ver 2.3.9
                $response = chatbot_call_google_api($api_key, $message);

                break;

            case 'Mistral':

                $kchat_settings['model'] = $model;
                // DIAG - Diagnostics - Ver 2.3.0
                // Send message to Mistral API - Ver 2.3.0
                $response = chatbot_chatgpt_call_mistral_api($api_key, $message);

                break;

            case 'Markov Chain':

                $kchat_settings['model'] = $model;
                // DIAG - Diagnostics - Ver 2.1.8
                // Send message to Markov API - Ver 1.9.7
                $response = chatbot_chatgpt_call_markov_chain_api($message);

                break;

            case 'Transformer':

                $kchat_settings['model'] = $model;
                // DIAG - Diagnostics - Ver 2.2.0
                // Send message to Transformer Model API - Ver 2.2.0
                $response = chatbot_chatgpt_call_transformer_model_api($message);

                break;

            case 'Local Server':

                $kchat_settings['model'] = $model;
                // DIAG - Diagnostics - Ver 2.2.6
                // Send message to Local Model API - Ver 2.2.6
                $response = chatbot_chatgpt_call_local_model_api($message);

                break;

            default:

                $kchat_settings['model'] = $model;
                // DIAG - Diagnostics - Ver 2.2.6
                // Send message to ChatGPT API - Ver 1.6.7
                $response = chatbot_chatgpt_call_api($api_key, $message);

        }
        
        // DIAG - Diagnostics

        // Defensive programming - Ver 2.2.9
        if (is_array($response)) {
            if (isset($response['response'])) {
                // Likely nested from a mistake — pull the actual content out
                $response = $response['response'];
            } else {
                // Fallback: flatten it all into a string just to be safe
                $response = print_r($response, true);
            }
        }
        
        // Use TF-IDF to enhance response
        $chatbot_chatgpt_suppress_learnings = esc_attr(get_option('chatbot_chatgpt_suppress_learnings', 'Random'));
        if ( $chatbot_chatgpt_suppress_learnings != 'None') {
            $response = $response . '<br><br>' . chatbot_chatgpt_enhance_with_tfidf($message);
        }
        // DIAG - Diagnostics

        // DIAG - Diagnostics
        $thread_id = get_chatbot_chatgpt_threads($user_id, $session_id, $page_id, $assistant_id);
        append_message_to_conversation_log($session_id, $user_id, $page_id, 'Chatbot', $thread_id, $assistant_id, null, $response);

        // DIAG - Diagnostics
        $response = chatbot_chatgpt_check_for_links_and_images($response);

        // DIAG - Diagnostics - Ver 2.0.5

        // FIXME - Append extra message - Ver 2.1.1.1.1
        // Danger Will Robinson! Danger!
        $extra_message = esc_attr(get_option('chatbot_chatgpt_extra_message', ''));
        $response = chatbot_chatgpt_append_extra_message($response, $extra_message);

        // DIAG - Diagnostics - Ver 2.1.8

        // Clear conversation lock and process queue BEFORE sending response
        delete_transient($conv_lock);
        // DIAG - Diagnostics - Ver 2.3.4
        chatbot_chatgpt_process_queue($user_id, $page_id, $session_id, $assistant_id);
        
        // Return response
        wp_send_json_success($response);

    }

    // DIAG - Diagnostics
    global $chatbot_chatgpt_fixed_literal_messages;       
    // Define a default fallback message
    $default_message = 'Oops! I fell through the cracks!';
    $error_message = isset($chatbot_chatgpt_fixed_literal_messages[1]) 
        ? $chatbot_chatgpt_fixed_literal_messages[1] 
        : $default_message;

    // Send error response
    wp_send_json_error($error_message);
    
    // Clear conversation lock on error
    delete_transient($conv_lock);

}

// Handle nonce refresh requests - Ver 2.3.6
function chatbot_chatgpt_refresh_nonce() {
    // Generate fresh nonces
    $nonces = array(
        'chatbot_message_nonce' => wp_create_nonce('chatbot_message_nonce'),
        'chatbot_upload_nonce' => wp_create_nonce('chatbot_upload_nonce'),
        'chatbot_erase_nonce' => wp_create_nonce('chatbot_erase_nonce'),
        'chatbot_unlock_nonce' => wp_create_nonce('chatbot_unlock_nonce'),
        'chatbot_reset_nonce' => wp_create_nonce('chatbot_reset_nonce'),
        'chatbot_queue_nonce' => wp_create_nonce('chatbot_queue_nonce'),
        'chatbot_tts_nonce' => wp_create_nonce('chatbot_tts_nonce'),
        'chatbot_transcript_nonce' => wp_create_nonce('chatbot_transcript_nonce'),
    );
    
    wp_send_json_success($nonces);
}

// Function to clear stuck visitor locks - Ver 2.3.6
function chatbot_chatgpt_clear_stuck_visitor_locks() {
    global $wpdb;
    
    // Only run for visitors (not logged in users)
    if (is_user_logged_in()) {
        return;
    }
    
    // Clear locks older than 2 minutes
    $expired_time = time() - 120; // 2 minutes ago
    
    $lock_patterns = [
        '_transient_chatgpt_conv_lock_%',
        '_transient_timeout_chatgpt_conv_lock_%',
    ];
    
    foreach ($lock_patterns as $pattern) {
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d",
            $pattern,
            $expired_time
        ));
    }
}

// Hook to clear stuck locks on init for visitors
add_action('init', 'chatbot_chatgpt_clear_stuck_visitor_locks', 5);

// Add admin menu for visitor lock clearing tool - Ver 2.3.6
function chatbot_chatgpt_add_visitor_lock_tool_menu() {
    add_submenu_page(
        'chatbot-chatgpt',
        'Clear Visitor Locks',
        'Clear Visitor Locks',
        'manage_options',
        'chatbot-clear-visitor-locks',
        'chatbot_chatgpt_visitor_lock_tool_page'
    );
}
add_action('admin_menu', 'chatbot_chatgpt_add_visitor_lock_tool_menu');

// Admin page for visitor lock clearing tool
function chatbot_chatgpt_visitor_lock_tool_page() {
    if (isset($_POST['clear_locks']) && wp_verify_nonce($_POST['_wpnonce'], 'clear_visitor_locks')) {
        chatbot_chatgpt_clear_stuck_visitor_locks();
        echo '<div class="notice notice-success"><p>Visitor locks cleared successfully!</p></div>';
    }
    
    echo '<div class="wrap">';
    echo '<h1>Clear Visitor Locks</h1>';
    echo '<p>This tool clears stuck conversation locks that may be preventing visitors from using the chatbot.</p>';
    echo '<p><strong>Use this if visitors are getting "system is busy processing requests" messages.</strong></p>';
    echo '<form method="post">';
    wp_nonce_field('clear_visitor_locks');
    echo '<p><input type="submit" name="clear_locks" class="button-primary" value="Clear All Visitor Locks" onclick="return confirm(\'Are you sure you want to clear all visitor locks?\')"></p>';
    echo '</form>';
    echo '</div>';
}

// Add action to send messages - Ver 1.0.0
add_action('wp_ajax_chatbot_chatgpt_send_message', 'chatbot_chatgpt_send_message');
add_action('wp_ajax_nopriv_chatbot_chatgpt_send_message', 'chatbot_chatgpt_send_message');

// Add action to refresh nonce - Ver 2.3.6
add_action('wp_ajax_chatbot_chatgpt_refresh_nonce', 'chatbot_chatgpt_refresh_nonce');
add_action('wp_ajax_nopriv_chatbot_chatgpt_refresh_nonce', 'chatbot_chatgpt_refresh_nonce');

// Add action to get queue status
add_action('wp_ajax_chatbot_chatgpt_get_queue_status', 'chatbot_chatgpt_get_queue_status_ajax');
add_action('wp_ajax_nopriv_chatbot_chatgpt_get_queue_status', 'chatbot_chatgpt_get_queue_status_ajax');

// Add action to upload files - Ver 1.7.6 (Security: Authentication required)
add_action('wp_ajax_chatbot_chatgpt_upload_files', 'chatbot_chatgpt_upload_files');

// Add action to upload mp3 files - Ver 1.7.6 (Security: Authentication required)
add_action('wp_ajax_chatbot_chatgpt_upload_mp3', 'chatbot_chatgpt_upload_mp3');

// Add action to erase conversation - Ver 1.8.6 (Security: Authentication required)
add_action('wp_ajax_chatbot_chatgpt_erase_conversation', 'chatbot_chatgpt_erase_conversation_handler');
add_action('wp_ajax_nopriv_chatbot_chatgpt_erase_conversation', 'chatbot_chatgpt_erase_conversation_handler');

// Add action to unlock conversation - Ver 2.3.0 (Security: Authentication required)
add_action('wp_ajax_chatbot_chatgpt_unlock_conversation', 'chatbot_chatgpt_unlock_conversation_handler');

// Add action to reset all locks (Security: Authentication required)
add_action('wp_ajax_chatbot_chatgpt_reset_all_locks', 'chatbot_chatgpt_reset_all_locks_handler');

// Add action to reset cache and locks (Security: Authentication required) - Ver 2.3.6
add_action('wp_ajax_chatbot_chatgpt_reset_cache_locks', 'chatbot_chatgpt_reset_cache_locks_handler');

// Add action for text-to-speech (Security: Authentication required)
add_action('wp_ajax_chatbot_chatgpt_read_aloud', 'chatbot_chatgpt_read_aloud');

// Add action for transcript download (Security: Authentication required)
add_action('wp_ajax_chatbot_chatgpt_download_transcript', 'chatbot_chatgpt_download_transcript');

// Settings and Deactivation - Ver 1.5.0
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'chatbot_chatgpt_plugin_action_links');

// Unlock conversation handler - Ver 2.3.0
function chatbot_chatgpt_unlock_conversation_handler() {
    
    // Security: Check if user has permission to manage options (admin capability)
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions to unlock conversation.', 403);
        return;
    }

    // Security: Verify nonce for CSRF protection
    if (!isset($_POST['chatbot_nonce']) || !wp_verify_nonce($_POST['chatbot_nonce'], 'chatbot_unlock_nonce')) {
        wp_send_json_error('Security check failed. Please refresh the page and try again.', 403);
        return;
    }
    
    // Get parameters from POST
    $user_id = isset($_POST['user_id']) ? sanitize_text_field($_POST['user_id']) : '';
    $page_id = isset($_POST['page_id']) ? sanitize_text_field($_POST['page_id']) : '';
    $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
    $assistant_id = isset($_POST['assistant_id']) ? sanitize_text_field($_POST['assistant_id']) : '';
    
    if ($user_id && $page_id && $session_id && $assistant_id) {
        // Clear the conversation lock
        $conv_lock = 'chatgpt_conv_lock_' . wp_hash($assistant_id . '|' . $user_id . '|' . $page_id . '|' . $session_id);
        $lock_exists = get_transient($conv_lock);
        $deleted_lock = delete_transient($conv_lock);
        
        // Clear the message queue
        $queue_key = 'chatbot_message_queue_' . wp_hash($assistant_id . '|' . $user_id . '|' . $page_id . '|' . $session_id);
        $queue_exists = get_transient($queue_key);
        $deleted_queue = delete_transient($queue_key);
        
        // DIAG - Diagnostics - Ver 2.3.4
        
        // Try to clear all possible lock variations
        $possible_locks = [
            'chatgpt_conv_lock_' . wp_hash($assistant_id . '|' . $user_id . '|' . $page_id . '|' . $session_id),
            'chatgpt_conv_lock_' . wp_hash($user_id . '|' . $page_id . '|' . $session_id),
            'chatgpt_conv_lock_' . wp_hash($session_id),
            'chatgpt_conv_lock_' . $session_id,
            'chatgpt_conv_lock_' . $user_id . '_' . $page_id . '_' . $session_id,
            // Additional variations for visitor locks
            'chatgpt_conv_lock_' . wp_hash($assistant_id . '|' . $session_id . '|' . $page_id),
            'chatgpt_conv_lock_' . wp_hash($session_id . '|' . $page_id),
        ];
        
        foreach ($possible_locks as $lock_key) {
            if (get_transient($lock_key)) {
                delete_transient($lock_key);
                // DIAG - Diagnostics - Ver 2.3.4
            }
        }
        
        wp_send_json_success('Conversation unlocked');
    } else {
        // DIAG - Diagnostics - Ver 2.3.4
        wp_send_json_error('Missing parameters');
    }
}

// Global lock reset handler - Ver 2.3.0
function chatbot_chatgpt_reset_all_locks_handler() {
    
    // Security: Check if user has permission to manage options (admin capability)
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions to reset locks.', 403);
        return;
    }

    // Security: Verify nonce for CSRF protection
    if (!isset($_POST['chatbot_nonce']) || !wp_verify_nonce($_POST['chatbot_nonce'], 'chatbot_reset_nonce')) {
        wp_send_json_error('Security check failed. Please refresh the page and try again.', 403);
        return;
    }
    
    // Get parameters from POST
    $user_id = isset($_POST['user_id']) ? sanitize_text_field($_POST['user_id']) : '';
    $page_id = isset($_POST['page_id']) ? sanitize_text_field($_POST['page_id']) : '';
    $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
    $assistant_id = isset($_POST['assistant_id']) ? sanitize_text_field($_POST['assistant_id']) : '';
    
    $cleared_count = 0;
    
    if ($user_id && $page_id && $session_id && $assistant_id) {
        // Clear all possible lock variations for this conversation
        $possible_locks = [
            'chatgpt_conv_lock_' . wp_hash($assistant_id . '|' . $user_id . '|' . $page_id . '|' . $session_id),
            'chatgpt_conv_lock_' . wp_hash($user_id . '|' . $page_id . '|' . $session_id),
            'chatgpt_conv_lock_' . wp_hash($session_id),
            'chatgpt_conv_lock_' . $session_id,
            'chatgpt_conv_lock_' . $user_id . '_' . $page_id . '_' . $session_id,
            'chatgpt_run_lock_' . wp_hash($assistant_id . '|' . $user_id . '|' . $page_id . '|' . $session_id),
            'chatgpt_run_lock_' . wp_hash($user_id . '|' . $page_id . '|' . $session_id),
            'chatgpt_run_lock_' . wp_hash($session_id),
            'chatgpt_run_lock_' . $session_id,
            'chatgpt_run_lock_' . $user_id . '_' . $page_id . '_' . $session_id,
        ];
        
        foreach ($possible_locks as $lock_key) {
            if (get_transient($lock_key)) {
                delete_transient($lock_key);
                $cleared_count++;
                // DIAG - Diagnostics - Ver 2.3.4
            }
        }
        
        // Clear all possible queue variations
        $possible_queues = [
            'chatbot_message_queue_' . wp_hash($assistant_id . '|' . $user_id . '|' . $page_id . '|' . $session_id),
            'chatbot_message_queue_' . wp_hash($user_id . '|' . $page_id . '|' . $session_id),
            'chatbot_message_queue_' . wp_hash($session_id),
            'chatbot_message_queue_' . $session_id,
            'chatbot_message_queue_' . $user_id . '_' . $page_id . '_' . $session_id,
        ];
        
        foreach ($possible_queues as $queue_key) {
            if (get_transient($queue_key)) {
                delete_transient($queue_key);
                $cleared_count++;
                // DIAG - Diagnostics - Ver 2.3.4
            }
        }
        
        // DIAG - Diagnostics - Ver 2.3.4
        wp_send_json_success('Reset completed - Cleared ' . $cleared_count . ' locks/queues');

    } else {

        // DIAG - Diagnostics - Ver 2.3.4
        wp_send_json_error('Missing parameters');
    
    }

}

// Reset Cache and Locks Handler - Ver 2.3.6
function chatbot_chatgpt_reset_cache_locks_handler() {
    
    // Security: Check if user has permission to manage options (admin capability)
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions to reset cache and locks.', 403);
        return;
    }

    // Security: Verify nonce for CSRF protection
    if (!isset($_POST['chatbot_nonce']) || !wp_verify_nonce($_POST['chatbot_nonce'], 'chatbot_reset_cache_locks')) {
        wp_send_json_error('Security check failed. Please refresh the page and try again.', 403);
        return;
    }
    
    global $wpdb;
    $cleared_count = 0;
    
    try {
        // Clear all conversation locks
        $lock_patterns = [
            '_transient_chatgpt_conv_lock_%',
            '_transient_timeout_chatgpt_conv_lock_%',
            '_transient_chatgpt_run_lock_%',
            '_transient_timeout_chatgpt_run_lock_%',
            '_transient_chatbot_message_queue_%',
            '_transient_timeout_chatbot_message_queue_%',
            '_transient_chatbot_chatgpt_%',
            '_transient_timeout_chatbot_chatgpt_%'
        ];
        
        foreach ($lock_patterns as $pattern) {
            $result = $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $pattern
            ));
            $cleared_count += $result;
        }
        
        // Clear expired transients
        $expired_result = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d",
            '_transient_timeout_%',
            time()
        ));
        $cleared_count += $expired_result;
        
        // Clear WordPress object cache if available
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        // Clear any file-based caches
        $cache_dirs = [
            WP_CONTENT_DIR . '/cache/',
            WP_CONTENT_DIR . '/uploads/chatbot-chatgpt/',
            plugin_dir_path(__FILE__) . 'cache/',
            plugin_dir_path(__FILE__) . 'audio/',
            plugin_dir_path(__FILE__) . 'downloads/',
            plugin_dir_path(__FILE__) . 'transcripts/'
        ];
        
        foreach ($cache_dirs as $cache_dir) {
            if (is_dir($cache_dir)) {
                $files = glob($cache_dir . '*');
                foreach ($files as $file) {
                    if (is_file($file) && filemtime($file) < (time() - 3600)) { // Older than 1 hour
                        @unlink($file);
                        $cleared_count++;
                    }
                }
            }
        }
        
        // Log the action
        $log_message = '[' . date('Y-m-d H:i:s') . '] [Chatbot] [Advanced Reset] Cache and locks reset by admin user ID: ' . get_current_user_id() . ' - Cleared ' . $cleared_count . ' entries';
        chatbot_error_log($log_message);
        
        wp_send_json_success('Cache and locks reset successfully. Cleared ' . $cleared_count . ' entries.');
        
    } catch (Exception $e) {
        $log_message = '[' . date('Y-m-d H:i:s') . '] [Chatbot] [Advanced Reset] Error: ' . $e->getMessage();
        chatbot_error_log($log_message);
        wp_send_json_error('Error resetting cache and locks: ' . $e->getMessage());
    }
}

// Append an extra message to the response - Ver 2.0.9
function chatbot_chatgpt_append_extra_message($response, $extra_message) {

    // Append the extra message to the response
    $response = $response . ' ' . $extra_message;
    return $response;

}

// Crawler aka Knowledge Navigator - Ver 1.6.1
function chatbot_chatgpt_kn_status_activation() {

    add_option('chatbot_chatgpt_kn_status', 'Never Run');
    // clear any old scheduled runs

    if (wp_next_scheduled('crawl_scheduled_event_hook')) {
        wp_clear_scheduled_hook('crawl_scheduled_event_hook');
    }

    // clear the 'knowledge_navigator_scan_hook' hook on plugin activation - Ver 1.6.3
    if (wp_next_scheduled('knowledge_navigator_scan_hook')) {
        // BREAK/FIX - Do not unset the hook - Ver 1.8.5
        // wp_clear_scheduled_hook('knowledge_navigator_scan_hook'); // Clear scheduled runs
    }

}
register_activation_hook(__FILE__, 'chatbot_chatgpt_kn_status_activation');

// Clean Up in Aisle 4
function chatbot_chatgpt_kn_status_deactivation() {

    delete_option('chatbot_chatgpt_kn_status');
    wp_clear_scheduled_hook('knowledge_navigator_scan_hook'); 

}
register_deactivation_hook(__FILE__, 'chatbot_chatgpt_kn_status_deactivation');

// Markov Chain builder - Activation Hook - Ver 2.1.6
function chatbot_markov_chain_status_activation() {

    // DIAG - Diagnostics - Ver 2.1.6

    // Add the option for build status with a default value of 'Never Run'
    add_option('chatbot_markov_chain_build_status', 'Never Run');

    // Clear any old scheduled runs, if present
    if (wp_next_scheduled('chatbot_markov_chain_scan_hook')) {
        // BREAK/FIX - Do not unset the hook - Ver 2.1.6
        // wp_clear_scheduled_hook('chatbot_markov_chain_scan_hook'); // Clear scheduled runs
    }

}
register_activation_hook(__FILE__, 'chatbot_markov_chain_status_activation');

// Clean up scheduled events and options - Deactivation Hook
function chatbot_markov_chain_status_deactivation() {

    // DIAG - Diagnostics - Ver 2.1.6

    // Delete the build status option on deactivation
    delete_option('chatbot_markov_chain_build_status');

    // Clear any scheduled events related to the Markov Chain scan
    wp_clear_scheduled_hook('chatbot_markov_chain_scan_hook');

}
register_deactivation_hook(__FILE__, 'chatbot_markov_chain_status_deactivation');

// Transformer Model builder - Activation Hook - Ver 2.2.0
function chatbot_transformer_model_status_activation() {

    // DIAG - Diagnostics - Ver 2.2.0

    // Add the option for build status with a default value of 'Never Run'
    add_option('chatbot_transformer_model_build_status', 'Never Run');

    // Clear any old scheduled runs, if present
    if (wp_next_scheduled('chatbot_transformer_model_scan_hook')) {
        // BREAK/FIX - Do not unset the hook - Ver 2.2.0
        // wp_clear_scheduled_hook('chatbot_transformer_model_scan_hook'); // Clear scheduled runs
    }

}
register_activation_hook(__FILE__, 'chatbot_transformer_model_status_activation');

// Clean up scheduled events and options - Deactivation Hook
function chatbot_transformer_model_status_deactivation() {

    // DIAG - Diagnostics - Ver 2.2.0

    // Delete the build status option on deactivation
    delete_option('chatbot_transformer_model_build_status');

    // Clear any scheduled events related to the Transformer Model scan
    wp_clear_scheduled_hook('chatbot_transformer_model_scan_hook');

}
register_deactivation_hook(__FILE__, 'chatbot_transformer_model_status_deactivation');

// Function to add a new message and response, keeping only the last five - Ver 1.6.1
function addEntry($transient_name, $newEntry) {

    $context_history = get_transient($transient_name);
    if (!$context_history) {
        $context_history = [];
    }

    // Determine the total length of all existing entries
    $totalLength = 0;
    foreach ($context_history as $entry) {
        if (is_string($entry)) {
            $totalLength += strlen($entry);
        } elseif (is_array($entry)) {
            $totalLength += strlen(json_encode($entry)); // Convert to string if an array
        }
    }

    // IDEA - How will the new threading option from OpenAI change how this works?
    // Define thresholds for the number of entries to keep
    $maxEntries = 30; // Default maximum number of entries
    if ($totalLength > 5000) { // Higher threshold
        $maxEntries = 20;
    }
    if ($totalLength > 10000) { // Lower threshold
        $maxEntries = 10;
    }

    while (count($context_history) >= $maxEntries) {
        array_shift($context_history); // Remove the oldest element
    }

    if (is_array($newEntry)) {
        $newEntry = json_encode($newEntry); // Convert the array to a string
    }

    array_push($context_history, $newEntry); // Append the new element
    set_transient($transient_name, $context_history); // Update the transient
}

// Function to return message and response - Ver 1.6.1
function concatenateHistory($transient_name) {
    $context_history = get_transient($transient_name);
    if (!$context_history) {
        return ''; // Return an empty string if the transient does not exist
    }
    return implode(' ', $context_history); // Concatenate the array values into a single string
}

// FIXME - MOVE CORE FUNCTIONS TO A SEPARATE FILE, LEAVING ONLY THE HOOKS HERE
// Initialize the Greetings - Ver 1.6.1
function enqueue_greetings_script( $initial_greeting = null, $subsequent_greeting = null) {

    // If user is logged in, then modify greeting if greeting contains "[...]" or remove if not logged in - Ver 1.9.4
    if (is_user_logged_in()) {

        $current_user_id = get_current_user_id();
        $current_user = get_userdata($current_user_id);

        //Do this for Initial Greeting
        if ( empty($initial_greeting) ) {
            $initial_greeting = esc_attr(get_option('chatbot_chatgpt_initial_greeting', 'Hello! How can I help you today?'));
        }

        // Determine what the field name is between the brackets
        $user_field_name = '';
        $user_field_name = substr($initial_greeting, strpos($initial_greeting, '[') + 1, strpos($initial_greeting, ']') - strpos($initial_greeting, '[') - 1);
        // If $initial_greeting contains "[$user_field_name]" then replace with field from DB
        if (strpos($initial_greeting, '[' . $user_field_name . ']') !== false) {
            $initial_greeting = str_replace('[' . $user_field_name . ']', $current_user->$user_field_name, $initial_greeting);
        } else {
            $initial_greeting = str_replace('[' . $user_field_name . ']', '', $initial_greeting);
            // Remove the extra space when two spaces are present
            $initial_greeting = str_replace('  ', ' ', $initial_greeting);
            // Remove the extra space before punctuation including period, comma, exclamation mark, and question mark
            $initial_greeting = preg_replace('/\s*([.,!?])/', '$1', $initial_greeting);
        }

        // Do this for Subsequent Greeting
        if ( empty($subsequent_greeting) ) {
            $subsequent_greeting = esc_attr(get_option('chatbot_chatgpt_subsequent_greeting', 'Hello again! How can I help you?'));
        }

        // Determine what the field name is between the brackets
        $user_field_name = '';
        $user_field_name = substr($subsequent_greeting, strpos($subsequent_greeting, '[') + 1, strpos($subsequent_greeting, ']') - strpos($subsequent_greeting, '[') - 1);
        // If $subsequent_greeting contains "[$user_field_name]" then replace with field from DB
        if (strpos($subsequent_greeting, '[' . $user_field_name . ']') !== false) {
            $subsequent_greeting = str_replace('[' . $user_field_name . ']', $current_user->$user_field_name, $subsequent_greeting);
        } else {
            $subsequent_greeting = str_replace('[' . $user_field_name . ']', '', $subsequent_greeting);
            // Remove the extra space when two spaces are present
            $subsequent_greeting = str_replace('  ', ' ', $subsequent_greeting);
            // Remove the extra space before punctuation including period, comma, exclamation mark, and question mark
            $subsequent_greeting = preg_replace('/\s*([.,!?])/', '$1', $subsequent_greeting);
        }

    } else {

        //Do this for Initial Greeting
        if ( empty($initial_greeting) ) {
            $initial_greeting = esc_attr(get_option('chatbot_chatgpt_initial_greeting', 'Hello! How can I help you today?'));
        }

        $user_field_name = '';
        $user_field_name = substr($initial_greeting, strpos($initial_greeting, '[') + 1, strpos($initial_greeting, ']') - strpos($initial_greeting, '[') - 1 );

        // $initial_greeting = preg_replace('/\s*\[' . preg_quote($user_field_name, '/') . '\]\s*/', '', $initial_greeting);
        $initial_greeting = str_replace('[' . $user_field_name . ']', '', $initial_greeting);
        // Remove the extra space when two spaces are present
        $initial_greeting = str_replace('  ', ' ', $initial_greeting);
        // Remove the extra space before punctuation including period, comma, exclamation mark, and question mark
        $initial_greeting = preg_replace('/\s*([.,!?])/', '$1', $initial_greeting);

        //Do this for Subsequent Greeting
        if ( empty($subsequent_greeting) ) {
            $subsequent_greeting = esc_attr(get_option('chatbot_chatgpt_subsequent_greeting', 'Hello again! How can I help you?'));
        }

        $user_field_name = '';
        $user_field_name = substr($subsequent_greeting, strpos($subsequent_greeting, '[') + 1, strpos($subsequent_greeting, ']') - strpos($subsequent_greeting, '[') - 1);

        // $subsequent_greeting = preg_replace('/\s*\[' . preg_quote($user_field_name, '/') . '\]\s*/', '', $subsequent_greeting);
        $subsequent_greeting = str_replace('[' . $user_field_name . ']', '', $subsequent_greeting);
        // Remove the extra space when two spaces are present
        $subsequent_greeting = str_replace('  ', ' ', $subsequent_greeting);
        // Remove the extra space before punctuation including period, comma, exclamation mark, and question mark
        $subsequent_greeting = preg_replace('/\s*([.,!?])/', '$1', $subsequent_greeting);
        
    }

    $greetings = array(
        'initial_greeting' => $initial_greeting,
        'subsequent_greeting' => $subsequent_greeting,
    );

    return $greetings;

}
// 
add_action('wp_enqueue_scripts', 'enqueue_greetings_script');

// Add the color picker to the adaptive appearance settings section - Ver 1.8.1
function enqueue_color_picker($hook_suffix) {

    global $chatbot_chatgpt_plugin_version;

    // first check that $hook_suffix is appropriate for your admin page
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('my-script-handle', plugin_dir_url(__FILE__) . 'assets/js/chatbot-chatgpt-color-picker.js', array('wp-color-picker'), $chatbot_chatgpt_plugin_version, true);

}
add_action('admin_enqueue_scripts', 'enqueue_color_picker');

// Determine if the plugin is installed
function kchat_get_plugin_version() {

    global $chatbot_chatgpt_plugin_version;

    if (!function_exists('get_plugin_data')) {
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }

    $plugin_data = get_plugin_data(plugin_dir_path(__FILE__) . 'chatbot-chatgpt.php');
    // DIAG - Print the plugin data
    // $plugin_version = $plugin_data['chatbot_chatgpt_version'];
    $plugin_version = $plugin_data['Version'];
    // $plugin_version = $chatbot_chatgpt_plugin_version;
    update_option('chatbot_chatgpt_plugin_version', $plugin_version);
    // DIAG - Log the plugin version

    return $plugin_version;

}

// DO NOT REMOVE THIS IF, IT IS ESSENTIAL FOR THE AUTOMATIC DEACTIVATION OF THE PLUGIN
}

