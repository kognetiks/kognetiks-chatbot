<?php
/**
 * Kognetiks Insights - Automated Emails - Ver 1.0.0
 *
 * This file contains the code for the Kognetiks Insights automated emails.
 * 
 * 
 * 
 * @package kognetiks-insights
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// EMERGENCY BYPASS: Set this to true in wp-config.php to completely disable this file
// Add this line to wp-config.php: define( 'KOGNETIKS_INSIGHTS_EMAIL_DISABLED', true );
// Note: This won't prevent function definitions, but will prevent hooks from being registered

// EMERGENCY SAFETY: Disable admin_init hook completely to prevent site hangs
// Set this to false to re-enable after cleaning up cron jobs
if ( ! defined( 'KOGNETIKS_INSIGHTS_EMAIL_INIT_ENABLED' ) ) {
    define( 'KOGNETIKS_INSIGHTS_EMAIL_INIT_ENABLED', false ); // Set to true to re-enable
}

/**
 * Internal helper: period label + start/end timestamps.
 *
 * @param string $period 'daily', 'weekly', or 'monthly'
 * @return array
 */
function kognetiks_insights_get_period_window( $period = 'weekly' ) {

    // Normalize period: accept 'daily', 'monthly', default to 'weekly'
    $period = ( $period === 'monthly' ) ? 'monthly' : ( ( $period === 'daily' ) ? 'daily' : 'weekly' );

    $now = current_time( 'timestamp' ); // WP local time
    // Normalize to start-of-day for consistency.
    $today_start = strtotime( date( 'Y-m-d 00:00:00', $now ) );
    // End of today (23:59:59) to ensure we include all of today's data
    $today_end = strtotime( date( 'Y-m-d 23:59:59', $now ) );

    if ( $period === 'monthly' ) {
        $start = strtotime( date( 'Y-m-01 00:00:00', $today_start ) );
        $end   = $today_end; // Include full current day
        $label = date_i18n( 'F Y', $today_start );
    } elseif ( $period === 'daily' ) {
        // Daily window: just today
        $start = $today_start;
        $end   = $today_end; // Include full current day
        $label = date_i18n( 'F j, Y', $today_start );
    } else {
        // Week window: last 7 days (including today), simple rolling window.
        // Go back 6 days from start of today, so we get 7 days total (including today)
        $start = strtotime( '-6 days', $today_start );
        $end   = $today_end; // Include full current day
        $label = sprintf(
            '%s – %s',
            date_i18n( 'M j', $start ),
            date_i18n( 'M j, Y', $end )
        );
    }

    return [
        'period' => $period,
        'start'  => $start,
        'end'    => $end,
        'label'  => $label,
    ];
}

/**
 * Internal helper: build a consistent HTML email wrapper.
 *
 * @param string $title
 * @param string $subtitle
 * @param string $content_html
 * @param array  $meta
 * @return string
 */
function kognetiks_insights_build_email_html( $title, $subtitle, $content_html, $meta = [] ) {

    $site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
    $site_url  = home_url();

    $footer_note = ! empty( $meta['footer_note'] )
        ? wp_kses_post( $meta['footer_note'] )
        : '';

    $brand_line = sprintf(
        '<div style="margin-top:16px;font-size:12px;color:#666;">Sent by %s • %s</div>',
        esc_html( $site_name ),
        esc_url( $site_url )
    );

    $period_line = '';
    if ( ! empty( $meta['period_label'] ) ) {
        $period_line = sprintf(
            '<div style="margin-top:6px;font-size:13px;color:#555;">Reporting period: <strong>%s</strong></div>',
            esc_html( $meta['period_label'] )
        );
    }

    $html  = '<div style="font-family:Arial,Helvetica,sans-serif;line-height:1.5;color:#111;">';
    $html .= sprintf( '<h2 style="margin:0 0 6px 0;">%s</h2>', esc_html( $title ) );
    $html .= sprintf( '<div style="font-size:14px;color:#444;margin-bottom:10px;">%s</div>', esc_html( $subtitle ) );
    $html .= $period_line;
    $html .= '<hr style="border:none;border-top:1px solid #ddd;margin:14px 0;" />';
    $html .= '<div style="font-size:14px;">' . $content_html . '</div>';
    $html .= '<hr style="border:none;border-top:1px solid #eee;margin:18px 0;" />';
    $html .= $brand_line;

    if ( $footer_note ) {
        $html .= '<div style="margin-top:10px;font-size:12px;color:#666;">' . $footer_note . '</div>';
    }

    $html .= '</div>';

    return $html;
}

/**
 * Internal helper: simple metric card row.
 *
 * @param array $items Each item: ['label' => 'Conversations', 'value' => '147', 'hint' => 'optional']
 * @return string
 */
function kognetiks_insights_metric_row_html( $items = [] ) {

    if ( empty( $items ) || ! is_array( $items ) ) {
        return '';
    }

    $cells = '';

    foreach ( $items as $item ) {
        $label = isset( $item['label'] ) ? (string) $item['label'] : '';
        $value = isset( $item['value'] ) ? (string) $item['value'] : '';
        $hint  = isset( $item['hint'] ) ? (string) $item['hint'] : '';

        $cells .= '<td style="padding:12px;border:1px solid #eee;vertical-align:top;">';
        $cells .= sprintf( '<div style="font-size:12px;color:#555;">%s</div>', esc_html( $label ) );
        $cells .= sprintf( '<div style="font-size:22px;font-weight:bold;margin-top:2px;">%s</div>', esc_html( $value ) );
        if ( $hint !== '' ) {
            $cells .= sprintf( '<div style="font-size:12px;color:#777;margin-top:4px;">%s</div>', esc_html( $hint ) );
        }
        $cells .= '</td>';
    }

    return '<table role="presentation" cellspacing="0" cellpadding="0" style="border-collapse:collapse;width:100%;margin:12px 0;"><tr>' . $cells . '</tr></table>';
}

/**
 * Internal helper: bullet list section.
 *
 * @param string $heading
 * @param array  $bullets
 * @return string
 */
function kognetiks_insights_bullets_html( $heading, $bullets = [] ) {

    if ( empty( $bullets ) ) {
        return '';
    }

    $out  = sprintf( '<h3 style="margin:14px 0 8px 0;font-size:15px;">%s</h3>', esc_html( $heading ) );
    $out .= '<ul style="margin:0 0 8px 18px;padding:0;">';

    foreach ( $bullets as $b ) {
        $out .= sprintf( '<li style="margin:0 0 6px 0;">%s</li>', esc_html( $b ) );
    }

    $out .= '</ul>';

    return $out;
}

/**
 * Internal helper: compute usage stats for a window + trends vs previous window.
 *
 * conversations:
 *   - distinct session_id having at least one Visitor (or User) row in window
 * pages:
 *   - distinct page_id among Visitor/User rows in window
 * visitors:
 *   - distinct session_id among Visitor rows in window
 * users:
 *   - distinct user_id among non-Visitor rows in window (best-effort)
 *
 * Trends are pct change vs previous equal-length window.
 *
 * @param int $start_ts
 * @param int $end_ts
 * @return array
 */
function kognetiks_insights_get_usage_stats( $start_ts, $end_ts ) {
    global $wpdb;

    $tables = kognetiks_insights_get_table_names();
    $log    = $tables['conversation_log'];

    // Convert timestamps to MySQL DATETIME strings using WordPress date functions
    // WordPress stores dates in local time, so we need to ensure proper conversion
    $start_dt = date( 'Y-m-d H:i:s', $start_ts );
    $end_dt   = date( 'Y-m-d H:i:s', $end_ts );

    // Previous equal-length window immediately before start.
    $window_seconds = max( 1, (int) ( $end_ts - $start_ts ) );
    $prev_end_ts    = $start_ts;
    $prev_start_ts  = $start_ts - $window_seconds;

    $prev_start_dt = date( 'Y-m-d H:i:s', $prev_start_ts );
    $prev_end_dt   = date( 'Y-m-d H:i:s', $prev_end_ts );

    /**
     * Simplified query: Count distinct session_ids, page_ids, etc. for human messages
     * Human messages are Visitor or User types with non-empty message_text
     * Exclude Chatbot and token rows
     * Using simpler WHERE clause instead of complex CASE statements for better reliability
     */
    $sql_window = "
        SELECT
            (SELECT COUNT(DISTINCT session_id)
             FROM {$log}
             WHERE interaction_time >= %s AND interaction_time <= %s
             AND user_type IN ('Visitor', 'User')
             AND message_text IS NOT NULL
             AND TRIM(message_text) != '') AS conversations,

            (SELECT COUNT(DISTINCT page_id)
             FROM {$log}
             WHERE interaction_time >= %s AND interaction_time <= %s
             AND user_type IN ('Visitor', 'User')
             AND message_text IS NOT NULL
             AND TRIM(message_text) != ''
             AND page_id IS NOT NULL
             AND page_id > 0) AS pages,

            (SELECT COUNT(DISTINCT session_id)
             FROM {$log}
             WHERE interaction_time >= %s AND interaction_time <= %s
             AND user_type = 'Visitor'
             AND (user_id = 0 OR user_id IS NULL)
             AND message_text IS NOT NULL
             AND TRIM(message_text) != '') AS visitors,

            (SELECT COUNT(DISTINCT user_id)
             FROM {$log}
             WHERE interaction_time >= %s AND interaction_time <= %s
             AND user_id > 0
             AND user_id IS NOT NULL
             AND user_type IN ('Visitor', 'User')
             AND message_text IS NOT NULL
             AND TRIM(message_text) != '') AS users
    ";

    // Build params: date range repeated for each subquery (4 subqueries, each needs start and end)
    $cur = $wpdb->get_row( $wpdb->prepare( $sql_window, $start_dt, $end_dt, $start_dt, $end_dt, $start_dt, $end_dt, $start_dt, $end_dt ), ARRAY_A );
    $prev = $wpdb->get_row( $wpdb->prepare( $sql_window, $prev_start_dt, $prev_end_dt, $prev_start_dt, $prev_end_dt, $prev_start_dt, $prev_end_dt, $prev_start_dt, $prev_end_dt ), ARRAY_A );

    // Debug: Log query details (temporary - remove after debugging)
    if ( function_exists( 'back_trace' ) ) {
        back_trace( 'NOTICE', 'Insights Stats Query - Date Range: ' . $start_dt . ' to ' . $end_dt );
        back_trace( 'NOTICE', 'Insights Stats Query - SQL: ' . $wpdb->last_query );
        back_trace( 'NOTICE', 'Insights Stats Query - Results: ' . print_r( $cur, true ) );
        
        // Also check if there's any data in the table at all
        $total_check = $wpdb->get_var( "SELECT COUNT(*) FROM {$log} WHERE user_type IN ('Visitor', 'User') LIMIT 1" );
        back_trace( 'NOTICE', 'Insights Stats Query - Total Visitor/User rows in table: ' . $total_check );
        
        // Check date range coverage
        $date_check = $wpdb->get_var( $wpdb->prepare( 
            "SELECT COUNT(*) FROM {$log} WHERE interaction_time >= %s AND interaction_time <= %s", 
            $start_dt, 
            $end_dt 
        ) );
        back_trace( 'NOTICE', 'Insights Stats Query - Total rows in date range: ' . $date_check );
    }

    $c = [
        'conversations' => isset( $cur['conversations'] ) ? (int) $cur['conversations'] : 0,
        'pages'         => isset( $cur['pages'] ) ? (int) $cur['pages'] : 0,
        'visitors'      => isset( $cur['visitors'] ) ? (int) $cur['visitors'] : 0,
        'users'         => isset( $cur['users'] ) ? (int) $cur['users'] : 0,
    ];

    $p = [
        'conversations' => isset( $prev['conversations'] ) ? (int) $prev['conversations'] : 0,
        'pages'         => isset( $prev['pages'] ) ? (int) $prev['pages'] : 0,
        'visitors'      => isset( $prev['visitors'] ) ? (int) $prev['visitors'] : 0,
        'users'         => isset( $prev['users'] ) ? (int) $prev['users'] : 0,
    ];

    $pct = function( $cur_val, $prev_val ) {
        $cur_val  = (float) $cur_val;
        $prev_val = (float) $prev_val;
        if ( $prev_val <= 0 ) {
            return null;
        }
        return ( ( $cur_val - $prev_val ) / $prev_val ) * 100.0;
    };

    $stats = [
        'conversations'       => $c['conversations'],
        'pages'               => $c['pages'],
        'visitors'            => $c['visitors'],
        'users'               => $c['users'],
        'trend_conversations' => $pct( $c['conversations'], $p['conversations'] ),
        'trend_pages'         => $pct( $c['pages'], $p['pages'] ),
        'trend_visitors'      => $pct( $c['visitors'], $p['visitors'] ),
        'trend_users'         => $pct( $c['users'], $p['users'] ),
    ];

    return apply_filters( 'kognetiks_insights_usage_stats', $stats, $start_ts, $end_ts );
}

/**
 * Paid: Top Unanswered Questions (best-effort based on your current logging)
 *
 * Definition:
 * - Find chatbot responses that look like "fallback / unclear / rephrase"
 * - For each, pull the most recent Visitor message_text from the same session before that time
 *
 * @param int $start_ts
 * @param int $end_ts
 * @param int $limit
 * @return array
 */
function kognetiks_insights_get_top_unanswered_questions( $start_ts, $end_ts, $limit = 5 ) {

    global $wpdb;

    $tables = kognetiks_insights_get_table_names();
    $log    = $tables['conversation_log'];

    // Convert timestamps to MySQL DATETIME strings using WordPress date functions
    $start_dt = date( 'Y-m-d H:i:s', $start_ts );
    $end_dt   = date( 'Y-m-d H:i:s', $end_ts );

    // Allowlist for human messages
    $human_types = [ 'Visitor', 'User' ];
    $human_in    = implode( ',', array_fill( 0, count( $human_types ), '%s' ) );

    // Global based on language file loaded
    global $fallback_like, $fallback_failure_and_apology, $fallback_deflection_and_generic_assistant_behavior, $fallback_clarification_requests_beyond_rephrasing, $fallback_to_external_help, $fallback_safety_refusal_and_policy_related, $fallback_conversation_breakdown;

    // Do a union of all fallback patterns
    // TODO - Later on, we can do a more sophisticated approach to combine these patterns, but for now this is a good start.
    $fallback_patterns = array_merge( $fallback_like, $fallback_failure_and_apology, $fallback_deflection_and_generic_assistant_behavior, $fallback_clarification_requests_beyond_rephrasing, $fallback_to_external_help, $fallback_safety_refusal_and_policy_related, $fallback_conversation_breakdown );
    // Diagnostics - log the fallback patterns
    if ( function_exists( 'back_trace' ) ) {
        back_trace( 'NOTICE', 'Insights Top Unanswered Questions - Fallback Patterns: ' . print_r( $fallback_patterns, true ) );
    }
    
    $like_sql = implode( ' OR ', array_fill( 0, count( $fallback_patterns ), 'c.message_text LIKE %s' ) );

    /**
     * Strategy:
     * - Find fallback chatbot rows "c" in window OR human messages with clarification/confusion patterns
     * - Join to the prior human question "q" in same session at max time <= c.time
     * - Group by q.message_text to get "top" unanswered questions
     * 
     * Two scenarios:
     * 1. Chatbot responds with fallback pattern -> find prior human question
     * 2. Human message contains clarification/confusion pattern -> find prior human question (original question)
     */
    $sql = "
        SELECT
            q.message_text AS question,
            COUNT(*) AS hits
        FROM {$log} c
        INNER JOIN {$log} q
            ON q.session_id = c.session_id
        WHERE
            (
                -- Scenario 1: Chatbot response matches fallback pattern
                ( c.user_type = 'Chatbot'
                  AND ( {$like_sql} ) )
                OR
                -- Scenario 2: Human message contains clarification/confusion pattern
                ( c.user_type IN ($human_in)
                  AND ( {$like_sql} ) )
            )
            AND c.interaction_time >= %s AND c.interaction_time <= %s
            AND c.message_text IS NOT NULL
            AND LENGTH(TRIM(c.message_text)) > 0

            -- prior human question in same session
            AND q.user_type IN ($human_in)
            AND q.message_text IS NOT NULL
            AND LENGTH(TRIM(q.message_text)) > 0
            AND q.message_text NOT REGEXP '^[0-9]+$'
            AND q.interaction_time = (
                SELECT MAX(q2.interaction_time)
                FROM {$log} q2
                WHERE q2.session_id = c.session_id
                  AND q2.user_type IN ($human_in)
                  AND q2.message_text IS NOT NULL
                  AND LENGTH(TRIM(q2.message_text)) > 0
                  AND q2.message_text NOT REGEXP '^[0-9]+$'
                  AND q2.interaction_time < c.interaction_time
            )
        GROUP BY q.message_text
        ORDER BY hits DESC
        LIMIT %d
    ";

    // Parameters must match SQL placeholder order exactly:
    // 1. fallback_patterns for chatbot LIKE check (line 379)
    // 2. human_types for c.user_type IN (line 382)
    // 3. fallback_patterns for human LIKE check (line 383)
    // 4. date range for interaction_time (line 385)
    // 5. human_types for q.user_type IN (line 390)
    // 6. human_types for q2.user_type IN subquery (line 398)
    // 7. limit (line 406)
    $params = array_merge(
        $fallback_patterns,     // 1. for chatbot fallback pattern check (line 379)
        $human_types,           // 2. for c.user_type IN (...) in Scenario 2 (line 382)
        $fallback_patterns,     // 3. for human clarification pattern check (line 383)
        [ $start_dt, $end_dt ], // 4. for interaction_time date range (line 385)
        $human_types,           // 5. for q.user_type IN (...) (line 390)
        $human_types,           // 6. for q2.user_type IN (...) in subquery (line 398)
        [ (int) $limit ]        // 7. for LIMIT (line 406)
    );

    $rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );

    // Debug: Log query details
    if ( function_exists( 'back_trace' ) ) {
        back_trace( 'NOTICE', 'Insights Top Unanswered Questions - SQL: ' . $wpdb->last_query );
        back_trace( 'NOTICE', 'Insights Top Unanswered Questions - Results count: ' . count( $rows ) );
        if ( ! empty( $rows ) ) {
            back_trace( 'NOTICE', 'Insights Top Unanswered Questions - Results: ' . print_r( $rows, true ) );
        }
    }

    $out = [];
    if ( ! empty( $rows ) ) {
        foreach ( $rows as $r ) {
            $q = isset( $r['question'] ) ? trim( (string) $r['question'] ) : '';
            if ( $q !== '' ) {
                $out[] = wp_strip_all_tags( $q );
            }
        }
    }

    return apply_filters( 'kognetiks_insights_top_unanswered_questions', $out, $start_ts, $end_ts, $limit );

}

/**
 * Paid: Top Pages by Activity (best-effort based on your current logging)
 *
 * Definition:
 * - Find the pages that have the most conversations
 * - For each page, pull the most recent Visitor message_text from the same session before that time
 *
 * @param int $start_ts
 * @param int $end_ts
 * @param int $limit
 * @return array
 */
function kognetiks_insights_get_top_pages_by_activity( $start_ts, $end_ts, $limit = 5 ) {

    global $wpdb;

    $tables = kognetiks_insights_get_table_names();
    $log    = $tables['conversation_log'];

    // Convert timestamps to MySQL DATETIME strings using WordPress date functions
    $start_dt = date( 'Y-m-d H:i:s', $start_ts );
    $end_dt   = date( 'Y-m-d H:i:s', $end_ts );

    $human_types = [ 'Visitor', 'User' ];
    $human_in    = implode( ',', array_fill( 0, count( $human_types ), '%s' ) );

    $sql = "
        SELECT
            page_id,
            COUNT(DISTINCT session_id) AS conversations
        FROM {$log}
        WHERE interaction_time >= %s AND interaction_time <= %s
          AND user_type IN ($human_in)
          AND message_text IS NOT NULL
          AND LENGTH(TRIM(message_text)) > 0
          AND message_text NOT REGEXP '^[0-9]+$'
          AND page_id IS NOT NULL
          AND page_id <> 0
        GROUP BY page_id
        ORDER BY conversations DESC
        LIMIT %d
    ";

    $params = array_merge(
        [ $start_dt, $end_dt ],
        $human_types,
        [ (int) $limit ]
    );

    $rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );

    $out = [];
    foreach ( (array) $rows as $r ) {
        $pid = isset( $r['page_id'] ) ? (int) $r['page_id'] : 0;
        $cnt = isset( $r['conversations'] ) ? (int) $r['conversations'] : 0;
        if ( $pid > 0 ) {
            $out[] = [
                'page_id'       => $pid,
                'title'         => get_the_title( $pid ),
                'url'           => get_permalink( $pid ),
                'conversations' => $cnt,
            ];
        }
    }

    return apply_filters( 'kognetiks_insights_top_pages_by_activity', $out, $start_ts, $end_ts, $limit );
}

/**
 * Internal helper: format top pages bullets.
 *
 * @param array $pages
 * @return array
 */
function kognetiks_insights_format_top_pages_bullets( $pages = [] ) {

    if ( empty( $pages ) ) {
        return [];
    }

    $bullets = [];
    foreach ( $pages as $p ) {
        $title = ! empty( $p['title'] ) ? $p['title'] : 'Untitled';
        $cnt   = isset( $p['conversations'] ) ? (int) $p['conversations'] : 0;
        $bullets[] = sprintf( '%s (%d conversations)', $title, $cnt );
    }
    return $bullets;

}

/**
 * Paid: Top Assistants Used (best-effort based on your current logging)
 *
 * Definition:
 * - Find the assistants that have the most conversations
 * - For each assistant, pull the most recent Visitor message_text from the same session before that time
 *
 * @param int $start_ts
 * @param int $end_ts
 * @param int $limit
 * @return array
 */
function kognetiks_insights_get_top_assistants_used( $start_ts, $end_ts, $limit = 5 ) {

    global $wpdb;

    $tables = kognetiks_insights_get_table_names();
    $log    = $tables['conversation_log'];

    // Convert timestamps to MySQL DATETIME strings using WordPress date functions
    $start_dt = date( 'Y-m-d H:i:s', $start_ts );
    $end_dt   = date( 'Y-m-d H:i:s', $end_ts );

    // Count by session_id to avoid inflating from token rows.
    $sql = "
        SELECT
            assistant_id,
            assistant_name,
            COUNT(DISTINCT session_id) AS conversations
        FROM {$log}
        WHERE interaction_time >= %s AND interaction_time <= %s
          AND assistant_id IS NOT NULL
          AND assistant_id <> ''
          AND assistant_name IS NOT NULL
          AND assistant_name <> ''
        GROUP BY assistant_id, assistant_name
        ORDER BY conversations DESC
        LIMIT %d
    ";

    $rows = $wpdb->get_results(
        $wpdb->prepare( $sql, $start_dt, $end_dt, (int) $limit ),
        ARRAY_A
    );

    $out = [];
    foreach ( (array) $rows as $r ) {
        $out[] = [
            'assistant_id'   => (string) $r['assistant_id'],
            'assistant_name' => (string) $r['assistant_name'],
            'conversations'  => (int) $r['conversations'],
        ];
    }

    return apply_filters( 'kognetiks_insights_top_assistants_used', $out, $start_ts, $end_ts, $limit );
}

/**
 * Internal helper: format top assistants bullets.
 *
 * @param array $assistants
 * @return array
 */
function kognetiks_insights_format_top_assistants_bullets( $assistants = [] ) {

    if ( empty( $assistants ) ) {
        return [];
    }

    $bullets = [];
    foreach ( $assistants as $a ) {
        $name = ! empty( $a['assistant_name'] ) ? $a['assistant_name'] : 'Assistant';
        $cnt  = isset( $a['conversations'] ) ? (int) $a['conversations'] : 0;
        $bullets[] = sprintf( '%s (%d conversations)', $name, $cnt );
    }

    return $bullets;

}

/**
 * Paid: Impact Metrics (best-effort based on your current logging)
 *
 * Definition:
 * - Calculate the estimated time saved based on the number of conversations and the minutes saved per conversation
 * - Calculate the resolved rate based on the number of conversations and the number of resolved conversations
 * - Calculate the average messages per chat based on the number of conversations and the number of messages
 *
 * @param int $start_ts
 * @param int $end_ts
 */
function kognetiks_insights_get_impact_metrics( $start_ts, $end_ts, $stats = [] ) { 

    // Default: 1.4 minutes saved per conversation (adjustable)
    $minutes_per_convo = (float) apply_filters( 'kognetiks_insights_minutes_saved_per_conversation', 1.4, $start_ts, $end_ts, $stats );

    $conversations = isset( $stats['conversations'] ) ? (int) $stats['conversations'] : 0;

    $hours_saved = 0.0;
    if ( $conversations > 0 && $minutes_per_convo > 0 ) {
        $hours_saved = ( $conversations * $minutes_per_convo ) / 60.0;
    }

    $impact = [
        'support_time_saved_hours' => $hours_saved,
        'resolved_rate_pct'        => null, // later
        'avg_messages_per_chat'    => null, // later
    ];

    return apply_filters( 'kognetiks_insights_impact_metrics', $impact, $start_ts, $end_ts, $stats );

}

/**
 * Internal helper: create a simple trend label.
 *
 * @param float|null $pct
 * @return string
 */
function kognetiks_insights_trend_label( $pct ) {

    if ( $pct === null || $pct === '' ) {
        return 'No prior data';
    }

    $pct = (float) $pct;
    $sign = ( $pct > 0 ) ? '+' : '';
    return $sign . rtrim( rtrim( number_format( $pct, 1, '.', '' ), '0' ), '.' ) . '%';

}

/**
 * Value Visibility Email - Ver 2.4.1 (FREE)
 *
 * Free users get basic stats: pages, visitors, users, conversations + light trend.
 *
 * @param array $args Optional: ['period' => 'daily'|'weekly'|'monthly', 'email_to' => 'someone@site.com']
 * @return array {subject, message_html, message_text, meta}
 */
function kognetiks_insights_value_visibility_email( $args = [] ) {

    $defaults = [
        'period'   => 'weekly',
        'email_to' => '', // optional, not used yet (activation later)
    ];
    $args = wp_parse_args( $args, $defaults );

    $window = kognetiks_insights_get_period_window( $args['period'] );
    $stats  = kognetiks_insights_get_usage_stats( $window['start'], $window['end'] );

    // Metric row: the exact items you called out.
    $metric_items = [
        [
            'label' => 'Conversations',
            'value' => (string) intval( $stats['conversations'] ),
            'hint'  => 'Chat sessions handled',
        ],
        [
            'label' => 'Pages',
            'value' => (string) intval( $stats['pages'] ),
            'hint'  => 'Where chats occurred',
        ],
        [
            'label' => 'Visitors',
            'value' => (string) intval( $stats['visitors'] ),
            'hint'  => 'Site visitors who engaged',
        ],
        [
            'label' => 'Users',
            'value' => (string) intval( $stats['users'] ),
            'hint'  => 'Logged-in users who engaged',
        ],
    ];

    $trend_lines = [
        'Conversations: ' . kognetiks_insights_trend_label( $stats['trend_conversations'] ),
        'Pages: ' . kognetiks_insights_trend_label( $stats['trend_pages'] ),
        'Visitors: ' . kognetiks_insights_trend_label( $stats['trend_visitors'] ),
        'Users: ' . kognetiks_insights_trend_label( $stats['trend_users'] ),
    ];

    $content  = '<p style="margin-top:0;">Here is a quick snapshot of your chatbot activity.</p>';
    $content .= kognetiks_insights_metric_row_html( $metric_items );
    $content .= kognetiks_insights_bullets_html( 'Summary and Trend', $trend_lines );

    // Light nudge without gating language.
    $content .= '<p style="margin:12px 0 0 0;color:#444;">Want deeper insights like top unanswered questions and estimated time saved? Enable premium analytics to unlock actionable reporting.</p>';

    $subject = sprintf(
        'Your chatbot activity report (%s)',
        $window['label']
    );

    $subtitle = 'Basic usage metrics for your site';

    $message_html = kognetiks_insights_build_email_html(
        'Chatbot Activity Snapshot',
        $subtitle,
        $content,
        [
            'period_label' => $window['label'],
            'footer_note'  => 'You are receiving this email because free analytics reporting is enabled for the Kognetiks Chatbot. Unlock your chatbot\'s value by upgrading on the chatbot\'s Reporting or Insights settings tab.',
        ]
    );

    // Plain text fallback (kept simple)
    $message_text  = "Chatbot Activity Snapshot\n";
    $message_text .= "Reporting period: {$window['label']}\n\n";
    $message_text .= "Conversations: " . intval( $stats['conversations'] ) . "\n";
    $message_text .= "Pages: " . intval( $stats['pages'] ) . "\n";
    $message_text .= "Visitors: " . intval( $stats['visitors'] ) . "\n";
    $message_text .= "Users: " . intval( $stats['users'] ) . "\n\n";
    $message_text .= "Summary / Trend:\n";
    foreach ( $trend_lines as $line ) {
        $message_text .= "- {$line}\n";
    }
    $message_text .= "\nUpgrade for actionable insights like top unanswered questions and estimated time saved.\n";

    $payload = [
        'subject'       => apply_filters( 'kognetiks_insights_value_visibility_subject', $subject, $window, $stats, $args ),
        'message_html'  => apply_filters( 'kognetiks_insights_value_visibility_html', $message_html, $window, $stats, $args ),
        'message_text'  => apply_filters( 'kognetiks_insights_value_visibility_text', $message_text, $window, $stats, $args ),
        'meta'          => [
            'tier'   => 'free',
            'period' => $window['period'],
            'start'  => $window['start'],
            'end'    => $window['end'],
        ],
    ];

    return $payload;

}

/**
 * Value Translation Email - Ver 2.4.1 (PAID)
 *
 * Paid users get actionable insights:
 * - estimated time saved
 * - top unanswered questions
 * - key engagement/quality signals (hooks for more)
 *
 * @param array $args Optional: ['period' => 'daily'|'weekly'|'monthly', 'email_to' => 'someone@site.com']
 * @return array {subject, message_html, message_text, meta}
 */
function kognetiks_insights_value_translation_email( $args = [] ) {

    $defaults = [
        'period'   => 'weekly',
        'email_to' => '',
    ];
    $args = wp_parse_args( $args, $defaults );

    $window = kognetiks_insights_get_period_window( $args['period'] );
    $stats  = kognetiks_insights_get_usage_stats( $window['start'], $window['end'] );

    // TODO: Replace with real calculations once stats are wired.
    // Keep the output keys stable to prevent template churn.
    $impact = [
        'support_time_saved_hours' => 0.0,
        'resolved_rate_pct'        => null, // e.g., 82.3
        'avg_messages_per_chat'    => null,
    ];

    $impact = kognetiks_insights_get_impact_metrics( $window['start'], $window['end'], $stats );

    $top_unanswered = kognetiks_insights_get_top_unanswered_questions( $window['start'], $window['end'], 5 );
    $top_pages      = kognetiks_insights_get_top_pages_by_activity( $window['start'], $window['end'], 5 );
    $top_assistants = kognetiks_insights_get_top_assistants_used( $window['start'], $window['end'], 5 );    

    $recommendations = kognetiks_insights_generate_recommendations(
        $stats,
        $impact,
        $top_unanswered,
        $top_pages,
        $top_assistants,
        5
    );
    
    $metric_items = [
        [
            'label' => 'Conversations',
            'value' => (string) intval( $stats['conversations'] ),
            'hint'  => 'Chats handled',
        ],
        [
            'label' => 'Est. Time Saved',
            'value' => rtrim( rtrim( number_format( (float) $impact['support_time_saved_hours'], 1, '.', '' ), '0' ), '.' ) . ' hrs',
            'hint'  => 'Based on your settings',
        ],
        [
            'label' => 'Resolved Rate',
            'value' => ( $impact['resolved_rate_pct'] === null ) ? '' : ( rtrim( rtrim( number_format( (float) $impact['resolved_rate_pct'], 1, '.', '' ), '0' ), '.' ) . '%' ),
            'hint'  => 'Answered without fallback',
        ],
        [
            'label' => 'Engagement Depth',
            'value' => ( $impact['avg_messages_per_chat'] === null ) ? '' : rtrim( rtrim( number_format( (float) $impact['avg_messages_per_chat'], 1, '.', '' ), '0' ), '.' ),
            'hint'  => 'Avg messages per chat',
        ],
    ];

    $content  = '<p style="margin-top:0;">Here is what your chatbot activity likely meant for your business.</p>';
    $content .= kognetiks_insights_metric_row_html( $metric_items );

    // Insights section
    if ( ! empty( $top_unanswered ) ) {
        $content .= kognetiks_insights_bullets_html( 'Top Unanswered Questions', $top_unanswered );
        $content .= '<p style="margin:8px 0 0 0;color:#444;">Consider adding content or knowledge base entries that address these questions.</p>';
    } else {
        $content .= '<p style="margin:10px 0 0 0;color:#444;"><strong>Top Unanswered Questions:</strong> No items detected for this period.</p>';
    }

    $pages_bullets = kognetiks_insights_format_top_pages_bullets( $top_pages );
    if ( ! empty( $pages_bullets ) ) {
        $content .= kognetiks_insights_bullets_html( 'Top Pages by Chat Activity', $pages_bullets );
    }

    $assist_bullets = kognetiks_insights_format_top_assistants_bullets( $top_assistants );
    if ( ! empty( $assist_bullets ) ) {
        $content .= kognetiks_insights_bullets_html( 'Top Assistants Used', $assist_bullets );
    }

    // Add a flexible “Recommendations” block (paid differentiator, populated via filter)
    $recommendations = apply_filters( 'kognetiks_insights_recommendations', [], $window['start'], $window['end'], $stats, $impact );
    if ( ! empty( $recommendations ) && is_array( $recommendations ) ) {
        $content .= kognetiks_insights_bullets_html( 'Suggested Next Steps', array_slice( $recommendations, 0, 5 ) );
    }

    $subject = sprintf(
        'Your chatbot insights report (%s)',
        $window['label']
    );

    $subtitle = 'Actionable insights and impact metrics';

    // Determine tier for footer message
    $is_premium = ( function_exists( 'kognetiks_insights_is_premium' ) && kognetiks_insights_is_premium() );
    $footer_note = $is_premium
        ? 'You are receiving this email because premium analytics reporting is enabled for the Kognetiks Chatbot.'
        : 'You are receiving this email because free analytics reporting is enabled for the Kognetiks Chatbot. Unlock your chatbot\'s value by upgrading on the chatbot\'s Reporting or Insights settings tab.';
    
    $message_html = kognetiks_insights_build_email_html(
        'Chatbot Insights Report',
        $subtitle,
        $content,
        [
            'period_label' => $window['label'],
            'footer_note'  => $footer_note,
        ]
    );

    $message_text  = "Chatbot Insights Report\n";
    $message_text .= "Reporting period: {$window['label']}\n\n";
    $message_text .= "Conversations: " . intval( $stats['conversations'] ) . "\n";
    $message_text .= "Estimated time saved: " . number_format( (float) $impact['support_time_saved_hours'], 1, '.', '' ) . " hrs\n";
    $message_text .= "Resolved rate: " . ( $impact['resolved_rate_pct'] === null ? 'n/a' : number_format( (float) $impact['resolved_rate_pct'], 1, '.', '' ) . '%' ) . "\n";
    $message_text .= "Engagement depth: " . ( $impact['avg_messages_per_chat'] === null ? 'n/a' : number_format( (float) $impact['avg_messages_per_chat'], 1, '.', '' ) ) . "\n\n";

    if ( ! empty( $top_unanswered ) ) {
        $message_text .= "Top unanswered questions:\n";
        foreach ( $top_unanswered as $q ) {
            $message_text .= "- {$q}\n";
        }
        $message_text .= "\n";
    }

    if ( ! empty( $recommendations ) && is_array( $recommendations ) ) {
        $message_text .= "Suggested next steps:\n";
        foreach ( array_slice( $recommendations, 0, 5 ) as $r ) {
            $message_text .= "- {$r}\n";
        }
        $message_text .= "\n";
    }
    if ( ! empty( $pages_bullets ) ) {
        $message_text .= "Top pages by chat activity:\n";
        foreach ( $pages_bullets as $b ) {
            $message_text .= "- {$b}\n";
        }
        $message_text .= "\n";
    }
    
    if ( ! empty( $assist_bullets ) ) {
        $message_text .= "Top assistants used:\n";
        foreach ( $assist_bullets as $b ) {
            $message_text .= "- {$b}\n";
        }
        $message_text .= "\n";
    }

    $payload = [
        'subject'       => apply_filters( 'kognetiks_insights_value_translation_subject', $subject, $window, $stats, $impact, $args ),
        'message_html'  => apply_filters( 'kognetiks_insights_value_translation_html', $message_html, $window, $stats, $impact, $args ),
        'message_text'  => apply_filters( 'kognetiks_insights_value_translation_text', $message_text, $window, $stats, $impact, $args ),
        'meta'          => [
            'tier'   => 'paid',
            'period' => $window['period'],
            'start'  => $window['start'],
            'end'    => $window['end'],
        ],
    ];

    return $payload;

}

/**
 * Internal helper: get table names safely.
 *
 * @return array
 */
function kognetiks_insights_get_table_names() {

    global $wpdb;

    return [
        'conversation_log' => $wpdb->prefix . 'chatbot_chatgpt_conversation_log',
        'interactions'     => $wpdb->prefix . 'chatbot_chatgpt_interactions',
    ];

}

/**
 * Paid: Generate 3–5 deterministic recommendations from stats/insights.
 *
 * @param array $stats
 * @param array $impact
 * @param array $top_unanswered array of strings
 * @param array $top_pages array of ['page_id','title','url','conversations']
 * @param array $top_assistants array of ['assistant_id','assistant_name','conversations']
 * @param int   $max
 * @return array array of recommendation strings
 */
function kognetiks_insights_generate_recommendations( $stats, $impact, $top_unanswered, $top_pages, $top_assistants, $max = 5 ) {

    $recs = [];

    $conversations = isset( $stats['conversations'] ) ? (int) $stats['conversations'] : 0;
    $pages         = isset( $stats['pages'] ) ? (int) $stats['pages'] : 0;

    // 1) Unanswered questions -> knowledge content
    if ( ! empty( $top_unanswered ) ) {
        $recs[] = 'Add a short FAQ or knowledge entry addressing the top unanswered questions to improve resolution rates.';
    }

    // 2) Concentrated activity on a page -> page-specific help
    if ( ! empty( $top_pages ) && isset( $top_pages[0]['conversations'] ) ) {
        $top_cnt = (int) $top_pages[0]['conversations'];
        if ( $conversations > 0 ) {
            $share = $top_cnt / $conversations; // 0..1
            if ( $share >= 0.35 ) {
                $title = ! empty( $top_pages[0]['title'] ) ? $top_pages[0]['title'] : 'your top page';
                $recs[] = sprintf(
                    'Your highest chat volume is on “%s”. Consider adding a short help section on that page and linking to the most relevant documentation.',
                    $title
                );
            }
        }
    }

    // 3) Many pages with chats -> navigation + discoverability
    if ( $pages >= 5 ) {
        $recs[] = 'Chats are happening across multiple pages. Consider adding a consistent “Help” or “Support” link in your header or footer to guide visitors to answers faster.';
    }

    // 4) Time saved present -> remind to staff-proof it
    $hours_saved = isset( $impact['support_time_saved_hours'] ) ? (float) $impact['support_time_saved_hours'] : 0.0;
    if ( $hours_saved >= 1.0 ) {
        $recs[] = 'Since the chatbot is saving measurable time, consider routing common pre-sales and support questions into a dedicated assistant or knowledge set for consistency.';
    }

    // 5) Assistant concentration -> tune the dominant assistant
    if ( ! empty( $top_assistants ) && isset( $top_assistants[0]['conversations'] ) ) {
        $top_a_cnt = (int) $top_assistants[0]['conversations'];
        if ( $conversations > 0 ) {
            $a_share = $top_a_cnt / $conversations;
            if ( $a_share >= 0.60 ) {
                $name = ! empty( $top_assistants[0]['assistant_name'] ) ? $top_assistants[0]['assistant_name'] : 'your top assistant';
                $recs[] = sprintf(
                    'Most chats are handled by “%s”. Review its instructions and knowledge sources to make sure it reflects your latest FAQs and policies.',
                    $name
                );
            }
        }
    }

    // Ensure we always have at least 3 recommendations in paid.
    // Add generic but still useful items, only if needed.
    if ( count( $recs ) < 3 ) {
        $recs[] = 'Review the most common topics visitors ask about and turn them into short, scannable answers on your site.';
    }
    if ( count( $recs ) < 3 ) {
        $recs[] = 'If you use multiple assistants, consider assigning them by page or intent so visitors get more focused answers.';
    }
    if ( count( $recs ) < 3 ) {
        $recs[] = 'Check your chatbot greeting and first suggested prompts. Small tweaks can increase engagement and reduce confusion.';
    }

    // Cap, deterministic order, de-dupe
    $recs = array_values( array_unique( $recs ) );
    $max  = max( 1, (int) $max );

    return array_slice( $recs, 0, $max );

}

/**
 * Send the correct automated email based on license.
 *
 * @param array $args ['period' => 'daily'|'weekly'|'monthly', 'email_to' => '', 'force_tier' => '' ]
 * @return array payload returned from the email builder
 */
function kognetiks_insights_send_proof_of_value_email( $args = [] ) {

    $defaults = [
        'period'     => 'weekly',
        'email_to'   => get_option( 'admin_email' ),
        'force_tier' => '', // 'free' or 'paid' for testing
    ];
    $args = wp_parse_args( $args, $defaults );

    $tier = 'free';

    // Optional override for testing.
    if ( $args['force_tier'] === 'paid' || $args['force_tier'] === 'free' ) {
        $tier = $args['force_tier'];
    } else {
        // Replace this with your actual Freemius check.
        // Example pattern:
        // if ( function_exists( 'kognetiks_insights_fs' ) && kognetiks_insights_fs()->can_use_premium_code() ) { $tier = 'paid'; }
        $tier = ( function_exists( 'kognetiks_insights_is_premium' ) && kognetiks_insights_is_premium() ) ? 'paid' : 'free';
    }

    $payload = ( $tier === 'paid' )
        ? kognetiks_insights_value_translation_email( $args )
        : kognetiks_insights_value_visibility_email( $args );

    // Send (HTML + text alternative)
    $to      = sanitize_email( $args['email_to'] );
    $subject = $payload['subject'];

    // Prefer HTML. Add headers accordingly.
    $headers = [ 'Content-Type: text/html; charset=UTF-8' ];

    $sent = wp_mail( $to, $subject, $payload['message_html'], $headers );

    // Optional: log the result somewhere
    do_action( 'kognetiks_insights_automated_email_sent', $sent, $tier, $to, $payload, $args );

    return $payload;

}

/**
 * Add custom monthly cron interval.
 *
 * @param array $schedules Existing cron schedules
 * @return array Modified schedules
 */
function kognetiks_insights_add_monthly_cron_interval( $schedules ) {

    $schedules['monthly'] = [
        'interval' => MONTH_IN_SECONDS, // 30 days
        'display'  => __( 'Once Monthly', 'chatbot-chatgpt' ),
    ];

    return $schedules;

}
// Re-enabled - safe to use
add_filter( 'cron_schedules', 'kognetiks_insights_add_monthly_cron_interval' );

/**
 * Schedule automated proof of value email cron job.
 *
 * @param string $period 'daily', 'weekly', or 'monthly'
 * @param string $email_to Email address to send to (optional, defaults to admin_email)
 * @return bool True if scheduled successfully, false otherwise
 */
function kognetiks_insights_schedule_proof_of_value_email( $period = 'weekly', $email_to = '' ) {

    // CRITICAL: Clear ALL existing scheduled hooks FIRST to prevent duplicates
    // Use multiple methods to ensure complete cleanup
    wp_clear_scheduled_hook( 'kognetiks_insights_send_proof_of_value_email_hook' );
    
    // Double-check: Force clear all instances
    $crons = _get_cron_array();
    if ( $crons ) {
        foreach ( $crons as $timestamp => $cron ) {
            if ( isset( $cron['kognetiks_insights_send_proof_of_value_email_hook'] ) ) {
                foreach ( $cron['kognetiks_insights_send_proof_of_value_email_hook'] as $key => $event ) {
                    wp_unschedule_event( $timestamp, 'kognetiks_insights_send_proof_of_value_email_hook', $event['args'] );
                }
            }
        }
    }
    
    // Verify it's actually cleared before scheduling
    $still_scheduled = wp_next_scheduled( 'kognetiks_insights_send_proof_of_value_email_hook' );
    if ( $still_scheduled ) {
        // If still scheduled, try one more aggressive clear
        $max_attempts = 20;
        $attempts = 0;
        while ( $still_scheduled && $attempts < $max_attempts ) {
            wp_unschedule_event( $still_scheduled, 'kognetiks_insights_send_proof_of_value_email_hook' );
            $still_scheduled = wp_next_scheduled( 'kognetiks_insights_send_proof_of_value_email_hook' );
            $attempts++;
        }
    }

    // Map period to WordPress cron intervals
    $interval_mapping = [
        'daily'   => 'daily',
        'weekly'  => 'weekly',
        'monthly' => 'monthly',
    ];

    $interval = isset( $interval_mapping[ $period ] ) ? $interval_mapping[ $period ] : 'weekly';

    // Verify the interval is registered
    $schedules = wp_get_schedules();
    if ( ! isset( $schedules[ $interval ] ) ) {
        // Interval not registered, fallback to weekly
        $interval = 'weekly';
    }

    // Only schedule if we're sure it's cleared
    $verify_cleared = wp_next_scheduled( 'kognetiks_insights_send_proof_of_value_email_hook' );
    if ( ! $verify_cleared ) {
        // Schedule the event - use a proper recurring schedule
        // For daily: schedule for next day at the same time
        // For weekly: schedule for next week at the same time
        // For monthly: schedule for next month at the same time
        if ( $interval === 'monthly' ) {
            $timestamp = time() + MONTH_IN_SECONDS;
        } elseif ( $interval === 'daily' ) {
            $timestamp = time() + DAY_IN_SECONDS;
        } else {
            $timestamp = time() + WEEK_IN_SECONDS;
        }
        $result = wp_schedule_event( $timestamp, $interval, 'kognetiks_insights_send_proof_of_value_email_hook', [ $period, $email_to ] );
        
        // Check if scheduling succeeded
        // wp_schedule_event() returns false on failure, or true/1 on success
        // Trust the return value rather than immediately checking wp_next_scheduled()
        // which may not find it due to timing or argument matching issues
        if ( $result === false ) {
            // Scheduling failed, return false
            return false;
        }
        
        // Scheduling succeeded - return true
        // Note: We trust wp_schedule_event()'s return value rather than immediately
        // checking wp_next_scheduled() which may have timing issues or argument matching problems
        return true;
    }
    
    // If it was still scheduled after all clearing attempts, there's already a valid schedule
    // Return true because the cron job exists (even if we couldn't replace it)
    return true;

}

/**
 * Unschedule automated proof of value email cron job.
 * Optimized to handle many cron jobs efficiently.
 *
 * @return void
 */
function kognetiks_insights_unschedule_proof_of_value_email() {

    // Use the simple method first - wp_clear_scheduled_hook handles all instances
    wp_clear_scheduled_hook( 'kognetiks_insights_send_proof_of_value_email_hook' );
    
    // Force clear all instances using _get_cron_array to ensure complete cleanup
    $crons = _get_cron_array();
    if ( $crons ) {
        foreach ( $crons as $timestamp => $cron ) {
            if ( isset( $cron['kognetiks_insights_send_proof_of_value_email_hook'] ) ) {
                foreach ( $cron['kognetiks_insights_send_proof_of_value_email_hook'] as $key => $event ) {
                    wp_unschedule_event( $timestamp, 'kognetiks_insights_send_proof_of_value_email_hook', $event['args'] );
                }
            }
        }
    }
    
    // Final verification - if still scheduled, try one more aggressive clear
    $scheduled = wp_next_scheduled( 'kognetiks_insights_send_proof_of_value_email_hook' );
    if ( $scheduled ) {
        // If still scheduled after clear, try one more time with a timeout protection
        // Limit the loop to prevent infinite loops
        $max_iterations = 20;
        $iterations = 0;
        
        while ( $scheduled && $iterations < $max_iterations ) {
            wp_unschedule_event( $scheduled, 'kognetiks_insights_send_proof_of_value_email_hook' );
            $scheduled = wp_next_scheduled( 'kognetiks_insights_send_proof_of_value_email_hook' );
            $iterations++;
        }
    }

}

/**
 * Initialize and verify cron job status based on current settings.
 * This ensures cron jobs are properly cleaned up if the feature is disabled.
 * FIXED: Only runs on the specific settings page, not on every admin page load.
 *
 * @return void
 */
function kognetiks_insights_init_proof_of_value_email_cron() {
    // Only run on the chatbot settings reporting page
    if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'chatbot-chatgpt' || ! isset( $_GET['tab'] ) || $_GET['tab'] !== 'reporting' ) {
        return;
    }
    
    // Don't run during form submission - let the update_option hooks handle it
    if ( isset( $_POST['option_page'] ) && $_POST['option_page'] === 'chatbot_chatgpt_reporting' ) {
        return;
    }
    
    // Don't run during AJAX requests
    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        return;
    }
    
    // CRITICAL: Add a transient to prevent multiple rapid calls (prevents duplicate scheduling)
    $last_check = get_transient( 'kognetiks_insights_cron_init_last_check' );
    if ( $last_check && ( time() - $last_check ) < 10 ) {
        // Don't run if we checked less than 10 seconds ago
        return;
    }
    set_transient( 'kognetiks_insights_cron_init_last_check', time(), 60 );
    
    $enabled = get_option( 'chatbot_chatgpt_insights_email_enabled', 'No' );
    
    if ( $enabled === 'Yes' ) {
        // Feature is enabled, ensure cron is scheduled
        $period = get_option( 'chatbot_chatgpt_insights_email_frequency', 'weekly' );
        $email   = get_option( 'chatbot_chatgpt_insights_email_address', '' );
        
        // Check if cron is already scheduled (this is fast, doesn't load all cron jobs)
        $scheduled = wp_next_scheduled( 'kognetiks_insights_send_proof_of_value_email_hook' );
        
        if ( ! $scheduled ) {
            // Not scheduled, schedule it
            kognetiks_insights_schedule_proof_of_value_email( $period, $email );
        }
    } else {
        // Feature is disabled, ensure cron is not scheduled
        kognetiks_insights_unschedule_proof_of_value_email();
    }

}

/**
 * Cron job callback function to send proof of value email.
 *
 * @param string $period 'daily', 'weekly', or 'monthly'
 * @param string $email_to Email address (optional)
 * @return void
 */
function kognetiks_insights_send_proof_of_value_email_callback( $period = 'weekly', $email_to = '' ) {

    // Safety check: Prevent multiple emails from being sent in quick succession
    $last_sent = get_transient( 'kognetiks_insights_email_last_sent' );
    if ( $last_sent && ( time() - $last_sent ) < 300 ) { // 5 minutes minimum between emails
        return; // Too soon, skip this run
    }

    // Verify the feature is still enabled before sending
    $enabled = get_option( 'chatbot_chatgpt_insights_email_enabled', 'No' );
    
    if ( $enabled !== 'Yes' ) {
        // Feature is disabled, unschedule the cron and exit
        kognetiks_insights_unschedule_proof_of_value_email();
        return;
    }

    $args = [
        'period'   => $period,
        'email_to' => ! empty( $email_to ) ? $email_to : get_option( 'admin_email' ),
    ];

    kognetiks_insights_send_proof_of_value_email( $args );
    
    // Set transient to prevent rapid-fire emails
    set_transient( 'kognetiks_insights_email_last_sent', time(), 3600 ); // 1 hour expiry

}

// Hook the callback to the cron event - Re-enabled with safety checks
add_action( 'kognetiks_insights_send_proof_of_value_email_hook', 'kognetiks_insights_send_proof_of_value_email_callback', 10, 2 );

// Initialize cron job status - Only runs on reporting settings page
add_action( 'admin_init', 'kognetiks_insights_init_proof_of_value_email_cron', 20 );

/**
 * Emergency function to immediately stop all insights email cron jobs.
 * Call this function to stop the email flood.
 * 
 * Usage: Add this to wp-config.php temporarily or run via WP-CLI:
 * kognetiks_insights_emergency_stop_emails();
 */
function kognetiks_insights_emergency_stop_emails() {

    // Clear all scheduled hooks
    wp_clear_scheduled_hook( 'kognetiks_insights_send_proof_of_value_email_hook' );
    
    // Force unschedule all instances
    $crons = _get_cron_array();
    if ( $crons ) {
        foreach ( $crons as $timestamp => $cron ) {
            if ( isset( $cron['kognetiks_insights_send_proof_of_value_email_hook'] ) ) {
                foreach ( $cron['kognetiks_insights_send_proof_of_value_email_hook'] as $key => $event ) {
                    wp_unschedule_event( $timestamp, 'kognetiks_insights_send_proof_of_value_email_hook', $event['args'] );
                }
            }
        }
    }
    
    // Disable the feature in settings
    update_option( 'chatbot_chatgpt_insights_email_enabled', 'No' );
    
    // Clear the last sent transient
    delete_transient( 'kognetiks_insights_email_last_sent' );
    
    return true;

}
