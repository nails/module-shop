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
     * Returns available shipping options.
     * @return array
     */
    public function options()
    {
        throw new ShippingDriverException('Driver must define options()', 0);
    }

    // --------------------------------------------------------------------------

    /**
     * Calculates the cost of shipping, in the shop's base currency.
     * @param  array    $aShippableItems An array of all shippable items
     * @param  string   $sOptionSlug     The chosen shipping option's slug
     * @param  stdClass $oBasket         The entire basket object
     * @return integer
     */
    public function calculate($aShippableItems, $sOptionSlug, $oBasket)
    {
        throw new ShippingDriverException('Driver must define calculate()', 0);
    }

    // --------------------------------------------------------------------------

    /**
     * Calculates the cost of shipping an individual variant, in the shop's
     * base currency.
     * @param  object   $oVariant An array of all shippable items
     * @param  stdClass $oBasket  The entire basket object
     * @return integer
     */
    public function calculateVariant($oVariant, $oBasket = null)
    {
        throw new ShippingDriverException('Driver must define calculateVariant()', 0);
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
}
