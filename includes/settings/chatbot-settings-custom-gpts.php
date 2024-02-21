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

// GPT Assistants settings section callback - Ver 1.7.2
function chatbot_chatgpt_gpt_assistants_section_callback($args) {
    ?>
    <p>Configure settings for your GPT Assistants by adding your below.</p>
    <p>If you have developed a GPT Assistant, you will need the id of the assistant - is usually starts with "asst_".</p>
    <p>Enter your GPT Assistant ID instead of ChatGPT.  Set the 'Use GPT Assistant ID' to 'Yes'.</p>
    <p>Otherwise, you can leave the GPT Assistant ID field blank and set the usage to 'No'.</p>
    <p>More information can be found here <a href="https://platform.openai.com/playground?mode=assistant" target="_blank">https://platform.openai.com/playground?mode=assistant</a>.</p>
    <p>See <a href="?page=chatbot-chatgpt&tab=support">Support</a> for more details on using multiple GPT assistants.</p>
    <h2>Using Multiple GPT Assistants</h2>
    <p>You can integrate GPT Assistants into your platform using one of shortcode configurations below.</p>
    <p>Each configuration requires either 'primary', 'alternate' or a GPT Assistant ID, denoted as 'asst_xxxxxxxxxxxxxxxxxxxxxxxx'.</p>
    <p>GPT Assistants work with both 'floating' and 'embedded' styles.</p>
    <p><b>NOTE:</b>The 'primary' and 'alternate' assistants are set in the ChatGPT settings page.</p>
    <p><b>NOTE:</b>For best results ensure that the shortcode appears only once on the page.</p>
    <p>Use the following format to invoke the primary or alternate assistant:</p>
    <ul style="list-style-type: disc; list-style-position: inside; padding-left: 1em;">
        <li><b>&#91;chatbot style="floating" assistant="primary"&#93;</b> - Floating style, GPT Assistant as set in Primary setting</li>
        <li><b>&#91;chatbot style="embedded" assistant="alternate"&#93;</b> - Embedded style, GPT Assistant as set in Alternate setting</li>
        <li><b>&#91;chatbot style="floating" assistant="asst_xxxxxxxxxxxxxxxxxxxxxxxx"&#93;</b> - Floating style, GPT Assistant as set in GPT Assistant ID setting</li>
        <li><b>&#91;chatbot style="embedded" assistant="asst_xxxxxxxxxxxxxxxxxxxxxxxx"&#93;</b> - Embedded style, GPT Assistant as set in GPT Assistant ID setting</li>
        <li><b>Mix and match the style and assistant attributes to suit your needs.</b></li>
    </ul>
    <p><b>NOTE: </b>When using the 'embedded' style, it's best to put the shortcode in a page or post, not in a footer.</b></p>
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
