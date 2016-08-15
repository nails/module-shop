<?php

return array(
    'models' => array(
        'Voucher' => function () {
            if (class_exists('\App\Shop\Model\Voucher')) {
                return new \App\Shop\Model\Voucher();
            } else {
                return new \Nails\Shop\Model\Voucher();
            }
        },
        'Currency' => function () {
            if (class_exists('\App\Shop\Model\Currency')) {
                return new \App\Shop\Model\Currency();
            } else {
                return new \Nails\Shop\Model\Currency();
            }
        },
        'Page' => function () {
            if (class_exists('\App\Shop\Model\Page')) {
                return new \App\Shop\Model\Page();
            } else {
                return new \Nails\Shop\Model\Page();
            }
        },
        'Skin' => function () {
            if (class_exists('\App\Shop\Model\Skin')) {
                return new \App\Shop\Model\Skin();
            } else {
                return new \Nails\Shop\Model\Skin();
            }
        },
        'Order' => function () {
            //  @todo - move away from CI loading
            get_instance()->load->model('shop/shop_order_model');
            return get_instance()->shop_order_model;
        },
        'OrderLifecycle' => function () {
            if (class_exists('\App\Shop\Model\Order\Lifecycle')) {
                return new \App\Shop\Model\Order\Lifecycle();
            } else {
                return new \Nails\Shop\Model\Order\Lifecycle();
            }
        },
        'Feed' => function () {
            if (class_exists('\App\Shop\Model\Feed')) {
                return new \App\Shop\Model\Feed();
            } else {
                return new \Nails\Shop\Model\Feed();
            }
        },
        'Product' => function () {

            $oCi = get_instance();
            $oCi->load->model('shop/shop_model');
            $oCi->load->model('shop/shop_product_model');

            return $oCi->shop_product_model;
        },
        'ShippingDriver' => function () {

            $oCi = get_instance();
            $oCi->load->model('shop/shop_model');
            $oCi->load->model('shop/shop_shipping_driver_model');

            return $oCi->shop_shipping_driver_model;
        }
    )
);
