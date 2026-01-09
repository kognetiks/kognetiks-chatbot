<?php
/**
 * Kognetiks Chatbot for WordPress - Transformer Model - Sentential Context Model (SCM) - Ver 2.2.1
 *
 * This file contains the code for implementing an enhanced Transformer-like algorithm in PHP.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Sentential Context Model (SCM) - Transformer Model - Ver 2.2.6
function transformer_model_sentential_context_model_response_lite($prompt, $max_tokens = null) {

    global $wpdb;
    global $stopWords;

    function preprocess_text($text) {

        global $stopWords;

        $text = strtolower(strip_tags($text));
        $text = preg_replace('/[^a-z0-9 ]/', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        $words = explode(' ', $text);
        $filtered_words = array_diff($words, $stopWords);
        return implode(' ', $filtered_words);

    }

    function get_min_window_length($content_words, $prompt_words) {

        $required = array_count_values($prompt_words);
        $required_count = count($required);
        $formed = [];
        $formed_count = 0;
        $min_window_length = PHP_INT_MAX;
        $left = 0;
        $n = count($content_words);
    
        for ($right = 0; $right < $n; $right++) {
            $word = $content_words[$right];
            if (isset($required[$word])) {
                if (!isset($formed[$word])) {
                    $formed[$word] = 0;
                }
                $formed[$word]++;
                if ($formed[$word] == $required[$word]) {
                    $formed_count++;
                }
            }
    
            while ($formed_count === $required_count && $left <= $right) {
                $current_window_length = $right - $left + 1;
                if ($current_window_length < $min_window_length) {
                    $min_window_length = $current_window_length;
                }
    
                $left_word = $content_words[$left];
                if (isset($required[$left_word])) {
                    $formed[$left_word]--;
                    if ($formed[$left_word] < $required[$left_word]) {
                        $formed_count--;
                    }
                }
                $left++;
            }
        }
    
        return $min_window_length === PHP_INT_MAX ? null : $min_window_length;

    }

    $prompt = preprocess_text($prompt);
    // DIAG - Diagnostic - Ver 2.2.6

    $highest_score = 0;
    $best_match = "";
    $batch_size = 50;
    $offset = 0;

    do {

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

        // Find the max frequency in this batch to normalize frequency scores
        $max_frequency = 1;
        // Get the similarity threshold for the prompt
        $similarity_threshold = esc_attr(get_option('chatbot_transformer_model_similarity_threshold', 0.7));

        // Weight adjacency score higher (e.g., 70%) and frequency lower (30%)
        $adjacency_score_threshold = $similarity_threshold;
        $normalization_frequency_threshold = 1.0 - $adjacency_score_threshold;

        foreach ($results as $post) {

            $content = preprocess_text($post->post_content);
            if (empty($content)) continue;

            $content_words = explode(' ', $content);
            $word_frequencies = array_count_values($content_words);
            $prompt_words = explode(' ', $prompt);
            $intersection = array_intersect($content_words, $prompt_words);
            
            $frequency_score = 0;
            foreach ($intersection as $word) {
                $frequency_score += $word_frequencies[$word];
            }

            if ($frequency_score > $max_frequency) {
                $max_frequency = $frequency_score;
            }

        }

        foreach ($results as $post) {

            $content = preprocess_text($post->post_content);
            if (empty($content)) continue;

            $content_words = explode(' ', $content);
            $word_frequencies = array_count_values($content_words);
            $prompt_words = explode(' ', $prompt);
            $intersection = array_intersect($content_words, $prompt_words);
            
            $frequency_score = 0;
            foreach ($intersection as $word) {
                $frequency_score += $word_frequencies[$word];
            }

            // Normalize frequency score (0-100)
            $normalized_frequency = ($frequency_score / $max_frequency) * 100;

            // Compute adjacency score using the minimum window method
            $min_window = get_min_window_length($content_words, $prompt_words);
            if ($min_window !== null) {
                $best_possible = count($prompt_words);
                $worst_possible = count($content_words);
                $normalized_distance = ($min_window - $best_possible) / max(1, ($worst_possible - $best_possible));
                $adjacency_score = 100 * (1 - $normalized_distance);
                $adjacency_score = max(0, min(100, $adjacency_score));
            } else {
                $adjacency_score = 0;
            }

            // Weight adjacency score higher (e.g., 70%) and frequency lower (30%)
            $combined_score = ($adjacency_score_threshold * $adjacency_score) + ($normalization_frequency_threshold * $normalized_frequency);

            if ($combined_score > $highest_score) {
                $highest_score = $combined_score;
                $best_match = "";

                if (!empty($intersection)) {
                    $matched_word = reset($intersection);
                    $match_index = array_search($matched_word, $content_words);
                    $sentences = preg_split('/(?<=[.!?])\s+/', $post->post_content);
                    $sentence_count = count($sentences);
                    $sentence_index = 0;

                    foreach ($sentences as $index => $sentence) {
                        if (stripos($sentence, $matched_word) !== false) {
                            $sentence_index = $index;
                            break;
                        }
                    }

                    $sentence_response_length = esc_attr(get_option('chatbot_transformer_model_sentence_response_length', 3));
                    $leading_sentence_ratio = esc_attr(get_option('chatbot_transformer_model_leading_sentences_ratio', 0.5));
                    $sentences_before = max(0, $sentence_index - round($leading_sentence_ratio * $sentence_response_length));
                    $start_index = max(0, $sentences_before);
                    $end_index = min($sentence_count - 1, $start_index + $sentence_response_length - 1);
                    $actual_response_length = min($sentence_response_length, $end_index - $start_index + 1);
                    $end_index = $start_index + $actual_response_length - 1;

                    for ($i = $start_index; $i <= $end_index; $i++) {
                        $best_match .= $sentences[$i] . ' ';
                    }


                }
            }
        }

        $offset += $batch_size;

    } while (!empty($results));

    global $no_matching_content_response;
    
    return $highest_score > 0 ? $best_match : $no_matching_content_response[array_rand($no_matching_content_response)];

}

// Sentential Context Model (SCM) - Transformer Model - Ver 2.2.6
function transformer_model_sentential_context_model_response_lite_version_two($prompt, $max_tokens = null) {
    global $wpdb;
    global $stopWords;

    // Preprocess text: convert to lowercase, strip HTML tags, remove punctuation/extra spaces, and filter out stop words.
    function preprocess_text($text) {
        global $stopWords;
        $text = strtolower(strip_tags($text));
        $text = preg_replace('/[^a-z0-9 ]/', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        $words = explode(' ', $text);
        $filtered_words = array_diff($words, $stopWords);
        return implode(' ', $filtered_words);
    }

    // Helper function: find the minimum window length in $content_words that contains all $prompt_words.
    function get_min_window_length($content_words, $prompt_words) {
        $required = array_count_values($prompt_words);
        $required_count = count($required);
        $formed = [];
        $formed_count = 0;
        $min_window_length = PHP_INT_MAX;
        $left = 0;
        $n = count($content_words);
    
        for ($right = 0; $right < $n; $right++) {
            $word = $content_words[$right];
            if (isset($required[$word])) {
                if (!isset($formed[$word])) {
                    $formed[$word] = 0;
                }
                $formed[$word]++;
                if ($formed[$word] == $required[$word]) {
                    $formed_count++;
                }
            }
    
            while ($formed_count === $required_count && $left <= $right) {
                $current_window_length = $right - $left + 1;
                if ($current_window_length < $min_window_length) {
                    $min_window_length = $current_window_length;
                }
    
                $left_word = $content_words[$left];
                if (isset($required[$left_word])) {
                    $formed[$left_word]--;
                    if ($formed[$left_word] < $required[$left_word]) {
                        $formed_count--;
                    }
                }
                $left++;
            }
        }
    
        return $min_window_length === PHP_INT_MAX ? null : $min_window_length;
    }

    // Preprocess the prompt.
    $prompt = preprocess_text($prompt);

    // Initialize variables.
    $highest_score = 0;
    $best_match = "";
    $batch_size = 50;
    $offset = 0;

    do {
        // Fetch a batch of posts, pages, and products.
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
    
            // Skip if content is empty.
            if (empty($content)) {
                continue;
            }
    
            // Tokenize content.
            $content_words = explode(' ', $content);
    
            // Calculate word frequencies in the content.
            $word_frequencies = array_count_values($content_words);
    
            // Calculate similarity using word intersection and frequency.
            $prompt_words = explode(' ', $prompt);
            $intersection = array_intersect($content_words, $prompt_words);
            $frequency_score = 0;
    
            foreach ($intersection as $word) {
                $frequency_score += $word_frequencies[$word];
            }
    
            // Frequency score normalized: fraction of prompt words (scaled to 0-100 later).
            $score = $frequency_score / max(1, count($prompt_words));
    
            // Calculate adjacency score using the minimum window approach.
            $min_window = get_min_window_length($content_words, $prompt_words);
    
            if ($min_window !== null) {
                // Best-case: window equals count($prompt_words); worst-case: window equals total content words.
                $best_possible = count($prompt_words);
                $worst_possible = count($content_words);
                $normalized_distance = ($min_window - $best_possible) / max(1, ($worst_possible - $best_possible));
                $adjacency_score = 100 * (1 - $normalized_distance);
                $adjacency_score = max(0, min(100, $adjacency_score));
            } else {
                $adjacency_score = 0;
            }
    
            // Normalize the frequency score to 0-100.
            $normalized_frequency = $score * 100;
    
            // Combine frequency and adjacency scores.
            $combined_score = ($normalized_frequency + $adjacency_score) / 2;
    
            // Update best match if this content has a higher combined score.
            if ($combined_score > $highest_score) {
                $highest_score = $combined_score;
                $best_match = "";
    
                if (!empty($intersection)) {
                    $matched_word = reset($intersection);
                    $match_index = array_search($matched_word, $content_words);
    
                    // Split the original post content into sentences.
                    $sentences = preg_split('/(?<=[.!?])\s+/', $post->post_content);
                    $sentence_count = count($sentences);
    
                    // Find the sentence containing the matched word.
                    $sentence_index = 0;
                    foreach ($sentences as $index => $sentence) {
                        if (stripos($sentence, $matched_word) !== false) {
                            $sentence_index = $index;
                            break;
                        }
                    }
    
                    // Determine the number of sentences to include in the result.
                    $sentence_response_length = esc_attr(get_option('chatbot_transformer_model_sentence_response_length', 3));
                    $start_index = max(0, $sentence_index - 1);
                    $end_index = min($sentence_count - 1, $sentence_index + $sentence_response_length - 1);
                    $actual_response_length = min($sentence_response_length, $end_index - $start_index + 1);
                    $end_index = $start_index + $actual_response_length - 1;
    
                    for ($i = $start_index; $i <= $end_index; $i++) {
                        $best_match .= $sentences[$i] . ' ';
                    }
    
                }
            }
        }
    
        $offset += $batch_size;
    } while (!empty($results));
    
    return $highest_score > 0 ? $best_match : "I couldn't find relevant content for your query.";

}

// Sentential Context Model (SCM) - Transformer Model - Ver 2.2.6
function transformer_model_sentential_context_model_response_lite_version_one($prompt, $max_tokens = null) {

    global $wpdb;
    global $stopWords;

    // Preprocess text (convert to lowercase, remove special characters, stop words)
    function preprocess_text($text) {

        global $stopWords;
        // Convert to lowercase and strip HTML tags
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

    // Reset variables
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

            // Tokenize content
            $content_words = explode(' ', $content);

            // Calculate word frequencies in the content
            $word_frequencies = array_count_values($content_words);

            // Calculate similarity using word intersection and frequency
            $prompt_words = explode(' ', $prompt);
            $intersection = array_intersect($content_words, $prompt_words);
            $frequency_score = 0;

            foreach ($intersection as $word) {

                $frequency_score += $word_frequencies[$word];

            }

            $scoring_algorithm = 'pairwise'; // Set to 'average' or 'pairwise'
            // DIAG - Diagnostics

            if ( $scoring_algorithm == 'average' ) { // ORIGINAL

                $score = $frequency_score / max(1, count($prompt_words));

                // Calculate adjacency score
                $positions = [];

                foreach ($prompt_words as $word) {

                    $pos = array_keys($content_words, $word);
                    if (!empty($pos)) {
                        $positions = array_merge($positions, $pos);
                    }

                }

                if (count($positions) > 1) {

                    sort($positions);
                    $total_distance = 0;
                    for ($i = 1; $i < count($positions); $i++) {
                        $total_distance += $positions[$i] - $positions[$i - 1];
                    }
                    $average_distance = $total_distance / (count($positions) - 1);
                    $max_possible_distance = count($content_words) - 1;
                    $adjacency_score = 100 * (1 - ($average_distance / $max_possible_distance));

                } else {

                    $adjacency_score = 0;

                }

            } elseif ( $scoring_algorithm == 'pairwise' ) {

                // Calculate frequency score (already computed elsewhere)
                $score = $frequency_score / max(1, count($prompt_words));

                // Calculate adjacency score using the minimum distance between any two prompt words.
                $positions = [];
                foreach ($prompt_words as $word) {
                    $pos = array_keys($content_words, $word);
                    if (!empty($pos)) {
                        $positions = array_merge($positions, $pos);
                    }
                }

                if (count($positions) > 1) {
                    sort($positions);
                    $min_distance = PHP_INT_MAX;
                    for ($i = 1; $i < count($positions); $i++) {
                        $distance = $positions[$i] - $positions[$i - 1];
                        if ($distance < $min_distance) {
                            $min_distance = $distance;
                        }
                    }
                    $max_possible_distance = count($content_words) - 1;
                    $adjacency_score = 100 * (1 - ($min_distance / $max_possible_distance));
                } else {
                    $adjacency_score = 0;
                }

            } else {

                return ("Error: Invalid scoring algorithm: " . $scoring_algorithm);

            }

            // Combine the intersection score and adjacency score
            $combined_score = ($score + $adjacency_score) / 2;

            // Update highest score and best match
            if ($combined_score > $highest_score) {

                $highest_score = $combined_score;

                // Get the best match content
                $best_match = "";

                if (!empty($intersection)) {

                    $match_index = array_search(reset($intersection), $content_words);

                    // Split content into sentences
                    $sentences = preg_split('/(?<=[.!?])\s+/', $post->post_content);
                    $sentence_count = count($sentences);

                    // Find the sentence containing the match
                    $sentence_index = 0;
                    foreach ($sentences as $index => $sentence) {

                        if (stripos($sentence, reset($intersection)) !== false) {
                            $sentence_index = $index;
                            break;
                        }

                    }

                    // Get store number of sentences around the matching sentence
                    $sentence_response_length = esc_attr(get_option('chatbot_transformer_model_sentence_response_length', 3));
                    // DIAG - Diagnostics
                    $start_index = max(0, $sentence_index - 1);
                    $end_index = min($sentence_count - 1, $sentence_index + $sentence_response_length - 1);

                    // Ensure the number of sentences does not exceed the stored value
                    $actual_response_length = min($sentence_response_length, $end_index - $start_index + 1);
                    $end_index = $start_index + $actual_response_length - 1;

                    for ($i = $start_index; $i <= $end_index; $i++) {

                        $best_match .= $sentences[$i] . ' ';

                    }

                    // DIAG - Diagnostics

                }
            }
        }

        $offset += $batch_size;

    } while (!empty($results));

    return $highest_score > 0 ? $best_match : "I couldn't find relevant content for your query.";

}

// Sentential Context Model (SCM) - Transformer Model - Ver 2.2.1
function transformer_model_sentential_context_model_response($input, $responseCount = 500) {

    // DIAG - Diagnostic - Ver 2.2.1

    // Normalize the input string - Ver 2.2.2
    if (class_exists('Normalizer')) {
        $input = Normalizer::normalize($input, Normalizer::FORM_C);
    }

    // MOVED TO transformer-model-scheduler.php
    // Fetch WordPress content
    $corpus = transformer_model_sentential_context_fetch_wordpress_content( $input );

    // Set the window size for co-occurrence matrix
    $windowSize = intval(esc_attr(get_option('chatbot_transformer_model_word_content_window_size', 3)));
    // DIAG - Diagnostic - Ver 2.2.1

    // DIAG - Diagnostic - Ver 2.2.1

    // MOVED TO transformer-model-scheduler.php
    // Build embeddings (with caching for performance)
    $embeddings = transformer_model_sentential_context_get_cached_embeddings($corpus, $windowSize);

    // Generate contextual response
    $response = transformer_model_sentential_context_generate_contextual_response($input, $embeddings, $corpus, $responseCount);

    return $response;

}

// Function to fetch WordPress page and post content
function transformer_model_sentential_context_fetch_wordpress_content($input = null) {

    // DIAG - Diagnostic - Ver 2.2.1

    global $wpdb;
    global $no_matching_content_response;

    // Only fetch content with words from the input
    if (empty($input)) {
        return '';
    }
    // DIAG - Diagnostics - Ver 2.2.1

    // Step 1 - Normalize and remove stop words
    // $input = preg_replace('/[^\w\s]/', '', $input);
    $input = preg_replace('/[^\p{L}\s]/u', '', $input); // Ver 2.2.2
    // $words = array_filter(array_map('trim', explode(' ', strtolower($input))));
    $words = array_filter(array_map('trim', explode(' ', mb_strtolower($input, 'UTF-8')))); // Ver 2.2.2
    $words = transformer_model_sentential_context_remove_stop_words($words);

    // FIXME - OVERRIDE - Ver 2.2.4
    $all_words = $words;

    // DIAG - Diagnostic - Ver 2.2.4

    // Step 2 - Query the TF-IDF table for the highest-scoring words
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_knowledge_base_tfidf';
    $limit = intval(esc_attr(get_option('chatbot_transformer_model_word_content_window_size', 3)));

    $results = [];
    if (!empty($words)) {
        $placeholders = implode(',', array_fill(0, count($words), '%s'));
        $query = $wpdb->prepare(
            "SELECT word, score FROM $table_name WHERE word IN ($placeholders) ORDER BY score DESC",
            $words
        );
        $rows = $wpdb->get_results($query);

        if ($wpdb->last_error) {
            prod_trace( 'ERROR', 'WordPress database error: ' . $wpdb->last_error);
        } elseif (!empty($rows)) {
            foreach ($rows as $row) {
                $results[] = ['word' => $row->word, 'score' => $row->score];
            }
        }
    }

    // Step 3 - Supplement results with remaining words, longest first
    usort($words, function($a, $b) {
        return strlen($b) <=> strlen($a);
    });

    $existing_words = array_column($results, 'word');
    $remaining_words = array_diff($words, $existing_words);

    // DIAG - Diagnostic - Ver 2.2.1 - Print the words and scores
    // for ($i = 0; $i < count($results); $i++) {
    // }

    // Ensure results meet the limit
    if (count($results) > $limit) {
        $results = array_slice($results, 0, $limit);
    }

    // foreach ($remaining_words as $word) {
    //     if (count($results) >= $limit) {
    //         break;
    //     }
    //     $results[] = ['word' => $word, 'score' => 0];
    // }

    $results = array_merge($results, array_map(function($word) {
        return ['word' => $word, 'score' => 0];
    }, $remaining_words));

    // DIAG - Diagnostic - Ver 2.2.1 - Print the words and scores
    // for ($i = 0; $i < count($results); $i++) {
    // }

    // Define the window size
    $window_size = get_option('chatbot_transformer_model_word_content_windows_size', 3); // Default to 3 if not set

    // Step 4 - Build the LIKE condition
    $final_words = array_column($results, 'word');
    $like_conditions = [];

    $final_words = $all_words;

    // // ORIGINAL APPROACH
    // Use a sliding window to group words
    // Build LIKE conditions using prepared statements for security
    $like_condition_parts = [];
    $like_condition_values = [];
    
    for ($i = 0; $i <= count($final_words) - $window_size; $i++) {
        $group = array_slice($final_words, $i, $window_size);
        $group_clauses = [];
        foreach ($group as $word) {
            $escaped_word = $wpdb->esc_like($word);
            $group_clauses[] = "post_content LIKE %s";
            $like_condition_values[] = '%' . $escaped_word . '%';
        }
        $like_condition_parts[] = '(' . implode(' AND ', $group_clauses) . ')';
    }
    
    // Combine all groups with OR
    $like_condition_template = implode(' OR ', $like_condition_parts);

    // // VERION 2
    // for ($i = 0; $i <= count($final_words) - $window_size; $i++) {
    //     $group = array_slice($final_words, $i, $window_size);
    //     $group_clauses = [];
    //     foreach ($group as $word) {
    //         $escaped_word = $wpdb->esc_like($word);
    //         $group_clauses[] = "post_content LIKE '%" . esc_sql($escaped_word) . "%'";
    //     }
    //     // At least half of the words must match
    //     $like_conditions[] = '(' . implode(' OR ', $group_clauses) . ')';
    // }
    // $like_condition = implode(' AND ', $like_conditions);

    // // VERSION 3
    // $search_terms = implode(' ', array_map('esc_sql', $final_words));
    // $like_condition = "MATCH(post_content) AGAINST ('$search_terms' IN NATURAL LANGUAGE MODE)";

    // // VERSION 4
    // for ($i = 0; $i <= count($final_words) - $window_size; $i++) {
    //     $group = array_slice($final_words, $i, $window_size);
    //     $group_clauses = [];
    //     foreach ($group as $word) {
    //         $escaped_word = $wpdb->esc_like($word);
    //         $group_clauses[] = "post_content LIKE '%" . esc_sql($escaped_word) . "%'";
    //     }
    //     // Require at least one match per group
    //     $like_conditions[] = '(' . implode(' OR ', $group_clauses) . ')';
    // }
    // // Require at least one group to match
    // $like_condition = implode(' AND ', $like_conditions);

    // DIAG - Diagnostic - Ver 2.2.4

    // Handle error for no matching content
    if (empty($like_condition_template) || empty($like_condition_values)) {
        return $no_matching_content_response[array_rand($no_matching_content_response)];
    }

    // Step 5 - Fetch WordPress content
    // Build the complete SQL query with proper parameterization
    // Combine base query placeholders with LIKE condition placeholders
    $sql_template = "
        SELECT post_content
        FROM {$wpdb->posts}
        WHERE post_status = %s
        AND (post_type = %s OR post_type = %s)
        AND (" . $like_condition_template . ")
    ";
    
    // Prepare the complete query with all values
    $prepare_args = array_merge(['publish', 'post', 'page'], $like_condition_values);
    $sql = call_user_func_array([$wpdb, 'prepare'], array_merge([$sql_template], $prepare_args));

    $results = $wpdb->get_results($sql, ARRAY_A);

    // Combine content into a single string
    $content = '';
    if (!empty($results)) {
        foreach ($results as $row) {
            $content .= $row['post_content'] . ' ';
        }
    } else {
        $content = $no_matching_content_response[array_rand($no_matching_content_response)];
    }

    // Clean and return content
    $content = strip_tags($content);
    $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5);

    return $content;

}

// Function to build or retrieve cached embeddings
function transformer_model_sentential_context_get_cached_embeddings($corpus, $windowSize = 3) {

    // DIAG - Diagnostic - Ver 2.2.1

    $embeddings = transformer_model_sentential_context_build_cooccurrence_matrix($corpus, $windowSize);

    return $embeddings;

}

// Function to build a co-occurrence matrix for word embeddings
function transformer_model_sentential_context_build_cooccurrence_matrix($corpus, $windowSize = 3) {

    // DIAG - Diagnostic - Ver 2.2.1

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

    // DIAG - Diagnostic - Ver 2.2.1

    // Use global stop words list
    global $stopWords;

    if (!is_array($stopWords)) {
        $stopWords = array(); // Ensure $stopWords is an array
    }

    return array_diff($words, $stopWords);

}

// Function to calculate cosine similarity between two vectors
function transformer_model_sentential_context_cosine_similarity($vectorA, $vectorB) {

    // DIAG - Diagnostic - Ver 2.2.1

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

    // DIAG - Diagnostic - Ver 2.3.0

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

    // Retrieve settings
    $maxSentences = intval(esc_attr(get_option('chatbot_transformer_model_sentence_response_length', 20)));
    $maxTokens = intval(esc_attr(get_option('chatbot_transformer_model_max_tokens', 10000)));

    // Ratios for splitting sentences and tokens
    $sentenceBeforeRatio = floatval(esc_attr(get_option('chatbot_transformer_model_leading_sentences_ratio', '0.2')));  // 20% of sentences before
    $tokenBeforeRatio = floatval(esc_attr(get_option('chatbot_transformer_model_leading_token_ratio', '0.2')));         // 20% of tokens before

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
            break; // Stop if adding this sentence exceeds the token limit
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
            break; // Stop if adding this sentence exceeds the token limit
        }

    }

    // Calculate key stats
    $similarityThreshold = floatval(esc_attr(get_option('chatbot_transformer_model_similarity_threshold', 0.5)));
    $highestSimilarity = max($similarities);
    $averageSimilarity = array_sum($similarities) / count($similarities);

    $matchesAboveThreshold = array_filter($similarities, function($similarity) use ($similarityThreshold) {
        return $similarity > $similarityThreshold;
    });
    $numMatchesAboveThreshold = count($matchesAboveThreshold);
    $totalSentencesAnalyzed = count($sentences);

    // Before returning repsonse log the key stats

    // Return the response
    return $response;

}
