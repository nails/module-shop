<?php

interface Shop_shipping_driver
{
    /**
     * Caldulcates the cost of shipping, in the shop's base currency. It is important that this return a float.
     * @param  array    $shippableItems An array of all shippable items
     * @param  stdClass $basket         The entire basket object
     * @return float
     */
    public function calculate($shippableItems, $basket);

    /**
     * Specifies any configurable fields
     * @return array
     */
    public function configure();

    /**
     * Specifies any basket-wide shipping options
     * @return array
     */
    public function optionsBasket();

    /**
     * Specified any product-wide shipping options
     * @return array
     */
    public function optionsProduct();

    /**
     * Specifies any variant-specific shipping options
     * @return array
     */
    public function optionsVariant();
}
