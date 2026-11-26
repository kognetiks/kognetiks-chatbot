<?php
/**
 * Kognetiks Chatbot - Generated file storage.
 *
 * Runtime files live under the active site's WordPress uploads directory,
 * in a chatbot-chatgpt namespace. The path is not cached, so switch_to_blog()
 * is honored on the next call. Private logs are not placed here.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

/**
 * Approved storage directories and how their contents are classified.
 *
 * writable false means the uploads tree cannot be treated as a safe place
 * for that category. Callers keep the previous location or avoid the file.
 *
 * @return array<string,array{class:string,writable:bool,disposition:string}>
 */
function chatbot_chatgpt_generated_storage_catalog() {
    return array(
        'audio'                        => array( 'class' => 'user', 'writable' => true, 'disposition' => 'expire' ),
        'chatbot-logs'                 => array( 'class' => 'private', 'writable' => false, 'disposition' => 'blocked' ),
        'downloads'                    => array( 'class' => 'user', 'writable' => true, 'disposition' => 'expire' ),
        'results'                      => array( 'class' => 'private', 'writable' => false, 'disposition' => 'blocked' ),
        'transcripts'                  => array( 'class' => 'private', 'writable' => false, 'disposition' => 'blocked' ),
        'uploads'                      => array( 'class' => 'user', 'writable' => true, 'disposition' => 'expire' ),
        'widget-logs'                  => array( 'class' => 'private', 'writable' => false, 'disposition' => 'blocked' ),
        'sentential_embeddings_cache'  => array( 'class' => 'public', 'writable' => true, 'disposition' => 'rebuild' ),
        'lexical_embeddings_cache'     => array( 'class' => 'public', 'writable' => true, 'disposition' => 'rebuild' ),
    );
}

/**
 * @param string $path Path.
 * @return string
 */
function chatbot_chatgpt_generated_storage_normalize( $path ) {
    $path = trim( str_replace( '\\', '/', (string) $path ) );
    if ( $path === '' || strpos( $path, "\0" ) !== false || strpos( $path, '..' ) !== false ) {
        return '';
    }

    return rtrim( $path, '/' );
}

/**
 * @param string $path Path.
 * @return bool
 */
function chatbot_chatgpt_generated_storage_is_absolute( $path ) {
    return $path !== '' && ( substr( $path, 0, 1 ) === '/' || (bool) preg_match( '/^[A-Za-z]:\//', $path ) );
}

/**
 * Upload location for the active site. A test may supply the array.
 *
 * @return array{basedir:string,baseurl:string,error:string,shared:bool}
 */
function chatbot_chatgpt_generated_storage_upload_info() {
    if ( isset( $GLOBALS['chatbot_chatgpt_generated_storage_upload'] ) && is_array( $GLOBALS['chatbot_chatgpt_generated_storage_upload'] ) ) {
        $given = $GLOBALS['chatbot_chatgpt_generated_storage_upload'];
        return array(
            'basedir' => isset( $given['basedir'] ) ? (string) $given['basedir'] : '',
            'baseurl' => isset( $given['baseurl'] ) ? (string) $given['baseurl'] : '',
            'error'   => ! empty( $given['error'] ) ? (string) $given['error'] : '',
            'shared'  => ! empty( $given['shared'] ),
        );
    }

    if ( ! function_exists( 'wp_upload_dir' ) ) {
        return array( 'basedir' => '', 'baseurl' => '', 'error' => 'uploads_unavailable', 'shared' => false );
    }

    $uploads = wp_upload_dir();
    if ( ! is_array( $uploads ) || ! empty( $uploads['error'] ) || empty( $uploads['basedir'] ) ) {
        return array( 'basedir' => '', 'baseurl' => '', 'error' => 'uploads_error', 'shared' => false );
    }

    $shared = defined( 'UPLOADS' ) && is_string( UPLOADS ) && UPLOADS !== '';

    return array(
        'basedir' => (string) $uploads['basedir'],
        'baseurl' => isset( $uploads['baseurl'] ) ? (string) $uploads['baseurl'] : '',
        'error'   => '',
        'shared'  => $shared,
    );
}

/**
 * Site folder used only when every site shares one uploads directory.
 *
 * @return string
 */
function chatbot_chatgpt_generated_storage_site_segment() {
    $blog_id = function_exists( 'get_current_blog_id' ) ? (int) get_current_blog_id() : 0;
    if ( $blog_id < 1 ) {
        return '';
    }

    return 'site-' . $blog_id;
}

/**
 * Absolute chatbot-chatgpt directory for the active site, or an empty string.
 *
 * @return string
 */
function chatbot_chatgpt_generated_storage_namespace_dir() {
    $info    = chatbot_chatgpt_generated_storage_upload_info();
    $basedir = chatbot_chatgpt_generated_storage_normalize( $info['basedir'] );
    if ( $info['error'] !== '' || $basedir === '' || ! chatbot_chatgpt_generated_storage_is_absolute( $basedir ) ) {
        return '';
    }
    if ( is_link( $basedir ) || ! is_dir( $basedir ) || ! is_writable( $basedir ) ) {
        return '';
    }

    $namespace = $basedir . '/chatbot-chatgpt';
    if ( ! empty( $info['shared'] ) ) {
        $segment = chatbot_chatgpt_generated_storage_site_segment();
        if ( $segment === '' ) {
            return '';
        }
        $namespace .= '/' . $segment;
    }
    if ( is_link( $namespace ) || is_file( $namespace ) ) {
        return '';
    }
    if ( ! is_dir( $namespace ) ) {
        $made = function_exists( 'wp_mkdir_p' ) ? @wp_mkdir_p( $namespace ) : @mkdir( $namespace, 0755, true );
        if ( ( ! $made && ! is_dir( $namespace ) ) || is_link( $namespace ) ) {
            return '';
        }
    }
    @chmod( $namespace, 0755 );

    $real_base = realpath( $basedir );
    $real_ns   = realpath( $namespace );
    if ( false === $real_base || false === $real_ns ) {
        return '';
    }
    $real_base = chatbot_chatgpt_generated_storage_normalize( $real_base );
    $real_ns   = chatbot_chatgpt_generated_storage_normalize( $real_ns );
    if ( $real_ns === '' || strpos( strtolower( $real_ns ), strtolower( $real_base ) . '/' ) !== 0 ) {
        return '';
    }

    return $real_ns;
}

/**
 * Public URL for one stored file. Private categories return an empty string.
 *
 * @param string $slug     Approved directory.
 * @param string $filename Basename.
 * @return string
 */
function chatbot_chatgpt_generated_storage_url( $slug, $filename ) {
    $catalog = chatbot_chatgpt_generated_storage_catalog();
    if ( ! isset( $catalog[ $slug ] ) || empty( $catalog[ $slug ]['writable'] ) ) {
        return '';
    }

    $filename = str_replace( '\\', '/', (string) $filename );
    if ( $filename === '' || strpos( $filename, '/' ) !== false || strpos( $filename, '..' ) !== false ) {
        return '';
    }
    $filename = basename( $filename );
    if ( $filename === '' || $filename === '.' || $filename === '..' ) {
        return '';
    }

    $info    = chatbot_chatgpt_generated_storage_upload_info();
    $baseurl = rtrim( str_replace( '\\', '/', (string) $info['baseurl'] ), '/' );
    if ( $info['error'] !== '' || $baseurl === '' || strpos( $baseurl, '..' ) !== false ) {
        return '';
    }

    $url = $baseurl . '/chatbot-chatgpt/';
    if ( ! empty( $info['shared'] ) ) {
        $segment = chatbot_chatgpt_generated_storage_site_segment();
        if ( $segment === '' ) {
            return '';
        }
        $url .= $segment . '/';
    }

    return $url . $slug . '/' . rawurlencode( $filename );
}

/**
 * Absolute directory for an approved slug, or an empty string when it must not be used.
 *
 * @param string $slug Approved directory name.
 * @return string
 */
function chatbot_chatgpt_generated_storage_directory( $slug ) {
    $catalog = chatbot_chatgpt_generated_storage_catalog();
    if ( ! isset( $catalog[ $slug ] ) || empty( $catalog[ $slug ]['writable'] ) ) {
        return '';
    }

    $namespace = chatbot_chatgpt_generated_storage_namespace_dir();
    if ( $namespace === '' ) {
        return '';
    }

    $directory = $namespace . '/' . $slug;
    if ( is_link( $directory ) || is_file( $directory ) ) {
        return '';
    }
    if ( ! is_dir( $directory ) ) {
        $made = function_exists( 'wp_mkdir_p' ) ? @wp_mkdir_p( $directory ) : @mkdir( $directory, 0755, true );
        if ( ( ! $made && ! is_dir( $directory ) ) || is_link( $directory ) ) {
            return '';
        }
    }
    @chmod( $directory, 0755 );

    $real = realpath( $directory );
    if ( false === $real ) {
        return '';
    }
    $real = chatbot_chatgpt_generated_storage_normalize( $real );
    if ( strpos( strtolower( $real ), strtolower( $namespace ) . '/' . $slug ) !== 0 ) {
        return '';
    }

    return $real;
}

/**
 * Previous plugin-install location for one slug. Used to find leftovers, not for new writes.
 *
 * @param string $slug Approved directory name.
 * @return string
 */
function chatbot_chatgpt_generated_storage_legacy_directory( $slug ) {
    $catalog = chatbot_chatgpt_generated_storage_catalog();
    if ( ! isset( $catalog[ $slug ] ) ) {
        return '';
    }

    global $chatbot_chatgpt_plugin_dir_path;
    $root = isset( $chatbot_chatgpt_plugin_dir_path ) ? (string) $chatbot_chatgpt_plugin_dir_path : '';
    if ( $root === '' && function_exists( 'chatbot_chatgpt_beta_plugin_directory' ) ) {
        $root = chatbot_chatgpt_beta_plugin_directory();
    }
    $root = chatbot_chatgpt_generated_storage_normalize( $root );
    if ( $root === '' ) {
        return '';
    }

    if ( $slug === 'sentential_embeddings_cache' || $slug === 'lexical_embeddings_cache' ) {
        return $root . '/includes/transformers/' . $slug;
    }

    return $root . '/' . $slug;
}

/**
 * What should happen to files already stored inside the plugin package.
 *
 * This describes the plan. It does not copy or delete anything.
 *
 * @return array<string,array{class:string,disposition:string,legacy:string}>
 */
function chatbot_chatgpt_generated_storage_relocation_plan() {
    $plan = array();
    foreach ( chatbot_chatgpt_generated_storage_catalog() as $slug => $meta ) {
        $plan[ $slug ] = array(
            'class'       => $meta['class'],
            'disposition' => $meta['disposition'],
            'legacy'      => chatbot_chatgpt_generated_storage_legacy_directory( $slug ),
        );
    }

    return $plan;
}

/**
 * Copy one file into an approved destination and remove the source only after the copy matches.
 *
 * Knowledge caches and private categories are refused. Nothing calls this during a normal request.
 *
 * @param string $source      Absolute source file.
 * @param string $slug        Approved writable slug.
 * @param string $basename    Required destination basename.
 * @return bool
 */
function chatbot_chatgpt_generated_storage_relocate_file( $source, $slug, $basename ) {
    $catalog = chatbot_chatgpt_generated_storage_catalog();
    if ( ! isset( $catalog[ $slug ] ) || $catalog[ $slug ]['disposition'] !== 'expire' ) {
        return false;
    }

    $basename = str_replace( '\\', '/', (string) $basename );
    if ( $basename === '' || strpos( $basename, '/' ) !== false || strpos( $basename, '..' ) !== false ) {
        return false;
    }
    $basename = basename( $basename );
    if ( $basename === '' || $basename === '.' || $basename === '..' ) {
        return false;
    }

    $source = chatbot_chatgpt_generated_storage_normalize( $source );
    if ( $source === '' || ! is_file( $source ) || is_link( $source ) ) {
        return false;
    }

    $directory = chatbot_chatgpt_generated_storage_directory( $slug );
    if ( $directory === '' ) {
        return false;
    }

    $destination = $directory . '/' . $basename;
    if ( is_file( $destination ) ) {
        return false;
    }
    if ( ! @copy( $source, $destination ) ) {
        return false;
    }

    $source_hash = @hash_file( 'sha256', $source );
    $dest_hash   = @hash_file( 'sha256', $destination );
    if ( ! is_string( $source_hash ) || $source_hash === '' || $source_hash !== $dest_hash ) {
        @unlink( $destination );
        return false;
    }

    if ( function_exists( 'wp_delete_file' ) ) {
        wp_delete_file( $source );
    } else {
        @unlink( $source );
    }

    return ! is_file( $source ) && is_file( $destination );
}
