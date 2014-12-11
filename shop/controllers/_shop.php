<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Name:		NALS_SHOP_Controller
 *
 * Description:	This controller executes various bits of common Shop functionality
 *
 **/


class NAILS_Shop_Controller extends NAILS_Controller
{
	protected $_skin_front;
	protected $_skin_checkout;


	// --------------------------------------------------------------------------


	public function __construct()
	{
		parent::__construct();

		// --------------------------------------------------------------------------

		//	Check this module is enabled in settings
		if ( ! module_is_enabled( 'shop' ) ) :

			//	Cancel execution, module isn't enabled
			show_404();

		endif;

		// --------------------------------------------------------------------------

		//	Load language file
		$this->lang->load( 'shop' );

		// --------------------------------------------------------------------------

		//	Load the models
		$this->load->model( 'shop/shop_model' );
		$this->load->model( 'shop/shop_basket_model' );
		$this->load->model( 'shop/shop_brand_model' );
		$this->load->model( 'shop/shop_category_model' );
		$this->load->model( 'shop/shop_collection_model' );
		$this->load->model( 'shop/shop_currency_model' );
		$this->load->model( 'shop/shop_order_model' );
		$this->load->model( 'shop/shop_product_model' );
		$this->load->model( 'shop/shop_product_type_model' );
		$this->load->model( 'shop/shop_range_model' );
		$this->load->model( 'shop/shop_shipping_driver_model' );
		$this->load->model( 'shop/shop_sale_model' );
		$this->load->model( 'shop/shop_tag_model' );
		$this->load->model( 'shop/shop_voucher_model' );
		$this->load->model( 'shop/shop_skin_front_model' );
		$this->load->model( 'shop/shop_skin_checkout_model' );

		// --------------------------------------------------------------------------

		//	"Front of house" Skin
		$_skin = app_setting( 'skin_front', 'shop' ) ? app_setting( 'skin_front', 'shop' ) : 'shop-skin-front-classic';
		$this->_load_skin( $_skin, 'front' );

		//	"Checkout" Skin
		$_skin = app_setting( 'skin_checkout', 'shop' ) ? app_setting( 'skin_checkout', 'shop' ) : 'shop-skin-checkout-classic';
		$this->_load_skin( $_skin, 'checkout' );

		// --------------------------------------------------------------------------

		//	Shop's name
		$this->_shop_name = app_setting( 'name', 'shop' ) ? app_setting( 'name', 'shop' ) : 'Shop';

		// --------------------------------------------------------------------------

		//	Shop's base URL
		$this->_shop_url = app_setting( 'url', 'shop' ) ? app_setting( 'url', 'shop' ) : 'shop/';

		// --------------------------------------------------------------------------

		//	Pass data to the views
		$this->data['shop_name']	= $this->_shop_name;
		$this->data['shop_url']		= $this->_shop_url;
	}

	// --------------------------------------------------------------------------


	protected function _load_skin( $skin, $skin_type )
	{
		//	Sanity test; make sure we're loading a skin type which is supported
		switch ( $skin_type ) :

			case 'front' :

				$this->_skin_front			=& $this->shop_skin_front_model->get( $skin );
				$this->data['skin_front']	=& $this->_skin_front;

				if ( ! $this->_skin_front ) :

					$_error_subject = 'Failed to load shop front skin "' . $skin . '"';
					$_error_message = 'Shop front skin "' . $skin . '" failed to load at ' . APP_NAME . ', the following reason was given: ' . $this->shop_skin_front_model->last_error();

				endif;

			break;

			case 'checkout' :

				$this->_skin_checkout			=& $this->shop_skin_checkout_model->get( $skin );
				$this->data['skin_checkout']	=& $this->_skin_checkout;

				if ( ! $this->_skin_checkout ) :

					$_error_subject = 'Failed to load shop checkout skin "' . $skin . '"';
					$_error_message = 'Shop checkout skin "' . $skin . '" failed to load at ' . APP_NAME . ', the following reason was given: ' . $this->shop_skin_checkout_model->last_error();

				endif;

			break;

			default :

				showFatalError('"' . $skin_tye . '" is not a valid skin type', 'An invalid skin type was attempted on ' . APP_NAME);

			break;

		endswitch;

		if ( ! empty( $_error_subject ) || ! empty( $_error_message ) ) :

			showFatalError($_error_subject, $_error_message);

		endif;
	}


	// --------------------------------------------------------------------------


	protected function _load_skin_assets( $assets, $css_inline, $js_inline, $url )
	{
		//	CSS and JS
		if ( ! empty( $assets ) && is_array( $assets ) ) :

			foreach ( $assets AS $asset ) :

				if ( is_string( $asset ) ) :

					$this->asset->load( $url . 'assets/' . $asset );

				else :

					$this->asset->load( $asset[0], $asset[1] );

				endif;

			endforeach;

		endif;

		// --------------------------------------------------------------------------

		//	CSS - Inline
		if ( ! empty( $css_inline ) && is_array( $css_inline ) ) :

			foreach ( $css_inline AS $asset ) :

				$this->asset->inline( $asset, 'CSS_INLINE' );

			endforeach;

		endif;

		// --------------------------------------------------------------------------

		//	JS - Inline
		if ( ! empty( $js_inline ) && is_array( $js_inline ) ) :

			foreach ( $js_inline AS $asset ) :

				$this->asset->inline( $asset, 'JS_INLINE' );

			endforeach;

		endif;
	}
}

/* End of file _shop.php */
/* Location: ./modules/shop/controllers/_shop.php */