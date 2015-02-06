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
        $this->load->model('country_model');

        $this->data['countries_flat']   = $this->country_model->getAllFlat();
        $this->data['payment_gateways'] = $this->shop_payment_gateway_model->get_enabled_formatted();

        if (!count($this->data['payment_gateways'])) {

            $this->data['error'] = '<strong>Error:</strong> No Payment Gateways are configured.';
            $this->data['page']->title = $this->shopName . ': No Payment Gateways have been configured';

            $this->load->view('structure/header', $this->data);
            $this->load->view($this->skin->path . 'views/checkout/no_gateway', $this->data);
            $this->load->view('structure/footer', $this->data);
            return;
        }

        // --------------------------------------------------------------------------

        $basket = $this->shop_basket_model->get();

        if (empty($basket->items)) {

            $status  = 'error';
            $message = '<strong>Sorry,</strong> you cannot checkout just now. Your basket is empty.';
            $this->session->set_flashdata($status, $message);
            redirect($this->shopUrl . 'basket');
        }

        // --------------------------------------------------------------------------

        //  Abandon any previous orders
        $previousOrder = $this->shop_payment_gateway_model->checkout_session_get();

        if ($previousOrder) {

            $this->shop_order_model->abandon($previousOrder);
            $this->shop_payment_gateway_model->checkout_session_clear();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            if (!$this->shop_payment_gateway_model->is_enabled($this->input->post('payment_gateway'))) {

                $this->data['error']  = '"' . $this->input->post('payment_gateway') . '" ';
                $this->data['error'] .= 'is not a valid payment gateway.';

            } else {

                $this->load->library('form_validation');

                $this->form_validation->set_rules('delivery_address_line_1', '', 'xss_clean|trim|required');
                $this->form_validation->set_rules('delivery_address_line_2', '', 'xss_clean|trim');
                $this->form_validation->set_rules('delivery_address_town', '', 'xss_clean|trim|required');
                $this->form_validation->set_rules('delivery_address_state', '', 'xss_clean|trim');
                $this->form_validation->set_rules('delivery_address_postcode', '', 'xss_clean|trim|required');
                $this->form_validation->set_rules('delivery_address_country', '', 'xss_clean|required');

                $this->form_validation->set_rules('first_name', '', 'xss_clean|trim|required');
                $this->form_validation->set_rules('last_name', '', 'xss_clean|trim|required');
                $this->form_validation->set_rules('email', '', 'xss_clean|trim|required');
                $this->form_validation->set_rules('telephone', '', 'xss_clean|trim|required');

                if (!$this->input->post('same_billing_address')) {

                    $this->form_validation->set_rules('billing_address_line_1', '', 'xss_clean|trim|required');
                    $this->form_validation->set_rules('billing_address_line_2', '', 'xss_clean|trim');
                    $this->form_validation->set_rules('billing_address_town', '', 'xss_clean|trim|required');
                    $this->form_validation->set_rules('billing_address_state', '', 'xss_clean|trim');
                    $this->form_validation->set_rules('billing_address_postcode', '', 'xss_clean|trim|required');
                    $this->form_validation->set_rules('billing_address_country', '', 'xss_clean|trim|required');

                } else {

                    $this->form_validation->set_rules('billing_address_line_1', '', 'xss_clean|trim');
                    $this->form_validation->set_rules('billing_address_line_2', '', 'xss_clean|trim');
                    $this->form_validation->set_rules('billing_address_town', '', 'xss_clean|trim');
                    $this->form_validation->set_rules('billing_address_state', '', 'xss_clean|trim');
                    $this->form_validation->set_rules('billing_address_postcode', '', 'xss_clean|trim');
                    $this->form_validation->set_rules('billing_address_country', '', 'xss_clean|trim');
                }

                $this->form_validation->set_rules('payment_gateway', '', 'xss_clean|trim|required');

                $this->form_validation->set_message('required', lang('fv_required'));

                if ($this->form_validation->run()) {

                    //  Prepare data
                    $data = new stdClass();

                    //  Contact details
                    $data->contact             = new stdClass();
                    $data->contact->first_name = $this->input->post('first_name');
                    $data->contact->last_name  = $this->input->post('last_name');
                    $data->contact->email      = $this->input->post('email');
                    $data->contact->telephone  = $this->input->post('telephone');

                    //  Delivery Details
                    $data->delivery           = new stdClass();
                    $data->delivery->line_1   = $this->input->post('delivery_address_line_1');
                    $data->delivery->line_2   = $this->input->post('delivery_address_line_2');
                    $data->delivery->town     = $this->input->post('delivery_address_town');
                    $data->delivery->state    = $this->input->post('delivery_address_state');
                    $data->delivery->postcode = $this->input->post('delivery_address_postcode');
                    $data->delivery->country  = $this->input->post('delivery_address_country');

                    //  Billing details
                    if (!$this->input->post('same_billing_address')) {

                        $data->billing           = new stdClass();
                        $data->billing->line_1   = $this->input->post('billing_address_line_1');
                        $data->billing->line_2   = $this->input->post('billing_address_line_2');
                        $data->billing->town     = $this->input->post('billing_address_town');
                        $data->billing->state    = $this->input->post('billing_address_state');
                        $data->billing->postcode = $this->input->post('billing_address_postcode');
                        $data->billing->country  = $this->input->post('billing_address_country');

                    } else {

                        $data->billing           = new stdClass();
                        $data->billing->line_1   = $this->input->post('delivery_address_line_1');
                        $data->billing->line_2   = $this->input->post('delivery_address_line_2');
                        $data->billing->town     = $this->input->post('delivery_address_town');
                        $data->billing->state    = $this->input->post('delivery_address_state');
                        $data->billing->postcode = $this->input->post('delivery_address_postcode');
                        $data->billing->country  = $this->input->post('delivery_address_country');
                    }

                    //  And the basket
                    $data->basket = $basket;

                    //  Generate the order and proceed to payment
                    $order = $this->shop_order_model->create($data, true);

                    if ($order) {

                        /**
                         * Order created successfully, attempt payment. We need to keep track of the order ID
                         * so that when we redirect the processing/cancel pages can pick up where we left off.
                         */

                        $this->shop_payment_gateway_model->checkout_session_save($order->id, $order->ref, $order->code);

                        $result = $this->shop_payment_gateway_model->do_payment(
                            $order->id,
                            $this->input->post('payment_gateway'),
                            $this->input->post()
                        );

                        if ($result) {

                            /**
                             * Payment complete! Mark order as paid and then process it, finally send user to
                             * processing page for receipt
                             */

                            $this->shop_order_model->paid($order->id);
                            $this->shop_order_model->process($order->id);

                            $shop_url = app_setting('url', 'shop') ? app_setting('url', 'shop') : 'shop/';
                            redirect($shop_url . 'checkout/processing?ref=' . $order->ref);

                        } else {

                            //  Payment failed, mark this order as a failure too.
                            $this->shop_order_model->fail($order->id, $this->shop_payment_gateway_model->last_error());

                            $this->data['error']  = '<strong>Sorry,</strong> something went wrong during checkout. ';
                            $this->data['error'] .= $this->shop_payment_gateway_model->last_error();
                            $this->data['payment_error'] = $this->shop_payment_gateway_model->last_error();

                            $this->shop_payment_gateway_model->checkout_session_clear();
                        }

                    } else {

                        $this->data['error']  = '<strong>Sorry,</strong> there was a problem processing your order. ';
                        $this->data['error'] .= $this->shop_order_model->last_error();
                    }

                } else {

                    $this->data['error'] = lang('fv_there_were_errors');
                }
            }
        }

        // --------------------------------------------------------------------------

        //  Load assets required by the payment gateways
        foreach ($this->data['payment_gateways'] as $pg) {

            $assets = $this->shop_payment_gateway_model->get_checkout_assets($pg->slug);

            foreach ($assets as $asset) {

                $inline = array('JS-INLINE', 'CSS-INLINE');

                if (in_array($asset[2], $inline)) {

                    $this->asset->inline($asset[0], $asset[2]);

                } else {

                    $this->asset->load($asset[0], $asset[1], $asset[2]);
                }
            }
        }

        // --------------------------------------------------------------------------

        $this->data['page']->title = $this->shopName . ': Checkout';
        $this->data['basket']      = $basket;

        // --------------------------------------------------------------------------

        $this->load->view('structure/header', $this->data);
        $this->load->view($this->skin->path . 'views/checkout/index', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Shown to the user once the payment gateway has been informed.
     * @return void
     */
    public function processing()
    {
        $this->data['order'] = $this->getOrder();

        if (empty($this->data['order'])) {

            show_404();

        } else {

            //  Fetch the product/variants associated with each order item
            foreach ($this->data['order']->items as $item) {

                $item->product = $this->shop_product_model->get_by_id($item->product_id);

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

            //  Map the country codes to names
            $this->load->model('country_model');
            $this->data['country'] = $this->country_model->getAllFlat();

            if ($this->data['order']->shipping_address->country) {

                $key = $this->data['order']->shipping_address->country;
                $this->data['order']->shipping_address->country = $this->data['country'][$key];
            }

            if ($this->data['order']->billing_address->country) {

                $key = $this->data['order']->billing_address->country;
                $this->data['order']->billing_address->country = $this->data['country'][$key];
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
        $this->load->view($this->skin->path . 'views/checkout/processing/unpaid', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Renders the "pending" processing view.
     * @return void
     */
    protected function processingPending()
    {
        //  Now we know what the state of play is, clear the session.
        $this->shop_payment_gateway_model->checkout_session_clear();

        //  And load the view
        $this->load->view($this->skin->path . 'views/checkout/processing/pending', $this->data);
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
        $this->shop_payment_gateway_model->checkout_session_clear();

        //  And load the view
        $this->load->view($this->skin->path . 'views/checkout/processing/paid', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Renders the "failed" processing view.
     * @return void
     */
    protected function processingFailed()
    {
        if (!$this->data['error']) {

            $this->data['error'] = '<strong>Sorry,</strong> there was a problem processing your order';
        }

        if (!isset($this->data['page']->title) || !$this->data['page']->title) {

            $this->data['page']->title = 'An error occurred';
        }

        // --------------------------------------------------------------------------

        //  Now we know what the state of play is, clear the session.
        $this->shop_payment_gateway_model->checkout_session_clear();

        //  And load the view
        $this->load->view($this->skin->path . 'views/checkout/processing/failed', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Renders the "abandoned" processing view.
     * @return void
     */
    protected function processingAbandoned()
    {
        if (!$this->data['error']) {

            $this->data['error'] = '<strong>Sorry,</strong> there was a problem processing your order';
        }

        if (!isset($this->data['page']->title) || !$this->data['page']->title) {

            $this->data['page']->title = 'An error occurred';
        }

        // --------------------------------------------------------------------------

        //  Now we know what the state of play is, clear the session.
        $this->shop_payment_gateway_model->checkout_session_clear();

        //  And load the view
        $this->load->view($this->skin->path . 'views/checkout/processing/abandoned', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Renders the "cancelled" processing view.
     * @return void
     */
    protected function processingCancelled()
    {
        if (!$this->data['error']) {

            $this->data['error'] = '<strong>Sorry,</strong> there was a problem processing your order';
        }

        if (!isset($this->data['page']->title) || !$this->data['page']->title) {

            $this->data['page']->title = 'An error occurred';
        }

        // --------------------------------------------------------------------------

        //  Now we know what the state of play is, clear the session.
        $this->shop_payment_gateway_model->checkout_session_clear();

        //  And load the view
        $this->load->view($this->skin->path . 'views/checkout/processing/cancelled', $this->data);
    }


    // --------------------------------------------------------------------------


    /**
     * Renders the "error" processing view.
     * @return void
     */
    protected function processingError($error = '')
    {
        if (!$this->data['error']) {

            $this->data['error'] = '<strong>Sorry,</strong> there was a problem processing your order. ' . $error;
        }

        if (!isset($this->data['page']->title) || !$this->data['page']->title) {

            $this->data['page']->title = 'An error occurred';
        }

        // --------------------------------------------------------------------------

        //  Now we know what the state of play is, clear the session.
        $this->shop_payment_gateway_model->checkout_session_clear();

        //  And load the view
        $this->load->view($this->skin->path . 'views/checkout/processing/error', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Marks an order as cancelled and redirects the user to the basket with feedback.
     * @return void
     */
    public function cancel()
    {
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
        $order = $this->getOrder();

        if (empty($order)) {

            show_404();
        }

        $result = $this->shop_payment_gateway_model->confirm_complete_payment($this->uri->rsegment(3), $order);

        if ($result) {

            redirect($this->shopUrl . 'checkout/processing?ref=' . $order->ref);

        } else {

            $status   = 'error';
            $message  = 'An error occurred during checkout, you may have been charged. ';
            $message .= $this->shop_payment_gateway_model->last_error();

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
        //  Fetch and check order
        $this->load->model('shop/shop_order_model');

        $this->data['order'] = $this->shop_order_model->get_by_ref($this->uri->rsegment(3));
        if (!$this->data['order'] || $this->uri->rsegment(4) != md5($this->data['order']->code)) {

            show_404();
        }

        // --------------------------------------------------------------------------

        //  Load up the shop's skin
        $skin = app_setting('skin_checkout', 'shop');
        $skin = empty($skin) ? 'shop-skin-checkout-classic' : $skin;

        $this->load->model('shop/shop_skin_checkout_model');
        $skin = $this->shop_skin_checkout_model->get($skin);

        if (!$skin) {

            $subject  = 'Failed to load shop skin "' . $skin . '"';
            $message  = 'Shop skin "' . $skin . '" failed to load at ' . APP_NAME . ', for the following reason: ';
            $message .= $this->shop_skin_checkout_model->last_error();

            showFatalError($subject, $message);
        }

        // --------------------------------------------------------------------------

        //  Views
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
    protected function getOrder($redirect = true)
    {
        $order_ref = $this->input->get('ref');

        if ($order_ref) {

            $this->shop_payment_gateway_model->checkout_session_clear();
            return $this->shop_order_model->get_by_ref($order_ref);

        } else {

            //  No ref, try the session
            $order_id = $this->shop_payment_gateway_model->checkout_session_get();

            if ($order_id) {

                $order = $this->shop_order_model->get_by_id($order_id);

                if ($order) {

                    $this->shop_payment_gateway_model->checkout_session_clear();
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
