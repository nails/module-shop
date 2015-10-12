<?php

//  Shop's URL
$shopUrl = app_setting('url', 'shop') ? app_setting('url', 'shop') : 'shop/';

//  Country
$oCountryModel = \Nails\Factory::model('Country');
$countriesFlat = $oCountryModel->getAllFlat();

?>
<p>
    Thank you very much for your order with <?=anchor('', APP_NAME)?>.
</p>
<p>
    Your order has been marked as fulfilled, indicating the dispatch of your goods.
    If you have any questions, please get in touch using the details below.
</p>
<h2>Your Order</h2>
<table class="default-style">
    <tbody>
        <tr>
            <td class="left-header-cell">Reference</td>
            <td>
                <?=$order->ref?>
            </td>
            <td class="text-right">
                <?=anchor($shopUrl . 'checkout/invoice/' . $order->ref . '/' . md5($order->code), 'Download Invoice', 'class="button small" style="margin:0;"')?>
            </td>
        </tr>
        <tr>
            <td class="left-header-cell">Placed</td>
            <td colspan="2"><?=toUserDatetime($order->created)?></td>
        </tr>
        <tr>
            <td class="left-header-cell">Customer</td>
            <td colspan="2">
            <?php

            echo $order->user->first_name . ' ' . $order->user->last_name;
            echo '<br />' . mailto($order->user->email);
            echo '<br />' . tel($order->user->telephone);

            ?>
            </td>
        </tr>
        <tr>
            <td class="left-header-cell">Addresses</td>
            <td>
            <?php

            echo '<strong>Delivery</strong><br />';
            $address = array(
                $order->shipping_address->line_1,
                $order->shipping_address->line_2,
                $order->shipping_address->town,
                $order->shipping_address->state,
                $order->shipping_address->postcode,
                $order->shipping_address->country->label
            );

            $address = array_filter($address);
            echo implode('<br />', $address);

            ?>
            </td>
            <td>
            <?php

            echo '<strong>Billing</strong><br />';
            $address = array(
                $order->billing_address->line_1,
                $order->billing_address->line_2,
                $order->billing_address->town,
                $order->billing_address->state,
                $order->billing_address->postcode,
                $order->billing_address->country->label
            );

            $address = array_filter($address);
            echo implode('<br />', $address);

            ?>
            </td>
        </tr>
        <?php if (!empty($order->note)) { ?>
        <tr>
            <td class="left-header-cell">Note</td>
            <td colspan="2"><?=$order->note?></td>
        </tr>
        <?php } ?>
    </tbody>
</table>
<p></p>
<table class="default-style">
    <thead>
        <tr>
            <th class="text-left">Item</th>
            <th>Quantity</th>
            <th>Unit Cost</th>
        </tr>
    </thead>
    <tbody>
        <?php

        foreach ($order->items as $item) {

            echo '<tr>';
            echo '<td>';
                echo '<strong>' . $item->product_label . '</strong>';
                echo $item->product_label != $item->variant_label ? '<br />' . $item->variant_label : '';
                echo $item->sku ? '<br /><small>' . $item->sku . '</small>' : '';
            echo '</td>';
            echo '<td class="text-center">' . $item->quantity . '</td>';
            echo '<td class="text-center">' . $item->price->user_formatted->value_inc_tax . '</td>';
            echo '</tr>';
        }

        ?>
        <tr>
            <td style="border-top:1px solid #CCCCCC; background: #EFEFEF" class="text-right" colspan="2">
                Sub Total:<br />Shipping:<br />Tax:<br />Total:
            </td>
            <td style="border-top:1px solid #CCCCCC; background: #EFEFEF" class="text-center">
               <?=$order->totals->user_formatted->item?>
                <br /><?=$order->totals->user_formatted->shipping?>
                <br /><?=$order->totals->user_formatted->tax?>
                <br /><strong><?=$order->totals->user_formatted->grand?></strong>
            </td>
        </tr>
    </tbody>
</table>
<?php

$invoiceCompany   = app_setting('invoice_company', 'shop');
$invoiceAddress   = app_setting('invoice_address', 'shop');
$invoiceVatNo     = app_setting('invoice_vat_no', 'shop');
$invoiceCompanyNo = app_setting('invoice_company_no', 'shop');
$invoiceFooter    = app_setting('invoice_footer', 'shop');

if (!empty($invoiceCompany)||!empty($invoiceAddress)||!empty($invoiceVatNo)||!empty($invoiceCompanyNo)) {

    ?>
    <h2>Other Details</h2>
    <table class="default-style">
        <tbody>
        <?php

            if (!empty($invoiceCompany)||!empty($invoiceAddress)) {

                ?>
                <tr>
                    <td class="left-header-cell">Merchant</td>
                    <td>
                    <?php

                        echo $invoiceCompany  ? '<strong>' . $invoiceCompany . '</strong>' : '<strong>' . APP_NAME . '</strong>';
                        echo $invoiceAddress  ? '<br />' . nl2br($invoiceAddress) . '<br />' : '';

                    ?>
                    </td>
                </tr>
                <?php

            }

            if (!empty($invoiceVatNo)) {

                ?>
                <tr>
                    <td class="left-header-cell">VAT Number</td>
                    <td>
                    <?php

                        echo $invoiceVatNo ? $invoiceVatNo : '';
                    ?>
                    </td>
                </tr>
                <?php

            }

            if (!empty($invoiceCompanyNo)) {

                ?>
                <tr>
                    <td class="left-header-cell">Company Number</td>
                    <td>
                    <?php
                        echo $invoiceCompanyNo ? $invoiceCompanyNo : '';
                    ?>
                    </td>
                </tr>
                <?php

            }

        ?>
        </tbody>
    </table>
    <?php

}

if (!empty($invoiceFooter)) {

    echo '<p>';
    echo '<small>' . $invoiceFooter . '</small>';
    echo '</p>';
}
