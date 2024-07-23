<?php
/**
 * Kognetiks Chatbot for WordPress - Settings - Custom GPTs
 *
 * This file contains the code for the Chatbot settings page.
 * It allows users to configure the API key and other parameters
 * required to access the ChatGPT API from their own account.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Register Assistant settings - Ver 2.0.2.1
function chatbot_chatgpt_assistant_settings_init() {

    // chatbot_chatgpt_assistant_settings
    add_settings_section(
        'chatbot_chatgpt_assistant_settings_section',
        'Assistant Settings',
        'chatbot_chatgpt_assistant_settings_section_callback',
        'chatbot_chatgpt_assistant_settings'
    );

    // Settings Custom GPTs tab - Ver 1.7.2
    // register_setting('chatbot_chatgpt_custom_gpts', 'chatbot_chatgpt_use_custom_gpt_assistant_id'); // Ver 1.6.7 - REMOVED in Ver 2.0.5
    register_setting('chatbot_chatgpt_custom_gpts', 'chatbot_chatgpt_allow_file_uploads'); // Ver 1.7.6
    register_setting('chatbot_chatgpt_custom_gpts', 'chatbot_chatgpt_display_custom_gpt_assistant_name'); // Ver 1.9.4
    register_setting('chatbot_chatgpt_custom_gpts', 'chatbot_chatgpt_assistant_id'); // Ver 1.6.7
    register_setting('chatbot_chatgpt_custom_gpts', 'chatbot_chatgpt_assistant_instructions'); // Ver 1.9.3
    register_setting('chatbot_chatgpt_custom_gpts', 'chatbot_chatgpt_assistant_id_alternate'); // Alternate Assistant - Ver 1.7.2
    register_setting('chatbot_chatgpt_custom_gpts', 'chatbot_chatgpt_assistant_instructions_alternate'); // Alternate Assistant - Ver 1.9.3
    register_setting('chatbot_chatgpt_custom_gpts', 'chatbot_chatgpt_assistant_beta_version'); // Beta Assistant - Ver 1.9.3
    register_setting('chatbot_chatgpt_custom_gpts', 'chatbot_chatgpt_thread_retention_period'); // Thread Retention Period - Ver 1.9.9
    register_setting('chatbot_chatgpt_custom_gpts', 'chatbot_chatgpt_max_prompt_tokens'); // Max Prompt Tokens - Ver 2.0.1
    register_setting('chatbot_chatgpt_custom_gpts', 'chatbot_chatgpt_max_completion_tokens'); // Max Response Tokens - Ver 2.0.1

    // General Settings for Assistants
    add_settings_section(
        'chatbot_chatgpt_custom_gpts_section',
        'Assistant General Settings',
        'chatbot_chatgpt_gpt_assistants_section_callback',
        'chatbot_chatgpt_gpt_assistants_settings'
    );
    
    // Use GPT Assistant ID (Yes or No) - Ver 1.6.7 - REMOVED in Ver 2.0.5
    // add_settings_field(
    //     'chatbot_chatgpt_use_custom_gpt_assistant_id',
    //     'Use GPT Assistant Id',
    //     'chatbot_chatgpt_use_gpt_assistant_id_callback',
    //     'chatbot_chatgpt_gpt_assistants_settings',
    //     'chatbot_chatgpt_custom_gpts_section'
    // );

    // Allow file uploads to the Assistant - Ver 1.7.6
    add_settings_field(
        'chatbot_chatgpt_allow_file_uploads',
        'Allow File Uploads',
        'chatbot_chatgpt_allow_file_uploads_callback',
        'chatbot_chatgpt_gpt_assistants_settings',
        'chatbot_chatgpt_custom_gpts_section'
    );

    // Display Custom GPT Assistant Name - Ver 1.9.4
    add_settings_field(
        'chatbot_chatgpt_display_custom_gpt_assistant_name',
        'Display GPT Assistant Name',
        'chatbot_chatgpt_use_gpt_assistant_name_callback',
        'chatbot_chatgpt_gpt_assistants_settings',
        'chatbot_chatgpt_custom_gpts_section'
    );

    // Assistant Id Settings
    add_settings_section(
        'chatbot_chatgpt_assistant_ids_section',
        'Assistant IDs and Additional Instructions',
        'chatbot_chatgpt_assistant_id_section_callback',
        'chatbot_chatgpt_assistant_id_settings'
    );

    // CustomGPT Assistant Id - Ver 1.6.7
    add_settings_field(
        'chatbot_chatgpt_assistant_id',
        'Primary GPT Assistant Id',
        'chatbot_chatgpt_assistant_id_callback',
        'chatbot_chatgpt_assistant_id_settings',
        'chatbot_chatgpt_assistant_ids_section'
    );

    add_settings_field(
        'chatbot_chatgpt_assistant_instructions',
        'Assistant Instructions',
        'chatbot_chatgpt_assistant_instructions_callback',
        'chatbot_chatgpt_assistant_id_settings',
        'chatbot_chatgpt_assistant_ids_section'
    );

    // CustomGPT Assistant Id Alternate - Ver 1.7.2
    add_settings_field(
        'chatbot_chatgpt_assistant_id_alternate',
        'Alternate GPT Assistant Id',
        'chatbot_chatgpt_assistant_id_alternate_callback',
        'chatbot_chatgpt_assistant_id_settings',
        'chatbot_chatgpt_assistant_ids_section'
    );

    add_settings_field(
        'chatbot_chatgpt_assistant_instructions_alternate',
        'Alternate Assistant Instructions',
        'chatbot_chatgpt_assistant_instructions_alternate_callback',
        'chatbot_chatgpt_assistant_id_settings',
        'chatbot_chatgpt_assistant_ids_section'
    );

    // Advanced Additional Settings
    add_settings_section(
        'chatbot_chatgpt_assistant_advanced_settings_section',
        'Advanced Additional Settings',
        'chatbot_chatgpt_assistant_advanced_settings_section_callback',
        'chatbot_chatgpt_additional_assistant_settings'
    );

    // Max Prompt Tokens - Ver 2.0.1
    add_settings_field(
        'chatbot_chatgpt_max_prompt_tokens',
        'Max Prompt Tokens',
        'chatbot_chatgpt_max_prompt_tokens_callback',
        'chatbot_chatgpt_additional_assistant_settings',
        'chatbot_chatgpt_assistant_advanced_settings_section'
    );

    // Max Response Tokens - Ver 2.0.1
    add_settings_field(
        'chatbot_chatgpt_max_completion_tokens',
        'Max Response Tokens',
        'chatbot_chatgpt_max_completion_tokens_callback',
        'chatbot_chatgpt_additional_assistant_settings',
        'chatbot_chatgpt_assistant_advanced_settings_section'
    );

    // Thread Retention Period - Ver 1.9.9
    add_settings_field(
        'chatbot_chatgpt_thread_retention_period',
        'Thread Retention Period (hrs)',
        'chatbot_chatgpt_thread_retention_period_callback',
        'chatbot_chatgpt_additional_assistant_settings',
        'chatbot_chatgpt_assistant_advanced_settings_section'
    );

    add_settings_field(
        'chatbot_chatgpt_assistant_beta_version',
        'Beta Assistant Version',
        'chatbot_chatgpt_assistant_beta_version_callback',
        'chatbot_chatgpt_additional_assistant_settings',
        'chatbot_chatgpt_assistant_advanced_settings_section'
    );
    
}
add_action('admin_init', 'chatbot_chatgpt_assistant_settings_init');

// GPT Assistants settings section callback - Ver 1.7.2 - Updated Ver 2.0.4
function chatbot_chatgpt_assistant_settings_section_callback($args) {
    ?>
    <p>Effortlessly manage you chatbot Assistants all in one place using the intuitive interface below.</p>
    <p>You will no longer need to remember all the Assistant options, as they are all available here for you to view and edit.</p>
    <p>Tailor each Assistant to meet the unique needs of your audience, ensuring an engaging and personalized experience for all.</p>
    <p>If you have developed an Assistant in the OpenAI Playground, you will need the id of the assistant - it usually starts with "asst_".</p>
    <p>More information can be found here <a href="https://platform.openai.com/playground?mode=assistant" target="_blank">https://platform.openai.com/playground?mode=assistant</a>.</p>
    <!-- <p>When you're ready to use an Assistant, simply add the shortcode <code>[chatbot assistant="Common Name"]</code> to your page.</p> -->
    <p>When you're ready to use an Assistant, simply add a shortcode such as <code>[chatbot-1]</code>, <code>[chatbot-2]</code>, etc. to your page.</p>
    <p><b>TIP:</b> For best results ensure that the shortcode appears only once on the page.</p>
    <p><b>TIP:</b> When using the 'embedded' style, it's best to put the shortcode in a page or post, not in a footer.</b></p>
    <p><b><i>Don't forget to click </i><code>Save Settings</code><i> to save any changes your might make.</i></b></p>
    <p style="background-color: #e0f7fa; padding: 10px;"><b>For an explanation Assistant Settings and additional documentation please click <a href="?page=chatbot-chatgpt&tab=support&dir=assistants&file=manage-assistants.md">here</a>.</b></p>
    <?php
}

// General settings section callback - Ver 2.0.2.1
function chatbot_chatgpt_gpt_assistants_section_callback($args) {
    ?>
    <p>Configure the Chatbot to allow file uploads and display the Assistant's name.</p>
    <p style="background-color: #e0f7fa; padding: 10px;"><b>For an explanation of the Assistant General Settings and additional documentation please click <a href="?page=chatbot-chatgpt&tab=support&dir=assistants&file=assistants.md">here</a>.</b></p>
    <?php
}

// GPT Assistant ID section callback - Ver 1.7.2
function chatbot_chatgpt_assistant_id_section_callback($args) {
    ?>
    <p>Configure a Primary and Alternate Assistant by entering the ID and any additional instructions.</p>
    <p style="background-color: #e0f7fa; padding: 10px;"><b>For an explanation of the general Settings and additional documentation please click <a href="?page=chatbot-chatgpt&tab=support&dir=assistants&file=assistants.md">here</a>.</b></p>
    <?php
}

// GPT Assistant Instructions section callback - Ver 1.9.3
function chatbot_chatgpt_assistant_advanced_settings_section_callback($args) {
    ?>
    <p>Configure the Advanced Settings for Assistants prompt and response tokens, thread retention periods, and the Beta version setting.</p>
    <p style="background-color: #e0f7fa; padding: 10px;"><b>For an explanation of the Advanced Settings and additional documentation please click <a href="?page=chatbot-chatgpt&tab=support&dir=assistants&file=assistants.md">here</a>.</b></p>
    <?php
}

// Use GPT Assistant Id field callback - Ver 1.6.7
function chatbot_chatgpt_use_gpt_assistant_id_callback($args) {
    $use_assistant_id = esc_attr(get_option('chatbot_chatgpt_use_custom_gpt_assistant_id', 'No'));
    ?>
    <select id="chatbot_chatgpt_use_custom_gpt_assistant_id" name="chatbot_chatgpt_use_custom_gpt_assistant_id">
        <option value="Yes" <?php selected( $use_assistant_id, 'Yes' ); ?>><?php echo esc_html( 'Yes' ); ?></option>
        <option value="No" <?php selected( $use_assistant_id, 'No' ); ?>><?php echo esc_html( 'No' ); ?></option>
    </select>
    <?php
    if ($use_assistant_id == 'No') {
        update_option('chatbot_chatgpt_assistant_id', '');
        update_option('chatbot_chatgpt_assistant_id_alternate', '');
    }
}

// Allow file uploads field callback - Ver 1.7.6
function chatbot_chatgpt_allow_file_uploads_callback($args) {
    $allow_file_uploads = esc_attr(get_option('chatbot_chatgpt_allow_file_uploads', 'No'));
    ?>
    <select id="chatbot_chatgpt_allow_file_uploads" name="chatbot_chatgpt_allow_file_uploads">
        <option value="Yes" <?php selected( $allow_file_uploads, 'Yes' ); ?>><?php echo esc_html( 'Yes' ); ?></option>
        <option value="No" <?php selected( $allow_file_uploads, 'No' ); ?>><?php echo esc_html( 'No' ); ?></option>
    </select>
    <?php
}

// GPT Assistant ID field callback - Ver 1.6.7
function chatbot_chatgpt_assistant_id_callback($args) {
    $assistant_id = esc_attr(get_option('chatbot_chatgpt_assistant_id', 'Please provide the GPT Assistant Id.'));
    $use_assistant_id = esc_attr(get_option('chatbot_chatgpt_use_custom_gpt_assistant_id', 'No'));
    if ($use_assistant_id == 'Yes' && ($assistant_id == 'Please provide the GPT Assistant Id.' or empty($assistant_id))) {
        $assistant_id = 'Please provide the GPT Assistant Id.';
    }
    // Set default value if empty
    // $assistant_id = empty($assistant_id) ? 'Please provide the GPT Assistant ID.': $assistant_id;
    ?>
    <input type="text" id="chatbot_chatgpt_assistant_id" name="chatbot_chatgpt_assistant_id" value="<?php echo esc_attr( $assistant_id ); ?>" class="regular-text">
    <?php
}

// GPT Assistant ID field callback - Ver 1.6.7
function chatbot_chatgpt_assistant_id_alternate_callback($args) {
    $assistant_id_alternate = esc_attr(get_option('chatbot_chatgpt_assistant_id_alternate', 'Please provide the Alternate GPT Assistant Id.'));
    $use_assistant_id = esc_attr(get_option('chatbot_chatgpt_use_custom_gpt_assistant_id', 'No'));
    if ($use_assistant_id == 'Yes' && ($assistant_id_alternate == 'Please provide the GPT Assistant Id.' or empty($assistant_id_alternate))) {
        $assistant_id_alternate = 'Please provide the Alternate GPT Assistant Id, if any.';
    }
    // Set default value if empty
    // $assistant_id = empty($assistant_id) ? 'Please provide the GPT Assistant ID.': $assistant_id;
    ?>
    <input type="text" id="chatbot_chatgpt_assistant_id_alternate" name="chatbot_chatgpt_assistant_id_alternate" value="<?php echo esc_attr( $assistant_id_alternate ); ?>" class="regular-text">
    <?php
}

// GPT Assistant Instructions field callback - Ver 1.9.3
function chatbot_chatgpt_assistant_instructions_callback ($args) {
    $chatbot_chatgpt_assistant_instructions = esc_attr(get_option('chatbot_chatgpt_assistant_instructions', ''));
    ?>
    <textarea id="chatbot_chatgpt_assistant_instructions" name="chatbot_chatgpt_assistant_instructions" placeholder="Added instructions to assistant if needed ...." rows="5" cols="50"><?php echo esc_attr( $chatbot_chatgpt_assistant_instructions ); ?></textarea>
    <?php
}

// GPT Assistant Instructions Alternate field callback - Ver 1.9.3
function chatbot_chatgpt_assistant_instructions_alternate_callback ($args) {
    $chatbot_chatgpt_assistant_instructions_alternate = esc_attr(get_option('chatbot_chatgpt_assistant_instructions_alternate', ''));
    ?>
    <textarea id="chatbot_chatgpt_assistant_instructions_alternate" name="chatbot_chatgpt_assistant_instructions_alternate" placeholder="Added instructions to assistant if needed ...." rows="5" cols="50"><?php echo esc_attr( $chatbot_chatgpt_assistant_instructions_alternate ); ?></textarea>
    <?php
}

// Use GPT Assistant Names field callback - Ver 1.9.4
function chatbot_chatgpt_use_gpt_assistant_name_callback($args) {
    $use_assistant_name = esc_attr(get_option('chatbot_chatgpt_display_custom_gpt_assistant_name', 'Yes'));
    ?>
    <select id="chatbot_chatgpt_display_custom_gpt_assistant_name" name="chatbot_chatgpt_display_custom_gpt_assistant_name">
        <option value="Yes" <?php selected( $use_assistant_name, 'Yes' ); ?>><?php echo esc_html( 'Yes' ); ?></option>
        <option value="No" <?php selected( $use_assistant_name, 'No' ); ?>><?php echo esc_html( 'No' ); ?></option>
    </select>
    <?php
}

// Set Assistant Beta Version - Ver 1.9.6
function chatbot_chatgpt_assistant_beta_version_callback($args) {
    $assistant_beta_version = esc_attr(get_option('chatbot_chatgpt_assistant_beta_version', 'v2'));
    ?>
    <select id="chatbot_chatgpt_assistant_beta_version" name="chatbot_chatgpt_assistant_beta_version">
        <option value="v1" <?php selected( $assistant_beta_version, 'v1' ); ?>><?php echo esc_html( 'v1' ); ?></option>
        <option value="v2" <?php selected( $assistant_beta_version, 'v2' ); ?>><?php echo esc_html( 'v2' ); ?></option>
    </select>
    <?php
}

// Set chatbot_chatgpt_thread_retention_period - Ver 1.9.9
function chatbot_chatgpt_thread_retention_period_callback($args) {
    $chatbot_chatgpt_thread_retention_period = esc_attr(get_option('chatbot_chatgpt_thread_retention_period', 36));
    ?>
    <select id="chatbot_chatgpt_thread_retention_period" name="chatbot_chatgpt_thread_retention_period">
        <?php
        for ($i = 6; $i <= 720; $i += 6) {
            echo '<option value="' . $i . '" ' . selected($chatbot_chatgpt_thread_retention_period, (string)$i) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php
}

// Set chatbot_chatgpt_max_prompt_tokens - Ver 2.0.1
// https://platform.openai.com/docs/assistants/how-it-works/max-completion-and-max-prompt-tokens
function chatbot_chatgpt_max_prompt_tokens_callback($args) {
    $max_prompt_tokens = esc_attr(get_option('chatbot_chatgpt_max_prompt_tokens', 20000));
    ?>
    <select id="chatbot_chatgpt_max_prompt_tokens" name="chatbot_chatgpt_max_prompt_tokens">
        <?php
        for ($i = 1000; $i <= 100000; $i += 1000) {
            echo '<option value="' . $i . '" ' . selected($max_prompt_tokens, (string)$i) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php
}

// Set chatbot_chatgpt_max_completion_tokens - Ver 2.0.1
// https://platform.openai.com/docs/assistants/how-it-works/max-completion-and-max-prompt-tokens
function chatbot_chatgpt_max_completion_tokens_callback($args) {
    $max_completion_tokens = esc_attr(get_option('chatbot_chatgpt_max_completion_tokens', 20000));
    ?>
    <select id="chatbot_chatgpt_max_completion_tokens" name="chatbot_chatgpt_max_completion_tokens">
        <?php
        for ($i = 1000; $i <= 100000; $i += 1000) {
            echo '<option value="' . $i . '" ' . selected($max_completion_tokens, (string)$i) . '>' . esc_html($i) . '</option>';
        }
    ?>
    </select>
    <?php
}
