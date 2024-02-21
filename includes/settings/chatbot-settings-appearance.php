<?php
/**
 * Kognetiks Chatbot for WordPress - Settings - Appearance - Ver 1.8.1
 *
 * This file contains the code for the Chatbot settings page.
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
    register_setting('chatbot_chatgpt_appearance', 'chatbot_chatgpt_appearance_header_text_color');
    register_setting('chatbot_chatgpt_appearance', 'chatbot_chatgpt_appearance_user_text_background_color');
    register_setting('chatbot_chatgpt_appearance', 'chatbot_chatgpt_appearance_bot_text_background_color');
    register_setting('chatbot_chatgpt_appearance', 'chatbot_chatgpt_appearance_greeting_text_color');
    register_setting('chatbot_chatgpt_appearance', 'chatbot_chatgpt_appearance_width_wide');
    register_setting('chatbot_chatgpt_appearance', 'chatbot_chatgpt_appearance_width_narrow');
    register_setting('chatbot_chatgpt_appearance', 'chatbot_chatgpt_width_setting');
    register_setting('chatbot_chatgpt_appearance', 'chatbot_chatgpt_appearance_reset');
    register_setting('chatbot_chatgpt_appearance', 'chatbot_chatgpt_appearance_user_css_setting');

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
        'chatbot_chatgpt_appearance_header_text_color',
        'Header Text Color',
        'chatbot_chatgpt_appearance_header_text_color_callback',
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
        'chatbot_chatgpt_appearance_greeting_text_color',
        'Greeting Text Color',
        'chatbot_chatgpt_appearance_greeting_text_color_callback',
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

    add_settings_field(
        'chatbot_chatgpt_appearance_user_css_setting',
        'Custom CSS',
        'chatbot_chatgpt_appearance_user_css_setting_callback',
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
    // back_trace( 'NOTICE', 'Enter function: chatbot_chatgpt_appearance_restore_default_settings()');

    $chatbot_chatgpt_appearance_reset = 'No';
    update_option('chatbot_chatgpt_appearance_reset', $chatbot_chatgpt_appearance_reset);

    // Delete the background color
    delete_option('chatbot_chatgpt_appearance_background_color');
    delete_option('chatbot_chatgpt_appearance_header_background_color');

    // Delete the text color
    delete_option('chatbot_chatgpt_appearance_text_color');
    delete_option('chatbot_chatgpt_appearance_user_text_background_color');
    delete_option('chatbot_chatgpt_appearance_bot_text_background_color');
    delete_option('chatbot_chatgpt_appearance_greeting_text_color');
    delete_option('chatbot_chatgpt_appearance_header_text_color');

    // Delete the width settings
    delete_option('chatbot_chatgpt_appearance_width_wide');
    delete_option('chatbot_chatgpt_appearance_width_narrow');

    // Now override the css with the default color
    chatbot_chatgpt_appearance_custom_css_settings();

    // Update the width setting to 'Narrow'
    // update_option ('chatbot_chatgpt_width_setting', 'Narrow');

    // DIAG - Exit function
    // back_trace( 'NOTICE', 'Exit function: chatbot_chatgpt_appearance_restore_default_settings()');

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
    chatbot_chatgpt_appearance_greeting_text_color_custom_css_settings();
    chatbot_chatgpt_appearance_header_text_color_custom_css_settings();

    // Dimension settings
    chatbot_chatgpt_appearance_width_wide_custom_css_settings();
    chatbot_chatgpt_appearance_width_narrow_custom_css_settings();

    // Inject inline css
    chatbot_chatgpt_appearance_inject_custom_css_settings();

}

// Inject the custom css settings
function chatbot_chatgpt_appearance_inject_custom_css_settings(): void {

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', 'Injecting custom CSS settings...');
    // back_trace( 'NOTICE', print_r($GLOBALS['chatbotChatGPTAppearanceCSS'], true));

    // Prepend any user CSS settings
    $chatbot_chatgpt_appearance_user_css_setting = esc_attr(get_option('chatbot_chatgpt_appearance_user_css_setting', ''));
    // Remove any leading or trailing spaces
    $chatbot_chatgpt_appearance_user_css_setting = trim($chatbot_chatgpt_appearance_user_css_setting);
    // Remove multiple spaces
    $chatbot_chatgpt_appearance_user_css_setting = preg_replace('/\s+/', ' ', $chatbot_chatgpt_appearance_user_css_setting);
    // Remove any line breaks
    $chatbot_chatgpt_appearance_user_css_setting = str_replace(array("\r", "\n"), '', $chatbot_chatgpt_appearance_user_css_setting);

    // $GLOBALS['chatbotChatGPTAppearanceCSS']['chatbot-chatgpt-user-css'] = $chatbot_chatgpt_appearance_user_css_setting;

    // DIAG - Diagnostics - Ver 1.8.6
    // foreach ($GLOBALS['chatbotChatGPTAppearanceCSS'] as $cssRule) {
    //     back_trace( 'NOTICE', 'cssRule: ' . $cssRule);
    // }

    // Inject the custom css settings
    $chatbotChatGPTAppearanceCSS = $GLOBALS['chatbotChatGPTAppearanceCSS'];
    $chatbotChatGPTAppearanceCSS = implode("\n", $chatbotChatGPTAppearanceCSS); // Prepend spaces for indentation

    ?>
    <style>
        <?php
        echo "\t\t" . $chatbot_chatgpt_appearance_user_css_setting . "\n"; // Put user CSS settings at the top
        // Loop through each CSS rule and output it with indentation
        foreach ($GLOBALS['chatbotChatGPTAppearanceCSS'] as $cssRule) {
            echo "\t\t" . $cssRule . "\n"; // Add spaces before each rule for indentation
        }
        ?>
    </style>
    <?php
    
}
// Hook into wp_head
add_action('wp_footer', 'chatbot_chatgpt_appearance_inject_custom_css_settings');
