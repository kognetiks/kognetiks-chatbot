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
 * Returns true if premium code should run (Freemius).
 *
 * @return bool
 */
function kognetiks_insights_is_premium() {

    if ( ! function_exists( 'chatbot_chatgpt_freemius' ) ) {
        return false;
    }

    $fs = chatbot_chatgpt_freemius();
    if ( ! is_object( $fs ) ) {
        return false;
    }

    if ( method_exists( $fs, 'can_use_premium_code' ) ) {
        return (bool) $fs->can_use_premium_code();
    }

    // Fallbacks (in case method availability differs by SDK version)
    if ( method_exists( $fs, 'has_active_valid_license' ) ) {
        return (bool) $fs->has_active_valid_license();
    }

    if ( method_exists( $fs, 'is_paying' ) ) {
        return (bool) $fs->is_paying();
    }

    return false;
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