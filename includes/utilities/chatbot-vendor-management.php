<?php
/**
 * Kognetiks Chatbot - Vendor Management (Freemius “Quiet Proof” Controls) - Ver 2.4.4
 *
 * Suppresses Freemius marketing/promotional admin notices (keeps errors/warnings)
 * Removes Freemius upgrade/trial links from the Plugins list action links
 *
 * Assumes Freemius is bootstrapped in the main plugin file and that file calls:
 * do_action( 'chatbot_chatgpt_freemius_loaded' );
 *
 * @package chatbot-chatgpt
 */

if ( ! defined( 'WPINC' ) ) {
    die();
}

// Tunables
// Tunables - Ver 2.4.4
if ( ! defined( 'KCHAT_FREEMIUS_QUIET_MODE' ) ) {
    define( 'KCHAT_FREEMIUS_QUIET_MODE', true );
}

// Quiet Proof controls (runs after Freemius loads) - Ver 2.4.4
add_action( 'chatbot_chatgpt_freemius_loaded', function () {

    // DIAG - Diagnostics - Ver 2.4.4
    back_trace("NOTICE", "Freemius loaded");

    if ( ! KCHAT_FREEMIUS_QUIET_MODE ) {
        return;
    }

    if ( ! function_exists( 'chatbot_chatgpt_freemius' ) ) {
        return;
    }

    $fs = chatbot_chatgpt_freemius();
    if ( ! $fs || ! is_object( $fs ) ) {
        return;
    }

    /**
     * 1) Suppress Freemius promotional admin notices.
     *    Keep system/critical notices (errors/warnings).
     */
    $fs->add_filter( 'show_admin_notice', function ( $show, $notice ) {

        if ( ! $show ) {
            return $show;
        }

        // Surgical: kill the built-in “trial promotion” notice.
        if ( isset( $notice['id'] ) && 'trial_promotion' === $notice['id'] ) {
            return false;
        }

        // Keep critical/system notices.
        $type  = $notice['type']  ?? '';
        $level = $notice['level'] ?? '';

        if ( in_array( $type, array( 'error', 'warning' ), true ) ) {
            return $show;
        }
        if ( in_array( $level, array( 'error', 'warning' ), true ) ) {
            return $show;
        }

        // Soft-kill other marketing-ish notices by signals.
        $id   = strtolower( (string) ( $notice['id']   ?? '' ) );
        $slug = strtolower( (string) ( $notice['slug'] ?? '' ) );
        $msg  = strtolower( wp_strip_all_tags( (string) ( $notice['message'] ?? '' ) ) );

        $marketing_signals = array(
            'trial',
            'start trial',
            'upgrade',
            'premium',
            'discount',
            'deal',
            'limited',
            'offer',
            'save',
            'coupon',
            'pricing',
            'plans',
        );

        foreach ( $marketing_signals as $s ) {
            if (
                $s !== '' &&
                ( strpos( $id, $s ) !== false || strpos( $slug, $s ) !== false || strpos( $msg, $s ) !== false )
            ) {
                return false;
            }
        }

        return $show;

    }, 10, 2 );

}, 20 );

// Remove upgrade/trial links from Plugins list action links - Ver 2.4.4
add_action( 'admin_init', function () {

    if ( ! KCHAT_FREEMIUS_QUIET_MODE ) {
        return;
    }

    // DIAG - Diagnostics - Ver 2.4.4
    back_trace("NOTICE", "Admin init - Remove upgrade/trial links from Plugins list action links");

    // Strongly preferred: set this in your main plugin file:
    // define( 'CHATBOT_CHATGPT_PLUGIN_FILE', __FILE__ );
    if ( defined( 'CHATBOT_CHATGPT_PLUGIN_FILE' ) ) {
        $plugin_file = CHATBOT_CHATGPT_PLUGIN_FILE;
    } else {
        // Safe fallback (works, but less ideal than the constant)
        $plugin_file = dirname( __FILE__ ) . '/chatbot-chatgpt.php';
        if ( ! file_exists( $plugin_file ) ) {
            return;
        }
    }

    $hook = 'plugin_action_links_' . plugin_basename( $plugin_file );

    add_filter( $hook, function ( $links ) {

        if ( ! is_array( $links ) ) {
            return $links;
        }

        return array_values( array_filter( $links, function ( $link ) {
            $l = strtolower( wp_strip_all_tags( (string) $link ) );

            return ! (
                strpos( $l, 'upgrade' ) !== false ||
                strpos( $l, 'trial' ) !== false ||
                strpos( $l, 'go premium' ) !== false ||
                strpos( $l, 'premium' ) !== false ||
                strpos( $l, 'pricing' ) !== false ||
                strpos( $l, 'plans' ) !== false
            );
        } ) );

    }, 100 );

}, 20 );

