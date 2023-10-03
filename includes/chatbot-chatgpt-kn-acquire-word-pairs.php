<?php
/**
 * Chatbot ChatGPT for WordPress - Settings - Knowledge Navigator - Acquire Word Pairs
 *
 * This file contains the code for the Chatbot ChatGPT Knowledge Navigator.
 * 
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) )
die;

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
    foreach (['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'li', 'a'] as $tagName) {
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
        
    // Replace all non-word characters with a space
    $contentWithoutTags = preg_replace('/\W+/', ' ', $textContent);

    // Get words and convert to lower case
    $words = str_word_count(strtolower($contentWithoutTags), 1);

    // Filter out stop words
    $words = array_diff($words, $stopWords);

    // Filter out any $words that are equal to a blank space
    $words = array_filter($words, function($word) {
        return $word !== ' ';
    });

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
    $wordPairs = array_slice($wordPairs, 0, 10);

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
    
    global $totalWordPairCount;

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