<div class="group-shop manage product-type overview">
    <p>
        Product types control how the order is processed when a user completes checkout and
        payment is authorised. Most products can simply be considered a generic product, however,
        there are some cases when a product should be processed differently (e.g a download
        requires links to be generated). For the most part product types will be defined by
        the developer, however you may create your own for the sake of organisation in admin.
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
                    <th class="ipn">IPN Method</th>
                    <th class="physical">Physical</th>
                    <th class="max-po">Max Per Order</th>
                    <th class="max-v">Max Variations</th>
                    <th class="count">Products</th>
                    <th class="modified">Modified</th>
                    <th class="actions">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php

                if ($productTypes) {

                    foreach ($productTypes as $productType) {

                        echo '<tr>';
                            echo '<td class="label">';
                                echo $productType->label;
                                echo $productType->description ? '<small>' . character_limiter(strip_tags($productType->description), 225) . '</small>' : '<small>No Description</small>';
                            echo '</td>';
                            echo '<td class="ipn">';
                                echo $productType->ipn_method ? '<code>' . $productType->ipn_method . '()</code>' : '&mdash;';
                            echo '</td>';
                            echo '<td class="physical">';
                                echo $productType->is_physical ? lang('yes') : lang('no');
                            echo '</td>';
                            echo '<td class="max-po">';
                                echo $productType->max_per_order ? $productType->max_per_order : 'Unlimited';
                            echo '</td>';
                            echo '<td class="max-v">';
                                echo $productType->max_variations ? $productType->max_variations : 'Unlimited';
                            echo '</td>';
                            echo '<td class="count">';
                                echo !isset($productType->product_count) ? 'Unknown' : $productType->product_count;
                            echo '</td>';
                            echo \Nails\Admin\Helper::loadDatetimeCell($productType->modified);
                            echo '<td class="actions">';

                                if (userHasPermission('admin:shop:manage:productType:edit')) {

                                    echo anchor(
                                        'admin/shop/manage/productType/edit/' . $productType->id . $isModal,
                                        lang('action_edit'),
                                        'class="awesome small"'
                                    );
                                }

                                if (userHasPermission('admin:shop:manage:productType:delete')) {

                                    echo anchor(
                                        'admin/shop/manage/productType/delete/' . $productType->id . $isModal,
                                        lang('action_delete'),
                                        'class="awesome small red confirm" data-body="This action cannot be undone."'
                                    );
                                }

                            echo '</td>';
                        echo '</tr>';
                    }

                } else {

                    echo '<tr>';
                        echo '<td colspan="8" class="no-data">';
                            echo 'No Product Types Found';
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

    echo \Nails\Admin\Helper::loadInlineView('utilities/footer', array('items' => $productTypes));
