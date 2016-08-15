<?php

/**
 * Migration:   8
 * Started:     04/03/2016
 * Finalised:   29/06/2016
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Database Migration
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Database\Migration\Nailsapp\ModuleShop;

use Nails\Common\Console\Migrate\Base;

class Migration8 extends Base
{
    /**
     * Execute the migration
     * @return Void
     */
    public function execute()
    {
        $this->query("UPDATE `nails_app_setting` SET `key` = 'enabled_driver_feed' WHERE `key` = 'enabled_feed_drivers' AND `grouping` = 'nailsapp/module-shop';");
    }
}
