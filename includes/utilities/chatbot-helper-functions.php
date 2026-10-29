<?php
/**
 * Kognetiks Chatbot - Some Helper Functions
 *
 * This file contains the code for some helper functions.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

/**
 * Check if user has premium access.
 * 
 * IMPORTANT: Premium features run only in the premium Freemius build
 * (`is__premium_only()`). The free WordPress.org ZIP never unlocks Insights
 * from a paid license alone — install the premium ZIP. Trial also requires
 * the premium build.
 *
 * @return bool True if user has premium access
 * @since 2.4.2
 */
function chatbot_chatgpt_is_premium() {

    /* ============================================================
     * EXTERNAL OVERRIDE (e.g. dev plugin)
     * ============================================================ */
    $override = apply_filters( 'chatbot_chatgpt_is_premium_override', null );
    if ( $override === true ) {
        return true;
    }

    /* ============================================================
     * PRODUCTION LOGIC (FREEMIUS)
     * ============================================================ */

    // Freemius not loaded → not premium
    if ( ! function_exists( 'chatbot_chatgpt_freemius' ) ) {
        return false;
    }

    $fs = chatbot_chatgpt_freemius();
    if ( ! is_object( $fs ) ) {
        return false;
    }

    // Must be running the premium build
    if ( ! method_exists( $fs, 'is__premium_only' ) || ! $fs->is__premium_only() ) {
        return false;
    }

    // Final authority: Freemius says premium code is allowed
    if ( method_exists( $fs, 'can_use_premium_code' ) ) {
        return (bool) $fs->can_use_premium_code();
    }

    return false;
}



/**
 * Returns true if premium code should run (Freemius).
 * Updated to also check Premium plan status for users who upgraded but haven't activated premium code yet.
 *
 * @return bool
 * @since 2.4.2
 */
function kognetiks_insights_is_premium() {

    if ( ! function_exists( 'chatbot_chatgpt_freemius' ) ) {
        return false;
    }

    // Use the shared premium check function
    return chatbot_chatgpt_is_premium();
}


/**
 * Safe wrapper for wp_mail() with timeout protection.
 * Prevents fatal errors when email sending times out (e.g., on localhost without SMTP).
 *
 * @param string|array $to          Email address(es) to send to
 * @param string       $subject     Email subject
 * @param string       $message     Email message body
 * @param string|array $headers     Optional. Email headers
 * @param string|array $attachments Optional. Email attachments
 * @return bool True if email was sent successfully, false otherwise
 * @since 2.4.4
 */
function chatbot_chatgpt_safe_wp_mail( $to, $subject, $message, $headers = '', $attachments = [] ) {
    
    // Check if we're on localhost/development environment
    // On localhost without SMTP, email sending will hang and timeout
    $host = isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : '';
    $server_addr = isset( $_SERVER['SERVER_ADDR'] ) ? $_SERVER['SERVER_ADDR'] : '';
    
    $is_localhost = (
        strpos( $host, 'localhost' ) !== false ||
        strpos( $host, '127.0.0.1' ) !== false ||
        $server_addr === '127.0.0.1' ||
        $server_addr === '::1'
    );
    
    // Check if SMTP is configured via WordPress constants or filters
    $smtp_configured = (
        defined( 'SMTP_HOST' ) && ! empty( SMTP_HOST ) ||
        has_filter( 'wp_mail_smtp_host' ) ||
        has_filter( 'phpmailer_init' )
    );
    
    // On localhost without SMTP, skip email sending to prevent timeouts
    // Allow override via constant if needed for testing
    if ( $is_localhost && ! $smtp_configured && ! defined( 'CHATBOT_CHATGPT_FORCE_EMAIL_ON_LOCALHOST' ) ) {
        // DIAG - Diagnostics - Ver 2.4.5
        // back_trace('ERROR', 'Skipping email send on localhost without SMTP configuration. Email would have been sent to: ' . ( is_array( $to ) ? implode( ', ', $to ) : $to ) );
        return false; // Return false to indicate email was not sent
    }
    
    // Store original execution time limit
    $original_time_limit = ini_get( 'max_execution_time' );
    
    // Set a shorter timeout for email operations (30 seconds)
    // This prevents hanging when SMTP is not configured
    $email_timeout = 30;
    
    try {
        // Temporarily set a shorter timeout for email operations
        if ( $original_time_limit > 0 ) {
            @set_time_limit( $email_timeout );
        }
        
        // Suppress errors and attempt to send email
        $result = @wp_mail( $to, $subject, $message, $headers, $attachments );
        
        // Restore original time limit
        if ( $original_time_limit > 0 ) {
            @set_time_limit( $original_time_limit );
        }
        
        // Log failure if email didn't send
        prod_trace( 'ERROR', 'Failed to send email to ' . ( is_array( $to ) ? implode( ', ', $to ) : $to ) . '. This may be due to missing SMTP configuration.' );
        
        return $result;
        
    } catch ( Exception $e ) {

        // Restore original time limit on exception
        if ( $original_time_limit > 0 ) {
            @set_time_limit( $original_time_limit );
        }
        
        // DIAG - Diagnostics - Ver 2.4.5
        prod_trace( 'ERROR', 'Email sending exception: ' . $e->getMessage() );
        
        return false;
        
    } catch ( Error $e ) {
        
        // Catch PHP 7+ errors (fatal errors)
        if ( $original_time_limit > 0 ) {
            @set_time_limit( $original_time_limit );
        }
        
        // DIAG - Diagnostics - Ver 2.4.5
        prod_trace( 'ERROR', 'Email sending error: ' . $e->getMessage() );
        
        return false;

    }
}

/**
 * Helper function to send an email
 *
 * @param string $to
 * @param string $subject
 * @param string $message
 * @param array $headers
 * @return void
 */
function kognetiks_insights_send_email( $to, $subject, $message, $headers = [] ) {

    kognetiks_insights_send_proof_of_value_email([
        'period'     => 'weekly',
        'email_to'   => get_option('admin_email'),
        'force_tier' => 'free', // switch to 'paid' to preview paid output
    ]);

}
// Add an action to send the email
add_action( 'kognetiks_insights_send_email', 'kognetiks_insights_send_email' );

/**
 * AJAX timeout (seconds) exposed to the chatbot UI.
 * Local Server uses its own timeout setting because model load/inference can run much longer.
 *
 * @return int
 */
function chatbot_chatgpt_get_ajax_timeout_seconds() {

    $platform = esc_attr( get_option( 'chatbot_ai_platform_choice', 'OpenAI' ) );

    if ( $platform === 'Local Server' ) {
        // Cap UI wait at 3 minutes so a stuck Jan.ai call does not spin for 6+ minutes
        $local_timeout = max( 5, intval( get_option( 'chatbot_local_timeout_setting', 120 ) ) );
        return min( $local_timeout, 180 );
    }

    return max( 5, intval( get_option( 'chatbot_chatgpt_timeout_setting', 240 ) ) );
}

/**
 * Extend PHP max_execution_time for a long-running HTTP call.
 *
 * @param int $timeout_seconds Remote request timeout in seconds.
 * @return int|false Original limit, or false if unchanged.
 */
function chatbot_chatgpt_extend_php_execution_for_timeout( $timeout_seconds ) {

    $current = (int) ini_get( 'max_execution_time' );
    $required = (int) $timeout_seconds + 30;

    if ( $current > 0 && $required > $current ) {
        @set_time_limit( $required );
        return $current;
    }

    return false;
}

/**
 * Restore PHP max_execution_time after a long-running HTTP call.
 *
 * @param int|false $original_time Original limit from chatbot_chatgpt_extend_php_execution_for_timeout().
 */
function chatbot_chatgpt_restore_php_execution_time( $original_time ) {

    if ( $original_time !== false && $original_time > 0 ) {
        @set_time_limit( $original_time );
    }
}

/**
 * User id segment for legacy lock hashes.
 * Anonymous visitors are 0 here; isolation comes from the verified session in the new keys.
 *
 * @param int|null $current_user_id WordPress user ID, or null to use get_current_user_id().
 * @return int
 */
function chatbot_chatgpt_get_conversation_lock_user_id( $current_user_id = null ) {

    if ( $current_user_id === null ) {
        $current_user_id = get_current_user_id();
    }

    return chatbot_chatgpt_normalize_wp_user_id( $current_user_id );
}

/**
 * Clear conversation locks and the message queue for one actor.
 * Also clears pre-isolation hashes that included this same session id.
 *
 * @param string $assistant_id Assistant id.
 * @param mixed  $lock_user_id WordPress user id, or 0 for a visitor.
 * @param string $page_id Page id.
 * @param string $session_id Session id.
 * @param bool   $session_already_authorized Privileged admin cleanup only. Conversation reads never set this.
 */
function chatbot_chatgpt_clear_conversation_locks_and_queue( $assistant_id, $lock_user_id, $page_id, $session_id, $session_already_authorized = false ) {

    chatbot_chatgpt_delete_scoped_locks_and_queue( $lock_user_id, $session_id, $page_id, $assistant_id, $session_already_authorized );

    if ( ! function_exists( 'wp_hash' ) || ! is_string( $session_id ) || $session_id === '' ) {
        return;
    }

    $uid = chatbot_chatgpt_normalize_wp_user_id( $lock_user_id );
    $legacy_inputs = array(
        $assistant_id . '|0|' . $page_id . '|' . $session_id,
        $assistant_id . '|' . $uid . '|' . $page_id . '|' . $session_id,
        $assistant_id . '|' . $session_id . '|' . $page_id . '|' . $session_id,
    );

    foreach ( $legacy_inputs as $hash_input ) {
        delete_transient( 'chatgpt_conv_lock_' . wp_hash( $hash_input ) );
        delete_transient( 'chatbot_message_queue_' . wp_hash( $hash_input ) );
        delete_transient( 'kchat_conv_lock_' . wp_hash( $hash_input ) );
        delete_transient( 'chatbot_local_jan_busy_' . wp_hash( $hash_input ) );
    }
}

/**
 * Seconds to wait between Jan.ai chat completions (0 disables post-reply cooldown).
 *
 * @return int 0–30
 */
function chatbot_local_get_jan_cooldown_seconds() {

    $seconds = (int) get_option( 'chatbot_local_jan_cooldown_setting', 3 );
    $seconds = max( 0, min( 30, $seconds ) );

    return (int) apply_filters( 'chatbot_local_jan_cooldown_seconds', $seconds );
}
