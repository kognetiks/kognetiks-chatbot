<?php
/**
 * Kognetiks Chatbot - API Endpoints - Ver 2.2.2  - Revised - Ver 2.2.6
 *
 * This file contains the code for managing the API endpoints.
 *
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

/**
 * Loopback names that may use http. Any other host must use https.
 *
 * @param string $host Parsed host, without brackets.
 * @return bool
 */
function chatbot_chatgpt_provider_base_url_host_is_local( $host ) {

    $host = strtolower( trim( (string) $host, '[]' ) );
    return in_array( $host, array( 'localhost', '127.0.0.1', '::1' ), true );

}

/**
 * Hosts that are not provider endpoints: cloud metadata, this-network, and obfuscated IPs.
 *
 * @param string $host Parsed host, without brackets.
 * @return bool
 */
function chatbot_chatgpt_provider_base_url_host_is_blocked( $host ) {

    $host = strtolower( trim( (string) $host, '[]' ) );
    if ( in_array( $host, array( 'metadata.google.internal', 'metadata.internal' ), true ) ) {
        return true;
    }
    // Decimal and hex forms of an address, which some clients still dial.
    if ( preg_match( '/^(0x[0-9a-f]+|\d+)$/i', $host ) ) {
        return true;
    }
    if ( preg_match( '/^\d{1,3}(\.\d{1,3}){3}$/', $host ) ) {
        $octets = explode( '.', $host );
        $nums   = array();
        foreach ( $octets as $octet ) {
            if ( strlen( $octet ) > 1 && '0' === $octet[0] ) {
                return true;
            }
            $number = (int) $octet;
            if ( $number > 255 ) {
                return true;
            }
            $nums[] = $number;
        }
        if ( 0 === $nums[0] || 255 === $nums[0] ) {
            return true;
        }
        // 169.254.0.0/16, including the cloud metadata address.
        if ( 169 === $nums[0] && 254 === $nums[1] ) {
            return true;
        }
        return false;
    }
    if ( function_exists( 'filter_var' ) && filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
        $packed = inet_pton( $host );
        if ( false === $packed ) {
            return true;
        }
        $first  = ord( $packed[0] );
        $second = ord( $packed[1] );
        // fe80::/10 link-local.
        if ( 0xfe === $first && ( $second & 0xc0 ) === 0x80 ) {
            return true;
        }
        if ( inet_pton( '::' ) === $packed ) {
            return true;
        }
    }
    return false;

}

/**
 * Accept a provider base URL, or return empty when it must not be requested.
 *
 * https is required. http is allowed only for localhost, 127.0.0.1, and ::1.
 * A username, password, or empty host is rejected. Other https hosts stay
 * allowed so Azure resources and OpenAI-compatible proxies can be configured.
 *
 * @param mixed $url Submitted or stored URL.
 * @return string
 */
function chatbot_chatgpt_validate_provider_base_url( $url ) {

    if ( ! is_string( $url ) ) {
        return '';
    }
    $url = trim( wp_unslash( $url ) );
    if ( '' === $url ) {
        return '';
    }

    $clean = esc_url_raw( $url );
    if ( ! is_string( $clean ) || '' === $clean ) {
        return '';
    }

    $parts = wp_parse_url( $clean );
    if ( ! is_array( $parts ) || empty( $parts['host'] ) || empty( $parts['scheme'] ) ) {
        return '';
    }
    if ( isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
        return '';
    }

    $scheme = strtolower( (string) $parts['scheme'] );
    $host   = strtolower( (string) $parts['host'] );
    if ( '' === $host || false !== strpbrk( $host, "@/\\#? \t\r\n" ) ) {
        return '';
    }

    if ( chatbot_chatgpt_provider_base_url_host_is_local( $host ) ) {
        if ( 'https' === $scheme || 'http' === $scheme ) {
            return $clean;
        }
        return '';
    }
    if ( 'https' !== $scheme || chatbot_chatgpt_provider_base_url_host_is_blocked( $host ) ) {
        return '';
    }
    return $clean;

}

/**
 * Stored provider base URL, or the documented default when the stored value is unusable.
 *
 * @param string $option_name Option key.
 * @param string $fallback    Documented default URL.
 * @return string
 */
function chatbot_chatgpt_get_provider_base_url( $option_name, $fallback ) {

    $stored = get_option( $option_name, $fallback );
    if ( ! is_string( $stored ) ) {
        $stored = $fallback;
    }
    $clean = chatbot_chatgpt_validate_provider_base_url( $stored );
    if ( '' === $clean ) {
        $clean = chatbot_chatgpt_validate_provider_base_url( $fallback );
    }
    return $clean;

}

/**
 * Settings API callback. Keeps the previous usable URL when the submission is rejected.
 *
 * @param mixed  $url         Submitted value.
 * @param string $option_name Option key.
 * @param string $fallback    Documented default URL.
 * @return string
 */
function chatbot_chatgpt_sanitize_provider_base_url( $url, $option_name, $fallback ) {

    $clean = chatbot_chatgpt_validate_provider_base_url( is_string( $url ) ? $url : '' );
    if ( '' !== $clean ) {
        return $clean;
    }
    if ( function_exists( 'add_settings_error' ) ) {
        add_settings_error(
            'chatbot_chatgpt_messages',
            $option_name . '_rejected',
            __( 'That base URL was not saved. Use an https address. http is allowed only for localhost, 127.0.0.1, or ::1. A username, password, or empty host is not allowed.', 'chatbot-chatgpt' )
        );
    }
    return chatbot_chatgpt_get_provider_base_url( $option_name, $fallback );

}

/**
 * @param mixed $url Submitted OpenAI base URL.
 * @return string
 */
function chatbot_chatgpt_sanitize_openai_base_url( $url ) {
    return chatbot_chatgpt_sanitize_provider_base_url( $url, 'chatbot_chatgpt_base_url', 'https://api.openai.com/v1' );
}

/**
 * @param mixed $url Submitted Azure base URL.
 * @return string
 */
function chatbot_chatgpt_sanitize_azure_base_url( $url ) {
    return chatbot_chatgpt_sanitize_provider_base_url( $url, 'chatbot_azure_base_url', 'https://YOUR_RESOURCE_NAME.openai.azure.com/deployments/DEPLOYMENT_NAME/chat/completions?api-version=YYYY-MM-DD' );
}

/**
 * @param mixed $url Submitted NVIDIA base URL.
 * @return string
 */
function chatbot_chatgpt_sanitize_nvidia_base_url( $url ) {
    return chatbot_chatgpt_sanitize_provider_base_url( $url, 'chatbot_nvidia_base_url', 'https://integrate.api.nvidia.com/v1' );
}

/**
 * @param mixed $url Submitted Anthropic base URL.
 * @return string
 */
function chatbot_chatgpt_sanitize_anthropic_base_url( $url ) {
    return chatbot_chatgpt_sanitize_provider_base_url( $url, 'chatbot_anthropic_base_url', 'https://api.anthropic.com/v1' );
}

/**
 * @param mixed $url Submitted DeepSeek base URL.
 * @return string
 */
function chatbot_chatgpt_sanitize_deepseek_base_url( $url ) {
    return chatbot_chatgpt_sanitize_provider_base_url( $url, 'chatbot_deepseek_base_url', 'https://api.deepseek.com' );
}

/**
 * @param mixed $url Submitted Google base URL.
 * @return string
 */
function chatbot_chatgpt_sanitize_google_base_url( $url ) {
    return chatbot_chatgpt_sanitize_provider_base_url( $url, 'chatbot_google_base_url', 'https://generativelanguage.googleapis.com/v1beta' );
}

/**
 * @param mixed $url Submitted Mistral base URL.
 * @return string
 */
function chatbot_chatgpt_sanitize_mistral_base_url( $url ) {
    return chatbot_chatgpt_sanitize_provider_base_url( $url, 'chatbot_mistral_base_url', 'https://api.mistral.ai/v1' );
}

/**
 * @param mixed $url Submitted local base URL.
 * @return string
 */
function chatbot_chatgpt_sanitize_local_base_url( $url ) {
    return chatbot_chatgpt_sanitize_provider_base_url( $url, 'chatbot_local_base_url', 'http://127.0.0.1:1337/v1' );
}

// Base URL for the API calls - Ver 2.2.6
function get_api_base_url() {

    $chatbot_ai_platform_choice = get_option( 'chatbot_ai_platform_choice', 'OpenAI' );
    if ( ! is_string( $chatbot_ai_platform_choice ) || '' === $chatbot_ai_platform_choice ) {
        $chatbot_ai_platform_choice = 'OpenAI';
    }

    switch ( $chatbot_ai_platform_choice ) {

        case 'Azure OpenAI':

            return chatbot_chatgpt_get_provider_base_url( 'chatbot_azure_base_url', 'https://YOUR_RESOURCE_NAME.openai.azure.com/DEPLOYMENT_NAME/MODEL/chat/completions?api-version=YYYY-MM-DD' );

        case 'NVIDIA':

            return chatbot_chatgpt_get_provider_base_url( 'chatbot_nvidia_base_url', 'https://integrate.api.nvidia.com/v1' );

        case 'Anthropic':

            return chatbot_chatgpt_get_provider_base_url( 'chatbot_anthropic_base_url', 'https://api.anthropic.com/v1' );

        case 'DeepSeek':

            return chatbot_chatgpt_get_provider_base_url( 'chatbot_deepseek_base_url', 'https://api.deepseek.com' );

        case 'Google':

            return chatbot_chatgpt_get_provider_base_url( 'chatbot_google_base_url', 'https://generativelanguage.googleapis.com/v1beta' );

        case 'Mistral':

            return chatbot_chatgpt_get_provider_base_url( 'chatbot_mistral_base_url', 'https://api.mistral.ai/v1' );

        case 'Local Server':

            return chatbot_chatgpt_get_provider_base_url( 'chatbot_local_base_url', 'http://127.0.0.1:1337/v1' );

        case 'OpenAI':
        default:

            return chatbot_chatgpt_get_provider_base_url( 'chatbot_chatgpt_base_url', 'https://api.openai.com/v1' );

    }

}

// Base URL for the ChatGPT API calls - Ver 2.2.2
function get_threads_api_url() {

    return get_api_base_url() . "/threads";

}

// Base URL for the ChatGPT API calls - Ver 2.2.2
function get_files_api_url() {

    $chatbot_ai_platform_choice = esc_attr(get_option('chatbot_ai_platform_choice'), 'OpenAI');

    if ($chatbot_ai_platform_choice == 'OpenAI') {

        return get_api_base_url() . "/files";

    } elseif ($chatbot_ai_platform_choice == 'Azure OpenAI') {

        $chatbot_azure_resource_name = esc_attr(get_option('chatbot_azure_resource_name', 'YOUR_RESOURCE_NAME'));
        $chatbot_azure_deployment_name = esc_attr(get_option('chatbot_azure_deployment_name', 'DEPLOYMENT_NAME'));
        $chatbot_azure_api_version = esc_attr(get_option('chatbot_azure_api_version', '2024-08-01-preview'));
        // Assemble the URL
        // $url = 'https://RESOURCE_NAME_GOES_HERE.openai.azure.com/openai/files?api-version=2024-03-01-preview';
        $url = 'https://' . $chatbot_azure_resource_name . '.openai.azure.com/openai/files?api-version=' . $chatbot_azure_api_version;

        return $url;

    }

    return 'ERROR: No file API URL found!';

}

// Base URL for the ChatGPT API calls - Ver 2.2.2 - Revised - Ver 2.2.6
function get_chat_completions_api_url() {

    $chatbot_ai_platform_choice = esc_attr(get_option('chatbot_ai_platform_choice'), 'OpenAI');

    switch ($chatbot_ai_platform_choice) {

        // Base URL for the OpenAI API calls - Ver 1.8.1
        case 'OpenAI':

            return get_api_base_url() . "/chat/completions";
            break;

        // Base URL for the Azure OpenAI API calls - Ver 2.2.6
        case 'Azure OpenAI':

            $chatbot_azure_resource_name = esc_attr(get_option('chatbot_azure_resource_name', 'YOUR_RESOURCE_NAME'));
            $chatbot_azure_deployment_name = esc_attr(get_option('chatbot_azure_deployment_name', 'DEPLOYMENT_NAME'));
            $chatbot_azure_api_version = esc_attr(get_option('chatbot_azure_api_version', 'YYYY-MM-DD'));

            // Assemble the URL
            return 'https://' . $chatbot_azure_resource_name . '.openai.azure.com/openai/deployments/' . $chatbot_azure_deployment_name . '/chat/completions?api-version=' . $chatbot_azure_api_version;
            break;

        // Base URL for the NVIDIA API calls - Ver 2.1.8
        case 'NVIDIA':

            return get_api_base_url() . "/chat/completions";
            break;

        // Base URL for the Anthropic API calls - Ver 2.2.1
        case 'Anthropic':

            return get_api_base_url() . "/messages";
            break;

        // Base URL for the DeepSeek API calls - Ver 2.2.2
        case 'DeepSeek':

            return get_api_base_url() . "/chat/completions";
            break;

        // Base URL for the Google API calls - Ver 2.3.9
        // Note: Google API uses /models/{model}:generateContent format, not /chat/completions
        // This function returns a base URL that can be used to construct the full endpoint
        case 'Google':

            // Google API endpoint format: {base}/models/{model}:generateContent
            // This function returns the base, caller must append /models/{model}:generateContent
            return get_api_base_url() . "/models";
            break;

        // Base URL for the Mistral API calls - Ver 2.3.0
        case 'Mistral':

            return get_api_base_url() . "/chat/completions";
            break;

        // Base URL for the Local API calls - Ver 2.2.6
        case 'Local Server':

            return get_api_base_url() . "/chat/completions";
            break;

        default:

            return get_api_base_url() . "/chat/completions";
            break;
    }

}
