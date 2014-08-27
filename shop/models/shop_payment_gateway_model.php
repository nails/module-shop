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
	 * Attempts to make a payment for the order
	 * @param  int    $order_id The order to make a payment against.
	 * @param  string $gateway  the gateway to use.
	 * @return boolean
	 */
	public function do_payment( $order_id, $gateway )
	{
		$_enabled_gateways = $this->get_enabled();

		if ( array_search( $gateway, $_enabled_gateways ) === FALSE ) :

			$this->_set_error( '"' . $gateway . '" is not an enabled Payment Gatway.' );
			return FALSE;

		endif;

		$_order = $this->shop_order_model->get_by_id( $order_id );

		if ( ! $_order || $_order->status != 'UNPAID' ) :

			$this->_set_error( 'Cannot create payment against order.' );
			return FALSE;

		endif;

		// --------------------------------------------------------------------------

		//	Prepare the gateway
		if ( method_exists( $this, '_prepare_gateway_' . strtolower( $gateway ) ) ) :

			$_gateway = $this->{'_prepare_gateway_' . strtolower( $gateway )}();

		else :

			$this->_set_error( '"' . $gateway . '" is not a supported gateway.' );
			log_message( 'error', '"' . $gateway . '" is considered supported but no handling method was found.' );
			return FALSE;

		endif;

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
		$_data['shippingAddress1']	= $_order->shipping_address->line_1;;
		$_data['shippingAddress2']	= $_order->shipping_address->line_2;;
		$_data['shippingCity']		= $_order->shipping_address->town;;
		$_data['shippingPostcode']	= $_order->shipping_address->postcode;;
		$_data['shippingState']		= $_order->shipping_address->state;;
		$_data['shippingCountry']	= $_order->shipping_address->country;;

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
		$_data['returnUrl']		= site_url( $_shop_url . 'checkout/processing/' . $_order->ref . '/' . $_order->code );

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


	protected function _prepare_gateway_worldpay()
	{
		//	Fetch credentials
		$_installation_id	= app_setting( 'omnipay_WorldPay_installationId',	'shop' );
		$_account_id		= app_setting( 'omnipay_WorldPay_accountId', 		'shop' );
		$_secret_word		= app_setting( 'omnipay_WorldPay_secretWord',		'shop' );
		$_callback_password	= app_setting( 'omnipay_WorldPay_callbackPassword',	'shop' );

		//	Testing, or no?
		$_test_mode = ENVIRONMENT == 'PRODUCTION' ? FALSE : TRUE;

		// --------------------------------------------------------------------------

		if ( empty( $_installation_id ) ) :

			show_fatal_error( 'WorldPay is not configured correctly', 'Attempted payment using WorldPay but credentials are missing.' );

		endif;

		// --------------------------------------------------------------------------

		$_gateway = Omnipay::create( 'WorldPay' );
		$_gateway->setInstallationId( $_installation_id );
		$_gateway->setAccountId( $_account_id );
		$_gateway->setSecretWord( $_account_id );
		$_gateway->setCallbackPassword( $_account_id );
		$_gateway->setTestMode( $_test_mode );

		// --------------------------------------------------------------------------

		return $_gateway;
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