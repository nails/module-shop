<?php

/**
 * Migration:   5
 * Started:     09/11/2015
 * Finalised:
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
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` DROP `price_base_value`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` DROP `price_user_value`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` DROP `sale_price_base_value`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` DROP `sale_price_base_value_inc_tax`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` DROP `sale_price_base_value_ex_tax`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` DROP `sale_price_base_value_tax`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` DROP `sale_price_user_value`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` DROP `sale_price_user_value_inc_tax`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` DROP `sale_price_user_value_ex_tax`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` DROP `sale_price_user_value_tax`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` ADD `price_base_discount_value_inc_tax` INT(11)  NOT NULL  AFTER `price_base_value_tax`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` ADD `price_base_discount_value_ex_tax` INT(11)  NOT NULL  AFTER `price_base_discount_value_inc_tax`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` ADD `price_base_discount_value_tax` INT(11)  NOT NULL  AFTER `price_base_discount_value_ex_tax`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` ADD `price_user_discount_value_inc_tax` INT(11)  NOT NULL  AFTER `price_user_value_tax`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` ADD `price_user_discount_value_ex_tax` INT(11)  NOT NULL  AFTER `price_user_discount_value_inc_tax`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` ADD `price_user_discount_value_tax` INT(11)  NOT NULL  AFTER `price_user_discount_value_ex_tax`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` ADD `price_base_discount_item` INT(11)  NOT NULL  AFTER `price_base_discount_value_tax`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` ADD `price_base_discount_tax` INT(11)  NOT NULL  AFTER `price_base_discount_item`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` ADD `price_user_discount_item` INT(11)  NOT NULL  AFTER `price_user_discount_value_tax`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order_product` ADD `price_user_discount_tax` INT(11)  NOT NULL  AFTER `price_user_discount_item`;");
        $this->query("UPDATE `{{NAILS_DB_PREFIX}}shop_order_product` SET `price_base_discount_value_inc_tax` = `price_base_value_inc_tax`, `price_base_discount_value_ex_tax` = `price_base_value_ex_tax`, `price_base_discount_value_tax` = `price_base_value_tax`, `price_user_discount_value_inc_tax` = `price_user_value_inc_tax`, `price_user_discount_value_ex_tax` = `price_user_value_ex_tax`, `price_user_discount_value_tax` = `price_user_value_tax`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` ADD `shipping_driver` VARCHAR(150)  NULL  DEFAULT NULL  AFTER `shipping_country`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` ADD `shipping_option` VARCHAR(150)  NULL  DEFAULT NULL  AFTER `shipping_driver`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` ADD `delivery_option` VARCHAR(150)  NULL  DEFAULT ''  AFTER `delivery_type`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` CHANGE `fulfilment_status` `fulfilment_status` ENUM('UNFULFILLED','PACKED','FULFILLED')  CHARACTER SET utf8  COLLATE utf8_general_ci  NOT NULL  DEFAULT 'UNFULFILLED';");

        /**
         * Convert ship driver settings into JSON strings rather than use serialize
         */

        $oResult = $this->query('SELECT id, ship_driver_data FROM {{NAILS_DB_PREFIX}}shop_product_variation');
        while ($oRow = $oResult->fetch(\PDO::FETCH_OBJ)) {

            $mOldValue = unserialize($oRow->ship_driver_data);
            $sNewValue = json_encode($mOldValue);

            //  Update the record
            $sQuery = '
                UPDATE `{{NAILS_DB_PREFIX}}shop_product_variation`
                SET
                    `ship_driver_data` = :newValue
                WHERE
                    `id` = :id
            ';

            $oSth = $this->prepare($sQuery);

            $oSth->bindParam(':newValue', $sNewValue, \PDO::PARAM_STR);
            $oSth->bindParam(':id', $oRow->id, \PDO::PARAM_INT);

            $oSth->execute();
        }
    }
}
