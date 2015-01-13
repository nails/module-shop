<?php

//  Shop's URL
$shopUrl = app_setting('url', 'shop') ? app_setting('url', 'shop') : 'shop/';

//  Country
$this->load->model('common/country_model');
$countriesFlat = $this->country_model->get_all_flat();

?>
<p>
    Thank you very much for your order with <?=anchor('', APP_NAME)?>.
</p>
<p>
    We have now received full payment for your order, please don't hesitate
    to contact us if you have any questions or concerns.
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
            <td colspan="2"><?=user_datetime($order->created)?></td>
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

            echo '<strong>Delivery</strong>';
            foreach ($order->shipping_address as $key => $line) {

                if (!empty($line)) {

                    if ($key == 'country' && isset($countriesFlat[$line])) {

                        echo '<br />' . $countriesFlat[$line];

                    } else {

                        echo '<br />' . $line;

                    }
                }
            }

            ?>
            </td>
            <td>
            <?php

            echo '<strong>Billing</strong>';
            foreach ($order->billing_address as $key => $line) {

                if (!empty($line)) {

                    if ($key == 'country' && isset($countriesFlat[$line])) {

                        echo '<br />' . $countriesFlat[$line];

                    } else {

                        echo '<br />' . $line;

                    }
                }
            }

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

$_invoice_company       = app_setting('invoice_company', 'shop');
$_invoice_address       = app_setting('invoice_address', 'shop');
$_invoice_vat_no        = app_setting('invoice_vat_no', 'shop');
$_invoice_company_no    = app_setting('invoice_company_no', 'shop');
$_invoice_footer        = app_setting('invoice_footer', 'shop');

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
