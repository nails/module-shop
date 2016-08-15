<div class="group-shop vouchers edit">
    <p>
        Use the following form to create a new voucher for use in the shop.
    </p>
    <?=form_open()?>
    <fieldset id="create-voucher-basic">
        <legend>Basic Information</legend>
        <?php

        //  Voucher type
        $field             = array();
        $field['key']      = 'type';
        $field['label']    = 'Type';
        $field['class']    = 'select2';
        $field['required'] = true;

        echo form_field_dropdown($field, $voucherTypes);

        // --------------------------------------------------------------------------

        //  Code
        $field                = array();
        $field['key']         = 'code';
        $field['label']       = 'Code';
        $field['info']        = '<a href="#" id="generate-code" class="btn btn-xs btn-default">Generate Valid Code</a> ';
        $field['info']       .= '<b id="generateCodeSpinner" class="fa fa-spin fa-spinner"></b>';
        $field['placeholder'] = 'Define the code for this voucher or generate one using the link on the left.';
        $field['required']    = true;

        echo form_field($field);

        // --------------------------------------------------------------------------

        //  Label
        $field                = array();
        $field['key']         = 'label';
        $field['label']       = 'Label/Description';
        $field['placeholder'] = 'The label is shown to the user when the voucher is applied.';
        $field['required']    = true;

        echo form_field($field);

        // --------------------------------------------------------------------------

        //  Discount application
        $field             = array();
        $field['key']      = 'discount_application';
        $field['label']    = 'Applies to';
        $field['class']    = 'select2';
        $field['required'] = true;

        $options = array(
            'PRODUCTS'      => 'Products Only',
            'PRODUCT_TYPES' => 'Certain Type of Product Only',
            'PRODUCT'       => 'Certain Product Only',
            'SHIPPING'     => 'Shipping Costs Only',
            'ALL'           => 'Both Products and Shipping'
        );

        echo form_field_dropdown($field, $options);

        // --------------------------------------------------------------------------

        //  Discount type
        $field             = array();
        $field['key']      = 'discount_type';
        $field['label']    = 'Discount Type';
        $field['class']    = 'select2';
        $field['required'] = true;

        $options = array(
            'PERCENTAGE' => 'Percentage',
            'AMOUNT'     => 'Specific amount'
        );

        echo form_field_dropdown($field, $options);

        // --------------------------------------------------------------------------

        //  Discount value
        $field                = array();
        $field['key']         = 'discount_value';
        $field['label']       = 'Discount Value';
        $field['placeholder'] = 'Define the value of the discount as appropriate (i.e percentage or amount)';
        $field['required']    = true;
        $field['tip']         = 'If Discount Type is Percentage then specify a number 1-100, if it\'s a Specific Amount then define the amount.';

        echo form_field($field);

        // --------------------------------------------------------------------------

        //  Valid from
        $field                = array();
        $field['key']         = 'valid_from';
        $field['label']       = 'Valid From';
        $field['default']     = date('Y-m-d H:i:s');
        $field['placeholder'] = 'YYYY-MM-DD HH:MM:SS';
        $field['class']       = 'datetime1';
        $field['required']    = true;

        echo form_field($field);

        // --------------------------------------------------------------------------

        //  Valid To
        $field                = array();
        $field['key']         = 'valid_to';
        $field['label']       = 'Expires';
        $field['sub_label']   = 'Leave blank for no expiry date';
        $field['placeholder'] = 'YYYY-MM-DD HH:MM:SS';
        $field['class']       = 'datetime2';

        echo form_field($field, 'If left blank then the voucher will not expire (unless another expiring condition is met).');

        ?>
    </fieldset>
    <fieldset id="create-voucher-meta">
        <legend>Extended Data</legend>
        <div id="no-extended-data" class="alert alert-warning" style="display:block;">
            More options may become available depending on your choices above.
        </div>
        <div id="type-limited" class="extended-data" style="display:none;">
            <?php

            //  Limited Use Limit
            $field                = array();
            $field['key']         = 'limited_use_limit';
            $field['label']       = 'Limit number of uses';
            $field['placeholder'] = 'Define the number of times this voucher can be used.';
            $field['required']    = true;

            echo form_field($field);

            ?>
        </div>
        <div id="application-product_types" class="extended-data" style="display:none;">
            <?php

            if (empty($product_types)) {

                ?>
                <p class="alert alert-danger">
                    <strong>No product types are defined</strong>
                    <br />At least one product type must be defined before you can create vouchers which apply
                    to particular product types.  <?=anchor('admin/shop/manage/productType', 'Create Products Now')?>
                </p>
                <?php

            } else {

                //  Product Types application
                $field             = array();
                $field['key']      = 'product_type_id';
                $field['label']    = 'Limit to products of type';
                $field['required'] = true;
                $field['class']    = 'select2';

                echo form_field_dropdown($field, $product_types);

            }

            ?>
        </div>
        <div id="application-product" class="extended-data" style="display:none;">
            <?php

            //  Product Types application
            $field             = array();
            $field['key']      = 'product_id';
            $field['label']    = 'Limit to product';
            $field['required'] = true;
            $field['class']    = 'select2';
            $field['id']       = 'product-id';

            echo form_field($field, $product_types);

            ?>
        </div>
    </fieldset>
    <p>
        <?=form_submit('submit', lang('action_create'), 'class="btn btn-primary"')?>
    </p>
    <?=form_close()?>
</div>
