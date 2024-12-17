<?php
/**
 * Kognetiks Chatbot - Markov Chain Decode - Beaker - Ver 2.2.0
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
    
    back_trace( 'NOTICE', 'Generating Markov Chain response with improved starting key selection');

    global $chatbot_markov_chain_fallback_response, $wpdb;
    $markov_chain_table = $wpdb->prefix . 'chatbot_markov_chain';
    $tfidf_table = $wpdb->prefix . 'chatbot_chatgpt_knowledge_base_tfidf';

    $chainLength = intval(esc_attr(get_option('chatbot_markov_chain_length', 3)));
    $maxSentences = intval(esc_attr(get_option('chatbot_markov_chain_max_sentences', 3)));
    $offTopicMax = intval(esc_attr(et_option('chatbot_markov_chain_off_topic_max', 5)));

    // Clean up start words
    $startWords = array_filter(array_map(function($word) {
        return preg_replace('/[^\w\s\-]/u', '', trim($word));
    }, $startWords));

    // Extract n-grams from startWords
    $nGrams = [];
    $numWords = count($startWords);
    for ($n = $chainLength; $n > 0; $n--) {
        for ($i = 0; $i <= $numWords - $n; $i++) {
            $nGram = implode(' ', array_slice($startWords, $i, $n));
            $nGrams[$n][] = $nGram;
        }
    }

    // Attempt to find a starting key using n-grams
    $key = null;
    foreach ($nGrams as $n => $grams) {
        foreach ($grams as $nGram) {
            if (markov_chain_beaker_key_exists($nGram, $markov_chain_table)) {
                $key = $nGram;
                $chainLength = $n; // Adjust chain length
                back_trace( 'NOTICE', "Found starting key using n-gram of length $n: $key");
                break 2;
            }
        }
    }

    // If no key is found, use fuzzy matching
    if (!$key) {
        back_trace( 'NOTICE', 'No exact key found. Attempting fuzzy matching.');
        $allKeys = markov_chain_beaker_get_all_keys($markov_chain_table);
        $bestMatch = null;
        $highestSimilarity = 0;

        foreach ($allKeys as $existingKey) {
            $similarity = similar_text(implode(' ', $startWords), $existingKey, $percent);
            if ($percent > $highestSimilarity) {
                $highestSimilarity = $percent;
                $bestMatch = $existingKey;
            }
        }

        if ($bestMatch && $highestSimilarity > 50) { // Threshold can be adjusted
            $key = $bestMatch;
            back_trace( 'NOTICE', "Found starting key using fuzzy matching: $key");
        }
    }

    // If still no key is found, use a random key
    if (!$key) {
        back_trace( 'NOTICE', 'Fallback to random key');
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
    back_trace( 'NOTICE', 'Building the response text using the key: ' . $key);

    for ($i = 0; $i < $max_tokens; $i++) {
        $nextWord = markov_chain_beaker_get_next_word($key, $markov_chain_table);
        if ($nextWord === null) {
            back_trace( 'NOTICE', 'Next word is null. Ending generation.');
            break;
        }

        $words[] = $nextWord;
        $keyWords = array_slice($words, -$chainLength);
        $key = implode(' ', $keyWords);

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
    $response = process_text($response);

    return $response;
    
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

    // Clean up the key removing any non-alphanumeric characters - Ver 2.2.0 - 2024 11 27
    $currentKey = preg_replace('/[^\w\s\-]/u', '', $currentKey);

    // Fetch possible next words with their frequencies
    $results = $wpdb->get_results(
        $wpdb->prepare("SELECT next_word, frequency FROM $markov_chain_table WHERE word = %s", $currentKey),
        ARRAY_A
    );

    if (empty($results)) {
        // Try reducing the chain length if no results are found
        $keyParts = explode(' ', $currentKey);
        
        while (count($keyParts) > 0) {
            array_shift($keyParts);
            if (count($keyParts) > 0) {
                $newKey = implode(' ', $keyParts);
                $nextWord = markov_chain_beaker_get_next_word($newKey, $markov_chain_table);
                if ($nextWord !== null) {
                    return $nextWord;
                }
            }
        }
        
        // If no results are found with smaller keys, return null
        return null;
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

// Unified function to clean, format, and correct text - Ver 2.2.0 - 2024 11 28
function process_text($text) {

    global $abbreviations;

    // DIAG - Diagnostics
    back_trace( 'NOTICE', 'BEFORE PROCESSING - $text: ' . $text);

    // Step 1: Remove non-ASCII characters except for common symbols
    $text = preg_replace('/[^\x20-\x7E\x{2018}\x{2019}\x{201C}\x{201D}]/u', '', $text);

    // Step 2: Replace non-breaking spaces with standard spaces
    $text = preg_replace('/\x{00A0}/u', ' ', $text);

    // Step 3: Replace special line breaks (U+2028 and U+2029) with period + space
    $text = preg_replace('/[\x{2028}\x{2029}]/u', '. ', $text);

    // Step 4: Trim and standardize spacing
    $text = trim($text);
    $text = preg_replace('/\s+/', ' ', $text); // Collapse multiple spaces

    // Step 5: Fix punctuation spacing
    $text = preg_replace('/\s+([.,!?])/', '$1', $text); // Remove space before punctuation
    $text = preg_replace('/([.,!?])([^\s"\'])/', '$1 $2', $text); // Add space after punctuation if missing

    // Step 6: Ensure space after a period and before quotes
    $text = preg_replace('/([.!?])(")/', '$1 $2', $text); // Add space between punctuation and opening quotes

    // Step 7: Remove redundant punctuation
    $text = preg_replace('/([.!?])[:;]+/', '$1', $text); // Remove redundant punctuation

    // Step 8: Ensure proper sentence endings
    $text = preg_replace('/\s*([.!?])\s*$/', '$1', $text); // Ensure a single punctuation mark at the end
    if (!preg_match('/[.!?]$/', $text)) {
        $text .= '.'; // Add a period if missing
    }

    // Step 9: Fix punctuation spacing - Repeated to ensure consistency
    $text = preg_replace('/\s+([.,!?])/', '$1', $text); // Remove space before punctuation
    $text = preg_replace('/([.,!?])([^\s"\'])/', '$1 $2', $text); // Add space after punctuation if missing

    // Step 10: Capitalize the first letter of each sentence
    $text = preg_replace_callback('/(?:^|[.!?])\s*(\w)/', function ($matches) {
        return strtoupper($matches[0]);
    }, $text);

    // Final Step: Remove redundant punctuation patterns (e.g., `. ..` becomes `. `)
    $text = preg_replace('/([.!?])\s+\.\s*\./', '$1 ', $text); // Fix `. ..` to `. `
    $text = preg_replace('/\.\s+\./', '. ', $text); // Fix `. .` to `. `

    // Final trim and diagnostics
    $text = trim($text);
    back_trace( 'NOTICE', 'AFTER PROCESSING - $text: ' . $text);

    return $text;

}

// FIXME - REPLACED IN Ver 2.2.0 - 2024 11 28
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

// Clean up the Markov Chain response
function markov_chain_beaker_clean_up_response($response) {

    global $abbreviations;

    // DIAG - Diagnostics - Ver 2.2.0 - 2024 11 25
    back_trace( 'NOTICE', 'BEFORE CLEANING - $response: ' . $response );

    // Before doing anything, remove any non-ASCII characters except for curly quotes and other common characters
    $response = preg_replace('/[^\x20-\x7E\x{2018}\x{2019}\x{201C}\x{201D}]/u', '', $response);

    // Replace U+2028 and U+2029 with a period followed by a space
    $response = preg_replace('/[\x{2028}\x{2029}]/u', '. ', $response);

    // Trim and capitalize
    $response = ucfirst(trim($response));

    // Escape periods in abbreviations for the regular expression
    $escaped_abbreviations = array_map(function($abbr) {
        return preg_quote($abbr, '/');
    }, $abbreviations);

    // Create a regular expression pattern from the abbreviations
    $abbreviations_pattern = implode('|', $escaped_abbreviations);

    // Fix spacing and punctuation
    $response = preg_replace('/\s+/', ' ', $response);
    $response = preg_replace('/\s+([.,!?])/', '$1', $response);
    $response = preg_replace('/([.,!?])([^\s])/', '$1 $2', $response);

    // Handle common abbreviations
    $response = preg_replace('/\b(' . $abbreviations_pattern . ')\s+/', '$1 ', $response);

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

// FIXME - REPLACED IN Ver 2.2.0 - 2024 11 28
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
