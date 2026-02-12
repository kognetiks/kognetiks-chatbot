<?php
/**
 * Kognetiks Chatbot - Manage Error Logs - Ver 2.0.7 - Updated 2.3.1.1 - 2025-06-26
 *
 * This file contains the code for managing error logs
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Function to ensure proper directory permissions
function ensure_log_directory_permissions($dir_path) {

    // Ensure the directory exists
    if (!file_exists($dir_path)) {
        if (!wp_mkdir_p($dir_path)) {
            if ( defined('WP_DEBUG') && WP_DEBUG ) {
                error_log('[Chatbot] [chatbot-manage-error-logs.php] Failed to create directory: ' . $dir_path);
            }
            return false;
        }
    }
    
    // Set directory permissions to 0755 (readable and executable by all, writable by owner)
    if (!chmod($dir_path, 0755)) {
        if ( defined('WP_DEBUG') && WP_DEBUG ) {
            error_log('[Chatbot] [chatbot-manage-error-logs.php] Failed to set directory permissions: ' . $dir_path);
        }
    }
    
    return true;

}

// Function to check web server permissions
function check_web_server_permissions($dir_path) {

    $test_file = $dir_path . 'test_permissions.tmp';
    
    // Try to create a test file
    if (file_put_contents($test_file, 'test') === false) {
        return false;
    }
    
    // Try to delete the test file
    if (!unlink($test_file)) {
        return false;
    }
    
    return true;

}

// Retrieve error log file names
function chatbot_chatgpt_manage_error_logs() {

    global $chatbot_chatgpt_plugin_dir_path;

    $chatbot_logs_dir = $chatbot_chatgpt_plugin_dir_path . 'chatbot-logs/';
    // error_log('[Chatbot] [chatbot-manage-error-logs.php] chatbot_chatgpt_manage_error_logs() - $chatbot_logs_dir: ' . $chatbot_logs_dir);

    // Ensure the directory and index file exist with proper permissions
    if (!ensure_log_directory_permissions($chatbot_logs_dir)) {
        echo '<div class="notice notice-error"><p>Failed to create or set permissions for logs directory. Please check server permissions.</p></div>';
        return;
    }
    
    create_directory_and_index_file($chatbot_logs_dir);

    // Check web server permissions
    if (!check_web_server_permissions($chatbot_logs_dir)) {
        echo '<div class="notice notice-warning"><p><strong>Warning:</strong> The web server does not have sufficient permissions to manage log files in this directory. File deletion operations may fail.</p></div>';
    }

    // Initialize $scanned_dir with a default value
    $scanned_dir = false;

    // Check if the directory exists
    if (is_dir($chatbot_logs_dir)) {
        $scanned_dir = scandir($chatbot_logs_dir);
    } else {
        // Handle the error, e.g., log it, create the directory, or throw an exception
        if ( defined('WP_DEBUG') && WP_DEBUG ) {
            error_log('[Chatbot] [chatbot-manage-error-logs.php] Directory not found: ' . $chatbot_logs_dir);
        }
        // Optionally, create the directory
        // mkdir($chatbot_logs_dir, 0777, true);
        // Then, you might want to scan it again or handle the situation differently
    }

    // Check if scandir returns false and handle the error
    if ($scanned_dir === false) {
        echo '<p>Error accessing log files directory.</p>';
        return;
    }

    $files = array_diff($scanned_dir, array('..', '.'));

    // Exclude non-log files
    $files = array_filter($files, function($file) {
        return preg_match('/\.log$/', $file);
    });

    if (empty($files)) {
        echo '<p>No log files found.</p>';
        return;
    }

    // DIAG - Log files for troubleshooting - Ver 2.0.7

    // Start HTML output with styling
    $output = '<style>
        .error-log-templates-display {
            overflow-x: auto; /* Add horizontal scroll if needed */
        }
        .error-log-templates-display table {
            width: 100%;
            border-collapse: collapse;
        }
        .error-log-templates-display th, .error-log-templates-display td {
            border: 1px solid #ddd;
            padding: 8px;
            padding: 10px !important; /* Adjust cell padding */
            white-space: normal !important; /* Allow cell content to wrap */
            word-break: keep-all !important; /* Keep all words together */
            text-align: center !important; /* Center text-align */
        }
        .error-log-templates-display th {
            background-color: #f2f2f2;
        }
        /* Scope notice styles to error-log section only to avoid overriding WordPress admin notices */
        .error-log-templates-display .notice {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .error-log-templates-display .notice-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .error-log-templates-display .notice-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .error-log-templates-display .notice-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
    </style>';

    $output .= '<div class="wrap error-log-templates-display">';
    
    // Display status messages
    if (isset($_GET['deleted']) && $_GET['deleted'] == '1') {
        $output .= '<div class="notice notice-success"><p>Log file deleted successfully.</p></div>';
    }
    if (isset($_GET['deleted_all'])) {
        $output .= '<div class="notice notice-success"><p>' . intval($_GET['deleted_all']) . ' log file(s) deleted successfully.</p></div>';
    }
    if (isset($_GET['failed'])) {
        $output .= '<div class="notice notice-error"><p>' . intval($_GET['failed']) . ' log file(s) could not be deleted. Please check file permissions.</p></div>';
    }
    if (isset($_GET['permissions_fixed']) && $_GET['permissions_fixed'] == '1') {
        $output .= '<div class="notice notice-success"><p>Directory permissions have been fixed.</p></div>';
    }
    if (isset($_GET['files_fixed'])) {
        $output .= '<div class="notice notice-success"><p>' . intval($_GET['files_fixed']) . ' log file(s) permissions have been fixed.</p></div>';
    }

    $output .= '<form method="post" action="">';
    $output .= '<table>';
    $output .= '<thead>';
    $output .= '<tr>';
    $output .= '<th>File Name</th>';
    $output .= '<th>Actions</th>';
    $output .= '</tr>';
    $output .= '</thead>';
    $output .= '<tbody>';

    foreach ($files as $file) {
        $file_path = $chatbot_logs_dir . $file;
        $output .= '<tr>';
        $output .= '<td>' . esc_html($file) . '</td>';
        $output .= '<td>';
        $output .= '<a href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=download_log&file=' . urlencode($file)), 'download_log_' . $file)) . '" class="button button-primary">Download</a> ';
        $output .= '<a href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=delete_log&file=' . urlencode($file)), 'delete_log_' . $file)) . '" class="button button-primary">Delete</a>';
        $output .= '</td>';
        $output .= '</tr>';
    }

    $output .= '</tbody>';
    $output .= '</table>';
    $output .= '<p><a href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=delete_all_logs'), 'delete_all_logs')) . '" class="button button-danger">Delete All</a></p>';
    $output .= '<p><a href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=fix_permissions'), 'fix_permissions')) . '" class="button button-secondary">Fix Permissions</a></p>';
    $output .= '</form>';
    $output .= '</div>';

    // Output style block first (wp_kses_post strips <style> and would show CSS as text).
    echo $output;

    return;

}

// Handle error log actions
function handle_log_actions() {

    global $chatbot_chatgpt_plugin_dir_path;

    // Security: Require admin capability - log management is admin-only
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'chatbot-chatgpt' ), 403 );
    }

    if (!isset($_GET['action']) || !isset($_GET['_wpnonce'])) {
        return;
    }

    $action = sanitize_text_field($_GET['action']);
    $nonce = sanitize_text_field($_GET['_wpnonce']);

    switch ($action) {
        case 'download_log':
            if (!wp_verify_nonce($nonce, 'download_log_' . sanitize_file_name($_GET['file']))) {
                wp_die('Invalid nonce');
            }
            $file = sanitize_file_name(basename($_GET['file']));
            $chatbot_logs_dir = $chatbot_chatgpt_plugin_dir_path . 'chatbot-logs/';
            $file_path = $chatbot_logs_dir . $file;

            if (file_exists($file_path)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($file_path));
                readfile($file_path);
                exit;
            } else {
                wp_die('File not found');
            }
            break;

        case 'delete_log':
            if (!wp_verify_nonce($nonce, 'delete_log_' . sanitize_file_name($_GET['file']))) {
                wp_die('Invalid nonce');
            }
            $file = sanitize_file_name(basename($_GET['file']));
            $chatbot_logs_dir = $chatbot_chatgpt_plugin_dir_path . 'chatbot-logs/';
            $file_path = $chatbot_logs_dir . $file;

            if (file_exists($file_path)) {
                // Check if file is writable before attempting to delete
                if (!is_writable($file_path)) {
                    if ( defined('WP_DEBUG') && WP_DEBUG ) {
                        error_log('[Chatbot] [chatbot-manage-error-logs.php] File not writable: ' . $file_path);
                    }
                    wp_die('File is not writable. Please check file permissions.');
                }
                
                // Attempt to delete the file with error handling
                if (!unlink($file_path)) {
                    if ( defined('WP_DEBUG') && WP_DEBUG ) {
                        error_log('[Chatbot] [chatbot-manage-error-logs.php] Failed to delete file: ' . $file_path);
                    }
                    wp_die('Failed to delete file. Please check file permissions or if the file is in use.');
                }
                
                wp_redirect(admin_url('admin.php?page=chatbot-chatgpt&tab=tools&deleted=1')); // Redirect with success message
                exit;
            } else {
                wp_die('File not found');
            }
            break;

        case 'delete_all_logs':
            if (!wp_verify_nonce($nonce, 'delete_all_logs')) {
                wp_die('Invalid nonce');
            }
            $chatbot_logs_dir = $chatbot_chatgpt_plugin_dir_path . 'chatbot-logs/';
            $files = array_diff(scandir($chatbot_logs_dir), array('..', '.', 'index.php')); // Exclude index.php from deletion
            
            $deleted_count = 0;
            $failed_count = 0;
            
            foreach ($files as $file) {
                $file_path = $chatbot_logs_dir . $file;
                if (file_exists($file_path) && is_file($file_path)) {
                    // Check if file is writable before attempting to delete
                    if (!is_writable($file_path)) {
                        if ( defined('WP_DEBUG') && WP_DEBUG ) {
                            error_log('[Chatbot] [chatbot-manage-error-logs.php] File not writable: ' . $file_path);
                        }
                        $failed_count++;
                        continue;
                    }
                    
                    // Attempt to delete the file
                    if (unlink($file_path)) {
                        $deleted_count++;
                    } else {
                        if ( defined('WP_DEBUG') && WP_DEBUG ) {
                            error_log('[Chatbot] [chatbot-manage-error-logs.php] Failed to delete file: ' . $file_path);
                        }
                        $failed_count++;
                    }
                }
            }
            
            // Redirect with status message
            $redirect_url = admin_url('admin.php?page=chatbot-chatgpt&tab=tools');
            if ($deleted_count > 0) {
                $redirect_url .= '&deleted_all=' . $deleted_count;
            }
            if ($failed_count > 0) {
                $redirect_url .= '&failed=' . $failed_count;
            }
            
            wp_redirect($redirect_url);
            exit;
            break;

        case 'fix_permissions':
            if (!wp_verify_nonce($nonce, 'fix_permissions')) {
                wp_die('Invalid nonce');
            }
            $chatbot_logs_dir = $chatbot_chatgpt_plugin_dir_path . 'chatbot-logs/';
            
            // Fix directory permissions
            $dir_fixed = ensure_log_directory_permissions($chatbot_logs_dir);
            
            // Fix file permissions for all log files
            $files = array_diff(scandir($chatbot_logs_dir), array('..', '.', 'index.php'));
            $files_fixed = 0;
            
            foreach ($files as $file) {
                $file_path = $chatbot_logs_dir . $file;
                if (file_exists($file_path) && is_file($file_path)) {
                    if (chmod($file_path, 0644)) { // Readable by all, writable by owner
                        $files_fixed++;
                    }
                }
            }
            
            // Redirect with status
            $redirect_url = admin_url('admin.php?page=chatbot-chatgpt&tab=tools');
            if ($dir_fixed) {
                $redirect_url .= '&permissions_fixed=1';
            }
            if ($files_fixed > 0) {
                $redirect_url .= '&files_fixed=' . $files_fixed;
            }
            
            wp_redirect($redirect_url);
            exit;
            break;

        default:
            wp_die('Invalid action');
    }
    
}
// Admin-only: no admin_post_nopriv_* - unauthenticated users cannot manage logs
add_action('admin_post_download_log', 'handle_log_actions');
add_action('admin_post_delete_log', 'handle_log_actions');
add_action('admin_post_delete_all_logs', 'handle_log_actions');
add_action('admin_post_fix_permissions', 'handle_log_actions');
