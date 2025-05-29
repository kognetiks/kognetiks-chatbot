<?php
/**
 * Kognetiks Analytics - Diagnostics - Ver 1.0.0
 *
 * This file contains the code for global variables used
 * by the program.
 * 
 * 
 * @package kognetiks-analytics
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Get the WordPress language
$language = get_locale();

// Check if the language is English
if ($language !== 'en_US') {
    // Load the EN sentiment words dictionary
    require_once plugin_dir_path(dirname(__FILE__)) . 'analytics/languages/en_US.php';
} else {
    // Load the default sentiment words dictionary
    require_once plugin_dir_path(dirname(__FILE__)) . 'analytics/languages/en_US.php';
}
