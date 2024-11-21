<?php
/**
 * Kognetiks Chatbot for WordPress - Transformer Model - Scheduler - Ver 2.2.0
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

    // Retrieve the schedule setting
    $chatbot_transformer_model_build_schedule = esc_attr(get_option('chatbot_transformer_model_build_schedule', 'Disable'));

    // Exit if the scheduler is not enabled
    if (in_array($chatbot_transformer_model_build_schedule, ['No', 'Disable', 'Cancel'])) {
        wp_clear_scheduled_hook('chatbot_transformer_model_scan_hook');
        update_option('chatbot_transformer_model_build_status', 'No Schedule');
        prod_trace('NOTICE', 'chatbot_transformer_model_scheduler: ' . $chatbot_transformer_model_build_schedule);
        return;
    }

    // Diagnostic logging
    back_trace('NOTICE', 'Scheduler started');

    // Update the status as 'In Process'
    update_option('chatbot_transformer_model_build_status', 'In Process');
    prod_trace('NOTICE', 'chatbot_transformer_model_build_schedule: ' . $chatbot_transformer_model_build_schedule);

    // Reset the cache file if offset is 0
    if (get_option('chatbot_transformer_model_offset', 0) === 0) {
        transformer_model_sentential_context_reset_cache();
    }

    // Schedule the first scan
    wp_schedule_single_event(time() + 10, 'chatbot_transformer_model_scan_hook');
    back_trace('NOTICE', 'Initial scan scheduled');

}
add_action('chatbot_transformer_model_scheduler_hook', 'chatbot_transformer_model_scheduler');

// Reset the cache file and offset
function transformer_model_sentential_context_reset_cache() {

    back_trace('NOTICE', 'Cache and Offset Reset Start');
    update_option('chatbot_transformer_model_offset', 0);
    $cacheFile = __DIR__ . '/sentential_embeddings_cache.php';

    if (file_exists($cacheFile)) {
        unlink($cacheFile);
        back_trace('NOTICE', "$cacheFile deleted");
    }

    back_trace('NOTICE', 'Cache and Offset Reset End');

}

// Check if the Transformer Model needs to be built or updated
function chatbot_transformer_model_scan() {

    back_trace('NOTICE', 'Scan Start');

    // Retrieve current state
    $offset = intval(get_option('chatbot_transformer_model_offset', 0));
    $batchSize = intval(get_option('chatbot_transform_model_batch_size', 50));
    $corpus = transformer_model_sentential_context_fetch_content($offset, $batchSize);

    if (empty($corpus)) {
        update_option('chatbot_transformer_model_build_status', 'Completed');
        update_option('chatbot_transformer_model_build_schedule', 'Completed');
        back_trace('NOTICE', 'All items processed. Scan complete.');
        return;
    }

    // Build embeddings
    $embeddings = transformer_model_sentential_context_cache_embeddings($corpus);

    // Update the offset
    $processedItems = count($corpus);
    update_option('chatbot_transformer_model_offset', $offset + $processedItems);

    // Log the processed batch
    back_trace('NOTICE', 'Processed ' . $processedItems . ' items starting at offset ' . $offset);

    // Schedule the next batch if needed
    wp_schedule_single_event(time() + 10, 'chatbot_transformer_model_scan_hook');
    back_trace('NOTICE', 'Next batch scheduled');

}
add_action('chatbot_transformer_model_scan_hook', 'chatbot_transformer_model_scan');

// Fetch WordPress content
function transformer_model_sentential_context_fetch_content($offset, $batchSize) {

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
        back_trace('NOTICE', 'No more posts to process.');
        return [];
    }

    return $results;

}

// Cache embeddings for the fetched content
function transformer_model_sentential_context_cache_embeddings($corpus) {

    back_trace('NOTICE', 'Cache Embeddings Start');

    $embeddings = [];
    foreach ($corpus as $row) {
        $postID = $row['ID'];
        $postContent = strip_tags(html_entity_decode($row['post_content'], ENT_QUOTES | ENT_HTML5));
        $postEmbeddings = transformer_model_sentential_context_build_cooccurrence_matrix($postContent, 2);
        $embeddings[$postID] = $postEmbeddings;
    }

    // Save embeddings to the cache file
    $cacheFile = __DIR__ . '/sentential_embeddings_cache.php';
    $existingCache = file_exists($cacheFile) ? include $cacheFile : [];
    $mergedEmbeddings = array_merge($existingCache, $embeddings);
    file_put_contents($cacheFile, '<?php return ' . var_export($mergedEmbeddings, true) . ';');

    back_trace('NOTICE', 'Cache Embeddings End');

    return $embeddings;

}