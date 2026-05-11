<?php
class MEVP_Booking_Handler {

    public function save_booking_data($request) {
        $params = $request->get_json_params();

        if (!$params || empty($params)) {
            return rest_ensure_response(array(
                'success' => false,
                'message' => 'No booking data provided'
            ));
        }

        $api_base_url = mevp_get_api_base_url();
        $api_key = mevp_get_api_key();

        if (empty($api_base_url) || empty($api_key)) {
            return rest_ensure_response(array(
                'success' => false,
                'message' => 'API configuration is missing. Please configure API Base URL and API Key in plugin settings.',
                'data' => null
            ));
        }

        $booking_data = $this->prepare_booking_data($params);
        $external_api_response = $this->send_booking_to_external_api($api_base_url, $api_key, $booking_data);

        if (is_wp_error($external_api_response)) {
            return rest_ensure_response(array(
                'success' => false,
                'message' => 'Failed to create booking: ' . $external_api_response->get_error_message(),
                'error_code' => $external_api_response->get_error_code(),
                'data' => null
            ));
        }

        $local_booking_id = $this->save_booking_to_local_db($params, $external_api_response);

        delete_transient('mevp_bookings_cache');

        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Booking created successfully',
            'local_booking_id' => $local_booking_id,
            'external_api_response' => $external_api_response,
            'data' => $booking_data
        ));
    }

    public function handle_ajax_booking() {
        $booking_data = $_POST['booking_data'];

        if (is_string($booking_data)) {
            $booking_data = json_decode(stripslashes($booking_data), true);
        }

        $request = new WP_REST_Request('POST', '/mevp/v1/save-booking');
        $request->set_body_params($booking_data);

        $response = $this->save_booking_data($request);
        $response_data = $response->get_data();

        if ($response_data['success']) {
            wp_send_json_success($response_data);
        } else {
            wp_send_json_error($response_data);
        }
    }

    private function prepare_booking_data($params) {
        return array(
            'booking_id' => isset($params['booking_id']) ? sanitize_text_field($params['booking_id']) : $this->generate_booking_id(),
            'name' => isset($params['fullName']) ? sanitize_text_field($params['fullName']) : '',
            'email' => isset($params['email']) ? sanitize_email($params['email']) : '',
            'primary_number' => isset($params['primaryPhone']) ? sanitize_text_field($params['primaryPhone']) : '',
            'passengers' => isset($params['passengers']) ? sanitize_text_field($params['passengers']) : '',
            'luggage' => isset($params['luggage']) ? sanitize_text_field($params['luggage']) : '',
            'car_id' => isset($params['carId']) ? intval($params['carId']) : 0,
            'car_name' => isset($params['carName']) ? sanitize_text_field($params['carName']) : '',
            'pickup_datetime' => isset($params['pickupDateTime']) ? sanitize_text_field($params['pickupDateTime']) : '',
            'pu_full_address' => isset($params['pickupLocation']) ? sanitize_text_field($params['pickupLocation']) : '',
            'pu_latitude' => isset($params['pickupLocationLat']) ? sanitize_text_field($params['pickupLocationLat']) : '',
            'pu_longitude' => isset($params['pickupLocationLng']) ? sanitize_text_field($params['pickupLocationLng']) : '',
            'do_full_address' => isset($params['dropOffLocation']) ? sanitize_text_field($params['dropOffLocation']) : '',
            'do_latitude' => isset($params['dropOffLocationLat']) ? sanitize_text_field($params['dropOffLocationLat']) : '',
            'do_longitude' => isset($params['dropOffLocationLng']) ? sanitize_text_field($params['dropOffLocationLng']) : '',
            'is_round_trip' => isset($params['isRoundTrip']) ? sanitize_text_field($params['isRoundTrip']) : '',
            'pu_return_datetime' => isset($params['returnPickupDateTime']) ? sanitize_text_field($params['returnPickupDateTime']) : '',
            'special_requests' => isset($params['special_requests']) ? sanitize_textarea_field($params['special_requests']) : '',
            'status' => 'pending'
        );
    }

    private function send_booking_to_external_api($api_base_url, $api_key, $booking_data) {
        $endpoint = '/api/save-quote-request';
        $url = trailingslashit($api_base_url) . ltrim($endpoint, '/');

        $headers = array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'WordPress/MEVP-Plugin/1.0'
        );

        $args = array(
            'method' => 'POST',
            'timeout' => 30,
            'headers' => $headers,
            'body' => json_encode($booking_data),
            'sslverify' => defined('WP_DEBUG') && WP_DEBUG ? false : true
        );

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MEVP Booking API Request URL: ' . $url);
            error_log('MEVP Booking API Request Body: ' . json_encode($booking_data));
        }

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            error_log('MEVP Booking API Error: ' . $response->get_error_message());
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($status_code < 200 || $status_code >= 300) {
            return new WP_Error(
                'api_error',
                sprintf('API returned %d: %s', $status_code, substr($body, 0, 200)),
                array('status' => $status_code)
            );
        }

        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', 'Invalid JSON response: ' . json_last_error_msg());
        }

        return $data;
    }

    private function save_booking_to_local_db($booking_data, $external_api_response) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'mevp_data';

        $local_data = array(
            'title' => 'booking_' . $this->generate_booking_id(),
            'content' => json_encode(array(
                'booking_data' => $booking_data,
                'external_response' => $external_api_response,
                'created_at' => current_time('mysql')
            )),
            'status' => isset($external_api_response['status']) ? $external_api_response['status'] : 'pending',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );

        $result = $wpdb->insert($table_name, $local_data);

        return $result ? $wpdb->insert_id : false;
    }

    private function generate_booking_id() {
        $prefix = 'BK';
        $date = date('Ymd');
        $random = strtoupper(substr(uniqid(), -6));
        return $prefix . $date . '_' . $random;
    }
}