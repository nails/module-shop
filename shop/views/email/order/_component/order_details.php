<?php

$sShopUrl = appSetting('url', 'shop') ? appSetting('url', 'shop') : 'shop/';

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
                <?php

                echo anchor(
                    $sShopUrl . 'checkout/invoice/' . $order->ref . '/' . md5($order->code),
                    'Download Invoice',
                    'class="button small" style="margin:0;"'
                );

                ?>
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

            $aCustomer = array(
                $order->user->first_name . ' ' . $order->user->last_name,
                mailto($order->user->email),
                tel($order->user->telephone)
            );

            $aCustomer = array_filter($aCustomer);
            echo implode('<br />', $aCustomer);

            ?>
            </td>
        </tr>
        <tr>
            <td class="left-header-cell">Address</td>
            <td>
            <?php

            echo '<strong>Billing</strong><br />';
            $aAddress = array(
                $order->billing_address->line_1,
                $order->billing_address->line_2,
                $order->billing_address->town,
                $order->billing_address->state,
                $order->billing_address->postcode,
                $order->billing_address->country->label
            );

            $aAddress = array_filter($aAddress);
            echo implode('<br />', $aAddress);

            if ($order->requires_shipping) {
                echo '</td><td>';
                echo '<strong>Delivery</strong><br />';
                $aAddress = array(
                    $order->shipping_address->line_1,
                    $order->shipping_address->line_2,
                    $order->shipping_address->town,
                    $order->shipping_address->state,
                    $order->shipping_address->postcode,
                    $order->shipping_address->country->label
                );

                $aAddress = array_filter($aAddress);
                echo implode('<br />', $aAddress);
            }

            ?>
            </td>
        </tr>
        <tr>
            <td class="left-header-cell">Shipping Option</td>
            <td colspan="2"><?=$order->shipping_option->label?></td>
        </tr>
        <?php

        if (!empty($order->note)) {

            ?>
            <tr>
                <td class="left-header-cell">Note</td>
                <td colspan="2"><?=$order->note?></td>
            </tr>
            <?php
        }

        if (!empty($order->voucher->id)) {

            ?>
            <tr>
                <td class="left-header-cell">Voucher</td>
                <td colspan="2">
                    <?=$order->voucher->code?> - <?=$order->voucher->label?>
                </td>
            </tr>
            <?php
        }

        ?>
    </tbody>
</table>
<h2>Order Items</h2>
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

            ?>
            <tr>
                <td style="<?=$borderStyle?>">
                    <strong><?=$item->product_label?></strong>
                    <?php

                    echo $item->product_label != $item->variant_label ? '<br />' . $item->variant_label : '';
                    echo $item->sku ? '<br /><small>' . $item->sku . '</small>' : '';

                    ?>
                </td>
                <td class="text-center" style="<?=$borderStyle?>vertical-align: middle;"><?=$item->quantity?></td>
                <td class="text-center" style="<?=$borderStyle?>vertical-align: middle;"><?=$item->price->base_formatted->value_ex_tax?></td>
                <td class="text-center" style="<?=$borderStyle?>vertical-align: middle;"><?=$item->price->base_formatted->value_tax?></td>
                <td class="text-center" style="<?=$borderStyle?>vertical-align: middle;"><?=$item->price->base_formatted->item_total?></td>
            </tr>
            <?php

            if ($item->ship_collection_only) {

                ?>
                <tr>
                    <td colspan="5" style="border-bottom:1px dashed #EEEEEE;padding-top:0;">
                        <p class="heads-up warning" style="margin:0;">
                            This item is collect only.
                        </p>
                    </td>
                </tr>
                <?php
            }
        }

        ?>
        <tr>
            <td style="border-top:1px solid #CCCCCC; background: #EFEFEF" class="text-right" colspan="4">
                Sub Total:
                <?php

                if (!empty($order->totals->base->grand_discount)) {

                    echo '<br />Discount:';
                }

                ?>
                <br />Shipping:
                <br />Tax:
                <br />Total:
            </td>
            <td style="border-top:1px solid #CCCCCC; background: #EFEFEF" class="text-center">
                <?=$order->totals->base_formatted->item?>
                <?php

                if (!empty($order->totals->base->grand_discount)) {

                    echo '<br />-' . $order->totals->base_formatted->grand_discount;
                }

                ?>
                <br /><?=$order->totals->base_formatted->shipping?>
                <br /><?=$order->totals->base_formatted->tax?>
                <br /><strong><?=$order->totals->base_formatted->grand?></strong>
            </td>
        </tr>
    </tbody>
</table>