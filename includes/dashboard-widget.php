<?php

class BookedDashboardWidget {

	function __construct(){
		if (current_user_can('edit_booked_appointments')):
			add_action( 'wp_dashboard_setup', array($this, 'booked_dashboard_widget') );
		endif;
	}

	public function booked_dashboard_widget() {

		wp_add_dashboard_widget(
	        'booked_upcoming_appointments',
	        '<span><i class="fa-regular fa-calendar-days"></i>&nbsp;&nbsp;'.esc_html__('Upcoming Appointments','booked') . '</span>',
	        array($this, 'booked_dashboard_widget_function')
	    );

	}

	public function booked_dashboard_widget_function() {

		echo '<div class="booked-pending-appt-list booked-dashboard-widget">';

			/*
			Set some variables
			*/

			$time_format = get_option('time_format');
			$date_format = get_option('date_format');

			/*
			Grab all of the appointments for this day
			*/

			$calendars = get_terms('booked_custom_calendars','orderby=slug&hide_empty=0');
			if (!empty($calendars) && !current_user_can('manage_booked_options')):

				$booked_current_user = wp_get_current_user();
				$calendars = booked_filter_agent_calendars($booked_current_user,$calendars);

				foreach($calendars as $calendar):
					$calendar_ids[] = $calendar->term_id;
				endforeach;

				if (count($calendar_ids) >= 1):

					$args = array(
					   'post_type' => 'booked_appointments',
					   'posts_per_page' => 5000,
					   'post_status' => array( 'publish', 'future' ),
					   'meta_key' => '_appointment_timestamp',
					   'orderby' => 'meta_value_num',
					   'order' => 'ASC',
					   'tax_query' => array(
							array(
								'taxonomy' => 'booked_custom_calendars',
								'field'    => 'term_id',
								'terms'    => $calendar_ids,
							),
						),
					);

				elseif (empty($calendar_ids) && !current_user_can('manage_booked_options')):

					$args = false;

				else:

					$args = array(
						'post_type' => 'booked_appointments',
						'posts_per_page' => 500,
						'post_status' => array( 'publish', 'future' ),
						'meta_key' => '_appointment_timestamp',
						'orderby' => 'meta_value_num',
						'order' => 'ASC'
					);

				endif;

			elseif (empty($calendars) && !current_user_can('manage_booked_options')):
				$args = false;
			else:
				$args = array(
					'post_type' => 'booked_appointments',
					'posts_per_page' => 500,
					'post_status' => array( 'publish', 'future' ),
					'meta_key' => '_appointment_timestamp',
					'orderby' => 'meta_value_num',
					'order' => 'ASC'
				);
			endif;

			$appointments_array = array();
			$counter = 0;

			$bookedAppointments = new WP_Query($args);
			if($bookedAppointments->have_posts()):
				while ($bookedAppointments->have_posts()):

					$bookedAppointments->the_post();
					global $post;

					$calendars = array();

					$calendar_terms = get_the_terms($post->ID,'booked_custom_calendars');
					if (!empty($calendar_terms)):
						foreach($calendar_terms as $calendar){
							$calendars[$calendar->term_id] = $calendar->name;
						}
					endif;

					$guest_name = get_post_meta($post->ID, '_appointment_guest_name',true);
					$guest_surname = get_post_meta($post->ID, '_appointment_guest_surname',true);
					$guest_email = get_post_meta($post->ID, '_appointment_guest_email',true);

					$timestamp = intval( get_post_meta( $post->ID, '_appointment_timestamp',true ) );
					$timeslot = get_post_meta( $post->ID, '_appointment_timeslot',true );
					$user_id = get_post_meta( $post->ID, '_appointment_user',true );

					$day = date_i18n('d',$timestamp);

					$current_timestamp = current_time('timestamp');

					if ($timestamp >= $current_timestamp){
						$counter++;

						if (!$guest_name):
							$user_id = get_post_meta($post->ID, '_appointment_user',true);
							$appointments_array[$timestamp.'-'.$post->ID]['user'] = $user_id;
						else:
							$appointments_array[$timestamp.'-'.$post->ID]['guest_name'] = $guest_name . ( $guest_surname ? ' ' . $guest_surname : '' );
							$appointments_array[$timestamp.'-'.$post->ID]['guest_email'] = $guest_email;
						endif;

						$appointments_array[$timestamp.'-'.$post->ID]['post_id'] = $post->ID;
						$appointments_array[$timestamp.'-'.$post->ID]['timestamp'] = $timestamp;
						$appointments_array[$timestamp.'-'.$post->ID]['timeslot'] = $timeslot;
						$appointments_array[$timestamp.'-'.$post->ID]['status'] = $post->post_status;
						$appointments_array[$timestamp.'-'.$post->ID]['calendar'] = implode(',',$calendars);
						if ($counter == 10): break; endif;
					}

				endwhile;
				$appointments_array = apply_filters('booked_appointments_timestamp_postid_array', $appointments_array);
			endif;

			// Sort by timestamp, just in case they aren't ordered properly.
			ksort($appointments_array);

			foreach($appointments_array as $appt):

				echo '<div class="pending-appt bookedClearFix" data-appt-id="'.$appt['post_id'].'">';

					$date_display = date_i18n($date_format,$appt['timestamp']);
					$day_name = date_i18n('l',$appt['timestamp']);

					$timeslots = explode('-',$appt['timeslot']);
					$time_start = date_i18n($time_format,strtotime($timeslots[0]));
					$time_end = date_i18n($time_format,strtotime($timeslots[1]));

					$date_to_compare = strtotime(date_i18n('Y-m-d',$appt['timestamp']).' '.date_i18n('H:i:s',strtotime($timeslots[0])));
					$late_date = current_time('timestamp');

					if ($timeslots[0] == '0000' && $timeslots[1] == '2400'):
						$timeslotText = esc_html__('All day','booked');
					else :
						$timeslotText = $time_start.'&ndash;'.$time_end;
					endif;

					$pending_statuses = apply_filters('booked_admin_pending_post_status',array('draft'));

					$status = (in_array($appt['status'],$pending_statuses) ? 'pending' : 'approved');
					echo '<span class="appt-block" data-appt-id="'.$appt['post_id'].'">';

						if (!isset($appt['guest_name'])):
							$user_info = get_userdata($appt['user']);
							if (isset($user_info->ID)):
								echo '<a href="#" class="user" data-user-id="'.$user_info->ID.'"><i class="fa-solid fa-pencil"></i>&nbsp;'.booked_get_name($user_info->ID).'</a>';
							else :
								esc_html_e('(this user no longer exists)','booked');
							endif;
						else :
							echo '<a href="#" class="user" data-user-id="0"><i class="fa-solid fa-pencil"></i>&nbsp;'.$appt['guest_name'].'</a>';
						endif;

						echo '<br>';
						if ($late_date > $date_to_compare): echo '<span class="late-appt">' . esc_html__('This appointment has passed.','booked') . '</span><br>'; endif;
						if ($appt['calendar']): echo '<strong style="color:#000">'.$appt['calendar'].'</strong><br>'; endif;
						echo '<i class="fa-solid fa-calendar-days"></i>&nbsp;&nbsp;'.$day_name.', '.$date_display;
						echo '&nbsp;&nbsp;&nbsp;<i class="fa-solid fa-clock"></i>&nbsp;&nbsp;'.$timeslotText;

					echo '</span>';

				echo '</div>';

			endforeach;

			echo '<div class="pending-appt' . ( !empty($appointments_array) ? ' no-pending-message' : '' ) . '">';
				echo '<div style="display:flex; flex-direction:column; height:200px; width:100%; justify-content:center; align-items:center;">
					<div style="font-size:50px; color:#aaa;"><i class="fa-regular fa-calendar"></i></div>
					<div style="font-size:16px; color:#aaa;">' . esc_html__('There are no upcoming appointments.','booked') . '</div>
				</div>';
			echo '</div>';

		echo '</div>';

		wp_reset_postdata();

	}

}

new BookedDashboardWidget;
