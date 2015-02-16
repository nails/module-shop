<div class="group-shop manage brands overview">
    <p>
        Manage which brands are available for your products.
    </p>
    <?php

        echo \Nails\Admin\Helper::loadSearch($search);
        echo \Nails\Admin\Helper::loadPagination($pagination);

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

                                    echo anchor(cdn_serve($brand->logo_id), img(cdn_thumb($brand->logo_id, 32, 32)), 'class="fancybox"');

                                } else {

                                    echo img(NAILS_ASSETS_URL . 'img/admin/modules/shop/manager/no-image.jpg');

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

                            echo \Nails\Admin\Helper::loadDatetimeCell($brand->modified);
                            echo \Nails\Admin\Helper::loadBoolCell($brand->is_active);

                            echo '<td class="actions">';

                                if (userHasPermission('admin.shop:0.brand_edit')) {

                                    echo anchor('admin/shop/manage/brand/edit/' . $brand->id . $isModal, lang('action_edit'), 'class="awesome small"');

                                }

                                if (userHasPermission('admin.shop:0.brand_delete')) {

                                    echo anchor('admin/shop/manage/brand/delete/' . $brand->id . $isModal, lang('action_delete'), 'class="awesome small red confirm" data-title="Are you sure?" data-body="This action cannot be undone."');

                                }

                                echo anchor($shopUrl . 'brand/' . $brand->slug, lang('action_view'), 'class="awesome small orange" target="_blank"');

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

        echo \Nails\Admin\Helper::loadPagination($pagination);

    ?>
</div>
<?php

    echo \Nails\Admin\Helper::loadInlineView('utilities/footer', array('items' => $brands));
