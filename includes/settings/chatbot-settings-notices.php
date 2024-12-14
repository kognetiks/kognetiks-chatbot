<?php
/**
 * Kognetiks Chatbot for WordPress - Notices
 *
 * This file contains the code for the Chatbot settings page.
 * It handles the notices and other parameters.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// General function to display the message - Ver 1.8.1
function chatbot_chatgpt_general_admin_notice($message = null) {
    if (!empty($message)) {
        printf('<div class="%1$s"><p><strong>Kognetiks Chatbot: </strong>%2$s</p></div>', 'notice notice-error is-dismissible', $message);
        return;
    }
}
add_action('admin_notices', 'chatbot_chatgpt_general_admin_notice');

// Notify outcomes - Ver 1.6.3
function display_option_value_admin_notice() {
    // Suppress Notices On/Off - Ver 1.6.5
    global $chatbot_chatgpt_suppress_notices;
    $chatbot_chatgpt_suppress_notices = esc_attr(get_option('chatbot_chatgpt_suppress_notices', 'Off'));

    if ($chatbot_chatgpt_suppress_notices == 'On') {
        return;
    }

    $kn_results = esc_attr(get_option('chatbot_chatgpt_kn_results'));
    if ($kn_results) {
        // Check if notice is already dismissed
        $dismiss_url = wp_nonce_url(
            add_query_arg('dismiss_chatgpt_notice', '1'),
            'dismiss_chatgpt_notice',
            '_chatgpt_dismiss_nonce'
        );
        echo '<div class="notice notice-success is-dismissible"><p><strong>Kognetiks Chatbot:</strong> ' . $kn_results . ' <a href="' . $dismiss_url . '">Dismiss</a></p></div>';
    }

    $kn_status = esc_attr(get_option('chatbot_chatgpt_kn_status'));
    $kn_dismissed = esc_attr(get_option('chatbot_chatgpt_kn_dismissed'));

    if ($kn_status === 'Disable' || $kn_dismissed === '1') {
        return;
    } elseif ($kn_status === 'Never Run') {
        $dismiss_url = wp_nonce_url(
            add_query_arg('dismiss_kn_status_notice', '1'),
            'dismiss_kn_status_notice',
            '_chatgpt_dismiss_nonce'
        );
        echo '<div class="notice notice-success is-dismissible"><p><strong>Kognetiks Chatbot:</strong> Please visit the <b>Knowledge Navigator</b> settings, select a <b>Run Schedule</b>, then <b>Save Settings</b>. <a href="' . $dismiss_url . '">Dismiss</a></p></div>';
    }
}
add_action('admin_notices', 'display_option_value_admin_notice');

// Handle outcome notification dismissal - Ver 1.6.3
function dismiss_chatgpt_notice() {
    if (isset($_GET['dismiss_chatgpt_notice'])) {
        delete_option('chatbot_chatgpt_kn_results');
    }
    if (isset($_GET['dismiss_kn_status_notice'])) {
        update_option('chatbot_chatgpt_kn_dismissed', '1');
        // DIAG - Diagnostics - Ver 2.0.4
        // back_trace( 'NOTICE' , 'chatbot_chatgpt_kn_dismissed updated to 1');
    }
}
add_action('admin_init', 'dismiss_chatgpt_notice');
