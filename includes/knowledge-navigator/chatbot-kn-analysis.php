<?php
/**
 * Kognetiks Chatbot for WordPress - Knowledge Navigator - TF-IDF Analyzer
 *
 * This file contains the code for the Chatbot Knowledge Navigator analysis.
 * 
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Knowledge Navigator Analysis section callback - Ver 1.6.2
function chatbot_chatgpt_kn_analysis_section_callback($args) {
    ?>
    <p>Use the 'Download Data' button to retrieve the Knowledge Navigator results.</p>
    <?php
    if (is_admin()) {
        $header = " ";
        $header .= '<a class="button button-primary" href="' . esc_url(admin_url('admin-post.php?action=chatbot_chatgpt_kn_analysis_download_csv')) . '">Download Data</a>';
        echo $header;
    }
}


// Knowledge Navigator Analysis section callback - Ver 1.6.2
function chatbot_chatgpt_kn_analysis_output_callback($args) {
    // Get the saved chatbot_chatgpt_kn_analysis_choice value or default to "CSV"
    $output_choice = esc_attr(get_option('chatbot_chatgpt_kn_analysis_output', 'CSV'));
    // DIAG - Log the output choice
    // back_trace( 'NOTICE', '$output_choice' . $output_choice);
    ?>
    <select id="chatbot_chatgpt_kn_analysis_output" name="chatbot_chatgpt_kn_analysis_output">
        <option value="<?php echo esc_attr( 'CSV' ); ?>" <?php selected( $output_choice, 'CSV' ); ?>><?php echo esc_html( 'CSV' ); ?></option>
    </select>
    <?php
}


// Download the TF-IDF data
function chatbot_chatgpt_kn_analysis_download_csv(): void {

    // Generate the results directory path
    $results_dir_path = plugin_dir_path(__FILE__) . '../../results/';
    // back_trace( 'NOTICE', 'CHATBOT_CHATGPT_PLUGIN_DIR_PATH: ' . CHATBOT_CHATGPT_PLUGIN_DIR_PATH);
    // $results_dir_path = CHATBOT_CHATGPT_PLUGIN_DIR_PATH . 'results/';
    // back_trace( 'NOTICE', 'results_dir_path: ' . $results_dir_path);

    // Specify the output file's path
    $results_csv_file = $results_dir_path . 'results.csv';

    // Exit early if the file doesn't exist
    if (!file_exists($results_csv_file)) {
        // DIAG - Diagnostic - Ver 1.9.1
        // back_trace( 'NOTICE', 'File not found!');
        wp_die('File not found!');
    }

    if (can_use_curl_for_file_protocol()) {

        // Initialize a cURL session
        $curl = curl_init();

        // Set the cURL options
        curl_setopt($curl, CURLOPT_URL, 'file://' . realpath($results_csv_file));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        // Execute the cURL session
        $csv_data = curl_exec($curl);

        // Check for errors
        if ($csv_data === false) {
            wp_die('Error reading file: ' . curl_error($curl));
        }

        // Close the cURL session
        curl_close($curl);

        // Deliver the file for download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=Knowledge Navigator Results.csv');
        echo $csv_data;
        exit;

    } else {

        // DIAG - Diagnostic - Ver 1.9.1
        // back_trace( 'NOTICE', 'cURL is not enabled for the file protocol!');
        chatbot_chatgpt_general_admin_notice('cURL is not enabled for the file protocol!');
        // wp_die('cURL is not enabled for the file protocol!');

    }

}
add_action('admin_post_chatbot_chatgpt_kn_analysis_download_csv', 'chatbot_chatgpt_kn_analysis_download_csv');
