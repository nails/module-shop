YOUR ORDER
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


<?php

if ($order->requires_shipping) {

    if (!empty($order->shipping_option->label)) {
        echo "SHIPPING OPTION\n";
        echo $order->shipping_option->label . "\n\n";
    }

    $aAddress = array(
        $order->shipping_address->line_1,
        $order->shipping_address->line_2,
        $order->shipping_address->town,
        $order->shipping_address->state,
        $order->shipping_address->postcode,
        $order->shipping_address->country->label
    );

    $aAddress = array_filter($aAddress);
    echo "SHIPPING ADDRESS\n";
    echo implode("\n", $aAddress);

}

?>


BILLING ADDRESS
<?php

$aAddress = array(
    $order->billing_address->line_1,
    $order->billing_address->line_2,
    $order->billing_address->town,
    $order->billing_address->state,
    $order->billing_address->postcode,
    $order->billing_address->country->label
);

$aAddress = array_filter($aAddress);
echo implode("\n", $aAddress);

?>

<?php

if (!empty($order->note)) {

    echo 'NOTE' . "\n";
    echo $order->note . "\n";
}

?>

<?php

if (!empty($order->voucher->id)) {

    echo 'VOUCHER' . "\n";
    echo $order->voucher->code . ' - ' . $order->voucher->label . "\n";
}

?>


YOUR ITEMS
----------

<?php

foreach ($order->items as $item) {

    echo strtoupper($item->product_label) . "\n";
    echo $item->product_label != $item->variant_label ? strtoupper($item->variant_label) : '';
    echo $item->sku ? "\nSKU:       " . $item->sku : '';

    echo "\nQuantity:  " . $item->quantity;
    echo "\nUnit Cost: " . $item->price->user_formatted->value_inc_tax;
    echo "\n\n";

    echo strtoupper($item->product_label) . "\n";
    echo $item->product_label != $item->variant_label ? strtoupper($item->variant_label) : '';
    echo $item->sku ? "\nSKU:       " . $item->sku : '';

    echo "\nQuantity:  " . $item->quantity;
    echo "\nUnit Cost: " . $item->price->user_formatted->value_inc_tax;
    echo "\n\n";

}

?>
---

Sub Total: <?=$order->totals->user_formatted->item . "\n"?>
Shipping:  <?=$order->totals->user_formatted->shipping . "\n"?>
Tax:       <?=$order->totals->user_formatted->tax_combined . "\n"?>
<?=$order->totals->base->grand_discount ? 'Discount: -' . $order->totals->base_formatted->grand_discount . "\n" : ''?>
Total:     <?=$order->totals->user_formatted->grand . "\n"?>
