<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Name:		Shop - Notify
 *
 * Description:	This controller handles the user's notify
 *
 **/

/**
 * OVERLOADING NAILS' AUTH MODULE
 *
 * Note the name of this class; done like this to allow apps to extend this class.
 * Read full explanation at the bottom of this file.
 *
 **/

//	Include _shop.php; executes common functionality
require_once '_shop.php';

class NAILS_Notify extends NAILS_Shop_Controller
{
	public function __construct()
	{
		parent::__construct();

		// --------------------------------------------------------------------------

		//	Load appropriate assets
		$_assets		= ! empty( $this->_skin_checkout->assets )		? $this->_skin_checkout->assets		: array();
		$_css_inline	= ! empty( $this->_skin_checkout->css_inline )	? $this->_skin_checkout->css_inline	: array();
		$_js_inline		= ! empty( $this->_skin_checkout->js_inline )	? $this->_skin_checkout->js_inline	: array();

		$this->_load_skin_assets( $_assets, $_css_inline, $_js_inline, $this->_skin_checkout->url );
	}


	// --------------------------------------------------------------------------


	public function index()
	{
		$_variant_id = $this->uri->rsegment( '2' );
		$this->data['product'] = $this->shop_product_model->getByVariantId( $_variant_id );

		if ( ! $this->data['product'] ) :

			show_404();

		endif;

		foreach ( $this->data['product']->variations AS $v ) :

			if ( $v->id = $_variant_id ) :

				$this->data['variant'] = $v;

			endif;

		endforeach;

		if ( ! $this->data['variant'] ) :

			show_404();

		endif;

		// --------------------------------------------------------------------------

		if ( $this->input->get( 'is_fancybox' ) ) :

			$this->data['headerOverride'] = 'structure/header/blank';
			$this->data['footerOverride'] = 'structure/footer/blank';

		endif;

		// --------------------------------------------------------------------------

		if ( $this->input->post() ) :

			$this->load->model( 'shop/shop_inform_product_available_model' );

			if ( $this->shop_inform_product_available_model->add( $_variant_id, $this->input->post( 'email' ) ) ) :

				$this->data['success']				= '<strong>Success!</strong> You were added to the notification list for this item.';
				$this->data['successfully_added']	= TRUE;

			else :

				$this->data['error'] = '<strong>Sorry,</strong> could not add you to the mailing list. ' . $this->shop_inform_product_available_model->last_error();

			endif;

		endif;

		// --------------------------------------------------------------------------

		$this->data['page']->title = $this->_shop_name . ': Notify when "' . $this->data['variant']->label . '" is back in stock';

		// --------------------------------------------------------------------------

		$this->load->view( 'structure/header',								$this->data );
		$this->load->view( $this->_skin_front->path . 'views/notify/index',	$this->data );
		$this->load->view( 'structure/footer',								$this->data );
	}

	// --------------------------------------------------------------------------

	public function _remap()
	{
		$this->index();
	}
}


// --------------------------------------------------------------------------


/**
 * OVERLOADING NAILS' SHOP MODULE
 *
 * The following block of code makes it simple to extend one of the core shop
 * controllers. Some might argue it's a little hacky but it's a simple 'fix'
 * which negates the need to massively extend the CodeIgniter Loader class
 * even further (in all honesty I just can't face understanding the whole
 * Loader class well enough to change it 'properly').
 *
 * Here's how it works:
 *
 * CodeIgniter instantiate a class with the same name as the file, therefore
 * when we try to extend the parent class we get 'cannot redeclare class X' errors
 * and if we call our overloading class something else it will never get instantiated.
 *
 * We solve this by prefixing the main class with NAILS_ and then conditionally
 * declaring this helper class below; the helper gets instantiated et voila.
 *
 * If/when we want to extend the main class we simply define NAILS_ALLOW_EXTENSION
 * before including this PHP file and extend as normal (i.e in the same way as below);
 * the helper won't be declared so we can declare our own one, app specific.
 *
 **/

if ( ! defined( 'NAILS_ALLOW_EXTENSION_NOTIFY' ) ) :

	class Notify extends NAILS_Notify
	{
	}

endif;

/* End of file notify.php */
/* Location: ./modules/shop/controllers/notify.php */