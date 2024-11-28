<?php
/**
 * Kognetiks Chatbot for WordPress - Markov Chain Decode - Beaker - Ver 2.2.0
 *
 * This file contains the improved code for implementing the Markov Chain algorithm.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Generate a sentence using the Markov Chain with context reinforcement
function generate_markov_text_beaker_model($startWords = [], $max_tokens = 500, $primaryKeyword = '', $minLength = 10) {

    back_trace('NOTICE', 'Generating Markov Chain response with adjusted coherence handling');

    global $chatbot_markov_chain_fallback_response, $wpdb;
    $markov_chain_table = $wpdb->prefix . 'chatbot_markov_chain';
    $tfidf_table = $wpdb->prefix . 'chatbot_chatgpt_knowledge_base_tfidf';

    $chainLength = intval(get_option('chatbot_markov_chain_length', 3));
    $maxSentences = intval(get_option('chatbot_markov_chain_max_sentences', 3));
    $offTopicMax = intval(get_option('chatbot_markov_chain_off_topic_max', 5));

    // Clean up start words
    $startWords = array_filter(array_map(function($word) {
        return preg_replace('/[^\w\s\-]/u', '', trim($word));
    }, $startWords));

    // Find the highest-scoring TF-IDF word
    $highestScoringWord = null;
    if ($wpdb->get_var("SHOW TABLES LIKE '$tfidf_table'") == $tfidf_table) {
        $tfidf_results = $wpdb->get_results(
            "SELECT word, score FROM $tfidf_table WHERE word IN ('" . implode("','", $startWords) . "') ORDER BY score DESC LIMIT 1",
            ARRAY_A
        );
        if (!empty($tfidf_results)) {
            $highestScoringWord = $tfidf_results[0]['word'];
        }
    }

    // if one of the $startWords is missing a score, use the highest score - OPTION 1
    // if ($highestScoringWord) {
    //     foreach ($startWords as $word) {
    //         if ($word === $highestScoringWord) {
    //             $highestScoringWord = null;
    //             break;
    //         }
    //     }
    // }

    // if one of the $startWords is missing a score, use the average score - OPTION 2
    // if ($highestScoringWord) {
    //     $tfidf_results = $wpdb->get_results(
    //         "SELECT AVG(score) AS average_score FROM $tfidf_table WHERE word IN ('" . implode("','", $startWords) . "')",
    //         ARRAY_A
    //     );
    //     if (!empty($tfidf_results)) {
    //         $highestScoringWord = $tfidf_results[0]['average_score'];
    //     }
    // }

    // Combine TF-IDF with Frequence - OPTION 3
    $tfidfFrequencyScores = []; // To store combined scores
    $tfidf_tabe = $wpdb->prefix . 'chatbot_chatgpt_knowledge_base_tfidf';

    if ($wpdb->get_var("SHOW TABLES LIKE '$tfidf_table'") == $tfidf_table) {

        foreach ($startWords as $word) {
            // Get TF-IDF score for the word
            $tfidfResult = $wpdb->get_row(
                $wpdb->prepare("SELECT score FROM $tfidf_table WHERE word = %s LIMIT 1", $word),
                ARRAY_A
            );
            $tfidfScore = $tfidfResult['score'] ?? 0;

            // Get frequency for the word
            $frequencyResult = $wpdb->get_row(
                $wpdb->prepare("SELECT SUM(frequency) AS frequency FROM $markov_chain_table WHERE word = %s", $word),
                ARRAY_A
            );
            $frequency = $frequencyResult['frequency'] ?? 0;

            // Combine TF-IDF score and frequency into a single metric
            // Adjust weights as needed (e.g., 70% TF-IDF, 30% frequency)
            $combinedScore = ($tfidfScore * 0.7) + ($frequency * 0.3);

            // Store the combined score
            $tfidfFrequencyScores[$word] = $combinedScore;
        }

        // Sort words by their combined scores in descending order
        arsort($tfidfFrequencyScores);

        // Take the word with the highest combined score
        $highestScoringWord = key($tfidfFrequencyScores);

    }

    // Attempt to construct a starting key
    $key = null;
    if ($highestScoringWord) {
        // DIAG - Diagnostics - Ver 2.2.0 - 2024 11 27
        back_trace( 'NOTICE', 'Attempting to construct a key using $highestScoringWord: ' . $highestScoringWord );
        foreach ($startWords as $index => $word) {
            if ($word === $highestScoringWord) {
                for ($offset = 0; $offset < $chainLength; $offset++) {
                    $start = max(0, $index - $offset);
                    $end = min($start + $chainLength, count($startWords));
                    $attemptedKey = implode(' ', array_slice($startWords, $start, $end - $start));
                    if (markov_chain_beaker_key_exists($attemptedKey, $markov_chain_table)) {
                        $key = $attemptedKey;
                        break 2;
                    }
                }
            }
        }
    }

    // Fallback to sliding window approach if no TF-IDF key is found
    if (!$key && !empty($startWords)) {
        // DIAG - Diagnostics - Ver 2.2.0 - 2024 11 27
        back_trace( 'NOTICE', 'Fallback to sliding window approach using $startWords: ' . implode(' ', $startWords) );
        for ($i = count($startWords) - $chainLength; $i >= 0; $i--) {
            $attemptedKey = implode(' ', array_slice($startWords, $i, $chainLength));
            if (markov_chain_beaker_key_exists($attemptedKey, $markov_chain_table)) {
                $key = $attemptedKey;
                break;
            }
        }
    }

    // Try reducing the chain length if no key is found
    if (!$key && $chainLength > 1) {
        // DIAG - Diagnostics - Ver 2.2.0 - 2024 11 27
        back_trace( 'NOTICE', 'Fallback to reduced chain length' );
        for ($i = $chainLength - 1; $i > 0; $i--) {
            $attemptedKey = implode(' ', array_slice($startWords, -$i));
            if (markov_chain_beaker_key_exists($attemptedKey, $markov_chain_table)) {
                $key = $attemptedKey;
                break;
            }
        }
    }

    // Use a random key if no starting key is found
    if (!$key) {
        // DIAG - Diagnostics - Ver 2.2.0 - 2024 11 27
        back_trace( 'NOTICE', 'Fallback to random key' );
        $key = markov_chain_beaker_get_random_key($markov_chain_table);
        if (!$key) {
            return $chatbot_markov_chain_fallback_response[array_rand($chatbot_markov_chain_fallback_response)];
        }
    }

    // Initialize variables for text generation
    $words = explode(' ', $key);
    $offTopicCount = 0;
    $sentenceCount = 0;

    // Generate the response text
    // DIAG - Diagnostics - Ver 2.2.0 - 2024 11 27
    back_trace( 'NOTICE', 'Building the response text using the key: ' . $key );

    for ($i = 0; $i < $max_tokens; $i++) {
        $nextWord = markov_chain_beaker_get_next_word($key, $markov_chain_table);
        if ($nextWord === null) {
            // DIAG - Diagnostics - Ver 2.2.0 - 2024 11 27
            back_trace( 'NOTICE', 'Next word is null so we should be done' );
            break;
        }

        $words[] = $nextWord;
        $key = implode(' ', array_slice($words, -$chainLength));

        // Clean up the key
        $key = preg_replace('/[^\w\s\-]/u', '', $key);

        // Allow topic drift after minimum length is reached
        if ($i >= $minLength && $primaryKeyword && strpos($nextWord, $primaryKeyword) === false) {
            $offTopicCount++;
            if ($offTopicCount >= $offTopicMax) {
                break;
            }
        }

        // Check sentence boundaries and enforce sentence limit
        if (preg_match('/[.!?]$/', $nextWord)) {
            $sentenceCount++;
            if ($sentenceCount >= $maxSentences) {
                break;
            }
        }
    }

    // Final cleanup
    $response = implode(' ', $words);
    $response = markov_chain_beaker_clean_up_response($response);
    $response = finalize_generated_text($response);

    return $response;
}

// Finalize the generated text
function finalize_generated_text($text) {

    // Capitalize first letter of each sentence
    $text = preg_replace_callback('/([.!?]\s*|\A)(\w)/', function ($matches) {
        return $matches[1] . strtoupper($matches[2]);
    }, $text);

    // Fix spacing and punctuation
    $text = preg_replace('/\s+([.,!?])/', '$1', $text); // Remove space before punctuation
    $text = preg_replace('/\s{2,}/', ' ', $text); // Collapse multiple spaces

    // Reduce multiple punctuation marks to a single one
    $text = preg_replace('/([.!?]){2,}/', '$1', $text); // Remove redundant punctuation anywhere

    // Ensure the text ends with a single punctuation mark
    $text = preg_replace('/([^.!?])$/', '$1.', $text);

    // Split long sentences logically
    if (str_word_count($text) > 40) {
        $text = preg_replace('/(\w{10,})(\s+\w{10,})/', '$1. $2', $text);
    }

    return trim($text);

}

// Check if the key exists in the database
function markov_chain_beaker_key_exists($key, $markov_chain_table) {

    global $wpdb;

    $result = $wpdb->get_var($wpdb->prepare("SELECT 1 FROM $markov_chain_table WHERE word = %s LIMIT 1", $key));

    if ($result === null) {
        // DIAG - Diagnostics - Ver 2.2.0 - 2024 11 27
        back_trace( 'NOTICE', 'Key Not Found in DB - $key: ' . $key );
    } else {
        // DIAG - Diagnostics - Ver 2.2.0 - 2024 11 27
        back_trace( 'NOTICE', 'Key Found in DB - $key: ' . $key );
    }

    return !is_null($result);

}

// Get the next word from the database
function markov_chain_beaker_get_next_word($currentKey, $markov_chain_table) {

    global $wpdb;

    // Normalize the key - Ver 2.2.0 - 2024 11 27
    $currentKey = trim(strtolower($currentKey));

    // Clean up the key
    $currentKey = preg_replace('/[^\w\s\-]/u', '', $currentKey);

    // Fetch possible next words with their frequencies
    $results = $wpdb->get_results(
        $wpdb->prepare("SELECT next_word, frequency FROM $markov_chain_table WHERE word = %s", $currentKey),
        ARRAY_A
    );

    if (empty($results)) {
        // Try reducing the chain length if no results are found
        $keyParts = explode(' ', $currentKey);
        array_shift($keyParts);
        if (count($keyParts) > 0) {
            $newKey = implode(' ', $keyParts);
            return markov_chain_beaker_get_next_word($newKey, $markov_chain_table);
        } else {
            return null;
        }
    }

    // Calculate total frequency
    $totalFrequency = array_sum(array_column($results, 'frequency'));

    // Generate a random number between 1 and total frequency
    $rand = mt_rand(1, $totalFrequency);

    // Select next word based on weighted probability
    $cumulative = 0;
    foreach ($results as $row) {
        $cumulative += $row['frequency'];
        if ($rand <= $cumulative) {
            return $row['next_word'];
        }
    }

    // Fallback to the most frequent next word
    return $results[0]['next_word'];

}

// Get a random key from the database
function markov_chain_beaker_get_random_key($markov_chain_table) {

    global $wpdb;
    return $wpdb->get_var("SELECT word FROM $markov_chain_table ORDER BY RAND() LIMIT 1");

}

// Clean up the Markov Chain response
function markov_chain_beaker_clean_up_response($response) {

    // DIAG - Diagnostics - Ver 2.2.0 - 2024 11 25
    back_trace( 'NOTICE', 'BEFORE CLEANING - $response: ' . $response );

    // Before doing anything, remove any non-ASCII characters except for curly quotes and other common characters
    $response = preg_replace('/[^\x20-\x7E\x{2018}\x{2019}\x{201C}\x{201D}]/u', '', $response);

    // Trim and capitalize
    $response = ucfirst(trim($response));

    // Fix spacing and punctuation
    $response = preg_replace('/\s+/', ' ', $response);
    $response = preg_replace('/\s+([.,!?])/', '$1', $response);
    $response = preg_replace('/([.,!?])([^\s])/', '$1 $2', $response);

    // Ensure proper sentence endings
    if (!preg_match('/[.!?]$/', $response)) {
        $response .= '.';
    }

    // Remove excessive spaces before punctuation
    $response = preg_replace('/\s+([.,!?])/', '$1', $response);

    // Capitalize the first letter of each sentence
    $response = preg_replace_callback('/(?:^|[.!?])\s*(\w)/', function($matches) {
        return strtoupper($matches[0]);
    }, $response);

    // Fix common grammar issues
    $response = markov_chain_beaker_fix_grammar($response);

    // DIAG - Diagnostics - Ver 2.2.0 - 2024 11 25
    back_trace( 'NOTICE', 'AFTER CLEANING - $response: ' . $response );

    return $response;

}

// Fix common grammar issues in the response
function markov_chain_beaker_fix_grammar($response) {

    // Correct indefinite articles
    $response = preg_replace('/\b(a|an) ([aeiouAEIOU])/', 'an $2', $response);
    $response = preg_replace('/\b(an) ([^aeiouAEIOU\s])/', 'a $2', $response);

    // Correct common mistakes
    $replacements = [
        '/\b(you|we|they) is\b/' => '$1 are',
        '/\bI is\b/' => 'I am',
        '/\bdoesn\'t has\b/' => 'doesn\'t have',
        '/\bcan\'t not\b/' => 'cannot',
        '/\bmore better\b/' => 'better',
        '/\b(\w+) \1\b/i' => '$1',
    ];

    foreach ($replacements as $pattern => $replacement) {
        $response = preg_replace($pattern, $replacement, $response);
    }

    return $response;

}
