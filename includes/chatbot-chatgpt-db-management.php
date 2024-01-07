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
if ( ! defined( 'WPINC' ) ) {
    die;
}

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

// Converation Tracking - Ver 1.7.6
function create_conversation_logging_table() {
    global $wpdb;
    global $sessionId;
    global $thread_Id;
    global $assistant_Id;
    global $user_id;
    global $page_id;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log'; 

    // SQL to create the conversation logging table
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        session_id VARCHAR(255) NOT NULL,
        user_id VARCHAR(255),
        page_id VARCHAR(255),
        interaction_time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        user_type ENUM('chatbot', 'visitor') NOT NULL,
        thread_id VARCHAR(255),
        assistant_id VARCHAR(255),
        message_text text NOT NULL,
        PRIMARY KEY  (id),
        INDEX (session_id),
        INDEX (user_id)
    );";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Log errors or notify admin if there was an error
    if (!empty($wpdb->last_error)) {
        // DIAG - Diagnostics
        chatbot_chatgpt_back_trace( 'ERROR', 'Error creating conversation logging table: ' . $wpdb->last_error);
    }

    return;

}

// Hook to run the function during plugin activation - Ver 1.7.6
register_activation_hook(__FILE__, 'create_conversation_logging_table');

// Append message to conversation log in the database - Ver 1.7.6
function append_message_to_conversation_log($session_id, $user_id, $page_id, $user_type, $thread_id, $assistant_id, $message_text) {
    global $wpdb;
    global $sessionId;
    global $thread_Id;
    global $assistant_Id;
    global $user_id;
    global $page_id;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';

    // Check if conversation logging is enabled
    if (get_option('chatbot_chatgpt_enable_conversation_logging') !== 'On') {
        // Logging is disabled, so just return without doing anything
        return;
    }

    // Prepare and execute the SQL statement
    $insert_result = $wpdb->insert(
        $table_name,
        array(
            'session_id' => $session_id,
            'user_id' => $user_id,
            'page_id' => $page_id,
            'user_type' => $user_type,
            'thread_id' => $thread_id,
            'assistant_id' => $assistant_id,
            'message_text' => $message_text
        ),
        array(
            '%s', '%s', '%s', '%s', '%s', '%s', '%s'
        )
    );

    // Check if the insert was successful
    if ($insert_result === false) {
        // DIAG - Diagnostics
        chatbot_chatgpt_back_trace( 'ERROR', "Failed to insert chat message: " . $wpdb->last_error);
        return false;
    }

    return true;

}