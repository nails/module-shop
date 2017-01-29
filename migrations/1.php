<?php

/**
 * Migration:   1
 * Started:     22/04/2015
 * Finalised:   22/04/2015
 */

namespace Nails\Database\Migration\Nailsapp\ModuleShop;

use Nails\Common\Console\Migrate\Base;

class Migration1 extends Base
{
    /**
     * Execute the migration
     * @return Void
     */
    public function execute()
    {
        /* These migrations convert the shop from using floats to ints for storing values */

        /* Update the shop_order tables; increase the range of the column before performing the calculation */
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` CHANGE `total_base_item` `total_base_item` FLOAT(100,6)  NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` CHANGE `total_base_item` `total_base_item` FLOAT(100,6) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` CHANGE `total_base_shipping` `total_base_shipping` FLOAT(100,6) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` CHANGE `total_base_tax` `total_base_tax` FLOAT(100,6) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` CHANGE `total_base_grand` `total_base_grand` FLOAT(100,6) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` CHANGE `total_user_item` `total_user_item` FLOAT(100,6) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` CHANGE `total_user_shipping` `total_user_shipping` FLOAT(100,6) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` CHANGE `total_user_tax` `total_user_tax` FLOAT(100,6) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` CHANGE `total_user_grand` `total_user_grand` FLOAT(100,6) NOT NULL;");
        $this->query("UPDATE `{{NAILS_DB_PREFIX}}shop_order` SET `total_base_item` = `total_base_item`*100, `total_base_shipping` = `total_base_shipping`*100, `total_base_tax` = `total_base_tax`*100, `total_base_grand` = `total_base_grand`*100, `total_user_item` = `total_user_item`*100, `total_user_shipping` = `total_user_shipping`*100, `total_user_tax` = `total_user_tax`*100, `total_user_grand` = `total_user_grand`*100;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` CHANGE `total_base_item` `total_base_item` INT(11) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` CHANGE `total_base_shipping` `total_base_shipping` INT(11) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` CHANGE `total_base_tax` `total_base_tax` INT(11) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` CHANGE `total_base_grand` `total_base_grand` INT(11) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` CHANGE `total_user_item` `total_user_item` INT(11) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` CHANGE `total_user_shipping` `total_user_shipping` INT(11) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` CHANGE `total_user_tax` `total_user_tax` INT(11) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` CHANGE `total_user_grand` `total_user_grand` INT(11) NOT NULL;");

        /* Update the shop_order_payment tables; increase the range of the column before performing the calculation */
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_payment` CHANGE `amount` `amount` FLOAT(100,6) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_payment` CHANGE `amount_base` `amount_base` FLOAT(100,6) NOT NULL;");
        $this->query("UPDATE `{{NAILS_DB_PREFIX}}shop_order_payment` SET `amount` = `amount`*100, `amount_base` = `amount_base`*100;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_payment` CHANGE `amount` `amount` INT(11) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_payment` CHANGE `amount_base` `amount_base` INT(11) NOT NULL;");

        /* Update the shop_order_product tables; increase the range of the column before performing the calculation */
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` CHANGE `price_base_value` `price_base_value` FLOAT(100,6) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` CHANGE `price_base_value_inc_tax` `price_base_value_inc_tax` FLOAT(100,6) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` CHANGE `price_base_value_ex_tax` `price_base_value_ex_tax` FLOAT(100,6) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` CHANGE `price_base_value_tax` `price_base_value_tax` FLOAT(100,6) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` CHANGE `price_user_value` `price_user_value` FLOAT(100,6) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` CHANGE `price_user_value_inc_tax` `price_user_value_inc_tax` FLOAT(100,6) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` CHANGE `price_user_value_ex_tax` `price_user_value_ex_tax` FLOAT(100,6) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` CHANGE `price_user_value_tax` `price_user_value_tax` FLOAT(100,6) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` CHANGE `sale_price_base_value` `sale_price_base_value` FLOAT(100,6) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` CHANGE `sale_price_base_value_inc_tax` `sale_price_base_value_inc_tax` FLOAT(100,6) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` CHANGE `sale_price_base_value_ex_tax` `sale_price_base_value_ex_tax` FLOAT(100,6) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` CHANGE `sale_price_base_value_tax` `sale_price_base_value_tax` FLOAT(100,6) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` CHANGE `sale_price_user_value` `sale_price_user_value` FLOAT(100,6) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` CHANGE `sale_price_user_value_inc_tax` `sale_price_user_value_inc_tax` FLOAT(100,6) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` CHANGE `sale_price_user_value_ex_tax` `sale_price_user_value_ex_tax` FLOAT(100,6) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` CHANGE `sale_price_user_value_tax` `sale_price_user_value_tax` FLOAT(100,6) NOT NULL;");
        $this->query("UPDATE `{{NAILS_DB_PREFIX}}shop_order_product` SET `price_base_value` = `price_base_value`*100, `price_base_value_inc_tax` = `price_base_value_inc_tax`*100, `price_base_value_ex_tax` = `price_base_value_ex_tax`*100, `price_base_value_tax` = `price_base_value_tax`*100, `price_user_value` = `price_user_value`*100, `price_user_value_inc_tax` = `price_user_value_inc_tax`*100, `price_user_value_ex_tax` = `price_user_value_ex_tax`*100, `price_user_value_tax` = `price_user_value_tax`*100, `sale_price_base_value` = `sale_price_base_value`*100, `sale_price_base_value_inc_tax` = `sale_price_base_value_inc_tax`*100, `sale_price_base_value_ex_tax` = `sale_price_base_value_ex_tax`*100, `sale_price_base_value_tax` = `sale_price_base_value_tax`*100, `sale_price_user_value` = `sale_price_user_value`*100, `sale_price_user_value_inc_tax` = `sale_price_user_value_inc_tax`*100, `sale_price_user_value_ex_tax` = `sale_price_user_value_ex_tax`*100, `sale_price_user_value_tax` = `sale_price_user_value_tax`*100;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` CHANGE `price_base_value` `price_base_value` INT(11) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` CHANGE `price_base_value_inc_tax` `price_base_value_inc_tax` INT(11) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` CHANGE `price_base_value_ex_tax` `price_base_value_ex_tax` INT(11) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` CHANGE `price_base_value_tax` `price_base_value_tax` INT(11) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` CHANGE `price_user_value` `price_user_value` INT(11) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` CHANGE `price_user_value_inc_tax` `price_user_value_inc_tax` INT(11) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` CHANGE `price_user_value_ex_tax` `price_user_value_ex_tax` INT(11) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` CHANGE `price_user_value_tax` `price_user_value_tax` INT(11) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` CHANGE `sale_price_base_value` `sale_price_base_value` INT(11) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` CHANGE `sale_price_base_value_inc_tax` `sale_price_base_value_inc_tax` INT(11) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` CHANGE `sale_price_base_value_ex_tax` `sale_price_base_value_ex_tax` INT(11) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` CHANGE `sale_price_base_value_tax` `sale_price_base_value_tax` INT(11) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` CHANGE `sale_price_user_value` `sale_price_user_value` INT(11) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` CHANGE `sale_price_user_value_inc_tax` `sale_price_user_value_inc_tax` INT(11) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` CHANGE `sale_price_user_value_ex_tax` `sale_price_user_value_ex_tax` INT(11) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` CHANGE `sale_price_user_value_tax` `sale_price_user_value_tax` INT(11) NOT NULL;");

        /* Update the shop_order_product tables; increase the range of the column before performing the calculation */
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_product_variation_price` CHANGE `price` `price` FLOAT(100,6) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_product_variation_price` CHANGE `sale_price` `sale_price` FLOAT(100,6) NOT NULL;");
        $this->query("UPDATE `{{NAILS_DB_PREFIX}}shop_product_variation_price` SET `price` = `price`*100, `sale_price` = `sale_price`*100;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_product_variation_price` CHANGE `price` `price` INT(11) NOT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_product_variation_price` CHANGE `sale_price` `sale_price` INT(11) NOT NULL;");

        /* And some other updates too */
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` ADD `ship_collection_only` TINYINT(1)  UNSIGNED  NOT NULL  DEFAULT '0'  AFTER `tax_rate_id`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` CHANGE `delivery_type` `delivery_type` ENUM('DELIVER','COLLECT', 'DELIVER_COLLECT')  CHARACTER SET utf8  COLLATE utf8_general_ci  NOT NULL  DEFAULT 'DELIVER';");

        $this->query("
            CREATE TABLE `{{NAILS_DB_PREFIX}}shop_product_related` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `product_id` int(11) unsigned NOT NULL,
                `related_id` int(11) unsigned NOT NULL,
                PRIMARY KEY (`id`),
                KEY `product_id` (`product_id`),
                KEY `related_id` (`related_id`),
                CONSTRAINT `{{NAILS_DB_PREFIX}}shop_product_related_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_product` (`id`) ON DELETE CASCADE,
                CONSTRAINT `{{NAILS_DB_PREFIX}}shop_product_related_ibfk_2` FOREIGN KEY (`related_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_product` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }
}
