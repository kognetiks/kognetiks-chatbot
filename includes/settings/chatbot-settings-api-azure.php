<?php
/**
 * Kognetiks Chatbot - Settings - API/Azure OpenAI Page
 *
 * This file contains the code for the Chatbot settings page.
 * It allows users to configure the API key and other parameters
 * required to access the Azure OpenAI API from their own account.
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// API/Azure OpenAI Settings section callback - Ver 2.2.6
function chatbot_azure_model_settings_section_callback($args) {

    ?>
    <p>Configure the default settings for the Chatbot plugin to use Azure OpenAI for chat, voice, and image generation.  Start by adding your API key then selecting your choices below.  Don't forget to click "Save Settings" at the very bottom of this page.</p>
    <p>More information about Azure OpenAI models and their capability can be found at <a href="https://azure.microsoft.com/en-us/products/ai-model-catalog" target="_blank">https://azure.microsoft.com/en-us/products/ai-model-catalog</a>.</p>
    <p><b><i>Don't forget to click </i><code>Save Settings</code><i> to save any changes your might make.</i></b></p>
    <p style="background-color: #e0f7fa; padding: 10px;"><b>For an explanation of the API/Azure OpenAI Settings and additional documentation please click <a href="?page=chatbot-chatgpt&tab=support&dir=api-azure-openai-settings&file=api-azure-openai-model-settings.md">here</a>.</b></p>
    <?php

}

function chatbot_azure_api_general_section_callback($args) {

    ?>
    <p>Configure the settings for the plugin by adding your API key. This plugin requires an API key from OpenAI to function. You can obtain an API key by signing up at <a href="https://portal.azure.com/#home" target="_blank">https://portal.azure.com/#home</a>.</p>
    <?php

}

function chatbot_azure_api_chat_section_callback($args) {

    ?>
    <p>Configure the settings for the plugin when using chat models. Depending on the OpenAI model you choose, the maximum tokens may be as high as 4097. The default is 150. For more information about the maximum tokens parameter, please see <a href="https://learn.microsoft.com/en-us/azure/ai-services/openai/reference" target="_blank">https://learn.microsoft.com/en-us/azure/ai-services/openai/reference</a>. Enter a conversation context to help the model understand the conversation. See the default for ideas. Some example shortcodes include:</p>
    <p><b>NOTE:</b> Enter your API key (above), click <code>Save Settings</code> at the bottom of this page, in order to retrieve the full list of available models.</p>
    <ul style="list-style-type: disc; list-style-position: inside; padding-left: 1em;">
        <li><code>&#91;chatbot&#93;</code> - Default chat model, style is floating</li>
        <li><code>&#91;chatbot style="floating" model="gpt-4o-2024-11-20"&#93;</code> - Style is floating, specific model</li>
        <li><code>&#91;chatbot style="embedded" model="gpt-4o-2024-11-20"&#93;</code> - Style is embedded, default chat model</li>
        <!-- <li><code>&#91;chatbot style=embedded model=chat&#93;</code> - Style is embedded, default chat model</li> -->
    </ul>
    <?php

}

function chatbot_azure_api_image_section_callback($args) {

    ?>
    <p>Configure the settings for the plugin when using image models. Some example shortcodes include:</p>
    <ul style="list-style-type: disc; list-style-position: inside; padding-left: 1em;">
        <li><code>&#91;chatbot style="floating" model="'dall-e-2"&#93;</code> - Style is floating, specific model</li>
        <li><code>&#91;chatbot style="embedded" model="'dall-e-3"&#93;</code> - Style is embedded, default image model</li>
        <!-- <li><code>&#91;chatbot style=embedded model=image&#93;</code> - Style is embedded, default image model</li> -->
    </ul>
    <?php

}

function chatbot_azure_api_voice_section_callback($args) {

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

// Whisper Section Callback - Ver 2.2.6
function chatbot_azure_api_whisper_section_callback($args) {

    ?>
    <p>Configure the settings for the plugin when using whisper models. Some example shortcodes include:</p>
    <ul style="list-style-type: disc; list-style-position: inside; padding-left: 1em;">
        <li><code>&#91;chatbot style="floating" model="whisper-1"&#93;</code> - Style is floating, specific model</li>
        <li><code>&#91;chatbot style="embedded" model="whisper-1"&#93;</code> - Style is embedded, specific model</li>
        <!-- <li><code>&#91;chatbot style=embedded model=whisper&#93;</code> - Style is embedded, default whisper model</li> -->
    </ul>
    <?php

}

function chatbot_azure_api_advanced_section_callback($args) {

    ?>
    <p><strong>CAUTION</strong>: Configure the advanced settings for the plugin. Enter the base URL for the Azure OpenAI API.  The default is <code>https://YOUR_RESOURCE_NAME.openai.azure.com/openai/deployments/DEPLOYMENT_NAME/chat/completions?api-version=YYYY-MM-DD</code>.  Be sure to replace YOUR_RESOURCE_NAME, DEPLOYMENT_NAME and YYYY-MM-DD with your details.</p>
    <?php

}

// API key field callback
function chatbot_azure_api_key_callback($args) {

    $api_key = esc_attr(get_option('chatbot_azure_api_key'));

    // Decrypt the API key - Ver 2.2.6
    $api_key = chatbot_chatgpt_decrypt_api_key($api_key);

    ?>
    <input type="password" id="chatbot_azure_api_key" name="chatbot_azure_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text"  autocomplete="off">
    <?php

}

// Azure OpenAI Models
function chatbot_azure_model_choice_callback($args) {
  
    // Get the saved chatbot_azure_model_choice value or default to "gpt-3.5-turbo"
    $model_choice = esc_attr(get_option('chatbot_azure_model_choice', 'gpt-3.5-turbo'));

    // Fetch models from the API
    $models = chatbot_azure_get_models();

    // DIAG - Ver 2.2.6

    // Limit the models to chat models
    $models = array_filter($models, function($model) {
        return strpos($model['id'], 'gpt') !== false;
    });

    // Check for errors
    if (is_string($models) && strpos($models, 'Error:') === 0) {
        // If there's an error, display the hardcoded list
        $model_choice = esc_attr(get_option('chatbot_azure_model_choice', 'gpt-3.5-turbo'));
        ?>
        <select id="chatbot_azure_model_choice" name="chatbot_azure_model_choice">
            <option value="<?php echo esc_attr( 'gpt-4-1106-preview' ); ?>" <?php selected( $model_choice, 'gpt-4-1106-preview' ); ?>><?php echo esc_html( 'gpt-4-1106-preview' ); ?></option>
            <option value="<?php echo esc_attr( 'gpt-4' ); ?>" <?php selected( $model_choice, 'gpt-4' ); ?>><?php echo esc_html( 'gpt-4' ); ?></option>
            <option value="<?php echo esc_attr( 'gpt-3.5-turbo' ); ?>" <?php selected( $model_choice, 'gpt-3.5-turbo' ); ?>><?php echo esc_html( 'gpt-3.5-turbo' ); ?></option>
        </select>
        <?php
    } else {
        // If models are fetched successfully, display them dynamically
        ?>
        <select id="chatbot_azure_model_choice" name="chatbot_azure_model_choice">
            <?php foreach ($models as $model): ?>
                <option value="<?php echo esc_attr($model['id']); ?>" <?php selected(esc_attr(get_option('chatbot_azure_model_choice')), $model['id']); ?>><?php echo esc_html($model['id']); ?></option>
            <?php endforeach; ?>
            ?>
        </select>
        <?php
    }

}

// Max Tokens choice - Ver 2.2.6
function chatbot_azure_max_tokens_setting_callback($args) {

    // Get the saved chatbot_azure_max_tokens_setting or default to 1000
    $max_tokens = esc_attr(get_option('chatbot_azure_max_tokens_setting', '1000'));
    // Allow for a range of tokens between 100 and 16384 in 100-step increments - Ver 2.2.6
    ?>
    <select id="chatbot_azure_max_tokens_setting" name="chatbot_azure_max_tokens_setting">
        <?php
        for ($i=100; $i<=16300; $i+=100) {
            echo '<option value="' . esc_attr($i) . '" ' . selected($max_tokens, (string)$i, false) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php

}

// Conversation Context - Ver 2.2.6
function chatbot_azure_conversation_context_callback($args) {

    // Get the value of the setting we've registered with register_setting()
    $chatbot_azure_conversation_context = esc_attr(get_option('chatbot_azure_conversation_context'));

    // Check if the option has been set, if not, use a default value
    if (empty($chatbot_azure_conversation_context)) {
        $chatbot_azure_conversation_context = "You are a versatile, friendly, and helpful assistant designed to support me in a variety of tasks that responds in Markdown.";
        // Save the default value into the option
        update_option('chatbot_azure_conversation_context', $chatbot_azure_conversation_context);
    }

    ?>
    <!-- Define the textarea field. -->
    <textarea id='chatbot_azure_conversation_context' name='chatbot_azure_conversation_context' rows='5' cols='50' maxlength='12500'><?php echo esc_html(stripslashes($chatbot_azure_conversation_context)); ?></textarea>
    <?php

}

// Set chatbot_azure_temperature - Ver 2.2.6
function chatbot_azure_temperature_callback($args) {

    $temperature = esc_attr(get_option('chatbot_azure_temperature', 0.50));
    ?>
    <select id="chatbot_azure_temperature" name="chatbot_azure_temperature">
        <?php
        for ($i = 0.01; $i <= 2.01; $i += 0.01) {
            echo '<option value="' . $i . '" ' . selected($temperature, (string)$i) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php

}

// Set chatbot_azure_top_p - Ver 2.2.6
function chatbot_azure_top_p_callback($args) {

    $top_p = esc_attr(get_option('chatbot_azure_top_p', 1.00));
    ?>
    <select id="chatbot_azure_top_p" name="chatbot_azure_top_p">
        <?php
        for ($i = 0.01; $i <= 1.01; $i += 0.01) {
            echo '<option value="' . $i . '" ' . selected($top_p, (string)$i) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php

}

// Base URL for the Azure OpenAI API - Ver 2.2.6
function chatbot_azure_base_url_callback($args) {

    $chatbot_azure_base_url = esc_attr(get_option('chatbot_azure_base_url', 'https://YOUR_RESOURCE_NAME.openai.azure.com/deployments/DEPLOYMENT_NAME/chat/completions?api-version=YYYY-MM-DD'));
    ?>
    <input type="text" id="chatbot_azure_base_url" name="chatbot_azure_base_url" value="<?php echo esc_attr( $chatbot_azure_base_url ); ?>" class="regular-text">
    <?php

}

// API Resource Name for the Azure OpenAI API - Ver 2.2.6
function chatbot_azure_resource_name_callback($args) {

    $chatbot_azure_resource_name = esc_attr(get_option('chatbot_azure_resource_name', 'YOUR_RESOURCE_NAME'));
    ?>
    <input type="text" id="chatbot_azure_resource_name" name="chatbot_azure_resource_name" value="<?php echo esc_attr( $chatbot_azure_resource_name ); ?>" class="regular-text">
    <?php

}

// API Deployment Name for the Azure OpenAI API - Ver 2.2.6
function chatbot_azure_deployment_name_callback($args) {

    $chatbot_azure_deployment_name = esc_attr(get_option('chatbot_azure_deployment_name', 'DEPLOYMENT_NAME'));
    ?>
    <input type="text" id="chatbot_azure_deployment_name" name="chatbot_azure_deployment_name" value="<?php echo esc_attr( $chatbot_azure_deployment_name ); ?>" class="regular-text">
    <?php

}

// API Version for the Azure OpenAI API - Ver 2.2.6
function chatbot_azure_api_version_callback($args) {

    $chatbot_azure_api_version = esc_attr(get_option('chatbot_azure_api_version', '2024-03-01-preview'));
    ?>
    <input type="text" id="chatbot_azure_api_version" name="chatbot_azure_api_version" value="<?php echo esc_attr( $chatbot_azure_api_version ); ?>" class="regular-text">
    <?php

}

// Timeout Settings Callback - Ver 2.2.6
function chatbot_azure_timeout_setting_callback($args) {

    // Get the saved chatbot_azure_timeout value or default to 240
    $timeout = esc_attr(get_option('chatbot_azure_timeout_setting', 240));

    // Allow for a range of tokens between 5 and 500 in 5-step increments - Ver 2.2.6
    ?>
    <select id="chatbot_azure_timeout_setting" name="chatbot_azure_timeout_setting">
        <?php
        for ($i=5; $i<=500; $i+=5) {
            echo '<option value="' . esc_attr($i) . '" ' . selected($timeout, (string)$i, false) . '>' . esc_html($i) . '</option>';
        }
        ?>
    </select>
    <?php
    
}

// Voice Model Options Callback - Ver 2.2.6
function chatbot_azure_voice_model_option_callback($args) {

    // Get the saved chatbot_azure_model_choice value or default to "gpt-3.5-turbo"
    $voice_model_option = esc_attr(get_option('chatbot_azure_voice_model_option', 'tts-1-hd'));

    // Fetch models from the API
    $voice_models = chatbot_azure_get_models();

    // Limit the models to voice models
    $voice_models = array_filter($voice_models, function($voice_model) {
        return strpos($voice_model['id'], 'tts') !== false;
    });
    
    // Check for errors
    if (is_string($voice_models) && strpos($voice_models, 'Error:') === 0) {
        // If there's an error, display the hardcoded list
        $voice_model_option = esc_attr(get_option('chatbot_azure_voice_model_option', 'tts-1-hd'));
        ?>
        <select id="chatbot_azure_voice_model_option" name="chatbot_azure_voice_model_option">
            <option value="<?php echo esc_attr( 'tts-1' ); ?>" <?php selected( $voice_model_option, 'tts-1' ); ?>><?php echo esc_html( 'tts-1' ); ?></option>
            <option value="<?php echo esc_attr( 'tts-1-1106' ); ?>" <?php selected( $voice_model_option, 'tts-1-1106' ); ?>><?php echo esc_html( 'tts-1-1106' ); ?></option>
            <option value="<?php echo esc_attr( 'tts-1-hd' ); ?>" <?php selected( $voice_model_option, 'tts-1-hd' ); ?>><?php echo esc_html( 'tts-1-hd' ); ?></option>
            <option value="<?php echo esc_attr( 'tts-1-hd-1106' ); ?>" <?php selected( $voice_model_option, 'tts-1-hd-1106' ); ?>><?php echo esc_html( 'tts-1-hd-1106' ); ?></option>
        </select>
        <?php
    } else {
        // If models are fetched successfully, display them dynamically
        ?>
        <select id="chatbot_azure_voice_model_option" name="chatbot_azure_voice_model_option">
            <?php foreach ($voice_models as $voice_model): ?>
                <option value="<?php echo esc_attr($voice_model['id']); ?>" <?php selected(esc_attr(get_option('chatbot_azure_voice_model_option')), $voice_model['id']); ?>><?php echo esc_html($voice_model['id']); ?></option>
            <?php endforeach; ?>
        </select>
        <?php  
    }

}

// Voice Options Callback - Ver 2.2.6
function chatbot_azure_voice_option_callback($args) {

    // Get the saved chatbot_azure_voice_options value or default to "Alloy"
    $voice_option = esc_attr(get_option('chatbot_azure_voice_option', 'alloy'));
    ?>
    <select id="chatbot_azure_voice_option" name="chatbot_azure_voice_option">
        <option value="alloy" <?php selected($voice_option, 'alloy'); ?>>Alloy</option>
        <option value="echo" <?php selected($voice_option, 'echo'); ?>>Echo</option>
        <option value="fable" <?php selected($voice_option, 'fable'); ?>>Fable</option>
        <option value="onyx" <?php selected($voice_option, 'onyx'); ?>>Onyx</option>
        <option value="nova" <?php selected($voice_option, 'nova'); ?>>Nova</option>
        <option value="shimmer" <?php selected($voice_option, 'shimmer'); ?>>Shimmer</option>
    </select>
    <?php
}

// Voice Output Options Callback - Ver 2.2.6
function chatbot_azure_audio_output_format_callback($args) {

    // Get the saved chatbot_azure_voice_output_options value or default to "mp3"
    $audio_output_format = esc_attr(get_option('chatbot_azure_audio_output_format', 'mp3'));
    ?>
    <select id="chatbot_azure_audio_output_format" name="chatbot_azure_audio_output_format">
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
function chatbot_azure_read_aloud_option_callback($args) {

    // Get the saved chatbot_azure_read_aloud_option value or default to "yes"
    $read_aloud_option = esc_attr(get_option('chatbot_azure_read_aloud_option', 'yes'));
    ?>
    <select id="chatbot_azure_read_aloud_option" name="chatbot_azure_read_aloud_option">
        <option value="yes" <?php selected($read_aloud_option, 'yes'); ?>>Yes</option>
        <option value="no" <?php selected($read_aloud_option, 'no'); ?>>No</option>
    </select>
    <?php

}

// Image Model Options Callback - Ver 2.2.6
function chatbot_azure_image_model_option_callback($args) {

    // Get the saved chatbot_azure_model_option value or default to "'dall-e-3"
    $image_model_option = esc_attr(get_option('chatbot_azure_image_model_option', 'dall-e-3'));

    // Fetch models from the API
    $image_models = chatbot_azure_get_models();

    // Limit the models to image models
    $image_models = array_filter($image_models, function($image_model) {
        return strpos($image_model['id'], 'dall-e') !== false;
    });
    
    // Check for errors
    if (is_string($image_models) && strpos($image_models, 'Error:') === 0) {
        // If there's an error, display the hardcoded list
        $image_model_option = esc_attr(get_option('chatbot_azure_image_model_option', 'dall-e-3'));
        ?>
        <select id="chatbot_azure_image_model_option" name="chatbot_azure_image_model_option">
            <option value="<?php echo esc_attr( 'dall-e-2' ); ?>" <?php selected( $image_model_option, 'dall-e-2' ); ?>><?php echo esc_html( 'dall-e-2' ); ?></option>
            <option value="<?php echo esc_attr( 'dall-e-3' ); ?>" <?php selected( $image_model_option, 'dall-e-3' ); ?>><?php echo esc_html( 'dall-e-3' ); ?></option>
        </select>
        <?php
    } else {
        // If models are fetched successfully, display them dynamically
        ?>
        <select id="chatbot_azure_image_model_option" name="chatbot_azure_image_model_option">
            <?php foreach ($image_models as $image_model): ?>
                <option value="<?php echo esc_attr($image_model['id']); ?>" <?php selected(esc_attr(get_option('chatbot_azure_image_model_option')), $image_model['id']); ?>><?php echo esc_html($image_model['id']); ?></option>
            <?php endforeach; ?>
        </select>
        <?php  
    }

}

// Image Output Format Options Callback - Ver 2.2.6
function chatbot_azure_image_output_format_callback($args) {

    // Get the saved chatbot_azure_image_output_format value or default to "png"
    $image_output_format = esc_attr(get_option('chatbot_azure_image_output_format', 'png'));
    ?>
    <select id="chatbot_azure_image_output_format" name="chatbot_azure_image_output_format">
        <option value="png" <?php selected($image_output_format, 'png'); ?>>PNG</option>
    </select>
    <?php

}

// Image Output Size Options Callback - Ver 2.2.6
function chatbot_azure_image_output_size_callback($args) {

    // The size of the generated images. Must be one of 256x256, 512x512, or 1024x1024 for dall-e-2.
    // Must be one of 1024x1024, 1792x1024, or 1024x1792 for dall-e-3 models.

    // Get the saved chatbot_azure_image_model_option value or default to "dall-e-3"
    $model = esc_attr(get_option('chatbot_azure_image_model_option', 'dall-e-3')); 

    // Get the saved chatbot_azure_image_output_size value or default to "1024x1024"
    $image_output_size = esc_attr(get_option('chatbot_azure_image_output_size', '1024x1024'));

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
    <select id="chatbot_azure_image_output_size" name="chatbot_azure_image_output_size">
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

// Image Output Quantity Options Callback - Ver 2.2.6
function chatbot_azure_image_output_quantity_callback($args) {

    // Options include 1, 2, 3, or 4
    // n integer or null Optional Defaults to 1
    // The number of images to generate. Must be between 1 and 10. For dall-e-3, only n=1 is supported.

    $image_output_quantity = esc_attr(get_option('chatbot_azure_image_output_quantity', '1'));

    $model = esc_attr(get_option('chatbot_azure_image_model_option', 'dall-e-3'));
    
    // Display the options based on model selection
    ?>
        <select id="chatbot_azure_image_output_quantity" name="chatbot_azure_image_output_quantity">
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

// Image Output Quality Options Callback - Ver 2.2.6
function chatbot_azure_image_output_quality_callback($args) {

    // Get the saved chatbot_azure_image_output_quality value or default to "3"
    $image_output_quality = esc_attr(get_option('chatbot_azure_image_output_quality', 'standard'));
    ?>
    <select id="chatbot_azure_image_output_quality" name="chatbot_azure_image_output_quality">
        <option value="standard" <?php selected($image_output_quality, 'standard'); ?>>Standard</option>
        <option value="hd" <?php selected($image_output_quality, 'hd'); ?>>HD</option>
    </select>
    <?php

}

// Image Style Options Callback - Ver 2.2.6
function chatbot_azure_image_style_output_callback($args) {

    // Get the saved chatbot_azure_image_style_output value or default to "3"
    $image_style_output = esc_attr(get_option('chatbot_azure_image_style_output', 'vivid'));
    ?>
    <select id="chatbot_azure_image_style_output" name="chatbot_azure_image_style_output">
        <option value="vivid" <?php selected($image_style_output, 'vivid'); ?>>Vivid</option>
        <option value="natural" <?php selected($image_style_output, 'natural'); ?>>Natural</option>
    </select>
    <?php

}   

// Whisper Model Option Callback - Ver 2.2.6
function chatbot_azure_whisper_model_option_callback($args) {
    
        // Get the saved chatbot_azure_whisper_model_option value or default to "whisper-1"
        $whisper_model_option = esc_attr(get_option('chatbot_azure_whisper_model_option', 'whisper-1'));
    
        // Fetch models from the API
        $whisper_models = chatbot_azure_get_models();
    
        // Limit the models to whisper models
        $whisper_models = array_filter($whisper_models, function($whisper_model) {
            return strpos($whisper_model['id'], 'whisper') !== false;
        });
        
        // Check for errors
        if (is_string($whisper_models) && strpos($whisper_models, 'Error:') === 0) {
            // If there's an error, display the hardcoded list
            $whisper_model_option = esc_attr(get_option('chatbot_azure_whisper_model_option', 'whisper-1'));
            ?>
            <select id="chatbot_azure_whisper_model_option" name="chatbot_azure_whisper_model_option">
                <option value="<?php echo esc_attr( 'whisper-1' ); ?>" <?php selected( $whisper_model_option, 'whisper-1' ); ?>><?php echo esc_html( 'whisper-1' ); ?></option>
            </select>
            <?php
        } else {
            // If models are fetched successfully, display them dynamically
            ?>
            <select id="chatbot_azure_whisper_model_option" name="chatbot_azure_whisper_model_option">
                <?php foreach ($whisper_models as $whisper_model): ?>
                    <option value="<?php echo esc_attr($whisper_model['id']); ?>" <?php selected(esc_attr(get_option('chatbot_azure_whisper_model_option')), $whisper_model['id']); ?>><?php echo esc_html($whisper_model['id']); ?></option>
                <?php endforeach; ?>
            </select>
            <?php  
        }
}

// Whisper Output Format Options Callback - Ver 2.2.6
function chatbot_azure_whisper_response_format_callback($args) {
    
        // Get the saved chatbot_azure_whisper_response_format value or default to "text"
        $whisper_response_format = esc_attr(get_option('chatbot_azure_whisper_response_format', 'text'));
        ?>
        <select id="chatbot_azure_whisper_response_format" name="chatbot_azure_whisper_response_format">
            <option value="text" <?php selected($whisper_response_format, 'text'); ?>>Text</option>
        </select>
        <?php
    
}

// Register API settings
function chatbot_azure_api_settings_init() {

    // DIAG - Diagnostics

    add_settings_section(
        'chatbot_azure_model_settings_section',
        'API/Azure OpenAI Settings',
        'chatbot_azure_model_settings_section_callback',
        'chatbot_azure_model_settings_general'
    );

    // API/Azure OpenAI settings tab - Ver 2.2.6
    register_setting('chatbot_azure_api_model', 'chatbot_azure_api_key', 'chatbot_chatgpt_sanitize_api_key');

    add_settings_section(
        'chatbot_azure_api_general_section',
        'Azure OpenAI API Settings',
        'chatbot_azure_api_general_section_callback',
        'chatbot_azure_api_general'
    );

    add_settings_field(
        'chatbot_azure_api_key',
        'Azure OpenAI API Key',
        'chatbot_azure_api_key_callback',
        'chatbot_azure_api_general',
        'chatbot_azure_api_general_section'
    );

    // Advanced Model Settings - Ver 2.2.6
    register_setting('chatbot_azure_api_model', 'chatbot_azure_base_url'); // Ver 1.8.1
    register_setting('chatbot_azure_api_model', 'chatbot_azure_resource_name'); // Ver 2.2.6
    register_setting('chatbot_azure_api_model', 'chatbot_azure_deployment_name'); // Ver 2.2.6
    register_setting('chatbot_azure_api_model', 'chatbot_azure_api_version'); // Ver 2.2.6
    register_setting('chatbot_azure_api_model', 'chatbot_azure_timeout_setting'); // Ver 1.8.8

    add_settings_section(
        'chatbot_azure_api_advanced_section',
        'Advanced API Settings',
        'chatbot_azure_api_advanced_section_callback',
        'chatbot_azure_api_advanced'
    );

    // Set the base URL for the API - Ver 2.2.6
    // add_settings_field(
    //     'chatbot_azure_base_url',
    //     'Base URL for API',
    //     'chatbot_azure_base_url_callback',
    //     'chatbot_azure_api_advanced',
    //     'chatbot_azure_api_advanced_section'
    // );

    // API Resrouce Name - Ver 2.2.6
    add_settings_field(
        'chatbot_azure_resource_name',
        'API Resource Name',
        'chatbot_azure_resource_name_callback',
        'chatbot_azure_api_advanced',
        'chatbot_azure_api_advanced_section'
    );

    // API Deployment Name - Ver 2.2.6
    add_settings_field(
        'chatbot_azure_deployment_name',
        'API Deployment Name',
        'chatbot_azure_deployment_name_callback',
        'chatbot_azure_api_advanced',
        'chatbot_azure_api_advanced_section'
    );

    // API Version - Ver 2.2.6
    add_settings_field(
        'chatbot_azure_api_version',
        'API Version',
        'chatbot_azure_api_version_callback',
        'chatbot_azure_api_advanced',
        'chatbot_azure_api_advanced_section'
    );

    // Timeout setting - Ver 2.2.6
    add_settings_field(
        'chatbot_azure_timeout_setting',
        'Timeout Setting (in seconds)',
        'chatbot_azure_timeout_setting_callback',
        'chatbot_azure_api_advanced',
        'chatbot_azure_api_advanced_section'
    );

    // Chat Options - Ver 2.2.6
    register_setting('chatbot_azure_api_model', 'chatbot_azure_api_enabled');
    register_setting('chatbot_azure_api_model', 'chatbot_azure_model_choice');
    register_setting('chatbot_azure_api_model', 'chatbot_azure_max_tokens_setting'); // Max Tokens setting options - Ver 2.2.6
    register_setting('chatbot_azure_api_model', 'chatbot_azure_conversation_context'); // Conversation Context - Ver 2.2.6
    register_setting('chatbot_azure_api_model', 'chatbot_azure_temperature'); // Temperature - Ver 2.2.6
    register_setting('chatbot_azure_api_model', 'chatbot_azure_top_p'); // Top P - Ver 2.2.6

    add_settings_section(
        'chatbot_azure_api_chat_section',
        'Chat Settings',
        'chatbot_azure_api_chat_section_callback',
        'chatbot_azure_api_chat'
    );

    add_settings_field(
        'chatbot_azure_model_choice',
        'Azure OpenAI Model Default',
        'chatbot_azure_model_choice_callback',
        'chatbot_azure_api_chat',
        'chatbot_azure_api_chat_section'
    );

    // Setting to adjust in small increments the number of Max Tokens - Ver 2.2.6
    add_settings_field(
        'chatbot_azure_max_tokens_setting',
        'Maximum Tokens Setting',
        'chatbot_azure_max_tokens_setting_callback',
        'chatbot_azure_api_chat',
        'chatbot_azure_api_chat_section'
    );

    // Setting to adjust the conversation context - Ver 2.2.6
    add_settings_field(
        'chatbot_azure_conversation_context',
        'Conversation Context',
        'chatbot_azure_conversation_context_callback',
        'chatbot_azure_api_chat',
        'chatbot_azure_api_chat_section'
    );

    // Temperature - Ver 2.2.6
    add_settings_field(
        'chatbot_azure_temperature',
        'Temperature',
        'chatbot_azure_temperature_callback',
        'chatbot_azure_api_chat',
        'chatbot_azure_api_chat_section'
    );

    // Top P - Ver 2.2.6
    add_settings_field(
        'chatbot_azure_top_p',
        'Top P',
        'chatbot_azure_top_p_callback',
        'chatbot_azure_api_chat',
        'chatbot_azure_api_chat_section'
    );

    // Voice Options - Ver 2.2.6
    register_setting('chatbot_azure_api_model', 'chatbot_azure_voice_model_option'); // Ver 2.2.6
    register_setting('chatbot_azure_api_model', 'chatbot_azure_voice_option'); // Ver 2.2.6
    register_setting('chatbot_azure_api_model', 'chatbot_azure_audio_output_format'); // Ver 2.2.6
    register_setting('chatbot_azure_api_model', 'chatbot_azure_read_aloud_option'); // Ver 2.2.6
    
    // Voice Options - Ver 2.2.6
    add_settings_section(
        'chatbot_azure_api_voice_section',
        'Voice Settings (Text to Speech)',
        'chatbot_azure_api_voice_section_callback',
        'chatbot_azure_api_voice'
    );

    // Voice Option - Ver 2.2.6
    add_settings_field(
        'chatbot_azure_voice_model_option',
        'Voice Model Default',
        'chatbot_azure_voice_model_option_callback',
        'chatbot_azure_api_voice',
        'chatbot_azure_api_voice_section'
    );

    // Voice Option
    add_settings_field(
        'chatbot_azure_voice_option',
        'Voice',
        'chatbot_azure_voice_option_callback',
        'chatbot_azure_api_voice',
        'chatbot_azure_api_voice_section'
    );

    // Audio Output Options
    add_settings_field(
        'chatbot_azure_audio_output_format',
        'Audio Output Option',
        'chatbot_azure_audio_output_format_callback',
        'chatbot_azure_api_voice',
        'chatbot_azure_api_voice_section'
    );

    // Allow Read Aloud - Ver 2.2.6
    add_settings_field(
        'chatbot_azure_read_aloud_option',
        'Allow Read Aloud',
        'chatbot_azure_read_aloud_option_callback',
        'chatbot_azure_api_voice',
        'chatbot_azure_api_voice_section'
    );

    // Image Options - Ver 2.2.6
    register_setting('chatbot_azure_api_model', 'chatbot_azure_image_model_option'); // Ver 2.2.6
    register_setting('chatbot_azure_api_model', 'chatbot_azure_image_output_format'); // Ver 2.2.6
    register_setting('chatbot_azure_api_model', 'chatbot_azure_image_output_size'); // Ver 2.2.6
    register_setting('chatbot_azure_api_model', 'chatbot_azure_image_output_quantity'); // Ver 2.2.6
    register_setting('chatbot_azure_api_model', 'chatbot_azure_image_output_quality'); // Ver 2.2.6
    register_setting('chatbot_azure_api_model', 'chatbot_azure_image_style_output'); // Ver 2.2.6

    // Image Options - Ver 2.2.6
    add_settings_section(
        'chatbot_azure_api_image_section',
        'Image Settings',
        'chatbot_azure_api_image_section_callback',
        'chatbot_azure_api_image'
    );

    add_settings_field(
        'chatbot_azure_image_model_option',
        'Image Model Default',
        'chatbot_azure_image_model_option_callback',
        'chatbot_azure_api_image',
        'chatbot_azure_api_image_section'
    );

    add_settings_field(
        'chatbot_azure_image_output_format',
        'Image Output Option',
        'chatbot_azure_image_output_format_callback',
        'chatbot_azure_api_image',
        'chatbot_azure_api_image_section'
    );

    add_settings_field(
        'chatbot_azure_image_output_size',
        'Image Output Size',
        'chatbot_azure_image_output_size_callback',
        'chatbot_azure_api_image',
        'chatbot_azure_api_image_section'
    );

    add_settings_field(
        'chatbot_azure_image_output_quantity',
        'Image Quantity',
        'chatbot_azure_image_output_quantity_callback',
        'chatbot_azure_api_image',
        'chatbot_azure_api_image_section'
    );

    add_settings_field(
        'chatbot_azure_image_output_quality',
        'Image Quality',
        'chatbot_azure_image_output_quality_callback',
        'chatbot_azure_api_image',
        'chatbot_azure_api_image_section'
    );

    add_settings_field(
        'chatbot_azure_image_style_output',
        'Image Style Output',
        'chatbot_azure_image_style_output_callback',
        'chatbot_azure_api_image',
        'chatbot_azure_api_image_section'
    );

    // Whisper Options - Ver 2.2.6
    register_setting('chatbot_azure_api_model', 'chatbot_azure_whisper_model_option');
    register_setting('chatbot_azure_api_model', 'chatbot_azure_whisper_response_format');

    // Image Options - Ver 2.2.6
    add_settings_section(
        'chatbot_azure_api_whisper_section',
        'Whisper Settings (Speech to Text)',
        'chatbot_azure_api_whisper_section_callback',
        'chatbot_azure_api_whisper'
    );

    add_settings_field(
        'chatbot_azure_whisper_model_option',
        'Whisper Model Default',
        'chatbot_azure_whisper_model_option_callback',
        'chatbot_azure_api_whisper',
        'chatbot_azure_api_whisper_section'
    );

    add_settings_field(
        'chatbot_azure_whisper_response_format',
        'Whisper Output Option',
        'chatbot_azure_whisper_response_format_callback',
        'chatbot_azure_api_whisper',
        'chatbot_azure_api_whisper_section'
    );

}
add_action('admin_init', 'chatbot_azure_api_settings_init');
