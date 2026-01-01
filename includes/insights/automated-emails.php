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

/**
 * Internal helper: period label + start/end timestamps.
 *
 * @param string $period 'weekly' or 'monthly'
 * @return array
 */
function kognetiks_insights_get_period_window( $period = 'weekly' ) {

    $period = ( $period === 'monthly' ) ? 'monthly' : 'weekly';

    $now = current_time( 'timestamp' ); // WP local time
    // Normalize to start-of-day for consistency.
    $today = strtotime( date( 'Y-m-d 00:00:00', $now ) );

    if ( $period === 'monthly' ) {
        $start = strtotime( date( 'Y-m-01 00:00:00', $today ) );
        $end   = $now;
        $label = date_i18n( 'F Y', $today );
    } else {
        // Week window: last 7 days (including today), simple rolling window.
        $start = strtotime( '-6 days', $today );
        $end   = $now;
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

    // Convert to MySQL DATETIME strings in WP local time.
    $offset   = (float) get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
    $start_dt = gmdate( 'Y-m-d H:i:s', $start_ts + $offset );
    $end_dt   = gmdate( 'Y-m-d H:i:s', $end_ts + $offset );

    // Previous equal-length window immediately before start.
    $window_seconds = max( 1, (int) ( $end_ts - $start_ts ) );
    $prev_end_ts    = $start_ts;
    $prev_start_ts  = $start_ts - $window_seconds;

    $prev_start_dt = gmdate( 'Y-m-d H:i:s', $prev_start_ts + $offset );
    $prev_end_dt   = gmdate( 'Y-m-d H:i:s', $prev_end_ts + $offset );

    // Explicit allowlist for human rows.
    $human_types = [ 'Visitor', 'User' ];

    // Explicit denylist for known system/token rows (defense-in-depth).
    $non_human_types = [ 'Chatbot', 'Prompt Tokens', 'Completion Tokens', 'Total Tokens' ];

    // Placeholders
    $human_in = implode( ',', array_fill( 0, count( $human_types ), '%s' ) );
    $deny_in  = implode( ',', array_fill( 0, count( $non_human_types ), '%s' ) );

    /**
     * Human row predicate:
     * - user_type in allowlist
     * - message_text is present and not just whitespace
     * - (optional) exclude denylist, even though allowlist already protects us
     */
    $sql_window = "
        SELECT
            COUNT(DISTINCT CASE
                WHEN user_type IN ($human_in)
                 AND user_type NOT IN ($deny_in)
                 AND message_text IS NOT NULL
                 AND LENGTH(TRIM(message_text)) > 0
                THEN session_id END
            ) AS conversations,

            COUNT(DISTINCT CASE
                WHEN user_type IN ($human_in)
                 AND user_type NOT IN ($deny_in)
                 AND message_text IS NOT NULL
                 AND LENGTH(TRIM(message_text)) > 0
                THEN page_id END
            ) AS pages,

            COUNT(DISTINCT CASE
                WHEN user_type = 'Visitor'
                 AND user_id = 0
                 AND message_text IS NOT NULL
                 AND LENGTH(TRIM(message_text)) > 0
                THEN session_id END
            ) AS visitors,

            COUNT(DISTINCT CASE
                WHEN user_id > 0
                 AND user_type IN ($human_in)
                 AND message_text IS NOT NULL
                 AND LENGTH(TRIM(message_text)) > 0
                THEN user_id END
            ) AS users

        FROM {$log}
        WHERE interaction_time >= %s AND interaction_time <= %s
    ";

    // Build params: human allowlist + denylist + date range
    $params_current = array_merge( $human_types, $non_human_types, [ $start_dt, $end_dt ] );
    $cur = $wpdb->get_row( $wpdb->prepare( $sql_window, $params_current ), ARRAY_A );

    $params_prev = array_merge( $human_types, $non_human_types, [ $prev_start_dt, $prev_end_dt ] );
    $prev = $wpdb->get_row( $wpdb->prepare( $sql_window, $params_prev ), ARRAY_A );

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

    $start_dt = gmdate( 'Y-m-d H:i:s', $start_ts + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );
    $end_dt   = gmdate( 'Y-m-d H:i:s', $end_ts + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );

    // Patterns that resemble your "I’m not following / rephrase" style messages.
    // Tweak these later, or make them filterable.
    $fallback_like = [
        '%i\'m not following%',
        '%could you ask that%',
        '%that\'s unclear%',
        '%didn\'t quite catch%',
        '%could you try rephras%',
        '%could you rephrase%',
    ];

    $like_sql = implode( ' OR ', array_fill( 0, count( $fallback_like ), 'c.message_text LIKE %s' ) );

    // Pull candidate sessions/times where the chatbot gave a fallback response.
    $sql = "
        SELECT
            v.message_text AS visitor_question
        FROM {$log} c
        INNER JOIN {$log} v
            ON v.session_id = c.session_id
        WHERE
            c.user_type = 'Chatbot'
            AND c.interaction_time >= %s AND c.interaction_time <= %s
            AND ( {$like_sql} )
            AND v.user_id = 0
            AND v.interaction_time = (
                SELECT MAX(v2.interaction_time)
                FROM {$log} v2
                WHERE v2.session_id = c.session_id
                AND v2.user_id = 0
                AND v2.interaction_time <= c.interaction_time
            )
        LIMIT %d
    ";

    $params = array_merge(
        [ $start_dt, $end_dt ],
        $fallback_like,
        [ (int) $limit ]
    );

    $rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );

    $out = [];
    if ( ! empty( $rows ) ) {
        foreach ( $rows as $r ) {
            $q = isset( $r['visitor_question'] ) ? trim( (string) $r['visitor_question'] ) : '';
            if ( $q !== '' ) {
                $out[] = wp_strip_all_tags( $q );
            }
        }
    }

    // De-dupe while keeping order
    $out = array_values( array_unique( $out ) );

    return apply_filters( 'kognetiks_insights_top_unanswered_questions', $out, $start_ts, $end_ts, $limit );
}

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
 * @param array $args Optional: ['period' => 'weekly'|'monthly', 'email_to' => 'someone@site.com']
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
            'footer_note'  => 'You are receiving this email because automated reporting is enabled for Kognetiks Insights.',
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
 * @param array $args Optional: ['period' => 'weekly'|'monthly', 'email_to' => 'someone@site.com']
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
            'value' => ( $impact['resolved_rate_pct'] === null ) ? '—' : ( rtrim( rtrim( number_format( (float) $impact['resolved_rate_pct'], 1, '.', '' ), '0' ), '.' ) . '%' ),
            'hint'  => 'Answered without fallback',
        ],
        [
            'label' => 'Engagement Depth',
            'value' => ( $impact['avg_messages_per_chat'] === null ) ? '—' : rtrim( rtrim( number_format( (float) $impact['avg_messages_per_chat'], 1, '.', '' ), '0' ), '.' ),
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

    $message_html = kognetiks_insights_build_email_html(
        'Chatbot Insights Report',
        $subtitle,
        $content,
        [
            'period_label' => $window['label'],
            'footer_note'  => 'You are receiving this email because premium analytics reporting is enabled for Kognetiks Insights.',
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

