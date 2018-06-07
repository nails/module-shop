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
            <?php

                echo '<fieldset>';
                    echo '<legend>Checkout Customisation</legend>';
                    echo '<p>These customisations will be applied on <em>this</em> site.</p>';

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

                echo '</fieldset>';

                // --------------------------------------------------------------------------

                echo '<fieldset>';
                    echo '<legend>PayPal Customisation</legend>';
                    echo '<p>These customisations apply to the PayPal interface.</p>';

                    $field                = array();
                    $field['key']         = 'omnipay_' . $gateway_slug . '_brandName';
                    $field['label']       = 'Brand Name';
                    $field['default']     = appSetting($field['key'], 'nailsapp/module-shop');
                    $field['placeholder'] = 'Choose a name for your store, as seen on PayPal. Defaults to PayPal settings.';
                    $field['required']    = false;

                    echo form_field($field);

                    // --------------------------------------------------------------------------

                    $field            = array();
                    $field['key']     = 'omnipay_' . $gateway_slug . '_headerImageUrl';
                    $field['label']   = 'Header Image';
                    $field['default'] = appSetting($field['key'], 'nailsapp/module-shop');
                    $field['bucket']  = 'shop-pg-img-' . strtolower($gateway_slug) . '-header';
                    $field['tip']     = 'Ideal dimensions are 750x90px';

                    echo form_field_cdn_object_picker($field);

                    // --------------------------------------------------------------------------

                    $field             = array();
                    $field['key']      = 'omnipay_' . $gateway_slug . '_logoImageUrl';
                    $field['label']    = 'Logo';
                    $field['default']  = appSetting($field['key'], 'nailsapp/module-shop');
                    $field['required'] = false;
                    $field['bucket']   = 'shop-pg-img-' . strtolower($gateway_slug) . '-logo';
                    $field['tip']      = 'Ideal dimensions are 190x60px';

                    echo form_field_cdn_object_picker($field);

                    // --------------------------------------------------------------------------

                    $field                = array();
                    $field['key']         = 'omnipay_' . $gateway_slug . '_borderColor';
                    $field['label']       = 'Border Color';
                    $field['default']     = appSetting($field['key'], 'nailsapp/module-shop');
                    $field['required']    = false;
                    $field['placeholder'] = 'e.g., CCCCCC';
                    $field['info']        = 'If supplied, this should be a 6 character hex code, without the # symbol, e.g. FFFFFF for white';

                    echo form_field($field);

                echo '</fieldset>';

            ?>
        </div>
        <?php

            $active = $this->input->post('activeTab') == 'tab-params' ? 'active' : '';

        ?>
        <div class="tab-page tab-params <?=$active?> fieldset">
            <fieldset>
                <legend>API Credentials</legend>
                <p>
                    You can get these details from within your PayPal account's settings.
                    <b class="fa fa-question-circle fa-lg" rel="tipsy" title="You'll want &quot;Option 2&quot; in &quot;API Access&quot; under &quot;My Selling Preferences&quot;"></b>
                </p>
                <?php

                    $field             = array();
                    $field['key']      = 'omnipay_' . $gateway_slug . '_username';
                    $field['label']    = 'API Username';
                    $field['default']  = appSetting($field['key'], 'nailsapp/module-shop');
                    $field['required'] = true;

                    echo form_field($field);

                    // --------------------------------------------------------------------------

                    $field             = array();
                    $field['key']      = 'omnipay_' . $gateway_slug . '_password';
                    $field['label']    = 'API Password';
                    $field['default']  = appSetting($field['key'], 'nailsapp/module-shop');
                    $field['required'] = true;

                    echo form_field_password($field);

                    // --------------------------------------------------------------------------

                    $field             = array();
                    $field['key']      = 'omnipay_' . $gateway_slug . '_signature';
                    $field['label']    = 'API Signature';
                    $field['default']  = appSetting($field['key'], 'nailsapp/module-shop');
                    $field['required'] = true;

                    echo form_field($field);

                ?>
            </fieldset>
            <fieldset>
                <legend>Other Configuration</legend>
                <?php

                    $field             = array();
                    $field['key']      = 'omnipay_' . $gateway_slug . '_solutionType';
                    $field['label']    = 'Solution Type';
                    $field['default']  = appSetting($field['key'], 'nailsapp/module-shop');
                    $field['required'] = true;
                    $field['class']    = 'select2';

                    $field['options']         = array();
                    $field['options']['Sole'] = 'Sole - Buyer does not need to create a PayPal account to check out';
                    $field['options']['Mark'] = 'Mark - Buyer must have a PayPal account to check out';

                    echo form_field_dropdown($field);

                    // --------------------------------------------------------------------------

                    $field             = array();
                    $field['key']      = 'omnipay_' . $gateway_slug . '_landingPage';
                    $field['label']    = 'Landing Page';
                    $field['default']  = appSetting($field['key'], 'nailsapp/module-shop');
                    $field['required'] = true;
                    $field['class']    = 'select2';

                    $field['options']            = array();
                    $field['options']['Billing'] = 'Billing – Non-PayPal account';
                    $field['options']['Login']   = 'Login – PayPal account login';

                    echo form_field_dropdown($field);

                ?>
            </fieldset>
        </div>
    </section>
    <div class="admin-floating-controls">
        <button type="submit" class="btn btn-primary">
            Save Changes
        </button>
    </div>
    <?=form_close()?>
</div>