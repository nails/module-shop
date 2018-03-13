<div class="group-shop manage brands overview">
    <p>
        Manage which brands are available for your products.
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

                if ($brands) {

                    foreach ($brands as $brand) {

                        echo '<tr>';
                            echo '<td class="label">';

                                echo '<div class="thumbnail">';
                                if ($brand->logo_id) {

                                    echo anchor(cdnServe($brand->logo_id), img(cdnCrop($brand->logo_id, 35, 35)), 'class="fancybox"');

                                } else {

                                    echo img(NAILS_PATH . 'module-shop/assets/img/no-image.jpg');
                                }
                                echo '</div>';

                                echo '<div class="content">';
                                    echo $brand->label;
                                    echo $brand->description ? '<small>' . character_limiter(strip_tags($brand->description), 225) . '</small>' : '<small>No Description</small>';
                                echo '</div>';

                            echo '</td>';
                            echo '<td class="count">';
                                echo !isset($brand->product_count) ? 'Unknown' : $brand->product_count;
                            echo '</td>';

                            echo adminHelper('loadDatetimeCell', $brand->modified);
                            echo adminHelper('loadBoolCell', $brand->is_active);

                            echo '<td class="actions">';

                                if (userHasPermission('admin:shop:manage:brand:edit')) {

                                    echo anchor(
                                        'admin/shop/manage/brand/edit/' . $brand->id . $isModal,
                                        lang('action_edit'),
                                        'class="btn btn-xs btn-primary"'
                                    );

                                }

                                if (userHasPermission('admin:shop:manage:brand:delete')) {

                                    echo anchor(
                                        'admin/shop/manage/brand/delete/' . $brand->id . $isModal,
                                        lang('action_delete'),
                                        'class="btn btn-xs btn-danger confirm" data-body="This action cannot be undone."'
                                    );

                                }

                                echo anchor($shopUrl . 'brand/' . $brand->slug, lang('action_view'), 'class="btn btn-xs btn-default" target="_blank"');

                            echo '</td>';
                        echo '</tr>';
                    }

                } else {

                    echo '<tr>';
                        echo '<td colspan="5" class="no-data">';
                            echo 'No Brands Found';
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

    echo adminHelper('loadInlineView', 'utilities/footer', array('items' => $brands));
