UPDATE `nails_shop_order`
SET
`total_base_item` = `total_base_item`*100,
`total_base_shipping` = `total_base_shipping`*100,
`total_base_tax` = `total_base_tax`*100,
`total_base_grand` = `total_base_grand`*100,
`total_user_item` = `total_user_item`*100,
`total_user_shipping` = `total_user_shipping`*100,
`total_user_tax` = `total_user_tax`*100,
`total_user_grand` = `total_user_grand`*100;


ALTER TABLE `nails_shop_order` CHANGE `total_base_item` `total_base_item` INT(11) NOT NULL;
ALTER TABLE `nails_shop_order` CHANGE `total_base_shipping` `total_base_shipping` INT(11) NOT NULL;
ALTER TABLE `nails_shop_order` CHANGE `total_base_tax` `total_base_tax` INT(11) NOT NULL;
ALTER TABLE `nails_shop_order` CHANGE `total_base_grand` `total_base_grand` INT(11) NOT NULL;
ALTER TABLE `nails_shop_order` CHANGE `total_user_item` `total_user_item` INT(11) NOT NULL;
ALTER TABLE `nails_shop_order` CHANGE `total_user_shipping` `total_user_shipping` INT(11) NOT NULL;
ALTER TABLE `nails_shop_order` CHANGE `total_user_tax` `total_user_tax` INT(11) NOT NULL;
ALTER TABLE `nails_shop_order` CHANGE `total_user_grand` `total_user_grand` INT(11) NOT NULL;


-- ------------------------------------------------------------------------


UPDATE `nails_shop_order_payment`
SET
`amount` = `amount`*100,
`amount_base` = `amount_base`*100;


ALTER TABLE `nails_shop_order_payment` CHANGE `amount` `amount` INT(11) NOT NULL;
ALTER TABLE `nails_shop_order_payment` CHANGE `amount_base` `amount_base` INT(11) NOT NULL;


-- ------------------------------------------------------------------------


UPDATE `nails_shop_order_product`
SET
`price_base_value` = `price_base_value`*100,
`price_base_value_inc_tax` = `price_base_value_inc_tax`*100,
`price_base_value_ex_tax` = `price_base_value_ex_tax`*100,
`price_base_value_tax` = `price_base_value_tax`*100,
`price_user_value` = `price_user_value`*100,
`price_user_value_inc_tax` = `price_user_value_inc_tax`*100,
`price_user_value_ex_tax` = `price_user_value_ex_tax`*100,
`price_user_value_tax` = `price_user_value_tax`*100,
`sale_price_base_value` = `sale_price_base_value`*100,
`sale_price_base_value_inc_tax` = `sale_price_base_value_inc_tax`*100,
`sale_price_base_value_ex_tax` = `sale_price_base_value_ex_tax`*100,
`sale_price_base_value_tax` = `sale_price_base_value_tax`*100,
`sale_price_user_value` = `sale_price_user_value`*100,
`sale_price_user_value_inc_tax` = `sale_price_user_value_inc_tax`*100,
`sale_price_user_value_ex_tax` = `sale_price_user_value_ex_tax`*100,
`sale_price_user_value_tax` = `sale_price_user_value_tax`*100;

ALTER TABLE `nails_shop_order_product` CHANGE `price_base_value` `price_base_value` INT(11) NOT NULL;
ALTER TABLE `nails_shop_order_product` CHANGE `price_base_value_inc_tax` `price_base_value_inc_tax` INT(11) NOT NULL;
ALTER TABLE `nails_shop_order_product` CHANGE `price_base_value_ex_tax` `price_base_value_ex_tax` INT(11) NOT NULL;
ALTER TABLE `nails_shop_order_product` CHANGE `price_base_value_tax` `price_base_value_tax` INT(11) NOT NULL;
ALTER TABLE `nails_shop_order_product` CHANGE `price_user_value` `price_user_value` INT(11) NOT NULL;
ALTER TABLE `nails_shop_order_product` CHANGE `price_user_value_inc_tax` `price_user_value_inc_tax` INT(11) NOT NULL;
ALTER TABLE `nails_shop_order_product` CHANGE `price_user_value_ex_tax` `price_user_value_ex_tax` INT(11) NOT NULL;
ALTER TABLE `nails_shop_order_product` CHANGE `price_user_value_tax` `price_user_value_tax` INT(11) NOT NULL;
ALTER TABLE `nails_shop_order_product` CHANGE `sale_price_base_value` `sale_price_base_value` INT(11) NOT NULL;
ALTER TABLE `nails_shop_order_product` CHANGE `sale_price_base_value_inc_tax` `sale_price_base_value_inc_tax` INT(11) NOT NULL;
ALTER TABLE `nails_shop_order_product` CHANGE `sale_price_base_value_ex_tax` `sale_price_base_value_ex_tax` INT(11) NOT NULL;
ALTER TABLE `nails_shop_order_product` CHANGE `sale_price_base_value_tax` `sale_price_base_value_tax` INT(11) NOT NULL;
ALTER TABLE `nails_shop_order_product` CHANGE `sale_price_user_value` `sale_price_user_value` INT(11) NOT NULL;
ALTER TABLE `nails_shop_order_product` CHANGE `sale_price_user_value_inc_tax` `sale_price_user_value_inc_tax` INT(11) NOT NULL;
ALTER TABLE `nails_shop_order_product` CHANGE `sale_price_user_value_ex_tax` `sale_price_user_value_ex_tax` INT(11) NOT NULL;
ALTER TABLE `nails_shop_order_product` CHANGE `sale_price_user_value_tax` `sale_price_user_value_tax` INT(11) NOT NULL;


-- ------------------------------------------------------------------------


UPDATE `nails_shop_product_variation_price`
SET
`price` = `price`*100,
`sale_price` = `sale_price`*100;


ALTER TABLE `nails_shop_product_variation_price` CHANGE `price` `price` INT(11) NOT NULL;
ALTER TABLE `nails_shop_product_variation_price` CHANGE `sale_price` `sale_price` INT(11) NOT NULL;