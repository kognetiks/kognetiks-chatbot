<?php
/**
 * Kognetiks Chatbot - Personal data export and erase
 *
 * Registers the conversation log with WordPress Tools → Export Personal Data
 * and Erase Personal Data. Anonymous visitors have no WordPress user id, so
 * their rows are not included.
 *
 * @package chatbot-chatgpt
 */

if ( ! defined( 'WPINC' ) ) {
    die();
}

/**
 * Conversation log table, or empty when WordPress is not ready.
 *
 * @return string
 */
function chatbot_chatgpt_privacy_log_table() {
    global $wpdb;
    if ( ! isset( $wpdb ) || ! is_object( $wpdb ) || empty( $wpdb->prefix ) ) {
        return '';
    }
    return $wpdb->prefix . 'chatbot_chatgpt_conversation_log';
}

/**
 * True when the conversation log table exists.
 *
 * @return bool
 */
function chatbot_chatgpt_privacy_log_table_exists() {
    global $wpdb;
    $table = chatbot_chatgpt_privacy_log_table();
    if ( $table === '' || ! isset( $wpdb ) || ! method_exists( $wpdb, 'get_var' ) ) {
        return false;
    }
    $found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
    return $found === $table;
}

/**
 * @param array $exporters Registered exporters.
 * @return array
 */
function chatbot_chatgpt_register_privacy_exporter( $exporters ) {
    $exporters['chatbot-chatgpt-conversations'] = array(
        'exporter_friendly_name' => __( 'Kognetiks Chatbot conversations', 'chatbot-chatgpt' ),
        'callback'               => 'chatbot_chatgpt_privacy_export_conversations',
    );
    return $exporters;
}
add_filter( 'wp_privacy_personal_data_exporters', 'chatbot_chatgpt_register_privacy_exporter' );

/**
 * Export conversation rows stored for this account.
 *
 * @param string $email_address Account email.
 * @param int    $page          1-based page.
 * @return array{data:array,done:bool}
 */
function chatbot_chatgpt_privacy_export_conversations( $email_address, $page = 1 ) {
    $empty = array(
        'data' => array(),
        'done' => true,
    );
    $user = get_user_by( 'email', $email_address );
    if ( ! $user || ! chatbot_chatgpt_privacy_log_table_exists() ) {
        return $empty;
    }

    global $wpdb;
    $page = max( 1, (int) $page );
    $per_page = 20;
    $offset = ( $page - 1 ) * $per_page;
    $table = chatbot_chatgpt_privacy_log_table();

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, interaction_time, user_type, page_id, message_text
            FROM {$table}
            WHERE user_id = %s AND user_type IN ('Visitor', 'Chatbot')
            ORDER BY id ASC
            LIMIT %d OFFSET %d",
            (string) $user->ID,
            $per_page,
            $offset
        )
    );
    if ( ! is_array( $rows ) ) {
        return $empty;
    }

    $export = array();
    foreach ( $rows as $row ) {
        $export[] = array(
            'group_id'    => 'chatbot-chatgpt-conversations',
            'group_label' => __( 'Chatbot conversations', 'chatbot-chatgpt' ),
            'group_description' => __( 'Messages this account sent to the chatbot and the replies stored on this site.', 'chatbot-chatgpt' ),
            'item_id'     => 'chatbot-message-' . (int) $row->id,
            'data'        => array(
                array(
                    'name'  => __( 'Time', 'chatbot-chatgpt' ),
                    'value' => (string) $row->interaction_time,
                ),
                array(
                    'name'  => __( 'Role', 'chatbot-chatgpt' ),
                    'value' => (string) $row->user_type,
                ),
                array(
                    'name'  => __( 'Page ID', 'chatbot-chatgpt' ),
                    'value' => (string) $row->page_id,
                ),
                array(
                    'name'  => __( 'Message', 'chatbot-chatgpt' ),
                    'value' => (string) $row->message_text,
                ),
            ),
        );
    }

    return array(
        'data' => $export,
        'done' => count( $rows ) < $per_page,
    );
}

/**
 * @param array $erasers Registered erasers.
 * @return array
 */
function chatbot_chatgpt_register_privacy_eraser( $erasers ) {
    $erasers['chatbot-chatgpt-conversations'] = array(
        'eraser_friendly_name' => __( 'Kognetiks Chatbot conversations', 'chatbot-chatgpt' ),
        'callback'             => 'chatbot_chatgpt_privacy_erase_conversations',
    );
    return $erasers;
}
add_filter( 'wp_privacy_personal_data_erasers', 'chatbot_chatgpt_register_privacy_eraser' );

/**
 * Delete stored chat rows and the private transients indexed for this account.
 *
 * @param string $email_address Account email.
 * @param int    $page          1-based page.
 * @return array{items_removed:int,items_retained:bool,messages:array,done:bool}
 */
function chatbot_chatgpt_privacy_erase_conversations( $email_address, $page = 1 ) {
    $done = array(
        'items_removed'  => 0,
        'items_retained' => false,
        'messages'       => array(),
        'done'           => true,
    );
    $user = get_user_by( 'email', $email_address );
    if ( ! $user ) {
        return $done;
    }

    $removed = 0;
    $page = max( 1, (int) $page );
    if ( 1 === $page ) {
        $removed += chatbot_chatgpt_privacy_delete_indexed_transients( (int) $user->ID );
    }

    if ( ! chatbot_chatgpt_privacy_log_table_exists() ) {
        $done['items_removed'] = $removed;
        return $done;
    }

    global $wpdb;
    $table = chatbot_chatgpt_privacy_log_table();
    $deleted = $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$table} WHERE user_id = %s LIMIT %d",
            (string) $user->ID,
            100
        )
    );
    if ( false === $deleted ) {
        $done['items_removed'] = $removed;
        $done['items_retained'] = true;
        $done['messages'][] = __( 'Chatbot conversation rows could not be deleted.', 'chatbot-chatgpt' );
        return $done;
    }

    $removed += (int) $deleted;
    $done['items_removed'] = $removed;
    $done['done'] = ( (int) $deleted ) < 100;
    return $done;
}

/**
 * Delete transients recorded while this account was chatting.
 *
 * @param int $user_id WordPress user id.
 * @return int
 */
function chatbot_chatgpt_privacy_delete_indexed_transients( $user_id ) {
    $user_id = (int) $user_id;
    if ( $user_id <= 0 || ! function_exists( 'get_user_meta' ) ) {
        return 0;
    }

    $stored = get_user_meta( $user_id, 'chatbot_chatgpt_private_transients', true );
    $removed = 0;
    if ( is_array( $stored ) ) {
        foreach ( $stored as $key ) {
            if ( ! is_string( $key ) || ! preg_match( '/^(kchat_ctx_|chatbot_chatgpt_tts_last_)[a-f0-9]{32}$/', $key ) ) {
                continue;
            }
            delete_transient( $key );
            $removed++;
        }
    }
    delete_user_meta( $user_id, 'chatbot_chatgpt_private_transients' );
    return $removed;
}
