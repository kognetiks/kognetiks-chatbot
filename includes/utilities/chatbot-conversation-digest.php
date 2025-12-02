<?php
/**
 * Kognetiks Chatbot - Chatbot Conversation Context
 *
 * This file contains the code for building the conversation context for the chatbot.
 *
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

/**
 * 
 * This module will send conversation digests to a designed email address
 * 
 * @return void
 */

// Schedule conversation digest cron job - Ver 2.3.9
function chatbot_chatgpt_schedule_conversation_digest() {

    // DIAG - Diagnostics - Ver 2.3.9
    // back_trace( 'NOTICE', 'chatbot_chatgpt_schedule_conversation_digest()');

    // Get the enabled setting
    $enabled = esc_attr(get_option('chatbot_chatgpt_conversation_digest_enabled', 'No'));
    
    // If not enabled, don't schedule
    if ($enabled !== 'Yes') {
        return;
    }
    
    // Get the email address
    $email_address = esc_attr(get_option('chatbot_chatgpt_conversation_digest_email', ''));
    
    // If email is empty, don't schedule
    if (empty($email_address)) {
        return;
    }
    
    // Get the frequency setting
    $frequency = esc_attr(get_option('chatbot_chatgpt_conversation_digest_frequency', 'Daily'));
    
    // Clear any existing scheduled hooks
    wp_clear_scheduled_hook('chatbot_chatgpt_send_conversation_digest_hook');
    
    // Map frequency to WordPress cron intervals
    $interval_mapping = array(
        'Hourly' => 'hourly',
        'Daily' => 'daily',
        'Weekly' => 'weekly'
    );
    
    $interval = isset($interval_mapping[$frequency]) ? $interval_mapping[$frequency] : 'daily';
    
    // Schedule the event
    $timestamp = time() + 60; // Start 60 seconds from now
    wp_schedule_event($timestamp, $interval, 'chatbot_chatgpt_send_conversation_digest_hook');
    
}

// Send conversation digest email - Ver 2.3.9
function chatbot_chatgpt_send_conversation_digest() {

    // DIAG - Diagnostics - Ver 2.3.9
    // back_trace( 'NOTICE', 'chatbot_chatgpt_send_conversation_digest()');

    // Get the enabled setting
    $enabled = esc_attr(get_option('chatbot_chatgpt_conversation_digest_enabled', 'No'));
    
    // If not enabled, don't send
    if ($enabled !== 'Yes') {
        return;
    }
    
    // Get the email address
    $email_address = esc_attr(get_option('chatbot_chatgpt_conversation_digest_email', ''));
    
    // If email is empty, don't send
    if (empty($email_address)) {
        return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';
    
    // Get the last digest timestamp (stored as an option)
    $last_digest_timestamp = get_option('chatbot_chatgpt_last_digest_timestamp', '');
    
    // If this is the first run, get conversations from the last 24 hours
    if (empty($last_digest_timestamp)) {
        $start_time = date('Y-m-d H:i:s', strtotime('-24 hours'));
    } else {
        $start_time = $last_digest_timestamp;
    }
    
    // Query for new conversations (only Visitor and Chatbot messages, not token data)
    $query = $wpdb->prepare("
        SELECT id, session_id, user_id, page_id, interaction_time, user_type, message_text, thread_id, assistant_id, assistant_name
        FROM $table_name
        WHERE interaction_time > %s
        AND user_type IN ('Chatbot', 'Visitor')
        ORDER BY interaction_time ASC
    ", $start_time);
    
    $conversations = $wpdb->get_results($query);
    
    // If no new conversations, don't send email
    if (empty($conversations)) {
        // Update the last digest timestamp even if no conversations
        update_option('chatbot_chatgpt_last_digest_timestamp', current_time('mysql'));
        return;
    }
    
    // Organize conversations by session
    $conversations_by_session = array();
    foreach ($conversations as $conversation) {
        $session_id = $conversation->session_id;
        if (!isset($conversations_by_session[$session_id])) {
            $conversations_by_session[$session_id] = array();
        }
        $conversations_by_session[$session_id][] = $conversation;
    }
    
    // Build email content
    $subject = 'Chatbot Conversation Digest - ' . date('Y-m-d H:i:s');
    $message = "New Chatbot Conversations Digest\n\n";
    $message .= "Period: " . date('Y-m-d H:i:s', strtotime($start_time)) . " to " . current_time('mysql') . "\n\n";
    $message .= "Total Conversations: " . count($conversations_by_session) . "\n";
    $message .= "Total Messages: " . count($conversations) . "\n\n";
    $message .= "---\n\n";
    
    // Add each conversation session
    $session_count = 0;
    foreach ($conversations_by_session as $session_id => $session_conversations) {
        $session_count++;
        $message .= "Conversation #" . $session_count . " (Session ID: " . $session_id . ")\n";
        
        // Get session metadata from first message
        $first_message = $session_conversations[0];
        if (!empty($first_message->user_id)) {
            $message .= "User ID: " . $first_message->user_id . "\n";
        }
        if (!empty($first_message->page_id)) {
            $message .= "Page ID: " . $first_message->page_id . "\n";
        }
        if (!empty($first_message->thread_id)) {
            $message .= "Thread ID: " . $first_message->thread_id . "\n";
        }
        if (!empty($first_message->assistant_name)) {
            $message .= "Assistant: " . $first_message->assistant_name . "\n";
        }
        $message .= "Started: " . $first_message->interaction_time . "\n";
        $message .= "\n";
        
        // Add messages in chronological order
        foreach ($session_conversations as $msg) {
            $user_label = ($msg->user_type === 'Visitor') ? 'Visitor' : 'Chatbot';
            $message .= "[" . $msg->interaction_time . "] " . $user_label . ": " . $msg->message_text . "\n";
        }
        
        $message .= "\n---\n\n";
    }
    
    $message .= "\nThis is an automated digest from your Chatbot Conversation Logging system.\n";
    
    // Send the email
    $sent = wp_mail($email_address, $subject, $message);
    
    // Update the last digest timestamp
    if ($sent) {
        update_option('chatbot_chatgpt_last_digest_timestamp', current_time('mysql'));
    }
    
}

// Register the cron action hook - Ver 2.3.9
add_action('chatbot_chatgpt_send_conversation_digest_hook', 'chatbot_chatgpt_send_conversation_digest');


