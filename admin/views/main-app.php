<?php
$google_maps_api_key = mevp_get_google_maps_api_key();

// Localize script to pass data to Vue
wp_localize_script('mevp-app-script', 'mevpData', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mevp_nonce'),
        'googleMapsApiKey' => $google_maps_api_key,
        'apiBaseUrl' => mevp_get_api_base_url(),
        'apiKey' => mevp_get_api_key(),
]);

?>
<div class="wrap mevp-admin-wrap">
    <div id="mevp-app">
        <div class="mevp-loading">
            <div class="elementor-loader-wrapper">
                <div class="elementor-loader">
                    <div class="elementor-loader-boxes">
                        <div class="elementor-loader-box"></div>
                        <div class="elementor-loader-box"></div>
                        <div class="elementor-loader-box"></div>
                        <div class="elementor-loader-box"></div>
                    </div>
                </div>
                <div class="elementor-loading-title">Loading plugin ..</div>
            </div>
        </div>
    </div>
</div>
