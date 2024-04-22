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

            // Initialize the knowledge acquisition process
            chatbot_kn_initalization();

            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'FINISH chatbot_chatgpt_kn_action: ' . $action  . ' ' . date('Y-m-d H:i:s') );
            break;

        case 'phase 1':

            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'START chatbot_chatgpt_kn_action: ' . $action  . ' ' . date('Y-m-d H:i:s') );

            chatbot_kn_run_phase_1();

            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'FINISH chatbot_chatgpt_kn_action: ' . $action  . ' ' . date('Y-m-d H:i:s') );
            break;

        case 'phase 2':

            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'START chatbot_chatgpt_kn_action: ' . $action  . ' ' . date('Y-m-d H:i:s') );

            // Reinitialize the batch acquisition for comments
            chatbot_kn_reinitialization();

            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'FINISH chatbot_chatgpt_kn_action: ' . $action  . ' ' . date('Y-m-d H:i:s') );
            break;

        case 'phase 3':

            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'START chatbot_chatgpt_kn_action: ' . $action  . ' ' . date('Y-m-d H:i:s') );

            chatbot_kn_run_phase_3();

            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'FINISH chatbot_chatgpt_kn_action: ' . $action  . ' ' . date('Y-m-d H:i:s') );
            break;

        case 'phase 4':

            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'START chatbot_chatgpt_kn_action: ' . $action  . ' ' . date('Y-m-d H:i:s') );

            // Determine the top words and word pairs
            chatbot_kn_run_phase_4();

            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'FINISH chatbot_chatgpt_kn_action: ' . $action  . ' ' . date('Y-m-d H:i:s') );
            break;

        case 'phase 5':

            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'START chatbot_chatgpt_kn_action: ' . $action  . ' ' . date('Y-m-d H:i:s') );

            // Reinitialize the batch acquisition for pages, posts, and products
            chatbot_kn_run_phase_5();

            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'FINISH chatbot_chatgpt_kn_action: ' . $action  . ' ' . date('Y-m-d H:i:s') );
            break;

        case 'phase 6':

            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'START chatbot_chatgpt_kn_action: ' . $action  . ' ' . date('Y-m-d H:i:s') );

            chatbot_kn_run_phase_6();

            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'FINISH chatbot_chatgpt_kn_action: ' . $action  . ' ' . date('Y-m-d H:i:s') );
            break;

        case 'phase TBD':

            // THERE PROBABLY NEEDS TO BE A REINITIALIZATION BEFORE THIS PHASE

            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'START chatbot_chatgpt_kn_action: ' . $action  . ' ' . date('Y-m-d H:i:s') );

            // Assign scores to the top 10% of the words in comments

            // TBD - Analyze the acquired content
            // chatbot_kn_run_phase_TBD();

            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'FINISH chatbot_chatgpt_kn_action: ' . $action  . ' ' . date('Y-m-d H:i:s') );
            break;

        case 'phase 7':

            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'START chatbot_chatgpt_kn_action: ' . $action  . ' ' . date('Y-m-d H:i:s') );

            chatbot_kn_output_the_results();

            // DIAG - Diagnostics - Ver 1.9.6
            back_trace( 'NOTICE', 'FINISH chatbot_chatgpt_kn_action: ' . $action  . ' ' . date('Y-m-d H:i:s') );
            break;
    
        case 'phase 8':

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

    // DIAG - Diagnostics - Ver 1.9.6
    // back_trace( 'NOTICE', 'chatbot_kn_phase_1_initalization' );

    // Since this is the first step, set the item count = 0
    update_option( 'chatbot_chatgpt_kn_item_count', 0 );

    // Define the batch size
    // FIXME - This should be set in the settings and default to 100
    update_option('chatbot_kn_items_per_batch', 100); // Fetching 100 items at a time

    // Reset the chatbot_chatgpt_knowledge_base table
    dbKNStore();

    // Reset the chatbot_chatgpt_knowledge_base_word_count table
    dbKNStoreWordCount();

    // Reset the chatbot_chatgpt_knowledge_base_tfidf table
    dbKNStoreTFIDF();

    // chatbot_kn_schedule_batch_acquisition();
    update_option( 'chatbot_chatgpt_kn_action', 'phase 1' );

    // Reset chatbot_chatgpt_kn_total_word_count to 0
    update_option('chatbot_chatgpt_kn_total_word_count', 0);

    // Reset chatbot_chatgpt_kn_document_count to 0
    update_option('chatbot_chatgpt_kn_document_count', 0);

    // Reset the number of items analyzed
    update_option('no_of_items_analyzed', 0);

    // Get teh number of posts, pages, products and comments
    chatbot_kn_count_documents();

    // Schedule the next action
    wp_schedule_single_event( time() + 2, 'chatbot_kn_acquire_controller' );

}

function chatbot_kn_reinitialization() {

    global $wpdb;

    // DIAG - Diagnostics - Ver 1.9.6
    // back_trace( 'NOTICE', 'chatbot_kn_phase_2_initialization' );

    // Initialize the $topWords array
    $topWords = [];
    $topWordPairs = [];

    update_option('chatbot_chatgpt_kn_item_count', 0);

    update_option('chatbot_chatgpt_kn_action', 'phase 3');

    // Schedule the next action
    wp_schedule_single_event( time() + 2, 'chatbot_kn_acquire_controller' );

}

// Count the number of posts, pages, and products
function chatbot_kn_count_documents() {
    
        global $wpdb;

        $document_count = 0;
    
        // Count the number of published pages
        $page_count = 0;
        if ( esc_attr(get_option('chatbot_chatgpt_kn_include_pages', 'No')) === 'Yes') {
            $page_count = $wpdb->get_var(
                "SELECT COUNT(ID) FROM {$wpdb->prefix}posts WHERE post_type = 'page' AND post_status = 'publish'"
            );
            $document_count += $page_count;
        }
    
        // Count the number of published posts
        $post_count = 0;
        if ( esc_attr(get_option('chatbot_chatgpt_kn_include_posts', 'No')) === 'Yes') {
            $post_count = $wpdb->get_var(
                "SELECT COUNT(ID) FROM {$wpdb->prefix}posts WHERE post_type = 'post' AND post_status = 'publish'"
            );
            $document_count += $post_count;
        }
    
        // Count the number of published products
        $product_count = 0;
        if ( esc_attr(get_option('chatbot_chatgpt_kn_include_products', 'No')) === 'Yes') {
            $product_count = $wpdb->get_var(
                "SELECT COUNT(ID) FROM {$wpdb->prefix}posts WHERE post_type = 'product' AND post_status = 'publish'"
            );
            $document_count += $product_count;
        }
    
        // Count the number of approved comments
        // FIXME - EXCLUDE COMMENTS FOR NOW
        update_option('chatbot_chatgpt_kn_include_comments', 'No');
        $comment_count = 0;
        if ( esc_attr(get_option('chatbot_chatgpt_kn_include_comments', 'No')) === 'Yes') {
            $comment_count = $wpdb->get_var(
                "SELECT COUNT(comment_post_ID) FROM {$wpdb->prefix}comments WHERE comment_approved = '1'"
            );
            $document_count += $comment_count;
        }
    
        // Update the total number of documents
        update_option('chatbot_chatgpt_kn_document_count', $document_count);

        // DIAG - Diagnostics - Ver 1.9.6
        back_trace( 'NOTICE', 'chatbot_kn_count_documents: ' . $document_count );

}

// Acquire the content for each page, post, or product in the run
function chatbot_kn_run_phase_1() {

    global $wpdb;

    // DIAG - Diagnostics - Ver 1.9.6
    // back_trace( 'NOTICE', 'chatbot_kn_run_phase_1' );

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
        $Content = $result->post_content;

        // Check if the post content is not empty
        if ( !empty($Content) ) {
            // Ensure the post content is treated as UTF-8
            $ContentUtf8 = mb_convert_encoding($Content, 'UTF-8', mb_detect_encoding($Content));

            // Now call kn_acquire_words with the UTF-8 encoded content
            kn_acquire_words( $ContentUtf8, 'add' );

        } else {
            // Handle the case where content is empty
            continue;
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
function chatbot_kn_run_phase_3() {

    global $wpdb;

    // DIAG - Diagnostics - Ver 1.9.6
    // back_trace( 'NOTICE', 'chatbot_kn_run_phase_3' );

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

        update_option( 'chatbot_chatgpt_kn_action', 'phase 4' );
        // Schedule the next action
        wp_schedule_single_event( time() + 2, 'chatbot_kn_acquire_controller' );

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
       
        // Check if the comment content is not empty
        if ( !empty($commentContent) ) {
            // Ensure the post content is treated as UTF-8
            $commentContentUtf8 = mb_convert_encoding($commentContent, 'UTF-8', mb_detect_encoding($commentContent));

            // Now call kn_acquire_words with the UTF-8 encoded content
            kn_acquire_words( $commentContentUtf8 , 'add' );

        } else {
            // Handle the case where content is empty
            continue;
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

// Compute the TF-IDF
function chatbot_kn_run_phase_4 () {

    global $wpdb;

    // Maximum number of top words
    $max_top_words = esc_attr(get_option('chatbot_chatgpt_kn_maximum_top_words', 100)); // Default to 100
    
    // SQL query to fetch top words based on their document count
    $results = $wpdb->get_results(
        "SELECT word, word_count, document_count FROM {$wpdb->prefix}chatbot_chatgpt_knowledge_base_word_count 
        ORDER BY document_count DESC LIMIT $max_top_words"
    );
    
    // Total number of documents in the corpus
    $totalDocumentCount = get_option('chatbot_chatgpt_kn_document_count', 0); // Total documents in the corpus
    
    // Total number of words in the corpus
    $totalWordCount = get_option('chatbot_chatgpt_kn_total_word_count', 0); // Total words across documents

    foreach ($results as $result) {

        $word = $result->word;
    
        $wordCount = $result->word_count;  // Using 'count' directly from the query
    
        $documentCount = $result->document_count;  // Using 'document_count' directly from the query
    
        $wordCount = $result->word_count;
    
        // Calculate the Term Frequency (TF) for the $word
        // This should be the total occurrences of the word divided by the total number of words, if available
        $tf = $wordCount / $totalWordCount;
    
        // Calculate Inverse Document Frequency (IDF)
        $idf = log($totalDocumentCount / $documentCount);
    
        // Calculate the TF-IDF
        $tfidf = $tf * $idf;
    
        // Store the TF-IDF in the chatbot_chatgpt_knowledge_base_tfidf table
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

// Phase 5
function chatbot_kn_run_phase_5 () {

    // DIAG - Diagnostics - Ver 1.9.6
    // back_trace( 'NOTICE', 'chatbot_kn_run_phase_5' );

    // REINITIALIZE THE BATCH ACQUISITION

    // Since this is the first step, set the item count = 0
    update_option( 'chatbot_chatgpt_kn_item_count', 0 );

    // Define the batch size
    // FIXME - This should be set in the settings and default to 100
    update_option('chatbot_kn_items_per_batch', 100); // Fetching 100 items at a time

    // chatbot_kn_schedule_batch_acquisition();
    update_option( 'chatbot_chatgpt_kn_action', 'phase 6' );

    // Schedule the next action
    wp_schedule_single_event( time() + 2, 'chatbot_kn_acquire_controller' );

}

// Phase 6
function chatbot_kn_run_phase_6 () {

    global $wpdb;

    // DIAG - Diagnostics - Ver 1.9.6
    // back_trace( 'NOTICE', 'chatbot_kn_run_phase_5' );

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
        update_option( 'chatbot_chatgpt_kn_action', 'phase 7' );

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
        $Content = $result->post_content;

        // Check if the post content is not empty
        if ( !empty($Content) ) {
            // Ensure the post content is treated as UTF-8
            $ContentUtf8 = mb_convert_encoding($Content, 'UTF-8', mb_detect_encoding($Content));

            // Now call kn_acquire_words with the UTF-8 encoded content
            $words = kn_acquire_words( $ContentUtf8 , 'skip');

            $wordScores = array();

            // Store each url, title, word and score in the chatbot_chatgpt_knowledge_base table if the word is found in the TF-IDF table
            foreach ( $words as $word ) {
                $tfidf = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT score FROM {$wpdb->prefix}chatbot_chatgpt_knowledge_base_tfidf WHERE word = %s",
                        $word
                    )
                );
            
                $wordScores[$word] = $tfidf;
            }

            // Sort the $words array by $tfidf in descending order
            rsort($wordScores);
            
            // Count the number of words in the $words array
            $word_count = count($wordScores);

            // Get the tuning percentage from the options table - Stored as an integer between 0 and 100, so divide by 100
            $tuning_percentage = esc_attr(get_option('chatbot_chatgpt_kn_tuning_percentage', 25)) / 100;

            // Trim the $words array to the top 10% of the words
            // FIXME - This should be set in the settings and default to 10%
            $top_words = array_slice( $wordScores, 0, ceil($word_count * $tuning_percentage ), true);

            // Store the top words in the chatbot_chatgpt_knowledge_base table
            foreach ($top_words as $word => $score) {

                // Construct the URL for the post
                $url = get_permalink($result->ID);
                
                // Construct the Title for the post
                $title = get_the_title($result->ID);

                // Store each url, title, word and score in the chatbot_chatgpt_knowledge_base table
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
        } else {
            // Handle the case where content is empty
            continue;
        }

        // Increment the number of items analyzed by one
        // $no_of_items_analyzed++;
    
    }

    // Update the number of items analyzed
    // update_option('no_of_items_analyzed', $no_of_items_analyzed);

    // chatbot_kn_schedule_batch_acquisition();
    update_option( 'chatbot_chatgpt_kn_action', 'phase 6' );

    // Schedule the next action
    wp_schedule_single_event( time() + 2, 'chatbot_kn_acquire_controller' );

    // Unset large variables to free memory
    unset($results);

}

// Output the results
function chatbot_kn_output_the_results() {

    global $wpdb;


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
    update_option( 'chatbot_chatgpt_kn_action', 'phase 8' );

    // Schedule the next action
    wp_schedule_single_event( time() + 2, 'chatbot_kn_acquire_controller' );

}

// Wrap up the knowledge acquisition process
function chatbot_kn_wrap_up() {

    // DIAG - Diagnostics - Ver 1.9.6
    // back_trace( 'NOTICE', 'chatbot_kn_wrap_up' );

    // FIXME - Drop the chatbot_chatgpt_knowledge_base_word_count table
    // DIAG - Diagnostics - Ver 1.9.6
    back_trace ( 'NOTICE', 'Dropping chatbot_chatgpt_knowledge_base_word_count table' );
    dbKNClean();

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