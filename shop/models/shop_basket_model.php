<?php

/**
 * This model manages the user's basket
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

class NAILS_Shop_basket_model extends NAILS_Model
{
    protected $_cache_key;
    protected $_basket;
    protected $_sess_var;


    // --------------------------------------------------------------------------


    /**
     * Constructs the basket model, creating an empty, default basket object.
     */
    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        //    Defaults
        $this->_cache_key    = 'basket';
        $this->_sess_var    = 'shop_basket';

        // --------------------------------------------------------------------------

        //    Default, empty, basket
        $this->_basket = $this->defaultBasket();

        //    Populate basket from session data?
        $_saved_basket = @unserialize($this->session->userdata($this->_sess_var));

        if (empty($_saved_basket) && $this->user_model->is_logged_in()) {

            //    Check the active_user data in case it exists there
            $_saved_basket = @unserialize(active_user('shop_basket'));
        }

        if (!empty($_saved_basket)) {

            $this->_basket->items = $_saved_basket->items;

            if (isset($_saved_basket->order)) {

                $this->addOrder($_saved_basket->order);
            }

            if (isset($_saved_basket->customer_details)) {

                $this->addCustomerDetails($_saved_basket->customer_details);
            }

            if (isset($_saved_basket->shipping_type)) {

                $this->addShippingType($_saved_basket->shipping_type);
            }

            if (isset($_saved_basket->shipping_details)) {

                $this->addShippingDetails($_saved_basket->shipping_details);
            }

            if (isset($_saved_basket->payment_gateway)) {

                $this->addPaymentGateway($_saved_basket->payment_gateway);
            }

            if (isset($_saved_basket->voucher)) {

                $this->addVoucher($_saved_basket->voucher);
            }

            if (isset($_saved_basket->note)) {

                $this->addNote($_saved_basket->note);
            }
        }

        // --------------------------------------------------------------------------

        //    Clear any startup errors
        $this->clear_errors();
    }


    // --------------------------------------------------------------------------


    /**
     * Takes the internal _basket object and fills it out a little.
     * @return stdClass
     */
    public function get()
    {
        $_cache = $this->_get_cache($this->_cache_key);

        if (!empty($_cache)) {

            return $_cache;

        }

        // --------------------------------------------------------------------------

        //    Clone the basket object so we don't damage/alter the original
        $_basket = clone $this->_basket;

        // --------------------------------------------------------------------------

        //    First loop through all the items and fetch product information
        $this->load->model('shop/shop_product_model');

        //    This variable will hold any keys which need to be unset
        $_unset = array();

        foreach ($_basket->items as $basket_key => $item) {

            $item->product = $this->shop_product_model->get_by_id($item->product_id);

            if (!empty($item->product)) {

                //    Find the variant
                foreach ($item->product->variations as &$v) {

                    if ($v->id == $item->variant_id) {

                        $item->variant = $v;
                        break;

                    }

                }

                if (empty($item->variant)) {

                    //    Bad variant ID, possible item has been deleted so don't get too angry
                    $_unset[] = $basket_key;

                }

            } else {

                //    Bad product ID, again, possible product was deleted or deactivated - KCCO
                $_unset[] = $basket_key;

            }

        }

        //    Removing anything?
        if (!empty($_unset)) {

            foreach ($_unset as $key) {

                //    Remove from the local basket object
                unset($_basket->items[$key]);

                //    Also remove from the main basket object
                unset($this->_basket->items[$key]);

            }

            $_basket->items            = array_values($_basket->items);
            $this->_basket->items    = array_values($this->_basket->items);
            $_basket->items_removed    = count($_unset);

            // --------------------------------------------------------------------------

            $this->save();

        }

        // --------------------------------------------------------------------------

        //    Calculate basket item costs
        foreach ($_basket->items as $item) {

            $_basket->totals->base->item += $item->quantity * $item->variant->price->price->base->value_ex_tax;
            $_basket->totals->user->item += $item->quantity * $item->variant->price->price->user->value_ex_tax;

        }

        // --------------------------------------------------------------------------

        //    Calculate shipping costs
        if ($_basket->shipping->type === 'DELIVER') {

            $this->load->model('shop/shop_shipping_driver_model');
            $_shipping_costs = $this->shop_shipping_driver_model->calculate($_basket);

            $_basket->totals->base->shipping = $_shipping_costs->base;
            $_basket->totals->user->shipping = $_shipping_costs->user;

        } else {

            $_basket->totals->base->shipping = 0;
            $_basket->totals->user->shipping = 0;
        }

        // --------------------------------------------------------------------------

        //    Apply any discounts
        //    TODO

        // --------------------------------------------------------------------------

        //    Calculate Tax costs
        foreach ($_basket->items as $item) {

            $_basket->totals->base->tax += $item->quantity * $item->variant->price->price->base->value_tax;
            $_basket->totals->user->tax += $item->quantity * $item->variant->price->price->user->value_tax;

        }

        // --------------------------------------------------------------------------

        //    Calculate grand total
        $_basket->totals->base->grand = $_basket->totals->base->item + $_basket->totals->base->shipping + $_basket->totals->base->tax;
        $_basket->totals->user->grand = $_basket->totals->user->item + $_basket->totals->user->shipping + $_basket->totals->user->tax;

        // --------------------------------------------------------------------------

        //    If item prices are inclusive of tax then show the items total + tax
        if (!app_setting('price_exclude_tax', 'shop')) {

            $_basket->totals->base->item += $_basket->totals->base->tax;
            $_basket->totals->user->item += $_basket->totals->user->tax;

        }

        // --------------------------------------------------------------------------

        //    Format totals
        $_basket->totals->base_formatted->item        = $this->shop_currency_model->format_base($_basket->totals->base->item);
        $_basket->totals->base_formatted->shipping    = $this->shop_currency_model->format_base($_basket->totals->base->shipping);
        $_basket->totals->base_formatted->tax        = $this->shop_currency_model->format_base($_basket->totals->base->tax);
        $_basket->totals->base_formatted->grand        = $this->shop_currency_model->format_base($_basket->totals->base->grand);

        $_basket->totals->user_formatted->item        = $this->shop_currency_model->format_user($_basket->totals->user->item);
        $_basket->totals->user_formatted->shipping    = $this->shop_currency_model->format_user($_basket->totals->user->shipping);
        $_basket->totals->user_formatted->tax        = $this->shop_currency_model->format_user($_basket->totals->user->tax);
        $_basket->totals->user_formatted->grand        = $this->shop_currency_model->format_user($_basket->totals->user->grand);

        // --------------------------------------------------------------------------

        //    Save to cache and spit it back
        $this->_set_cache($this->_cache_key, $_basket);

        return $_basket;
    }


    // --------------------------------------------------------------------------


    /**
     * Returns the number of items in the basket.
     * @param  boolean $respect_quantity If true then the number of items in the basket is counted rather than just the number of items
     * @return int
     */
    public function get_count($respect_quantity = true)
    {
        if ($respect_quantity) {

            $_count = 0;

            foreach ($this->_basket->items as $item) {

                $_count += $item->quantity;

            }

            return $_count;

        } else {

            return count($this->_basket->items);

        }
    }


    // --------------------------------------------------------------------------


    /**
     * Returns the total value of the basket, in the user's currency, optionally
     * formatted.
     * @param  boolean $include_symbol    Whether to include the currency symbol or not
     * @param  boolean $include_thousands Whether to include the thousand seperator or not
     * @return string
     */
    public function get_total($formatted = true)
    {
        if ($formatted) {

            return $_out->totals->user_formatted->grand;

        } else {

            return $_out->totals->user->grand;

        }
    }


    // --------------------------------------------------------------------------


    /**
     * Adds an item to the basket, if it's already in the basket it increments it
     * by $quantity.
     * @param int $variant_id The ID of the variant to add
     * @param int $quantity   The quantity to add
     * @return boolean
     */
    public function add($variant_id, $quantity = 1)
    {
        $quantity = intval($quantity);

        if (empty($quantity)) {

            $quantity = 1;

        }

        // --------------------------------------------------------------------------

        if ($quantity < 1) {

            $this->_set_error('Quantity must be greater than 0.');
            return false;

        }

        // --------------------------------------------------------------------------

        $this->load->model('shop/shop_product_model');

        //    Check if item is already in the basket.
        $_key = $this->getBasketKeyByVariantId($variant_id);

        // --------------------------------------------------------------------------

        if ($_key !== false) {

            //    Already in the basket, increment
            return $this->increment($variant_id, $quantity);

        }

        // --------------------------------------------------------------------------

        //    Check the product ID is valid
        $_product = $this->shop_product_model->getByVariantId($variant_id);

        if (!$_product) {

            $this->_set_error('No Product for that Variant ID.');
            return false;

        }

        $_variant = null;
        foreach ($_product->variations as $variant) {

            if ($variant_id == $variant->id) {

                $_variant = $variant;
                break;

            }

        }

        if (!$_variant) {

            $this->_set_error('Invalid Variant ID.');
            return false;

        }

        // --------------------------------------------------------------------------

        //    Check product is active
        if (!$_product->is_active) {

            $this->_set_error('Product is not available.');
            return false;

        }

        // --------------------------------------------------------------------------

        //    Check there are items
        if (!is_null($_variant->quantity_available) && $_variant->quantity_available <= 0) {

            $this->_set_error('Product is not available.');
            return false;

        }

        // --------------------------------------------------------------------------

        //    Check quantity is available, if more are being requested, then reduce.
        if (!is_null($_variant->quantity_available) && $quantity > $_variant->quantity_available) {

            $quantity = $_variant->quantity_available;

        }

        // --------------------------------------------------------------------------

        //    All good, add to basket
        $_temp                = new \stdClass();
        $_temp->variant_id    = $variant_id;
        $_temp->product_id    = $_product->id;
        $_temp->quantity    = $quantity;

        //    TODO: remove dependency on these fields
        $_temp->product_label    = $_product->label;
        $_temp->variant_label    = $_variant->label;
        $_temp->variant_sku        = $_variant->sku;

        $this->_basket->items[]    = $_temp;

        unset($_temp);

        // --------------------------------------------------------------------------

        //    Invalidate the basket cache
        $this->saveSession();
        $this->_unset_cache($this->_cache_key);

        // --------------------------------------------------------------------------

        return true;
    }


    // --------------------------------------------------------------------------


    /**
     * Removes a variant from the basket
     * @param  int $variant_id The variant's ID
     * @return boolean
     */
    public function remove($variant_id)
    {
        $_key = $this->getBasketKeyByVariantId($variant_id);

        // --------------------------------------------------------------------------

        if ($_key !== false) {

            return $this->removeByKey($_key);

        } else {

            $this->_set_error('This item is not in your basket.');
            return false;

        }
    }


    // --------------------------------------------------------------------------


    /**
     * Removes a particular item from the basket by it's key and resets the item keys
     * @param  int $key The basket item's key
     * @return boolean
     */
    protected function removeByKey($key)
    {
        unset($this->_basket->items[$key]);
        $this->_basket->items = array_values($this->_basket->items);

        // --------------------------------------------------------------------------

        //    Invalidate the basket cache
        $this->saveSession();
        $this->_unset_cache($this->_cache_key);

        // --------------------------------------------------------------------------

        return true;
    }


    // --------------------------------------------------------------------------


    /**
     * Increments the quantity of an item in the basket.
     * @param  int  $variant_id   The variant's ID
     * @param  int  $increment_by The amount to increment the item by
     * @return boolean
     */
    public function increment($variant_id, $increment_by = 1)
    {
        $_key = $this->getBasketKeyByVariantId($variant_id);

        // --------------------------------------------------------------------------

        if ($_key !== false) {

            $_can_increment = true;
            $_max_increment = null;

            /**
             * Check we can increment the product
             * @TODO; work out what the maximum number of items this product type can
             * have. If $_max_increment is null assume no limit on incrementations
             */

            if ($_can_increment && (is_null($_max_increment) || $increment <= $_max_increment)) {

                //    Increment
                $this->_basket->items[$_key]->quantity += $increment_by;

                // --------------------------------------------------------------------------

                //    Invalidate the basket cache
                $this->saveSession();
                $this->_unset_cache($this->_cache_key);

                // --------------------------------------------------------------------------

                return true;

            } else {

                $this->_set_error('You cannot increment this item that many times.');
                return false;

            }

        } else {

            $this->_set_error('This item is not in your basket.');
            return false;

        }
    }


    // --------------------------------------------------------------------------


    /**
     * Decrements the quantity of an item in the basket, if the decremntation reaches
     * zero then the item will be removed from the basket.
     * @param  int  $variant_id   The variant's ID
     * @param  int  $decrement_by The amount to decrement the item by
     * @return boolean
     */
    public function decrement($variant_id, $decrement_by = 1)
    {
        $_key = $this->getBasketKeyByVariantId($variant_id);

        // --------------------------------------------------------------------------

        if ($_key !== false) {

            $_max_decrement = $this->_basket->items[$_key]->quantity;

            if ($_max_decrement > 1) {

                if ($decrement_by >= $_max_decrement) {

                    //    The requested decrement will take the quantity to 0 or less just remove it.
                    $this->remove($variant_id);

                } else {

                    //    Decrement
                    $this->_basket->items[$_key]->quantity -= $decrement_by;

                    // --------------------------------------------------------------------------

                    //    Invalidate the basket cache
                    $this->saveSession();
                    $this->_unset_cache($this->_cache_key);

                }

            } else {

                $this->remove($variant_id);

            }

            return true;

        } else {

            $this->_set_error('This item is not in your basket.');
            return false;

        }
    }


    // --------------------------------------------------------------------------


    /**
     * Returns the basket's "customer details" object.
     * @return stdClass
     */
    public function getCustomerDetails()
    {
        return $this->_basket->customer->details;
    }


    // --------------------------------------------------------------------------


    /**
     * Sets the basket's "customer details" object.
     * @return boolean
     */
    public function addCustomerDetails($details)
    {
        //    TODO: verify?
        $this->_basket->customer->details = $details;

        //    Invalidate the basket cache
        $this->saveSession();
        $this->_unset_cache($this->_cache_key);

        return true;
    }


    // --------------------------------------------------------------------------


    /**
     * Resets the basket's "customer details" object.
     * @return boolean
     */
    public function removeCustomerDetails()
    {
        $this->_basket->customer->details = $this->defaultCustomerDetails();

        //    Invalidate the basket cache
        $this->saveSession();
        $this->_unset_cache($this->_cache_key);

        return true;
    }


    // --------------------------------------------------------------------------


    /**
     * Returns the basket's "shipping details" object.
     * @return stdClass
     */
    public function getShippingDetails()
    {
        return $this->_basket->shipping->details;
    }


    // --------------------------------------------------------------------------


    /**
     * Sets the basket's "shipping details" object.
     * @return boolean
     */
    public function addShippingDetails($details)
    {
        //    TODO: verify?
        $this->_basket->shipping->details = $details;

        //    Invalidate the basket cache
        $this->saveSession();
        $this->_unset_cache($this->_cache_key);

        return true;
    }


    // --------------------------------------------------------------------------


    /**
     * Resets the basket's "shipping details" object.
     * @return boolean
     */
    public function removeShippingDetails()
    {
        $this->_basket->shipping->details = $this->defaultShippingDetails();

        //    Invalidate the basket cache
        $this->saveSession();
        $this->_unset_cache($this->_cache_key);

        return true;
    }


    // --------------------------------------------------------------------------


    /**
     * Returns the basket's "shipping type" object.
     * @return stdClass
     */
    public function getShippingType()
    {
        return $this->_basket->shipping->type;
    }


    // --------------------------------------------------------------------------


    /**
     * Sets the basket's "shipping type" object.
     * @return boolean
     */
    public function addShippingType($deliveryType)
    {
        if ($deliveryType == 'COLLECT' || $deliveryType == 'DELIVER') {

            $this->_basket->shipping->type = $deliveryType;

            //    Invalidate the basket cache
            $this->saveSession();
            $this->_unset_cache($this->_cache_key);

            return true;

        } else {

            $this->_set_error('"' . $deliveryType . '" is not a valid delivery type.');
            return false;
        }
    }


    // --------------------------------------------------------------------------


    /**
     * Resets the basket's "shipping details" object.
     * @return boolean
     */
    public function removeShippingType()
    {
        $this->_basket->shipping->type = $this->defaultShippingType();

        //    Invalidate the basket cache
        $this->saveSession();
        $this->_unset_cache($this->_cache_key);

        return true;
    }


    // --------------------------------------------------------------------------


    /**
     * Returns the basket's "payment gatway" object.
     * @return stdClass
     */
    public function getPaymentGateway()
    {
        return $this->_basket->payment_gateway;
    }


    // --------------------------------------------------------------------------


    /**
     * Sets the basket's "payment gateway" object.
     * @return boolean
     */
    public function addPaymentGateway($payment_gateway)
    {
        //    TODO: verify?
        $this->_basket->payment_gateway = $payment_gateway;

        //    Invalidate the basket cache
        $this->saveSession();
        $this->_unset_cache($this->_cache_key);

        return true;
    }


    // --------------------------------------------------------------------------


    /**
     * Resets the basket's "payment gateway" object.
     * @return boolean
     */
    public function removePaymentGateway()
    {
        $this->_basket->payment_gateway = $this->defaultPaymentGateway();

        //    Invalidate the basket cache
        $this->saveSession();
        $this->_unset_cache($this->_cache_key);

        return true;
    }


    // --------------------------------------------------------------------------


    /**
     * Returns the basket's "order" object.
     * @return stdClass
     */
    public function getOrder()
    {
        return $this->_basket->order;
    }


    // --------------------------------------------------------------------------


    /**
     * Sets the basket's "order" object.
     * @return boolean
     */
    public function addOrder($order)
    {
        //    TODO: verify?
        $this->_basket->order = $order;

        //    Invalidate the basket cache
        $this->saveSession();
        $this->_unset_cache($this->_cache_key);

        return true;
    }


    // --------------------------------------------------------------------------


    /**
     * Resets the basket's "order" object.
     * @return boolean
     */
    public function removeOrder()
    {
        $this->_basket->order = $this->defaultOrder();

        //    Invalidate the basket cache
        $this->saveSession();
        $this->_unset_cache($this->_cache_key);

        return true;
    }


    // --------------------------------------------------------------------------


    /**
     * Returns the basket's "voucher" object.
     * @return stdClass
     */
    public function getVoucher()
    {
        return $this->_basket->voucher;
    }


    // --------------------------------------------------------------------------


    /**
     * Adds a voucher to a basket.
     * @param string $voucher_code The voucher's code
     */
    public function addVoucher($voucher_code)
    {
        if (empty($voucher_code)) {

            $this->_set_error('No voucher code supplied.');
            return false;

        }

        $this->load->model('shop/shop_voucher_model');
        $_voucher = $this->shop_voucher_model->validate($voucher_code, $this->get());

        if ($_voucher) {

            $this->_basket->voucher = $voucher;

            //    Invalidate the basket cache
            $this->saveSession();
            $this->_unset_cache($this->_cache_key);

            return true;

        } else {

            $this->removeVoucher();
            $this->_set_error($this->shop_voucher_model->last_error());

            return false;

        }
    }


    // --------------------------------------------------------------------------


    /**
     * Resets the basket's "voucher" object.
     * @return void
     */
    public function removeVoucher()
    {
        $this->_basket->voucher = $this->defaultVoucher();

        //    Invalidate the basket cache
        $this->saveSession();
        $this->_unset_cache($this->_cache_key);

        return true;
    }


    // --------------------------------------------------------------------------


    /**
     * Retrieves the note associated with an order
     * @return string
     */
    public function getNote()
    {
        return $this->_basket->note;
    }


    // --------------------------------------------------------------------------


    /**
     * Adds a note to the basket
     * @param string $note The note to add
     */
    public function addNote($note)
    {
        $this->_basket->note = $note;

        //    Invalidate the basket cache
        $this->saveSession();
        $this->_unset_cache($this->_cache_key);

        return true;
    }


    // --------------------------------------------------------------------------


    /**
     * Resets the basket's "note" object
     * @return void
     */
    public function removeNote()
    {
        $this->_basket->note = $this->defaultNote();

        //    Invalidate the basket cache
        $this->saveSession();
        $this->_unset_cache($this->_cache_key);

        return true;
    }


    // --------------------------------------------------------------------------


    /**
     * Determines whether a particular variant is already in the basket.
     * @param  int  $variant_id The ID of the variant
     * @return boolean
     */
    public function isInBasket($variant_id)
    {
        if ($this->getBasketKeyByVariantId($variant_id) !== false) {

            return true;

        } else {

            return false;

        }
    }


    // --------------------------------------------------------------------------


    public function getVariantQuantity($variant_id)
    {
        $_key = $this->getBasketKeyByVariantId($variant_id);

        if ($_key !== false) {

            return $this->_basket->items[$_key]->quantity;

        } else {

            return false;

        }
    }


    // --------------------------------------------------------------------------


    /**
     * Saves the contents of the basked to the session and, if logged in, to the
     * user's meta data
     * @return void
     */
    public function save()
    {
        $this->saveSession();
        $this->saveUser();
    }


    // --------------------------------------------------------------------------


    /**
     * Generates the 'save object' which is used by the other _save_*() methods
     * @return stdClass()
     */
    protected function saveObject()
    {
        $_save                        = new \stdClass();
        $_save->items                = $this->_basket->items;
        $_save->order                = $this->_basket->order;
        $_save->customer_details    = $this->_basket->customer->details;
        $_save->shipping_type        = $this->_basket->shipping->type;
        $_save->shipping_details    = $this->_basket->shipping->details;
        $_save->payment_gateway        = $this->_basket->payment_gateway;
        $_save->voucher                = $this->_basket->voucher->id;
        $_save->note                = $this->_basket->note;

        return serialize($_save);
    }


    // --------------------------------------------------------------------------


    /**
     * Saves the 'save object' to the user's meta record
     * @return void
     */
    protected function saveSession()
    {
        if (!headers_sent()) {

            $this->session->set_userdata($this->_sess_var, $this->saveObject());

        }
    }


    // --------------------------------------------------------------------------


    /**
     * Saves the 'save object' to the user's meta record
     * @return void
     */
    protected function saveUser()
    {
        //    If logged in, save the basket to the user's meta data for safe keeping.
        if ($this->user_model->is_logged_in()) {

            $_data = array('shop_basket' => $this->saveObject());
            $this->user_model->update(active_user('id'), $_data);

        }
    }


    // --------------------------------------------------------------------------


    /**
     * Reset's the basket to it's default (empty) state.
     * @return void
     */
    public function destroy()
    {
        $this->_basket = $this->defaultBasket();

        // --------------------------------------------------------------------------

        //    Invalidate the basket cache
        $this->saveSession();
        $this->_unset_cache($this->_cache_key);
    }


    // --------------------------------------------------------------------------


    /**
     * Fetches a basket key using the variant's ID
     * @param  int $variant_id The ID of the variant
     * @return mixed           Int on success false on failure
     */
    protected function getBasketKeyByVariantId($variant_id)
    {
        foreach ($this->_basket->items as $key => $item) {

            if ($variant_id == $item->variant_id) {

                return $key;
                break;

            }

        }

        // --------------------------------------------------------------------------

        return false;
    }


    // --------------------------------------------------------------------------


    /**
     * Returns a default, empty, basket object
     * @return stdClass
     */
    protected function defaultBasket()
    {
        $_out                        = new \stdClass();
        $_out->items                = array();
        $_out->order                = $this->defaultOrder();
        $_out->customer                = new \stdClass();
        $_out->customer->details    = $this->defaultCustomerDetails();
        $_out->shipping                = new \stdClass();
        $_out->shipping->type        = $this->defaultShippingType();
        $_out->shipping->details    = $this->defaultShippingDetails();
        $_out->payment_gateway        = $this->defaultPaymentGateway();
        $_out->voucher                = $this->defaultVoucher();
        $_out->note                    = $this->defaultNote();

        $_out->totals                    = new \stdClass();
        $_out->totals->base                = new \stdClass();
        $_out->totals->base_formatted    = new \stdClass();
        $_out->totals->user                = new \stdClass();
        $_out->totals->user_formatted    = new \stdClass();

        $_out->totals->base->item        = 0;
        $_out->totals->base->shipping    = 0;
        $_out->totals->base->tax        = 0;
        $_out->totals->base->grand        = 0;

        $_out->totals->base_formatted->item        = '';
        $_out->totals->base_formatted->shipping    = '';
        $_out->totals->base_formatted->tax        = '';
        $_out->totals->base_formatted->grand    = '';

        $_out->totals->user->item        = 0;
        $_out->totals->user->shipping    = 0;
        $_out->totals->user->tax        = 0;
        $_out->totals->user->grand        = 0;

        $_out->totals->user_formatted->item        = '';
        $_out->totals->user_formatted->shipping    = '';
        $_out->totals->user_formatted->tax        = '';
        $_out->totals->user_formatted->grand    = '';

        return $_out;
    }


    // --------------------------------------------------------------------------


    /**
     * Returns a default, empty, basket "order" object
     * @return stdClass
     */
    protected function defaultOrder()
    {
        $_out        = new \stdClass();
        $_out->id    = null;

        return $_out;
    }


    // --------------------------------------------------------------------------


    /**
     * Returns a default, empty, basket "payment gateway" object
     * @return stdClass
     */
    protected function defaultPaymentGateway()
    {
        $_out        = new \stdClass();
        $_out->id    = null;

        return $_out;
    }


    // --------------------------------------------------------------------------


    /**
     * Returns a default, empty, basket "voucher" object
     * @return stdClass
     */
    protected function defaultVoucher()
    {
        $_out        = new \stdClass();
        $_out->id    = null;
        $_out->code    = null;

        return $_out;
    }


    // --------------------------------------------------------------------------


    /**
     * Returns an empty "note" object
     * @return string
     */
    protected function defaultNote()
    {
        return '';
    }


    // --------------------------------------------------------------------------


    /**
     * Returns a default, empty, basket "customer details" object
     * @return stdClass
     */
    protected function defaultCustomerDetails()
    {
        $_out                = new \stdClass();
        $_out->id            = null;
        $_out->first_name    = null;
        $_out->last_name    = null;
        $_out->email        = null;

        return $_out;
    }


    // --------------------------------------------------------------------------


    protected function defaultShippingType()
    {
        return 'DELIVER';
    }


    // --------------------------------------------------------------------------


    /**
     * Returns a default, empty, basket "shipping details" object
     * @return stdClass
     */
    protected function defaultShippingDetails()
    {
        /**
         * Clear addressing as per:
         * http://www.royalmail.com/personal/help-and-support/How-do-I-address-my-mail-correctly
         */

        $_out                = new \stdClass();
        $_out->addressee    = null;    //    Named addresse
        $_out->line_1        = null;    //    Building number and street name
        $_out->line_2        = null;    //    Locality name, if required
        $_out->town            = null;    //    Town
        $_out->state        = null;    //    State
        $_out->postcode        = null;    //    Postcode
        $_out->country        = null;    //    Country

        return $_out;
    }


    // --------------------------------------------------------------------------


    /**
     * Saves the user's basket to the meta table on shut down.
     */
    public function __destruct()
    {
        $this->saveUser();
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

if (!defined('NAILS_ALLOW_EXTENSION_SHOP_BASKET_MODEL')) {

    class Shop_basket_model extends NAILS_Shop_basket_model
    {
    }

}

/* End of file shop_basket_model.php */
/* Location: ./modules/shop/models/shop_basket_model.php */