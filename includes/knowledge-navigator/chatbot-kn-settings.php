<?php
/**
 * Kognetiks Chatbot for WordPress - Knowledge Navigator - Settings
 *
 * This file contains the code for the Chatbot settings page.
 * These are all the options for the Knowledge Navigator.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

global $topwords, $words, $start_url, $domain, $max_top_words, $chatbot_chatgpt_diagnostics, $plugin_dir_path, $results_dir_path, $no_of_items_analyzed;
$start_url = site_url();
$domain = parse_url($start_url, PHP_URL_HOST);
$max_top_words = esc_attr(get_option('chatbot_chatgpt_kn_maximum_top_words', 25)); // Default to 25

// Knowledge Navigator Results
function chatbot_chatgpt_kn_results_callback($run_scanner): void {

    // DIAG - Diagnostic - Ver 1.6.3
    // back_trace( 'NOTICE', 'chatbot_chatgpt_kn_results_callback');
    // back_trace( 'NOTICE', '$run_scanner: ' . $run_scanner);
    // back_trace( 'NOTICE', 'chatbot_chatgpt_kn_schedule: ' . get_option('chatbot_chatgpt_kn_schedule'));

    // NUCLEAR OPTION - OVERRIDE VALUE TO NO
    // update_option('chatbot_chatgpt_kn_schedule', 'No');

    global $topWords;

    // Must be one of: Now, Hourly, Twice Daily, Weekly
    // $run_scanner = get_option('chatbot_chatgpt_kn_schedule', 'No');

    if (!isset($run_scanner)) {
        $run_scanner = 'No';
    }

    if (in_array($run_scanner, ['Now', 'Hourly', 'Daily', 'Twice Daily', 'Weekly', 'Cancel'])) {

        // DIAG - Diagnostic - Ver 1.6.3
        // back_trace( 'NOTICE', "$run_scanner: " . $run_scanner);
        // back_trace( 'NOTICE', "max_top_words: " . serialize($GLOBALS['max_top_words']));
        // back_trace( 'NOTICE', "domain: " . serialize($GLOBALS['domain']));
        // back_trace( 'NOTICE', "start_url: " . serialize($GLOBALS['start_url']));

        $no_of_items_analyzed = 0;
        update_option('no_of_items_analyzed', $no_of_items_analyzed);

        // WP Cron Scheduler - VER 1.6.2
        // back_trace( 'NOTICE', 'BEFORE wp_clear_scheduled_hook');
        wp_clear_scheduled_hook('knowledge_navigator_scan_hook'); // Clear before rescheduling
        // back_trace( 'NOTICE', 'AFTER wp_clear_scheduled_hook');

        if ($run_scanner === 'Cancel') {
            update_option('chatbot_chatgpt_kn_schedule', 'No');
            update_option('chatbot_chatgpt_scan_interval', 'No Schedule');
        } else {
            if (!wp_next_scheduled('knowledge_navigator_scan_hook')) {

                // RESET THE NO OF LINKS CRAWLED HERE
                update_option('no_of_items_analyzed', 0);
                
                // RESET THE STATUS MESSAGE
                update_option('chatbot_chatgpt_kn_status', 'In Process');

                // Log action to debug.log
                // back_trace( 'NOTICE', 'BEFORE crawl_schedule_event_hook');

                // IDEA WP Cron Scheduler - VER 1.6.2
                // https://chat.openai.com/share/b1de5d84-966c-4f0f-b24d-329af3e55616
                // A standard system cron job runs at specified intervals regardless of the 
                // website's activity or traffic, but WordPress cron jobs are triggered by visits
                // to your site.
                // https://wpshout.com/wp_schedule_event-examples/
                // wp_schedule_single_event(time(), 'knowledge_navigator_scan_hook');

                $interval_mapping = [
                    'Now' => 10, // For immediate execution, just delay by 10 seconds
                    'Hourly' => 'hourly',
                    'Twice Daily' => 'twicedaily',
                    'Daily' => 'daily',
                    'Weekly' => 'weekly' // assuming you've defined a custom 'weekly' schedule
                ];

                if (in_array($run_scanner, array_keys($interval_mapping))) {
                    $timestamp = time() + 10; // Always run 10 seconds from now
                    $interval = $interval_mapping[$run_scanner];
                    $hook = 'knowledge_navigator_scan_hook';
                    if ($run_scanner === 'Now') {
                        wp_schedule_single_event($timestamp, $hook); // Schedule a one-time event if 'Now' is selected
                    } else {
                        wp_schedule_event($timestamp, $interval, $hook); // Schedule a recurring event for other intervals
                    }
                }
                
                // DIAG - Log action to debug.log
                // back_trace( 'NOTICE', 'AFTER crawl_schedule_event_hook');

                // Log scan interval - Ver 1.6.3
                if ($interval === 'Now') {
                    update_option('chatbot_chatgpt_scan_interval', 'No Schedule');
                } else {
                    update_option('chatbot_chatgpt_scan_interval', $run_scanner);
                }

                // Reset before reloading the page
                $run_scanner = 'No';
                update_option('chatbot_chatgpt_kn_schedule', 'No');
            }
        }
    }
}

// Knowledge Navigator Introduction
function chatbot_chatgpt_knowledge_navigator_section_callback($args): void {

    // See if the scanner is needs to run
    $results = chatbot_chatgpt_kn_results_callback(esc_attr(get_option('chatbot_chatgpt_kn_schedule')));

    // Force run the scanner
    // $results = chatbot_chatgpt_kn_acquire();
    
    ?>
        <div class="wrap">
            <p>Introducing <b>Knowledge Navigator</b> - the smart explorer behind our Kognetiks Chatbot plugin that's designed to delve into the core of your website. Like a digital archaeologist, it embarks on an all-encompassing journey through your site's pages, carefully following every internal link to get a holistic view of your content. The exciting part? It sifts through each page, extracting the essence of your content in the form of keywords and phrases, gradually building a meticulous, interactive map of your website's architecture. </p>
            <p>What's the outcome? Detailed "results.csv" and "results.json" files are created, tucking away all this valuable information in a dedicated 'results' directory within the plugin's folder. The prime objective of <b>Knowledge Navigator</b> is to enable the Kognetiks Chatbot plugin to have a crystal clear understanding of your website's context and content. The result? Your chatbot will deliver responses that are not just accurate, but also fittingly contextual, thereby crafting a truly bespoke user experience. This all is powered by the advanced AI technology of OpenAI's Large Language Model (LLM) API.</p>
            <p>And how does the <b>Knowledge Navigator</b> do all this? It employs a clever technique known as TF-IDF (Term Frequency-Inverse Document Frequency) to unearth the keywords that really matter. The keywords are ranked by their TF-IDF scores, where the score represents the keyword's relevance to your site. This score is a fine balance between the term's frequency on your site and its inverse document frequency (which is essentially the log of total instances divided by the number of documents containing the term). In simpler words, it's a sophisticated measure of how special a keyword is to your content.</p>
            <hr style="border-top: 2px solid;">
            <h2>Knowledge Navigator Status</h2>
            <p><b><i>When you're ready to scan you website, set the 'Run Schedule' to 'Yes', then click 'Save Settings'.</i></b></p>
            <div style="background-color: white; border: 1px solid #ccc; padding: 10px; margin: 10px; display: inline-block;">
                <p><b>Knowledge Navigator Status</b></p>
                <p><b>Scheduled to Run: </b><?php echo esc_attr(get_option('chatbot_chatgpt_scan_interval', 'No Schedule')); ?></p>
                <p><b>Status of Last Run: </b><?php echo esc_attr(get_option('chatbot_chatgpt_kn_status', 'In Process')); ?></p>
                <p><b>Content Items Analyzed: </b><?php echo esc_attr(get_option('no_of_items_analyzed', 0)); ?></p>
            </div>
            <p>Refresh this page to determine the progress and status of Knowledge Navigation!</p>
            <p>To choose a new Run Schedule, select from one of the options below (Now, Hourly, ...)  then click 'Save Settings'.</p>
        </div>
    <?php
}

function chatbot_chatgpt_kn_settings_section_callback($args) {
    ?>
    <p><i>Select the Run schedule (frequency, i.e., hourly, daily, weekly, etc.) and maximum number of top words to index.</i></p>
    <p><i>Choose the content types you want to include in the Knowledge Navigator's indexing process. This will help the Kognetiks Chatbot plugin to understand your website's content better and provide more accurate responses.</i></p>
    <?php

}

// Select Frequency of Scan - Ver 1.6.2
function chatbot_chatgpt_kn_schedule_callback($args): void {
    $chatbot_chatgpt_kn_schedule = esc_attr(get_option('chatbot_chatgpt_kn_schedule', 'No'));
    ?>
    <select id="chatbot_chatgpt_kn_schedule" name="chatbot_chatgpt_kn_schedule">
        <option value="No" <?php selected($chatbot_chatgpt_kn_schedule, 'No'); ?>><?php echo esc_html('No'); ?></option>
        <option value="Now" <?php selected($chatbot_chatgpt_kn_schedule, 'Now'); ?>><?php echo esc_html('Now'); ?></option>
        <option value="Hourly" <?php selected($chatbot_chatgpt_kn_schedule, 'Hourly'); ?>><?php echo esc_html('Hourly'); ?></option>
        <option value="Twice Daily" <?php selected($chatbot_chatgpt_kn_schedule, 'Twice Daily'); ?>><?php echo esc_html('Twice Daily'); ?></option>
        <option value="Daily" <?php selected($chatbot_chatgpt_kn_schedule, 'Daily'); ?>><?php echo esc_html('Daily'); ?></option>
        <option value="Weekly" <?php selected($chatbot_chatgpt_kn_schedule, 'Weekly'); ?>><?php echo esc_html('Weekly'); ?></option>
        <option value="Cancel" <?php selected($chatbot_chatgpt_kn_schedule, 'Cancel'); ?>><?php echo esc_html('Cancel'); ?></option>
    </select>
    <?php
}

function chatbot_chatgpt_kn_maximum_top_words_callback($args): void {
    $GLOBALS['max_top_words'] = intval(get_option('chatbot_chatgpt_kn_maximum_top_words', 25));
    ?>
    <select id="chatbot_chatgpt_kn_maximum_top_words" name="chatbot_chatgpt_kn_maximum_top_words">
        <?php
        for ($i = 100; $i <= 2000; $i += 100) {
            echo '<option value="' . $i . '"' . selected($GLOBALS['max_top_words'], $i, false) . '>' . $i . '</option>';
        }
        ?>
    </select>
    <?php
}

function chatbot_chatgpt_kn_include_posts_callback($args): void {
    $chatbot_chatgpt_kn_include_posts = esc_attr(get_option('chatbot_chatgpt_kn_include_posts', 'Yes'));
    ?>
    <select id="chatbot_chatgpt_kn_include_posts" name="chatbot_chatgpt_kn_include_posts">
        <option value="No" <?php selected($chatbot_chatgpt_kn_include_posts, 'No'); ?>><?php echo esc_html('No'); ?></option>
        <option value="Yes" <?php selected($chatbot_chatgpt_kn_include_posts, 'Yes'); ?>><?php echo esc_html('Yes'); ?></option>
    </select>
    <?php
}

function chatbot_chatgpt_kn_include_pages_callback($args): void {
    $chatbot_chatgpt_kn_include_pages = esc_attr(get_option('chatbot_chatgpt_kn_include_pages', 'Yes'));
    ?>
    <select id="chatbot_chatgpt_kn_include_pages" name="chatbot_chatgpt_kn_include_pages">
        <option value="No" <?php selected($chatbot_chatgpt_kn_include_pages, 'No'); ?>><?php echo esc_html('No'); ?></option>
        <option value="Yes" <?php selected($chatbot_chatgpt_kn_include_pages, 'Yes'); ?>><?php echo esc_html('Yes'); ?></option>
    </select>
    <?php
}

function chatbot_chatgpt_kn_include_products_callback($args): void {
    $chatbot_chatgpt_kn_include_products = esc_attr(get_option('chatbot_chatgpt_kn_include_products', 'Yes'));
    ?>
    <select id="chatbot_chatgpt_kn_include_products" name="chatbot_chatgpt_kn_include_products">
        <option value="No" <?php selected($chatbot_chatgpt_kn_include_products, 'No'); ?>><?php echo esc_html('No'); ?></option>
        <option value="Yes" <?php selected($chatbot_chatgpt_kn_include_products, 'Yes'); ?>><?php echo esc_html('Yes'); ?></option>
    </select>
    <?php
}

function chatbot_chatgpt_kn_include_comments_callback($args): void {
    $chatbot_chatgpt_kn_include_comments = esc_attr(get_option('chatbot_chatgpt_kn_include_comments', 'Yes'));
    ?>
    <select id="chatbot_chatgpt_kn_include_comments" name="chatbot_chatgpt_kn_include_comments">
        <option value="No" <?php selected($chatbot_chatgpt_kn_include_comments, 'No'); ?>><?php echo esc_html('No'); ?></option>
        <option value="Yes" <?php selected($chatbot_chatgpt_kn_include_comments, 'Yes'); ?>><?php echo esc_html('Yes'); ?></option>
    </select>
    <?php
}
