<?php
class MEVP_API_Handler {
    private $car_handler;
    private $booking_handler;
    public function __construct() {
        $this->car_handler = new MEVP_Car_Handler();
        $this->booking_handler = new MEVP_Booking_Handler();

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

        register_rest_route('mevp/v1', '/save-booking', array(
            'methods' => 'POST',
            'callback' => array($this, 'save_booking_data'),
            'permission_callback' => array($this, 'check_permission')
        ));
    }

    public function save_booking_data($request)
    {
        return $this->booking_handler->save_booking_data($request);
    }
    
    public function check_permission() {
        //  return current_user_can('manage_options');
        return current_user_can('read') || true;
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
        global $wpdb;

        // Get settings
        $settings = get_option('mevp_settings', array(
            'api_endpoint' => '',
            'cache_duration' => 3600,
            'max_items' => 100
        ));

        $table_name = $wpdb->prefix . 'mevp_data';

        // Get car data from database
        $record = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE title = %s",
                'cars_data'
            )
        );

        // Check if data exists
        if (!$record || empty($record->content)) {
            return rest_ensure_response(array(
                'success' => false,
                'message' => 'No cars data found in database. Please fetch cars data from API first via settings page.',
                'data' => null,
                'status' => 'not_found'
            ));
        }

        // Decode JSON content
        $cars_data = json_decode($record->content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return rest_ensure_response(array(
                'success' => false,
                'message' => 'Invalid JSON data in database: ' . json_last_error_msg(),
                'data' => null,
                'status' => 'invalid_data'
            ));
        }

        // Create cache key for the response
        $cache_key = 'mevp_cars_data_response';
        $cached_response = get_transient($cache_key);

        // Return cached response if available
        if ($cached_response !== false) {
            return rest_ensure_response(array(
                'success' => true,
                'cached' => true,
                'from_database' => false,
                'last_updated' => $record->updated_at ?? $record->created_at,
                'total_cars' => is_array($cars_data) ? count($cars_data) : 0,
                'data' => $cars_data
            ));
        }else{
            // Cache the response
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
