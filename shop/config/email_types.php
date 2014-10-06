<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Define email types for this module.
 */

$config['email_types'] = array();

$config['email_types'][0]					= new stdClass();
$config['email_types'][0]->slug				= 'shop_inform_product_available';
$config['email_types'][0]->name				= 'Shop: Variant back in stock';
$config['email_types'][0]->description		= 'Sent to customers who have requested to be informed when a product/variant is back in stock.';
$config['email_types'][0]->template_header	= '';
$config['email_types'][0]->template_body	= 'shop/inventory/inform_product_available';
$config['email_types'][0]->template_footer	= '';
$config['email_types'][0]->default_subject	= '';

$config['email_types'][1]					= new stdClass();
$config['email_types'][1]->slug				= 'notification_paid';
$config['email_types'][1]->name				= 'Shop Manager Order Notification';
$config['email_types'][1]->description		= 'Sent to the shop manager(s) when an order is completed.';
$config['email_types'][1]->template_header	= '';
$config['email_types'][1]->template_body	= 'shop/order/notification_paid';
$config['email_types'][1]->template_footer	= '';
$config['email_types'][1]->default_subject	= '';

$config['email_types'][2]					= new stdClass();
$config['email_types'][2]->slug				= 'notification_partial_payment';
$config['email_types'][2]->name				= 'Shop Manager Order Notification (partial payment)';
$config['email_types'][2]->description		= 'Sent to the shop manager(s) when an order receives a partial payment.';
$config['email_types'][2]->template_header	= '';
$config['email_types'][2]->template_body	= 'shop/order/notification_partial_payment';
$config['email_types'][2]->template_footer	= '';
$config['email_types'][2]->default_subject	= '';

$config['email_types'][3]					= new stdClass();
$config['email_types'][3]->slug				= 'receipt';
$config['email_types'][3]->name				= 'Customer Receipt';
$config['email_types'][3]->description		= 'Sent to the customer when their order is fully paid.';
$config['email_types'][3]->template_header	= '';
$config['email_types'][3]->template_body	= 'shop/order/receipt';
$config['email_types'][3]->template_footer	= '';
$config['email_types'][3]->default_subject	= '';

$config['email_types'][4]					= new stdClass();
$config['email_types'][4]->slug				= 'receipt_partial_payment';
$config['email_types'][4]->name				= 'Customer Receipt (partial payment)';
$config['email_types'][4]->description		= 'Sent to the customer when a partial payment is received for an order';
$config['email_types'][4]->template_header	= '';
$config['email_types'][4]->template_body	= 'shop/order/receipt_partial_payment';
$config['email_types'][4]->template_footer	= '';
$config['email_types'][4]->default_subject	= '';

/* End of file email_types.php */
/* Location: ./module-shop/shop/config/email_types.php */