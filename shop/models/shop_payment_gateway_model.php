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
	protected $_supported;
	protected $_checkout_session_key;

	// --------------------------------------------------------------------------


	/**
	 * Construct the model
	 */
	public function __construct()
	{
		parent::__construct();

		// --------------------------------------------------------------------------

		/**
		 * An array of gateways supported by Nails.
		 * ========================================
		 *
		 * In order to qualify for "supported" status, do_payment() needs to know
		 * how to handle the checkout procedure and Admin settings needs to know how
		 * to gather the production and staging credentials.
		 */

		$this->_supported	= array();
		$this->_supported[]	= 'WorldPay';
		$this->_supported[]	= 'Stripe';
		$this->_supported[]	= 'PayPal_Express';

		// --------------------------------------------------------------------------

		//	These gateways use redirects rather than inline card details
		$this->_is_redirect		= array();
		$this->_is_redirect[]	= 'WorldPay';
		$this->_is_redirect[]	= 'PayPal_Express';

		// --------------------------------------------------------------------------

		$this->_checkout_session_key = 'nailsshopcheckoutorder';
	}


	// --------------------------------------------------------------------------


	/**
	 * Returns an array of payment gateways available to the system.
	 * @return array
	 */
	public function get_available()
	{
		//	Available to the system
		$_available	= Omnipay::find();
		$_out		= array();

		foreach( $_available AS $gateway ) :

			if ( array_search( $gateway, $this->_supported ) !== FALSE ) :

				$_out[] = $gateway;

			endif;

		endforeach;

		asort( $_out );

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


	public function get_enabled_formatted()
	{
		$_enabled_payment_gateways	= $this->get_enabled();
		$_payment_gateways			= array();

		foreach( $_enabled_payment_gateways AS $pg ) :

			$_temp				= new stdClass();
			$_temp->slug		= $this->shop_payment_gateway_model->get_correct_casing( $pg );
			$_temp->label		= app_setting( 'omnipay_' . $_temp->slug . '_customise_label', 'shop' );
			$_temp->img			= app_setting( 'omnipay_' . $_temp->slug . '_customise_img', 'shop' );
			$_temp->is_redirect = $this->shop_payment_gateway_model->is_redirect( $pg );

			//	Sort label
			if ( empty( $_temp->label ) ) :

				$_temp->label = str_replace( '_', ' ', $_temp->slug );
				$_temp->label = ucwords( $_temp->label );

			endif;

			$_payment_gateways[] = $_temp;

		endforeach;

		return $_payment_gateways;
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
	 * Returns any assets which the gateway requires for checkout
	 * @param  string $gateway The name of the gateway to check for
	 * @return array
	 */
	public function get_checkout_assets( $gateway )
	{
		$_gateway_name = $this->get_correct_casing( $gateway );

		$_assets				= array();
		$_assets['Stripe']		= array();
		$_assets['Stripe'][]	= array( 'https://js.stripe.com/v2/', 'APP', 'JS' );
		$_assets['Stripe'][]	= array( 'window.NAILS.SHOP_Checkout_Stripe_publishableKey = "' . app_setting( 'omnipay_Stripe_publishableKey', 'shop' ) . '";', 'APP', 'JS-INLINE' );

		return isset( $_assets[$_gateway_name] ) ? $_assets[$_gateway_name] : array();
	}


	// --------------------------------------------------------------------------


	/**
	 * Determines whether a gateway is available or not
	 * @param  string  $gateway The gateway to check
	 * @return boolean
	 */
	public function is_available( $gateway )
	{
		$_gateway = $this->get_correct_casing( $gateway );

		if ( $_gateway ) :

			//	get_correct_casing() will return NULL if not a valid gateway
			return TRUE;

		else :

			return FALSE;

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Determines whether a gateway is enabled or not
	 * @param  string  $gateway The gateway to check
	 * @return boolean
	 */
	public function is_enabled( $gateway )
	{
		$_gateway = $this->get_correct_casing( $gateway );

		if ( $_gateway ) :

			$_enabled = $this->get_enabled();

			return in_array( $_gateway, $_enabled );

		else :

			return FALSE;

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Determines whether the payment gateway is going to redirect to take card
	 * details or whether the card details are taken inline.
	 * @param  string  $gateway The gateway to check
	 * @return boolean          Boolean on success, NULL on failure
	 */
	public function is_redirect( $gateway )
	{
		$_gateway = $this->get_correct_casing( $gateway );

		if ( ! $_gateway ) :

			return NULL;

		endif;

		return in_array( $gateway, $this->_is_redirect );
	}


	// --------------------------------------------------------------------------


	/**
	 * Attempts to make a payment for the order
	 * @param  int    $order_id The order to make a payment against
	 * @param  string $gateway  The gateway to use
	 * @param  array  $raw_data The Raw data of the request
	 * @return boolean
	 */
	public function do_payment( $order_id, $gateway, $raw_data )
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

		//	Any gateway specific handlers for the card object?
		if ( method_exists( $this, '_prepare_card_' . strtolower( $gateway ) ) ) :

			$this->{'_prepare_card_' . strtolower( $gateway )}( $_data );

		endif;

		$_card = new CreditCard( $_data );

		//	And now the purchase request
		$_data					= array();
		$_data['amount'] 		= $_order->totals->user->grand;
		$_data['currency']		= $_order->currency;
		$_data['card']			= $_card;
		$_data['transactionId']	= $_order->id;
		$_data['description']	= 'Payment for Order: ' . $_order->ref;
		$_data['clientIp']		= $this->input->ip_address();

		//	Set the relevant URLs
		$_shop_url = app_setting( 'url', 'shop' ) ? app_setting( 'url', 'shop' ) : 'shop/';
		$_data['returnUrl'] = site_url( $_shop_url . 'checkout/processing?ref=' . $_order->ref );
		$_data['cancelUrl'] = site_url( $_shop_url . 'checkout/cancel?ref=' . $_order->ref );
		$_data['notifyUrl'] = site_url( 'api/shop/webhook/' . strtolower( $_gateway_name ) . '?ref=' . $_order->ref );

		//	Any gateway specific handlers for the request object?
		if ( method_exists( $this, '_prepare_request_' . strtolower( $gateway ) ) ) :

			$this->{'_prepare_request_' . strtolower( $gateway )}( $_data, $_order );

		endif;

		// --------------------------------------------------------------------------

		//	Attempt the purchase
		try
		{
			$_response = $_gateway->purchase( $_data )->send();

			if ( $_response->isSuccessful() ) :

				//	Payment was successful - add the payment to the order and process if required
				$this->load->model( 'shop/shop_order_payment_model' );

				$_transaction_id = $_response->getTransactionReference();

				//	First, check we've not already handled this payment. This should NOT happen.
				$_payment = $this->shop_order_payment_model->get_by_transaction_id( $_transaction_id, $_gateway_name );

				if ( $_payment ) {

					showFatalError('Transaction already processed.', 'Transaction with id: ' . $_transaction_id . ' has already been processed. Order ID: ' . $_order->id);
				}

				//	Define the payment data
				$_payment_data						= array();
				$_payment_data['order_id']			= $_order->id;
				$_payment_data['transaction_id']	= $_transaction_id;
				$_payment_data['amount']			= $_order->totals->user->grand;
				$_payment_data['currency']			= $_order->currency;

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

				//	Bad news, fall over
				if ( $_payment ) {

					showFatalError('Failed to create payment reference against order ' . $_order->id, 'The customer was charged but the payment failed to associate with the order. ' . $this->shop_order_payment_model->last_error());
				}

				// --------------------------------------------------------------------------

				//	Update order
				if ( $this->shop_order_payment_model->order_is_paid( $_order->id ) ) :

					if ( ! $this->shop_order_model->paid( $_order->id ) ) :

						sendDeveloperMail('Failed to mark order #' . $_order->id . ' as paid', 'The transaction for this order was successfull, but I was unable to mark the order as paid.');

					endif;

					// --------------------------------------------------------------------------

					//	Process the order, i.e do any after sales stuff which needs done immediately
					if ( ! $this->shop_order_model->process( $_order->id ) ) :

						sendDeveloperMail('Failed to process order #' . $_order->id . ' as paid', 'The transaction for this order was successfull, but I was unable to processthe order.');

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

				return TRUE;

			elseif ( $_response->isRedirect() ) :

				//	Redirect to offsite payment gateway
				$_response->redirect();

			else :

				//	Payment failed: display message to customer
				$_error  = 'Our payment processor denied the transaction and did not charge you.';
				$_error .= $_response->getMessage() ? ' Reason: ' . $_response->getMessage() : '';
				$this->_set_error( $_error );
				return FALSE;

			endif;
		}
		catch( Exception $e )
		{
			$this->_set_error( 'Payment Request failed. ' . $e->getMessage() );
			return FALSE;
		}

		// --------------------------------------------------------------------------

		return TRUE;
	}


	// --------------------------------------------------------------------------


	public function confirm_complete_payment( $gateway, $order )
	{
		$_gateway_name = $this->get_correct_casing( $gateway );

		if ( $_gateway_name ) :

			$_gateway = $this->_prepare_gateway( $_gateway_name );


		else :

			$this->_set_error( '"' . $gateway . '" is not a valid gateway.' );
			return FALSE;

		endif;

		// --------------------------------------------------------------------------

		//	Payment data
		$_payment_data						= array();
		$_payment_data['order_id']			= $order->id;
		$_payment_data['transaction_id']	= NULL;
		$_payment_data['amount']			= $order->totals->user->grand;
		$_payment_data['currency']			= $order->currency;

		// --------------------------------------------------------------------------

		//	Complete the payment
		return $this->_complete_payment( $_gateway_name, $_payment_data, $order, FALSE );
	}


	// --------------------------------------------------------------------------


	/**
	 * Called via the webhook
	 * @param  [type]  $gateway    [description]
	 * @param  boolean $enable_log [description]
	 * @return [type]              [description]
	 */
	public function webhook_complete_payment( $gateway, $enable_log = FALSE )
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

		//	Complete the payment
		return $this->_complete_payment( $_gateway_name, $_payment_data, $_order, $enable_log );
	}


	// --------------------------------------------------------------------------


	protected function _complete_payment( $gateway_name, $payment_data, $order, $enable_log )
	{
		$_gateway = $this->_prepare_gateway( $gateway_name, $enable_log );

		try
		{
			_LOG( 'Attempting completePurchase()' );
			$_response = $_gateway->completePurchase( $payment_data )->send();
		}
		catch ( Exception $e )
		{
			$_error = 'Payment Failed with error: ' . $e->getMessage();
			_LOG( $_error );
			$this->_set_error( $_error );
			return FALSE;
		}

		if ( ! $_response ->isSuccessful() ):

			$_error = 'Payment Failed with error: ' . $_response->getMessage();
			_LOG( $_error );
			$this->_set_error( $_error );
			return FALSE;

		endif;

		// --------------------------------------------------------------------------

		//	Add payment against the order
		$_data						= array();
		$_data['order_id']			= $payment_data['order_id'];
		$_data['payment_gateway']	= $gateway_name;
		$_data['transaction_id']	= $_response->getTransactionReference();
		$_data['amount']			= $payment_data['amount'];
		$_data['currency']			= $payment_data['currency'];
		$_data['raw_get']			= $this->input->server( 'QUERY_STRING' );
		$_data['raw_post']			= @file_get_contents( 'php://input' );

		$this->load->model( 'shop/shop_order_payment_model' );

		//	First check if this transaction has been dealt with before
		if (empty($_data['transaction_id'])) {

			$_error = 'Unable to extract payment transaction ID from request.';
			_LOG($_error);
			$this->_set_error($_error);
			return false;

		} else {

			_LOG('Payment Transaction ID: #' . $_payment_data['transaction_id']);

		}

		$_payment = $this->shop_order_payment_model->get_by_transaction_id( $_data['transaction_id'], $gateway_name );

		if ( $_payment ):

			$_error = 'Payment with ID ' . $gateway_name . ':' . $_data['transaction_id'] . ' has already been processed by this system.';
			_LOG( $_error );
			$this->_set_error( $_error );
			return FALSE;

		endif;

		$_result = $this->shop_order_payment_model->create( $_data );

		if ( ! $_result ) :

			$_error = 'Failed to create payment reference. ' . $this->shop_order_payment_model->last_error();
			_LOG( $_error );
			$this->_set_error( $_error );
			return FALSE;

		endif;

		// --------------------------------------------------------------------------

		//	Update order
		if ( $this->shop_order_payment_model->order_is_paid( $order->id ) ) :

			_LOG( 'Order is completely paid.' );

			if ( ! $this->shop_order_model->paid( $order->id ) ) :

				$_error = 'Failed to mark order #' . $order->id . ' as PAID.';
				_LOG( $_error );
				$this->_set_error( $_error );
				return FALSE;

			else :

				_LOG( 'Marked order #' . $order->id . ' as PAID.' );

			endif;

			// --------------------------------------------------------------------------

			//	Process the order, i.e do any after sales stuff which needs done immediately
			if ( ! $this->shop_order_model->process( $order->id ) ) :

				$_error = 'Failed to process order #' . $order->id . '.';
				_LOG( $_error );
				$this->_set_error( $_error );
				return FALSE;

			else :

				_LOG( 'Successfully processed order #' . $order->id );

			endif;

			// --------------------------------------------------------------------------

			//	Send notifications to manager(s) and customer
			$this->shop_order_model->send_order_notification( $order, $payment_data, FALSE );
			$this->shop_order_model->send_receipt( $order, $payment_data, FALSE );

		else :

			_LOG( 'Order is partially paid.' );

			//	Send notifications to manager(s) and customer
			$this->shop_order_model->send_order_notification( $order, $payment_data, TRUE );
			$this->shop_order_model->send_receipt( $order, $payment_data, TRUE );

		endif;

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


	/**
	 * Prepares the request object when submitting to Stripe
	 * @param  array $data The raw request object
	 * @return void
	 */
	protected function _prepare_request_stripe( &$data, $order )
	{
		$data['token'] = $this->input->post( 'stripe_token' );
	}


	// --------------------------------------------------------------------------


	/**
	 * Prepares the request object when submitting to PayPal_Express
	 * @param  array $data The raw request object
	 * @return void
	 */
	protected function _prepare_request_paypal_express( &$data, $order )
	{
		//	Alter the return URL so we go to an intermediary page
		$_shop_url = app_setting( 'url', 'shop' ) ? app_setting( 'url', 'shop' ) : 'shop/';
		$data['returnUrl'] = site_url( $_shop_url . 'checkout/confirm/paypal_express?ref=' . $order->ref );
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


	// --------------------------------------------------------------------------


	/**
	 * Returns the default parameters for a gateway
	 * @param  string $gateway The gateway to get parameters for
	 * @return array
	 */
	public function get_default_params( $gateway )
	{
		$_gateway_name = $this->get_correct_casing( $gateway );

		if ( ! $_gateway_name ) :

			return array();

		endif;

		$_gateway	= Omnipay::create( $_gateway_name );

		return $_gateway->getDefaultParameters();
	}


	// --------------------------------------------------------------------------


	/**
	 * Saves the order ID to the session in an encrypted format
	 * @param  int    $order_id   The order's ID
	 * @param  string $order_ref  The order's ref
	 * @param  strong $order_code The order's code
	 * @return void
	 */
	public function checkout_session_save( $order_id, $order_ref, $order_code )
	{
		$this->checkout_session_clear();

		// --------------------------------------------------------------------------

		$_hash = $order_id . ':' . $order_ref . ':' . $order_code;
		$_hash = $this->encrypt->encode( $_hash, APP_PRIVATE_KEY );

		$_session				= array();
		$_session['hash']		= $_hash;
		$_session['signature']	= md5( $_hash . APP_PRIVATE_KEY );

		$this->session->set_userdata( $this->_checkout_session_key, $_session );
	}


	// --------------------------------------------------------------------------


	/**
	 * Clears the order ID from the session
	 * @return void
	 */
	public function checkout_session_clear()
	{
		$this->session->unset_userdata( $this->_checkout_session_key );
	}


	// --------------------------------------------------------------------------


	/**
	 * Fetches the order ID from the session, verifying it along the way
	 * @return mixed INT on success FALSE on failure.
	 */
	public function checkout_session_get()
	{
		$_hash = $this->session->userdata( $this->_checkout_session_key );

		if ( is_array( $_hash ) ) :

			if ( ! empty( $_hash['hash'] ) && ! empty( $_hash['signature'] ) ) :

				if ( $_hash['signature'] == md5( $_hash['hash'] . APP_PRIVATE_KEY ) ) :

					$_hash = $this->encrypt->decode( $_hash['hash'], APP_PRIVATE_KEY );

					if ( ! empty( $_hash ) ) :

						$_hash = explode( ':', $_hash );

						if ( count( $_hash ) == 3 ) :

							//	Return just the order ID.
							return (int) $_hash[0];

						else :

							$this->_set_error( 'Wrong number of hash parts. Error #5' );
							return FALSE;

						endif;

					else :

						$this->_set_error( 'Unable to decrypt hash. Error #4' );
						return FALSE;

					endif;

				else :

					$this->_set_error( 'Invalid signature. Error #3' );
					return FALSE;

				endif;

			else :

				$this->_set_error( 'Session data missing elements. Error #2' );
				return FALSE;

			endif;

		else :

			$this->_set_error( 'Invalid session data. Error #1' );
			return FALSE;

		endif;
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