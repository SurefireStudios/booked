<?php

class Booked_WC_Variation {

	private static $variations = array();

	private function __construct( $variation_id ) {

	}

	# ------------------
	# Filter To Modify the aailable variation output
	# woocommerce_available_variation
	# in class-wc-product.php
	# apply_filters('woocommerce_available_variation', $variation_data_to_return, $product_class_obj, $variation_class_obj);
	# ------------------

	public static function woocommerce_available_variation($variation_data_to_return, $product_class_obj, $variation_class_obj) {

		$attributes = $variation_data_to_return['attributes'];
		$price = strip_tags($variation_data_to_return['price_html']);

		$variation_title = $price ? $price . ' - ' : '';
		$i = 0;
		$separator = ', ';
		foreach ($attributes as $taxonomy_name => $term_slug) {
			if ( $i > 0 ) {
				$variation_title .= $separator;
			}

			$variation_title .= wc_attribute_label($term_slug);

			$i++;
		}

		$variation_data_to_return['variation_title'] = $variation_title;

		return $variation_data_to_return;
	}
	
}