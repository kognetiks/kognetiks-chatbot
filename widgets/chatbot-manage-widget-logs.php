<?php
/**
 * Kognetiks Chatbot - Manage Widget Logs - Ver 2.1.3
 *
 * This file contains the code for managing Widget logs
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Retrieve widget log file names
function chatbot_manage_widget_logs() {

    global $chatbot_chatgpt_plugin_dir_path;

    $chatbot_logs_dir = $chatbot_chatgpt_plugin_dir_path . 'widget-logs/';

    // Ensure the directory and index file exist
    create_directory_and_index_file($chatbot_logs_dir);

    // Initialize $scanned_dir with a default value
    $scanned_dir = false;

    // Check if the directory exists
    if (is_dir($chatbot_logs_dir)) {
        $scanned_dir = scandir($chatbot_logs_dir);
    } else {
        // Handle the error, e.g., log it, create the directory, or throw an exception
        error_log("Directory not found: " . $chatbot_logs_dir);
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
    // back_trace( 'NOTICE', 'chatbot_manage_widget_logs', 'Files: ' . print_r($files, true));

    // Start HTML output with styling
    $output = '<style>
        .widget-log-templates-display {
            overflow-x: auto; /* Add horizontal scroll if needed */
        }
        .widget-log-templates-display table {
            width: 100%;
            border-collapse: collapse;
        }
        .widget-log-templates-display th, .widget-log-templates-display td {
            border: 1px solid #ddd;
            padding: 8px;
            padding: 10px !important; /* Adjust cell padding */
            white-space: normal !important; /* Allow cell content to wrap */
            word-break: keep-all !important; /* Keep all words together */
            text-align: center !important; /* Center text-align */
        }
        .widget-log-templates-display th {
            background-color: #f2f2f2;
        }
    </style>';

    $output .= '<div class="wrap widget-log-templates-display">';

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
        $output .= '<a href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=download_widget_log&file=' . urlencode($file)), 'download_log_' . $file)) . '" class="button button-primary">Download</a> ';
        $output .= '<a href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=delete_widget_log&file=' . urlencode($file)), 'delete_log_' . $file)) . '" class="button button-primary">Delete</a>';
        $output .= '</td>';
        $output .= '</tr>';
    }

    $output .= '</tbody>';
    $output .= '</table>';
    $output .= '<p><a href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=delete_all_widget_logs'), 'delete_all_logs')) . '" class="button button-danger">Delete All</a></p>';
    $output .= '</form>';
    $output .= '</div>';

    echo $output; // Output the generated HTML

    return;
}

// Handle widget log actions
function handle_widget_log_actions() {

    global $chatbot_chatgpt_plugin_dir_path;

    if (!isset($_GET['action']) || !isset($_GET['_wpnonce'])) {
        return;
    }

    $action = sanitize_text_field($_GET['action']);
    $nonce = sanitize_text_field($_GET['_wpnonce']);

    switch ($action) {
        case 'download_widget_log':
            if (!wp_verify_nonce($nonce, 'download_log_' . sanitize_file_name($_GET['file']))) {
                wp_die('Invalid nonce');
            }
            $file = sanitize_file_name(basename($_GET['file']));
            $chatbot_logs_dir = $chatbot_chatgpt_plugin_dir_path . 'widget-logs/';
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

        case 'delete_widget_log':
            if (!wp_verify_nonce($nonce, 'delete_log_' . sanitize_file_name($_GET['file']))) {
                wp_die('Invalid nonce');
            }
            $file = sanitize_file_name(basename($_GET['file']));
            $chatbot_logs_dir = $chatbot_chatgpt_plugin_dir_path . 'widget-logs/';
            $file_path = $chatbot_logs_dir . $file;

            if (file_exists($file_path)) {
                unlink($file_path);
                wp_redirect(admin_url('admin.php?page=chatbot-chatgpt&tab=tools')); // Redirect to plugin page
                exit;
            } else {
                wp_die('File not found');
            }
            break;

        case 'delete_all_widget_logs':
            if (!wp_verify_nonce($nonce, 'delete_all_logs')) {
                wp_die('Invalid nonce');
            }
            $chatbot_logs_dir = $chatbot_chatgpt_plugin_dir_path . 'widget-logs/';
            $files = array_diff(scandir($chatbot_logs_dir), array('..', '.'));

            foreach ($files as $file) {
                $file_path = $chatbot_logs_dir . $file;
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }

            wp_redirect(admin_url('admin.php?page=chatbot-chatgpt&tab=tools')); // Redirect to plugin page
            exit;
            break;

        default:
            wp_die('Invalid action');
    }
}
add_action('admin_post_nopriv_download_widget_log', 'handle_widget_log_actions');
add_action('admin_post_download_widget_log', 'handle_widget_log_actions');
add_action('admin_post_nopriv_delete_widget_log', 'handle_widget_log_actions');
add_action('admin_post_delete_widget_log', 'handle_widget_log_actions');
add_action('admin_post_nopriv_delete_all_widget_logs', 'handle_widget_log_actions');
add_action('admin_post_delete_all_widget_logs', 'handle_widget_log_actions');
