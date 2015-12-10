<?php

/**
 * shops the shop overview and basic stats
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    AdminController
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Admin\Shop;

use Nails\Factory;
use Nails\Admin\Helper;
use Nails\Shop\Controller\BaseAdmin;

class Overview extends BaseAdmin
{
    /**
     * Announces this controller's navGroups
     * @return stdClass
     */
    public static function announce()
    {
        if (userHasPermission('admin:shop:overview:view')) {

            $oNavGroup = Factory::factory('Nav', 'nailsapp/module-admin');
            $oNavGroup->setLabel('Shop');
            $oNavGroup->setIcon('fa-shopping-cart');
            $oNavGroup->addAction('Overview', 'index', array(), -1);

            return $oNavGroup;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of extra permissions for this controller
     * @return array
     */
    public static function permissions()
    {
        $aPermissions = parent::permissions();

        $aPermissions['view'] = 'Can view the shop overview';

        return $aPermissions;
    }

    // --------------------------------------------------------------------------

    /**
     * Shows top line stats and details of thigns like unfulfilled orders.
     * @return void
     */
    public function index()
    {
        $this->data['page']->title = 'Shop Overview';
        Helper::loadView('index');
    }
}
