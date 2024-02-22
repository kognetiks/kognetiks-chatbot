<?php
/**
 * Kognetiks Chatbot for WordPress - Utilities - Ver 1.8.1
 *
 * This file contains the code for plugin utilities.
 * It is used to check for mobile devices and other utilities.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Check for device type
function is_mobile_device() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $mobile_agents = array('Mobile', 'Android', 'Silk/', 'Kindle', 'BlackBerry', 'Opera Mini', 'Opera Mobi');

    foreach ($mobile_agents as $device) {
        if (strpos($user_agent, $device) !== false) {
            return true; // Mobile device detected
        }
    }

    return false; // Not a mobile device
}

// Dump DB options to file
function chatbot_chatgpt_dump_options_to_file() {

    // $debug_dir_path = dirname(plugin_dir_path(__FILE__)) . '/debug/';
    // back_trace( 'NOTICE', 'CHATBOT_CHATGPT_PLUGIN_DIR_PATH: ' . CHATBOT_CHATGPT_PLUGIN_DIR_PATH);
    $debug_dir_path = CHATBOT_CHATGPT_PLUGIN_DIR_PATH . 'debug/';
    // back_trace( 'NOTICE', 'results_dir_path: ' . $debug_dir_path);

    if (!file_exists($debug_dir_path)) {
        mkdir($debug_dir_path, 0777, true);
    }

    global $wpdb;
    $options = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}options WHERE option_name LIKE 'chatbot%' AND option_name != 'chatbot_chatgpt_api_key'", ARRAY_A);

    $file = $debug_dir_path . 'chatbot-chatgpt-options.txt';
    file_put_contents($file, print_r($options, true));

}
