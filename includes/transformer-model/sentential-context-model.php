<?php
/**
 * Kognetiks Chatbot for WordPress - Transformer Model - Sentential Context Model (SCM) - Ver 2.2.0
 *
 * This file contains the code for implementing an enhanced Transformer-like algorithm in PHP.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Main function to get the chatbot's response
function transformer_model_sentential_context_model_response($input, $responseCount = 3) {

    // DIAG - Diagnostic - Ver 2.2.0
    back_trace('NOTICE', 'transformer_model_sentential_context_model_sentential_context_response');

    // MOVED TO transformer-model-scheduler.php
    // Fetch WordPress content
    $corpus = transformer_model_sentential_context_fetch_wordpress_content();

    // MOVED TO transformer-model-scheduler.php
    // Build embeddings (with caching for performance)
    $embeddings = transformer_model_sentential_context_get_cached_embeddings($corpus);

    // Generate contextual response
    $response = transformer_model_sentential_context_generate_contextual_response($input, $embeddings, $corpus, $responseCount);

    return $response;

}

// Function to fetch WordPress page and post content
function transformer_model_sentential_context_fetch_wordpress_content() {

    global $wpdb;

    // DIAG - Diagnostic - Ver 2.2.0
    back_trace('NOTICE', 'transformer_model_sentential_context_fetch_wordpress_content');

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

    // DIAG - Diagnostic - Ver 2.2.0
    back_trace('NOTICE', 'Content in characters: ' . strlen($content));

    // Clean up the content
    $content = strip_tags($content); // Remove HTML tags
    $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5); // Decode HTML entities

    // DIAG - Diagnostic - Ver 2.2.0
    back_trace('NOTICE', 'Content in characters after cleanup: ' . strlen($content));

    return $content;

}

// Function to build or retrieve cached embeddings
function transformer_model_sentential_context_get_cached_embeddings($corpus, $windowSize = 2) {

    // DIAG - Diagnostic - Ver 2.2.0
    back_trace('NOTICE', 'transformer_model_sentential_context_get_cached_embeddings');

    $cacheFile = __DIR__ . '/sentential_embeddings_cache.php';

    // Check if embeddings are cached
    if (file_exists($cacheFile)) {
        $embeddings = include $cacheFile;
    } else {
        $embeddings = transformer_model_sentential_context_build_cooccurrence_matrix($corpus, $windowSize);
        // Cache the embeddings
        file_put_contents($cacheFile, '<?php return ' . var_export($embeddings, true) . ';');
    }

    return $embeddings;

}

// Function to build a co-occurrence matrix for word embeddings
function transformer_model_sentential_context_build_cooccurrence_matrix($corpus, $windowSize = 2) {

    // DIAG - Diagnostic - Ver 2.2.0
    back_trace('NOTICE', 'transformer_model_sentential_context_build_cooccurrence_matrix');

    $matrix = [];
    $words = preg_split('/\s+/', strtolower($corpus)); // Tokenize and normalize
    $words = transformer_model_sentential_context_remove_stop_words($words); // Remove stop words

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

// Function to remove stop words from an array of words
function transformer_model_sentential_context_remove_stop_words($words) {

    // Use global stop words list
    global $stopWords;

    // $stopWords = [
    //     'the', 'is', 'at', 'which', 'on', 'and', 'a', 'an', 'of', 'in', 'to', 'it', 'for', 'with', 'as', 'was', 'were',
    //     'this', 'that', 'by', 'be', 'are', 'from', 'or', 'have', 'has', 'had', 'not', 'but', 'they', 'you', 'he', 'she',
    //     'we', 'his', 'her', 'their', 'them', 'can', 'will', 'would', 'there', 'what', 'when', 'where', 'who', 'how',
    //     'all', 'any', 'both', 'each', 'few', 'more', 'some', 'such', 'no', 'nor', 'too', 'very', 'one', 'do', 'does',
    //     'did', 'so', 'than', 'then', 'now', 'up', 'down', 'into', 'out', 'about', 'again', 'over', 'after', 'before',
    //     'between', 'under', 'above', 'below', 'same', 'other', 'another', 'while', 'during', 'without', 'within',
    //     'among', 'through', 'my', 'your', 'our', 'their', 'its'
    // ];

    return array_diff($words, $stopWords);

}

// Function to calculate cosine similarity between two vectors
function transformer_model_sentential_context_cosine_similarity($vectorA, $vectorB) {

    // DIAG - Diagnostic - Ver 2.2.0
    // back_trace( 'NOTICE', 'transformer_model_sentential_context_cosine_similarity' );

    $commonKeys = array_intersect_key($vectorA, $vectorB);

    if (empty($commonKeys)) {
        return 0;
    }

    $dotProduct = 0;
    $magnitudeA = 0;
    $magnitudeB = 0;

    foreach ($commonKeys as $key => $value) {
        $dotProduct += $vectorA[$key] * $vectorB[$key];
    }

    foreach ($vectorA as $value) {
        $magnitudeA += $value * $value;
    }

    foreach ($vectorB as $value) {
        $magnitudeB += $value * $value;
    }

    $magnitudeA = sqrt($magnitudeA);
    $magnitudeB = sqrt($magnitudeB);

    return ($magnitudeA * $magnitudeB) ? $dotProduct / ($magnitudeA * $magnitudeB) : 0;

}

// Function to generate a contextual response
function transformer_model_sentential_context_generate_contextual_response($input, $embeddings, $corpus, $maxResponseLength = 300) {

    // DIAG - Diagnostic - Ver 2.3.1
    back_trace('NOTICE', 'transformer_model_sentential_context_generate_contextual_response');

    // Tokenize the corpus into sentences
    $sentences = preg_split('/(?<=[.?!])\s+/', $corpus);
    $sentenceVectors = [];

    // Compute embeddings for sentences
    foreach ($sentences as $index => $sentence) {
        $sentenceWords = preg_split('/\s+/', strtolower($sentence));
        $sentenceWords = transformer_model_sentential_context_remove_stop_words($sentenceWords); // Remove stop words
        $sentenceVector = [];
        $wordCount = 0;

        foreach ($sentenceWords as $word) {
            if (isset($embeddings[$word])) {
                foreach ($embeddings[$word] as $contextWord => $value) {
                    $sentenceVector[$contextWord] = ($sentenceVector[$contextWord] ?? 0) + $value;
                }
                $wordCount++;
            }
        }

        // Normalize the sentence vector
        if ($wordCount > 0) {
            foreach ($sentenceVector as $key => $value) {
                $sentenceVector[$key] /= $wordCount;
            }
        }

        $sentenceVectors[$index] = $sentenceVector;
    }

    // Compute the input vector
    $inputWords = preg_split('/\s+/', strtolower($input));
    $inputWords = transformer_model_sentential_context_remove_stop_words($inputWords); // Remove stop words
    $inputVector = [];
    $wordCount = 0;

    foreach ($inputWords as $word) {
        if (isset($embeddings[$word])) {
            foreach ($embeddings[$word] as $contextWord => $value) {
                $inputVector[$contextWord] = ($inputVector[$contextWord] ?? 0) + $value;
            }
            $wordCount++;
        }
    }

    // Normalize the input vector
    if ($wordCount > 0) {
        foreach ($inputVector as $key => $value) {
            $inputVector[$key] /= $wordCount;
        }
    }

    // Compute similarities
    $similarities = [];

    foreach ($sentenceVectors as $index => $vector) {
        $similarity = transformer_model_sentential_context_cosine_similarity($inputVector, $vector);
        $similarities[$index] = $similarity;
    }

    // Find the index of the most similar sentence
    arsort($similarities);
    $bestMatchIndex = key($similarities);
    $bestMatchSentence = trim($sentences[$bestMatchIndex]);

    // Initialize the response
    $response = $bestMatchSentence;
    $responseLength = strlen($response);

    // Optionally include adjacent sentences for additional context
    $maxResponseLength = max($maxResponseLength, $responseLength); // Ensure the max length is not less than the response

    // Include previous sentence if it exists and doesn't exceed max length
    if ($bestMatchIndex > 0) {
        $previousSentence = trim($sentences[$bestMatchIndex - 1]);
        $candidateResponse = $previousSentence . ' ' . $response;
        if (strlen($candidateResponse) <= $maxResponseLength) {
            $response = $candidateResponse;
            $responseLength = strlen($response);
        }
    }

    // Include next sentence if it exists and doesn't exceed max length
    if ($bestMatchIndex < count($sentences) - 1) {
        $nextSentence = trim($sentences[$bestMatchIndex + 1]);
        $candidateResponse = $response . ' ' . $nextSentence;
        if (strlen($candidateResponse) <= $maxResponseLength) {
            $response = $candidateResponse;
            $responseLength = strlen($response);
        }
    }

    // Return the response
    return $response;

}
