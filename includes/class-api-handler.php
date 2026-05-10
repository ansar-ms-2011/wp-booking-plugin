<?php
class MEVP_API_Handler {
    
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_action('wp_ajax_mevp_ajax_action', array($this, 'handle_ajax_request'));
        add_action('wp_ajax_nopriv_mevp_ajax_action', array($this, 'handle_ajax_request'));
    }
    
    public function register_rest_routes() {
        register_rest_route('mevp/v1', '/data', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_data'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        register_rest_route('mevp/v1', '/data/(?P<id>\d+)', array(
            'methods' => 'POST',
            'callback' => array($this, 'save_data'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        register_rest_route('mevp/v1', '/data/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_data'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        register_rest_route('mevp/v1', '/settings', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_settings'),
            'permission_callback' => array($this, 'check_permission')
        ));

        register_rest_route('mevp/v1', '/get-cars', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_cars_data'),
            'permission_callback' => array($this, 'check_permission')
        ));
    }
    
    public function check_permission() {
//        return current_user_can('manage_options');
        return true;
    }
    
    public function get_settings() {
        $settings = get_option('mevp_settings', array(
            'api_endpoint' => '',
            'cache_duration' => 3600,
            'max_items' => 100
        ));
        
        return rest_ensure_response(array(
            'success' => true,
            'data' => [
                ...$settings,
                'googleMapsApiKey' => mevp_get_google_maps_api_key(),
                'apiBaseUrl' => mevp_get_api_base_url(),
                'apiKey' => mevp_get_api_key()
            ]
        ));
    }

    public function get_cars_data($request) {

        // Get settings
        $settings = get_option('mevp_settings', array(
            'api_endpoint' => '',
            'cache_duration' => 3600,
            'max_items' => 100
        ));

        // Get API credentials
        $api_base_url = mevp_get_api_base_url();
        $api_key = mevp_get_api_key();

        // Validate API configuration
        if (empty($api_base_url) || empty($api_key)) {
            return rest_ensure_response(array(
                'success' => false,
                'message' => 'API configuration is missing. Please configure API Base URL and API Key in plugin settings.',
                'data' => null
            ));
        }

        // Create cache key
        $cache_key = 'mevp_cars_data_' . md5($api_base_url);
        $cached_data = get_transient($cache_key);

        // Return cached data if available
        if ($cached_data !== false) {
            return rest_ensure_response(array(
                'success' => true,
                'cached' => true,
                'data' => $cached_data
            ));
        }

        // Call external API with parameters
        $external_api_response = $this->call_external_cars_api(
            $api_base_url,
            $api_key,
        );

        if (is_wp_error($external_api_response)) {
            return rest_ensure_response(array(
                'success' => false,
                'message' => $external_api_response->get_error_message(),
                'error_code' => $external_api_response->get_error_code(),
                'data' => null
            ));
        }

        // Cache the response
        $cache_duration = isset($settings['cache_duration']) ? $settings['cache_duration'] : 3600;
        set_transient($cache_key, $external_api_response, $cache_duration);

        return rest_ensure_response(array(
            'success' => true,
            'cached' => false,
            'cache_duration' => $cache_duration,
            'data' => array(
                'cars' => $external_api_response,
            )
        ));
    }

    /**
     * Call external cars API with pagination
     */
    private function call_external_cars_api($api_base_url, $api_key) {
        $endpoint = '/api/get-vehicle-types';

        // Build URL with query parameters
        $url = add_query_arg(array(
            'status' => 'available'
        ), trailingslashit($api_base_url) . ltrim($endpoint, '/'));

        // Prepare request headers
        $headers = array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'WordPress/MEVP-Plugin/1.0'
        );

        $args = array(
            'method' => 'GET',
            'timeout' => 30,
            'headers' => $headers,
            'sslverify' => false, // Set to false only for development
        );

        // Make the request
        $response = wp_remote_request($url, $args);

        // Log request for debugging (remove in production)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MEVP API Request URL: ' . $url);
            error_log('MEVP API Response Code: ' . wp_remote_retrieve_response_code($response));
        }

        // Handle WP errors
        if (is_wp_error($response)) {
            error_log('MEVP API Error: ' . $response->get_error_message());
            return $response;
        }

        // Check status code
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200 && $status_code !== 201) {
            $body = wp_remote_retrieve_body($response);
            return new WP_Error(
                'api_error',
                sprintf('API returned %d: %s', $status_code, substr($body, 0, 200)),
                array('status' => $status_code)
            );
        }

        // Parse response
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error(
                'json_error',
                'Invalid JSON response: ' . json_last_error_msg()
            );
        }

        return $data;
    }
    
    public function handle_ajax_request() {
        check_ajax_referer('mevp_nonce', 'nonce');
        
        $sub_action = sanitize_text_field($_POST['sub_action']);
        
        switch($sub_action) {
            case 'get_data':
                $data = $this->get_custom_data();
                wp_send_json_success($data);
                break;
            default:
                wp_send_json_error('Invalid action');
        }
    }
    
    private function get_custom_data() {
        return array('example' => 'data');
    }

    public function get_data() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mevp_data';

        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC");

        return rest_ensure_response(array(
            'success' => true,
            'data' => $results
        ));
    }

    public function save_data($request) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mevp_data';

        $params = $request->get_json_params();
        $id = $request->get_param('id');

        $data = array(
            'title' => sanitize_text_field($params['title']),
            'content' => sanitize_textarea_field($params['content']),
            'status' => sanitize_text_field($params['status']),
            'updated_at' => current_time('mysql')
        );

        if ($id && $id > 0) {
            $wpdb->update($table_name, $data, array('id' => $id));
            $message = 'Data updated successfully';
        } else {
            $data['created_at'] = current_time('mysql');
            $wpdb->insert($table_name, $data);
            $id = $wpdb->insert_id;
            $message = 'Data created successfully';
        }

        return rest_ensure_response(array(
            'success' => true,
            'message' => $message,
            'id' => $id
        ));
    }

    public function delete_data($request) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mevp_data';
        $id = $request->get_param('id');

        $wpdb->delete($table_name, array('id' => $id));

        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Data deleted successfully'
        ));
    }
}
