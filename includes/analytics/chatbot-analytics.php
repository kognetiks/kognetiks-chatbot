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

// Get the WordPress language
$chatbot_chatgpt_installed_language_code = get_locale();

// Set a default language code if site language is not set
if ( empty( $chatbot_chatgpt_installed_language_code ) ) {
    $chatbot_chatgpt_installed_language_code = "en_US";
}

// Check if the language is English
// if ($chatbot_chatgpt_installed_language_code !== 'en_US') {
//     // Load the EN sentiment words dictionary
//     require_once plugin_dir_path((__FILE__)) . '/languages/en_US.php';
// } else {
//     // Load the default sentiment words dictionary
//     require_once plugin_dir_path((__FILE__)) . '/languages/en_US.php';
// }

chatbot_chatgpt_load_globals($chatbot_chatgpt_installed_language_code);

// Load the sentiment words dictionary
function chatbot_chatgpt_load_globals($language_code) {

    // Log the selected language code
    error_log('Loading globals for language: ' . $language_code);

    // Define the file path for translations
    $file_path = plugin_dir_path(__FILE__) . '/languages/' . substr(strtolower($language_code), 0, 2) . '.php';

    // Check if the file exists and include it, else log a warning
    if ( file_exists($file_path) ) {
        require_once $file_path;
        error_log('Loaded translation file: ' . $file_path);
        $chatbot_chatgpt_globals_loaded = true;
    } else {
        // Fall back to English if the file does not exist
        $chatbot_chatgpt_language_code = "en_US";
        $fallback_file = plugin_dir_path(__FILE__) . '/languages/en_US.php';
        require_once $fallback_file;
        error_log('Translation file not found for ' . $language_code . '. Falling back to English: ' . $fallback_file);
        $chatbot_chatgpt_globals_loaded = true;
    }

    // Log a warning if the file could not be loaded
    if ( ! $chatbot_chatgpt_globals_loaded ) {
        error_log ('Could not load translation file: ' . $file_path);
        $chatbot_chatgtp_globals_loaded = false;
    }

}


