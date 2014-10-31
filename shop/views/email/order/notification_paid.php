<?php

//  Country
$this->load->model('system/country_model');
$countriesFlat = $this->country_model->get_all_flat();

?>
<p>
    An order has just been completed with <?=anchor('', APP_NAME)?>.
</p>
<p>
    Full payment has been received and the order should be processed.
</p>
<h2>Order Details</h2>
<table class="default-style">
    <tbody>
        <tr>
            <td class="left-header-cell">Reference</td>
            <td>
                <?=$order->ref?>
            </td>
            <td class="text-right">
                <?=anchor('admin/shop/orders/download_invoice/' . $order->id, 'Download Invoice', 'class="button small" style="margin:0;"')?>
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
