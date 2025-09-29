<?php

class Booked_WC_Ajax {

	private function __construct() {

		$functions = array(
			'wp_ajax_' => array(
				// 'action' => 'function_name'
				'load_variations' => 'load_product_variations',
				'add_to_cart' => 'add_appointment_to_cart',
				'mark_paid' => 'mark_appointment_as_paid'
			),
			'wp_ajax_nopriv_' => array(
				// 'action' => 'function_name
				'load_variations' => 'load_product_variations',
				'add_to_cart' => 'add_appointment_to_cart'
			)
		);

		foreach ($functions as $ajax_type => $requests) {
			foreach ($requests as $action_value => $function_name) {
				$filter_name = $ajax_type . BOOKED_WC_PLUGIN_PREFIX . $action_value;
				add_action($filter_name, array($this, $function_name));
			}
		}
	}

	protected function verify_domain() {
		$domain = $_SERVER['SERVER_NAME'];

		return strstr(home_url('/'), $domain);
	}

	public static function setup() {
		return new self();
	}

	public function mark_appointment_as_paid() {

		if (empty($_POST['appt_id']) || !current_user_can('manage_booked_options')){
			return;
		}

		$appt_id = (int) $_POST['appt_id'];
		$appointment = Booked_WC_Appointment::get($appt_id);

		if (!$appointment->order_id){
			$_oid = false;
			update_post_meta($appt_id, '_booked_wc_appointment_order_id', 'manual');
		} else {
			$_oid = $appointment->order_id;
			$order = new WC_Order($appointment->order_id);
			$order->update_status('completed');
		}

		echo ( $_oid ? get_edit_post_link( $_oid ) : 'no_order' );
		exit;

	}

	public function load_product_variations() {

		if (
			empty($_POST['product_id'])
			|| !get_post($_POST['product_id'])
		) {
			return;
		}

		$product_id = intval($_POST['product_id']);
		$calendar_id = isset($_POST['calendar_id']) ? intval($_POST['calendar_id']) : 0;
		$field_name = $_POST['field_name'];
		$is_required = false;

		if ( $field_name ) {
			$field_parts = explode('---',$field_name);
			$field_type = $field_parts[0];
			$end_of_string = explode('___',$field_parts[1]);
			$numbers_only = $end_of_string[0];
			$is_required = (isset($end_of_string[1]) ? true : false);

			$field_name = 'paid-service-variation---' . $numbers_only;

			if ( $is_required ) {
				$field_name .= '___' . $end_of_string[1];
			}
		}

		try {
			$product = Booked_WC_Product::get($product_id);
			$fragment_file = Booked_WC_Fragments::get_path('ajax-loaded/product','variations');
			include($fragment_file);
		} catch (Exception $e) {
			$message = __('An error has occur.', 'booked');
			throw new Exception($message);
		}

		exit;
	}

	public function add_appointment_to_cart() {
		$response = new Booked_WC_Response();

		$app_id = (!empty($_POST['app_id']) && $_POST['app_id']) ? intval($_POST['app_id']) : false;
		if ( !$app_id ) {
			$response->add_message(__('Appointment ID is not defined.', 'booked'));
			$response->create();
		}

		try {
			$appointment = Booked_WC_Appointment::get($app_id);
			if ( !$appointment->products ) {
				$response->add_message($e->getMessage());
				$response->create();
			}

			// add the appointment to cart
			Booked_WC_Cart::add_appointment($app_id);
		} catch (Exception $e) {
			$response->add_message($e->getMessage());
			$response->create();
		}

		$response->add_message(__('Appointment has been added to the cart', 'booked'));
		$response->set_status(true);
		$response->create();
	}
}
