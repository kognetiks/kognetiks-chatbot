<?php
/**
 * Kognetiks Chatbot - Chatbot Assisted Search - Ver 2.2.7
 *
 * This file contains the code for assisted search from OpenAI Assistants
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Handle the assistant search request
function assistant_search_handler($request) {
    
    back_trace('NOTICE', 'Starting assistant_search_handler');
    
    global $wpdb;

    // Validate required parameters
    if (!$request->get_param('q') || !$request->get_param('assistant_id')) {
        back_trace('ERROR', 'Missing required parameters');
        return new WP_Error('missing_params', 'Missing required parameters', array('status' => 400));
    }

    $search_query = sanitize_text_field($request->get_param('q'));
    $assistant_id = sanitize_text_field($request->get_param('assistant_id'));

    back_trace('NOTICE', 'Assistant ID: ' . $assistant_id);
    back_trace('NOTICE', 'Search Query: ' . $search_query);

    // Validate the assistant_id
    $assistant_exists = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}chatbot_chatgpt_assistants WHERE assistant_id = %s",
            $assistant_id
        )
    );

    if (!$assistant_exists) {
        back_trace('ERROR', 'Invalid Assistant ID: ' . $assistant_id);
        return new WP_Error('invalid_assistant', 'Invalid Assistant ID', array('status' => 403));
    }

    // Use WP_Query to search posts or pages
    $query = new WP_Query([
        's' => $search_query,
        'post_type' => ['post', 'page'],
        'posts_per_page' => 5,
    ]);

    $results = [];
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $results[] = [
                'ID'      => get_the_ID(),
                'title'   => get_the_title(),
                'url'     => get_permalink(),
                'excerpt' => get_the_excerpt(),
            ];
        }
        wp_reset_postdata();
    }

    if (empty($results)) {
        return new WP_Error('no_results', 'No results found.', array('status' => 200));
    }

    back_trace('NOTICE', 'Results: ' . print_r($results, true));
    return ['results' => $results];
}

