<?php

do_action('booked_before_creating_appointment');

$date = isset($_POST['date']) ? esc_html( $_POST['date'] ) : '';
$title = isset($_POST['title']) ? esc_html( $_POST['title'] ) : '';
$timestamp = isset($_POST['timestamp']) ? esc_html( $_POST['timestamp'] ) : '';
$timeslot = isset($_POST['timeslot']) ? esc_html( $_POST['timeslot'] ) : '';
$customer_type = isset($_POST['customer_type']) ? esc_html( $_POST['customer_type'] ) : '';

$hide_end_times = get_option('booked_hide_end_times',false);

$calendar_id = (isset($_POST['calendar_id']) ? $_POST['calendar_id'] : false);
$calendar_id_for_cf = $calendar_id;
if ($calendar_id):
	$calendar_id = array($calendar_id);
	$calendar_id = array_map( 'intval', $calendar_id );
	$calendar_id = array_unique( $calendar_id );
endif;

$time_format = get_option('time_format');
$date_format = get_option('date_format');

// Get custom field data (new in v1.2)
$custom_fields = array();

if ( $calendar_id_for_cf ) {
	$custom_fields = json_decode(stripslashes(get_option('booked_custom_fields_'.$calendar_id_for_cf)),true);
}

if ( !$custom_fields ) {
	$custom_fields = json_decode(stripslashes(get_option('booked_custom_fields')),true);
}

$custom_field_data = array();
$cf_meta_value = '';

if (!empty($custom_fields)):

	$previous_field = false;

	foreach($custom_fields as $key => $field):

		$field_name = $field['name'];
		$field_title = $field['value'];

		$field_title_parts = explode('---',$field_name);
		if ($field_title_parts[0] == 'radio-buttons-label' || $field_title_parts[0] == 'checkboxes-label'):
			$current_group_name = $field_title;
		elseif ($field_title_parts[0] == 'single-radio-button' || $field_title_parts[0] == 'single-checkbox'):
			// Don't change the group name yet
		else :
			$current_group_name = $field_title;
		endif;

		if ($field_name != $previous_field){

			if (isset($_POST[$field_name]) && $_POST[$field_name]):

				$field_value = $_POST[$field_name];
				if (is_array($field_value)){
					$field_value = implode(', ',$field_value);
				}

				$custom_field_data[$key] = array(
					'label' => $current_group_name,
					'value' => $field_value
				);

			endif;

			$previous_field = $field_name;

		}

	endforeach;

	$custom_field_data = apply_filters('booked_custom_field_data', $custom_field_data);

	if (!empty($custom_field_data)):
		foreach($custom_field_data as $key => $data):
			$cf_meta_value .= '<p class="cf-meta-value"><strong>'.$data['label'].'</strong><br>'.$data['value'].'</p>';
		endforeach;
	endif;

endif;
// END Get custom field data

if ($customer_type == 'guest'):

	$name = esc_attr($_POST['guest_name']);
	$surname = isset($_POST['guest_surname']) ? esc_attr($_POST['guest_surname']) : false;
	$fullname = ( $surname ? $name . ' ' . $surname : $name );
	$email = isset($_POST['guest_email']) ? esc_attr($_POST['guest_email']) : false;
	$email_required = get_option('booked_require_guest_email_address',false);

	if ( $email_required && $email && is_email($email) && $name || !$email_required && !$email && $name):

		// Create a new appointment post for a current customer
		$new_post = apply_filters('booked_new_appointment_args', array(
			'post_title' => date_i18n($date_format,$timestamp).' @ '.date_i18n($time_format,$timestamp).' (User: Guest)',
			'post_content' => '',
			'post_status' => 'publish',
			'post_date' => date_i18n('Y',strtotime($date)).'-'.date_i18n('m',strtotime($date)).'-01 00:00:00',
			'post_type' => 'booked_appointments'
		));
		$post_id = wp_insert_post($new_post);

		update_post_meta($post_id, '_appointment_title', $title);
		update_post_meta($post_id, '_appointment_guest_name', $name);
		update_post_meta($post_id, '_appointment_guest_surname', $surname);
		update_post_meta($post_id, '_appointment_guest_email', $email);
		update_post_meta($post_id, '_appointment_timestamp', $timestamp);
		update_post_meta($post_id, '_appointment_timeslot', $timeslot);
		wp_publish_post($post_id);

		if (apply_filters('booked_update_cf_meta_value', true)) {
			update_post_meta($post_id, '_cf_meta_value', $cf_meta_value);
		}

		if (apply_filters('booked_update_appointment_calendar', true)) {
			if (isset($calendar_id) && $calendar_id): wp_set_object_terms($post_id,$calendar_id,'booked_custom_calendars'); endif;
		}

		$email_content = get_option('booked_approval_email_content');
		$email_subject = get_option('booked_approval_email_subject');

		if ($email && $email_content && $email_subject):

			$token_replacements = booked_get_appointment_tokens( $post_id );
			$email_content = booked_token_replacement( $email_content,$token_replacements );
			$email_subject = booked_token_replacement( $email_subject,$token_replacements );

			do_action( 'booked_approved_email', $email, $email_subject, $email_content );

		endif;

		echo 'success###'.$date;

		do_action('booked_new_appointment_created', $post_id);

	else:

		if ( !is_email($email) ):
			echo 'error###' . esc_html__( 'That email does not appear to be valid.','booked');
		endif;

	endif;

elseif ($customer_type == 'current'):

	$user_id = esc_html( $_POST['user_id'] );

	// Create a new appointment post for a current customer
	$new_post = apply_filters('booked_new_appointment_args', array(
		'post_title' => date_i18n($date_format,$timestamp).' @ '.date_i18n($time_format,$timestamp).' (User: '.$user_id.')',
		'post_content' => '',
		'post_status' => 'publish',
		'post_date' => date_i18n('Y',strtotime($date)).'-'.date_i18n('m',strtotime($date)).'-01 00:00:00',
		'post_author' => $user_id,
		'post_type' => 'booked_appointments'
	));
	$post_id = wp_insert_post($new_post);

	update_post_meta($post_id, '_appointment_title', $title);
	update_post_meta($post_id, '_appointment_timestamp', $timestamp);
	update_post_meta($post_id, '_appointment_timeslot', $timeslot);
	update_post_meta($post_id, '_appointment_user', $user_id);
	wp_publish_post($post_id);

	if (apply_filters('booked_update_cf_meta_value', true)) {
		update_post_meta($post_id, '_cf_meta_value', $cf_meta_value);
	}

	if (apply_filters('booked_update_appointment_calendar', true)) {
		if (isset($calendar_id) && $calendar_id): wp_set_object_terms($post_id,$calendar_id,'booked_custom_calendars'); endif;
	}

	// Send an email to the User?
	$user_data = get_userdata( $user_id );
	$email = $user_data->user_email;
	$email_content = get_option('booked_approval_email_content');
	$email_subject = get_option('booked_approval_email_subject');
	if ($email_content && $email_subject):

		$token_replacements = booked_get_appointment_tokens( $post_id );
		$email_content = booked_token_replacement( $email_content,$token_replacements );
		$email_subject = booked_token_replacement( $email_subject,$token_replacements );

		do_action( 'booked_approved_email', $email, $email_subject, $email_content );

	endif;

	echo 'success###'.$date;

	do_action('booked_new_appointment_created', $post_id);

else:

	$name = esc_attr($_POST['name']);
	$surname = ( isset($_POST['surname']) && $_POST['surname'] ? esc_attr($_POST['surname']) : false );
	$fullname = ( $surname ? $name . ' ' . $surname : $name );
	$email = esc_attr( $_POST['email'] );
	$password = ($_POST['password'] ? esc_attr( $_POST['password'] ) : wp_generate_password());

	$errors = booked_registration_validation($email,$password);

	if (empty($errors)):

		$userdata = array(
        	'user_login'    =>  $email,
			'user_email'    =>  $email,
			'user_pass'     =>  $password,
			'first_name'	=>	$name,
			'last_name'		=>	$surname
        );
        $user_id = wp_insert_user( $userdata );

        update_user_meta( $user_id, 'nickname', $name );
		wp_update_user( array ('ID' => $user_id, 'display_name' => $name ) );

        // Send a registration welcome email to the new user?
        $email_content = get_option('booked_registration_email_content');
		$email_subject = get_option('booked_registration_email_subject');
		if ($email_content && $email_subject):

			$token_replacements = array(
				'name' => $fullname,
				'email' => $email,
				'password' => $password
			);

			$email_content = booked_token_replacement( $email_content,$token_replacements,'user' );
			$email_subject = booked_token_replacement( $email_subject,$token_replacements,'user' );

			do_action( 'booked_registration_email',$email, $email_subject, $email_content );

		endif;

		// Create a new appointment post for this new customer
		$new_post = apply_filters('booked_new_appointment_args', array(
			'post_title' => date_i18n($date_format,$timestamp).' @ '.date_i18n($time_format,$timestamp).' (User: '.$user_id.')',
			'post_content' => '',
			'post_status' => 'publish',
			'post_date' => date_i18n('Y',strtotime($date)).'-'.date_i18n('m',strtotime($date)).'-01 00:00:00',
			'post_author' => $user_id,
			'post_type' => 'booked_appointments'
		));
		$post_id = wp_insert_post($new_post);

		update_post_meta($post_id, '_appointment_title', $title);
		update_post_meta($post_id, '_appointment_timestamp', $timestamp);
		update_post_meta($post_id, '_appointment_timeslot', $timeslot);
		update_post_meta($post_id, '_appointment_user', $user_id);
		wp_publish_post($post_id);

		if (apply_filters('booked_update_cf_meta_value', true)) {
			update_post_meta($post_id, '_cf_meta_value', $cf_meta_value);
		}

        if (apply_filters('booked_update_appointment_calendar', true)) {
			if (isset($calendar_id) && $calendar_id): wp_set_object_terms($post_id,$calendar_id,'booked_custom_calendars'); endif;
		}

		// Send an email to the user?
		$email_content = get_option('booked_approval_email_content');
		$email_subject = get_option('booked_approval_email_subject');

		if ($email_content && $email_subject):

			$token_replacements = booked_get_appointment_tokens( $post_id );
			$email_content = booked_token_replacement( $email_content,$token_replacements );
			$email_subject = booked_token_replacement( $email_subject,$token_replacements );

			do_action( 'booked_approved_email',$email, $email_subject, $email_content );

		endif;

		echo 'success###'.$date;

		do_action('booked_new_appointment_created', $post_id);

	else :
		echo 'error###'.implode('
',$errors);
	endif;

endif;
