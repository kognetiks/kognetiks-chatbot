<?php
/*
 * Plugin Name: Chatbot ChatGPT
 * Plugin URI:  https://github.com/kognetiks/chatbot-chatgpt
 * Description: A simple plugin to add a Chatbot ChatGPT to your Wordpress Website.
 * Version:     1.7.5
 * Author:      Kognetiks.com
 * Author URI:  https://www.kognetiks.com
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *  
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU
 * General Public License version 2, as published by the Free Software Foundation. You may NOT assume
 * that you can use any other version of the GPL.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Chatbot ChatGPT. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
 * 
*/

// If this file is called directly, die.
defined( 'WPINC' ) || die;

// If this file is called directly, die.
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

// Declare Globals here - Ver 1.6.3
global $wpdb; // Declare the global $wpdb object

// Uniquely Identify the Visitor - Ver 1.7.4
global $sessionId; // Declare the global $sessionID variable

if ($sessionId == '') {
    session_start();
}
$sessionId = session_id();
// error_log('Session ID: ' . $sessionId);

// Include necessary files
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-chatgpt-globals.php'; // Globals - Ver 1.6.5

// Include necessary files - ChatGPT API and Custom GPT Assistant API - Ver 1.6.9
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-chatgpt-call-gpt-api.php'; // ChatGPT API - Ver 1.6.9
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-chatgpt-call-gpt-assistant.php'; // Custom GPT Assistants - Ver 1.6.9

// Include necessary files - Knowledge Navigator
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-chatgpt-kn-acquire.php'; // Knowledge Navigator Acquistion - Ver 1.6.3
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-chatgpt-kn-acquire-words.php'; // Knowledge Navigator Acquistion - Ver 1.6.5
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-chatgpt-kn-acquire-word-pairs.php'; // Knowledge Navigator Acquistion - Ver 1.6.5
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-chatgpt-kn-analysis.php'; // Knowlege Navigator Analysis- Ver 1.6.2
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-chatgpt-kn-db.php'; // Knowledge Navigator - Database Management - Ver 1.6.3
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-chatgpt-kn-enhance-response.php'; // Knowledge Navigator - TD-IDF Response Enhancement - Ver 1.6.9
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-chatgpt-kn-scheduler.php'; // Knowledge Navigator - Scheduler - Ver 1.6.3
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-chatgpt-kn-settings.php'; // Knowlege Navigator - Settings - Ver 1.6.1

// Include necessary files
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-chatgpt-db-management.php'; // Database Management for Reporting - Ver 1.6.3
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-chatgpt-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-chatgpt-settings-api-model.php'; // Refactoring Settings - Ver 1.5.0
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-chatgpt-settings-api-test.php'; // Refactoring Settings - Ver 1.6.3
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-chatgpt-settings-avatar.php'; // Refactoring Settings - Ver 1.5.0
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-chatgpt-settings-buttons.php'; // Refactoring Settings - Ver 1.6.5
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-chatgpt-settings-custom-gpts.php'; // Refactoring Settings - Ver 1.7.2
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-chatgpt-settings-diagnostics.php'; // Refactoring Settings - Ver 1.6.5
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-chatgpt-settings-links.php'; // Refactoring Settings - Ver 1.5.0
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-chatgpt-settings-localization.php'; // Refactoring Settings - Ver 1.7.2.1
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-chatgpt-settings-localize.php'; // Fixing localStorage - Ver 1.6.1
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-chatgpt-settings-notices.php'; // Notices - Ver 1.6.3
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-chatgpt-settings-premium.php'; // Refactoring Settings - Ver 1.5.0
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-chatgpt-settings-registration.php'; // Refactoring Settings - Ver 1.5.0
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-chatgpt-settings-reporting.php'; // Reporting - Ver 1.6.3
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-chatgpt-settings-setup.php'; // Refactoring Settings - Ver 1.5.0
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-chatgpt-settings-skins.php'; // Adpative Skins - Ver 1.6.7
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-chatgpt-settings-support.php'; // Refactoring Settings - Ver 1.5.0
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-chatgpt-shortcode.php';
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-chatgpt-threads.php'; // Ver 1.7.2.1
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-chatgpt-transients.php'; // Ver 1.7.2
require_once plugin_dir_path(__FILE__) . 'includes/chatbot-chatgpt-upgrade.php'; // Ver 1.6.7

add_action('init', 'my_custom_buffer_start');
function my_custom_buffer_start() {
    ob_start();
}

// Diagnotics on/off setting can be found on the Settings tab - Ver 1.5.0
// update_option('chatbot_chatgpt_diagnostics', 'Off');
global $chatbot_chatgpt_diagnostics;
$chatbot_chatgpt_diagnostics = esc_attr(get_option('chatbot_chatgpt_diagnostics', 'Off'));

// Custom buttons on/off setting can be found on the Settings tab - Ver 1.6.5
global $chatbot_chatgpt_enable_custom_buttons;
$chatbot_chatgpt_enable_custom_buttons = esc_attr(get_option('chatbot_chatgpt_enable_custom_buttons', 'Off'));

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

function chatbot_chatgpt_enqueue_admin_scripts() {
    wp_enqueue_script('chatbot_chatgpt_admin', plugins_url('assets/js/chatbot-chatgpt-admin.js', __FILE__), array('jquery'), '1.0.0', true);
}
add_action('admin_enqueue_scripts', 'chatbot_chatgpt_enqueue_admin_scripts');

// Enqueue plugin scripts and styles
function chatbot_chatgpt_enqueue_scripts() {
    // Ensure the Dashicons font is properly enqueued - Ver 1.1.0
    wp_enqueue_style( 'dashicons' );
    wp_enqueue_style('chatbot-chatgpt-css', plugins_url('assets/css/chatbot-chatgpt.css', __FILE__));
    wp_enqueue_script('chatbot-chatgpt-js', plugins_url('assets/js/chatbot-chatgpt.js', __FILE__), array('jquery'), '1.0', true);
    // Enqueue the chatbot-chatgpt-local.js file - Ver 1.4.1
    wp_enqueue_script('chatbot-chatgpt-local', plugins_url('assets/js/chatbot-chatgpt-local.js', __FILE__), array('jquery'), '1.0', true);
  
    // Localize the data for user id and page id
    $user_id = get_current_user_id();
    $page_id = get_the_ID();
    $script_data_array = array(
        'user_id' => $user_id,
        'page_id' => $page_id
    );
    wp_localize_script('chatbot-chatgpt-js', 'php_vars', $script_data_array);

    // Defaults for Ver 1.6.1
    $defaults = array(
        'chatgpt_bot_name' => 'Chatbot ChatGPT',
        // TODO IDEA - Add a setting to fix or randomize the bot prompt
        'chatgpt_chatbot_bot_prompt' => 'Enter your question ...',
        'chatgpt_initial_greeting' => 'Hello! How can I help you today?',
        'chatgpt_subsequent_greeting' => 'Hello again! How can I help you?',
        'chatbot_chatgpt_display_style' => 'floating',
        'chatbot_chatgpt_assistant_alias' => 'primary',
        'chatgptStartStatus' => 'closed',
        'chatgptStartStatusNewVisitor' => 'closed',
        'chatgpt_disclaimer_setting' => 'No',
        'chatgpt_max_tokens_setting' => '150',
        'chatgpt_width_setting' => 'Narrow',
        'chatbot_chatgpt_diagnostics' => 'Off',
        'chatgpt_avatar_icon_setting' => 'icon-001.png',
        'chatgpt_avatar_icon_url_setting' => '',
        'chatgpt_custom_avatar_icon_setting' => 'icon-001.png',
        'chatgpt_avatar_greeting_setting' => 'Howdy!!! Great to see you today! How can I help you?',
        'chatgpt_model_choice' => 'gpt-3.5-turbo',
        'chatgpt_max_tokens_setting' => 150,
        'chatbot_chatgpt_conversation_context' => 'You are a versatile, friendly, and helpful assistant designed to support me in a variety of tasks.',
        'chatbot_chatgpt_enable_custom_buttons' => 'Off',
        'chatbot_chatgpt_custom_button_name_1' => '',
        'chatbot_chatgpt_custom_button_url_1' => '',
        'chatbot_chatgpt_custom_button_name_2' => '',
        'chatbot_chatgpt_custom_button_url_2' => '',
    );

    // Revised for Ver 1.5.0 
    $option_keys = array(
        'chatgpt_bot_name',
        'chatgpt_chatbot_bot_prompt', // Added in Ver 1.6.6
        'chatgpt_initial_greeting',
        'chatgpt_subsequent_greeting',
        'chatbot_chatgpt_display_style',
        'chatbot_chatgpt_assistant_alias',
        'chatgptStartStatus',
        'chatgptStartStatusNewVisitor',
        'chatgpt_disclaimer_setting',
        'chatgpt_max_tokens_setting',
        'chatgpt_width_setting',
        'chatbot_chatgpt_diagnostics',
        // Avatar Options - Ver 1.5.0
        'chatgpt_avatar_icon_setting',
        'chatgpt_avatar_icon_url_setting',
        'chatgpt_custom_avatar_icon_setting',
        'chatgpt_avatar_greeting_setting',
        'chatbot_chatgpt_enable_custom_buttons',
        'chatbot_chatgpt_custom_button_name_1',
        'chatbot_chatgpt_custom_button_url_1',
        'chatbot_chatgpt_custom_button_name_2',
        'chatbot_chatgpt_custom_button_url_2',

    );

    $chatbot_settings = array();
    foreach ($option_keys as $key) {
        $default_value = isset($defaults[$key]) ? $defaults[$key] : '';
        $chatbot_settings[$key] = esc_attr(get_option($key, $default_value));
    }

    $chatbot_settings['iconBaseURL'] = plugins_url( 'assets/icons/', __FILE__ );
    wp_localize_script('chatbot-chatgpt-js', 'plugin_vars', array(
        'pluginUrl' => plugins_url('', __FILE__ ),
    ));

    wp_localize_script('chatbot-chatgpt-local', 'chatbotSettings', $chatbot_settings);

    wp_localize_script('chatbot-chatgpt-js', 'chatbot_chatgpt_params', array(
        'ajax_url' => admin_url('admin-ajax.php'),
    ));

    // Populate the chatbot settings array with values from the database, using default values where necessary
    $chatbot_settings = array();
    foreach ($option_keys as $key) {
        $default_value = isset($defaults[$key]) ? $defaults[$key] : '';
        $chatbot_settings[$key] = esc_attr(get_option($key, $default_value));
        // DIAG - Diagnostics
        // chatbot_chatgpt_back_trace( 'NOTICE', 'chatbot-chatgpt.php: Key: ' . $key . ', Value: ' . $chatbot_settings[$key]);
    }

    // Update localStorage - Ver 1.6.1
    echo "<script type=\"text/javascript\">
    document.addEventListener('DOMContentLoaded', (event) => {
        // Encode the chatbot settings array into JSON format for use in JavaScript
        let chatbotSettings = " . json_encode($chatbot_settings) . ";

        Object.keys(chatbotSettings).forEach((key) => {
            if(!localStorage.getItem(key)) {
                // DIAG - Log the key and value
                // console.log('Chatbot ChatGPT: NOTICE: Setting ' + key + ' in localStorage');
                localStorage.setItem(key, chatbotSettings[key]);
            } else {
                // DIAG - Log the key and value
                // console.log('Chatbot ChatGPT: NOTICE: ' + key + ' is already set in localStorage');
            }
        });
    });
    </script>";
    
}
add_action('wp_enqueue_scripts', 'chatbot_chatgpt_enqueue_scripts');


// Settings and Deactivation Links - Ver - 1.5.0
function enqueue_jquery_ui() {
    wp_enqueue_style('wp-jquery-ui-dialog');
    wp_enqueue_script('jquery-ui-dialog');
}
add_action( 'admin_enqueue_scripts', 'enqueue_jquery_ui' );


// Handle Ajax requests
function chatbot_chatgpt_send_message() {
    // Retrieve the API key
    $api_key = esc_attr(get_option('chatgpt_api_key'));
    // Retrieve the Use Custom GPT Assistant Id
    $model = esc_attr(get_option('chatgpt_model_choice', 'gpt-3.5-turbo'));
    // FIXME - If gpt-4-turbo is selected, set the API model to gpt-4-1106-preview, i.e., the API name for the model
    if ($model == 'gpt-4-turbo') {
        $model = 'gpt-4-1106-preview';
    }
    // DIAG - Diagnostics
    // chatbot_chatgpt_back_trace( 'NOTICE', '$model: ' . $model);
    // Retrieve the Max tokens - Ver 1.4.2
    $max_tokens = esc_attr(get_option('chatgpt_max_tokens_setting', 150));
    // Send only clean text via the API
    $message = sanitize_text_field($_POST['message']);

    // FIXME - ADD THIS BACK IN AFTER DECIDING WHAT TO DO ABOUT MISSING OR BAD API KEYS
    // Check API key and message
    if (!$api_key || !$message) {
        wp_send_json_error('Invalid API key or message');
    }

    $thread_Id = '';
    $assistant_id = '';
    $user_id = '';
    $page_id = '';
    // error_log ('$sessionId ' . $sessionId);
    
    // Check the transient for the Assistant ID - Ver 1.7.2
    $user_id = intval($_POST['user_id']);
    $page_id = intval($_POST['page_id']); 
    // DIAG - Diagnostics
    // chatbot_chatgpt_back_trace( 'NOTICE', '$user_id ' . $user_id);
    // chatbot_chatgpt_back_trace( 'NOTICE', '$page_id ' . $page_id);
    $chatbot_settings = get_chatbot_chatgpt_transients($user_id, $page_id);
    $display_style = isset($chatbot_settings['display_style']) ? $chatbot_settings['display_style'] : '';
    $chatbot_chatgpt_assistant_alias = isset($chatbot_settings['assistant_alias']) ? $chatbot_settings['assistant_alias'] : '';
    $chatbot_settings = get_chatbot_chatgpt_threads($user_id, $page_id);
    $assistant_id = isset($chatbot_settings['assistantID']) ? $chatbot_settings['assistantID'] : '';
    $thread_Id = isset($chatbot_settings['threadID']) ? $chatbot_settings['threadID'] : '';

    // Assistants
    // $chatbot_chatgpt_assistant_alias == 'original'; // Default
    // $chatbot_chatgpt_assistant_alias == 'primary';
    // $chatbot_chatgpt_assistant_alias == 'alternate';
    // $chatbot_chatgpt_assistant_alias == 'asst_xxxxxxxxxxxxxxxxxxxxxxxx'; // Custom GPT Assistant Id
  
    // Which Assistant ID to use - Ver 1.7.2
    if ($chatbot_chatgpt_assistant_alias == 'original') {
        $use_assistant_id = 'No';
        // error_log ('Using Original GPT Assistant Id');
    } elseif ($chatbot_chatgpt_assistant_alias == 'primary') {
        $assistant_id = esc_attr(get_option('chatbot_chatgpt_assistant_id'));
        $use_assistant_id = 'Yes';
        // error_log ('Using Primary GPT Assistant Id ' . $assistant_id);
        // Check if the Custom GPT Assistant Id is blank, null, or "Please provide the Customer GPT Assistant Id."
        if (empty($assistant_id) || $assistant_id == "Please provide the Customer GPT Assistant Id.") {
            // Override the $use_assistant_id and set it to 'No'
            $use_assistant_id = 'No';
            // error_log ('Falling back to ChatGPT API');
        }
    } elseif ($chatbot_chatgpt_assistant_alias == 'alternate') {
        $assistant_id = esc_attr(get_option('chatbot_chatgpt_assistant_id_alternate'));
        $use_assistant_id = 'Yes';
        // error_log ('Using Alternate GPT Assistant Id ' . $assistant_id);
        // Check if the Custom GPT Assistant Id is blank, null, or "Please provide the Customer GPT Assistant Id."
        if (empty($assistant_id) || $assistant_id == "Please provide the Customer GPT Assistant Id.") {
            // Override the $use_assistant_id and set it to 'No'
            $use_assistant_id = 'No';
            // error_log ('Falling back to ChatGPT API');
        }
    } else {
        // Reference Custom GPT Assistant IDs directly - Ver 1.7.3
        if (substr($chatbot_chatgpt_assistant_alias, 0, 5) === 'asst_') {
            // DIAG - Diagnostics
            // chatbot_chatgpt_back_trace( 'NOTICE', 'Using Custom GPT Assistant Id: ' . $chatbot_chatgpt_assistant_alias);
            // Override the $assistant_id with the Custom GPT Assistant Id
            $assistant_id = $chatbot_chatgpt_assistant_alias;
            $use_assistant_id = 'Yes';
            // error_log ('Using Custom GPT Assistant Id ' . $assistant_id);
        } else {
            // DIAG - Diagnostics
            // chatbot_chatgpt_back_trace( 'NOTICE', 'Using ChatGPT API: ' . $chatbot_chatgpt_assistant_alias);
            // Override the $use_assistant_id and set it to 'No'
            $use_assistant_id = 'No';
            // error_log ('Falling back to ChatGPT API');
        }
    }

    // Decide whether to use an Assistant or ChatGPT - Ver 1.6.7
    if ($use_assistant_id == 'Yes') {
        // DIAG - Diagnostics
        // chatbot_chatgpt_back_trace( 'NOTICE', 'Using Custom GPT Assistant Id: ' . $use_assistant_id);
        // DIAG - Diagnostics
        // chatbot_chatgpt_back_trace( 'NOTICE', '* * * chatbot-chatgpt.php * * *');
        // chatbot_chatgpt_back_trace( 'NOTICE', '$user_id ' . $user_id);
        // chatbot_chatgpt_back_trace( 'NOTICE', '$page_id ' . $page_id);
        // chatbot_chatgpt_back_trace( 'NOTICE', '* * * chatbot-chatgpt.php * * *');
        // Send message to Custom GPT API - Ver 1.6.7
        $response = chatbot_chatgpt_custom_gpt_call_api($api_key, $message, $assistant_id, $thread_Id, $user_id, $page_id);
        // Use TF-IDF to enhance response
        $response = $response . chatbot_chatgpt_enhance_with_tfidf($message);
        // DIAG - Diagnostics
        // chatbot_chatgpt_back_trace( 'NOTICE', ['message' => 'response', 'response' => $response]);
        // Clean (erase) the output buffer - Ver 1.6.8
        ob_clean();
        if (substr($response, 0, 6) === 'Error:' || substr($response, 0, 7) === 'Failed:') {
            // Return response
            wp_send_json_error('Oops! Something went wrong on our end. Please try again later');
        } else {
            // Return response
            wp_send_json_success($response);
        }
    } else {
        // DIAG - Diagnostics
        // chatbot_chatgpt_back_trace( 'NOTICE', 'Using ChatGPT API: ' . $use_assistant_id);
        // chatbot_chatgpt_back_trace( 'NOTICE', '$assistant_id: ' . $assistant_id);
        // Send message to ChatGPT API - Ver 1.6.7
        $response = chatbot_chatgpt_call_api($api_key, $message);
        // DIAG - Diagnostics
        // chatbot_chatgpt_back_trace( 'NOTICE', ['message' => 'BEFORE CALL TO ENHANCE TFIDF', 'response' => $response]);
        // Use TF-IDF to enhance response
        $response = $response . chatbot_chatgpt_enhance_with_tfidf($message);
        // DIAG - Diagnostics
        // chatbot_chatgpt_back_trace( 'NOTICE', ['message' => 'AFTER CALL TO ENHANCE TFIDF', 'response' => $response]);
        // Return response
        wp_send_json_success($response);
    }

    wp_send_json_error('Oops, I fell through the cracks!');

}

add_action('wp_ajax_chatbot_chatgpt_send_message', 'chatbot_chatgpt_send_message');
add_action('wp_ajax_nopriv_chatbot_chatgpt_send_message', 'chatbot_chatgpt_send_message');

// Settings and Deactivation - Ver 1.5.0
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'chatbot_chatgpt_plugin_action_links');

// Crawler aka Knowledge Navigator - Ver 1.6.1
function chatbot_chatgpt_kn_status_activation() {
    add_option('chatbot_chatgpt_kn_status', 'Never Run');
    // clear any old scheduled runs
    if (wp_next_scheduled('crawl_scheduled_event_hook')) {
        wp_clear_scheduled_hook('crawl_scheduled_event_hook');
    }
    // clear the 'knowledge_navigator_scan_hook' hook on plugin activation - Ver 1.6.3
    if (wp_next_scheduled('knowledge_navigator_scan_hook')) {
        wp_clear_scheduled_hook('knowledge_navigator_scan_hook'); // Clear scheduled runs
    }
}
register_activation_hook(__FILE__, 'chatbot_chatgpt_kn_status_activation');

// Clean Up in Aisle 4
function chatbot_chatgpt_kn_status_deactivation() {
    delete_option('chatbot_chatgpt_kn_status');
    wp_clear_scheduled_hook('knowledge_navigator_scan_hook'); 
}
register_deactivation_hook(__FILE__, 'chatbot_chatgpt_kn_status_deactivation');

// Function to add a new message and response, keeping only the last five - Ver 1.6.1
function addEntry($transient_name, $newEntry) {
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
function concatenateHistory($transient_name) {
    $context_history = get_transient($transient_name);
    if (!$context_history) {
        return ''; // Return an empty string if the transient does not exist
    }
    return implode(' ', $context_history); // Concatenate the array values into a single string
}

// Initialize the Greetings - Ver 1.6.1
function enqueue_greetings_script() {
    global $chatbot_chatgpt_diagnostics;

    // DIAG - Diagnostics - Ver 1.6.1
    // chatbot_chatgpt_back_trace( 'NOTICE', "enqueue_greetings_script() called");

    wp_enqueue_script('greetings', plugin_dir_url(__FILE__) . 'assets/js/greetings.js', array('jquery'), null, true);

    $greetings = array(
        'initial_greeting' => esc_attr(get_option('chatgpt_initial_greeting', 'Hello! How can I help you today?')),
        'subsequent_greeting' => esc_attr(get_option('chatgpt_subsequent_greeting', 'Hello again! How can I help you?')),
    );

    wp_localize_script('greetings', 'greetings_data', $greetings);

}
add_action('wp_enqueue_scripts', 'enqueue_greetings_script');
