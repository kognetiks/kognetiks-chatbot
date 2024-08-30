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

// Plugin directory path
global $chatbot_chatgpt_plugin_dir_path;
// $chatbot_chatgpt_plugin_dir_path = plugin_dir_path( __FILE__ );
// error_log('chatbot_chatgpt_plugin_dir_path: ' . $chatbot_chatgpt_plugin_dir_path);

// Plugin directory URL
global $chatbot_chatgpt_plugin_dir_url;
// $chatbot_chatgpt_plugin_dir_url = plugins_url( '/', __FILE__ );
// error_log('chatbot_chatgpt_plugin_dir_url: ' . $chatbot_chatgpt_plugin_dir_url);

// Include necessary files - Widgets - Ver 2.1.3
require_once $chatbot_chatgpt_plugin_dir_path . 'widgets/chatbot-chatgpt-widget-logging.php';

// If remote access is not allowed, abort.
$chatbot_chatgpt_enable_remote_widget = esc_attr(get_option('chatbot_chatgpt_enable_remote_widget', 'No'));

if ($chatbot_chatgpt_enable_remote_widget !== 'Yes') {

    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    chatbot_chatgpt_widget_logging('Remote access is not allowed for ' . $referer );
    die;

}

// Allowed domain examples - Ver 2.1.3
// $allowed_domains = [
//     'localhost',
//     'kognetiks.com',
// ];

// Retrieve the allowed domains from the WordPress options
$allowed_domains_string = esc_attr(get_option('chatbot_chatgpt_allowed_remote_domains', ''));

// Convert the comma-separated string to an array
$allowed_domains = array_map('trim', explode(',', $allowed_domains_string));

// Log the allowed domains for debugging purposes
chatbot_chatgpt_widget_logging('Allowed Domains: ' . $allowed_domains_string);

// Check if allowed domains list is empty
if (empty($allowed_domains_string)) {

    $is_allowed = false;

} else {

    // Check the HTTP_REFERER to ensure the request is from an allowed server
    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

    // Normalize referer to strip www if present
    $normalized_referer = preg_replace('/^www\./', '', parse_url($referer, PHP_URL_HOST));

    $is_allowed = false;
    foreach ($allowed_domains as $allowed_domain) {

        // Normalize allowed domain to strip www if present
        $normalized_domain = preg_replace('/^www\./', '', $allowed_domain);

        if (!empty($normalized_domain) && strpos($normalized_referer, $normalized_domain) !== false) {

            $is_allowed = true;
            // Log the referer for accounting, monitoring, and debugging purposes
            chatbot_chatgpt_widget_logging('Allowed: ' . $referer);
            break;
        }
    }
}

if (!$is_allowed) {
    // Log the referer for accounting, monitoring, and debugging purposes
    chatbot_chatgpt_widget_logging('Unauthorized access: ' . $referer);
    die;
}

// Access the global shortcodes array
global $shortcode_tags;

// Get the shortcode parameter from the URL and sanitize it
$shortcode_param = isset($_GET['assistant']) ? sanitize_text_field($_GET['assistant']) : '';

// Check if the sanitized shortcode exists in the list of registered shortcodes
if (!array_key_exists($shortcode_param, $shortcode_tags)) {

    // Log the referer for accounting, monitoring, and debugging purposes
    // chatbot_chatgpt_remote_access( 'Allowed: ' . $referer . ' Invalid shortcode: ' . $shortcode_param );
    error_log ( 'Allowed: ' . $referer . ' Invalid shortcode: ' . $shortcode_param );
    // Terminate script if the shortcode is not registered
    chatbot_chatgpt_widget_logging('Invalid shortcode');
    die;

} else {

    // Log the referer for accounting, monitoring, and debugging purposes
    // chatbot_chatgpt_remote_access( 'Allowed: ' . $referer . ' Valid shortcode: ' . $shortcode_param );
    chatbot_chatgpt_widget_logging('Allowed: ' . $referer . ' Valid shortcode: ' . $shortcode_param );

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
    'chatbot_chatgpt_start_status' => esc_attr(get_option('chatbot_chatgpt_start_status', 'closed')),
    'chatbot_chatgpt_start_status_new_visitor' => esc_attr(get_option('chatbot_chatgpt_start_status_new_visitor', 'closed')),
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

        // Set values for the chatbot
        var kchat_settings = <?php echo $kchat_settings_json; ?>;

        // Set values in local storage
        localStorage.setItem('chatbot_chatgpt_opened', 'true');
        localStorage.setItem('chatbot_chatgpt_start_status', 'open');
        localStorage.setItem('chatbot_chatgpt_start_status_new_visitor', 'open');
        
    </script>
</body>
</html>

