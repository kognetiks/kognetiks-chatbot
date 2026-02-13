<?php
/**
 * Kognetiks Chatbot - Knowledge Navigator - Database and File Management - Ver 1.6.3
 *
 * This file contains the code for table actions for database and file management.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Database Management - drop the table if it exists, then add it if it doesn't exist - Ver 1.6.3
function dbKNStore() {


    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_knowledge_base';

    // // Fallback cascade for invalid or unsupported character sets
    // if (empty($charset_collate) || strpos($charset_collate, 'utf8mb4') === false) {
    //     if (strpos($charset_collate, 'utf8') === false) {
    //         // Fallback to utf8 if utf8mb4 is not supported
    //         $charset_collate = "CHARACTER SET utf8 COLLATE utf8_general_ci";
    //     }
    // }

    // FIXME - IRISH TEXT ENCODING - REMOVED IN VER 2.2.1 - 2024-12-24
    // $charset_collate = "CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci";

    // Drop table if it exists
    $wpdb->query("DROP TABLE IF EXISTS $table_name");

    // SQL to create a new table
    $sql = "CREATE TABLE $table_name (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        url TEXT NOT NULL,
        title TEXT,
        word TEXT,
        score FLOAT NOT NULL,
        pid BIGINT UNSIGNED
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Execute SQL query and create the table
    if(dbDelta($sql)) {
        return true;  // Table created successfully
    } else {
        // Log the error
        prod_trace( 'ERROR', 'Failed to create table: ' . $table_name);
        prod_trace( 'ERROR', 'SQL: ' . $sql);
        // Log the specific reason for the failure
        if($wpdb->last_error !== '') {
            prod_trace( 'ERROR', 'Details: ' . $wpdb->last_error);
        }
        return false;  // Table creation failed
    }

}

// Database Management - drop a table if it exists, then add it if it doesn't exist to store the TF-IDF words and score - Ver 1.6.3
function dbKNStoreTFIDF() {

    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_knowledge_base_tfidf';

    // Fallback cascade for invalid or unsupported character sets
    // if (empty($charset_collate) || strpos($charset_collate, 'utf8mb4') === false) {
    //     if (strpos($charset_collate, 'utf8') === false) {
    //         // Fallback to utf8 if utf8mb4 is not supported
    //         $charset_collate = "CHARACTER SET utf8 COLLATE utf8_general_ci";
    //     }
    // }

    // FIXME - IRISH TEXT ENCODING - REMOVED IN VER 2.2.1 - 2024-12-24
    // $charset_collate = "CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci";

    // Drop table if it exists
    $wpdb->query("DROP TABLE IF EXISTS $table_name");

    // SQL to create a new table
    $sql = "CREATE TABLE $table_name (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        word TEXT NOT NULL,
        score FLOAT NOT NULL
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Execute SQL query and create the table
    if(dbDelta($sql)) {
        return true;  // Table created successfully
    } else {
        // Log the error
        prod_trace( 'ERROR', 'Failed to create table: ' . $table_name);
        prod_trace( 'ERROR', 'SQL: ' . $sql);
        // Log the specific reason for the failure
        if($wpdb->last_error !== '') {
            prod_trace( 'ERROR', 'Details: ' . $wpdb->last_error);
        }
        return false;  // Table creation failed
    }

}

// Database Management - drop a table if it exists, then add it if it doesn't exist to store the words and score - Ver 1.9.6
function dbKNStoreWordCount() {

    global $wpdb;
    
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_knowledge_base_word_count';

    $charset_collate = $wpdb->get_charset_collate();
    // Fallback cascade for invalid or unsupported character sets
    // if (empty($charset_collate) || strpos($charset_collate, 'utf8mb4') === false) {
    //     if (strpos($charset_collate, 'utf8') === false) {
    //         // Fallback to utf8 if utf8mb4 is not supported
    //         $charset_collate = "CHARACTER SET utf8 COLLATE utf8_general_ci";
    //     }
    // }

    // FIXME - IRISH TEXT ENCODING - REMOVED IN VER 2.2.1 - 2024-12-24
    // $charset_collate = "CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci";

    // Drop table if it exists
    $wpdb->query("DROP TABLE IF EXISTS $table_name");

    // SQL to create a new table
    $sql = "CREATE TABLE $table_name (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        word VARCHAR(191) NOT NULL UNIQUE,
        word_count INT NOT NULL,
        document_count INT NOT NULL
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    // Execute SQL query and create the table
    if(dbDelta($sql)) {
        return true;  // Table created successfully
    } else {
        // Log the error
        prod_trace( 'ERROR', 'Failed to create table: ' . $table_name);
        prod_trace( 'ERROR', 'SQL: ' . $sql);
        // Log the specific reason for the failure
        if($wpdb->last_error !== '') {
            prod_trace( 'ERROR', 'Details: ' . $wpdb->last_error);
        }
        return false;  // Table creation failed
        
    }
    
}

// Database Management - drop a table if it exists to clean up the database - Ver 1.9.6
function dbKNClean() {

    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_knowledge_base_word_count';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");

    return true;

}

// Store the top words for context
function store_top_words() {

    global $wpdb;

    global $topWords;

    // Call the dbKNStoreTFIDF function - Ver 1.6.3
    dbKNStoreTFIDF();
    
    // String together the $topWords
    $chatbot_chatgpt_kn_conversation_context = "This site includes references to and information about the following topics: ";
    foreach ($topWords as $word => $tfidf) {
        $chatbot_chatgpt_kn_conversation_context .= $word . ", ";
        }
    $chatbot_chatgpt_kn_conversation_context .= "and more.";
    
    // Save the results message value into the option
    update_option('chatbot_chatgpt_kn_conversation_context', $chatbot_chatgpt_kn_conversation_context);

    // Add each word to the TF-IDF table - Ver 1.6.3
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_knowledge_base_tfidf';
    foreach ($topWords as $word => $tfidf) {
        $wpdb->insert(
            $table_name,
            array(
                'word' => $word,
                'score' => $tfidf
            )
        );
    }
    
    return;

}

// Save the results to a file
function output_results() {

    global $chatbot_chatgpt_plugin_dir_path;
    global $topWords;

    // Generate the directory path
    $results_dir_path = $chatbot_chatgpt_plugin_dir_path . 'results/';

    // Ensure the directory exists or attempt to create it
    if (!create_directory_and_index_file($results_dir_path)) {
        // Error handling, e.g., log the error or handle the failure appropriately
        return;
    }

    // Define output files' paths
    $results_csv_file = $results_dir_path . 'results.csv';
    $results_json_file = $results_dir_path . 'results.json';

    // Write CSV
    try {
        $f = new SplFileObject($results_csv_file, 'w');
        $f->fputcsv(['Word', 'TF-IDF']);
        foreach ($topWords as $word => $tfidf) {
            $f->fputcsv([$word, $tfidf]);
        }
    } catch (RuntimeException $e) {
    }

    // Write JSON
    try {
        if (file_put_contents($results_json_file, json_encode($topWords)) === false) {
            throw new Exception("Failed to write to JSON file.");
        }
    } catch (Exception $e) {
    }

    return;
}
