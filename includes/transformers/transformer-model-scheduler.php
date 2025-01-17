<?php
/**
 * Kognetiks Chatbot for WordPress - Transformer Model - Scheduler - Ver 2.2.1
 *
 * This is the file that schedules the building of the Transformer Model.
 * Scheduling can be set to now, daily, weekly, etc.
 * 
 * @package chatbot-chatgpt
 */


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Handle long-running scripts with a scheduled event function
function chatbot_transformer_model_scheduler() {

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', 'chatbot_transformer_model_scheduler - start');

    // Retrieve the schedule setting
    $chatbot_transformer_model_build_schedule = esc_attr(get_option('chatbot_transformer_model_build_schedule', 'Disable'));

    // Don't schedule if already completed
    if ($chatbot_transformer_model_build_schedule === 'Completed') {
        return;
    }

    // Retrieve the current status
    $chatbot_transformer_model_build_status = get_option('chatbot_transformer_model_build_status', 'No Schedule');

    // Exit if the scheduler is not enabled
    if (in_array($chatbot_transformer_model_build_schedule, ['No', 'Disable', 'Cancel'])) {
        wp_clear_scheduled_hook('chatbot_transformer_model_scan_hook');
        update_option('chatbot_transformer_model_build_status', 'No Schedule');
        update_option('chatbot_transformer_model_content_items_processed', 0);
        // prod_trace('NOTICE', 'chatbot_transformer_model_scheduler: ' . $chatbot_transformer_model_build_schedule);
        return;
    }

    // Reset the cache file if offset is 0
    if (in_array($chatbot_transformer_model_build_schedule, ['Now', 'Hourly', 'Twice Daily', 'Daily', 'Weekly'])) {

        // DIAG - Diagnostics
        // back_trace( 'NOTICE', 'Scheduler started');

        // Update the status as 'In Process'
        update_option('chatbot_transformer_model_build_status', 'In Process');

        // DIAG - Diagnostics
        prod_trace('NOTICE', 'chatbot_transformer_model_build_status: ' . $chatbot_transformer_model_build_status);

        // Reset the cache fil
        transformer_model_sentential_context_reset_cache();

        // Reset the offset
        update_option('chatbot_transformer_model_offset', 0);

        // Reset the content items processed
        update_option('chatbot_transformer_model_content_items_processed', 0);
            
        // Schedule the first scan
        wp_schedule_single_event(time() + 10, 'chatbot_transformer_model_scan_hook');

    }

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', 'chatbot_transformer_model_scheduler - end');

}
add_action('chatbot_transformer_model_scheduler_hook', 'chatbot_transformer_model_scheduler');

// Reset the cache files and offset
function transformer_model_sentential_context_reset_cache() {

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', 'transformer_model_sentential_context_reset_cache - start');

    // Log reset activity
    prod_trace('NOTICE', 'Transformer Model Content Cache Reset');

    // Reset offset and content items processed
    update_option('chatbot_transformer_model_offset', 0);
    update_option('chatbot_transformer_model_content_items_processed', 0);

    // Cache folder path
    $cacheDir = __DIR__ . '/sentential_embeddings_cache/';

    // Check if the directory exists
    if (is_dir($cacheDir)) {
        // Open directory and delete all files inside
        $files = glob($cacheDir . '*.php'); // Get all PHP files in the directory
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file); // Delete the file
                // back_trace( 'NOTICE', "Deleted cache file: $file");
            }
        }
        // Optionally, delete the folder itself if required
        // rmdir($cacheDir); // Uncomment if you want to remove the folder
    } else {
        // back_trace( 'NOTICE', "Cache directory not found: $cacheDir");
    }

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', 'transformer_model_sentential_context_reset_cache - end');

}


// Check if the Transformer Model needs to be built or updated
function chatbot_transformer_model_scan() {

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', 'chatbot_transformer_model_scan - start');

    // Retrieve current state
    $offset = intval(get_option('chatbot_transformer_model_offset', 0));
    $batchSize = intval(get_option('chatbot_transform_model_batch_size', 50));
    $corpus = transformer_model_sentential_context_fetch_content($offset, $batchSize);

    if (empty($corpus)) {
        update_option('chatbot_transformer_model_build_status', 'Completed');
        update_option('chatbot_transformer_model_build_schedule', 'Completed');
        update_option('chatbot_transformer_model_last_updated', current_time('mysql'));
        prod_trace('NOTICE', 'chatbot_transformer_model_build_schedule: Completed');
        return;
    }

    // Retrieve the window size
    $windowSize = intval(esc_attr(get_option('chatbot_transformer_model_word_content_window_size', 3)));

    // Build embeddings with the updated logic
    transformer_model_sentential_context_cache_embeddings($corpus, $windowSize);

    // Update the offset
    $processedItems = count($corpus);
    update_option('chatbot_transformer_model_offset', $offset + $processedItems);

    prod_trace('NOTICE', "Processed $processedItems items starting at offset $offset");

    // Schedule the next batch if needed
    wp_schedule_single_event(time() + 10, 'chatbot_transformer_model_scan_hook');

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', 'chatbot_transformer_model_scan - end');

}
add_action('chatbot_transformer_model_scan_hook', 'chatbot_transformer_model_scan');

// Fetch WordPress content
function transformer_model_sentential_context_fetch_content($offset, $batchSize) {

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', 'transformer_model_sentential_context_fetch_content - start');

    global $wpdb;

    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT ID, post_content FROM {$wpdb->posts} WHERE post_status = %s AND (post_type = %s OR post_type = %s) LIMIT %d, %d",
            'publish', 'post', 'page', $offset, $batchSize
        ),
        ARRAY_A
    );

    if (empty($results)) {
        update_option('chatbot_transformer_model_offset', 0);
        // back_trace( 'NOTICE', 'No more posts to process.');
        return [];
    } else {
        // Schedule the next batch
        wp_schedule_single_event(time() + 10, 'chatbot_transformer_model_scan_hook');
    }

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', 'transformer_model_sentential_context_fetch_content - end');

    return $results;

}

// Cache embeddings for the fetched content
function transformer_model_sentential_context_cache_embeddings($corpus, $windowSize = 3) {

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', 'transformer_model_sentential_context_cache_embeddings - start');

    $cacheDir = __DIR__ . '/sentential_embeddings_cache/';

    // Ensure the cache directory exists
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }

    $cacheUpdates = []; // To group updates by file

    // Process the content to build embeddings
    foreach ($corpus as $row) {

        $postContent = strip_tags(html_entity_decode($row['post_content'], ENT_QUOTES | ENT_HTML5));
        $postEmbeddings = transformer_model_sentential_context_build_cooccurrence_matrix($postContent, $windowSize);

        // Group embeddings by their cache file
        foreach ($postEmbeddings as $word => $context) {

            // Sanitize word
            $word = preg_replace('/[^\w\s]/u', '', $word);
            $word = strtolower(trim($word));

            // Skip empty or invalid keys
            if (empty($word)) {
                continue;
            }

            $firstChar = strtolower($word[0]); // Determine the first character of the n-gram
            if (!preg_match('/[a-z]/', $firstChar)) {
                $firstChar = 'other'; // Handle non-alphabetic characters
            }

            // Sanitize context keys and values
            foreach ($context as $contextWord => $count) {
                $contextWord = preg_replace('/[^\w\s]/u', '', $contextWord);
                $contextWord = strtolower(trim($contextWord));
                if (!empty($contextWord)) {
                    $context[$contextWord] = intval($count); // Ensure count is an integer
                } else {
                    unset($context[$contextWord]); // Remove invalid context words
                }
            }

            // Add to cache updates grouped by file
            $cacheUpdates[$firstChar][$word] = $contextUpdates[$word] ?? [];
            if (!isset($cacheUpdates[$firstChar][$word])) {
                $cacheUpdates[$firstChar][$word] = $context;
            } else {
                foreach ($context as $contextWord => $count) {
                    $cacheUpdates[$firstChar][$word][$contextWord] = 
                        ($cacheUpdates[$firstChar][$word][$contextWord] ?? 0) + $count;
                }
            }
        }
    }

    // Write updates to cache files
    foreach ($cacheUpdates as $fileKey => $embeddings) {
        $cacheFile = $cacheDir . $fileKey . '.php';

        // Load existing cache if it exists
        $existingEmbeddings = [];
        if (file_exists($cacheFile)) {
            $existingEmbeddings = include $cacheFile;
        }

        // Merge updates with existing embeddings
        foreach ($embeddings as $word => $context) {
            if (!isset($existingEmbeddings[$word])) {
                $existingEmbeddings[$word] = $context;
            } else {
                foreach ($context as $contextWord => $count) {
                    $existingEmbeddings[$word][$contextWord] = 
                        ($existingEmbeddings[$word][$contextWord] ?? 0) + $count;
                }
            }
        }

        // Write the updated cache file
        file_put_contents($cacheFile, '<?php return ' . var_export($existingEmbeddings, true) . ';');
    }

    // back_trace( 'NOTICE', 'transformer_model_sentential_context_cache_embeddings - end');

}

