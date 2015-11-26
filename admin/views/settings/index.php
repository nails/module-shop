<div class="group-settings shop">
    <p>
        Configure various aspects of the shop.
    </p>
    <?php

        echo form_open();
        $sActiveTab = $this->input->post('active_tab') ?: 'tab-general';
        echo '<input type="hidden" name="active_tab" value="' . $sActiveTab . '" id="active-tab">';

    ?>
    <ul class="tabs" data-tabgroup="main-tabs" data-active-tab-input="#active-tab">
        <li class="tab">
            <a href="#" data-tab="tab-general">General</a>
        </li>
        <li class="tab">
            <a href="#" data-tab="tab-browse">Browsing</a>
        </li>
        <li class="tab">
            <a href="#" data-tab="tab-skin">Skin</a>
        </li>
        <li class="tab">
            <a href="#" data-tab="tab-payment-gateway">Payment Gateways</a>
        </li>
        <li class="tab">
            <a href="#" data-tab="tab-currencies">Currencies</a>
        </li>
        <li class="tab">
            <a href="#" data-tab="tab-shipping">Shipping</a>
        </li>
        <li class="tab">
            <a href="#" data-tab="tab-pages">Pages</a>
        </li>
    </ul>
    <section class="tabs" data-tabgroup="main-tabs">
        <div class="tab-page tab-general">
            <p>
                Generic store settings. Use these to control some store behaviours.
            </p>
            <fieldset id="shop-settings-online">
                <legend>Maintenance</legend>
                <p>
                    Is the shop enabled?
                </p>
                <?php

                    $field            = array();
                    $field['key']     = 'maintenance_enabled';
                    $field['label']   = 'Maintenance Mode';
                    $field['default'] = (bool) appSetting($field['key'], 'shop');
                    $field['tip']     = 'Use this field to temporarily disable the shop, e.g. for extended maintenance.';

                    echo form_field_boolean($field);

                    $display = $field['default'] ? 'block' : 'none';

                ?>
                <div id="shop-maintenance-extras" style="display:<?=$display?>">
                    <?php

                        $field                = array();
                        $field['key']         = 'maintenance_title';
                        $field['label']       = 'Maintenance Title';
                        $field['default']     = appSetting($field['key'], 'shop') ? appSetting($field['key'], 'shop') : '';
                        $field['placeholder'] = 'Customise what the maintenance page title is';

                        echo form_field($field);

                        // --------------------------------------------------------------------------

                        $field                = array();
                        $field['key']         = 'maintenance_body';
                        $field['label']       = 'Maintenance Body';
                        $field['default']     = appSetting($field['key'], 'shop') ? appSetting($field['key'], 'shop') : '';
                        $field['placeholder'] = 'Customise what the maintenance page body text is';

                        echo form_field_wysiwyg($field);

                    ?>
                </div>
            </fieldset>
            <fieldset id="shop-settings-name">
                <legend>Name &amp; URL</legend>
                <?php

                    //  Shop Name
                    $field                = array();
                    $field['key']         = 'name';
                    $field['label']       = 'Shop Name';
                    $field['default']     = appSetting($field['key'], 'shop') ? appSetting($field['key'], 'shop') : 'Shop';
                    $field['placeholder'] = 'Customise the Shop\'s Name';

                    echo form_field($field);

                    // --------------------------------------------------------------------------

                    //  Shop URL
                    $field                = array();
                    $field['key']         = 'url';
                    $field['label']       = 'Shop URL';
                    $field['default']     = appSetting($field['key'], 'shop') ? appSetting($field['key'], 'shop') : 'shop/';
                    $field['placeholder'] = 'Customise the Shop\'s URL (include trialing slash)';

                    echo form_field($field);

                ?>
            </fieldset>
            <fieldset id="shop-settings-external-products">
                <legend>External Products</legend>
                <p>
                    Allow the shop to list items sold by another vendor. External products are listed and categorised
                    just like normal products but will link out to a third party.
                </p>
                <?php

                    $field             = array();
                    $field['key']      = 'enable_external_products';
                    $field['label']    = 'Enable External Products';
                    $field['default']  = appSetting($field['key'], 'shop');
                    $field['text_on']  = strtoupper(lang('yes'));
                    $field['text_off'] = strtoupper(lang('no'));

                    echo form_field_boolean($field);

                ?>
            </fieldset>
            <fieldset id="shop-settings-tax">
                <legend>Taxes</legend>
                <p>
                    Configure how the shop calculates taxes on the products you sell.
                </p>
                <?php

                    $field             = array();
                    $field['key']      = 'price_exclude_tax';
                    $field['label']    = 'Product price exclude Taxes';
                    $field['default']  = appSetting($field['key'], 'shop');
                    $field['text_on']  = strtoupper(lang('yes'));
                    $field['text_off'] = strtoupper(lang('no'));

                    echo form_field_boolean($field);

                ?>
            </fieldset>
            <fieldset id="shop-settings-dates">
                <legend>Accounting Dates</legend>
                <p>
                    Completing these fields will allow for reports to be generated which apply to the company's
                    financial year.
                </p>
                <?php

                    $field                = array();
                    $field['key']         = 'firstFinancialYearEndDate';
                    $field['label']       = 'First Financial Year End';
                    $field['default']     = appSetting($field['key'], 'shop');

                    echo form_field_date($field);

                ?>
            </fieldset>
            <fieldset id="shop-settings-invoice">
                <legend>Invoicing</legend>
                <p>
                    These details will be visible on invoices and email receipts.
                </p>
                <?php

                    //  Company Name
                    $field                = array();
                    $field['key']         = 'invoice_company';
                    $field['label']       = 'Company Name';
                    $field['default']     = appSetting($field['key'], 'shop');
                    $field['placeholder'] = 'The registered company name.';

                    echo form_field($field);

                    // --------------------------------------------------------------------------

                    //  Address
                    $field                = array();
                    $field['key']         = 'invoice_address';
                    $field['label']       = 'Company Address';
                    $field['type']        = 'textarea';
                    $field['default']     = appSetting($field['key'], 'shop');
                    $field['placeholder'] = 'The address to show on the invoice.';

                    echo form_field($field);

                    // --------------------------------------------------------------------------

                    //  VAT Number
                    $field                = array();
                    $field['key']         = 'invoice_vat_no';
                    $field['label']       = 'VAT Number';
                    $field['default']     = appSetting($field['key'], 'shop');
                    $field['placeholder'] = 'Your VAT number, if any.';

                    echo form_field($field);

                    // --------------------------------------------------------------------------

                    //  Company Number
                    $field                = array();
                    $field['key']         = 'invoice_company_no';
                    $field['label']       = 'Company Number';
                    $field['default']     = appSetting($field['key'], 'shop');
                    $field['placeholder'] = 'Your company number.';

                    echo form_field($field);

                    // --------------------------------------------------------------------------

                    //  Invoice Footer
                    $field                = array();
                    $field['key']         = 'invoice_footer';
                    $field['label']       = 'Footer Text';
                    $field['type']        = 'textarea';
                    $field['default']     = appSetting($field['key'], 'shop');
                    $field['placeholder'] = 'Any text to include on the footer of each invoice.';
                    $field['tip']         = 'Use this space to include shop specific information such as your returns policy.';

                    echo form_field($field);

                ?>
            </fieldset>
            <fieldset id="shop-settings-warehouse-collection">
                <legend>Warehouse Collection</legend>
                <p>
                    If you'd like customers to be able to collect items from your warehouse or store, then enable it
                    below and provide collection details.
                </p>
                <?php

                    $field            = array();
                    $field['key']     = 'warehouse_collection_enabled';
                    $field['label']   = 'Enabled';
                    $field['default'] = appSetting($field['key'], 'shop');

                    echo form_field_boolean($field);

                    // --------------------------------------------------------------------------

                    if ($this->input->post($field['key']) || appSetting($field['key'], 'shop')) {

                        $style = '';

                    } else {

                        $style = 'style="display:none;"';
                    }

                    echo '<div id="warehouse-collection-address" ' . $style . '>';

                        $field                = array();
                        $field['key']         = 'warehouse_addr_addressee';
                        $field['label']       = 'Addressee';
                        $field['default']     = appSetting($field['key'], 'shop');
                        $field['placeholder'] = 'The person or department responsible for collection items.';

                        echo form_field($field);

                        // --------------------------------------------------------------------------

                        $field                = array();
                        $field['key']         = 'warehouse_addr_line1';
                        $field['label']       = 'Address Line 1';
                        $field['default']     = appSetting($field['key'], 'shop');
                        $field['placeholder'] = 'The first line of the warehouse\'s address';

                        echo form_field($field);

                        // --------------------------------------------------------------------------

                        $field                = array();
                        $field['key']         = 'warehouse_addr_line2';
                        $field['label']       = 'Address Line 2';
                        $field['default']     = appSetting($field['key'], 'shop');
                        $field['placeholder'] = 'The second line of the warehouse\'s address';

                        echo form_field($field);

                        // --------------------------------------------------------------------------

                        $field                = array();
                        $field['key']         = 'warehouse_addr_town';
                        $field['label']       = 'Address Town';
                        $field['default']     = appSetting($field['key'], 'shop');
                        $field['placeholder'] = 'The town line of the warehouse\'s address';

                        echo form_field($field);

                        // --------------------------------------------------------------------------

                        $field                = array();
                        $field['key']         = 'warehouse_addr_postcode';
                        $field['label']       = 'Address Postcode';
                        $field['default']     = appSetting($field['key'], 'shop');
                        $field['placeholder'] = 'The postcode line of the warehouse\'s address';

                        echo form_field($field);

                        // --------------------------------------------------------------------------

                        $field                = array();
                        $field['key']         = 'warehouse_addr_state';
                        $field['label']       = 'Address State';
                        $field['default']     = appSetting($field['key'], 'shop');
                        $field['placeholder'] = 'The state line of the warehouse\'s address, if applicable';

                        echo form_field($field);

                        // --------------------------------------------------------------------------

                        $field                = array();
                        $field['key']         = 'warehouse_addr_country';
                        $field['label']       = 'Address Country';
                        $field['default']     = appSetting($field['key'], 'shop');
                        $field['placeholder'] = 'The country line of the warehouse\'s address';

                        echo form_field($field);

                        // --------------------------------------------------------------------------

                        $field            = array();
                        $field['key']     = 'warehouse_collection_delivery_enquiry';
                        $field['label']   = 'Enable Delivery Enquiry';
                        $field['default'] = appSetting($field['key'], 'shop');
                        $field['tip']     = 'For items which are &quot;collect only&quot;, enable a button which allows the user to submit a delivery enquiry.';

                        echo form_field_boolean($field);

                    echo '</div>';

                ?>
            </fieldset>
            <fieldset id="shop-settings-misc">
                <legend>Miscellaneous</legend>
                <?php

                    //  Brand Listing
                    $field            = array();
                    $field['key']     = 'page_brand_listing';
                    $field['label']   = 'Brand Listing Page';
                    $field['default'] = appSetting($field['key'], 'shop');

                    echo form_field_boolean($field, 'The page shown when the brand URL is used, but no slug is specified. Renders all the populated brands and their SEO data.');

                    // --------------------------------------------------------------------------

                    //  Category Listing
                    $field            = array();
                    $field['key']     = 'page_category_listing';
                    $field['label']   = 'Category Listing Page';
                    $field['default'] = appSetting($field['key'], 'shop');

                    echo form_field_boolean($field, 'The page shown when the category URL is used, but no slug is specified. Renders all the populated categories and their SEO data.');

                    // --------------------------------------------------------------------------

                    //  Collection Listing
                    $field            = array();
                    $field['key']     = 'page_collection_listing';
                    $field['label']   = 'Collection Listing Page';
                    $field['default'] = appSetting($field['key'], 'shop');

                    echo form_field_boolean($field, 'The page shown when the collection URL is used, but no slug is specified. Renders all the active collections and their SEO data.');

                    // --------------------------------------------------------------------------

                    //  Range Listing
                    $field            = array();
                    $field['key']     = 'page_range_listing';
                    $field['label']   = 'Range Listing Page';
                    $field['default'] = appSetting($field['key'], 'shop');

                    echo form_field_boolean($field, 'The page shown when the range URL is used, but no slug is specified. Renders all the active ranges and their SEO data.');

                    // --------------------------------------------------------------------------

                    //  Sale Listing
                    $field            = array();
                    $field['key']     = 'page_sale_listing';
                    $field['label']   = 'Sale Listing Page';
                    $field['default'] = appSetting($field['key'], 'shop');

                    echo form_field_boolean($field, 'The page shown when the sale URL is used, but no slug is specified. Renders all the active sales and their SEO data.');

                    // --------------------------------------------------------------------------

                    //  Tag Listing
                    $field            = array();
                    $field['key']     = 'page_tag_listing';
                    $field['label']   = 'Tag Listing Page';
                    $field['default'] = appSetting($field['key'], 'shop');

                    echo form_field_boolean($field, 'The page shown when the tag URL is used, but no slug is specified. Renders all the populated tags and their SEO data.');

                ?>
            </fieldset>
        </div>
        <div class="tab-page tab-browse">
            <p>
                Configure the default browsing experience for your customers.
            </p>
            <fieldset id="shop-browsing-tweaks">
                <legend>Browsing Tweaks</legend>
                <?php

                    $field            = array();
                    $field['key']     = 'expand_variants';
                    $field['label']   = 'Expand Variants';
                    $field['default'] = appSetting($field['key'], 'shop');
                    $field['tip']     = 'Expand product variants so that each variant is seemingly an individual product when browsing.';

                    echo form_field_boolean($field);

                ?>
            </fieldset>
            <fieldset id="shop-sorting-tweaks">
                <legend>Sorting Defaults</legend>
                <?php

                    /*
                     * Changing these? Then make sure you update the skins etc too.
                     * Probably would be a good @todo to abstract these into a model/config file somewhere.
                     */

                    $field            = array();
                    $field['key']     = 'default_product_per_page';
                    $field['label']   = 'Products per Page';
                    $field['class']   = 'select2';
                    $field['default'] = appSetting($field['key'], 'shop');

                    $options        = array();
                    $options['20']  = '10';
                    $options['40']  = '40';
                    $options['80']  = '80';
                    $options['100'] = '100';
                    $options['all'] = 'All';

                    echo form_field_dropdown($field, $options);

                    // --------------------------------------------------------------------------

                    $field            = array();
                    $field['key']     = 'default_product_sort';
                    $field['label']   = 'Product Sorting';
                    $field['class']   = 'select2';
                    $field['default'] = appSetting($field['key'], 'shop');

                    $options                   = array();
                    $options['recent']         = 'Recently Added';
                    $options['price-low-high'] = 'Price: Low to High';
                    $options['price-high-low'] = 'Price: High to Low';
                    $options['a-z']            = 'A to Z';

                    echo form_field_dropdown($field, $options);

                ?>
            </fieldset>
        </div>
        <div class="tab-page tab-skin">
            <?php

                $sActiveTab = $this->input->post('active_tab_skins') ?: 'tab-skin-foh';
                echo '<input type="hidden" name="active_tab_skins" value="' . $sActiveTab . '" id="active-tab-skins">';

            ?>
            <ul class="tabs" data-tabgroup="skins" data-active-tab-input="#active-tab-skins">
                <li class="tab active">
                    <a href="#" data-tab="tab-skin-foh">Front of House</a>
                </li>
                <li class="tab">
                    <a href="#" data-tab="tab-skin-checkout">Checkout</a>
                </li>
            </ul>
            <section class="tabs" data-tabgroup="skins">
                <div class="tab-page tab-skin-foh active">
                    <p>
                        The "Front of House" skin is responsible for the user's experience whilst browsing your store.
                    </p>
                    <?php

                    if ($skins_front) {

                        ?>
                        <table>
                            <thead>
                                <tr>
                                    <th class="selected">Selected</th>
                                    <th class="label">Label</th>
                                    <th class="configure">Configure</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php

                                foreach ($skins_front as $skin) {

                                    $bSelected = $skin->slug == $skin_front_selected ? true : false;

                                    ?>
                                    <tr>
                                        <td class="selected">
                                            <?=form_radio('skin_front', $skin->slug, $bSelected)?>
                                        </td>
                                        <td class="label">
                                            <?php

                                            echo $skin->name;
                                            if (!empty($skin->description)) {

                                                echo '<small>';
                                                echo $skin->description;
                                                echo '</small>';
                                            }

                                            ?>
                                        </td>
                                        <td class="configure">
                                            <?php

                                            if (!empty($skin->data->settings)) {

                                                echo anchor(
                                                    'admin/shop/settings/shop_skin?type=front&slug=' . $skin->slug,
                                                    'Configure',
                                                    'data-fancybox-type="iframe" class="fancybox btn btn-xs btn-primary"'
                                                );
                                            }

                                            ?>
                                        </td>
                                    </tr>
                                    <?php
                                }

                                ?>
                            </tbody>
                        </table>
                        <?php

                    } else {

                        ?>
                        <p class="alert alert-danger">
                            <strong>Error:</strong> I'm sorry, but I couldn't find any front of house skins to use.
                            This is a configuration error and should be raised with the developer.
                        </p>
                        <?php
                    }

                    ?>
                </div>
                <div class="tab-page tab-skin-checkout">
                    <p>
                        The "Checkout" Skin is responsible for the user's basket and checkout experience.
                    </p>
                    <?php

                    if ($skins_checkout) {

                        ?>
                        <table>
                            <thead>
                                <tr>
                                    <th class="selected">Selected</th>
                                    <th class="label">Label</th>
                                    <th class="configure">Configure</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php

                                foreach ($skins_checkout as $skin) {

                                    $bSelected = $skin->slug == $skin_checkout_selected ? true : false;

                                    ?>
                                    <tr>
                                        <td class="selected">
                                            <?=form_radio('skin_checkout', $skin->slug, $bSelected)?>
                                        </td>
                                        <td class="label">
                                            <?php

                                            echo $skin->name;
                                            if (!empty($skin->description)) {

                                                echo '<small>';
                                                echo $skin->description;
                                                echo '</small>';
                                            }

                                            ?>
                                        </td>
                                        <td class="configure">
                                            <?php

                                            if (!empty($skin->data->settings)) {

                                                echo anchor(
                                                    'admin/shop/settings/shop_skin?type=checkout&slug=' . $skin->slug,
                                                    'Configure',
                                                    'data-fancybox-type="iframe" class="fancybox btn btn-xs btn-primary"'
                                                );
                                            }

                                            ?>
                                        </td>
                                    </tr>
                                    <?php
                                }

                                ?>
                            </tbody>
                        </table>
                        <?php

                    } else {

                        ?>
                        <p class="alert alert-danger">
                            <strong>Error:</strong> I'm sorry, but I couldn't find any checkout skins to use.
                            This is a configuration error and should be raised with the developer.
                        </p>
                        <?php
                    }

                    ?>
                </div>
            </section>
        </div>
        <div class="tab-page tab-payment-gateway">
            <p>
                Set Payment Gateway credentials.
            </p>
            <?php

                if (!empty($payment_gateways)) {

                    ?>
                    <table id="payment-gateways">
                        <thead class="payment-gateways">
                            <tr>
                                <th class="enabled">Enabled</th>
                                <th class="label">Label</th>
                                <th class="configure">Configure</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php

                            $enabledPaymentGateways = set_value('enabled_payment_gateways', appSetting('enabled_payment_gateways', 'shop'));
                            $enabledPaymentGateways = array_filter((array) $enabledPaymentGateways);

                            foreach ($payment_gateways as $slug) {

                                $_enabled = array_search($slug, $enabledPaymentGateways) !== false ? true : false;

                                ?>
                                <tr>
                                    <td class="enabled">
                                        <div class="toggle toggle-modern"></div>
                                        <?=form_checkbox('enabled_payment_gateways[]', $slug, $_enabled)?>
                                    </td>
                                    <td class="label">
                                        <?=str_replace('_', ' ', $slug)?>
                                    </td>
                                    <td class="configure">
                                        <?php

                                        echo anchor(
                                            'admin/shop/settings/shop_pg?gateway=' . $slug,
                                            'Configure',
                                            'data-fancybox-type="iframe" class="fancybox btn btn-xs btn-primary"'
                                        );

                                        ?>
                                    </td>
                                </tr>
                                <?php
                            }

                            ?>
                        <tbody>
                    </table>
                    <?php

                } else {

                    ?>
                    <p class="alert alert-danger">
                        <strong>No payment gateways are available.</strong>
                        <br />I could not find any payment gateways. Please contact the developers
                        on <?=mailto(APP_DEVELOPER_EMAIL)?> for assistance.
                    </p>
                    <?php
                }

            ?>
        </div>
        <div class="tab-page tab-currencies">
            <p>
                Configure supported currencies.
            </p>
            <fieldset id="shop-currencies-base">
                <legend>Base Currency</legend>
                <p>
                    Define the default currency of the shop. Reporting will be generated in this currency.
                </p>
                <?php

                    if ($productCount) {

                        ?>
                        <p class="alert alert-warning">
                            <strong>Important:</strong> The base currency cannot be changed once a
                            product has been created.
                        </p>
                        <?php
                    }

                ?>
                <p>
                <?php

                    //  Base Currency
                    $field             = array();
                    $field['key']      = 'base_currency';
                    $field['label']    = 'Base Currency';
                    $field['default']  = appSetting($field['key'], 'shop');
                    $field['readonly'] = $productCount ? 'disabled="disabled"' : '';

                    $_currencies = array();

                    foreach ($currencies as $c) {

                        $_currencies[$c->code] = $c->code . ' - ' . $c->label;
                    }

                    echo form_dropdown(
                        $field['key'],
                        $_currencies,
                        set_value($field['key'], $field['default']),
                        'class="select2" ' . $field['readonly']
                    );

                ?>
                </p>
            </fieldset>
            <fieldset id="shop-currencies-base">
                <legend>Additional Supported Currencies</legend>
                <p>
                    Define which currencies you wish to support in your store in addition to the base currency.
                </p>
                <p class="alert alert-warning">
                    <strong>Important:</strong> Not all payment gateways support all currencies and some must be configured to
                    support a particular currency. Additional costs may apply, please choose additional currencies carefully.
                </p>
                <p>
                <?php

                    $_default = set_value('additional_currencies', appSetting('additional_currencies', 'shop'));
                    $_default = array_filter((array) $_default);

                    echo '<select name="additional_currencies[]" multiple="multiple" class="select2">';
                    foreach ($currencies as $currency) {

                        $_selected = array_search($currency->code, $_default) !== false ? 'selected="selected"' : '';

                        echo '<option value="'. $currency->code . '" ' . $_selected . '>' . $currency->code . ' - ' . $currency->label . '</option>';

                    }
                    echo '</select>';

                ?>
                </p>
                <p class="alert alert-warning">
                    <strong>Important:</strong> If you wish to support multiple currencies you must also provide an
                    App ID for the <a href="https://openexchangerates.org" target="_blank">Open Exchange Rates</a>
                    service. The system uses this service to calculate exchange rates for all supported currencies.
                    <br />
                    Find out more, and create your App, at <a href="https://openexchangerates.org" target="_blank">Open Exchange Rates</a>.
                </p>
                <?php

                    $field                = array();
                    $field['key']         = 'openexchangerates_app_id';
                    $field['label']       = 'Open Exchange Rates App ID';
                    $field['default']     = appSetting('openexchangerates_app_id', 'shop');
                    $field['placeholder'] = 'Set the Open exchange Rate App ID';

                    echo form_field($field);

                ?>
            </fieldset>
        </div>
        <div class="tab-page tab-shipping">
            <?php

            if (!empty($shipping_drivers)) {

                ?>
                <table id="shipping-modules">
                    <thead class="shipping-modules">
                        <tr>
                            <th class="selected">Selected</th>
                            <th class="label">Label</th>
                            <th class="configure">Configure</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php

                        $enabledShippingDriver = set_value('enabled_shipping_driver', appSetting('enabled_shipping_driver', 'shop'));

                        foreach ($shipping_drivers as $driver) {

                            $_name        = !empty($driver->name) ? $driver->name : 'Untitled';
                            $_description = !empty($driver->description) ? $driver->description : '';
                            $_enabled     = $driver->name == $enabledShippingDriver ? true : false;

                            ?>
                            <tr>
                                <td class="selected">
                                    <?=form_radio('enabled_shipping_driver', $driver->name, $_enabled)?>
                                </td>
                                <td class="label">
                                    <?php

                                    echo $_name;
                                    echo $_description ? '<small>' . $_description . '</small>' : '';

                                    ?>
                                </td>
                                <td class="configure">
                                    <?php

                                    if (!empty($driver->configurable)) {
                                        echo anchor(
                                            'admin/shop/settings/shop_sd?driver=' . $driver->name,
                                            'Configure',
                                            'data-fancybox-type="iframe" class="fancybox btn btn-xs btn-primary"'
                                        );
                                    }

                                    ?>
                                </td>
                            </tr>
                            <?php
                        }

                        ?>
                    <tbody>
                </table>
                <?php

            } else {

                ?>
                <p class="alert alert-danger">
                    <strong>No shipping drivers are available.</strong>
                    <br />I could not find any shipping drivers. Please contact the developers on
                    <?=mailto(APP_DEVELOPER_EMAIL)?> for assistance.
                </p>
                <?php
            }

            ?>
        </div>
        <div class="tab-page tab-pages">
            <?php

            $aPages = appSetting('pages', 'shop');

            if (isModuleEnabled('nailsapp/module-cms')) {

                ?>
                <p>
                    Use the CMS to define the content for the following pages.
                </p>
                <?php

                if (empty($cmsPages)) {

                    ?>
                    <div class="alert alert-danger">
                        <p>
                            <strong>No CMS Pages have been defined</strong>
                        </p>
                        <p>
                            Create some pages in the CMS and come back here to select them for use with the shop.
                        </p>
                    </div>
                    <?php

                } else {

                    ?>
                    <div class="table-responsive">
                        <table class="table-pages">
                            <thead>
                                <tr>
                                    <th class="label">Page</th>
                                    <th>CMS Page</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php

                                foreach ($pages as $sSlug => $sLabel) {

                                    if (!$this->input->post()) {

                                        $iCmsId = !empty($aPages->{$sSlug}->cmsPageId) ? $aPages->{$sSlug}->cmsPageId : '';

                                    } else {

                                        $iCmsId = $_POST['pages'][$sSlug]['cmsPageId'];
                                    }

                                    ?>
                                    <tr>
                                        <td><?=$sLabel?></td>
                                        <td>
                                            <select class="selectt2" name="pages[<?=$sSlug?>][cmsPageId]">
                                                <option value="">
                                                    Disable this page
                                                </option>
                                                <?php

                                                foreach ($cmsPages as $iId => $sTitle) {

                                                    $sSelected = $iId == $iCmsId ? 'selected' : '';

                                                    ?>
                                                    <option value="<?=$iId?>" <?=$sSelected?>>
                                                        <?=$sTitle?>
                                                    </option>
                                                    <?php
                                                }

                                                ?>
                                            </select>
                                        </td>
                                    </tr>
                                    <?php
                                }

                                ?>
                            </tbody>
                        </table>
                    </div>
                    <?php
                }

            } else {

                ?>
                <p>
                    Specify the content for the following pages which are of interest to shoppers.
                    Leave blank to disable.
                </p>
                <div class="table-responsive">
                    <table class="table-pages">
                        <thead>
                            <tr>
                                <th class="label">Page</th>
                                <th>Page Body</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php

                            foreach ($pages as $sSlug => $sLabel) {

                                ?>
                                <tr>
                                    <td><?=$sLabel?></td>
                                    <td>
                                        <?php

                                        if (!$this->input->post()) {

                                            $sBody = !empty($aPages->{$sSlug}->body) ? $aPages->{$sSlug}->body : '';

                                        } else {

                                            $sBody = $_POST['pages'][$sSlug]['body'];
                                        }

                                        echo '<textarea class="wysiwyg" name="pages[' . $sSlug . '][body]">';
                                        echo $sBody;
                                        echo '</textarea>';

                                        ?>
                                    </td>
                                </tr>
                                <?php
                            }

                            ?>
                        </tbody>
                    </table>
                </div>
                <?php
            }

            ?>
        </div>
    </section>
    <p>
        <?=form_submit('submit', lang('action_save_changes'), 'class="btn btn-primary"')?>
    </p>
    <?=form_close()?>
</div>