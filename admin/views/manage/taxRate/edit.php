<div class="group-shop manage tax-rate edit">
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
        Manage which tax rates the shop supports.
    </p>
    <?=$isFancybox ? '' : '<hr />'?>
    <ul class="tabs disabled">
        <li class="tab">
            <?=anchor('admin/shop/manage/tax_rate' . $isFancybox, 'Overview', 'class="confirm" data-title="Are you sure?" data-body="Any unsaved changes will be lost."')?>
        </li>
        <li class="tab active">
            <?=anchor('admin/shop/manage/taxRate/create' . $isFancybox, 'Create Tax Rate')?>
        </li>
    </ul>
    <section class="tabs pages">
        <div class="tab page active">
            <fieldset>
                <legend>Basic Details</legend>
                <p>
                    These fields describe the tax rate.
                </p>
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
            <?php

                echo form_submit('submit', 'Save', 'class="awesome"');
                echo anchor(
                    'admin/shop/manage/tax_rate' . $isFancybox,
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

    echo \Nails\Admin\Helper::loadInlineView('utilities/footer', array('items' => $tax_rates));
