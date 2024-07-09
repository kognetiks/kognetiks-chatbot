<?php
/**
 * Kognetiks Chatbot for WordPress - Options Exporter - Ver 2.0.6
 *
 * This file contains the code for exporting the chatbot options.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Export the chatbot options to a file
function chatbot_chatgpt_options_exporter() {

    $debug_dir_path = CHATBOT_CHATGPT_PLUGIN_DIR_PATH . 'debug/';
    // back_trace( 'NOTICE', 'results_dir_path: ' . $debug_dir_path);

    if (!file_exists($debug_dir_path)) {
        mkdir($debug_dir_path, 0777, true);
    }

    global $wpdb;
    $options = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}options WHERE option_name LIKE 'chatbot%' AND option_name != 'chatbot_chatgpt_api_key'", ARRAY_A);

    $file = $debug_dir_path . 'chatbot-chatgpt-options.txt';
    file_put_contents($file, print_r($options, true));

    echo "<div>";
    echo "<p>Options have been dumped to file.</p>";
    echo "</div>";

}