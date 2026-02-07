<?php
/**
 * Kognetiks Chatbot - Vendor Management (Freemius UI OFF + Quiet Proof Foundation) - Ver 2.4.4
 *
 * Goal:
 * - Freemius remains the licensing + checkout rail.
 * - Freemius stops acting like the product’s marketing voice.
 *
 * What this file does:
 * - Suppresses Freemius promotional surfaces (banners/notices/nags)
 * - Keeps critical notices (errors/warnings)
 * - Removes Freemius upgrade/trial links from Plugins list action links
 * - Provides "proof" helpers you will use for your own Proof → Unlock → Trial flow
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
if ( ! defined( 'KCHAT_FREEMIUS_QUIET_MODE' ) ) {
    // When true: suppress Freemius marketing UI surfaces
    define( 'KCHAT_FREEMIUS_QUIET_MODE', true );
}

// Proof Unlocked
if ( ! function_exists( 'kchat_has_unlock_proof' ) ) {
    function kchat_has_unlock_proof() {
        global $wpdb;

        // Must have logging enabled
        if ( get_option( 'chatbot_chatgpt_enable_conversation_logging', 'Off' ) !== 'On' ) {
            return false;
        }

        $table = $wpdb->prefix . 'chatbot_chatgpt_conversation_log';

        // If table doesn't exist yet, no proof
        $exists = $wpdb->get_var(
            $wpdb->prepare( "SHOW TABLES LIKE %s", $table )
        );

        if ( empty( $exists ) ) {
            return false;
        }

        // Current assumption: 5 rows per conversation
        $row_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
        $conversation_count = (int) floor( $row_count / 5 );

        return ( $conversation_count >= 10 );
    }
}

// Proof gate (you’ll use this for your own notices/UX)
if ( ! function_exists( 'kchat_freemius_proof_gate' ) ) {
    function kchat_freemius_proof_gate() {
        return function_exists( 'kchat_has_unlock_proof' ) ? (bool) kchat_has_unlock_proof() : false;
    }
}

// Freemius: suppress marketing UI surfaces
add_action( 'chatbot_chatgpt_freemius_loaded', function () {

    // if ( function_exists( 'back_trace' ) ) {
    //     back_trace( "NOTICE", "Freemius loaded (vendor management - UI OFF)" );
    // }

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
     * 1) Suppress Freemius admin notices that are marketing.
     *    Keep errors/warnings.
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

        // Block promotional notices.
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

        // Known Freemius trial promo notice id
        if ( isset( $notice['id'] ) && 'trial_promotion' === $notice['id'] ) {
            return false;
        }

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

    // 2) Suppress deactivation feedback / cancellation nags (optional but “quiet”).
    $fs->add_filter( 'show_deactivation_feedback_form', '__return_false' );
    $fs->add_filter( 'show_deactivation_subscription_cancellation', '__return_false' );

}, 20 );

// 3) CSS fallback to hide Freemius top banners that bypass show_admin_notice.
//    This is the banner you showed: "Hey! How do you like ... Start free trial"
//
// We scope this to wp-admin. If you want it only on specific screens, we can narrow it.
add_action( 'admin_head', function () {

    if ( ! KCHAT_FREEMIUS_QUIET_MODE ) {
        return;
    }

    // Only in wp-admin
    if ( ! is_admin() ) {
        return;
    }

    // If Freemius isn't present, nothing to do
    if ( ! function_exists( 'chatbot_chatgpt_freemius' ) ) {
        return;
    }

    echo '<style>
        /* Freemius marketing banner / promo surfaces (best-effort selectors) */
        .fs-notice, 
        .fs-admin-notice, 
        .fs-admin-notice-wrapper,
        .fs-upgrade-notice,
        .fs-trial-notice,
        .fs-promo-notice,
        .fs-trial-promotion,
        #fs_connect, 
        #fs_connect_wrapper,
        .fs-plugin-tab-upgrade,
        .fs-upgrade,
        .fs-trial
        { display: none !important; }
    </style>';
}, 50 );

// Remove upgrade/trial links from Plugins list action links
add_action( 'admin_init', function () {

    if ( ! KCHAT_FREEMIUS_QUIET_MODE ) {
        return;
    }

    // if ( function_exists( 'back_trace' ) ) {
    //     back_trace( "NOTICE", "Admin init - Remove upgrade/trial links from Plugins list action links" );
    // }

    if ( defined( 'CHATBOT_CHATGPT_PLUGIN_FILE' ) ) {
        $plugin_file = CHATBOT_CHATGPT_PLUGIN_FILE;
    } else {
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
