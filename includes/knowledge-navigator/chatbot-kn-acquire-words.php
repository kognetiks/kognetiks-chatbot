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
    die;
}

global $max_top_words, $chatbot_chatgpt_diagnostics, $frequencyData, $totalWordCount, $totalWordPairCount ;
$max_top_words = esc_attr(get_option('chatbot_chatgpt_kn_maximum_top_words', 100)); // Default to 100
$topWords = [];
$topWordPairs = [];
$frequencyData = [];
$totalWordCount = 0;
$totalWordPairCount = 0;

// Knowledge Navigator - Acquire Top Words using TF-IDF - Ver 1.6.5
function kn_acquire_just_the_words( $content ) {

    global $stopWords;
    global $max_top_words;
    global $topWords;
    global $totalWordCount;

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

    // Updated sequence of processing to remove extraneous contents before TF-IDF - Ver 1.6.5
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

    // Before computer the TF-IDF for the $words array, trim the $words array to the top 10 words
    $words = array_slice($words, 0, 100);

    // Computer the TF-IDF for the $words array
    foreach ($words as $word => $count) {
        $words[$word] = computeTFIDF($word);
    } 

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
