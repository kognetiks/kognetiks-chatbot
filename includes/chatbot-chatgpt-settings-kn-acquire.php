<?php
/**
 * Chatbot ChatGPT for WordPress - Settings - Knowledge Navigator - Acquire
 *
 * This file contains the code for the Chatbot ChatGPT settings page.
 * It allows users to configure the API key and other parameters
 * required to access the ChatGPT API from their own account.
 *
 * @package chatbot-chatgpt
 */

 // TODO If this file is called directly, abort.
if ( ! defined( 'WPINC' ) )
die;

// TODO Dump Post Data to an log file
function chatbot_chatgpt_kn_acquire() {
    global $wpdb;

    // Generate directory path
    $results_dir_path = dirname(plugin_dir_path(__FILE__)) . '/results/';

    // Create directory if it doesn't exist
    if (!file_exists($results_dir_path)) {
        mkdir($results_dir_path, 0777, true);
    }

    // Log directory path for debugging
    error_log("Directory path: " . $results_dir_path);

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
        "SELECT post_content FROM {$wpdb->prefix}posts WHERE post_type='post' AND post_status='publish'", 
        ARRAY_A
    );

    // Loop through query results
    foreach ($results as $result) {
        $output_str = '';
        $output_str .= json_encode($result['post_content']) . "\n";
        error_log($output_str, 3, $log_file_posts);
    }

    // Query WordPress database for page content
    $results = $wpdb->get_results(
        "SELECT post_content FROM {$wpdb->prefix}posts WHERE post_type='page' AND post_status='publish'", 
        ARRAY_A
    );

    // Loop through query results
    foreach ($results as $result) {
        $output_str = '';
        $output_str .= json_encode($result['post_content']) . "\n";
        error_log($output_str, 3, $log_file_pages);
    }

    // Query WordPress database for comment content
    $results = $wpdb->get_results(
        "SELECT comment_content FROM {$wpdb->prefix}comments WHERE comment_approved='1'", 
        ARRAY_A
    );

    // Loop through query results
    foreach ($results as $result) {
        $output_str = '';
        $output_str .= json_encode($result['comment_content']) . "\n";
        error_log($output_str, 3, $log_file_comments);
    }
    
    return;
}

// Add the action hook 
add_action( 'chatbot_chatgpt_kn_acquire', 'chatbot_chatgpt_kn_acquire' );
