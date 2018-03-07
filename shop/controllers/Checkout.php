<?php

//  Include _shop.php; executes common functionality
require_once '_shop.php';

/**
 * This class provides checkout functionality
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

use Nails\Factory;

class NAILS_Checkout extends NAILS_Shop_Controller
{
    /**
     * Construct the controller
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('shop/shop_payment_gateway_model');

        //  Load the skin to use
        $this->loadSkin('checkout');
    }

    // --------------------------------------------------------------------------

    /**
     * Handle the checkout process
     * @return void
     */
    public function index()
    {
        if ($this->maintenance->enabled) {

            $this->renderMaintenancePage();
            return;
        }

        // --------------------------------------------------------------------------

        $oCountryModel = Factory::model('Country');

        $this->data['countries_flat']   = $oCountryModel->getAllFlat();
        $this->data['payment_gateways'] = $this->shop_payment_gateway_model->getEnabledFormatted();

        if (!count($this->data['payment_gateways'])) {

            $this->data['error'] = '<strong>Error:</strong> No Payment Gateways are configured.';
            $this->data['page']->title = $this->shopName . ': No Payment Gateways have been configured';

            $oView = Factory::service('View');
            $oView->load('structure/header', $this->data);
            $oView->load($this->skin->path . 'views/checkout/no_gateway', $this->data);
            $oView->load('structure/footer', $this->data);
            return;
        }

        // --------------------------------------------------------------------------

        $basket = $this->shop_basket_model->get();

        if (empty($basket->items)) {

            $status  = 'error';
            $message = 'Sorry, you cannot checkout just now. Your basket is empty.';
            $this->session->set_flashdata($status, $message);
            redirect($this->shopUrl . 'basket');
        }

        // --------------------------------------------------------------------------

        //  Abandon any previous orders
        $previousOrder = $this->shop_payment_gateway_model->checkoutSessionGet();

        if ($previousOrder) {

            $this->shop_order_model->abandon($previousOrder);
            $this->shop_payment_gateway_model->checkoutSessionClear();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            if (!$this->shop_payment_gateway_model->isEnabled($this->input->post('payment_gateway'))) {

                $this->data['error']  = '"' . $this->input->post('payment_gateway') . '" ';
                $this->data['error'] .= 'is not a valid payment gateway.';

            } else {

                $oFormValidation = Factory::service('FormValidation');

                if ($basket->shipping->isRequired) {
                    $oFormValidation->set_rules('delivery_address_line_1', '', 'trim|required');
                    $oFormValidation->set_rules('delivery_address_line_2', '', 'trim');
                    $oFormValidation->set_rules('delivery_address_town', '', 'trim|required');
                    $oFormValidation->set_rules('delivery_address_state', '', 'trim');
                    $oFormValidation->set_rules('delivery_address_postcode', '', 'trim|required');
                    $oFormValidation->set_rules('delivery_address_country', '', 'required');
                }

                $oFormValidation->set_rules('first_name', '', 'trim|required');
                $oFormValidation->set_rules('last_name', '', 'trim|required');
                $oFormValidation->set_rules('email', '', 'trim|required');
                $oFormValidation->set_rules('telephone', '', 'trim|required');

                if (!$this->input->post('same_billing_address')) {

                    $oFormValidation->set_rules('billing_address_line_1', '', 'trim|required');
                    $oFormValidation->set_rules('billing_address_line_2', '', 'trim');
                    $oFormValidation->set_rules('billing_address_town', '', 'trim|required');
                    $oFormValidation->set_rules('billing_address_state', '', 'trim');
                    $oFormValidation->set_rules('billing_address_postcode', '', 'trim|required');
                    $oFormValidation->set_rules('billing_address_country', '', 'trim|required');

                } else {

                    $oFormValidation->set_rules('billing_address_line_1', '', 'trim');
                    $oFormValidation->set_rules('billing_address_line_2', '', 'trim');
                    $oFormValidation->set_rules('billing_address_town', '', 'trim');
                    $oFormValidation->set_rules('billing_address_state', '', 'trim');
                    $oFormValidation->set_rules('billing_address_postcode', '', 'trim');
                    $oFormValidation->set_rules('billing_address_country', '', 'trim');
                }

                $oFormValidation->set_rules('payment_gateway', '', 'trim|required');

                $oFormValidation->set_message('required', lang('fv_required'));

                if ($oFormValidation->run()) {

                    //  Prepare data
                    $aInsertData = array();

                    //  Contact details
                    $aInsertData['contact']             = new \stdClass();
                    $aInsertData['contact']->first_name = $this->input->post('first_name');
                    $aInsertData['contact']->last_name  = $this->input->post('last_name');
                    $aInsertData['contact']->email      = $this->input->post('email');
                    $aInsertData['contact']->telephone  = $this->input->post('telephone');

                    //  Delivery Details
                    $aInsertData['delivery']           = new \stdClass();
                    $aInsertData['delivery']->line_1   = $this->input->post('delivery_address_line_1');
                    $aInsertData['delivery']->line_2   = $this->input->post('delivery_address_line_2');
                    $aInsertData['delivery']->town     = $this->input->post('delivery_address_town');
                    $aInsertData['delivery']->state    = $this->input->post('delivery_address_state');
                    $aInsertData['delivery']->postcode = $this->input->post('delivery_address_postcode');
                    $aInsertData['delivery']->country  = $this->input->post('delivery_address_country');

                    //  Billing details
                    if (!$this->input->post('same_billing_address')) {

                        $aInsertData['billing']           = new \stdClass();
                        $aInsertData['billing']->line_1   = $this->input->post('billing_address_line_1');
                        $aInsertData['billing']->line_2   = $this->input->post('billing_address_line_2');
                        $aInsertData['billing']->town     = $this->input->post('billing_address_town');
                        $aInsertData['billing']->state    = $this->input->post('billing_address_state');
                        $aInsertData['billing']->postcode = $this->input->post('billing_address_postcode');
                        $aInsertData['billing']->country  = $this->input->post('billing_address_country');

                    } else {

                        $aInsertData['billing']           = new \stdClass();
                        $aInsertData['billing']->line_1   = $this->input->post('delivery_address_line_1');
                        $aInsertData['billing']->line_2   = $this->input->post('delivery_address_line_2');
                        $aInsertData['billing']->town     = $this->input->post('delivery_address_town');
                        $aInsertData['billing']->state    = $this->input->post('delivery_address_state');
                        $aInsertData['billing']->postcode = $this->input->post('delivery_address_postcode');
                        $aInsertData['billing']->country  = $this->input->post('delivery_address_country');
                    }

                    //  And the basket
                    $aInsertData['basket'] = $basket;

                    //  Generate the order and proceed to payment
                    $order = $this->shop_order_model->create($aInsertData, true);

                    if ($order) {

                        /**
                         * Order created successfully, attempt payment. We need to keep track of the order ID
                         * so that when we redirect the processing/cancel pages can pick up where we left off.
                         */

                        $this->shop_payment_gateway_model->checkoutSessionSave($order->id, $order->ref, $order->code);

                        $result = $this->shop_payment_gateway_model->doPayment(
                            $order->id,
                            $this->input->post('payment_gateway')
                        );

                        if ($result) {

                            /**
                             * Payment complete! Mark order as paid and then process it, finally send user to
                             * processing page for receipt
                             */

                            $this->shop_order_model->paid($order->id);
                            $this->shop_order_model->process($order->id);

                            $shopUrl = appSetting('url', 'nailsapp/module-shop') ? appSetting('url', 'nailsapp/module-shop') : 'shop/';
                            redirect($shopUrl . 'checkout/processing?ref=' . $order->ref);

                        } else {

                            //  Payment failed, mark this order as a failure too.
                            $this->shop_order_model->fail($order->id, $this->shop_payment_gateway_model->lastError());

                            $this->data['error']  = 'Sorry, something went wrong during checkout. ';
                            $this->data['error'] .= $this->shop_payment_gateway_model->lastError();
                            $this->data['payment_error'] = $this->shop_payment_gateway_model->lastError();

                            $this->shop_payment_gateway_model->checkoutSessionClear();
                        }

                    } else {

                        $this->data['error']  = 'Sorry, there was a problem processing your order. ';
                        $this->data['error'] .= $this->shop_order_model->lastError();
                    }

                } else {

                    $this->data['error'] = lang('fv_there_were_errors');
                }
            }
        }

        // --------------------------------------------------------------------------

        //  Load assets required by the payment gateways
        $oAsset = Factory::service('Asset');
        foreach ($this->data['payment_gateways'] as $pg) {

            $assets = $this->shop_payment_gateway_model->getCheckoutAssets($pg->slug);

            foreach ($assets as $asset) {

                $inline = array('JS-INLINE', 'CSS-INLINE');

                if (in_array($asset[2], $inline)) {
                    $oAsset->inline($asset[0], $asset[2]);
                } else {
                    $oAsset->load($asset[0], $asset[1], $asset[2]);
                }
            }
        }

        // --------------------------------------------------------------------------

        $this->data['page']->title = $this->shopName . ': Checkout';
        $this->data['basket']      = $basket;

        // --------------------------------------------------------------------------

        $oView = Factory::service('View');
        $oView->load('structure/header', $this->data);
        $oView->load($this->skin->path . 'views/checkout/index', $this->data);
        $oView->load('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Shown to the user once the payment gateway has been informed.
     * @return void
     */
    public function processing()
    {
        if ($this->maintenance->enabled) {

            $this->renderMaintenancePage();
            return;
        }

        // --------------------------------------------------------------------------

        $this->data['order'] = $this->getOrder();

        if (empty($this->data['order'])) {

            show_404();

        } else {

            //  Fetch the product/variants associated with each order item
            foreach ($this->data['order']->items as $item) {

                $item->product = $this->shop_product_model->getById($item->product_id);

                if (!empty($item->product)) {

                    //  Find the variant
                    foreach ($item->product->variations as &$v) {

                        if ($v->id == $item->variant_id) {

                            $item->variant = $v;
                            break;
                        }
                    }
                }
            }

            // --------------------------------------------------------------------------

            //  Empty the basket
            $this->shop_basket_model->destroy();

            // --------------------------------------------------------------------------

            switch ($this->data['order']->status) {

                case 'UNPAID':

                    $this->processingUnpaid();
                    break;

                case 'PAID':

                    $this->processingPaid();
                    break;

                case 'PENDING':

                    $this->processingPending();
                    break;

                case 'FAILED':

                    $this->processingFailed();
                    break;

                case 'ABANDONED':

                    $this->processingAbandoned();
                    break;

                case 'CANCELLED':

                    $this->processingCancelled();
                    break;

                default:

                    $this->processingError();
                    break;
            }
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Renders the "unpaid" processing view.
     * @return void
     */
    protected function processingUnpaid()
    {
        $oView = Factory::service('View');
        $oView->load($this->skin->path . 'views/checkout/processing/unpaid', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Renders the "pending" processing view.
     * @return void
     */
    protected function processingPending()
    {
        //  Now we know what the state of play is, clear the session.
        $this->shop_payment_gateway_model->checkoutSessionClear();

        //  And load the view
        $oView = Factory::service('View');
        $oView->load($this->skin->path . 'views/checkout/processing/pending', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Renders the "paid" processing view.
     * @return void
     */
    protected function processingPaid()
    {
        $this->data['page']->title = 'Thanks for your order!';
        $this->data['success']     = '<strong>Success!</strong> Your order has been processed.';

        // --------------------------------------------------------------------------

        //  Now we know what the state of play is, clear the session.
        $this->shop_payment_gateway_model->checkoutSessionClear();

        //  And load the view
        $oView = Factory::service('View');
        $oView->load($this->skin->path . 'views/checkout/processing/paid', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Renders the "failed" processing view.
     * @return void
     */
    protected function processingFailed()
    {
        if (!$this->data['error']) {

            $this->data['error'] = 'Sorry, there was a problem processing your order';
        }

        if (!isset($this->data['page']->title) || !$this->data['page']->title) {

            $this->data['page']->title = 'An error occurred';
        }

        // --------------------------------------------------------------------------

        //  Now we know what the state of play is, clear the session.
        $this->shop_payment_gateway_model->checkoutSessionClear();

        //  And load the view
        $oView = Factory::service('View');
        $oView->load($this->skin->path . 'views/checkout/processing/failed', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Renders the "abandoned" processing view.
     * @return void
     */
    protected function processingAbandoned()
    {
        if (!$this->data['error']) {

            $this->data['error'] = 'Sorry, there was a problem processing your order';
        }

        if (!isset($this->data['page']->title) || !$this->data['page']->title) {

            $this->data['page']->title = 'An error occurred';
        }

        // --------------------------------------------------------------------------

        //  Now we know what the state of play is, clear the session.
        $this->shop_payment_gateway_model->checkoutSessionClear();

        //  And load the view
        $oView = Factory::service('View');
        $oView->load($this->skin->path . 'views/checkout/processing/abandoned', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Renders the "cancelled" processing view.
     * @return void
     */
    protected function processingCancelled()
    {
        if (!$this->data['error']) {

            $this->data['error'] = 'Sorry, there was a problem processing your order';
        }

        if (!isset($this->data['page']->title) || !$this->data['page']->title) {

            $this->data['page']->title = 'An error occurred';
        }

        // --------------------------------------------------------------------------

        //  Now we know what the state of play is, clear the session.
        $this->shop_payment_gateway_model->checkoutSessionClear();

        //  And load the view
        $oView = Factory::service('View');
        $oView->load($this->skin->path . 'views/checkout/processing/cancelled', $this->data);
    }


    // --------------------------------------------------------------------------


    /**
     * Renders the "error" processing view.
     * @return void
     */
    protected function processingError($error = '')
    {
        if (!$this->data['error']) {

            $this->data['error'] = 'Sorry, there was a problem processing your order. ' . $error;
        }

        if (!isset($this->data['page']->title) || !$this->data['page']->title) {

            $this->data['page']->title = 'An error occurred';
        }

        // --------------------------------------------------------------------------

        //  Now we know what the state of play is, clear the session.
        $this->shop_payment_gateway_model->checkoutSessionClear();

        //  And load the view
        $oView = Factory::service('View');
        $oView->load($this->skin->path . 'views/checkout/processing/error', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Marks an order as cancelled and redirects the user to the basket with feedback.
     * @return void
     */
    public function cancel()
    {
        if ($this->maintenance->enabled) {

            $this->renderMaintenancePage();
            return;
        }

        // --------------------------------------------------------------------------

        $order = $this->getOrder(false);

        if (empty($order)) {

            show_404();
        }

        //  Can't cancel an order which has been paid
        if ($order->status == 'PAID') {

            $status   = 'error';
            $message  = '<strong>Order cannot be cancelled.</strong><br />that order has already been paid ';
            $message .= 'and cannot be cancelled.';

        } else {

            $this->shop_order_model->cancel($order->id);

            $status   = 'message';
            $message  = '<strong>Checkout was cancelled.</strong><br />At your request, we cancelled checkout - ';
            $message .= 'you have not been charged.';
        }

        $this->session->set_flashdata($status, $message);
        redirect($this->shopUrl . 'basket');
    }

    // --------------------------------------------------------------------------

    public function confirm()
    {
        if ($this->maintenance->enabled) {

            $this->renderMaintenancePage();
            return;
        }

        // --------------------------------------------------------------------------

        $order = $this->getOrder();

        if (empty($order)) {

            show_404();
        }

        $result = $this->shop_payment_gateway_model->confirmCompletePayment($this->uri->rsegment(3), $order);

        if ($result) {

            redirect($this->shopUrl . 'checkout/processing?ref=' . $order->ref);

        } else {

            $status   = 'error';
            $message  = 'An error occurred during checkout, you may have been charged. ';
            $message .= $this->shop_payment_gateway_model->lastError();

            $this->session->set_flashdata($status, $message);
            redirect($this->shopUrl . 'checkout');
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Allows the customer to download an invoice
     * @return void
     */
    public function invoice()
    {
        if ($this->maintenance->enabled) {

            $this->renderMaintenancePage();
            return;
        }

        // --------------------------------------------------------------------------

        //  Fetch and check order
        $this->load->model('shop/shop_order_model');

        $this->data['order'] = $this->shop_order_model->getByRef($this->uri->rsegment(3));
        if (!$this->data['order'] || $this->uri->rsegment(4) != md5($this->data['order']->code)) {

            show_404();
        }

        // --------------------------------------------------------------------------

        //  Load up the shop's skin
        $skin = $this->oSkinModel->getEnabled('checkout');

        //  Views
        $this->data['for_user'] = 'CUSTOMER';
        $oPdf = Factory::service('Pdf', 'nailsapp/module-pdf');
        $oPdf->setPaperSize('A4', 'landscape');
        $oPdf->loadView($skin->path . 'views/order/invoice', $this->data);
        $oPdf->download('INVOICE-' . $this->data['order']->ref . '.pdf');
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches the order, used by the checkout process
     * @param  boolean $redirect Whether to redirect to the processing page if session order is found
     * @return mixed
     */
    protected function getOrder($redirect = true)
    {
        $orderRef = $this->input->get('ref');

        if ($orderRef) {

            $this->shop_payment_gateway_model->checkoutSessionClear();
            return $this->shop_order_model->getByRef($orderRef);

        } else {

            //  No ref, try the session
            $orderId = $this->shop_payment_gateway_model->checkoutSessionGet();

            if ($orderId) {

                $order = $this->shop_order_model->getById($orderId);

                if ($order) {

                    $this->shop_payment_gateway_model->checkoutSessionClear();

                    if ($redirect) {

                        redirect($this->shopUrl . 'checkout/processing?ref=' . $order->ref);

                    } else {

                        return $order;
                    }
                }
            }
        }
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

if (!defined('NAILS_ALLOW_EXTENSION_CHECKOUT')) {

    class Checkout extends NAILS_Checkout
    {
    }
}
