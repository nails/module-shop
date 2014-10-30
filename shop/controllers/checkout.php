<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Name:		Shop - Checkout
 *
 * Description:	This controller handles the user's checkout experience
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

class NAILS_Checkout extends NAILS_Shop_Controller
{
	/**
	 * Construct the model
	 */
	public function __construct()
	{
		parent::__construct();
		$this->load->model( 'shop/shop_payment_gateway_model' );

		// --------------------------------------------------------------------------

		//	Load appropriate assets
		$_assets		= ! empty( $this->_skin_checkout->assets )		? $this->_skin_checkout->assets		: array();
		$_css_inline	= ! empty( $this->_skin_checkout->css_inline )	? $this->_skin_checkout->css_inline	: array();
		$_js_inline		= ! empty( $this->_skin_checkout->js_inline )	? $this->_skin_checkout->js_inline	: array();

		$this->_load_skin_assets( $_assets, $_css_inline, $_js_inline, $this->_skin_checkout->url );
	}


	// --------------------------------------------------------------------------


	/**
	 * Handle the checkout process
	 *
	 * @access	public
	 * @return	void
	 *
	 **/
	public function index()
	{
		$this->load->model( 'system/country_model' );

		$this->data['countries_flat']	= $this->country_model->get_all_flat();
		$this->data['payment_gateways']	= $this->shop_payment_gateway_model->get_enabled_formatted();

		if ( ! count( $this->data['payment_gateways'] ) ) :

			$this->data['error'] = '<strong>Error:</strong> No Payment Gateways are configured.';
			$this->data['page']->title = $this->_shop_name . ': No Payment Gateways have been configured';

			$this->load->view( 'structure/header',											$this->data );
			$this->load->view( $this->_skin_checkout->path . 'views/checkout/no_gateway',	$this->data );
			$this->load->view( 'structure/footer',											$this->data );
			return;

		endif;

		// --------------------------------------------------------------------------

		$_basket = $this->shop_basket_model->get();

		if ( empty( $_basket->items ) ) :

			$this->session->set_flashdata( 'error', '<strong>Sorry,</strong> you cannot checkout just now. Your basket is empty.' );
			redirect( $this->_shop_url . 'basket' );

		endif;

		// --------------------------------------------------------------------------

		//	Abandon any previous orders
		$_previous_order = $this->shop_payment_gateway_model->checkout_session_get();

		if ( $_previous_order ) :

			$this->shop_order_model->abandon( $_previous_order );
			$this->shop_payment_gateway_model->checkout_session_clear();

		endif;

		// --------------------------------------------------------------------------

		if ( $this->input->post() ) :

			if ( ! $this->shop_payment_gateway_model->is_enabled( $this->input->post( 'payment_gateway' ) ) ) :

				$this->data['error'] = '"' . $this->input->post( 'payment_gateway' ) . '" is not a valid payment gateway.';

			else :

				$this->load->library( 'form_validation' );

				$this->form_validation->set_rules( 'delivery_address_line_1',	'', 'xss_clean|trim|required' );
				$this->form_validation->set_rules( 'delivery_address_line_2',	'', 'xss_clean|trim' );
				$this->form_validation->set_rules( 'delivery_address_town',		'', 'xss_clean|trim|required' );
				$this->form_validation->set_rules( 'delivery_address_state',	'', 'xss_clean|trim' );
				$this->form_validation->set_rules( 'delivery_address_postcode',	'', 'xss_clean|trim|required' );
				$this->form_validation->set_rules( 'delivery_address_country',	'', 'xss_clean|required' );

				$this->form_validation->set_rules( 'first_name',				'', 'xss_clean|trim|required' );
				$this->form_validation->set_rules( 'last_name',					'', 'xss_clean|trim|required' );
				$this->form_validation->set_rules( 'email',						'', 'xss_clean|trim|required' );
				$this->form_validation->set_rules( 'telephone',					'', 'xss_clean|trim' );

				if ( ! $this->input->post( 'same_billing_address' ) ) :

					$this->form_validation->set_rules( 'billing_address_line_1',	'', 'xss_clean|trim|required' );
					$this->form_validation->set_rules( 'billing_address_line_2',	'', 'xss_clean|trim' );
					$this->form_validation->set_rules( 'billing_address_town',		'', 'xss_clean|trim|required' );
					$this->form_validation->set_rules( 'billing_address_state',		'', 'xss_clean|trim' );
					$this->form_validation->set_rules( 'billing_address_postcode',	'', 'xss_clean|trim|required' );
					$this->form_validation->set_rules( 'billing_address_country',	'', 'xss_clean|trim|required' );

				else :

					$this->form_validation->set_rules( 'billing_address_line_1',	'', 'xss_clean|trim' );
					$this->form_validation->set_rules( 'billing_address_line_2',	'', 'xss_clean|trim' );
					$this->form_validation->set_rules( 'billing_address_town',		'', 'xss_clean|trim' );
					$this->form_validation->set_rules( 'billing_address_state',		'', 'xss_clean|trim' );
					$this->form_validation->set_rules( 'billing_address_postcode',	'', 'xss_clean|trim' );
					$this->form_validation->set_rules( 'billing_address_country',	'', 'xss_clean|trim' );

				endif;

				$this->form_validation->set_rules( 'payment_gateway', '', 'xss_clean|trim|required' );

				$this->form_validation->set_message( 'required', lang( 'fv_required' ) );

				if ( $this->form_validation->run() ) :

					//	Prepare data
					$_data = new stdClass();

					//	Contact details
					$_data->contact				= new stdClass();
					$_data->contact->first_name	= $this->input->post( 'first_name' );
					$_data->contact->last_name	= $this->input->post( 'last_name' );
					$_data->contact->email		= $this->input->post( 'email' );
					$_data->contact->telephone	= $this->input->post( 'telephone' );

					//	Delivery Details
					$_data->delivery			= new stdClass();
					$_data->delivery->line_1	= $this->input->post( 'delivery_address_line_1' );
					$_data->delivery->line_2	= $this->input->post( 'delivery_address_line_2' );
					$_data->delivery->town		= $this->input->post( 'delivery_address_town' );
					$_data->delivery->state		= $this->input->post( 'delivery_address_state' );
					$_data->delivery->postcode	= $this->input->post( 'delivery_address_postcode' );
					$_data->delivery->country	= $this->input->post( 'delivery_address_country' );

					//	Billing details
					if ( ! $this->input->post( 'same_billing_address' ) ) :

						$_data->billing				= new stdClass();
						$_data->billing->line_1		= $this->input->post( 'billing_address_line_1' );
						$_data->billing->line_2		= $this->input->post( 'billing_address_line_2' );
						$_data->billing->town		= $this->input->post( 'billing_address_town' );
						$_data->billing->state		= $this->input->post( 'billing_address_state' );
						$_data->billing->postcode	= $this->input->post( 'billing_address_postcode' );
						$_data->billing->country	= $this->input->post( 'billing_address_country' );

					else :

						$_data->billing				= new stdClass();
						$_data->billing->line_1		= $this->input->post( 'delivery_address_line_1' );
						$_data->billing->line_2		= $this->input->post( 'delivery_address_line_2' );
						$_data->billing->town		= $this->input->post( 'delivery_address_town' );
						$_data->billing->state		= $this->input->post( 'delivery_address_state' );
						$_data->billing->postcode	= $this->input->post( 'delivery_address_postcode' );
						$_data->billing->country	= $this->input->post( 'delivery_address_country' );

					endif;

					//	And the basket
					$_data->basket = $_basket;

					//	Generate the order and proceed to payment
					$_order = $this->shop_order_model->create( $_data, TRUE );

					if ( $_order ) :

						/**
						 * Order created successfully, attempt payment. We need to keep track of the order ID
						 * so that when we redirect the processing/cancel pages can pick up where we left off.
						 */

						$this->shop_payment_gateway_model->checkout_session_save( $_order->id, $_order->ref, $_order->code );

						if ( $this->shop_payment_gateway_model->do_payment( $_order->id, $this->input->post( 'payment_gateway' ), $this->input->post() ) ) :

							//	Payment complete! Mark order as paid and then process it, finally send user to processing page for receipt
							$this->shop_order_model->paid( $_order->id );
							$this->shop_order_model->process( $_order->id );

							$_shop_url = app_setting( 'url', 'shop' ) ? app_setting( 'url', 'shop' ) : 'shop/';
							redirect( $_shop_url . 'checkout/processing?ref=' . $_order->ref );

						else :

							//	Payment failed, mark this order as a failure too.
							$this->shop_order_model->fail( $_order->id, $this->shop_payment_gateway_model->last_error() );
							$this->data['error']			= '<strong>Sorry,</strong> something went wrong during checkout. ' . $this->shop_payment_gateway_model->last_error();
							$this->data['payment_error']	= $this->shop_payment_gateway_model->last_error();

							$this->shop_payment_gateway_model->checkout_session_clear();

						endif;

					else :

						$this->data['error'] = '<strong>Sorry,</strong> there was a problem processing your order. ' . $this->shop_order_model->last_error();

					endif;

				else :

					$this->data['error'] = lang( 'fv_there_were_errors' );

				endif;

			endif;

		endif;

		// --------------------------------------------------------------------------

		//	Load assets required by the payment gateways
		foreach ( $this->data['payment_gateways'] AS $pg ) :

			$_assets = $this->shop_payment_gateway_model->get_checkout_assets( $pg->slug );

			foreach ( $_assets AS $asset ) :

				$_inline = array( 'JS_INLINE', 'JS-INLINE', 'CSS_INLINE', 'CSS-INLINE' );

				if ( in_array( $asset[2], $_inline ) ) :

					$this->asset->inline( $asset[0], $asset[2] );

				else :

					$this->asset->load( $asset[0], $asset[1], $asset[2] );

				endif;

			endforeach;

		endforeach;

		// --------------------------------------------------------------------------

		$this->data['page']->title = $this->_shop_name . ': Checkout';

		// --------------------------------------------------------------------------

		$this->load->view( 'structure/header',										$this->data );
		$this->load->view( $this->_skin_checkout->path . 'views/checkout/index',	$this->data );
		$this->load->view( 'structure/footer',										$this->data );
	}

	// --------------------------------------------------------------------------


	/**
	 * Shown to the user once the payment gateway has been informed.
	 * @return void
	 */
	public function processing()
	{
		$this->data['order'] = $this->_get_order();

		if ( empty( $this->data['order'] ) ) :

			show_404();

		else :

			//	Fetch the product/variants associated with each order item
			foreach ( $this->data['order']->items AS $item ) :

				$item->product = $this->shop_product_model->get_by_id( $item->product_id );

				if ( ! empty( $item->product ) ) :

					//	Find the variant
					foreach( $item->product->variations AS &$v ) :

						if ( $v->id == $item->variant_id ) :

							$item->variant = $v;
							break;

						endif;

					endforeach;

				endif;

			endforeach;

			// --------------------------------------------------------------------------

			//	Map the country codes to names
			$this->load->model( 'system/country_model' );
			$this->data['country'] = $this->country_model->get_all_flat();

			if ( $this->data['order']->shipping_address->country ) :

				$this->data['order']->shipping_address->country = $this->data['country'][$this->data['order']->shipping_address->country];

			endif;

			if ( $this->data['order']->billing_address->country ) :

				$this->data['order']->billing_address->country = $this->data['country'][$this->data['order']->billing_address->country];

			endif;

			// --------------------------------------------------------------------------

			//	Empty the basket
			$this->shop_basket_model->destroy();

			// --------------------------------------------------------------------------

			switch( $this->data['order']->status ) :

				case 'UNPAID' :		$this->_processing_unpaid();		break;
				case 'PAID' :		$this->_processing_paid();			break;
				case 'PENDING' :	$this->_processing_pending();		break;
				case 'FAILED' :		$this->_processing_failed();		break;
				case 'ABANDONED' :	$this->_processing_abandoned();		break;
				case 'CANCELLED' :	$this->_processing_cancelled();		break;
				default :			$this->_processing_error();			break;

			endswitch;

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Renders the "unpaid" processing view.
	 * @return void
	 */
	protected function _processing_unpaid()
	{
		$this->load->view( $this->_skin_checkout->path . 'views/checkout/processing/unpaid',	$this->data );
	}


	// --------------------------------------------------------------------------


	/**
	 * Renders the "pending" processing view.
	 * @return void
	 */
	protected function _processing_pending()
	{
		//	Now we know what the state of play is, clear the session.
		$this->shop_payment_gateway_model->checkout_session_clear();

		//	And load the view
		$this->load->view( $this->_skin_checkout->path . 'views/checkout/processing/pending',	$this->data );
	}


	// --------------------------------------------------------------------------


	/**
	 * Renders the "paid" processing view.
	 * @return void
	 */
	protected function _processing_paid()
	{
		$this->data['page']->title	= 'Thanks for your order!';
		$this->data['success']		= '<strong>Success!</strong> Your order has been processed.';

		// --------------------------------------------------------------------------

		//	Now we know what the state of play is, clear the session.
		$this->shop_payment_gateway_model->checkout_session_clear();

		//	And load the view
		$this->load->view( $this->_skin_checkout->path . 'views/checkout/processing/paid',	$this->data );
	}


	// --------------------------------------------------------------------------


	/**
	 * Renders the "failed" processing view.
	 * @return void
	 */
	protected function _processing_failed()
	{
		if ( ! $this->data['error'] ) :

			$this->data['error'] = '<strong>Sorry,</strong> there was a problem processing your order';

		endif;

		if ( ! isset( $this->data['page']->title ) || ! $this->data['page']->title ) :

			$this->data['page']->title = 'An error occurred';

		endif;

		// --------------------------------------------------------------------------

		//	Now we know what the state of play is, clear the session.
		$this->shop_payment_gateway_model->checkout_session_clear();

		//	And load the view
		$this->load->view( $this->_skin_checkout->path . 'views/checkout/processing/failed',	$this->data );
	}


	// --------------------------------------------------------------------------


	/**
	 * Renders the "abandoned" processing view.
	 * @return void
	 */
	protected function _processing_abandoned()
	{
		if ( ! $this->data['error'] ) :

			$this->data['error'] = '<strong>Sorry,</strong> there was a problem processing your order';

		endif;

		if ( ! isset( $this->data['page']->title ) || ! $this->data['page']->title ) :

			$this->data['page']->title = 'An error occurred';

		endif;

		// --------------------------------------------------------------------------

		//	Now we know what the state of play is, clear the session.
		$this->shop_payment_gateway_model->checkout_session_clear();

		//	And load the view
		$this->load->view( $this->_skin_checkout->path . 'views/checkout/processing/abandoned',	$this->data );
	}


	// --------------------------------------------------------------------------


	/**
	 * Renders the "cancelled" processing view.
	 * @return void
	 */
	protected function _processing_cancelled()
	{
		if ( ! $this->data['error'] ) :

			$this->data['error'] = '<strong>Sorry,</strong> there was a problem processing your order';

		endif;

		if ( ! isset( $this->data['page']->title ) || ! $this->data['page']->title ) :

			$this->data['page']->title = 'An error occurred';

		endif;

		// --------------------------------------------------------------------------

		//	Now we know what the state of play is, clear the session.
		$this->shop_payment_gateway_model->checkout_session_clear();

		//	And load the view
		$this->load->view( $this->_skin_checkout->path . 'views/checkout/processing/cancelled',	$this->data );
	}


	// --------------------------------------------------------------------------


	/**
	 * Renders the "error" processing view.
	 * @return void
	 */
	protected function _processing_error( $error = '' )
	{
		if ( ! $this->data['error'] ) :

			$this->data['error'] = '<strong>Sorry,</strong> there was a problem processing your order. ' . $error;

		endif;

		if ( ! isset( $this->data['page']->title ) || ! $this->data['page']->title ) :

			$this->data['page']->title = 'An error occurred';

		endif;

		// --------------------------------------------------------------------------

		//	Now we know what the state of play is, clear the session.
		$this->shop_payment_gateway_model->checkout_session_clear();

		//	And load the view
		$this->load->view( $this->_skin_checkout->path . 'views/checkout/processing/error',	$this->data );
	}


	// --------------------------------------------------------------------------


	/**
	 * Marks an order as cancelled and redirects the user to the basket with feedback.
	 * @return void
	 */
	public function cancel()
	{
		$_order = $this->_get_order(false);

		if ( empty( $_order ) ) :

			show_404();

		endif;

		//	Can't cancel an order which has been paid
		if ( $_order->status == 'PAID' ) :

			$this->session->set_flashdata( 'error', '<strong>Order cannot be cancelled.</strong><br />that order has already been paid and cannot be cancelled.' );

		else :

			$this->shop_order_model->cancel( $_order->id );
			$this->session->set_flashdata( 'message', '<strong>Checkout was cancelled.</strong><br />At your request, we cancelled checkout - you have not been charged.' );

		endif;

		redirect( $this->_shop_url . 'basket' );
	}


	// --------------------------------------------------------------------------


	public function confirm()
	{
		$_order = $this->_get_order();

		if ( empty( $_order ) ) :

			show_404();

		endif;

		$_result = $this->shop_payment_gateway_model->confirm_complete_payment( $this->uri->rsegment( 3 ), $_order );

		if ( $_result ) :

			redirect( $this->_shop_url . 'checkout/processing?ref=' . $_order->ref );

		else :

			$this->session->set_flashdata( 'error', 'An error occurred during checkout, you may have been charged. ' . $this->shop_payment_gateway_model->last_error() );
			redirect( $this->_shop_url . 'checkout' );

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Allows the customer to download an invoice
	 * @return void
	 */
	public function invoice()
	{
		//	Fetch and check order
		$this->load->model('shop/shop_order_model');

		$this->data['order'] = $this->shop_order_model->get_by_ref($this->uri->rsegment(3));
		if (!$this->data['order'] || $this->uri->rsegment(4) != md5($this->data['order']->code)) {

			show_404();

		}

		// --------------------------------------------------------------------------

		//	Load up the shop's skin
		$skin = app_setting('skin_checkout', 'shop') ? app_setting('skin_checkout', 'shop') : 'shop-skin-checkout-classic';

		$this->load->model('shop/shop_skin_checkout_model');
		$skin = $this->shop_skin_checkout_model->get($skin);

		if (!$skin) {

			show_fatal_error('Failed to load shop skin "' . $skin . '"', 'Shop skin "' . $skin . '" failed to load at ' . APP_NAME . ', the following reason was given: ' . $this->shop_skin_checkout_model->last_error());

		}

		// --------------------------------------------------------------------------

		//	Views
		$this->data['for_user'] = 'CUSTOMER';
		$this->load->library('pdf/pdf');
		$this->pdf->set_paper_size('A4', 'landscape');
		$this->pdf->load_view($skin->path . 'views/order/invoice', $this->data);
		$this->pdf->download('INVOICE-' . $this->data['order']->ref . '.pdf');
	}


	// --------------------------------------------------------------------------


	/**
	 * Fetches the order, used by the checkout process
	 * @param  boolean $redirect Whether to redirect to the processing page if session order is found
	 * @return mixed
	 */
	protected function _get_order($redirect = true)
	{
		$_order_ref = $this->input->get( 'ref' );

		if ( $_order_ref ) :

			$this->shop_payment_gateway_model->checkout_session_clear();
			return $this->shop_order_model->get_by_ref( $_order_ref );

		else :

			//	No ref, try the session
			$_order_id = $this->shop_payment_gateway_model->checkout_session_get();

			if ( $_order_id ) :

				$_order = $this->shop_order_model->get_by_id( $_order_id );

				if ( $_order ) :

					$this->shop_payment_gateway_model->checkout_session_clear();
					if ($redirect == true) {

						redirect($this->_shop_url . 'checkout/processing?ref=' . $_order->ref);

					} else {

						return $_order;
					}

				endif;

			endif;

		endif;
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

if ( ! defined( 'NAILS_ALLOW_EXTENSION_CHECKOUT' ) ) :

	class Checkout extends NAILS_Checkout
	{
	}

endif;

/* End of file checkout.php */
/* Location: ./application/modules/shop/controllers/checkout.php */