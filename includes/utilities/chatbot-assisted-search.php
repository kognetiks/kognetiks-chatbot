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

// Add the REST API route for the assistant search
add_action('rest_api_init', function () {

    register_rest_route('assistant/v1', '/search', [
        'methods'  => 'GET',
        'callback' => 'assistant_search_handler',
    ]);

});

// Handle the assistant search request
function assistant_search_handler($request) {
    global $wpdb;

    $search_query = sanitize_text_field($request->get_param('q'));
    $assistant_id = sanitize_text_field($request->get_param('assistant_id'));

    // Validate the assistant_id
    $assistant_exists = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}chatbot_chatgpt_assistants WHERE id = %s",
            $assistant_id
        )
    );

    if (!$assistant_exists) {
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
                'ID'    => get_the_ID(),
                'title' => get_the_title(),
                'url'   => get_permalink(),
                'excerpt' => get_the_excerpt(),
            ];
        }
        wp_reset_postdata();
    }

    // Return JSON response
    return [
        'results' => $results,
    ];
}

