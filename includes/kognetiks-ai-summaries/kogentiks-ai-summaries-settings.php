<?php
/**
 * Kognetiks Chatbot for WordPress - Settings - Kogentiks AI Summaries - Ver 2.2.1
 *
 * This file contains the code for the Kognetiks AI Summaries settings page.
 * It handles the support settings and other parameters.
 * 
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Callback for settings section
function kogentiks_ai_summaries_section_callback() {
    ?>
    <p>Configure the settings for AI-generated summaries below.</p>
    <p>The AI Summaries feature enables your Kognetiks Chatbot to automatically generate concise summaries of pages, posts, and other content. These summaries can enhance the Chatbot and Search functionalities by providing visitors with quick, AI-powered insights into your content. Summaries are generated only when they do not already exist or if the content has been updated since the summary was last created.</p>
    <p style="background-color: #e0f7fa; padding: 10px;"><b>For an explanation of the AI Summaries settings and additional documentation please click <a href="?page=chatbot-chatgpt&tab=support&dir=ai-summaries&file=ai-summaries.md">here</a>.</b></p>
    <?php
}

// Callback for individual fields
function chatbot_chatgpt_enhanced_response_include_ai_summary_callback() {
    $value = get_option('chatbot_chatgpt_enhanced_response_include_ai_summary', 'No');
    ?>
    <select id="chatbot_chatgpt_enhanced_response_include_ai_summary" name="chatbot_chatgpt_enhanced_response_include_ai_summary">
        <option value="No" <?php selected( $value, 'No' ); ?>><?php echo esc_html( 'No' ); ?></option>
        <option value="Yes" <?php selected( $value, 'Yes' ); ?>><?php echo esc_html( 'Yes' ); ?></option>
    </select>
    <?php
}

function kognetiks_ai_summaries_enabled_callback() {
    $value = get_option('kognetiks_ai_summaries_enabled', 'No');
    ?>
    <select id="kognetiks_ai_summaries_enabled" name="kognetiks_ai_summaries_enabled">
        <option value="No" <?php selected( $value, 'No' ); ?>><?php echo esc_html( 'No' ); ?></option>
        <option value="Yes" <?php selected( $value, 'Yes' ); ?>><?php echo esc_html( 'Yes' ); ?></option>
    </select>
    <?php
}

function kognetiks_ai_summaries_length_callback() {
    $value = get_option('kognetiks_ai_summaries_length', 55);
    ?>
    <select id="kognetiks_ai_summaries_length" name="kognetiks_ai_summaries_length">
        <?php
        for ( $i = 1; $i <= 500; $i++ ) {
            echo '<option value="' . esc_attr( $i ) . '" ' . selected( $value, (string) $i, false ) . '>' . esc_html( $i ) . '</option>';
        }
        ?>
    </select>
    <?php
}

// Function to register Kogentiks AI Summaries settings
function kogentiks_ai_summaries_settings_init() {

    // Register settings
    register_setting( 'kognetiks_ai_summaries', 'kognetiks_ai_summaries_enabled' );
    register_setting( 'kognetiks_ai_summaries', 'kognetiks_ai_summaries_length' );
    register_setting( 'kognetiks_ai_summaries', 'chatbot_chatgpt_enhanced_response_include_ai_summary' );

    // AI Summary Settings - Ver 2.2.1
    add_settings_section(
        'kogentiks_ai_summaries_section',
        'AI Summaries Settings',
        'kogentiks_ai_summaries_section_callback',
        'kognetiks_ai_summaries'
    );

    // Add fields
    add_settings_field(
        'chatbot_chatgpt_enhanced_response_include_ai_summary',
        'Enable AI Summaries for Enhanced Responses',
        'chatbot_chatgpt_enhanced_response_include_ai_summary_callback',
        'kognetiks_ai_summaries',
        'kogentiks_ai_summaries_section'
    );

    add_settings_field(
        'kognetiks_ai_summaries_enabled',
        'Enable AI Summaries for Site Search',
        'kognetiks_ai_summaries_enabled_callback',
        'kognetiks_ai_summaries',
        'kogentiks_ai_summaries_section'
    );

    add_settings_field(
        'kognetiks_ai_summaries_length',
        'AI Summaries Length (Words)',
        'kognetiks_ai_summaries_length_callback',
        'kognetiks_ai_summaries',
        'kogentiks_ai_summaries_section'
    );
}
add_action( 'admin_init', 'kogentiks_ai_summaries_settings_init' );
