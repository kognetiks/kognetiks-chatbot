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

	// Global Variables
    global $wpdb;
    global $learningMessages;
    global $errorResponses;
    global $stopWords;
    $enhanced_response = "";

    // Check that Knowledge Navigator is finished running
    $chatbot_chatgpt_kn_status = get_option('chatbot_chatgpt_kn_status', '');
    // If the status does not contain 'Completed', then Knowledge Navigator is still running
    if (false === strpos($chatbot_chatgpt_kn_status, 'Completed')) {
        // IDEA Return one of the $errorResponses - Ver 1.6.3
        // IDEA Belt and Suspenders - We shouldn't be here unless something went really wrong up above this point
        // return $errorResponses[array_rand($errorResponses)];
        return;
    }

    // Retrieve links to the highest scoring documents - Ver 1.6.3
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_knowledge_base';
    $words = explode(" ", $message);
    // DIAG Diagnostic - Ver 1.7.2.1
    // back_trace( 'NOTICE', '$words: ' . print_r($words, true));
    $match_found = false;
    $highest_score = 0;
    $highest_score_word = "";
    $highest_score_url = "";

    // Strip out $stopWords
    // FIXME - DO I NEED TO CHECK FOR LOCALIZATION HERE?
    $words = array_diff($words, $stopWords);
 
    // Loop through each word in the message
    foreach ($words as $key => $word) {
        // Strip off any trailing punctuation
        $word = rtrim($word, ".,;:!?");
    
        // Check for plural
        // if (substr($word, -1) == "s") {
        //     $word_singular = substr($word, 0, -1);
        //     $words[] = $word_singular;
        // }
   
        // Remove s at end of any words - Ver 1.6.5 - 2023 10 11
        $word = rtrim($word, 's');

        // Count the number of $words
        $word_count = count($words);
    
        // Check if the key exists before accessing it
        if (isset($words[$key + 1])) {
            // Create the word pair
            $word_pair = $word . " " . $words[$key + 1];
    
            // Find the highest score for the word pair
            $result = $wpdb->get_row($wpdb->prepare("SELECT score, url FROM $table_name WHERE word = %s ORDER BY score DESC LIMIT 1", $word_pair));
            // Exit if there is an error
            if (!$wpdb->last_error) {
                if ($result !== null && $result->score > $highest_score) {
                    $highest_score = $result->score;
                    $highest_score_word = $word_pair;
                    $highest_score_url = $result->url;
                }
                // Add your success handling code here
            } else {
                // Handle error here
                $highest_score = 0;
            }
        }
    
        // Find the highest score for the word
        $result = $wpdb->get_row($wpdb->prepare("SELECT score, url FROM $table_name WHERE word = %s ORDER BY score DESC LIMIT 1", $word));
        // Exit if there is an error
        if (!$wpdb->last_error) {
            if ($result !== null && $result->score > $highest_score) {
                $highest_score = $result->score;
                $highest_score_word = $word;
                $highest_score_url = $result->url;
            }
            // Add your success handling code here
        } else {
            // Handle error here
            $highest_score = 0;
        }
    }

    if (!isset($enhanced_response)) {
        $enhanced_response = "";
    }

    // DIAG Diagnostic - Ver 1.6.5
    // back_trace( 'NOTICE', '$highest_score: ' . $highest_score);
    // back_trace( 'NOTICE', '$highest_score_word: ' . $highest_score_word);
    // back_trace( 'NOTICE', 'Chatbot: $highest_score_url: ' . $highest_score_url);

    // IDEA Append message and link if found to ['choices'][0]['message']['urls']
    if ($highest_score > 0) {
        // Return the URL with the highest score
        $match_found = true;
        // $enhanced_response = $highest_score_url;
        // if (!isset($enhanced_response)) {
        //     $enhanced_response = '';
        // }

        // Support for None, Random, or Custom Learnings Messages - Ver 1.7.1
        $chatbot_chatgpt_suppress_learnings = esc_attr(get_option('chatbot_chatgpt_suppress_learnings', 'Random'));
        $chatbot_chatgpt_custom_learnings_message = esc_attr(get_option('chatbot_chatgpt_custom_learnings_message', 'More information may be found here ...'));

        if (get_locale() !== "en_US") {
            $localized_learningMessages = get_localized_learningMessages(get_locale(), $learningMessages);
        } else {
            $localized_learningMessages = $learningMessages;
        }

        if ('None' == $chatbot_chatgpt_suppress_learnings) {
            // $enhanced_response .= "\n\n" . "Also look" . " ";
        } else {
            if ('Random' == $chatbot_chatgpt_suppress_learnings) {
                $enhanced_response .= "\n\n" . $localized_learningMessages[array_rand($localized_learningMessages)];
            } elseif ('Custom' == $chatbot_chatgpt_suppress_learnings) {
                $enhanced_response .= "\n\n" . $chatbot_chatgpt_custom_learnings_message . " ";
            }
            // Aligned URL with markdown - Ver 1.9.2
            // $enhanced_response .= "[URL: " . $highest_score_url . "]";
            $enhanced_response .= "[here](" . $highest_score_url . ")";
        }
    } else {
        // If no match is found, return a generic response
        $match_found = false;
        $enhanced_response = '';
        // if (!isset($enhanced_response)) {
        //     $enhanced_response = '';
        // }
        // Only append $errorResponses if there is no response from the engine
        // if (empty($enhanced_response)) {
        //     $enhanced_response .= $errorResponses[array_rand($errorResponses)];
        // }
    }
   
    // REMOVED IN VER 1.9.2
    // Strip out any <strong></strong> tags in $response_body['choices'][0]['message']['content'] - Ver 1.6.3
    // $enhanced_response = preg_replace('/<strong>(.*?)<\/strong>/', '$1', $enhanced_response);
    // // Strip out any <b></b> tags in $response_body['choices'][0]['message']['content'] - Ver 1.6.3
    // $enhanced_response = preg_replace('/<b>(.*?)<\/b>/', '$1', $enhanced_response);

    // DIAG - Diagnostic - Ver 1.6.3
    // back_trace( 'NOTICE', '$match_found: ' . $match_found);
    // back_trace( 'NOTICE', '$highest_score: ' . $highest_score);
    // back_trace( 'NOTICE', '$highest_score_word: ' . $highest_score_word);
    // back_trace( 'NOTICE', '$highest_score_url: ' . $highest_score_url);
    // back_trace( 'NOTICE', '$enhanced_response: ' . $enhanced_response);

	// Interaction Tracking - Ver 1.6.3
	update_interaction_tracking();

	if (isset($enhanced_response) && !empty($enhanced_response)) {
		// Handle the response from the chat engine
		// Context History - Ver 1.6.1
		addEntry('chatbot_chatgpt_context_history', $enhanced_response);
		return $enhanced_response;
	} else {
		// Handle any errors that are returned from the chat engine
		//
		// IDEA USE ALTERNATE MODEL TO GENERATE A RESPONSE HERE
		//
		// return 'Error: Unable to fetch response from ChatGPT API. Please check Settings for a valid API key or your OpenAI account for additional information.';

		// IDEA Return one of the $errorResponses - Ver 1.6.3
		// IDEA Belt and Suspenders - We shouldn't be here unless something went really wrong up above this point
		// return $errorResponses[array_rand($errorResponses)];
		return;
	}

}
