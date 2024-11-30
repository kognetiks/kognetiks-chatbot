<?php
/**
 * Kognetiks Chatbot for WordPress - Knowledge Navigator - Enhance Response - Ver 1.6.9 - Updated - Ver 2.1.5 - 2024 09 13
 *
 * This file contains the code for to utilize the DB with the TF-IDF data to enhance the chatbots response.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
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

    // Split the message into words and remove stop words
    $words = explode(" ", $message);
    $words = array_diff($words, $stopWords);

    // Initialize arrays to hold word scores and results
    $word_scores = array();
    $results = array();

    $limit = esc_attr(get_option('chatbot_chatgpt_enhanced_response_limit', 3));

    // Calculate total score for each word
    foreach ($words as $word) {
        $word = rtrim($word, "s.,;:!?");
        // Get the total score for the word
        $query = $wpdb->prepare("SELECT SUM(score) as total_score FROM $table_name WHERE word = %s", $word);
        $total_score = $wpdb->get_var($query);
        // Store the word and its total score
        $word_scores[$word] = $total_score ? $total_score : 0;
    }

    // Sort words by their scores (lowest to highest)
    asort($word_scores);
    // Extract sorted words
    $sorted_words = array_keys($word_scores);
    $total_words = count($sorted_words);

    // Build placeholders for the prepared statement
    $placeholders = implode(',', array_fill(0, $total_words, '%s'));

    // Initialize the number of words to match (starting from all words)
    $num_words_to_match = $total_words;

    while ($num_words_to_match > 0 && count($results) < $limit) {
        // Prepare the SQL query
        $query_params = array_merge($sorted_words, [$num_words_to_match]);
        $query = $wpdb->prepare(
            "SELECT pid, url, title, SUM(score) as total_score, COUNT(DISTINCT word) as word_match_count
            FROM $table_name
            WHERE word IN ($placeholders)
            GROUP BY pid, url, title
            HAVING word_match_count = %d
            ORDER BY word_match_count DESC, pid DESC",
            $query_params
        );

        // Execute the query
        $rows = $wpdb->get_results($query);

        // Check if matches are found
        if (!$wpdb->last_error && !empty($rows)) {
            foreach ($rows as $row) {
                $result_key = hash('sha256', $row->url);
                if (!isset($results[$result_key])) {
                    $results[$result_key] = [
                        'pid' => $row->pid,
                        'score' => $row->total_score,
                        'url' => $row->url,
                        'title' => $row->title,
                        'word_match_count' => $row->word_match_count
                    ];
                }
                if (count($results) >= $limit) {
                    break 2; // Break out of both loops
                }
            }
        }

        // Decrease the number of words to match
        $num_words_to_match--;
    }

    // Convert results to indexed array
    $results = array_values($results);

    // Select top three results
    $results = array_slice($results, 0, $limit);
    $links = [];

    // Option - Include Title in Enhanced Response
    $include_title = esc_attr(get_option('chatbot_chatgpt_enhanced_response_include_title', 'yes'));
    // FIXME - TEMPORARY - REMOVE THIS
    // $include_title = 'no';

    foreach ($results as $result) {
        if ('yes' == $include_title) {
            $links[] = "<li>[" . $result['title'] . "](" . $result['url'] . ")</li>";
        } else {
            $links[] = "[here](" . $result['url'] . ")";
        }

        // Determine if AI summary should be included
        $include_ai_summary = esc_attr(get_option('chatbot_chatgpt_enhanced_response_include_ai_summary', 'No'));
        // FIXME - TEMPORARY OVERRIDE
        $include_ai_summary = 'Yes';
        back_trace( 'NOTICE', '$include_ai_summary: ' . $include_ai_summary );

        if ($include_ai_summary == 'Yes') {

            // DIAG - Diagnostics - Ver 2.2.0
            back_trace( 'NOTICE', 'Generating AI summary' );
            back_trace( 'NOTICE', '$result[pid]: ' . $result['pid'] );

            $ai_summary = generate_ai_summary($result['pid']);
            if (!empty($ai_summary)) {
                $links[] = "<li>" . $ai_summary . "</li>";
            }

            // DIAG - Diagnostics - Ver 2.2.0
            back_trace( 'NOTICE', '$ai_summary: ' . $ai_summary );

        }
    }

    if (!empty($links)) {

        if ('no' == $include_title) {
            // Formatting: here, here, and here.
            $links_string = implode(", ", $links);
            $links_string = ltrim($links_string, ',');
            $links_string = $links_string . ".";
        } else {
            // Formatting: bullet list
            $links_string = implode("", $links);
        }

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
        if ('yes' == $include_title) {
            $enhanced_response .= '<ul>' . $links_string . '</ul>';
        } else {
            $enhanced_response .= $links_string;
        }

    }

    return !empty($enhanced_response) ? $enhanced_response : null;
    
}
