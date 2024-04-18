<?php
/**
 * Kognetiks Chatbot for WordPress - Settings - Knowledge Navigator - Acquire Content Awareness
 *
 * This file contains the code for the Chatbot Knowledge Navigator.
 * 
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Batch the acquisition of site content
//
// This process is intended to scale to large sites with many pages, posts and products.
//
// Start with the first batch and acquire the content for each published post, page, or product
// The results in the chatbot_chatgpt_knowledge_base table.
//
// This process is run in the background using the WordPress cron system.
//
// The frequency of the batch acquisition can be set in the Chatbot Knowledge Navigator settings.
//
// The knowledge acquisition is run in three steps:
// 1. Initialize - Initialize the batch acquisition for posts, pages, and products
// 2. Run - Acquires the content for each post in the batch
// 3. Reinitialize - Reinitialize the batch acquisition for comments
// 4. Run - Acquires the content for each comment in the batch
// 5. Analyze - Analyze the acquired content
//
// The knowledge acquisition can be cancelled at any time.
//
// The knowledge acquisition is completed when all publised pages, posts and products have been analyzed.
//
// The batch acquisition can be run manually by clicking the setting the "Select Run Schedule" to "Now"
// in the Chatbot Knowledge Navigator settings.
//
// The batch acquisition can be cancelled manually by clicking the setting the "Select Run Schedule" to
// one of "Now", "Hourly", "Twice Daily", "Daily" or "Weekly" in the Chatbot Knowledge Navigator settings.
//

// Chatbot Knowledge Navigator - Controller
function chatbot_kn_acquire_controller() {

    // Get the current action
    $action = esc_attr( get_option( 'chatbot_chatgpt_kn_action', 'initialize' ) ); // Default to run to kickoff the process

    switch ( $action ) {
        case 'initialize':
            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'chatbot_chatgpt_kn_action: ' . $action );
            chatbot_kn_initalization();
            break;
        case 'phase 1':
            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'chatbot_chatgpt_kn_action: ' . $action );
            chatbot_kn_run_post_acquisition();
            break;
        case 'phase 2':
            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'chatbot_chatgpt_kn_action: ' . $action );
            chatbot_kn_reinitialization();
            break;
        case 'phase 3':
            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'chatbot_chatgpt_kn_action: ' . $action );
            chatbot_kn_run_comment_acquisition();
            update_option( 'chatbot_chatgpt_kn_action', 'completed' );
            break;
        case 'phase 4':
            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'chatbot_chatgpt_kn_action: ' . $action );
            update_option( 'chatbot_chatgpt_kn_action', 'completed' );
            break;
        case 'cancel':
            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'chatbot_chatgpt_kn_action: ' . $action );
            // chatbot_kn_cancel_batch_acquisition();
            update_option( 'chatbot_chatgpt_kn_action', 'cancelled' );
            break;
        default:
            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'chatbot_chatgpt_kn_action: ' . $action );
            break;
    }

}
// Add the action hook
add_action( 'chatbot_kn_acquire_controller', 'chatbot_kn_acquire_controller' );

// Initialize the knowledge acquisition process
function chatbot_kn_initalization() {

    global $wpdb;
    global $topWords;
    global $topWordPairs;
    global $max_top_words;

    // DIAG - Diagnostics - Ver 1.9.6
    back_trace( 'NOTICE', 'chatbot_kn_phase_1_initalization' );

    // Since this is the first step, set the item count = 1
    update_option( 'chatbot_chatgpt_kn_item_count', 1 );

    // Initialize the $topWords array
    $topWords = [];
    $topWordPairs = [];

    // Reset the chatbot_chatgpt_knowledge_base table
    dbKNStore();

    // chatbot_kn_schedule_batch_acquisition();
    update_option( 'chatbot_chatgpt_kn_action', 'phase 1' );

    // Reset the number of items analyzed
    update_option('no_of_items_analyzed', 1);

    // Schedule the next action
    wp_schedule_single_event( time() + 2, 'chatbot_kn_acquire_controller' );

}

function chatbot_kn_reinitialization() {

    global $wpdb;
    global $topWords;
    global $topWordPairs;
    global $max_top_words;

    // Initialize the $topWords array
    $topWords = [];
    $topWordPairs = [];

    // DIAG - Diagnostics - Ver 1.9.6
    back_trace( 'NOTICE', 'chatbot_kn_phase_2_initialization' );

    update_option('chatbot_chatgpt_kn_item_count', 1);

    update_option('chatbot_chatgpt_kn_action', 'phase 3');

    // Schedule the next action
    wp_schedule_single_event( time() + 2, 'chatbot_kn_acquire_controller' );

}

// Acquire the content for each page, post, or product in the run
function chatbot_kn_run_post_acquisition() {

    global $wpdb;
    global $topWords;
    global $topWordPairs;
    global $max_top_words;

    // DIAG - Diagnostics - Ver 1.9.6
    back_trace( 'NOTICE', 'chatbot_kn_run_post_acquisition' );

    // Get the item count
    $offset = get_option('chatbot_chatgpt_kn_item_count', 1); // Default offset set to 1 if not specified
    $batch_size = get_option('chatbot_kn_items_per_batch', 50); // Fetching 100 items at a time
    $no_of_items_analyzed = get_option('no_of_items_analyzed', 1);

    // DIAG - Diagnostics - Ver 1.9.6
    back_trace( 'NOTICE', '$offset: ' . $offset );
    back_trace( 'NOTICE', '$batch_size:' . $batch_size );
    back_trace( 'NOTICE', '$no_of_items_analyzed: ' . $no_of_items_analyzed );

    // Set the next starting point
    update_option( 'chatbot_chatgpt_kn_item_count', $offset + $batch_size );

    // Define published types to include based on settings
    $post_types = [];
    if (get_option('chatbot_chatgpt_kn_include_pages', 'No') === 'Yes') {
        $post_types[] = 'page';
    }
    if (get_option('chatbot_chatgpt_kn_include_posts', 'No') === 'Yes') {
        $post_types[] = 'post';
        $post_types[] = 'epkb_post_type_1';  // Assuming you always want to include this type
    }
    if (get_option('chatbot_chatgpt_kn_include_products', 'No') === 'Yes') {
        $post_types[] = 'product';
    }

    // Prepare the SQL query part for post types
    $placeholders = implode(', ', array_fill(0, count($post_types), '%s'));
    $prepared_query = $wpdb->prepare(
        "SELECT ID, post_title, post_content, post_excerpt, post_type FROM {$wpdb->prefix}posts 
        WHERE post_type IN ($placeholders) AND post_status = 'publish' 
        ORDER BY ID ASC LIMIT %d OFFSET %d",
        array_merge($post_types, [$batch_size, $offset])
    );

    // DIAG - Diagnostics - Ver 1.9.6
    back_trace( 'NOTICE', '$prepared_query: ' . $prepared_query );

    // Get the published items
    $results = $wpdb->get_results($prepared_query);

    // If the $results = false, then there are no more items to process
    if ( empty($results) ) {
        // DIAG - Diagnostics - Ver 1.9.6
        back_trace( 'NOTICE', 'No more items to process' );
        update_option( 'chatbot_chatgpt_kn_action', 'phase 2' );
        // Schedule the next action
        wp_schedule_single_event( time() + 2, 'chatbot_kn_acquire_controller' );
        return;
    }

    // Process the results

    // Loop through query results
    foreach ($results as $result) {
        // DIAG - Diagnostic - Ver 1.6.3
        // foreach($result as $key => $value) {
        //     back_trace( 'NOTICE', 'Key: $key, Value: $value');
        // }        

        // Directly use the post content
        $postContent = $result->post_content;

        // Check if the post content is not empty
        if (!empty($postContent)) {
            // Ensure the post content is treated as UTF-8
            $postContentUtf8 = mb_convert_encoding($postContent, 'UTF-8', mb_detect_encoding($postContent));

            // Now call kn_acquire_just_the_words with the UTF-8 encoded post content and return $words
            $words = kn_acquire_just_the_words($postContentUtf8);

            // Now call kn_acquire_word_pairs with the UTF-8 encoded post content and return $word_pairs
            $word_pairs = kn_acquire_word_pairs($postContentUtf8);
        } else {
            // Handle the case where post content is empty
            // For example, log an error, skip this post, etc.
            // back_trace( 'NOTICE', 'Post ID ' . $result['ID'] . ' has empty content.');
        }
        
        // Construct the URL for the post
        $url = get_permalink($result->ID);
        // Construct the Title for the post
        $title = get_the_title($result->ID);
        // Store each url, title, word and score in the chatbot_chatgpt_knowledge_base table
        foreach ($words as $word => $score) {
            $wpdb->insert(
                $wpdb->prefix . 'chatbot_chatgpt_knowledge_base',
                array(
                    'url' => $url,
                    'title' => $title,
                    'word' => $word,
                    'score' => $score
                )
            );
        }

        // Store each url, title, word pair and score in the chatbot_chatgpt_knowledge_base table
        foreach ($word_pairs as $word => $score) {
            $wpdb->insert(
                $wpdb->prefix . 'chatbot_chatgpt_knowledge_base',
                array(
                    'url' => $url,
                    'title' => $title,
                    'word' => $word,
                    'score' => $score
                )
            );
        }

        // Increment the number of items analyzed by one
        $no_of_items_analyzed++;
        update_option('no_of_items_analyzed', $no_of_items_analyzed);
    
    }

    // chatbot_kn_schedule_batch_acquisition();
    update_option( 'chatbot_chatgpt_kn_action', 'phase 1' );

    // Schedule the next action
    wp_schedule_single_event( time() + 2, 'chatbot_kn_acquire_controller' );

}

// Acquire the content for each comment in the run
function chatbot_kn_run_comment_acquisition() {

    global $wpdb;
    global $topWords;
    global $topWordPairs;
    global $max_top_words;

    // DIAG - Diagnostics - Ver 1.9.6
    back_trace( 'NOTICE', 'chatbot_kn_run_comment_acquisition' );

    // Get the item count
    $offset = get_option('chatbot_chatgpt_kn_item_count', 1); // Default offset set to 1 if not specified
    $batch_size = get_option('chatbot_kn_items_per_batch', 50); // Fetching 100 items at a time
    $no_of_items_analyzed = get_option('no_of_items_analyzed', 1);

    // DIAG - Diagnostics - Ver 1.9.6
    back_trace( 'NOTICE', '$offset: ' . $offset );
    back_trace( 'NOTICE', '$batch_size:' . $batch_size );
    back_trace( 'NOTICE', '$no_of_items_analyzed: ' . $no_of_items_analyzed );

    // Set the next starting point
    update_option( 'chatbot_chatgpt_kn_item_count', $offset + $batch_size );

    // Get the setting for including comments
    $chatbot_chatgpt_kn_include_comments = get_option('chatbot_chatgpt_kn_include_comments', 'No');

    // Query WordPress database for comment content
    if ($chatbot_chatgpt_kn_include_comments === 'Yes') {
        // Prepare the SQL query for fetching approved comments
        $prepared_query = $wpdb->prepare(
            "SELECT comment_post_ID, comment_content FROM {$wpdb->prefix}comments WHERE comment_approved = %s", 
            '1'
        );
    
        // Execute the query and fetch results
        $results = $wpdb->get_results($prepared_query, ARRAY_A);
    
        // DIAG - Diagnostics - Ver 1.9.6
        back_trace('NOTICE', '$prepared_query: ' . $prepared_query);
    } else {
        // DIAG - Diagnostics - Ver 1.9.6
        back_trace('NOTICE', 'Exclude comments');
        $results = [];
    }

    // If the $results = false, then there are no more items to process
    if ( empty($results) ) {
        // DIAG - Diagnostics - Ver 1.9.6
        back_trace( 'NOTICE', 'No more items to process' );
        update_option( 'chatbot_chatgpt_kn_action', 'phase 4' );
        // Schedule the next action
        wp_schedule_single_event( time() + 2, 'chatbot_kn_acquire_controller' );
        return;
    }

    // Process the results

    // Loop through query results
    foreach ($results as $result) {
        // DIAG - Diagnostic - Ver 1.6.3
        // foreach($result as $key => $value) {
        //     back_trace( 'NOTICE', "Key: $key, Value: $value");
        // }        

        // Directly use the post content
        if (array_key_exists('post_content', $result)) {
            $postContent = $result['post_content'];
        } else {
            // Handle the case where the key does not exist
            $postContent = ""; // or some default value
        }

        // Check if the post content is not empty
        if (!empty($postContent)) {
            // Ensure the post content is treated as UTF-8
            $postContentUtf8 = mb_convert_encoding($postContent, 'UTF-8', mb_detect_encoding($postContent));

            // Now call kn_acquire_just_the_words with the UTF-8 encoded post content and return $words
            $words = kn_acquire_just_the_words($postContentUtf8);

            // Now call kn_acquire_word_pairs with the UTF-8 encoded post content and return $word_pairs
            $word_pairs = kn_acquire_word_pairs($postContentUtf8);
        } else {
            // Handle the case where post content is empty
            // For example, log an error, skip this post, etc.
            // back_trace( 'NOTICE', 'Post ID ' . $result['id'] . ' has empty content.');
        }
        
        // Construct the URL for the comments
        if (array_key_exists('id', $result)) {
            $url = get_permalink($result['id']);
        } else {
            // Handle the case where the key does not exist
            $url = ""; // or some default value
        }
        // Construct the Title for the post
        $title = 'Comment';
        // Store each url, title, word and score in the chatbot_chatgpt_knowledge_base table
        foreach ($words as $word => $score) {
            $wpdb->insert(
                $wpdb->prefix . 'chatbot_chatgpt_knowledge_base',
                array(
                    'url' => $url,
                    'title' => $title,
                    'word' => $word,
                    'score' => $score
                )
            );
        }

        // Store each url, title, word pairs and score in the chatbot_chatgpt_knowledge_base table
        foreach ($word_pairs as $word => $score) {
            $wpdb->insert(
                $wpdb->prefix . 'chatbot_chatgpt_knowledge_base',
                array(
                    'url' => $url,
                    'title' => $title,
                    'word' => $word,
                    'score' => $score
                )
            );
        }

        // Increment the number of items analyzed by one
        $no_of_items_analyzed++;
        update_option('no_of_items_analyzed', $no_of_items_analyzed);

    }

    // chatbot_kn_schedule_batch_acquisition();
    update_option( 'chatbot_chatgpt_kn_action', 'phase 3' );

    // Schedule the next action
    wp_schedule_single_event( time() + 2, 'chatbot_kn_acquire_controller' );

}