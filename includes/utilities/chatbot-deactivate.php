<?php
/**
 * Kognetiks Chatbot - Deactivate and/or Delete the Plugin
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

    // Clean up insights email cron jobs on deactivation
    if (function_exists('kognetiks_insights_unschedule_proof_of_value_email')) {
        kognetiks_insights_unschedule_proof_of_value_email();
    } else {
        // Fallback: clear the hook directly if function doesn't exist
        wp_clear_scheduled_hook('kognetiks_insights_send_proof_of_value_email_hook');
    }
    
    // Clean up conversation digest cron job on deactivation
    wp_clear_scheduled_hook('kognetiks_insights_send_conversation_digest_email_hook');

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

    // Ask if the data should be removed, if not return
    if (esc_attr(get_option('chatbot_chatgpt_delete_data')) != 'yes') {
        return;
    }

    // Check for a setting that specifies whether to delete data
    if (esc_attr(get_option('chatbot_chatgpt_delete_data')) == 'yes') {

        // Delete on-off options
        $wpdb->delete($wpdb->prefix . 'options', ['option_name' => 'chatbot_ai_platform_choice']);
    
        // Delete ChatGPT options
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s", 'chatbot_chatgpt%'));

        // Delete Azure OpenAI options
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s", 'chatbot_azure%'));

        // Delete NVIDIA options
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s", 'chatbot_nvidia%'));

        // Delete Anthropic options
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s", 'chatbot_anthropic%'));

        // Delete DeepSeek options
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s", 'chatbot_deepseek%'));

        // Delete Google options
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s", 'chatbot_google%'));

        // Delete Mistral options
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s", 'chatbot_mistral%'));

        // Delete Markov Chain options
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s", 'chatbot_markov%'));

        // Delete Local options
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s", 'chatbot_local%'));

        // Delete Transformer options
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s", 'chatbot_transformer_model%'));

        // Delete Insights options
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s", 'kognetiks_insights%'));

        // Delete ChatGPT tables
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}chatbot_chatgpt_assistants");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}chatbot_chatgpt_azure_assistants");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}chatbot_chatgpt_conversation_log");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}chatbot_chatgpt_interactions");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}chatbot_chatgpt_knowledge_base");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}chatbot_chatgpt_knowledge_base_tfidf");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}chatbot_chatgpt_knowledge_base_word_count");

        // Delete NVIDIA tables
        // NONE CURRENTLY - Ver 2.1.8

        // Delete Markov Chain tables
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}chatbot_markov_chain");

        // Delete Anthropic tables
        // NONE CURRENTLY - Ver 2.2.0

        // Delete Google tables
        // NONE CURRENTLY - Ver 2.3.9

        // Delete Sentential Transformer tables
        // NONE CURENTLY - Ver 2.2.1

        // Delete Lexical Transformer tables
        // NONE CURRENTLY - Ver 2.2.1

        // Delete ChatGPT transients
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_chatbot_chatgpt%', '_transient_timeout_chatbot_chatgpt%'));

        // Delete NVIDIA transients
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_chatbot_nvidia%', '_transient_timeout_chatbot_nvidia%'));

        // Delete Anthropic transients
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_chatbot_anthropic%', '_transient_timeout_chatbot_anthropic%'));

        // Delete Google transients
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_chatbot_google%', '_transient_timeout_chatbot_google%'));

        // Delete Markov Chain transients
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_chatbot_markov%', '_transient_timeout_chatbot_markov%'));
        
        // Delete Transformer transients
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_chatbot_transformer_model%', '_transient_timeout_chatbot_transformer_model%'));

        // Delete any scheduled cron events
        $crons = _get_cron_array();
        foreach ($crons as $timestamp => $cron) {
            foreach ($cron as $hook => $events) {
                if (strpos($hook, 'chatbot_chatgpt') !== false) {
                    foreach ($events as $event) {
                        wp_unschedule_event($timestamp, $hook, $event['args']);
                    }
                }
                if (strpos($hook, 'chatbot_transformer') !== false) {
                    foreach ($events as $event) {
                        wp_unschedule_event($timestamp, $hook, $event['args']);
                    }
                }
            }
        }

        // Delete the cron event called "knowledge_navigator_scan_hook"
        wp_clear_scheduled_hook('knowledge_navigator_scan_hook');

        // Delete any insights options
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s", 'kognetiks_insights%'));

        // Delete any scheduled insights cron events
        $crons = _get_cron_array();
        foreach ($crons as $timestamp => $cron) {
            foreach ($cron as $hook => $events) {
                if (strpos($hook, 'kognetiks_insights') !== false) {
                    foreach ($events as $event) {
                        wp_unschedule_event($timestamp, $hook, $event['args']);
                    }
                }
            }
        }
        
    }

    // DIAG - Log the uninstall

    return;
}
