<?php
/*
 * Plugin Name: Kognetiks Chatbot
 * Plugin URI:  https://github.com/kognetiks/kognetiks-chatbot
 * Description: This simple plugin adds an AI powered chatbot to your WordPress website.
 * Version:     2.3.0
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
 */

// If this file is called directly, die.
defined( 'WPINC' ) || die();

// Start output buffering earlier to prevent "headers already sent" issues - Ver 2.1.8
ob_start();

// Plugin version
global $chatbot_chatgpt_plugin_version;
$chatbot_chatgpt_plugin_version = '2.3.0';

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
function kognetiks_assign_unique_id() {
    if (!isset($_COOKIE['kognetiks_unique_id'])) {
        $unique_id = uniqid('kognetiks_', true);
        
        // Set a cookie using the built-in setcookie function
        setcookie('kognetiks_unique_id', $unique_id, time() + (86400 * 30), "/", "", true, true); // HttpOnly and Secure flags set to true
                
        // Ensure the cookie is set for the current request
        $_COOKIE['kognetiks_unique_id'] = $unique_id;
    }
}
add_action('init', 'kognetiks_assign_unique_id', 1); // Set higher priority

// Get the unique ID of the visitor or logged-in user - Ver 2.0.4
function kognetiks_get_unique_id() {
    if (isset($_COOKIE['kognetiks_unique_id'])) {
        // error_log('Unique ID found: ' . $_COOKIE['kognetiks_unique_id']);
        return sanitize_text_field($_COOKIE['kognetiks_unique_id']);
    }
    // error_log('Unique ID not found');
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

// Include necessary files - Main files
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-anthropic-api.php';         // ANT API - Ver 2.0.7
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-azure-openai-api.php';      // Azure OpenAI API - Ver 2.2.6
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-azure-api-assistant.php';   // Azure OpenAI Assistants API - Ver 2.2.6
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-deepseek-api.php';          // ChatGPT API - Ver 2.2.2
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-kognetiks-api-mc.php';      // Kognetiks - Markov Chain API - Ver 2.1.6
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-kognetiks-api-tm.php';      // Kognetiks - Transformer Model API - Ver 2.2.0
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-local-api.php';             // Local API - Ver 2.2.6
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-mistral-api.php';           // Mistral API - Ver 2.3.0
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-nvidia-api.php';            // NVIDIA API - Ver 2.1.8
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-openai-api-assistant.php';  // GPT Assistants - Ver 1.6.9
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-openai-api-chatgpt.php';    // ChatGPT API - Ver 1.6.9
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-openai-api-image.php';      // Image API - Ver 1.9.4
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-openai-api-kflow.php';      // Kognetiks - Flow API - Ver 1.9.5
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-openai-api-omni.php';       // ChatGPT API - Ver 2.0.2.1
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-openai-api-stt.php';        // STT API - Ver 2.0.1
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-openai-api-tts-api.php';    // TTS API - Ver 1.9.4
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
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-api-nvidia.php';               // NVIDIA
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-api-local.php';                // Local Server - Ver 2.2.6
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
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-premium.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-registration-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-registration-kn.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-registration.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-reporting.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-support.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-tools.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-transformers.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings.php';

// Include necessary files - Utilities - Ver 1.9.0
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-api-endpoints.php';                // API Endpoints - Ver 2.2.2
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-assistants.php';                   // Assistants Management for OpenAI - Ver 2.0.4
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-assistants-azure.php';             // Assistants Management for Azure - Ver 2.2.6
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-assisted-search.php';              // Assisted Search - Ver 2.2.7 - 2025-03-26
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-conversation-history.php';         // Ver 1.9.2
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-content-search.php';               // Functions - Ver 2.2.4
require_once plugin_dir_path(__FILE__) . 'includes/dashboard/chatbot-chatgpt-dashboard-widget.php';      // Dashboard Widget - Ver 2.2.7
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-db-management.php';                // Database Management for Reporting - Ver 1.6.3
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-deactivate.php';                   // Deactivation - Ver 1.9.9
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-download-transcript.php';          // Functions - Ver 1.9.9
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-erase-conversation.php';           // Functions - Ver 1.8.6
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-file-download.php';                // Download a file via the API - Ver 2.0.3
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-file-helper.php';                  // Functions - Ver 2.0.3
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-file-upload.php';                  // Functions - Ver 1.7.6
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-filter-out-html-tags.php';         // Functions - Ver 1.9.6
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-keyguard.php';                     // Functions - Ver 2.2.6
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-link-and-image-handling.php';      // Globals - Ver 1.9.1
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-models.php';                       // Functions - Ver 1.9.4
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-names.php';                        // Functions - Ver 1.9.4
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-options-helper.php';               // Functions - Ver 2.0.5
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-threads.php';                      // Ver 1.7.2.1
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-transients-file.php';              // Ver 1.9.2
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-transients.php';                   // Ver 1.7.2
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-upgrade.php';                      // Ver 1.6.7
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-utilities.php';                    // Ver 1.8.6

// Third-party libraries
require_once plugin_dir_path(__FILE__) . 'includes/utilities/parsedown.php';                            // Version 2.0.2.1

// Include necessary files - Tools - Ver 2.0.6
require_once plugin_dir_path(__FILE__) . 'tools/chatbot-capability-tester.php';
require_once plugin_dir_path(__FILE__) . 'tools/chatbot-manage-error-logs.php';
require_once plugin_dir_path(__FILE__) . 'tools/chatbot-options-exporter.php';
require_once plugin_dir_path(__FILE__) . 'tools/chatbot-shortcode-tester.php';
require_once plugin_dir_path(__FILE__) . 'tools/chatbot-shortcode-tester-tool.php';

// Include necessary files - Widgets - Ver 2.1.3
require_once plugin_dir_path(__FILE__) . 'widgets/chatbot-manage-widget-logs.php';

// Log the User ID and Session ID - Ver 2.0.6 - 2024 07 11
// back_trace( 'NOTICE', '$user_id: ' . $user_id);
// back_trace( 'NOTICE', '$session_id: ' . $session_id);

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
        
        // Model choice - Ver 1.9.4
        if (esc_attr(get_option('chatbot_chatgpt_model_choice')) === null) {
            $model = 'gpt-4-1106-preview';
            update_option('chatbot_chatgpt_model_choice', $model);
            // DIAG - Diagnostics
            // back_trace( 'NOTICE', 'Model upgraded: ' . $model);
        } elseif (empty($model)) {
            $model = 'gpt-4-1106-preview';
        }
    
        // Voice choice - Ver 1.9.5
        if (esc_attr(get_option('chatbot_chatgpt_voice_option')) === null) {
            $voice = 'alloy';
            update_option('chatbot_chatgpt_voice_option', $voice);
            // DIAG - Diagnostics
            // back_trace( 'NOTICE', 'Voice upgraded: ' . $voice);
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
        
        // Model choice - Ver 1.9.4
        if (esc_attr(get_option('chatbot_azure_model_choice')) === null) {
            $model = 'gpt-4-1106-preview';
            update_option('chatbot_azure_model_choice', $model);
            // DIAG - Diagnostics
            // back_trace( 'NOTICE', 'Model upgraded: ' . $model);
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
        //     // back_trace( 'NOTICE', 'Voice upgraded: ' . $voice);
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

        // Model choice - Ver 2.1.8
        if (esc_attr(get_option('chatbot_nvidia_model_choice')) === null) {
            $model = 'nvidia/llama-3.1-nemotron-51b-instruct';
            update_option('chatbot_nvidia_model_choice', $model);
            // DIAG - Diagnostics
            // back_trace( 'NOTICE', 'Model upgraded: ' . $model);
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
        
        // Model choice - Ver 2.2.1
        if (esc_attr(get_option('chatbot_anthropic_model_choice')) === null) {
            $model = 'claude-3-5-sonnet-latest';
            update_option('chatbot_anthropic_model_choice', $model);
            // DIAG - Diagnostics
            // back_trace( 'NOTICE', 'Model upgraded: ' . $model);
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
        
        // Model choice - Ver 2.2.1
        if (esc_attr(get_option('chatbot_deepseek_model_choice')) === null) {
            $model = 'deepseek-chat';
            update_option('chatbot_deepseek_model_choice', $model);
            // DIAG - Diagnostics
            // back_trace( 'NOTICE', 'Model upgraded: ' . $model);
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

        // Model choice - Ver 2.2.1
        if (esc_attr(get_option('chatbot_mistral_model_choice')) === null) {
            $model = 'mistral-small-latest';
            update_option('chatbot_mistral_model_choice', $model);
            // DIAG - Diagnostics
            // back_trace( 'NOTICE', 'Model upgraded: ' . $model);
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
                 
        // Model choice - Ver 2.1.8
        if (esc_attr(get_option('chatbot_markov_chain_model_choice')) === null) {
            $model = 'markov-chain-flask';
            update_option('chatbot_markov_chain_model_choice', $model);
            // DIAG - Diagnostics
            // back_trace( 'NOTICE', 'Model upgraded: ' . $model);
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
    
        // Model choice - Ver 2.2.0
        if (esc_attr(get_option('chatbot_transformer_model_choice')) === null) {
            $model = 'sentential-context-model';
            update_option('chatbot_transformer_model_choice', $model);
            // DIAG - Diagnostics
            // back_trace( 'NOTICE', 'Model upgraded: ' . $model);
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
        // back_trace( 'NOTICE', '$chatbot_ai_platform_choice: ' . $chatbot_ai_platform_choice);

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

        $chatbot_mistral_api_enabled = 'No';
        update_option('chatbot_mistral_api_enabled', 'No');
    
        // Model choice - Ver 2.2.0
        if (esc_attr(get_option('chatbot_local_model_choice')) === null) {
            $model = 'llama3.2-3b-instruct';
            update_option('chatbot_local_model_choice', $model);
            // DIAG - Diagnostics
            // back_trace( 'NOTICE', 'Model upgraded: ' . $model);
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

        // Model choice - Ver 1.9.4
        if (esc_attr(get_option('chatbot_chatgpt_model_choice')) === null) {
            $model = 'gpt-4-1106-preview';
            update_option('chatbot_chatgpt_model_choice', $model);
            // DIAG - Diagnostics
            // back_trace( 'NOTICE', 'Model upgraded: ' . $model);
        } elseif (empty($model)) {
            $model = 'gpt-4-1106-preview';
        }

        // Voice choice - Ver 1.9.5
        if (esc_attr(get_option('chatbot_chatgpt_voice_option')) === null) {
            $voice = 'alloy';
            update_option('chatbot_chatgpt_voice_option', $voice);
            // DIAG - Diagnostics
            // back_trace( 'NOTICE', 'Voice upgraded: ' . $voice);
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
    // back_trace( 'NOTICE', '$chatbot_ai_platform_choice: ' . $chatbot_ai_platform_choice);
    // back_trace( 'NOTICE', '$chatbot_chatgpt_allow_file_uploads: ' . $chatbot_chatgpt_allow_file_uploads);
} elseif ($chatbot_ai_platform_choice == 'Azure OpenAI') {
    $chatbot_chatgpt_allow_file_uploads = esc_attr(get_option('chatbot_azure_allow_file_uploads', 'No'));
    // DIAG - Diagnostics - Ver 2.2.6
    // back_trace( 'NOTICE', '$chatbot_ai_platform_choice: ' . $chatbot_ai_platform_choice);
    // back_trace( 'NOTICE', '$chatbot_chatgpt_allow_file_uploads: ' . $chatbot_chatgpt_allow_file_uploads);
} else {
    $chatbot_chatgpt_allow_file_uploads = esc_attr(get_option('chatbot_chatgpt_allow_file_uploads', 'No'));
    // DIAG - Diagnostics - Ver 2.2.6
    // back_trace( 'NOTICE', '$chatbot_ai_platform_choice: ' . $chatbot_ai_platform_choice);
    // back_trace( 'NOTICE', '$chatbot_chatgpt_allow_file_uploads: ' . $chatbot_chatgpt_allow_file_uploads);
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
    // back_trace( 'NOTICE', '$user_id: ' . $user_id);
    // back_trace( 'NOTICE', '$session_id: ' . $session_id);

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
    ));

    // DIAG - Diagnostics - Ver 1.8.6
    // back_trace( 'NOTICE', '$user_id: ' . $user_id);
    // back_trace( 'NOTICE', '$page_id: ' . $page_id);
    // back_trace( 'NOTICE', '$session_id: ' . $session_id);
    // back_trace( 'NOTICE', '$thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', '$assistant_id: ' . $assistant_id);
    // back_trace( 'NOTICE', '$additional_instructions: ' . $additional_instructions);
    // back_trace( 'NOTICE', '$model: ' . $model);
    
    // Set visitor and logged in user limits - Ver 2.0.1
    if (is_user_logged_in()) {
        // back_trace( 'NOTICE', 'User is logged in');
        $kchat_settings['chatbot_chatgpt_message_limit_setting'] = esc_attr(get_option('chatbot_chatgpt_user_message_limit_setting', '999'));
        $kchat_settings['chatbot_chatgpt_message_limit_period_setting'] = esc_attr(get_option('chatbot_chatgpt_user_message_limit_period_setting', 'Lifetime'));
        $kchat_settings['chatbot_chatgpt_display_message_count'] = esc_attr(get_option('chatbot_chatgpt_display_message_count', 'No'));
    } else {
        // back_trace( 'NOTICE', 'User is NOT logged in');
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

// Handle Ajax requests
function chatbot_chatgpt_send_message() {

    // Global variables
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

    $chatbot_ai_platform_choice = esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI'));

    // DIAG - Diagnostics - Ver 2.2.1
    // back_trace( 'NOTICE', '$chatbot_ai_platform_choice: ' . $chatbot_ai_platform_choice);

    switch ($chatbot_ai_platform_choice) {

        case 'OpenAI':

            $api_key = esc_attr(get_option('chatbot_chatgpt_api_key'));
            // Decrypt the API key - Ver 2.2.6
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            $model = esc_attr(get_option('chatbot_chatgpt_model_choice', 'gpt-4-1106-preview'));
            $kchat_settings['chatbot_chatgpt_model'] = $model;
            $kchat_settings['model'] = $model;
            // DIAG - Diagnostics - Ver 2.1.8
            // back_trace( 'NOTICE', '$model: ' . $model);
            break;

        case 'Azure OpenAI':

            $api_key = esc_attr(get_option('chatbot_azure_api_key'));
            // Decrypt the API key - Ver 2.2.6
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            $model = esc_attr(get_option('chatbot_azure_model_choice', 'gpt-4-1106-preview'));
            $kchat_settings['chatbot_chatgpt_model'] = $model;
            $kchat_settings['model'] = $model;
            // DIAG - Diagnostics - Ver 2.2.6
            // back_trace( 'NOTICE', '$model: ' . $model);
            break;

        case 'NVIDIA':

            $api_key = esc_attr(get_option('chatbot_nvidia_api_key'));
            $model = esc_attr(get_option('chatbot_nvidia_model_choice', 'nvidia/llama-3.1-nemotron-51b-instruct'));
            // Decrypt the API key - Ver 2.2.6
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            $kchat_settings['chatbot_chatgpt_model'] = $model;
            $kchat_settings['model'] = $model;
            // DIAG - Diagnostics - Ver 2.1.8
            // back_trace( 'NOTICE', '$model: ' . $model);
            break;

        case 'Anthropic':

            $api_key = esc_attr(get_option('chatbot_anthropic_api_key'));
            // Decrypt the API key - Ver 2.2.6
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            $model = esc_attr(get_option('chatbot_anthropic_model_choice', 'claude-3-5-sonnet-latest'));
            $kchat_settings['chatbot_chatgpt_model'] = $model;
            $kchat_settings['model'] = $model;
            // DIAG - Diagnostics - Ver 2.1.8
            // back_trace( 'NOTICE', '$model: ' . $model);
            break;

        case 'DeepSeek':

            $api_key = esc_attr(get_option('chatbot_deepseek_api_key'));
            // Decrypt the API key - Ver 2.2.6
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            $model = esc_attr(get_option('chatbot_deepseek_model_choice', 'deepseek-chat'));
            $kchat_settings['chatbot_chatgpt_model'] = $model;
            $kchat_settings['model'] = $model;
            // DIAG - Diagnostics - Ver 2.2.2
            // back_trace( 'NOTICE', '$model: ' . $model);
            break;
        
        case 'Markov Chain':

            $api_key = esc_attr(get_option('chatbot_markov_chain_api_key', 'NOT REQUIRED'));
            $model = esc_attr(get_option('chatbot_markov_chain_model_choice', 'markov-chain-flask'));
            $kchat_settings['chatbot_chatgpt_model'] = $model;
            $kchat_settings['model'] = $model;
            // DIAG - Diagnostics - Ver 2.1.8
            // back_trace( 'NOTICE', '$model: ' . $model);
            break;

        case 'Transformer':

            $api_key = esc_attr(get_option('chatbot_transformer_api_key', 'NOT REQUIRED'));
            $model = esc_attr(get_option('chatbot_transformer_model_choice', 'lexical-context-model'));
            $kchat_settings['chatbot_chatgpt_model'] = $model;
            $kchat_settings['model'] = $model;
            // DIAG - Diagnostics - Ver 2.1.8
            // back_trace( 'NOTICE', '$model: ' . $model);
            break;

        case 'Local Server':

            $api_key = esc_attr(get_option('chatbot_local_api_key', 'NOT REQUIRED'));
            // Decrypt the API key - Ver 2.2.6
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            $model = esc_attr(get_option('chatbot_local_model_choice', 'llama3.2-3b-instruct'));
            $kchat_settings['chatbot_chatgpt_model'] = $model;
            $kchat_settings['model'] = $model;
            // DIAG - Diagnostics - Ver 2.2.6
            // back_trace( 'NOTICE', '$model: ' . $model);
            break;

        case 'Mistral':

            $api_key = esc_attr(get_option('chatbot_mistral_api_key'));
            // Decrypt the API key - Ver 2.2.6
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            $model = esc_attr(get_option('chatbot_mistral_model_choice', 'mistral-small-latest'));
            $kchat_settings['chatbot_chatgpt_model'] = $model;
            $kchat_settings['model'] = $model;
            // DIAG - Diagnostics - Ver 2.2.2
            // back_trace( 'NOTICE', '$model: ' . $model);
            break;

        default:

            $api_key = esc_attr(get_option('chatbot_chatgpt_api_key'));
            // Decrypt the API key - Ver 2.2.6
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            $model = esc_attr(get_option('chatbot_chatgpt_model_choice', 'gpt-3.5-turbo'));
            $kchat_settings['chatbot_chatgpt_model'] = $model;
            $kchat_settings['model'] = $model;
            // DIAG - Diagnostics - Ver 2.1.8
            // back_trace( 'NOTICE', '$model: ' . $model);
            break;

    }

    // DIAG - Diagnostics - Ver 2.1.8
    // back_trace( 'NOTICE', '$model: ' . $model);

    // Send only clean text via the API
    $message = sanitize_text_field($_POST['message']);

    // Check for missing API key or message
    // if (!$api_key || !$message) {
    if ( !$message ) {
        // DIAG - Diagnostics
        // back_trace( 'ERROR', 'Invalid API Key or Message.');
        global $chatbot_chatgpt_fixed_literal_messages;
        // Define a default fallback message
        $default_message = 'Error: Invalid API key or Message. Please check the plugin settings.';
        $error_message = isset($chatbot_chatgpt_fixed_literal_messages[15]) 
            ? $chatbot_chatgpt_fixed_literal_messages[15] 
            : $default_message;
        // Send error response
        wp_send_json_error($error_message);
    }
    
    // Removed in Ver 1.8.6 - 2024 02 15
    // $thread_id = '';
    // $assistant_id = '';
    // $user_id = '';
    // $page_id = '';
    
    // Check the transient for the Assistant ID - Ver 1.7.2
    // $user_id = intval($_POST['user_id']); // REMOVED intval in Ver 2.0.8
    // $page_id = intval($_POST['page_id']); // REMOVED intval in Ver 2.0.8
    $user_id = $_POST['user_id'];
    $page_id = $_POST['page_id'];
    $session_id = $_POST['session_id'];

    // DIAG - Diagnostics - Ver 1.8.6
    // back_trace( 'NOTICE', '$user_id: ' . $user_id);
    // back_trace( 'NOTICE', '$page_id: ' . $page_id);
    // back_trace( 'NOTICE', '$session_id: ' . $session_id);

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
    // back_trace( 'NOTICE', '$model: ' . $model);
    
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
    // back_trace( 'NOTICE', '$model: ' . $model);

    $voice = isset($kchat_settings['chatbot_chatgpt_voice_option']) ? $kchat_settings['chatbot_chatgpt_voice_option'] : '';

    // DIAG - Diagnostics - Ver 1.8.6
    // back_trace( 'NOTICE', '========================================');
    // back_trace( 'NOTICE', '$user_id: ' . $user_id);
    // back_trace( 'NOTICE', '$page_id: ' . $page_id);
    // back_trace( 'NOTICE', '$session_id: ' . $session_id);
    // back_trace( 'NOTICE', '$thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', '$assistant_id: ' . $assistant_id);
    // back_trace( 'NOTICE', '$chatbot_chatgpt_assistant_alias: ' . $chatbot_chatgpt_assistant_alias);
    // back_trace( 'NOTICE', '3 - $model: ' . $model);
    // back_trace( 'NOTICE', '$voice: ' . $voice);
    // DIAG - Diagnostics - Ver 2.0.9
    // back_trace( 'NOTICE', '========================================');
    // foreach ($kchat_settings as $key => $value) {
    //      // back_trace( 'NOTICE', '$kchat_settings[' . $key . ']: ' . $value);
    // }

    // Assistants
    // $chatbot_chatgpt_assistant_alias == 'original'; // Default
    // $chatbot_chatgpt_assistant_alias == 'primary';
    // $chatbot_chatgpt_assistant_alias == 'alternate';
    // $chatbot_chatgpt_assistant_alias == 'asst_xxxxxxxxxxxxxxxxxxxxxxxx'; // GPT Assistant Id
  
    // Which Assistant ID to use - Ver 1.7.2
    if ($chatbot_chatgpt_assistant_alias == 'original') {

        $use_assistant_id = 'No';
        // DIAG - Diagnostics - Ver 2.0.5
        // back_trace( 'NOTICE' , 'Using Original ChatGPT - $chatbot_chatgpt_assistant_alias: ' . $chatbot_chatgpt_assistant_alias);

    } elseif ($chatbot_chatgpt_assistant_alias == 'primary') {

        $assistant_id = esc_attr(get_option('assistant_id'));
        // $additional_instructions = esc_attr(get_option('chatbot_chatgpt_assistant_instructions', '')); // REMOVED VER 2.0.9
        $use_assistant_id = 'Yes';

        // DIAG - Diagnostics - Ver 2.0.5
        // back_trace( 'NOTICE' , 'Using Primary Assistant - $assistant_id: ' .  $assistant_id);
        
        // Check if the GPT Assistant ID is blank, null, or "Please provide the GPT Assistant ID."
        if (empty($assistant_id) || $assistant_id == "Please provide the GPT Assistant Id.") {
        
            // Primary assistant_id not set
            $chatbot_chatgpt_assistant_alias = 'original';
            $use_assistant_id = 'No';
        
            // DIAG - Diagnostics - Ver 2.0.5
            // back_trace( 'NOTICE' ,'Falling back to ChatGPT API - $assistant_id: ' . $assistant_id );
        }

    } elseif ($chatbot_chatgpt_assistant_alias == 'alternate') {

        $assistant_id = esc_attr(get_option('chatbot_chatgpt_assistant_id_alternate'));
        // $additional_instructions = esc_attr(get_option('chatbot_chatgpt_assistant_instructions_alternate', '')); // REMOVED VER 2.0.9
        $use_assistant_id = 'Yes';

        // DIAG - Diagnostics - Ver 2.0.5
        // back_trace( 'NOTICE' , 'Using Alternate Assistant - $assistant_id: ' .  $assistant_id);

        // Check if the GPT Assistant ID is blank, null, or "Please provide the GPT Assistant ID."
        if (empty($assistant_id) || $assistant_id == "Please provide the GPT Assistant Id.") {

            /// Alternate assistant_id not set
            $chatbot_chatgpt_assistant_alias = 'original';
            $use_assistant_id = 'No';

            // DIAG - Diagnostics - Ver 2.0.5
            // back_trace( 'NOTICE' ,'Falling back to ChatGPT API - $assistant_id: ' . $assistant_id );
        
        }

    } elseif (str_starts_with($assistant_id, 'asst_')) {

        $chatbot_chatgpt_assistant_alias = $assistant_id; // Belt & Suspenders
        $use_assistant_id = 'Yes';

        // DIAG - Diagnostics - Ver 2.0.5
        // back_trace( 'NOTICE' ,'Assistant ID pass as a parameter - $assistant_id: ' . $assistant_id );

    } else {

        // Reference GPT Assistant IDs directly - Ver 1.7.3
        if (str_starts_with($chatbot_chatgpt_assistant_alias, 'asst_')) {

            // DIAG - Diagnostics - 2.0.5
            // back_trace( 'NOTICE', 'Using GPT Assistant ID: ' . $chatbot_chatgpt_assistant_alias);

            // Override the $assistant_id with the GPT Assistant ID
            $assistant_id = $chatbot_chatgpt_assistant_alias;
            $use_assistant_id = 'Yes';

            // DIAG - Diagnostics - Ver 2.0.5
            // back_trace( 'NOTICE' , 'Using $assistant_id ' . $assistant_id);

        } else {

            // DIAG - Diagnostics - Ver 2.0.5
            // back_trace( 'NOTICE', 'Using ChatGPT API: ' . $chatbot_chatgpt_assistant_alias);

            // Override the $use_assistant_id and set it to 'No'
            $use_assistant_id = 'No';
            
            // DIAG - Diagnostics - Ver 1.8.1
            // back_trace( 'NOTICE' , 'Falling back to ChatGPT API');

        }

    }

    // Get any additional instructions - Ver 2.0.9
    $additional_instructions = get_chatbot_chatgpt_transients( 'additional_instructions', $user_id, $page_id, $session_id);

    // Decide whether to use Flow, Assistant or Original ChatGPT
    if ($model == 'flow'){
        
        // DIAG - Diagnostics - Ver 2.1.1.1
        // back_trace( 'NOTICE', 'Using ChatGPT Flow');
        // back_trace( 'NOTICE', '$user_id ' . $user_id);
        // back_trace( 'NOTICE', '$page_id ' . $page_id);

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
        // back_trace( 'NOTICE', '$message: ' . $message);
        $thread_id = get_chatbot_chatgpt_threads($user_id, $session_id, $page_id, $assistant_id);
        // back_trace( 'NOTICE', '$thread_id ' . $thread_id);
        append_message_to_conversation_log($session_id, $user_id, $page_id, 'Visitor', $thread_id, $assistant_id, null, $message);

        // BELT & SUSPENDERS
        $thread_id = '';

        // Send message to ChatGPT API - Ver 1.6.7
        $response = chatbot_chatgpt_call_flow_api($api_key, $message);

        wp_send_json_success($response);

    } elseif ($use_assistant_id == 'Yes') {

        // DIAG - Diagnostics - Ver 2.1.1.1
        // back_trace( 'NOTICE', 'Using GPT Assistant ID: ' . $use_assistant_id);
        // back_trace( 'NOTICE', '$user_id ' . $user_id);
        // back_trace( 'NOTICE', '$page_id ' . $page_id);
        // back_trace( 'NOTICE', '$message ' . $message);


        // DIAG - Diagnostics
        // back_trace( 'NOTICE', '$message ' . $message);
        $thread_id = get_chatbot_chatgpt_threads($user_id, $session_id, $page_id, $assistant_id);
        // back_trace( 'NOTICE', '$thread_id ' . $thread_id);
        append_message_to_conversation_log($session_id, $user_id, $page_id, 'Visitor', $thread_id, $assistant_id, null, $message);

        // DIAG - Diagnostics - Ver 2.0.9
        // back_trace( 'NOTICE', '========================================');
        // back_trace( 'NOTICE', 'BEFORE CALL TO MODULE $additional_instructions: ' . $additional_instructions);

        // Send message to Custom GPT API - Ver 1.6.7
        $chatbot_ai_platform_choice = esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI'));

        if ($chatbot_ai_platform_choice == 'OpenAI') {
            // Send message to Custom GPT API - Ver 1.6.7
            // DIAG - Diagnostics
            // back_trace( 'NOTICE', 'Using OpenAI');
            $response = chatbot_chatgpt_custom_gpt_call_api($api_key, $message, $assistant_id, $thread_id, $session_id, $user_id, $page_id);
        } elseif ($chatbot_ai_platform_choice == 'Azure OpenAI') {
            // Send message to Custom GPT API - Ver 2.2.6
            // DIAG - Diagnostics
            // back_trace( 'NOTICE', 'Using Azure OpenAI');
            $response = chatbot_azure_custom_gpt_call_api($api_key, $message, $assistant_id, $thread_id, $session_id, $user_id, $page_id);
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
        // back_trace( 'NOTICE', '$response ' . print_r($response,true));
        $thread_id = get_chatbot_chatgpt_threads($user_id, $session_id, $page_id, $assistant_id);
        // back_trace( 'NOTICE', '$thread_id ' . $thread_id);
        append_message_to_conversation_log($session_id, $user_id, $page_id, 'Chatbot', $thread_id, $assistant_id, null, $response);

        // Clean (erase) the output buffer - Ver 1.6.8
        // Check if output buffering is active before attempting to clean it
        if (ob_get_level() > 0) {
            ob_clean();
            // DIAG - Diagnostics
            // back_trace( 'NOTICE', 'Output buffer cleaned');
        } else {
            // Optionally start output buffering if needed for your application
            // ob_start();
            // DIAG - Diagnostics
            // back_trace( 'NOTICE', 'Output buffer not cleaned');
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
        
            // Send success response
            wp_send_json_success($response);

        }

    } else {

        // DIAG - Diagnostics - Ver 2.1.1.1
        // back_trace( 'NOTICE', 'Using ChatGPT');
        // back_trace( 'NOTICE', '$user_id ' . $user_id);
        // back_trace( 'NOTICE', '$page_id ' . $page_id);
        // back_trace( 'NOTICE', '$message ' . $message);

        // Belt & Suspenders - Ver 2.1.5.1
        if (!isset($kchat_settings['model'])) {
            $kchat_settings['model'] = $model;
        };

        // FIXME - TESTING - Ver 2.1.8
        // back_trace( 'NOTICE', '$model: ' . $model);
        // back_trace( 'NOTICE', '$kchat_settings[model]: ' . $kchat_settings['model']);

        // if (str_starts_with($model,'dall')) {
        //     // back_trace ( 'NOTICE', 'Using Image API');
        // } else {
        //     // back_trace ( 'NOTICE', 'Using ChatGPT API');
        // }

        $thread_id = get_chatbot_chatgpt_threads($user_id, $session_id, $page_id, $assistant_id);
        // back_trace( 'NOTICE', '$thread_id ' . $thread_id);
        append_message_to_conversation_log($session_id, $user_id, $page_id, 'Visitor', $thread_id, $assistant_id, null, $message);
        
        // If $model starts with 'gpt' then the chatbot_chatgpt_call_api or 'dall' then chatbot_chatgpt_call_image_api
        // TRY NOT TO FETCH MODEL AGAIN
        // $model = esc_attr(get_option('chatbot_chatgpt_model_choice', 'gpt-3.5-turbo'));
        $model = isset($kchat_settings['model']) ? $kchat_settings['model'] : null;
        $voice = isset($kchat_settings['voice']) ? $kchat_settings['voice'] : null;

        // FIXME - TESTING - Ver 2.1.8
        // back_trace( 'NOTICE', '$model: ' . $model);

        $chatbot_ai_platform_choice = esc_attr(get_option('chatbot_ai_platform_choice', 'OpenAI'));

        // DIAG - Diagnostics - Ver 2.2.6
        // back_trace( 'NOTICE', '$chatbot_ai_platform_choice: ' . $chatbot_ai_platform_choice);

        switch ($chatbot_ai_platform_choice) {

            case 'OpenAI':

                switch ($model) {

                    case str_starts_with($model, 'gpt-4o'):

                        // The string 'gpt-4o' is found in $model
                        // Reload the model - BELT & SUSPENDERS
                        $kchat_settings['model'] = $model;
                        // DIAG - Diagnostics - Ver 2.1.8
                        // back_trace( 'NOTICE', 'Calling ChatGPT Omni API');
                        // Send message to ChatGPT API - Ver 1.6.7
                        $response = chatbot_chatgpt_call_omni($api_key, $message);

                        break;

                    case str_starts_with($model, 'gpt'):
                        
                        // Reload the model - BELT & SUSPENDERS
                        $kchat_settings['model'] = $model;
                        // DIAG - Diagnostics - Ver 2.1.8
                        // back_trace( 'NOTICE', 'Calling ChatGPT API');
                        // Send message to ChatGPT API - Ver 1.6.7
                        $response = chatbot_chatgpt_call_api($api_key, $message);

                        break;

                    case str_starts_with($model, 'dall'):

                        // Reload the model - BELT & SUSPENDERS
                        $kchat_settings['model'] = $model;
                        // DIAG - Diagnostics - Ver 2.1.8
                        // back_trace( 'NOTICE', 'Calling Dall E Image API');
                        // Send message to Image API - Ver 1.9.4
                        $response = chatbot_chatgpt_call_image_api($api_key, $message);

                        break;

                    case str_starts_with($model, 'tts'):

                        // Reload the model - BELT & SUSPENDERS
                        $kchat_settings['model'] = $model;
                        $kchat_settings['voice'] = $voice;
                        // DIAG - Diagnostics - Ver 2.1.8
                        // back_trace( 'NOTICE', 'Calling TTS API');
                        // Send message to TTS API - Text-to-speech - Ver 1.9.5
                        $response = chatbot_chatgpt_call_tts_api($api_key, $message, $voice, $user_id, $page_id, $session_id);

                        break;

                    case str_starts_with($model, 'whisper'):
                        
                        // Reload the model - BELT & SUSPENDERS
                        $kchat_settings['model'] = $model;
                        // DIAG - Diagnostics - Ver 2.1.8
                        // back_trace( 'NOTICE', 'Calling STT API');
                        // Send message to STT API - Speech-to-text - Ver 1.9.6
                        $response = chatbot_chatgpt_call_stt_api($api_key, $message);

                        break;

                }

                break;

            case 'Azure OpenAI':

                $kchat_settings['model'] = $model;
                // DIAG - Diagnostics - Ver 2.2.6
                // back_trace( 'NOTICE', 'Calling Azure OpenAI API');
                // Send message to Azure OpenAI API - Ver 2.2.6
                $response = chatbot_call_azure_openai_api($api_key, $message);

                break;

            case 'NVIDIA':

                $kchat_settings['model'] = $model;
                // DIAG - Diagnostics - Ver 2.1.8
                // back_trace( 'NOTICE', 'Calling NVIDIA API');
                // Send message to NVIDIA API - Ver 2.1.8
                $response = chatbot_nvidia_call_api($api_key, $message);

                break;

            case 'Anthropic':
            
                $kchat_settings['model'] = $model;
                // DIAG - Diagnostics - Ver 2.1.8
                // back_trace( 'NOTICE', 'Calling Anthropic API');
                // Send message to Claude API - Ver 2.1.8
                $response = chatbot_call_anthropic_api($api_key, $message);

                break;

            case 'DeepSeek':

                $kchat_settings['model'] = $model;
                // DIAG - Diagnostics - Ver 2.2.2
                // back_trace( 'NOTICE', 'Calling DeepSeek API');
                // Send message to DeepSeek API - Ver 2.2.2
                $response = chatbot_call_deepseek_api($api_key, $message);

                break;

            case 'Mistral':

                $kchat_settings['model'] = $model;
                // DIAG - Diagnostics - Ver 2.3.0
                // back_trace( 'NOTICE', 'Calling Mistral API');
                // Send message to Mistral API - Ver 2.3.0
                $response = chatbot_chatgpt_call_mistral_api($api_key, $message);

                break;

            case 'Markov Chain':

                $kchat_settings['model'] = $model;
                // DIAG - Diagnostics - Ver 2.1.8
                // back_trace( 'NOTICE', 'Calling Markov Chain API');
                // Send message to Markov API - Ver 1.9.7
                $response = chatbot_chatgpt_call_markov_chain_api($message);

                break;

            case 'Transformer':

                $kchat_settings['model'] = $model;
                // DIAG - Diagnostics - Ver 2.2.0
                // back_trace( 'NOTICE', 'Calling Transformer Model API');
                // Send message to Transformer Model API - Ver 2.2.0
                $response = chatbot_chatgpt_call_transformer_model_api($message);

                break;

            case 'Local Server':

                $kchat_settings['model'] = $model;
                // DIAG - Diagnostics - Ver 2.2.6
                // back_trace( 'NOTICE', 'Calling Local Model API');
                // Send message to Local Model API - Ver 2.2.6
                $response = chatbot_chatgpt_call_local_model_api($message);

                break;

            default:

                $kchat_settings['model'] = $model;
                // DIAG - Diagnostics - Ver 2.2.6
                // back_trace( 'NOTICE', 'Calling default ' .$model . ' model API');
                // Send message to ChatGPT API - Ver 1.6.7
                $response = chatbot_chatgpt_call_api($api_key, $message);

        }
        
        // DIAG - Diagnostics
        // back_trace( 'NOTICE', '$message: ' . $message);
        // back_trace( 'NOTICE', '$response: ' . print_r($response, true));

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
        // back_trace( 'NOTICE', ['message' => 'AFTER CALL TO ENHANCE TFIDF', 'response' => $response]);

        // DIAG - Diagnostics
        // back_trace( 'NOTICE', '$response ' . print_r($response,true));
        $thread_id = get_chatbot_chatgpt_threads($user_id, $session_id, $page_id, $assistant_id);
        // back_trace( 'NOTICE', '$thread_id ' . $thread_id);
        append_message_to_conversation_log($session_id, $user_id, $page_id, 'Chatbot', $thread_id, $assistant_id, null, $response);

        // DIAG - Diagnostics
        // back_trace( 'NOTICE', 'Check for links and images in response before returning');
        $response = chatbot_chatgpt_check_for_links_and_images($response);

        // DIAG - Diagnostics - Ver 2.0.5
        // back_trace( 'NOTICE', '$response: ' . print_r($response, true));

        // FIXME - Append extra message - Ver 2.1.1.1.1
        // Danger Will Robinson! Danger!
        $extra_message = esc_attr(get_option('chatbot_chatgpt_extra_message', ''));
        $response = chatbot_chatgpt_append_extra_message($response, $extra_message);

        // DIAG - Diagnostics - Ver 2.1.8
        // back_trace('NOTICE', '$response: ' . print_r($response, true));

        // Return response
        wp_send_json_success($response);

    }

    // DIAG - Diagnostics
    // back_trace( 'ERROR', 'Oops! I fell through the cracks!');
    global $chatbot_chatgpt_fixed_literal_messages;       
    // Define a default fallback message
    $default_message = 'Oops! I fell through the cracks!';
    $error_message = isset($chatbot_chatgpt_fixed_literal_messages[1]) 
        ? $chatbot_chatgpt_fixed_literal_messages[1] 
        : $default_message;

    // Send error response
    wp_send_json_error($error_message);

}

// Add action to send messages - Ver 1.0.0
add_action('wp_ajax_chatbot_chatgpt_send_message', 'chatbot_chatgpt_send_message');
add_action('wp_ajax_nopriv_chatbot_chatgpt_send_message', 'chatbot_chatgpt_send_message');

// Add action to upload files - Ver 1.7.6
add_action('wp_ajax_chatbot_chatgpt_upload_files', 'chatbot_chatgpt_upload_files');
add_action('wp_ajax_nopriv_chatbot_chatgpt_upload_files', 'chatbot_chatgpt_upload_files');

// Add action to upload files - Ver 1.7.6
add_action('wp_ajax_chatbot_chatgpt_upload_mp3', 'chatbot_chatgpt_upload_mp3');
add_action('wp_ajax_nopriv_chatbot_chatgpt_upload_mp3', 'chatbot_chatgpt_upload_mp3');

// Add action to erase conversation - Ver 1.8.6
add_action('wp_ajax_chatbot_chatgpt_erase_conversation', 'chatbot_chatgpt_erase_conversation_handler');
add_action('wp_ajax_nopriv_chatbot_chatgpt_erase_conversation', 'chatbot_chatgpt_erase_conversation_handler'); // For logged-out users, if needed

// Settings and Deactivation - Ver 1.5.0
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'chatbot_chatgpt_plugin_action_links');

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
    // back_trace( 'NOTICE', 'Markov Chain Status Activation');

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
    // back_trace( 'NOTICE', 'Markov Chain Status Deactivation');

    // Delete the build status option on deactivation
    delete_option('chatbot_markov_chain_build_status');

    // Clear any scheduled events related to the Markov Chain scan
    wp_clear_scheduled_hook('chatbot_markov_chain_scan_hook');

}
register_deactivation_hook(__FILE__, 'chatbot_markov_chain_status_deactivation');

// Transformer Model builder - Activation Hook - Ver 2.2.0
function chatbot_transformer_model_status_activation() {

    // DIAG - Diagnostics - Ver 2.2.0
    // back_trace( 'NOTICE', 'Transformer Model Status Activation');

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
    // back_trace( 'NOTICE', 'Transformer Model Status Deactivation');

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
    // back_trace( 'NOTICE', 'Plugin data: ' . print_r($plugin_data, true));
    // $plugin_version = $plugin_data['chatbot_chatgpt_version'];
    $plugin_version = $plugin_data['Version'];
    // $plugin_version = $chatbot_chatgpt_plugin_version;
    update_option('chatbot_chatgpt_plugin_version', $plugin_version);
    // DIAG - Log the plugin version
    // back_trace( 'NOTICE', 'Plugin version ' . $plugin_version);

    return $plugin_version;

}
