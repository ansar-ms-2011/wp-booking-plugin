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
    }
    
    public function check_permission() {
        return current_user_can('manage_options');
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
    
    public function get_settings() {
        $settings = get_option('mevp_settings', array(
            'api_endpoint' => '',
            'cache_duration' => 3600,
            'max_items' => 100
        ));
        
        return rest_ensure_response(array(
            'success' => true,
            'data' => $settings
        ));
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
}
