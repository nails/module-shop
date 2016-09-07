<?php

/**
 * Migration:   10
 * Started:     07/08/2016
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

class Migration10 extends Base
{
    /**
     * Execute the migration
     * @return Void
     */
    public function execute()
    {
        $this->query("
            CREATE TABLE `{{NAILS_DB_PREFIX}}shop_order_lifecycle` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `label` varchar(150) DEFAULT NULL,
                `admin_icon` varchar(25) DEFAULT NULL,
                `admin_note` text,
                `admin_sidebar_show` tinyint(1) unsigned NOT NULL DEFAULT '0',
                `admin_sidebar_severity` enum('info','danger','success','warning') DEFAULT 'info',
                `send_email` tinyint(1) unsigned NOT NULL DEFAULT '0',
                `email_subject` varchar(150) DEFAULT NULL,
                `email_body` text,
                `email_body_plaintext` text,
                `order` int(11) unsigned NOT NULL DEFAULT '0',
                `created` datetime NOT NULL,
                `created_by` int(11) unsigned DEFAULT NULL,
                `modified` datetime NOT NULL,
                `modified_by` int(11) unsigned DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `created_by` (`created_by`),
                KEY `modified_by` (`modified_by`),
                CONSTRAINT `{{NAILS_DB_PREFIX}}shop_order_lifecycle_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{{NAILS_DB_PREFIX}}shop_order_lifecycle_ibfk_2` FOREIGN KEY (`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
        $this->query("
            CREATE TABLE `{{NAILS_DB_PREFIX}}shop_order_lifecycle_history` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `order_id` int(11) unsigned NOT NULL,
                `lifecycle_id` int(11) unsigned NOT NULL,
                `email_id` int(11) unsigned DEFAULT NULL,
                `created` datetime NOT NULL,
                `created_by` int(11) unsigned DEFAULT NULL,
                `modified` datetime NOT NULL,
                `modified_by` int(11) unsigned DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `created_by` (`created_by`),
                KEY `modified_by` (`modified_by`),
                KEY `order_id` (`order_id`),
                KEY `lifecycle_id` (`lifecycle_id`),
                KEY `email_id` (`email_id`),
                CONSTRAINT `{{NAILS_DB_PREFIX}}shop_order_lifecycle_history_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{{NAILS_DB_PREFIX}}shop_order_lifecycle_history_ibfk_2` FOREIGN KEY (`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{{NAILS_DB_PREFIX}}shop_order_lifecycle_history_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_order` (`id`) ON DELETE CASCADE,
                CONSTRAINT `{{NAILS_DB_PREFIX}}shop_order_lifecycle_history_ibfk_4` FOREIGN KEY (`lifecycle_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_order_lifecycle` (`id`) ON DELETE CASCADE,
                CONSTRAINT `{{NAILS_DB_PREFIX}}shop_order_lifecycle_history_ibfk_5` FOREIGN KEY (`email_id`) REFERENCES `{{NAILS_DB_PREFIX}}email_archive` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
        $this->query("
            INSERT INTO `{{NAILS_DB_PREFIX}}shop_order_lifecycle` (`id`, `label`, `admin_icon`, `admin_note`, `admin_sidebar_show`, `admin_sidebar_severity`, `send_email`, `email_subject`, `email_body`, `email_body_plaintext`, `order`, `created`, `created_by`, `modified`, `modified_by`)
            VALUES
                (1, 'Placed', 'fa-question-circle', 'The order has been placed but payment has not yet been confirmed.', 0, NULL, 0, NULL, NULL, NULL, 0, NOW(), NULL, NOW(), NULL),
                (2, 'Paid', 'fa-credit-card', 'Payment has been confirmed.', 1, 'danger', 0, NULL, NULL, NULL, 1, NOW(), NULL, NOW(), NULL),
                (3, 'Dispatched', 'fa-truck', 'The order has been dispatched and is on its way to the customer.', 0, NULL, 1, 'Your order is on its way!', '<p>We thought you\'d like to know that your recent order with us has been dispatched.</p>\r<p>It was dispatched using our <strong>{{order.shipping_option.label}}</strong> delivery service.</p>', 'We thought you\'d like to know that your recent order with us has been dispatched.\r\rIt was dispatched using our {{order.shipping_option.label}} delivery service.', 2, NOW(), NULL, NOW(), NULL),
                (4, 'Collected', 'fa-user', 'The order has been collected by the customer.', 0, NULL, 1, 'Your order was collected', '<p>We thought you\'d like to know that your recent order with us has been collected.</p>', 'We thought you\'d like to know that your recent order with us has been collected.', 3, NOW(), NULL, NOW(), NULL);
        ");

        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` ADD `lifecycle_id` INT(11)  UNSIGNED  NOT NULL  DEFAULT '1'  AFTER `fulfilled`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` ADD FOREIGN KEY (`lifecycle_id`) REFERENCES `{{NAILS_DB_PREFIX}}shop_order_lifecycle` (`id`) ON DELETE RESTRICT;");

        //  Map all the existing "paid" orders to the new lifecycle (id: 2)
        $sQuery = '
            UPDATE `{{NAILS_DB_PREFIX}}shop_order`
            SET
                `lifecycle_id` = 2
            WHERE
                `status` = "PAID"
        ';
        $this->query($sQuery);


        //  Map all the existing "fulfilled" orders to the new lifecycle (id: 3)
        $sQuery = '
            UPDATE `{{NAILS_DB_PREFIX}}shop_order`
            SET
                `lifecycle_id` = 3
            WHERE
                `status` = "PAID"
                AND
                `fulfilment_status` = "FULFILLED"
        ';
        $this->query($sQuery);

        //  Map all the existing "collection" orders to the new lifecycle (id: 4)
        $sQuery = '
            UPDATE `{{NAILS_DB_PREFIX}}shop_order`
            SET
                `lifecycle_id` = 4
            WHERE
                `status` = "PAID"
                AND
                `fulfilment_status` = "FULFILLED"
                AND
                `delivery_type` = "COLLECT"

        ';
        $this->query($sQuery);

        //  Remove fulfilled columns
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` DROP `fulfilled`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}shop_order` DROP `fulfilment_status`;");
    }
}
