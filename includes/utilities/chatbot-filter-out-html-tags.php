<?php
/**
 * Kognetiks Chatbot for WordPress - Filter out HTML Tags from Content
 *
 * This file contains the code for uploading files as part
 * in support of Custom GPT Assistants via the Chatbot.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

function chatbot_chatgpt_filter_out_html_tags ($content) {

    if ( empty($content) ) {
        return [];
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

    return $words;

}
