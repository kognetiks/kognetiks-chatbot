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

    $token    = 'v2.' . $visitor_id . '.' . kognetiks_sign_visitor_id( $visitor_id );
    $lifetime = 86400 * 30;
    $header   = kognetiks_visitor_cookie_header( $token, time() + $lifetime, $lifetime );

    // setcookie()'s secure argument defaults to false, and its options array is a
    // separate signature. Send the attributes explicitly so Secure cannot be omitted.
    if ( $header !== '' && ! headers_sent() ) {
        header( 'Set-Cookie: ' . $header, false );
    }

    $_COOKIE[ kognetiks_visitor_cookie_name() ] = $token;
    return $visitor_id;
}

/**
 * Set-Cookie header for a signed visitor token.
 * The token is restricted to the issued alphabet so it cannot add another header.
 *
 * @param string $token    v2 visitor token.
 * @param int    $expires  Unix expiration time.
 * @param int    $lifetime Max-Age in seconds.
 * @return string Header value without the Set-Cookie prefix, or empty when refused.
 */
function kognetiks_visitor_cookie_header( $token, $expires, $lifetime ) {
    if ( ! is_string( $token ) || ! preg_match( '/^v2\.[a-f0-9]{32}\.[a-f0-9]{64}$/', $token ) ) {
        return '';
    }
    if ( ! is_int( $expires ) || ! is_int( $lifetime ) || $lifetime < 1 ) {
        return '';
    }

    return kognetiks_visitor_cookie_name()
        . '=' . $token
        . '; Expires=' . gmdate( 'D, d-M-Y H:i:s', $expires ) . ' GMT'
        . '; Max-Age=' . $lifetime
        . '; Path=/; Secure; HttpOnly; SameSite=Lax';
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
function chatbot_chatgpt_enqueue_message( $user_id, $page_id, $session_id, $assistant_id, $message, $client_message_id = null, $assistant_row_id = null ) {
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

    $queue_item = array(
        'message'           => $message,
        'client_message_id' => $client_message_id,
        'timestamp'         => time(),
        'user_id'           => chatbot_chatgpt_normalize_wp_user_id( $user_id ),
        'page_id'           => chatbot_chatgpt_normalize_storage_part( $page_id ),
        'session_id'        => is_string( $session_id ) ? $session_id : '',
        'assistant_id'      => chatbot_chatgpt_normalize_storage_part( $assistant_id ),
    );
    $queue_item = chatbot_chatgpt_annotate_queue_item_row_id( $queue_item, $assistant_row_id );
    if ( ! is_array( $queue_item ) ) {
        return false;
    }
    $queue[] = $queue_item;

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

/**
 * Row scope for a v2 conversation key.
 *
 * A validation decision is read here, before responses_row_id_from_decision()
 * collapses explicit 0 and a missing row to null. Rejected and forged values
 * fail closed. They are not stored as 0 or absent.
 *
 * @param mixed $decision Validation result, or a scope of absent, 0, or a positive id.
 * @return string absent, 0, a positive decimal id, or empty when the scope is refused.
 */
function chatbot_chatgpt_conversation_v2_row_scope( $decision ) {
    if ( is_array( $decision ) ) {
        if ( ! isset( $decision['result'] ) || ! is_string( $decision['result'] ) ) {
            return '';
        }
        if ( 'legacy' === $decision['result'] ) {
            return 'absent';
        }
        if ( 'trusted' !== $decision['result'] || ! isset( $decision['row_id'] ) || ! is_int( $decision['row_id'] ) || $decision['row_id'] < 0 ) {
            return '';
        }
        return (string) $decision['row_id'];
    }

    if ( 'absent' === $decision ) {
        return 'absent';
    }
    if ( is_int( $decision ) ) {
        if ( $decision < 0 ) {
            return '';
        }
        return (string) $decision;
    }
    if ( ! is_string( $decision ) || ! preg_match( '/^(0|[1-9][0-9]*)$/', $decision ) ) {
        return '';
    }
    $id = (int) $decision;
    if ( (string) $id !== $decision ) {
        return '';
    }
    return $decision;
}

/**
 * OpenAI Responses conversation key. v1 key generation is unchanged.
 *
 * The raw actor is an HMAC input. It is not copied into the transient name.
 *
 * @param mixed $user_id User id.
 * @param mixed $session_id Session id.
 * @param mixed $page_id Page id.
 * @param mixed $assistant_id External pmpt_ or asst_ id.
 * @param mixed $row_scope Validation decision or canonical scope.
 * @param bool  $session_already_authorized See chatbot_chatgpt_conversation_actor_token().
 * @return string Empty when the actor, page, assistant, or row scope is refused.
 */
function chatbot_chatgpt_conversation_v2_storage_key( $user_id, $session_id, $page_id, $assistant_id, $row_scope, $session_already_authorized = false ) {
    if ( ! function_exists( 'wp_salt' ) ) {
        return '';
    }
    $actor = chatbot_chatgpt_conversation_actor_token( $user_id, $session_id, $session_already_authorized );
    $page = chatbot_chatgpt_normalize_storage_part( $page_id );
    $assistant = chatbot_chatgpt_normalize_storage_part( $assistant_id );
    $scope = chatbot_chatgpt_conversation_v2_row_scope( $row_scope );
    if ( $actor === '' || $page === '' || $assistant === '' || $scope === '' ) {
        return '';
    }

    $material = 'kchat-conv-v2'
        . '|' . $actor
        . '|' . $page
        . '|' . $assistant
        . '|' . $scope;
    $hash = substr( hash_hmac( 'sha256', $material, wp_salt( 'auth' ) ), 0, 32 );
    $key = 'kchat_cnv2_' . $hash;
    if ( strlen( $key ) > 172 ) {
        return '';
    }
    return $key;
}

/**
 * First-seen vector-store ids that will be sent. Order is preserved.
 *
 * @param mixed $vector_store_ids Effective ids.
 * @return string[]|null Null when an entry is not usable text.
 */
function chatbot_chatgpt_conversation_v2_vector_store_ids( $vector_store_ids ) {
    if ( null === $vector_store_ids || array() === $vector_store_ids ) {
        return array();
    }
    if ( ! is_array( $vector_store_ids ) ) {
        return null;
    }
    $ids = array();
    foreach ( $vector_store_ids as $id ) {
        if ( ! is_string( $id ) || $id === '' ) {
            return null;
        }
        if ( ! in_array( $id, $ids, true ) ) {
            $ids[] = $id;
        }
    }
    return $ids;
}

/**
 * Fingerprint of the Responses request that will be sent.
 *
 * The result is an HMAC. Instruction text and store ids are not returned.
 * Display settings, visitor text, and unused defaults are not inputs.
 *
 * @param string $mode bridge, local, or assistant.
 * @param mixed  $model Sent model, or null or an empty string when the request omits model.
 * @param string $instructions Exact instruction or Bridge developer text, including an empty string.
 * @param mixed  $vector_store_ids Ids that file_search will send, in that order.
 * @param mixed  $max_num_results file_search limit. Ignored when no store is sent.
 * @return string 64 hex characters, or empty when the effective request is refused.
 */
function chatbot_chatgpt_conversation_v2_fingerprint( $mode, $model, $instructions, $vector_store_ids = array(), $max_num_results = null ) {
    if ( ! function_exists( 'wp_salt' ) ) {
        return '';
    }
    if ( ! is_string( $mode ) || ! in_array( $mode, array( 'bridge', 'local', 'assistant' ), true ) ) {
        return '';
    }
    if ( ! is_string( $instructions ) ) {
        return '';
    }
    if ( null === $model || ( is_string( $model ) && $model === '' ) ) {
        $model_field = 'omitted';
    } elseif ( is_string( $model ) ) {
        $model_field = 'sent:' . strlen( $model ) . ':' . $model;
    } else {
        return '';
    }

    $stores = chatbot_chatgpt_conversation_v2_vector_store_ids( $vector_store_ids );
    if ( null === $stores ) {
        return '';
    }
    if ( array() === $stores ) {
        $search_field = 'absent';
    } else {
        if ( ! is_int( $max_num_results ) || $max_num_results < 1 || $max_num_results > 50 ) {
            return '';
        }
        $encoded_ids = array();
        foreach ( $stores as $store_id ) {
            $encoded_ids[] = strlen( $store_id ) . ':' . $store_id;
        }
        $search_field = 'limit:' . $max_num_results . '|' . implode( ',', $encoded_ids );
    }

    $material = 'kchat-conv-fp-v2'
        . '|mode:' . $mode
        . '|model:' . $model_field
        . '|instructions:' . strlen( $instructions ) . ':' . $instructions
        . '|file-search:' . $search_field;
    return hash_hmac( 'sha256', $material, wp_salt( 'auth' ) );
}

/**
 * Opaque generation token. It identifies a conversation record, not its contents.
 *
 * @return string 32 hex characters, or empty when random bytes are unavailable.
 */
function chatbot_chatgpt_conversation_v2_generation() {
    try {
        $bytes = random_bytes( 16 );
    } catch ( Exception $e ) {
        return '';
    }
    $token = bin2hex( $bytes );
    if ( ! preg_match( '/^[a-f0-9]{32}$/', $token ) ) {
        return '';
    }
    return $token;
}

/**
 * True when a record has a conversation id, fingerprint, and generation.
 *
 * A bare v1 conversation string is not a record.
 *
 * @param mixed $record Candidate record.
 * @return bool
 */
function chatbot_chatgpt_conversation_v2_record_is_valid( $record ) {
    if ( ! is_array( $record ) ) {
        return false;
    }
    $expected = array( 'fp', 'generation', 'id' );
    $keys = array_keys( $record );
    sort( $keys );
    if ( $keys !== $expected ) {
        return false;
    }
    if ( ! is_string( $record['id'] ) || ! preg_match( '/^cnv_[A-Za-z0-9]{8,}$/', $record['id'] ) ) {
        return false;
    }
    if ( ! is_string( $record['fp'] ) || ! preg_match( '/^[a-f0-9]{64}$/', $record['fp'] ) ) {
        return false;
    }
    if ( ! is_string( $record['generation'] ) || ! preg_match( '/^[a-f0-9]{32}$/', $record['generation'] ) ) {
        return false;
    }
    return true;
}

/**
 * Build a record. Refuses to pair a fingerprint with a missing conversation id.
 *
 * @param string $id OpenAI conversation id.
 * @param string $fp Fingerprint from chatbot_chatgpt_conversation_v2_fingerprint().
 * @param string $generation Token from chatbot_chatgpt_conversation_v2_generation().
 * @return array{id:string,fp:string,generation:string}|null
 */
function chatbot_chatgpt_conversation_v2_build_record( $id, $fp, $generation ) {
    $record = array(
        'id'         => $id,
        'fp'         => $fp,
        'generation' => $generation,
    );
    if ( ! chatbot_chatgpt_conversation_v2_record_is_valid( $record ) ) {
        return null;
    }
    return $record;
}

/**
 * JSON form suitable for one stored record value.
 *
 * @param mixed $record Valid record.
 * @return string Empty when the record is refused.
 */
function chatbot_chatgpt_conversation_v2_encode_record( $record ) {
    if ( ! chatbot_chatgpt_conversation_v2_record_is_valid( $record ) ) {
        return '';
    }
    $json = json_encode(
        array(
            'id'         => $record['id'],
            'fp'         => $record['fp'],
            'generation' => $record['generation'],
        )
    );
    return is_string( $json ) ? $json : '';
}

/**
 * Read a stored record. v1 conversation strings and partial values are refused.
 *
 * @param mixed $stored Array or JSON object returned from a transient.
 * @return array{id:string,fp:string,generation:string}|null
 */
function chatbot_chatgpt_conversation_v2_read_record( $stored ) {
    if ( is_string( $stored ) ) {
        if ( $stored === '' || $stored[0] !== '{' ) {
            return null;
        }
        $decoded = json_decode( $stored, true );
        if ( ! is_array( $decoded ) ) {
            return null;
        }
        $stored = $decoded;
    }
    if ( ! chatbot_chatgpt_conversation_v2_record_is_valid( $stored ) ) {
        return null;
    }
    return $stored;
}

/**
 * Revalidate a stored positive row before an omitted field may keep it.
 *
 * No loader means the row cannot be checked, so the scope is refused.
 * A deleted or mismatched row is refused. It is not rewritten as 0 or absent.
 *
 * @param string        $scope Canonical positive id.
 * @param mixed         $resolved_assistant_id Assistant id for this request.
 * @param callable|null $load_row function( int $row_id ): array|null
 * @return array{result:string,scope:string,error?:string}
 */
function chatbot_chatgpt_conversation_v2_retain_server_row( $scope, $resolved_assistant_id, $load_row ) {
    $unavailable = function_exists( 'chatbot_chatgpt_assistant_row_unavailable_message' )
        ? chatbot_chatgpt_assistant_row_unavailable_message()
        : 'This chatbot configuration is unavailable.';
    $refused = array(
        'result' => 'rejected',
        'scope'  => '',
        'error'  => $unavailable,
    );
    if ( ! is_string( $resolved_assistant_id ) || ! is_callable( $load_row ) ) {
        return $refused;
    }
    $row_id = (int) $scope;
    if ( $row_id < 1 || (string) $row_id !== $scope ) {
        return $refused;
    }
    $row = call_user_func( $load_row, $row_id );
    if ( ! function_exists( 'chatbot_chatgpt_loaded_row_id' ) || ! function_exists( 'chatbot_chatgpt_row_external_id_matches' ) ) {
        return $refused;
    }
    if ( chatbot_chatgpt_loaded_row_id( $row ) !== $row_id || ! chatbot_chatgpt_row_external_id_matches( $row, $resolved_assistant_id ) ) {
        return $refused;
    }
    return array(
        'result' => 'ok',
        'scope'  => $scope,
    );
}

/**
 * Scope for one synchronous Responses request.
 *
 * A trusted decision supplies its row. An omitted field keeps a stored 0, or
 * a stored positive row after that row is loaded again and still matches the
 * assistant. It does not become absent while that selection exists. The Step
 * 3 validator still returns legacy for a missing field; this function does
 * not change that contract.
 *
 * @param mixed         $decision Validation decision.
 * @param mixed         $server_row_raw Stored row string.
 * @param bool          $server_row_present Whether the stored row exists.
 * @param mixed         $resolved_assistant_id Assistant id used to recheck a retained row.
 * @param callable|null $load_row function( int $row_id ): array|null
 * @return array{result:string,scope:string,error?:string} result is ok, blocked, or rejected.
 */
function chatbot_chatgpt_conversation_v2_request_scope( $decision, $server_row_raw, $server_row_present, $resolved_assistant_id = null, $load_row = null ) {
    if ( ! is_array( $decision ) || ! isset( $decision['result'] ) ) {
        return array(
            'result' => 'blocked',
            'scope'  => '',
        );
    }
    if ( 'rejected' === $decision['result'] ) {
        return array(
            'result' => 'rejected',
            'scope'  => '',
        );
    }
    if ( 'trusted' === $decision['result'] ) {
        $scope = chatbot_chatgpt_conversation_v2_row_scope( $decision );
        if ( $scope === '' ) {
            return array(
                'result' => 'rejected',
                'scope'  => '',
            );
        }
        return array(
            'result' => 'ok',
            'scope'  => $scope,
        );
    }
    if ( 'legacy' === $decision['result'] ) {
        if ( $server_row_present ) {
            $server_scope = chatbot_chatgpt_conversation_v2_row_scope( $server_row_raw );
            if ( '0' === $server_scope ) {
                return array(
                    'result' => 'ok',
                    'scope'  => '0',
                );
            }
            if ( '' !== $server_scope && 'absent' !== $server_scope ) {
                return chatbot_chatgpt_conversation_v2_retain_server_row( $server_scope, $resolved_assistant_id, $load_row );
            }
        }
        return array(
            'result' => 'ok',
            'scope'  => 'absent',
        );
    }
    return array(
        'result' => 'blocked',
        'scope'  => '',
    );
}

/**
 * Fingerprint the Responses payload that will be sent.
 *
 * Bridge text is the developer message. Local and asst_ text is the
 * instructions field. The visitor message is not included.
 *
 * @param string $mode bridge, local, or assistant.
 * @param array  $payload Responses payload before or after the conversation id is known.
 * @return string
 */
function chatbot_chatgpt_conversation_v2_fingerprint_from_payload( $mode, $payload ) {
    if ( ! is_array( $payload ) ) {
        return '';
    }
    $model = null;
    if ( isset( $payload['model'] ) && is_string( $payload['model'] ) && $payload['model'] !== '' ) {
        $model = $payload['model'];
    }

    $instructions = '';
    if ( 'bridge' === $mode ) {
        if ( isset( $payload['input'] ) && is_array( $payload['input'] ) ) {
            foreach ( $payload['input'] as $item ) {
                if ( is_array( $item ) && isset( $item['role'] ) && 'developer' === $item['role'] && isset( $item['content'] ) && is_string( $item['content'] ) ) {
                    $instructions = $item['content'];
                    break;
                }
            }
        }
    } elseif ( isset( $payload['instructions'] ) && is_string( $payload['instructions'] ) ) {
        $instructions = $payload['instructions'];
    }

    $ids = array();
    $limit = null;
    if ( isset( $payload['tools'] ) && is_array( $payload['tools'] ) ) {
        foreach ( $payload['tools'] as $tool ) {
            if ( ! is_array( $tool ) || ! isset( $tool['type'] ) || 'file_search' !== $tool['type'] ) {
                continue;
            }
            $ids = isset( $tool['vector_store_ids'] ) ? $tool['vector_store_ids'] : array();
            $limit = isset( $tool['max_num_results'] ) ? $tool['max_num_results'] : null;
            break;
        }
    }
    return chatbot_chatgpt_conversation_v2_fingerprint( $mode, $model, $instructions, $ids, $limit );
}

/**
 * Option that records who holds the v2 create lock.
 *
 * @param string $storage_key v2 transient name.
 * @return string
 */
function chatbot_chatgpt_conversation_v2_lock_name( $storage_key ) {
    if ( $storage_key === '' || ! function_exists( 'wp_salt' ) ) {
        return '';
    }
    return 'kchat_cnv2_lock_' . substr( hash_hmac( 'sha256', 'lock|' . $storage_key, wp_salt( 'auth' ) ), 0, 32 );
}

/**
 * Replace an option only when its current value still matches.
 *
 * @param string $name Option name.
 * @param string $expected Current value.
 * @param string $new_value Replacement value.
 * @return bool
 */
function chatbot_chatgpt_conversation_v2_replace_option_value( $name, $expected, $new_value ) {
    global $wpdb;
    if ( ! isset( $wpdb ) || ! is_object( $wpdb ) || empty( $wpdb->options ) || ! method_exists( $wpdb, 'query' ) || ! method_exists( $wpdb, 'prepare' ) ) {
        return false;
    }
    $result = $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value = %s",
            $new_value,
            $name,
            $expected
        )
    );
    return 1 === (int) $result;
}

/**
 * Delete an option only when its current value still matches.
 *
 * @param string $name Option name.
 * @param string $expected Value held by this worker.
 * @return bool
 */
function chatbot_chatgpt_conversation_v2_delete_option_value( $name, $expected ) {
    global $wpdb;
    if ( ! isset( $wpdb ) || ! is_object( $wpdb ) || empty( $wpdb->options ) || ! method_exists( $wpdb, 'query' ) || ! method_exists( $wpdb, 'prepare' ) ) {
        return false;
    }
    $result = $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s",
            $name,
            $expected
        )
    );
    chatbot_chatgpt_conversation_v2_bust_option_cache( $name );
    return 1 === (int) $result;
}

/**
 * Delete a timeout only when its value still matches and its record is absent.
 *
 * The record check is inside this statement. InnoDB locks that option name
 * for the delete, so a newer record inserted by another connection keeps
 * its timeout.
 *
 * @param string $record_name Record option name.
 * @param string $timeout_name Timeout option name.
 * @param string $timeout Exact timeout value previously observed.
 * @return bool
 */
function chatbot_chatgpt_conversation_v2_delete_timeout_if_record_absent( $record_name, $timeout_name, $timeout ) {
    global $wpdb;
    if ( ! is_string( $record_name ) || $record_name === '' || ! is_string( $timeout_name ) || $timeout_name === '' || ! is_string( $timeout ) || $timeout === '' ) {
        return false;
    }
    if ( ! isset( $wpdb ) || ! is_object( $wpdb ) || empty( $wpdb->options ) || ! method_exists( $wpdb, 'query' ) || ! method_exists( $wpdb, 'prepare' ) ) {
        return false;
    }
    $result = $wpdb->query(
        $wpdb->prepare(
            "DELETE t FROM {$wpdb->options} t LEFT JOIN {$wpdb->options} r ON r.option_name = %s WHERE t.option_name = %s AND t.option_value = %s AND r.option_name IS NULL",
            $record_name,
            $timeout_name,
            $timeout
        )
    );
    chatbot_chatgpt_conversation_v2_bust_option_cache( $timeout_name );
    return 1 === (int) $result;
}

/**
 * Read one lock option from the database.
 *
 * A direct update is not visible through the option cache, so ownership
 * checks must not trust get_option() after another worker replaces the lease.
 *
 * @param string $name Option name.
 * @return string
 */
function chatbot_chatgpt_conversation_v2_read_option( $name ) {
    global $wpdb;
    if ( isset( $wpdb ) && is_object( $wpdb ) && ! empty( $wpdb->options ) && method_exists( $wpdb, 'get_var' ) && method_exists( $wpdb, 'prepare' ) ) {
        $value = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1", $name ) );
        return is_string( $value ) ? $value : '';
    }
    if ( function_exists( 'get_option' ) ) {
        $value = get_option( $name, '' );
        return is_string( $value ) ? $value : '';
    }
    return '';
}

/**
 * Claim the v2 create lock. The returned value is this worker's lease.
 *
 * A lease older than 30 seconds can be replaced by one atomic update.
 * The previous worker keeps its old lease string, which no longer matches.
 *
 * @param string $storage_key v2 transient name.
 * @return string Lease value, or empty when another worker holds a fresh lock.
 */
function chatbot_chatgpt_conversation_v2_claim_lock( $storage_key ) {
    $name = chatbot_chatgpt_conversation_v2_lock_name( $storage_key );
    if ( $name === '' || ! function_exists( 'add_option' ) || ! function_exists( 'get_option' ) ) {
        return '';
    }
    $token = chatbot_chatgpt_conversation_v2_generation();
    if ( $token === '' ) {
        return '';
    }
    $lease = $token . '.' . time();
    if ( add_option( $name, $lease, '', 'no' ) ) {
        return $lease;
    }

    $existing = chatbot_chatgpt_conversation_v2_read_option( $name );
    if ( ! is_string( $existing ) || ! preg_match( '/^[a-f0-9]{32}\.(\d+)$/', $existing, $matches ) ) {
        return '';
    }
    $stamp = (int) $matches[1];
    if ( $stamp <= 0 || ( time() - $stamp ) <= 30 ) {
        return '';
    }
    $replacement = $token . '.' . time();
    if ( ! chatbot_chatgpt_conversation_v2_replace_option_value( $name, $existing, $replacement ) ) {
        return '';
    }
    return $replacement;
}

/**
 * True when this worker's lease is still the stored lock.
 *
 * @param string $storage_key v2 transient name.
 * @param string $lease Lease returned by the claim.
 * @return bool
 */
function chatbot_chatgpt_conversation_v2_lock_held( $storage_key, $lease ) {
    $name = chatbot_chatgpt_conversation_v2_lock_name( $storage_key );
    if ( $name === '' || ! is_string( $lease ) || $lease === '' || ! function_exists( 'get_option' ) ) {
        return false;
    }
    $current = chatbot_chatgpt_conversation_v2_read_option( $name );
    return is_string( $current ) && hash_equals( $lease, $current );
}

/**
 * Release a lease. A stale lease does not delete a newer worker's lock.
 *
 * @param string $storage_key v2 transient name.
 * @param string $lease Lease returned by the claim.
 * @return bool
 */
function chatbot_chatgpt_conversation_v2_release_lock( $storage_key, $lease ) {
    $name = chatbot_chatgpt_conversation_v2_lock_name( $storage_key );
    if ( $name === '' || ! is_string( $lease ) || $lease === '' ) {
        return false;
    }
    return chatbot_chatgpt_conversation_v2_delete_option_value( $name, $lease );
}

/**
 * Option name for one v2 record. The _transient_ prefix keeps the value out of
 * autoload and lets WordPress recognize the retention timeout beside it.
 *
 * @param string $storage_key v2 transient name.
 * @return string
 */
function chatbot_chatgpt_conversation_v2_record_option_name( $storage_key ) {
    return '_transient_' . $storage_key;
}

/**
 * Option name for the unix-time expiry of one v2 record.
 *
 * @param string $storage_key v2 transient name.
 * @return string
 */
function chatbot_chatgpt_conversation_v2_timeout_option_name( $storage_key ) {
    return '_transient_timeout_' . $storage_key;
}

/**
 * Drop a cached option so a later read sees a direct write.
 *
 * @param string $name Option name.
 * @return void
 */
function chatbot_chatgpt_conversation_v2_bust_option_cache( $name ) {
    if ( function_exists( 'wp_cache_delete' ) ) {
        wp_cache_delete( $name, 'options' );
        wp_cache_delete( 'notoptions', 'options' );
    }
}

/**
 * Insert an option only when its name is absent.
 *
 * The options table unique key makes INSERT IGNORE the atomic check.
 *
 * @param string $name Option name.
 * @param string $value Exact stored value.
 * @return bool
 */
function chatbot_chatgpt_conversation_v2_insert_option( $name, $value ) {
    global $wpdb;
    if ( ! isset( $wpdb ) || ! is_object( $wpdb ) || empty( $wpdb->options ) || ! method_exists( $wpdb, 'query' ) || ! method_exists( $wpdb, 'prepare' ) ) {
        return false;
    }
    $result = $wpdb->query(
        $wpdb->prepare(
            "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')",
            $name,
            $value
        )
    );
    chatbot_chatgpt_conversation_v2_bust_option_cache( $name );
    return 1 === (int) $result;
}

/**
 * Load one record. An expired, timeless, or invalid value is deleted only
 * when the stored bytes are still the bytes just read.
 *
 * @param string $storage_key v2 transient name.
 * @return array{record:array{id:string,fp:string,generation:string}|null,raw:string,timeout:string}
 */
function chatbot_chatgpt_conversation_v2_load_record( $storage_key ) {
    $record_name = chatbot_chatgpt_conversation_v2_record_option_name( $storage_key );
    $timeout_name = chatbot_chatgpt_conversation_v2_timeout_option_name( $storage_key );
    for ( $attempt = 0; $attempt < 2; $attempt++ ) {
        $raw = chatbot_chatgpt_conversation_v2_read_option( $record_name );
        if ( $raw === '' ) {
            return array(
                'record'  => null,
                'raw'     => '',
                'timeout' => '',
            );
        }
        $timeout = chatbot_chatgpt_conversation_v2_read_option( $timeout_name );
        $record = chatbot_chatgpt_conversation_v2_read_record( $raw );
        $stamp = ctype_digit( $timeout ) ? (int) $timeout : 0;
        if ( is_array( $record ) && $stamp > time() ) {
            return array(
                'record'  => $record,
                'raw'     => $raw,
                'timeout' => $timeout,
            );
        }
        if ( ! chatbot_chatgpt_conversation_v2_delete_option_value( $record_name, $raw ) ) {
            continue;
        }
        if ( $timeout !== '' ) {
            chatbot_chatgpt_conversation_v2_delete_timeout_if_record_absent( $record_name, $timeout_name, $timeout );
        }
        chatbot_chatgpt_conversation_v2_bust_option_cache( $record_name );
        chatbot_chatgpt_conversation_v2_bust_option_cache( $timeout_name );
    }
    return array(
        'record'  => null,
        'raw'     => '',
        'timeout' => '',
    );
}

/**
 * Insert a complete record and its expiry. A failed insert leaves any
 * different existing record in place.
 *
 * @param string                                    $storage_key v2 transient name.
 * @param array{id:string,fp:string,generation:string} $record Record to store.
 * @return array{json:string,timeout:string}|null
 */
function chatbot_chatgpt_conversation_v2_insert_record( $storage_key, $record ) {
    $json = chatbot_chatgpt_conversation_v2_encode_record( $record );
    if ( $json === '' ) {
        return null;
    }
    $timeout = (string) ( time() + chatbot_chatgpt_thread_retention_seconds() );
    $record_name = chatbot_chatgpt_conversation_v2_record_option_name( $storage_key );
    $timeout_name = chatbot_chatgpt_conversation_v2_timeout_option_name( $storage_key );
    for ( $attempt = 0; $attempt < 2; $attempt++ ) {
        if ( ! chatbot_chatgpt_conversation_v2_insert_option( $record_name, $json ) ) {
            return null;
        }
        if ( chatbot_chatgpt_conversation_v2_insert_option( $timeout_name, $timeout ) ) {
            return array(
                'json'    => $json,
                'timeout' => $timeout,
            );
        }
        if ( chatbot_chatgpt_conversation_v2_delete_option_value( $record_name, $json ) ) {
            $blocking = chatbot_chatgpt_conversation_v2_read_option( $timeout_name );
            if ( $blocking !== '' ) {
                chatbot_chatgpt_conversation_v2_delete_timeout_if_record_absent( $record_name, $timeout_name, $blocking );
            }
        }
    }
    return null;
}

/**
 * Remove one observed record value. A newer value does not match.
 *
 * @param string $storage_key v2 transient name.
 * @param string $raw Exact option value previously read.
 * @param string $timeout Exact timeout value previously read.
 * @return bool
 */
function chatbot_chatgpt_conversation_v2_delete_observed_record( $storage_key, $raw, $timeout ) {
    if ( ! is_string( $raw ) || $raw === '' ) {
        return false;
    }
    $record_name = chatbot_chatgpt_conversation_v2_record_option_name( $storage_key );
    if ( ! chatbot_chatgpt_conversation_v2_delete_option_value( $record_name, $raw ) ) {
        return false;
    }
    if ( is_string( $timeout ) && $timeout !== '' ) {
        chatbot_chatgpt_conversation_v2_delete_timeout_if_record_absent( $record_name, chatbot_chatgpt_conversation_v2_timeout_option_name( $storage_key ), $timeout );
    }
    chatbot_chatgpt_conversation_v2_bust_option_cache( $record_name );
    return true;
}

/**
 * Read the v2 record for one scope. v1 storage is not consulted.
 *
 * @param mixed  $user_id User id.
 * @param mixed  $session_id Session id.
 * @param mixed  $page_id Page id.
 * @param mixed  $assistant_id External id.
 * @param mixed  $row_scope Trusted scope.
 * @return array{id:string,fp:string,generation:string}|null
 */
function chatbot_chatgpt_conversation_v2_current_record( $user_id, $session_id, $page_id, $assistant_id, $row_scope ) {
    $key = chatbot_chatgpt_conversation_v2_storage_key( $user_id, $session_id, $page_id, $assistant_id, $row_scope, false );
    if ( $key === '' ) {
        return null;
    }
    $loaded = chatbot_chatgpt_conversation_v2_load_record( $key );
    return $loaded['record'];
}

/**
 * Remove one v2 record and its create lock. Does not call OpenAI.
 *
 * @param mixed  $user_id User id.
 * @param mixed  $session_id Session id.
 * @param mixed  $page_id Page id.
 * @param mixed  $assistant_id External id.
 * @param mixed  $row_scope Trusted scope.
 * @return bool
 */
function chatbot_chatgpt_conversation_v2_clear_scope( $user_id, $session_id, $page_id, $assistant_id, $row_scope ) {
    $key = chatbot_chatgpt_conversation_v2_storage_key( $user_id, $session_id, $page_id, $assistant_id, $row_scope, false );
    if ( $key === '' ) {
        return false;
    }
    $raw = chatbot_chatgpt_conversation_v2_read_option( chatbot_chatgpt_conversation_v2_record_option_name( $key ) );
    $timeout = chatbot_chatgpt_conversation_v2_read_option( chatbot_chatgpt_conversation_v2_timeout_option_name( $key ) );
    $lock = chatbot_chatgpt_conversation_v2_lock_name( $key );
    $lock_value = $lock === '' ? '' : chatbot_chatgpt_conversation_v2_read_option( $lock );
    if ( $raw !== '' ) {
        chatbot_chatgpt_conversation_v2_delete_observed_record( $key, $raw, $timeout );
    }
    if ( $lock !== '' && $lock_value !== '' ) {
        chatbot_chatgpt_conversation_v2_delete_option_value( $lock, $lock_value );
    }
    return true;
}

/**
 * Clear the v2 record for the row stored for this page.
 *
 * An invalid stored row is left alone. A missing row clears the absent scope.
 *
 * @param mixed $user_id User id.
 * @param mixed $session_id Session id.
 * @param mixed $page_id Page id.
 * @param mixed $assistant_id External id.
 * @return bool
 */
function chatbot_chatgpt_conversation_v2_clear_selected_scope( $user_id, $session_id, $page_id, $assistant_id ) {
    $scope = 'absent';
    if ( function_exists( 'get_chatbot_chatgpt_transients' ) ) {
        $raw = get_chatbot_chatgpt_transients( 'assistant_row_id', $user_id, $page_id, $session_id );
        if ( '' !== $raw ) {
            $candidate = chatbot_chatgpt_conversation_v2_row_scope( $raw );
            if ( $candidate === '' || $candidate === 'absent' ) {
                return false;
            }
            $scope = $candidate;
        }
    }
    return chatbot_chatgpt_conversation_v2_clear_scope( $user_id, $session_id, $page_id, $assistant_id, $scope );
}

/**
 * Attributes for one OpenAI Responses widget.
 *
 * The generation is read from the current record. This does not create a conversation.
 * Non-OpenAI widgets receive an empty string and keep their existing transcript key.
 *
 * @param mixed $user_id User id.
 * @param mixed $session_id Session id.
 * @param mixed $page_id Page id.
 * @param mixed $assistant_id External id.
 * @param mixed $row_id Validated shortcode row, including 0.
 * @return string Leading-space attributes, or empty.
 */
function chatbot_chatgpt_conversation_v2_widget_attribute_string( $user_id, $session_id, $page_id, $assistant_id, $row_id ) {
    $platform = function_exists( 'get_option' ) ? get_option( 'chatbot_ai_platform_choice', 'OpenAI' ) : 'OpenAI';
    if ( ! function_exists( 'chatbot_chatgpt_routes_openai_responses' ) || ! chatbot_chatgpt_routes_openai_responses( $platform, 'Yes', $assistant_id ) ) {
        return '';
    }
    $scope = chatbot_chatgpt_conversation_v2_row_scope( is_int( $row_id ) ? $row_id : (string) $row_id );
    if ( $scope === '' ) {
        return '';
    }
    $generation = '';
    $record = chatbot_chatgpt_conversation_v2_current_record( $user_id, $session_id, $page_id, $assistant_id, $scope );
    if ( is_array( $record ) && isset( $record['generation'] ) && is_string( $record['generation'] ) ) {
        $generation = $record['generation'];
    }
    return ' data-assistant-id="' . esc_attr( (string) $assistant_id ) . '" data-row-scope="' . esc_attr( $scope ) . '" data-conversation-generation="' . esc_attr( $generation ) . '"';
}

/**
 * Reuse or rotate one v2 record. The callable creates a remote conversation.
 *
 * A supplied conversation id is not an argument. Callers cannot bypass the fingerprint.
 *
 * @param mixed    $user_id User id.
 * @param mixed    $session_id Session id.
 * @param mixed    $page_id Page id.
 * @param mixed    $assistant_id External id.
 * @param mixed    $row_scope Trusted scope.
 * @param string   $fingerprint Fingerprint of the payload about to be sent.
 * @param callable $creator Returns a conversation id or WP_Error.
 * @return array{id:string,fp:string,generation:string}|WP_Error
 */
function chatbot_chatgpt_conversation_v2_acquire( $user_id, $session_id, $page_id, $assistant_id, $row_scope, $fingerprint, $creator ) {
    $key = chatbot_chatgpt_conversation_v2_storage_key( $user_id, $session_id, $page_id, $assistant_id, $row_scope, false );
    if ( $key === '' || ! is_string( $fingerprint ) || ! preg_match( '/^[a-f0-9]{64}$/', $fingerprint ) ) {
        return new WP_Error( 'conversation_unverified', __( 'Unauthorized access to conversation.', 'chatbot-chatgpt' ) );
    }

    $lease = chatbot_chatgpt_conversation_v2_claim_lock( $key );
    if ( $lease === '' ) {
        $ready = chatbot_chatgpt_conversation_v2_load_record( $key );
        if ( is_array( $ready['record'] ) && hash_equals( $fingerprint, $ready['record']['fp'] ) ) {
            return $ready['record'];
        }
        return new WP_Error( 'conversation_busy', __( 'Conversation is busy. Please retry.', 'chatbot-chatgpt' ) );
    }

    try {
        $loaded = chatbot_chatgpt_conversation_v2_load_record( $key );
        if ( is_array( $loaded['record'] ) && hash_equals( $fingerprint, $loaded['record']['fp'] ) ) {
            return $loaded['record'];
        }
        if ( $loaded['raw'] !== '' ) {
            if ( ! chatbot_chatgpt_conversation_v2_lock_held( $key, $lease ) ) {
                return new WP_Error( 'conversation_busy', __( 'Conversation is busy. Please retry.', 'chatbot-chatgpt' ) );
            }
            $retired = chatbot_chatgpt_conversation_v2_delete_observed_record( $key, $loaded['raw'], $loaded['timeout'] );
            if ( ! $retired ) {
                $replaced = chatbot_chatgpt_conversation_v2_load_record( $key );
                if ( is_array( $replaced['record'] ) && hash_equals( $fingerprint, $replaced['record']['fp'] ) ) {
                    return $replaced['record'];
                }
                return new WP_Error( 'conversation_store_failed', __( 'Conversation could not be stored.', 'chatbot-chatgpt' ) );
            }
        }
        if ( ! chatbot_chatgpt_conversation_v2_lock_held( $key, $lease ) ) {
            return new WP_Error( 'conversation_busy', __( 'Conversation is busy. Please retry.', 'chatbot-chatgpt' ) );
        }

        $created = call_user_func( $creator );
        if ( is_wp_error( $created ) ) {
            return $created;
        }
        if ( ! is_string( $created ) || trim( $created ) === '' ) {
            return new WP_Error( 'conversation_create_failed', __( 'Conversation created but missing ID.', 'chatbot-chatgpt' ) );
        }
        $created = trim( $created );
        if ( ! chatbot_chatgpt_conversation_v2_lock_held( $key, $lease ) ) {
            return new WP_Error( 'conversation_busy', __( 'Conversation is busy. Please retry.', 'chatbot-chatgpt' ) );
        }

        $raced = chatbot_chatgpt_conversation_v2_load_record( $key );
        if ( is_array( $raced['record'] ) ) {
            if ( hash_equals( $fingerprint, $raced['record']['fp'] ) ) {
                return $raced['record'];
            }
            return new WP_Error( 'conversation_store_failed', __( 'Conversation could not be stored.', 'chatbot-chatgpt' ) );
        }

        $record = chatbot_chatgpt_conversation_v2_build_record( $created, $fingerprint, chatbot_chatgpt_conversation_v2_generation() );
        if ( ! is_array( $record ) ) {
            return new WP_Error( 'conversation_store_failed', __( 'Conversation could not be stored.', 'chatbot-chatgpt' ) );
        }
        if ( ! chatbot_chatgpt_conversation_v2_lock_held( $key, $lease ) ) {
            return new WP_Error( 'conversation_busy', __( 'Conversation is busy. Please retry.', 'chatbot-chatgpt' ) );
        }
        $inserted = chatbot_chatgpt_conversation_v2_insert_record( $key, $record );
        if ( ! is_array( $inserted ) ) {
            $existing = chatbot_chatgpt_conversation_v2_load_record( $key );
            if ( is_array( $existing['record'] ) && hash_equals( $fingerprint, $existing['record']['fp'] ) ) {
                return $existing['record'];
            }
            return new WP_Error( 'conversation_store_failed', __( 'Conversation could not be stored.', 'chatbot-chatgpt' ) );
        }
        if ( ! chatbot_chatgpt_conversation_v2_lock_held( $key, $lease ) ) {
            chatbot_chatgpt_conversation_v2_delete_observed_record( $key, $inserted['json'], $inserted['timeout'] );
            return new WP_Error( 'conversation_busy', __( 'Conversation is busy. Please retry.', 'chatbot-chatgpt' ) );
        }
        $stored = chatbot_chatgpt_conversation_v2_load_record( $key );
        if ( is_array( $stored['record'] ) && hash_equals( $fingerprint, $stored['record']['fp'] ) ) {
            return $stored['record'];
        }
        return new WP_Error( 'conversation_store_failed', __( 'Conversation could not be stored.', 'chatbot-chatgpt' ) );
    } finally {
        chatbot_chatgpt_conversation_v2_release_lock( $key, $lease );
    }
}
