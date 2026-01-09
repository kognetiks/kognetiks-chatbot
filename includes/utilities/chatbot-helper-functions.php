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
 * IMPORTANT: This function follows Freemius best practices for checking premium status.
 * The CHATBOT_CHATGPT_FORCE_FREE_MODE constant is DEV/TESTING ONLY and should NEVER
 * be used in production. It's only for local development/testing purposes.
 *
 * @return bool True if user has premium access
 * @since 2.4.2
 */
function chatbot_chatgpt_is_premium() {
    
    // PRODUCTION CODE: Follow Freemius best practices for premium status checks
    
    if ( ! function_exists( 'chatbot_chatgpt_freemius' ) ) {
        return false;
    }

    $fs = chatbot_chatgpt_freemius();
    if ( ! is_object( $fs ) ) {
        return false;
    }

    // PRIMARY CHECK: can_use_premium_code() works with free codebase
    // This returns true if user is in trial OR has a valid license
    // This is the most reliable check for freemium model (same codebase, features unlock by license)
    if ( method_exists( $fs, 'can_use_premium_code' ) ) {
        if ( $fs->can_use_premium_code() ) {
            return true; // User has trial or valid license - grant premium access
        }
    }

    // SECONDARY CHECK: is_paying_or_trial() - works with free codebase
    // This checks if user is paying or in trial (requires registration)
    if ( method_exists( $fs, 'is_paying_or_trial' ) ) {
        if ( $fs->is_paying_or_trial() ) {
            return true;
        }
    }

    // TERTIARY CHECK: Check if user is on Premium plan (works with free codebase)
    // This handles upgrades where plan changed but license not yet synced
    $is_premium_plan = false;
    if ( method_exists( $fs, 'is_plan' ) ) {
        $is_premium_plan = $fs->is_plan( 'premium', false ); // Check for premium or higher plans
    }
    
    // Also check trial plan specifically (for freemium model)
    if ( ! $is_premium_plan && method_exists( $fs, 'is_trial_plan' ) ) {
        $is_premium_plan = $fs->is_trial_plan( 'premium', false );
    }

    // Return true if user is on Premium plan
    if ( $is_premium_plan ) {
        return true;
    }

    // FALLBACK: Check premium code access (requires premium codebase to be installed)
    // This is checked last since it requires the premium plugin version
    if ( method_exists( $fs, 'can_use_premium_code__premium_only' ) ) {
        if ( $fs->can_use_premium_code__premium_only() ) {
            return true;
        }
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