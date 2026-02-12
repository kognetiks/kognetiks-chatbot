<?php
/**
 * Kognetiks Chatbot - Chatbot WIDGET ENDPOINT - Ver 2.1.3 - Updated Ver 2.2.7 - 2025-03-21
 *
 * This file contains the code accessing the Chatbot ChatGPT endpoint.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
// if ( ! defined( 'WPINC' ) ) {
//     die();
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

// Include necessary files - Widgets - Ver 2.1.3
require_once plugin_dir_path( __FILE__ ) . 'chatbot-widget-logging.php';

// If remote access is not allowed, abort.
if ($chatbot_ai_platform_choice === 'Azure OpenAI') {
    $chatbot_enable_remote_widget = esc_attr(get_option('chatbot_azure_enable_remote_widget', 'No'));
} else {
    $chatbot_enable_remote_widget = esc_attr(get_option('chatbot_chatgpt_enable_remote_widget', 'No'));
}

if ($chatbot_enable_remote_widget !== 'Yes') {
    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    chatbot_widget_logging('Remote access is not allowed', $referer );
    die();
} else {
    // Log the referer for accounting, monitoring, and debugging purposes
    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    $request_ip = getUserIP();
    chatbot_widget_logging('Remote access is allowed' , $referer , $request_ip);
}

// Allowed domain, shortcode examples - Ver 2.1.3
// $allowed_domains = [
//     'localhost,chatbot-4',
//     'kognetiks.com,chatbot-4',
// ];

// Belt and suspenders: Ensure the shortcodes are registered before proceeding
register_chatbot_shortcodes();

// Access the global shortcodes array
global $shortcode_tags;

// Fetch the list of shortcodes
$shortcode_names = array_keys($shortcode_tags);
// Output the registered shortcodes for debugging purposes
// foreach ($shortcode_names as $shortcode_name) {
//     chatbot_widget_logging('Registered Shortcode', $shortcode_name);
// }

// Get the shortcode parameter from the URL and sanitize it
$shortcode_param = isset($_GET['assistant']) ? sanitize_text_field($_GET['assistant']) : '';

$chatbot_prompt = isset($_GET['chatbot_prompt']) ? sanitize_text_field($_GET['chatbot_prompt']) : '';
$chatbot_prompt = preg_replace("/^\\\\'|\\\\'$/", '', $chatbot_prompt);

// Retrieve the allowed pairs based on the ai platform choice
if ($chatbot_ai_platform_choice === 'Azure OpenAI') {
    // Add the allowed domains and assistants from the Azure OpenAI Assistant Settings - Ver 2.2.6 - 2025-03-12
    $allowed_domains_string = esc_attr(get_option('chatbot_azure_allowed_remote_domains', ''));
} else {
    // Retrieve the allowed domains and assistants from the OpenAI Assistants options
    $allowed_domains_string = esc_attr(get_option('chatbot_chatgpt_allowed_remote_domains', ''));
}

// Convert the string to an array of domain-assistant pairs, assuming they are newline-separated
$allowed_pairs = array_map('trim', explode("\n", $allowed_domains_string));

// Log the allowed pairs for debugging purposes
chatbot_widget_logging('Allowed Domain-Assistant Pairs: ' . $allowed_domains_string );

// Check if allowed pairs list is empty
if (empty($allowed_pairs)) {
    $is_allowed = false;
} else {
    // Check the HTTP_REFERER to ensure the request is from an allowed server
    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

    // Normalize referer to get the base domain
    // $normalized_referer = preg_replace('/^www\./', '', parse_url($referer, PHP_URL_HOST));
    $host = parse_url($referer, PHP_URL_HOST) ?? '';
    $normalized_referer = preg_replace('/^www\./', '', $host);
    $base_referer = implode('.', array_slice(explode('.', $normalized_referer), -2));

    chatbot_widget_logging('$host: ' . $host);
    chatbot_widget_logging('$normalized_referer: ' . $normalized_referer);
    chatbot_widget_logging('$base_referer: ' . $base_referer);

    $is_allowed = false;

    foreach ($allowed_pairs as $allowed_pair) {
        // Split the pair into domain and shortcode
        $pair_parts = array_map('trim', explode(',', $allowed_pair));

        // Ensure the pair contains both domain and shortcode
        if (count($pair_parts) === 2) {
            list($allowed_domain, $allowed_shortcode) = $pair_parts;

            // Normalize allowed domain to get the base domain
            $normalized_domain = preg_replace('/^www\./', '', $allowed_domain);
            $base_domain = implode('.', array_slice(explode('.', $normalized_domain), -2));

            // Debugging: Log the normalized referer and domain for comparison
            chatbot_widget_logging('Checking Pair', $base_referer, $base_domain);

            if (!empty($base_domain) && $base_referer === $base_domain && $allowed_shortcode === $shortcode_param) {
                $is_allowed = true;
                // Log the valid referer and shortcode pair
                chatbot_widget_logging('Allowed Pair', $referer, $shortcode_param);
                break;
            }
        }
    }
}

if (!$is_allowed) {
    // Log the unauthorized access attempt
    chatbot_widget_logging('Unauthorized Access', $referer, $shortcode_param);
    die();
}

// Check if the sanitized shortcode exists in the list of registered shortcodes
if (!array_key_exists($shortcode_param, $shortcode_tags)) {
    chatbot_widget_logging('Invalid shortcode: ' . $shortcode_param, $referer, $request_ip);
    die();
} else {
    chatbot_widget_logging('Valid shortcode: ' . $shortcode_param, $referer, $request_ip);
}

// Since we're confident that $shortcode_param is a valid registered shortcode,
// it's safe to pass it to the do_shortcode function.
if (!empty($chatbot_prompt)) {
    $chatbot_html = do_shortcode('[' . esc_html($shortcode_param) . ' chatbot_prompt="' . esc_html($chatbot_prompt) . '"]');
} else {
    $chatbot_html = do_shortcode('[' . esc_html($shortcode_param) . ']');
}

// Set the initial chatbot settings
if (is_user_logged_in()) {

    $kchat_settings['chatbot_chatgpt_message_limit_setting'] = esc_attr(get_option('chatbot_chatgpt_user_message_limit_setting', '999'));
    $kchat_settings['chatbot_chatgpt_message_limit_setting_period'] = esc_attr(get_option('chatbot_chatgpt_user_message_limit_period_setting', 'Lifetime'));
    $kchat_settings['chatbot_chatgpt_display_message_count'] = esc_attr(get_option('chatbot_chatgpt_display_message_count', 'No'));

} else {

    $kchat_settings['chatbot_chatgpt_message_limit_setting'] = esc_attr(get_option('chatbot_chatgpt_visitor_message_limit_setting', '999'));
    $kchat_settings['chatbot_chatgpt_message_limit_setting_period'] = esc_attr(get_option('chatbot_chatgpt_visitor_message_limit_period_setting', 'Lifetime'));
    $kchat_settings['chatbot_chatgpt_display_message_count'] = esc_attr(get_option('chatbot_chatgpt_display_message_count', 'No'));
}

// Get the tail number from the session id and assign it to page id - Ver 2.1.4
// $session_id_parts = explode('.', $session_id);
// $page_id = $session_id_parts[1];
// error_log('[Chatbot] [chatbot-widget-endpoint.php] Widget Endpoint - $page_id: ' . $page_id);
$page_id = '999999';

// Localize the data for the chatbot - Ver 2.1.1.1
// Include nonces so send-message and other AJAX actions pass security check (widget iframe had no nonces before)
$kchat_settings = array_merge($kchat_settings, array(
    'chatbot-chatgpt-version' => esc_attr($chatbot_chatgpt_plugin_version),
    'plugins_url' => esc_url($chatbot_chatgpt_plugin_dir_url),
    'ajax_url' => esc_url(admin_url('admin-ajax.php')),
    'user_id' => esc_html($user_id),
    'session_id' => esc_html($session_id),
    'page_id' => esc_html($page_id),
    'model' => esc_html($model),
    'voice' => esc_html($voice),
    'chatbot_chatgpt_timeout_setting' => esc_attr(get_option('chatbot_chatgpt_timeout_setting', '240')),
    'chatbot_chatgpt_avatar_icon_setting' => esc_attr(get_option('chatbot_chatgpt_avatar_icon_setting', '')),
    'chatbot_chatgpt_custom_avatar_icon_setting' => esc_attr(get_option('chatbot_chatgpt_custom_avatar_icon_setting', '')),
    'chatbot_chatgpt_avatar_greeting_setting' => esc_attr(get_option('chatbot_chatgpt_avatar_greeting_setting', 'Howdy!!! Great to see you today! How can I help you?')),
    'chatbot_chatgpt_force_page_reload' => esc_attr(get_option('chatbot_chatgpt_force_page_reload', 'No')),
    'chatbot_chatgpt_custom_error_message' => esc_attr(get_option('chatbot_chatgpt_custom_error_message', 'Your custom error message goes here.')),
    'chatbot_chatgpt_start_status' => esc_attr(get_option('chatbot_chatgpt_start_status', 'closed')),
    'chatbot_chatgpt_start_status_new_visitor' => esc_attr(get_option('chatbot_chatgpt_start_status_new_visitor', 'closed')),
    'chatbot_message_nonce' => wp_create_nonce('chatbot_message_nonce'),
    'chatbot_upload_nonce' => wp_create_nonce('chatbot_upload_nonce'),
    'chatbot_erase_nonce' => wp_create_nonce('chatbot_erase_nonce'),
    'chatbot_unlock_nonce' => wp_create_nonce('chatbot_unlock_nonce'),
    'chatbot_reset_nonce' => wp_create_nonce('chatbot_reset_nonce'),
    'chatbot_queue_nonce' => wp_create_nonce('chatbot_queue_nonce'),
    'chatbot_tts_nonce' => wp_create_nonce('chatbot_tts_nonce'),
    'chatbot_transcript_nonce' => wp_create_nonce('chatbot_transcript_nonce'),
    'nonce_timestamp' => time() * 1000,
));

$kchat_settings_json = wp_json_encode($kchat_settings);

// Widget Sizing Controls
// $chatbot_chatgpt_widget_width = esc_attr(get_option('chatbot_chatgpt_widget_width', '500'));
// $chatbot_chatgpt_widget_height = '45hv';

// Retrieve the width and height from the URL query parameters
$iframe_width = isset($_GET['width']) ? intval($_GET['width']) : 500;  // Default to 500 if not set
$iframe_height = isset($_GET['height']) ? intval($_GET['height']) : 600;  // Default to 600 if not set

// Widget Sizing Controls
$chatbot_widget_width = ($iframe_width - 20) . 'px';
$chatbot_widget_height = ($iframe_height - 20) . 'px';

// Output the HTML and necessary scripts
?>
<!DOCTYPE html>
<html>
<head>
    <?php wp_head(); // Use wp_head() instead of get_header() to avoid theme dependency ?>
    <style>
        /* Include any additional styles needed */
        body, html {
            background: transparent !important;
        }
        .chatbot-wrapper {
            width: <?php echo esc_attr( $chatbot_widget_width ); ?>;
            max-width: 1000px;
            margin: 0 auto;
            height: <?php echo esc_attr( $chatbot_widget_height ); ?>;
            max-height: 1000px;
            overflow: hidden;
            position: fixed;
            bottom: 10px;
            right: 10px;
            padding: 25px;
            background: transparent;
            z-index: 9999;
            }
        #chatbot-chatgpt {
            /* Start minimized to reduce flashing */
            height: 1px;
            width: 1px;
        }
        .chatbot-wide {
            height: 55vh !important}
        }
    </style>
</head>
<body>
    <div class="chatbot-wrapper">
        <?php echo wp_kses_post( $chatbot_html ); ?>
    </div>
    <?php wp_footer(); // Use wp_footer() instead of get_footer() to avoid theme dependency ?>
    <script type="text/javascript">

        // Set values for the chatbot
        var kchat_settings = <?php echo $kchat_settings_json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON from wp_json_encode for JavaScript, escaping would corrupt the data ?>;

        // Set values in local storage
        localStorage.setItem('chatbot_chatgpt_opened', 'true');
        localStorage.setItem('chatbot_chatgpt_start_status', 'open');
        localStorage.setItem('chatbot_chatgpt_start_status_new_visitor', 'open');
        
    </script>
</body>
</html>
