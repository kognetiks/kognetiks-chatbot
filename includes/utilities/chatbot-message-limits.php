<?php
/**
 * Kognetiks Chatbot - Message spend limits
 *
 * Length, burst, and the saved visitor or user quota are enforced here
 * before a provider call. The browser counter is only a display.
 *
 * @package chatbot-chatgpt
 */

if ( ! defined( 'WPINC' ) ) {
    die();
}

/**
 * Hard character ceiling for one chat prompt.
 *
 * @return int
 */
function chatbot_chatgpt_max_message_characters() {
    return 4000;
}

/**
 * Visitor quota used only when the site has not saved one.
 *
 * @return int
 */
function chatbot_chatgpt_visitor_message_limit_default() {
    return 20;
}

/**
 * Visitor period used only when the site has not saved one.
 *
 * @return string
 */
function chatbot_chatgpt_visitor_message_period_default() {
    return 'Daily';
}

/**
 * Character length. A huge byte string is treated as over the ceiling.
 *
 * @param string $raw Prompt text.
 * @return int
 */
function chatbot_chatgpt_message_character_count( $raw ) {
    if ( ! is_string( $raw ) ) {
        return 0;
    }
    if ( strlen( $raw ) > 65536 ) {
        return chatbot_chatgpt_max_message_characters() + 1;
    }
    if ( function_exists( 'mb_strlen' ) ) {
        return (int) mb_strlen( $raw, 'UTF-8' );
    }
    return strlen( $raw );
}

/**
 * Clamp a saved quota to the 1-999 range offered in settings.
 *
 * @param mixed $limit Saved limit.
 * @return int
 */
function chatbot_chatgpt_normalize_message_limit( $limit ) {
    if ( function_exists( 'absint' ) ) {
        $limit = absint( $limit );
    } else {
        $limit = (int) $limit;
        if ( $limit < 0 ) {
            $limit = 0;
        }
    }
    if ( $limit < 1 ) {
        $limit = 1;
    }
    if ( $limit > 999 ) {
        $limit = 999;
    }
    return $limit;
}

/**
 * Keep a period name the server knows how to window.
 *
 * @param mixed $period Saved period.
 * @return string
 */
function chatbot_chatgpt_normalize_message_period( $period ) {
    $allowed = array( 'Hourly', 'Daily', 'Weekly', 'Monthly', 'Quarterly', 'Yearly', 'Lifetime' );
    if ( ! is_string( $period ) || ! in_array( $period, $allowed, true ) ) {
        return 'Lifetime';
    }
    return $period;
}

/**
 * Saved quota for this actor. A missing visitor option uses the tighter default.
 *
 * @param bool $is_logged_in Logged-in account.
 * @return array{limit:int,period:string}
 */
function chatbot_chatgpt_actor_message_quota( $is_logged_in ) {
    if ( $is_logged_in ) {
        $limit  = get_option( 'chatbot_chatgpt_user_message_limit_setting', 999 );
        $period = get_option( 'chatbot_chatgpt_user_message_limit_period_setting', 'Lifetime' );
    } else {
        $limit  = get_option( 'chatbot_chatgpt_visitor_message_limit_setting', chatbot_chatgpt_visitor_message_limit_default() );
        $period = get_option( 'chatbot_chatgpt_visitor_message_limit_period_setting', chatbot_chatgpt_visitor_message_period_default() );
    }

    return array(
        'limit'  => chatbot_chatgpt_normalize_message_limit( $limit ),
        'period' => chatbot_chatgpt_normalize_message_period( $period ),
    );
}

/**
 * Fixed window for a quota period. Lifetime lasts ten years.
 *
 * @param string            $period Hourly through Lifetime.
 * @param DateTimeInterface $now    Server time.
 * @return array{bucket:string,ttl:int}
 */
function chatbot_chatgpt_message_period_window( $period, DateTimeInterface $now ) {
    $period = chatbot_chatgpt_normalize_message_period( $period );
    $zone   = $now->getTimezone();
    $now    = new DateTimeImmutable( $now->format( 'Y-m-d H:i:s' ), $zone ? $zone : new DateTimeZone( 'UTC' ) );

    if ( 'Lifetime' === $period ) {
        return array(
            'bucket' => 'lifetime',
            'ttl'    => 10 * 365 * 24 * 3600,
        );
    }

    if ( 'Hourly' === $period ) {
        $start = $now->setTime( (int) $now->format( 'H' ), 0, 0 );
        $end   = $start->modify( '+1 hour' );
    } elseif ( 'Daily' === $period ) {
        $start = $now->setTime( 0, 0, 0 );
        $end   = $start->modify( '+1 day' );
    } elseif ( 'Weekly' === $period ) {
        $days  = (int) $now->format( 'N' ) - 1;
        $start = $now->setTime( 0, 0, 0 )->modify( '-' . $days . ' days' );
        $end   = $start->modify( '+7 days' );
    } elseif ( 'Monthly' === $period ) {
        $start = $now->setDate( (int) $now->format( 'Y' ), (int) $now->format( 'n' ), 1 )->setTime( 0, 0, 0 );
        $end   = $start->modify( '+1 month' );
    } elseif ( 'Quarterly' === $period ) {
        $quarter_month = (int) ( floor( ( (int) $now->format( 'n' ) - 1 ) / 3 ) * 3 ) + 1;
        $start         = $now->setDate( (int) $now->format( 'Y' ), $quarter_month, 1 )->setTime( 0, 0, 0 );
        $end           = $start->modify( '+3 months' );
    } else {
        $start = $now->setDate( (int) $now->format( 'Y' ), 1, 1 )->setTime( 0, 0, 0 );
        $end   = $start->modify( '+1 year' );
    }

    $ttl = $end->getTimestamp() - $now->getTimestamp();
    if ( $ttl < 1 ) {
        $ttl = 1;
    }

    return array(
        'bucket' => $start->format( 'YmdHis' ),
        'ttl'    => $ttl,
    );
}

/**
 * Decide whether this send is over the length, burst, or quota.
 *
 * @param int   $character_count Prompt length.
 * @param int   $burst_count     Sends already counted in the burst window.
 * @param int   $burst_limit     Burst cap.
 * @param int[] $quota_counts    Sends already counted in each quota bucket.
 * @param int   $quota_limit     Saved visitor or user cap.
 * @return array{status:int,message:string}|null
 */
function chatbot_chatgpt_message_spend_block( $character_count, $burst_count, $burst_limit, array $quota_counts, $quota_limit ) {
    if ( (int) $character_count > chatbot_chatgpt_max_message_characters() ) {
        return array(
            'status'  => 400,
            'message' => 'Message is too long. Please shorten it and try again.',
        );
    }
    if ( (int) $burst_count >= (int) $burst_limit ) {
        return array(
            'status'  => 429,
            'message' => 'Rate limit exceeded. Please wait before sending another message.',
        );
    }
    foreach ( $quota_counts as $count ) {
        if ( (int) $count >= (int) $quota_limit ) {
            return array(
                'status'  => 429,
                'message' => 'Oops! You have reached the message limit. Please try again later.',
            );
        }
    }
    return null;
}

/**
 * Stable token for a transient key. The raw IP or user id is not the key.
 *
 * @param string $value Material to hash.
 * @return string
 */
function chatbot_chatgpt_message_limit_token( $value ) {
    $value = (string) $value;
    if ( function_exists( 'wp_fast_hash' ) ) {
        return wp_fast_hash( $value );
    }
    return hash( 'sha256', $value );
}

/**
 * Client address. The proxy must place the real client in REMOTE_ADDR.
 *
 * @return string
 */
function chatbot_chatgpt_message_client_ip() {
    $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? wp_unslash( $_SERVER['REMOTE_ADDR'] ) : 'unknown';
    if ( ! is_string( $ip ) ) {
        return 'unknown';
    }
    $ip = sanitize_text_field( $ip );
    if ( '' === $ip ) {
        return 'unknown';
    }
    return $ip;
}

/**
 * Integer burst counter. A missing transient is zero.
 *
 * @param string $key Transient name.
 * @return int
 */
function chatbot_chatgpt_read_burst_count( $key ) {
    $value = get_transient( $key );
    if ( false === $value ) {
        return 0;
    }
    return (int) $value;
}

/**
 * Record one burst hit. The 60 second window refreshes on each allowed hit.
 *
 * @param string $key Transient name.
 * @return void
 */
function chatbot_chatgpt_bump_burst_count( $key ) {
    $next = chatbot_chatgpt_read_burst_count( $key ) + 1;
    set_transient( $key, $next, 60 );
}

/**
 * Count inside a fixed period window. An expired window reads as zero.
 *
 * @param string $key Transient name.
 * @param int    $now Unix time.
 * @return int
 */
function chatbot_chatgpt_read_window_count( $key, $now ) {
    $state = get_transient( $key );
    if ( ! is_array( $state ) || ! isset( $state['count'], $state['expires'] ) || (int) $state['expires'] <= (int) $now ) {
        return 0;
    }
    return (int) $state['count'];
}

/**
 * Add one send to a fixed period window without extending the period.
 *
 * @param string $key Transient name.
 * @param int    $ttl Seconds until the period ends.
 * @param int    $now Unix time.
 * @return void
 */
function chatbot_chatgpt_bump_window_count( $key, $ttl, $now ) {
    $state = get_transient( $key );
    if ( ! is_array( $state ) || ! isset( $state['count'], $state['expires'] ) || (int) $state['expires'] <= (int) $now ) {
        $state = array(
            'count'   => 0,
            'expires' => (int) $now + (int) $ttl,
        );
    }
    $state['count'] = (int) $state['count'] + 1;
    $remaining      = (int) $state['expires'] - (int) $now;
    if ( $remaining < 1 ) {
        $remaining = 1;
    }
    set_transient( $key, $state, $remaining );
}

/**
 * Accept one chat send, or return a 400 or 429 error.
 *
 * Guests must be under both the visitor-cookie quota and the IP quota.
 * Accounts, including administrators, use the saved user quota.
 * An over-limit request does not increment the counters.
 *
 * @param int    $user_id    WordPress user id, or 0 for a guest.
 * @param string $visitor_id Verified visitor id.
 * @return true|WP_Error
 */
function chatbot_chatgpt_accept_message_spend( $user_id, $visitor_id ) {
    if ( ! isset( $_POST['message'] ) ) {
        return true;
    }

    $raw = wp_unslash( $_POST['message'] );
    if ( ! is_string( $raw ) ) {
        return new WP_Error(
            'chatbot_message_required',
            'Error: Message is required. Please enter a message and try again.',
            array( 'status' => 400 )
        );
    }
    if ( '' === trim( $raw ) ) {
        return true;
    }

    $is_logged_in = (int) $user_id > 0;
    if ( ! $is_logged_in && ( ! is_string( $visitor_id ) || '' === $visitor_id ) ) {
        return new WP_Error(
            'chatbot_message_limited',
            'Unauthorized access to conversation.',
            array( 'status' => 403 )
        );
    }
    $quota        = chatbot_chatgpt_actor_message_quota( $is_logged_in );
    $now_unix     = time();
    $clock        = function_exists( 'current_datetime' ) ? current_datetime() : new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
    $window       = chatbot_chatgpt_message_period_window( $quota['period'], $clock );
    $ip           = chatbot_chatgpt_message_client_ip();
    $burst_limit  = $is_logged_in ? 20 : 10;
    $burst_key    = $is_logged_in
        ? 'chatbot_rate_limit_user_' . chatbot_chatgpt_message_limit_token( (string) (int) $user_id )
        : 'chatbot_rate_limit_' . chatbot_chatgpt_message_limit_token( $ip );

    $quota_keys = array();
    if ( $is_logged_in ) {
        $quota_keys[] = 'chatbot_q_u_' . chatbot_chatgpt_message_limit_token( (string) (int) $user_id . '|' . $window['bucket'] );
    } else {
        $quota_keys[] = 'chatbot_q_v_' . chatbot_chatgpt_message_limit_token( $visitor_id . '|' . $window['bucket'] );
        $quota_keys[] = 'chatbot_q_ip_' . chatbot_chatgpt_message_limit_token( $ip . '|' . $window['bucket'] );
    }

    $quota_counts = array();
    foreach ( $quota_keys as $quota_key ) {
        $quota_counts[] = chatbot_chatgpt_read_window_count( $quota_key, $now_unix );
    }

    $block = chatbot_chatgpt_message_spend_block(
        chatbot_chatgpt_message_character_count( $raw ),
        chatbot_chatgpt_read_burst_count( $burst_key ),
        $burst_limit,
        $quota_counts,
        $quota['limit']
    );
    if ( is_array( $block ) ) {
        return new WP_Error(
            'chatbot_message_limited',
            $block['message'],
            array( 'status' => (int) $block['status'] )
        );
    }

    chatbot_chatgpt_bump_burst_count( $burst_key );
    foreach ( $quota_keys as $quota_key ) {
        chatbot_chatgpt_bump_window_count( $quota_key, $window['ttl'], $now_unix );
    }

    return true;
}
