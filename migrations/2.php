<?php

/**
 * Migration:   2
 * Started:     11/05/2015
 * Finalised:   11/05/2015
 */

namespace Nails\Database\Migration\Nailsapp\ModuleShop;

use Nails\Common\Console\Migrate\Base;

class Migration2 extends Base
{
    /**
     * Execute the migration
     * @return Void
     */
    public function execute()
    {
        $this->query("
            CREATE TABLE `{{NAILS_DB_PREFIX}}shop_product_supplier` (
                `product_id` int(11) unsigned NOT NULL,
                `supplier_id` int(11) unsigned NOT NULL,
                KEY `product_id` (`product_id`),
                KEY `supplier_id` (`supplier_id`),
                CONSTRAINT `{{NAILS_DB_PREFIX}}shop_product_supplier_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_product` (`id`) ON DELETE CASCADE,
                CONSTRAINT `{{NAILS_DB_PREFIX}}shop_product_supplier_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_supplier` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
        $this->query("
            CREATE TABLE `{{NAILS_DB_PREFIX}}shop_supplier` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `slug` varchar(150) DEFAULT NULL,
                `label` varchar(255) NOT NULL DEFAULT '',
                `created` datetime NOT NULL,
                `created_by` int(11) unsigned DEFAULT NULL,
                `modified` datetime NOT NULL,
                `modified_by` int(11) unsigned DEFAULT NULL,
                `is_active` tinyint(1) unsigned NOT NULL DEFAULT '0',
                PRIMARY KEY (`id`),
                KEY `created_by` (`created_by`),
                KEY `modified_by` (`modified_by`),
                CONSTRAINT `{{NAILS_DB_PREFIX}}shop_supplier_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{{NAILS_DB_PREFIX}}shop_supplier_ibfk_3` FOREIGN KEY (`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_product_variation` ADD `is_active` TINYINT(1)  UNSIGNED  NOT NULL  DEFAULT '1'  AFTER `ship_driver_data`;");
    }
}
