<?php
/**
 * Kognetiks Chatbot - Database Management for Reporting - Ver 1.6.3
 *
 * This file contains the code for table actions for reporting
 * to display the Chatbot on the website.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Create the interaction tracking table - Ver 1.6.3
function create_chatbot_chatgpt_interactions_table() {
    
    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_interactions';
    
    $charset_collate = $wpdb->get_charset_collate();

    // Fallback cascade for invalid or unsupported character sets
    if (empty($charset_collate) || strpos($charset_collate, 'utf8mb4') === false) {
        if (strpos($charset_collate, 'utf8') === false) {
            // Fallback to utf8 if utf8mb4 is not supported
            $charset_collate = "CHARACTER SET utf8 COLLATE utf8_general_ci";
        }
    }

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        date DATE PRIMARY KEY,
        count INT
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Check for errors after dbDelta
    if ($wpdb->last_error) {
        // logErrorToServer('Failed to create table: ' . $table_name);
        // logErrorToServer('SQL: ' . $sql);
        // logErrorToServer('Error details: ' . $wpdb->last_error);
        error_log('[Chatbot] [chatbot-db-management.php] Failed to insert row into table: ' . $table_name);
        error_log('[Chatbot] [chatbot-db-management.php] Failed to create table: ' . $table_name);
        error_log('[Chatbot] [chatbot-db-management.php] SQL: ' . $sql);
        error_log('[Chatbot] [chatbot-db-management.php] Error details: ' . $wpdb->last_error);
        return false;  // Table creation failed
    }

    // DIAG - Diagnostics
    // back_trace( 'SUCCESS', 'Successfully created chatbot_chatgpt_interactions table');
    return;

}
// Hook to run the function when the plugin is activated
// register_activation_hook(__FILE__, 'create_chatbot_chatgpt_interactions_table');

// Update Interaction Tracking - Ver 1.6.3
function update_interaction_tracking() {

    global $wpdb;

    // Check version and create table if necessary
    // FIXME - WHAT IF THE TABLE WAS DROPPED? - Ver 1.7.6
    // chatbot_chatgpt_check_version();

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

// Conversation Tracking - Ver 1.7.6
function create_conversation_logging_table() {

    global $wpdb;

    // Check version and create table if necessary
    // FIXME - WHAT IF THE TABLE WAS DROPPED? - Ver 1.7.6
    // chatbot_chatgpt_check_version();

    // Check if the table already exists
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';

    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name) {
        // DIAG - Diagnostics
        // back_trace( 'NOTICE', 'Table already exists: ' . $table_name);

        // Modify interaction_time column to remove DEFAULT CURRENT_TIMESTAMP
        $sql = "ALTER TABLE $table_name MODIFY COLUMN interaction_time datetime NOT NULL;";
        $result = $wpdb->query($sql);
        if ($result === false) {
            // If there was an error, log it
            // back_trace( 'ERROR', 'Error modifying interaction_time column: ' . $wpdb->last_error);
        } else {
            // If the operation was successful, log the success
            // back_trace( 'SUCCESS', 'Successfully modified interaction_time column');
        }

        if ($wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM $table_name LIKE %s", 'assistant_name')) === 'assistant_name') {
            // DIAG - Diagnostics
            // back_trace( 'NOTICE', 'Column assistant_name already exists in table: ' . $table_name);
        } else {
            // Directly execute the ALTER TABLE command without prepare()
            $sql = "ALTER TABLE $table_name ADD COLUMN assistant_name VARCHAR(255) AFTER assistant_id";
            $result = $wpdb->query($sql);
            if ($result === false) {
                // If there was an error, log it
                // back_trace( 'ERROR', 'Error altering chatbot_chatgpt_conversation_log table: ' . $wpdb->last_error);
            } else {
                // If the operation was successful, log the success
                // back_trace( 'SUCCESS', 'Successfully altered chatbot_chatgpt_conversation_log table');
            }
        }

        // Check and add sentiment_score column if it doesn't exist
        if ($wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM $table_name LIKE %s", 'sentiment_score')) === 'sentiment_score') {
            // DIAG - Diagnostics
            // back_trace( 'NOTICE', 'Column sentiment_score already exists in table: ' . $table_name);
        } else {
            // Directly execute the ALTER TABLE command without prepare()
            $sql = "ALTER TABLE $table_name ADD COLUMN sentiment_score FLOAT AFTER message_text";
            $result = $wpdb->query($sql);
            if ($result === false) {
                // If there was an error, log it
                // back_trace( 'ERROR', 'Error adding sentiment_score column: ' . $wpdb->last_error);
            } else {
                // If the operation was successful, log the success
                // back_trace( 'SUCCESS', 'Successfully added sentiment_score column');
            }
        }

        // Directly execute the ALTER TABLE command without prepare()
        $sql = "ALTER TABLE $table_name MODIFY COLUMN user_type ENUM('Chatbot', 'Visitor', 'Prompt Tokens', 'Completion Tokens', 'Total Tokens')";
        $result = $wpdb->query($sql);
        if ($result === false) {
            // If there was an error, log it
            // back_trace( 'ERROR', 'Error altering chatbot_chatgpt_conversation_log table: ' . $wpdb->last_error);
        } else {
            // If the operation was successful, log the success
            // back_trace( 'SUCCESS', 'Successfully altered chatbot_chatgpt_conversation_log table');
        }

        // Fetch rows where user_type is missing
        $rows = $wpdb->get_results("SELECT id FROM $table_name WHERE user_type IS NULL OR user_type = '' ORDER BY id ASC", ARRAY_A);

        // Sequence of user_types to update with
        $sequence = ["Prompt Tokens", "Completion Tokens", "Total Tokens"];
        $sequenceIndex = 0;

        foreach ($rows as $row) {
            // Update the row with the corresponding sequence value
            $update_result = $wpdb->update(
                $table_name, 
                ['user_type' => $sequence[$sequenceIndex]], // data
                ['id' => $row['id']] // where
            );

            // Move to the next sequence value, or reset if at the end of the sequence
            $sequenceIndex = ($sequenceIndex + 1) % count($sequence);

            if ($update_result === false) {
                // If there was an error, log it
                // back_trace( 'ERROR', 'Error updating missing chatbot_chatgpt_conversation_log table: ' . $wpdb->last_error);
            } else {
                // If the operation was successful, log the success
                // back_trace( 'SUCCESS', 'Successfully updated missing values in chatbot_chatgpt_conversation_log table');
            }
        }
        
        // DIAG - Diagnostics - Ver 1.9.9
        // back_trace( 'SUCCESS', 'Successfully updated chatbot_chatgpt_conversation_log table');

    } else {
        // DIAG - Diagnostics
        // back_trace( 'NOTICE', 'Table does not exist: ' . $table_name);
        // SQL to create the conversation logging table

        $charset_collate = $wpdb->get_charset_collate();

        // Fallback cascade for invalid or unsupported character sets
        if (empty($charset_collate) || strpos($charset_collate, 'utf8mb4') === false) {
            if (strpos($charset_collate, 'utf8') === false) {
                // Fallback to utf8 if utf8mb4 is not supported
                $charset_collate = "CHARACTER SET utf8 COLLATE utf8_general_ci";
            }
        }

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            session_id VARCHAR(255) NOT NULL,
            user_id VARCHAR(255),
            page_id VARCHAR(255),
            interaction_time datetime NOT NULL,
            user_type ENUM('Chatbot', 'Visitor', 'Prompt Tokens', 'Completion Tokens', 'Total Tokens') NOT NULL,
            thread_id VARCHAR(255),
            assistant_id VARCHAR(255),
            assistant_name VARCHAR(255),
            message_text text NOT NULL,
            sentiment_score FLOAT,
            PRIMARY KEY  (id),
            INDEX session_id_index (session_id),
            INDEX user_id_index (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Check for errors after dbDelta
        if ($wpdb->last_error) {
            error_log('[Chatbot] [chatbot-db-management.php] Failed to create table: ' . $table_name);
            error_log('[Chatbot] [chatbot-db-management.php] SQL: ' . $sql);
            error_log('[Chatbot] [chatbot-db-management.php] Error details: ' . $wpdb->last_error);
            return false;  // Table creation failed
        }
    }

    // back_trace( 'SUCCESS', 'Successfully created/updated chatbot_chatgpt_conversation_log table');
    
    return;

}

// Append message to conversation log in the database - Ver 1.7.6
function append_message_to_conversation_log($session_id, $user_id, $page_id, $user_type, $thread_id, $assistant_id, $assistant_name, $message) {

    global $wpdb;

    // $user_type can be 'chatbot', 'visitor', 'prompt_tokens', 'completion_tokens', 'total_tokens'

    // Check if conversation logging is enabled
    if (esc_attr(get_option('chatbot_chatgpt_enable_conversation_logging')) !== 'On') {
        // Logging is disabled, so just return without doing anything
        return;
    }

    // Belt & Suspenders - Ver 1.9.3 - 20224 03 18
    // Cannot have a partial user_id based on the number value of the session_id - it trims it
    // 9ae6a5ebfacc3df8015a42d01bb25fbe becomes 9 - UGH!
    // Fixed Ver 2.3.6: Only set user_id to 0 if it's actually a string matching session_id
    // For logged-in users, preserve their actual WordPress user ID (integer > 0)
    if ( is_string($user_id) && $user_id === $session_id ) {
        $user_id = 0;
    } elseif ( is_numeric($user_id) && $user_id > 0 ) {
        // For logged-in users, ensure we keep their WordPress user ID
        $user_id = (int) $user_id;
    } elseif ( empty($user_id) || $user_id == 0 ) {
        // For anonymous users, ensure user_id is 0
        $user_id = 0;
    }

    // Get the $assistant_name from the transient - REMOVED - LET'S AVOID AN ADDITIONAL CALL TO THE DB HERE - Ver 2.2.7
    $assistant_name = get_chatbot_chatgpt_transients('assistant_name', $user_id, $page_id, $session_id);

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';

    // Check if sentiment_score column exists and analytics module is available
    $include_sentiment_score = false;
    if (function_exists('chatbot_chatgpt_add_sentiment_score_column')) {
        // Try to add the column if it doesn't exist
        chatbot_chatgpt_add_sentiment_score_column();
        // Check if the column now exists
        if ($wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM $table_name LIKE %s", 'sentiment_score')) === 'sentiment_score') {
            $include_sentiment_score = true;
        }
    }

    // Prepare the data array
    $data = array(
        'session_id' => $session_id,
        'user_id' => $user_id,
        'page_id' => $page_id,
        'user_type' => $user_type,
        'thread_id' => $thread_id,
        'assistant_id' => $assistant_id,
        'assistant_name' => $assistant_name,
        'interaction_time' => current_time('mysql'),
        'message_text' => $message
    );

    // Prepare the format array
    // Fixed: Changed user_id and page_id from %d to %s since columns are VARCHAR(255) - Ver 2.3.6
    $format = array(
        '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
    );

    // Add sentiment_score if available
    if ($include_sentiment_score) {
        $data['sentiment_score'] = 0; // Default sentiment score
        $format[] = '%f';
    }

    // Prepare and execute the SQL statement
    $insert_result = $wpdb->insert($table_name, $data, $format);

    // Check if the insert was successful
    if ($insert_result === false) {
        // DIAG - Diagnostics
        // back_trace( 'ERROR', "Failed to insert chat message: " . $wpdb->last_error);
        return false;
    }

    return true;

}

// Function to delete specific expired transients - Ver 1.7.6
function clean_specific_expired_transients() {


    global $wpdb;

    // Prefix for transients in the options table.
    $prefix = '_transient_';

    // The pattern to match in the transient's name.
    $pattern = 'chatbot_chatgpt';

    // SQL query to select expired transients that match the pattern.
    $sql = $wpdb->prepare(
        "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s AND option_name LIKE %s",
        $wpdb->esc_like($prefix . 'timeout_') . '%',
        '%' . $wpdb->esc_like($pattern) . '%'
    );

    // Execute the query.
    $expired_transients = $wpdb->get_col($sql);

    // Iterate through the results and delete each expired transient.
    foreach ($expired_transients as $transient) {
        // Extract the transient name by removing the '_transient_timeout_' prefix.
        $transient_name = str_replace($prefix . 'timeout_', '', $transient);

        // Delete the transient.
        delete_transient( $transient_name );

        // Delete the transient timeout.
        $wpdb->delete($wpdb->options, ['option_name' => $transient]);
    }
}

// Function to purge conversation log entries that are older than the specified number of days - Ver 1.7.6
function chatbot_chatgpt_conversation_log_cleanup() {

    global $wpdb;

    // Check if conversation logging is enabled
    if (esc_attr(get_option('chatbot_chatgpt_enable_conversation_logging')) !== 'On') {
        // Logging is disabled, so just return without doing anything
        return;
    }

    // Get the number of days to keep the conversation log
    $days_to_keep = esc_attr(get_option('chatbot_chatgpt_conversation_log_days_to_keep'));

    // If the number of days is not set, then set it to 30 days
    if ($days_to_keep === false) {
        $days_to_keep = 30;
    }

    // Get the date that is $days_to_keep days ago
    $purge_date = date('Y-m-d', strtotime('-' . $days_to_keep . ' days'));

    // Get the table name
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';

    // Prepare and execute the SQL statement
    $delete_result = $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE interaction_time < %s", $purge_date));

    // Check if delete was successful
    if ($delete_result === false) {
        // DIAG - Diagnostics
        // back_trace( 'ERROR', "Failed to delete conversation log entries: " . $wpdb->last_error);
        return false;
    }

    return true;

}
// Register activation and deactivation hooks
register_activation_hook(plugin_dir_path(dirname(__FILE__)) . 'chatbot-chatgpt.php', 'chatbot_chatgpt_activate_db');
register_deactivation_hook(plugin_dir_path(dirname(__FILE__)) . 'chatbot-chatgpt.php', 'chatbot_chatgpt_deactivate_db');

// Function to handle database setup on activation
function chatbot_chatgpt_activate_db() {
    // Create the interaction tracking table
    create_chatbot_chatgpt_interactions_table();
    
    // Create the conversation logging table
    create_conversation_logging_table();
    
    // Schedule the cleanup cron job
    if (!wp_next_scheduled('chatbot_chatgpt_conversation_log_cleanup_event')) {
        wp_schedule_event(time(), 'daily', 'chatbot_chatgpt_conversation_log_cleanup_event');
    }
}

// Add sentiment_score column if missing - Ver 2.3.1
function chatbot_chatgpt_add_sentiment_score_column() {

    global $wpdb;
    
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';
    
    // Check if the table exists
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) !== $table_name) {
        // Table doesn't exist, nothing to do
        return false;
    }
    
    // Check if sentiment_score column already exists
    if ($wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM $table_name LIKE %s", 'sentiment_score')) === 'sentiment_score') {
        // Column already exists
        return true;
    }
    
    // Add the sentiment_score column
    $sql = "ALTER TABLE $table_name ADD COLUMN sentiment_score FLOAT AFTER message_text";
    $result = $wpdb->query($sql);
    
    if ($result === false) {
        error_log('[Chatbot] [chatbot-db-management.php] Error adding sentiment_score column: ' . $wpdb->last_error);
        return false;
    }
    
    return true;
}

// Function to handle cleanup on deactivation
function chatbot_chatgpt_deactivate_db() {
    // Clear the scheduled cleanup event
    wp_clear_scheduled_hook('chatbot_chatgpt_conversation_log_cleanup_event');
    
    // Clean up any expired transients
    clean_specific_expired_transients();
}

// Hook for the cleanup event
add_action('chatbot_chatgpt_conversation_log_cleanup_event', 'chatbot_chatgpt_conversation_log_cleanup');
