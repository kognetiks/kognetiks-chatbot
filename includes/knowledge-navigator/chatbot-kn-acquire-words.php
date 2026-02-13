<?php
/**
 * Kognetiks Chatbot - Settings - Knowledge Navigator - Acquire Words
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
function kn_acquire_words($Content, $action = 'add') {

    global $wpdb;
    
    // Get current total word count
    $current_total = esc_attr(get_option('chatbot_chatgpt_kn_total_word_count', 0));
    
    // Get current document count
    $current_documents = esc_attr(get_option('chatbot_chatgpt_kn_document_count', 0));
    
    // Clean and tokenize the content
    $words = str_word_count(strtolower($Content), 1);
    $word_count = count($words);
    
    if ($action === 'add') {
        // Update total word count
        $new_total = $current_total + $word_count;
        update_option('chatbot_chatgpt_kn_total_word_count', $new_total);
        
        // Update document count
        $new_documents = $current_documents + 1;
        update_option('chatbot_chatgpt_kn_document_count', $new_documents);
        
        // Count word frequencies
        $word_freq = array_count_values($words);
        
        // Store word counts
        foreach ($word_freq as $word => $count) {
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}chatbot_chatgpt_knowledge_base_word_count WHERE word = %s",
                $word
            ));
            
            if ($existing) {
                $wpdb->update(
                    $wpdb->prefix . 'chatbot_chatgpt_knowledge_base_word_count',
                    array(
                        'word_count' => $existing->word_count + $count,
                        'document_count' => $existing->document_count + 1
                    ),
                    array('word' => $word)
                );
            } else {
                $wpdb->insert(
                    $wpdb->prefix . 'chatbot_chatgpt_knowledge_base_word_count',
                    array(
                        'word' => $word,
                        'word_count' => $count,
                        'document_count' => 1
                    )
                );
            }
        }
    }
    
    return $words;

}
