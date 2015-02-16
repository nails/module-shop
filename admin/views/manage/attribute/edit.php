<div class="group-shop manage attributes edit">
    <?php

        if ($isFancybox) {

            echo '<h1>' . $page->title . '</h1>';
            $class = 'system-alert';

        } else {

            $class = '';
        }

        echo form_open(uri_string() . $isFancybox);

    ?>
    <p class="<?=$class?>">
        Manage which attributes are available for your products.
    </p>
    <?=$isFancybox ? '' : '<hr />'?>
    <ul class="tabs disabled">
        <li class="tab">
            <?=anchor('admin/shop/manage/attribute' . $isFancybox, 'Overview', 'class="confirm" data-title="Are you sure?" data-body="Any unsaved changes will be lost."')?>
        </li>
        <li class="tab active">
            <?=anchor('admin/shop/manage/attribute/create' . $isFancybox, 'Create Attribute')?>
        </li>
    </ul>
    <section class="tabs pages">
        <div class="tab page active">
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
                    'admin/shop/manage/attribute' . $isFancybox,
                    'Cancel',
                    'class="awesome red confirm" data-title="Are you sure?" data-body="All unsaved changes will be lost."'
                );

            ?>
            </p>
        </div>
    </section>
    <?=form_close();?>
</div>
<?php

    echo \Nails\Admin\Helper::loadInlineView('utilities/footer', array('items' => $attributes));
