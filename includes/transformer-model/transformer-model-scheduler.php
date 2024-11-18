<?php
/**
 * Kognetiks Chatbot for WordPress - Transformer Sentential Context Model Scheduler - Ver 2.2.0
 *
 * This file contains the code for the Transformer settings page.
 * It manages the settings and other parameters.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Schedule the Transformer Model Scheduler
function chatbot_transformer_model_scheduler() {

    // Retrieve the schedule setting
    $chatbot_transformer_model_build_schedule = esc_attr(get_option('chatbot_transformer_model_build_schedule', 'Disable'));

    if ($chatbot_transformer_model_build_schedule === 'Disable' || $chatbot_transformer_model_build_schedule === 'Completed') {
        return; // Exit if the scheduler is disabled
    }

    // DIAG - Diagnostics - V 2.2.0
    back_trace('NOTICE', 'chatbot_transformer_model_scheduler - START');

    // Retrieve current state (offset and completed status)
    $offset = intval(get_option('chatbot_transformer_model_offset', 0));
    $batchSize = 50; // Process 50 posts/pages at a time
    $processedItems = 0;

    // If $offset is 0, reset the cache file
    if ($offset === 0) {
        $cacheFile = __DIR__ . '/sentential_embeddings_cache.php';
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }

    global $wpdb;

    // Update the status
    update_option('chatbot_chatgpt_scan_interval', 'In Progress');

    // Fetch next batch of posts/pages to process
    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT ID, post_content FROM {$wpdb->posts} WHERE post_status = %s AND (post_type = %s OR post_type = %s) LIMIT %d, %d",
            'publish', 'post', 'page', $offset, $batchSize
        ),
        ARRAY_A
    );

    // Check if there are items to process
    if (empty($results)) {
        // All posts/pages are processed, reset offset and stop scheduling
        update_option('chatbot_transformer_model_offset', 0);
        back_trace('NOTICE', 'chatbot_transformer_model_scheduler - All posts processed.');
        update_option('chatbot_chatgpt_scan_interval', 'Complete');
        update_option('chatbot_transformer_model_last_updated', current_time('mysql'));
        update_option('chatbot_transformer_model_build_schedule', 'Completed');
        return;
    }

    // Process the current batch of posts/pages
    $embeddings = [];
    foreach ($results as $row) {
        $postID = $row['ID'];
        $postContent = strip_tags(html_entity_decode($row['post_content'], ENT_QUOTES | ENT_HTML5));
        $postEmbeddings = transformer_model_sentential_context_build_cooccurrence_matrix($postContent, 2);
        $embeddings[$postID] = $postEmbeddings;
        $processedItems++;
    }

    // Save embeddings to the cache file
    $cacheFile = __DIR__ . '/sentential_embeddings_cache.php';
    $existingCache = file_exists($cacheFile) ? include $cacheFile : [];
    $mergedEmbeddings = array_merge($existingCache, $embeddings);
    file_put_contents($cacheFile, '<?php return ' . var_export($mergedEmbeddings, true) . ';');

    // Update the offset for the next batch
    update_option('chatbot_transformer_model_offset', $offset + $processedItems);

    // Schedule the next run (2 minutes later)
    if (!wp_next_scheduled('chatbot_transformer_model_scheduler_event')) {
        wp_schedule_single_event(time() + 120, 'chatbot_transformer_model_scheduler_event');
    }

    // DIAG - Diagnostics - V 2.2.0
    back_trace('NOTICE', "Processed $processedItems items, next offset: " . ($offset + $processedItems));
}

// Hook the scheduler function to WordPress
add_action('chatbot_transformer_model_scheduler_event', 'chatbot_transformer_model_scheduler');
