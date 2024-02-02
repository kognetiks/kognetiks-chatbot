<?php
/**
 * Chatbot ChatGPT for WordPress - Settings - Appearance - Ver 1.8.1
 *
 * This file contains the code for the Chatbot ChatGPT settings page.
 * It handles the appearance settings and other parameters.
 * 
 *
 * @package chatbot-chatgpt
 */

// IDEA - COMING SOON - Ver 1.6.8

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

function chatbot_chatgpt_appearance_settings(): void {
    
    register_setting('chatbot_chatgpt_appearance', 'chatbot_chatgpt_appearance_background_color');
    register_setting('chatbot_chatgpt_appearance', 'chatbot_chatgpt_appearance_header_background_color');
    register_setting('chatbot_chatgpt_appearance', 'chatbot_chatgpt_appearance_bubble_background_color');
    register_setting('chatbot_chatgpt_appearance', 'chatbot_chatgpt_appearance_text_color');
    register_setting('chatbot_chatgpt_appearance', 'chatbot_chatgpt_appearance_user_text_background_color');
    register_setting('chatbot_chatgpt_appearance', 'chatbot_chatgpt_appearance_bot_text_background_color');
    register_setting('chatbot_chatgpt_appearance', 'chatbot_chatgpt_appearance_width_wide');
    register_setting('chatbot_chatgpt_appearance', 'chatbot_chatgpt_appearance_width_narrow');
    register_setting('chatbot_chatgpt_settings', 'chatbot_chatgpt_width_setting');
    register_setting('chatbot_chatgpt_appearance', 'chatbot_chatgpt_appearance_reset');

    add_settings_section(
        'chatbot_chatgpt_appearance_section',
        'Appearance Settings',
        'chatbot_chatgpt_appearance_section_callback',
        'chatbot_chatgpt_appearance'
    );

    add_settings_field(
        'chatbot_chatgpt_appearance_background_color',
        'Chatbot Background Color',
        'chatbot_chatgpt_appearance_background_color_callback',
        'chatbot_chatgpt_appearance',
        'chatbot_chatgpt_appearance_section'
    );

    add_settings_field(
        'chatbot_chatgpt_appearance_header_background_color',
        'Header Background Color',
        'chatbot_chatgpt_appearance_header_background_color_callback',
        'chatbot_chatgpt_appearance',
        'chatbot_chatgpt_appearance_section'
    );

    add_settings_field(
        'chatbot_chatgpt_appearance_text_color',
        'Text Color',
        'chatbot_chatgpt_appearance_text_color_callback',
        'chatbot_chatgpt_appearance',
        'chatbot_chatgpt_appearance_section'
    );

    add_settings_field(
        'chatbot_chatgpt_appearance_user_text_background_color',
        'User Text Background Color',
        'chatbot_chatgpt_appearance_user_text_background_color_callback',
        'chatbot_chatgpt_appearance',
        'chatbot_chatgpt_appearance_section'
    );

    add_settings_field(
        'chatbot_chatgpt_appearance_bot_text_background_color',
        'Bot Text Background Color',
        'chatbot_chatgpt_appearance_bot_text_background_color_callback',
        'chatbot_chatgpt_appearance',
        'chatbot_chatgpt_appearance_section'
    );

    add_settings_field(
        'chatbot_chatgpt_appearance_width_wide',
        'Chatbot Width Wide',
        'chatbot_chatgpt_appearance_width_wide_callback',
        'chatbot_chatgpt_appearance',
        'chatbot_chatgpt_appearance_section'
    );

    add_settings_field(
        'chatbot_chatgpt_appearance_width_narrow',
        'Chatbot Width Narrow',
        'chatbot_chatgpt_appearance_width_narrow_callback',
        'chatbot_chatgpt_appearance',
        'chatbot_chatgpt_appearance_section'
    );

    // Option to change the width of the bot from narrow to wide - Ver 1.4.2
    add_settings_field(
        'chatbot_chatgpt_width_setting',
        'Chatbot Width Setting',
        'chatbot_chatgpt_width_setting_callback',
        'chatbot_chatgpt_appearance',
        'chatbot_chatgpt_appearance_section'
    );

    add_settings_field(
        'chatbot_chatgpt_appearance_reset',
        'Restore Defaults',
        'chatbot_chatgpt_appearance_reset_callback',
        'chatbot_chatgpt_appearance',
        'chatbot_chatgpt_appearance_section'
    );

}
add_action('admin_init', 'chatbot_chatgpt_appearance_settings');


// Custom Appearance Settings - Ver 1.8.1
function chatbot_chatgpt_appearance_section_callback(): void{
    ?>
    <div>
        <p>Choose a color combinations that best represents you and your brand.  You can change your color combinations at any time.</p>
        <p><b><i>Don't forget to click 'Save Settings' to save your changes.</i><b></p>
    </div>
    <?php
}


// Reset the appearance settings - Ver 1.8.1
function chatbot_chatgpt_appearance_reset_callback(): void {
    $chatbot_chatgpt_appearance_reset = esc_attr(get_option('chatbot_chatgpt_appearance_reset', 'No'));
    ?>
    <label for="chatbot_chatgpt_appearance_reset"></label><select id="chatbot_chatgpt_appearance_reset" name="chatbot_chatgpt_appearance_reset">
        <option value="Yes" <?php selected( $chatbot_chatgpt_appearance_reset, 'Yes' ); ?>><?php echo esc_html( 'Yes' ); ?></option>
        <option value="No" <?php selected( $chatbot_chatgpt_appearance_reset, 'No' ); ?>><?php echo esc_html( 'No' ); ?></option>
    </select>
    <?php
}

// Restore the appearance defaults - Ver 1.8.1
function chatbot_chatgpt_appearance_restore_default_settings(): void {

    // DIAG - Enter function
    chatbot_chatgpt_back_trace( 'NOTICE', 'Enter function: chatbot_chatgpt_appearance_restore_default_settings()');

    $chatbot_chatgpt_appearance_reset = 'No';
    update_option('chatbot_chatgpt_appearance_reset', $chatbot_chatgpt_appearance_reset);

    // Delete the background color
    delete_option('chatbot_chatgpt_appearance_background_color');
    delete_option('chatbot_chatgpt_appearance_header_background_color');

    // Delete the text color
    delete_option('chatbot_chatgpt_appearance_text_color');
    delete_option('chatbot_chatgpt_appearance_user_text_background_color');
    delete_option('chatbot_chatgpt_appearance_bot_text_background_color');

    // Delete the width settings
    delete_option('chatbot_chatgpt_appearance_width_wide');
    delete_option('chatbot_chatgpt_appearance_width_narrow');

    // Now override the css with the default color
    chatbot_chatgpt_appearance_custom_css_settings();

    // DIAG - Exit function
    chatbot_chatgpt_back_trace( 'NOTICE', 'Exit function: chatbot_chatgpt_appearance_restore_default_settings()');

}

// Override the css with the color chosen by the user
function chatbot_chatgpt_appearance_custom_css_settings(): void {
    
    // Color settings
    chatbot_chatgpt_appearance_background_custom_css_settings();
    chatbot_chatgpt_appearance_header_background_custom_css_settings();

    // Text settings
    chatbot_chatgpt_appearance_text_color_custom_css_settings();
    chatbot_chatgpt_appearance_user_text_background_custom_css_settings();
    chatbot_chatgpt_appearance_bot_text_background_custom_css_settings();

    // Dimension settings
    chatbot_chatgpt_appearance_width_wide_custom_css_settings();
    chatbot_chatgpt_appearance_width_narrow_custom_css_settings();

}
