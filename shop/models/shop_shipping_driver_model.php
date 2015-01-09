<?php

/**
 * OVERLOADING NAILS' MODELS
 *
 * Note the name of this class; done like this to allow apps to extend this class.
 * Read full explanation at the bottom of this file.
 *
 **/

class NAILS_Shop_shipping_driver_model extends NAILS_Model
{
    protected $available;
    protected $shippingDriverLocations;
    protected $driver;
    protected $driverConfig;

    // --------------------------------------------------------------------------

    /**
     * Construct the model.
     */
    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        $this->available = null;

        /**
         * Shipping driver locations
         * The model will search these directories for shipping drivers; to add more directories extend this
         * This must be an array with 2 indexes:
         * `path`   => The absolute path to the directory containing the shipping drivers (required)
         * `url`    => The URL to access the shipping drivers (required)
         * `regex`  => If the directory doesn't only contain shipping drivers then specify a regex to filter by
         */

        if (empty($this->shippingDriverLocations)) {

            $this->shippingDriverLocations = array();
        }

        //  'Official' Shipping Drivers
        $this->shippingDriverLocations[] = array(
            'path'  => NAILS_PATH,
            'url'   => NAILS_URL,
            'regex' => '/^shop-shipping-driver-(.*)$/'
        );

        //  App Shipping Drivers
        $this->shippingDriverLocations[] = array(
            'path' => FCPATH . APPPATH . 'modules/shop/shipping_drivers',
            'url' => site_url(APPPATH . 'modules/shop/shipping_drivers', page_is_secure())
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches all available shipping drivers
     * @param  boolean $refresh Fetchf rom refresh - skip the cache
     * @return array
     */
    public function getAvailable($refresh = false)
    {
        if (!is_null($this->available) && !$refresh) {

            return $this->available;
        }

        //  Reset
        $this->available = array();

        // --------------------------------------------------------------------------

        /**
         * Look for shipping_drivers, where a shipping_driver has the same name, the
         * last one found is the one which is used
         */

        $this->load->helper('directory');

        //  Take a fresh copy
        $shippingDriverLocations = $this->shippingDriverLocations;

        //  Sanitise
        for ($i = 0; $i < count($shippingDriverLocations); $i++) {

            //  Ensure path is present and has a trailing slash
            if (isset($shippingDriverLocations[$i]['path'])) {

                $shippingDriverLocations[$i]['path'] = substr($shippingDriverLocations[$i]['path'], -1, 1) == '/' ? $shippingDriverLocations[$i]['path'] : $shippingDriverLocations[$i]['path'] . '/';

            } else {

                unset($shippingDriverLocations[$i]);
            }

            //  Ensure URL is present and has a trailing slash
            if (isset($shippingDriverLocations[$i]['url'])) {

                $shippingDriverLocations[$i]['url'] = substr($shippingDriverLocations[$i]['url'], -1, 1) == '/' ? $shippingDriverLocations[$i]['url'] : $shippingDriverLocations[$i]['url'] . '/';

            } else {

                unset($shippingDriverLocations[$i]);

            }
        }

        //  Reset array keys, possible that some may have been removed
        $shippingDriverLocations = array_values($shippingDriverLocations);

        foreach ($shippingDriverLocations as $shippingDriver_location) {

            $path = $shippingDriver_location['path'];
            $shippingDrivers = is_dir($path) ? directory_map($path, 1) : array();

            if (is_array($shippingDrivers)) {

                foreach ($shippingDrivers as $shippingDriver) {

                    //  do we need to filter out non shipping_drivers?
                    if (!empty($shippingDriver_location['regex'])) {

                        if (!preg_match($shippingDriver_location['regex'], $shippingDriver)) {

                            log_message('debug', '"' . $shippingDriver . '" is not a shop shipping_driver.');
                            continue;
                        }
                    }

                    // --------------------------------------------------------------------------

                    //  Exists?
                    if (file_exists($path . $shippingDriver . '/config.json')) {

                        $config = @json_decode(file_get_contents($path . $shippingDriver . '/config.json'));

                    } else {

                        $msg = 'Could not find configuration file for shipping_driver "' . $path . $shippingDriver. '".';
                        log_message('error', $msg);
                        continue;
                    }

                    //  Valid?
                    if (empty($config)) {

                        $msg = 'Configuration file for shipping_driver "' . $path . $shippingDriver. '" contains invalid JSON.';
                        log_message('error', $msg);
                        continue;

                    } elseif (!is_object($config)) {

                        $msg = 'Configuration file for shipping_driver "' . $path . $shippingDriver. '" contains invalid data.';
                        log_message('error', $msg);
                        continue;
                    }

                    // --------------------------------------------------------------------------

                    //  All good!

                    //  Set the slug
                    $config->slug = strtolower($shippingDriver);

                    //  Set the path
                    $config->path = $path . $shippingDriver . '/';

                    //  Set the URL
                    $config->url = $shippingDriver_location['url'] . $shippingDriver . '/';

                    $this->available[$shippingDriver] = $config;
                }
            }
        }

        $this->available = array_values($this->available);

        return $this->available;
    }

    // --------------------------------------------------------------------------

    /**
     * Gets a single driver
     * @param  string  $slug    the driver's slug
     * @param  boolean $refresh Skip the cache
     * @return stdClass
     */
    public function get($slug, $refresh = false)
    {
        $shippingDrivers = $this->getAvailable($refresh);

        foreach ($shippingDrivers as $shippingDriver) {

            if ($shippingDriver->slug == $slug) {

                return $shippingDriver;
            }
        }

        $this->_set_error('"' . $slug . '" was not found.');
        return false;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the enabled driver.
     * @return mixed stdClass on success, false on failure
     */
    public function getEnabled()
    {
        $slug = app_setting('enabled_shipping_driver', 'shop');

        if (!$slug) {

            return false;
        }

        return $this->get($slug);
    }

    // --------------------------------------------------------------------------

    /**
     * Loads a driver
     * @param  string $slug The driver to load
     * @return boolean
     */
    public function load($slug = null)
    {
        if (is_null($slug)) {

            $slug = app_setting('enabled_shipping_driver', 'shop');

            if (!$slug) {

                return false;
            }
        }

        $driver = $this->get($slug);

        if (!$driver) {

            return false;
        }

        $this->unload();

        require_once NAILS_PATH . 'module-shop/shop/interfaces/shippingDriver.php';
        require_once $driver->path . 'driver.php';

        $class = ucfirst(str_replace('-', '_', $driver->slug));
        $this->driver = new $class();

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
     * Takes a basket object and calculates the cost of shipping
     * @param  stdClass $basket A basket object
     * @return stdClass
     */
    public function calculate($basket)
    {
        $free       = new stdClass();
        $free->base = (float) 0;
        $free->user = (float) 0;

        // --------------------------------------------------------------------------

        if (!$this->isDriverLoaded()) {

            //  No driver loaded, detect enabled driver and attempt to load
            $enabledDriver = app_setting('enabled_shipping_driver', 'shop');

            if (empty($enabledDriver) || !$this->load($enabledDriver)) {

                //  Free shipping, I guess?
                return $free;
            }
        }

        // --------------------------------------------------------------------------

        if (!is_callable(array($this->driver, 'calculate'))) {

            //  Driver isn't configured properly, free shipping.
            return $free;
        }

        // --------------------------------------------------------------------------

        /**
         * Have the driver calculate the cost of shipping, this should return a float
         * which is in the base currency. It is passed an array of all shippable items
         * (i.e., items who's type markes them as `is_physical` and is not set to
         * `collect only`), as well as a reference to the basket, shold the driver need
         * to know anything else about the order.
         */

        $shippableItems = array();

        foreach ($basket->items as $item) {

            if (!empty($item->product->type->is_physical) && empty($item->variant->shipping->collection_only)) {

                $shippableItems[] = $item;
            }
        }

        $cost = $this->driver->calculate($shippableItems, $basket);

        if (is_int($cost) || is_numeric($cost)) {

            $cost = (float) $cost;

        } elseif (!is_float($cost)) {

            $cost = 0;
        }

        $out       = new stdClass();
        $out->base = $cost;

        //  Convert the base price to the user's currency
        $this->load->model('shop/shop_currency_model');
        $out->user = $this->shop_currency_model->convert_base_to_user($cost);

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of possible shipping methods which the user can select from.
     * These might include priority shipping or recorded delivery for example.
     * @return array
     */
    public function optionsBasket()
    {
        if (!$this->isDriverLoaded()) {

            if (!$this->load()) {

                return array();
            }
        }

        // --------------------------------------------------------------------------

        if (!is_callable(array($this->driver, 'optionsBasket'))) {

            //  Driver isn't configured properly
            return array();
        }

        // --------------------------------------------------------------------------

        return $this->driver->optionsBasket();
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of additional options for variants which can be set by admin
     * @return array
     */
    public function optionsVariant()
    {
        if (!$this->isDriverLoaded()) {

            if (!$this->load()) {

                return array();
            }
        }

        // --------------------------------------------------------------------------

        if (!is_callable(array($this->driver, 'optionsVariant'))) {

            //  Driver isn't configured properly
            return array();
        }

        // --------------------------------------------------------------------------

        return $this->driver->optionsVariant();
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of additional options for products  which can be set by admin
     * @return array
     */
    public function optionsProduct()
    {
        if (!$this->isDriverLoaded()) {

            if (!$this->load()) {

                return array();
            }
        }

        // --------------------------------------------------------------------------

        if (!is_callable(array($this->driver, 'optionsProduct'))) {

            //  Driver isn't configured properly
            return array();
        }

        // --------------------------------------------------------------------------

        return $this->driver->optionsProduct();
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

        // --------------------------------------------------------------------------

        //  Call the config method
        if (!is_callable(array($this->driverconfig, 'configure'))) {

            //  Driver isn't configured properly
            return false;
        }

        // --------------------------------------------------------------------------

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
