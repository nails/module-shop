<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Define app notifications for this module.
 */

$config['notification_definitions'] = array();

$config['notification_definitions']['shop']					= new stdClass();
$config['notification_definitions']['shop']->slug			= 'shop';
$config['notification_definitions']['shop']->label			= 'Shop';
$config['notification_definitions']['shop']->description	= 'Shop related notifications.';
$config['notification_definitions']['shop']->options		= array();

$config['notification_definitions']['shop']->options['orders']					= new stdClass();
$config['notification_definitions']['shop']->options['orders']->slug			= 'orders';
$config['notification_definitions']['shop']->options['orders']->label			= 'Order Notifications';
$config['notification_definitions']['shop']->options['orders']->sub_label		= '';
$config['notification_definitions']['shop']->options['orders']->tip				= '';
$config['notification_definitions']['shop']->options['orders']->email_subject	= 'An order has been placed';
$config['notification_definitions']['shop']->options['orders']->email_tpl		= '';
$config['notification_definitions']['shop']->options['orders']->email_message	= '';

$config['notification_definitions']['shop']->options['product_enquiry']					= new stdClass();
$config['notification_definitions']['shop']->options['product_enquiry']->slug			= 'product_enquiry';
$config['notification_definitions']['shop']->options['product_enquiry']->label			= 'Product Enquiries';
$config['notification_definitions']['shop']->options['product_enquiry']->sub_label		= '';
$config['notification_definitions']['shop']->options['product_enquiry']->tip			= '';
$config['notification_definitions']['shop']->options['product_enquiry']->email_subject	= 'New Product Enquiry';
$config['notification_definitions']['shop']->options['product_enquiry']->email_tpl		= '';
$config['notification_definitions']['shop']->options['product_enquiry']->email_message	= '';

$config['notification_definitions']['shop']->options['delivery_enquiry']				= new stdClass();
$config['notification_definitions']['shop']->options['delivery_enquiry']->slug			= 'delivery_enquiry';
$config['notification_definitions']['shop']->options['delivery_enquiry']->label			= 'Delivery Enquiries';
$config['notification_definitions']['shop']->options['delivery_enquiry']->sub_label		= '';
$config['notification_definitions']['shop']->options['delivery_enquiry']->tip			= '';
$config['notification_definitions']['shop']->options['delivery_enquiry']->email_subject	= 'New Delivery Enquiry';
$config['notification_definitions']['shop']->options['delivery_enquiry']->email_tpl		= '';
$config['notification_definitions']['shop']->options['delivery_enquiry']->email_message	= '';

/* End of file app_notifications.php */
/* Location: ./module-shop/shop/config/app_notifications.php */