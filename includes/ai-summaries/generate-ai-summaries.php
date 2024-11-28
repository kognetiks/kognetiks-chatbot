<?php
/**
 * Kognetiks Chatbot for WordPress - Generate AI Summaries - Ver 2.2.0
 *
 * This file contains the code generating AI summaries for the pages or posts returned when enhanced responses are turned on or called directly via a search inquiry.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Return an AI summary for the page or post
function generate_ai_summary( $pid )  {

    global $wpdb;
    global $kchat_settings;

    // DIAG - Diagnostics - Ver 2.2.0
    back_trace( 'NOTICE', 'Generating AI summary' );
    back_trace( 'NOTICE', '$pid: ' . $pid );
    // back_trace( 'NOTICE', '$kchat_settings: ' . print_r($kchat_settings, true));

    
    // Set the Model before the call to the API
    $model = $kchat_settings['chatbot_chatgpt_model'];
    
    // DIAG - Diagnostics - Ver 2.2.0
    back_trace( 'NOTICE', '$model: ' . $model );

    // Retrieve the API key
    if ($model = str_starts_with($model,'nvidia')) {

        $api_key = esc_attr(get_option('chatbot_nvidia_api_key'));
        if (empty($api_key)) {
            back_trace( 'NOTICE', 'No API key found for NVIDIA API');
            return '';
        }

    } else if ($model = str_starts_with($model,'gpt')) {

        $api_key = esc_attr(get_option('chatbot_chatgpt_api_key'));
        if (empty($api_key)) {
            back_trace( 'NOTICE', 'No API key found for ChatGPT API');
            return '';
        }

    } else {

        // No API key is needed for Markov Chain or Transformer Models

    }

    // Fetch the content of the page or post
    $content = get_post_field( 'post_content', $pid );

    // Sanitize the content
    $content = htmlspecialchars(strip_tags($content), ENT_QUOTES, 'UTF-8');

    // Remove any extra spaces and newlines
    $content = preg_replace('/\s+/', ' ', $content);

    // Add special instructions for the content
    $special_instructions = "Here are some special instructions for the content that follows - please summarize this content in as few words as possible: ";

    if (strpos($model, 'markov') === 0 || strpos($model, 'context-model') === 0) {
        // No special instructions needed for Markov Chain or Transformer Models
        $content = $content;
    } else {
        // Try and add the special instructions to the content to keep the AI focused on the task
        $content = $special_instructions . $content;
    }

    // Add the payload to the message
    $message = $content;

    // DIAG - Diagnostics - Ver 2.2.0
    // back_trace( 'NOTICE', '$content: ' . $content );

    // Belt & Suspenders - Check for empty API key
    $model = $kchat_settings['chatbot_chatgpt_model'];
    back_trace( 'NOTICE', '$model: ' . $model );

    // Call the API to generate the AI summary
    if (str_starts_with($model !== null && $model, 'gpt') !== false) {
        // The string 'gpt' is found in $model
        // Reload the model - BELT & SUSPENDERS
        $kchat_settings['model'] = $model;
        // DIAG - Diagnostics - Ver 2.1.8
        back_trace( 'NOTICE', 'Calling ChatGPT API');
        // Send message to ChatGPT API - Ver 1.6.7
        $response = chatbot_chatgpt_call_api_vanilla($api_key, $message);
    } elseif ($model !==null && str_starts_with($model,'nvidia')) {
        $kchat_settings['model'] = $model;
        // DIAG - Diagnostics - Ver 2.1.8
        back_trace( 'NOTICE', 'Calling NVIDIA API');
        // Send message to NVIDIA API - Ver 2.1.8
        $response = chatbot_nvidia_call_api($api_key, $message);
        // back_trace( 'NOTICE', 'LINE 910 - NVIDIA API Response: ' . $response);
    } elseif ($model !== null && str_starts_with($model,'markov')) {
        $kchat_settings['model'] = $model;
        // DIAG - Diagnostics - Ver 2.1.8
        back_trace( 'NOTICE', 'Calling Markov Chain API');
        // Send message to Markov API - Ver 1.9.7
        $response = chatbot_chatgpt_call_markov_chain_api($message);
    } elseif ($model !== null && str_contains($model,'context-model')) {
        $kchat_settings['model'] = $model;
        // DIAG - Diagnostics - Ver 2.2.0
        back_trace( 'NOTICE', 'Calling Transformer Model API');
        // Send message to Transformer Model API - Ver 2.2.0
        $response = chatbot_chatgpt_call_transformer_model_api($message);
    } else {
        $response = '';
    }

    $ai_summary = $response;

    // DIAG - Diagnostics - Ver 2.2.0
    back_trace( 'NOTICE', '$ai_summary: ' . $ai_summary );

    return $ai_summary;

}