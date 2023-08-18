<?php
/**
 * Chatbot ChatGPT for WordPress - TF-IDF Analyzer
 *
 * This file contains the code for the Chatbot ChatGPT settings page.
 * 
 * 
 *
 * @package chatbot-chatgpt
 */


// Premium settings section callback - Ver 1.3.0
function chatbot_chatgpt_tf_idf_section_callback($args) {
    ?>
    <p>TF-IDF Analysis.</p>
    if (is_admin()) {
            $header .= " ";
            $header .= '<a class="button button-primary" href="' . esc_url(admin_url('admin-post.php?action=download_analyzer_download_csv')) . '">Download Data as CSV</a>';
        }
    <?php
}


// Download the TF-IDF data
function chatbot_chatgpt_tf_idf_download_csv() {
    // Get the absolute path to the plugin directory
    $plugin_dir_path = plugin_dir_path(__FILE__);

    // Go up one level to the parent directory
    $parent_dir_path = dirname($plugin_dir_path);

    // Create a "results" subdirectory in the parent directory if it doesn't exist
    $results_dir_path = $parent_dir_path . '/results/';

    // Specify the output files' paths
    $results_csv_file = $results_dir_path . 'results.csv';

    // Check if the file exists
    if (!file_exists($results_csv_file)) {
        wp_die("File not found!");
    }

    // Read the file
    $csv_data = file_get_contents($results_csv_file);

    if ($csv_data === false) {
        wp_die("Error reading file.");
    }

    // Set headers and echo the file content for download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=tf_idf_download_data.csv');
    echo $csv_data;
    exit;
}
add_action('admin_post_download_analyzer_download_csv', 'chatbot_chatgpt_tf_idf_download_csv');