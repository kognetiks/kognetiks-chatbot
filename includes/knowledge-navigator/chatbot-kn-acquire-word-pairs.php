<?php
/**
 * Kognetiks Chatbot for WordPress - Settings - Knowledge Navigator - Acquire Word Pairs
 *
 * This file contains the code for the Chatbot Knowledge Navigator.
 * 
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

global $max_top_words, $chatbot_chatgpt_diagnostics, $frequencyData, $totalWordCount, $totalWordPairCount ;
$max_top_words = esc_attr(get_option('chatbot_chatgpt_kn_maximum_top_words', 100)); // Default to 100
$topWords = [];
$topWordPairs = [];
$frequencyData = [];
$totalWordCount = 0;
$totalWordPairCount = 0;

// Knowledge Navigator - Acquire Top Word Pairs using TF-IDF - Ver 1.6.5
function kn_acquire_word_pairs( $content ) {

    global $stopWords;
    global $max_top_word_pairs;
    global $topWordPairs;
    global $totalWordPairCount;
    
    // DIAG - Diagnostic - Ver 1.6.3
    // back_trace( 'NOTICE', "FUNCTION - kn_acquire_just_the_words");
    
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

    $dom = new DOMDocument();
    @$dom->loadHTML($content);

    // Remove script and style elements
    foreach ($dom->getElementsByTagName('script') as $script) {
        $script->parentNode->removeChild($script);
    }
    foreach ($dom->getElementsByTagName('style') as $style) {
        $style->parentNode->removeChild($style);
    }

    // Extract text content from specific tags
    $textContent = '';
    // Added additional HTML tags for removal - Ver 1.7.2.1
    foreach (['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'li', 'a', 'div', 'span', 'ul', 'ol', 'table', 'tr', 'td', 'th', 'img', 'figcaption', 'figure', 'blockquote', 'pre', 'code', 'nav', 'header', 'footer', 'article', 'section', 'aside', 'main', 'body'] as $tagName) {
        $elements = $dom->getElementsByTagName($tagName);
        foreach ($elements as $element) {
            $textContent .= $element->textContent . ' ';
        }
    }

    // Handle New Line and Carriage Return characters
    // Belt
    $textContent = preg_replace('/\r?\n/', ' ', $textContent);
    // Suspenders
    $textContent = preg_replace('/\r?\n/u', ' ', $textContent);
    // And Braces
    $textContent = str_replace("\\r\\n", ' ', $textContent);

    // Remove Comments
    $textContent = preg_replace('/<!--(.*?)-->/', ' ', $textContent);

    // Remove URLs
    $textContent = preg_replace('!https?://\S+!', ' ', $textContent);

    // Replace new line characters with a space
    $textContent = str_replace("\n", ' ', $textContent);
        
    // Ensure $textContent is in UTF-8
    $textContentUtf8 = mb_convert_encoding($textContent, 'UTF-8', mb_detect_encoding($textContent));

    // Replace all non-word characters with a space, preserving Unicode characters
    $contentWithoutTags = preg_replace('/[^\p{L}\p{N}_]+/u', ' ', $textContentUtf8);

    // Convert to lower case
    $textContentLower = mb_strtolower($contentWithoutTags, 'UTF-8');

    // Split the text into words based on spaces
    $words = explode(' ', $textContentLower);

    // Filter out stop words
    $words = array_diff($words, $localized_stopWords);

    // Remove 's' and 'â' at end of any words - Ver 1.6.5 - 2023 10 11
    $words = array_map(function($word) {
        return rtrim($word, 'sâÃ¢£Â²°');
    }, $words);

    // Filter out any $words that are equal to a blank space
    $words = array_filter($words, function($word) {
        // return $word that do not start with "asst_" and is not in the specified array or a blank space
        return substr($word, 0, 5) !== 'asst_' && !in_array($word, ['â', 'Ã¢', 'Ã°', '']) && $word !== ' ';
    });

    // Initialize the arrays
    $wordPairs = array();
    $wordCounts = array_count_values($wordPairs);

    // Generate word pairs
    $wordKeys = array_keys($words);  // Get the keys of the words
    for ($i = 0; $i < count($wordKeys) - 1; $i++) {
        $wordPairs[] = $words[$wordKeys[$i]] . ' ' . $words[$wordKeys[$i + 1]];
    }  

    // Compute the TF-IDF for the $wordPairs array, and return the max top word pairs
    $wordPairs = array_count_values($wordPairs);
    arsort($wordPairs);
    $wordPairs = array_slice($wordPairs, 0, $max_top_word_pairs);

    // Find the $wordPairs in the $topWordPairs array, update the count, and sort the array
    foreach ($wordPairs as $wordPair => $count) {
        if (array_key_exists($wordPair, $topWordPairs)) {
            $topWordPairs[$wordPair] += $count;
        } else {
            $topWordPairs[$wordPair] = $count;
        }
    }
    arsort($topWordPairs);

    // Update the totalWordPairCount with the sum of the $wordPairs array
    $totalWordPairCount = $totalWordPairCount + array_sum($wordPairs);

    // Before computing the TF-IDF for the $wordPairs array, trim the $wordPairs array to the top 10 word pairs
    $wordPairs = array_slice($wordPairs, 0, 100);

    // Compute the TF-IDF for the $wordPairs array
    foreach ($wordPairs as $wordPair => $count) {
        $wordPairs[$wordPair] = computePairedTFIDF($wordPair);
    }

    return $wordPairs;
}


// Compute the TF-IDF for the $wordPairs array
function computePairedTFIDF($term) {

    global $topWordPairs;
    global $totalWordPairCount;

    $tf = $topWordPairs[$term] / $totalWordPairCount;
    $idf = computePairedInverseDocumentFrequency($term);

    return $tf * $idf;

}


function computePairedTermFrequency($term) {
    
    global $totalWordPairCount, $topWordPairs;

    return $topWordPairs[$term] / count($topWordPairs);

}


function computePairedInverseDocumentFrequency($term) {

    global $topWordPairs;

    $numDocumentsWithTerm = 0;
    foreach ($topWordPairs as $wordPairs => $frequency) {
        if ($wordPairs === $term) {
            $numDocumentsWithTerm++;
        }
    }
    
    return log(count($topWordPairs) / ($numDocumentsWithTerm + 1));

}
