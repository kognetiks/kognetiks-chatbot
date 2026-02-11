<?php
/**
 * Kognetiks Chatbot - Uninstall - Ver 2.4.5
 *
 * Runs only when the plugin is deleted via WordPress (Plugins → Delete).
 * WordPress includes this file instead of loading the main plugin, so vendor/Freemius
 * are not loaded and the plugin directory (including vendor) can be fully removed.
 *
 * @package chatbot-chatgpt
 */

// WordPress defines WP_UNINSTALL_PLUGIN before including this file.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

$plugin_dir = plugin_dir_path( __FILE__ );
require_once $plugin_dir . 'includes/utilities/chatbot-deactivate.php';

// DIAG - Diagnotics - Ver 2.4.5
if ( defined('WP_DEBUG') && WP_DEBUG ) {
    error_log('uninstall.php - running chatbot_chatgpt_uninstall');
}

// Call the uninstall function
if ( function_exists('chatbot_chatgpt_uninstall') ) {
    chatbot_chatgpt_uninstall();
}

// DIAG - Diagnotics - Ver 2.4.5
if ( defined('WP_DEBUG') && WP_DEBUG ) {
    error_log('uninstall.php - chatbot_chatgpt_uninstall completed');
}
