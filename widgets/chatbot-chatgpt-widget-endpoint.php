<?php
/**
 * Kognetiks Chatbot for WordPress - Chatbot ChatGPT WIDGET ENDPOINT - Ver 2.1.3
 *
 * This file contains the code accessing the Chatbot ChatGPT endpoint.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
// if ( ! defined( 'WPINC' ) ) {
//     die;
// }

// Load WordPress
$path = dirname(__FILE__);
while (!file_exists($path . '/wp-load.php') && $path != dirname($path)) {
    $path = dirname($path);
}

if (file_exists($path . '/wp-load.php')) {
    include($path . '/wp-load.php');
} else {
    die('wp-load.php not found');
}

// Access the global shortcodes array
global $shortcode_tags;

// Get the shortcode parameter from the URL and sanitize it
$shortcode_param = isset($_GET['assistant']) ? sanitize_text_field($_GET['assistant']) : 'chatbot-1';

// Check if the sanitized shortcode exists in the list of registered shortcodes
if (!array_key_exists($shortcode_param, $shortcode_tags)) {
    // Terminate script if the shortcode is not registered
    die('Invalid shortcode');
}

// Since we're confident that $shortcode_param is a valid registered shortcode,
// it's safe to pass it to the do_shortcode function.
$chatbot_html = do_shortcode('[' . esc_html($shortcode_param) . ']');

// Set the initial chatbot settings
if (is_user_logged_in()) {
    $kchat_settings['chatbot_chatgpt_message_limit_setting'] = esc_attr(get_option('chatbot_chatgpt_message_limit_setting', '999'));
} else {
    $kchat_settings['chatbot_chatgpt_message_limit_setting'] = esc_attr(get_option('chatbot_chatgpt_visitor_message_limit_setting', '999'));
}

// Localize the data for the chatbot - Ver 2.1.1.1
$kchat_settings = array_merge($kchat_settings,array(
    'chatbot-chatgpt-version' => esc_attr($chatbot_chatgpt_plugin_version),
    'plugins_url' => esc_url($chatbot_chatgpt_plugin_dir_url),
    'ajax_url' => esc_url(admin_url('admin-ajax.php')),
    'user_id' => esc_html($user_id),
    'session_id' => esc_html($session_id),
    'page_id' => esc_html(999999),
    'model' => esc_html($model),
    'voice' => esc_html($voice),
    'chatbot_chatgpt_timeout_setting' => esc_attr(get_option('chatbot_chatgpt_timeout_setting', '240')),
    'chatbot_chatgpt_avatar_icon_setting' => esc_attr(get_option('chatbot_chatgpt_avatar_icon_setting', '')),
    'chatbot_chatgpt_custom_avatar_icon_setting' => esc_attr(get_option('chatbot_chatgpt_custom_avatar_icon_setting', '')),
    'chatbot_chatgpt_avatar_greeting_setting' => esc_attr(get_option('chatbot_chatgpt_avatar_greeting_setting', 'Howdy!!! Great to see you today! How can I help you?')),
    'chatbot_chatgpt_force_page_reload' => esc_attr(get_option('chatbot_chatgpt_force_page_reload', 'No')),
    'chatbot_chatgpt_custom_error_message' => esc_attr(get_option('chatbot_chatgpt_custom_error_message', 'Your custom error message goes here.')),
    'chatbot_chatgpt_message_limit_setting' => esc_attr(get_option('chatbot_chatgpt_message_limit_setting', '999')),
));

$kchat_settings_json = wp_json_encode($kchat_settings);

// Output the HTML and necessary scripts
?>
<!DOCTYPE html>
<html>
<head>
    <?php wp_head(); // Ensure all WordPress head actions are triggered ?>
    <style>
        /* Include any additional styles needed */
        body, html {
            background: transparent !important;
        }
        .chatbot-wrapper {
            width: 1000px;
            max-width: 600px;
            margin: 0 auto;
            height: 600px;
            max-height: 550px;
            overflow: hidden;
            position: fixed;
            bottom: 10px;
            right: 10px;
            background: transparent;
            z-index: 9999;
            }
        #chatbot-chatgpt {
            height: 550px !important;
            width: 500px !important
        }
    </style>
</head>
<body>
    <div class="chatbot-wrapper">
        <?php echo $chatbot_html; ?>
    </div>
    <?php wp_footer(); // Ensure all WordPress footer actions are triggered ?>
    <script type="text/javascript">
        var kchat_settings = <?php echo $kchat_settings_json; ?>;

Yes, you can set values in local storage using JavaScript within the script block in your HTML. Here’s how you can modify your script to set the specified local storage values:

Updated Script Section
Add the following JavaScript code to set the values in local storage:

php
Copy code
<script type="text/javascript">
    document.addEventListener("DOMContentLoaded", function() {

        // Set values for the chatbot
        var kchat_settings = <?php echo $kchat_settings_json; ?>;

        // Set values in local storage
        localStorage.setItem('chatbot_chatgpt_opened', 'true');
        localStorage.setItem('chatbot_chatgpt_start_status', 'open');
        localStorage.setItem('chatbot_chatgpt_start_status_new_visitor', 'open');
        
    </script>
</body>
</html>

