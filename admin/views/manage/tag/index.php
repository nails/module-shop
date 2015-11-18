<div class="group-shop manage tags overview">
    <p>
        Manage the which tags are available for your products. Tags help the shop determine related products.
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
                    <th class="actions">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php

                if ($tags) {

                    foreach ($tags as $tag) {

                        echo '<tr>';
                            echo '<td class="label">';

                            echo $tag->label;
                            echo $tag->description ? '<small>' . character_limiter(strip_tags($tag->description), 225) . '</small>' : '<small>No Description</small>';

                            echo '</td>';
                            echo '<td class="count">';
                                echo !isset($tag->product_count) ? 'Unknown' : $tag->product_count;
                            echo '</td>';
                            echo adminHelper('loadDatetimeCell', $tag->modified);
                            echo '<td class="actions">';

                                if (userHasPermission('admin:shop:manage:tag:edit')) {

                                    echo anchor(
                                        'admin/shop/manage/tag/edit/' . $tag->id . $isModal,
                                        lang('action_edit'),
                                        'class="awesome small"'
                                    );
                                }

                                if (userHasPermission('admin:shop:manage:tag:delete')) {

                                    echo anchor(
                                        'admin/shop/manage/tag/delete/' . $tag->id . $isModal,
                                        lang('action_delete'),
                                        'class="awesome small red confirm" data-body="This action cannot be undone."'
                                    );
                                }

                                echo anchor(
                                    $shopUrl . 'tag/' . $tag->slug,
                                    lang('action_view'),
                                    'class="awesome small orange" target="_blank"'
                                );

                            echo '</td>';
                        echo '</tr>';
                    }

                } else {

                    echo '<tr>';
                        echo '<td colspan="4" class="no-data">';
                            echo 'No Tags Found';
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

    echo adminHelper('loadInlineView', 'utilities/footer', array('items' => $tags));
