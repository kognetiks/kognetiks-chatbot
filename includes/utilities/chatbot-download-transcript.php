<?php
/**
 * Kognetiks Chatbot - Transcript files - Ver 2.5.3
 *
 * Transcript downloads are built in the browser from the conversation already
 * on the page. This file removes previously generated public transcript files
 * and the retired cleanup schedule. It does not write transcript content.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

/**
 * Generated transcript names look like transcript_{26 alnum}_{UTC timestamp}.txt.
 *
 * @param string $path File path or basename.
 * @return bool
 */
function chatbot_chatgpt_is_generated_transcript_filename( $path ) {
    $base = function_exists( 'wp_basename' ) ? wp_basename( (string) $path ) : basename( (string) $path );
    return (bool) preg_match( '/^transcript_[A-Za-z0-9]{26}_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.txt$/', $base );
}

/**
 * Delete generated transcript files in one directory.
 *
 * index.php, .htaccess, and every other filename are left in place.
 * Returns false when the directory exists but cannot be listed.
 *
 * @param string|null $directory Transcript directory. Null uses the plugin transcripts folder.
 * @return int|false
 */
function chatbot_chatgpt_purge_generated_transcript_files( $directory = null ) {
    if ( null === $directory ) {
        global $chatbot_chatgpt_plugin_dir_path;
        $directory = rtrim( (string) $chatbot_chatgpt_plugin_dir_path, '/\\' ) . '/transcripts';
    }

    $directory = rtrim( (string) $directory, '/\\' );
    if ( '' === $directory || ! is_dir( $directory ) ) {
        return 0;
    }

    $real_dir = realpath( $directory );
    if ( false === $real_dir || ! is_dir( $real_dir ) ) {
        return false;
    }

    $entries = glob( $real_dir . DIRECTORY_SEPARATOR . '*' );
    if ( ! is_array( $entries ) ) {
        return false;
    }

    $prefix = strtolower( str_replace( '\\', '/', $real_dir ) ) . '/';
    $deleted = 0;

    foreach ( $entries as $file ) {
        if ( ! is_file( $file ) || ! chatbot_chatgpt_is_generated_transcript_filename( $file ) ) {
            continue;
        }
        $real_file = realpath( $file );
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
 * Drop the retired hourly cleanup and delete leftover generated transcript files once.
 *
 * @return void
 */
function chatbot_chatgpt_retire_public_transcript_files() {
    if ( function_exists( 'wp_clear_scheduled_hook' ) ) {
        wp_clear_scheduled_hook( 'chatbot_chatgpt_cleanup_transcript_files' );
        wp_clear_scheduled_hook( 'chatbot_chatgpt_cleanup_transcripts' );
    }

    if ( get_option( 'chatbot_chatgpt_public_transcripts_retired_v1', '' ) === 'Completed' ) {
        return;
    }

    $removed = chatbot_chatgpt_purge_generated_transcript_files();
    if ( false === $removed ) {
        return;
    }

    update_option( 'chatbot_chatgpt_public_transcripts_retired_v1', 'Completed' );
}
add_action( 'init', 'chatbot_chatgpt_retire_public_transcript_files', 21 );
