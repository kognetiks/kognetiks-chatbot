<?php
/**
 * Chatbot ChatGPT for WordPress - Notices
 *
 * This file contains the code for the Chatbot ChatGPT settings page.
 * It handles the notices and other parameters.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) )
die;

// Notify outcomes - Ver 1.6.3
function display_option_value_admin_notice() {


    // Suppress Notices On/Off - Ver 1.6.5
    global $chatbot_chatgpt_suppress_notices;
    $chatbot_chatgpt_suppress_notices = esc_attr(get_option('chatbot_chatgpt_suppress_notices', 'Off'));

    if ($chatbot_chatgpt_suppress_notices == 'On') {
        return;
    }

    $kn_results = get_option('chatbot_chatgpt_kn_results');
    if ($kn_results) {
        // Check if notice is already dismissed
        $dismiss_url = wp_nonce_url(
            add_query_arg('dismiss_chatgpt_notice', '1'),
            'dismiss_chatgpt_notice',
            '_chatgpt_dismiss_nonce'
        );
            echo '<div class="notice notice-success is-dismissible"><p><b>Knowledge Navigator Outcome:</b> ' . $kn_results . ' <a href="' . $dismiss_url . '">Dismiss</a></p></div>';
            // error_log( 'Chatbot ChatGPT: Knowledge Navigator Outcome: ' . $kn_results);
    }

    // FIXME - NOT CURRENTLY WORKING WITH API-TEST.PHP
    // $api_status = get_option('chatbot_chatgpt_api_status');
    // if ($api_status != 'Success: Connection to ChatGPT API was successful!') {
    //     $dismiss_url = wp_nonce_url(
    //         add_query_arg('dismiss_api_status_notice', '1'),
    //         'dismiss_api_status_notice',
    //         '_chatgpt_dismiss_nonce'
    //     );
    //         echo '<div class="notice notice-success is-dismissible"><p>' . $api_status . ' <a href="' . $dismiss_url . '">Dismiss</a></p></div>';
    //         error_log('API Status: ' . $api_status);
    // }

    $kn_status = get_option('chatbot_chatgpt_kn_status');
    if ($kn_status === 'Never Run') {
        $dismiss_url = wp_nonce_url(
            add_query_arg('dismiss_kn_status_notice', '1'),
            'dismiss_kn_status_notice',
            '_chatgpt_dismiss_nonce'
        );
            echo '<div class="notice notice-success is-dismissible"><p>Please visit the <b>Knowledge Navigator</b> settings, select a <b>Run Schedule</b>, then <b>Save Settings</b>. <a href="' . $dismiss_url . '">Dismiss</a></p></div>';
            // error_log( 'Chatbot ChatGPT: API Status: ' . $kn_status);
    }

}
add_action('admin_notices', 'display_option_value_admin_notice');


// Handle outcome notification dismissal - Ver 1.6.3
function dismiss_chatgpt_notice() {
    if (isset($_GET['dismiss_chatgpt_notice'])) {
        delete_option('chatbot_chatgpt_kn_results');
    }
}
add_action('admin_init', 'dismiss_chatgpt_notice');


// FIXME - NOT CURRENTLY WORKING WITH API-TEST.PHP
// Handle outcome notification dismissal - Ver 1.6.3
// function dismiss_api_status_notice() {
//     if (isset($_GET['dismiss_api_status_notice'])) {
//         delete_option('chatbot_chatgpt_api_status');
//     }
// }
// add_action('admin_init', 'dismiss_chatgpt_notice');
