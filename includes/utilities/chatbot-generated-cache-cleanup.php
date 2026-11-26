<?php
/**
 * Kognetiks Chatbot - Generated cache cleanup plan.
 *
 * This file inventories known disposable Sentential and Lexical cache artifacts.
 * Deletion stays off unless CHATBOT_CHATGPT_GFI_CLEANUP_EXECUTE is exactly true.
 * That constant is not defined by the plugin. Nothing here is attached to init.
 *
 * @package chatbot-chatgpt
 */

if ( ! defined( 'WPINC' ) ) {
    die();
}

/**
 * Cleanup revision. This is not the plugin version.
 *
 * @return string
 */
function chatbot_chatgpt_generated_cache_cleanup_revision() {
    return 'chatbot_chatgpt_generated_cache_cleanup_v1';
}

/**
 * @return bool
 */
function chatbot_chatgpt_generated_cache_cleanup_enabled() {
    return defined( 'CHATBOT_CHATGPT_GFI_CLEANUP_EXECUTE' ) && CHATBOT_CHATGPT_GFI_CLEANUP_EXECUTE === true;
}

/**
 * @return string
 */
function chatbot_chatgpt_generated_cache_cleanup_state_key() {
    return chatbot_chatgpt_generated_cache_cleanup_revision();
}

/**
 * Names this cleanup will never select.
 *
 * @param string $name Basename.
 * @return bool
 */
function chatbot_chatgpt_generated_cache_cleanup_is_guard( $name ) {
    return in_array( strtolower( (string) $name ), array( 'index.php', '.htaccess', 'web.config' ), true );
}

/**
 * A name is disposable when either engine's known pattern matches it.
 *
 * @param string $name Basename.
 * @return bool
 */
function chatbot_chatgpt_generated_cache_cleanup_name_is_disposable( $name ) {
    if ( ! function_exists( 'chatbot_chatgpt_beta_legacy_cache_filename_is_disposable' ) ) {
        return false;
    }
    foreach ( array( 'sentential', 'lexical' ) as $engine ) {
        if ( chatbot_chatgpt_beta_legacy_cache_filename_is_disposable( $engine, $name ) ) {
            return true;
        }
    }

    return false;
}

/**
 * Classify one basename. This does not touch the filesystem.
 *
 * @param string        $name         Basename.
 * @param array<int, string> $signed_names Basenames in the current verified manifest.
 * @return string
 */
function chatbot_chatgpt_generated_cache_cleanup_classify_name( $name, $signed_names ) {
    $name = (string) $name;
    if ( $name === '' || strpos( $name, '/' ) !== false || strpos( $name, '\\' ) !== false || strpos( $name, '..' ) !== false ) {
        return 'reject-name';
    }
    if ( chatbot_chatgpt_generated_cache_cleanup_is_guard( $name ) ) {
        return 'preserve-guard';
    }
    if ( in_array( $name, $signed_names, true ) ) {
        return 'preserve-signed';
    }
    if ( chatbot_chatgpt_generated_cache_cleanup_name_is_disposable( $name ) ) {
        return 'eligible';
    }

    return 'preserve-unrelated';
}

/**
 * Read one directory without following links or entering child directories.
 *
 * @param string             $directory   Directory to examine. The caller supplies it.
 * @param array<int, string> $signed_names Basenames that belong to a verified generation.
 * @return array<string, mixed>
 */
function chatbot_chatgpt_generated_cache_cleanup_plan( $directory, $signed_names = array() ) {
    $plan = array(
        'revision'  => chatbot_chatgpt_generated_cache_cleanup_revision(),
        'directory' => (string) $directory,
        'enabled'   => chatbot_chatgpt_generated_cache_cleanup_enabled(),
        'entries'   => array(),
    );
    if ( ! is_string( $directory ) || $directory === '' || is_link( $directory ) || ! is_dir( $directory ) ) {
        $plan['error'] = 'unreadable-directory';
        return $plan;
    }
    $names = scandir( $directory );
    if ( ! is_array( $names ) ) {
        $plan['error'] = 'unreadable-directory';
        return $plan;
    }
    foreach ( $names as $name ) {
        if ( $name === '.' || $name === '..' ) {
            continue;
        }
        $path = $directory . DIRECTORY_SEPARATOR . $name;
        if ( is_link( $path ) ) {
            $action = 'reject-symlink';
        } elseif ( is_dir( $path ) ) {
            $action = 'preserve-directory';
        } else {
            $action = chatbot_chatgpt_generated_cache_cleanup_classify_name( $name, $signed_names );
        }
        $plan['entries'][] = array(
            'name'   => $name,
            'action' => $action,
        );
    }

    return $plan;
}

/**
 * Whether two directories are the same path.
 *
 * @param string $left  First path.
 * @param string $right Second path.
 * @return bool
 */
function chatbot_chatgpt_generated_cache_cleanup_same_directory( $left, $right ) {
    if ( ! function_exists( 'chatbot_chatgpt_beta_normalize_path' ) ) {
        return false;
    }
    $left  = strtolower( rtrim( chatbot_chatgpt_beta_normalize_path( $left ), '/' ) );
    $right = strtolower( rtrim( chatbot_chatgpt_beta_normalize_path( $right ), '/' ) );

    return $left !== '' && $left === $right;
}

/**
 * The current site cache directory or the shared legacy root this path names.
 *
 * A child directory, another site's directory, and any other path are refused.
 *
 * @param string $directory Candidate directory.
 * @return array{kind: string, engine: string}|null
 */
function chatbot_chatgpt_generated_cache_cleanup_directory_scope( $directory ) {
    if ( ! function_exists( 'chatbot_chatgpt_beta_engine_cache_directory' ) || ! function_exists( 'chatbot_chatgpt_beta_legacy_plugin_cache_root' ) ) {
        return null;
    }
    foreach ( array( 'sentential', 'lexical' ) as $engine ) {
        $site = chatbot_chatgpt_beta_engine_cache_directory( $engine );
        if ( chatbot_chatgpt_generated_cache_cleanup_same_directory( $directory, $site ) ) {
            return array(
                'kind'   => 'site',
                'engine' => $engine,
            );
        }
    }
    foreach ( array( 'sentential', 'lexical' ) as $engine ) {
        $legacy = chatbot_chatgpt_beta_legacy_plugin_cache_root( $engine );
        if ( chatbot_chatgpt_generated_cache_cleanup_same_directory( $directory, $legacy ) ) {
            return array(
                'kind'   => 'legacy',
                'engine' => $engine,
            );
        }
    }

    return null;
}

/**
 * Basenames of the generation that verifies right now.
 *
 * A legacy shared root has no signed generation. An untrusted site directory has none either.
 *
 * @param string $engine Engine slug.
 * @param string $kind   site or legacy.
 * @return array<int, string>
 */
function chatbot_chatgpt_generated_cache_cleanup_live_signed_names( $engine, $kind ) {
    if ( $kind !== 'site' || ! function_exists( 'chatbot_chatgpt_gfi_generation_is_trusted' ) || ! function_exists( 'chatbot_chatgpt_gfi_manifest' ) ) {
        return array();
    }
    if ( ! chatbot_chatgpt_gfi_generation_is_trusted( $engine, false ) ) {
        return array();
    }
    $manifest = chatbot_chatgpt_gfi_manifest( $engine );
    if ( ! is_array( $manifest ) || ! isset( $manifest['files'] ) || ! is_array( $manifest['files'] ) ) {
        return array();
    }

    return array_map( 'strval', array_keys( $manifest['files'] ) );
}

/**
 * Delete only when execution has been explicitly enabled.
 *
 * The supplied name list is a dry-run hint. Enabled deletion ignores it and reads
 * the live manifest under the publication lock immediately before unlinking.
 * The plugin does not define the execution switch and does not call this during requests.
 *
 * @param string             $directory    Directory the caller has already chosen.
 * @param array<int, string> $signed_names Dry-run names. Ignored once deletion is enabled.
 * @return array<string, mixed>
 */
function chatbot_chatgpt_generated_cache_cleanup_execute( $directory, $signed_names = array() ) {
    $plan   = chatbot_chatgpt_generated_cache_cleanup_plan( $directory, $signed_names );
    $result = array(
        'revision'  => chatbot_chatgpt_generated_cache_cleanup_revision(),
        'enabled'   => false,
        'completed' => false,
        'deleted'   => array(),
        'failed'    => array(),
        'status'    => 'disabled',
        'plan'      => $plan,
    );
    if ( ! chatbot_chatgpt_generated_cache_cleanup_enabled() ) {
        return $result;
    }

    $result['enabled'] = true;
    $scope             = chatbot_chatgpt_generated_cache_cleanup_directory_scope( $directory );
    if ( ! is_array( $scope ) ) {
        $result['status'] = 'refused';
        chatbot_chatgpt_generated_cache_cleanup_store( $result );
        return $result;
    }
    if ( is_link( $directory ) ) {
        $result['status']   = 'failed';
        $result['failed'][] = '.';
        chatbot_chatgpt_generated_cache_cleanup_store( $result );
        return $result;
    }
    $engine = (string) $scope['engine'];
    $kind   = (string) $scope['kind'];
    if ( $kind === 'site' && function_exists( 'chatbot_chatgpt_gfi_build_is_active' ) && chatbot_chatgpt_gfi_build_is_active( $engine ) ) {
        $result['status'] = 'busy';
        chatbot_chatgpt_generated_cache_cleanup_store( $result );
        return $result;
    }
    if ( ! function_exists( 'chatbot_chatgpt_gfi_lock' ) || ! function_exists( 'chatbot_chatgpt_gfi_token' ) ) {
        $result['status'] = 'failed';
        chatbot_chatgpt_generated_cache_cleanup_store( $result );
        return $result;
    }
    $token = chatbot_chatgpt_gfi_token();
    if ( ! chatbot_chatgpt_gfi_lock( $engine, $token ) ) {
        $result['status'] = 'busy';
        chatbot_chatgpt_generated_cache_cleanup_store( $result );
        return $result;
    }
    try {
        if ( $kind === 'site' && function_exists( 'chatbot_chatgpt_gfi_build_is_active' ) && chatbot_chatgpt_gfi_build_is_active( $engine ) ) {
            $result['status'] = 'busy';
            return $result;
        }
        $live = chatbot_chatgpt_generated_cache_cleanup_live_signed_names( $engine, $kind );
        $plan = chatbot_chatgpt_generated_cache_cleanup_plan( $directory, $live );
        $result['plan'] = $plan;
        if ( isset( $plan['error'] ) ) {
            if ( ! is_dir( $directory ) && ! is_link( $directory ) ) {
                $result['completed'] = true;
                $result['status']    = 'completed';
                return $result;
            }
            $result['status'] = 'failed';
            return $result;
        }
        foreach ( $plan['entries'] as $entry ) {
            if ( ( $entry['action'] ?? '' ) !== 'eligible' ) {
                continue;
            }
            $name = (string) $entry['name'];
            $live = chatbot_chatgpt_generated_cache_cleanup_live_signed_names( $engine, $kind );
            if ( in_array( $name, $live, true ) ) {
                continue;
            }
            $path = $directory . DIRECTORY_SEPARATOR . $name;
            if ( is_link( $path ) || is_dir( $path ) || ! is_file( $path ) ) {
                if ( is_file( $path ) || is_link( $path ) ) {
                    $result['failed'][] = $name;
                }
                continue;
            }
            if ( ! @unlink( $path ) && is_file( $path ) ) {
                $result['failed'][] = $name;
                continue;
            }
            $result['deleted'][] = $name;
        }
        $result['completed'] = $result['failed'] === array();
        $result['status']    = $result['completed'] ? 'completed' : 'failed';
    } finally {
        chatbot_chatgpt_gfi_unlock( $engine, $token );
        if ( $result['status'] !== 'disabled' ) {
            chatbot_chatgpt_generated_cache_cleanup_store( $result );
        }
    }

    return $result;
}

/**
 * @param array<string, mixed> $result Cleanup result.
 * @return void
 */
function chatbot_chatgpt_generated_cache_cleanup_store( $result ) {
    if ( ! function_exists( 'update_option' ) || empty( $result['enabled'] ) ) {
        return;
    }
    $status = isset( $result['status'] ) ? (string) $result['status'] : 'failed';
    if ( ! in_array( $status, array( 'completed', 'failed', 'busy', 'refused' ), true ) ) {
        $status = 'failed';
    }
    update_option(
        chatbot_chatgpt_generated_cache_cleanup_state_key(),
        array(
            'status'  => $status,
            'failed'  => isset( $result['failed'] ) && is_array( $result['failed'] ) ? array_values( $result['failed'] ) : array(),
            'deleted' => isset( $result['deleted'] ) && is_array( $result['deleted'] ) ? array_map( 'strval', $result['deleted'] ) : array(),
            'gmt'     => gmdate( 'Y-m-d H:i:s' ),
        ),
        false
    );
}
