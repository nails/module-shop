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
    protected $aDrivers;
    protected $aEnabled;

    // --------------------------------------------------------------------------

    /**
     * Construct the model.
     */
    public function __construct()
    {
        $this->aDrivers = _NAILS_GET_DRIVERS('nailsapp/module-shop', 'feed') ?: array();
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
     * Gets a feed driver
     * @param  string  $sSlug The driver's slug
     * @return stdClass
     */
    public function get($sSlug)
    {
        $aDrivers = $this->getAvailable();

        foreach ($aDrivers as $oDriver) {
            if ($oDriver->slug == $sSlug) {
                return $oDriver;
            }
        }

        return false;
    }
}
