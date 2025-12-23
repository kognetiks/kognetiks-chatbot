<?php
/**
 * Kognetiks Chatbot - Settings - Mistral Agents - Ver 2.3.0
 *
 * This file contains the code for the Chatbot settings page.
 * It allows users to configure the API key and other parameters
 * required to access the ChatGPT API from their own account.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Register Agent settings - Ver 2.0.2.1
function chatbot_mistral_agent_settings_init() {

    // chatbot_mistral_agent_settings
    add_settings_section(
        'chatbot_mistral_agent_settings_section',
        'Agent Settings',
        'chatbot_mistral_agent_settings_section_callback',
        'chatbot_mistral_agent_settings'
    );

    // Settings Custom GPTs tab - Ver 1.7.2
    // register_setting('chatbot_mistral_agents', 'chatbot_mistral_use_agents_assistant_id'); // Ver 1.6.7 - REMOVED in Ver 2.0.5
    register_setting('chatbot_mistral_agents', 'chatbot_mistral_allow_file_uploads'); // Ver 1.7.6
    register_setting('chatbot_mistral_agents', 'chatbot_mistral_display_agents_assistant_name'); // Ver 1.9.4
    register_setting('chatbot_mistral_agents', 'chatbot_mistral_conversation_transcript_email'); // Ver 2.2.7
    register_setting('chatbot_mistral_agents', 'assistant_id'); // Ver 1.6.7
    register_setting('chatbot_mistral_agents', 'chatbot_mistral_agent_instructions'); // Ver 1.9.3
    register_setting('chatbot_mistral_agents', 'chatbot_mistral_agent_id_alternate'); // Alternate Agent - Ver 1.7.2
    register_setting('chatbot_mistral_agents', 'chatbot_mistral_agent_instructions_alternate'); // Alternate Agent - Ver 1.9.3
    register_setting('chatbot_mistral_agents', 'chatbot_mistral_agent_beta_version'); // Beta Agent - Ver 1.9.3
    register_setting('chatbot_mistral_agents', 'chatbot_mistral_thread_retention_period'); // Thread Retention Period - Ver 1.9.9
    register_setting('chatbot_mistral_agents', 'chatbot_mistral_max_prompt_tokens'); // Max Prompt Tokens - Ver 2.0.1
    register_setting('chatbot_mistral_agents', 'chatbot_mistral_max_completion_tokens'); // Max Response Tokens - Ver 2.0.1
    register_setting('chatbot_mistral_agents', 'chatbot_mistral_enable_remote_widget'); // Enable Remote Widget - Ver 2.1.3
    register_setting('chatbot_mistral_agents', 'chatbot_mistral_allowed_remote_domains'); // Allowed Remote Domains - Ver 2.1.3
    register_setting('chatbot_mistral_agents', 'chatbot_widget_logging'); // Widget Logging - Ver 2.1.3

    // General Settings for Agents
    add_settings_section(
        'chatbot_mistral_agents_section',
        'Agent General Settings',
        'chatbot_mistral_agents_section_callback',
        'chatbot_mistral_agents_settings'
    );
    
    // Use Agent Id (Yes or No) - Ver 1.6.7 - REMOVED in Ver 2.0.5
    // add_settings_field(
    //     'chatbot_mistral_use_agents_assistant_id',
    //     'Use Agent Id',
    //     'chatbot_mistral_use_agents_id_callback',
    //     'chatbot_mistral_agents_settings',
    //     'chatbot_mistral_agents_section'
    // );

    // Allow file uploads to the Agent - Ver 1.7.6
    add_settings_field(
        'chatbot_mistral_allow_file_uploads',
        'Allow File Uploads',
        'chatbot_mistral_allow_file_uploads_callback',
        'chatbot_mistral_agents_settings',
        'chatbot_mistral_agents_section'
    );

    // Display Custom GPT Agent Name - Ver 1.9.4
    add_settings_field(
        'chatbot_mistral_display_agents_assistant_name',
        'Display Agent Name',
        'chatbot_mistral_use_agents_name_callback',
        'chatbot_mistral_agents_settings',
        'chatbot_mistral_agents_section'
    );

    // Conversation Transcript Email - Ver 2.2.7
    add_settings_field(
        'chatbot_mistral_conversation_transcript_email',
        'Transcript Email',
        'chatbot_mistral_conversation_transcript_email_callback',
        'chatbot_mistral_agents_settings',
        'chatbot_mistral_agents_section'
    );

    // Agent Id Settings
    add_settings_section(
        'chatbot_mistral_agent_ids_section',
        'Agent IDs and Additional Instructions',
        'chatbot_mistral_agent_id_section_callback',
        'chatbot_mistral_agent_id_settings'
    );

    // CustomAgent Id - Ver 1.6.7
    add_settings_field(
        'assistant_id',
        'Primary Agent Id',
        'chatbot_mistral_agent_id_callback',
        'chatbot_mistral_agent_id_settings',
        'chatbot_mistral_agent_ids_section'
    );

    add_settings_field(
        'chatbot_mistral_agent_instructions',
        'Agent Instructions',
        'chatbot_mistral_agent_instructions_callback',
        'chatbot_mistral_agent_id_settings',
        'chatbot_mistral_agent_ids_section'
    );

    // CustomAgent Id Alternate - Ver 1.7.2
    add_settings_field(
        'chatbot_mistral_agent_id_alternate',
        'Alternate Agent Id',
        'chatbot_mistral_agent_id_alternate_callback',
        'chatbot_mistral_agent_id_settings',
        'chatbot_mistral_agent_ids_section'
    );

    add_settings_field(
        'chatbot_mistral_agent_instructions_alternate',
        'Alternate Agent Instructions',
        'chatbot_mistral_agent_instructions_alternate_callback',
        'chatbot_mistral_agent_id_settings',
        'chatbot_mistral_agent_ids_section'
    );

    // Advanced Additional Settings
    add_settings_section(
        'chatbot_mistral_agent_advanced_settings_section',
        'Advanced Additional Settings',
        'chatbot_mistral_agent_advanced_settings_section_callback',
        'chatbot_mistral_additional_assistant_settings'
    );

    // Max Prompt Tokens - Ver 2.0.1
    add_settings_field(
        'chatbot_mistral_max_prompt_tokens',
        'Max Prompt Tokens',
        'chatbot_mistral_max_prompt_tokens_callback',
        'chatbot_mistral_additional_assistant_settings',
        'chatbot_mistral_agent_advanced_settings_section'
    );

    // Max Response Tokens - Ver 2.0.1
    add_settings_field(
        'chatbot_mistral_max_completion_tokens',
        'Max Response Tokens',
        'chatbot_mistral_max_completion_tokens_callback',
        'chatbot_mistral_additional_assistant_settings',
        'chatbot_mistral_agent_advanced_settings_section'
    );

    // Thread Retention Period - Ver 1.9.9
    add_settings_field(
        'chatbot_mistral_thread_retention_period',
        'Thread Retention Period (hrs)',
        'chatbot_mistral_thread_retention_period_callback',
        'chatbot_mistral_additional_assistant_settings',
        'chatbot_mistral_agent_advanced_settings_section'
    );

    add_settings_field(
        'chatbot_mistral_agent_beta_version',
        'Beta Agent Version',
        'chatbot_mistral_agent_beta_version_callback',
        'chatbot_mistral_additional_assistant_settings',
        'chatbot_mistral_agent_advanced_settings_section'
    );

    // Remote Widget Settings - Ver 2.1.3
    add_settings_section(
        'chatbot_mistral_remote_widget_settings_section',
        'Remote Widget Settings',
        'chatbot_mistral_remote_widget_settings_section_callback',
        'chatbot_mistral_remote_widget_settings'
    );

    // Max Response Tokens - Ver 2.1.3
    add_settings_field(
        'chatbot_mistral_enable_remote_widget',
        'Enable Remote Widget',
        'chatbot_mistral_enable_remote_widget_callback',
        'chatbot_mistral_remote_widget_settings',
        'chatbot_mistral_remote_widget_settings_section'
    );

    // Max Prompt Tokens - Ver 2.1.3
    add_settings_field(
        'chatbot_mistral_allowed_remote_domains',
        'Allowed Remote Domains',
        'chatbot_mistral_allowed_remote_domains_callback',
        'chatbot_mistral_remote_widget_settings',
        'chatbot_mistral_remote_widget_settings_section'
    );

    // Widget Logging - Ver 2.1.3
    add_settings_field(
        'chatbot_widget_logging',
        'Widget Logging',
        'chatbot_widget_logging_callback',
        'chatbot_mistral_remote_widget_settings',
        'chatbot_mistral_remote_widget_settings_section'
    );
    
}
add_action('admin_init', 'chatbot_mistral_agent_settings_init');

// GPT Agents settings section callback - Ver 1.7.2 - Updated Ver 2.0.4
function chatbot_mistral_agent_settings_section_callback($args) {
    ?>
    <p>Effortlessly manage your chatbot Agents all in one place using the intuitive interface below.</p>
    <p>You will no longer need to remember all the Agent options, as they are all available here for you to view and edit.</p>
    <p>Tailor each Agent to meet the unique needs of your audience, ensuring an engaging and personalized experience for all.</p>
    <p>If you have developed an Agent in the Mistral Playground, you will need the id of the agent - it usually starts with "ag:".</p>
    <p>More information can be found here <a href="https://console.mistral.ai/build/agents" target="_blank">https://console.mistral.ai/build/agents</a>.</p>
    <p>When you're ready to use an Agent, simply add a shortcode such as <code>[agent-1]</code>, <code>[agent-2]</code>, etc. to your page.</p>
    <p><b>TIP:</b> To deploy a search assistant, enter the assistant id as `websearch`.</p>
    <p><b>TIP:</b> For best results ensure that the shortcode appears only once on the page.</p>
    <p><b>TIP:</b> When using the 'embedded' style, it's best to put the shortcode in a page or post, not in a footer.</b></p>
    <p><b><i>Don't forget to click </i><code>Save Settings</code><i> to save any changes your might make.</i></b></p>
    <p style="background-color: #e0f7fa; padding: 10px;"><b>For an explanation Agent Settings and additional documentation please click <a href="?page=chatbot-chatgpt&tab=support&dir=assistants&file=manage-assistants.md">here</a>.</b></p>
    <?php
}

// General settings section callback - Ver 2.0.2.1
function chatbot_mistral_agents_section_callback($args) {
    ?>
    <p>Configure the Chatbot to allow file uploads and display the Agent's name.</p>
    <p style="background-color: #e0f7fa; padding: 10px;"><b>For an explanation of the Agents General Settings and additional documentation please click <a href="?page=chatbot-chatgpt&tab=support&dir=assistants&file=assistants.md">here</a>.</b></p>
    <?php
}

// Agent Id section callback - Ver 1.7.2
function chatbot_mistral_agent_id_section_callback($args) {
    ?>
    <p>Configure a Primary and Alternate Agent by entering the ID and any additional instructions.</p>
    <p style="background-color: #e0f7fa; padding: 10px;"><b>For an explanation of the general Settings and additional documentation please click <a href="?page=chatbot-chatgpt&tab=support&dir=assistants&file=assistants.md">here</a>.</b></p>
    <?php
}

// GPT Agent Instructions section callback - Ver 1.9.3
function chatbot_mistral_agent_advanced_settings_section_callback($args) {
    ?>
    <p>Configure the Advanced Settings for Agents prompt and response tokens, thread retention periods, and the Beta version setting.</p>
    <p style="background-color: #e0f7fa; padding: 10px;"><b>For an explanation of the Advanced Settings and additional documentation please click <a href="?page=chatbot-chatgpt&tab=support&dir=assistants&file=assistants.md">here</a>.</b></p>
    <?php
}

// Use Agent Id field callback - Ver 1.6.7
function chatbot_mistral_use_agents_id_callback($args) {
    $use_assistant_id = esc_attr(get_option('chatbot_mistral_use_agents_assistant_id', 'No'));
    ?>
    <select id="chatbot_mistral_use_agents_assistant_id" name="chatbot_mistral_use_agents_assistant_id">
        <option value="Yes" <?php selected( $use_assistant_id, 'Yes' ); ?>><?php echo esc_html( 'Yes' ); ?></option>
        <option value="No" <?php selected( $use_assistant_id, 'No' ); ?>><?php echo esc_html( 'No' ); ?></option>
    </select>
    <?php
    if ($use_assistant_id == 'No') {
        update_option('assistant_id', '');
        update_option('chatbot_mistral_agent_id_alternate', '');
    }
}

// Allow file uploads field callback - Ver 1.7.6
function chatbot_mistral_allow_file_uploads_callback($args) {
    $allow_file_uploads = esc_attr(get_option('chatbot_mistral_allow_file_uploads', 'No'));
    ?>
    <select id="chatbot_mistral_allow_file_uploads" name="chatbot_mistral_allow_file_uploads">
        <option value="Yes" <?php selected( $allow_file_uploads, 'Yes' ); ?>><?php echo esc_html( 'Yes' ); ?></option>
        <option value="No" <?php selected( $allow_file_uploads, 'No' ); ?>><?php echo esc_html( 'No' ); ?></option>
    </select>
    <?php
}

// Agent Id field callback - Ver 1.6.7
function chatbot_mistral_agent_id_callback($args) {
    $assistant_id = esc_attr(get_option('assistant_id', 'Please provide the Agent Id.'));
    $use_assistant_id = esc_attr(get_option('chatbot_mistral_use_agents_assistant_id', 'No'));
    if ($use_assistant_id == 'Yes' && ($assistant_id == 'Please provide the Agent Id.' or empty($assistant_id))) {
        $assistant_id = 'Please provide the Agent Id.';
    }
    // Set default value if empty
    // $assistant_id = empty($assistant_id) ? 'Please provide the Agent Id.': $assistant_id;
    ?>
    <input type="text" id="chatbot_mistral_agent_id" name="chatbot_mistral_agent_id" value="<?php echo esc_attr( $assistant_id ); ?>" class="regular-text">
    <?php
}

// Agent Id field callback - Ver 1.6.7
function chatbot_mistral_agent_id_alternate_callback($args) {
    $assistant_id_alternate = esc_attr(get_option('chatbot_mistral_agent_id_alternate', 'Please provide the Alternate Agent Id.'));
    $use_assistant_id = esc_attr(get_option('chatbot_mistral_use_agents_assistant_id', 'No'));
    if ($use_assistant_id == 'Yes' && ($assistant_id_alternate == 'Please provide the Agent Id.' or empty($assistant_id_alternate))) {
        $assistant_id_alternate = 'Please provide the Alternate Agent Id, if any.';
    }
    // Set default value if empty
    // $assistant_id = empty($assistant_id) ? 'Please provide the Agent Id.': $assistant_id;
    ?>
    <input type="text" id="chatbot_mistral_agent_id_alternate" name="chatbot_mistral_agent_id_alternate" value="<?php echo esc_attr( $assistant_id_alternate ); ?>" class="regular-text">
    <?php
}

// GPT Agent Instructions field callback - Ver 1.9.3
function chatbot_mistral_agent_instructions_callback ($args) {
    $chatbot_mistral_agent_instructions = esc_attr(get_option('chatbot_mistral_agent_instructions', ''));
    ?>
    <textarea id="chatbot_mistral_agent_instructions" name="chatbot_mistral_agent_instructions" placeholder="Added instructions to assistant if needed ...." rows="5" cols="50"><?php echo esc_attr( $chatbot_mistral_agent_instructions ); ?></textarea>
    <?php
}

// GPT Agent Instructions Alternate field callback - Ver 1.9.3
function chatbot_mistral_agent_instructions_alternate_callback ($args) {
    $chatbot_mistral_agent_instructions_alternate = esc_attr(get_option('chatbot_mistral_agent_instructions_alternate', ''));
    ?>
    <textarea id="chatbot_mistral_agent_instructions_alternate" name="chatbot_mistral_agent_instructions_alternate" placeholder="Added instructions to assistant if needed ...." rows="5" cols="50"><?php echo esc_attr( $chatbot_mistral_agent_instructions_alternate ); ?></textarea>
    <?php
}

// Use GPT Agent Names field callback - Ver 1.9.4
function chatbot_mistral_use_agents_name_callback($args) {
    $use_assistant_name = esc_attr(get_option('chatbot_mistral_display_agents_assistant_name', 'Yes'));
    ?>
    <select id="chatbot_mistral_display_agents_assistant_name" name="chatbot_mistral_display_agents_assistant_name">
        <option value="Yes" <?php selected( $use_assistant_name, 'Yes' ); ?>><?php echo esc_html( 'Yes' ); ?></option>
        <option value="No" <?php selected( $use_assistant_name, 'No' ); ?>><?php echo esc_html( 'No' ); ?></option>
    </select>
    <?php
}

// Conversation Transcript Email field callback - Ver 2.2.7
function chatbot_mistral_conversation_transcript_email_callback($args) {
    $transcript_email = esc_attr(get_option('chatbot_mistral_conversation_transcript_email', ''));
    ?>
    <input type="email" id="chatbot_mistral_conversation_transcript_email" name="chatbot_mistral_conversation_transcript_email" value="<?php echo esc_attr( $transcript_email ); ?>" class="regular-text" placeholder="Enter email address for conversation transcripts">
    <p class="description">Email address where conversation transcripts will be sent when an assistant response contains the string "[conversation_transcript]".</p>
    <?php
}

// Set Agent Beta Version - Ver 1.9.6
function chatbot_mistral_agent_beta_version_callback($args) {
    $assistant_beta_version = esc_attr(get_option('chatbot_mistral_agent_beta_version', 'v2'));
    ?>
    <select id="chatbot_mistral_agent_beta_version" name="chatbot_mistral_agent_beta_version">
        <option value="v1" <?php selected( $assistant_beta_version, 'v1' ); ?>><?php echo esc_html( 'v1' ); ?></option>
        <option value="v2" <?php selected( $assistant_beta_version, 'v2' ); ?>><?php echo esc_html( 'v2' ); ?></option>
    </select>
    <?php
}

// Set chatbot_mistral_thread_retention_period - Ver 1.9.9
function chatbot_mistral_thread_retention_period_callback($args) {
    $chatbot_mistral_thread_retention_period = esc_attr(get_option('chatbot_mistral_thread_retention_period', 36));
    ?>
    <select id="chatbot_mistral_thread_retention_period" name="chatbot_mistral_thread_retention_period">
        <?php
        for ($i = 6; $i <= 720; $i += 6) {
            echo '<option value="' . $i . '" ' . selected($chatbot_mistral_thread_retention_period, (string)$i) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php
}

// Set chatbot_mistral_max_prompt_tokens - Ver 2.0.1
function chatbot_mistral_max_prompt_tokens_callback($args) {
    $max_prompt_tokens = esc_attr(get_option('chatbot_mistral_max_prompt_tokens', 20000));
    ?>
    <select id="chatbot_mistral_max_prompt_tokens" name="chatbot_mistral_max_prompt_tokens">
        <?php
        for ($i = 1000; $i <= 100000; $i += 1000) {
            echo '<option value="' . $i . '" ' . selected($max_prompt_tokens, (string)$i) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php
}

// Set chatbot_mistral_max_completion_tokens - Ver 2.0.1
function chatbot_mistral_max_completion_tokens_callback($args) {
    $max_completion_tokens = esc_attr(get_option('chatbot_mistral_max_completion_tokens', 20000));
    ?>
    <select id="chatbot_mistral_max_completion_tokens" name="chatbot_mistral_max_completion_tokens">
        <?php
        for ($i = 1000; $i <= 100000; $i += 1000) {
            echo '<option value="' . $i . '" ' . selected($max_completion_tokens, (string)$i) . '>' . esc_html($i) . '</option>';
        }
    ?>
    </select>
    <?php
}

// Remote Widget Settings section callback - Ver 2.1.3
function chatbot_mistral_remote_widget_settings_section_callback($args) {
    ?>
    <p>Configure the Remote Widget settings to allow access from specific domains to specific agents. Please each pair, seperated with a comma, on their own line.</p>
    <p>For example the Allowed Remote Domain might be <code>www.example.com,agent-1</code>.</p>
    <p style="background-color: #e0f7fa; padding: 10px;"><b>For an explanation of the Remote Widget Settings and additional documentation please click <a href="?page=chatbot-chatgpt&tab=support&dir=assistants&file=remote-widget-settings.md">here</a>.</b></p>
    <?php
}

// Enable Remote Widget field callback - Ver 2.1.3
function chatbot_mistral_enable_remote_widget_callback($args) {
    $enable_remote_widget = esc_attr(get_option('chatbot_mistral_enable_remote_widget', 'No'));
    ?>
    <select id="chatbot_mistral_enable_remote_widget" name="chatbot_mistral_enable_remote_widget">
        <option value="Yes" <?php selected( $enable_remote_widget, 'Yes' ); ?>><?php echo esc_html( 'Yes' ); ?></option>
        <option value="No" <?php selected( $enable_remote_widget, 'No' ); ?>><?php echo esc_html( 'No' ); ?></option>
    </select>
    <?php
}

// Allowed Remote Domains field callback - Ver 2.1.3
function chatbot_mistral_allowed_remote_domains_callback($args) {
    $allowed_remote_domains = esc_attr(get_option('chatbot_mistral_allowed_remote_domains', ''));
    ?>
    <textarea id="chatbot_mistral_allowed_remote_domains" name="chatbot_mistral_allowed_remote_domains" placeholder="Enter the allowed remote domains separated by a comma." rows="5" cols="50"><?php echo esc_attr( $allowed_remote_domains ); ?></textarea>
    <?php
}
