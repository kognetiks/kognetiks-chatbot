<?php
/**
 * Kognetiks Chatbot - Transformer Model - Scheduler - Ver 2.2.1
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
        // prod_trace( 'NOTICE', 'chatbot_transformer_model_scheduler: ' . $chatbot_transformer_model_build_schedule);
        return;
    }

    // Reset the cache file if offset is 0
    if (in_array($chatbot_transformer_model_build_schedule, ['Now', 'Hourly', 'Twice Daily', 'Daily', 'Weekly'])) {

        // Update the status as 'In Process'
        update_option('chatbot_transformer_model_build_status', 'In Process');

        prod_trace( 'NOTICE', 'chatbot_transformer_model_build_status: ' . $chatbot_transformer_model_build_status);

        // Reset the cache fil
        transformer_model_sentential_context_reset_cache();

        // Reset the offset
        update_option('chatbot_transformer_model_offset', 0);

        // Reset the content items processed
        update_option('chatbot_transformer_model_content_items_processed', 0);
            
        // Schedule the first scan
        wp_schedule_single_event(time() + 10, 'chatbot_transformer_model_scan_hook');

    }

}
add_action('chatbot_transformer_model_scheduler_hook', 'chatbot_transformer_model_scheduler');

// Reset the cache files and offset
function transformer_model_sentential_context_reset_cache() {

    // Log reset activity
    prod_trace( 'NOTICE', 'Transformer Model Content Cache Reset');

    // Reset offset and content items processed
    update_option('chatbot_transformer_model_offset', 0);
    update_option('chatbot_transformer_model_content_items_processed', 0);

    chatbot_chatgpt_beta_engine_purge( 'sentential' );
    chatbot_chatgpt_beta_engine_mark_building( 'sentential' );
    // Job owner is stored by GFI begin for later scan/commit requests.

}


// Check if the Transformer Model needs to be built or updated
function chatbot_transformer_model_scan() {

    // Retrieve current state
    $offset = intval(get_option('chatbot_transformer_model_offset', 0));
    $batchSize = intval(get_option('chatbot_transform_model_batch_size', 50));
    $corpus = transformer_model_sentential_context_fetch_content($offset, $batchSize);

    if ( (int) $corpus['raw'] === 0 ) {
        $generation = (int) get_option( chatbot_chatgpt_beta_engine_build_generation_key( 'sentential' ), 0 );
        $owner      = function_exists( 'chatbot_chatgpt_gfi_job_owner' ) ? chatbot_chatgpt_gfi_job_owner( 'sentential' ) : '';
        if ( $owner !== '' && function_exists( 'chatbot_chatgpt_gfi_remember_owner' ) ) {
            chatbot_chatgpt_gfi_remember_owner( 'sentential', $owner );
        }
        if ( chatbot_chatgpt_beta_engine_commit( 'sentential', $generation, $owner ) ) {
            update_option('chatbot_transformer_model_build_status', 'Completed');
            update_option('chatbot_transformer_model_build_schedule', 'Completed');
            update_option('chatbot_transformer_model_last_updated', current_time('mysql'));
            prod_trace( 'NOTICE', 'chatbot_transformer_model_build_schedule: Completed');
        } else {
            update_option('chatbot_transformer_model_build_status', 'Invalidated');
        }
        return;
    }

    // Retrieve the window size
    $windowSize = intval(esc_attr(get_option('chatbot_transformer_model_word_content_window_size', 3)));

    // Build embeddings with the updated logic
    if ( ! empty( $corpus['items'] ) ) {
        transformer_model_sentential_context_cache_embeddings($corpus['items'], $windowSize);
    }

    // Advance by the SQL page size so filtered rows cannot stall or skip the rest of the corpus.
    update_option('chatbot_transformer_model_offset', $offset + (int) $corpus['raw']);

    prod_trace( 'NOTICE', 'Processed ' . count( $corpus['items'] ) . ' eligible items starting at offset ' . $offset );

    // Schedule the next batch if needed
    wp_schedule_single_event(time() + 10, 'chatbot_transformer_model_scan_hook');

}
add_action('chatbot_transformer_model_scan_hook', 'chatbot_transformer_model_scan');

// Fetch WordPress content
function transformer_model_sentential_context_fetch_content($offset, $batchSize) {

    global $wpdb;

    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT ID, post_content, post_type, post_status FROM {$wpdb->posts} WHERE " . chatbot_chatgpt_knowledge_post_sql_clause() . " AND (post_type = %s OR post_type = %s) LIMIT %d, %d",
            'post', 'page', $offset, $batchSize
        ),
        ARRAY_A
    );

    if (empty($results)) {
        return array(
            'items' => array(),
            'raw'   => 0,
        );
    }

    $eligible = array();
    foreach ( $results as $row ) {
        $post_id = isset( $row['ID'] ) ? (int) $row['ID'] : 0;
        if ( $post_id <= 0 || ! chatbot_chatgpt_is_post_eligible_for_knowledge( $post_id ) ) {
            continue;
        }
        $row['post_content'] = chatbot_chatgpt_post_content_for_knowledge( $post_id );
        if ( $row['post_content'] === '' ) {
            continue;
        }
        $eligible[] = $row;
    }

    return array(
        'items' => $eligible,
        'raw'   => count( $results ),
    );

}

// Cache embeddings for the fetched content
function transformer_model_sentential_context_cache_embeddings($corpus, $windowSize = 3) {

    $owner = function_exists( 'chatbot_chatgpt_gfi_job_owner' ) ? chatbot_chatgpt_gfi_job_owner( 'sentential' ) : '';
    if ( $owner === '' && function_exists( 'chatbot_chatgpt_gfi_session_owner' ) ) {
        $owner = chatbot_chatgpt_gfi_session_owner( 'sentential' );
    }
    if ( $owner === '' || ( function_exists( 'chatbot_chatgpt_gfi_builder_may_mutate' ) && ! chatbot_chatgpt_gfi_builder_may_mutate( 'sentential', $owner ) ) ) {
        return;
    }
    if ( function_exists( 'chatbot_chatgpt_gfi_remember_owner' ) ) {
        chatbot_chatgpt_gfi_remember_owner( 'sentential', $owner );
    }
    if ( function_exists( 'chatbot_chatgpt_gfi_touch' ) ) {
        chatbot_chatgpt_gfi_touch( 'sentential', $owner );
    }
    $cacheDir = chatbot_chatgpt_beta_engine_cache_directory( 'sentential' );
    if ( $cacheDir === '' ) {
        return;
    }
    $cacheDir = rtrim( $cacheDir, '/\\' ) . '/';

    // Ensure the cache directory exists
    if (!create_directory_and_index_file($cacheDir)) {
        return;
    }

    $generation = (int) get_option( chatbot_chatgpt_beta_engine_build_generation_key( 'sentential' ), 0 );
    if ( ! chatbot_chatgpt_beta_engine_build_is_current( 'sentential', $generation, $owner ) ) {
        return;
    }

    $cacheUpdates = []; // To group updates by file

    // Process the content to build embeddings
    foreach ($corpus as $row) {

        $postContent = wp_strip_all_tags(strip_shortcodes(html_entity_decode($row['post_content'], ENT_QUOTES | ENT_HTML5)));
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
        $generation = (int) get_option( chatbot_chatgpt_beta_engine_build_generation_key( 'sentential' ), 0 );
        $can_merge = chatbot_chatgpt_beta_engine_build_is_current( 'sentential', $generation, $owner )
            && ( chatbot_chatgpt_beta_engine_state( 'sentential' ) === 'building' || chatbot_chatgpt_beta_engine_stamp_matches( 'sentential' ) );
        if ( $can_merge && file_exists($cacheFile)) {
            if ( chatbot_chatgpt_beta_engine_state( 'sentential' ) === 'building' ) {
                $existingEmbeddings = chatbot_chatgpt_gfi_include_builder_literal( $cacheFile );
            } elseif ( function_exists( 'chatbot_chatgpt_gfi_include_trusted' ) ) {
                $existingEmbeddings = chatbot_chatgpt_gfi_include_trusted( 'sentential', $cacheFile );
                if ( ! is_array( $existingEmbeddings ) ) {
                    return;
                }
            } else {
                return;
            }
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

        // Stale workers that lost build ownership must not rewrite sealed letter files.
        if ( function_exists( 'chatbot_chatgpt_gfi_builder_may_mutate' ) && ! chatbot_chatgpt_gfi_builder_may_mutate( 'sentential', $owner ) ) {
            return;
        }

        // Write the updated cache file with a direct-access ABSPATH guard.
        if ( function_exists( 'chatbot_chatgpt_gfi_literal_cache_php' ) ) {
            $cache_php = chatbot_chatgpt_gfi_literal_cache_php( $existingEmbeddings );
        } else {
            $cache_php = "<?php\n\nif ( ! defined( 'ABSPATH' ) ) {\n    http_response_code( 403 );\n    exit;\n}\n\nreturn " . var_export( $existingEmbeddings, true ) . ';';
        }
        file_put_contents( $cacheFile, $cache_php );
        chatbot_chatgpt_beta_apply_cache_permissions( $cacheDir );
        chatbot_chatgpt_beta_apply_cache_permissions( $cacheFile );
    }


}

