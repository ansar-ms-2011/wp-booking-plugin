<?php
require_once plugin_dir_path(__FILE__) . 'class-encryption.php';

// Get decrypted Google Maps API key
function mevp_get_google_maps_api_key() {
    $encrypted = get_option('mevp_google_maps_api_key', '');

    if (empty($encrypted)) {
        return '';
    }

    // Check if encrypted
    if (strpos($encrypted, 'MEVP_ENC:') === 0) {
        $encrypted = substr($encrypted, 9); // Remove prefix
        return MEVP_Encryption::decrypt($encrypted);
    }

    // For backward compatibility (unencrypted data)
    return $encrypted;
}

// Get decrypted API key
function mevp_get_api_key() {
    $encrypted = get_option('mevp_api_key', '');

    if (empty($encrypted)) {
        return '';
    }

    // Check if encrypted
    if (strpos($encrypted, 'MEVP_ENC:') === 0) {
        $encrypted = substr($encrypted, 9); // Remove prefix
        return MEVP_Encryption::decrypt($encrypted);
    }

    // For backward compatibility (unencrypted data)
    return $encrypted;
}

// Get API base URL (not encrypted)
function mevp_get_api_base_url() {
    return get_option('mevp_api_base_url', '');
}

// Helper to check if encryption is working
function mevp_test_encryption() {
    $test_string = 'test_value_' . time();
    $encrypted = MEVP_Encryption::encrypt($test_string);
    $decrypted = MEVP_Encryption::decrypt($encrypted);

    return $decrypted === $test_string;
}

// Enhanced API request with decrypted keys
function mevp_make_api_request($endpoint, $method = 'GET', $data = []) {
    $base_url = mevp_get_api_base_url();
    $api_key = mevp_get_api_key(); // This now returns decrypted key

    if (empty($base_url) || empty($api_key)) {
        return new WP_Error('missing_settings', 'API settings are not configured');
    }

    $url = trailingslashit($base_url) . ltrim($endpoint, '/');

    $response = wp_remote_request($url, [
        'method' => $method,
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
            'X-API-Key' => $api_key,
        ],
        'body' => !empty($data) ? json_encode($data) : null,
        'timeout' => 30,
    ]);

    if (is_wp_error($response)) {
        return $response;
    }

    return json_decode(wp_remote_retrieve_body($response), true);
}
?>