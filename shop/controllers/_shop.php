<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Name:		NALS_SHOP_Controller
 *
 * Description:	This controller executes various bits of common Shop functionality
 *
 **/


class NAILS_Shop_Controller extends NAILS_Controller
{
	protected $_skin;


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
		$this->load->model( 'shop/shop_shipping_model' );
		$this->load->model( 'shop/shop_sale_model' );
		$this->load->model( 'shop/shop_tag_model' );
		$this->load->model( 'shop/shop_voucher_model' );
		$this->load->model( 'shop/shop_skin_model' );

		// --------------------------------------------------------------------------

		//	Load up the shop's skin
		$_skin = app_setting( 'skin', 'shop' ) ? app_setting( 'skin', 'shop' ) : 'skin-shop-gettingstarted';

		$this->_skin = $this->shop_skin_model->get( $_skin );

		if ( ! $this->_skin ) :

			show_fatal_error( 'Failed to load shop skin "' . $_skin . '"', 'Shop skin "' . $_skin . '" failed to load at ' . APP_NAME . ', the following reason was given: ' . $this->shop_skin_model->last_error() );

		endif;

		// --------------------------------------------------------------------------

		//	Pass to $this->data, for the views
		$this->data['skin'] = $this->_skin;

		// --------------------------------------------------------------------------

		//	Load skin assets

		//	CSS
		if ( ! empty( $this->_skin->assets->css ) && is_array( $this->_skin->assets->css ) ) :

			foreach ( $this->_skin->assets->css AS $asset ) :

				$this->asset->load( $this->_skin->url . 'assets/css/' . $asset );

			endforeach;

		endif;

		//	CSS - Inline
		if ( ! empty( $this->_skin->assets->css_inline ) && is_array( $this->_skin->assets->css_inline ) ) :

			foreach ( $this->_skin->assets->css_inline AS $asset ) :

				$this->asset->inline( $asset, 'CSS_INLINE' );

			endforeach;

		endif;

		//	JS
		if ( ! empty( $this->_skin->assets->js ) && is_array( $this->_skin->assets->js ) ) :

			foreach ( $this->_skin->assets->js AS $asset ) :

				$this->asset->load( $this->_skin->url . 'assets/js/' . $asset );

			endforeach;

		endif;

		//	JS - Inline
		if ( ! empty( $this->_skin->assets->js_inline ) && is_array( $this->_skin->assets->js_inline ) ) :

			foreach ( $this->_skin->assets->js_inline AS $asset ) :

				$this->asset->inline( $asset, 'JS_INLINE' );

			endforeach;

		endif;

		// --------------------------------------------------------------------------

		//	Shop's name
		$this->_shop_name = app_setting( 'name', 'shop' ) ? app_setting( 'name', 'shop' ) : 'Shop';
	}
}

/* End of file _shop.php */
/* Location: ./modules/shop/controllers/_shop.php */