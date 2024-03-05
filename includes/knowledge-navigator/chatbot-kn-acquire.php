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

global $max_top_words, $chatbot_chatgpt_diagnostics, $frequencyData, $totalWordCount, $totalWordPairCount ;
$max_top_words = esc_attr(get_option('chatbot_chatgpt_kn_maximum_top_words', 100)); // Default to 100
$topWords = [];
$topWordPairs = [];
$frequencyData = [];
$totalWordCount = 0;
$totalWordPairCount = 0;

// Output Knowledge Navigator Data to log files for pages, posts and comments - Ver 1.6.3
function chatbot_chatgpt_kn_acquire(): void {

    global $wpdb;
    global $topWords;
    global $topWordPairs;
    global $max_top_words;

    // What to scan
    $chatbot_chatgpt_kn_include_posts = esc_attr(get_option('chatbot_chatgpt_kn_include_posts', 'Yes'));
    $chatbot_chatgpt_kn_include_pages = esc_attr(get_option('chatbot_chatgpt_kn_include_pages', 'Yes'));
    $chatbot_chatgpt_kn_include_products = esc_attr(get_option('chatbot_chatgpt_kn_include_products', 'Yes'));
    $chatbot_chatgpt_kn_include_comments = esc_attr(get_option('chatbot_chatgpt_kn_include_comments', 'Yes'));

    // back_trace( 'NOTICE', 'chatbot_chatgpt_kn_include_posts: ' . $chatbot_chatgpt_kn_include_posts);
    // back_trace( 'NOTICE', 'chatbot_chatgpt_kn_include_pages: ' . $chatbot_chatgpt_kn_include_pages);
    // back_trace( 'NOTICE', 'chatbot_chatgpt_kn_include_products: ' . $chatbot_chatgpt_kn_include_products);
    // back_trace( 'NOTICE', 'chatbot_chatgpt_kn_include_comments: ' . $chatbot_chatgpt_kn_include_comments);
    
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
    // $results_dir_path = plugin_dir_path(__FILE__) . '../../results/';
    // back_trace( 'NOTICE', 'CHATBOT_CHATGPT_PLUGIN_DIR_PATH: ' . CHATBOT_CHATGPT_PLUGIN_DIR_PATH);
    $results_dir_path = CHATBOT_CHATGPT_PLUGIN_DIR_PATH . 'results/';
    // back_trace( 'NOTICE', 'results_dir_path: ' . $results_dir_path);

    // Create directory if it doesn't exist
    if (!file_exists($results_dir_path)) {
        mkdir($results_dir_path, 0755, true);
    }

    // DIAG - Log directory path for debugging
    // back_trace( 'NOTICE', 'Directory path: ' . $results_dir_path);

    // Prepare log file for posts
    $log_file_posts = $results_dir_path . 'results-posts.log';
    // back_trace( 'NOTICE', 'Log file for posts: ' . $log_file_posts);

    // Delete post log file if it already exists
    if (file_exists($log_file_posts)) {
        unlink($log_file_posts);
    }

    // Prepare log file for pages
    $log_file_pages = $results_dir_path . 'results-pages.log';
    // back_trace( 'NOTICE', 'Log file for pages: ' . $log_file_pages);

    // Delete log file if it already exists
    if (file_exists($log_file_pages)) {
        unlink($log_file_pages);
    }

    // Prepare log file for comments
    $log_file_comments = $results_dir_path . 'results-comments.log';
    // back_trace( 'NOTICE', 'Log file for comments: ' . $log_file_comments);

    // Delete log file if it already exists
    if (file_exists($log_file_comments)) {
        unlink($log_file_comments);
    }

    // Query WordPress database for post content
    // Added support for Echo Knowledge Base (EKB) post_type - Ver 1.6.5
    // https://wordpress.org/support/topic/great-with-minor-code-edits/
    // https://wordpress.org/support/topic/add-pages-and-knowledge-base-to-search/
    // Added support for WooCommerce product post_type - Ver 1.7.5
    // https://github.com/woocommerce/woocommerce/wiki/Database-Description
    // https://woo.com/document/installed-taxonomies-post-types/

    // Decide what to include in the search - posts only, products only, or both
    if ( $chatbot_chatgpt_kn_include_posts === 'Yes' && $chatbot_chatgpt_kn_include_products === 'Yes') {
        // back_trace( 'NOTICE', 'Include both posts and products');
        $results = $wpdb->get_results(
            "SELECT ID, post_name, post_content FROM {$wpdb->prefix}posts WHERE (post_type='post' OR post_type='product' OR post_type='epkb_post_type_1') AND post_status='publish'", 
            ARRAY_A
        );
    } elseif ( $chatbot_chatgpt_kn_include_posts === 'Yes' && $chatbot_chatgpt_kn_include_products === 'No') {
        // back_trace( 'NOTICE', 'Include posts only');
        $results = $wpdb->get_results(
            "SELECT ID, post_name, post_content FROM {$wpdb->prefix}posts WHERE (post_type='post' OR post_type='epkb_post_type_1') AND post_status='publish'", 
            ARRAY_A
        );
    } elseif ( $chatbot_chatgpt_kn_include_posts === 'No' && $chatbot_chatgpt_kn_include_products === 'Yes') {
        // back_trace( 'NOTICE', 'Include products only');
        $results = $wpdb->get_results(
            "SELECT ID, post_name, post_content FROM {$wpdb->prefix}posts WHERE (post_type='product' OR post_type='epkb_post_type_1') AND post_status='publish'", 
            ARRAY_A
        );
    } else {
        // back_trace( 'NOTICE', 'Include neither posts nor products');
        $results = [];
    }

    // Loop through query results
    foreach ($results as $result) {
        // DIAG - Diagnostic - Ver 1.6.3
        // foreach($result as $key => $value) {
        //     back_trace( 'NOTICE', 'Key: $key, Value: $value');
        // }        

        // Directly use the post content
        $postContent = $result['post_content'];

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
    if ( $chatbot_chatgpt_kn_include_pages === 'Yes') {
        // back_trace( 'NOTICE', 'Include pages');
        $results = $wpdb->get_results(
            "SELECT ID, post_name, post_content FROM {$wpdb->prefix}posts WHERE post_type='page' AND post_status='publish'", 
            ARRAY_A
        );
    } else {
        // back_trace( 'NOTICE', 'Exclude pages');
        $results = [];
    }

    // Loop through query results
    foreach ($results as $result) {
        // DIAG - Diagnostic - Ver 1.6.3
        // foreach($result as $key => $value) {
        //     // back_trace( 'NOTICE', "Key: $key, Value: $value");
        // }

        // Directly use the post content
        $postContent = $result['post_content'];

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

        error_log($url . "\n", 3, $log_file_pages);
        error_log(print_r($word_pairs, true) . "\n", 3, $log_file_pages);
        
        // Increment the number of items analyzed by one
        $no_of_items_analyzed++;
        update_option('no_of_items_analyzed', $no_of_items_analyzed);
    }

    // Query WordPress database for comment content
    if ( $chatbot_chatgpt_kn_include_comments === 'Yes') {
        // back_trace( 'NOTICE', 'Include comments');
        $results = $wpdb->get_results(
            "SELECT comment_post_ID, comment_content FROM {$wpdb->prefix}comments WHERE comment_approved='1'", 
            ARRAY_A
        );
    } else {
        // back_trace( 'NOTICE', 'Exclude comments');
        $results = [];
    }

    // Loop through query results
    foreach ($results as $result) {
        // DIAG - Diagnostic - Ver 1.6.3
        // foreach($result as $key => $value) {
        //     // back_trace( 'NOTICE', "Key: $key, Value: $value");
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

        // Log the URL and the $words array
        error_log($url . "\n", 3, $log_file_comments);
        error_log(print_r($words, true) . "\n", 3, $log_file_comments);

        error_log($url . "\n", 3, $log_file_comments);
        error_log(print_r($word_pairs, true) . "\n", 3, $log_file_comments);
        
        // Increment the number of items analyzed by one
        $no_of_items_analyzed++;
        update_option('no_of_items_analyzed', $no_of_items_analyzed);
    }

    // Now computer the TF-IDF for the $topWords array
    foreach ($topWords as $word => $count) {
        $topWords[$word] = computeTFIDF($word);
    }

    // DIAG - Error log $max_top_words
    // back_trace( 'NOTICE', "$max_top_words: " . $max_top_words);

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
