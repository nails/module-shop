An order has just been completed with <?=APP_NAME?>.

Full payment has been received and the order should be processed.
<?php

    $address   = array();
    $address[] = appSetting('warehouse_addr_addressee', 'shop');
    $address[] = appSetting('warehouse_addr_line1', 'shop');
    $address[] = appSetting('warehouse_addr_line2', 'shop');
    $address[] = appSetting('warehouse_addr_town', 'shop');
    $address[] = appSetting('warehouse_addr_postcode', 'shop');
    $address[] = appSetting('warehouse_addr_state', 'shop');
    $address[] = appSetting('warehouse_addr_country', 'shop');
    $address   = array_filter($address);

    if ($order->delivery_type === 'COLLECT') {

        if ($address) {

            $box   = array();
            $box[] = 'IMPORTANT: ALL ITEMS IN THIS ORDER SHOULD BE COLLECTED FROM:';
            $box[] = '';

            $box = array_merge($box, $address);

            $box = array_map(function($val) {

                return str_pad($val, 60, ' ');

            }, $box);

            echo "\n" . '+-------------------------------------------------------------+';
            echo "\n| " . implode("|\n| ", $box) . '|';
            echo "\n" . '+-------------------------------------------------------------+';

        } else {

            $box   = array();
            $box[] = 'IMPORTANT: ALL ITEMS IN THIS ORDER SHOULD BE COLLECTED:';
            $box[] = '';

            $box = array_merge($box, $address);

            $box = array_map(function($val) {

                return str_pad($val, 55, ' ');

            }, $box);

            echo "\n" . '+-------------------------------------------------------------+';
            echo "\n| " . implode("|\n| ", $box) . '|';
            echo "\n" . '+-------------------------------------------------------------+';
        }

    } else if ($order->delivery_type === 'DELIVER_COLLECT') {

        if ($address) {

            $box   = array();
            $box[] = 'IMPORTANT: THIS ORDER WILL ONLY BE PARTIALLY SHIPPED.';
            $box[] = 'COLLECT ONLY ITEMS SHOULD BE COLLECTED FROM:';
            $box[] = '';

            $box = array_merge($box, $address);

            $box = array_map(function($val) {

                return str_pad($val, 60, ' ');

            }, $box);

            echo "\n" . '+-------------------------------------------------------------+';
            echo "\n| " . implode("|\n| ", $box) . '|';
            echo "\n" . '+-------------------------------------------------------------+';

        } else {

            $box   = array();
            $box[] = 'IMPORTANT: THIS ORDER WILL ONLY BE PARTIALLY SHIPPED.';
            $box[] = '';

            $box = array_merge($box, $address);

            $box = array_map(function($val) {

                return str_pad($val, 55, ' ');

            }, $box);

            echo "\n" . '+-------------------------------------------------------------+';
            echo "\n| " . implode("|\n| ", $box) . '|';
            echo "\n" . '+-------------------------------------------------------------+';
        }
    }

?>


ORDER DETAILS
----------

REFERENCE
<?=$order->ref?>


PLACED
<?=toUserDatetime($order->created)?>


CUSTOMER
<?php

echo $order->user->first_name . ' ' . $order->user->last_name;
echo "\n" . $order->user->email;
echo "\n" . $order->user->telephone;

?>


SHIPPING ADDRESS
<?php

$address = array(
    $order->shipping_address->line_1,
    $order->shipping_address->line_2,
    $order->shipping_address->town,
    $order->shipping_address->state,
    $order->shipping_address->postcode,
    $order->shipping_address->country->label
);

$address = array_filter($address);
echo implode("\n", $address);

?>


BILLING ADDRESS
<?php

$address = array(
    $order->billing_address->line_1,
    $order->billing_address->line_2,
    $order->billing_address->town,
    $order->billing_address->state,
    $order->billing_address->postcode,
    $order->billing_address->country->label
);

$address = array_filter($address);
echo implode("\n", $address);

if (!empty($order->note)) {

    echo "\n" . 'NOTE' . "\n";
    echo $order->note . "\n";
}

?>



ITEMS
-----

<?php

foreach ($order->items as $item) {

    echo strtoupper($item->product_label) . "\n";
    echo $item->product_label != $item->variant_label ? strtoupper($item->variant_label) : '';
    echo $item->sku ? "\nSKU:       " . $item->sku : '';

    echo "\nQuantity:  " . $item->quantity;
    echo "\nUnit Cost: " . $item->price->base_formatted->value_ex_tax;
    echo "\nUnit Tax:  " . $item->price->base_formatted->value_tax;
    echo "\nTotal:     " . $item->price->base_formatted->value_total;
    echo $item->ship_collection_only ? "\n" . 'THIS ITEM IS COLLECT ONLY' : '';
    echo "\n\n";
}

?>
---

Sub Total: <?=$order->totals->base_formatted->item . "\n"?>
Shipping:  <?=$order->totals->base_formatted->shipping . "\n"?>
Tax:       <?=$order->totals->base_formatted->tax . "\n"?>
Total:     <?=$order->totals->base_formatted->grand . "\n"?>
