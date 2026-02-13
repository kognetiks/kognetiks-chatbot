<?php
/**
 * Kognetiks Chatbot - Keyguard Support - Ver 2.2.6
 *
 * This file contains the code for managing the Keyguard functions.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

// One-time upgrade to encrypted keys using the old secret key - Ver 2.2.6
// chatbot_chatgpt_upgrade_encrypted_api_keys('chatbot_chatgpt_fetch_stored_api_keys', 'chatbot_chatgpt_update_stored_api_key', $old_keyguard);

// XORs the given key with a salt key and returns the base64-encoded result - Ver 2.2.6
function chatbot_chatgpt_obfuscate_keyguard($key) {

    // Ensure AUTH_KEY is defined.
    if ( ! defined('AUTH_KEY') ) {
        trigger_error('AUTH_KEY must be defined in wp-config.php for secure obfuscation.', E_USER_ERROR);
    }
    $salt = AUTH_KEY;
    // Derive a 32-byte binary salt key.
    $salt_key = hash('sha256', $salt, true);
    // Convert the hex key to binary.
    $binary_key = hex2bin($key);
    // XOR the binary key with the salt key.
    $obfuscated_binary = $binary_key ^ $salt_key;
    // Return the result base64-encoded.
    return base64_encode($obfuscated_binary);

}

// XORs the given obfuscated key with a salt key and returns the hex-encoded result - Ver 2.2.6
function chatbot_chatgpt_deobfuscate_keyguard($obfuscated_key) {

    // Ensure AUTH_KEY is defined.
    if ( ! defined('AUTH_KEY') ) {
        trigger_error('AUTH_KEY must be defined in wp-config.php for secure obfuscation.', E_USER_ERROR);
    }
    $salt = AUTH_KEY;
    // Derive a 32-byte binary salt key.
    $salt_key = hash('sha256', $salt, true);
    // Convert the hex key to binary.
    $obfuscated_binary = base64_decode($obfuscated_key);
    // XOR the binary key with the salt key.
    $binary_key = $obfuscated_binary ^ $salt_key;
    // Return the result base64-decoded.
    return bin2hex($binary_key);

}

// Retrieves the chatbot secret key and if it doesn't exist, a new key is generated and stored - Ver 2.2.6
function chatbot_chatgpt_generate_and_store_keyguard() {

    $option_name = 'kognetiks_keyguard';
    
    // Retrieve the obfuscated key from the DB.
    $obfuscated_key = get_option($option_name);
    
    if (empty($obfuscated_key)) {
        // Generate a new 256-bit (32-byte) key and convert it to a hex string.
        $key = bin2hex(random_bytes(32));
        // Obfuscate the key.
        $obfuscated_key = chatbot_chatgpt_obfuscate_keyguard($key);
        // Store in the DB. Disable autoload to prevent it from loading on every page load.
        update_option($option_name, $obfuscated_key, '', 'no');
        return $key;
    }
    
    // Deobfuscate to retrieve the original key.
    return chatbot_chatgpt_deobfuscate_keyguard($obfuscated_key);

}

// Encrypts the given plaintext using AES-256-CBC with the provided secret key - Ver 2.2.6
//
// Returns a JSON string containing the base64-encoded IV and the encrypted data,
//
function chatbot_chatgpt_encrypt_api_key_with_key($plaintext, $keyguard) {

    $cipher = 'aes-256-cbc';
    $iv_length = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($iv_length);
    // Convert the hex-encoded keyguard to binary.
    $binary_key = hex2bin($keyguard);
    $encrypted = openssl_encrypt($plaintext, $cipher, $binary_key, 0, $iv);
    if ($encrypted === false) {
        return false;
    }
    $data = [
        'iv'        => base64_encode($iv),
        'encrypted' => $encrypted
    ];
    return json_encode($data);

}

// Decrypts the given JSON encoded string using AES-256-CBC with the provided secret key - Ver 2.2.6
function chatbot_chatgpt_decrypt_api_key_with_key($data, $keyguard) {

    $decoded = json_decode($data, true);
    if (!isset($decoded['iv']) || !isset($decoded['encrypted'])) {
        return false;
    }
    $iv = base64_decode($decoded['iv']);
    $encrypted = $decoded['encrypted'];
    // Convert the hex-encoded keyguard to binary.
    $binary_key = hex2bin($keyguard);
    return openssl_decrypt($encrypted, 'aes-256-cbc', $binary_key, 0, $iv);

}

// Convenience function to encrypt an API key using the stored secret key - Ver 2.2.6
function chatbot_chatgpt_encrypt_api_key($plaintext) {

    $keyguard = chatbot_chatgpt_generate_and_store_keyguard();
    return chatbot_chatgpt_encrypt_api_key_with_key($plaintext, $keyguard);

}

// Convenience function to decrypt an API key using the stored secret key - Ver 2.2.6
function chatbot_chatgpt_decrypt_api_key($data, $option_name = null) {

    // Fix the JSON before decrypting.
    $data = html_entity_decode($data);
    // error_log('[Chatbot] [chatbot-keyguard.php] chatbot_chatgpt_decrypt_api_key() - $data: ' . print_r($data, true));
    
    // Attempt to decode the data as JSON.
    $decoded = json_decode($data, true);
    
    // If not in the expected JSON format, assume it's plain text.
    if (!is_array($decoded) || !isset($decoded['iv']) || !isset($decoded['encrypted'])) {
        // error_log('[Chatbot] [chatbot-keyguard.php] Plaintext detected.');

        $chatbot_ai_platform_choice = esc_attr( get_option('chatbot_ai_platform_choice') );
        switch ($chatbot_ai_platform_choice) {
            case 'OpenAI':
                $option_name = 'chatbot_chatgpt_api_key';
                break;
            case 'NVIDIA':
                $option_name = 'chatbot_nvidia_api_key';
                break;
            case 'Anthropic':
                $option_name = 'chatbot_anthropic_api_key';
                break;
            case 'DeepSeek':
                $option_name = 'chatbot_deepseek_api_key';
                break;
            case 'Google':
                $option_name = 'chatbot_google_api_key';
                break;
            case 'Mistral':
                $option_name = 'chatbot_mistral_api_key';
                break;
            case 'Azure OpenAI':
                $option_name = 'chatbot_azure_api_key';
                break;
            case 'Local':
                $option_name = 'chatbot_local_api_key';
                break;
            default:
                $option_name = null;
        }
        
        // If an option name is provided, encrypt the plain text and update the DB.
        if (!empty($option_name)) {
            $encrypted_value = chatbot_chatgpt_encrypt_api_key($data);
            update_option($option_name, $encrypted_value);
            // error_log('[Chatbot] [chatbot-keyguard.php] Failed to insert row into table: ' . $table_name);
            // error_log('[Chatbot] [chatbot-keyguard.php] Updated option ' . $option_name . ' with encrypted value.');
        }
        // Return the plain text.
        return $data;
    }
    
    // Otherwise, use the stored keyguard to decrypt.
    $keyguard = chatbot_chatgpt_generate_and_store_keyguard();
    // error_log('[Chatbot] [chatbot-keyguard.php] chatbot_chatgpt_decrypt_api_key() - $keyguard: ' . $keyguard);
    $decrypted_key = chatbot_chatgpt_decrypt_api_key_with_key($data, $keyguard);
    // error_log('[Chatbot] [chatbot-keyguard.php] chatbot_chatgpt_decrypt_api_key() - $decrypted_key: ' . $decrypted_key);
    return $decrypted_key;

}

// Upgrades stored encrypted API keys to use the new secret key.
//
// This function assumes you have stored API keys encrypted with an old secret key.
// You must supply the old key so that each API key can be decrypted and then re-encrypted
// with the new secret key (retrieved via chatbot_chatgpt_generate_and_store_keyguard()). The function takes two callbacks:
// one to fetch all stored keys and one to update each key in storage.
//
function chatbot_chatgpt_upgrade_encrypted_api_keys(callable $fetch_keys_callback, callable $update_key_callback, $old_keyguard) {

    // Get the new secret key (this will generate one if it doesn't exist).
    $new_keyguard = chatbot_chatgpt_generate_and_store_keyguard();
    
    // Fetch stored keys. Expected format: [ 'key_id1' => 'encrypted_data', ... ]
    $stored_keys = $fetch_keys_callback();
    
    foreach ($stored_keys as $key_id => $encrypted_api_key) {

        // Decrypt using the old secret key.
        $decrypted = chatbot_chatgpt_decrypt_api_key_with_key($encrypted_api_key, $old_keyguard);
        if ($decrypted === false) {
            // Log or handle the decryption error, then skip this key.
            continue;
        }
        // Re-encrypt using the new secret key.
        $new_encrypted = chatbot_chatgpt_encrypt_api_key_with_key($decrypted, $new_keyguard);
        if ($new_encrypted === false) {
            // Log or handle the encryption error.
            continue;
        }
        // Update the stored key using the provided callback.
        $update_key_callback($key_id, $new_encrypted);
    }

}

// Fetch stored API keys from the database - Ver 2.2.6
// 
// Example: Fetch keys from the database. Replace this with your actual DB call.
// Expected format: [ 'key1' => 'plain_text_or_encrypted_value', ... ]
//     return [
//         'key1' => 'UNENCRYPTED_API_KEY_1',
//         'key2' => '{"iv":"...","encrypted":"..."}', // Already encrypted key
//         'key3' => 'UNENCRYPTED_API_KEY_3'
//     ];
//
function chatbot_chatgpt_fetch_stored_api_keys($chatbot_ai_platform_choice = null) {

    // Example: Fetch keys from the database. Replace this with your actual DB call.
    // Expected format: [ 'key1' => 'plain_text_or_encrypted_value', ... ]
    // return [
    //     'key1' => 'UNENCRYPTED_API_KEY_1',
    //     'key2' => '{"iv":"...","encrypted":"..."}', // Already encrypted key
    //     'key3' => 'UNENCRYPTED_API_KEY_3'
    // ];
    $stored_keys = array();
    $stored_keys = array(
        'chatbot_chatgpt_api_key' => esc_attr( get_option('chatbot_chatgpt_api_key') ),
        'chatbot_nvidia_api_key' => esc_attr( get_option('chatbot_nvidia_api_key') ),
        'chatbot_anthropic_api_key' => esc_attr( get_option('chatbot_anthropic_api_key') ),
        'chatbot_deepseek_api_key' => esc_attr( get_option('chatbot_deepseek_api_key') ),
        'chatbot_google_api_key' => esc_attr( get_option('chatbot_google_api_key') ),
        'chatbot_mistral_api_key' => esc_attr( get_option('chatbot_mistral_api_key') ),
        'chatbot_azure_api_key' => esc_attr( get_option('chatbot_azure_api_key') ),
    );
    
    return $stored_keys;

}

// Update stored API key in the database - Ver 2.2.6
function chatbot_chatgpt_update_stored_api_key($key_id, $new_encrypted_value) {

    // Example: Update the key in the database. Replace with your actual update logic.
    // echo "Updating key {$key_id} with new value: {$new_encrypted_value}\n";
    if ($key_id == 'chatbot_chatgpt_api_key') {
        update_option('chatbot_chatgpt_api_key', $new_encrypted_value);
    } elseif ($key_id == 'chatbot_nvidia_api_key') {
        update_option('chatbot_nvidia_api_key', $new_encrypted_value);
    } elseif ($key_id == 'chatbot_anthropic_api_key') {
        update_option('chatbot_anthropic_api_key', $new_encrypted_value);
    } elseif ($key_id == 'chatbot_deepseek_api_key') {
        update_option('chatbot_deepseek_api_key', $new_encrypted_value);
    } elseif ($key_id == 'chatbot_google_api_key') {
        update_option('chatbot_google_api_key', $new_encrypted_value);
    } elseif ($key_id == 'chatbot_mistral_api_key') {
        update_option('chatbot_mistral_api_key', $new_encrypted_value);
    } elseif ($key_id == 'chatbot_azure_api_key') {
        update_option('chatbot_azure_api_key', $new_encrypted_value);
    }

}

// Upgrade unencrypted API keys stored in the database - Ver 2.2.6
//
// This function fetches stored API keys via the provided callback.
// It then checks each key—if the key is not already in the encrypted JSON format,
// it encrypts the key and uses the update callback to save the new encrypted value.
//
function chatbot_chatgpt_upgrade_unencrypted_api_keys(callable $fetch_keys_callback, callable $update_key_callback) {

    // Retrieve the secret key (auto-generates it if it doesn't exist)
    $keyguard = chatbot_chatgpt_generate_and_store_keyguard();

    // Fetch all stored keys. Expected format: [ 'key_id1' => 'stored_value', ... ]
    $stored_keys = $fetch_keys_callback();

    foreach ($stored_keys as $key_id => $stored_value) {
        // Try decoding the stored value as JSON.
        $decoded = json_decode($stored_value, true);

        // Check if the stored value is already encrypted:
        // It should be an array with both 'iv' and 'encrypted' keys.
        if (is_array($decoded) && isset($decoded['iv']) && isset($decoded['encrypted'])) {
            // This key is already encrypted. Skip upgrading.
            continue;
        }

        // Otherwise, assume it's a plain text (unencrypted) key.
        $new_encrypted_value = chatbot_chatgpt_encrypt_api_key_with_key($stored_value, $keyguard);
        if ($new_encrypted_value !== false) {
            // Update the stored key using the provided callback.
            $update_key_callback($key_id, $new_encrypted_value);
        }
    }
    
}

// Hook our upgrade process into admin initialization - Ver 2.2.6
add_action('admin_init', 'chatbot_chatgpt_run_chatbot_api_key_upgrade');

// Force the upgrade process to run - Ver 2.2.6
function chatbot_chatgpt_run_chatbot_api_key_upgrade() {

    // Check if the upgrade has already run.
    if ( get_option('kognetiks_keyguard_upgraded') !== 'yes' ) {
        // For unencrypted keys, run the upgrade.
        chatbot_chatgpt_upgrade_unencrypted_api_keys('chatbot_chatgpt_fetch_stored_api_keys', 'chatbot_chatgpt_update_stored_api_key');
        
        // Optionally, if you need to upgrade keys encrypted with an old secret key,
        // call chatbot_chatgpt_upgrade_encrypted_api_keys here as well:
        // chatbot_chatgpt_upgrade_encrypted_api_keys('chatbot_chatgpt_fetch_stored_api_keys', 'chatbot_chatgpt_update_stored_api_key', $old_keyguard);
        
        // Mark that the upgrade has been performed.
        update_option('kognetiks_keyguard_upgraded', 'yes');
    }

}

// Sanitize the API key - Ver 2.2.6
function chatbot_chatgpt_sanitize_api_key($input) {

    // Check if input is already in encrypted JSON format.
    $decoded = json_decode($input, true);
    if (is_array($decoded) && isset($decoded['iv']) && isset($decoded['encrypted'])) {
        // Already encrypted; do not encrypt again.
        return $input;
    }
    // Otherwise, encrypt the plain text API key.
    if (!empty($input)) {
        $encrypted_key = chatbot_chatgpt_encrypt_api_key($input);
        return $encrypted_key;
    }
    return $input;

}
