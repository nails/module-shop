<div class="group-shop manage collections overview">
    <p>
        Manage which collections are available for your products. Products grouped together
        into a collection are deemed related and can have their own customised landing page.
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

                if ($collections) {

                    foreach ($collections as $collection) {

                        echo '<tr>';
                            echo '<td class="label">';

                            echo $collection->label;
                            echo $collection->description ? '<small>' . character_limiter(strip_tags($collection->description), 225) . '</small>' : '<small>No Description</small>';

                            echo '</td>';
                            echo '<td class="count">';
                                echo !isset($collection->product_count) ? 'Unknown' : $collection->product_count;
                            echo '</td>';

                            echo adminHelper('loadDatetimeCell', $collection->modified);
                            echo adminHelper('loadBoolCell', $collection->is_active);

                            echo '<td class="actions">';

                                if (userHasPermission('admin:shop:manage:collection:edit')) {

                                    echo anchor(
                                        'admin/shop/manage/collection/edit/' . $collection->id . $isModal,
                                        lang('action_edit'),
                                        'class="btn btn-xs btn-primary"'
                                    );
                                }

                                if (userHasPermission('admin:shop:manage:collection:delete')) {

                                    echo anchor(
                                        'admin/shop/manage/collection/delete/' . $collection->id . $isModal,
                                        lang('action_delete'),
                                        'class="btn btn-xs btn-danger confirm" data-body="This action cannot be undone."'
                                    );
                                }

                                echo anchor($shopUrl . 'collection/' . $collection->slug, lang('action_view'), 'class="btn btn-xs btn-default" target="_blank"');

                            echo '</td>';
                        echo '</tr>';
                    }

                } else {

                    echo '<tr>';
                        echo '<td colspan="5" class="no-data">';
                            echo 'No Collections Found';
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

    echo adminHelper('loadInlineView', 'utilities/footer', array('items' => $collections));
