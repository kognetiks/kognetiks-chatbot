<?php
/**
 * Kognetiks Chatbot for WordPress - Transformer Model - Lexical Context Model (LCM) - Ver 2.2.0
 *
 * This file contains the code for implementing a Transformer algorithm in PHP
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Transform input sentence into a response
function transformer_model_lexical_context_response( $input, $max_tokens = 50) {

    // DIAG - Diagnostic - Ver 2.2.1
    back_trace('NOTICE', 'transformer_model_lexical_context_response');

    // Fetch WordPress content
    $corpus = transformer_model_lexical_context_fetch_wordpress_content();

    // Build embeddings
    $embeddings = transformer_model_lexical_context_build_cooccurrence_matrix($corpus);

    // Generate contextual response
    $response = transformer_model_lexical_context_generate_contextual_response($input, $embeddings, $corpus);

    return $response;

}

// Transformer function to read WordPress page and post content
function transformer_model_lexical_context_fetch_wordpress_content() {

    global $wpdb;

    // DIAG - Diagnostic - Ver 2.2.0
    back_trace( 'NOTICE', 'transformer_model_lexical_context_fetch_wordpress_content' );

    // Query to get post and page content
    $results = $wpdb->get_results(
        "SELECT post_content FROM {$wpdb->prefix}posts WHERE post_status = 'publish' AND (post_type = 'post' OR post_type = 'page')",
        ARRAY_A
    );

    // Combine all content into a single string
    $content = '';
    foreach ($results as $row) {
        $content .= ' ' . $row['post_content'];
    }

    // Return combined content
    return strip_tags($content); // Remove HTML tags

}

// Transformer function to build a co-occurrence matrix for word embeddings
function transformer_model_lexical_context_build_cooccurrence_matrix($corpus, $windowSize = 2) {

    // DIAG - Diagnostic - Ver 2.2.0
    back_trace( 'NOTICE', 'transformer_model_lexical_context_build_cooccurrence_matrix' );

    $matrix = [];
    $words = preg_split('/\s+/', strtolower($corpus)); // Tokenize and normalize

    foreach ($words as $i => $word) {
        if (!isset($matrix[$word])) {
            $matrix[$word] = [];
        }

        for ($j = max(0, $i - $windowSize); $j <= min(count($words) - 1, $i + $windowSize); $j++) {
            if ($i !== $j) {
                $contextWord = $words[$j];
                $matrix[$word][$contextWord] = ($matrix[$word][$contextWord] ?? 0) + 1;
            }
        }
    }

    return $matrix;

}

// Transformer function to calculate cosine similarity between two vectors
function transformer_model_lexical_context_cosine_similarity($vectorA, $vectorB) {

    // DIAG - Diagnostic - Ver 2.2.0
    // back_trace( 'NOTICE', 'transformer_model_lexical_context_cosine_similarity' );

    $dotProduct = array_sum(array_map(fn($a, $b) => $a * $b, $vectorA, $vectorB));
    $magnitudeA = sqrt(array_sum(array_map(fn($x) => $x * $x, $vectorA)));
    $magnitudeB = sqrt(array_sum(array_map(fn($x) => $x * $x, $vectorB)));

    return $magnitudeA && $magnitudeB ? $dotProduct / ($magnitudeA * $magnitudeB) : 0;

}

// Transformer to generate a response based on the input
function transformer_model_lexical_context_generate_response($input, $embeddings) {

    // DIAG - Diagnostic - Ver 2.2.0
    back_trace( 'NOTICE', 'transformer_model_context_lexical_generate_response' );

    $inputWords = preg_split('/\s+/', strtolower($input));
    $inputVector = [];

    // Average embedding vectors for input words
    foreach ($inputWords as $word) {
        if (isset($embeddings[$word])) {
            foreach ($embeddings[$word] as $contextWord => $value) {
                $inputVector[$contextWord] = ($inputVector[$contextWord] ?? 0) + $value;
            }
        }
    }

    // Find the most similar word in the embeddings
    $bestMatch = '';
    $bestScore = -1;
    foreach ($embeddings as $word => $vector) {
        $similarity = transformer_model_lexical_context_cosine_similarity($inputVector, $vector);
        if ($similarity > $bestScore) {
            $bestMatch = $word;
            $bestScore = $similarity;
        }
    }

    // Return the best match or fallback response
    return $bestMatch ?: "I don't understand that.";

}

// Transformer function to generate a contextual response
function transformer_model_lexical_context_generate_contextual_response($input, $embeddings, $corpus, $responseLength = 10) {

    // DIAG - Diagnostic - Ver 2.2.1
    back_trace('NOTICE', 'transformer_model_lexical_context_generate_contextual_response');

    $inputWords = preg_split('/\s+/', strtolower($input));
    $inputVector = [];

    // Average embedding vectors for input words
    foreach ($inputWords as $word) {
        if (isset($embeddings[$word])) {
            foreach ($embeddings[$word] as $contextWord => $value) {
                $inputVector[$contextWord] = ($inputVector[$contextWord] ?? 0) + $value;
            }
        }
    }

    // Find the most similar word in the embeddings
    $bestMatch = '';
    $bestScore = -1;
    foreach ($embeddings as $word => $vector) {
        $similarity = transformer_model_lexical_context_cosine_similarity($inputVector, $vector);
        if ($similarity > $bestScore) {
            $bestMatch = $word;
            $bestScore = $similarity;
        }
    }

    if (!$bestMatch) {
        return "I don't understand that.";
    }

    // Extract context from the corpus
    $words = preg_split('/\s+/', strtolower($corpus));
    $bestMatchIndex = array_search($bestMatch, $words);
    if ($bestMatchIndex === false) {
        return "I don't understand that.";
    }

    // Collect surrounding words for response
    $start = max(0, $bestMatchIndex - floor($responseLength / 2));
    $end = min(count($words) - 1, $bestMatchIndex + floor($responseLength / 2));
    $responseWords = array_slice($words, $start, $end - $start + 1);

    return ucfirst(implode(' ', $responseWords)) . '.';

}
