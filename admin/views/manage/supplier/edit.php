<div class="group-shop manage suppliers edit">
    <?=form_open(uri_string() . $isModal)?>
    <fieldset>
        <legend>Basic Details</legend>
        <p>
            These fields describe the supplier.
        </p>
        <?php

            // --------------------------------------------------------------------------

            $field                = array();
            $field['key']         = 'label';
            $field['label']       = 'Label';
            $field['required']    = true;
            $field['default']     = isset($supplier->label) ? $supplier->label : '';
            $field['placeholder'] = 'The label to give your supplier';

            echo form_field($field);

            // --------------------------------------------------------------------------

            $field            = array();
            $field['key']     = 'is_active';
            $field['label']   = 'Active';
            $field['default'] = isset($supplier->is_active) ? $supplier->is_active : true;

            echo form_field_boolean($field);

        ?>
    </fieldset>
    <p style="margin-top:1em;">
    <?php

        echo form_submit('submit', 'Save', 'class="awesome"');
        echo anchor(
            'admin/shop/manage/supplier' . $isModal,
            'Cancel',
            'class="awesome red confirm" data-title="Are you sure?" data-body="All unsaved changes will be lost."'
        );

    ?>
    </p>
    <?=form_close();?>
</div>
<?php

    echo \Nails\Admin\Helper::loadInlineView('utilities/footer', array('items' => $suppliers));
