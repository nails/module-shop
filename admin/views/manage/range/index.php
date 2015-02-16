<div class="group-shop manage ranges overview">
    <?php

        if ($isFancybox) {

            echo '<h1>' . $page->title . '</h1>';
            $class = 'system-alert';

        } else {

            $class = '';
        }

    ?>
    <p class="<?=$class?>">
        Manage which ranges are available for your products. Products grouped together into a range are deemed related and can have their own customised landing page.
    </p>
    <?=$isFancybox ? '' : '<hr />'?>
    <ul class="tabs disabled">
        <li class="tab active">
            <?=anchor('admin/shop/manage/range' . $isFancybox, 'Overview')?>
        </li>
        <li class="tab">
            <?=anchor('admin/shop/manage/range/create' . $isFancybox, 'Create Range')?>
        </li>
    </ul>
    <section class="tabs pages">
        <div class="tab page active">
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

                                    echo \Nails\Admin\Helper::loadDatetimeCell($range->modified);
                                    echo \Nails\Admin\Helper::loadBoolCell($range->is_active);

                                    echo '<td class="actions">';

                                        if (userHasPermission('admin.shop:0.range_edit')) {

                                            echo anchor(
                                                'admin/shop/manage/range/edit/' . $range->id . $isFancybox,
                                                lang('action_edit'),
                                                'class="awesome small"'
                                            );
                                        }

                                        if (userHasPermission('admin.shop:0.range_delete')) {

                                            echo anchor(
                                                'admin/shop/manage/range/delete/' . $range->id . $isFancybox,
                                                lang('action_delete'),
                                                'class="awesome small red confirm" data-title="Are you sure?" data-body="This action cannot be undone."'
                                            );
                                        }

                                        echo anchor(
                                            $shopUrl . 'range/' . $range->slug,
                                            lang('action_view'),
                                            'class="awesome small orange" target="_blank"'
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
        </div>
    </section>
</div>
<?php

    echo \Nails\Admin\Helper::loadInlineView('utilities/footer', array('items' => $ranges));
