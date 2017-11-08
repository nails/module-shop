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

namespace Nails\Shop;

use Nails\Common\Interfaces\RouteGenerator;
use Nails\Factory;
use PDO;

class Routes implements RouteGenerator
{
    /**
     * Returns an array of routes for this module
     * @return array
     */
    public static function generate()
    {
        $oDb     = Factory::service('PDODatabase');
        $oModel  = Factory::model('AppSetting');
        $aRoutes = [];

        $oRows = $oDb->query('
          SELECT * FROM ' . $oModel->getTableName() . '
          WHERE `grouping` = "nailsapp/module-shop"
          AND `key` = "url"

        ');

        if (!$oRows->rowCount()) {
            return $aRoutes;
        }

        $sUrl = json_decode($oRows->fetch(PDO::FETCH_OBJ)->value) ?: 'shop';
        $sUrl = preg_replace('/^\//', '', $sUrl);
        $sUrl = preg_replace('/\/$/', '', $sUrl);

        $aRoutes[$sUrl . '(/(.+))?'] = 'shop/$2';

        return $aRoutes;
    }
}
