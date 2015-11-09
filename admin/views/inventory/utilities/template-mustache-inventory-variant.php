<?php

    /**
     * Counter string
     * If the $variation var is passed then we're loading this in PHP and we want
     * to prefill the fields. The form_helper functions don't pick up the fields
     * automatically because of the Mustache ' . $_counter . ' variable.
     *
     * Additionally, make sure it's not === false as the value can persist when
     * this view is loaded multiple times.
     */

    $_counter = isset($variation) && $counter !== false ? $counter : '{{counter}}';

?>
<div class="variation" data-counter="<?=$_counter?>">
    <?php

        //  Pass the vraiation ID along for the ride too
        if (!empty($variation->id)) {

            echo form_hidden('variation[' . $_counter . '][id]', $variation->id);

        } elseif (!empty($variation['id'])) {

            echo form_hidden('variation[' . $_counter . '][id]', $variation['id']);
        }

    ?>
    <div class="not-applicable">
        <p>
            <strong>The specified product type has a limited number of variations it can support.</strong>
            This variation will be deleted when you submit this form.
        </p>
    </div>
    <ul class="tabs" data-tabgroup="variation">
        <li class="tab active">
            <a href="#" class="tabber-variation-details" data-tab="tab-variation-details">Details</a>
        </li>
        <li class="tab">
            <a href="#" data-tab="tab-variation-meta">Meta</a>
        </li>
        <li class="tab">
            <a href="#" data-tab="tab-variation-pricing">Pricing</a>
        </li>
        <li class="tab">
            <a href="#" data-tab="tab-variation-gallery">Gallery</a>
        </li>
        <li class="tab">
            <a href="#" class="tabber-variation-shipping" data-tab="tab-variation-shipping">Shipping</a>
        </li>
        <li class="action">
            <a href="#" class="delete">Delete</a>
        </li>
    </ul>
    <section class="tabs" data-tabgroup="variation">
        <div class="tab-page tab-variation-details active fieldset">
            <?php

                $field                = array();
                $field['key']         = 'variation[' . $_counter . '][label]';
                $field['label']       = 'Label';
                $field['required']    = true;
                $field['placeholder'] = 'Give this variation a title';
                $field['default']     = !empty($variation->label) ? $variation->label : '';

                echo form_field($field);

                // --------------------------------------------------------------------------

                $field                = array();
                $field['key']         = 'variation[' . $_counter . '][sku]';
                $field['label']       = 'SKU';
                $field['placeholder'] = 'This variation\'s Stock Keeping Unit; a unique offline identifier (e.g for POS or warehouses)';
                $field['default']     = !empty($variation->sku) ? $variation->sku : '';

                echo form_field($field);

                // --------------------------------------------------------------------------

                $field             = array();
                $field['key']      = 'variation[' . $_counter . '][stock_status]';
                $field['label']    = 'Stock Status';
                $field['class']    = 'select2 stock-status';
                $field['required'] = true;
                $field['default']  = !empty($variation->stock_status) ? $variation->stock_status : 'IN_STOCK';

                $options                 = array();
                $options['IN_STOCK']     = 'In Stock';
                $options['OUT_OF_STOCK'] = 'Out of Stock';

                echo form_field_dropdown($field, $options);

                // --------------------------------------------------------------------------

                $status  = set_value('variation[' . $_counter . '][stock_status]', $field['default']);
                $display = $status == 'IN_STOCK' ? 'block' : 'none';

                echo '<div class="stock-status-field IN_STOCK" style="display:' . $display . '">';

                    $field                = array();
                    $field['key']         = 'variation[' . $_counter . '][quantity_available]';
                    $field['label']       = 'Quantity Available';
                    $field['placeholder'] = 'How many units of this variation are available? Leave blank for unlimited';
                    $field['default']     = isset($variation->quantity_available) ? $variation->quantity_available : '';

                    echo form_field($field);

                echo '</div>';

                // --------------------------------------------------------------------------

                $field             = array();
                $field['key']      = 'variation[' . $_counter . '][out_of_stock_behaviour]';
                $field['label']    = 'Out of Stock Behaviour';
                $field['class']    = 'select2 out-of-stock-behaviour';
                $field['required'] = true;
                $field['default']  = !empty($variation->out_of_stock_behaviour) ? $variation->out_of_stock_behaviour : 'OUT_OF_STOCK';
                $field['tip']      = 'Specify the behaviour of the item when the quantity available of an item reaches 0.';

                $options                 = array();
                $options['TO_ORDER']     = 'Behave as if: To Order';
                $options['OUT_OF_STOCK'] = 'Behave as if: Out of Stock';

                echo form_field_dropdown($field, $options);

                // --------------------------------------------------------------------------

                $status  = set_value('variation[' . $_counter . '][out_of_stock_behaviour]', $field['default']);
                $display = $status == 'TO_ORDER' ? 'block' : 'none';

                echo '<div class="out-of-stock-behaviour-field TO_ORDER" style="display:' . $display . '">';

                    $field                = array();
                    $field['key']         = 'variation[' . $_counter . '][out_of_stock_to_order_lead_time]';
                    $field['label']       = 'Out of Stock Lead Time';
                    $field['sub_label']   = 'Max. 50 characters';
                    $field['required']    = true;
                    $field['placeholder'] = 'How long is the lead time on orders for this product when it\'s out of stock?';
                    $field['default']     = !empty($variation->out_of_stock_to_order_lead_time) ? $variation->out_of_stock_to_order_lead_time : '';

                    echo form_field($field);

                echo '</div>';

                // --------------------------------------------------------------------------

                $field            = array();
                $field['key']     = 'variation[' . $_counter . '][is_active]';
                $field['label']   = 'Active';
                $field['default'] = !empty($variation->is_active) ? $variation->is_active : false;

                echo form_field_boolean($field);

            ?>
        </div>
        <div class="tab-page tab-variation-meta fieldset">
            <?php

                foreach ($product_types_meta as $productTypeId => $metaFields) {

                    echo '<div class="meta-fields meta-fields-' . $productTypeId . '" style="display:none;">';

                    if ($metaFields) {

                        $defaults = array();

                        //  Set any default values
                        if (isset($variation->meta)) {

                            //  DB Data
                            foreach ($variation->meta as $variationMeta) {

                                $defaults[$variationMeta->meta_field_id] = $variationMeta->value;

                                if ($variationMeta->allow_multiple) {

                                    $defaults[$variationMeta->meta_field_id] = implode(',', $defaults[$variationMeta->meta_field_id]);
                                }
                            }

                        } elseif (isset($variation['meta'][$productTypeId])) {

                            //  POST Data
                            foreach ($variation['meta'][$productTypeId] as $meta_field_id => $meta_field_value) {

                                $defaults[$meta_field_id] = $meta_field_value;
                            }
                        }

                        foreach ($metaFields as $metaField) {

                            $field                = array();
                            $field['key']         = 'variation[' . $_counter . '][meta][' . $productTypeId . '][' .  $metaField->id . ']';
                            $field['label']       = !empty($metaField->label)                  ? $metaField->label : '';
                            $field['sub_label']   = !empty($metaField->admin_form_sub_label)   ? $metaField->admin_form_sub_label : '';
                            $field['placeholder'] = !empty($metaField->admin_form_placeholder) ? $metaField->admin_form_placeholder : '';
                            $field['tip']         = !empty($metaField->admin_form_tip)         ? $metaField->admin_form_tip : '';
                            $field['class']       = !empty($metaField->allow_multiple)         ? 'allow-multiple' : '';
                            $field['default']     = !empty($defaults[$metaField->id])          ? $defaults[$metaField->id] : '';
                            $field['info']        = !empty($metaField->allow_multiple)         ? '<strong>Tip:</strong> This field accepts multiple selections, seperate multiple values with a comma or hit enter.' : '';

                            echo form_field($field);
                        }

                    } else {

                        echo '<p>There are no extra fields for this product type.</p>';
                    }

                    echo '</div>';
                }

            ?>
        </div>
        <div class="tab-page tab-variation-pricing">
            <?php if (count($currencies) > 1) { ?>
            <p>
                Define the price points for this variation. If you'd like to set a specific price for a certain
                currency then define that also otherwise the system will calculate automatically using current
                exchange rates.
            </p>
            <?php } ?>
            <table class="pricing-options">
                <thead>
                    <tr>
                        <th>Currency</th>
                        <th>Price</th>
                        <th>Sale Price</th>
                    </tr>
                </thead>
                <tbody>
                    <!--    BASE CURRENCY   -->
                    <?php

                        //  Prep the prices into an easy to access array
                        $price     = array();
                        $salePrice = array();

                        if (!empty($variation->price_raw)) {

                            $oCurrencyModel = \Nails\Factory::model('Currency', 'nailsapp/module-shop');

                            foreach ($variation->price_raw as $priceRaw) {

                                $price[$priceRaw->currency->code]     = $oCurrencyModel->intToFloat($priceRaw->price, $priceRaw->currency->code);
                                $salePrice[$priceRaw->currency->code] = $oCurrencyModel->intToFloat($priceRaw->sale_price, $priceRaw->currency->code);
                            }
                        }

                    ?>
                    <tr>
                        <td class="currency">
                        <?php

                            echo SHOP_BASE_CURRENCY_CODE;

                            $key = 'variation[' . $_counter . '][pricing][0][currency]';
                            echo form_hidden($key, SHOP_BASE_CURRENCY_CODE);

                        ?>
                        </td>
                        <td class="price">
                        <?php

                            $key     = 'variation[' . $_counter . '][pricing][0][price]';
                            $error   = form_error($key, '<span class="error show-in-tabs">', '</span>');
                            $class   = array('variation-price', SHOP_BASE_CURRENCY_CODE);
                            $default = !empty($price[SHOP_BASE_CURRENCY_CODE]) ? $price[SHOP_BASE_CURRENCY_CODE] : '';

                            if ($error) {

                                $class[] = 'error';
                            }

                            echo form_input(
                                $key,
                                set_value($key, $default),
                                'data-prefix="' . SHOP_BASE_CURRENCY_SYMBOL . '" ' .
                                'data-code="' . SHOP_BASE_CURRENCY_CODE . '" ' .
                                'class="' . implode(' ', $class) . '" ' .
                                'placeholder="Price"'
                            );
                            echo $error;

                        ?>
                        </td>
                        <td class="price-sale">
                        <?php

                            $key     = 'variation[' . $_counter . '][pricing][0][sale_price]';
                            $error   = form_error($key, '<span class="error show-in-tabs">', '</span>');
                            $class   = array('variation-price-sale', SHOP_BASE_CURRENCY_CODE);
                            $default = !empty($salePrice[SHOP_BASE_CURRENCY_CODE]) ? $salePrice[SHOP_BASE_CURRENCY_CODE] : '';

                            if ($error) {

                                $class[] = 'error';
                            }

                            echo form_input(
                                $key,
                                set_value($key, $default),
                                'data-prefix="' . SHOP_BASE_CURRENCY_SYMBOL . '" ' .
                                'data-code="' . SHOP_BASE_CURRENCY_CODE . '" ' .
                                'class="' . implode(' ', $class) . '" ' .
                                'placeholder="Sale Price"'
                            );
                            echo $error;

                        ?>
                        </td>
                    </tr>
                    <!--    OTHER CURRENCIES    -->
                    <?php

                        $counterInside = 1;
                        foreach ($currencies as $currency) {

                            if ($currency->code != SHOP_BASE_CURRENCY_CODE) {

                                ?>
                                <tr>
                                    <td class="currency">
                                        <?php

                                            echo $currency->code;

                                            $key = 'variation[' . $_counter . '][pricing][' . $counterInside . '][currency]';
                                            echo form_hidden($key, $currency->code);

                                        ?>
                                    </td>
                                    <td class="price">
                                        <?php

                                            $key     = 'variation[' . $_counter . '][pricing][' . $counterInside . '][price]';
                                            $error   = form_error($key, '<span class="error show-in-tabs">', '</span>');
                                            $class   = array('variation-price', $currency->code);
                                            $default = !empty($price[$currency->code]) ? $price[$currency->code] : '';

                                            if ($error) {

                                                $class[] = 'error';
                                            }

                                            echo form_input(
                                                $key,
                                                set_value($key, $default),
                                                'data-prefix="' . $currency->symbol . '" ' .
                                                'data-code="' . $currency->code . '" ' .
                                                'class="' . implode(' ', $class) . '" ' .
                                                'placeholder="Calculate automatically from ' . SHOP_BASE_CURRENCY_CODE . '"'
                                            );
                                            echo $error;

                                        ?>
                                    </td>
                                    <td class="price-sale">
                                        <?php

                                            $key     = 'variation[' . $_counter . '][pricing][' . $counterInside . '][sale_price]';
                                            $error   = form_error($key, '<span class="error show-in-tabs">', '</span>');
                                            $class   = array('variation-price-sale', $currency->code);
                                            $default = !empty($salePrice[$currency->code]) ? $salePrice[$currency->code] : '';

                                            if ($error) {

                                                $class[] = 'error';
                                            }

                                            echo form_input(
                                                $key,
                                                set_value($key, $default),
                                                'data-prefix="' . $currency->symbol . '" ' .
                                                'data-code="' . $currency->code . '" ' .
                                                'class="' . implode(' ', $class) . '"' .
                                                'placeholder="Calculate automatically from ' . SHOP_BASE_CURRENCY_CODE . '"'
                                            );
                                            echo $error;

                                        ?>
                                    </td>
                                </tr>
                                <?php

                                $counterInside++;
                            }
                        }

                    ?>
                </tbody>
            </table>
            <?php

                $display = empty($isFirst) || empty($numVariants) || $numVariants == 1 ? 'none' : 'block';
                echo '<p class="variation-sync-prices" style="display:' . $display . '">';
                    echo '<a href="#" class="awesome small orange">Sync Prices</a>';
                echo '</p>';

            ?>
        </div>
        <div class="tab-page tab-variation-gallery">
            <p>
                Specify which, if any, of the uploaded gallery images feature this product variation.
            </p>
            <?php

                //  Render, if there's POST then make sure we render it enough times
                //  Otherwise check to see if there's $item data

                if ($this->input->post('gallery')) {

                    $_gallery  = $this->input->post('gallery');
                    $_selected = isset($_POST['variation'][$_counter]['gallery']) ? $_POST['variation'][$_counter]['gallery'] : array();

                } elseif (!empty($item->gallery)) {

                    $_gallery  = $item->gallery;
                    $_selected = !empty($variation->gallery) ? $variation->gallery : array();

                } else {

                    $_gallery  = array();
                    $_selected = array();
                }

            ?>
            <ul class="gallery-associations <?=!empty($_gallery) ? '' : 'empty' ?>">
                <li class="empty">No images have been uploaded; upload some using the <a href="#">Gallery tab</a></li>
                <?php

                    if (!empty($_gallery)) {

                        foreach ($_gallery as $image) {

                            //  Is this item selected for this variation?
                            $_checked = array_search($image, $_selected) !== false ? 'selected' : false;

                            echo '<li class="image object-id-' . $image . ' ' . $_checked . '">';
                                echo form_checkbox('variation[' . $_counter . '][gallery][]', $image, (bool) $_checked);
                                echo img(array(
                                    'src'   => cdnCrop($image, 34, 34),
                                    'style' => 'width:34px;height:34px;'
                                ));
                            echo '</li>';
                        }
                    }

                ?>
                <li class="actions">
                    <a href="#" data-function="all" class="action awesome small orange">Select All</a>
                    <a href="#" data-function="none" class="action awesome small orange">Select None</a>
                    <a href="#" data-function="toggle" class="action awesome small orange">Toggle</a>
                </li>
            </ul>
        </div>
        <div class="tab-page tab-variation-shipping fieldset">
            <?php

                if (!empty($shipping_driver)) {

                    echo '<div class="shipping-collection-only">';

                        $field             = array();
                        $field['key']      = 'variation[' . $_counter . '][shipping][collection_only]';
                        $field['class']    = 'field-collection-only';
                        $field['label']    = 'Collection Only';
                        $field['readonly'] = !app_setting('warehouse_collection_enabled', 'shop');
                        $field['info']     = !app_setting('warehouse_collection_enabled', 'shop') ? '<strong>Warehouse Collection is disabled</strong>' : '';
                        $field['info']    .= !app_setting('warehouse_collection_enabled', 'shop') && userHasPermission('admin:shop:settings:update') ? '<br />if you wish to allow customers to collect from your warehouse you must enable it in ' . anchor('admin/shop/settings', 'settings', 'class="confirm" data-title="Stop Editing?" data-body="Any unsaved changes will be lost."') . '.' : '';
                        $field['default']  = isset($variation->shipping->collection_only) ? (bool) $variation->shipping->collection_only : false;
                        $tip               = 'Items marked as collection only will be handled differently in checkout and reporting.';

                        echo form_field_boolean($field, $tip);

                        $optionsHidden = $field['default'] ? 'block' : 'none';

                    echo '</div>';

                    // --------------------------------------------------------------------------

                    if (!empty($shipping_options_variant)) {

                        $display = $field['default'] ? 'none' : 'block';
                        echo '<div class="shipping-driver-options" style="display:' . $display . '">';

                            //  Any further options from the shipping driver?
                            foreach ($shipping_options_variant as $field) {

                                //  Prep the field names
                                if (empty($field['key'])) {

                                    continue;
                                }

                                //  Order is important here as $field['key'] gets overwritten
                                $default          = isset($variation->shipping->driver_data[$shipping_driver->slug][$field['key']]) ? $variation->shipping->driver_data[$shipping_driver->slug][$field['key']] : '';
                                $field['key']     = 'variation[' . $_counter . '][shipping][driver_data][' . $shipping_driver->slug . '][' . $field['key'] . ']';
                                $field['default'] = set_value($field['key'], $default);
                                $field['class']   = isset($field['class']) ? $field['class'] . ' driver-option' : 'driver-option';

                                //  @todo: Use admin form builder - Asana ticket: https://app.asana.com/0/6627768688940/15891120890395
                                $_type = isset($field['type']) ? $field['type'] : '';

                                switch ($_type) {

                                    case 'dropdown':

                                        echo form_field_dropdown($field);
                                        break;

                                    case 'bool':
                                    case 'boolean':

                                        echo form_field_boolean($field);
                                        break;

                                    default:

                                        echo form_field($field);
                                        break;
                                }
                            }

                        echo '</div>';

                        echo '<div class="shipping-driver-options-hidden" style="display:' . $optionsHidden . '">';
                            echo '<p class="system-alert notice" style="margin-top:1em;">';
                                echo 'Further shipping options have been hidden because the item is set as ';
                                echo '"collection only" and will not be included while calculating shipping costs.';
                            echo '</p>';
                        echo '</div>';
                    }

                    // --------------------------------------------------------------------------

                    $display = empty($isFirst) || empty($numVariants) || $numVariants == 1 ? 'none' : 'block';
                    echo '<p class="variation-sync-shipping" style="display:' . $display . '">';
                        echo '<a href="#" class="awesome small orange">Sync Shipping</a>';
                    echo '</p>';

                } else {

                    echo '<p class="system-alert message">';
                        echo '<strong>No Shipping Drivers Enabled.</strong>';
                        echo userHasPermission('admin:shop:settings:update') ? '<br />You can enable and configure shipping drivers in ' . anchor('admin/shop/settings', 'settings', 'class="confirm" data-title="Stop Editing?" data-body="Any unsaved changes will be lost."') . '.' : '';
                    echo '</p>';
                }

            ?>
        </div>
    </section>
</div>