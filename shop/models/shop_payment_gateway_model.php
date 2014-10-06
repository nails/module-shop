<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Name:			shop_payment_gateway_model.php
 *
 * Description:		This model handles everything to do with OmniPay / Payment Gateways
 *
 **/

/**
 * OVERLOADING NAILS' MODELS
 *
 * Note the name of this class; done like this to allow apps to extend this class.
 * Read full explanation at the bottom of this file.
 *
 **/

//	Namespace malarky
use Omnipay\Common;
use Omnipay\Common\CreditCard;
use Omnipay\Omnipay;

class NAILS_Shop_payment_gateway_model extends NAILS_Model
{
	/**
	 * Returns an array of payment gateways available to the system.
	 * @return array
	 */
	public function get_available()
	{
		/**
		 * An array of gateways supported by Nails.
		 * In order to qualify for "supported" status, do_payment() needs to know
		 * how to handle the checkout procedure and Admin settings needs to know how
		 * to gather the production and staging credentials.
		 */
		//
		$_supported		= array();
		$_supported[]	= 'WorldPay';

		//	Available to the system
		$_available		= Omnipay::find();

		$_out = array();

		foreach( $_available AS $gateway ) :

			if ( array_search( $gateway, $_supported ) !== FALSE ) :

				$_out[] = $gateway;

			endif;

		endforeach;

		return $_out;
	}


	// --------------------------------------------------------------------------


	/**
	 * Returns a list of Gateways which are enabled in the database and also
	 * available to the system.
	 * @return array
	 */
	public function get_enabled()
	{
		$_available	= $this->get_available();
		$_enabled	= array_filter( (array) app_setting( 'enabled_payment_gateways', 'shop' ) );
		$_out		= array();

		foreach( $_enabled AS $gateway ) :

			if ( array_search( $gateway, $_available ) !== FALSE ) :

				$_out[] = $gateway;

			endif;

		endforeach;

		return $_out;
	}


	// --------------------------------------------------------------------------


	/**
	 * Returns the correct casing for a payment gateway
	 * @param  string $name The payment gateway to retrieve
	 * @return mixed        String on success, NULL on failure
	 */
	public function get_correct_casing( $name )
	{
		$_gateways	= $this->get_available();
		$_name		= NULL;

		foreach( $_gateways AS $gateway ) :

			if ( trim( strtolower( $name ) ) == strtolower( $gateway ) ) :

				$_name = $gateway;
				break;

			endif;

		endforeach;

		return $_name;
	}


	// --------------------------------------------------------------------------


	/**
	 * Attempts to make a payment for the order
	 * @param  int    $order_id The order to make a payment against.
	 * @param  string $gateway  the gateway to use.
	 * @return boolean
	 */
	public function do_payment( $order_id, $gateway )
	{
		$_enabled_gateways	= $this->get_enabled();
		$_gateway_name		= $this->get_correct_casing( $gateway );

		if ( empty( $_gateway_name ) || array_search( $_gateway_name, $_enabled_gateways ) === FALSE ) :

			$this->_set_error( '"' . $gateway . '" is not an enabled Payment Gatway.' );
			return FALSE;

		endif;

		$this->load->model( 'shop/shop_model' );
		$this->load->model( 'shop/shop_order_model' );
		$_order = $this->shop_order_model->get_by_id( $order_id );

		if ( ! $_order || $_order->status != 'UNPAID' ) :

			$this->_set_error( 'Cannot create payment against order.' );
			return FALSE;

		endif;

		// --------------------------------------------------------------------------

		//	Prepare the gateway
		$_gateway = $this->_prepare_gateway( $_gateway_name );

		// --------------------------------------------------------------------------

		//	Prepare the CreditCard object (used by OmniPay)
		$_data						= array();
		$_data['firstName']			= $_order->user->first_name;
		$_data['lastName']			= $_order->user->last_name;
		$_data['email']				= $_order->user->email;
		$_data['billingAddress1']	= $_order->billing_address->line_1;
		$_data['billingAddress2']	= $_order->billing_address->line_2;
		$_data['billingCity']		= $_order->billing_address->town;
		$_data['billingPostcode']	= $_order->billing_address->postcode;
		$_data['billingState']		= $_order->billing_address->state;
		$_data['billingCountry']	= $_order->billing_address->country;
		$_data['billingPhone']		= $_order->user->telephone;
		$_data['shippingAddress1']	= $_order->shipping_address->line_1;
		$_data['shippingAddress2']	= $_order->shipping_address->line_2;
		$_data['shippingCity']		= $_order->shipping_address->town;
		$_data['shippingPostcode']	= $_order->shipping_address->postcode;
		$_data['shippingState']		= $_order->shipping_address->state;
		$_data['shippingCountry']	= $_order->shipping_address->country;
		$_data['shippingPhone']		= $_order->user->telephone;

		$_card = new CreditCard( $_data );

		//	And now the purchase request
		$_data					= array();
		$_data['amount'] 		= $_order->totals->user->grand;
		$_data['currency']		= $_order->currency;
		$_data['card']			= $_card;
		$_data['transactionId']	= $_order->id;
		$_data['description']	= 'Payment for Order: ' . $_order->ref;
		$_data['clientIp']		= $this->input->ip_address();

		//	Set the return URL
		$_shop_url = app_setting( 'url', 'shop' ) ? app_setting( 'url', 'shop' ) : 'shop/';
		$_data['returnUrl'] = site_url( $_shop_url . 'checkout/processing/' . $_order->ref . '/' . $_order->code );

		// --------------------------------------------------------------------------

		//	Attempt the purchase
		$_response = $_gateway->purchase( $_data )->send();

		if ( $_response->isSuccessful() ) :

			//	Payment was successful
			dumpanddie($_response);
			return TRUE;

		elseif ( $_response->isRedirect() ) :

			//	Redirect to offsite payment gateway
			$_response->redirect();

		else :

			//	Payment failed: display message to customer
			$this->_set_error( 'Payment Gateway denied the transaction. ' . $_response->getMessage() );
			return FALSE;

		endif;

		// --------------------------------------------------------------------------

		return TRUE;
	}


	// --------------------------------------------------------------------------


	public function complete_payment( $gateway, $enable_log = FALSE )
	{
		/**
		 * Set the logger's dummy mode. If set to FALSE calls to _LOG()
		 * will do nothing. We do this to keep the method clean and not
		 * littered with conditionals.
		 */

		_LOG_DUMMY_MODE( !$enable_log );

		// --------------------------------------------------------------------------

		$_gateway_name = $this->get_correct_casing( $gateway );

		_LOG( 'Detected gateway: ' . $_gateway_name );

		if ( empty( $_gateway_name ) ) :

			$_error = '"' . $gateway . '" is not a valid gateway.';
			_LOG( $_error );
			$this->_set_error( $_error );
			return FALSE;

		endif;

		// --------------------------------------------------------------------------

		/**
		 * Big OmniPay Hack
		 * ================
		 *
		 * It staggers me there's no way to retrieve data like the original transactionId
		 * in OmniPay. [This thread](https://github.com/thephpleague/omnipay/issues/204)
		 * on GitHub, possibly explains their reasoning for not including an official
		 * mechanism. So, until there's an official solution I'll have to roll something
		 * a little hacky.
		 *
		 * For each gateway that Nails supports we need to manually extract data.
		 * Totally foul.
		 */

		_LOG( 'Fetching Payment Data' );
		$_payment_data = $this->_extract_payment_data( $_gateway_name );

		//	Verify ID
		if ( empty( $_payment_data['order_id'] ) ) :

			$_error = 'Unable to extract Order ID from request.';
			_LOG( $_error );
			$this->_set_error( $_error );
			return FALSE;

		else :

			_LOG( 'Order ID: #' . $_payment_data['order_id'] );

		endif;

		//	Verify Amount
		if ( empty( $_payment_data['amount'] ) ) :

			$_error = 'Unable to extract payment amount from request.';
			_LOG( $_error );
			$this->_set_error( $_error );
			return FALSE;

		else :

			_LOG( 'Payment Amount: #' . $_payment_data['amount'] );

		endif;

		//	Verify Currency
		if ( empty( $_payment_data['currency'] ) ) :

			$_error = 'Unable to extract currency from request.';
			_LOG( $_error );
			$this->_set_error( $_error );
			return FALSE;

		else :

			_LOG( 'Payment Currency: #' . $_payment_data['currency'] );

		endif;

		//	Verify Transaction ID
		if ( empty( $_payment_data['transaction_id'] ) ) :

			$_error = 'Unable to extract payment transaction ID from request.';
			_LOG( $_error );
			$this->_set_error( $_error );
			return FALSE;

		else :

			_LOG( 'Payment Transaction ID: #' . $_payment_data['transaction_id'] );

		endif;

		// --------------------------------------------------------------------------

		//	Verify order exists
		$this->load->model( 'shop/shop_model' );
		$this->load->model( 'shop/shop_order_model' );
		$_order = $this->shop_order_model->get_by_id( $_payment_data['order_id'] );

		if ( ! $_order  ) :

			$_error = 'Could not find order #' . $_payment_data['order_id'] . '.';
			_LOG( $_error );
			$this->_set_error( $_error );
			return FALSE;

		endif;

		// --------------------------------------------------------------------------

		//	Check this payment
		$this->load->model( 'shop/shop_order_payment_model' );

		//	First, check we've not already handled this payment
		$_payment = $this->shop_order_payment_model->get_by_transaction_id( $_payment_data['transaction_id'], $_gateway_name );

		if ( $_payment ) :

			$_error = 'Payment with ID ' . $_gateway_name . ':' . $_payment_data['transaction_id'] . ' has already been processed.';
			_LOG( $_error );
			$this->_set_error( $_error );
			return FALSE;

		endif;

		// --------------------------------------------------------------------------

		//	Payment verified?
		$_gateway = $this->_prepare_gateway( $_gateway_name, $enable_log );

		try
		{
			_LOG( 'Attempting completePurchase()' );
			$_response = $_gateway->completePurchase()->send();
		}
		catch ( Exception $e )
		{
			$_error = 'Payment Failed with error: ' . $e->getMessage();
			_LOG( $_error );
			$this->_set_error( $_error );
			return FALSE;
		}

		// --------------------------------------------------------------------------

		//	Add payment against the order
		$_data						= array();
		$_data['order_id']			= $_payment_data['order_id'];
		$_data['payment_gateway']	= $_gateway_name;
		$_data['transaction_id']	= $_payment_data['transaction_id'];
		$_data['amount']			= $_payment_data['amount'];
		$_data['currency']			= $_payment_data['currency'];
		$_data['raw_get']			= $this->input->server( 'QUERY_STRING' );
		$_data['raw_post']			= @file_get_contents( 'php://input' );

		$_result = $this->shop_order_payment_model->create( $_data );

		if ( $_payment ) :

			$_error = 'Failed to create payment reference. ' . $this->shop_order_payment_model->last_error();
			_LOG( $_error );
			$this->_set_error( $_error );
			return FALSE;

		endif;

		// --------------------------------------------------------------------------

		//	Update order
		if ( $this->shop_order_payment_model->order_is_paid( $_order->id ) ) :

			_LOG( 'Order is completely paid.' );

			if ( ! $this->shop_order_model->paid( $_order->id ) ) :

				$_error = 'Failed to mark order #' . $_order->id . ' as PAID.';
				_LOG( $_error );
				$this->_set_error( $_error );
				return FALSE;

			else :

				_LOG( 'Marked order #' . $_order->id . ' as PAID.' );

			endif;

			// --------------------------------------------------------------------------

			//	Process the order, i.e do any after sales stuff which needs done immediately
			if ( ! $this->shop_order_model->process( $_order->id ) ) :

				$_error = 'Failed to process order #' . $_order->id . '.';
				_LOG( $_error );
				$this->_set_error( $_error );
				return FALSE;

			else :

				_LOG( 'Successfully processed order #' . $_order->id );

			endif;

			// --------------------------------------------------------------------------

			//	Send notifications to manager(s) and customer
			$this->shop_order_model->send_order_notification( $_order, $_payment_data, FALSE );
			$this->shop_order_model->send_receipt( $_order, $_payment_data, FALSE );

		else :

			_LOG( 'Order is partially paid.' );

			//	Send notifications to manager(s) and customer
			$this->shop_order_model->send_order_notification( $_order, $_payment_data, TRUE );
			$this->shop_order_model->send_receipt( $_order, $_payment_data, TRUE );

		endif;

		// --------------------------------------------------------------------------

		return TRUE;
	}


	// --------------------------------------------------------------------------


	protected function _prepare_gateway( $gateway_name, $enable_log = FALSE )
	{
		/**
		 * Set the logger's dummy mode. If set to FALSE calls to _LOG()
		 * will do nothing. We do this to keep the method clean and not
		 * littered with conditionals.
		 */

		_LOG_DUMMY_MODE( !$enable_log );
		_LOG( 'Preparing "' . $gateway_name . '"' );

		$_gateway	= Omnipay::create( $gateway_name );
		$_params	= $_gateway->getDefaultParameters();

		foreach ( $_params AS $param => $default ) :

			_LOG( 'Setting value for "omnipay_' . $gateway_name . '_' . $param . '"' );
			$_value = app_setting( 'omnipay_' . $gateway_name . '_' . $param,	'shop' );
			$_gateway->{'set' . ucfirst( $param )}( $_value );

		endforeach;

		// --------------------------------------------------------------------------

		//	Testing, or no?
		$_test_mode = ENVIRONMENT == 'PRODUCTION' ? FALSE : TRUE;
		$_gateway->setTestMode( $_test_mode );

		if ( $_test_mode ) :

			_LOG( 'TEST MODE' );

		endif;

		// --------------------------------------------------------------------------

		return $_gateway;
	}


	// --------------------------------------------------------------------------


	protected function _extract_payment_data( $gateway )
	{
		$_out					= array();
		$_out['order_id']		= NULL;
		$_out['transaction_id']	= NULL;
		$_out['amount']			= NULL;
		$_out['currency']		= NULL;

		if ( method_exists( $this, '_extract_payment_data_' . strtolower( $gateway ) ) ) :

			$_out = $this->{'_extract_payment_data_' . strtolower( $gateway )}();

		endif;

		return $_out;
	}


	// --------------------------------------------------------------------------


	protected function _extract_payment_data_worldpay()
	{
		$_out					= array();
		$_out['order_id']		= (int) $this->input->post( 'cartId' );
		$_out['transaction_id']	= $this->input->post( 'transId' );
		$_out['amount']			= (float) $this->input->post( 'amount' );
		$_out['currency']		= $this->input->post( 'currency' );

		return $_out;
	}
}


// --------------------------------------------------------------------------


/**
 * OVERLOADING NAILS' MODELS
 *
 * The following block of code makes it simple to extend one of the core shop
 * models. Some might argue it's a little hacky but it's a simple 'fix'
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

if ( ! defined( 'NAILS_ALLOW_EXTENSION_SHOP_PAYMENT_GATEWAY_MODEL' ) ) :

	class Shop_payment_gateway_model extends NAILS_Shop_payment_gateway_model
	{
	}

endif;

/* End of file shop_payment_gateway_model.php */
/* Location: ./modules/shop/models/shop_payment_gateway_model.php */