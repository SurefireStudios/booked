<?php
if ( $field_type==='paid-service-label' ):
	$look_for_subs = 'paid-service';
	?>
	<li class="ui-state-default">
		<i class="main-handle fa-solid fa-bars"></i>
		<small><?php _e('Product Selector', 'booked'); ?></small>

		<p>
			<input class="cf-required-checkbox"<?php if ($is_required): echo ' checked="checked"'; endif; ?> type="checkbox" name="required---<?php echo $numbers_only; ?>" id="required---<?php echo $numbers_only; ?>">
			<label for="required---<?php echo $numbers_only; ?>"><?php _e('Required Field', 'booked'); ?></label>
		</p>

		<input type="text" name="<?php echo $name; ?>" value="<?php echo htmlentities($value, ENT_QUOTES | ENT_IGNORE, "UTF-8"); ?>" placeholder="<?php _e('Enter a label for this drop-down group...', 'booked'); ?>" />

		<ul id="booked-cf-paid-service">
<?php
endif;

return $look_for_subs;