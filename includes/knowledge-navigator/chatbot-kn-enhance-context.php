<?php
/**
 * Kognetiks Chatbot - Knowledge Navigator - Enhance Context - Ver 1.6.9
 *
 * This file contains the code for to utilize the DB with the TF-IDF data to enhance the chatbots context.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

function kn_enhance_context( $message ) {

    global $wpdb;

    global $stopWords;

    // Split the text into words based on spaces
    $enhancedMessage = mb_strtolower($message);

    $enhancedMessage = explode(' ', $enhancedMessage);

    // if (get_locale() !== "en_US") {
    //     // $localized_stopWords = localize_global_stopwords(get_locale(), $stopWords);
    //     $localized_stopWords = get_localized_stopwords(get_locale(), $stopWords);
    // } else {
    //     $localized_stopWords = $stopWords;
    // }

    // FIXME - CZECH OVERRIDE - REMOVED IN VER 2.2.1 - 2024-12-24
    $localized_stopWords = $stopWords;

    // Filter out stop words
    $enhancedMessage = array_diff($enhancedMessage, $localized_stopWords);

    // Remove 's' and 'â' at end of any words - Ver 1.6.5 - 2023 10 11
    // FIXME - Determine if word ends in an s then leave the s else if the word is plural then remove the s
    $enhancedMessage = array_map(function($enhancedMessage) {
        return rtrim($enhancedMessage, 'sâÃ¢£Â²°Ã±');
    }, $enhancedMessage);

    // Filter out any $enhancedMessage that are equal to a blank space
    $enhancedMessage = array_filter($enhancedMessage, function($enhancedMessage) {
        // return $enhancedMessage that do not start with "asst_" and is not in the specified array or a blank space
        return !str_starts_with($enhancedMessage, 'asst_') && !in_array($enhancedMessage, ['â', 'Ã¢', 'Ã°', 'Ã±', '']) && $enhancedMessage !== ' ';
    });

    // Find matches in the knowledge base
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_knowledge_base';
    $results = [];

    // Check if the table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") !== $table_name) {
        prod_trace( 'WARNING', 'Table ' . $table_name . ' does not exist. Skipping knowledge base match step.');
        return null; // Skip processing if the table doesn't exist
    }

    $limit = esc_attr(get_option('chatbot_chatgpt_enhanced_response_limit', 3));
    
    foreach ($enhancedMessage as $word) {
        $word = rtrim($word, "s.,;:!?");
        // IN THIS CASE RETURN THE PID
        $query = $wpdb->prepare("SELECT score, pid FROM $table_name WHERE word = %s ORDER BY score DESC LIMIT $limit", $word);
        $rows = $wpdb->get_results($query);
        if (!$wpdb->last_error && !empty($rows)) {
            foreach ($rows as $row) {
                $results[] = ['score' => $row->score, 'pid' => $row->pid];
            }
        }
    }

    // Sort results by score in descending order
    usort($results, function($a, $b) {
        return $b['score'] <=> $a['score'];
    });

    // Select top three results
    $results = array_slice($results, 0, $limit);
    $enhancedContext = [];

    foreach ($results as $result) {
        // from the posts table get the content based on the pid
        $query = $wpdb->prepare("SELECT post_content FROM $wpdb->posts WHERE ID = %d", $result['pid']);
        $enhancedContext[] = $wpdb->get_var($query);
    }

    // Collapse the enhanced content into a single string
    $enhancedContext = implode(' ', $enhancedContext);
    $enhancedContext = implode(' ', chatbot_chatgpt_filter_out_html_tags($enhancedContext));

    return $enhancedContext;

}
