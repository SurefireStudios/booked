<?php

class Booked_WC_Functions {

	private static $calls = array();

	private function __construct() {

	}

	public static function get_products() {
		// check if the products has been already retrieved
		// dont make additional requests
		if (
			!isset(self::$calls['products'])
			|| empty(self::$calls['products']['objects'])
		) {
			$products = get_posts(array(
				'posts_per_page' => 200, // there shouldn't be more than 200 product
				'post_type' => 'product',
				'meta_query' => array(
					array(
						'key' => '_booked_appointment',
						'compare' => '=',
						'value' => 'yes'
					)
				),
				'suppress_filters' => false
			));

			$options = array();
			foreach ($products as $product) {
				$options[$product->ID] = apply_filters('the_title', $product->post_title);
			}

			self::$calls['products']['objects'] = $products;
			self::$calls['products']['options'] = $options;
		}

		return self::$calls['products'];
	}

	public static function get_calendar_page_info( $calendar_id = null ) {
		global $wpdb;
		$pages = false;
		$shortcode = '[booked-calendar';

		if ( $calendar_id ) {
			$calendar_id = intval($calendar_id);
			$search_query = "`post_type` = 'page' AND `post_content` LIKE '%calendar={$calendar_id}%'";
			$search_query .= " OR `post_type` = 'page' AND `post_content` LIKE '%calendar=\"{$calendar_id}\"%'";
			$search_query .= ' OR `post_type` = "page" AND `post_content` LIKE "%calendar=\'{'.$calendar_id.'}\'%"';
			$query = "SELECT * FROM `{$wpdb->posts}` WHERE ".$search_query;
			$pages = $wpdb->get_results($query);
		}

		if ( !$pages ) {
			$shortcode = '[booked-calendar';
			$query = "SELECT * FROM `{$wpdb->posts}` WHERE `post_type` = 'page' AND `post_content` LIKE '{$shortcode}'";
			$pages = $wpdb->get_results($query);
		}

		if (!$pages){
			return;
		}

		// return the first found calendar
		return $pages[0];
	}

	public static function get_login_page_info() {
		global $wpdb;
		$query = "SELECT * FROM `{$wpdb->posts}`
					WHERE `post_type` = 'page'
					AND `post_content` LIKE '%[booked-login]%'";

		$pages = $wpdb->get_results($query);
		if ( !$pages ) {
			return;
		}

		// return the first found calendar
		return $pages[0];
	}

	public static function get_appointments_page_info() {
		global $wpdb;
		$query = "SELECT * FROM `{$wpdb->posts}`
					WHERE `post_type` = 'page'
					AND `post_content` LIKE '%[booked-appointments]%'";

		$pages = $wpdb->get_results($query);
		if ( !$pages ) {
			return;
		}

		// return the first found calendar
		return $pages[0];
	}

	public static function booked_new_appointment_form() {

		if (
			!empty($_POST['action'])
			&& $_POST['action']==='booked_new_appointment_form'
			&& !empty($_POST['source'])
			&& $_POST['source']==='booked_wc_extension'
		) {
			$booking_form = Booked_WC_Fragments::get_path('ajax-loaded/appointment', 'change-date');
			include($booking_form);
			exit;
		}
	}

	public static function get_calendar_id_from_post_request() {
		$calendar_id = (isset($_POST['calendar_id']) ? $_POST['calendar_id'] : false);
		$calendar_id = array($calendar_id);
		$calendar_id = array_map( 'intval', $calendar_id );
		$calendar_id = array_unique( $calendar_id );

		if ( !empty($calendar_id) && is_numeric($calendar_id[0]) ) {
			return $calendar_id[0];
		}

		return false;
	}

	public static function get_custom_fields() {
		$calendar_id = self::get_calendar_id_from_post_request();
		$custom_fields = array();

		if ( $calendar_id ) {
			$custom_fields_option_name = 'booked_custom_fields_' . $calendar_id;
			$custom_fields = json_decode(stripslashes(get_option($custom_fields_option_name)),true);
		}

		if ( !$custom_fields ) {
			$custom_fields = json_decode(stripslashes(get_option('booked_custom_fields')),true);
		}

		return $custom_fields;
	}

	// filtrates the custom field values before creating a new appointment
	public static function booked_custom_field_data( $custom_field_data = array() ) {

		// get custom fields
		$custom_fields = self::get_custom_fields();

		if ( empty($custom_fields) ) {
			return $custom_field_data;
		}

		// handle the custom fields data
		$has_product = false;
		
		foreach($custom_fields as $field):

			$field_parts = explode('---',$field['name']);
			$field_type = $field_parts[0];
			if ( $field_type == 'required' && isset( $field_parts[1] ) && isset( $field['value'] ) && $field['value'] ):
				$required_fields[] = $field_parts[1];
			endif;
		
		endforeach;

		foreach($custom_fields as $key => $field) {
			$field_name = $field['name'];
			$field_parts = explode('---',$field_name);
			$field_type = $field_parts[0];
			$field_marker = '';

			if ( $field_type !== 'paid-service-label' ) {
				continue;
			}

			if ( !isset($_POST[$field_name]) || empty($_POST[$field_name]) ) {
				continue;
			}

			$field_title = $field['value'];

			$product_id = intval($_POST[$field_name]);
			$product = Booked_WC_Product::get($product_id);

			$end_of_string = explode('___',$field_parts[1]);
			$numbers_only = $end_of_string[0];
			$is_required = in_array( $numbers_only, $required_fields );

			// set product title
			$custom_field_data[$key]['value'] = esc_html($product->title);

			$field_marker .= 'product_id::' . $product_id;

			$option_name = str_replace($field_type, 'paid-service-variation', $field_name);
			if ( isset($_POST[$option_name]) && !empty($_POST[$option_name]) ) {
				$variation_id = intval($_POST[$option_name]);
				if ( isset($product->variations[$variation_id]) ) {
					$variation_details = $product->variations[$variation_id];
					$variation_title = $variation_details['variation_title'];

					// add variation value
					$custom_field_data[$key]['value'] .= '<br />[ ' . esc_html($variation_title) . ' ]';
					$field_marker .= '|variation_id::' . $variation_id;

				}
			}

			// add a marker containing the product ID
			// simple-product -> product_id::ID
			// variable-product -> product_id::ID|variation_id::ID
			$custom_field_data[$key]['value'] .= "<!-- {$field_marker} -->";

			$has_product = true;
		}

		if ( $has_product ) {

			// check for a calendar assignee
			$calendar_id = self::get_calendar_id_from_post_request();
			if ( $calendar_id ) {
				$term_meta = get_option( "taxonomy_{$calendar_id}" );
				$assignee_email = $term_meta['notifications_user_id'];

				if ( $assignee_email && ($usr=get_user_by('email', $assignee_email)) ) {
					$custom_field_data['booking-agent'] = array(
						'label' => esc_attr__('Booking Agent', 'booked'),
						'value' => $usr->display_name
					);
				}
			}

			add_filter('booked_new_appointment_args', array('Booked_WC_Functions', 'booked_new_appointment_args_on_create'), 10, 1);
		}

		return $custom_field_data;
	}

	// filtrates the custom field values before creating a new appointment
	public static function booked_prepare_sending_reminder( $send = true, $appt_id = false ) {

		if ( empty( $appt_id ) ):
			return false;
		endif;

		if ( is_array( $appt_id ) ):
			return false;
		else:
			$custom_field_val = get_post_meta( $appt_id, '_cf_meta_value', true );
			$is_wc_order = strpos( $custom_field_val, '<!-- product_id' );
			if ( $is_wc_order ):
				$order = new Booked_WC_Appointment_Payment_Status( $appt_id );
				if ( !empty($order) && isset($order->is_paid) && !$order->is_paid ):
					return false;
				else:
					return true;
				endif;
			else:
				return true;
			endif;
		endif;

		return true;

	}

	// filtrates the appointment data before creating it
	// this hook should be called only in Booked_WC_Functions::booked_custom_field_data() only if product option is available
	public static function booked_new_appointment_args_on_create( $post_params=array() ) {

		// just in case, remove the filter
		remove_filter('booked_new_appointment_args', array('Booked_WC_Functions', 'booked_new_appointment_args_on_create'), 10, 1);

		// set post status to awaiting payment
		$post_params['post_status'] = BOOKED_WC_PLUGIN_PREFIX . 'awaiting';

		return $post_params;
	}

	// filtered the appointment information before populating to the calendar
	public static function booked_appointments_array( $appointments_array ) {
		return $appointments_array;
	}

	public static function booked_store_appointment_creation_date( $appointment_id=null ) {

		if ( !$appointment_id ) {
			return;
		}

		$current_time = current_time('timestamp');

		update_post_meta($appointment_id, '_' . BOOKED_WC_PLUGIN_PREFIX . 'time_created', $current_time);
		update_post_meta($appointment_id, '_' . BOOKED_WC_PLUGIN_PREFIX . 'date_created', date('Y-m-d H:i:s', $current_time));

	}

	public static function booked_get_custom_fields_information() {
		$custom_fields = self::get_custom_fields();
		$submission_values = array();
		$previous_field = false;
		$is_product = false;
		
		foreach($custom_fields as $field):

			$field_parts = explode('---',$field['name']);
			$field_type = $field_parts[0];
			if ( $field_type == 'required' && isset( $field_parts[1] ) && isset( $field['value'] ) && $field['value'] ):
				$required_fields[] = $field_parts[1];
			endif;
		
		endforeach;

		foreach($custom_fields as $field) {
			$field_name = $field['name'];
			$field_title = $field['value'];

			$field_parts = explode('---',$field_name);
			$field_type = $field_parts[0];

			switch ($field_type) {
				case 'paid-service-label':
					$is_product	= true;
					$current_group_name = $field_title;
					break;
				case 'checkboxes-label':
				case 'radio-buttons-label':
					$is_product	= false;
					$current_group_name = $field_title;
					break;
				case 'single-checkbox':
				case 'single-radio-button':
					$is_product	= false;
					// Don't change the group name yet
					break;
				default:
					$is_product	= false;
					$current_group_name = $field_title;
					break;
			}

			if ( $field_name===$previous_field ) {
				continue;
			}

			$previous_field = $field_name;

			if ( !isset($_POST[$field_name]) || empty($_POST[$field_name]) ) {
				continue;
			}

			// set regular field data and conitnue
			if ( !$is_product ) {

				$field_value = $_POST[$field_name];
				if (is_array($field_value)){
					$field_value = implode(', ',$field_value);
				}
				$submission_values[$current_group_name] = $field_value;

				continue;
			}

			// if the field is a product

			$product_id = intval($_POST[$field_name]);
			$product = Booked_WC_Product::get($product_id);

			$end_of_string = explode('___',$field_parts[1]);
			$numbers_only = $end_of_string[0];
			$is_required = in_array( $numbers_only, $required_fields );

			// set product title
			$submission_values[$current_group_name] = esc_html($product->title);

			$option_name = str_replace($field_type, 'paid-service-variation', $field_name);
			if ( isset($_POST[$option_name]) && !empty($_POST[$option_name]) ) {
				$variation_id = intval($_POST[$option_name]);
				if ( isset($product->variations[$variation_id]) ) {
					$variation_details = $product->variations[$variation_id];
					$variation_title = $variation_details['variation_title'];

					// add variation value
					$submission_values[$current_group_name] .= '<br />[ ' . esc_html($variation_title) . ' ]';
				}
			}
		}

		return $submission_values;
	}

	public static function booked_store_custom_fields_information( $appointment_id = null ) {
		if ( !$appointment_id ) {
			return;
		}

		$custom_fields = self::booked_get_custom_fields_information();
		$i = 0;
		$separator = '--SEP--';
		$meta_key = '_' . BOOKED_WC_PLUGIN_PREFIX . 'cfield_';
		foreach ($custom_fields as $field_label => $field_value) {
			$this_meta_key = $meta_key . strval($i);
			$value_to_store = $field_label . $separator . $field_value;

			// save the value
			update_post_meta($appointment_id, $this_meta_key, $value_to_store);

			$i++;
		}
	}

	public static function booked_new_appointment_created( $appointment_id = null ) {

		if ( is_admin() && isset($_POST['booked_form_type']) && $_POST['booked_form_type'] == 'admin' ):
			return;
		endif;

		$appointment_id = intval($appointment_id);

		try {
			$appointment = Booked_WC_Appointment::get($appointment_id);
		} catch (Exception $e) {
			return;
		}

		// check if the appointment has assigned products to it
		if ( !$appointment->products ) {
			return;
		}

		self::booked_store_custom_fields_information($appointment_id);
		$added_to_cart = Booked_WC_Cart::add_appointment($appointment_id);
		if ( !$added_to_cart ) {
			return;
		}

	}

	// add edit button to the front front end appointments listing
	public static function booked_shortcode_appointments_buttons( $appointment_id=null ) {
		// add change date and pay buttons
		$edit_button = Booked_WC_Fragments::get_path('appointment', 'buttons');
		include($edit_button);
	}

	public static function booked_shortcode_appointments_additional_information( $appointment_id=null ) {
		$additional_info = Booked_WC_Fragments::get_path('appointment', 'additional-info');
		include($additional_info);
	}

	// Don't show cancel button on appointment with products
	public static function booked_shortcode_appointments_allow_cancel($allow_cancel, $app_id) {
		$appointment = Booked_WC_Appointment::get(intval($app_id));
		return !$appointment->products;
	}

	// Add "booked_wc_awaiting" to the "Pending" status types
	public static function booked_admin_pending_post_status($statuses) {
		$statuses = array('draft','booked_wc_awaiting');
		return $statuses;
	}

	// remove delete button if order attached
	public static function booked_fea_shortcode_appointments_buttons($button_html, $appt_id) {
		$appointment = Booked_WC_Appointment::get(intval($appt_id));
		$status = get_post_status(intval($appt_id));
		if ($appointment->order_id):
			$status_class = $status !== 'publish' && $status !== 'future' ? 'pending' : 'approved';
			$button_html = '<div class="booked-fea-buttons">';
				$button_html .= ($status_class == 'pending' ? '<button data-appt-id="'.$appt_id.'" class="approve button button-primary">'.__('Approve','booked').'</button>' : '');
			$button_html .=	'</div>';
			return $button_html;
		else:
			return $button_html;
		endif;
	}

	public static function booked_button_book_appointment( $label ) {

		if (
			empty($_POST['app_id'])
			|| empty($_POST['app_action'])
			|| $_POST['app_action']!=='edit'
			|| empty($_POST['source'])
			|| $_POST['source']!=='booked_wc_extension'
		) {
			return $label;
		}


		$appointment = Booked_WC_Appointment::get(intval($_POST['app_id']));
		$current_time = current_time('timestamp');

		// check if the date has been passed
		if ( $current_time > $appointment->timestamp ) {
			return $label;
		}

		return '<span class="button-text" >' . __('Choose New Date', 'booked') . '</span></button></span>';
	}

	public static function booked_before_creating_appointment() {

		global $woocommerce;

		$customer_type = isset($_POST['customer_type']) ? $_POST['customer_type'] : '';
		if ($customer_type == 'guest'):
			$first_name = esc_attr($_POST['guest_name']);
			$last_name = isset($_POST['guest_surname']) && $_POST['guest_surname'] ? esc_attr($_POST['guest_surname']) : false;
			$email = isset($_POST['guest_email']) ? esc_attr($_POST['guest_email']) : '';
		elseif ($customer_type == 'new'):
			$first_name = esc_attr($_POST['booked_appt_name']);
			$last_name = ( isset($_POST['booked_appt_surname']) && $_POST['booked_appt_surname'] ? esc_attr($_POST['booked_appt_surname']) : false );
			$email = $_POST['booked_appt_email'];
		elseif ($customer_type == 'current' && is_user_logged_in() ):
			$_this_user = wp_get_current_user();
			$first_name = $_this_user->user_firstname;
			$last_name = $_this_user->user_lastname;
			$email = $_this_user->user_email;
		endif;

		if ( isset( $email ) ):
			$woocommerce->session->set( 'booked_first_name', $first_name );
			$woocommerce->session->set( 'booked_last_name', $last_name );
			$woocommerce->session->set( 'booked_email', $email );
		endif;

		if ( is_admin() && isset($_POST['booked_form_type']) && $_POST['booked_form_type'] == 'admin' ):
			return;
		endif;

		$action = (isset($_POST['action']) &&!empty($_POST['action'])) ? $_POST['action'] : false;
		$app_id = (isset($_POST['app_id']) &&!empty($_POST['app_id'])) ? $_POST['app_id'] : false;
		$app_action = (isset($_POST['app_action']) &&!empty($_POST['app_action'])) ? $_POST['app_action'] : false;
		$source = (isset($_POST['source']) &&!empty($_POST['source'])) ? $_POST['source'] : false;

		if (
			(!$action || $action !== 'booked_add_appt')
			|| (!$app_id || !intval($app_id))
			|| (!$app_action || $app_action!=='edit')
			|| (!$source || $source!=='booked_wc_extension')
		) {
			return;
		}

		$appointment = Booked_WC_Appointment::get(intval($app_id));
		$current_time = current_time('timestamp');

		// check if the date has been passed
		if ( $current_time > $appointment->timestamp ) {
			return;
		}

		// remove cart functionality
		remove_action('booked_new_appointment_created', array('Booked_WC_Functions', 'booked_new_appointment_created'), 15, 1);

		// turn the wp_insert_post to act as wp_update_post
		add_filter('booked_new_appointment_args', array('Booked_WC_Functions', 'booked_new_appointment_args_on_date_change'), 10, 1);

		// dont allow to update the metas and the calendar term
		add_filter('booked_update_cf_meta_value', array('Booked_WC_Functions', 'return_false'), 10);
		add_filter('booked_update_appointment_calendar', array('Booked_WC_Functions', 'return_false'), 10);

	}

	public static function booked_wc_mailer_actions( $mailer_actions ){
		$send_upon_completion = self::booked_disable_confirmation_emails();
		if ( $send_upon_completion ):
			$mailer_actions[] = 'booked_wc_confirmation_email';
			$mailer_actions[] = 'booked_wc_admin_confirmation_email';
			return $mailer_actions;
		endif;
		return $mailer_actions;
	}

	public static function booked_disable_confirmation_emails(){
		if ( Booked_WC_Settings::get_option('email_confirmations') === 'after_complete' || Booked_WC_Settings::get_option('email_confirmations') === false ):
			return apply_filters( 'booked_disable_confirmation_emails', true );
		else:
			return apply_filters( 'booked_disable_confirmation_emails', false );
		endif;
	}

	public static function remove_default_emails() {

		$booked_wc_product = false;

		if (isset($_POST)):
			foreach($_POST as $var => $val):
				$var_parts = explode('---',$var);
				if (isset($var_parts[0]) && $var_parts[0] == 'paid-service-label'):
					$booked_wc_product = true;
					break;
				endif;
			endforeach;

			$booked_disable_confirmation_emails = self::booked_disable_confirmation_emails();
			if ( $booked_wc_product && $booked_disable_confirmation_emails ):
				remove_action( 'booked_confirmation_email', 'booked_mailer', 10 );
				remove_action( 'booked_admin_confirmation_email', 'booked_mailer', 10 );
			endif;

		else:
			return;
		endif;

		return;

	}

	// see wp_update_post()
	public static function booked_new_appointment_args_on_date_change( $post_args ) {

		$appointment_id = $_POST['app_id'];
		$appointment_obj = get_post($appointment_id, ARRAY_A);

		if ( !$appointment_obj ) {
			return 0;
		}

		$default_post_status = get_option('booked_new_appointment_default','draft');
		$post_args['ID'] = $_POST['app_id'];

		// Escape data pulled from DB.
		$appointment_obj = wp_slash($appointment_obj);

		// Passed post category list overwrites existing category list if not empty.
		$post_cats = $appointment_obj['post_category'];
		$post_args = array_merge($appointment_obj, $post_args);
		$post_args['post_category'] = $post_cats;

		// Drafts shouldn't be assigned a date unless explicitly done so by the user.
		$post_args['post_date'] = current_time('mysql');
		$post_args['post_date_gmt'] = '';

		// keep the awaiting payment status if the appointment is awaiting payment
		$app_detailed_obj = Booked_WC_Appointment::get(intval($appointment_id));
		$awaiting_payment_status = BOOKED_WC_PLUGIN_PREFIX . 'awaiting';
		if ($app_detailed_obj->products && !$app_detailed_obj->is_paid) {
			$post_args['post_status'] = $awaiting_payment_status;
		} else {
			$post_args['post_status'] = $default_post_status;
		}

		return $post_args;

	}

	public static function return_false() {
		return false;
	}

	public static function booked_admin_calendar_buttons_before($calendar_id, $appt_id, $status) {
		$appt_id = intval($appt_id);
		$appointment = Booked_WC_Appointment::get($appt_id);

		if ( !$appointment->products ) {
			return;
		}

		add_filter('booked_admin_show_calendar_buttons', array('Booked_WC_Functions', 'return_false'), 10);

		$edit_button = Booked_WC_Fragments::get_path('admin-calendar', 'app-buttons');
		include($edit_button);
	}

	public static function booked_admin_calendar_buttons_after($calendar_id, $appt_id, $status) {
		remove_filter('booked_admin_show_calendar_buttons', array('Booked_WC_Functions', 'return_false'), 10);
	}

}
