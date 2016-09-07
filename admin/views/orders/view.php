<?php

use Nails\Shop\Model\Order\Lifecycle;

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
    data-lifecycle-id="<?=$order->lifecycle_id?>"
    data-delivery-type="<?=$order->delivery_option?>"
    data-num-collect-items="<?=$iNumCollectItems?>"
    data-did-set-lifecycle="<?=!empty($did_set_lifecycle)?>"
>
    <div class="order-lifecycle">
        <?php

        $bReachedCurrent = false;

        foreach ($allLifecycle as $oLifecycle) {

            if ($order->delivery_type === 'COLLECT' && $oLifecycle->id === Lifecycle::ORDER_DISPATCHED) {
                continue;
            } else if ($order->delivery_type === 'DELIVER' && $oLifecycle->id === Lifecycle::ORDER_COLLECTED) {
                continue;
            }

            if ($oLifecycle->id == $order->lifecycle_id) {
                $sStateClass = 'current';
                $bReachedCurrent = true;
            } elseif (!$bReachedCurrent) {
                $sStateClass = 'complete';
            } else {
                $sStateClass = 'incomplete';
            }

            $sUrl = site_url('admin/shop/orders/lifecycle/' . $order->id . '/' . $oLifecycle->id)

            ?>
            <a href="<?=$sUrl?>" data-label="<?=$oLifecycle->label?>" data-will-send-email="<?=$oLifecycle->send_email ? 'true' : 'false'?>" class="order-lifecycle__step order-lifecycle__step--<?=$sStateClass?>" rel="tipsy" title="<?=$oLifecycle->admin_note?>">
                <div class="order-lifecycle__step__line--left"></div>
                <div class="order-lifecycle__step__line--right"></div>
                <div class="order-lifecycle__step__icon">
                    <b class="fa <?=$oLifecycle->admin_icon?>"></b>
                </div>
                <div class="order-lifecycle__step__label">
                    <?=$oLifecycle->label?>
                </div>
            </a>
        <?php
        }

        ?>
    </div>
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
                                <td class="value" style="padding: 0;">
                                    <table style="margin:0;border:0;">
                                        <tr>
                                            <td style="width: 110px;">Items</td>
                                            <td>
                                                <?php

                                                echo $order->totals->base_formatted->tax_item;

                                                ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="width: 110px;">Shipping</td>
                                            <td>
                                                <?php

                                                echo $order->totals->base_formatted->tax_shipping;

                                                ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="width: 110px;">Total</td>
                                            <td>
                                                <?php

                                                echo $order->totals->base_formatted->tax_combined;

                                                ?>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td class="label">Discounts</td>
                                <td class="value" style="padding: 0;">
                                    <table style="margin:0;border:0;">
                                        <tr>
                                            <td style="width: 110px;">Items</td>
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
                                            <td style="width: 110px;">Shipping</td>
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
                                            <td style="width: 110px;">Tax (Items)</td>
                                            <td>
                                                <?php

                                                echo $order->totals->base_formatted->tax_item_discount;

                                                if ($order->currency != $order->base_currency) {

                                                    ?>
                                                    <small>
                                                        User checked out in <?=$order->currency?>:
                                                        <?=$order->totals->user_formatted->tax_item_discount?>
                                                    </small>
                                                    <?php
                                                }

                                                ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="width: 110px;">Tax (Shipping)</td>
                                            <td>
                                                <?php

                                                echo $order->totals->base_formatted->tax_shipping_discount;

                                                if ($order->currency != $order->base_currency) {

                                                    ?>
                                                    <small>
                                                        User checked out in <?=$order->currency?>:
                                                        <?=$order->totals->user_formatted->tax_shipping_discount?>
                                                    </small>
                                                    <?php
                                                }

                                                ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="width: 110px;">Total</td>
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
                                    !empty($order->shipping_address->country) ? $order->shipping_address->country->label : ''
                                );

                                $aAddress = array_filter($aAddress);

                                if (!empty($aAddress)) {
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
                                    <?php
                                } else {
                                    echo '<span class="text-muted">No shipping address supplied.</span>';
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
            <div class="match-height">
                <div class="order-status-container payment-status">
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
                <div class="order-status-container lifecycle-history" id="lifecycle-history">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Status History</strong>
                        </div>
                        <div class="table-responsive">
                            <table class="order-products">
                                <tbody>
                                    <?php

                                    foreach ($lifecycleHistory as $oHistory) {

                                        ?>
                                        <tr>
                                            <td class="text-center"><b class="fa fa-lg <?=$oHistory->lifecycle->admin_icon?>"></b></td>
                                            <td><?=$oHistory->lifecycle->label?></td>
                                            <?=adminHelper('loadUserCell', $oHistory->created_by)?>
                                            <?=adminHelper('loadDateTimeCell', $oHistory->created)?>
                                            <td class="text-center">
                                                <?php

                                                if ($oHistory->email_id) {
                                                    echo anchor('email/view_online/' . $oHistory->email_id, 'View Email', 'class="fancybox btn btn-xs btn-default"');
                                                } else {
                                                    echo '<span class="text-muted">No email sent</span>';
                                                }

                                                ?>
                                            </td>
                                        </tr>
                                        <?php
                                    }

                                    ?>
                                </tbody>
                            </table>
                        </div>
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
