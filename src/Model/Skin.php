<?php

/**
 * This model manages the Shop skins
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Model
 * @author      Nails Dev Team
 * @link
* @todo consider consolidating this into a single, Nails-wide, skin model
 */

namespace Nails\Shop\Model;

use Nails\Shop\Exception\SkinException;

class Skin
{
    protected $aAvailable;
    protected $aEnabled;

    // --------------------------------------------------------------------------

    const DEFAULT_FRONT_SKIN    = 'nailsapp/skin-shop-front-classic';
    const DEFAULT_CHECKOUT_SKIN = 'nailsapp/skin-shop-checkout-classic';

    // --------------------------------------------------------------------------

    /**
     * Construct the model.
     */
    public function __construct()
    {
        $this->aAvailable = array();
        $this->aEnabled   = array();

        //  Get available skins
        $this->aAvailable['FRONT']    = _NAILS_GET_SKINS('nailsapp/module-shop', 'front');
        $this->aAvailable['CHECKOUT'] = _NAILS_GET_SKINS('nailsapp/module-shop', 'checkout');

        if (empty($this->aAvailable['FRONT'])) {
            throw new SkinException(
                'No Front of House skins are available.'
            );
        }

        if (empty($this->aAvailable['CHECKOUT'])) {
            throw new SkinException(
                'No Checkout skins are available.'
            );
        }

        //  Get the front skin
        $sSkinSlug               = appSetting('skin_front', 'shop') ?: self::DEFAULT_FRONT_SKIN;
        $this->aEnabled['FRONT'] = $this->get('front', $sSkinSlug);
        if (empty($this->aEnabled['FRONT'])) {
            throw new SkinException(
                'Front of House Skin "' . $sSkinSlug . '" does not exist.'
            );
        }

        //  Get the checkout skin
        $sSkinSlug                  = appSetting('skin_checkout', 'shop') ?: self::DEFAULT_CHECKOUT_SKIN;
        $this->aEnabled['CHECKOUT'] = $this->get('checkout', $sSkinSlug);
        if (empty($this->aEnabled['CHECKOUT'])) {
            throw new SkinException(
                'Checkout Skin "' . $sSkinSlug . '" does not exist.'
            );
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches all available front skins
     * @param  string $sType The skin's type
     * @return array
     */
    public function getAvailable($sType)
    {
        if (!isset($this->aAvailable[strtoupper($sType)])) {
            throw new SkinException(
                '"' . $sType . '" is not a valid skin type.'
            );
        }

        return $this->aAvailable[strtoupper($sType)];
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the config for the enabled skin
     * @param  string   $sType The skin's type
     * @return stdClass
     */
    public function getEnabled($sType)
    {
        if (!isset($this->aEnabled[strtoupper($sType)])) {
            throw new SkinException(
                '"' . $sType . '" is not a valid skin type.'
            );
        }

        return $this->aEnabled[strtoupper($sType)];
    }

    // --------------------------------------------------------------------------

    /**
     * Gets a single skin
     * @param  string  $sType The skin's type
     * @param  string  $sSlug The skin's slug
     * @return stdClass
     */
    public function get($sType, $sSlug)
    {
        $aSkins = $this->getAvailable($sType);

        foreach ($aSkins as $oSkin) {
            if ($oSkin->slug == $sSlug) {
                return $oSkin;
            }
        }

        return false;
    }

    // --------------------------------------------------------------------------

    /**
     * Retrives a skin setting
     * @param  string $sKey  The key to retrieve
     * @param  string $sType The skin's type
     * @return mixed
     */
    public function getSetting($sKey, $sType)
    {
        if (!isset($this->aEnabled[strtoupper($sType)])) {
            throw new SkinException(
                '"' . $sType . '" is not a valid skin type.'
            );
        }

        return appSetting($sKey, $this->aEnabled[strtoupper($sType)]->slug);
    }
}
