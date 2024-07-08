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


function chatbot_chatgpt_tools_section_callback() {

?>
    <div>
        <p>This tab provides tools, tests and diagnostics that are enabled when the Chatbot Diagnostics are enabled on the Messages tab.</p>
    </div>
    <?php
    
}

function chatbot_chatgpt_tools_setting_callback($args) {

    chatbot_shortcode_tester();

}

