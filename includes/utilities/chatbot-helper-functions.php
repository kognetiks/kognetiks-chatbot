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
 * Check if user has premium access (either via premium code or Premium plan).
 * This handles the case where user sees "Your plan was successfully changed to Premium" 
 * but premium code isn't active yet.
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

    // Check if user can use premium code
    $can_use_premium = false;
    if ( method_exists( $fs, 'can_use_premium_code__premium_only' ) ) {
        $can_use_premium = (bool) $fs->can_use_premium_code__premium_only();
    } elseif ( method_exists( $fs, 'can_use_premium_code' ) ) {
        $can_use_premium = (bool) $fs->can_use_premium_code();
    }

    // Also check if user is on Premium plan (in case they upgraded but premium code not activated yet)
    $is_premium_plan = false;
    if ( method_exists( $fs, 'is_plan' ) ) {
        $is_premium_plan = $fs->is_plan( 'premium', false ); // Check for premium or higher plans
    }

    // Return true if user can use premium code OR is on Premium plan
    if ( $can_use_premium || $is_premium_plan ) {
        return true;
    }

    // Additional fallbacks (in case method availability differs by SDK version)
    if ( method_exists( $fs, 'has_active_valid_license' ) ) {
        return (bool) $fs->has_active_valid_license();
    }

    if ( method_exists( $fs, 'is_paying' ) ) {
        return (bool) $fs->is_paying();
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