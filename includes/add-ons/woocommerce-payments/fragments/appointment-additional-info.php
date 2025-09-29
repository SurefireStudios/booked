<?php

if ( !isset($appointment_id) || !is_numeric($appointment_id) ) {
	return;
}

$appointment = Booked_WC_Appointment::get($appointment_id);

// if there are no products then do not show payment info
if ( !$appointment->products ) {
	return;
}

$payment_class = 'booked_wc_payment_pending';

// if order is available then pull the status from it
if ( $appointment->order_id ) {
	if ( $appointment->is_paid ) {
		$payment_class = 'booked_wc_payment_completed';
	}
	$status_text = $appointment->payment_status_text;
} else if ( !$appointment->order_id || $appointment->order_id && !$appointment->is_paid ) {
	$status_text = __('Awaiting Payment', 'booked');
}
?>

<br><i class="fa-solid fa-cart-shopping"></i><span class="<?php echo $payment_class ?>"><?php echo $status_text ?></span>
