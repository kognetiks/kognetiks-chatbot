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
if ( ! defined( 'WPINC' ) )
die;

// function chatbot_chatgpt_skins_enqueue_styles() {

//     // DIAG - Diagnostics
//     // error_log( 'Chatbot ChatGPT: chatbot_chatgpt_skins_enqueue_styles()');
    
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
//     // error_log( 'Chatbot ChatGPT: chatbot_chatgpt_skins_enqueue_styles(): ' . $custom_css);

// }
// add_action('wp_enqueue_scripts', 'chatbot_chatgpt_skins_enqueue_styles');
