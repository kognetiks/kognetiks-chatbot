<?php
/**
 * Chatbot ChatGPT for WordPress - Knowledge Navigator - Scheduler - Ver 1.6.3
 *
 * This is the file that schedules the Knowledge Navigator.
 * Scheduled can be now, daily, weekly, etc.
 * 
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
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

    // DIAG Diagnostic - Ver 1.6.3
    // error_log("ENTERING crawl_scehedule_event_hook");
    update_option('chatbot_chatgpt_crawler_status', 'In Process');

    $result = "";
    // Reset the results message
    update_option('chatbot_chatgpt_kn_results', $result);

    // Make sure the results table exists before proceeding - Ver 1.6.3
    dbKNStore();

    // Call the kn-acquire.php script
    chatbot_chatgpt_kn_acquire();

    // Save the results message value into the option
    $kn_results = 'Knowledge Navigation completed! Check the Analysis to download or results.csv file in the plugin directory.';
    update_option('chatbot_chatgpt_kn_results', $kn_results);

    // Notify outcome for up to 3 minutes
    set_transient('chatbot_chatgpt_kn_results', $kn_results);

    // DIAG Diagnostic - Ver 1.6.3
    // error_log("EXITING crawl_scehedule_event_hook");

    // Get the current date and time.
    $date_time_completed = date("Y-m-d H:i:s");

    // Concatenate the status message with the date and time.
    $status_message = 'Completed on ' . $date_time_completed;

    // Update the option with the new status message.
    update_option('chatbot_chatgpt_kn_status', $status_message);

}
add_action('crawl_scheduled_event_hook', 'crawl_scheduled_event');

