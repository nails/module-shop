<div class="group-settings shop-pg">
    <p <?=!empty($isModal) ? 'class="system-alert"' : ''?>>
        Configure the <?=$gateway_name?> payment gateway.
    </p>
    <hr />
    <?=form_open('admin/settings/shop_pg/' . $this->uri->segment(4) . '?isModal=' . $this->input->get('isModal'))?>
        <ul class="tabs">
            <li class="tab active">
                <a href="#" data-tab="tab-customise">Customise</a>
            </li>
            <li class="tab">
                <a href="#" data-tab="tab-params">Gateway Parameters</a>
            </li>
        </ul>
        <section class="tabs pages">

            <div class="tab page basics active fieldset" id="tab-customise">
                <p>
                    These settings allow you to customise how the customer percieves the payment gateway.
                </p>
                <hr />
                <?php

                    $field                = array();
                    $field['key']         = 'omnipay_' . $gateway_slug . '_customise_label';
                    $field['label']       = 'Label';
                    $field['placeholder'] = 'Give this Payment Gateway a custom customer facing label';
                    $field['default']     = app_setting($field['key'], 'shop');
                    $field['tip']         = 'Set this to override the default payment gateway name.';

                    echo form_field($field);

                    // --------------------------------------------------------------------------

                    $field            = array();
                    $field['key']     = 'omnipay_' . $gateway_slug . '_customise_img';
                    $field['label']   = 'Image';
                    $field['bucket']  = 'shop-pg-img-' . $gateway_slug;
                    $field['default'] = app_setting($field['key'], 'shop');
                    $field['tip']     = 'No image is shown by default, but you can choose to show one, perhaps a logo, or an image showing which cards are accepted.';

                    echo form_field_mm_image($field);

                ?>
            </div>

            <div class="tab page basics fieldset" id="tab-params">
                <p>
                    Generally speaking, these fields are provided by <?=$gateway_name?>.
                </p>
                <hr />
                <?php

                    $field             = array();
                    $field['key']      = 'omnipay_' . $gateway_slug . '_apiKey';
                    $field['label']    = 'Secret Key';
                    $field['default']  = app_setting($field['key'], 'shop');
                    $field['required'] = true;

                    echo form_field($field);

                    // --------------------------------------------------------------------------

                    $field             = array();
                    $field['key']      = 'omnipay_' . $gateway_slug . '_publishableKey';
                    $field['label']    = 'Publishable Key';
                    $field['default']  = app_setting($field['key'], 'shop');
                    $field['required'] = true;

                    echo form_field($field);

                ?>
            </div>
        </section>
        <p>
            <button type="submit" class="awesome">
                Save Changes
            </button>
        </p>
    <?=form_close()?>
</div>