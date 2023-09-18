<?php
/**
 * Chatbot ChatGPT for WordPress - Scheduler for Crawler - Ver 1.6.3
 *
 * This file contains the code for table actions for reporting
 * to display the Chatbot ChatGPT on the website.
 *
 * @package chatbot-chatgpt
 */

// TODO If this file is called directly, abort.
if ( ! defined( 'WPINC' ) )
	die;

global $topWords;

// Handle long running scripts with a scheduled event function - Ver 1.6.1
function crawl_scheduled_event() {

    global $topWords;

    $run_scanner = get_option('chatbot_chatgpt_knowledge_navigator', 'No');

    // The second parameter is the default value if the option is not set.
   update_option('chatbot_chatgpt_kn_status', 'In Process');

    if (!isset($run_scanner)) {
        $run_scanner = 'No';
    }

    // TODO Log the variables to debug.log
    // error_log("ENTERING crawl_scehedule_event_hook");
    update_option('chatbot_chatgpt_crawler_status', 'In Process');

    $result = "";
    // Reset the results message
    update_option('chatbot_chatgpt_kn_results', $result);

    // TODO - MOVED TO SCHEDULER
    // Make sure the results table exists before proceeding - Ver 1.6.3
    createTableIfNotExists();

    $crawler = new WebCrawler($GLOBALS['start_url']);
    $crawler->crawl(0, $GLOBALS['domain']);

    // Computer the TF-IDF (Term Frequency-Inverse Document Frequency)
    $crawler->computeFrequency();

    // Collect top N words with the highest TF-IDF scores.
    $topWords = [];
    for ($i = 0; $i < $GLOBALS['max_top_words']; $i++) {
        $maxTFIDF = 0;
        $maxWord = null;

        foreach ($crawler->getFrequencyData() as $word => $frequency) {
            $tfidf = $crawler->computeTFIDF($word);

            if ($tfidf > $maxTFIDF) {
                $maxTFIDF = $tfidf;
                $maxWord = $word;
            }
        }

        if ($maxWord !== null) {
            $topWords[$maxWord] = $maxTFIDF;
            $crawler->removeWordFromFrequencyData($maxWord);
        }
    }

    // TODO Diagnostics - Ver 1.6.1
    // var_dump($topWords);

    // Store the results
    output_results($topWords);

    // String together the $topWords
    $chatbot_chatgpt_kn_conversation_context = "This site includes references to and information about the following topics: ";
    foreach ($topWords as $word => $tfidf) {
        $chatbot_chatgpt_kn_conversation_context .= $word . ", ";
        }
    $chatbot_chatgpt_kn_conversation_context .= "and more.";
    
    // Save the results message value into the option
    update_option('chatbot_chatgpt_kn_conversation_context', $chatbot_chatgpt_kn_conversation_context);

    // Save the results message value into the option
    $kn_results = 'Knowledge Navigation completed! Check the Analysis to download or results.csv file in the plugin directory.';
    update_option('chatbot_chatgpt_kn_results', $kn_results);

    // Notify outcome for up to 3 minutes
    set_transient('chatbot_chatgpt_kn_results', $kn_results);

    // TODO Log the variables to debug.log
    // error_log("EXITING crawl_scehedule_event_hook");

    // Get the current date and time.
    $date_time_completed = date("Y-m-d H:i:s");

    // Concatenate the status message with the date and time.
    $status_message = 'Completed on ' . $date_time_completed;

    // Update the option with the new status message.
    update_option('chatbot_chatgpt_kn_status', $status_message);

}
add_action('crawl_scheduled_event_hook', 'crawl_scheduled_event');

