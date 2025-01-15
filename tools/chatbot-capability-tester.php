<?php
/**
 * Kognetiks Chatbot - Capability Tester - Ver 2.0.6
 *
 * This file contains the code for testing user capabilities
 * and displaying the results.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// User Capability Check
function chatbot_chatgpt_capability_tester() {

    echo '<div>';
    echo '<h2>Capability Check Results</h2>';

    $capabilities = array(
        'read',
        'edit_posts',
        'publish_posts',
        'manage_options'
    );

    foreach ($capabilities as $capability) {
        if (current_user_can($capability)) {
            // back_trace( 'NOTICE', 'User has the capability: ' . $capability);
            echo '<p>User has the capability: ' . $capability . '</p>';
        } else {
            // back_trace( 'ERROR', 'User does not have the capability: ' . $capability);
            echo '<p>User does not have the capability: ' . $capability . '</p>';
        }
    }

    echo '</div>';

}
