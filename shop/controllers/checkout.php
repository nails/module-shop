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
	 * Handle the checkout process
	 *
	 * @access	public
	 * @return	void
	 *
	 **/
	public function index()
	{
		$_basket = $this->shop_basket_model->get();

		if ( empty( $_basket->items ) ) :

			$this->session->set_flashdata( 'error', '<strong>Sorry,</strong> you cannot checkout just now. Your basket is empty.' );
			redirect( $this->_shop_url . 'basket' );

		endif;

		// --------------------------------------------------------------------------

		if ( $this->input->post() ) :

			$this->load->library( 'form_validation' );

			$this->form_validation->set_rules( 'delivery_address_line_1',	'', 'xss_clean|required' );
			$this->form_validation->set_rules( 'delivery_address_line_2',	'', 'xss_clean' );
			$this->form_validation->set_rules( 'delivery_address_town',		'', 'xss_clean|required' );
			$this->form_validation->set_rules( 'delivery_address_state',	'', 'xss_clean' );
			$this->form_validation->set_rules( 'delivery_address_postcode',	'', 'xss_clean|required' );
			$this->form_validation->set_rules( 'delivery_address_country',	'', 'xss_clean|required' );

			$this->form_validation->set_rules( 'first_name',				'', 'xss_clean|required' );
			$this->form_validation->set_rules( 'last_name',					'', 'xss_clean|required' );
			$this->form_validation->set_rules( 'email',						'', 'xss_clean|required' );
			$this->form_validation->set_rules( 'telephone',					'', 'xss_clean' );

			if ( ! $this->input->post( 'same_billing_address' ) ) :

				$this->form_validation->set_rules( 'billing_address_line_1',	'', 'xss_clean|required' );
				$this->form_validation->set_rules( 'billing_address_line_2',	'', 'xss_clean' );
				$this->form_validation->set_rules( 'billing_address_town',		'', 'xss_clean|required' );
				$this->form_validation->set_rules( 'billing_address_state',		'', 'xss_clean' );
				$this->form_validation->set_rules( 'billing_address_postcode',	'', 'xss_clean|required' );
				$this->form_validation->set_rules( 'billing_address_country',	'', 'xss_clean|required' );

			else :

				$this->form_validation->set_rules( 'billing_address_line_1',	'', 'xss_clean' );
				$this->form_validation->set_rules( 'billing_address_line_2',	'', 'xss_clean' );
				$this->form_validation->set_rules( 'billing_address_town',		'', 'xss_clean' );
				$this->form_validation->set_rules( 'billing_address_state',		'', 'xss_clean' );
				$this->form_validation->set_rules( 'billing_address_postcode',	'', 'xss_clean' );
				$this->form_validation->set_rules( 'billing_address_country',	'', 'xss_clean' );

			endif;

			$this->form_validation->set_message( 'required', lang( 'fv_required' ) );

			if ( $this->form_validation->run() ) :

				//	Prepare data
				$_data						= new stdClass();

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

					//	Order created successfully proceed to payment
					redirect( $this->_shop_url . 'checkout/payment/' . $_order->ref . '/' . $_order->code );

				else :

					$this->data['error'] = '<strong>Sorry,</strong> there was a problem processing your order. ' . $this->shop_order_model->last_error();

				endif;

			else :

				$this->data['error'] = lang( 'fv_there_were_errors' );

			endif;


		endif;

		// --------------------------------------------------------------------------

		$this->data['page']->title = $this->_shop_name . ': Checkout';

		// --------------------------------------------------------------------------

		$this->load->model( 'system/country_model' );
		$this->data['countries_flat'] = $this->country_model->get_all_flat();

		// --------------------------------------------------------------------------

		$this->load->view( 'structure/header',							$this->data );
		$this->load->view( $this->_skin->path . 'views/checkout/index',	$this->data );
		$this->load->view( 'structure/footer',							$this->data );
	}


	// --------------------------------------------------------------------------


	/**
	 * Handle redirecting to the chosen payment gateway.
	 * @return void
	 */
	public function payment()
	{
		//	Verify there's an unpaid order
		$this->data['order_ref']	= $this->uri->rsegment(3);
		$this->data['order_code']	= $this->uri->rsegment(4);
		$_gateway					= $this->uri->rsegment(5);

		$_order = $this->shop_order_model->get_by_ref( $this->data['order_ref'] );

		if ( ! $_order || $_order->code != $this->data['order_code'] || $_order->status != 'UNPAID' ) :

			show_404();

		endif;

		// --------------------------------------------------------------------------

		$this->load->model( 'shop/shop_payment_gateway_model' );
		$this->data['payment_gateways'] = $this->shop_payment_gateway_model->get_enabled();

		if ( ! $_gateway && count( $this->data['payment_gateways'] ) == 1 ) :

			$_gateway = $this->data['payment_gateways'][0];

		endif;

		if ( $_gateway ) :

			//	Gateway selected, process
			if ( $this->shop_payment_gateway_model->do_payment( $_order->id, $_gateway ) ) :

				dump( 'Payment complete' );

			else :

				$this->session->set_flashdata( 'error', '<strong>Sorry,</strong> something went wrong during checkout. ' . $this->shop_payment_gateway_model->last_error() );
				redirect($this->_shop_url . 'checkout' );

			endif;

		else :

			$this->load->view( 'structure/header',										$this->data );
			$this->load->view( $this->_skin->path . 'views/checkout/choose_gateway',	$this->data );
			$this->load->view( 'structure/footer',										$this->data );

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Shown to the user once the payment gateway has been informed.
	 * @return void
	 */
	public function processing()
	{
		$this->data['order'] = $this->shop_order_model->get_by_ref( $this->input->get( 'ref' ) );

		if ( ! $this->data['order'] ) :

			show_404();

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
	}


	// --------------------------------------------------------------------------


	/**
	 * Renders the "unpaid" processing view.
	 * @return void
	 */
	protected function _processing_unpaid()
	{
		$this->load->view( $this->_skin->path . 'views/checkout/processing/unpaid', $this->data );
	}


	// --------------------------------------------------------------------------


	/**
	 * Renders the "pending" processing view.
	 * @return void
	 */
	protected function _processing_pending()
	{
		$this->load->view( 'structure/header',											$this->data );
		$this->load->view( $this->_skin->path . 'views/checkout/processing/pending',	$this->data );
		$this->load->view( 'structure/footer',											$this->data );
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

		$this->load->view( 'structure/header',										$this->data );
		$this->load->view( $this->_skin->path . 'views/checkout/processing/paid',	$this->data );
		$this->load->view( 'structure/footer',										$this->data );
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

		$this->load->view( 'structure/header',										$this->data );
		$this->load->view( $this->_skin->path . 'views/checkout/processing/failed',	$this->data );
		$this->load->view( 'structure/footer',										$this->data );
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

		$this->load->view( 'structure/header',											$this->data );
		$this->load->view( $this->_skin->path . 'views/checkout/processing/abandoned',	$this->data );
		$this->load->view( 'structure/footer',											$this->data );
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

		$this->load->view( 'structure/header',											$this->data );
		$this->load->view( $this->_skin->path . 'views/checkout/processing/cancelled',	$this->data );
		$this->load->view( 'structure/footer',								 			$this->data );
	}


	// --------------------------------------------------------------------------


	/**
	 * Renders the "error" processing view.
	 * @return void
	 */
	protected function _processing_error()
	{
		if ( ! $this->data['error'] ) :

			$this->data['error'] = '<strong>Sorry,</strong> there was a problem processing your order';

		endif;

		if ( ! isset( $this->data['page']->title ) || ! $this->data['page']->title ) :

			$this->data['page']->title = 'An error occurred';

		endif;

		// --------------------------------------------------------------------------

		$this->load->view( 'structure/header',										$this->data );
		$this->load->view( $this->_skin->path . 'views/checkout/processing/error',	$this->data );
		$this->load->view( 'structure/footer',										$this->data );
	}


	// --------------------------------------------------------------------------


	/**
	 * Marks an order as cancelled and redirects the use to the basket with feedback.
	 * @return void
	 */
	public function cancel()
	{
		$this->data['order'] = $this->shop_order_model->get_by_ref( $this->input->get( 'ref' ) );

		if ( ! $this->data['order'] ) :

			show_404();

		endif;

		// --------------------------------------------------------------------------

		$this->shop_order_model->cancel( $this->data['order']->id );

		$this->session->set_flashdata( 'message', '<strong>Checkout was cancelled.</strong><br />At your request, we cancelled checkout - you have not been charged.' );

		redirect( $this->_shop_url . 'basket' );
	}


	// --------------------------------------------------------------------------


	/**
	 * Handles incoming IPN notifications
	 * @return void
	 */
	public function notify()
	{
		//	Testing, testing, 1, 2, 3?
		$this->data['testing'] = $this->_notify_is_testing();

		//	Handle the notification in a way appropriate to the payment gateway
		switch( $this->uri->rsegment( 3 ) ) :

			case 'paypal';	$this->_notify_paypal();	break;

			// --------------------------------------------------------------------------

			default : /*	Silence is golden	*/	break;

		endswitch;
	}


	// --------------------------------------------------------------------------


	/**
	 * Handles incoming IPN notification from PayPal
	 * @return void
	 */
	protected function _notify_paypal()
	{
		//	Configure log
		_LOG_FILE( $this->_shop_url . 'notify/paypal/ipn-' . date( 'Y-m-d' ) . '.php' );

		_LOG();
		_LOG( '- - - - - - - - - - - - - - - - - - -' );
		_LOG( 'Waking up IPN responder; handling with PayPal' );

		// --------------------------------------------------------------------------

		//	POST data?

		//	Want to test a previous IPN message?
		//	Paste the IPN message into the following and uncomment the following lines

		//	$_message = '';
		//	$_message = str_replace( '+', '%2B', $_message );
		//	parse_str( $_message, $_POST );

		if ( ! $this->data['testing'] && ! $this->input->post() ) :

			_LOG( 'No POST data, going back to sleep...' );
			_LOG( '- - - - - - - - - - - - - - - - - - -' );
			_LOG();

			return;

		endif;

		// --------------------------------------------------------------------------

		//	Are we testing?
		if ( $this->data['testing'] ) :

			$_ipn = TRUE;
			_LOG();
			_LOG( '**TESTING**' );
			_LOG( '**Simulating data sent from PayPal**' );
			_LOG();

			//	Check order exists
			$_order = $this->shop_order_model->get_by_ref( $this->input->get( 'ref' ) );

			if ( ! $_order ) :

				_LOG( 'Invalid order reference, aborting.' );
				_LOG( '- - - - - - - - - - - - - - - - - - -' );
				_LOG();

				return;

			endif;

			// --------------------------------------------------------------------------

			$_paypal					= array();
			$_paypal['payment_type']	= 'instant';
			$_paypal['invoice']			= $_order->ref;
			$_paypal['custom']			=  $this->encrypt->encode( md5( $_order->ref . ':' . $_order->code ), APP_PRIVATE_KEY );
			$_paypal['txn_id']			= 'TEST:' . random_string( 'alpha', 6 );
			$_paypal['txn_type']		= 'cart';
			$_paypal['payment_status']	= 'Completed';
			$_paypal['pending_reason']	= 'PaymentReview';
			$_paypal['mc_fee']			= 0.00;

		else :

			_LOG( 'Validating the IPN call' );
			$this->load->library( 'paypal' );

			$_ipn		= $this->paypal->validate_ipn();
			$_paypal	= $this->input->post();

			$_order = $this->shop_order_model->get_by_ref( $this->input->post( 'invoice' ) );

			if ( ! $_order ) :

				_LOG( 'Invalid order ID, aborting. Likely a transaction not initiated by the site.' );
				_LOG( '- - - - - - - - - - - - - - - - - - -' );
				_LOG();

				return;

			endif;

		endif;

		// --------------------------------------------------------------------------

		//	Did the IPN validate?
		if ( $_ipn ) :

			_LOG( 'IPN Verified with PayPal' );
			_LOG();

			// --------------------------------------------------------------------------

			//	Extra verification step, check the 'custom' variable decodes appropriately
			_LOG( 'Verifying data' );
			_LOG();

			$_verification = $this->encrypt->decode( $_paypal['custom'], APP_PRIVATE_KEY );

			if ( $_verification != md5( $_order->ref . ':' . $_order->code ) ) :

				$_data = array(
					'pp_txn_id'	=> $_paypal['txn_id']
				);
				$this->shop_order_model->fail( $_order->id, $_data );

				_LOG( 'Order failed secondary verification, aborting.' );
				_LOG( '- - - - - - - - - - - - - - - - - - -' );
				_LOG();

				// --------------------------------------------------------------------------

				//	Inform developers
				send_developer_mail( 'An IPN request failed', 'An IPN request was made which failed secondary verification, Order: ' . $_paypal['invoice'] );

				return;

			endif;

			// --------------------------------------------------------------------------

			//	Only bother to handle certain types
			//	TODO: handle refunds
			_LOG( 'Checking txn_type is supported' );
			_LOG();

			if ( $_paypal['txn_type'] != 'cart' ) :

				_LOG( '"' . $_paypal['txn_type'] . '" is not a supported PayPal txn_type, gracefully aborting.' );
				_LOG( '- - - - - - - - - - - - - - - - - - -' );
				_LOG();

				return;

			endif;

			// --------------------------------------------------------------------------

			//	Check if order has already been processed
			_LOG( 'Checking if order has already been processed' );
			_LOG();

			if ( strtoupper( ENVIRONMENT ) == 'PRODUCTION' && $_order->status != 'UNPAID' ) :

				_LOG( 'Order has already been processed, aborting.' );
				_LOG( '- - - - - - - - - - - - - - - - - - -' );
				_LOG();

				return;

			elseif ( strtoupper( ENVIRONMENT ) != 'PRODUCTION' && $_order->status != 'UNPAID' ) :

				_LOG( 'Order has already been processed, but not on production so continuing anyway.' );
				_LOG();

			endif;

			// --------------------------------------------------------------------------

			//	Check the status of the payment
			_LOG( 'Checking the status of the payment' );
			_LOG();


			switch( strtolower( $_paypal['payment_status'] ) ) :


				case 'completed' :

					//	Do nothing, this transaction is OK
					_LOG( 'Payment status is "completed"; continuing...' );

				break;

				// --------------------------------------------------------------------------

				case 'reversed' :

					//	Transaction was cancelled, mark order as FAILED
					_LOG( 'Payment was reversed, marking as failed and aborting' );

					$_data = array(
						'pp_txn_id'	=> $_paypal['txn_id']
					);
					$this->shop_order_model->fail( $_order->id, $_data );

				break;

				// --------------------------------------------------------------------------

				case 'pending' :

					//	Check the pending_reason, if it's 'paymentreview' then gracefully stop
					//	processing; PayPal will send a further IPN once the payment is complete

					_LOG( 'Payment status is "pending"; check the reason.' );

					if ( strtolower( $_paypal['pending_reason'] ) == 'paymentreview' ) :

						//	The transaction is pending review, gracefully stop proicessing, but don't cancel the order
						_LOG( 'Payment is pending review by PayPal, gracefully aborting just now.' );
						$this->shop_order_model->pending( $_order->id );
						return;

					else :

						_LOG( 'Unsupported payment reason "' . $_paypal['pending_reason'] . '", aborting.' );

						// --------------------------------------------------------------------------

						$_data = array(
							'pp_txn_id'	=> $_paypal['txn_id']
						);
						$this->shop_order_model->fail( $_order->id, $_data );

						// --------------------------------------------------------------------------

						//	Inform developers
						send_developer_mail( 'A PayPal payment failed', '<strong>' . $_order->user->first_name . ' ' . $_order->user->last_name . ' (' . $_order->user->email . ')</strong> has just attempted to pay for order ' . $_order->ref . '. The payment failed with status "' . $_paypal['payment_status'] . '" and reason "' . $_paypal['pending_reason'] . '".' );
						return;


					endif;

					// --------------------------------------------------------------------------

					return;

				break;

				// --------------------------------------------------------------------------

				default :

					//	Unknown/invalid payment status
					_LOG( 'Invalid payment status' );

					$_data = array(
						'pp_txn_id'	=> $_paypal['txn_id']
					);
					$this->shop_order_model->fail( $_order->id, $_data );

					// --------------------------------------------------------------------------

					//	Inform developers
					send_developer_mail( 'A PayPal payment failed', '<strong>' . $_order->user->first_name . ' ' . $_order->user->last_name . ' (' . $_order->user->email . ')</strong> has just attempted to pay for order ' . $_order->ref . '. The payment failed with status "' . $_paypal['payment_status'] . '" and reason "' . $_paypal['pending_reason'] . '".' );
					return;

				break;

			endswitch;

			// --------------------------------------------------------------------------

			//	All seems good, continue with order processing
			_LOG( 'All seems well, continuing...' );
			_LOG();

			_LOG( 'Setting txn_id (' . $_paypal['txn_id'] . ') and fees_deducted (' . $_paypal['mc_fee'] . ').' );
			_LOG();

			$_data = array(
				'pp_txn_id'		=> $_paypal['txn_id'],
				'fees_deducted'	=> $_paypal['mc_fee']
			);
			$this->shop_order_model->paid( $_order->id, $_data );

			// --------------------------------------------------------------------------

			//	PROCESSSSSS...
			$this->shop_order_model->process( $_order );
			_LOG();

			// --------------------------------------------------------------------------

			//	Send a receipt to the customer
			_LOG( 'Sending receipt to customer: ' . $_order->user->email );
			$this->shop_order_model->send_receipt( $_order );
			_LOG();

			// --------------------------------------------------------------------------

			//	Send a notification to the store owner(s)
			_LOG( 'Sending notification to store owner(s): ' . notification( 'notify_order', 'shop' ) );
			$this->shop_order_model->send_order_notification( $_order );

			// --------------------------------------------------------------------------

			if ( $_order->voucher ) :

				//	Redeem the voucher, if it's there
				_LOG( 'Redeeming voucher: ' . $_order->voucher->code . ' - ' . $_order->voucher->label );
				$this->shop_voucher_model->redeem( $_order->voucher->id, $_order );

			endif;

			// --------------------------------------------------------------------------

			_LOG();

			// --------------------------------------------------------------------------

			_LOG( 'All done here, going back to sleep...' );
			_LOG( '- - - - - - - - - - - - - - - - - - -' );
			_LOG();

			if ( $this->data['testing'] ) :

				echo anchor( $this->_shop_url . 'checkout/processing?ref=' . $_order->ref, 'Continue to Processing Page' );

			endif;

		else :

			_LOG( 'PayPal did not verify this IPN call, aborting.' );
			_LOG( '- - - - - - - - - - - - - - - - - - -' );
			_LOG();

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Determines whether the IPN is in a testing mode or not
	 * @return bool
	 */
	protected function _notify_is_testing()
	{
		if ( strtoupper( ENVIRONMENT ) == 'PRODUCTION' ) :

			return FALSE;

		endif;

		// --------------------------------------------------------------------------

		if ( $this->input->get( 'testing' ) && $this->input->get( 'ref' ) ) :

			return TRUE;

		else :

			return FALSE;

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