<?php
/**
 * Kognetiks Chatbot - Knowledge Navigator - Settings
 *
 * This file contains the code for the Chatbot settings page.
 * These are all the options for the Knowledge Navigator.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

global $chatbot_chatgpt_plugin_dir_path;
global $chatbot_chatgpt_plugin_dir_url;

global $topwords, $words, $start_url, $domain, $max_top_words, $chatbot_chatgpt_diagnostics, $plugin_dir_path, $results_dir_path, $chatbot_chatgpt_no_of_items_analyzed;
$start_url = site_url();
$domain = parse_url($start_url, PHP_URL_HOST);
$max_top_words = esc_attr(get_option('chatbot_chatgpt_kn_maximum_top_words', 25)); // Default to 25

// Knowledge Navigator Results
function chatbot_chatgpt_kn_results_callback($run_scanner) {

    // DIAG - Diagnostics - Ver 1.6.3

    // NUCLEAR OPTION - OVERRIDE VALUE TO NO
    // update_option('chatbot_chatgpt_kn_schedule', 'No');
    
    global $topWords;

    // Must be one of: Now, Hourly, Twice Daily, Weekly
    // $run_scanner = esc_attr(get_option('chatbot_chatgpt_kn_schedule', 'No'));

    if (!isset($run_scanner)) {
        $run_scanner = 'No';
    }

    if (in_array($run_scanner, ['Now', 'Hourly', 'Daily', 'Twice Daily', 'Weekly', 'Disable', 'Cancel'])) {

        // DIAG - Diagnostics - Ver 1.6.3

        $chatbot_chatgpt_no_of_items_analyzed = 0;
        update_option('chatbot_chatgpt_no_of_items_analyzed', $chatbot_chatgpt_no_of_items_analyzed);

        // WP Cron Scheduler - VER 1.6.2

        wp_clear_scheduled_hook('knowledge_navigator_scan_hook'); // Clear before rescheduling

        if ($run_scanner === 'Cancel') {
            update_option( 'chatbot_chatgpt_kn_schedule', 'No' );
            update_option( 'chatbot_chatgpt_scan_interval', 'No Schedule' );
            update_option( 'chatbot_chatgpt_kn_action', 'cancel' );
            update_option( 'chatbot_chatgpt_kn_status', 'Cancelled' );
        } elseif ($run_scanner === 'Disable') {
            update_option( 'chatbot_chatgpt_kn_schedule', 'Disable' );
            update_option( 'chatbot_chatgpt_scan_interval', 'No Schedule' );
            update_option( 'chatbot_chatgpt_kn_action', 'disable' );
            update_option( 'chatbot_chatgpt_kn_status', 'Disabled' );
        } else {
            if (!wp_next_scheduled('knowledge_navigator_scan_hook')) {

                // RESET THE NO OF LINKS CRAWLED HERE
                update_option('chatbot_chatgpt_no_of_items_analyzed', 0);
                
                // RESET THE STATUS MESSAGE
                update_option('chatbot_chatgpt_kn_status', 'In Process');

                // Log action to debug.log

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
function chatbot_chatgpt_knowledge_navigator_section_callback($args) {

    // See if the scanner needs to run
    $results = chatbot_chatgpt_kn_results_callback(esc_attr(get_option('chatbot_chatgpt_kn_schedule')));

    // Force run the scanner
    // $results = chatbot_chatgpt_kn_acquire();
    
    ?>
        <div class="wrap">
            <p>Introducing <b>Knowledge Navigator</b> - the smart explorer behind our Kognetiks Chatbot plugin that's designed to delve into the core of your website. Like a digital archaeologist, it embarks on an all-encompassing journey through your site's published pages, posts, products and approved comments, carefully following every internal link to get a holistic view of your content. The exciting part? It sifts through each page, extracting the essence of your content in the form of keywords and phrases, gradually building a meticulous, interactive map of your website's architecture. </p>
            <p>What's the outcome? Detailed "results.csv" and "results.json" files are created, tucking away all this valuable information in a dedicated 'results' directory within the plugin's folder. The prime objective of <b>Knowledge Navigator</b> is to enable the Kognetiks Chatbot plugin to have a crystal clear understanding of your website's context and content. The result? Your chatbot will deliver responses that are not just accurate, but also fittingly contextual, thereby crafting a truly bespoke user experience. This all is powered by the advanced AI technology of OpenAI', NVIDIA's, and other AI platform's Large Language Model (LLM) APIs.</p>
            <p>And how does the <b>Knowledge Navigator</b> do all this? It employs a clever technique known as TF-IDF (Term Frequency-Inverse Document Frequency) to unearth the keywords that really matter. The keywords are ranked by their TF-IDF scores, where the score represents the keyword's relevance to your site. This score is a fine balance between the term's frequency on your site and its inverse document frequency (which is essentially the log of total instances divided by the number of documents containing the term). In simpler words, it's a sophisticated measure of how special a keyword is to your content.</p>
            <p><b><i>Don't forget to click </i><code>Save Settings</code><i> to save any changes your might make.</i></b></p>
            <p style="background-color: #e0f7fa; padding: 10px;"><b>For an explanation on how to use the Knowledge Navigator and additional documentation please click <a href="?page=chatbot-chatgpt&tab=support&dir=knowledge-navigator&file=knowledge-navigator.md">here</a>.</b></p>
        </div>
    <?php
}

// Knowledge Navigator Status - Ver 2.0.0.
function chatbot_chatgpt_kn_status_section_callback($args) {

    // See if the scanner is needs to run
    $results = chatbot_chatgpt_kn_results_callback(esc_attr(get_option('chatbot_chatgpt_kn_schedule')));

    // Force run the scanner
    // $results = chatbot_chatgpt_kn_acquire();
    
    ?>
        <div class="wrap">
            <div style="background-color: white; border: 1px solid #ccc; padding: 10px; margin: 10px; display: inline-block;">

                <p><b>Scheduled to Run: </b><?php echo esc_attr(get_option('chatbot_chatgpt_scan_interval', 'No Schedule')); ?></p>
                <p><b>Status of Last Run: </b><?php echo esc_attr(get_option('chatbot_chatgpt_kn_status', 'Please select a Run Schedule below.')); ?></p>
                <p><b>Content Items Analyzed: </b><?php echo esc_attr(get_option('chatbot_chatgpt_no_of_items_analyzed', 0)); ?></p>
            </div>
            <p>Refresh this page to determine the progress and status of Knowledge Navigation!</p>
        </div>
    <?php
}

function chatbot_chatgpt_kn_settings_section_callback($args) {
    ?>
    <p>When you're ready to scan you website, set the 'Run Schedule' to one of 'Now', 'Hourly', 'Twice Daily', 'Daily', or 'Weekly', then click 'Save Settings'.</p>
    <p>Then select the maximum number of top words to index and choose the Tuning Percentage (the percent of top keywords within a given page, post or product).</p>
    <p>Then click 'Save Settings' at the bottom of the page.</p>
    <?php
}

// Dynamic post type inclusion settings - Ver 2.3.0
function chatbot_chatgpt_kn_get_published_post_types() {

    global $wpdb;
    $published_types = [];
    
    // Get all post types that actually exist in the posts table
    $db_types = $wpdb->get_col("SELECT DISTINCT post_type FROM {$wpdb->posts} WHERE post_type NOT LIKE 'wp_%' AND post_type NOT IN ('attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset')");
    
    // Make sure we always have our core types first
    $core_types = ['post' => 'Post', 'page' => 'Page', 'product' => 'Product'];
    foreach ($core_types as $type => $label) {
        $published_types[$type] = $label;
    }
    
    // Add any additional types from the database
    foreach ($db_types as $type) {
        if (!isset($published_types[$type])) {
            $label = ucfirst(str_replace(['_', '-'], ' ', $type));
            $published_types[$type] = $label;
        }
    }
    
    // DIAG - Diagnostics - Ver 2.2.6
    
    return $published_types;

}

// Register settings on init
add_action('admin_init', 'chatbot_chatgpt_kn_register_settings');

function chatbot_chatgpt_kn_register_settings() {
    // Register the section first
    add_settings_section(
        'chatbot_chatgpt_kn_include_exclude_section',
        'Knowledge Navigator Include/Exclude Settings',
        'chatbot_chatgpt_kn_include_exclude_section_callback',
        'chatbot-chatgpt'
    );

    // Get all post types - do this only once
    $published_types = chatbot_chatgpt_kn_get_published_post_types();
    
    // Register settings and fields for each post type
    foreach ($published_types as $type => $label) {
        // Always use plural form for option names
        $plural_type = $type === 'reference' ? 'references' : $type . 's';
        $option_name = 'chatbot_chatgpt_kn_include_' . $plural_type;
        
        // Register the setting
        register_setting(
            'chatbot_chatgpt_knowledge_navigator',
            $option_name
        );
        
        // Add the settings field
        add_settings_field(
            'chatbot_chatgpt_kn_include_' . $plural_type, // Use plural for field ID to match option_name
            'Include ' . ucfirst($label) . 's',    // Display plural in label
            'chatbot_chatgpt_kn_include_post_type_callback',
            'chatbot-chatgpt',
            'chatbot_chatgpt_kn_include_exclude_section',
            [
                'post_type' => $type,              // Pass singular post_type
                'option_name' => $option_name      // Pass plural option_name
            ]
        );
    }

    // Register comments setting
    register_setting(
        'chatbot_chatgpt_knowledge_navigator',
        'chatbot_chatgpt_kn_include_comments'
    );
    
    add_settings_field(
        'chatbot_chatgpt_kn_include_comments',
        'Include Approved Comments',
        'chatbot_chatgpt_kn_include_comments_callback',
        'chatbot-chatgpt',
        'chatbot_chatgpt_kn_include_exclude_section'
    );
}

function chatbot_chatgpt_kn_include_exclude_section_callback($args) {
    ?>
    <p>Choose the content types you want to include in the Knowledge Navigator's indexing process. Only published content will be indexed.</p>
    <?php
}

function chatbot_chatgpt_kn_include_post_type_callback($args) {
    if (empty($args['option_name'])) {
        return;
    }

    $option_name = $args['option_name'];
    $value = get_option($option_name, 'No');

    ?>
    <select id="<?php echo esc_attr($option_name); ?>" name="<?php echo esc_attr($option_name); ?>">
        <option value="No" <?php selected($value, 'No'); ?>>No</option>
        <option value="Yes" <?php selected($value, 'Yes'); ?>>Yes</option>
    </select>
    <?php
}

function chatbot_chatgpt_kn_enhanced_response_section_callback($args) {
    ?>
    <p>Choose the number of enhanced responses you want to display in the chatbot's response. Enhanced responses are links to published/approved content on you site and are displayed along with the titles of the page, post, product and/or comment.</p>
    <p>Then click 'Save Settings' at the bottom of the page.</p>
    <?php
}

// Select Frequency of Scan - Ver 1.6.2
function chatbot_chatgpt_kn_schedule_callback($args) {
    $chatbot_chatgpt_kn_schedule = esc_attr(get_option('chatbot_chatgpt_kn_schedule', 'No'));
    ?>
    <select id="chatbot_chatgpt_kn_schedule" name="chatbot_chatgpt_kn_schedule">
        <option value="No" <?php selected($chatbot_chatgpt_kn_schedule, 'No'); ?>><?php echo esc_html('No'); ?></option>
        <option value="Now" <?php selected($chatbot_chatgpt_kn_schedule, 'Now'); ?>><?php echo esc_html('Now'); ?></option>
        <option value="Hourly" <?php selected($chatbot_chatgpt_kn_schedule, 'Hourly'); ?>><?php echo esc_html('Hourly'); ?></option>
        <option value="Twice Daily" <?php selected($chatbot_chatgpt_kn_schedule, 'Twice Daily'); ?>><?php echo esc_html('Twice Daily'); ?></option>
        <option value="Daily" <?php selected($chatbot_chatgpt_kn_schedule, 'Daily'); ?>><?php echo esc_html('Daily'); ?></option>
        <option value="Weekly" <?php selected($chatbot_chatgpt_kn_schedule, 'Weekly'); ?>><?php echo esc_html('Weekly'); ?></option>
        <option value="Disable" <?php selected($chatbot_chatgpt_kn_schedule, 'Disable'); ?>><?php echo esc_html('Disable'); ?></option>
        <option value="Cancel" <?php selected($chatbot_chatgpt_kn_schedule, 'Cancel'); ?>><?php echo esc_html('Cancel'); ?></option>
    </select>
    <?php
}

function chatbot_chatgpt_kn_maximum_top_words_callback($args) {
    $GLOBALS['max_top_words'] = intval(esc_attr(get_option('chatbot_chatgpt_kn_maximum_top_words', 250)));
    ?>
    <select id="chatbot_chatgpt_kn_maximum_top_words" name="chatbot_chatgpt_kn_maximum_top_words">
        <?php
        for ($i = 500; $i <= 10000; $i += 500) {
            echo '<option value="' . esc_attr( $i ) . '"' . selected($GLOBALS['max_top_words'], $i, false) . '>' . esc_html( $i ) . '</option>';
        }
        ?>
    </select>
    <?php
}

function chatbot_chatgpt_kn_include_comments_callback($args) {
    $chatbot_chatgpt_kn_include_comments = esc_attr(get_option('chatbot_chatgpt_kn_include_comments', 'No'));
    ?>
    <select id="chatbot_chatgpt_kn_include_comments" name="chatbot_chatgpt_kn_include_comments">
        <option value="No" <?php selected($chatbot_chatgpt_kn_include_comments, 'No'); ?>><?php echo esc_html('No'); ?></option>
        <option value="Yes" <?php selected($chatbot_chatgpt_kn_include_comments, 'Yes'); ?>><?php echo esc_html('Yes'); ?></option>
    </select>
    <?php
}

function chatbot_chatgpt_enhanced_response_limit_callback($args) {
    $chatbot_chatgpt_enhanced_response_limit = intval(esc_attr(get_option('chatbot_chatgpt_enhanced_response_limit', 3)));
    ?>
    <select id="chatbot_chatgpt_enhanced_response_limit" name="chatbot_chatgpt_enhanced_response_limit">
        <?php
        for ($i = 1; $i <= 10; $i++) {
            echo '<option value="' . esc_attr( $i ) . '"' . selected($chatbot_chatgpt_enhanced_response_limit, $i, false) . '>' . esc_html( $i ) . '</option>';
        }
        ?>
    </select>
    <?php
}

function chatbot_chatgpt_kn_tuning_percentage_callback($args) {
    $chatbot_chatgpt_kn_tuning_percentage = intval(esc_attr(get_option('chatbot_chatgpt_kn_tuning_percentage', 25)));
    ?>
    <select id="chatbot_chatgpt_kn_tuning_percentage" name="chatbot_chatgpt_kn_tuning_percentage">
        <?php
        for ($i = 10; $i <= 100; $i += 5) {
            echo '<option value="' . esc_attr( $i ) . '"' . selected($chatbot_chatgpt_kn_tuning_percentage, $i, false) . '>' . esc_html( $i ) . '</option>';
        }
        ?>
    </select>
    <?php
}

// Suppress Learnings Message - Ver 1.7.1
function chatbot_chatgpt_suppress_learnings_callback($args) {
    global $chatbot_chatgpt_suppress_learnings;
    $chatbot_chatgpt_suppress_learnings = esc_attr(get_option('chatbot_chatgpt_suppress_learnings', 'Random'));
    ?>
    <select id="chatgpt_suppress_learnings_setting" name = "chatbot_chatgpt_suppress_learnings">
        <option value="None" <?php selected( $chatbot_chatgpt_suppress_learnings, 'None' ); ?>><?php echo esc_html( 'None' ); ?></option>
        <option value="Random" <?php selected( $chatbot_chatgpt_suppress_learnings, 'Random' ); ?>><?php echo esc_html( 'Random' ); ?></option>
        <option value="Custom" <?php selected( $chatbot_chatgpt_suppress_learnings, 'Custom' ); ?>><?php echo esc_html( 'Custom' ); ?></option>
    </select>
    <?php
}

// Suppress Learnings Message - Ver 1.7.1
function chatbot_chatgpt_custom_learnings_message_callback($args) {
    global $chatbot_chatgpt_custom_learnings_message;
    $chatbot_chatgpt_custom_learnings_message = esc_attr(get_option('chatbot_chatgpt_custom_learnings_message', 'More information may be found here ...'));
    ?>
    <input type="text" style="width: 50%;" id="chatbot_chatgpt_custom_learnings_message" name = "chatbot_chatgpt_custom_learnings_message" value="<?php echo esc_attr( $chatbot_chatgpt_custom_learnings_message ); ?>">
    <?php
}

// Optionally include post or page excerpts with Knowledge Navigator responses - Ver 2.2.1
function chatbot_chatgpt_enhanced_response_include_excerpts_callback() {
    $value = esc_attr(get_option('chatbot_chatgpt_enhanced_response_include_excerpts', 'No'));
    ?>
    <select id="chatbot_chatgpt_enhanced_response_include_excerpts" name="chatbot_chatgpt_enhanced_response_include_excerpts">
        <option value="No" <?php selected( $value, 'No' ); ?>><?php echo esc_html( 'No' ); ?></option>
        <option value="Yes" <?php selected( $value, 'Yes' ); ?>><?php echo esc_html( 'Yes' ); ?></option>
    </select>
    <?php
}
