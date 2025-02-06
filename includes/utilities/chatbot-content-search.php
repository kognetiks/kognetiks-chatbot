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

// Search
function chatbot_chatgpt_content_search( $search_prompt ) {

    // DIAG - Diagnostic - Ver 2.2.4
    // back_trace( 'NOTICE', 'chatbot_chatgpt_content_search' );

    global $wpdb;
    global $stopWords;

    // Empty prompt - shouldn't happen
    if ( ! isset( $search_prompt ) ) {
        return;
    }

    $tf_idf_table_name = $wpdb->prefix . 'chatbot_chatgpt_knowledge_base_tfidf';

    // Remove stopwords from the $search_prompt
    $search_prompt = preg_replace('/\b(' . implode('|', $stopWords) . ')\b/i', '', $search_prompt);
    
    // Remove any punctuation from the search prompt
    $search_prompt = preg_replace('/[^\w\s\'-]/', '', $search_prompt);
    
    // Convert the search prompt into an array of words
    $words = preg_split('/\s+/', trim($search_prompt));
    
    // Initialize an array to store valid words
    $filtered_words = [];
    
    foreach ($words as $word) {
        $word = trim($word);
        if ($word === '') {
            continue; // Skip empty entries
        }
    
        // Check if the word exists in the TF-IDF database table
        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $tf_idf_table_name WHERE word LIKE %s", $word));
    
        if ($exists > 0) {
            back_trace( 'NOTICE', 'Word exists: ' . $word );
            $filtered_words[] = $word; // Retain the word
        } else {
            back_trace( 'NOTICE', 'Word does not exist: ' . $word );
        }
    }
    
    // Reconstruct the filtered search prompt
    $search_prompt = implode(' ', $filtered_words);
    
    // Split prompt into individual words without punctuation
    $cleaned_prompt = preg_replace('/[^\w\s\'-]/', '', $search_prompt);
    
    // Normalize whitespace (replace multiple spaces with a single space)
    $cleaned_prompt = preg_replace('/\s+/', ' ', trim($cleaned_prompt));
    
    // DIAG - Diagnostic - Ver 2.2.4
    back_trace('NOTICE', '$cleaned_prompt: ' . $cleaned_prompt);

    $words = preg_split('/\s+/', trim($cleaned_prompt));
    $words = array_filter($words); // remove any empty entries

    // If somehow no words, bail
    if ( empty( $words ) ) {
        return array();
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
    back_trace( 'NOTICE', 'Character count: ' . strlen( $content_string ) );
    back_trace( 'NOTICE', 'Word count: ' . str_word_count( $content_string ) );

    return $content_string;

}