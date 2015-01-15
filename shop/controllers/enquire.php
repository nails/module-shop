<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Name:		Shop - enquire
 *
 * Description:	This controller handles the shop's enquire functionality
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

class NAILS_Enquire extends NAILS_Shop_Controller
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
		//	TODO
	}


	// --------------------------------------------------------------------------


	public function delivery()
	{
		$_product_id = $this->uri->rsegment( '3' );
		$_variant_id = $this->uri->rsegment( '4' );

		$this->data['product'] = $this->shop_product_model->get_by_id( $_product_id );
		$this->data['variant'] = NULL;

		if ( ! $this->data['product'] ) :

			show_404();

		endif;

		if ( $_variant_id ) :

			//	Fetch the variation
			foreach ( $this->data['product']->variations as $v ) :

				if ( $v->id = $_variant_id ) :

					$this->data['variant'] = $v;
					break;

				endif;

			endforeach;

			//	Check it's "collection only"
			if ( ! $this->data['variant'] || ! $this->data['variant']->shipping->collection_only ) :

				show_404();

			endif;

		endif;

		if ( ! $this->data['variant'] ) :

			//	Check that there are 'collection only' variations
			$_collect_only_variations = array();
			foreach ( $this->data['product']->variations as $v ) :

				if ( $v->shipping->collection_only ) :

					$_collect_only_variations[] = $v;

				endif;

			endforeach;

			if ( ! count( $_collect_only_variations ) ) :

				show_404();

			elseif ( count( $_collect_only_variations ) == 1 ) :

				$this->data['variant'] = $_collect_only_variations[0];

			endif;

		endif;

		// --------------------------------------------------------------------------

		if ( $this->input->get( 'is_fancybox' ) ) :

			$this->data['headerOverride'] = 'structure/header/blank';
			$this->data['footerOverride'] = 'structure/footer/blank';

		endif;

		// --------------------------------------------------------------------------

		if ( $this->input->post() ) :

			$this->load->library( 'form_validation' );

			$this->form_validation->set_rules( 'name',		'', 'xss_clean|required' );
			$this->form_validation->set_rules( 'email',		'', 'xss_clean|required|valid_email' );
			$this->form_validation->set_rules( 'telephone',	'', 'xss_clean' );
			$this->form_validation->set_rules( 'address',	'', 'xss_clean|required' );
			$this->form_validation->set_rules( 'notes',		'', 'xss_clean' );


			$this->form_validation->set_message( 'required',	lang( 'fv_required' ) );
			$this->form_validation->set_message( 'valid_email',	lang( 'fv_valid_email' ) );

			if ( $this->form_validation->run() ) :

				$_data							= array();

				$_data['customer']				= new stdClass();
				$_data['customer']->name		= $this->input->post( 'name' );
				$_data['customer']->email		= $this->input->post( 'email' );
				$_data['customer']->telephone	= $this->input->post( 'telephone' );
				$_data['customer']->address		= $this->input->post( 'address' );
				$_data['customer']->notes		= $this->input->post( 'notes' );

				$_data['product']				= new stdClass();
				$_data['product']->id			= $this->data['product']->id;
				$_data['product']->slug			= $this->data['product']->slug;
				$_data['product']->label		= $this->data['product']->label;

				foreach ( $this->data['product']->variations as $v ) :

					if ( $v->id == $this->input->post( 'variant_id' ) ) :

						$_data['variant']			= new stdClass();
						$_data['variant']->id		= $v->id;
						$_data['variant']->sku		= $v->sku;
						$_data['variant']->label	= $v->label;

					endif;

				endforeach;

				$_override				= array();
				$_override['email_tpl'] = $this->_skin_front->path . 'views/email/delivery_enquiry';

				if ( app_notification_notify( 'delivery_enquiry', 'shop', $_data, $_override ) ) :

					$this->data['success'] = '<strong>Success!</strong> Your enquiry was received successfully.';

				else :

					$this->data['error'] = '<strong>Sorry,</strong> failed to send enquiry. ' . $this->app_notification_model->last_error();

				endif;

			else :

				$this->data['error'] = lang( 'fv_there_were_errors' );

			endif;

		endif;

		// --------------------------------------------------------------------------

		if ( $this->data['variant'] ) :

			$this->data['page']->title = $this->_shop_name . ': Delivery enquiry about "' . $this->data['variant']->label . '"';

		else :

			$this->data['page']->title = $this->_shop_name . ': Delivery enquiry about "' . $this->data['product']->label . '"';

		endif;

		// --------------------------------------------------------------------------

		$this->load->view( 'structure/header',									$this->data );
		$this->load->view( $this->_skin_front->path . 'views/enquire/index',	$this->data );
		$this->load->view( 'structure/footer',									$this->data );
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

if ( ! defined( 'NAILS_ALLOW_EXTENSION_ENQUIRE' ) ) :

	class Enquire extends NAILS_Enquire
	{
	}

endif;

/* End of file enquire.php */
/* Location: ./modules/shop/controllers/enquire.php */