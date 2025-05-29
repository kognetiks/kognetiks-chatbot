<?php
/**
 * Kognetiks Analytics - Ver 1.0.0
 *
 * This file contains the code for the Kognetiks Analytics package.
 * 
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
    require_once plugin_dir_path((__FILE__)) . '/languages/en_US.php';
} else {
    // Load the default sentiment words dictionary
    require_once plugin_dir_path((__FILE__)) . '/languages/en_US.php';
}


