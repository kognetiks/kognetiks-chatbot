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

global $max_top_words;

global $frequencyData;
global $totalWordCount;
global $totalWordPairCount;

$max_top_words = esc_attr(get_option('chatbot_chatgpt_kn_maximum_top_words', 100)); // Default to 100

$topWords = [];
$topWordPairs = [];
$frequencyData = [];
$totalWordCount = 0;
$totalWordPairCount = 0;

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
// The knowledge acquisition is run in multiple steps:
// 1. Initialize - Initialize the batch acquisition for posts, pages, and products
// 2. Run - Acquires the content for each post, page, or product in the batch
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
            back_trace( 'NOTICE', 'START chatbot_chatgpt_kn_action: ' . $action  . ' ' . date('Y-m-d H:i:s') );

            chatbot_kn_initalization();

            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'FINISH chatbot_chatgpt_kn_action: ' . $action  . ' ' . date('Y-m-d H:i:s') );
            break;

        case 'phase 1':

            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'START chatbot_chatgpt_kn_action: ' . $action  . ' ' . date('Y-m-d H:i:s') );

            chatbot_kn_run_post_acquisition();

            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'FINISH chatbot_chatgpt_kn_action: ' . $action  . ' ' . date('Y-m-d H:i:s') );
            break;

        case 'phase 2':

            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'START chatbot_chatgpt_kn_action: ' . $action  . ' ' . date('Y-m-d H:i:s') );

            chatbot_kn_reinitialization();

            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'FINISH chatbot_chatgpt_kn_action: ' . $action  . ' ' . date('Y-m-d H:i:s') );
            break;

        case 'phase 3':

            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'START chatbot_chatgpt_kn_action: ' . $action  . ' ' . date('Y-m-d H:i:s') );

            chatbot_kn_run_comment_acquisition();

            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'FINISH chatbot_chatgpt_kn_action: ' . $action  . ' ' . date('Y-m-d H:i:s') );
            break;

        case 'phase 4':

            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'START chatbot_chatgpt_kn_action: ' . $action  . ' ' . date('Y-m-d H:i:s') );

            // Determine the top words and word pairs
            chatbot_kn_determine_top_words();

            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'FINISH chatbot_chatgpt_kn_action: ' . $action  . ' ' . date('Y-m-d H:i:s') );
            break;

        case 'phase 5':

            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'START chatbot_chatgpt_kn_action: ' . $action  . ' ' . date('Y-m-d H:i:s') );

            chatbot_kn_output_the_results();

            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'FINISH chatbot_chatgpt_kn_action: ' . $action  . ' ' . date('Y-m-d H:i:s') );
            break;
    
        case 'phase 6':

            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'START chatbot_chatgpt_kn_action: ' . $action  . ' ' . date('Y-m-d H:i:s') );

            // Wrap up the knowledge acquisition process
            chatbot_kn_wrap_up();
           
            update_option( 'chatbot_chatgpt_kn_action', 'completed' );

            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'FINISH chatbot_chatgpt_kn_action: ' . $action  . ' ' . date('Y-m-d H:i:s') );
            break;
            
        case 'completed':

            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'chatbot_chatgpt_kn_action: ' . $action );

            return;

        case 'cancel':

            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'START chatbot_chatgpt_kn_action: ' . $action  . ' ' . date('Y-m-d H:i:s') );

            // chatbot_kn_cancel_batch_acquisition();

            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'FINISH chatbot_chatgpt_kn_action: ' . $action  . ' ' . date('Y-m-d H:i:s') );
            break;

        default:

            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'ERROR', 'chatbot_chatgpt_kn_action: ' . $action );
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
    // back_trace( 'NOTICE', 'chatbot_kn_phase_1_initalization' );

    // Since this is the first step, set the item count = 0
    update_option( 'chatbot_chatgpt_kn_item_count', 0 );

    // Define the batch size
    // FIXME - This should be set in the settings and default to 100
    update_option('chatbot_kn_items_per_batch', 100); // Fetching 100 items at a time

    // Initialize the $topWords array
    $topWords = [];
    $topWordPairs = [];

    // Reset the chatbot_chatgpt_knowledge_base table
    dbKNStore();

    // chatbot_kn_schedule_batch_acquisition();
    update_option( 'chatbot_chatgpt_kn_action', 'phase 1' );

    // Reset the number of items analyzed
    update_option('no_of_items_analyzed', 0);

    // Schedule the next action
    wp_schedule_single_event( time() + 2, 'chatbot_kn_acquire_controller' );

}

function chatbot_kn_reinitialization() {

    global $wpdb;
    global $topWords;
    global $topWordPairs;
    global $max_top_words;

    // DIAG - Diagnostics - Ver 1.9.6
    // back_trace( 'NOTICE', 'chatbot_kn_phase_2_initialization' );

    // Initialize the $topWords array
    $topWords = [];
    $topWordPairs = [];

    // Reset the chatbot_chatgpt_knowledge_base_tfidf table
    dbKNStoreTFIDF();

    update_option('chatbot_chatgpt_kn_item_count', 0);

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
    // back_trace( 'NOTICE', 'chatbot_kn_run_post_acquisition' );

    // Get the item count
    $offset = get_option('chatbot_chatgpt_kn_item_count', 0); // Default offset set to 0 if not specified
    // FIXME - This should be set in the settings and default to 100
    $batch_size = get_option('chatbot_kn_items_per_batch', 100); // Fetching 100 items at a time
    $no_of_items_analyzed = get_option('no_of_items_analyzed', 0);

    // DIAG - Diagnostics - Ver 1.9.6
    // back_trace( 'NOTICE', '$offset: ' . $offset );
    // back_trace( 'NOTICE', '$batch_size: ' . $batch_size );
    // back_trace( 'NOTICE', '$no_of_items_analyzed: ' . $no_of_items_analyzed );

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
    // back_trace( 'NOTICE', '$prepared_query: ' . $prepared_query );

    // Get the published items
    $results = $wpdb->get_results($prepared_query);

    // If the $results = false, then there are no more items to process
    if ( empty($results) ) {
        // DIAG - Diagnostics - Ver 1.9.6
        // back_trace( 'NOTICE', 'No more items to process' );
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
            // DIAG - Diagnostics - Ver 1.9.6
            // back_trace( 'NOTICE', 'Post has empty content.');
            continue;
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
                    'score' => $score,
                    'cardinality' => 1
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
                    'score' => $score,
                    'cardinality' => 2
                )
            );
        }

        // Increment the number of items analyzed by one
        $no_of_items_analyzed++;
    
    }

    // Update the number of items analyzed
    update_option('no_of_items_analyzed', $no_of_items_analyzed);

    // chatbot_kn_schedule_batch_acquisition();
    update_option( 'chatbot_chatgpt_kn_action', 'phase 1' );

    // Schedule the next action
    wp_schedule_single_event( time() + 2, 'chatbot_kn_acquire_controller' );

    // Unset large variables to free memory
    unset($results);

}

// Acquire the content for each comment in the run
function chatbot_kn_run_comment_acquisition() {

    global $wpdb;
    global $topWords;
    global $topWordPairs;
    global $max_top_words;

    // DIAG - Diagnostics - Ver 1.9.6
    // back_trace( 'NOTICE', 'chatbot_kn_run_comment_acquisition' );

    // Get the item count
    $offset = get_option('chatbot_chatgpt_kn_item_count', 0); // Default offset set to 0 if not specified
    // FIXME - This should be set in the settings and default to 100
    $batch_size = get_option('chatbot_kn_items_per_batch', 100); // Fetching 100 items at a time
    $no_of_items_analyzed = get_option('no_of_items_analyzed', 0);

    // DIAG - Diagnostics - Ver 1.9.6
    // back_trace( 'NOTICE', '$offset: ' . $offset );
    // back_trace( 'NOTICE', '$batch_size: ' . $batch_size );
    // back_trace( 'NOTICE', '$no_of_items_analyzed: ' . $no_of_items_analyzed );

    // Set the next starting point
    update_option( 'chatbot_chatgpt_kn_item_count', $offset + $batch_size );

    // Get the setting for including comments
    $chatbot_chatgpt_kn_include_comments = get_option('chatbot_chatgpt_kn_include_comments', 'No');

    // Query WordPress database for comment content
    if ($chatbot_chatgpt_kn_include_comments === 'Yes') {

        // Prepare the SQL query for fetching approved comments
        $prepared_query = $wpdb->prepare(
            "SELECT comment_ID, comment_post_ID, comment_content FROM {$wpdb->prefix}comments WHERE comment_approved = '1' 
            ORDER BY comment_ID ASC LIMIT %d OFFSET %d",
            array_merge([$batch_size, $offset])
        );
    
        // Execute the query and fetch results
        $results = $wpdb->get_results($prepared_query, ARRAY_A);
    
        // DIAG - Diagnostics - Ver 1.9.6
        // back_trace('NOTICE', '$prepared_query: ' . $prepared_query);

    } else {

        // DIAG - Diagnostics - Ver 1.9.6
        // back_trace('NOTICE', 'Exclude comments');

        unset($results);

        return;
    }

    // If the $results = false, then there are no more items to process
    if ( empty($results) ) {
        // DIAG - Diagnostics - Ver 1.9.6
        // back_trace( 'NOTICE', 'No more items to process' );
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
        if (array_key_exists('comment_content', $result)) {
            $commentContent = $result['comment_content'];
        } else {
            // Handle the case where the key does not exist
            $commentContent = "";
            // DIAG - Diagnostics - Ver 1.9.6
            // back_trace( 'NOTICE', 'Comment has empty content.');
            continue;
        }

        // Check if the post content is not empty
        if (!empty($commentContent)) {
            // Ensure the post content is treated as UTF-8
            $commentContentUtf8 = mb_convert_encoding($commentContent, 'UTF-8', mb_detect_encoding($commentContent));

            // Now call kn_acquire_just_the_words with the UTF-8 encoded comment content and return $words
            $words = kn_acquire_just_the_words($commentContentUtf8);

            // Now call kn_acquire_word_pairs with the UTF-8 encoded comment content and return $word_pairs
            $word_pairs = kn_acquire_word_pairs($commentContentUtf8);
        } else {
            // Handle the case where post content is empty
            // DIAG - Diagnostics - Ver 1.9.6
            // back_trace( 'NOTICE', 'Comment has empty content.');
            continue;
        }
        
        // Construct the URL for the comments
        if (array_key_exists('comment_post_ID', $result)) {
            $url = get_permalink($result['comment_post_ID']);
        } else {
            // Handle the case where the key does not exist
            $url = "";
            // DIAG - Diagnostics - Ver 1.9.6
            // back_trace( 'NOTICE', 'Comment has empty content.');
            continue;
        }

        // Construct the Title for the comments
        $title = 'Comment';

        // Store each url, title, word and score in the chatbot_chatgpt_knowledge_base table
        foreach ($words as $word => $score) {
            $wpdb->insert(
                $wpdb->prefix . 'chatbot_chatgpt_knowledge_base',
                array(
                    'url' => $url,
                    'title' => $title,
                    'word' => $word,
                    'score' => $score,
                    'cardinality' => 1
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
                    'score' => $score,
                    'cardinality' => 2
                )
            );
        }

        // Increment the number of items analyzed by one
        $no_of_items_analyzed++;

    }

    // Update the number of items analyzed
    update_option('no_of_items_analyzed', $no_of_items_analyzed);

    // chatbot_kn_schedule_batch_acquisition();
    update_option( 'chatbot_chatgpt_kn_action', 'phase 3' );

    // Schedule the next action
    wp_schedule_single_event( time() + 2, 'chatbot_kn_acquire_controller' );

    // Unset large variables to free memory
    unset($results);

}

// Determine the top words
function chatbot_kn_determine_top_words() {

    global $wpdb;
    global $topWords;
    global $topWordPairs;
    global $max_top_words;
    global $totalWordCount;

    // Get the maximum number of top words
    $max_top_words = esc_attr(get_option('chatbot_chatgpt_kn_maximum_top_words', 100)); // Default to 100

    // Retrieve the list of words where the cardinality = 1 and count the number of occurrences
    // $results = $wpdb->get_results(
    //     "SELECT word, COUNT(DISTINCT word) AS total_count FROM {$wpdb->prefix}chatbot_chatgpt_knowledge_base 
    //     WHERE cardinality = 1 GROUP BY word ORDER BY total_count DESC LIMIT $max_top_words"
    // );

    // Retrieve the list of words where the cardinality = 1 or 2 and count the number of occurrences
    $results = $wpdb->get_results(
        "SELECT word, COUNT(DISTINCT word) AS total_count FROM {$wpdb->prefix}chatbot_chatgpt_knowledge_base 
        GROUP BY word ORDER BY total_count DESC LIMIT $max_top_words"
    );

    // Store the top words in the $topWords array
    foreach ($results as $result) {
        $topWords[$result->word] = $result->total_count;
    }

    // Count the total number of words
    $totalWordCount = array_sum($topWords);
   
    // Now computer the TF-IDF for the $topWords array
    foreach ($topWords as $word => $count) {
        $topWords[$word] = computeTFIDF($word);
    }

    // Slice the $topWords array to the maximum number of top words
    $topWords = array_slice($topWords, 0, $max_top_words, true);

    // Store the top words in the chatbot_chatgpt_knowledge_base_tfidf table
    foreach ($topWords as $word => $tfidf) {
        $wpdb->insert(
            $wpdb->prefix . 'chatbot_chatgpt_knowledge_base_tfidf',
            array(
                'word' => $word,
                'score' => $tfidf
            )
        );
    }

    // Unset large variables to free memory
    unset($results);

    // chatbot_kn_schedule_batch_acquisition();
    update_option( 'chatbot_chatgpt_kn_action', 'phase 5' );

    // Schedule the next action
    wp_schedule_single_event( time() + 2, 'chatbot_kn_acquire_controller' );

    // Unset large variables to free memory
    unset($results);
    
}

// Output the results
function chatbot_kn_output_the_results() {

    global $wpdb;
    global $topWords;
    global $topWordPairs;
    global $max_top_words;

    // Generate directory path
    $results_dir_path = CHATBOT_CHATGPT_PLUGIN_DIR_PATH . 'results/';
    // back_trace( 'NOTICE', 'results_dir_path: ' . $results_dir_path);

    // Create directory if it doesn't exist
    if (!file_exists($results_dir_path)) {
        mkdir($results_dir_path, 0755, true);
    }

    // DIAG - Log directory path for debugging
    // back_trace( 'NOTICE', 'Directory path: ' . $results_dir_path);

    // Remove legacy files
    if (file_exists($results_dir_path . 'results-comments.log')) {
        unlink($results_dir_path . 'results-comments.log');
    }
    if (file_exists($results_dir_path . 'results-pages.log')) {
        unlink($results_dir_path . 'results-pages.log');
    }
    if (file_exists($results_dir_path . 'results-posts.log')) {
        unlink($results_dir_path . 'results-posts.log');
    }

    // Prepare CSV file for output
    $results_csv_file = $results_dir_path . 'results.csv';
    // back_trace( 'NOTICE', 'CSV file for output: ' . $results_csv_file);

    // Delete CSV file if it already exists
    if (file_exists($results_csv_file)) {
        unlink($results_csv_file);
    }

    // Prepare JSON file for output
    $results_json_file = $results_dir_path . 'results.json';
    // back_trace( 'NOTICE', 'JSON file: ' . $results_json_file);

    // Delete JSON file if it already exists
    if (file_exists($results_json_file)) {
        unlink($results_json_file);
    }

    // Retrieve the list of words and the score for each word orded by score descending in the TF-IDF table
    $results = $wpdb->get_results(
        "SELECT word, score FROM {$wpdb->prefix}chatbot_chatgpt_knowledge_base_tfidf ORDER BY score DESC"
    );

    // Write CSV for pages, posts, and products
    try {
        $f = new SplFileObject($results_csv_file, 'w');
        $f->fputcsv(['Word', 'TF-IDF']);
        foreach ($results as $result) {
            $f->fputcsv([$result->word, $result->score]);
        }
    } catch (RuntimeException $e) {
        // back_trace( 'ERROR', 'Failed to open CSV file for writing: ' . $e->getMessage());
    }

    // Write JSON for pages, posts, and products
    try {
        if (file_put_contents($results_json_file, json_encode($results)) === false) {
            throw new Exception("Failed to write to JSON file.");
        }
    } catch (Exception $e) {
        // back_trace( 'ERROR', $e->getMessage());
    }

    // Close the files
    $f = null;

    // Unset large variables to free memory
    unset($results);

    // // Now write the .log files
    // $tfidf_results = $results_dir_path . 'tfidf_results.csv';
    // // back_trace( 'NOTICE', 'Log file: ' . $tfidf_results);

    // // Delete log file if it already exists
    // if (file_exists($tfidf_results)) {
    //     unlink($tfidf_results);
    // }

    // // Retrieve the words and the scores for each URL in the knowledge base table
    // $results = $wpdb->get_results(
    //     "SELECT id, url, title, word, score FROM {$wpdb->prefix}chatbot_chatgpt_knowledge_base ORDER BY title, url, score DESC"
    // );

    // // Write the log file
    // try {
    //     $f = new SplFileObject($tfidf_results, 'w');
    //     $f->fputcsv(['ID', 'URL', 'Title', 'Word', 'Score']);
    //     foreach ($results as $result) {
    //         $f->fputcsv([$result->id, $result->url, $result->title, $result->word, $result->score]);
    //     }
    // } catch (RuntimeException $e) {
    //     // back_trace( 'ERROR', 'Failed to open log file for writing: ' . $e->getMessage());
    // }

    // // Close the file
    // $f = null;

    // // Unset large variables to free memory
    // unset($results);

    // chatbot_kn_schedule_batch_acquisition();
    update_option( 'chatbot_chatgpt_kn_action', 'phase 6' );

    // Schedule the next action
    wp_schedule_single_event( time() + 2, 'chatbot_kn_acquire_controller' );

}

// Wrap up the knowledge acquisition process
function chatbot_kn_wrap_up() {

    // DIAG - Diagnostics - Ver 1.9.6
    // back_trace( 'NOTICE', 'chatbot_kn_wrap_up' );

    // Save the results message value into the option
    $kn_results = 'Knowledge Navigation completed! Check the Analysis to download or results.csv file in the plugin directory.';
    update_option('chatbot_chatgpt_kn_results', $kn_results);

    // Notify outcome for up to 3 minutes
    set_transient('chatbot_chatgpt_kn_results', $kn_results);

    // Get the current date and time.
    $date_time_completed = date("Y-m-d H:i:s");

    // Concatenate the status message with the date and time.
    $status_message = 'Completed on ' . $date_time_completed;

    // Update the option with the new status message.
    update_option('chatbot_chatgpt_kn_status', $status_message);

}