<?php

class Booked_WC_Response {

	protected $message = array();

	protected $status = 'error';

	protected $misc = array();

	public function __construct() {

	}

	public function erase_message() {
		$this->message = array();

		return $this;
	}

	public function add_message( $message='' ) {
		if ( !$message ) {
			return $this;
		}

		$this->message[] = $message;

		return $this;
	}

	public function set_status( $status=false ) {
		$this->status = $status===false ? 'error' : 'success';

		return $this;
	}

	public function create() {
		$output = array_merge(array(
			'status' => $this->status,
			'messages' => $this->message
		), $this->misc);

		exit(json_encode($output));
	}

	public function add_misc($property, $value) {
		$this->misc[$property] = $value;

		return $this;
	}
}