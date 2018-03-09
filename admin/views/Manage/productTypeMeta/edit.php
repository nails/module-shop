<div class="group-shop manage product-type-meta edit">
    <?=form_open(uri_string() . $isModal)?>
    <fieldset>
        <legend>Basic Details</legend>
        <?php

            // --------------------------------------------------------------------------

            $field                = array();
            $field['key']         = 'label';
            $field['label']       = 'Label';
            $field['required']    = true;
            $field['default']     = isset($meta_field->label) ? $meta_field->label : '';
            $field['placeholder'] = 'The label to give this field, e.g., Colour';

            echo form_field($field);

            // --------------------------------------------------------------------------

            $field                = array();
            $field['key']         = 'admin_form_sub_label';
            $field['label']       = 'Admin: Sub Label';
            $field['placeholder'] = 'The sub label, shown beneath the text on the left using a smaller font size on the Inventory Create and Edit pages';
            $field['default']     = isset($meta_field->admin_form_sub_label) ? $meta_field->admin_form_sub_label : '';

            echo form_field($field);

            // --------------------------------------------------------------------------

            $field                = array();
            $field['key']         = 'admin_form_placeholder';
            $field['label']       = 'Admin: Placeholder';
            $field['default']     = isset($meta_field->admin_form_placeholder) ? $meta_field->admin_form_placeholder : '';
            $field['placeholder'] = 'Placeholder text in admin on the Inventory Create and Edit pages, in the same way that this one is being shown now';

            echo form_field($field);

            // --------------------------------------------------------------------------

            $field                = array();
            $field['key']         = 'admin_form_tip';
            $field['label']       = 'Admin: Tip';
            $field['placeholder'] = 'Tip for this field when rendered on the Inventory Create and Edit pages';
            $field['default']     = isset($meta_field->admin_form_tip) ? $meta_field->admin_form_tip : '';
            $field['tip']         = 'This text will be shown as a tooltip in admin, in the same way that this one is being shown now';

            echo form_field($field);

            // --------------------------------------------------------------------------

            $field             = array();
            $field['key']      = 'allow_multiple';
            $field['label']    = 'Allow Multiple Selections';
            $field['tip']      = 'Allow admin to specify more than one value per variant.';
            $field['text_on']  = lang('yes');
            $field['text_off'] = lang('no');
            $field['default']  = isset($meta_field->allow_multiple) ? $meta_field->allow_multiple : false;

            echo form_field_boolean($field);

            // --------------------------------------------------------------------------

            $field             = array();
            $field['key']      = 'is_filter';
            $field['label']    = 'Is Filter';
            $field['tip']      = 'Allow this field to act as a product filter on the front end.';
            $field['text_on']  = lang('yes');
            $field['text_off'] = lang('no');
            $field['default']  = isset($meta_field->allow_multiple) ? $meta_field->allow_multiple : false;

            echo form_field_boolean($field);

        ?>
    </fieldset>
    <fieldset>
        <legend>Associated Product Types</legend>
        <p>
            Specify which product types should inherit this meta field. Meta fields form the
            basis of the sidebar filters in the front end.
        </p>
        <select name="associated_product_types[]" multiple="multiple" class="select2" data-placeholder="Select product types">
            <option value=""></option>
            <?php

                $selected = array();

                if ($this->input->post('associated_product_types')) {

                    $selected[] = $this->input->post('associated_product_types');

                } elseif (isset($meta_field->associated_product_types)) {

                    foreach ($meta_field->associated_product_types as $productType) {

                        $selected[] = $productType->id;
                    }
                }

                foreach ($productTypes as $type) {

                    $is_selected = array_search($type->id, $selected) !== false ? 'selected="selected"' : '';

                    echo '<option value="' . $type->id . '" ' .$is_selected . '>';
                        echo $type->label;
                    echo '</option>';
                }

            ?>
        </select>
    </fieldset>
    <p style="margin-top:1em;">
    <?php

        echo form_submit('submit', 'Save', 'class="btn btn-primary"');
        anchor(
            'admin/shop/manage/product_type_meta' . $isModal,
            'Cancel',
            'class="btn btn-danger confirm" data-body="All unsaved changes will be lost."'
        );

    ?>
    </p>
    <?=form_close();?>
</div>