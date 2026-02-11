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
function chatbot_chatgpt_uninstall() {

    if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
        return;
    }

    global $wpdb;

    $debug = defined('WP_DEBUG') && WP_DEBUG;

    if ( $debug ) {
        error_log('[Chatbot] [chatbot-deactivate.php] uninstall: function called');
    }

    $delete_data = get_option('chatbot_chatgpt_delete_data');
    if ( $delete_data !== 'yes' ) {
        if ( $debug ) {
            error_log('[Chatbot] [chatbot-deactivate.php] uninstall: delete_data != yes, skipping cleanup');
        }
        return;
    }

    $errors_occurred = false;

    $execute_query = function( $query, $description ) use ( &$wpdb, &$errors_occurred, $debug ) {
        $result = $wpdb->query( $query );
        if ( $result === false && ! empty( $wpdb->last_error ) ) {
            if ( $debug ) {
                error_log("[Chatbot] [chatbot-deactivate.php] uninstall error ($description): " . $wpdb->last_error);
            }
            $errors_occurred = true;
        }
        return $result;
    };

    // Options: exact + prefixes
    $exact_options = array(
        'chatbot_ai_platform_choice',
    );

    foreach ( $exact_options as $opt ) {
        $execute_query(
            $wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name = %s", $opt),
            "delete option {$opt}"
        );
    }

    $prefixes = array(
        'chatbot_chatgpt',
        'chatbot_openai',
        'chatbot_azure',
        'chatbot_nvidia',
        'chatbot_anthropic',
        'chatbot_deepseek',
        'chatbot_google',
        'chatbot_mistral',
        'chatbot_markov',
        'chatbot_local',
        'chatbot_transformer',
        'kognetiks_insights',
    );

    foreach ( $prefixes as $prefix ) {
        $like = $wpdb->esc_like( $prefix ) . '%';
        $execute_query(
            $wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s", $like),
            "delete options like {$prefix}%"
        );
    }

    // Transients (prefix-based)
    $transient_prefixes = array(
        'chatbot_chatgpt',
        'chatbot_nvidia',
        'chatbot_anthropic',
        'chatbot_google',
        'chatbot_markov',
        'chatbot_transformer_model',
        'chatbot_mistral',
        'chatbot_local',
        'chatbot_azure',
        'kchat',
    );

    foreach ( $transient_prefixes as $tp ) {
        $like1 = $wpdb->esc_like('_transient_' . $tp) . '%';
        $like2 = $wpdb->esc_like('_transient_timeout_' . $tp) . '%';

        $execute_query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s OR option_name LIKE %s",
                $like1,
                $like2
            ),
            "delete transients for {$tp}"
        );
    }

    // Tables
    $tables_to_drop = array(
        'chatbot_chatgpt_assistants',
        'chatbot_chatgpt_azure_assistants',
        'chatbot_chatgpt_conversation_log',
        'chatbot_chatgpt_interactions',
        'chatbot_chatgpt_knowledge_base',
        'chatbot_chatgpt_knowledge_base_tfidf',
        'chatbot_chatgpt_knowledge_base_word_count',
        'chatbot_markov_chain',
    );

    foreach ( $tables_to_drop as $table ) {
        $table_name = $wpdb->prefix . $table;
        $execute_query(
            "DROP TABLE IF EXISTS `{$table_name}`",
            "drop table {$table_name}"
        );
    }

    // Cron: clear only known hooks you own
    wp_clear_scheduled_hook('knowledge_navigator_scan_hook');
    wp_clear_scheduled_hook('kognetiks_insights_send_proof_of_value_email_hook');
    wp_clear_scheduled_hook('kognetiks_insights_send_conversation_digest_email_hook');
    wp_clear_scheduled_hook('chatbot_chatgpt_conversation_log_cleanup_event');

    if ( $debug ) {
        error_log('[Chatbot] [chatbot-deactivate.php] uninstall: completed ' . ($errors_occurred ? 'with errors' : 'successfully'));
    }

}
