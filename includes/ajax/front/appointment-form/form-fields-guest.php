<?php

	$email_required = get_option('booked_require_guest_email_address',false);
	$name_requirements = get_option('booked_registration_name_requirements',array('require_name'));
	$name_requirements = ( isset($name_requirements[0]) ? $name_requirements[0] : false );

?>

<div class="field">
	<label class="field-label"><?php esc_html_e("Your Information:","booked"); ?><i class="required-asterisk fa-solid fa-asterisk"></i></label>
	<p class="field-small-p"><?php
		echo ( $email_required ? ( $name_requirements == 'require_surname' ? esc_html__('Please enter your first name, last name and email address:','overbooked') : esc_html__('Please enter your name and email address:','overbooked') ) : ( isset($name_requirements[0]) && $name_requirements[0] == 'require_surname' ? esc_html__('Please enter your first and last name:','overbooked') : esc_html__('Please enter your name:','overbooked') ) );
	?></p>
</div>

<?php if ( $name_requirements == 'require_surname' ): ?>
	<div class="field">
		<input value="" placeholder="<?php esc_html_e('First Name','overbooked'); ?>..." type="text" class="textfield" name="guest_name" />
		<input value="" placeholder="<?php esc_html_e('Last Name','overbooked'); ?>..." type="text" class="textfield" name="guest_surname" />
	</div>
<?php else: ?>
	<div class="field">
		<?php if ( $email_required ): ?>
			<input value="" placeholder="<?php esc_html_e('Name','overbooked'); ?>..." type="text" class="textfield" name="guest_name" />
			<input value="" placeholder="<?php esc_html_e('Email Address','overbooked'); ?>..." type="email" class="textfield" name="guest_email" />
		<?php else: ?>
			<input value="" placeholder="<?php esc_html_e('Name','overbooked'); ?>..." type="text" class="large textfield" name="guest_name" />
		<?php endif; ?>
	</div>
<?php endif; ?>

<?php if ( $email_required && $name_requirements == 'require_surname' ): ?>
	<div class="field">
		<input value="" placeholder="<?php esc_html_e('Email Address','overbooked'); ?>..." type="email" class="large textfield" name="guest_email" />
	</div>
<?php endif; ?>
