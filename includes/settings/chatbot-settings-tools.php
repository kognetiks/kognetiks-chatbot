<?php
/**
 * Kognetiks Chatbot for WordPress - Settings - Tools - Ver 2.0.6
 *
 * This file contains the code for the Chatbot settings page.
 * It handles the support settings and other parameters.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Add the Tools section
function chatbot_chatgpt_tools_section_callback() {

?>
    <div>
        <p>This tab provides tools, tests and diagnostics that are enabled when the Chatbot Diagnostics are enabled on the Messages tab.</p>
    </div>
    <?php
    
}

// Add the Shortcode Tester
function chatbot_chatgpt_tools_setting_callback($args) {

    chatbot_shortcode_tester();

}

// User Capability Check - Ver 2.0.5
// function chatbot_chatgpt_check_user_capability_callback() {

//     echo '<h2>User Capability Check</h2>';

//     $capabilities = array(
//         'read',
//         'edit_posts',
//         'publish_posts',
//         'manage_options'
//     );

//     foreach ($capabilities as $capability) {
//         if (current_user_can($capability)) {
//             // back_trace('NOTICE', 'User has the capability: ' . $capability);
//             echo '<p>User has the capability: ' . $capability . '</p>';
//         } else {
//             // back_trace('ERROR', 'User does not have the capability: ' . $capability);
//             echo '<p>User does not have the capability: ' . $capability . '</p>';
//         }
//     }

// }

