<?php
/**
 * Kognetiks Chatbot - Upgrade the Plugin
 *
 * This file contains the code for upgrading the plugin.
 * It should run with the plugin is activated or updated.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Activation Hook - Revised 1.7.6
function chatbot_chatgpt_activate() {

    // DIAG - Diagnostics - Ver 2.4.5
    if ( defined('WP_DEBUG') && WP_DEBUG ) {
        error_log('[Chatbot] [chatbot-upgrade.php] activate: function called');
    }

    // Logic to run during activation
    chatbot_chatgpt_upgrade();

    // Handle unexpect output during activation - Ver 2.0.6 - 2024 07 10
    $unexpected_output = ob_get_clean();
    if (!empty($unexpected_output)) {
        // Log or handle unexpected output
        if ( defined('WP_DEBUG') && WP_DEBUG ) {
            error_log('[Chatbot] [chatbot-upgrade.php] Unexpected output during plugin activation: ' . $unexpected_output);
        }
    }

    return;

}

// Upgrade Hook for Plugin Update - Revised 1.7.6
function chatbot_chatgpt_upgrade_completed($upgrader_object, $options) {

    // DIAG - Diagnostics - Ver 2.4.5
    // error_log('chatbot_chatgpt_upgrade_completed');

    if ($options['action'] == 'update' && $options['type'] == 'plugin') {
        if (isset($options['plugins']) && is_array($options['plugins'])) {
            foreach($options['plugins'] as $plugin) {
                if (plugin_basename(__FILE__) === $plugin) {
                    // Logic to run during upgrade
                    chatbot_chatgpt_upgrade();
                    break;
                }
            }
        } else {
            // Do nothing
        }
    }

    return;

}

// Upgrade Logic - Revised 1.7.6
function chatbot_chatgpt_upgrade() {

    // DIAG - Diagnostics - Ver 2.4.5
    if ( defined('WP_DEBUG') && WP_DEBUG ) {
        error_log('[Chatbot] [chatbot-upgrade.php] upgrade: function called');
    }

    // Removed obsolete or replaced options
    if ( esc_attr(get_option( 'chatbot_chatgpt_crawler_status' )) ) {
        delete_option( 'chatbot_chatgpt_crawler_status' );
    }

    // Add new or replaced options - chatbot_chatgpt_diagnostics
    if (esc_attr( get_option( 'chatbot_chatgpt_diagnostics' )) ) {
        $diagnostics = esc_attr(get_option( 'chatbot_chatgpt_diagnostics' ));
        if ( !$diagnostics || $diagnostics == '' || $diagnostics == ' ' ) {
            update_option( 'chatbot_chatgpt_diagnostics', 'No' );
        }
    }

    // Add new or replaced options - chatbot_chatgpt_plugin_version
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_plugin_version') )) {
        delete_option( 'chatgpt_plugin_version' );
    }

    // Replace option - chatbot_chatgpt_width_setting
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatbot_width_setting' ))) {
        $chatbot_chatgpt_width_setting = esc_attr(get_option( 'chatbot_width_setting' ));
        delete_option( 'chatbot_width_setting' );
        update_option( 'chatbot_chatgpt_width_setting', $chatbot_chatgpt_width_setting );
    }

    // Replace option - chatbot_chatgpt_api_key
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_api_key' ))) {
        $chatbot_chatgpt_api_key = esc_attr(get_option( 'chatgpt_api_key' ));
        delete_option( 'chatgpt_api_key' );
        update_option( 'chatbot_chatgpt_api_key', $chatbot_chatgpt_api_key );
    }

    // Replace option - chatbot_chatgpt_avatar_greeting_setting
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_avatar_greeting_setting' ))) {
        $chatbot_chatgpt_avatar_greeting_setting = esc_attr(get_option( 'chatgpt_avatar_greeting_setting' ));
        delete_option( 'chatgpt_avatar_greeting_setting' );
        update_option( 'chatbot_chatgpt_avatar_greeting_setting', $chatbot_chatgpt_avatar_greeting_setting );
    }

    // Replace option - chatgpt_avatar_icon_setting
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_avatar_icon_setting' ))) {
        $chatbot_chatgpt_avatar_greeting_setting = esc_attr(get_option( 'chatgpt_avatar_icon_setting' ));
        delete_option( 'chatgpt_avatar_icon_setting' );
        update_option( 'chatbot_chatgpt_avatar_icon_setting', $chatbot_chatgpt_avatar_greeting_setting );
    }

    // Replace option - chatgpt_avatar_icon_setting
    // If the old option exists, delete it
    if (esc_attr(get_option ( 'chatbot_chatgpt_avatar_icon' ))) {
        delete_option( 'chatbot_chatgpt_avatar_icon' );
    }

    // Replace option - chatgpt_avatar_icon_setting
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_avatar_icon_url_setting' ))) {
        $chatbot_chatgpt_avatar_icon_url_setting = esc_attr(get_option( 'chatgpt_avatar_icon_url_setting' ));
        delete_option( 'chatgpt_avatar_icon_url_setting' );
        update_option( 'chatbot_chatgpt_avatar_icon_url_setting', $chatbot_chatgpt_avatar_icon_url_setting );
    }

    // Replace option - chatgpt_bot_name
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_bot_name' ))) {
        $chatbot_chatgpt_bot_name = esc_attr(get_option( 'chatgpt_bot_name' ));
        delete_option( 'chatgpt_bot_name' );
        update_option( 'chatbot_chatgpt_bot_name', $chatbot_chatgpt_bot_name );
    }

    // Replace option - chatgpt_custom_avatar_icon_setting
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_custom_avatar_icon_setting' ))) {
        $chatbot_chatgpt_custom_avatar_icon_setting = esc_attr(get_option( 'chatgpt_custom_avatar_icon_setting' ));
        delete_option( 'chatgpt_custom_avatar_icon_setting' );
        update_option( 'chatbot_chatgpt_custom_avatar_icon_setting', $chatbot_chatgpt_custom_avatar_icon_setting );
    }

    // Replace option - chatgpt_diagnostics
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_diagnostics' ))) {
        $chatbot_chatgpt_diagnostics = esc_attr(get_option( 'chatgpt_diagnostics' ));
        delete_option( 'chatgpt_diagnostics' );
        update_option( 'chatbot_chatgpt_diagnostics', $chatbot_chatgpt_diagnostics );
    }

    // Replace option - chatgpt_disclaimer_setting
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_disclaimer_setting' ))) {
        $chatbot_chatgpt_disclaimer_setting = esc_attr(get_option( 'chatgpt_disclaimer_setting' ));
        delete_option( 'chatgpt_disclaimer_setting' );
        update_option( 'chatbot_chatgpt_disclaimer_setting', $chatbot_chatgpt_disclaimer_setting );
    }

    // Replace option - chatgpt_initial_greeting
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_initial_greeting' ))) {
        $chatbot_chatgpt_initial_greeting = esc_attr(get_option( 'chatgpt_initial_greeting' ));
        delete_option( 'chatgpt_initial_greeting' );
        update_option( 'chatbot_chatgpt_initial_greeting', $chatbot_chatgpt_initial_greeting );
    }

    // Replace option - chatgpt_max_tokens_setting
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_max_tokens_setting' ))) {
        $chatbot_chatgpt_max_tokens_setting = esc_attr(get_option( 'chatgpt_max_tokens_setting' ));
        delete_option( 'chatgpt_max_tokens_setting' );
    }

    // Replace option - chatgpt_model_choice
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_model_choice' ))) {
        $chatbot_chatgpt_model_choice = esc_attr(get_option( 'chatgpt_model_choice' ));
        delete_option( 'chatgpt_model_choice' );
        update_option( 'chatbot_chatgpt_model_choice', $chatbot_chatgpt_model_choice );
    }

    // Replace option - chatgptStartStatusNewVisitor
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgptStartStatusNewVisitor' ))) {
        $chatbot_chatgpt_start_status_new_visitor = esc_attr(get_option( 'chatgptStartStatusNewVisitor' ));
        delete_option( 'chatgptStartStatusNewVisitor' );
        update_option( 'chatbot_chatgpt_start_status_new_visitor', $chatbot_chatgpt_start_status_new_visitor );
    }
    if (esc_attr(get_option( 'chatgpt_start_status' ))) {
        delete_option( 'chatgpt_start_status' );
    }

    // Replace option - chatgptstartstatus
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgptstartstatus' ))) {
        $chatbot_chatgpt_start_status = esc_attr(get_option( 'chatgptstartstatus' ));
        delete_option( 'chatgptstartstatus' );
        update_option( 'chatbot_chatgpt_start_status', $chatbot_chatgpt_start_status );
    }

    // Replace option - chatgpt_chatbot_bot_prompt
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_chatbot_bot_prompt' ))) {
        $chatbot_chatgpt_bot_prompt = esc_attr(get_option( 'chatgpt_chatbot_bot_prompt' ));
        delete_option( 'chatgpt_chatbot_bot_prompt' );
        update_option( 'chatbot_chatgpt_bot_prompts', $chatbot_chatgpt_bot_prompt );
    }

    // Replace option - chatgpt_subsequent_greeting
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_subsequent_greeting' ))) {
        $chatbot_chatgpt_subsequent_greeting = esc_attr(get_option( 'chatgpt_subsequent_greeting' ));
        delete_option( 'chatgpt_subsequent_greeting' );
        update_option( 'chatbot_chatgpt_subsequent_greeting', $chatbot_chatgpt_subsequent_greeting );
    }

    // Replace option - chatGPTChatBotStatus
    if (esc_attr(get_option( 'chatGPTChatBotStatus' ))) {
        delete_option( 'chatGPTChatBotStatus' );
    }

    // Replace option - chatGPTChatBotStatusNewVisitor
    if (esc_attr(get_option( 'chatGPTChatBotStatusNewVisitor' ))) {
        delete_option( 'chatGPTChatBotStatusNewVisitor' );
    }

    // Replace option - chatbot_kn_items_per_batch
    if (esc_attr(get_option( 'chatbot_kn_items_per_batch' ))) {
        $chatbot_chatgpt_kn_items_per_batch = esc_attr(get_option( 'chatbot_kn_items_per_batch' ));
        delete_option( 'chatbot_kn_items_per_batch' );
        update_option( 'chatbot_chatgpt_kn_items_per_batch', $chatbot_chatgpt_kn_items_per_batch );
    }

    // Replace option - no_of_items_analyzed
    if (esc_attr(get_option( 'no_of_items_analyzed' ))) {
        $chatbot_chatgpt_no_of_items_analyzed = esc_attr(get_option( 'no_of_items_analyzed' ));
        delete_option( 'no_of_items_analyzed' );
        update_option( 'chatbot_chatgpt_no_of_items_analyzed', $chatbot_chatgpt_no_of_items_analyzed );
    }

    // Reset the Knowledge Navigator reminder option
    if (esc_attr(get_option( 'chatbot_chatgpt_kn_dismissed' ))) {
        delete_option( 'chatbot_chatgpt_kn_dismissed' );
    }

    // Replace option - chatbot_chatgpt_enable_custom_buttons - Ver 2.0.5
    $chatbot_chatgpt_enable_custom_buttons = esc_attr(get_option( 'chatbot_chatgpt_enable_custom_buttons' ));
    if ($chatbot_chatgpt_enable_custom_buttons == 'On') {
        $chatbot_chatgpt_enable_custom_buttons = 'Floating Only';
        update_option('chatbot_chatgpt_enable_custom_buttons', 'Floating Only');
    }

    // FIXME - DETERMINE WHAT OTHER 'OLD' OPTIONS SHOULD BE DELETED
    // FIXME - DETERMINE WHAT OPTION NAMES NEED TO BE CHANGED (DELETE, THEN REPLACE)

    // Remove legacy chatbot_chatgpt_plugin_version option if it exists (replaced by chatbot_chatgpt_version_installed in Ver 2.4.1)
    if (get_option('chatbot_chatgpt_plugin_version', false) !== false) {
        delete_option('chatbot_chatgpt_plugin_version');
    }
    
    // Track installed version for upgrade detection - Ver 2.4.1
    // Use multisite-aware options if plugin is network-activated
    $is_network = false;
    if (is_multisite()) {
        // Try to detect if plugin is network-activated
        if (!function_exists('is_plugin_active_for_network')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        $plugin_file = 'chatbot-chatgpt/chatbot-chatgpt.php';
        $is_network = is_plugin_active_for_network($plugin_file);
    }
    
    $get_opt = $is_network ? 'get_site_option' : 'get_option';
    $update_opt = $is_network ? 'update_site_option' : 'update_option';
    $delete_opt = $is_network ? 'delete_site_option' : 'delete_option';
    
    global $chatbot_chatgpt_plugin_version;
    $plugin_version = isset($chatbot_chatgpt_plugin_version) ? $chatbot_chatgpt_plugin_version : '';
    
    $installed_version = call_user_func($get_opt, 'chatbot_chatgpt_version_installed', '');
    if ($installed_version !== $plugin_version) {
        call_user_func($update_opt, 'chatbot_chatgpt_version_installed', $plugin_version);
        // If version changed (upgrade scenario), clear reporting notice dismissal and snooze
        if (!empty($installed_version) && $installed_version !== $plugin_version) {
            call_user_func($update_opt, 'chatbot_chatgpt_reporting_notice_dismissed', '0');
            call_user_func($delete_opt, 'chatbot_chatgpt_reporting_notice_snooze_until');
        }
    }

    // Add new/replaced options - chatbot_chatgpt_interactions
    create_chatbot_chatgpt_interactions_table();

    // Add new/replaced options - create_conversation_logging_table
    create_conversation_logging_table();

    // Ensure sentiment_score column exists for existing installations
    if (function_exists('chatbot_chatgpt_add_sentiment_score_column')) {
        chatbot_chatgpt_add_sentiment_score_column();
    }

    return;

}
