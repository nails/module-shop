<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * --------------------------------------------------------------------------
 * PRODUCT META
 * --------------------------------------------------------------------------
 *
 * This config file defines any custom meta fields applicable to products.
 *
 * Basic Prototype:
 *
 * $config['shop_product_meta']	= array();
 *
 * $config['shop_product_meta']['COL_NAME'] = array(
 * 	'datatype'		=> 'text|bool|id|date',
 * 	'label'			=> 'Label to render',
 * 	'required'		=> TRUE|FALSE,
 * 	'max_length'	=> int,
 * 	'default'		=> mixed,
 * 	'validation'	=> 'form_validation|rules|',
 * 	'join'			=> array(
 * 		'table'		=> 'table_name',
 * 		'id'		=> 'id_column',
 * 		'name'		=> 'name_column',
 * 		'order_col'	=> 'order_column',
 * 		'order_dir'	-> 'order_dir_column'
 * 	)
 * );
 *
 **/

$config['shop_product_meta'] = array();