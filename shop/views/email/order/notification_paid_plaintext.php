An order has just been completed with <?=APP_NAME?>.

Full payment has been received and the order should be processed.


ORDER DETAILS
----------

REFERENCE
<?=$order->ref?>


PLACED
<?=userDatetime($order->created)?>


CUSTOMER
<?php

echo $order->user->first_name . ' ' . $order->user->last_name;
echo "\n" . $order->user->email;
echo "\n" . $order->user->telephone;

?>


SHIPPING ADDRESS
<?php

foreach ($order->shipping_address as $line) {
    if (!empty($line)) {
        echo $line . "\n";
    }
}

?>

BILLING ADDRESS
<?php

foreach ($order->billing_address as $line) {
    if (!empty($line)) {
        echo $line . "\n";
    }
}

if (!empty($order->note)) {

    echo "\n" . 'NOTE' . "\n";
    echo $order->note . "\n";
}

?>


ITEMS
-----

<?php

foreach ($order->items as $item) {

    echo strtoupper( $item->product_label ) . "\n";
    echo $item->product_label != $item->variant_label ? strtoupper( $item->variant_label ) : '';
    echo $item->sku ? "\nSKU:       " . $item->sku : '';

    echo "\nQuantity:  " . $item->quantity;
    echo "\nUnit Cost: " . $item->price->user_formatted->value_inc_tax;
    echo "\n\n";

    echo strtoupper( $item->product_label ) . "\n";
    echo $item->product_label != $item->variant_label ? strtoupper( $item->variant_label ) : '';
    echo $item->sku ? "\nSKU:       " . $item->sku : '';

    echo "\nQuantity:  " . $item->quantity;
    echo "\nUnit Cost: " . $item->price->user_formatted->value_inc_tax;
    echo "\n\n";

}

?>
---

Sub Total: <?=$order->totals->user_formatted->item . "\n"?>
Shipping:  <?=$order->totals->user_formatted->shipping . "\n"?>
Tax:       <?=$order->totals->user_formatted->tax . "\n"?>
Total:     <?=$order->totals->user_formatted->grand . "\n"?>
