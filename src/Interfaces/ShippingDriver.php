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

namespace Nails\Shop\Interfaces;

interface ShippingDriver
{
    /**
     * Accepts an array of config values from the main driver model
     * @param array $aConfig The configs to set
     * @return array
     */
    public function setConfig($aConfig);

    /**
     * Returns available shipping options for the items in the basket
     * @param  array    $aShippableItems An array of all shippable items
     * @param  stdClass $oBasket         The entire basket object
     * @return float
     */
    public function options($aShippableItems, $oBasket);

    /**
     * Calculates the cost of shipping, in the shop's base currency.
     * @param  array    $aShippableItems An array of all shippable items
     * @param  string   $sOptionSlug     The chosen shipping option's slug
     * @param  stdClass $oBasket         The entire basket object
     * @return integer
     */
    public function calculate($aShippableItems, $sOptionSlug, $oBasket);

    /**
     * Calculates the cost of shipping an individual variant, in the shop's
     * base currency.
     * @param  object   $oVariant An array of all shippable items
     * @param  stdClass $oBasket  The entire basket object
     * @return integer
     */
    public function calculateVariant($oVariant, $oBasket = null);

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
