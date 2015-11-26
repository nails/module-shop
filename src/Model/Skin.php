<?php

/**
 * This model manages the Shop skins
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Shop\Model;

class Skin
{
    protected $aAvailable;

    // --------------------------------------------------------------------------

    /**
     * Construct the model.
     */
    public function __construct()
    {
        $this->aAvailable             = array();
        $this->aAvailable['FRONT']    = _NAILS_GET_SKINS('nailsapp/module-shop', 'front');
        $this->aAvailable['CHECKOUT'] = _NAILS_GET_SKINS('nailsapp/module-shop', 'checkout');
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches all available front skins
     * @param  string $sType The skin's type
     * @return array
     */
    public function getAvailable($sType)
    {
        if (isset($this->aAvailable[strtoupper($sType)])) {

            return $this->aAvailable[strtoupper($sType)];

        } else {

            return array();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Gets a single skin
     * @param  string  $sType The skin's type
     * @param  string  $sSlug The skin's name
     * @return stdClass
     */
    public function get($sType, $sName)
    {
        $aSkins = $this->getAvailable($sType);

        foreach ($aSkins as $oSkin) {
            if ($oSkin->name == $sName) {
                return $oSkin;
            }
        }

        return false;
    }
}
