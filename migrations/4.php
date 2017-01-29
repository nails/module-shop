<?php

/**
 * Migration:   4
 * Started:     29/09/2015
 * Finalised:
 */

namespace Nails\Database\Migration\Nailsapp\ModuleShop;

use Nails\Common\Console\Migrate\Base;

class Migration4 extends Base
{
    /**
     * Execute the migration
     * @return Void
     */
    public function execute()
    {
        $this->query("
            CREATE TABLE `{{NAILS_DB_PREFIX}}user_meta_shop` (
                `user_id` int(11) unsigned NOT NULL,
                `basket` text,
                `currency` char(3) DEFAULT NULL,
                `recently_viewed` varchar(300) DEFAULT NULL,
                PRIMARY KEY (`user_id`),
                KEY `user_id` (`user_id`),
                CONSTRAINT `{{NAILS_DB_PREFIX}}user_meta_shop_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }
}
