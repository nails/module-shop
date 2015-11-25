<div class="group-shop manage collections edit">
    <?=form_open(uri_string() . $isModal)?>
    <fieldset>
        <legend>Basic Details</legend>
        <p>
            These fields describe the collection.
        </p>
        <?php

            $field                = array();
            $field['key']         = 'label';
            $field['label']       = 'Label';
            $field['required']    = true;
            $field['default']     = isset($collection->label) ? $collection->label : '';
            $field['placeholder'] = 'The label to give your collection';

            echo form_field($field);

            // --------------------------------------------------------------------------

            $field            = array();
            $field['key']     = 'cover_id';
            $field['label']   = 'Cover Image';
            $field['default'] = isset($collection->cover_id) ? $collection->cover_id : '';
            $field['bucket']  = 'shop-collection-cover';

            echo form_field_mm_image($field);

            // --------------------------------------------------------------------------

            $field                = array();
            $field['key']         = 'description';
            $field['label']       = 'Description';
            $field['placeholder'] = 'This text may be used on the collection\'s overview page.';
            $field['default']     = isset($collection->description) ? $collection->description : '';

            echo form_field_wysiwyg($field);

            // --------------------------------------------------------------------------

            $field            = array();
            $field['key']     = 'is_active';
            $field['label']   = 'Active';
            $field['default'] = isset($collection->is_active) ? $collection->is_active : true;

            echo form_field_boolean($field);

        ?>
    </fieldset>
    <fieldset>
        <legend>Search Engine Optimisation</legend>
        <p>
            These fields help describe the collection to search engines. These fields won't be seen publicly.
        </p>
        <?php

            $field                = array();
            $field['key']         = 'seo_title';
            $field['label']       = 'SEO Title';
            $field['sub_label']   = 'Max. 150 characters';
            $field['placeholder'] = 'An alternative, SEO specific title for the collection.';
            $field['default']     = isset($collection->seo_title) ? $collection->seo_title : '';

            echo form_field($field);

            // --------------------------------------------------------------------------

            $field                = array();
            $field['key']         = 'seo_description';
            $field['label']       = 'SEO Description';
            $field['sub_label']   = 'Max. 300 characters';
            $field['type']        = 'textarea';
            $field['placeholder'] = 'This text will be read by search engines when they\'re indexing the page. Keep this short and concise.';
            $field['default']     = isset($collection->seo_description) ? $collection->seo_description : '';

            echo form_field($field);

            // --------------------------------------------------------------------------

            $field                = array();
            $field['key']         = 'seo_keywords';
            $field['label']       = 'SEO Keywords';
            $field['sub_label']   = 'Max. 150 characters';
            $field['placeholder'] = 'These comma separated keywords help search engines understand the context of the page; stick to 5-10 words.';
            $field['default']     = isset($collection->seo_keywords) ? $collection->seo_keywords : '';

            echo form_field($field);

        ?>
    </fieldset>
    <p style="margin-top:1em;">
    <?php

        echo form_submit('submit', 'Save', 'class="btn btn-primary"');
        echo anchor(
            'admin/shop/manage/collection' . $isModal,
            'Cancel',
            'class="btn btn-danger confirm" data-body="All unsaved changes will be lost."'
        );

    ?>
    </p>
    <?=form_close();?>
</div>
<?php

    echo adminHelper('loadInlineView', 'utilities/footer', array('items' => $collections));
