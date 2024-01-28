<?php
/**
 * Chatbot ChatGPT for WordPress - Settings - Appearence - Ver 1.8.1
 *
 * This file contains the code for the Chatbot ChatGPT settings page.
 * It handles the appearence settings and other parameters.
 * 
 *
 * @package chatbot-chatgpt
 */

// IDEA - COMING SOON - Ver 1.6.8

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

function chatbot_chatgpt_appearance_settings() {
    // Appearance settings tab - Ver 1.8.1
    register_setting('chatbot_chatgpt_appearance', 'chatbot_chatgpt_appearance_background_color');

    add_settings_section(
        'chatbot_chatgpt_appearance_section',
        'Appearance Settings',
        'chatbot_chatgpt_appearence_section_callback',
        'chatbot_chatgpt_appearance'
    );

    add_settings_field(
        'chatbot_chatgpt_appearance_background_color',
        'Background Color',
        'chatbot_chatgpt_appearance_background_color_callback',
        'chatbot_chatgpt_appearance',
        'chatbot_chatgpt_appearance_section'
    );
}
add_action('admin_init', 'chatbot_chatgpt_appearance_settings');


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
    $chatbot_chatgpt_appearance_background_color = esc_attr(get_option('chatbot_chatgpt_appearance_background_color', '#f1f1f1'));
    ?>
    <input type="text" id="chatbot_chatgpt_appearance_background_color"
        name="chatbot_chatgpt_appearance_background_color"
        value="<?php echo esc_attr($chatbot_chatgpt_appearance_background_color); ?>"
        class="my-color-field"
    />
    <?php
    $chatbot_chatgpt_appearance_background_color = isset($_POST['chatbot_chatgpt_appearance_background_color']) ? trim($_POST['chatbot_chatgpt_appearance_background_color']) : '';

    if(empty($chatbot_chatgpt_appearance_background_color)) {
        // Show an error message or handle the error
        chatbot_chatgpt_general_admin_notice( 'Background color cannot be blank. ');
    } else {
        // Save the value
        update_option('chatbot_chatgpt_appearance_background_color', $chatbot_chatgpt_appearance_background_color);
    }
}


