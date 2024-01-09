<?php
/**
 * Chatbot ChatGPT for WordPress - Upgrade the chatbot-chatgpt plugin.
 *
 * This file contains the code for upgrading the plugin.
 * It should run with the plugin is activated, deactivated, or updated.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Activation Hook - Revised 1.7.6
function chatbot_chatgpt_activate() {

    // DIAG - Log the activation
    chatbot_chatgpt_back_trace( 'NOTICE', 'Plugin activation started');

    // Logic to run during activation
    chatbot_chatgpt_upgrade();

    // DIAG - Log the activation
    chatbot_chatgpt_back_trace( 'NOTICE', 'Plugin activation completed');

    return;

}

// Deactivation Hook - Revised 1.7.6
function chatbot_chatgpt_deactivate() {

    // DIAG - Log the activation
    chatbot_chatgpt_back_trace( 'NOTICE', 'Plugin deactivation started');

    // Logic to run during deactivation
    // FIXME - THIS IS NOT DELETEING THE PLUGIN - JUST DEACTIVATION

    // DIAG - Log the activation
    chatbot_chatgpt_back_trace( 'NOTICE', 'Plugin deactivation completed');

    return;

}

// Upgrade Hook for Plugin Update - Revised 1.7.6
function chatbot_chatgpt_upgrade_completed($upgrader_object, $options) {

    // DIAG - Log the activation
    chatbot_chatgpt_back_trace( 'NOTICE', 'Plugin upgrade started');

    if ($options['action'] == 'update' && $options['type'] == 'plugin') {
        foreach($options['plugins'] as $plugin) {
            if (plugin_basename(__FILE__) === $plugin) {

                // Logic to run during upgrade
                chatbot_chatgpt_upgrade();

                break;

            }
        }
    }

    // DIAG - Log the activation
    chatbot_chatgpt_back_trace( 'NOTICE', 'Plugin upgrade started');

    return;

}

// Upgrade Logic - Revised 1.7.6
function chatbot_chatgpt_upgrade() {

    // DIAG - Log the upgrade
    chatbot_chatgpt_back_trace( 'NOTICE', 'Plugin upgrade started');

    // Removed obsolete or replaced options
    if ( get_option( 'chatbot_chatgpt_crawler_status' ) ) {
        delete_option( 'chatbot_chatgpt_crawler_status' );
        chatbot_chatgpt_back_trace( 'NOTICE', 'chatbot_chatgpt_crawler_status option deleted');
    }

    // Add new or replaced options - chatbot_chatgpt_diagnostics
    if ( get_option( 'chatbot_chatgpt_diagnostics' ) ) {
        $diagnostics = get_option( 'chatbot_chatgpt_diagnostics' );
        if ( !$diagnostics || $diagnostics == '' || $diagnostics == ' ' ) {
            update_option( 'chatbot_chatgpt_diagnostics', 'No' );
        }
        chatbot_chatgpt_back_trace( 'NOTICE', 'chatbot_chatgpt_diagnostics option updated');
    }

    // Add new or replaced options - chatbot_chatgpt_plugin_version
    // If the old option exists, delete it
    if (get_option('chatgpt_plugin_version')) {
        delete_option('chatgpt_plugin_version');
        // DIAG - Log the old option deletion
        chatbot_chatgpt_back_trace('NOTICE', 'chatgpt_plugin_version option deleted');
    }

    // FIXME - DETERMINE WHAT OTHER 'OLD' OPTIONS SHOULD BE DELETED
    // FIXME - DETERMINE WHAT OPTION NAMES NEED TO BE CHANGED (DELETE, THEN REPLACE)

    // Add/update the option - chatbot_chatgpt_plugin_version
    $plugin_version = get_plugin_version();
    update_option('chatbot_chatgpt_plugin_version', $plugin_version);
    // DIAG - Log the plugin version
    chatbot_chatgpt_back_trace('NOTICE', 'chatbot_chatgpt_plugin_version option created');

    // Add new/replaced options - chatbot_chatgpt_interactions
    create_chatbot_chatgpt_interactions_table();
    // DIAG - Log the table creation
    chatbot_chatgpt_back_trace( 'NOTICE', 'chatbot_chatgpt_interactions table created');

    // Add new/replaced options - create_conversation_logging_table
    create_conversation_logging_table();
    // DIAG - Log the table creation
    chatbot_chatgpt_back_trace( 'NOTICE', 'chatbot_chatgpt_conversation_log table created');

    // DIAG - Log the upgrade compelete
    chatbot_chatgpt_back_trace( 'NOTICE', 'Plugin upgrade completed');

    return;

}

// Upgrade Logic - Revised 1.7.6
function chatbot_chatgpt_uninstall(){
    
        // DIAG - Log the uninstall
        chatbot_chatgpt_back_trace( 'NOTICE', 'Plugin uninstall started');
    
        // Removed obsolete or replaced options
        // FIXME - Ask what data should be removed
        // TBD
    
        // DIAG - Log the uninstall
        chatbot_chatgpt_back_trace( 'NOTICE', 'Plugin uninstall completed');

        return;
}

// Determine if the plugin is installed
function get_plugin_version() {

    if (!function_exists('get_plugin_data')) {
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }

    $plugin_data = get_plugin_data(plugin_dir_path(__FILE__) . '../chatbot-chatgpt.php');
    $plugin_version = $plugin_data['Version'];

    // DIAG - Log the plugin version
    chatbot_chatgpt_back_trace( 'NOTICE', 'Plugin version '. $plugin_version);

    return $plugin_version;

}

// Define version number - Ver 1.6.3
// define('CHATBOT_CHATGPT_PLUGIN_VERSION', '1.6.3');

// // Check version number - Ver 1.6.3
// function chatbot_chatgpt_check_version() {
//     $saved_version = esc_attr(get_option('chatbot_chatgpt_plugin_version'));

//     if ($saved_version === false || version_compare(CHATBOT_CHATGPT_PLUGIN_VERSION, $saved_version, '>')) {
//         // Do this for all version upgrades or fresh installs
//         create_chatbot_chatgpt_interactions_table();

//         if ($saved_version !== false && version_compare($saved_version, '1.6.3', '<')) {
//             // Do anything specific for upgrading to 1.6.3 or greater
//             // (but not for fresh installs)
//         }

//         // Do any other specific version upgrade checks here

//         update_option('chatbot_chatgpt_plugin_version', CHATBOT_CHATGPT_PLUGIN_VERSION);
//     }

//     return;

// }
// // Hook it to 'plugins_loaded' so it runs on every WP load
// add_action('plugins_loaded', 'chatbot_chatgpt_check_version');
