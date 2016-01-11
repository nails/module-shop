<?php

$iNumCollectItems = 0;

foreach ($order->items as $oItem) {
    if ($oItem->ship_collection_only) {
        $iNumCollectItems++;
    }
}

?>
<div
    id="order"
    class="group-shop orders single"
    data-fulfilment-status="<?=$order->fulfilment_status?>"
    data-delivery-type="<?=$order->delivery_option?>"
    data-num-collect-items="<?=$iNumCollectItems?>"
>
    <div class="row col-3-container">
        <div class="col-md-4">
            <div class="panel panel-default match-height">
                <div class="panel-heading">
                    <strong>Order Details</strong>
                </div>
                <div class="table-responsive">
                    <table>
                        <tbody>
                            <tr>
                                <td class="label">ID</td>
                                <td class="value"><?=$order->id?></td>
                            </tr>
                            <tr>
                                <td class="label">Ref</td>
                                <td class="value"><?=$order->ref?></td>
                            </tr>
                            <tr>
                                <td class="label">Created</td>
                                <?=adminHelper('loadDatetimeCell', $order->created)?>
                            </tr>
                            <tr>
                                <td class="label">Modified</td>
                                <?=adminHelper('loadDatetimeCell', $order->modified)?>
                            </tr>
                            <tr>
                                <td class="label">Voucher</td>
                                <td class="value">
                                <?php

                                if (empty($order->voucher)) {

                                    echo '<span class="text-muted">No Voucher Used</span>';

                                } else {


                                    echo $order->voucher->label;
                                    echo '<small>' . $order->voucher->code . '</small>';
                                }

                                ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="label">Sub Total</td>
                                <td class="value">
                                <?php

                                echo $order->totals->base_formatted->item;

                                if ($order->currency != $order->base_currency) {

                                    ?>
                                    <small>
                                        User checked out in <?=$order->currency?>:
                                        <?=$order->totals->user_formatted->item?>
                                    </small>
                                    <?php
                                }

                                ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="label">Shipping</td>
                                <td class="value">
                                <?php

                                echo $order->totals->base_formatted->shipping;

                                if ($order->currency != $order->base_currency) {

                                    ?>
                                    <small>
                                        User checked out in <?=$order->currency?>:
                                        <?=$order->totals->user_formatted->shipping?>
                                    </small>
                                    <?php
                                }

                                ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="label">Tax</td>
                                <td class="value">
                                <?php

                                echo $order->totals->base_formatted->tax;

                                if ($order->currency != $order->base_currency) {

                                    ?>
                                    <small>
                                        User checked out in <?=$order->currency?>:
                                        <?=$order->totals->user_formatted->tax?>
                                    </small>
                                    <?php
                                }

                                ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="label">Discounts</td>
                                <td class="value" style="padding: 0;">
                                    <table>
                                        <tr>
                                            <td style="width: 80px;">Items</td>
                                            <td>
                                                <?php

                                                echo $order->totals->base_formatted->item_discount;

                                                if ($order->currency != $order->base_currency) {

                                                    ?>
                                                    <small>
                                                        User checked out in <?=$order->currency?>:
                                                        <?=$order->totals->user_formatted->item_discount?>
                                                    </small>
                                                    <?php
                                                }

                                                ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="width: 80px;">Tax</td>
                                            <td>
                                                <?php

                                                echo $order->totals->base_formatted->tax_discount;

                                                if ($order->currency != $order->base_currency) {

                                                    ?>
                                                    <small>
                                                        User checked out in <?=$order->currency?>:
                                                        <?=$order->totals->user_formatted->tax_discount?>
                                                    </small>
                                                    <?php
                                                }

                                                ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="width: 80px;">Shipping</td>
                                            <td>
                                                <?php

                                                echo $order->totals->base_formatted->shipping_discount;

                                                if ($order->currency != $order->base_currency) {

                                                    ?>
                                                    <small>
                                                        User checked out in <?=$order->currency?>:
                                                        <?=$order->totals->user_formatted->shipping_discount?>
                                                    </small>
                                                    <?php
                                                }

                                                ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="width: 80px;">Total</td>
                                            <td>
                                                <?php

                                                echo $order->totals->base_formatted->grand_discount;

                                                if ($order->currency != $order->base_currency) {

                                                    ?>
                                                    <small>
                                                        User checked out in <?=$order->currency?>:
                                                        <?=$order->totals->user_formatted->grand_discount?>
                                                    </small>
                                                    <?php
                                                }

                                                ?>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td class="label">Total</td>
                                <td class="value">
                                <?php

                                echo $order->totals->base_formatted->grand;

                                if ($order->currency != $order->base_currency) {

                                    ?>
                                    <small>
                                        User checked out in <?=$order->currency?>:
                                        <?=$order->totals->user_formatted->grand?>
                                    </small>
                                    <?php
                                }

                                ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="label">Note</td>
                                <td class="value">
                                <?php

                                if (empty($order->note)) {

                                    echo '<span class="text-muted">No Note Specified</span>';

                                } else {

                                    echo $order->note;
                                }

                                ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="panel panel-default match-height">
                <div class="panel-heading">
                    <strong>Customer &amp; Delivery Details</strong>
                </div>
                <div class="table-responsive">
                    <table>
                        <tbody>
                            <tr>
                                <td class="label">Name &amp; Email</td>
                                <?php echo adminHelper('loadUserCell', $order->user); ?>
                            </tr>
                            <tr>
                                <td class="label">Telephone</td>
                                <td class="value">
                                <?php

                                if ($order->user->telephone) {

                                    echo tel($order->user->telephone);

                                } else {

                                    echo '<span class="text-muted">Not supplied</span>';
                                }

                                ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="label">Billing<br />Address</td>
                                <td class="value">
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

                                    echo implode('<br />', $aAddress);

                                    ?>
                                    <small>
                                        <?php

                                        echo anchor(
                                            'https://www.google.com/maps/?q=' . urlencode(implode(', ', $aAddress)),
                                            '<b class="fa fa-map-marker"></b> Map',
                                            'target="_blank"'
                                        );

                                        ?>
                                    </small>
                                </td>
                            </tr>
                            <tr>
                                <td class="label">Shipping<br />Option</td>
                                <td class="value">
                                    <?php

                                    if (!empty($order->shipping_option['label'])) {

                                        echo $order->shipping_option['label'];

                                    } else {

                                        echo $order->delivery_option;
                                    }

                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="label">Shipping<br />Address</td>
                                <td class="value">
                                    <?php

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

                                    ?>
                                    <small>
                                        <?php

                                        echo anchor(
                                            'https://www.google.com/maps/?q=' . urlencode(implode(', ', $aAddress)),
                                            '<b class="fa fa-map-marker"></b> Map',
                                            'target="_blank"'
                                        );

                                        ?>
                                    </small>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="match-height">
                <div class="order-status-container">
                    <div class="order-status <?=strtolower($order->status)?>">
                    <?php

                    switch ($order->status) {

                        case 'UNPAID' :
                            ?>
                            <h1>
                                <b class="fa fa-exclamation-triangle"></b>
                                Unpaid
                            </h1>
                            <p>
                                The user was sent to the payment gateway but has not yet completed payment.
                            </p>
                            <?php

                            if (time() - strtotime($order->created) > 3600) {

                                ?>
                                <p>
                                    <small>
                                        This order looks to be quite old now, it's likely the
                                        user abandoned checkout.
                                    </small>
                                </p>
                                <?php
                            }
                            break;

                        case 'PAID':
                            ?>
                            <h1>
                                <b class="fa fa-check-circle-o"></b>
                                Paid
                            </h1>
                            <p>
                                This order has been fully paid.
                            </p>
                            <?php
                            break;

                        case 'ABANDONED':
                            ?>
                            <h1>
                                <b class="fa fa-times-circle"></b>
                                Abandoned
                            </h1>
                            <p>
                                This order was abandoned by the user prior to reaching checkout.
                            </p>
                            <?php
                            break;

                        case 'CANCELLED':
                            ?>
                            <h1>
                                <b class="fa fa-times-circle"></b>
                                Cancelled
                            </h1>
                            <p>
                                This order has been marked as cancelled.
                            </p>
                            <?php
                            break;

                        case 'FAILED':
                            ?>
                            <h1>
                                <b class="fa fa-exclamation-triangle"></b>
                                Failed
                            </h1>
                            <p>
                                There was an issue processing this order. Failure details of the failure may
                                have been provided from the payment gateway, if so these will be shown below.
                            </p>
                            <?php
                            // @todo: Show failure details
                            break;

                        case 'PENDING':
                            ?>
                            <h1>
                                <b class="fa fa-clock-o fa-spin"></b>
                                Pending
                            </h1>
                            <p>
                                This order is pending action, details and further options may be shown below.
                            </p>
                            <?php
                            // @todo: Show pending reasons/actions
                            break;

                        default:
                            $sStatus = ucwords(strtolower($order->status));
                            ?>
                            <h1>
                                <?=$sStatus?>
                            </h1>
                            <p>
                                "<?=$sStatus?>" is not an order status I understand, there may be a problem.
                            </p>
                            <?php
                            break;
                    }

                    ?>
                    </div>
                </div>
                <div class="order-status-container">
                    <div class="order-status <?=strtolower($order->fulfilment_status)?>">
                    <?php

                    $sVerbFulfilled   = $order->delivery_option !== 'COLLECTION' ? 'fulfilled' : 'collected';
                    $sVerbUnfulfilled = $order->delivery_option !== 'COLLECTION' ? 'unfulfilled' : 'uncollected';

                    $sButtonUrlUnfulfill   = 'admin/shop/orders/unfulfil/' . $order->id;
                    $sButtonLabelUnfulfill = 'Mark ' . ucfirst($sVerbUnfulfilled);
                    $sButtonUrlPack        = 'admin/shop/orders/pack/' . $order->id;
                    $sButtonLabelPack      = 'Mark Packed';
                    $sButtonUrlFulfill     = 'admin/shop/orders/fulfil/' . $order->id;
                    $sButtonLabelFulfill   = 'Mark ' . ucfirst($sVerbFulfilled);

                    switch ($order->fulfilment_status) {

                        case 'FULFILLED':
                            ?>
                            <h1>
                                <b class="fa fa-truck"></b>
                                <?=strtoupper($sVerbFulfilled)?>
                            </h1>
                            <p>
                                This order has been <?=$sVerbFulfilled?>, no further action is nessecary.
                            </p>
                            <p>
                                <?=anchor($sButtonUrlUnfulfill, $sButtonLabelUnfulfill, 'class="btn btn-warning"')?>
                                <?=anchor($sButtonUrlPack, $sButtonLabelPack, 'class="btn btn-info"')?>
                            </p>
                            <?php
                            break;

                        case 'PACKED':
                            ?>
                            <h1>
                                <b class="fa fa-cube"></b>
                                PACKED
                            </h1>
                            <p>
                                This order has <strong>not</strong> been <?=$sVerbFulfilled?>.
                            </p>
                            <p>
                                <?=anchor($sButtonUrlUnfulfill, $sButtonLabelUnfulfill, 'class="btn btn-warning"')?>
                                <?=anchor($sButtonUrlFulfill, $sButtonLabelFulfill, 'class="btn btn-success"')?>
                            </p>
                            <?php
                            break;

                        case 'UNFULFILLED':
                            ?>
                            <h1>
                                <b class="fa fa-clock-o"></b>
                                <?=strtoupper($sVerbUnfulfilled)?>
                            </h1>
                            <p>
                                This order has <strong>not</strong> been <?=$sVerbFulfilled?>.
                            </p>
                            <p>
                                <?=anchor($sButtonUrlPack, $sButtonLabelPack, 'class="btn btn-info"')?>
                                <?=anchor($sButtonUrlFulfill, $sButtonLabelFulfill, 'class="btn btn-success"')?>
                            </p>
                            <?php
                            break;

                        default:
                            $sStatus = ucwords(strtolower($order->fulfilment_status));
                            ?>
                            <h1>
                                <?=$sStatus?>
                            </h1>
                            <p>
                                "<?=$sStatus?>" is not a fulfilment status I understand, there may be a problem.
                            </p>
                            <?php
                            break;
                    }

                    ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <fieldset class="notcollapsable">
        <legend>Products</legend>
        <div class="table-responsive">
            <table class="order-products">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Item</th>
                        <th>Type</th>
                        <th class="text-center">Quantity</th>
                        <th class="text-center">Unit Cost</th>
                        <th class="text-center">Tax per Unit</th>
                        <th class="text-center">Total</th>
                        <th class="text-center">Refunded</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php

                if (!empty($order->items)) {

                    foreach ($order->items as $item) {

                        ?>
                        <tr>
                            <td>
                                <?=$item->sku?>
                            </td>
                            <td>
                                <?php

                                echo $item->product_label;
                                echo $item->variant_label != $item->product_label ? '<br /><small>' . $item->variant_label . '</small>' : '';

                                if ($item->ship_collection_only) {

                                    ?>
                                    <p class="alert alert-warning skinny">
                                        <strong>Note:</strong> This item is for collection only.
                                    </p>
                                    <?php
                                }

                                ?>
                            </td>
                            <td>
                                <?=$item->type->label?>
                            </td>
                            <td class="text-center">
                                <?=$item->quantity?>
                            </td>
                            <td class="text-center">
                                <?=$item->price->base_formatted->value_ex_tax?>
                            </td>
                            <td class="text-center">
                                <?=$item->price->base_formatted->value_tax?>
                            </td>
                            <td class="text-center">
                                <?=$item->price->base_formatted->item_total?>
                            </td>
                            <td class="text-center">
                                <?=$item->refunded ? 'Refunded ' . $item->refunded_date : 'No'?>
                            </td>
                            <td class="text-center">
                                <?=anchor('', 'Refund', 'class="btn btn-xs btn-primary todo"')?>
                            </td>
                        </tr>
                        <?php
                    }

                } else {

                    ?>
                    <tr>
                        <td colspan="9" class="no-data">
                            No Items
                        </td>
                    </tr>
                    <?php
                }

                ?>
                </tbody>
            </table>
        </div>
    </fieldset>
    <fieldset class="notcollapsable">
        <legend>Payments</legend>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Payment Gateway</th>
                        <th>Transaction ID</th>
                        <th>Amount</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                <?php

                if (!empty($payments)) {

                    $oCurrencyModel = nailsFactory('model', 'Currency', 'nailsapp/module-shop');

                    foreach ($payments as $payment) {

                        ?>
                        <tr>
                            <td>
                                <?=$payment->payment_gateway?>
                            </td>
                            <td>
                                <?=$payment->transaction_id?>
                            </td>
                            <td>
                                <?=$oCurrencyModel->formatBase($payment->amount_base)?>
                            </td>
                            <?=adminHelper('loadDatetimeCell', $payment->created)?>
                        </tr>
                        <?php

                    }

                } else {

                    ?>
                    <tr>
                        <td colspan="4" class="no-data">
                            No Payments
                        </td>
                    </tr>
                    <?php

                }

                ?>
                </tbody>
            </table>
        </div>
    </fieldset>
</div>