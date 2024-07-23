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
        <p><b><i>Don't forget to click </i><code>Save Settings</code><i> to save your changes.</i></b></p>
        <p style="background-color: #e0f7fa; padding: 10px;"><b>For an explanation of the Tool settings and additional documentation please click <a href="?page=chatbot-chatgpt&tab=support&dir=tools&file=tools.md">here</a>.</b></p>
    </div>
    <?php
    
}

function chatbot_chatgpt_tools_setting_callback() {

    // PLACEHOLDER

}

// Add the Shortcode Tester
function chatbot_chatgpt_shortcode_tools_section_callback($args) {

    ?>
    <div>
        <p>This tool automatically tests the Chatbot Shortcode. There are three tests in all. Test 1 checks calling shortcodes without any parameters.  Test 2 checks calling a shortcode with a single paramemter. And, Test 3 checks calling a shortcode with three parameters. The results are displayed below the tests.</p>
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
        <p><b>NOTE:</b> If you change the format from CSV to JSON, or vice versa, you will need to scoll to the bottom of the page and <code>Save Changes</code> to update the format.</p>
    </div>
    <?php

    // Call the capability tester
    // chatbot_chatgpt_options_exporter();

}

function chatbot_chatgpt_export_tools_callback() {

    // PLACEHOLDER - VER 2.0.7

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

// Register Tools settings - Ver 2.0.7
function chatbot_chatgpt_tools_settings_init() {

    // Register tools settings
    // register_setting('chatbot_chatgpt_tools', 'chatbot_chatgpt_tools');
    // register_setting('chatbot_chatgpt_tools', 'chatbot_chatgpt_shortcode_tester');
    // register_setting('chatbot_chatgpt_tools', 'chatbot_chatgpt_capability_tester');
    register_setting('chatbot_chatgpt_tools', 'chatbot_chatgpt_options_exporter');
    register_setting('chatbot_chatgpt_tools', 'chatbot_chatgpt_manage_error_logs');

    // Tools Overview
    add_settings_section(
        'chatbot_chatgpt_tools_section',
        'Tools',
        'chatbot_chatgpt_tools_section_callback',
        'chatbot_chatgpt_tools'
    );

    // Tools Overview
    // add_settings_field(
    //     'chatbot_chatgpt_tools_field',
    //     'Tools',
    //     'chatbot_chatgpt_tools_setting_callback',
    //     'chatbot_chatgpt_tools',
    //     'chatbot_chatgpt_tools_section'
    // );

    // Shortcode Tester Overview
    add_settings_section(
        'chatbot_chatgpt_shortcode_tools_section',
        'Shortcode Tester',
        'chatbot_chatgpt_shortcode_tools_section_callback',
        'chatbot_chatgpt_shortcode_tools'
    );

    // Shortcode Tester Tool
    // add_settings_field(
    //     'chatbot_chatgpt_shortcode_tester_field',
    //     'Shortcode Tester',
    //     'chatbot_chatgpt_shortcode_tools_callback',
    //     'chatbot_chatgpt_tools',
    //     'chatbot_chatgpt_shortcode_tools_section'
    // );

    // Capability Check Overview
    add_settings_section(
        'chatbot_chatgpt_capability_tools_section',
        'Capability Check',
        'chatbot_chatgpt_capability_tools_section_callback',
        'chatbot_chatgpt_capability_tools'
    );

    // Capability Check Tool
    // add_settings_field(
    //     'chatbot_chatgpt_capability_tester_field',
    //     'Capability Check',
    //     'chatbot_chatgpt_capability_tools_callback',
    //     'chatbot_chatgpt_tools',
    //     'chatbot_chatgpt_capability_tools_section'
    // );

    // options_exporter Check Overview
    add_settings_section(
        'chatbot_chatgpt_options_exporter_tools_section',
        'Options Exporter',
        'chatbot_chatgpt_options_exporter_tools_section_callback',
        'chatbot_chatgpt_options_exporter_tools'
    );

    // options_exporter Check Tool
    add_settings_field(
        'chatbot_chatgpt_options_exporter',
        'Options Exporter',
        'chatbot_chatgpt_options_exporter_callback',
        'chatbot_chatgpt_tools',
        'chatbot_chatgpt_options_exporter_tools_section'
    );

    // Manage Error Logs
    add_settings_section(
        'chatbot_chatgpt_manage_error_logs_section',
        'Manage Error Logs',
        'chatbot_chatgpt_manage_error_logs_section_callback',
        'chatbot_chatgpt_manage_error_logs'
    );

    // add_settings_field(
    //     'chatbot_chatgpt_manage_error_logs_field',
    //     'Manage Error Logs',
    //     'chatbot_chatgpt_manage_error_logs_callback',
    //     'chatbot_chatgpt_tools',
    //     'chatbot_chatgpt_manage_error_logs_section'
    // );
    
}
add_action('admin_init', 'chatbot_chatgpt_tools_settings_init');

