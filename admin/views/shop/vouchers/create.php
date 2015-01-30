<div class="group-shop vouchers create">
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
            $field['required'] = TRUE;

            $options = array(
                'NORMAL'      => 'Normal',
                'LIMITED_USE' => 'Limited use',
                'GIFT_CARD'   => 'Gift Card'
           );

            echo form_field_dropdown($field, $options);

            // --------------------------------------------------------------------------

            //  Code
            $field                = array();
            $field['key']         = 'code';
            $field['label']       = 'Code';
            $field['sub_label']   = '<a href="#" id="generate-code">Generate Valid Code</a>';
            $field['placeholder'] = 'Define the code for this voucher or generate one using the link on the left.';
            $field['required']    = TRUE;

            echo form_field($field);

            // --------------------------------------------------------------------------

            //  Label
            $field                = array();
            $field['key']         = 'label';
            $field['label']       = 'Label/Description';
            $field['placeholder'] = 'The label is shown to the user when the voucher is applied.';
            $field['required']    = TRUE;

            echo form_field($field);

            // --------------------------------------------------------------------------

            //  Discount type
            $field             = array();
            $field['key']      = 'discount_type';
            $field['label']    = 'Discount Type';
            $field['class']    = 'select2';
            $field['required'] = TRUE;

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
            $field['required']    = TRUE;

            echo form_field($field, 'If Discount Type is Percentage then specify a number 1-100, if it\'s a Specific Amount then define the amount.');

            // --------------------------------------------------------------------------

            //  Discount application
            $field             = array();
            $field['key']      = 'discount_application';
            $field['label']    = 'Applies to';
            $field['class']    = 'select2';
            $field['required'] = TRUE;

            $options = array(
                'PRODUCTS'      => 'Purchases Only',
                'PRODUCT_TYPES' => 'Certain Type of Product Only',
                'SHIPPING'      => 'Shipping Costs Only',
                'ALL'           => 'Both Products and Shipping'
           );

            echo form_field_dropdown($field, $options);

            // --------------------------------------------------------------------------

            //  Valid from
            $field                = array();
            $field['key']         = 'valid_from';
            $field['label']       = 'Valid From';
            $field['default']     = date('Y-m-d H:i:s', strtotime('TODAY'));
            $field['placeholder'] = 'YYYY-MM-DD HH:MM:SS';
            $field['class']       = 'datetime1';
            $field['required']    = TRUE;

            echo form_field($field);

            // --------------------------------------------------------------------------

            //  Valid To
            $field                = array();
            $field['key']         = 'valid_to';
            $field['label']       = 'Valid To';
            $field['sub_label']   = 'Leave blank for no expiry date';
            $field['placeholder'] = 'YYYY-MM-DD HH:MM:SS';
            $field['class']       = 'datetime2';

            echo form_field($field, 'If left blank then the voucher will not expire (unless another expiring condition is met).');

        ?>
    </fieldset>
    <fieldset id="create-voucher-meta">
        <legend>Extended Data</legend>
        <div id="no-extended-data" style="display:block;">
            <p class="system-alert">
                <strong>Note:</strong> More options may become available depending on your choices above.
            </p>
        </div>
        <div id="type-limited" style="display:none;">
            <?php

            //  Limited Use Limit
            $field                = array();
            $field['key']         = 'limited_use_limit';
            $field['label']       = 'Limit number of uses';
            $field['placeholder'] = 'Define the number of times this voucher can be used.';
            $field['required']    = TRUE;

            echo form_field($field);

            ?>
        </div>
        <div id="application-product_types" style="display:none;">
            <?php

            if (empty($product_types)) {

                echo '<p class="system-alert error">';
                    echo '<strong>No product types are defined</strong>';
                    echo '<br />At least one product type must be defined before you can create vouchers ';
                    echo 'which apply to particular product types.';
                echo '</p>';

            } else {

                //  Product Types application
                $field             = array();
                $field['key']      = 'product_type_id';
                $field['label']    = 'Limit to products of type';
                $field['required'] = TRUE;
                $field['class']    = 'select2';

                echo form_field_dropdown($field, $product_types);

            }

            ?>
        </div>
    </fieldset>
    <p>
        <?=form_submit('submit', lang('action_create'), 'class="awesome"')?>
    </p>
    <?=form_close()?>
</div>
<script type="text/javascript">
    $(function(){

        var _shop_voucher = new NAILS_Admin_Shop_Vouchers;
        _shop_voucher.init_create();

    })
</script>