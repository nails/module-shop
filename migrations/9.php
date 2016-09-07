<?php

/**
 * Migration:   9
 * Started:     29/06/2016
 * Finalised:   07/08/2016
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Database Migration
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Database\Migration\Nailsapp\ModuleShop;

use Nails\Common\Console\Migrate\Base;

class Migration9 extends Base
{
    /**
     * Execute the migration
     * @return Void
     */
    public function execute()
    {
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` CHANGE `total_base_tax` `total_base_tax_item` INT(11)  NOT NULL DEFAULT '0';");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` CHANGE `total_base_tax_discount` `total_base_tax_item_discount` INT(11)  NOT NULL DEFAULT '0';");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` CHANGE `total_user_tax` `total_user_tax_item` INT(11)  NOT NULL DEFAULT '0';");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` CHANGE `total_user_tax_discount` `total_user_tax_item_discount` INT(11)  NOT NULL DEFAULT '0';");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` ADD `total_base_tax_shipping` INT(11)  NOT NULL  DEFAULT '0'  AFTER `total_base_tax_item_discount`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` ADD `total_base_tax_shipping_discount` INT(11)  NOT NULL  DEFAULT '0'  AFTER `total_base_tax_shipping`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` ADD `total_user_tax_shipping` INT(11)  NOT NULL  DEFAULT '0'  AFTER `total_user_tax_item_discount`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` ADD `total_user_tax_shipping_discount` INT(11)  NOT NULL  DEFAULT '0'  AFTER `total_user_tax_shipping`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` CHANGE `total_base_item` `total_base_item` INT(11)  NOT NULL  DEFAULT '0';");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` CHANGE `total_base_item_discount` `total_base_item_discount` INT(11)  NOT NULL  DEFAULT '0';");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` CHANGE `total_base_shipping` `total_base_shipping` INT(11)  NOT NULL  DEFAULT '0';");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` CHANGE `total_base_shipping_discount` `total_base_shipping_discount` INT(11)  NOT NULL  DEFAULT '0';");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` CHANGE `total_base_tax_item` `total_base_tax_item` INT(11)  NOT NULL  DEFAULT '0';");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` CHANGE `total_base_grand` `total_base_grand` INT(11)  NOT NULL  DEFAULT '0';");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` CHANGE `total_base_grand_discount` `total_base_grand_discount` INT(11)  NOT NULL  DEFAULT '0';");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` CHANGE `total_user_item` `total_user_item` INT(11)  NOT NULL  DEFAULT '0';");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` CHANGE `total_user_item_discount` `total_user_item_discount` INT(11)  NOT NULL  DEFAULT '0';");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` CHANGE `total_user_shipping` `total_user_shipping` INT(11)  NOT NULL  DEFAULT '0';");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` CHANGE `total_user_shipping_discount` `total_user_shipping_discount` INT(11)  NOT NULL  DEFAULT '0';");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` CHANGE `total_user_grand` `total_user_grand` INT(11)  NOT NULL  DEFAULT '0';");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` CHANGE `total_user_grand_discount` `total_user_grand_discount` INT(11)  NOT NULL  DEFAULT '0';");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` ADD `total_base_tax_combined` INT(11)  NOT NULL  DEFAULT '0'  AFTER `total_base_tax_shipping_discount`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` ADD `total_base_tax_combined_discount` INT(11)  NOT NULL  DEFAULT '0'  AFTER `total_base_tax_combined`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` ADD `total_user_tax_combined` INT(11)  NOT NULL  DEFAULT '0'  AFTER `total_user_tax_shipping_discount`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` ADD `total_user_tax_combined_discount` INT(11)  NOT NULL  DEFAULT '0'  AFTER `total_user_tax_combined`;");

    }
}
