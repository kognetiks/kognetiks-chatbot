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
 * IMPORTANT: In the FREE plugin build, trial does NOT unlock premium features.
 * Trial + premium functionality requires installing the premium ZIP/plugin.
 * Paid license can still unlock premium (even in free build).
 *
 * Premium access rules:
 * - If user is paying OR has active valid license: return true
 * - If user is in trial: return true ONLY if running premium build
 * - Otherwise return false
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
        // if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'error_log' ) ) {
        //     back_trace('ERROR', 'Skipping email send on localhost without SMTP configuration. Email would have been sent to: ' . ( is_array( $to ) ? implode( ', ', $to ) : $to ) );
        // }
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
        
        // Log failure if email didn't send and we're in debug mode
        if ( ! $result && defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'back_trace' ) ) {
            back_trace( 'ERROR', 'Failed to send email to ' . ( is_array( $to ) ? implode( ', ', $to ) : $to ) . '. This may be due to missing SMTP configuration.' );
        }
        
        return $result;
        
    } catch ( Exception $e ) {
        // Restore original time limit on exception
        if ( $original_time_limit > 0 ) {
            @set_time_limit( $original_time_limit );
        }
        
        // Log the exception
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'back_trace' ) ) {
            back_trace( 'ERROR', 'Email sending exception: ' . $e->getMessage() );
        }
        
        return false;
        
    } catch ( Error $e ) {
        // Catch PHP 7+ errors (fatal errors)
        if ( $original_time_limit > 0 ) {
            @set_time_limit( $original_time_limit );
        }
        
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'back_trace' ) ) {
            back_trace( 'ERROR', 'Email sending error: ' . $e->getMessage() );
        }
        
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
