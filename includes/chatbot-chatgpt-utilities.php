<?php
/**
 * Chatbot ChatGPT for WordPress - Upgrade the chatbot-chatgpt plugin.
 *
 * This file contains the code for upgrading the plugin.
 * It should run with the plugin is activated, deactivated, or updated.
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