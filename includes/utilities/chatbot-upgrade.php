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

    // DIAG - Log the activation
    // back_trace( 'NOTICE', 'Plugin activation started');

    // Logic to run during activation
    chatbot_chatgpt_upgrade();

    // Handle unexpect output during activation - Ver 2.0.6 - 2024 07 10
    $unexpected_output = ob_get_clean();
    if (!empty($unexpected_output)) {
        // Log or handle unexpected output
        error_log('Unexpected output during plugin activation: ' . $unexpected_output);
    }

    // DIAG - Log the activation
    // back_trace( 'NOTICE', 'Plugin activation completed');

    return;

}

// Upgrade Hook for Plugin Update - Revised 1.7.6
function chatbot_chatgpt_upgrade_completed($upgrader_object, $options) {

    // DIAG - Log the activation
    // back_trace( 'NOTICE', 'Plugin upgrade started');

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
            // DIAG - Log the warning
            // back_trace( 'WARNING', '"plugins" key is not set or not an array');
        }
    }

    // DIAG - Log the activation
    // back_trace( 'NOTICE', 'Plugin upgrade started');

    return;

}

// Upgrade Logic - Revised 1.7.6
function chatbot_chatgpt_upgrade() {

    // DIAG - Log the upgrade
    // back_trace( 'NOTICE', 'Plugin upgrade started');

    // Removed obsolete or replaced options
    if ( esc_attr(get_option( 'chatbot_chatgpt_crawler_status' )) ) {
        delete_option( 'chatbot_chatgpt_crawler_status' );
        // back_trace( 'NOTICE', 'chatbot_chatgpt_crawler_status option deleted');
    }

    // Add new or replaced options - chatbot_chatgpt_diagnostics
    if (esc_attr( get_option( 'chatbot_chatgpt_diagnostics' )) ) {
        $diagnostics = esc_attr(get_option( 'chatbot_chatgpt_diagnostics' ));
        if ( !$diagnostics || $diagnostics == '' || $diagnostics == ' ' ) {
            update_option( 'chatbot_chatgpt_diagnostics', 'No' );
        }
        // back_trace( 'NOTICE', 'chatbot_chatgpt_diagnostics option updated');
    }

    // Add new or replaced options - chatbot_chatgpt_plugin_version
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_plugin_version') )) {
        delete_option( 'chatgpt_plugin_version' );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatgpt_plugin_version option deleted');
    }

    // Replace option - chatbot_chatgpt_width_setting
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatbot_width_setting' ))) {
        $chatbot_chatgpt_width_setting = esc_attr(get_option( 'chatbot_width_setting' ));
        delete_option( 'chatbot_width_setting' );
        update_option( 'chatbot_chatgpt_width_setting', $chatbot_chatgpt_width_setting );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatbot_width_setting option deleted');
    }

    // Replace option - chatbot_chatgpt_api_key
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_api_key' ))) {
        $chatbot_chatgpt_api_key = esc_attr(get_option( 'chatgpt_api_key' ));
        delete_option( 'chatgpt_api_key' );
        update_option( 'chatbot_chatgpt_api_key', $chatbot_chatgpt_api_key );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatgpt_api_key option deleted');
    }

    // Replace option - chatbot_chatgpt_avatar_greeting_setting
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_avatar_greeting_setting' ))) {
        $chatbot_chatgpt_avatar_greeting_setting = esc_attr(get_option( 'chatgpt_avatar_greeting_setting' ));
        delete_option( 'chatgpt_avatar_greeting_setting' );
        update_option( 'chatbot_chatgpt_avatar_greeting_setting', $chatbot_chatgpt_avatar_greeting_setting );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatbot_chatgpt_avatar_greeting_setting option deleted');
    }

    // Replace option - chatgpt_avatar_icon_setting
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_avatar_icon_setting' ))) {
        $chatbot_chatgpt_avatar_greeting_setting = esc_attr(get_option( 'chatgpt_avatar_icon_setting' ));
        delete_option( 'chatgpt_avatar_icon_setting' );
        update_option( 'chatbot_chatgpt_avatar_icon_setting', $chatbot_chatgpt_avatar_greeting_setting );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatgpt_avatar_icon_setting option deleted');
    }

    // Replace option - chatgpt_avatar_icon_setting
    // If the old option exists, delete it
    if (esc_attr(get_option ( 'chatbot_chatgpt_avatar_icon' ))) {
        delete_option( 'chatbot_chatgpt_avatar_icon' );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatbot_chatgpt_avatar_icon option replaced');
    }

    // Replace option - chatgpt_avatar_icon_setting
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_avatar_icon_url_setting' ))) {
        $chatbot_chatgpt_avatar_icon_url_setting = esc_attr(get_option( 'chatgpt_avatar_icon_url_setting' ));
        delete_option( 'chatgpt_avatar_icon_url_setting' );
        update_option( 'chatbot_chatgpt_avatar_icon_url_setting', $chatbot_chatgpt_avatar_icon_url_setting );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatgpt_avatar_icon_url_setting option deleted');
    }

    // Replace option - chatgpt_bot_name
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_bot_name' ))) {
        $chatbot_chatgpt_bot_name = esc_attr(get_option( 'chatgpt_bot_name' ));
        delete_option( 'chatgpt_bot_name' );
        update_option( 'chatbot_chatgpt_bot_name', $chatbot_chatgpt_bot_name );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatgpt_bot_name option deleted');
    }

    // Replace option - chatgpt_custom_avatar_icon_setting
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_custom_avatar_icon_setting' ))) {
        $chatbot_chatgpt_custom_avatar_icon_setting = esc_attr(get_option( 'chatgpt_custom_avatar_icon_setting' ));
        delete_option( 'chatgpt_custom_avatar_icon_setting' );
        update_option( 'chatbot_chatgpt_custom_avatar_icon_setting', $chatbot_chatgpt_custom_avatar_icon_setting );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatgpt_custom_avatar_icon_setting option deleted');
    }

    // Replace option - chatgpt_diagnostics
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_diagnostics' ))) {
        $chatbot_chatgpt_diagnostics = esc_attr(get_option( 'chatgpt_diagnostics' ));
        delete_option( 'chatgpt_diagnostics' );
        update_option( 'chatbot_chatgpt_diagnostics', $chatbot_chatgpt_diagnostics );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatgpt_diagnostics option deleted');
    }

    // Replace option - chatgpt_disclaimer_setting
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_disclaimer_setting' ))) {
        $chatbot_chatgpt_disclaimer_setting = esc_attr(get_option( 'chatgpt_disclaimer_setting' ));
        delete_option( 'chatgpt_disclaimer_setting' );
        update_option( 'chatbot_chatgpt_disclaimer_setting', $chatbot_chatgpt_disclaimer_setting );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatgpt_disclaimer_setting option deleted');
    }

    // Replace option - chatgpt_initial_greeting
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_initial_greeting' ))) {
        $chatbot_chatgpt_initial_greeting = esc_attr(get_option( 'chatgpt_initial_greeting' ));
        delete_option( 'chatgpt_initial_greeting' );
        update_option( 'chatbot_chatgpt_initial_greeting', $chatbot_chatgpt_initial_greeting );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatgpt_initial_greeting option deleted');
    }

    // Replace option - chatgpt_max_tokens_setting
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_max_tokens_setting' ))) {
        $chatbot_chatgpt_max_tokens_setting = esc_attr(get_option( 'chatgpt_max_tokens_setting' ));
        delete_option( 'chatgpt_max_tokens_setting' );
        update_option( 'chatbot_chatgpt_max_tokens_setting', $chatbot_chatgpt_max_tokens_setting );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatgpt_max_tokens_setting option deleted');
    }

    // Replace option - chatgpt_model_choice
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_model_choice' ))) {
        $chatbot_chatgpt_model_choice = esc_attr(get_option( 'chatgpt_model_choice' ));
        delete_option( 'chatgpt_model_choice' );
        update_option( 'chatbot_chatgpt_model_choice', $chatbot_chatgpt_model_choice );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatgpt_model_choice option deleted');
    }

    // Replace option - chatgptStartStatusNewVisitor
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgptStartStatusNewVisitor' ))) {
        $chatbot_chatgpt_start_status_new_visitor = esc_attr(get_option( 'chatgptStartStatusNewVisitor' ));
        delete_option( 'chatgptStartStatusNewVisitor' );
        update_option( 'chatbot_chatgpt_start_status_new_visitor', $chatbot_chatgpt_start_status_new_visitor );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatgptStartStatusNewVisitor option deleted');
    }
    if (esc_attr(get_option( 'chatgpt_start_status' ))) {
        delete_option( 'chatgpt_start_status' );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatgpt_start_status option deleted');
    }

    // Replace option - chatgptstartstatus
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgptstartstatus' ))) {
        $chatbot_chatgpt_start_status = esc_attr(get_option( 'chatgptstartstatus' ));
        delete_option( 'chatgptstartstatus' );
        update_option( 'chatbot_chatgpt_start_status', $chatbot_chatgpt_start_status );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatgptstartstatus option deleted');
    }

    // Replace option - chatgpt_chatbot_bot_prompt
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_chatbot_bot_prompt' ))) {
        $chatbot_chatgpt_bot_prompt = esc_attr(get_option( 'chatgpt_chatbot_bot_prompt' ));
        delete_option( 'chatgpt_chatbot_bot_prompt' );
        update_option( 'chatbot_chatgpt_bot_prompts', $chatbot_chatgpt_bot_prompt );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatgpt_chatbot_bot_prompt option deleted');
    }

    // Replace option - chatgpt_subsequent_greeting
    // If the old option exists, delete it
    if (esc_attr(get_option( 'chatgpt_subsequent_greeting' ))) {
        $chatbot_chatgpt_subsequent_greeting = esc_attr(get_option( 'chatgpt_subsequent_greeting' ));
        delete_option( 'chatgpt_subsequent_greeting' );
        update_option( 'chatbot_chatgpt_subsequent_greeting', $chatbot_chatgpt_subsequent_greeting );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatgpt_subsequent_greeting option deleted');
    }

    // Replace option - chatGPTChatBotStatus
    if (esc_attr(get_option( 'chatGPTChatBotStatus' ))) {
        delete_option( 'chatGPTChatBotStatus' );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatGPTChatBotStatus option deleted');
    }

    // Replace option - chatGPTChatBotStatusNewVisitor
    if (esc_attr(get_option( 'chatGPTChatBotStatusNewVisitor' ))) {
        delete_option( 'chatGPTChatBotStatusNewVisitor' );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatGPTChatBotStatusNewVisitor option deleted');
    }

    // Replace option - chatbot_kn_items_per_batch
    if (esc_attr(get_option( 'chatbot_kn_items_per_batch' ))) {
        $chatbot_chatgpt_kn_items_per_batch = esc_attr(get_option( 'chatbot_kn_items_per_batch' ));
        delete_option( 'chatbot_kn_items_per_batch' );
        update_option( 'chatbot_chatgpt_kn_items_per_batch', $chatbot_chatgpt_kn_items_per_batch );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatbot_kn_items_per_batch option deleted');
    }

    // Replace option - no_of_items_analyzed
    if (esc_attr(get_option( 'no_of_items_analyzed' ))) {
        $chatbot_chatgpt_no_of_items_analyzed = esc_attr(get_option( 'no_of_items_analyzed' ));
        delete_option( 'no_of_items_analyzed' );
        update_option( 'chatbot_chatgpt_no_of_items_analyzed', $chatbot_chatgpt_no_of_items_analyzed );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'no_of_items_analyzed option deleted');
    }

    // Reset the Knowledge Navigator reminder option
    if (esc_attr(get_option( 'chatbot_chatgpt_kn_dismissed' ))) {
        delete_option( 'chatbot_chatgpt_kn_dismissed' );
        // DIAG - Log the old option deletion
        // back_trace( 'NOTICE', 'chatbot_chatgpt_kn_dismissed option deleted');
    }

    // Replace option - chatbot_chatgpt_enable_custom_buttons - Ver 2.0.5
    $chatbot_chatgpt_enable_custom_buttons = esc_attr(get_option( 'chatbot_chatgpt_enable_custom_buttons' ));
    if ($chatbot_chatgpt_enable_custom_buttons == 'On') {
        $chatbot_chatgpt_enable_custom_buttons = 'Floating Only';
        update_option('chatbot_chatgpt_enable_custom_buttons', 'Floating Only');
    }

    // FIXME - DETERMINE WHAT OTHER 'OLD' OPTIONS SHOULD BE DELETED
    // FIXME - DETERMINE WHAT OPTION NAMES NEED TO BE CHANGED (DELETE, THEN REPLACE)

    // Add/update the option - chatbot_chatgpt_plugin_version
    global $chatbot_chatgpt_plugin_version;
    $plugin_version = $chatbot_chatgpt_plugin_version;
    update_option('chatbot_chatgpt_plugin_version', $plugin_version);
    // DIAG - Log the plugin version
    // back_trace( 'NOTICE', 'chatbot_chatgpt_plugin_version option created');

    // Add new/replaced options - chatbot_chatgpt_interactions
    create_chatbot_chatgpt_interactions_table();
    // DIAG - Log the table creation
    // back_trace( 'NOTICE', 'chatbot_chatgpt_interactions table created');

    // Add new/replaced options - create_conversation_logging_table
    create_conversation_logging_table();
    // DIAG - Log the table creation
    // back_trace( 'NOTICE', 'chatbot_chatgpt_conversation_log table created');

    // Ensure sentiment_score column exists for existing installations
    add_sentiment_score_column_to_existing_table();
    // DIAG - Log the column addition
    // back_trace( 'NOTICE', 'sentiment_score column ensured');

    // DIAG - Log the upgrade complete
    // back_trace( 'NOTICE', 'Plugin upgrade completed');

    return;

}
