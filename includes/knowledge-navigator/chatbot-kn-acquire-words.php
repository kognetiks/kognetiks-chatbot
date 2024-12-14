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
function kn_acquire_words($content, $option = null) {

    global $wpdb;
    global $stopWords;
    global $topWords;

    // Ensure $content is in UTF-8
    if (mb_detect_encoding($content, 'UTF-8', true) !== 'UTF-8') {
        $content = mb_convert_encoding($content, 'UTF-8', 'auto');
    }

    // Translate stop words
    if (get_locale() !== "en_US") {
        $localized_stopWords = get_localized_stopwords(get_locale(), $stopWords);
        $localized_stopWords = array_map(function($word) {
            return mb_convert_encoding($word, 'UTF-8', 'auto');
        }, $localized_stopWords);
    } else {
        $localized_stopWords = $stopWords;
    }

    // Filter out HTML tags
    $words = chatbot_chatgpt_filter_out_html_tags($content);

    // Filter out stop words
    $words = array_diff($words, $localized_stopWords);

    // Sanitize words
    $words = array_map(function($word) {
        $word = htmlspecialchars_decode($word, ENT_QUOTES); // Decode HTML entities
        return preg_replace('/[^\p{L}\p{N}_]+/u', ' ', $word); // Remove non-Unicode characters
    }, $words);

    // Filter blank spaces and invalid characters
    $words = array_filter($words, function($word) {
        return trim($word) !== '';
    });

    // Compress the $words array to unique words and their counts
    $words = array_count_values($words);

    if ($option === 'add') {
        $table_name = $wpdb->prefix . 'chatbot_chatgpt_knowledge_base_word_count';

        foreach ($words as $word => $count) {
            $word = htmlspecialchars_decode($word, ENT_QUOTES); // Decode HTML entities
            if (mb_detect_encoding($word, 'UTF-8', true) !== 'UTF-8') {
                $word = mb_convert_encoding($word, 'UTF-8', 'auto');
            }
            $escaped_word = esc_sql($word);
            $wpdb->query(
                $wpdb->prepare(
                    "INSERT INTO $table_name (word, word_count, document_count) VALUES (%s, %d, 1)
                    ON DUPLICATE KEY UPDATE word_count = word_count + %d, document_count = document_count + 1",
                    $escaped_word, $count, $count
                )
            );
        }

        // Update total word count
        $totalWordCount = count($words);
        $chatbot_chatgpt_kn_total_word_count = esc_attr(get_option('chatbot_chatgpt_kn_total_word_count', 0));
        $chatbot_chatgpt_kn_total_word_count += $totalWordCount;
        update_option('chatbot_chatgpt_kn_total_word_count', $chatbot_chatgpt_kn_total_word_count);

        return;
    }

    return array_keys($words); // Return words for further use
    
}
