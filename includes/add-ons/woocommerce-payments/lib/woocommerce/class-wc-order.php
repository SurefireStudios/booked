<?php

class Booked_WC_Order {

	private static $orders = array();

	public $order;
	public $order_id;
	public $appointments = array();
	public $products;
	public $items = array();

	private function __construct( $order_id ) {
		$this->order_id = $order_id;
		$this->get_data();
		$this->get_items();
		$this->get_appointments();
		$this->get_status_text();
	}

	public static function get( $order_id=null ) {
		if ( !is_integer($order_id) ) {
			$message = sprintf( __('%s integer expected when %s given.', 'booked'), 'Booked_WC_Order::get($order_id)', gettype($order_id) );
			throw new Exception($message);
		} else if ( $order_id===0 ) {
			self::$orders[$order_id] = false;
		} else if ( !isset(self::$orders[$order_id]) ) {
			self::$orders[$order_id] = new self($order_id);
		}

		return self::$orders[$order_id];
	}

	protected function get_data() {
		$order_id = absint($this->order_id);
		$this->order = new WC_Order($order_id);
		return $this;
	}

	protected function get_status_text() {
		$status = $this->order->get_status();
		$statuses = wc_get_order_statuses();

		$this->order->post_status_text = isset($statuses[$status]) ? $statuses[$status] : $status;

		return $this;
	}

	protected function get_items() {
		$this->items = $this->order->get_items();
		return $this;
	}

	protected function get_appointments() {
		$this->appointments = get_post_meta($this->order_id, '_' . BOOKED_WC_PLUGIN_PREFIX . 'order_appointments', true);
		return $this;
	}
}

class Booked_WC_Order_Hooks {

	// woocommerce_order_status_refunded
	// woocommerce_order_status_cancelled
	// delete appointments on refunded or cancelled
	public static function woocommerce_order_remove_appointment( $order_id ) {

		$order_id = (int) $order_id;

		$this_post = get_post($order_id);
		if (!$this_post || $this_post->post_type!=='shop_order') {
			return;
		}

		$order = Booked_WC_Order::get($order_id);

		$appointments = $order->appointments;
		if ( !$appointments ) {
			return;
		}

		$deleted = array();
		foreach ($appointments as $app_id) {
			if (!in_array($app_id, $deleted) && !get_post($app_id)) {
				return;
			}

			$deleted[] = $app_id;

			try {
				do_action('booked_appointment_cancelled',$app_id);
				wp_delete_post($app_id, true);
			} catch (Exception $e) {
				//
			}
		}
	}

	public static function woocommerce_order_complete( $order_id ) {

		$order_id = (int) $order_id;

		$this_post = get_post($order_id);
		if (!$this_post || $this_post->post_type!=='shop_order') {
			return;
		}

		$order = Booked_WC_Order::get($order_id);

		$appointments = $order->appointments;
		if ( !$appointments ) {
			return;
		}

		$completed = array();
		foreach ($appointments as $appt_id) {

			if (!in_array($appt_id, $completed) && !get_post($appt_id)) {
				return;
			}

			$completed[] = $appt_id;

			$send_upon_completion = Booked_WC_Functions::booked_disable_confirmation_emails();
			if ( $send_upon_completion ):

				// Add Booked WC confirmation email actions
				add_action( 'booked_wc_confirmation_email', 'booked_mailer', 10, 3 );
				add_action( 'booked_wc_admin_confirmation_email', 'booked_mailer', 10, 3 );

				// Send a confirmation email to the User?
				$email_content = get_option('booked_appt_confirmation_email_content');
				$email_subject = get_option('booked_appt_confirmation_email_subject');

				$token_replacements = booked_get_appointment_tokens( $appt_id );

				if ($email_content && $email_subject):

					$email_content = booked_token_replacement( $email_content,$token_replacements );
					$email_subject = booked_token_replacement( $email_subject,$token_replacements );

					do_action( 'booked_wc_confirmation_email', $token_replacements['email'], $email_subject, $email_content );

				endif;

				// Send an email to the Admin?
				$email_content = get_option('booked_admin_appointment_email_content');
				$email_subject = get_option('booked_admin_appointment_email_subject');
				if ($email_content && $email_subject):

	            	$cals = wp_get_object_terms( $appt_id, 'booked_custom_calendars' );
	            	$calendar_id = $cals[0]->term_id;

					$admin_email = booked_which_admin_to_send_email($calendar_id);
					$email_content = booked_token_replacement( $email_content,$token_replacements );
					$email_subject = booked_token_replacement( $email_subject,$token_replacements );

					do_action( 'booked_wc_admin_confirmation_email', $admin_email, $email_subject, $email_content );

				endif;

				// Remove Booked WC confirmation email actions
				remove_action( 'booked_wc_confirmation_email', 'booked_mailer', 10 );
				remove_action( 'booked_wc_admin_confirmation_email', 'booked_mailer', 10 );

			endif;

		}

	}

	// validate cart and appointment products
	// this is needed in case a specific appointment has two or more products and the user removes any of them in order to reduce the price
	public static function woocommerce_validate_order_items( $order_id ) {
		$cart_appointments = Booked_WC_Cart::get_cart_appointments();

		$appointment_ids = array();

		foreach ($cart_appointments['ids'] as $app_id) {
			$app_id = intval($app_id);
			if ( $app_id<=0 ) {
				continue;
			}
			$appointment = Booked_WC_Appointment::get($app_id);

			if ( !$appointment->products ) {
				continue;
			}

			foreach ($appointment->products as $product) {
				$product_id = apply_filters( 'wpml_object_id', $product->product_id, 'product', false);
				$variation_id = isset($product->variation_id) ? $product->variation_id : 0;

				$check_key = "{$app_id}::{$product_id}::{$variation_id}";

				if ( !in_array($check_key, $cart_appointments['extended']) ) {
					$message = sprintf( __('Appointment "%s" and cart products do not match. Please make sure that all appointment products are available in the cart.', 'booked'), $appointment->timeslot_text );
					throw new Exception( $message );
				}
			}
		}

		// if the script above passes, link the appointments and their order
		foreach ($cart_appointments['ids'] as $app_id) {
			// link the appointment with the order
			update_post_meta($app_id, '_' . BOOKED_WC_PLUGIN_PREFIX . 'appointment_order_id', $order_id);
		}

		if ( $cart_appointments['ids'] ) {
			// link the order with all appointments appointment
			update_post_meta($order_id, '_' . BOOKED_WC_PLUGIN_PREFIX . 'order_appointments', $cart_appointments['ids']);
		}
	}

	// assign order items to their appointments in case the plugin must extend even more
	public static function woocommerce_add_order_item_meta($item_id, $values, $cart_item_key, $unique=false) {

		$prefix = BOOKED_WC_PLUGIN_PREFIX;

		$item_metas = array(
			'appointment_id',
			'appointment_cal_name',
			'appointment_assignee_name',
			// 'appointment_timerange',
		);

		// populating main metas
		foreach ($item_metas as $key) {
			$meta_key = $prefix . $key;

			if ( !isset($values[$meta_key]) ) {
				continue;
			}

			$value = $values[$meta_key];
			if ( !$value ) {
				return;
			}

			wc_add_order_item_meta($item_id, $meta_key, $value, $unique);
		}

		if ( isset( $values[ $prefix . 'appointment_id' ] ) ){

			$appt_id = $values[ $prefix . 'appointment_id' ];
			$appointment = Booked_WC_Appointment::get( $appt_id );

			$custom_fields = (array) $appointment->custom_fields;
			$i = 0;

			foreach ($custom_fields as $field_label => $field_value){
				$meta_key = '_' . $prefix . 'cfield_' . $i;
				$value = str_replace( ':', '', $field_label ) . '--SEP--' . $field_value;
				wc_add_order_item_meta( $item_id, $meta_key, $value, $unique);
				$i++;
			}

		}

	}

	public static function woocommerce_hidden_order_itemmeta( $hidden_meta ) {
		$hidden_meta[] = BOOKED_WC_PLUGIN_PREFIX . 'appointment_timerange';

		return $hidden_meta;
	}

	public static function woocommerce_order_items_meta_display($output, $order_item_meta_obj) {
		// preg_match_all('~<dt.+class="([^"]+)".*Form Field:</dt>~im', $output, $matches);

		// wrap labels in strong
		$output = preg_replace('~(cfield_\d+.+<p>)([^:]+:)(.+)(</p>)~im', '$1<small><strong>$2</strong>$3</small>$4', $output);

		// replace the form field text
		return preg_replace('~(<dt.+class="[^"]+".*)Form Field:(</dt>)~im', '$1$2', $output);
	}

}
