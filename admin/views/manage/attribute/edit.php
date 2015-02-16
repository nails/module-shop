<div class="group-shop manage attributes edit">
    <?=form_open(uri_string() . $isModal)?>
    <fieldset>
        <legend>Basic Details</legend>
        <?php

            $field                = array();
            $field['key']         = 'label';
            $field['label']       = 'Label';
            $field['required']    = true;
            $field['placeholder'] = 'The attribute\'s label';
            $field['default']     = isset($attribute->label) ? $attribute->label : '';

            echo form_field($field);

            // --------------------------------------------------------------------------

            $field                = array();
            $field['key']         = 'description';
            $field['label']       = 'Description';
            $field['type']        = 'textarea';
            $field['placeholder'] = 'The attribute\'s description';
            $field['default']     = isset($attribute->description) ? $attribute->description : '';

            echo form_field($field);

        ?>
    </fieldset>
    <p style="margin-top:1em;">
    <?php

        echo form_submit('submit', 'Save', 'class="awesome"');
        echo anchor(
            'admin/shop/manage/attribute' . $isModal,
            'Cancel',
            'class="awesome red confirm" data-title="Are you sure?" data-body="All unsaved changes will be lost."'
        );

    ?>
    </p>
    <?=form_close();?>
</div>
<?php

    echo \Nails\Admin\Helper::loadInlineView('utilities/footer', array('items' => $attributes));
