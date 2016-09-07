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

$config['email_types'] = array(
    (object) array(
        'slug'             => 'shop_inform_product_available',
        'name'             => 'Shop: Variant back in stock',
        'description'      => 'Sent to customers who have requested to be informed when a product/variant is back in stock.',
        'isUnsubscribable' => false,
        'template_header'  => '',
        'template_body'    => 'shop/email/inventory/inform_product_available',
        'template_footer'  => '',
        'default_subject'  => '{{product.label}} is back in stock'
    ),

    (object) array(
        'name'             => 'Shop: Manager Order Notification',
        'slug'             => 'shop_notification_paid',
        'description'      => 'Sent to the shop manager(s) when an order is completed.',
        'isUnsubscribable' => false,
        'template_header'  => '',
        'template_body'    => 'shop/email/order/notification_paid',
        'template_footer'  => '',
        'default_subject'  => 'An order has been completed'
    ),
    (object) array(
        'name'             => 'Shop: Manager Order Notification (partial payment)',
        'slug'             => 'shop_notification_partial_payment',
        'description'      => 'Sent to the shop manager(s) when an order receives a partial payment.',
        'isUnsubscribable' => false,
        'template_header'  => '',
        'template_body'    => 'shop/email/order/notification_partial_payment',
        'template_footer'  => '',
        'default_subject'  => 'A payment was received'
    ),
    (object) array(
        'name'             => 'Shop: Customer Receipt',
        'slug'             => 'shop_receipt',
        'description'      => 'Sent to the customer when their order is fully paid.',
        'isUnsubscribable' => false,
        'template_header'  => '',
        'template_body'    => 'shop/email/order/receipt',
        'template_footer'  => '',
        'default_subject'  => 'Thanks for your order'
    ),
    (object) array(
        'name'             => 'Shop: Customer Receipt (partial payment)',
        'slug'             => 'shop_receipt_partial_payment',
        'description'      => 'Sent to the customer when a partial payment is received for an order',
        'isUnsubscribable' => false,
        'template_header'  => '',
        'template_body'    => 'shop/email/order/receipt_partial_payment',
        'template_footer'  => '',
        'default_subject'  => 'Thanks for your payment'
    ),
    (object) array(
        'name'             => 'Shop: Order Lifecycle Updated',
        'slug'             => 'shop_order_lifecycle',
        'description'      => 'Sent to the customer when order lifecycle is updated (and lifecycle requests email update)',
        'isUnsubscribable' => false,
        'template_header'  => '',
        'template_body'    => 'shop/email/order/lifecycle',
        'template_footer'  => '',
        'default_subject'  => 'Order Updated'
    ),
);
