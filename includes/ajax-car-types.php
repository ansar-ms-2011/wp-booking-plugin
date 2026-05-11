<?php
// Add AJAX action for fetching cars
add_action('wp_ajax_mevp_fetch_cars', 'mevp_fetch_cars_callback');

function mevp_fetch_cars_callback() {
    global $wpdb;

    // Verify nonce for security
    if (!check_ajax_referer('mevp_fetch_cars_nonce', 'nonce', false)) {
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

    // Make API request to fetch cars
    $response = wp_remote_get($base_url . '/api/get-vehicle-types', [
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

    $cars = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error('Invalid JSON response from API');
        return;
    }

    // Delete cached response
    delete_transient('mevp_cars_data_response');
    // Store cars in wp_mevp_data table
    $table_name = $wpdb->prefix . 'mevp_data';

    // Check if cars record already exists
    $existing_record = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM $table_name WHERE title = %s", 'cars_data')
    );

    $cars_json = json_encode($cars);
    $current_time = current_time('mysql');

    if ($existing_record) {
        // Update existing record
        $result = $wpdb->update(
            $table_name,
            [
                'content' => $cars_json,
                'status' => 'updated',
            ],
            ['title' => 'cars_data'],
            ['%s', '%s'],
            ['%s']
        );

        if ($result === false) {
            wp_send_json_error('Failed to update cars data in database');
            return;
        }

        wp_send_json_success([
            'message' => 'Cars data updated successfully!',
            'total_cars' => count($cars),
            'action' => 'updated'
        ]);
    } else {
        // Insert new record
        $result = $wpdb->insert(
            $table_name,
            [
                'title' => 'cars_data',
                'content' => $cars_json,
                'status' => 'active',
            ],
            ['%s', '%s', '%s']
        );

        if (!$result) {
            wp_send_json_error('Failed to insert cars data into database');
            return;
        }

        wp_send_json_success([
            'message' => 'Cars data stored successfully!',
            'total_cars' => count($cars),
            'action' => 'inserted'
        ]);
    }
}

add_action('wp_ajax_mevp_load_stored_cars', 'mevp_load_stored_cars_callback');

function mevp_load_stored_cars_callback() {
    global $wpdb;

    // Verify nonce
    if (!check_ajax_referer('mevp_fetch_cars_nonce', 'nonce', false)) {
        wp_send_json_error('Security check failed');
        return;
    }

    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }

    $table_name = $wpdb->prefix . 'mevp_data';

    // Get stored cars data
    $record = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM $table_name WHERE title = %s", 'cars_data')
    );

    if (!$record || empty($record->content)) {
        wp_send_json_error('No cars data found. Please fetch cars first.');
        return;
    }

    $cars = json_decode($record->content, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error('Invalid data format in database');
        return;
    }

    wp_send_json_success([
        'cars' => $cars,
        'last_updated' => $record->updated_at ?? $record->created_at,
        'status' => $record->status
    ]);
}