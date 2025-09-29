<?php

if ( !isset($_GET['sh']) || isset($_GET['sh']) && $_GET['sh'] != BOOKEDICAL_SECURE_HASH )
	wp_die('<strong>Calendar Feed Requirements:</strong><br>The Booked calendar feeds now require a secure hash to access. Please take a look at your "Appointments > Calendar Feeds" page for the updated feed URLs.');

header('Content-type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename=appointment-feed-'.BOOKED_VERSION.'.ics');

if (isset($_GET['calendar']) && $_GET['calendar']):
	$calendar_id = esc_html( $_GET['calendar'] );
else:
	$calendar_id = false;
endif;

// 1 year ago to 5 years later.
$from_timestamp = strtotime(date('Y-m-01', strtotime("-1 year")));
$to_timestamp = strtotime(date('Y-m-d', strtotime("+5 years")));

$args = array(
	'post_type' => 'booked_appointments',
	'posts_per_page' => -1,
	'post_status' => array('publish', 'future'),
	'meta_query' => array(
		array(
			'key'     => '_appointment_timestamp',
			'value'   => array( $from_timestamp, $to_timestamp ),
			'compare' => 'BETWEEN',
		)
	)
);

if ($calendar_id):
	$args['tax_query'] = array(
		array(
			'taxonomy' => 'booked_custom_calendars',
			'field'    => 'id',
			'terms'    => $calendar_id,
		)
	);
endif;

if ($calendar_id):
	$calendar_name = get_term_by('id',$calendar_id,'booked_custom_calendars');
	$calendar_name = $calendar_name->name;
else :
	$calendar_name = 'Appointments';
endif;

$appts_in_this_timeslot = array();

$bookedAppointments = new WP_Query($args);
if($bookedAppointments->have_posts()):
	while ($bookedAppointments->have_posts()):
		$bookedAppointments->the_post();
		global $post;
		$timestamp = get_post_meta($post->ID, '_appointment_timestamp',true);
		$timeslot = get_post_meta($post->ID, '_appointment_timeslot',true);
		$user_id = get_post_meta($post->ID, '_appointment_user',true);
		$day = date('d',$timestamp);
		$appointments_array[$post->ID]['post_id'] = $post->ID;
		$appointments_array[$post->ID]['timestamp'] = $timestamp;
		$appointments_array[$post->ID]['timeslot'] = $timeslot;
		$appointments_array[$post->ID]['status'] = $post->post_status;
		$appointments_array[$post->ID]['user'] = $user_id;
		$appts_in_this_timeslot[] = $post->ID;
	endwhile;
endif;

?>BEGIN:VCALENDAR<?php echo "\r\n"; ?>
VERSION:2.0<?php echo "\r\n"; ?>
PRODID:-//getbooked.io//Booked Calendar<?php echo "\r\n"; ?>
CALSCALE:GREGORIAN<?php echo "\r\n"; ?>
<?php if (!empty($appts_in_this_timeslot)):

	foreach($appts_in_this_timeslot as $appt_id):

		$guest_name = get_post_meta($appt_id, '_appointment_guest_name',true);
		$guest_surname = get_post_meta($appt_id, '_appointment_guest_surname',true);
		$guest_name = $guest_name . ( $guest_surname ? ' ' . $guest_surname : '' );
		$guest_email = get_post_meta($appt_id, '_appointment_guest_email',true);

		if (!$guest_name):

			// Customer Information
			$user_id = $appointments_array[$appt_id]['user'];
			if ($user_id):
				$user_info = get_userdata($user_id);
				$display_name = booked_get_name($user_id);
				if ( !empty($user_info) ):
					$email = $user_info->user_email;
				else:
					$display_name = esc_html__('[No name]','booked');
					$email = esc_html__('[No email]','booked');
				endif;
			else:
				$display_name = esc_html__('[No name]','booked');
				$email = esc_html__('[No email]','booked');
			endif;

		else:

			$display_name = $guest_name;
			$email = $guest_email;

		endif;

		$display_name = clean_calendarString($display_name);
		$email = clean_calendarString($email);

		// Appointment Information
		if ( isset($appt_id) ):

			$time_format = get_option('time_format');
			$date_format = get_option('date_format');
			$appt_id = $appt_id;

			$timestamp = get_post_meta($appt_id, '_appointment_timestamp',true);
			$timeslot = get_post_meta($appt_id, '_appointment_timeslot',true);
			$cf_meta_value = get_post_meta($appt_id, '_cf_meta_value',true);

			$timeslots = explode('-',$timeslot);

			if ($timeslots[0] == '0000' && $timeslots[1] == '2400'):
				$formatted_start_date = dateToCal(get_post_meta($appt_id, '_appointment_timestamp',true),true);
				$formatted_end_date = false;
			else :
				$end_date = date('Y-m-d',strtotime(get_gmt_from_date(date('Y-m-d H:i:s',get_post_meta($appt_id, '_appointment_timestamp',true)))));
				$end_date_time = $end_date . date('H:i:s',strtotime(get_gmt_from_date(date('Y-m-d H:i:s',strtotime($end_date.' '.$timeslots[1])))));
				$formatted_start_date = dateToCal(get_post_meta($appt_id, '_appointment_timestamp',true));
				$formatted_end_date = date('Ymd\THis',strtotime($end_date_time));
			endif;

			$cf_fields = array(); $cf_counter = 0;

			preg_match_all('/<p class=\"cf-meta-value\">([\\s\\S]*?)<\/p>/s', $cf_meta_value, $cf_meta_matches);

			if ( isset($cf_meta_matches[1]) ):

				foreach($cf_meta_matches[1] as $cf_meta_match):

					$p_content = ( isset( $cf_meta_match ) ? $cf_meta_match : false );

					if ($p_content):

						$p_content = explode('<br>',$p_content);
						preg_match('/<strong>(.*?)<\/strong>/s', $p_content[0], $s_matches);

						if ( isset($s_matches[1]) && $s_matches[1] && isset($p_content[1]) && $p_content[1] ):
							$cf_fields[$cf_counter]['title'] = ( isset( $s_matches[1] ) ? clean_calendarString( $s_matches[1] ) : false );
							$cf_fields[$cf_counter]['content'] = ( isset( $p_content[1] ) ? clean_calendarString( $p_content[1] ) : false );
							$cf_counter++;
						endif;

					endif;

				endforeach;

			endif;

			$description = ( $email ? $email . "\r\n" . ( !empty($cf_fields) ? display_customFields($cf_fields) : "" ) : "" );
			$description = str_replace("\r\n", "\\n\\n", $description);

?>BEGIN:VEVENT<?php echo "\r\n"; ?>
DTSTAMP:<?php echo $formatted_start_date; ?>Z<?php echo "\r\n"; ?><?php
if ($formatted_end_date):
?>DTSTART:<?php echo $formatted_start_date; ?>Z<?php echo "\r\n"; ?>
DTEND:<?php echo $formatted_end_date; ?>Z<?php echo "\r\n"; ?><?php
else:
?>DTSTART;VALUE=DATE:<?php echo $formatted_start_date; ?><?php echo "\r\n"; ?>
DTEND;VALUE=DATE:<?php echo $formatted_start_date; ?><?php echo "\r\n"; ?><?php
endif;
?>SUMMARY:<?php echo apply_filters( 'booked_calendar_feed_display_name', $display_name, $appt_id ); ?><?php echo "\r\n"; ?>
<?php echo ( $description ? "DESCRIPTION:" . $description . "\r\n" : "" ); ?>
UID:booked-appointment-<?php echo $appt_id; ?><?php echo "\r\n"; ?>
END:VEVENT<?php echo "\r\n"; ?>
<?php

		endif;

	endforeach;

endif;

?>END:VCALENDAR<?php

/* Convert Dates */
function dateToCal($timestamp,$all_day = false) {
	if ($all_day):
		return date('Ymd',strtotime(get_gmt_from_date(date('Y-m-d H:i:s',$timestamp))));
	else:
		return date('Ymd\THis',strtotime(get_gmt_from_date(date('Y-m-d H:i:s',$timestamp))));
	endif;
}

function clean_calendarString($string = false){

	if ($string):

		preg_match_all( "/<\!--([\\s\\S]*?)-->/", $string, $matches );
		if ( isset($matches[0]) && !empty($matches[0]) ):
			foreach ($matches[0] as $match ):
				$string = str_replace( $match, '', $string );
			endforeach;
		endif;

		if (function_exists('mb_convert_encoding')):
			$string = mb_convert_encoding( $string, 'UTF-8' );
		else:
			$string = htmlspecialchars_decode(utf8_decode(htmlentities($string, ENT_COMPAT, 'utf-8', false)));
		endif;

		return preg_replace( "/([\,\;])/","\\\$1", $string);

	else:

		return false;

	endif;

}

/* Convert Custom Fields */
function display_customFields($cf_fields) {

	ob_start();

	foreach($cf_fields as $field):
		echo trim( $field['title'] ) . "\\n";
		echo trim( $field['content'] ) . "\r\n";
	endforeach;

	return ob_get_clean();

}
