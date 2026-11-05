<?php
/**
 * Admin warning that hosted OpenAI Prompt Objects retire on 30 November 2026.
 *
 * The notice uses the existing Bridge/Local resolver. It does not call OpenAI,
 * change assistant rows, or change the mode map. Dismissal is stored per
 * administrator and per site for the rows that still need migration. A mode
 * save that leaves none of those rows clears that site's dismissals, so a
 * later return to Bridge is shown again.
 *
 * @package chatbot-chatgpt
 */

if ( ! defined( 'WPINC' ) ) {
    die();
}

/**
 * User-meta key for one site's dismissal.
 *
 * User meta is global in Multisite, so the key includes the site ID.
 *
 * @param int|null $blog_id Site ID. Null uses the current site.
 * @return string
 */
function chatbot_chatgpt_pmpt_retirement_dismiss_meta_key( $blog_id = null ) {
    if ( null === $blog_id ) {
        $blog_id = function_exists( 'get_current_blog_id' ) ? get_current_blog_id() : 1;
    }
    $blog_id = (int) $blog_id;
    if ( $blog_id < 1 ) {
        $blog_id = 1;
    }
    return 'chatbot_chatgpt_pmpt_retire_dismiss_' . $blog_id;
}

/**
 * Support-tab URL for the Local-mode migration guide.
 *
 * @return string
 */
function chatbot_chatgpt_pmpt_retirement_guide_url() {
    return admin_url( 'admin.php?page=chatbot-chatgpt&tab=support&dir=assistants-local-mode&file=assistants-local-mode.md' );
}

/**
 * Saved pmpt_ rows for the current site.
 *
 * @return array<int,array<string,mixed>>
 */
function chatbot_chatgpt_pmpt_retirement_prompt_rows() {
    global $wpdb;
    $table = $wpdb->prefix . 'chatbot_chatgpt_assistants';
    $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
    if ( $exists !== $table ) {
        return array();
    }
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, assistant_id, additional_instructions FROM `{$table}` WHERE assistant_id LIKE %s",
            $wpdb->esc_like( 'pmpt_' ) . '%'
        ),
        ARRAY_A
    );
    return is_array( $rows ) ? $rows : array();
}

/**
 * Resolver result when a row still needs migration, or an empty string.
 *
 * Confirmed Local mode returns an empty string. asst_ rows do too.
 * Bridge, including a missing mode entry, and a Local entry that no longer
 * matches its row both still need attention.
 *
 * @param mixed $row Assistants-table row.
 * @return string
 */
function chatbot_chatgpt_pmpt_retirement_migration_state( $row ) {
    if ( ! is_array( $row ) || ! function_exists( 'chatbot_chatgpt_resolve_pmpt_responses_mode' ) ) {
        return '';
    }
    $assistant_id = isset( $row['assistant_id'] ) ? trim( (string) $row['assistant_id'] ) : '';
    if ( ! function_exists( 'chatbot_chatgpt_id_starts_with' ) || ! chatbot_chatgpt_id_starts_with( $assistant_id, 'pmpt_' ) ) {
        return '';
    }
    $row_id = isset( $row['id'] ) ? $row['id'] : null;
    $mode = chatbot_chatgpt_resolve_pmpt_responses_mode( $assistant_id, $row_id, $row );
    $result = ( is_array( $mode ) && isset( $mode['result'] ) ) ? (string) $mode['result'] : '';
    if ( 'bridge' === $result || 'error' === $result ) {
        return $result;
    }
    return '';
}

/**
 * Hash of the rows that still need migration.
 *
 * An empty string means the notice must not appear. The hash does not include
 * prompt IDs.
 *
 * @param mixed $rows Row arrays.
 * @return string
 */
function chatbot_chatgpt_pmpt_retirement_affected_signature( $rows ) {
    if ( ! is_array( $rows ) ) {
        return '';
    }
    $parts = array();
    foreach ( $rows as $row ) {
        $state = chatbot_chatgpt_pmpt_retirement_migration_state( $row );
        if ( '' === $state || ! is_array( $row ) ) {
            continue;
        }
        $normalized = function_exists( 'chatbot_chatgpt_normalize_assistant_row_id' )
            ? chatbot_chatgpt_normalize_assistant_row_id( isset( $row['id'] ) ? $row['id'] : null )
            : array( 'state' => 'invalid', 'id' => null );
        if ( 'valid' !== $normalized['state'] || $normalized['id'] < 1 ) {
            continue;
        }
        $parts[ $normalized['id'] ] = $normalized['id'] . ':' . $state;
    }
    if ( array() === $parts ) {
        return '';
    }
    ksort( $parts, SORT_NUMERIC );
    return hash( 'sha256', implode( '|', $parts ) );
}

/**
 * Whether this administrator already dismissed this exact migration set.
 *
 * @param int      $user_id User ID.
 * @param string   $signature Current affected-row signature.
 * @param int|null $blog_id Site ID. Null uses the current site.
 * @return bool
 */
function chatbot_chatgpt_pmpt_retirement_is_dismissed( $user_id, $signature, $blog_id = null ) {
    if ( ! is_string( $signature ) || '' === $signature || ! function_exists( 'get_user_meta' ) ) {
        return false;
    }
    $stored = get_user_meta( (int) $user_id, chatbot_chatgpt_pmpt_retirement_dismiss_meta_key( $blog_id ), true );
    return is_string( $stored ) && '' !== $stored && hash_equals( $signature, $stored );
}

/**
 * Record dismissal for one administrator and site.
 *
 * Does not write assistant rows or the mode map.
 *
 * @param int    $user_id User ID.
 * @param bool   $nonce_valid Whether the dismissal nonce checked out.
 * @param string $signature Current affected-row signature.
 * @return string dismissed, forbidden, invalid-nonce, or none.
 */
function chatbot_chatgpt_pmpt_retirement_dismiss_request( $user_id, $nonce_valid, $signature ) {
    $user_id = (int) $user_id;
    if ( $user_id < 1 || ! function_exists( 'user_can' ) || ! user_can( $user_id, 'manage_options' ) ) {
        return 'forbidden';
    }
    if ( true !== $nonce_valid ) {
        return 'invalid-nonce';
    }
    if ( ! is_string( $signature ) || '' === $signature ) {
        return 'none';
    }
    update_user_meta( $user_id, chatbot_chatgpt_pmpt_retirement_dismiss_meta_key(), $signature );
    return 'dismissed';
}

/**
 * Forget this site's dismissals when nothing still needs migration.
 *
 * A return to Bridge stores the same row state as the original Bridge row, so
 * the saved signature would match again. Clearing it only in that empty state
 * avoids a new option and does not run on ordinary admin page views.
 *
 * @param mixed $rows Candidate rows for the current site.
 * @return bool True when at least one dismissal was removed.
 */
function chatbot_chatgpt_pmpt_retirement_release_dismissals_when_clear( $rows ) {
    if ( '' !== chatbot_chatgpt_pmpt_retirement_affected_signature( $rows ) ) {
        return false;
    }
    global $wpdb;
    $key = chatbot_chatgpt_pmpt_retirement_dismiss_meta_key();
    $user_ids = $wpdb->get_col( $wpdb->prepare( "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s", $key ) );
    if ( ! is_array( $user_ids ) || array() === $user_ids ) {
        return false;
    }
    foreach ( $user_ids as $user_id ) {
        delete_user_meta( (int) $user_id, $key );
    }
    return true;
}

/**
 * Run the clear check after the mode map is saved.
 *
 * @return void
 */
function chatbot_chatgpt_pmpt_retirement_after_mode_save() {
    chatbot_chatgpt_pmpt_retirement_release_dismissals_when_clear( chatbot_chatgpt_pmpt_retirement_prompt_rows() );
}
$chatbot_chatgpt_pmpt_retirement_mode_option = chatbot_chatgpt_pmpt_row_mode_option_name();
add_action( 'add_option_' . $chatbot_chatgpt_pmpt_retirement_mode_option, 'chatbot_chatgpt_pmpt_retirement_after_mode_save' );
add_action( 'update_option_' . $chatbot_chatgpt_pmpt_retirement_mode_option, 'chatbot_chatgpt_pmpt_retirement_after_mode_save' );
add_action( 'delete_option_' . $chatbot_chatgpt_pmpt_retirement_mode_option, 'chatbot_chatgpt_pmpt_retirement_after_mode_save' );

/**
 * True on a site admin screen for someone who can manage chatbot settings.
 *
 * Network admin screens are skipped. The plugin file is loaded only where the
 * plugin is active, so this does not assume network activation.
 *
 * @return bool
 */
function chatbot_chatgpt_pmpt_retirement_screen_allows_notice() {
    if ( function_exists( 'is_network_admin' ) && is_network_admin() ) {
        return false;
    }
    if ( ! is_admin() ) {
        return false;
    }
    return current_user_can( 'manage_options' );
}

/**
 * Notice markup, or an empty string when there is nothing to show.
 *
 * @param mixed $rows Candidate rows. The caller supplies the current site's rows.
 * @return string
 */
function chatbot_chatgpt_pmpt_retirement_notice_html( $rows ) {
    $signature = chatbot_chatgpt_pmpt_retirement_affected_signature( $rows );
    if ( '' === $signature ) {
        return '';
    }
    if ( chatbot_chatgpt_pmpt_retirement_is_dismissed( get_current_user_id(), $signature ) ) {
        return '';
    }

    $guide = chatbot_chatgpt_pmpt_retirement_guide_url();
    $dismiss = wp_nonce_url(
        add_query_arg( 'chatbot_chatgpt_dismiss_pmpt_retirement', '1', admin_url( 'admin.php' ) ),
        'chatbot_chatgpt_dismiss_pmpt_retirement'
    );

    $html  = '<div class="notice notice-warning">';
    $html .= '<p><strong>' . esc_html__( 'Kognetiks Chatbot — Action Required', 'chatbot-chatgpt' ) . '</strong></p>';
    $html .= '<p>' . sprintf(
        /* translators: %s: retirement date */
        esc_html__( 'OpenAI Prompt Objects retire on %s.', 'chatbot-chatgpt' ),
        '<strong>' . esc_html__( 'November 30, 2026', 'chatbot-chatgpt' ) . '</strong>'
    ) . '</p>';
    $html .= '<p>' . sprintf(
        /* translators: %s: pmpt_ prefix, shown as an example rather than a saved ID */
        esc_html__( 'If your chatbot uses an OpenAI Prompt ID beginning with %s, review its configuration and migrate from Bridge to Local mode before the deadline.', 'chatbot-chatgpt' ),
        '<code>pmpt_</code>'
    ) . '</p>';
    $html .= '<p>' . esc_html__( 'Switching modes does not automatically copy your hosted prompt\'s instructions, model, tools, or other settings.', 'chatbot-chatgpt' ) . '</p>';
    $html .= '<p><a href="' . esc_url( $guide ) . '">' . esc_html__( 'Read migration guide', 'chatbot-chatgpt' ) . '</a>';
    $html .= ' <a href="' . esc_url( $dismiss ) . '">' . esc_html__( 'Dismiss', 'chatbot-chatgpt' ) . '</a></p>';
    $html .= '</div>';
    return $html;
}

/**
 * Print the notice on site admin screens.
 *
 * @return void
 */
function chatbot_chatgpt_pmpt_retirement_admin_notice() {
    if ( ! chatbot_chatgpt_pmpt_retirement_screen_allows_notice() ) {
        return;
    }
    $html = chatbot_chatgpt_pmpt_retirement_notice_html( chatbot_chatgpt_pmpt_retirement_prompt_rows() );
    if ( '' === $html ) {
        return;
    }
    echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the builder.
}
add_action( 'admin_notices', 'chatbot_chatgpt_pmpt_retirement_admin_notice' );

/**
 * Handle the nonce-protected Dismiss link.
 *
 * @return void
 */
function chatbot_chatgpt_pmpt_retirement_handle_dismiss() {
    if ( ! isset( $_GET['chatbot_chatgpt_dismiss_pmpt_retirement'] ) ) {
        return;
    }
    if ( function_exists( 'is_network_admin' ) && is_network_admin() ) {
        return;
    }
    $nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
    $valid = is_string( $nonce ) && false !== wp_verify_nonce( $nonce, 'chatbot_chatgpt_dismiss_pmpt_retirement' );
    $signature = chatbot_chatgpt_pmpt_retirement_affected_signature( chatbot_chatgpt_pmpt_retirement_prompt_rows() );
    chatbot_chatgpt_pmpt_retirement_dismiss_request( get_current_user_id(), $valid, $signature );
    if ( 'cli' === PHP_SAPI || headers_sent() ) {
        return;
    }
    wp_safe_redirect( remove_query_arg( array( 'chatbot_chatgpt_dismiss_pmpt_retirement', '_wpnonce' ) ) );
    exit;
}
add_action( 'admin_init', 'chatbot_chatgpt_pmpt_retirement_handle_dismiss' );
