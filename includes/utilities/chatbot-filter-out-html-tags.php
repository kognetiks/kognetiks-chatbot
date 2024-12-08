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
    die();
}

// Filter out HTML tags from content - Updated Ver 2.2.1
function chatbot_chatgpt_filter_out_html_tags($content) {

    if (empty($content)) {
        return [];
    }

    // Initialize DOMDocument with proper encoding
    $dom = new DOMDocument('1.0', 'UTF-8');
    @$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));

    // Remove script and style elements
    foreach ($dom->getElementsByTagName('script') as $script) {
        $script->parentNode->removeChild($script);
    }
    foreach ($dom->getElementsByTagName('style') as $style) {
        $style->parentNode->removeChild($style);
    }

    // Extract text content from specified tags, avoiding nested content duplication
    $textContent = '';
    foreach (['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'li', 'a', 'div', 'span', 'ul', 'ol', 'table', 'tr', 'td', 'th', 'img', 'figcaption', 'figure', 'blockquote', 'pre', 'code', 'nav', 'header', 'footer', 'article', 'section', 'aside', 'main', 'body'] as $tagName) {
        $elements = $dom->getElementsByTagName($tagName);
        foreach ($elements as $element) {
            if ($element->parentNode === null || $element->parentNode->nodeName !== $tagName) {
                $textContent .= $element->textContent . ' ';
            }
        }
    }

    // Decode HTML entities
    $textContent = html_entity_decode($textContent, ENT_QUOTES, 'UTF-8');

    // Normalize line breaks and whitespace
    $textContent = preg_replace('/\r\n|\r|\n|\\r\\n/u', ' ', $textContent);

    // Remove HTML comments
    $textContent = preg_replace('/<!--[\s\S]*?-->/', ' ', $textContent);

    // Remove URLs
    $textContent = preg_replace('!https?://\S+!', ' ', $textContent);

    // Ensure text content is in UTF-8
    if (mb_detect_encoding($textContent, 'UTF-8', true) !== 'UTF-8') {
        $textContent = mb_convert_encoding($textContent, 'UTF-8', 'auto');
    }

    // Replace all non-word characters with a space, preserving Unicode characters
    $contentWithoutTags = preg_replace('/[^\p{L}\p{N}_]+/u', ' ', $textContent);

    // Trim and collapse spaces
    $contentWithoutTags = trim(preg_replace('/\s+/', ' ', $contentWithoutTags));

    // Convert to lowercase
    $textContentLower = mb_strtolower($contentWithoutTags, 'UTF-8');

    // Split the text into words
    $words = explode(' ', $textContentLower);

    return $words;
}
