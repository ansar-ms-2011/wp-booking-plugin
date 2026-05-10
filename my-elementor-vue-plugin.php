<?php
/**
 * Plugin Name: Wp Booking Plugin
 * Plugin URI: https://ride2theairports.com
 * Description: WordPress plugin with Vue.js and Elementor-style UI
 * Version: 1.0.0
 * Author: Ansar Mehmood Khan
 * Author URI: https://ansarkhan.com
 * License: GPL v2 or later
 * Text Domain: my-elementor-vue
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MEVP_VERSION', '1.0.0');
define('MEVP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MEVP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MEVP_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Initialize plugin
add_action('plugins_loaded', 'mevp_init_plugin');

function mevp_init_plugin() {
    // Load required files
    require_once MEVP_PLUGIN_DIR . 'includes/class-asset-loader.php';
    require_once MEVP_PLUGIN_DIR . 'includes/class-api-handler.php';
    require_once MEVP_PLUGIN_DIR . 'includes/class-db-handler.php';
    
    // Initialize classes
    new MEVP_Asset_Loader();
    new MEVP_API_Handler();
}


function mevp_deactivate_plugin() {
    flush_rewrite_rules();
}

function mevp_render_admin_page() {
    include MEVP_PLUGIN_DIR . 'admin/views/main-app.php';
}

// Add shortcode for frontend
add_shortcode('my_vue_plugin', 'mevp_frontend_shortcode');

function mevp_frontend_shortcode() {
    ob_start();
    include MEVP_PLUGIN_DIR . 'admin/views/main-app.php';
    return ob_get_clean();
}

// Load settings and helpers
require_once plugin_dir_path(__FILE__) . 'admin/settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/helpers.php';

// Enqueue scripts and pass settings to your React app
add_action('admin_enqueue_scripts', 'mevp_admin_enqueue_scripts');
function mevp_admin_enqueue_scripts($hook) {
    // Only load on your plugin page
    if ($hook !== 'toplevel_page_mevp-admin' && $hook !== 'mevp_page_mevp-settings') {
        return;
    }

    // Pass settings to JavaScript
    wp_localize_script('mevp-app', 'mevpSettings', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mevp_nonce'),
        'googleMapsApiKey' => mevp_get_google_maps_api_key(),
        'apiBaseUrl' => mevp_get_api_base_url(),
    ]);
}

function mevp_get_encryption_key() {
    // Try to get existing key
    $encryption_key = get_option('mevp_encryption_key');

    if (empty($encryption_key)) {
        // Generate a new secure key
        $encryption_key = base64_encode(openssl_random_pseudo_bytes(32));
        add_option('mevp_encryption_key', $encryption_key, '', 'no');
    }

    return $encryption_key;
}

// Activation/Deactivation hooks
register_activation_hook(__FILE__, 'mevp_activate_plugin');
register_deactivation_hook(__FILE__, 'mevp_deactivate_plugin');

function mevp_activate_plugin() {
    require_once MEVP_PLUGIN_DIR . 'includes/class-db-handler.php';
    MEVP_DB_Handler::create_tables();
    flush_rewrite_rules();
    mevp_get_encryption_key();
}

