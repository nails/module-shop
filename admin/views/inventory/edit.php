<div class="group-shop inventory edit">
    <?=form_open(null, 'id="product-form"')?>
    <ul class="tabs" data-tabgroup="main-product">
        <li class="tab active">
            <a href="#" data-tab="tab-basics">Product Info</a>
        </li>

        <li class="tab">
            <a href="#" id="tabber-meta" data-tab="tab-meta">Product Meta</a>
        </li>

        <li class="tab">
            <a href="#" id="tabber-description" data-tab="tab-description">Description</a>
        </li>

        <li class="tab">
            <a href="#" id="tabber-variations" data-tab="tab-variations">Variations</a>
        </li>

        <li class="tab">
            <a href="#" id="tabber-gallery" data-tab="tab-gallery">Gallery</a>
        </li>

        <li class="tab">
            <a href="#" id="tabber-attributes" data-tab="tab-attributes">Attributes</a>
        </li>

        <li class="tab">
            <a href="#" id="tabber-ranges-collections" data-tab="tab-ranges-collections">Ranges &amp; Collections</a>
        </li>

        <li class="tab">
            <a href="#" id="tabber-seo" data-tab="tab-seo">SEO</a>
        </li>
    </ul>

    <section class="tabs pages main-product">

        <div class="tab page basics active fieldset" id="tab-basics">
            <?php

                $field             = array();
                $field['key']      = 'type_id';
                $field['label']    = 'Type';
                $field['required'] = true;
                $field['class']    = 'type_id select2';
                $field['id']       = 'type_id';
                $field['info']     = '<a href="#" class="manage-types awesome orange small">Manage Product Types</a>';
                $field['default']  = !empty($item->type->id) ? $item->type->id : null;

                if (count($product_types_flat) == 1) {

                    reset($product_types_flat);
                    $_id = key($product_types_flat);

                    //  Only one product type, no need to render a drop down
                    echo '<input type="hidden" name="' . $field['key'] . '" value="' . $_id . '" class="' . $field['key'] . '">';

                } else {

                    echo form_field_dropdown($field, $product_types_flat);
                }

                // --------------------------------------------------------------------------

                $field                = array();
                $field['key']         = 'label';
                $field['label']       = 'Label';
                $field['required']    = true;
                $field['placeholder'] = 'Give this product a label';
                $field['default']     = !empty($item->label) ? $item->label : '';

                echo form_field($field);

                // --------------------------------------------------------------------------

                $field             = array();
                $field['key']      = 'is_active';
                $field['label']    = 'Is Active';
                $field['default']  = true;
                $field['text_on']  = strtoupper(lang('yes'));
                $field['text_off'] = strtoupper(lang('no'));
                $field['default']  = isset($item->is_active) ? $item->is_active : true;

                echo form_field_boolean($field);

                // --------------------------------------------------------------------------

                $field          = array();
                $field['key']   = 'brands[]';
                $field['label'] = 'Brands';
                $field['class'] = 'brands select2';
                $field['info']  = '<a href="#" class="manage-brands awesome orange small">Manage Brands</a>';
                $tip            = 'If this product contains multiple brands (e.g a hamper) specify them all here.';

                //  Defaults
                if ($this->input->post('brands')) {

                    $field['default'] = $this->input->post('brands');

                } elseif (!empty($item->brands)) {

                    $field['default'] = array();

                    //  Build an array which matches the potential $_POST array
                    foreach ($item->brands as $brand) {

                        $field['default'][] = $brand->id;
                    }
                }

                echo form_field_dropdown_multiple($field, $brands, $tip);

                // --------------------------------------------------------------------------

                $field          = array();
                $field['key']   = 'categories[]';
                $field['label'] = 'Categories';
                $field['class'] = 'categories select2';
                $field['info']  = '<a href="#" class="manage-categories awesome orange small">Manage Categories</a>';
                $tip            = 'Specify which categories this product falls into.';

                //  Defaults
                if ($this->input->post('categories')) {

                    $field['default'] = $this->input->post('categories');

                } elseif (!empty($item->categories)) {

                    $field['default'] = array();

                    //  Build an array which matches the potential $_POST array
                    foreach ($item->categories as $category) {

                        $field['default'][] = $category->id;
                    }
                }

                echo form_field_dropdown_multiple($field, $categories, $tip);

                // --------------------------------------------------------------------------

                $field          = array();
                $field['key']   = 'tags[]';
                $field['label'] = 'Tags';
                $field['class'] = 'tags select2';
                $field['info']  = '<a href="#" class="manage-tags awesome orange small">Manage Tags</a>';
                $tip            = 'Use tags to associate products together, e.g. events.';

                //  Defaults
                if ($this->input->post('tags')) {

                    $field['default'] = $this->input->post('tags');

                } elseif (!empty($item->tags)) {

                    $field['default'] = array();

                    //  Build an array which matches the potential $_POST array
                    foreach ($item->tags as $tag) {

                        $field['default'][] = $tag->id;
                    }
                }

                echo form_field_dropdown_multiple($field, $tags, $tip);

                // --------------------------------------------------------------------------

                $field             = array();
                $field['key']      = 'tax_rate_id';
                $field['label']    = 'Tax Rate';
                $field['class']    = 'tax_rate_id select2';
                $field['required'] = true;
                $field['info']     = '<a href="#" class="manage-tax-rates awesome orange small">Manage Tax Rates</a>';
                $field['default']  = !empty($item->tax_rate->id) ? $item->tax_rate->id : null;

                echo form_field_dropdown($field, $tax_rates);

            ?>
        </div>

        <div class="tab page meta fieldset" id="tab-meta">

            <fieldset>
                <legend>Dates &amp; Times</legend>
                <?php

                if (!empty($item->published) && $item->published != '0000-00-00 00:00{00') {

                    $publishedTime = toUserDatetime($item->published, 'Y-m-d H:i:s');

                } else {

                    $publishedTime = date('Y-m-d H:i:00');
                }

                $field                = array();
                $field['key']         = 'published';
                $field['label']       = 'Published';
                $field['required']    = true;
                $field['placeholder'] = 'What date and time should this item be published on site.';
                $field['default']     = $publishedTime;
                $field['info']        = 'You can specify a date in the future if you wish, the system will not show ';
                $field['info']       .= 'products where the published date is in the future.';

                echo form_field_datetime($field);

                ?>
            </fieldset>

            <?php if (app_setting('enable_external_products', 'shop')) { ?>
            <fieldset>
                <legend>External Product</legend>
                <p>
                    If this item is sold on an external site then turn this setting on and the store will handle
                    redirecting users to the appropriate vendor.
                </p>
                <?php

                    echo '<div id="is-external">';

                        $field             = array();
                        $field['key']      = 'is_external';
                        $field['label']    = 'Is External';
                        $field['text_on']  = strtoupper(lang('yes'));
                        $field['text_off'] = strtoupper(lang('no'));
                        $field['default']  = isset($item->is_external) ? $item->is_external : false;

                        echo form_field_boolean($field);

                    echo '</div>';

                    $_display = $field['default'] || $this->input->post('is_external') ? 'block' : 'none';
                    echo '<div id="is-external-fields" style="display:' . $_display . '">';

                        $field                = array();
                        $field['key']         = 'external_vendor_label';
                        $field['label']       = 'External Vendor';
                        $field['sub_label']   = 'Max. 150 characters';
                        $field['placeholder'] = 'The name of the vendor';
                        $field['default']     = isset($item->external_vendor_label) ? $item->external_vendor_label : '';

                        echo form_field($field);

                        // --------------------------------------------------------------------------

                        $field                = array();
                        $field['key']         = 'external_vendor_url';
                        $field['label']       = 'External Vendor URL';
                        $field['sub_label']   = 'Max. 500 characters';
                        $field['placeholder'] = 'The URL of the page to redirect the user to';
                        $field['default']     = isset($item->external_vendor_url) ? $item->external_vendor_url : '';

                        echo form_field($field);

                    echo '</div>';

                ?>
            </fieldset>
            <?php }; ?>

        </div>

        <div class="tab page description" id="tab-description">
        <?php

            $field            = array();
            $field['key']     = 'description';
            $field['default'] = !empty($item->description) ? $item->description : '';


            echo form_error($field['key'], '<p class="system-alert error">', '</p>');
            echo form_textarea($field['key'], set_value($field['key'], $field['default']), 'class="wysiwyg" id="productDescription"');

        ?>
        </div>

        <div class="tab page variations" id="tab-variations">
            <p>
                Variations allow you to offer the same product but with different attributes (e.g colours or sizes).
                Shoppers will be given the choice of which variation they wish to purchase. There must always be at
                least one variation of a product. Confused? <a href="#help-variation-examples" class="fancybox">See
                some examples</a>.
            </p>
            <div id="product-variations">
                <?php

                    //  Data which will be passed to template
                    $viewData                 = array();
                    $viewData['is_first']     = true;
                    $viewData['is_php']       = true;
                    $viewData['counter']      = 0;
                    $viewData['num_variants'] = 0;

                    /**
                     * Render, if there's POST then make sure we render it enough times,
                     * otherwise check to see if there's $item data
                     */

                    if ($this->input->post('variation')) {

                        $_variations = $this->input->post('variation');

                    } elseif (!empty($item->variations)) {

                        $_variations = array();

                        //  Build an array which matches the potential $_POST array
                        foreach ($item->variations as $variation) {

                            $_variations[] = $variation;
                        }

                    } else {

                        $_variations = array();
                    }

                    if (!empty($_variations)) {

                        foreach ($_variations as $variation) {

                            $viewData['variation']    = $variation;
                            $viewData['num_variants'] = count($_variations);

                            $template = \Nails\Admin\Helper::loadInlineView(
                                'utilities/template-mustache-inventory-variant',
                                $viewData,
                                true
                            );

                            echo $this->mustache->render($template, $viewData);

                            $viewData['counter']++;
                            $viewData['is_first'] = false;
                        }

                    } else {

                        $template = \Nails\Admin\Helper::loadInlineView(
                            'utilities/template-mustache-inventory-variant',
                            $viewData,
                            true
                        );

                        echo $this->mustache->render($template, $viewData);
                    }

                ?>
            </div>
            <div class="add-variation-button enabled">
                <p class="enabled">
                    <a href="#" id="product-variation-add" class="awesome green small">Add Variation</a>
                </p>
                <p class="disabled">
                    <a class="awesome grey small">Add Variation</a>
                    <span class="no-more-variations">
                        The specified product type does not allow for any more variationsto be added.
                    </span>
                </p>
            </div>
        </div>

        <div class="tab page gallery" id="tab-gallery" >
            <p>
                Upload images to the product gallery. Once uploaded you can specify which variations are featured
                on the <a href="#" class="switch-to-variations">variations tab</a>.
                <small>
                <?php

                    $_max_upload   = ini_get('upload_max_filesize');
                    $_max_upload   = return_bytes($_max_upload);

                    $_max_post     = ini_get('post_max_size');
                    $_max_post     = return_bytes($_max_post);

                    $_memory_limit = ini_get('memory_limit');
                    $_memory_limit = return_bytes($_memory_limit);

                    $_upload_mb    = min($_max_upload, $_max_post, $_memory_limit);
                    $_upload_mb    = format_bytes($_upload_mb);

                    echo 'Images only, max file size is ' . $_upload_mb . '.';

                ?>
                </small>
            </p>
            <p>
                <input type="file" id="file_upload" />
            </p>
            <p class="system-alert notice" id="upload-message" style="display:none">
                <strong>Please be patient while files upload.</strong>
                <br />Tabs have been disabled until uploads are complete.
            </p>
            <?php

                /**
                 * Render, if there's POST then make sure we render it enough times,
                 * otherwise check to see if there's $item data
                 */

                if ($this->input->post('gallery')) {

                    $_gallery = $this->input->post('gallery');

                } elseif (!empty($item->gallery)) {

                    $_gallery = $item->gallery;

                } else {

                    $_gallery = array();
                }

            ?>
            <ul id="gallery-items" class="<?=!empty($_gallery) ? '' : 'empty' ?>">
                <li class="empty">
                    No images, why not upload some?
                </li>
                <?php

                    if (!empty($_gallery)) {

                        foreach ($_gallery as $image) {

                            $viewData = array(
                                'objectId' => $image
                            );

                            echo \Nails\Admin\Helper::loadInlineView(
                                'utilities/template-mustache-gallery-item',
                                $viewData,
                                true
                            );
                        }
                    }

                ?>
            </ul>
        </div>
        <div class="tab page attributes" id="tab-attributes">
            <p>
                Specify specific product attributes, e..g for a pair of jeans you might specify a 'Style' attribute and
                give it a value of 'Bootcut'. Attributes should be common across all variations of the product.
            </p>
            <table>
                <thead>
                    <tr>
                        <th class="attribute">Attribute</th>
                        <th class="value">Value</th>
                        <th class="delete">&nbsp;</th>
                    </tr>
                </thead>
                <tbody id="product-attributes">
                    <?php

                        /**
                         * Render, if there's POST then make sure we render it enough times,
                         * otherwise check to see if there's $item data
                         */

                        if ($this->input->post('attributes')) {

                            $_attributes = $this->input->post('attributes');

                        } elseif (!empty($item->attributes)) {

                            $_attributes = array();

                            //  Build an array which matches the potential $_POST array
                            foreach ($item->attributes as $attribute) {

                                $_temp                 = array();
                                $_temp['attribute_id'] = $attribute->id;
                                $_temp['value']        = $attribute->value;

                                $_attributes[] = $_temp;
                            }

                        } else {

                            $_attributes = array();
                        }

                        if (!empty($_attributes)) {

                            $counter = 0;
                            foreach ($_attributes as $attribute) {

                                $viewData = array('attribute' => $attribute, 'counter' => $counter);

                                echo \Nails\Admin\Helper::loadInlineView(
                                    'utilities/template-mustache-attribute',
                                    $viewData,
                                    true
                                );

                                $counter++;
                            }
                        }

                    ?>
                </tbody>
            </table>
            <p>
                <a href="#" id="product-attribute-add" class="awesome small green">Add Attribute</a>
                <a href="#" class="awesome small orange manage-attributes">Manage Attributes</a>
            </p>
        </div>

        <div class="tab page ranges-collections" id="tab-ranges-collections">
            <p>
                Specify which ranges and/or collections this product should appear in.
            </p>
            <p>
                A range is an actual line of stock, or a range of products from one of your
                suppliers. For example this might be the 'Jimi Hendrix' range from 'Vintage Rock Tees'.
            </p>
            <p>
                Collections offer you a unique way to combine stock into 'smart' categories,
                for example you might create collections for 'Gifts for Him', 'Gifts for Her',
                'Valentines Day Gifts', 'Stocking Fillers' etc.
            </p>
            <p>
                <strong>Ranges</strong>
            </p>
            <p>
                <select name="ranges[]" class="ranges select2" multiple="multiple" style="width:100%">
                <?php

                    /**
                     * Render, if there's POST then make sure we render it enough times,
                     * otherwise check to see if there's $item data
                     */

                    if ($this->input->post('ranges')) {

                        $_selected = $this->input->post('ranges');

                    } elseif (!empty($item->ranges)) {

                        $_selected = array();

                        //  Build an array which matches the potential $_POST array
                        foreach ($item->ranges as $range) {

                            $_selected[] = $range->id;
                        }

                    } else {

                        $_selected = array();
                    }

                    foreach ($ranges as $range) {

                        $_checked = array_search($range->id, $_selected) !== false ? 'selected="selected"' : '';

                        echo '<option value="' . $range->id . '" ' . $_checked . '>';
                        echo !$range->is_active ? '[INACTIVE] ' : '';
                        echo $range->label;
                        echo trim($range->description) ? ' - ' . word_limiter(trim($range->description), 25) : '';
                        echo '</option>';
                    }

                ?>
                </select>
            </p>
            <p>
                <a href="#" class="awesome small orange manage-ranges">Manage Ranges</a>
            </p>
            <hr />
            <p>
                <strong>Collections</strong>
            </p>
            <p>
                <select name="collections[]" class="collections select2" multiple="multiple" style="width:100%">
                <?php

                    //  Render, if there's POST then make sure we render it enough times
                    //  Otherwise check to see if there's $item data

                    if ($this->input->post('collections')) {

                        $_selected = $this->input->post('collections');

                    } elseif (!empty($item->collections)) {

                        $_selected = array();

                        //  Build an array which matches the potential $_POST array
                        foreach ($item->collections as $collection) {

                            $_selected[] = $collection->id;
                        }

                    } else {

                        $_selected = array();
                    }

                    foreach ($collections as $collection) {

                        $_checked = array_search($collection->id, $_selected) !== false ? 'selected="selected"' : '';

                        echo '<option value="' . $collection->id . '" ' . $_checked . '>';
                        echo !$collection->is_active ? '[INACTIVE] ' : '';
                        echo $collection->label;
                        echo $collection->description ? ' - ' . word_limiter($collection->description, 25) : '';
                        echo '</option>';
                    }

                ?>
                </select>
            </p>
            <p>
                <a href="#" class="awesome small orange manage-collections">Manage Collections</a>
            </p>
        </div>
        <div class="tab page seo" id="tab-seo">
            <p>
                Define some meta information here which will help search engines understand the product. Keep it
                relevant and concise, trying too hard and 'keyword flooding' can have the opposite effect.
            </p>
            <fieldset id="shop-inventory-create-seo">
                <legend>Search Engine Optimisation</legend>
                <?php

                    $field                = array();
                    $field['key']         = 'seo_title';
                    $field['label']       = 'Title';
                    $field['sub_label']   = 'Max. 150 characters';
                    $field['placeholder'] = 'Search Engine Optimised title';
                    $field['default']     = !empty($item->seo_title) ? $item->seo_title : '';

                    echo form_field($field, 'Keep this below 100 characters');

                    // --------------------------------------------------------------------------

                    $field                = array();
                    $field['key']         = 'seo_description';
                    $field['label']       = 'Description';
                    $field['sub_label']   = 'Max. 300 characters';
                    $field['placeholder'] = 'Search Engine Optimised description';
                    $field['type']        = 'textarea';
                    $field['default']     = !empty($item->seo_description) ? $item->seo_description : '';

                    echo form_field($field, 'Keep this relevant and below 140 characters');

                    // --------------------------------------------------------------------------

                    $field                = array();
                    $field['key']         = 'seo_keywords';
                    $field['label']       = 'Keywords';
                    $field['sub_label']   = 'Max. 150 characters';
                    $field['placeholder'] = 'Comma separated keywords';
                    $field['default']     = !empty($item->seo_keywords) ? $item->seo_keywords : '';

                    echo form_field($field, 'Comma seperated keywords. Try to keep to 10 or fewer.');

                ?>
            </fieldset>
        </div>

    </section>
    <p>
    <?php

        $_action = empty($item->id) ? lang('action_create') : lang('action_save_changes');
        echo form_submit('submit', $_action, 'class="awesome"');

    ?>
    </p>
    <?=form_close()?>
</div>
<script type="text/template" id="template-variation">
<?php

    $viewData                 = array();
    $viewData['is_first']     = false;
    $viewData['is_php']       = false;
    $viewData['counter']      = false;
    $viewData['variation']    = null;
    $viewData['num_variants'] = null;

    echo \Nails\Admin\Helper::loadInlineView(
        'utilities/template-mustache-inventory-variant',
        $viewData,
        true
    );

?>
</script>
<div id="dialog-confirm-delete" title="Confirm Delete" style="display:none;">
    <p>
        <span class="ui-icon ui-icon-alert" style="float: left; margin: 0 7px 0 0;"></span>
        This item will be removed from the interface and cannot be recovered.
        <strong>Are you sure?</strong>
    </p>
</div>
<div id="dialog-no-delete-one-variation" title="Cannot Delete" style="display:none;">
    <p>
        <span class="ui-icon ui-icon-alert" style="float: left; margin: 0 7px 0 0;"></span>
        Products must have at least one variation.
    </p>
</div>
<script type="text/template" id="template-uploadify">
    <li class="gallery-item uploadify-queue-item" id="${fileID}" data-instance_id="${instanceID}" data-file_id="${fileID}">
        <a href="#" data-instance_id="${instanceID}" data-file_id="${fileID}" class="remove"></a>
        <div class="progress" style="height:0%"></div>
        <div class="data data-cancel">CANCELLED</div>
    </li>
</script>
<script type="text/template" id="template-gallery-item">
<?php

    $viewData             = array();
    $viewData['objectId'] = false;

    echo \Nails\Admin\Helper::loadInlineView(
        'utilities/template-mustache-gallery-item',
        $viewData,
        true
    );

?>
</script>
<script type="text/template" id="template-attribute">
<?php

    $viewData = array('attribute' => null);
    echo \Nails\Admin\Helper::loadInlineView(
        'utilities/template-mustache-attribute',
        $viewData,
        true
    );

?>
</script>
<div id="help-variation-examples" style="display:none;">
    <h1>Variations Explained</h1>
    <p>
        When the same product can be sold in multiple styles, colours
        or sizes then a variation should be created for each item. This
        allows the system to keep track of the different variants of items
        which have been sold. Each product must contain at least one variation
        (i.e the first variant is the original product).
    </p>
    <p>
        Additionally, when images are uploaded you can specify if a variant appears in
        the image - this allows the front end shop to only show relevant images when the
        shopper changes the variant they want.
    </p>
    <p>
        Remember that how you use variations is not set in stone, it's purely a system
        to give the shopper a choice from within a single product, rather than going back
        and forth between multiple products.
    </p>
    <h2>Examples</h2>
    <p>
        The following examples are designed to help you choose how you might classify items,
        remember, ultimately it's your own decision as to how items are grouped and should seem
        natural as a shopper.
    </p>
    <h3>Clothing</h3>
    <p>
        Because clothing can come in both a size and a colour it can be confusing as to how to
        properly distingush between them. We suggest that you group these together like so:
    </p>
    <ul>
        <li><strong>Product: Ladies Jeans</strong></li>
        <li>Variant: Black - Size 8</li>
        <li>Variant: Black - Size 10</li>
        <li>Variant: Black - Size 12</li>
        <li>Variant: Red - Size 8</li>
        <li>Variant: Red - Size 10</li>
        <li>Variant: Red - Size 12</li>
    </ul>
    <p>
        This way, the system knows exactly how many pairs of red ladies jeans there are in a size 8,
        etc. Also, each variation can have it's own SKU which can help warehouse operators correctly
        find the right items. Furthermore, when ordered alphabetically the products which you'd naturally
        expect to be together are grouped nicely.
    </p>
    <h3>Books</h3>
    <p>
        Books, do not come in sizes and colours like clothing, however they do have alternate versions:
        paperback and hardback.
    </p>
    <p>
        These versions also affect the price, which is why versions can define their own price point. In
        the same vein, hardback books can be heavier which'll affect shipping costs.
    </p>
    <ul>
        <li><strong>Product: Harry Potter and the Philosopher's Stone</strong></li>
        <li>Variant: Paperback, £11.99</li>
        <li>Variant: Hardback, £16.99</li>
        <li>Variant: Signed Copy, £20.99, only 1 copy available</li>
        <li>Variant: Special Edition - free wand, £17.99</li>
    </ul>
    <p>
        As you can see the same book can be sold in different formats. Each variant can also define it's own meta
        information, such as ISBN.
    </p>
    <p>
        <strong>Please note:</strong> a different <em>edition</em> of the book is a different product and should
        be sold as such.
    </p>
</div>
