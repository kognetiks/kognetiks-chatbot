<?php
/**
 * Kognetiks Chatbot - Knowledge Navigator export storage and retirement.
 *
 * Analysis output is stored in non-autoloaded options and streamed to an
 * administrator after a capability and nonce check. CSV and JSON files are not
 * left in the public plugin directory. Options and PHP authorization do not
 * protect a file the web server can read directly, so those files are removed.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

require_once dirname( __DIR__ ) . '/utilities/chatbot-content-eligibility.php';

/**
 * Basenames previously written under the public results directory.
 *
 * @return string[]
 */
function chatbot_chatgpt_kn_public_export_basenames() {
    return array(
        'results.csv',
        'results.json',
        'tfidf_results.csv',
        'results-comments.log',
        'results-pages.log',
        'results-posts.log',
    );
}

/**
 * Option name for a stored analysis export. Anything else is rejected.
 *
 * @param string $which csv or json.
 * @return string
 */
function chatbot_chatgpt_kn_export_storage_key( $which ) {
    if ( $which === 'csv' ) {
        return 'chatbot_chatgpt_kn_export_csv';
    }
    if ( $which === 'json' ) {
        return 'chatbot_chatgpt_kn_export_json';
    }

    return '';
}

/**
 * Nonce action for the analysis download.
 *
 * @return string
 */
function chatbot_chatgpt_kn_download_nonce_action() {
    return 'chatbot_chatgpt_kn_analysis_download_csv';
}

/**
 * Why a download request must be rejected, or an empty string when it may proceed.
 *
 * @return string anonymous, forbidden, invalid_nonce, or an empty string.
 */
function chatbot_chatgpt_kn_download_authorization_error() {
    if ( ! function_exists( 'is_user_logged_in' ) || ! is_user_logged_in() ) {
        return 'anonymous';
    }

    if ( ! function_exists( 'current_user_can' ) || ! current_user_can( 'manage_options' ) ) {
        return 'forbidden';
    }

    $nonce = '';
    if ( isset( $_REQUEST['_wpnonce'] ) ) {
        $raw = wp_unslash( $_REQUEST['_wpnonce'] );
        if ( is_string( $raw ) ) {
            $nonce = sanitize_text_field( $raw );
        }
    }

    if ( $nonce === '' || ! function_exists( 'wp_verify_nonce' ) || ! wp_verify_nonce( $nonce, chatbot_chatgpt_kn_download_nonce_action() ) ) {
        return 'invalid_nonce';
    }

    return '';
}

/**
 * Delete known public analysis files inside one directory.
 *
 * Only the allowlisted basenames are removed. A symlink is unlinked without
 * deleting a target outside the directory. Returns false when the directory
 * exists but cannot be resolved, so the caller can retry.
 *
 * @param string|null $directory Results directory. Null uses the plugin results folder.
 * @return int|false Number of files removed, or false when the directory cannot be checked.
 */
function chatbot_chatgpt_kn_remove_public_export_files( $directory = null ) {
    if ( null === $directory ) {
        global $chatbot_chatgpt_plugin_dir_path;
        $directory = rtrim( (string) $chatbot_chatgpt_plugin_dir_path, '/\\' ) . '/results';
    }

    $directory = rtrim( (string) $directory, '/\\' );
    if ( $directory === '' || ! is_dir( $directory ) ) {
        return 0;
    }

    $real_dir = realpath( $directory );
    if ( false === $real_dir || ! is_dir( $real_dir ) ) {
        return false;
    }

    $prefix  = strtolower( str_replace( '\\', '/', $real_dir ) ) . '/';
    $deleted = 0;

    foreach ( chatbot_chatgpt_kn_public_export_basenames() as $base ) {
        if ( ! in_array( $base, chatbot_chatgpt_kn_public_export_basenames(), true ) ) {
            continue;
        }

        $candidate = $real_dir . DIRECTORY_SEPARATOR . $base;
        if ( ! file_exists( $candidate ) && ! is_link( $candidate ) ) {
            continue;
        }

        if ( is_link( $candidate ) ) {
            if ( unlink( $candidate ) ) {
                $deleted++;
            }
            continue;
        }

        $real_file = realpath( $candidate );
        if ( false === $real_file || ! is_file( $real_file ) ) {
            continue;
        }

        $normalized = strtolower( str_replace( '\\', '/', $real_file ) );
        if ( strpos( $normalized, $prefix ) !== 0 ) {
            continue;
        }

        if ( function_exists( 'wp_delete_file' ) ) {
            wp_delete_file( $real_file );
        } else {
            unlink( $real_file );
        }

        if ( ! file_exists( $real_file ) ) {
            $deleted++;
        }
    }

    return $deleted;
}

/**
 * Normalize analyzer rows to word/score pairs.
 *
 * @param mixed $rows Objects, associative word lists, or list rows.
 * @return array<int, array{word: string, score: mixed}>
 */
function chatbot_chatgpt_kn_normalize_analysis_rows( $rows ) {
    $normalized = array();

    if ( ! is_array( $rows ) && ! is_object( $rows ) ) {
        return $normalized;
    }

    foreach ( $rows as $key => $row ) {
        $word  = '';
        $score = '';

        if ( is_object( $row ) ) {
            $word  = isset( $row->word ) ? (string) $row->word : '';
            $score = isset( $row->score ) ? $row->score : '';
        } elseif ( is_array( $row ) ) {
            if ( isset( $row['word'] ) ) {
                $word  = (string) $row['word'];
                $score = isset( $row['score'] ) ? $row['score'] : '';
            } elseif ( is_string( $key ) ) {
                $word  = $key;
                $score = $row;
            }
        } elseif ( is_string( $key ) ) {
            $word  = $key;
            $score = $row;
        }

        if ( $word === '' ) {
            continue;
        }

        $normalized[] = array(
            'word'  => $word,
            'score' => $score,
        );
    }

    return $normalized;
}

/**
 * Store analysis output in options and remove any public copy of those files.
 *
 * @param mixed $rows Analysis rows.
 * @return bool
 */
function chatbot_chatgpt_kn_store_analysis_rows( $rows ) {
    $rows = chatbot_chatgpt_kn_normalize_analysis_rows( $rows );

    $handle = fopen( 'php://temp', 'w+' );
    if ( false === $handle ) {
        return false;
    }

    fputcsv( $handle, array( 'Word', 'TF-IDF' ) );
    foreach ( $rows as $row ) {
        fputcsv( $handle, array( $row['word'], $row['score'] ) );
    }
    rewind( $handle );
    $csv = stream_get_contents( $handle );
    fclose( $handle );

    if ( ! is_string( $csv ) ) {
        return false;
    }

    $csv_key  = chatbot_chatgpt_kn_export_storage_key( 'csv' );
    $json_key = chatbot_chatgpt_kn_export_storage_key( 'json' );
    if ( $csv_key === '' || $json_key === '' ) {
        return false;
    }

    $json = wp_json_encode( $rows );
    if ( ! is_string( $json ) ) {
        return false;
    }

    update_option( $csv_key, $csv, false );
    update_option( $json_key, $json, false );

    $removed = chatbot_chatgpt_kn_remove_public_export_files();

    return ( false !== $removed );
}

/**
 * Knowledge Navigator table name for one known suffix.
 *
 * @param string $suffix Table suffix without the WordPress prefix.
 * @return string
 */
function chatbot_chatgpt_kn_table_name( $suffix ) {
    global $wpdb;

    $allowed = array(
        'chatbot_chatgpt_knowledge_base',
        'chatbot_chatgpt_knowledge_base_tfidf',
        'chatbot_chatgpt_knowledge_base_word_count',
    );

    if ( ! in_array( $suffix, $allowed, true ) || ! isset( $wpdb->prefix ) ) {
        return '';
    }

    $name = $wpdb->prefix . $suffix;
    if ( ! preg_match( '/^[A-Za-z0-9_]+$/', $name ) ) {
        return '';
    }

    return $name;
}

/**
 * Whether a plugin table exists. Underscores are escaped for LIKE.
 *
 * @param string $table Validated table name.
 * @return bool
 */
function chatbot_chatgpt_kn_table_exists( $table ) {
    global $wpdb;

    if ( $table === '' || ! isset( $wpdb ) ) {
        return false;
    }

    $like  = $wpdb->esc_like( $table );
    $found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $like ) );

    return ( $found === $table );
}

/**
 * Delete every row from a known Knowledge Navigator table without dropping it.
 *
 * @param string $suffix Table suffix.
 * @return bool
 */
function chatbot_chatgpt_kn_delete_all_rows( $suffix ) {
    global $wpdb;

    $table = chatbot_chatgpt_kn_table_name( $suffix );
    if ( $table === '' || ! chatbot_chatgpt_kn_table_exists( $table ) ) {
        return true;
    }

    $wpdb->last_error = '';
    $result           = $wpdb->query( "DELETE FROM `{$table}`" );

    return ( false !== $result && $wpdb->last_error === '' );
}

/**
 * Clear aggregates that mix words from many posts and cannot be filtered by source.
 *
 * An in-progress acquisition is stopped so it cannot write those words back.
 *
 * @return bool
 */
function chatbot_chatgpt_kn_invalidate_unscoped_knowledge() {
    $tfidf = chatbot_chatgpt_kn_delete_all_rows( 'chatbot_chatgpt_knowledge_base_tfidf' );
    $words = chatbot_chatgpt_kn_delete_all_rows( 'chatbot_chatgpt_knowledge_base_word_count' );
    if ( ! $tfidf || ! $words ) {
        return false;
    }

    update_option( 'chatbot_chatgpt_kn_conversation_context', '', false );
    delete_option( 'chatbot_chatgpt_kn_export_csv' );
    delete_option( 'chatbot_chatgpt_kn_export_json' );

    return true;
}

/**
 * Remove one batch of knowledge-base rows whose source post is no longer eligible.
 *
 * Eligible public rows are left in place. Progress is stored so a failed batch can resume.
 *
 * @return void
 */
function chatbot_chatgpt_kn_revalidate_knowledge_base() {
    global $wpdb;

    if ( get_option( 'chatbot_chatgpt_kn_aggregates_invalidated_v1', '' ) !== 'yes' ) {
        if ( ! chatbot_chatgpt_kn_invalidate_unscoped_knowledge() ) {
            wp_schedule_single_event( time() + 120, 'chatbot_chatgpt_kn_revalidate_knowledge_base' );
            return;
        }
        update_option( 'chatbot_chatgpt_kn_aggregates_invalidated_v1', 'yes', false );
        wp_schedule_single_event( time() + 30, 'chatbot_chatgpt_kn_revalidate_knowledge_base' );
        return;
    }

    $table = chatbot_chatgpt_kn_table_name( 'chatbot_chatgpt_knowledge_base' );
    if ( $table === '' || ! chatbot_chatgpt_kn_table_exists( $table ) ) {
        update_option( 'chatbot_chatgpt_protected_content_migration_v1', 'completed', false );
        return;
    }

    if ( get_option( 'chatbot_chatgpt_kn_revalidate_null_pids_v1', '' ) !== 'yes' ) {
        $wpdb->last_error = '';
        $deleted          = $wpdb->query( "DELETE FROM `{$table}` WHERE pid IS NULL OR pid = 0" );
        if ( false === $deleted || $wpdb->last_error !== '' ) {
            wp_schedule_single_event( time() + 120, 'chatbot_chatgpt_kn_revalidate_knowledge_base' );
            return;
        }
        update_option( 'chatbot_chatgpt_kn_revalidate_null_pids_v1', 'yes', false );
    }

    $last = (int) get_option( 'chatbot_chatgpt_kn_revalidate_last_pid', 0 );
    $wpdb->last_error = '';
    $pids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT DISTINCT pid FROM `{$table}` WHERE pid > %d ORDER BY pid ASC LIMIT %d",
            $last,
            100
        )
    );

    if ( ! is_array( $pids ) || $wpdb->last_error !== '' ) {
        wp_schedule_single_event( time() + 120, 'chatbot_chatgpt_kn_revalidate_knowledge_base' );
        return;
    }

    if ( empty( $pids ) ) {
        update_option( 'chatbot_chatgpt_protected_content_migration_v1', 'completed', false );
        update_option(
            'chatbot_chatgpt_kn_status',
            'Knowledge Navigator topic words were cleared because older indexes could not separate protected content. Run Knowledge Navigator again to rebuild them from public content.'
        );
        return;
    }

    foreach ( $pids as $pid ) {
        $pid = (int) $pid;
        if ( $pid <= 0 ) {
            continue;
        }

        if ( ! chatbot_chatgpt_is_post_eligible_for_knowledge( $pid ) ) {
            $wpdb->last_error = '';
            $deleted          = $wpdb->delete( $table, array( 'pid' => $pid ), array( '%d' ) );
            if ( false === $deleted || $wpdb->last_error !== '' ) {
                wp_schedule_single_event( time() + 120, 'chatbot_chatgpt_kn_revalidate_knowledge_base' );
                return;
            }
        }

        update_option( 'chatbot_chatgpt_kn_revalidate_last_pid', $pid, false );
    }

    wp_schedule_single_event( time() + 30, 'chatbot_chatgpt_kn_revalidate_knowledge_base' );
}
add_action( 'chatbot_chatgpt_kn_revalidate_knowledge_base', 'chatbot_chatgpt_kn_revalidate_knowledge_base' );

/**
 * Retire public analysis files and schedule source checks for existing indexes.
 *
 * File removal and the prompt-context clear are bounded. Row deletion runs later, one batch at a time.
 *
 * @return void
 */
function chatbot_chatgpt_protected_content_transition() {
    if ( get_option( 'chatbot_chatgpt_protected_content_files_retired_v1', '' ) !== 'yes' ) {
        $removed = chatbot_chatgpt_kn_remove_public_export_files();
        if ( false === $removed ) {
            return;
        }
        update_option( 'chatbot_chatgpt_protected_content_files_retired_v1', 'yes', false );
    }

    if ( get_option( 'chatbot_chatgpt_protected_content_prompt_cleared_v1', '' ) !== 'yes' ) {
        $had_context = (string) get_option( 'chatbot_chatgpt_kn_conversation_context', '' ) !== '';
        update_option( 'chatbot_chatgpt_kn_conversation_context', '', false );
        delete_option( 'chatbot_chatgpt_kn_export_csv' );
        delete_option( 'chatbot_chatgpt_kn_export_json' );

        $action = (string) get_option( 'chatbot_chatgpt_kn_action', '' );
        $active = array( 'phase 1', 'phase 2', 'phase 3', 'phase 4', 'phase 5', 'phase 6', 'phase 7', 'phase 8' );
        if ( in_array( $action, $active, true ) ) {
            update_option( 'chatbot_chatgpt_kn_action', 'cancel' );
            if ( function_exists( 'wp_clear_scheduled_hook' ) ) {
                wp_clear_scheduled_hook( 'chatbot_kn_acquire_controller' );
            }
            $had_context = true;
        }

        if ( $had_context ) {
            update_option(
                'chatbot_chatgpt_kn_status',
                'Knowledge Navigator topic words were cleared because older indexes could not separate protected content. Run Knowledge Navigator again to rebuild them from public content.'
            );
        }

        update_option( 'chatbot_chatgpt_protected_content_prompt_cleared_v1', 'yes', false );
    }

    if ( get_option( 'chatbot_chatgpt_protected_content_migration_v1', '' ) === 'completed' ) {
        return;
    }

    if ( function_exists( 'wp_next_scheduled' ) && function_exists( 'wp_schedule_single_event' ) ) {
        if ( ! wp_next_scheduled( 'chatbot_chatgpt_kn_revalidate_knowledge_base' ) ) {
            wp_schedule_single_event( time() + 60, 'chatbot_chatgpt_kn_revalidate_knowledge_base' );
        }
    }
}
add_action( 'init', 'chatbot_chatgpt_protected_content_transition', 22 );
