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
if ( ! defined( 'WPINC' ) )
die;

// FIXME - THIS IS NOT WORKING AS EXPECTED - Ver 1.6.7

// If the plugin is updated, run the upgrade function.
function chatbot_chatgpt_upgrade_completed($upgrader_object, $options) {

    // DIAG - Log the upgrade.
    // error_log( 'Chatbot ChatGPT: chatbot_chatgpt_upgrade_completed() - Started' );
    // error_log( 'Chatbot ChatGPT: $upgrader_object: ' . print_r( $upgrader_object, true ) );
    // error_log( 'Chatbot ChatGPT: $options: ' . print_r( $options, true ) );

    // Check if our plugin was updated
    if (is_array($options) && isset($options['action']) && isset($options['type']) && $options['action'] == 'update' && $options['type'] == 'plugin' ) {
        foreach($options['plugins'] as $plugin) {
            if ($plugin == plugin_basename(__FILE__)) {
                // DIAG - Log the action.
                // error_log( 'Chatbot ChatGPT: Plugin Upgraded' );
                // Our plugin was updated. Run the upgrade function.
                chatbot_chatgpt_upgrade();
            }
        }
    }

    // DIAG - Log the upgrade.
    // error_log( 'Chatbot ChatGPT: chatbot_chatgpt_upgrade_completed() - Completed' );

    return;

}
// FIXME - THIS IS NOT WORKING AS EXPECTED - Ver 1.6.7
add_action('upgrader_process_complete', 'chatbot_chatgpt_upgrade_completed', 10, 2);

// If the plugin is activated or deactivated, run the upgrade function.
function chatbot_chatgpt_upgrade_activation_deactivation($upgrader_object, $options) {

    // DIAG - Log the upgrade.
    // error_log( 'Chatbot ChatGPT: chatbot_chatgpt_upgrade_activation_deactivation() - Started' );
    // error_log( 'Chatbot ChatGPT: $upgrader_object: ' . print_r( $upgrader_object, true ) );
    // error_log( 'Chatbot ChatGPT: $options: ' . print_r( $options, true ) );

    // Check if our plugin was activated
    if (is_array($options) && isset($options['action']) && isset($options['type']) && $options['action'] == 'activate' && $options['type'] == 'plugin' ) {
        foreach($options['plugins'] as $plugin) {
            if ($plugin == plugin_basename(__FILE__)) {
                // DIAG - Log the action.
                // error_log( 'Chatbot ChatGPT: Plugin Activation' );
                // Our plugin was activated. Run the upgrade function.
                chatbot_chatgpt_upgrade();
            }
        }
    }

    // Check if our plugin was deactivated
    if (is_array($options) && isset($options['action']) && isset($options['type']) && $options['action'] == 'deactivate' && $options['type'] == 'plugin' ) {
        foreach($options['plugins'] as $plugin) {
            if ($plugin == plugin_basename(__FILE__)) {
                // DIAG - Log the action.
                // error_log( 'Chatbot ChatGPT: Plugin Deactivation' );
                // Our plugin was deactivated.
                // TODO - Add code to run when plugin is deactivated.
            }
        }
    }
    
    // DIAG - Log the upgrade.
    // error_log( 'Chatbot ChatGPT: chatbot_chatgpt_upgrade_activation_deactivation() - Completed' );

    return;

}
// FIXME - THIS IS NOT WORKING AS EXPECTED - Ver 1.6.7
register_activation_hook(__FILE__, 'chatbot_chatgpt_upgrade_activation_deactivation');

// If updating the plugin, run the upgrade function.
function chatbot_chatgpt_upgrade() {

    // DIAG - Log the upgrade.
    // error_log( 'Chatbot ChatGPT: chatbot_chatgpt_upgrade() - Started' );

    // Get the current version of the plugin.
    $version = get_option( 'chatbot_chatgpt_plugin_version' );

    // If the plugin is not installed, set the version to 0.
    if ( ! $version ) {
        $version = '0';
    }

    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

    // If the plugin is installed but not activated, set the version to 0.
    if ( $version && ! is_plugin_active( plugins_url('', __FILE__ ) . '/chatbot-chatgpt.php' ) ) {
        $version = '0';
    }

    // If the plugin is installed and activated, run the upgrade function.
    if ( $version && is_plugin_active( 'chatbot-chatgpt/chatbot-chatgpt.php' ) ) {
        // If the plugin is version 1.0.0 or older, run the upgrade function.
        if ( version_compare( $version, '1.6.7', '<' ) ) {
            chatbot_chatgpt_upgrade_167();
            // DIAG - Log the currrent plugin version.
            // error_log( 'Chatbot ChatGPT: Current version is ' . $version );
        }
    }

    // DIAG - Log the upgrade.
    // error_log( 'Chatbot ChatGPT: chatbot_chatgpt_upgrade() - Completed' );

    return;
}


// Udgrade the plugin to version 1.6.7.
function chatbot_chatgpt_upgrade_167() {

    // DIAG - Log the upgrade.
    // error_log( 'Chatbot ChatGPT: chatbot_chatgpt_upgrade_167() - Started' );

    // Determine if option chatbot_chatgpt_crawler_status is in the options table.
    // If it is then remove it.
    if ( get_option( 'chatbot_chatgpt_crawler_status' ) ) {
        delete_option( 'chatbot_chatgpt_crawler_status' );
    }

    // Determine if option chatbot_chatgpt_diagnostics is in the options table.
    // If it is and the value is null or empty or blank then set it to No.
    if ( get_option( 'chatbot_chatgpt_diagnostics' ) ) {
        $diagnostics = get_option( 'chatbot_chatgpt_diagnostics' );
        if ( ! $diagnostics ) {
            update_option( 'chatbot_chatgpt_diagnostics', 'No' );
        }
        if ( $diagnostics == '' ) {
            update_option( 'chatbot_chatgpt_diagnostics', 'No' );
        }
        if ( $diagnostics == ' ' ) {
            update_option( 'chatbot_chatgpt_diagnostics', 'No' );
        }
    }

    // Determine if option chatgpt_plugin_version is in the options table.
    // If it is then remove it and add option chatbot_chatgpt_plugin_version only chatbot_chatgpt_plugin_version isn't in the options table.
    if ( get_option( 'chatgpt_plugin_version' ) ) {
        delete_option( 'chatgpt_plugin_version' );
        if ( ! get_option( 'chatbot_chatgpt_plugin_version' ) ) {
            add_option( 'chatbot_chatgpt_plugin_version', '1.6.7' );
        }
    }

    // DIAG - Log the upgrade.
    // error_log( 'Chatbot ChatGPT: chatbot_chatgpt_upgrade_167() - Completed' );

    return;

}