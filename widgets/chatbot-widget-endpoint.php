<?php
/**
 * Kognetiks Chatbot - Legacy remote widget URL - Ver 2.4.8
 *
 * Direct access must not bootstrap WordPress (no wp-load.php walk).
 * Known query args are forwarded to the front controller four levels up, which
 * is ABSPATH on a standard wp-content/plugins/{plugin}/widgets/ layout.
 *
 * Preferred URL: /kognetiks-chatbot-widget/?assistant=...&token=...
 *
 * @package chatbot-chatgpt
 */

if ( ! defined( 'ABSPATH' ) ) {
    /**
     * Server-derived path to WordPress index.php (never taken from request params).
     *
     * @return string Root-relative `/…/index.php` or a relative fallback.
     */
    $chatbot_chatgpt_legacy_widget_index_path = static function () {
        $script = ( isset( $_SERVER['SCRIPT_NAME'] ) && is_string( $_SERVER['SCRIPT_NAME'] ) )
            ? str_replace( '\\', '/', $_SERVER['SCRIPT_NAME'] )
            : '';

        if (
            '' === $script
            || '/' !== $script[0]
            || false !== strpos( $script, '..' )
            || false !== strpbrk( $script, "\r\n\0" )
            || 1 !== preg_match( '#/widgets/chatbot-widget-endpoint\.php$#', $script )
        ) {
            return '../../../../index.php';
        }

        $base = dirname( dirname( dirname( dirname( dirname( $script ) ) ) ) );
        $base = str_replace( '\\', '/', (string) $base );

        if ( '/' === $base || '.' === $base || '' === $base ) {
            $path = '/index.php';
        } else {
            $path = rtrim( $base, '/' ) . '/index.php';
        }

        if ( '/index.php' === $path ) {
            return $path;
        }

        if ( 1 === preg_match( '#^/(?:[^\s/:\\\\]+/)+index\.php$#', $path ) && false === strpos( $path, '..' ) ) {
            return $path;
        }

        return '../../../../index.php';
    };

    /**
     * Allowlist a forwarded widget query value. Returns null to drop the arg.
     *
     * @param string $key   assistant|token|width|height|chatbot_prompt.
     * @param mixed  $value Raw request value.
     * @return string|null
     */
    $chatbot_chatgpt_legacy_widget_sanitize_arg = static function ( $key, $value ) {
        if ( ! is_string( $value ) ) {
            return null;
        }

        $value = str_replace( array( "\r", "\n", "\0" ), '', $value );
        if ( strlen( $value ) > 512 ) {
            $value = substr( $value, 0, 512 );
        }

        switch ( $key ) {
            case 'assistant':
                if ( preg_match( '/^(?:chatbot|chatbot_chatgpt|kognetiks_chatbot|(?:chatbot|assistant|agent)-\d+)$/', $value, $match ) ) {
                    return $match[0];
                }
                return null;

            case 'token':
                if ( preg_match( '/^[a-fA-F0-9]{64}$/', $value, $match ) ) {
                    return strtolower( $match[0] );
                }
                return null;

            case 'width':
            case 'height':
                if ( preg_match( '/^\d{1,4}$/', $value, $match ) ) {
                    $number = (int) $match[0];
                    if ( $number >= 1 && $number <= 1000 ) {
                        return (string) $number;
                    }
                }
                return null;

            case 'chatbot_prompt':
                if ( preg_match( '/[\x00-\x1F\x7F]/', $value ) ) {
                    return null;
                }
                return $value;

            default:
                return null;
        }
    };

    /**
     * Whether $url is a same-document relative redirect to $expected_path.
     *
     * @param string $url           Candidate Location value.
     * @param string $expected_path Allowed path (no query).
     * @return bool
     */
    $chatbot_chatgpt_legacy_widget_is_safe_redirect = static function ( $url, $expected_path ) {
        if ( ! is_string( $url ) || '' === $url || ! is_string( $expected_path ) || '' === $expected_path ) {
            return false;
        }

        if ( false !== strpbrk( $url, "\r\n\0" ) ) {
            return false;
        }

        if ( preg_match( '#^[a-z][a-z0-9+.-]*:#i', $url ) || 0 === strpos( $url, '//' ) ) {
            return false;
        }

        $parts = parse_url( $url );
        if ( ! is_array( $parts ) ) {
            return false;
        }

        if ( isset( $parts['scheme'] ) || isset( $parts['host'] ) || isset( $parts['user'] ) || isset( $parts['port'] ) ) {
            return false;
        }

        return isset( $parts['path'] ) && $parts['path'] === $expected_path;
    };

    $path    = $chatbot_chatgpt_legacy_widget_index_path();
    $forward = array();
    $keys    = array( 'assistant', 'token', 'width', 'height', 'chatbot_prompt' );

    foreach ( $keys as $key ) {
        if ( ! isset( $_GET[ $key ] ) ) {
            continue;
        }

        $clean = $chatbot_chatgpt_legacy_widget_sanitize_arg( $key, $_GET[ $key ] );
        if ( null !== $clean ) {
            $forward[ $key ] = $clean;
        }
    }

    $query = 'kognetiks_chatbot_widget=1';
    foreach ( $forward as $key => $value ) {
        $query .= '&' . rawurlencode( $key ) . '=' . rawurlencode( $value );
    }

    $location = $path . '?' . $query;
    $stripped = preg_replace( '|[^a-zA-Z0-9\-~+_.?#=&;,/:%!*\[\]()@]|', '', $location );
    if ( is_string( $stripped ) && $chatbot_chatgpt_legacy_widget_is_safe_redirect( $stripped, $path ) ) {
        $location = $stripped;
    } else {
        $location = $path . '?kognetiks_chatbot_widget=1';
    }

    if ( ! $chatbot_chatgpt_legacy_widget_is_safe_redirect( $location, $path ) ) {
        $location = '../../../../index.php?kognetiks_chatbot_widget=1';
    }

    header( 'Location: ' . $location, true, 302 );
    header( 'X-Robots-Tag: noindex, nofollow', true );
    exit;
}

if ( function_exists( 'chatbot_chatgpt_render_remote_widget' ) ) {
    chatbot_chatgpt_render_remote_widget();
}
exit;
