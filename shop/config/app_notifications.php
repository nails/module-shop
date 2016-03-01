<?php

/**
 * This config file defines app notifications for this module.
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Config
 * @author      Nails Dev Team
 * @link
 */

$config['notification_definitions'] = array(
    'nailsapp/module-shop' => (object) array(
        'slug'        => 'nailsapp/module-shop',
        'label'       => 'Shop',
        'description' => 'Shop related notifications',
        'options'     => array(
            'orders' => (object) array(
                'slug'          => 'orders',
                'label'         => 'Order Notifications',
                'sub_label'     => '',
                'tip'           => '',
                'email_subject' => 'An order has been placed',
                'email_tpl'     => '',
                'email_message' => ''
            ),
            'product_enquiry' => (object) array(
                'slug'          => 'product_enquiry',
                'label'         => 'Product Enquiries',
                'sub_label'     => '',
                'tip'           => '',
                'email_subject' => 'New Product Enquiry',
                'email_tpl'     => '',
                'email_message' => ''
            ),
            'delivery_enquiry' => (object) array(
                'slug'          => 'delivery_enquiry',
                'label'         => 'Delivery Enquiries',
                'sub_label'     => '',
                'tip'           => '',
                'email_subject' => 'New Delivery Enquiry',
                'email_tpl'     => '',
                'email_message' => ''
            )
        )
    )
);
