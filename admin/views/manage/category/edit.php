<div class="group-shop manage categories edit">
    <?php

        if ($isFancybox) {

            echo '<h1>' . $page->title . '</h1>';
            $class = 'system-alert';

        } else {

            $class = '';
        }

        echo form_open(uri_string() . $isFancybox);

    ?>
    <p class="<?=$class?>">
        Manage the shop's categories. Categories are like departments and should be used to organise
        similar products. Additionally, categories can be nested to more granularly organise items.
    </p>
    <?=$isFancybox ? '' : '<hr />'?>
    <ul class="tabs disabled">
        <li class="tab">
            <?=anchor('admin/shop/manage/category' . $isFancybox, 'Overview', 'class="confirm" data-title="Are you sure?" data-body="Any unsaved changes will be lost."')?>
        </li>
        <li class="tab active">
            <?=anchor('admin/shop/manage/category/create' . $isFancybox, 'Create Category')?>
        </li>
    </ul>
    <section class="tabs pages">
        <div class="tab page active">
            <fieldset>
                <legend>Basic Details</legend>
                <p>
                    These fields describe the category and help organise it into the shop's category hierarchy.
                </p>
                <?php

                    $field                = array();
                    $field['key']         = 'label';
                    $field['label']       = 'Label';
                    $field['required']    = true;
                    $field['default']     = isset($category->label) ? $category->label : '';
                    $field['placeholder'] = 'The label to give your category';

                    echo form_field($field);

                    // --------------------------------------------------------------------------

                    $field            = array();
                    $field['key']     = 'parent_id';
                    $field['label']   = 'Parent';
                    $field['class']   = 'select2';
                    $field['default'] = isset($category->parent_id) ? $category->parent_id : '';

                    $options = array();

                    foreach ($categories as $cat) {

                        //  Category can't have itself as the parent
                        if (isset($category->id) && $category->id == $cat->id) {

                            continue;
                        }

                        $breadcrumbs = array();
                        foreach ($cat->breadcrumbs as $bc) {

                            $breadcrumbs[] = $bc->label;
                        }

                        $options[$cat->id] = implode(' &rsaquo; ', $breadcrumbs);
                    }

                    asort($options);
                    $options = (array) 'No Parent' + $options;
                    echo form_field_dropdown($field, $options);

                    // --------------------------------------------------------------------------

                    $field            = array();
                    $field['key']     = 'cover_id';
                    $field['label']   = 'Cover Image';
                    $field['default'] = isset($category->cover_id) ? $category->cover_id : '';
                    $field['bucket']  = 'shop-category-cover';

                    echo form_field_mm_image($field);

                    // --------------------------------------------------------------------------

                    $field                = array();
                    $field['key']         = 'description';
                    $field['label']       = 'Description';
                    $field['placeholder'] = 'This text may be used on the category\'s overview page.';
                    $field['default']     = isset($category->description) ? $category->description : '';

                    echo form_field_wysiwyg($field);

                ?>
            </fieldset>
            <fieldset>
                <legend>Search Engine Optimisation</legend>
                <p>
                    These fields help describe the category to search engines. These fields won't be seen publicly.
                </p>
                <?php

                    $field                = array();
                    $field['key']         = 'seo_title';
                    $field['label']       = 'SEO Title';
                    $field['sub_label']   = 'Max. 150 characters';
                    $field['placeholder'] = 'An alternative, SEO specific title for the category.';
                    $field['default']     = isset($category->seo_title) ? $category->seo_title : '';

                    echo form_field($field);

                    // --------------------------------------------------------------------------

                    $field                = array();
                    $field['key']         = 'seo_description';
                    $field['label']       = 'SEO Description';
                    $field['sub_label']   = 'Max. 300 characters';
                    $field['type']        = 'textarea';
                    $field['placeholder'] = 'This text will be read by search engines when they\'re indexing the page. Keep this short and concise.';
                    $field['default']     = isset($category->seo_description) ? $category->seo_description : '';

                    echo form_field($field);

                    // --------------------------------------------------------------------------

                    $field                = array();
                    $field['key']         = 'seo_keywords';
                    $field['label']       = 'SEO Keywords';
                    $field['sub_label']   = 'Max. 150 characters';
                    $field['placeholder'] = 'These comma separated keywords help search engines understand the context of the page; stick to 5-10 words.';
                    $field['default']     = isset($category->seo_keywords) ? $category->seo_keywords : '';

                    echo form_field($field);

                ?>
            </fieldset>
            <p style="margin-top:1em;">
            <?php

                echo form_submit('submit', 'Save', 'class="awesome"');
                echo anchor(
                    'admin/shop/manage/category' . $isFancybox,
                    'Cancel',
                    'class="awesome red confirm" data-title="Are you sure?" data-body="All unsaved changes will be lost."'
                );

            ?>
            </p>
        </div>
    </section>
    <?=form_close();?>
</div>
<?php

    echo \Nails\Admin\Helper::loadInlineView('utilities/footer', array('items' => $categories));
