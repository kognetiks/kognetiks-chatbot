<?php
/**
 * Kognetiks Chatbot for WordPress - Markov Chain Encode - Ver 2.2.0
 *
 * This file contains the improved code for implementing the Markov Chain algorithm.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Run the Markov Chain algorithm and store the chain in the database
function runMarkovChatbotAndSaveChain() {

    // Diagnostics
    back_trace( 'NOTICE', 'runMarkovChatbotAndSaveChain - Start');

    // Step 0: Force a full rebuild if necessary
    $force_rebuild = esc_attr(get_option('chatbot_markov_chain_force_rebuild', 'No'));
    
    // FIXME - Temporary to force a full rebuild
    $force_rebuild = 'Yes';
    back_trace( 'NOTICE', 'Forcing a full rebuild of the Markov Chain');

    if ($force_rebuild == 'Yes') {
        back_trace( 'NOTICE', 'Forcing a full rebuild of the Markov Chain');
        dropMarkovChainTable();
        update_option('chatbot_markov_chain_force_rebuild', 'No');
        // FIXME - Temporary fix for the last updated timestamp
        update_option('chatbot_markov_chain_last_updated', '2000-01-01 00:00:00'); // Reset the timestamp
    }

    // Step 1: Check if the Markov Chain table exists
    createMarkovChainTable();

    // Step 2: Get the last updated timestamp for the Markov Chain
    $last_updated = get_option('chatbot_markov_chain_last_updated', '2000-01-01 00:00:00');

    // Step 3: Initialize batch processing variables
    $batch_starting_point = get_transient('chatbot_markov_chain_batch_starting_point') ?: 1;
    $batch_size = max(1, min(intval(get_option('chatbot_markov_chain_batch_size', 100)), 100)); // Limit batch size between 1 and 100

    // Step 4: Get the total number of posts
    $total_posts = wp_count_posts('post')->publish;

    // Step 5: Calculate the number of batches
    $total_batches = ceil($total_posts / $batch_size);

    // Step 6: Set the maximum execution time
    ini_set('max_execution_time', 300);

    // Step 7: Start processing content
    processContentBatches($last_updated, $batch_starting_point, $batch_size);

    // Step 8: Report the results of the Markov Chain build
    $stats = getDatabaseStats('chatbot_markov_chain');
    if (!empty($stats)) {
        prod_trace( 'NOTICE', 'Number of Rows: ' . $stats['row_count']);
        prod_trace( 'NOTICE', 'Database Size: ' . $stats['table_size_mb'] . ' MB');
    }

    back_trace( 'NOTICE', 'runMarkovChatbotAndSaveChain - End');

}

// Create or update the Markov Chain table
function createMarkovChainTable() {

    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_markov_chain';
    $charset_collate = $wpdb->get_charset_collate();

    // Adjust charset if necessary
    if (empty($charset_collate) || strpos($charset_collate, 'utf8mb4') === false) {
        if (strpos($charset_collate, 'utf8') === false) {
            $charset_collate = "CHARACTER SET utf8 COLLATE utf8_general_ci";
        }
    }

    // SQL to create or update the table
    $create_table_sql = "
        CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            chain_length tinyint(2) NOT NULL,
            word varchar(255) NOT NULL,
            next_word varchar(255) NOT NULL,
            frequency int NOT NULL,
            last_updated datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY word_next_word (chain_length, word(191), next_word(191)),
            INDEX idx_word (word(191)),
            INDEX idx_chain_length (chain_length)
        ) $charset_collate;";

    // Execute the SQL to create the table
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($create_table_sql);

    // Check if the unique key exists
    $indexes = $wpdb->get_results("SHOW INDEX FROM $table_name WHERE Key_name = 'word_next_word'");
    if (empty($indexes)) {
        // Add unique key if it doesn't exist
        $wpdb->query("ALTER TABLE $table_name ADD UNIQUE KEY `word_next_word` (`chain_length`,`word`(191),`next_word`(191))");
    }

    // Check for errors after dbDelta
    if ($wpdb->last_error) {
        prod_trace( 'Failed to create table: ' . $table_name);
        prod_trace( 'SQL: ' . $create_table_sql);
        prod_trace( 'Error details: ' . $wpdb->last_error);
        return false;
    }

    prod_trace( 'NOTICE', 'Markov Chain table created/updated successfully.');

}
// Register the table creation function to run on plugin activation
register_activation_hook(__FILE__, 'createMarkovChainTable');

// Drop the Markov Chain table
function dropMarkovChainTable() {

    // DIAG - Diagnostic - Ver 2.2.0
    back_trace( 'NOTICE', 'dropMarkovChainTable - Start');

    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_markov_chain';

    // Drop the table if it exists
    $wpdb->query("DROP TABLE IF EXISTS $table_name");

    // Check for errors
    if ($wpdb->last_error) {
        prod_trace( 'Failed to drop table: ' . $table_name);
        prod_trace( 'Error details: ' . $wpdb->last_error);
        return false;
    }

    prod_trace( 'NOTICE', 'Markov Chain table dropped successfully.');

    return true;

}

// Process content in batches
function processContentBatches($last_updated, $batch_starting_point, $batch_size) {

    back_trace( 'NOTICE', 'processContentBatches - Start');

    // Process posts and pages
    $processing_types = ['posts', 'comments', 'synthetic'];
    $all_batches_completed = true;

    foreach ($processing_types as $type) {

        $continue_processing = true;
        prod_trace( 'NOTICE', 'Processing type: ' . $type);
        prod_trace( 'NOTICE', 'Batch starting point: ' . $batch_starting_point);
    
        $processed_content = false; // Flag to check if any content was processed
    
        while ($continue_processing) {
            $content = getContentBatch($last_updated, $batch_starting_point, $batch_size, $type);
    
            if ($content) {
                buildMarkovChain($content);
                $batch_starting_point++;
                set_transient('chatbot_markov_chain_batch_starting_point', $batch_starting_point, 12 * HOUR_IN_SECONDS);
                $all_batches_completed = false;
                $processed_content = true; // Mark that some content was processed
            } else {
                $continue_processing = false;
            }
        }
    
        // Edge case: If no content was processed for this type
        if (!$processed_content) {
            prod_trace( 'NOTICE', "No content found for processing type: $type. Skipping to next type.");
        }
    }

    // Schedule next batch if not all completed
    if (!$all_batches_completed) {

        if (!wp_next_scheduled('chatbot_markov_chain_next_batch')) {
            if (!wp_schedule_single_event(time() + 60, 'chatbot_markov_chain_next_batch')) {
                prod_trace( 'ERROR', 'Failed to schedule next batch for Markov Chain processing.');
            } else {
                back_trace( 'NOTICE', 'Next batch scheduled.');
            }
        }

    } else {

        update_option('chatbot_markov_chain_build_schedule', 'Completed');
        if (get_option('chatbot_markov_chain_build_schedule') === 'Completed') {
            delete_transient('chatbot_markov_chain_batch_starting_point');
            delete_option('chatbot_markov_chain_build_schedule');
            back_trace( 'NOTICE', 'All temporary data cleaned up after completion.');
        }

    }

    updateMarkovChainTimestamp();

    prod_trace( 'NOTICE', 'processContentBatches - End');

}
add_action('chatbot_markov_chain_next_batch', 'runMarkovChatbotAndSaveChain');

// Get a batch of content based on type
function getContentBatch($last_updated, $batch_starting_point, $batch_size, $processing_type) {

    global $wpdb;

    $offset = ($batch_starting_point - 1) * $batch_size;

    if ($processing_type == 'posts') {
        // Fetch posts and pages
        $args = [
            'post_type'      => ['post', 'page'],
            'post_status'    => 'publish',
            'date_query'     => [
                [
                    'after' => $last_updated,
                ],
            ],
            'posts_per_page' => $batch_size,
            'offset'         => $offset,
        ];

        $query = new WP_Query($args);
        $content = '';

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_content = get_the_content();

                // Clean up the post content
                $clean_content = clean_up_training_data($post_content);
                $content .= ' ' . get_the_title() . ' ' . $clean_content;
            }
            wp_reset_postdata();
            return $content;
        } else {
            return false;
        }
    }

    if ($processing_type == 'comments') {
        // Fetch comments
        $comments = get_comments([
            'status'     => 'approve',
            'date_query' => [
                [
                    'after'     => $last_updated,
                    'inclusive' => true,
                ],
            ],
            'number' => $batch_size,
            'offset' => $offset,
        ]);

        if (!empty($comments)) {
            $content = '';
            foreach ($comments as $comment) {
                $clean_comment = clean_up_training_data($comment->comment_content);
                $content .= ' ' . $clean_comment;
            }
            return $content;
        } else {
            return false;
        }
    }

    if ($processing_type == 'synthetic') {

        // Fetch synthetic data
        $syntheticData = chatbot_markov_chain_synthetic_data_generation();
        $clean_content = clean_up_training_data($syntheticData);
        return $clean_content;

    }

    return false;

}

// Build the Markov Chain by saving each word and its next word into the database
function buildMarkovChain($content) {

    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_markov_chain';

    // Clean and split the content into words
    $content = strtolower($content);
    $words = preg_split('/\s+/', $content);

    // Define the maximum chain length
    $maxChainLength = intval(get_option('chatbot_markov_chain_length', 3));

    // Build the Markov Chain for different chain lengths
    for ($chainLength = $maxChainLength; $chainLength >= 1; $chainLength--) {
        for ($i = 0; $i <= count($words) - $chainLength - 1; $i++) {
            $key = implode(' ', array_slice($words, $i, $chainLength));
            $nextWord = $words[$i + $chainLength];

            // Clean up the key and next word
            $key = preg_replace('/[^\w\s\-]/u', '', $key); // Allow alphanumeric, spaces, and hyphens
            $nextWord = preg_replace('/[^\w\s\-]/u', '', $nextWord);

            // Skip if key or next word is empty
            if (empty($key) || empty($nextWord)) {
                continue;
            }

            // Use INSERT ON DUPLICATE KEY UPDATE to handle duplicates efficiently
            $wpdb->query(
                $wpdb->prepare(
                    "INSERT INTO $table_name (chain_length, word, next_word, frequency, last_updated)
                    VALUES (%d, %s, %s, 1, %s)
                    ON DUPLICATE KEY UPDATE
                    frequency = frequency + 1, last_updated = %s",
                    $chainLength, $key, $nextWord, current_time('mysql'), current_time('mysql')
                )
            );

            // Handle potential database errors
            if ($wpdb->last_error) {
                prod_trace( 'ERROR', sprintf(
                    'Failed to update Markov Chain (chain_length: %d, key: %s, next_word: %s). Error: %s',
                    $chainLength, $key, $nextWord, $wpdb->last_error
                ));
            }
        }
    }
}

// Update the last updated timestamp for the Markov Chain
function updateMarkovChainTimestamp() {

    update_option('chatbot_markov_chain_last_updated', current_time('mysql'));

}

// Clean up inbound text for better Markov Chain processing
function clean_up_training_data($content) {

    $clean_content = $content;

    do {
        $previous_clean_content = $clean_content;

        // Replace &nbsp; and other HTML entities with spaces
        $clean_content = html_entity_decode($clean_content, ENT_QUOTES | ENT_HTML5);

        // Strip HTML tags, shortcodes, and comments
        $clean_content = wp_strip_all_tags(strip_shortcodes($clean_content));

        // Remove HTML comments
        $clean_content = preg_replace('/<!--.*?-->/', '', $clean_content);

        // Replace line breaks with spaces
        $clean_content = preg_replace('/[\r\n]+/', ' ', $clean_content);

        // Collapse multiple spaces
        $clean_content = preg_replace('/\s+/', ' ', $clean_content);

    } while ($clean_content !== $previous_clean_content);

    return trim($clean_content);

}

// Get stats for a specific table in the database
function getDatabaseStats($table_name) {

    global $wpdb;

    $table_name = $wpdb->prefix . $table_name;

    $row_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

    $table_size = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb 
             FROM information_schema.tables 
             WHERE table_schema = %s AND table_name = %s",
            DB_NAME, $table_name
        )
    );

    return [
        'row_count'     => $row_count,
        'table_size_mb' => $table_size,
    ];
}

// Synthetic Data Generation
function chatbot_markov_chain_synthetic_data_generation() {

    $syntheticData = '';

    // Get the synthetic data model choice
    $syntheticDataModel = esc_attr(get_option('chatbot_markov_chain_model_choice', 'markov-chain-flask'));
    $syntheticDataFile = plugin_dir_path(__FILE__) . 'chatbot-' . $syntheticDataModel . '.txt';

    // Read the synthetic data from the file
    $syntheticData = file_get_contents($syntheticDataFile);

    if ($syntheticData === false) {
        prod_trace('ERROR', 'Failed to read synthetic data file: ' . $syntheticDataFile);
        return '';
    } else {
        prod_trace( 'NOTICE', 'Synthetic Data read successfully');
    }

    return $syntheticData;

}
