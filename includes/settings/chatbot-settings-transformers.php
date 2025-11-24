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
    <p><strong>Transformer-Inspired Models</strong>: The transformer-inspired models (below) in this plugin generate text using local algorithms inspired by the <a href="https://en.wikipedia.org/wiki/Transformer_(deep_learning_architecture)" target="_blank" rel="noopener noreferrer">Transformer deep learning architecture</a>, a concept developed by researchers at Google in their 2017 paper "Attention Is All You Need". While these models do not implement the full transformer architecture, they utilize similar principles, such as word embeddings and context analysis, to generate responses based on your site's content. They run locally on your server, providing privacy and control over the data. Although less advanced than models like those provided by OpenAI, NVIDIA, Anthropic, DeepSeek or Mistral and may sometimes produce nonsensical output, they can be effective, especially when your site contains a substantial amount of content.</p>
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
    </p>
    <p class="description">
        This removes the existing lexical cache file and rebuilds it immediately. Depending on your site size, this may take a few minutes.
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
    
    // DIAG - Diagnostics - Ver 2.1.6
    // back_trace( 'NOTICE', 'chatbot_transformer_model_build_schedule: ' . $chatbot_transformer_model_build_schedule );
    
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

    // Get the saved chatbot_transformer_model_max_tokens or default to 10000
    $max_tokens = esc_attr(get_option('chatbot_transformer_model_max_tokens', '10000'));

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
                <p><b>Scheduled to Run: </b><?php echo $chatbot_transformer_model_current_build_schedule; ?></p>
                <p><b>Status of Last Run: </b><?php echo esc_attr(get_option('chatbot_transformer_model_last_updated', 'Please select a Build Schedule below.')); ?></p>
                <!-- <p><b>Character Count: </b><?php echo $chatbot_transformer_model_character_count; ?></p> -->
                <!-- <p><b>Table Size: </b><?php echo $chatbot_transformer_model_content_in_mb; ?> MB</p> -->
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
 * Handle Lexical Cache rebuild requests from the settings UI.
 */
function chatbot_transformer_model_handle_cache_rebuild() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have permission to perform this action.', 'chatbot-chatgpt'));
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

    $cacheDir = trailingslashit($chatbot_chatgpt_plugin_dir_path) . 'includes/transformers/lexical_embeddings_cache';
    $cacheFile = $cacheDir . '/lexical_embeddings_cache.php';
    $cacheVersionFile = $cacheDir . '/lexical_embeddings_cache_version.txt';

    if (!file_exists($cacheDir)) {
        wp_mkdir_p($cacheDir);
    }

    require_once $chatbot_chatgpt_plugin_dir_path . 'includes/transformers/lexical-context-model.php';

    // Remove existing cache artefacts before rebuilding.
    $filesToDelete = [
        $cacheFile,
        $cacheFile . '.gz',
        $cacheFile . '.ser',
        $cacheFile . '.old',
        $cacheVersionFile,
    ];

    foreach ($filesToDelete as $file) {
        if ($file && file_exists($file)) {
            @unlink($file);
        }
    }

    $corpus = transformer_model_lexical_context_fetch_wordpress_content();
    if (empty($corpus)) {
        wp_safe_redirect(add_query_arg('lexical_cache_status', 'empty_corpus', $redirect_url));
        exit;
    }

    $windowSize = intval(get_option('chatbot_transformer_model_word_content_window_size', 3));
    $windowSize = max(1, $windowSize);

    $embeddings = transformer_model_lexical_context_build_pmi_matrix($corpus, $windowSize);

    if (empty($embeddings)) {
        wp_safe_redirect(add_query_arg('lexical_cache_status', 'build_error', $redirect_url));
        exit;
    }

    $status = 'write_error';
    if (transformer_model_lexical_context_save_cache($cacheFile, $embeddings)) {
        file_put_contents($cacheVersionFile, md5($corpus));
        $status = 'success';
    }

    wp_safe_redirect(add_query_arg('lexical_cache_status', $status, $redirect_url));
    exit;
}
add_action('admin_post_chatbot_transformer_model_rebuild_cache', 'chatbot_transformer_model_handle_cache_rebuild');

add_action('admin_init', 'chatbot_transformer_model_api_settings_init');