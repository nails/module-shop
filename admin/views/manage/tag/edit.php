<div class="group-shop manage tags edit">
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
        Manage which tags are available for your products. Products grouped together into a
        tag are deemed related and can have their own customised landing page.
    </p>
    <?=$isFancybox ? '' : '<hr />'?>
    <ul class="tabs disabled">
        <li class="tab">
            <?=anchor('admin/shop/manage/tag' . $isFancybox, 'Overview', 'class="confirm" data-title="Are you sure?" data-body="Any unsaved changes will be lost."')?>
        </li>
        <li class="tab active">
            <?=anchor('admin/shop/manage/tag/create' . $isFancybox, 'Create Tag')?>
        </li>
    </ul>
    <section class="tabs pages">
        <div class="tab page active">
            <fieldset>
                <legend>Basic Details</legend>
                <p>
                    These fields describe the tag.
                </p>
                <?php

                    $field                = array();
                    $field['key']         = 'label';
                    $field['label']       = 'Label';
                    $field['required']    = true;
                    $field['default']     = isset($tag->label) ? $tag->label : '';
                    $field['placeholder'] = 'The label to give your tag';

                    echo form_field($field);

                    // --------------------------------------------------------------------------

                    $field            = array();
                    $field['key']     = 'cover_id';
                    $field['label']   = 'Cover Image';
                    $field['default'] = isset($tag->cover_id) ? $tag->cover_id : '';
                    $field['bucket']  = 'shop-tag-cover';

                    echo form_field_mm_image($field);

                    // --------------------------------------------------------------------------

                    $field                = array();
                    $field['key']         = 'description';
                    $field['label']       = 'Description';
                    $field['placeholder'] = 'This text may be used on the tag\'s overview page.';
                    $field['default']     = isset($tag->description) ? $tag->description : '';

                    echo form_field_wysiwyg($field);

                ?>
            </fieldset>
            <fieldset>
                <legend>Search Engine Optimisation</legend>
                <p>
                    These fields help describe the tag to search engines. These fields won't be seen publicly.
                </p>
                <?php

                    $field                = array();
                    $field['key']         = 'seo_title';
                    $field['label']       = 'SEO Title';
                    $field['sub_label']   = 'Max. 150 characters';
                    $field['placeholder'] = 'An alternative, SEO specific title for the tag.';
                    $field['default']     = isset($tag->seo_title) ? $tag->seo_title : '';

                    echo form_field($field);

                    // --------------------------------------------------------------------------

                    $field                = array();
                    $field['key']         = 'seo_description';
                    $field['label']       = 'SEO Description';
                    $field['sub_label']   = 'Max. 300 characters';
                    $field['type']        = 'textarea';
                    $field['placeholder'] = 'This text will be read by search engines when they\'re indexing the page. Keep this short and concise.';
                    $field['default']     = isset($tag->seo_description) ? $tag->seo_description : '';

                    echo form_field($field);

                    // --------------------------------------------------------------------------

                    $field                = array();
                    $field['key']         = 'seo_keywords';
                    $field['label']       = 'SEO Keywords';
                    $field['sub_label']   = 'Max. 150 characters';
                    $field['placeholder'] = 'These comma separated keywords help search engines understand the context of the page; stick to 5-10 words.';
                    $field['default']     = isset($tag->seo_keywords) ? $tag->seo_keywords : '';

                    echo form_field($field);

                ?>
            </fieldset>
            <p style="margin-top:1em;">
            <?php

                echo form_submit('submit', 'Save', 'class="awesome"');
                echo anchor(
                    'admin/shop/manage/tag' . $isFancybox,
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

    echo \Nails\Admin\Helper::loadInlineView('utilities/footer', array('items' => $tags));
