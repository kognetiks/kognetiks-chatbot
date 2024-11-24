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
function transformer_model_sentential_context_model_response($input, $responseCount = 500) {

    // DIAG - Diagnostic - Ver 2.2.0
    back_trace( 'NOTICE', 'transformer_model_sentential_context_model_response');

    // Fetch embeddings from the database
    $embeddings = transformer_model_sentential_context_fetch_embeddings_from_db();

    // Retrieve WordPress content for tokenization
    $corpus = transformer_model_sentential_context_fetch_wordpress_content();

    // Generate contextual response
    $response = transformer_model_sentential_context_generate_contextual_response($input, $embeddings, $corpus, $responseCount);

    return $response;

}

// Function to fetch WordPress page and post content
function transformer_model_sentential_context_fetch_wordpress_content() {

    global $wpdb;

    // DIAG - Diagnostic - Ver 2.2.0
    back_trace( 'NOTICE', 'transformer_model_sentential_context_fetch_wordpress_content');

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

    // Clean up the content
    $content = strip_tags($content); // Remove HTML tags
    $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5); // Decode HTML entities

    // DIAG - Diagnostic - Ver 2.2.0
    back_trace( 'NOTICE', 'Content size in characters after cleanup: ' . strlen($content));

    return $content;

}

// Function to fetch embeddings from the database
function transformer_model_sentential_context_fetch_embeddings_from_db() {

    global $wpdb;

    // DIAG - Diagnostic - Ver 2.2.0
    back_trace( 'NOTICE', 'transformer_model_sentential_context_fetch_embeddings_from_db');

    back_trace( 'NOTICE', "Memory Limit: " . round( ini_get('memory_limit') / 1024 / 1024, 2 ) . " MB" );
    back_trace( 'NOTICE', "Memory Usage: " . round( memory_get_usage($real_usage = true) / 1024 / 1024, 2 ) . " MB" );
    back_trace( 'NOTICE', "Memory allocated: " . round( memory_get_peak_usage() / 1024 / 1024, 2 ) . " MB" );
    back_trace( 'NOTICE', "Memory Usage (BEFORE): " . round( memory_get_usage() / 1024 / 1024, 2 ) . " MB" );

    // Fetch all embeddings from the database
    $results = $wpdb->get_results("SELECT post_id, context, word, count FROM {$wpdb->prefix}chatbot_sentential_embeddings", ARRAY_A);

    $embeddings = [];
    foreach ($results as $row) {
        $post_id = $row['post_id'];
        $context = $row['context'];
        $word = $row['word'];
        $count = $row['count'];

        if (!isset($embeddings[$post_id])) {
            $embeddings[$post_id] = [];
        }

        if (!isset($embeddings[$post_id][$context])) {
            $embeddings[$post_id][$context] = [];
        }

        $embeddings[$post_id][$context][$word] = $count;
    }

    back_trace( 'NOTICE', "Memory Limit: " . round( ini_get('memory_limit') / 1024 / 1024, 2 ) . " MB" );
    back_trace( 'NOTICE', "Memory Usage: " . round( memory_get_usage($real_usage = true) / 1024 / 1024, 2 ) . " MB" );
    back_trace( 'NOTICE', "Memory allocated: " . round( memory_get_peak_usage() / 1024 / 1024, 2 ) . " MB" );
    back_trace( 'NOTICE', "Memory Usage (BEFORE): " . round( memory_get_usage() / 1024 / 1024, 2 ) . " MB" );

    return $embeddings;

}

// Function to build embeddings and save them to the database
function transformer_model_sentential_context_build_and_store_embeddings($corpus) {

    global $wpdb;


    back_trace( 'NOTICE', 'transformer_model_sentential_context_build_and_store_embeddings');

    $windowSize = intval(esc_attr(get_option('chatbot_transformer_model_word_content_window_size', 3)));
    $embeddings = transformer_model_sentential_context_build_cooccurrence_matrix($corpus, $windowSize);

    // Store embeddings in the database
    foreach ($embeddings as $post_id => $postEmbeddings) {
        add_sentential_embeddings_to_table($post_id, $postEmbeddings);
    }

    return $embeddings;

}

// Function to build a co-occurrence matrix for word embeddings
function transformer_model_sentential_context_build_cooccurrence_matrix($corpus, $windowSize = 2) {

    // DIAG - Diagnostic - Ver 2.2.0
    // back_trace( 'NOTICE', 'transformer_model_sentential_context_build_cooccurrence_matrix');

    $matrix = [];
    $words = preg_split('/\s+/', strtolower($corpus)); // Tokenize and normalize
    $words = transformer_model_sentential_context_remove_stop_words($words); // Remove stop words

    foreach ($words as $i => $word) {
        if (!isset($matrix[$word])) {
            $matrix[$word] = [];
        }

        for ($j = max(0, $i - $windowSize); $j <= min(count($words) - 1, $i + $windowSize); $j++) {
            if ($i !== $j) {
                if (isset($words[$j])) {
                    $contextWord = $words[$j];
                    $matrix[$word][$contextWord] = ($matrix[$word][$contextWord] ?? 0) + 1;
                }
            }
        }
    }

    return $matrix;

}

// Function to generate a contextual response
function transformer_model_sentential_context_generate_contextual_response($input, $embeddings, $corpus, $maxTokens = 500) {

    global $chatbotFallbackResponses;

    // DIAG - Diagnostic - Ver 2.3.0
    back_trace( 'NOTICE', 'transformer_model_sentential_context_generate_contextual_response');
    if (!empty($maxTokens)) {
        back_trace( 'NOTICE', 'Max Tokens: ' . $maxTokens);
    } else {
        back_trace( 'NOTICE', 'Max Tokens: (empty)');
    }
    if (!empty($input)) {
        back_trace( 'NOTICE', 'Input: ' . $input);
    } else {
        back_trace( 'NOTICE', 'Input: (empty)');
    }
    if (!empty($corpus)) {
        back_trace( 'NOTICE', 'Corpus: ' . substr($corpus, 0, 100) . '...');
    } else {
        back_trace( 'NOTICE', 'Corpus: (empty)');
    }
    if (!empty($embeddings)) {
        back_trace( 'NOTICE', 'Embeddings: ' . count($embeddings) . ' posts');
    } else {
        back_trace( 'NOTICE', 'Embeddings: (empty)');
    }
 
    back_trace( 'NOTICE', "Memory Limit: " . round( ini_get('memory_limit') / 1024 / 1024, 2 ) . " MB" );
    back_trace( 'NOTICE', "Memory Usage: " . round( memory_get_usage($real_usage = true) / 1024 / 1024, 2 ) . " MB" );
    back_trace( 'NOTICE', "Memory allocated: " . round( memory_get_peak_usage() / 1024 / 1024, 2 ) . " MB" );
    back_trace( 'NOTICE', "Memory Usage (BEFORE): " . round( memory_get_usage() / 1024 / 1024, 2 ) . " MB" );

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
                    $sentenceVector[$contextWord] = ($sentenceVector[$contextWord] ?? 0) + (is_array($value) ? 0 : $value);
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

    // Calculate key stats
    $highestSimilarity = max($similarities);
    $averageSimilarity = array_sum($similarities) / count($similarities);
    $matchesAboveThreshold = array_filter($similarities, function($similarity) {
        return $similarity > floatval(get_option('chatbot_transformer_model_similarity_threshold', 0.2));
    });
    $numMatchesAboveThreshold = count($matchesAboveThreshold);
    $totalSentencesAnalyzed = count($sentences);

    // Log key stats
    back_trace( 'NOTICE', 'Key Stats:');
    back_trace( 'NOTICE', ' - Highest Similarity: ' . $highestSimilarity);
    back_trace( 'NOTICE', ' - Average Similarity: ' . $averageSimilarity);
    back_trace( 'NOTICE', ' - Matches Above Threshold: ' . $numMatchesAboveThreshold);
    back_trace( 'NOTICE', ' - Total Sentences Analyzed: ' . $totalSentencesAnalyzed);

    // Add a similarity threshold
    $similarityThreshold = floatval(get_option('chatbot_transformer_model_similarity_threshold', 0.2)); // Default to 0.2

    // If the highest similarity is below the threshold, return a fallback message
    if ($highestSimilarity < $similarityThreshold) {
        back_trace( 'NOTICE', 'Low similarity detected: ' . $highestSimilarity);
        return $chatbotFallbackResponses[array_rand($chatbotFallbackResponses)];
    }

    // Find the index of the most similar sentence
    arsort($similarities);
    $bestMatchIndex = key($similarities);
    $bestMatchSentence = trim($sentences[$bestMatchIndex]);

    // Initialize the response
    $response = $bestMatchSentence;

    // Retrieve settings
    $maxSentences = intval(esc_attr(get_option('chatbot_transformer_model_sentence_response_length', 5)));
    $maxTokens = intval(esc_attr(get_option('chatbot_transformer_model_max_tokens', 500)));

    // Ratios for splitting sentences and tokens
    $sentenceBeforeRatio = 0.0;
    $tokenBeforeRatio = 0.0;

    // Distribute sentences and tokens
    $sentencesBefore = floor($maxSentences * $sentenceBeforeRatio);
    $sentencesAfter = $maxSentences - $sentencesBefore;
    $tokensBefore = floor($maxTokens * $tokenBeforeRatio);
    $tokensAfter = $maxTokens - $tokensBefore;

    $responseWordCount = str_word_count($response);

    // Add sentences before the best match
    $tokensUsedBefore = 0;
    $sentencesUsedBefore = 0;
    for ($i = $bestMatchIndex - 1; $i >= 0 && $sentencesUsedBefore < $sentencesBefore && $tokensUsedBefore < $tokensBefore; $i--) {
        $previousSentence = trim($sentences[$i]);
        $sentenceWordCount = str_word_count($previousSentence);
        if ($tokensUsedBefore + $sentenceWordCount <= $tokensBefore) {
            $response = $previousSentence . ' ' . $response;
            $tokensUsedBefore += $sentenceWordCount;
            $sentencesUsedBefore++;
        } else {
            break;
        }
    }

    // Add sentences after the best match
    $tokensUsedAfter = 0;
    $sentencesUsedAfter = 0;
    for ($i = $bestMatchIndex + 1; $i < count($sentences) && $sentencesUsedAfter < $sentencesAfter && $tokensUsedAfter < $tokensAfter; $i++) {
        $nextSentence = trim($sentences[$i]);
        $sentenceWordCount = str_word_count($nextSentence);
        if ($tokensUsedAfter + $sentenceWordCount <= $tokensAfter) {
            $response .= ' ' . $nextSentence;
            $tokensUsedAfter += $sentenceWordCount;
            $sentencesUsedAfter++;
        } else {
            break;
        }
    }

    // Return the response
    return $response;

}

// Function to remove stop words from an array of words
function transformer_model_sentential_context_remove_stop_words($words) {

    global $stopWords;

    return array_diff($words, $stopWords);

}