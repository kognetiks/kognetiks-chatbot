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
    back_trace( 'NOTICE', 'getAllPublishedContent - Start');

    $last_updated = getMarkovChainLastUpdated();

    // Get the last updated date
    $last_updated = '2000-01-01 00:00:00'; // FIXME - Remove this line
    // $last_updated = esc_attr(get_option('chatbot_chatgpt_markov_last_updated', '2000-01-01 00:00:00'));

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
            // Strip HTML tags, shortcodes, and comments, and clean up excessive whitespace
            $clean_content = wp_strip_all_tags(strip_shortcodes($post_content));
            $clean_content = preg_replace('/<!--.*?-->/', '', $clean_content); // Remove HTML comments
            $clean_content = preg_replace('/\s+/', ' ', $clean_content); // Collapse multiple spaces
            $content .= ' ' . get_the_title() . ' ' . $clean_content;
        }
    } else {
        back_trace( 'NOTICE', 'getAllPublishedContent - No posts found after: ' . $last_updated);
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
        $clean_comment = wp_strip_all_tags($comment->comment_content);
        $clean_comment = preg_replace('/\s+/', ' ', $clean_comment); // Collapse multiple spaces
        $content .= ' ' . $clean_comment;
    }

    // Update the last updated timestamp
    updateMarkovChainTimestamp();

    // DIAG - Diagnostics - Ver 2.1.6
    back_trace( 'NOTICE', 'getAllPublishedContent - End');

    return $content;

}

// Build the Markov Chain and save it in chunks
function buildMarkovChain($content) {

    // DIAG - Diagnostics - Ver 2.1.6
    back_trace('NOTICE', 'buildMarkovChainAndSaveInChunks - Start with content: ' . substr($content, 0, 500)); // Log the first 500 characters of content

    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_markov_chain';

    // Ensure the table is cleared before starting
    $wpdb->query("DELETE FROM $table_name");

    // DIAG - Clear any previous chain data
    back_trace('NOTICE', 'Previous Markov Chain data cleared.');

    $chainLength = esc_attr(get_option('chatbot_chatgpt_markov_chain_length', 3)); // Default chain length is 3
    $words = preg_split('/\s+/', preg_replace('/[^\w\s]/', '', $content)); // Split content into words and remove punctuation

    $markovChain = [];
    $chunkSizeLimit = 5000;  // Set a limit for when to save a chunk
    $chunkData = [];  // This will store the temporary chunk data
    $currentChunk = '';

    for ($i = 0; $i < count($words) - $chainLength; $i++) {

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
        }

    }

    // DIAG - Diagnostics - Ver 2.1.6
    back_trace('NOTICE', 'buildMarkovChainAndSaveInChunks - End');

}

// Save a single chunk of the Markov Chain to the database
function saveMarkovChainChunk($chunk, $chunkIndex) {

    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_markov_chain';

    back_trace('NOTICE', 'Chunk content being saved (truncated): ' . substr($chunk, 0, 100));  // Log first 100 chars of chunk
    // back_trace('NOTICE', 'Full Chunk content being saved: ' . substr($chunk, 0, 500));

    // Insert the chunk into the database
    $wpdb->query(
        $wpdb->prepare(
            "INSERT INTO $table_name (chunk_index, chain_chunk, last_updated) VALUES (%d, %s, NOW())",
            $chunkIndex, $chunk
        )
    );

    // Handle errors
    if ($wpdb->last_error) {
        back_trace( 'NOTICE', 'Error saving chunk ' . $chunkIndex . ': ' . $wpdb->last_error);
    } else {
        back_trace('NOTICE', 'Chunk ' . $chunkIndex . ' saved successfully.');
    }

}

// Generate a sentence using the Markov Chain
function generateMarkovText($markovChain, $length = 100, $startWords = []) {

    // DIAG - Diagnostics - Ver 2.1.6
    back_trace( 'NOTICE', 'generateMarkovText - Start');
    back_trace( 'NOTICE', 'Markov Chain Length: ' . count($markovChain));
    back_trace( 'NOTICE', 'Requested Length: ' . $length);
    back_trace( 'NOTICE', 'Start Words: ' . implode(' ', $startWords));

    // Check if the Markov Chain is empty or not
    if (empty($markovChain) || !is_array($markovChain)) {
        return 'No Markov Chain found.';
    } else {
        back_trace( 'NOTICE', 'Markov Chain found.');
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
        // Turn the length into an negative integer
        $chatbot_chatgpt_markov_chain_length = -absint($chatbot_chatgpt_markov_chain_length);

        $key = implode(' ', array_slice($cleanStartWords, $chatbot_chatgpt_markov_chain_length)); // Get the last three words for better matching

        // DIAG - Diagnostics - Ver 2.1.6
        back_trace( 'NOTICE', 'Start words after cleanup - $key: ' . $key);
        
        // Check if the key exists in the Markov chain
        if (!isset($lowerKeys[$key])) {
            back_trace( 'NOTICE', 'Start words not found in Markov Chain, falling back to random key.');
            // Fallback to a random key if start words are not found
            $key = array_keys($lowerKeys)[array_rand(array_keys($lowerKeys))];
        } else {
            back_trace( 'NOTICE', 'Start words found in Markov Chain.');
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
            back_trace( 'NOTICE', 'Next word selected: ' . $nextWord);

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
            back_trace( 'NOTICE', 'Key not found, falling back to random key.');
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
    }

    // Limit the response to 500 characters for brevity (adjust as needed)
    if (strlen($response) > 500) {
        $response = substr($response, 0, 497) . '...';
    }

    // Apply grammar cleanup and nonsense filtering
    $response = clean_up_markov_chain_response($response); // Clean up response
    back_trace( 'NOTICE', 'Response after cleanup: ' . $response);

    $response = fix_common_grammar_issues($response); // Fix common grammar issues
    back_trace( 'NOTICE', 'Response after grammar fix: ' . $response);

    $response = remove_nonsense_phrases($response); // Remove nonsense phrases
    back_trace( 'NOTICE', 'Response after nonsense removal: ' . $response);

    $response = filter_out_non_standard_words($response); // Filter out non-standard words
    back_trace( 'NOTICE', 'Response after word filtering: ' . $response);

    // DIAG - Diagnostics - Ver 2.1.6
    back_trace( 'NOTICE', 'generateMarkovText - End');

    return $response; // Return the generated and cleaned-up response

}

// Run the Markov Chain algorithm
function runMarkovChatbot() {

    // DIAG - Diagnostics - Ver 2.1.6
    back_trace( 'NOTICE', 'runMarkovChatbot - Start' );

    // Step 1: Get all the published content
    $content = getAllPublishedContent();

    // Step 2: Build the Markov Chain from the content
    $markovChain = buildMarkovChain($content);

    // Step 3: Generate text using the Markov Chain
    $response = generateMarkovText($markovChain, 50);

    // DIAG - Diagnostics - Ver 2.1.6
    back_trace( 'NOTICE', 'runMarkovChatbot - End' );

    return $response;

}

// Store the Markov Chain in the database
// function saveMarkovChainToDatabase($markovChain) {

//     // DIAG - Diagnostics - Ver 2.1.6
//     back_trace( 'NOTICE', 'saveMarkovChainToDatabase - Start' );

//     global $wpdb;
//     $table_name = $wpdb->prefix . 'chatbot_chatgpt_markov_chain';
    
//     // Serialize the array
//     $serializedChain = serialize($markovChain);
    
//     // Insert or update the chain in the database
//     $wpdb->query(
//         $wpdb->prepare(
//             "INSERT INTO $table_name (chain, last_updated) VALUES (%s, NOW()) ON DUPLICATE KEY UPDATE chain = %s, last_updated = NOW()",
//             $serializedChain, $serializedChain
//         )
//     );

//     // DIAG - Diagnostics - Ver 2.1.6
//     back_trace( 'NOTICE', 'saveMarkovChainToDatabase - End' );

// }

// Save the Markov Chain in the database using chunks
function saveMarkovChainToDatabase($markovChain) {

    // DIAG - Diagnostics - Ver 2.1.6
    back_trace( 'NOTICE', 'saveMarkovChainToDatabase - Start');

    // Save the Markov Chain in chunks using the function saveMarkovChainInChunks
    saveMarkovChainInChunks($markovChain);

    // DIAG - Diagnostics - Ver 2.1.6
    back_trace( 'NOTICE', 'saveMarkovChainToDatabase - End');

}

// Retrieve the Markov Chain from the database
function getMarkovChainFromDatabase() {

    // DIAG - Diagnostics - Ver 2.1.6
    back_trace( 'NOTICE', 'getMarkovChainFromDatabase - Start');

    // Retrieve the Markov Chain from chunks
    $markovChain = getMarkovChainFromChunks();

    if ($markovChain) {

        back_trace( 'NOTICE', 'getMarkovChainFromDatabase - End');
        return $markovChain;

    } else {

        // If no Markov Chain found, rebuild it
        back_trace( 'NOTICE', 'getMarkovChainFromDatabase - No Markov Chain found, rebuilding.');

        runMarkovChatbotAndSaveChain();

        // After rebuilding, attempt to fetch it again
        $markovChain = getMarkovChainFromChunks();

        if ($markovChain) {
            back_trace( 'NOTICE', 'getMarkovChainFromDatabase - Markov Chain rebuilt and saved.');
            return $markovChain;
        } else {
            back_trace( 'NOTICE', 'getMarkovChainFromDatabase - Failed to rebuild the Markov Chain.');
            return null; // Return null to indicate the failure
        }

    }

}

// Check if the Markov Chain exists in the database
function createMarkovChainTable() {

    // DIAG - Diagnostics - Ver 2.1.6
    back_trace( 'NOTICE', 'createMarkovChainTable - Start' );

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

    // DIAG - Diagnostics - Ver 2.1.6
    back_trace( 'NOTICE', 'createMarkovChainTable - End' );

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
    back_trace( 'NOTICE', 'runMarkovChatbotAndSaveChain - Start');

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
        back_trace( 'NOTICE', 'Markov Chain updated and saved to the database.');

    } else {

        // DIAG - Diagnostics - Ver 2.1.6
        back_trace( 'NOTICE', 'No new content since last update. Markov Chain not rebuilt.');

    }

    // DIAG - Diagnostics - Ver 2.1.6
    back_trace( 'NOTICE', 'runMarkovChatbotAndSaveChain - End');

}
// Hook the function to run after WordPress is fully loaded
add_action('wp_loaded', 'runMarkovChatbotAndSaveChain');

// Clean up the Markov Chain response for better readability
function clean_up_markov_chain_response($response) {

    // Step 1: Capitalize the first letter of each sentence
    $response = preg_replace_callback('/(?:^|\.\s+)(\w)/', function($matches) {
        return strtoupper($matches[1]);
    }, trim($response));

    back_trace( 'NOTICE', 'After capitalization: ' . $response);

    // Step 2: Add punctuation at the end if missing
    if (!preg_match('/[.!?]$/', $response)) {
        $response .= '.'; // Add a period if no punctuation at the end
    }
    
    back_trace( 'NOTICE', 'After punctuation check: ' . $response);

    // Step 3: Remove extra spaces
    $response = preg_replace('/\s+/', ' ', $response); // Replace multiple spaces with a single space

    back_trace( 'NOTICE', 'After space cleanup: ' . $response);

    // Step 4: Basic punctuation cleanup
    // Remove spaces before punctuation, ensure space after punctuation
    $response = preg_replace('/\s+([?.!,])/', '$1', $response); // Remove space before punctuation
    $response = preg_replace('/([?.!,])([^\s?.!,])/', '$1 $2', $response); // Ensure space after punctuation

    back_trace( 'NOTICE', 'After punctuation spacing cleanup: ' . $response);

    // Step 5: Fix common grammar errors
    $response = fix_common_grammar_issues($response);

    back_trace( 'NOTICE', 'After grammar fixes: ' . $response);

    // Step 6: Remove or replace nonsense words/phrases
    $response = remove_nonsense_phrases($response);

    back_trace( 'NOTICE', 'After nonsense filtering: ' . $response);

    return $response;
}

// Remove nonsense words or phrases from the response
function remove_nonsense_phrases($response) {

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
    // Example: Fix "is are" to just "is" or "are"
    $response = preg_replace('/\bis are\b/', 'are', $response);
    
    // Example: Replace "has have" with just "has"
    $response = preg_replace('/\bhas have\b/', 'has', $response);
    
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
    back_trace( 'NOTICE', 'saveMarkovChainInChunks - Start');

    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_markov_chain';

    // Serialize the Markov Chain
    $serializedChain = serialize($markovChain);
    $chunkSize = 5000; // 500-byte Chunk size limit

    if ($serializedChain === false) {
        back_trace('ERROR', 'Serialization failed for Markov Chain data.');
        return; // Exit the function if serialization fails
    } else {
        back_trace('NOTICE', 'Serialization successful.');
    }

    // Split the chain into chunks
    $chunks = str_split($serializedChain, $chunkSize);

    // Clear existing chunks
    $wpdb->query("DELETE FROM $table_name");

    // Handle errors when clearing the table
    if ($wpdb->last_error) {
        back_trace('ERROR', 'saveMarkovChainInChunks - Error clearing existing chunks: ' . $wpdb->last_error);
        return; // Exit function on failure
    }

    // Insert each chunk
    foreach ($chunks as $index => $chunk) {

        // Log the length of each serialized chunk
        back_trace( 'NOTICE', 'Chunk ' . $index . ' length: ' . strlen($chunk));

        $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO $table_name (chunk_index, chain_chunk, last_updated) VALUES (%d, %s, NOW())",
                $index, $chunk
            )
        );

        // Log error if insertion fails
        if ($wpdb->last_error) {

            back_trace( 'ERROR', 'Error saving chunk ' . $index . ': ' . $wpdb->last_error . ' | Query: ' . $wpdb->last_query);

        } else {

            back_trace( 'NOTICE', 'Chunk ' . $index . ' saved successfully.');
        }

    }

    // DIAG - Diagnostics - Ver 2.1.6
    back_trace('NOTICE', 'saveMarkovChainInChunks - End');

}


// Retrieve the Markov Chain from chunks and reassemble it
function getMarkovChainFromChunks() {

    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_markov_chain';

    // DIAG - Diagnostics - Start of retrieval process
    back_trace( 'NOTICE', 'getMarkovChainFromChunks - Start');

    // Fetch all chunks in order
    $results = $wpdb->get_results("SELECT chain_chunk FROM $table_name ORDER BY chunk_index ASC");

    // Log the results of fetched chunks
    if (empty($results)) {
        back_trace( 'NOTICE', 'No chunks found in the database.');
        return null;
    }

    // Reassemble the chain
    $serializedChain = '';
    foreach ($results as $row) {
        $serializedChain .= $row->chain_chunk; // Concatenate all chunks together
    }

    // Log the reassembled chain before unserializing
    back_trace( 'NOTICE', 'Reassembled serialized chain: ' . $serializedChain);

    // Check if the reassembled chain is valid before unserializing
    if (!empty($serializedChain)) {

        $unserializedChain = unserialize($serializedChain);

        if ($unserializedChain === false) {
            back_trace( 'NOTICE', 'Unserialization failed. Check if the concatenated string is valid serialized data.');
            return null;
        }

        // Log success
        back_trace( 'NOTICE', 'Markov Chain unserialized successfully.');
        return $unserializedChain;

    } else {

        back_trace( 'NOTICE', 'Serialized chain is empty or invalid.');
        return null;

    }

}