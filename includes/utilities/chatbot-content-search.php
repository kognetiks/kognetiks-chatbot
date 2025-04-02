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

    // DIAG - Diagnostic - Ver 2.2.9
    back_trace('NOTICE', 'chatbot_chatgpt_content_search');
    back_trace('NOTICE', '====== SEARCH REQUEST RECEIVED ======');
    back_trace('NOTICE', 'Request parameters: ' . $search_prompt);

    global $wpdb;

    // Settings
    $include_excerpt = true;
    $page = 1;
    $per_page = 5;
    $offset = ($page - 1) * $per_page;

    // Get post types to search
    $post_types = get_searchable_post_types();

    // DIAG - Diagnostic - Ver 2.2.9
    back_trace('NOTICE', 'Searchable post types: ' . implode(', ', $post_types));

    // Clean and prepare search terms
    $search_terms = prepare_search_terms($search_prompt);
    // DIAG - Diagnostic - Ver 2.2.9
    back_trace('NOTICE', 'Prepared search terms: ' . implode(', ', $search_terms));
    
    // If no search terms after preparation, return empty result
    if (empty($search_terms)) {
        // DIAG - Diagnostic - Ver 2.2.9
        back_trace('NOTICE', 'No valid search terms after preparation');
        return [
            'success' => true,
            'total_posts' => 0,
            'total_pages' => 0,
            'current_page' => $page,
            'results' => [],
            'message' => 'No valid search terms.'
        ];
    }

    // Build search conditions
    $search_conditions = [];
    $placeholders = [];
    
    foreach ($search_terms as $term) {
        $like_term = '%' . $wpdb->esc_like($term) . '%';
        $search_conditions[] = "(post_title LIKE %s OR post_content LIKE %s)";
        $placeholders[] = $like_term;  // For post_title
        $placeholders[] = $like_term;  // For post_content
    }

    // Escape and build IN clause for post types
    $in_clause = implode(',', array_map(fn($type) => "'" . esc_sql($type) . "'", $post_types));
    
    // Build the main query
    $query = "
        SELECT ID, post_title, post_content, post_excerpt, post_author, post_date, guid
        FROM {$wpdb->posts} 
        WHERE post_type IN ($in_clause)
        AND post_status = 'publish'
        AND (". implode(' OR ', $search_conditions) .")
        ORDER BY post_date DESC
        LIMIT %d OFFSET %d
    ";

    // Add the LIMIT parameters
    $placeholders[] = $per_page;
    $placeholders[] = $offset;

    // DIAG - Diagnostic - Ver 2.2.9
    // back_trace('NOTICE', 'Query template: ' . $query);
    back_trace('NOTICE', 'Placeholders: ' . print_r($placeholders, true));

    try {
        // Prepare and execute the query
        $prepared_query = $wpdb->prepare($query, ...$placeholders);
        // DIAG - Diagnostic - Ver 2.2.9
        // back_trace('NOTICE', 'RAW SQL (DEV ONLY): ' . $prepared_query);
        
        $results = $wpdb->get_results($prepared_query);
        
        if ($wpdb->last_error) {
            back_trace('ERROR', 'Database error: ' . $wpdb->last_error);
        }
        
    } catch (Exception $e) {
        // DIAG - Diagnostic - Ver 2.2.9
        back_trace('ERROR', 'Exception in query preparation: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Search query failed.',
            'error' => $e->getMessage()
        ];
    }

    // DIAG - Diagnostic - Ver 2.2.9
    back_trace('NOTICE', 'Actual result count: ' . count($results));

    if (!$results) {
        // DIAG - Diagnostic - Ver 2.2.9
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
        // DIAG - Diagnostic - Ver 2.2.9
        // back_trace('NOTICE', 'Formatted result: ' . print_r($formatted_results, true));
        back_trace('NOTICE', 'URL: ' . $post->guid );
        back_trace('NOTICE', ' - Title: ' . $post->post_title);
    }

    // Count query needs its own set of placeholders
    $count_placeholders = [];
    foreach ($search_terms as $term) {
        $like_term = '%' . $wpdb->esc_like($term) . '%';
        // Add placeholder twice - once for title, once for content
        $count_placeholders[] = $like_term;  // For post_title LIKE %s
        $count_placeholders[] = $like_term;  // For post_content LIKE %s
    }

    // Count query
    $count_query = "
        SELECT COUNT(*) 
        FROM {$wpdb->posts} 
        WHERE post_type IN ($in_clause)
        AND post_status = 'publish'
        AND (". implode(' OR ', $search_conditions) .")
    ";

    try {
        // Prepare and execute the count query
        $prepared_count_query = $wpdb->prepare($count_query, ...$count_placeholders);
        // back_trace('NOTICE', 'Count SQL: ' . $prepared_count_query);
        
        $total_posts = (int) $wpdb->get_var($prepared_count_query);
        
        if ($wpdb->last_error) {
            // DIAG - Diagnostic - Ver 2.2.9
            back_trace('ERROR', 'Count query error: ' . $wpdb->last_error);
            $total_posts = count($results); // Fallback to result count if count query fails
        }
        
    } catch (Exception $e) {
        // DIAG - Diagnostic - Ver 2.2.9
        back_trace('ERROR', 'Exception in count query: ' . $e->getMessage());
        $total_posts = count($results); // Fallback to result count if count query fails
    }

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

    // DIAG - Diagnostic - Ver 2.2.9
    back_trace('NOTICE', 'Search completed successfully');
    back_trace('NOTICE', 'Results count: ' . count($formatted_results));

    return $response;

}

// Helper function to get searchable post types
function get_searchable_post_types() {

    global $wpdb;

    $registered_types = get_post_types(['public' => true], 'objects');
    $post_types = [];

    // First, handle registered post types
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

    // DIAG - Diagnostic - Ver 2.2.9
    back_trace('NOTICE', 'Searchable post types: ' . implode(', ', $post_types));

    return $post_types;
    
}

// Helper function to prepare search terms
function prepare_search_terms($search_prompt) {

    // Use the global stop words array
    global $stopWords;
    
    // Convert to lowercase
    $search_prompt = strtolower($search_prompt);
    
    // Remove punctuation but keep spaces
    $search_prompt = preg_replace('/[^\w\s]/', ' ', $search_prompt);
    
    // Split into words
    $words = preg_split('/\s+/', trim($search_prompt));
    
    // Remove stop words and empty strings
    $words = array_filter($words, function($word) use ($stopWords) {
        return !empty($word) && !in_array($word, $stopWords) && strlen($word) > 2;
    });
    
    // Convert filtered words to terms
    $terms = array_values(array_unique($words));
    
    // DIAG - Diagnostic - Ver 2.2.9
    back_trace('NOTICE', 'Final search terms: ' . implode(', ', $terms));
    
    return $terms;

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