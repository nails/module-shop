<div class="group-shop product-availability-notifications edit">
    <p>
        Use the following form to <?=isset($notification) ? 'edit' : 'create' ?> a product availability notification.
    </p>
    <?=form_open()?>
    <fieldset>
        <legend>Basic Information</legend>
        <?php

            $field                = array();
            $field['key']         = 'email';
            $field['label']       = 'Email';
            $field['type']        = 'email';
            $field['placeholder'] = 'The user\'s email address';
            $field['default']     = isset($notification->user->email) ? $notification->user->email : '';
            $field['required']    = true;

            echo form_field($field);

            // --------------------------------------------------------------------------

            $field              = array();
            $field['key']       = 'item';
            $field['label']     = 'Item';
            $field['default']   = isset($notification->product->id) ? $notification->product->id : '';
            $field['default']  .= isset($notification->variation->id) ? ':' . $notification->variation->id : '';
            $field['required']  = true;
            $field['options']   = $productsVariationsFlat;
            $field['class']     = 'select2';

            echo form_field_dropdown($field);

        ?>
    </fieldset>
    <p>
        <?=form_submit('submit', lang('action_save_changes'), 'class="btn btn-primary"')?>
    </p>
    <?=form_close()?>
</div>