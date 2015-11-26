<?php

/**
 * This interface is implemented by all Shop shipping drivers
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Interface
 * @author      Nails Dev Team
 * @link
 */

interface ShippingDriver
{
    /**
     * Returns available shipping options for the items in the basket
     * @param  array    $aShippableItems An array of all shippable items
     * @param  stdClass $oBasket         The entire basket object
     * @return float
     */
    public function options($aShippableItems, $oBasket);

    /**
     * Calculates the cost of shipping, in the shop's base currency.
     * It is important that this return a float.
     * @param  array    $aShippableItems An array of all shippable items
     * @param string    $sOptionSlug     The chosen shipping option's slug
     * @param  stdClass $oBasket         The entire basket object
     * @return float
     */
    public function calculate($aShippableItems, $sOptionSlug, $oBasket);

    /**
     * Calculates the cost of shipping an individual variant, in the shop's
     * base currency. It is important that this return a float.
     * @param  object   $oVariant An array of all shippable items
     * @param  stdClass $oBasket  The entire basket object
     * @return float
     */
    public function calculateVariant($oVariant, $oBasket = null);

    /**
     * Specifies any configurable fields
     * @return array
     */
    public function configure();

    /**
     * Specifies any basket-wide shipping options
     * @return array
     */
    public function fieldsBasket();

    /**
     * Specified any product-wide shipping options
     * @return array
     */
    public function fieldsProduct();

    /**
     * Specifies any variant-specific shipping options
     * @return array
     */
    public function fieldsVariant();
}
