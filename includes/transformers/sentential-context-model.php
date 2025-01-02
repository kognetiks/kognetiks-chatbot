<?php
/**
 * Kognetiks Chatbot for WordPress - Transformer Model - Sentential Context Model (SCM) - Ver 2.2.1
 *
 * This file contains the code for implementing an enhanced Transformer-like algorithm in PHP.
 *
 * 
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Transformer Model - Sentential Context Model (SCM)
function transformer_model_sentential_context_model_response( $input, $responseCount = 500 ) {

    // DIAG - Diagnostics - Ver 2.2.1
    // back_trace( 'NOTICE', 'transformer_model_sentential_context_model_sentential_context_response');
    // back_trace( 'NOTICE', 'Input: ' . $input);
    // back_trace( 'NOTICE', 'Response Count: ' . $responseCount);

    global $wpdb;

    // STEP 1 - Determine the number of batches
    $batchSize = 50;

    $totalItems = (int) $wpdb->get_var(
        "SELECT COUNT(*) 
         FROM {$wpdb->posts} 
         WHERE post_status = 'publish' 
           AND (post_type = 'post' OR post_type = 'page')"
    );

    // DIAG - Diagnostics - Ver 2.2.1
    // back_trace( 'NOTICE', 'Total published items: ' . $totalItems);

    // Temporarily set $batchSize = $totalItems
    // $batchSize = $totalItems;

    // back_trace( 'NOTICE', 'Batch size: ' . $batchSize);

    // Calculate the number of batches
    $numBatches = ceil($totalItems / $batchSize);

    // $numBatches = 1; // Temporarily set to 1
    // back_trace( 'NOTICE', 'Number of batches: ' . $numBatches);

    // DIAG - Diagnostics - Ver 2.2.1
    // back_trace( 'NOTICE', 'Number of batches: ' . $numBatches);

    // STEP 2 - Initialize array to hold the "best response" from each batch
    $batchResponses = [];

    // STEP 3 - Loop through the content in batches
    for ($start = 0; $start < $totalItems; $start += $batchSize) {

        // DIAG - Diagnostics - Ver 2.2.1
        // back_trace( 'NOTICE', sprintf('Processing batch offset %d', $start));
        
        // Calculate the offset end (e.g., 49 if start=0, 99 if start=50, etc.)
        $end = $start + $batchSize - 1;

        // DIAG - Diagnostics - Ver 2.2.1
        // back_trace( 'NOTICE', sprintf('Processing batch offset %d - %d', $start, $end));

        // STEP 3a - Fetch exactly 50 (or fewer if near the end) published items
        $corpus = transformer_model_sentential_context_fetch_wordpress_content($start, $end);
        
        // STEP 3b - (Re)build or reuse embeddings as needed; depends on your cache logic
        //     If you have a global or partial cache, you can slice it or re-generate it here.
        //     For demonstration, assume you have a function that fetches embeddings on the fly:
        $windowSize = intval(esc_attr(get_option('chatbot_transformer_model_word_content_window_size', 3)));

        // For big performance gains, you might want to keep an in-memory or file-based cache keyed by offsets
        // or by post IDs. This is just a placeholder:
        $embeddings = transformer_model_sentential_context_get_cached_embeddings($corpus, $windowSize);

        // back_trace( 'NOTICE', 'Embeddings keys for this batch: ' . print_r(array_keys($embeddings), true));
        // back_trace( 'NOTICE', 'Embeddings for this batch: ' . print_r($embeddings, true));

        // STEP 3c - Generate a response for this batch
        $batchResponse = transformer_model_sentential_context_generate_contextual_response( $input, $embeddings, $corpus, $responseCount, $windowSize );

        // DIAG - Diagnostics - Ver 2.2.1
        back_trace( 'NOTICE', 'Batch Response: ' . print_r($batchResponse, true));

        // STEP 3d - **Pick the "best" response** from this batch. 
        // In some cases, the generate_contextual_response might return a single best result.
        // If it returns multiple suggestions, pick the best among them here.

        // For example, if it returns an array
        // $bestFromThisBatch = pick_best_response($batchResponse);

        // For simplicity, assume it returns a single best string.  
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
    // $finalBestResponse = '';
    // foreach ($batchResponses as $candidate) {
    //     // Example logic: pick the candidate with the greatest length
    //     // (Replace with your actual “best” logic.)
    //     if (strlen($candidate) > strlen($finalBestResponse)) {
    //         $finalBestResponse = $candidate;
    //     }
    // }

    // DIAG - Diagnostics - Ver 2.2.1
    for ($i = 0; $i < count($batchResponses); $i++) {
        $cleanedSentence = preg_replace('/\s+/', ' ',  $batchResponses[$i]);
        // back_trace( 'NOTICE', 'Batch Response ' . $i . ': ' . $cleanedSentence);
    }

    // STEP 4 - Return the best overall response
    $finalBestResponse = '';
    // Assemble the $batchResponses as a $corpus
    $corpus = implode(' ', $batchResponses);
    // STEP 4a - Set the window size
    $windowSize = intval(esc_attr(get_option('chatbot_transformer_model_word_content_window_size', 3)));
    // STEP 4b - Retreive the file-based cache of embeddings
    $embeddings = transformer_model_sentential_context_get_cached_embeddings($corpus, $windowSize);
    // STEP 4c - Run the final best response through the generator one more time
    $finalBestResponse = transformer_model_sentential_context_generate_contextual_response( $input, $embeddings, $corpus, $responseCount, $windowSize );

    // STEP 5 - Return the best overall response
    return $finalBestResponse;

}

// Function to pick the best response from a batch
function pick_best_response($batchResponse) {

    // Assumes: $batchResponse is an array of responses with similarity scores
    // Example: $batchResponse = [['response' => '...', 'similarity' => 0.9], ...];

    if (empty($batchResponse)) {
        return null;
    }

    // Sort the responses by similarity score in descending order
    usort($batchResponse, function($a, $b) {
        return $b['similarity'] <=> $a['similarity'];
    });

    // Return the response with the highest similarity score
    return $batchResponse[0]['response'];

}

// Function to fetch WordPress page and post content
function transformer_model_sentential_context_fetch_wordpress_content($content_offset_start = 0, $content_offset_end = 50) {

    global $wpdb;

    // DIAG - Diagnostics - Ver 2.2.1
    // back_trace( 'NOTICE', 'transformer_model_sentential_context_fetch_wordpress_content');
    // back_trace( 'NOTICE', 'Content Offset Start: ' . $content_offset_start);
    // back_trace( 'NOTICE', 'Content Offset End: ' . $content_offset_end);

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

    // back_trace( 'NOTICE', 'Content length: ' . strlen($content));

    // Clean up the content
    $content = strip_tags($content); // Remove HTML tags
    $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5); // Decode HTML entities
    $content = preg_replace('/[^\w\s]/', '', $content); // Remove non-alphanumeric characters

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', 'Content: ' . $content);

    return $content;

}

// Function to build or retrieve cached embeddings
function transformer_model_sentential_context_get_cached_embeddings($corpus, $windowSize = 2) {

    // DIAG - Diagnostics - Ver 2.2.1
    // back_trace( 'NOTICE', 'transformer_model_sentential_context_get_cached_embeddings - start');

    $cacheFile = __DIR__ . '/sentential_embeddings_cache.php';

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', 'Cache File: ' . $cacheFile);

    // Check if embeddings are cached
    if (file_exists($cacheFile)) {

        $embeddings = include $cacheFile;

        // DIAG - Diagnostics
        // back_trace( 'NOTICE', 'Embeddings found in cache');

    } else {

        // DIAG - Diagnostics
        // back_trace( 'NOTICE', 'Embeddings not found in cache');

        update_option('chatbot_transformer_model_build_schedule', 'Now');
        chatbot_transformer_model_scheduler();

    }

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', 'transformer_model_sentential_context_get_cached_embeddings - end');

    return $embeddings;

}

// Function to build a co-occurrence matrix for word embeddings
function transformer_model_sentential_context_build_cooccurrence_matrix($corpus, $windowSize = 2) {

    // DIAG - Diagnostics - Ver 2.2.1
    // back_trace( 'NOTICE', 'transformer_model_sentential_context_build_cooccurrence_matrix - start');

    $matrix = [];

    // Before splitting into words:
    $corpus = preg_replace('/[^\w\s]/', '', $corpus);  // Remove punctuation

    // Tokenize and normalize
    $words = explode(' ', strtolower(trim($corpus)));
    $words = array_filter(array_map('trim', $words));
    $words = transformer_model_sentential_context_remove_stop_words($words); // Remove stop words

    // Generate n-grams
    $ngrams = [];
    for ($i = 0; $i <= count($words) - $windowSize; $i++) {
        $ngrams[] = implode(' ', array_slice($words, $i, $windowSize));
    }

    // DIAG - Diagnostics - Ver 2.2.1
    // back_trace('NOTICE', 'Generated Corpus N-Grams: ' . implode(', ', array_slice($ngrams, 0, 10)));

    foreach ($ngrams as $i => $ngram) {

        if (!isset($matrix[$ngram])) {
            $matrix[$ngram] = [];
        }
        
        for ($j = max(0, $i - $windowSize); $j <= min(count($ngrams) - 1, $i + $windowSize); $j++) {
            if ($i !== $j) {
                $contextNgram = $ngrams[$j];
                $matrix[$ngram][$contextNgram] = ($matrix[$ngram][$contextNgram] ?? 0) + 1;
            }
        }
    }

    // DIAG - Diagnostics - Ver 2.2.1
    // back_trace('NOTICE', 'Generated Embeddings: ' . print_r(array_slice($matrix, 0, 10), true));

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', 'transformer_model_sentential_context_build_cooccurrence_matrix - end');

    return $matrix;

}

// Function to remove stop words from an array of words
function transformer_model_sentential_context_remove_stop_words($words) {

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', 'transformer_model_sentential_context_remove_stop_words - start');

    // Use global stop words list
    global $stopWords;

    return array_diff($words, $stopWords);

}

// Function to calculate cosine similarity between two vectors
function transformer_model_sentential_context_cosine_similarity($vectorA, $vectorB) {

    // DIAG - Diagnostics - Ver 2.2.1
    // back_trace( 'NOTICE', 'transformer_model_sentential_context_cosine_similarity - start' );

    // Check for empty vectors
    if (empty($vectorA) || empty($vectorB)) {
        // if (empty($vectorA)) {
        //     back_trace( 'NOTICE', 'Empty Vector A');
        // }
        // if (empty($vectorB)) {
        //     back_trace( 'NOTICE', 'Empty Vector B');
        // }
        return 0;
    }

    $commonKeys = array_intersect_key($vectorA, $vectorB);

    if (empty($commonKeys)) {
        // back_trace( 'NOTICE', 'No common keys found');
        return 0;
    }

    $dotProduct = 0.0;
    $magnitudeA = 0.0;
    $magnitudeB = 0.0;

    // Compute dot product and magnitudes
    foreach ($commonKeys as $key => $value) {
        $dotProduct += $vectorA[$key] * $vectorB[$key];
    }

    $magnitudeA = sqrt(array_reduce($vectorA, fn($carry, $val) => $carry + $val * $val, 0.0));
    $magnitudeB = sqrt(array_reduce($vectorB, fn($carry, $val) => $carry + $val * $val, 0.0));

    // back_trace( 'NOTICE', '$vectorA: ' . print_r($vectorA, true));
    // back_trace( 'NOTICE', '$vectorB: ' . print_r($vectorB, true));
    // back_trace( 'NOTICE', 'Dot Product: ' . $dotProduct);
    // back_trace( 'NOTICE', 'Magnitude A: ' . $magnitudeA);
    // back_trace( 'NOTICE', 'Magnitude B: ' . $magnitudeB);

    return ($magnitudeA * $magnitudeB) ? $dotProduct / ($magnitudeA * $magnitudeB) : 0.0;

}

// Function to generate a contextual response based on input and embeddings
function transformer_model_sentential_context_generate_contextual_response($input, $embeddings, $corpus, $maxTokens = 500, $windowSize = 3) {

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', 'transformer_model_sentential_context_generate_contextual_response - start');
    // back_trace('NOTICE', 'Input: ' . $input);
    // back_trace('NOTICE', 'Embedding Keys: ' . implode(', ', array_slice(array_keys($embeddings), 0, 10)));
    // back_trace('NOTICE', 'Character Length $corpus: ' . strlen($corpus));
    // back_trace( 'NOTICE', 'Max Tokens: ' . $maxTokens);

    global $chatbotFallbackResponses;

    // Tokenize the corpus into sentences
    $sentences = preg_split('/(?<=[.?!])\s+/', $corpus);

    $sentenceVectors = [];

    // Compute embeddings for sentences
    foreach ($sentences as $index => $sentence) {

        // Remove punctuation from each sentence
        $sentence = preg_replace('/[^\w\s]/', '', $sentence);

        // Now split and normalize
        $sentenceWords = preg_split('/\s+/', strtolower(trim($sentence)));
        $sentenceWords = transformer_model_sentential_context_remove_stop_words($sentenceWords); // Remove stop words

        // DIAG - Diagnostics - Ver 2.2.1
        back_trace('NOTICE', 'Words for Sentence ' . $index . ': ' . implode(', ', $sentenceWords));

        $sentenceVector = [];
        $wordCount = 0;

        foreach ($sentenceWords as $word) {
            if (isset($embeddings[$word])) {
                foreach ($embeddings[$word] as $contextWord => $value) {
                    $sentenceVector[$contextWord] = ($sentenceVector[$contextWord] ?? 0) + $value;
                }
                $wordCount++;
            } else {
                // DIAG - Diagnostics - Ver 2.2.1
                // back_trace('NOTICE', 'Word not found in embeddings: ' . $word);
                $sentenceVector[$word] = 0; // Skip to the next word
            }
        }

        // Normalize the sentence vector
        if ($wordCount > 0) {
            foreach ($sentenceVector as $key => $value) {
                $sentenceVector[$key] /= $wordCount;
            }
        } else {
            // DIAG - Diagnostics - Ver 2.2.1
            // back_trace('NOTICE', 'Empty Sentence Vector for Sentence ' . $index);
        }
        
        $sentenceVectors[$index] = $sentenceVector;

    }

    // Compute the input vector
    $input = strtolower(trim($input));
    $inputWords = preg_split('/\s+/', preg_replace('/[^\w\s]/', '', $input));
    $inputWords = transformer_model_sentential_context_remove_stop_words($inputWords);
    // back_trace('NOTICE', 'Processed Input Words: ' . implode(', ', $inputWords));

    // DIAG - Diagnostics - Ver 2.2.1
    // back_trace('NOTICE', 'Input Words: ' . implode(', ', $inputWords));

    $inputNgrams = [];
    for ($i = 0; $i <= count($inputWords) - $windowSize; $i++) {
        $inputNgrams[] = implode(' ', array_slice($inputWords, $i, $windowSize));
    }

    // DIAG - Diagnostics - Ver 2.2.1
    // back_trace('NOTICE', 'Input N-Grams: ' . implode(', ', $inputNgrams));

    $inputVector = [];
    $wordCount = 0;

    foreach ($inputNgrams as $ngram) {
        if (isset($embeddings[$ngram])) {
            foreach ($embeddings[$ngram] as $contextWord => $value) {
                $inputVector[$contextWord] = ($inputVector[$contextWord] ?? 0) + $value;
            }
            $wordCount++;
        } else {
            // DIAG - Diagnostics - Ver 2.2.1
            // back_trace('NOTICE', 'N-Gram not found in embeddings: ' . $ngram);
        }
    }

    // Normalize the input vector
    if ($wordCount > 0) {
        foreach ($inputVector as $key => $value) {
            $inputVector[$key] /= $wordCount;
        }
    } else {
        // back_trace('NOTICE', 'Empty Input Vector');
    }
    
    // DIAG - Diagnostics - Ver 2.2.1
    // back_trace('NOTICE', 'Generated Input Vector: ' . print_r($inputVector, true));

    // Compute similarities
    $similarities = [];
    foreach ($sentenceVectors as $index => $vector) {

        if (empty($inputVector) || empty($vector)) {
            // DIAG - Diagnostics - Ver 2.2.1
            // back_trace('NOTICE', 'Empty Vector Detected for Sentence ' . $index);
            continue;
        }

        $similarity = transformer_model_sentential_context_cosine_similarity($inputVector, $vector);
        $similarities[$index] = $similarity;

        // DIAG - Diagnostics - Ver 2.2.1
        // back_trace('NOTICE', 'Similarity for Sentence ' . $index . ': ' . $similarity . ' | Sentence: ' . $sentences[$index]);

    }

    // Check for highest similarity
    if (empty($similarities)) {
        // DIAG - Diagnostics - Ver 2.2.1
        // back_trace('NOTICE', 'No similarities computed. Returning fallback.');
        return $chatbotFallbackResponses[array_rand($chatbotFallbackResponses)];
    }

    // Similarity threshold - Default to 0.2
    $similarityThreshold = floatval(get_option('chatbot_transformer_model_similarity_threshold', 0.2));

    // Calculate key stats
    $highestSimilarity = max($similarities);
    $averageSimilarity = array_sum($similarities) / count($similarities);

    $matchesAboveThreshold = array_filter($similarities, function($similarity) use ($similarityThreshold) {
        // back_trace( 'NOTICE', 'Similarity: ' . $similarity);
        // back_trace( 'NOTICE', 'Threshold: ' . $similarityThreshold);
        return $similarity > $similarityThreshold;
    });
    $numMatchesAboveThreshold = count($matchesAboveThreshold);
    $totalSentencesAnalyzed = count($sentences);

    // DIAG - Diagnostics - Ver 2.2.1
    // Print out each matching sentence and its similarity score
    // foreach ($matchesAboveThreshold as $index => $similarity) {
    //     $cleanedSentence = preg_replace('/\s+/', ' ', $sentences[$index]);
    //     // back_trace( 'NOTICE', 'Sentence: ' . $cleanedSentence );
    //     // back_trace( 'NOTICE', 'Similarity: ' . $similarity );
    // }

    // Log key stats
    // back_trace( 'NOTICE', 'Key Stats:');
    // back_trace( 'NOTICE', ' - Similarity Threshold: ' . $similarityThreshold);
    // back_trace( 'NOTICE', ' - Highest Similarity: ' . $highestSimilarity);
    // back_trace( 'NOTICE', ' - Average Similarity: ' . $averageSimilarity);
    // back_trace( 'NOTICE', ' - Matches Above Threshold: ' . $numMatchesAboveThreshold);
    // back_trace( 'NOTICE', ' - Total Sentences Analyzed: ' . $totalSentencesAnalyzed);

    // If the highest similarity is below the threshold, return a fallback message
    if ($highestSimilarity < $similarityThreshold) {
        // back_trace( 'NOTICE', 'Low similarity detected: ' . $highestSimilarity);
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
    $sentenceBeforeRatio = floatval(esc_attr(get_option('chatbot_transformer_model_leading_sentences_ratio', 0.25)));
    $tokenBeforeRatio = floatval(esc_attr(get_option('chatbot_transformer_model_leading_token_ratio', 0.25)));

    // Add a total counter to ensure we don't exceed $maxSentences
    $totalSentencesUsed = 1; // the best match itself

    // Distribute sentences and tokens
    $sentencesBefore = floor($maxSentences * $sentenceBeforeRatio);
    $sentencesAfter = $maxSentences - $sentencesBefore;
    $tokensBefore = floor($maxTokens * $tokenBeforeRatio);
    $tokensAfter = $maxTokens - $tokensBefore;

    // DIAG - Diagnostics - Ver 2.2.1
    // back_trace( 'NOTICE', '$maxSentences: ' . $maxSentences);
    // back_trace( 'NOTICE', '$maxTokens: ' . $maxTokens);
    // back_trace( 'NOTICE', '$sentencesBefore: ' . $sentencesBefore);
    // back_trace( 'NOTICE', '$sentencesAfter: ' . $sentencesAfter);
    // back_trace( 'NOTICE', '$tokensBefore: ' . $tokensBefore);
    // back_trace( 'NOTICE', '$tokensAfter: ' . $tokensAfter);

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
