<?php
/**
 * Kognetiks Chatbot for WordPress - Search - Ver 2.2.4
 *
 * This file contains the code for implementing pre-processor before engaging with an LLM.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Handle the assistant search request
function chatbot_chatgpt_content_search($search_prompt) {
    back_trace('NOTICE', 'chatbot_chatgpt_content_search');
    back_trace('NOTICE', '====== SEARCH REQUEST RECEIVED ======');
    back_trace('NOTICE', 'Request parameters: ' . $search_prompt);

    global $wpdb;

    // Settings
    $include_excerpt = true;
    $page = 1;
    $per_page = 5;
    $offset = ($page - 1) * $per_page;

    back_trace('NOTICE', '- Include Excerpt: ' . ($include_excerpt ? 'true' : 'false'));
    back_trace('NOTICE', '- Page: ' . $page);
    back_trace('NOTICE', '- Per Page: ' . $per_page);

    // Get all registered public post types
    $registered_types = get_post_types(['public' => true], 'objects');
    $post_types = [];

    foreach ($registered_types as $type) {
        $plural_type = $type->name === 'reference' ? 'references' : $type->name . 's';
        $option_name = 'chatbot_chatgpt_kn_include_' . $plural_type;
        if (esc_attr(get_option($option_name, 'No')) === 'Yes') {
            $post_types[] = $type->name;
        }
    }

    // Add any extra post types from DB
    $db_post_types = $wpdb->get_col("SELECT DISTINCT post_type FROM {$wpdb->posts}");
    foreach ($db_post_types as $type) {
        if (!in_array($type, $post_types)) {
            $plural_type = $type === 'reference' ? 'references' : $type . 's';
            $option_name = 'chatbot_chatgpt_kn_include_' . $plural_type;
            if (esc_attr(get_option($option_name, 'No')) === 'Yes') {
                $post_types[] = $type;
            }
        }
    }

    // Escape and build IN clause
    $in_clause = implode(',', array_map(fn($type) => "'" . esc_sql($type) . "'", $post_types));
    $search_term = '%' . $wpdb->esc_like($search_prompt) . '%';

    // Use manually interpolated query for dev debug ONLY
    $raw_query = "
        SELECT ID, post_title, post_content, post_excerpt, post_author, post_date, guid 
        FROM {$wpdb->posts} 
        WHERE post_type IN ($in_clause)
        AND post_status = 'publish'
        AND (post_title LIKE '$search_term' OR post_content LIKE '$search_term')
        ORDER BY post_date DESC
        LIMIT $per_page OFFSET $offset
    ";

    back_trace('NOTICE', 'RAW SQL (DEV ONLY): ' . $raw_query);

    // Use raw query for testing — safe because $in_clause and $search_term are escaped
    $results = $wpdb->get_results($raw_query);

    back_trace('NOTICE', 'Actual result count: ' . count($results));

    if (!$results) {
        back_trace('NOTICE', 'No results found or query error');
        return [
            'success' => true,
            'total_posts' => 0,
            'total_pages' => 0,
            'current_page' => $page,
            'results' => [],
            'message' => 'No results found.'
        ];
    }

    $formatted_results = [];
    foreach ($results as $post) {
        $formatted_results[] = [
            'ID' => $post->ID,
            'title' => $post->post_title,
            'url' => $post->guid,
            'date' => $post->post_date,
            'author' => get_the_author_meta('display_name', $post->post_author),
            'excerpt' => $include_excerpt ? strip_tags($post->post_content) : null
        ];
    }

    // Count query
    $count_query = "
        SELECT COUNT(*) 
        FROM {$wpdb->posts} 
        WHERE post_type IN ($in_clause)
        AND post_status = 'publish'
        AND (post_title LIKE '$search_term' OR post_content LIKE '$search_term')
    ";

    $total_posts = (int) $wpdb->get_var($count_query);

    $response = [
        'success' => true,
        'total_posts' => $total_posts,
        'total_pages' => ceil($total_posts / $per_page),
        'current_page' => $page,
        'results' => $formatted_results,
    ];

    if (empty($formatted_results)) {
        $response['message'] = 'No results found.';
    }

    back_trace('NOTICE', 'Search completed successfully');
    back_trace('NOTICE', 'Results count: ' . count($formatted_results));

    return $response;
}


// Search - Deprecated - Ver 2.2.9 - 2025-04-01
function chatbot_chatgpt_content_search_deprecated( $search_prompt ) {

    // DIAG - Diagnostic - Ver 2.2.4
    // back_trace( 'NOTICE', 'chatbot_chatgpt_content_search' );

    global $wpdb;
    global $stopWords;

    // Empty prompt - shouldn't happen
    if ( ! isset( $search_prompt ) ) {
        return;
    }

    $tf_idf_table_name = $wpdb->prefix . 'chatbot_chatgpt_knowledge_base_tfidf';

    // Remove stopwords and punctuation, then normalize whitespace
    $search_prompt = preg_replace(
        [
            '/\b(' . implode('|', $stopWords) . ')\b/i', // Remove stopwords
            '/[^\w\s\'-]/', // Remove unwanted punctuation except apostrophes and hyphens
            '/\s+/' // Normalize whitespace (replace multiple spaces with a single space)
        ],
        [' ', '', ' '], 
        trim($search_prompt)
    );

    // Convert the cleaned search prompt into an array of words
    $words = array_filter(preg_split('/\s+/', $search_prompt)); // Removes empty values

    // Initialize an array to store valid words
    $filtered_words = [];

    foreach ($words as $word) {
        // Check if the word exists in the TF-IDF database table
        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $tf_idf_table_name WHERE word LIKE %s", $word));

        if ($exists > 0) {
            // back_trace( 'NOTICE', 'Word exists: ' . $word);
            $filtered_words[] = $word; // Retain the word
        } else {
            // back_trace( 'NOTICE', 'Word does not exist: ' . $word);
        }
    }

    // Reconstruct the final cleaned search prompt
    $cleaned_prompt = implode(' ', $filtered_words);

    // DIAG - Diagnostic - Ver 2.2.4
    // back_trace( 'NOTICE', '$cleaned_prompt: ' . $cleaned_prompt);

    $words = preg_split('/\s+/', trim($cleaned_prompt));
    $words = array_filter($words); // remove any empty entries

    // If somehow no words, bail
    if ( empty( $words ) ) {
        return '';
    }
    
    // Build the SUM(...) expression. For each word, we do:
    // (post_title LIKE '%...%' OR post_content LIKE '%...%')
    // Then sum all of them.
    $score_parts = array();
    $placeholders = array();

    foreach ( $words as $word ) {

        // Clean up the word for safe LIKE usage
        $like = '%' . $wpdb->esc_like( $word ) . '%';

        // Each piece is (post_title LIKE %s OR post_content LIKE %s)
        // We'll add placeholders for these.
        $score_parts[] = "(post_title LIKE %s OR post_content LIKE %s)";

        // We'll push the same $like param twice (once for title, once for content)
        $placeholders[] = $like;
        $placeholders[] = $like;

    }

    // Join them with a plus sign so MySQL sums each match
    // e.g. (title LIKE %s OR content LIKE %s) + (title LIKE %s OR content LIKE %s) + ...
    $score_expression = implode(' + ', $score_parts);
    
    $table_name = $wpdb->prefix . 'posts';

    // Construct the query with a "HAVING score >= x" condition
    // then require a minimum number of word-matches.
    // For example, at least 1 word: HAVING score >= 1
    // $min_score = 3;
    $min_score = esc_attr( get_option( 'chatbot_chatgpt_search_min_score', 3 ) );

    // Set the minimum possible score
    $min_score_limit = 1;

    // Or at least half the words: HAVING score >= floor(count($words)/2), etc.
    // $min_score = floor( count( $words ) / 2 );

    // Limit the number of results
    // $limit = 3;
    $limit = esc_attr( get_option( 'chatbot_chatgpt_search_limit', 3 ) );

    // Search for a match
    do {
        // Construct the query
        $query = "SELECT 
                    ID,
                    post_title,
                    post_content,
                    ($score_expression) AS score
                FROM $table_name
                WHERE post_type IN ('post', 'page', 'product')
                AND post_status = 'publish'
                HAVING score >= $min_score
                ORDER BY score DESC
                LIMIT $limit";
    
        // Prepare and execute
        $prepared_query = $wpdb->prepare( $query, $placeholders );
        $results = $wpdb->get_results( $prepared_query );
    
        // If results are found, exit the loop
        if ( ! empty( $results ) ) {
            break;
        }
    
        // Decrease the minimum score for the next iteration
        $min_score--;
    
    } while ( $min_score >= $min_score_limit ); // Stop when $min_score reaches 1

    // If you just want to return an array of the concatenated post_content:
    $contents = array();
    if ( ! empty( $results ) ) {
        foreach ( $results as $row ) {
            $contents[] = $row->post_content;
        }
    }

    $content_string = ''; // Will hold the combined text

    if ( ! empty( $results ) ) {
        foreach ( $results as $row ) {
            // Each $row is an object with post_content property
            $content_string .= $row->post_content . ' ';
        }
    }

    // Remove any HTML tags
    $content_string = strip_tags( $content_string );

    // Remove nbsp and other HTML entities
    $content_string = html_entity_decode( $content_string );

    // Remove any non-alphanumeric characters
    // $content_string = preg_replace( '/[^a-zA-Z0-9\s]/', '', $content_string );

    // Remove any extra whitespace
    $content_string = preg_replace( '/\s+/', ' ', $content_string );

    // Convert to lowercase
    // $content_string = strtolower( $content_string );

    // Remove EOL, CR/LF and tabs
    $content_string = str_replace( array( "\n", "\r", "\t", "\u{200B}" ), ' ', $content_string );

    // DIAG - Diagnostic - Ver 2.2.4
    // back_trace( 'NOTICE', '$content_string: ' . $content_string);
    // back_trace( 'NOTICE', 'Character count: ' . strlen( $content_string ) );
    // back_trace( 'NOTICE', 'Word count: ' . str_word_count( $content_string ) );

    return $content_string;

}