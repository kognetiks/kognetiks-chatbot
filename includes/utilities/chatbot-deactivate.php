<?php
/**
 * Kognetiks Chatbot for WordPress - Deactivate and/or Delete the plugin
 *
 * This file contains the code for deactivating and/or deleting the plugin.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Deactivation Hook - Revised 1.9.9
function chatbot_chatgpt_deactivate() {

    // DIAG - Log the activation
    back_trace( 'NOTICE', 'Plugin deactivation started');

    // Logic to run during deactivation
    // FIXME - THIS IS NOT DELETING THE PLUGIN - JUST DEACTIVATION

    // FIXME - Asked what data should be removed
    // 
    // DB - chatbot_chatgpt_conversation_log
    // DB - chatbot_chatgpt_interactions
    // DB - chatbot_chatgpt_knowledge_base
    // DB - chatbot_chatgpt_knowledge_base_tfidf

    // FIXME - Asked what transients should be removed
    // 

    // FIXME - Asked what options should be removed
    //
    // OPTIONS - *chatbot_chatgpt*

    // DIAG - Log the activation
    back_trace( 'NOTICE', 'Plugin deactivation completed');

    return;

}

// Delete Plugin Data Notice - Ver 1.9.9
add_action('admin_notices', 'chatbot_chatgpt_admin_notices');
function chatbot_chatgpt_admin_notices() {
    $chatbot_chatgpt_delete_data = get_option('chatbot_chatgpt_delete_data');
    if (empty($chatbot_chatgpt_delete_data)) {
        echo '<div class="notice notice-warning is-dismissible">
            <p><strong>Kognetiks Chatbot:</strong> Remember to set data deletion preferences in the plugin settings on the Messages tab if you plan to uninstall the plugin.</p>
        </div>';
    }
}

// Upgrade Logic - Revised 1.9.9
function chatbot_chatgpt_uninstall(){

    global $wpdb;

    // DIAG - Log the uninstall
    back_trace( 'NOTICE', 'Plugin uninstall started');

    // Ask if the data should be removed, if not return
    if (get_option('chatbot_chatgpt_delete_data') != 'yes') {
        return;
    }

    // Check for a setting that specifies whether to delete data
    if (get_option('chatbot_chatgpt_delete_data') == 'yes') {
    
        // Delete options
        back_trace( 'NOTICE', 'Deleting options');
        $wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE 'chatbot_chatgpt%'");

        // Delete tables
        back_trace( 'NOTICE', 'Deleting tables');
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}chatbot_chatgpt_conversation_log");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}chatbot_chatgpt_interactions");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}chatbot_chatgpt_knowledge_base");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}chatbot_chatgpt_knowledge_base_tfidf");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}chatbot_chatgpt_knowledge_base_word_count");

        // Delete transients
        back_trace( 'NOTICE', 'Deleting transients');
        $wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '_transient_chatbot_chatgpt%' OR option_name LIKE '_transient_timeout_chatbot_chatgpt%'");

        // Delete any scheduled cron events
        back_trace( 'NOTICE', 'Deleting cron events');
        $crons = _get_cron_array();
        foreach ($crons as $timestamp => $cron) {
            foreach ($cron as $hook => $events) {
                if (strpos($hook, 'chatbot_chatgpt') !== false) {
                    foreach ($events as $event) {
                        wp_unschedule_event($timestamp, $hook, $event['args']);
                    }
                }
            }
        }

        // Delete the cron event called "knowledge_navigator_scan_hook"
        back_trace( 'NOTICE', 'Deleting cron event: knowledge_navigator_scan_hook');
        wp_clear_scheduled_hook('knowledge_navigator_scan_hook');

    }

    // DIAG - Log the uninstall
    back_trace( 'NOTICE', 'Plugin uninstall completed');

    return;
}

