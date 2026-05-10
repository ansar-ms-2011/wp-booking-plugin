<?php
// Add admin menu
add_action('admin_menu', 'mevp_add_admin_menu');
function mevp_add_admin_menu()
{
    add_options_page(
            'MEVP Settings',
            'MEVP Settings',
            'manage_options',
            'mevp-settings',
            'mevp_settings_page'
    );
}

add_action('admin_init', 'mevp_register_settings');
function mevp_register_settings()
{
    register_setting('mevp_settings_group', 'mevp_google_maps_api_key', [
            'sanitize_callback' => 'mevp_encrypt_sensitive_data'
    ]);
    register_setting('mevp_settings_group', 'mevp_api_base_url');
    register_setting('mevp_settings_group', 'mevp_api_key', [
            'sanitize_callback' => 'mevp_encrypt_sensitive_data'
    ]);
}

// Sanitize and encrypt sensitive data
function mevp_encrypt_sensitive_data($input)
{
    if (empty($input)) {
        return '';
    }

    // Check if already encrypted (starts with pattern)
    if (strpos($input, 'MEVP_ENC:') === 0) {
        return $input;
    }

    // Encrypt the data
    $encrypted = MEVP_Encryption::encrypt($input);

    // Add prefix to identify encrypted data
    return 'MEVP_ENC:' . $encrypted;
}

// Settings page HTML
function mevp_settings_page()
{
    // Handle manual save to ensure encryption
    if (isset($_POST['submit']) && check_admin_referer('mevp_settings_action', 'mevp_settings_nonce')) {
        $google_maps_key = sanitize_text_field(isset($_POST['mevp_google_maps_api_key']) ? $_POST['mevp_google_maps_api_key'] : '');
        $api_key = sanitize_text_field(isset($_POST['mevp_api_key']) ? $_POST['mevp_api_key'] : '');
        $base_url = esc_url_raw(isset($_POST['mevp_api_base_url']) ? $_POST['mevp_api_base_url'] : '');

        // Encrypt and save
        if (!empty($google_maps_key)) {
            update_option('mevp_google_maps_api_key', mevp_encrypt_sensitive_data($google_maps_key));
        }

        if (!empty($api_key)) {
            update_option('mevp_api_key', mevp_encrypt_sensitive_data($api_key));
        }

        update_option('mevp_api_base_url', $base_url);

        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }

    // Get current values (decrypt for display)
    $google_maps_key = mevp_get_google_maps_api_key();
    $api_key = mevp_get_api_key();
    $base_url = get_option('mevp_api_base_url', '');
    ?>
    <div class="wrap">
        <div class="mevp-settings-container">
            <h1>Booking Plugin Settings</h1>
            <div class="notice notice-info">
                <p><strong>Security Note:</strong> API keys are encrypted before being stored in the database.</p>
            </div>
            <form method="post" action="" class="mevp-settings-form">
                <?php wp_nonce_field('mevp_settings_action', 'mevp_settings_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="mevp_api_base_url">API Base URL</label>
                        </th>
                        <td>
                            <input type="url"
                                   name="mevp_api_base_url"
                                   id="mevp_api_base_url"
                                   value="<?php echo esc_attr($base_url); ?>"
                                   class="regular-text"/>
                            <p class="description">
                                <span style="font-size: 12px;">Enter the base URL for your API (not encrypted)</span>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="mevp_api_key">API Key</label>
                        </th>
                        <td>
                            <input type="password"
                                   name="mevp_api_key"
                                   id="mevp_api_key"
                                   value="<?php echo esc_attr($api_key); ?>"
                                   class="regular-text mevp-api-key"
                                   autocomplete="off"/>
                            <p class="description">
                                <button type="button" class="button toggle-visibility">Show/Hide</button>
                                <span style="font-size: 12px;">Enter your API authentication key (automatically encrypted)</span>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="mevp_google_maps_api_key">Google Maps API Key</label>
                        </th>
                        <td>
                            <input type="password"
                                   name="mevp_google_maps_api_key"
                                   id="mevp_google_maps_api_key"
                                   value="<?php echo esc_attr($google_maps_key); ?>"
                                   class="regular-text mevp-google-maps-key"
                                   autocomplete="off"/>
                            <p class="description">
                                <button type="button" class="button toggle-visibility">Show/Hide</button>
                                <span style="font-size: 12px;">Enter your Google Maps API key (automatically encrypted)</span>
                            </p>
                        </td>
                    </tr>
                </table>
                <hr>
                <div class="mevp-save-button-container">
                    <?php
                    echo get_submit_button(
                            'Save Settings',
                            'primary mevp-save-button', // Add class directly in type parameter
                            'submit',
                            false,
                            array(
                                    'class' => 'mevp-save-button' // This will be ADDED to existing classes
                            )
                    );
                    ?>
                </div>
            </form>
        </div>

        <script>
            jQuery(document).ready(function ($) {
                $('.toggle-visibility').on('click', function () {
                    var input = $(this).closest('td').find('input');
                    var type = input.attr('type') === 'password' ? 'text' : 'password';
                    input.attr('type', type);
                    $(this).text(type === 'password' ? 'Show' : 'Hide');
                });
            });
        </script>
    </div>
    <style>
        .mevp-settings-container {
            max-width: 900px;
            margin: 20px auto;
        }
        .mevp-settings-form {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .mevp-google-maps-key, .mevp-api-key {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
        }

        .mevp-save-button-container {
            text-align: center;
            margin-top: 20px;
        }

        .mevp-save-button {
            min-width: 250px;
            padding: 10px 20px;
        }
    </style>
    <?php
}
?>