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
    protected $driver;
    protected $driverConfig;

    // --------------------------------------------------------------------------

    /**
     * Construct the model.
     */
    public function __construct()
    {
        parent::__construct();
        $this->aAvailable = _NAILS_GET_DRIVERS('nailsapp/module-shop', 'shipping');
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
            if ($oDriver->name == $sSlug) {
                return $oDriver;
            }
        }

        $this->setError('"' . $sSlug . '" was not found.');
        return false;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the enabled driver.
     * @return mixed stdClass on success, false on failure
     */
    public function getEnabled()
    {
        $sSlug = appSetting('enabled_shipping_driver', 'shop');

        if (!$sSlug) {
            return false;
        }

        return $this->get($sSlug);
    }

    // --------------------------------------------------------------------------

    /**
     * Loads a driver
     * @param  string $slug The driver to load
     * @return boolean
     */
    public function load($sSlug = null)
    {
        if (is_null($sSlug)) {

            $sSlug = appSetting('enabled_shipping_driver', 'shop');

            if (!$sSlug) {
                return false;
            }
        }

        $oDriver = $this->get($sSlug);

        if (!$oDriver) {

            return false;
        }

        $this->unload();

        dumpanddie($oDriver);


        require_once $oDriver->path . '/driver.php';



        $this->oDriver = new $sClassName();

        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * Unloads a driver
     * @return void
     */
    public function unload()
    {
        unset($this->driver);
        $this->driver = null;
    }

    // --------------------------------------------------------------------------

    /**
     * Determines whether a driver is loaded or not
     * @return boolean
     */
    protected function isDriverLoaded()
    {
        return !is_null($this->driver);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of the shippable items from the basket object
     * @param  object $basket The basket object
     * @return array
     */
    private function getShippableItemsFromBasket($basket)
    {
        $shippableItems = array();

        foreach ($basket->items as $item) {

            if (!empty($item->product->type->is_physical) && empty($item->variant->shipping->collection_only)) {

                $shippableItems[] = $item;
            }
        }

        return $shippableItems;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of the available shipping options
     * @return array
     */
    public function options($oBasket)
    {
        if (!$this->isDriverLoaded()) {

            if (!$this->load()) {

                return array();
            }
        }

        /**
         * Ask the driver what the available options are, pass it the shippable items so it can
         * amend it's responses as nessecary and work out the cost
         */

        $aShippableItems = $this->getShippableItemsFromBasket($oBasket);
        $aOptions = $this->driver->options($aShippableItems, $oBasket);

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
                throw new ShippingDriverException('"' . $aOption['slug'] . '" is not a unique shipping option slug', 1);
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

                $aOption['cost'] = (float) $aOption['cost'];

            } elseif (!is_float($aOption['cost'])) {

                $aOption['cost'] = 0;
            }

            //  Convert the base price to the user's currency
            $oCurrencyModel = Factory::model('Currency', 'nailsapp/module-shop');

            $oTemp                 = new \stdClass();
            $oTemp->slug           = $aOption['slug'];
            $oTemp->label          = $aOption['label'];
            $oTemp->cost           = $oCurrencyModel->convertBaseToUser($aOption['cost']);
            $oTemp->cost_formatted = $oCurrencyModel->formatUser($aOption['cost']);
            $oTemp->default        = (bool) $aOption['default'];

            $aOut[] = $oTemp;
        }

        return $aOut;
    }

    // --------------------------------------------------------------------------

    /**
     * Takes a basket object and calculates the cost of shipping
     * @param  stdClass $basket A basket object
     * @return stdClass
     */
    public function calculate($basket)
    {
        $free       = new \stdClass();
        $free->base = (float) 0;
        $free->user = (float) 0;

        // --------------------------------------------------------------------------

        //  If the shipping type is COLLECTION (a special type) then shipping is FREE
        if ($basket->shipping->option == 'COLLECTION') {
            return $free;
        }

        // --------------------------------------------------------------------------

        if (!$this->isDriverLoaded()) {

            //  No driver loaded, detect enabled driver and attempt to load
            $enabledDriver = appSetting('enabled_shipping_driver', 'shop');

            if (empty($enabledDriver) || !$this->load($enabledDriver)) {

                //  Free shipping, I guess?
                return $free;
            }
        }

        // --------------------------------------------------------------------------

        /**
         * Have the driver calculate the cost of shipping, this should return a float
         * which is in the base currency. It is passed an array of all shippable items
         * (i.e., items who's type markes them as `is_physical` and is not set to
         * `collect only`), as well as a reference to the basket, shold the driver need
         * to know anything else about the order.
         */

        $shippableItems = $this->getShippableItemsFromBasket($basket);
        $cost = $this->driver->calculate($shippableItems, $basket->shipping->option, $basket);

        if (is_int($cost) || is_numeric($cost)) {

            $cost = (float) $cost;

        } elseif (!is_float($cost)) {

            $cost = 0;
        }

        $out       = new \stdClass();
        $out->base = $cost;

        //  Convert the base price to the user's currency
        $oCurrencyModel = Factory::model('Currency', 'nailsapp/module-shop');
        $out->user = $oCurrencyModel->convertBaseToUser($cost);

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Takes a product variant ID and works out what the shipping would be on it
     * @param  stdClass $variantId ID of the variant in question
     * @return stdClass
     */
    public function calculateVariant($variantId)
    {
        $free       = new \stdClass();
        $free->base = (float) 0;
        $free->user = (float) 0;

        // --------------------------------------------------------------------------

        //  Check that we have a valid item
        $item = $this->shop_product_model->getByVariantId($variantId);

        //  If for whatever reason we can't find the product, return free (no charge)
        if (!$item) {

            return $free;
        }

        // --------------------------------------------------------------------------

        if (!$this->isDriverLoaded()) {

            //  No driver loaded, detect enabled driver and attempt to load
            $enabledDriver = appSetting('enabled_shipping_driver', 'shop');

            if (empty($enabledDriver) || !$this->load($enabledDriver)) {

                //  No driver loaded, assume no charge for delivery
                return $free;
            }
        }

        // --------------------------------------------------------------------------

        if (empty($item->type->is_physical)) {

            //  Item is not physical, assume no charge for delivery
            return $free;
        }

        // --------------------------------------------------------------------------

        $variant = null;
        foreach ($item->variations as $v) {

            if ($v->id = $variantId) {
                if (!empty($v->ship_collection_only)) {

                    //  Item is collect only, assume no charge for delivery
                    return $free;
                } else {

                    $variant = $v;
                }
            }
        }

        // --------------------------------------------------------------------------

        /**
         * Have the driver calculate the cost of shipping, this should return a float
         * which is in the base currency. Similar to the calculate() method
         */

        $cost = $this->driver->calculateVariant($variant);

        if (is_int($cost) || is_numeric($cost)) {

            $cost = (float) $cost;

        } elseif (!is_float($cost)) {

            $cost = 0;
        }

        $out       = new \stdClass();
        $out->base = $cost;

        //  Convert the base price to the user's currency
        $oCurrencyModel = Factory::model('Currency', 'nailsapp/module-shop');
        $out->user = $oCurrencyModel->convertBaseToUser($cost);

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of possible shipping methods which the user can select from.
     * These might include priority shipping or recorded delivery for example.
     * @return array
     */
    public function fieldsBasket()
    {
        if (!$this->isDriverLoaded()) {

            if (!$this->load()) {

                return array();
            }
        }

        return $this->driver->fieldsBasket();
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of additional options for variants which can be set by admin
     * @return array
     */
    public function fieldsVariant()
    {
        if (!$this->isDriverLoaded()) {

            if (!$this->load()) {

                return array();
            }
        }

        return $this->driver->fieldsVariant();
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of additional options for products  which can be set by admin
     * @return array
     */
    public function fieldsProduct()
    {
        if (!$this->isDriverLoaded()) {

            if (!$this->load()) {

                return array();
            }
        }

        return $this->driver->fieldsProduct();
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

        if (!$this->isDriverLoaded()) {

            if (!$this->load()) {

                return $oEmptyPromo;
            }
        }

        if (method_exists($this->driver, 'getPromotion')) {

            $shippableItems = $this->getShippableItemsFromBasket($basket);
            return $this->driver->getPromotion($shippableItems, $basket);

        } else {

            return $oEmptyPromo;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Handles the configuration of the driver in admin
     * @return array
     */
    public function configure($slug)
    {
        //  Fetch the driver in question
        $driver = $this->get($slug);

        if (!$driver) {

            return false;
        }

        // --------------------------------------------------------------------------

        //  Unload any previously loaded driver for configuration
        unset($this->driverconfig);
        $this->driverconfig = null;

        // --------------------------------------------------------------------------

        //  Load the driver
        require_once NAILS_PATH . 'module-shop/shop/interfaces/shippingDriver.php';
        require_once $driver->path . 'driver.php';

        $class = ucfirst(strtolower(str_replace('-', '_', $driver->slug)));
        $this->driverconfig = new $class();

        //  Spit back whatever the driver desires
        return $this->driverconfig->configure();
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
