<?php
/**
 * Kognetiks Chatbot for WordPress - Transformer Model - Scheduler - Ver 2.1.6
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

// Handle long-running scripts with a scheduled event function - Ver 1.6.1
function chatbot_transformer_model_scheduler() {

    // Retrieve the schedule setting
    $chatbot_transformer_model_build_schedule = esc_attr(get_option('chatbot_transformer_model_build_schedule', 'Disable'));

    // Exit if the scheduler is No
    if ($chatbot_transformer_model_build_schedule === 'No') {
        wp_clear_scheduled_hook('chatbot_transformer_model_scan_hook');
        update_option('chatbot_transformer_model_build_status', 'No Schedule');
        prod_trace( 'NOTICE', 'chatbot_transformer_model_scheduler: ' . $chatbot_transformer_model_build_schedule);
        prod_trace( 'NOTICE', 'chatbot_transformer_model_build_status: ' . $chatbot_transformer_model_build_status);
        return; // Exit if the scheduler is No
    }

    // Exit if the scheduler is disabled
    if ($chatbot_transformer_model_build_schedule === 'Disable') {
        wp_clear_scheduled_hook('chatbot_transformer_model_scan_hook');
        update_option('chatbot_transformer_model_build_status', 'Disabled');
        prod_trace( 'NOTICE', 'chatbot_transformer_model_scheduler: ' . $chatbot_transformer_model_build_schedule);
        prod_trace( 'NOTICE', 'chatbot_transformer_model_build_status: ' . $chatbot_transformer_model_build_status);
        return; // Exit if the scheduler is disabled
    }

    // Exit if the scheduler is cancelled
    if ($chatbot_transformer_model_build_schedule === 'Cancel') {
        wp_clear_scheduled_hook('chatbot_transformer_model_scan_hook');
        update_option('chatbot_transformer_model_build_schedule', 'No');
        update_option('chatbot_transformer_model_build_status', 'Cancelled');
        prod_trace( 'NOTICE', 'chatbot_transformer_model_scheduler: ' . $chatbot_transformer_model_build_schedule);
        prod_trace( 'NOTICE', 'chatbot_transformer_model_build_status: ' . $chatbot_transformer_model_build_status);
        return;

    }

    // If not set, set the schedule to 'No'
    if (!isset($chatbot_transformer_model_build_schedule)) {
        wp_clear_scheduled_hook('chatbot_transformer_model_scan_hook');
        update_option('chatbot_transformer_model_build_schedule', 'No');
        update_option('chatbot_transformer_model_build_status', 'No Schedule');
        prod_trace( 'NOTICE', 'chatbot_transformer_model_scheduler: ' . $chatbot_transformer_model_build_schedule);
        prod_trace( 'NOTICE', 'chatbot_transformer_model_build_status: ' . $chatbot_transformer_model_build_status);
        return;

    }

    // DIAG - Diagnostic - Ver 2.1.6
    back_trace( 'NOTICE', 'Scheduler started');

    // Update the status as 'In Process'
    $chatbot_transformer_model_build_schedule = get_option('chatbot_transformer_model_build_schedule', 'No');
    prod_trace( 'NOTICE', 'chatbot_transformer_model_build_schedule: ' . $chatbot_transformer_model_build_schedule);
    $chatbot_transformer_model_build_status = get_option('chatbot_transformer_model_build_status', 'In Process');
    update_option('chatbot_transformer_model_build_status', 'In Process');
    prod_trace( 'NOTICE', 'chatbot_transformer_model_build_status: ' . $chatbot_transformer_model_build_status);

    // Reset the cache file if $offset is 0
    if ($offset === 0) {
        transformer_model_sentential_context_reset_cache();
    }

    // Schedule the first scan
    back_trace( 'NOTICE', 'INITIAL SCAN chatbot_transformer_model_scan_hook scheduled');
    wp_schedule_single_event(time() + 10, 'chatbot_transformer_model_scan_hook');

    // DIAG - Diagnostic - Ver 2.1.6
    // back_trace( 'NOTICE', 'chatbot_transformer_model_scheduler - END');
}
add_action('chatbot_transformer_model_scheduler_hook', 'chatbot_transformer_model_scheduler');

// Transformer model reset the cache file
function transformer_model_sentential_context_reset_cache() {

    // DIAG - Diagnostic - Ver 2.2.0
    back_trace( 'NOTICE', 'Cache and Offset Reset Start');

    // Reset the offset
    update_option('chatbot_transformer_model_offset', 0);

    // Reset the cache file
    $cacheFile = __DIR__ . '/sentential_embeddings_cache.php';
   
    if (file_exists($cacheFile)) {
        unlink($cache_file);
        back_trace( 'NOTICE', $cacheFile . ' file deleted');
    }

    back_trace( 'NOTICE', 'Cache and Offset Reset End');

}

// Check if the Transformer Model needs to be built or updated
function chatbot_transformer_model_scan( $offset = 0 ) {

    // DIAG - Diagnostics - Ver 2.1.6
    back_trace( 'NOTICE', 'Scan Start');

    // Retrieve current state (offset and completed status)
    $offset = intval(get_option('chatbot_transformer_model_offset', 0));
    $batchSize = 50; // Process 50 posts/pages at a time
    $processedItems = 0;

    // Get the current schedule setting
    $run_scanner = get_option('chatbot_transformer_model_build_schedule', 'No');

    // Update the status to 'In Process' and log the current time
    update_option('chatbot_transformer_model_build_status', 'In Process');

    if (!isset($run_scanner)) {
        $run_scanner = 'No';
    }

    // Reset the results message
    update_option('chatbot_transformer_model_build_results', '');

    // Run the Transformer Model building and saving process
    $offset = intval(get_option('chatbot_transformer_model_offset', 0)); // Start from 0 or the next offset
    $batchSize = intval(get_option('chatbot_transform_model_batch_size', 50)); // Process 50 posts/pages at a time

    // Fetch WordPress content
    $corpus = transformer_model_sentential_context_fetch_content( $offset, $batchSize);

    // Build embeddings (with caching for performance)
    $embeddings = transformer_model_sentential_context_cache_embeddings($corpus);

    // DIAG - Diagnostics - Ver 2.1.6
    // back_trace( 'NOTICE', 'chatbot_transformer_model_scan - End');

    // Schedule the next batch if there are more items to process
    if ($offset > 0) {
        wp_schedule_single_event(time() + 10, 'chatbot_transformer_model_scan_hook');
    } else {
        update_option('chatbot_transformer_model_build_schedule', 'Completed');
    }

    // DIAG - Diagnostics - V 2.2.0
    back_trace( 'NOTICE', 'Processed ' . $processedItems . ' items');
    back_trace( 'NOTICE', 'Next offset is ' . ($offset + $processedItems));

}
add_action('chatbot_transformer_model_scan_hook', 'chatbot_transformer_model_scan');

function transformer_model_sentential_context_fetch_content( $offset, $batchSize ) {

    global $wpdb;

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
        back_trace( 'NOTICE', 'chatbot_transformer_model_scheduler - All posts processed.');
        update_option('chatbot_chatgpt_scan_interval', 'Complete');
        update_option('chatbot_transformer_model_last_updated', current_time('mysql'));
        update_option('chatbot_transformer_model_build_schedule', 'Completed');
        return;
    }

}

// Transformer Model - Sentential Context - Cache Embeddings
function transformer_model_sentential_context_cache_embeddings( $corpus ) {

    // DIAG - Diagnostic - Ver 2.2.0
    back_trace( 'NOTICE', 'Cache Embeddings Start');

    $processedItems = 0;

    // Process the current batch of posts/pages
    $embeddings = [];
    foreach ($corpus as $row) {
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

    // DIAG - Diagnostic - Ver 2.2.0
    back_trace( 'NOTICE', 'Cache Embeddings End');

}

// Transformer Model Build Schedule handler
function chatbot_transformer_model_build_results_callback($run_scanner) {

    // DIAG - Diagnostic - Ver 2.1.6
    // back_trace( 'NOTICE', 'chatbot_transformer_model_build_results_callback');
    // back_trace( 'NOTICE', '$run_scanner: ' . $run_scanner);
    // back_trace( 'NOTICE', 'chatbot_transformer_model_build_schedule: ' . esc_attr(get_option('chatbot_transformer_model_build_schedule')));

    update_option('chatbot_transformer_model_last_updated', date('Y-m-d H:i:s'));

    if (!isset($run_scanner)) {
        $run_scanner = 'No';
    }

    // Clear and reschedule hooks based on $run_scanner
    if (in_array($run_scanner, ['Now', 'Hourly', 'Daily', 'Twice Daily', 'Weekly', 'Disable', 'Cancel'])) {
        
        // Clear any existing hooks
        wp_clear_scheduled_hook('chatbot_transformer_model_scan_hook');
        
        if ($run_scanner === 'Cancel' || $run_scanner === 'Disable') {

            // Handle 'Cancel' and 'Disable'
            $status = ($run_scanner === 'Cancel') ? 'Cancelled' : 'Disabled';
            update_option('chatbot_transformer_model_build_status', $status);
            update_option('chatbot_transformer_model_build_schedule', 'No');
            update_option('chatbot_transformer_model_scan_interval', 'No Schedule');
            update_option('chatbot_transformer_model_build_action', strtolower($status));

        } else {

            if (!wp_next_scheduled('chatbot_transformer_model_scan_hook')) {
                // Log the schedule
                update_option('chatbot_transformer_model_build_status', 'In Process');

                // Handle valid scheduling options
                $interval_mapping = [
                    'Now' => 10, // Immediate execution, 10 seconds from now
                    'Hourly' => 'hourly',
                    'Twice Daily' => 'twicedaily',
                    'Daily' => 'daily',
                    'Weekly' => 'weekly'
                ];

                $timestamp = time() + 10; // Run 10 seconds from now
                $interval = $interval_mapping[$run_scanner];

                if ($run_scanner === 'Now') {
                    wp_schedule_single_event($timestamp, 'chatbot_transformer_model_scan_hook');
                } else {
                    wp_schedule_event($timestamp, $interval, 'chatbot_transformer_model_scan_hook');
                }

                // Log scan interval - Ver 2.1.6
                if ($interval === 'Now') {
                    update_option('chatbot_transformer_model_scan_interval', 'No Schedule');
                } else {
                    update_option('chatbot_transformer_model_scan_interval', $run_scanner);
                }

                // Reset before reloading the page
                $run_scanner = 'No';
                update_option('chatbot_transformer_model_build_schedule', 'No');
            }
        }
    }
}