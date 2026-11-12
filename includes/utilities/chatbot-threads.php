<?php
/**
 * Kognetiks Chatbot - Threads
 *
 * Isolated conversation ids (OpenAI cnv_ values and legacy thread ids) are stored
 * under a verified actor, page, assistant, and the tab generation. The historical
 * anonymous key chatbot_chatgpt_thread_id_0_{page_id} is never read. A legacy
 * authenticated thread is not copied into a new tab.
 *
 * @package chatbot-chatgpt
 */

if ( ! defined( 'WPINC' ) ) {
    die();
}

/**
 * Store a conversation id for one verified actor, page, assistant, and tab.
 * Does not replace an existing different id. Callers must erase first.
 *
 * @param string $thread_id Conversation id.
 * @param string $assistant_id Assistant or prompt id.
 * @param mixed  $user_id WordPress user id, or 0 for a visitor.
 * @param mixed  $page_id Page id.
 * @param mixed  $session_id Verified visitor id. Required for anonymous visitors.
 * @return bool
 */
function set_chatbot_chatgpt_threads( $thread_id, $assistant_id, $user_id, $page_id, $session_id = null ) {

    if ( ! is_string( $thread_id ) || trim( $thread_id ) === '' ) {
        return false;
    }
    $thread_id = trim( $thread_id );
    if ( $session_id === null ) {
        $session_id = '';
    }

    $key = chatbot_chatgpt_conversation_storage_key( $user_id, $session_id, $page_id, $assistant_id );
    if ( $key === '' ) {
        return false;
    }

    $existing = get_transient( $key );
    if ( is_string( $existing ) && $existing !== '' && $existing !== $thread_id ) {
        return false;
    }

    set_transient( $key, $thread_id, chatbot_chatgpt_thread_retention_seconds() );

    if ( chatbot_chatgpt_normalize_wp_user_id( $user_id ) <= 0 ) {
        chatbot_chatgpt_delete_legacy_anonymous_keys_for_page( $page_id );
    }

    return true;
}

/**
 * Read the conversation id for one verified actor, page, assistant, and tab.
 *
 * @param mixed $user_id WordPress user id, or 0 for a visitor.
 * @param mixed $session_id Verified visitor id.
 * @param mixed $page_id Page id.
 * @param mixed $assistant_id Assistant or prompt id.
 * @return string
 */
function get_chatbot_chatgpt_threads( $user_id, $session_id, $page_id, $assistant_id ) {

    $key = chatbot_chatgpt_conversation_storage_key( $user_id, $session_id, $page_id, $assistant_id );
    if ( $key === '' ) {
        return '';
    }

    if ( chatbot_chatgpt_normalize_wp_user_id( $user_id ) <= 0 ) {
        // Retire the shared anonymous row. Do not read it.
        chatbot_chatgpt_delete_legacy_anonymous_keys_for_page( $page_id );
    }

    $thread_id = get_transient( $key );
    if ( is_string( $thread_id ) && $thread_id !== '' ) {
        return $thread_id;
    }

    $uid = chatbot_chatgpt_normalize_wp_user_id( $user_id );
    if ( $uid > 0 ) {
        // Leave the old user-and-page thread unread so a new tab starts clean.
        chatbot_chatgpt_delete_matching_legacy_authenticated_thread( $uid, $page_id, $assistant_id );
    }

    return '';
}

/**
 * Remove one actor's conversation association for one page and assistant.
 *
 * @param mixed $user_id WordPress user id, or 0 for a visitor.
 * @param mixed $page_id Page id.
 * @param mixed $session_id Verified visitor id.
 * @param mixed $assistant_id Assistant or prompt id.
 * @return bool
 */
function delete_chatbot_chatgpt_threads( $user_id, $page_id, $session_id = null, $assistant_id = null ) {

    if ( $session_id === null ) {
        $session_id = '';
    }

    $key = chatbot_chatgpt_conversation_storage_key( $user_id, $session_id, $page_id, $assistant_id );
    if ( $key === '' ) {
        return false;
    }

    delete_transient( $key );

    $uid = chatbot_chatgpt_normalize_wp_user_id( $user_id );
    if ( $uid > 0 ) {
        chatbot_chatgpt_delete_matching_legacy_authenticated_thread( $uid, $page_id, $assistant_id );
    } else {
        chatbot_chatgpt_delete_legacy_anonymous_keys_for_page( $page_id );
    }

    return true;
}
