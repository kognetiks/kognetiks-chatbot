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

// Language code for the chatbot
// Default: en_US

// Declare the $chatbot_chatgpt_language_code variable as global
global $chatbot_chatgpt_language_code;

// Set the language code for the chatbot
$chatbot_chatgpt_language_code = "en_US";

// Determine the site language using the get_locale()
$chatbot_chatgpt_installed_language_code = get_locale();

// Check if the site language is set
if ( empty( $chatbot_chatgpt_installed_language_code ) ) {
    // Set the chatbot language code to the site language
    $chatbot_chatgpt_language_code = "en_US";
    chatbot_chatgpt_load_globals("en_US");
} else {
    // Set the chatbot language code to the site language
    $chatbot_chatgpt_language_code = $chatbot_chatgpt_installed_language_code;
    chatbot_chatgpt_load_globals($chatbot_chatgpt_installed_language_code);
}

// Load the global arrays for the English language
function chatbot_chatgpt_load_globals($chatbot_chatgpt_installed_language_code) {

    global $chatbot_chatgpt_language_code;
    
    // Language code for the chatbot
    // error_log( '$chatbot_chatgpt_installed_language_code: ' . $chatbot_chatgpt_installed_language_code );
    // error_log( '$chatbot_chatgpt_language_code: ' . $chatbot_chatgpt_language_code );

    $chatbot_chatgpt_language_code = $chatbot_chatgpt_installed_language_code;

    switch ($chatbot_chatgpt_installed_language_code) {

        case "en_US":
            $chatbot_chatgpt_language_code = "en_US";
            // Load the global arrays for the English language
            require_once plugin_dir_path(__FILE__) . 'translations/chatbot-globals-en.php';                    // Globals - English - Ver 1.6.5
            break;

        case "cs_CZ":
            $chatbot_chatgpt_language_code = "cs_CZ";
            require_once plugin_dir_path(__FILE__) . 'translations/chatbot-globals-cs.php';                    // Globals - Czech - Ver 2.2.2
            break;

        case "de_DE":
            require_once plugin_dir_path(__FILE__) . 'translations/chatbot-globals-de.php';                    // Globals - German - Ver 2.2.22
            break;
        
        case "es_ES":
            $chatbot_chatgpt_language_code = "es_ES";
            require_once plugin_dir_path(__FILE__) . 'translations/chatbot-globals-es.php';                    // Globals - Spanish - Ver 2.2.2
            break;

        case "fr_FR":
            $chatbot_chatgpt_language_code = "fr_FR";
            require_once plugin_dir_path(__FILE__) . 'translations/chatbot-globals-fr.php';                    // Globals - French - Ver 2.2.2
            break;

        case "it_IT":
            $chatbot_chatgpt_language_code = "it_IT";
            require_once plugin_dir_path(__FILE__) . 'translations/chatbot-globals-it.php';                    // Globals - Italian - Ver 2.2.2
            break;

        case "pl_PL":
            $chatbot_chatgpt_language_code = "pl_PL";
            require_once plugin_dir_path(__FILE__) . 'translations/chatbot-globals-pl.php';                    // Globals - Polish - Ver 2.2.2
            break;

        case "pt_PT":
            $chatbot_chatgpt_language_code = "pt_PT";
            require_once plugin_dir_path(__FILE__) . 'translations/chatbot-globals-pt.php';                    // Globals - Portuguese - Ver 2.2.2
            break;

        case "ru_RU":
            $chatbot_chatgpt_language_code = "ru_RU";
            require_once plugin_dir_path(__FILE__) . 'translations/chatbot-globals-ru.php';                    // Globals - Russian - Ver 2.2.2
            break;

        default:
            $chatbot_chatgpt_language_code = "en_US";
            require_once plugin_dir_path(__FILE__) . 'translations/chatbot-globals-en.php';                    // Globals - English - Ver 1.6.5
            break;

    }

}