<?php
/**
 * Kognetiks Chatbot - Knowledge Navigator - Enhance Response - Ver 1.6.9 - Updated in Ver 2.2.9
 * 
 * Updates
 * 
 * Ver 2.1.5 - 2024 09 13 - TBD
 * 
 * Ver 2.2.1 - 2024 12 01 - Added excerpts to enhanced responses
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

function chatbot_chatgpt_enhance_with_tfidf($message) {

    // DIAG - Diagnostics - Ver 2.2.9
    // back_trace( 'NOTICE', 'chatbot_chatgpt_enhance_with_tfidf');

    global $wpdb;
    global $learningMessages;
    global $stopWords;

    $enhanced_response = "";
    $results = array();
    $limit = esc_attr(get_option('chatbot_chatgpt_enhanced_response_limit', 3));

    $search_results = chatbot_chatgpt_content_search($message);

    // Access the 'results' key from the returned array
    $results = isset($search_results['results']) ? $search_results['results'] : [];

    // Convert results to indexed array
    $results = array_values($results);

    // DIAG - Diagnostics - Ver 2.2.9
    // back_trace( 'NOTICE', '=====================================');
    // back_trace( 'NOTICE', '$results: ' . print_r($results, true));
    // back_trace( 'NOTICE', '=====================================');

    // Select top three results
    $results = array_slice($results, 0, $limit);
    $links = [];

    // Option - Include Title in Enhanced Response
    $include_title = esc_attr(get_option('chatbot_chatgpt_enhanced_response_include_title', 'yes'));

    // Decide if the links to site content and exceprts should be included in the response
    $include_post_or_page_excerpt = esc_attr(get_option('chatbot_chatgpt_enhanced_response_include_excerpts', 'No'));

    // Debugging output to verify the structure of the results
    // back_trace('NOTICE', 'Debugging $results: ' . print_r($results, true));

    foreach ($results as $result) {
        // Debugging output for each result
        // back_trace('NOTICE', 'Processing result: ' . print_r($result, true));

        if (is_array($result) && isset($result['title'], $result['url'], $result['ID'])) {
            if ('yes' == $include_title) {
                $links[] = "<li>[" . $result['title'] . "](" . $result['url'] . ")</li>";
            } else {
                $links[] = "[here](" . $result['url'] . ")";
            }

            if ($include_post_or_page_excerpt == 'Yes') {
                $post_excerpt = get_the_excerpt($result['ID']);
                if (!empty($post_excerpt)) {
                    $links[] = "<li>" . $post_excerpt . "</li>";
                }
            }
        }
    }

    // DIAG - Diagnostics - Ver 2.2.9
    // back_trace( 'NOTICE', 'links: ' . print_r($links, true));

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

    // DIAG - Diagnostics - Ver 2.2.9
    // if (!empty($enhanced_response)) {
    //     back_trace( 'NOTICE', '$enhanced_response: ' . print_r($enhanced_response, true));
    // } else {
    //     back_trace( 'NOTICE', 'No enhanced response found');
    // }

    return !empty($enhanced_response) ? $enhanced_response : null;

}

// Enhance the response with TF-IDF - Ver 1.6.9
function chatbot_chatgpt_enhance_with_tfidf_deprecated($message) {
    
    global $wpdb;
    global $learningMessages;
    global $stopWords;

    $enhanced_response = "";

    // Check if the Knowledge Navigator is finished running
    $chatbot_chatgpt_kn_status = esc_attr(get_option('chatbot_chatgpt_kn_status', ''));
    if (false === strpos($chatbot_chatgpt_kn_status, 'Completed')) {
        return;
    }

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_knowledge_base';

    // Check if the table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") !== $table_name) {
        prod_trace( 'WARNING', 'Table ' . $table_name . ' does not exist. Skipping knowledge base match step.');
        return null; // Skip processing if the table doesn't exist
    }

    // Split the message into words and remove stop words
    $words = explode(" ", $message);
    $words = array_diff($words, $stopWords);

    // DIAG - Diagnostics - Ver 2.2.1
    // back_trace( 'NOTICE', 'Input message: ' . $message);
    // back_trace( 'NOTICE', 'Processed words: ' . implode(", ", $words));

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
        // DIAG - Diagnostics - Ver 2.2.1
        // back_trace( 'NOTICE', 'Word: ' . $word . ', Score: ' . $word_scores[$word]);
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

        // Diagnostics - Ver 2.2.1
        // back_trace( 'NOTICE', 'SQL Query: ' . $query );

        // Check if matches are found
        if ($rows) {
            foreach ($rows as $row) {

                // Diagnostics - Ver 2.2.1
                // back_trace( 'INFO', 'Match found: PID=' . $row->pid . ', URL=' . $row->url . ', Title=' . $row->title . ', Score=' . $row->total_score . ', Word Matches=' . $row->word_match_count);
                
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
                    break 2;
                }

            }
        } else {

            // back_trace( 'NOTICE', 'No matches found for num_words_to_match=' . $num_words_to_match);
            
        }

        $num_words_to_match--;

    }

    // DIAG - Diagnostics - Ver 2.2.1
    // foreach ($results as $result) {
    //     // back_trace( 'NOTICE', 'Final result: PID=' . $result['pid'] . ', URL=' . $result['url'] . ', Title=' . $result['title'] . ', Score=' . $result['score'] . ', Word Matches=' . $result['word_match_count']);
    // }

    // Convert results to indexed array
    $results = array_values($results);

    // Select top three results
    $results = array_slice($results, 0, $limit);
    $links = [];

    // Option - Include Title in Enhanced Response
    $include_title = esc_attr(get_option('chatbot_chatgpt_enhanced_response_include_title', 'yes'));

    // Decide if the links to site content and exceprts should be included in the response
    $include_post_or_page_excerpt = esc_attr(get_option('chatbot_chatgpt_enhanced_response_include_excerpts', 'No'));

    // DIAG - Diagnostics - Ver 2.2.9
    // back_trace('NOTICE', 'Debugging $results: ' . print_r($results, true));


    foreach ($results as $result) {

        // DIAG - Diagnostics - Ver 2.2.9
        // back_trace('NOTICE', 'Processing result: ' . print_r($result, true));

        if (is_object($result) && isset($result->post_title, $result->url, $result->ID)) {
            if ('yes' == $include_title) {
                $links[] = "<li>[" . $result->post_title . "](" . $result->url . ")</li>";
            } else {
                $links[] = "[here](" . $result->url . ")";
            }

            if ($include_post_or_page_excerpt == 'Yes') {
                $post_excerpt = get_the_excerpt($result->ID);
                if (!empty($post_excerpt)) {
                    $links[] = "<li>" . $post_excerpt . "</li>";
                }
            }
        
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
