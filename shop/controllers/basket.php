<?php

//  Include _shop.php; executes common functionality
require_once '_shop.php';

/**
 * This class provides basket functionality
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

use Nails\Factory;

class NAILS_Basket extends NAILS_Shop_Controller
{
    /**
     * Construct the controller
     */
    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        $return = $this->input->get('return') ? $this->input->get_post('return') : $this->shopUrl . 'basket';
        $this->data['return'] = $return;

        // --------------------------------------------------------------------------

        //  Load the skin to use
        $this->loadSkin('checkout');
    }

    // --------------------------------------------------------------------------

    /**
     * Render the user's basket
     * @return void
     **/
    public function index()
    {
        if ($this->maintenance->enabled) {

            $this->renderMaintenancePage();
            return;
        }

        // --------------------------------------------------------------------------

        $this->data['page']->title = $this->shopName . ': Your Basket';

        // --------------------------------------------------------------------------

        $this->data['basket'] = $this->shop_basket_model->get();

        // --------------------------------------------------------------------------

        if (count($this->data['basket']->items) && !$this->data['basket']->shipping->isDeliverable) {

            $this->data['message']  = '<strong>We won\'t deliver this order</strong>';
            $this->data['message'] .= '<br />All items in your order must be collected.';
        }

        // --------------------------------------------------------------------------

        /**
         * Shipping promotions.
         * The shipping driver can optionally return strings to highlight a promotion
         * in the basket.
         */

        $this->data['shippingDriverPromo'] = $this->shop_shipping_driver_model->getPromotion($this->data['basket']);

        // --------------------------------------------------------------------------

        /**
         * Continue shopping URL. Skins can render a button which takes the user to a
         * sensible place to keep shopping
         */

        $this->data['continue_shopping_url'] = $this->shopUrl;

        //  Most recently viewed item
        $recentlyViewed = $this->shop_product_model->getRecentlyViewed();

        if (!empty($recentlyViewed)) {

            $productId = end($recentlyViewed);
            $product   = $this->shop_product_model->getById($productId);

            if ($product && $product->is_active) {

                $this->data['continue_shopping_url'] .= 'product/' . $product->slug;
            }
        }

        // --------------------------------------------------------------------------

        //  Other recently viewed items
        $this->data['recently_viewed'] = array();
        if (!empty($recentlyViewed)) {
            $this->data['recently_viewed'] = $this->shop_product_model->getByIds($recentlyViewed);
        }

        // --------------------------------------------------------------------------

        //  Abandon any previous orders
        $this->load->model('shop/shop_payment_gateway_model');
        $previousOrder = $this->shop_payment_gateway_model->checkoutSessionGet();

        if ($previousOrder) {

            $this->shop_order_model->abandon($previousOrder);
            $this->shop_payment_gateway_model->checkoutSessionClear();
        }

        // --------------------------------------------------------------------------

        $this->load->view('structure/header', $this->data);
        $this->load->view($this->skin->path . 'views/basket/index', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Adds an item to the user's basket (fall back for when JS is not available)
     * @access  public
     * @return void
     **/
    public function add()
    {
        if ($this->maintenance->enabled) {

            $this->renderMaintenancePage();
            return;
        }

        // --------------------------------------------------------------------------

        $variantId = $this->input->get_post('variant_id');
        $quantity  = (int) $this->input->get_post('quantity');

        if ($this->shop_basket_model->add($variantId, $quantity)) {

            $status  = 'success';
            $message = '<strong>Success!</strong> Item was added to your basket.';
            $message .= anchor(
                $this->shopUrl . 'basket',
                'Checkout <span class="glyphicon glyphicon-chevron-right"></span>',
                'class="btn btn-success btn-xs pull-right"'
            );

        } else {

            $status   = 'error';
            $message  = '<strong>Sorry,</strong> there was a problem adding to your basket: ';
            $message .= $this->shop_basket_model->lastError();
        }

        // --------------------------------------------------------------------------

        $this->session->set_flashdata($status, $message);
        redirect($this->data['return']);
    }

    // --------------------------------------------------------------------------

    /**
     * Removes an item from the user's basket (fall back for when JS is not available)
     * @return void
     **/
    public function remove()
    {
        if ($this->maintenance->enabled) {

            $this->renderMaintenancePage();
            return;
        }

        // --------------------------------------------------------------------------

        $variantId = $this->input->get_post('variant_id');

        if ($this->shop_basket_model->remove($variantId)) {

            $status  = 'success';
            $message = '<strong>Success!</strong> Item was removed from your basket.';

        } else {

            $status   = 'error';
            $message  = '<strong>Sorry,</strong> there was a problem removing the item from your basket: ';
            $message .= $this->shop_basket_model->lastError();
        }

        // --------------------------------------------------------------------------

        $this->session->set_flashdata($status, $message);
        redirect($this->data['return']);
    }

    // --------------------------------------------------------------------------

    /**
     * Empties a user's basket
     * @return void
     **/
    public function destroy()
    {
        if ($this->maintenance->enabled) {

            $this->renderMaintenancePage();
            return;
        }

        // --------------------------------------------------------------------------

        $this->shop_basket_model->destroy();
        redirect($this->data['return']);
    }

    // --------------------------------------------------------------------------

    /**
     * Increment an item in the user's basket (fall back for when JS is not available)
     * @return void
     **/
    public function increment()
    {
        if ($this->maintenance->enabled) {

            $this->renderMaintenancePage();
            return;
        }

        // --------------------------------------------------------------------------

        $variantId = $this->input->get_post('variant_id');

        if ($this->shop_basket_model->increment($variantId)) {

            $status  = 'success';
            $message = '<strong>Success!</strong> Quantity adjusted!';

        } else {

            $status  = 'error';
            $message = '<strong>Sorry,</strong> could not adjust quantity. ' . $this->shop_basket_model->lastError();
        }

        // --------------------------------------------------------------------------

        $this->session->set_flashdata($status, $message);
        redirect($this->data['return']);
    }

    // --------------------------------------------------------------------------

    /**
     * Decrement an item in the user's basket (fall back for when JS is not available)
     * @return void
     **/
    public function decrement()
    {
        if ($this->maintenance->enabled) {

            $this->renderMaintenancePage();
            return;
        }

        // --------------------------------------------------------------------------

        $variantId = $this->input->get_post('variant_id');

        if ($this->shop_basket_model->decrement($variantId)) {

            $status  = 'success';
            $message = '<strong>Success!</strong> Quantity adjusted!';

        } else {

            $status  = 'error';
            $message = '<strong>Sorry,</strong> could not adjust quantity. ' . $this->shop_basket_model->lastError();
        }

        // --------------------------------------------------------------------------

        $this->session->set_flashdata($status, $message);
        redirect($this->data['return']);
    }

    // --------------------------------------------------------------------------

    /**
     * Validate and add a voucher to a basket
     * @return void
     **/
    public function add_voucher()
    {
        if ($this->maintenance->enabled) {

            $this->renderMaintenancePage();
            return;
        }

        if ($this->shop_basket_model->addVoucher($this->input->post('voucher'))) {

            $status  = 'success';
            $message = '<strong>Success!</strong> Voucher has been applied to your basket.';

        } else {

            $status  = 'error';
            $message = '<Strong>Sorry,</strong> failed to add voucher. ' . $this->shop_basket_model->lastError();
        }

        // --------------------------------------------------------------------------

        $this->session->set_flashdata($status, $message);
        redirect($this->data['return']);
    }

    // --------------------------------------------------------------------------

    /**
     * Remove any associated voucher from the user's basket
     * @return void
     **/
    public function remove_voucher()
    {
        if ($this->maintenance->enabled) {

            $this->renderMaintenancePage();
            return;
        }

        // --------------------------------------------------------------------------

        if ($this->shop_basket_model->removeVoucher()) {

            $status  = 'success';
            $message = '<strong>Success!</strong> Your voucher was removed.';

        } else {

            $status  = 'error';
            $message = '<strong>Sorry,</strong> failed to remove voucher. ' . $this->shop_basket_model->lastError();

        }

        // --------------------------------------------------------------------------

        $this->session->set_flashdata($status, $message);
        redirect($this->data['return']);
    }

    // --------------------------------------------------------------------------

    /**
     * Adds a note to the basket
     * @return void
     */
    public function add_note()
    {
        if ($this->maintenance->enabled) {

            $this->renderMaintenancePage();
            return;
        }

        // --------------------------------------------------------------------------

        if ($this->shop_basket_model->addNote($this->input->get_post('note'))) {

            $status  = 'success';
            $message = '<strong>Success!</strong> Note was added to your basket.';

        } else {

            $status  = 'error';
            $message = '<strong>Sorry,</strong> failed to save note. ' . $this->shop_basket_model->lastError();

        }

        // --------------------------------------------------------------------------

        $this->session->set_flashdata($status, $message);
        redirect($this->data['return']);
    }

    // --------------------------------------------------------------------------

    public function remove_note()
    {
        if ($this->maintenance->enabled) {

            $this->renderMaintenancePage();
            return;
        }

        // --------------------------------------------------------------------------

        if ($this->shop_basket_model->removeNote()) {

            $status  = 'success';
            $message = '<strong>Success!</strong> Note was removed from your basket.';

        } else {

            $status  = 'error';
            $message = '<strong>Sorry,</strong> failed to remove note. ' . $this->shop_basket_model->lastError();

        }

        // --------------------------------------------------------------------------

        $this->session->set_flashdata($status, $message);
        redirect($this->data['return']);
    }

    // --------------------------------------------------------------------------

    /**
     * Set the user's preferred currency
     * @return void
     **/
    public function set_currency()
    {
        if ($this->maintenance->enabled) {

            $this->renderMaintenancePage();
            return;
        }

        // --------------------------------------------------------------------------

        $oCurrencyModel = Factory::model('Currency', 'nailsapp/module-shop');
        $oCurrency      = $oCurrencyModel->getByCode($this->input->get_post('currency'));

        if ($oCurrency) {

            //  Valid currency
            $this->session->set_userdata('shop_currency', $oCurrency->code);

            if ($this->user_model->isLoggedIn()) {

                //  Save to the user object
                $oUserMeta = Factory::model('UserMeta', 'nailsapp/module-auth');
                $oUserMeta->update(
                    NAILS_DB_PREFIX . 'user_meta_shop',
                    activeUser('id'),
                    array(
                        'currency' => $oCurrency->code
                    )
                );
            }

            $sStatus  = 'success';
            $sMessage = '<strong>Success!</strong> Your currency has been updated.';

        } else {

            //  Failed to validate, feedback
            $sStatus  = 'error';
            $sMessage = '<strong>Sorry,</strong> that currency is not supported.';
        }

        // --------------------------------------------------------------------------

        $this->session->set_flashdata($sStatus, $sMessage);
        redirect($this->data['return']);
    }

    // --------------------------------------------------------------------------

    /**
     * Set a basket as a "for collection" order
     * @return void
     */
    public function set_as_collection()
    {
        if ($this->maintenance->enabled) {

            $this->renderMaintenancePage();
            return;
        }

        // --------------------------------------------------------------------------

        if ($this->shop_basket_model->addShippingType('COLLECT')) {

            $status  = 'success';
            $message = '<strong>Success!</strong> Your basket was set as a "Collection" order.';

        } else {

            $status   = 'error';
            $message  = '<strong>Sorry,</strong> failed to set your basket as a "Collection" order. ';
            $message .= $this->shop_basket_model->lastError();
        }

        // --------------------------------------------------------------------------

        $this->session->set_flashdata($status, $message);
        redirect($this->data['return']);
    }

    // --------------------------------------------------------------------------

    /**
     * Set a basket as a "for delivery" order
     * @return void
     */
    public function set_as_delivery()
    {
        if ($this->maintenance->enabled) {

            $this->renderMaintenancePage();
            return;
        }

        // --------------------------------------------------------------------------

        //  Check that the basket can in fact be set as delivery
        $numCollectOnlyItems = 0;
        $basket              = $this->shop_basket_model->get();

        if (!$basket->shipping->isDeliverable) {

            $status   = 'error';
            $message  = '<strong>Sorry,</strong> failed to set your basket as a "delivery" order. ';
            $message .= 'Your order does not contain any deliverable items.';

        } elseif ($this->shop_basket_model->addShippingType('DELIVER')) {

            $status  = 'success';
            $message = '<strong>Success!</strong> Your basket was set as a "Delivery" order.';

            /**
             * Refresh the basket, we need to check if this is a DELIVER_COLLECT order now,
             * if it is, we should give the user a heads-up.
             */

            $basket = $this->shop_basket_model->get(true);

            if ($basket->shipping->type == 'DELIVER_COLLECT') {

                $this->session->set_flashdata(
                    'message',
                    '<strong>We will only partially deliver this order</strong>' .
                    '<br />Your order contains items which are collect only.'
                );
            }

        } else {

            $status   = 'error';
            $message  = '<strong>Sorry,</strong> failed to set your basket as a "Delivery" order. ';
            $message .= $this->shop_basket_model->lastError();
        }

        // --------------------------------------------------------------------------

        $this->session->set_flashdata($status, $message);
        redirect($this->data['return']);
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

if (!defined('NAILS_ALLOW_EXTENSION_BASKET')) {

    class Basket extends NAILS_Basket
    {
    }
}
