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
        }
    )
);