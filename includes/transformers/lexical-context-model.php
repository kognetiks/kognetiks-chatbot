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

// Main function to generate a response
function transformer_model_lexical_context_response( $input, $max_tokens = null ) {

    $max_tokens = 50;

    // DIAG - Diagnostics - Ver 2.3.0
    // back_trace( 'NOTICE', 'transformer_model_lexical_context_response');

    // Maximum tokens
    if (empty($max_tokens)) {
        $max_tokens = esc_attr(get_option('chatbot_transformer_model_max_tokens', 50));
    }

    // Belt & Suspenders - Check for clean input
    $input = sanitize_text_field($input);
    if (empty($input)) {
        return "I didn't understand that, please try again.";
    }

    // Fetch WordPress content
    $corpus = transformer_model_lexical_context_fetch_wordpress_content();

    // Build embeddings
    $embeddings = transformer_model_lexical_context_get_cached_embeddings($corpus);

    // Generate contextual response
    $response = transformer_model_lexical_context_generate_contextual_response($input, $embeddings, $corpus, $max_tokens);

    return $response;

}

// Function to get cached embeddings
function transformer_model_lexical_context_get_cached_embeddings($corpus, $windowSize = 3) {

    // DIAG - Diagnostics - Ver 2.3.0
    // back_trace( 'NOTICE', 'transformer_model_lexical_context_get_cached_embeddings');

    $cacheFile = __DIR__ . '/lexical_embeddings_cache.php';

    if (file_exists($cacheFile)) {
        $embeddings = include $cacheFile;
    } else {
        $embeddings = transformer_model_lexical_context_build_pmi_matrix($corpus, $windowSize);
        file_put_contents($cacheFile, '<?php return ' . var_export($embeddings, true) . ';');
    }

    return $embeddings;

}

// Function to fetch WordPress content
function transformer_model_lexical_context_fetch_wordpress_content() {

    global $wpdb;

    // DIAG - Diagnostics - Ver 2.3.0
    // back_trace( 'NOTICE', 'transformer_model_lexical_context_fetch_wordpress_content' );

    // Query to get post and page content
    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT post_content FROM {$wpdb->posts} WHERE post_status = %s AND (post_type = %s OR post_type = %s)",
            'publish', 'post', 'page'
        ),
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

// Function to build a PMI matrix for word embeddings
function transformer_model_lexical_context_build_pmi_matrix($corpus, $windowSize = 3) {

    // DIAG - Diagnostics - Ver 2.3.0
    // back_trace( 'NOTICE', 'transformer_model_lexical_context_build_pmi_matrix' );

    $words = preg_split('/\s+/', strtolower($corpus)); // Tokenize and normalize
    $vocab = array_unique($words);
    $wordCounts = array_count_values($words);
    $totalWords = count($words);

    // Initialize co-occurrence counts
    $coOccurrenceCounts = [];

    for ($i = 0; $i < count($words); $i++) {
        $word = $words[$i];
        $contextStart = max(0, $i - $windowSize);
        $contextEnd = min(count($words) - 1, $i + $windowSize);
        for ($j = $contextStart; $j <= $contextEnd; $j++) {
            if ($i != $j) {
                $contextWord = $words[$j];
                if (!isset($coOccurrenceCounts[$word][$contextWord])) {
                    $coOccurrenceCounts[$word][$contextWord] = 0;
                }
                $coOccurrenceCounts[$word][$contextWord] += 1;
            }
        }
    }

    // Compute PMI values
    $embeddings = [];
    foreach ($coOccurrenceCounts as $word => $contexts) {
        foreach ($contexts as $contextWord => $count) {
            $p_word = $wordCounts[$word] / $totalWords;
            $p_context = $wordCounts[$contextWord] / $totalWords;
            $p_word_context = $count / $totalWords;
            $pmi = log($p_word_context / ($p_word * $p_context));
            if ($pmi > 0) {
                $embeddings[$word][$contextWord] = $pmi;
            }
        }
    }

    return $embeddings;
    
}

// Function to calculate cosine similarity between two vectors
function transformer_model_lexical_context_cosine_similarity($vectorA, $vectorB) {

    // DIAG - Diagnostics - Ver 2.3.0
    // back_trace( 'NOTICE', 'transformer_model_lexical_context_cosine_similarity' );

    $dotProduct = 0;
    $magnitudeA = 0;
    $magnitudeB = 0;

    $allKeys = array_unique(array_merge(array_keys($vectorA), array_keys($vectorB)));

    foreach ($allKeys as $key) {
        $a = isset($vectorA[$key]) ? $vectorA[$key] : 0;
        $b = isset($vectorB[$key]) ? $vectorB[$key] : 0;

        $dotProduct += $a * $b;
        $magnitudeA += $a * $a;
        $magnitudeB += $b * $b;
    }

    $magnitudeA = sqrt($magnitudeA);
    $magnitudeB = sqrt($magnitudeB);

    if ($magnitudeA * $magnitudeB == 0) {
        return 0;
    }

    return $dotProduct / ($magnitudeA * $magnitudeB);

}

// Function to generate a contextual response
function transformer_model_lexical_context_generate_contextual_response($input, $embeddings, $corpus, $responseLength = 50) {

    // DIAG - Diagnostics - Ver 2.3.0
    // back_trace( 'NOTICE', 'transformer_model_lexical_context_generate_contextual_response');

    global $stopWords;

    // Preprocess input
    $inputWords = preg_split('/\s+/', strtolower($input));

    // Build input embedding
    $inputEmbedding = [];
    foreach ($inputWords as $word) {
        if (isset($embeddings[$word])) {
            foreach ($embeddings[$word] as $contextWord => $value) {
                $inputEmbedding[$contextWord] = ($inputEmbedding[$contextWord] ?? 0) + $value;
            }
        }
    }

    // Compute similarities with vocabulary words
    $similarities = [];
    foreach ($embeddings as $word => $vector) {
        $similarity = transformer_model_lexical_context_cosine_similarity($inputEmbedding, $vector);
        if ($similarity > 0) {
            $similarities[$word] = $similarity;
        }
    }

    // Sort words by similarity
    arsort($similarities);

    // Generate response by selecting top similar words
    $responseWords = array_slice(array_keys($similarities), 0, $responseLength);

    // Reconstruct the response
    $response = implode(' ', $responseWords);

    // Make sure the response does not end with a stop word
    $response = removeStopWordFromEnd($response, $stopWords);

    // Ensure the response ends with appropriate punctuation
    $response = ucfirst($response);
    if (!preg_match('/[.!?]$/', $response)) {
        $response .= '.';
    }

    return $response;

}

// Trim off any stop words from the end of the response
function removeStopWordFromEnd($response, $stopWords) {

    // Split the response into words
    $responseWords = preg_split('/\s+/', rtrim($response, " \t\n\r\0\x0B.,!?;:"));
    $lastWord = strtolower(end($responseWords));
    // back_trace( 'NOTICE', 'removeStopWordFromEnd - Last Word: ' . $lastWord);

    // Check if the last word is a stop word
    if (in_array($lastWord, $stopWords)) {
        array_pop($responseWords); // Remove the last word
        $response = implode(' ', $responseWords); // Reconstruct the response
        return removeStopWordFromEnd($response, $stopWords); // Recursive call
    }

    return $response;

}