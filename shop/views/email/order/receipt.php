<?php

//  Shop's URL
$shopUrl = app_setting('url', 'shop') ? app_setting('url', 'shop') : 'shop/';

//  Country
$this->load->model('country_model');
$countriesFlat = $this->country_model->getAllFlat();

?>
<p>
    Thank you very much for your order with <?=anchor('', APP_NAME)?>.
</p>
<p>
    We have now received full payment for your order, please don't hesitate
    to contact us if you have any questions or concerns.
</p>
<?php

    $address   = array();
    $address[] = app_setting('warehouse_addr_addressee', 'shop');
    $address[] = app_setting('warehouse_addr_line1', 'shop');
    $address[] = app_setting('warehouse_addr_line2', 'shop');
    $address[] = app_setting('warehouse_addr_town', 'shop');
    $address[] = app_setting('warehouse_addr_postcode', 'shop');
    $address[] = app_setting('warehouse_addr_state', 'shop');
    $address[] = app_setting('warehouse_addr_country', 'shop');
    $address   = array_filter($address);

    if ($order->delivery_type === 'COLLECT') {

        if ($address) {

            echo '<div class="heads-up warning">';
                echo '<strong>Important:</strong> All items in this order should be collected from:';
                echo '<hr>' . implode('<br />', $address) . '<br />';
            echo '</div>';

        } else {

            echo '<p class="heads-up warning">';
                echo '<strong>Important:</strong> All items in this order should be collected.';
            echo '</p>';
        }

    } else if ($order->delivery_type === 'DELIVER_COLLECT') {

        if ($address) {

            echo '<div class="heads-up warning">';
                echo '<strong>Important:</strong> This order will only be partially shipped. Collect only items should be collected from:';
                echo '<hr>' . implode('<br />', $address) . '<br />';
            echo '</div>';

        } else {

            echo '<p class="heads-up warning">';
                echo '<strong>Important:</strong> This order will only be partially shipped.';
            echo '</p>';
        }
    }

?>
<h2>Your Order</h2>
<p></p>
<table class="default-style">
    <thead>
        <tr>
            <th class="text-left">Item</th>
            <th>Quantity</th>
            <th>Unit Cost</th>
            <th>Unit Tax</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        <?php

        foreach ($order->items as $item) {

            $borderStyle = !$item->ship_collection_only ? 'border-bottom:1px dashed #EEEEEE;' : '';

            echo '<tr>';
                echo '<td style="' . $borderStyle . '">';
                    echo '<strong>' . $item->product_label . '</strong>';
                    echo $item->product_label != $item->variant_label ? '<br />' . $item->variant_label : '';
                    echo $item->sku ? '<br /><small>' . $item->sku . '</small>' : '';
                echo '</td>';
                echo '<td class="text-center" style="' . $borderStyle . 'vertical-align: middle;">' . $item->quantity . '</td>';
                echo '<td class="text-center" style="' . $borderStyle . 'vertical-align: middle;">' . $item->price->base_formatted->value_ex_tax . '</td>';
                echo '<td class="text-center" style="' . $borderStyle . 'vertical-align: middle;">' . $item->price->base_formatted->value_tax . '</td>';
                echo '<td class="text-center" style="' . $borderStyle . 'vertical-align: middle;">' . $item->price->base_formatted->value_total . '</td>';
            echo '</tr>';

            if ($item->ship_collection_only) {

                echo '<tr>';
                    echo '<td colspan="5" style="border-bottom:1px dashed #EEEEEE;padding-top:0;">';
                        echo '<p class="heads-up warning" style="margin:0;">';
                            echo 'This item is collect only.';
                        echo '</p>';
                    echo '</td>';
                echo '</tr>';
            }
        }

        ?>
        <tr>
            <td style="border-top:1px solid #CCCCCC; background: #EFEFEF" class="text-right" colspan="4">
                Sub Total:<br />Shipping:<br />Tax:<br />Total:
            </td>
            <td style="border-top:1px solid #CCCCCC; background: #EFEFEF" class="text-center">
               <?=$order->totals->base_formatted->item?>
                <br /><?=$order->totals->base_formatted->shipping?>
                <br /><?=$order->totals->base_formatted->tax?>
                <br /><strong><?=$order->totals->base_formatted->grand?></strong>
            </td>
        </tr>
    </tbody>
</table>
<?php

$_invoice_company    = app_setting('invoice_company', 'shop');
$_invoice_address    = app_setting('invoice_address', 'shop');
$_invoice_vat_no     = app_setting('invoice_vat_no', 'shop');
$_invoice_company_no = app_setting('invoice_company_no', 'shop');
$_invoice_footer     = app_setting('invoice_footer', 'shop');

if (!empty($_invoice_company)||!empty($_invoice_address)||!empty($_invoice_vat_no)||!empty($_invoice_company_no)) {

    ?>
    <h2>Other Details</h2>
    <table class="default-style">
        <tbody>
            <?php

            if (!empty($_invoice_company)||!empty($_invoice_address)) {

                ?>
                <tr>
                    <td class="left-header-cell">Merchant</td>
                    <td>
                    <?php

                        echo $_invoice_company  ? '<strong>' . $_invoice_company . '</strong>' : '<strong>' . APP_NAME . '</strong>';
                        echo $_invoice_address  ? '<br />' . nl2br($_invoice_address) . '<br />' : '';
                    ?>
                    </td>
                </tr>
                <?php

            };

            if (!empty($_invoice_vat_no)) {

                ?>
                <tr>
                    <td class="left-header-cell">VAT Number</td>
                    <td>
                    <?php

                        echo $_invoice_vat_no ? $_invoice_vat_no : '';
                    ?>
                    </td>
                </tr>
                <?php

            };

            if (!empty($_invoice_company_no)) {

                ?>
                <tr>
                    <td class="left-header-cell">Company Number</td>
                    <td>
                    <?php
                        echo $_invoice_company_no ? $_invoice_company_no : '';
                    ?>
                    </td>
                </tr>
                <?php

            };

            ?>
        </tbody>
    </table>
    <?php

}

if (!empty($_invoice_footer)) {

    echo '<p>';
    echo '<small>' . $_invoice_footer . '</small>';
    echo '</p>';
}
