<?php

if ( ! isset( $bookings ) ) {
	return;
}

$guest_booking = get_option( 'booked_booking_type', 'registered' ) === 'guest';

$customer_type = 'current';
if ( ! $is_user_logged_in ) {
	$customer_type = 'new';

	if ( $guest_booking ) {
		$customer_type = 'guest';
	}
}

$total_calendars = count( $bookings );
$appointment_counter = 0;
?>
<div class="bookings">

	<?php

	// should never come here, but just in case
	if ( !$total_calendars ) {
		esc_html_e( 'Sorry, there are no bookings available', 'booked' );
		return;
	}

	// When the bookings share same calendar
	if ( $total_calendars === 1 ):

		foreach ( $bookings as $calendar_id => $appointments ): ?>
			<input type="hidden" name="calendar_id" value="<?php echo intval($calendar_id); ?>" />

			<div class="booked-appointments">
				<?php
				foreach ($appointments as $appointment_key => $appointment):
					include( BOOKED_AJAX_INCLUDES_DIR . 'front/appointment-form/appointment.php' );
					$appointment_counter++;
				endforeach;
				?>
			</div><?php

			if ( get_option('users_can_register') && !$is_user_logged_in && $customer_type != 'guest' ) {
				include( BOOKED_AJAX_INCLUDES_DIR . 'front/appointment-form/form-fields-registration.php' );
			} elseif ( !$is_user_logged_in && $customer_type == 'guest' ) {
				include( BOOKED_AJAX_INCLUDES_DIR . 'front/appointment-form/form-fields-guest.php' );
			}

			?><div class="booked-calendar-fields">
				<?php booked_custom_fields( $calendar_id ); ?>
			</div><?php

		endforeach;
	endif;



	// When the bookings share more than one calendar. Tabbed interface to switch between the calendars.
	if ( $total_calendars > 1 ): ?>
		<div class="booked-tabs">
			<div class="booked-tabs-nav">
				<?php $i = 0; foreach ( $bookings as $calendar_id => $appointments ): ?>
					<?php
					$calendar_title = esc_html__('Unknown', 'booked');

					if ( ! $calendar_id ) {
						$calendar_title = esc_html__('Default', 'booked');
					}

					if ( $calendar_id && ( $calendar = get_term_by( 'id', $calendar_id, BOOKED_MS_TAX_CALENDAR ) ) ) {
						$calendar_title = $calendar->name;
					}
					?>
					<span class="<?php echo $i === 0 ? 'active' : '' ?>" data-tab-cnt="booked-calendar-<?php echo intval($calendar_id) ?>" ><?php echo esc_html( $calendar_title  ); ?></span>
					<?php $i++; ?>
				<?php endforeach ?>
			</div>

			<div class="booked-tabs-cnt">
				<?php $i = 0; foreach ( $bookings as $calendar_id => $appointments ): ?>
					<div class="booked-calendar-<?php echo intval($calendar_id) ?> <?php echo $i === 0 ? 'active' : '' ?>">
						<div class="booked-appointments">
							<?php
							foreach ($appointments as $appointment_key => $appointment) {
								// print the appointment details
								include(BOOKED_AJAX_INCLUDES_DIR . 'front/appointment-form/appointment.php');
								$appointment_counter++;
							}
							?>
						</div>
					</div>
					<?php $i++; ?>
				<?php endforeach ?>
			</div>

			<?php
			if ( get_option('users_can_register') && !$is_user_logged_in ) {
				include(BOOKED_AJAX_INCLUDES_DIR . 'front/appointment-form/form-fields-registration.php');
			}
			?>

			<div class="booked-tabs-cnt">
				<?php $i = 0; foreach ( $bookings as $calendar_id => $appointments ): ?>
					<div class="booked-calendar-<?php echo intval($calendar_id) ?> <?php echo $i === 0 ? 'active' : '' ?>">
						<div class="booked-calendar-fields">
							<?php booked_custom_fields( $calendar_id ); ?>
						</div>
					</div>
					<?php $i++; ?>
				<?php endforeach ?>
			</div>
		</div>
	<?php endif ?>
</div>
