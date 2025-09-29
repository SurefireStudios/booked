<?php
$is_user_logged_in = is_user_logged_in();
$booked_current_user = $is_user_logged_in ? wp_get_current_user() : false;

$guest_booking = get_option( 'booked_booking_type', 'registered' ) === 'guest';
$new_appointment_default = get_option('booked_new_appointment_default','draft');

$customer_type = 'current';
if ( ! $is_user_logged_in ) {
	$customer_type = 'new';

	if ( $guest_booking ) {
		$customer_type = 'guest';
	}
}

// check the limit
$reached_limit = false;
$will_reached_limit = false;
$appointment_limit = get_option( 'booked_appointment_limit' );
if ( $is_user_logged_in && $appointment_limit ) {
	$upcoming_user_appointments = booked_user_appointments( $booked_current_user->ID, true );
	$reached_limit = $upcoming_user_appointments >= $appointment_limit;

	// check the reached limit when there are more than one appointment to book
	// in some cases the limit might be reached after booking too many appointments at a time
	if ( $total_appts > 1 ) {
		$will_reached_limit = ( $upcoming_user_appointments + $total_appts ) >= $appointment_limit;
	}
}
?>

<?php // Not logged in and guest booking is disabled ?>
<?php if ( ! $is_user_logged_in && ! $guest_booking ): ?>

	<form name="customerChoices" action="" id="customerChoices" class="bookedClearFix"<?php echo ( !get_option('users_can_register') ? ' style="display:none;"' : '' ); ?>>

		<?php if ( get_option('users_can_register') ): ?>
			<div class="field">
				<span class="checkbox-radio-block">
					<input data-condition="customer_choice" type="radio" name="customer_choice[]" id="customer_new" value="new" checked="checked">
					<label for="customer_new"><?php esc_html_e('New customer','booked'); ?></label>
				</span>
			</div>
		<?php endif; ?>

		<div class="field">
			<span class="checkbox-radio-block">
				<input data-condition="customer_choice" type="radio" name="customer_choice[]" id="customer_current" value="current"<?php echo ( !get_option('users_can_register') ? ' checked="checked"' : '' ); ?>>
				<label for="customer_current"><?php esc_html_e('Current customer','booked'); ?></label>
			</span>
		</div>
	</form>

	<div class="condition-block customer_choice<?php echo ( !get_option('users_can_register') && !is_user_logged_in() ? ' default' : '' ); ?>" id="condition-current">

		<?php
		$tmp_bookings = $bookings;
		$first_booking = array_shift( $tmp_bookings );
		$first_booking = ! empty($first_booking) ? $first_booking[0] : array( 'date' => '', 'title' => '', 'timeslot' => '', 'calendar_id' => '' );
		?>
		<form id="ajaxlogin" action="" method="post" data-date="<?php echo $first_booking['date']; ?>" data-title="<?php echo $first_booking['title']; ?>" data-timeslot="<?php echo $first_booking['timeslot']; ?>" data-calendar-id="<?php echo $first_booking['calendar_id']; ?>">
			<div class="cf-block">

				<?php include(BOOKED_AJAX_INCLUDES_DIR . 'front/appointment-form/form-fields-login.php'); ?>

				<input type="hidden" name="action" value="booked_ajax_login">
				<?php wp_nonce_field( 'ajax_login_nonce', 'security' ); ?>

				<div class="field">
					<p class="status"></p>
				</div>

				<?php if ( !is_multisite() ): ?>
				<a href="#" class="booked-forgot-password"><?php esc_html_e( 'I forgot my password.', 'booked' ); ?></a>
				<?php endif; ?>

			</div>

			<div class="field">
				<input name="submit" type="submit" class="button button-primary" value="<?php esc_html_e('Sign in', 'booked') ?>">
				<button class="cancel button"><?php esc_html_e('Cancel','booked'); ?></button>
			</div>
		</form>

		<?php if ( !is_multisite() ): ?>
			<form id="ajaxforgot" action="" method="post">
				<div class="cf-block" style="margin:0 0 5px;">

					<?php include(BOOKED_AJAX_INCLUDES_DIR . 'front/appointment-form/form-fields-forgot.php'); ?>

					<input type="hidden" name="action" value="booked_ajax_forgot">
					<?php wp_nonce_field( 'ajax_forgot_nonce', 'security' ); ?>

					<div class="field">
						<p class="status"></p>
					</div>

				</div>

				<div class="field">
					<input name="submit_forgot" type="submit" class="button button-primary" value="<?php esc_html_e('Reset Password', 'booked') ?>">
					<button class="booked-forgot-goback button"><?php esc_html_e('Go Back','booked'); ?></button>
				</div>
			</form>
		<?php endif; ?>

	</div>

<?php endif ?>

<?php // The booking form ?>
<div class="condition-block customer_choice<?php echo ( $guest_booking || get_option('users_can_register') && !is_user_logged_in() || is_user_logged_in() ? ' default' : '' ); ?>" id="condition-new">
	<form action="" method="post" id="newAppointmentForm">
		<input type="hidden" name="customer_type" value="<?php echo $customer_type; ?>" />
		<input type="hidden" name="action" value="booked_add_appt" />

		<?php if ( $is_user_logged_in ): ?>
			<input type="hidden" name="user_id" value="<?php echo $booked_current_user->ID; ?>" />
		<?php endif ?>

		<?php
		$error_message = '';

		// User limit reached
		if ( $reached_limit ) {
			$error_message = sprintf(_n("Sorry, but you've hit the appointment limit. Each user may only book %d appointment at a time.","Sorry, but you've hit the appointment limit. Each user may only book %d appointments at a time.", $appointment_limit, "booked" ), $appointment_limit);
		}

		// User limit not reached yet, however, the limit will be exceeded when booking the next appointments
		if ( $will_reached_limit && ! $reached_limit ) {
			$error_message = sprintf(esc_html__("Sorry, but you're about to book more appointments than you are allowed to book at a time. Each user may only book %d appointments at a time.", "booked" ), $appointment_limit);
		}

		// Print the error message, if any
		if ( $error_message ) {
			echo wpautop( $error_message );
		}

		// If there aren't any errors, and the user is logged in
		if ( $is_user_logged_in && ! $error_message ) {
			$msg = sprintf( _n( 'You are about to request an appointment for %s.', 'You are about to request appointments for %s.', $total_appts, 'booked' ), '<em>' . booked_get_name( $booked_current_user->ID ) . '</em>' ) . ' ' . _n( 'Please review and confirm that you would like to request the following appointment:', 'Please review and confirm that you would like to request the following appointments:', $total_appts, 'booked' );
			echo wpautop( $msg );
		}

		// If there aren't any errors, and the user isn't logged in
		if ( ! $is_user_logged_in && ! $error_message ) {
			$msg = _n( 'Please confirm that you would like to request the following appointment:', 'Please confirm that you would like to request the following appointments:', $total_appts, 'booked' );
			echo wpautop( $msg );
		}

		// If no errors, list the bookings
		if ( ! $error_message ) {
			// list calendars and their appointments
			include( BOOKED_AJAX_INCLUDES_DIR . 'front/appointment-form/bookings.php' );
		}

		if ( ! $is_user_logged_in && ! $error_message && class_exists('ReallySimpleCaptcha') ) : ?>
			<?php
			$rsc_url = WP_PLUGIN_URL . '/really-simple-captcha/';
			$captcha = new ReallySimpleCaptcha();
			$captcha->bg = array(245,245,245);
			$captcha->fg = array(150,150,150);
			$captcha_word = $captcha->generate_random_word(); //generate a random string with letters
			$captcha_prefix = mt_rand(); //random number
			$captcha_image = $captcha->generate_image($captcha_prefix, $captcha_word); //generate the image file. it returns the file name
			$captcha_file = rtrim(get_bloginfo('wpurl'), '/') . '/wp-content/plugins/really-simple-captcha/tmp/' . $captcha_image; //construct the absolute URL of the captcha image
			?>
			<p class="captcha">
				<label for="captcha_code"><?php esc_html_e('Please enter the following text:','booked'); ?></label>
				<img class="captcha-image" src="<?php echo $rsc_url ?>tmp/<?php echo $captcha_image ?>">
			</p>

			<div class="field">
				<input type="text" name="captcha_code" class="textfield large" value="" tabindex="104" />
				<input type="hidden" name="captcha_word" value="<?php echo $captcha_word; ?>" />
			</div>

			<br>
		<?php endif; ?>

		<div class="field">
			<p class="status"></p>
		</div>

		<div class="field">
			<?php if ( $error_message ): ?>
				<button class="cancel button"><?php esc_html_e('Okay','booked'); ?></button>
			<?php else: ?>
				<input type="submit" id="submit-request-appointment" class="button button-primary" value="<?php echo ( $new_appointment_default == 'draft' ? esc_html( _n( 'Request Appointment', 'Request Appointments', $total_appts, 'booked' ) ) : esc_html( _n( 'Book Appointment', 'Book Appointments', $total_appts, 'booked' ) ) ); ?>">
				<button class="cancel button"><?php esc_html_e('Cancel','booked'); ?></button>
			<?php endif; ?>
		</div>
	</form>
</div>
