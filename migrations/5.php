<?php

/**
 * Migration:   5
 * Started:     09/11/2015
 * Finalised:   09/11/2015
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Database Migration
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Database\Migration\Nailsapp\ModuleShop;

use Nails\Common\Console\Migrate\Base;

class Migration5 extends Base
{
    /**
     * Execute the migration
     * @return Void
     */
    public function execute()
    {
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` CHANGE `note` `note` VARCHAR(150)  CHARACTER SET utf8  COLLATE utf8_general_ci  NULL  DEFAULT '';");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` ADD `total_base_item_discount` INT(11)  NOT NULL  AFTER `total_base_item`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` ADD `total_base_shipping_discount` INT(11)  NOT NULL  AFTER `total_base_shipping`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` ADD `total_base_tax_discount` INT(11)  NOT NULL  AFTER `total_base_tax`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` ADD `total_base_grand_discount` INT(11)  NOT NULL  AFTER `total_base_grand`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` ADD `total_user_item_discount` INT(11)  NOT NULL  AFTER `total_user_item`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` ADD `total_user_shipping_discount` INT(11)  NOT NULL  AFTER `total_user_shipping`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` ADD `total_user_tax_discount` INT(11)  NOT NULL  AFTER `total_user_tax`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` ADD `total_user_grand_discount` INT(11)  NOT NULL  AFTER `total_user_grand`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_voucher` CHANGE `discount_value` `discount_value` INT(11)  UNSIGNED  NOT NULL  DEFAULT '0';");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_voucher` CHANGE `gift_card_balance` `gift_card_balance` INT(11)  UNSIGNED  NOT NULL  DEFAULT '0';");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_voucher` CHANGE `discount_application` `discount_application` ENUM('PRODUCTS','PRODUCT','PRODUCT_TYPES','SHIPPING','ALL')  CHARACTER SET utf8  COLLATE utf8_general_ci  NOT NULL  DEFAULT 'PRODUCTS';");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_voucher` ADD `product_id` INT(11)  UNSIGNED  NULL  DEFAULT NULL  AFTER `product_type_id`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_voucher` ADD FOREIGN KEY (`product_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_product` (`id`) ON DELETE CASCADE;");
    }
}
