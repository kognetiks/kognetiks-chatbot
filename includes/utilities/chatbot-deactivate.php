<?php
/**
 * Kognetiks Chatbot for WordPress - Deactivate and/or Delete the Plugin
 *
 * This file contains the code for deactivating and/or deleting the plugin.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Deactivation Hook - Revised 1.9.9
function chatbot_chatgpt_deactivate() {

    if (empty(esc_attr(get_option('chatbot_chatgpt_delete_data')))) {      
        chatbot_chatgpt_admin_notices();
    }

}

// Delete Plugin Data Notice - Ver 1.9.9
add_action('admin_notices', 'chatbot_chatgpt_admin_notices');
function chatbot_chatgpt_admin_notices() {

    if (empty(esc_attr(get_option('chatbot_chatgpt_delete_data')))) {     

        echo '<div class="notice notice-warning is-dismissible">
            <p><strong>Kognetiks Chatbot:</strong> Remember to set your data deletion preferences in the plugin settings on the Messages tab if you plan to uninstall the plugin.</p>
        </div>';
        update_option('chatbot_chatgpt_delete_data', 'no');

    }

}

// Upgrade Logic - Revised 1.9.9
function chatbot_chatgpt_uninstall(){

    global $wpdb;

    // DIAG - Log the uninstall
    // back_trace( 'NOTICE', 'PLUGIN UNINSTALL STARTED');

    // Ask if the data should be removed, if not return
    if (get_option('chatbot_chatgpt_delete_data') != 'yes') {
        return;
    }

    // Check for a setting that specifies whether to delete data
    if (get_option('chatbot_chatgpt_delete_data') == 'yes') {

        // Delete on-off options
        // back_trace( 'NOTICE', 'Deleting one-off options');
        $wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE 'chatbot_ai_platform_choice'");
    
        // Delete ChatGPT options
        // back_trace( 'NOTICE', 'Deleting ChatGPT options');
        $wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE 'chatbot_chatgpt%'");

        // Delete NVIDIA options
        // back_trace( 'NOTICE', 'Deleting NVIDIA options');
        $wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE 'chatbot_nvidia%'");

        // Delete Markov Chain options
        // back_trace( 'NOTICE', 'Deleting Markov Chain options');
        $wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE 'chatbot_markov%'");

        // Delete ChatGPT tables
        // back_trace( 'NOTICE', 'Deleting tables');
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}chatbot_chatgpt_assistants");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}chatbot_chatgpt_conversation_log");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}chatbot_chatgpt_interactions");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}chatbot_chatgpt_knowledge_base");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}chatbot_chatgpt_knowledge_base_tfidf");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}chatbot_chatgpt_knowledge_base_word_count");

        // Delete NVIDIA tables
        // back_trace( 'NOTICE', 'Deleting NVIDIA tables');
        // NONE CURRENTLY - Ver 2.1.8

        // Delete Markov Chain tables
        // back_trace( 'NOTICE', 'Deleting Markov Chain tables');
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}chatbot_markov_chain");

        // Delete ChatGPT transients
        // back_trace( 'NOTICE', 'Deleting transients');
        $wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '_transient_chatbot_chatgpt%' OR option_name LIKE '_transient_timeout_chatbot_chatgpt%'");

        // Delete NVIDIA transients
        // back_trace( 'NOTICE', 'Deleting NVIDIA transients');
        $wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '_transient_chatbot_nvidia%' OR option_name LIKE '_transient_timeout_chatbot_nvidia%'");

        // Delete Markov Chain transients
        // back_trace( 'NOTICE', 'Deleting Markov Chain transients');
        $wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '_transient_chatbot_markov%' OR option_name LIKE '_transient_timeout_chatbot_markov%'");

        // Delete any scheduled cron events
        // back_trace( 'NOTICE', 'Deleting cron events');
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
        // back_trace( 'NOTICE', 'Deleting cron event: knowledge_navigator_scan_hook');
        wp_clear_scheduled_hook('knowledge_navigator_scan_hook');

    }

    // DIAG - Log the uninstall
    // back_trace( 'NOTICE', 'PLUGIN UNINSTALL COMPLETED');

    return;
}
