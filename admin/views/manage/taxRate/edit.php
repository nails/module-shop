<div class="group-shop manage tax-rate edit">
<?=form_open(uri_string() . $isModal)?>
    <fieldset>
        <legend>Basic Details</legend>
        <?php

            $field                = array();
            $field['key']         = 'label';
            $field['label']       = 'Label';
            $field['required']    = true;
            $field['placeholder'] = 'The tax rate\'s label';
            $field['default']     = isset($tax_rate->label) ? $tax_rate->label : '';

            echo form_field($field);

            // --------------------------------------------------------------------------

            $field                = array();
            $field['key']         = 'rate';
            $field['label']       = 'Rate';
            $field['required']    = true;
            $field['placeholder'] = 'The tax rate\'s rate';
            $field['default']     = isset($tax_rate->rate) ? $tax_rate->rate : '';
            $field['info']        = 'This should be expressed as a decimal between 0 and 1. For example, 17.5% would be entered as 0.175.';

            echo form_field($field);

        ?>
    </fieldset>
    <p style="margin-top:1em;">
        <?=form_submit('submit', 'Save', 'class="btn btn-primary"')?>
        <?=anchor(
            'admin/shop/manage/taxRate' . $isModal,
            'Cancel',
            'class="btn btn-danger confirm" data-body="All unsaved changes will be lost."'
        )?>
    </p>
    <?=form_close();?>
</div>
<?php

    echo \Nails\Admin\Helper::loadInlineView('utilities/footer', array('items' => $taxRates));
