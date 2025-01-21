<?php
/**
 * Kognetiks Chatbot - Globals - Ver 1.6.5
 *
 * This file contains the code for global variables used
 * by the program.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Declare global language code variable
global $chatbot_chatgpt_language_code;

// Get the site language using get_locale()
$chatbot_chatgpt_installed_language_code = get_locale();

// Log the site language for debugging
// error_log('Site locale: ' . $chatbot_chatgpt_installed_language_code);

// Set a default language code if site language is not set
if ( empty( $chatbot_chatgpt_installed_language_code ) ) {
    $chatbot_chatgpt_installed_language_code = "en_US";
}

// Load the global arrays for the determined language
chatbot_chatgpt_load_globals($chatbot_chatgpt_installed_language_code);

// Load chatbot language globals based on the site language.
function chatbot_chatgpt_load_globals($language_code) {

    global $chatbot_chatgpt_language_code;

    // Set the global language code
    $chatbot_chatgpt_language_code = $language_code;

    // Log the selected language code
    // error_log('Loading globals for language: ' . $language_code);

    // Define the file path for translations
    $file_path = plugin_dir_path(__FILE__) . 'translations/chatbot-globals-' . substr(strtolower($language_code), 0, 2) . '.php';

    // Check if the file exists and include it, else log a warning
    if ( file_exists($file_path) ) {
        require_once $file_path;
        // error_log('Loaded translation file: ' . $file_path);
    } else {
        // Fall back to English if the file does not exist
        $chatbot_chatgpt_language_code = "en_US";
        $fallback_file = plugin_dir_path(__FILE__) . 'translations/chatbot-globals-en.php';
        require_once $fallback_file;
        // error_log('Translation file not found for ' . $language_code . '. Falling back to English: ' . $fallback_file);
    }

}
