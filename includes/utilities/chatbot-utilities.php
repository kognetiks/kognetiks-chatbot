<?php
/**
 * Kognetiks Chatbot - Utilities - Ver 1.8.1
 *
 * This file contains the code for plugin utilities.
 * It is used to check for mobile devices and other utilities.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Check for device type
function is_mobile_device() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $mobile_agents = array('Mobile', 'Android', 'Silk/', 'Kindle', 'BlackBerry', 'Opera Mini', 'Opera Mobi', 'iPhone', 'iPad', 'iPod', 'Windows Phone', 'webOS', 'Symbian', 'IEMobile');

    foreach ($mobile_agents as $device) {
        if (strpos($user_agent, $device) !== false) {
            return true; // Mobile device detected
        }
    }

    return false; // Not a mobile device

}

// Function to create a directory and an index.php file
function create_directory_and_index_file($dir_path) {
    // Ensure the directory ends with a slash
    $dir_path = rtrim($dir_path, '/') . '/';

    // Check if the directory exists, if not create it
    if (!file_exists($dir_path) && !wp_mkdir_p($dir_path)) {
        // Error handling, e.g., log the error or handle the failure appropriately
        // back_trace( 'ERROR', 'Failed to create directory.');
        return false;
    }

    // Path for the index.php file
    $index_file_path = $dir_path . 'index.php';

    // Check if the index.php file exists, if not create it
    if (!file_exists($index_file_path)) {
        $file_content = "<?php\n// Silence is golden.\n\n";
        file_put_contents($index_file_path, $file_content);
    }

    // Set directory permissions
    chmod($dir_path, 0755);

    return true;

}
