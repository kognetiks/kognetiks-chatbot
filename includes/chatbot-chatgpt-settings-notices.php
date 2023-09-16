<?php
/**
 * Chatbot ChatGPT for WordPress - Notices
 *
 * This file contains the code for the Chatbot ChatGPT settings page.
 * It allows users to configure the API key and other parameters
 * required to access the ChatGPT API from their own account.
 *
 * @package chatbot-chatgpt
 */

// TODO If this file is called directly, abort.
if ( ! defined( 'WPINC' ) )
die;

// Notify outcomes - Ver 1.6.3
function display_option_value_admin_notice() {

    $kn_results = get_option('chatbot_chatgpt_kn_results');

    // Dismissable notice - Ver 1.6.1
    if ($kn_results) {
        // Check if notice is already dismissed
        $dismiss_url = wp_nonce_url(
            add_query_arg('dismiss_chatgpt_notice', '1'),
            'dismiss_chatgpt_notice',
            '_chatgpt_dismiss_nonce'
        );
            echo '<div class="notice notice-success is-dismissible"><p>Knowledge Navigator Outcome: ' . $kn_results . ' <a href="' . $dismiss_url . '">Dismiss</a></p></div>';
    }

    $api_status = get_option('chatbot_chatgpt_api_status');
    if ($api_status != 'Success: Connection to ChatGPT API was successful!') {
        $dismiss_url = wp_nonce_url(
            add_query_arg('dismiss_api_status_notice', '1'),
            'dismiss_api_status_notice',
            '_chatgpt_dismiss_nonce'
        );
            echo '<div class="notice notice-success is-dismissible"><p>' . $api_status . ' <a href="' . $dismiss_url . '">Dismiss</a></p></div>';
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


// Handle outcome notification dismissal - Ver 1.6.3
function dismiss_api_status_notice() {
    if (isset($_GET['dismiss_api_status_notice'])) {
        delete_option('chatbot_chatgpt_api_status');
    }
}
add_action('admin_init', 'dismiss_chatgpt_notice');
