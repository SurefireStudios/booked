<?php

// http://docs.woothemes.com/wc-apidocs/class-WC_Cart.html
class Booked_WC_Cart {

	// doesn't work on INIT, => use template_redirect
	public static function add_appointment( $app_id=null ) {

		$app_id = intval($app_id);

		$appointment = Booked_WC_Appointment::get($app_id);
		if ( !$appointment->products ) {
			$message = sprintf(__('Appointment with ID %1$d does not have any products assigned to it.', 'booked'), $post_id);
			throw new Exception($message);
		}

		$cart = WC()->cart;
		if ( !method_exists($cart, 'add_to_cart') ) {
			return;
		}

		$cart_appointments = self::get_cart_appointments();

		foreach ($appointment->products as $product) {
			$product_id = intval($product->product_id);

			if ( !isset($product->variation_id) || !intval($product->variation_id) ) {
				$variation_id = false;
			} else {
				$variation_id = intval($product->variation_id);
			}

			$check_key = "{$app_id}::{$product_id}::" . intval($variation_id);
			// check if the product is added to the cart and if it isn't, add it
			// we need that in case a specific appointment has more than one product and one of the is removed from the cart by accident
			if ( in_array($check_key, $cart_appointments['extended']) ) {
				continue;
			}

			$additional_item_data = array(
				BOOKED_WC_PLUGIN_PREFIX . 'appointment_id' => $app_id
			);

			// add calendar name as part of the item data
			if ( !empty($appointment->calendar->calendar_obj) ) {
				$additional_item_data[BOOKED_WC_PLUGIN_PREFIX . 'appointment_cal_name'] = $appointment->calendar->calendar_obj->name;
			}
			// <---

			// add calendar assignee
			if ( !empty($appointment->calendar->calendar_obj) ) {
				$term_meta = get_option( "taxonomy_{$appointment->calendar->calendar_obj->term_id}" );
				$assignee_email = $term_meta['notifications_user_id'];

				if ( $assignee_email && ($usr=get_user_by('email', $assignee_email)) ) {
					$additional_item_data[BOOKED_WC_PLUGIN_PREFIX . 'appointment_assignee_name'] = $usr->display_name;
				}
			}
			// <---

			// add timerange name as part of the item data
			$additional_item_data[BOOKED_WC_PLUGIN_PREFIX . 'appointment_timerange'] = $appointment->timeslot_text;
			// <---

			// add the custom field information as part of the item data
			$i = 0;
			$separator = '--SEP--';
			$meta_key = '_' . BOOKED_WC_PLUGIN_PREFIX . 'cfield_';
			$custom_fields = (array) $appointment->custom_fields;
			foreach ($custom_fields as $field_label => $field_value) {
				$key = $meta_key . strval($i);
				$value = $field_label . ': ' . $separator . $field_value;
				$additional_item_data[$key] = $value;
				$i++;
			}
			// <---

			$quantity = 1;
			$variation_attributes = array();

			// If WPML is installed, let's make sure it adds the correct Product ID to the cart.
			if ( function_exists( 'icl_object_id' ) ):
				$product_id = icl_object_id( $product_id, 'product', true );
			elseif ( function_exists( 'pll_get_post' ) ):
				$product_id = pll_get_post( $product_id, 'product', true );
			endif;

			$cart->add_to_cart(
				$product_id,
				$quantity,
				$variation_id,
				$variation_attributes,
				$additional_item_data
			);

		}

		return true;
	}

	public static function empty_cart( $clear_persistent_cart=true ) {
		// empty current cart session
		$cart = WC()->cart;

		if ( method_exists($cart, 'empty_cart') ) {
			$cart->empty_cart($clear_persistent_cart);
		}
	}

	public static function get_cart_appointments() {

		$cart = WC()->cart;

		if ( method_exists($cart, 'get_cart') ):

			$cart_items = $cart->get_cart();
			$cart_apps = array(
				'extended' => array(), // app_id::product_id::variation_id
				'ids' => array()
			);

			$app_id_key = BOOKED_WC_PLUGIN_PREFIX . 'appointment_id';

			foreach ($cart_items as $cart_item) {
				if ( !isset($cart_item[$app_id_key]) ) {
					continue;
				}

				$app_id = $cart_item[$app_id_key];
				$product_id = intval($cart_item['product_id']);
				$variation_id = intval($cart_item['variation_id']);

				$cart_apps['ids'][] = $app_id;
				$cart_apps['extended'][] = "{$app_id}::{$product_id}::{$variation_id}"; // app_id::product_id::variation_id
			}

			return $cart_apps;

		else:

			return false;

		endif;

	}
}

class Booked_WC_Cart_Hooks {

	public static function woocommerce_checkout_fields( $fields ) {
	    global $woocommerce;
	    $booked_first_name = $woocommerce->session->get( 'booked_first_name' );
	    $booked_last_name = $woocommerce->session->get( 'booked_last_name' );
	    $booked_email = $woocommerce->session->get( 'booked_email' );

	    if(!is_null($booked_first_name)):
	    	$fields['billing']['billing_first_name']['default'] = $booked_first_name;
	    endif;
	    if(!is_null($booked_last_name)):
	    	$fields['billing']['billing_last_name']['default'] = $booked_last_name;
	    endif;
	    if(!is_null($booked_email)):
	    	$fields['billing']['billing_email']['default'] = $booked_email;
	    endif;
	    return $fields;
	}

	public static function woocommerce_cart_item_permalink( $permalink, $cart_item, $cart_item_key ){

		$app_id_key = BOOKED_WC_PLUGIN_PREFIX . 'appointment_id';

		if ( !isset($cart_item[$app_id_key]) ) {
			return $permalink;
		}

		$appt_id = intval($cart_item[$app_id_key]);

		if ( $appt_id ):
			$appointment = Booked_WC_Appointment::get( $appt_id );
			if ( !empty( $appointment ) ):
				return false;
			else:
				return $permalink;
			endif;
		else:
			return $permalink;
		endif;

	}

	// change the product title on the cart page if it is assigned to a appointment
	public static function woocommerce_cart_item_name( $product_title, $cart_item, $cart_item_key ) {

		$app_id_key = BOOKED_WC_PLUGIN_PREFIX . 'appointment_id';

		if ( !isset($cart_item[$app_id_key]) ) {
			return $product_title;
		}

		$appt_id = intval($cart_item[$app_id_key]);

		try {

			if ( isset($cart_item['variation_id']) && $cart_item['variation_id'] ):
				$variation = wc_get_product($cart_item['variation_id']);
				if ( function_exists( 'wc_get_formatted_variation' ) ):
					$variation_text = '<br>' . wc_get_formatted_variation( $variation );
				else:
					$variation_text = '<br>' . $variation->get_formatted_variation_attributes();
				endif;
			else:
				$variation_text = '';
			endif;

			$appointment = Booked_WC_Appointment::get( $appt_id );

			// remove product title link, so the visitors can't acess the product details page
			$product_title = '<b>' . preg_replace('~<a[^>]+>([^<]+)</a>~i', '$1', $product_title) . ' &times; ' . $cart_item['quantity'] . '</b>' . $variation_text;
			$product_title .= '<div class="booked-wc-checkout-section"><small>' . ucwords( $appointment->timeslot_text ) . '</small></div>';

			$product_title .= '<div class="booked-wc-checkout-section">';

			// Get the Date
			$date_format = get_option('date_format');
			$product_title .= '<small><b>' . __('Date', 'booked') . ':</b>&nbsp;' . date_i18n( $date_format, $appointment->timestamp ) . '</small>';

			if ( !empty($appointment->calendar) && !empty($appointment->calendar->calendar_obj) ) {
				// Get the Calendar Name
				$product_title .= '<br><small><b>' . __('Calendar', 'booked') . ':</b>&nbsp;' . $appointment->calendar->calendar_obj->name . '</small>';

				// Check for a Booking Agent
				$term_meta = get_option( "taxonomy_{$appointment->calendar->calendar_obj->term_id}" );
	  			$assignee_email = $term_meta['notifications_user_id'];
	  			if ( $assignee_email && ($usr=get_user_by('email', $assignee_email)) ) {
					$product_title .= '<br><small><b>' . __('Booking Agent', 'booked') . ':</b>&nbsp;' . $usr->display_name . '</small>';
	  			}
	  		}

	  		$custom_fields = (array) $appointment->custom_fields;

			foreach ($custom_fields as $field_label => $field_value) {
				$product_title .= '<br><small><b>' . $field_label . ':</b>&nbsp;' . $field_value . '</small>';
			}

  			$product_title .= '</div>';

		} catch (Exception $e) {

			return $product_title;

		}

		return $product_title;

	}

	public static function woocommerce_checkout_cart_item_quantity( $quantity , $cart_item , $cart_item_key  ) {
    	$app_id_key = BOOKED_WC_PLUGIN_PREFIX . 'appointment_id';
		if ( isset($cart_item[$app_id_key]) ) {
			return false;
		}
		return $quantity;
	}

	public static function woocommerce_cart_item_thumbnail( $thumbnail, $cart_item ) {

		$app_id_key = BOOKED_WC_PLUGIN_PREFIX . 'appointment_id';
		if ( isset($cart_item[$app_id_key]) ) {
			if ( Booked_WC_Settings::get_option('enable_thumbnails') === 'enable' ) {
				return $thumbnail;
			} else {
				return false;
			}
		} else {
			return $thumbnail;
		}

	}

	// removed the missing appointments from the cart
	public static function woocommerce_remove_missing_appointment_products() {

		$cart = WC()->cart;
		if ( !is_object( $cart ) || is_object( $cart ) && !method_exists($cart, 'remove_cart_item') || is_object( $cart ) && !method_exists($cart, 'get_cart') ) {
			return;
		}

		$cart_items = $cart->get_cart();

		$app_id_key = BOOKED_WC_PLUGIN_PREFIX . 'appointment_id';

		foreach ($cart_items as $cart_item_key => $cart_item) {
			if ( !isset($cart_item[$app_id_key]) ) {
				continue;
			}

			$app_id = $cart_item[$app_id_key];

			if ( get_post($app_id) ) {
				continue;
			}

			$cart->remove_cart_item($cart_item_key);
		}
	}
}
