<?php
/**
 * Kognetiks Chatbot for WordPress - Knowledge Navigator - Enhance Response - Ver 1.6.9
 *
 * This file contains the code for to utilize the DB with the TF-IDF data to enhance the chatbots response.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Enhance the response with TF-IDF - Ver 1.6.9
function chatbot_chatgpt_enhance_with_tfidf($message) {

    global $wpdb;
    global $learningMessages;
    global $stopWords;
    $enhanced_response = "";

    // Check if the Knowledge Navigator is finished running
    $chatbot_chatgpt_kn_status = get_option('chatbot_chatgpt_kn_status', '');
    if (false === strpos($chatbot_chatgpt_kn_status, 'Completed')) {
        return;
    }

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_knowledge_base';
    $words = explode(" ", $message);
    $words = array_diff($words, $stopWords);
    $results = [];

    $limit = esc_attr(get_option('chatbot_chatgpt_enhanced_response_limit', 3));

    foreach ($words as $word) {
        $word = rtrim($word, "s.,;:!?");
        $query = $wpdb->prepare("SELECT score, url FROM $table_name WHERE word = %s ORDER BY score DESC LIMIT $limit", $word);
        $rows = $wpdb->get_results($query);
        if (!$wpdb->last_error && !empty($rows)) {
            foreach ($rows as $row) {
                $results[] = ['score' => $row->score, 'url' => $row->url];
            }
        }
    }

    // Sort results by score in descending order
    usort($results, function($a, $b) {
        return $b['score'] <=> $a['score'];
    });

    // Select top three results
    $results = array_slice($results, 0, $limit);
    $links = [];

    foreach ($results as $result) {
        $links[] = "[here](" . $result['url'] . ")";
    }

    if (!empty($links)) {

        // FIXME - ADD FORMATTING OPTION HERE, e.g. BULLET POINTS, NUMBERED LIST, etc.
        $links_string = implode(", ", $links);
        $links_string = ltrim($links_string, ',');
        $links_string = $links_string . ".";

        // Determine the pre-message based on the settings
        if (get_locale() !== "en_US") {
            $localized_learningMessages = get_localized_learningMessages(get_locale(), $learningMessages);
        } else {
            $localized_learningMessages = $learningMessages;
        }

        $chatbot_chatgpt_suppress_learnings = esc_attr(get_option('chatbot_chatgpt_suppress_learnings', 'Random'));
        $chatbot_chatgpt_custom_learnings_message = esc_attr(get_option('chatbot_chatgpt_custom_learnings_message', 'More information may be found here ...'));

        if ('Random' == $chatbot_chatgpt_suppress_learnings) {
            $enhanced_response .= "\n\n" . $localized_learningMessages[array_rand($localized_learningMessages)] . " ";
        } elseif ('Custom' == $chatbot_chatgpt_suppress_learnings) {
            $enhanced_response .= "\n\n" . $chatbot_chatgpt_custom_learnings_message . " ";
        }

        // Append the links to the enhanced response
        $enhanced_response .= $links_string;
    }

    return !empty($enhanced_response) ? $enhanced_response : null;
    
}