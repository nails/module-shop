<div class="group-shop product-availability-notifications browse">
    <p>
        The following people have requested notification when items are back in stock.
    </p>
    <hr />
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th class="checkAll">
                    <?php

                        if (!empty($notifications)) {

                            echo '<input type="checkbox" id="toggle-all" />';

                        }

                    ?>
                    </th>
                    <th class="user">User</th>
                    <th class="product">Product</th>
                    <th class="created">Created</th>
                    <th class="actions text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php

                    if (!empty($notifications)) {

                        foreach ($notifications as $item) {

                            ?>
                            <tr id="notification-<?=$item->id?>">
                                <td class="checkAll">
                                    <input type="checkbox" class="batch-checkbox" value="<?=$item->id?>" />
                                </td>

                                <?php

                                    echo adminHelper('loadUserCell', $item->user);

                                ?>
                                <td class="product">
                                <?php

                                    echo '<strong>' . anchor('admin/shop/inventory/edit/' . $item->product->id, $item->product->label) . '</strong>';

                                    if ($item->product->label != $item->variation->label) {

                                        echo '<br />' . $item->variation->label;

                                    }

                                ?>
                                </td>
                                <?php

                                    echo adminHelper('loadDatetimeCell', $item->created);

                                ?>
                                <td class="actions text-center">
                                    <?php

                                        //    Render buttons
                                        $_buttons = array();

                                        if (userHasPermission('admin:shop:availability:edit')) {

                                            $_buttons[] = anchor('admin/shop/availability/edit/' . $item->id, lang('action_edit'), 'class="btn btn-xs btn-primary"');

                                        }

                                        // --------------------------------------------------------------------------

                                        if (userHasPermission('admin:shop:availability:delete')) {

                                            $_buttons[] = anchor('admin/shop/availability/delete/' . $item->id, lang('action_delete'), 'class="btn btn-xs btn-danger confirm" data-body="This action cannot be undone."');

                                        }

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
                            <td colspan="5" class="no-data">
                                <p>No Product Availability Notifications Found</p>
                            </td>
                        </tr>
                        <?php
                    }

                ?>
            </tbody>
        </table>
        <?php

            if (!empty($notifications)) {

                $_options            = array();
                $_options['']        = 'Choose';
                $_options['delete']    = 'Delete';

                ?>
                <div class="panel" id="batch-action">
                    <div class="panel-body">
                        With checked:
                        <?=form_dropdown('', $_options, null)?>
                        <a href="#" class="btn btn-xs btn-primary">Go</a>
                    </div>
                </div>
                <?php

            }

        ?>
    </div>
</div>