<?php

class Booked_FEA_Shortcodes {

	function __construct(){
		add_shortcode('booked-fea-appointments', array($this, 'booked_fea_appointments_shortcode') );
	}

	/* FEA APPOINTMENTS SHORTCODE */
	public function booked_fea_appointments_shortcode($atts = null, $content = null) {

		ob_start();

		if (is_user_logged_in() && current_user_can('edit_booked_appointments')):

			$booked_current_user = wp_get_current_user();
			$my_id = $booked_current_user->ID;
			
			$calendars = get_terms('booked_custom_calendars',array('order_by' => 'slug','hide_empty' => false));
			$calendar_ids = array();
			
			if (!empty($calendars)):
				foreach($calendars as $calendar):
					$calendar_id = $calendar->term_id;
					$term_meta = get_option( "taxonomy_$calendar_id" );
					if ($booked_current_user->user_email == $term_meta['notifications_user_id']):
						$calendar_ids[] = $calendar_id;
					endif;
				endforeach;
			endif;
			
			$historic = isset($atts['historic']) && $atts['historic'] ? true : false;
			$pending = isset($atts['pending']) && $atts['pending'] ? true : false;

			$time_format = get_option('time_format');
			$date_format = get_option('date_format');
			$appointments_array = booked_agent_appointments($my_id,false,$time_format,$date_format,$calendar_ids,$pending,$historic);
			$total_appts = count($appointments_array);
			$appointment_default_status = get_option('booked_new_appointment_default','draft');

			if (!isset($atts['remove_wrapper'])): echo '<div id="booked-profile-page" class="booked-shortcode">'; endif;
			
				echo '<div class="booked-profile-appt-list booked-fea-appt-list">';
				
					$booked_light_color = get_option('booked_light_color','#44535B');
					$booked_button_color = get_option('booked_button_color','#56C477');
				
					echo '<style type="text/css">';
						echo "body #booked-profile-page .booked-profile-appt-list button.button-primary { background:$booked_button_color; border-color:$booked_button_color; color:#fff; }\n";
						echo "body #booked-profile-page .booked-profile-appt-list button.button-primary:hover { background:$booked_light_color; border-color:$booked_light_color; }";
						echo "body #booked-profile-page .booked-profile-appt-list .appt-block .booked-wc_status-text.paid { color:$booked_button_color; }";
					echo '</style>';

					if ($pending):
						if ($total_appts):
							echo '<h4>'.sprintf(_n('%s Pending Appointment','%s Pending Appointments',$total_appts,'booked'),'<span class="count">'.number_format($total_appts).'</span>').'</h4>';
						else:
							echo '<p class="booked-no-margin">'.__('No pending appointments.','booked').'</p>';
						endif;
					elseif ($historic):
						if ($total_appts):
							echo '<h4>'.sprintf(_n('%s Past Appointment','%s Past Appointments',$total_appts,'booked'),'<span class="count">'.number_format($total_appts).'</span>').'</h4>';
						else:
							echo '<p class="booked-no-margin">'.__('No past appointments.','booked').'</p>';
						endif;	
					else:
						if ($total_appts):
							echo '<h4>'.sprintf(_n('%s Upcoming Appointment','%s Upcoming Appointments',$total_appts,'booked'),'<span class="count">'.number_format($total_appts).'</span>').'</h4>';
						else:
							echo '<p class="booked-no-margin">'.__('No upcoming appointments.','booked').'</p>';
						endif;
					endif;
				
					foreach($appointments_array as $appt):
					
						$calendar_id = isset($appt['calendar_id'][0]->term_id) ? $appt['calendar_id'][0]->term_id : false;

						$today = date_i18n($date_format);
						$date_display = date_i18n($date_format,$appt['timestamp']);
						if ($date_display == $today){
							$date_display = __('Today','booked');
							$day_name = '';
						} else {
							$day_name = date_i18n('l',$appt['timestamp']).', ';
						}

						$date_to_convert = date('F j, Y',$appt['timestamp']);

						$cf_meta_value = get_post_meta($appt['post_id'], '_cf_meta_value',true);

						$timeslots = explode('-',$appt['timeslot']);
						$time_start = date($time_format,strtotime($timeslots[0]));
						$time_end = date($time_format,strtotime($timeslots[1]));

						$appt_date_time = strtotime($date_to_convert.' '.date('H:i:s',strtotime($timeslots[0])));
						$current_timestamp = current_time('timestamp');
						
						$google_date_startend = date('Ymd',$appt['timestamp']);
						$google_time_start = date('Hi',strtotime($timeslots[0]));
						$google_time_end = date('Hi',strtotime($timeslots[1]));

						$cancellation_buffer = get_option('booked_cancellation_buffer',0);

						if ($cancellation_buffer):
							if ($cancellation_buffer < 1){
								$time_type = 'minutes';
								$time_count = $cancellation_buffer * 60;
							} else {
								$time_type = 'hours';
								$time_count = $cancellation_buffer;
							}
							$buffered_timestamp = strtotime('+'.$time_count.' '.$time_type,$current_timestamp);
							$date_to_compare = $buffered_timestamp;
						else:
							$date_to_compare = current_time('timestamp');
						endif;

						if ($timeslots[0] == '0000' && $timeslots[1] == '2400'):
							$timeslotText = __('All day','booked');
							$google_date_startend_end = date('Ymd',strtotime(date('Y-m-d',$appt['timestamp']) . '+ 1 Day'));
							$google_time_end = '0000';
						else :
							$timeslotText = (!get_option('booked_hide_end_times') ? __('from','booked').' ' : __('at','booked').' ') . $time_start . (!get_option('booked_hide_end_times') ? ' ' . __('to','booked').' '.$time_end : '');
							$google_date_startend_end = $google_date_startend;
						endif;

						$status = ($appt['status'] !== 'publish' && $appt['status'] !== 'future' ? __('pending','booked') : __('approved','booked'));
						$status_class = $appt['status'] !== 'publish' && $appt['status'] !== 'future' ? 'pending' : 'approved';
						
						echo '<div class="appt-block bookedClearFix" data-appt-id="'.$appt['post_id'].'">';
						
							$default_button_html = '<div class="booked-fea-buttons">';
								$default_button_html .= '<a href="#" class="delete"'.($calendar_id ? ' data-calendar-id="'.$calendar_id.'"' : '').'><i class="fa-solid fa-xmark"></i></a>';
								$default_button_html .= ($status_class == 'pending' ? '<button data-appt-id="'.$appt['post_id'].'" class="approve button button-primary">'.__('Approve','booked').'</button>' : '');
							$default_button_html .=	'</div>';
						
							$default_button_html = apply_filters('booked_fea_shortcode_appointments_buttons', $default_button_html, $appt['post_id']);
							echo $default_button_html;
					
							$date_display = date_i18n($date_format,$appt['timestamp']);
							$day_name = date_i18n('l',$appt['timestamp']);
		
							$timeslots = explode('-',$appt['timeslot']);
							$time_start = date_i18n($time_format,strtotime($timeslots[0]));
							$time_end = date_i18n($time_format,strtotime($timeslots[1]));
							
							$atc_date_startend = date('Y-m-d',$appt['timestamp']);
							$atc_time_start = date('H:i:s',strtotime($timeslots[0]));
							$atc_time_end = date('H:i:s',strtotime($timeslots[1]));
	
							$date_to_compare = strtotime(date('F j, Y',$appt['timestamp']).' '.date('H:i:s',strtotime($timeslots[0])));
							$late_date = current_time('timestamp');
	
							if ($timeslots[0] == '0000' && $timeslots[1] == '2400'):
								$timeslotText = __('All day','booked');
								$atc_date_startend_end = date('Y-m-d',strtotime(date('Y-m-d',$appt['timestamp']) . '+ 1 Day'));
								$atc_time_end = '00:00:00';
							else :
								$timeslotText = (!get_option('booked_hide_end_times') ? __('from','booked').' ' : __('at','booked').' ') . $time_start . (!get_option('booked_hide_end_times') ? ' ' . __('to','booked').' '.$time_end : '');
								$atc_date_startend_end = $atc_date_startend;
							endif;
							
							$pending_statuses = apply_filters('booked_admin_pending_post_status',array('draft'));
		
							$status = (in_array($appt['status'],$pending_statuses) ? 'pending' : 'approved');
							$display_name = false;
							
							if (!isset($appt['guest_name'])):
								$user_info = get_userdata($appt['user']);
								if (isset($user_info->ID)):
									if ($user_info->user_firstname):
										$user_display = '<a href="#" class="user" data-user-id="'.$appt['user'].'">'.$user_info->user_firstname.($user_info->user_lastname ? ' '.$user_info->user_lastname : '').'</a>';
										$display_name = $user_info->user_firstname.($user_info->user_lastname ? ' '.$user_info->user_lastname : '');
									elseif ($user_info->display_name):
										$user_display = '<a href="#" class="user" data-user-id="'.$appt['user'].'">'.$user_info->display_name.'</a>';
										$display_name = $user_info->display_name;
									else:
										$user_display = '<a href="#" class="user" data-user-id="'.$appt['user'].'">'.$user_info->user_login.'</a>';
										$display_name = $user_info->user_login;
									endif;
								else :
									$user_display = __('(this user no longer exists)','booked');
								endif;
							else :
								$user_display = '<a href="#" class="user" data-user-id="0">'.$appt['guest_name'].'</a>';
								$display_name = $appt['guest_name'];
							endif;
						
							echo $user_display;
							echo '<br>';
							if ($late_date > $date_to_compare): echo '<span class="late-appt">' . __('This appointment has passed.','booked') . '</span><br>'; endif;
							if ($appt['calendar_id']): echo '<strong style="color:#000">'.$appt['calendar_id'][0]->name.'</strong><br>'; endif;
							echo '<i class="fa-solid fa-calendar-days"></i>'.$day_name.', '.$date_display;
							echo '&nbsp;&nbsp;&nbsp;<i class="fa-solid fa-clock"></i>'.$timeslotText;
							
							do_action('booked_shortcode_appointments_additional_information', $appt['post_id']);
							$cf_meta_value = apply_filters('booked_fea_cf_metavalue',$cf_meta_value);
							echo ($cf_meta_value ? '<br><i class="fa-solid fa-circle-info"></i><a href="#" class="booked-show-cf">'.__('Additional information','booked').'</a><div class="cf-meta-values-hidden">'.$cf_meta_value.'</div>' : '');
							
							if (!$historic):
								if ($appt_date_time >= $date_to_compare):
							
									$calendar_button_array = array(
										'atc_date_startend' => $atc_date_startend,
										'atc_time_start' => $atc_time_start,
										'atc_date_startend_end' => $atc_date_startend_end,
										'atc_time_end' => $atc_time_end,
									);
									
									ob_start();
									booked_add_to_calendar_button($calendar_button_array,$cf_meta_value);
									$buttons_content = ob_get_clean();
								
									if ($buttons_content):
										echo '<div class="booked-cal-buttons">';
											echo $buttons_content;
										echo '</div>';
									endif;
									
								endif;
							endif;
		
						echo '</div>';

					endforeach;

				echo '</div>';


			if (!isset($atts['remove_wrapper'])): echo '</div>'; endif;

			wp_reset_postdata();

		else :

			return '<p>'.__('Please log in to view your upcoming appointments.','booked').'</p>';

		endif;

		return ob_get_clean();

	}

}

$booked_fea_shortcodes = new Booked_FEA_Shortcodes;