<div class="group-shop manage suppliers overview">
    <p>
        Manage which suppliers are available for your products.
    </p>
    <?php

        echo \Nails\Admin\Helper::loadSearch($search);
        echo \Nails\Admin\Helper::loadPagination($pagination);

    ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th class="label">Label</th>
                    <th class="count">Products</th>
                    <th class="modified">Modified</th>
                    <th class="active">Active</th>
                    <th class="actions">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php

                if ($suppliers) {

                    foreach ($suppliers as $supplier) {

                        echo '<tr>';
                            echo '<td class="label">';
                                echo $supplier->label;
                            echo '</td>';
                            echo '<td class="count">';
                                echo !isset($supplier->product_count) ? 'Unknown' : $supplier->product_count;
                            echo '</td>';

                            echo \Nails\Admin\Helper::loadDatetimeCell($supplier->modified);
                            echo \Nails\Admin\Helper::loadBoolCell($supplier->is_active);

                            echo '<td class="actions">';

                                if (userHasPermission('admin:shop:manage:supplier:edit')) {

                                    echo anchor(
                                        'admin/shop/manage/supplier/edit/' . $supplier->id . $isModal,
                                        lang('action_edit'),
                                        'class="awesome small"'
                                    );

                                }

                                if (userHasPermission('admin:shop:manage:supplier:delete')) {

                                    echo anchor(
                                        'admin/shop/manage/supplier/delete/' . $supplier->id . $isModal,
                                        lang('action_delete'),
                                        'class="awesome small red confirm" data-body="This action cannot be undone."'
                                    );

                                }

                            echo '</td>';
                        echo '</tr>';
                    }

                } else {

                    echo '<tr>';
                        echo '<td colspan="5" class="no-data">';
                            echo 'No Suppliers Found';
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
<?php

    echo \Nails\Admin\Helper::loadInlineView('utilities/footer', array('items' => $suppliers));
