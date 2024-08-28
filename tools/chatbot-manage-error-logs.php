<?php
/**
 * Kognetiks Chatbot for WordPress - Manage Error Logs - Ver 2.0.7
 *
 * This file contains the code for managing error logs
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Retrieve error log file names
function chatbot_chatgpt_manage_error_logs() {

    $chatbot_logs_dir = $chatbot_chatgpt_pluign_dir_path . 'chatbot-logs/';

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
    // back_trace( 'NOTICE', 'chatbot_chatgpt_manage_error_logs', 'Files: ' . print_r($files, true));

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
    </style>';

    $output .= '<div class="wrap error-log-templates-display">';

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
    $output .= '</form>';
    $output .= '</div>';

    echo $output; // Output the generated HTML

    return;
}

// Handle error log actions
function handle_log_actions() {
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
            $chatbot_logs_dir = $chatbot_chatgpt_pluign_dir_path . 'chatbot-logs/';
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
            $chatbot_logs_dir = $chatbot_chatgpt_pluign_dir_path . 'chatbot-logs/';
            $file_path = $chatbot_logs_dir . $file;

            if (file_exists($file_path)) {
                unlink($file_path);
                wp_redirect(admin_url('admin.php?page=chatbot-chatgpt&tab=tools')); // Redirect to plugin page
                exit;
            } else {
                wp_die('File not found');
            }
            break;

        case 'delete_all_logs':
            if (!wp_verify_nonce($nonce, 'delete_all_logs')) {
                wp_die('Invalid nonce');
            }
            $chatbot_logs_dir = $chatbot_chatgpt_pluign_dir_path . 'chatbot-logs/';
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
add_action('admin_post_nopriv_download_log', 'handle_log_actions');
add_action('admin_post_download_log', 'handle_log_actions');
add_action('admin_post_nopriv_delete_log', 'handle_log_actions');
add_action('admin_post_delete_log', 'handle_log_actions');
add_action('admin_post_nopriv_delete_all_logs', 'handle_log_actions');
add_action('admin_post_delete_all_logs', 'handle_log_actions');
