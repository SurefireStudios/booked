<?php

// Deactivate the original add-on plugin.
add_action('init', 'deactivate_booked_wc_payments');
function deactivate_booked_wc_payments()
{
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    if (
        in_array(
            'booked-woocommerce-payments/booked-woocommerce-payments.php',
            apply_filters('active_plugins', get_option('active_plugins'))
        )
    ) {
        deactivate_plugins(
            plugin_basename(
                'booked-woocommerce-payments/booked-woocommerce-payments.php'
            )
        );
    }
}

// Global constants
define('BOOKED_WC_PLUGIN_PREFIX', 'booked_wc_');
define('BOOKED_WC_POST_TYPE', 'booked_appointments');
define('BOOKED_WC_TAX_CALENDAR', 'booked_custom_calendars');
define('BOOKED_WC_APPOINTMENTS_PAGE', 'booked-appointments');
define('BOOKED_WC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BOOKED_WC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BOOKED_WC_PLUGIN_AJAX_URL', admin_url('admin-ajax.php'));

// Plugin WooCommerce Libraries
require_once BOOKED_WC_PLUGIN_DIR .
    'lib/woocommerce/class-wc-prevent-purchasing.php';
require_once BOOKED_WC_PLUGIN_DIR .
    'lib/woocommerce/class-wc-meta-box-product.php';
require_once BOOKED_WC_PLUGIN_DIR . 'lib/woocommerce/class-wc-product.php';
require_once BOOKED_WC_PLUGIN_DIR . 'lib/woocommerce/class-wc-variation.php';
require_once BOOKED_WC_PLUGIN_DIR . 'lib/woocommerce/class-wc-order.php';
require_once BOOKED_WC_PLUGIN_DIR . 'lib/woocommerce/class-wc-order-item.php';
require_once BOOKED_WC_PLUGIN_DIR . 'lib/woocommerce/class-wc-cart.php';
require_once BOOKED_WC_PLUGIN_DIR . 'lib/woocommerce/class-wc-helper.php';
require_once BOOKED_WC_PLUGIN_DIR . 'lib/woocommerce/class-woocommerce.php';

// Default Plugin Libraries
require_once BOOKED_WC_PLUGIN_DIR . 'lib/class-settings.php';
require_once BOOKED_WC_PLUGIN_DIR . 'lib/class-wp-cron.php';
require_once BOOKED_WC_PLUGIN_DIR . 'lib/class-post-status.php';
require_once BOOKED_WC_PLUGIN_DIR . 'lib/class-fragments.php';
require_once BOOKED_WC_PLUGIN_DIR . 'lib/class-admin-notices.php';
require_once BOOKED_WC_PLUGIN_DIR . 'lib/class-enqueue-scripts.php';
require_once BOOKED_WC_PLUGIN_DIR . 'lib/class-wp-ajax.php';
require_once BOOKED_WC_PLUGIN_DIR . 'lib/class-json-response.php';
require_once BOOKED_WC_PLUGIN_DIR . 'lib/class-custom-fields.php';
require_once BOOKED_WC_PLUGIN_DIR . 'lib/class-static-functions.php';
require_once BOOKED_WC_PLUGIN_DIR . 'lib/class-appointment.php';
require_once BOOKED_WC_PLUGIN_DIR . 'lib/class-appointment-payment-status.php';
require_once BOOKED_WC_PLUGIN_DIR . 'lib/class-cleanup.php';
require_once BOOKED_WC_PLUGIN_DIR . 'lib/core.php';

// Setup
add_action('init', ['Booked_WC', 'setup']);
