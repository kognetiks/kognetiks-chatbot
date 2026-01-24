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
     * DEV OVERRIDE (LOCALHOST / DEV ENV ONLY)
     * ============================================================ */

    // Kill-switch: allow real licensing tests when defined
    $dev_override_enabled =
        ! defined('KCHAT_DISABLE_DEV_PREMIUM_OVERRIDE') ||
        KCHAT_DISABLE_DEV_PREMIUM_OVERRIDE === false;

    $host = $_SERVER['HTTP_HOST'] ?? '';
    $server_addr = $_SERVER['SERVER_ADDR'] ?? '';

    // Normalize host (strip port if present, e.g. localhost:8080)
    $host_no_port = preg_replace('/:\d+$/', '', $host);

    $is_local_host =
        $host_no_port === 'localhost' ||
        $host_no_port === '127.0.0.1' ||
        substr($host_no_port, -6) === '.local';

    // Private LAN ranges (covers many local/dev setups)
    $is_private_ip = false;
    if ( $server_addr !== '' ) {
        $is_valid_ip = filter_var($server_addr, FILTER_VALIDATE_IP) !== false;
        if ( $is_valid_ip ) {
            $is_private_ip =
                filter_var(
                    $server_addr,
                    FILTER_VALIDATE_IP,
                    FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
                ) === false;
        }
    }

    if (
        $dev_override_enabled &&
        defined('WP_DEBUG') && WP_DEBUG === true &&
        ( $is_local_host || $is_private_ip )
    ) {
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
