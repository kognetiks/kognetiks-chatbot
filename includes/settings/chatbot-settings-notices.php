<?php
/**
 * Kognetiks Chatbot - Notices
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

// Helper function for multisite-aware option handling - Ver 2.4.1
function chatbot_chatgpt_get_option($option_name, $default = false) {
    if (is_multisite()) {
        // Check if plugin is network-activated
        if (!function_exists('is_plugin_active_for_network')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        $plugin_file = 'chatbot-chatgpt/chatbot-chatgpt.php';
        if (is_plugin_active_for_network($plugin_file)) {
            return get_site_option($option_name, $default);
        }
    }
    return get_option($option_name, $default);
}

// Helper function for multisite-aware option update - Ver 2.4.1
function chatbot_chatgpt_update_option($option_name, $value) {
    if (is_multisite()) {
        // Check if plugin is network-activated
        if (!function_exists('is_plugin_active_for_network')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        $plugin_file = 'chatbot-chatgpt/chatbot-chatgpt.php';
        if (is_plugin_active_for_network($plugin_file)) {
            return update_site_option($option_name, $value);
        }
    }
    return update_option($option_name, $value);
}

// Helper function for multisite-aware option deletion - Ver 2.4.1
function chatbot_chatgpt_delete_option($option_name) {
    if (is_multisite()) {
        // Check if plugin is network-activated
        if (!function_exists('is_plugin_active_for_network')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        $plugin_file = 'chatbot-chatgpt/chatbot-chatgpt.php';
        if (is_plugin_active_for_network($plugin_file)) {
            return delete_site_option($option_name);
        }
    }
    return delete_option($option_name);
}

// Check if user/site is Premium (Freemius) - Ver 2.4.1
function chatbot_chatgpt_user_is_premium() {
    // If Freemius function doesn't exist, treat as NOT premium
    if (!function_exists('chatbot_chatgpt_freemius')) {
        return false;
    }
    
    $fs = chatbot_chatgpt_freemius();
    
    // If Freemius object is not available, treat as NOT premium
    if (!is_object($fs)) {
        return false;
    }
    
    // Check if user can use premium code AND is on Premium plan
    if ($fs->can_use_premium_code__premium_only() && $fs->is_plan('Premium')) {
        return true;
    }
    
    return false;
}

// Track plugin version and detect upgrades - Ver 2.4.1
// Note: Version tracking is primarily handled in chatbot-upgrade.php
// This function ensures stored version is set on first admin load if missing
function chatbot_chatgpt_track_version() {
    global $chatbot_chatgpt_plugin_version;
    
    $stored_version = chatbot_chatgpt_get_option('chatbot_chatgpt_version_installed', '');
    $current_version = $chatbot_chatgpt_plugin_version;
    
    // If stored version is empty, set it (first install scenario)
    if (empty($stored_version) && !empty($current_version)) {
        chatbot_chatgpt_update_option('chatbot_chatgpt_version_installed', $current_version);
    }
}
add_action('admin_init', 'chatbot_chatgpt_track_version', 1);

// Handle snooze of reporting notice - Ver 2.4.1
function chatbot_chatgpt_snooze_reporting_notice() {
    // Only process on plugin admin pages
    if (!isset($_GET['page']) || $_GET['page'] !== 'chatbot-chatgpt') {
        return;
    }
    
    // Check if snooze was requested
    if (!isset($_GET['kchat_snooze_reporting_notice']) || $_GET['kchat_snooze_reporting_notice'] !== '1') {
        return;
    }
    
    // Verify user capability
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Verify nonce
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'kchat_snooze_reporting_notice')) {
        return;
    }
    
    // Set snooze until timestamp (7 days from now)
    $snooze_until = time() + (7 * DAY_IN_SECONDS);
    chatbot_chatgpt_update_option('chatbot_chatgpt_reporting_notice_snooze_until', $snooze_until);
    
    // Redirect back to same page without query args
    $current_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'general';
    $redirect_url = remove_query_arg(array('kchat_snooze_reporting_notice', '_wpnonce'), admin_url('admin.php?page=chatbot-chatgpt&tab=' . $current_tab));
    wp_safe_redirect($redirect_url);
    exit;
}
add_action('admin_init', 'chatbot_chatgpt_snooze_reporting_notice');

// Handle dismissal of reporting notice - Ver 2.4.1
function chatbot_chatgpt_dismiss_reporting_notice() {
    // Only process on plugin admin pages
    if (!isset($_GET['page']) || $_GET['page'] !== 'chatbot-chatgpt') {
        return;
    }
    
    // Check if dismissal was requested
    if (!isset($_GET['kchat_dismiss_reporting_notice']) || $_GET['kchat_dismiss_reporting_notice'] !== '1') {
        return;
    }
    
    // Verify user capability
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Verify nonce
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'kchat_dismiss_reporting_notice')) {
        return;
    }
    
    // Mark notice as dismissed
    chatbot_chatgpt_update_option('chatbot_chatgpt_reporting_notice_dismissed', '1');
    
    // Clear snooze to avoid stale state
    chatbot_chatgpt_delete_option('chatbot_chatgpt_reporting_notice_snooze_until');
    
    // Redirect back to same page without query args
    $current_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'general';
    $redirect_url = remove_query_arg(array('kchat_dismiss_reporting_notice', '_wpnonce'), admin_url('admin.php?page=chatbot-chatgpt&tab=' . $current_tab));
    wp_safe_redirect($redirect_url);
    exit;
}
add_action('admin_init', 'chatbot_chatgpt_dismiss_reporting_notice');

// Display reporting feature discovery notice - Ver 2.4.1
function chatbot_chatgpt_reporting_notice() {
    // Only show on plugin admin pages
    if (!isset($_GET['page']) || $_GET['page'] !== 'chatbot-chatgpt') {
        return;
    }
    
    // Only show to users who can manage options
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Suppress notice for Premium/trial users (any premium entitlement)
    // If Freemius is available and user can use premium code, never show discovery notice
    if (function_exists('chatbot_chatgpt_freemius')) {
        $fs = chatbot_chatgpt_freemius();
        if (is_object($fs) && is_callable([$fs, 'can_use_premium_code__premium_only'])) {
            if ($fs->can_use_premium_code__premium_only()) {
                return; // Premium/trial users: suppress discovery notice
            }
        }
    }
    
    // Check if notice was dismissed
    $dismissed = chatbot_chatgpt_get_option('chatbot_chatgpt_reporting_notice_dismissed', '0');
    if ($dismissed === '1') {
        return;
    }
    
    // Check if notice is snoozed
    $snooze_until = chatbot_chatgpt_get_option('chatbot_chatgpt_reporting_notice_snooze_until', 0);
    if (!empty($snooze_until) && is_numeric($snooze_until) && $snooze_until > time()) {
        // Notice is still snoozed
        return;
    }
    
    // Show notice if:
    // 1. First install (stored version is empty) - notice will show once
    // 2. After upgrade (dismissal was cleared by upgrade function)
    // 3. Snooze period has expired
    // The version tracking and upgrade functions handle setting/updating the stored version
    
    // Build dismiss URL
    $current_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'general';
    $dismiss_url = add_query_arg(
        array(
            'page' => 'chatbot-chatgpt',
            'tab' => $current_tab,
            'kchat_dismiss_reporting_notice' => '1',
            '_wpnonce' => wp_create_nonce('kchat_dismiss_reporting_notice'),
        ),
        admin_url('admin.php')
    );
    
    // Build snooze URL
    $snooze_url = add_query_arg(
        array(
            'page' => 'chatbot-chatgpt',
            'tab' => $current_tab,
            'kchat_snooze_reporting_notice' => '1',
            '_wpnonce' => wp_create_nonce('kchat_snooze_reporting_notice'),
        ),
        admin_url('admin.php')
    );
    
    // Build reporting URL
    $reporting_url = admin_url('admin.php?page=chatbot-chatgpt&tab=reporting');
    
    // Display notice
    echo '<div class="notice notice-info is-dismissible">';
    echo '<p><strong>Your chatbot is already having conversations.</strong></p>';
    echo '<p>We\'ve added a new Reporting section that summarizes activity and highlights where attention may be needed.</p>';
    echo '<p>';
    echo '<a href="' . esc_url($reporting_url) . '" class="button button-primary">View Reports</a> ';
    echo '<a href="' . esc_url($snooze_url) . '" class="button">Remind me later</a> ';
    echo '<a href="' . esc_url($dismiss_url) . '" class="button">Dismiss</a>';
    echo '</p>';
    echo '</div>';
}
add_action('admin_notices', 'chatbot_chatgpt_reporting_notice');