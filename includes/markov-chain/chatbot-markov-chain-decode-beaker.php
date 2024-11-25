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

    // Diagnostics
    back_trace('NOTICE', 'Generating Markov Chain response with TF-IDF support in Beaker model');

    global $chatbot_markov_chain_fallback_response, $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_markov_chain';
    $tfidf_table = $wpdb->prefix . 'chatbot_chatgpt_knowledge_base_tfidf';

    $chainLength = intval(get_option('chatbot_markov_chain_length', 3));

    // Clean up start words
    $startWords = array_filter(array_map(function($word) {
        return preg_replace('/[^\w\s\-]/u', '', trim($word));
    }, $startWords));

    // Check if TF-IDF table exists and find the highest-scoring word
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

    // Attempt to construct a starting key, prioritizing the TF-IDF word if available
    $key = null;
    if ($highestScoringWord) {
        foreach ($startWords as $index => $word) {
            if ($word === $highestScoringWord) {
                $key = implode(' ', array_slice($startWords, max(0, $index - ($chainLength - 1)), $chainLength));
                if (markov_chain_beaker_key_exists($key, $table_name)) {
                    break;
                }
            }
        }
    }

    // Fallback to sliding window approach if no TF-IDF key is found
    if (!$key && !empty($startWords)) {
        for ($i = count($startWords) - $chainLength; $i >= 0; $i--) {
            $attemptedKey = implode(' ', array_slice($startWords, $i, $chainLength));
            if (markov_chain_beaker_key_exists($attemptedKey, $table_name)) {
                $key = $attemptedKey;
                break;
            }
        }
    }

    // Use a random key if no starting key is found
    if (!$key) {
        $key = markov_chain_beaker_get_random_key($table_name);
        if (!$key) {
            return $chatbot_markov_chain_fallback_response[array_rand($chatbot_markov_chain_fallback_response)];
        }
    }

    // Initialize variables for text generation
    $words = explode(' ', $key);
    $offTopicCount = 0;
    $offTopicMax = intval(get_option('chatbot_markov_chain_off_topic_max', 5));
    $minLength = rand($minLength, $max_tokens / 2);

    for ($i = 0; $i < $max_tokens; $i++) {

        $nextWord = markov_chain_beaker_get_next_word($key, $table_name);
        if ($nextWord === null) {
            break;
        }

        $words[] = $nextWord;
        $key = implode(' ', array_slice($words, -$chainLength));

        // Allow topic drift after minimum length is reached
        if ($i >= $minLength && $primaryKeyword && strpos($nextWord, $primaryKeyword) === false) {
            $offTopicCount++;
            if ($offTopicMax > 0 && $offTopicCount >= $offTopicMax) {
                break;
            }
        }

        // Check for natural sentence endings
        if (count($words) >= $minLength && preg_match('/[.!?]$/', $nextWord)) {
            break;
        }
    }

    // Final cleanup
    $response = implode(' ', $words);
    $response = markov_chain_beaker_clean_up_response($response);

    // Apply finalize_generated_text function here
    $response = finalize_generated_text($response);

    return $response;

}

// Finalize the generated text
function finalize_generated_text($text) {

    // Capitalize the first letter of each sentence
    $text = preg_replace_callback('/(?:^|[.!?])\s*(\w)/', function($matches) {
        return strtoupper($matches[0]);
    }, $text);

    // Remove spaces before punctuation
    $text = preg_replace('/\s+([.,!?])/', '$1', $text);

    return trim($text);
    
}

// Check if the key exists in the database
function markov_chain_beaker_key_exists($key, $table_name) {

    global $wpdb;
    $result = $wpdb->get_var($wpdb->prepare("SELECT 1 FROM $table_name WHERE word = %s LIMIT 1", $key));
    return !is_null($result);

}

// Get the next word from the database
function markov_chain_beaker_get_next_word($currentKey, $table_name) {

    global $wpdb;

    // Fetch possible next words with their frequencies
    $results = $wpdb->get_results(
        $wpdb->prepare("SELECT next_word, frequency FROM $table_name WHERE word = %s", $currentKey),
        ARRAY_A
    );

    if (empty($results)) {
        // Try reducing the chain length if no results are found
        $keyParts = explode(' ', $currentKey);
        array_shift($keyParts);
        if (count($keyParts) > 0) {
            $newKey = implode(' ', $keyParts);
            return markov_chain_beaker_get_next_word($newKey, $table_name);
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
function markov_chain_beaker_get_random_key($table_name) {

    global $wpdb;
    return $wpdb->get_var("SELECT word FROM $table_name ORDER BY RAND() LIMIT 1");

}

// Clean up the Markov Chain response
function markov_chain_beaker_clean_up_response($response) {

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

    // Fix common grammar issues
    $response = markov_chain_beaker_fix_grammar($response);

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
