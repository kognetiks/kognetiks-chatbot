<?php
/**
 * Kognetiks Chatbot - Menus
 *
 * This file contains the code for the administrative menus for the plugin.
 * 
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Use a number lower than default (10), e.g., 5.
add_action('admin_menu', 'kognetiks_chatbot_register_menus', 5);

// Add a menu item in the admin panel
function kognetiks_chatbot_register_menus() {

    global $menu;

    // Check if the 'Kognetiks' menu already exists
    $kognetiks_menu_exists = false;

    foreach ( $menu as $menu_item ) {

        if ( isset( $menu_item[2] ) && $menu_item[2] === 'kognetiks_main_menu' ) {
            $kognetiks_menu_exists = true;
            break;
        }

    }

    // If no Kognetiks menu exists, add a standalone menu for this plugin
    if ( ! $kognetiks_menu_exists ) {

        add_menu_page(
            'Kognetiks',                            // Page title
            'Kognetiks',                            // Menu title
            'manage_options',                       // Capability
            'kognetiks_main_menu',                  // Menu slug
            'chatbot_chatgpt_settings_page',        // Callback function
            'dashicons-rest-api',                   // Icon
            999                                     // Position
        );

        add_submenu_page(
            'kognetiks_main_menu',                  // Parent slug
            'Chatbot',                              // Page title
            'Chatbot',                              // Menu title
            'manage_options',                       // Capability     
            'chatbot-chatgpt',                      // Menu slug
            'chatbot_chatgpt_settings_page'         // Callback function
        );

    } else {

        // If Kognetiks menu exists, add this as a submenu
        add_submenu_page(
            'kognetiks_main_menu',                  // Parent slug
            'Chatbot',                              // Page title
            'Chatbot',                              // Menu title
            'manage_options',                       // Capability     
            'chatbot-chatgpt',                      // Menu slug
            'chatbot_chatgpt_settings_page'         // Callback function
        );

    }

};

// Remove the extra submenu page
add_action('admin_menu', 'chatbot_chatgpt_remove_extra_submenu', 999);
function chatbot_chatgpt_remove_extra_submenu() {

    remove_submenu_page('kognetiks_main_menu', 'kognetiks_main_menu');

}
