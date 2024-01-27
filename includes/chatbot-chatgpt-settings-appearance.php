<?php
/**
 * Chatbot ChatGPT for WordPress - Settings - Adaptive Skins
 *
 * This file contains the code for the Chatbot ChatGPT settings page.
 * It handles the adaptive skins settings and other parameters.
 * 
 *
 * @package chatbot-chatgpt
 */

// IDEA - COMING SOON - Ver 1.6.8

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Function to add the adaptive appearance settings to the Chatbot ChatGPT settings page
// function chatbot_chatgpt_settings_appearance() {

//     // Register the settings
//     register_setting('chatbot_chatgpt_settings_appearance', 'chatbot_chatgpt_appearance');

//     // Add the adaptive appearance settings section
//     add_settings_section(
//         'chatbot_chatgpt_settings_appearance_section',
//         'Adaptive Appearance Settings',
//         'chatbot_chatgpt_settings_appearance_section_callback',
//         'chatbot_chatgpt_settings_appearance'
//     );

//     // Add the adaptive appearance settings fields
//     add_settings_field(
//         'chatbot_chatgpt_settings_appearance_field',
//         'Adaptive Appearance Settings',
//         'chatbot_chatgpt_settings_appearance_field_callback',
//         'chatbot_chatgpt_settings_appearance',
//         'chatbot_chatgpt_settings_appearance_section'
//     );

// }

// Custom Avatar Icon - Ver 1.5.0
function chatbot_chatgpt_appearence_section_callback($args) {
    echo '<div>
        <p>Choose an color combinations that best represents you and your brand.  You can change your color combinations at any time.</p>
        <p><b><i>Don\'t forget to click \'Save Settings\' to save your changes.</i><b></p>
    </div>';
}

// Add color picker for background color
function chatbot_chatgpt_appearance_background_color_callback() {
    // Get the value of the setting we've registered with register_setting()
    $options = get_option('chatbot_chatgpt_appearance_background_color');
    ?>
    <input type="text" id="background_color"
        name="chatbot_chatgpt_appearance_background_color_"
        value="<?php echo esc_attr($options['chatbot_chatgpt_appearance_background_color']); ?>"
        class="my-color-field" />
    <?php
}



// function chatbot_chatgpt_skins_enqueue_styles() {

//     // DIAG - Diagnostics
//     // chatbot_chatgpt_back_trace( 'NOTICE', );
    
//     $primary_color = get_theme_mod('primary_color', '#000000'); // Default to black if not set

//     $custom_css = "
//         .parent-class chatbot-chatgpt {
//             background-color: {$primary_color} !important;
//         }
//         .parent-class chatbot-chatgpt .chatbot-chatgpt-header {
//             background-color: {$primary_color} !important;
//         }";
//     wp_add_inline_style('chatbot-chatgpt', $custom_css);

//     // DIAG - Diagnostics
//     // chatbot_chatgpt_back_trace( 'NOTICE', '$custom_css: ' . $custom_css);

// }
// add_action('wp_enqueue_scripts', 'chatbot_chatgpt_skins_enqueue_styles');
