<?php
	
if(!class_exists('BookedFEA_Ajax')) {
	class BookedFEA_Ajax {
		
		public function __construct() {
			
			// Ajax Actions
			add_action('wp_ajax_booked_fea_delete_appt', array(&$this,'booked_fea_delete_appt'));
			add_action('wp_ajax_booked_fea_approve_appt', array(&$this,'booked_fea_approve_appt'));
			
			// Ajax Loaders
			add_action('wp_ajax_booked_fea_user_info_modal', array(&$this,'booked_fea_user_info_modal'));
		
		}
			
		// Delete an Appointment
		public function booked_fea_delete_appt(){
			
			if ( isset($_POST['appt_id']) && current_user_can('edit_booked_appointments') ):
		
				$appt_id = esc_html( $_POST['appt_id'] );
				$post_type = get_post_type( $appt_id );
				
				if ( $post_type == 'booked_appointments' ):
		
					// Send an email to the user?
					$email_content = get_option('booked_cancellation_email_content');
					$email_subject = get_option('booked_cancellation_email_subject');
					
					if ($email_content && $email_subject):
	
						$token_replacements = booked_get_appointment_tokens( $appt_id );
						$email_content = booked_token_replacement( $email_content,$token_replacements );
						$email_subject = booked_token_replacement( $email_subject,$token_replacements );
	
						booked_mailer( $token_replacements['email'], $email_subject, $email_content );
	
					endif;
			
					wp_delete_post($appt_id,true);
					wp_die();
					
				endif;
			
			endif;
			
		}
		
		// Approve an Appointment
		public function booked_fea_approve_appt(){
			
			if (isset($_POST['appt_id'])):
		
				$appt_id = esc_html( $_POST['appt_id'] );
		
				// Send an email to the user?
				$email_content = get_option('booked_approval_email_content');
				$email_subject = get_option('booked_approval_email_subject');

				$token_replacements = booked_get_appointment_tokens( $appt_id );

				if ($email_content && $email_subject):

					$email_content = booked_token_replacement( $email_content,$token_replacements );
					$email_subject = booked_token_replacement( $email_subject,$token_replacements );
					
					booked_mailer( $token_replacements['email'], $email_subject, $email_content );
					
				endif;
		
				wp_publish_post( $appt_id );
				wp_die();
				
			endif;
			
		}
		
		// Display the Appointment/User Info Modal
		public function booked_fea_user_info_modal(){
			
			if (isset($_POST['user_id'])):
			
				ob_start();
				
				echo '<div class="booked-scrollable">';
					echo '<p class="booked-title-bar"><small>' . __('Appointment Information','booked') . '</small></p>';
			
					if (!$_POST['user_id'] && isset($_POST['appt_id'])):
					
						$guest_name = get_post_meta($_POST['appt_id'], '_appointment_guest_name',true);
						$guest_email = get_post_meta($_POST['appt_id'], '_appointment_guest_email',true);
					
						echo '<p class="fea-modal-title">'.__('Contact Information','booked').'</p>';
						echo '<p><strong class="booked-left-title">'.__('Name','booked').':</strong> '.$guest_name.'<br>';
						if ($guest_email) : echo '<strong class="booked-left-title">'.__('Email','booked').':</strong> <a href="mailto:'.$guest_email.'">'.$guest_email.'</a>'; endif;
						echo '</p>';
						
					else :
			
						// Customer Information
						$user_info = get_userdata($_POST['user_id']);
						$display_name = booked_get_name($_POST['user_id']);
						$email = $user_info->user_email;
						$phone = get_user_meta($_POST['user_id'], 'booked_phone', true);
				
						echo '<p class="fea-modal-title">'.__('Contact Information','booked').'</p>';
						echo '<p><strong class="booked-left-title">'.__('Name','booked').':</strong> '.$display_name.'<br>';
						if ($email) : echo '<strong class="booked-left-title">'.__('Email','booked').':</strong> <a href="mailto:'.$email.'">'.$email.'</a><br>'; endif;
						if ($phone) : echo '<strong class="booked-left-title">'.__('Phone','booked').':</strong> <a href="tel:'.preg_replace('/[^0-9+]/', '', $phone).'">'.$phone.'</a>'; endif;
						echo '</p>';
			
					endif;
			
					// Appointment Information
					if (isset($_POST['appt_id'])):
			
						$time_format = get_option('time_format');
						$date_format = get_option('date_format');
						$appt_id = $_POST['appt_id'];
			
						$timestamp = get_post_meta($appt_id, '_appointment_timestamp',true);
						$timeslot = get_post_meta($appt_id, '_appointment_timeslot',true);
						$cf_meta_value = get_post_meta($appt_id, '_cf_meta_value',true);
			
						$date_display = date_i18n($date_format,$timestamp);
						$day_name = date_i18n('l',$timestamp);
			
						$timeslots = explode('-',$timeslot);
						$time_start = date($time_format,strtotime($timeslots[0]));
						$time_end = date($time_format,strtotime($timeslots[1]));
			
						if ($timeslots[0] == '0000' && $timeslots[1] == '2400'):
							$timeslotText = 'All day';
						else :
							$timeslotText = $time_start.' '.__('to','booked').' '.$time_end;
						endif;
						
						$cf_meta_value = apply_filters('booked_fea_cf_metavalue',$cf_meta_value);
			
						echo '<p class="fea-modal-title fea-bordered">'.__('Appointment Information','booked').'</p>';
						do_action('booked_before_appointment_information_admin');
						echo '<p><strong class="booked-left-title">'.__('Date','booked').':</strong> '.$day_name.', '.$date_display.'<br>';
						echo '<strong class="booked-left-title">'.__('Time','booked').':</strong> '.$timeslotText.'</p>';
						echo ($cf_meta_value ? '<div class="cf-meta-values">'.$cf_meta_value.'</div>' : '');
						do_action('booked_after_appointment_information_admin');
			
					endif;
			
					// Close button
					echo '<a href="#" class="close"><i class="fa-solid fa-xmark"></i></a>';
				echo '</div>';
				
				echo ob_get_clean();
				wp_die();
				
			endif;
			
		}
	}
}