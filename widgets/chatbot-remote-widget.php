<?php
/**
 * Kognetiks Chatbot - Remote Widget (WordPress-native endpoint) - Ver 2.4.8
 *
 * Serves the remote iframe widget via a query var / rewrite. Authorization is a
 * signed HMAC token bound to an allowlisted host + assistant pair. Referer is
 * logged only. Browsers are locked to the issued host via CSP frame-ancestors.
 *
 * @package chatbot-chatgpt
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Query var used by the remote widget endpoint.
 *
 * @since 2.4.8
 * @return string
 */
function chatbot_chatgpt_remote_widget_query_var() {
    return 'kognetiks_chatbot_widget';
}

/**
 * Pretty permalink path for the remote widget.
 *
 * @since 2.4.8
 * @return string
 */
function chatbot_chatgpt_remote_widget_path() {
    return 'kognetiks-chatbot-widget';
}

/**
 * Option keys for the active AI platform's remote-widget settings.
 *
 * @since 2.4.8
 * @return array{enable:string,domains:string}
 */
function chatbot_chatgpt_get_remote_widget_option_keys() {
    $platform = get_option( 'chatbot_ai_platform_choice', 'OpenAI' );

    if ( 'Azure OpenAI' === $platform ) {
        return array(
            'enable'  => 'chatbot_azure_enable_remote_widget',
            'domains' => 'chatbot_azure_allowed_remote_domains',
        );
    }

    if ( 'Mistral' === $platform ) {
        return array(
            'enable'  => 'chatbot_mistral_enable_remote_widget',
            'domains' => 'chatbot_mistral_allowed_remote_domains',
        );
    }

    return array(
        'enable'  => 'chatbot_chatgpt_enable_remote_widget',
        'domains' => 'chatbot_chatgpt_allowed_remote_domains',
    );
}

/**
 * Whether a host string is safe to use in tokens and CSP sources.
 *
 * @since 2.4.8
 * @param string $host Host[:port].
 * @return bool
 */
function chatbot_chatgpt_is_valid_widget_host( $host ) {
    if ( ! is_string( $host ) || '' === $host || strlen( $host ) > 253 ) {
        return false;
    }

    if ( preg_match( '/[\s\/\\\\#?@\\[\\]]/', $host ) ) {
        return false;
    }

    $is_localhost = (bool) preg_match( '/^localhost(?::[0-9]{1,5})?$/i', $host );
    $is_ipv4      = (bool) preg_match( '/^\d{1,3}(?:\.\d{1,3}){3}(?::[0-9]{1,5})?$/', $host );
    $is_name      = (bool) preg_match( '/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?::[0-9]{1,5})?$/i', $host );

    if ( ! $is_localhost && ! $is_ipv4 && ! $is_name ) {
        return false;
    }

    if ( false !== strpos( $host, ':' ) ) {
        $port = (int) substr( $host, strrpos( $host, ':' ) + 1 );
        if ( $port < 1 || $port > 65535 ) {
            return false;
        }
    }

    return true;
}

/**
 * Whether a remote-widget assistant tag is one of this plugin's chatbot shortcodes.
 *
 * @since 2.4.8
 * @param string $shortcode Shortcode tag without brackets.
 * @return bool
 */
function chatbot_chatgpt_is_remote_widget_shortcode( $shortcode ) {
    $shortcode = strtolower( (string) $shortcode );

    if ( in_array( $shortcode, array( 'chatbot', 'chatbot_chatgpt', 'kognetiks_chatbot' ), true ) ) {
        return true;
    }

    return (bool) preg_match( '/^(chatbot|assistant|agent)-\d+$/', $shortcode );
}

/**
 * Normalize an allowlist host: URL → host, lowercase, strip leading www.
 *
 * Does not collapse to eTLD+1. example.co.uk stays example.co.uk.
 *
 * @since 2.4.8
 * @param string $host Raw host or URL.
 * @return string
 */
function chatbot_chatgpt_normalize_widget_host( $host ) {
    $host = trim( (string) $host );
    if ( '' === $host ) {
        return '';
    }

    if ( preg_match( '#^https?://#i', $host ) ) {
        $parsed = wp_parse_url( $host, PHP_URL_HOST );
        $port   = wp_parse_url( $host, PHP_URL_PORT );
        $host   = is_string( $parsed ) ? $parsed : '';
        if ( '' !== $host && is_int( $port ) && $port > 0 ) {
            $host .= ':' . $port;
        }
    }

    $host = strtolower( $host );
    $stripped = preg_replace( '/^www\./', '', $host );
    $host     = is_string( $stripped ) ? trim( $stripped, '.' ) : '';
    if ( '' === $host ) {
        return '';
    }

    if ( function_exists( 'idn_to_ascii' ) && false === strpos( $host, 'xn--' ) && preg_match( '/[^\x20-\x7E]/', $host ) ) {
        if ( defined( 'INTL_IDNA_VARIANT_UTS46' ) ) {
            $ascii = idn_to_ascii( $host, 0, INTL_IDNA_VARIANT_UTS46 );
        } else {
            $ascii = idn_to_ascii( $host );
        }
        if ( is_string( $ascii ) && '' !== $ascii ) {
            $host = strtolower( $ascii );
        }
    }

    if ( ! chatbot_chatgpt_is_valid_widget_host( $host ) ) {
        return '';
    }

    return $host;
}

/**
 * Parse newline-separated "host,shortcode" allowlist pairs.
 *
 * @since 2.4.8
 * @param string $raw Option value.
 * @return array<int,array{host:string,shortcode:string}>
 */
function chatbot_chatgpt_parse_widget_allowlist_pairs( $raw ) {
    $pairs = array();
    if ( ! is_string( $raw ) || '' === trim( $raw ) ) {
        return $pairs;
    }

    $lines = preg_split( '/\r\n|\r|\n/', $raw );
    if ( ! is_array( $lines ) ) {
        return $pairs;
    }

    foreach ( $lines as $line ) {
        $line = trim( $line );
        if ( '' === $line || false === strpos( $line, ',' ) ) {
            continue;
        }

        $parts = array_map( 'trim', explode( ',', $line, 2 ) );
        if ( 2 !== count( $parts ) ) {
            continue;
        }

        $host      = chatbot_chatgpt_normalize_widget_host( $parts[0] );
        $shortcode = sanitize_text_field( $parts[1] );

        if ( '' === $host || '' === $shortcode || ! preg_match( '/^[a-z0-9_-]+$/i', $shortcode ) ) {
            continue;
        }

        $pairs[] = array(
            'host'      => $host,
            'shortcode' => $shortcode,
        );
    }

    return $pairs;
}

/**
 * HMAC token bound to a normalized host and assistant shortcode.
 *
 * @since 2.4.8
 * @param string $host      Allowlisted host.
 * @param string $assistant Shortcode name.
 * @return string 64-char hex HMAC.
 */
function chatbot_chatgpt_get_widget_embed_token( $host, $assistant ) {
    $host      = chatbot_chatgpt_normalize_widget_host( $host );
    $assistant = sanitize_text_field( $assistant );

    return hash_hmac( 'sha256', $host . "\n" . $assistant, wp_salt( 'auth' ) );
}

/**
 * Public URL for a signed remote-widget embed.
 *
 * @since 2.4.8
 * @param string $host      Allowlisted host.
 * @param string $assistant Shortcode name.
 * @param array  $extra     Extra query args (width, height, chatbot_prompt).
 * @return string
 */
function chatbot_chatgpt_get_remote_widget_url( $host, $assistant, $extra = array() ) {
    $args = array(
        'assistant' => $assistant,
        'token'     => chatbot_chatgpt_get_widget_embed_token( $host, $assistant ),
    );

    if ( ! get_option( 'permalink_structure' ) ) {
        $args[ chatbot_chatgpt_remote_widget_query_var() ] = '1';
        $base = home_url( '/' );
    } else {
        $base = home_url( '/' . chatbot_chatgpt_remote_widget_path() . '/' );
    }

    if ( is_array( $extra ) && ! empty( $extra ) ) {
        $args = array_merge( $args, $extra );
    }

    return add_query_arg( $args, $base );
}

/**
 * CSP frame-ancestors value for a matched allowlist host.
 *
 * @since 2.4.8
 * @param string $host Normalized host.
 * @return string
 */
function chatbot_chatgpt_widget_frame_ancestors( $host ) {
    $host  = chatbot_chatgpt_normalize_widget_host( $host );
    $hosts = array();

    if ( '' !== $host ) {
        $hosts[] = $host;
        $bare    = $host;
        if ( false !== strpos( $host, ':' ) ) {
            $bare = substr( $host, 0, strrpos( $host, ':' ) );
        }
        if ( 'localhost' !== $bare && ! preg_match( '/^\d{1,3}(?:\.\d{1,3}){3}$/', $bare ) ) {
            $hosts[] = 'www.' . $host;
        }
    }

    $origins = array( "'self'" );
    foreach ( $hosts as $candidate ) {
        if ( ! chatbot_chatgpt_is_valid_widget_host( $candidate ) ) {
            continue;
        }
        $origins[] = 'https://' . $candidate;
        $origins[] = 'http://' . $candidate;
    }

    return implode( ' ', array_unique( $origins ) );
}

/**
 * Referer for logging only (not authorization).
 *
 * @since 2.4.8
 * @return string
 */
function chatbot_chatgpt_get_widget_request_referer() {
    if ( empty( $_SERVER['HTTP_REFERER'] ) ) {
        return '';
    }

    return esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
}

/**
 * Register the remote-widget rewrite rule.
 *
 * @since 2.4.8
 * @return void
 */
function chatbot_chatgpt_register_remote_widget_rewrites() {
    add_rewrite_rule(
        '^' . chatbot_chatgpt_remote_widget_path() . '/?$',
        'index.php?' . chatbot_chatgpt_remote_widget_query_var() . '=1',
        'top'
    );
}
add_action( 'init', 'chatbot_chatgpt_register_remote_widget_rewrites', 10 );

/**
 * Expose the remote-widget query var.
 *
 * @since 2.4.8
 * @param string[] $vars Public query vars.
 * @return string[]
 */
function chatbot_chatgpt_register_remote_widget_query_var( $vars ) {
    $vars[] = chatbot_chatgpt_remote_widget_query_var();
    return $vars;
}
add_filter( 'query_vars', 'chatbot_chatgpt_register_remote_widget_query_var' );

/**
 * Flush rewrite rules once after this endpoint is introduced.
 *
 * @since 2.4.8
 * @return void
 */
function chatbot_chatgpt_maybe_flush_remote_widget_rewrites() {
    if ( '1' === get_option( 'chatbot_chatgpt_widget_rewrite_version' ) ) {
        return;
    }

    chatbot_chatgpt_register_remote_widget_rewrites();
    flush_rewrite_rules( false );
    update_option( 'chatbot_chatgpt_widget_rewrite_version', '1' );
}
add_action( 'init', 'chatbot_chatgpt_maybe_flush_remote_widget_rewrites', 99 );
add_action( 'admin_init', 'chatbot_chatgpt_maybe_flush_remote_widget_rewrites' );

/**
 * Deny a remote-widget request.
 *
 * @since 2.4.8
 * @param string $reason  Log reason.
 * @param string $referer Logged referer.
 * @param string $ip      Logged IP.
 * @return void
 */
function chatbot_chatgpt_deny_remote_widget( $reason, $referer = '', $ip = '' ) {
    if ( function_exists( 'chatbot_widget_logging' ) ) {
        chatbot_widget_logging( $reason, $referer, $ip );
    }

    status_header( 403 );
    nocache_headers();
    header( 'X-Robots-Tag: noindex, nofollow', true );
    exit;
}

/**
 * Render the remote widget when the query var is present.
 *
 * @since 2.4.8
 * @return void
 */
function chatbot_chatgpt_maybe_render_remote_widget() {
    if ( is_admin() ) {
        return;
    }

    $flag = get_query_var( chatbot_chatgpt_remote_widget_query_var() );
    if ( empty( $flag ) ) {
        return;
    }

    chatbot_chatgpt_render_remote_widget();
}
add_action( 'template_redirect', 'chatbot_chatgpt_maybe_render_remote_widget', 0 );

/**
 * Authorize and output the remote widget document.
 *
 * @since 2.4.8
 * @return void
 */
function chatbot_chatgpt_render_remote_widget() {
    global $chatbot_chatgpt_plugin_version;
    global $chatbot_chatgpt_plugin_dir_url;
    global $user_id;
    global $session_id;
    global $model;
    global $voice;
    global $kchat_settings;
    global $shortcode_tags;

    $referer = chatbot_chatgpt_get_widget_request_referer();
    $request_ip = function_exists( 'getUserIP' ) ? getUserIP() : '';

    $keys    = chatbot_chatgpt_get_remote_widget_option_keys();
    $enabled = get_option( $keys['enable'], 'No' );

    if ( 'Yes' !== $enabled ) {
        chatbot_chatgpt_deny_remote_widget( 'Remote access is not allowed', $referer, $request_ip );
    }

    if ( function_exists( 'chatbot_widget_logging' ) ) {
        chatbot_widget_logging( 'Remote access is allowed', $referer, $request_ip );
    }

    $shortcode_param = isset( $_GET['assistant'] ) ? sanitize_text_field( wp_unslash( $_GET['assistant'] ) ) : '';
    $token           = isset( $_GET['token'] ) ? strtolower( sanitize_text_field( wp_unslash( $_GET['token'] ) ) ) : '';
    $chatbot_prompt  = isset( $_GET['chatbot_prompt'] ) ? sanitize_text_field( wp_unslash( $_GET['chatbot_prompt'] ) ) : '';

    $allowlist_raw = get_option( $keys['domains'], '' );
    $allowlist_raw = is_string( $allowlist_raw ) ? $allowlist_raw : '';
    $pairs         = chatbot_chatgpt_parse_widget_allowlist_pairs( $allowlist_raw );

    if ( function_exists( 'chatbot_widget_logging' ) ) {
        chatbot_widget_logging( 'Allowed Domain-Assistant Pairs: ' . $allowlist_raw, $referer, $request_ip );
    }

    $matched_host = '';
    $is_allowed   = false;

    if (
        '' !== $shortcode_param
        && preg_match( '/^[a-z0-9_-]+$/i', $shortcode_param )
        && preg_match( '/^[a-f0-9]{64}$/i', $token )
        && ! empty( $pairs )
    ) {
        foreach ( $pairs as $pair ) {
            if ( $pair['shortcode'] !== $shortcode_param ) {
                continue;
            }

            $expected = chatbot_chatgpt_get_widget_embed_token( $pair['host'], $pair['shortcode'] );
            if ( hash_equals( $expected, $token ) ) {
                $is_allowed   = true;
                $matched_host = $pair['host'];
                if ( function_exists( 'chatbot_widget_logging' ) ) {
                    chatbot_widget_logging( 'Allowed Pair', $referer, $shortcode_param );
                }
                break;
            }
        }
    }

    if ( ! $is_allowed || '' === $matched_host ) {
        chatbot_chatgpt_deny_remote_widget( 'Unauthorized Access', $referer, $shortcode_param );
    }

    if (
        ! chatbot_chatgpt_is_remote_widget_shortcode( $shortcode_param )
        || ! is_array( $shortcode_tags )
        || ! array_key_exists( $shortcode_param, $shortcode_tags )
    ) {
        chatbot_chatgpt_deny_remote_widget( 'Invalid shortcode: ' . $shortcode_param, $referer, $request_ip );
    }

    if ( function_exists( 'chatbot_widget_logging' ) ) {
        chatbot_widget_logging( 'Valid shortcode: ' . $shortcode_param, $referer, $request_ip );
    }

    if ( '' !== $chatbot_prompt ) {
        $chatbot_html = do_shortcode( '[' . $shortcode_param . ' chatbot_prompt="' . esc_attr( $chatbot_prompt ) . '"]' );
    } else {
        $chatbot_html = do_shortcode( '[' . $shortcode_param . ']' );
    }

    if ( ! is_array( $kchat_settings ) ) {
        $kchat_settings = array();
    }

    if ( is_user_logged_in() ) {
        $kchat_settings['chatbot_chatgpt_message_limit_setting']        = esc_attr( get_option( 'chatbot_chatgpt_user_message_limit_setting', '999' ) );
        $kchat_settings['chatbot_chatgpt_message_limit_setting_period'] = esc_attr( get_option( 'chatbot_chatgpt_user_message_limit_period_setting', 'Lifetime' ) );
        $kchat_settings['chatbot_chatgpt_display_message_count']        = esc_attr( get_option( 'chatbot_chatgpt_display_message_count', 'No' ) );
    } else {
        $kchat_settings['chatbot_chatgpt_message_limit_setting']        = esc_attr( get_option( 'chatbot_chatgpt_visitor_message_limit_setting', '999' ) );
        $kchat_settings['chatbot_chatgpt_message_limit_setting_period'] = esc_attr( get_option( 'chatbot_chatgpt_visitor_message_limit_period_setting', 'Lifetime' ) );
        $kchat_settings['chatbot_chatgpt_display_message_count']        = esc_attr( get_option( 'chatbot_chatgpt_display_message_count', 'No' ) );
    }

    $page_id = '999999';

    $kchat_settings = array_merge(
        $kchat_settings,
        array(
            'chatbot-chatgpt-version'                        => esc_attr( $chatbot_chatgpt_plugin_version ),
            'plugins_url'                                    => esc_url( $chatbot_chatgpt_plugin_dir_url ),
            'ajax_url'                                       => esc_url( admin_url( 'admin-ajax.php' ) ),
            'user_id'                                        => esc_html( $user_id ),
            'session_id'                                     => esc_html( $session_id ),
            'page_id'                                        => esc_html( $page_id ),
            'model'                                          => esc_html( $model ),
            'voice'                                          => esc_html( $voice ),
            'chatbot_chatgpt_timeout_setting'                => (string) chatbot_chatgpt_get_ajax_timeout_seconds(),
            'chatbot_chatgpt_avatar_icon_setting'            => esc_attr( get_option( 'chatbot_chatgpt_avatar_icon_setting', '' ) ),
            'chatbot_chatgpt_custom_avatar_icon_setting'     => esc_attr( get_option( 'chatbot_chatgpt_custom_avatar_icon_setting', '' ) ),
            'chatbot_chatgpt_avatar_greeting_setting'        => esc_attr( get_option( 'chatbot_chatgpt_avatar_greeting_setting', 'Howdy!!! Great to see you today! How can I help you?' ) ),
            'chatbot_chatgpt_force_page_reload'              => esc_attr( get_option( 'chatbot_chatgpt_force_page_reload', 'No' ) ),
            'chatbot_chatgpt_custom_error_message'           => esc_attr( get_option( 'chatbot_chatgpt_custom_error_message', 'Your custom error message goes here.' ) ),
            'chatbot_chatgpt_start_status'                   => esc_attr( get_option( 'chatbot_chatgpt_start_status', 'closed' ) ),
            'chatbot_chatgpt_start_status_new_visitor'       => esc_attr( get_option( 'chatbot_chatgpt_start_status_new_visitor', 'closed' ) ),
            'nonce_timestamp'                                => time() * 1000,
        ),
        chatbot_chatgpt_get_ajax_nonces()
    );

    $kchat_settings_json = wp_json_encode( $kchat_settings, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );

    $iframe_width  = isset( $_GET['width'] ) ? absint( $_GET['width'] ) : 500;
    $iframe_height = isset( $_GET['height'] ) ? absint( $_GET['height'] ) : 600;
    $iframe_width  = max( 200, min( 1000, $iframe_width ) );
    $iframe_height = max( 200, min( 1000, $iframe_height ) );

    $chatbot_widget_width  = ( $iframe_width - 20 ) . 'px';
    $chatbot_widget_height = ( $iframe_height - 20 ) . 'px';

    $ancestors = chatbot_chatgpt_widget_frame_ancestors( $matched_host );

    status_header( 200 );
    nocache_headers();
    header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ), true );
    header( 'X-Robots-Tag: noindex, nofollow', true );
    header( 'Content-Security-Policy: frame-ancestors ' . $ancestors, true );

    ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <?php wp_head(); ?>
    <style>
        body, html {
            background: transparent !important;
        }
        .chatbot-wrapper {
            width: <?php echo esc_attr( $chatbot_widget_width ); ?>;
            max-width: 1000px;
            margin: 0 auto;
            height: <?php echo esc_attr( $chatbot_widget_height ); ?>;
            max-height: 1000px;
            overflow: hidden;
            position: fixed;
            bottom: 10px;
            right: 10px;
            padding: 25px;
            background: transparent;
            z-index: 9999;
        }
        #chatbot-chatgpt {
            height: 1px;
            width: 1px;
        }
        .chatbot-wide {
            height: 55vh !important;
        }
    </style>
</head>
<body>
    <div class="chatbot-wrapper">
        <?php
        // Shortcode output includes required inline scripts. wp_kses_post() strips
        // <script> and leaves the JS as visible text on the page.
        echo $chatbot_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted do_shortcode() of an allowlisted registered shortcode
        ?>
    </div>
    <?php wp_footer(); ?>
    <script type="text/javascript">
        var kchat_settings = <?php echo $kchat_settings_json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON from wp_json_encode for JavaScript ?>;
        localStorage.setItem('chatbot_chatgpt_opened', 'true');
        localStorage.setItem('chatbot_chatgpt_start_status', 'open');
        localStorage.setItem('chatbot_chatgpt_start_status_new_visitor', 'open');
    </script>
</body>
</html>
    <?php
    exit;
}

/**
 * Admin iframe snippets for each allowlisted domain/assistant pair.
 *
 * @since 2.4.8
 * @param string $raw_allowlist Option value.
 * @return void
 */
function chatbot_chatgpt_render_remote_widget_embed_snippets( $raw_allowlist ) {
    $pairs = chatbot_chatgpt_parse_widget_allowlist_pairs( $raw_allowlist );

    echo '<p class="description">Each pair needs a signed token. Copy the iframe onto the remote site, or open the test URL in a new tab on this machine.</p>';
    echo '<p class="description"><strong>Localhost testing:</strong> Do not paste <code>&amp;#038;</code> into the address bar — that is HTML for <code>&amp;</code>. Open the test URL instead. An iframe on this same WordPress site is allowed. A <code>file://</code> HTML file or a different port is blocked by <code>frame-ancestors</code>.</p>';

    if ( empty( $pairs ) ) {
        echo '<p class="description">Save at least one <code>domain.com,chatbot-1</code> pair (one per line) to generate embed snippets.</p>';
        return;
    }

    $base = chatbot_chatgpt_get_remote_widget_url( $pairs[0]['host'], $pairs[0]['shortcode'] );
    echo '<p class="description">Widget URL pattern: <code>' . esc_html( strtok( $base, '?' ) ) . '</code></p>';

    foreach ( $pairs as $pair ) {
        $url = chatbot_chatgpt_get_remote_widget_url(
            $pair['host'],
            $pair['shortcode'],
            array(
                'width'  => 500,
                'height' => 600,
            )
        );
        $safe_url = esc_url( $url, array( 'http', 'https' ), 'db' );
        $snippet  = '<iframe src="' . $safe_url . '" width="500" height="600" style="border:0;position:fixed;bottom:10px;right:10px;z-index:9999;" title="Chatbot"></iframe>';

        echo '<p><strong>' . esc_html( $pair['host'] . ',' . $pair['shortcode'] ) . '</strong></p>';
        echo '<p class="description">Test URL: <a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $url ) . '</a></p>';
        echo '<textarea readonly rows="4" cols="80" class="large-text code">' . esc_textarea( $snippet ) . '</textarea>';
    }
}
