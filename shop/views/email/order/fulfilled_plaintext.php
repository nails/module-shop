<?php

//  Country
$oCountryModel = nailsFactory('model', 'Country');
$countriesFlat = $oCountryModel->getAllFlat();

?>
Thank you very much for your order with <?=APP_NAME?>.

Your order has been marked as fulfilled, indicating the dispatch of your goods. If you have any questions, please get in touch using the details below.

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
Tax:       <?=$order->totals->user_formatted->tax . "\n"?>
Total:     <?=$order->totals->user_formatted->grand . "\n"?>
<?php

$invoiceCompany   = app_setting('invoice_company', 'shop');
$invoiceAddress   = app_setting('invoice_address', 'shop');
$invoiceVatNo     = app_setting('invoice_vat_no', 'shop');
$invoiceCompanyNo = app_setting('invoice_company_no', 'shop');
$invoiceFooter    = app_setting('invoice_footer', 'shop');

if (empty($invoiceCompany)||!empty($invoiceAddress)||!empty($invoiceVatNo)||!empty($invoiceCompanyNo)) {

    echo "\n\n" . 'OTHER DETAILS' . "\n";
    echo '-------------' . "\n\n";

    if (!empty($invoiceCompany)||!empty($invoiceAddress)) {

        echo 'MERCHANT' . "\n";
        echo $invoiceCompany  ? $invoiceCompany : APP_NAME;
        echo $invoiceAddress  ? "\n" . $invoiceAddress : '';
        echo "\n\n";
    };

    if (!empty($invoiceVatNo)) {

        echo 'VAT NUMBER' . "\n";
        echo $invoiceVatNo ? $invoiceVatNo : '';
        echo "\n\n";
    };

    if (!empty($invoiceCompanyNo)) {

        echo 'COMPANY NUMBER' . "\n";
        echo $invoiceCompanyNo ? $invoiceCompanyNo : '';
        echo "\n\n";
    };
}

if (!empty($invoiceFooter)) {

    echo "\n\n" . $invoiceFooter;
}
