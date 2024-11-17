<?php
/**
 * Kognetiks Chatbot for WordPress - Transformer - Settings - Ver 2.1.6.1
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
    $results = chatbot_transformer_model_build_results_callback(esc_attr(get_option('chatbot_transformer_model_build_schedule')));

    ?>
    <p>Configure the settings for the plugin when using Transformer models. Some example shortcodes include:</p>
    <ul style="list-style-type: disc; list-style-position: inside; padding-left: 1em;">
        <li><code>&#91;chatbot style="floating" model="transformer-model-sentential-context"&#93;</code> - Style is floating, specific model</li>
        <li><code>&#91;chatbot style="embedded" model="transformer-model-sentential-context"&#93;</code> - Style is embedded, specific model</li>
    </ul>
    <p>A Transformer Model generates text using a local algorithm based on the <a href="https://en.wikipedia.org/wiki/Transformer_(deep_learning_architecture)" target="_blank" rel="noopener noreferrer">deep learning architecture</a>, a concept developed by researchers at Google and based on the multi-head attention mechanism proposed in a 2017 paper titled 'Attention Is All You Need'. Transformer models in this plugin are trained on your site's published content, including pages and posts. These models run locally on your server and are not available on the OpenAI platform. While they can produce useful text, they are less advanced than OpenAI models and may sometimes generate nonsensical output. However, they can still be effective when your site has a large amount of content.</p> 
    <?php
}

function chatbot_transformer_model_api_model_general_section_callback($args){
    ?>
    <p>Configure the settings for the plugin when using Transformer models.  Depending on the Transformer model you choose, the maximum tokens may be as high as 4000.  The default is 500.</p>
    <?php
}

// Transformer Advanced Settings Callback - Ver 2.1.9
function chatbot_transformer_model_advanced_settings_section_callback($args) {

    ?>
    <p>Configure the advanced settings for the plugin when using the Transformer Models.</p>
    <p>Schedule the transformer model build process to run at different intervals. The build process trains the model on your site's published content, including pages and posts. The model is then used to generate text for the chatbot. The build process can be resource-intensive, so it is recommended to run it during off-peak hours or less frequently on high-traffic sites.</p>
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
function chatbot_transformer_model_length_callback($args) {

    // Get the saved chatbot_transformer_model_length_setting value or default to 10
    $transformer_length = esc_attr(get_option('chatbot_transformer_model_length', '3'));
    // Allow for a range of tokens between 1 and 10 in 1-step increments - Ver 2.1.6
    ?>
    <select id="chatbot_transformer_model_length" name="chatbot_transformer_model_length">
        <?php
        for ($i=1; $i<=10; $i+=1) {
            echo '<option value="' . esc_attr($i) . '" ' . selected($transformer_length, (string)$i, false) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php

}

// Transformer Next Phrase Length Settings Callback - Ver 2.1.6
function chatbot_transformer_model_next_phrase_length_callback($args) {

    // Get the saved chatbot_transformer_model_next_phrase_length_setting value or default to 10
    $transformer_next_phrase_length = esc_attr(get_option('chatbot_transformer_model_next_phrase_length', '1'));
    // Allow for a range of tokens between 10 and 1000 in 10-step increments - Ver 2.1.6
    ?>
    <select id="chatbot_transformer_model_next_phrase_length" name="chatbot_transformer_model_next_phrase_length">
        <?php
        for ($i=1; $i<=10; $i+=1) {
            echo '<option value="' . esc_attr($i) . '" ' . selected($transformer_next_phrase_length, (string)$i, false) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php
    
}

// Transformer Model Choice Callback - Ver 2.1.8
function chatbot_transformer_model_choice_callback($args) {

    global $chatbot_transformer_model_api_enabled;
    
    // Get the saved chatbot_transformer_model_choice value or default to the transformer-model-lexical-context model
    $model_choice = esc_attr(get_option('chatbot_transformer_model_choice', 'transformer-model-lexical-context'));

    ?>
    <select id="chatbot_transformer_model_choice" name="chatbot_transformer_model_choice">
        <option value="<?php echo esc_attr( 'transformer-model-lexical-context' ); ?>" <?php selected( $model_choice, 'transformer-model-lexical-context' ); ?>><?php echo esc_html( 'transformer-model-lexical-context' ); ?></option>
        <option value="<?php echo esc_attr( 'transformer-model-sentential-context' ); ?>" <?php selected( $model_choice, 'transformer-model-sentential-context' ); ?>><?php echo esc_html( 'transformer-model-sentential-context' ); ?></option>
    </select>
    <?php

}

// Max Tokens choice - Ver 2.1.9
function chatbot_transformer_model_max_tokens_setting_callback($args) {

    // Get the saved chatbot_transformer_model_max_tokens or default to 500
    $max_tokens = esc_attr(get_option('chatbot_transformer_model_max_tokens', '500'));

    // Allow for a range of tokens between 100 and 4096 in 100-step increments - Ver 2.0.4
    ?>
    <select id="chatbot_transformer_model_max_tokens" name="chatbot_transformer_model_max_tokens">
        <?php
        for ($i=100; $i<=4000; $i+=100) {
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

    // Get DB Stats
    // $chatbot_transformer_model_db_stats = getDatabaseStats("chatbot_transformer_model");
    // Unpack the returned array
    // $chatbot_transformer_model_row_count = $chatbot_transformer_model_db_stats['row_count'];
    // $chatbot_transformer_model_table_size_mb = $chatbot_transformer_model_db_stats['table_size_mb'];
    
    ?>
        <div class="wrap">
            <div style="background-color: white; border: 1px solid #ccc; padding: 10px; margin: 10px; display: inline-block;">
                <p><b>Scheduled to Run: </b><?php echo $chatbot_transformer_model_current_build_schedule; ?></p>
                <p><b>Status of Last Run: </b><?php echo esc_attr(get_option('chatbot_transformer_model_last_updated', 'Please select a Build Schedule below.')); ?></p>
                <p><b>Row Count: </b><?php echo $chatbot_transformer_model_row_count; ?></p>
                <p><b>Table Size: </b><?php echo $chatbot_transformer_model_table_size_mb; ?> MB</p>
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
    register_setting('chatbot_transformer_model_api_model', 'chatbot_transformer_model_length'); // Ver 2.1.6
    register_setting('chatbot_transformer_model_api_model', 'chatbot_transformer_model_next_phrase_length'); // Ver 2.1.6

    add_settings_section(
        'chatbot_transformer_model_api_model_general_section',
        'Transformer Model Settings',
        'chatbot_transformer_model_api_model_general_section_callback',
        'chatbot_transformer_model_api_model_general'
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

    add_settings_field(
        'chatbot_transformer_model_build_schedule',
        'Transformer Model Build Schedule',
        'chatbot_transformer_model_build_schedule_callback',
        'chatbot_transformer_model_advanced_settings',
        'chatbot_transformer_model_advanced_settings_section'
    );

    add_settings_field(
        'chatbot_transformer_model_length',
        'Transformer Length',
        'chatbot_transformer_model_length_callback',
        'chatbot_transformer_model_advanced_settings',
        'chatbot_transformer_model_advanced_settings_section'
    );

    add_settings_field(
        'chatbot_transformer_model_next_phrase_length',
        'Transformer Length Next Phase Length',
        'chatbot_transformer_model_next_phrase_length_callback',
        'chatbot_transformer_model_advanced_settings',
        'chatbot_transformer_model_advanced_settings_section'
    );

}
add_action('admin_init', 'chatbot_transformer_model_api_settings_init');