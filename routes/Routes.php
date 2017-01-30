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
use Nails\Factory;
use PDO;

class Routes extends BaseRoutes
{
    /**
     * Returns an array of routes for this module
     * @return array
     */
    public function getRoutes()
    {
        $oDb     = Factory::service('ConsoleDatabase', 'nailsapp/module-console');
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
