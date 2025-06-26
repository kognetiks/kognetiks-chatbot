<?php
/**
 * Kognetiks Analytics - Ver 1.0.0
 *
 * This file contains the code for the Kognetiks Analytics package.
 * 
 * 
 * 
 * @package kognetiks-analytics
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Load the language-specific globals
function kognetiks_analytics_load_globals( $language_code ) {

    // Log the selected language code
    // error_log( '[Chatbot] [chatbot-analytics.php] Loading globals for language: ' . $language_code );

    $file_path = plugin_dir_path( __FILE__ ) . '/languages/' . $language_code . '.php';

    if ( file_exists( $file_path ) ) {
        require_once $file_path;
        // error_log( '[Chatbot] [chatbot-analytics.php] Loaded translation file: ' . $file_path );
    } else {
        $fallback_file = plugin_dir_path( __FILE__ ) . '/languages/en_US.php';
        require_once $fallback_file;
        error_log( '[Chatbot] [chatbot-analytics.php] Translation file not found for ' . $language_code . '. Falling back to: ' . $fallback_file );
    }

}

// Call the function after it's defined
$chatbot_chatgpt_installed_language_code = get_locale();

if ( empty( $chatbot_chatgpt_installed_language_code ) ) {

    // Default language code
    $chatbot_chatgpt_installed_language_code = 'en_US';

}
// Load the language-specific globals
kognetiks_analytics_load_globals( $chatbot_chatgpt_installed_language_code );

// Add sentiment_score column if missing - Ver 2.3.1
function chatbot_chatgpt_add_sentiment_score_column() {

    global $wpdb;
    
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';
    
    // Check if the table exists
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) !== $table_name) {
        // Table doesn't exist, nothing to do
        return false;
    }
    
    // Check if sentiment_score column already exists
    if ($wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM $table_name LIKE %s", 'sentiment_score')) === 'sentiment_score') {
        // Column already exists
        return true;
    }
    
    // Add the sentiment_score column
    $sql = "ALTER TABLE $table_name ADD COLUMN sentiment_score FLOAT AFTER message_text";
    $result = $wpdb->query($sql);
    
    if ($result === false) {
        prod_trace ('ERROR', 'Error adding sentiment_score column: ' . $wpdb->last_error);
        return false;
    }
    
    return true;
}

// Automatically add the sentiment_score column when analytics is loaded
chatbot_chatgpt_add_sentiment_score_column();
