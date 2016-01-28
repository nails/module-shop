<?php

/**
 * This model manages Shop Currencies
 *
 * @package  Nails
 * @subpackage  module-shop
 * @category    Model
 * @author    Nails Dev Team
 * @link
 */

namespace Nails\Shop\Model;

use Nails\Factory;

class Currency
{
    use \Nails\Common\Traits\ErrorHandling;

    // --------------------------------------------------------------------------

    protected $oerUrl;
    protected $rates;
    protected $aCurrency;
    protected $oDb;

    // --------------------------------------------------------------------------

    /**
     * Construct the model, define defaults and load dependencies.
     */
    public function __construct()
    {
        //  Load required config file
        $oConfig = Factory::service('Config');
        $oConfig->load('shop/currency');
        $this->aCurrency = $oConfig->item('currency');

        // --------------------------------------------------------------------------

        $this->oDb = Factory::service('Database');

        // --------------------------------------------------------------------------

        //  Defaults
        $this->oerUrl = 'http://openexchangerates.org/api/latest.json';
        $this->rates  = null;
    }

    // --------------------------------------------------------------------------

    /**
     * Get all defined currencies.
     * @return array
     */
    public function getAll()
    {
        return $this->aCurrency;
    }

    // --------------------------------------------------------------------------

    /**
     * Get all defined currencies as a flat array; the index is the currency's code,
     * the value is the currency's label.
     * @return array
     */
    public function getAllFlat()
    {
        $out      = array();
        $currency = $this->getAll();

        foreach ($currency as $c) {

            $out[$c->code] = $c->label;
        }

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Gets all currencies supported by the shop.
     * @return array
     */
    public function getAllSupported()
    {
        $currencies = $this->getAll();
        $additional = appSetting('additional_currencies', 'shop');
        $base       = appSetting('base_currency', 'shop');
        $supported  = array();

        if (isset($currencies[$base])) {

            $supported[] = $currencies[$base];
        }

        if (is_array($additional)) {

            foreach ($additional as $additional) {

                if (isset($currencies[$additional])) {

                    $supported[] = $currencies[$additional];
                }
            }
        }

        return $supported;
    }

    // --------------------------------------------------------------------------

    /**
     * Gets all supported currencies as a flat array; the index is the currency's
     * code, the value is the currency's label.
     * @return array
     */
    public function getAllSupportedFlat()
    {
        $out      = array();
        $currency = $this->getAllSupported();

        foreach ($currency as $c) {

            $out[$c->code] = $c->label;
        }

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Gets an individual currency by it's 3 letter code.
     * @param  string $code The code to return
     * @return mixed        stdClass on success, false on failure
     */
    public function getByCode($code)
    {
        $currency = $this->getAll();

        return !empty($currency[$code]) ? $currency[$code] : false;
    }

    // --------------------------------------------------------------------------

    /**
     * Syncs exchange rates to the Open Exchange Rates service.
     * @param  boolean $muteLog Whether or not to write errors to the log
     * @return boolean
     */
    public function sync($muteLog = true)
    {
        $oerAppId             = appSetting('openexchangerates_app_id', 'shop');
        $oerEtag              = appSetting('openexchangerates_etag', 'shop');
        $oerLastModified      = appSetting('openexchangerates_last_modified', 'shop');
        $additionalCurrencies = appSetting('additional_currencies', 'shop');
        $oLogger              = Factory::service('Logger');

        if (empty($additionalCurrencies)) {

            $message = 'No additional currencies are supported, aborting sync.';
            $this->setError($message);

            if (empty($muteLog)) {

                $oLogger->line('... ' . $message);
            }

            return false;
        }

        if ($oerAppId) {

            //  Make sure we know what the base currency is
            if (!defined('SHOP_BASE_CURRENCY_CODE')) {
                get_instance()->load->model('shop/shop_model');
            }

            if (empty($muteLog)) {

                $oLogger->line('... Base Currency is ' . SHOP_BASE_CURRENCY_CODE);
            }

            /**
             * Set up the HTTP request
             * First attempt to get the rates using the Shop's base currency
             * (only available to paid subscribers, but probably more accurate)
             */

            $oHttpClient = Factory::factory('HttpClient');

            $aParams = array(
                'query' => array(
                    'app_id' => $oerAppId,
                    'base' => SHOP_BASE_CURRENCY_CODE
                ),
                'headers' => array()
            );

            if (!empty($oerEtag) && !empty($oerLastModified)) {

                $aParams['headers']['If-None-Match']     = $oerEtag;
                $aParams['headers']['If-Modified-Since'] = $oerLastModified;
            }

            try {

                $oResponse = $oHttpClient->request('GET', $this->oerUrl, $aParams);

                if ($oResponse->getStatusCode() === 304) {

                    //  304 Not Modified, abort sync.
                    if (empty($muteLog)) {

                        $oLogger->line('... OER reported 304 Not Modified, aborting sync');
                    }

                    return true;
                }

            } catch (Exception $e) {

                if ($e->getCode() ===  401) {

                    /**
                     * Probably due to invalid `app_id`
                     */

                    if (empty($muteLog)) {

                        $oLogger->line('... OER reported 401 unauthorised, aborting sync.');
                        $oLogger->line('A valid `app_id` must be present, double check it is correct?');
                    }

                    showFatalError(
                        'OER Reported an error',
                        $e->getCode()
                    );

                } else if ($e->getCode() === 403) {

                    /**
                     * Probably failed due to requesting a non-USD base
                     * Try again with but using USD base this time.
                     */

                    $aParams['query']['base'] = 'USD';

                    try {

                        $oResponse = $oHttpClient->request('GET', $this->oerUrl, $aParams);

                        if ($oResponse->getStatusCode() === 304) {

                            //  304 Not Modified, abort sync.
                            if (empty($muteLog)) {

                                $oLogger->line('... OER reported 304 Not Modified, aborting sync');
                            }

                            return true;
                        }

                    } catch (Exception $e) {

                        if (empty($muteLog)) {

                            $oLogger->line('... OER reported ' . $e->getMessage());
                            $oLogger->line('This is the second attempt, aborting sync');
                        }

                        showFatalError(
                            'OER Reported an error',
                            $e->getCode()
                        );
                    }
                }
            }

            /**
             * Ok, now we know the rates we need to work out what the base_exchange rate is.
             * If the store's base rate is the same as the API's base rate then we're golden,
             * if it's not then we'll need to do some calculations.
             */

            //  Headers, look for the E-Tag and last modified
            if ($oResponse->hasHeader('ETag')) {

                $aHeaders = $oResponse->getHeader('ETag');
                setAppSetting('openexchangerates_etag', 'shop', $aHeaders[0]);
            }

            if ($oResponse->hasHeader('Last-Modified')) {

                $aHeaders = $oResponse->getHeader('Last-Modified');
                setAppSetting('openexchangerates_last_modified', 'shop', $aHeaders[0]);
            }

            $toSave = array();
            $oBody  = @json_decode($oResponse->getBody());
            $oDate  = Factory::factory('DateTime');

            if (empty($oBody)) {

                $oLogger->line('Failed to parse response body.');
                return false;
            }

            if (SHOP_BASE_CURRENCY_CODE == $oBody->base) {

                foreach ($oBody->rates as $toCurrency => $rate) {

                    if (array_search($toCurrency, $additionalCurrencies) !== false) {

                        if (empty($muteLog)) {

                            $oLogger->line('... ' . $toCurrency . ' > ' . $rate);
                        }

                        $toSave[] = array(
                            'from'     => $oBody->base,
                            'to'       => $toCurrency,
                            'rate'     => $rate,
                            'modified' => $oDate->format('Y-m-d H:i:s')
                        );
                    }
                }

            } else {

                if (empty($muteLog)) {

                    $oLogger->line('... API base is ' . $oBody->base . '; calculating differences...');
                }

                $base = 1;
                foreach ($oBody->rates as $code => $rate) {

                    if ($code == SHOP_BASE_CURRENCY_CODE) {

                        $base = $rate;
                        break;
                    }
                }

                foreach ($oBody->rates as $toCurrency => $rate) {

                    if (array_search($toCurrency, $additionalCurrencies) !== false) {

                        //  We calculate the new exchange rate as so: $rate / $base
                        $newRate  = $rate / $base;
                        $toSave[] = array(
                            'from'     => SHOP_BASE_CURRENCY_CODE,
                            'to'       => $toCurrency,
                            'rate'     => $newRate,
                            'modified' => $oDate->format('Y-m-d H:i:s')
                        );

                        if (empty($muteLog)) {

                            $oLogger->line('... Calculating and saving new exchange rate for ' . SHOP_BASE_CURRENCY_CODE . ' > ' . $toCurrency . ' (' . $newRate . ')');
                        }
                    }
                }
            }

            // --------------------------------------------------------------------------

            /**
             * Ok, we've done all the BASE -> CURRENCY conversions, now how about we work
             * out the reverse?
             */
            $toSaveReverse = array();

            //  Easy one first, base to base, base, bass, drop da bass. BASS.
            $toSaveReverse[] = array(
                'from'     => SHOP_BASE_CURRENCY_CODE,
                'to'       => SHOP_BASE_CURRENCY_CODE,
                'rate'     => 1,
                'modified' => $oDate->format('Y-m-d H:i:s')
            );

            foreach ($toSave as $old) {

                $toSaveReverse[] = array(
                    'from'     => $old['to'],
                    'to'       => SHOP_BASE_CURRENCY_CODE,
                    'rate'     => 1 / $old['rate'],
                    'modified' => $oDate->format('Y-m-d H:i:s')
                );

            }

            $toSave = array_merge($toSave, $toSaveReverse);

            // --------------------------------------------------------------------------

            if ($this->oDb->truncate(NAILS_DB_PREFIX . 'shop_currency_exchange')) {

                if (!empty($toSave)) {

                    if ($this->oDb->insert_batch(NAILS_DB_PREFIX . 'shop_currency_exchange', $toSave)) {

                        return true;

                    } else {

                        $message = 'Failed to insert new currency data.';
                        $this->setError($message);

                        if (empty($muteLog)) {

                            $oLogger->line('... ' . $message);
                        }

                        return false;
                    }

                } else {

                    return true;
                }

            } else {

                $message = 'Failed to truncate currency table.';
                $this->setError($message);

                if (empty($muteLog)) {

                    $oLogger->line('... ' . $message);
                }

                return false;
            }

        } else {

            $message = '`openexchangerates_app_id` setting is not set. Sync aborted.';
            $this->setError($message);

            if (empty($muteLog)) {

                $oLogger->line('... ' . $message);
            }

            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Converts a value between currencies.
     * @param  mixed  $value The value to convert
     * @param  string $from  The currency to convert from
     * @param  string $to    The currency to convert too
     * @return mixed         Float on success, false on failure
     */
    public function convert($value, $from, $to)
    {
        /**
         * If we're "converting" between the same currency then we don't need to
         * look up rates
         */
        if ($from === $to) {

            return $value;
        }

        // --------------------------------------------------------------------------

        $currencyFrom = $this->getByCode($from);

        if (!$currencyFrom) {

            $this->setError('Invalid `from` currency code.');
            return false;
        }

        $currencyTo = $this->getByCode($to);

        if (!$currencyTo) {

            $this->setError('Invalid `to` currency code.');
            return false;
        }

        // --------------------------------------------------------------------------

        if (is_null($this->rates)) {

            $this->rates = array();
            $rates       = $this->oDb->get(NAILS_DB_PREFIX . 'shop_currency_exchange')->result();

            foreach ($rates as $rate) {

                $this->rates[$rate->from . $rate->to] = $rate->rate;
            }
        }

        if (isset($this->rates[$from . $to])) {

            if ($currencyFrom->decimal_precision === $currencyTo->decimal_precision) {

                $result = $value * $this->rates[$from . $to];
                $result = round($result, 0, PHP_ROUND_HALF_UP);

            } else {

                $result = round($value * $this->rates[$from . $to], 0, PHP_ROUND_HALF_UP);
                $result = $result / pow(10, $currencyFrom->decimal_precision);
                $result = round($result, $currencyTo->decimal_precision, PHP_ROUND_HALF_UP);
            }

            return $result;

        } else {

            $this->setError('No exchange rate available for those currencies; does the system need to sync?');
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Converts a value from the base currency to the user's currency.
     * @param  mixed $value The value to convert
     * @return mixed        Float on success, false on failure
     */
    public function convertBaseToUser($value)
    {
        return $this->convert($value, SHOP_BASE_CURRENCY_CODE, SHOP_USER_CURRENCY_CODE);
    }

    // --------------------------------------------------------------------------

    /**
     * Converts a value from the user's currency to the base currency.
     * @param  mixed $value The value to convert
     * @return mixed        Float on success, false on failure
     */
    public function convertUserToBase($value)
    {
        return $this->convert($value, SHOP_USER_CURRENCY_CODE, SHOP_BASE_CURRENCY_CODE);
    }

    // --------------------------------------------------------------------------

    /**
     * Formats a value using the settings for a given currency.
     * @param  integer $value     The value to format, as an integer
     * @param  string  $code      The currency to format as
     * @param  boolean $incSymbol Whether or not to include the currency's symbol
     * @return mixed              String on success, false on failure
     */
    public function format($value, $code, $incSymbol = true)
    {
        $currency = $this->getByCode($code);

        if (!$currency) {

            $this->setError('Invalid currency code.');
            return false;
        }

        /**
         * The input comes in as an integer, convert into a decimal with the
         * correct number of decimal places.
         */

        $value = $this->intToFloat($value, $code);
        $value = number_format($value, $currency->decimal_precision, $currency->decimal_symbol, $currency->thousands_seperator);

        if ($incSymbol) {

            if ($currency->symbol_position == 'BEFORE') {

                $value = $currency->symbol . $value;

            } else {

                $value = $value . $currency->symbol;
            }
        }

        return $value;
    }

    // --------------------------------------------------------------------------

    /**
     * Formats a value using the settings for the base currency.
     * @param  mixed   $value     The value to format, string, int or float
     * @param  boolean $incSymbol Whether or not to include the currency's symbol
     * @return mixed              String on success, false on failure
     */
    public function formatBase($value, $incSymbol = true)
    {
        return $this->format($value, SHOP_BASE_CURRENCY_CODE, $incSymbol);
    }

    // --------------------------------------------------------------------------

    /**
     * Formats a value using the settings for the user's currency.
     * @param  mixed   $value     The value to format, string, int or float
     * @param  boolean $incSymbol Whether or not to include the currency's symbol
     * @return mixed              String on success, false on failure
     */
    public function formatUser($value, $incSymbol = true)
    {
        return $this->format($value, SHOP_USER_CURRENCY_CODE, $incSymbol);
    }

    // --------------------------------------------------------------------------

    /**
     * Converts an integer to a float with the correct number of decimal points
     * as the currency requires
     * @param  integer $value The integer to convert
     * @param  string  $code  The curreny code to convert for
     * @return float
     */
    public function intToFloat($value, $code)
    {
        $currency = $this->getByCode($code);

        if (!$currency) {

            $this->setError('Invalid currency code.');
            return false;
        }

        $result = $value / pow(10, $currency->decimal_precision);

        return (float) $result;
    }

    // --------------------------------------------------------------------------

    /**
     * Converts a float to an integer
     * @param  integer $value The integer to convert
     * @param  string  $code  The curreny code to convert for
     * @return integer
     */
    public function floatToInt($value, $code)
    {
        $currency = $this->getByCode($code);

        if (!$currency) {

            $this->setError('Invalid currency code.');
            return false;
        }

        $result = $value * pow(10, $currency->decimal_precision);

        /**
         * Due to the nature of floating point numbers (best explained here
         * http://stackoverflow.com/a/4934594/789224) simply casting as an integer
         * can cause some odd rounding behaviour (although eprfectly rational). If we
         * cast as a string, then cast as an integer we can be sure that the value is
         * correct. Others said to use round() but that gives me the fear.
         */

        $result = (string) $result;

        return (int) $result;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the exchange rate between currencies
     * @param  string $from The currency to convert from
     * @param  string $to   The currency to convert to
     * @return float
     */
    public function getExchangeRate($from, $to)
    {
        $this->oDb->select('rate');
        $this->oDb->where('from', $from);
        $this->oDb->where('to', $to);

        $rate = $this->oDb->get(NAILS_DB_PREFIX . 'shop_currency_exchange')->row();

        if (!$rate) {

            return null;
        }

        return (float) $rate->rate;
    }
}
