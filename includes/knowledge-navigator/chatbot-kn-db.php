<?php
/**
 * Kognetiks Chatbot for WordPress - Knowledge Navigator - Database and File Management - Ver 1.6.3
 *
 * This file contains the code for table actions for database and file management.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Database Management - drop the table if it exists, then add it if it doesn't exist - Ver 1.6.3
function dbKNStore(): bool {
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
        word TEXT,
        score FLOAT NOT NULL
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Execute SQL query and create the table
    if(dbDelta($sql)) {
        return true;  // Table created successfully
    } else {
        return false;  // Table creation failed
    }

}

// Database Management - drop a table if it exists, then add it if it doesn't exist to store the TF-IDF words and score - Ver 1.6.3
function dbKNStoreTFIDF(): bool {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_knowledge_base_tfidf';

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
        return false;  // Table creation failed
    }

}

// Store the top words for context
function store_top_words(): void {

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
    global $topWords;

    // DIAG - Diagnostic - Ver 1.6.3
    // back_trace( 'NOTICE', 'ENTER: output_results()');

    // Generate the directory path
    // $results_dir_path = dirname(plugin_dir_path(__FILE__)) . '../../results/';
    $results_dir_path = plugin_dir_path(__FILE__) . '../../results/';

    // Create the directory if it doesn't exist
    if (!file_exists($results_dir_path)) {
        if (!mkdir($results_dir_path, 0755, true)) {
            // back_trace( 'ERROR', 'Failed to create results directory');
            return;
        }
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
        // back_trace( 'ERROR', 'Failed to open CSV file for writing: ' . $e->getMessage());
    }

    // Write JSON
    try {
        if (file_put_contents($results_json_file, json_encode($topWords)) === false) {
            throw new Exception("Failed to write to JSON file.");
        }
    } catch (Exception $e) {
        // back_trace( 'ERROR', $e->getMessage());
    }

    return;
}
