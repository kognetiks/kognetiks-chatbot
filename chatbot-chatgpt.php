<?php
/*
 * Plugin Name: Kognetiks Chatbot
 * Plugin URI:  https://github.com/kognetiks/kognetiks-chatbot
 * Description: A simple plugin to add an AI powered chatbot to your WordPress website.
 * Version:     1.9.6
 * Author:      Kognetiks.com
 * Author URI:  https://www.kognetiks.com
 * License:     GPLv3 or later
 * License URI: https://www.gnu.org/licenses/gpl-30.html
 * 
 * Copyright (c) 2024 Stephen Howell
 *  
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU
 * General Public License version 3, as published by the Free Software Foundation. You may NOT assume
 * that you can use any other version of the GPL.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Kognetiks Chatbot for WordPress. If not, see https://www.gnu.org/licenses/gpl-3.0.html.
 * 
*/

// If this file is called directly, die.
defined( 'WPINC' ) || die;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

// Define the plugin version
defined ('CHATBOT_CHATGPT_VERSION') || define ('CHATBOT_CHATGPT_VERSION', '1.9.6');

// Main plugin file
define('CHATBOT_CHATGPT_PLUGIN_DIR_PATH', plugin_dir_path(__FILE__));

// Declare Globals
global $wpdb;

// Uniquely Identify the Visitor
global $session_id;

// Start output buffering to prevent "headers already sent" issues - Ver 1.8.5
ob_start();

// Updated for Ver 1.8.5
// Cookie "PHPSESSID" does not have a proper "SameSite" attribute value. Soon, cookies 
// without the "SameSite" attribute or with an invalid value will be treated as “Lax”. 
// This means that the cookie will no longer be sent in third-party contexts. If your 
// application depends on this cookie being available in such contexts, please add the 
// "SameSite=None" attribute to it. To know more about the "SameSite" attribute, 
// read https://developer.mozilla.org/docs/Web/HTTP/Headers/Set-Cookie/SameSite

// Start the session if it has not been started, set the global, then close the session
if (empty($session_id)) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start([
            'cookie_lifetime' => 0,
            'cookie_path' => '/',
            'cookie_domain' => $_SERVER['HTTP_HOST'],
            'cookie_secure' => true,
            'cookie_httponly' => true,
            'cookie_samesite' => 'Strict'
        ]);
    }
    $session_id = session_id();

}

ob_end_flush(); // End output buffering and send the buffer to the browser

// Include necessary files - Main files
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-gpt-api.php'; // ChatGPT API - Ver 1.6.9
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-gpt-assistant.php'; // Custom GPT Assistants - Ver 1.6.9
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-image-api.php'; // Image API - Ver 1.9.4
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-tts-api.php'; // TTS API - Ver 1.9.4
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-globals.php'; // Globals - Ver 1.6.5
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-shortcode.php';

require_once plugin_dir_path(__FILE__) . 'includes/chatbot-call-flow-api.php'; // ChatGPT API - Ver 1.9.5

// Include necessary files - Appearance - Ver 1.8.1
require_once plugin_dir_path(__FILE__) . 'includes/appearance/chatbot-settings-appearance-body.php';
require_once plugin_dir_path(__FILE__) . 'includes/appearance/chatbot-settings-appearance-dimensions.php';
require_once plugin_dir_path(__FILE__) . 'includes/appearance/chatbot-settings-appearance-text.php';
require_once plugin_dir_path(__FILE__) . 'includes/appearance/chatbot-settings-appearance-user-css.php';

// Include necessary files - Knowledge Navigator
require_once plugin_dir_path(__FILE__) . 'includes/knowledge-navigator/chatbot-kn-acquire.php'; // Knowledge Navigator Acquisition - Ver 1.6.3
require_once plugin_dir_path(__FILE__) . 'includes/knowledge-navigator/chatbot-kn-acquire-controller.php'; // Knowledge Navigator Acquisition - Ver 1.9.6
require_once plugin_dir_path(__FILE__) . 'includes/knowledge-navigator/chatbot-kn-acquire-words.php'; // Knowledge Navigator Acquisition - Ver 1.9.6
require_once plugin_dir_path(__FILE__) . 'includes/knowledge-navigator/chatbot-kn-analysis.php'; // Knowledge Navigator Analysis- Ver 1.6.2
require_once plugin_dir_path(__FILE__) . 'includes/knowledge-navigator/chatbot-kn-db.php'; // Knowledge Navigator - Database Management - Ver 1.6.3
require_once plugin_dir_path(__FILE__) . 'includes/knowledge-navigator/chatbot-kn-enhance-context.php'; // Knowledge Navigator - Enhance Context - Ver 1.6.9
require_once plugin_dir_path(__FILE__) . 'includes/knowledge-navigator/chatbot-kn-enhance-response.php'; // Knowledge Navigator - TD-IDF Response Enhancement - Ver 1.6.9
require_once plugin_dir_path(__FILE__) . 'includes/knowledge-navigator/chatbot-kn-scheduler.php'; // Knowledge Navigator - Scheduler - Ver 1.6.3
require_once plugin_dir_path(__FILE__) . 'includes/knowledge-navigator/chatbot-kn-settings.php'; // Knowledge Navigator - Settings - Ver 1.6.1

// Include necessary files - Settings
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-api-model.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-api-test.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-appearance.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-avatar.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-buttons.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-custom-gpts.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-diagnostics.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-links.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-localization.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-localize.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-notices.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-premium.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-registration-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-registration.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-reporting.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-setup.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings-support.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings/chatbot-settings.php';

// Include necessary files - Utilities - Ver 1.9.0
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-conversation-history.php'; // Ver 1.9.2
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-db-management.php'; // Database Management for Reporting - Ver 1.6.3
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-erase-conversation.php'; // Functions - Ver 1.8.6
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-file-upload.php'; // Functions - Ver 1.7.6
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-filter-out-html-tags.php'; // Functions - Ver 1.9.6
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-link-and-image-handling.php'; // Globals - Ver 1.9.1
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-models.php'; // Functions - Ver 1.9.4
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-names.php'; // Functions - Ver 1.9.4
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-threads.php'; // Ver 1.7.2.1
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-transients-file.php'; // Ver 1.9.2
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-transients.php'; // Ver 1.7.2
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-upgrade.php'; // Ver 1.6.7
require_once plugin_dir_path(__FILE__) . 'includes/utilities/chatbot-utilities.php'; // Ver 1.8.6

add_action('init', 'my_custom_buffer_start');
function my_custom_buffer_start(): void {
    ob_start();
}

// Check for Upgrades - Ver 1.7.7
if (!esc_attr(get_option('chatbot_chatgpt_upgraded'))) {
    chatbot_chatgpt_upgrade();
    update_option('chatbot_chatgpt_upgraded', 'Yes');
}

// Diagnotics on/off setting can be found on the Settings tab - Ver 1.5.0
$chatbot_chatgpt_diagnostics = esc_attr(get_option('chatbot_chatgpt_diagnostics', 'Off'));

// Dump the chatbot settings - Ver 1.8.6
// DIAG - Diagnostics
// back_trace('NOTICE', 'chatbot-chatgpt.php: Dump Options to File is ON');
// chatbot_chatgpt_dump_options_to_file();

// Model choice - Ver 1.9.4
global $model;
// Starting with V1.9.4 the model choice "gpt-4-turbo" is replaced with "gpt-4-1106-preview"
if (get_option('chatbot_chatgpt_model_choice') == 'gpt-4-turbo') {
    $model = 'gpt-4-1106-preview';
    update_option('chatbot_chatgpt_model_choice', $model);
    // DIAG - Diagnostics
    // back_trace ( 'NOTICE', 'Model upgraded: ' . $model);
}

// Voice choice - Ver 1.9.5
global $voice;
if (get_option('chatbot_chatgpt_voice_option') == 'alloy') {
    $voice = 'alloy';
    update_option('chatbot_chatgpt_voice_option', $voice);
    // DIAG - Diagnostics
    // back_trace ( 'NOTICE', 'Voice upgraded: ' . $voice);
}

// Custom buttons on/off setting can be found on the Settings tab - Ver 1.6.5
$chatbot_chatgpt_enable_custom_buttons = esc_attr(get_option('chatbot_chatgpt_enable_custom_buttons', 'Off'));

// Allow file uploads on/off setting can be found on the Settings tab - Ver 1.7.6
global $chatbot_chatgpt_allow_file_uploads;
// TEMP OVERRIDE - Ver 1.7.6
// update_option('chatbot_chatgpt_allow_file_uploads', 'No');
$chatbot_chatgpt_allow_file_uploads = esc_attr(get_option('chatbot_chatgpt_allow_file_uploads', 'No'));

// Suppress Notices on/off setting can be found on the Settings tab - Ver 1.6.5
global $chatbot_chatgpt_suppress_notices;
$chatbot_chatgpt_suppress_notices = esc_attr(get_option('chatbot_chatgpt_suppress_notices', 'Off'));

// Suppress Attribution on/off setting can be found on the Settings tab - Ver 1.6.5
global $chatbot_chatgpt_suppress_attribution;
$chatbot_chatgpt_suppress_attribution = esc_attr(get_option('chatbot_chatgpt_suppress_attribution', 'Off'));

// Suppress Learnings Message - Ver 1.7.1
global $chatbot_chatgpt_suppress_learnings;
$chatbot_chatgpt_suppress_learnings = esc_attr(get_option('chatbot_chatgpt_suppress_learnings', 'Random'));

// Context History - Ver 1.6.1
$context_history = [];

function chatbot_chatgpt_enqueue_admin_scripts(): void {
    wp_enqueue_script('chatbot_chatgpt_admin', plugins_url('assets/js/chatbot-chatgpt-admin.js', __FILE__), array('jquery'), '1.0.0', true);
}
add_action('admin_enqueue_scripts', 'chatbot_chatgpt_enqueue_admin_scripts');

// Activation, deactivation, and uninstall functions
register_activation_hook(__FILE__, 'chatbot_chatgpt_activate');
register_deactivation_hook(__FILE__, 'chatbot_chatgpt_deactivate');
register_uninstall_hook(__FILE__, 'chatbot_chatgpt_uninstall');
add_action('upgrader_process_complete', 'chatbot_chatgpt_upgrade_completed', 10, 2);

// Enqueue plugin scripts and styles
function chatbot_chatgpt_enqueue_scripts(): void {

    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;
    global $script_data_array;
    global $additional_instructions;
    global $model;
    global $voice;

    // Enqueue the styles
    wp_enqueue_style('dashicons');
    wp_enqueue_style('chatbot-chatgpt-css', plugins_url('assets/css/chatbot-chatgpt.css', __FILE__));

    // Now override the default styles with the custom styles - Ver 1.8.1
    chatbot_chatgpt_appearance_custom_css_settings();

    // Custom css overrides - Ver 1.8.1
    // $customer_css_path = plugins_url(assets/css/chatbot-chatgpt-custom.css', __FILE__));
    // if ( file_exists ( $customer_css_path )) {
    //     wp_enqueue_style('chatbot-chatgpt-custom-css', plugins_url('assets/css/chatbot-chatgpt-custom.css', __FILE__));
    // }

    // Enqueue the scripts
    wp_enqueue_script('chatbot-chatgpt-js', plugins_url('assets/js/chatbot-chatgpt.js', __FILE__), array('jquery'), '1.0', true);
    wp_enqueue_script('chatbot-chatgpt-local', plugins_url('assets/js/chatbot-chatgpt-local.js', __FILE__), array('jquery'), '1.0', true);

    // Enqueue DOMPurify - Ver 1.8.1
    // https://raw.githubusercontent.com/cure53/DOMPurify/main/dist/purify.min.js
    // https://chat.openai.com/c/275770c1-fa72-404b-97c2-2dad2e8a0230
    wp_enqueue_script( 'dompurify', plugin_dir_url(__FILE__) . 'assets/js/purify.min.js', array(), '1.0.0', true );

    // Localize the data for user id and page id
    $user_id = get_current_user_id();
    $page_id = get_the_id();

    $script_data_array = array(
        'user_id' => $user_id,
        'page_id' => $page_id,
        'session_id' => $session_id,
        'thread_id' => $thread_id,
        'assistant_id' => $assistant_id,
        'additional_instructions' => $additional_instructions,
        'model' => $model,
        'voice' => 'alloy',
    );

    // DIAG - Diagnostics - Ver 1.8.6
    // back_trace( 'NOTICE', '$user_id: ' . $user_id);
    // back_trace( 'NOTICE', '$page_id: ' . $page_id);
    // back_trace( 'NOTICE', '$session_id: ' . $session_id);
    // back_trace( 'NOTICE', '$thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', '$assistant_id: ' . $assistant_id);
    // back_trace( 'NOTICE', '$additional_instructions: ' . $additional_instructions);
    // back_trace( 'NOTICE', '$model: ' . $model);
    
    // Defaults for Ver 1.6.1
    $defaults = array(
        'chatbot_chatgpt_bot_name' => 'Kognetiks Chatbot',
        // TODO IDEA - Add a setting to fix or randomize the bot prompt
        'chatbot_chatgpt_bot_prompt' => 'Enter your question ...',
        'chatbot_chatgpt_initial_greeting' => 'Hello! How can I help you today?',
        'chatbot_chatgpt_subsequent_greeting' => 'Hello again! How can I help you?',
        'chatbot_chatgpt_display_style' => 'floating',
        'chatbot_chatgpt_assistant_alias' => 'primary',
        'chatbot_chatgpt_start_status' => 'closed',
        'chatbot_chatgpt_start_status_new_visitor' => 'closed',
        'chatbot_chatgpt_disclaimer_setting' => 'No',
        'chatbot_chatgpt_audience_choice' => 'all',
        'chatbot_chatgpt_max_tokens_setting' => '150',
        'chatbot_chatgpt_message_limit_setting' => '999',
        'chatbot_chatgpt_width_setting' => 'Narrow',
        'chatbot_chatgpt_diagnostics' => 'Off',
        'chatbot_chatgpt_avatar_icon_setting' => 'icon-001.png',
        'chatbot_chatgpt_avatar_icon_url_setting' => '',
        'chatbot_chatgpt_custom_avatar_icon_setting' => '',
        'chatbot_chatgpt_avatar_greeting_setting' => 'Howdy!!! Great to see you today! How can I help you?',
        'chatbot_chatgpt_model_choice' => 'gpt-3.5-turbo',
        'chatbot_chatgpt_conversation_context' => 'You are a versatile, friendly, and helpful assistant designed to support me in a variety of tasks.',
        'chatbot_chatgpt_enable_custom_buttons' => 'Off',
        'chatbot_chatgpt_custom_button_name_1' => '',
        'chatbot_chatgpt_custom_button_url_1' => '',
        'chatbot_chatgpt_custom_button_name_2' => '',
        'chatbot_chatgpt_custom_button_url_2' => '',
        'chatbot_chatgpt_allow_file_uploads' => 'No',
        'chatbot_chatgpt_timeout_setting' => '240',
        'chatbot_chatgpt_voice_option' => 'alloy',
        'chatbot_chatgpt_audio_output_format' => 'mp3',
    );

    // Revised for Ver 1.5.0 
    $option_keys = array(
        'chatbot_chatgpt_bot_name',
        'chatbot_chatgpt_bot_prompt', // Added in Ver 1.6.6
        'chatbot_chatgpt_initial_greeting',
        'chatbot_chatgpt_subsequent_greeting',
        'chatbot_chatgpt_display_style',
        'chatbot_chatgpt_assistant_alias',
        'chatbot_chatgpt_start_status',
        'chatbot_chatgpt_start_status_new_visitor',
        'chatbot_chatgpt_disclaimer_setting',
        'chatbot_chatgpt_audience_choice',
        'chatbot_chatgpt_max_tokens_setting',
        'chatbot_chatgpt_message_limit_setting',
        'chatbot_chatgpt_width_setting',
        'chatbot_chatgpt_diagnostics',
        'chatbot_chatgpt_avatar_icon_setting',
        'chatbot_chatgpt_avatar_icon_url_setting',
        'chatbot_chatgpt_custom_avatar_icon_setting',
        'chatbot_chatgpt_avatar_greeting_setting',
        'chatbot_chatgpt_enable_custom_buttons',
        'chatbot_chatgpt_custom_button_name_1',
        'chatbot_chatgpt_custom_button_url_1',
        'chatbot_chatgpt_custom_button_name_2',
        'chatbot_chatgpt_custom_button_url_2',
        'chatbot_chatgpt_allow_file_uploads',
        'chatbot_chatgpt_timeout_setting',
        'chatbot_chatgpt_voice_option',
        'chatbot_chatgpt_audio_output_format',
    );

    $chatbot_settings = array();
    foreach ($option_keys as $key) {
        $default_value = $defaults[$key] ?? '';
        $chatbot_settings[$key] = esc_attr(get_option($key, $default_value));
        // DIAG - Diagnostics
        // back_trace( 'NOTICE', 'chatbot-chatgpt.php: Key: ' . $key . ', Value: ' . $chatbot_settings[$key]);
    }

    $chatbot_settings['chatbot_chatgpt_icon_base_url'] = plugins_url( '/assets/icons/', __FILE__ );

    // Localize the data for javascript
    wp_localize_script('chatbot-chatgpt-js', 'php_vars', $script_data_array);

    wp_localize_script('chatbot-chatgpt-js', 'plugin_vars', array(
        'plugins_url' => plugins_url('', __FILE__ ),
    ));

    wp_localize_script('chatbot-chatgpt-local', 'chatbotSettings', $chatbot_settings);

    wp_localize_script('chatbot-chatgpt-js', 'chatbot_chatgpt_params', array(
        'plugins_url' => plugins_url('', __FILE__ ),
        'ajax_url' => admin_url('admin-ajax.php'),
    ));

    // Upload files - Ver 1.7.6
    wp_localize_script('chatbot-chatgpt-upload-trigger-js', 'chatbot_chatgpt_params', array(
        'plugins_url' => plugins_url('', __FILE__ ),
        'ajax_url' => admin_url('admin-ajax.php'),
    ));

}
add_action('wp_enqueue_scripts', 'chatbot_chatgpt_enqueue_scripts');

// Settings and Deactivation Links - Ver - 1.5.0
if (!function_exists('enqueue_jquery_ui')) {
    function enqueue_jquery_ui(): void {
        wp_enqueue_style('wp-jquery-ui-dialog');
        wp_enqueue_script('jquery-ui-dialog');
    }
    add_action( 'admin_enqueue_scripts', 'enqueue_jquery_ui' );
}

// Schedule Cleanup of Expired Transients
if (!wp_next_scheduled('chatbot_chatgpt_cleanup_event')) {
    wp_schedule_event(time(), 'daily', 'chatbot_chatgpt_cleanup_event');
}
add_action('chatbot_chatgpt_cleanup_event', 'clean_specific_expired_transients');

// Schedule Conversation Log Cleanup - Ver 1.6.7
if (!wp_next_scheduled('chatbot_chatgpt_conversation_log_cleanup_event')) {
    wp_schedule_event(time(), 'daily', 'chatbot_chatgpt_conversation_log_cleanup_event');
}
add_action('chatbot_chatgpt_conversation_log_cleanup_event', 'chatbot_chatgpt_conversation_log_cleanup');


// Handle Ajax requests
function chatbot_chatgpt_send_message(): void {

    // Global variables
    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;
    global $chatbot_chatgpt_display_style;
    global $chatbot_chatgpt_assistant_alias;
    global $script_data_array;
    global $additional_instructions;
    global $model;
    global $voice;

    global $flow_data;

    $api_key = '';

    // Retrieve the API key
    $api_key = esc_attr(get_option('chatbot_chatgpt_api_key'));

    // Retrieve the GPT Model
    if (!empty($model)) {
        $model = esc_attr(get_option('chatbot_chatgpt_model_choice', 'gpt-3.5-turbo'));
        // DIAG - Diagnostics
        // back_trace ( 'NOTICE', 'Model from options: ' . $model);
    } else {
        // SEE IF $script_data_array HAS THE MODEL
        if ( isset($script_data_array['model'])) {
            $model = $script_data_array['model'];
            // DIAG - Diagnostics
            // back_trace ( 'NOTICE', 'Model set in global: ' . $model);
        } else {
            // FIXME - I SHOULDN'T BE FALLING THRU HERE - DO NOTHING
            // DIAG - Diagnostics
            // back_trace ( 'ERROR', 'Model not set!!!');
        }
    }

    // Retrieve the Max tokens - Ver 1.4.2
    $max_tokens = esc_attr(get_option('chatbot_chatgpt_max_tokens_setting', 150));

    // Send only clean text via the API
    $message = sanitize_text_field($_POST['message']);

    // FIXME - ADD THIS BACK IN AFTER DECIDING WHAT TO DO ABOUT MISSING OR BAD API KEYS
    // Check API key and message
    if (!$api_key || !$message) {
        wp_send_json_error('Invalid API key or message');
    }

    // Removed in Ver 1.8.6 - 2024 02 15
    // $thread_id = '';
    // $assistant_id = '';
    // $user_id = '';
    // $page_id = '';
    
    // Check the transient for the Assistant ID - Ver 1.7.2
    $user_id = intval($_POST['user_id']);
    $page_id = intval($_POST['page_id']);

    // DIAG - Diagnostics - Ver 1.8.6
    // back_trace( 'NOTICE', 'chatbot_chatgpt_send_message $user_id: ' . $user_id);
    // back_trace( 'NOTICE', 'chatbot_chatgpt_send_message $page_id: ' . $page_id);

    $chatbot_settings['display_style'] = get_chatbot_chatgpt_transients( 'display_style', $user_id, $page_id);
    $chatbot_settings['assistant_alias'] = get_chatbot_chatgpt_transients( 'assistant_alias', $user_id, $page_id);
    $chatbot_settings['model'] = get_chatbot_chatgpt_transients( 'model', $user_id, $page_id);
    $chatbot_settings['voice'] = get_chatbot_chatgpt_transients( 'voice', $user_id, $page_id);

    $display_style = isset($chatbot_settings['display_style']) ? $chatbot_settings['display_style'] : '';
    $chatbot_chatgpt_assistant_alias = isset($chatbot_settings['assistant_alias']) ? $chatbot_settings['assistant_alias'] : '';

    $temp_model = $chatbot_settings['model']; // Store the model in a temporary variable before overwriting $chatbot_settings

    $chatbot_settings = get_chatbot_chatgpt_threads($user_id, $page_id);

    $chatbot_settings['model'] = $temp_model; // Restore the model after overwriting $chatbot_settings

    $assistant_id = isset($chatbot_settings['assistantID']) ? $chatbot_settings['assistantID'] : '';
    $thread_Id = isset($chatbot_settings['threadID']) ? $chatbot_settings['threadID'] : '';
    $model = isset($chatbot_settings['model']) ? $chatbot_settings['model'] : '';

    // DIAG - Diagnostics - Ver 1.8.6
    // back_trace( 'NOTICE', '$user_id: ' . $user_id);
    // back_trace( 'NOTICE', '$page_id: ' . $page_id);
    // back_trace( 'NOTICE', '$session_id: ' . $session_id);
    // back_trace( 'NOTICE', '$thread_id: ' . $thread_id);
    // back_trace( 'NOTICE', '$assistant_id: ' . $assistant_id);
    // back_trace( 'NOTICE', '$model: ' . $model);

    // Assistants
    // $chatbot_chatgpt_assistant_alias == 'original'; // Default
    // $chatbot_chatgpt_assistant_alias == 'primary';
    // $chatbot_chatgpt_assistant_alias == 'alternate';
    // $chatbot_chatgpt_assistant_alias == 'asst_xxxxxxxxxxxxxxxxxxxxxxxx'; // GPT Assistant Id
  
    // Which Assistant ID to use - Ver 1.7.2
    if ($chatbot_chatgpt_assistant_alias == 'original') {
        $use_assistant_id = 'No';
        // DIAG - Diagnostics - Ver 1.8.1
        // back_trace( 'NOTICE' , 'Using Original GPT Assistant ID');
    } elseif ($chatbot_chatgpt_assistant_alias == 'primary') {
        $assistant_id = esc_attr(get_option('chatbot_chatgpt_assistant_id'));
        $additional_instructions = esc_attr(get_option('chatbot_chatgpt_assistant_instructions'), '');
        $use_assistant_id = 'Yes';
        // DIAG - Diagnostics - Ver 1.8.1
        // back_trace( 'NOTICE' , 'Using Primary GPT Assistant ID ' .  $assistant_id);
        // Check if the GPT Assistant ID is blank, null, or "Please provide the GPT Assistant ID."
        if (empty($assistant_id) || $assistant_id == "Please provide the GPT Assistant Id.") {
            // Override the $use_assistant_id and set it to 'No'
            $use_assistant_id = 'No';
            // DIAG - Diagnostics - Ver 1.8.1
            // back_trace( 'NOTICE' ,'Falling back to ChatGPT API' );
        }
    } elseif ($chatbot_chatgpt_assistant_alias == 'alternate') {
        $assistant_id = esc_attr(get_option('chatbot_chatgpt_assistant_id_alternate'));
        $additional_instructions = esc_attr(get_option('chatbot_chatgpt_assistant_instructions_alternate'), '');
        $use_assistant_id = 'Yes';
        // DIAG - Diagnostics - Ver 1.8.1
        // back_trace( 'NOTICE' , 'Using Alternate GPT Assistant ID ' .  $assistant_id);
        // Check if the GPT Assistant ID is blank, null, or "Please provide the GPT Assistant ID."
        if (empty($assistant_id) || $assistant_id == "Please provide the GPT Assistant Id.") {
            // Override the $use_assistant_id and set it to 'No'
            $use_assistant_id = 'No';
            // DIAG - Diagnostics - Ver 1.8.1
            // back_trace( 'NOTICE' ,'Falling back to ChatGPT API' );
        }
    } else {
        // Reference GPT Assistant IDs directly - Ver 1.7.3
        if (str_starts_with($chatbot_chatgpt_assistant_alias, 'asst_')) {
            // DIAG - Diagnostics
            // back_trace( 'NOTICE', 'Using GPT Assistant ID: ' . $chatbot_chatgpt_assistant_alias);
            // Override the $assistant_id with the GPT Assistant ID
            $assistant_id = $chatbot_chatgpt_assistant_alias;
            $use_assistant_id = 'Yes';
            // DIAG - Diagnostics - Ver 1.8.1
            // back_trace( 'NOTICE' , 'Using GPT Assistant Id ' . $assistant_id);
        } else {
            // DIAG - Diagnostics
            // back_trace( 'NOTICE', 'Using ChatGPT API: ' . $chatbot_chatgpt_assistant_alias);
            // Override the $use_assistant_id and set it to 'No'
            $use_assistant_id = 'No';
            // DIAG - Diagnostics - Ver 1.8.1
            // back_trace( 'NOTICE' , 'Falling back to ChatGPT API');
        }
    }

    // Decide whether to use an Flow, Assistant or original ChatGPT
    if ($model == 'flow'){
        
        // DIAG - Diagnostics
        // back_trace( 'NOTICE', 'Using ChatGPT Flow');

        // Reload the model - BELT & SUSPENDERS
        $script_data_array['model'] = $model;

        // Get the step from the transient
        $kflow_step = get_chatbot_chatgpt_transients( 'kflow_step', null, null, $session_id);
        if (empty($kflow_step)) {
            $kflow_step = 0; // FIXME - Set to 1 or to zero?
        }

        // $thread_id
        $thread_id = '[answer=' . $kflow_step + 1 . ']';
        
        // Add +1 to $script_data_array['next_step']
        $kflow_step = $kflow_step + 1;

        // Set the next step
        set_chatbot_chatgpt_transients( 'kflow_step', $kflow_step, null, null, $session_id);

        // DIAG - Diagnostics
        // back_trace( 'NOTICE', '$message: ' . $message);
        append_message_to_conversation_log($session_id, $user_id, $page_id, 'Visitor', $thread_id, $assistant_id, $message);

        // BELT & SUSPENDERS
        $thread_id = '';

        // Send message to ChatGPT API - Ver 1.6.7
        $response = chatbot_chatgpt_call_flow_api($api_key, $message);
        wp_send_json_success($response);

    } elseif ($use_assistant_id == 'Yes') {
        // DIAG - Diagnostics
        // back_trace( 'NOTICE', 'Using GPT Assistant ID: ' . $use_assistant_id);
        // back_trace( 'NOTICE', '$user_id ' . $user_id);
        // back_trace( 'NOTICE', '$page_id ' . $page_id);

        // DIAG - Diagnostics
        // back_trace( 'NOTICE', '$message ' . $message);
        append_message_to_conversation_log($session_id, $user_id, $page_id, 'Visitor', $thread_id, $assistant_id, $message);
        
        // Send message to Custom GPT API - Ver 1.6.7
        $response = chatbot_chatgpt_custom_gpt_call_api($api_key, $message, $assistant_id, $thread_id, $user_id, $page_id);

        // Use TF-IDF to enhance response
        $response = $response . chatbot_chatgpt_enhance_with_tfidf($message);

        // DIAG - Diagnostics
        // back_trace( 'NOTICE', '$response ' . print_r($response,true));
        append_message_to_conversation_log($session_id, $user_id, $page_id, 'Chatbot', $thread_id, $assistant_id, $response);

        // Clean (erase) the output buffer - Ver 1.6.8
        ob_clean();
        if (str_starts_with($response, 'Error:') || str_starts_with($response, 'Failed:')) {
            // Return response
            // back_trace( 'NOTICE', '$response ' . print_r($response,true));
            wp_send_json_error('Oops! Something went wrong on our end. Please try again later');
        } else {
            // DIAG - Diagnostics
            // back_trace( 'NOTICE', 'Check for links and images in response before returning');
            $response = chatbot_chatgpt_check_for_links_and_images($response);
            // Return response
            wp_send_json_success($response);
        }
    } else {
        // DIAG - Diagnostics
        // back_trace( 'NOTICE', 'Using ChatGPT');
        // back_trace( 'NOTICE', '$user_id ' . $user_id);
        // back_trace( 'NOTICE', '$page_id ' . $page_id);

        // DIAG - Diagnostics
        // back_trace( 'NOTICE', '$message ' . $message);
        append_message_to_conversation_log($session_id, $user_id, $page_id, 'Visitor', $thread_id, $assistant_id, $message);
        
        // If $model starts with 'gpt' then the chatbot_chatgpt_call_api or 'dall' then chatbot_chatgpt_call_image_api
        // TRY NOT TO FETCH MODEL AGAIN
        // $model = esc_attr(get_option('chatbot_chatgpt_model_choice', 'gpt-3.5-turbo'));
        if (str_starts_with($model, 'gpt')) {
            // Reload the model - BELT & SUSPENDERS
            $script_data_array['model'] = $model;
            // Send message to ChatGPT API - Ver 1.6.7
            $response = chatbot_chatgpt_call_api($api_key, $message);
        } elseif (str_starts_with($model, 'dall')) {
            // Reload the model - BELT & SUSPENDERS
            $script_data_array['model'] = $model;
            // Send message to Image API - Ver 1.9.4
            $response = chatbot_chatgpt_call_image_api($api_key, $message);
        } elseif (str_starts_with($model, 'tts')) {
            // Reload the model - BELT & SUSPENDERS
            $script_data_array['model'] = $model;
            // Send message to TTS API - Text-to-speech - Ver 1.9.5
            $response = chatbot_chatgpt_call_tts_api($api_key, $message);
        } elseif (str_starts_with($model,"whisper")) {
            $script_data_array['model'] = $model;
            // Send message to STT API - Speech-to-text - Ver 1.9.6
            $response = chatbot_chatgpt_call_stt_api($api_key, $message);
        } else {
            // Reload the model - BELT & SUSPENDERS
            $script_data_array['model'] = $model;
            // Send message to ChatGPT API - Ver 1.6.7
            $response = chatbot_chatgpt_call_api($api_key, $message);
        }
        
        // DIAG - Diagnostics
        // back_trace( 'NOTICE', ['message' => 'BEFORE CALL TO ENHANCE TFIDF', 'response' => $response]);
        
        // Use TF-IDF to enhance response
        $response = $response . chatbot_chatgpt_enhance_with_tfidf($message);
        // DIAG - Diagnostics
        // back_trace( 'NOTICE', ['message' => 'AFTER CALL TO ENHANCE TFIDF', 'response' => $response]);

        // DIAG - Diagnostics
        // back_trace( 'NOTICE', '$response ' . print_r($response,true));
        append_message_to_conversation_log($session_id, $user_id, $page_id, 'Chatbot', $thread_id, $assistant_id, $response);

        // DIAG - Diagnostics
        // back_trace( 'NOTICE', 'Check for links and images in response before returning');
        $response = chatbot_chatgpt_check_for_links_and_images($response);

        // Return response
        wp_send_json_success($response);
    }

    wp_send_json_error('Oops, I fell through the cracks!');

}

// Add action to send messages - Ver 1.0.0
add_action('wp_ajax_chatbot_chatgpt_send_message', 'chatbot_chatgpt_send_message');
add_action('wp_ajax_nopriv_chatbot_chatgpt_send_message', 'chatbot_chatgpt_send_message');

// Add action to upload files - Ver 1.7.6
add_action('wp_ajax_chatbot_chatgpt_upload_file_to_assistant', 'chatbot_chatgpt_upload_file_to_assistant');
add_action('wp_ajax_nopriv_chatbot_chatgpt_upload_file_to_assistant', 'chatbot_chatgpt_upload_file_to_assistant');

// Add action to erase conversation - Ver 1.8.6
add_action('wp_ajax_chatbot_chatgpt_erase_conversation', 'chatbot_chatgpt_erase_conversation_handler');
add_action('wp_ajax_nopriv_chatbot_chatgpt_erase_conversation', 'chatbot_chatgpt_erase_conversation_handler'); // For logged-out users, if needed

// Settings and Deactivation - Ver 1.5.0
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'chatbot_chatgpt_plugin_action_links');

// Crawler aka Knowledge Navigator - Ver 1.6.1
function chatbot_chatgpt_kn_status_activation(): void {
    add_option('chatbot_chatgpt_kn_status', 'Never Run');
    // clear any old scheduled runs
    if (wp_next_scheduled('crawl_scheduled_event_hook')) {
        wp_clear_scheduled_hook('crawl_scheduled_event_hook');
    }
    // clear the 'knowledge_navigator_scan_hook' hook on plugin activation - Ver 1.6.3
    if (wp_next_scheduled('knowledge_navigator_scan_hook')) {
        // BREAK/FIX - Do not unset the hook - Ver 1.8.5
        // wp_clear_scheduled_hook('knowledge_navigator_scan_hook'); // Clear scheduled runs
    }
}
register_activation_hook(__FILE__, 'chatbot_chatgpt_kn_status_activation');

// Clean Up in Aisle 4
function chatbot_chatgpt_kn_status_deactivation(): void {
    delete_option('chatbot_chatgpt_kn_status');
    wp_clear_scheduled_hook('knowledge_navigator_scan_hook'); 
}
register_deactivation_hook(__FILE__, 'chatbot_chatgpt_kn_status_deactivation');

// Function to add a new message and response, keeping only the last five - Ver 1.6.1
function addEntry($transient_name, $newEntry): void {

    $context_history = get_transient($transient_name);
    if (!$context_history) {
        $context_history = [];
    }

    // Determine the total length of all existing entries
    $totalLength = 0;
    foreach ($context_history as $entry) {
        if (is_string($entry)) {
            $totalLength += strlen($entry);
        } elseif (is_array($entry)) {
            $totalLength += strlen(json_encode($entry)); // Convert to string if an array
        }
    }

    // IDEA - How will the new threading option from OpenAI change how this works?
    // Define thresholds for the number of entries to keep
    $maxEntries = 30; // Default maximum number of entries
    if ($totalLength > 5000) { // Higher threshold
        $maxEntries = 20;
    }
    if ($totalLength > 10000) { // Lower threshold
        $maxEntries = 10;
    }

    while (count($context_history) >= $maxEntries) {
        array_shift($context_history); // Remove the oldest element
    }

    if (is_array($newEntry)) {
        $newEntry = json_encode($newEntry); // Convert the array to a string
    }

    array_push($context_history, $newEntry); // Append the new element
    set_transient($transient_name, $context_history); // Update the transient
}


// Function to return message and response - Ver 1.6.1
function concatenateHistory($transient_name): string {
    $context_history = get_transient($transient_name);
    if (!$context_history) {
        return ''; // Return an empty string if the transient does not exist
    }
    return implode(' ', $context_history); // Concatenate the array values into a single string
}

// Initialize the Greetings - Ver 1.6.1
function enqueue_greetings_script(): void {

    // DIAG - Diagnostics - Ver 1.6.1
    // back_trace( 'NOTICE', "enqueue_greetings_script() called");

    wp_enqueue_script('greetings', plugin_dir_url(__FILE__) . 'assets/js/greetings.js', array('jquery'), null, true);

    // If user is logged in, then modify greeting if greeting contains "[...]" or remove if not logged in - Ver 1.9.4
    if (is_user_logged_in()) {

        $current_user_id = get_current_user_id();
        $current_user = get_userdata($current_user_id);

        //Do this for Initial Greeting
        $initial_greeting = esc_attr(get_option('chatbot_chatgpt_initial_greeting', 'Hello! How can I help you today?'));

        // Determine what the field name is between the brackets
        $user_field_name = '';
        $user_field_name = substr($initial_greeting, strpos($initial_greeting, '[') + 1, strpos($initial_greeting, ']') - strpos($initial_greeting, '[') - 1);

        // If $initial_greeting contains "[$user_field_name]" then replace with field from DB
        if (strpos($initial_greeting, '[' . $user_field_name . ']') !== false) {
            $initial_greeting = str_replace('[' . $user_field_name . ']', $current_user->$user_field_name, $initial_greeting);
        } else {
            $initial_greeting = str_replace('[' . $user_field_name . ']', '', $initial_greeting);
        }

        // Do this for Subsequent Greeting
        $subsequent_greeting = esc_attr(get_option('chatbot_chatgpt_subsequent_greeting', 'Hello again! How can I help you?'));

        // Determine what the field name is between the brackets
        $user_field_name = '';
        $user_field_name = substr($subsequent_greeting, strpos($subsequent_greeting, '[') + 1, strpos($subsequent_greeting, ']') - strpos($subsequent_greeting, '[') - 1);

        // If $subsequent_greeting contains "[$user_field_name]" then replace with field from DB
        if (strpos($subsequent_greeting, '[' . $user_field_name . ']') !== false) {
            $subsequent_greeting = str_replace('[' . $user_field_name . ']', $current_user->$user_field_name, $subsequent_greeting);
        } else {
            $subsequent_greeting = str_replace('[' . $user_field_name . ']', '', $subsequent_greeting);
        }

    } else {

        $initial_greeting = esc_attr(get_option('chatbot_chatgpt_initial_greeting', 'Hello! How can I help you today?'));

        $user_field_name = '';
        $user_field_name = substr($initial_greeting, strpos($initial_greeting, '[') + 1, strpos($initial_greeting, ']') - strpos($initial_greeting, '[') - 1 );

        // $initial_greeting = str_replace('[' . $user_field_name . ']', '', $initial_greeting);
        $initial_greeting = preg_replace('/\s*\[' . preg_quote($user_field_name, '/') . '\]\s*/', '', $initial_greeting);

        $subsequent_greeting = esc_attr(get_option('chatbot_chatgpt_subsequent_greeting', 'Hello again! How can I help you?'));

        $user_field_name = '';
        $user_field_name = substr($subsequent_greeting, strpos($subsequent_greeting, '[') + 1, strpos($subsequent_greeting, ']') - strpos($subsequent_greeting, '[') - 1);

        // $subsequent_greeting = str_replace('[' . $user_field_name . ']', '', $subsequent_greeting);
        $subsequent_greeting = preg_replace('/\s*\[' . preg_quote($user_field_name, '/') . '\]\s*/', '', $subsequent_greeting);
    }

    $greetings = array(
        'initial_greeting' => $initial_greeting,
        'subsequent_greeting' => $subsequent_greeting,
    );

    wp_localize_script('greetings', 'greetings_data', $greetings);

}
add_action('wp_enqueue_scripts', 'enqueue_greetings_script');


// Function to add the adaptive appearance settings to the Chatbot settings page
function chatbot_chatgpt_settings_appearance(): void {

    // Register the settings
    register_setting('chatbot_chatgpt_settings_appearance', 'chatbot_chatgpt_appearance');

    // Add the adaptive appearance settings section
    add_settings_section(
        'chatbot_chatgpt_settings_appearance_section',
        'Adaptive Appearance Settings',
        'chatbot_chatgpt_settings_appearance_section_callback',
        'chatbot_chatgpt_settings_appearance'
    );

    // Add the adaptive appearance settings fields
    add_settings_field(
        'chatbot_chatgpt_settings_appearance_field',
        'Adaptive Appearance Settings',
        'chatbot_chatgpt_settings_appearance_field_callback',
        'chatbot_chatgpt_settings_appearance',
        'chatbot_chatgpt_settings_appearance_section'
    );

}

// Add the color picker to the adaptive appearance settings section - Ver 1.8.1
function enqueue_color_picker($hook_suffix): void {
    // first check that $hook_suffix is appropriate for your admin page
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('my-script-handle', plugin_dir_url(__FILE__) . 'assets/js/chatbot-chatgpt-color-picker.js', array('wp-color-picker'), false, true);
}
add_action('admin_enqueue_scripts', 'enqueue_color_picker');

// Determine if the plugin is installed
function kchat_get_plugin_version() {

    if (!function_exists('get_plugin_data')) {
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }

    $plugin_data = get_plugin_data(plugin_dir_path(__FILE__) . 'chatbot-chatgpt.php');
    $plugin_version = $plugin_data['Version'];
    update_option('chatbot_chatgpt_plugin_version', $plugin_version);
    // DIAG - Log the plugin version
    // back_trace( 'NOTICE', 'Plugin version '. $plugin_version);

    return $plugin_version;

}
