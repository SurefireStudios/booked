<?php

class Booked_WC_Product {

	private static $products = array();

	public $data;
	public $post_id;
	public $type;
	public $title;
	public $currency;
	public $variations=array();

	private function __construct( $post_id ) {

		if ( function_exists( 'icl_object_id' ) ):
			$post_id = icl_object_id( $post_id, 'product', true );
		elseif ( function_exists( 'pll_get_post' ) ):
			$post_id = pll_get_post( $post_id, 'product', true );
		endif;

		$this->post_id = $post_id;
		$this->currency = get_woocommerce_currency_symbol();
		$this->get_data();
	}

	public static function get( $post_id = null ) {

		if ( !is_integer( $post_id ) ) {
			$message = sprintf( __('%s integer expected when %s given.', 'booked'), 'Booked_WC_Product::get($post_id)', gettype($post_id) );
			throw new Exception($message);
		} else if ( $post_id === 0 ) {
			self::$products[$post_id] = false;
		} else if ( !isset(self::$products[$post_id]) ) {
			self::$products[$post_id] = new self($post_id);
		}

		return self::$products[$post_id];
	}

	protected function get_data() {
		$this->_get_product_data();
		$this->_get_price();
		$this->_get_type();
		$this->_get_title();
		$this->_get_variations();
	}

	protected function _get_product_data() {
		$this->data = wc_get_product($this->post_id);

		if ( !$this->data ) {
			//$message = sprintf(__('An error has occur while retrieving product data for product with ID %1$d.', 'booked'), $this->post_id);
			//throw new Exception($message);
		}

		return $this;
	}

	protected function _get_price() {
		$this->data->get_price();
		return $this;
	}

	protected function _get_type() {
		$this->type = $this->data->get_type();
		return $this;
	}

	protected function _get_title() {

		$booked_wc_currency_symbol = get_woocommerce_currency_symbol();
		$booked_wc_currency_position = get_option( 'woocommerce_currency_pos','left' );

		if ( $this->type === 'variable' ) {
			$this->title = $this->data->get_name();
		} else {
			$this_price = ( $this->data->get_price() ? $this->data->get_price() : '0' );
			echo '<!-- ' . $this->data->get_name() . ' -->';
			switch ( $booked_wc_currency_position ) {
				case 'left' :
					$this->title = $booked_wc_currency_symbol . $this_price . ' - ' . $this->data->get_name();
				break;
				case 'right' :
				 	$this->title = $this_price . $booked_wc_currency_symbol . ' - ' . $this->data->get_name();
				break;
				case 'left_space' :
				  	$this->title = $booked_wc_currency_symbol . ' ' . $this_price . ' - ' . $this->data->get_name();
				break;
				case 'right_space' :
				  	$this->title = $this_price . ' ' . $booked_wc_currency_symbol . ' - ' . $this->data->get_name();
				break;
			}
		}

		return $this;
	}

	protected function _get_variations() {
		if ( $this->type==='variable' ) {

			add_filter('woocommerce_available_variation', array('Booked_WC_Variation', 'woocommerce_available_variation'), 10, 3);
			$product_variations = $this->data->get_available_variations();
			remove_filter('woocommerce_available_variation', array('Booked_WC_Variation', 'woocommerce_available_variation'));

			// use variation IDs as keys for their values
			$variations = array();
			foreach ($product_variations as $variation_data) {
				$vid = ( isset($variation_data['variation_id']) && $variation_data['variation_id'] ? $variation_data['variation_id'] : $variation_data['id'] );
				$variations[$vid] = $variation_data;
			}
			$this->variations = $variations;
		}

		return $this;
	}
}
