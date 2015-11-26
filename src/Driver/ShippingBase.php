<?php

/**
 * This interface is implemented by all Shop shipping drivers
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Driver
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Shop\Driver;

class ShippingBase
{
    /**
     * Returns available shipping options for the items in the basket
     * @param  array    $aShippableItems An array of all shippable items
     * @param  stdClass $oBasket         The entire basket object
     * @return array
     */
    public function options($aShippableItems, $oBasket)
    {
        return array();
    }

    // --------------------------------------------------------------------------

    /**
     * Calculates the cost of shipping, in the shop's base currency.
     * It is important that this return a float.
     * @param  array    $aShippableItems An array of all shippable items
     * @param string    $sOptionSlug     The chosen shipping option's slug
     * @param  stdClass $oBasket         The entire basket object
     * @return float
     */
    public function calculate($aShippableItems, $sOptionSlug, $oBasket)
    {
        return 0;
    }

    // --------------------------------------------------------------------------

    /**
     * Calculates the cost of shipping an individual variant, in the shop's
     * base currency. It is important that this return a float.
     * @param  object   $oVariant An array of all shippable items
     * @param  stdClass $oBasket  The entire basket object
     * @return float
     */
    public function calculateVariant($oVariant, $oBasket = null)
    {
        return 0;
    }

    // --------------------------------------------------------------------------

    /**
     * Specifies any configurable fields
     * @return array
     */
    public function configure()
    {
        return array();
    }

    // --------------------------------------------------------------------------

    /**
     * Specifies any basket-wide shipping options
     * @return array
     */
    public function fieldsBasket()
    {
        return array();
    }

    // --------------------------------------------------------------------------

    /**
     * Specified any product-wide shipping options
     * @return array
     */
    public function fieldsProduct()
    {
        return array();
    }

    // --------------------------------------------------------------------------

    /**
     * Specifies any variant-specific shipping options
     * @return array
     */
    public function fieldsVariant()
    {
        return array();
    }

    // --------------------------------------------------------------------------
}
