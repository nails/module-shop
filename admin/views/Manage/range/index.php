<div class="group-shop manage ranges overview">
    <p>
        Manage which ranges are available for your products. Products grouped
        together into a range are deemed related and can have their own customised
        landing page.
    </p>
    <?php

        echo adminHelper('loadSearch', $search);
        echo adminHelper('loadPagination', $pagination);

    ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th class="label">Label &amp; Description</th>
                    <th class="count">Products</th>
                    <th class="modified">Modified</th>
                    <th class="active">Active</th>
                    <th class="actions">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php

                if ($ranges) {

                    foreach ($ranges as $range) {

                        echo '<tr>';
                            echo '<td class="label">';

                            echo $range->label;
                            echo $range->description ? '<small>' . character_limiter(strip_tags($range->description), 225) . '</small>' : '<small>No Description</small>';

                            echo '</td>';
                            echo '<td class="count">';
                                echo !isset($range->product_count) ? 'Unknown' : $range->product_count;
                            echo '</td>';

                            echo adminHelper('loadDatetimeCell', $range->modified);
                            echo adminHelper('loadBoolCell', $range->is_active);

                            echo '<td class="actions">';

                                if (userHasPermission('admin:shop:manage:range:edit')) {

                                    echo anchor(
                                        'admin/shop/manage/range/edit/' . $range->id . $isModal,
                                        lang('action_edit'),
                                        'class="btn btn-xs btn-primary"'
                                    );
                                }

                                if (userHasPermission('admin:shop:manage:range:delete')) {

                                    echo anchor(
                                        'admin/shop/manage/range/delete/' . $range->id . $isModal,
                                        lang('action_delete'),
                                        'class="btn btn-xs btn-danger confirm" data-body="This action cannot be undone."'
                                    );
                                }

                                echo anchor(
                                    $shopUrl . 'range/' . $range->slug,
                                    lang('action_view'),
                                    'class="btn btn-xs btn-default" target="_blank"'
                                );

                            echo '</td>';
                        echo '</tr>';
                    }

                } else {

                    echo '<tr>';
                        echo '<td colspan="5" class="no-data">';
                            echo 'No Ranges Found';
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

    echo adminHelper('loadInlineView', 'utilities/footer', array('items' => $ranges));
