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

// Activation/Deactivation hooks
register_activation_hook(__FILE__, 'mevp_activate_plugin');
register_deactivation_hook(__FILE__, 'mevp_deactivate_plugin');

function mevp_activate_plugin() {
    require_once MEVP_PLUGIN_DIR . 'includes/class-db-handler.php';
    MEVP_DB_Handler::create_tables();
    flush_rewrite_rules();
}

function mevp_deactivate_plugin() {
    flush_rewrite_rules();
}

// Add admin menu
add_action('admin_menu', 'mevp_add_admin_menu');

function mevp_add_admin_menu() {
    add_menu_page(
        __('My Vue Plugin', 'my-elementor-vue'),
        __('Vue Plugin', 'my-elementor-vue'),
        'manage_options',
        'my-elementor-vue',
        'mevp_render_admin_page',
        'dashicons-admin-generic',
        30
    );
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
