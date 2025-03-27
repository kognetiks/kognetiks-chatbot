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
    
    // DIAG - Diagnostics - Ver 2.2.7
    back_trace('NOTICE', 'Registering assistant search endpoint');
    
    register_rest_route('assistant/v1', '/search', [
        'methods'  => 'GET',
        'callback' => 'chatbot_assistant_search_handler',
        'permission_callback' => '__return_true',
        'args' => [
            'endpoint' => [
                'required' => true,
                'type' => 'string',
            ],
            'query' => [
                'required' => true,
                'type' => 'string',
            ],
            'include_excerpt' => [
                'required' => true,
                'type' => 'boolean',
            ],
            'page' => [
                'required' => true,
                'type' => 'integer',
                'default' => 1,
            ],
            'per_page' => [
                'required' => true,
                'type' => 'integer',
                'default' => 5,
            ],
        ],
    ]);
});

// Handle the assistant search request
function chatbot_assistant_search_handler($request) {

    // Log the incoming request more extensively
    back_trace('NOTICE', '====== SEARCH REQUEST RECEIVED ======');
    back_trace('NOTICE', 'Request parameters: ' . print_r($request->get_params(), true));
    back_trace('NOTICE', 'Request URL: ' . $request->get_route());
    back_trace('NOTICE', 'Request method: ' . $request->get_method());
    
    global $wpdb;

    try {
        // Get and validate parameters
        $endpoint = sanitize_text_field($request->get_param('endpoint'));
        $query = sanitize_text_field($request->get_param('query'));
        $include_excerpt = (bool) $request->get_param('include_excerpt');
        $page = (int) $request->get_param('page');
        $per_page = (int) $request->get_param('per_page');

        back_trace('NOTICE', 'Validated parameters:');
        back_trace('NOTICE', '- Endpoint: ' . $endpoint);
        back_trace('NOTICE', '- Query: ' . $query);
        back_trace('NOTICE', '- Include Excerpt: ' . ($include_excerpt ? 'true' : 'false'));
        back_trace('NOTICE', '- Page: ' . $page);
        back_trace('NOTICE', '- Per Page: ' . $per_page);

        // Use WP_Query to search posts or pages
        $args = [
            's' => $query,
            'post_type' => ['post', 'page'],
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => 'relevance',
            'order' => 'DESC',
        ];

        back_trace('NOTICE', 'WP_Query arguments: ' . print_r($args, true));

        $query = new WP_Query($args);

        if (is_wp_error($query)) {
            back_trace('ERROR', 'WP_Query error: ' . $query->get_error_message());
            return new WP_Error('query_error', $query->get_error_message(), ['status' => 500]);
        }

        $results = [];
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $result = [
                    'ID' => get_the_ID(),
                    'title' => get_the_title(),
                    'url' => get_permalink(),
                    'date' => get_the_date(),
                    'author' => get_the_author(),
                ];

                if ($include_excerpt) {
                    $result['excerpt'] = get_the_excerpt();
                }

                $results[] = $result;
            }
            wp_reset_postdata();
        }

        $response = [
            'success' => true,
            'total_posts' => $query->found_posts,
            'total_pages' => $query->max_num_pages,
            'current_page' => $page,
            'results' => $results,
        ];

        if (empty($results)) {
            $response['message'] = 'No results found.';
        }

        back_trace('NOTICE', 'Search completed successfully');
        back_trace('NOTICE', 'Results count: ' . count($results));
        return new WP_REST_Response($response, 200);

    } catch (Exception $e) {
        back_trace('ERROR', 'Exception caught: ' . $e->getMessage());
        back_trace('ERROR', 'Stack trace: ' . $e->getTraceAsString());
        return new WP_Error('search_error', $e->getMessage(), ['status' => 500]);
    }
}

