<?php
/**
 * Kognetiks Chatbot for WordPress - Knowledge Navigator - Scheduler - Ver 1.6.3
 *
 * This is the file that schedules the Knowledge Navigator.
 * Scheduled can be now, daily, weekly, etc.
 * 
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Handle long-running scripts with a scheduled event function - Ver 1.6.1
function knowledge_navigator_scan() {

    // DIAG - Diagnostic - Ver 1.6.3
    // back_trace( 'NOTICE', 'ENTERING knowledge_navigator_scan()');
    
    $run_scanner = esc_attr(get_option('chatbot_chatgpt_knowledge_navigator', 'No'));
    
    // DIAG - Diagnostic - Ver 1.9.6
    // back_trace( 'NOTICE', '$run_scanner: ' . $run_scanner );

    // The second parameter is the default value if the option is not set.
    update_option('chatbot_chatgpt_kn_status', 'In Process');

    if (!isset($run_scanner)) {
        $run_scanner = 'No';
    }

    // FIXME - Handle the case where the scanner is already running

    // FIXME - Handle the case where the user wants to stop the scanner
    // 'Cancel' the scheduled event

    // Reset the results message
    update_option('chatbot_chatgpt_kn_results', '');

    // New process to acquire the content - Ver 1.9.6 - 2024 04 18
    // DIAG - Diagnostic - Ver 1.9.6
    // back_trace( 'NOTICE', 'chatbot_chatgpt_kn_action - schedule kicked off' );

    update_option( 'chatbot_chatgpt_kn_action', 'initialize' );

    chatbot_kn_acquire_controller();

    // DIAG - Diagnostic - Ver 1.6.3
    // back_trace( 'NOTICE', 'EXITING knowledge_navigator_scan()');

}
add_action('knowledge_navigator_scan_hook', 'knowledge_navigator_scan');
