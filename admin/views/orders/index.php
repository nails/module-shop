<div class="group-shop orders browse">
    <p>
        Browse all orders which have been processed by the site from this page.
    </p>
    <?=adminHelper('loadSearch', $search)?>
    <?=adminHelper('loadPagination', $pagination)?>
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
                    <th class="value">Shipping</th>
                    <th class="value">Tax</th>
                    <th class="value">Total</th>
                    <th class="value">Discount</th>
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
                        <?=adminHelper('loadDatetimeCell', $order->created)?>
                        <?=adminHelper('loadUserCell', $order->user)?>
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

                            echo $order->totals->base_formatted->shipping;

                            if ($order->currency !== $order->base_currency) {
                                echo '<small>' . $order->totals->user_formatted->shipping . '</small>';
                            }

                            ?>
                        </td>
                        <td class="value">
                            <?php

                            echo $order->totals->base_formatted->tax_combined;

                            if ($order->currency !== $order->base_currency) {
                                echo '<small>' . $order->totals->user_formatted->tax_combined . '</small>';
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
                        <td class="value">
                            <?php

                            echo $order->totals->base_formatted->grand_discount;

                            if ($order->currency !== $order->base_currency) {
                                echo '<small>' . $order->totals->user_formatted->grand_discount . '</small>';
                            }

                            ?>
                        </td>
                        <?php

                        switch ($order->status) {

                            case 'UNPAID':
                                $sStatus = 'danger';
                                $sIcon   = 'fa-times-circle';
                                break;

                            case 'PAID':
                                $sStatus = 'success';
                                $sIcon   = 'fa-check-circle';
                                break;

                            case 'ABANDONED':
                                $sStatus = 'warning';
                                $sIcon   = 'fa-times-circle';
                                break;

                            case 'CANCELLED':
                                $sStatus = 'warning';
                                $sIcon   = 'fa-times-circle';
                                break;

                            case 'FAILED':
                                $sStatus = 'danger';
                                $sIcon   = 'fa-times-circle';
                                break;

                            case 'PENDING':
                                $sStatus = 'info';
                                $sIcon   = 'fa-info-circle';
                                break;

                            default:
                                $sStatus = '';
                                $sIcon   = 'fa-info-circle';
                                break;
                        }

                        ?>
                        <td class="status text-center <?=$sStatus?>">
                            <i class="fa fa-lg <?=$sIcon?>"></i>
                            <small><?=$order->status?></small>
                        </td>
                        <td class="status text-center">
                            <i class="fa fa-lg <?=$order->lifecycle->admin_icon?>"></i>
                            <small><?=$order->lifecycle->label?></small>
                        </td>
                        <td class="actions">
                            <?php

                            //  Render buttons
                            $aButtons = array();

                            // --------------------------------------------------------------------------

                            if (userHasPermission('admin:shop:orders:view')) {

                                $aButtons[] = anchor(
                                    'admin/shop/orders/view/' . $order->id,
                                    lang('action_view'),
                                    'class="btn btn-xs btn-default"'
                                );
                                $aButtons[] = anchor(
                                    'shop/checkout/invoice/' . $order->ref . '/' . md5($order->code),
                                    'Download',
                                    'class="btn btn-xs btn-primary"'
                                );
                            }

                            // --------------------------------------------------------------------------

                            if (userHasPermission('admin:shop:orders:reprocess')) {

                                $aButtons[] = anchor(
                                    'admin/shop/orders/reprocess/' . $order->id,
                                    'Process',
                                    'class="btn btn-xs btn-warning confirm" data-body="Processing the order again may result in multiple dispatch of items, or dispatch of unpaid items."'
                                );
                            }

                            // --------------------------------------------------------------------------

                            if ($aButtons) {

                                foreach ($aButtons aS $button) {

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
                    <td colspan="12" class="no-data">
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

            $aOptions                     = array();
            $aOptions['']                 = 'Choose';
            $aOptions['mark-cancelled']   = 'Mark Cancelled';
            $aOptions['download']         = 'Download';
            $aOptions['---']              = '---';
            foreach ($lifecycles as $iId => $sLabel) {
                $aOptions['mark-lifecycle-' . $iId] = 'Mark as ' . $sLabel;
            }

            ?>
            <div class="panel" id="batch-action">
                <div class="panel-body">
                    With checked:
                    <?=form_dropdown('', $aOptions, null)?>
                    <a href="#" class="btn btn-xs btn-primary">Go</a>
                </div>
            </div>
            <?php
        }

        ?>
    </div>
    <?=adminHelper('loadPagination', $pagination)?>
</div>
