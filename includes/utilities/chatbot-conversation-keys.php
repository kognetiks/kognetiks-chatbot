<?php
/**
 * Kognetiks Chatbot - Conversation isolation keys
 *
 * Anonymous visitors share WordPress user ID 0. Conversation storage, locks,
 * and queues must be keyed by a verified visitor identity plus page and
 * assistant, never by that shared user ID alone.
 *
 * @package chatbot-chatgpt
 */

if ( ! defined( 'WPINC' ) ) {
    die();
}

/**
 * Signed visitor cookie name. The value is v2.{id}.{hmac}, not a trusted key by itself.
 *
 * @return string
 */
function kognetiks_visitor_cookie_name() {
    return 'kognetiks_unique_id';
}

/**
 * HMAC for a server-issued visitor id. The raw id is not a secret; the signature is.
 *
 * @param string $visitor_id 32 hex characters.
 * @return string
 */
function kognetiks_sign_visitor_id( $visitor_id ) {
    return hash_hmac( 'sha256', 'kognetiks-visitor-v2|' . $visitor_id, wp_salt( 'auth' ) );
}

/**
 * Return the visitor id when the cookie is a valid v2 token, otherwise an empty string.
 * Unsigned or forged cookies are rejected. A matching regular expression is not enough.
 *
 * @param mixed $raw Cookie value.
 * @return string
 */
function kognetiks_parse_verified_visitor_cookie( $raw ) {
    if ( ! is_string( $raw ) || $raw === '' ) {
        return '';
    }

    $parts = explode( '.', $raw );
    if ( count( $parts ) !== 3 || $parts[0] !== 'v2' ) {
        return '';
    }

    $visitor_id = $parts[1];
    $signature  = $parts[2];
    if ( ! preg_match( '/^[a-f0-9]{32}$/', $visitor_id ) || ! preg_match( '/^[a-f0-9]{64}$/', $signature ) ) {
        return '';
    }

    $expected = kognetiks_sign_visitor_id( $visitor_id );
    if ( strlen( $expected ) !== strlen( $signature ) || ! hash_equals( $expected, $signature ) ) {
        return '';
    }

    return $visitor_id;
}

/**
 * Issue a new signed visitor cookie and mirror it into $_COOKIE for this request.
 *
 * @return string Visitor id, or empty string if generation failed.
 */
function kognetiks_issue_visitor_cookie() {
    try {
        $visitor_id = bin2hex( random_bytes( 16 ) );
    } catch ( Exception $e ) {
        return '';
    }

    if ( ! preg_match( '/^[a-f0-9]{32}$/', $visitor_id ) ) {
        return '';
    }

    $token = 'v2.' . $visitor_id . '.' . kognetiks_sign_visitor_id( $visitor_id );

    if ( ! headers_sent() ) {
        $cookie_args = array(
            'expires'  => time() + ( 86400 * 30 ),
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax',
        );
        @setcookie( kognetiks_visitor_cookie_name(), $token, $cookie_args ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
    }

    $_COOKIE[ kognetiks_visitor_cookie_name() ] = $token;
    return $visitor_id;
}

/**
 * Replace a missing or unsigned visitor cookie. Existing valid cookies are kept.
 *
 * @return void
 */
function kognetiks_assign_unique_id() {
    if ( ! function_exists( 'wp_salt' ) ) {
        return;
    }

    $raw = isset( $_COOKIE[ kognetiks_visitor_cookie_name() ] ) ? wp_unslash( $_COOKIE[ kognetiks_visitor_cookie_name() ] ) : '';
    if ( kognetiks_parse_verified_visitor_cookie( $raw ) === '' ) {
        kognetiks_issue_visitor_cookie();
    }

    // Plugin load runs before wp_salt() exists, so the verified id is published here.
    $verified = kognetiks_get_unique_id();
    if ( ! is_string( $verified ) || $verified === '' ) {
        return;
    }

    global $session_id, $user_id;
    $session_id = $verified;
    if ( empty( $user_id ) || $user_id == 0 ) {
        $user_id = $verified;
    }
}

/**
 * Verified visitor id for this request, or null when the cookie is missing or forged.
 *
 * @return string|null
 */
function kognetiks_get_unique_id() {
    $raw = isset( $_COOKIE[ kognetiks_visitor_cookie_name() ] ) ? wp_unslash( $_COOKIE[ kognetiks_visitor_cookie_name() ] ) : '';
    $visitor_id = kognetiks_parse_verified_visitor_cookie( $raw );
    return $visitor_id === '' ? null : $visitor_id;
}

/**
 * WordPress user IDs are short decimal strings. Visitor tokens are not.
 *
 * @param mixed $user_id Candidate user id.
 * @return int
 */
function chatbot_chatgpt_normalize_wp_user_id( $user_id ) {
    if ( is_int( $user_id ) ) {
        return $user_id > 0 ? $user_id : 0;
    }
    if ( is_string( $user_id ) && preg_match( '/^[1-9][0-9]{0,9}$/', $user_id ) ) {
        return (int) $user_id;
    }
    return 0;
}

/**
 * Bound a page or assistant component before it is hashed into a storage key.
 *
 * @param mixed $value Raw component.
 * @return string
 */
function chatbot_chatgpt_normalize_storage_part( $value ) {
    if ( ! is_scalar( $value ) ) {
        return '';
    }
    $value = trim( (string) $value );
    if ( strlen( $value ) > 191 ) {
        $value = substr( $value, 0, 191 );
    }
    return $value;
}

/**
 * The posted session id must be the visitor id from this request's signed cookie.
 *
 * @param mixed $session_id Session id from the request.
 * @return bool
 */
function verify_session_ownership( $session_id ) {
    if ( ! is_string( $session_id ) ) {
        return false;
    }
    $session_id = trim( $session_id );
    if ( $session_id === '' || strlen( $session_id ) > 128 ) {
        return false;
    }
    $verified = kognetiks_get_unique_id();
    if ( ! is_string( $verified ) || $verified === '' || strlen( $verified ) !== strlen( $session_id ) ) {
        return false;
    }
    return hash_equals( $verified, $session_id );
}

/**
 * A caller may use a conversation when they are that authenticated user,
 * or an anonymous visitor whose session matches the signed cookie.
 *
 * @param mixed       $user_id    WordPress user id, or 0 for a visitor.
 * @param mixed       $page_id    Page id. Ownership is the actor, not the page record.
 * @param string|null $session_id Visitor session. Required for anonymous callers.
 * @return bool
 */
function verify_conversation_ownership( $user_id, $page_id, $session_id = null ) {
    unset( $page_id );
    $uid = chatbot_chatgpt_normalize_wp_user_id( $user_id );
    if ( $uid > 0 ) {
        return (int) get_current_user_id() === $uid;
    }
    return verify_session_ownership( is_string( $session_id ) ? $session_id : '' );
}

/**
 * Actor token for storage. Anonymous callers must present the signed cookie.
 * Privileged lock cleanup may pass an already-authorized 32-hex session after a capability check.
 *
 * @param mixed  $user_id User id.
 * @param mixed  $session_id Session id.
 * @param bool   $session_already_authorized Skip the cookie comparison. Never use this for conversation reads.
 * @return string
 */
function chatbot_chatgpt_conversation_actor_token( $user_id, $session_id, $session_already_authorized = false ) {
    $uid = chatbot_chatgpt_normalize_wp_user_id( $user_id );
    if ( $uid > 0 ) {
        $current = function_exists( 'get_current_user_id' ) ? (int) get_current_user_id() : 0;
        // Conversation reads require the authenticated user. Privileged lock cleanup may name another user
        // only after the caller has already enforced manage_options.
        if ( $current !== $uid && ! $session_already_authorized ) {
            return '';
        }
        return 'user:' . $uid;
    }

    if ( ! is_string( $session_id ) || ! preg_match( '/^[a-f0-9]{32}$/', $session_id ) ) {
        return '';
    }
    if ( ! $session_already_authorized && ! verify_session_ownership( $session_id ) ) {
        return '';
    }
    return 'anon:' . $session_id;
}

/**
 * Deterministic transient name. The raw session id is an HMAC input, not the key.
 *
 * @param string $prefix Transient prefix.
 * @param string $purpose Domain separation.
 * @param mixed  $user_id User id.
 * @param mixed  $session_id Session id.
 * @param mixed  $page_id Page id.
 * @param mixed  $assistant_id Assistant or prompt id.
 * @param bool   $session_already_authorized See chatbot_chatgpt_conversation_actor_token().
 * @return string Empty when the actor cannot be verified.
 */
function chatbot_chatgpt_scoped_transient_key( $prefix, $purpose, $user_id, $session_id, $page_id, $assistant_id, $session_already_authorized = false ) {
    $actor = chatbot_chatgpt_conversation_actor_token( $user_id, $session_id, $session_already_authorized );
    if ( $actor === '' ) {
        return '';
    }

    $material = $purpose
        . '|' . $actor
        . '|' . chatbot_chatgpt_normalize_storage_part( $page_id )
        . '|' . chatbot_chatgpt_normalize_storage_part( $assistant_id );
    $hash = substr( hash_hmac( 'sha256', $material, wp_salt( 'auth' ) ), 0, 32 );
    $key  = $prefix . $hash;

    // WordPress transient names must stay under 172 characters.
    if ( strlen( $key ) > 172 ) {
        return '';
    }
    return $key;
}

/**
 * Live OpenAI conversation (cnv_) storage key.
 *
 * @param mixed $user_id User id.
 * @param mixed $session_id Session id.
 * @param mixed $page_id Page id.
 * @param mixed $assistant_id Assistant or prompt id.
 * @return string
 */
function chatbot_chatgpt_conversation_storage_key( $user_id, $session_id, $page_id, $assistant_id ) {
    return chatbot_chatgpt_scoped_transient_key( 'kchat_cnv_', 'kchat-conv-v1', $user_id, $session_id, $page_id, $assistant_id, false );
}

/**
 * Processing lock used by the message handler.
 *
 * @param mixed $user_id User id.
 * @param mixed $session_id Session id.
 * @param mixed $page_id Page id.
 * @param mixed $assistant_id Assistant id.
 * @param bool  $session_already_authorized Privileged cleanup only.
 * @return string
 */
function chatbot_chatgpt_conversation_processing_lock_key( $user_id, $session_id, $page_id, $assistant_id, $session_already_authorized = false ) {
    return chatbot_chatgpt_scoped_transient_key( 'chatgpt_conv_lock_', 'kchat-lock-v1', $user_id, $session_id, $page_id, $assistant_id, $session_already_authorized );
}

/**
 * Responses API in-call lock.
 *
 * @param mixed $user_id User id.
 * @param mixed $session_id Session id.
 * @param mixed $page_id Page id.
 * @param mixed $assistant_id Assistant id.
 * @return string
 */
function chatbot_chatgpt_responses_conv_lock_key( $user_id, $session_id, $page_id, $assistant_id ) {
    return chatbot_chatgpt_scoped_transient_key( 'kchat_conv_lock_', 'kchat-resp-lock-v1', $user_id, $session_id, $page_id, $assistant_id, false );
}

/**
 * Per-visitor message queue key.
 *
 * @param mixed $user_id User id.
 * @param mixed $session_id Session id.
 * @param mixed $page_id Page id.
 * @param mixed $assistant_id Assistant id.
 * @param bool  $session_already_authorized Privileged cleanup only.
 * @return string
 */
function chatbot_chatgpt_message_queue_key( $user_id, $session_id, $page_id, $assistant_id, $session_already_authorized = false ) {
    return chatbot_chatgpt_scoped_transient_key( 'chatbot_message_queue_', 'kchat-queue-v1', $user_id, $session_id, $page_id, $assistant_id, $session_already_authorized );
}

/**
 * Stable pseudonymous safety identifier. Not the raw cookie or user id.
 *
 * @param mixed $user_id User id.
 * @param mixed $session_id Session id.
 * @return string Empty when the actor is not verified.
 */
function chatbot_chatgpt_safety_identifier( $user_id, $session_id ) {
    $actor = chatbot_chatgpt_conversation_actor_token( $user_id, $session_id, false );
    if ( $actor === '' ) {
        return '';
    }
    return hash_hmac( 'sha256', 'kchat-safety-v1|' . $actor, wp_salt( 'auth' ) );
}

/**
 * Conversation transient lifetime, bounded to the existing retention setting.
 *
 * @return int Seconds.
 */
function chatbot_chatgpt_thread_retention_seconds() {
    $hours = 36;
    if ( function_exists( 'get_option' ) ) {
        $hours = (int) get_option( 'chatbot_chatgpt_thread_retention_period', 36 );
    }
    if ( $hours < 1 ) {
        $hours = 36;
    }
    if ( $hours > 720 ) {
        $hours = 720;
    }
    $hour = defined( 'HOUR_IN_SECONDS' ) ? (int) HOUR_IN_SECONDS : 3600;
    return $hours * $hour;
}

/**
 * Legacy shared anonymous thread key. Never read this. It may contain a conversation
 * that several visitors already shared.
 *
 * @param mixed $page_id Page id.
 * @return string
 */
function chatbot_chatgpt_legacy_anonymous_thread_key( $page_id ) {
    $page_id = chatbot_chatgpt_normalize_storage_part( $page_id );
    if ( ! preg_match( '/^[0-9]{1,20}$/', $page_id ) ) {
        return '';
    }
    return 'chatbot_chatgpt_thread_id_0_' . $page_id;
}

/**
 * Delete the obsolete shared anonymous transients for one page.
 * Does not call OpenAI and does not touch conversation logs.
 *
 * @param mixed $page_id Page id.
 * @return void
 */
function chatbot_chatgpt_delete_legacy_anonymous_keys_for_page( $page_id ) {
    $thread_key = chatbot_chatgpt_legacy_anonymous_thread_key( $page_id );
    if ( $thread_key === '' ) {
        return;
    }
    delete_transient( $thread_key );
    delete_transient( 'chatbot_chatgpt_assistant_id_0_' . chatbot_chatgpt_normalize_storage_part( $page_id ) );
}

/**
 * True for the site-wide Chat Completions history buffers.
 *
 * @param mixed $transient_name Transient name.
 * @return bool
 */
function chatbot_chatgpt_is_shared_context_history_name( $transient_name ) {
    return $transient_name === 'chatbot_chatgpt_context_history'
        || $transient_name === 'chatbot_azure_context_history';
}

/**
 * Drop the legacy shared history buffers. They mixed every visitor's messages.
 *
 * @return void
 */
function chatbot_chatgpt_discard_shared_context_history() {
    delete_transient( 'chatbot_chatgpt_context_history' );
    delete_transient( 'chatbot_azure_context_history' );
}

/**
 * Per-visitor history key. Empty when the actor is not verified, so callers do not
 * fall back to the shared transient.
 *
 * @param string      $base_name Legacy transient name.
 * @param string|null $session_id Visitor id. Null uses the current request global.
 * @param mixed       $user_id WordPress user id. Null uses the current request global.
 * @return string
 */
function chatbot_chatgpt_visitor_context_history_key( $base_name = 'chatbot_chatgpt_context_history', $session_id = null, $user_id = null ) {
    if ( ! chatbot_chatgpt_is_shared_context_history_name( $base_name ) ) {
        return '';
    }

    delete_transient( $base_name );

    if ( $user_id === null && array_key_exists( 'user_id', $GLOBALS ) ) {
        $user_id = $GLOBALS['user_id'];
    }
    if ( $session_id === null && array_key_exists( 'session_id', $GLOBALS ) ) {
        $session_id = $GLOBALS['session_id'];
    }

    $actor = chatbot_chatgpt_conversation_actor_token( $user_id, is_string( $session_id ) ? $session_id : '', false );
    if ( $actor === '' ) {
        return '';
    }

    $purpose = ( $base_name === 'chatbot_azure_context_history' ) ? 'kchat-azure-ctx-v1' : 'kchat-context-v1';
    return 'kchat_ctx_' . substr( hash_hmac( 'sha256', $purpose . '|' . $actor, wp_salt( 'auth' ) ), 0, 32 );
}

/**
 * Read one visitor's Chat Completions history. Never returns the legacy shared buffer.
 *
 * @param string      $base_name Legacy transient name.
 * @param string|null $session_id Visitor id.
 * @param mixed       $user_id WordPress user id.
 * @return array
 */
function chatbot_chatgpt_read_context_history_entries( $base_name, $session_id = null, $user_id = null ) {
    $key = chatbot_chatgpt_visitor_context_history_key( $base_name, $session_id, $user_id );
    if ( $key === '' ) {
        return array();
    }
    $history = get_transient( $key );
    return is_array( $history ) ? $history : array();
}

/**
 * Append one visitor or assistant message to that visitor's history.
 *
 * @param string      $base_name Legacy transient name.
 * @param mixed       $new_entry Message text.
 * @param string|null $session_id Visitor id.
 * @param mixed       $user_id WordPress user id.
 * @return bool
 */
function chatbot_chatgpt_append_context_history_entry( $base_name, $new_entry, $session_id = null, $user_id = null ) {
    $key = chatbot_chatgpt_visitor_context_history_key( $base_name, $session_id, $user_id );
    if ( $key === '' ) {
        return false;
    }

    $context_history = get_transient( $key );
    if ( ! is_array( $context_history ) ) {
        $context_history = array();
    }

    $total_length = 0;
    foreach ( $context_history as $entry ) {
        if ( is_string( $entry ) ) {
            $total_length += strlen( $entry );
        } elseif ( is_array( $entry ) ) {
            $encoded = wp_json_encode( $entry );
            $total_length += is_string( $encoded ) ? strlen( $encoded ) : 0;
        }
    }

    $max_entries = 30;
    if ( $total_length > 5000 ) {
        $max_entries = 20;
    }
    if ( $total_length > 10000 ) {
        $max_entries = 10;
    }

    while ( count( $context_history ) >= $max_entries ) {
        array_shift( $context_history );
    }

    if ( is_array( $new_entry ) ) {
        $encoded = wp_json_encode( $new_entry );
        $new_entry = is_string( $encoded ) ? $encoded : '';
    }

    $context_history[] = $new_entry;
    set_transient( $key, $context_history );
    return true;
}

/**
 * One-time removal of every shared anonymous conversation transient.
 *
 * @return void
 */
function chatbot_chatgpt_retire_shared_anonymous_conversation_keys() {
    chatbot_chatgpt_discard_shared_context_history();

    if ( get_option( 'chatbot_chatgpt_anon_conv_keys_retired_v1' ) === '1' ) {
        return;
    }

    global $wpdb;
    if ( ! isset( $wpdb ) || ! is_object( $wpdb ) || empty( $wpdb->options ) || ! method_exists( $wpdb, 'query' ) ) {
        return;
    }

    $likes = array(
        $wpdb->esc_like( '_transient_chatbot_chatgpt_thread_id_0_' ) . '%',
        $wpdb->esc_like( '_transient_timeout_chatbot_chatgpt_thread_id_0_' ) . '%',
        $wpdb->esc_like( '_transient_chatbot_chatgpt_assistant_id_0_' ) . '%',
        $wpdb->esc_like( '_transient_timeout_chatbot_chatgpt_assistant_id_0_' ) . '%',
    );

    foreach ( $likes as $like ) {
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $like
            )
        );
    }

    update_option( 'chatbot_chatgpt_anon_conv_keys_retired_v1', '1', false );
}

/**
 * Authenticated conversations used to omit the assistant id. Reuse that row only
 * when the stored assistant matches, then copy it onto the isolated key.
 *
 * @param int    $user_id Authenticated user id.
 * @param mixed  $page_id Page id.
 * @param mixed  $assistant_id Requested assistant id.
 * @return string Conversation id, or empty.
 */
function chatbot_chatgpt_migrate_legacy_authenticated_thread( $user_id, $page_id, $assistant_id ) {
    $user_id = chatbot_chatgpt_normalize_wp_user_id( $user_id );
    $page_id = chatbot_chatgpt_normalize_storage_part( $page_id );
    if ( $user_id <= 0 || ! preg_match( '/^[0-9]{1,20}$/', $page_id ) ) {
        return '';
    }
    if ( (int) get_current_user_id() !== $user_id ) {
        return '';
    }

    $legacy_thread_key    = 'chatbot_chatgpt_thread_id_' . $user_id . '_' . $page_id;
    $legacy_assistant_key = 'chatbot_chatgpt_assistant_id_' . $user_id . '_' . $page_id;
    $stored_assistant     = get_transient( $legacy_assistant_key );
    $requested_assistant  = chatbot_chatgpt_normalize_storage_part( $assistant_id );

    if ( ! is_string( $stored_assistant ) || $stored_assistant !== $requested_assistant ) {
        return '';
    }

    $legacy_thread = get_transient( $legacy_thread_key );
    if ( ! is_string( $legacy_thread ) || $legacy_thread === '' ) {
        return '';
    }

    return $legacy_thread;
}

/**
 * Delete a legacy authenticated thread only when it belongs to this assistant.
 *
 * @param int   $user_id User id.
 * @param mixed $page_id Page id.
 * @param mixed $assistant_id Assistant id.
 * @return void
 */
function chatbot_chatgpt_delete_matching_legacy_authenticated_thread( $user_id, $page_id, $assistant_id ) {
    $user_id = chatbot_chatgpt_normalize_wp_user_id( $user_id );
    $page_id = chatbot_chatgpt_normalize_storage_part( $page_id );
    if ( $user_id <= 0 || ! preg_match( '/^[0-9]{1,20}$/', $page_id ) ) {
        return;
    }

    $legacy_assistant_key = 'chatbot_chatgpt_assistant_id_' . $user_id . '_' . $page_id;
    $stored_assistant     = get_transient( $legacy_assistant_key );
    $requested_assistant  = chatbot_chatgpt_normalize_storage_part( $assistant_id );
    if ( ! is_string( $stored_assistant ) || $stored_assistant !== $requested_assistant ) {
        return;
    }

    delete_transient( 'chatbot_chatgpt_thread_id_' . $user_id . '_' . $page_id );
    delete_transient( $legacy_assistant_key );
}

/**
 * Option name for the create-race lock. Bounded and derived from the storage key.
 *
 * @param string $storage_key Isolated conversation key.
 * @return string
 */
function chatbot_chatgpt_conversation_create_lock_option( $storage_key ) {
    return 'kchat_cnv_create_' . substr( hash_hmac( 'sha256', 'create|' . $storage_key, wp_salt( 'auth' ) ), 0, 32 );
}

/**
 * Claim the right to create one conversation. A stale lock older than 30 seconds can be replaced.
 *
 * @param string $storage_key Isolated conversation key.
 * @return bool
 */
function chatbot_chatgpt_claim_conversation_create_lock( $storage_key ) {
    if ( $storage_key === '' ) {
        return false;
    }
    $option = chatbot_chatgpt_conversation_create_lock_option( $storage_key );
    if ( add_option( $option, (string) time(), '', 'no' ) ) {
        return true;
    }

    $existing = get_option( $option, '' );
    $stamp    = is_numeric( $existing ) ? (int) $existing : 0;
    if ( $stamp > 0 && ( time() - $stamp ) > 30 ) {
        delete_option( $option );
        return (bool) add_option( $option, (string) time(), '', 'no' );
    }
    return false;
}

/**
 * Release a conversation-create lock.
 *
 * @param string $storage_key Isolated conversation key.
 * @return void
 */
function chatbot_chatgpt_release_conversation_create_lock( $storage_key ) {
    if ( $storage_key === '' ) {
        return;
    }
    delete_option( chatbot_chatgpt_conversation_create_lock_option( $storage_key ) );
}

/**
 * Reuse a stored conversation or create one. A failed claim does not create a second conversation.
 *
 * @param mixed    $user_id User id.
 * @param mixed    $session_id Session id.
 * @param mixed    $page_id Page id.
 * @param mixed    $assistant_id Assistant id.
 * @param mixed    $thread_id Existing conversation id, if the caller already has one.
 * @param callable $creator Returns a conversation id string or WP_Error. Must not call the network unless the caller wants that.
 * @return string|WP_Error
 */
function chatbot_chatgpt_resolve_or_create_conversation_id( $user_id, $session_id, $page_id, $assistant_id, $thread_id, $creator ) {
    $thread_id = is_string( $thread_id ) ? trim( $thread_id ) : '';
    if ( $thread_id !== '' ) {
        return $thread_id;
    }

    $storage_key = chatbot_chatgpt_conversation_storage_key( $user_id, $session_id, $page_id, $assistant_id );
    if ( $storage_key === '' ) {
        return new WP_Error( 'conversation_unverified', __( 'Unauthorized access to conversation.', 'chatbot-chatgpt' ) );
    }

    $stored = get_chatbot_chatgpt_threads( $user_id, $session_id, $page_id, $assistant_id );
    if ( is_string( $stored ) && $stored !== '' ) {
        return $stored;
    }

    if ( ! chatbot_chatgpt_claim_conversation_create_lock( $storage_key ) ) {
        $stored = get_chatbot_chatgpt_threads( $user_id, $session_id, $page_id, $assistant_id );
        if ( is_string( $stored ) && $stored !== '' ) {
            return $stored;
        }
        return new WP_Error( 'conversation_busy', __( 'Conversation is busy. Please retry.', 'chatbot-chatgpt' ) );
    }

    try {
        $stored = get_chatbot_chatgpt_threads( $user_id, $session_id, $page_id, $assistant_id );
        if ( is_string( $stored ) && $stored !== '' ) {
            return $stored;
        }

        $created = call_user_func( $creator );
        if ( is_wp_error( $created ) ) {
            return $created;
        }
        if ( ! is_string( $created ) || trim( $created ) === '' ) {
            return new WP_Error( 'conversation_create_failed', __( 'Conversation created but missing ID.', 'chatbot-chatgpt' ) );
        }
        $created = trim( $created );

        $written = set_chatbot_chatgpt_threads( $created, $assistant_id, $user_id, $page_id, $session_id );
        if ( $written === false ) {
            $stored = get_chatbot_chatgpt_threads( $user_id, $session_id, $page_id, $assistant_id );
            if ( is_string( $stored ) && $stored !== '' ) {
                return $stored;
            }
            return new WP_Error( 'conversation_store_failed', __( 'Conversation could not be stored.', 'chatbot-chatgpt' ) );
        }
        return $created;
    } finally {
        chatbot_chatgpt_release_conversation_create_lock( $storage_key );
    }
}

/**
 * Remove isolated locks and the message queue for one actor.
 *
 * @param mixed $user_id User id.
 * @param mixed $session_id Session id.
 * @param mixed $page_id Page id.
 * @param mixed $assistant_id Assistant id.
 * @param bool  $session_already_authorized Privileged cleanup only.
 * @return void
 */
function chatbot_chatgpt_delete_scoped_locks_and_queue( $user_id, $session_id, $page_id, $assistant_id, $session_already_authorized = false ) {
    $keys = array(
        chatbot_chatgpt_conversation_processing_lock_key( $user_id, $session_id, $page_id, $assistant_id, $session_already_authorized ),
        chatbot_chatgpt_responses_conv_lock_key( $user_id, $session_id, $page_id, $assistant_id ),
        chatbot_chatgpt_message_queue_key( $user_id, $session_id, $page_id, $assistant_id, $session_already_authorized ),
    );

    if ( $session_already_authorized ) {
        $keys[1] = chatbot_chatgpt_scoped_transient_key( 'kchat_conv_lock_', 'kchat-resp-lock-v1', $user_id, $session_id, $page_id, $assistant_id, true );
    }

    foreach ( $keys as $key ) {
        if ( is_string( $key ) && $key !== '' ) {
            delete_transient( $key );
        }
    }
}

/**
 * Queue a message for a verified actor. A forged session is not stored.
 *
 * @param mixed       $user_id User id.
 * @param mixed       $page_id Page id.
 * @param mixed       $session_id Session id.
 * @param mixed       $assistant_id Assistant id.
 * @param string      $message Message text.
 * @param string|null $client_message_id Client idempotency id.
 * @return string|false
 */
function chatbot_chatgpt_enqueue_message( $user_id, $page_id, $session_id, $assistant_id, $message, $client_message_id = null ) {
    $queue_key = chatbot_chatgpt_message_queue_key( $user_id, $session_id, $page_id, $assistant_id, false );
    if ( $queue_key === '' ) {
        return false;
    }

    $queue = get_transient( $queue_key );
    if ( ! is_array( $queue ) ) {
        $queue = array();
    }

    $client_message_id = is_string( $client_message_id ) && $client_message_id !== ''
        ? $client_message_id
        : ( function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'kchat_', true ) );

    $queue[] = array(
        'message'           => $message,
        'client_message_id' => $client_message_id,
        'timestamp'         => time(),
        'user_id'           => chatbot_chatgpt_normalize_wp_user_id( $user_id ),
        'page_id'           => chatbot_chatgpt_normalize_storage_part( $page_id ),
        'session_id'        => is_string( $session_id ) ? $session_id : '',
        'assistant_id'      => chatbot_chatgpt_normalize_storage_part( $assistant_id ),
    );

    set_transient( $queue_key, $queue, 3600 );
    return $client_message_id;
}

/**
 * Pop the next queued message for a verified actor.
 *
 * @param mixed $user_id User id.
 * @param mixed $page_id Page id.
 * @param mixed $session_id Session id.
 * @param mixed $assistant_id Assistant id.
 * @return array|null
 */
function chatbot_chatgpt_dequeue_message( $user_id, $page_id, $session_id, $assistant_id ) {
    $queue_key = chatbot_chatgpt_message_queue_key( $user_id, $session_id, $page_id, $assistant_id, false );
    if ( $queue_key === '' ) {
        return null;
    }

    $queue = get_transient( $queue_key );
    if ( ! is_array( $queue ) || $queue === array() ) {
        return null;
    }

    $message = array_shift( $queue );
    if ( $queue === array() ) {
        delete_transient( $queue_key );
    } else {
        set_transient( $queue_key, $queue, 3600 );
    }

    return is_array( $message ) ? $message : null;
}

/**
 * Queue depth for a verified actor.
 *
 * @param mixed $user_id User id.
 * @param mixed $page_id Page id.
 * @param mixed $session_id Session id.
 * @param mixed $assistant_id Assistant id.
 * @return array{has_messages:bool,count:int,next_message:mixed}
 */
function chatbot_chatgpt_get_queue_status( $user_id, $page_id, $session_id, $assistant_id ) {
    $queue_key = chatbot_chatgpt_message_queue_key( $user_id, $session_id, $page_id, $assistant_id, false );
    $queue     = ( $queue_key !== '' ) ? get_transient( $queue_key ) : false;
    $count     = is_array( $queue ) ? count( $queue ) : 0;

    return array(
        'has_messages' => $count > 0,
        'count'        => $count,
        'next_message' => ( is_array( $queue ) && isset( $queue[0] ) ) ? $queue[0] : null,
    );
}

/**
 * Drop one visitor's conversation association, lock, and queue.
 * Does not delete the OpenAI conversation or the transcript table.
 *
 * @param mixed $user_id User id.
 * @param mixed $page_id Page id.
 * @param mixed $session_id Session id.
 * @param mixed $assistant_id Assistant id.
 * @return true|WP_Error
 */
function chatbot_chatgpt_erase_visitor_conversation( $user_id, $page_id, $session_id, $assistant_id ) {
    $uid = chatbot_chatgpt_normalize_wp_user_id( $user_id );
    if ( $uid > 0 ) {
        if ( (int) get_current_user_id() !== $uid ) {
            return new WP_Error( 'unauthorized', __( 'Unauthorized access to conversation.', 'chatbot-chatgpt' ) );
        }
    } elseif ( ! verify_session_ownership( is_string( $session_id ) ? $session_id : '' ) ) {
        return new WP_Error( 'unauthorized', __( 'Unauthorized access to conversation.', 'chatbot-chatgpt' ) );
    }

    $page_id = chatbot_chatgpt_normalize_storage_part( $page_id );
    if ( $page_id === '' ) {
        return new WP_Error( 'missing_page', __( 'Page ID is required.', 'chatbot-chatgpt' ) );
    }

    $deleted = delete_chatbot_chatgpt_threads( $uid, $page_id, $session_id, $assistant_id );
    if ( $deleted !== true ) {
        return new WP_Error( 'unauthorized', __( 'Unauthorized access to conversation.', 'chatbot-chatgpt' ) );
    }

    if ( function_exists( 'chatbot_chatgpt_clear_conversation_locks_and_queue' ) ) {
        chatbot_chatgpt_clear_conversation_locks_and_queue( $assistant_id, $uid, $page_id, $session_id, false );
    } else {
        chatbot_chatgpt_delete_scoped_locks_and_queue( $uid, $session_id, $page_id, $assistant_id, false );
    }

    return true;
}
