<div class="group-shop manage product-type edit">
    <?=form_open(uri_string() . $isModal)?>
    <fieldset>
        <legend>Basic Details</legend>
        <p>
            These fields describe the product type.
        </p>
        <?php

            $field                = array();
            $field['key']         = 'label';
            $field['label']       = 'Label';
            $field['required']    = true;
            $field['default']     = isset($product_type->label) ? $product_type->label : '';
            $field['placeholder'] = 'The name of this type of product, e.g. Books.';

            echo form_field($field);

            // --------------------------------------------------------------------------

            $field                = array();
            $field['key']         = 'description';
            $field['label']       = 'Description';
            $field['type']        = 'textarea';
            $field['placeholder'] = 'Describe the product type clearly, mainly for the benefit of other admins.';
            $field['default']     = isset($product_type->description) ? $product_type->description : '';

            echo form_field($field);

            // --------------------------------------------------------------------------

            $field            = array();
            $field['key']     = 'is_physical';
            $field['label']   = 'Is Physical';
            $field['default'] = isset($product_type->is_physical) ? $product_type->is_physical : true;

            echo form_field_boolean($field);

        ?>
    </fieldset>
    <fieldset>
        <legend>Advanced Configurations</legend>
        <p class="system-alert message">
            These fields provide granular control over the product type's behaviour. Setting or changing
            these values will alter the way the shop behaves.
            <br /><strong>Use with extreme caution</strong>.
        </p>
        <?php

            // --------------------------------------------------------------------------

            $field                = array();
            $field['key']         = 'max_per_order';
            $field['label']       = 'Max Per Order';
            $field['required']    = true;
            $field['default']     = isset($product_type->max_per_order) ? $product_type->max_per_order : '';
            $field['placeholder'] = 'Maximum number of times this particular product can be added to the basket. Specify 0 for unlimited.';

            echo form_field($field, 'Limit the number of times an individual product can be added to an order. This only applies to a single product, i.e. an item with a limit of 1 can only be added once, but multiple (different) products of the same type can be added, but only once each. Specify 0 for unlimited.');

            // --------------------------------------------------------------------------

            $field                = array();
            $field['key']         = 'max_variations';
            $field['label']       = 'Max Variations';
            $field['placeholder'] = 'The maximum number of variations this type of product can have. Specify 0 for unlimited.';
            $field['default']     = isset($product_type->max_variations) ? $product_type->max_variations : '';

            echo form_field($field, 'Define the number of variations this product can have. Specify 0 for unlimited variations.');

            // --------------------------------------------------------------------------

            $field                = array();
            $field['key']         = 'ipn_method';
            $field['label']       = 'IPN Method';
            $field['placeholder'] = 'The IPN method to call upon notification of successfull payment.';
            $field['default']     = isset($product_type->ipn_method) ? $product_type->ipn_method : '';

            echo form_field($field, 'This method should be callable within the scope of `shop_order_model`. Do not include the `_process` method name prefix here.');

        ?>
    </fieldset>
    <p style="margin-top:1em;">
    <?php

        echo form_submit('submit', 'Save', 'class="awesome"');
        echo anchor(
            'admin/shop/manage/product_type' . $isModal,
            'Cancel',
            'class="awesome red confirm" data-title="Are you sure?" data-body="All unsaved changes will be lost."'
        );

    ?>
    </p>
    <?=form_close();?>
</div>
<?php

    echo \Nails\Admin\Helper::loadInlineView('utilities/footer', array('items' => $productTypes));
