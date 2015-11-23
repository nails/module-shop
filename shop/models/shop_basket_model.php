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

use Nails\Factory;
use Nails\Common\Model\Base;

class NAILS_Shop_basket_model extends Base
{
    protected $oUser;
    protected $oUserMeta;
    protected $cacheKey;
    protected $basket;
    protected $sessVar;
    protected $iTotalItemDiscount;
    protected $iTotalShipDiscount;
    protected $iTotalTaxDiscount;
    protected $iTotalDiscount;

    // --------------------------------------------------------------------------

    /**
     * Constructs the basket model, creating an empty, default basket object.
     */
    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        $this->oUser     = Factory::model('User', 'nailsapp/module-auth');
        $this->oUserMeta = Factory::model('UserMeta', 'nailsapp/module-auth');

        // --------------------------------------------------------------------------

        //  Defaults
        $this->cacheKey = 'basket';
        $this->sessVar  = 'shop_basket';

        // --------------------------------------------------------------------------

        //  Default, empty, basket
        $this->basket = $this->defaultBasket();

        //  Populate basket from session data?
        $savedBasket = json_decode($this->session->userdata($this->sessVar));

        if (empty($savedBasket) && $this->oUser->isLoggedIn()) {

            //  Check the activeUser data in case it exists there
            $oUserMeta = $this->oUserMeta->get(
                NAILS_DB_PREFIX . 'user_meta_shop',
                activeUser('id'),
                array('basket')
            );
            $savedBasket = json_decode($oUserMeta->basket);
        }

        if (!empty($savedBasket)) {

            $this->basket->items = $savedBasket->items;

            if (!empty($savedBasket->order)) {
                $this->addOrder($savedBasket->order);
            }

            if (!empty($savedBasket->customer_details)) {
                $this->addCustomerDetails($savedBasket->customer_details);
            }

            if (!empty($savedBasket->shipping_type)) {
                $this->addShippingType($savedBasket->shipping_type);
            }

            if (!empty($savedBasket->shipping_details)) {
                $this->addShippingDetails($savedBasket->shipping_details);
            }

            if (!empty($savedBasket->payment_gateway)) {
                $this->addPaymentGateway($savedBasket->payment_gateway);
            }

            if (!empty($savedBasket->voucher->code)) {
                $this->addVoucher($savedBasket->voucher->code);
            }

            if (!empty($savedBasket->note)) {
                $this->addNote($savedBasket->note);
            }
        }

        // --------------------------------------------------------------------------

        //  Clear any startup errors
        $this->clear_errors();
    }

    // --------------------------------------------------------------------------

    /**
     * Takes the internal $this->basket object and fills it out a little.
     * @param  boolean $skipCache Whether to skip the cache or not
     * @return stdClass
     */
    public function get($skipCache = false)
    {
        if (!empty($skipCache)) {
            $cache = $this->_get_cache($this->cacheKey);
            if (!empty($cache)) {
                return $cache;
            }
        }

        // --------------------------------------------------------------------------

        //  Clone the basket object so we don't damage/alter the original
        $basket = clone $this->basket;

        // --------------------------------------------------------------------------

        //  First loop through all the items and fetch product information
        $this->load->model('shop/shop_product_model');
        $oCurrencyModel = Factory::model('Currency', 'nailsapp/module-shop');

        //  This variable will hold any keys which need to be unset
        $unset = array();

        //  This variable will hold the labels of any adjusted items
        $adjusted = array();

        foreach ($basket->items as $basketKey => $item) {

            /**
             * Set the item label, this is used to feedback to the suer should items
             * be removed or adjusted.
             */

            if ($item->product_label === $item->variant_label) {

                $itemLabel = $item->product_label;

            } else {

                $itemLabel = $item->product_label . ' - ' . $item->variant_label;
            }

            //  Now get the product
            $item->product = $this->shop_product_model->get_by_id($item->product_id);

            if (!empty($item->product)) {

                //  Find the variant
                foreach ($item->product->variations as &$v) {

                    if ($v->id == $item->variant_id) {

                        $item->variant = $v;
                        break;
                    }
                }

                if (empty($item->variant)) {

                    //  Bad variant ID, possible item has been deleted so don't get too angry
                    $unset[] = array(
                        $basketKey,
                        $itemLabel
                    );

                } else {

                    /**
                     * Found the item, do we still have enough of the stock? We have enoughs tock if:
                     * - $available is null (unlimited)
                     * - $item->quantity is <= $available
                     */

                    $available = $item->variant->quantity_available;
                    if (!is_null($available) && $item->quantity > $item->variant->quantity_available) {

                        $adjusted[] = array(
                            $basketKey,
                            $itemLabel,
                            $item->variant->quantity_available
                        );
                    }
                }

            } else {

                //  Bad product ID, again, possible product was deleted or deactivated - KCCO
                $unset[] = array(
                    $basketKey,
                    $itemLabel
                );
            }
        }

        //  Removing anything?
        if (!empty($unset)) {

            $basket->itemsRemoved = array();

            foreach ($unset as $item) {

                list($key, $label, $newQuantity) = $item;

                //  Remove from the local basket object
                unset($basket->items[$key]);

                //  Also remove from the main basket object
                unset($this->basket->items[$key]);

                //  Add to the itemsRemoved Array
                $basket->itemsRemoved[] = $label;
            }

            //  Reset the indexes
            $basket->items       = array_values($basket->items);
            $this->basket->items = array_values($this->basket->items);

            $this->save();
        }

        //  Anything adjusted?
        if (!empty($adjusted)) {

            $basket->itemsAdjusted = array();

            foreach ($adjusted as $item) {

                list($key, $label, $newQuantity) = $item;

                //  Adjust the local basket object
                $basket->items[$key]->quantity = $newQuantity;

                //  Adjust the main basket object
                $this->basket->items[$key]->quantity = $newQuantity;

                //  Add to the itemsAdjusted array
                $basket->itemsAdjusted[] = $label;
            }

            $this->save();
        }

        // --------------------------------------------------------------------------

        /**
         * Reset totals - if this gets called multiple times we don't want values
         * doubling up
         */

        $basket->totals->base->item              = 0;
        $basket->totals->base->item_discount     = 0;
        $basket->totals->base->shipping          = 0;
        $basket->totals->base->shipping_discount = 0;
        $basket->totals->base->tax               = 0;
        $basket->totals->base->tax_discount      = 0;
        $basket->totals->base->grand             = 0;
        $basket->totals->base->grand_discount    = 0;

        $basket->totals->user->item              = 0;
        $basket->totals->user->item_discount     = 0;
        $basket->totals->user->shipping          = 0;
        $basket->totals->user->shipping_discount = 0;
        $basket->totals->user->tax               = 0;
        $basket->totals->user->tax_discount      = 0;
        $basket->totals->user->grand             = 0;
        $basket->totals->user->grand_discount    = 0;

        // --------------------------------------------------------------------------

        //  Calculate basket item costs
        foreach ($basket->items as $item) {

            $basket->totals->base->item += $item->quantity * $item->variant->price->price->base->value_ex_tax;
            $basket->totals->user->item += $item->quantity * $item->variant->price->price->user->value_ex_tax;
        }

        // --------------------------------------------------------------------------

        //  Calculate Tax costs
        foreach ($basket->items as $item) {

            $basket->totals->base->tax += $item->quantity * $item->variant->price->price->base->value_tax;
            $basket->totals->user->tax += $item->quantity * $item->variant->price->price->user->value_tax;
        }

        // --------------------------------------------------------------------------

        //  Determine the shipping Type
        $basket->shipping->user          = $this->basket->shipping->user;
        $basket->shipping->type          = $this->getShippingType();
        $basket->shipping->isDeliverable = $this->isDeliverable();

        // --------------------------------------------------------------------------

        //  Calculate shipping costs
        if ($basket->shipping->type === 'DELIVER' || $basket->shipping->type === 'DELIVER_COLLECT') {

            $this->load->model('shop/shop_shipping_driver_model');
            $oShippingCosts = $this->shop_shipping_driver_model->calculate($basket);

            $basket->totals->base->shipping = $oShippingCosts->base;
            $basket->totals->user->shipping = $oShippingCosts->user;

        } else {

            $basket->totals->base->shipping = 0;
            $basket->totals->user->shipping = 0;
        }

        // --------------------------------------------------------------------------

        /**
         * Apply discounts. Track the totals in a class variable so that in the case
         * of a specific voucher the calculate methods can bail out when the voucher
         * is maxed.
         */

        /**
         * For each item, define a discount_item, discount_tax property, discounted_item,
         * discounted_tax on it's base price.
         */
        foreach ($basket->items as $oItem) {

            $oItemPrice = $oItem->variant->price->price->base;

            $oItemPrice->discount_item          = 0;
            $oItemPrice->discount_tax           = 0;
            $oItemPrice->discount_value_inc_tax = $oItemPrice->value_inc_tax;
            $oItemPrice->discount_value_ex_tax  = $oItemPrice->value_ex_tax;
            $oItemPrice->discount_value_tax     = $oItemPrice->value_tax;
        }

        if (!empty($basket->voucher->id)) {

            $this->iTotalItemDiscount = 0;
            $this->iTotalShipDiscount = 0;
            $this->iTotalTaxDiscount  = 0;
            $this->iTotalDiscount     = 0;

            //  Voucher applies to products onlt
            if ($basket->voucher->discount_application == 'PRODUCTS') {

                foreach ($basket->items as $oItem) {
                    $this->applyDiscountToItem($oItem, $basket->voucher);
                }

            //  Voucher applies to specific item in the basket
            } elseif ($basket->voucher->discount_application == 'PRODUCT') {

                foreach ($basket->items as $oItem) {
                    if ($oItem->product->id == $basket->voucher->product_id) {
                        $this->applyDiscountToItem($oItem, $basket->voucher);
                        break;
                    }
                }

            //  Voucher applies to specific item types in the basket
            } elseif ($basket->voucher->discount_application == 'PRODUCT_TYPES') {

                foreach ($basket->items as $oItem) {
                    if ($oItem->product->type->id == $basket->voucher->product_type_id) {
                        $this->applyDiscountToItem($oItem, $basket->voucher);
                    }
                }

            //  Voucher applies to shipping costs
            } elseif ($basket->voucher->discount_application == 'SHIPPING') {

                $iDiscount = $this->calculateShippingDiscount($basket->voucher, $basket);
                $this->iTotalShipDiscount += $iDiscount;
                $this->iTotalDiscount     += $iDiscount;

            //  Voucher applies to everything, all products and shipping
            } elseif ($basket->voucher->discount_application == 'ALL') {

                foreach ($basket->items as $oItem) {
                    $this->applyDiscountToItem($oItem, $basket->voucher);
                }

                /**
                 * If the voucher is an AMOUNT voucher then we need to do some edits here
                 * to avoid the entire voucher value being applied to the shipping cost
                 * (as the two totals are tracked independently).
                 */

                if ($basket->voucher->discount_type == 'AMOUNT' && $this->iTotalDiscount < $basket->voucher->discount_value) {

                    //  Store the existing value
                    $iVoucherValue = $basket->voucher->discount_value;

                    //  Temporarily reduce the voucher's worth (for the following calculation
                    $basket->voucher->discount_value = $basket->voucher->discount_value - $this->iTotalDiscount;

                    //  Perform the calculation
                    $iDiscount = $this->calculateShippingDiscount($basket->voucher, $basket);
                    $this->iTotalShipDiscount += $iDiscount;
                    $this->iTotalDiscount     += $iDiscount;

                    //  And reset it back to it's previous value
                    $basket->voucher->discount_value = $iVoucherValue;

                } else {

                    $iDiscount = $this->calculateShippingDiscount($basket->voucher, $basket);
                    $this->iTotalShipDiscount += $iDiscount;
                    $this->iTotalDiscount     += $iDiscount;
                }
            }

            //  Apply the new values
            $basket->totals->base->item_discount     = $this->iTotalItemDiscount;
            $basket->totals->base->shipping_discount = $this->iTotalShipDiscount;
            $basket->totals->base->tax_discount      = $this->iTotalTaxDiscount;
            $basket->totals->base->grand_discount    = $this->iTotalDiscount;

            $basket->totals->user->item_discount     = $oCurrencyModel->convertBaseToUser($basket->totals->base->item_discount);
            $basket->totals->user->shipping_discount = $oCurrencyModel->convertBaseToUser($basket->totals->base->shipping_discount);
            $basket->totals->user->tax_discount      = $oCurrencyModel->convertBaseToUser($basket->totals->base->tax_discount);
            $basket->totals->user->grand_discount    = $oCurrencyModel->convertBaseToUser($basket->totals->base->grand_discount);
        }

        /**
         * For each item, format it's discount_item and discount_tax property and
         * calculate the user's version
         */
        foreach ($basket->items as $oItem) {

            $oItemPrice = $oItem->variant->price->price;

            //  Convert user prices
            $oItemPrice->user->discount_item          = $oCurrencyModel->convertBaseToUser($oItemPrice->base->discount_item);
            $oItemPrice->user->discount_tax           = $oCurrencyModel->convertBaseToUser($oItemPrice->base->discount_tax);
            $oItemPrice->user->discount_value_inc_tax = $oCurrencyModel->convertBaseToUser($oItemPrice->base->discount_value_inc_tax);
            $oItemPrice->user->discount_value_ex_tax  = $oCurrencyModel->convertBaseToUser($oItemPrice->base->discount_value_ex_tax);
            $oItemPrice->user->discount_value_tax     = $oCurrencyModel->convertBaseToUser($oItemPrice->base->discount_value_tax);

            // Formatted strings
            $oItemPrice->base_formatted->discount_item          = $oCurrencyModel->formatBase($oItemPrice->base->discount_item);
            $oItemPrice->base_formatted->discount_tax           = $oCurrencyModel->formatBase($oItemPrice->base->discount_tax);
            $oItemPrice->base_formatted->discount_value_inc_tax = $oCurrencyModel->formatBase($oItemPrice->base->discount_value_inc_tax);
            $oItemPrice->base_formatted->discount_value_ex_tax  = $oCurrencyModel->formatBase($oItemPrice->base->discount_value_ex_tax);
            $oItemPrice->base_formatted->discount_value_tax     = $oCurrencyModel->formatBase($oItemPrice->base->discount_value_tax);

            $oItemPrice->user_formatted->discount_item          = $oCurrencyModel->formatUser($oItemPrice->user->discount_item);
            $oItemPrice->user_formatted->discount_tax           = $oCurrencyModel->formatUser($oItemPrice->user->discount_tax);
            $oItemPrice->user_formatted->discount_value_inc_tax = $oCurrencyModel->formatUser($oItemPrice->user->discount_value_inc_tax);
            $oItemPrice->user_formatted->discount_value_ex_tax  = $oCurrencyModel->formatUser($oItemPrice->user->discount_value_ex_tax);
            $oItemPrice->user_formatted->discount_value_tax     = $oCurrencyModel->formatUser($oItemPrice->user->discount_value_tax);

            //  Place this at the top level of the ite, too
            $oItem->price = $oItemPrice;
        }

        // --------------------------------------------------------------------------

        //  Calculate grand totals
        $basket->totals->base->grand  = $basket->totals->base->item;
        $basket->totals->base->grand += $basket->totals->base->shipping;
        $basket->totals->base->grand += $basket->totals->base->tax;
        $basket->totals->base->grand -= $basket->totals->base->grand_discount;

        $basket->totals->user->grand  = $basket->totals->user->item;
        $basket->totals->user->grand += $basket->totals->user->shipping;
        $basket->totals->user->grand += $basket->totals->user->tax;
        $basket->totals->user->grand -= $basket->totals->user->grand_discount;

        // --------------------------------------------------------------------------

        //  If item prices are inclusive of tax then show the items total + tax
        if (!appSetting('price_exclude_tax', 'shop')) {

            $basket->totals->base->item += $basket->totals->base->tax;
            $basket->totals->user->item += $basket->totals->user->tax;
        }

        // --------------------------------------------------------------------------

        //  Format totals
        $basket->totals->base_formatted->item              = $oCurrencyModel->formatBase($basket->totals->base->item);
        $basket->totals->base_formatted->item_discount     = $oCurrencyModel->formatBase($basket->totals->base->item_discount);
        $basket->totals->base_formatted->shipping          = $oCurrencyModel->formatBase($basket->totals->base->shipping);
        $basket->totals->base_formatted->shipping_discount = $oCurrencyModel->formatBase($basket->totals->base->shipping_discount);
        $basket->totals->base_formatted->tax               = $oCurrencyModel->formatBase($basket->totals->base->tax);
        $basket->totals->base_formatted->tax_discount      = $oCurrencyModel->formatBase($basket->totals->base->tax_discount);
        $basket->totals->base_formatted->grand             = $oCurrencyModel->formatBase($basket->totals->base->grand);
        $basket->totals->base_formatted->grand_discount    = $oCurrencyModel->formatBase($basket->totals->base->grand_discount);

        $basket->totals->user_formatted->item              = $oCurrencyModel->formatUser($basket->totals->user->item);
        $basket->totals->user_formatted->item_discount     = $oCurrencyModel->formatUser($basket->totals->user->item_discount);
        $basket->totals->user_formatted->shipping          = $oCurrencyModel->formatUser($basket->totals->user->shipping);
        $basket->totals->user_formatted->shipping_discount = $oCurrencyModel->formatUser($basket->totals->user->shipping_discount);
        $basket->totals->user_formatted->tax               = $oCurrencyModel->formatUser($basket->totals->user->tax);
        $basket->totals->user_formatted->tax_discount      = $oCurrencyModel->formatUser($basket->totals->user->tax_discount);
        $basket->totals->user_formatted->grand             = $oCurrencyModel->formatUser($basket->totals->user->grand);
        $basket->totals->user_formatted->grand_discount    = $oCurrencyModel->formatUser($basket->totals->user->grand_discount);

        // --------------------------------------------------------------------------

        //  Save to cache and spit it back
        $this->_set_cache($this->cacheKey, $basket);

        return $basket;
    }

    // --------------------------------------------------------------------------

    protected function applyDiscountToItem($oItem, $oVoucher)
    {
        //  Shortcut
        $oItemPrice = $oItem->variant->price->price;

        //  Item discount (pre-tax)
        $iItemDiscount = $this->calculateItemDiscount($oVoucher, $oItem);
        $this->iTotalItemDiscount += $iItemDiscount;
        $this->iTotalDiscount     += $iItemDiscount;

        //  Tax Discount
        $iTaxDiscount = $this->calculateTaxDiscount($oVoucher, $oItem);
        $this->iTotalTaxDiscount += $iTaxDiscount;
        $this->iTotalDiscount    += $iTaxDiscount;

        //  Update values
        $oItemPrice->base->discount_item           = $iItemDiscount;
        $oItemPrice->base->discount_tax            = $iTaxDiscount;
        $oItemPrice->base->discount_value_inc_tax -= $iItemDiscount;
        $oItemPrice->base->discount_value_inc_tax -= $iTaxDiscount;
        $oItemPrice->base->discount_value_ex_tax  -= $iItemDiscount;
        $oItemPrice->base->discount_value_tax     -= $iTaxDiscount;
    }

    // --------------------------------------------------------------------------

    protected function calculateItemDiscount($oVoucher, $oItem)
    {
        $iItemPrice = $oItem->variant->price->price->base->value_ex_tax;
        $iDiscount  = 0;
        if ($oVoucher->discount_type == 'PERCENTAGE') {

            $iDiscount = (int) round($iItemPrice * $oItem->quantity * ($oVoucher->discount_value/100));

        } else if ($oVoucher->discount_type == 'AMOUNT') {

            //  Ensure we've not maxed the discount
            if ($this->iTotalItemDiscount < $oVoucher->discount_value) {
                if ($oVoucher->discount_value < $iItemPrice) {
                    $iDiscount = $oVoucher->discount_value;
                } else {
                    $iDiscount = $iItemPrice;
                }
            }
        }

        return $iDiscount;
    }

    // --------------------------------------------------------------------------

    protected function calculateTaxDiscount($oVoucher, $oItem)
    {
        $iItemPrice = $oItem->variant->price->price->base->value_tax;
        $iDiscount  = 0;
        if ($oVoucher->discount_type == 'PERCENTAGE') {

            $iDiscount = (int) round($iItemPrice * $oItem->quantity * ($oVoucher->discount_value/100));

        } else if ($oVoucher->discount_type == 'AMOUNT') {

            //  Ensure we've not maxed the discount
            if ($this->iTotalItemDiscount < $oVoucher->discount_value) {
                if ($oVoucher->discount_value < $iItemPrice) {
                    $iDiscount = $oVoucher->discount_value;
                } else {
                    $iDiscount = $iItemPrice;
                }
            }
        }

        return $iDiscount;
    }

    // --------------------------------------------------------------------------

    /**
     * Calculate the discount to apply to the basket's shipping costs
     * @param  object $oVoucher The voucher object
     * @param  object $oBasket  The basket object
     * @return int
     */
    protected function calculateShippingDiscount($oVoucher, $oBasket)
    {
        $iDiscount = 0;
        if ($oVoucher->discount_type == 'PERCENTAGE') {

            $iDiscount = (int) round($oBasket->totals->base->shipping * ($oVoucher->discount_value/100));

        } else if ($oVoucher->discount_type == 'AMOUNT') {

            //  Ensure we've not maxed the discount
            if ($this->iTotalShipDiscount < $oVoucher->discount_value) {
                if ($oVoucher->discount_value < $oBasket->totals->base->shipping) {
                    $iDiscount = $oVoucher->discount_value;
                } else {
                    $iDiscount = $oBasket->totals->base->shipping;
                }
            }
        }

        return $iDiscount;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the number of items in the basket.
     * @param  boolean $respectQuantity If true then the number of items in the basket is counted rather than just the number of items
     * @return int
     */
    public function getCount($respectQuantity = true)
    {
        if ($respectQuantity) {

            $count = 0;

            foreach ($this->basket->items as $item) {

                $count += $item->quantity;
            }

            return $count;

        } else {

            return count($this->basket->items);
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the total value of the basket, in the user's currency, optionally
     * formatted
     * @param  boolean $formatted Whether to return the formatted value or not
     * @return string
     */
    public function getTotal($formatted = true)
    {
        $basket = $this->get();
        if ($formatted) {

            return $basket->totals->user_formatted->grand;

        } else {

            return $basket->totals->user->grand;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Adds an item to the basket, if it's already in the basket it increments it
     * by $quantity.
     * @param int $variantId The ID of the variant to add
     * @param int $quantity  The quantity to add
     * @return boolean
     */
    public function add($variantId, $quantity = 1)
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

        //  Check if item is already in the basket.
        $key = $this->getBasketKeyByVariantId($variantId);

        // --------------------------------------------------------------------------

        if ($key !== false) {

            //  Already in the basket, increment
            return $this->increment($variantId, $quantity);
        }

        // --------------------------------------------------------------------------

        //  Check the product ID is valid
        $_product = $this->shop_product_model->getByVariantId($variantId);

        if (!$_product) {

            $this->_set_error('No Product for that Variant ID.');
            return false;
        }

        $_variant = null;
        foreach ($_product->variations as $variant) {

            if ($variantId == $variant->id) {

                $_variant = $variant;
                break;
            }
        }

        if (!$_variant) {

            $this->_set_error('Invalid Variant ID.');
            return false;
        }

        // --------------------------------------------------------------------------

        //  Check product is active
        if (!$_product->is_active) {

            $this->_set_error('Product is not available.');
            return false;
        }

        // --------------------------------------------------------------------------

        /**
         * Check the product is available; a product is available if:
         * - $_variant->quantity_available is null (unlimited)
         * - $quantity <= $_variant->quantity_available
         */

        if (!is_null($_variant->quantity_available) && $_variant->quantity_available == 0) {

            $this->_set_error('Product is not available.');
            return false;

        } else if (!is_null($_variant->quantity_available) && $quantity > $_variant->quantity_available) {

            //  Asking for more items than are available, reduce.
            $quantity = $_variant->quantity_available;
        }

        // --------------------------------------------------------------------------

        //  All good, add to basket
        $temp             = new \stdClass();
        $temp->variant_id = $variantId;
        $temp->product_id = $_product->id;
        $temp->quantity   = $quantity;

        //  @todo: remove dependency on these fields
        $temp->product_label = $_product->label;
        $temp->variant_label = $_variant->label;
        $temp->variant_sku   = $_variant->sku;

        $this->basket->items[] = $temp;

        unset($temp);

        // --------------------------------------------------------------------------

        //  Invalidate the basket cache
        $this->saveSession();
        $this->_unset_cache($this->cacheKey);

        // --------------------------------------------------------------------------

        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * Removes a variant from the basket
     * @param  int $variantId The variant's ID
     * @return boolean
     */
    public function remove($variantId)
    {
        $key = $this->getBasketKeyByVariantId($variantId);

        // --------------------------------------------------------------------------

        if ($key !== false) {

            return $this->removeByKey($key);

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
        unset($this->basket->items[$key]);
        $this->basket->items = array_values($this->basket->items);

        // --------------------------------------------------------------------------

        //  Invalidate the basket cache
        $this->saveSession();
        $this->_unset_cache($this->cacheKey);

        // --------------------------------------------------------------------------

        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * Increments the quantity of an item in the basket.
     * @param  int  $variantId   The variant's ID
     * @param  int  $incrementBy The amount to increment the item by
     * @return boolean
     */
    public function increment($variantId, $incrementBy = 1)
    {
        $key = $this->getBasketKeyByVariantId($variantId);

        // --------------------------------------------------------------------------

        if ($key !== false) {

            $item = $this->shop_product_model->getByVariantId($variantId);

            if ($item) {

                //  We need to check that we can increment the item by the desired amount
                $quantity     = $this->basket->items[$key]->quantity;
                $newQuantity  = $quantity + $incrementBy;
                $available    = 0;
                $maxIncrement = $item->type->max_per_order;

                foreach ($item->variations as $variant) {

                    if ($variant->id == $variantId) {

                        $available = $variant->quantity_available;
                        break;
                    }
                }

                /**
                 * Determine whether the user can increment the product. In order to be
                 * incrementable there must:
                 * - Be sufficient stock (or unlimited)
                 * - not exceed any limit imposed by the product type
                 */

                if (is_null($available)) {

                    //  Unlimited quantity
                    $sufficient = true;

                } else if ($newQuantity <= $available) {

                    //  Fewer than the quantity available, user can increment
                    $sufficient = true;

                } else {

                    $sufficient = false;
                }

                if (empty($maxIncrement)) {

                    //  Unlimited additions allowed
                    $notExceed = true;

                } else if ($newQuantity <= $maxIncrement) {

                    //  Not exceeded the maximum per order, user can increment
                    $notExceed = true;

                } else {

                    $notExceed = false;
                }

                if ($sufficient && $notExceed) {

                    //  Increment
                    $this->basket->items[$key]->quantity = $newQuantity;

                    //  Invalidate the basket cache
                    $this->saveSession();
                    $this->_unset_cache($this->cacheKey);

                    return true;

                } else {

                    $this->_set_error('You cannot increment this item that many times.');
                    return false;
                }

            } else {

                $this->_set_error('Could not find product.');
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
     * @param  int  $variantId   The variant's ID
     * @param  int  $decrementBy The amount to decrement the item by
     * @return boolean
     */
    public function decrement($variantId, $decrementBy = 1)
    {
        $key = $this->getBasketKeyByVariantId($variantId);

        // --------------------------------------------------------------------------

        if ($key !== false) {

            $maxDecrement = $this->basket->items[$key]->quantity;

            if ($maxDecrement > 1) {

                if ($decrementBy >= $maxDecrement) {

                    //  The requested decrement will take the quantity to 0 or less just remove it.
                    $this->remove($variantId);

                } else {

                    //  Decrement
                    $this->basket->items[$key]->quantity -= $decrementBy;

                    //  Invalidate the basket cache
                    $this->saveSession();
                    $this->_unset_cache($this->cacheKey);
                }

            } else {

                $this->remove($variantId);
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
        return $this->basket->customer->details;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the basket's "customer details" object.
     * @return boolean
     */
    public function addCustomerDetails($details)
    {
        //  @todo: verify?
        $this->basket->customer->details = $details;

        //  Invalidate the basket cache
        $this->saveSession();
        $this->_unset_cache($this->cacheKey);

        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * Resets the basket's "customer details" object.
     * @return boolean
     */
    public function removeCustomerDetails()
    {
        $this->basket->customer->details = $this->defaultCustomerDetails();

        //  Invalidate the basket cache
        $this->saveSession();
        $this->_unset_cache($this->cacheKey);

        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the basket's "shipping details" object.
     * @return stdClass
     */
    public function getShippingDetails()
    {
        return $this->basket->shipping->details;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the basket's "shipping details" object.
     * @return boolean
     */
    public function addShippingDetails($details)
    {
        //  @todo: verify?
        $this->basket->shipping->details = $details;

        //  Invalidate the basket cache
        $this->saveSession();
        $this->_unset_cache($this->cacheKey);

        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * Resets the basket's "shipping details" object.
     * @return boolean
     */
    public function removeShippingDetails()
    {
        $this->basket->shipping->details = $this->defaultShippingDetails();

        //  Invalidate the basket cache
        $this->saveSession();
        $this->_unset_cache($this->cacheKey);

        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the basket's "shipping type" object.
     * @return stdClass
     */
    public function getShippingType()
    {
        /**
         * If the basket is a DELIVER basket then check if there are any collect only
         * items. If we find a collect only item then mark as DELIVER_COLLECT, unless
         * ALL the items are for colelct in which case it's a COLLECT order.
         */

        if ($this->basket->shipping->user === 'DELIVER') {

            $numCollectOnlyItems = 0;
            foreach ($this->basket->items as $item) {
                if ($item->variant->ship_collection_only) {
                    $numCollectOnlyItems++;
                }
            }

            if ($numCollectOnlyItems > 0 && $numCollectOnlyItems < count($this->basket->items)) {

                return 'DELIVER_COLLECT';

            } elseif ($numCollectOnlyItems > 0 && $numCollectOnlyItems === count($this->basket->items)) {

                return 'COLLECT';

            } else {

                return 'DELIVER';
            }

        } else {

            return $this->basket->shipping->user;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the basket's "shipping type" object.
     * @return boolean
     */
    public function addShippingType($deliveryType)
    {
        if ($deliveryType == 'COLLECT' || $deliveryType == 'DELIVER') {

            $this->basket->shipping->user = $deliveryType;

            //  Invalidate the basket cache
            $this->saveSession();
            $this->_unset_cache($this->cacheKey);

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
        $this->basket->shipping->user = $this->defaultShippingType();

        //  Invalidate the basket cache
        $this->saveSession();
        $this->_unset_cache($this->cacheKey);

        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns whether the basket is deliverable or not
     * @return boolean
     */
    public function isDeliverable()
    {
        $numCollectOnlyItems = 0;
        foreach ($this->basket->items as $item) {
            if ($item->variant->ship_collection_only) {
                $numCollectOnlyItems++;
            }
        }

        return $numCollectOnlyItems !== count($this->basket->items);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the basket's "payment gatway" object.
     * @return stdClass
     */
    public function getPaymentGateway()
    {
        return $this->basket->payment_gateway;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the basket's "payment gateway" object.
     * @return boolean
     */
    public function addPaymentGateway($payment_gateway)
    {
        //  @todo: verify?
        $this->basket->payment_gateway = $payment_gateway;

        //  Invalidate the basket cache
        $this->saveSession();
        $this->_unset_cache($this->cacheKey);

        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * Resets the basket's "payment gateway" object.
     * @return boolean
     */
    public function removePaymentGateway()
    {
        $this->basket->payment_gateway = $this->defaultPaymentGateway();

        //  Invalidate the basket cache
        $this->saveSession();
        $this->_unset_cache($this->cacheKey);

        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the basket's "order" object.
     * @return stdClass
     */
    public function getOrder()
    {
        return $this->basket->order;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the basket's "order" object.
     * @return boolean
     */
    public function addOrder($order)
    {
        //  @todo: verify?
        $this->basket->order = $order;

        //  Invalidate the basket cache
        $this->saveSession();
        $this->_unset_cache($this->cacheKey);

        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * Resets the basket's "order" object.
     * @return boolean
     */
    public function removeOrder()
    {
        $this->basket->order = $this->defaultOrder();

        //  Invalidate the basket cache
        $this->saveSession();
        $this->_unset_cache($this->cacheKey);

        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the basket's "voucher" object.
     * @return stdClass
     */
    public function getVoucher()
    {
        return $this->basket->voucher;
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

        $oVoucherModel = Factory::model('Voucher', 'nailsapp/module-shop');
        $oVoucher      = $oVoucherModel->validate($voucher_code, $this->get());

        if ($oVoucher) {

            $this->basket->voucher = $oVoucher;

            //  Invalidate the basket cache
            $this->saveSession();
            $this->_unset_cache($this->cacheKey);

            return true;

        } else {

            $this->removeVoucher();
            $this->_set_error($oVoucherModel->last_error());

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
        $this->basket->voucher = $this->defaultVoucher();

        //  Invalidate the basket cache
        $this->saveSession();
        $this->_unset_cache($this->cacheKey);

        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * Retrieves the note associated with an order
     * @return string
     */
    public function getNote()
    {
        return $this->basket->note;
    }

    // --------------------------------------------------------------------------

    /**
     * Adds a note to the basket
     * @param string $note The note to add
     */
    public function addNote($note)
    {
        $this->basket->note = $note;

        //  Invalidate the basket cache
        $this->saveSession();
        $this->_unset_cache($this->cacheKey);

        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * Resets the basket's "note" object
     * @return void
     */
    public function removeNote()
    {
        $this->basket->note = $this->defaultNote();

        //  Invalidate the basket cache
        $this->saveSession();
        $this->_unset_cache($this->cacheKey);

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
        return $this->getBasketKeyByVariantId($variant_id) !== false;
    }

    // --------------------------------------------------------------------------

    public function getVariantQuantity($variant_id)
    {
        $key = $this->getBasketKeyByVariantId($variant_id);

        if ($key !== false) {

            return $this->basket->items[$key]->quantity;

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
        $_save                   = new \stdClass();
        $_save->items            = $this->basket->items;
        $_save->order            = $this->basket->order;
        $_save->customer_details = $this->basket->customer->details;
        $_save->shipping_type    = $this->basket->shipping->user;
        $_save->shipping_details = $this->basket->shipping->details;
        $_save->payment_gateway  = $this->basket->payment_gateway;
        $_save->voucher          = new \stdClass();
        $_save->voucher->code    = $this->basket->voucher->code;
        $_save->note             = $this->basket->note;

        return json_encode($_save);
    }

    // --------------------------------------------------------------------------

    /**
     * Saves the 'save object' to the user's meta record
     * @return void
     */
    protected function saveSession()
    {
        if (!headers_sent()) {

            $this->session->set_userdata($this->sessVar, $this->saveObject());
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Saves the 'save object' to the user's meta record
     * @return void
     */
    protected function saveUser()
    {
        //  If logged in, save the basket to the user's meta data for safe keeping.
        if ($this->oUser->isLoggedIn()) {

            $this->oUserMeta->update(
                NAILS_DB_PREFIX . 'user_meta_shop',
                activeUser('id'),
                array(
                    'basket' => $this->saveObject()
                )
            );
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Reset's the basket to it's default (empty) state.
     * @return void
     */
    public function destroy()
    {
        $this->basket = $this->defaultBasket();

        // --------------------------------------------------------------------------

        //  Invalidate the basket cache
        $this->saveSession();
        $this->_unset_cache($this->cacheKey);
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches a basket key using the variant's ID
     * @param  int $variantId The ID of the variant
     * @return mixed          Int on success false on failure
     */
    protected function getBasketKeyByVariantId($variantId)
    {
        foreach ($this->basket->items as $key => $item) {

            if ($variantId == $item->variant_id) {

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
        $out                    = new \stdClass();
        $out->items             = array();
        $out->order             = $this->defaultOrder();
        $out->customer          = new \stdClass();
        $out->customer->details = $this->defaultCustomerDetails();
        $out->shipping          = new \stdClass();
        $out->shipping->user    = $this->defaultShippingType();
        $out->shipping->details = $this->defaultShippingDetails();
        $out->payment_gateway   = $this->defaultPaymentGateway();
        $out->voucher           = $this->defaultVoucher();
        $out->note              = $this->defaultNote();

        $out->totals                 = new \stdClass();
        $out->totals->base           = new \stdClass();
        $out->totals->base_formatted = new \stdClass();
        $out->totals->user           = new \stdClass();
        $out->totals->user_formatted = new \stdClass();

        $out->totals->base->item              = 0;
        $out->totals->base->item_discount     = 0;
        $out->totals->base->shipping          = 0;
        $out->totals->base->shipping_discount = 0;
        $out->totals->base->tax               = 0;
        $out->totals->base->tax_discount      = 0;
        $out->totals->base->grand             = 0;
        $out->totals->base->grand_discount    = 0;

        $out->totals->base_formatted->item              = '';
        $out->totals->base_formatted->item_discount     = '';
        $out->totals->base_formatted->shipping          = '';
        $out->totals->base_formatted->shipping_discount = '';
        $out->totals->base_formatted->tax               = '';
        $out->totals->base_formatted->tax_discount      = '';
        $out->totals->base_formatted->grand             = '';
        $out->totals->base_formatted->grand_discount    = '';

        $out->totals->user->item              = 0;
        $out->totals->user->item_discount     = 0;
        $out->totals->user->shipping          = 0;
        $out->totals->user->shipping_discount = 0;
        $out->totals->user->tax               = 0;
        $out->totals->user->tax_discount      = 0;
        $out->totals->user->grand             = 0;
        $out->totals->user->grand_discount    = 0;

        $out->totals->user_formatted->item              = '';
        $out->totals->user_formatted->item_discount     = '';
        $out->totals->user_formatted->shipping          = '';
        $out->totals->user_formatted->shipping_discount = '';
        $out->totals->user_formatted->tax               = '';
        $out->totals->user_formatted->tax_discount      = '';
        $out->totals->user_formatted->grand             = '';
        $out->totals->user_formatted->grand_discount    = '';

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns a default, empty, basket "order" object
     * @return stdClass
     */
    protected function defaultOrder()
    {
        $out     = new \stdClass();
        $out->id = null;

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns a default, empty, basket "payment gateway" object
     * @return stdClass
     */
    protected function defaultPaymentGateway()
    {
        $out     = new \stdClass();
        $out->id = null;

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns a default, empty, basket "voucher" object
     * @return stdClass
     */
    protected function defaultVoucher()
    {
        $out       = new \stdClass();
        $out->id   = null;
        $out->code = null;

        return $out;
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
        $out             = new \stdClass();
        $out->id         = null;
        $out->first_name = null;
        $out->last_name  = null;
        $out->email      = null;

        return $out;
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

        $_out            = new \stdClass();
        $_out->addressee = null;    //  Named addresse
        $_out->line_1    = null;    //  Building number and street name
        $_out->line_2    = null;    //  Locality name, if required
        $_out->town      = null;    //  Town
        $_out->state     = null;    //  State
        $_out->postcode  = null;    //  Postcode
        $_out->country   = null;    //  Country

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
