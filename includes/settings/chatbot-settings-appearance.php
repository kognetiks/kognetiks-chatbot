<?php
/**
 * Kognetiks Chatbot - Settings - Appearance - Ver 1.8.1
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
    die();
}

function chatbot_chatgpt_appearance_settings_init() {
    
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
    register_setting('chatbot_chatgpt_appearance', 'chatbot_chatgpt_image_width_setting');
    register_setting('chatbot_chatgpt_appearance', 'chatbot_chatgpt_width_setting');
    register_setting('chatbot_chatgpt_appearance', 'chatbot_chatgpt_appearance_reset');
    register_setting('chatbot_chatgpt_appearance', 'chatbot_chatgpt_appearance_user_css_setting');
    register_setting('chatbot_chatgpt_appearance', 'chatbot_chatgpt_enable_mathjax');

    // Enable MathJax
    register_setting(
        'chatbot_chatgpt_appearance',
        'chatbot_chatgpt_enable_mathjax',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        )
    );

    // Open Icon
    register_setting(
        'chatbot_chatgpt_appearance',
        'chatbot_chatgpt_appearance_open_icon',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_url',
        )
    );

    // Collapse Icon
    register_setting(
        'chatbot_chatgpt_appearance',
        'chatbot_chatgpt_appearance_collapse_icon',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_url',
        )
    );

    // Erase Icon
    register_setting(
        'chatbot_chatgpt_appearance',
        'chatbot_chatgpt_appearance_erase_icon',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_url',
        )
    );

    // Mic Enabled Icon
    register_setting(
        'chatbot_chatgpt_appearance',
        'chatbot_chatgpt_appearance_mic_enabled_icon',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_url',
        )
    );

    // Mic Disabled Icon
    register_setting(
        'chatbot_chatgpt_appearance',
        'chatbot_chatgpt_appearance_mic_disabled_icon',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_url',
        )
    );

    // Send Icon
    register_setting(
        'chatbot_chatgpt_appearance',
        'chatbot_chatgpt_appearance_send_icon',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_url',
        )
    );

    // Attach Icon
    register_setting(
        'chatbot_chatgpt_appearance',
        'chatbot_chatgpt_appearance_attach_icon',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_url',
        )
    );

    // Read Aloud Icon
    register_setting(
        'chatbot_chatgpt_appearance',
        'chatbot_chatgpt_appearance_read_aloud_icon',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_url',
        )
    );

    // Download Icon
    register_setting(
        'chatbot_chatgpt_appearance',
        'chatbot_chatgpt_appearance_download_icon',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_url',
        )
    );

    add_settings_section(
        'chatbot_chatgpt_appearance_overview_section',          // Section ID
        'Appearance Settings Overview',                         // Section Title
        'chatbot_chatgpt_appearance_overview_section_callback', // Section Callback
        'chatbot_chatgpt_appearance_overview'                   // Page
    );

    add_settings_section(
        'chatbot_chatgpt_appearance_section',                   // Section ID
        'Appearance Settings',                                  // Section Title
        'chatbot_chatgpt_appearance_section_callback',          // Section Callback
        'chatbot_chatgpt_appearance'                            // Page
    );

    add_settings_field(
        'chatbot_chatgpt_appearance_background_color',          // Field ID
        'Chatbot Background Color',                             // Field Title
        'chatbot_chatgpt_appearance_background_color_callback', // Field Callback
        'chatbot_chatgpt_appearance',                           // Page
        'chatbot_chatgpt_appearance_section'                    // Section
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

    // Option to fix image width to a percentage of the chatbot width - Ver 2.0.3
    add_settings_field(
        'chatbot_chatgpt_image_width_setting',
        'Image Width Setting',
        'chatbot_chatgpt_image_width_setting_callback',
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

    add_settings_field(
        'chatbot_chatgpt_enable_mathjax',
        'Enable Glyph Rendering',
        'chatbot_chatgpt_enable_mathjax_callback',
        'chatbot_chatgpt_appearance',
        'chatbot_chatgpt_appearance_section'
    );

    // SECTION - Custom Icons - Ver 2.2.2
    add_settings_section(
        'chatbot_chatgpt_appearance_icons_overview_section',            // Section ID
        'Custom Icons Settings Overview',                               // Section Title
        'chatbot_chatgpt_appearance_icons_overview_section_callback',   // Section Callback
        'chatbot_chatgpt_appearance_icons_overview'                     // Page
    );
    
    add_settings_section(
        'chatbot_chatgpt_appearance_icons_section',                     // Section ID
        'Custom Icons Settings',                                        // Section Title
        'chatbot_chatgpt_appearance_icons_appearance_section_callback', // Section Callback
        'chatbot_chatgpt_appearance_icons'                              // Page
    );
    
    // FIELDS - Custom Icons - Ver 2.2.2
    add_settings_field(
        'chatbot_chatgpt_appearance_open_icon',                     // Field ID
        'Open Icon',                                                // Field Title
        'chatbot_chatgpt_appearance_open_icon_callback',            // Field Callback
        'chatbot_chatgpt_appearance_icons',                         // Page
        'chatbot_chatgpt_appearance_icons_section'                  // Section
    );
    
    add_settings_field(
        'chatbot_chatgpt_appearance_collapse_icon',                 // Field ID
        'Collapse Icon',                                            // Field Title
        'chatbot_chatgpt_appearance_collapse_icon_callback',        // Field Callback
        'chatbot_chatgpt_appearance_icons',                         // Page
        'chatbot_chatgpt_appearance_icons_section'                  // Section
    );
    
    add_settings_field(
        'chatbot_chatgpt_appearance_erase_icon',                    // Field ID
        'Erase Icon',                                               // Field Title
        'chatbot_chatgpt_appearance_erase_icon_callback',           // Field Callback
        'chatbot_chatgpt_appearance_icons',                         // Page
        'chatbot_chatgpt_appearance_icons_section'                  // Section
    );
    
    add_settings_field(
        'chatbot_chatgpt_appearance_mic_enabled_icon',              // Field ID
        'Mic Enabled Icon',                                         // Field Title
        'chatbot_chatgpt_appearance_mic_enabled_icon_callback',     // Field Callback
        'chatbot_chatgpt_appearance_icons',                         // Page
        'chatbot_chatgpt_appearance_icons_section'                  // Section
    );

    add_settings_field(
        'chatbot_chatgpt_appearance_mic_disabled_icon',             // Field ID
        'Mic Disabled Icon',                                        // Field Title
        'chatbot_chatgpt_appearance_mic_disabled_icon_callback',    // Field Callback
        'chatbot_chatgpt_appearance_icons',                         // Page
        'chatbot_chatgpt_appearance_icons_section'                  // Section
    );

    add_settings_field(
        'chatbot_chatgpt_appearance_send_icon',                     // Field ID
        'Send Icon',                                                // Field Title
        'chatbot_chatgpt_appearance_send_icon_callback',            // Field Callback
        'chatbot_chatgpt_appearance_icons',                         // Page
        'chatbot_chatgpt_appearance_icons_section'                  // Section
    );

    add_settings_field(
        'chatbot_chatgpt_appearance_attach_icon',                   // Field ID
        'Attach Icon',                                              // Field Title
        'chatbot_chatgpt_appearance_attach_icon_callback',          // Field Callback
        'chatbot_chatgpt_appearance_icons',                         // Page
        'chatbot_chatgpt_appearance_icons_section'                  // Sectionn
    );

    add_settings_field(
        'chatbot_chatgpt_appearance_read_aloud_icon',               // Field ID
        'Read Aloud Icon',                                          // Field Title
        'chatbot_chatgpt_appearance_read_aloud_icon_callback',      // Field Callback
        'chatbot_chatgpt_appearance_icons',                         // Page
        'chatbot_chatgpt_appearance_icons_section'                  // Section
    );

    add_settings_field(
        'chatbot_chatgpt_appearance_download_icon',                 // Field ID
        'Download Icon',                                            // Field Title
        'chatbot_chatgpt_appearance_download_icon_callback',        // Field Callback
        'chatbot_chatgpt_appearance_icons',                         // Page
        'chatbot_chatgpt_appearance_icons_section'                  // Section
    );

}
add_action('admin_init', 'chatbot_chatgpt_appearance_settings_init');

// Appearance Settings Overview
function chatbot_chatgpt_appearance_overview_section_callback(){
    ?>
    <div>
        <p>Choose the color combinations that best represents you and your brand.  You can change your color combinations at any time.</p>
        <p><b><i>Don't forget to click </i><code>Save Settings</code><i> to save any changes your might make.</i></b></p>
        <p style="background-color: #e0f7fa; padding: 10px;"><b>For an explanation on how to use Appearance settings and additional documentation please click <a href="?page=chatbot-chatgpt&tab=support&dir=appearance&file=appearance.md">here</a>.</b></p>

    </div>
    <?php
}

// Custom Appearance Settings - Ver 1.8.1
function chatbot_chatgpt_appearance_section_callback(){

    // PLACEHOLDER - VER 2.0.7

}

// Reset the appearance settings - Ver 1.8.1
function chatbot_chatgpt_appearance_reset_callback() {
    $chatbot_chatgpt_appearance_reset = esc_attr(get_option('chatbot_chatgpt_appearance_reset', 'No'));
    ?>
    <label for="chatbot_chatgpt_appearance_reset"></label><select id="chatbot_chatgpt_appearance_reset" name="chatbot_chatgpt_appearance_reset">
        <option value="Yes" <?php selected( $chatbot_chatgpt_appearance_reset, 'Yes' ); ?>><?php echo esc_html( 'Yes' ); ?></option>
        <option value="No" <?php selected( $chatbot_chatgpt_appearance_reset, 'No' ); ?>><?php echo esc_html( 'No' ); ?></option>
    </select>
    <?php
}

// Restore the appearance defaults - Ver 1.8.1
function chatbot_chatgpt_appearance_restore_default_settings() {

    // DIAG - Enter function

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

}

// Override the css with the color chosen by the user
function chatbot_chatgpt_appearance_custom_css_settings() {
    
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

    // Image settings
    chatbot_chatgpt_appearance_image_width_custom_css_settings();

    // Inject inline css
    chatbot_chatgpt_appearance_inject_custom_css_settings();

}

// Inject the custom css settings
function chatbot_chatgpt_appearance_inject_custom_css_settings() {

    global $page_id;

    // DIAG - Diagnostics

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
// Hook into wp_footer
add_action('wp_footer', 'chatbot_chatgpt_appearance_inject_custom_css_settings');

// MathJax Settings - Ver 2.2.4
function chatbot_chatgpt_enable_mathjax_callback() {
    $chatbot_chatgpt_enable_mathjax = esc_attr(get_option('chatbot_chatgpt_enable_mathjax', 'Yes'));
    ?>
    <label for="chatbot_chatgpt_enable_mathjax"></label><select id="chatbot_chatgpt_enable_mathjax" name="chatbot_chatgpt_enable_mathjax">
        <option value="Yes" <?php selected( $chatbot_chatgpt_enable_mathjax, 'Yes' ); ?>><?php echo esc_html( 'Yes' ); ?></option>
        <option value="No" <?php selected( $chatbot_chatgpt_enable_mathjax, 'No' ); ?>><?php echo esc_html( 'No' ); ?></option>
    </select>
    <?php
}

// Custom Icons Section Callback - Ver 2.2.2
function chatbot_chatgpt_appearance_icons_appearance_section_callback() {
    ?>
    <p>Enter a valid URL for your custom icons for the chatbot below.  Once you save your settings, the new icon will be display here and where ever used within the application.</p>
    <?php
}

// Custom Icons Overview - Ver 2.2.2
function chatbot_chatgpt_appearance_icons_overview_section_callback(){
    ?>
    <div>
        <p>Choose the icons that best represent you and your brand. You can change your icons at any time.</p>
        <p><b><i>Don't forget to click </i><code>Save Settings</code><i> to save any changes you might make.</i></b></p>
        <p style="background-color: #e0f7fa; padding: 10px;"><b>For an explanation on how to use Custom Icons settings and additional documentation please click <a href="?page=chatbot-chatgpt&tab=support&dir=appearance&file=custom-icons.md">here</a>.</b></p>
    </div>
    <?php
}

// Custom Open Icon - Ver 2.2.2
function chatbot_chatgpt_appearance_open_icon_callback() {
    $chatbot_chatgpt_appearance_open_icon = esc_attr(get_option('chatbot_chatgpt_appearance_open_icon', ''));
    ?>
    <img decoding="async" src="<?php echo chatbot_chatgpt_appearance_icon_path('open_icon'); ?>" alt="Open Icon" style="width: 20px; height: 20px;" />
    <input type="text" id="chatbot_chatgpt_appearance_open_icon" name="chatbot_chatgpt_appearance_open_icon" value="<?php echo $chatbot_chatgpt_appearance_open_icon; ?>" size="50" />
    <?php
}

// Custom Collapse Icon - Ver 2.2.2
function chatbot_chatgpt_appearance_collapse_icon_callback() {
    $chatbot_chatgpt_appearance_collapse_icon = esc_attr(get_option('chatbot_chatgpt_appearance_collapse_icon', ''));
    ?>
    <img decoding="async" src="<?php echo chatbot_chatgpt_appearance_icon_path('collapse_icon'); ?>" alt="Collapse Icon" style="width: 20px; height: 20px;" />
    <input type="text" id="chatbot_chatgpt_appearance_collapse_icon" name="chatbot_chatgpt_appearance_collapse_icon" value="<?php echo $chatbot_chatgpt_appearance_collapse_icon; ?>" size="50" />
    <?php
}

// Custom Erase Icon - Ver 2.2.2
function chatbot_chatgpt_appearance_erase_icon_callback() {
    $chatbot_chatgpt_appearance_erase_icon = esc_attr(get_option('chatbot_chatgpt_appearance_erase_icon', ''));
    ?>
    <img decoding="async" src="<?php echo chatbot_chatgpt_appearance_icon_path('erase_icon'); ?>" alt="Erase Icon" style="width: 20px; height: 20px;" />
    <input type="text" id="chatbot_chatgpt_appearance_erase_icon" name="chatbot_chatgpt_appearance_erase_icon" value="<?php echo $chatbot_chatgpt_appearance_erase_icon; ?>" size="50" />
    <?php
}

// Custom Mic Enabled Icon - Ver 2.2.2
function chatbot_chatgpt_appearance_mic_enabled_icon_callback() {
    $chatbot_chatgpt_appearance_mic_enabled_icon = esc_attr(get_option('chatbot_chatgpt_appearance_mic_enabled_icon', ''));
    ?>
    <img decoding="async" src="<?php echo chatbot_chatgpt_appearance_icon_path('mic_enabled_icon'); ?>"  alt="Mic Enabled Icon" style="width: 20px; height: 20px;" />
    <input type="text" id="chatbot_chatgpt_mic_appearance_enabled_icon" name="chatbot_chatgpt_appearance_mic_enabled_icon" value="<?php echo $chatbot_chatgpt_appearance_mic_enabled_icon; ?>" size="50" />
    <?php
}

// Custom Mic Disabled Icon - Ver 2.2.2
function chatbot_chatgpt_appearance_mic_disabled_icon_callback() {
    $chatbot_chatgpt_appearance_mic_disabled_icon = esc_attr(get_option('chatbot_chatgpt_appearance_mic_disabled_icon', ''));
    ?>
    <img decoding="async" src="<?php echo chatbot_chatgpt_appearance_icon_path('mic_disabled_icon'); ?>" lt="Mic Disabled Icon" style="width: 20px; height: 20px;" />
    <input type="text" id="chatbot_chatgpt_appearance_mic_disabled_icon" name="chatbot_chatgpt_appearance_mic_disabled_icon" value="<?php echo $chatbot_chatgpt_appearance_mic_disabled_icon; ?>" size="50" />
    <?php
}

// Custom Send Icon - Ver 2.2.2
function chatbot_chatgpt_appearance_send_icon_callback() {
    $chatbot_chatgpt_appearance_send_icon = esc_attr(get_option('chatbot_chatgpt_appearance_send_icon', ''));
    ?>
    <img decoding="async" src="<?php echo chatbot_chatgpt_appearance_icon_path('send_icon'); ?>" alt="Send Icon" style="width: 20px; height: 20px;" />
    <input type="text" id="chatbot_chatgpt_appearance_send_icon" name="chatbot_chatgpt_appearance_send_icon" value="<?php echo $chatbot_chatgpt_appearance_send_icon; ?>" size="50" />
    <?php
}

// Custom Attach Icon - Ver 2.2.2
function chatbot_chatgpt_appearance_attach_icon_callback() {
    $chatbot_chatgpt_appearance_attach_icon = esc_attr(get_option('chatbot_chatgpt_appearance_attach_icon', ''));
    ?>
    <img decoding="async" src="<?php echo chatbot_chatgpt_appearance_icon_path('attach_icon'); ?>" alt="Attach Icon" style="width: 20px; height: 20px;" />
    <input type="text" id="chatbot_chatgpt_appearance_attach_icon" name="chatbot_chatgpt_appearance_attach_icon" value="<?php echo $chatbot_chatgpt_appearance_attach_icon; ?>" size="50" />
    <?php
}

// Custom Read Aloud Icon - Ver 2.2.2
function chatbot_chatgpt_appearance_read_aloud_icon_callback() {
    $chatbot_chatgpt_appearance_read_aloud_icon = esc_attr(get_option('chatbot_chatgpt_appearance_read_aloud_icon', ''));
    ?>
    <img decoding="async" src="<?php echo chatbot_chatgpt_appearance_icon_path('read_aloud_icon'); ?>" alt="Read Aloud Icon" style="width: 20px; height: 20px;" />
    <input type="text" id="chatbot_chatgpt_appearance_read_aloud_icon" name="chatbot_chatgpt_appearance_read_aloud_icon" value="<?php echo $chatbot_chatgpt_appearance_read_aloud_icon; ?>" size="50" />
    <?php
}

// Custom Download Icon - Ver 2.2.2
function chatbot_chatgpt_appearance_download_icon_callback() {
    $chatbot_chatgpt_appearance_download_icon = esc_attr(get_option('chatbot_chatgpt_appearance_download_icon', ''));
    ?>
    <img decoding="async" src="<?php echo chatbot_chatgpt_appearance_icon_path('download_icon'); ?>" alt="Download Icon" style="width: 20px; height: 20px;" />
    <input type="text" id="chatbot_chatgpt_appearance_download_icon" name="chatbot_chatgpt_appearance_download_icon" value="<?php echo $chatbot_chatgpt_appearance_download_icon; ?>" size="50" />
    <?php
}

// Function to set custom icons
function chatbot_chatgpt_appearance_icon_path($icon_common_name) {

    // DIAG - Diagnostics

    switch ($icon_common_name) {
        case 'open_icon':
            // EXAMPLE: chatbot_chatgpt_appearance_icon_path('open_icon')
            $icon_path = esc_attr(get_option('chatbot_chatgpt_appearance_open_icon', ''));
            if ( $icon_path == '') {
                $icon_path = plugins_url('../../assets/icons/chat_FILL0_wght400_GRAD0_opsz24.png', __FILE__);
            }
            break;
        case 'collapse_icon':
            // EXAMPLE: chatbot_chatgpt_appearance_icon_path('collapse_icon')
            $icon_path = esc_attr(get_option('chatbot_chatgpt_appearance_collapse_icon', ''));
            if ( $icon_path == '') {
                $icon_path = plugins_url('../../assets/icons/close_FILL0_wght400_GRAD0_opsz24.png', __FILE__);
            }
            break;
        case 'erase_icon':
            // EXAMPLE: chatbot_chatgpt_appearance_icon_path('erase_icon')
            $icon_path = esc_attr(get_option('chatbot_chatgpt_appearance_erase_icon', ''));
            if ( $icon_path == '') {
                $icon_path = plugins_url('../../assets/icons/delete_FILL0_wght400_GRAD0_opsz24.png', __FILE__);
            }
            break;
        case 'mic_enabled_icon':
            // EXAMPLE: chatbot_chatgpt_appearance_icon_path('mic_enabled_icon')
            $icon_path = esc_attr(get_option('chatbot_chatgpt_appearance_mic_enabled_icon', ''));
            if ( $icon_path == '') {
                $icon_path = plugins_url('../../assets/icons/mic_24dp_000000_FILL0_wght400_GRAD0_opsz24.png', __FILE__);
            }
            break;
        case 'mic_disabled_icon':
            // EXAMPLE: chatbot_chatgpt_appearance_icon_path('mic_disabled_icon')
            $icon_path = esc_attr(get_option('chatbot_chatgpt_appearance_mic_disabled_icon', ''));
            if ( $icon_path == '') {
                $icon_path = plugins_url('../../assets/icons/mic_off_24dp_000000_FILL0_wght400_GRAD0_opsz24.png', __FILE__);
            }
            break;
        case 'send_icon':
            // EXAMPLE: chatbot_chatgpt_appearance_icon_path('send_icon')
            $icon_path = esc_attr(get_option('chatbot_chatgpt_appearance_send_icon', ''));
            if ( $icon_path == '') {
                $icon_path = plugins_url('../../assets/icons/send_FILL0_wght400_GRAD0_opsz24.png', __FILE__);
                // error_log ('[Chatbot] [chatbot-settings-appearance.php] send_icon: ' . $icon_path);
            }   
            break;
        case 'attach_icon':
            // EXAMPLE: chatbot_chatgpt_appearance_icon_path('attach_icon')
            $icon_path = esc_attr(get_option('chatbot_chatgpt_appearance_attach_icon', ''));
            if ( $icon_path == '') {
                $icon_path = plugins_url('../../assets/icons/attach_file_FILL0_wght400_GRAD0_opsz24.png', __FILE__);
            }
            break;
        case 'read_aloud_icon':
            // EXAMPLE: chatbot_chatgpt_appearance_icon_path('read_aloud_icon')
            $icon_path = esc_attr(get_option('chatbot_chatgpt_appearance_read_aloud_icon', ''));
            if ( $icon_path == '') {
                $icon_path = plugins_url('../../assets/icons/text_to_speech_FILL0_wght400_GRAD0_opsz24.png', __FILE__);
            }
            break;
        case 'download_icon':
            // EXAMPLE: chatbot_chatgpt_appearance_icon_path('download_icon')
            $icon_path = esc_attr(get_option('chatbot_chatgpt_appearance_download_icon', ''));
            if ( $icon_path == '') {
                $icon_path = plugins_url('../../assets/icons/download_FILL0_wght400_GRAD0_opsz24.png', __FILE__);
            }
            break;
        default:
            $icon_path = '';
    }

    return $icon_path;
    
}
