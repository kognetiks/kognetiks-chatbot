<?php
/**
 * Kognetiks Chatbot for WordPress - Registration - Knowledge Navigator Settings - Ver 2.0.0
 *
 * This file contains the code for the Chatbot settings page.
 * It handles the registration of settings and other parameters.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Register settings
function chatbot_chatgpt_kn_settings_init(): void {

    // Knowledge Navigator Tab
    
    // Knowledge Navigator Settings and Schedule - Ver 2.0.0
    add_settings_section(
        'chatbot_chatgpt_knowledge_navigator_settings_section',
        'Knowledge Navigator',
        'chatbot_chatgpt_knowledge_navigator_section_callback',
        'chatbot_chatgpt_knowledge_navigator'
    );

    // Knowledge Navigator Status
    add_settings_section(
        'chatbot_chatgpt_kn_status_section',
        'Knowledge Navigator Status',
        'chatbot_chatgpt_kn_status_section_callback',
        'chatbot_chatgpt_kn_status'
    );

    // Knowledge Navigator Settings and Schedule - Ver 2.0.0
    register_setting('chatbot_chatgpt_knowledge_navigator', 'chatbot_chatgpt_kn_schedule');
    register_setting('chatbot_chatgpt_knowledge_navigator', 'chatbot_chatgpt_kn_maximum_top_words');
    register_setting('chatbot_chatgpt_knowledge_navigator', 'chatbot_chatgpt_kn_tuning_percentage');

    add_settings_section(
        'chatbot_chatgpt_kn_scheduling_section',
        'Knowledge Navigator Scheduling',
        'chatbot_chatgpt_kn_settings_section_callback',
        'chatbot_chatgpt_kn_scheduling'
    );

    add_settings_field(
        'chatbot_chatgpt_kn_schedule',
        'Select Run Schedule',
        'chatbot_chatgpt_kn_schedule_callback',
        'chatbot_chatgpt_kn_scheduling',
        'chatbot_chatgpt_kn_scheduling_section'
    );

    add_settings_field(
        'chatbot_chatgpt_kn_maximum_top_words',
        'Maximum Top Words',
        'chatbot_chatgpt_kn_maximum_top_words_callback',
        'chatbot_chatgpt_kn_scheduling',
        'chatbot_chatgpt_kn_scheduling_section'
    );

    add_settings_field(
        'chatbot_chatgpt_kn_tuning_percentage',
        'Tuning Percentage',
        'chatbot_chatgpt_kn_tuning_percentage_callback',
        'chatbot_chatgpt_kn_scheduling',
        'chatbot_chatgpt_kn_scheduling_section'
    );

    // Knowledge Navigator Inclusion/Exclusion Settings - Ver 2.0.0
    register_setting('chatbot_chatgpt_knowledge_navigator', 'chatbot_chatgpt_kn_include_posts');
    register_setting('chatbot_chatgpt_knowledge_navigator', 'chatbot_chatgpt_kn_include_pages');
    register_setting('chatbot_chatgpt_knowledge_navigator', 'chatbot_chatgpt_kn_include_products');
    register_setting('chatbot_chatgpt_knowledge_navigator', 'chatbot_chatgpt_kn_include_comments');

    add_settings_section(
        'chatbot_chatgpt_kn_include_exclude_section',
        'Knowledge Navigator Include/Exclude Settings',
        'chatbot_chatgpt_kn_include_exclude_section_callback',
        'chatbot_chatgpt_kn_include_exclude'
    );

    add_settings_field(
        'chatbot_chatgpt_kn_include_posts',
        'Include Published Posts',
        'chatbot_chatgpt_kn_include_posts_callback',
        'chatbot_chatgpt_kn_include_exclude',
        'chatbot_chatgpt_kn_include_exclude_section'
    );

    add_settings_field(
        'chatbot_chatgpt_kn_include_pages',
        'Include Published Pages',
        'chatbot_chatgpt_kn_include_pages_callback',
        'chatbot_chatgpt_kn_include_exclude',
        'chatbot_chatgpt_kn_include_exclude_section'
    );

    add_settings_field(
        'chatbot_chatgpt_kn_include_products',
        'Include Published Products',
        'chatbot_chatgpt_kn_include_products_callback',
        'chatbot_chatgpt_kn_include_exclude',
        'chatbot_chatgpt_kn_include_exclude_section'
    );

    add_settings_field(
        'chatbot_chatgpt_kn_include_comments',
        'Include Approved Comments',
        'chatbot_chatgpt_kn_include_comments_callback',
        'chatbot_chatgpt_kn_include_exclude',
        'chatbot_chatgpt_kn_include_exclude_section'
    );

    // Knowledge Navigator Enhanced Responses - Ver 2.0.0
    register_setting('chatbot_chatgpt_knowledge_navigator', 'chatbot_chatgpt_suppress_learnings');
    register_setting('chatbot_chatgpt_knowledge_navigator', 'chatbot_chatgpt_custom_learnings_message');
    register_setting('chatbot_chatgpt_knowledge_navigator', 'chatbot_chatgpt_enhanced_response_limit');

    add_settings_section(
        'chatbot_chatgpt_kn_enhanced_response_section',
        'Knowledge Navigator Enhanced Response Settings',
        'chatbot_chatgpt_kn_enhanced_response_section_callback',
        'chatbot_chatgpt_kn_enhanced_response'
    );

    add_settings_field(
        'chatbot_chatgpt_suppress_learnings',
        'Suppress Learnings Messages',
        'chatbot_chatgpt_suppress_learnings_callback',
        'chatbot_chatgpt_kn_enhanced_response',
        'chatbot_chatgpt_kn_enhanced_response_section'
    );

    add_settings_field(
        'chatbot_chatgpt_custom_learnings_message',
        'Custom Learnings Message',
        'chatbot_chatgpt_custom_learnings_message_callback',
        'chatbot_chatgpt_kn_enhanced_response',
        'chatbot_chatgpt_kn_enhanced_response_section'
    );

    add_settings_field(
        'chatbot_chatgpt_enhanced_response_limit',
        'Enhanced Response Limit',
        'chatbot_chatgpt_enhanced_response_limit_callback',
        'chatbot_chatgpt_kn_enhanced_response',
        'chatbot_chatgpt_kn_enhanced_response_section'
    );

    // Analysis Tab

    // Knowledge Navigator Analysis settings tab - Ver 1.6.1
    register_setting('chatbot_chatgpt_kn_analysis', 'chatbot_chatgpt_kn_analysis_output');

    add_settings_section(
        'chatbot_chatgpt_kn_analysis_section',
        'Knowledge Navigator Analysis',
        'chatbot_chatgpt_kn_analysis_section_callback',
        'chatbot_chatgpt_kn_analysis'
    );

    add_settings_field(
        'chatbot_chatgpt_kn_analysis_output',
        'Output Format',
        'chatbot_chatgpt_kn_analysis_output_callback',
        'chatbot_chatgpt_kn_analysis',
        'chatbot_chatgpt_kn_analysis_section'
    );

}
add_action('admin_init', 'chatbot_chatgpt_kn_settings_init');