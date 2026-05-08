<?php
/**
 * Kognetiks Chatbot - Transformer - Settings - Ver 2.1.6.1
 *
 * This file contains the code for the Transformer settings page.
 * It manages the settings and other parameters.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

/**
 * WP-Cron hook for LCM full lexical cache rebuild (PMI + IDF). Single and recurring events use different args.
 *
 * @return string
 */
function chatbot_lcm_lexical_cache_rebuild_cron_hook() {
    return 'chatbot_transformer_model_lexical_cache_rebuild_cron';
}

/**
 * Register a ~30-day interval for monthly LCM rebuild scheduling (WordPress has no built-in monthly preset).
 *
 * @param array<string, array<string, int|string>> $schedules Cron schedules.
 * @return array<string, array<string, int|string>>
 */
function chatbot_lcm_lexical_cache_register_monthly_cron_schedule( $schedules ) {

    if ( ! isset( $schedules['monthly'] ) ) {
        $schedules['monthly'] = array(
            'interval' => 30 * DAY_IN_SECONDS,
            'display'  => __( 'Once Monthly (approx. 30 days)', 'chatbot-chatgpt' ),
        );
    }

    return $schedules;
}
add_filter( 'cron_schedules', 'chatbot_lcm_lexical_cache_register_monthly_cron_schedule' );

/**
 * Remove only recurring LCM lexical rebuild events (does not cancel one-off rebuilds from the admin button or "Now").
 *
 * @return void
 */
function chatbot_lcm_lexical_cache_clear_recurring_rebuild_events() {

    wp_clear_scheduled_hook( chatbot_lcm_lexical_cache_rebuild_cron_hook(), array( 'recurring' ) );
}

/**
 * Whether a one-off cron job is already queued for the LCM rebuild hook (legacy empty args or marker "once").
 *
 * @return bool
 */
function chatbot_lcm_lexical_cache_pending_one_shot_scheduled() {

    $hook = chatbot_lcm_lexical_cache_rebuild_cron_hook();
    // Marker used for one-off jobs from this UI or the Delete & Rebuild button (large corpus).
    if ( wp_next_scheduled( $hook, array( 'once' ) ) ) {
        return true;
    }
    // Legacy: single events scheduled before cron args were added (empty args — recurring uses array( 'recurring' ) only).
    return (bool) wp_next_scheduled( $hook, array() );
}

/**
 * Apply Lexical Cache Rebuild Schedule option (called from sanitize). Does not change PMI math.
 *
 * @param string $value One of Never|Now|Daily|Weekly|Monthly.
 * @return void
 */
function chatbot_lcm_lexical_cache_apply_rebuild_schedule( $value ) {

    $hook = chatbot_lcm_lexical_cache_rebuild_cron_hook();

    switch ( $value ) {
        case 'Never':
            chatbot_lcm_lexical_cache_clear_recurring_rebuild_events();
            return;

        case 'Now':
            if ( ! chatbot_lcm_lexical_cache_pending_one_shot_scheduled() ) {
                wp_schedule_single_event( time() + 10, $hook, array( 'once' ) );
                set_transient( 'chatbot_lexical_rebuild_job_pending', 1, 2 * HOUR_IN_SECONDS );
                update_option( 'chatbot_lcm_lexical_cache_rebuild_last_status', 'running', false );
                update_option( 'chatbot_lcm_lexical_cache_rebuild_last_time', time(), false );
            }
            if ( function_exists( 'transformer_model_lexical_context_lexical_rebuild_log' ) ) {
                transformer_model_lexical_context_lexical_rebuild_log( 'schedule_ui=Now queued one-off wp-cron' );
            }
            return;

        case 'Daily':
        case 'Weekly':
        case 'Monthly':
            chatbot_lcm_lexical_cache_clear_recurring_rebuild_events();
            $interval_map = array(
                'Daily'   => 'daily',
                'Weekly'  => 'weekly',
                'Monthly' => 'monthly',
            );
            $interval    = $interval_map[ $value ];
            $first_run_at = time() + 60;
            wp_schedule_event( $first_run_at, $interval, $hook, array( 'recurring' ) );
            update_option( 'chatbot_lcm_lexical_cache_rebuild_last_status', 'scheduled', false );
            update_option( 'chatbot_lcm_lexical_cache_rebuild_last_time', time(), false );
            return;

        default:
            return;
    }
}

/**
 * Sanitize Lexical Cache Rebuild Schedule (strict allow-list).
 *
 * @param mixed $value Raw submitted value.
 * @return string Never|Daily|Weekly|Monthly (Never after processing Now).
 */
function chatbot_lcm_lexical_cache_rebuild_schedule_sanitize( $value ) {

    $allowed = array( 'Never', 'Now', 'Daily', 'Weekly', 'Monthly' );
    $value   = is_string( $value ) ? $value : '';
    if ( ! in_array( $value, $allowed, true ) ) {
        return 'Never';
    }

    chatbot_lcm_lexical_cache_apply_rebuild_schedule( $value );

    if ( 'Now' === $value ) {
        return 'Never';
    }

    return $value;
}

/**
 * Shows lexical rebuild worker heartbeat + stalled warnings while a background job is pending.
 *
 * @return void
 */
function chatbot_lcm_lexical_echo_rebuild_worker_panel() {

    if ( ! function_exists( 'chatbot_lcm_lexical_cache_rebuild_cron_hook' ) ) {
        return;
    }

    $hook = chatbot_lcm_lexical_cache_rebuild_cron_hook();

    $next_once      = wp_next_scheduled( $hook, array( 'once' ) );
    $next_legacy    = wp_next_scheduled( $hook, array() );
    $pending        = (bool) get_transient( 'chatbot_lexical_rebuild_job_pending' ) || (bool) $next_once || (bool) $next_legacy;

    if ( ! $pending ) {
        return;
    }

    $activity   = get_option( 'chatbot_lcm_lexical_rebuild_activity', array() );
    if ( ! is_array( $activity ) ) {
        $activity = array();
    }
    $ts         = isset( $activity['ts'] ) ? (int) $activity['ts'] : 0;
    $enqueue_ts = (int) get_option( 'chatbot_lcm_lexical_cache_rebuild_last_time', 0 );
    $running_lbl = get_option( 'chatbot_lcm_lexical_cache_rebuild_last_status', '' );
    $running_lbl = is_string( $running_lbl ) ? $running_lbl : '';

    $stale_secs = max( 60, (int) apply_filters( 'chatbot_lexical_rebuild_stale_after_seconds', 330 ) );
    $no_hb_secs = max( 60, (int) apply_filters( 'chatbot_lexical_rebuild_no_heartbeat_seconds', 210 ) );

    $tz       = wp_timezone();
    $date_fmt = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
    $fmt      = static function ( $unix ) use ( $tz, $date_fmt ) {
        if ( $unix <= 0 ) {
            return '';
        }

        return wp_date( $date_fmt, $unix, $tz );
    };

    ?>
    <div style="border: 1px solid #c3c4c7; padding: 10px 12px; margin: 10px 0; max-width: 720px; background: #f6f7f7;">
        <p style="margin: 0 0 8px 0;"><strong><?php esc_html_e( 'Background lexical rebuild worker', 'chatbot-chatgpt' ); ?></strong></p>
        <?php if ( $ts > 0 ) : ?>
            <p style="margin: 4px 0;">
                <?php
                printf(
                    esc_html__( 'Last heartbeat: %1$s (%2$s)', 'chatbot-chatgpt' ),
                    esc_html( $fmt( $ts ) ),
                    esc_html( isset( $activity['step'] ) ? (string) $activity['step'] : '?' )
                );
                ?>
                <?php if ( isset( $activity['context'] ) && is_string( $activity['context'] ) && $activity['context'] !== '' ) : ?>
                    <span style="color:#646970;"><?php echo esc_html( '[' . $activity['context'] . ']' ); ?></span>
                <?php endif; ?>
            </p>
            <p style="margin: 4px 0; font-size: 12px;">
                <?php
                echo esc_html(
                    sprintf(
                        __( 'Pipeline: stage=%s offset=%s N_docs=%s / rows=%s', 'chatbot-chatgpt' ),
                        isset( $activity['stage'] ) ? (string) $activity['stage'] : '?',
                        isset( $activity['offset'] ) ? (string) (int) $activity['offset'] : '?',
                        isset( $activity['N_docs'] ) ? (string) (int) $activity['N_docs'] : '?',
                        isset( $activity['total_rows'] ) ? (string) (int) $activity['total_rows'] : '?'
                    )
                );
                ?>
            </p>
        <?php elseif ( $enqueue_ts > 0 && 'running' === $running_lbl ) : ?>
            <p style="margin: 4px 0;"><?php esc_html_e( 'No worker heartbeat captured yet — the queue may still be waiting for WP-Cron or the first PHP slice.', 'chatbot-chatgpt' ); ?></p>
        <?php else : ?>
            <p style="margin: 4px 0;"><?php esc_html_e( 'A rebuild appears pending, but activity details are unavailable. Check the plugin error log for [LCM][rebuild] lines.', 'chatbot-chatgpt' ); ?></p>
        <?php endif; ?>

        <?php if ( $ts > 0 && ( time() - $ts ) > $stale_secs ) : ?>
            <div class="notice notice-warning inline" style="margin:10px 0 0;"><p><?php esc_html_e( 'No heartbeat for several minutes — the worker may have been killed by Apache/FastCGI (idle timeout ~30s on MAMP), or PHP ran out of memory. Use “Run One Rebuild Step Now” after waiting for the lock, clear the stalled job if needed, then check Apache / PHP / chatbot error logs.', 'chatbot-chatgpt' ); ?></div>
        <?php elseif ( $ts <= 0 && $enqueue_ts > 0 && ( time() - $enqueue_ts ) > $no_hb_secs && 'running' === $running_lbl ) : ?>
            <div class="notice notice-warning inline" style="margin:10px 0 0;"><p><?php esc_html_e( 'Still no first heartbeat — WP-Cron may not be firing locally. Load the front-end once or click “Run One Rebuild Step Now”.', 'chatbot-chatgpt' ); ?></div>
        <?php elseif ( $ts > 0 ) : ?>
            <p style="margin:8px 0 0; font-size:12px;"><em><?php esc_html_e( 'As long as the heartbeat time advances when you refresh this screen, work is progressing.', 'chatbot-chatgpt' ); ?></em></p>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Section intro + status for LCM lexical cache WP-Cron scheduling.
 *
 * @param mixed $args Section args.
 * @return void
 */
function chatbot_lcm_lexical_cache_schedule_section_callback( $args ) {

    ?>
    <p><?php echo esc_html__( 'Automate full PMI + IDF lexical cache rebuilds on a schedule (same rebuild as the button, via WP-Cron). Large sites should rely on this or the button’s background queue instead of waiting on a long browser request.', 'chatbot-chatgpt' ); ?></p>
    <?php

    $hook          = chatbot_lcm_lexical_cache_rebuild_cron_hook();
    $saved         = get_option( 'chatbot_lcm_lexical_cache_rebuild_schedule', 'Never' );
    $allowed_saved = array( 'Never', 'Daily', 'Weekly', 'Monthly' );
    if ( ! in_array( $saved, $allowed_saved, true ) ) {
        $saved = 'Never';
    }

    $next_recurring = wp_next_scheduled( $hook, array( 'recurring' ) );
    $next_once      = wp_next_scheduled( $hook, array( 'once' ) );
    $next_legacy    = wp_next_scheduled( $hook, array() );
    $last_ts        = (int) get_option( 'chatbot_lcm_lexical_cache_rebuild_last_time', 0 );
    $last_status    = get_option( 'chatbot_lcm_lexical_cache_rebuild_last_status', '' );
    if ( ! is_string( $last_status ) ) {
        $last_status = '';
    }
    $last_error = get_option( 'chatbot_lcm_lexical_cache_rebuild_last_error', '' );
    if ( ! is_string( $last_error ) ) {
        $last_error = '';
    }
    $is_pending = (bool) get_transient( 'chatbot_lexical_rebuild_job_pending' ) || (bool) $next_once || (bool) $next_legacy;

    $tz = wp_timezone();
    $fmt_next_recurring = $next_recurring ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next_recurring, $tz ) : '';
    $fmt_next_once      = $next_once ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next_once, $tz ) : '';
    $fmt_last           = $last_ts ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $last_ts, $tz ) : '';

    $status_label = '';
    if ( $is_pending ) {
        $status_label = __( 'In progress', 'chatbot-chatgpt' );
    } elseif ( 'success' === $last_status ) {
        $status_label = __( 'Success', 'chatbot-chatgpt' );
    } elseif ( 'failed' === $last_status ) {
        $status_label = __( 'Failed', 'chatbot-chatgpt' );
    }

    ?>
    <div style="background:#fff;border:1px solid #c3c4c7;padding:12px;max-width:720px;margin:10px 0;">
        <p><strong><?php echo esc_html__( 'Saved schedule', 'chatbot-chatgpt' ); ?>:</strong> <?php echo esc_html( $saved ); ?></p>
        <p><strong><?php echo esc_html__( 'Next recurring rebuild (WP-Cron)', 'chatbot-chatgpt' ); ?>:</strong>
            <?php echo $next_recurring ? esc_html( $fmt_next_recurring ) : esc_html__( 'Not scheduled', 'chatbot-chatgpt' ); ?></p>
        <p><strong><?php echo esc_html__( 'Next one-off rebuild (if queued)', 'chatbot-chatgpt' ); ?>:</strong>
            <?php echo $next_once ? esc_html( $fmt_next_once ) : esc_html__( 'None', 'chatbot-chatgpt' ); ?></p>
        <p><strong><?php echo esc_html__( 'Last completed rebuild job', 'chatbot-chatgpt' ); ?>:</strong>
            <?php
            if ( $fmt_last ) {
                $suffix = ( $status_label !== '' ) ? ( ' — ' . $status_label ) : '';
                echo esc_html( $fmt_last . $suffix );
            } else {
                echo esc_html__( 'No completed rebuild job recorded yet.', 'chatbot-chatgpt' );
            }
            ?>
        </p>
        <?php if ( $status_label === __( 'Failed', 'chatbot-chatgpt' ) && $last_error !== '' ) : ?>
            <p style="margin:0;"><em><?php echo esc_html__( 'Last failure reason', 'chatbot-chatgpt' ); ?>: <?php echo esc_html( $last_error ); ?></em></p>
        <?php endif; ?>
        <?php if ( $is_pending ) : ?>
            <p style="margin:0;"><em><?php echo esc_html__( 'A rebuild is currently queued/running. The “Success/Failed” status shown above reflects the last completed job, not the current run.', 'chatbot-chatgpt' ); ?></em></p>
        <?php endif; ?>
    </div>
    <?php
    chatbot_lcm_lexical_echo_rebuild_worker_panel();
}

/**
 * Dropdown: Lexical Cache Rebuild Schedule.
 *
 * @param mixed $args Field args.
 * @return void
 */
function chatbot_lcm_lexical_cache_rebuild_schedule_callback( $args ) {

    $current = get_option( 'chatbot_lcm_lexical_cache_rebuild_schedule', 'Never' );
    $allowed = array( 'Never', 'Now', 'Daily', 'Weekly', 'Monthly' );
    if ( ! in_array( $current, $allowed, true ) ) {
        $current = 'Never';
    }

    $labels = array(
        'Never'   => __( 'Never', 'chatbot-chatgpt' ),
        'Now'     => __( 'Now (one rebuild, then reset to Never)', 'chatbot-chatgpt' ),
        'Daily'   => __( 'Daily', 'chatbot-chatgpt' ),
        'Weekly'  => __( 'Weekly', 'chatbot-chatgpt' ),
        'Monthly' => __( 'Monthly', 'chatbot-chatgpt' ),
    );
    ?>
    <select id="chatbot_lcm_lexical_cache_rebuild_schedule" name="chatbot_lcm_lexical_cache_rebuild_schedule">
        <?php foreach ( $labels as $val => $label ) : ?>
            <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $current, $val ); ?>><?php echo esc_html( $label ); ?></option>
        <?php endforeach; ?>
    </select>
    <p class="description"><?php echo esc_html__( 'Never removes only recurring jobs; one-off rebuilds already queued are left intact. Logs use prefix [LCM][rebuild] with context=wp_cron.', 'chatbot-chatgpt' ); ?></p>
    <?php
}

// Transformer Options Callback - Ver 2.1.6
function chatbot_transformer_model_settings_section_callback($args) {

    // See if the scanner needs to run
    // $results = chatbot_transformer_model_build_results_callback(esc_attr(get_option('chatbot_transformer_model_build_schedule')));
    $results = chatbot_transformer_model_scheduler();

    ?>
    <p>Configure the settings for the plugin when using transformer models. Some example shortcodes include:</p>
    <ul style="list-style-type: disc; list-style-position: inside; padding-left: 1em;">
        <!-- <li><code>&#91;chatbot style="floating" model="lexical-context-model"&#93;</code> - Style is floating, specific model</li> -->
        <!-- <li><code>&#91;chatbot style="embedded" model="lexical-context-model"&#93;</code> - Style is embedded, specific model</li> -->
        <li><code>&#91;chatbot style="floating" model="sentential-context-model"&#93;</code> - Style is floating, specific model</li>
        <li><code>&#91;chatbot style="embedded" model="sentential-context-model"&#93;</code> - Style is embedded, specific model</li>
    </ul>
    <!-- <p>A Transformer Model generates text using a local algorithm based on the <a href="https://en.wikipedia.org/wiki/Transformer_(deep_learning_architecture)" target="_blank" rel="noopener noreferrer">deep learning architecture</a>, a concept developed by researchers at Google and based on the multi-head attention mechanism proposed in a 2017 paper titled 'Attention Is All You Need'. The transformer-inspired models included here are trained on your site's published content, including pages and posts. These models run locally on your server and are not available on the OpenAI platform. Although these models may not match the sophistication of OpenAI's offerings and might occasionally generate nonsensical output, they can still be effective, especially when your site contains a large amount of content.</p>  -->
    <p><strong>Transformer-Inspired Models</strong>: The transformer-inspired models (below) in this plugin generate text using local algorithms inspired by the <a href="https://en.wikipedia.org/wiki/Transformer_(deep_learning_architecture)" target="_blank" rel="noopener noreferrer">Transformer deep learning architecture</a>, a concept developed by researchers at Google in their 2017 paper "Attention Is All You Need". While these models do not implement the full transformer architecture, they utilize similar principles, such as word embeddings and context analysis, to generate responses based on your site's content. They run locally on your server, providing privacy and control over the data. Although less advanced than models like those provided by OpenAI, NVIDIA, Anthropic, DeepSeek, Google or Mistral and may sometimes produce nonsensical output, they can be effective, especially when your site contains a substantial amount of content.</p>
    <p><strong>The Sentential Context Model (SCM) is a sentence-based model.</strong> The SCM operates at the level of entire sentences, analyzing the structure and meaning of sentences to generate coherent and contextually relevant responses. By comparing input sentences with sentences from your WordPress content, the SCM selects the most appropriate responses based on sentence-level similarity. This approach allows the chatbot to provide more comprehensive and context-aware replies, enhancing user interactions with more natural and meaningful conversations. <strong>When to use SCM</strong>: Ideal for generating more comprehensive and context-aware responses, particularly when conversational flow and coherence are important.</p>
    <!-- <p><strong>The Lexical Context Model (LCM) is a word-based model. (COMING SOON)</strong> The LCM focuses on individual words and their relationships within the text. It utilizes word embeddings derived from co-occurrence matrices to understand the context in which words appear. By analyzing word-level similarities between the user's input and the content from your WordPress site, the LCM generates responses that are relevant based on specific keywords and phrases. This model is effective for generating quick and pertinent answers by leveraging word-level context. <strong>When to use LCM</strong>: Best suited for quick, keyword-focused answers where speed and relevance to specific terms are prioritized.</p> -->
    <p><strong>Privacy Advantage</strong>: The tranformer models process data locally on your server, ensuring that user interactions and site content are not sent to external services. This enhances privacy and allows you to maintain control over your data.</p>
    <p><strong>NOTE</strong>: Currently in beta, the Transformer-Inspired Models are under active development and may occasionally produce nonsensical or irrelevant responses. We are continuously improving the models to enhance their performance and accuracy.</p>
    <p style="background-color: #e0f7fa; padding: 10px;"><b>For an explanation on how to use the Transformer API and additional documentation please click <a href="?page=chatbot-chatgpt&tab=support&dir=api-transformer-settings&file=api-transformer-model-settings.md">here</a>.</b></p>
    <?php
}

function chatbot_transformer_model_api_model_general_section_callback($args){
    ?>
    <p>Configure the settings for the plugin when using transformer models.  Depending on the transformer model you choose, the maximum tokens may be as high as 10000.  The default is 500.</p>
    <?php
}

function chatbot_transformer_model_cache_info_callback($args) {

    $model_choice = esc_attr(get_option('chatbot_transformer_model_choice', 'sentential-context-model'));

    if ($model_choice !== 'lexical-context-model') {
        echo '<p>This section becomes available when the <strong>Lexical Context Model</strong> is selected.</p>';
        return;
    }

    $status_message = '';
    if (!empty($_GET['lexical_cache_status'])) {
        $status = sanitize_text_field($_GET['lexical_cache_status']);
        switch ($status) {
            case 'success':
                $status_message = '<div class="notice notice-success is-dismissible"><p>Lexical cache deleted and rebuilt successfully.</p></div>';
                break;
            case 'empty_corpus':
                $status_message = '<div class="notice notice-warning is-dismissible"><p>No published content was found, so the lexical cache could not be rebuilt.</p></div>';
                break;
            case 'write_error':
                $status_message = '<div class="notice notice-error is-dismissible"><p>Unable to write the lexical cache to disk. Check file permissions and try again.</p></div>';
                break;
            case 'build_error':
                $status_message = '<div class="notice notice-error is-dismissible"><p>The lexical cache rebuild failed. Please review your logs for details.</p></div>';
                break;
            case 'scheduled':
                $status_message = '<div class="notice notice-info is-dismissible"><p>The lexical cache rebuild was <strong>scheduled</strong> to run in the background (large site corpus). It should complete within a few minutes if WP-Cron runs. Check logs for <code>[LCM][rebuild]</code> lines, or run a smaller sync rebuild via filters <code>chatbot_lexical_rebuild_sync_max_documents</code> / <code>chatbot_lexical_rebuild_sync_max_corpus_bytes</code>.</p></div>';
                break;
            case 'already_scheduled':
                $status_message = '<div class="notice notice-warning is-dismissible"><p>A lexical cache rebuild is already scheduled or running. Please wait before starting another.</p></div>';
                break;
            case 'cleared':
                $status_message = '<div class="notice notice-success is-dismissible"><p>Cleared the pending lexical rebuild flag and any queued one-off cron events. You can schedule a new rebuild now.</p></div>';
                break;
            case 'ran_step':
                $status_message = '<div class="notice notice-info is-dismissible"><p>Ran one rebuild step. Check logs for <code>[LCM][rebuild]</code> heartbeat lines (offset should advance each run).</p></div>';
                break;
            case 'sync_failed':
                $status_message = '<div class="notice notice-error is-dismissible"><p>The lexical cache rebuild did not finish. Existing cache files were left unchanged. See logs for <code>[LCM][rebuild]</code>.</p></div>';
                break;
        }
    }

    global $chatbot_chatgpt_plugin_dir_path;

    if (empty($chatbot_chatgpt_plugin_dir_path)) {
        echo '<p>Unable to locate the lexical cache directory.</p>';
        return;
    }

    $cacheDir = trailingslashit($chatbot_chatgpt_plugin_dir_path) . 'includes/transformers/lexical_embeddings_cache';
    $cacheFile = $cacheDir . '/lexical_embeddings_cache.php';
    $compressedFile = $cacheFile . '.gz';

    if (!file_exists($cacheFile) || !file_exists($compressedFile)) {
        echo '<p>The lexical embeddings cache has not been created yet. Run the transformer build process to generate it.</p>';
        return;
    }

    if (!function_exists('transformer_model_lexical_context_get_cache_timestamps')) {
        require_once $chatbot_chatgpt_plugin_dir_path . 'includes/transformers/lexical-context-model.php';
    }

    list($createdAt, $updatedAt) = transformer_model_lexical_context_get_cache_timestamps($cacheFile);

    $wrapperContent = file_get_contents($cacheFile);
    $originalSize = null;
    $compressionRatio = null;

    if ($wrapperContent !== false) {
        if (preg_match('/Original size would be:\s*([0-9,]+)/i', $wrapperContent, $match)) {
            $originalSize = (int) str_replace(',', '', $match[1]);
        }
        if (preg_match('/Compression ratio:\s*([0-9.]+)%/i', $wrapperContent, $match)) {
            $compressionRatio = floatval($match[1]);
        }
        if (empty($createdAt) && preg_match('/Created:\s*(.+)/i', $wrapperContent, $match)) {
            $createdAt = trim(str_replace(['//', 'Created:'], '', $match[0]));
        }
        if (empty($updatedAt) && preg_match('/Updated:\s*(.+)/i', $wrapperContent, $match)) {
            $updatedAt = trim(str_replace(['//', 'Updated:'], '', $match[0]));
        }
    }

    $compressedSize = file_exists($compressedFile) ? filesize($compressedFile) : 0;

    if (empty($originalSize)) {
        $originalSize = filesize($cacheFile);
    }

    if (!empty($originalSize) && empty($compressionRatio) && $compressedSize > 0) {
        $compressionRatio = round((1 - ($compressedSize / $originalSize)) * 100, 1);
    }

    $estimatedMemoryBytes = $originalSize ? ($originalSize * 2) : ($compressedSize * 2);

    ?>
    <?php echo wp_kses_post($status_message); ?>
    <?php chatbot_lcm_lexical_echo_rebuild_worker_panel(); ?>
    <table class="widefat fixed striped">
        <tbody>
            <tr>
                <th scope="row">Cache Directory</th>
                <td><?php echo esc_html(str_replace($chatbot_chatgpt_plugin_dir_path, '', $cacheDir)); ?></td>
            </tr>
            <tr>
                <th scope="row">Created</th>
                <td><?php echo esc_html($createdAt ?: 'Unknown'); ?></td>
            </tr>
            <tr>
                <th scope="row">Last Updated</th>
                <td><?php echo esc_html($updatedAt ?: 'Unknown'); ?></td>
            </tr>
            <tr>
                <th scope="row">Original Serialized Size</th>
                <td><?php echo esc_html(chatbot_transformer_model_format_bytes($originalSize)); ?></td>
            </tr>
            <tr>
                <th scope="row">Compressed Size (.gz)</th>
                <td><?php echo esc_html(chatbot_transformer_model_format_bytes($compressedSize)); ?></td>
            </tr>
            <tr>
                <th scope="row">Compression Ratio</th>
                <td><?php echo esc_html(isset($compressionRatio) ? $compressionRatio . '%' : 'N/A'); ?></td>
            </tr>
            <tr>
                <th scope="row">Estimated Memory Needed</th>
                <td>
                    <?php echo esc_html(chatbot_transformer_model_format_bytes($estimatedMemoryBytes)); ?>
                    <p class="description">Approximation: serialized size × 2 to cover decompression and PHP array overhead.</p>
                </td>
            </tr>
        </tbody>
    </table>
    <?php
        $rebuild_url = wp_nonce_url(
            admin_url('admin-post.php?action=chatbot_transformer_model_rebuild_cache'),
            'chatbot_transformer_model_rebuild_cache'
        );
    ?>
    <p style="margin-top: 15px;">
        <a href="<?php echo esc_url($rebuild_url); ?>" class="button button-secondary" onclick="return confirm('Delete and rebuild the lexical cache now?');">
            Delete &amp; Rebuild Lexical Cache
        </a>
        <?php
            $pending = (bool) get_transient('chatbot_lexical_rebuild_job_pending') || ( function_exists( 'chatbot_lcm_lexical_cache_pending_one_shot_scheduled' ) && chatbot_lcm_lexical_cache_pending_one_shot_scheduled() );
            if ( $pending ) :
                $clear_url = wp_nonce_url(
                    admin_url('admin-post.php?action=chatbot_transformer_model_clear_lexical_rebuild'),
                    'chatbot_transformer_model_clear_lexical_rebuild'
                );
                $step_url = wp_nonce_url(
                    admin_url('admin-post.php?action=chatbot_transformer_model_run_lexical_rebuild_step'),
                    'chatbot_transformer_model_run_lexical_rebuild_step'
                );
        ?>
            <a href="<?php echo esc_url($step_url); ?>" class="button button-secondary" style="margin-left: 8px;">
                Run One Rebuild Step Now
            </a>
            <a href="<?php echo esc_url($clear_url); ?>" class="button button-link-delete" onclick="return confirm('Clear the pending rebuild flag and queued one-off cron events? Only do this if a rebuild is stuck.');" style="margin-left: 8px;">
                Clear Pending Rebuild (if stuck)
            </a>
        <?php endif; ?>
    </p>
    <p class="description">
        <?php echo esc_html__( 'Rebuilds the PMI embeddings cache and the optional LCM local IDF file (same corpus version). On large sites the request may schedule a background WP-Cron job instead of finishing in the browser (see notices after clicking). For predictable automation, use Lexical Cache Rebuild Schedule below.', 'chatbot-chatgpt' ); ?>
    </p>
    <?php

}

// Transformer Advanced Settings Callback - Ver 2.1.9
function chatbot_transformer_model_advanced_settings_section_callback($args) {

    ?>
    <p>Configure the advanced settings for the plugin when using the Transformer Models.</p>
    <!-- <p>Schedule the transformer model build process to run at different intervals. The build process trains the model on your site's published content, including pages and posts. The model is then used to generate text for the chatbot. The build process can be resource-intensive, so it is recommended to run it during off-peak hours or less frequently on high-traffic sites.</p> -->
    <?php

}

// Transformer Model Build Schedule Callback - Ver 2.1.6
function chatbot_transformer_model_build_schedule_callback($args) {

    // Get the saved chatbot_transformer_model_build_schedule value or default to "No"
    $chatbot_transformer_model_build_schedule = esc_attr(get_option('chatbot_transformer_model_build_schedule', 'No'));
    
    $options = [
        'No' => 'No',
        'Now' => 'Now',
        'Hourly' => 'Hourly',
        'Twice Daily' => 'Twice Daily',
        'Daily' => 'Daily',
        'Weekly' => 'Weekly',
        'Disable' => 'Disable',
        'Cancel' => 'Cancel'
    ];
    ?>
    <select id="chatbot_transformer_model_build_schedule" name="chatbot_transformer_model_build_schedule">
        <?php foreach ($options as $value => $label) : ?>
            <option value="<?php echo esc_attr($value); ?>" <?php selected($chatbot_transformer_model_build_schedule, $value); ?>>
                <?php echo esc_html($label); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
    
}

// Transformer Length Options Callback - Ver 2.1.6
function chatbot_transformer_model_word_content_window_size_callback($args) {

    // Get the saved chatbot_transformer_model_word_content_window_size_setting value or default to 3
    $transformer_model_window_length = esc_attr(get_option('chatbot_transformer_model_word_content_window_size', 3));
    // Allow for a range of tokens between 1 and 5 in 1-step increments - Ver 2.1.6
    ?>
    <select id="chatbot_transformer_model_word_content_window_size" name="chatbot_transformer_model_word_content_window_size">
        <?php
        for ($i=1; $i<=5; $i+=1) {
            echo '<option value="' . esc_attr($i) . '" ' . selected($transformer_model_window_length, (string)$i, false) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php

}

// Transformer Similarity Threshold Settings Callback - Ver 2.2.1
function chatbot_transformer_model_similarity_threshold_callback($args) {

    // Get the saved chatbot_transformer_model_similarity_threshold_setting value or default to 0.5
    $similarity_threshold = esc_attr(get_option('chatbot_transformer_model_similarity_threshold', '0.5'));
    // Allow for a range of tokens between 0.1 and 1.0 in 0.1-step increments - Ver 2.2.1
    ?>
    <select id="chatbot_transformer_model_similarity_threshold" name="chatbot_transformer_model_similarity_threshold">
        <?php
        for ($i=1; $i<=10; $i+=1) {
            echo '<option value="' . esc_attr($i/10) . '" ' . selected($similarity_threshold, (string)($i/10), false) . '>' . esc_html($i/10) . '</option>';
        }
        ?>
    </select>
    <?php

}

// Transformer Leading Sentences Ratio Settings Callback - Ver 2.2.1
function chatbot_transformer_model_leading_sentences_ratio_callback($args) {

    // Get the saved chatbot_transformer_model_leading_sentences_ratio_setting value or default to 0.2
    $leading_sentences_ratio = esc_attr(get_option('chatbot_transformer_model_leading_sentences_ratio', '0.2'));
    // Allow for a range of tokens between 0.1 and 1.0 in 0.1-step increments - Ver 2.2.1
    ?>
    <select id="chatbot_transformer_model_leading_sentences_ratio" name="chatbot_transformer_model_leading_sentences_ratio">
        <?php
        for ($i=1; $i<=10; $i+=1) {
            echo '<option value="' . esc_attr($i/10) . '" ' . selected($leading_sentences_ratio, (string)($i/10), false) . '>' . esc_html($i/10) . '</option>';
        }
        ?>
    </select>
    <?php

}

// Transformer Leading Token Ratio Settings Callback - Ver 2.2.1
function chatbot_transformer_model_leading_token_ratio_callback($args) {

    // Get the saved chatbot_transformer_model_leading_token_ratio_setting value or default to 0.2
    $leading_token_ratio = esc_attr(get_option('chatbot_transformer_model_leading_token_ratio', '0.2'));
    // Allow for a range of tokens between 0.1 and 1.0 in 0.1-step increments - Ver 2.2.1
    ?>
    <select id="chatbot_transformer_model_leading_token_ratio" name="chatbot_transformer_model_leading_token_ratio">
        <?php
        for ($i=1; $i<=10; $i+=1) {
            echo '<option value="' . esc_attr($i/10) . '" ' . selected($leading_token_ratio, (string)($i/10), false) . '>' . esc_html($i/10) . '</option>';
        }
        ?>
    </select>
    <?php

}

// LCM: optional corpus-local IDF multiplier on lexical sentence scores (default off).
function chatbot_transformer_model_lexical_local_idf_callback( $args ) {

    $lexical_local_idf = esc_attr( get_option( 'chatbot_transformer_model_lexical_local_idf', 'No' ) );
    ?>
    <select id="chatbot_transformer_model_lexical_local_idf" name="chatbot_transformer_model_lexical_local_idf">
        <option value="No" <?php selected( $lexical_local_idf, 'No' ); ?>><?php echo esc_html( 'No' ); ?></option>
        <option value="Yes" <?php selected( $lexical_local_idf, 'Yes' ); ?>><?php echo esc_html( 'Yes' ); ?></option>
    </select>
    <p class="description"><?php echo esc_html( 'When enabled, LCM boosts results using a conservative IDF-based relevance bonus derived from the current document set. This highlights rarer, more specific query terms. Use “Delete & Rebuild Lexical Cache” to generate the IDF file; chat requests read the cached data only.' ); ?></p>
    <?php
}

// LCM: optional query-intent expansion scoring (default off).
function chatbot_lcm_query_intent_expansion_callback( $args ) {

    $model_choice = esc_attr( get_option( 'chatbot_transformer_model_choice', 'lexical-context-model' ) );
    if ( $model_choice !== 'lexical-context-model' ) {
        echo '<p class="description">' . esc_html( 'This setting applies when Transformer Model Choice is Lexical Context Model (lexical-context-model).' ) . '</p>';
        return;
    }

    $intent_expansion = esc_attr( get_option( 'chatbot_lcm_query_intent_expansion', 'No' ) );
    if ( $intent_expansion !== 'Yes' && $intent_expansion !== 'No' ) {
        $intent_expansion = 'No';
    }
    ?>
    <select id="chatbot_lcm_query_intent_expansion" name="chatbot_lcm_query_intent_expansion">
        <option value="No" <?php selected( $intent_expansion, 'No' ); ?>><?php echo esc_html( 'No' ); ?></option>
        <option value="Yes" <?php selected( $intent_expansion, 'Yes' ); ?>><?php echo esc_html( 'Yes' ); ?></option>
    </select>
    <p class="description"><?php echo esc_html( 'When Yes, LCM applies small plugin-specific scoring boosts when queries imply use of WordPress content—for example matching phrases related to posts, pages, Knowledge Navigator, knowledge base, and similar concepts. On large sites this can slightly increase processing time.' ); ?></p>
    <?php
}

// Transformer Next Phrase Length Settings Callback - Ver 2.1.6
function chatbot_transformer_model_sentence_response_length_callback($args) {

    // Get the saved chatbot_transformer_model_sentence_response_length_setting value or default to 20
    $sentence_response_length = esc_attr(get_option('chatbot_transformer_model_sentence_response_length', '20'));
    // Allow for a range of sentences between 1 and 20 in 1-step increments - Ver 2.1.6
    ?>
    <select id="chatbot_transformer_model_sentence_response_length" name="chatbot_transformer_model_sentence_response_length">
        <?php
        for ($i=1; $i<=20; $i+=1) {
            echo '<option value="' . esc_attr($i) . '" ' . selected($sentence_response_length, (string)$i, false) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php
    
}

// Transformer Model Choice Callback - Ver 2.1.8
function chatbot_transformer_model_choice_callback($args) {

    global $chatbot_transformer_model_api_enabled;
    
    // Get the saved chatbot_transformer_model_choice value or default to the lexical-context-model model
    $model_choice = esc_attr(get_option('chatbot_transformer_model_choice', 'lexical-context-model'));

    ?>
    <select id="chatbot_transformer_model_choice" name="chatbot_transformer_model_choice">
        <option value="<?php echo esc_attr( 'lexical-context-model' ); ?>" <?php selected( $model_choice, 'lexical-context-model' ); ?>><?php echo esc_html( 'lexical-context-model' ); ?></option>
        <option value="<?php echo esc_attr( 'sentential-context-model-lite' ); ?>" <?php selected( $model_choice, 'sentential-context-model-lite' ); ?>><?php echo esc_html( 'sentential-context-model-lite' ); ?></option>
        <option value="<?php echo esc_attr( 'sentential-context-model' ); ?>" <?php selected( $model_choice, 'sentential-context-model' ); ?>><?php echo esc_html( 'sentential-context-model' ); ?></option>
    </select>
    <?php

}

// Max Tokens choice - Ver 2.1.9
function chatbot_transformer_model_max_tokens_setting_callback($args) {

    // Get the saved chatbot_transformer_model_max_tokens or default to 1000
    $max_tokens = esc_attr(get_option('chatbot_transformer_model_max_tokens', '1000'));

    // Allow for a range of tokens between 100 and 10000 in 100-step increments - Ver 2.0.4
    ?>
    <select id="chatbot_transformer_model_max_tokens" name="chatbot_transformer_model_max_tokens">
        <?php
        for ($i=100; $i<=50000; $i+=100) {
            echo '<option value="' . esc_attr($i) . '" ' . selected($max_tokens, (string)$i, false) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php
}

// Transformer Model Build Status - Ver 2.0.0.
function chatbot_transformer_model_status_section_callback($args) {

    // See if the scanner is needs to run
    $chatbot_transformer_model_current_build_schedule = esc_attr(get_option('chatbot_transformer_model_build_schedule', 'No Schedule'));
    if ($chatbot_transformer_model_current_build_schedule == 'No') {
        $chatbot_transformer_model_current_build_schedule  = 'No Schedule';
    }

    // Get Transformer Model Stats
    $chatbot_transformer_model_character_count = esc_attr(get_option('chatbot_transformer_model_character_count', 0));
    $chatbot_transformer_model_content_in_mb = esc_attr(get_option('chatbot_transformer_model_content_in_mb', 0));
    
    ?>
        <div class="wrap">
            <div style="background-color: white; border: 1px solid #ccc; padding: 10px; margin: 10px; display: inline-block;">
                <p><b>Scheduled to Run: </b><?php echo esc_html( $chatbot_transformer_model_current_build_schedule ); ?></p>
                <p><b>Status of Last Run: </b><?php echo esc_attr(get_option('chatbot_transformer_model_last_updated', 'Please select a Build Schedule below.')); ?></p>
                <!-- <p><b>Character Count: </b><?php echo esc_html( $chatbot_transformer_model_character_count ); ?></p> -->
                <!-- <p><b>Table Size: </b><?php echo esc_html( $chatbot_transformer_model_content_in_mb ); ?> MB</p> -->
                <p><b>Content Items Proccessed: </b><?php echo esc_attr(get_option('chatbot_transformer_model_content_items_processed', 0)); ?></p>
            </div>
            <p>Refresh this page to determine the progress and status of Transformer Model build status!</p>
        </div>
    <?php
}

// Register API settings - Moved for Ver 2.1.8
function chatbot_transformer_model_api_settings_init() {

    add_settings_section(
        'chatbot_transformer_model_api_enabled_section',
        'API/Transformer Settings',
        'chatbot_transformer_model_settings_section_callback',
        'chatbot_transformer_model_settings_general'
    );

    // Transformer Options - Ver 2.1.6
    register_setting('chatbot_transformer_model_api_model', 'chatbot_transformer_model_api_enabled'); // Ver 2.1.6
    register_setting('chatbot_transformer_model_api_model', 'chatbot_transformer_model_choice'); // Ver 2.1.8
    register_setting('chatbot_transformer_model_api_model', 'chatbot_transformer_model_max_tokens'); // Ver 2.1.9
    register_setting('chatbot_transformer_model_api_model', 'chatbot_transformer_model_build_schedule'); // Ver 2.1.6
    register_setting('chatbot_transformer_model_api_model', 'chatbot_transformer_model_word_content_window_size'); // Ver 2.1.6
    register_setting('chatbot_transformer_model_api_model', 'chatbot_transformer_model_sentence_response_length'); // Ver 2.1.6
    register_setting('chatbot_transformer_model_api_model', 'chatbot_transformer_model_similarity_threshold'); // Ver 2.2.1
    register_setting('chatbot_transformer_model_api_model', 'chatbot_transformer_model_leading_sentences_ratio'); // Ver 2.2.1
    register_setting('chatbot_transformer_model_api_model', 'chatbot_transformer_model_leading_token_ratio'); // Ver 2.2.1
    register_setting('chatbot_transformer_model_api_model', 'chatbot_transformer_model_lexical_local_idf');
    register_setting(
        'chatbot_transformer_model_api_model',
        'chatbot_lcm_query_intent_expansion',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'chatbot_lcm_query_intent_expansion_sanitize',
            'default'           => 'No',
        )
    );

    register_setting(
        'chatbot_transformer_model_api_model',
        'chatbot_lcm_lexical_cache_rebuild_schedule',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'chatbot_lcm_lexical_cache_rebuild_schedule_sanitize',
            'default'           => 'Never',
        )
    );

    add_settings_section(
        'chatbot_transformer_model_api_model_general_section',
        'Transformer Model Settings',
        'chatbot_transformer_model_api_model_general_section_callback',
        'chatbot_transformer_model_api_model_general'
    );

    add_settings_section(
        'chatbot_transformer_model_cache_info_section',
        'Lexical Context Cache Status',
        'chatbot_transformer_model_cache_info_callback',
        'chatbot_transformer_model_cache_info'
    );

    add_settings_section(
        'chatbot_lcm_lexical_cache_schedule_section',
        __( 'Lexical Cache Rebuild Schedule (WP-Cron)', 'chatbot-chatgpt' ),
        'chatbot_lcm_lexical_cache_schedule_section_callback',
        'chatbot_lcm_lexical_cache_schedule'
    );

    add_settings_field(
        'chatbot_lcm_lexical_cache_rebuild_schedule',
        __( 'Lexical Cache Rebuild Schedule', 'chatbot-chatgpt' ),
        'chatbot_lcm_lexical_cache_rebuild_schedule_callback',
        'chatbot_lcm_lexical_cache_schedule',
        'chatbot_lcm_lexical_cache_schedule_section'
    );

    add_settings_field(
        'chatbot_transformer_model_choice',
        'Transformer Model Choice',
        'chatbot_transformer_model_choice_callback',
        'chatbot_transformer_model_api_model_general',
        'chatbot_transformer_model_api_model_general_section'
    );

    add_settings_field(
        'chatbot_transformer_model_max_tokens',
        'Maximum Tokens Setting',
        'chatbot_transformer_model_max_tokens_setting_callback',
        'chatbot_transformer_model_api_model_general',
        'chatbot_transformer_model_api_model_general_section'
    );

    add_settings_section(
        'chatbot_transformer_model_status_section',
        'Transformer Model Build Status',
        'chatbot_transformer_model_status_section_callback',
        'chatbot_transformer_model_status'
    );

    add_settings_section(
        'chatbot_transformer_model_advanced_settings_section',
        'Transformer Model Advanced Settings',
        'chatbot_transformer_model_advanced_settings_section_callback',
        'chatbot_transformer_model_advanced_settings'
    );

    // add_settings_field(
    //     'chatbot_transformer_model_build_schedule',
    //     'Transformer Model Build Schedule',
    //     'chatbot_transformer_model_build_schedule_callback',
    //     'chatbot_transformer_model_advanced_settings',
    //     'chatbot_transformer_model_advanced_settings_section'
    // );

    add_settings_field(
        'chatbot_transformer_model_word_content_window_size',
        'Word Content Window Size',
        'chatbot_transformer_model_word_content_window_size_callback',
        'chatbot_transformer_model_advanced_settings',
        'chatbot_transformer_model_advanced_settings_section'
    );

    add_settings_field(
        'chatbot_transformer_model_sentence_response_length',
        'Sentence Response Count',
        'chatbot_transformer_model_sentence_response_length_callback',
        'chatbot_transformer_model_advanced_settings',
        'chatbot_transformer_model_advanced_settings_section'
    );

    add_settings_field(
        'chatbot_transformer_model_similarity_threshold',
        'Similarity Threshold',
        'chatbot_transformer_model_similarity_threshold_callback',
        'chatbot_transformer_model_advanced_settings',
        'chatbot_transformer_model_advanced_settings_section'
    );

    add_settings_field(
        'chatbot_transformer_model_leading_sentences_ratio',
        'Leading Sentences Ratio',
        'chatbot_transformer_model_leading_sentences_ratio_callback',
        'chatbot_transformer_model_advanced_settings',
        'chatbot_transformer_model_advanced_settings_section'
    );

    add_settings_field(
        'chatbot_transformer_model_leading_token_ratio',
        'Leading Token Ratio',
        'chatbot_transformer_model_leading_token_ratio_callback',
        'chatbot_transformer_model_advanced_settings',
        'chatbot_transformer_model_advanced_settings_section'
    );

    add_settings_field(
        'chatbot_transformer_model_lexical_local_idf',
        'LCM local IDF weighting',
        'chatbot_transformer_model_lexical_local_idf_callback',
        'chatbot_transformer_model_advanced_settings',
        'chatbot_transformer_model_advanced_settings_section'
    );

    add_settings_field(
        'chatbot_lcm_query_intent_expansion',
        'LCM Query Intent Expansion',
        'chatbot_lcm_query_intent_expansion_callback',
        'chatbot_transformer_model_advanced_settings',
        'chatbot_transformer_model_advanced_settings_section'
    );

}

/**
 * Sanitize LCM query intent expansion to Yes or No only.
 *
 * @param mixed $value Raw option value.
 * @return string
 */
function chatbot_lcm_query_intent_expansion_sanitize( $value ) {

    if ( $value === 'Yes' || $value === '1' || $value === 1 || $value === true ) {
        return 'Yes';
    }

    return 'No';
}

if (!function_exists('chatbot_transformer_model_format_bytes')) {
    function chatbot_transformer_model_format_bytes($bytes) {
        if (!is_numeric($bytes) || $bytes <= 0) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        $power = min($power, count($units) - 1);

        return number_format($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }
}

/**
 * Clear "pending rebuild" state and queued one-off cron events (recovery for killed wp-cron.php jobs).
 *
 * @return void
 */
function chatbot_transformer_model_handle_clear_lexical_rebuild() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have permission to perform this action.', 'chatbot-chatgpt' ) );
    }

    check_admin_referer( 'chatbot_transformer_model_clear_lexical_rebuild' );

    $redirect_url = admin_url( 'admin.php?page=chatbot-chatgpt&tab=api_transformer' );

    // Clear transient gate.
    delete_transient( 'chatbot_lexical_rebuild_job_pending' );

    // Clear queued one-off cron events (both the current arg format and the legacy empty-args format).
    if ( function_exists( 'chatbot_lcm_lexical_cache_rebuild_cron_hook' ) ) {
        $hook = chatbot_lcm_lexical_cache_rebuild_cron_hook();
        wp_clear_scheduled_hook( $hook, array( 'once' ) );
        wp_clear_scheduled_hook( $hook, array() );
    }

    // Clear chunked rebuild state + temp files if present.
    $state = get_option( 'chatbot_lcm_lexical_rebuild_state', array() );
    if ( is_array( $state ) && ! empty( $state['token'] ) ) {
        $token = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $state['token'] );
        global $chatbot_chatgpt_plugin_dir_path;
        if ( ! empty( $chatbot_chatgpt_plugin_dir_path ) ) {
            $dir   = trailingslashit( $chatbot_chatgpt_plugin_dir_path ) . 'includes/transformers/lexical_embeddings_cache/';
            $base  = $dir . 'lexical_rebuild_counts.' . $token;
            $paths = array(
                $base . '.co.bin',
                $base . '.wc.bin',
                $base . '.extra.bin',
                $base . '.bin',
                $dir . 'lexical_rebuild_pmi.' . $token . '.bin',
                $dir . 'lexical_rebuild_corpus.' . $token . '.txt',
            );
            foreach ( $paths as $p ) {
                if ( $p && file_exists( $p ) ) {
                    @unlink( $p );
                }
            }
        }
    }
    delete_option( 'chatbot_lcm_lexical_rebuild_state' );

    if ( function_exists( 'transformer_model_lexical_context_rebuild_clear_activity' ) ) {
        transformer_model_lexical_context_rebuild_clear_activity();
    }

    wp_safe_redirect( add_query_arg( 'lexical_cache_status', 'cleared', $redirect_url ) );
    exit;
}
add_action( 'admin_post_chatbot_transformer_model_clear_lexical_rebuild', 'chatbot_transformer_model_handle_clear_lexical_rebuild' );

/**
 * Run exactly one chunked rebuild slice immediately (useful when WP-Cron does not fire on localhost).
 *
 * @return void
 */
function chatbot_transformer_model_handle_run_lexical_rebuild_step() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have permission to perform this action.', 'chatbot-chatgpt' ) );
    }

    check_admin_referer( 'chatbot_transformer_model_run_lexical_rebuild_step' );

    global $chatbot_chatgpt_plugin_dir_path;
    if ( ! empty( $chatbot_chatgpt_plugin_dir_path ) ) {
        require_once $chatbot_chatgpt_plugin_dir_path . 'includes/transformers/lexical-context-model.php';
    }

    if ( function_exists( 'transformer_model_lexical_context_run_full_lexical_cache_rebuild_chunked' ) ) {
        // Run a single safe slice (time budget filter already defaults to 12s).
        transformer_model_lexical_context_run_full_lexical_cache_rebuild_chunked( 'manual_step', 'once' );
    }

    $redirect_url = admin_url( 'admin.php?page=chatbot-chatgpt&tab=api_transformer' );
    wp_safe_redirect( add_query_arg( 'lexical_cache_status', 'ran_step', $redirect_url ) );
    exit;
}
add_action( 'admin_post_chatbot_transformer_model_run_lexical_rebuild_step', 'chatbot_transformer_model_handle_run_lexical_rebuild_step' );

/**
 * Handle Lexical Cache rebuild requests from the settings UI.
 *
 * Large corpora defer to WP-Cron to avoid FastCGI idle timeouts. Rebuild uses atomic file swap — existing cache is kept if the job fails.
 */
function chatbot_transformer_model_handle_cache_rebuild() {
    if (!current_user_can('manage_options')) {
        wp_die( esc_html__( 'You do not have permission to perform this action.', 'chatbot-chatgpt' ) );
    }

    check_admin_referer('chatbot_transformer_model_rebuild_cache');

    $redirect_url = admin_url('admin.php?page=chatbot-chatgpt&tab=api_transformer');
    $model_choice = esc_attr(get_option('chatbot_transformer_model_choice', 'sentential-context-model'));
    if ($model_choice !== 'lexical-context-model') {
        wp_safe_redirect(add_query_arg('lexical_cache_status', 'build_error', $redirect_url));
        exit;
    }

    global $chatbot_chatgpt_plugin_dir_path;
    if (empty($chatbot_chatgpt_plugin_dir_path)) {
        wp_safe_redirect(add_query_arg('lexical_cache_status', 'build_error', $redirect_url));
        exit;
    }

    require_once $chatbot_chatgpt_plugin_dir_path . 'includes/transformers/lexical-context-model.php';

    $sql_agg = transformer_model_lexical_context_lexical_corpus_sql_aggregate();
    $pre_metrics = array(
        'document_count' => (int) ( $sql_agg['row_count'] ?? 0 ),
        'chunk_count'    => 0,
        'corpus_bytes'   => (int) ( $sql_agg['content_bytes'] ?? 0 ),
    );

    if (transformer_model_lexical_context_lexical_rebuild_should_defer_to_cron($pre_metrics)) {
        if (get_transient('chatbot_lexical_rebuild_job_pending') || chatbot_lcm_lexical_cache_pending_one_shot_scheduled()) {
            wp_safe_redirect(add_query_arg('lexical_cache_status', 'already_scheduled', $redirect_url));
            exit;
        }

        wp_schedule_single_event(time() + 10, chatbot_lcm_lexical_cache_rebuild_cron_hook(), array( 'once' ));
        set_transient('chatbot_lexical_rebuild_job_pending', 1, 2 * HOUR_IN_SECONDS);
        update_option( 'chatbot_lcm_lexical_cache_rebuild_last_status', 'running', false );
        update_option( 'chatbot_lcm_lexical_cache_rebuild_last_time', time(), false );

        if (function_exists('transformer_model_lexical_context_lexical_rebuild_log')) {
            transformer_model_lexical_context_lexical_rebuild_log('admin_rebuild deferred to wp-cron (SQL aggregate / large corpus)');
        }

        wp_safe_redirect(add_query_arg('lexical_cache_status', 'scheduled', $redirect_url));
        exit;
    }

    if ((int) ($sql_agg['row_count'] ?? 0) === 0) {
        wp_safe_redirect(add_query_arg('lexical_cache_status', 'empty_corpus', $redirect_url));
        exit;
    }

    $documents = transformer_model_lexical_context_fetch_wordpress_documents();
    if (empty($documents)) {
        wp_safe_redirect(add_query_arg('lexical_cache_status', 'empty_corpus', $redirect_url));
        exit;
    }

    $metrics = transformer_model_lexical_context_lexical_rebuild_corpus_metrics($documents);
    if (function_exists('transformer_model_lexical_context_lexical_rebuild_log')) {
        transformer_model_lexical_context_lexical_rebuild_log(
            sprintf(
                'admin_rebuild_request document_count=%d chunk_count=%d corpus_bytes=%d',
                $metrics['document_count'],
                $metrics['chunk_count'],
                $metrics['corpus_bytes']
            )
        );
    }

    if (transformer_model_lexical_context_lexical_rebuild_should_defer_to_cron($metrics)) {
        if (get_transient('chatbot_lexical_rebuild_job_pending') || chatbot_lcm_lexical_cache_pending_one_shot_scheduled()) {
            wp_safe_redirect(add_query_arg('lexical_cache_status', 'already_scheduled', $redirect_url));
            exit;
        }

        wp_schedule_single_event(time() + 10, chatbot_lcm_lexical_cache_rebuild_cron_hook(), array( 'once' ));
        set_transient('chatbot_lexical_rebuild_job_pending', 1, 2 * HOUR_IN_SECONDS);
        update_option( 'chatbot_lcm_lexical_cache_rebuild_last_status', 'running', false );
        update_option( 'chatbot_lcm_lexical_cache_rebuild_last_time', time(), false );

        if (function_exists('transformer_model_lexical_context_lexical_rebuild_log')) {
            transformer_model_lexical_context_lexical_rebuild_log('admin_rebuild deferred to wp-cron (large corpus)');
        }

        wp_safe_redirect(add_query_arg('lexical_cache_status', 'scheduled', $redirect_url));
        exit;
    }

    $result = transformer_model_lexical_context_run_full_lexical_cache_rebuild('browser_sync');

    delete_transient('chatbot_lexical_rebuild_job_pending');

    if (!empty($result['ok'])) {
        wp_safe_redirect(add_query_arg('lexical_cache_status', 'success', $redirect_url));
        exit;
    }

    $code = isset($result['error']) ? $result['error'] : 'sync_failed';
    if ($code === 'empty_corpus') {
        wp_safe_redirect(add_query_arg('lexical_cache_status', 'empty_corpus', $redirect_url));
        exit;
    }

    wp_safe_redirect(add_query_arg('lexical_cache_status', 'sync_failed', $redirect_url));
    exit;
}
add_action('admin_post_chatbot_transformer_model_rebuild_cache', 'chatbot_transformer_model_handle_cache_rebuild');

/**
 * WP-Cron worker: full lexical PMI + IDF rebuild (same atomic install as sync browser path).
 *
 * @param string|null $run_kind Optional. WordPress passes the first cron arg: 'once' | 'recurring', or empty for legacy jobs.
 * @return void
 */
function chatbot_transformer_model_lexical_cache_rebuild_cron_runner( $run_kind = null ) {

    if (!function_exists('transformer_model_lexical_context_run_full_lexical_cache_rebuild')) {
        global $chatbot_chatgpt_plugin_dir_path;
        if (!empty($chatbot_chatgpt_plugin_dir_path)) {
            require_once $chatbot_chatgpt_plugin_dir_path . 'includes/transformers/lexical-context-model.php';
        }
    }

    $model_choice = esc_attr(get_option('chatbot_transformer_model_choice', 'sentential-context-model'));
    if ($model_choice !== 'lexical-context-model') {
        if ($run_kind === 'once' || $run_kind === null || $run_kind === '') {
            delete_transient('chatbot_lexical_rebuild_job_pending');
        }
        if ( function_exists( 'transformer_model_lexical_context_rebuild_clear_activity' ) ) {
            transformer_model_lexical_context_rebuild_clear_activity();
        }
        return;
    }

    // Chunked rebuild to avoid FastCGI idle timeouts on wp-cron.php (common on MAMP/mod_fastcgi).
    if ( function_exists( 'transformer_model_lexical_context_run_full_lexical_cache_rebuild_chunked' ) ) {
        $chunk = transformer_model_lexical_context_run_full_lexical_cache_rebuild_chunked( 'wp_cron', $run_kind );
        if ( empty( $chunk['done'] ) ) {
            // Reschedule soon to continue; keep args consistent (once vs recurring marker).
            $hook = chatbot_lcm_lexical_cache_rebuild_cron_hook();
            $arg  = $run_kind ?: 'once';
            if ( ! wp_next_scheduled( $hook, array( $arg ) ) ) {
                wp_schedule_single_event( time() + 15, $hook, array( $arg ) );
            }
            return;
        }
        $result = array( 'ok' => ! empty( $chunk['ok'] ) );
    } else {
        $result = transformer_model_lexical_context_run_full_lexical_cache_rebuild('wp_cron');
    }

    update_option('chatbot_lcm_lexical_cache_rebuild_last_time', time(), false);
    update_option(
        'chatbot_lcm_lexical_cache_rebuild_last_status',
        !empty($result['ok']) ? 'success' : 'failed',
        false
    );

    if ( function_exists( 'transformer_model_lexical_context_rebuild_clear_activity' ) ) {
        transformer_model_lexical_context_rebuild_clear_activity();
    }

    // Transient is only used for one-off admin-defer jobs; recurring runs must not clear it.
    if ($run_kind === 'once' || $run_kind === null || $run_kind === '') {
        delete_transient('chatbot_lexical_rebuild_job_pending');
    }
}
add_action('chatbot_transformer_model_lexical_cache_rebuild_cron', 'chatbot_transformer_model_lexical_cache_rebuild_cron_runner', 10, 1);

add_action('admin_init', 'chatbot_transformer_model_api_settings_init');
