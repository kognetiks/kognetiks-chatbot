<?php
/**
 * Kognetiks Analytics - Utilities - Ver 1.0.0
 *
 * This file contains the code for the Kognetiks Analytics utilities.
 * 
 * 
 * 
 * @package kognetiks-analytics
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// SQL Query to get the data from the chatbot_chatgpt_conversation_log table
function kognetiks_analytics_get_chatbot_chatgpt_conversation_log_data() {

    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';

    $results = $wpdb->get_results("SELECT * FROM $table_name");

    // Error handling
    if (empty($results)) {
        return "No data found";
    }

    return $results;

}

// SQL Query to count the number of rows in the chatbot_chatgpt_conversation_log table
function kognetiks_analytics_count_chatbot_chatgpt_conversation_log_data() {

    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';

    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

    // Error handling
    if (empty($count)) {
        return "No data found";
    }

    return $count;

}

// Compute the total tokens
function kognetiks_analytics_total_tokens() {

    global $wpdb;

    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';

    //SQL Query to total the number of Prompt Tokens used where user_type = "Prompt Tokens" and values is integer in "messsage_text"
    $prompt_tokens_total = $wpdb->get_var("SELECT SUM(CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(message_text, 'prompt_tokens:', -1), ' completion_tokens:', 1) AS UNSIGNED)) FROM $table_name WHERE user_type = 'Prompt Tokens'");

    // Error handling
    if (empty($total)) {
        return "No data found";
    }

    // SQL Query to total the number of Completion Tokens used where user_type = "Completion Tokens" and values is integer in "messsage_text"
    $completion_tokens_total = $wpdb->get_var("SELECT SUM(CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(message_text, 'completion_tokens:', -1), ' total_tokens:', 1) AS UNSIGNED)) FROM $table_name WHERE user_type = 'Completion Tokens'");

    // Error handling
    if (empty($completion_tokens_total)) {
        return "No data found";
    }

    // SQL Query to total the number of Total Tokens used where user_type = "Total Tokens" and values is integer in "messsage_text"
    $total_tokens_total = $wpdb->get_var("SELECT SUM(CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(message_text, 'total_tokens:', -1), ' prompt_tokens:', 1) AS UNSIGNED)) FROM $table_name WHERE user_type = 'Total Tokens'");

    // Error handling
    if (empty($total_tokens_total)) {
        return "No data found";
    }

    // SQL Query to count the number of "Visitor" user_type prompt
    $visitor_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE user_type = 'Visitor'");

    // Error handling
    if (empty($visitor_count)) {
        return "No data found";
    }

    // Count the number of unique "session_id"
    $session_id_count = $wpdb->get_var("SELECT COUNT(DISTINCT session_id) FROM $table_name");

    // Error handling
    if (empty($session_id_count)) {
        return "No data found";
    }
   
    // SQL Query to count the number of "Chatbot" user_type prompt
    $chatbot_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE user_type = 'Chatbot'");

    // Error handling
    if (empty($chatbot_count)) {
        return "No data found";
    }

    // Compute basic analytics
    $total_tokens = $prompt_tokens_total + $completion_tokens_total;
    $total_tokens_per_prompt = $total_tokens / $visitor_count;
    $avearge_prompt_tokens_per_visitor = $prompt_tokens_total / $visitor_count;
    $avearge_completion_tokens_per_visitor = $completion_tokens_total / $visitor_count;
    $avearge_total_tokens_per_visitor = $total_tokens / $visitor_count;
    $average_tokens_per_prompt = $total_tokens / $chatbot_count;
    $average_tokens_per_chatbot = $total_tokens / $chatbot_count;
    $average_tokens_per_visitor = $total_tokens / $visitor_count;
    $average_tokens_per_session = $total_tokens / $session_id_count;
    $average_tokens_per_chatbot_per_session = $total_tokens / $chatbot_count / $session_id_count;
    $average_tokens_per_visitor_per_session = $total_tokens / $visitor_count / $session_id_count;

    // Return the results
    return array(
        'total_tokens' => $total_tokens,
        'total_tokens_per_prompt' => $total_tokens_per_prompt,
        'prompt_tokens_total' => $prompt_tokens_total,
        'completion_tokens_total' => $completion_tokens_total,
        'total_tokens_total' => $total_tokens_total,
        'visitor_count' => $visitor_count,
        'chatbot_count' => $chatbot_count,
        'average_tokens_per_prompt' => $average_tokens_per_prompt,
        'average_tokens_per_chatbot' => $average_tokens_per_chatbot,
        'average_tokens_per_visitor' => $average_tokens_per_visitor,
        'session_id_count' => $session_id_count,
        'average_tokens_per_session' => $average_tokens_per_session,
        'average_tokens_per_chatbot_per_session' => $average_tokens_per_chatbot_per_session,
        'average_tokens_per_visitor_per_session' => $average_tokens_per_visitor_per_session
    );

}

// Compute time-based conversation counts
function kognetiks_analytics_get_time_based_conversation_counts($period = 'Today', $user_type = 'All') {

    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';
    
    // First, let's check if we have any data at all
    $total_records = $wpdb->get_var("SELECT COUNT(*) FROM `$table_name`");
    back_trace('NOTICE', "Total records in table: " . $total_records);
    
    // Check the timestamp format
    $sample_timestamp = $wpdb->get_var("SELECT interaction_time FROM `$table_name` LIMIT 1");
    back_trace('NOTICE', "Sample timestamp: " . $sample_timestamp);
    
    // Define period ranges
    $periods = array(
        'Today' => array(
            'current' => "DATE(interaction_time) = CURDATE()",
            'previous' => "DATE(interaction_time) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)",
            'current_label' => 'Today',
            'previous_label' => 'Yesterday'
        ),
        'Week' => array(
            'current' => "YEARWEEK(interaction_time) = YEARWEEK(CURDATE())",
            'previous' => "YEARWEEK(interaction_time) = YEARWEEK(DATE_SUB(CURDATE(), INTERVAL 1 WEEK))",
            'current_label' => 'This Week',
            'previous_label' => 'Last Week'
        ),
        'Month' => array(
            'current' => "DATE_FORMAT(interaction_time, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')",
            'previous' => "DATE_FORMAT(interaction_time, '%Y-%m') = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m')",
            'current_label' => 'This Month',
            'previous_label' => 'Last Month'
        ),
        'Quarter' => array(
            'current' => "QUARTER(interaction_time) = QUARTER(CURDATE()) AND YEAR(interaction_time) = YEAR(CURDATE())",
            'previous' => "QUARTER(interaction_time) = QUARTER(DATE_SUB(CURDATE(), INTERVAL 3 MONTH)) AND YEAR(interaction_time) = YEAR(DATE_SUB(CURDATE(), INTERVAL 3 MONTH))",
            'current_label' => 'This Quarter',
            'previous_label' => 'Last Quarter'
        ),
        'Year' => array(
            'current' => "YEAR(interaction_time) = YEAR(CURDATE())",
            'previous' => "YEAR(interaction_time) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 YEAR))",
            'current_label' => 'This Year',
            'previous_label' => 'Last Year'
        )
    );
    
    $period_info = $periods[$period];
    
    // Add user type filter condition
    $user_type_condition = '';
    if ($user_type !== 'All') {
        $user_type_condition = " AND user_type = '" . esc_sql($user_type) . "'";
    }
    
    // Get current period data
    $current_query = "SELECT COUNT(DISTINCT session_id) FROM `$table_name` WHERE " . $period_info['current'] . $user_type_condition;
    $current_total = $wpdb->get_var($current_query);
    
    $current_unique_query = "SELECT COUNT(DISTINCT session_id) FROM `$table_name` WHERE " . $period_info['current'] . $user_type_condition;
    $current_unique = $wpdb->get_var($current_unique_query);
    
    // Get previous period data
    $previous_query = "SELECT COUNT(DISTINCT session_id) FROM `$table_name` WHERE " . $period_info['previous'] . $user_type_condition;
    $previous_total = $wpdb->get_var($previous_query);
    
    $previous_unique_query = "SELECT COUNT(DISTINCT session_id) FROM `$table_name` WHERE " . $period_info['previous'] . $user_type_condition;
    $previous_unique = $wpdb->get_var($previous_unique_query);
    
    // DIAG - Diagnostics - V1.0.0
    back_trace('NOTICE', "Current period query: " . $current_query);
    back_trace('NOTICE', "Previous period query: " . $previous_query);
    
    $current_data = array(
        'total' => $current_total,
        'unique_visitors' => $current_unique
    );
    
    $previous_data = array(
        'total' => $previous_total,
        'unique_visitors' => $previous_unique
    );
    
    back_trace('NOTICE', "Current period results: " . json_encode($current_data));
    back_trace('NOTICE', "Previous period results: " . json_encode($previous_data));
    
    return array(
        'current' => $current_data,
        'previous' => $previous_data,
        'current_period_label' => $period_info['current_label'],
        'previous_period_label' => $period_info['previous_label']
    );

}

// Compute message statistics
function kognetiks_analytics_get_message_statistics($period = 'Today', $user_type = 'All') {

    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';
    
    // First, let's check if we have any data at all
    $total_records = $wpdb->get_var("SELECT COUNT(*) FROM `$table_name`");
    back_trace('NOTICE', "Total records in table: " . $total_records);
    
    // Check the timestamp format
    $sample_timestamp = $wpdb->get_var("SELECT interaction_time FROM `$table_name` LIMIT 1");
    back_trace('NOTICE', "Sample timestamp: " . $sample_timestamp);
    
    // Define period ranges (same as above)
    $periods = array(
        'Today' => array(
            'current' => "DATE(interaction_time) = CURDATE()",
            'previous' => "DATE(interaction_time) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)",
            'current_label' => 'Today',
            'previous_label' => 'Yesterday'
        ),
        'Week' => array(
            'current' => "YEARWEEK(interaction_time) = YEARWEEK(CURDATE())",
            'previous' => "YEARWEEK(interaction_time) = YEARWEEK(DATE_SUB(CURDATE(), INTERVAL 1 WEEK))",
            'current_label' => 'This Week',
            'previous_label' => 'Last Week'
        ),
        'Month' => array(
            'current' => "DATE_FORMAT(interaction_time, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')",
            'previous' => "DATE_FORMAT(interaction_time, '%Y-%m') = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m')",
            'current_label' => 'This Month',
            'previous_label' => 'Last Month'
        ),
        'Quarter' => array(
            'current' => "QUARTER(interaction_time) = QUARTER(CURDATE()) AND YEAR(interaction_time) = YEAR(CURDATE())",
            'previous' => "QUARTER(interaction_time) = QUARTER(DATE_SUB(CURDATE(), INTERVAL 3 MONTH)) AND YEAR(interaction_time) = YEAR(DATE_SUB(CURDATE(), INTERVAL 3 MONTH))",
            'current_label' => 'This Quarter',
            'previous_label' => 'Last Quarter'
        ),
        'Year' => array(
            'current' => "YEAR(interaction_time) = YEAR(CURDATE())",
            'previous' => "YEAR(interaction_time) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 YEAR))",
            'current_label' => 'This Year',
            'previous_label' => 'Last Year'
        )
    );
    
    $period_info = $periods[$period];
    
    // Add user type filter condition
    $user_type_condition = '';
    if ($user_type !== 'All') {
        $user_type_condition = " AND user_type = '" . esc_sql($user_type) . "'";
    }
    
    $current_query = "
        SELECT 
            COUNT(*) as total_messages,
            COUNT(CASE WHEN user_type = 'Visitor' THEN 1 END) as visitor_messages,
            COUNT(CASE WHEN user_type = 'Chatbot' THEN 1 END) as chatbot_messages
        FROM `$table_name`
        WHERE " . $period_info['current'] . $user_type_condition;

    // DIAG - Diagnostics - V1.0.0
    back_trace('NOTICE', "Current period query: " . $current_query);
    
    $previous_query = "
        SELECT 
            COUNT(*) as total_messages,
            COUNT(CASE WHEN user_type = 'Visitor' THEN 1 END) as visitor_messages,
            COUNT(CASE WHEN user_type = 'Chatbot' THEN 1 END) as chatbot_messages
        FROM `$table_name`
        WHERE " . $period_info['previous'] . $user_type_condition;

    // DIAG - Diagnostics - V1.0.0
    back_trace('NOTICE', "Previous period query: " . $previous_query);
    
    // Get current period data
    $current_data = $wpdb->get_row($current_query);
    
    // Get previous period data
    $previous_data = $wpdb->get_row($previous_query);
    
    return array(
        'current' => array(
            'total_messages' => $current_data->total_messages,
            'visitor_messages' => $current_data->visitor_messages,
            'chatbot_messages' => $current_data->chatbot_messages
        ),
        'previous' => array(
            'total_messages' => $previous_data->total_messages,
            'visitor_messages' => $previous_data->visitor_messages,
            'chatbot_messages' => $previous_data->chatbot_messages
        ),
        'current_period_label' => $period_info['current_label'],
        'previous_period_label' => $period_info['previous_label']
    );

}

// Compute session statistics
function kognetiks_analytics_get_session_statistics($period = 'Today', $user_type = 'All') {

    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';
    
    // Define period ranges (same as above)
    $periods = array(
        'Today' => array(
            'current' => "DATE(interaction_time) = CURDATE()",
            'previous' => "DATE(interaction_time) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)",
            'current_label' => 'Today',
            'previous_label' => 'Yesterday'
        ),
        'Week' => array(
            'current' => "YEARWEEK(interaction_time) = YEARWEEK(CURDATE())",
            'previous' => "YEARWEEK(interaction_time) = YEARWEEK(DATE_SUB(CURDATE(), INTERVAL 1 WEEK))",
            'current_label' => 'This Week',
            'previous_label' => 'Last Week'
        ),
        'Month' => array(
            'current' => "DATE_FORMAT(interaction_time, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')",
            'previous' => "DATE_FORMAT(interaction_time, '%Y-%m') = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m')",
            'current_label' => 'This Month',
            'previous_label' => 'Last Month'
        ),
        'Quarter' => array(
            'current' => "QUARTER(interaction_time) = QUARTER(CURDATE()) AND YEAR(interaction_time) = YEAR(CURDATE())",
            'previous' => "QUARTER(interaction_time) = QUARTER(DATE_SUB(CURDATE(), INTERVAL 3 MONTH)) AND YEAR(interaction_time) = YEAR(DATE_SUB(CURDATE(), INTERVAL 3 MONTH))",
            'current_label' => 'This Quarter',
            'previous_label' => 'Last Quarter'
        ),
        'Year' => array(
            'current' => "YEAR(interaction_time) = YEAR(CURDATE())",
            'previous' => "YEAR(interaction_time) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 YEAR))",
            'current_label' => 'This Year',
            'previous_label' => 'Last Year'
        )
    );
    
    $period_info = $periods[$period];
    
    // Add user type filter condition
    $user_type_condition = '';
    if ($user_type !== 'All') {
        $user_type_condition = " AND user_type = '" . esc_sql($user_type) . "'";
    }
    
    $current_query = "
        SELECT 
            session_id,
            TIMESTAMPDIFF(MINUTE, MIN(interaction_time), MAX(interaction_time)) as duration_minutes
        FROM `$table_name`
        WHERE " . $period_info['current'] . $user_type_condition . "
        GROUP BY session_id";
    
    $previous_query = "
        SELECT 
            session_id,
            TIMESTAMPDIFF(MINUTE, MIN(interaction_time), MAX(interaction_time)) as duration_minutes
        FROM `$table_name`
        WHERE " . $period_info['previous'] . $user_type_condition . "
        GROUP BY session_id";
    
    // Get current period data
    $current_durations = $wpdb->get_results($current_query);
    
    // Get previous period data
    $previous_durations = $wpdb->get_results($previous_query);
    
    // Calculate statistics for current period
    $current_durations_array = array_column($current_durations, 'duration_minutes');
    $current_stats = array(
        'avg_duration' => count($current_durations_array) > 0 ? array_sum($current_durations_array) / count($current_durations_array) : 0
    );
    
    // Calculate statistics for previous period
    $previous_durations_array = array_column($previous_durations, 'duration_minutes');
    $previous_stats = array(
        'avg_duration' => count($previous_durations_array) > 0 ? array_sum($previous_durations_array) / count($previous_durations_array) : 0
    );
    
    return array(
        'current' => $current_stats,
        'previous' => $previous_stats,
        'current_period_label' => $period_info['current_label'],
        'previous_period_label' => $period_info['previous_label']
    );

}

// Compute token statistics with period comparison
function kognetiks_analytics_get_token_statistics($period = 'Today', $user_type = 'All') {

    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';
    
    // Define period ranges (same as above)
    $periods = array(
        'Today' => array(
            'current' => "DATE(interaction_time) = CURDATE()",
            'previous' => "DATE(interaction_time) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)",
            'current_label' => 'Today',
            'previous_label' => 'Yesterday'
        ),
        'Week' => array(
            'current' => "YEARWEEK(interaction_time) = YEARWEEK(CURDATE())",
            'previous' => "YEARWEEK(interaction_time) = YEARWEEK(DATE_SUB(CURDATE(), INTERVAL 1 WEEK))",
            'current_label' => 'This Week',
            'previous_label' => 'Last Week'
        ),
        'Month' => array(
            'current' => "DATE_FORMAT(interaction_time, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')",
            'previous' => "DATE_FORMAT(interaction_time, '%Y-%m') = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m')",
            'current_label' => 'This Month',
            'previous_label' => 'Last Month'
        ),
        'Quarter' => array(
            'current' => "QUARTER(interaction_time) = QUARTER(CURDATE()) AND YEAR(interaction_time) = YEAR(CURDATE())",
            'previous' => "QUARTER(interaction_time) = QUARTER(DATE_SUB(CURDATE(), INTERVAL 3 MONTH)) AND YEAR(interaction_time) = YEAR(DATE_SUB(CURDATE(), INTERVAL 3 MONTH))",
            'current_label' => 'This Quarter',
            'previous_label' => 'Last Quarter'
        ),
        'Year' => array(
            'current' => "YEAR(interaction_time) = YEAR(CURDATE())",
            'previous' => "YEAR(interaction_time) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 YEAR))",
            'current_label' => 'This Year',
            'previous_label' => 'Last Year'
        )
    );
    
    $period_info = $periods[$period];
    
    // Add user type filter condition
    $user_type_condition = '';
    if ($user_type !== 'All') {
        $user_type_condition = " AND user_type = '" . esc_sql($user_type) . "'";
    }
    
    // DIAG - Diagnostics - V1.0.0
    $current_query = "
        SELECT SUM(CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(message_text, 'total_tokens:', -1), ' prompt_tokens:', 1) AS UNSIGNED))
        FROM `$table_name`
        WHERE " . $period_info['current'] . $user_type_condition . " AND user_type = 'Total Tokens'";
    
    $previous_query = "
        SELECT SUM(CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(message_text, 'total_tokens:', -1), ' prompt_tokens:', 1) AS UNSIGNED))
        FROM `$table_name`
        WHERE " . $period_info['previous'] . $user_type_condition . " AND user_type = 'Total Tokens'";
    
    // Get current period data
    $current_data = array(
        'total_tokens' => $wpdb->get_var($current_query)
    );
    
    // Get previous period data
    $previous_data = array(
        'total_tokens' => $wpdb->get_var($previous_query)
    );
    
    return array(
        'current' => $current_data,
        'previous' => $previous_data,
        'current_period_label' => $period_info['current_label'],
        'previous_period_label' => $period_info['previous_label']
    );

}

// Compute visitor statistics
function kognetiks_analytics_get_visitor_statistics($period = 'Today', $user_type = 'All') {

    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';
    
    // Define period ranges (same as above)
    $periods = array(
        'Today' => array(
            'current' => "DATE(interaction_time) = CURDATE()",
            'previous' => "DATE(interaction_time) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)",
            'current_label' => 'Today',
            'previous_label' => 'Yesterday'
        ),
        'Week' => array(
            'current' => "YEARWEEK(interaction_time) = YEARWEEK(CURDATE())",
            'previous' => "YEARWEEK(interaction_time) = YEARWEEK(DATE_SUB(CURDATE(), INTERVAL 1 WEEK))",
            'current_label' => 'This Week',
            'previous_label' => 'Last Week'
        ),
        'Month' => array(
            'current' => "DATE_FORMAT(interaction_time, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')",
            'previous' => "DATE_FORMAT(interaction_time, '%Y-%m') = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m')",
            'current_label' => 'This Month',
            'previous_label' => 'Last Month'
        ),
        'Quarter' => array(
            'current' => "QUARTER(interaction_time) = QUARTER(CURDATE()) AND YEAR(interaction_time) = YEAR(CURDATE())",
            'previous' => "QUARTER(interaction_time) = QUARTER(DATE_SUB(CURDATE(), INTERVAL 3 MONTH)) AND YEAR(interaction_time) = YEAR(DATE_SUB(CURDATE(), INTERVAL 3 MONTH))",
            'current_label' => 'This Quarter',
            'previous_label' => 'Last Quarter'
        ),
        'Year' => array(
            'current' => "YEAR(interaction_time) = YEAR(CURDATE())",
            'previous' => "YEAR(interaction_time) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 YEAR))",
            'current_label' => 'This Year',
            'previous_label' => 'Last Year'
        )
    );
    
    $period_info = $periods[$period];
    
    // Add user type filter condition
    $user_type_condition = '';
    if ($user_type !== 'All') {
        $user_type_condition = " AND user_type = '" . esc_sql($user_type) . "'";
    }
    
    $current_query = "
        SELECT 
            COUNT(DISTINCT session_id) as total_visitors,
            COUNT(DISTINCT CASE WHEN visit_count = 1 THEN session_id END) as new_visitors,
            COUNT(DISTINCT CASE WHEN visit_count > 1 THEN session_id END) as returning_visitors
        FROM (
            SELECT 
                session_id,
                COUNT(DISTINCT DATE(interaction_time)) as visit_count
            FROM $table_name
            WHERE " . $period_info['current'] . $user_type_condition . "
            GROUP BY session_id
        ) as visitor_stats";
    
    // Get previous period data
    $previous_query = "
        SELECT 
            COUNT(DISTINCT session_id) as total_visitors,
            COUNT(DISTINCT CASE WHEN visit_count = 1 THEN session_id END) as new_visitors,
            COUNT(DISTINCT CASE WHEN visit_count > 1 THEN session_id END) as returning_visitors
        FROM (
            SELECT 
                session_id,
                COUNT(DISTINCT DATE(interaction_time)) as visit_count
            FROM $table_name
            WHERE " . $period_info['previous'] . $user_type_condition . "
            GROUP BY session_id
        ) as visitor_stats";
    
    // Get current period data
    $current_data = $wpdb->get_row($current_query);
    
    // Get previous period data
    $previous_data = $wpdb->get_row($previous_query);
    
    return array(
        'current' => array(
            'total_visitors' => $current_data->total_visitors ?? 0,
            'new_visitors' => $current_data->new_visitors ?? 0,
            'returning_visitors' => $current_data->returning_visitors ?? 0
        ),
        'previous' => array(
            'total_visitors' => $previous_data->total_visitors ?? 0,
            'new_visitors' => $previous_data->new_visitors ?? 0,
            'returning_visitors' => $previous_data->returning_visitors ?? 0
        ),
        'current_period_label' => $period_info['current_label'],
        'previous_period_label' => $period_info['previous_label']
    );
}

// Compute engagement statistics
function kognetiks_analytics_get_engagement_statistics($period = 'Today', $user_type = 'All') {

    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';
    
    // Define period ranges (same as above)
    $periods = array(
        'Today' => array(
            'current' => "DATE(interaction_time) = CURDATE()",
            'previous' => "DATE(interaction_time) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)",
            'current_label' => 'Today',
            'previous_label' => 'Yesterday'
        ),
        'Week' => array(
            'current' => "YEARWEEK(interaction_time) = YEARWEEK(CURDATE())",
            'previous' => "YEARWEEK(interaction_time) = YEARWEEK(DATE_SUB(CURDATE(), INTERVAL 1 WEEK))",
            'current_label' => 'This Week',
            'previous_label' => 'Last Week'
        ),
        'Month' => array(
            'current' => "DATE_FORMAT(interaction_time, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')",
            'previous' => "DATE_FORMAT(interaction_time, '%Y-%m') = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m')",
            'current_label' => 'This Month',
            'previous_label' => 'Last Month'
        ),
        'Quarter' => array(
            'current' => "QUARTER(interaction_time) = QUARTER(CURDATE()) AND YEAR(interaction_time) = YEAR(CURDATE())",
            'previous' => "QUARTER(interaction_time) = QUARTER(DATE_SUB(CURDATE(), INTERVAL 3 MONTH)) AND YEAR(interaction_time) = YEAR(DATE_SUB(CURDATE(), INTERVAL 3 MONTH))",
            'current_label' => 'This Quarter',
            'previous_label' => 'Last Quarter'
        ),
        'Year' => array(
            'current' => "YEAR(interaction_time) = YEAR(CURDATE())",
            'previous' => "YEAR(interaction_time) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 YEAR))",
            'current_label' => 'This Year',
            'previous_label' => 'Last Year'
        )
    );
    
    $period_info = $periods[$period];
    
    // Add user type filter condition
    $user_type_condition = '';
    if ($user_type !== 'All') {
        $user_type_condition = " AND user_type = '" . esc_sql($user_type) . "'";
    }
    
    // Get current period engagement data
    $current_query = "
        SELECT 
            session_id,
            COUNT(*) as message_count,
            TIMESTAMPDIFF(MINUTE, MIN(interaction_time), MAX(interaction_time)) as duration_minutes
        FROM `$table_name`
        WHERE " . $period_info['current'] . $user_type_condition . "
        GROUP BY session_id";
    
    $current_engagement = $wpdb->get_results($current_query);
    
    // Get previous period engagement data
    $previous_query = "
        SELECT 
            session_id,
            COUNT(*) as message_count,
            TIMESTAMPDIFF(MINUTE, MIN(interaction_time), MAX(interaction_time)) as duration_minutes
        FROM `$table_name`
        WHERE " . $period_info['previous'] . $user_type_condition . "
        GROUP BY session_id";
    
    $previous_engagement = $wpdb->get_results($previous_query);
    
    // Calculate high engagement rate for current period
    $current_high_engagement = array_filter($current_engagement, function($session) {
        return $session->message_count >= 5 || $session->duration_minutes >= 5;
    });
    $current_high_engagement_rate = count($current_engagement) > 0 
        ? (count($current_high_engagement) / count($current_engagement)) * 100 
        : 0;
    
    // Calculate high engagement rate for previous period
    $previous_high_engagement = array_filter($previous_engagement, function($session) {
        return $session->message_count >= 5 || $session->duration_minutes >= 5;
    });
    $previous_high_engagement_rate = count($previous_engagement) > 0 
        ? (count($previous_high_engagement) / count($previous_engagement)) * 100 
        : 0;
    
    // Calculate average messages before drop-off for current period
    $current_avg_messages = count($current_engagement) > 0 
        ? array_sum(array_column($current_engagement, 'message_count')) / count($current_engagement)
        : 0;
    
    // Calculate average messages before drop-off for previous period
    $previous_avg_messages = count($previous_engagement) > 0 
        ? array_sum(array_column($previous_engagement, 'message_count')) / count($previous_engagement)
        : 0;
    
    return array(
        'current' => array(
            'high_engagement_rate' => $current_high_engagement_rate,
            'avg_messages_before_dropoff' => $current_avg_messages
        ),
        'previous' => array(
            'high_engagement_rate' => $previous_high_engagement_rate,
            'avg_messages_before_dropoff' => $previous_avg_messages
        ),
        'current_period_label' => $period_info['current_label'],
        'previous_period_label' => $period_info['previous_label']
    );

}

// Compute sentiment statistics
function kognetiks_analytics_get_sentiment_statistics($period = 'Today', $user_type = 'All') {
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';
    
    // Define period ranges (same as above)
    $periods = array(
        'Today' => array(
            'current' => "DATE(interaction_time) = CURDATE()",
            'previous' => "DATE(interaction_time) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)",
            'current_label' => 'Today',
            'previous_label' => 'Yesterday'
        ),
        'Week' => array(
            'current' => "YEARWEEK(interaction_time) = YEARWEEK(CURDATE())",
            'previous' => "YEARWEEK(interaction_time) = YEARWEEK(DATE_SUB(CURDATE(), INTERVAL 1 WEEK))",
            'current_label' => 'This Week',
            'previous_label' => 'Last Week'
        ),
        'Month' => array(
            'current' => "DATE_FORMAT(interaction_time, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')",
            'previous' => "DATE_FORMAT(interaction_time, '%Y-%m') = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m')",
            'current_label' => 'This Month',
            'previous_label' => 'Last Month'
        ),
        'Quarter' => array(
            'current' => "QUARTER(interaction_time) = QUARTER(CURDATE()) AND YEAR(interaction_time) = YEAR(CURDATE())",
            'previous' => "QUARTER(interaction_time) = QUARTER(DATE_SUB(CURDATE(), INTERVAL 3 MONTH)) AND YEAR(interaction_time) = YEAR(DATE_SUB(CURDATE(), INTERVAL 3 MONTH))",
            'current_label' => 'This Quarter',
            'previous_label' => 'Last Quarter'
        ),
        'Year' => array(
            'current' => "YEAR(interaction_time) = YEAR(CURDATE())",
            'previous' => "YEAR(interaction_time) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 YEAR))",
            'current_label' => 'This Year',
            'previous_label' => 'Last Year'
        )
    );
    
    $period_info = $periods[$period];
    
    // Add user type filter condition
    $user_type_condition = '';
    if ($user_type !== 'All') {
        $user_type_condition = " AND user_type = '" . esc_sql($user_type) . "'";
    }
    
    // Get current period sentiment data
    $current_query = "
        SELECT 
            AVG(CAST(sentiment_score AS DECIMAL(10,2))) as avg_score,
            COUNT(CASE WHEN CAST(sentiment_score AS DECIMAL(10,2)) > 0 THEN 1 END) * 100.0 / COUNT(*) as positive_percent
        FROM `$table_name`
        WHERE " . $period_info['current'] . $user_type_condition . "
        AND sentiment_score IS NOT NULL
        AND sentiment_score != ''";
    
    $current_data = $wpdb->get_row($current_query);
    
    // Get previous period sentiment data
    $previous_query = "
        SELECT 
            AVG(CAST(sentiment_score AS DECIMAL(10,2))) as avg_score,
            COUNT(CASE WHEN CAST(sentiment_score AS DECIMAL(10,2)) > 0 THEN 1 END) * 100.0 / COUNT(*) as positive_percent
        FROM `$table_name`
        WHERE " . $period_info['previous'] . $user_type_condition . "
        AND sentiment_score IS NOT NULL
        AND sentiment_score != ''";
    
    $previous_data = $wpdb->get_row($previous_query);
    
    return array(
        'current' => array(
            'avg_score' => $current_data->avg_score ?? 0,
            'positive_percent' => $current_data->positive_percent ?? 0
        ),
        'previous' => array(
            'avg_score' => $previous_data->avg_score ?? 0,
            'positive_percent' => $previous_data->positive_percent ?? 0
        ),
        'current_period_label' => $period_info['current_label'],
        'previous_period_label' => $period_info['previous_label']
    );

}

// Helper function to calculate the statistics median
function kognetiks_analytics_median_computation($numbers) {

    sort($numbers);
    $count = count($numbers);
    $middle = floor($count / 2);
    
    if ($count % 2 == 0) {
        return ($numbers[$middle - 1] + $numbers[$middle]) / 2;
    } else {
        return $numbers[$middle];
    }

}
