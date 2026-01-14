<?php
/**
 * Kognetiks Insights - Insights Settings - Ver 1.0.0
 *
 * This file contains the code for the Kognetiks Insights settings page.
 * 
 * 
 * 
 * @package kognetiks-insights
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Settings Page
function kognetiks_insights_settings_page() {
    
    // DIAG - Diagnostics

    // Determine active tab
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'insights';

    // Tab navigation UI and page wrapper
    echo '<div class="wrap" id="kognetiks-insights">';
    echo '<h1><span class="dashicons dashicons-chart-bar" style="font-size: 25px;"></span> Kognetiks Insights</h1>';
    echo '<p style="background-color: #e0f7fa; padding: 10px;"><b>For an explanation on how to use Insights and additional documentation please click <a href="?page=chatbot-chatgpt&file=insights-package.md&tab=support&dir=insights-package">here</a>.</b></p>';
    
    // Determine if the last scoring date/time is earlier than the current date/time, if so, then start the scoring process, else exit
    $last_scoring_date = get_option('kognetiks_insights_last_scoring_date');
    $last_scoring_timestamp = strtotime($last_scoring_date);
    $current_timestamp = time();

    if ($last_scoring_timestamp !== false && ($current_timestamp - $last_scoring_timestamp) <= 3600) {
        // Last scoring was within the last hour, skip warning
    } else {
        // Last scoring was more than an hour ago (or never)
        ?>
        <div class="notice notice-warning" style="padding: 10px; margin: 8px 0;">
            <h2 style="margin: 0;">⚠️ Please start the scoring process to update the sentiment scores before proceeding.</h2>
        </div>
        <?php
    }

    // Tab content
    if ($active_tab === 'insights') {
        // Handle scoring control actions
        if (isset($_POST['kap_scoring_action'])) {
            $action = $_POST['kap_scoring_action'];
            if ($action === 'start' && isset($_POST['kognetiks_insights_scoring_start_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['kognetiks_insights_scoring_start_nonce'])), 'kognetiks_insights_scoring_start')) {
                kognetiks_insights_start_scoring();
                kognetiks_insights_score_conversations_without_sentiment_score();
            } elseif ($action === 'stop' && isset($_POST['kognetiks_insights_scoring_stop_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['kognetiks_insights_scoring_stop_nonce'])), 'kognetiks_insights_scoring_stop')) {
                kognetiks_insights_stop_scoring();
            } elseif ($action === 'restart' && isset($_POST['kognetiks_insights_scoring_restart_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['kognetiks_insights_scoring_restart_nonce'])), 'kognetiks_insights_scoring_restart')) {
                kognetiks_insights_restart_scoring();
                kognetiks_insights_score_conversations_without_sentiment_score();
            } elseif ($action === 'reset' && isset($_POST['kognetiks_insights_scoring_reset_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['kognetiks_insights_scoring_reset_nonce'])), 'kognetiks_insights_scoring_reset')) {
                kognetiks_insights_reset_scoring();
            }
            echo '<script>location.reload();</script>';
            exit;
        }

        // Check if conversation logging is enabled and table exists
        global $wpdb;
        $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';
        $logging_enabled = get_option('chatbot_chatgpt_enable_conversation_logging', 'Off');
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

        if (!$table_exists || $logging_enabled !== 'On') {
            ?>
            <div class="notice notice-warning" style="padding: 20px; margin: 20px 0;">
                <h2 style="margin-top: 0;">⚠️ Conversation Logging Required</h2>
                <p>To use Kognetiks Insights, you need to enable conversation logging in the Kognetiks Chatbot settings.</p>
                <p>Please follow these steps:</p>
                <ol>
                    <li>Go to <a href="<?php echo esc_url(admin_url('admin.php?page=chatbot-chatgpt&tab=reporting')); ?>">Kognetiks Chatbot Settings</a></li>
                    <li>Navigate to the "Reporting" tab and scroll down to the "Reporting Settings" section</li>
                    <li>Set the "Enable Conversation Logging" option to "On"</li>
                    <li>Choose the "Conversation Log Days to Keep" option to the number of days you want to keep the conversation logs (default is 30 days)</li>
                    <li>Save your changes by scrolling to the bottom of the page and clicking the "Save Changes" button</li>
                </ol>
                <p>Once conversation logging is enabled, you'll be able to view insights data here.</p>
            </div>
            <?php
            return;
        }

        // Ensure sentiment_score column exists for existing installations
        if ($table_exists && function_exists('chatbot_chatgpt_add_sentiment_score_column')) {
            chatbot_chatgpt_add_sentiment_score_column();
        }

        // Verify nonce for period filter form submission
        if (
            isset($_POST['chatbot_chatgpt_insights_period_filter_nonce']) &&
            wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['chatbot_chatgpt_insights_period_filter_nonce'])),
                'chatbot_chatgpt_insights_period_filter_action'
            )
        ) {
            $selected_period = isset($_POST['chatbot_chatgpt_insights_period_filter'])
                ? sanitize_text_field(wp_unslash($_POST['chatbot_chatgpt_insights_period_filter']))
                : 'Today';
        } else {
            $selected_period = get_transient('chatbot_chatgpt_selected_period');
            if (!$selected_period) {
                $selected_period = 'Today';
            }
        }

        // Verify nonce for user type filter form submission
        if (isset($_POST['kognetiks_insights_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['kognetiks_insights_nonce'])), 'kognetiks_insights_user_type_filter')) {
            // Get the selected user type from the form submission
            $selected_user_type = isset($_POST['kognetiks_insights_user_type_filter']) ? sanitize_text_field(wp_unslash($_POST['kognetiks_insights_user_type_filter'])) : 'All';
        } else {
            $selected_user_type = 'All';
        }

        // Verify nonce for scoring control form submission
        if (isset($_POST['kognetiks_insights_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['kognetiks_insights_nonce'])), 'kognetiks_insights_scoring_control')) {
            // Get the selected scoring control from the form submission
            $selected_scoring_control = isset($_POST['kognetiks_insights_scoring_control']) ? sanitize_text_field(wp_unslash($_POST['kognetiks_insights_scoring_control'])) : 'Manual';
            // Update the option in the database
            kognetiks_insights_set_scoring_control_mode($selected_scoring_control);
            // DIAG - Diagnostics
        } else {
            // Get the current scoring control mode from the database
            $selected_scoring_control = kognetiks_insights_get_scoring_control_mode();
        }

        // Get all statistics
        $time_based_counts = kognetiks_insights_get_time_based_conversation_counts($selected_period, $selected_user_type);
        $message_stats = kognetiks_insights_get_message_statistics($selected_period, $selected_user_type);
        $visitor_stats = kognetiks_insights_get_visitor_statistics($selected_period, $selected_user_type);
        $session_stats = kognetiks_insights_get_session_statistics($selected_period, $selected_user_type);
        $token_stats = kognetiks_insights_get_token_statistics($selected_period, $selected_user_type);
        $engagement_stats = kognetiks_insights_get_engagement_statistics($selected_period, $selected_user_type);
        $sentiment_stats = kognetiks_insights_get_sentiment_statistics($selected_period, $selected_user_type);

        ?>
        <div class="insights-container">
            <!-- Period Filter and Scoring Controls -->
            <div class="insights-header-grid" style="display: grid; grid-template-columns: 220px 140px 140px 320px; gap: 16px; align-items: end; margin-bottom: 2px;">
                <span style="font-weight: bold; color: #1d2327;">Period</span>
                <span style="font-weight: bold; color: #1d2327;">Type</span>
                <span style="font-weight: bold; color: #1d2327;">Scoring</span>
                <span style="font-weight: bold; color: #1d2327;">Manual Controls</span>
            </div>
            <div class="insights-controls-grid" style="display: grid; grid-template-columns: 220px 140px 140px 320px; gap: 16px; align-items: center; height: 50px; margin-bottom: 16px;">
                <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=chatbot-chatgpt')); ?>" class="period-filter-form" style="margin-bottom: 0; min-width: 220px;">
                    <?php wp_nonce_field('chatbot_chatgpt_insights_period_filter_action', 'chatbot_chatgpt_insights_period_filter_nonce'); ?>
                    <input type="hidden" name="chatbot_chatgpt_insights_action" value="period_filter" />
                    <select name="chatbot_chatgpt_insights_period_filter" id="chatbot_chatgpt_insights_period_filter" onchange="this.form.submit()" style="width: 100%;">
                        <option value="Today" <?php selected($selected_period, 'Today'); ?>>Today vs Yesterday</option>
                        <option value="Week" <?php selected($selected_period, 'Week'); ?>>This Week vs Last Week</option>
                        <option value="Month" <?php selected($selected_period, 'Month'); ?>>This Month vs Last Month</option>
                        <option value="Quarter" <?php selected($selected_period, 'Quarter'); ?>>This Quarter vs Last Quarter</option>
                        <option value="Year" <?php selected($selected_period, 'Year'); ?>>This Year vs Last Year</option>
                    </select>
                </form>
                <form method="post" action="" class="user-type-filter-form" style="margin-bottom: 20; min-width: 120px;">
                    <?php wp_nonce_field('kognetiks_insights_user_type_filter', 'kognetiks_insights_nonce'); ?>
                    <select name="kognetiks_insights_user_type_filter" id="kognetiks_insights_user_type_filter" onchange="this.form.submit()" style="width: 100%;">
                        <option value="All" <?php selected($selected_user_type, 'All'); ?>>All</option>
                        <option value="Visitor" <?php selected($selected_user_type, 'Visitor'); ?>>Visitor</option>
                        <option value="Chatbot" <?php selected($selected_user_type, 'Chatbot'); ?>>Chatbot</option>
                    </select>
                </form>
                <form method="post" action="" class="scoring-control-form" style="margin-bottom: 20; min-width: 120px;">
                    <?php wp_nonce_field('kognetiks_insights_scoring_control', 'kognetiks_insights_nonce'); ?>
                    <select name="kognetiks_insights_scoring_control" id="kognetiks_insights_scoring_control" onchange="this.form.submit()" style="width: 100%;">
                        <option value="Manual" <?php selected($selected_scoring_control, 'Manual'); ?>>Manual</option>
                        <option value="Automated" <?php selected($selected_scoring_control, 'Automated'); ?>>Automated</option>
                    </select>
                </form>
                <div style="display: flex; gap: 8px; min-width: 320px;">
                    <form method="post" style="display:inline; margin-bottom: 0;">
                        <?php wp_nonce_field('kognetiks_insights_scoring_start', 'kognetiks_insights_scoring_start_nonce'); ?>
                        <button type="submit" name="kap_scoring_action" value="start" class="button button-primary">Start</button>
                    </form>
                    <form method="post" style="display:inline; margin-bottom: 0;">
                        <?php wp_nonce_field('kognetiks_insights_scoring_stop', 'kognetiks_insights_scoring_stop_nonce'); ?>
                        <button type="submit" name="kap_scoring_action" value="stop" class="button">Stop</button>
                    </form>
                    <form method="post" style="display:inline; margin-bottom: 0;">
                        <?php wp_nonce_field('kognetiks_insights_scoring_restart', 'kognetiks_insights_scoring_restart_nonce'); ?>
                        <button type="submit" name="kap_scoring_action" value="restart" class="button">Restart</button>
                    </form>
                    <form method="post" style="display:inline; margin-bottom: 0;">
                        <?php wp_nonce_field('kognetiks_insights_scoring_reset', 'kognetiks_insights_scoring_reset_nonce'); ?>
                        <button type="submit" name="kap_scoring_action" value="reset" class="button">Reset</button>
                    </form>
                </div>
            </div>

            <div class="insights-container">
                <!-- Section Header -->
                <div class="section-header">
                    <h2>📊 Conversation Statistics</h2>
                    <p class="section-description">Key metrics about your chatbot's conversations and user interactions.</p>
                </div>

                <!-- Conversation Statistics -->
                <div class="insights-section">
                    <h3>Overview</h3>
                    <div class="stats-grid">
                        <div class="stat-box">
                            <h3>Total Conversations</h3>
                            <div class="comparison-row">
                                <div class="current-period">
                                    <span class="period-label"><?php echo esc_html($time_based_counts['current_period_label']); ?></span>
                                    <p class="stat-value"><?php echo number_format($time_based_counts['current']['total'] ?? 0); ?></p>
                                </div>
                                <div class="trend-indicator">
                                    <?php 
                                    $current = $time_based_counts['current']['total'] ?? 0;
                                    $previous = $time_based_counts['previous']['total'] ?? 0;
                                    if ($current > $previous) {
                                        $percent_change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
                                        echo '<span class="trend-up">⬆</span><span class="percent-change">+' . number_format($percent_change, 1) . '%</span>';
                                    } elseif ($current < $previous) {
                                        $percent_change = $previous > 0 ? (($previous - $current) / $previous) * 100 : 0;
                                        echo '<span class="trend-down">⬇</span><span class="percent-change">-' . number_format($percent_change, 1) . '%</span>';
                                    }
                                    ?>
                                </div>
                                <div class="previous-period">
                                    <span class="period-label"><?php echo esc_html($time_based_counts['previous_period_label']); ?></span>
                                    <p class="stat-value"><?php echo number_format($time_based_counts['previous']['total'] ?? 0); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="stat-box">
                            <h3>Unique Visitors</h3>
                            <div class="comparison-row">
                                <div class="current-period">
                                    <span class="period-label"><?php echo esc_html($time_based_counts['current_period_label']); ?></span>
                                    <p class="stat-value"><?php echo number_format($time_based_counts['current']['unique_visitors'] ?? 0); ?></p>
                                </div>
                                <div class="trend-indicator">
                                    <?php 
                                    $current = $time_based_counts['current']['unique_visitors'] ?? 0;
                                    $previous = $time_based_counts['previous']['unique_visitors'] ?? 0;
                                    if ($current > $previous) {
                                        $percent_change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
                                        echo '<span class="trend-up">⬆</span><span class="percent-change">+' . number_format($percent_change, 1) . '%</span>';
                                    } elseif ($current < $previous) {
                                        $percent_change = $previous > 0 ? (($previous - $current) / $previous) * 100 : 0;
                                        echo '<span class="trend-down">⬇</span><span class="percent-change">-' . number_format($percent_change, 1) . '%</span>';
                                    }
                                    ?>
                                </div>
                                <div class="previous-period">
                                    <span class="period-label"><?php echo esc_html($time_based_counts['previous_period_label']); ?></span>
                                    <p class="stat-value"><?php echo number_format($time_based_counts['previous']['unique_visitors'] ?? 0); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Conversation Quality Insights -->
                <div class="section-header">
                    <h2>🧠 Conversation Quality Insights</h2>
                    <p class="section-description">Insights into the effectiveness and sentiment of chatbot interactions.</p>
                </div>

                <div class="insights-section">
                    <h3>Sentiment Insights</h3>
                    <div class="stats-grid">
                        <div class="stat-box">
                            <h3>Average Sentiment Score</h3>
                            <div class="comparison-row">
                                <div class="current-period">
                                    <span class="period-label"><?php echo esc_html($sentiment_stats['current_period_label'] ?? 'Current Period'); ?></span>
                                    <p class="stat-value"><?php echo number_format($sentiment_stats['current']['avg_score'] ?? 0, 2); ?></p>
                                </div>
                                <div class="trend-indicator">
                                    <?php 
                                    $current = $sentiment_stats['current']['avg_score'] ?? 0;
                                    $previous = $sentiment_stats['previous']['avg_score'] ?? 0;
                                    if ($current > $previous) {
                                        $percent_change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
                                        echo '<span class="trend-up">⬆</span><span class="percent-change">+' . number_format($percent_change, 1) . '%</span>';
                                    } elseif ($current < $previous) {
                                        $percent_change = $previous > 0 ? (($previous - $current) / $previous) * 100 : 0;
                                        echo '<span class="trend-down">⬇</span><span class="percent-change">-' . number_format($percent_change, 1) . '%</span>';
                                    }
                                    ?>
                                </div>
                                <div class="previous-period">
                                    <span class="period-label"><?php echo esc_html($sentiment_stats['previous_period_label'] ?? 'Previous Period'); ?></span>
                                    <p class="stat-value"><?php echo number_format($sentiment_stats['previous']['avg_score'] ?? 0, 2); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="stat-box">
                            <h3>Positive Conversations</h3>
                            <div class="comparison-row">
                                <div class="current-period">
                                    <span class="period-label"><?php echo esc_html($sentiment_stats['current_period_label'] ?? 'Current Period'); ?></span>
                                    <p class="stat-value"><?php echo number_format($sentiment_stats['current']['positive_percent'] ?? 0, 1); ?>%</p>
                                </div>
                                <div class="trend-indicator">
                                    <?php 
                                    $current = $sentiment_stats['current']['positive_percent'] ?? 0;
                                    $previous = $sentiment_stats['previous']['positive_percent'] ?? 0;
                                    if ($current > $previous) {
                                        $percent_change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
                                        echo '<span class="trend-up">⬆</span><span class="percent-change">+' . number_format($percent_change, 1) . '%</span>';
                                    } elseif ($current < $previous) {
                                        $percent_change = $previous > 0 ? (($previous - $current) / $previous) * 100 : 0;
                                        echo '<span class="trend-down">⬇</span><span class="percent-change">-' . number_format($percent_change, 1) . '%</span>';
                                    }
                                    ?>
                                </div>
                                <div class="previous-period">
                                    <span class="period-label"><?php echo esc_html($sentiment_stats['previous_period_label'] ?? 'Previous Period'); ?></span>
                                    <p class="stat-value"><?php echo number_format($sentiment_stats['previous']['positive_percent'] ?? 0, 1); ?>%</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="insights-section">
                    <h3>Engagement Insights</h3>
                    <div class="stats-grid">
                        <div class="stat-box">
                            <h3>High Engagement Rate</h3>
                            <div class="comparison-row">
                                <div class="current-period">
                                    <span class="period-label"><?php echo esc_html($engagement_stats['current_period_label'] ?? 'Current Period'); ?></span>
                                    <p class="stat-value"><?php echo number_format($engagement_stats['current']['high_engagement_rate'] ?? 0, 1); ?>%</p>
                                </div>
                                <div class="trend-indicator">
                                    <?php 
                                    $current = $engagement_stats['current']['high_engagement_rate'] ?? 0;
                                    $previous = $engagement_stats['previous']['high_engagement_rate'] ?? 0;
                                    if ($current > $previous) {
                                        $percent_change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
                                        echo '<span class="trend-up">⬆</span><span class="percent-change">+' . number_format($percent_change, 1) . '%</span>';
                                    } elseif ($current < $previous) {
                                        $percent_change = $previous > 0 ? (($previous - $current) / $previous) * 100 : 0;
                                        echo '<span class="trend-down">⬇</span><span class="percent-change">-' . number_format($percent_change, 1) . '%</span>';
                                    }
                                    ?>
                                </div>
                                <div class="previous-period">
                                    <span class="period-label"><?php echo esc_html($engagement_stats['previous_period_label'] ?? 'Previous Period'); ?></span>
                                    <p class="stat-value"><?php echo number_format($engagement_stats['previous']['high_engagement_rate'] ?? 0, 1); ?>%</p>
                                </div>
                            </div>
                        </div>
                        <div class="stat-box">
                            <h3>Average Messages Before Drop-off</h3>
                            <div class="comparison-row">
                                <div class="current-period">
                                    <span class="period-label"><?php echo esc_html($engagement_stats['current_period_label'] ?? 'Current Period'); ?></span>
                                    <p class="stat-value"><?php echo number_format($engagement_stats['current']['avg_messages_before_dropoff'] ?? 0, 1); ?></p>
                                </div>
                                <div class="trend-indicator">
                                    <?php 
                                    $current = $engagement_stats['current']['avg_messages_before_dropoff'] ?? 0;
                                    $previous = $engagement_stats['previous']['avg_messages_before_dropoff'] ?? 0;
                                    if ($current > $previous) {
                                        $percent_change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
                                        echo '<span class="trend-up">⬆</span><span class="percent-change">+' . number_format($percent_change, 1) . '%</span>';
                                    } elseif ($current < $previous) {
                                        $percent_change = $previous > 0 ? (($previous - $current) / $previous) * 100 : 0;
                                        echo '<span class="trend-down">⬇</span><span class="percent-change">-' . number_format($percent_change, 1) . '%</span>';
                                    }
                                    ?>
                                </div>
                                <div class="previous-period">
                                    <span class="period-label"><?php echo esc_html($engagement_stats['previous_period_label'] ?? 'Previous Period'); ?></span>
                                    <p class="stat-value"><?php echo number_format($engagement_stats['previous']['avg_messages_before_dropoff'] ?? 0, 1); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Message Statistics -->
                <div class="insights-section">
                    <h2>Message Statistics</h2>
                    <div class="stats-grid">
                        <div class="stat-box">
                            <h3>Total Messages</h3>
                            <div class="comparison-row">
                                <div class="current-period">
                                    <span class="period-label"><?php echo esc_html($message_stats['current_period_label']); ?></span>
                                    <p class="stat-value"><?php echo number_format($message_stats['current']['total_messages']); ?></p>
                                </div>
                                <div class="trend-indicator">
                                    <?php 
                                    $current = $message_stats['current']['total_messages'];
                                    $previous = $message_stats['previous']['total_messages'];
                                    if ($current > $previous) {
                                        $percent_change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
                                        echo '<span class="trend-up">⬆</span><span class="percent-change">+' . number_format($percent_change, 1) . '%</span>';
                                    } elseif ($current < $previous) {
                                        $percent_change = $previous > 0 ? (($previous - $current) / $previous) * 100 : 0;
                                        echo '<span class="trend-down">⬇</span><span class="percent-change">-' . number_format($percent_change, 1) . '%</span>';
                                    }
                                    ?>
                                </div>
                                <div class="previous-period">
                                    <span class="period-label"><?php echo esc_html($message_stats['previous_period_label']); ?></span>
                                    <p class="stat-value"><?php echo number_format($message_stats['previous']['total_messages']); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="stat-box">
                            <h3>Visitor Messages</h3>
                            <div class="comparison-row">
                                <div class="current-period">
                                    <span class="period-label"><?php echo esc_html($message_stats['current_period_label']); ?></span>
                                    <p class="stat-value"><?php echo number_format($message_stats['current']['visitor_messages']); ?></p>
                                </div>
                                <div class="trend-indicator">
                                    <?php 
                                    $current = $message_stats['current']['visitor_messages'];
                                    $previous = $message_stats['previous']['visitor_messages'];
                                    if ($current > $previous) {
                                        $percent_change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
                                        echo '<span class="trend-up">⬆</span><span class="percent-change">+' . number_format($percent_change, 1) . '%</span>';
                                    } elseif ($current < $previous) {
                                        $percent_change = $previous > 0 ? (($previous - $current) / $previous) * 100 : 0;
                                        echo '<span class="trend-down">⬇</span><span class="percent-change">-' . number_format($percent_change, 1) . '%</span>';
                                    }
                                    ?>
                                </div>
                                <div class="previous-period">
                                    <span class="period-label"><?php echo esc_html($message_stats['previous_period_label']); ?></span>
                                    <p class="stat-value"><?php echo number_format($message_stats['previous']['visitor_messages']); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="stat-box">
                            <h3>Chatbot Messages</h3>
                            <div class="comparison-row">
                                <div class="current-period">
                                    <span class="period-label"><?php echo esc_html($message_stats['current_period_label']); ?></span>
                                    <p class="stat-value"><?php echo number_format($message_stats['current']['chatbot_messages']); ?></p>
                                </div>
                                <div class="trend-indicator">
                                    <?php 
                                    $current = $message_stats['current']['chatbot_messages'];
                                    $previous = $message_stats['previous']['chatbot_messages'];
                                    if ($current > $previous) {
                                        $percent_change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
                                        echo '<span class="trend-up">⬆</span><span class="percent-change">+' . number_format($percent_change, 1) . '%</span>';
                                    } elseif ($current < $previous) {
                                        $percent_change = $previous > 0 ? (($previous - $current) / $previous) * 100 : 0;
                                        echo '<span class="trend-down">⬇</span><span class="percent-change">-' . number_format($percent_change, 1) . '%</span>';
                                    }
                                    ?>
                                </div>
                                <div class="previous-period">
                                    <span class="period-label"><?php echo esc_html($message_stats['previous_period_label']); ?></span>
                                    <p class="stat-value"><?php echo number_format($message_stats['previous']['chatbot_messages']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Session Statistics -->
                <div class="insights-section">
                    <h2>Session Statistics</h2>
                    <div class="stats-grid">
                        <div class="stat-box">
                            <h3>Avg Session Duration</h3>
                            <div class="comparison-row">
                                <div class="current-period">
                                    <span class="period-label"><?php echo esc_html($session_stats['current_period_label']); ?></span>
                                    <p class="stat-value"><?php echo number_format($session_stats['current']['avg_duration'], 1); ?> min</p>
                                </div>
                                <div class="trend-indicator">
                                    <?php 
                                    $current = $session_stats['current']['avg_duration'];
                                    $previous = $session_stats['previous']['avg_duration'];
                                    if ($current > $previous) {
                                        $percent_change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
                                        echo '<span class="trend-up">⬆</span><span class="percent-change">+' . number_format($percent_change, 1) . '%</span>';
                                    } elseif ($current < $previous) {
                                        $percent_change = $previous > 0 ? (($previous - $current) / $previous) * 100 : 0;
                                        echo '<span class="trend-down">⬇</span><span class="percent-change">-' . number_format($percent_change, 1) . '%</span>';
                                    }
                                    ?>
                                </div>
                                <div class="previous-period">
                                    <span class="period-label"><?php echo esc_html($session_stats['previous_period_label']); ?></span>
                                    <p class="stat-value"><?php echo number_format($session_stats['previous']['avg_duration'], 1); ?> min</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Token Usage Statistics -->
                <div class="insights-section">
                    <h2>Token Usage Statistics</h2>
                    <div class="stats-grid">
                        <div class="stat-box">
                            <h3>Total Tokens Used</h3>
                            <div class="comparison-row">
                                <div class="current-period">
                                    <span class="period-label"><?php echo esc_html($token_stats['current_period_label']); ?></span>
                                    <p class="stat-value"><?php echo number_format($token_stats['current']['total_tokens'] ?? 0); ?></p>
                                </div>
                                <div class="trend-indicator">
                                    <?php 
                                    $current = $token_stats['current']['total_tokens'] ?? 0;
                                    $previous = $token_stats['previous']['total_tokens'] ?? 0;
                                    if ($current > $previous) {
                                        $percent_change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
                                        echo '<span class="trend-up">⬆</span><span class="percent-change">+' . number_format($percent_change, 1) . '%</span>';
                                    } elseif ($current < $previous) {
                                        $percent_change = $previous > 0 ? (($previous - $current) / $previous) * 100 : 0;
                                        echo '<span class="trend-down">⬇</span><span class="percent-change">-' . number_format($percent_change, 1) . '%</span>';
                                    }
                                    ?>
                                </div>
                                <div class="previous-period">
                                    <span class="period-label"><?php echo esc_html($token_stats['previous_period_label']); ?></span>
                                    <p class="stat-value"><?php echo number_format($token_stats['previous']['total_tokens'] ?? 0); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <style>
                .insights-container {
                    max-width: 1200px;
                    margin: 20px 0;
                }
                .period-filter-form {
                    margin: 20px 0;
                }
                .period-filter-form select,
                .button {
                    vertical-align: middle;
                    box-sizing: border-box;
                    font-size: 15px;
                    padding: 0 14px;
                    line-height: 1.2;
                }
                .period-filter-form select {
                    position: relative;
                    top: 0px; /* Adjust this value as needed for perfect alignment */
                }
                .user-type-filter-form {
                    margin: 20px 0;
                }
                .user-type-filter-form select {
                    vertical-align: middle;
                    box-sizing: border-box;
                    font-size: 15px;
                    padding: 0 14px;
                    line-height: 1.2;
                }
                .user-type-filter-form select {
                    vertical-align: middle;
                    box-sizing: border-box;
                    font-size: 15px;
                    padding: 0 14px;
                    line-height: 1.2;
                }
                .scoring-control-form {
                    margin: 20px 0;
                }
                .scoring-control-form select {
                    vertical-align: middle;
                }
                .insights-section {
                    background: #fff;
                    padding: 20px;
                    margin-bottom: 20px;
                    border-radius: 5px;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                }
                .stats-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                    gap: 20px;
                    margin-top: 15px;
                }
                .stat-box {
                    background: #f8f9fa;
                    padding: 15px;
                    border-radius: 4px;
                }
                .stat-box h3 {
                    margin: 0 0 10px 0;
                    font-size: 14px;
                    color: #666;
                }
                .comparison-row {
                    display: flex;
                    justify-content: space-between;
                    gap: 20px;
                    align-items: center;
                }
                .current-period, .previous-period {
                    flex: 1;
                    text-align: center;
                }
                .trend-indicator {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 32px;
                    font-weight: bold;
                    min-width: 40px;
                    margin: 0 10px;
                    flex-direction: column;
                }
                .trend-up {
                    color: #28a745;
                    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
                    margin-bottom: 4px;
                }
                .trend-down {
                    color: #dc3545;
                    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
                    margin-bottom: 4px;
                }
                .percent-change {
                    font-size: 14px;
                    font-weight: normal;
                    margin-top: 2px;
                }
                .trend-up + .percent-change {
                    color: #28a745;
                }
                .trend-down + .percent-change {
                    color: #dc3545;
                }
                .period-label {
                    display: block;
                    font-size: 12px;
                    color: #666;
                    margin-bottom: 5px;
                }
                .stat-value {
                    font-size: 24px;
                    font-weight: bold;
                    margin: 0;
                    color: #2271b1;
                }
                h2 {
                    margin-top: 0;
                    color: #1d2327;
                    border-bottom: 1px solid #eee;
                    padding-bottom: 10px;
                }
                .section-header {
                    margin-bottom: 20px;
                    border-bottom: 2px solid #e5e5e5;
                    padding-bottom: 10px;
                }
                .section-header h2 {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    margin: 0;
                    padding: 0;
                    color: #1d2327;
                    font-size: 1.5em;
                }
                .section-description {
                    color: #646970;
                    margin: 5px 0 0;
                    font-size: 14px;
                }
                .nav-tab-wrapper {
                    border-bottom: 1px solid #ccc;
                    padding-bottom: 0;
                    margin-bottom: 20px;
                    background: #f1f1f1;
                }
                .nav-tab, .nav-tab:focus, .nav-tab:active {
                    font-weight: normal !important;
                    background: #e5e5e5 !important;
                    color: #555 !important;
                    border: 1px solid #ccc !important;
                    border-bottom: none !important;
                    box-shadow: none !important;
                    outline: none !important;
                }
                .nav-tab-active, .nav-tab-active:focus, .nav-tab-active:active {
                    background: #fff !important;
                    color: #222 !important;
                    border-bottom: 1px solid #fff !important;
                    z-index: 2;
                    font-weight: bold !important;
                }
            </style>
        </div>
        <?php
    }
    echo '</div>'; // Close .wrap
    
}

// Function to start the scoring process
function kognetiks_insights_start_scoring() {

    // DIAG - Diagnostics

    kognetiks_insights_set_scoring_status('running');

}
