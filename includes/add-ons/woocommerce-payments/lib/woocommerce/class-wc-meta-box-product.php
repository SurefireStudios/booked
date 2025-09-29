<?php

class Booked_WC_Meta_Box_Product_Data {

	private function __construct() {
		$this->product_filters();
		$this->product_actions();
	}

	public static function setup() {
		return new self();
	}

	protected function product_filters() {
		// add additional product type
		add_filter('product_type_options', array($this, 'product_type_options'), 10, 1);
	}

	protected function product_actions() {
		// save the new product type meta value
		// 'woocommerce_process_product_meta_' . $product_type
		add_action('woocommerce_process_product_meta_variable', array($this, 'woocommerce_process_product_meta'), 10, 1);
		add_action('woocommerce_process_product_meta_simple', array($this, 'woocommerce_process_product_meta'), 10, 1);
	}

	# ------------------
	# Filters
	# ------------------

	public function product_type_options( $options ) {
		$options['booked_appointment'] = array(
			'id' => '_booked_appointment',
			'wrapper_class' => 'show_if_simple show_if_variable',
			'label' => __('Booked Product', 'booked'),
			'description' => __('Booked Products are hidden from view and only used in booking services.', 'booked'),
			'default' => 'no'
		);

		return $options;
	}

	# ------------------
	# Actions
	# ------------------

	public function woocommerce_process_product_meta( $post_id ) {

		// Get types
		$is_booked_appointment = isset($_POST['_booked_appointment']) ? 'yes' : 'no';

		// Product type + Booked Appointment Service
		update_post_meta($post_id, '_booked_appointment', $is_booked_appointment);

	}
}