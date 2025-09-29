<?php

add_action( 'widgets_init', 'booked_register_widgets' );

function booked_register_widgets(){
	register_widget( 'Booked_Calendar_Widget' );
}

class Booked_Calendar_Widget extends WP_Widget {

	public function __construct() {
		$widget_ops = array( 
			'classname' => 'booked_calendar',
			'description' => 'The Booked Calendar Widget',
		);
		parent::__construct( 'booked_calendar', esc_html__('Booked Calendar','booked'), $widget_ops );
	}
    
    function form($instance) {
	
	    $title = isset($instance['title']) ? esc_attr($instance['title']) : '';
	    $calendar = isset($instance['booked_calendar_chooser']) ? $instance['booked_calendar_chooser'] : 0;
	    $month = isset($instance['booked_calendar_month']) ? $instance['booked_calendar_month'] : 0;
	    $year = isset($instance['booked_calendar_year']) ? $instance['booked_calendar_year'] : 0;
	    
	    $args = array(
			'taxonomy'			=> 'booked_custom_calendars',
			'show_option_none' 	=> 'Default',
			'option_none_value'	=> 0,
			'hide_empty'		=> 0,
			'echo'				=> 0,
			'orderby'			=> 'name',
			'id'				=> $this->get_field_id('booked_calendar_chooser'),
			'name'				=> $this->get_field_name('booked_calendar_chooser'),
			'selected'			=> $calendar
		);

		if (!get_option('booked_hide_default_calendar')): $args['show_option_all'] = esc_html__('Default Calendar','booked'); endif;
	
	    ?>
	
		<p>
	      	<label for="<?php echo $this->get_field_id('title'); ?>"><?php esc_html_e('Widget Title','booked'); ?>:</label>
	      	<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
	    </p>
	    
	    <p class="booked-widget-col-13">
	      	<label><?php esc_html_e('Calendar to Display','booked'); ?>:</label><br>
	      	<?php echo str_replace( "\n", '', wp_dropdown_categories( $args ) ); ?>
	    </p>
	    
	    <?php $current_month = 0; ?>
	    
	    <p class="booked-widget-col-13">
	      	<label><?php esc_html_e('Month','booked'); ?>:</label><br>
	      	<select name="<?php echo $this->get_field_name('booked_calendar_month'); ?>">
		      	<?php do {
			      	echo '<option value="'.$current_month.'"'.($month == $current_month ? ' selected' : '').'>'.(!$current_month ? esc_html__('Current month') : date_i18n('F',strtotime('2016-'.$current_month.'-01'))).'</option>';
				  	$current_month++;
		      	} while ($current_month <= 12); ?>
	      	</select>
	    </p>
	    
	    <?php $current_year = date_i18n('Y'); $highest_year = $current_year + 25; ?>
	    
	    <p class="booked-widget-col-13">
	      	<label><?php esc_html_e('Year','booked'); ?>:</label><br>
	      	<select name="<?php echo $this->get_field_name('booked_calendar_year'); ?>">
		      	<option value="0"<?php if (!$year): ?> selected<?php endif; ?>><?php esc_html_e('Current year'); ?></option>
		      	<?php do {
			      	echo '<option value="'.$current_year.'"'.($year == $current_year ? ' selected' : '').'>'.$current_year.'</option>';
				  	$current_year++;
		      	} while ($current_year <= $highest_year); ?>
	      	</select>
	    </p>
	    
	    <?php
	}

    function widget($args, $instance) {
        
        extract( $args );

		// these are our widget options
		$widget_title = isset($instance['title']) ? $instance['title'] : false;
	    $title = apply_filters('widget_title', $widget_title);
	    $calendar = isset($instance['booked_calendar_chooser']) ? $instance['booked_calendar_chooser'] : false;
	    $month = isset($instance['booked_calendar_month']) ? $instance['booked_calendar_month'] : false;
	    $year = isset($instance['booked_calendar_year']) ? $instance['booked_calendar_year'] : false;
	
	    echo $before_widget;
	
		if ( $title ) {
			echo $before_title . $title . $after_title;
		}
		
		echo do_shortcode('[booked-calendar size="small"'.($calendar ? ' calendar="'.$calendar.'"' : '').($month ? ' month="'.$month.'"' : '').($year ? ' year="'.$year.'"' : '').']');
	    
	    echo $after_widget;
	
	}
	
    function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['booked_calendar_month'] = $new_instance['booked_calendar_month'];
		$instance['booked_calendar_year'] = $new_instance['booked_calendar_year'];
		$instance['booked_calendar_chooser'] = $new_instance['booked_calendar_chooser'];
		return $instance;
    }

}