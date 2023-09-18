<?php
/**
 * Chatbot ChatGPT for WordPress - Knowlege Navigator - Database Management - Ver 1.6.3
 *
 * This file contains the code for table actions for reporting
 * to display the Chatbot ChatGPT on the website.
 *
 * @package chatbot-chatgpt
 */

// TODO If this file is called directly, abort.
if ( ! defined( 'WPINC' ) )
	die;

// Database Management - drop the table if it exists, then add it if it doesn't exist - Ver 1.6.3
function dbKNStore() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_knowledge_base';

    // Drop table if it exists
    $wpdb->query("DROP TABLE IF EXISTS $table_name");

    // SQL to create a new table
    $sql = "CREATE TABLE $table_name (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        url TEXT NOT NULL,
        title TEXT,
        top_word TEXT
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Execute SQL query and create the table
    if(dbDelta($sql)) {
        return true;  // Table created successfully
    } else {
        return false;  // Table creation failed
    }
}
