<?php
/**
 * Chatbot ChatGPT for WordPress - Settings - Adaptive Skins
 *
 * This file contains the code for the Chatbot ChatGPT settings page.
 * It handles the adaptive skins settings and other parameters.
 * 
 *
 * @package chatbot-chatgpt
 */

// IDEA - COMING SOON - Ver 1.6.8

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Function to add the adaptive appearance settings to the Chatbot ChatGPT settings page
function chatbot_chatgpt_settings_appearance() {

    // DIAG - Diagnostics
    // chatbot_chatgpt_back_trace( 'NOTICE', );

    // Add the adaptive skins settings section
    add_settings_section(
        'chatbot_chatgpt_settings_skins_section',
        __( 'Adaptive Skins', 'chatbot-chatgpt' ),
        'chatbot_chatgpt_settings_skins_section_callback',
        'chatbot_chatgpt_settings_page'
    );

    // Add the adaptive skins settings fields
    add_settings_field(
        'chatbot_chatgpt_settings_skins_field',
        __( 'Adaptive Skins', 'chatbot-chatgpt' ),
        'chatbot_chatgpt_settings_skins_field_callback',
        'chatbot_chatgpt_settings_page',
        'chatbot_chatgpt_settings_skins_section'
    );

    // Register the adaptive skins settings
    register_setting(
        'chatbot_chatgpt_settings_page',
        'chatbot_chatgpt_settings_skins_field'
    );

}



// function chatbot_chatgpt_skins_enqueue_styles() {

//     // DIAG - Diagnostics
//     // chatbot_chatgpt_back_trace( 'NOTICE', );
    
//     $primary_color = get_theme_mod('primary_color', '#000000'); // Default to black if not set

//     $custom_css = "
//         .parent-class chatbot-chatgpt {
//             background-color: {$primary_color} !important;
//         }
//         .parent-class chatbot-chatgpt .chatbot-chatgpt-header {
//             background-color: {$primary_color} !important;
//         }";
//     wp_add_inline_style('chatbot-chatgpt', $custom_css);

//     // DIAG - Diagnostics
//     // chatbot_chatgpt_back_trace( 'NOTICE', '$custom_css: ' . $custom_css);

// }
// add_action('wp_enqueue_scripts', 'chatbot_chatgpt_skins_enqueue_styles');
