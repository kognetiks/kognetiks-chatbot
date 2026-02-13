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
        'callback' => 'chatbot_assistant_search_handler',
        'permission_callback' => 'assistant_permission_callback',
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

// Secure the endpoint with a permission callback
function assistant_permission_callback( $request ) {

    // Retrieve the assistant ID from a custom header
    $assistant_id = $request->get_header('x-assistant-id');
    
    if ( empty( $assistant_id ) ) {
        // return new WP_Error( 'missing_assistant_id', __('Missing Assistant ID'), array( 'status' => 403 ) );
        return new WP_Error( 'unauthorized', __('Unauthorized', 'chatbot-chatgpt'), array( 'status' => 403 ) );
    }
    
    // Sanitize the assistant ID
    $assistant_id = sanitize_text_field( $assistant_id );
    
    global $wpdb;
    // Use your table name (including the prefix)
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_assistants';
    
    // Check if the assistant ID exists in the table

    $exists = $wpdb->get_var( 
        $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE assistant_id = %s", $assistant_id)
    );
    
    if ( ! $exists ) {
        // return new WP_Error( 'invalid_assistant_id', __('Invalid Assistant ID'), array( 'status' => 403 ) );
        return new WP_Error( 'unauthorized', __('Unauthorized', 'chatbot-chatgpt'), array( 'status' => 403 ) );
    }

    return true;
}

// Handle the assistant search request
function chatbot_assistant_search_handler($request) {
    
    global $wpdb;

    try {
        // Get and validate parameters
        $endpoint = sanitize_text_field($request->get_param('endpoint'));
        $query = sanitize_text_field($request->get_param('query'));
        $include_excerpt = (bool) $request->get_param('include_excerpt');
        $page = (int) $request->get_param('page');
        $per_page = (int) $request->get_param('per_page');
        $offset = ($page - 1) * $per_page;

        // Get the object of the search query
        $object = chatbot_chatgpt_get_object_of_search_prompt($query);
        
        // Prepare search terms
        $search_terms = chatbot_chatgpt_prepare_search_terms($object);

        // Get post types to search
        $post_types = chatbot_chatgpt_get_searchable_post_types();

        // Build search conditions
        $search_conditions = [];
        $placeholders = [];

        // Add search conditions for each term
        foreach ($search_terms as $term) {
            $like_term = '%' . $wpdb->esc_like($term) . '%';
            $search_conditions[] = "(post_title LIKE %s OR post_content LIKE %s)";
            $placeholders[] = $like_term;  // For post_title
            $placeholders[] = $like_term;  // For post_content
        }

        // If no search conditions, return empty result
        if (empty($search_conditions)) {
            return new WP_REST_Response([
                'success' => true,
                'total_posts' => 0,
                'total_pages' => 0,
                'current_page' => $page,
                'results' => [],
                'message' => 'No valid search terms.'
            ], 200);
        }

        // Escape and build IN clause for post types
        $in_clause = implode(',', array_map(fn($type) => "'" . esc_sql($type) . "'", $post_types));

        // Build the main query - Try first with AND
        $query = "
            SELECT ID, post_title, post_content, post_excerpt, post_author, post_date, guid
            FROM {$wpdb->posts} 
            WHERE post_type IN ($in_clause)
            AND post_status = 'publish'
            AND (". implode(' AND ', $search_conditions) .")
            ORDER BY post_date DESC
            LIMIT %d OFFSET %d
        ";

        // Add the LIMIT parameters
        $placeholders[] = $per_page;
        $placeholders[] = $offset;

        try {
            // Prepare and execute the query

            $prepared_query = $wpdb->prepare($query, ...$placeholders);
            $results = $wpdb->get_results($prepared_query);
            
            if ($wpdb->last_error) {
                throw new Exception('Database error: ' . $wpdb->last_error);
            }
        } catch (Exception $e) {
            // If AND search fails or returns no results, try OR search
            $query = "
                SELECT ID, post_title, post_content, post_excerpt, post_author, post_date, guid
                FROM {$wpdb->posts} 
                WHERE post_type IN ($in_clause)
                AND post_status = 'publish'
                AND (". implode(' OR ', $search_conditions) .")
                ORDER BY post_date DESC
                LIMIT %d OFFSET %d
            ";

            $prepared_query = $wpdb->prepare($query, ...$placeholders);
            $results = $wpdb->get_results($prepared_query);
            
            if ($wpdb->last_error) {
                throw new Exception('Database error: ' . $wpdb->last_error);
            }
        }

        $formatted_results = [];
        if ($results) {
            foreach ($results as $post) {
                $result = [
                    'ID' => $post->ID,
                    'title' => $post->post_title,
                    'url' => $post->guid,
                    'date' => $post->post_date,
                    'author' => get_the_author_meta('display_name', $post->post_author),
                ];

                if ($include_excerpt) {
                    $result['excerpt'] = strip_tags($post->post_content);
                }

                $formatted_results[] = $result;
            }
        }

        $response = [
            'success' => true,
            'total_posts' => count($formatted_results),
            'total_pages' => ceil(count($formatted_results) / $per_page),
            'current_page' => $page,
            'results' => $formatted_results,
        ];

        if (empty($formatted_results)) {
            $response['message'] = 'No results found.';
        }

        return new WP_REST_Response($response, 200);

    } catch (Exception $e) {
        return new WP_Error('search_error', $e->getMessage(), ['status' => 500]);
    }
}
