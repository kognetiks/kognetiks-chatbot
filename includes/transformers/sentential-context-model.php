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

// Transformer Model - Sentential Context Model (SCM)
function transformer_model_sentential_context_model_response($input, $responseCount = 500) {

    // DIAG - Diagnostic - Ver 2.2.0
    back_trace('NOTICE', 'transformer_model_sentential_context_model_sentential_context_response');

    global $wpdb;

    // STEP 1 - Determine the number of batches
    $batchSize = 50;

    $totalItems = (int) $wpdb->get_var(
        "SELECT COUNT(*) 
         FROM {$wpdb->posts} 
         WHERE post_status = 'publish' 
           AND (post_type = 'post' OR post_type = 'page')"
    );

    // DIAG - Diagnostic - Ver 2.2.1
    back_trace('NOTICE', 'Total published items: ' . $totalItems);

    // Calculate the number of batches
    $numBatches = ceil($totalItems / $batchSize);

    // DIAG - Diagnostic - Ver 2.2.1
    back_trace('NOTICE', 'Number of batches: ' . $numBatches);

    // STEP 2 - Initialize array to hold the "best response" from each batch
    $batchResponses = [];

    // STEP 3 - Loop through the content in batches
    for ($start = 0; $start < $totalItems; $start += $batchSize) {
        
        // Calculate the offset end (e.g., 49 if start=0, 99 if start=50, etc.)
        $end = $start + $batchSize - 1;

        // DIAG
        back_trace('NOTICE', sprintf('Processing batch offset %d - %d', $start, $end));

        // STEP 3a - Fetch exactly 50 (or fewer if near the end) published items
        $corpus = transformer_model_sentential_context_fetch_wordpress_content($start, $end);
        
        // STEP 3b - (Re)build or reuse embeddings as needed; depends on your cache logic
        //     If you have a global or partial cache, you can slice it or re-generate it here.
        //     For demonstration, assume you have a function that fetches embeddings on the fly:
        $windowSize = intval(esc_attr(get_option('chatbot_transformer_model_word_content_window_size', 3)));
        // For big performance gains, you might want to keep an in-memory or file-based cache keyed by offsets
        // or by post IDs. This is just a placeholder:
        $embeddings = transformer_model_sentential_context_get_cached_embeddings($corpus, $windowSize);

        // STEP 3c - Generate a response for this batch
        $batchResponse = transformer_model_sentential_context_generate_contextual_response(
            $input,
            $embeddings,
            $corpus,
            $responseCount
        );

        // STEP 3d - **Pick the "best" response** from this batch. 
        //     In some cases, your generate_contextual_response might return a single best result.
        //     If it returns multiple suggestions, you’d pick the best among them here.
        //     For example, if it returns an array, you might do something like:
        //
        //       $bestFromThisBatch = pick_best_response($batchResponse);
        //
        //     For simplicity, assume it returns a single best string.  
        $bestFromThisBatch = $batchResponse;

        // Collect the best from each batch
        $batchResponses[] = $bestFromThisBatch;

        // Optional: Freed memory if necessary
        unset($corpus, $embeddings, $batchResponse);

    }

    // STEP 4 - Second pass: pick the best of the best from all $batchResponses
    //    This “best” logic is up to you. You might want to run them all back
    //    through a ranking function or just pick the largest/smallest, etc.
    //
    // For demonstration, let’s do a naive approach:
    $finalBestResponse = '';
    foreach ($batchResponses as $candidate) {
        // Example logic: pick the candidate with the greatest length
        // (Replace with your actual “best” logic.)
        if (strlen($candidate) > strlen($finalBestResponse)) {
            $finalBestResponse = $candidate;
        }
    }

    // STEP 5 - Return the best overall response
    return $finalBestResponse;

}

// Function to fetch WordPress page and post content
function transformer_model_sentential_context_fetch_wordpress_content($content_offset_start = 0, $content_offset_end = 50) {

    global $wpdb;

    // DIAG - Diagnostic - Ver 2.2.0
    back_trace('NOTICE', 'transformer_model_sentential_context_fetch_wordpress_content');
    back_trace('NOTICE', 'Content Offset Start: ' . $content_offset_start);
    back_trace('NOTICE', 'Content Offset End: ' . $content_offset_end);

    // Query to get post and page content
    $safeStart = intval($content_offset_start);
    // e.g. if start=0 and end=49 => 50 items
    $safeEnd   = intval($content_offset_end) - $safeStart + 1;
    if ($safeEnd < 1) {
        $safeEnd = 50; // fallback or 10, up to you
    }

    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT post_content
               FROM {$wpdb->posts}
              WHERE post_status = %s
                AND (post_type = %s OR post_type = %s)
              LIMIT %d, %d",
            'publish',
            'post',
            'page',
            $safeStart,
            $safeEnd
        ),
        ARRAY_A
    );

    // Combine content
    $content = '';
    foreach ($results as $row) {
        $content .= ' ' . $row['post_content'];
    }

    // Clean up the content
    $content = strip_tags($content); // Remove HTML tags
    $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5); // Decode HTML entities

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
    // back_trace('NOTICE', 'transformer_model_sentential_context_build_cooccurrence_matrix');

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
                } else {
                    // Handle the case where the index does not exist
                    $contextWord = null; // or any default value
                }
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

function transformer_model_sentential_context_generate_contextual_response($input, $embeddings, $corpus, $maxTokens = 500) {

    global $chatbotFallbackResponses;

    // DIAG - Diagnostic - Ver 2.3.0
    back_trace('NOTICE', 'transformer_model_sentential_context_generate_contextual_response');
    back_trace('NOTICE', 'Max Tokens: ' . $maxTokens);

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

    // Similarity threshold default to 0.2
    $similarityThresholdDefault = '0.5';

    // Calculate key stats
    $highestSimilarity = max($similarities);
    $averageSimilarity = array_sum($similarities) / count($similarities);
    $matchesAboveThreshold = array_filter($similarities, function($similarity) use ($similarityThresholdDefault) {
        return $similarity > floatval(get_option('chatbot_transformer_model_similarity_threshold', $similarityThresholdDefault));
    });
    $numMatchesAboveThreshold = count($matchesAboveThreshold);
    $totalSentencesAnalyzed = count($sentences);

    // Log key stats
    back_trace('NOTICE', 'Key Stats:');
    back_trace('NOTICE', ' - Similarity Threshold: ' . get_option('chatbot_transformer_model_similarity_threshold', $similarityThresholdDefault));
    back_trace('NOTICE', ' - Highest Similarity: ' . $highestSimilarity);
    back_trace('NOTICE', ' - Average Similarity: ' . $averageSimilarity);
    back_trace('NOTICE', ' - Matches Above Threshold: ' . $numMatchesAboveThreshold);
    back_trace('NOTICE', ' - Total Sentences Analyzed: ' . $totalSentencesAnalyzed);

    // Add a similarity threshold
    $similarityThreshold = floatval(get_option('chatbot_transformer_model_similarity_threshold', $similarityThresholdDefault));

    // If the highest similarity is below the threshold, return a fallback message
    if ($highestSimilarity < $similarityThreshold) {
        back_trace('NOTICE', 'Low similarity detected: ' . $highestSimilarity);
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
    $sentenceBeforeRatio = 0.25;
    $tokenBeforeRatio = 0.75;

    // Add a total counter to ensure we don't exceed $maxSentences
    $totalSentencesUsed = 1; // the best match itself

    // Distribute sentences and tokens
    $sentencesBefore = floor($maxSentences * $sentenceBeforeRatio);
    $sentencesAfter = $maxSentences - $sentencesBefore;
    $tokensBefore = floor($maxTokens * $tokenBeforeRatio);
    $tokensAfter = $maxTokens - $tokensBefore;

    // DIAG - Diagnostic - Ver 2.2.1
    back_trace('NOTICE', '$maxSentences: ' . $maxSentences);
    back_trace('NOTICE', '$maxTokens: ' . $maxTokens);
    back_trace('NOTICE', '$sentencesBefore: ' . $sentencesBefore);
    back_trace('NOTICE', '$sentencesAfter: ' . $sentencesAfter);
    back_trace('NOTICE', '$tokensBefore: ' . $tokensBefore);
    back_trace('NOTICE', '$tokensAfter: ' . $tokensAfter);

    $responseWordCount = str_word_count($response);

    // Add sentences before the best match
    $tokensUsedBefore = 0;
    $sentencesUsedBefore = 0;
    for ($i = $bestMatchIndex - 1; $i >= 0 && $sentencesUsedBefore < $sentencesBefore && $tokensUsedBefore < $tokensBefore && $totalSentencesUsed < $maxSentences; $i--) {
        $previousSentence = trim($sentences[$i]);
        $sentenceWordCount = str_word_count($previousSentence);
        if ($tokensUsedBefore + $sentenceWordCount <= $tokensBefore) {
            $response = $previousSentence . ' ' . $response;
            $tokensUsedBefore += $sentenceWordCount;
            $sentencesUsedBefore++;
            $totalSentencesUsed++;
            if ($sentencesUsedBefore >= $sentencesBefore) {
                break;
            }
        } else {
            break;
        }
    }

    // Add sentences after the best match
    $tokensUsedAfter = 0;
    $sentencesUsedAfter = 0;
    for ($i = $bestMatchIndex + 1; $i < count($sentences) && $sentencesUsedAfter < $sentencesAfter && $tokensUsedAfter < $tokensAfter && $totalSentencesUsed < $maxSentences; $i++) {
        $nextSentence = trim($sentences[$i]);
        $sentenceWordCount = str_word_count($nextSentence);
        if ($tokensUsedAfter + $sentenceWordCount <= $tokensAfter) {
            $response .= ' ' . $nextSentence;
            $tokensUsedAfter += $sentenceWordCount;
            $sentencesUsedAfter++;
            $totalSentencesUsed++;
            if ($sentencesUsedAfter >= $sentencesAfter) {
                break;
            }
        } else {
            break;
        }
    }

    // Return the response
    return $response;

}
