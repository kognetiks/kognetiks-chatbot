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
function transformer_model_lexical_context_response( $input, $max_tokens = null) {

    // DIAG - Diagnostic - Ver 2.2.1
    back_trace('NOTICE', 'transformer_model_lexical_context_response');

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
    // $embeddings = transformer_model_lexical_context_build_cooccurrence_matrix($corpus);
    $embeddings = transformer_model_lexical_context_get_cached_embeddings($corpus);

    // Response lenght
    $responseLength = $max_tokens;

    // Generate contextual response
    $response = transformer_model_lexical_context_generate_contextual_response($input, $embeddings, $corpus, $responseLength = 50);

    return $response;

}

// Transformer function to get cached embeddings
function transformer_model_lexical_context_get_cached_embeddings($corpus, $windowSize = 2) {

    // DIAG - Diagnostic - Ver 2.2.0
    back_trace('NOTICE', 'transformer_model_lexical_context_get_cached_embeddings');

    $cacheFile = __DIR__ . '/lexical_embeddings_cache.php';

    if (file_exists($cacheFile)) {
        $embeddings = include $cacheFile;
    } else {
        $embeddings = transformer_model_lexical_context_build_cooccurrence_matrix($corpus, $windowSize);
        file_put_contents($cacheFile, '<?php return ' . var_export($embeddings, true) . ';');
    }

    return $embeddings;

}

// Transformer function to read WordPress page and post content
function transformer_model_lexical_context_fetch_wordpress_content() {

    global $wpdb;

    // DIAG - Diagnostic - Ver 2.2.0
    back_trace( 'NOTICE', 'transformer_model_lexical_context_fetch_wordpress_content' );

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
    back_trace( 'NOTICE', 'transformer_model_lexical_context_generate_response' );

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
    return $bestMatch ?: "I didn't understand that, please try again.";

}

// Transformer function to generate a contextual response
function transformer_model_lexical_context_generate_contextual_response($input, $embeddings, $corpus, $responseLength = 10) {

    // DIAG - Diagnostic - Ver 2.2.1
    back_trace('NOTICE', 'transformer_model_lexical_context_generate_contextual_response');

    global $stopWords;

    // Tokenize the corpus into words and punctuation
    $words = preg_split('/(\s+|(?=[.,!?;:])|(?<=[.,!?;:]))/', $corpus, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

    // Prepare the input words
    $inputWords = preg_split('/\s+/', strtolower($input));
    $inputVector = [];

    // Build the input vector
    foreach ($inputWords as $word) {
        if (isset($embeddings[$word])) {
            foreach ($embeddings[$word] as $contextWord => $value) {
                $inputVector[$contextWord] = ($inputVector[$contextWord] ?? 0) + $value;
            }
        }
    }

    // Find the best matching word
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
        return "I didn't understand that, please try again.";
    }

    // Find all occurrences of the best match
    $lowerWords = array_map('strtolower', $words);
    $bestMatchIndices = array_keys($lowerWords, $bestMatch);
    if (empty($bestMatchIndices)) {
        return "I didn't understand that, please try again.";
    }

    // Use the first occurrence of the best match
    $bestMatchIndex = $bestMatchIndices[0];

    // Extract surrounding words for the response
    $start = max(0, $bestMatchIndex - floor($responseLength / 2));
    $end = min(count($words) - 1, $bestMatchIndex + floor($responseLength / 2));
    $responseWords = array_slice($words, $start, $end - $start + 1);

    // Reconstruct the response with proper capitalization and spacing
    $response = '';
    $capitalizeNext = true;

    foreach ($responseWords as $word) {
        // Check if the word is punctuation
        if (preg_match('/[.,!?;:]/', $word)) {
            $response = rtrim($response); // Remove any trailing space
            $response .= $word . ' ';
            if (in_array($word, ['.', '!', '?'])) {
                $capitalizeNext = true;
            }
        } elseif (trim($word) === '') {
            // Handle spaces
            $response .= ' ';
        } else {
            // Word token
            if ($capitalizeNext) {
                $word = ucfirst($word);
                $capitalizeNext = false;
            }
            $response .= $word . ' ';
        }
    }

    // Make sure the response does not end with a stop word
    $response = removeStopWordFromEnd($response, $stopWords);

    // Make sure the response does not start with any punctuation or whitespace
    $response = ltrim($response, " \t\n\r\0\x0B.,!?;:");

    // Trim any extra whitespace
    $response = trim($response);

    // Ensure the response ends with appropriate punctuation
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
    back_trace('NOTICE', 'Last Word: ' . $lastWord);

    // Check if the last word is a stop word
    if (in_array($lastWord, $stopWords)) {
        array_pop($responseWords); // Remove the last word
        $response = implode(' ', $responseWords); // Reconstruct the response
        return removeStopWordFromEnd($response, $stopWords); // Recursive call
    }

    return $response;

}