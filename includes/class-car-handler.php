<?php
class MEVP_Car_Handler {

    public function get_cars_data($request) {
        global $wpdb;

        $settings = get_option('mevp_settings', array(
            'api_endpoint' => '',
            'cache_duration' => 3600,
            'max_items' => 100
        ));

        $table_name = $wpdb->prefix . 'mevp_data';

        $record = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE title = %s",
                'cars_data'
            )
        );

        if (!$record || empty($record->content)) {
            return rest_ensure_response(array(
                'success' => false,
                'message' => 'No cars data found in database. Please fetch cars data from API first via settings page.',
                'data' => null,
                'status' => 'not_found'
            ));
        }

        $cars_data = json_decode($record->content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return rest_ensure_response(array(
                'success' => false,
                'message' => 'Invalid JSON data in database: ' . json_last_error_msg(),
                'data' => null,
                'status' => 'invalid_data'
            ));
        }

        $cache_key = 'mevp_cars_data_response';
        $cached_response = get_transient($cache_key);

        if ($cached_response !== false) {
            return rest_ensure_response(array(
                'success' => true,
                'cached' => true,
                'from_database' => false,
                'last_updated' => $record->updated_at ?? $record->created_at,
                'total_cars' => is_array($cars_data) ? count($cars_data) : 0,
                'data' => $cars_data
            ));
        } else {
            $cache_duration = isset($settings['cache_duration']) ? $settings['cache_duration'] : 3600;
            set_transient($cache_key, $cars_data, $cache_duration);

            return rest_ensure_response(array(
                'success' => true,
                'cached' => false,
                'from_database' => true,
                'cache_duration' => $cache_duration,
                'last_updated' => $record->updated_at ?? $record->created_at,
                'status' => $record->status,
                'total_cars' => is_array($cars_data) ? count($cars_data) : 0,
                'data' => $cars_data
            ));
        }
    }
}