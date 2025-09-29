<?php

if(!class_exists('Booked_Admin_AJAX')) {
	class Booked_Admin_AJAX {

		public function __construct() {
			add_action('admin_init', [ $this, 'init' ] );
		}

		public function init(){
			if (current_user_can('edit_booked_appointments')):
				// ------------ Actions ------------ //
				add_action('wp_ajax_booked_admin_add_appt', array(&$this,'booked_admin_add_appt'));
				add_action('wp_ajax_booked_admin_edit_appt', array(&$this,'booked_admin_edit_appt'));
				add_action('wp_ajax_booked_admin_delete_custom_timeslot', array(&$this,'booked_admin_delete_custom_timeslot'));
				add_action('wp_ajax_booked_admin_adjust_custom_timeslot_count', array(&$this,'booked_admin_adjust_custom_timeslot_count'));
				add_action('wp_ajax_booked_admin_add_custom_timeslot', array(&$this,'booked_admin_add_custom_timeslot'));
				add_action('wp_ajax_booked_admin_add_custom_timeslots', array(&$this,'booked_admin_add_custom_timeslots'));
				add_action('wp_ajax_booked_admin_save_custom_time_slots', array(&$this,'booked_admin_save_custom_time_slots'));
				add_action('wp_ajax_booked_admin_save_custom_fields', array(&$this,'booked_admin_save_custom_fields'));
				add_action('wp_ajax_booked_admin_add_timeslots', array(&$this,'booked_admin_add_timeslots'));
				add_action('wp_ajax_booked_admin_add_timeslot', array(&$this,'booked_admin_add_timeslot'));
				add_action('wp_ajax_booked_admin_clear_timeslots', array(&$this,'booked_admin_clear_timeslots'));
				add_action('wp_ajax_booked_admin_adjust_default_timeslot_count', array(&$this,'booked_admin_adjust_default_timeslot_count'));
				add_action('wp_ajax_booked_admin_delete_timeslot', array(&$this,'booked_admin_delete_timeslot'));
				add_action('wp_ajax_booked_admin_delete_appt', array(&$this,'booked_admin_delete_appt'));
				add_action('wp_ajax_booked_admin_approve_appt', array(&$this,'booked_admin_approve_appt'));
				add_action('wp_ajax_booked_admin_approve_all', array(&$this,'booked_admin_approve_all'));
				add_action('wp_ajax_booked_admin_delete_all', array(&$this,'booked_admin_delete_all'));
				add_action('wp_ajax_booked_admin_delete_past', array(&$this,'booked_admin_delete_past'));
				add_action('wp_ajax_booked_date_formatting', array(&$this,'booked_date_formatting'));
				add_action('wp_ajax_booked_admin_disable_slot', array(&$this,'booked_admin_disable_slot'));

				// ------------ Loaders ------------ //
				add_action('wp_ajax_booked_admin_load_timeslots', array(&$this,'booked_admin_load_timeslots'));
				add_action('wp_ajax_booked_admin_load_full_timeslots', array(&$this,'booked_admin_load_full_timeslots'));
				add_action('wp_ajax_booked_admin_load_full_customfields', array(&$this,'booked_admin_load_full_customfields'));
				add_action('wp_ajax_booked_admin_calendar_picker', array(&$this,'booked_admin_calendar_picker'));
				add_action('wp_ajax_booked_admin_calendar_month', array(&$this,'booked_admin_calendar_month'));
				add_action('wp_ajax_booked_admin_calendar_date', array(&$this,'booked_admin_calendar_date'));
				add_action('wp_ajax_booked_admin_refresh_date_square', array(&$this,'booked_admin_refresh_date_square'));
				add_action('wp_ajax_booked_admin_user_info_modal', array(&$this,'booked_admin_user_info_modal'));
				add_action('wp_ajax_booked_admin_new_appointment_form', array(&$this,'booked_admin_new_appointment_form'));
				add_action('wp_ajax_booked_admin_custom_timeslots_list', array(&$this,'booked_admin_custom_timeslots_list'));
				add_action('wp_ajax_booked_admin_get_timeslots_select', array(&$this,'booked_admin_get_timeslots_select'));
			endif;
		}
		
		
		public static function nonce_check( $nonce ){
			if ( !wp_verify_nonce( $_POST['nonce'], $nonce ) ){
				die ( 'Required "nonce" value is not here, please let the developer know.');
			}
		}


		// ------------ ACTIONS ------------ //

		// Enable/Disable Time Slot
		public function booked_admin_disable_slot(){
			
			Booked_Admin_AJAX::nonce_check('ajax-admin-nonce');

			booked_wpml_ajax();

			$date = esc_html( $_POST['date'] );
			$date = date( 'Y-m-d', strtotime( $date ) );
			$timeslot = esc_html( $_POST['timeslot'] );
			$calendar_id = esc_html( $_POST['calendar_id'] );

			$disabled_timeslots = get_option( 'booked_disabled_timeslots', array() );

			if ( $calendar_id ):
				if ( isset( $disabled_timeslots[$calendar_id][$date][$timeslot] ) && $disabled_timeslots[$calendar_id][$date][$timeslot] ):
					echo 'enabled';
					unset( $disabled_timeslots[$calendar_id][$date][$timeslot] );
				else:
					echo 'disabled';
					$disabled_timeslots[$calendar_id][$date][$timeslot] = true;
				endif;
			else:
				if ( isset( $disabled_timeslots[0][$date][$timeslot] ) && $disabled_timeslots[0][$date][$timeslot] ):
					echo 'enabled';
					unset( $disabled_timeslots[0][$date][$timeslot] );
				else:
					echo 'disabled';
					$disabled_timeslots[0][$date][$timeslot] = true;
				endif;
			endif;

			update_option( 'booked_disabled_timeslots', $disabled_timeslots );
			wp_die();

		}

		// Date Formatting
		public function booked_date_formatting(){
			
			Booked_Admin_AJAX::nonce_check('ajax-admin-nonce');

			booked_wpml_ajax();

			if (isset($_POST['date']) && $_POST['date']):
				$date_format = get_option('date_format');
				echo ucwords( date_i18n( $date_format,strtotime(esc_html( $_POST['date'] )) ) );
			else:
				echo '';
			endif;

			wp_die();

		}

		// Add Appointment
		public function booked_admin_add_appt(){
			
			Booked_Admin_AJAX::nonce_check('ajax-admin-add-appt-nonce');

			booked_wpml_ajax();

			if (isset($_POST['date']) && isset($_POST['timestamp']) && isset($_POST['timeslot']) && isset($_POST['customer_type'])):

				include(BOOKED_AJAX_INCLUDES_DIR . 'admin/add-appointment.php');

			endif;
			wp_die();

		}

		public function booked_admin_edit_appt(){
			
			Booked_Admin_AJAX::nonce_check('ajax-admin-edit-appt-nonce');

			booked_wpml_ajax();

			if ( is_user_logged_in() && current_user_can( 'edit_booked_appointments' ) ):

				$email_required = get_option('booked_require_guest_email_address',false);

				if ( isset($_POST['appt_date']) && isset($_POST['appt_timeslot']) && isset($_POST['name']) ):

					if ( !$email_required || $email_required && isset($_POST['email']) ):

						include(BOOKED_AJAX_INCLUDES_DIR . 'admin/edit-appointment.php');

					endif;

				endif;

			else:

				echo 'error###' . esc_html__( "Your user level does not have the ability to edit appointments.", "booked" );

			endif;

			wp_die();

		}

		// Delete Custom Timeslot
		public function booked_admin_delete_custom_timeslot(){
			
			Booked_Admin_AJAX::nonce_check('ajax-admin-nonce');

			booked_wpml_ajax();

			if (isset($_POST['timeslot']) && isset($_POST['currentArray'])):

				$timeslot_to_delete = $_POST['timeslot'];
				$current_times = json_decode(stripslashes($_POST['currentArray']),true);
				$current_times_details = json_decode(stripslashes($_POST['currentArrayDetails']),true);

				do_action('booked_deleting_custom_timeslot',$_POST['start_date'],$_POST['end_date'],$_POST['timeslot'],$_POST['calendar_id']);

				if (isset($current_times[$timeslot_to_delete])):
					unset($current_times[$timeslot_to_delete]);
					unset($current_times_details[$timeslot_to_delete]);
				endif;

				echo json_encode(array(
					'timeslot' => $current_times,
					'timeslot_details' => $current_times_details
				));

			endif;
			wp_die();

		}

		// Adjust Custom Timeslot Count
		public function booked_admin_adjust_custom_timeslot_count(){
			
			Booked_Admin_AJAX::nonce_check('ajax-admin-nonce');

			booked_wpml_ajax();

			if (isset($_POST['currentArray']) && isset($_POST['timeslot']) && isset($_POST['newCount'])):

				$current_times = json_decode(stripslashes($_POST['currentArray']),true);
				$timeslot = $_POST['timeslot'];
				$newCount = $_POST['newCount'];

				if (!empty($current_times[$timeslot])):

					$current_count = $current_times[$timeslot];
					if ($newCount > 0):
						$current_times[$timeslot] = $newCount;
					else :
						$current_times[$timeslot] = 1;
					endif;

					$current_times_details[$timeslot]['title'] = $title;

				endif;

				echo json_encode($current_times);

			endif;
			wp_die();

		}

		// Add Custom Timeslot
		public function booked_admin_add_custom_timeslot(){
			
			Booked_Admin_AJAX::nonce_check('ajax-admin-nonce');

			booked_wpml_ajax();

			if (isset($_POST['startTime']) && isset($_POST['endTime']) && isset($_POST['count']) && isset($_POST['currentTimes'])):

				$title = isset($_POST['title']) ? $_POST['title'] : '';
				$startTime = $_POST['startTime'];
				$endTime = $_POST['endTime'];
				$count = $_POST['count'];
				$current_times = json_decode(stripslashes($_POST['currentTimes']),true);
				$current_times_details = !empty($_POST['currentTimesDetails']) ? json_decode(stripslashes($_POST['currentTimesDetails']),true) : array();

				if ($startTime == 'allday'):
					$startTime = '0000';
					$endTime = '2400';
				endif;

				do_action('booked_creating_custom_timeslot',$_POST['start_date'],$_POST['end_date'],$startTime,$endTime,$_POST['calendar_id'],$title);

				if (isset($current_times[$startTime.'-'.$endTime])):
					$current_times[$startTime.'-'.$endTime] = $current_times[$startTime.'-'.$endTime] + $count;
				else :
					$current_times[$startTime.'-'.$endTime] = $count;
				endif;

				$current_times_details[$startTime.'-'.$endTime]['title'] = $title;

				echo json_encode(array(
					'timeslot' => $current_times,
					'timeslot_details' => $current_times_details,
				));

			endif;
			wp_die();

		}

		// Add Custom Timeslots
		public function booked_admin_add_custom_timeslots(){
			
			Booked_Admin_AJAX::nonce_check('ajax-admin-nonce');

			booked_wpml_ajax();

			if (isset($_POST['startTime']) && isset($_POST['endTime']) && isset($_POST['interval']) && isset($_POST['count']) && isset($_POST['time_between'])):
				$title = isset($_POST['title']) ? $_POST['title'] : '';

				$startTime = $_POST['startTime'];
				$endTime = $_POST['endTime'];
				if ($_POST['endTime'] == '2400'):
					$endTime = '2400';
				endif;

				$interval = $_POST['interval'];
				$count = $_POST['count'];
				$time_between = $_POST['time_between'];
				$current_times = json_decode(stripslashes($_POST['currentTimes']),true);
				$current_times_details = !empty($_POST['currentTimesDetails']) ? json_decode(stripslashes($_POST['currentTimesDetails']),true) : array();

				do {

					$newStartTime = date_i18n("Hi", strtotime('+'.$interval.' minutes', strtotime($startTime)));

					if (isset($current_times[$startTime.'-'.$newStartTime])):
						$current_times[$startTime.'-'.$newStartTime] = $current_times[$startTime.'-'.$newStartTime] + $count;
					else :
						$current_times[$startTime.'-'.$newStartTime] = $count;
					endif;

					$current_times_details[$startTime.'-'.$newStartTime]['title'] = $title;

					do_action('booked_creating_custom_timeslot',$_POST['start_date'],$_POST['end_date'],$startTime,$newStartTime,$_POST['calendar_id'],$title);

					if ($time_between):
						$time_to_add = $time_between + $interval;
					else :
						$time_to_add = $interval;
					endif;
					$startTime = date_i18n("Hi", strtotime('+'.$time_to_add.' minutes', strtotime($startTime)));
					if ($startTime == '0000'):
						$startTime = '2400';
					endif;

				} while ($startTime < $endTime);

				echo json_encode(array(
					'timeslot' => $current_times,
					'timeslot_details' => $current_times_details,
				));

			endif;
			wp_die();

		}

		// Save Custom Timeslots
		public function booked_admin_save_custom_time_slots(){
			
			Booked_Admin_AJAX::nonce_check('ajax-admin-nonce');

			booked_wpml_ajax();

			if (isset($_POST['custom_timeslots_encoded'])):

				$custom_timeslots_encoded = htmlentities( stripslashes($_POST['custom_timeslots_encoded']), ENT_NOQUOTES );
				update_option('booked_custom_timeslots_encoded',$custom_timeslots_encoded);

			endif;

			wp_die();

		}

		// Save Custom Fields
		public function booked_admin_save_custom_fields(){
			
			Booked_Admin_AJAX::nonce_check('ajax-admin-nonce');

			booked_wpml_ajax();

			if (isset($_POST['booked_custom_fields'])):

				$custom_fields = $_POST['booked_custom_fields'];
				$calendar_id = $_POST['booked_cf_calendar_id'];
				if ($custom_fields != '[]'):
					if ($calendar_id):
						update_option('booked_custom_fields_'.$calendar_id,$custom_fields);
					else:
						update_option('booked_custom_fields',$custom_fields);
					endif;
				else:
					if ($calendar_id):
						delete_option('booked_custom_fields_'.$calendar_id);
					else:
						delete_option('booked_custom_fields');
					endif;
				endif;

			endif;
			wp_die();

		}

		// Add Timeslots
		public function booked_admin_add_timeslots(){
			
			Booked_Admin_AJAX::nonce_check('ajax-admin-nonce');

			booked_wpml_ajax();

			if ( isset($_POST['day']) && isset($_POST['startTime']) && isset($_POST['endTime']) ):

				$calendar_id = (isset($_POST['calendar_id']) ? $_POST['calendar_id'] : false);
				$title = isset($_POST['title']) ? $_POST['title'] : '';

				$day = esc_html( $_POST['day'] );
				$startTime = esc_html( $_POST['startTime'] );
				$endTime = esc_html( $_POST['endTime'] );
				$interval = esc_html( $_POST['interval'] );
				$count = esc_html( $_POST['count'] );
				$time_between = esc_html( $_POST['time_between'] );

				if ($calendar_id):
					$booked_defaults = get_option('booked_defaults_'.$calendar_id);
				else :
					$booked_defaults = get_option('booked_defaults');
				endif;

				if ( empty($booked_defaults) ): $booked_defaults = array(); endif;

				$temp_date = date_i18n( 'Y-m-d' );
				$loop_started = false;

				do {

					$newStartTime = date_i18n( "Hi", strtotime( '+' . $interval . ' minutes', strtotime( $temp_date . ' ' . $startTime ) ) );
					if ( $newStartTime < $endTime ):
						if (!empty($booked_defaults[$day][$startTime.'-'.$newStartTime])): $currentCount = $booked_defaults[$day][$startTime.'-'.$newStartTime]; else : $currentCount = 0; endif;
						$booked_defaults[$day][$startTime.'-'.$newStartTime] = $count + $currentCount;
						$booked_defaults[$day.'-details'][$startTime.'-'.$newStartTime]['title'] = $title;
					endif;

					if ( $time_between ):
						$time_to_add = $time_between + $interval;
					else :
						$time_to_add = $interval;
					endif;

					$startTime = date_i18n("Hi", strtotime('+'.$time_to_add.' minutes', strtotime( $temp_date . ' ' . $startTime ) ) );
					$mins = (int)$startTime;

					if ( $loop_started && $last_mins > $mins ):
						break;
					else:
						$last_mins = $mins;
						$loop_started = true;
					endif;

				} while ( $startTime < $endTime );

				if ($calendar_id):
					update_option('booked_defaults_'.$calendar_id,apply_filters('booked_update_timeslots',$booked_defaults));
				else :
					update_option('booked_defaults',apply_filters('booked_update_timeslots',$booked_defaults));
				endif;

			endif;
			wp_die();

		}

		// Clear Timeslots
		public function booked_admin_clear_timeslots(){
			
			Booked_Admin_AJAX::nonce_check('ajax-admin-nonce');

			booked_wpml_ajax();

			if ( isset($_POST['day']) ):

				$calendar_id = (isset($_POST['calendar_id']) ? esc_html( $_POST['calendar_id'] ) : false);
				$day = esc_html( $_POST['day'] );

				if ($calendar_id):
					$booked_defaults = get_option( 'booked_defaults_' . $calendar_id );
				else :
					$booked_defaults = get_option( 'booked_defaults' );
				endif;

				if ( isset( $booked_defaults[$day] ) ): unset( $booked_defaults[$day] ); endif;
				if ( isset( $booked_defaults[$day . '-details'] ) ): unset( $booked_defaults[$day . '-details'] ); endif;

				if ($calendar_id):
					update_option('booked_defaults_'.$calendar_id,apply_filters('booked_update_timeslots',$booked_defaults));
				else :
					update_option('booked_defaults',apply_filters('booked_update_timeslots',$booked_defaults));
				endif;

			endif;
			wp_die();

		}

		// Add Timeslot
		public function booked_admin_add_timeslot(){
			
			Booked_Admin_AJAX::nonce_check('ajax-admin-nonce');

			booked_wpml_ajax();

			if (isset($_POST['day']) && isset($_POST['startTime']) && isset($_POST['count'])):

				$calendar_id = (isset($_POST['calendar_id']) ? $_POST['calendar_id'] : false);
				$title = isset($_POST['title']) ? $_POST['title'] : '';

				$day = $_POST['day'];
				$startTime = $_POST['startTime'];
				$endTime = isset($_POST['endTime']) ? $_POST['endTime'] : false;
				$count = $_POST['count'];

				if ($startTime == 'allday'):
					$startTime = '0000';
					$endTime = '2400';
				endif;

				if ($calendar_id):
					$booked_defaults = get_option('booked_defaults_'.$calendar_id);
				else :
					$booked_defaults = get_option('booked_defaults');
				endif;

				if (empty($booked_defaults)): $booked_defaults = array(); endif;

				if (!empty($booked_defaults[$day][$startTime.'-'.$endTime])): $currentCount = $booked_defaults[$day][$startTime.'-'.$endTime]; else : $currentCount = 0; endif;
				$booked_defaults[$day][$startTime.'-'.$endTime] = $count + $currentCount;
				$booked_defaults[$day.'-details'][$startTime.'-'.$endTime]['title'] = $title;

				do_action('booked_creating_timeslot',$day,$startTime,$endTime,$calendar_id);

				if ($calendar_id):
					update_option('booked_defaults_'.$calendar_id,apply_filters('booked_update_timeslots',$booked_defaults));
				else :
					update_option('booked_defaults',apply_filters('booked_update_timeslots',$booked_defaults));
				endif;

			endif;
			wp_die();

		}

		// Adjust Default Timeslot Count
		public function booked_admin_adjust_default_timeslot_count(){
			
			Booked_Admin_AJAX::nonce_check('ajax-admin-nonce');

			booked_wpml_ajax();

			if (isset($_POST['newCount']) && isset($_POST['day']) && isset($_POST['timeslot'])):

				$calendar_id = (isset($_POST['calendar_id']) ? $_POST['calendar_id'] : false);

				$day = $_POST['day'];
				$timeslot = $_POST['timeslot'];
				$newCount = $_POST['newCount'];

				if ($calendar_id):
					$booked_defaults = get_option('booked_defaults_'.$calendar_id);
				else :
					$booked_defaults = get_option('booked_defaults');
				endif;

				if (!empty($booked_defaults[$day][$timeslot])):

					$booked_defaults[$day][$timeslot] = $newCount;

					if ($calendar_id):
						update_option('booked_defaults_'.$calendar_id,$booked_defaults);
					else :
						update_option('booked_defaults',$booked_defaults);
					endif;

				endif;

			endif;
			wp_die();

		}

		// Delete Timeslot
		public function booked_admin_delete_timeslot(){
			
			Booked_Admin_AJAX::nonce_check('ajax-admin-nonce');

			booked_wpml_ajax();

			if (isset($_POST['day']) && isset($_POST['timeslot'])):

				$calendar_id = (isset($_POST['calendar_id']) ? $_POST['calendar_id'] : false);

				$day = $_POST['day'];
				$timeslot = $_POST['timeslot'];

				if ($calendar_id):
					$booked_defaults = get_option('booked_defaults_'.$calendar_id);
				else :
					$booked_defaults = get_option('booked_defaults');
				endif;

				do_action('booked_deleting_timeslot',$day,$timeslot,$calendar_id);

				if (!empty($booked_defaults[$day][$timeslot])):

					unset($booked_defaults[$day][$timeslot]);
					unset($booked_defaults[$day.'-details'][$timeslot]);

					$timeslot_total = 0;
					foreach($booked_defaults as $default):
						if (!empty($default)):
							$timeslot_total++;
						endif;
					endforeach;

					if ($calendar_id):
						if ($timeslot_total):
							update_option('booked_defaults_'.$calendar_id,$booked_defaults);
						else :
							delete_option('booked_defaults_'.$calendar_id);
						endif;
					else :
						if ($timeslot_total):
							update_option('booked_defaults',$booked_defaults);
						else :
							delete_option('booked_defaults');
						endif;
					endif;

				endif;

			endif;
			wp_die();

		}

		// Delete Appointment
		public function booked_admin_delete_appt(){
			
			Booked_Admin_AJAX::nonce_check('ajax-admin-nonce');

			booked_wpml_ajax();

			if (isset($_POST['appt_id'])):

				$appt_id = $_POST['appt_id'];
				include(BOOKED_AJAX_INCLUDES_DIR . 'admin/delete-appointment.php');

			endif;
			wp_die();

		}

		// Approve Appointment
		public function booked_admin_approve_appt(){
			
			Booked_Admin_AJAX::nonce_check('ajax-admin-nonce');

			booked_wpml_ajax();

			if (isset($_POST['appt_id'])):

				$appt_id = $_POST['appt_id'];
				booked_send_user_approved_email($appt_id);
				wp_publish_post($appt_id);
				do_action('booked_appointment_approved',$appt_id);

			endif;
			wp_die();

		}

		// Approve All Appointments
		public function booked_admin_approve_all(){
			
			Booked_Admin_AJAX::nonce_check('ajax-admin-nonce');

			booked_wpml_ajax();

			$calendars = get_terms('booked_custom_calendars','orderby=slug&hide_empty=0');
			$booked_none_assigned = true;
			$default_calendar_id = false;

			if (!empty($calendars)):

				if (!current_user_can('manage_booked_options')):

					$booked_current_user = wp_get_current_user();
					$calendars = booked_filter_agent_calendars($booked_current_user,$calendars);

				endif;

			endif;

			if (empty($calendars) && !current_user_can('manage_booked_options')):

				wp_die();

			elseif(current_user_can('manage_booked_options')):

				$args = array(
					'post_type' => 'booked_appointments',
					'posts_per_page' => 500,
					'post_status' => apply_filters('booked_admin_pending_post_status',array('draft')),
					'meta_key' => '_appointment_timestamp',
					'orderby' => 'meta_value_num',
					'order' => 'ASC'
				);

			else:

				$calendar_ids = array();

				if (!empty($calendars)):
					foreach($calendars as $cal):
						$calendar_ids[] = $cal->term_id;
					endforeach;
				endif;

				$args = array(
					'post_type' => 'booked_appointments',
					'posts_per_page' => 500,
					'post_status' => apply_filters('booked_admin_pending_post_status',array('draft')),
					'meta_key' => '_appointment_timestamp',
					'orderby' => 'meta_value_num',
					'order' => 'ASC'
				);

				if (!empty($calendar_ids)):
					$args['tax_query'] = array(
						array(
							'taxonomy' => 'booked_custom_calendars',
							'field'    => 'term_id',
							'terms'    => $calendar_ids,
						)
					);
				endif;

			endif;

			$appointments_array = array();

			if ($args):
				$bookedAppointments = new WP_Query($args);
				if($bookedAppointments->have_posts()):
					while ($bookedAppointments->have_posts()):

						$bookedAppointments->the_post();
						$appt_id = $bookedAppointments->post->ID;

						booked_send_user_approved_email($appt_id);
						wp_publish_post($appt_id);
						do_action('booked_appointment_approved',$appt_id);

					endwhile;
				endif;
			endif;

			wp_die();

		}

		// Delete All Appointments
		public function booked_admin_delete_all(){
			
			Booked_Admin_AJAX::nonce_check('ajax-admin-nonce');

			booked_wpml_ajax();

			$calendars = get_terms('booked_custom_calendars','orderby=slug&hide_empty=0');
			$booked_none_assigned = true;
			$default_calendar_id = false;

			if (!empty($calendars)):

				if (!current_user_can('manage_booked_options')):

					$booked_current_user = wp_get_current_user();
					$calendars = booked_filter_agent_calendars($booked_current_user,$calendars);

				endif;

			endif;

			if (empty($calendars) && !current_user_can('manage_booked_options')):

				wp_die();

			elseif(current_user_can('manage_booked_options')):

				$args = array(
					'post_type' => 'booked_appointments',
					'posts_per_page' => 500,
					'post_status' => apply_filters('booked_admin_pending_post_status',array('draft')),
					'meta_key' => '_appointment_timestamp',
					'orderby' => 'meta_value_num',
					'order' => 'ASC'
				);

			else:

				$calendar_ids = array();

				if (!empty($calendars)):
					foreach($calendars as $cal):
						$calendar_ids[] = $cal->term_id;
					endforeach;
				endif;

				$args = array(
					'post_type' => 'booked_appointments',
					'posts_per_page' => 500,
					'post_status' => apply_filters('booked_admin_pending_post_status',array('draft')),
					'meta_key' => '_appointment_timestamp',
					'orderby' => 'meta_value_num',
					'order' => 'ASC'
				);

				if (!empty($calendar_ids)):
					$args['tax_query'] = array(
						array(
							'taxonomy' => 'booked_custom_calendars',
							'field'    => 'term_id',
							'terms'    => $calendar_ids,
						)
					);
				endif;

			endif;

			$appointments_array = array();

			if ($args):
				$bookedAppointments = new WP_Query($args);
				if($bookedAppointments->have_posts()):
					while ($bookedAppointments->have_posts()):

						$bookedAppointments->the_post();
						global $post;
						$appt_id = $post->ID;

						include(BOOKED_AJAX_INCLUDES_DIR . 'admin/delete-appointment.php');

					endwhile;
				endif;
			endif;

			wp_die();

		}

		// Delete Past Appointments
		public function booked_admin_delete_past(){
			
			Booked_Admin_AJAX::nonce_check('ajax-admin-nonce');

			booked_wpml_ajax();

			$calendars = get_terms('booked_custom_calendars','orderby=slug&hide_empty=0');
			$booked_none_assigned = true;
			$default_calendar_id = false;

			if (!empty($calendars)):

				if (!current_user_can('manage_booked_options')):

					$booked_current_user = wp_get_current_user();
					$calendars = booked_filter_agent_calendars($booked_current_user,$calendars);

				endif;

			endif;

			if (empty($calendars) && !current_user_can('manage_booked_options')):

				wp_die();

			elseif(current_user_can('manage_booked_options')):

				$args = array(
					'post_type' => 'booked_appointments',
					'posts_per_page' => 500,
					'post_status' => apply_filters('booked_admin_pending_post_status',array('draft')),
					'meta_key' => '_appointment_timestamp',
					'orderby' => 'meta_value_num',
					'order' => 'ASC'
				);

			else:

				$calendar_ids = array();

				if (!empty($calendars)):
					foreach($calendars as $cal):
						$calendar_ids[] = $cal->term_id;
					endforeach;
				endif;

				$args = array(
					'post_type' => 'booked_appointments',
					'posts_per_page' => 500,
					'post_status' => apply_filters('booked_admin_pending_post_status',array('draft')),
					'meta_key' => '_appointment_timestamp',
					'orderby' => 'meta_value_num',
					'order' => 'ASC'
				);

				if (!empty($calendar_ids)):
					$args['tax_query'] = array(
						array(
							'taxonomy' => 'booked_custom_calendars',
							'field'    => 'term_id',
							'terms'    => $calendar_ids,
						)
					);
				endif;

			endif;

			$late_date = current_time('timestamp');
			$appointments_array = array();

			if ($args):
				$bookedAppointments = new WP_Query($args);
				if($bookedAppointments->have_posts()):
					while ($bookedAppointments->have_posts()):

						$bookedAppointments->the_post();
						global $post;
						$appt_id = $post->ID;

						$timestamp = get_post_meta($post->ID, '_appointment_timestamp',true);
						$timeslot = get_post_meta($appt_id, '_appointment_timeslot',true);
						$timeslots = explode('-',$timeslot);

						$date_to_compare = strtotime(date_i18n('Y-m-d',$timestamp).' '.date_i18n('H:i:s',strtotime($timeslots[0])));

						if ($late_date > $date_to_compare):
							include(BOOKED_AJAX_INCLUDES_DIR . 'admin/delete-appointment.php');
						endif;

					endwhile;
				endif;
			endif;

			wp_die();

		}



		// ------------ LOADERS ------------ //

		// Timeslot Select Box
		public function booked_admin_get_timeslots_select(){
			
			Booked_Admin_AJAX::nonce_check('ajax-admin-nonce');

			booked_wpml_ajax();

			if ( !isset($_POST['date']) || !isset($_POST['appt_id']) )
				wp_die();

			$date = esc_html( $_POST['date'] );
			$year = date_i18n( 'Y', strtotime($date) );
			$month = date_i18n( 'm', strtotime($date) );
			$day = date_i18n( 'd', strtotime($date) );
			$appt_id = ( isset($_POST['appt_id'] ) ? esc_html( $_POST['appt_id'] ) : false );

			booked_timeslots_select( $appt_id, $year, $month, $day );
			wp_die();

		}

		// Timeslots
		public function booked_admin_load_timeslots(){
			
			Booked_Admin_AJAX::nonce_check('ajax-admin-nonce');

			booked_wpml_ajax();

			if (isset($_POST['day'])):

				$calendar_id = (isset($_POST['calendar_id']) ? esc_html( $_POST['calendar_id'] ) : false);

				if ($calendar_id):
					$booked_defaults = get_option('booked_defaults_'.$calendar_id);
				else :
					$booked_defaults = get_option('booked_defaults');
				endif;

				$day = esc_html( $_POST['day'] );
				$time_format = get_option('time_format');

				if (!empty($booked_defaults[$day])):
					ksort($booked_defaults[$day]);
					foreach($booked_defaults[$day] as $time => $count):
						echo booked_render_timeslot_info($time_format,$day,$time,$count,$calendar_id, $booked_defaults);
					endforeach;
				else :
					echo '<p><small>'.esc_html__('No time slots.','booked').'</small></p>';
				endif;

			endif;
			wp_die();

		}

		// Custom Timeslots
		public function booked_admin_custom_timeslots_list(){
			
			Booked_Admin_AJAX::nonce_check('ajax-admin-nonce');

			booked_wpml_ajax();

			if (isset($_POST['json_array'])):

				$this_timeslot = array();
				$this_timeslot['booked_custom_start_date'] = $_POST['start_date'];
				if ($_POST['end_date']): $this_timeslot['booked_custom_end_date'] = $_POST['end_date']; else: $this_timeslot['booked_custom_end_date'] = $_POST['start_date']; endif;
				$calendar_id = $_POST['calendar_id'];

				$timeslots = json_decode(stripslashes($_POST['json_array']),true);
				$timeslots_detailed = !empty($_POST['json_array_detailed']) ? json_decode(stripslashes($_POST['json_array_detailed']),true) : array();

				if (!empty($timeslots)):

					echo '<div class="cts-header"><span class="slotsTitle">'.esc_html__('Spaces Available','booked').'</span>'.esc_html__('Time Slot','booked').'</div>';

					foreach ($timeslots as $timeslot => $count):

						$time = explode('-',$timeslot);
						$time_format = get_option('time_format');

						echo '<span class="timeslot" data-timeslot="'.$timeslot.'">';
						echo '<span class="slotsBlock"><span class="changeCount minus" data-count="-1"><i class="fa-solid fa-circle-minus"></i></span><span class="count"><em>'.$count.'</em> ' . _n('Space Available','Spaces Available',$count,'booked') . '</span><span class="changeCount add" data-count="1"><i class="fa-solid fa-circle-plus"></i></span></span>';

						do_action( 'booked_single_custom_timeslot_start', $this_timeslot, $timeslot, $calendar_id );

						if (  !empty( $timeslots_detailed[$timeslot] ) ) {

							if ( !empty($timeslots_detailed[$timeslot]['title']) ) {
								echo '<span class="title">' . esc_html($timeslots_detailed[$timeslot]['title']) . '</span>';
							}

						}

						if ($time[0] == '0000' && $time[1] == '2400'):
							echo '<span class="start"><i class="fa-solid fa-clock"></i>&nbsp;&nbsp;' . strtoupper(esc_html__('All day','booked')) . '</span>';
						else :
							echo '<span class="start"><i class="fa-solid fa-clock"></i>&nbsp;&nbsp;' . date_i18n($time_format,strtotime('2014-01-01 '.$time[0])) . '</span> &ndash; <span class="end">' . date_i18n($time_format,strtotime('2014-01-01 '.$time[1])) . '</span>';
						endif;

						do_action( 'booked_single_custom_timeslot_end', $this_timeslot, $timeslot, $calendar_id );

						echo '<span class="delete"><i class="fa-solid fa-xmark"></i></span>';
						echo '</span>';

					endforeach;

					echo '</div>';

				endif;

			endif;
			wp_die();

		}

		// Full Timeslots
		public function booked_admin_load_full_timeslots(){
			
			Booked_Admin_AJAX::nonce_check('ajax-admin-nonce');

			booked_wpml_ajax();

			$calendar_id = (isset($_POST['calendar_id']) ? $_POST['calendar_id'] : false);
			booked_render_timeslots($calendar_id);
			wp_die();

		}

		// Full Custom Fields
		public function booked_admin_load_full_customfields(){
			
			Booked_Admin_AJAX::nonce_check('ajax-admin-nonce');

			booked_wpml_ajax();

			$calendar_id = (isset($_POST['calendar_id']) ? $_POST['calendar_id'] : false);
			booked_render_custom_fields($calendar_id);
			wp_die();

		}

		// Calendar Picker
		public function booked_admin_calendar_picker(){
			
			Booked_Admin_AJAX::nonce_check('ajax-admin-nonce');

			booked_wpml_ajax();

			if (isset($_POST['gotoMonth'])):

				$timestamp = ($_POST['gotoMonth'] != 'false' ? strtotime($_POST['gotoMonth']) : current_time('timestamp'));
				$year = date_i18n('Y',$timestamp);
				$month = date_i18n('m',$timestamp);
				booked_admin_calendar($year,$month,false,'small');

			endif;
			wp_die();

		}

		// Calendar Month
		public function booked_admin_calendar_month(){
			
			Booked_Admin_AJAX::nonce_check('ajax-admin-nonce');

			booked_wpml_ajax();

			if (isset($_POST['gotoMonth'])):

				$calendar_id = (isset($_POST['calendar_id']) ? $_POST['calendar_id'] : false);
				$timestamp = ($_POST['gotoMonth'] != 'false' ? strtotime($_POST['gotoMonth']) : current_time('timestamp'));

				$year = date_i18n('Y',$timestamp);
				$month = date_i18n('m',$timestamp);

				booked_admin_calendar($year,$month,$calendar_id);

			endif;
			wp_die();

		}

		// Calendar Day
		public function booked_admin_calendar_date(){
			
			Booked_Admin_AJAX::nonce_check('ajax-admin-nonce');

			booked_wpml_ajax();

			if (isset($_POST['date'])):

				$calendar_id = (isset($_POST['calendar_id']) ? esc_html( $_POST['calendar_id'] ) : false);
				$date = esc_html( $_POST['date'] );
				booked_admin_calendar_date_content($date,$calendar_id);

			endif;
			wp_die();

		}

		// Calendar Date Square
		public function booked_admin_refresh_date_square(){
			
			Booked_Admin_AJAX::nonce_check('ajax-admin-nonce');

			booked_wpml_ajax();

			if (isset($_POST['date'])):

				$calendar_id = (isset($_POST['calendar_id']) ? $_POST['calendar_id'] : false);
				$date = esc_html( $_POST['date'] );
				booked_admin_calendar_date_square($date,$calendar_id);

			endif;
			wp_die();

		}

		// User Info Modal
		public function booked_admin_user_info_modal(){
			
			Booked_Admin_AJAX::nonce_check('ajax-admin-nonce');

			booked_wpml_ajax();

			if (isset($_POST['user_id'])):

				include(BOOKED_AJAX_INCLUDES_DIR . 'admin/user-info.php');

			endif;
			wp_die();

		}

		// New Appointment Form
		public function booked_admin_new_appointment_form(){
			
			Booked_Admin_AJAX::nonce_check('ajax-admin-nonce');

			booked_wpml_ajax();

			if (isset($_POST['date']) && isset($_POST['timeslot'])):

				include(BOOKED_AJAX_INCLUDES_DIR . 'admin/appointment-form.php');

			endif;
			wp_die();

		}


	}
}
