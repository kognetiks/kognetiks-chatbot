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
    // $last_updated = getMarkovChainLastUpdated();
    $last_updated = get_option('chatbot_chatgpt_markov_chain_last_updated', '2000-01-01 00:00:00');

    // FIXME - This is a temporary fix to force the Markov Chain to update every time
    $last_updated = '2000-01-01 00:00:00';

    // Get the starting batch, if no transient exists, set it to 1
    $batch_starting_point = get_transient('chatbot_chatgpt_markov_chain_batch_starting_point');
    If (empty($batch_starting_point)) {
        $batch_starting_point = 1;
    }

    // Get the batch size from the settings
    // Number of posts/pages to process in each batch
    $batch_size = esc_attr(get_option('chatbot_chatgpt_markov_chain_batch_size', 100));
    back_trace( 'NOTICE', 'Batch Size: ' . $batch_size);

    ini_set('max_execution_time', 300); // Sets the max execution time to 300 seconds

    // Get the total number of posts/pages
    $total_posts = wp_count_posts('post')->publish;
    back_trace( 'NOTICE', 'Total Posts: ' . $total_posts);

    // Calculate the number of batches
    $total_batches = ceil($total_posts / $batch_size);
    back_trace( 'NOTICE', 'Total Batches: ' . $total_batches);

    // Start with posts and pages
    $processing_type = 'posts';

    // Step 3: Get all published content (posts, pages, and comments) that have been updated after the last Markov Chain update
    $content = getAllPublishedContent($last_updated, $batch_starting_point, $batch_size, $processing_type);

    if (!empty($content)) {

        // Step 4: Build the Markov Chain from the new content
        $markovChain = buildMarkovChain($content);

        // Step 5: Save the new chain to the database
        saveMarkovChainToDatabase($markovChain);

        // Step 6: Update the last updated timestamp
        update_option('chatbot_chatgpt_markov_chain_last_updated', current_time('mysql'));
        
        // DIAG - Diagnostics - Ver 2.1.6
        back_trace( 'NOTICE', 'Markov Chain updated and saved to the database.');

    } else {

        // DIAG - Diagnostics - Ver 2.1.6
        back_trace( 'NOTICE', 'No new content since last update. Markov Chain not rebuilt.');

    }

    // FIXME - This function is not working as expected
    $stats = getDatabaseStats('chatbot_chatgpt_markov_chain');
    if (!empty($stats)) {
        prod_trace('NOTICE', 'Number of Rows: ' . $stats['row_count']);
        prod_trace('NOTICE', 'Database Size: ' . $stats['table_size_mb'] . ' MB');
    }

    // DIAG - Diagnostics - Ver 2.1.6
    back_trace( 'NOTICE', 'runMarkovChatbotAndSaveChain - End');

}

// Step 1: Check if the Markov Chain table exists and create/update it if necessary
function createMarkovChainTable() {

    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_markov_chain';
    $charset_collate = $wpdb->get_charset_collate();
    
    // SQL to create or update the table
    $create_table_sql = "
        CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            word varchar(255) NOT NULL,
            next_word varchar(255) NOT NULL,
            frequency int NOT NULL,
            last_updated datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY word_next_word (word(191), next_word(191)),
            INDEX idx_word (word(191))
        ) $charset_collate;";
    
    // Execute the SQL to create the table
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($create_table_sql);

    // Check for any database errors
    if (!empty($wpdb->last_error)) {
        prod_trace('WARNING', 'createMarkovChainTable - Creating table: ' . $create_table_sql);
        prod_trace('ERROR', 'Database error during table creation: ' . $wpdb->last_error);
    } else {
        prod_trace('NOTICE', 'Markov Chain table created/updated successfully.');
    }
}

// Register the table creation function to run on plugin activation
register_activation_hook(__FILE__, 'createMarkovChainTable');

// Step 3: Extract the published content and comments in batches
function getAllPublishedContent($last_updated, $batch_starting_point, $batch_size, $processing_type = 'posts') {

    global $wpdb;

    if (empty($batch_starting_point)) {
        $batch_starting_point = 1;
    }

    if (empty($batch_size)) {
        $batch_size = 100;
    }

    $batch_size = 50; // Limit the batch size to 50 for now

    if ($batch_starting_point == 1 && $processing_type == 'posts') {

        // Reset the Markov Chain table if it's the first post batch
        $table_name = $wpdb->prefix . 'chatbot_chatgpt_markov_chain';
    
        // Delete all data from the table
        $wpdb->query("DELETE FROM $table_name");
    
        // Reset the auto-increment value back to 1
        $wpdb->query("ALTER TABLE $table_name AUTO_INCREMENT = 1");

    }

    // DIAG - Diagnostics
    back_trace('NOTICE', 'getAllPublishedContent - Start');

    // Process posts first
    if ($processing_type == 'posts') {

        // DIAG - Diagnostics - Ver 2.1.6.1
        back_trace('NOTICE', 'Processing posts and pages');

        // Calculate the offset for the current post batch
        $offset = ($batch_starting_point - 1) * $batch_size;

        // DIAG - Diagnostics - Ver 2.1.6.1
        back_trace('NOTICE', 'Offset: ' . $offset);
        back_trace('NOTICE', 'Batch Size: ' . $batch_size);

        // Query for posts and pages after the last updated date
        $args = array(
            'post_type' => array('post', 'page'),
            'post_status' => 'publish',
            'date_query' => array(
                array(
                    'after' => $last_updated, // Only get content after the last update
                ),
            ),
            'posts_per_page' => $batch_size, // Limit the number of posts per batch
            'offset' => $offset // Offset to fetch the correct batch
        );

        // Get published posts/pages
        $query = new WP_Query($args);
        $content = '';

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_content = get_the_content();

                // Clean up the post content for better Markov Chain processing
                $clean_content = clean_up_training_data($post_content);
                $content .= ' ' . get_the_title() . ' ' . $clean_content;
            }

            // Build the Markov Chain with the current batch content
            buildMarkovChain($content);

            // If more posts are available, schedule the next post batch
            if ($query->found_posts > $batch_starting_point * $batch_size) {
                $next_batch_starting_point = $batch_starting_point + 1;
                wp_schedule_single_event(time() + 120, 'getAllPublishedContent', array($last_updated, $next_batch_starting_point, $batch_size, 'posts'));
                back_trace('NOTICE', 'getAllPublishedContent - Scheduled next post batch #' . $next_batch_starting_point);
            } else {
                // No more posts, move to processing comments
                wp_schedule_single_event(time() + 120, 'getAllPublishedContent', array($last_updated, 1, $batch_size, 'comments'));
                back_trace('NOTICE', 'getAllPublishedContent - Posts done, moving to comments');
            }
        }

        // Reset post data
        wp_reset_postdata();

    }

    // Process comments after posts
    if ($processing_type == 'comments') {

        // DIAG - Diagnostics - Ver 2.1.6.1
        back_trace('NOTICE', 'Processing comments');

        // Calculate the offset for the current comment batch
        $offset = ($batch_starting_point - 1) * $batch_size;

        // DIAG - Diagnostics - Ver 2.1.6.1
        back_trace('NOTICE', 'Offset: ' . $offset);
        back_trace('NOTICE', 'Batch Size: ' . $batch_size);

        // Fetch all comments after the last updated date
        $comments = get_comments(array(
            'status' => 'approve',
            'date_query' => array(
                array(
                    'after' => $last_updated,
                    'inclusive' => true,
                ),
            ),
            'number' => $batch_size,
            'offset' => $offset,
        ));

        $content = '';
        foreach ($comments as $comment) {
            // Clean up the comment content for better Markov Chain processing
            $clean_comment = clean_up_training_data($comment->comment_content);
            $content .= ' ' . $clean_comment;
        }

        // Build the Markov Chain with the current batch content
        buildMarkovChain($content);

        // If more comments are available, schedule the next comment batch
        if (count($comments) === $batch_size) {
            $next_batch_starting_point = $batch_starting_point + 1;
            wp_schedule_single_event(time() + 120, 'getAllPublishedContent', array($last_updated, $next_batch_starting_point, $batch_size, 'comments'));
            back_trace('NOTICE', 'getAllPublishedContent - Scheduled next comment batch #' . $next_batch_starting_point);
        } else {
            back_trace('NOTICE', 'getAllPublishedContent - Comments done');
        }

    }

    // Update the last updated timestamp
    updateMarkovChainTimestamp();

    // DIAG - Diagnostics
    back_trace('NOTICE', 'getAllPublishedContent - End');

}

// Step 4: Build the Markov Chain by saving each word and its next word directly into the database
function buildMarkovChain($content) {

    global $wpdb;

    // Reset the Markov Chain table
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_markov_chain';
    $wpdb->query("DELETE FROM $table_name");

    // Split the content into words
    // FIXME - TRY LEAVING PUNCTUATION IN FOR BETTER MARKOV CHAIN GENERATION - Ver 2.1.6
    // $words = preg_split('/\s+/', preg_replace("/[^\w\s']/u", '', $content));
    $words = preg_split('/\s+/', $content);
    
    // Correctly retrieve the chain length (key size) from the database
    $chainLength = esc_attr(get_option('chatbot_chatgpt_markov_chain_length', 2));  // Default to 2 (for two-word key) if not set

    // Set the phrase size for the next part of the Markov Chain
    $phraseSize = esc_attr(get_option('chatbot_chatgpt_markov_chain_next_phrase_length', 2));  // Default to 2 (for four-word phrase) if not set

    // Build and save the Markov Chain
    for ($i = 0; $i < count($words) - ($chainLength + $phraseSize - 1); $i++) {

        // Generate the key by taking 'chainLength' number of words (e.g., 2 words)
        $key = implode(' ', array_slice($words, $i, $chainLength));

        // count words in $key
        $key_word_count = str_word_count($key);
        if ($key_word_count < $chainLength) {
            // Skip this iteration if the key does not have enough words
            continue;
        }

        // Remove non-alphanumeric characters from the key
        $key = preg_replace("/[^a-zA-Z0-9\s]/", '', $key);
        
        // Generate the next phrase by taking 'phraseSize' number of words after the key (e.g., 2 words)
        $nextPhrase = implode(' ', array_slice($words, $i + $chainLength, $phraseSize));

        // Check if this word and next phrase combination already exists in the database
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT frequency FROM $table_name WHERE word = %s AND next_word = %s",
                $key, $nextPhrase
            )
        );

        if ($existing === null) {

            // If it doesn't exist, insert the word pair (key and next phrase)
            $wpdb->insert(
                $table_name,
                array(
                    'word' => $key,
                    'next_word' => $nextPhrase,
                    'frequency' => 1,
                    'last_updated' => current_time('mysql')
                ),
                array('%s', '%s', '%d', '%s')
            );

            // Handle errors
            if ($wpdb->last_error) {
                prod_trace('ERROR', 'Error inserting word pair: ' . $wpdb->last_error);
            }

        } else {

            // If it exists, increment the frequency

            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE $table_name SET frequency = frequency + 1, last_updated = %s WHERE word = %s AND next_word = %s",
                    current_time('mysql'), $key, $nextPhrase
                )
            );

            // Handle errors
            if ($wpdb->last_error) {
                prod_trace('ERROR', 'Error updating frequency: ' . $wpdb->last_error);
            }

        }
    }
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
    update_option('chatbot_chatgpt_markov_chain_last_updated', current_time('mysql'));
}

// Get the last updated timestamp for the Markov Chain
function getMarkovChainLastUpdated() {
    return get_option('chatbot_chatgpt_markov_chain_last_updated', '2000-01-01 00:00:00');
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
            // back_trace( 'ERROR', 'Expected $clean_content to be a string, but got ' . gettype($clean_content));
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

    // Validate that the chunk is a serialized string
    if (is_string($chunk) && @unserialize($chunk) !== false || $chunk === 'b:0;') {
        // Log chunk size and content (truncated)
        // back_trace('NOTICE', 'Chunk content being saved (truncated): ' . substr($chunk, 0, 100)); // First 100 characters
        // back_trace('NOTICE', 'Full Chunk length: ' . strlen($chunk));  // Log the full length of the chunk

        $update_date = date('Y-m-d H:i:s');

        // Insert the chunk into the database
        $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO $table_name (chunk_index, chain_chunk, last_updated) VALUES (%d, %s, %s)",
                $chunkIndex, $chunk, $update_date
            )
        );

        // Handle errors
        if ($wpdb->last_error) {
            // back_trace('ERROR', 'Error saving chunk ' . $chunkIndex . ': ' . $wpdb->last_error);
            // back_trace('ERROR', 'Query: ' . $wpdb->last_query);
        } else {
            // back_trace('NOTICE', 'Chunk ' . $chunkIndex . ' saved successfully.');
        }

    } else {
        // Log the issue if the chunk is not a valid serialized string
        // back_trace('ERROR', 'Invalid serialized chunk data for chunk index: ' . $chunkIndex);
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

// Get stats for a specific table in the database
function getDatabaseStats($table_name) {

    global $wpdb;

    // Ensure the table name is properly prefixed
    $table_name = $wpdb->prefix . $table_name;

    // Get the number of rows in the specified table
    $row_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

    // Get the total size of the specific table
    $table_size = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb 
             FROM information_schema.tables 
             WHERE table_schema = %s AND table_name = %s", 
            DB_NAME, $table_name
        )
    );

    back_trace('NOTICE', 'Row Count: ' . $row_count);
    back_trace('NOTICE', 'Table Size: ' . $table_size . ' MB');

    return [
        'row_count' => $row_count,
        'table_size_mb' => $table_size
    ];

}

