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

class Routes
{
    /**
     * Returns an array of routes for this module
     * @return array
     */
    public function getRoutes()
    {
        $routes   = array();
        $settings = appSetting(null, 'nailsapp/module-shop', true);

        //  Shop front page route
        $shopUrl = isset($settings['url']) ? substr($settings['url'], 0, -1) : 'shop';
        $routes[$shopUrl . '(/(.+))?'] = 'shop/$2';

        //  @todo: all shop product/category/tag/sale routes etc

        return $routes;
    }
}
