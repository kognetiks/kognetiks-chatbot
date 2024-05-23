<?php
/**
 * Kognetiks Chatbot for WordPress - Parsedown Utilities - Ver 2.0.2.1
 *
 * This file contains the code for plugin utilities.
 * It is used to check for mobile devices and other utilities.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Markdown to HTML conversion
function convert_markdown_to_html($markdown) {
    $Parsedown = new ParsedownCustom();
    return $Parsedown->text($markdown);
}

// Read the Markdown File and Convert to HTML
function get_markdown_file_content($file_path) {
    $markdown_content = file_get_contents($file_path);

    // Get the directory of the current markdown file
    // $dir = dirname($file_path);
    $dir = CHATBOT_CHATGPT_PLUGIN_DIR_PATH . 'documentation/';

    // Replace relative image paths with absolute paths
    $markdown_content = preg_replace_callback('/!\[.*?\]\((.*?)\)/', function ($matches) use ($dir) {
        $image_path = $matches[1];

        // If the image path is not already absolute
        if (!preg_match('/^https?:\/\//', $image_path)) {
            // Convert the relative image path to an absolute path
            $image_path = $dir . '/' . ltrim($image_path, './');
        }

        // Normalize the directory separators
        $image_path = str_replace('\\', '/', $image_path);

        // Convert the server file path to a URL
        $document_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
        $base_url = site_url();
        $image_url = str_replace($document_root, $base_url, $image_path);

        // Log the values for debugging
        error_log('$document_root: ' . $document_root);
        error_log('$base_url: ' . $base_url);
        error_log('$image_path: ' . $image_path);
        error_log('$image_url: ' . $image_url);

        // Replace the image path in the markdown
        return str_replace($matches[1], $image_url, $matches[0]);
    }, $markdown_content);

    // Convert the markdown to HTML
    $Parsedown = new Parsedown();
    return $Parsedown->text($markdown_content);
}

// Display the HTML content
function display_markdown_file($atts) {
    $atts = shortcode_atts(array(
        'file' => 'overview.md'
    ), $atts);

    if (!empty($atts['file'])) {
        // Remove './' from the path
        $atts['file'] = ltrim($atts['file'], './');
        $file_path = CHATBOT_CHATGPT_PLUGIN_DIR_PATH . 'documentation/' . $atts['file'];
        if (!file_exists($file_path)) {
            // Handle the error, e.g., return a message or throw an exception
            return 'File not found: ' . $file_path;
        }
        $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);

        // Check if the file is an image
        if (in_array($file_extension, array('png', 'jpg', 'jpeg', 'gif'))) {
            // Construct the URL for the image
            $file_url = site_url() . '/wp-content/plugins/chatbot-chatgpt/documentation/' . $atts['file'];
            // DIAG - Diagnostics - Ver 2.0.2.1
            back_trace ( 'NOTICE', '$file_url: '. $file_url );
            return $file_url;
        } else {
            // DIAG - Diagnostics - Ver 2.0.2.1
            back_trace ( 'NOTICE', '$file_path: '. $file_path );
            return get_markdown_file_content($file_path);
        }
    } else {
        return 'No file specified.';
    }
}
add_shortcode('display_markdown', 'display_markdown_file');


