<?php
/**
 * Kognetiks Chatbot - Vendor Management (Freemius “Quiet Proof” Controls) - Ver 2.4.4
 *
 * BOTH WORLDS MODE:
 * - Throttle Freemius trial promo timing (first show + reshow cadence)
 * - Gate ANY Freemius trial promo / marketing notices behind “proof”
 * - Keep critical notices (errors/warnings)
 * - Remove Freemius upgrade/trial links from the Plugins list action links
 *
 * Assumes Freemius is bootstrapped in the main plugin file and that file calls:
 * do_action( 'chatbot_chatgpt_freemius_loaded' );
 *
 * @package chatbot-chatgpt
 */

if ( ! defined( 'WPINC' ) ) {
    die();
}

// Tunables - Ver 2.4.4
if ( ! defined( 'KCHAT_FREEMIUS_QUIET_MODE' ) ) {
    define( 'KCHAT_FREEMIUS_QUIET_MODE', true );
}

// Proof Unlocked
function kchat_has_unlock_proof() {
    global $wpdb;

    // Must have logging enabled
    if ( get_option('chatbot_chatgpt_enable_conversation_logging', 'Off') !== 'On' ) {
        return false;
    }

    $table = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';

    // If table doesn't exist yet, no proof
    $exists = $wpdb->get_var( $wpdb->prepare(
        "SHOW TABLES LIKE %s",
        $table
    ) );

    if ( empty( $exists ) ) {
        return false;
    }

    // 5 rows per conversation
    $row_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
    $conversation_count = (int) floor( $row_count / 5 );

    return ( $conversation_count >= 10 );
}

/**
 * Proof gate:
 * Return true only when you want Freemius to be allowed to surface conversion messaging.
 *
 * Default behavior (safe):
 * - If kchat_has_unlock_proof() exists (your “>= 10 conversations + logging enabled” check), use it.
 * - Otherwise, return false (stay quiet).
 */
if ( ! function_exists( 'kchat_freemius_proof_gate' ) ) {
    function kchat_freemius_proof_gate() {
        if ( function_exists( 'kchat_has_unlock_proof' ) ) {
            return (bool) kchat_has_unlock_proof();
        }
        return false;
    }
}

// Quiet Proof controls (runs after Freemius loads) - Ver 2.4.4
add_action( 'chatbot_chatgpt_freemius_loaded', function () {

    // DIAG - Diagnostics - Ver 2.4.4
    if ( function_exists( 'back_trace' ) ) {
        back_trace( "NOTICE", "Freemius loaded (vendor management)" );
    }

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
     * BOTH WORLDS PART 1: Throttle trial promo timing.
     * - First trial promo: delay to 14 days
     * - Reshow after dismissal: 180 days
     */
    $fs->add_filter( 'show_first_trial_after_n_sec', function( $default_sec ) {
        return 14 * 24 * 60 * 60;
    } );

    $fs->add_filter( 'reshow_trial_after_every_n_sec', function( $default_sec ) {
        return 180 * 24 * 60 * 60;
    } );

    /**
     * BOTH WORLDS PART 2: Gate marketing notices behind proof.
     * - Allow critical notices (errors/warnings) always
     * - Block trial/upgrade/discount style notices until proof gate passes
     * - Once proof gate passes, allow Freemius to show (but still throttled by timing above)
     */
    $fs->add_filter( 'show_admin_notice', function ( $show, $notice ) {

        if ( ! $show ) {
            return $show;
        }

        // Always allow critical/system notices.
        $type  = $notice['type']  ?? '';
        $level = $notice['level'] ?? '';

        if ( in_array( $type, array( 'error', 'warning' ), true ) ) {
            return $show;
        }
        if ( in_array( $level, array( 'error', 'warning' ), true ) ) {
            return $show;
        }

        // Identify if this looks like marketing / conversion.
        $id   = strtolower( (string) ( $notice['id']   ?? '' ) );
        $slug = strtolower( (string) ( $notice['slug'] ?? '' ) );
        $msg  = strtolower( wp_strip_all_tags( (string) ( $notice['message'] ?? '' ) ) );

        // Freemius “trial promotion” notice id (known).
        $is_trial_promo = ( isset( $notice['id'] ) && 'trial_promotion' === $notice['id'] );

        // General marketing signals (avoid blocking everything unnecessarily).
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

        $looks_marketing = $is_trial_promo;

        if ( ! $looks_marketing ) {
            foreach ( $marketing_signals as $s ) {
                if (
                    $s !== '' &&
                    ( strpos( $id, $s ) !== false || strpos( $slug, $s ) !== false || strpos( $msg, $s ) !== false )
                ) {
                    $looks_marketing = true;
                    break;
                }
            }
        }

        // If it looks marketing, only show it after proof gate passes.
        if ( $looks_marketing ) {
            return kchat_freemius_proof_gate();
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
    if ( function_exists( 'back_trace' ) ) {
        back_trace( "NOTICE", "Admin init - Remove upgrade/trial links from Plugins list action links" );
    }

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

