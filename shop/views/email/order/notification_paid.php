<?php

//  Country
$oCountryModel = nailsFactory('model', 'Country');
$countriesFlat = $oCountryModel->getAllFlat();

?>
<p>
    An order has just been completed with <?=anchor('', APP_NAME)?>.
</p>
<p>
    Full payment has been received and the order should be processed.
</p>
<?php

    if ($order->delivery_type === 'COLLECT') {

        echo '<p class="heads-up warning">';
            echo '<strong>Important:</strong> All items in this order will be collected.';
        echo '</p>';

    } else if ($order->delivery_type === 'DELIVER_COLLECT') {

        echo '<p class="heads-up warning">';
            echo '<strong>Important:</strong> This order will only be partially shipped.';
        echo '</p>';
    }

?>
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
