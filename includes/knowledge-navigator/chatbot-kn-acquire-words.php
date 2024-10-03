<?php
/**
 * Kognetiks Chatbot for WordPress - Settings - Knowledge Navigator - Acquire Words
 *
 * This file contains the code for the Chatbot Knowledge Navigator.
 * 
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Knowledge Navigator - Acquire Top Words using TF-IDF - Ver 1.9.6
function kn_acquire_words( $content, $option = null ) {

    global $wpdb;

    global $stopWords;
    global $topWords;

    // DIAG - Diagnostic - Ver 1.6.3
    // back_trace( 'NOTICE', "FUNCTION - kn_acquire_just_the_words");

    // Initialize the $topWords array - Ver 1.9.6
    $topWords = [];
    $totalWordCount = 0;

    // Before beginning, translate the $stopWords array into the language of the website
    if (get_locale() !== "en_US") {
        // DIAG - Diagnostic - Ver 1.7.2.1
        // back_trace( 'NOTICE', 'get_locale()' . get_locale());
        // $localized_stopWords = localize_global_stopwords(get_locale(), $stopWords);
        $localized_stopWords = get_localized_stopwords(get_locale(), $stopWords);
        // DIAG - Diagnostic - Ver 1.7.2.1
        // back_trace( 'NOTICE',  '$localized_stopWords ' . $localized_stopWords);
    } else {
        $localized_stopWords = $stopWords;
    }

    // Filter out HTML tags
    $words = chatbot_chatgpt_filter_out_html_tags($content);

    // Filter out stop words
    $words = array_diff($words, $localized_stopWords);

    // Remove 's' and 'â' at end of any words - Ver 1.6.5 - 2023 10 11
    // FIXME - Determine if word ends in an s then leave the s else if the word is plural then remove the s
    $words = array_map(function($word) {
        return rtrim($word, 'sâÃ¢£Â²°Ã±');
    }, $words);

    // Filter out any $words that are equal to a blank space
    $words = array_filter($words, function($word) {
        // return $word that do not start with "asst_" and is not in the specified array or a blank space
        return !str_starts_with($word, 'asst_') && !in_array($word, ['â', 'Ã¢', 'Ã°', 'Ã±', '']) && $word !== ' ';
    });

    // Insert the word into the database
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_knowledge_base_word_count';

    // Compress the $words array to the unique words and their counts
    $words = array_count_values($words);

    // Check the $option to determine if the $words should be added to the database
    if ($option === 'add') {

        foreach ($words as $word => $count) {

            $escaped_word = esc_sql($word);
            $wpdb->query(
                $wpdb->prepare(
                    "INSERT INTO $table_name (word, word_count, document_count) VALUES (%s, %d, 1)
                    ON DUPLICATE KEY UPDATE word_count = word_count + %d, document_count = document_count + 1",
                    $escaped_word, $count, $count
                )
            );
            
        }

        // Count the number of words and add to the chatbot_chatgpt_kn_total_word_count
        $totalWordCount = count($words);
        $chatbot_chatgpt_kn_total_word_count = get_option('chatbot_chatgpt_kn_total_word_count');
        $chatbot_chatgpt_kn_total_word_count += $totalWordCount;
        update_option('chatbot_chatgpt_kn_total_word_count', $chatbot_chatgpt_kn_total_word_count);

    }

    if ($option === 'add') {
        // Just return
        return;
    } else {
        // Return the $words array for use in the TF-IDF calculation
        return array_keys($words);
    }

}
