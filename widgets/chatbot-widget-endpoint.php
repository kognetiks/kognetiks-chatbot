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
    $allowed_keys = array( 'assistant', 'token', 'width', 'height', 'chatbot_prompt' );
    $forward      = array();

    foreach ( $allowed_keys as $key ) {
        if ( ! isset( $_GET[ $key ] ) || ! is_string( $_GET[ $key ] ) ) {
            continue;
        }
        $value = str_replace( array( "\r", "\n", "\0" ), '', $_GET[ $key ] );
        if ( strlen( $value ) > 512 ) {
            $value = substr( $value, 0, 512 );
        }
        $forward[ $key ] = $value;
    }

    $target = '../../../../index.php?kognetiks_chatbot_widget=1';
    if ( ! empty( $forward ) ) {
        $target .= '&' . http_build_query( $forward, '', '&', PHP_QUERY_RFC3986 );
    }

    header( 'Location: ' . $target, true, 302 );
    header( 'X-Robots-Tag: noindex, nofollow', true );
    exit;
}

if ( function_exists( 'chatbot_chatgpt_render_remote_widget' ) ) {
    chatbot_chatgpt_render_remote_widget();
}
exit;
