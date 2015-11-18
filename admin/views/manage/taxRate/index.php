<div class="group-shop manage tax-rate overview">
    <p>
        Manage which tax rates the shop supports.
    </p>
    <?php

        echo adminHelper('loadSearch', $search);
        echo adminHelper('loadPagination', $pagination);

    ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th class="label">Label</th>
                    <th class="rate">Rate</th>
                    <th class="count">Products</th>
                    <th class="modified">Modified</th>
                    <th class="actions">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php

                if ($taxRates) {

                    foreach ($taxRates as $taxRate) {

                        echo '<tr>';
                            echo '<td class="label">';
                                echo $taxRate->label;
                            echo '</td>';
                            echo '<td class="rate">';
                                echo $taxRate->rate * 100 . '%';
                            echo '</td>';
                            echo '<td class="count">';
                                echo !isset($taxRate->product_count) ? 'Unknown' : $taxRate->product_count;
                            echo '</td>';
                            echo adminHelper('loadDatetimeCell', $taxRate->modified);
                            echo '<td class="actions">';

                                if (userHasPermission('admin:shop:manage:taxRate:edit')) {

                                    echo anchor(
                                        'admin/shop/manage/taxRate/edit/' . $taxRate->id . $isModal,
                                        lang('action_edit'),
                                        'class="btn btn-xs btn-primary"'
                                    );
                                }

                                if (userHasPermission('admin:shop:manage:taxRate:delete')) {

                                    echo anchor(
                                        'admin/shop/manage/taxRate/delete/' . $taxRate->id . $isModal,
                                        lang('action_delete'),
                                        'class="btn btn-xs btn-danger confirm" data-body="This action cannot be undone."'
                                    );
                                }

                            echo '</td>';
                        echo '</tr>';
                    }

                } else {

                    echo '<tr>';
                        echo '<td colspan="5" class="no-data">';
                            echo 'No Tax Rates Found';
                        echo '</td>';
                    echo '</tr>';
                }

            ?>
            </tbody>
        </table>
    </div>
    <?php

        echo adminHelper('loadPagination', $pagination);

    ?>
</div>
<?php

    echo adminHelper('loadPagination', $pagination);
    echo adminHelper('loadInlineView', 'utilities/footer', array('items' => $taxRates));
