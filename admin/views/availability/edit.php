<div class="group-shop product-availability-notifications edit">
    <p>
        Use the following form to <?=isset($notification) ? 'edit' : 'create' ?> a product availability notification.
    </p>
    <?=form_open()?>
    <fieldset>
        <legend>Basic Information</legend>
        <?php

            $_field                    = array();
            $_field['key']            = 'email';
            $_field['label']        = 'Email';
            $_field['type']            = 'email';
            $_field['placeholder']    = 'The user\'s email address';
            $_field['default']        = isset($notification->user->email) ? $notification->user->email : '';
            $_field['required']        = true;

            echo form_field($_field);

            // --------------------------------------------------------------------------

            $_field                    = array();
            $_field['key']            = 'item';
            $_field['label']        = 'Item';
            $_field['default']        = isset($notification->product->id) ? $notification->product->id : '';
            $_field['default']        .= isset($notification->variation->id) ? ':' . $notification->variation->id : '';
            $_field['required']        = true;
            $_field['class']        = 'select2';

            $_field['options']        = $products_variations_flat;

            echo form_field_dropdown($_field);

        ?>
    </fieldset>
    <p>
        <?=form_submit('submit', lang('action_save_changes'), 'class="awesome"')?>
    </p>
    <?=form_close()?>
</div>