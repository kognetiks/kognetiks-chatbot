<?php
/**
 * Kognetiks Chatbot for WordPress - Download Transcript - Ver 1.9.9
 *
 * This file contains the code for uploading files as part
 * in support of Custom GPT Assistants via the Chatbot.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

function chatbot_chatgpt_download_transcript() {
    if (!isset($_POST['user_id'], $_POST['page_id'], $_POST['conversation_content'])) {
        wp_send_json_error('Missing required POST fields');
        return;
    }

    $user_id = sanitize_text_field($_POST['user_id']);
    $page_id = sanitize_text_field($_POST['page_id']);
    $conversation_content = sanitize_textarea_field($_POST['conversation_content']);

    // Get the base directory of the current file within the WordPress plugin directory
    $pluginBaseDir = plugin_dir_path(__FILE__);

    // Define the path to the transcripts directory
    $transcriptDir = $pluginBaseDir . 'transcripts/';

    // Ensure directory exists or attempt to create it
    if (!file_exists($transcriptDir)) {
        wp_mkdir_p($transcriptDir);
    }

    // Create the filename
    $transcriptFileName = 'transcript_' . date('Y-m-d_H-i-s') . '.txt';
    $transcriptFile = $transcriptDir . $transcriptFileName;

    // Attempt to write the content to the file
    if (file_put_contents($transcriptFile, $conversation_content) === false) {
        wp_send_json_error('Failed to write to file');
        return;
    }

    // Construct the URL for download
    $url = plugins_url('transcripts/' . $transcriptFileName, __FILE__);
    wp_send_json_success($url);
}
add_action('wp_ajax_chatbot_chatgpt_download_transcript', 'chatbot_chatgpt_download_transcript');
add_action('wp_ajax_nopriv_chatbot_chatgpt_download_transcript', 'chatbot_chatgpt_download_transcript');


// Get the conversation history
function chatbot_chatgpt_get_covnersation_transcript( $user_id, $page_id ) {
    
    global $wpdb;
    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;

    // $user_id = get_current_user_id();
    if ( $user_id !== '' ) {
        // DIAG = Diagnostics
        // back_trace( 'NOTICE', '$user_id: ' . $user_id);
    } else {
        // DIAG = Diagnostics
        // back_trace( 'NOTICE', 'No user is currently logged in.');
        // back_trace( 'NOTICE', '$session_id: ' . $session_id);
        // Removed - Ver 1.9.0
        // $user_id = $session_id;
        // Add back - Ver 1.9.3 - 2024 03 18
        $user_id = $_POST['user_id'];
    }

    if ( $page_id !== '') {
        // DIAG = Diagnostics
        // back_trace( 'NOTICE', '$page_id: ' . $page_id);
    } else {
        // DIAG = Diagnostics
        // back_trace( 'NOTICE', 'No page_id is currently set.');
        // back_trace( 'NOTICE', '$page_id: ' . $page_id);
        // Removed - Ver 1.9.0
        // $page_id = $session_id;
        // Add back - Ver 1.9.3 - 2024 03 18
        $page_id = $_POST['page_id'];
    }

    // DIAG - Diagnostics - Ver 1.9.9
    back_trace( 'NOTICE', '$user_id: ' . $user_id);
    back_trace( 'NOTICE', '$page_id: ' . $page_id);
    back_trace( 'NOTICE', '$session_id: ' . $session_id);

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log'; // Adjust the table name as necessary

    // New query with subquery for correct sorting
    $query = $wpdb->prepare(
        "SELECT c.id, c.user_type, c.interaction_time, c.message_text
        FROM $table_name c
        INNER JOIN (
            SELECT thread_id, MIN(interaction_time) as first_interaction_time
            FROM $table_name 
            WHERE session_id = %s AND user_id = %s AND page_id = %s
            AND user_type IN ('Chatbot', 'Visitor')
            AND DATE(interaction_time) = CURDATE()
            GROUP BY thread_id
        ) t ON c.thread_id = t.thread_id
        WHERE c.user_id = %s
        AND c.user_type IN ('Chatbot', 'Visitor')
        AND DATE(c.interaction_time) = CURDATE()
        ORDER BY t.first_interaction_time ASC, c.interaction_time ASC",
        $session_id, $user_id, $page_id, $user_id
    );
    
    $transcript = $wpdb->get_results($query);

    // DIAG - Diagnostics - Ver 1.9.9
    // back_trace( 'NOTICE', '$transcript: ' . print_r($transcript, true));
    
    if (empty($transcript)) {
        return 'No conversation found.';
    }
    
    // Assuming you need to format the results:
    $result_text = '';
    foreach ($transcript as $entry) {
        $result_text .= sprintf("[%s] %s: %s\n", $entry->interaction_time, $entry->user_type, $entry->message_text);
    }

    // DIAG - Diagnostics - Ver 1.9.9
    back_trace( 'NOTICE', '$result_text: ' . $result_text);
    
    return $result_text;

}