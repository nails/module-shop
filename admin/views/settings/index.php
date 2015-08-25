<div class="group-settings shop">
    <p>
        Configure various aspects of the shop.
    </p>
    <hr />
    <ul class="tabs" data-tabgroup="main-tabs">
        <?php $activeTab = $this->input->post('update') == 'settings' || !$this->input->post() ? 'active' : ''?>
        <li class="tab <?=$activeTab?>">
            <a href="#" data-tab="tab-general">General</a>
        </li>

        <?php $activeTab = $this->input->post('update') == 'browse' ? 'active' : ''?>
        <li class="tab <?=$activeTab?>">
            <a href="#" data-tab="tab-browse">Browsing</a>
        </li>

        <?php $activeTab = $this->input->post('update') == 'skin' ? 'active' : ''?>
        <li class="tab <?=$activeTab?>">
            <a href="#" data-tab="tab-skin">Skin</a>
        </li>

        <?php $activeTab = $this->input->post('update') == 'skin-configure' ? 'active' : ''?>
        <li class="tab <?=$activeTab?>">
            <a href="#" data-tab="tab-skin-config">Skin - Configure</a>
        </li>

        <?php $activeTab = $this->input->post('update') == 'payment_gateway' ? 'active' : ''?>
        <li class="tab <?=$activeTab?>">
            <a href="#" data-tab="tab-payment-gateway">Payment Gateways</a>
        </li>

        <?php $activeTab = $this->input->post('update') == 'currencies' ? 'active' : ''?>
        <li class="tab <?=$activeTab?>">
            <a href="#" data-tab="tab-currencies">Currencies</a>
        </li>

        <?php $activeTab = $this->input->post('update') == 'shipping' ? 'active' : ''?>
        <li class="tab <?=$activeTab?>">
            <a href="#" data-tab="tab-shipping">Shipping</a>
        </li>
    </ul>
    <section class="tabs" data-tabgroup="main-tabs">
        <?php $display = $this->input->post('update') == 'settings' || !$this->input->post() ? 'active' : ''?>
        <div class="tab-page tab-general <?=$display?>">
            <?php

                echo form_open(null, 'style="margin-bottom:0;"');
                echo form_hidden('update', 'settings');
            ?>
            <p>
                Generic store settings. Use these to control some store behaviours.
            </p>
            <hr />
            <fieldset id="shop-settings-online">
                <legend>Maintenance</legend>
                <p>
                    Is the shop enabled?
                </p>
                <?php

                    $field            = array();
                    $field['key']     = 'maintenance_enabled';
                    $field['label']   = 'Maintenance Mode';
                    $field['default'] = (bool) app_setting($field['key'], 'shop');
                    $field['tip']     = 'Use this field to temporarily disable the shop, e.g. for extended maintenance.';

                    echo form_field_boolean($field);

                    $display = $field['default'] ? 'block' : 'none';

                ?>
                <div id="shop-maintenance-extras" style="display:<?=$display?>">
                    <?php

                        $field                = array();
                        $field['key']         = 'maintenance_title';
                        $field['label']       = 'Maintenance Title';
                        $field['default']     = app_setting($field['key'], 'shop') ? app_setting($field['key'], 'shop') : '';
                        $field['placeholder'] = 'Customise what the maintenance page title is';

                        echo form_field($field);

                        // --------------------------------------------------------------------------

                        $field                = array();
                        $field['key']         = 'maintenance_body';
                        $field['label']       = 'Maintenance Body';
                        $field['default']     = app_setting($field['key'], 'shop') ? app_setting($field['key'], 'shop') : '';
                        $field['placeholder'] = 'Customise what the maintenance page body text is';

                        echo form_field_wysiwyg($field);

                    ?>
                </div>
            </fieldset>
            <fieldset id="shop-settings-name">
                <legend>Name</legend>
                <p>
                    Is this a shop? Or is it store? Maybe a boutique...?
                </p>
                <?php

                    //  Shop Name
                    $field                = array();
                    $field['key']         = 'name';
                    $field['label']       = 'Shop Name';
                    $field['default']     = app_setting($field['key'], 'shop') ? app_setting($field['key'], 'shop') : 'Shop';
                    $field['placeholder'] = 'Customise the Shop\'s Name';

                    echo form_field($field);

                ?>
            </fieldset>
            <fieldset id="shop-settings-url">
                <legend>URL</legend>
                <p>
                    Customise the shop's URL by specifying it here.
                </p>
                <?php

                    //  Shop URL
                    $field                = array();
                    $field['key']         = 'url';
                    $field['label']       = 'Shop URL';
                    $field['default']     = app_setting($field['key'], 'shop') ? app_setting($field['key'], 'shop') : 'shop/';
                    $field['placeholder'] = 'Customise the Shop\'s URL (include trialing slash)';

                    echo form_field($field);

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
                    $field['default']  = app_setting($field['key'], 'shop');
                    $field['text_on']  = strtoupper(lang('yes'));
                    $field['text_off'] = strtoupper(lang('no'));

                    echo form_field_boolean($field);

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
                    $field['default']  = app_setting($field['key'], 'shop');
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
                    $field['default']     = app_setting($field['key'], 'shop');

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
                    $field['default']     = app_setting($field['key'], 'shop');
                    $field['placeholder'] = 'The registered company name.';

                    echo form_field($field);

                    // --------------------------------------------------------------------------

                    //  Address
                    $field                = array();
                    $field['key']         = 'invoice_address';
                    $field['label']       = 'Company Address';
                    $field['type']        = 'textarea';
                    $field['default']     = app_setting($field['key'], 'shop');
                    $field['placeholder'] = 'The address to show on the invoice.';

                    echo form_field($field);

                    // --------------------------------------------------------------------------

                    //  VAT Number
                    $field                = array();
                    $field['key']         = 'invoice_vat_no';
                    $field['label']       = 'VAT Number';
                    $field['default']     = app_setting($field['key'], 'shop');
                    $field['placeholder'] = 'Your VAT number, if any.';

                    echo form_field($field);

                    // --------------------------------------------------------------------------

                    //  Company Number
                    $field                = array();
                    $field['key']         = 'invoice_company_no';
                    $field['label']       = 'Company Number';
                    $field['default']     = app_setting($field['key'], 'shop');
                    $field['placeholder'] = 'Your company number.';

                    echo form_field($field);

                    // --------------------------------------------------------------------------

                    //  Invoice Footer
                    $field                = array();
                    $field['key']         = 'invoice_footer';
                    $field['label']       = 'Footer Text';
                    $field['type']        = 'textarea';
                    $field['default']     = app_setting($field['key'], 'shop');
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
                    $field['default'] = app_setting($field['key'], 'shop');

                    echo form_field_boolean($field);

                    // --------------------------------------------------------------------------

                    if ($this->input->post($field['key']) || app_setting($field['key'], 'shop')) {

                        $style = '';

                    } else {

                        $style = 'style="display:none;"';
                    }

                    echo '<div id="warehouse-collection-address" ' . $style . '>';

                        $field                = array();
                        $field['key']         = 'warehouse_addr_addressee';
                        $field['label']       = 'Addressee';
                        $field['default']     = app_setting($field['key'], 'shop');
                        $field['placeholder'] = 'The person or department responsible for collection items.';

                        echo form_field($field);

                        // --------------------------------------------------------------------------

                        $field                = array();
                        $field['key']         = 'warehouse_addr_line1';
                        $field['label']       = 'Address Line 1';
                        $field['default']     = app_setting($field['key'], 'shop');
                        $field['placeholder'] = 'The first line of the warehouse\'s address';

                        echo form_field($field);

                        // --------------------------------------------------------------------------

                        $field                = array();
                        $field['key']         = 'warehouse_addr_line2';
                        $field['label']       = 'Address Line 2';
                        $field['default']     = app_setting($field['key'], 'shop');
                        $field['placeholder'] = 'The second line of the warehouse\'s address';

                        echo form_field($field);

                        // --------------------------------------------------------------------------

                        $field                = array();
                        $field['key']         = 'warehouse_addr_town';
                        $field['label']       = 'Address Town';
                        $field['default']     = app_setting($field['key'], 'shop');
                        $field['placeholder'] = 'The town line of the warehouse\'s address';

                        echo form_field($field);

                        // --------------------------------------------------------------------------

                        $field                = array();
                        $field['key']         = 'warehouse_addr_postcode';
                        $field['label']       = 'Address Postcode';
                        $field['default']     = app_setting($field['key'], 'shop');
                        $field['placeholder'] = 'The postcode line of the warehouse\'s address';

                        echo form_field($field);

                        // --------------------------------------------------------------------------

                        $field                = array();
                        $field['key']         = 'warehouse_addr_state';
                        $field['label']       = 'Address State';
                        $field['default']     = app_setting($field['key'], 'shop');
                        $field['placeholder'] = 'The state line of the warehouse\'s address, if applicable';

                        echo form_field($field);

                        // --------------------------------------------------------------------------

                        $field                = array();
                        $field['key']         = 'warehouse_addr_country';
                        $field['label']       = 'Address Country';
                        $field['default']     = app_setting($field['key'], 'shop');
                        $field['placeholder'] = 'The country line of the warehouse\'s address';

                        echo form_field($field);

                        // --------------------------------------------------------------------------

                        $field            = array();
                        $field['key']     = 'warehouse_collection_delivery_enquiry';
                        $field['label']   = 'Enable Delivery Enquiry';
                        $field['default'] = app_setting($field['key'], 'shop');
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
                    $field['default'] = app_setting($field['key'], 'shop');

                    echo form_field_boolean($field, 'The page shown when the brand URL is used, but no slug is specified. Renders all the populated brands and their SEO data.');

                    // --------------------------------------------------------------------------

                    //  Category Listing
                    $field            = array();
                    $field['key']     = 'page_category_listing';
                    $field['label']   = 'Category Listing Page';
                    $field['default'] = app_setting($field['key'], 'shop');

                    echo form_field_boolean($field, 'The page shown when the category URL is used, but no slug is specified. Renders all the populated categories and their SEO data.');

                    // --------------------------------------------------------------------------

                    //  Collection Listing
                    $field            = array();
                    $field['key']     = 'page_collection_listing';
                    $field['label']   = 'Collection Listing Page';
                    $field['default'] = app_setting($field['key'], 'shop');

                    echo form_field_boolean($field, 'The page shown when the collection URL is used, but no slug is specified. Renders all the active collections and their SEO data.');

                    // --------------------------------------------------------------------------

                    //  Range Listing
                    $field            = array();
                    $field['key']     = 'page_range_listing';
                    $field['label']   = 'Range Listing Page';
                    $field['default'] = app_setting($field['key'], 'shop');

                    echo form_field_boolean($field, 'The page shown when the range URL is used, but no slug is specified. Renders all the active ranges and their SEO data.');

                    // --------------------------------------------------------------------------

                    //  Sale Listing
                    $field            = array();
                    $field['key']     = 'page_sale_listing';
                    $field['label']   = 'Sale Listing Page';
                    $field['default'] = app_setting($field['key'], 'shop');

                    echo form_field_boolean($field, 'The page shown when the sale URL is used, but no slug is specified. Renders all the active sales and their SEO data.');

                    // --------------------------------------------------------------------------

                    //  Tag Listing
                    $field            = array();
                    $field['key']     = 'page_tag_listing';
                    $field['label']   = 'Tag Listing Page';
                    $field['default'] = app_setting($field['key'], 'shop');

                    echo form_field_boolean($field, 'The page shown when the tag URL is used, but no slug is specified. Renders all the populated tags and their SEO data.');

                ?>
            </fieldset>
            <p style="margin-top:1em;margin-bottom:0;">
                <?=form_submit('submit', lang('action_save_changes'), 'class="awesome" style="margin-bottom:0;"')?>
            </p>
            <?=form_close()?>
        </div>
        <?php $display = $this->input->post('update') == 'browse' ? 'active' : ''?>
        <div class="tab-page tab-browse <?=$display?>">
            <?php

                echo form_open(null, 'style="margin-bottom:0;"');
                echo form_hidden('update', 'browse');

            ?>
            <p>
                Configure the default browsing experience for your customers.
            </p>
            <hr />
            <fieldset id="shop-browsing-tweaks">
                <legend>Browsing Tweaks</legend>
                <?php

                    $field            = array();
                    $field['key']     = 'expand_variants';
                    $field['label']   = 'Expand Variants';
                    $field['default'] = app_setting($field['key'], 'shop');
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
                    $field['default'] = app_setting($field['key'], 'shop');

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
                    $field['default'] = app_setting($field['key'], 'shop');

                    $options                   = array();
                    $options['recent']         = 'Recently Added';
                    $options['price-low-high'] = 'Price: Low to High';
                    $options['price-high-low'] = 'Price: High to Low';
                    $options['a-z']            = 'A to Z';

                    echo form_field_dropdown($field, $options);

                ?>
            </fieldset>
            <p style="margin-top:1em;margin-bottom:0;">
                <?=form_submit('submit', lang('action_save_changes'), 'class="awesome" style="margin-bottom:0;"')?>
            </p>
            <?=form_close()?>
        </div>
        <?php $display = $this->input->post('update') == 'skin' ? 'active' : ''?>
        <div class="tab-page tab-skin <?=$display?>">
            <?php

                echo form_open(null, 'style="margin-bottom:0;"');
                echo form_hidden('update', 'skin');

            ?>
            <ul class="tabs" data-tabgroup="skins">
                <li class="tab active">
                    <a href="#" data-tab="tab-skin-foh">Front of House</a>
                </li>
                <li class="tab">
                    <a href="#" data-tab="tab-skin-checkout">Checkout</a>
                </li>
            </ul>
            <section class="tabs" data-tabgroup="skins">
                <div class="tab-page tab-skin-foh active clearfix">
                    <p class="system-alert notice">
                        The "Front of House" skin is responsible for the user's experience whilst browsing your store.
                    </p>
                    <?php

                        if ($skins_front) {

                            echo '<ul class="skins">';
                            foreach ($skins_front as $skin) {

                                $_name        = !empty($skin->name) ? $skin->name : 'Untitled';
                                $_description = !empty($skin->description) ? $skin->description : '';

                                if (file_exists($skin->path . 'icon.png')) {

                                    $_icon = $skin->url . 'icon.png';

                                } elseif (file_exists($skin->path . 'icon.jpg')) {

                                    $_icon = $skin->url . 'icon.jpg';

                                } elseif (file_exists($skin->path . 'icon.gif')) {

                                    $_icon = $skin->url . 'icon.gif';

                                } else {

                                    $_icon = NAILS_ASSETS_URL . 'img/admin/modules/settings/shop-skin-no-icon.png';
                                }

                                $_selected = $skin->slug == $skin_front_selected ? true : false;
                                $_class    = $_selected ? 'selected' : '';

                                echo '<li class="skin ' . $_class . '" rel="tipsy" title="' . htmlentities($_description, ENT_QUOTES) . '">';
                                    echo '<div class="icon">' . img($_icon) . '</div>';
                                    echo '<div class="name">';
                                        echo $_name;
                                        echo '<span class="fa fa-check-circle"></span>';
                                    echo '</div>';
                                    echo form_radio('skin_front', $skin->slug, $_selected);
                                echo '</li>';

                            }
                            echo '</ul>';

                        } else {

                            echo '<p class="system-alert error">';
                                echo '<strong>Error:</strong> ';
                                echo 'I\'m sorry, but I couldn\'t find any front of house skins to use. This is a configuration error and should be raised with the developer.';
                            echo '</p>';
                        }

                    ?>
                </div>
                <div class="tab-page tab-skin-checkout clearfix">
                    <p class="system-alert notice">
                        The "Checkout" Skin is responsible for the user's basket and checkout experience.
                    </p>
                    <?php

                        if ($skins_checkout) {

                            echo '<ul class="skins">';
                            foreach ($skins_checkout as $skin) {

                                $_name        = !empty($skin->name) ? $skin->name : 'Untitled';
                                $_description = !empty($skin->description) ? $skin->description : '';

                                if (file_exists($skin->path . 'icon.png')) {

                                    $_icon = $skin->url . 'icon.png';

                                } elseif (file_exists($skin->path . 'icon.jpg')) {

                                    $_icon = $skin->url . 'icon.jpg';

                                } elseif (file_exists($skin->path . 'icon.gif')) {

                                    $_icon = $skin->url . 'icon.gif';

                                } else {

                                    $_icon = NAILS_ASSETS_URL . 'img/admin/modules/settings/shop-skin-no-icon.png';
                                }

                                $_selected = $skin->slug == $skin_checkout_selected ? true : false;
                                $_class    = $_selected ? 'selected' : '';

                                echo '<li class="skin ' . $_class . '" rel="tipsy" title="' . htmlentities($_description, ENT_QUOTES) . '">';
                                    echo '<div class="icon">' . img($_icon) . '</div>';
                                    echo '<div class="name">';
                                        echo $_name;
                                        echo '<span class="fa fa-check-circle"></span>';
                                    echo '</div>';
                                    echo form_radio('skin_checkout', $skin->slug, $_selected);
                                echo '</li>';
                            }
                            echo '</ul>';

                        } else {

                            echo '<p class="system-alert error">';
                                echo '<strong>Error:</strong> ';
                                echo 'I\'m sorry, but I couldn\'t find any checkout skins to use. This is a configuration error and should be raised with the developer.';
                            echo '</p>';
                        }

                    ?>
                </div>
            </section>
            <p>
                <?=form_submit('submit', lang('action_save_changes'), 'class="awesome" style="margin-bottom:0;"')?>
            </p>
            <?=form_close()?>
        </div>
        <?php $display = $this->input->post('update') == 'skin_config' ? 'active' : ''?>
        <div class="tab-page tab-skin-config <?=$display?>">
            <ul class="tabs" data-tabgroup="skins-config">
                <li class="tab active">
                    <a href="#" data-tab="tab-skin-config-foh">Front of House</a>
                </li>
                <li class="tab">
                    <a href="#" data-tab="tab-skin-config-checkout">Checkout</a>
                </li>
            </ul>
            <?php

                echo form_open(null, 'style="margin-bottom:0;"');
                echo form_hidden('update', 'skin_config');

            ?>
            <section class="tabs" data-tabgroup="skins-config">
            <?php

                echo '<div class="tab-page tab-skin-config-foh active">';
                if (!empty($skin_front_current)) {

                    if (!empty($skin_front_current->settings)) {

                        echo '<p class="system-alert notice">';
                            echo 'You are configuring settings for the <strong>' . $skin_front_current->name . '</strong> "Front of House" skin.';
                        echo '</p>';

                        echo '<div class="fieldset">';

                        foreach ($skin_front_current->settings as $setting) {

                            $field                = array();
                            $field['key']         = !empty($setting->key) ? 'skin_config[' . $skin_front_current->slug . '][' . $setting->key . ']' : '';
                            $field['label']       = !empty($setting->label) ? $setting->label : '';
                            $field['placeholder'] = !empty($setting->placeholder) ? $setting->placeholder : '';
                            $field['tip']         = !empty($setting->tip) ? $setting->tip : '';
                            $field['type']        = !empty($setting->type) ? $setting->type : '';

                            if (empty($field['key'])) {

                                continue;

                            } else {

                                $field['default']  = app_setting($setting->key, 'shop-' . $skin_front_current->slug);
                            }

                            switch ($field['type']) {

                                case 'bool' :
                                case 'boolean' :

                                    echo form_field_boolean($field);
                                    break;

                                case 'dropdown' :

                                    if (!empty($setting->options) && is_array($setting->options)) {

                                        $options = array();
                                        $field['class'] = 'select2';

                                        foreach ($setting->options as $option) {

                                            if (isset($option->value)) {

                                                $_value = $option->value;

                                            } else {

                                                $_value = null;
                                            }

                                            if (isset($option->label)) {

                                                $_label = $option->label;

                                            } else {

                                                $_label = null;
                                            }

                                            $options[$_value] = $_label;
                                        }

                                        echo form_field_dropdown($field, $options);
                                    }
                                    break;

                                default :

                                    echo form_field($field);
                                    break;
                            }
                        }

                        echo '</div>';

                    } else {

                        echo '<p class="system-alert message">';
                            echo 'No configurable settings for the <strong>' . $skin_front_current->name . '</strong> "Front of House" skin.';
                        echo '</p>';
                    }

                } else {

                    echo '<p class="system-alert message">';
                        echo 'No configurable settings for this skin.';
                    echo '</p>';
                }
                echo '</div>';

                echo '<div class="tab-page tab-skin-config-checkout">';
                if (!empty($skin_checkout_current)) {

                    if (!empty($skin_checkout_current->settings)) {

                        echo '<p class="system-alert notice">';
                            echo 'You are configuring settings for the <strong>' . $skin_checkout_current->name . '</strong> "Checkout" skin.';
                        echo '</p>';

                        echo '<div class="fieldset">';

                        foreach ($skin_checkout_current->settings as $setting) {

                            $field                = array();
                            $field['key']         = !empty($setting->key) ? 'skin_config[' . $skin_checkout_current->slug . '][' . $setting->key . ']' : '';
                            $field['label']       = !empty($setting->label) ? $setting->label : '';
                            $field['placeholder'] = !empty($setting->placeholder) ? $setting->placeholder : '';
                            $field['tip']         = !empty($setting->tip) ? $setting->tip : '';
                            $field['type']        = !empty($setting->type) ? $setting->type : '';

                            if (empty($field['key'])) {

                                continue;

                            } else {

                                $field['default']  = app_setting($setting->key, 'shop-' . $skin_checkout_current->slug);
                            }

                            switch ($field['type']) {

                                case 'bool' :
                                case 'boolean' :

                                    echo form_field_boolean($field);
                                    break;

                                case 'dropdown' :

                                    if (!empty($setting->options) && is_array($setting->options)) {

                                        $options = array();
                                        $field['class'] = 'select2';

                                        foreach ($setting->options as $option) {

                                            if (isset($option->value)) {

                                                $_value = $option->value;

                                            } else {

                                                $_value = null;
                                            }

                                            if (isset($option->label)) {

                                                $_label = $option->label;

                                            } else {

                                                $_label = null;
                                            }

                                            $options[$_value] = $_label;
                                        }

                                        echo form_field_dropdown($field, $options);
                                    }
                                    break;

                                default :

                                    echo form_field($field);
                                    break;
                            }
                        }

                        echo '</div>';

                    } else {

                        echo '<p class="system-alert message">';
                            echo 'No configurable settings for the <Strong>' . $skin_checkout_current->name . '</strong> "Checkout" skin.';
                        echo '</p>';
                    }

                } else {

                    echo '<p class="system-alert message">';
                        echo 'No configurable settings for this skin.';
                    echo '</p>';
                }
                echo '</div>';

            ?>
            </section>
            <?php

                echo '<p style="margin-top:1em;margin-bottom:0;">';
                    echo form_submit('submit', lang('action_save_changes'), 'class="awesome" style="margin-bottom:0;"');
                echo '</p>';
                echo form_close();

            ?>
        </div>
        <?php $display = $this->input->post('update') == 'payment_gateway' ? 'active' : ''?>
        <div class="tab-page tab-payment-gateway <?=$display?>">
            <?php

                echo form_open(null, 'style="margin-bottom:0;"');
                echo form_hidden('update', 'payment_gateway');
            ?>
            <p>
                Set Payment Gateway credentials.
            </p>
            <hr />
            <?php

                if (!empty($payment_gateways)) {

                    echo '<table id="payment-gateways">';
                        echo '<thead class="payment-gateways">';
                            echo '<tr>';
                                echo '<th class="enabled">Enabled</th>';
                                echo '<th class="label">Label</th>';
                                echo '<th class="configure">Configure</th>';
                            echo '</tr>';
                        echo '</thead>';
                        echo '<tbody>';

                        $enabledPaymentGateways = set_value('enabled_payment_gateways', app_setting('enabled_payment_gateways', 'shop'));
                        $enabledPaymentGateways = array_filter((array) $enabledPaymentGateways);

                        foreach ($payment_gateways as $slug) {

                            $_enabled = array_search($slug, $enabledPaymentGateways) !== false ? true : false;

                            echo '<tr>';
                                echo '<td class="enabled">';
                                    echo '<div class="toggle toggle-modern"></div>';
                                    echo form_checkbox('enabled_payment_gateways[]', $slug, $_enabled);
                                echo '</td>';
                                echo '<td class="label">';
                                    echo str_replace('_', ' ', $slug);
                                echo '</td>';
                                echo '<td class="configure">';
                                    echo anchor('admin/shop/settings/shop_pg/' . $slug, 'Configure', 'data-fancybox-type="iframe" class="fancybox awesome small"');
                                echo '</td>';
                            echo '</tr>';
                        }

                        echo '<tbody>';
                    echo '</table>';
                    echo '<hr />';

                } else {

                    echo '<p class="system-alert error">';
                        echo '<strong>No payment gateways are available.</strong>';
                        echo '<br />I could not find any payment gateways. Please contact the developers on ' . mailto(APP_DEVELOPER_EMAIL) . ' for assistance.';
                    echo '</p>';
                }

            ?>
            <p style="margin-top:1em;margin-bottom:0;">
                <?=form_submit('submit', lang('action_save_changes'), 'class="awesome" style="margin-bottom:0;"')?>
            </p>
            <?=form_close()?>
        </div>
        <?php $display = $this->input->post('update') == 'currencies' ? 'active' : ''?>
        <div class="tab-page tab-currencies <?=$display?>">
            <?php

                echo form_open(null, 'style="margin-bottom:0;"');
                echo form_hidden('update', 'currencies');

            ?>
            <p>
                Configure supported currencies.
            </p>
            <hr />
            <fieldset id="shop-currencies-base">
                <legend>Base Currency</legend>
                <p>
                    Define the default currency of the shop. Reporting will be generated in this currency.
                </p>
                <?php

                    if ($productCount) {

                        echo '<p class="system-alert message">';
                            echo '<strong>Important:</strong> The base currency cannot be changed once a product has been created.';
                        echo '</p>';
                    }

                ?>
                <p>
                <?php

                    //  Base Currency
                    $field             = array();
                    $field['key']      = 'base_currency';
                    $field['label']    = 'Base Currency';
                    $field['default']  = app_setting($field['key'], 'shop');
                    $field['readonly'] = $productCount ? 'disabled="disabled"' : '';

                    $_currencies = array();

                    foreach ($currencies as $c) {

                        $_currencies[$c->code] = $c->code . ' - ' . $c->label;
                    }

                    echo form_dropdown($field['key'], $_currencies, set_value($field['key'], $field['default']), 'class="select2" ' . $field['readonly']);

                ?>
                </p>
            </fieldset>
            <fieldset id="shop-currencies-base">
                <legend>Additional Supported Currencies</legend>
                <p>
                    Define which currencies you wish to support in your store in addition to the base currency.
                </p>
                <p class="system-alert message">
                    <strong>Important:</strong> Not all payment gateways support all currencies and some must be configured to
                    support a particular currency. Additional costs may apply, please choose additional currencies carefully.
                </p>
                <p>
                <?php

                    $_default = set_value('additional_currencies', app_setting('additional_currencies', 'shop'));
                    $_default = array_filter((array) $_default);

                    echo '<select name="additional_currencies[]" multiple="multiple" class="select2">';
                    foreach ($currencies as $currency) {

                        $_selected = array_search($currency->code, $_default) !== false ? 'selected="selected"' : '';

                        echo '<option value="'. $currency->code . '" ' . $_selected . '>' . $currency->code . ' - ' . $currency->label . '</option>';

                    }
                    echo '</select>';

                ?>
                </p>
                <hr />
                <p class="system-alert message">
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
                    $field['default']     = app_setting('openexchangerates_app_id', 'shop');
                    $field['placeholder'] = 'Set the Open exchange Rate App ID';

                    echo form_field($field);

                ?>
            </fieldset>
            <p style="margin-top:1em;margin-bottom:0;">
                <?=form_submit('submit', lang('action_save_changes'), 'class="awesome" style="margin-bottom:0;"')?>
            </p>
            <?=form_close()?>
        </div>
        <?php $display = $this->input->post('update') == 'shipping' ? 'active' : ''?>
        <div class="tab-page tab-shipping <?=$display?>">
            <?php

                echo form_open(null, 'style="margin-bottom:0;"');
                echo form_hidden('update', 'shipping');

                if (!empty($shipping_drivers)) {

                    echo '<table id="shipping-modules">';
                        echo '<thead class="shipping-modules">';
                            echo '<tr>';
                                echo '<th class="selected">Selected</th>';
                                echo '<th class="label">Label</th>';
                                echo '<th class="configure">Configure</th>';
                            echo '</tr>';
                        echo '</thead>';
                        echo '<tbody>';

                        $enabledShippingDriver = set_value('enabled_shipping_driver', app_setting('enabled_shipping_driver', 'shop'));

                        foreach ($shipping_drivers as $driver) {

                            $_name        = !empty($driver->name) ? $driver->name : 'Untitled';
                            $_description = !empty($driver->description) ? $driver->description : '';
                            $_enabled     = $driver->slug == $enabledShippingDriver ? true : false;

                            echo '<tr>';
                                echo '<td class="selected">';
                                    echo form_radio('enabled_shipping_driver', $driver->slug, $_enabled);
                                echo '</td>';
                                echo '<td class="label">';
                                    echo $_name;
                                    echo $_description ? '<small>' . $_description . '</small>' : '';
                                echo '</td>';
                                echo '<td class="configure">';
                                    echo !empty($driver->configurable) ? anchor('admin/shop/settings/shop_sd?driver=' . $driver->slug, 'Configure', 'data-fancybox-type="iframe" class="fancybox awesome small"') : '';
                                echo '</td>';
                            echo '</tr>';
                        }

                        echo '<tbody>';
                    echo '</table>';
                    echo '<hr />';

                } else {

                    echo '<p class="system-alert error">';
                        echo '<strong>No shipping drivers are available.</strong>';
                        echo '<br />I could not find any shipping drivers. Please contact the developers on ' . mailto(APP_DEVELOPER_EMAIL) . ' for assistance.';
                    echo '</p>';
                }

            ?>
            <p style="margin-top:1em;margin-bottom:0;">
                <?=form_submit('submit', lang('action_save_changes'), 'class="awesome" style="margin-bottom:0;"')?>
            </p>
            <?=form_close()?>
        </div>
    </section>
</div>