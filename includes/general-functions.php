<?php

function booked_avatar($user_id,$size = 150){
	if (get_user_meta($user_id, 'avatar',true)):
		if (wp_get_attachment_image( get_user_meta($user_id,'avatar',true), array($size,$size) )):
			return wp_get_attachment_image( get_user_meta($user_id,'avatar',true), array($size,$size) );
		else:
			return get_avatar($user_id, $size);
		endif;
	else :
		return get_avatar($user_id, $size);
	endif;
}

function booked_compress_css($css){

    // Remove tabs, spaces, newlines, etc.
    $css = str_replace( array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $css );

    return $css;

}

function booked_add_to_calendar_button($dates,$cf_meta_value){
	if (!get_option('booked_hide_google_link',false)):

		// Convert BRs to line breaks
		$cf_meta_value = preg_replace('#<br\s*?/?>#i', "\n", $cf_meta_value);

		wp_enqueue_script('booked-atc');

		?><div title="<?php esc_attr_e('Add to Calendar','booked'); ?>" class="addeventatc atc-style-booked google-cal-button">
		    <?php esc_html_e('Add to Calendar','booked'); ?>
		    <span class="start"><?php echo $dates['atc_date_startend']; ?> <?php echo $dates['atc_time_start']; ?></span>
		    <span class="end"><?php echo $dates['atc_date_startend_end']; ?> <?php echo $dates['atc_time_end']; ?></span>
		    <span class="timezone"><?php echo booked_get_timezone_string(); ?></span>
		    <span class="title"><?php echo sprintf(esc_html__('Appointment with %s','booked'),get_bloginfo('name')); ?></span>
		    <span class="description"><?php echo strip_tags(str_replace('<br>','
',$cf_meta_value)); ?></span>
		    <span class="location"><?php echo get_bloginfo('name'); ?></span>
		</div><?php

	endif;
}

function booked_get_timezone_string() {

    if ( $timezone = get_option( 'timezone_string' ) )
        return $timezone;

    if ( 0 === ( $utc_offset = get_option( 'gmt_offset', 0 ) ) )
        return 'UTC';

    $utc_offset *= 3600;

    if ( $timezone = timezone_name_from_abbr( '', $utc_offset, 0 ) ) {
        return $timezone;
    }

    $is_dst = date_i18n( 'I' );

    foreach ( timezone_abbreviations_list() as $abbr ) {
        foreach ( $abbr as $city ) {
            if ( $city['dst'] == $is_dst && $city['offset'] == $utc_offset )
                return $city['timezone_id'];
        }
    }

    return 'UTC';
}

function booked_get_profile_page(){

	global $wpdb;

	$shortcode = '[booked-profile';
	$query = "SELECT * FROM `{$wpdb->posts}` WHERE `post_status` != 'trash' AND `post_type` = 'page' AND `post_content` LIKE '%{$shortcode}%'";
	$pages = $wpdb->get_results($query);

	if (!$pages){
		return;
	}

	return $pages[0];

}

function booked_home_url(){
	if (function_exists('pll_home_url')):
		$home_url = rtrim(pll_home_url(), "/");
	else:
		$home_url = rtrim(home_url(), "/");
	endif;

	return $home_url;
}

function booked_get_name( $user_id, $part = 'full' ){
	switch ($part):
		case 'full':
			$username = get_user_meta( $user_id, 'first_name', true ) ? get_user_meta( $user_id, 'first_name', true ).(get_user_meta( $user_id, 'last_name', true ) ? ' '.get_user_meta( $user_id, 'last_name', true ) : '') : false;
			if (!$username):
				$user_info = get_userdata($user_id);
				if (!empty($user_info)): $username = $user_info->display_name; else: return false; endif;
			endif;
			if (!$username):
				$user_info = get_userdata($user_id);
				if (!empty($user_info)): $username = $user_info->user_login; else: return false; endif;
			endif;
			return $username;
		break;
		case 'first':
			$username = get_user_meta( $user_id, 'first_name', true ) ? get_user_meta( $user_id, 'first_name', true ) : false;
			if (!$username):
				$username = booked_get_name( $user_id );
			endif;
			return $username;
		break;
		case 'last':
			$username = get_user_meta( $user_id, 'last_name', true ) ? get_user_meta( $user_id, 'last_name', true ) : false;
			if (!$username):
				return '';
			endif;
			return $username;
		break;
	endswitch;
}

function booked_user_role()
{
	$booked_current_user = wp_get_current_user();
	$roles = $booked_current_user->roles;
	$role = array_shift($roles);
	return $role;
}

function booked_filter_agent_calendars($this_user,$calendars)
{

	$booked_current_user_email = $this_user->data->user_email;

	foreach($calendars as $key => $calendar):

		$term_meta = get_option( "taxonomy_".$calendar->term_id );
		$calendar_owner = $term_meta['notifications_user_id'];

		if ($calendar_owner != $booked_current_user_email):
			unset($calendars[$key]);
		endif;

	endforeach;

	return $calendars;

}

function booked_convertTime($time)
{
	settype($time, 'integer');
    if ($time < 1) {
        return;
    }
    $hours = lz(floor($time / 60));
    $minutes = lz(($time % 60));
    return $hours.':'.$minutes;
}

// lz = leading zero
function lz($num)
{
    return (strlen($num) < 2) ? "0{$num}" : $num;
}

function booked_pending_appts_count(){

	$calendars = get_terms('booked_custom_calendars','orderby=slug&hide_empty=0');
	if (!empty($calendars) && !current_user_can('manage_booked_options')):

		$booked_current_user = wp_get_current_user();
		$calendars = booked_filter_agent_calendars($booked_current_user,$calendars);

		if (!empty($calendars)):

			foreach($calendars as $calendar):
				$calendar_ids[] = $calendar->term_id;
			endforeach;

			$args = array(
			   'posts_per_page' => 500,
			   'post_status' => apply_filters('booked_admin_pending_post_status',array('draft')),
			   'post_type' => 'booked_appointments',
			   'tax_query' => array(
					array(
						'taxonomy' => 'booked_custom_calendars',
						'field'    => 'term_id',
						'terms'    => $calendar_ids,
					),
				),
			);

		else:
			return 0;
		endif;

	elseif (empty($calendars) && !current_user_can('manage_booked_options')):
		return 0;
	else:
		$args = array(
		   'posts_per_page' => 500,
		   'post_status' => apply_filters('booked_admin_pending_post_status',array('draft')),
		   'post_type' => 'booked_appointments',
		);
	endif;

	$pending_count_query = new WP_Query($args);
	return $pending_count_query->found_posts;

}

function booked_registration_validation( $email, $password, $captcha_value = false, $captcha_from_user = false )  {
	global $reg_errors;
	$reg_errors = new WP_Error;
	$errors = array();

	if ($captcha_value):
		if ($captcha_value != $captcha_from_user):
			$reg_errors->add('captcha', esc_html__('The text you\'ve entered does not match the image.','booked'));
		else :
			$captcha = new ReallySimpleCaptcha();
			$captcha->remove($captcha_value);
		endif;
	endif;

	if ( !$email || !$password ) {
	    $reg_errors->add('field', esc_html__('All fields are required to register.','booked'));
	}

	if ( username_exists( $email ) ) {
    	$reg_errors->add('user_name', esc_html__('That username already exists.','booked'));
    }

    if ( ! validate_username( $email ) ) {
	    $reg_errors->add( 'username_invalid', esc_html__('That name is not valid.','booked'));
	}

    if ( !is_email( $email ) ) {
	    $reg_errors->add( 'email_invalid', esc_html__('That email address is not valid.','booked'));
	}

	if ( email_exists( $email ) ) {
	    $reg_errors->add( 'email', esc_html__('That email is already in use.','booked'));
	}

	if ( is_wp_error( $reg_errors ) ) {

		foreach ( $reg_errors->get_error_messages() as $error ) {
	    	$errors[] = $error;
	    }

	}

	return $errors;

}

function booked_complete_registration() {
    global $reg_errors, $name, $surname, $display_name, $username, $password, $email;

    if ( 1 > count( $reg_errors->get_error_messages() ) ) {

        $userdata = array(
        	'user_login'    => $email,
			'user_email'    => $email,
			'user_pass'     => $password,
			'first_name'	=> $name,
			'last_name'		=> $surname
        );
        $user_id = wp_insert_user( $userdata );

        update_user_meta( $user_id, 'nickname', $display_name );
		wp_update_user( array ('ID' => $user_id, 'display_name' => $display_name ) );

        // Send a registration welcome email to the new user?
		$email_content = get_option('booked_registration_email_content');
		$email_subject = get_option('booked_registration_email_subject');
		if ($email_content && $email_subject):

			$token_replacements = array(
				'name' => $display_name,
				'username' => $email,
				'password' => $password,
				'email' => $email
			);

			$email_content = booked_token_replacement( $email_content,$token_replacements,'user' );
			$email_subject = booked_token_replacement( $email_subject,$token_replacements );

			do_action('booked_registration_email',$email, $email_subject, $email_content);

		endif;

        return '<p class="booked-form-notice"><strong>'.esc_html__('Success!','booked').'</strong><br />'.esc_html__('Registration complete, please check your email for login information.','booked').'</p>';

    } else {
	    return false;
    }
}

function booked_which_admin_to_send_email($calendar_id = false){

	$admin_email = false;

	if ($calendar_id):
		$term_meta = get_option( "taxonomy_$calendar_id" );
		$selected_value = $term_meta['notifications_user_id'];

		if ($selected_value):
			$admin_email = $selected_value;
		endif;
	endif;

	if (!$admin_email && get_option('booked_default_email_user')):
		$admin_email = get_option('booked_default_email_user');
	endif;

	if (!$admin_email):
		$admin_email = get_option( 'admin_email' );
	endif;

	return $admin_email;

}

function booked_registration_form($name, $surname, $email, $password){

	$name_requirements = get_option('booked_registration_name_requirements',array('require_name'));
	$name_requirements = ( isset($name_requirements[0]) ? $name_requirements[0] : false );

	?><form action="" method="post" class="wp-user-form">

		<p class="name">
			<label for="name"><?php echo ( $name_requirements == 'require_surname' ? esc_html__('First Name','booked') : esc_html__('Your Name','booked') ); ?></label>
			<input type="text" name="booked_reg_name" value="<?php echo ( isset( $name ) ? $name : null ); ?>" id="name" tabindex="101" />
		</p>
		<?php if ($name_requirements == 'require_surname'): ?>
		<p class="surname">
			<label for="surname"><?php esc_html_e('Last Name','booked'); ?></label>
			<input type="text" name="booked_reg_surname" value="<?php echo ( isset( $surname ) ? $surname : null ); ?>" id="surname" tabindex="102" />
		</p>
		<?php endif; ?>
		<p class="email">
			<label for="email"><?php esc_html_e('Email Address','booked'); ?></label>
			<input type="email" name="booked_reg_email" value="<?php echo ( isset( $email ) ? $email : null ); ?>" id="email" tabindex="103" />
		</p>
		<p class="password">
			<label for="password"><?php esc_html_e('Choose a Password','booked'); ?></label>
			<input type="password" name="booked_reg_password" value="<?php echo ( isset( $password ) ? $password : null ); ?>" id="password" tabindex="104" />
		</p>

		<?php if (class_exists('ReallySimpleCaptcha')) :

			?><p class="captcha">
				<label for="captcha_code"><?php esc_html_e('Please enter the following text:','booked'); ?></label><?php

				$rsc_url = WP_PLUGIN_URL . '/really-simple-captcha/';

		        $captcha = new ReallySimpleCaptcha();
		        $captcha->fg = array(150,150,150);
	            $captcha_word = $captcha->generate_random_word(); //generate a random string with letters
	            $captcha_prefix = mt_rand(); //random number
	            $captcha_image = $captcha->generate_image($captcha_prefix, $captcha_word); //generate the image file. it returns the file name
	            $captcha_file = rtrim(get_bloginfo('wpurl'), '/') . '/wp-content/plugins/really-simple-captcha/tmp/' . $captcha_image; //construct the absolute URL of the captcha image

		        echo '<img class="captcha-image" src="'.$rsc_url.'tmp/'.$captcha_image.'">';

		        ?><input type="text" name="captcha_code" value="" tabindex="104" />
			    <input type="hidden" name="captcha_word" value="<?php echo $captcha_word; ?>" />
			</p><?php

		endif; ?>

		<input type="submit" name="booked_reg_submit" value="<?php esc_html_e('Register','booked'); ?>" class="user-submit button-primary" tabindex="105" />

	</form><?php

}

/* Custom Time Slot Functions */
function booked_apply_custom_timeslots_filter($booked_defaults = false,$calendar_id = false){

	$custom_timeslots_array = array();
	$booked_custom_timeslots_encoded = get_option('booked_custom_timeslots_encoded');
	$booked_custom_timeslots_decoded = json_decode($booked_custom_timeslots_encoded,true);

	if (!empty($booked_custom_timeslots_decoded)):

		$custom_timeslots_array = booked_custom_timeslots_reconfigured($booked_custom_timeslots_decoded);
		foreach($custom_timeslots_array as $key => $value):

			if ($value['booked_custom_start_date']):

				$formatted_date = date_i18n('Ymd',strtotime($value['booked_custom_start_date']));
				$formatted_end_date = date_i18n('Ymd',strtotime($value['booked_custom_end_date']));

				// To include or not to include?
				if (!isset($value['booked_custom_calendar_id']) || $calendar_id && isset($value['booked_custom_calendar_id']) && $value['booked_custom_calendar_id'] == $calendar_id || !$calendar_id && !$value['booked_custom_calendar_id']){

					if (!$value['booked_custom_end_date']){
						// Single Date
						if ($value['vacationDayCheckbox']){
							// Time slots disabled
							$booked_defaults[$formatted_date] = array();
						} else {
							// Add time slots to this date
							$booked_defaults[$formatted_date] = $value['booked_this_custom_timelots'];
						}
					} else {
						// Multiple Dates
						$tempDate = $formatted_date;
						do {
							if ($value['vacationDayCheckbox']){
								// Time slots disabled
								$booked_defaults[$tempDate] = array();
							} else {
								// Add time slots to this date
								$booked_defaults[$tempDate] = $value['booked_this_custom_timelots'];
							}
							$tempDate = date_i18n('Ymd',strtotime($tempDate . ' +1 day'));
						} while ($tempDate <= $formatted_end_date);
					}

				}

			endif;

		endforeach;

	endif;

	return $booked_defaults;
}

/* Custom Time Slot Functions */
function booked_apply_custom_timeslots_details_filter($booked_defaults = false,$calendar_id = false){
	$custom_timeslots_array = array();
	$booked_custom_timeslots_encoded = get_option('booked_custom_timeslots_encoded');
	$booked_custom_timeslots_decoded = json_decode($booked_custom_timeslots_encoded,true);

	if (!empty($booked_custom_timeslots_decoded)):

		$custom_timeslots_array = booked_custom_timeslots_reconfigured($booked_custom_timeslots_decoded);

		foreach($custom_timeslots_array as $key => $value):

			if ($value['booked_custom_start_date']):

				$formatted_date = date_i18n('Ymd',strtotime($value['booked_custom_start_date']));
				$formatted_end_date = date_i18n('Ymd',strtotime($value['booked_custom_end_date']));

				// To include or not to include?
				if (!isset($value['booked_custom_calendar_id']) || $calendar_id && isset($value['booked_custom_calendar_id']) && $value['booked_custom_calendar_id'] == $calendar_id || !$calendar_id && !$value['booked_custom_calendar_id']){

					if (!$value['booked_custom_end_date']){
						// Single Date
						if ($value['vacationDayCheckbox']){
							// Time slots disabled
							$booked_defaults[$formatted_date] = array();
							$booked_defaults[$formatted_date.'-details'] = array();
						} else {
							// Add time slots to this date
							$booked_defaults[$formatted_date] = $value['booked_this_custom_timelots'];
							$booked_defaults[$formatted_date.'-details'] = !empty($value['booked_this_custom_timelots_details']) ? $value['booked_this_custom_timelots_details'] : array();
						}
					} else {
						// Multiple Dates
						$tempDate = $formatted_date;
						do {
							if ($value['vacationDayCheckbox']){
								// Time slots disabled
								$booked_defaults[$tempDate] = array();
								$booked_defaults[$tempDate.'-details'] = array();
							} else {
								// Add time slots to this date
								$booked_defaults[$tempDate] = $value['booked_this_custom_timelots'];
								$booked_defaults[$tempDate.'-details'] = !empty($value['booked_this_custom_timelots_details']) ? $value['booked_this_custom_timelots_details'] : array();
							}
							$tempDate = date_i18n('Ymd',strtotime($tempDate . ' +1 day'));
						} while ($tempDate <= $formatted_end_date);
					}

				}

			endif;

		endforeach;

	endif;

	return $booked_defaults;
}

function booked_custom_timeslots_reconfigured($booked_custom_timeslots_decoded){

	$total_fields = ( isset($booked_custom_timeslots_decoded['booked_custom_start_date']) && is_array($booked_custom_timeslots_decoded['booked_custom_start_date']) ? count( $booked_custom_timeslots_decoded['booked_custom_start_date'] ) - 1 : 0 );
	$custom_timeslots_array = array();
	$counter = 0;

	if ($total_fields):

		do {
			foreach($booked_custom_timeslots_decoded as $key => $values):
				if ($key == 'booked_this_custom_timelots' || $key == 'booked_this_custom_timelots_details'):
					$values = json_decode($values[$counter],true);
					$custom_timeslots_array[$counter][$key] = $values;
				else:
					$custom_timeslots_array[$counter][$key] = (isset($values[$counter]) ? $values[$counter] : $values);
				endif;
			endforeach;
			$counter++;
		} while($total_fields >= $counter);

	else :

		$custom_timeslots_array[0] = $booked_custom_timeslots_decoded;

	endif;

	return $custom_timeslots_array;

}
/* End Custom Time Slot Functions */
