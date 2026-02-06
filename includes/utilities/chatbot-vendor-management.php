<?php
/**
 * Kognetiks Chatbot - Vendor Management - Ver 2.4.4
 *
 * This file contains the code for plugin vendor management functions.
 *
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

/**
 * Suppress Freemius promotional admin notices
 *
 * Intercept Freemius admin notices and block marketing-related ones
 * This is a custom solution to suppress Freemius promotional admin notices
 * It is not a part of the Freemius SDK and is not supported by Freemius
 * It is a custom solution to suppress Freemius promotional admin notices
 * It is not a part of the Freemius SDK and is not supported by Freemius
 *
 */
if ( function_exists( 'kchat_fs' ) ) {

    kchat_fs()->add_filter('show_admin_notice', function ($show, $notice) {

        // DIAG - Diagnostics - Ver 2.4.4
        back_trace("NOTICE", "Suppressing Freemius promotional admin notice: " . $notice['message']);
        back_trace("NOTICE", "Notice ID: " . $notice['id']);
        back_trace("NOTICE", "Notice Slug: " . $notice['slug']);
        back_trace("NOTICE", "Notice Message: " . $notice['message']);
        back_trace("NOTICE", "Notice Type: " . $notice['type']);
        back_trace("NOTICE", "Notice Level: " . $notice['level']);
        back_trace("NOTICE", "Notice Time: " . $notice['time']);
        back_trace("NOTICE", "Notice User: " . $notice['user']);
        back_trace("NOTICE", "Notice User ID: " . $notice['user_id']);

        // Allow critical/system notices
        if ( !empty($notice['type']) && in_array($notice['type'], ['error', 'warning'], true) ) {
            return $show;
        }

        // Block anything that looks like marketing
        $id   = $notice['id'] ?? '';
        $slug = $notice['slug'] ?? '';
        $msg  = strtolower( strip_tags( $notice['message'] ?? '' ) );

        $marketing_signals = [
            'trial', 'start trial', 'upgrade', 'discount', 'deal', 'limited',
            'premium', 'save', 'offer'
        ];

        foreach ( $marketing_signals as $s ) {
            if ( strpos($id, $s) !== false || strpos($slug, $s) !== false || strpos($msg, $s) !== false ) {
                return false;
            }
        }

        return $show;
    }, 10, 2);

}

