<?php

class Booked_WC_Fragments {

	protected $slug;

	protected $name;

	private function __construct($slug, $name) {
		$this->slug = $slug;
		$this->name = (string) $name;

		$this->_set_path();
	}

	protected function _set_path() {
		if ( $this->name !== '' ) {
			$template_path = "{$this->slug}-{$this->name}.php";
		} else {
			$template_path = "{$this->slug}.php";
		}

		$this->template_path = BOOKED_WC_PLUGIN_DIR . 'fragments/' . $template_path;

		return $this;
	}

	protected function _get_path() {
		return $this->template_path;
	}

	public static function load($slug, $name=null) {
		$fragments = new self($slug, $name);

		include($fragments->_get_path());
	}

	public static function get_path($slug, $name=null) {
		$fragments = new self($slug, $name);

		return $fragments->_get_path();
	}
}