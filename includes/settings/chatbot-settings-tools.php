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
        <p style="background-color: #e0f7fa; padding: 10px;"><b>For an explanation of the Tool settings and additional documentation please click <a href="?page=chatbot-chatgpt&tab=support&dir=tools&file=tools.md">here</a>.</b></p>
    </div>
    <?php
    
}

function chatbot_chatgpt_tools_setting_callback() {

    ?>
    <div>
        <p>Options Exporter</p>
        <p>Manage Error Logs</p>
        <p>Shortcode Tester</p>
        <p>Capability Check</p>
    </div>
    <?php

}

// Add the Shortcode Tester
function chatbot_chatgpt_shortcode_tools_section_callback($args) {

    ?>
    <div>
        <p>This tool allows you to test the Chatbot Shortcode. Enter the shortcode in the text box and click the Test button to see the Chatbot in action.</p>
    </div>
    <?php

    // Call the shortcode tester
    chatbot_shortcode_tester();

}

// Capability Check Overview
function chatbot_chatgpt_capability_tools_section_callback() {

    ?>
    <div>
        <p>This tool allows you to check the permissions for various features.</p>
    </div>
    <?php

    // Call the capability tester
    chatbot_chatgpt_capability_tester();

}

function chatbot_chatgpt_options_exporter_tools_section_callback() {

    ?>
    <div>
        <p>Export the Chatbot options to a file.</p>
    </div>
    <?php

    // Call the capability tester
    chatbot_chatgpt_options_exporter();

}

function chatbot_chatgpt_export_tools_callback() {
    // Output the settings field for export options
}


function chatbot_chatgpt_manage_error_logs_section_callback() {

    ?>
    <div>
        <p>Click the <code>Download</code> button to retrieve a log file, or the <code>Delete</code> button to remove a log file.</p>
        <p>Click the <code>Delete All</code> button to remove all log files.</p>
    </div>
    <?php

    // Call the capability tester
    chatbot_chatgpt_manage_error_logs();

}



