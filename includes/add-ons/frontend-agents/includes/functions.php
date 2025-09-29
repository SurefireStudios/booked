<?php
	
function booked_profile_content_fea_appointments(){
	echo do_shortcode('[booked-fea-appointments remove_wrapper=1]');	
}

function booked_profile_content_fea_history(){
	echo do_shortcode('[booked-fea-appointments remove_wrapper=1 historic=1]');	
}

function booked_profile_content_fea_pending(){
	echo do_shortcode('[booked-fea-appointments remove_wrapper=1 pending=1]');	
}
	
function booked_agent_appointments($user_id,$only_count = false,$time_format = false,$date_format = false,$calendar_ids = array(),$pending = false,$historic = false){

	if (!$date_format || !$time_format){
		$time_format = get_option('time_format');
		$date_format = get_option('date_format');
	}
	
	if ($pending):
		$statuses = apply_filters('booked_admin_pending_post_status',array('draft'));
	else:
		$statuses = apply_filters('booked_admin_approved_post_status',array('publish','future'));
	endif;
	
	$order = $historic ? 'DESC' : 'ASC';
	$count = $historic ? 50 : -1;
	
	$calendars = get_terms('booked_custom_calendars','orderby=slug&hide_empty=0');
	$default_calendar_id = false;
	
	if (!empty($calendars)):
	
		if (!current_user_can('manage_booked_options')):
		
			$booked_current_user = wp_get_current_user();
			$calendars = booked_filter_agent_calendars($booked_current_user,$calendars);
			
			if (empty($calendars)):
				$booked_none_assigned = true;
			else:
				$first_calendar = array_slice($calendars, 0, 1);
				$default_calendar_id = array_shift($first_calendar)->term_id;
				$booked_none_assigned = false;
			endif;
		
		else:
			$booked_none_assigned = false;
		endif;
		
	endif;
	
	if (empty($calendars) && !current_user_can('manage_booked_options')):
			
		$args = false;
		
	elseif(current_user_can('manage_booked_options')):
	
		$args = array(
			'post_type' => 'booked_appointments',
			'posts_per_page' => -1,
			'post_status' => $statuses,
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
			'posts_per_page' => $count,
			'post_status' => $statuses,
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
				$appt_date_value = date('Y-m-d',get_post_meta($post->ID, '_appointment_timestamp',true));
				$appt_timeslot = get_post_meta($post->ID, '_appointment_timeslot',true);
				$appt_timeslots = explode('-',$appt_timeslot);
				$appt_time_start = date('H:i:s',strtotime($appt_timeslots[0]));
	
				$appt_timestamp = strtotime($appt_date_value.' '.$appt_time_start);
				$current_timestamp = current_time('timestamp');
	
				$day = date('d',$appt_timestamp);
				$category = get_the_category();
				$calendar_id = wp_get_post_terms( $post->ID, 'booked_custom_calendars' );
	
				$guest_name = get_post_meta($post->ID, '_appointment_guest_name',true);
				$guest_surname = get_post_meta($post->ID, '_appointment_guest_surname',true);
				$guest_email = get_post_meta($post->ID, '_appointment_guest_email',true);
	
				if (!$historic && $appt_timestamp >= $current_timestamp || $historic && $appt_timestamp < $current_timestamp){
					
					if (!$guest_name):
						$user_id = get_post_meta($post->ID, '_appointment_user',true);
						$appointments_array[$post->ID]['user'] = $user_id;
					else:
						$appointments_array[$post->ID]['guest_name'] = $guest_name . ( $guest_surname ? ' ' . $guest_surname : '' );
						$appointments_array[$post->ID]['guest_email'] = $guest_email;
					endif;
					
					$appointments_array[$post->ID]['post_id'] = $post->ID;
					$appointments_array[$post->ID]['timestamp'] = $appt_timestamp;
					$appointments_array[$post->ID]['timeslot'] = $appt_timeslot;
					$appointments_array[$post->ID]['calendar_id'] = $calendar_id;
					$appointments_array[$post->ID]['status'] = $post->post_status;
				}
	
			endwhile;
			$appointments_array = apply_filters('booked_appointments_array', $appointments_array);
		endif;
	
		wp_reset_query();
		if ($only_count):
			return count($appointments_array);
		else :
			return $appointments_array;
		endif;

	else :
		if ($only_count):
			return 0;
		else:
			return array();
		endif;
	
	endif;

}