<?php

if(!class_exists('Booked_AJAX')) {
	class Booked_AJAX {

		public function __construct() {

			// ------------ Guests & Logged-in Users ------------ //

				// Actions

			add_action('wp_ajax_booked_ajax_login', array(&$this,'booked_ajax_login'));
			add_action('wp_ajax_nopriv_booked_ajax_login', array(&$this,'booked_ajax_login'));

			add_action('wp_ajax_booked_ajax_forgot', array(&$this,'booked_ajax_forgot'));
			add_action('wp_ajax_nopriv_booked_ajax_forgot', array(&$this,'booked_ajax_forgot'));

			add_action('wp_ajax_booked_add_appt', array(&$this,'booked_add_appt'));
			add_action('wp_ajax_nopriv_booked_add_appt', array(&$this,'booked_add_appt'));

				// Loaders

			add_action('wp_ajax_booked_calendar_month', array(&$this,'booked_calendar_month'));
			add_action('wp_ajax_nopriv_booked_calendar_month', array(&$this,'booked_calendar_month'));

			add_action('wp_ajax_booked_calendar_date', array(&$this,'booked_calendar_date'));
			add_action('wp_ajax_nopriv_booked_calendar_date', array(&$this,'booked_calendar_date'));

			add_action('wp_ajax_booked_appointment_list_date', array(&$this,'booked_appointment_list_date'));
			add_action('wp_ajax_nopriv_booked_appointment_list_date', array(&$this,'booked_appointment_list_date'));

			add_action('wp_ajax_booked_new_appointment_form', array(&$this,'booked_new_appointment_form'));
			add_action('wp_ajax_nopriv_booked_new_appointment_form', array(&$this,'booked_new_appointment_form'));


			// ------------ Logged-in Users Only ------------ //

				// Actions

			add_action('wp_ajax_booked_cancel_appt', array(&$this,'booked_cancel_appt'));

		}
		
		public static function nonce_check( $nonce ){
			if ( !isset($_POST['nonce']) || !wp_verify_nonce( $_POST['nonce'], $nonce ) ){
				wp_die( 'Security check failed. Please refresh the page and try again.', 'Security Error', array( 'response' => 403 ) );
			}
		}


		// ------------ LOADERS ------------ //

		// Calendar Month
		public function booked_calendar_month(){
			
			Booked_AJAX::nonce_check( 'ajax-nonce' );

			booked_wpml_ajax();

			if (isset($_POST['gotoMonth'])):

				$calendar_id = (isset($_POST['calendar_id']) ? $_POST['calendar_id'] : false);
				$force_default = (isset($_POST['force_default']) ? $_POST['force_default'] : false);
				$timestamp = ($_POST['gotoMonth'] != 'false' ? strtotime($_POST['gotoMonth']) : current_time('timestamp'));

				$year = date_i18n('Y',$timestamp);
				$month = date_i18n('m',$timestamp);

				booked_fe_calendar($year,$month,$calendar_id,$force_default);

			endif;
			wp_die();

		}

		// Calendar Date
		public function booked_calendar_date(){
			
			Booked_AJAX::nonce_check( 'ajax-nonce' );

			booked_wpml_ajax();

			if (isset($_POST['date'])):

				$calendar_id = (isset($_POST['calendar_id']) ? $_POST['calendar_id'] : false);
				booked_fe_calendar_date_content($_POST['date'],$calendar_id);

			endif;
			wp_die();

		}

		// Appointment List Date
		public function booked_appointment_list_date(){
			
			Booked_AJAX::nonce_check( 'ajax-nonce' );

			booked_wpml_ajax();

			if (isset($_POST['date'])):

				$date = date_i18n('Ymd',strtotime($_POST['date']));
				$calendar_id = (isset($_POST['calendar_id']) ? $_POST['calendar_id'] : false);
				$force_default = (isset($_POST['force_default']) ? $_POST['force_default'] : false);

				booked_fe_appointment_list_content($date,$calendar_id,$force_default);

			endif;
			wp_die();

		}

		// New Appointment Form
		public function booked_new_appointment_form(){
			
			Booked_AJAX::nonce_check( 'ajax-nonce' );

			booked_wpml_ajax();

			if ( apply_filters( 'booked_show_new_appointment_form', true ) ):

				include(BOOKED_AJAX_INCLUDES_DIR . 'front/appointment-form.php');

			endif;

			wp_die();
		}


		// ------------ ACTIONS ------------ //

		public function booked_ajax_login(){
			
			Booked_AJAX::nonce_check( 'ajax-nonce' );

			booked_wpml_ajax();

			if (isset($_POST['security']) && isset($_POST['username']) && isset($_POST['password'])):

				$nonce_check = wp_verify_nonce( $_POST['security'], 'ajax_login_nonce' );

				if ($nonce_check){

					if (is_email($_POST['username'])) {
				        $user = get_user_by('email', $_POST['username']);
				    } else {
						$user = get_user_by('login', $_POST['username']);
				    }

				    $creds = array();

				    if ($user && wp_check_password( $_POST['password'], $user->data->user_pass, $user->ID)) {
				        $creds = array('user_login' => $user->data->user_login, 'user_password' => $_POST['password']);
				        $creds['remember'] = true;
				    }

					$user = wp_signon( $creds, false );

					if ( !is_wp_error($user) ):
						echo 'success';
					endif;

				}

			endif;

			wp_die();

		}

		public function booked_ajax_forgot(){
			
			Booked_AJAX::nonce_check( 'ajax-nonce' );

			booked_wpml_ajax();

			global $wpdb, $wp_hasher;

			if (isset($_POST['security']) && isset($_POST['username'])):

				$nonce_check = wp_verify_nonce( $_POST['security'], 'ajax_forgot_nonce' );

				if ($nonce_check){

					$password_reset = booked_reset_password( $_POST['username'] );

					if ( $password_reset ):
						echo 'success';
					endif;

				}

			endif;

			wp_die();

		}

		public function booked_add_appt(){
			
			Booked_AJAX::nonce_check( 'ajax-nonce' );

			booked_wpml_ajax();

			$can_add_appt = apply_filters(
				'booked_can_add_appt',
				isset($_POST['date']) && isset($_POST['timestamp']) && isset($_POST['timeslot']) && isset($_POST['customer_type'])
			);

			if ( $can_add_appt ):

				include(BOOKED_AJAX_INCLUDES_DIR . 'front/book-appointment.php');

			endif;

			wp_die();

		}

		public function booked_cancel_appt(){
			
			Booked_AJAX::nonce_check( 'ajax-nonce' );

			booked_wpml_ajax();

			if (is_user_logged_in() && isset($_POST['appt_id'])):

				include(BOOKED_AJAX_INCLUDES_DIR . 'front/cancel-appointment.php');

			endif;

			wp_die();

		}



	}
}
