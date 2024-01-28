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

function chatbot_chatgpt_appearance_settings() {
    
    register_setting('chatbot_chatgpt_appearance', 'chatbot_chatgpt_appearance_background_color');
    register_setting('chatbot_chatgpt_appearance', 'chatbot_chatgpt_appearance_header_background_color');
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
        'chatbot_chatgpt_appearance_reset',
        'Restore Defaults',
        'chatbot_chatgpt_appearance_reset_callback',
        'chatbot_chatgpt_appearance',
        'chatbot_chatgpt_appearance_section'
    );

}
add_action('admin_init', 'chatbot_chatgpt_appearance_settings');


// Custom Appearance Settings - Ver 1.8.1
function chatbot_chatgpt_appearance_section_callback($args) {
    ?>
    <div>
        <p>Choose an color combinations that best represents you and your brand.  You can change your color combinations at any time.</p>
        <p><b><i>Don\'t forget to click \'Save Settings\' to save your changes.</i><b></p>
    </div>
    <?php
}

// Set the chatbot background color
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
    if(empty($chatbot_chatgpt_appearance_background_color)) {
        // Show an error message or handle the error
        chatbot_chatgpt_general_admin_notice( 'Background color cannot be blank.');
        update_option('chatbot_chatgpt_appearance_background_color', '#f1f1f1');
    } else {
        // Save the value
        update_option('chatbot_chatgpt_appearance_background_color', $chatbot_chatgpt_appearance_background_color);
    }
}

// Now override the css with the color chosen by the user
function chatbot_chatgpt_appearance_background_custom_css_settings() {
    $chatbot_chatgpt_appearance_background_color = esc_attr(get_option('chatbot_chatgpt_appearance_background_color', '#f1f1f1'));
    ?>
    <style type="text/css">
        .chatbot-bubble {
            background-color: <?php echo $chatbot_chatgpt_appearance_background_color; ?> !important;
        }
        .floating-style {
            background-color: <?php echo $chatbot_chatgpt_appearance_background_color; ?> !important;
        }
        .embedded-style {
            background-color: <?php echo $chatbot_chatgpt_appearance_background_color; ?> !important;
        }
    </style>
    <?php
}
add_action('wp_head', 'chatbot_chatgpt_appearance_background_custom_css_settings');

// Set the chatbot background color
function chatbot_chatgpt_appearance_header_background_color_callback() {
    // Get the value of the setting we've registered with register_setting()
    $chatbot_chatgpt_appearance_header_background_color = esc_attr(get_option('chatbot_chatgpt_appearance_header_background_color', '#f1f1f1'));
    ?>
    <input type="text" id="chatbot_chatgpt_appearance_header_background_color"
        name="chatbot_chatgpt_appearance_header_background_color"
        value="<?php echo esc_attr($chatbot_chatgpt_appearance_header_background_color); ?>"
        class="my-color-field"
    />
    <?php
    if(empty($chatbot_chatgpt_appearance_header_background_color)) {
        // Show an error message or handle the error
        chatbot_chatgpt_general_admin_notice( 'Header background color cannot be blank.');
        update_option('chatbot_chatgpt_appearance_header_background_color', '#222');
    } else {
        // Save the value
        update_option('chatbot_chatgpt_appearance_header_background_color', $chatbot_chatgpt_appearance_header_background_color);
    }
}

// Now override the css with the color chosen by the user
function chatbot_chatgpt_appearance_header_background_custom_css_settings() {
    $chatbot_chatgpt_appearance_header_background_color = esc_attr(get_option('chatbot_chatgpt_appearance_header_background_color', '#f1f1f1'));
    ?>
    <style type="text/css">
        #chatbot-chatgpt-header {
            background-color: <?php echo $chatbot_chatgpt_appearance_header_background_color; ?> !important;
        }
    </style>
    <?php
}
add_action('wp_head', 'chatbot_chatgpt_appearance_header_background_custom_css_settings');


// Appearance Settings - Ver 1.8.1
function chatbot_chatgpt_appearance_reset_callback($args) {
    $chatbot_chatgpt_appearance_reset = esc_attr(get_option('chatbot_chatgpt_appearance_reset', 'No'));
    ?>
    <select id="chatbot_chatgpt_appearance_reset" name = "chatbot_chatgpt_appearance_reset">
        <option value="Yes" <?php selected( $chatbot_chatgpt_appearance_reset, 'Yes' ); ?>><?php echo esc_html( 'Yes' ); ?></option>
        <option value="No" <?php selected( $chatbot_chatgpt_appearance_reset, 'No' ); ?>><?php echo esc_html( 'No' ); ?></option>
    </select>
    <?php
}

// Restore Defaults - Ver 1.8.1
function chatbot_chatgpt_appearance_restore_default_settings() {
    // DIAG - Enter function
    chatbot_chatgpt_back_trace( 'NOTICE', 'Enter function: chatbot_chatgpt_appearance_restore_default_settings()');
    $chatbot_chatgpt_appearance_reset = 'No';
    update_option('chatbot_chatgpt_appearance_reset', $chatbot_chatgpt_appearance_reset);
    $chatbot_chatgpt_appearance_background_color = '#f1f1f1';
    update_option('chatbot_chatgpt_appearance_background_color', $chatbot_chatgpt_appearance_background_color);
    $chatbot_chatgpt_appearance_header_background_color = '#222';
    update_option('chatbot_chatgpt_appearance_header_background_color', $chatbot_chatgpt_appearance_header_background_color);
    ?>
    <style type="text/css">
        .chatbot-bubble {
            background-color: <?php echo $chatbot_chatgpt_appearance_background_color; ?> !important;
        }
        .floating-style {
            background-color: <?php echo $chatbot_chatgpt_appearance_background_color; ?> !important;
        }
        .embedded-style {
            background-color: <?php echo $chatbot_chatgpt_appearance_background_color; ?> !important;
        }
        #chatbot-chatgpt-header {
            background-color: #222 !important;
        }
    </style>
    <?php
    // DIAG - Exit function
    chatbot_chatgpt_back_trace( 'NOTICE', 'Exit function: chatbot_chatgpt_appearance_restore_default_settings()');
}
