<?php
/**
 * Kognetiks Chatbot for WordPress - Markov Chain Decode - Ver 2.1.6
 *
 * This file contains the code for implementing the Markov Chain algorithm
 *
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Generate a sentence using the Markov Chain with context reinforcement
function generateMarkovText($startWords = [], $length = 100, $primaryKeyword = '') {

    global $chatbot_markov_chain_fallback_response;

    $length = 100; // Set default length

    // Get the Markov Chain length from the options
    $chainLength = esc_attr(get_option('chatbot_markov_chain_length', 2)); // Default to 2 if not set

    // Clean up start words
    $startWords = array_map('trim', $startWords);
    $startWords = array_map(function($word) {
        return preg_replace('/[^\w\s]/', '', $word); // Clean up non-alphanumeric characters
    }, $startWords);

    // Initialize with the primary key (if available)
    $key = $primaryKeyword ? $primaryKeyword : null;

    // Phase 1: Attempt to find a starting point
    if (!empty($startWords)) {
        for ($i = count($startWords) - $chainLength; $i >= 0; $i--) {
            $attemptedKey = implode(' ', array_slice($startWords, $i, $chainLength));
            $keyExists = checkKeyInDatabase($attemptedKey);

            if ($keyExists) {
                $key = $attemptedKey;
                $primaryKeyword = $key; // Set primary keyword for context reinforcement
                back_trace( 'NOTICE', 'Found starting key: ' . $key);
                back_trace( 'NOTICE', 'Primary keyword: ' . $primaryKeyword);
                break;
            }
        }

        if (!$key) {
            return $chatbot_markov_chain_fallback_response[array_rand($chatbot_markov_chain_fallback_response)];
        }
    }

    // Phase 2: Generate words going forward, reinforcing context periodically
    $words = explode(' ', $key); 
    $iterationsSinceContextCheck = 0;

    for ($i = 0; $i < $length; $i++) {

        // Fetch the next word
        $nextWord = getNextWordFromDatabase($key);

        if ($nextWord === null) {
            break; // End the sentence if no next word is found
        }

        // Explode $nextWord in case it contains multiple words
        $nextWordsArray = explode(' ', $nextWord);
        $words = array_merge($words, $nextWordsArray);

        // Update the key with the last 'chainLength' words
        $key = implode(' ', array_slice($words, -$chainLength));

        // Periodically reintroduce primary keyword context to keep output on track
        $iterationsSinceContextCheck++;
        if ($iterationsSinceContextCheck >= 10 && $primaryKeyword) {
            $key = $primaryKeyword; // Reinforce context every 10 iterations
            $iterationsSinceContextCheck = 0;
        }
    }

    // Final sentence building and punctuation check
    $response = implode(' ', $words);

    // Clean up and return the response
    return clean_up_markov_chain_response($response);

}

// Check if the key exists
function checkKeyInDatabase($key) {

    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_markov_chain';

    $result = $wpdb->get_var($wpdb->prepare("SELECT word FROM $table_name WHERE word = %s", $key));
    
    return $result;

}

// Tune the Markov Chain response for better readability
function getNextWordFromDatabase($currentWord) {

    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_markov_chain';

    // Query to get possible next words and their frequencies
    $results = $wpdb->get_results(
        $wpdb->prepare("SELECT next_word, frequency FROM $table_name WHERE word = %s", $currentWord),
        ARRAY_A
    );

    if (empty($results)) {
        return null; // Return null if no next word is found
    }

    // Sort results by frequency to get the most common word
    usort($results, function($a, $b) {
        return $b['frequency'] - $a['frequency'];
    });

    // 80% chance to select the most frequent word
    if (mt_rand(1, 100) <= 80) {
        return $results[0]['next_word']; // Return the most frequent word
    }

    // Otherwise, use the probabilistic approach
    $totalProbability = array_sum(array_column($results, 'frequency'));
    $random = mt_rand(1, $totalProbability);

    $cumulative = 0;
    foreach ($results as $row) {
        $cumulative += $row['frequency'];
        if ($random <= $cumulative) {
            return $row['next_word'];
        }
    }

    // Fallback to the most frequent word (shouldn't happen)
    return $results[0]['next_word'];

}

// Get a random word from the database to start the chain
function getRandomWordFromDatabase() {

    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_markov_chain';

    // Query to get a random word from the database
    $randomWord = $wpdb->get_var("SELECT word FROM $table_name ORDER BY RAND() LIMIT 1");

    return $randomWord;

}

// Select the next word based on its probability distribution
function selectNextWordBasedOnProbability($nextWords) {

    // Create an array of cumulative probabilities
    $cumulativeProbabilities = [];
    $totalProbability = 0;

    foreach ($nextWords as $word => $probability) {
        $totalProbability += (float)$probability;
        $cumulativeProbabilities[] = ['word' => $word, 'cumulative' => $totalProbability];
    }

    // Generate a random number between 0 and 1
    $random = mt_rand() / mt_getrandmax();

    // Find the word that matches the random number
    foreach ($cumulativeProbabilities as $item) {
        if ($random <= $item['cumulative']) {
            return $item['word'];
        }
    }

    // Fallback to the last word in case no match is found (shouldn't happen)
    return end($cumulativeProbabilities)['word'];

}

// Clean up the Markov Chain response for better readability
function clean_up_markov_chain_response($response) {

    // Trim whitespace and ensure first letter is capitalized
    $response = ucfirst(trim($response));

    // Step 1: Capitalize the first letter of each sentence
    // This uses a regex to match sentence boundaries and capitalize appropriately
    $response = preg_replace_callback('/(?:^|[.!?]\s+)([a-z])/', function($matches) {
        return strtoupper($matches[1]);
    }, $response);

    // Step 2: Add punctuation at the end if missing
    if (!preg_match('/[.!?]$/', $response)) {
        $response .= '.';
    }

    // Step 3: Replace multiple spaces with a single space
    $response = preg_replace('/\s+/', ' ', $response);

    // Step 4: Basic punctuation cleanup
    // Ensure no space before punctuation and space after punctuation
    $response = preg_replace('/\s+([?.!,])/', '$1', $response);  // No space before punctuation
    $response = preg_replace('/([?.!,])([^\s?.!,])/', '$1 $2', $response);  // Space after punctuation

    // Step 5: Fix grammar issues (custom function for specific cases)
    $response = fix_common_grammar_issues($response);

    // Step 6: Ensure the response starts with an alphanumeric character
    $response = preg_replace('/^[^a-zA-Z0-9]+/', '', $response);

    // Step 7: Additional punctuation and case fixes
    // Insert a period between lowercase followed by an uppercase letter
    $response = preg_replace('/([a-z]) ([A-Z])/', '$1. $2', $response);
    // Remove spaces between special characters
    $response = preg_replace('/([^\w\s])\s+([^\w\s])/', '$1$2', $response);
    // Handle cases with lowercase words followed by uppercase
    $response = preg_replace('/([a-z]+) ([A-Z]) ([a-z]+)/', '$1. $2 $3', $response);

    // Step 8: Handle edge cases like misplaced punctuation and capitalization
    $response = preg_replace('/([.!?])"([a-z])/', '$1" $2', $response);  // Fix misplaced punctuation within quotes
    $response = preg_replace('/([a-z])\. ([A-Z])/', '$1. $2', $response);  // Fix misplaced periods

    // Final Step: Upper case the first letter again in case any fixes affected it
    $response = ucfirst($response);

    return $response;
}

// Fix common grammar issues in the response
function fix_common_grammar_issues($response) {

    do {
        $previous_response = $response;
    
        // Example: Replace "a an" with "an"
        $response = preg_replace('/\ba an\b/', 'an', $response);
    
        // Example: Correct common phrase issues like "more better" -> "better"
        $response = preg_replace('/\bmore better\b/', 'better', $response);
    
        // Example: Correct "a apple" -> "an apple"
        $response = preg_replace('/\ba ([aeiouAEIOU])\b/', 'an $1', $response);
    
        // Example: Correct "an [consonant sound]" -> "a [consonant sound]"
        $response = preg_replace('/\ban ([bcdfghjklmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ])\b/', 'a $1', $response);
    
        // Example: Replace "you is" with "you are"
        $response = preg_replace('/\byou is\b/', 'you are', $response);
    
        // Example: Replace "doesn't has" with "doesn't have"
        $response = preg_replace('/\bdoesn\'t has\b/', 'doesn\'t have', $response);
    
        // Remove repetitive articles
        $response = preg_replace('/\b(a|an|and|for|the|to) \1\b/i', '$1', $response);
    
        // Remove invalid word pairs like "the it"
        $response = preg_replace('/\b(the|a|an|and|for|to|in|on|with|by|from|at|of) (it|he|she|they|we|you|I)\b/i', '$2', $response);
    
        // Remove invalid word pairs like "too and to in"
        $response = preg_replace('/\b(too|and|to|in) (and|to|in|too)\b/i', '$2', $response);
    
        // Remove invalid sequences like "a and an"
        $response = preg_replace('/\b(a|an|and|for|the|to) (and|an|a|for|the|to)\b/i', '$2', $response);
    
        // Handle specific invalid word pairs like "of the", "with to", "as and"
        $response = preg_replace('/\b(of|with|as|by|to|for|from|in|on) (and|of|to|the)\b/i', '$1', $response);
    
        // Don't end a sentence with a preposition or conjunction followed by a period
        $response = preg_replace('/\b(a|as|at|by|for|from|in|of|on|or|to|the|with|and|when)\.\b/i', '.', $response);
    
        // Remove prepositions or articles at the end of a sentence before a period
        $response = preg_replace('/\b(a|an|the|and|or|in|on|with|at|for|by|to|of)\b\.$/', '.', $response);
    
        // Ensure proper punctuation before uppercase letters
        $response = preg_replace('/([a-z]) ([A-Z])/', '$1. $2', $response);
    
        // Remove standalone prepositions or conjunctions at the end of sentences
        $response = preg_replace('/\b(a|as|at|by|for|from|in|of|on|or|to|the|with|and|when)\b\./i', '.', $response);
    
        // Remove standalone prepositions or conjunctions at the end of sentences without a period
        $response = preg_replace('/\b(a|as|at|by|for|from|in|of|on|or|to|the|with|and|when)\b$/i', '', $response);
    
        // Capitalize the first letter of each sentence
        $response = preg_replace_callback('/(?:^|[.!?]\s+)([a-z])/', function ($matches) {
            return strtoupper($matches[0]);
        }, $response);
    
        // Remove any spaces before periods
        $response = preg_replace('/\s+\./', '.', $response);
    
        // Replace double periods with a single period
        $response = preg_replace('/\.\.+/', '.', $response);
    
        // Correct leftover conjunctions or prepositions at sentence boundaries
        $response = preg_replace('/(\b[a-z]+\b)\s+\1\b/i', '$1', $response);
    
    } while ($previous_response !== $response); // Loop until no more changes are made
    
    return $response;

}
