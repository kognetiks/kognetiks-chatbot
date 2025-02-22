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

// Lexical Context Model (LCM) - Transformer Model - Ver 2.2.6
function transformer_model_lexical_context_response( $prompt, $max_tokens = null) {

    global $wpdb;

    // Use global stop words list
    global $stopWords;
    
    // Preprocess text (convert to lowercase, remove special characters, stop words)
    function preprocess_text($text) {

        global $stopWords;

        // Convert to lowercase
        $text = strtolower(strip_tags($text));
        // Remove punctuation and special characters
        $text = preg_replace('/[^a-z0-9 ]/', '', $text);
        // Remove extra spaces
        $text = preg_replace('/\s+/', ' ', $text);
        // Trim leading and trailing spaces
        $text = trim($text);
        // Tokenize text
        $words = explode(' ', $text);
        // Remove stop words
        $filtered_words = array_diff($words, $stopWords);

        // Return preprocessed text
        return implode(' ', $filtered_words);

    }
    
    $prompt = preprocess_text($prompt);
    $highest_score = 0;
    $best_match = "";
    $batch_size = 50;
    $offset = 0;
    
    do {
        // Fetch a batch of posts, pages, and products
        $query = $wpdb->prepare(
            "SELECT post_title, post_content FROM {$wpdb->posts} 
            WHERE post_status = 'publish' 
            AND (post_type = 'post' OR post_type = 'page' OR post_type = 'product')
            LIMIT %d OFFSET %d", $batch_size, $offset
        );
        $results = $wpdb->get_results($query);
        
        if (empty($results)) {
            break;
        }
        
        foreach ($results as $post) {

            $content = preprocess_text($post->post_content);
            
            // Skip if content is empty
            if (empty($content)) {
                continue;
            }

            // Remove punctuation and special characters
            $content = preg_replace('/[^a-z0-9 ]/', '', $content);
            // Remove extra spaces
            $content = preg_replace('/\s+/', ' ', $content);
            // Trim leading and trailing spaces
            $content = trim($content);
            // Remove stop words
            $content_words = explode(' ', $content);
            $content_words = array_diff($content_words, $stopWords);

            // Calculate similarity using word intersection
            $prompt_words = explode(' ', $prompt);
            $intersection = array_intersect($content_words, $prompt_words);
            $score = count($intersection) / max(1, count($prompt_words));
            
            if ($score > $highest_score) {

                $highest_score = $score;
            
                // Get the best match content
                // $best_match = $post->post_content;
            
                // Get 1-3 sentences around the matching words
                $best_match = "";
                $match_index = array_search(key($intersection), $content_words);
            
                // Split content into sentences
                $sentences = preg_split('/(?<=[.!?])\s+/', $post->post_content);
                $sentence_count = count($sentences);
            
                // Find the sentence containing the match
                $sentence_index = 0;
                foreach ($sentences as $index => $sentence) {
                    if (strpos($sentence, $content_words[$match_index]) !== false) {
                        $sentence_index = $index;
                        break;
                    }
                }
            
                // Get store number of sentences around the matching sentence
                $sentence_response_length = esc_attr(get_option('chatbot_transformer_model_sentence_response_length', 3));
                back_trace('NOTICE', 'Sentence Response Length: ' . $sentence_response_length);
                $start_index = max(0, $sentence_index - 1);
                $end_index = min($sentence_count - 1, $sentence_index + $sentence_response_length - 1);
            
                // Ensure the number of sentences does not exceed the stored value
                $actual_response_length = min($sentence_response_length, $end_index - $start_index + 1);
                $end_index = $start_index + $actual_response_length - 1;
            
                for ($i = $start_index; $i <= $end_index; $i++) {
                    $best_match .= $sentences[$i] . ' ';
                }
                
            }

        }
        
        $offset += $batch_size;

    } while (!empty($results));
    
    // return $highest_score > 0 ? substr($best_match, 0, $max_tokens) . '...' : "I couldn't find relevant content for your query.";
    return $highest_score > 0 ? $best_match : "I couldn't find relevant content for your query.";

}

// Main function to generate a response
function transformer_model_lexical_context_response_old( $input, $max_tokens = null ) {

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