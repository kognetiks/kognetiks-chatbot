<?php
/**
 * Kognetiks Chatbot - Knowledge Navigator - TF-IDF Analyzer
 *
 * This file contains the code for the Chatbot Knowledge Navigator analysis.
 * 
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Knowledge Navigator Analysis section callback - Ver 1.6.2
function chatbot_chatgpt_kn_analysis_section_callback($args) {
    ?>
    <p>Use the 'Download Data' button to retrieve the Knowledge Navigator results.</p>
    <p style="background-color: #e0f7fa; padding: 10px;"><b>For an explanation on how to use the Knowledge Navigator Analysis and additional documentation please click <a href="?page=chatbot-chatgpt&tab=support&dir=knowledge-navigator&file=knowledge-navigator-analysis.md">here</a>.</b></p>
    <?php
    if (is_admin()) {
        $download_url = wp_nonce_url(
            admin_url( 'admin-post.php?action=chatbot_chatgpt_kn_analysis_download_csv' ),
            chatbot_chatgpt_kn_download_nonce_action()
        );
        $header = ' ';
        $header .= '<a class="button button-primary" href="' . esc_url( $download_url ) . '">Download Data</a>';
        echo wp_kses_post( $header );
    }
}


// Knowledge Navigator Analysis section callback - Ver 1.6.2
function chatbot_chatgpt_kn_analysis_output_callback($args) {
    // Get the saved chatbot_chatgpt_kn_analysis_choice value or default to "CSV"
    $output_choice = esc_attr(get_option('chatbot_chatgpt_kn_analysis_output', 'CSV'));
    ?>
    <select id="chatbot_chatgpt_kn_analysis_output" name="chatbot_chatgpt_kn_analysis_output">
        <option value="<?php echo esc_attr( 'CSV' ); ?>" <?php selected( $output_choice, 'CSV' ); ?>><?php echo esc_html( 'CSV' ); ?></option>
    </select>
    <?php
}


// Download the TF-IDF data
function chatbot_chatgpt_kn_analysis_download_csv() {

    if ( chatbot_chatgpt_kn_download_authorization_error() !== '' ) {
        wp_die(
            esc_html__( 'You do not have sufficient permissions to access this page.', 'chatbot-chatgpt' ),
            '',
            array( 'response' => 403 )
        );
    }

    $storage_key = chatbot_chatgpt_kn_export_storage_key( 'csv' );
    $csv_data = ( $storage_key === '' ) ? '' : get_option( $storage_key, '' );

    if ( ! is_string( $csv_data ) || $csv_data === '' ) {
        wp_die(
            esc_html__( 'File not found.', 'chatbot-chatgpt' ),
            '',
            array( 'response' => 404 )
        );
    }

    nocache_headers();
    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename="knowledge-navigator-results.csv"' );
    header( 'Content-Length: ' . strlen( $csv_data ) );
    header( 'X-Content-Type-Options: nosniff' );

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV download, not HTML.
    echo $csv_data;
    exit;

}
add_action('admin_post_chatbot_chatgpt_kn_analysis_download_csv', 'chatbot_chatgpt_kn_analysis_download_csv');
