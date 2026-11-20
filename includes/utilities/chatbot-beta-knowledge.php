<?php
/**
 * Kognetiks Chatbot - Disposable beta knowledge engines.
 *
 * Sentential Context, Lexical Context, and Markov Chain indexes are derived data.
 * This file refuses artifacts built before the protected-content revision and
 * clears them once per site. It does not keep the old model contents.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

require_once __DIR__ . '/chatbot-generated-storage.php';
require_once __DIR__ . '/chatbot-generated-file-integrity.php';

/**
 * Revision stamped onto a clean rebuild. Older files and rows do not carry it.
 *
 * @return string
 */
function chatbot_chatgpt_beta_knowledge_revision() {
    return 'eligibility-v1';
}

/**
 * Beta engines whose derived data is disposable.
 *
 * @return string[]
 */
function chatbot_chatgpt_beta_knowledge_engines() {
    return array( 'sentential', 'lexical', 'markov' );
}

/**
 * @param string $engine Engine slug.
 * @return bool
 */
function chatbot_chatgpt_beta_engine_is_known( $engine ) {
    return in_array( (string) $engine, chatbot_chatgpt_beta_knowledge_engines(), true );
}

/**
 * @param string $engine Engine slug.
 * @return string
 */
function chatbot_chatgpt_beta_engine_state_key( $engine ) {
    return 'chatbot_chatgpt_beta_engine_' . $engine . '_state';
}

/**
 * @param string $engine Engine slug.
 * @return string
 */
function chatbot_chatgpt_beta_engine_generation_key( $engine ) {
    return 'chatbot_chatgpt_beta_engine_' . $engine . '_generation';
}

/**
 * @param string $engine Engine slug.
 * @return string
 */
function chatbot_chatgpt_beta_engine_build_generation_key( $engine ) {
    return 'chatbot_chatgpt_beta_engine_' . $engine . '_build_generation';
}

/**
 * invalid, building, ready, or failed. A missing value is invalid.
 *
 * @param string $engine Engine slug.
 * @return string
 */
function chatbot_chatgpt_beta_engine_state( $engine ) {
    if ( ! chatbot_chatgpt_beta_engine_is_known( $engine ) || ! function_exists( 'get_option' ) ) {
        return 'invalid';
    }

    $state = (string) get_option( chatbot_chatgpt_beta_engine_state_key( $engine ), '' );
    if ( ! in_array( $state, array( 'invalid', 'building', 'ready', 'failed' ), true ) ) {
        return 'invalid';
    }

    return $state;
}

/**
 * @param string $engine Engine slug.
 * @return int
 */
function chatbot_chatgpt_beta_engine_generation( $engine ) {
    if ( ! function_exists( 'get_option' ) ) {
        return 0;
    }

    return (int) get_option( chatbot_chatgpt_beta_engine_generation_key( $engine ), 0 );
}

/**
 * Plugin directory. Legacy public caches live under this tree.
 *
 * @return string
 */
function chatbot_chatgpt_beta_plugin_directory() {
    return dirname( __DIR__, 2 );
}

/**
 * Normalize a path and reject parent-directory segments.
 *
 * @param string $path Raw path.
 * @return string
 */
function chatbot_chatgpt_beta_normalize_path( $path ) {
    $path = trim( str_replace( '\\', '/', (string) $path ) );
    if ( $path === '' || strpos( $path, "\0" ) !== false ) {
        return '';
    }

    $drive = '';
    if ( preg_match( '/^[A-Za-z]:\//', $path ) ) {
        $drive = substr( $path, 0, 2 );
        $path  = substr( $path, 2 );
    }
    $absolute = ( $drive !== '' || substr( $path, 0, 1 ) === '/' );
    $parts    = explode( '/', $path );
    $kept     = array();
    foreach ( $parts as $part ) {
        if ( $part === '' || $part === '.' ) {
            continue;
        }
        if ( $part === '..' ) {
            return '';
        }
        $kept[] = $part;
    }

    $body = implode( '/', $kept );
    if ( $drive !== '' ) {
        return $drive . '/' . $body;
    }
    if ( $absolute ) {
        return '/' . $body;
    }

    return $body;
}

/**
 * @param string $path Path.
 * @return bool
 */
function chatbot_chatgpt_beta_path_is_absolute( $path ) {
    $path = chatbot_chatgpt_beta_normalize_path( $path );

    return $path !== '' && ( substr( $path, 0, 1 ) === '/' || (bool) preg_match( '/^[A-Za-z]:\//', $path ) );
}

/**
 * Whether $path is the same as $root or a descendant of it.
 *
 * @param string $path Candidate.
 * @param string $root Boundary.
 * @return bool
 */
function chatbot_chatgpt_beta_path_is_within_root( $path, $root ) {
    $path = strtolower( rtrim( chatbot_chatgpt_beta_normalize_path( $path ), '/' ) );
    $root = strtolower( rtrim( chatbot_chatgpt_beta_normalize_path( $root ), '/' ) );
    if ( $path === '' || $root === '' ) {
        return false;
    }

    return $path === $root || strpos( $path, $root . '/' ) === 0;
}

/**
 * Apply ordinary plugin file modes. These modes do not make a public file confidential.
 *
 * @param string $path File or directory.
 * @return void
 */
function chatbot_chatgpt_beta_apply_cache_permissions( $path ) {
    if ( is_dir( $path ) ) {
        @chmod( $path, 0755 );
    } elseif ( is_file( $path ) ) {
        @chmod( $path, 0644 );
    }
}

/**
 * Legacy cache root inside the plugin package. New files are not written here.
 *
 * @param string $engine Engine slug.
 * @return string
 */
function chatbot_chatgpt_beta_plugin_cache_root( $engine ) {
    $plugin = chatbot_chatgpt_beta_plugin_directory();
    if ( $engine === 'sentential' ) {
        return $plugin . '/includes/transformers/sentential_embeddings_cache';
    }
    if ( $engine === 'lexical' ) {
        return $plugin . '/includes/transformers/lexical_embeddings_cache';
    }

    return '';
}

/**
 * Directory that still holds pre-isolation shared files. Site directories are not cleaned here.
 *
 * @param string $engine Engine slug.
 * @return string
 */
function chatbot_chatgpt_beta_legacy_plugin_cache_root( $engine ) {
    if ( isset( $GLOBALS['chatbot_chatgpt_beta_legacy_cache_roots'][ $engine ] ) ) {
        return rtrim( (string) $GLOBALS['chatbot_chatgpt_beta_legacy_cache_roots'][ $engine ], '/\\' );
    }

    return chatbot_chatgpt_beta_plugin_cache_root( $engine );
}

/**
 * Create a cache root when needed and reject a root that is not a real writable directory.
 *
 * There is no fallback to another directory.
 *
 * @param string $root Candidate root.
 * @return string
 */
function chatbot_chatgpt_beta_prepare_cache_root( $root ) {
    $root = chatbot_chatgpt_beta_normalize_path( $root );
    if ( $root === '' || ! chatbot_chatgpt_beta_path_is_absolute( $root ) || is_link( $root ) || is_file( $root ) ) {
        return '';
    }
    if ( ! is_dir( $root ) ) {
        $made = function_exists( 'wp_mkdir_p' ) ? @wp_mkdir_p( $root ) : @mkdir( $root, 0755, true );
        if ( ( ! $made && ! is_dir( $root ) ) || is_link( $root ) ) {
            return '';
        }
    }
    chatbot_chatgpt_beta_apply_cache_permissions( $root );
    if ( ! is_dir( $root ) || ! is_writable( $root ) ) {
        return '';
    }

    $real = realpath( $root );
    if ( false === $real || is_link( $root ) ) {
        return '';
    }
    $real = chatbot_chatgpt_beta_normalize_path( $real );
    if ( $real === '' || strtolower( $real ) !== strtolower( $root ) ) {
        return '';
    }

    return $real;
}

/**
 * Cache root for one engine. The test override replaces this root and is not a site directory.
 *
 * The path is not cached. switch_to_blog() changes the prefix on the next call.
 *
 * @param string $engine Engine slug.
 * @return string
 */
function chatbot_chatgpt_beta_engine_cache_root( $engine ) {
    if ( $engine !== 'sentential' && $engine !== 'lexical' ) {
        return '';
    }
    if ( isset( $GLOBALS['chatbot_chatgpt_beta_cache_directories'][ $engine ] ) ) {
        $root = (string) $GLOBALS['chatbot_chatgpt_beta_cache_directories'][ $engine ];
    } else {
        $slug = ( 'sentential' === $engine ) ? 'sentential_embeddings_cache' : 'lexical_embeddings_cache';
        $root = chatbot_chatgpt_generated_storage_directory( $slug );
        if ( $root === '' ) {
            return '';
        }
    }

    return chatbot_chatgpt_beta_prepare_cache_root( $root );
}

/**
 * Directory name for the active site. This is the site table prefix, such as wp_ or wp_2_.
 *
 * @return string
 */
function chatbot_chatgpt_beta_site_directory_segment() {
    global $wpdb;

    $blog_id = function_exists( 'get_current_blog_id' ) ? (int) get_current_blog_id() : 0;
    $prefix  = '';
    if ( isset( $wpdb ) && is_object( $wpdb ) && method_exists( $wpdb, 'get_blog_prefix' ) ) {
        $prefix = (string) $wpdb->get_blog_prefix( $blog_id > 0 ? $blog_id : null );
    } elseif ( isset( $wpdb->prefix ) ) {
        $prefix = (string) $wpdb->prefix;
    }

    $prefix = str_replace( '\\', '/', $prefix );
    if ( ! preg_match( '/^[A-Za-z0-9_]+$/', $prefix ) ) {
        return '';
    }

    return $prefix;
}

/**
 * Site-specific cache directory for a file-backed engine, or an empty string for Markov.
 *
 * @param string $engine Engine slug.
 * @return string
 */
function chatbot_chatgpt_beta_engine_cache_directory( $engine ) {
    if ( $engine !== 'sentential' && $engine !== 'lexical' ) {
        return '';
    }

    $root    = chatbot_chatgpt_beta_engine_cache_root( $engine );
    $segment = chatbot_chatgpt_beta_site_directory_segment();
    if ( $root === '' || $segment === '' ) {
        return '';
    }

    $directory = $root . '/' . $segment;
    if ( is_link( $directory ) ) {
        return '';
    }
    if ( is_dir( $directory ) ) {
        $real = realpath( $directory );
        if ( false === $real || ! chatbot_chatgpt_beta_path_is_within_root( $real, $root ) ) {
            return '';
        }
        chatbot_chatgpt_beta_apply_cache_permissions( $directory );
    }

    return $directory;
}

/**
 * Whether a path stays inside a directory. Parent segments are rejected.
 *
 * @param string $path      File path.
 * @param string $directory Directory path.
 * @return bool
 */
function chatbot_chatgpt_beta_path_is_inside( $path, $directory ) {
    $path      = str_replace( '\\', '/', (string) $path );
    $directory = rtrim( str_replace( '\\', '/', (string) $directory ), '/' );
    if ( $path === '' || $directory === '' ) {
        return false;
    }
    if ( strpos( strtolower( $path ), strtolower( $directory ) . '/' ) !== 0 ) {
        return false;
    }

    $relative = substr( $path, strlen( $directory ) + 1 );

    return $relative !== '' && strpos( $relative, '..' ) === false;
}

/**
 * @param string $engine Engine slug.
 * @return string
 */
function chatbot_chatgpt_beta_engine_stamp_path( $engine ) {
    $directory = chatbot_chatgpt_beta_engine_cache_directory( $engine );
    if ( $directory === '' ) {
        return '';
    }

    return $directory . '/eligibility-revision.txt';
}

/**
 * Whether the derived artifact was written by a clean rebuild of this revision.
 *
 * @param string $engine Engine slug.
 * @return bool
 */
function chatbot_chatgpt_beta_engine_stamp_matches( $engine ) {
    if ( ! chatbot_chatgpt_beta_engine_is_known( $engine ) ) {
        return false;
    }

    $revision = chatbot_chatgpt_beta_knowledge_revision();
    if ( $engine === 'markov' ) {
        if ( ! function_exists( 'get_option' ) ) {
            return false;
        }

        return (string) get_option( 'chatbot_chatgpt_beta_engine_markov_stamp', '' ) === $revision;
    }

    $path = chatbot_chatgpt_beta_engine_stamp_path( $engine );
    if ( $path === '' || ! is_file( $path ) ) {
        return false;
    }

    $stamp = file_get_contents( $path );

    return is_string( $stamp ) && trim( $stamp ) === $revision;
}

/**
 * A ready engine has finished a clean rebuild and still has that rebuild's stamp.
 *
 * @param string $engine Engine slug.
 * @return bool
 */
function chatbot_chatgpt_beta_engine_is_ready( $engine ) {
    if ( function_exists( 'chatbot_chatgpt_gfi_review_abandoned_build' ) ) {
        chatbot_chatgpt_gfi_review_abandoned_build( $engine );
    }
    if ( chatbot_chatgpt_beta_engine_state( $engine ) !== 'ready' || ! chatbot_chatgpt_beta_engine_stamp_matches( $engine ) ) {
        return false;
    }
    if ( $engine === 'markov' || ! function_exists( 'chatbot_chatgpt_gfi_generation_is_trusted' ) ) {
        return true;
    }

    return chatbot_chatgpt_gfi_generation_is_trusted( $engine, true );
}

/**
 * Delete derived files in one directory. index.php and .htaccess stay.
 *
 * Missing directories are success. An unreadable directory returns false so the caller can retry.
 *
 * @param string $directory Absolute directory.
 * @return int|false
 */
function chatbot_chatgpt_beta_purge_directory( $directory ) {
    $directory = rtrim( (string) $directory, '/\\' );
    if ( $directory === '' || ! is_dir( $directory ) ) {
        return 0;
    }
    if ( is_link( $directory ) ) {
        return false;
    }

    $real_dir = realpath( $directory );
    if ( false === $real_dir || ! is_dir( $real_dir ) ) {
        return false;
    }

    $entries = glob( $real_dir . DIRECTORY_SEPARATOR . '*' );
    if ( ! is_array( $entries ) ) {
        return false;
    }

    $prefix  = strtolower( str_replace( '\\', '/', $real_dir ) ) . '/';
    $deleted = 0;
    $keep    = array( 'index.php', '.htaccess', 'web.config' );

    foreach ( $entries as $entry ) {
        if ( is_link( $entry ) ) {
            if ( ! @unlink( $entry ) && ( is_link( $entry ) || file_exists( $entry ) ) ) {
                return false;
            }
            $deleted++;
            continue;
        }
        if ( ! is_file( $entry ) ) {
            continue;
        }

        $base = strtolower( basename( $entry ) );
        if ( in_array( $base, $keep, true ) ) {
            continue;
        }

        $real_file = realpath( $entry );
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
            @unlink( $real_file );
        }
        if ( file_exists( $real_file ) ) {
            return false;
        }
        $deleted++;
    }

    return $deleted;
}

/**
 * Validated Markov table name for the current site.
 *
 * @return string
 */
function chatbot_chatgpt_beta_markov_table_name() {
    global $wpdb;

    if ( ! isset( $wpdb->prefix ) ) {
        return '';
    }

    $name = $wpdb->prefix . 'chatbot_markov_chain';
    if ( ! preg_match( '/^[A-Za-z0-9_]+$/', $name ) ) {
        return '';
    }

    return $name;
}

/**
 * Remove Markov chain rows. The table definition and WordPress posts stay.
 *
 * @return bool
 */
function chatbot_chatgpt_beta_purge_markov_rows() {
    global $wpdb;

    $table = chatbot_chatgpt_beta_markov_table_name();
    if ( $table === '' || ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
        return true;
    }

    $like  = $wpdb->esc_like( $table );
    $found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $like ) );
    if ( $found !== $table ) {
        return true;
    }

    $wpdb->last_error = '';
    $result           = $wpdb->query( "DELETE FROM `{$table}`" );

    return ( false !== $result && $wpdb->last_error === '' );
}

/**
 * Remove one engine's derived files or rows.
 *
 * @param string $engine Engine slug.
 * @return bool
 */
function chatbot_chatgpt_beta_engine_purge( $engine ) {
    if ( ! chatbot_chatgpt_beta_engine_is_known( $engine ) ) {
        return false;
    }

    if ( $engine === 'markov' ) {
        $purged = chatbot_chatgpt_beta_purge_markov_rows();
        if ( function_exists( 'delete_option' ) ) {
            delete_option( 'chatbot_chatgpt_beta_engine_markov_stamp' );
        }
        if ( function_exists( 'delete_transient' ) ) {
            delete_transient( 'chatbot_markov_chain_batch_starting_points' );
        }
        if ( function_exists( 'update_option' ) ) {
            update_option( 'chatbot_markov_chain_force_rebuild', 'Yes', false );
            update_option( 'chatbot_markov_chain_last_updated', '2000-01-01 00:00:00', false );
            update_option( 'chatbot_markov_chain_build_status', 'Invalidated', false );
        }

        return $purged;
    }

    $removed = chatbot_chatgpt_beta_purge_directory( chatbot_chatgpt_beta_engine_cache_directory( $engine ) );
    if ( false === $removed ) {
        return false;
    }

    if ( $engine === 'sentential' && function_exists( 'update_option' ) ) {
        update_option( 'chatbot_transformer_model_offset', 0, false );
        update_option( 'chatbot_transformer_model_content_items_processed', 0, false );
        update_option( 'chatbot_transformer_model_build_status', 'Invalidated', false );
    }

    if ( $engine === 'lexical' && function_exists( 'delete_option' ) ) {
        delete_option( 'chatbot_lcm_lexical_rebuild_state' );
        delete_option( 'chatbot_lcm_lexical_rebuild_activity' );
        if ( function_exists( 'update_option' ) ) {
            update_option( 'chatbot_lcm_lexical_cache_rebuild_last_status', 'invalidated', false );
            update_option( 'chatbot_lcm_lexical_cache_rebuild_last_error', 'invalidated', false );
        }
        if ( function_exists( 'delete_transient' ) ) {
            delete_transient( 'chatbot_lexical_rebuild_job_pending' );
        }
    }

    return true;
}

/**
 * Mark derived data unusable and delete it. Safe to call more than once.
 *
 * The engine is marked invalid even when a file delete fails, so chatbot retrieval stops.
 * A false return means the files or rows are still present and the caller should try again.
 *
 * @param string $engine Engine slug.
 * @return bool
 */
function chatbot_chatgpt_beta_engine_invalidate( $engine ) {
    if ( ! chatbot_chatgpt_beta_engine_is_known( $engine ) || ! function_exists( 'update_option' ) ) {
        return false;
    }

    $purged     = chatbot_chatgpt_beta_engine_purge( $engine );
    $generation = chatbot_chatgpt_beta_engine_generation( $engine ) + 1;
    update_option( chatbot_chatgpt_beta_engine_generation_key( $engine ), $generation, false );
    update_option( chatbot_chatgpt_beta_engine_state_key( $engine ), 'invalid', false );
    update_option( chatbot_chatgpt_beta_engine_build_generation_key( $engine ), 0, false );
    if ( function_exists( 'chatbot_chatgpt_gfi_discard' ) ) {
        chatbot_chatgpt_gfi_discard( $engine );
    }

    return (bool) $purged;
}

/**
 * Start a rebuild. A rebuild that is already invalid is purged again.
 *
 * For GFI engines, build ownership is claimed atomically. A losing worker
 * receives 0 and must not mutate. Pass $continue_owner when continuing a
 * chunked/scan build across PHP requests.
 *
 * @param string $engine          Engine slug.
 * @param string $continue_owner  Caller-held owner from a prior successful begin.
 * @return int Generation captured for this build, or 0 on failure.
 */
function chatbot_chatgpt_beta_engine_begin( $engine, $continue_owner = '' ) {
    if ( ! chatbot_chatgpt_beta_engine_is_known( $engine ) ) {
        return 0;
    }

    $state = chatbot_chatgpt_beta_engine_state( $engine );
    if ( $state === 'building' ) {
        // Continuing the same active build (chunked workers / scan batches).
        $owner = (string) $continue_owner;
        if ( $owner === '' && function_exists( 'chatbot_chatgpt_gfi_session_owner' ) ) {
            $owner = chatbot_chatgpt_gfi_session_owner( $engine );
        }
        if ( function_exists( 'chatbot_chatgpt_gfi_is_engine' ) && chatbot_chatgpt_gfi_is_engine( $engine ) ) {
            if ( $owner === '' || ! chatbot_chatgpt_gfi_builder_may_mutate( $engine, $owner ) ) {
                return 0;
            }
            chatbot_chatgpt_gfi_remember_owner( $engine, $owner );
            chatbot_chatgpt_gfi_touch( $engine, $owner );
        } elseif ( function_exists( 'chatbot_chatgpt_gfi_touch' ) ) {
            chatbot_chatgpt_gfi_touch( $engine );
        }

        return (int) get_option( chatbot_chatgpt_beta_engine_build_generation_key( $engine ), 0 );
    }

    if ( $state !== 'ready' ) {
        chatbot_chatgpt_beta_engine_purge( $engine );
    }

    $generation = chatbot_chatgpt_beta_engine_generation( $engine );

    // Claim ownership before flipping engine state so a loser does not leave
    // a building state without a matching owner record.
    $owner = '';
    if ( function_exists( 'chatbot_chatgpt_gfi_is_engine' ) && chatbot_chatgpt_gfi_is_engine( $engine )
        && function_exists( 'chatbot_chatgpt_gfi_begin' ) ) {
        $owner = chatbot_chatgpt_gfi_begin( $engine, $generation );
        if ( $owner === '' ) {
            return 0;
        }
    }

    update_option( chatbot_chatgpt_beta_engine_state_key( $engine ), 'building', false );
    update_option( chatbot_chatgpt_beta_engine_build_generation_key( $engine ), $generation, false );

    // Defect B: a ready→building transition must not leave the previous ready
    // manifest authenticating files the rebuild is about to rewrite in place.
    if ( $state === 'ready' && function_exists( 'chatbot_chatgpt_gfi_discard_manifest' ) ) {
        chatbot_chatgpt_gfi_discard_manifest( $engine );
    }

    return $generation;
}

/**
 * Record that a full cache reset is the current build, after the caller has already deleted the files.
 *
 * @param string $engine         Engine slug.
 * @param string $continue_owner Caller-held owner when continuing.
 * @return string Owner token on success, empty string on failure.
 */
function chatbot_chatgpt_beta_engine_mark_building( $engine, $continue_owner = '' ) {
    if ( ! chatbot_chatgpt_beta_engine_is_known( $engine ) || ! function_exists( 'update_option' ) ) {
        return '';
    }

    if ( chatbot_chatgpt_beta_engine_state( $engine ) === 'building' ) {
        // Caller must present its own token (argument or request session). Do not
        // silently load job_owner — that would let a losing worker borrow the winner.
        $owner = (string) $continue_owner;
        if ( $owner === '' && function_exists( 'chatbot_chatgpt_gfi_session_owner' ) ) {
            $owner = chatbot_chatgpt_gfi_session_owner( $engine );
        }
        if ( function_exists( 'chatbot_chatgpt_gfi_is_engine' ) && chatbot_chatgpt_gfi_is_engine( $engine ) ) {
            if ( $owner === '' || ! chatbot_chatgpt_gfi_builder_may_mutate( $engine, $owner ) ) {
                return '';
            }
            chatbot_chatgpt_gfi_remember_owner( $engine, $owner );
            chatbot_chatgpt_gfi_touch( $engine, $owner );

            return $owner;
        }
        if ( function_exists( 'chatbot_chatgpt_gfi_touch' ) ) {
            chatbot_chatgpt_gfi_touch( $engine, $owner );
        }

        return $owner;
    }

    $generation = chatbot_chatgpt_beta_engine_generation( $engine );
    $owner      = '';
    if ( function_exists( 'chatbot_chatgpt_gfi_is_engine' ) && chatbot_chatgpt_gfi_is_engine( $engine )
        && function_exists( 'chatbot_chatgpt_gfi_begin' ) ) {
        $owner = chatbot_chatgpt_gfi_begin( $engine, $generation );
        if ( $owner === '' ) {
            return '';
        }
    }

    update_option( chatbot_chatgpt_beta_engine_state_key( $engine ), 'building', false );
    update_option( chatbot_chatgpt_beta_engine_build_generation_key( $engine ), $generation, false );
    if ( function_exists( 'chatbot_chatgpt_gfi_discard_manifest' ) ) {
        chatbot_chatgpt_gfi_discard_manifest( $engine );
    }

    return $owner;
}

/**
 * Whether this build may still publish its result.
 *
 * Requires an active `building` state for the captured generation. A generation
 * that is already `ready` cannot be recommitted by a stale worker.
 *
 * @param string $engine     Engine slug.
 * @param int    $generation Generation returned when the build started.
 * @param string $owner      Caller-held owner token.
 * @return bool
 */
function chatbot_chatgpt_beta_engine_build_is_current( $engine, $generation, $owner = '' ) {
    $generation = (int) $generation;
    $started    = (int) get_option( chatbot_chatgpt_beta_engine_build_generation_key( $engine ), -1 );
    if ( $started < 0 || $started !== $generation ) {
        return false;
    }
    if ( chatbot_chatgpt_beta_engine_generation( $engine ) !== $generation ) {
        return false;
    }
    if ( chatbot_chatgpt_beta_engine_state( $engine ) !== 'building' ) {
        return false;
    }
    // Ownership applies only to GFI-covered engines. Markov has no GFI build record.
    if ( function_exists( 'chatbot_chatgpt_gfi_is_engine' ) && chatbot_chatgpt_gfi_is_engine( $engine )
        && function_exists( 'chatbot_chatgpt_gfi_build_owns_generation' ) ) {
        return chatbot_chatgpt_gfi_build_owns_generation( $engine, $generation, $owner );
    }

    return true;
}

/**
 * Publish a finished rebuild. A visibility change during the build discards the result.
 *
 * @param string $engine     Engine slug.
 * @param int    $generation Generation captured at the start.
 * @param string $owner      Caller-held owner token.
 * @return bool
 */
function chatbot_chatgpt_beta_engine_commit( $engine, $generation, $owner = '' ) {
    if ( ! chatbot_chatgpt_beta_engine_is_known( $engine ) ) {
        return false;
    }

    if ( $owner === '' && function_exists( 'chatbot_chatgpt_gfi_session_owner' ) ) {
        $owner = chatbot_chatgpt_gfi_session_owner( $engine );
    }

    if ( ! chatbot_chatgpt_beta_engine_build_is_current( $engine, $generation, $owner ) ) {
        $keep = function_exists( 'chatbot_chatgpt_gfi_generation_is_trusted' )
            && chatbot_chatgpt_gfi_generation_is_trusted( $engine, false );
        if ( $keep ) {
            return false;
        }
        chatbot_chatgpt_beta_engine_purge( $engine );
        if ( function_exists( 'update_option' ) ) {
            update_option( chatbot_chatgpt_beta_engine_state_key( $engine ), 'invalid', false );
        }
        if ( function_exists( 'chatbot_chatgpt_gfi_discard' ) ) {
            chatbot_chatgpt_gfi_discard( $engine );
        }

        return false;
    }

    if ( $engine === 'markov' ) {
        update_option( 'chatbot_chatgpt_beta_engine_markov_stamp', chatbot_chatgpt_beta_knowledge_revision(), false );
    } else {
        $path = chatbot_chatgpt_beta_engine_stamp_path( $engine );
        $dir  = chatbot_chatgpt_beta_engine_cache_directory( $engine );
        if ( $path === '' ) {
            update_option( chatbot_chatgpt_beta_engine_state_key( $engine ), 'failed', false );

            return false;
        }
        if ( ! is_dir( $dir ) ) {
            $made = false;
            if ( function_exists( 'wp_mkdir_p' ) ) {
                $made = wp_mkdir_p( $dir );
            } else {
                $made = @mkdir( $dir, 0755, true );
            }
            if ( ! $made && ! is_dir( $dir ) ) {
                update_option( chatbot_chatgpt_beta_engine_state_key( $engine ), 'failed', false );

                return false;
            }
        }
        if ( is_link( $dir ) ) {
            update_option( chatbot_chatgpt_beta_engine_state_key( $engine ), 'failed', false );

            return false;
        }
        chatbot_chatgpt_beta_apply_cache_permissions( $dir );
        if ( false === file_put_contents( $path, chatbot_chatgpt_beta_knowledge_revision() ) ) {
            update_option( chatbot_chatgpt_beta_engine_state_key( $engine ), 'failed', false );

            return false;
        }
        chatbot_chatgpt_beta_apply_cache_permissions( $path );
        if ( function_exists( 'chatbot_chatgpt_gfi_publish' ) && ! chatbot_chatgpt_gfi_publish( $engine, $owner ) ) {
            update_option( chatbot_chatgpt_beta_engine_state_key( $engine ), 'failed', false );

            return false;
        }
    }

    update_option( chatbot_chatgpt_beta_engine_state_key( $engine ), 'ready', false );

    return chatbot_chatgpt_beta_engine_is_ready( $engine );
}

/**
 * Staging files created by the current lexical rebuild may be read back for verification.
 *
 * @param string $path Cache path.
 * @return bool
 */
function chatbot_chatgpt_beta_lexical_cache_is_loadable( $path ) {
    $directory = chatbot_chatgpt_beta_engine_cache_directory( 'lexical' );
    if ( ! chatbot_chatgpt_beta_path_is_inside( $path, $directory ) ) {
        return false;
    }

    $base = strtolower( basename( (string) $path ) );
    if ( strpos( $base, '.staging.' ) !== false ) {
        return true;
    }

    if ( ! chatbot_chatgpt_beta_engine_stamp_matches( 'lexical' ) ) {
        return false;
    }
    if ( function_exists( 'chatbot_chatgpt_gfi_file_is_trusted' ) ) {
        return chatbot_chatgpt_gfi_file_is_trusted( 'lexical', $path );
    }

    return true;
}

/**
 * Message returned when a beta model must not answer from old derived data.
 *
 * @return string
 */
function chatbot_chatgpt_beta_engine_unavailable_message() {
    return 'This local model is unavailable until it is rebuilt from public site content.';
}

/**
 * Compare two post snapshots and invalidate every beta engine when eligibility changes.
 *
 * @param object|null $before Earlier post.
 * @param object|null $after  Later post.
 * @return bool Whether the engines were invalidated.
 */
function chatbot_chatgpt_beta_knowledge_visibility_changed( $before, $after ) {
    $before_ok = chatbot_chatgpt_post_snapshot_is_eligible( $before );
    $after_ok  = chatbot_chatgpt_post_snapshot_is_eligible( $after );
    if ( $before_ok === $after_ok ) {
        return false;
    }

    foreach ( chatbot_chatgpt_beta_knowledge_engines() as $engine ) {
        chatbot_chatgpt_beta_engine_invalidate( $engine );
    }

    return true;
}

/**
 * @param int          $post_id Post ID.
 * @param object|null  $after   Updated post.
 * @param object|null  $before  Previous post.
 * @return void
 */
function chatbot_chatgpt_beta_knowledge_post_updated( $post_id, $after, $before ) {
    unset( $post_id );
    if ( is_object( $after ) && isset( $after->post_type ) && $after->post_type === 'revision' ) {
        return;
    }

    chatbot_chatgpt_beta_knowledge_visibility_changed( $before, $after );
}
add_action( 'post_updated', 'chatbot_chatgpt_beta_knowledge_post_updated', 10, 3 );

/**
 * @param string $new_status New status.
 * @param string $old_status Old status.
 * @param object $post       Post object.
 * @return void
 */
function chatbot_chatgpt_beta_knowledge_status_transition( $new_status, $old_status, $post ) {
    if ( $new_status === $old_status || ! is_object( $post ) ) {
        return;
    }
    if ( isset( $post->post_type ) && $post->post_type === 'revision' ) {
        return;
    }

    $before = clone $post;
    $after  = clone $post;
    $before->post_status = (string) $old_status;
    $after->post_status  = (string) $new_status;
    chatbot_chatgpt_beta_knowledge_visibility_changed( $before, $after );
}
add_action( 'transition_post_status', 'chatbot_chatgpt_beta_knowledge_status_transition', 10, 3 );

/**
 * A deleted public post can remain inside an aggregate model, so the model is discarded.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function chatbot_chatgpt_beta_knowledge_post_deleted( $post_id ) {
    if ( ! function_exists( 'get_post' ) ) {
        return;
    }

    $post = get_post( (int) $post_id );
    if ( ! chatbot_chatgpt_post_snapshot_is_eligible( $post ) ) {
        return;
    }

    foreach ( chatbot_chatgpt_beta_knowledge_engines() as $engine ) {
        chatbot_chatgpt_beta_engine_invalidate( $engine );
    }
}
add_action( 'before_delete_post', 'chatbot_chatgpt_beta_knowledge_post_deleted' );

/**
 * Whether a filename in the old shared cache root is a disposable generated file.
 *
 * Site directories are not files, so they are never selected.
 *
 * @param string $engine   sentential or lexical.
 * @param string $filename Basename.
 * @return bool
 */
function chatbot_chatgpt_beta_legacy_cache_filename_is_disposable( $engine, $filename ) {
    $filename = strtolower( str_replace( '\\', '/', (string) $filename ) );
    if ( $filename === '' || strpos( $filename, '/' ) !== false || strpos( $filename, '..' ) !== false ) {
        return false;
    }
    if ( in_array( $filename, array( 'index.php', '.htaccess', 'web.config' ), true ) ) {
        return false;
    }

    if ( $engine === 'sentential' ) {
        return $filename === 'eligibility-revision.txt' || (bool) preg_match( '/^(?:[a-z]|other)\.php$/', $filename );
    }

    if ( $engine !== 'lexical' ) {
        return false;
    }

    $exact = array(
        'lexical_embeddings_cache.php',
        'lexical_embeddings_cache.php.gz',
        'lexical_embeddings_cache.php.ser',
        'lexical_embeddings_cache.php.old',
        'lexical_embeddings_cache_version.txt',
        'lexical_local_idf_cache.json',
    );
    if ( in_array( $filename, $exact, true ) ) {
        return true;
    }

    $patterns = array(
        '/^lexical_embeddings_cache\.staging\.[a-z0-9_-]+\.php(?:\.gz|\.ser)?$/',
        '/^lexical_embeddings_cache_version\.staging\.[a-z0-9_-]+\.txt$/',
        '/^lexical_local_idf_cache\.staging\.[a-z0-9_-]+\.json$/',
        '/^lexical_rebuild_pmi\.[a-z0-9_-]+\.bin$/',
        '/^lexical_rebuild_corpus\.[a-z0-9_-]+\.txt$/',
        '/^lexical_rebuild_counts\.[a-z0-9_-]+(?:\.(?:co|wc|extra))?\.bin$/',
        '/\.lcm_bak_[a-z0-9_-]+$/',
        '/\.bak$/',
    );
    foreach ( $patterns as $pattern ) {
        if ( preg_match( $pattern, $filename ) ) {
            return true;
        }
    }

    return false;
}

/**
 * Delete one known generated file or a symlink that uses that name.
 *
 * The symlink itself is removed. Its target is not followed.
 *
 * @param string $engine Engine slug.
 * @param string $entry  Absolute path.
 * @param string $root   Directory that must contain the file.
 * @return int|false
 */
function chatbot_chatgpt_beta_delete_legacy_cache_file( $engine, $entry, $root ) {
    $name = basename( (string) $entry );
    if ( ! chatbot_chatgpt_beta_legacy_cache_filename_is_disposable( $engine, $name ) ) {
        return 0;
    }
    if ( is_link( $entry ) ) {
        if ( ! unlink( $entry ) && file_exists( $entry ) ) {
            return false;
        }

        return file_exists( $entry ) ? false : 1;
    }
    if ( ! is_file( $entry ) ) {
        return 0;
    }

    $real_file = realpath( $entry );
    $real_root = realpath( $root );
    if ( false === $real_file || false === $real_root || ! is_file( $real_file ) ) {
        return false;
    }
    if ( ! chatbot_chatgpt_beta_path_is_within_root( $real_file, $real_root ) ) {
        return 0;
    }
    $parent = dirname( str_replace( '\\', '/', $real_file ) );
    $root_n = rtrim( str_replace( '\\', '/', $real_root ), '/' );
    if ( strtolower( $parent ) !== strtolower( $root_n ) ) {
        return 0;
    }

    if ( function_exists( 'wp_delete_file' ) ) {
        wp_delete_file( $real_file );
    } else {
        unlink( $real_file );
    }
    if ( file_exists( $real_file ) ) {
        return false;
    }

    return 1;
}

/**
 * Delete known generated files that sit directly in one legacy shared cache root.
 *
 * Prefix directories are the live per-site caches. This function does not enter them.
 *
 * @param string $engine Engine slug.
 * @param string $root   Legacy cache root.
 * @return int|false
 */
function chatbot_chatgpt_beta_purge_legacy_cache_root( $engine, $root ) {
    $root = rtrim( (string) $root, '/\\' );
    if ( $root === '' || ! file_exists( $root ) ) {
        return 0;
    }
    if ( is_link( $root ) || ! is_dir( $root ) ) {
        return false;
    }

    $real_root = realpath( $root );
    if ( false === $real_root || ! is_dir( $real_root ) ) {
        return false;
    }

    $entries = glob( $real_root . DIRECTORY_SEPARATOR . '*' );
    if ( ! is_array( $entries ) ) {
        return false;
    }

    $removed = 0;
    foreach ( $entries as $entry ) {
        if ( is_dir( $entry ) ) {
            continue;
        }

        $count = chatbot_chatgpt_beta_delete_legacy_cache_file( $engine, $entry, $real_root );
        if ( false === $count ) {
            return false;
        }
        $removed += $count;
    }

    return $removed;
}

/**
 * Delete known generated files left directly in the shared plugin cache roots.
 *
 * This is an explicit maintenance operation. It is not hooked to init.
 * It does not enter per-site directories, and it does not copy legacy contents.
 * A missing directory is success. A failed delete returns false.
 *
 * @return int|false
 */
function chatbot_chatgpt_beta_purge_legacy_shared_cache_files() {
    $removed = 0;
    foreach ( array( 'sentential', 'lexical' ) as $engine ) {
        $count = chatbot_chatgpt_beta_purge_legacy_cache_root( $engine, chatbot_chatgpt_beta_legacy_plugin_cache_root( $engine ) );
        if ( false === $count ) {
            return false;
        }
        $removed += $count;
    }

    return $removed;
}

/**
 * Clear disposable beta artifacts once for this security revision.
 *
 * @return void
 */
function chatbot_chatgpt_beta_knowledge_invalidate_once() {
    if ( ! function_exists( 'get_option' ) || ! function_exists( 'update_option' ) ) {
        return;
    }

    if ( get_option( 'chatbot_chatgpt_beta_knowledge_invalidated_eligibility_v1', '' ) === 'yes' ) {
        return;
    }

    foreach ( chatbot_chatgpt_beta_knowledge_engines() as $engine ) {
        if ( ! chatbot_chatgpt_beta_knowledge_invalidate_engine_once( $engine ) ) {
            return;
        }
    }

    update_option( 'chatbot_chatgpt_beta_knowledge_invalidated_eligibility_v1', 'yes', false );
}

/**
 * One engine's eligibility pass.
 *
 * A generation that still verifies is kept. An active build or a held publication
 * lock defers the pass. Schedules are not changed.
 *
 * @param string $engine Engine slug.
 * @return bool True when this engine is done. False when the caller should retry.
 */
function chatbot_chatgpt_beta_knowledge_invalidate_engine_once( $engine ) {
    if ( $engine === 'markov' || ! function_exists( 'chatbot_chatgpt_gfi_lock' ) ) {
        return chatbot_chatgpt_beta_engine_invalidate( $engine );
    }
    if ( function_exists( 'chatbot_chatgpt_gfi_build_is_active' ) && chatbot_chatgpt_gfi_build_is_active( $engine ) ) {
        return false;
    }
    $token = chatbot_chatgpt_gfi_token();
    if ( ! chatbot_chatgpt_gfi_lock( $engine, $token ) ) {
        return false;
    }
    try {
        if ( function_exists( 'chatbot_chatgpt_gfi_generation_is_trusted' ) && chatbot_chatgpt_gfi_generation_is_trusted( $engine, false ) ) {
            return true;
        }

        return chatbot_chatgpt_beta_engine_invalidate( $engine );
    } finally {
        chatbot_chatgpt_gfi_unlock( $engine, $token );
    }
}
add_action( 'init', 'chatbot_chatgpt_beta_knowledge_invalidate_once', 23 );
