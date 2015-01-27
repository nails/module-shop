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
        $this->data['page']->title = $this->shopName . ': Your Basket';

        // --------------------------------------------------------------------------

        $this->data['basket'] = $this->shop_basket_model->get();

        if (!empty($this->data['basket']->items_removed) && empty($this->data['message'])) {

            if ($this->data['basket']->items_removed > 1) {

                $this->data['message']  = '<strong>Some items were removed.</strong> ';
                $this->data['message'] .= $this->data['basket']->items_removed . ' items were removed from your ';
                $this->data['message'] .= 'basket because they are no longer available.';

            } else {

                $this->data['message']  = '<strong>Some items were removed.</strong> An item was removed from your ';
                $this->data['message'] .= 'basket because it is no longer available.';
            }
        }

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
            $product   = $this->shop_product_model->get_by_id($productId);

            if ($product && $product->is_active) {

                $this->data['continue_shopping_url'] .= 'product/' . $product->slug;
            }
        }

        // --------------------------------------------------------------------------

        //  Other recently viewed items
        $this->data['recently_viewed'] = array();
        if (!empty($recentlyViewed)) {

            $this->data['recently_viewed'] = $this->shop_product_model->get_by_ids($recentlyViewed);
        }

        // --------------------------------------------------------------------------

        //  Abandon any previous orders
        $this->load->model('shop/shop_payment_gateway_model');
        $previousOrder = $this->shop_payment_gateway_model->checkout_session_get();

        if ($previousOrder) {

            $this->shop_order_model->abandon($previousOrder);
            $this->shop_payment_gateway_model->checkout_session_clear();
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
        $variantId = $this->input->get_post('variant_id');
        $quantity  = (int) $this->input->get_post('quantity');

        if ($this->shop_basket_model->add($variantId, $quantity)) {

            $status  = 'success';
            $message = '<strong>Success!</strong> Item was added to your basket.';

        } else {

            $status   = 'error';
            $message  = '<strong>Sorry,</strong> there was a problem adding to your basket: ';
            $message .= $this->shop_basket_model->last_error();
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
        $variantId = $this->input->get_post('variant_id');

        if ($this->shop_basket_model->remove($variantId)) {

            $status  = 'success';
            $message = '<strong>Success!</strong> Item was removed from your basket.';

        } else {

            $status   = 'error';
            $message  = '<strong>Sorry,</strong> there was a problem removing the item from your basket: ';
            $message .= $this->shop_basket_model->last_error();
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
        $variantId = $this->input->get_post('variant_id');

        if ($this->shop_basket_model->increment($variantId)) {

            $status  = 'success';
            $message = '<strong>Success!</strong> Quantity adjusted!';

        } else {

            $status  = 'error';
            $message = '<strong>Sorry,</strong> could not adjust quantity. ' . $this->shop_basket_model->last_error();
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
        $variantId = $this->input->get_post('variant_id');

        if ($this->shop_basket_model->decrement($variantId)) {

            $status  = 'success';
            $message = '<strong>Success!</strong> Quantity adjusted!';

        } else {

            $status  = 'error';
            $message = '<strong>Sorry,</strong> could not adjust quantity. ' . $this->shop_basket_model->last_error();
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
        $voucher = $this->shop_voucher_model->validate($this->input->get_post('voucher'), get_basket());

        if ($voucher) {

            //  Validated, add to basket
            if ($this->shop_basket_model->addVoucher($voucher->code)) {

                $status  = 'success';
                $message = '<strong>Success!</strong> Voucher has been applied to your basket.';

            } else {

                $status  = 'error';
                $message = '<Strong>Sorry,</strong> failed to add voucher. ' . $this->shop_basket_model->last_error();
            }

        } else {

            //  Failed to validate, feedback
            $status  = 'error';
            $message = '<strong>Sorry,</strong> that voucher is not valid. ' . $this->shop_voucher_model->last_error();
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
        if ($this->shop_basket_model->remove_voucher()) {

            $status  = 'success';
            $message = '<strong>Success!</strong> Your voucher was removed.';

        } else {

            $status  = 'error';
            $message = '<strong>Sorry,</strong> failed to remove voucher. ' . $this->shop_basket_model->last_error();

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
        if ($this->shop_basket_model->addNote($this->input->get_post('note'))) {

            $status  = 'success';
            $message = '<strong>Success!</strong> Note was added to your basket.';

        } else {

            $status  = 'error';
            $message = '<strong>Sorry,</strong> failed to save note. ' . $this->shop_basket_model->last_error();

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
        $currency = $this->shop_currency_model->get_by_code($this->input->get_post('currency'));

        if ($currency) {

            //  Valid currency
            $this->session->set_userdata('shop_currency', $currency->code);

            if ($this->user_model->is_logged_in()) {

                //  Save to the user object
                $this->user_model->update(active_user('id'), array('shop_currency' => $currency->code));
            }

            $status  = 'success';
            $message = '<strong>Success!</strong> Your currency has been updated.';

        } else {

            //  Failed to validate, feedback
            $status  = 'error';
            $message = '<strong>Sorry,</strong> that currency is not supported.';
        }

        // --------------------------------------------------------------------------

        $this->session->set_flashdata($status, $message);
        redirect($this->data['return']);
    }

    // --------------------------------------------------------------------------

    /**
     * Set a basket as a "for collection" order
     * @return void
     */
    public function set_as_collection()
    {
        if ($this->shop_basket_model->addShippingType('COLLECT')) {

            $status  = 'success';
            $message = '<strong>Success!</strong> Your basket was set as a "collection" order.';

        } else {

            $status   = 'error';
            $message  = '<strong>Sorry,</strong> failed to set your basket as a "collection" order. ';
            $message .= $this->shop_basket_model->last_error();
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
        if ($this->shop_basket_model->addShippingType('DELIVER')) {

            $status  = 'success';
            $message = '<strong>Success!</strong> Your basket was set as a "delivery" order.';

        } else {

            $status   = 'error';
            $message  = '<strong>Sorry,</strong> failed to set your basket as a "delivery" order. ';
            $message .= $this->shop_basket_model->last_error();
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
