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

use Nails\Factory;

class Shop_model
{
    use \Nails\Common\Traits\Caching;

    // --------------------------------------------------------------------------

    protected $oUser;
    protected $oUserMeta;
    protected $oCurrency;
    protected $settings;
    protected $base_currency;

    // --------------------------------------------------------------------------

    /**
     * Sets up shop constants
     * @param array $config An optional configuration array
     */
    public function __construct($config = array())
    {
        $this->oUser     = Factory::model('User', 'nailsapp/module-auth');
        $this->oUserMeta = Factory::model('UserMeta', 'nailsapp/module-auth');
        $this->oCurrency = Factory::model('Currency', 'nailsapp/module-shop');

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
        if (get_instance()->session->userdata('shop_currency')) {

            //  Use the currency defined in the session
            $currencyCode = get_instance()->session->userdata('shop_currency');

        } else {

            /**
             * First we'll look at the user's meta, see if we already know what the chosen currency is.
             * Failing that try to determine the user's location and set a currency based on that?
             * If not, fall back to base currency
             */

            $oUserMeta = $this->oUserMeta->get(
                NAILS_DB_PREFIX . 'user_meta_shop',
                activeUser('id'),
                array(
                    'currency'
                )
            );

            if (!empty($oUserMeta->currency)) {

                $currencyCode = $oUserMeta->currency;

            } else {

                $oGeoIp = Factory::service('GeoIp', 'nailsapp/module-geo-ip');
                $lookup = $oGeoIp->country();

                if (!empty($lookup->status) && $lookup->status == 200) {

                    //  We know the code, does it have a known currency?
                    $countryCurrency = $this->oCurrency->getByCountry($lookup->country->iso);

                    if ($countryCurrency) {

                        $currencyCode = $countryCurrency->code;

                    } else {

                        //  Fall back to default
                        $currencyCode = $base->code;
                    }

                } else {

                    $currencyCode = $base->code;
                }
            }

            //  Save to session
            if (!headers_sent()) {

                get_instance()->session->set_userdata('shop_currency', $currencyCode);
            }
        }

        //  Fetch the user's render currency
        $userCurrency = $this->oCurrency->getByCode($currencyCode);

        if (!$userCurrency) {

            //  Bad currency code
            $userCurrency = $base;

            if (!headers_sent()) {

                get_instance()->session->unset_userdata('shop_currency', $currencyCode);
            }

            if (isLoggedIn()) {

                $this->oUserMeta->update(
                    NAILS_DB_PREFIX . 'user_meta_shop',
                    activeUser('id'),
                    array(
                        'currency' => null
                    )
                );
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
        $cache = $this->getCache('base_currency');

        if ($cache) {

            return $cache;
        }

        // --------------------------------------------------------------------------

        //  Fetch base currency
        $base = $this->oCurrency->getByCode(appSetting('base_currency', 'nailsapp/module-shop'));

        //  If no base currency is found, default to GBP
        if (!$base) {

            $base = $this->oCurrency->getByCode('GBP');

            if (!$base) {

                $subject = 'Could not define base currency';
                $message = 'No base currency was set, so the system fell back to GBP, but could not find that either.';
                showFatalError($subject, $message);

            } else {

                setAppSetting('base_currency', 'nailsapp/module-shop', 'GBP');
            }
        }

        //  Cache
        $this->setCache('base_currency', $base);

        return $base;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the shop's base URL
     * @return string
     */
    public function getShopUrl()
    {
        return appSetting('url', 'nailsapp/module-shop') ? appSetting('url', 'nailsapp/module-shop') : 'shop/';
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the shop's name
     * @return string
     */
    public function getShopName()
    {
        return appSetting('name', 'nailsapp/module-shop') ? appSetting('name', 'nailsapp/module-shop') : 'Shop';
    }
}
