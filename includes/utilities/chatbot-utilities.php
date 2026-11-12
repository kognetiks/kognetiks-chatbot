<?php
/**
 * Kognetiks Chatbot - Utilities - Ver 1.8.1
 *
 * This file contains the code for plugin utilities.
 * It is used to check for mobile devices and other utilities.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// Check for device type
function is_mobile_device() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $mobile_agents = array('Mobile', 'Android', 'Silk/', 'Kindle', 'BlackBerry', 'Opera Mini', 'Opera Mobi', 'iPhone', 'iPad', 'iPod', 'Windows Phone', 'webOS', 'Symbian', 'IEMobile');

    foreach ($mobile_agents as $device) {
        if (strpos($user_agent, $device) !== false) {
            return true; // Mobile device detected
        }
    }

    return false; // Not a mobile device

}

// Function to create a directory and an index.php file
function create_directory_and_index_file($dir_path) {
    // Ensure the directory ends with a slash
    $dir_path = rtrim($dir_path, '/') . '/';

    // Check if the directory exists, if not create it
    if (!file_exists($dir_path) && !wp_mkdir_p($dir_path)) {
        // Error handling, e.g., log the error or handle the failure appropriately
        return false;
    }

    // Path for the index.php file
    $index_file_path = $dir_path . 'index.php';

    // Check if the index.php file exists, if not create it
    if (!file_exists($index_file_path)) {
        $file_content = "<?php\n// Silence is golden.\n\n";
        file_put_contents($index_file_path, $file_content);
    }

    chatbot_chatgpt_write_no_script_htaccess($dir_path);
    chatbot_chatgpt_write_private_log_htaccess($dir_path);

    // Set directory permissions
    global $wp_filesystem;
    if (!function_exists('WP_Filesystem')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    if (empty($wp_filesystem)) {
        WP_Filesystem();
    }
    if ($wp_filesystem) {
        $wp_filesystem->chmod($dir_path, 0755);
    }

    return true;

}

/**
 * Directories that must stay web-readable for downloads and audio, and must not run scripts.
 *
 * @return string[]
 */
function chatbot_chatgpt_static_directory_names() {
    return array( 'uploads', 'downloads', 'audio', 'transcripts' );
}

/**
 * index.php and .htaccess must survive hourly cleanup.
 *
 * @param string $path File path.
 * @return bool
 */
function chatbot_chatgpt_is_static_guard_file( $path ) {
    $base = strtolower( wp_basename( (string) $path ) );
    return ( 'index.php' === $base || '.htaccess' === $base );
}

/**
 * Apache rules that deny script execution. Nginx does not read .htaccess; the comment states the equivalent.
 *
 * @return string
 */
function chatbot_chatgpt_no_script_htaccess_rules() {
    return <<<'HTA'
# chatbot-static-deny v1
# Do not execute scripts in this directory. index.php remains so listings stay blocked.
# nginx does not read this file. Equivalent location:
# location ~* /wp-content/plugins/chatbot-chatgpt/(uploads|downloads|audio|transcripts)/.*\.(php|phtml|pht|phar|phps|cgi|pl|asp|aspx|shtml)$ { deny all; }
Options -Indexes -ExecCGI

<IfModule mod_authz_core.c>
    <FilesMatch "(?i)\.(php|php\d+|phtml|pht|phar|phps|cgi|pl|asp|aspx|shtml)$">
        SetHandler default-handler
        Require all denied
    </FilesMatch>
</IfModule>

<IfModule php_module>
    php_flag engine off
</IfModule>
<IfModule php7_module>
    php_flag engine off
</IfModule>
<IfModule php8_module>
    php_flag engine off
</IfModule>
<IfModule mod_php.c>
    php_flag engine off
</IfModule>
<IfModule mod_php7.c>
    php_flag engine off
</IfModule>
<IfModule mod_php8.c>
    php_flag engine off
</IfModule>

<IfModule mod_mime.c>
    RemoveHandler .php .php3 .php4 .php5 .php7 .php8 .phtml .pht .phar .phps
    RemoveType .php .php3 .php4 .php5 .php7 .php8 .phtml .pht .phar .phps
</IfModule>
HTA;
}

/**
 * Write the no-script .htaccess into a static directory when it is one of the four public folders.
 *
 * @param string $dir_path Directory path.
 * @return void
 */
function chatbot_chatgpt_write_no_script_htaccess( $dir_path ) {
    $normalized = rtrim( str_replace( '\\', '/', (string) $dir_path ), '/' );
    $base       = basename( $normalized );
    if ( ! in_array( $base, chatbot_chatgpt_static_directory_names(), true ) ) {
        return;
    }

    $path  = $normalized . '/.htaccess';
    $rules = chatbot_chatgpt_no_script_htaccess_rules();
    if ( file_exists( $path ) ) {
        $current = file_get_contents( $path );
        if ( is_string( $current ) && false !== strpos( $current, 'chatbot-static-deny v1' ) ) {
            return;
        }
    }

    file_put_contents( $path, $rules );
}

/**
 * Ensure the four public directories exist, block listings, and refuse script execution.
 *
 * @return void
 */
function chatbot_chatgpt_harden_static_directories() {
    global $chatbot_chatgpt_plugin_dir_path;

    if ( empty( $chatbot_chatgpt_plugin_dir_path ) ) {
        return;
    }

    $names = array_merge( chatbot_chatgpt_static_directory_names(), chatbot_chatgpt_private_log_directory_names() );
    foreach ( $names as $name ) {
        create_directory_and_index_file( $chatbot_chatgpt_plugin_dir_path . $name . '/' );
    }
}

/**
 * Diagnostic directories. Files in these folders are not meant to be downloaded.
 *
 * @return string[]
 */
function chatbot_chatgpt_private_log_directory_names() {
    return array( 'chatbot-logs', 'widget-logs' );
}

/**
 * Deny every web request for a private log directory.
 * Nginx does not read .htaccess. The comment states the equivalent rule.
 *
 * @return string
 */
function chatbot_chatgpt_private_log_htaccess_rules() {
    return <<<'HTA'
# chatbot-private-log-deny v1
# Diagnostic logs are not public. Nginx does not read this file. Equivalent:
# location ^~ /wp-content/plugins/chatbot-chatgpt/chatbot-logs/ { deny all; }
# location ^~ /wp-content/plugins/chatbot-chatgpt/widget-logs/ { deny all; }
Options -Indexes
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
    Deny from all
</IfModule>
HTA;
}

/**
 * Write the deny-all rule into chatbot-logs or widget-logs.
 *
 * @param string $dir_path Directory path.
 * @return void
 */
function chatbot_chatgpt_write_private_log_htaccess( $dir_path ) {
    $normalized = rtrim( str_replace( '\\', '/', (string) $dir_path ), '/' );
    $base       = basename( $normalized );
    if ( ! in_array( $base, chatbot_chatgpt_private_log_directory_names(), true ) ) {
        return;
    }

    $path  = $normalized . '/.htaccess';
    $rules = chatbot_chatgpt_private_log_htaccess_rules();
    if ( file_exists( $path ) ) {
        $current = file_get_contents( $path );
        if ( is_string( $current ) && false !== strpos( $current, 'chatbot-private-log-deny v1' ) ) {
            return;
        }
    }

    file_put_contents( $path, $rules );
}

/**
 * Stable token for a log line. The raw address is not written to disk.
 *
 * @param mixed $ip Address or another log field.
 * @return string
 */
function chatbot_chatgpt_redact_ip_for_log( $ip ) {
    $ip = is_string( $ip ) ? trim( $ip ) : '';
    if ( $ip === '' || $ip === 'unknown' ) {
        return 'unknown';
    }
    if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
        return sanitize_text_field( $ip );
    }
    if ( ! function_exists( 'wp_salt' ) ) {
        return 'redacted';
    }
    return 'ip_' . substr( hash_hmac( 'sha256', 'kchat-ip-v1|' . $ip, wp_salt( 'auth' ) ), 0, 16 );
}
add_action( 'init', 'chatbot_chatgpt_harden_static_directories' );

/**
 * MIME type from file contents.
 *
 * @param string $path Absolute path.
 * @return string Empty when fileinfo cannot read the file.
 */
function chatbot_chatgpt_finfo_mime( $path ) {
    if ( ! is_string( $path ) || ! is_readable( $path ) || ! function_exists( 'finfo_open' ) ) {
        return '';
    }

    $finfo = finfo_open( FILEINFO_MIME_TYPE );
    if ( false === $finfo ) {
        return '';
    }

    $mime = finfo_file( $finfo, $path );
    if ( PHP_VERSION_ID < 80100 ) {
        finfo_close( $finfo );
    }

    if ( ! is_string( $mime ) ) {
        return '';
    }

    $mime = strtolower( trim( $mime ) );
    $semi = strpos( $mime, ';' );
    if ( false !== $semi ) {
        $mime = trim( substr( $mime, 0, $semi ) );
    }

    return $mime;
}

/**
 * Move or rename a file using the WordPress filesystem API.
 *
 * @param string $source      Absolute source path.
 * @param string $destination Absolute destination path.
 * @param bool   $overwrite   Whether to overwrite an existing destination. Default true.
 * @return bool True on success, false on failure.
 */
function chatbot_chatgpt_move_file( $source, $destination, $overwrite = true ) {

    global $wp_filesystem;

    if ( ! function_exists( 'WP_Filesystem' ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    if ( empty( $wp_filesystem ) ) {
        WP_Filesystem();
    }

    if ( ! $wp_filesystem ) {
        return false;
    }

    return (bool) $wp_filesystem->move( $source, $destination, $overwrite );

}

/**
 * GPT-5 and later, and the o-series, are reasoning chat models.
 *
 * Chat Completions deprecates max_tokens in favor of max_completion_tokens and
 * says max_tokens is not compatible with o-series models. GPT-5, GPT-6, and
 * later reasoning models reject max_tokens the same way. When reasoning effort
 * is not none, those models also reject temperature and top_p. These requests
 * do not set reasoning_effort, so the API default applies.
 *
 * GPT-4, GPT-4.1, and GPT-4o still accept max_tokens, temperature, and top_p.
 *
 * @param string $model The model name.
 * @return bool
 */
function chatbot_openai_is_reasoning_chat_model( $model ) {

    $model = strtolower( trim( (string) $model ) );
    if ( '' === $model ) {
        return false;
    }

    // gpt-5, gpt-5.6-terra, gpt-6-luna, gpt-6.1-sol, and later generations.
    if ( 1 === preg_match( '/^gpt-(?:[5-9]|[1-9]\d)(?:[.]|-|$)/', $model ) ) {
        return true;
    }

    // o1, o3, o4-mini, and later o-series ids.
    if ( 1 === preg_match( '/^o\d+(?:[.]|-|$)/', $model ) ) {
        return true;
    }

    return false;

}

/**
 * Check if a model requires max_completion_tokens instead of max_tokens.
 *
 * @param string $model The model name.
 * @return bool True if model requires max_completion_tokens, false otherwise.
 */
function chatbot_openai_requires_max_completion_tokens( $model ) {

    return chatbot_openai_is_reasoning_chat_model( $model );

}

/**
 * Check if a model doesn't support temperature and top_p parameters.
 *
 * @param string $model The model name.
 * @return bool True if model doesn't support temperature/top_p, false otherwise.
 */
function chatbot_openai_doesnt_support_temperature( $model ) {

    return chatbot_openai_is_reasoning_chat_model( $model );

}
