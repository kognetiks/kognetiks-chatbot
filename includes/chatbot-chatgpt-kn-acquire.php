<?php
/**
 * Chatbot ChatGPT for WordPress - Settings - Knowledge Navigator - Acquire
 *
 * This file contains the code for the Chatbot ChatGPT Knowledge Navigator.
 * 
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) )
die;

global $max_top_words, $chatbot_chatgpt_diagnostics, $frequencyData, $totalWordCount, $totalWordPairCount ;
$max_top_words = esc_attr(get_option('chatbot_chatgpt_kn_maximum_top_words', 100)); // Default to 100
$topWords = [];
$topWordPairs = [];
$frequencyData = [];
$totalWordCount = 0;
$totalWordPairCount = 0;

// Output Knowledge Navigator Data to log files for pages, posts and comments - Ver 1.6.3
function chatbot_chatgpt_kn_acquire() {

    global $wpdb;
    global $topWords;
    global $topWordPairs;
    global $max_top_words;

    $url = '';
    $words = [];
    $no_of_items_analyzed = 0;
    update_option('no_of_items_analyzed', $no_of_items_analyzed);

    // Reset the $no_of_items_analyzed to zero
    $no_of_items_analyzed = 0;

    // Initialize the $topWords array
    $topWords = [];
    $topWordPairs = [];

    // Reset the chatbot_chatgpt_knowledge_base table
    dbKNStore();
    
    // Generate directory path
    $results_dir_path = dirname(plugin_dir_path(__FILE__)) . '/results/';

    // Create directory if it doesn't exist
    if (!file_exists($results_dir_path)) {
        mkdir($results_dir_path, 0755, true);
    }

    // DIAG - Log directory path for debugging
    // error_log("Directory path: " . $results_dir_path);

    // Prepare log file for posts
    $log_file_posts = $results_dir_path . 'results-posts.log';

    // Delete post log file if it already exists
    if (file_exists($log_file_posts)) {
        unlink($log_file_posts);
    }

    // Prepare log file for pages
    $log_file_pages = $results_dir_path . 'results-pages.log';

    // Delete log file if it already exists
    if (file_exists($log_file_pages)) {
        unlink($log_file_pages);
    }

    // Prepare log file for comments
    $log_file_comments = $results_dir_path . 'results-comments.log';

    // Delete log file if it already exists
    if (file_exists($log_file_comments)) {
        unlink($log_file_comments);
    }

    // Query WordPress database for post content
    $results = $wpdb->get_results(
        "SELECT ID, post_name, post_content FROM {$wpdb->prefix}posts WHERE post_type='post' AND post_status='publish'", 
        ARRAY_A
    );

    // Loop through query results
    foreach ($results as $result) {
        // DIAG - Diagnostic - Ver 1.6.3
        // foreach($result as $key => $value) {
        //     error_log("Key: $key, Value: $value");
        // }        
        $output_str = '';
        $output_str .= json_encode($result['post_content']) . "\n";

        // Call kn_acquire_just_the_words with $output_str and return $words
        $words = kn_acquire_just_the_words($output_str);

        // Call kn_acquire_word_pairs with $output_str and return $word_pairs - Ver 1.6.5
        $word_pairs = kn_acquire_word_pairs($output_str);
        
        // Construct the URL for the post
        $url = get_permalink($result['ID']);
        // Construct the Title for the post
        $title = get_the_title($result['ID']);
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

        // Log the URL and the $words array
        error_log($url . "\n", 3, $log_file_posts);
        error_log(print_r($words, true) . "\n", 3, $log_file_posts);

        error_log($url . "\n", 3, $log_file_posts);
        error_log(print_r($word_pairs, true) . "\n", 3, $log_file_posts);

        // Increment the number of items analyzed by one
        $no_of_items_analyzed++;
        update_option('no_of_items_analyzed', $no_of_items_analyzed);
    }

    // Query WordPress database for page content
    $results = $wpdb->get_results(
        "SELECT ID, post_name, post_content FROM {$wpdb->prefix}posts WHERE post_type='page' AND post_status='publish'", 
        ARRAY_A
    );

    // Loop through query results
    foreach ($results as $result) {
        // DIAG - Diagnostic - Ver 1.6.3
        // foreach($result as $key => $value) {
        //     error_log("Key: $key, Value: $value");
        // }        
        $output_str = '';
        $output_str .= json_encode($result['post_content']) . "\n";

        // Call kn_acquire_just_the_words with $output_str and return $words
        $words = kn_acquire_just_the_words($output_str);

        // Call kn_acquire_word_pairs with $output_str and return $word_pairs - Ver 1.6.5
        $word_pairs = kn_acquire_word_pairs($output_str);

        // Construct the URL for the page
        $url = get_permalink($result['ID']);
        // Construct the Title for the post
        $title = get_the_title($result['ID']);
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

        // Log the URL and the $words array
        error_log($url . "\n", 3, $log_file_pages);
        error_log(print_r($words, true) . "\n", 3, $log_file_pages);
        // Increment the number of items analyzed by one
        $no_of_items_analyzed++;
        update_option('no_of_items_analyzed', $no_of_items_analyzed);
    }

    // Query WordPress database for comment content
    $results = $wpdb->get_results(
        "SELECT comment_post_ID, comment_content FROM {$wpdb->prefix}comments WHERE comment_approved='1'", 
        ARRAY_A
    );

    // Loop through query results
    foreach ($results as $result) {
        // DIAG - Diagnostic - Ver 1.6.3
        // foreach($result as $key => $value) {
        //     error_log("Key: $key, Value: $value");
        // }        
        $output_str = '';
        $output_str .= json_encode($result['comment_content']) . "\n";

        // Call kn_acquire_just_the_words with $output_str and return $words
        $words = kn_acquire_just_the_words($output_str);

        // Call kn_acquire_word_pairs with $output_str and return $word_pairs - Ver 1.6.5
        $word_pairs = kn_acquire_word_pairs($output_str);

        // Construct the URL for the comments
        $url = get_permalink($result['ID']);
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

        // Log the URL and the $words array
        error_log($url . "\n", 3, $log_file_comments);
        error_log(print_r($words, true) . "\n", 3, $log_file_comments);
        // Increment the number of items analyzed by one
        $no_of_items_analyzed++;
        update_option('no_of_items_analyzed', $no_of_items_analyzed);
    }

    // Now computer the TF-IDF for the $topWords array
    foreach ($topWords as $word => $count) {
        $topWords[$word] = computeTFIDF($word);
    }

    // DIAG - Error log $max_top_words
    // error_log("Max Top Words: " . $max_top_words);

    // slice off the top max_top_words
    $topWords = array_slice($topWords, 0, $max_top_words);

    // Store the top words for context
    store_top_words();

    // Output the results to a file
    output_results();
    
    return;

}

// Add the action hook
add_action( 'chatbot_chatgpt_kn_acquire', 'chatbot_chatgpt_kn_acquire' );

