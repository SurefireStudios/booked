<?php

class Booked_WC_Appointment {

	private static $appointments = array();

	public $post_id;
	public $user_id;
	public $order_id = null;

	public $timestamp;
	public $timeslot;
	public $timeslot_text;
	public $custom_fields = array();

	public $is_paid = false;
	public $payment_status;
	public $payment_status_text;

	public $products = array();

	public $post;

	public $calendar = null;
	public $products_extended = array();

	private function __construct( $post_id ) {
		$this->post_id = $post_id;
		$this->get_data();
		$this->get_metas();
		$this->get_custom_fields_info();
		$this->get_timeslot_text();
		$this->get_calendar();
		$this->get_products();
		$this->get_products_data();
		$this->get_order();
		$this->get_payment_status();
	}

	public static function get( $post_id=null ) {
		if ( !is_integer($post_id) ) {
			$message = sprintf( __('%s integer expected when %s given.', 'booked'), 'Booked_WC_Appointment::get($post_id)', gettype($post_id) );
			throw new Exception($message);
		} else if ( $post_id===0 ) {
			self::$appointments[$post_id] = false;
		} else if ( !isset(self::$appointments[$post_id]) ) {
			self::$appointments[$post_id] = new self($post_id);
		}

		return self::$appointments[$post_id];
	}

	protected function get_data() {

		$this->post = get_post($this->post_id);

		if ( !$this->post ) {
			return false;
		}

		return $this;
	}

	protected function get_metas() {
		$this->timestamp = get_post_meta($this->post_id, '_appointment_timestamp', true);
		$this->timeslot = get_post_meta($this->post_id, '_appointment_timeslot', true);
		$this->user_id = get_post_meta($this->post_id, '_appointment_user', true);
		$this->title = get_post_meta($this->post_id, '_appointment_title', true);

		return $this;
	}

	protected function get_custom_fields_info() {
		$i = 0;
		$meta_exists = true;
		$separator = '--SEP--';
		$meta_key = '_' . BOOKED_WC_PLUGIN_PREFIX . 'cfield_';

		do {
			$this_meta_key = $meta_key . strval($i);
			$meta_value = get_post_meta($this->post_id, $this_meta_key, true);

			if ( empty($meta_value) || !strpos($meta_value, $separator) ) {
				$meta_exists = false;
				break;
			}

			$meta_value_parts = explode($separator, $meta_value);

			$label = $meta_value_parts[0];
			$value = $meta_value_parts[1];

			$this->custom_fields[$label] = $value;

			$i++;
		} while ( $meta_exists );

		return $this;
	}

	protected function get_timeslot_text() {

		global $timeslot_saved,$timestamp_saved;

		if ( !$this->timeslot && isset($timeslot_saved) && $timeslot_saved ):

			$this->timeslot = $timeslot_saved;

		endif;

		if ( !$this->timestamp && isset($timestamp_saved) && $timestamp_saved ):

			$this->timestamp = $timestamp_saved;

		endif;

		if ( !empty($this->timeslot) ):

			$timeslots = explode('-', $this->timeslot);
			$time_format = get_option('time_format');
			$date_format = get_option('date_format');

			$time_start = date_i18n($time_format, strtotime($timeslots[0]));
			$time_end = date_i18n($time_format, strtotime($timeslots[1]));

			$hide_end_times = get_option('booked_hide_end_times');

			if ( $this->timestamp ):
				$timestamp_saved = $this->timestamp;
			endif;

			$day_year = date_i18n($date_format, $timestamp_saved);

			$show_only_titles = get_option('booked_show_only_titles');
			if ( $show_only_titles && $this->title ){

				$timeslotText = $this->title;

			} else {

				if ($timeslots[0] == '0000' && $timeslots[1] == '2400') {
					$timeslotText = $day_year . ' (' . esc_html__('All day','booked') . ')';
				} else if ( !$hide_end_times ) {
					$timeslotText = sprintf(__('from %1$s to %2$s on %3$s', 'booked'), $time_start, $time_end, $day_year);
				} else if ( $hide_end_times ) {
					$timeslotText = sprintf(__('at %1$s on %2$s', 'booked'), $time_start, $day_year);
				} else {
					$timeslotText = 'N/A';
				}

			}

			$this->timeslot_text = $timeslotText;
			$timeslot_saved = $this->timeslot;

			return $this;

		endif;

	}

	protected function get_calendar() {
		$calendars = wp_get_post_terms($this->post_id, BOOKED_WC_TAX_CALENDAR);

		if ( !is_wp_error($calendars) && isset($calendars[0]) ) {
			$calendar_obj = $calendars[0];
			$calendar_page = Booked_WC_Functions::get_calendar_page_info($calendar_obj->term_id);
		} else {
			$calendar_obj = false;
			$calendar_page = Booked_WC_Functions::get_calendar_page_info();
		}

		$this->calendar = (object) array(
			'calendar_obj' => $calendar_obj,
			'calendar_page' => $calendar_page,
			'calendar_link' => $calendar_page ? get_permalink($calendar_page->ID) : false
		);

		return $this;
	}

	protected function get_products() {
		$cf_meta = get_post_meta($this->post_id, '_cf_meta_value', true);
		if ( !$cf_meta ) {
			return $this;
		}

		$pattern = '~<!--\s([^\s]+)\s-->~mi';
		// match all products assigned to that appointment
		preg_match_all($pattern, $cf_meta, $products);

		if ( !$products ) {
			return $this;
		}

		foreach ($products[1] as $product_string) {
			$product_info = explode('|', $product_string);

			$product_info_to_add = array();

			foreach ($product_info as $product_info_string) {
				$info = explode('::', $product_info_string);
				$label = $info[0];
				$value = $info[1];

				$product_info_to_add[$label] = $value;
			}

			$this->products[] = (object) $product_info_to_add;
		}

		return $this;
	}

	protected function get_products_data() {
		foreach ($this->products as $product_data) {
			$product_id = intval($product_data->product_id);
			$data_to_add = array();

			$product_obj = wc_get_product($product_id);
			$data_to_add['product'] = $product_obj;

			if ( isset($product_data->variation_id) ) {
				$variation_id = intval($product_data->variation_id);
				if (!empty($product_obj->variations[$variation_id])):
					$variation_data = $product_obj->variations[$variation_id];
					$data_to_add['variation'] = $variation_data;
				else:
					$data_to_add['variation'] = false;
				endif;
			} else {
				$data_to_add['variation'] = false;
			}

			$this->products_extended[] = (object) $data_to_add;
		}

		return $this;
	}

	protected function get_order() {
		$this->order_id = get_post_meta($this->post_id, '_' . BOOKED_WC_PLUGIN_PREFIX . 'appointment_order_id', true);
		return $this;
	}

	protected function get_payment_status() {
		$payment_status = new Booked_WC_Appointment_Payment_Status($this->post_id);

		$this->is_paid = $payment_status->is_paid;
		$this->payment_status = $payment_status->payment_status;
		$this->payment_status_text = $payment_status->payment_status_text;

		return $this;
	}
}
