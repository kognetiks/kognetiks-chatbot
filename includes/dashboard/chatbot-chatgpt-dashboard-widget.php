<?php
/**
 * Kognetiks Chatbot - Dashboard Widget
 *
 * This file contains the code for the Chatbot dashboard widget.
 * It displays chatbot statistics and token usage in the WordPress admin dashboard.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die();
}

// Add the dashboard widget
function chatbot_chatgpt_add_dashboard_widget() {

    // Only show to users who can manage options (administrators)
    if (current_user_can('manage_options')) {
        wp_add_dashboard_widget(
            'chatbot_chatgpt_dashboard_widget',
            'Kognetiks Chatbot Statistics',
            'chatbot_chatgpt_dashboard_widget_content'
        );
    }

}
add_action('wp_dashboard_setup', 'chatbot_chatgpt_add_dashboard_widget');

// Format duration in seconds to human readable format
function format_duration($seconds) {

    if ($seconds < 60) {
        return round($seconds) . ' seconds';
    } elseif ($seconds < 3600) {
        return round($seconds / 60) . ' minutes';
    } elseif ($seconds < 86400) {
        return round($seconds / 3600, 1) . ' hours';
    } else {
        return round($seconds / 86400, 1) . ' days';
    }

}

// Widget content function
function chatbot_chatgpt_dashboard_widget_content() {

    global $wpdb;
    
    // Get the current period setting
    $period = get_option('chatbot_chatgpt_dashboard_period', '24h');
    $view_type = get_option('chatbot_chatgpt_dashboard_view', 'sessions');
    
    // Handle form submission
    if (isset($_POST['chatbot_chatgpt_dashboard_period'])) {
        $period = sanitize_text_field($_POST['chatbot_chatgpt_dashboard_period']);
        update_option('chatbot_chatgpt_dashboard_period', $period);
    }
    
    if (isset($_POST['chatbot_chatgpt_dashboard_view'])) {
        $view_type = sanitize_text_field($_POST['chatbot_chatgpt_dashboard_view']);
        update_option('chatbot_chatgpt_dashboard_view', $view_type);
    }
    
    // Calculate the start date based on the period
    $start_date = '';
    switch ($period) {
        case '24h':
            $start_date = date('Y-m-d H:i:s', strtotime('-24 hours'));
            break;
        case '7d':
            $start_date = date('Y-m-d H:i:s', strtotime('-7 days'));
            break;
        case '30d':
            $start_date = date('Y-m-d H:i:s', strtotime('-30 days'));
            break;
        case '90d':
            $start_date = date('Y-m-d H:i:s', strtotime('-90 days'));
            break;
        case '365d':
            $start_date = date('Y-m-d H:i:s', strtotime('-365 days'));
            break;
    }
    
    // Debug: Log the calculated start date
    prod_trace( 'NOTICE', 'Dashboard widget - Period: ' . $period . ', Start date: ' . $start_date);
    
    // Get chat statistics
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';
    
    // Check if table exists first
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    if (!$table_exists) {
        prod_trace( 'ERROR', 'Table ' . $table_name . ' does not exist');
        $chat_count = 0;
        $daily_chats = array();
        $token_stats = array();
        $avg_duration = null;
    } else {
        if ($view_type === 'sessions') {
            $chat_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT session_id) FROM $table_name WHERE interaction_time >= %s",
                $start_date
            ));
        
            // Get daily chat counts for the graph
            if ($period === '24h') {
                // Use simpler query for 24h period to avoid placeholder issues
                $daily_chats = $wpdb->get_results($wpdb->prepare(
                    "SELECT 
                        DATE_FORMAT(interaction_time, '%%Y-%%m-%%d %%H:00:00') as date,
                        COUNT(DISTINCT session_id) as count
                    FROM $table_name 
                    WHERE interaction_time >= %s
                    GROUP BY DATE_FORMAT(interaction_time, '%%Y-%%m-%%d %%H:00:00')
                    ORDER BY date ASC",
                    $start_date
                ));
            } else {
                // Use simpler query for daily periods to avoid placeholder issues
                $daily_chats = $wpdb->get_results($wpdb->prepare(
                    "SELECT 
                        DATE(interaction_time) as date,
                        COUNT(DISTINCT session_id) as count
                    FROM $table_name 
                    WHERE interaction_time >= %s
                    GROUP BY DATE(interaction_time)
                    ORDER BY date ASC",
                    $start_date
                ));
            }
        } else {
            $chat_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) / 2 FROM $table_name 
                WHERE interaction_time >= %s 
                AND user_type IN ('Chatbot', 'Visitor')",
                $start_date
            ));
        
        // Get daily interaction counts for the graph
        if ($period === '24h') {
            // Use simpler query for 24h period to avoid placeholder issues
            $daily_chats = $wpdb->get_results($wpdb->prepare(
                "SELECT 
                    DATE_FORMAT(interaction_time, '%%Y-%%m-%%d %%H:00:00') as date,
                    COUNT(*) / 2 as count
                FROM $table_name 
                WHERE interaction_time >= %s AND user_type IN ('Chatbot', 'Visitor')
                GROUP BY DATE_FORMAT(interaction_time, '%%Y-%%m-%%d %%H:00:00')
                ORDER BY date ASC",
                $start_date
            ));
        } else {
            // Use simpler query for daily periods to avoid placeholder issues
            $daily_chats = $wpdb->get_results($wpdb->prepare(
                "SELECT 
                    DATE(interaction_time) as date,
                    COUNT(*) / 2 as count
                FROM $table_name 
                WHERE interaction_time >= %s AND user_type IN ('Chatbot', 'Visitor')
                GROUP BY DATE(interaction_time)
                ORDER BY date ASC",
                $start_date
            ));
        }
    }
    
    // Close the table existence check
    }
    
    // DIAG - Diagnostics - Ver 2.2.7
    if (empty($daily_chats)) {
        prod_trace( 'NOTICE', 'No data in daily_chats. Period: ' . $period . ', Start date: ' . $start_date);
        prod_trace( 'NOTICE', 'Total chats count: ' . $chat_count);
        
        // Check if table exists and has data
        if (isset($table_exists) && !$table_exists) {
            prod_trace( 'ERROR', 'Table ' . $table_name . ' does not exist');
        } else {
            $total_rows = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            prod_trace( 'NOTICE', 'Table exists with ' . $total_rows . ' total rows');
            
            // Check for recent data
            $recent_data = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE interaction_time >= %s",
                $start_date
            ));
            prod_trace( 'NOTICE', 'Recent data count: ' . $recent_data);
        }
    }
    
    // Get token usage - only if table exists
    if (isset($table_exists) && $table_exists) {
        $token_stats = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                SUM(CASE WHEN user_type = 'Prompt Tokens' THEN CAST(message_text AS UNSIGNED) ELSE 0 END) as prompt_tokens,
                SUM(CASE WHEN user_type = 'Completion Tokens' THEN CAST(message_text AS UNSIGNED) ELSE 0 END) as completion_tokens,
                SUM(CASE WHEN user_type = 'Total Tokens' THEN CAST(message_text AS UNSIGNED) ELSE 0 END) as total_tokens
            FROM $table_name 
            WHERE interaction_time >= %s",
            $start_date
        ));
        
        // Check for query errors
        if ($wpdb->last_error) {
            prod_trace( 'ERROR', 'Token stats query error: ' . $wpdb->last_error);
            $token_stats = array();
        }
        
        // Get average conversation duration
        $avg_duration = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(duration) FROM (
                SELECT session_id, 
                       TIMESTAMPDIFF(SECOND, MIN(interaction_time), MAX(interaction_time)) as duration
                FROM $table_name 
                WHERE interaction_time >= %s
                GROUP BY session_id
            ) as durations",
            $start_date
        ));
        
        // Check for query errors
        if ($wpdb->last_error) {
            prod_trace( 'ERROR', 'Average duration query error: ' . $wpdb->last_error);
            $avg_duration = null;
        }
    } else {
        $token_stats = array();
        $avg_duration = null;
    }
    
    // Output the statistics
    ?>
    <div class="chatbot-dashboard-stats">
        <style>
            .chatbot-dashboard-stats {
                padding: 10px;
            }
            .chatbot-period-selector {
                margin-bottom: 20px;
                padding: 10px;
                background: #f0f0f1;
                border-radius: 4px;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .chatbot-period-selector label {
                font-weight: 600;
                color: #1d2327;
            }
            .chatbot-period-select {
                width: 100%;
                padding: 8px 12px;
                border: 1px solid #8c8f94;
                border-radius: 4px;
                background-color: #fff;
                color: #2c3338;
                font-size: 14px;
                line-height: 1.4;
                appearance: none;
                -webkit-appearance: none;
                -moz-appearance: none;
                position: relative;
                padding-right: 30px;
                background-image: none;
            }
            .chatbot-period-select:focus {
                border-color: #2271b1;
                box-shadow: 0 0 0 1px #2271b1;
                outline: none;
            }
            .chatbot-period-select-wrapper {
                position: relative;
                flex: 1;
            }
            .chatbot-period-select-wrapper::after {
                /* content: "\f347"; dashicons-arrow-down-alt2 */
                font-family: dashicons;
                position: absolute;
                right: 10px;
                top: 50%;
                transform: translateY(-50%);
                pointer-events: none;
                color: #2c3338;
                font-size: 16px;
            }
            .chatbot-date-range {
                margin: 5px 0 15px 0;
                padding: 5px 10px;
                color: #646970;
                font-size: 13px;
                text-align: center;
            }
            .chatbot-stat-box {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 15px;
                margin-bottom: 15px;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
            .chatbot-stat-title {
                font-weight: 600;
                margin-bottom: 10px;
                color: #1d2327;
            }
            .chatbot-stat-value {
                font-size: 24px;
                color: #2271b1;
                margin: 5px 0;
            }
            .chatbot-stat-label {
                color: #646970;
                font-size: 13px;
            }
            .chatbot-token-stats {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
            }
            .chatbot-token-stat {
                text-align: center;
                padding: 10px;
                background: #f0f0f1;
                border-radius: 4px;
            }
            .chatbot-graph {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 15px;
                margin-bottom: 15px;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
            .chatbot-graph-title {
                font-weight: 600;
                margin-bottom: 10px;
                color: #1d2327;
            }
            .chatbot-graph-container {
                display: flex;
                align-items: flex-end;
                height: 100px;
                padding: 10px 0;
                gap: 8px;
                border-bottom: 1px solid #e2e4e7;
                min-height: 100px;
                overflow-x: auto;
                overflow-y: hidden;
                padding-bottom: 25px;
            }
            .chatbot-graph-container::-webkit-scrollbar {
                height: 8px;
            }
            .chatbot-graph-container::-webkit-scrollbar-track {
                background: #f1f1f1;
                border-radius: 4px;
            }
            .chatbot-graph-container::-webkit-scrollbar-thumb {
                background: #888;
                border-radius: 4px;
            }
            .chatbot-graph-container::-webkit-scrollbar-thumb:hover {
                background: #555;
            }
            .chatbot-graph-bar {
                flex: 0 0 30px;
                background: #2271b1;
                border-radius: 2px 2px 0 0;
                position: relative;
                min-height: 1px;
            }
            .chatbot-graph-bar:hover {
                background: #135e96;
            }
            .chatbot-graph-bar .chatbot-graph-value {
                position: absolute;
                top: -20px;
                left: 50%;
                transform: translateX(-50%);
                font-size: 12px;
                color: #646970;
                white-space: nowrap;
            }
            .chatbot-graph-bar .chatbot-graph-label {
                position: absolute;
                bottom: -20px;
                left: 50%;
                transform: translateX(-50%);
                font-size: 11px;
                color: #646970;
                white-space: nowrap;
                text-transform: <?php echo $period === '7d' ? 'uppercase' : 'none'; ?>;
            }
            .chatbot-view-selector {
                margin-bottom: 20px;
                padding: 10px;
                background: #f0f0f1;
                border-radius: 4px;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .chatbot-view-selector label {
                font-weight: 600;
                color: #1d2327;
            }
            .chatbot-view-select {
                width: 100%;
                padding: 8px 12px;
                border: 1px solid #8c8f94;
                border-radius: 4px;
                background-color: #fff;
                color: #2c3338;
                font-size: 14px;
                line-height: 1.4;
                appearance: none;
                -webkit-appearance: none;
                -moz-appearance: none;
                position: relative;
                padding-right: 30px;
                background-image: none;
            }
            .chatbot-view-select:focus {
                border-color: #2271b1;
                box-shadow: 0 0 0 1px #2271b1;
                outline: none;
            }
            .chatbot-view-select-wrapper {
                position: relative;
                flex: 1;
            }
            .chatbot-view-select-wrapper::after {
                font-family: dashicons;
                position: absolute;
                right: 10px;
                top: 50%;
                transform: translateY(-50%);
                pointer-events: none;
                color: #2c3338;
                font-size: 16px;
            }
        </style>
        
        <form method="post" class="chatbot-period-selector">
            <label for="chatbot_chatgpt_dashboard_period">Time Period:</label>
            <div class="chatbot-period-select-wrapper">
                <select name="chatbot_chatgpt_dashboard_period" id="chatbot_chatgpt_dashboard_period" onchange="this.form.submit()" class="chatbot-period-select">
                    <option value="24h" <?php selected($period, '24h'); ?>>Last 24 Hours</option>
                    <option value="7d" <?php selected($period, '7d'); ?>>Last 7 Days</option>
                    <option value="30d" <?php selected($period, '30d'); ?>>Last 30 Days</option>
                    <option value="90d" <?php selected($period, '90d'); ?>>Last 90 Days</option>
                    <option value="365d" <?php selected($period, '365d'); ?>>Last 365 Days</option>
                </select>
            </div>
        </form>
        
        <form method="post" class="chatbot-view-selector">
            <label for="chatbot_chatgpt_dashboard_view">View Type:</label>
            <div class="chatbot-view-select-wrapper">
                <select name="chatbot_chatgpt_dashboard_view" id="chatbot_chatgpt_dashboard_view" onchange="this.form.submit()" class="chatbot-view-select">
                    <option value="sessions" <?php selected($view_type, 'sessions'); ?>>Sessions</option>
                    <option value="interactions" <?php selected($view_type, 'interactions'); ?>>Interactions</option>
                </select>
            </div>
        </form>
        
        <div class="chatbot-date-range">
            <?php 
            $end_date = current_time('mysql');
            echo date('F j, Y', strtotime($start_date)) . ' - ' . date('F j, Y', strtotime($end_date));
            ?>
        </div>
        
        <div class="chatbot-graph">
            <div class="chatbot-graph-title"><?php 
                echo $period === '24h' ? 'Today\'s Chat Activity' : 
                    ($period === '7d' ? 'Chat Activity (Last 7 Days)' : 
                    ($period === '30d' ? 'Chat Activity (Last 30 Days)' :
                    ($period === '90d' ? 'Chat Activity (Last 90 Days)' :
                    'Chat Activity (Last 365 Days)'))); 
            ?></div>
            <div class="chatbot-graph-container">
                <?php
                if (!empty($daily_chats)) {
                    $max_count = max(array_column($daily_chats, 'count'));
                    foreach ($daily_chats as $day) {
                        $height = $max_count > 0 ? (round($day->count) / $max_count * 100) : 0;
                        ?>
                        <div class="chatbot-graph-bar" style="height: <?php echo $height; ?>%">
                            <div class="chatbot-graph-value"><?php echo round($day->count); ?></div>
                            <div class="chatbot-graph-label"><?php echo date('m/d', strtotime($day->date)); ?></div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div style="text-align: center; color: #646970;">No chat activity in this period</div>';
                }
                ?>
            </div>
        </div>
        
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const graphContainer = document.querySelector('.chatbot-graph-container');
                if (graphContainer) {
                    // Scroll to the right
                    graphContainer.scrollLeft = graphContainer.scrollWidth;
                    
                    // Add a small delay to ensure the scroll happens after all content is loaded
                    setTimeout(() => {
                        graphContainer.scrollLeft = graphContainer.scrollWidth;
                    }, 100);
                }
            });
        </script>
        
        <div class="chatbot-stat-box">
            <div class="chatbot-stat-title">Total <?php echo $view_type === 'sessions' ? 'Sessions' : 'Interactions'; ?></div>
            <div class="chatbot-stat-value"><?php echo number_format($chat_count); ?></div>
            <div class="chatbot-stat-label"><?php echo $view_type === 'sessions' ? 'Unique conversations in the selected period' : 'Total interactions (messages exchanged) in the selected period'; ?></div>
        </div>
        
        <div class="chatbot-stat-box">
            <div class="chatbot-stat-title">Average Conversation Duration</div>
            <div class="chatbot-stat-value"><?php echo $avg_duration ? format_duration($avg_duration) : '0 seconds'; ?></div>
            <div class="chatbot-stat-label">Average time spent per conversation</div>
        </div>
        
        <div class="chatbot-stat-box">
            <div class="chatbot-stat-title">Token Usage</div>
            <div class="chatbot-token-stats">
                <div class="chatbot-token-stat">
                    <div class="chatbot-stat-value"><?php echo number_format(!empty($token_stats) && isset($token_stats[0]) ? ($token_stats[0]->prompt_tokens ?? 0) : 0); ?></div>
                    <div class="chatbot-stat-label">Prompt Tokens</div>
                </div>
                <div class="chatbot-token-stat">
                    <div class="chatbot-stat-value"><?php echo number_format(!empty($token_stats) && isset($token_stats[0]) ? ($token_stats[0]->completion_tokens ?? 0) : 0); ?></div>
                    <div class="chatbot-stat-label">Completion Tokens</div>
                </div>
                <div class="chatbot-token-stat">
                    <div class="chatbot-stat-value"><?php echo number_format(!empty($token_stats) && isset($token_stats[0]) ? ($token_stats[0]->total_tokens ?? 0) : 0); ?></div>
                    <div class="chatbot-stat-label">Total Tokens</div>
                </div>
            </div>
        </div>
    </div>
    <?php
    
} 