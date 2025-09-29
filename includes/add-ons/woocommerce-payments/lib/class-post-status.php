<?php

class Booked_WC_Post_Status {

	private function __construct() {
		$this->register_status();

		add_action('admin_footer', array($this, 'append_post_status_list'));

		add_filter('display_post_states', array($this, 'display_post_states'));
	}

	public static function setup() {
		return new self();
	}

	protected function register_status() {
		register_post_status(
			BOOKED_WC_PLUGIN_PREFIX . 'awaiting',
			array(
				'label'                     => _x('Awaiting Payment', 'booked'),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop('Awaiting Payment <span class="count">(%s)</span>', 'Awaiting Payment <span class="count">(%s)</span>'),
			)
		);

		return $this;
	}

	public function append_post_status_list() {
		global $post;

		if( $post && $post->post_type===BOOKED_WC_POST_TYPE ) {
			$post_status = BOOKED_WC_PLUGIN_PREFIX . 'awaiting';
			$complete = '';
			$label = '';

			if( $post->post_status===$post_status ) {
				$complete = ' selected=\"selected\"';
				$label = '<span id=\"post-status-display\"> ' . __('Awaiting Payment', 'booked') . '</span>';
			}

			echo '
				<script type="text/javascript">
					jQuery(document).ready(function($){
						$("select#post_status").append("<option value=\"' . $post_status . '\" ' . $complete . '>' .  __('Awaiting Payment', 'booked') . '</option>");
						$(".misc-pub-section label").append("' . $label . '");
					});
				</script>
			';
		 }
	}

	public function display_post_states( $states ) {
		global $post;
		$arg = get_query_var( 'post_status' );
		$post_status = BOOKED_WC_PLUGIN_PREFIX . 'awaiting';

		if($arg !== $post_status && isset( $post->post_status ) && $post->post_status === $post_status) {
			return array(__('Awaiting Payment', 'booked'));
		}

		return $states;
	}
}
