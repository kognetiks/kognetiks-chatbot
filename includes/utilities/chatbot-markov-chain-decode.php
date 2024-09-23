<?php
/**
 * Kognetiks Chatbot for WordPress - Markove Chain Decode - Ver 2.1.6
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

// Generate a sentence using the Markov Chain with probabilities, fetching next words dynamically
function generateMarkovText($startWords = [], $length = 100) {

    global $chatbot_chatgpt_markov_chain_fallback_response;

    // FIXME - Override length of response - Ver 2.1.6 - 2024-09-23
    $length = 100;

    // checkMarkovChainUpdate(); // Check if the Markov Chain needs to be updated

    // DIAG - Diagnostics - Ver 2.1.6 - 2024-09-21
    // back_trace('NOTICE', 'generateMarkovText - Start');
    // back_trace('NOTICE', 'Requested Length: ' . $length);
    // back_trace('NOTICE', 'Start Words: ' . implode(' ', $startWords));

    // Get the Markov Chain length from the options
    $chainLength = esc_attr(get_option('chatbot_chatgpt_markov_chain_length', 2)); // Default to 2 if not set

    // Trim and clean up start words
    $startWords = array_map('trim', $startWords);
    $startWords = array_map(function($word) {
        return preg_replace('/[^\w\s]/', '', $word); // Clean up non-alphanumeric characters
    }, $startWords);

    // Phase 1: Try to find a valid starting point by backstepping from the end of the input
    $key = null;

    if (!empty($startWords)) {
        // Start by checking the rightmost part of the phrase and then backstep left
        for ($i = count($startWords) - $chainLength; $i >= 0; $i--) {
            $attemptedKey = implode(' ', array_slice($startWords, $i, $chainLength));

            // back_trace('NOTICE', 'Attempting Key: ' . $attemptedKey);

            $keyExists = checkKeyInDatabase($attemptedKey);

            if ($keyExists) {
                $key = $attemptedKey;
                // back_trace('NOTICE', 'Using Key: ' . $key);
                break;
            }
        }

        // If no key is found, use a random word as fallback
        // if (!$key) {
        //     $key = getRandomWordFromDatabase();
        //     // back_trace('NOTICE', 'No valid key found, using random word: ' . $key);
        // }

        // Modify the code to use a random fallback response if no key is found
        if (!$key) {
            // Select a random response from the global array
            return $chatbot_chatgpt_markov_chain_fallback_response[array_rand($chatbot_chatgpt_markov_chain_fallback_response)];
        }

    } else {

        // If no start words provided, get a random word from the database
        // $key = getRandomWordFromDatabase();
        // back_trace('NOTICE', 'Random Phrase: ' . $key);

        // Modify the code to use a random fallback response if no key is found
        if (!$key) {
            // Select a random response from the global array
            return $chatbot_chatgpt_markov_chain_fallback_response[array_rand($chatbot_chatgpt_markov_chain_fallback_response)];
        }

    }

    // Phase 2: Generate words going forward from the starting point
    $words = explode(' ', $key); // Initialize sentence generation with the found key

    for ($i = 0; $i < $length; $i++) {

        // DIAG - Check current key before fetching next word
        // back_trace('NOTICE', 'Fetching next word for Key: ' . $key);

        // Make sure the key has no leading or trailing spaces or special characters
        $key = preg_replace('/[^\w\s]/', '', $key);

        $nextWord = getNextWordFromDatabase($key);

        // DIAG - Log the retrieved next word
        // back_trace('NOTICE', 'Next Word Retrieved: ' . ($nextWord ?? 'NULL'));

        if ($nextWord === null) {
            // back_trace('NOTICE', 'No Next Word Found, Ending Sentence Generation');
            break; // End the sentence if no next word is found
        }

        // Explode $nextWord in case it contains multiple words
        $nextWordsArray = explode(' ', $nextWord);

        // Add the next words to the sentence
        $words = array_merge($words, $nextWordsArray);

        // Ensure the key strictly shifts forward to the last 'chainLength' words in the sentence
        $key = implode(' ', array_slice($words, -$chainLength));

        // DIAG - Log the updated key after adding the next word(s)
        // back_trace('NOTICE', 'Updated Key after Addition: ' . $key);
    }

    // Final sentence building and punctuation check
    $response = implode(' ', $words);

    // Clean up and return the response
    return clean_up_markov_chain_response($response);

}

// Check if the key exists
function checkKeyInDatabase($key) {

    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_markov_chain';

    $result = $wpdb->get_var($wpdb->prepare("SELECT word FROM $table_name WHERE word = %s", $key));
    
    return $result;

}

// FIXME - REPLACED - Ver 2.1.6 - 2024-09-23
// Retrieve the next word based on the current word, querying the database dynamically
function getNextWordFromDatabase_OLD($currentWord) {

    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_markov_chain';

    // Query to get possible next words and their frequencies
    $results = $wpdb->get_results(
        $wpdb->prepare("SELECT next_word, frequency FROM $table_name WHERE word = %s", $currentWord),
        ARRAY_A
    );

    if (empty($results)) {
        return null; // Return null if no next word is found
    }

    // Create a cumulative probability distribution based on word frequency
    $cumulativeProbabilities = [];
    $totalProbability = 0;

    foreach ($results as $row) {
        $totalProbability += $row['frequency'];
        $cumulativeProbabilities[] = ['word' => $row['next_word'], 'cumulative' => $totalProbability];
    }

    // Generate a random number between 0 and the total frequency
    $random = mt_rand(1, $totalProbability);

    // Find the word that matches the random number
    foreach ($cumulativeProbabilities as $item) {
        if ($random <= $item['cumulative']) {
            return $item['word'];
        }
    }

    // Fallback to the last word in case no match is found (shouldn't happen)
    return end($cumulativeProbabilities)['word'];

}

// Tune the Markov Chain response for better readability
function getNextWordFromDatabase($currentWord) {

    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_markov_chain';

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
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_markov_chain';

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

// Retrieve the Markov Chain from the database // NO LONGER USED - Ver 2.1.6 - 2024-09-22
function getMarkovChainFromDatabase() {

    // DIAG - Diagnostics - Start
    // back_trace('NOTICE', 'getMarkovChainFromDatabase - Start');

    // FIXME - FORCE REBUILD - Ver 2.1.6 - 2024-09-19
    $chabot_chatgpt_markov_chain_build_schedule = get_option('chatbot_chatgpt_markov_chain_build_status', 'No');

    if ($chatbot_chatgpt_markov_chain_build_schedule == 'Yes') {

        // back_trace('NOTICE', 'Forcing Markov Chain rebuild.');
        update_option('chatbot_chatgpt_markov_chain_last_updated', '2000-01-01 00:00:00');
        update_option('chatbot_chatgpt_markov_chain_build_status', 'No');
        $markovChain = null;

        // Run the Markov Chain building and saving process
        runMarkovChatbotAndSaveChain();

    }

    // Retrieve the Markov Chain from chunks
    $markovChain = getMarkovChainFromChunks();

    // Check if we successfully retrieved the Markov Chain
    if (!empty($markovChain)) {
        // back_trace('NOTICE', 'Markov Chain Length: ' . count($markovChain));
        // How much memory is being used by the Markov Chain
        // back_trace( 'NOTICE', 'Memory usage: ' . memory_get_usage());
        // back_trace( 'NOTICE', 'Memory usage in megabytes: ' . round(memory_get_usage() / 1024 / 1024, 2));
        return $markovChain;  // Return the valid Markov Chain
    }

    // If no Markov Chain found, rebuild it
    // back_trace('NOTICE', 'No Markov Chain found. Rebuilding.');

    // Run the Markov Chain building and saving process
    runMarkovChatbotAndSaveChain();

    // After rebuilding, attempt to fetch it again
    $markovChain = getMarkovChainFromChunks();

    // Check if rebuilding was successful
    if (!empty($markovChain)) {

        // back_trace('NOTICE', 'Markov Chain Length after rebuild: ' . count($markovChain));
        return $markovChain;  // Return the rebuilt Markov Chain

    }

    // If rebuild fails, log the issue and return null
    // back_trace('NOTICE', 'Failed to rebuild the Markov Chain.');
    return null;  // Return null to indicate failure

}

// FIXME - REPLACED - Ver 2.1.6 - 2024-09-23
// Clean up the Markov Chain response for better readability
function clean_up_markov_chain_response_OLD($response) {

    // Upper case the first letter of the response
    $response = ucfirst($response);

    // Step 1: Capitalize the first letter of each sentence
    $response = preg_replace_callback('/(?:^|\.\s+)(\w)/', function($matches) {
        return strtoupper($matches[1]);
    }, trim($response));

    // back_trace( 'NOTICE', 'After capitalization: ' . $response);

    // Step 2: Add punctuation at the end if missing
    if (!preg_match('/[.!?]$/', $response)) {
        $response .= '.'; // Add a period if no punctuation at the end
    }
    
    // back_trace( 'NOTICE', 'After punctuation check: ' . $response);

    // Step 3: Remove extra spaces
    $response = preg_replace('/\s+/', ' ', $response); // Replace multiple spaces with a single space

    // back_trace( 'NOTICE', 'After space cleanup: ' . $response);

    // Step 4: Basic punctuation cleanup
    // Remove spaces before punctuation, ensure space after punctuation
    $response = preg_replace('/\s+([?.!,])/', '$1', $response); // Remove space before punctuation
    $response = preg_replace('/([?.!,])([^\s?.!,])/', '$1 $2', $response); // Ensure space after punctuation

    // back_trace( 'NOTICE', 'After punctuation spacing cleanup: ' . $response);

    // Step 5: Fix common grammar errors
    $response = fix_common_grammar_issues($response);

    // back_trace( 'NOTICE', 'After grammar fixes: ' . $response);

    // Step 6: Remove or replace nonsense words/phrases
    // $response = remove_nonsense_phrases($response);

    // Step 7: Make sure the first character of the response is not puctuation, spaces, or other non-alphanumeric characters
    $response = preg_replace('/^[^a-zA-Z0-9]+/', '', $response);

    // Step 8: Remove any double spaces caused by previous cleanup
    $response = preg_replace('/\s+/', ' ', $response);

    // Step 9: If there is a lower case leter followed by a space followed by an upper case letter, add a period
    $response = preg_replace('/([a-z]) ([A-Z])/', '$1. $2', $response);

    // Step 10: If there is a special character followed by a space followed by a specai character, remove the space
    $response = preg_replace('/([^\w\s])\s+([^\w\s])/', '$1$2', $response);

    // Step 11: if there is are severl lower case letters followed by a single upper case letter followed by several lower case letters, add a period and space
    $response = preg_replace('/([a-z]+) ([A-Z]) ([a-z]+)/', '$1. $2 $3', $response);

    // Step 12: For example ,"it's justsales.". Change to "It's just sales."
    $response = preg_replace('/([.!?])"([a-z])/', '$1" $2', $response);

    // Step 13: For example "As you can obtain an. API key" Change to "As you can obtain an API key"
    $response = preg_replace('/([a-z])\. ([A-Z])/', '$1. $2', $response);

    // Step 14: Upper case the first letter of the response
    $response = ucfirst($response);

    // back_trace( 'NOTICE', 'After nonsense filtering: ' . $response);

    return $response;

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

// Remove nonsense words or phrases from the response
function remove_nonsense_phrases($response) {

    // FIXME - TEMP IGNORE - Ver 2.1.6 - 2024-09-19
    // Define some nonsense words or phrases to be removed
    $nonsense_phrases = [
        'Lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit', 'sed', 'eiusmod',  'tempor', 
        'incididunt', 'ut', 'labore', 'et', 'dolore', 'magna', 'aliqua', 'Ut', 'enim', 'minim',  'veniam', 'quis', 
        'nostrud', 'exercitation', 'ullamco', 'laboris', 'nisi', 'aliquip', 'ex', 'ea', 'commodo',  'consequat', 'duis', 
        'aute', 'irure', 'in', 'reprehenderit', 'voluptate', 'velit', 'esse', 'cillum', 'eu', 'fugiat', 'nulla', 'pariatur', 
        'excepteur', 'sint', 'occaecat', 'cupidatat', 'non', 'proident', 'sunt', 'culpa',  'qui', 'officia', 'deserunt', 
        'mollit', 'anim', 'id', 'est', 'laborum', 'dolorem', 'fugit', 'consequatur', 'unde',  'omnis', 'iste', 'natus', 'similique'
    ];

    // Lowercase the nonsense phrases for case-insensitive matching
    $nonsense_phrases = array_map('strtolower', $nonsense_phrases);
    
    // Convert response to lowercase for case-insensitive matching
    $lowercase_response = strtolower($response);

    foreach ($nonsense_phrases as $phrase) {
        // Use a regex to remove whole word matches only (case-insensitive)
        $pattern = '/\b' . preg_quote($phrase, '/') . '\b/i';
        $lowercase_response = preg_replace($pattern, '', $lowercase_response);
    }
    
    // Replace double spaces caused by removals
    $lowercase_response = preg_replace('/\s+/', ' ', $lowercase_response);
    
    // Return trimmed response with original case
    return trim($lowercase_response);

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

// Filter out stopwords and keep meaningful words in the response
function filter_out_non_standard_words($response) {

    // List of stopwords that should be removed from the response
    global $stopWords;

    // Break the response into words
    $words = explode(' ', $response);

    // Filter out stopwords
    $filtered_words = array_filter($words, function($word) use ($stopWords) {
        // Clean up the word and check against our stopwords list
        $clean_word = strtolower(trim($word, ",.!?"));
        return !in_array($clean_word, $stopWords); // Keep the word if it's not in stopWords
    });

    // Join the filtered words back into a response
    return implode(' ', $filtered_words);

}

// Retrieve the Markov Chain from chunks and reassemble it
function getMarkovChainFromChunks() {

    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_markov_chain';

    // Fetch all chunks in order by chunk_index
    $results = $wpdb->get_results("SELECT chain_chunk, chunk_index FROM $table_name ORDER BY chunk_index ASC");

    // Error handling
    if ($wpdb->last_error) {
        prod_trace('ERROR', 'Error fetching chunks: ' . $wpdb->last_error);
        return null;
    }

    if (empty($results)) {
        prod_trace('NOTICE', 'No chunks found in the database.');
        return null;
    }

    // Initialize the final array for holding unserialized data
    $finalArray = [];

    // Process each chunk
    foreach ($results as $row) {

        // DIAG - Log the chunk being unserialized for debugging
        // back_trace('NOTICE', 'Processing chunk ' . $row->chunk_index . ' with length: ' . strlen($row->chain_chunk));
        // back_trace('NOTICE', 'Serialized chunk content (truncated): ' . substr($row->chain_chunk, 0, 100)); // Log first 100 chars of the chunk

        // Unserialize each chunk
        $unserializedChunk = @unserialize($row->chain_chunk);

        if ($unserializedChunk === false) {
            // Log the failure and check the serialized data format
            // back_trace('ERROR', 'Unserialization failed for chunk ' . $row->chunk_index . '. Check if the data format is correct.');
        } else {
            // Check if the unserialized data is an array
            if (!is_array($unserializedChunk)) {
                // back_trace('ERROR', 'Expected array, but got ' . gettype($unserializedChunk) . ' for chunk ' . $row->chunk_index);
            } else {
                // Merge unserialized data into the final array
                $finalArray = array_merge($finalArray, $unserializedChunk);
            }
        }
    }

    // Return the final reassembled Markov Chain array
    if (!empty($finalArray)) {
        // back_trace('NOTICE', 'Markov Chain fully reassembled. Length: ' . count($finalArray));
        return $finalArray;
    } else {
        // back_trace('ERROR', 'No valid data reassembled from chunks.');
        return null;
    }

}