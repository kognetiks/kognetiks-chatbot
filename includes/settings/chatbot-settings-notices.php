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
// Updated to use centralized helper function for consistency
function chatbot_chatgpt_user_is_premium() {
    // Use the centralized helper function
    return function_exists('chatbot_chatgpt_is_premium') ? chatbot_chatgpt_is_premium() : false;
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

// Handle dismissal of reporting notice - Ver 2.4.4 (Silent Mode)
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
    
    // Enter Silent Mode instead of permanent dismissal
    // Silent Mode: Notice stays quiet until Conversation Logging is enabled AND threshold activity is reached
    chatbot_chatgpt_update_option('chatbot_chatgpt_reporting_notice_silent_mode', '1');
    chatbot_chatgpt_update_option('chatbot_chatgpt_reporting_notice_silent_mode_started', current_time('timestamp'));
    chatbot_chatgpt_update_option('chatbot_chatgpt_reporting_notice_silent_mode_reentry_triggered', '0');
    
    // Clear snooze to avoid stale state
    chatbot_chatgpt_delete_option('chatbot_chatgpt_reporting_notice_snooze_until');
    
    // Clear threshold check cache when entering silent mode
    $cache_key = 'kchat_notice_reentry_' . get_current_blog_id() . '_' . get_current_user_id();
    delete_transient($cache_key);
    
    // Redirect back to same page without query args
    $current_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'general';
    $redirect_url = remove_query_arg(array('kchat_dismiss_reporting_notice', '_wpnonce'), admin_url('admin.php?page=chatbot-chatgpt&tab=' . $current_tab));
    wp_safe_redirect($redirect_url);
    exit;
}
add_action('admin_init', 'chatbot_chatgpt_dismiss_reporting_notice');

// Check Silent Mode re-entry thresholds - Ver 2.4.4
// Returns true if notice should re-surface (thresholds met), false otherwise
function chatbot_chatgpt_reporting_notice_check_silent_mode_thresholds($silent_mode_started) {
    // Check if Conversation Logging is enabled (required for re-entry)
    $logging_enabled = get_option('chatbot_chatgpt_enable_conversation_logging', 'Off');
    if ($logging_enabled !== 'On') {
        return false; // Conversation logging must be enabled
    }
    
    // Use transient cache to avoid hitting DB on every page load
    // Cache key includes blog ID and user ID for multisite compatibility
    $cache_key = 'kchat_notice_reentry_' . get_current_blog_id() . '_' . get_current_user_id();
    $cached_result = get_transient($cache_key);
    
    if ($cached_result !== false) {
        // Return cached result (1 = threshold met, 0 = not met)
        return ($cached_result === '1');
    }
    
    // Count distinct conversations since Silent Mode started
    // Using COUNT(DISTINCT session_id) to count actual conversations, not individual message rows
    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';
    
    // Convert silent mode start timestamp to MySQL DATETIME string
    // Using gmdate() to ensure consistent UTC conversion for database queries
    $since_datetime = gmdate('Y-m-d H:i:s', $silent_mode_started);
    
    // Query: Count distinct session_ids since silent mode started
    // Only count Visitor/User messages (exclude Chatbot responses and token rows)
    $conversation_count = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT session_id)
        FROM {$table_name}
        WHERE interaction_time >= %s
        AND user_type IN ('Visitor', 'User')
        AND message_text IS NOT NULL
        AND TRIM(message_text) != ''
    ", $since_datetime));
    
    // Threshold: 10 distinct conversations
    $threshold_met = ($conversation_count >= 10);
    
    // Cache result for 5 minutes
    set_transient($cache_key, $threshold_met ? '1' : '0', 5 * MINUTE_IN_SECONDS);
    
    return $threshold_met;
}

// Display reporting feature discovery notice - Ver 2.4.4 (Silent Mode)
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
    // Check if user has premium access (either via premium code or Premium plan)
    // This handles the case where user upgraded to Premium but premium code isn't activated yet
    $is_premium_user = false;
    if (function_exists('chatbot_chatgpt_is_premium')) {
        $is_premium_user = chatbot_chatgpt_is_premium();
    } elseif (function_exists('chatbot_chatgpt_freemius')) {
        // Fallback to original check if helper function not available
        $fs = chatbot_chatgpt_freemius();
        if (is_object($fs)) {
            if (is_callable([$fs, 'can_use_premium_code'])) {
                $is_premium_user = $fs->can_use_premium_code();
            } elseif (is_callable([$fs, 'is_plan'])) {
                // Also check plan status as fallback
                $is_premium_user = $fs->is_plan('premium', false);
            }
        }
    }
    
    // If user is premium, automatically dismiss the notice and don't show it
    if ($is_premium_user) {
        // Auto-dismiss for premium users to prevent it from reappearing
        chatbot_chatgpt_update_option('chatbot_chatgpt_reporting_notice_dismissed', '1');
        chatbot_chatgpt_delete_option('chatbot_chatgpt_reporting_notice_snooze_until');
        return; // Premium users: suppress discovery notice
    }
    
    // Migration: Handle backward compatibility for users who dismissed before Silent Mode
    // If old dismissed option is set but silent mode is not, migrate to silent mode
    $old_dismissed = chatbot_chatgpt_get_option('chatbot_chatgpt_reporting_notice_dismissed', '0');
    $silent_mode = chatbot_chatgpt_get_option('chatbot_chatgpt_reporting_notice_silent_mode', '0');
    
    if ($old_dismissed === '1' && $silent_mode !== '1') {
        // Migrate old dismissal to Silent Mode with re-entry already triggered
        // This prevents previously dismissed users from suddenly seeing the notice again
        chatbot_chatgpt_update_option('chatbot_chatgpt_reporting_notice_silent_mode', '1');
        chatbot_chatgpt_update_option('chatbot_chatgpt_reporting_notice_silent_mode_started', current_time('timestamp'));
        chatbot_chatgpt_update_option('chatbot_chatgpt_reporting_notice_silent_mode_reentry_triggered', '1');
    }
    
    // Check if notice is snoozed (snooze takes precedence over silent mode)
    $snooze_until = chatbot_chatgpt_get_option('chatbot_chatgpt_reporting_notice_snooze_until', 0);
    if (!empty($snooze_until) && is_numeric($snooze_until) && $snooze_until > time()) {
        // Notice is still snoozed
        return;
    }
    
    // Silent Mode logic: Check if notice should be shown
    $silent_mode = chatbot_chatgpt_get_option('chatbot_chatgpt_reporting_notice_silent_mode', '0');
    $silent_mode_started = chatbot_chatgpt_get_option('chatbot_chatgpt_reporting_notice_silent_mode_started', 0);
    $reentry_triggered = chatbot_chatgpt_get_option('chatbot_chatgpt_reporting_notice_silent_mode_reentry_triggered', '0');
    
    $is_reentry = false; // Flag to determine if notice is showing due to re-entry
    
    if ($silent_mode === '1') {
        // Silent Mode is active
        if ($reentry_triggered === '1') {
            // Re-entry already triggered - do not show notice again
            return;
        }
        
        // Check if thresholds are met for re-entry
        if (!empty($silent_mode_started) && is_numeric($silent_mode_started)) {
            $thresholds_met = chatbot_chatgpt_reporting_notice_check_silent_mode_thresholds($silent_mode_started);
            
            if ($thresholds_met) {
                // Thresholds met - show notice and mark re-entry as triggered
                chatbot_chatgpt_update_option('chatbot_chatgpt_reporting_notice_silent_mode_reentry_triggered', '1');
                $is_reentry = true;
            } else {
                // Thresholds not met - do not show notice
                return;
            }
        } else {
            // Silent mode active but no start timestamp - treat as not active
            // This shouldn't happen, but handle gracefully
            return;
        }
    }
    // If silent mode is OFF, show notice normally (fall through to display)
    
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
    
    // Display notice with conditional copy based on re-entry status
    echo '<div class="notice notice-info is-dismissible">';
    
    if ($is_reentry) {
        // Re-entry copy: Notice shown after Silent Mode threshold met
        echo '<p><strong>Your site had conversations you didn&apos;t see.</strong></p>';
        echo '<p>Conversation Digest and Proof-of-Value reports are ready. Turn real conversations into insight, automatically.</p>';
        echo '<p>';
        echo '<a href="' . esc_url($reporting_url) . '" class="button button-primary">View Your Digest</a> ';
    } else {
        // Initial/standard copy
        echo '<p><strong>Your site had conversations you didn&apos;t see.</strong></p>';
        echo '<p>Your first Conversation Digest and Proof-of-Value reports turn those interactions into clear insight, automatically. No dashboards. No guesswork.</p>';
        echo '<p><em>Want stronger signals?</em> Insights highlights confusion, drop-offs, and recurring issues that may need attention.</p>';
        echo '<p>';
        echo '<a href="' . esc_url($reporting_url) . '" class="button button-primary">View Your First Digest</a> ';
    }
    
    // Common buttons for both cases
    echo '<a href="' . esc_url($snooze_url) . '" class="button">Remind me later</a> ';
    echo '<a href="' . esc_url($dismiss_url) . '" class="button">Dismiss</a>';
    echo '</p>';
    echo '</div>';
}
add_action('admin_notices', 'chatbot_chatgpt_reporting_notice');

// Display notice for users in trial but not running premium build - Ver 2.4.2
function chatbot_chatgpt_trial_premium_build_notice() {
    // Only show on plugin admin pages
    if (!isset($_GET['page']) || $_GET['page'] !== 'chatbot-chatgpt') {
        return;
    }
    
    // Only show to users who can manage options
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Check if Freemius is available
    if (!function_exists('chatbot_chatgpt_freemius')) {
        return;
    }
    
    $fs = chatbot_chatgpt_freemius();
    if (!is_object($fs)) {
        return;
    }
    
    // Detect if running premium build
    $running_premium_build = false;
    if (method_exists($fs, 'is__premium_only')) {
        $running_premium_build = $fs->is__premium_only();
    }
    
    // If running premium build, don't show notice
    if ($running_premium_build) {
        return;
    }
    
    // Check if user is in trial
    $is_trial = false;
    if (method_exists($fs, 'is_trial')) {
        $is_trial = $fs->is_trial();
    }
    
    // If not in trial, don't show notice
    if (!$is_trial) {
        return;
    }
    
    // Check if user is paying (if paying, they have premium access even in free build)
    $is_paying = false;
    if (method_exists($fs, 'is_paying')) {
        $is_paying = $fs->is_paying();
    }
    
    // If paying, don't show notice
    if ($is_paying) {
        return;
    }
    
    // Check if notice was dismissed
    $dismissed = chatbot_chatgpt_get_option('chatbot_chatgpt_trial_premium_build_notice_dismissed', '0');
    if ($dismissed === '1') {
        return;
    }
    
    // Build dismiss URL
    $current_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'general';
    $dismiss_url = add_query_arg(
        array(
            'page' => 'chatbot-chatgpt',
            'tab' => $current_tab,
            'kchat_dismiss_trial_premium_notice' => '1',
            '_wpnonce' => wp_create_nonce('kchat_dismiss_trial_premium_notice'),
        ),
        admin_url('admin.php')
    );
    
    // Get account/download URL for premium ZIP
    $account_url = '';
    if (method_exists($fs, 'get_account_url')) {
        // Use get_account_url with download_latest action to get download page
        $account_url = $fs->get_account_url('download_latest');
    }
    
    // Display notice
    echo '<div class="notice notice-info is-dismissible">';
    echo '<p><strong>Kognetiks Chatbot:</strong> Your trial is active. Please install the Premium version to access trial features.</p>';
    echo '<p>';
    // Add button to download/access premium ZIP
    if (!empty($account_url)) {
        echo '<a href="' . esc_url($account_url) . '" class="button button-primary" style="margin-right: 10px;">Download Premium Version</a>';
    }
    echo '<a href="' . esc_url($dismiss_url) . '" class="button">Dismiss</a>';
    echo '</p>';
    echo '</div>';
}
add_action('admin_notices', 'chatbot_chatgpt_trial_premium_build_notice');

// Handle dismissal of trial premium build notice - Ver 2.4.2
function chatbot_chatgpt_dismiss_trial_premium_notice() {
    // Only process on plugin admin pages
    if (!isset($_GET['page']) || $_GET['page'] !== 'chatbot-chatgpt') {
        return;
    }
    
    // Check if dismissal was requested
    if (!isset($_GET['kchat_dismiss_trial_premium_notice']) || $_GET['kchat_dismiss_trial_premium_notice'] !== '1') {
        return;
    }
    
    // Verify user capability
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Verify nonce
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'kchat_dismiss_trial_premium_notice')) {
        return;
    }
    
    // Mark notice as dismissed
    chatbot_chatgpt_update_option('chatbot_chatgpt_trial_premium_build_notice_dismissed', '1');
    
    // Redirect back to same page without query args
    $current_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'general';
    $redirect_url = remove_query_arg(array('kchat_dismiss_trial_premium_notice', '_wpnonce'), admin_url('admin.php?page=chatbot-chatgpt&tab=' . $current_tab));
    wp_safe_redirect($redirect_url);
    exit;
}
add_action('admin_init', 'chatbot_chatgpt_dismiss_trial_premium_notice');
