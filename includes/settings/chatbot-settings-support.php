<?php
/**
 * Kognetiks Chatbot for WordPress - Settings - Support Page
 *
 * This file contains the code for the Chatbot settings page.
 * It handles the support settings and other parameters.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Support settings section callback - Ver 1.3.0
function chatbot_chatgpt_support_section_callback() {

    global $dir;

    // // Get the 'documentation' parameter from the URL
    $docLocation = '';
    if (isset($_GET['dir'])) {
        $dir = sanitize_text_field($_GET['dir']);
    } else {
        $dir = '';
    }
    if (isset($_GET['file'])) {
        $file = sanitize_text_field($_GET['file']);
    } else {
        $file = '';
    }
    if (!empty($dir) && !empty($file)) {
        $docLocation = $dir . '/' . $file;
    } else if (empty($dir) && empty($file)) {
        $docLocation = 'overview.md';
    }

    // The files are in the 'documentation' directory
    $docLocation = CHATBOT_CHATGPT_PLUGIN_DIR_PATH . 'documentation/' . $docLocation;

    // DIAG - Diagnostics - Ver 2.0.2.1
    back_trace ( 'NOTICE', '$docLocation: '. $docLocation );
    
    // echo '<div class="wrap">';
    // // Use do_shortcode to display the Markdown content, passing the file parameter
    // echo do_shortcode('[display_markdown file="' . esc_attr($docLocation) . '"]');
    // echo '</div>';

    $parsedown = new Parsedown();
    $markdownContent = file_get_contents($docLocation);
    $htmlContent = $parsedown->text($markdownContent);

    // $basePath = CHATBOT_CHATGPT_PLUGIN_DIR_PATH . 'documentation/'
    // $basePath = "?page=chatbot-chatgpt&tab=support&dir=" . $dir . "&file=" . $file;
    $dir = isset($_GET['dir']) ? sanitize_text_field($_GET['dir']) : '';
    $file = isset($_GET['file']) ? sanitize_text_field($_GET['file']) : '';
    
    $basePath = CHATBOT_CHATGPT_PLUGIN_DIR_PATH . 'documentation/';
    $basePath = "?page=chatbot-chatgpt";
    if ($dir !== '') {
        $basePath .= "&tab=support&dir=" . $dir;
    }
    if ($file !== '') {
        // Remove 'overview.md/' from the file parameter
        $file = str_replace('overview.md/', '', $file);
        $basePath .= "&file=" . $file;
    }
    $adjustedHtmlContent = adjustPaths($htmlContent, $basePath);

    echo $adjustedHtmlContent;

}

function adjustPaths($html, $basePath) {
    // Adjust image paths
    $html = preg_replace_callback(
        '/<img\s+src="([^"]+)"/i',
        function ($matches) use ($basePath) {
            return '<img src="' . adjustImagePath($matches[1], $basePath) . '" style="border: 1px solid black;"';
        },
        $html
    );

    // Adjust anchor paths
    $html = preg_replace_callback(
        '/<a\s+href="([^"]+)"/i',
        function ($matches) use ($basePath) {
            return '<a href="' . adjustPath($matches[1], $basePath) . '"';
        },
        $html
    );

    return $html;
}

function adjustPath($url, $basePath) {
    if (strpos($url, 'http') !== 0 && strpos($url, '#') !== 0) {
        // Split the URL by '/' to get the dir and file
        $parts = explode('/', $url);
        if (count($parts) >= 2) {
            $dir = $parts[0];
            $file = $parts[1];

            // Construct the URL with the correct parameters
            $url = rtrim($basePath, '/') . "&tab=support&dir=" . $dir . "&file=" . $file;
        } else {
            // If the URL is a relative path, append it to the base path of the current document
            $basePathParts = explode('&file=', $basePath);
            $url = rtrim($basePathParts[0], '/') . "&file=" . $url;
        }
    }
    return $url;
}

function adjustImagePath($url, $basePath) {
    if (strpos($url, 'http') !== 0) {
        // If the URL is a relative path, construct the direct path to the image
        $basePathParts = explode('&dir=', $basePath);
        if (count($basePathParts) > 1) {
            $dirParts = explode('&file=', $basePathParts[1]);
            $dir = rtrim($dirParts[0], '/');
            $url = site_url() . '/wp-content/plugins/chatbot-chatgpt/documentation/' . $dir . '/' . $url;
        } else {
            $url = site_url() . '/wp-content/plugins/chatbot-chatgpt/documentation/' . $url;
        }
    }
    return $url;
}