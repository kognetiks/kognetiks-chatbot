<?php
/**
 * Chatbot ChatGPT for WordPress - Settings - Knowledge Navigator - Acquire
 *
 * This file contains the code for the Chatbot ChatGPT Knowledge Navigator.
 * 
 * 
 *
 * @package chatbot-chatgpt
 */

// TODO If this file is called directly, abort.
if ( ! defined( 'WPINC' ) )
die;

global $max_top_words, $chatgpt_diagnostics, $frequencyData, $totalWordCount;
$max_top_words = esc_attr(get_option('chatbot_chatgpt_kn_maximum_top_words', 25)); // Default to 25
$topWords = [];
$frequencyData = [];
$totalWordCount = 0;

// TODO Dump Post Data to an log file
function chatbot_chatgpt_kn_acquire() {

    global $wpdb;
    global $topWords;
    global $max_top_words;

    // Initialize the $topWords array
    $topWords = [];
    
    // Generate directory path
    $results_dir_path = dirname(plugin_dir_path(__FILE__)) . '/results/';

    // Create directory if it doesn't exist
    if (!file_exists($results_dir_path)) {
        mkdir($results_dir_path, 0755, true);
    }

    // Log directory path for debugging
    error_log("Directory path: " . $results_dir_path);

    // Prepare log file for posts
    $log_file_posts = $results_dir_path . 'results-posts.log';

    // Delete post log file if it already exists
    if (file_exists($log_file_posts)) {
        unlink($log_file_posts);
    }

    // Prepare log file for pages
    $log_file_pages = $results_dir_path . 'results-pages.log';

    // Delete log file if it already exists
    if (file_exists($log_file_pages)) {
        unlink($log_file_pages);
    }

    // Prepare log file for comments
    $log_file_comments = $results_dir_path . 'results-comments.log';

    // Delete log file if it already exists
    if (file_exists($log_file_comments)) {
        unlink($log_file_comments);
    }

    // Query WordPress database for post content
    $results = $wpdb->get_results(
        "SELECT post_content FROM {$wpdb->prefix}posts WHERE post_type='post' AND post_status='publish'", 
        ARRAY_A
    );

    // Loop through query results
    foreach ($results as $result) {
        $output_str = '';
        $output_str .= json_encode($result['post_content']) . "\n";
        // Call kn_acquire_just_the_words with $output_str and return $words
        $words = kn_acquire_just_the_words($output_str);
        error_log(print_r($words, true) . "\n", 3, $log_file_posts);
    }

    // Query WordPress database for page content
    $results = $wpdb->get_results(
        "SELECT post_content FROM {$wpdb->prefix}posts WHERE post_type='page' AND post_status='publish'", 
        ARRAY_A
    );

    // Loop through query results
    foreach ($results as $result) {
        $output_str = '';
        $output_str .= json_encode($result['post_content']) . "\n";
        // Call kn_acquire_just_the_words with $output_str and return $words
        $words = kn_acquire_just_the_words($output_str);
        error_log(print_r($words, true) . "\n", 3, $log_file_pages);
    }

    // Query WordPress database for comment content
    $results = $wpdb->get_results(
        "SELECT comment_content FROM {$wpdb->prefix}comments WHERE comment_approved='1'", 
        ARRAY_A
    );

    // Loop through query results
    foreach ($results as $result) {
        $output_str = '';
        $output_str .= json_encode($result['comment_content']) . "\n";
        // Call kn_acquire_just_the_words with $output_str and return $words
        $words = kn_acquire_just_the_words($output_str);
        error_log(print_r($words, true) . "\n", 3, $log_file_comments);
    }

    // Now computer the TF-IDF for the $topWords array
    foreach ($topWords as $word => $count) {
        $topWords[$word] = computeTFIDF($word);
    }

    // Error log $max_top_words
    error_log("Max Top Words: " . $max_top_words);

    // slice off the top max_top_words
    $topWords = array_slice($topWords, 0, $max_top_words);

    // Store the top words for context
    store_top_words();

    // Output the results to a file
    output_results();
    
    return;

}

// Add the action hook
add_action( 'chatbot_chatgpt_kn_acquire', 'chatbot_chatgpt_kn_acquire' );


function kn_acquire_just_the_words( $content ) {

    global $max_top_words;
    global $topWords;
    global $totalWordCount;
    
    // TODO COMMENT OUT LATER
    error_log ("FUNCTION - kn_acquire_just_the_words");

    // List of common stop words to be ignored
    $stopWords = ['a', 'about', 'above', 'after', 'again', 'against', 'all', 'am', 'an', 'and', 'any', 'are', "aren't", 'as', 'at'];
    $stopWords = array_merge($stopWords, ['b', 'be', 'because', 'been', 'before', 'being', 'below', 'between', 'both', 'but', 'by']);
    $stopWords = array_merge($stopWords, ['c', 'can', "can't", 'cannot', 'could', "couldn't"]);
    $stopWords = array_merge($stopWords, ['d', 'did', "didn't", 'do', 'does', "doesn't", 'doing', "don't", 'down', 'during']);
    $stopWords = array_merge($stopWords, ['e', 'each']);
    $stopWords = array_merge($stopWords, ['f', 'few', 'for', 'from', 'further']);
    $stopWords = array_merge($stopWords, ['g']);
    $stopWords = array_merge($stopWords, ['h', 'had', "hadn't", 'has', "hasn't", 'have', "haven't", 'having', 'he', "he'd", "he'll", "he's", 'her', 'here', "here's", 'hers', 'herself', 'him', 'himself', 'his', 'how', "how's"]);
    $stopWords = array_merge($stopWords, ['i', "i'd", "i'll", "i'm", "i've", 'if', 'in', 'into', 'is', "isn't", 'it', "it's", 'its', 'itself']);
    $stopWords = array_merge($stopWords, ['j', 'k']);
    $stopWords = array_merge($stopWords, ['l', "let's"]);
    $stopWords = array_merge($stopWords, ['m', 'me', 'more', 'most', "mustn't", 'my', 'myself']);
    $stopWords = array_merge($stopWords, ['n', 'no', 'nor', 'not']);
    $stopWords = array_merge($stopWords, ['o', 'of', 'off', 'on', 'once', 'only', 'or', 'other', 'ought', 'our', 'ours' ,'ourselves', 'out', 'over', 'own']);
    $stopWords = array_merge($stopWords, ['p', 'q']);
    $stopWords = array_merge($stopWords, ['r', 're']);
    $stopWords = array_merge($stopWords, ['s', 'same', "shan't", 'she', "she'd", "she'll", "she's", 'should', "shouldn't", 'so', 'some', 'such']);
    $stopWords = array_merge($stopWords, ['t', 'than', 'that', "that's", 'the', 'their', 'theirs', 'them', 'themselves', 'then', 'there', "there's", 'these', 'they', "they'd", "they'll", "they're", "they've", 'this', 'those', 'through', 'to', 'too']);
    $stopWords = array_merge($stopWords, ['u', 'under', 'until', 'up']);
    $stopWords = array_merge($stopWords, ['v', 'very']);
    $stopWords = array_merge($stopWords, ['w', 'was', "wasn't", 'we', "we'd", "we'll", "we're", "we've", 'were', "weren't", 'what', "what's", 'when', "when's", 'where', "where's", 'which', 'while', 'who', "who's", 'whom', 'why', "why's", 'with', "won't", 'would', "wouldn't"]);
    $stopWords = array_merge($stopWords, ['x']);
    $stopWords = array_merge($stopWords, ['y', 'you', "you'd", "you'll", "you're", "you've", 'your', 'yours', 'yourself', 'yourselves']);
    $stopWords = array_merge($stopWords, ['z']);
     
    $dom = new DOMDocument();
    @$dom->loadHTML($content);

    // Remove script and style elements
    foreach ($dom->getElementsByTagName('script') as $script) {
        $script->parentNode->removeChild($script);
    }
    foreach ($dom->getElementsByTagName('style') as $style) {
        $style->parentNode->removeChild($style);
    }
    
    // Extract text content only from specific tags
    $textContent = '';
    foreach (['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'li', 'a'] as $tagName) {
        $elements = $dom->getElementsByTagName($tagName);
        foreach ($elements as $element) {
            $textContent .= ' ' . $element->textContent;
        }
    }
    
    // Replace all non-word characters with a space
    $contentWithoutTags = preg_replace('/\W+/', ' ', $textContent);
    
    // Get words and convert to lower case
    $words = str_word_count(strtolower($contentWithoutTags), 1);    

    // Filter out stop words
    $words = array_diff($words, $stopWords);

    // Compute the TF-IDF for the $words array, and return the max top words
    $words = array_count_values($words);
    arsort($words);
    $words = array_slice($words, 0, $max_top_words);

    // Find the $words in the $topWords array, update the count, and sort the array
    foreach ($words as $word => $count) {
        if (array_key_exists($word, $topWords)) {
            $topWords[$word] += $count;
        } else {
            $topWords[$word] = $count;
        }
    }

    // Sort the $topWords array
    arsort($topWords);

    // Update the totalWordCount with the sum of the $words array
    $totalWordCount = $totalWordCount + array_sum($words);
    
    return $words;

}

function computeTFIDF($term) {

    global $topWords;
    global $totalWordCount;

    $tf = $topWords[$term] / $totalWordCount;
    $idf = computeInverseDocumentFrequency($term);

    return $tf * $idf;

}

function computeTermFrequency($term) {
    
    global $topWords;

    return $topWords[$term] / count($topWords);

}

function computeInverseDocumentFrequency($term) {

    global $topWords;

    $numDocumentsWithTerm = 0;
    foreach ($topWords as $word => $frequency) {
        if ($word === $term) {
            $numDocumentsWithTerm++;
        }
    }
    
    return log(count($topWords) / ($numDocumentsWithTerm + 1));

}

// Store the top words for context
function store_top_words() {

    global $topWords;

    // String together the $topWords
    $chatbot_chatgpt_kn_conversation_context = "This site includes references to and information about the following topics: ";
    foreach ($topWords as $word => $tfidf) {
        $chatbot_chatgpt_kn_conversation_context .= $word . ", ";
        }
    $chatbot_chatgpt_kn_conversation_context .= "and more.";
    
    // Save the results message value into the option
    update_option('chatbot_chatgpt_kn_conversation_context', $chatbot_chatgpt_kn_conversation_context);

    return;

}

// Save the results to a file
function output_results() {

    global $topWords;

    // TODO COMMENT OUT LATER
    error_log("FUNCTION - output_results");

    // Generate the directory path
    $results_dir_path = dirname(plugin_dir_path(__FILE__)) . '/results/';

    // Create the directory if it doesn't exist
    if (!file_exists($results_dir_path) && !mkdir($results_dir_path, 0755, true)) {
        error_log('Failed to create results directory.');
        return;
    }

    // Define output files' paths
    $results_csv_file = $results_dir_path . 'results.csv';
    $results_json_file = $results_dir_path . 'results.json';

    // Write CSV
    if ($f = fopen($results_csv_file, 'w')) {
        fputcsv($f, ['Word', 'TF-IDF']);
        foreach ($topWords as $word => $tfidf) {
            fputcsv($f, [$word, $tfidf]);
        }
        fclose($f);
    } else {
        error_log('Failed to open CSV file for writing.');
    }

    // Write JSON
    if (!file_put_contents($results_json_file, json_encode($topWords))) {
        error_log('Failed to write JSON file.');
    }
}
