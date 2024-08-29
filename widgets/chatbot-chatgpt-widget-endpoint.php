<?php
/**
 * Chatbot Endpoint for Remote Server
 * This file handles requests from the remote server and serves the chatbot.
 */

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

if (is_user_logged_in()) {
    // back_trace( 'NOTICE', 'User is logged in');
    $kchat_settings['chatbot_chatgpt_message_limit_setting'] = esc_attr(get_option('chatbot_chatgpt_message_limit_setting', '999'));
} else {
    // back_trace( 'NOTICE', 'User is NOT logged in');
    $kchat_settings['chatbot_chatgpt_message_limit_setting'] = esc_attr(get_option('chatbot_chatgpt_visitor_message_limit_setting', '999'));
}

// Localize the data for the chatbot - Ver 2.1.1.1
$kchat_settings = array_merge($kchat_settings,array(
    'chatbot-chatgpt-version' => $chatbot_chatgpt_plugin_version,
    'plugins_url' => $chatbot_chatgpt_plugin_dir_url,
    'ajax_url' => admin_url('admin-ajax.php'),
    'user_id' => $user_id,
    'session_id' => $session_id,
    'page_id' => 999999,
    'session_id' => $session_id,
    // 'thread_id' => $thread_id,
    // 'assistant_id' => $assistant_id,
    // 'additional_instructions' => $additional_instructions,
    'model' => $model,
    'voice' => $voice,
    'chatbot_chatgpt_timeout_setting' => esc_attr(get_option('chatbot_chatgpt_timeout_setting', '240')),
    'chatbot_chatgpt_avatar_icon_setting' => esc_attr(get_option('chatbot_chatgpt_avatar_icon_setting', '')),
    'chatbot_chatgpt_custom_avatar_icon_setting' => esc_attr(get_option('chatbot_chatgpt_custom_avatar_icon_setting', '')),
    'chatbot_chatgpt_avatar_greeting_setting' => esc_attr(get_option('chatbot_chatgpt_avatar_greeting_setting', 'Howdy!!! Great to see you today! How can I help you?')),
    'chatbot_chatgpt_force_page_reload' => esc_attr(get_option('chatbot_chatgpt_force_page_reload', 'No')),
    'chatbot_chatgpt_custom_error_message' => esc_attr(get_option('chatbot_chatgpt_custom_error_message', 'Your custom error message goes here.')),
    'chatbot_chatgpt_message_limit_setting' => esc_attr(get_option('chatbot_chatgpt_message_limit_setting', '999')),
));

$kchat_settings_json = wp_json_encode($kchat_settings);

// Process the shortcode to get the chatbot HTML and settings
$chatbot_html = do_shortcode('[chatbot-4]');

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
    </script>
</body>
</html>
