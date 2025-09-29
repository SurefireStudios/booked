<?php

// Deactivate the original add-on plugin.
add_action('init', 'deactivate_booked_fea');
function deactivate_booked_fea()
{
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    if (
        in_array(
            'booked-frontend-agents/booked-frontend-agents.php',
            apply_filters('active_plugins', get_option('active_plugins'))
        )
    ) {
        deactivate_plugins(
            plugin_basename('booked-frontend-agents/booked-frontend-agents.php')
        );
    }
}

add_action('plugins_loaded', 'init_booked_fea');
function init_booked_fea()
{
    define('BOOKEDFEA_PLUGIN_DIR', dirname(__FILE__));
    define('BOOKEDFEA_PLUGIN_URL', BOOKED_PLUGIN_URL . '/includes/add-ons/frontend-agents');
    $bookedfea_plugin = new Booked_FEA_Plugin();
}

class Booked_FEA_Plugin
{
    public function __construct()
    {
        add_action('init', [&$this, 'booked_fea_init']);
        add_action('wp_enqueue_scripts', [&$this, 'front_end_scripts']);

        require_once sprintf('%s/includes/functions.php', BOOKEDFEA_PLUGIN_DIR);
        require_once sprintf(
            '%s/includes/shortcodes.php',
            BOOKEDFEA_PLUGIN_DIR
        );
        require_once sprintf('%s/includes/ajax.php', BOOKEDFEA_PLUGIN_DIR);

        $bookedfea_ajax = new BookedFEA_Ajax();
    }

    public function booked_fea_init()
    {
        if (
            is_user_logged_in() &&
            current_user_can('edit_booked_appointments')
        ):
            add_filter(
                'booked_profile_tab_content',
                [&$this, 'booked_fea_tabs'],
                1
            );
            add_filter('booked_profile_tabs', [&$this, 'booked_fea_tabs'], 1);
        endif;
    }

    public static function front_end_scripts()
    {
        wp_register_script(
            'booked-fea-js',
            BOOKEDFEA_PLUGIN_URL . '/js/functions.js',
            [],
            BOOKED_VERSION,
            true
        );
        $booked_fea_vars = [
            'ajax_url' => admin_url('admin-ajax.php'),
            'i18n_confirm_appt_delete' => __(
                'Are you sure you want to cancel this appointment?',
                'booked'
            ),
            'i18n_confirm_appt_approve' => __(
                'Are you sure you want to approve this appointment?',
                'booked'
            ),
        ];
        wp_localize_script(
            'booked-fea-js',
            'booked_fea_vars',
            $booked_fea_vars
        );
        wp_enqueue_script('booked-fea-js');
    }

    public function booked_fea_tabs($custom_tabs)
    {
        $custom_tabs = [
            'fea_appointments' => [
                'title' => __(
                    'Upcoming Appointments',
                    'booked'
                ),
                'fa-icon' => 'calendar-days',
                'class' => false,
            ],
            'fea_pending' => [
                'title' =>
                    __('Pending Appointments', 'booked') .
                    '<div class="counter"></div>',
                'fa-icon' => 'clock',
                'class' => false,
            ],
            'fea_history' => [
                'title' => __('Appointment History', 'booked'),
                'fa-icon' => 'calendar-days',
                'class' => false,
            ],
            'edit' => [
                'title' => __('Edit Profile', 'booked'),
                'fa-icon' => 'pencil',
                'class' => 'edit-button',
            ],
        ];

        return $custom_tabs;
    }
}
