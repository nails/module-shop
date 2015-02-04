<?php

/**
 * Generate shop reports
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    AdminController
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Admin\Shop;

class Reports extends \AdminController
{
    /**
     * Announces this controller's navGroups
     * @return stdClass
     */
    public static function announce()
    {
        if (userHasPermission('admin.shop:0.can_generate_reports')) {

            $navGroup = new \Nails\Admin\Nav('Shop');
            $navGroup->addMethod('Generate Reports');
            return $navGroup;
        }
    }
}
