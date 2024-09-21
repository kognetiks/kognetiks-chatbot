<?php
/**
 * Kognetiks Chatbot for WordPress - Markove Chain Encode - Ver 2.1.6
 *
 * This file contains the code for implementing the Markov Chain algorithm
 *
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Run the Markov Chain algorithm and store the chain in the database only if new content exists
function runMarkovChatbotAndSaveChain() {

    // DIAG - Diagnostics - Ver 2.1.6
    back_trace( 'NOTICE', 'runMarkovChatbotAndSaveChain - Start');

    // Step 1: Check if the Markov Chain table exists
    createMarkovChainTable();

    // Step 2: Get the last updated timestamp for the Markov Chain
    $last_updated = get_option('chatbot_chatgpt_markov_last_updated', '2000-01-01 00:00:00');

    // Step 3: Get all published content (posts, pages, and comments) that have been updated after the last Markov Chain update
    $content = getAllPublishedContent($last_updated);

    if (!empty($content)) {

        // Step 4: Build the Markov Chain from the new content
        $markovChain = buildMarkovChain($content);

        // Step 5: Save the new chain to the database
        saveMarkovChainToDatabase($markovChain);

        // Step 6: Update the last updated timestamp
        update_option('chatbot_chatgpt_markov_last_updated', current_time('mysql'));
        
        // DIAG - Diagnostics - Ver 2.1.6
        back_trace( 'NOTICE', 'Markov Chain updated and saved to the database.');

    } else {

        // DIAG - Diagnostics - Ver 2.1.6
        back_trace( 'NOTICE', 'No new content since last update. Markov Chain not rebuilt.');

    }

    // DIAG - Diagnostics - Ver 2.1.6
    back_trace( 'NOTICE', 'runMarkovChatbotAndSaveChain - End');

}
// Hook the function to run after WordPress is fully loaded
add_action('wp_loaded', 'runMarkovChatbotAndSaveChain');

// Step 1: Check if the Markov Chain exists in the database
function createMarkovChainTable() {

    // DIAG - Diagnostics - Ver 2.1.6
    back_trace( 'NOTICE', 'createMarkovChainTable - Start' );

    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_markov_chain';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        chunk_index int(11) NOT NULL,
        chain_chunk LONGTEXT NOT NULL,
        last_updated datetime NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY chunk_index (chunk_index)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    if (!empty($wpdb->last_error)) {

        prod_trace( 'ERROR', 'Database error during table creation: ' . $wpdb->last_error);

    } else {

        prod_trace( 'NOTICE', 'Markov Chain table created/updated successfully.');

    }

    // DIAG - Diagnostics - Ver 2.1.6
    back_trace( 'NOTICE', 'createMarkovChainTable - End' );

}
register_activation_hook(__FILE__, 'createMarkovChainTable');

// Step 3: Extract the published content
function getAllPublishedContent() {

    // DIAG - Diagnostics - Ver 2.1.6
    // back_trace( 'NOTICE', 'getAllPublishedContent - Start');

    $last_updated = getMarkovChainLastUpdated();

    // Get the last updated date
    // $last_updated = '2000-01-01 00:00:00'; // FIXME - Remove this line
    $last_updated = esc_attr(get_option('chatbot_chatgpt_markov_last_updated', '2000-01-01 00:00:00'));

    // Query for posts and pages after the last updated date
    $args = array(
        'post_type' => array('post', 'page'),
        'post_status' => 'publish',
        'date_query' => array(
            array(
                'after' => $last_updated, // Only get content after the last update
            ),
        ),
        'posts_per_page' => -1 // Get all posts and pages
    );

    // Get published posts/pages
    $query = new WP_Query($args);
    $content = '';

    // Loop through each post or page
    if ($query->have_posts()) {

        while ($query->have_posts()) {
            $query->the_post();
            $post_content = get_the_content();

            // Ensure $post_content is a string
            if (is_object($post_content)) {
                $post_content = wp_kses_post($post_content);
            }

            // Clean up the post content for better Markov Chain processing
            $clean_content = clean_up_training_data($post_content);
            // Add cleaned post content to the overall content
            $content .= ' ' . get_the_title() . ' ' . $clean_content;

        }

    } else {

        // back_trace( 'NOTICE', 'getAllPublishedContent - No posts found after: ' . $last_updated);

    }

    // Reset post data
    wp_reset_postdata();

    // Fetch all comments after the last updated date
    $comments = get_comments(array(
        'status' => 'approve',
        'date_query' => array(
            array(
                'after' => $last_updated,
                'inclusive' => true, // Include comments from the exact last_updated date
            ),
        ),
    ));

    // Add cleaned comment content
    foreach ($comments as $comment) {

        // Clean up the post content for better Markov Chain processing
        $clean_comment = clean_up_training_data($comment->comment_content);

        // Add cleaned comment content to the overall content
        $content .= ' ' . $clean_comment;

    }

    // Update the last updated timestamp
    updateMarkovChainTimestamp();

    // DIAG - Diagnostics - Ver 2.1.6
    // back_trace( 'NOTICE', 'getAllPublishedContent - End');

    return $content;

}

// Step 4: Build the Markov Chain with probabilities
function buildMarkovChain($content) {

    // DIAG - Diagnostics - Ver 2.1.6
    back_trace( 'NOTICE', 'buildMarkovChain - Start');

    global $wpdb;

    // Reset the Markov Chain table
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_markov_chain';
    $wpdb->query("DELETE FROM $table_name");

    // Handle errors when clearing the table
    if ($wpdb->last_error) {
        prod_trace( 'ERROR', 'Error clearing Markov Chain table: ' . $wpdb->last_error);
        return; // Exit function on failure
    }

    // Reset the Markov Chain
    $markovChain = [];

    // Get the content and split it into words
    $words = preg_split('/\s+/', preg_replace("/[^\w\s']/u", '', $content)); // Keeps apostrophes

    // Get the Markov Chain options from the database
    $chainLength = esc_attr(get_option('chatbot_chatgpt_markov_chain_length', 1)); // Single word as default

    // Initialize chunk size limit for database storage
    $chunkSizeLimit = esc_attr(get_option('chatbot_chatgpt_markov_chain_chunk_size_limit', 10000));
    $chunkData = [];
    $currentChunk = '';

    // Loop through each word in the content
    for ($i = 0; $i < count($words) - $chainLength; $i++) {
        $key = implode(' ', array_slice($words, $i, $chainLength)); // Current word or phrase (depends on chain length)
        $nextWord = $words[$i + $chainLength]; // The word that follows the key

        // If the key doesn't exist, initialize it
        if (!isset($markovChain[$key])) {
            $markovChain[$key] = [];
        }

        // If the next word exists in the chain, increase its frequency
        if (!isset($markovChain[$key][$nextWord])) {
            $markovChain[$key][$nextWord] = 1;
        } else {
            $markovChain[$key][$nextWord]++;
        }

        // Add to chunk data for later database storage
        $chunkData[$key][$nextWord] = $markovChain[$key][$nextWord];

        // Serialize and save the chunk once it reaches the chunkSizeLimit
        $currentChunk = serialize($chunkData);
        if (strlen($currentChunk) >= $chunkSizeLimit || $i == count($words) - $chainLength - 1) {
            saveMarkovChainChunk($currentChunk, floor($i / $chainLength));
            $chunkData = []; // Clear temporary chunk data
        }
    }

    // Now calculate probabilities for each key
    foreach ($markovChain as $key => &$nextWords) {
        $totalCount = array_sum($nextWords);
        foreach ($nextWords as $word => &$count) {
            $count = $count / $totalCount; // Convert count to probability
        }
    }

    // Save the chain to the database
    saveMarkovChainToDatabase($markovChain);

    // DIAG - Diagnostics - Ver 2.1.6
    

}

// Step 5 - Save the Markov Chain in the database using chunks
function saveMarkovChainToDatabase($markovChain) {

    // DIAG - Diagnostics - Ver 2.1.6
    // back_trace( 'NOTICE', 'saveMarkovChainToDatabase - Start');

    // Save the Markov Chain in chunks using the function saveMarkovChainInChunks
    saveMarkovChainInChunks($markovChain);

    // DIAG - Diagnostics - Ver 2.1.6
    // back_trace( 'NOTICE', 'saveMarkovChainToDatabase - End');

}

// Update the last updated timestamp for the Markov Chain
function updateMarkovChainTimestamp() {
    update_option('chatbot_chatgpt_markov_last_updated', current_time('mysql'));
}

// Get the last updated timestamp for the Markov Chain
function getMarkovChainLastUpdated() {
    return get_option('chatbot_chatgpt_markov_last_updated', '2000-01-01 00:00:00');
}

// Clean up inbound text for better Markov Chain processing
function clean_up_training_data($content) {

    $clean_content = $content;

    do {

        $previous_clean_content = $clean_content;

        // Replace &nbsp; with a regular space
        if (is_string($clean_content)) {
            // Replace &nbsp; with a regular space
            $clean_content = str_replace('&nbsp;', ' ', $clean_content);
        } else {
            // Handle the case where $clean_content is not a string
            back_trace( 'ERROR', 'Expected $clean_content to be a string, but got ' . gettype($clean_content));
        }

        // Swap <br> tags with spaces
        $clean_content = preg_replace('/<br\s*\/?>/', ' ', $clean_content);

        // Swap <p> tags with spaces
        $clean_content = preg_replace('/<p\s*\/?>/', ' ', $clean_content);

        // Strip HTML tags, shortcodes, and comments, and clean up excessive whitespace
        $clean_content = wp_strip_all_tags(strip_shortcodes($clean_content));

        // Remove HTML comments
        $clean_content = preg_replace('/<!--.*?-->/', '', $clean_content);

        // Collapse multiple spaces
        $clean_content = preg_replace('/\s+/', ' ', $clean_content);

    } while ($clean_content !== $previous_clean_content); // Loop until no more changes are made

    return trim($clean_content); // Return with any leading/trailing whitespace removed

}

// Save a single chunk of the Markov Chain to the database
function saveMarkovChainChunk($chunk, $chunkIndex) {

    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_markov_chain';

    // back_trace( 'NOTICE', 'Chunk content being saved (truncated): ' . substr($chunk, 0, 100));  // Log first 100 chars of chunk
    // back_trace( 'NOTICE', 'Full Chunk content being saved: ' . substr($chunk, 0, 500));

    // back_trace( 'NOTICE', '$chunkIndex: ' . $chunkIndex);
    // back_trace( 'NOTICE', '$chuck: ' . strlen($chunk));
    $update_date = date('Y-m-d H:i:s');
    // back_trace('NOTICE', 'date/time: ' . $update_date);

    // Insert the chunk into the database
    $wpdb->query(
        $wpdb->prepare(
            "INSERT INTO $table_name (chunk_index, chain_chunk, last_updated) VALUES (%d, %s, %s)",
            $chunkIndex, $chunk, $update_date
        )
    );

    // Handle errors
    if ($wpdb->last_error) {
        // back_trace( 'NOTICE', 'Error saving chunk ' . $chunkIndex . ': ' . $wpdb->last_error);
        // back_trace( 'NOTICE', 'Query: ' . $wpdb->last_query);
    } else {
        // back_trace( 'NOTICE', 'Chunk ' . $chunkIndex . ' saved successfully.');
        // back_trace( 'NOTICE', 'Query: ' . $wpdb->last_query);
    }

}

// Save the Markov Chain in chunks to avoid exceeding database limits
function saveMarkovChainInChunks($markovChain) {

    // DIAG - Diagnostics - Ver 2.1.6
    // back_trace( 'NOTICE', 'saveMarkovChainInChunks - Start');

    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_markov_chain';

    // Serialize the Markov Chain
    $serializedChain = serialize($markovChain);
    $chunkSize = 10000; // 10000-byte Chunk size limit

    if ($serializedChain === false) {
        // back_trace( 'ERROR', 'Serialization failed for Markov Chain data.');
        return; // Exit the function if serialization fails
    } else {
        // back_trace( 'NOTICE', 'Serialization successful.');
    }

    // Split the chain into chunks
    $chunks = str_split($serializedChain, $chunkSize);

    // Clear existing chunks
    // $wpdb->query("DELETE FROM $table_name");

    // Handle errors when clearing the table
    if ($wpdb->last_error) {
        // back_trace( 'ERROR', 'saveMarkovChainInChunks - Error clearing existing chunks: ' . $wpdb->last_error);
        return; // Exit function on failure
    }

    // Insert each chunk
    foreach ($chunks as $index => $chunk) {

        // Log the length of each serialized chunk
        // back_trace( 'NOTICE', 'Chunk ' . $index . ' length: ' . strlen($chunk));

        // Skip empty or invalid chunks
        if (empty($chunk) || $chunk === 'N;') {
            // back_trace('NOTICE', 'Skipping empty or null chunk at index ' . $index);
            continue; // Skip to the next chunk
        }

        $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO $table_name (chunk_index, chain_chunk, last_updated) VALUES (%d, %s, NOW())",
                $index, $chunk
            )
        );

        $saved_chunk = $wpdb->get_var($wpdb->prepare("SELECT chain_chunk FROM $table_name WHERE chunk_index = %d", $index));
        if ($saved_chunk !== $chunk) {
            prod_trace( 'ERROR', 'Data integrity issue: Saved chunk does not match the original.');
        }

        // Log error if insertion fails
        if ($wpdb->last_error) {

            // back_trace( 'ERROR', 'Error saving chunk ' . $index . ': ' . $wpdb->last_error . ' | Query: ' . $wpdb->last_query);

        } else {

            // back_trace( 'NOTICE', 'Chunk ' . $index . ' saved successfully.');
        }

    }

    // DIAG - Diagnostics - Ver 2.1.6
    // back_trace( 'NOTICE', 'saveMarkovChainInChunks - End');

}
