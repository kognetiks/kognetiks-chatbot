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

// Generate a sentence using the Markov Chain
function generateMarkovText($startWords = [], $length = 100) {

    global $stopWords;

    // DIAG - Diagnostics - Ver 2.1.6
    back_trace( 'NOTICE', 'generateMarkovText - Start');
    back_trace( 'NOTICE', 'Requested Length: ' . $length);
    back_trace( 'NOTICE', 'Start Words: ' . implode(' ', $startWords));

    // Retrieve the chain length from the options table
    $chatbot_chatgpt_markov_chain_length = esc_attr(get_option('chatbot_chatgpt_markov_chain_length', 3));

    // Trim any leading or trailing whitespace from the start words
    $startWords = array_map('trim', $startWords);
    // Trim any punctuation from the start words
    $startWords = array_map(function($word) {
        return preg_replace('/[^\w\s]/', '', $word);
    }, $startWords);

    // Remove stopwords from the start words
    // $startWords = array_filter($startWords, function($word) {
    //     global $stopWords;
    //     return !in_array(strtolower($word), $stopWords);
    // });

    // Trim the start words to the chain length
    // $startWords = array_slice($startWords, -$chatbot_chatgpt_markov_chain_length);

    // DIAG - Diagnostics - Ver 2.1.6
    back_trace( 'NOTICE', 'Adjusted Start Words: ' . implode(' ', $startWords));

    // Retrieve the Markov Chain from the database
    // back_trace( 'NOTICE', 'Retrieving Markov Chain from the database.');
    $markovChain = getMarkovChainFromDatabase();

    // back_trace( 'NOTICE', 'Markov Chain retrieved from the database.');
    back_trace( 'NOTICE', 'Markov Chain Length: ' . count($markovChain));
    // How much memory is being used by the Markov Chain
    back_trace( 'NOTICE', 'Memory usage: ' . memory_get_usage());
    back_trace( 'NOTICE', 'Memory usage in megabytes: ' . round(memory_get_usage() / 1024 / 1024, 2) . 'M');

    // Check if the Markov Chain is empty or not
    if (empty($markovChain) || !is_array($markovChain)) {
        prod_trace('ERROR', 'No Markov Chain found.');
        return 'ERROR: No Markov Chain found.';
    }

    // Check if the length is valid
    if ($length < 1) {
        prod_trace('ERROR', 'Invalid length.');
        return 'ERROR: Invalid length.';
    }

    // Normalize the keys in the Markov Chain to increase the likelihood of a match
    $lowerKeys = array_change_key_case($markovChain, CASE_LOWER);

    // Normalize and handle start words
    if (!empty($startWords)) {

        // Lowercase and trim the start words to ensure matching
        $cleanStartWords = array_map('strtolower', array_map('trim', $startWords));

        // Get the Markov Chain length from the options table
        $chatbot_chatgpt_markov_chain_length = esc_attr(get_option('chatbot_chatgpt_markov_chain_length', 3));

        $foundKey = false; // Flag to check if a match was found

        // Ensure we always try shifting until fewer than the chain length words remain
        while (count($cleanStartWords) >= $chatbot_chatgpt_markov_chain_length) {

            // Take the last set of words that match the chain length from the current position
            $key = implode(' ', array_slice($cleanStartWords, -$chatbot_chatgpt_markov_chain_length));

            // DIAG - Diagnostics - Ver 2.1.6
            back_trace('NOTICE', 'Start words in while loop - $key: ' . $key);

            // Check if the key exists in the Markov chain
            if (isset($lowerKeys[$key])) {
                back_trace('NOTICE', 'Start words found in Markov Chain: ' . $key);
                $foundKey = true;
                break; // Exit the loop if found
            } else {
                back_trace('NOTICE', 'Start words not found, shifting right and trying again.');
                array_pop($cleanStartWords); // Shift right by removing the last word
            }
        }

        // Fallback to random key if no match is found
        if (!$foundKey) {
            back_trace('NOTICE', 'No matching start words found in Markov Chain, falling back to random key.');
            $key = array_keys($lowerKeys)[array_rand(array_keys($lowerKeys))];
        }

    } else {
        // Start with a random key if no start words provided
        $key = array_keys($lowerKeys)[array_rand(array_keys($lowerKeys))];
    }
        
    // Split the key into words to start building the response
    $words = explode(' ', $key);

    // Get the chain length from the options table
    $chainLength = intval(esc_attr(get_option('chatbot_chatgpt_markov_chain_length', 3)));

    // Generate the response text
    for ($i = 0; $i < $length; $i++) {

        if (isset($lowerKeys[$key]) && is_array($lowerKeys[$key])) {

            $nextWords = $lowerKeys[$key];

            if (empty($nextWords)) {
                break; // Break the loop if no next words are found
            }

            $nextWord = $nextWords[array_rand($nextWords)];
            // back_trace( 'NOTICE', 'Next word selected: ' . $nextWord);

            // Check if the next word is a duplicate of the previous word
            if (end($words) === $nextWord) {
                continue; // Skip if the word is the same as the previous word
            }

            $words[] = $nextWord;

            // Build the new key using the last three words generated (for better coherence)
            $keyWords = array_slice($words, count($words) - $chainLength, $chainLength); // Get the last three words
            $key = implode(' ', $keyWords);

        } else {
            // Fallback: If no matching key is found, pick a new random key
            // back_trace( 'NOTICE', 'Key not found, falling back to random key.');
            $key = array_keys($lowerKeys)[array_rand(array_keys($lowerKeys))];
        }
    }

    // Final sentence building and punctuation check
    $response = implode(' ', $words);

    // Strip any remaining HTML tags
    $response = wp_strip_all_tags($response);

    // Ensure the message ends with a period, unless it ends with other punctuation
    if (!preg_match('/[.!?]$/', $response)) {
        $response .= '.';
    }

    // Capitalize the first letter if needed
    if (!ctype_upper($response[0])) {
        $response = ucfirst($response);
        // back_trace( 'NOTICE', 'Response capitalized: ' . $response);
    }

    // Limit the response to max_tokens characters for brevity (adjust as needed)
    $max_tokens = esc_attr(get_option('chatbot_chatgpt_max_tokens_setting', 500));
    if (strlen($response) > $max_tokens) {
        $response = substr($response, 0, ($max_tokens - 3)) . '...';
        // back_trace( 'NOTICE', 'Response truncated: ' . $response);
    }

    // Apply grammar cleanup and nonsense filtering
    $response = clean_up_markov_chain_response($response);
    // back_trace( 'NOTICE', 'Response after cleanup: ' . $response);

    // Fix common grammar issues
    $response = fix_common_grammar_issues($response);
    // back_trace( 'NOTICE', 'Response after grammar fix: ' . $response);

    // Remove nonsense phrases
    // $response = remove_nonsense_phrases($response);
    // back_trace( 'NOTICE', 'Response after nonsense removal: ' . $response);

    // Add punctuation before uppercase words
    $response = preg_replace('/([a-z]) ([A-Z])/', '$1. $2', $response);
    // back_trace( 'NOTICE', 'Response after punctuation fix: ' . $response);

    // FIXME - TEMP IGNORE - Ver 2.1.6 - 2024-09-19
    // Filter out non-standard words
    // $response = filter_out_non_standard_words($response);
    // back_trace( 'NOTICE', 'Response after word filtering: ' . $response);

    // DIAG - Diagnostics - Ver 2.1.6
    // back_trace( 'NOTICE', 'generateMarkovText - End');

    return $response; // Return the generated and cleaned-up response

}

// Retrieve the Markov Chain from the database
function getMarkovChainFromDatabase() {

    // DIAG - Diagnostics - Start
    back_trace('NOTICE', 'getMarkovChainFromDatabase - Start');

    // FIXME - FORCE REBUILD - Ver 2.1.6 - 2024-09-19
    $force_markov_chain_rebuild = get_option('chatbot_chatgpt_force_markov_chain_rebuild', 'No');
    if ($force_markov_chain_rebuild = 'Yes') {
        back_trace('NOTICE', 'Forcing Markov Chain rebuild.');
        // update_option('chatbot_chatgpt_markov_chain_length', 3);
        update_option('chatbot_chatgpt_markov_last_updated', '2000-01-01 00:00:00');
        update_option('chatbot_chatgpt_force_markov_chain_rebuild', 'No');
        $markovChain = null;
    }

    // Retrieve the Markov Chain from chunks
    $markovChain = getMarkovChainFromChunks();

    // Check if we successfully retrieved the Markov Chain
    if (!empty($markovChain)) {
        back_trace('NOTICE', 'Markov Chain Length: ' . count($markovChain));
        // How much memory is being used by the Markov Chain
        back_trace( 'NOTICE', 'Memory usage: ' . memory_get_usage());
        back_trace( 'NOTICE', 'Memory usage in megabytes: ' . round(memory_get_usage() / 1024 / 1024, 2));
        return $markovChain;  // Return the valid Markov Chain
    }

    // If no Markov Chain found, rebuild it
    back_trace('NOTICE', 'No Markov Chain found. Rebuilding.');

    // Run the Markov Chain building and saving process
    runMarkovChatbotAndSaveChain();

    // After rebuilding, attempt to fetch it again
    $markovChain = getMarkovChainFromChunks();

    // Check if rebuilding was successful
    if (!empty($markovChain)) {
        back_trace('NOTICE', 'Markov Chain Length after rebuild: ' . count($markovChain));
        return $markovChain;  // Return the rebuilt Markov Chain
    }

    // If rebuild fails, log the issue and return null
    back_trace('NOTICE', 'Failed to rebuild the Markov Chain.');
    return null;  // Return null to indicate failure

}

// Clean up the Markov Chain response for better readability
function clean_up_markov_chain_response($response) {

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

    // Upper case the first letter of the response
    $response = ucfirst($response);

    // back_trace( 'NOTICE', 'After nonsense filtering: ' . $response);

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

        // DIAG - Diagnostics - Ver 2.1.6
        // back_trace('NOTICE', 'Processing chunk ' . $row->chunk_index . ' with length: ' . strlen($row->chain_chunk));

        // Unserialize each chunk
        $unserializedChunk = @unserialize($row->chain_chunk);

        if ($unserializedChunk === false) {

            back_trace('NOTICE', 'Unserialization failed for chunk ' . $row->chunk_index);

        } else {

            // Log successful unserialization
            // back_trace('NOTICE', 'Chunk ' . $row->chunk_index . ' unserialized successfully.');

            // Merge unserialized data into the final array
            $finalArray = array_merge($finalArray, $unserializedChunk);

            // back_trace('NOTICE', 'Final array size after chunk ' . $row->chunk_index . ': ' . count($finalArray));

        }
    }

    // Return the final reassembled Markov Chain array
    if (!empty($finalArray)) {

        // DIAG - Diagnostics - Ver 2.1.6
        // back_trace('NOTICE', 'Markov Chain fully reassembled. Length: ' . count($finalArray));
        return $finalArray;

    } else {

        // DIAG - Diagnostics - Ver 2.1.6
        back_trace('NOTICE', 'No valid data reassembled from chunks.');
        return null;

    }

}