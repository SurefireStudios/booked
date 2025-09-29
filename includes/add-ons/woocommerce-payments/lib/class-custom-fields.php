<?php

class Booked_WC_Custom_Fields {

	private function __construct() {

		if (
			(
				is_admin()
				&& isset($_GET['page'])
				&& $_GET['page'] !== 'booked-setting'
			) ||
			(
				!empty($_POST['action'])
				&& $_POST['action']==='booked_admin_load_full_customfields'
			)
		) {
			$this->admin_hooks();
		} else {
			$this->front_end_hooks();
		}
	}

	protected function admin_hooks() {
		// add Product Selector button
		add_action('booked_custom_fields_add_buttons', array($this, 'booked_custom_fields_add_buttons'));

		// add Product Selector button
		add_action('booked_custom_fields_add_template', array($this, 'booked_custom_fields_add_template'));

		// add prepopulated template with options
		add_filter('booked_custom_fields_add_template_main', array($this, 'booked_custom_fields_add_template_main'), 10, 7);
		add_filter('booked_custom_fields_add_template_subs', array($this, 'booked_custom_fields_add_template_subs'), 10, 4);

		// close the html if the last field has type booked appointment
		add_action('booked_custom_fields_add_template_subs_end', array($this, 'booked_custom_fields_add_template_subs_end'), 10, 2);
	}

	protected function front_end_hooks() {

		// add prepopulated template with options
		add_filter('booked_custom_fields_add_template_main', array($this, '_booked_custom_fields_add_template_main'), 10, 8);
		add_filter('booked_custom_fields_add_template_subs', array($this, '_booked_custom_fields_add_template_subs'), 10, 7);

		// close the html if the last field has type booked appointment
		add_action('booked_custom_fields_add_template_subs_end', array($this, '_booked_custom_fields_add_template_subs_end'), 10, 2);

	}

	public static function setup() {
		return new self();
	}

	# ------------------
	# Administration
	# ------------------

	public function booked_custom_fields_add_buttons() {
		Booked_WC_Fragments::load('booked-administration-fields/buttons');
	}

	public function booked_custom_fields_add_template() {
		Booked_WC_Fragments::load('booked-administration-fields/templates');
	}

	public function booked_custom_fields_add_template_subs($field_type='', $name='', $value='', $look_for_subs='') {
		$reset_subs = true;

		if ( $field_type==='single-paid-service' ) {
			$reset_subs = false;
			$template_path = Booked_WC_Fragments::get_path('booked-administration-fields/templates-subs');
			include($template_path);
		} else if ( $look_for_subs==='paid-service' ) {
			$this->booked_custom_fields_add_template_subs_end($field_type, $look_for_subs);
		}

		return $reset_subs;
	}

	public function booked_custom_fields_add_template_main($default_return=false, $field_type='', $name='', $value='', $is_required=false, $look_for_subs='', $numbers_only=0) {
		$template_path = Booked_WC_Fragments::get_path('booked-administration-fields/templates-main');
		$look_for_subs = include($template_path); // echo + return
		return $look_for_subs;
	}

	public function booked_custom_fields_add_template_subs_end($field_type='', $look_for_subs='') {
		$template_path = Booked_WC_Fragments::get_path('booked-administration-fields/templates-subs-end');
		include($template_path);
	}

	# ------------------
	# Front End
	# ------------------

	public function _booked_custom_fields_add_template_subs($field_type='', $name='', $value='', $is_required=false, $look_for_subs='', $numbers_only=0, $data_attributes='') {
		$reset_subs = true;

		if ( $field_type==='single-paid-service' ):
			$reset_subs = false;
			$template_path = Booked_WC_Fragments::get_path( 'booked-frontend-fields/templates-subs' );
			include( $template_path );
		elseif ( $look_for_subs==='paid-service' ):
			$this->_booked_custom_fields_add_template_subs_end($field_type, $look_for_subs);
		endif;

		return $reset_subs;
	}

	public function _booked_custom_fields_add_template_main($default_return=false, $field_type='', $name='', $value='', $is_required=false, $look_for_subs='', $numbers_only=0, $data_attributes='') {
		if ( $field_type === 'paid-service-label' ):
			$template_path = Booked_WC_Fragments::get_path('booked-frontend-fields/templates-main');
			include($template_path);
			$look_for_subs = 'paid-service';
		endif;
		return $look_for_subs;
	}

	public function _booked_custom_fields_add_template_subs_end($field_type='', $look_for_subs='') {
		$template_path = Booked_WC_Fragments::get_path('booked-frontend-fields/templates-subs-end');
		include($template_path);
	}
}
