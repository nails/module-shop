<?php

/**
 * This model manages Shop Payment agteways and integrates OmniPay
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

use Nails\Factory;
use Omnipay\Common;
use Omnipay\Common\CreditCard;
use Omnipay\Omnipay;

class NAILS_Shop_payment_gateway_model extends NAILS_Model
{
    protected $supported;
    protected $isRedirect;
    protected $checkoutSessionKey;
    protected $oLogger;
    protected $oCurrencyModel;

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
         * In order to qualify for "supported" status, doPayment() needs to know
         * how to handle the checkout procedure and Admin settings needs to know how
         * to gather the production and staging credentials.
         */

        $this->supported   = array();
        $this->supported[] = 'WorldPay';
        $this->supported[] = 'Stripe';
        $this->supported[] = 'PayPal_Express';

        // --------------------------------------------------------------------------

        //  These gateways use redirects rather than inline card details
        $this->isRedirect   = array();
        $this->isRedirect[] = 'WorldPay';
        $this->isRedirect[] = 'PayPal_Express';

        // --------------------------------------------------------------------------

        $this->checkoutSessionKey = 'nailsshopcheckoutorder';

        // --------------------------------------------------------------------------

        $this->oLogger        = Factory::service('Logger');
        $this->oCurrencyModel = Factory::model('Currency', 'nailsapp/module-shop');
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of payment gateways available to the system.
     * @return array
     */
    public function getAvailable()
    {
        // Available to the system
        $available = Omnipay::find();
        $out       = array();

        foreach ($available as $gateway) {

            if (array_search($gateway, $this->supported) !== false) {

                $out[] = $gateway;
            }
        }

        asort($out);

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns a list of Gateways which are enabled in the database and also
     * available to the system.
     * @return array
     */
    public function getEnabled()
    {
        $available = $this->getAvailable();
        $enabled   = array_filter((array) appSetting('enabled_payment_gateways', 'shop'));
        $out       = array();

        foreach ($enabled as $gateway) {

            if (array_search($gateway, $available) !== false) {

                $out[] = $gateway;
            }
        }

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns enabled payment gateways as a formatted array
     * @return array
     */
    public function getEnabledFormatted()
    {
        $enabledPaymentGateways = $this->getEnabled();
        $paymentGateways        = array();

        foreach ($enabledPaymentGateways as $pg) {

            $temp              = new \stdClass();
            $temp->slug        = $this->shop_payment_gateway_model->getCorrectCasing($pg);
            $temp->label       = appSetting('omnipay_' . $temp->slug . '_customise_label', 'shop');
            $temp->img         = appSetting('omnipay_' . $temp->slug . '_customise_img', 'shop');
            $temp->is_redirect = $this->isRedirect($pg);

            if (empty($temp->label)) {

                $temp->label = str_replace('_', ' ', $temp->slug);
                $temp->label = ucwords($temp->label);
            }

            $paymentGateways[] = $temp;
        }

        return $paymentGateways;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the correct casing for a payment gateway
     * @param  string $gatewayName The payment gateway to retrieve
     * @return mixed               String on success, null on failure
     */
    public function getCorrectCasing($gatewayName)
    {
        $gateways = $this->getAvailable();
        $name     = null;

        foreach ($gateways as $gateway) {

            if (trim(strtolower($gatewayName)) == strtolower($gateway)) {

                $name = $gateway;
                break;
            }
        }

        return $name;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns any assets which the gateway requires for checkout
     * @param  string $gateway The name of the gateway to check for
     * @return array
     */
    public function getCheckoutAssets($gateway)
    {
        $gatewayName = $this->getCorrectCasing($gateway);

        $assets             = array();
        $assets['Stripe']   = array();
        $assets['Stripe'][] = array('https://js.stripe.com/v2/', 'APP', 'JS');
        $assets['Stripe'][] = array('window.NAILS.SHOP_Checkout_Stripe_publishableKey = "' . appSetting('omnipay_Stripe_publishableKey', 'shop') . '";', 'APP', 'JS-INLINE');

        return isset($assets[$gatewayName]) ? $assets[$gatewayName] : array();
    }

    // --------------------------------------------------------------------------

    /**
     * Determines whether a gateway is available or not
     * @param  string  $gateway The gateway to check
     * @return boolean
     */
    public function isAvailable($gateway)
    {
        $gateway = $this->getCorrectCasing($gateway);

        if ($gateway) {

            //  getCorrectCasing() will return null if not a valid gateway
            return true;

        } else {

            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Determines whether a gateway is enabled or not
     * @param  string  $gateway The gateway to check
     * @return boolean
     */
    public function isEnabled($gateway)
    {
        $gateway = $this->getCorrectCasing($gateway);

        if ($gateway) {

            $enabled = $this->getEnabled();

            return in_array($gateway, $enabled);

        } else {

            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Determines whether the payment gateway is going to redirect to take card
     * details or whether the card details are taken inline.
     * @param  string  $gateway The gateway to check
     * @return boolean          Boolean on success, null on failure
     */
    public function isRedirect($gateway)
    {
        $gateway = $this->getCorrectCasing($gateway);

        if (!$gateway) {

            return null;
        }

        return in_array($gateway, $this->isRedirect);
    }

    // --------------------------------------------------------------------------

    /**
     * Attempts to make a payment for the order
     * @param  int    $orderId The order to make a payment against
     * @param  string $gateway The gateway to use
     * @return boolean
     */
    public function doPayment($orderId, $gateway)
    {
        $enabledGateways = $this->getEnabled();
        $gatewayName     = $this->getCorrectCasing($gateway);

        if (empty($gatewayName) || array_search($gatewayName, $enabledGateways) === false) {

            $this->setError('"' . $gateway . '" is not an enabled Payment Gatway.');
            return false;
        }

        $this->load->model('shop/shop_model');
        $this->load->model('shop/shop_order_model');
        $order = $this->shop_order_model->getById($orderId);

        if (!$order || $order->status != 'UNPAID') {

            $this->setError('Cannot create payment against order.');
            return false;
        }

        // --------------------------------------------------------------------------

        //  Prepare the gateway
        $gatewayPrepared = $this->prepareGateway($gatewayName);

        // --------------------------------------------------------------------------

        //  Prepare the CreditCard object (used by OmniPay)
        $data                     = array();
        $data['firstName']        = $order->user->first_name;
        $data['lastName']         = $order->user->last_name;
        $data['email']            = $order->user->email;
        $data['billingAddress1']  = $order->billing_address->line_1;
        $data['billingAddress2']  = $order->billing_address->line_2;
        $data['billingCity']      = $order->billing_address->town;
        $data['billingPostcode']  = $order->billing_address->postcode;
        $data['billingState']     = $order->billing_address->state;
        $data['billingCountry']   = $order->billing_address->country;
        $data['billingPhone']     = $order->user->telephone;
        $data['shippingAddress1'] = $order->shipping_address->line_1;
        $data['shippingAddress2'] = $order->shipping_address->line_2;
        $data['shippingCity']     = $order->shipping_address->town;
        $data['shippingPostcode'] = $order->shipping_address->postcode;
        $data['shippingState']    = $order->shipping_address->state;
        $data['shippingCountry']  = $order->shipping_address->country;
        $data['shippingPhone']    = $order->user->telephone;

        //  Any gateway specific handlers for the card object?
        Factory::helper('string');
        $methodName = 'prepareCard' . ucfirst(underscoreToCamelcase($gateway, false));

        if (method_exists($this, $methodName)) {

            $this->{$methodName}($data);
        }

        $creditCard = new CreditCard($data);

        //  And now the purchase request
        $data                  = array();
        $data['amount']        = $this->oCurrencyModel->intToFloat($order->totals->user->grand, $order->currency);
        $data['currency']      = $order->currency;
        $data['card']          = $creditCard;
        $data['transactionId'] = $order->id;
        $data['description']   = 'Payment for Order: ' . $order->ref;
        $data['clientIp']      = $this->input->ip_address();

        //  Set the relevant URLs
        $shopUrl = appSetting('url', 'shop') ? appSetting('url', 'shop') : 'shop/';
        $data['returnUrl'] = site_url($shopUrl . 'checkout/processing?ref=' . $order->ref);
        $data['cancelUrl'] = site_url($shopUrl . 'checkout/cancel?ref=' . $order->ref);
        $data['notifyUrl'] = site_url('api/shop/webhook/' . strtolower($gatewayName) . '?ref=' . $order->ref);

        //  Any gateway specific handlers for the request object?
        $methodName = 'prepareRequest' . ucfirst(underscoreToCamelcase($gateway, false));

        if (method_exists($this, $methodName)) {

            $this->{$methodName}($data, $order);
        }

        // --------------------------------------------------------------------------

        //  Attempt the purchase
        try {

            $gatewayResponse = $gatewayPrepared->purchase($data)->send();

            if ($gatewayResponse->isSuccessful()) {

                //  Payment was successful - add the payment to the order and process if required
                $this->load->model('shop/shop_order_payment_model');

                $transactionId = $gatewayResponse->getTransactionReference();

                //  First, check we've not already handled this payment. This should NOT happen.
                $payment = $this->shop_order_payment_model->getByTransactionId($transactionId, $gatewayName);

                if ($payment) {

                    showFatalError(
                        'Transaction already processed.',
                        'Transaction with id: ' . $transactionId . ' has already been processed. Order ID: ' . $order->id
                    );
                }

                //  Define the payment data
                $paymentData                   = array();
                $paymentData['order_id']       = $order->id;
                $paymentData['transaction_id'] = $transactionId;
                $paymentData['amount']         = $this->oCurrencyModel->intToFloat($order->totals->user->grand, $order->currency);
                $paymentData['currency']       = $order->currency;

                // --------------------------------------------------------------------------

                //  Add payment against the order
                $data                    = array();
                $data['order_id']        = $paymentData['order_id'];
                $data['payment_gateway'] = $gatewayName;
                $data['transaction_id']  = $paymentData['transaction_id'];
                $data['amount']          = $paymentData['amount'];
                $data['currency']        = $paymentData['currency'];
                $data['raw_get']         = $this->input->server('QUERY_STRING');
                $data['raw_post']        = @file_get_contents('php://input');

                if (!$this->shop_order_payment_model->create($data)) {

                    $subject  = 'Failed to create payment reference against order ' . $order->id;
                    $message  = 'The customer was charged but the payment failed to associate with the order. ';
                    $message .= $this->shop_order_payment_model->lastError();
                    showFatalError(
                        $subject,
                        $message
                    );
                }

                // --------------------------------------------------------------------------

                //  Update order
                if ($this->shop_order_payment_model->order_is_paid($order->id)) {

                    if (!$this->shop_order_model->paid($order->id)) {

                        $subject = 'Failed to mark order #' . $order->id . ' as paid';
                        $message = 'The transaction for this order was successfull, but I was unable to mark the order as paid.';
                        sendDeveloperMail($subject, $message);
                    }

                    // --------------------------------------------------------------------------

                    //  Process the order, i.e do any after sales stuff which needs done immediately
                    if (!$this->shop_order_model->process($order->id)) {

                        $subject = 'Failed to process order #' . $order->id . ' as paid';
                        $message = 'The transaction for this order was successfull, but I was unable to process the order.';
                        sendDeveloperMail($subject, $message);
                    }

                    // --------------------------------------------------------------------------

                    //  Send notifications to manager(s) and customer
                    $this->shop_order_model->sendOrderNotification($order->id, $paymentData, false);
                    $this->shop_order_model->sendReceipt($order->id, $paymentData, false);

                } else {

                    $this->oLogger->line('Order is partially paid.');

                    //  Send notifications to manager(s) and customer
                    $this->shop_order_model->sendOrderNotification($order->id, $paymentData, true);
                    $this->shop_order_model->sendReceipt($order->id, $paymentData, true);
                }

                return true;

            } elseif ($gatewayResponse->isRedirect()) {

                //  Redirect to offsite payment gateway
                $gatewayResponse->redirect();

            } else {

                //  Payment failed: display message to customer
                $error  = 'Our payment processor denied the transaction and did not charge you.';
                $error .= $gatewayResponse->getMessage() ? ' Reason: ' . $gatewayResponse->getMessage() : '';
                $this->setError($error);
                return false;
            }
        } catch (Exception $e) {
            $this->setError('Payment Request failed. ' . $e->getMessage());
            return false;
        }

        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * Completes a payment, called from the "confirm" page
     * @param  string   $gateway The gateway name
     * @param  stdClass $order   The order object
     * @return boolean
     */
    public function confirmCompletePayment($gateway, $order)
    {
        $gatewayName = $this->getCorrectCasing($gateway);

        if (!$gatewayName) {

            $this->setError('"' . $gateway . '" is not a valid gateway.');
            return false;
        }

        // --------------------------------------------------------------------------

        //  Payment data
        $paymentData                   = array();
        $paymentData['order_id']       = $order->id;
        $paymentData['transaction_id'] = null;
        $paymentData['amount']         = $order->totals->user->grand;
        $paymentData['currency']       = $order->currency;

        // --------------------------------------------------------------------------

        //  Complete the payment
        return $this->completePayment($gatewayName, $paymentData, $order, false);
    }

    // --------------------------------------------------------------------------

    /**
     * Completes a payment, caled from the webhook/API
     * @param  string  $gateway   The gateway name
     * @param  boolean $enableLog Whether or not to write to the log
     * @return boolean
     */
    public function webhookCompletePayment($gateway, $enableLog = false)
    {
        /**
         * Set the logger's dummy mode. If set to false calls to $this->oLogger->line()
         * will do nothing. We do this to keep the method clean and not
         * littered with conditionals.
         */

        _LOG_DUMMY_MODE(!$enableLog);

        // --------------------------------------------------------------------------

        $gatewayName = $this->getCorrectCasing($gateway);

        if (empty($gatewayName)) {

            $error = '"' . $gateway . '" is not a valid gateway.';
            $this->oLogger->line($error);
            $this->setError($error);
            return false;

        } else {

            $this->oLogger->line('Detected gateway: ' . $gatewayName);
        }

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

        $this->oLogger->line('Fetching Payment Data');
        $paymentData = $this->extractPaymentData($gatewayName);

        //  Verify ID
        if (empty($paymentData['order_id'])) {

            $error = 'Unable to extract Order ID from request.';
            $this->oLogger->line($error);
            $this->setError($error);
            return false;

        } else {

            $this->oLogger->line('Order ID: #' . $paymentData['order_id']);
        }

        //  Verify Amount
        if (empty($paymentData['amount'])) {

            $error = 'Unable to extract payment amount from request.';
            $this->oLogger->line($error);
            $this->setError($error);
            return false;

        } else {

            $this->oLogger->line('Payment Amount: ' . $paymentData['amount']);
        }

        //  Verify Currency
        if (empty($paymentData['currency'])) {

            $error = 'Unable to extract currency from request.';
            $this->oLogger->line($error);
            $this->setError($error);
            return false;

        } else {

            $this->oLogger->line('Payment Currency: ' . $paymentData['currency']);
        }

        // --------------------------------------------------------------------------

        //  Verify order exists
        $this->load->model('shop/shop_model');
        $this->load->model('shop/shop_order_model');
        $order = $this->shop_order_model->getById($paymentData['order_id']);

        if (!$order) {

            $error = 'Could not find order #' . $paymentData['order_id'] . '.';
            $this->oLogger->line($error);
            $this->setError($error);
            return false;
        }

        // --------------------------------------------------------------------------

        //  Complete the payment
        return $this->completePayment($gatewayName, $paymentData, $order, $enableLog);
    }

    // --------------------------------------------------------------------------

    /**
     * Completes payment for an order
     * @param  string  $gatewayName The gateway name
     * @param  array   $paymentData the payment data
     * @param  object  $order       The order object
     * @param  boolean $enableLog   Whether to write to the log or not
     * @return boolean
     */
    protected function completePayment($gatewayName, $paymentData, $order, $enableLog)
    {
        $gateway = $this->prepareGateway($gatewayName, $enableLog);

        try {

            $this->oLogger->line('Attempting completePurchase()');
            $gatewayResponse = $gateway->completePurchase($paymentData)->send();

        } catch (Exception $e) {

            $error = 'Payment Failed with exception: ' . $e->getMessage();
            $this->oLogger->line($error);
            $this->setError($error);
            return false;
        }

        if (!$gatewayResponse->isSuccessful()) {

            $error = 'Payment Failed with error: ' . $gatewayResponse->getMessage();
            $this->oLogger->line($error);
            $this->setError($error);
            return false;
        }

        // --------------------------------------------------------------------------

        //  Add payment against the order
        $data                    = array();
        $data['order_id']        = $paymentData['order_id'];
        $data['payment_gateway'] = $gatewayName;
        $data['transaction_id']  = $gatewayResponse->getTransactionReference();
        $data['amount']          = $paymentData['amount'];
        $data['currency']        = $paymentData['currency'];
        $data['raw_get']         = $this->input->server('QUERY_STRING');
        $data['raw_post']        = @file_get_contents('php://input');

        $this->load->model('shop/shop_order_payment_model');

        //  First check if this transaction has been dealt with before
        if (empty($data['transaction_id'])) {

            $error = 'Unable to extract payment transaction ID from request.';
            $this->oLogger->line($error);
            $this->setError($error);
            return false;

        } else {

            $this->oLogger->line('Payment Transaction ID: #' . $paymentData['transaction_id']);
        }

        $payment = $this->shop_order_payment_model->getByTransactionId($data['transaction_id'], $gatewayName);

        if ($payment) {

            $error = 'Payment with ID ' . $gatewayName . ':' . $data['transaction_id'] . ' has already been processed by this system.';
            $this->oLogger->line($error);
            $this->setError($error);
            return false;
        }

        if (!$this->shop_order_payment_model->create($data)) {

            $error = 'Failed to create payment reference. ' . $this->shop_order_payment_model->lastError();
            $this->oLogger->line($error);
            $this->setError($error);
            return false;
        }

        // --------------------------------------------------------------------------

        //  Update order
        if ($this->shop_order_payment_model->order_is_paid($order->id)) {

            $this->oLogger->line('Order is completely paid.');

            if (!$this->shop_order_model->paid($order->id)) {

                $error = 'Failed to mark order #' . $order->id . ' as PAID.';
                $this->oLogger->line($error);
                $this->setError($error);
                return false;

            } else {

                $this->oLogger->line('Marked order #' . $order->id . ' as PAID.');
            }

            // --------------------------------------------------------------------------

            //  Process the order, i.e do any after sales stuff which needs done immediately
            if (!$this->shop_order_model->process($order->id)) {

                $error = 'Failed to process order #' . $order->id . '.';
                $this->oLogger->line($error);
                $this->setError($error);
                return false;

            } else {

                $this->oLogger->line('Successfully processed order #' . $order->id);
            }

            // --------------------------------------------------------------------------

            //  Send notifications to manager(s) and customer
            $this->shop_order_model->sendOrderNotification($order->id, $paymentData, false);
            $this->shop_order_model->sendReceipt($order->id, $paymentData, false);

        } else {

            $this->oLogger->line('Order is partially paid.');

            //  Send notifications to manager(s) and customer
            $this->shop_order_model->sendOrderNotification($order->id, $paymentData, true);
            $this->shop_order_model->sendReceipt($order->id, $paymentData, true);
        }

        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * Prepares the gateway, creates a new instance and sets all the settings
     * @param  string  $gatewayName The gateway name
     * @param  boolean $enableLog   Whether to write to the log or not
     * @return object
     */
    protected function prepareGateway($gatewayName, $enableLog = false)
    {
        /**
         * Set the logger's dummy mode. If set to false calls to $this->oLogger->line()
         * will do nothing. We do this to keep the method clean and not
         * littered with conditionals.
         */

        _LOG_DUMMY_MODE(!$enableLog);
        $this->oLogger->line('Preparing "' . $gatewayName . '"');

        $gateway = Omnipay::create($gatewayName);
        $params  = $gateway->getDefaultParameters();

        foreach ($params as $param => $default) {

            $this->oLogger->line('Setting value for "omnipay_' . $gatewayName . '_' . $param . '"');
            $value = appSetting('omnipay_' . $gatewayName . '_' . $param, 'shop');
            $gateway->{'set' . ucfirst($param)}($value);
        }

        //  Testing, or no?
        $testMode = ENVIRONMENT == 'PRODUCTION' ? false : true;
        $gateway->setTestMode($testMode);

        if ($testMode) {

            $this->oLogger->line('TEST MODE');
        }

        return $gateway;
    }

    // --------------------------------------------------------------------------

    /**
     * Prepares the request object when submitting to Stripe
     * @param  array  &$data The raw request array
     * @param  object $order The order object
     * @return void
     */
    protected function prepareRequestStripe(&$data, $order)
    {
        $data['token'] = $this->input->post('stripe_token');
    }

    // --------------------------------------------------------------------------

    /**
     * Prepares the request object when submitting to PayPal Express
     * @param  array  &$data The raw request array
     * @param  object $order The order object
     * @return void
     */
    protected function prepareRequestPaypalExpress(&$data, $order)
    {
        //  Alter the return URL so we go to an intermediary page
        $shopUrl = appSetting('url', 'shop') ? appSetting('url', 'shop') : 'shop/';
        $data['returnUrl'] = site_url($shopUrl . 'checkout/confirm/paypal_express?ref=' . $order->ref);
    }

    // --------------------------------------------------------------------------

    /**
     * Extracts payment details from the response, can vary per gateway
     * @param  string $gateway The gateway name
     * @return array
     */
    protected function extractPaymentData($gateway)
    {
        Factory::helper('string');
        $methodName = 'extractPaymentData' . ucfirst(underscoreToCamelcase($gateway, false));

        if (method_exists($this, $methodName)) {

            $out = $this->{$methodName}();

        } else {

            $out                   = array();
            $out['order_id']       = null;
            $out['transaction_id'] = null;
            $out['amount']         = null;
            $out['currency']       = null;
        }

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Extracts payment details for WorldPay
     * @return array
     */
    protected function extractPaymentDataWorldpay()
    {
        $out                   = array();
        $out['order_id']       = (int) $this->input->post('cartId');
        $out['transaction_id'] = $this->input->post('transId');
        $out['currency']       = $this->input->post('currency');
        $out['amount']         = $this->oCurrencyModel->floatToInt($this->input->post('amount'), $out['currency']);

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the default parameters for a gateway
     * @param  string $gateway The gateway name
     * @return array
     */
    public function getDefaultParameters($gateway)
    {
        $gatewayName = $this->getCorrectCasing($gateway);

        if (!$gatewayName) {

            return array();
        }

        $gateway = Omnipay::create($gatewayName);

        return $gateway->getDefaultParameters();
    }

    // --------------------------------------------------------------------------

    /**
     * Saves the order ID to the session in an encrypted format
     * @param  int    $orderId   The order's ID
     * @param  string $orderRef  The order's ref
     * @param  string $orderCode The order's code
     * @return void
     */
    public function checkoutSessionSave($orderId, $orderRef, $orderCode)
    {
        $this->checkoutSessionClear();

        // --------------------------------------------------------------------------

        $hash = $orderId . ':' . $orderRef . ':' . $orderCode;
        $hash = $this->encrypt->encode($hash, APP_PRIVATE_KEY);

        $session              = array();
        $session['hash']      = $hash;
        $session['signature'] = md5($hash . APP_PRIVATE_KEY);

        $this->session->set_userdata($this->checkoutSessionKey, $session);
    }

    // --------------------------------------------------------------------------

    /**
     * Clears the order ID from the session
     * @return void
     */
    public function checkoutSessionClear()
    {
        $this->session->unset_userdata($this->checkoutSessionKey);
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches the order ID from the session, verifying it along the way
     * @return mixed INT on success false on failure.
     */
    public function checkoutSessionGet()
    {
        $hash = $this->session->userdata($this->checkoutSessionKey);

        if (is_array($hash)) {

            if (!empty($hash['hash']) && !empty($hash['signature'])) {

                if ($hash['signature'] == md5($hash['hash'] . APP_PRIVATE_KEY)) {

                    $hash = $this->encrypt->decode($hash['hash'], APP_PRIVATE_KEY);

                    if (!empty($hash)) {

                        $hash = explode(':', $hash);

                        if (count($hash) == 3) {

                            //  Return just the order ID.
                            return (int) $hash[0];

                        } else {

                            $this->setError('Wrong number of hash parts. Error #5');
                            return false;
                        }

                    } else {

                        $this->setError('Unable to decrypt hash. Error #4');
                        return false;
                    }

                } else {

                    $this->setError('Invalid signature. Error #3');
                    return false;
                }

            } else {

                $this->setError('Session data missing elements. Error #2');
                return false;
            }

        } else {

            $this->setError('Invalid session data. Error #1');
            return false;
        }
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

if (!defined('NAILS_ALLOW_EXTENSION_SHOP_PAYMENT_GATEWAY_MODEL')) {

    class Shop_payment_gateway_model extends NAILS_Shop_payment_gateway_model
    {
    }
}
