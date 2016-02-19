<?php

/**
 * Migration:   7
 * Started:     19/02/2016
 * Finalised:   19/02/2016
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Database Migration
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Database\Migration\Nailsapp\ModuleShop;

use Nails\Common\Console\Migrate\Base;

class Migration7 extends Base
{
    /**
     * Execute the migration
     * @return Void
     */
    public function execute()
    {
        $this->query("UPDATE `{{NAILS_DB_PREFIX}}app_setting` SET `grouping` = 'nailsapp/module-shop' WHERE `grouping` = 'shop';");
        $this->query("UPDATE `{{NAILS_DB_PREFIX}}app_notification` SET `grouping` = 'nailsapp/module-shop' WHERE `grouping` = 'shop';");
    }
}
