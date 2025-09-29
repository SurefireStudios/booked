<section class="wrap">
	<div class="booked-admin-calendar-notice-area"><h1 style="display:none;"></h1></div>
</section>
<section id="booked-plugin-page" class="wrap booked-admin-calendar-page-wrap">
	
	<?php

	$calendars = get_terms('booked_custom_calendars','orderby=slug&hide_empty=0');
	$booked_none_assigned = true;
	$default_calendar_id = false;
	$noTopBar = false;

	if (!empty($calendars)):

		if (!current_user_can('manage_booked_options')):

			$booked_current_user = wp_get_current_user();
			$calendars = booked_filter_agent_calendars($booked_current_user,$calendars);

			if (!empty($calendars)):
				$first_calendar = array_slice($calendars, 0, 1);
				$default_calendar_id = array_shift($first_calendar)->term_id;
				$booked_none_assigned = false;
			endif;

		else:
			$booked_none_assigned = false;
		endif;

		if (!$booked_none_assigned && count($calendars) >= 1):

			?><div class="booked-calendarSwitcher">
				<i class="fa-solid fa-calendar-days"></i><?php

				echo '<select name="bookedCalendarDisplayed">';
				if (current_user_can('manage_booked_options')): echo '<option value="">'.esc_html__('All Calendars','booked').'</option>'; endif;

				foreach($calendars as $calendar):

					?><option value="<?php echo $calendar->term_id; ?>"><?php echo $calendar->name; ?></option><?php

				endforeach;

				echo '</select>';

			?></div><?php

		else :

			$noTopBar = true;
			?><div class="noCalendarsSpacer"></div><?php

		endif;

	else :

		$noTopBar = true;
		?><div class="noCalendarsSpacer"></div><?php

	endif;

	if (!current_user_can('manage_booked_options') && $booked_none_assigned):

		echo '<div style="text-align:center;">';
			echo '<br><br><h3>'.esc_html__('There are no calendars assigned to you.','booked').'</h3>';
			echo '<p>'.esc_html__('Get in touch with the Administration of this site to get a calendar assigned to you.','booked').'</p>';
		echo '</div>';

	else:

		?><div class="booked-admin-calendar-wrap<?php echo ($noTopBar ? ' noTopBar' : ''); ?>">
			<?php booked_admin_calendar(false,false,$default_calendar_id); ?>
		</div><?php

	endif; ?>

</section>
