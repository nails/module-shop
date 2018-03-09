<div class="group-shop manage product-type-meta overview">
    <p>
        Product Type Meta fields allow the shop to store additional information for variants. The store
        also uses this data to provide a user friendly filtering system which responds to the products
        available in the current view.
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
                    <th class="label">Associated Product Types</th>
                    <th class="modified">Modified</th>
                    <th class="actions">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php

                if ($metaFields) {

                    foreach ($metaFields as $field) {

                        echo '<tr>';
                            echo '<td class="label">';
                                echo $field->label;
                            echo '</td>';
                            echo '<td class="associated">';
                                foreach ($field->associated_product_types as $association) {

                                    echo '<span class="badge">';
                                        echo anchor(
                                            'admin/shop/manage/productType/edit/' . $association->id,
                                            $association->label
                                        );
                                    echo '</span>';
                                }
                            echo '</td>';
                            echo adminHelper('loadDatetimeCell', $field->modified);
                            echo '<td class="actions">';

                                if (userHasPermission('admin:shop:manage:productTypeMeta:edit')) {

                                    echo anchor(
                                        'admin/shop/manage/productTypeMeta/edit/' . $field->id . $isModal,
                                        lang('action_edit'),
                                        'class="btn btn-xs btn-primary"'
                                    );
                                }

                                if (userHasPermission('admin:shop:manage:productTypeMeta:delete')) {

                                    echo anchor(
                                        'admin/shop/manage/productTypeMeta/delete/' . $field->id . $isModal,
                                        lang('action_delete'),
                                        'class="btn btn-xs btn-danger confirm" data-body="This action cannot be undone."'
                                    );
                                }

                            echo '</td>';
                        echo '</tr>';

                    }

                } else {

                    echo '<tr>';
                        echo '<td colspan="8" class="no-data">';
                            echo 'No Meta Fields Found';
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