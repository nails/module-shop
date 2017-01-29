<?php

/**
 * Generates shop routes
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Routes\Shop;

use Nails\Common\Model\BaseRoutes;

class Routes extends BaseRoutes
{
    /**
     * Returns an array of routes for this module
     * @return array
     */
    public function getRoutes()
    {
        $aRoutes   = [];
        $aSettings = appSetting(null, 'nailsapp/module-shop', true);

        //  Shop front page route
        $sShopUrl = isset($aSettings['url']) ? substr($aSettings['url'], 0, -1) : 'shop';

        $aRoutes[$sShopUrl . '(/(.+))?'] = 'shop/$2';

        //  @todo: all shop product/category/tag/sale routes etc

        return $aRoutes;
    }
}
