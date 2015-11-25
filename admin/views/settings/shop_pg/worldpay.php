<div class="group-settings shop-pg">
    <p>
        Configure the <?=$gateway_name?> payment gateway. The following fields can be either found or
        set within the "Installation Administration" area of <?=$gateway_name?>'s Merchant interface.
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
        <?php

            $active = $this->input->post('activeTab') == 'tab-help-settings' ? 'active' : '';

        ?>
        <li class="tab <?=$active?>">
            <a href="#" data-tab="tab-help-settings">Help: Settings for <?=$gateway_name?></a>
        </li>
        <?php

            $active = $this->input->post('activeTab') == 'tab-help-template' ? 'active' : '';

        ?>
        <li class="tab <?=$active?>">
            <a href="#" data-tab="tab-help-template">Help: Templating</a>
        </li>
    </ul>
    <section class="tabs pages">
        <?php

            $active = $this->input->post('activeTab') == 'tab-customise' || !$this->input->post('activeTab') ? 'active' : '';

        ?>
        <div class="tab-page tab-customise <?=$active?> fieldset">
            <p>
                These settings allow you to customise how the customer percieves the payment gateway.
            </p>
            <fieldset>
                <?php

                    $field                = array();
                    $field['key']         = 'omnipay_' . $gateway_slug . '_customise_label';
                    $field['label']       = 'Label';
                    $field['placeholder'] = 'Give this Payment Gateway a custom customer facing label';
                    $field['default']     = appSetting($field['key'], 'shop');

                    echo form_field($field);

                    // --------------------------------------------------------------------------

                    $field            = array();
                    $field['key']     = 'omnipay_' . $gateway_slug . '_customise_img';
                    $field['label']   = 'Image';
                    $field['bucket']  = 'shop-pg-img-' . $gateway_slug;
                    $field['default'] = appSetting($field['key'], 'shop');

                    echo form_field_mm_image($field);

                ?>
            </fieldset>
        </div>
        <?php

            $active = $this->input->post('activeTab') == 'tab-params' ? 'active' : '';

        ?>
        <div class="tab-page tab-params <?=$active?> fieldset">
            <p>
                These settings will be provided by <?=$gateway_name?> and should be entered carefully here.
            </p>
            <fieldset>
            <?php

                $field                = array();
                $field['key']         = 'omnipay_' . $gateway_slug . '_installationId';
                $field['label']       = 'Installation ID';
                $field['default']     = appSetting($field['key'], 'shop');
                $field['placeholder'] = 'The Installation ID for this shop.';
                $field['required']    = true;

                echo form_field($field);

                // --------------------------------------------------------------------------

                $field                = array();
                $field['key']         = 'omnipay_' . $gateway_slug . '_accountId';
                $field['label']       = 'Administratrion Code';
                $field['default']     = appSetting($field['key'], 'shop');
                $field['placeholder'] = 'The Account ID for this shop.';
                $field['required']    = true;

                echo form_field($field);

            ?>
            </fieldset>
            <p>
                These settings are defined by the user, it is important that details are entered <strong>exactly</strong>
                as they are on <?=$gateway_name?>.
            </p>
            <fieldset>
            <?php

                $field                = array();
                $field['key']         = 'omnipay_' . $gateway_slug . '_callbackPassword';
                $field['label']       = 'Payment Response Password';
                $field['info']        = '<a href="#" id="generate-password" class="btn btn-xs btn-default">Generate</a>';
                $field['default']     = appSetting($field['key'], 'shop');
                $field['placeholder'] = 'The Payment Response Password for this installation.';
                $field['type']        = 'password';
                $field['required']    = true;
                $field['id']          = 'the-password';
                $field['tip']         = 'This should be sufficiently hard to guess and definitely never revealed to anyone.';

                echo form_field($field);

                // --------------------------------------------------------------------------

                $field                = array();
                $field['key']         = 'omnipay_' . $gateway_slug . '_secretWord';
                $field['label']       = 'MD5 Secret for Transactions';
                $field['info']        = '<a href="#" id="generate-secret" class="btn btn-xs btn-default">Generate</a>';
                $field['default']     = appSetting($field['key'], 'shop');
                $field['placeholder'] = 'The MD5 Secret for this installation\'s transactions.';
                $field['type']        = 'password';
                $field['required']    = true;
                $field['id']          = 'the-secret';
                $field['tip']         = 'This should be sufficiently hard to guess and definitely never revealed to anyone.';

                echo form_field($field);

            ?>
            </fieldset>
        </div>
        <?php

            $active = $this->input->post('activeTab') == 'tab-help-settings' ? 'active' : '';

        ?>
        <div class="tab-page tab-help-settings <?=$active?> fieldset">
            <p>
                The following settings are not configurable at this end, but should be set in the
                <?=$gateway_name?> Installation Administration area.
            </p>
            <fieldset>
                <?php

                    $field                = array();
                    $field['key']         = '';
                    $field['label']       = 'Description';
                    $field['placeholder'] = 'Anything you like, something descriptive!';
                    $field['readonly']    = true;

                    echo form_field($field);

                    // --------------------------------------------------------------------------

                    $field                = array();
                    $field['key']         = '';
                    $field['label']       = 'Customer Description';
                    $field['placeholder'] = 'Anything you like, something descriptive!';
                    $field['readonly']    = true;

                    echo form_field($field);

                    // --------------------------------------------------------------------------

                    $field             = array();
                    $field['key']      = '';
                    $field['label']    = 'Store-Builder used:';
                    $field['default']  = 'Other - Please specify below';
                    $field['readonly'] = true;

                    echo form_field($field);

                    // --------------------------------------------------------------------------

                    $field             = array();
                    $field['key']      = '';
                    $field['label']    = 'Store-builder: if other - please specify';
                    $field['default']  = 'Nails Shop';
                    $field['readonly'] = true;

                    echo form_field($field);

                    // --------------------------------------------------------------------------

                    $shop_url = appSetting('url', 'shop') ? appSetting('url', 'shop') : 'shop/';

                    $field              = array();
                    $field['key']       = '';
                    $field['label']     = 'Payment Response URL';
                    $field['default']   = site_url('api/shop/webhook/worldpay');
                    $field['readonly']  = true;
                    $field['info']      = 'Please note that this URL will vary between installations. The URL shown ';
                    $field['info']     .= 'above is correct for the <strong>' . ENVIRONMENT . '</strong> (or equivilent) ';
                    $field['info']     .= 'installation. If ' . $gateway_name . ' is having problems confirming payments ensure this URL ';
                    $field['info']     .= 'is worldwide accessible.';

                    echo form_field($field);

                    // --------------------------------------------------------------------------

                    $field             = array();
                    $field['key']      = '';
                    $field['label']    = 'Payment Response enabled?';
                    $field['default']  = true;
                    $field['readonly'] = true;

                    echo form_field_boolean($field);

                    // --------------------------------------------------------------------------

                    $field             = array();
                    $field['key']      = '';
                    $field['label']    = 'Enable Recurring Payment Response';
                    $field['default']  = true;
                    $field['readonly'] = true;

                    echo form_field_boolean($field);

                    // --------------------------------------------------------------------------

                    $field             = array();
                    $field['key']      = '';
                    $field['label']    = 'Enable the Shopper Response';
                    $field['default']  = false;
                    $field['readonly'] = true;

                    echo form_field_boolean($field);

                    // --------------------------------------------------------------------------

                    $field             = array();
                    $field['key']      = '';
                    $field['label']    = 'Suspension of Payment Response';
                    $field['default']  = false;
                    $field['readonly'] = true;

                    echo form_field_boolean($field);

                    // --------------------------------------------------------------------------

                    $field                = array();
                    $field['key']         = '';
                    $field['label']       = 'Payment Response failure email address';
                    $field['placeholder'] = 'Email address where you\'d like to receive failure notifications';
                    $field['readonly']    = true;

                    echo form_field($field);

                    // --------------------------------------------------------------------------

                    $field             = array();
                    $field['key']      = '';
                    $field['label']    = 'Attach HTTP(s) Payment Message to the failure email?';
                    $field['default']  = true;
                    $field['readonly'] = true;

                    echo form_field_boolean($field);

                    // --------------------------------------------------------------------------

                    $field                = array();
                    $field['key']         = '';
                    $field['label']       = 'Merchant receipt email address';
                    $field['placeholder'] = 'Email address where you\'d like to receive receipts';
                    $field['readonly']    = true;

                    echo form_field($field);

                    // --------------------------------------------------------------------------

                    $field                = array();
                    $field['key']         = '';
                    $field['label']       = 'Info servlet password';
                    $field['placeholder'] = 'Leave blank';
                    $field['readonly']    = true;

                    echo form_field($field);

                    // --------------------------------------------------------------------------

                    $field             = array();
                    $field['key']      = '';
                    $field['label']    = 'Signature Fields';
                    $field['default']  = 'instId:amount:currency:cartId';
                    $field['readonly'] = true;

                    echo form_field($field);

                ?>
            </fieldset>
        </div>
        <?php

            $active = $this->input->post('activeTab') == 'tab-help-template' ? 'active' : '';

        ?>
        <div class="tab-page tab-help-template <?=$active?> fieldset">
            <p>
                In order to make the checkout experience more pleasant for the user, we recommend replacing
                the default <?=$gateway_name?> template files with these ones:
            </p>
            <fieldset>
                <ul>
                    <li>
                        <a href="#template-resultY" class="fancybox">resultY.html</a>
                        - The page the user sees when checkout is successfull.
                    </li>
                    <li>
                        <a href="#template-resultC" class="fancybox">resultC.html</a>
                        - The page the user sees when checkout is cancelled.
                    </li>
                </ul>
            </fieldset>
        </div>
    </section>
    <p>
        <button type="submit" class="btn btn-primary">
            Save Changes
        </button>
    </p>
    <?=form_close()?>
</div>
<div type="text/template" id="template-resultY" style="display:none">
<p style="max-width:100%;box-sizing:border-box;" class="alert alert-warning">
    Use the HTML code below to create a file called <code>resultY.html</code> and upload it into the
    file management area of <?=$gateway_name?>.
    <br />
    <strong>Please note:</strong> The URL mentioned in this template will vary between environments.
</p>
<textarea readonly="readonly" onClick="this.select();" style="width:100%;box-sizing:border-box;height:400px;margin:0;">
<?php
$app_name          = APP_NAME;
$shop_url          = appSetting('url', 'shop') ? appSetting('url', 'shop') : 'shop/';
$processing_url    = site_url($shop_url . 'checkout/processing');
$html = <<<EOT
<html>
    <head>
        <title>Please Wait...</title>
        <meta http-equiv="content-type" content="text/html;charset=utf-8" />
        <meta http-equiv="refresh" content="3;URL='$processing_url'" />
        <style type="text/css">

            body
            {
                font-family:helvetica,arial,sans-serif;
                font-size:12px;
                text-align:Center;
                padding:30px;
            }

            h2
            {
                font-size:12px;
                margin-bottom:40px;
            }

            input[type=submit]
            {
                border:1px solid #ccc;
                background:#ececec;
                padding:7px;
                cursor:pointer;
                border-radius:3px;
                -webkit-border-radius:3px;
                -moz-border-radius:3px;
            }

        </style>
    </head>
    <body>
        <h1>
            Please wait while we redirect
            <br />you back to $app_name
        </h1>
        <p>
            Your payment was accepted.
        </p>
    </body>
</html>
EOT;

    echo htmlspecialchars($html);
?>
</textarea>
</div>
<div type="text/template" id="template-resultC" style="display:none">
<p style="width:100%;box-sizing:border-box;" class="alert alert-warning">
    Use the HTML code below to create a file called <code>resultC.html</code> and upload it into the
    file management area of <?=$gateway_name?>.
    <br />
    <strong>Please note:</strong> The URL mentioned in this template will vary between environments.
</p>
<textarea readonly="readonly" onClick="this.select();" style="width:100%;box-sizing:border-box;height:400px;margin:0;">
<?php
$app_name          = APP_NAME;
$shop_url          = appSetting('url', 'shop') ? appSetting('url', 'shop') : 'shop/';
$processing_url    = site_url($shop_url . 'checkout/cancel');
$html = <<<EOT
<html>
    <head>
        <title>Please Wait...</title>
        <meta http-equiv="content-type" content="text/html;charset=utf-8" />
        <meta http-equiv="refresh" content="3;URL='$processing_url'" />
        <style type="text/css">

            body
            {
                font-family:helvetica,arial,sans-serif;
                font-size:12px;
                text-align:Center;
                padding:30px;
            }

            h2
            {
                font-size:12px;
                margin-bottom:40px;
            }

            input[type=submit]
            {
                border:1px solid #ccc;
                background:#ececec;
                padding:7px;
                cursor:pointer;
                border-radius:3px;
                -webkit-border-radius:3px;
                -moz-border-radius:3px;
            }

        </style>
    </head>
    <body>
        <h1>
            Please wait while we redirect
            <br />you back to $app_name
        </h1>
        <p>
            Your payment was cancelled.
        </p>
    </body>
</html>
EOT;

    echo htmlspecialchars($html);
?>
</textarea>
</div>
