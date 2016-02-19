<div class="group-settings shop-pg">
    <p>
        Configure the <?=$gateway_name?> payment gateway.
    </p>
    <hr />
    <?php

        echo form_open('admin/shop/settings/shop_pg?gateway=' . $gateway_slug . '&isModal=' . $isModal);
        echo '<input type="hidden" name="activeTab" value="' . set_value('activeTab') . '" id="activeTab" />';
    ?>
    <ul class="tabs">
        <?php

            $active = $this->input->post('activeTab') == 'tab-customise' || !$this->input->post('activeTab') ? 'active' : '';

        ?>
        <li class="tab <?=$active?>">
            <a href="#" data-tab="tab-customise">Customise</a>
        </li>
        <?php

            $active = $this->input->post('activeTab') == 'tab-params' ? 'active' : '';

        ?>
        <li class="tab <?=$active?>">
            <a href="#" data-tab="tab-params">Gateway Parameters</a>
        </li>
    </ul>
    <section class="tabs">
        <?php

            $active = $this->input->post('activeTab') == 'tab-customise' || !$this->input->post('activeTab') ? 'active' : '';

        ?>
        <div class="tab-page tab-customise <?=$active?> fieldset">
            <p>
                These settings allow you to customise how the customer percieves the payment gateway.
            </p>
            <hr />
            <?php

                $field                = array();
                $field['key']         = 'omnipay_' . $gateway_slug . '_customise_label';
                $field['label']       = 'Label';
                $field['placeholder'] = 'Give this Payment Gateway a custom customer facing label';
                $field['default']     = appSetting($field['key'], 'nailsapp/module-shop');
                $field['tip']         = 'Set this to override the default payment gateway name.';

                echo form_field($field);

                // --------------------------------------------------------------------------

                $field            = array();
                $field['key']     = 'omnipay_' . $gateway_slug . '_customise_img';
                $field['label']   = 'Image';
                $field['bucket']  = 'shop-pg-img-' . $gateway_slug;
                $field['default'] = appSetting($field['key'], 'nailsapp/module-shop');
                $field['tip']     = 'No image is shown by default, but you can choose to show one, perhaps a logo, or an image showing which cards are accepted.';

                echo form_field_cdn_object_picker($field);

            ?>
        </div>
        <?php

            $active = $this->input->post('activeTab') == 'tab-params' ? 'active' : '';

        ?>
        <div class="tab-page tab-params <?=$active?> fieldset">
            <p>
                Generally speaking, these fields are provided by <?=$gateway_name?>.
            </p>
            <hr />
            <?php

                foreach ($params as $key => $value) {

                    //  Prep the visible label
                    $label   = $key;
                    $label   = preg_replace('/([a-z])([A-Z])/', '$1 $2', $label);
                    $label   = ucwords($label);
                    $replace = array('API', 'ID', 'URL');
                    $label   = str_ireplace($replace, $replace, $label);

                    $field             = array();
                    $field['key']      = 'omnipay_' . $gateway_slug . '_' . $key;
                    $field['label']    = $label;
                    $field['default']  = appSetting($field['key'], 'nailsapp/module-shop');
                    $field['required'] = true;

                    echo form_field($field);
                }

            ?>
        </div>
    </section>
    <p>
        <button type="submit" class="btn btn-primary">
            Save Changes
        </button>
    </p>
    <?=form_close()?>
</div>
