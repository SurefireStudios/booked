<?php

global $error,$post;

$booked_current_user = wp_get_current_user();
$profile_username = $booked_current_user->user_login;
$my_id = $booked_current_user->ID;
$my_profile = true;

$user_data = get_user_by( 'id', $booked_current_user->ID );

?><div id="booked-profile-page"<?php if ($my_profile): ?> class="me"<?php endif; ?>><?php

if (empty($user_data)) {

	echo '<h2>' . esc_html__('No profile here!','booked') . '</h2>';
	echo '<p>' . esc_html__('Sorry, this user profile does not exist.','booked') . '</p>';

} else { ?>

	<?php
			
	$user_meta = get_user_meta($user_data->ID);
	$user_url = $user_data->data->user_url;
	$user_desc = $user_meta['description'][0];
	$h3_class = '';
			
	?>

	<div class="booked-profile-header bookedClearFix">

		<div class="booked-info">
			<div class="booked-user">
				<div class="booked-user-avatar"><?php echo booked_avatar($user_data->ID,50); ?></div>
				<h3 class="<?php echo $h3_class; ?>">
					<?php echo sprintf(esc_html__('Welcome back, %s!','booked'),'<strong>'.booked_get_name( $user_data->ID ).'</strong>'); ?>
					<?php if ($my_profile): ?>
						&nbsp;&nbsp;<a class="booked-logout-button" href="<?php echo wp_logout_url(get_permalink($post->ID)); ?>" title="<?php esc_html_e('Sign Out','booked'); ?>"><?php esc_html_e('Sign Out','booked'); ?></a>
					<?php endif; ?>
				</h3>
			</div>
		</div>

	</div>

	<ul class="booked-tabs bookedClearFix">
		<?php
			
			$default_tabs = array(
				'appointments' => array(
					'title' => esc_html__('Upcoming Appointments','booked'),
					'fa-icon' => 'calendar-days',
					'class' => false
				),
				'history' => array(
					'title' => esc_html__('Appointment History','booked'),
					'fa-icon' => 'calendar-check',
					'class' => false
				),
				'edit' => array(
					'title' => esc_html__('Edit Profile','booked'),
					'fa-icon' => 'pen-to-square',
					'class' => 'edit-button'
				)
			);
			
			echo apply_filters( 'booked_profile_tabs', $default_tabs );
		
		?>
	</ul>

	<?php $appointment_default_status = get_option('booked_new_appointment_default','draft');

	if ( is_user_logged_in() && $my_profile ) : ?>
	
		<?php echo apply_filters( 'booked_profile_tab_content',$default_tabs ); ?>

	<?php endif; ?>

<?php } ?>

</div>