<div class="field">
	<label class="field-label"><?php esc_html_e("Registration:","booked"); ?><i class="required-asterisk fa-solid fa-asterisk"></i></label>
	<p class="field-small-p"><?php esc_html_e('Please enter your name, your email address and choose a password to get started.','booked'); ?></p>
</div>

<?php
	$name_requirements = get_option('booked_registration_name_requirements',array('require_name'));
	$name_requirements = ( isset($name_requirements[0]) ? $name_requirements[0] : false );
?>

<?php if ( $name_requirements == 'require_surname' ): ?>
	<div class="field">
		<input value="" placeholder="<?php esc_html_e('First Name','booked'); ?>..." type="text" class="textfield" name="booked_appt_name" />
		<input value="" placeholder="<?php esc_html_e('Last Name','booked'); ?>..." type="text" class="textfield" name="booked_appt_surname" />
	</div>
<?php else: ?>
	<div class="field">
		<input value="" placeholder="<?php esc_html_e('Name','booked'); ?>..." type="text" class="large textfield" name="booked_appt_name" />
	</div>
<?php endif; ?>

<div class="field">
	<input value="" placeholder="<?php esc_html_e('Email Address','booked'); ?>..." type="email" class="textfield" name="booked_appt_email" />
	<input value="" placeholder="<?php esc_html_e('Choose a password','booked'); ?>..." type="password" class="textfield" name="booked_appt_password" />
</div>
