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
        prod_trace('NOTICE', 'chatbot_transformer_model_scheduler: ' . $chatbot_transformer_model_build_schedule);
        return;
    }

    // Diagnostic logging
    // back_trace( 'NOTICE', 'Scheduler started');

    // Update the status as 'In Process'
    update_option('chatbot_transformer_model_build_status', 'In Process');
    prod_trace('NOTICE', 'chatbot_transformer_model_build_status: ' . $chatbot_transformer_model_build_status);

    // Reset the cache file if offset is 0
    if (get_option('chatbot_transformer_model_offset', 0) === 0) {
        // Reset the cache file and offset
        transformer_model_sentential_context_reset_cache();
        // Reset the content items processed
        update_option('chatbot_transformer_model_content_items_processed', 0);
    }

    // Schedule the first scan
    wp_schedule_single_event(time() + 10, 'chatbot_transformer_model_scan_hook');

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', 'chatbot_transformer_model_scheduler - end');

}
add_action('chatbot_transformer_model_scheduler_hook', 'chatbot_transformer_model_scheduler');

// Reset the cache file and offset
function transformer_model_sentential_context_reset_cache() {

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', 'transformer_model_sentential_context_reset_cache - start');

    // DIAG - Diagnostics
    prod_trace( 'NOTICE', 'Transformer Model Content Cache Reset');

    update_option('chatbot_transformer_model_offset', 0);
    update_option('chatbot_transformer_model_content_items_processed', 0);
    $cacheFile = __DIR__ . '/sentential_embeddings_cache.php';

    if (file_exists($cacheFile)) {
        unlink($cacheFile);
        // back_trace( 'NOTICE', "$cacheFile deleted");
    }

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', 'transformer_model_sentential_context_reset_cache - end');

}

// Check if the Transformer Model needs to be built or updated
function chatbot_transformer_model_scan() {

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', 'hatbot_transformer_model_scan - start');

    // Retrieve current state
    $offset = intval(get_option('chatbot_transformer_model_offset', 0));
    $batchSize = intval(get_option('chatbot_transform_model_batch_size', 50));
    $chatbot_transformer_model_content_items_processed = intval(get_option('chatbot_transformer_model_content_items_processed', 0));
    $corpus = transformer_model_sentential_context_fetch_content($offset, $batchSize);

    if (empty($corpus)) {

        update_option('chatbot_transformer_model_build_status', 'Completed');
        update_option('chatbot_transformer_model_build_schedule', 'Completed');
        update_option('chatbot_transformer_model_content_items_processed', 0);
        update_option('chatbot_transformer_model_last_updated', current_time('mysql'));
        prod_trace('NOTICE', 'chatbot_transformer_model_build_schedule: Completed');

        // DIAG - Diagnostics
        // back_trace( 'NOTICE', 'All items processed. Scan complete.');

        return;
    }

    // Retrieve the window size
    $windowSize = intval(esc_attr(get_option('chatbot_transformer_model_word_content_window_size', 3)));

    // Build embeddings
    $embeddings = transformer_model_sentential_context_cache_embeddings($corpus, $windowSize);

    // Update the offset
    $processedItems = count($corpus);
    update_option('chatbot_transformer_model_offset', $offset + $processedItems);
    update_option('chatbot_transformer_model_content_items_processed', $chatbot_transformer_model_content_items_processed + $processedItems);


    // Log the processed batch
    prod_trace( 'NOTICE', 'Processed ' . $processedItems . ' items starting at offset ' . $offset);

    // Schedule the next batch if needed
    wp_schedule_single_event(time() + 10, 'chatbot_transformer_model_scan_hook');

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', 'Next batch scheduled');

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
    }

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', 'transformer_model_sentential_context_fetch_content - end');

    return $results;

}

// Cache embeddings for the fetched content
function transformer_model_sentential_context_cache_embeddings($corpus, $windowSize = 3) {

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', 'transformer_model_sentential_context_cache_embeddings - start');

    $cacheFile = __DIR__ . '/sentential_embeddings_cache.php';

    // Check if embeddings are cached
    if (file_exists($cacheFile)) {
        $embeddings = include $cacheFile;
    } else {
        $embeddings = [];
    }

    // Build new embeddings
    foreach ($corpus as $row) {
        $postContent = strip_tags(html_entity_decode($row['post_content'], ENT_QUOTES | ENT_HTML5));
        $postEmbeddings = transformer_model_sentential_context_build_cooccurrence_matrix($postContent, $windowSize);
        foreach ($postEmbeddings as $word => $context) {
            if (!isset($embeddings[$word])) {
                $embeddings[$word] = $context;
            } else {
                foreach ($context as $contextWord => $count) {
                    if (isset($embeddings[$word][$contextWord])) {
                        $embeddings[$word][$contextWord] += $count;
                    } else {
                        $embeddings[$word][$contextWord] = $count;
                    }
                }
            }
        }
    }

    // Cache the embeddings
    file_put_contents($cacheFile, '<?php return ' . var_export($embeddings, true) . ';');

    return $embeddings;

}