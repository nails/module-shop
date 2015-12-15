<?php

/**
 * This config file defines email types for this module.
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Config
 * @author      Nails Dev Team
 * @link
 */

$config['email_types'] = array();

$config['email_types'][0]                   = new \stdClass();
$config['email_types'][0]->slug             = 'shop_inform_product_available';
$config['email_types'][0]->name             = 'Shop: Variant back in stock';
$config['email_types'][0]->description      = 'Sent to customers who have requested to be informed when a product/variant is back in stock.';
$config['email_types'][0]->isUnsubscribable = false;
$config['email_types'][0]->template_header  = '';
$config['email_types'][0]->template_body    = 'shop/email/inventory/inform_product_available';
$config['email_types'][0]->template_footer  = '';
$config['email_types'][0]->default_subject  = '{{product.label}} is back in stock';

$config['email_types'][1]                   = new \stdClass();
$config['email_types'][1]->slug             = 'shop_notification_paid';
$config['email_types'][1]->name             = 'Shop Manager Order Notification';
$config['email_types'][1]->description      = 'Sent to the shop manager(s) when an order is completed.';
$config['email_types'][1]->isUnsubscribable = false;
$config['email_types'][1]->template_header  = '';
$config['email_types'][1]->template_body    = 'shop/email/order/notification_paid';
$config['email_types'][1]->template_footer  = '';
$config['email_types'][1]->default_subject  = 'An order has been completed';

$config['email_types'][2]                   = new \stdClass();
$config['email_types'][2]->slug             = 'shop_notification_partial_payment';
$config['email_types'][2]->name             = 'Shop Manager Order Notification (partial payment)';
$config['email_types'][2]->description      = 'Sent to the shop manager(s) when an order receives a partial payment.';
$config['email_types'][2]->isUnsubscribable = false;
$config['email_types'][2]->template_header  = '';
$config['email_types'][2]->template_body    = 'shop/email/order/notification_partial_payment';
$config['email_types'][2]->template_footer  = '';
$config['email_types'][2]->default_subject  = 'A payment was received';

$config['email_types'][3]                   = new \stdClass();
$config['email_types'][3]->slug             = 'shop_receipt';
$config['email_types'][3]->name             = 'Customer Receipt';
$config['email_types'][3]->description      = 'Sent to the customer when their order is fully paid.';
$config['email_types'][3]->isUnsubscribable = false;
$config['email_types'][3]->template_header  = '';
$config['email_types'][3]->template_body    = 'shop/email/order/receipt';
$config['email_types'][3]->template_footer  = '';
$config['email_types'][3]->default_subject  = 'Thanks for your order';

$config['email_types'][4]                   = new \stdClass();
$config['email_types'][4]->slug             = 'shop_receipt_partial_payment';
$config['email_types'][4]->name             = 'Customer Receipt (partial payment)';
$config['email_types'][4]->description      = 'Sent to the customer when a partial payment is received for an order';
$config['email_types'][4]->isUnsubscribable = false;
$config['email_types'][4]->template_header  = '';
$config['email_types'][4]->template_body    = 'shop/email/order/receipt_partial_payment';
$config['email_types'][4]->template_footer  = '';
$config['email_types'][4]->default_subject  = 'Thanks for your payment';

$config['email_types'][5]                   = new \stdClass();
$config['email_types'][5]->slug             = 'shop_order_fulfilled';
$config['email_types'][5]->name             = 'Order Fulfilled';
$config['email_types'][5]->description      = 'Sent to the customer when order is marked as fulfilled';
$config['email_types'][5]->isUnsubscribable = false;
$config['email_types'][5]->template_header  = '';
$config['email_types'][5]->template_body    = 'shop/email/order/fulfilled';
$config['email_types'][5]->template_footer  = '';
$config['email_types'][5]->default_subject  = 'Order Fulfilled';
