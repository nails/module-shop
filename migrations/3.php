<?php

/**
 * Migration:   3
 * Started:     15/05/2015
 * Finalised:
 */

namespace Nails\Database\Migration\Nailsapp\ModuleShop;

use Nails\Common\Console\Migrate\Base;

class Migration3 extends Base
{
    /**
     * Execute the migration
     * @return Void
     */
    public function execute()
    {
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_product` ADD `google_category` VARCHAR(300)  NULL  DEFAULT NULL  AFTER `external_vendor_url`;");
    }
}
