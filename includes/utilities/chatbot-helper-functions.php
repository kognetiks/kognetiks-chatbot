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
    
    if ( ! function_exists( 'chatbot_chatgpt_freemius' ) ) {
        return false;
    }

    $fs = chatbot_chatgpt_freemius();
    if ( ! is_object( $fs ) ) {
        return false;
    }

    // Detect whether we are running inside the premium build
    // Use safe checks: method_exists() so free build doesn't fatal
    $running_premium_build = false;
    if ( method_exists( $fs, 'is__premium_only' ) ) {
        $running_premium_build = $fs->is__premium_only();
    }

    // PRIMARY CHECK: If user is paying, grant premium access
    // This works in both free and premium builds
    if ( method_exists( $fs, 'is_paying' ) ) {
        if ( $fs->is_paying() ) {
            return true;
        }
    }

    // SECONDARY CHECK: If user has active valid license, grant premium access
    // This works in both free and premium builds
    if ( method_exists( $fs, 'has_active_valid_license' ) ) {
        if ( $fs->has_active_valid_license() ) {
            return true;
        }
    }

    // TERTIARY CHECK: If user is in trial, grant premium access ONLY if running premium build
    // In free build, trial does NOT unlock premium features
    if ( method_exists( $fs, 'is_trial' ) ) {
        if ( $fs->is_trial() ) {
            return $running_premium_build;
        }
    }

    // No premium access
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
