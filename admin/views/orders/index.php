<div class="group-shop orders browse">
    <p>
        Browse all orders which have been processed by the site from this page.
    </p>
    <?php

        echo \Nails\Admin\Helper::loadSearch($search);
        echo \Nails\Admin\Helper::loadPagination($pagination);

    ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th class="checkAll">
                    <?php

                        if (!empty($orders)) {

                            echo '<input type="checkbox" id="toggle-all" />';
                        }

                    ?>
                    </th>
                    <th class="ref">Ref</th>
                    <th class="datetime">Placed</th>
                    <th class="user">Customer</th>
                    <th class="value">Items</th>
                    <th class="value">Tax</th>
                    <th class="value">Shipping</th>
                    <th class="value">Total</th>
                    <th class="status">Status</th>
                    <th class="fulfilment">Fulfilled</th>
                    <th class="actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php

                    if ($orders) {

                        foreach ($orders as $order) {

                            ?>
                            <tr id="order-<?=$order->id?>">
                                <td class="checkAll">
                                    <input type="checkbox" class="batch-checkbox" value="<?=$order->id?>" />
                                </td>
                                <td class="ref"><?=$order->ref?></td>
                                <?php

                                    echo \Nails\Admin\Helper::loadDatetimeCell($order->created);
                                    echo \Nails\Admin\Helper::loadUserCell($order->user);

                                ?>
                                <td class="value">
                                <?php

                                    echo $order->totals->base_formatted->item;

                                    if ($order->currency !== $order->base_currency) {

                                        echo '<small>' . $order->totals->user_formatted->item . '</small>';

                                    }

                                ?>
                                </td>
                                <td class="value">
                                <?php

                                    echo $order->totals->base_formatted->tax;

                                    if ($order->currency !== $order->base_currency) {

                                        echo '<small>' . $order->totals->user_formatted->tax . '</small>';

                                    }

                                ?>
                                </td>
                                <td class="value">
                                <?php

                                    echo $order->totals->base_formatted->shipping;

                                    if ($order->currency !== $order->base_currency) {

                                        echo '<small>' . $order->totals->user_formatted->shipping . '</small>';

                                    }

                                ?>
                                </td>
                                <td class="value">
                                <?php

                                    echo $order->totals->base_formatted->grand;

                                    if ($order->currency !== $order->base_currency) {

                                        echo '<small>' . $order->totals->user_formatted->grand . '</small>';

                                    }

                                ?>
                                </td>
                                <?php


                                    switch ($order->status) {

                                        case 'UNPAID':
                                            $status = 'error';
                                            break;

                                        case 'PAID':
                                            $status = 'success';
                                            break;

                                        case 'ABANDONED':
                                            $status = 'message';
                                            break;

                                        case 'CANCELLED':
                                            $status = 'message';
                                            break;

                                        case 'FAILED':
                                            $status = 'error';
                                            break;

                                        case 'PENDING':
                                            $status = 'notice';
                                           break;

                                        default:
                                            $status = '';
                                            break;
                                    }

                                    echo '<td class="status ' . $status . '">';
                                        echo $order->status;
                                    echo '</td>';

                                    $boolValue = $order->fulfilment_status == 'FULFILLED';
                                    echo \Nails\Admin\Helper::loadBoolCell($boolValue, $order->fulfilled);

                                ?>
                                <td class="actions">
                                    <?php

                                        //  Render buttons
                                        $_buttons = array();

                                        // --------------------------------------------------------------------------

                                        if (userHasPermission('admin:shop:orders:view')) {

                                            $_buttons[] = anchor(
                                                'admin/shop/orders/view/' . $order->id,
                                                lang('action_view'),
                                                'class="awesome green small"'
                                            );
                                            $_buttons[] = anchor(
                                                'admin/shop/orders/download_invoice/' . $order->id,
                                                'Download',
                                                'class="awesome small"'
                                            );
                                        }

                                        // --------------------------------------------------------------------------

                                        // if (userHasPermission('admin:shop:orders:reprocess')) {

                                        //      $_buttons[] = anchor(
                                        //         'admin/shop/orders/reprocess/' . $order->id,
                                        //         'Process',
                                        //         'class="awesome small orange confirm" data-title="Are you sure?" data-body="Processing the order again may result in multiple dispatch of items."'
                                        //     );
                                        // }

                                        // --------------------------------------------------------------------------

                                        if ($_buttons) {

                                            foreach ($_buttons aS $button) {

                                                echo $button;
                                            }

                                        } else {

                                            echo '<span class="blank">There are no actions you can perform on this item.</span>';
                                        }

                                    ?>
                                </td>
                            </tr>
                            <?php

                        }

                    } else {

                        ?>
                        <tr>
                            <td colspan="11" class="no-data">
                                <p>No Orders found</p>
                            </td>
                        </tr>
                        <?php
                    }

                ?>
            </tbody>
        </table>
        <?php

            if ($orders) {

                $_options                     = array();
                $_options['']                 = 'Choose';
                $_options['mark-fulfilled']   = 'Mark Fulfilled';
                $_options['mark-unfulfilled'] = 'Mark Unfulfilled';
                $_options['mark-cancelled']   = 'Mark Cancelled';
                $_options['download']         = 'Download';

                echo '<div class="panel" id="batch-action">';
                    echo 'With checked: ';
                    echo form_dropdown('', $_options, null);
                    echo ' <a href="#" class="awesome small">Go</a>';
                echo '</div>';
            }

        ?>
    </div>
    <?php

        echo \Nails\Admin\Helper::loadPagination($pagination);

    ?>
</div>
<script type="text/javascript">

    function mark_fulfilled(order_id)
    {
        $('#order-' + order_id).find('td.fulfilment').removeClass('no').addClass('yes').text('<?=lang('yes')?>');
    }

    function mark_unfulfilled(order_id)
    {
        $('#order-' + order_id).find('td.fulfilment').removeClass('yes').addClass('no').text('<?=lang('no')?>');
    }
</script>