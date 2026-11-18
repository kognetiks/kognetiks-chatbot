<?php
/**
 * Kognetiks Chatbot - Generated File Integrity (GFI) v1.
 *
 * HMAC-SHA-256 integrity for Sentential Context and Lexical Context caches.
 * This does not encrypt cache files and does not control HTTP access.
 *
 * @package chatbot-chatgpt
 */

if ( ! defined( 'WPINC' ) ) {
    die();
}

/**
 * Request-local key material and trust results. Nothing here is written to options.
 *
 * @return array<string, mixed>
 */
function &chatbot_chatgpt_gfi_runtime() {
    static $runtime = array(
        'keys'   => array(),
        'trust'  => array(),
        'failed' => array(),
        'code'   => '',
    );

    return $runtime;
}

/**
 * Engines whose file caches are covered by GFI v1.
 *
 * @return string[]
 */
function chatbot_chatgpt_gfi_engines() {
    return array( 'sentential', 'lexical' );
}

/**
 * @param string $engine Engine slug.
 * @return bool
 */
function chatbot_chatgpt_gfi_is_engine( $engine ) {
    return in_array( (string) $engine, chatbot_chatgpt_gfi_engines(), true );
}

/**
 * Active site id from WordPress. Request data is never consulted.
 *
 * @return int
 */
function chatbot_chatgpt_gfi_site_id() {
    if ( function_exists( 'get_current_blog_id' ) ) {
        $site_id = (int) get_current_blog_id();
        if ( $site_id > 0 ) {
            return $site_id;
        }
    }

    return 1;
}

/**
 * @param string $engine Engine slug.
 * @return string
 */
function chatbot_chatgpt_gfi_manifest_key( $engine ) {
    return 'chatbot_chatgpt_gfi_v1_' . $engine;
}

/**
 * @param string $engine Engine slug.
 * @return string
 */
function chatbot_chatgpt_gfi_recovery_key( $engine ) {
    return 'chatbot_chatgpt_gfi_recovery_' . $engine;
}

/**
 * @param string $engine Engine slug.
 * @return string
 */
function chatbot_chatgpt_gfi_lock_key( $engine ) {
    return 'chatbot_chatgpt_gfi_lock_' . $engine;
}

/**
 * Drop request-local trust and derived keys. Failure latch stays for this request.
 *
 * @return void
 */
function chatbot_chatgpt_gfi_clear_request_cache() {
    $runtime          = &chatbot_chatgpt_gfi_runtime();
    $runtime['keys']     = array();
    $runtime['trust']    = array();
    $runtime['reviewed'] = array();
    if ( empty( $runtime['in_fail'] ) ) {
        $runtime['failed'] = array();
    }
}

/**
 * Length-prefixed string. The length is the byte count.
 *
 * @param string $value Field value.
 * @return string
 */
function chatbot_chatgpt_gfi_lp( $value ) {
    $value = (string) $value;

    return pack( 'N', strlen( $value ) ) . $value;
}

/**
 * @param int $value Unsigned 32-bit value.
 * @return string
 */
function chatbot_chatgpt_gfi_u32( $value ) {
    return pack( 'N', (int) $value & 0xffffffff );
}

/**
 * @param int $value File size.
 * @return string
 */
function chatbot_chatgpt_gfi_u64( $value ) {
    $value = (int) $value;
    if ( $value < 0 ) {
        $value = 0;
    }
    $hi = (int) floor( $value / 4294967296 );
    $lo = (int) ( $value % 4294967296 );

    return pack( 'NN', $hi, $lo );
}

/**
 * Basename-only artifact name. Slashes and parent segments are rejected.
 *
 * @param string $name Candidate name.
 * @return bool
 */
function chatbot_chatgpt_gfi_name_is_safe( $name ) {
    return is_string( $name ) && preg_match( '/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $name ) === 1;
}

/**
 * @param string $value Candidate MAC.
 * @return bool
 */
function chatbot_chatgpt_gfi_mac_is_hex( $value ) {
    return is_string( $value ) && preg_match( '/^[0-9a-f]{64}$/', $value ) === 1;
}

/**
 * Fixed diagnostic codes. Callers cannot supply a path or a secret through this list.
 *
 * @param string $code Proposed code.
 * @return string
 */
function chatbot_chatgpt_gfi_code( $code ) {
    $allowed = array(
        'ok',
        'incomplete',
        'missing_file',
        'missing_signature',
        'bad_manifest',
        'bad_version',
        'bad_key_version',
        'wrong_site',
        'wrong_generation',
        'bad_path',
        'bad_size',
        'bad_name',
        'hmac_mismatch',
        'salt_rotation',
        'unexpected_file',
        'symlink',
        'read_error',
    );

    return in_array( (string) $code, $allowed, true ) ? (string) $code : 'bad_manifest';
}

/**
 * Derive the site-bound signing key. The result is kept in request memory only.
 *
 * @param int $site_id Site id.
 * @return string Raw key, or an empty string when derivation is unavailable.
 */
function chatbot_chatgpt_gfi_signing_key( $site_id ) {
    $site_id = (int) $site_id;
    $runtime = &chatbot_chatgpt_gfi_runtime();
    if ( isset( $runtime['keys'][ $site_id ] ) && is_string( $runtime['keys'][ $site_id ] ) ) {
        return $runtime['keys'][ $site_id ];
    }
    if ( ! function_exists( 'wp_salt' ) || ! function_exists( 'hash_hkdf' ) ) {
        return '';
    }

    $parts = array();
    foreach ( array( 'auth', 'secure_auth', 'logged_in', 'nonce' ) as $scheme ) {
        $part = wp_salt( $scheme );
        if ( ! is_string( $part ) || $part === '' ) {
            return '';
        }
        $parts[] = $part;
    }

    $info = "kognetiks-chatbot-gfi-v1\0site:" . $site_id . "\0purpose:artifact-integrity\0kdf:1";
    $key  = hash_hkdf( 'sha256', implode( "\0", $parts ), 32, $info, 'kognetiks-chatbot-gfi-v1' );
    if ( ! is_string( $key ) || strlen( $key ) !== 32 ) {
        return '';
    }
    $runtime['keys'][ $site_id ] = $key;

    return $key;
}

/**
 * Short identifier for the current key. This is not key material.
 *
 * @param string $key Raw signing key.
 * @return string
 */
function chatbot_chatgpt_gfi_key_id( $key ) {
    return substr( hash( 'sha256', (string) $key ), 0, 16 );
}

/**
 * Canonical file header. File bytes are hashed after this header.
 *
 * @param int    $site_id    Site id.
 * @param string $engine     Engine slug.
 * @param string $name       Logical artifact name.
 * @param string $path       Relative path.
 * @param string $generation Generation id.
 * @param int    $size       Byte length.
 * @return string
 */
function chatbot_chatgpt_gfi_file_header( $site_id, $engine, $name, $path, $generation, $size ) {
    return chatbot_chatgpt_gfi_lp( 'gfi-file-v1' )
        . chatbot_chatgpt_gfi_u32( (int) $site_id )
        . chatbot_chatgpt_gfi_lp( $engine )
        . chatbot_chatgpt_gfi_lp( $name )
        . chatbot_chatgpt_gfi_lp( $path )
        . chatbot_chatgpt_gfi_lp( $generation )
        . chatbot_chatgpt_gfi_u64( (int) $size )
        . chatbot_chatgpt_gfi_u32( 1 )
        . chatbot_chatgpt_gfi_u32( 1 );
}

/**
 * Stream a file handle into an HMAC. The handle is not rewound here.
 *
 * @param string   $key           Raw signing key.
 * @param string   $header        Canonical header.
 * @param resource $handle        Open binary handle.
 * @param int      $expected_size Byte count that must be consumed.
 * @return string Hex MAC, or an empty string on a short or long read.
 */
function chatbot_chatgpt_gfi_hmac_handle( $key, $header, $handle, $expected_size ) {
    $context = hash_init( 'sha256', HASH_HMAC, $key );
    if ( false === $context ) {
        return '';
    }
    hash_update( $context, $header );
    $left = (int) $expected_size;
    while ( $left > 0 ) {
        $chunk = fread( $handle, (int) min( 1048576, $left ) );
        if ( ! is_string( $chunk ) || $chunk === '' ) {
            return '';
        }
        hash_update( $context, $chunk );
        $left -= strlen( $chunk );
    }
    $extra = fread( $handle, 1 );
    if ( is_string( $extra ) && $extra !== '' ) {
        return '';
    }

    return hash_final( $context );
}

/**
 * MAC over the expected inventory and generation metadata.
 *
 * @param string               $key      Raw signing key.
 * @param array<string, mixed> $manifest Manifest without relying on its stored MAC.
 * @return string
 */
function chatbot_chatgpt_gfi_inventory_mac( $key, $manifest ) {
    $files = isset( $manifest['files'] ) && is_array( $manifest['files'] ) ? $manifest['files'] : array();
    ksort( $files );
    $context = hash_init( 'sha256', HASH_HMAC, $key );
    if ( false === $context ) {
        return '';
    }
    hash_update( $context, chatbot_chatgpt_gfi_lp( 'gfi-inventory-v1' ) );
    hash_update( $context, chatbot_chatgpt_gfi_u32( (int) $manifest['site_id'] ) );
    hash_update( $context, chatbot_chatgpt_gfi_lp( (string) $manifest['engine'] ) );
    hash_update( $context, chatbot_chatgpt_gfi_lp( (string) $manifest['generation_id'] ) );
    hash_update( $context, chatbot_chatgpt_gfi_lp( (string) $manifest['created_gmt'] ) );
    hash_update( $context, chatbot_chatgpt_gfi_u32( 1 ) );
    hash_update( $context, chatbot_chatgpt_gfi_u32( 1 ) );
    hash_update( $context, chatbot_chatgpt_gfi_u32( count( $files ) ) );
    foreach ( $files as $name => $row ) {
        if ( ! is_array( $row ) ) {
            return '';
        }
        hash_update( $context, chatbot_chatgpt_gfi_lp( (string) $name ) );
        hash_update( $context, chatbot_chatgpt_gfi_lp( isset( $row['path'] ) ? (string) $row['path'] : '' ) );
        hash_update( $context, chatbot_chatgpt_gfi_lp( isset( $row['generation_id'] ) ? (string) $row['generation_id'] : '' ) );
        hash_update( $context, chatbot_chatgpt_gfi_u64( isset( $row['size'] ) ? (int) $row['size'] : 0 ) );
        hash_update( $context, chatbot_chatgpt_gfi_lp( isset( $row['hmac'] ) ? (string) $row['hmac'] : '' ) );
    }

    return hash_final( $context );
}

/**
 * @param string $engine Engine slug.
 * @return array<string, mixed>|null
 */
function chatbot_chatgpt_gfi_manifest( $engine ) {
    if ( ! function_exists( 'get_option' ) ) {
        return null;
    }
    $manifest = get_option( chatbot_chatgpt_gfi_manifest_key( $engine ), null );

    return is_array( $manifest ) ? $manifest : null;
}

/**
 * @param string $engine Engine slug.
 * @return array<string, mixed>
 */
function chatbot_chatgpt_gfi_recovery( $engine ) {
    if ( ! function_exists( 'get_option' ) ) {
        return array();
    }
    $recovery = get_option( chatbot_chatgpt_gfi_recovery_key( $engine ), array() );

    return is_array( $recovery ) ? $recovery : array();
}

/**
 * @param string               $engine   Engine slug.
 * @param array<string, mixed> $recovery Recovery record.
 * @return void
 */
function chatbot_chatgpt_gfi_save_recovery( $engine, $recovery ) {
    if ( function_exists( 'update_option' ) ) {
        update_option( chatbot_chatgpt_gfi_recovery_key( $engine ), $recovery, false );
    }
}

/**
 * Diagnostic view. Paths, signatures, and key material are omitted.
 *
 * @param string $engine Engine slug.
 * @return array<string, int|string>
 */
function chatbot_chatgpt_gfi_status( $engine ) {
    $manifest = chatbot_chatgpt_gfi_manifest( $engine );
    $recovery = chatbot_chatgpt_gfi_recovery( $engine );
    $files    = ( is_array( $manifest ) && isset( $manifest['files'] ) && is_array( $manifest['files'] ) ) ? $manifest['files'] : array();

    return array(
        'engine'            => (string) $engine,
        'site_id'           => chatbot_chatgpt_gfi_site_id(),
        'readiness'         => is_array( $manifest ) && isset( $manifest['status'] ) ? (string) $manifest['status'] : 'missing',
        'generation_id'     => is_array( $manifest ) && isset( $manifest['generation_id'] ) ? (string) $manifest['generation_id'] : '',
        'file_count'        => count( $files ),
        'signature_version' => is_array( $manifest ) && isset( $manifest['signature_version'] ) ? (int) $manifest['signature_version'] : 0,
        'key_version'       => is_array( $manifest ) && isset( $manifest['key_version'] ) ? (int) $manifest['key_version'] : 0,
        'recovery'          => isset( $recovery['status'] ) ? (string) $recovery['status'] : '',
        'code'              => isset( $recovery['code'] ) ? chatbot_chatgpt_gfi_code( (string) $recovery['code'] ) : '',
        'attempts'          => isset( $recovery['attempts'] ) ? (int) $recovery['attempts'] : 0,
    );
}

/**
 * Last evaluation code for the current request. Empty after a clear.
 *
 * @return string
 */
function chatbot_chatgpt_gfi_last_code() {
    $runtime = &chatbot_chatgpt_gfi_runtime();

    return chatbot_chatgpt_gfi_code( (string) $runtime['code'] );
}

/**
 * @param string $code Evaluation code.
 * @param bool   $ok   Whether the generation is trusted.
 * @param bool   $hard Whether a ready engine should be invalidated.
 * @return array{ok: bool, hard: bool, code: string}
 */
function chatbot_chatgpt_gfi_result( $code, $ok, $hard ) {
    $runtime         = &chatbot_chatgpt_gfi_runtime();
    $runtime['code'] = chatbot_chatgpt_gfi_code( $code );

    return array(
        'ok'   => (bool) $ok,
        'hard' => (bool) $hard,
        'code' => $runtime['code'],
    );
}

/**
 * Files that may sit in the cache directory without being part of a generation.
 *
 * @return string[]
 */
function chatbot_chatgpt_gfi_guard_names() {
    return array( 'index.php', '.htaccess', 'web.config' );
}

/**
 * Collect signable files. A symlink or an unexpected directory fails the collection.
 *
 * @param string $directory Absolute cache directory.
 * @return array<string, string>|false
 */
function chatbot_chatgpt_gfi_collect_files( $directory ) {
    $directory = rtrim( (string) $directory, '/\\' );
    if ( $directory === '' || is_link( $directory ) || ! is_dir( $directory ) ) {
        return false;
    }
    $real = realpath( $directory );
    if ( false === $real || ! is_dir( $real ) ) {
        return false;
    }
    $entries = scandir( $real );
    if ( ! is_array( $entries ) ) {
        return false;
    }

    $files = array();
    foreach ( $entries as $entry ) {
        if ( $entry === '.' || $entry === '..' ) {
            continue;
        }
        $path = $real . DIRECTORY_SEPARATOR . $entry;
        if ( is_link( $path ) ) {
            return false;
        }
        if ( is_dir( $path ) ) {
            return false;
        }
        if ( ! is_file( $path ) ) {
            return false;
        }
        if ( in_array( strtolower( $entry ), chatbot_chatgpt_gfi_guard_names(), true ) ) {
            continue;
        }
        if ( ! chatbot_chatgpt_gfi_name_is_safe( $entry ) ) {
            return false;
        }
        $files[ $entry ] = $path;
    }
    ksort( $files );

    return $files;
}

/**
 * Resolve one inventory path and refuse anything outside the cache directory.
 *
 * @param string $directory Absolute cache directory.
 * @param string $relative  Relative artifact name.
 * @return string
 */
function chatbot_chatgpt_gfi_resolve( $directory, $relative ) {
    if ( ! chatbot_chatgpt_gfi_name_is_safe( $relative ) ) {
        return '';
    }
    $directory = rtrim( (string) $directory, '/\\' );
    $candidate = $directory . DIRECTORY_SEPARATOR . $relative;
    if ( is_link( $candidate ) || ! is_file( $candidate ) ) {
        return '';
    }
    $real_dir  = realpath( $directory );
    $real_file = realpath( $candidate );
    if ( false === $real_dir || false === $real_file || ! is_file( $real_file ) || is_link( $real_file ) ) {
        return '';
    }
    $prefix = strtolower( str_replace( '\\', '/', $real_dir ) ) . '/';
    $file   = strtolower( str_replace( '\\', '/', $real_file ) );
    if ( strpos( $file, $prefix ) !== 0 ) {
        return '';
    }

    return $real_file;
}

/**
 * @param string $engine Engine slug.
 * @param string $path   Candidate file path.
 * @return string
 */
function chatbot_chatgpt_gfi_relative_from_path( $engine, $path ) {
    $name = basename( str_replace( '\\', '/', (string) $path ) );
    if ( ! chatbot_chatgpt_gfi_name_is_safe( $name ) || ! function_exists( 'chatbot_chatgpt_beta_engine_cache_directory' ) ) {
        return '';
    }
    $directory = chatbot_chatgpt_beta_engine_cache_directory( $engine );
    if ( chatbot_chatgpt_gfi_resolve( $directory, $name ) === '' ) {
        return '';
    }

    return $name;
}

/**
 * Separate record for an in-progress build. A ready manifest is left in place.
 *
 * @param string $engine Engine slug.
 * @return string
 */
function chatbot_chatgpt_gfi_build_key( $engine ) {
    return 'chatbot_chatgpt_gfi_build_v1_' . $engine;
}

/**
 * How long a build may go without a heartbeat before it is abandoned.
 *
 * Chunked rebuilds call begin() on every chunk, which refreshes the heartbeat.
 * Thirty minutes is longer than the lexical chunk lock and shorter than an abandoned job.
 *
 * @return int
 */
function chatbot_chatgpt_gfi_build_stale_seconds() {
    return 1800;
}

/**
 * @param string $engine Engine slug.
 * @return array<string, mixed>|null
 */
function chatbot_chatgpt_gfi_build_record( $engine ) {
    if ( ! function_exists( 'get_option' ) ) {
        return null;
    }
    $record = get_option( chatbot_chatgpt_gfi_build_key( $engine ), null );

    return is_array( $record ) ? $record : null;
}

/**
 * @param string $engine Engine slug.
 * @return void
 */
function chatbot_chatgpt_gfi_clear_build( $engine ) {
    if ( function_exists( 'delete_option' ) ) {
        delete_option( chatbot_chatgpt_gfi_build_key( $engine ) );
    }
}

/**
 * Start a build without replacing a manifest that still describes a sealed generation.
 *
 * @param string $engine     Engine slug.
 * @param int    $generation Build generation.
 * @return void
 */
function chatbot_chatgpt_gfi_begin( $engine, $generation ) {
    if ( ! chatbot_chatgpt_gfi_is_engine( $engine ) || ! function_exists( 'update_option' ) ) {
        return;
    }
    $now = time();
    update_option(
        chatbot_chatgpt_gfi_build_key( $engine ),
        array(
            'status'         => 'building',
            'site_id'        => chatbot_chatgpt_gfi_site_id(),
            'engine'         => (string) $engine,
            'generation_id'  => (string) (int) $generation,
            'owner'          => chatbot_chatgpt_gfi_token(),
            'started_gmt'    => $now,
            'heartbeat_gmt'  => $now,
        ),
        false
    );
}

/**
 * Show that the current build is still running.
 *
 * @param string $engine Engine slug.
 * @return void
 */
function chatbot_chatgpt_gfi_touch( $engine ) {
    if ( ! chatbot_chatgpt_gfi_is_engine( $engine ) || ! function_exists( 'update_option' ) || ! function_exists( 'chatbot_chatgpt_beta_engine_generation' ) ) {
        return;
    }
    $record = chatbot_chatgpt_gfi_build_record( $engine );
    if ( ! is_array( $record ) || ( $record['status'] ?? '' ) !== 'building' ) {
        return;
    }
    if ( (string) ( $record['generation_id'] ?? '' ) !== (string) (int) chatbot_chatgpt_beta_engine_generation( $engine ) ) {
        return;
    }
    $record['heartbeat_gmt'] = time();
    update_option( chatbot_chatgpt_gfi_build_key( $engine ), $record, false );
}

/**
 * @param string $engine Engine slug.
 * @return bool
 */
function chatbot_chatgpt_gfi_build_is_active( $engine ) {
    $record = chatbot_chatgpt_gfi_build_record( $engine );
    if ( ! is_array( $record ) || ( $record['status'] ?? '' ) !== 'building' ) {
        return false;
    }
    $heartbeat = isset( $record['heartbeat_gmt'] ) ? (int) $record['heartbeat_gmt'] : 0;
    if ( $heartbeat >= time() - chatbot_chatgpt_gfi_build_stale_seconds() ) {
        return true;
    }
    $lock = function_exists( 'get_option' ) ? get_option( chatbot_chatgpt_gfi_lock_key( $engine ), '' ) : '';
    if ( is_string( $lock ) && $lock !== '' ) {
        $parts = explode( '|', $lock, 2 );
        $stamp = isset( $parts[1] ) ? (int) $parts[1] : 0;
        if ( $stamp > time() - 900 ) {
            return true;
        }
    }

    return false;
}

/**
 * Drop an abandoned build. A sealed generation that still verifies is kept.
 *
 * A visitor request only schedules recovery. It does not rebuild.
 *
 * @param string $engine Engine slug.
 * @return void
 */
function chatbot_chatgpt_gfi_review_abandoned_build( $engine ) {
    if ( ! chatbot_chatgpt_gfi_is_engine( $engine ) ) {
        return;
    }
    $runtime = &chatbot_chatgpt_gfi_runtime();
    $latch   = chatbot_chatgpt_gfi_site_id() . '|' . $engine;
    if ( ! empty( $runtime['reviewing'] ) || ! empty( $runtime['reviewed'][ $latch ] ) ) {
        return;
    }
    $runtime['reviewed'][ $latch ] = true;
    $record = chatbot_chatgpt_gfi_build_record( $engine );
    if ( ! is_array( $record ) || ( $record['status'] ?? '' ) !== 'building' ) {
        return;
    }
    if ( chatbot_chatgpt_gfi_build_is_active( $engine ) ) {
        return;
    }

    $runtime['reviewing'] = true;
    $trusted = chatbot_chatgpt_gfi_evaluate( $engine );
    $runtime['reviewing'] = false;
    if ( ! empty( $trusted['ok'] ) ) {
        chatbot_chatgpt_gfi_clear_build( $engine );
        if ( function_exists( 'chatbot_chatgpt_beta_engine_state' ) && chatbot_chatgpt_beta_engine_state( $engine ) === 'building' && function_exists( 'update_option' ) ) {
            update_option( chatbot_chatgpt_beta_engine_state_key( $engine ), 'ready', false );
        }
        return;
    }

    chatbot_chatgpt_gfi_fail( $engine, 'incomplete' );
}

/**
 * Remove the manifest after invalidation. Recovery bookkeeping is left intact.
 *
 * @param string $engine Engine slug.
 * @return void
 */
function chatbot_chatgpt_gfi_discard( $engine ) {
    if ( function_exists( 'delete_option' ) ) {
        delete_option( chatbot_chatgpt_gfi_manifest_key( $engine ) );
    }
    chatbot_chatgpt_gfi_clear_build( $engine );
    chatbot_chatgpt_gfi_clear_request_cache();
}

/**
 * @param string $engine Engine slug.
 * @param string $token  Lock token.
 * @return bool
 */
function chatbot_chatgpt_gfi_lock( $engine, $token ) {
    if ( ! function_exists( 'get_option' ) || ! function_exists( 'update_option' ) ) {
        return false;
    }
    $key     = chatbot_chatgpt_gfi_lock_key( $engine );
    $current = get_option( $key, '' );
    if ( is_string( $current ) && $current !== '' ) {
        $parts = explode( '|', $current, 2 );
        $stamp = isset( $parts[1] ) ? (int) $parts[1] : 0;
        if ( $stamp > time() - 900 ) {
            return false;
        }
    }
    update_option( $key, $token . '|' . time(), false );
    $stored = get_option( $key, '' );

    return is_string( $stored ) && strpos( $stored, $token . '|' ) === 0;
}

/**
 * @param string $engine Engine slug.
 * @param string $token  Lock token.
 * @return void
 */
function chatbot_chatgpt_gfi_unlock( $engine, $token ) {
    if ( ! function_exists( 'get_option' ) || ! function_exists( 'delete_option' ) ) {
        return;
    }
    $stored = get_option( chatbot_chatgpt_gfi_lock_key( $engine ), '' );
    if ( is_string( $stored ) && strpos( $stored, $token . '|' ) === 0 ) {
        delete_option( chatbot_chatgpt_gfi_lock_key( $engine ) );
    }
}

/**
 * @return string
 */
function chatbot_chatgpt_gfi_token() {
    if ( function_exists( 'random_bytes' ) ) {
        try {
            return bin2hex( random_bytes( 16 ) );
        } catch ( Exception $exception ) {
            unset( $exception );
        }
    }

    return bin2hex( hash( 'sha256', uniqid( '', true ), true ) );
}

/**
 * Sign the files currently in the site directory and store the manifest.
 *
 * @param string $engine Engine slug.
 * @return bool
 */
function chatbot_chatgpt_gfi_publish( $engine ) {
    if ( ! chatbot_chatgpt_gfi_is_engine( $engine ) || ! function_exists( 'chatbot_chatgpt_beta_engine_cache_directory' ) ) {
        return false;
    }
    chatbot_chatgpt_gfi_touch( $engine );
    $token = chatbot_chatgpt_gfi_token();
    if ( ! chatbot_chatgpt_gfi_lock( $engine, $token ) ) {
        return false;
    }
    try {
        return chatbot_chatgpt_gfi_publish_locked( $engine );
    } finally {
        chatbot_chatgpt_gfi_unlock( $engine, $token );
    }
}

/**
 * @param string $engine Engine slug.
 * @return bool
 */
function chatbot_chatgpt_gfi_publish_locked( $engine ) {
    $directory = chatbot_chatgpt_beta_engine_cache_directory( $engine );
    $files     = chatbot_chatgpt_gfi_collect_files( $directory );
    if ( ! is_array( $files ) || ! isset( $files['eligibility-revision.txt'] ) ) {
        return false;
    }
    $site_id = chatbot_chatgpt_gfi_site_id();
    $key     = chatbot_chatgpt_gfi_signing_key( $site_id );
    if ( $key === '' || ! function_exists( 'chatbot_chatgpt_beta_engine_generation' ) ) {
        return false;
    }
    $generation = (string) (int) chatbot_chatgpt_beta_engine_generation( $engine );
    $records    = array();
    foreach ( $files as $name => $path ) {
        $handle = fopen( $path, 'rb' );
        if ( false === $handle ) {
            return false;
        }
        $size = false;
        if ( flock( $handle, LOCK_SH ) ) {
            clearstatcache( true, $path );
            $stat = fstat( $handle );
            $size = is_array( $stat ) && isset( $stat['size'] ) ? (int) $stat['size'] : false;
        }
        $hmac = '';
        if ( false !== $size ) {
            $header = chatbot_chatgpt_gfi_file_header( $site_id, $engine, $name, $name, $generation, $size );
            $hmac   = chatbot_chatgpt_gfi_hmac_handle( $key, $header, $handle, $size );
        }
        flock( $handle, LOCK_UN );
        fclose( $handle );
        if ( ! chatbot_chatgpt_gfi_mac_is_hex( $hmac ) ) {
            return false;
        }
        $records[ $name ] = array(
            'site_id'            => $site_id,
            'name'               => $name,
            'path'               => $name,
            'generation_id'      => $generation,
            'size'               => $size,
            'hmac'               => $hmac,
            'signature_version'  => 1,
            'key_version'        => 1,
            'status'             => 'signed',
        );
    }

    $created  = gmdate( 'Y-m-d H:i:s' );
    $manifest = array(
        'gfi_version'       => 1,
        'site_id'           => $site_id,
        'engine'            => (string) $engine,
        'generation_id'     => $generation,
        'created_gmt'       => $created,
        'status'            => 'ready',
        'signature_version' => 1,
        'key_version'       => 1,
        'key_id'            => chatbot_chatgpt_gfi_key_id( $key ),
        'inventory'         => array_keys( $records ),
        'files'             => $records,
        'inventory_mac'     => '',
        'last_verified_gmt' => $created,
    );
    $mac = chatbot_chatgpt_gfi_inventory_mac( $key, $manifest );
    if ( ! chatbot_chatgpt_gfi_mac_is_hex( $mac ) ) {
        return false;
    }
    $manifest['inventory_mac'] = $mac;
    update_option( chatbot_chatgpt_gfi_manifest_key( $engine ), $manifest, false );
    if ( function_exists( 'delete_option' ) ) {
        delete_option( chatbot_chatgpt_gfi_recovery_key( $engine ) );
    }
    chatbot_chatgpt_gfi_clear_build( $engine );
    chatbot_chatgpt_gfi_clear_request_cache();

    return chatbot_chatgpt_gfi_generation_is_trusted( $engine, false );
}

/**
 * Decide whether the stored generation matches the current site, key, and files.
 *
 * @param string $engine Engine slug.
 * @return array{ok: bool, hard: bool, code: string}
 */
function chatbot_chatgpt_gfi_evaluate( $engine ) {
    if ( ! chatbot_chatgpt_gfi_is_engine( $engine ) ) {
        return chatbot_chatgpt_gfi_result( 'bad_manifest', false, false );
    }
    $state = function_exists( 'chatbot_chatgpt_beta_engine_state' ) ? chatbot_chatgpt_beta_engine_state( $engine ) : '';
    $manifest = chatbot_chatgpt_gfi_manifest( $engine );
    if ( ! is_array( $manifest ) ) {
        $hard = ( $state === 'ready' );
        return chatbot_chatgpt_gfi_result( 'missing_signature', false, $hard );
    }
    if ( ! isset( $manifest['status'] ) || $manifest['status'] !== 'ready' ) {
        return chatbot_chatgpt_gfi_result( 'incomplete', false, false );
    }
    if ( $state === 'invalid' ) {
        return chatbot_chatgpt_gfi_result( 'incomplete', false, false );
    }
    if ( (int) ( $manifest['gfi_version'] ?? 0 ) !== 1 || (int) ( $manifest['signature_version'] ?? 0 ) !== 1 ) {
        return chatbot_chatgpt_gfi_result( 'bad_version', false, true );
    }
    if ( (int) ( $manifest['key_version'] ?? 0 ) !== 1 ) {
        return chatbot_chatgpt_gfi_result( 'bad_key_version', false, true );
    }
    if ( (int) ( $manifest['site_id'] ?? 0 ) !== chatbot_chatgpt_gfi_site_id() || (string) ( $manifest['engine'] ?? '' ) !== (string) $engine ) {
        return chatbot_chatgpt_gfi_result( 'wrong_site', false, true );
    }
    $generation = function_exists( 'chatbot_chatgpt_beta_engine_generation' ) ? (string) (int) chatbot_chatgpt_beta_engine_generation( $engine ) : '';
    if ( $generation === '' || (string) ( $manifest['generation_id'] ?? '' ) !== $generation ) {
        return chatbot_chatgpt_gfi_result( 'wrong_generation', false, true );
    }
    if ( ! isset( $manifest['files'] ) || ! is_array( $manifest['files'] ) || ! isset( $manifest['inventory'] ) || ! is_array( $manifest['inventory'] ) ) {
        return chatbot_chatgpt_gfi_result( 'bad_manifest', false, true );
    }
    $key = chatbot_chatgpt_gfi_signing_key( chatbot_chatgpt_gfi_site_id() );
    if ( $key === '' ) {
        return chatbot_chatgpt_gfi_result( 'read_error', false, true );
    }
    $key_id = isset( $manifest['key_id'] ) ? (string) $manifest['key_id'] : '';
    $current_id = chatbot_chatgpt_gfi_key_id( $key );
    if ( strlen( $key_id ) !== strlen( $current_id ) || ! hash_equals( $current_id, $key_id ) ) {
        return chatbot_chatgpt_gfi_result( 'salt_rotation', false, true );
    }
    $stored_mac = isset( $manifest['inventory_mac'] ) ? (string) $manifest['inventory_mac'] : '';
    $actual_mac = chatbot_chatgpt_gfi_inventory_mac( $key, $manifest );
    if ( ! chatbot_chatgpt_gfi_mac_is_hex( $stored_mac ) || ! chatbot_chatgpt_gfi_mac_is_hex( $actual_mac ) || ! hash_equals( $actual_mac, $stored_mac ) ) {
        return chatbot_chatgpt_gfi_result( 'bad_manifest', false, true );
    }

    $names = array();
    foreach ( $manifest['files'] as $name => $row ) {
        if ( ! is_string( $name ) || ! is_array( $row ) ) {
            return chatbot_chatgpt_gfi_result( 'bad_manifest', false, true );
        }
        if ( ! chatbot_chatgpt_gfi_name_is_safe( $name ) || (string) ( $row['path'] ?? '' ) !== $name || (string) ( $row['name'] ?? '' ) !== $name ) {
            return chatbot_chatgpt_gfi_result( 'bad_path', false, true );
        }
        if ( (int) ( $row['site_id'] ?? 0 ) !== chatbot_chatgpt_gfi_site_id() ) {
            return chatbot_chatgpt_gfi_result( 'wrong_site', false, true );
        }
        if ( (string) ( $row['generation_id'] ?? '' ) !== $generation ) {
            return chatbot_chatgpt_gfi_result( 'wrong_generation', false, true );
        }
        if ( (int) ( $row['signature_version'] ?? 0 ) !== 1 ) {
            return chatbot_chatgpt_gfi_result( 'bad_version', false, true );
        }
        if ( (int) ( $row['key_version'] ?? 0 ) !== 1 ) {
            return chatbot_chatgpt_gfi_result( 'bad_key_version', false, true );
        }
        if ( ! chatbot_chatgpt_gfi_mac_is_hex( (string) ( $row['hmac'] ?? '' ) ) || ! isset( $row['size'] ) || (int) $row['size'] < 0 ) {
            return chatbot_chatgpt_gfi_result( 'bad_manifest', false, true );
        }
        $names[] = $name;
    }
    $expected = $manifest['inventory'];
    $sorted_names = $names;
    $sorted_expected = $expected;
    sort( $sorted_names );
    sort( $sorted_expected );
    if ( $sorted_names !== $sorted_expected || ! in_array( 'eligibility-revision.txt', $sorted_names, true ) ) {
        return chatbot_chatgpt_gfi_result( 'incomplete', false, true );
    }

    if ( ! function_exists( 'chatbot_chatgpt_beta_engine_cache_directory' ) ) {
        return chatbot_chatgpt_gfi_result( 'read_error', false, true );
    }
    $directory = chatbot_chatgpt_beta_engine_cache_directory( $engine );
    $present   = chatbot_chatgpt_gfi_collect_files( $directory );
    if ( ! is_array( $present ) ) {
        return chatbot_chatgpt_gfi_result( is_dir( $directory ) ? 'symlink' : 'read_error', false, true );
    }
    $present_names = array_keys( $present );
    sort( $present_names );
    if ( $present_names !== $sorted_names ) {
        $code = array_diff( $present_names, $sorted_names ) ? 'unexpected_file' : 'missing_file';
        return chatbot_chatgpt_gfi_result( $code, false, true );
    }

    foreach ( $manifest['files'] as $name => $row ) {
        $path = chatbot_chatgpt_gfi_resolve( $directory, $name );
        if ( $path === '' ) {
            return chatbot_chatgpt_gfi_result( 'missing_file', false, true );
        }
        $verified = chatbot_chatgpt_gfi_verify_open( $engine, $path, $row, $key, false );
        if ( ! is_array( $verified ) ) {
            return chatbot_chatgpt_gfi_result( chatbot_chatgpt_gfi_last_code(), false, true );
        }
        flock( $verified['handle'], LOCK_UN );
        fclose( $verified['handle'] );
    }

    return chatbot_chatgpt_gfi_result( 'ok', true, false );
}

/**
 * Open a file, lock it, and accept it only when the HMAC matches the manifest record.
 *
 * @param string               $engine   Engine slug.
 * @param string               $path     Absolute path.
 * @param array<string, mixed> $row      Manifest record.
 * @param string               $key      Raw signing key.
 * @param bool                 $keep_open Whether the caller still needs the handle.
 * @return array{handle: resource, size: int}|false
 */
function chatbot_chatgpt_gfi_verify_open( $engine, $path, $row, $key, $keep_open ) {
    unset( $keep_open );
    $handle = fopen( $path, 'rb' );
    if ( false === $handle ) {
        chatbot_chatgpt_gfi_result( 'read_error', false, true );
        return false;
    }
    if ( ! flock( $handle, LOCK_SH ) ) {
        fclose( $handle );
        chatbot_chatgpt_gfi_result( 'read_error', false, true );
        return false;
    }
    clearstatcache( true, $path );
    $stat = fstat( $handle );
    $size = is_array( $stat ) && isset( $stat['size'] ) ? (int) $stat['size'] : -1;
    if ( $size !== (int) $row['size'] ) {
        flock( $handle, LOCK_UN );
        fclose( $handle );
        chatbot_chatgpt_gfi_result( 'bad_size', false, true );
        return false;
    }
    $header = chatbot_chatgpt_gfi_file_header(
        (int) $row['site_id'],
        $engine,
        (string) $row['name'],
        (string) $row['path'],
        (string) $row['generation_id'],
        $size
    );
    $hmac = chatbot_chatgpt_gfi_hmac_handle( $key, $header, $handle, $size );
    $stored = (string) $row['hmac'];
    if ( ! chatbot_chatgpt_gfi_mac_is_hex( $hmac ) || strlen( $hmac ) !== strlen( $stored ) || ! hash_equals( $stored, $hmac ) ) {
        flock( $handle, LOCK_UN );
        fclose( $handle );
        chatbot_chatgpt_gfi_result( 'hmac_mismatch', false, true );
        return false;
    }
    if ( ! rewind( $handle ) ) {
        flock( $handle, LOCK_UN );
        fclose( $handle );
        chatbot_chatgpt_gfi_result( 'read_error', false, true );
        return false;
    }

    return array(
        'handle' => $handle,
        'size'   => $size,
    );
}

/**
 * @param string $site_key Site and engine cache key.
 * @return array{ok: bool, hard: bool, code: string}|null
 */
function chatbot_chatgpt_gfi_cache_get( $site_key ) {
    $runtime = &chatbot_chatgpt_gfi_runtime();
    if ( ! isset( $runtime['trust'][ $site_key ] ) || ! is_array( $runtime['trust'][ $site_key ] ) ) {
        return null;
    }

    return $runtime['trust'][ $site_key ];
}

/**
 * @param string                               $site_key Cache key.
 * @param array{ok: bool, hard: bool, code: string} $result Evaluation.
 * @return void
 */
function chatbot_chatgpt_gfi_cache_set( $site_key, $result ) {
    $runtime                       = &chatbot_chatgpt_gfi_runtime();
    $runtime['trust'][ $site_key ] = $result;
}

/**
 * Whether the current generation may be used. A hard failure can invalidate it.
 *
 * @param string $engine Engine slug.
 * @param bool   $report Whether to record and schedule recovery.
 * @return bool
 */
function chatbot_chatgpt_gfi_generation_is_trusted( $engine, $report = false ) {
    $runtime = &chatbot_chatgpt_gfi_runtime();
    if ( empty( $runtime['reviewing'] ) ) {
        chatbot_chatgpt_gfi_review_abandoned_build( $engine );
    }
    $site_key = chatbot_chatgpt_gfi_site_id() . '|' . $engine;
    $cached   = chatbot_chatgpt_gfi_cache_get( $site_key );
    if ( ! is_array( $cached ) ) {
        $cached = chatbot_chatgpt_gfi_evaluate( $engine );
        chatbot_chatgpt_gfi_cache_set( $site_key, $cached );
    } else {
        $runtime         = &chatbot_chatgpt_gfi_runtime();
        $runtime['code'] = $cached['code'];
    }
    if ( ! $cached['ok'] && $report && ! empty( $cached['hard'] ) ) {
        chatbot_chatgpt_gfi_fail( $engine, $cached['code'] );
    }

    return (bool) $cached['ok'];
}

/**
 * A production file is trusted only as a member of the current sealed generation.
 *
 * @param string $engine Engine slug.
 * @param string $path   Absolute or relative cache path.
 * @return bool
 */
function chatbot_chatgpt_gfi_file_is_trusted( $engine, $path ) {
    if ( ! chatbot_chatgpt_gfi_generation_is_trusted( $engine, false ) ) {
        $cached = chatbot_chatgpt_gfi_cache_get( chatbot_chatgpt_gfi_site_id() . '|' . $engine );
        if ( is_array( $cached ) && ! empty( $cached['hard'] ) ) {
            chatbot_chatgpt_gfi_generation_is_trusted( $engine, true );
        }
        return false;
    }
    $name     = chatbot_chatgpt_gfi_relative_from_path( $engine, $path );
    $manifest = chatbot_chatgpt_gfi_manifest( $engine );

    return $name !== '' && is_array( $manifest ) && isset( $manifest['files'][ $name ] );
}

/**
 * Read a published file once. The returned string is the exact HMAC input.
 *
 * @param string $engine Engine slug.
 * @param string $path   File path inside the site cache.
 * @return string|false
 */
function chatbot_chatgpt_gfi_verified_bytes( $engine, $path ) {
    if ( ! chatbot_chatgpt_gfi_generation_is_trusted( $engine, true ) ) {
        return false;
    }
    $name     = chatbot_chatgpt_gfi_relative_from_path( $engine, $path );
    $manifest = chatbot_chatgpt_gfi_manifest( $engine );
    if ( $name === '' || ! is_array( $manifest ) || ! isset( $manifest['files'][ $name ] ) || ! is_array( $manifest['files'][ $name ] ) ) {
        chatbot_chatgpt_gfi_fail( $engine, 'missing_signature' );
        return false;
    }
    $row = $manifest['files'][ $name ];
    $key = chatbot_chatgpt_gfi_signing_key( chatbot_chatgpt_gfi_site_id() );
    if ( $key === '' || ! function_exists( 'chatbot_chatgpt_beta_engine_cache_directory' ) ) {
        chatbot_chatgpt_gfi_fail( $engine, 'read_error' );
        return false;
    }
    $resolved = chatbot_chatgpt_gfi_resolve( chatbot_chatgpt_beta_engine_cache_directory( $engine ), $name );
    if ( $resolved === '' ) {
        chatbot_chatgpt_gfi_fail( $engine, 'bad_path' );
        return false;
    }
    $handle = fopen( $resolved, 'rb' );
    if ( false === $handle ) {
        chatbot_chatgpt_gfi_fail( $engine, 'read_error' );
        return false;
    }
    $size = -1;
    if ( flock( $handle, LOCK_SH ) ) {
        clearstatcache( true, $resolved );
        $stat = fstat( $handle );
        $size = is_array( $stat ) && isset( $stat['size'] ) ? (int) $stat['size'] : -1;
    }
    if ( $size !== (int) $row['size'] ) {
        flock( $handle, LOCK_UN );
        fclose( $handle );
        chatbot_chatgpt_gfi_fail( $engine, 'bad_size' );
        return false;
    }
    $context = hash_init( 'sha256', HASH_HMAC, $key );
    if ( false === $context ) {
        flock( $handle, LOCK_UN );
        fclose( $handle );
        chatbot_chatgpt_gfi_fail( $engine, 'read_error' );
        return false;
    }
    hash_update(
        $context,
        chatbot_chatgpt_gfi_file_header(
            (int) $row['site_id'],
            $engine,
            (string) $row['name'],
            (string) $row['path'],
            (string) $row['generation_id'],
            $size
        )
    );
    $buffer = '';
    $left   = $size;
    while ( $left > 0 ) {
        $chunk = fread( $handle, (int) min( 1048576, $left ) );
        if ( ! is_string( $chunk ) || $chunk === '' ) {
            flock( $handle, LOCK_UN );
            fclose( $handle );
            chatbot_chatgpt_gfi_fail( $engine, 'read_error' );
            return false;
        }
        hash_update( $context, $chunk );
        $buffer .= $chunk;
        $left   -= strlen( $chunk );
    }
    $extra = fread( $handle, 1 );
    flock( $handle, LOCK_UN );
    fclose( $handle );
    if ( is_string( $extra ) && $extra !== '' ) {
        chatbot_chatgpt_gfi_fail( $engine, 'bad_size' );
        return false;
    }
    $hmac   = hash_final( $context );
    $stored = (string) $row['hmac'];
    if ( ! chatbot_chatgpt_gfi_mac_is_hex( $hmac ) || strlen( $hmac ) !== strlen( $stored ) || ! hash_equals( $stored, $hmac ) ) {
        chatbot_chatgpt_gfi_fail( $engine, 'hmac_mismatch' );
        return false;
    }

    return $buffer;
}

/**
 * @param string $engine Engine slug.
 * @param string $path   File path inside the site cache.
 * @return string|false
 */
function chatbot_chatgpt_gfi_trusted_contents( $engine, $path ) {
    return chatbot_chatgpt_gfi_verified_bytes( $engine, $path );
}

/**
 * Execute a published PHP cache from the authenticated byte string.
 *
 * There is no second read and no temporary PHP file.
 *
 * @param string $engine Engine slug.
 * @param string $path   Published PHP cache path.
 * @return mixed|null Null when verification or execution is refused.
 */
function chatbot_chatgpt_gfi_include_trusted( $engine, $path ) {
    $bytes = chatbot_chatgpt_gfi_verified_bytes( $engine, $path );
    if ( ! is_string( $bytes ) ) {
        return null;
    }

    return chatbot_chatgpt_gfi_execute_buffer( $bytes );
}

/**
 * Read one in-progress cache file and execute it only when it is a literal array.
 *
 * The same string is authenticated by the token check and executed. A replacement
 * that contains calls, variables, or includes is refused.
 *
 * @param string $path Cache file written by the current builder.
 * @return mixed|null
 */
function chatbot_chatgpt_gfi_include_builder_literal( $path ) {
    if ( is_link( $path ) || ! is_file( $path ) ) {
        return null;
    }
    $handle = fopen( $path, 'rb' );
    if ( false === $handle ) {
        return null;
    }
    $bytes = '';
    if ( flock( $handle, LOCK_SH ) ) {
        while ( ! feof( $handle ) ) {
            $chunk = fread( $handle, 1048576 );
            if ( ! is_string( $chunk ) || $chunk === '' ) {
                break;
            }
            $bytes .= $chunk;
            if ( strlen( $bytes ) > 67108864 ) {
                flock( $handle, LOCK_UN );
                fclose( $handle );
                return null;
            }
        }
    }
    flock( $handle, LOCK_UN );
    fclose( $handle );
    if ( ! chatbot_chatgpt_gfi_buffer_is_literal_return( $bytes ) ) {
        return null;
    }

    return chatbot_chatgpt_gfi_execute_buffer( $bytes );
}

/**
 * A generated cache may execute only as `<?php return <literal array>;`.
 *
 * TOKEN_PARSE is required so the token stream matches the PHP parser.
 * Comments are ignored. Any other statement, call, variable, or operator is refused.
 *
 * @param string $bytes File bytes from a single read.
 * @return bool
 */
function chatbot_chatgpt_gfi_buffer_is_literal_return( $bytes ) {
    if ( ! is_string( $bytes ) || strlen( $bytes ) < 12 || strlen( $bytes ) > 67108864 ) {
        return false;
    }
    if ( strncmp( $bytes, '<?php', 5 ) !== 0 || strpos( $bytes, "\0" ) !== false ) {
        return false;
    }
    if ( ! defined( 'TOKEN_PARSE' ) || ! function_exists( 'token_get_all' ) ) {
        return false;
    }
    try {
        $raw = token_get_all( $bytes, TOKEN_PARSE );
    } catch ( Throwable $exception ) {
        unset( $exception );
        return false;
    }
    $tokens = array();
    foreach ( $raw as $token ) {
        if ( is_array( $token ) && ( $token[0] === T_WHITESPACE || ( defined( 'T_COMMENT' ) && $token[0] === T_COMMENT ) || ( defined( 'T_DOC_COMMENT' ) && $token[0] === T_DOC_COMMENT ) ) ) {
            continue;
        }
        $tokens[] = $token;
    }
    $index = 0;
    $count = count( $tokens );
    if ( $count < 4 || ! is_array( $tokens[0] ) || $tokens[0][0] !== T_OPEN_TAG || strncmp( (string) $tokens[0][1], '<?php', 5 ) !== 0 ) {
        return false;
    }
    $index = 1;
    if ( ! isset( $tokens[ $index ] ) || ! is_array( $tokens[ $index ] ) || $tokens[ $index ][0] !== T_RETURN ) {
        return false;
    }
    $index++;
    if ( ! chatbot_chatgpt_gfi_parse_literal_array( $tokens, $index ) ) {
        return false;
    }
    if ( ! isset( $tokens[ $index ] ) || ';' !== $tokens[ $index ] ) {
        return false;
    }
    $index++;

    return $index === $count;
}

/**
 * @param array<int, mixed> $tokens Tokens with whitespace removed.
 * @param int               $index  Cursor.
 * @return bool
 */
function chatbot_chatgpt_gfi_parse_literal_array( $tokens, &$index ) {
    if ( ! isset( $tokens[ $index ] ) ) {
        return false;
    }
    $token = $tokens[ $index ];
    if ( is_array( $token ) && $token[0] === T_ARRAY ) {
        $index++;
        if ( ! isset( $tokens[ $index ] ) || '(' !== $tokens[ $index ] ) {
            return false;
        }
        $index++;
        $close = ')';
    } elseif ( '[' === $token ) {
        $index++;
        $close = ']';
    } else {
        return false;
    }
    if ( isset( $tokens[ $index ] ) && $tokens[ $index ] === $close ) {
        $index++;
        return true;
    }
    while ( isset( $tokens[ $index ] ) ) {
        $saved = $index;
        if ( chatbot_chatgpt_gfi_parse_literal_scalar( $tokens, $index ) && isset( $tokens[ $index ] ) && is_array( $tokens[ $index ] ) && $tokens[ $index ][0] === T_DOUBLE_ARROW ) {
            $index++;
        } else {
            $index = $saved;
        }
        if ( ! chatbot_chatgpt_gfi_parse_literal_value( $tokens, $index ) ) {
            return false;
        }
        if ( ! isset( $tokens[ $index ] ) ) {
            return false;
        }
        if ( ',' === $tokens[ $index ] ) {
            $index++;
            if ( isset( $tokens[ $index ] ) && $tokens[ $index ] === $close ) {
                $index++;
                return true;
            }
            continue;
        }
        if ( $tokens[ $index ] === $close ) {
            $index++;
            return true;
        }
        return false;
    }

    return false;
}

/**
 * @param array<int, mixed> $tokens Tokens with whitespace removed.
 * @param int               $index  Cursor.
 * @return bool
 */
function chatbot_chatgpt_gfi_parse_literal_value( $tokens, &$index ) {
    if ( ! isset( $tokens[ $index ] ) ) {
        return false;
    }
    $token = $tokens[ $index ];
    if ( is_array( $token ) && $token[0] === T_ARRAY ) {
        return chatbot_chatgpt_gfi_parse_literal_array( $tokens, $index );
    }
    if ( '[' === $token ) {
        return chatbot_chatgpt_gfi_parse_literal_array( $tokens, $index );
    }

    return chatbot_chatgpt_gfi_parse_literal_scalar( $tokens, $index );
}

/**
 * @param array<int, mixed> $tokens Tokens with whitespace removed.
 * @param int               $index  Cursor.
 * @return bool
 */
function chatbot_chatgpt_gfi_parse_literal_scalar( $tokens, &$index ) {
    if ( ! isset( $tokens[ $index ] ) ) {
        return false;
    }
    $token = $tokens[ $index ];
    if ( '+' === $token || '-' === $token ) {
        $index++;
        if ( ! isset( $tokens[ $index ] ) || ! is_array( $tokens[ $index ] ) ) {
            return false;
        }
        if ( $tokens[ $index ][0] !== T_LNUMBER && $tokens[ $index ][0] !== T_DNUMBER ) {
            return false;
        }
        $index++;
        return true;
    }
    if ( ! is_array( $token ) ) {
        return false;
    }
    if ( $token[0] === T_CONSTANT_ENCAPSED_STRING || $token[0] === T_LNUMBER || $token[0] === T_DNUMBER ) {
        $index++;
        return true;
    }
    if ( $token[0] === T_STRING && in_array( strtolower( (string) $token[1] ), array( 'null', 'true', 'false', 'nan', 'inf' ), true ) ) {
        $index++;
        return true;
    }

    return false;
}

/**
 * Decode serialized cache bytes and refuse objects.
 *
 * @param string $serialized Serialized PHP.
 * @return array<mixed>|null
 */
function chatbot_chatgpt_gfi_unserialize_array( $serialized ) {
    if ( ! is_string( $serialized ) || $serialized === '' ) {
        return null;
    }
    $value = @unserialize( $serialized, array( 'allowed_classes' => false ) );

    return is_array( $value ) ? $value : null;
}

/**
 * Run a literal cache from memory. A missing stream wrapper fails closed.
 *
 * @param string $bytes Authenticated or literal-checked bytes.
 * @return mixed|null
 */
function chatbot_chatgpt_gfi_execute_buffer( $bytes ) {
    if ( ! chatbot_chatgpt_gfi_buffer_is_literal_return( $bytes ) || ! chatbot_chatgpt_gfi_register_stream() ) {
        return null;
    }
    $id = chatbot_chatgpt_gfi_token();
    if ( ! Chatbot_Chatgpt_Gfi_Verified_Stream::stage( $id, $bytes ) ) {
        return null;
    }
    $value = null;
    try {
        $value = include 'gfi-verified://' . $id;
    } catch ( Throwable $exception ) {
        unset( $exception );
        $value = null;
    } finally {
        Chatbot_Chatgpt_Gfi_Verified_Stream::drop( $id );
    }

    return is_array( $value ) ? $value : null;
}

/**
 * Install this request's wrapper. A wrapper already bound to the protocol is replaced.
 *
 * Replacement fails closed when the previous wrapper cannot be removed.
 * The protocol is registered without STREAM_IS_URL.
 *
 * @return bool
 */
function chatbot_chatgpt_gfi_register_stream() {
    if ( ! function_exists( 'stream_wrapper_register' ) || ! function_exists( 'stream_wrapper_unregister' ) ) {
        return false;
    }
    if ( in_array( 'gfi-verified', stream_get_wrappers(), true ) && ! @stream_wrapper_unregister( 'gfi-verified' ) ) {
        return false;
    }

    return stream_wrapper_register( 'gfi-verified', 'Chatbot_Chatgpt_Gfi_Verified_Stream', 0 );
}

/**
 * In-memory source for an authenticated cache. This is not a filesystem path.
 */
class Chatbot_Chatgpt_Gfi_Verified_Stream {
    /**
     * Validated buffers for this request. Callers cannot supply PHP through a global.
     *
     * @var array<string, string>
     */
    private static $buffers = array();

    /**
     * @var string
     */
    private $data = '';

    /**
     * @var int
     */
    private $position = 0;

    /**
     * @return int
     */
    public static function pending_count() {
        return count( self::$buffers );
    }

    /**
     * @param string $id    32 hex characters.
     * @param string $bytes Bytes already checked by the caller.
     * @return bool
     */
    public static function stage( $id, $bytes ) {
        if ( ! is_string( $id ) || ! preg_match( '/^[a-f0-9]{32,64}$/', $id ) ) {
            return false;
        }
        if ( ! chatbot_chatgpt_gfi_buffer_is_literal_return( $bytes ) ) {
            return false;
        }
        self::$buffers[ $id ] = $bytes;

        return true;
    }

    /**
     * @param string $id Buffer id.
     * @return void
     */
    public static function drop( $id ) {
        unset( self::$buffers[ $id ] );
    }

    /**
     * @param string $path Stream URL.
     * @return string
     */
    private static function id_from_path( $path ) {
        $prefix = 'gfi-verified://';
        $path   = (string) $path;
        if ( strncmp( $path, $prefix, strlen( $prefix ) ) !== 0 ) {
            return '';
        }
        $id = substr( $path, strlen( $prefix ) );
        if ( ! preg_match( '/^[a-f0-9]{32,64}$/', $id ) ) {
            return '';
        }

        return $id;
    }

    /**
     * @param string $id Buffer id.
     * @return string|null
     */
    private static function validated_bytes( $id ) {
        if ( ! isset( self::$buffers[ $id ] ) || ! is_string( self::$buffers[ $id ] ) ) {
            return null;
        }
        $bytes = self::$buffers[ $id ];
        if ( ! chatbot_chatgpt_gfi_buffer_is_literal_return( $bytes ) ) {
            unset( self::$buffers[ $id ] );
            return null;
        }

        return $bytes;
    }

    /**
     * @param string $path        Stream URL.
     * @param string $mode        Open mode.
     * @param int    $options     Open options.
     * @param string $opened_path Resolved path, unused.
     * @return bool
     */
    public function stream_open( $path, $mode, $options, &$opened_path ) {
        unset( $mode, $options, $opened_path );
        $bytes = self::validated_bytes( self::id_from_path( $path ) );
        if ( ! is_string( $bytes ) ) {
            return false;
        }
        $this->data     = $bytes;
        $this->position = 0;

        return true;
    }

    /**
     * @param int $count Maximum bytes.
     * @return string
     */
    public function stream_read( $count ) {
        $chunk           = substr( $this->data, $this->position, $count );
        $this->position += strlen( $chunk );

        return $chunk;
    }

    /**
     * @return bool
     */
    public function stream_eof() {
        return $this->position >= strlen( $this->data );
    }

    /**
     * @return array<string, int>
     */
    public function stream_stat() {
        return array( 'size' => strlen( $this->data ) );
    }

    /**
     * @param string $path  Stream URL.
     * @param int    $flags Stat flags.
     * @return array<string, int>
     */
    public function url_stat( $path, $flags ) {
        unset( $flags );
        $bytes = self::validated_bytes( self::id_from_path( $path ) );
        if ( ! is_string( $bytes ) ) {
            return false;
        }

        return array( 'size' => strlen( $bytes ) );
    }

    /**
     * @return void
     */
    public function stream_close() {
        $this->data     = '';
        $this->position = 0;
    }

    /**
     * @return int
     */
    public function stream_tell() {
        return $this->position;
    }

    /**
     * @param int   $option Option.
     * @param mixed $arg1   First argument.
     * @param mixed $arg2   Second argument.
     * @return bool
     */
    public function stream_set_option( $option, $arg1, $arg2 ) {
        unset( $option, $arg1, $arg2 );

        return true;
    }

    /**
     * @param int $offset Offset.
     * @param int $whence SEEK_SET, SEEK_CUR, or SEEK_END.
     * @return bool
     */
    public function stream_seek( $offset, $whence ) {
        $length = strlen( $this->data );
        if ( SEEK_SET === $whence ) {
            $next = $offset;
        } elseif ( SEEK_CUR === $whence ) {
            $next = $this->position + $offset;
        } else {
            $next = $length + $offset;
        }
        if ( $next < 0 || $next > $length ) {
            return false;
        }
        $this->position = $next;

        return true;
    }
}

/**
 * Shared opener for a single artifact that already belongs to a trusted generation.
 *
 * @param string $engine Engine slug.
 * @param string $path   File path.
 * @return array{handle: resource, size: int}|false
 */
function chatbot_chatgpt_gfi_open_record( $engine, $path ) {
    if ( ! chatbot_chatgpt_gfi_generation_is_trusted( $engine, true ) ) {
        return false;
    }
    $name = chatbot_chatgpt_gfi_relative_from_path( $engine, $path );
    $manifest = chatbot_chatgpt_gfi_manifest( $engine );
    if ( $name === '' || ! is_array( $manifest ) || ! isset( $manifest['files'][ $name ] ) || ! is_array( $manifest['files'][ $name ] ) ) {
        chatbot_chatgpt_gfi_fail( $engine, 'missing_signature' );
        return false;
    }
    $key = chatbot_chatgpt_gfi_signing_key( chatbot_chatgpt_gfi_site_id() );
    if ( $key === '' || ! function_exists( 'chatbot_chatgpt_beta_engine_cache_directory' ) ) {
        chatbot_chatgpt_gfi_fail( $engine, 'read_error' );
        return false;
    }
    $resolved = chatbot_chatgpt_gfi_resolve( chatbot_chatgpt_beta_engine_cache_directory( $engine ), $name );
    if ( $resolved === '' ) {
        chatbot_chatgpt_gfi_fail( $engine, 'bad_path' );
        return false;
    }
    $opened = chatbot_chatgpt_gfi_verify_open( $engine, $resolved, $manifest['files'][ $name ], $key, true );
    if ( ! is_array( $opened ) ) {
        chatbot_chatgpt_gfi_fail( $engine, chatbot_chatgpt_gfi_last_code() );
        return false;
    }

    return $opened;
}

/**
 * Sentential and Lexical schedules that must not be restarted by an integrity failure.
 *
 * Completed is an administrator follow-up. No, Disable, Cancel, and Never stay off.
 *
 * @param string $engine Engine slug.
 * @return string allowed|disabled|needs_admin
 */
function chatbot_chatgpt_gfi_recovery_policy( $engine ) {
    if ( $engine === 'sentential' ) {
        $schedule = function_exists( 'get_option' ) ? (string) get_option( 'chatbot_transformer_model_build_schedule', 'Disable' ) : 'Disable';
        if ( in_array( $schedule, array( 'No', 'Disable', 'Cancel', 'No Schedule', '' ), true ) ) {
            return 'disabled';
        }
        if ( $schedule === 'Completed' ) {
            return 'needs_admin';
        }

        return 'allowed';
    }
    if ( $engine === 'lexical' ) {
        $schedule = function_exists( 'get_option' ) ? (string) get_option( 'chatbot_lcm_lexical_cache_rebuild_schedule', 'Never' ) : 'Never';
        if ( in_array( $schedule, array( 'Now', 'Daily', 'Weekly', 'Monthly' ), true ) ) {
            return 'allowed';
        }

        return 'disabled';
    }

    return 'disabled';
}

/**
 * Queue one existing scheduler event. This does not run the rebuild.
 *
 * @param string $engine Engine slug.
 * @param string $code   Sanitized failure code.
 * @return string
 */
function chatbot_chatgpt_gfi_schedule_recovery( $engine, $code ) {
    if ( ! chatbot_chatgpt_gfi_is_engine( $engine ) ) {
        return 'disabled';
    }
    $code     = chatbot_chatgpt_gfi_code( $code );
    $policy   = chatbot_chatgpt_gfi_recovery_policy( $engine );
    $recovery = chatbot_chatgpt_gfi_recovery( $engine );
    $recovery['site_id'] = chatbot_chatgpt_gfi_site_id();
    $recovery['code']    = $code;
    if ( $policy !== 'allowed' ) {
        $recovery['status']  = $policy;
        $recovery['pending'] = false;
        chatbot_chatgpt_gfi_save_recovery( $engine, $recovery );
        return $policy;
    }
    $attempts = isset( $recovery['attempts'] ) ? (int) $recovery['attempts'] : 0;
    $next     = isset( $recovery['next_gmt'] ) ? (int) $recovery['next_gmt'] : 0;
    if ( ! empty( $recovery['pending'] ) && $next > time() ) {
        $recovery['status'] = 'pending';
        chatbot_chatgpt_gfi_save_recovery( $engine, $recovery );
        return 'pending';
    }
    if ( $attempts >= 3 ) {
        $recovery['status']  = 'exhausted';
        $recovery['pending'] = false;
        chatbot_chatgpt_gfi_save_recovery( $engine, $recovery );
        return 'exhausted';
    }

    $delays = array( 1 => 900, 2 => 3600, 3 => 21600 );
    $attempts++;
    $delay = isset( $delays[ $attempts ] ) ? (int) $delays[ $attempts ] : 21600;
    $when  = time() + $delay;
    $recovery['attempts'] = $attempts;
    $recovery['pending']  = true;
    $recovery['next_gmt'] = $when;
    $recovery['status']   = 'scheduled';
    chatbot_chatgpt_gfi_save_recovery( $engine, $recovery );

    $hook = 'chatbot_transformer_model_scan_hook';
    $args = array();
    if ( $engine === 'lexical' ) {
        $hook = 'chatbot_transformer_model_lexical_cache_rebuild_cron';
        $args = array( 'once' );
    }
    $already = function_exists( 'wp_next_scheduled' ) ? wp_next_scheduled( $hook, $args ) : false;
    if ( ! $already && function_exists( 'wp_schedule_single_event' ) ) {
        wp_schedule_single_event( $when, $hook, $args );
    }
    if ( function_exists( 'prod_trace' ) ) {
        prod_trace( 'ERROR', 'GFI ' . $engine . ' ' . $code );
    }

    return 'scheduled';
}

/**
 * Make one generation unusable and ask the existing scheduler for a single retry.
 *
 * @param string $engine Engine slug.
 * @param string $code   Sanitized failure code.
 * @return void
 */
function chatbot_chatgpt_gfi_fail( $engine, $code ) {
    if ( ! chatbot_chatgpt_gfi_is_engine( $engine ) ) {
        return;
    }
    $runtime = &chatbot_chatgpt_gfi_runtime();
    $latch   = chatbot_chatgpt_gfi_site_id() . '|' . $engine;
    if ( isset( $runtime['failed'][ $latch ] ) ) {
        return;
    }
    $runtime['failed'][ $latch ] = true;
    $runtime['in_fail']          = true;
    $code = chatbot_chatgpt_gfi_code( $code );
    try {
        if ( function_exists( 'chatbot_chatgpt_beta_engine_invalidate' ) ) {
            chatbot_chatgpt_beta_engine_invalidate( $engine );
        } else {
            chatbot_chatgpt_gfi_discard( $engine );
        }
        chatbot_chatgpt_gfi_schedule_recovery( $engine, $code );
    } finally {
        $runtime['in_fail'] = false;
    }
}

/**
 * Administrator notice when recovery will not run by itself.
 *
 * @return void
 */
function chatbot_chatgpt_gfi_admin_notice() {
    if ( ! function_exists( 'is_admin' ) || ! is_admin() ) {
        return;
    }
    if ( ! function_exists( 'current_user_can' ) || ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $page = isset( $_GET['page'] ) ? (string) $_GET['page'] : '';
    if ( function_exists( 'wp_unslash' ) ) {
        $page = (string) wp_unslash( $page );
    }
    if ( $page !== 'chatbot-chatgpt' && strpos( $page, 'chatbot-chatgpt' ) !== 0 ) {
        return;
    }
    foreach ( chatbot_chatgpt_gfi_engines() as $engine ) {
        $status = chatbot_chatgpt_gfi_status( $engine );
        if ( ! in_array( $status['recovery'], array( 'disabled', 'needs_admin', 'exhausted' ), true ) ) {
            continue;
        }
        $label = $engine === 'sentential' ? 'Sentential Context' : 'Lexical Context';
        if ( $status['recovery'] === 'exhausted' ) {
            $text = $label . ' stayed unavailable after repeated rebuilds failed an integrity check. Choose Now or an interval to try again.';
        } elseif ( $status['recovery'] === 'needs_admin' ) {
            $text = $label . ' finished its last schedule. An integrity failure left that cache unavailable. Choose Now or an interval to rebuild it.';
        } else {
            $text = $label . ' is turned off, so its cache was not rebuilt after an integrity failure. Choose Now or an interval to rebuild it.';
        }
        $safe = function_exists( 'esc_html' ) ? esc_html( $text ) : htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
        echo '<div class="notice notice-warning"><p>' . $safe . '</p></div>';
    }
}
if ( function_exists( 'add_action' ) ) {
    add_action( 'admin_notices', 'chatbot_chatgpt_gfi_admin_notice' );
}

require_once __DIR__ . '/chatbot-generated-cache-cleanup.php';
