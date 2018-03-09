<div class="group-shop manage index">
    <p>
        Choose which Manager you'd like to utilise.
    </p>
    <?=$isModal ? '' : '<hr />'?>
    <?php

        //    Gather manager options available to this user
        $option = array();

        if (userHasPermission('admin:shop:manage:attribute:manage')) {

            $options[] = anchor('admin/shop/manage/attribute' . $isModal, 'Attributes');
        }

        if (userHasPermission('admin:shop:manage:brand:manage')) {

            $options[] = anchor('admin/shop/manage/brand' . $isModal, 'Brands');
        }

        if (userHasPermission('admin:shop:manage:supplier:manage')) {

            $options[] = anchor('admin/shop/manage/supplier' . $isModal, 'Suppliers');
        }

        if (userHasPermission('admin:shop:manage:category:manage')) {

            $options[] = anchor('admin/shop/manage/category' . $isModal, 'Categories');
        }

        if (userHasPermission('admin:shop:manage:collection:manage')) {

            $options[] = anchor('admin/shop/manage/collection' . $isModal, 'Collections');
        }

        if (userHasPermission('admin:shop:manage:range:manage')) {

            $options[] = anchor('admin/shop/manage/range' . $isModal, 'Ranges');
        }

        if (userHasPermission('admin:shop:manage:tag:manage')) {

            $options[] = anchor('admin/shop/manage/tag' . $isModal, 'Tags');
        }

        if (userHasPermission('admin:shop:manage:taxRate:manage')) {

            $options[] = anchor('admin/shop/manage/taxRate' . $isModal, 'Tax Rates');
        }

        if (userHasPermission('admin:shop:manage:productType:manage')) {

            $options[] = anchor('admin/shop/manage/productType' . $isModal, 'Product Types');
        }

        if (userHasPermission('admin:shop:manage:productTypeMeta:manage')) {

            $options[] = anchor('admin/shop/manage/productTypeMeta' . $isModal, 'Product Type Meta');
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

            echo '<p class="alert alert-warning">';
                echo 'It looks as if there are no manager options available for you to use. If you ';
                echo 'were expecting to see options here then please contact the shop manager.';
            echo '</p>';
        }

    ?>
    </ul>
</div>