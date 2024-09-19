<?php
/**
 * Kognetiks Chatbot for WordPress - Markove Chain - Ver 2.1.6
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

// Extract the published content
function getAllPublishedContent() {

    // DIAG - Diagnostics - Ver 2.1.6
    // back_trace( 'NOTICE', 'getAllPublishedContent - Start');

    $last_updated = getMarkovChainLastUpdated();

    // Get the last updated date
    // $last_updated = '2000-01-01 00:00:00'; // FIXME - Remove this line
    $last_updated = esc_attr(get_option('chatbot_chatgpt_markov_last_updated', '2000-01-01 00:00:00'));

    // Query for posts and pages after the last updated date
    $args = array(
        'post_type' => array('post', 'page'),
        'post_status' => 'publish',
        'date_query' => array(
            array(
                'after' => $last_updated, // Only get content after the last update
            ),
        ),
        'posts_per_page' => -1 // Get all posts and pages
    );

    // Get published posts/pages
    $query = new WP_Query($args);
    $content = '';

    // Loop through each post or page
    if ($query->have_posts()) {

        while ($query->have_posts()) {
            $query->the_post();
            $post_content = get_the_content();

            // Ensure $post_content is a string
            if (is_object($post_content)) {
                $post_content = wp_kses_post($post_content);
            }

            // Clean up the post content for better Markov Chain processing
            $clean_content = clean_up_training_data($post_content);
            // Add cleaned post content to the overall content
            $content .= ' ' . get_the_title() . ' ' . $clean_content;

        }

    } else {

        // back_trace( 'NOTICE', 'getAllPublishedContent - No posts found after: ' . $last_updated);

    }

    // Reset post data
    wp_reset_postdata();

    // Fetch all comments after the last updated date
    $comments = get_comments(array(
        'status' => 'approve',
        'date_query' => array(
            array(
                'after' => $last_updated,
                'inclusive' => true, // Include comments from the exact last_updated date
            ),
        ),
    ));

    // Add cleaned comment content
    foreach ($comments as $comment) {

        // Clean up the post content for better Markov Chain processing
        $clean_comment = clean_up_training_data($comment->comment_content);

        // Add cleaned comment content to the overall content
        $content .= ' ' . $clean_comment;

    }

    // Update the last updated timestamp
    updateMarkovChainTimestamp();

    // DIAG - Diagnostics - Ver 2.1.6
    // back_trace( 'NOTICE', 'getAllPublishedContent - End');

    return $content;

}

// Build the Markov Chain and save it in chunks
function buildMarkovChain($content) {

    // DIAG - Diagnostics - Ver 2.1.6
    // back_trace( 'NOTICE', 'buildMarkovChainAndSaveInChunks - Start with content: ' . substr($content, 0, 500)); // Log the first 500 characters of content

    // Clear existing chunks
    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_markov_chain';
    $wpdb->query("DELETE FROM $table_name");

    $chainLength = esc_attr(get_option('chatbot_chatgpt_markov_chain_length', 3)); // Default chain length is 3
    // $words = preg_split('/\s+/', preg_replace('/[^\w\s]/', '', $content)); // Split content into words and remove punctuation
    $words = preg_split('/\s+/', preg_replace("/[^\w\s']/u", '', $content)); // Keeps apostrophes

    $markovChain = [];
    $chunkSizeLimit = 10000;  // Set a limit for when to save a chunk
    $chunkData = [];  // This will store the temporary chunk data
    $currentChunk = '';

    for ($i = 0; $i < count($words) - $chainLength; $i++) {

        // Avoid out-of-bounds error
        if (!isset($words[$i + $chainLength])) {
            continue; // Skip iteration if the next word doesn't exist
        }

        $key = implode(' ', array_slice($words, $i, $chainLength)); // Build key based on chain length
        $nextWord = $words[$i + $chainLength];
    
        if (!isset($markovChain[$key])) {
            $markovChain[$key] = [];
        }
    
        // Add to both $markovChain and the temporary $chunkData
        $markovChain[$key][] = $nextWord;
        $chunkData[$key][] = $nextWord;
    
        // Serialize the temporary chunk data after it reaches the chunkSizeLimit
        $currentChunk = serialize($chunkData);
    
        if (strlen($currentChunk) >= $chunkSizeLimit || $i == count($words) - $chainLength - 1) {
            // Save this chunk
            saveMarkovChainChunk($currentChunk, floor($i / $chainLength));
    
            // Clear only the temporary chunk data, keep $markovChain intact
            $chunkData = [];
            // back_trace( 'NOTICE', 'Chunk data cleared at iteration ' . $i);
        }
    }

    // DIAG - Diagnostics - Ver 2.1.6
    // back_trace( 'NOTICE', 'buildMarkovChainAndSaveInChunks - End');

}

// Save a single chunk of the Markov Chain to the database
function saveMarkovChainChunk($chunk, $chunkIndex) {

    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_markov_chain';

    // back_trace( 'NOTICE', 'Chunk content being saved (truncated): ' . substr($chunk, 0, 100));  // Log first 100 chars of chunk
    // back_trace( 'NOTICE', 'Full Chunk content being saved: ' . substr($chunk, 0, 500));

    // back_trace( 'NOTICE', '$chunkIndex: ' . $chunkIndex);
    // back_trace( 'NOTICE', '$chuck: ' . strlen($chunk));
    $update_date = date('Y-m-d H:i:s');
    // back_trace('NOTICE', 'date/time: ' . $update_date);

    // Insert the chunk into the database
    $wpdb->query(
        $wpdb->prepare(
            "INSERT INTO $table_name (chunk_index, chain_chunk, last_updated) VALUES (%d, %s, %s)",
            $chunkIndex, $chunk, $update_date
        )
    );

    // Handle errors
    if ($wpdb->last_error) {
        // back_trace( 'NOTICE', 'Error saving chunk ' . $chunkIndex . ': ' . $wpdb->last_error);
        // back_trace( 'NOTICE', 'Query: ' . $wpdb->last_query);
    } else {
        // back_trace( 'NOTICE', 'Chunk ' . $chunkIndex . ' saved successfully.');
        // back_trace( 'NOTICE', 'Query: ' . $wpdb->last_query);
    }

}

// Generate a sentence using the Markov Chain
function generateMarkovText($startWords = [], $length = 100) {

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

    // Trim the start words to the chain length
    // $startWords = array_slice($startWords, -$chatbot_chatgpt_markov_chain_length);

    // DIAG - Diagnostics - Ver 2.1.6
    back_trace( 'NOTICE', 'Adjusted Start Words: ' . implode(' ', $startWords));

    // Retrieve the Markov Chain from the database
    // back_trace( 'NOTICE', 'Retrieving Markov Chain from the database.');
    $markovChain = getMarkovChainFromDatabase();

    // back_trace( 'NOTICE', 'Markov Chain retrieved from the database.');
    back_trace( 'NOTICE', 'Markov Chain Length: ' . count($markovChain));

    // Check if the Markov Chain is empty or not
    if (empty($markovChain) || !is_array($markovChain)) {
        return 'No Markov Chain found.';
    } else {
        // back_trace( 'NOTICE', 'Markov Chain found.');
    }

    // Check if the length is valid
    if ($length < 1) {
        return 'Invalid length.';
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

            // Take the first set of words that match the chain length from the current position
            $key = implode(' ', array_slice($cleanStartWords, 0, $chatbot_chatgpt_markov_chain_length));

            // DIAG - Diagnostics - Ver 2.1.6
            back_trace('NOTICE', 'Start words in while loop - $key: ' . $key);

            // Check if the key exists in the Markov chain
            if (isset($lowerKeys[$key])) {
                back_trace('NOTICE', 'Start words found in Markov Chain: ' . $key);
                $foundKey = true;
                break; // Exit the loop if found
            } else {
                back_trace('NOTICE', 'Start words not found, shifting left and trying again.');
                array_shift($cleanStartWords); // Shift left by removing the first word
                // array_pop($cleanStartWords); // Shift right by removing the last word
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
            $keyWords = array_slice($words, count($words) - 3, 3); // Get the last three words
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

// Run the Markov Chain algorithm
function runMarkovChatbot() {

    // DIAG - Diagnostics - Ver 2.1.6
    // back_trace( 'NOTICE', 'runMarkovChatbot - Start' );

    // Step 1: Get all the published content
    $content = getAllPublishedContent();

    // Step 2: Build the Markov Chain from the content
    $markovChain = buildMarkovChain($content);

    // Step 3: Generate text using the Markov Chain
    $response = generateMarkovText($markovChain, 50);

    // DIAG - Diagnostics - Ver 2.1.6
    // back_trace( 'NOTICE', 'runMarkovChatbot - End' );

    return $response;

}

// Save the Markov Chain in the database using chunks
function saveMarkovChainToDatabase($markovChain) {

    // DIAG - Diagnostics - Ver 2.1.6
    // back_trace( 'NOTICE', 'saveMarkovChainToDatabase - Start');

    // Save the Markov Chain in chunks using the function saveMarkovChainInChunks
    saveMarkovChainInChunks($markovChain);

    // DIAG - Diagnostics - Ver 2.1.6
    // back_trace( 'NOTICE', 'saveMarkovChainToDatabase - End');

}

// Retrieve the Markov Chain from the database
function getMarkovChainFromDatabase() {

    // DIAG - Diagnostics - Ver 2.1.6
    // back_trace( 'NOTICE', 'getMarkovChainFromDatabase - Start');

    // FIXME - FORCE REBUILD - Ver 2.1.6 - 2024-09-19
    back_trace( 'NOTICE', 'Forcing Markov Chain rebuild.');
    update_option('chatbot_chatgpt_markov_chain_length', 3);
    update_option('chatbot_chatgpt_markov_last_updated', '2000-01-01 00:00:00');
    $markovChain = null;

    // Retrieve the Markov Chain from chunks
    $markovChain = getMarkovChainFromChunks();

    if ($markovChain) {

        // Length of the Markov Chain
        back_trace( 'NOTICE', 'Markov Chain Length: ' . count($markovChain));

        $serializedChain = serialize($markovChain);
        back_trace( 'NOTICE', 'Markov Chain Length: ' . strlen($serializedChain));
      
        // back_trace( 'NOTICE', 'getMarkovChainFromDatabase - End');
        return $markovChain;

    } else {

        // If no Markov Chain found, rebuild it
        // back_trace( 'NOTICE', 'getMarkovChainFromDatabase - No Markov Chain found, rebuilding.');

        runMarkovChatbotAndSaveChain();

        // After rebuilding, attempt to fetch it again
        $markovChain = getMarkovChainFromChunks();
        
            // Length of the Markov Chain
            back_trace( 'NOTICE', 'Markov Chain Length: ' . count($markovChain));

        $serializedChain = serialize($markovChain);
        back_trace( 'NOTICE', 'Markov Chain Length: ' . strlen($serializedChain));

        if ($markovChain) {
            // back_trace( 'NOTICE', 'getMarkovChainFromDatabase - Markov Chain rebuilt and saved.');
            return $markovChain;
        } else {
            // back_trace( 'NOTICE', 'getMarkovChainFromDatabase - Failed to rebuild the Markov Chain.');
            return null; // Return null to indicate the failure
        }

    }

}

// Check if the Markov Chain exists in the database
function createMarkovChainTable() {

    // DIAG - Diagnostics - Ver 2.1.6
    // back_trace( 'NOTICE', 'createMarkovChainTable - Start' );

    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_markov_chain';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        chunk_index int(11) NOT NULL,
        chain_chunk LONGTEXT NOT NULL,
        last_updated datetime NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY chunk_index (chunk_index)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    if (!empty($wpdb->last_error)) {

        prod_trace( 'ERROR', 'Database error during table creation: ' . $wpdb->last_error);

    } else {

        prod_trace( 'NOTICE', 'Markov Chain table created/updated successfully.');

    }

    // DIAG - Diagnostics - Ver 2.1.6
    // back_trace( 'NOTICE', 'createMarkovChainTable - End' );

}
register_activation_hook(__FILE__, 'createMarkovChainTable');

function updateMarkovChainTimestamp() {
    update_option('chatbot_chatgpt_markov_last_updated', current_time('mysql'));
}

function getMarkovChainLastUpdated() {
    return get_option('chatbot_chatgpt_markov_last_updated', '2000-01-01 00:00:00');
}


// Run the Markov Chain algorithm and store the chain in the database only if new content exists
function runMarkovChatbotAndSaveChain() {

    // DIAG - Diagnostics - Ver 2.1.6
    // back_trace( 'NOTICE', 'runMarkovChatbotAndSaveChain - Start');

    // Step 0: Check if the Markov Chain table exists
    createMarkovChainTable();

    // Step 1: Get the last updated timestamp for the Markov Chain
    $last_updated = get_option('chatbot_chatgpt_markov_last_updated', '2000-01-01 00:00:00');

    // Step 2: Get all published content (posts, pages, and comments) that have been updated after the last Markov Chain update
    $content = getAllPublishedContent($last_updated);

    if (!empty($content)) {

        // Step 3: Build the Markov Chain from the new content
        $markovChain = buildMarkovChain($content);

        // Step 4: Save the new chain to the database
        saveMarkovChainToDatabase($markovChain);

        // Step 5: Update the last updated timestamp
        update_option('chatbot_chatgpt_markov_last_updated', current_time('mysql'));
        
        // DIAG - Diagnostics - Ver 2.1.6
        // back_trace( 'NOTICE', 'Markov Chain updated and saved to the database.');

    } else {

        // DIAG - Diagnostics - Ver 2.1.6
        // back_trace( 'NOTICE', 'No new content since last update. Markov Chain not rebuilt.');

    }

    // DIAG - Diagnostics - Ver 2.1.6
    // back_trace( 'NOTICE', 'runMarkovChatbotAndSaveChain - End');

}
// Hook the function to run after WordPress is fully loaded
add_action('wp_loaded', 'runMarkovChatbotAndSaveChain');

// Clean up inbound text for better Markov Chain processing
function clean_up_training_data($content) {

    $clean_content = $content;

    do {

        $previous_clean_content = $clean_content;

        // Replace &nbsp; with a regular space
        if (is_string($clean_content)) {
            // Replace &nbsp; with a regular space
            $clean_content = str_replace('&nbsp;', ' ', $clean_content);
        } else {
            // Handle the case where $clean_content is not a string
            back_trace( 'ERROR', 'Expected $clean_content to be a string, but got ' . gettype($clean_content));
        }

        // Swap <br> tags with spaces
        $clean_content = preg_replace('/<br\s*\/?>/', ' ', $clean_content);

        // Swap <p> tags with spaces
        $clean_content = preg_replace('/<p\s*\/?>/', ' ', $clean_content);

        // Strip HTML tags, shortcodes, and comments, and clean up excessive whitespace
        $clean_content = wp_strip_all_tags(strip_shortcodes($clean_content));

        // Remove HTML comments
        $clean_content = preg_replace('/<!--.*?-->/', '', $clean_content);

        // Collapse multiple spaces
        $clean_content = preg_replace('/\s+/', ' ', $clean_content);

    } while ($clean_content !== $previous_clean_content); // Loop until no more changes are made

    return trim($clean_content); // Return with any leading/trailing whitespace removed

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

// Save the Markov Chain in chunks to avoid exceeding database limits
function saveMarkovChainInChunks($markovChain) {

    // DIAG - Diagnostics - Ver 2.1.6
    // back_trace( 'NOTICE', 'saveMarkovChainInChunks - Start');

    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_markov_chain';

    // Serialize the Markov Chain
    $serializedChain = serialize($markovChain);
    $chunkSize = 10000; // 10000-byte Chunk size limit

    if ($serializedChain === false) {
        // back_trace( 'ERROR', 'Serialization failed for Markov Chain data.');
        return; // Exit the function if serialization fails
    } else {
        // back_trace( 'NOTICE', 'Serialization successful.');
    }

    // Split the chain into chunks
    $chunks = str_split($serializedChain, $chunkSize);

    // Clear existing chunks
    // $wpdb->query("DELETE FROM $table_name");

    // Handle errors when clearing the table
    if ($wpdb->last_error) {
        // back_trace( 'ERROR', 'saveMarkovChainInChunks - Error clearing existing chunks: ' . $wpdb->last_error);
        return; // Exit function on failure
    }

    // Insert each chunk
    foreach ($chunks as $index => $chunk) {

        // Log the length of each serialized chunk
        // back_trace( 'NOTICE', 'Chunk ' . $index . ' length: ' . strlen($chunk));

        // Skip empty or invalid chunks
        if (empty($chunk) || $chunk === 'N;') {
            // back_trace('NOTICE', 'Skipping empty or null chunk at index ' . $index);
            continue; // Skip to the next chunk
        }

        $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO $table_name (chunk_index, chain_chunk, last_updated) VALUES (%d, %s, NOW())",
                $index, $chunk
            )
        );

        $saved_chunk = $wpdb->get_var($wpdb->prepare("SELECT chain_chunk FROM $table_name WHERE chunk_index = %d", $index));
        if ($saved_chunk !== $chunk) {
            prod_trace( 'ERROR', 'Data integrity issue: Saved chunk does not match the original.');
        }

        // Log error if insertion fails
        if ($wpdb->last_error) {

            // back_trace( 'ERROR', 'Error saving chunk ' . $index . ': ' . $wpdb->last_error . ' | Query: ' . $wpdb->last_query);

        } else {

            // back_trace( 'NOTICE', 'Chunk ' . $index . ' saved successfully.');
        }

    }

    // DIAG - Diagnostics - Ver 2.1.6
    // back_trace( 'NOTICE', 'saveMarkovChainInChunks - End');

}

// Retrieve the Markov Chain from chunks and reassemble it
function getMarkovChainFromChunks() {
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_markov_chain';

    // Fetch all chunks in order by chunk_index
    $results = $wpdb->get_results("SELECT chain_chunk, chunk_index FROM $table_name ORDER BY chunk_index ASC");

    // Error handling
    if ($wpdb->last_error) {
        back_trace('ERROR', 'Error fetching chunks: ' . $wpdb->last_error);
        return null;
    }

    if (empty($results)) {
        back_trace('NOTICE', 'No chunks found in the database.');
        return null;
    }

    // Initialize the final array for holding unserialized data
    $finalArray = [];

    // Process each chunk
    foreach ($results as $row) {
        back_trace('NOTICE', 'Processing chunk ' . $row->chunk_index . ' with length: ' . strlen($row->chain_chunk));

        // Unserialize each chunk
        $unserializedChunk = @unserialize($row->chain_chunk);

        if ($unserializedChunk === false) {
            back_trace('NOTICE', 'Unserialization failed for chunk ' . $row->chunk_index);
        } else {
            // Log successful unserialization
            back_trace('NOTICE', 'Chunk ' . $row->chunk_index . ' unserialized successfully.');

            // Merge unserialized data into the final array
            $finalArray = array_merge($finalArray, $unserializedChunk);

            back_trace('NOTICE', 'Final array size after chunk ' . $row->chunk_index . ': ' . count($finalArray));
        }
    }

    // Return the final reassembled Markov Chain array
    if (!empty($finalArray)) {
        back_trace('NOTICE', 'Markov Chain fully reassembled. Length: ' . count($finalArray));
        return $finalArray;
    } else {
        back_trace('NOTICE', 'No valid data reassembled from chunks.');
        return null;
    }
}

