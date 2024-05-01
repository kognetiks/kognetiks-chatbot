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

    global $wpdb;
    global $session_id;
    global $user_id;
    global $page_id;
    global $thread_id;
    global $assistant_id;

    if (isset($_POST['user_id']) && isset($_POST['page_id'])) {
        $user_id = $_POST['user_id'];
        $page_id = $_POST['page_id'];
    }

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
    back_trace( 'NOTICE', '$thread_id: ' . $thread_id);
    back_trace( 'NOTICE', '$assistant_id: ' . $assistant_id);
    
    // Get the conversation transcript
    // $transcript = chatbot_chatgpt_get_covnersation_transcript( $user_id, $page_id );
    $transcript = $_post['conversation_content'];

    // DIAG - Diagnostics - Ver 1.9.9
    back_trace( 'NOTICE', '$transcript: ' . print_r($transcript, true));

    $debug_dir_path = CHATBOT_CHATGPT_PLUGIN_DIR_PATH . 'transcripts/';

    if (!file_exists($debug_dir_path)) {
        mkdir($debug_dir_path, 0777, true);
    }

    $transcriptFile = $debug_dir_path . 'transcript' . ' ' . date('Y-m-d H-i-s') . '.txt';

    // DIAG - Diagnostics - Ver 1.9.9
    back_trace( 'NOTICE', '$transcriptFile: ' . $transcriptFile);

    // Create a new file
    $file = fopen( $transcriptFile, 'w' );

    // Write the transcript to the file
    fwrite( $file, $transcript );

    // Close the file
    fclose( $file );

    if (can_use_curl_for_file_protocol()) {

        // Use $_POST instead of $_post
        $transcript = isset($_POST['conversation_content']) ? $_POST['conversation_content'] : '';

        // Check if $transcript is not empty before writing to the file
        if (!empty($transcript)) {
            $file = fopen($transcriptFile, 'w');
            fwrite($file, $transcript);
            fclose($file);
        } else {
            $class = 'notice notice-error';
            $message = __( 'No transcript content to write', 'chatbot-chatgpt' );
            chatbot_chatgpt_general_admin_notice($message);
            return;
        }

        // Define $transcript_data before using it
        $transcript_data = file_get_contents(realpath($transcriptFile));

        // Check for errors
        if ($transcript_data === false) {
            $class = 'notice notice-error';
            $message = __( 'Error reading file', 'chatbot-chatgpt' );
            chatbot_chatgpt_general_admin_notice($message);
            return;
        }

        // Deliver the file for download
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment;filename=' . basename($transcriptFile));
        echo $transcript_data;

        // Delete the file
        unlink($transcriptFile);
        exit;

    } else {
            
        $class = 'notice notice-error';
        $message = __( 'cURL is not enabled for the file protocol!', 'chatbot-chatgpt' );
        chatbot_chatgpt_general_admin_notice($message);
        return;

    }

}
add_action('wp_ajax_chatbot_chatgpt_download_transcript', 'chatbot_chatgpt_download_transcript');  // Handles AJAX request for logged-in users
add_action('wp_ajax_nopriv_chatbot_chatgpt_download_transcript', 'chatbot_chatgpt_download_transcript');  // Handles AJAX request for logged-out users

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