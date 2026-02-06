<?php
/**
 * Kognetiks Chatbot - Uninstall
 *
 * Fired when the plugin is deleted. Removes all plugin tables and options.
 * WordPress runs this file automatically when the user deletes the plugin
 * (deactivate followed by delete). WP_UNINSTALL_PLUGIN is defined by WordPress.
 *
 * @package chatbot-chatgpt
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Load the uninstall logic from the deactivate utility
require_once plugin_dir_path( __FILE__ ) . 'includes/utilities/chatbot-deactivate.php';

if ( function_exists( 'chatbot_chatgpt_uninstall' ) ) {
    chatbot_chatgpt_uninstall();
}
