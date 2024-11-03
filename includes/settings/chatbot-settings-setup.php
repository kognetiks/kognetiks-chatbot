<?php
/**
 * Kognetiks Chatbot for WordPress - Settings - Setup Page
 *
 * This file contains the code for the Chatbot settings page.
 * It handles the setup settings and other parameters.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// General settings section callback - Ver 2.0.2.1
function chatbot_chatgpt_bot_settings_section_callback($args) {

    ?>
    <p>Configure the general settings for the Chatbot plugin, including name of the chatbot, prompts and greetings, and general settings.</p>
    <p><b><i>Don't forget to click </i><code>Save Settings</code><i> to save any changes your might make.</i></b></p>
    <p style="background-color: #e0f7fa; padding: 10px;"><b>For an explanation of the general Settings and additional documentation please click <a href="?page=chatbot-chatgpt&tab=support&dir=settings&file=settings.md">here</a>.</b></p>
    <?php

}

// AI Engine Selection section callback - Ver 2.1.8
function chatbot_ai_engine_section_callback($args) {

    // DIAG - Diagnostics - Ver 2.1.8
    // back_trace( 'NOTICE', 'chatbot_ai_engine_section_callback');

    $chatbot_ai_engine_choice = esc_attr(get_option('chatbot_ai_engine_choice', 'OpenAI'));

    ?>
    <p>Configure the AI Engine for the Chatbot plugin. The default will be one of <?php echo $chatbot_ai_engine_choice ?>'s AI models; assumes you have or will provide a valid API key.</p>
    <?php

}

// AI Engine Choice - Ver 2.1.8
function chatbot_ai_engine_choice_callback($args) {

    $chatbot_ai_engine_choice = esc_attr(get_option('chatbot_ai_engine_choice', 'OpenAI'));

    if (empty($chatbot_ai_engine_choice) || $chatbot_ai_engine_choice == 'OpenAI') {
        $chatbot_ai_engine_choice = 'OpenAI';
        update_option('chatbot_ai_engine_choice', 'OpenAI');
        update_option('chatbot_chatgpt_api_enabled', 'Yes');
        update_option('chatbot_nvidia_api_enabled', 'No');
        update_option('chatbot_markov_chain_api_enabled', 'No');
        $chatbot_chatgpt_api_enabled = 'Yes';
        $chatbot_nvidia_api_enabled = 'No';
        $chatbot_markov_chain_api_enabled = 'No';
    } else if ($chatbot_ai_engine_choice == 'NVIDIA') {
        update_option('chatbot_ai_engine_choice', 'NVIDIA');
        update_option('chatbot_chatgpt_api_enabled', 'No');
        update_option('chatbot_nvidia_api_enabled', 'Yes');
        update_option('chatbot_markov_chain_api_enabled', 'No');
        $chatbot_nvidia_api_enabled = 'Yes';
        $chatbot_chatgpt_api_enabled = 'No';
        $chatbot_markov_chain_api_enabled = 'No';
    } else if ($chatbot_ai_engine_choice == 'Markov Chain') {
        update_option('chatbot_ai_engine_choice', 'Markov Chain');
        update_option('chatbot_chatgpt_api_enabled', 'No');
        update_option('chatbot_nvidia_api_enabled', 'No');
        update_option('chatbot_markov_chain_api_enabled', 'Yes');
        $chatbot_markov_chain_api_enabled = 'Yes';
        $chatbot_nvidia_api_enabled = 'No';
        $chatbot_chatgpt_api_enabled = 'No';
    } else {
        update_option('chatbot_ai_engine_choice', 'OpenAI');
        update_option('chatbot_chatgpt_api_enabled', 'Yes');
        update_option('chatbot_nvidia_api_enabled', 'No');
        update_option('chatbot_markov_chain_api_enabled', 'No');
        $chatbot_chatgpt_api_enabled = 'Yes';
        $chatbot_nvidia_api_enabled = 'No';
        $chatbot_markov_chain_api_enabled = 'No';
    }

    ?>
    <select id="chatbot_ai_engine_choice" name="chatbot_ai_engine_choice">
        <option value="OpenAI" <?php selected( $chatbot_ai_engine_choice, 'OpenAI' ); ?>><?php echo esc_html( 'OpenAI' ); ?></option>
        <option value="NVIDIA" <?php selected( $chatbot_ai_engine_choice, 'NVIDIA' ); ?>><?php echo esc_html( 'NVIDIA' ); ?></option>
        <option value="Markov Chain" <?php selected( $chatbot_ai_engine_choice, 'Markov Chain' ); ?>><?php echo esc_html( 'Markov Chain' ); ?></option>
    </select>
    <?php

    // if ($chatbot_ai_engine_choice == 'OpenAI') {
    //     echo '<p><b>OpenAI ChatGPT is the default AI Engine for the Chatbot plugin.</b></p>';
    // } elseif ($chatbot_ai_engine_choice == 'NVIDIA') {
    //     echo '<p><b>NVIDIA ChatGPT is the NVIDIA AI Engine for the Chatbot plugin.</b></p>';
    // } elseif ($chatbot_ai_engine_choice == 'Markov Chain') {
    //     echo '<p><b>Markov Chain is the Markov Chain AI Engine for the Chatbot plugin.</b></p>';
    // }

}

// Configure the chatbot's name and start status
function chatbot_chatgpt_name_section_callback($args) {

    // DIAG - Diagnostics - Ver 2.0.2.1
    // back_trace( 'NOTICE', 'chatbot_chatgpt_name_section_callback');

    ?>
    <p>Configure the name of the chatbot and start status for the Chatbot.</p>
    <?php

}

function chatbot_chatgpt_message_limits_section_callback($args) {
    ?>
    <p>Configure message limits for (logged-in/registered) Users and (guest/unregistered) Visitors access.</p>
    <p>The User Message Limit Settings applies to authenticed, registered and/or logged-in users. The Visitor Message Limit applies to unauthenteicated, general, non-logged-in visitors or guests. The default is 999.</p>
    <?php
}

// Greeting settings section callback - Ver 1.3.0
function chatbot_chatgpt_greetings_section_callback($args) {

    // DIAG - Diagnostics - Ver 2.0.2.1
    // back_trace( 'NOTICE', 'chatbot_chatgpt_greetings_section_callback');

    ?>
    <p>Configure the prompt and greetings for the Chatbot.</p>
    <?php

}

// Logged-in User Message Limit - Ver 1.9.6
function chatbot_chatgpt_user_message_limit_setting_callback($args) {
    // Get the saved chatbot_chatgpt_user_message_limit_setting value or default to 999
    $message_limit = esc_attr(get_option('chatbot_chatgpt_user_message_limit_setting', '999'));
    // Allow for a range of message limits between 1 and 999 in 1-step increments - Ver 1.9.6
    ?>
    <select id="chatbot_chatgpt_user_message_limit_setting" name="chatbot_chatgpt_user_message_limit_setting">
        <?php
        for ($i=1; $i<=999; $i++) {
            echo '<option value="' . esc_attr($i) . '" ' . selected($message_limit, (string)$i, false) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php
}

// Limit Period Setting - Ver 2.1.6
function chatbot_chatgpt_user_message_limit_period_setting_callback($args) {
    // Options: Hourly, Daily, Weekly, Monthly, Quarterly, Yearly, Lifetime
    $message_limit_period = esc_attr(get_option('chatbot_chatgpt_user_message_limit_period_setting', 'Lifetime'));
    ?>
    <select id="chatbot_chatgpt_user_message_limit_period_setting" name="chatbot_chatgpt_user_message_limit_period_setting">
        <option value="Hourly" <?php selected($message_limit_period, 'Hourly'); ?>>Hourly</option>
        <option value="Daily" <?php selected($message_limit_period, 'Daily'); ?>>Daily</option>
        <option value="Weekly" <?php selected($message_limit_period, 'Weekly'); ?>>Weekly</option>
        <option value="Monthly" <?php selected($message_limit_period, 'Monthly'); ?>>Monthly</option>
        <option value="Quarterly" <?php selected($message_limit_period, 'Quarterly'); ?>>Quarterly</option>
        <option value="Yearly" <?php selected($message_limit_period, 'Yearly'); ?>>Yearly</option>
        <option value="Lifetime" <?php selected($message_limit_period, 'Lifetime'); ?>>Lifetime</option>
    </select>
    <?php
}


// Visitor Message Limit - Ver 2.0.1
function chatbot_chatgpt_visitor_message_limit_setting_callback($args) {
    // Get the saved chatbot_chatgpt_visitor_message_limit_setting value or default to 999
    $visitor_message_limit = esc_attr(get_option('chatbot_chatgpt_visitor_message_limit_setting', '999'));
    // Allow for a range of visitor message limits between 1 and 999 in 1-step increments - Ver 2.0.1
    ?>
    <select id="chatbot_chatgpt_visitor_message_limit_setting" name="chatbot_chatgpt_visitor_message_limit_setting">
        <?php
        for ($i=1; $i<=999; $i++) {
            echo '<option value="' . esc_attr($i) . '" ' . selected($visitor_message_limit, (string)$i, false) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php
}

// Limit Period Setting - Ver 2.1.6
function chatbot_chatgpt_visitor_message_limit_period_setting_callback($args) {
    // Options: Hourly, Daily, Weekly, Monthly, Quarterly, Yearly, Lifetime
    $message_limit_period = esc_attr(get_option('chatbot_chatgpt_visitor_message_limit_period_setting', 'Lifetime'));
    ?>
    <select id="chatbot_chatgpt_visitor_message_limit_period_setting" name="chatbot_chatgpt_visitor_message_limit_period_setting">
        <option value="Hourly" <?php selected($message_limit_period, 'Hourly'); ?>>Hourly</option>
        <option value="Daily" <?php selected($message_limit_period, 'Daily'); ?>>Daily</option>
        <option value="Weekly" <?php selected($message_limit_period, 'Weekly'); ?>>Weekly</option>
        <option value="Monthly" <?php selected($message_limit_period, 'Monthly'); ?>>Monthly</option>
        <option value="Quarterly" <?php selected($message_limit_period, 'Quarterly'); ?>>Quarterly</option>
        <option value="Yearly" <?php selected($message_limit_period, 'Yearly'); ?>>Yearly</option>
        <option value="Lifetime" <?php selected($message_limit_period, 'Lifetime'); ?>>Lifetime</option>
    </select>
    <?php
}

// Additional settings section callback - Ver 1.3.0
function chatbot_chatgpt_additional_setup_section_callback($args) {

    // DIAG - Diagnostics - Ver 2.0.2.1
    // back_trace( 'NOTICE', 'chatbot_chatgpt_additional_setup_section_callback');

    ?>
    <p>Configure several additional settings for the Chatbot.</p>
    <?php

}

// Settings section callback - Ver 1.3.0
function chatbot_chatgpt_settings_section_callback($args) {
    ?>
    <p>Configure settings for the Chatbot plugin, including the bot name, start status, and greetings.</p>
    <?php
}

// Chatbot Name
function chatbot_chatgpt_bot_name_callback($args) {
    $bot_name = esc_attr(get_option('chatbot_chatgpt_bot_name', 'Kognetiks Chatbot'));
    ?>
    <input type="text" id="chatbot_chatgpt_bot_name" name="chatbot_chatgpt_bot_name" value="<?php echo esc_attr( $bot_name ); ?>" class="regular-text">
    <?php
}

function chatbot_chatgpt_start_status_callback($args) {
    $start_status = esc_attr(get_option('chatbot_chatgpt_start_status', 'closed'));
    ?>
    <select id="chatbot_chatgpt_start_status" name="chatbot_chatgpt_start_status">
        <option value="open" <?php selected( $start_status, 'open' ); ?>><?php echo esc_html( 'Open' ); ?></option>
        <option value="closed" <?php selected( $start_status, 'closed' ); ?>><?php echo esc_html( 'Closed' ); ?></option>
    </select>
    <?php
}

function chatbot_chatgpt_start_status_new_visitor_callback($args) {
    $start_status = esc_attr(get_option('chatbot_chatgpt_start_status_new_visitor', 'closed'));
    ?>
    <select id="chatbot_chatgpt_start_status_new_visitor" name="chatbot_chatgpt_start_status_new_visitor">
        <option value="open" <?php selected( $start_status, 'open' ); ?>><?php echo esc_html( 'Open' ); ?></option>
        <option value="closed" <?php selected( $start_status, 'closed' ); ?>><?php echo esc_html( 'Closed' ); ?></option>
    </select>
    <?php
}

// Added in Ver 1.6.6
function chatbot_chatgpt_bot_prompt_callback($args) {
    $chatbot_chatgpt_bot_prompt = esc_attr(get_option('chatbot_chatgpt_bot_prompt', 'Enter your question ...'));
    ?>
    <input type="text" id="chatbot_chatgpt_bot_prompt" name="chatbot_chatgpt_bot_prompt" value="<?php echo esc_attr( $chatbot_chatgpt_bot_prompt ); ?>" class="regular-text" required>
    <?php
}

function chatbot_chatgpt_initial_greeting_callback($args) {
    $initial_greeting = esc_attr(get_option('chatbot_chatgpt_initial_greeting', 'Hello! How can I help you today?'));
    ?>
    <textarea id="chatbot_chatgpt_initial_greeting" name="chatbot_chatgpt_initial_greeting" rows="2" cols="50"><?php echo esc_textarea( $initial_greeting ); ?></textarea>
    <?php
}

function chatbot_chatgpt_subsequent_greeting_callback($args) {
    $subsequent_greeting = esc_attr(get_option('chatbot_chatgpt_subsequent_greeting', 'Hello again! How can I help you?'));
    ?>
    <textarea id="chatbot_chatgpt_subsequent_greeting" name="chatbot_chatgpt_subsequent_greeting" rows="2" cols="50"><?php echo esc_textarea( $subsequent_greeting ); ?></textarea>
    <?php
}

// Option to allow downloading transcripts - Ver 2.0.3
function chatbot_chatgpt_allow_download_transcript_callback($args) {
    $chatbot_chatgpt_allow_download_transcript = esc_attr(get_option('chatbot_chatgpt_allow_download_transcript', 'Yes'));
    ?>
    <select id="chatbot_chatgpt_allow_download_transcript" name="chatbot_chatgpt_allow_download_transcript">
        <option value="Yes" <?php selected( $chatbot_chatgpt_allow_download_transcript, 'Yes' ); ?>><?php echo esc_html( 'Yes' ); ?></option>
        <option value="No" <?php selected( $chatbot_chatgpt_allow_download_transcript, 'No' ); ?>><?php echo esc_html( 'No' ); ?></option>
    </select>
    <?php    
}

// Option to force page reload on conversation cleared - Ver 2.0.4
function chatbot_chatgpt_force_page_reload_callback($args) {
    $chatbot_chatgpt_force_page_reload = esc_attr(get_option('chatbot_chatgpt_force_page_reload', 'No'));
    ?>
    <select id="chatbot_chatgpt_force_page_reload" name="chatbot_chatgpt_force_page_reload">
        <option value="Yes" <?php selected( $chatbot_chatgpt_force_page_reload, 'Yes' ); ?>><?php echo esc_html( 'Yes' ); ?></option>
        <option value="No" <?php selected( $chatbot_chatgpt_force_page_reload, 'No' ); ?>><?php echo esc_html( 'No' ); ?></option>
    </select>
    <?php    
}

// Conversation Continuation - Ver 2.0.7
function chatbot_chatgpt_conversation_continuation_callback($args) {
    $chatbot_chatgpt_conversation_continuation = esc_attr(get_option('chatbot_chatgpt_conversation_continuation', 'Off'));
    ?>
    <select id="chatbot_chatgpt_conversation_continuation" name="chatbot_chatgpt_conversation_continuation">
        <option value="On" <?php selected( $chatbot_chatgpt_conversation_continuation, 'On' ); ?>><?php echo esc_html( 'On' ); ?></option>
        <option value="Off" <?php selected( $chatbot_chatgpt_conversation_continuation, 'Off' ); ?>><?php echo esc_html( 'Off' ); ?></option>
    </select>
    <?php    
}

// Option to remove OpenAI disclaimer - Ver 1.4.1
function chatbot_chatgpt_disclaimer_setting_callback($args) {
    $chatbot_chatgpt_disclaimer_setting = esc_attr(get_option('chatbot_chatgpt_disclaimer_setting', 'Yes'));
    ?>
    <select id="chatbot_chatgpt_disclaimer_setting" name="chatbot_chatgpt_disclaimer_setting">
        <option value="Yes" <?php selected( $chatbot_chatgpt_disclaimer_setting, 'Yes' ); ?>><?php echo esc_html( 'Yes' ); ?></option>
        <option value="No" <?php selected( $chatbot_chatgpt_disclaimer_setting, 'No' ); ?>><?php echo esc_html( 'No' ); ?></option>
    </select>
    <?php    
}

// Audience Choice - Ver 1.9.0
function chatbot_chatgpt_audience_choice_callback($args) {
    $chatbot_chatgpt_audience_choice = esc_attr(get_option('chatbot_chatgpt_audience_choice', 'all'));
    ?>
    <select id="chatbot_chatgpt_audience_choice" name="chatbot_chatgpt_audience_choice">
        <option value="all" <?php selected( $chatbot_chatgpt_audience_choice, 'all' ); ?>><?php echo esc_html( 'All Audiences' ); ?></option>
        <option value="logged-in" <?php selected( $chatbot_chatgpt_audience_choice, 'logged-in' ); ?>><?php echo esc_html( 'Logged-in Only' ); ?></option>
        <option value="visitors" <?php selected( $chatbot_chatgpt_audience_choice, 'visitors' ); ?>><?php echo esc_html( 'Visitors Only' ); ?></option>
    </select>
    <?php
}

// Input Rows - Ver 1.9.9
function chatbot_chatgpt_input_rows_callback($args) {
    $chatbot_chatgpt_input_rows = esc_attr(get_option('chatbot_chatgpt_input_rows', '2'));
    // A picklist with a number between 1 and 10
    ?>
    <select id="chatbot_chatgpt_input_rows" name="chatbot_chatgpt_input_rows">
        <?php
        for ($i = 1; $i <= 10; $i++) {
            echo '<option value="' . $i . '" ' . selected( $chatbot_chatgpt_input_rows, $i ) . '>' . $i . '</option>';
        }
        ?>
    <?php
}

// Speech Recognition - Ver 2.1.5.1
function chatbot_chatgpt_speech_recognition_callback($args) {
    $chatbot_chatgpt_speech_recognition = esc_attr(get_option('chatbot_chatgpt_speech_recognition', 'No'));
    ?>
    <select id="chatbot_chatgpt_speech_recognition" name="chatbot_chatgpt_speech_recognition">
        <option value="Yes" <?php selected( $chatbot_chatgpt_speech_recognition, 'Yes' ); ?>><?php echo esc_html( 'Yes' ); ?></option>
        <option value="No" <?php selected( $chatbot_chatgpt_speech_recognition, 'No' ); ?>><?php echo esc_html( 'No' ); ?></option>
    </select>
    <?php    
}


// Register the settings
function chatbot_chatgpt_settings_setup_init() {

    add_settings_section(
        'chatbot_chatgpt_bot_settings_section',
        'General Settings',
        'chatbot_chatgpt_bot_settings_section_callback',
        'chatbot_chatgpt_bot_settings_general'
    );

    // Settings settings tab - Ver 1.3.0
    register_setting('chatbot_chatgpt_settings', 'chatbot_chatgpt_bot_name');
    register_setting('chatbot_chatgpt_settings', 'chatbot_chatgpt_start_status');
    register_setting('chatbot_chatgpt_settings', 'chatbot_chatgpt_start_status_new_visitor');
    register_setting('chatbot_chatgpt_settings', 'chatbot_chatgpt_bot_prompt');
    register_setting('chatbot_chatgpt_settings', 'chatbot_chatgpt_initial_greeting');
    register_setting('chatbot_chatgpt_settings', 'chatbot_chatgpt_subsequent_greeting');
    register_setting('chatbot_chatgpt_settings', 'chatbot_chatgpt_allow_download_transcript');
    register_setting('chatbot_chatgpt_settings', 'chatbot_chatgpt_force_page_reload');
    register_setting('chatbot_chatgpt_settings', 'chatbot_chatgpt_conversation_continuation');
    register_setting('chatbot_chatgpt_settings', 'chatbot_chatgpt_disclaimer_setting');
    register_setting('chatbot_chatgpt_settings', 'chatbot_chatgpt_audience_choice');
    register_setting('chatbot_chatgpt_settings', 'chatbot_chatgpt_input_rows');
    register_setting('chatbot_chatgpt_settings', 'chatbot_chatgpt_speech_recognition');
    register_setting('chatbot_chatgpt_settings', 'chatbot_chatgpt_user_message_limit_setting');
    register_setting('chatbot_chatgpt_settings', 'chatbot_chatgpt_user_message_limit_period_setting');
    register_setting('chatbot_chatgpt_settings', 'chatbot_chatgpt_visitor_message_limit_setting');
    register_setting('chatbot_chatgpt_settings', 'chatbot_chatgpt_visitor_message_limit_period_setting');

    register_setting('chatbot_chatgpt_settings', 'chatbot_ai_engine_choice');

    // Chatbot Settings - AI Engine Selection
    add_settings_section(
        'chatbot_ai_engine_section',
        'AI Engine Selection',
        'chatbot_ai_engine_section_callback',
        'chatbot_ai_engine_settings'
    );

    add_settings_field(
        'chatbot_ai_engine_choice',
        'AI Engine Choice',
        'chatbot_ai_engine_choice_callback',
        'chatbot_ai_engine_settings',
        'chatbot_ai_engine_section'
    );

    // Chatbot Settings - Chatbot Name, Start Status, Start Status New Visitor
    add_settings_section(
        'chatbot_chatgpt_name_section',
        'Chatbot Settings',
        'chatbot_chatgpt_name_section_callback',
        'chatbot_chatgpt_name_settings'
    );

    add_settings_field(
        'chatbot_chatgpt_bot_name',
        'Chatbot Name',
        'chatbot_chatgpt_bot_name_callback',
        'chatbot_chatgpt_name_settings',
        'chatbot_chatgpt_name_section'
    );

    add_settings_field(
        'chatbot_chatgpt_start_status',
        'Start Status',
        'chatbot_chatgpt_start_status_callback',
        'chatbot_chatgpt_name_settings',
        'chatbot_chatgpt_name_section'
    );

    add_settings_field(
        'chatbot_chatgpt_start_status_new_visitor',
        'Start Status New Visitor',
        'chatbot_chatgpt_start_status_new_visitor_callback',
        'chatbot_chatgpt_name_settings',
        'chatbot_chatgpt_name_section'
    );

    add_settings_section(
        'chatbot_chatgpt_message_limits_section',
        'Message Limit Settings',
        'chatbot_chatgpt_message_limits_section_callback',
        'chatbot_chatgpt_message_limits_settings'
    );

    add_settings_field(
        'chatbot_chatgpt_user_message_limit_setting',
        'User Message Limit per Period',
        'chatbot_chatgpt_user_message_limit_setting_callback',
        'chatbot_chatgpt_message_limits_settings',
        'chatbot_chatgpt_message_limits_section'
    );

    add_settings_field(
        'chatbot_chatgpt_user_message_limit_period_setting',
        'User Message Limit Period',
        'chatbot_chatgpt_user_message_limit_period_setting_callback',
        'chatbot_chatgpt_message_limits_settings',
        'chatbot_chatgpt_message_limits_section'
    );

    add_settings_field(
        'chatbot_chatgpt_visitor_message_limit_setting',
        'Visitor Message Limit per Period',
        'chatbot_chatgpt_visitor_message_limit_setting_callback',
        'chatbot_chatgpt_message_limits_settings',
        'chatbot_chatgpt_message_limits_section'
    );

    add_settings_field(
        'chatbot_chatgpt_visitor_message_limit_period_setting',
        'Visitor Message Limit Period',
        'chatbot_chatgpt_visitor_message_limit_period_setting_callback',
        'chatbot_chatgpt_message_limits_settings',
        'chatbot_chatgpt_message_limits_section'
    );

    // Chatbot Prompts and Greetings: Chatbot Prompt, Initial Greeting, Subsequent Greeting
    add_settings_section(
        'chatbot_chatgpt_greetings_section',
        'Prompts and Greetings',
        'chatbot_chatgpt_greetings_section_callback',
        'chatbot_chatgpt_greetings_settings'
    );

    add_settings_field(
        'chatbot_chatgpt_bot_prompt',
        'Chatbot Prompt',
        'chatbot_chatgpt_bot_prompt_callback',
        'chatbot_chatgpt_greetings_settings',
        'chatbot_chatgpt_greetings_section'
    );

    add_settings_field(
        'chatbot_chatgpt_initial_greeting',
        'Initial Greeting',
        'chatbot_chatgpt_initial_greeting_callback',
        'chatbot_chatgpt_greetings_settings',
        'chatbot_chatgpt_greetings_section'
    );

    add_settings_field(
        'chatbot_chatgpt_subsequent_greeting',
        'Subsequent Greeting',
        'chatbot_chatgpt_subsequent_greeting_callback',
        'chatbot_chatgpt_greetings_settings',
        'chatbot_chatgpt_greetings_section'
    );

    // Additional Settings: Disclaimer, Audience, Input Rows
    add_settings_section(
        'chatbot_chatgpt_additional_setup_section',
        'Additional Settings',
        'chatbot_chatgpt_additional_setup_section_callback',
        'chatbot_chatgpt_additional_setup_settings'
    );

    // Speech Recognition - Ver 2.1.5.1
    add_settings_field(
        'chatbot_chatgpt_speech_recognition',
        'Allow Speech Recognition',
        'chatbot_chatgpt_speech_recognition_callback',
        'chatbot_chatgpt_additional_setup_settings',
        'chatbot_chatgpt_additional_setup_section'
    );

    // Option to allow downloading transcripts - Ver 2.0.3
    add_settings_field(
        'chatbot_chatgpt_allow_download_transcript',
        'Allow Downloading Transcripts',
        'chatbot_chatgpt_allow_download_transcript_callback',
        'chatbot_chatgpt_additional_setup_settings',
        'chatbot_chatgpt_additional_setup_section'
    );

    // Option to force page reload on conversation cleared - Ver 2.0.3
    add_settings_field(
        'chatbot_chatgpt_force_page_reload',
        'Force Page Reload on Conversation Cleared',
        'chatbot_chatgpt_force_page_reload_callback',
        'chatbot_chatgpt_additional_setup_settings',
        'chatbot_chatgpt_additional_setup_section'
    );

    // Conversation Continuation - Ver 2.0.7
    add_settings_field(
        'chatbot_chatgpt_conversation_continuation',
        'Conversation Continuation',
        'chatbot_chatgpt_conversation_continuation_callback',
        'chatbot_chatgpt_additional_setup_settings',
        'chatbot_chatgpt_additional_setup_section'
    );

    // Option to remove the OpenAI disclaimer - Ver 1.4.1
    add_settings_field(
        'chatbot_chatgpt_disclaimer_setting',
        'Include "As an AI language model" disclaimer',
        'chatbot_chatgpt_disclaimer_setting_callback',
        'chatbot_chatgpt_additional_setup_settings',
        'chatbot_chatgpt_additional_setup_section'
    );

    // Audience setting - Ver 1.9.0
    add_settings_field(
        'chatbot_chatgpt_audience_choice',
        'Audience for Chatbot',
        'chatbot_chatgpt_audience_choice_callback',
        'chatbot_chatgpt_additional_setup_settings',
        'chatbot_chatgpt_additional_setup_section'
    );

    // Input rows setting - Ver 1.9.9
    add_settings_field(
        'chatbot_chatgpt_input_rows',
        'Input Rows',
        'chatbot_chatgpt_input_rows_callback',
        'chatbot_chatgpt_additional_setup_settings',
        'chatbot_chatgpt_additional_setup_section'
    );

}
add_action('admin_init', 'chatbot_chatgpt_settings_setup_init');
