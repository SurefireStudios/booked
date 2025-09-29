<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ($_POST['appointment_time'] && $_POST['appointment_time'] == 'upcoming'):
	$current_timestamp = current_time('timestamp');
	$meta_query = array(
		array(
			'key'     => '_appointment_timestamp',
			'value'   => $current_timestamp,
			'compare' => '>='
		)
	);
elseif ($_POST['appointment_time'] && $_POST['appointment_time'] == 'past'):
	$current_timestamp = current_time('timestamp');
	$meta_query = array(
		array(
			'key'     => '_appointment_timestamp',
			'value'   => $current_timestamp,
			'compare' => '<'
		)
	);
else:
	$meta_query = array();
endif;

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=booked_appointments_export.csv');
$output = fopen('php://output', 'w');

$export_columns = apply_filters( 'booked_csv_export_columns', array(
	'First Name',
	'Last Name',
	'Email',
	'Calendar',
	'Date',
	'Start Time',
	'End Time',
	'Combined Date/Time',
	'Custom Field Data' ));

fputcsv( $output, $export_columns );

$args = array(
	'post_type' => 'booked_appointments',
	'posts_per_page' => 500,
	'post_status' => $_POST['appointment_type'],
	'meta_key'   => '_appointment_timestamp',
	'orderby'    => 'meta_value_num',
	'order'      => 'ASC',
	'meta_query' => $meta_query
);

if ($_POST['calendar_id']):
	$args['tax_query'] = array(
		array(
			'taxonomy' => 'booked_custom_calendars',
			'field'    => 'term_id',
			'terms'    => $_POST['calendar_id'],
		)
	);
endif;

$appointments_array = array();
$date_format = get_option('date_format');
$time_format = get_option('time_format');

$bookedAppointments = new WP_Query( apply_filters('booked_fe_date_content_query',$args) );
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

		$timestamp = get_post_meta($post->ID, '_appointment_timestamp',true);
		$timeslot = get_post_meta($post->ID, '_appointment_timeslot',true);
		$timeslot = explode('-',$timeslot);
		$user_id = get_post_meta($post->ID, '_appointment_user',true);

		$customer_name = false;
		$customer_surname = false;
		$customer_email = false;

		if ($user_id):
			$user_info = get_userdata($user_id);
			if (!empty($user_info)):
				$customer_name = booked_get_name( $user_id, 'first' );
				$customer_surname = booked_get_name( $user_id, 'last' );
				$customer_email = $user_info->user_email;
			else:
				continue;
			endif;
		endif;

		if (!$customer_name):

			$customer_name = get_post_meta($post->ID, '_appointment_guest_name',true);
			$customer_surname = get_post_meta($post->ID, '_appointment_guest_surname',true);
			$customer_email = get_post_meta($post->ID, '_appointment_guest_email',true);

		endif;

		$cf_meta_value = get_post_meta($post->ID, '_cf_meta_value',true);
		if ($cf_meta_value):
			$cf_meta_value = rtrim(strip_tags(str_replace(array('</p>','<br>'),array("\n\n","\n"),$cf_meta_value)),"\n\n");
		else:
			$cf_meta_value = '';
		endif;

		$date_start = date_i18n($date_format,$timestamp);
		$time_start = date_i18n($time_format,strtotime(date_i18n('Y-m-d',$timestamp).' '.$timeslot[0]));
		$time_end = date_i18n($time_format,strtotime(date_i18n('Y-m-d',$timestamp).' '.$timeslot[1]));

		$appointments_array[$post->ID]['customer_name'] = esc_html( $customer_name );
		$appointments_array[$post->ID]['customer_surname'] = esc_html( $customer_surname );
		$appointments_array[$post->ID]['customer_email'] = esc_html( $customer_email );
		$appointments_array[$post->ID]['calendar'] = implode(',',$calendars);
		$appointments_array[$post->ID]['appointment_date'] = $date_start;
		$appointments_array[$post->ID]['appointment_start_time'] = $time_start;
		$appointments_array[$post->ID]['appointment_end_time'] = $time_end;
		$appointments_array[$post->ID]['appointment_combined_date_time'] = $date_start.' from '.$time_start.' to '.$time_end;
		$appointments_array[$post->ID]['custom_field_data'] = $cf_meta_value;

	endwhile;
endif;

foreach($appointments_array as $appt_id => $appointment):
	$appointment = apply_filters( 'booked_csv_row_data', $appointment, $appt_id );
	fputcsv( $output, $appointment );
endforeach;
die;
