<?php
/**
 * Chatbot ChatGPT for WordPress - Database Management for Reporting - Ver 1.6.3
 *
 * This file contains the code for table actions for reporting
 * to display the Chatbot ChatGPT on the website.
 *
 * @package chatbot-chatgpt
 */

// TODO If this file is called directly, abort.
if ( ! defined( 'WPINC' ) )
	die;

// Define version number - Ver 1.6.3
define('CHATBOT_CHATGPT_PLUGIN_VERSION', '1.6.3');

// Check version number - Ver 1.6.3
function chatbot_chatgpt_check_version() {
    $saved_version = esc_attr(get_option('chatbot_chatgpt_plugin_version'));

    if ($saved_version === false || version_compare(CHATBOT_CHATGPT_PLUGIN_VERSION, $saved_version, '>')) {
        // Do this for all version upgrades or fresh installs
        create_chatbot_chatgpt_interactions_table();

        if ($saved_version !== false && version_compare($saved_version, '1.6.3', '<')) {
            // Do anything specific for upgrading to 1.6.3 or greater
            // (but not for fresh installs)
        }

        // Do any other specific version upgrade checks here

        update_option('chatbot_chatgpt_plugin_version', CHATBOT_CHATGPT_PLUGIN_VERSION);
    }
}

// Create the interaction tracking table - Ver 1.6.3
function create_chatbot_chatgpt_interactions_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_interactions';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        date DATE PRIMARY KEY,
        count INT
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Hook it to 'plugins_loaded' so it runs on every WP load
add_action('plugins_loaded', 'chatbot_chatgpt_check_version');

// Hook to run the function when the plugin is activated
register_activation_hook(__FILE__, 'create_chatbot_chatgpt_interactions_table');
