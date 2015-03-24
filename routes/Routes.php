<?php

namespace Nails\Routes\Shop;

/**
 * Generates shop routes
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

class Routes
{
    /**
     * Returns an array of routes for this module
     * @return array
     */
    public function getRoutes()
    {
        $routes   = array();
        $settings = app_setting(null, 'shop', true);

        //  Shop front page route
        $shopUrl = isset($settings['url']) ? substr($settings['url'], 0, -1) : 'shop';
        $routes[$shopUrl . '(/(.+))?'] = 'shop/$2';

        //  @todo: all shop product/category/tag/sale routes etc

        return $routes;
    }
}
