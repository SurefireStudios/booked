<?php

if ( !isset($appointment_id) || !is_numeric($appointment_id) ) {
	return;
}

$appointment = Booked_WC_Appointment::get($appointment_id);
$awaiting_status = BOOKED_WC_PLUGIN_PREFIX . 'awaiting';

// add buttons only on appointments with products
if ( !$appointment->products ) {
	return;
}

$current_time = current_time('timestamp');

// check if the date has been passed
// if so, hide the edit button
if ( $current_time > $appointment->timestamp ) {
	return;
}

if ( !$appointment->calendar ) {
	return;
}

$calendar_link = $appointment->calendar->calendar_link;
if ( !$calendar_link ) {
	return;
}

$calendar_link = esc_url(add_query_arg(array(
	'app_id' => $appointment_id,
	'app_action' => 'edit',
	'source' => 'booked_wc_extension'
), $calendar_link));

if ( !$appointment->is_paid && $appointment->payment_status == 'awaiting_checkout' ): ?>
	<a href="#" data-appt-id="<?php echo $appointment_id ?>" class="pay"><?php _e('Pay', 'booked'); ?></a>
<?php endif ?>

<a href="<?php echo $calendar_link ?>" data-app-calendar="<?php echo $calendar_link ?>" data-appt-id="<?php echo $appointment_id ?>" class="edit"><?php _e('Change Date', 'booked'); ?></a>

<?php if ( !get_option('booked_dont_allow_user_cancellations',false) ) {

	$current_timestamp = current_time('timestamp');
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

	$date_to_convert = date_i18n('Y-m-d',$appointment->timestamp);
	$timeslots = explode('-',$appointment->timeslot);
	$appt_date_time = strtotime($date_to_convert.' '.date_i18n('H:i:s',strtotime($timeslots[0])));

	if ( $appt_date_time >= $date_to_compare) {
		if (!$appointment->is_paid || $appointment->is_paid && $appointment->order_id == 'manual'){
			echo '<a href="#" data-appt-id="'.$appointment_id.'" class="cancel">'.__('Cancel Appointment','booked').'</a>';
		}
	}
}
