<?php

/**
 * This model manages the Shop feed drivers
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Shop\Model;

use Nails\Shop\Exception\FeedException;

class Feed
{
    use \Nails\Common\Traits\ErrorHandling;

    // --------------------------------------------------------------------------

    protected $aDrivers  = array();
    protected $aEnabled  = array();
    protected $Instances = array();

    // --------------------------------------------------------------------------

    /**
     * Construct the model.
     */
    public function __construct()
    {
        //  All available drivers
        $this->aDrivers = _NAILS_GET_DRIVERS('nailsapp/module-shop', 'feed') ?: array();

        //  Enabled drivers
        $aEnabled = appSetting('enabled_feed_drivers', 'shop') ?: array();

        foreach ($this->aDrivers as $oDriver) {
            if (in_array($oDriver->slug, $aEnabled)) {
                $this->aEnabled[] = $oDriver;
            }
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches all available drivers
     * @return array
     */
    public function getAll()
    {
        return $this->aDrivers;
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches all enabled drivers
     * @return array
     */
    public function getEnabled()
    {
        return $this->aEnabled;
    }

    // --------------------------------------------------------------------------

    public function getInstance($sSlug)
    {
        if (isset($this->aInstances[$sSlug])) {

            return $this->aInstances[$sSlug];

        } else {

            foreach ($this->aEnabled as $oDriver) {
                if ($sSlug == $oDriver->slug) {

                    $this->aInstances[$sSlug] = _NAILS_GET_DRIVER_INSTANCE($oDriver);

                    //  Apply driver configurations
                    $aSettings = array(
                        'driver_slug' => $oDriver->slug
                    );

                    if (!empty($oDriver->data->settings)) {
                        $aSettings = array_merge(
                            $aSettings,
                            $this->extractDriverSettings(
                                $oDriver->data->settings,
                                $oDriver->slug
                            )
                        );
                    }

                   $this->aInstances[$sSlug]->setConfig($aSettings);

                    return $this->aInstances[$sSlug];
                }
            }

        }

        return null;
    }

    // --------------------------------------------------------------------------

    /**
     * Recursively gets all the settings from the settings array
     * @param  array  $aSettings The array of fieldsets and/or settings
     * @param  string $sSlug     The driver's slug
     * @return array
     */
    protected function extractDriverSettings($aSettings, $sSlug)
    {
        $aOut = array();

        foreach ($aSettings as $oSetting) {

            //  If the object contains a `fields` property then consider this a fieldset and inception
            if (isset($oSetting->fields)) {

                $aOut = array_merge(
                    $aOut,
                    $this->extractDriverSettings(
                        $oSetting->fields,
                        $sSlug
                    )
                );

            } else {

                $sValue = appSetting($oSetting->key, $sSlug);
                if (is_null($sValue) && isset($oSetting->default)) {
                    $sValue = $oSetting->default;
                }
                $aOut[$oSetting->key] = $sValue;
            }
        }

        return $aOut;
    }

    // --------------------------------------------------------------------------

    /**
     * Search a textfile containing a lsit of Google shopping categories
     * @todo find a home for this other than here, should probably live within the Google driver
     * @param  string $sTerm The search term
     * @return array
     */
    public function searchGoogleCategories($sTerm)
    {
        //  Open the cachefile, if it's not available then fetch a new one
        $sCacheFile = DEPLOY_CACHE_DIR . 'shop-feed-google-categories-' . date('m-Y') . '.txt';

        if (!file_exists($sCacheFile)) {

            //  @todo handle multiple locales
            $sData = file_get_contents('http://www.google.com/basepages/producttype/taxonomy.en-GB.txt');

            if (empty($sData)) {

                $this->setError('Failed to fetch feed from Google.');
                return false;
            }

            file_put_contents($sCacheFile, $sData);
        }

        $oHandle  = fopen($sCacheFile, 'r');
        $aResults = array();

        if ($oHandle) {

            while (($sLine = fgets($oHandle)) !== false) {

                if (substr($sLine, 0, 1) === '#') {
                    continue;
                }

                if (preg_match('/' . $sTerm . '/i', $sLine)) {

                    $aResults[] = $sLine;
                }
            }

            fclose($oHandle);

            return $aResults;

        } else {

            $this->setError('Failed to read feed from cache.');
            return false;
        }
    }
}
