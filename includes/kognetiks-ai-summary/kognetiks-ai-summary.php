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

    // Diagnostics
    back_trace( 'NOTICE', 'Generating AI summary' );
    back_trace( 'NOTICE', '$pid: ' . $pid );

    // Set the model
    $model = $kchat_settings['chatbot_chatgpt_model'];
    back_trace( 'NOTICE', '$model at start of AI summaries: ' . $model );

    // Fetch and sanitize the content
    $query = $wpdb->prepare("SELECT post_content, post_modified FROM $wpdb->posts WHERE ID = %d", $pid);

    $row = $wpdb->get_row($query);

    $content = $row->post_content;
    $post_modified = $row->post_modified;

    // Check for an existing ai summary
    $ai_summary = ai_summary_exists($pid);
    if ( $ai_summary ) {
        back_trace( 'NOTICE', 'AI summary exists' );
        if ( ai_summary_is_stale($pid) ) {
            back_trace( 'NOTICE', 'AI summary is stale' );
            $ai_summary = generate_ai_summary_api($model, $content);
            update_ai_summary($pid, $ai_summary, $post_modified);
        }
    } else {
        back_trace( 'NOTICE', 'AI summary does not exist' );
        if ($model == null) {
            if (esc_attr(get_option('chatbot_ai_platform_choice')) == 'OpenAI') {
                $model = esc_attr(get_option('chatbot_chatgpt_model_choice', 'gpt-3.5-turbo'));
            } else if (esc_attr(get_option('chatbot_ai_platform_choice')) == 'NVIDIA') {
                $model = esc_attr(get_option('chatbot_nvidia_model_choice', 'nvidia/llama-3.1-nemotron-51b-instruct'));
            } else {
                $model = null; // No model selected
            }
        }
        $ai_summary = generate_ai_summary_api($model, $content);
        insert_ai_summary($pid, $ai_summary, $post_modified);
    }

    return $ai_summary;

}

// Generate an AI summary using the appropriate API
function generate_ai_summary_api( $model, $content ) {

    $content = htmlspecialchars(strip_tags($content), ENT_QUOTES, 'UTF-8');
    $content = preg_replace('/\s+/', ' ', $content);

    // Prepare special instructions if needed
    $special_instructions = "Here are some special instructions for the content that follows - please summarize this content in as few words as possible: ";

    // Retrieve the API key if needed
    $api_key = '';

    // Update the model in settings
    $kchat_settings['model'] = $model;

    // Call the appropriate API based on the model
    switch (true) {
        case str_starts_with($model, 'gpt'):
            back_trace( 'NOTICE', 'Calling ChatGPT API');
            $api_key = esc_attr(get_option('chatbot_chatgpt_api_key'));
            back_trace( 'NOTICE', 'Adding special instructions to the content');
            $message = $special_instructions . $content;
            $response = chatbot_chatgpt_call_api_basic($api_key, $message);
            break;
        case str_starts_with($model, 'nvidia'):
            back_trace( 'NOTICE', 'Calling NVIDIA API');
            $api_key = esc_attr(get_option('chatbot_nvidia_api_key'));
            back_trace( 'NOTICE', 'Adding special instructions to the content');
            $message = $special_instructions . $content;
            $response = chatbot_nvidia_call_api($api_key, $message);
            break;
        case str_starts_with($model, 'markov'):
            back_trace( 'NOTICE', 'Calling Markov Chain API');
            back_trace( 'NOTICE', 'No special instructions needed for ');
            $message = $content;
            $response = chatbot_chatgpt_call_markov_chain_api($message);
            break;
        case str_contains($model, 'context-model'):
            back_trace( 'NOTICE', 'Calling Transformer Model API');
            back_trace( 'NOTICE', 'No special instructions needed for ');
            $message = $content;
            $response = chatbot_chatgpt_call_transformer_model_api($message);
            break;
        default:
            back_trace( 'NOTICE', 'No valid model found for AI summary generation');
            $response = '';
            break;
    }

    $ai_summary = $response;
    back_trace( 'NOTICE', '$ai_summary: ' . $ai_summary );

    return $ai_summary;

}

// Create the ai summary table if it does not exist
function create_ai_summary_table() {

    // DIAG - Diagnostics - Ver 2.2.0
    back_trace( 'NOTICE', 'Creating AI summary table' );

    global $wpdb;

    $table_name = $wpdb->prefix . 'kognetiks_ai_summaries';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        post_id mediumint(9) NOT NULL,
        ai_summary text NOT NULL,
        post_modified datetime NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    // Handle any errors
    if ( $wpdb->last_error ) {
        back_trace( 'ERROR', 'Error creating AI summary table' );
    }

}

// Insert an AI summary into the ai summary table
function insert_ai_summary( $pid, $ai_summary, $post_modified ) {


    // DIAG - Diagnostics - Ver 2.2.0
    back_trace( 'NOTICE', 'Inserting AI summary into table' );

    global $wpdb;
    
    // Create the table if it does not exist
    create_ai_summary_table();

    $table_name = $wpdb->prefix . 'kognetiks_ai_summaries';

    $wpdb->insert(
        $table_name,
        array(
            'post_id' => $pid,
            'ai_summary' => $ai_summary,
            'post_modified' => $post_modified
        )
    );

    // Handle any errors
    if ( $wpdb->last_error ) {
        back_trace( 'ERROR', 'Error inserting AI summary into table' );
    }

}

// Check if an AI summary exists for a post
function ai_summary_exists( $pid ) {

    // DIAG - Diagnostics - Ver 2.2.0
    back_trace( 'NOTICE', 'Checking if AI summary exists' );

    global $wpdb;

    $table_name = $wpdb->prefix . 'kognetiks_ai_summaries';

    $query = $wpdb->prepare("SELECT ai_summary, post_modified FROM $table_name WHERE post_id = %d", $pid);

    $row = $wpdb->get_row($query);

    $ai_summary = $row->ai_summary;
    $post_modified = $row->post_modified;

    if ( $row ) {
        // DIAG - Diagnostics - Ver 2.2.0
        back_trace( 'NOTICE', 'AI summary exists' );
        return $ai_summary;
    } else {
        // DIAG - Diagnostics - Ver 2.2.0
        back_trace( 'NOTICE', 'AI summary does not exist' );
        return null;
    }

}

// Delete an AI summary from the ai summary table
function delete_ai_summary( $pid ) {

    // DIAG - Diagnostics - Ver 2.2.0
    back_trace( 'NOTICE', 'Deleting AI summary from table' );

    global $wpdb;

    $table_name = $wpdb->prefix . 'kognetiks_ai_summaries';

    $wpdb->delete(
        $table_name,
        array( 'post_id' => $pid )
    );

    // Handle any errors
    if ( $wpdb->last_error ) {
        back_trace( 'ERROR', 'Error deleting AI summary from table' );
    }

}

// Check if an AI summary is stale
function ai_summary_is_stale( $pid ) {

    // DIAG - Diagnostics - Ver 2.2.0
    back_trace( 'NOTICE', 'Checking if AI summary is stale' );

    global $wpdb;

    $table_name = $wpdb->prefix . 'kognetiks_ai_summaries';

    // Fetch post_modified from ai_summaries table
    $query = $wpdb->prepare("SELECT post_modified FROM $table_name WHERE post_id = %d", $pid);
    $row = $wpdb->get_row($query);
    $ai_post_modified = $row->post_modified;

    // Fetch post_modified from posts table
    $query = $wpdb->prepare("SELECT post_modified FROM $wpdb->posts WHERE ID = %d", $pid);
    $row = $wpdb->get_row($query);
    $post_modified = $row->post_modified;

    // Compare the dates
    if ( strtotime($ai_post_modified) < strtotime($post_modified) ) {
        // DIAG - Diagnostics - Ver 2.2.0
        back_trace( 'NOTICE', 'AI summary is stale' );
        return true;
    } else {
        // DIAG - Diagnostics - Ver 2.2.0
        back_trace( 'NOTICE', 'AI summary is not stale' );
        return false;
    }

}

// Update an AI summary in the ai summary table
function update_ai_summary( $pid, $ai_summary, $post_modified ) {

    // DIAG - Diagnostics - Ver 2.2.0
    back_trace( 'NOTICE', 'Updating AI summary in table' );

    global $wpdb;

    $table_name = $wpdb->prefix . 'kognetiks_ai_summaries';

    $wpdb->update(
        $table_name,
        array(
            'ai_summary' => $ai_summary,
            'post_modified' => $post_modified
        ),
        array( 'post_id' => $pid )
    );

    // Handle any errors
    if ( $wpdb->last_error ) {
        back_trace( 'ERROR', 'Error updating AI summary in table' );
    }

}

// Function to replace the excerpt with AI summary
function replace_excerpt_with_ai_summary( $excerpt, $post = null ) {

    // Check if AI summaries are enabled
    $enabled = esc_attr(get_option( 'kognetiks_ai_summary_enabled', 'No' ));
    $enabled = 'Yes';
    if ( 'Yes' !== $enabled ) {
        return $excerpt; // Return the default excerpt
    }

    // Get the global post if not provided
    if ( null === $post ) {
        global $post;
    } else {
        $post = get_post( $post );
    }

    if ( ! $post ) {
        return ''; // No post found, return empty string
    }

    // Check if the post is password protected
    if ( post_password_required( $post ) ) {
        return __( 'There is no excerpt because this is a protected post.' );
    }

    // Attempt to generate or retrieve the AI summary
    $ai_summary = generate_ai_summary( $post->ID ); // Replace with your actual function

    // If AI summary exists, use it
    if ( ! empty( $ai_summary ) ) {

        // Get the desired excerpt length from options
        $ai_summary_length = intval( get_option( 'kognetiks_ai_summary_length', 55 ) );

        // Trim the AI summary to the specified length
        $excerpt = wp_trim_words( $ai_summary, $ai_summary_length, '...' );

    } else {

        // AI summary not available, proceed with default excerpt generation

        $excerpt = $post->post_excerpt;

        if ( empty( $excerpt ) ) {
            $content = $post->post_content;
            $content = strip_shortcodes( $content );

            // Apply 'the_content' filters
            $content = apply_filters( 'the_content', $content );
            $content = str_replace( ']]>', ']]&gt;', $content );

            // Get the default excerpt length and more string
            $excerpt_length = apply_filters( 'excerpt_length', 55 );
            $excerpt_more   = apply_filters( 'excerpt_more', ' [&hellip;]' );

            // Generate the excerpt
            $excerpt = wp_trim_words( $content, $excerpt_length, $excerpt_more );
        }
    
    }

    // Return the final excerpt without re-applying 'get_the_excerpt' filter to avoid recursion
    return $excerpt;

}
// Hook the function into 'get_the_excerpt' filter
add_filter( 'get_the_excerpt', 'replace_excerpt_with_ai_summary', 10, 2 );