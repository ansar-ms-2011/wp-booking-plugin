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
            <div class="mevp-cars-container">
                <h2>List of available Cars</h2>
                <table class="wp-list-table widefat fixed striped posts" id="wp-cars-list-table"></table>
                <div class="mevp-save-button-container">
                    <button class="button button-primary mevp-save-button" id="btnFetchCars">Fetch Active Cars</button>
                </div>
            </div>
            <!--            <div class="mevp-services-container">-->
            <!--                <h2>List of available Service Types</h2>-->
            <!--                <table class="wp-list-table widefat fixed striped posts" id="wp-services-list-table"></table>-->
            <!--                <div class="mevp-save-button-container">-->
            <!--                    <button class="button button-primary mevp-save-button" id="fetchServicesBtn">Fetch Active Services-->
            <!--                    </button>-->
            <!--                </div>-->
            <!--            </div>-->
        </div>

        <script>
            jQuery(document).ready(function ($) {
                // Toggle password visibility
                $('.toggle-visibility').on('click', function () {
                    var input = $(this).closest('td').find('input');
                    var type = input.attr('type') === 'password' ? 'text' : 'password';
                    input.attr('type', type);
                    $(this).text(type === 'password' ? 'Show' : 'Hide');
                });

                // Load stored cars on page load
                loadStoredCars();

                // Fetch cars button click handler
                $('#btnFetchCars').on('click', function () {
                    var $button = $(this);
                    var $table = $('#wp-cars-list-table');

                    // Disable button and show loading state
                    $button.prop('disabled', true).text('Fetching from API...');

                    // Show loading indicator in table
                    $table.html('<tr><td colspan="100" style="text-align: center;">Fetching cars from API... <span class="spinner is-active" style="float: none;"></span></td></tr>');

                    // Make AJAX request to fetch and store cars
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'mevp_fetch_cars',
                            nonce: '<?php echo wp_create_nonce('mevp_fetch_cars_nonce'); ?>'
                        },
                        success: function (response) {
                            if (response.success) {
                                // Show success message
                                showNotice('success', response.data.message + ' Total cars: ' + response.data.total_cars);
                                // Reload the cars table
                                loadStoredCars();
                            } else {
                                showNotice('error', response.data);
                                displayError(response.data);
                            }
                        },
                        error: function (xhr, status, error) {
                            showNotice('error', 'AJAX error: ' + error);
                            displayError('AJAX error: ' + error);
                        },
                        complete: function () {
                            $button.prop('disabled', false).text('Fetch Cars');
                        }
                    });
                });

                // Function to load stored cars from database
                function loadStoredCars() {
                    var $table = $('#wp-cars-list-table');

                    $table.html('<tr><td colspan="100" style="text-align: center;">Loading stored cars... <span class="spinner is-active" style="float: none;"></span></td></tr>');

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'mevp_load_stored_cars',
                            nonce: '<?php echo wp_create_nonce('mevp_fetch_cars_nonce'); ?>'
                        },
                        success: function (response) {
                            if (response.success) {
                                displayCarsTable(response.data.cars, response.data.last_updated);
                            } else {
                                displayEmptyState(response.data);
                            }
                        },
                        error: function () {
                            displayEmptyState('Unable to load data');
                        }
                    });
                }

                // Function to display cars in table with fixed fields
                function displayCarsTable(cars, lastUpdated) {
                    var $table = $('#wp-cars-list-table');

                    if (!cars || cars.length === 0) {
                        displayEmptyState('No cars found in database.');
                        return;
                    }

                    // Remove existing info notice
                    $('.mevp-cars-info').remove();

                    // Add last updated info
                    if (lastUpdated) {
                        $table.before('<div class="mevp-cars-info notice notice-info"><p><strong>Last Updated:</strong> ' + lastUpdated + ' | <strong>Total Cars:</strong> ' + cars.length + '</p></div>');
                    }

                    // Define the fixed fields to display
                    let fields = [
                        {key: 'id', label: 'ID', width: '60'},
                        {key: 'name', label: 'Car Name', width: 'auto'},
                        {key: 'specifications', label: 'Specifications', width: 'auto'},
                        {key: 'max_passengers', label: 'Max Passengers', width: '120'},
                        {key: 'max_luggage', label: 'Max Luggage', width: '120'},
                        {key: 'active', label: 'Status', width: '100'}
                    ];

                    // Build table header
                    let html = '<thead><tr>';

                    for (let i = 0; i < fields.length; i++) {
                        let width = fields[i].width !== 'auto' ? ' width="' + fields[i].width + '"' : '';
                        html += '<th' + width + '>' + escapeHtml(fields[i].label) + '</th>';
                    }
                    html += '</tr></thead><tbody>';

                    // Add car data rows
                    for (let j = 0; j < cars.length; j++) {
                        let car = cars[j];
                        html += '<tr>';

                        // Display each field
                        for (let k = 0; k < fields.length; k++) {
                            let fieldKey = fields[k].key;
                            let value = getFieldValue(car, fieldKey);
                            let displayValue = formatDisplayValue(value, fieldKey);
                            html += '<td>' + displayValue + '</td>';
                        }

                        html += '</tr>';
                    }

                    html += '</tbody>';
                    $table.html(html);
                }

                // Helper function to get field value from car object
                function getFieldValue(car, fieldKey) {
                    // Handle nested fields if needed (e.g., specifications.engine)
                    if (fieldKey.indexOf('.') !== -1) {
                        let parts = fieldKey.split('.');
                        let value = car;
                        for (let i = 0; i < parts.length; i++) {
                            if (value && typeof value === 'object' && parts[i] in value) {
                                value = value[parts[i]];
                            } else {
                                return '';
                            }
                        }
                        return value;
                    }

                    // Direct field access
                    if (car && fieldKey in car) {
                        return car[fieldKey];
                    }

                    // Try alternative common field names
                    let alternativeMappings = {
                        'max_passengers': ['passengers', 'seats', 'max_seats', 'capacity'],
                        'max_luggage': ['luggage', 'bags', 'luggage_capacity', 'boot_space'],
                        'active': ['status', 'is_active', 'available', 'is_available']
                    };

                    if (alternativeMappings[fieldKey]) {
                        for (let i = 0; i < alternativeMappings[fieldKey].length; i++) {
                            let altKey = alternativeMappings[fieldKey][i];
                            if (car && altKey in car) {
                                return car[altKey];
                            }
                        }
                    }

                    return '';
                }

                // Format display value based on field type
                function formatDisplayValue(value, fieldKey) {
                    if (value === null || value === undefined || value === '') {
                        return '<span style="color: #999;">-</span>';
                    }

                    // Format active/status field
                    if (fieldKey === 'active') {
                        if (value === true || value === 1 || value === '1' || value === 'true' || value === 'active' || value === 'yes') {
                            return '<span style="color: green; font-weight: bold;">✓ Active</span>';
                        } else {
                            return '<span style="color: red;">✗ Inactive</span>';
                        }
                    }

                    // Format specifications (could be object or string)
                    if (fieldKey === 'specifications') {
                        if (typeof value === 'object') {
                            // Display as formatted list if object
                            let specs = [];
                            for (let key in value) {
                                if (value.hasOwnProperty(key)) {
                                    specs.push('<strong>' + escapeHtml(key) + ':</strong> ' + escapeHtml(String(value[key])));
                                }
                            }
                            if (specs.length > 0) {
                                return '<ul style="margin: 0; padding-left: 15px;">' + specs.map(function (s) {
                                    return '<li>' + s + '</li>';
                                }).join('') + '</ul>';
                            }
                            return escapeHtml(JSON.stringify(value));
                        }
                        return escapeHtml(String(value));
                    }

                    // Format numbers
                    if (typeof value === 'number') {
                        return value.toLocaleString();
                    }

                    // Format boolean
                    if (typeof value === 'boolean') {
                        return value ? '✓ Yes' : '✗ No';
                    }

                    // Default: escape HTML and return
                    return escapeHtml(String(value));
                }

                // Display empty state
                function displayEmptyState(message) {
                    let $table = $('#wp-cars-list-table');
                    $('.mevp-cars-info').remove();
                    let msg = message || 'No cars data available. Click "Fetch Cars" to load data from API.';
                    $table.html('<tr><td colspan="100" style="text-align: center; padding: 40px;">📦 ' + escapeHtml(msg) + '</td></tr>');
                }

                // Escape HTML to prevent XSS
                function escapeHtml(text) {
                    if (!text) return '';
                    let map = {
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#39;'
                    };
                    return String(text).replace(/[&<>"']/g, function (m) {
                        return map[m];
                    });
                }

                // Display error message
                function displayError(message) {
                    let $table = $('#wp-cars-list-table');
                    $table.html('<tr><td colspan="100" style="color: red; text-align: center; padding: 20px;">⚠️ Error: ' + escapeHtml(message) + '</td></tr>');
                }

                // Show notice message
                function showNotice(type, message) {
                    let noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
                    let $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + escapeHtml(message) + '</p></div>');

                    $('.mevp-settings-container').prepend($notice);

                    // Make dismissible
                    $notice.find('.notice-dismiss').on('click', function () {
                        $notice.fadeOut(function () {
                            $(this).remove();
                        });
                    });

                    // Auto dismiss after 5 seconds
                    setTimeout(function () {
                        $notice.fadeOut(function () {
                            $(this).remove();
                        });
                    }, 5000);
                }

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

        .mevp-settings-container {
            max-width: 1200px;
            margin: 20px auto;
        }

        .mevp-settings-form {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        /* Table styles */
        .wp-list-table {
            margin-top: 10px;
            background: #fff;
        }

        .wp-list-table th {
            font-weight: bold;
            background-color: #f1f1f1;
            padding: 12px 10px;
        }

        .wp-list-table td {
            padding: 10px;
            vertical-align: top;
        }

        .wp-list-table ul {
            margin: 0;
        }

        .wp-list-table li {
            margin: 2px 0;
        }

        /* Button styles */
        #btnFetchCars {
            margin-right: 10px;
            margin-bottom: 10px;
        }

        .spinner.is-active {
            visibility: visible;
            float: none;
            margin: 0 5px;
        }

        /* Notice styles */
        .mevp-cars-info {
            margin: 10px 0;
        }
    </style>
    <?php
}

?>