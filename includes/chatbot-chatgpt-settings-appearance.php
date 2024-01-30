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

// Set the chatbot background color
function chatbot_chatgpt_appearance_background_color_callback(): void {
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
function chatbot_chatgpt_appearance_background_custom_css_settings(): void {
    $chatbot_chatgpt_appearance_background_color = esc_attr(get_option('chatbot_chatgpt_appearance_background_color', '#f1f1f1'));
    ?>
    <style>
        .chatbot-bubble {
            background-color: <?php echo $chatbot_chatgpt_appearance_background_color; ?> !important;
        }
        .floating-style {
            background-color: <?php echo $chatbot_chatgpt_appearance_background_color; ?> !important;
        }
        .embedded-style {
            background-color: <?php echo $chatbot_chatgpt_appearance_background_color; ?> !important;
        }
        #chatbot-chatgpt-submit {
            background-color: <?php echo $chatbot_chatgpt_appearance_background_color; ?> !important;
        }
        #chatbot-chatgpt-upload-file {
            background-color: <?php echo $chatbot_chatgpt_appearance_background_color; ?> !important;
        }
    </style>
    <?php
}
add_action('wp_head', 'chatbot_chatgpt_appearance_background_custom_css_settings');

// Set the chatbot background color
function chatbot_chatgpt_appearance_header_background_color_callback(): void {
    // Get the value of the setting we've registered with register_setting()
    $chatbot_chatgpt_appearance_header_background_color = esc_attr(get_option('chatbot_chatgpt_appearance_header_background_color', '#222222'));
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
        update_option('chatbot_chatgpt_appearance_header_background_color', '#222222');
    } else {
        // Save the value
        update_option('chatbot_chatgpt_appearance_header_background_color', $chatbot_chatgpt_appearance_header_background_color);
    }
}

// Now override the css with the color chosen by the user
function chatbot_chatgpt_appearance_header_background_custom_css_settings(): void {
    $chatbot_chatgpt_appearance_header_background_color = esc_attr(get_option('chatbot_chatgpt_appearance_header_background_color', '#222222'));
    ?>
    <style>
        #chatbot-chatgpt-header {
            background-color: <?php echo $chatbot_chatgpt_appearance_header_background_color; ?> !important;
        }
    </style>
    <?php
}
add_action('wp_head', 'chatbot_chatgpt_appearance_header_background_custom_css_settings');


// Set the chatbot text color
function chatbot_chatgpt_appearance_text_color_callback(): void {
    // Get the value of the setting we've registered with register_setting()
    $chatbot_chatgpt_appearance_text_color = esc_attr(get_option('chatbot_chatgpt_appearance_text_color', '#ffffff'));
    ?>
    <input type="text" id="chatbot_chatgpt_appearance_text_color"
        name="chatbot_chatgpt_appearance_text_color"
        value="<?php echo esc_attr($chatbot_chatgpt_appearance_text_color); ?>"
        class="my-color-field"
    />
    <?php
    if(empty($chatbot_chatgpt_appearance_text_color)) {
        // Show an error message or handle the error
        chatbot_chatgpt_general_admin_notice( 'Text color cannot be blank.');
        update_option('chatbot_chatgpt_appearance_text_color', '#ffffff');
    } else {
        // Save the value
        update_option('chatbot_chatgpt_appearance_text_color', $chatbot_chatgpt_appearance_text_color);
    }
}

// Now override the css with the color chosen by the user
function chatbot_chatgpt_appearance_text_custom_css_settings(): void {
    $chatbot_chatgpt_appearance_text_color = esc_attr(get_option('chatbot_chatgpt_appearance_text_color', '#ffffff'));
    ?>
    <style>
        .chatbot-bubble {
            color: <?php echo $chatbot_chatgpt_appearance_text_color; ?> !important;
        }
        .floating-style {
            color: <?php echo $chatbot_chatgpt_appearance_text_color; ?> !important;
        }
        .embedded-style {
            color: <?php echo $chatbot_chatgpt_appearance_text_color; ?> !important;
        }
        .user-text {
            color: <?php echo $chatbot_chatgpt_appearance_text_color; ?> !important;
        }
        .bot-text {
            color: <?php echo $chatbot_chatgpt_appearance_text_color; ?> !important;
        }
        #chatgptTitle.title {
            color: <?php echo $chatbot_chatgpt_appearance_text_color; ?> !important;
        }
        .typing-dot {
            color: <?php echo $chatbot_chatgpt_appearance_text_color; ?> !important;
        }
        .chatbot-chatgpt-custom-button-class {
            color: <?php echo $chatbot_chatgpt_appearance_text_color; ?> !important;
        }
    </style>
    <?php
}

// Set the chatbot user text background color
function chatbot_chatgpt_appearance_user_text_background_color_callback(): void {
    // Get the value of the setting we've registered with register_setting()
    $chatbot_chatgpt_appearance_user_text_background_color = esc_attr(get_option('chatbot_chatgpt_appearance_user_text_background_color', '#007bff'));
    ?>
    <input type="text" id="chatbot_chatgpt_appearance_user_text_background_color"
        name="chatbot_chatgpt_appearance_user_text_background_color"
        value="<?php echo esc_attr($chatbot_chatgpt_appearance_user_text_background_color); ?>"
        class="my-color-field"
    />
    <?php
    if(empty($chatbot_chatgpt_appearance_user_text_background_color)) {
        // Show an error message or handle the error
        chatbot_chatgpt_general_admin_notice( 'User text background color cannot be blank.');
        update_option('chatbot_chatgpt_appearance_user_text_background_color', '#007bff');
    } else {
        // Save the value
        update_option('chatbot_chatgpt_appearance_user_text_background_color', $chatbot_chatgpt_appearance_user_text_background_color);
    }

}

// Now override the css with the color chosen by the user
function chatbot_chatgpt_appearance_user_text_background_custom_css_settings(): void {
    $chatbot_chatgpt_appearance_user_text_background_color = esc_attr(get_option('chatbot_chatgpt_appearance_user_text_background_color', '#007bff'));
    ?>
    <style>
        .user-text {
            background-color: <?php echo $chatbot_chatgpt_appearance_user_text_background_color; ?> !important;
        }
    </style>
    <?php
}

// Set the chatbot bot text background color
function chatbot_chatgpt_appearance_bot_text_background_color_callback(): void {
    // Get the value of the setting we've registered with register_setting()
    $chatbot_chatgpt_appearance_bot_text_background_color = esc_attr(get_option('chatbot_chatgpt_appearance_bot_text_background_color', '#5BC236'));
    ?>
    <input type="text" id="chatbot_chatgpt_appearance_bot_text_background_color"
        name="chatbot_chatgpt_appearance_bot_text_background_color"
        value="<?php echo esc_attr($chatbot_chatgpt_appearance_bot_text_background_color); ?>"
        class="my-color-field"
    />
    <?php
    if(empty($chatbot_chatgpt_appearance_bot_text_background_color)) {
        // Show an error message or handle the error
        chatbot_chatgpt_general_admin_notice( 'Bot text background color cannot be blank.');
        update_option('chatbot_chatgpt_appearance_bot_text_background_color', '#5BC236');
    } else {
        // Save the value
        update_option('chatbot_chatgpt_appearance_bot_text_background_color', $chatbot_chatgpt_appearance_bot_text_background_color);
    }
}

// Now override the css with the color chosen by the user
function chatbot_chatgpt_appearance_bot_text_background_custom_css_settings(): void {
    $chatbot_chatgpt_appearance_bot_text_background_color = esc_attr(get_option('chatbot_chatgpt_appearance_bot_text_background_color', '#5BC236'));
    ?>
    <style>
        .bot-text {
            background-color: <?php echo $chatbot_chatgpt_appearance_bot_text_background_color; ?> !important;
        }
        .typing-indicator {
            background-color: <?php echo $chatbot_chatgpt_appearance_bot_text_background_color; ?> !important;
        }
    </style>
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

    // Now delete all the custom appearance settings from the WP DB
    delete_option('chatbot_chatgpt_appearance_background_color');
    delete_option('chatbot_chatgpt_appearance_header_background_color');
    delete_option('chatbot_chatgpt_appearance_text_color');
    delete_option('chatbot_chatgpt_appearance_user_text_background_color');
    delete_option('chatbot_chatgpt_appearance_bot_text_background_color');

    // Now override the css with the default color
    chatbot_chatgpt_appearance_custom_css_settings();

    // DIAG - Exit function
    chatbot_chatgpt_back_trace( 'NOTICE', 'Exit function: chatbot_chatgpt_appearance_restore_default_settings()');

}

// Override the css with the color chosen by the user
function chatbot_chatgpt_appearance_custom_css_settings(): void {
    chatbot_chatgpt_appearance_background_custom_css_settings();
    chatbot_chatgpt_appearance_header_background_custom_css_settings();
    chatbot_chatgpt_appearance_text_custom_css_settings();
    chatbot_chatgpt_appearance_user_text_background_custom_css_settings();
    chatbot_chatgpt_appearance_bot_text_background_custom_css_settings();
}
