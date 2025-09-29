<?php

/*
 * Reference : http://codex.wordpress.org/Settings_API
 * Reference : http://codex.wordpress.org/Function_Reference/add_settings_field
 */

add_filter('booked_admin_booked_screens', array('Booked_WC_Settings_Hooks', 'booked_admin_booked_screens'), 10, 1);

class Booked_WC_Settings {

	private function __construct() {

	}

	protected function _setup() {
		add_action('admin_init', array('Booked_WC_Settings_Fields', 'register') );
	}

	public static function setup() {
		$settings = new self();
		$settings->_setup();
	}

	public function render_payment_options_page() {
		Booked_WC_Fragments::load('settings', 'payment-options');
	}

	public static function get_option($option_name) {
		$options = get_option(BOOKED_WC_PLUGIN_PREFIX . 'payment_options');

		if ( isset($options[$option_name]) ) {
			return $options[$option_name];
		}
	}
}

class Booked_WC_Settings_Hooks {

	public static function booked_admin_booked_screens( $screens ) {
		$screens[] = BOOKED_WC_PLUGIN_PREFIX . 'payment_options';

		return $screens;
	}
}

class Booked_WC_Settings_Fields {

	protected $options_name;

	private function __construct() {
		$this->options_name = BOOKED_WC_PLUGIN_PREFIX . 'payment_options';
		$this->register_fields();
	}

	public static function register() {
		return new self();
	}

	protected function register_fields() {

		// Sets the defaults if settings aren't saved yet.
		$settings = get_option($this->options_name);
		if ( !$settings ) {
			$default_settings['email_confirmations'] = 'after_complete';
			$default_settings['enable_thumbnails'] = 'enable';
			$default_settings['enable_auto_cleanup'] = 'disable';
			$default_settings['cleanup_mode'] = 'twicedaily';
			update_option($this->options_name, $default_settings);
		}

		add_settings_section(
			$this->options_name,
			'',
			array($this, 'field_section'),
			$this->options_name
		);

		add_settings_field(
			'email_confirmations',
			__('Email Confirmations', 'booked'),
			array($this, 'email_confirmations'),
			$this->options_name,
			$this->options_name
		);

		add_settings_field(
			'enable_thumbnails',
			__('Enable Thumbnails', 'booked'),
			array($this, 'enable_thumbnails'),
			$this->options_name,
			$this->options_name
		);

		add_settings_field(
			'enable_auto_cleanup',
			__('Enable Auto-Cleanup', 'booked'),
			array($this, 'enable_auto_cleanup'),
			$this->options_name,
			$this->options_name
		);

		add_settings_field(
			'cleanup_mode',
			__('Cleanup Schedule', 'booked'),
			array($this, 'field_modes'),
			$this->options_name,
			$this->options_name
		);

		add_settings_field(
			'redirect_page',
			__('Redirect After Booking', 'booked'),
			array($this, 'redirect_page'),
			$this->options_name,
			$this->options_name
		);

		// Register our setting so that $_POST handling is done for us and
	 	// our callback function just has to echo the <input>
	 	register_setting($this->options_name, $this->options_name);
	}

	public function field_section() {
		//
	}

	public function email_confirmations() {
		$options = get_option($this->options_name);
		$field_value = isset($options['email_confirmations']) ? $options['email_confirmations'] : 'after_complete';

		$radio_options = array(
			'after_complete' => __('Send Booked confirmation emails ONLY after payment is marked as "complete".', 'booked'),
			'before_complete' => __('Send Booked confirmation emails right away.', 'booked')
		);
		?>
		<p><?php echo __('When do you want Booked to send out its confirmation emails?', 'booked') ?></p>
		<?php foreach ($radio_options as $option_value => $option_label): ?>
			<div>
				<input name="<?php echo $this->options_name ?>[email_confirmations]" type="radio" value="<?php echo $option_value ?>" <?php echo checked($field_value, $option_value) ?> />
				<p for="<?php echo $this->options_name ?>" style="display:inline-block; margin:0; padding:0; position:relative; top:-1px;"><?php echo $option_label ?></p>
			</div>
		<?php endforeach ?>
		<?php
	}

	public function enable_thumbnails() {
		$options = get_option($this->options_name);
		$field_value = isset($options['enable_thumbnails']) ? $options['enable_thumbnails'] : 'enable';

		$radio_options = array(
			'enable' => __('Enable', 'booked'),
			'disable' => __('Disable', 'booked')
		);
		?>
		<p><?php echo __('Enable this to show product thumbnails for Booked items (on the cart/checkout screens).', 'booked') ?></p>
		<?php foreach ($radio_options as $option_value => $option_label): ?>
			<div>
				<input name="<?php echo $this->options_name ?>[enable_thumbnails]" type="radio" value="<?php echo $option_value ?>" <?php echo checked($field_value, $option_value) ?> />
				<p for="<?php echo $this->options_name ?>" style="display:inline-block; margin:0; padding:0; position:relative; top:-1px;"><?php echo $option_label ?></p>
			</div>
		<?php endforeach ?>
		<?php
	}

	public function enable_auto_cleanup() {
		$options = get_option($this->options_name);
		$field_value = isset($options['enable_auto_cleanup']) ? $options['enable_auto_cleanup'] : 'enable';

		$radio_options = array(
			'enable' => __('Enable', 'booked'),
			'disable' => __('Disable', 'booked')
		);
		?>
		<p><?php echo __('Automatically cleans up all non-paid appointments at a specific time interval. For example, when someone chooses a day and product but does not pay for the appointment, then it will continue to be "booked" unless removed manually. This removes that step with an automated option.', 'booked') ?></p>
		<?php foreach ($radio_options as $option_value => $option_label): ?>
			<div>
				<input name="<?php echo $this->options_name ?>[enable_auto_cleanup]" type="radio" value="<?php echo $option_value ?>" <?php echo checked($field_value, $option_value) ?> />
				<p for="<?php echo $this->options_name ?>" style="display:inline-block; margin:0; padding:0; position:relative; top:-1px;"><?php echo $option_label ?></p>
			</div>
		<?php endforeach ?>
		<?php
	}

	public function redirect_page() {
		$options = get_option($this->options_name);
		$field_value = isset($options['redirect_page']) ? $options['redirect_page'] : 'checkout';

		$radio_options = array(
			'checkout' => __('Checkout Page (default)', 'booked'),
			'cart' => __('Cart Page', 'booked')
		);
		?>
		<p><?php echo __('You can choose to have Booked redirect to the Checkout page (default) or the Cart page instead.', 'booked') ?></p>
		<?php foreach ($radio_options as $option_value => $option_label): ?>
			<div>
				<input name="<?php echo $this->options_name ?>[redirect_page]" type="radio" value="<?php echo $option_value ?>" <?php echo ($option_value == 'checkout' && !$field_value ? 'checked="checked"' : checked($field_value, $option_value)); ?> />
				<p for="<?php echo $this->options_name ?>" style="display:inline-block; margin:0; padding:0; position:relative; top:-1px;"><?php echo $option_label ?></p>
			</div>
		<?php endforeach ?>
		<?php
	}

	public function field_modes() {
		$options = get_option($this->options_name);
		$field_value = isset($options['cleanup_mode']) ? $options['cleanup_mode'] : false;

		$available_schedules = wp_get_schedules();

		$schedules = array();
		foreach ($available_schedules as $name => $data) {
			$interval = (int)$data['interval'];
			if ( !isset($schedules[$interval]) ){
				$schedules[$interval] = array_merge(
					array('name' => $name),
					$data
				);
			}
		}

		usort($schedules, array($this, 'booked_schedule_sort'));

		?>
		<p><?php echo __('More schedule intervals can be added with a plugin like <a href="https://wordpress.org/plugins/wp-crontrol/" target="_blank">WP Crontrol</a>.', 'booked') ?> <?php echo __('The currently available schedules are listed below.', 'booked') ?></p>
		<?php foreach ($schedules as $index => $schedule_data): ?>
			<div>
				<input id="<?php echo $schedule_data['name'] ?>" name="<?php echo $this->options_name ?>[cleanup_mode]" type="radio" value="<?php echo $schedule_data['name'] ?>" <?php echo checked($field_value, $schedule_data['name']) ?> />
				<p for="<?php echo $schedule_data['name'] ?>" style="display:inline-block; margin:0; padding:0; position:relative; top:-1px;"><?php echo $schedule_data['display'] ?></p>
			</div>
		<?php endforeach ?>
		<?php
	}

	public function booked_schedule_sort($a, $b) {
		return $a['interval'] - $b['interval'];
	}

}
