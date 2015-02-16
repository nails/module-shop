<div class="group-shop vouchers browse">
    <p>
        Browse all vouchers (including gift cards) which are associated with the shop.
    </p>
    <?php

        echo \Nails\Admin\Helper::loadSearch($search);
        echo \Nails\Admin\Helper::loadPagination($pagination);

    ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th class="code">Code</th>
                    <th class="boolean">Active</th>
                    <th class="details">Details</th>
                    <th class="user">Created By</th>
                    <th class="datetime">Created</th>
                    <th class="value">Discount</th>
                    <th class="datetime">Valid From</th>
                    <th class="datetime">Expires</th>
                    <th class="uses">Uses</th>
                    <th class="actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php

                    if ($vouchers) {

                        foreach ($vouchers as $voucher) {

                            echo '<tr id="order-<?=number_format($voucher->id)?>">';
                                echo '<td class="code">' . $voucher->code . '</td>';

                                if ($voucher->is_active && strtotime($voucher->valid_from) > time()) {

                                    echo '<td class="boolean notice">';
                                        echo '<b class="fa fa-clock-o fa-lg"></b>';
                                        echo '<small>Pending</small>';
                                    echo '</td>';

                                } elseif ($voucher->is_active && $voucher->valid_to && strtotime($voucher->valid_to) < time()) {

                                    echo '<td class="boolean error">';
                                        echo '<b class="fa fa-times-circle fa-lg"></b>';
                                        echo '<small>Expired</small>';
                                    echo '</td>';

                                } else {

                                    echo \Nails\Admin\Helper::loadBoolCell($voucher->is_active);
                                }
                                echo '<td class="details">';

                                    echo $voucher->label;

                                    switch ($voucher->type) {

                                        case 'NORMAL':

                                            echo '<small>Type: Normal</small>';
                                            break;

                                        // --------------------------------------------------------------------------

                                        case 'LIMITED_USE':

                                            echo '<small>Type: Limited Use</small>';
                                            echo '<small>Limited to ' . $voucher->limited_use_limit . ' uses; used ' . $voucher->use_count . ' times</small>';
                                            break;

                                        // --------------------------------------------------------------------------

                                        case 'GIFT_CARD':

                                            echo '<small>Type: Gift card</small>';
                                            echo '<small>Remaining Balance: ' . SHOP_BASE_CURRENCY_SYMBOL . number_format($voucher->gift_card_balance, SHOP_BASE_CURRENCY_PRECISION). '</small>';
                                            break;
                                    }

                                    // --------------------------------------------------------------------------

                                    echo '<small>Applies to: ';
                                    switch ($voucher->discount_application) {

                                        case 'PRODUCTS':

                                            echo 'Purchases only';
                                            break;

                                        case 'SHIPPING':

                                            echo 'Shipping only';
                                            break;

                                        case 'PRODUCT_TYPES':

                                            echo 'Certain product types only &rsaquo; ' . $voucher->product->label;
                                            break;

                                        case 'ALL':

                                            echo 'Both Products and Shipping';
                                            break;
                                    }
                                    echo '</small>';

                                echo '</td>';

                                echo \Nails\Admin\Helper::loadUserCell($voucher->creator);
                                echo \Nails\Admin\Helper::loadDatetimeCell($voucher->created);

                                echo '<td class="value">';

                                    switch ($voucher->discount_type) {

                                        case 'AMOUNT':

                                            echo SHOP_BASE_CURRENCY_SYMBOL . number_format($voucher->discount_value, SHOP_BASE_CURRENCY_PRECISION);
                                            break;

                                        case 'PERCENTAGE':

                                            echo $voucher->discount_value . '%';
                                            break;
                                    }

                                echo '</td>';

                                echo \Nails\Admin\Helper::loadDatetimeCell($voucher->valid_from);
                                echo \Nails\Admin\Helper::loadDatetimeCell($voucher->valid_to, 'Does not expire');

                                echo '<td class="uses">';
                                    echo number_format($voucher->use_count);
                                echo '</td>';
                                echo '<td class="actions">';

                                    $buttons = array();

                                    // --------------------------------------------------------------------------

                                    if ($voucher->is_active) {

                                        if (userHasPermission('admin.shop{0.vouchers_deactivate')) {

                                            $buttons[] = anchor('admin/shop/vouchers/deactivate/' . $voucher->id, 'Suspend', 'class="awesome small red confirm"');
                                        }

                                    } else {

                                        if (userHasPermission('admin.shop{0.vouchers_activate')) {

                                            $buttons[] = anchor('admin/shop/vouchers/activate/' . $voucher->id, 'Activate', 'class="awesome small green"');
                                        }
                                    }

                                    // --------------------------------------------------------------------------

                                    if ($buttons) {

                                        foreach ($buttons as $button) {

                                            echo $button;
                                        }

                                    } else {

                                        echo '<span class="blank">There are no actions you can do on this item.</span>';
                                    }

                                echo '</td>';
                            echo '</tr>';
                        }

                    } else {

                        echo '<tr>';
                            echo '<td colspan="10" class="no-data">';
                                echo '<p>No Vouchers found</p>';
                            echo '</td>';
                        echo '</tr>';
                    }

                ?>
            </tbody>
        </table>
    </div>
    <?php

        echo \Nails\Admin\Helper::loadPagination($pagination);

    ?>
</div>