<div class="group-shop inventory browse">
    <p>
        Browse the shop's inventory.
    </p>
    <?php

        echo adminHelper('loadSearch', $search);
        echo adminHelper('loadPagination', $pagination);

    ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th class="id">ID</th>
                    <th class="image">Image</th>
                    <th class="active">Active</th>
                    <th class="label">Label &amp; Description</th>
                    <?php

                        if (count($productTypes) > 1) {

                            echo '<th class="type">Type</th>';
                        }
                    ?>
                    <th class="datetime">Modified</th>
                    <th class="actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php

                    if (!empty($products)) {

                        foreach ($products as $item) {

                            ?>
                            <tr id="product-<?=$item->id?>">
                                <td class="id"><?=number_format($item->id)?></td>
                                <td class="image">
                                    <?php

                                        if (!empty($item->gallery[0])) {

                                            echo anchor(cdnServe($item->gallery[0]), img(cdnScale($item->gallery[0], 75, 75)), 'class="fancybox"');

                                        } else {

                                            echo img(NAILS_ASSETS_URL . 'img/admin/modules/shop/image-icon.png');
                                        }

                                    ?>
                                </td>
                                <?php

                                    echo adminHelper('loadBoolCell', $item->is_active);

                                ?>
                                <td class="label">
                                    <?=$item->label?>
                                    <small><?=word_limiter(strip_tags($item->description), 30)?></small>
                                </td>
                                <?php

                                    if (count($productTypes) > 1) {

                                        echo '<td class="type">' . $item->type->label . '</td>';
                                    }
                                ?>
                                <?php

                                    echo adminHelper('loadDatetimeCell', $item->modified);

                                ?>
                                <td class="actions">
                                    <?php

                                        //  Render buttons
                                        $_buttons = array();

                                        $_buttons[] = anchor(
                                            $item->url,
                                            lang('action_view'),
                                            'class="btn btn-xs btn-default" target="_blank"'
                                        );

                                        if (userHasPermission('admin:shop:inventory:edit')) {

                                            $_buttons[] = anchor(
                                                'admin/shop/inventory/edit/' . $item->id,
                                                lang('action_edit'),
                                                'class="btn btn-xs btn-primary"'
                                            );
                                        }

                                        // --------------------------------------------------------------------------

                                        if (userHasPermission('admin:shop:inventory:delete')) {

                                            $_buttons[] = anchor('
                                                admin/shop/inventory/delete/' . $item->id,
                                                lang('action_delete'),
                                                'class="btn btn-xs btn-danger confirm" data-body="You can undo this action."'
                                            );
                                        }

                                        // --------------------------------------------------------------------------

                                        if ($_buttons) {

                                            foreach ($_buttons aS $button) {

                                                echo $button;
                                            }

                                        } else {

                                            ?>
                                            <span class="blank">
                                                There are no actions you can perform on this item.
                                            </span>
                                            <?php
                                        }

                                    ?>
                                </td>
                            </tr>
                            <?php

                        }

                    } else {
                        ?>
                        <tr>
                            <td colspan="7" class="no-data">
                                <p>No Products Found</p>
                            </td>
                        </tr>
                        <?php
                    }

                ?>
            </tbody>
        </table>
    </div>
    <?php

        echo adminHelper('loadPagination', $pagination);

    ?>
</div>