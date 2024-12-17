<?php
/**
 * Kognetiks Chatbot - Settings - API/ChatGPT Page
 *
 * This file contains the code for the Chatbot settings page.
 * It allows users to configure the API key and other parameters
 * required to access the ChatGPT API from their own account.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// API/ChatGPT Settings section callback - Ver 1.3.0 - Updated Ver 2.0.2.1
function chatbot_chatgpt_model_settings_section_callback($args) {
    ?>
    <p>Configure the default settings for the Chatbot plugin for chat, voice, and image generation.  Start by adding your API key then selecting your choices below.  Don't forget to click "Save Settings" at the very bottom of this page.</p>
    <p>More information about ChatGPT models and their capability can be found at <a href="https://platform.openai.com/docs/models/overview" target="_blank">https://platform.openai.com/docs/models/overview</a>.</p>
    <p><b><i>Don't forget to click </i><code>Save Settings</code><i> to save any changes your might make.</i></b></p>
    <p style="background-color: #e0f7fa; padding: 10px;"><b>For an explanation of the API/ChatGPT Settings and additional documentation please click <a href="?page=chatbot-chatgpt&tab=support&dir=api-chatgpt-settings&file=api-chatgpt-model-settings.md">here</a>.</b></p>
    <?php
}

function chatbot_chatgpt_api_chatgpt_general_section_callback($args) {
    ?>
    <p>Configure the settings for the plugin by adding your API key. This plugin requires an API key from OpenAI to function. You can obtain an API key by signing up at <a href="https://platform.openai.com/account/api-keys" target="_blank">https://platform.openai.com/account/api-keys</a>.</p>
    <?php
}

function chatbot_chatgpt_api_chatgpt_chat_section_callback($args) {
    ?>
    <p>Configure the settings for the plugin when using chat models. Depending on the OpenAI model you choose, the maximum tokens may be as high as 4097. The default is 150. For more information about the maximum tokens parameter, please see <a href="https://help.openai.com/en/articles/4936856-what-are-tokens-and-how-to-count-them" target="_blank">https://help.openai.com/en/articles/4936856-what-are-tokens-and-how-to-count-them</a>. Enter a conversation context to help the model understand the conversation. See the default for ideas. Some example shortcodes include:</p>
    <ul style="list-style-type: disc; list-style-position: inside; padding-left: 1em;">
        <li><code>&#91;chatbot&#93;</code> - Default chat model, style is floating</li>
        <li><code>&#91;chatbot style="floating" model="gpt-4"&#93;</code> - Style is floating, specific model</li>
        <li><code>&#91;chatbot style="embedded" model="gpt-4-1106-preview"&#93;</code> - Style is embedded, default chat model</li>
        <!-- <li><code>&#91;chatbot style=embedded model=chat&#93;</code> - Style is embedded, default chat model</li> -->
    </ul>
    <?php
}

function chatbot_chatgpt_api_chatgpt_image_section_callback($args) {
    ?>
    <p>Configure the settings for the plugin when using image models. Some example shortcodes include:</p>
    <ul style="list-style-type: disc; list-style-position: inside; padding-left: 1em;">
        <li><code>&#91;chatbot style="floating" model="dall-e-2"&#93;</code> - Style is floating, specific model</li>
        <li><code>&#91;chatbot style="embedded" model="dall-e-3"&#93;</code> - Style is embedded, default image model</li>
        <!-- <li><code>&#91;chatbot style=embedded model=image&#93;</code> - Style is embedded, default image model</li> -->
    </ul>
    <?php
}

function chatbot_chatgpt_api_chatgpt_voice_section_callback($args) {
    ?>
    <p>Configure the settings for the plugin when using audio models. Some example shortcodes include:</p>
    <ul style="list-style-type: disc; list-style-position: inside; padding-left: 1em;">
        <li><code>&#91;chatbot style="floating" model="tts-1-hd"&#93;</code> - Style is floating, specific model</li>
        <li><code>&#91;chatbot style="embedded" model="tts-1-hd-1106"&#93;</code> - Style is embedded, default image model</li>
        <li><code>&#91;chatbot style="floating" model="tts-1-hd" voice="nova"&#93;</code> - Style is floating, specific model, specific voice</li>
        <!-- <li><code>&#91;chatbot style=embedded model=speech&#93;</code> - Style is embedded, default image model</li> -->
    </ul>
    <p>There are also the default options for the "read aloud" button on the chatbot interface</p>
    <?php
}

// Whisper Section Callback - Ver 2.0.1
function chatbot_chatgpt_api_chatgpt_whisper_section_callback($args) {
    ?>
    <p>Configure the settings for the plugin when using whisper models. Some example shortcodes include:</p>
    <ul style="list-style-type: disc; list-style-position: inside; padding-left: 1em;">
        <li><code>&#91;chatbot style="floating" model="whisper-1"&#93;</code> - Style is floating, specific model</li>
        <li><code>&#91;chatbot style="embedded" model="whisper-1"&#93;</code> - Style is embedded, specific model</li>
        <!-- <li><code>&#91;chatbot style=embedded model=whisper&#93;</code> - Style is embedded, default whisper model</li> -->
    </ul>
    <?php
}

function chatbot_chatgpt_api_chatgpt_advanced_section_callback($args) {
    ?>
    <p>CAUTION: Configure the advanced settings for the plugin. Enter the base URL for the OpenAI API.  The default is <code>https://api.openai.com/v1</code>.</p>
    <?php
}

// API key field callback
function chatbot_chatgpt_api_key_callback($args) {
    $api_key = esc_attr(get_option('chatbot_chatgpt_api_key'));
    ?>
    <input type="password" id="chatbot_chatgpt_api_key" name="chatbot_chatgpt_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text"  autocomplete="off">
    <?php
}

// OpenAI Models
// https://platform.openai.com/docs/models
// EXPAND THE LIST OF MODELS STARTING WITH V1.9.4 - 2024 03 24
// https://platform.openai.com/docs/models/gpt-4-and-gpt-4-turbo
// Model choice
function chatbot_chatgpt_model_choice_callback($args) {
  
    // Get the saved chatbot_chatgpt_model_choice value or default to "gpt-3.5-turbo"
    $model_choice = esc_attr(get_option('chatbot_chatgpt_model_choice', 'gpt-3.5-turbo'));

    // Fetch models from the API
    $models = chatbot_openai_get_models();

    // DIAG - Ver 1.9.5
    // back_trace( 'NOTICE', '$models: ' . print_r($models, true) );

    // Limit the models to chat models
    $models = array_filter($models, function($model) {
        return strpos($model['id'], 'gpt') !== false;
    });

    // Check for errors
    if (is_string($models) && strpos($models, 'Error:') === 0) {
        // If there's an error, display the hardcoded list
        $model_choice = esc_attr(get_option('chatbot_chatgpt_model_choice', 'gpt-3.5-turbo'));
        ?>
        <select id="chatbot_chatgpt_model_choice" name="chatbot_chatgpt_model_choice">
            <option value="<?php echo esc_attr( 'gpt-4-1106-preview' ); ?>" <?php selected( $model_choice, 'gpt-4-1106-preview' ); ?>><?php echo esc_html( 'gpt-4-1106-preview' ); ?></option>
            <option value="<?php echo esc_attr( 'gpt-4' ); ?>" <?php selected( $model_choice, 'gpt-4' ); ?>><?php echo esc_html( 'gpt-4' ); ?></option>
            <option value="<?php echo esc_attr( 'gpt-3.5-turbo' ); ?>" <?php selected( $model_choice, 'gpt-3.5-turbo' ); ?>><?php echo esc_html( 'gpt-3.5-turbo' ); ?></option>
        </select>
        <?php
    } else {
        // If models are fetched successfully, display them dynamically
        ?>
        <select id="chatbot_chatgpt_model_choice" name="chatbot_chatgpt_model_choice">
            <?php foreach ($models as $model): ?>
                <option value="<?php echo esc_attr($model['id']); ?>" <?php selected(esc_attr(get_option('chatbot_chatgpt_model_choice')), $model['id']); ?>><?php echo esc_html($model['id']); ?></option>
            <?php endforeach; ?>
            ?>
        </select>
        <?php
    }

}

// Max Tokens choice - Ver 1.4.2
function chatgpt_max_tokens_setting_callback($args) {
    // Get the saved chatbot_chatgpt_max_tokens_setting or default to 500
    $max_tokens = esc_attr(get_option('chatbot_chatgpt_max_tokens_setting', '500'));
    // Allow for a range of tokens between 100 and 4096 in 100-step increments - Ver 2.0.4
    ?>
    <select id="chatbot_chatgpt_max_tokens_setting" name="chatbot_chatgpt_max_tokens_setting">
        <?php
        for ($i=100; $i<=4000; $i+=100) {
            echo '<option value="' . esc_attr($i) . '" ' . selected($max_tokens, (string)$i, false) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php
}

// Conversation Context - Ver 1.6.1
function chatbot_chatgpt_conversation_context_callback($args) {
    // Get the value of the setting we've registered with register_setting()
    $chatbot_chatgpt_conversation_context = esc_attr(get_option('chatbot_chatgpt_conversation_context'));

    // Check if the option has been set, if not, use a default value
    if (empty($chatbot_chatgpt_conversation_context)) {
        $chatbot_chatgpt_conversation_context = "You are a versatile, friendly, and helpful assistant designed to support me in a variety of tasks that responds in Markdown.";
        // Save the default value into the option
        update_option('chatbot_chatgpt_conversation_context', $chatbot_chatgpt_conversation_context);
    }

    ?>
    <!-- Define the textarea field. -->
    <textarea id='chatbot_chatgpt_conversation_context' name='chatbot_chatgpt_conversation_context' rows='5' cols='50' maxlength='12500'><?php echo esc_html(stripslashes($chatbot_chatgpt_conversation_context)); ?></textarea>
    <?php
}

// Set chatbot_chatgpt_temperature - Ver 2.0.1
// https://platform.openai.com/docs/assistants/how-it-works/temperature
function chatbot_chatgpt_temperature_callback($args) {
    $temperature = esc_attr(get_option('chatbot_chatgpt_temperature', 0.50));
    ?>
    <select id="chatbot_chatgpt_temperature" name="chatbot_chatgpt_temperature">
        <?php
        for ($i = 0.01; $i <= 2.01; $i += 0.01) {
            echo '<option value="' . $i . '" ' . selected($temperature, (string)$i) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php
}

// Set chatbot_chatgpt_top_p - Ver 2.0.1
// https://platform.openai.com/docs/assistants/how-it-works/top-p
function chatbot_chatgpt_top_p_callback($args) {
    $top_p = esc_attr(get_option('chatbot_chatgpt_top_p', 1.00));
    ?>
    <select id="chatbot_chatgpt_top_p" name="chatbot_chatgpt_top_p">
        <?php
        for ($i = 0.01; $i <= 1.01; $i += 0.01) {
            echo '<option value="' . $i . '" ' . selected($top_p, (string)$i) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php
}

// Base URL for the OpenAI API - Ver 1.8.1
function chatbot_chatgpt_base_url_callback($args) {
    $chatbot_chatgpt_base_url = esc_attr(get_option('chatbot_chatgpt_base_url', 'https://api.openai.com/v1'));
    ?>
    <input type="text" id="chatbot_chatgpt_base_url" name="chatbot_chatgpt_base_url" value="<?php echo esc_attr( $chatbot_chatgpt_base_url ); ?>" class="regular-text">
    <?php
}

// Base URL function calls - Ver 1.8.1
function get_openai_api_base_url() {
    return esc_attr(get_option('chatbot_chatgpt_base_url', 'https://api.openai.com/v1'));
}

// Base URL for the NVIDIA - Ver 2.1.8
function get_nvidia_api_base_url() {
    return esc_attr(get_option('chatbot_nvidia_base_url', 'https://integrate.api.nvidia.com/v1'));
}

// Base URL for the Anthropic - Ver 2.2.1
function get_anthropic_api_base_url() {
    return esc_attr(get_option('chatbot_anthropic_base_url', 'https://api.anthropic.com/v1'));
}

function get_threads_api_url() {
    return get_openai_api_base_url() . "/threads";
}

function get_files_api_url() {
    return get_openai_api_base_url() . "/files";
}

function get_chat_completions_api_url() {

    // Enable for either ChatGPT or NVIDIA - Ver 2.1.8
    if (get_option('chatbot_nvidia_api_enabled') == 'Yes' || esc_attr(get_option('chatbot_ai_platform_choice')) == 'NVIDIA') {
        // DIAG - Diagnostics - Ver 2.1.8
        // back_trace( 'NOTICE', 'get_chat_completions_api_url: NVIDIA API' );
        return get_nvidia_api_base_url() . "/chat/completions";
    } else if (get_option('chatbot_anthropic_api_enabled') == 'Yes' || esc_attr(get_option('chatbot_ai_platform_choice')) == 'Anthropic') {
        // DIAG - Diagnostics - Ver 2.2.1
        // back_trace( 'NOTICE', 'get_chat_completions_api_url: Anthropic API' );
        return get_anthropic_api_base_url() . "/messages";
    } else {
        // DIAG - Diagnostics - Ver 2.1.8
        // back_trace( 'NOTICE', 'get_chat_completions_api_url: OpenAI API' );
        return get_openai_api_base_url() . "/chat/completions";
    }

}

// Timeout Settings Callback - Ver 1.8.8
function chatbot_chatgpt_timeout_setting_callback($args) {

    // Get the saved chatbot_chatgpt_timeout value or default to 240
    $timeout = esc_attr(get_option('chatbot_chatgpt_timeout_setting', 240));

    // Allow for a range of tokens between 5 and 500 in 5-step increments - Ver 1.8.8
    ?>
    <select id="chatbot_chatgpt_timeout_setting" name="chatbot_chatgpt_timeout_setting">
        <?php
        for ($i=5; $i<=500; $i+=5) {
            echo '<option value="' . esc_attr($i) . '" ' . selected($timeout, (string)$i, false) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php
    
}

// Voice Model Options Callback - Ver 1.9.5
function chatbot_chatgpt_voice_model_option_callback($args) {

    // https://platform.openai.com/docs/guides/voice-models
    // https://platform.openai.com/docs/models/tts

    // Get the saved chatbot_chatgpt_model_choice value or default to "gpt-3.5-turbo"
    $voice_model_option = esc_attr(get_option('chatbot_chatgpt_voice_model_option', 'tts-1-hd'));

    // Fetch models from the API
    $voice_models = chatbot_openai_get_models();

    // Limit the models to voice models
    $voice_models = array_filter($voice_models, function($voice_model) {
        return strpos($voice_model['id'], 'tts') !== false;
    });
    
    // Check for errors
    if (is_string($voice_models) && strpos($voice_models, 'Error:') === 0) {
        // If there's an error, display the hardcoded list
        $voice_model_option = esc_attr(get_option('chatbot_chatgpt_voice_model_option', 'tts-1-hd'));
        ?>
        <select id="chatbot_chatgpt_voice_model_option" name="chatbot_chatgpt_voice_model_option">
            <option value="<?php echo esc_attr( 'tts-1' ); ?>" <?php selected( $voice_model_option, 'tts-1' ); ?>><?php echo esc_html( 'tts-1' ); ?></option>
            <option value="<?php echo esc_attr( 'tts-1-1106' ); ?>" <?php selected( $voice_model_option, 'tts-1-1106' ); ?>><?php echo esc_html( 'tts-1-1106' ); ?></option>
            <option value="<?php echo esc_attr( 'tts-1-hd' ); ?>" <?php selected( $voice_model_option, 'tts-1-hd' ); ?>><?php echo esc_html( 'tts-1-hd' ); ?></option>
            <option value="<?php echo esc_attr( 'tts-1-hd-1106' ); ?>" <?php selected( $voice_model_option, 'tts-1-hd-1106' ); ?>><?php echo esc_html( 'tts-1-hd-1106' ); ?></option>
        </select>
        <?php
    } else {
        // If models are fetched successfully, display them dynamically
        ?>
        <select id="chatbot_chatgpt_voice_model_option" name="chatbot_chatgpt_voice_model_option">
            <?php foreach ($voice_models as $voice_model): ?>
                <option value="<?php echo esc_attr($voice_model['id']); ?>" <?php selected(esc_attr(get_option('chatbot_chatgpt_voice_model_option')), $voice_model['id']); ?>><?php echo esc_html($voice_model['id']); ?></option>
            <?php endforeach; ?>
        </select>
        <?php  
    }

}

// Voice Options Callback - Ver 1.9.5
function chatbot_chatgpt_voice_option_callback($args) {

    // https://platform.openai.com/docs/guides/speech-to-text
    // Options include Alloy, Echo, Fable, Onyx, Nova, and Shimmer

    // Get the saved chatbot_chatgpt_voice_options value or default to "Alloy"
    $voice_option = esc_attr(get_option('chatbot_chatgpt_voice_option', 'alloy'));
    ?>
    <select id="chatbot_chatgpt_voice_option" name="chatbot_chatgpt_voice_option">
        <option value="alloy" <?php selected($voice_option, 'alloy'); ?>>Alloy</option>
        <option value="echo" <?php selected($voice_option, 'echo'); ?>>Echo</option>
        <option value="fable" <?php selected($voice_option, 'fable'); ?>>Fable</option>
        <option value="onyx" <?php selected($voice_option, 'onyx'); ?>>Onyx</option>
        <option value="nova" <?php selected($voice_option, 'nova'); ?>>Nova</option>
        <option value="shimmer" <?php selected($voice_option, 'shimmer'); ?>>Shimmer</option>
    </select>
    <?php
}

// Voice Output Options Callback - Ver 1.9.5
function chatbot_chatgpt_audio_output_format_callback($args) {

    // https://platform.openai.com/docs/guides/text-to-speech
    // Options include mp3, opus, aac, flac, wav, and pcm

    // Get the saved chatbot_chatgpt_voice_output_options value or default to "mp3"
    $audio_output_format = esc_attr(get_option('chatbot_chatgpt_audio_output_format', 'mp3'));
    ?>
    <select id="chatbot_chatgpt_audio_output_format" name="chatbot_chatgpt_audio_output_format">
        <option value="mp3" <?php selected($audio_output_format, 'mp3'); ?>>MP3</option>
        <option value="opus" <?php selected($audio_output_format, 'opus'); ?>>OPUS</option>
        <option value="aac" <?php selected($audio_output_format, 'aac'); ?>>AAC</option>
        <option value="flac" <?php selected($audio_output_format, 'flac'); ?>>FLAC</option>
        <option value="wav" <?php selected($audio_output_format, 'wav'); ?>>WAV</option>
        <option value="pcm" <?php selected($audio_output_format, 'pcm'); ?>>PCM</option>
    </select>
    <?php
}

// Read Aloud Option Callback - Ver 2.0.0
function chatbot_chatgpt_read_aloud_option_callback($args) {

    // Get the saved chatbot_chatgpt_read_aloud_option value or default to "yes"
    $read_aloud_option = esc_attr(get_option('chatbot_chatgpt_read_aloud_option', 'yes'));
    ?>
    <select id="chatbot_chatgpt_read_aloud_option" name="chatbot_chatgpt_read_aloud_option">
        <option value="yes" <?php selected($read_aloud_option, 'yes'); ?>>Yes</option>
        <option value="no" <?php selected($read_aloud_option, 'no'); ?>>No</option>
    </select>
    <?php

}

// Image Model Options Callback - Ver 1.9.5
function chatbot_chatgpt_image_model_option_callback($args) {

    // https://platform.openai.com/docs/guides/images
    // https://platform.openai.com/docs/models/dall-e

    // Get the saved chatbot_chatgpt_model_option value or default to "dall-e-3"
    $image_model_option = esc_attr(get_option('chatbot_chatgpt_image_model_option', 'dall-e-3'));

    // Fetch models from the API
    $image_models = chatbot_openai_get_models();

    // Limit the models to image models
    $image_models = array_filter($image_models, function($image_model) {
        return strpos($image_model['id'], 'dall-e') !== false;
    });
    
    // Check for errors
    if (is_string($image_models) && strpos($image_models, 'Error:') === 0) {
        // If there's an error, display the hardcoded list
        $image_model_option = esc_attr(get_option('chatbot_chatgpt_image_model_option', 'dall-e-3'));
        ?>
        <select id="chatbot_chatgpt_image_model_option" name="chatbot_chatgpt_image_model_option">
            <option value="<?php echo esc_attr( 'dall-e-2' ); ?>" <?php selected( $image_model_option, 'dall-e-2' ); ?>><?php echo esc_html( 'dall-e-2' ); ?></option>
            <option value="<?php echo esc_attr( 'dall-e-3' ); ?>" <?php selected( $image_model_option, 'dall-e-3' ); ?>><?php echo esc_html( 'dall-e-3' ); ?></option>
        </select>
        <?php
    } else {
        // If models are fetched successfully, display them dynamically
        ?>
        <select id="chatbot_chatgpt_image_model_option" name="chatbot_chatgpt_image_model_option">
            <?php foreach ($image_models as $image_model): ?>
                <option value="<?php echo esc_attr($image_model['id']); ?>" <?php selected(esc_attr(get_option('chatbot_chatgpt_image_model_option')), $image_model['id']); ?>><?php echo esc_html($image_model['id']); ?></option>
            <?php endforeach; ?>
        </select>
        <?php  
    }

}

// Image Output Format Options Callback - Ver 1.9.5
function chatbot_chatgpt_image_output_format_callback($args) {

    // https://platform.openai.com/docs/guides/images
    // Options include png

    // Get the saved chatbot_chatgpt_image_output_format value or default to "png"
    $image_output_format = esc_attr(get_option('chatbot_chatgpt_image_output_format', 'png'));
    ?>
    <select id="chatbot_chatgpt_image_output_format" name="chatbot_chatgpt_image_output_format">
        <option value="png" <?php selected($image_output_format, 'png'); ?>>PNG</option>
    </select>
    <?php

}

// Image Output Size Options Callback - Ver 1.9.5
function chatbot_chatgpt_image_output_size_callback($args) {

    // https://platform.openai.com/docs/guides/images
    // The size of the generated images. Must be one of 256x256, 512x512, or 1024x1024 for dall-e-2.
    // Must be one of 1024x1024, 1792x1024, or 1024x1792 for dall-e-3 models.

    // Get the saved chatbot_chatgpt_image_model_option value or default to "dall-e-3"
    $model = esc_attr(get_option('chatbot_chatgpt_image_model_option', 'dall-e-3')); 

    // Get the saved chatbot_chatgpt_image_output_size value or default to "1024x1024"
    $image_output_size = esc_attr(get_option('chatbot_chatgpt_image_output_size', '1024x1024'));

    // If the $model is dall-e-2, then size muss be one of 256x256, 512x512, or 1024x1024
    if ($model == 'dall-e-2') {
        if ($image_output_size != '256x256' && $image_output_size != '512x512' && $image_output_size != '1024x1024') {
            $image_output_size = '1024x1024';
        }
    }
    // If the $model is dall-e-3, then size muss be one of 1024x1024, 1792x1024, or 1024x1792
    if ($model == 'dall-e-3') {
        if ($image_output_size != '1024x1024' && $image_output_size != '1792x1024' && $image_output_size != '1024x1792') {
            $image_output_size = '1024x1024';
        }
    }

    // Display the options based on model selection
    ?>
    <select id="chatbot_chatgpt_image_output_size" name="chatbot_chatgpt_image_output_size">
        <?php if ($model == 'dall-e-2'): ?>
            <option value="256x256" <?php selected($image_output_size, '256x256'); ?>>256x256</option>
            <option value="512x512" <?php selected($image_output_size, '512x512'); ?>>512x512</option>
            <option value="1024x1024" <?php selected($image_output_size, '1024x1024'); ?>>1024x1024</option>
        <?php elseif ($model == 'dall-e-3'): ?>
            <option value="1024x1024" <?php selected($image_output_size, '1024x1024'); ?>>1024x1024</option>
            <option value="1792x1024" <?php selected($image_output_size, '1792x1024'); ?>>1792x1024</option>
            <option value="1024x1792" <?php selected($image_output_size, '1024x1792'); ?>>1024x1792</option>
        <?php endif; ?>
    </select>
    <?php

}

// Image Output Quantity Options Callback - Ver 1.9.5
function chatbot_chatgpt_image_output_quantity_callback($args) {

    // https://platform.openai.com/docs/guides/images
    // Options include 1, 2, 3, or 4
    // n integer or null Optional Defaults to 1
    // The number of images to generate. Must be between 1 and 10. For dall-e-3, only n=1 is supported.

    $image_output_quantity = esc_attr(get_option('chatbot_chatgpt_image_output_quantity', '1'));

    $model = esc_attr(get_option('chatbot_chatgpt_image_model_option', 'dall-e-3'));
    
    // Display the options based on model selection
    ?>
        <select id="chatbot_chatgpt_image_output_quantity" name="chatbot_chatgpt_image_output_quantity">
        <?php if ($model == 'dall-e-3'): ?>
            <option value="1" <?php selected($image_output_quantity, '1'); ?>>1</option>
        <?php elseif ($model = 'dall-e-2'): ?>
            <option value="1" <?php selected($image_output_quantity, '1'); ?>>1</option>
            <option value="2" <?php selected($image_output_quantity, '2'); ?>>2</option>
            <option value="3" <?php selected($image_output_quantity, '3'); ?>>3</option>
            <option value="4" <?php selected($image_output_quantity, '4'); ?>>4</option>
            <option value="5" <?php selected($image_output_quantity, '5'); ?>>5</option>
            <option value="6" <?php selected($image_output_quantity, '6'); ?>>6</option>
            <option value="7" <?php selected($image_output_quantity, '7'); ?>>7</option>
            <option value="8" <?php selected($image_output_quantity, '8'); ?>>8</option>
            <option value="9" <?php selected($image_output_quantity, '9'); ?>>9</option>
            <option value="10" <?php selected($image_output_quantity, '10'); ?>>10</option>
        <?php endif; ?>
        </select>
        <?php

}

// Image Output Quality Options Callback - Ver 1.9.5
function chatbot_chatgpt_image_output_quality_callback($args) {

    // https://platform.openai.com/docs/guides/images
    // Options include standard

    // Get the saved chatbot_chatgpt_image_output_quality value or default to "3"
    $image_output_quality = esc_attr(get_option('chatbot_chatgpt_image_output_quality', 'standard'));
    ?>
    <select id="chatbot_chatgpt_image_output_quality" name="chatbot_chatgpt_image_output_quality">
        <option value="standard" <?php selected($image_output_quality, 'standard'); ?>>Standard</option>
        <option value="hd" <?php selected($image_output_quality, 'hd'); ?>>HD</option>
    </select>
    <?php

}

// Image Style Options Callback - Ver 1.9.5
function chatbot_chatgpt_image_style_output_callback($args) {

    // https://platform.openai.com/docs/guides/images
    // Options include standard

    // Get the saved chatbot_chatgpt_image_style_output value or default to "3"
    $image_style_output = esc_attr(get_option('chatbot_chatgpt_image_style_output', 'vivid'));
    ?>
    <select id="chatbot_chatgpt_image_style_output" name="chatbot_chatgpt_image_style_output">
        <option value="vivid" <?php selected($image_style_output, 'vivid'); ?>>Vivid</option>
        <option value="natural" <?php selected($image_style_output, 'natural'); ?>>Natural</option>
    </select>
    <?php

}   

// Whisper Model Option Callback - Ver 2.0.1
function chatbot_chatgpt_whisper_model_option_callback($args) {
    
        // https://platform.openai.com/docs/models/whisper
        // Options include whisper-1
    
        // Get the saved chatbot_chatgpt_whisper_model_option value or default to "whisper-1"
        $whisper_model_option = esc_attr(get_option('chatbot_chatgpt_whisper_model_option', 'whisper-1'));
    
        // Fetch models from the API
        $whisper_models = chatbot_openai_get_models();
    
        // Limit the models to whisper models
        $whisper_models = array_filter($whisper_models, function($whisper_model) {
            return strpos($whisper_model['id'], 'whisper') !== false;
        });
        
        // Check for errors
        if (is_string($whisper_models) && strpos($whisper_models, 'Error:') === 0) {
            // If there's an error, display the hardcoded list
            $whisper_model_option = esc_attr(get_option('chatbot_chatgpt_whisper_model_option', 'whisper-1'));
            ?>
            <select id="chatbot_chatgpt_whisper_model_option" name="chatbot_chatgpt_whisper_model_option">
                <option value="<?php echo esc_attr( 'whisper-1' ); ?>" <?php selected( $whisper_model_option, 'whisper-1' ); ?>><?php echo esc_html( 'whisper-1' ); ?></option>
            </select>
            <?php
        } else {
            // If models are fetched successfully, display them dynamically
            ?>
            <select id="chatbot_chatgpt_whisper_model_option" name="chatbot_chatgpt_whisper_model_option">
                <?php foreach ($whisper_models as $whisper_model): ?>
                    <option value="<?php echo esc_attr($whisper_model['id']); ?>" <?php selected(esc_attr(get_option('chatbot_chatgpt_whisper_model_option')), $whisper_model['id']); ?>><?php echo esc_html($whisper_model['id']); ?></option>
                <?php endforeach; ?>
            </select>
            <?php  
        }
}

// Whisper Output Format Options Callback - Ver 2.0.1
function chatbot_chatgpt_whisper_response_format_callback($args) {
    
        // https://platform.openai.com/docs/models/whisper
        // Options include mp3
    
        // Get the saved chatbot_chatgpt_whisper_response_format value or default to "text"
        $whisper_response_format = esc_attr(get_option('chatbot_chatgpt_whisper_response_format', 'text'));
        ?>
        <select id="chatbot_chatgpt_whisper_response_format" name="chatbot_chatgpt_whisper_response_format">
            <option value="text" <?php selected($whisper_response_format, 'text'); ?>>Text</option>
        </select>
        <?php
    
}

