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

    // Clean the input
    $input = transformer_model_sentential_context_clean($input);
    // DIAG - Diagnostics - Ver 2.2.1
    back_trace( 'NOTICE', 'Cleaned Input: ' . $input);

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
        $windowSize = intval(esc_attr(get_option('chatbot_transformer_model_word_content_window_size', 2)));

        // For big performance gains, you might want to keep an in-memory or file-based cache keyed by offsets
        // or by post IDs. This is just a placeholder:
        $embeddings = transformer_model_sentential_context_get_cached_embeddings($corpus, $windowSize);

        $normalizedEmbeddings = [];
        foreach ($embeddings as $key => $values) {
            $normalizedKey = strtolower(trim(preg_replace('/[^\w\s]/', '', $key)));
            $normalizedEmbeddings[$normalizedKey] = $values;
        }
        $embeddings = $normalizedEmbeddings;

        // back_trace( 'NOTICE', 'Embeddings keys for this batch: ' . print_r(array_keys($embeddings), true));
        // back_trace( 'NOTICE', 'Embeddings for this batch: ' . print_r($embeddings, true));

        // STEP 3c - Generate a response for this batch
        $batchResponse = transformer_model_sentential_context_generate_contextual_response( $input, $embeddings, $corpus, $responseCount, $windowSize );

        // DIAG - Diagnostics - Ver 2.2.1
        // back_trace( 'NOTICE', 'Batch Response: ' . print_r($batchResponse, true));

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
    //    To find the “best” response, let's run them all back through
    //    the ranking function one more time.

    // DIAG - Diagnostics - Ver 2.2.1
    // for ($i = 0; $i < count($batchResponses); $i++) {
    //     $cleanedSentence = preg_replace('/\s+/', ' ',  $batchResponses[$i]);
    //     // back_trace( 'NOTICE', 'Batch Response ' . $i . ': ' . $cleanedSentence);
    // }

    $finalBestResponse = '';
    // Assemble the $batchResponses as a $corpus
    $corpus = implode(' ', $batchResponses);
    // STEP 4a - Set the window size
    $windowSize = intval(esc_attr(get_option('chatbot_transformer_model_word_content_window_size', 2)));
    // STEP 4b - Retreive the file-based cache of embeddings
    $embeddings = transformer_model_sentential_context_get_cached_embeddings($corpus, $windowSize);
    // STEP 4c - Run the final best response through the generator one more time
    $finalBestResponse = transformer_model_sentential_context_generate_contextual_response( $input, $embeddings, $corpus, $responseCount, $windowSize );

    // Optional: Freed memory if necessary
    unset($corpus, $embeddings, $batchResponse);

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

    $content = transformer_model_sentential_context_clean($content);

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', 'Content: ' . $content);

    return $content;

}

// Function to build or retrieve cached embeddings by alphabet
function transformer_model_sentential_context_get_cached_embeddings($corpus, $windowSize = 2) {

    // Define the cache directory
    $cacheDir = __DIR__ . '/sentential_embeddings_cache/';

    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }

    // Initialize the embeddings array
    $embeddings = [];

    // Check if the cache files already exist
    $cacheFiles = glob($cacheDir . '*.php');

    if (!empty($cacheFiles)) {

        // Load all cache files
        foreach ($cacheFiles as $file) {
            $letter = basename($file, '.php');
            $embeddings[$letter] = include $file;
        }

        // DIAG - Diagnostics - Ver 2.2.1
        // back_trace('NOTICE', 'Embeddings loaded from cache files.');

    } else {

        // DIAG - Diagnostics - Ver 2.2.1
        // back_trace('NOTICE', 'Embeddings cache files not found. Building cache...');

        // Build embeddings dynamically
        $corpus = transformer_model_sentential_context_clean($corpus);
        $words = preg_split('/\s+/', strtolower(trim($corpus)));

        // Generate n-grams and assign meaningful context
        for ($i = 0; $i <= count($words) - $windowSize; $i++) {
            $ngram = implode(' ', array_slice($words, $i, $windowSize));

            // Assign n-gram to the corresponding letter file
            $firstChar = strtolower($ngram[0]);

            // Example: Use actual context data instead of 'dummy_context'
            if (!isset($embeddings[$firstChar][$ngram])) {
                $embeddings[$firstChar][$ngram] = generate_context_for_ngram($ngram, $corpus); // Replace this with your actual context generation logic
            }
        }

        // Save embeddings to cache files
        foreach ($embeddings as $letter => $letterEmbeddings) {

            $filePath = $cacheDir . $letter . '.php';
            file_put_contents($filePath, '<?php return ' . var_export($letterEmbeddings, true) . ';');
        }

        // DIAG - Diagnostics - Ver 2.2.1
        // back_trace('NOTICE', 'Embeddings cache files created.');

    }

    return $embeddings;

}

// Generate meaningful context for an n-gram
function generate_context_for_ngram($ngram, $corpus) {

    // Generate meaningful context for the n-gram
    // Example: Count occurrences of n-gram in the corpus
    return [
        'occurrence_count' => substr_count($corpus, $ngram),
    ];

}

// Function to build a co-occurrence matrix for word embeddings
function transformer_model_sentential_context_build_cooccurrence_matrix($corpus, $windowSize = 2) {

    // DIAG - Diagnostics - Ver 2.2.1
    // back_trace( 'NOTICE', 'transformer_model_sentential_context_build_cooccurrence_matrix - start');

    $matrix = [];

    // Clean up the corpus
    $corpus = transformer_model_sentential_context_clean($corpus);

    // Tokenize and normalize
    $corpus = $corpus ?? ''; // Ensure $corpus is a string
    $words = array_filter(array_map('trim', explode(' ', $corpus))); // Split into words and trim

    // Remove stop words
    $words = transformer_model_sentential_context_remove_stop_words($words); // Assuming this handles lowercased words

    // Generate n-grams
    $ngrams = [];
    for ($i = 0; $i <= count($words) - $windowSize; $i++) {
        $ngrams[] = implode(' ', array_slice($words, $i, $windowSize));
    }

    // DIAG - Diagnostics - Ver 2.2.1
    // back_trace( 'NOTICE', 'Generated Corpus N-Grams: ' . implode(', ', array_slice($ngrams, 0, 10)));

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
    // back_trace( 'NOTICE', 'Generated Embeddings: ' . print_r(array_slice($matrix, 0, 10), true));

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', 'transformer_model_sentential_context_build_cooccurrence_matrix - end');

    return $matrix;

}

// Function to remove stop words from an array of words
function transformer_model_sentential_context_remove_stop_words($words) {

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', 'transformer_model_sentential_context_remove_stop_words - start');

    // DIAG - Diagnostics - Ver 2.2.1
    // Temporarily return
    return $words;

    // Use global stop words list
    // global $stopWords;

    // return array_diff($words, $stopWords);

}

// Function to calculate cosine similarity between two vectors
function transformer_model_sentential_context_cosine_similarity($vectorA, $vectorB) {

    // DIAG - Diagnostics - Ver 2.2.1
    // back_trace( 'NOTICE', 'transformer_model_sentential_context_cosine_similarity - start');

    // Check for empty vectors
    if (empty($vectorA) || empty($vectorB)) {
        // if (empty($vectorA)) {
        //     // back_trace( 'NOTICE', 'Empty Vector A');
        // }
        // if (empty($vectorB)) {
        //     // back_trace( 'NOTICE', 'Empty Vector B');
        // }
        return 0;
    }

    // Either OPTION_1 or OPTION_2
    $similarity_option = get_option('chatbot_transformer_model_similarity_option', 'OPTION_1');

    if ($similarity_option === 'OPTION_1') {

        // Combine all keys from both vectors
        $allKeys = array_unique(array_merge(array_keys($vectorA), array_keys($vectorB)));

        $dotProduct = 0.0;
        $sumSquareA = 0.0;
        $sumSquareB = 0.0;

        foreach ($allKeys as $key) {
            $valueA = $vectorA[$key] ?? 0.0;
            $valueB = $vectorB[$key] ?? 0.0;

            $dotProduct += $valueA * $valueB;
            $sumSquareA += $valueA * $valueA;
            $sumSquareB += $valueB * $valueB;
        }

        $magnitudeA = sqrt($sumSquareA);
        $magnitudeB = sqrt($sumSquareB);

        return ($magnitudeA * $magnitudeB) ? $dotProduct / ($magnitudeA * $magnitudeB) : 0.0;

    } else {

        $commonKeys = array_intersect_key($vectorA, $vectorB);

        // Log the contents of vectorA and vectorB
        // back_trace( 'NOTICE', 'vectorA: ' . print_r($vectorA, true));
        // back_trace( 'NOTICE', 'vectorB: ' . print_r($vectorB, true));
        // back_trace( 'NOTICE', 'Keys of vectorA: ' . implode(', ', array_keys($vectorA)));
        // back_trace( 'NOTICE', 'Keys of vectorB: ' . implode(', ', array_keys($vectorB)));
        // back_trace( 'NOTICE', 'commonKeys: ' . print_r($commonKeys, true));

        if (empty($commonKeys)) {
            // back_trace( 'NOTICE', 'No common keys found');
            return 0;
        } else {
            // back_trace( 'NOTICE', 'Common keys found');
        }

        $dotProduct = 0.0;
        foreach ($commonKeys as $key => $value) {
            $dotProduct += $vectorA[$key] * $vectorB[$key];
        }

        $magnitudeA = sqrt(array_reduce($vectorA, fn($carry, $val) => $carry + $val * $val, 0.0));
        $magnitudeB = sqrt(array_reduce($vectorB, fn($carry, $val) => $carry + $val * $val, 0.0));

        // DIAG - Diagnostics - Ver 2.2.1
        // back_trace( 'NOTICE', 'Dot Product: ' . $dotProduct);
        // back_trace( 'NOTICE', 'Magnitude A: ' . $magnitudeA);
        // back_trace( 'NOTICE', 'Magnitude B: ' . $magnitudeB);

        return ($magnitudeA * $magnitudeB) ? $dotProduct / ($magnitudeA * $magnitudeB) : 0.0;

    }

}

// Function to clean up text
function transformer_model_sentential_context_clean($source_content) {

    // DIAG - Diagnostics - Ver 2.2.1
    // back_trace( 'NOTICE', 'transformer_model_sentential_context_model_clean - start');

    // Clean up the corpus
    // $source_content = preg_replace('/\R+/u', ' ', $source_content); // Normalize all line breaks to spaces
    // $source_content = preg_replace('/[^\P{C}\s]/u', '', $source_content); // Remove invisible Unicode control characters
    // $source_content = preg_replace('/\s+/', ' ', $source_content); // Collapse multiple spaces into one
    // $source_content = html_entity_decode($source_content, ENT_QUOTES | ENT_HTML5, 'UTF-8'); // Decode entities
    // $source_content = strtolower(trim($source_content)); // Normalize case and trim whitespace

    // Clean up the content
    
    // 1. Remove WordPress Gutenberg block comments
    $source_content = preg_replace('/<!--.*?-->/', '', $source_content); // Remove all HTML comments

    // 2. Remove HTML tags
    $source_content = strip_tags($source_content); // Remove all HTML tags

    // 3. Decode HTML entities
    $source_content = html_entity_decode($source_content, ENT_QUOTES | ENT_HTML5, 'UTF-8'); // Decode entities

    // 4. Normalize all line breaks and whitespace
    // Replace multiple newlines, carriage returns, and other whitespace variations with a single space
    $source_content = preg_replace('/\r\n|\r|\n|\t|\v|\f|\x{2028}|\x{2029}|&nbsp;/u', '. ', $source_content);

    // 5. Remove any remaining invisible Unicode characters
    // Matches all invisible Unicode characters, including non-breaking spaces
    $source_content = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}\x{00A0}]/u', '', $source_content);

    // 6. Remove non-alphanumeric characters, i.e., punctuation, if needed
    // $source_content = preg_replace('/[^\w\s]/u', '', $source_content);

    // 7. Collapse multiple spaces into a single space
    $source_content = preg_replace('/\s+/', ' ', $source_content);

    // 8. Trim leading and trailing whitespace
    $source_content = trim($source_content);

    // Debugging: Optional - Log remaining problematic characters
    // foreach (str_split($source_content) as $char) {
    //     if (ord($char) < 32 || ord($char) > 126) {
    //         // back_trace( 'DEBUG', 'Problematic char: "' . json_encode($char) . '" ASCII: ' . ord($char));
    //     }
    // }
    
    return $source_content;

}

// Function to generate a contextual response based on input and embeddings
function transformer_model_sentential_context_generate_contextual_response($input, $embeddings, $corpus, $maxTokens = 500, $windowSize = 3) {

    // DIAG - Diagnostics
    back_trace( 'NOTICE', 'transformer_model_sentential_context_generate_contextual_response');
    back_trace( 'NOTICE', '$input: ' . $input);
    // back_trace( 'NOTICE', '$embeddings: ' . $embeddings);
    if (empty($embeddings)) {
        back_trace( 'NOTICE', '$embeddings empty');
    } else {
        back_trace( 'NOTICE', '$embeddings Length: ' . count($embeddings));
    }
    // back_trace( 'NOTICE', '$corpus: ' . $corpus);
    if (empty($corpus)) {
        back_trace( 'NOTICE', '$orpus empty');
    } else {
        back_trace( 'NOTICE', '$corpus Length: ' . strlen($corpus));
    }
    back_trace( 'NOTICE', '$maxTokens: ' . $maxTokens);
    back_trace( 'NOTICE', '$windowSize: ' . $windowSize);

    global $chatbotFallbackResponses;


    // Embeddings cache
    // $cacheFile = __DIR__ . '/sentential_embeddings_cache.php';
    // back_trace( 'NOTICE', '$cacheFile: ' . $cacheFile);

    // Set this to point to the cache directory
    $cacheDir = __DIR__ . '/sentential_embeddings_cache/';
    
    // Tokenize the corpus into sentences while retaining punctuation
    $sentences = preg_split('/(?<=[.!?])\s+(?=[A-Z])/', $corpus);

    // Clean sentences individually
    foreach ($sentences as &$sentence) {
        $sentence = trim($sentence); // Trim leading and trailing whitespace
    }

    // Remove empty sentences
    $sentences = array_filter($sentences, function($sentence) {
        return !empty($sentence);
    });

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', 'Number of Sentences: ' . count($sentences));
    // back_trace( 'NOTICE', '$windowSize: ' . $windowSize);

    // Compute the input vector
    $input = strtolower(trim($input)); // Normalize case and trim whitespace
    $inputWords = preg_split('/\s+/', preg_replace('/[^\w\s]/', '', $input));
    $inputWords = transformer_model_sentential_context_remove_stop_words($inputWords); // Remove stop words

    // Log the processed input words
    back_trace( 'NOTICE', 'Processed Input Words: ' . implode(', ', $inputWords));

    $inputVector = [];
    $wordCount = 0;

    for ($i = 0; $i <= count($inputWords) - $windowSize; $i++) {

        $ngram = implode(' ', array_slice($inputWords, $i, $windowSize));
        // DIAG - Diagnostics
        back_trace( 'NOTICE', 'Input N-Gram: ' . $ngram);

        $ngramEmbeddings = transformer_model_lazy_load_embeddings($cacheDir, $ngram);

        if ($ngramEmbeddings) {

            foreach ($ngramEmbeddings as $contextWord => $value) {
                $inputVector[$contextWord] = ($inputVector[$contextWord] ?? 0) + $value;
            }
            $wordCount++;

        } else {

            // Log that we didn't find an embedding for $ngram
            // back_trace( 'NOTICE', 'Input N-Gram not found in embeddings: ' . $ngram);

        }

    }

    // Normalize the input vector
    if ($wordCount > 0) {

        foreach ($inputVector as $key => $value) {
            $inputVector[$key] /= $wordCount;
        }

    } else {

        $inputVector = [];
        back_trace( 'NOTICE', 'Empty Input Vector');

    }

    // Limit vector size to reduce memory usage
    $inputVector = array_slice($inputVector, 0, 100, true);

    // DIAG - Diagnostics
    back_trace( 'NOTICE', 'Generated Input Vector: ' . print_r(array_slice($inputVector, 0, 10), true)); // Log partial vector for debugging

    // Process sentences in batches
    $highestSimilarity = -INF;
    $bestMatchIndex = -1;

    // FIXME - Temporary increase the maximum execution time
    set_time_limit(300); // Increase the maximum execution time to 300 seconds

    $batchSize = 1000; // Process sentences in smaller batches
    for ($batchStart = 0; $batchStart < count($sentences); $batchStart += $batchSize) {
        $sentenceBatch = array_slice($sentences, $batchStart, $batchSize);

        foreach ($sentenceBatch as $index => $sentence) {
            $sentence = transformer_model_sentential_context_clean($sentence);
            $sentenceWords = preg_split('/\s+/', strtolower(trim($sentence)));
            $sentenceWords = transformer_model_sentential_context_remove_stop_words($sentenceWords);

            $sentenceVector = [];
            $ngramCount = 0;

            for ($i = 0; $i <= count($sentenceWords) - $windowSize; $i++) {
                $ngram = implode(' ', array_slice($sentenceWords, $i, $windowSize));
                $ngramEmbeddings = transformer_model_lazy_load_embeddings($cacheDir, $ngram);
                if ($ngramEmbeddings) {
                    foreach ($ngramEmbeddings as $contextWord => $value) {
                        $sentenceVector[$contextWord] = ($sentenceVector[$contextWord] ?? 0) + $value;
                    }
                    $ngramCount++;
                }
            }

            if ($ngramCount > 0) {
                foreach ($sentenceVector as $k => $val) {
                    $sentenceVector[$k] /= $ngramCount;
                }
            }

            // Limit vector size to reduce memory usage
            // $sentenceVector = array_slice($sentenceVector, 0, 100, true);

            if (empty($inputVector) || empty($sentenceVector)) {
                continue;
            }

            $similarity = transformer_model_sentential_context_cosine_similarity($inputVector, $sentenceVector);

            if ($similarity > $highestSimilarity) {
                $highestSimilarity = $similarity;
                $bestMatchIndex = $batchStart + $index;
            }
        }

        // Log memory usage after each batch
        back_trace( 'NOTICE', 'Memory usage after batch ' . $batchStart . ': ' . memory_get_usage(true));

    }

    if ($highestSimilarity === -INF) {
        // back_trace( 'NOTICE', 'No similarities computed. Returning fallback.');
        return $chatbotFallbackResponses[array_rand($chatbotFallbackResponses)];
    }

    $similarityThreshold = floatval(get_option('chatbot_transformer_model_similarity_threshold', 0.2));
    // back_trace( 'NOTICE', 'Highest Similarity: ' . $highestSimilarity);

    if ($highestSimilarity < $similarityThreshold) {
        // back_trace( 'NOTICE', 'Low similarity detected: ' . $highestSimilarity);
        return $chatbotFallbackResponses[array_rand($chatbotFallbackResponses)];
    }

    $response = trim($sentences[$bestMatchIndex]);

    // Add surrounding sentences
    $maxSentences = intval(get_option('chatbot_transformer_model_sentence_response_length', 5));
    $tokensBefore = floor($maxTokens * 0.25);
    $tokensAfter = $maxTokens - $tokensBefore;

    $sentencesBefore = floor($maxSentences * 0.25);
    $sentencesAfter = $maxSentences - $sentencesBefore;

    for ($i = $bestMatchIndex - 1, $count = 0; $i >= 0 && $count < $sentencesBefore; $i--, $count++) {
        $response = trim($sentences[$i]) . ' ' . $response;
    }

    for ($i = $bestMatchIndex + 1, $count = 0; $i < count($sentences) && $count < $sentencesAfter; $i++, $count++) {
        $response .= ' ' . trim($sentences[$i]);
    }

    $response = preg_replace('/\s+/', ' ', $response);

    return $response;

}

// Lazy load embeddings for a specific n-gram from the cache file
function transformer_model_lazy_load_embeddings($cacheDir, $ngram) {

    // Validate that the cache directory is a string
    if (!is_string($cacheDir)) {
        back_trace('ERROR', 'Cache directory is not a string: ' . print_r($cacheDir, true));
        return null;
    }

    // Determine the cache file based on the first character of the n-gram
    $firstChar = strtolower($ngram[0]);
    $cacheFile = rtrim($cacheDir, '/') . '/' . $firstChar . '.php';

    // Check if the cache file exists
    if (!file_exists($cacheFile)) {
        // back_trace('NOTICE', "Cache file not found for n-gram: $ngram | Expected file: $cacheFile");
        return null; // Return early if the cache file doesn't exist
    }

    // Load the cache file
    $embeddings = include $cacheFile;

    // Check if the n-gram exists in the cache
    if (!isset($embeddings[$ngram])) {
        // back_trace('NOTICE', "N-Gram not found in cache: $ngram | Cache file: $cacheFile");
        return null; // Return early if the n-gram isn't found
    }

    // Log success
    // back_trace('NOTICE', "N-Gram found in cache: $ngram | Cache file: $cacheFile");

    // Return the n-gram's embedding
    return $embeddings[$ngram];

}

