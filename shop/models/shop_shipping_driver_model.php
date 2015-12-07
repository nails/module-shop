<?php

/**
 * This model abstracts Shop Shipping Drivers
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

use Nails\Factory;
use Nails\Shop\Exception\ShippingDriverException;

class NAILS_Shop_shipping_driver_model extends NAILS_Model
{
    protected $aAvailable;
    protected $oDriverConfig;
    protected $oDriver;

    // --------------------------------------------------------------------------

    const DEFAULT_DRIVER = 'nailsapp/driver-shop-shipping-flatrate';

    // --------------------------------------------------------------------------

    /**
     * Construct the model.
     */
    public function __construct()
    {
        parent::__construct();
        $this->aAvailable = _NAILS_GET_DRIVERS('nailsapp/module-shop', 'shipping');

        //  Load the active shipping driver
        $sDriverSlug         = appSetting('enabled_shipping_driver', 'shop') ?: self::DEFAULT_DRIVER;
        $this->oDriverConfig = $this->get($sDriverSlug);

        if (empty($this->oDriverConfig)) {
            throw new ShippingDriverException(
                'Could not find driver "' . $sDriverSlug . '".',
                1
            );
        }

        $this->oDriver = _NAILS_GET_DRIVER_INSTANCE($this->oDriverConfig);

        if (empty($this->oDriver)) {
            throw new ShippingDriverException(
                'Failed to load shipping driver "' . $sDriverSlug . '".',
                2
            );
        }

        if (!($this->oDriver instanceof \Nails\Shop\Driver\ShippingBase)) {
            throw new DriverException(
                'Driver "' . $sDriverSlug . '" must extend \Nails\Shop\Driver\ShippingBase',
                3
            );
        }

        //  Apply driver configurations
        $aSettings = array();
        if (!empty($this->oDriverConfig->data->settings)) {
            foreach ($this->oDriverConfig->data->settings as $oSetting) {
                $sValue = appSetting($oSetting->key, 'shop-driver-' . $this->oDriverConfig->slug);
                if(is_null($sValue) && isset($oSetting->default)) {
                    $sValue = $oSetting->default;
                }
                $aSettings[$oSetting->key] = $sValue;
            }
        }

        $this->oDriver->setConfig($aSettings);
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches all available shipping drivers
     * @param  boolean $refresh Fetchf rom refresh - skip the cache
     * @return array
     */
    public function getAvailable($refresh = false)
    {
        return $this->aAvailable;
    }

    // --------------------------------------------------------------------------

    /**
     * Gets a single driver
     * @param  string   $sSlug The driver's slug
     * @return stdClass
     */
    public function get($sSlug)
    {
        $aShippingDrivers = $this->getAvailable();
        foreach ($aShippingDrivers as $oDriver) {
            if ($oDriver->slug == $sSlug) {
                return $oDriver;
            }
        }
        return false;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the config for the enabled driver
     * @return sdClass
     */
    public function getEnabled()
    {
        return $this->oDriverConfig;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of the shippable items from the basket object
     * @param  object $basket The basket object
     * @return array
     */
    private function getShippableItemsFromBasket($basket)
    {
        $aShippableItems = array();

        foreach ($basket->items as $item) {

            if (!empty($item->product->type->is_physical) && empty($item->variant->shipping->collection_only)) {

                $aShippableItems[] = $item;
            }
        }

        return $aShippableItems;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of the available shipping options
     * @return array
     */
    public function options($oBasket)
    {
        /**
         * Ask the driver what the available options are, pass it the shippable items so it can
         * amend it's responses as nessecary and work out the cost
         */

        $aShippableItems = $this->getShippableItemsFromBasket($oBasket);
        $aOptions        = $this->oDriver->options($aShippableItems, $oBasket);

        $aSlugs      = array();
        $bHasDefault = false;
        $aOut        = array();

        //  If warehouse collection is enabled then add it as an option
        if (appSetting('warehouse_collection_enabled', 'shop')) {

            $oTemp                 = new \stdClass();
            $oTemp->slug           = 'COLLECTION';
            $oTemp->label          = 'Collection';
            $oTemp->cost           = 0;
            $oTemp->cost_formatted = 'FREE';
            $oTemp->default        = false;

            $aOut[] = $oTemp;
        }

        //  Test options
        foreach ($aOptions as &$aOption) {

            if (empty($aOption['slug'])) {
                throw new ShippingDriverException('Each shipping option must provide a unique slug', 1);
            }

            if (in_array($aOption['slug'], $aSlugs)) {
                throw new ShippingDriverException('"' . $aOption['slug'] . '" is not a unique shipping option slug', 2);
            }

            //  Can only have one default value, the first defined.
            if (!empty($aOption['default']) && $bHasDefault) {
                $aOption['default'] = false;
            } elseif (!empty($aOption['default'])) {
                $bHasDefault = true;
            }

            $aSlugs[] = $aOption['slug'];
        }

        //  Prepare each item
        foreach ($aOptions as &$aOption) {

            if (is_int($aOption['cost']) || is_numeric($aOption['cost'])) {

                $aOption['cost'] = (int) $aOption['cost'];

            } elseif (!is_float($aOption['cost'])) {

                $aOption['cost'] = 0;
            }

            //  Convert the base price to the user's currency
            $oCurrencyModel = Factory::model('Currency', 'nailsapp/module-shop');

            $oTemp                 = new \stdClass();
            $oTemp->slug           = $aOption['slug'];
            $oTemp->label          = $aOption['label'];
            $oTemp->cost           = $oCurrencyModel->convertBaseToUser($aOption['cost']);
            $oTemp->cost_formatted = $aOption['cost'] > 0 ? $oCurrencyModel->formatUser($aOption['cost']) : 'FREE';
            $oTemp->default        = (bool) $aOption['default'];

            $aOut[] = $oTemp;
        }

        return $aOut;
    }

    // --------------------------------------------------------------------------

    /**
     * Retursn the default option string
     * @return string
     */
    public function defaultOption()
    {
        return $this->oDriver->defaultOption();
    }

    // --------------------------------------------------------------------------

    /**
     * Takes a basket object and calculates the cost of shipping
     * @param  stdClass $basket A basket object
     * @return stdClass
     */
    public function calculate($basket)
    {
        $oFree       = new \stdClass();
        $oFree->base = (int) 0;
        $oFree->user = (int) 0;

        // --------------------------------------------------------------------------

        //  If the shipping type is COLLECTION (a special type) then shipping is FREE
        if ($basket->shipping->option == 'COLLECTION') {
            return $oFree;
        }

        // --------------------------------------------------------------------------

        /**
         * Have the driver calculate the cost of shipping, this should return an integer
         * which is in the base currency. It is passed an array of all shippable items
         * (i.e., items who's type marks them as `is_physical` and is not set to
         * `collect only`), as well as a reference to the basket, shold the driver need
         * to know anything else about the order.
         */

        $aShippableItems = $this->getShippableItemsFromBasket($basket);
        $iCost           = $this->oDriver->calculate($aShippableItems, $basket->shipping->option, $basket);

        if (!is_integer($iCost) || $iCost < 0) {
            throw new ShippingDriverException(
                'The value returned by the shipping driver must be a positive integer or zero.',
                5
            );
        }

        $out       = new \stdClass();
        $out->base = $iCost;

        //  Convert the base price to the user's currency
        $oCurrencyModel = Factory::model('Currency', 'nailsapp/module-shop');
        $out->user      = $oCurrencyModel->convertBaseToUser($iCost);

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Takes a product variant ID and works out what the shipping would be on it
     * @param  integer  $iVariantId ID of the variant in question
     * @return stdClass
     */
    public function calculateVariant($iVariantId)
    {
        $oFree       = new \stdClass();
        $oFree->base = (int) 0;
        $oFree->user = (int) 0;

        // --------------------------------------------------------------------------

        //  Check that we have a valid item
        //  @todo make this model a non-CI one when this line is replaced with a fatory call
        $otItem = $this->shop_product_model->getByVariantId($iVariantId);

        /**
         * If for whatever reason we can't find the product, or it isn't physcal return
         * free (no charge)
         */

        if (!$otItem || empty($otItem->type->is_physical)) {
            return $oFree;
        }

        // --------------------------------------------------------------------------

        $oVariant = null;
        foreach ($otItem->variations as $oVariation) {

            if ($oVariation->id = $oVariationariantId) {
                if (!empty($oVariation->ship_collection_only)) {

                    //  Item is collect only, assume no charge for delivery
                    return $oFree;

                } else {

                    $oVariant = $oVariation;
                }
            }
        }

        // --------------------------------------------------------------------------

        /**
         * Have the driver calculate the cost of shipping, this should return a float
         * which is in the base currency. Similar to the calculate() method
         */

        $iCost = $this->oDriver->calculateVariant($oVariant);

        if (!is_integer($iCost) || $iCost < 0) {
            throw new ShippingDriverException(
                'The value returned by the shipping driver must be a positive integer or zero.',
                6
            );
        }

        $oOut       = new \stdClass();
        $oOut->base = $iCost;

        //  Convert the base price to the user's currency
        $oCurrencyModel = Factory::model('Currency', 'nailsapp/module-shop');
        $oOut->user     = $oCurrencyModel->convertBaseToUser($iCost);

        return $oOut;
    }

    // --------------------------------------------------------------------------

    /**
     * Specifies the driver's configurable options
     * @return array
     */
    public function fieldsConfigure()
    {
        return $this->oDriver->fieldsConfigure();
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of additional options for products which can be set by admin
     * @return array
     */
    public function fieldsProduct()
    {
        return $this->oDriver->fieldsProduct();
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of additional options for variants which can be set by admin
     * @return array
     */
    public function fieldsVariant()
    {
        return $this->oDriver->fieldsVariant();
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an object containing the shipping promotions strings, if any
     * promotion is available.
     * @param  stdClass $basket A basket object
     * @return object
     */
    public function getPromotion($basket)
    {
        $oEmptyPromo = new \stdClass();
        $oEmptyPromo->title = '';
        $oEmptyPromo->body = '';
        $oEmptyPromo->applied = false;

        if (method_exists($this->oDriver, 'getPromotion')) {

            $aShippableItems = $this->getShippableItemsFromBasket($basket);
            return $this->oDriver->getPromotion($aShippableItems, $basket);

        } else {

            return $oEmptyPromo;
        }
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

if (!defined('NAILS_ALLOW_EXTENSION_SHOP_SHIPPING_DRIVER_MODEL')) {

    class Shop_shipping_driver_model extends NAILS_Shop_shipping_driver_model
    {
    }
}
