<?php
/**
 * Kognetiks Chatbot - Markov Chain - Scheduler - Ver 2.1.6
 *
 * This is the file that schedules the building of the Markov Chain.
 * Scheduling can be set to now, daily, weekly, etc.
 * 
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Handle long-running scripts with a scheduled event function - Ver 1.6.1
function chatbot_markov_chain_scheduler() {

    // DIAG - Diagnostics - Ver 2.1.6
    // back_trace( 'NOTICE', 'chatbot_markov_chain_scheduler - START');

    // FIXME - Force a full rebuild if necessary
    // update_option('chatbot_markov_chain_force_rebuild', 'Yes');
    // prod_trace( 'NOTICE', 'FIXME - chatbot_markov_chain_force_rebuild: Yes');

    // Retrieve the schedule setting
    $chatbot_markov_chain_build_schedule = esc_attr(get_option('chatbot_markov_chain_build_schedule', 'Disable'));

    if (!isset($chatbot_markov_chain_build_schedule)) {
        $chatbot_markov_chain_build_schedule = 'Disable';
        update_option('chatbot_markov_chain_build_schedule', $chatbot_markov_chain_build_schedule);
        prod_trace( 'NOTICE', 'chatbot_markov_chain_scheduler: ' . $chatbot_markov_chain_build_schedule);
        return;
    }

    // Update the status as 'In Process'
    update_option('chatbot_markov_chain_build_status', 'In Process');
    prod_trace( 'NOTICE', 'chatbot_markov_chain_build_status: ' . $chatbot_markov_chain_build_status);

    // Reset the results message
    update_option('chatbot_markov_chain_build_results', '');

    // Run the Markov Chain building and saving process
    chatbot_markov_chain_scan();

    // Update the status as 'Completed'
    update_option('chatbot_markov_chain_build_status', 'Completed');

    // DIAG - Diagnostics - Ver 2.1.6
    // back_trace( 'NOTICE', 'chatbot_markov_chain_scheduler - END');

}
add_action('chatbot_markov_chain_scheduler_hook', 'chatbot_markov_chain_scheduler');

// Check if the Markov Chain needs to be built or updated
function chatbot_markov_chain_scan() {

    // DIAG - Diagnostics - Ver 2.1.6
    // back_trace( 'NOTICE', 'chatbot_markov_chain_scan - Start');

    // Get the current schedule setting
    $run_scanner = esc_attr(get_option('chatbot_markov_chain_build_schedule', 'No'));

    // Update the status to 'In Process' and log the current time
    update_option('chatbot_markov_chain_build_status', 'In Process');

    if (!isset($run_scanner)) {
        $run_scanner = 'No';
    }

    // Reset the results message
    update_option('chatbot_markov_chain_build_results', '');

    // Run the Markov Chain building and saving process
    runMarkovChatbotAndSaveChain();

    // DIAG - Diagnostics - Ver 2.1.6
    // back_trace( 'NOTICE', 'chatbot_markov_chain_scan - End');

}
add_action('chatbot_markov_chain_scan_hook', 'chatbot_markov_chain_scan');

// Markov Chain Build Schedule handler
function chatbot_markov_chain_build_results_callback($run_scanner) {

    // DIAG - Diagnostics - Ver 2.1.6
    // back_trace( 'NOTICE', 'chatbot_markov_chain_build_results_callback');
    // back_trace( 'NOTICE', '$run_scanner: ' . $run_scanner);
    // back_trace( 'NOTICE', 'chatbot_markov_chain_build_schedule: ' . esc_attr(get_option('chatbot_markov_chain_build_schedule')));

    // update_option('chatbot_markov_chain_last_updated', date('Y-m-d H:i:s')); // REMOVED - Ver 2.2.0 - 2924-11-27

    if (!isset($run_scanner)) {
        $run_scanner = 'No';
    }

    // Clear and reschedule hooks based on $run_scanner
    if (in_array($run_scanner, ['Now', 'Hourly', 'Daily', 'Twice Daily', 'Weekly', 'Disable', 'Cancel'])) {
        
        // Clear any existing hooks
        wp_clear_scheduled_hook('chatbot_markov_chain_scan_hook');
        
        if ($run_scanner === 'Cancel' || $run_scanner === 'Disable') {

            // Handle 'Cancel' and 'Disable'
            $status = ($run_scanner === 'Cancel') ? 'Cancelled' : 'Disabled';
            update_option('chatbot_markov_chain_build_status', $status);
            update_option('chatbot_markov_chain_build_schedule', 'No');
            update_option('chatbot_markov_chain_scan_interval', 'No Schedule');
            update_option('chatbot_markov_chain_build_action', strtolower($status));

        } else {

            if (!wp_next_scheduled('chatbot_markov_chain_scan_hook')) {
                // Log the schedule
                update_option('chatbot_markov_chain_build_status', 'In Process');

                // Handle valid scheduling options
                $interval_mapping = [
                    'Now' => 10, // Immediate execution, 10 seconds from now
                    'Hourly' => 'hourly',
                    'Twice Daily' => 'twicedaily',
                    'Daily' => 'daily',
                    'Weekly' => 'weekly'
                ];

                $timestamp = time() + 10; // Run 10 seconds from now
                $interval = $interval_mapping[$run_scanner];

                if ($run_scanner === 'Now') {
                    wp_schedule_single_event($timestamp, 'chatbot_markov_chain_scan_hook');
                } else {
                    wp_schedule_event($timestamp, $interval, 'chatbot_markov_chain_scan_hook');
                }

                // Log scan interval - Ver 2.1.6
                if ($interval === 'Now') {
                    update_option('chatbot_markov_chain_scan_interval', 'No Schedule');
                } else {
                    update_option('chatbot_markov_chain_scan_interval', $run_scanner);
                }

                // Reset before reloading the page
                $run_scanner = 'No';
                update_option('chatbot_markov_chain_build_schedule', 'No');
            }
        }
    }
}

// Register a custom weekly interval if not already defined
add_filter('cron_schedules', 'chatbot_chatgpt_add_weekly_schedule');
function chatbot_chatgpt_add_weekly_schedule($schedules) {
    $schedules['weekly'] = array(
        'interval' => 604800, // 1 week in seconds
        'display' => __('Once Weekly')
    );
    return $schedules;
}
