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

    // Get the 'documentation' parameter from the URL
    $docLocation = '';
    if (isset($_GET['dir'])) {
        $dir = sanitize_text_field($_GET['dir']);
    } else {
        $dir = '';
    }
    if (isset($_GET['file'])) {
        $file = sanitize_text_field($_GET['file']);
    } else {
        $file = 'overview.md';
    }
    if (!empty($dir) && !empty($file)) {
        $docLocation = $dir . '/' . $file;
    } else {
        $docLocation = 'overview.md';
    }

    // DIAG - Diagnostics - Ver 2.0.2.1
    back_trace ( 'NOTICE', '$docLocation: '. $docLocation );
    
    echo '<div class="wrap">';
    // Use do_shortcode to display the Markdown content, passing the file parameter
    echo do_shortcode('[display_markdown file="' . esc_attr($docLocation) . '"]');
    echo '</div>';

}
