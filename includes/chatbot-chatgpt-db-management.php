<?php
/**
 * Chatbot ChatGPT for WordPress - Database Management for Reporting - Ver 1.6.3
 *
 * This file contains the code for table actions for reporting
 * to display the Chatbot ChatGPT on the website.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
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

    return;

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

    return;

}

// Hook it to 'plugins_loaded' so it runs on every WP load
add_action('plugins_loaded', 'chatbot_chatgpt_check_version');

// Hook to run the function when the plugin is activated
register_activation_hook(__FILE__, 'create_chatbot_chatgpt_interactions_table');

// Update Interaction Tracking - Ver 1.6.3
function update_interaction_tracking() {

    global $wpdb;

    // Check version and create table if necessary
    chatbot_chatgpt_check_version();

    // Get current date and table name
    $today = current_time('Y-m-d');
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_interactions';

    // Check if today's date already exists in the table
    $existing_count = $wpdb->get_var($wpdb->prepare("SELECT count FROM $table_name WHERE date = %s", $today));

    if ($existing_count !== null) {
        // If exists, increment the counter
        $wpdb->query($wpdb->prepare("UPDATE $table_name SET count = count + 1 WHERE date = %s", $today));
    } else {
        // If not, insert a new row with the date and set count as 1
        $wpdb->insert(
            $table_name,
            array(
                'date' => $today,
                'count' => 1
            ),
            array('%s', '%d')
        );
    }

    return;

}