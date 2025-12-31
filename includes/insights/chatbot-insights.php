<?php
/**
 * Kognetiks Insights - Ver 1.0.0
 *
 * This file contains the code for the Kognetiks Insights package.
 * 
 * 
 * 
 * @package kognetiks-insights
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Load the language-specific globals
// Ver 2.3.7 - Fixed translation file lookup to handle locale formats properly
function kognetiks_insights_load_globals( $language_code ) {

    // Log the selected language code
    // error_log( '[Chatbot] [chatbot-insights.php] Loading globals for language: ' . $language_code );

    // Try the full locale first (e.g., 'uk_UA', 'en_US')
    $file_path = plugin_dir_path( __FILE__ ) . '/languages/' . $language_code . '.php';

    if ( file_exists( $file_path ) ) {
        require_once $file_path;
        // error_log( '[Chatbot] [chatbot-insights.php] Loaded translation file: ' . $file_path );
        return;
    }

    // If full locale not found, try language code only (first 2 chars, e.g., 'uk' from 'uk_UA')
    $lang_code_short = substr( strtolower( $language_code ), 0, 2 );
    $file_path_short = plugin_dir_path( __FILE__ ) . '/languages/' . $lang_code_short . '.php';
    
    // Check if there's a matching file with full locale format (e.g., 'uk_UA.php' when looking for 'uk')
    // First, try to find any file that starts with the language code
    $languages_dir = plugin_dir_path( __FILE__ ) . '/languages/';
    if ( is_dir( $languages_dir ) ) {
        $files = glob( $languages_dir . $lang_code_short . '_*.php' );
        if ( !empty( $files ) ) {
            // Use the first matching file (e.g., 'uk_UA.php')
            require_once $files[0];
            // error_log( '[Chatbot] [chatbot-insights.php] Loaded translation file: ' . $files[0] );
            return;
        }
    }

    // Fall back to English if no translation file found
    $fallback_file = plugin_dir_path( __FILE__ ) . '/languages/en_US.php';
    if ( file_exists( $fallback_file ) ) {
        require_once $fallback_file;
        // Only log as notice, not error, since fallback is expected behavior for unsupported languages
        // error_log( '[Chatbot] [chatbot-insights.php] Translation file not found for ' . $language_code . '. Falling back to: ' . $fallback_file );
    }

}

// Call the function after it's defined
$chatbot_chatgpt_installed_language_code = get_locale();

if ( empty( $chatbot_chatgpt_installed_language_code ) ) {

    // Default language code
    $chatbot_chatgpt_installed_language_code = 'en_US';

}
// Load the language-specific globals
kognetiks_insights_load_globals( $chatbot_chatgpt_installed_language_code );

// Automatically add the sentiment_score column when insights is loaded
if (function_exists('chatbot_chatgpt_add_sentiment_score_column')) {
    chatbot_chatgpt_add_sentiment_score_column();
}
