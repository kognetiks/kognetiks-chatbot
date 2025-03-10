<?php
/**
 * Kognetiks Chatbot - Key Guard Support - Ver 2.2.6
 *
 * This file contains the code for managing the Key Guard functions.
 * 
 *
 * @package chatbot-chatgpt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

/* Example usage:

// Encrypt a new API key.
$encrypted = encrypt_api_key('MY_NEW_API_KEY');
echo "Encrypted API Key: {$encrypted}\n";

// Decrypt the API key.
$decrypted = decrypt_api_key($encrypted);
echo "Decrypted API Key: {$decrypted}\n";

// Example callbacks for upgrading stored keys.
// In practice, these would interact with your database.
function fetch_stored_api_keys() {
    // Simulated stored keys; in a real scenario, fetch these from your DB.
    return [
        'key1' => '{"iv":"' . base64_encode(openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'))) . '","encrypted":"' . openssl_encrypt('API_KEY_1', 'aes-256-cbc', 'old_secret_key', 0, openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'))) . '"}',
        // Add additional keys as needed.
    ];
}

function update_stored_api_key($key_id, $new_encrypted_key) {
    // In a real scenario, update the key in your database.
    echo "Updating key {$key_id} with new encrypted value: {$new_encrypted_key}\n";
}

// Assuming 'old_secret_key' is the key previously used.
$old_secret_key = 'old_secret_key';

// Upgrade stored keys.
upgrade_encrypted_api_keys('fetch_stored_api_keys', 'update_stored_api_key', $old_secret_key);

*/

// Define the secret key file path - Ver 2.2.6
define('PLUGIN_SECRET_KEY_FILE', __DIR__ . '/chatbot-keyguard-key.php');
// error_log('chatbot-keyguard.txt is located here: ' . PLUGIN_SECRET_KEY_FILE);

// One-time upgrade to encrypted keys using the old secret key - Ver 2.2.6
// upgrade_encrypted_api_keys('fetch_stored_api_keys', 'update_stored_api_key', $old_secret_key);

// Generates a new secret key and writes it to a PHP file - Ver 2.2.6
function generate_and_store_secret_key() {

    // Generate a 256-bit (32-byte) key and convert it to a hexadecimal string.
    $key = bin2hex(random_bytes(32));
    $content = "<?php\n";
    $content .= "// Auto-generated secret key for plugin encryption. Do not modify.\n";
    $content .= "if (!defined('PLUGIN_SECRET_KEY')) {\n";
    $content .= "    define('PLUGIN_SECRET_KEY', '$key');\n";
    $content .= "}\n";
    file_put_contents(PLUGIN_SECRET_KEY_FILE, $content);
    return $key;

}

// Retrieves the secret key and if it doesn't exist, a new key is generated and stored - Ver 2.2.6
function get_secret_key() {

    if (!file_exists(PLUGIN_SECRET_KEY_FILE)) {
        return generate_and_store_secret_key();
    }
    include_once PLUGIN_SECRET_KEY_FILE;
    if (defined('PLUGIN_SECRET_KEY')) {
        return PLUGIN_SECRET_KEY;
    }
    // Fallback: if the file exists but the constant is not defined.
    return generate_and_store_secret_key();

}

// Encrypts the given plaintext using AES-256-CBC with the provided secret key - Ver 2.2.6
// Returns a JSON string containing the base64-encoded IV and the encrypted data - Ver 2.2.6
function encrypt_api_key_with_key($plaintext, $secret_key) {

    $cipher = 'aes-256-cbc';
    $iv_length = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($iv_length);
    $encrypted = openssl_encrypt($plaintext, $cipher, $secret_key, 0, $iv);
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
function decrypt_api_key_with_key($data, $secret_key) {

    $decoded = json_decode($data, true);
    if (!isset($decoded['iv']) || !isset($decoded['encrypted'])) {
        return false;
    }
    $iv = base64_decode($decoded['iv']);
    $encrypted = $decoded['encrypted'];
    return openssl_decrypt($encrypted, 'aes-256-cbc', $secret_key, 0, $iv);

}

// Convenience function to encrypt an API key using the stored secret key - Ver 2.2.6
function encrypt_api_key($plaintext) {

    $secret_key = get_secret_key();
    return encrypt_api_key_with_key($plaintext, $secret_key);

}

// Convenience function to decrypt an API key using the stored secret key - Ver 2.2.6
function decrypt_api_key($data) {

    // Fix the JSON before decrypting
    $data = html_entity_decode($data);
    error_log('chatbot-keyguard.php: decrypt_api_key() - $data: ' . print_r($data, true));
    $secret_key = get_secret_key();
    // In production, avoid logging sensitive information such as secret keys.
    // error_log('chatbot-keyguard.php: decrypt_api_key() - $secret_key: ' . $secret_key);
    $decrypted_key = decrypt_api_key_with_key($data, $secret_key);
    error_log('chatbot-keyguard.php: decrypt_api_key() - $decrypted_key: ' . $decrypted_key);
    return $decrypted_key;

}

// Upgrades stored encrypted API keys to use the new secret key.
//
// This function assumes you have stored API keys encrypted with an old secret key.
// You must supply the old key so that each API key can be decrypted and then re-encrypted
// with the new secret key (retrieved via get_secret_key()). The function takes two callbacks:
// one to fetch all stored keys and one to update each key in storage.
//
function upgrade_encrypted_api_keys(callable $fetch_keys_callback, callable $update_key_callback, $old_secret_key) {

    // Get the new secret key (this will generate one if it doesn't exist).
    $new_secret_key = get_secret_key();
    
    // Fetch stored keys. Expected format: [ 'key_id1' => 'encrypted_data', ... ]
    $stored_keys = $fetch_keys_callback();
    
    foreach ($stored_keys as $key_id => $encrypted_api_key) {

        // Decrypt using the old secret key.
        $decrypted = decrypt_api_key_with_key($encrypted_api_key, $old_secret_key);
        if ($decrypted === false) {
            // Log or handle the decryption error, then skip this key.
            continue;
        }
        // Re-encrypt using the new secret key.
        $new_encrypted = encrypt_api_key_with_key($decrypted, $new_secret_key);
        if ($new_encrypted === false) {
            // Log or handle the encryption error.
            continue;
        }
        // Update the stored key using the provided callback.
        $update_key_callback($key_id, $new_encrypted);
    }

}

/* 
Example usage:

Assume you have functions to interact with your database:
-----------------------------------------------------------
function fetch_stored_api_keys() {
    // Example: Fetch keys from the database. Replace this with your actual DB call.
    // Expected format: [ 'key1' => 'plain_text_or_encrypted_value', ... ]
    return [
        'key1' => 'UNENCRYPTED_API_KEY_1',
        'key2' => '{"iv":"...","encrypted":"..."}', // Already encrypted key
        'key3' => 'UNENCRYPTED_API_KEY_3'
    ];
}

function update_stored_api_key($key_id, $new_encrypted_value) {
    // Example: Update the key in the database. Replace with your actual update logic.
    echo "Updating key {$key_id} with new value: {$new_encrypted_value}\n";
}

// Perform the upgrade for unencrypted keys.
upgrade_unencrypted_api_keys('fetch_stored_api_keys', 'update_stored_api_key');
*/

// Fetch stored API keys from the database - Ver 2.2.6
function fetch_stored_api_keys($chatbot_ai_platform_choice = null) {

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
        'chatbot_azure_api_key' => esc_attr( get_option('chatbot_azure_api_key') ),
    );

    // DIAG - Diagnostics
    error_log('chatbot-keyguard.php: fetch_stored_api_keys() - $stored_keys: ' . print_r($stored_keys, true));

    return $stored_keys;

}

// Update stored API key in the database - Ver 2.2.6
function update_stored_api_key($key_id, $new_encrypted_value) {

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
    } elseif ($key_id == 'chatbot_azure_api_key') {
        update_option('chatbot_azure_api_key', $new_encrypted_value);
    }

}

/**
 * Upgrade unencrypted API keys stored in the database.
 *
 * This function fetches stored API keys via the provided callback.
 * It then checks each key—if the key is not already in the encrypted JSON format,
 * it encrypts the key and uses the update callback to save the new encrypted value.
 *
 * @param callable $fetch_keys_callback A callback that returns an associative array
 *                                      where keys are key IDs and values are stored API keys.
 * @param callable $update_key_callback A callback that accepts a key ID and a new encrypted API key,
 *                                      then updates the storage accordingly.
 * @return void
 */

// Upgrade unencrypted API keys stored in the database - Ver 2.2.6
function upgrade_unencrypted_api_keys(callable $fetch_keys_callback, callable $update_key_callback) {

    // Retrieve the secret key (auto-generates it if it doesn't exist)
    $secret_key = get_secret_key();

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
        $new_encrypted_value = encrypt_api_key_with_key($stored_value, $secret_key);
        if ($new_encrypted_value !== false) {
            // Update the stored key using the provided callback.
            $update_key_callback($key_id, $new_encrypted_value);
        }
    }
    
}

// Hook our upgrade process into admin initialization - Ver 2.2.6
add_action('admin_init', 'run_chatbot_api_key_upgrade');

// Force the upgrade process to run - Ver 2.2.6
function run_chatbot_api_key_upgrade() {

    // Check if the upgrade has already run.
    if ( get_option('chatbot_keys_upgraded') !== 'yes' ) {
        // For unencrypted keys, run the upgrade.
        upgrade_unencrypted_api_keys('fetch_stored_api_keys', 'update_stored_api_key');
        
        // Optionally, if you need to upgrade keys encrypted with an old secret key,
        // call upgrade_encrypted_api_keys here as well:
        // upgrade_encrypted_api_keys('fetch_stored_api_keys', 'update_stored_api_key', $old_secret_key);
        
        // Mark that the upgrade has been performed.
        update_option('chatbot_keys_upgraded', 'yes');
    }

}
