<?php

class Booked_WC_Cleanup {

	protected $appointments = array();

	private function __construct() {
		$this->appointments_to_clean();
		$this->clean();
	}

	public static function start() {
		return new self();
	}

	protected function clean() {
		foreach ($this->appointments as $app) {
			$app_meta = get_post_meta($app->ID);
			if ( !isset($app_meta['_booked_wc_appointment_order_id']) || isset($app_meta['_booked_wc_appointment_order_id']) && !$app_meta['_booked_wc_appointment_order_id'] ):
				wp_delete_post($app->ID, true);
			endif;
		}
	}

	protected function appointments_to_clean() {

		add_action('pre_get_posts', array($this, 'pre_get_posts'));

		$this->appointments = get_posts(array(
			'posts_per_page' => -1,
			'post_type' => BOOKED_WC_POST_TYPE,
			'post_status' => array('any'),
			'suppress_filters' => false,
			'_' . BOOKED_WC_PLUGIN_PREFIX . 'cleanup' => true
		));

		remove_action('pre_get_posts', array($this, 'pre_get_posts'));
		return $this;

	}

	public function pre_get_posts( $query ) {

		if ( !$query->get( '_' . BOOKED_WC_PLUGIN_PREFIX . 'cleanup') ) {
			return $query;
		}

		add_filter('posts_where', array($this, 'posts_where'));
		return $query;

	}

	public function posts_where( $where ) {
		remove_filter('posts_where', array($this, 'posts_where'));

		$time_created_meta_key = '_' . BOOKED_WC_PLUGIN_PREFIX . 'time_created';
		$custom_fields_meta_key = '_cf_meta_value';
		$post_type = BOOKED_WC_POST_TYPE;

		$mode = Booked_WC_Settings::get_option('cleanup_mode');
		$available_schedules = wp_get_schedules();
		$schedule_details = $available_schedules[$mode];
		$current_time = current_time('timestamp');
		$time_to_compare = $current_time - esc_sql($schedule_details['interval']);

		global $wpdb;
		$appointment_to_delete = "SELECT p.ID FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} time_created ON (p.ID = time_created.post_id AND time_created.meta_key = '{$time_created_meta_key}')
				INNER JOIN {$wpdb->postmeta} custom_fields ON (p.ID = custom_fields.post_id AND custom_fields.meta_key = '{$custom_fields_meta_key}')
				WHERE p.post_type = '{$post_type}'
				AND CAST(time_created.meta_value AS SIGNED) < '{$time_to_compare}'
				AND custom_fields.meta_value LIKE '%product_id::%'";

		$where .= "AND `$wpdb->posts`.`ID` IN ( {$appointment_to_delete} )";

		return $where;
	}
}