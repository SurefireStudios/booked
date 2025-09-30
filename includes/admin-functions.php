<?php

function booked_new_tag($show_new_tags){
	if ($show_new_tags):
		echo '<span class="booked-new-tag">New</span>';
	endif;
	return false;
}

function booked_timeslots_select( $appt_id = false, $year, $month, $day = false ){

	if ( !$appt_id )
		return;

	// Caledar ID
	$calendars = get_the_terms( $appt_id, 'booked_custom_calendars' );
	if ( !empty($calendars) ):
		foreach( $calendars as $calendar ):
			$calendar_id = $calendar->term_id;
		endforeach;
	else:
		$calendar_id = false;
	endif;

	// Timeslot Information
	$time_format = get_option('time_format');

	$timeslot = get_post_meta($appt_id, '_appointment_timeslot',true);
	$timeslots = explode('-',$timeslot);
	$time_start = date_i18n($time_format,strtotime($timeslots[0]));
	$time_end = date_i18n($time_format,strtotime($timeslots[1]));

	if ($timeslots[0] == '0000' && $timeslots[1] == '2400'):
		$timeslotText = esc_html__('All day','overbooked');
	else :
		$timeslotText = $time_start.' '.esc_html__('to','overbooked').' '.$time_end;
	endif;

	$time_format = get_option('time_format');
	$full_date = $year . '-' . $month . ( $day ? '-' . $day : '' );
	$available_timeslots_array = booked_appointments_available( $year, $month, $day, $calendar_id, true, true );
	$available_timeslots_array = ( isset( $available_timeslots_array[$full_date] ) ? $available_timeslots_array[$full_date] : array() );

	echo '<select class="large textfield booked_appt_timeslot" name="appt_timeslot">';

		if ( !empty($available_timeslots_array) ):
			foreach( $available_timeslots_array as $_timeslot => $_available ):

				if ( $timeslot == $_timeslot ):
					$_available = '';
				elseif ( $_available > 0 ):
					$_available = ' (' . sprintf( _n( '%d Space Available', '%d Spaces Available', $_available, 'booked'), $_available ) . ')';
				else:
					$_available = ' [' . esc_html__('Full','overbooked') . ']';
				endif;

				$_timeslots = explode('-',$_timeslot);
				$_time_start = date_i18n($time_format,strtotime($_timeslots[0]));
				$_time_end = date_i18n($time_format,strtotime($_timeslots[1]));

				if ($_timeslots[0] == '0000' && $_timeslots[1] == '2400'):
					$_timeslotText = esc_html__('All day','overbooked');
				else :
					$_timeslotText = sprintf( esc_html__('%s to %s','overbooked'), $_time_start, $_time_end );
				endif;

				echo '<option value="' . $_timeslot . '"' . ($timeslot == $_timeslot ? ' selected="selected"' : '' ) . '>' . $_timeslotText . $_available . '</option>';

			endforeach;
		else:
			echo '<option value="' . $timeslot . '">' . $timeslotText . '</option>';
		endif;

	echo '</select>';

}

function booked_parse_readme_changelog( $readme_url = false, $title = false ){

	$readme = ( !$readme_url ? file_get_contents( BOOKED_PLUGIN_DIR . '/readme.txt') : file_get_contents( $readme_url ) );
	$readme = make_clickable(esc_html($readme));
	$readme = preg_replace( '/`(.*?)`/', '<code>\\1</code>', $readme);

	$readme = explode( '== Changelog ==', $readme );
	$readme = explode( '== Upgrade Notice ==', $readme[1] );
	$readme = $readme[0];

	$readme = preg_replace( '/\*\*(.*?)\*\*/', '<strong>\\1</strong>', $readme);
	$readme = preg_replace( '/\*(.*?)\*/', '<em>\\1</em>', $readme);

	$whats_new_title = '<h3>' . ( $title ? esc_html( $title ) : apply_filters( 'booked_whats_new_title', esc_html__( "What's new?", "booked" ) ) ) . '</h3>';
	$readme = preg_replace('/= (.*?) =/', $whats_new_title, $readme);
	$readme = preg_replace("/\*+(.*)?/i","<ul class='booked-whatsnew-list'><li>$1</li></ul>",$readme);
	$readme = preg_replace("/(\<\/ul\>\n(.*)\<ul class=\'booked-whatsnew-list\'\>*)+/","",$readme);
	$readme = explode( $whats_new_title, $readme );
	$readme = $whats_new_title . $readme[1];
	return $readme;
	

}

function booked_render_single_timeslot_form($timeslot_intervals,$type = false){

	ob_start();

	echo '<form id="single-timeslot-form" action="" method="post">';

		do_action( 'booked_single_timeslot_form_start' );

		echo booked_render_text_field('title', esc_html__('Title (optional)','overbooked'));
		echo booked_render_time_select('startTime',$timeslot_intervals,esc_html__('Start time ...','overbooked'),true);
		echo booked_render_time_select('endTime',$timeslot_intervals,esc_html__('End time ...','overbooked'));
		booked_render_count_select('count','How many?');

		do_action( 'booked_single_timeslot_form_end' );

	echo '</form>';

	$html = ob_get_clean();

	return $html;

}

function booked_render_bulk_timeslot_form($timeslot_intervals,$type = false){

	ob_start();

	echo '<form id="bulk-timeslot-form" action="" method="post">';

		do_action( 'booked_bulk_timeslot_form_start' );

		echo booked_render_text_field('title', esc_html__('Title (optional)','overbooked'));
		echo booked_render_time_select('startTime',$timeslot_intervals,esc_html__('Start time ...','overbooked'));
		echo booked_render_time_select('endTime',$timeslot_intervals,esc_html__('End time ...','overbooked'));
		booked_render_time_between_select('time_between',esc_html__('Time between ...','overbooked'));
		booked_render_interval_select('interval',esc_html__('Appt Length ...','overbooked'));
		booked_render_count_select('count',esc_html__('# of Each ...','overbooked'));

		do_action( 'booked_bulk_timeslot_form_end' );

	echo '</form>';

	$html = ob_get_clean();

	return $html;

}

function booked_render_time_select($select_name,$interval,$placeholder,$single = false){
	$time = 0;
	$interval = max(1, $interval);
	$time_format = get_option('time_format');

	$html = '<select name="'.$select_name.'">';
	$html .= '<option value="">'.$placeholder.'</option>';
		if ($single): $html .= '<option value="allday">'.esc_html__('All Day','overbooked').'</option>'; endif;
		do {
			$time_display = booked_convertTime($time);
			$html .= '<option value="'.date_i18n('Hi',strtotime('2014-01-01 '.$time_display)).'">'.date_i18n($time_format,strtotime('2014-01-01 '.$time_display)).'</option>';
			$time = $time + $interval;
		} while ($time < 1440);
		$html .= '<option value="2400">'.date_i18n($time_format,strtotime('2014-01-01 24:00')).' ('.esc_html__('night','overbooked').')</option>';
	$html .= '</select>';

	return apply_filters( 'booked_time_select_field', $html );
}

function booked_render_text_field($field_name,$placeholder){
	$html = '<input type="text" name="'.$field_name.'" placeholder="'.$placeholder.'" />';
	return apply_filters( 'booked_text_field', $html );
}

function booked_render_timeslots($calendar_id = false){

	if ($calendar_id):
		$booked_defaults = get_option('booked_defaults_'.$calendar_id);
	else :
		$booked_defaults = get_option('booked_defaults');
	endif;

	$time_format = get_option('time_format');
	$first_day_of_week = (get_option('start_of_week') == 0 ? 7 : 1);

	$day_loop = array(
		date_i18n( 'l', strtotime('Sunday') ),
		date_i18n( 'l', strtotime('Monday') ),
		date_i18n( 'l', strtotime('Tuesday') ),
		date_i18n( 'l', strtotime('Wednesday') ),
		date_i18n( 'l', strtotime('Thursday') ),
		date_i18n( 'l', strtotime('Friday') ),
		date_i18n( 'l', strtotime('Saturday') )
	);

	// Need to use the english three-letter day names to save settings properly
	$day_loop_english_array = array('Sun','Mon','Tue','Wed','Thu','Fri','Sat');

	if ($first_day_of_week == 1):
		$sunday_item = array_shift($day_loop); $day_loop[] = $sunday_item;
		$sunday_item_array = array_shift($day_loop_english_array); $day_loop_english_array[] = $sunday_item_array;
	endif;

	?><table class="booked-timeslots"<?php echo ($calendar_id ? ' data-calendar-id="'.$calendar_id.'"' : ''); ?>>
		<tr>
			<?php foreach($day_loop as $key => $day): ?>
			<td>
				<table>
					<thead>
						<tr>
							<?php
							echo '<th data-day="'.$day_loop_english_array[$key].'">';
								echo '<div>' . $day . '</div>';
								echo '<a href="#" class="booked-clear-timeslots button">' . esc_html__('Clear','overbooked') . '</a>';
								echo '<a href="#" class="booked-add-timeslot button">' . esc_html__('Add Timeslots','overbooked') . '</a>';
							echo '</th>';
							?>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td data-day="<?php echo $day_loop_english_array[$key]; ?>" class="addTimeslot"></td>
						</tr>
						<tr>
							<?php echo '<td class="dayTimeslots" data-day="'.$day_loop_english_array[$key].'">';
								if (!empty($booked_defaults[$day_loop_english_array[$key]])):
									ksort($booked_defaults[$day_loop_english_array[$key]]);
									foreach($booked_defaults[$day_loop_english_array[$key]] as $time => $count):
										echo booked_render_timeslot_info($time_format,$day_loop_english_array[$key],$time,$count,$calendar_id,$booked_defaults);
									endforeach;
								else :
									echo '<p><small>'.esc_html__('No time slots.','overbooked').'</small></p>';
								endif;
							echo '</td>'; ?>
						</tr>
					</tbody>
				</table>
			</td>
			<?php endforeach; ?>
		</tr>
	</table><?php

}

function booked_render_timeslot_info($time_format,$day,$time,$count,$calendar_id,$booked_defaults=array()){

	$title = isset($booked_defaults[$day.'-details'][$time]['title']) ? $booked_defaults[$day.'-details'][$time]['title'] : '';

	ob_start();

	echo '<span class="timeslot" data-timeslot="'.$time.'">';
		$time = explode('-',$time);

		do_action( 'booked_single_timeslot_start', $day, $time, $calendar_id );

		if ($time[0] == '0000' && $time[1] == '2400'):
			$timeslotText = '<span class="start">' . strtoupper( esc_html__( 'All day', 'booked') ) . '</span>';
		else :
			$timeslotText = '<span class="start">' . date_i18n( $time_format, strtotime( '2014-01-01 ' . $time[0] ) ) . '</span> &ndash; <span class="end">'.date_i18n($time_format,strtotime('2014-01-01 '.$time[1])).'</span>';
		endif;

		echo $timeslotText;
		echo '<span class="slotsBlock">';
			echo '<span class="changeCount minus" data-count="-1"><i class="fa-solid fa-circle-minus"></i></span>';
			echo '<span class="count"><em>'.$count.'</em> '._n('Space Available','Spaces Available',$count,'overbooked').'</span>';
			echo '<span class="changeCount add" data-count="1"><i class="fa-solid fa-circle-plus"></i></span>';
		echo '</span>';
		if ( $title ) {
			echo '<span class="booked_slot_title">'.esc_html($title).'</span>';
		}
		echo '<span class="delete"><i class="fa-solid fa-xmark"></i></span>';

		do_action( 'booked_single_timeslot_end', $day, $time, $calendar_id );

	echo '</span>';

	return ob_get_clean();

}

function booked_render_interval_select($select_name,$placeholder){

	ob_start();

	echo '<select name="'.$select_name.'">'; ?>
	<option value="60" selected><?php esc_html_e('Every 1 hour','overbooked'); ?></option>
	<option value="90"><?php esc_html_e('Every 1 hour, 30 minutes','overbooked'); ?></option>
	<option value="120"><?php esc_html_e('Every 2 hours','overbooked'); ?></option>
	<option value="45"><?php esc_html_e('Every 45 minutes','overbooked'); ?></option>
	<option value="30"><?php esc_html_e('Every 30 minutes','overbooked'); ?></option>
	<option value="20"><?php esc_html_e('Every 20 minutes','overbooked'); ?></option>
	<option value="15"><?php esc_html_e('Every 15 minutes','overbooked'); ?></option>
	<option value="10"><?php esc_html_e('Every 10 minutes','overbooked'); ?></option>
	<option value="5"><?php esc_html_e('Every 5 minutes','overbooked'); ?></option>
	<?php echo '</select>';

	echo apply_filters( 'booked_interval_select_field', ob_get_clean(), $select_name );

}

function booked_render_time_between_select($select_name,$placeholder){

	ob_start();

	echo '<select name="'.$select_name.'">'; ?>
	<option value="0" selected><?php echo $placeholder; ?></option>
	<option value="5"><?php esc_html_e('5 minutes','overbooked'); ?></option>
	<option value="10"><?php esc_html_e('10 minutes','overbooked'); ?></option>
	<option value="15"><?php esc_html_e('15 minutes','overbooked'); ?></option>
	<option value="20"><?php esc_html_e('20 minutes','overbooked'); ?></option>
	<option value="30"><?php esc_html_e('30 minutes','overbooked'); ?></option>
	<option value="45"><?php esc_html_e('45 minutes','overbooked'); ?></option>
	<option value="60"><?php esc_html_e('1 hour','overbooked'); ?></option>
	<?php echo '</select>';

	echo apply_filters( 'booked_time_between_select_field', ob_get_clean(), $select_name, $placeholder );

}

function booked_render_count_select($select_name,$placeholder){

	$total_spaces = 100;
	$counter = 0;

	echo '<select name="'.$select_name.'">';
		do {
			$counter++;
			echo '<option value="'.$counter.'"'.($counter == 1 ? ' selected="selected"' : '').'>'.sprintf(_n('%d space available','%d spaces available',$counter,'overbooked'),$counter).'</option>';
		} while ($counter < $total_spaces);
	echo '</select>';

}

function booked_admin_set_timezone(){
	$timezone_seconds = (int)get_site_option('gmt_offset') * 3600;
	$timezone_name = timezone_name_from_abbr(null, $timezone_seconds, true);
	date_default_timezone_set($timezone_name);
}

function booked_admin_calendar($year = false,$month = false,$calendar_id = false,$size = false){

	$local_time = current_time('timestamp');

	$year = ($year ? $year : date_i18n('Y',$local_time));
	$month = ($month ? $month : date_i18n('m',$local_time));
	$today = date_i18n('j',$local_time); // Defaults to current day
	$last_day = date_i18n('t',strtotime($year.'-'.$month));

	$monthShown = date_i18n($year.'-'.$month.'-01');
	$currentMonth = date_i18n('Y-m-01',$local_time);

	$first_day_of_week = (get_option('start_of_week') == 0 ? 7 : 1); 	// 1 = Monday, 7 = Sunday, Get from WordPress Settings

	$start_timestamp = strtotime($year.'-'.$month.'-01 00:00:00');
	$end_timestamp = strtotime($year.'-'.$month.'-'.$last_day.' 23:59:59');

	$args = array(
		'post_type' => 'booked_appointments',
		'posts_per_page' => 500,
		'post_status' => 'any',
		'meta_query' => array(
			array(
				'key'     => '_appointment_timestamp',
				'value'   => array( $start_timestamp, $end_timestamp ),
				'compare' => 'BETWEEN',
			)
		)
	);

	if ($calendar_id):
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'booked_custom_calendars',
				'field'    => 'term_id',
				'terms'    => $calendar_id,
			)
		);
	endif;

	$bookedAppointments = new WP_Query($args);
	if($bookedAppointments->have_posts()):
		while ($bookedAppointments->have_posts()):
			$bookedAppointments->the_post();
			global $post;
			$timestamp = get_post_meta($post->ID, '_appointment_timestamp',true);
			$day = date_i18n('j',$timestamp);
			$appointments_array[$day][$post->ID]['timestamp'] = $timestamp;
			$appointments_array[$day][$post->ID]['status'] = $post->post_status;
		endwhile;
		$appointments_array = apply_filters('booked_appointments_day_array', $appointments_array);
	endif;

	// Appointments Array
	// [DAY] => [POST_ID] => [TIMESTAMP/STATUS]

	if ($calendar_id):
		$calendar_name = get_term_by('id',$calendar_id,'booked_custom_calendars');
		$calendar_name = $calendar_name->name;
	else :
		$calendar_name = false;
	endif;

	$hide_weekends = get_option('booked_hide_weekends',false);

	?><table class="booked-calendar<?php echo ($size ? ' '.$size : ''); ?>"<?php echo ($calendar_id ? ' data-calendar-id="'.$calendar_id.'"' : ''); ?> data-monthShown="<?php echo $monthShown; ?>">
		<thead>
			<tr>
				<th colspan="<?php if (!$hide_weekends): ?>7<?php else: ?>5<?php endif; ?>">
					<a href="#" data-goto="<?php echo date_i18n('Y-m-01', strtotime("-1 month", strtotime($year.'-'.$month.'-01'))); ?>" class="page-left"><i class="fa-solid fa-angle-left"></i></a>
					<span class="calendarSavingState">
						<i class="fa-solid fa-circle-notch fa-spin"></i>
					</span>
					<span class="monthName">
						<?php if ($monthShown != $currentMonth): ?>
							<a href="#" class="backToMonth" data-goto="<?php echo $currentMonth; ?>"><i class="fa-solid fa-arrow-left"></i>&nbsp;&nbsp;&nbsp;<?php esc_html_e('Back to','overbooked'); ?> <?php echo date_i18n('F',strtotime($currentMonth)); ?></a>
						<?php endif; ?>
						<?php echo date_i18n("F Y", strtotime($year.'-'.$month.'-01')); ?>
						<?php if ($calendar_name): ?>
							<span class="calendarName"><i class="fa-solid fa-calendar-days"></i>&nbsp;&nbsp;&nbsp;<?php echo $calendar_name; ?></span>
						<?php endif; ?>
					</span>
					<a href="#" data-goto="<?php echo date_i18n('Y-m-01', strtotime("+1 month", strtotime($year.'-'.$month.'-01'))); ?>" class="page-right"><i class="fa-solid fa-angle-right"></i></a>
				</th>
			</tr>
			<tr class="days">
				<?php if ($first_day_of_week == 7 && !$hide_weekends): echo '<th>' . date_i18n( 'D', strtotime('Sunday') ) . '</th>'; endif; ?>
				<th><?php echo date_i18n( 'D', strtotime('Monday') ); ?></th>
				<th><?php echo date_i18n( 'D', strtotime('Tuesday') ); ?></th>
				<th><?php echo date_i18n( 'D', strtotime('Wednesday') ); ?></th>
				<th><?php echo date_i18n( 'D', strtotime('Thursday') ); ?></th>
				<th><?php echo date_i18n( 'D', strtotime('Friday') ); ?></th>
				<?php if (!$hide_weekends): ?><th><?php echo date_i18n( 'D', strtotime('Saturday') ); ?></th><?php endif; ?>
				<?php if ($first_day_of_week == 1 && !$hide_weekends): echo '<th>'. date_i18n( 'D', strtotime('Sunday') ) .'</th>'; endif; ?>
			</tr>
		</thead>
		<tbody><?php

			$today_date = date_i18n('Y',$local_time).'-'.date_i18n('m',$local_time).'-'.date_i18n('j',$local_time);
			$days = date_i18n("t",strtotime($year.'-'.$month.'-01'));		// Days in current month
			$lastmonth = date_i18n("t", mktime(0,0,0,$month-1,1,$year)); 	// Days in previous month

			$start = date_i18n("N", mktime(0,0,0,$month,1,$year)); 			// Starting day of current month
			if ($first_day_of_week == 7): $start = $start + 1; endif;
			if ($start > 7): $start = 1; endif;
			$finish = $days; 											// Finishing day of current month
			$laststart = $start - 1; 									// Days of previous month in calander

			$counter = 1;
			$nextMonthCounter = 1;

			if ($calendar_id):
				$booked_defaults = get_option('booked_defaults_'.$calendar_id);
				if (!$booked_defaults):
					$booked_defaults = get_option('booked_defaults');
				endif;
			else :
				$booked_defaults = get_option('booked_defaults');
			endif;

			$booked_defaults = booked_apply_custom_timeslots_filter($booked_defaults,$calendar_id);

			if($start > 5){ $rows = 6; } else { $rows = 5; }

			for($i = 1; $i <= $rows; $i++){
				echo '<tr class="week">';
				$day_count = 0;
				for($x = 1; $x <= 7; $x++){

					$classes = array();

					$appointments_count = 0;

					if(($counter - $start) < 0){

						$date = (($lastmonth - $laststart) + $counter);
						$classes[] = 'blur';
						$check_month = $month - 1;

					} else if(($counter - $start) >= $days){

						$date = $nextMonthCounter;
						$nextMonthCounter++;
						$classes[] = 'blur';
						$check_month = $month + 1;

						if ($day_count == 0): break; endif;

					} else {

						$check_month = $month;

						$date = ($counter - $start + 1);
						if($today == $counter - $start + 1){
							if ($today_date == $year.'-'.$month.'-'.$date):
								$classes[] = 'today';
							endif;
						}

						$day_name = date('D',strtotime($year.'-'.$month.'-'.$date));
						$full_count = (isset($booked_defaults[$day_name]) && !empty($booked_defaults[$day_name]) ? $booked_defaults[$day_name] : false);
						$total_full_count = 0;
						if ($full_count):
							foreach($full_count as $full_counter){
								$total_full_count = $total_full_count + $full_counter;
							}
						endif;

						if (isset($appointments_array[$date]) && !empty($appointments_array[$date])):
							$appointments_count = count($appointments_array[$date]);
							if ($appointments_count > 0 && $appointments_count < $total_full_count): $classes[] = 'partial';
							elseif ($appointments_count >= $total_full_count): $classes[] = 'booked'; endif;
						endif;

					}

					$check_year = $year;

					if ($check_month == 0):
						$check_month = 12;
						$check_year = $year - 1;
					elseif ($check_month == 13):
						$check_month = 1;
						$check_year = $year + 1;
					endif;

					$day_of_week = date_i18n('N',strtotime($check_year.'-'.$check_month.'-'.$date));

					if ($hide_weekends && $day_of_week == 6 || $hide_weekends && $day_of_week == 7):

						$html = '';

					else:

						$day_count++;

						echo '<td data-date="'.$check_year.'-'.$check_month.'-'.$date.'" class="'.implode(' ',$classes).'">';
							echo '<span class="date'.($appointments_count > 0 ? ' tooltip' : '').'" '.($appointments_count > 0 ? ' title="'.sprintf(_n('%d Appointment','%d Appointments',$appointments_count,'overbooked'),$appointments_count).'"' : '').'><span class="number">'. $date . '</span></span>';
						echo '</td>';

					endif;

					$counter++;
					$class = '';
				}
				echo '</tr>';
			} ?>
		</tbody>
	</table><?php

}

function booked_admin_calendar_date_content($date,$calendar_id = false){

	$calendars = get_terms('booked_custom_calendars','orderby=slug&hide_empty=0');
	$booked_current_user = wp_get_current_user();

	if (!empty($calendars)):
		$tabbed = true;
		if (!current_user_can('manage_booked_options')):
			$calendars = booked_filter_agent_calendars($booked_current_user,$calendars);
		endif;
	else :
		$tabbed = false;
	endif;

	echo '<div class="booked-appt-list">';

		$time_format = get_option('time_format');
		$date_format = get_option('date_format');

		/*
		Grab all of the appointments for this day
		*/

		if ($tabbed):

			?><ul id="bookedAppointmentTabs" class="bookedClearFix">
				<?php // Show the Default calendar to admins, not booking agents
				$is_first_tab = true;
				if ( !get_option('booked_hide_default_calendar') && current_user_can('manage_booked_options') ):
					$calendars = get_terms('booked_custom_calendars','orderby=slug&hide_empty=0');
					$appointments_in_tab = booked_get_admin_appointments($date,$time_format,$date_format,'default',false,$calendars);
					$total_appointments = (!empty($appointments_in_tab) ? count($appointments_in_tab) : 0);
					?><li<?php if (!$calendar_id): ?> class="active"<?php endif; ?>><a href="#calendar-default"><?php esc_html_e('Default Calendar','overbooked'); ?><?php echo ($total_appointments ? '<span>'.$total_appointments.'</span>' : ''); ?></a></li><?php
					$is_first_tab = false;
				endif;
				foreach($calendars as $calendar):
					$appointments_in_tab = booked_get_admin_appointments($date,$time_format,$date_format,$calendar->term_id);
					$total_appointments = (!empty($appointments_in_tab) ? count($appointments_in_tab) : 0);
					?><li<?php if ( $calendar_id == $calendar->term_id || !$calendar_id && $is_first_tab ): ?> class="active"<?php endif; ?>><a href="#calendar-<?php echo $calendar->term_id; ?>"><?php echo $calendar->name; ?><?php echo ($total_appointments ? '<span>'.$total_appointments.'</span>' : ''); ?></a></li><?php
					$is_first_tab = false;
				endforeach;
			?></ul><?php

			$tab_title = esc_html__('Appointments for','overbooked');
			$is_first_tab = true;
			if ( !get_option('booked_hide_default_calendar') && current_user_can('manage_booked_options') ):
				?><div id="bookedCalendarAppointmentsTab-default" class="bookedAppointmentTab<?php if (!$calendar_id): ?> active<?php endif; ?>"><?php
					booked_admin_calendar_date_loop($date,$time_format,$date_format,false,$tab_title,$tabbed,$calendars);
				?></div><?php
				$is_first_tab = false;
			endif;
			foreach($calendars as $calendar):
				?><div id="bookedCalendarAppointmentsTab-<?php echo $calendar->term_id; ?>" class="bookedAppointmentTab<?php if ( $calendar_id == $calendar->term_id || !$calendar_id && $is_first_tab): ?> active<?php endif; ?>"><?php
					$display_calendar_id = $calendar->term_id;
					$tab_title = sprintf(esc_html__('%s Appointments for','overbooked'),'<strong>'.$calendar->name.'</strong>');
					booked_admin_calendar_date_loop($date,$time_format,$date_format,$display_calendar_id,$tab_title,$tabbed,$calendars);
				?></div><?php
				$is_first_tab = false;
			endforeach;

		else :

			$tab_title = esc_html__('Appointments for','overbooked');
			booked_admin_calendar_date_loop($date,$time_format,$date_format,$calendar_id,$tab_title,false,$calendars);

		endif;

	echo '</div>';

}

function booked_get_admin_appointments($date,$time_format,$date_format,$calendar_id = false,$tabbed = false,$calendars = false){

	$year = date_i18n('Y',strtotime($date));
	$month = date_i18n('m',strtotime($date));
	$day = date_i18n('d',strtotime($date));

	$start_timestamp = strtotime( $year.'-'.$month.'-'.$day.' 00:00:00' );
	$end_timestamp = strtotime( $year.'-'.$month.'-'.$day.' 23:59:59' );

	$args = array(
		'post_type' => 'booked_appointments',
		'posts_per_page' => 500,
		'post_status' => 'any',
		'meta_query' => array(
			array(
				'key'     => '_appointment_timestamp',
				'value'   => array( $start_timestamp, $end_timestamp ),
				'compare' => 'BETWEEN'
			)
		)
	);

	if ($calendar_id && $calendar_id != 'default'):
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'booked_custom_calendars',
				'field'    => 'term_id',
				'terms'    => $calendar_id,
			)
		);
	elseif (!$calendar_id && $tabbed && !empty($calendars) || $calendar_id = 'default'):

		$not_in_calendar = array();

		foreach($calendars as $calendar_term){
            $not_in_calendar[] = $calendar_term->term_id;
        }

		$args['tax_query'] = array(
			array(
				'taxonomy' 			=> 'booked_custom_calendars',
				'field'    			=> 'term_id',
				'terms'            	=> $not_in_calendar,
				'include_children' 	=> false,
				'operator'         	=> 'NOT IN'
			)
		);

	endif;

	$appointments_array = array();

	$bookedAppointments = new WP_Query($args);
	if($bookedAppointments->have_posts()):
		while ($bookedAppointments->have_posts()):
			$bookedAppointments->the_post();
			global $post;
			$timestamp = get_post_meta($post->ID, '_appointment_timestamp',true);
			$timeslot = get_post_meta($post->ID, '_appointment_timeslot',true);
			$day = date_i18n('d',$timestamp);

			$guest_name = get_post_meta($post->ID, '_appointment_guest_name',true);
			$guest_surname = get_post_meta($post->ID, '_appointment_guest_surname',true);
			$guest_email = get_post_meta($post->ID, '_appointment_guest_email',true);

			$appointments_array[$post->ID]['post_id'] = $post->ID;
			$appointments_array[$post->ID]['timestamp'] = $timestamp;
			$appointments_array[$post->ID]['timeslot'] = $timeslot;
			$appointments_array[$post->ID]['status'] = $post->post_status;

			if (!$guest_name):
				$user_id = get_post_meta($post->ID, '_appointment_user',true);
				$appointments_array[$post->ID]['user'] = $user_id;
			else:
				$appointments_array[$post->ID]['guest_name'] = $guest_name;
				$appointments_array[$post->ID]['guest_surname'] = $guest_surname;
				$appointments_array[$post->ID]['guest_email'] = $guest_email;
			endif;

		endwhile;
		$appointments_array = apply_filters('booked_appointments_array', $appointments_array);
	endif;

	return $appointments_array;

}

function booked_admin_calendar_date_loop($date,$time_format,$date_format,$calendar_id = false,$tab_title,$tabbed = false,$calendars = false){

	$year = date_i18n('Y',strtotime($date));
	$month = date_i18n('m',strtotime($date));
	$day = date_i18n('d',strtotime($date));

	$date_display = date_i18n($date_format,strtotime($date));
	$day_name = date('D',strtotime($date));

	$appointments_array = booked_get_admin_appointments($date,$time_format,$date_format,$calendar_id,$tabbed,$calendars);

	/*
	Start the list
	*/

	do_action( 'booked_admin_calendar_date_loop_before_title', $date, $calendar_id );

	echo '<h2>'.$tab_title.' <strong>'.$date_display.'</strong></h2>';

	do_action( 'booked_admin_calendar_date_loop_after_title', $date, $calendar_id );

	/*
	Get today's default timeslots
	*/

	if ($calendar_id):
		$booked_defaults = get_option('booked_defaults_'.$calendar_id);
		if (!$booked_defaults):
			$booked_defaults = get_option('booked_defaults');
		endif;
	else :
		$booked_defaults = get_option('booked_defaults');
	endif;

	$formatted_date = date_i18n('Ymd',strtotime($date));
	$booked_defaults = booked_apply_custom_timeslots_details_filter($booked_defaults,$calendar_id);

	if (isset($booked_defaults[$formatted_date]) && !empty($booked_defaults[$formatted_date])):
		$todays_defaults = (is_array($booked_defaults[$formatted_date]) ? $booked_defaults[$formatted_date] : json_decode(html_entity_decode($booked_defaults[$formatted_date]),true));
		$todays_defaults_details = (is_array($booked_defaults[$formatted_date.'-details']) ? $booked_defaults[$formatted_date.'-details'] : json_decode(html_entity_decode($booked_defaults[$formatted_date.'-details']),true));
	elseif (isset($booked_defaults[$formatted_date]) && empty($booked_defaults[$formatted_date])):
		$todays_defaults = false;
		$todays_defaults_details = false;
	elseif (isset($booked_defaults[$day_name]) && !empty($booked_defaults[$day_name])):
		$todays_defaults = $booked_defaults[$day_name];
		$todays_defaults_details = ( isset($booked_defaults[$day_name]) ? $booked_defaults[$day_name.'-details'] : false );
	else :
		$todays_defaults = false;
		$todays_defaults_details = false;
	endif;

	/*
	There are timeslots available, let's loop through them
	*/

	if ($todays_defaults){

		ksort($todays_defaults);

		foreach($todays_defaults as $timeslot => $count):

			$appts_in_this_timeslot = array();

			/*
			Are there any appointments in this particular timeslot?
			If so, let's create an array of them.
			*/

			foreach($appointments_array as $post_id => $appointment):
				if ($appointment['timeslot'] == $timeslot):
					$appts_in_this_timeslot[] = $post_id;
				endif;
			endforeach;

			/*
			Calculate the number of spots available based on total minus the appointments booked
			*/

			$spots_available = $count - count($appts_in_this_timeslot);
			$spots_available = ($spots_available < 0 ? $spots_available = 0 : $spots_available = $spots_available);

			/*
			Display the timeslot
			*/

			$timeslot_parts = explode('-',$timeslot);

			$current_timestamp = current_time('timestamp');
			$this_timeslot_timestamp = strtotime($year.'-'.$month.'-'.$day.' '.$timeslot_parts[0]);

			if ($current_timestamp < $this_timeslot_timestamp){
				$available = true;
			} else {
				$available = false;
			}

			if ($timeslot_parts[0] == '0000' && $timeslot_parts[1] == '2400'):
				$timeslotText = esc_html__('All day','overbooked');
			else :
				$timeslotText = date_i18n($time_format,strtotime($timeslot_parts[0])).' &ndash; '.date_i18n($time_format,strtotime($timeslot_parts[1]));
			endif;

			$title = '';
			if ( !empty( $todays_defaults_details[$timeslot] ) ) {
				$title = !empty($todays_defaults_details[$timeslot]['title']) ? $todays_defaults_details[$timeslot]['title'] : '';
			}

			$is_disabled = booked_is_timeslot_disabled( $date,$timeslot,$calendar_id );

			echo '<div class="timeslot bookedClearFix' . ( $title ? ' has-title ' : '' ) . ( $is_disabled ? ' booked-disabled' : '' ) . '">';
				echo '<span class="timeslot-time">';
				if ( $title ) {
					echo '<span class="timeslot-title">' . esc_html($title) . '</span><br>';
				}
				echo '<i class="fa-solid fa-clock"></i>&nbsp;&nbsp;'.$timeslotText.'</span>';
				echo '<span class="timeslot-count">';

					do_action('booked_single_timeslot_in_list_start', $this_timeslot_timestamp, $timeslot, $calendar_id);

					echo '<span class="spots-available'.($spots_available == 0 ? ' empty' : '').'">'.$spots_available.' '._n('space','spaces',$spots_available,'overbooked').' '.esc_html__('available','overbooked').'</span>';

					/*
					Display the appointments set in this timeslot
					*/

					if (!empty($appts_in_this_timeslot)):

						$booked_appts = count($appts_in_this_timeslot);

						echo '<strong>'. sprintf( esc_html( _n('%d Appointment','%d Appointments',$booked_appts,'overbooked') ), $booked_appts ) . ':</strong>';

						foreach($appts_in_this_timeslot as $appt_id):

							if (!isset($appointments_array[$appt_id]['guest_name'])):
								$user_info = get_userdata($appointments_array[$appt_id]['user']);
								if (isset($user_info->ID)):
									if ($user_info->user_firstname):
										$user_display = '<a href="#" class="user" data-user-id="'.$appointments_array[$appt_id]['user'].'"><i class="fa-solid fa-pencil"></i>&nbsp;'.$user_info->user_firstname.($user_info->user_lastname ? ' '.$user_info->user_lastname : '').'</a>';
									elseif ($user_info->display_name):
										$user_display = '<a href="#" class="user" data-user-id="'.$appointments_array[$appt_id]['user'].'"><i class="fa-solid fa-pencil"></i>&nbsp;'.$user_info->display_name.'</a>';
									else :
										$user_display = '<a href="#" class="user" data-user-id="'.$appointments_array[$appt_id]['user'].'"><i class="fa-solid fa-pencil"></i>&nbsp;'.$user_info->user_login.'</a>';
									endif;
								else :
									$user_display = esc_html__('(this user no longer exists)','overbooked');
								endif;
							else :
								$user_display = '<a href="#" class="user" data-user-id="0"><i class="fa-solid fa-pencil"></i>&nbsp;'.$appointments_array[$appt_id]['guest_name'].' '.$appointments_array[$appt_id]['guest_surname'].'</a>';
							endif;

							$status = ($appointments_array[$appt_id]['status'] !== 'publish' && $appointments_array[$appt_id]['status'] !== 'future' ? 'pending' : 'approved');
							echo '<span class="appt-block" data-appt-id="'.$appt_id.'">';

								echo $user_display;

								do_action('booked_admin_calendar_buttons_before', $calendar_id, $appt_id, $status);

								if ( apply_filters('booked_admin_show_calendar_buttons', true) ) {
									echo '<a href="#" class="delete"'.($calendar_id ? ' data-calendar-id="'.$calendar_id.'"' : '').'><i class="fa-solid fa-xmark"></i></a>'.($status == 'pending' ? '<button data-appt-id="'.$appt_id.'" class="approve button button-primary">'.esc_html__('Approve','overbooked').'</button>' : '');
								}

								do_action('booked_admin_calendar_buttons_after', $calendar_id, $appt_id, $status);

							echo '</span>';
							unset($appointments_array[$appt_id]);

						endforeach;

					endif;

					do_action('booked_single_timeslot_in_list_end',$this_timeslot_timestamp,$timeslot,$calendar_id);

				echo '</span>';
				echo '<span class="timeslot-people">';
					echo '<button data-timeslot="'.$timeslot.'" data-title="'.esc_attr($title).'" data-date="'.$date.'"'.($calendar_id ? ' data-calendar-id="'.$calendar_id.'"' : '').' class="new-appt button button-primary"'.(!$spots_available ? ' disabled' : '').'>'.esc_html__('New Appointment','overbooked').'</button>';
					echo ( empty($appts_in_this_timeslot) ? '<button data-timeslot="'.$timeslot.'" data-title="'.esc_attr($title).'" data-date="'.$date.'"'.($calendar_id ? ' data-calendar-id="'.$calendar_id.'"' : '').' class="disable-slot button"'.(!$spots_available ? ' disabled' : '').'>' . ( $is_disabled ? esc_html__('Enable','overbooked') : esc_html__('Disable','overbooked') ) . '</button>' : '' );
				echo '</span>';
			echo '</div>';

		endforeach;

		/*
		Are there any additional appointments for this day that are not in the default timeslots?
		*/

		if (!empty($appointments_array)):

			echo '<span class="additional-timeslots">';
			echo '<br><p>'.esc_html__('There are additional appointments booked from previously available time slots:','overbooked').'</p>';
			foreach($appointments_array as $appointment):

				if (!isset($appointment['guest_name'])):
					$user_info = get_userdata($appointment['user']);
					if (isset($user_info->ID)):
						if ($user_info->user_firstname):
							$user_display = '<a href="#" class="user" data-user-id="'.$appointment['user'].'">'.$user_info->user_firstname.($user_info->user_lastname ? ' '.$user_info->user_lastname : '').'</a>';
						elseif ($user_info->display_name):
							$user_display = '<a href="#" class="user" data-user-id="'.$appointment['user'].'">'.$user_info->display_name.'</a>';
						else :
							$user_display = '<a href="#" class="user" data-user-id="'.$appointment['user'].'">'.$user_info->user_login.'</a>';
						endif;
					else :
						$user_display = esc_html__('(this user no longer exists)','overbooked');
					endif;
				else :
					$user_display = '<a href="#" class="user" data-user-id="0">'.$appointment['guest_name'].' '.$appointment['guest_surname'].'</a>';
				endif;

				$status = ($appointment['status'] !== 'publish' && $appointment['status'] !== 'future' ? 'pending' : 'approved');
				$timeslot = explode('-',$appointment['timeslot']);

				echo '<div class="timeslot bookedClearFix" data-appt-id="'.$appointment['post_id'].'">';
					echo '<span class="timeslot-time">'.date_i18n($time_format,strtotime($timeslot[0])).' &ndash; '.date_i18n($time_format,strtotime($timeslot[1])).'</span>';
					echo '<span class="timeslot-count count-wide">';
						echo '<span class="appt-block appt-no-padding" data-appt-id="'.$appointment['post_id'].'">';

							echo $user_display;

							do_action('booked_admin_calendar_buttons_before', $calendar_id, $appt_id, $status);

							if ( apply_filters('booked_admin_show_calendar_buttons', true) ) {
								echo '<a href="#" class="delete"'.($calendar_id ? ' data-calendar-id="'.$calendar_id.'"' : '').'><i class="fa-solid fa-xmark"></i></a>'.($status == 'pending' ? '<button data-appt-id="'.$appt_id.'" class="approve button button-primary">'.esc_html__('Approve','overbooked').'</button>' : '');
							}

							do_action('booked_admin_calendar_buttons_after', $calendar_id, $appt_id, $status);

						echo '</span>';
					echo '</span>';
				echo '</div>';

			endforeach;
			echo '</span>';

		endif;

	/*
	There are no default timeslots, however there are appointments booked.
	*/

	} else if (!$todays_defaults && !empty($appointments_array)) {

		echo '<span class="additional-timeslots">';
		echo '<p>'.esc_html__('There are no appointment slots available for this day, however there are appointments booked from previously available time slots:','overbooked').'</p>';
		foreach($appointments_array as $appointment):

			if (!isset($appointment['guest_name'])):
				$user_info = get_userdata($appointment['user']);
				if (isset($user_info->ID)):
					if ($user_info->user_firstname):
						$user_display = '<a href="#" class="user" data-user-id="'.$appointment['user'].'">'.$user_info->user_firstname.($user_info->user_lastname ? ' '.$user_info->user_lastname : '').'</a>';
					elseif ($user_info->display_name):
						$user_display = '<a href="#" class="user" data-user-id="'.$appointment['user'].'">'.$user_info->display_name.'</a>';
					else :
						$user_display = '<a href="#" class="user" data-user-id="'.$appointment['user'].'">'.$user_info->user_login.'</a>';
					endif;
				else :
					$user_display = esc_html__('(this user no longer exists)','overbooked');
				endif;
			else :
				$user_display = '<a href="#" class="user" data-user-id="0">'.$appointment['guest_name'].' '.$appointment['guest_surname'].'</a>';
			endif;

			$status = ($appointment['status'] !== 'publish' && $appointment['status'] !== 'future' ? 'pending' : 'approved');
			$timeslot = explode('-',$appointment['timeslot']);

			echo '<div class="timeslot bookedClearFix" data-appt-id="'.$appointment['post_id'].'">';
				echo '<span class="timeslot-time">'.date_i18n($time_format,strtotime($timeslot[0])).' &ndash; '.date_i18n($time_format,strtotime($timeslot[1])).'</span>';
				echo '<span class="timeslot-count count-wide">';

					echo '<span class="appt-block appt-no-padding" data-appt-id="'.$appointment['post_id'].'">';

						echo $user_display;

						do_action('booked_admin_calendar_buttons_before', $calendar_id, $appt_id, $status);

						if ( apply_filters('booked_admin_show_calendar_buttons', true) ) {
							echo '<a href="#" class="delete"'.($calendar_id ? ' data-calendar-id="'.$calendar_id.'"' : '').'><i class="fa-solid fa-xmark"></i></a>'.($status == 'pending' ? '<button data-appt-id="'.$appt_id.'" class="approve button button-primary">'.esc_html__('Approve','overbooked').'</button>' : '');
						}

						do_action('booked_admin_calendar_buttons_after', $calendar_id, $appt_id, $status);

					echo '</span>';

				echo '</span>';
			echo '</div>';

		endforeach;
		echo '</span>';

	/*
	There are no default timeslots and no appointments booked for this particular day.
	*/

	} else {
		echo '<p class="booked-none-available">'.esc_html__('There are no appointment time slots available for this day.','overbooked').' <a href="'.get_admin_url(null,'admin.php?page=booked-settings#defaults').'">'.esc_html__('Would you like to add some?','overbooked').'</a></p>';
	}

	do_action( 'booked_admin_calendar_date_loop_after_loop', $date, $calendar_id );

}

function booked_admin_calendar_date_square($date,$calendar_id = false){

	$local_time = current_time('timestamp');

	$year = date_i18n('Y',strtotime($date));
	$month = date_i18n('m',strtotime($date));
	$this_day = date_i18n('j',strtotime($date)); // Defaults to current day
	$last_day = date_i18n('t',strtotime($year.'-'.$month));

	$monthShown = date_i18n('Y-m-d',strtotime($year.'-'.$month.'-01'));
	$currentMonth = date_i18n('Y-m-01',$local_time);

	$first_day_of_week = (get_option('start_of_week') == 0 ? 7 : 1); 	// 1 = Monday, 7 = Sunday, Get from WordPress Settings

	$start_timestamp = strtotime($year.'-'.$month.'-01 00:00:00');
	$end_timestamp = strtotime($year.'-'.$month.'-'.$last_day.' 23:59:59');

	if ($calendar_id):
		$booked_defaults = get_option('booked_defaults_'.$calendar_id);
		if (!$booked_defaults):
			$booked_defaults = get_option('booked_defaults');
		endif;
	else :
		$booked_defaults = get_option('booked_defaults');
	endif;

	$args = array(
		'post_type' => 'booked_appointments',
		'posts_per_page' => 500,
		'meta_query' => array(
			array(
				'key'     => '_appointment_timestamp',
				'value'   => array( $start_timestamp, $end_timestamp ),
				'compare' => 'BETWEEN',
			)
		)
	);

	if ($calendar_id):
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'booked_custom_calendars',
				'field'    => 'term_id',
				'terms'    => $calendar_id,
			)
		);
	endif;

	$bookedAppointments = new WP_Query($args);
	if($bookedAppointments->have_posts()):
		while ($bookedAppointments->have_posts()):
			$bookedAppointments->the_post();
			global $post;
			$timestamp = get_post_meta($post->ID, '_appointment_timestamp',true);
			$day = date_i18n('j',$timestamp);
			$appointments_array[$day][$post->ID]['timestamp'] = $timestamp;
			$appointments_array[$day][$post->ID]['status'] = $post->post_status;
		endwhile;
		$appointments_array = apply_filters('booked_appointments_day_array', $appointments_array);
	endif;

	if ( !isset($_POST['inactive']) ):
		$classes[] = 'active';
	endif;

	$today_date = date_i18n('Y').'-'.date_i18n('m').'-'.date_i18n('j');
	if ($today_date == $_POST['date']):
		$classes[] = 'today';
	endif;

	$day_name = date('D',strtotime($date));
	$full_count = (isset($booked_defaults[$day_name]) && !empty($booked_defaults[$day_name]) ? $booked_defaults[$day_name] : false);
	$total_full_count = 0;
	if ($full_count):
		foreach($full_count as $full_counter){
			$total_full_count = $total_full_count + $full_counter;
		}
	endif;

	if (isset($appointments_array[$this_day]) && !empty($appointments_array[$this_day])):
		$appointments_count = count($appointments_array[$this_day]);
		if ($appointments_count > 0 && $appointments_count < $total_full_count): $classes[] = 'partial';
		elseif ($appointments_count >= $total_full_count): $classes[] = 'booked'; endif;
	endif;

	echo '<td data-date="'.$date.'" class="'.implode(' ',$classes).'">';
	echo '<span class="date'.(isset($appointments_count) && $appointments_count > 0 ? ' tooltip' : '').'"'.(isset($appointments_count) && $appointments_count > 0 ? ' title="'.sprintf(_n('%d Appointment','%d Appointments',$appointments_count,'overbooked'),$appointments_count).'"' : '').'><span class="number">'. $this_day . '</span></span>';
	echo '</td>';

}

function booked_render_custom_fields($calendar = false){

	?><form id="booked-cf-sortables-form">
		<ul id="booked-cf-sortables"><?php

			if (!$calendar):
				$custom_fields = json_decode(stripslashes(get_option('booked_custom_fields')),true);
			else:
				$custom_fields = json_decode(stripslashes(get_option('booked_custom_fields_'.$calendar)),true);
			endif;

			if (!empty($custom_fields)):

				$look_for_subs = false;
				$required_fields = [];

				foreach($custom_fields as $field):
				
					$field_parts = explode('---',$field['name']);
					$field_type = $field_parts[0];
					if ( $field_type == 'required' && isset( $field_parts[1] ) && isset( $field['value'] ) && $field['value'] ):
						$required_fields[] = $field_parts[1];
					endif;
				
				endforeach;
				
				foreach($custom_fields as $field):

					if ($look_for_subs):

						$field_type = explode('---',$field['name']);
						$field_type = $field_type[0];

						if ($field_type == 'single-checkbox'):

							?><li class="ui-state-default"><i class="sub-handle fa-solid fa-bars"></i>
								<input type="text" name="<?php echo $field['name']; ?>" value="<?php echo htmlentities($field['value'], ENT_QUOTES | ENT_IGNORE, "UTF-8"); ?>" placeholder="<?php esc_html_e('Enter a label for this checkbox...','overbooked'); ?>" />
								<span class="cf-delete"><i class="fa-solid fa-trash-can"></i></span>
							</li><?php

						elseif ($field_type == 'single-radio-button'):

							?><li class="ui-state-default"><i class="sub-handle fa-solid fa-bars"></i>
								<input type="text" name="<?php echo $field['name']; ?>" value="<?php echo htmlentities($field['value'], ENT_QUOTES | ENT_IGNORE, "UTF-8"); ?>" placeholder="<?php esc_html_e('Enter a label for this radio button...','overbooked'); ?>" />
								<span class="cf-delete"><i class="fa-solid fa-trash-can"></i></span>
							</li><?php

						elseif ($field_type == 'single-drop-down'):

							?><li class="ui-state-default"><i class="sub-handle fa-solid fa-bars"></i>
								<input type="text" name="<?php echo $field['name']; ?>" value="<?php echo htmlentities($field['value'], ENT_QUOTES | ENT_IGNORE, "UTF-8"); ?>" placeholder="<?php esc_html_e('Enter a label for this option...','overbooked'); ?>" />
								<span class="cf-delete"><i class="fa-solid fa-trash-can"></i></span>
							</li><?php

						elseif ($field_type == 'required'):

							// do nothing

						else :

							if ($look_for_subs == 'checkboxes'):

								?></ul>
								<button class="cfButton button" data-type="single-checkbox">+ <?php esc_html_e('Checkbox','overbooked'); ?></button>
								<span class="cf-delete"><i class="fa-solid fa-trash-can"></i></span>
							</li><?php

							elseif ($look_for_subs == 'radio-buttons'):

								?></ul>
								<button class="cfButton button" data-type="single-radio-button">+ <?php esc_html_e('Option','overbooked'); ?></button>
								<span class="cf-delete"><i class="fa-solid fa-trash-can"></i></span>
							</li><?php

							elseif ($look_for_subs == 'dropdowns'):

								?></ul>
								<button class="cfButton button" data-type="single-drop-down">+ <?php esc_html_e('Option','overbooked'); ?></button>
								<span class="cf-delete"><i class="fa-solid fa-trash-can"></i></span>
							</li><?php

							endif;

							$reset_subs = apply_filters(
								'booked_custom_fields_add_template_subs',
								$field_type,
								$field['name'],
								$field['value'],
								$look_for_subs
							);

							if ( $reset_subs ) {
								$look_for_subs = false;
							}

						endif;

					endif;

					$field_parts = explode('---',$field['name']);
					$field_type = $field_parts[0];
					$end_of_string = explode('___',$field_parts[1]);
					$numbers_only = $end_of_string[0];
					$is_required = in_array( $numbers_only, $required_fields );

					switch($field_type):

						case 'single-line-text-label' :

							?><li class="ui-state-default"><i class="main-handle fa-solid fa-bars"></i>
								<small><?php esc_html_e('Single Line Text','overbooked'); ?></small>
								<p><input class="cf-required-checkbox"<?php if ($is_required): echo ' checked="checked"'; endif; ?> type="checkbox" name="required---<?php echo $numbers_only; ?>" id="required---<?php echo $numbers_only; ?>"> <label for="required---<?php echo $numbers_only; ?>"><?php esc_html_e('Required Field','overbooked'); ?></label></p>
								<input type="text" name="<?php echo $field['name']; ?>" value="<?php echo htmlentities($field['value'], ENT_QUOTES | ENT_IGNORE, "UTF-8"); ?>" placeholder="<?php esc_html_e('Enter a label for this field...','overbooked'); ?>" />
								<span class="cf-delete"><i class="fa-solid fa-trash-can"></i></span>
							</li><?php

						break;

						case 'paragraph-text-label' :

							?><li class="ui-state-default"><i class="main-handle fa-solid fa-bars"></i>
								<small><?php esc_html_e('Paragraph Text','overbooked'); ?></small>
								<p><input class="cf-required-checkbox"<?php if ($is_required): echo ' checked="checked"'; endif; ?> type="checkbox" name="required---<?php echo $numbers_only; ?>" id="required---<?php echo $numbers_only; ?>"> <label for="required---<?php echo $numbers_only; ?>"><?php esc_html_e('Required Field','overbooked'); ?></label></p>
								<input type="text" name="<?php echo $field['name']; ?>" value="<?php echo htmlentities($field['value'], ENT_QUOTES | ENT_IGNORE, "UTF-8"); ?>" placeholder="<?php esc_html_e('Enter a label for this field...','overbooked'); ?>" />
								<span class="cf-delete"><i class="fa-solid fa-trash-can"></i></span>
							</li><?php

						break;

						case 'checkboxes-label' :

							?><li class="ui-state-default"><i class="main-handle fa-solid fa-bars"></i>
								<small><?php esc_html_e('Checkboxes','overbooked'); ?></small>
								<p><input class="cf-required-checkbox"<?php if ($is_required): echo ' checked="checked"'; endif; ?> type="checkbox" name="required---<?php echo $numbers_only; ?>" id="required---<?php echo $numbers_only; ?>"> <label for="required---<?php echo $numbers_only; ?>"><?php esc_html_e('Required Field','overbooked'); ?></label></p>
								<input type="text" name="<?php echo $field['name']; ?>" value="<?php echo htmlentities($field['value'], ENT_QUOTES | ENT_IGNORE, "UTF-8"); ?>" placeholder="<?php esc_html_e('Enter a label for this checkbox group...','overbooked'); ?>" />
								<ul id="booked-cf-checkboxes"><?php

							$look_for_subs = 'checkboxes';

						break;

						case 'radio-buttons-label' :

							?><li class="ui-state-default"><i class="main-handle fa-solid fa-bars"></i>
								<small><?php esc_html_e('Radio Buttons','overbooked'); ?></small>
								<p><input class="cf-required-checkbox"<?php if ($is_required): echo ' checked="checked"'; endif; ?> type="checkbox" name="required---<?php echo $numbers_only; ?>" id="required---<?php echo $numbers_only; ?>"> <label for="required---<?php echo $numbers_only; ?>"><?php esc_html_e('Required Field','overbooked'); ?></label></p>
								<input type="text" name="<?php echo $field['name']; ?>" value="<?php echo htmlentities($field['value'], ENT_QUOTES | ENT_IGNORE, "UTF-8"); ?>" placeholder="<?php esc_html_e('Enter a label for this radio button group...','overbooked'); ?>" />
								<ul id="booked-cf-radio-buttons"><?php

							$look_for_subs = 'radio-buttons';

						break;

						case 'drop-down-label' :

							?><li class="ui-state-default"><i class="main-handle fa-solid fa-bars"></i>
								<small><?php esc_html_e('Drop Down','overbooked'); ?></small>
								<p><input class="cf-required-checkbox"<?php if ($is_required): echo ' checked="checked"'; endif; ?> type="checkbox" name="required---<?php echo $numbers_only; ?>" id="required---<?php echo $numbers_only; ?>"> <label for="required---<?php echo $numbers_only; ?>"><?php esc_html_e('Required Field','overbooked'); ?></label></p>
								<input type="text" name="<?php echo $field['name']; ?>" value="<?php echo htmlentities($field['value'], ENT_QUOTES | ENT_IGNORE, "UTF-8"); ?>" placeholder="<?php esc_html_e('Enter a label for this drop-down group...','overbooked'); ?>" />
								<ul id="booked-cf-drop-down"><?php

							$look_for_subs = 'dropdowns';

						break;

						case 'plain-text-content' :

							?><li class="ui-state-default"><i class="main-handle fa-solid fa-bars"></i>
								<small><?php esc_html_e('Text Content','overbooked'); ?></small>
								<textarea name="<?php echo $field['name']; ?>"><?php echo htmlentities($field['value'], ENT_QUOTES | ENT_IGNORE, "UTF-8"); ?></textarea>
								<small class="help-text"><?php esc_html_e('HTML is allowed in this field.','overbooked'); ?></small>
								<span class="cf-delete"><i class="fa-solid fa-trash-can"></i></span>
							</li><?php

						break;

						default:
							$look_for_subs_action = apply_filters(
								'booked_custom_fields_add_template_main',
								false, // default value to return when there is no addon plugin to hook on it
								$field_type,
								$field['name'],
								$field['value'],
								$is_required,
								$look_for_subs,
								$numbers_only
							);
							$look_for_subs = $look_for_subs_action ? $look_for_subs_action : $look_for_subs;
						break;

					endswitch;

				endforeach;


				if ($look_for_subs):

					do_action('booked_custom_fields_add_template_subs_end', $field_type, $look_for_subs);

					if ($look_for_subs == 'checkboxes'):

						?></ul>
						<button class="cfButton button" data-type="single-checkbox">+ <?php esc_html_e('Checkbox','overbooked'); ?></button>
						<span class="cf-delete"><i class="fa-solid fa-trash-can"></i></span>
					</li><?php

					elseif ($look_for_subs == 'radio-buttons'):

						?></ul>
						<button class="cfButton button" data-type="single-radio-button">+ <?php esc_html_e('Radio Button','overbooked'); ?></button>
						<span class="cf-delete"><i class="fa-solid fa-trash-can"></i></span>
					</li><?php

					elseif ($look_for_subs == 'dropdowns'):

						?></ul>
						<button class="cfButton button" data-type="single-drop-down">+ <?php esc_html_e('Option','overbooked'); ?></button>
						<span class="cf-delete"><i class="fa-solid fa-trash-can"></i></span>
					</li><?php

					endif;

				endif;

			endif;
		?></ul>
	</form>

	<button class="cfButton button" data-type="single-line-text-label">+ <?php esc_html_e('Text Field','overbooked'); ?></button>&nbsp;
	<button class="cfButton button" data-type="paragraph-text-label">+ <?php esc_html_e('Paragraph Text','overbooked'); ?></button>&nbsp;
	<button class="cfButton button" data-type="checkboxes-label">+ <?php esc_html_e('Checkboxes','overbooked'); ?></button>&nbsp;
	<button class="cfButton button" data-type="radio-buttons-label">+ <?php esc_html_e('Radio Buttons','overbooked'); ?></button>&nbsp;
	<button class="cfButton button" data-type="drop-down-label">+ <?php esc_html_e('Drop Down','overbooked'); ?></button>&nbsp;
	<button class="cfButton button" data-type="plain-text-content">+ <?php esc_html_e('Text Content','overbooked'); ?></button>
	<?php do_action('booked_custom_fields_add_buttons');
}
