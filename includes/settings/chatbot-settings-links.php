<?php
/**
 * Kognetiks Chatbot - Simplified Settings Links
 *
 * This file is a simplified version of the Chatbot settings page.
 * It includes links for Settings, Deactivation, and Support.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Add link to ChatGPT options in the plugins page.
function chatbot_chatgpt_plugin_action_links( $links ) {

    if ( current_user_can( 'manage_options' ) ) {
        $settings_link = '<a href="' . esc_url( wp_nonce_url( admin_url( 'admin.php?page=chatbot-chatgpt' ), 'chatbot_chatgpt_settings' ) ) . '">' . __( 'Settings', 'chatbot-chatgpt' ) . '</a>';
        $support_link = '<a href="' . esc_url( wp_nonce_url( admin_url( 'admin.php?page=chatbot-chatgpt&tab=support' ), 'chatbot_chatgpt_support' ) ) . '">' . __( 'Support', 'chatbot-chatgpt' ) . '</a>';
        array_unshift( $links, $settings_link, $support_link );
    }
    return $links;

}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'chatbot_chatgpt_plugin_action_links' );

// Add deactivation link in the plugin row meta
function chatbot_chatgpt_plugin_row_meta( $links, $file ) {

    if ( plugin_basename( __FILE__ ) == $file && current_user_can( 'activate_plugins' ) ) {
        $deactivate_link = '<a href="' . esc_url( wp_nonce_url( 'plugins.php?action=deactivate&amp;plugin=' . urlencode( plugin_basename( __FILE__ ) ), 'deactivate-plugin_' . plugin_basename( __FILE__ ) ) ) . '">' . __( 'Deactivate', 'chatbot-chatgpt' ) . '</a>';
        $links[] = $deactivate_link;
    }
    return $links;
    
}
add_filter( 'plugin_row_meta', 'chatbot_chatgpt_plugin_row_meta', 10, 2 );
