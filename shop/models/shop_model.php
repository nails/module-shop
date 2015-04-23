<?php

/**
 * This model provides basic shop methods and sets up constants etc
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

class NAILS_Shop_model extends NAILS_Model
{
    protected $settings;
    protected $base_currency;

    // --------------------------------------------------------------------------

    /**
     * Sets up shop constants
     * @param array $config An optional configuration array
     */
    public function __construct($config = array())
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        $configSetSession = isset($config['set_session']) ? (bool) $config['set_session'] : true;

        // --------------------------------------------------------------------------

        $base = $this->getBaseCurrency();

        // --------------------------------------------------------------------------

        //  Shop's base currency (i.e what the products are listed in etc)
        if (!defined('SHOP_BASE_CURRENCY_SYMBOL')) {

            define('SHOP_BASE_CURRENCY_SYMBOL', $base->symbol);
        }

        if (!defined('SHOP_BASE_CURRENCY_SYMBOL_POS')) {

            define('SHOP_BASE_CURRENCY_SYMBOL_POS', $base->symbol_position);
        }

        if (!defined('SHOP_BASE_CURRENCY_PRECISION')) {

            define('SHOP_BASE_CURRENCY_PRECISION', $base->decimal_precision);
        }

        if (!defined('SHOP_BASE_CURRENCY_CODE')) {

            define('SHOP_BASE_CURRENCY_CODE', $base->code);
        }

        //  Formatting constants
        if (!defined('SHOP_BASE_CURRENCY_THOUSANDS')) {

            define('SHOP_BASE_CURRENCY_THOUSANDS', $base->thousands_seperator);
        }

        if (!defined('SHOP_BASE_CURRENCY_DECIMALS')) {

            define('SHOP_BASE_CURRENCY_DECIMALS', $base->decimal_symbol);
        }

        //  User's preferred currency
        if ($this->session->userdata('shop_currency')) {

            //  Use the currency defined in the session
            $currencyCode = $this->session->userdata('shop_currency');

        } elseif (activeUser('shop_currency')) {

            //  Use the currency defined in the user object
            $currencyCode = activeUser('shop_currency');

            if (!headers_sent()) {

                $this->session->set_userdata('shop_currency', $currencyCode);
            }

        } else {

            /**
             * Can we determine the user's location and set a currency based on that?
             * If not, fall back to base currency
             */

            $this->load->library('geo_ip/geo_ip');

            $lookup = $this->geo_ip->country();

            if (!empty($lookup->status) && $lookup->status == 200) {

                //  We know the code, does it have a known currency?
                $countryCurrency = $this->shop_currency_model->get_by_country($lookup->country->iso);

                if ($countryCurrency) {

                    $currencyCode = $countryCurrency->code;

                } else {

                    //  Fall back to default
                    $currencyCode = $base->code;
                }

            } else {

                $currencyCode = $base->code;
            }

            //  Save to session
            if (!headers_sent()) {

                $this->session->set_userdata('shop_currency', $currencyCode);
            }
        }

        //  Fetch the user's render currency
        $userCurrency = $this->shop_currency_model->getByCode($currencyCode);

        if (!$userCurrency) {

            //  Bad currency code
            $userCurrency = $base;

            if (!headers_sent()) {

                $this->session->unset_userdata('shop_currency', $currencyCode);
            }

            if ($this->user_model->isLoggedIn()) {

                $this->user_model->update(activeUser('id'), array('shop_currency' => null));
            }
        }

        //  Set the user constants
        if (!defined('SHOP_USER_CURRENCY_SYMBOL')) {

            define('SHOP_USER_CURRENCY_SYMBOL', $userCurrency->symbol);
        }

        if (!defined('SHOP_USER_CURRENCY_SYMBOL_POS')) {

            define('SHOP_USER_CURRENCY_SYMBOL_POS', $userCurrency->symbol_position);
        }

        if (!defined('SHOP_USER_CURRENCY_PRECISION')) {

            define('SHOP_USER_CURRENCY_PRECISION', $userCurrency->decimal_precision);
        }

        if (!defined('SHOP_USER_CURRENCY_CODE')) {

            define('SHOP_USER_CURRENCY_CODE', $userCurrency->code);
        }

        //  Formatting constants
        if (!defined('SHOP_USER_CURRENCY_THOUSANDS')) {

            define('SHOP_USER_CURRENCY_THOUSANDS', $userCurrency->thousands_seperator);
        }

        if (!defined('SHOP_USER_CURRENCY_DECIMALS')) {

            define('SHOP_USER_CURRENCY_DECIMALS', $userCurrency->decimal_symbol);
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Get the shop's base currency
     * @return stdClass
     */
    public function getBaseCurrency()
    {
        $cache = $this->_get_cache('base_currency');

        if ($cache) {

            return $cache;
        }

        // --------------------------------------------------------------------------

        //  Load the currency model
        $this->load->model('shop/shop_currency_model');

        // --------------------------------------------------------------------------

        //  Fetch base currency
        $base = $this->shop_currency_model->getByCode(app_setting('base_currency', 'shop'));

        //  If no base currency is found, default to GBP
        if (!$base) {

            $base = $this->shop_currency_model->getByCode('GBP');

            if (!$base) {

                $subject = 'Could not define base currency';
                $message = 'No base currency was set, so the system fell back to GBP, but could not find that either.';
                showFatalError($subject, $message);

            } else {

                set_app_setting('base_currency', 'shop', 'GBP');
            }
        }

        //  Cache
        $this->_set_cache('base_currency', $base);

        return $base;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the shop's base URL
     * @return string
     */
    public function getShopUrl()
    {
        return app_setting('url', 'shop') ? app_setting('url', 'shop') : 'shop/';
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the shop's name
     * @return string
     */
    public function getShopName()
    {
        return app_setting('name', 'shop') ? app_setting('name', 'shop') : 'Shop';
    }

    // --------------------------------------------------------------------------

    /**
     * Format a price according to it's currency settings
     * @param  string  $price            The price to format
     * @param  boolean $includeSymbol    Whether to include the currency symbol
     * @param  boolean $includeThousands Whether to include the thousands seperator
     * @param  string  $forCurrency      The currency code to format for
     * @param  boolean $decodeSymbol     Whether to decode the symbl to it's HTML entity
     * @return string
     */
    public function formatPrice($price, $includeSymbol = false, $includeThousands = false, $forCurrency = null, $decodeSymbol = false)
    {
        //  Formatting for which currency? If empty, assume user currency
        if (empty($forCurrency)) {

            $code      = SHOP_USER_CURRENCY_CODE;
            $symbol    = SHOP_USER_CURRENCY_SYMBOL;
            $thousands = $includeThousands ? SHOP_USER_CURRENCY_THOUSANDS : '';
            $precision = SHOP_USER_CURRENCY_PRECISION;
            $decimals  = SHOP_USER_CURRENCY_DECIMALS;
            $position  = SHOP_USER_CURRENCY_SYMBOL_POS;

        } else {

            //  Fetch the currency in question - check cache first
            $currency = $this->_get_cache('formatPrice-' . $forCurrency);

            if ($currency) {

                $code      = $currency->code;
                $symbol    = $currency->symbol;
                $thousands = $includeThousands ? $currency->thousands : '';
                $precision = $currency->precision;
                $decimals  = $currency->decimals;
                $position  = $currency->position;

            } else {

                //  Load the currency model, if not already loaded
                if (!$this->load->isModelLoaded('shop_currency_model')) {

                    $this->load->model('shop/shop_currency_model');
                }

                if (is_numeric($forCurrency)) {

                    $currency = $this->shop_currency_model->get_by_id($forCurrency);

                } else {

                    $currency = $this->shop_currency_model->getByCode($forCurrency);
                }

                if ($currency) {

                    $code      = $currency->code;
                    $symbol    = $currency->symbol;
                    $thousands = $includeThousands ? $currency->thousands_seperator : '';
                    $precision = $currency->decimal_precision;
                    $decimals  = $currency->decimal_symbol;
                    $position  = $currency->symbol_position;

                    //  Cache it
                    $cache            = new \stdClass();
                    $cache->code      = $code;
                    $cache->symbol    = $symbol;
                    $cache->thousands = $thousands;
                    $cache->precision = $precision;
                    $cache->decimals  = $decimals;
                    $cache->position  = $position;

                    $this->_set_cache('formatPrice-' . $forCurrency, $cache);

                } else {

                    return false;
                }
            }
        }

        // --------------------------------------------------------------------------

        $value = number_format($price, $precision, $decimals, $thousands);

        if ($includeSymbol) {

            if ($decodeSymbol) {

                //  ENT_HTML5 added in PHP 5.4.0, use that if you can, if not replace certain strings manually
                if (version_compare(phpversion(), '5.4.0', '>=')) {

                    $symbol = html_entity_decode($symbol, ENT_COMPAT | ENT_HTML5, 'UTF-8');

                } else {

                    $symbol = html_entity_decode($symbol, ENT_COMPAT, 'UTF-8');

                    $replace             = array();
                    $replace['&dollar;'] = '$';

                    $symbol = str_replace(array_keys($replace), $replace, $symbol);
                }
            }

            // --------------------------------------------------------------------------

            if ($position == 'BEFORE') {

                $return = $symbol . $value;

            } else {

                $return = $value . $symbol;
            }

            if (!$symbol || $symbol == '&curren;') {

                $return .= ' ' . $code;
            }

            // --------------------------------------------------------------------------

            return $return;

        } else {

            return $value;
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

if (!defined('NAILS_ALLOW_EXTENSION_SHOP_MODEL')) {

    class Shop_model extends NAILS_Shop_model
    {
    }
}
