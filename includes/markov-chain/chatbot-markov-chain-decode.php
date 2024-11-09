<?php
/**
 * Kognetiks Chatbot for WordPress - Markov Chain Decode - Ver 2.1.9
 *
 * This file contains the code for implementing the Markov Chain algorithm
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Generate a sentence using the Markov Chain with context reinforcement
function generateMarkovText($startWords = [], $max_tokens = 500, $primaryKeyword = '', $minLength = 10) {

    global $chatbot_markov_chain_fallback_response;

    // Vary the $minLength between the chain length the the maximum tokens - Ver 2.1.9.1
    $minLength = rand($minLength, $max_tokens / 2);

    $chainLength = esc_attr(get_option('chatbot_markov_chain_length', 3));

    // Clean up start words
    $startWords = array_map('trim', $startWords);
    $startWords = array_map(function($word) {
        return preg_replace('/[^\w\s]/', '', $word);
    }, $startWords);

    $key = $primaryKeyword ? $primaryKeyword : null;

    // Phase 1: Attempt to find a starting point
    if (!empty($startWords)) {
        for ($i = count($startWords) - $chainLength; $i >= 0; $i--) {
            $attemptedKey = implode(' ', array_slice($startWords, $i, $chainLength));
            $keyExists = checkKeyInDatabase($attemptedKey);

            if ($keyExists) {
                $key = $attemptedKey;
                $primaryKeyword = $key;
                break;
            }
        }

        if (!$key) {
            return $chatbot_markov_chain_fallback_response[array_rand($chatbot_markov_chain_fallback_response)];
        }
    }

    // Phase 2: Generate words going forward with a flexible topic drift and minimum length requirement
    $words = explode(' ', $key);
    $iterationsSinceContextCheck = 0;
    $offTopicCount = 0; 

    for ($i = 0; $i < $max_tokens; $i++) {

        $nextWord = getNextWordFromDatabase($key, 1);

        if ($nextWord === null) {
            break; // End if no next word is found
        }

        $nextWordsArray = explode(' ', $nextWord);
        $words = array_merge($words, $nextWordsArray);

        // Update the key with the last 'chainLength' words
        $key = implode(' ', array_slice($words, -$chainLength));

        // Try removing special characters and extra spaces from the key - Ver 2.1.9.1
        $key = preg_replace("/[^\w\s']/u", '', $key);
        $key = preg_replace('/\s+/', ' ', $key); // Remove extra spaces

        // Allow more topic drift after the minimum length is reached
        if ($i >= $minLength && strpos($nextWord, $primaryKeyword) === false) {
            $offTopicCount++;
        }

        // Exit if off-topic drift is too high after reaching minimum length
        if ($offTopicCount >= 10 && count($words) > $minLength) {
            break;
        }

        // Check for natural endings if the sentence is long enough
        if (count($words) >= $minLength && preg_match('/[.!?]$/', implode(' ', $words))) {
            break;
        }

        // Reinforce context only if needed to bring focus back
        $iterationsSinceContextCheck++;
        if ($iterationsSinceContextCheck >= 15 && $primaryKeyword) {
            $key = $primaryKeyword;
            $iterationsSinceContextCheck = 0;
        }
    }

    // Final cleanup and punctuation check to ensure sentence completion
    $response = implode(' ', $words);

    // If it doesn’t end with a period, check for possible endings or add punctuation
    if (!preg_match('/[.!?]$/', $response)) {
        $response = rtrim($response, ' ,;') . '.';
    }

    return clean_up_markov_chain_response($response);
    
}

// Check if the key exists
function checkKeyInDatabase($key, $attempts = 1) {

    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_markov_chain';

    // Limit attempts before proceeding
    if ($attempts > esc_attr(get_option('chatbot_markov_chain_length', 3))) {
        return null; // Stop further recursion if max attempts are reached
    }

    // Check the database for an exact match
    $result = $wpdb->get_var($wpdb->prepare("SELECT word FROM $table_name WHERE word = %s", $key));

    // DIAG - Diagnostics - V 2.1.9
    back_trace('NOTICE', 'Checking key: ' . $key . ' - Result: ' . $result);
    back_trace('NOTICE', 'Attempts: ' . $attempts);

    // If no exact match is found, try using the wildcard approach
    if ($result === null) {
        $keyWords = explode(' ', $key);

        // Shuffle the keywords to randomize the order
        shuffle($keyWords);

        // Drop the last word and append a wildcard symbol
        $wildcardKey = implode(' ', array_slice($keyWords, 0, -1)) . ' %';

        // Use LIKE for the wildcard search
        $result = $wpdb->get_var($wpdb->prepare("SELECT word FROM $table_name WHERE word LIKE %s", $wildcardKey));

        // Diagnostic output to log the wildcard key generation and attempt number
        back_trace('NOTICE', 'Attempt ' . $attempts . ': Wildcard key after shuffle - ' . $wildcardKey);
        back_trace('NOTICE', 'Wildcard key result: ' . $result);

        // Recursive call with incremented attempts if no result is found
        if ($result === null) {
            return checkKeyInDatabase($wildcardKey, $attempts + 1);
        }
    }

    return $result;
}


// Get the next word from the database based on the current word
function getNextWordFromDatabase($currentWord, $attempts = 1, $randomWordAttempts = 0) {

    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_markov_chain';

    // Limit for attempts to find a next word using $currentWord
    $maxAttempts = esc_attr(get_option('chatbot_markov_chain_length', 3));

    // Limit for attempts to get a random fallback word
    $maxRandomWordAttempts = $maxAttempts; // Limit based on chain length

    // Check if we've exceeded attempts for the primary word
    if ($attempts > $maxAttempts) {

        // If max attempts reached, use a random word as fallback, limited by $maxRandomWordAttempts
        if ($randomWordAttempts < $maxRandomWordAttempts) {
            $randomWord = getRandomWordFromDatabase();
            
            // Increment and try again if a random word is not found
            if ($randomWord !== null) {
                return $randomWord;
            }

            // Increment random word attempts and retry if necessary
            return getNextWordFromDatabase($currentWord, $attempts, $randomWordAttempts + 1);
        }

        // If we've exhausted random word attempts, return null to indicate failure
        return null;
    }

    // Diagnostic output
    back_trace('NOTICE', 'Checking current word: ' . $currentWord);
    back_trace('NOTICE', 'Attempts: ' . $attempts);

    // Query to get possible next words and their frequencies
    $results = $wpdb->get_results(
        $wpdb->prepare("SELECT next_word, frequency FROM $table_name WHERE word = %s", $currentWord),
        ARRAY_A
    );

    // Check if no results are found
    if (empty($results)) {
        // Randomize order of current word's parts and try again
        $wordParts = explode(' ', $currentWord);
        shuffle($wordParts);
        $shuffledWord = implode(' ', $wordParts);

        // Recursive call with incremented attempts
        return getNextWordFromDatabase($shuffledWord, $attempts + 1, $randomWordAttempts);
    }

    // Sort results by frequency to get the most common word
    usort($results, function($a, $b) {
        return $b['frequency'] - $a['frequency'];
    });

    // 80% chance to select the most frequent word
    if (mt_rand(1, 100) <= 90) {
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

// Clean up the Markov Chain response for better readability
function clean_up_markov_chain_response($response) {

    // Trim whitespace and ensure first letter is capitalized
    $response = ucfirst(trim($response));

    // Step 1: Capitalize the first letter of each sentence
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
    $response = preg_replace('/\s+([?.!,])/', '$1', $response);  // No space before punctuation
    $response = preg_replace('/([?.!,])([^\s?.!,])/', '$1 $2', $response);  // Space after punctuation

    // Step 5: Fix grammar issues
    $response = fix_common_grammar_issues($response);

    // Step 6: Ensure the response starts with an alphanumeric character
    $response = preg_replace('/^[^a-zA-Z0-9]+/', '', $response);

    // Step 7: Additional punctuation and case fixes
    // Insert punctuation where a lowercase word is followed by a space and a word starting with an uppercase letter but is not in ALL CAPS
    $response = preg_replace('/([a-z])\s+([A-Z][a-z]+)/', '$1. $2', $response);

    // Final check to ensure punctuation between phrases and correct spacing
    $response = preg_replace('/([^\w\s])\s+([^\w\s])/', '$1 $2', $response);

    return $response;

}

// Fix common grammar issues in the response
function fix_common_grammar_issues($response) {

    do {
        $previous_response = $response;
    
        // Grammar and formatting fixes
        $response = preg_replace('/\ba an\b/', 'an', $response);
        $response = preg_replace('/\bmore better\b/', 'better', $response);
        $response = preg_replace('/\ba ([aeiouAEIOU])\b/', 'an $1', $response);
        $response = preg_replace('/\ban ([bcdfghjklmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ])\b/', 'a $1', $response);
        $response = preg_replace('/\byou is\b/', 'you are', $response);
        $response = preg_replace('/\bdoesn\'t has\b/', 'doesn\'t have', $response);
        $response = preg_replace('/\b(a|an|and|for|the|to) \1\b/i', '$1', $response);
        $response = preg_replace('/\b(the|a|an|and|for|to|in|on|with|by|from|at|of) (it|he|she|they|we|you|I)\b/i', '$2', $response);
    
    } while ($previous_response !== $response); // Loop until no more changes are made
    
    return $response;

}
