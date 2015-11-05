<div class="group-shop manage categories overview">
    <p>
        Manage the shop's categories. Categories are like departments and should be used to organise
        similar products. Additionally, categories can be nested to more granularly organise items.
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
                    <th class="count">
                        Products
                        <span rel="tipsy-top" title="Parent category product counts include products contained within child categories." class="fa fa-question-circle"></span>
                    </th>
                    <th class="modified">Modified</th>
                    <th class="actions">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php

                if ($categories) {

                    foreach ($categories as $category) {

                        echo '<tr>';
                            echo '<td class="label indentosaurus indent-' . $category->depth . '">';

                                echo str_repeat('<div class="indentor"></div>', $category->depth);

                                echo '<div class="indentor-content">';

                                    echo $category->label;

                                    $breadcrumbs = array();
                                    foreach ($category->breadcrumbs as $bc) {

                                        $breadcrumbs[] = $bc->label;
                                    }

                                    echo $breadcrumbs ? '<small>' . implode(' &rsaquo; ', $breadcrumbs) . '</small>' : '<small>Top Level Category</small>';
                                    echo $category->description ? '<small>' . character_limiter(strip_tags($category->description), 225) . '</small>' : '<small>No Description</small>';

                                echo '</div>';

                            echo '</td>';
                            echo '<td class="count">';
                                echo !isset($category->product_count) ? 'Unknown' : $category->product_count;
                            echo '</td>';
                            echo \Nails\Admin\Helper::loadDatetimeCell($category->modified);
                            echo '<td class="actions">';

                                if (userHasPermission('admin:shop:manage:category:edit')) {

                                    echo anchor('admin/shop/manage/category/edit/' . $category->id . $isModal, lang('action_edit'), 'class="awesome small"');
                                }

                                if (userHasPermission('admin:shop:manage:category:delete')) {

                                    echo anchor('admin/shop/manage/category/delete/' . $category->id . $isModal, lang('action_delete'), 'class="awesome small red confirm" data-body="This action cannot be undone, any child categories will also be deleted."');
                                }

                                echo anchor($shopUrl . 'category/' . $category->slug, lang('action_view'), 'class="awesome small orange" target="_blank"');

                            echo '</td>';
                        echo '</tr>';
                    }

                } else {

                    echo '<tr>';
                        echo '<td colspan="4" class="no-data">';
                            echo 'No Categories Found';
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

    echo \Nails\Admin\Helper::loadInlineView('utilities/footer', array('items' => $categories));
