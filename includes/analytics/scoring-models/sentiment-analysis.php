<?php
/**
 * Kognetiks Analytics - Sentiment Analysis - Ver 1.0.0
 *
 * This file contains the code for the Kognetiks Sentiment Analysis.
 * It handles the sentiment analysis of the conversation.
 * 
 * 
 * @package kognetiks-analytics
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Add status tracking option
function kognetiks_analytics_get_scoring_status() {
    
    return get_option('kognetiks_analytics_scoring_status', 'stopped');

}

function kognetiks_analytics_set_scoring_status($status) {

    update_option('kognetiks_analytics_scoring_status', $status);

}

// Scoring lock helpers
function kognetiks_analytics_is_scoring_locked() {

    return get_option('kognetiks_analytics_scoring_lock', false) === '1';

}

function kognetiks_analytics_set_scoring_lock($locked = true) {

    update_option('kognetiks_analytics_scoring_lock', $locked ? '1' : '0');

}

// Stop the scoring process
function kognetiks_analytics_stop_scoring() {

    kognetiks_analytics_set_scoring_status('stopped');
    kognetiks_analytics_set_scoring_lock(false); // Clear lock on stop
    back_trace( 'NOTICE', 'Sentiment scoring process stopped');

}

// Reset all sentiment scores
function kognetiks_analytics_reset_scoring() {

    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';
    // Reset all sentiment scores to NULL
    $wpdb->query("UPDATE $table_name SET sentiment_score = NULL WHERE user_type IN ('Visitor', 'Chatbot')");
    kognetiks_analytics_set_scoring_lock(false); // Clear lock on reset
    back_trace( 'NOTICE', 'All sentiment scores have been reset');

}

// Restart the scoring process
function kognetiks_analytics_restart_scoring() {

    kognetiks_analytics_set_scoring_status('running');
    back_trace( 'NOTICE', 'Sentiment scoring process restarted');

}

// Score conversations without a sentiment score
function kognetiks_analytics_score_conversations_without_sentiment_score() {

    // DIAG - Diagnostics
    back_trace( 'NOTICE', 'Starting simple sentiment scoring process');
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';
    $batch_size = 100; // Process 100 records at a time

    // Get the last scoring date
    $last_scoring_date = get_option('kognetiks_analytics_last_scoring_date');
    $date_condition = '';
    if (!empty($last_scoring_date)) {
        $date_condition = $wpdb->prepare(" AND c.interaction_time >= %s", $last_scoring_date);
    }

    // Prevent concurrent runs
    if (kognetiks_analytics_is_scoring_locked()) {
        back_trace( 'NOTICE', 'Scoring is already running. Exiting.');
        return;
    }

    kognetiks_analytics_set_scoring_lock(true);

    // Check if scoring is stopped
    if (kognetiks_analytics_get_scoring_status() === 'stopped') {
        back_trace( 'NOTICE', 'Sentiment scoring process is stopped');
        kognetiks_analytics_set_scoring_lock(false);
        return;
    }

    // Create a temporary table to track processed IDs
    $temp_table = $wpdb->prefix . 'temp_processed_sentiment';
    $wpdb->query("CREATE TEMPORARY TABLE IF NOT EXISTS $temp_table (id bigint(20) PRIMARY KEY)");

    $total_processed = 0;
    $batch_number = 1;

    do {
        // Check if scoring has been stopped
        if (kognetiks_analytics_get_scoring_status() === 'stopped') {
            back_trace( 'NOTICE', 'Sentiment scoring process stopped after processing ' . $total_processed . ' conversations');
            break;
        }

        // Query for the next batch of records that haven't been processed
        $conversations = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT c.* FROM $table_name c 
                LEFT JOIN $temp_table t ON c.id = t.id 
                WHERE t.id IS NULL 
                AND (c.sentiment_score IS NULL OR c.sentiment_score = '' OR c.sentiment_score = 0)
                AND (c.user_type = 'Visitor' OR c.user_type = 'Chatbot')
                $date_condition
                ORDER BY c.id ASC 
                LIMIT %d",
                $batch_size
            ),
            ARRAY_A
        );

        // DIAG - Diagnostics
        back_trace( 'NOTICE', 'Processing batch #' . $batch_number . ' with ' . count($conversations) . ' conversations');

        if (empty($conversations)) {
            break;
        }

        foreach ($conversations as $conversation) {
            // Check if scoring has been stopped
            if (kognetiks_analytics_get_scoring_status() === 'stopped') {
                break;
            }

            $message_text = $conversation['message_text'];
            $sentiment_score = kognetiks_analytics_compute_sentiment_score($message_text);

            // Update the conversation with the sentiment score
            $wpdb->update(
                $table_name,
                array('sentiment_score' => $sentiment_score),
                array('id' => $conversation['id'])
            );

            // Mark this ID as processed
            $wpdb->insert($temp_table, array('id' => $conversation['id']));

            // DIAG - Diagnostics
            back_trace( 'NOTICE', 'Conversation ' . $conversation['id'] . ' scored with a sentiment score of ' . $sentiment_score);
        }

        $total_processed += count($conversations);
        $batch_number++;

        // DIAG - Diagnostics
        back_trace( 'NOTICE', 'Completed batch #' . ($batch_number - 1) . '. Total processed so far: ' . $total_processed);

    } while (count($conversations) > 0);

    // Clean up temporary table
    $wpdb->query("DROP TEMPORARY TABLE IF EXISTS $temp_table");

    // Set status to stopped when complete
    kognetiks_analytics_set_scoring_status('stopped');
    kognetiks_analytics_set_scoring_lock(false); // Clear lock at end

    // Set the last scoring date/time in the options table
    update_option('kognetiks_analytics_last_scoring_date', date('Y-m-d H:i:s'));

    // DIAG - Diagnostics
    back_trace( 'NOTICE', 'Completed scoring ' . $total_processed . ' conversations without a sentiment score');

    // DIAG - Diagnostics
    back_trace( 'NOTICE', 'Simple sentiment scoring process completed');

}

// Score conversations without a sentiment score
function kognetiks_analytics_score_conversations_without_sentiment_score_ai_based() {

    // DIAG - Diagnostics
    back_trace( 'NOTICE', 'Starting AI-basedsentiment scoring process');

    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';
    $batch_size = 100; // Process 100 records at a time

    // Get the last scoring date
    $last_scoring_date = get_option('kognetiks_analytics_last_scoring_date');
    $date_condition = '';
    if (!empty($last_scoring_date)) {
        $date_condition = $wpdb->prepare(" AND interaction_time >= %s", $last_scoring_date);
    }

    // Prevent concurrent runs
    if (kognetiks_analytics_is_scoring_locked()) {
        back_trace( 'NOTICE', 'Scoring is already running. Exiting.');
        return;
    }
    kognetiks_analytics_set_scoring_lock(true);

    // Check if scoring is stopped
    if (kognetiks_analytics_get_scoring_status() === 'stopped') {
        back_trace( 'NOTICE', 'Sentiment scoring process is stopped');
        kognetiks_analytics_set_scoring_lock(false);
        return;
    }

    // Check if any conversations have not been scored
    $conversations = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table_name 
            WHERE (sentiment_score IS NULL OR sentiment_score = '' OR sentiment_score = 0)
            AND (user_type = 'Visitor' OR user_type = 'Chatbot')
            $date_condition
            ORDER BY id ASC
            LIMIT %d",
            $batch_size
        ),
        ARRAY_A
    );

    // If there are no conversations to score, set the status to stopped
    if (empty($conversations)) {
        kognetiks_analytics_set_scoring_status('stopped');
        kognetiks_analytics_set_scoring_lock(false);
        back_trace( 'NOTICE', 'No conversations to score');
        return;
    }

    // If there are conversations to score, set the status to running
    kognetiks_analytics_set_scoring_status('running');

    $total_processed = 0;
    $batch_number = 1;

    do {
        // Check if scoring has been stopped
        if (kognetiks_analytics_get_scoring_status() === 'stopped') {
            back_trace( 'NOTICE', 'Sentiment scoring process stopped after processing ' . $total_processed . ' conversations');
            break;
        }
        // Query for the next batch of records with empty or NULL sentiment scores (no OFFSET!)
        $conversations = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name 
                WHERE (sentiment_score IS NULL OR sentiment_score = '' OR sentiment_score = 0) 
                AND (user_type = 'Visitor' OR user_type = 'Chatbot')
                $date_condition
                ORDER BY id ASC
                LIMIT %d",
                $batch_size
            ),
            ARRAY_A
        );
        // DIAG - Diagnostics
        back_trace( 'NOTICE', 'Processing batch #' . $batch_number . ' with ' . count($conversations) . ' conversations');
        if (empty($conversations)) {
            break;
        }
        foreach ($conversations as $conversation) {
            // Check if scoring has been stopped
            if (kognetiks_analytics_get_scoring_status() === 'stopped') {
                break;
            }
            $message_text = $conversation['message_text'];
            $sentiment_score = $conversation['sentiment_score'];
            // Score the conversation using the sentiment analysis model
            $sentiment_score = kognetiks_analytics_compute_sentiment_score_ai_based($message_text);
            // Update the conversation with the sentiment score
            $wpdb->update(
                $table_name,
                array('sentiment_score' => $sentiment_score),
                array('id' => $conversation['id'])
            );
            // DIAG - Diagnostics
            back_trace( 'NOTICE', 'Conversation ' . $conversation['id'] . ' scored with a sentiment score of ' . $sentiment_score);
        }
        $total_processed += count($conversations);
        $batch_number++;
        // DIAG - Diagnostics
        back_trace( 'NOTICE', 'Completed batch #' . ($batch_number - 1) . '. Total processed so far: ' . $total_processed);
    } while (count($conversations) > 0);

    // Set status to stopped when complete
    kognetiks_analytics_set_scoring_status('stopped');
    kognetiks_analytics_set_scoring_lock(false); // Clear lock at end
    
    // DIAG - Diagnostics
    back_trace( 'NOTICE', 'Completed scoring ' . $total_processed . ' conversations without a sentiment score');

    // DIAG - Diagnostics
    back_trace( 'NOTICE', 'AI-based sentiment scoring process completed');

}

// Get the scoring control mode (Manual/Automated)
function kognetiks_analytics_get_scoring_control_mode() {
    
    return get_option('kognetiks_analytics_scoring_control', 'Manual');

}

// Schedule the automated scoring cron job
function kognetiks_analytics_schedule_scoring_cron() {

    if (!wp_next_scheduled('kognetiks_analytics_automated_scoring')) {
        wp_schedule_event(time(), 'hourly', 'kognetiks_analytics_automated_scoring');
        back_trace( 'NOTICE', 'Automated scoring cron job scheduled');
    }

}

// Unschedule the automated scoring cron job
function kognetiks_analytics_unschedule_scoring_cron() {

    $timestamp = wp_next_scheduled('kognetiks_analytics_automated_scoring');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'kognetiks_analytics_automated_scoring');
        back_trace( 'NOTICE', 'Automated scoring cron job unscheduled');
    }

}

// Cron job callback function
function kognetiks_analytics_automated_scoring_callback() {

    // Only run if scoring control is set to Automated
    if (kognetiks_analytics_get_scoring_control_mode() === 'Automated') {
        back_trace( 'NOTICE', 'Running automated scoring cron job');
        kognetiks_analytics_score_conversations_without_sentiment_score();
    } else {
        // If somehow the cron job is still running but mode is Manual, unschedule it
        kognetiks_analytics_unschedule_scoring_cron();
    }

}
add_action('kognetiks_analytics_automated_scoring', 'kognetiks_analytics_automated_scoring_callback');

// Set the scoring control mode (Manual/Automated)
function kognetiks_analytics_set_scoring_control_mode($mode) {

    if (!in_array($mode, ['Manual', 'Automated'])) {
        return false;
    }

    $current_mode = kognetiks_analytics_get_scoring_control_mode();
    
    // Update the option in the database
    update_option('kognetiks_analytics_scoring_control', $mode);

    // Handle cron job based on mode change
    if ($mode === 'Automated' && $current_mode !== 'Automated') {
        // Switching to Automated - schedule the cron job
        kognetiks_analytics_schedule_scoring_cron();
    } elseif ($mode === 'Manual' && $current_mode !== 'Manual') {
        // Switching to Manual - unschedule the cron job
        kognetiks_analytics_unschedule_scoring_cron();
    }

    return true;

}

// Cleanup function to unschedule cron job on plugin deactivation
function kognetiks_analytics_deactivate() {

    kognetiks_analytics_unschedule_scoring_cron();

}
register_deactivation_hook(__FILE__, 'kognetiks_analytics_deactivate');

// Wrapper function to compute sentiment score using either simple or AI-based method
function kognetiks_analytics_compute_sentiment_score($message_text) {

    // Get the scoring method from options
    $scoring_method = get_option('kognetiks_analytics_scoring_method', 'simple');
    
    if ($scoring_method === 'ai_based') {
        // FIXME - AI-based scoring is future functionality
        // $score = kognetiks_analytics_compute_sentiment_score_ai_based($message_text);
    } else {
        $score = kognetiks_analytics_compute_sentiment_score_simple($message_text);
    }
    
    // Format score to one decimal place using standard rounding
    return round($score, 1);
    
}

// Compute the sentiment score for a message using a simple algorithm
function kognetiks_analytics_compute_sentiment_score_simple($message_text) {

    global $sentiment_words, $negator_words, $intensifier_words;
    
    // Convert to lowercase and remove punctuation
    $message_text = strtolower($message_text);
    $message_text = preg_replace('/[^\w\s]/', ' ', $message_text);
    
    // Split into words
    $tokens = preg_split('/\s+/', $message_text, -1, PREG_SPLIT_NO_EMPTY);
    
    $score = 0;
    $count = 0;
    $negator = false;
    
    foreach ($tokens as $word) {

        // Check for negators
        if (in_array($word, $negator_words)) {
            $negator = true;
            continue;
        }
        
        // Check for intensifiers
        $intensity = 1;
        if (isset($intensifier_words[$word])) {
            $intensity = $intensifier_words[$word];
            continue;
        }
        
        // Check for sentiment words
        if (isset($sentiment_words[$word])) {
            $word_score = $sentiment_words[$word];
            // Apply negator if present
            if ($negator) {
                $word_score = -$word_score;
                $negator = false;
            }
            // Apply intensity
            $word_score *= $intensity;
            $score += $word_score;
            $count++;
        }
    }
    
    if ($count === 0) return 0.0;
    
    // Normalize score to -1.0 to 1.0
    $normalized_score = $score / ($count * 5); // assuming max abs score = 5
    return max(min($normalized_score, 1.0), -1.0);

}   

// Compute the sentiment score for a message using an AI model
function kognetiks_analytics_compute_sentiment_score_ai_based($message_text) {

    // DIAG - Diagnostics
    // back_trace( 'NOTICE', 'Analyzing sentiment of message: ' . $message_text);

    // Get the AI Platform Choice from the options table
    $ai_platform_choice = get_option('chatbot_ai_platform_choice');

    // Get the AI Model from the options table
    switch ($ai_platform_choice) {  
        case 'OpenAI':
            $ai_model_choice = get_option('chatbot_chatgpt_model_choice');
            break;
        case 'Anthropic':
            $ai_model_choice = get_option('chatbot_anthropic_model_choice');
            break;
        case 'Azure OpenAI':
            $ai_model_choice = get_option('chatbot_azure_model_choice');
            break;
        case 'DeepSeek':
            $ai_model_choice = get_option('chatbot_deepseek_model_choice');
            break;
        case 'Mistral':
            $ai_model_choice = get_option('chatbot_mistral_model_choice');
            break;  
        case 'NVIDIA':
            $ai_model_choice = get_option('chatbot_nvidia_model_choice');
            break;
        case 'Local Server':
            $ai_model_choice = get_option('chatbot_local_model_choice');
            break;
        default:
            $ai_model_choice = 'gpt-3.5-turbo';
    }

    $sentiment_prompt = 'You are a sentiment analysis model. Your task is to analyze the sentiment of the message and return only the (no other text)score between -1.0 and 1.0. -1.0 is negative, 0 is neutral, and 1.0 is positive. Rate this message from -1.0 to 1.0: ';
    
    // Initialize sentiment score
    $sentiment_score = 0;
    
    // Get the API Key from the options table
    switch ($ai_platform_choice) {
        case 'OpenAI':
            $api_key = get_option('chatbot_chatgpt_api_key');
            // Decrypt the API Key
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            // Call OpenAI API
            $sentiment_score = kognetiks_analytics_openai_api_call($api_key, $sentiment_prompt . $message_text);
            break;
        case 'Anthropic':
            $api_key = get_option('chatbot_anthropic_api_key');
            // Decrypt the API Key
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            // Call Anthropic API
            $sentiment_score = kognetiks_analytics_anthropic_api_call($api_key, $sentiment_prompt . $message_text);
            break;
        case 'Azure OpenAI':
            $api_key = get_option('chatbot_azure_api_key');
            // Decrypt the API Key
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            // Call Azure OpenAI API
            $sentiment_score = kognetiks_analytics_azure_api_call($api_key, $sentiment_prompt . $message_text);
            break;
        case 'DeepSeek':
            $api_key = get_option('chatbot_deepseek_api_key');
            // Decrypt the API Key
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            // Call DeepSeek API
            $sentiment_score = kognetiks_analytics_deepseek_api_call($api_key, $sentiment_prompt . $message_text);
            break;
        case 'Mistral':
            $api_key = get_option('chatbot_mistral_api_key');
            // Decrypt the API Key
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            // Call Mistral API
            $sentiment_score = kognetiks_analytics_mistral_api_call($api_key, $sentiment_prompt . $message_text);
            break;
        case 'NVIDIA':
            $api_key = get_option('chatbot_nvidia_api_key');
            // Decrypt the API Key
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            // Call NVIDIA API
            $sentiment_score = kognetiks_analytics_nvidia_api_call($api_key, $sentiment_prompt . $message_text);
            break;
        case 'Local Server':
            $api_key = get_option('chatbot_local_server_api_key');
            // Decrypt the API Key
            $api_key = chatbot_chatgpt_decrypt_api_key($api_key);
            // Call the Local Server API
            $sentiment_score = kognetiks_analytics_local_api_call($api_key, $sentiment_prompt . $message_text);
            break;
        default:
            // If no platform is selected, return neutral sentiment
            $sentiment_score = 0;
            break;
    }

    // Ensure the sentiment score is a valid number between -1 and 1
    $sentiment_score = floatval($sentiment_score);
    if ($sentiment_score < -1) $sentiment_score = -1;
    if ($sentiment_score > 1) $sentiment_score = 1;

    // Return the sentiment score
    return $sentiment_score;

}
