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

/**
 * Check if a model requires max_completion_tokens instead of max_tokens
 * Newer OpenAI models (gpt-5, o1, o3, etc.) require max_completion_tokens
 * 
 * @param string $model The model name
 * @return bool True if model requires max_completion_tokens, false otherwise
 */
function chatbot_openai_requires_max_completion_tokens($model) {
    // Models that require max_completion_tokens instead of max_tokens
    $models_requiring_max_completion_tokens = array(
        'gpt-5',
        'gpt-5-',
        'o1',
        'o1-',
        'o3',
        'o3-',
    );
    
    // Use str_starts_with if available (PHP 8.0+), otherwise use substr
    foreach ($models_requiring_max_completion_tokens as $prefix) {
        if (function_exists('str_starts_with')) {
            if (str_starts_with($model, $prefix)) {
                return true;
            }
        } else {
            // PHP 7.x compatibility
            if (substr($model, 0, strlen($prefix)) === $prefix) {
                return true;
            }
        }
    }
    
    return false;
}

/**
 * Check if a model doesn't support temperature and top_p parameters
 * Some newer OpenAI models (gpt-5, o1, o3, etc.) use fixed values and don't accept these parameters
 * 
 * @param string $model The model name
 * @return bool True if model doesn't support temperature/top_p, false otherwise
 */
function chatbot_openai_doesnt_support_temperature($model) {
    // Models that don't support temperature/top_p parameters
    // gpt-5 only supports the default temperature value (1.0), not custom values
    // o1 and o3 models use fixed values and don't accept these parameters at all
    $models_without_temperature = array(
        'gpt-5',
        'gpt-5-',
        'o1',
        'o1-',
        'o3',
        'o3-',
    );
    
    // Use str_starts_with if available (PHP 8.0+), otherwise use substr
    foreach ($models_without_temperature as $prefix) {
        if (function_exists('str_starts_with')) {
            if (str_starts_with($model, $prefix)) {
                return true;
            }
        } else {
            // PHP 7.x compatibility
            if (substr($model, 0, strlen($prefix)) === $prefix) {
                return true;
            }
        }
    }
    
    return false;
}