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

    $delete_data = get_option('chatbot_chatgpt_delete_data');
    if ( empty( $delete_data ) ) {
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

    $delete_data = get_option('chatbot_chatgpt_delete_data');
    // Only show notice when option is empty (user has never set a preference).
    // Do NOT set to 'no' here - that would cause uninstall to skip cleanup for users
    // who never explicitly chose to keep data.
    if ( empty( $delete_data ) ) {

        echo '<div class="notice notice-warning is-dismissible">
            <p><strong>Kognetiks Chatbot:</strong> Remember to set your data deletion preferences in the plugin settings on the Messages tab if you plan to uninstall the plugin.</p>
        </div>';

    }

}

// Uninstall Logic - Revised 2.4.4
function chatbot_chatgpt_uninstall(){

    // Security check: Only allow uninstall via Freemius after_uninstall.
    // We have no uninstall.php so Freemius can track the uninstall event and collect user feedback.
    // WP_UNINSTALL_PLUGIN is defined by WordPress during the uninstall flow.
    if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
        return;
    }

    global $wpdb;

    // Log the uninstall attempt
    error_log('[Chatbot] [chatbot-deactivate.php] Uninstall function called');

    // Only remove data when the user has explicitly set chatbot_chatgpt_delete_data = yes.
    // If no or empty, keep all options and tables.
    $delete_data = get_option('chatbot_chatgpt_delete_data');
    if ( empty( $delete_data ) || $delete_data !== 'yes' ) {
        error_log('[Chatbot] [chatbot-deactivate.php] Data deletion not requested (chatbot_chatgpt_delete_data != yes), skipping cleanup');
        return;
    }

    error_log('[Chatbot] [chatbot-deactivate.php] Starting data deletion process');

    // Suppress errors during uninstall but log them
    $wpdb->suppress_errors(false); // Show errors for debugging
    $errors_occurred = false;

    // Helper function to execute queries with error checking
    $execute_query = function($query, $description) use (&$wpdb, &$errors_occurred) {
        $result = $wpdb->query($query);
        if ($result === false && !empty($wpdb->last_error)) {
            error_log("[Chatbot] [chatbot-deactivate.php] Error during $description: " . $wpdb->last_error);
            $errors_occurred = true;
        }
        return $result;
    };

    // Delete on-off options
    $execute_query(
        $wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name = %s", 'chatbot_ai_platform_choice'),
        'deleting chatbot_ai_platform_choice option'
    );
    
    // Delete ChatGPT options
    $execute_query(
        $wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s", 'chatbot_chatgpt%'),
        'deleting ChatGPT options'
    );

    // Delete Azure OpenAI options
    $execute_query(
        $wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s", 'chatbot_azure%'),
        'deleting Azure OpenAI options'
    );

    // Delete NVIDIA options
    $execute_query(
        $wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s", 'chatbot_nvidia%'),
        'deleting NVIDIA options'
    );

    // Delete Anthropic options
    $execute_query(
        $wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s", 'chatbot_anthropic%'),
        'deleting Anthropic options'
    );

    // Delete DeepSeek options
    $execute_query(
        $wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s", 'chatbot_deepseek%'),
        'deleting DeepSeek options'
    );

    // Delete Google options
    $execute_query(
        $wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s", 'chatbot_google%'),
        'deleting Google options'
    );

    // Delete Mistral options
    $execute_query(
        $wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s", 'chatbot_mistral%'),
        'deleting Mistral options'
    );

    // Delete Markov Chain options
    $execute_query(
        $wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s", 'chatbot_markov%'),
        'deleting Markov Chain options'
    );

    // Delete Local options
    $execute_query(
        $wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s", 'chatbot_local%'),
        'deleting Local options'
    );

    // Delete Transformer options
    $execute_query(
        $wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s", 'chatbot_transformer_model%'),
        'deleting Transformer options'
    );

    // Delete Insights options
    $execute_query(
        $wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s", 'kognetiks_insights%'),
        'deleting Insights options'
    );

    // Delete ChatGPT tables
    $tables_to_drop = array(
        'chatbot_chatgpt_assistants',
        'chatbot_chatgpt_azure_assistants',
        'chatbot_chatgpt_conversation_log',
        'chatbot_chatgpt_interactions',
        'chatbot_chatgpt_knowledge_base',
        'chatbot_chatgpt_knowledge_base_tfidf',
        'chatbot_chatgpt_knowledge_base_word_count',
        'chatbot_markov_chain'
    );

    foreach ($tables_to_drop as $table) {
        $table_name = $wpdb->prefix . $table;
        $execute_query(
            "DROP TABLE IF EXISTS `{$table_name}`",
            "dropping table {$table_name}"
        );
    }

    // Delete ChatGPT transients
    $execute_query(
        $wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_chatbot_chatgpt%', '_transient_timeout_chatbot_chatgpt%'),
        'deleting ChatGPT transients'
    );

    // Delete NVIDIA transients
    $execute_query(
        $wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_chatbot_nvidia%', '_transient_timeout_chatbot_nvidia%'),
        'deleting NVIDIA transients'
    );

    // Delete Anthropic transients
    $execute_query(
        $wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_chatbot_anthropic%', '_transient_timeout_chatbot_anthropic%'),
        'deleting Anthropic transients'
    );

    // Delete Google transients
    $execute_query(
        $wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_chatbot_google%', '_transient_timeout_chatbot_google%'),
        'deleting Google transients'
    );

    // Delete Markov Chain transients
    $execute_query(
        $wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_chatbot_markov%', '_transient_timeout_chatbot_markov%'),
        'deleting Markov Chain transients'
    );
    
    // Delete Transformer transients
    $execute_query(
        $wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_chatbot_transformer_model%', '_transient_timeout_chatbot_transformer_model%'),
        'deleting Transformer transients'
    );

    // Delete Mistral transients
    $execute_query(
        $wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_chatbot_mistral%', '_transient_timeout_chatbot_mistral%'),
        'deleting Mistral transients'
    );

    // Delete Local transients
    $execute_query(
        $wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_chatbot_local%', '_transient_timeout_chatbot_local%'),
        'deleting Local transients'
    );

    // Delete Azure OpenAI transients
    $execute_query(
        $wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_chatbot_azure%', '_transient_timeout_chatbot_azure%'),
        'deleting Azure OpenAI transients'
    );

    // Delete any kchat transients
    $execute_query(
        $wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_kchat%', '_transient_timeout_kchat%'),
        'deleting kchat transients'
    );

    // Delete any scheduled cron events
    $crons = _get_cron_array();
    if (!empty($crons)) {
        foreach ($crons as $timestamp => $cron) {
            foreach ($cron as $hook => $events) {
                if (strpos($hook, 'chatbot_chatgpt') !== false || strpos($hook, 'chatbot_transformer') !== false || strpos($hook, 'kognetiks_insights') !== false) {
                    foreach ($events as $event) {
                        wp_unschedule_event($timestamp, $hook, $event['args']);
                    }
                }
            }
        }
    }

    // Delete the cron event called "knowledge_navigator_scan_hook"
    wp_clear_scheduled_hook('knowledge_navigator_scan_hook');

    // Clear any remaining insights cron events
    wp_clear_scheduled_hook('kognetiks_insights_send_proof_of_value_email_hook');
    wp_clear_scheduled_hook('kognetiks_insights_send_conversation_digest_email_hook');
    wp_clear_scheduled_hook('chatbot_chatgpt_conversation_log_cleanup_event');

    if ($errors_occurred) {
        error_log('[Chatbot] [chatbot-deactivate.php] Uninstall completed with errors - some data may not have been deleted');
    } else {
        error_log('[Chatbot] [chatbot-deactivate.php] Uninstall completed successfully');
    }

    return;
}
