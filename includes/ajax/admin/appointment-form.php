<?php

$date = esc_html( $_POST['date'] );
$title = isset($_POST['title']) ? esc_html( $_POST['title'] ) : '';
$timeslot = esc_html( $_POST['timeslot'] );
$timeslot_parts = explode('-',$timeslot);

$date_format = get_option('date_format');
$time_format = get_option('time_format');

$user_array = booked_get_users();

$email_required = get_option('booked_require_guest_email_address',false);
$name_requirements = get_option('booked_registration_name_requirements',array('require_name'));
$name_requirements = ( isset($name_requirements[0]) ? $name_requirements[0] : false );

$calendar_id = (isset($_POST['calendar_id']) ? intval($_POST['calendar_id']) : false);
if ($calendar_id): $calendar_obj = get_term($calendar_id,'booked_custom_calendars'); $calendar_name = $calendar_obj->name; else: $calendar_name = ''; endif;

if ($timeslot_parts[0] == '0000' && $timeslot_parts[1] == '2400'):
	$timeslotText = esc_html__('All day','booked');
else :
	$timeslotText = date_i18n($time_format,strtotime($timeslot_parts[0])).' &ndash; '.date_i18n($time_format,strtotime($timeslot_parts[1]));
endif;

?>
<div class="booked-scrollable">

	<p class="booked-title-bar"><small><?php esc_html_e('New Appointment','booked'); ?></small></p>

	<?php if ($calendar_name): ?><p class="booked-calendar-name"><?php echo $calendar_name; ?></p><?php endif; ?>

	<p class="name"><b><i class="fa-solid fa-calendar-days"></i>&nbsp;&nbsp;<?php echo date_i18n($date_format, strtotime($date)); ?>&nbsp;&nbsp;&nbsp;&nbsp;<i class="fa-solid fa-clock"></i>&nbsp;&nbsp;<?php echo $timeslotText; ?></b></p>
	<form action="" method="post" class="booked-form" id="newAppointmentForm"<?php if ($calendar_id): echo ' data-calendar-id="'.$calendar_id.'"'; endif; ?>>

		<?php wp_nonce_field( 'ajax-admin-add-appt-nonce', 'nonce' ); ?>
		
		<input type="hidden" name="date" value="<?php echo date_i18n('Y-m-j', strtotime($date)); ?>" />
		<input type="hidden" name="timestamp" value="<?php echo strtotime($date.' '.$timeslot_parts[0]); ?>" />
		<input type="hidden" name="timeslot" value="<?php echo $timeslot; ?>" />

		<?php $guest_booking = (get_option('booked_booking_type','registered') == 'guest' ? true : false); ?>

		<div class="field">
			<input data-condition="customer_type" type="radio" name="customer_type" id="customer_current" value="current" checked> <label for="customer_current"><?php esc_html_e('Current Customer','booked'); ?></label>
		</div>
		<div class="field">
			<input data-condition="customer_type" type="radio" name="customer_type" id="customer_new" value="new"> <label for="customer_new"><?php esc_html_e('New Customer','booked'); ?></label>
		</div>

		<?php if ($guest_booking): ?>
			<div class="field">
				<input data-condition="customer_type" type="radio" name="customer_type" id="customer_guest" value="guest"> <label for="customer_guest"><?php esc_html_e('Guest','booked'); ?></label>
			</div>
		<?php endif; ?>

		<br>

		<div class="condition-block customer_type default" id="condition-current" data-condition-val="current">
			<div class="field">
				<select data-placeholder="<?php esc_html_e('Select a customer','booked'); ?>..." id="userList" name="user_id">
					<option></option>
					<?php foreach($user_array as $user): ?>
						<option value="<?php echo $user->ID; ?>"><?php echo booked_get_name($user->ID); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>

		<div class="condition-block customer_type" id="condition-new" data-condition-val="new">

			<?php if (isset($name_requirements) && $name_requirements == 'require_surname'): ?>
				<div class="field">
					<input value="" placeholder="<?php esc_html_e('First Name','booked'); ?>..." type="text" class="textfield" name="name" />
					<input value="" placeholder="<?php esc_html_e('Last Name','booked'); ?>..." type="text" class="textfield" name="surname" />
				</div>
			<?php else: ?>
				<div class="field">
					<input value="" placeholder="<?php esc_html_e('Name','booked'); ?>..." type="text" class="large textfield" name="name" />
				</div>
			<?php endif; ?>

			<div class="field">
				<input value="" placeholder="<?php esc_html_e('Email Address','booked'); ?>..." type="email" class="textfield" name="email" />
				<input value="" placeholder="<?php esc_html_e('Choose a password','booked'); ?>..." type="password" class="textfield" name="password" />
			</div>
		</div>

		<?php if ($guest_booking): ?>

			<div class="condition-block customer_type" id="condition-guest" data-condition-val="guest">

				<?php if (isset($name_requirements) && $name_requirements == 'require_surname'): ?>
					<div class="field">
						<input value="" placeholder="<?php esc_html_e('First Name','booked'); ?>..." type="text" class="textfield" name="guest_name" />
						<input value="" placeholder="<?php esc_html_e('Last Name','booked'); ?>..." type="text" class="textfield" name="guest_surname" />
					</div>
				<?php else: ?>
					<div class="field">
						<input value="" placeholder="<?php esc_html_e('Name','booked'); ?>..." type="text" class="large textfield" name="guest_name" />
					</div>
				<?php endif; ?>

				<?php if ( $email_required ): ?>
				<div class="field">
					<input value="" placeholder="<?php esc_html_e('Email Address','booked'); ?>" type="email" class="large textfield" name="guest_email" />
				</div>
				<?php endif; ?>

			</div>

		<?php endif; ?>

		<hr>

		<?php booked_custom_fields($calendar_id); ?>

		<input type="hidden" name="booked_form_type" value="admin" />
		<input type="hidden" name="action" value="booked_admin_add_appt" />
		<input type="hidden" name="calendar_id" value="<?php echo $calendar_id; ?>" />
		<input type="hidden" name="title" value="<?php echo esc_attr($title); ?>" />

		<div class="field">
			<input type="submit" class="button button-primary" value="<?php esc_html_e('Create Appointment','booked'); ?>">
			<button class="cancel button"><?php esc_html_e('Cancel','booked'); ?></button>
		</div>

	</form>
</div>

<?php echo '<a href="#" class="close"><i class="fa-solid fa-xmark"></i></a>';
