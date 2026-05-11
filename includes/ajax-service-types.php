<?php
// Add AJAX action for fetching services
add_action('wp_ajax_mevp_fetch_services', 'mevp_fetch_services_callback');

function mevp_fetch_services_callback() {
    global $wpdb;

    // Verify nonce for security
    if (!check_ajax_referer('mevp_fetch_services_nonce', 'nonce', false)) {
        wp_send_json_error('Security check failed');
        return;
    }

    // Check user permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }

    // Get API credentials
    $api_key = mevp_get_api_key();
    $base_url = get_option('mevp_api_base_url', '');

    // Validate credentials
    if (empty($base_url) || empty($api_key)) {
        wp_send_json_error('API Base URL or API Key is not configured');
        return;
    }

    // Make API request to fetch services
    $response = wp_remote_get($base_url . '/api/get-service-types', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ],
        'timeout' => 30,
    ]);

    // Check for errors
    if (is_wp_error($response)) {
        wp_send_json_error('API request failed: ' . $response->get_error_message());
        return;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    if ($status_code !== 200) {
        wp_send_json_error('API returned status code: ' . $status_code . '. Response: ' . $body);
        return;
    }

    $services = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error('Invalid JSON response from API');
        return;
    }

    // Store services in wp_mevp_data table
    $table_name = $wpdb->prefix . 'mevp_data';

    // Check if services record already exists
    $existing_record = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM $table_name WHERE title = %s", 'services_data')
    );

    $services_json = json_encode($services);
    $current_time = current_time('mysql');

    if ($existing_record) {
        // Update existing record
        $result = $wpdb->update(
            $table_name,
            [
                'content' => $services_json,
                'status' => 'updated',
            ],
            ['title' => 'services_data'],
            ['%s', '%s'],
            ['%s']
        );

        if ($result === false) {
            wp_send_json_error('Failed to update services data in database');
            return;
        }

        wp_send_json_success([
            'message' => 'Cars data updated successfully!',
            'total_services' => count($services),
            'action' => 'updated'
        ]);
    } else {
        // Insert new record
        $result = $wpdb->insert(
            $table_name,
            [
                'title' => 'services_data',
                'content' => $services_json,
                'status' => 'active',
            ],
            ['%s', '%s', '%s']
        );

        if (!$result) {
            wp_send_json_error('Failed to insert services data into database');
            return;
        }

        wp_send_json_success([
            'message' => 'Cars data stored successfully!',
            'total_services' => count($services),
            'action' => 'inserted'
        ]);
    }
}

add_action('wp_ajax_mevp_load_stored_services', 'mevp_load_stored_services_callback');

function mevp_load_stored_services_callback() {
    global $wpdb;

    // Verify nonce
    if (!check_ajax_referer('mevp_fetch_services_nonce', 'nonce', false)) {
        wp_send_json_error('Security check failed');
        return;
    }

    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }

    $table_name = $wpdb->prefix . 'mevp_data';

    // Get stored services data
    $record = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM $table_name WHERE title = %s", 'services_data')
    );

    if (!$record || empty($record->content)) {
        wp_send_json_error('No services data found. Please fetch services first.');
        return;
    }

    $services = json_decode($record->content, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error('Invalid data format in database');
        return;
    }

    wp_send_json_success([
        'services' => $services,
        'last_updated' => $record->updated_at ?? $record->created_at,
        'status' => $record->status
    ]);
}