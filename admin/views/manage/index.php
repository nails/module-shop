<div class="group-shop manage index">
    <p class="<?=$isFancybox ? 'system-alert' : ''?>">
        Choose which Manager you'd like to utilise.
    </p>
    <?=$isFancybox ? '' : '<hr />'?>
    <?php

        //    Gather manager options available to this user
        $option = array();

        if (userHasPermission('admin.shop:0.attribute_manage')) {

            $options[] = anchor('admin/shop/manage/attribute' . $isFancybox, 'Attributes');
        }

        if (userHasPermission('admin.shop:0.brand_manage')) {

            $options[] = anchor('admin/shop/manage/brand' . $isFancybox, 'Brands');
        }

        if (userHasPermission('admin.shop:0.category_manage')) {

            $options[] = anchor('admin/shop/manage/category' . $isFancybox, 'Categories');
        }

        if (userHasPermission('admin.shop:0.collection_manage')) {

            $options[] = anchor('admin/shop/manage/collection' . $isFancybox, 'Collections');
        }

        if (userHasPermission('admin.shop:0.range_manage')) {

            $options[] = anchor('admin/shop/manage/range' . $isFancybox, 'Ranges');
        }

        if (userHasPermission('admin.shop:0.tag_manage')) {

            $options[] = anchor('admin/shop/manage/tag' . $isFancybox, 'Tags');
        }

        if (userHasPermission('admin.shop:0.tax_rate_manage')) {

            $options[] = anchor('admin/shop/manage/taxRate' . $isFancybox, 'Tax Rates');
        }

        if (userHasPermission('admin.shop:0.product_type_manage')) {

            $options[] = anchor('admin/shop/manage/productType' . $isFancybox, 'Product Types');
        }

        if (userHasPermission('admin.shop:0.product_type_meta_manage')) {

            $options[] = anchor('admin/shop/manage/productTypeMeta' . $isFancybox, 'Product Type Meta');
        }

        // --------------------------------------------------------------------------

        if (!empty($options)) {

            echo '<ul class="options clearfix">';
            foreach ($options as $option) {

                echo '<li class="col-xs-12 col-sm-6 col-md-4 col-lg-3">';
                    echo '<div class="option">';
                        echo $option;
                    echo '</div>';
                echo '</li>';
            }
            echo '</ul>';

        } else {

            echo '<p class="system-alert message">';
                echo 'It looks as if there are no manager options available for you to use. If you ';
                echo 'were expecting to see options here then please contact the shop manager.';
            echo '</p>';
        }

    ?>
    </ul>
</div>