<?php
class MEVP_Asset_Loader {
    
    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
    }
    
    public function enqueue_scripts($hook) {
        if ('toplevel_page_my-elementor-vue' !== $hook) {
            return;
        }
        
        // Enqueue Elementor-like styles
        wp_enqueue_style(
            'mevp-elementor-style',
            MEVP_PLUGIN_URL . 'admin/css/admin-style.css',
            array(),
            MEVP_VERSION
        );
        
        // Enqueue Vue build
        wp_enqueue_script(
            'mevp-vue-app',
            MEVP_PLUGIN_URL . 'admin/js/app.js',
            array(),
            MEVP_VERSION,
            true
        );
        
        // Localize script for API communication
        wp_localize_script('mevp-vue-app', 'mevp_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mevp_nonce'),
            'rest_url' => rest_url('mevp/v1/'),
            'rest_nonce' => wp_create_nonce('wp_rest')
        ));
    }
    
    public function enqueue_frontend_scripts() {
        // Enqueue for frontend if needed
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'my_vue_plugin')) {
            wp_enqueue_script('mevp-vue-app', MEVP_PLUGIN_URL . 'admin/js/app.js', array(), MEVP_VERSION, true);
            wp_localize_script('mevp-vue-app', 'mevp_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mevp_nonce'),
                'rest_url' => rest_url('mevp/v1/'),
                'rest_nonce' => wp_create_nonce('wp_rest')
            ));
        }
    }
}
