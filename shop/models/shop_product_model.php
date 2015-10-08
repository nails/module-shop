<?php

/**
 * This model manages Shop Products
 *
 * @package  Nails
 * @subpackage  module-shop
 * @category    Model
 * @author    Nails Dev Team
 * @link
 */

class NAILS_Shop_product_model extends NAILS_Model
{
    /**
     * Construct the model
     */
    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        $this->table                             = NAILS_DB_PREFIX . 'shop_product';
        $this->tablePrefix                       = 'p';
        $this->table_attribute                   = NAILS_DB_PREFIX . 'shop_product_attribute';
        $this->table_brand                       = NAILS_DB_PREFIX . 'shop_product_brand';
        $this->table_supplier                    = NAILS_DB_PREFIX . 'shop_product_supplier';
        $this->table_category                    = NAILS_DB_PREFIX . 'shop_product_category';
        $this->table_collection                  = NAILS_DB_PREFIX . 'shop_product_collection';
        $this->table_gallery                     = NAILS_DB_PREFIX . 'shop_product_gallery';
        $this->table_range                       = NAILS_DB_PREFIX . 'shop_product_range';
        $this->table_sale                        = NAILS_DB_PREFIX . 'shop_sale_product';
        $this->table_tag                         = NAILS_DB_PREFIX . 'shop_product_tag';
        $this->table_related                     = NAILS_DB_PREFIX . 'shop_product_related';
        $this->table_variation                   = NAILS_DB_PREFIX . 'shop_product_variation';
        $this->table_variation_gallery           = NAILS_DB_PREFIX . 'shop_product_variation_gallery';
        $this->table_variation_product_type_meta = NAILS_DB_PREFIX . 'shop_product_variation_product_type_meta';
        $this->table_variation_price             = NAILS_DB_PREFIX . 'shop_product_variation_price';
        $this->table_type                        = NAILS_DB_PREFIX . 'shop_product_type';
        $this->table_tax_rate                    = NAILS_DB_PREFIX . 'shop_tax_rate';

        // --------------------------------------------------------------------------

        $this->destructiveDelete = false;

        // --------------------------------------------------------------------------

        //  Shop's base URL
        $this->shopUrl = $this->shop_model->getShopUrl();
    }

    // --------------------------------------------------------------------------

    /**
     * Creates a new object
     * @param  array $data      The data to create the object with
     * @param  bool  $returnObj Whether to return just the new ID or the full object
     * @return mixed
     */
    public function create($data = array(), $returnObj = false)
    {
        //  Do all we need to do with the incoming data
        $createData = $this->createUpdatePrepData($data);

        if (!$createData) {

            return false;
        }

        // --------------------------------------------------------------------------

        //  Execute
        $id = $this->createUpdateExecute($createData);

        //  Wrap it all up
        if ($id) {

            if ($returnObj) {

                return $this->get_by_id($id);

            } else {

                return $id;
            }
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Updates an existing object
     * @param  integer $id   The ID of the object to update
     * @param  array   $data The data to update the object with
     * @return bool
     */
    public function update($id, $data = array())
    {
        $current = $this->get_by_id($id);

        if (!$current) {

            $this->_set_error('Invalid product ID');
            return false;
        }

        // --------------------------------------------------------------------------

        //  Do all we need to do with the incoming data
        $updateData = $this->createUpdatePrepData($data, $id);

        if (!$updateData) {

            return false;
        }

        $updateData->id = $id;

        // --------------------------------------------------------------------------

        //  Execute
        $id = $this->createUpdateExecute($updateData);

        //  Wrap it all up
        if ($id) {

            return true;

        } else {

            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Prepares data, ready for the DB
     * @param  array   $data Raw data to use for the update/create
     * @param  integer $id   If updating, the ID of the item being updated
     * @return mixed         stdClass on success, false of failure
     */
    protected function createUpdatePrepData($data, $id = null)
    {
        //  Quick check of incoming data
        $_data = new \stdClass();

        if (empty($data['label'])) {

            $this->_set_error('Label is a required field.');
            return false;
        }

        // --------------------------------------------------------------------------

        //  Slug
        //  ====

        $_data->slug = $this->_generate_slug($data['label'], '', '', $this->table, null, $id);

        //  Product Info
        //  ============

        $_data->type_id = isset($data['type_id']) ? (int) $data['type_id']  : null;

        if (!$_data->type_id) {

            $this->_set_error('Product type must be defined.');
            return false;
        }

        $_data->label           = isset($data['label']) ? trim($data['label']) : null;
        $_data->is_active       = isset($data['is_active']) ? (bool) $data['is_active'] : false;
        $_data->is_deleted      = isset($data['is_deleted']) ? (bool) $data['is_deleted'] : false;
        $_data->brands          = isset($data['brands']) ? $data['brands'] : array();
        $_data->suppliers       = isset($data['suppliers']) ? $data['suppliers'] : array();
        $_data->categories      = isset($data['categories']) ? $data['categories'] : array();
        $_data->tags            = isset($data['tags']) ? $data['tags'] : array();
        $_data->google_category = !empty($data['google_category']) ? trim($data['google_category']) : null;

        if (app_setting('enable_external_products', 'shop')) {

            $_data->is_external           = isset($data['is_external']) ? (bool) $data['is_external'] : false;
            $_data->external_vendor_label = isset($data['external_vendor_label']) ? $data['external_vendor_label'] : '';
            $_data->external_vendor_url   = isset($data['external_vendor_url']) ? $data['external_vendor_url'] : '';
        }

        $_data->tax_rate_id = isset($data['tax_rate_id']) && (int) $data['tax_rate_id'] ? (int) $data['tax_rate_id'] : null;

        // --------------------------------------------------------------------------

        //  Description
        //  ===========
        $_data->description = isset($data['description']) ? $data['description']    : null;

        // --------------------------------------------------------------------------

        //  Variants - Loop variants
        //  ========================

        if (!isset($data['variation']) || !$data['variation']) {

            $this->_set_error('At least one variation is required.');
            return false;
        }

        $_data->variation = array();
        $productType      = $this->shop_product_type_model->get_by_id($_data->type_id);

        if (!$productType) {

            $this->_set_error('Invalid Product Type');
            return false;

        } else {

            $_data->is_physical = $productType->is_physical;
        }

        $this->load->model('shop/shop_product_type_meta_model');
        $productTypeMeta = $this->shop_product_type_meta_model->getByProductTypeId($productType->id);

        $skuTracker = array();

        foreach ($data['variation'] as $index => $v) {

            //  Details
            //  -------

            $_data->variation[$index] = new \stdClass();

            //  If there's an ID note it down, we'll be using it later as a switch between INSERT and UPDATE
            if (!empty($v['id'])) {

                $_data->variation[$index]->id = $v['id'];
            }

            $_data->variation[$index]->label     = isset($v['label']) ? $v['label'] : null;
            $_data->variation[$index]->sku       = isset($v['sku']) ? $v['sku'] : null;
            $_data->variation[$index]->is_active = empty($v['is_active']) ? false : true;

            $skuTracker[] = $_data->variation[$index]->sku;

            //  Stock
            //  -----

            $_data->variation[$index]->stock_status = isset($v['stock_status']) ? $v['stock_status'] : 'OUT_OF_STOCK';
            $stockStatus = $_data->variation[$index]->stock_status;

            switch ($stockStatus) {

                case 'IN_STOCK' :

                    $available = trim($v['quantity_available']);

                    if ($v['quantity_available'] === '') {

                        $_data->variation[$index]->quantity_available = null;

                    } else {

                        $_data->variation[$index]->quantity_available = (int) $available;
                    }

                    $_data->variation[$index]->lead_time = null;
                    break;

                case 'OUT_OF_STOCK' :

                    //  Shhh, be vewy qwiet, we're huntin' wabbits.
                    $_data->variation[$index]->quantity_available = 0;
                    $_data->variation[$index]->lead_time          = null;
                    break;
            }

            /**
             * If the status is IN_STOCK but there is no stock, then we should forcibly set
             * as if OUT_OF_STOCK was set.
             */

            $available = $_data->variation[$index]->quantity_available;

            if ($stockStatus == 'IN_STOCK' && !is_null($available) && $available == 0) {

                $_data->variation[$index]->stock_status = 'OUT_OF_STOCK';
                $_data->variation[$index]->lead_time    = null;
            }

            //  Out of Stock Behaviour
            //  ----------------------

            $_data->variation[$index]->out_of_stock_behaviour = isset($v['out_of_stock_behaviour']) ? $v['out_of_stock_behaviour'] : 'OUT_OF_STOCK';

            switch ($_data->variation[$index]->out_of_stock_behaviour) {

                case 'TO_ORDER' :

                    $_data->variation[$index]->out_of_stock_to_order_lead_time = isset($v['out_of_stock_to_order_lead_time']) ? $v['out_of_stock_to_order_lead_time'] : null;
                    break;

                case 'OUT_OF_STOCK' :

                    //  Shhh, be vewy qwiet, we're huntin' wabbits.
                    $_data->variation[$index]->out_of_stock_to_order_lead_time = null;
                    break;
            }

            //  Meta
            //  ----

            $_data->variation[$index]->meta = array();

            //  No need to set variation ID, that will be set later on during execution
            if (isset($v['meta'][$_data->type_id])) {

                foreach ($v['meta'][$_data->type_id] as $field_id => $value) {

                    if (!empty($value)) {

                        /**
                         * Test to see if this field allows multiple values, if it does then explode
                         * it and create multiple elements, if not, leave as is
                         */

                        foreach ($productTypeMeta as $meta) {

                            if ($meta->id == $field_id) {

                                $allowMultiple = true;
                                break;
                            }
                        }

                        if (empty($allowMultiple)) {

                            $temp                  = array();
                            $temp['meta_field_id'] = $field_id;
                            $temp['value']         = $value;
                            $_data->variation[$index]->meta[] = $temp;

                        } else {

                            $values = explode(',', $value);
                            foreach ($values as $val) {

                                $temp                  = array();
                                $temp['meta_field_id'] = $field_id;
                                $temp['value']         = $val;
                                $_data->variation[$index]->meta[] = $temp;
                            }
                        }
                    }
                }
            }

            //  Pricing
            //  -------
            $_data->variation[$index]->pricing = array();

            if (isset($v['pricing'])) {

                //  At the very least the base price must be defined
                $basePriceSet = false;

                foreach ($v['pricing'] as $priceIndex => $price) {

                    if (empty($price['currency'])) {

                        $this->_set_error('"Currency" field is required for all variant prices.');
                        return false;
                    }

                    $_data->variation[$index]->pricing[$priceIndex]             = new \stdClass();
                    $_data->variation[$index]->pricing[$priceIndex]->currency   = $price['currency'];
                    $_data->variation[$index]->pricing[$priceIndex]->price      = (float) $price['price'];
                    $_data->variation[$index]->pricing[$priceIndex]->sale_price = (float) $price['sale_price'];

                    if ($price['currency'] == SHOP_BASE_CURRENCY_CODE) {

                        $basePriceSet = true;
                    }

                    //  Convert the prices into the correct format for the database
                    $_data->variation[$index]->pricing[$priceIndex]->price = $this->shop_currency_model->floatToInt(
                        $_data->variation[$index]->pricing[$priceIndex]->price,
                        $_data->variation[$index]->pricing[$priceIndex]->currency
                    );
                    $_data->variation[$index]->pricing[$priceIndex]->sale_price = $this->shop_currency_model->floatToInt(
                        $_data->variation[$index]->pricing[$priceIndex]->sale_price,
                        $_data->variation[$index]->pricing[$priceIndex]->currency
                    );
                }

                if (!$basePriceSet) {

                    $this->_set_error('The ' . SHOP_BASE_CURRENCY_CODE . ' price must be set for all variants.');
                    return false;
                }
            }

            //  Gallery Associations
            //  --------------------
            $_data->variation[$index]->gallery = array();

            if (isset($v['gallery'])) {

                foreach ($v['gallery'] as $gallery_index => $image) {

                    if ($image) {

                        $_data->variation[$index]->gallery[] = $image;
                    }
                }
            }

            //  Shipping
            //  --------

            $_data->variation[$index]->shipping = new \stdClass();

            if ($productType->is_physical) {

                $_data->variation[$index]->shipping->collection_only = isset($v['shipping']['collection_only']) ? (bool) $v['shipping']['collection_only'] : false;
                $_data->variation[$index]->shipping->driver_data     = isset($v['shipping']['driver_data']) ? $v['shipping']['driver_data'] : null;

            } else {

                $_data->variation[$index]->shipping->collection_only = false;
                $_data->variation[$index]->shipping->driver_data     = null;
            }
        }

        //  Duplicate SKUs?
        $skuTracker = array_filter($skuTracker);
        $count      = array_count_values($skuTracker);

        if (count($count) != count($skuTracker)) {

            /**
             * If only one occurance of everything then the count on both should be
             * the same, if not then it'll vary.
             */

            $this->_set_error('All variations which have defined SKUs must be unique.');
            return false;
        }

        // --------------------------------------------------------------------------

        //  Gallery
        $_data->gallery = isset($data['gallery']) ? $data['gallery'] : array();

        // --------------------------------------------------------------------------

        //  Attributes
        $_data->attributes = isset($data['attributes']) ? $data['attributes'] : array();

        // --------------------------------------------------------------------------

        //  Ranges & Collections
        $_data->ranges      = isset($data['ranges']) ? $data['ranges'] : array();
        $_data->collections = isset($data['collections']) ? $data['collections'] : array();

        // --------------------------------------------------------------------------

        //  Related products
        $_data->related = isset($data['related']) ? explode(',', $data['related']) : array();
        $_data->related = array_filter($_data->related);
        $_data->related = array_unique($_data->related);
        $_data->related = array_map(function($val) {
            return (int) $val;
        }, $_data->related);

        // --------------------------------------------------------------------------

        //  SEO
        $_data->seo_title       = isset($data['seo_title']) ? $data['seo_title'] : null;
        $_data->seo_description = isset($data['seo_description']) ? $data['seo_description'] : null;
        $_data->seo_keywords    = isset($data['seo_keywords']) ? $data['seo_keywords'] : null;

        // --------------------------------------------------------------------------

        //  Published date
        $_data->published = isset($data['published']) ? ($data['published']) : null;

        // --------------------------------------------------------------------------

        return $_data;
    }

    // --------------------------------------------------------------------------

    /**
     * Actually executes the DB Call
     * @param  stdClass $data The object returned from createUpdatePrepData();
     * @return mixed          ID (int) on success, false on failure
     */
    protected function createUpdateExecute($data)
    {
        /**
         * Fetch the current state of the item if an ID is set
         * We'll use this later on in the shipping driver section to see what data we're updating
         */

        if (!empty($data->id)) {

            $current = $this->get_by_id($data->id);

        } else {

            $current = false;
        }

        // --------------------------------------------------------------------------

        //  Load dependant models
        $this->load->model('shop/shop_shipping_driver_model');

        // --------------------------------------------------------------------------

        //  Start the transaction, safety first!
        $this->db->trans_begin();
        $rollback = false;

        //  Add the product
        $this->db->set('slug', $data->slug);
        $this->db->set('type_id', $data->type_id);
        $this->db->set('label', $data->label);
        $this->db->set('description', $data->description);
        $this->db->set('seo_title', $data->seo_title);
        $this->db->set('seo_description', $data->seo_description);
        $this->db->set('seo_keywords', $data->seo_keywords);
        $this->db->set('tax_rate_id', $data->tax_rate_id);
        $this->db->set('is_active', $data->is_active);
        $this->db->set('is_deleted', $data->is_deleted);
        $this->db->set('published', $data->published);
        $this->db->set('google_category', $data->google_category);

        if (app_setting('enable_external_products', 'shop')) {

            $this->db->set('is_external', $data->is_external);
            $this->db->set('external_vendor_label', $data->external_vendor_label);
            $this->db->set('external_vendor_url', $data->external_vendor_url);
        }

        if (empty($data->id)) {

            $this->db->set('created', 'NOW()', false);

            if ($this->user_model->isLoggedIn()) {

                $this->db->set('created_by', activeUser('id'));
            }
        }

        $this->db->set('modified', 'NOW()', false);

        if ($this->user_model->isLoggedIn()) {

            $this->db->set('modified_by', activeUser('id'));
        }

        if (!empty($data->id)) {

            $this->db->where('id', $data->id);
            $result = $this->db->update($this->table);
            $action = 'update';

        } else {

            $result = $this->db->insert($this->table);
            $action = 'create';
            $data->id = $this->db->insert_id();
        }

        if ($result) {

            /**
             * The following items are all handled, and error, in [mostly]
             * the same way; loopy loop for clarity and consistency.
             */

            $types = array();

            //                 //Items to loop     //Field name     //Plural human      //Table name
            $types[]   = array($data->attributes,  'attribute_id',  'attributes',       $this->table_attribute);
            $types[]   = array($data->brands,      'brand_id',      'brands',           $this->table_brand);
            $types[]   = array($data->suppliers,   'supplier_id',   'suppliers',        $this->table_supplier);
            $types[]   = array($data->categories,  'category_id',   'categories',       $this->table_category);
            $types[]   = array($data->collections, 'collection_id', 'collections',      $this->table_collection);
            $types[]   = array($data->gallery,     'object_id',     'gallery items',    $this->table_gallery);
            $types[]   = array($data->ranges,      'range_id',      'ranges',           $this->table_range);
            $types[]   = array($data->tags,        'tag_id',        'tags',             $this->table_tag);
            $types[]   = array($data->related,     'related_id',    'related products', $this->table_related);

            foreach ($types as $type) {

                list($items, $field, $human, $table) = $type;

                //  Clear old items
                $this->db->where('product_id', $data->id);
                if (!$this->db->delete($table)) {

                    $this->_set_error('Failed to clear old product ' . $human . '.');
                    $rollback = true;
                    break;
                }

                $temp = array();
                switch ($field) {

                    case 'attribute_id':

                        foreach ($items as $item) {

                            $temp[] = array(
                                'product_id' => $data->id,
                                'attribute_id' => $item['attribute_id'],
                                'value' => $item['value']
                            );
                        }
                        break;

                    case 'object_id':

                        $counter = 0;
                        foreach ($items as $item_id) {

                            $temp[] = array(
                                'product_id' => $data->id,
                                $field => $item_id,
                                'order' => $counter
                            );
                            $counter++;
                        }
                        break;

                    default:

                        foreach ($items as $item_id) {

                            $temp[] = array(
                                'product_id' => $data->id,
                                $field => $item_id
                            );
                        }
                        break;

                }

                if ($temp) {

                    if (!$this->db->insert_batch($table, $temp)) {

                        $this->_set_error('Failed to add product ' . $human . '.');
                        $rollback = true;
                    }
                }
            }

            //  Product Variations
            //  ==================

            if (!$rollback) {

                $counter = 0;

                /**
                 * Keep a note of the variants we deal with, we'll want
                 * to mark any we don't deal with as deleted
                 */

                $variantIdTracker = array();

                foreach ($data->variation as $index => $v) {

                    //  Product Variation: Details
                    //  ==========================

                    $this->db->set('label', $v->label);
                    $this->db->set('sku', $v->sku);
                    $this->db->set('is_active', $v->is_active);
                    $this->db->set('order', $counter);


                    //  Product Variation: Stock Status
                    //  ===============================

                    $this->db->set('stock_status', $v->stock_status);
                    $this->db->set('quantity_available', $v->quantity_available);
                    $this->db->set('lead_time', $v->lead_time);

                    //  Product Variation: Out of Stock Behaviour
                    //  =========================================

                    $this->db->set('out_of_stock_behaviour', $v->out_of_stock_behaviour);
                    $this->db->set('out_of_stock_to_order_lead_time', $v->out_of_stock_to_order_lead_time);


                    //  Product Variation: Shipping
                    //  ===========================

                    $this->db->set('ship_collection_only', $v->shipping->collection_only);

                    if (!empty($v->id)) {

                        //  A variation ID exists, find it and update just the specific field.
                        foreach ($current->variations as $variation) {

                            if ($variation->id != $v->id) {

                                continue;

                            } else {

                                $currentDriverData = $variation->shipping->driver_data;
                                break;
                            }
                        }
                    }

                    $enabledDriver = $this->shop_shipping_driver_model->getEnabled();

                    if ($enabledDriver) {

                        if (!empty($currentDriverData)) {

                            //  Data exists, only update the specific bitty.
                            $currentDriverData[$enabledDriver->slug] = $v->shipping->driver_data[$enabledDriver->slug];
                            $this->db->set('ship_driver_data', serialize($currentDriverData));

                        } else {

                            //  Nothing exists, use whatever's been passed
                            $this->db->set('ship_driver_data', serialize($v->shipping->driver_data));
                        }
                    }

                    // --------------------------------------------------------------------------

                    if (!empty($v->id)) {

                        //  Existing variation, update what's there
                        $this->db->where('id', $v->id);
                        $result = $this->db->update($this->table_variation);
                        $action = 'update';

                        $variantIdTracker[] = $v->id;

                    } else {

                        //  New variation, add it.
                        $this->db->set('product_id', $data->id);
                        $result = $this->db->insert($this->table_variation);
                        $action = 'create';

                        $variantIdTracker[] = $this->db->insert_id();

                        $v->id = $this->db->insert_id();
                    }

                    if ($result) {

                        //  Product Variation: Gallery
                        //  ==========================

                        $this->db->where('variation_id', $v->id);
                        if (!$this->db->delete($this->table_variation_gallery)) {

                            $this->_set_error('Failed to clear gallery items for variant with label "' . $v->label . '"');
                            $rollback = true;
                        }

                        if  (!$rollback) {

                            $temp = array();
                            foreach ($v->gallery as $objectId) {

                                $temp[] = array(
                                    'variation_id' => $v->id,
                                    'object_id'    => $objectId
                                );
                            }

                            if ($temp) {

                                if (!$this->db->insert_batch($this->table_variation_gallery, $temp)) {

                                    $this->_set_error('Failed to update gallery items variant with label "' . $v->label . '"');
                                    $rollback = true;
                                }
                            }
                        }


                        //  Product Variation: Meta
                        //  =======================

                        if (!$rollback) {

                            foreach ($v->meta as &$meta) {

                                $meta['variation_id'] = $v->id;
                            }

                            $this->db->where('variation_id', $v->id);

                            if (!$this->db->delete($this->table_variation_product_type_meta)) {

                                $this->_set_error('Failed to clear meta data for variant with label "' . $v->label . '"');
                                $rollback = true;
                            }

                            if (!$rollback && !empty($v->meta)) {

                                if (!$this->db->insert_batch($this->table_variation_product_type_meta, $v->meta)) {

                                    $this->_set_error('Failed to update meta data for variant with label "' . $v->label . '"');
                                    $rollback = true;
                                }
                            }
                        }


                        //  Product Variation: Price
                        //  ========================

                        if (!$rollback) {

                            $this->db->where('variation_id', $v->id);
                            if (!$this->db->delete($this->table_variation_price)) {

                                $this->_set_error('Failed to clear price data for variant with label "' . $v->label . '"');
                                $rollback = true;
                            }

                            if (!$rollback) {

                                foreach ($v->pricing as &$price) {

                                    $price->variation_id = $v->id;
                                    $price->product_id   = $data->id;

                                    $price = (array) $price;
                                }

                                if ($v->pricing) {

                                    if (!$this->db->insert_batch($this->table_variation_price, $v->pricing)) {

                                        $this->_set_error('Failed to update price data for variant with label "' . $v->label . '"');
                                        $rollback = true;
                                    }
                                }
                            }
                        }

                    } else {

                        $this->_set_error('Unable to ' . $action . ' variation with label "' . $v->label . '".');
                        $rollback = true;
                        break;
                    }

                    $counter++;
                }

                //  Mark all untouched variants as deleted
                if (!$rollback) {

                    $this->db->set('is_deleted', true);
                    $this->db->where('product_id', $data->id);
                    $this->db->where_not_in('id', $variantIdTracker);

                    if (!$this->db->update($this->table_variation)) {

                        $this->_set_error('Unable to delete old variations.');
                        $rollback = true;
                    }
                }
            }

        } else {

            $this->_set_error('Failed to ' . $action . ' base product.');
            $rollback = true;
        }


        // --------------------------------------------------------------------------

        //  Wrap it all up
        if ($this->db->trans_status() === false || $rollback) {

            $this->db->trans_rollback();
            return false;

        } else {

            $this->db->trans_commit();

            // --------------------------------------------------------------------------

            //  Inform any persons who may have subscribed to a 'keep me informed' notification
            $variantsAvailable = array();

            $this->db->select('id');
            $this->db->where('product_id', $data->id);
            $this->db->where('is_deleted', false);
            $this->db->where('stock_status', 'IN_STOCK');
            $this->db->where('(quantity_available IS null OR quantity_available > 0)');
            $variantsAvailable_raw = $this->db->get($this->table_variation   )->result();
            $variantsAvailable = array();

            foreach ($variantsAvailable_raw as $v) {

                $variantsAvailable[] = $v->id;
            }

            if ($variantsAvailable) {

                if (!$this->load->isModelLoaded('shop_inform_product_available_model')) {

                    $this->load->model('shop/shop_inform_product_available_model');
                }

                $this->shop_inform_product_available_model->inform($data->id, $variantsAvailable);
            }

            // --------------------------------------------------------------------------

            return $data->id;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Marks a product as deleted
     * @param integer $id The ID of the object to delete
     * @return bool
     */
    public function delete($id)
    {
        return parent::update($id, array('is_deleted' => true));
    }

    // --------------------------------------------------------------------------

    /**
     * Restores a deleted object
     * @param  integer $id The ID of the object to delete
     * @return bool
     */
    public function restore($id)
    {
        return parent::update($id, array('is_deleted' => false));
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches all products
     * @param  integer $page           The page number of the results, if null then no pagination
     * @param  integer $perPage        How many items per page of paginated results
     * @param  array   $data           Any data to pass to _getcount_common()
     * @param  boolean $includeDeleted If non-destructive delete is enabled then this flag allows you to include deleted items
     * @param  string  $_caller        Internal flag to pass to _getcount_common(), contains the calling method
     * @return array
     */
    public function get_all($page = null, $perPage = null, $data = array(), $includeDeleted = false, $_caller = 'GET_ALL')
    {
        $this->load->model('shop/shop_category_model');

        $products = parent::get_all($page, $perPage, $data, $includeDeleted, $_caller);

        //  Handle requests for the raw query object
        if (!empty($data['RETURN_QUERY_OBJECT'])) {

            return $products;
        }

        // --------------------------------------------------------------------------

        /**
         * For consistency, we're going to create a 'base' price_raw object here,
         * where everything is 0. the code below can fill in the gaps where appropriate
         */

        $supportedCurrency     = $this->shop_currency_model->getAllSupported();
        $supportedCurrencyFlat = array();
        $basePriceRaw          = new \stdClass();

        foreach ($supportedCurrency as $currency) {

            $supportedCurrencyFlat[] = $currency->code;

            $basePriceRaw->{$currency->code}             = new \stdClass();
            $basePriceRaw->{$currency->code}->price      = 0;
            $basePriceRaw->{$currency->code}->sale_price = 0;
            $basePriceRaw->{$currency->code}->currency   = $currency;
        }

        // --------------------------------------------------------------------------

        foreach ($products as $product) {

            //  Format
            $this->formatProductObject($product);

            // --------------------------------------------------------------------------

            //  Fetch associated content

            //  Attributes
            //  ==========
            $this->db->select('pa.attribute_id id, a.label, pa.value');
            $this->db->where('pa.product_id', $product->id);
            $this->db->join(NAILS_DB_PREFIX . 'shop_attribute a', 'a.id = pa.attribute_id');
            $product->attributes = $this->db->get($this->table_attribute . ' pa')->result();

            //  Brands
            //  ======
            $this->db->select('b.id, b.slug, b.label, b.logo_id, b.is_active');
            $this->db->where('pb.product_id', $product->id);
            $this->db->join(NAILS_DB_PREFIX . 'shop_brand b', 'b.id = pb.brand_id');
            $product->brands = $this->db->get($this->table_brand . ' pb')->result();

            //  Suppliers
            //  =========
            $this->db->select('s.id, s.slug, s.label, s.is_active');
            $this->db->where('ps.product_id', $product->id);
            $this->db->join(NAILS_DB_PREFIX . 'shop_supplier s', 's.id = ps.supplier_id');
            $product->suppliers = $this->db->get($this->table_supplier . ' ps')->result();

            //  Categories
            //  ==========
            $this->db->select('c.id, c.slug, c.label, c.breadcrumbs');
            $this->db->where('pc.product_id', $product->id);
            $this->db->join(NAILS_DB_PREFIX . 'shop_category c', 'c.id = pc.category_id');
            $product->categories = $this->db->get($this->table_category . ' pc')->result();
            foreach ($product->categories as $category) {

                $category->id          = (int) $category->id;
                $category->breadcrumbs = json_decode($category->breadcrumbs);
                $category->url         = $this->shop_category_model->format_url($category->slug);
            }

            //  Collections
            //  ===========
            $this->db->select('c.id, c.slug, c.label');
            $this->db->where('pc.product_id', $product->id);
            $this->db->join(NAILS_DB_PREFIX . 'shop_collection c', 'c.id = pc.collection_id');
            $product->collections = $this->db->get($this->table_collection . ' pc')->result();

            //  Gallery
            //  =======
            $this->db->select('object_id');
            $this->db->where('product_id', $product->id);
            $this->db->order_by('order');
            $temp = $this->db->get($this->table_gallery)->result();

            $product->gallery = array();
            foreach ($temp as $image) {

                $product->gallery[] = (int) $image->object_id;
            }

            //  Featured image
            //  ==============
            if (!empty($product->gallery[0])) {

                $product->featured_img = $product->gallery[0];

            } else {

                $product->featured_img = null;
            }

            //  Range
            //  =====
            $this->db->select('r.id, r.slug, r.label');
            $this->db->where('pr.product_id', $product->id);
            $this->db->join(NAILS_DB_PREFIX . 'shop_range r', 'r.id = pr.range_id');
            $product->ranges = $this->db->get($this->table_range . ' pr')->result();

            //  Tags
            //  ====
            $this->db->select('t.id, t.slug, t.label');
            $this->db->where('pt.product_id', $product->id);
            $this->db->join(NAILS_DB_PREFIX . 'shop_tag t', 't.id = pt.tag_id');
            $product->tags = $this->db->get($this->table_tag . ' pt')->result();

            //  Variations
            //  ==========
            $this->db->select('pv.*');
            $this->db->where('pv.product_id', $product->id);
            if (empty($data['include_inactive_variants'])) {

                $this->db->where('pv.is_active', true);
            }
            if (empty($data['include_deleted_variants'])) {

                $this->db->where('pv.is_deleted', false);
            }
            $this->db->order_by('pv.order');
            $product->variations = $this->db->get($this->table_variation . ' pv')->result();

            foreach ($product->variations as &$v) {

                //  Meta
                //  ====

                $this->db->select('a.id,a.meta_field_id,b.label,a.value,b.allow_multiple');
                $this->db->join(NAILS_DB_PREFIX . 'shop_product_type_meta_field b', 'a.meta_field_id = b.id');
                $this->db->where('variation_id', $v->id);
                $metaRaw = $this->db->get($this->table_variation_product_type_meta . ' a')->result();

                //  Merge `allow_multiple` fields into one
                $v->meta = array();
                foreach ($metaRaw as $meta) {

                    if (!isset($v->meta[$meta->meta_field_id])) {

                        $v->meta[$meta->meta_field_id] = $meta;
                    }

                    if ($meta->allow_multiple) {

                        if (!is_array($v->meta[$meta->meta_field_id]->value)) {

                            //  Grab the current value and turn `value` into an array
                            $temp = $v->meta[$meta->meta_field_id]->value;
                            $v->meta[$meta->meta_field_id]->value   = array();
                            $v->meta[$meta->meta_field_id]->value[] = $temp;

                        } else {

                            $v->meta[$meta->meta_field_id]->value[] = $meta->value;
                        }

                    } else {

                        //  Overwrite previous entry
                        $v->meta[$meta->meta_field_id]->value = $meta->value;
                    }
                }

                //  Gallery
                //  =======

                $this->db->where('variation_id', $v->id);
                $temp = $this->db->get($this->table_variation_gallery)->result();
                $v->gallery = array();

                foreach ($temp as $image) {

                    $v->gallery[] = $image->object_id;
                }

                if (!empty($v->gallery[0])) {

                    $v->featured_img = $v->gallery[0];

                } else {

                    $v->featured_img = null;
                }

                //  Raw Price
                //  =========

                $this->db->select('pvp.price, pvp.sale_price, pvp.currency');
                $this->db->where('pvp.variation_id', $v->id);
                $this->db->where_in('pvp.currency', $supportedCurrencyFlat);
                $_price = $this->db->get($this->table_variation_price . ' pvp')->result();

                //  Dirty hack to "clone" the object, otherwise all variants will have the same price
                $v->price_raw = unserialize(serialize($basePriceRaw));
                $v->price     = new \stdClass();

                //  Set up a base object first, for consistency
                foreach ($_price as $price) {

                    $currencyCode = $price->currency;

                    //  Cast as integer
                    $v->price_raw->{$currencyCode}->price      = (int) $price->price;
                    $v->price_raw->{$currencyCode}->sale_price = (int) $price->sale_price;
                }

                $this->formatVariationObject($v);

                //  Calculated Price
                //  ================

                //  Fields
                $prototypeFields                = new \stdClass();
                $prototypeFields->value         = 0;
                $prototypeFields->value_inc_tax = 0;
                $prototypeFields->value_ex_tax  = 0;
                $prototypeFields->value_tax     = 0;

                //  Clone the fields for each price, we gotta use a deep copy 'hack' to avoid references.
                $v->price->price                 = new \stdClass();
                $v->price->price->base           = unserialize(serialize($prototypeFields));
                $v->price->price->base_formatted = unserialize(serialize($prototypeFields));
                $v->price->price->user           = unserialize(serialize($prototypeFields));
                $v->price->price->user_formatted = unserialize(serialize($prototypeFields));

                //  And an exact clone for the sale price
                $v->price->sale_price = unserialize(serialize($v->price->price));

                $basePrice = isset($v->price_raw->{SHOP_BASE_CURRENCY_CODE}) ? $v->price_raw->{SHOP_BASE_CURRENCY_CODE} : null;
                $userPrice = isset($v->price_raw->{SHOP_USER_CURRENCY_CODE}) ? $v->price_raw->{SHOP_USER_CURRENCY_CODE} : null;

                if (empty($basePrice)) {

                    /**
                     * Not having the base currency is an actual error. Fall over. Not having
                     * the user's currency is fine, we fall back to a conversion from the base
                     * currency.
                     */
                    $subject = 'Product missing price for base currency (' . SHOP_BASE_CURRENCY_CODE . ')';
                    $message = 'Product #' . $product->id . ' does not contain a price for the shop\'s base currency, ' . SHOP_BASE_CURRENCY_CODE . '.';
                    showFatalError($subject, $message);
                }

                //  Define the base prices first
                $v->price->price->base->value      = $basePrice->price;
                $v->price->sale_price->base->value = $basePrice->sale_price;

                // --------------------------------------------------------------------------

                /**
                 * If the user's currency preferences aren't the same as the
                 * base currency then we need to do some conversions
                 */

                if (SHOP_USER_CURRENCY_CODE != SHOP_BASE_CURRENCY_CODE) {

                    //  Price, first
                    if (empty($userPrice->price)) {

                        //  The user's price is empty() so we should automatically calculate it from the base price
                        $_price = $this->shop_currency_model->convertBaseToUser($basePrice->price);

                        if ($_price === false) {

                            showFatalError('Failed to convert currency', 'Could not convert from ' . SHOP_BASE_CURRENCY_CODE . ' to ' . SHOP_USER_CURRENCY_CODE . '. ' . $this->shop_currency_model->last_error());
                        }

                    } else {

                        //  A price has been explicitly set for this currency, so render it as is this
                        $_price = $userPrice->price;
                    }

                    //  Formatting not for visual purposes but to get value into the proper format
                    $v->price->price->user->value = number_format($_price, SHOP_USER_CURRENCY_PRECISION, '.', '');

                    // --------------------------------------------------------------------------

                    //  Sale price, second
                    if (empty($userPrice->sale_price)) {

                        //  The user's sale_price is empty() so we should automatically calculate it from the base price
                        $salePrice = $this->shop_currency_model->convertBaseToUser($basePrice->sale_price);

                        if ($_price === false) {

                            showFatalError('Failed to convert currency', 'Could not convert from ' . SHOP_BASE_CURRENCY_CODE . ' to ' . SHOP_USER_CURRENCY_CODE . '. ' . $this->shop_currency_model->last_error());
                        }

                    } else {

                        //  A sale_price has been explicitly set for this currency, so render it as is
                        $salePrice = $userPrice->sale_price;
                    }

                    //  Formatting not for visual purposes but to get value into the proper format
                    $v->price->sale_price->user->value = number_format($salePrice, SHOP_USER_CURRENCY_PRECISION, '.', '');

                } else {

                    //  Formatting not for visual purposes but to get value into the proper format
                    $v->price->price->user->value      = $v->price->price->base->value;
                    $v->price->sale_price->user->value = $v->price->sale_price->base->value;
                }

                // --------------------------------------------------------------------------

                //  Tax pricing
                if (app_setting('price_exclude_tax', 'shop')) {

                    //  Prices do not include any applicable taxes
                    $v->price->price->base->value_ex_tax = $v->price->price->base->value;
                    $v->price->price->user->value_ex_tax = $v->price->price->user->value;

                    //  Work out the ex-tax price by working out the tax and adding
                    if (!empty($product->tax_rate->rate)) {

                        $v->price->price->base->value_tax = $product->tax_rate->rate * $v->price->price->base->value_ex_tax;
                        $v->price->price->base->value_tax = round($v->price->price->base->value_tax, 0, PHP_ROUND_HALF_UP);
                        $v->price->price->user->value_tax = $product->tax_rate->rate * $v->price->price->user->value_ex_tax;
                        $v->price->price->user->value_tax = round($v->price->price->user->value_tax, 0, PHP_ROUND_HALF_UP);

                        $v->price->price->base->value_inc_tax = $v->price->price->base->value_ex_tax + $v->price->price->base->value_tax;
                        $v->price->price->user->value_inc_tax = $v->price->price->user->value_ex_tax + $v->price->price->user->value_tax;

                    } else {

                        $v->price->price->base->value_tax = 0;
                        $v->price->price->user->value_tax = 0;

                        $v->price->price->base->value_inc_tax = $v->price->price->base->value_ex_tax;
                        $v->price->price->user->value_inc_tax = $v->price->price->user->value_ex_tax;
                    }

                    // --------------------------------------------------------------------------

                    //  Sale price next...
                    $v->price->sale_price->base->value_ex_tax = $v->price->sale_price->base->value;
                    $v->price->sale_price->user->value_ex_tax = $v->price->sale_price->user->value;

                    //  Work out the ex-tax price by working out the tax and subtracting
                    if (!empty($product->tax_rate->rate)) {

                        $v->price->sale_price->base->value_tax = $product->tax_rate->rate * $v->price->sale_price->base->value_ex_tax;
                        $v->price->sale_price->base->value_tax = round($v->price->sale_price->base->value_tax, 0, PHP_ROUND_HALF_UP);
                        $v->price->sale_price->user->value_tax = $product->tax_rate->rate * $v->price->sale_price->user->value_ex_tax;
                        $v->price->sale_price->user->value_tax = round($v->price->sale_price->user->value_tax, 0, PHP_ROUND_HALF_UP);

                        $v->price->sale_price->base->value_inc_tax = $v->price->sale_price->base->value_ex_tax + $v->price->sale_price->base->value_tax;
                        $v->price->sale_price->user->value_inc_tax = $v->price->sale_price->user->value_ex_tax + $v->price->sale_price->user->value_tax;

                    } else {

                        $v->price->sale_price->base->value_tax = 0;
                        $v->price->sale_price->user->value_tax = 0;

                        $v->price->sale_price->base->value_inc_tax = $v->price->sale_price->base->value_ex_tax;
                        $v->price->sale_price->user->value_inc_tax = $v->price->sale_price->user->value_ex_tax;
                    }

                } else {

                    //  Prices are inclusive of any applicable taxes
                    $v->price->price->base->value_inc_tax = $v->price->price->base->value;
                    $v->price->price->user->value_inc_tax = $v->price->price->user->value;

                    //  Work out the ex-tax price by working out the tax and subtracting
                    if (!empty($product->tax_rate->rate)) {

                        $v->price->price->base->value_tax = ($product->tax_rate->rate * $v->price->price->base->value_inc_tax) / (1 + $product->tax_rate->rate);
                        $v->price->price->base->value_tax = round($v->price->price->base->value_tax, 0, PHP_ROUND_HALF_UP);
                        $v->price->price->user->value_tax = ($product->tax_rate->rate * $v->price->price->user->value_inc_tax) / (1 + $product->tax_rate->rate);
                        $v->price->price->user->value_tax = round($v->price->price->user->value_tax, 0, PHP_ROUND_HALF_UP);

                        $v->price->price->base->value_ex_tax = $v->price->price->base->value_inc_tax - $v->price->price->base->value_tax;
                        $v->price->price->user->value_ex_tax = $v->price->price->user->value_inc_tax - $v->price->price->user->value_tax;

                    } else {

                        $v->price->price->base->value_tax = 0;
                        $v->price->price->user->value_tax = 0;

                        $v->price->price->base->value_ex_tax = $v->price->price->base->value_inc_tax;
                        $v->price->price->user->value_ex_tax = $v->price->price->user->value_inc_tax;
                    }

                    // --------------------------------------------------------------------------

                    //  Sale price next...
                    $v->price->sale_price->base->value_inc_tax = $v->price->sale_price->base->value;
                    $v->price->sale_price->user->value_inc_tax = $v->price->sale_price->user->value;

                    //  Work out the ex-tax price by working out the tax and subtracting
                    if (!empty($product->tax_rate->rate)) {

                        $v->price->sale_price->base->value_tax = ($product->tax_rate->rate * $v->price->sale_price->base->value_inc_tax) / (1 + $product->tax_rate->rate);
                        $v->price->sale_price->base->value_tax = round($v->price->sale_price->base->value_tax, 0, PHP_ROUND_HALF_UP);
                        $v->price->sale_price->user->value_tax = ($product->tax_rate->rate * $v->price->sale_price->user->value_inc_tax) / (1 + $product->tax_rate->rate);
                        $v->price->sale_price->user->value_tax = round($v->price->sale_price->user->value_tax, 0, PHP_ROUND_HALF_UP);

                        $v->price->sale_price->base->value_ex_tax = $v->price->sale_price->base->value_inc_tax - $v->price->sale_price->base->value_tax;
                        $v->price->sale_price->user->value_ex_tax = $v->price->sale_price->user->value_inc_tax - $v->price->sale_price->user->value_tax;

                    } else {

                        $v->price->sale_price->base->value_tax = 0;
                        $v->price->sale_price->user->value_tax = 0;

                        $v->price->sale_price->base->value_ex_tax = $v->price->sale_price->base->value_inc_tax;
                        $v->price->sale_price->user->value_ex_tax = $v->price->sale_price->user->value_inc_tax;
                    }
                }

                // --------------------------------------------------------------------------

                //  Price Formatting and type casting
                $v->price->price->base->value         = (int) $v->price->price->base->value;
                $v->price->price->base->value_inc_tax = (int) $v->price->price->base->value_inc_tax;
                $v->price->price->base->value_ex_tax  = (int) $v->price->price->base->value_ex_tax;
                $v->price->price->base->value_tax     = (int) $v->price->price->base->value_tax;

                $v->price->price->base_formatted->value         = $this->shop_currency_model->formatBase($v->price->price->base->value);
                $v->price->price->base_formatted->value_inc_tax = $this->shop_currency_model->formatBase($v->price->price->base->value_inc_tax);
                $v->price->price->base_formatted->value_ex_tax  = $this->shop_currency_model->formatBase($v->price->price->base->value_ex_tax);
                $v->price->price->base_formatted->value_tax     = $this->shop_currency_model->formatBase($v->price->price->base->value_tax);

                $v->price->price->user->value         = (int) $v->price->price->user->value;
                $v->price->price->user->value_inc_tax = (int) $v->price->price->user->value_inc_tax;
                $v->price->price->user->value_ex_tax  = (int) $v->price->price->user->value_ex_tax;
                $v->price->price->user->value_tax     = (int) $v->price->price->user->value_tax;

                $v->price->price->user_formatted->value         = $this->shop_currency_model->formatUser($v->price->price->user->value);
                $v->price->price->user_formatted->value_inc_tax = $this->shop_currency_model->formatUser($v->price->price->user->value_inc_tax);
                $v->price->price->user_formatted->value_ex_tax  = $this->shop_currency_model->formatUser($v->price->price->user->value_ex_tax);
                $v->price->price->user_formatted->value_tax     = $this->shop_currency_model->formatUser($v->price->price->user->value_tax);


                $v->price->sale_price->base->value         = (int) $v->price->sale_price->base->value;
                $v->price->sale_price->base->value_inc_tax = (int) $v->price->sale_price->base->value_inc_tax;
                $v->price->sale_price->base->value_ex_tax  = (int) $v->price->sale_price->base->value_ex_tax;
                $v->price->sale_price->base->value_tax     = (int) $v->price->sale_price->base->value_tax;

                $v->price->sale_price->base_formatted->value         = $this->shop_currency_model->formatBase($v->price->sale_price->base->value);
                $v->price->sale_price->base_formatted->value_inc_tax = $this->shop_currency_model->formatBase($v->price->sale_price->base->value_inc_tax);
                $v->price->sale_price->base_formatted->value_ex_tax  = $this->shop_currency_model->formatBase($v->price->sale_price->base->value_ex_tax);
                $v->price->sale_price->base_formatted->value_tax     = $this->shop_currency_model->formatBase($v->price->sale_price->base->value_tax);

                $v->price->sale_price->user->value         = (int) $v->price->sale_price->user->value;
                $v->price->sale_price->user->value_inc_tax = (int) $v->price->sale_price->user->value_inc_tax;
                $v->price->sale_price->user->value_ex_tax  = (int) $v->price->sale_price->user->value_ex_tax;
                $v->price->sale_price->user->value_tax     = (int) $v->price->sale_price->user->value_tax;

                $v->price->sale_price->user_formatted->value         = $this->shop_currency_model->formatUser($v->price->sale_price->user->value);
                $v->price->sale_price->user_formatted->value_inc_tax = $this->shop_currency_model->formatUser($v->price->sale_price->user->value_inc_tax);
                $v->price->sale_price->user_formatted->value_ex_tax  = $this->shop_currency_model->formatUser($v->price->sale_price->user->value_ex_tax);
                $v->price->sale_price->user_formatted->value_tax     = $this->shop_currency_model->formatUser($v->price->sale_price->user->value_tax);

                // --------------------------------------------------------------------------

                //  Product User Price ranges
                if (empty($product->price)) {

                    $product->price = new \stdClass();
                }

                if (empty($product->price->user)) {

                    $product->price->user = new \stdClass();

                    $product->price->user->max_price         = null;
                    $product->price->user->max_price_inc_tax = null;
                    $product->price->user->max_price_ex_tax  = null;

                    $product->price->user->min_price         = null;
                    $product->price->user->min_price_inc_tax = null;
                    $product->price->user->min_price_ex_tax  = null;

                    $product->price->user->max_sale_price         = null;
                    $product->price->user->max_sale_price_inc_tax = null;
                    $product->price->user->max_sale_price_ex_tax  = null;

                    $product->price->user->min_sale_price         = null;
                    $product->price->user->min_sale_price_inc_tax = null;
                    $product->price->user->min_sale_price_ex_tax  = null;
                }

                if (empty($product->price->user_formatted)) {

                    $product->price->user_formatted = new \stdClass();

                    $product->price->user_formatted->max_price         = null;
                    $product->price->user_formatted->max_price_inc_tax = null;
                    $product->price->user_formatted->max_price_ex_tax  = null;

                    $product->price->user_formatted->min_price         = null;
                    $product->price->user_formatted->min_price_inc_tax = null;
                    $product->price->user_formatted->min_price_ex_tax  = null;

                    $product->price->user_formatted->max_sale_price         = null;
                    $product->price->user_formatted->max_sale_price_inc_tax = null;
                    $product->price->user_formatted->max_sale_price_ex_tax  = null;

                    $product->price->user_formatted->min_sale_price         = null;
                    $product->price->user_formatted->min_sale_price_inc_tax = null;
                    $product->price->user_formatted->min_sale_price_ex_tax  = null;
                }

                if (is_null($product->price->user->max_price) || $v->price->price->user->value > $product->price->user->max_price) {

                    $product->price->user->max_price         = $v->price->price->user->value;
                    $product->price->user->max_price_inc_tax = $v->price->price->user->value_inc_tax;
                    $product->price->user->max_price_ex_tax  = $v->price->price->user->value_ex_tax;

                    $product->price->user_formatted->max_price         = $v->price->price->user_formatted->value;
                    $product->price->user_formatted->max_price_inc_tax = $v->price->price->user_formatted->value_inc_tax;
                    $product->price->user_formatted->max_price_ex_tax  = $v->price->price->user_formatted->value_ex_tax;
                }

                if (is_null($product->price->user->min_price) || $v->price->price->user->value < $product->price->user->min_price) {

                    $product->price->user->min_price         = $v->price->price->user->value;
                    $product->price->user->min_price_inc_tax = $v->price->price->user->value_inc_tax;
                    $product->price->user->min_price_ex_tax  = $v->price->price->user->value_ex_tax;

                    $product->price->user_formatted->min_price         = $v->price->price->user_formatted->value;
                    $product->price->user_formatted->min_price_inc_tax = $v->price->price->user_formatted->value_inc_tax;
                    $product->price->user_formatted->min_price_ex_tax  = $v->price->price->user_formatted->value_ex_tax;
                }

                if (is_null($product->price->user->max_sale_price) || $v->price->sale_price->user->value > $product->price->user->max_sale_price) {

                    $product->price->user->max_sale_price         = $v->price->sale_price->user->value;
                    $product->price->user->max_sale_price_inc_tax = $v->price->sale_price->user->value_inc_tax;
                    $product->price->user->max_sale_price_ex_tax  = $v->price->sale_price->user->value_ex_tax;

                    $product->price->user_formatted->max_sale_price         = $v->price->sale_price->user_formatted->value;
                    $product->price->user_formatted->max_sale_price_inc_tax = $v->price->sale_price->user_formatted->value_inc_tax;
                    $product->price->user_formatted->max_sale_price_ex_tax  = $v->price->sale_price->user_formatted->value_ex_tax;
                }

                if (is_null($product->price->user->min_sale_price) || $v->price->sale_price->user->value < $product->price->user->min_sale_price) {

                    $product->price->user->min_sale_price         = $v->price->sale_price->user->value;
                    $product->price->user->min_sale_price_inc_tax = $v->price->sale_price->user->value_inc_tax;
                    $product->price->user->min_sale_price_ex_tax  = $v->price->sale_price->user->value_ex_tax;

                    $product->price->user_formatted->min_sale_price         = $v->price->sale_price->user_formatted->value;
                    $product->price->user_formatted->min_sale_price_inc_tax = $v->price->sale_price->user_formatted->value_inc_tax;
                    $product->price->user_formatted->min_sale_price_ex_tax  = $v->price->sale_price->user_formatted->value_ex_tax;
                }
            }

            //  Range strings
            if (!empty($product->price)) {

                if ($product->price->user->max_price == $product->price->user->min_price) {

                    $product->price->user_formatted->price_string = $product->price->user_formatted->min_price;

                } else {

                    $product->price->user_formatted->price_string = 'From ' . $product->price->user_formatted->min_price;
                }

                if ($product->price->user->max_sale_price == $product->price->user->min_sale_price) {

                    $product->price->user_formatted->sale_price_string = $product->price->user_formatted->min_sale_price;

                } else {

                    $product->price->user_formatted->sale_price_string = 'From ' . $product->price->user_formatted->min_sale_price;
                }
            }
        }

        // --------------------------------------------------------------------------

        return $products;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of all the products and their variations as a flat array
     * @return array
     */
    public function getAllProductVariationFlat()
    {
        $this->db->select('p.id p_id, v.id v_id, p.label p_label, v.label v_label, v.sku');
        $this->db->join($this->table . ' p', 'v.product_id = p.id');
        $this->db->order_by('p.label');
        $this->db->where('v.is_deleted', false);
        $this->db->where('p.is_deleted', false);
        $items = $this->db->get($this->table_variation . ' v')->result();

        $out = array();

        foreach ($items as $item) {

            $key = $item->p_id . ':' . $item->v_id;
            $label = $item->p_label == $item->v_label ? $item->p_label : $item->p_label . ' - ' . $item->v_label;
            $label .= $item->sku ? ' (SKU: ' . $item->sku . ')' : '';

            $out[$key] = $label;
        }

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches an item by it's ID; overriding to specify the `include_inactive` flag by default
     * @param  integer  $id   The ID of the product to fetch
     * @param  array $data An array of mutation options
     * @return mixed       false on failre, stdClass on success
     */
    public function get_by_id($id, $data = array())
    {
        if (!isset($data['include_inactive'])) {

            $data['include_inactive'] = true;
        }

        return parent::get_by_id($id, $data);
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches items by their IDs; overriding to specify the `include_inactive` flag by default
     * @param  array $ids  An array of product IDs to fetch
     * @param  array $data An array of mutation options
     * @return array
     */
    public function get_by_ids($ids, $data = array())
    {
        if (!isset($data['include_inactive'])) {

            $data['include_inactive'] = true;
        }

        return parent::get_by_ids($ids, $data);
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches an item by it's slug; overriding to specify the `include_inactive` flag by default
     * @param  string $slug The Slug of the product to fetch
     * @param  array  $data An array of mutation options
     * @return mixed        false on failre, stdClass on success
     */
    public function get_by_slug($slug, $data = array())
    {
        if (!isset($data['include_inactive'])) {

            $data['include_inactive'] = true;
        }

        return parent::get_by_slug($slug, $data);
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches items by their slugs; overriding to specify the `include_inactive` flag by default
     * @param  array $ids  An array of product Slugs to fetch
     * @param  array $data An array of mutation options
     * @return array
     */
    public function get_by_slugs($slugs, $data = array())
    {
        if (!isset($data['include_inactive'])) {

            $data['include_inactive'] = true;
        }

        return parent::get_by_slugs($slugs, $data);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns a product by one if it's variant's IDs
     * @param  integer $variantId The ID of the variant to look for
     * @return mixed               stdClass on success, false on failure
     */
    public function getByVariantId($variantId)
    {
        $this->db->select('product_id');
        $this->db->where('id', $variantId);
        $this->db->where('is_deleted', false);
        $variant = $this->db->get($this->table_variation)->row();

        if ($variant) {

            return $this->get_by_id($variant->product_id);

        } else {

            return false;
        }
    }

    // --------------------------------------------------------------------------

    public function getRelatedProducts($productId)
    {
        $this->db->select('related_id');
        $this->db->where('product_id', $productId);
        $result = $this->db->get($this->table_related)->result();

        if (empty($result)) {

            return array();

        } else {

            $ids = array();
            foreach ($result as $item) {
                $ids[] = $item->related_id;
            }

            return $this->get_by_ids($ids);
        }
    }

    // --------------------------------------------------------------------------

    /**
     * This method applies the conditionals which are common across the get_*()
     * methods and the count() method.
     * @param  string $data    Data passed from the calling method
     * @param  string $_caller The name of the calling method
     * @return void
     */
    protected function _getcount_common($data = array(), $_caller = null)
    {
        /**
         * If we're sorting on price or recently added then some magic needs to happen ahead
         * of calling _getcount_common();
         */

        $customSortStrings   = array();
        $customSortStrings[] = 'PRICE.ASC';
        $customSortStrings[] = 'PRICE.DESC';
        $customSortStrings[] = 'PUBLISHED.DESC';

        if (isset($data['sort']) && in_array($data['sort'], $customSortStrings)) {

            $customSort = explode('.', $data['sort']);
            unset($data['sort']);
        }

        // --------------------------------------------------------------------------

        parent::_getcount_common($data, $_caller);

        // --------------------------------------------------------------------------

        /**
         * Don't do anything if the caller is getAllProductVariationFlat(), it
         * will handle everything itself
         */

        if ($_caller == 'GET_ALL_PRODUCT_VARIATION_FLAT') {

            return;
        }

        // --------------------------------------------------------------------------

        //  Selects
        if (empty($data['_do_not_select'])) {

            $this->db->select($this->tablePrefix . '.*');
            $this->db->select('pt.label type_label, pt.max_per_order type_max_per_order, pt.is_physical type_is_physical');
            $this->db->select('tr.label tax_rate_label, tr.rate tax_rate_rate');
        }

        //  Joins
        $this->db->join($this->table_type . ' pt', 'p.type_id = pt.id');
        $this->db->join($this->table_tax_rate . ' tr', 'p.tax_rate_id = tr.id', 'LEFT');

        //  Default sort
        if (empty($customSort) && empty($data['sort'])) {

            $this->db->order_by($this->tablePrefix . '.label');

        } elseif (!empty($customSort) && $customSort[0] === 'PRICE') {

            $this->db->order_by('(SELECT MIN(`price`) FROM `' . $this->table_variation_price . '` vp WHERE vp.product_id = p.id)', $customSort[1]);

        } elseif (!empty($customSort) && $customSort[0] === 'PUBLISHED') {

            $this->db->order_by('p.published', 'DESC');
            $this->db->order_by('p.created', 'DESC');
        }

        //  Search
        if (!empty($data['keywords'])) {

            /**
             * Because of the sub query we need to manually create the where clause,
             * 'cause Active Record is a big pile of $%!@
             */

            \Nails\Factory::helper('string');
            $data['keywords'] = removeStopWords($data['keywords']);
            $search = $this->db->escape_like_str($data['keywords']);

            $where   = array();
            $where[] = $this->tablePrefix . '.id IN (SELECT product_id FROM ' . NAILS_DB_PREFIX . 'shop_product_variation WHERE label REGEXP \'([[:<:]]|^)' . $search . '([[:>:]]|$)\' OR sku LIKE \'%' . $search . '%\')' ;
            $where[] = $this->tablePrefix . '.id LIKE \'%' . $search  . '%\'';
            $where[] = $this->tablePrefix . '.label REGEXP \'([[:<:]]|^)' . $search . '([[:>:]]|$)\'';
            $where[] = $this->tablePrefix . '.description REGEXP \'([[:<:]]|^)' . $search . '([[:>:]]|$)\'';
            $where   = '(' . implode(' OR ', $where) . ')';

            $this->db->where($where);
        }

        // --------------------------------------------------------------------------

        //  Unless told otherwise, only return active items
        if (empty($data['include_inactive'])) {

            $this->db->where($this->tablePrefix . '.is_active', true);
        }

        // --------------------------------------------------------------------------

        //  Restricting to brand, supplier, category etc?

        //  Brands
        //  ======

        //  Oh hey there, if there's a brand_id filter set then that counts too.
        if (empty($data['_ignore_filters']) && !empty($data['filter']['brand_id'])) {

            if (!empty($data['brand_id'])) {

                //  Already being set, append the filter brand(s)
                if (!is_array($data['brand_id'])) {

                    $data['brand_id'] = array($data['brand_id']);
                }

            } else {

                $data['brand_id'] = $data['filter']['brand_id'];
            }

            $data['brand_id'] = array_merge($data['brand_id'], $data['filter']['brand_id']);
            $data['brand_id'] = array_unique($data['brand_id']);
            $data['brand_id'] = array_filter($data['brand_id']);
            $data['brand_id'] = array_map('intval', $data['brand_id']);
        }

        if (!empty($data['brand_id'])) {

            $where = $this->tablePrefix . '.id IN (SELECT product_id FROM ' . $this->table_brand . ' WHERE brand_id ';

            if (is_array($data['brand_id'])) {

                $brandIds = array_map(array($this->db, 'escape'), $data['brand_id']);
                $where .= 'IN (' . implode(',', $brandIds) . ')';

            } else {

                $where .= '= ' . $this->db->escape($data['brand_id']);
            }

            $where .= ')';

            $this->db->where($where);
        }

        //  Suppliers
        //  =========

        if (empty($data['_ignore_filters']) && !empty($data['filter']['supplier_id'])) {

            if (!empty($data['supplier_id'])) {

                //  Already being set, append the filter supplier(s)
                if (!is_array($data['supplier_id'])) {

                    $data['supplier_id'] = array($data['supplier_id']);
                }

            } else {

                $data['supplier_id'] = $data['filter']['supplier_id'];
            }

            $data['supplier_id'] = array_merge($data['supplier_id'], $data['filter']['supplier_id']);
            $data['supplier_id'] = array_unique($data['supplier_id']);
            $data['supplier_id'] = array_filter($data['supplier_id']);
            $data['supplier_id'] = array_map('intval', $data['supplier_id']);
        }

        if (!empty($data['supplier_id'])) {

            $where = $this->tablePrefix . '.id IN (SELECT product_id FROM ' . $this->table_supplier . ' WHERE supplier_id ';

            if (is_array($data['supplier_id'])) {

                $suplierIds = array_map(array($this->db, 'escape'), $data['supplier_id']);
                $where .= 'IN (' . implode(',', $suplierIds) . ')';

            } else {

                $where .= '= ' . $this->db->escape($data['supplier_id']);
            }

            $where .= ')';

            $this->db->where($where);
        }


        //  Categories
        //  ==========

        if (!empty($data['category_id'])) {

            $where = $this->tablePrefix . '.id IN (SELECT product_id FROM ' . $this->table_category . ' WHERE category_id ';

            if (is_array($data['category_id'])) {

                $categoryIds = array_map('intval', $data['category_id']);
                $categoryIds = array_map(array($this->db, 'escape'), $categoryIds);
                $where .= 'IN (' . implode(',', $categoryIds) . ')';

            } else {

                $where .= '= ' . $this->db->escape($data['category_id']);
            }

            $where .= ')';

            $this->db->where($where);
        }


        //  Collections
        //  ===========

        if (!empty($data['collection_id'])) {

            $where = $this->tablePrefix . '.id IN (SELECT product_id FROM ' . $this->table_collection . ' WHERE collection_id ';

            if (is_array($data['collection_id'])) {

                $collectionIds = array_map('intval', $data['collection_id']);
                $collectionIds = array_map(array($this->db, 'escape'), $collectionIds);
                $where .= 'IN (' . implode(',', $collectionIds) . ')';

            } else {

                $where .= '= ' . $this->db->escape($data['collection_id']);
            }

            $where .= ')';

            $this->db->where($where);
        }


        //  Ranges
        //  ======

        if (!empty($data['range_id'])) {

            $where = $this->tablePrefix . '.id IN (SELECT product_id FROM ' . $this->table_range . ' WHERE range_id ';

            if (is_array($data['range_id'])) {

                $rangeIds = array_map('intval', $data['range_id']);
                $rangeIds = array_map(array($this->db, 'escape'), $rangeIds);
                $where .= 'IN (' . implode(',', $rangeIds) . ')';

            } else {

                $where .= '= ' . $this->db->escape($data['range_id']);
            }

            $where .= ')';

            $this->db->where($where);
        }


        //  Sales
        //  =====

        if (!empty($data['sale_id'])) {

            $where = $this->tablePrefix . '.id IN (SELECT product_id FROM ' . $this->table_sale . ' WHERE sale_id ';

            if (is_array($data['sale_id'])) {

                $saleIds = array_map('intval', $data['sale_id']);
                $saleIds = array_map(array($this->db, 'escape'), $saleIds);
                $where .= 'IN (' . implode(',', $saleIds) . ')';

            } else {

                $where .= '= ' . $this->db->escape($data['sale_id']);
            }

            $where .= ')';

            $this->db->where($where);
        }


        //  Tags
        //  ====

        if (!empty($data['tag_id'])) {

            $where = $this->tablePrefix . '.id IN (SELECT product_id FROM ' . $this->table_tag . ' WHERE tag_id ';

            if (is_array($data['tag_id'])) {

                $tagIds = array_map('intval', $data['tag_id']);
                $tagIds = array_map(array($this->db, 'escape'), $tagIds);
                $where .= 'IN (' . implode(',', $tagIds) . ')';

            } else {

                $where .= '= ' . $this->db->escape($data['tag_id']);
            }

            $where .= ')';

            $this->db->where($where);
        }


        //  Stock Status
        //  ============

        if (!empty($data['stockStatus'])) {

            $where = 'SELECT count(*) FROM ' . $this->table_variation . ' WHERE product_id = p.id AND stock_status ';

            if (is_array($data['stockStatus'])) {

                $statuses = array_map(array($this->db, 'escape'), $data['stockStatus']);
                $where .= 'IN (' . implode(',', $statuses) . ')';

            } else {

                $where .= '= ' . $this->db->escape($data['stockStatus']);
            }

            $this->db->where('(' . $where . ') > 0');
        }

        // --------------------------------------------------------------------------

        /**
         * Filtering?
         * This is a beastly one, only do stuff if it's been requested
         */

        if (empty($data['_ignore_filters']) && !empty($data['filter'])) {

            //  Join the avriation table
            $this->db->join($this->table_variation . ' spv', $this->tablePrefix . '.id = spv.product_id');

            foreach ($data['filter'] as $meta_field_id => $values) {

                if (!is_numeric($meta_field_id)) {

                    continue;
                }

                $valuesClean = $values;
                $valuesClean = array_filter($valuesClean);
                $valuesClean = array_unique($valuesClean);
                $valuesClean = array_map('intval', $valuesClean);
                $valuesClean = array_map(array($this->db, 'escape'), $valuesClean);
                $valuesClean = implode(',', $valuesClean);

                $this->db->join($this->table_variation_product_type_meta . ' spvptm' . $meta_field_id , 'spvptm' . $meta_field_id . '.variation_id = spv.id AND spvptm' . $meta_field_id . '.meta_field_id = \'' . $meta_field_id . '\' AND spvptm' . $meta_field_id . '.value IN (' . $valuesClean . ')');
            }

            $this->db->group_by($this->tablePrefix . '.id');
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches all products which feature a particular brand
     * @param  integer $brandId        The ID of the brand
     * @param  integer $page           The page number of the results, if null then no pagination
     * @param  integer $perPage        How many items per page of paginated results
     * @param  array   $data           Any data to pass to _getcount_common()
     * @param  boolean $includeDeleted If non-destructive delete is enabled then this flag allows you to include deleted items
     * @return array
     */
    public function getForBrand($brandId, $page = null, $perPage = null, $data = array(), $includeDeleted = false)
    {
        $data['brand_id'] = $brandId;
        return $this->get_all($page, $perPage, $data, $includeDeleted, 'GET_FOR_BRAND');
    }

    // --------------------------------------------------------------------------

    /**
     * Counts all products which feature a particular brand
     * @param  integer $brandId        The ID of the brand
     * @param  array   $data           Any data to pass to _getcount_common()
     * @param  boolean $includeDeleted If non-destructive delete is enabled then this flag allows you to include deleted items
     * @return integer
     */
    public function countForBrand($brandId, $data = array(), $includeDeleted = false)
    {
        $data['brand_id'] = $brandId;
        return $this->count_all($data, $includeDeleted, 'COUNT_FOR_BRAND');
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches all products which feature a particular supplier
     * @param  integer $supplierId     The ID of the supplier
     * @param  integer $page           The page number of the results, if null then no pagination
     * @param  integer $perPage        How many items per page of paginated results
     * @param  array   $data           Any data to pass to _getcount_common()
     * @param  boolean $includeDeleted If non-destructive delete is enabled then this flag allows you to include deleted items
     * @return array
     */
    public function getForSupplier($supplierId, $page = null, $perPage = null, $data = array(), $includeDeleted = false)
    {
        $data['supplier_id'] = $supplierId;
        return $this->get_all($page, $perPage, $data, $includeDeleted, 'GET_FOR_SUPPLIER');
    }

    // --------------------------------------------------------------------------

    /**
     * Counts all products which feature a particular supplier
     * @param  integer $supplierId     The ID of the supplier
     * @param  array   $data           Any data to pass to _getcount_common()
     * @param  boolean $includeDeleted If non-destructive delete is enabled then this flag allows you to include deleted items
     * @return integer
     */
    public function countForSupplier($supplierId, $data = array(), $includeDeleted = false)
    {
        $data['supplier_id'] = $supplierId;
        return $this->count_all($data, $includeDeleted, 'COUNT_FOR_SUPPLIER');
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches all products which feature a particular category
     * @param  integer $categoryId     The ID of the category
     * @param  integer $page           The page number of the results, if null then no pagination
     * @param  integer $perPage        How many items per page of paginated results
     * @param  array   $data           Any data to pass to _getcount_common()
     * @param  boolean $includeDeleted If non-destructive delete is enabled then this flag allows you to include deleted items
     * @return array
     */
    public function getForCategory($categoryId, $page = null, $perPage = null, $data = array(), $includeDeleted = false)
    {
        //  Fetch this category's children also
        $this->load->model('shop/shop_category_model');
        $data['category_id'] = array_merge(array($categoryId), $this->shop_category_model->getIdsOfChildren($categoryId));
        return $this->get_all($page, $perPage, $data, $includeDeleted, 'GET_FOR_CATEGORY');
    }

    // --------------------------------------------------------------------------

    /**
     * Counts all products which feature a particular category
     * @param  integer $categoryId     The ID of the category
     * @param  array   $data           Any data to pass to _getcount_common()
     * @param  boolean $includeDeleted If non-destructive delete is enabled then this flag allows you to include deleted items
     * @return integer
     */
    public function countForCategory($categoryId, $data = array(), $includeDeleted = false)
    {
        //  Fetch this category's children also
        $this->load->model('shop/shop_category_model');
        $data['category_id'] = array_merge(array($categoryId), $this->shop_category_model->getIdsOfChildren($categoryId));
        return $this->count_all($data, $includeDeleted, 'COUNT_FOR_CATEGORY');
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches all products which feature a particular collection
     * @param  integer $collectionId   The ID of the collection
     * @param  integer $page           The page number of the results, if null then no pagination
     * @param  integer $perPage        How many items per page of paginated results
     * @param  array   $data           Any data to pass to _getcount_common()
     * @param  boolean $includeDeleted If non-destructive delete is enabled then this flag allows you to include deleted items
     * @return array
     */
    public function getForCollection($collectionId, $page = null, $perPage = null, $data = array(), $includeDeleted = false)
    {
        $data['collection_id'] = $collectionId;
        return $this->get_all($page, $perPage, $data, $includeDeleted, 'GET_FOR_COLLECTION');
    }

    // --------------------------------------------------------------------------

    /**
     * Counts all products which feature a particular collection
     * @param  integer  $collectionId   The ID of the collection
     * @param  array   $data           Any data to pass to _getcount_common()
     * @param  boolean $includeDeleted If non-destructive delete is enabled then this flag allows you to include deleted items
     * @return integer
     */
    public function countForCollection($collectionId, $data = array(), $includeDeleted = false)
    {
        $data['collection_id'] = $collectionId;
        return $this->count_all($data, $includeDeleted, 'COUNT_FOR_COLLECTION');
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches all products which feature a particular range
     * @param  integer $rangeId        The ID of the range
     * @param  integer $page           The page number of the results, if null then no pagination
     * @param  integer $perPage        How many items per page of paginated results
     * @param  array   $data           Any data to pass to _getcount_common()
     * @param  boolean $includeDeleted If non-destructive delete is enabled then this flag allows you to include deleted items
     * @return array
     */
    public function getForRange($rangeId, $page = null, $perPage = null, $data = array(), $includeDeleted = false)
    {
        $data['range_id'] = $rangeId;
        return $this->get_all($page, $perPage, $data, $includeDeleted, 'GET_FOR_RANGE');
    }

    // --------------------------------------------------------------------------

    /**
     * Counts all products which feature a particular range
     * @param  integer $rangeId        The ID of the range
     * @param  array   $data           Any data to pass to _getcount_common()
     * @param  boolean $includeDeleted If non-destructive delete is enabled then this flag allows you to include deleted items
     * @return integer
     */
    public function countForRange($rangeId, $data = array(), $includeDeleted = false)
    {
        $data['range_id'] = $rangeId;
        return $this->count_all($data, $includeDeleted, 'COUNT_FOR_RANGE');
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches all products which feature a particular sale
     * @param  integer $saleId         The ID of the sale
     * @param  integer $page           The page number of the results, if null then no pagination
     * @param  integer $perPage        How many items per page of paginated results
     * @param  array   $data           Any data to pass to _getcount_common()
     * @param  boolean $includeDeleted If non-destructive delete is enabled then this flag allows you to include deleted items
     * @return array
     */
    public function getForSale($saleId, $page = null, $perPage = null, $data = array(), $includeDeleted = false)
    {
        $data['sale_id'] = $saleId;
        return $this->get_all($page, $perPage, $data, $includeDeleted, 'GET_FOR_SALE');
    }

    // --------------------------------------------------------------------------

    /**
     * Counts all products which feature a particular sale
     * @param  integer $saleId         The ID of the sale
     * @param  array   $data           Any data to pass to _getcount_common()
     * @param  boolean $includeDeleted If non-destructive delete is enabled then this flag allows you to include deleted items
     * @return integer
     */
    public function countForSale($saleId, $data = array(), $includeDeleted = false)
    {
        $data['sale_id'] = $saleId;
        return $this->count_all($data, $includeDeleted, 'COUNT_FOR_SALE');
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches all products which feature a particular tag
     * @param  integer $tagId          The ID of the tag
     * @param  integer $page           The page number of the results, if null then no pagination
     * @param  integer $perPage        How many items per page of paginated results
     * @param  array   $data           Any data to pass to _getcount_common()
     * @param  boolean $includeDeleted If non-destructive delete is enabled then this flag allows you to include deleted items
     * @return array
     */
    public function getForTag($tagId, $page = null, $perPage = null, $data = array(), $includeDeleted = false)
    {
        $data['tag_id'] = $tagId;
        return $this->get_all($page, $perPage, $data, $includeDeleted, 'GET_FOR_TAG');
    }

    // --------------------------------------------------------------------------

    /**
     * Counts all products which feature a particular tag
     * @param  integer $tagId          The ID of the tag
     * @param  array   $data           Any data to pass to _getcount_common()
     * @param  boolean $includeDeleted If non-destructive delete is enabled then this flag allows you to include deleted items
     * @return integer
     */
    public function countForTag($tagId, $data = array(), $includeDeleted = false)
    {
        $data['tag_id'] = $tagId;
        return $this->count_all($data, $includeDeleted, 'COUNT_FOR_TAG');
    }

    // --------------------------------------------------------------------------

    /**
     * Formats a product's URL
     * @param  string $slug The product's slug
     * @return string       The product's URL
     */
    public function format_url($slug)
    {
        return site_url($this->shopUrl . 'product/' . $slug);
    }

    // --------------------------------------------------------------------------

    /**
     * Formats a product object
     * @param  stdClass $product The product object to format
     * @return void
     */
    protected function formatProductObject(&$product)
    {
        //  Type casting
        $product->id         = (int) $product->id;
        $product->is_active  = (bool) $product->is_active;
        $product->is_deleted = (bool) $product->is_deleted;

        //  Product type
        $product->type                = new \stdClass();
        $product->type->id            = (int) $product->type_id;
        $product->type->label         = $product->type_label;
        $product->type->max_per_order = (int) $product->type_max_per_order;
        $product->type->is_physical   = $product->type_is_physical;

        unset($product->type_id);
        unset($product->type_label);
        unset($product->type_max_per_order);
        unset($product->type_is_physical);

        //  Tax Rate
        $product->tax_rate        = new \stdClass();
        $product->tax_rate->id    = (int) $product->tax_rate_id;
        $product->tax_rate->label = $product->tax_rate_label;
        $product->tax_rate->rate  = $product->tax_rate_rate;

        unset($product->tax_rate_id);
        unset($product->tax_rate_label);
        unset($product->tax_rate_rate);

        //  URL
        $product->url = $this->format_url($product->slug);
    }

    // --------------------------------------------------------------------------

    /**
     * If the seo_description or seo_keywords fields are empty this method will
     * generate some content for them.
     * @param  object $product A product object
     * @return void
     */
    public function generateSeoContent(&$product)
    {
        /**
         * Autogenerate some SEO content if it's not been set
         * Buy {{PRODUCT}} at {{STORE}} ({{CATEGORIES}}) - {{DESCRIPTION,FIRST SENTENCE}}
         */

        if (empty($product->seo_description)) {

            //  Base string
            $product->seo_description = 'Buy ' . $product->label . ' at ' . APP_NAME;

            //  Add up to 3 categories
            if (!empty($product->categories)) {

                $categoriesArr = array();
                $counter       = 0;

                foreach ($product->categories as $category) {

                    $categoriesArr[] = $category->label;

                    $counter++;

                    if ($counter == 3) {

                        break;
                    }
                }

                $product->seo_description .= ' (' . implode(', ', $categoriesArr) . ')';
            }

            //  Add the first sentence of the description
            $description = strip_tags($product->description);
            $product->seo_description .= ' - ' . substr($description, 0, strpos($description, '.') + 1);

            //  Encode entities
            $product->seo_description = htmlentities(html_entity_decode($product->seo_description));
            $product->seo_description = str_replace('&amp;', 'and', $product->seo_description);
        }

        if (empty($product->seo_keywords)) {

            //  Sanitise the description
            $description = strip_tags($product->description);
            $description = html_entity_decode($description);

            //  Append the parent category/categories names onto the string
            foreach ($product->categories as $category) {
                foreach ($category->breadcrumbs as $crumb) {

                    $description .= strtolower($crumb->label);
                }
            }

            //  Trim and rmeove stop words
            $description = trim($description);
            $description = removeStopWords($description);

            //  Break it up and get the most frequently occurring words
            $description = strtolower($description);
            $description = str_replace("\n", ' ', strip_tags($description));
            $description = str_word_count($description, 1);
            $description = array_count_values($description);
            arsort($description);
            $description = array_keys($description);
            $description = array_slice($description, 0, 10);
            $product->seo_keywords = $description;

            //  Implode and encode entities
            $product->seo_keywords = array_unique($product->seo_keywords);
            $product->seo_keywords = implode(',', $product->seo_keywords);
            $product->seo_keywords = htmlentities($product->seo_keywords);
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Adds a product as a recently viewed item and saves it to the user's meta
     * data if they're logged in.
     * @param integer $productId The product's ID
     */
    public function addAsRecentlyViewed($productId)
    {
        //  Session
        $recentlyViewed = $this->session->userdata('shop_recently_viewed');

        if (empty($recentlyViewed)) {

            $recentlyViewed = array();
        }

        //  If this product is already there, remove it
        $search = array_search($productId, $recentlyViewed);
        if ($search !== false) {

            unset($recentlyViewed[$search]);
        }

        //  Pop it on the end
        $recentlyViewed[] = (int) $productId;

        //  Restrict to 6 most recently viewed items
        $recentlyViewed = array_slice($recentlyViewed, -6);

        $this->session->set_userdata('shop_recently_viewed', $recentlyViewed);

        // --------------------------------------------------------------------------

        //  Logged in?
        if ($this->user_model->isLoggedIn()) {

            $this->user_model->updateMeta(
                NAILS_DB_PREFIX . 'user_meta_shop',
                activeUser('id'),
                array(
                    'recently_viewed' => json_encode($recentlyViewed)
                )
            );
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of recently viewed products
     * @return array
     */
    public function getRecentlyViewed()
    {
        //  Session
        $recentlyViewed = $this->session->userdata('shop_recently_viewed');

        // --------------------------------------------------------------------------

        //  Logged in?
        if (empty($recentlyViewed) && $this->user_model->isLoggedIn()) {

            $oUserMeta = $this->user_model->getMeta(
                NAILS_DB_PREFIX . 'user_meta_shop',
                activeUser('id'),
                array(
                    'recently_viewed'
                )
            );
            $recentlyViewed = !empty($oUserMeta->recently_viewed) ? json_decode($oUserMeta->recently_viewed) : array();
        }

        // --------------------------------------------------------------------------

        return array_filter($recentlyViewed);
    }

    // --------------------------------------------------------------------------

    /**
     * Gets filters for products in a particular result set
     * @param  array $data A data array to pass to get_all
     * @return array
     */
    public function getFiltersForProducts($data)
    {
        if (!$this->table) {

            show_error(get_called_class() . '::count_all() Table variable not set');

        } else {

            $table  = $this->tablePrefix ? $this->table . ' ' . $this->tablePrefix : $this->table;
        }

        // --------------------------------------------------------------------------

        $filters = array();

        // --------------------------------------------------------------------------

        /**
         * Get all variations which appear within this result set; then determine which
         * product types these variations belong too. From that we can work out which
         * filters need fetched, their values and (maybe) the number of products each
         * filter value contains.
         */

        //  Fetch the products in the result set
        $data['_do_not_select']  = true;
        $data['_ignore_filters'] = true;
        $this->_getcount_common($data, 'GET_FILTERS_FOR_PRODUCTS');
        $this->db->select('p.id, p.type_id');
        $productIdsRaw  = $this->db->get($table)->result();
        $productIds     = array();
        $productTypeIds = array();

        foreach ($productIdsRaw as $pid) {

            $productIds[]     = $pid->id;
            $productTypeIds[] = $pid->type_id;
        }

        $productIds     = array_unique($productIds);
        $productIds     = array_filter($productIds);
        $productTypeIds = array_unique($productTypeIds);
        $productTypeIds = array_filter($productTypeIds);

        unset($productIdsRaw);

        if (!empty($productIds)) {

            /**
             * Brands apply to most products, include a brand filter if we're not looking
             * at a brand page
             */

            if (!isset($data['brand_id'])) {

                $this->db->select('sb.id value, sb.label, COUNT(spb.product_id) product_count');
                $this->db->join(NAILS_DB_PREFIX . 'shop_brand sb', 'sb.id = spb.brand_id');
                $this->db->where_in('spb.product_id', $productIds);
                $this->db->group_by('sb.id');
                $this->db->order_by('sb.label');
                $result = $this->db->get($this->table_brand . ' spb')->result();

                if ($result) {

                    $filters[0]         = new \stdClass();
                    $filters[0]->id     = 'brand_id';
                    $filters[0]->label  = 'Brands';
                    $filters[0]->values = $result;
                }
            }

            // --------------------------------------------------------------------------

            /**
             * Now fetch the variants in the result set, we'll use these
             * to restrict the values we show in the filters
             */

            $this->db->select('id');
            $this->db->where_in('product_id', $productIds);
            $variantIdsRaw = $this->db->get($this->table_variation)->result();
            $variantIds    = array();

            foreach ($variantIdsRaw as $vid) {

                $variantIds[] = $vid->id;
            }

            $variantIds = array_unique($variantIds);
            $variantIds = array_filter($variantIds);

            unset($variantIdsRaw);

            /**
             * For each product type, get it's associated meta content and then fetch
             * the distinct values from the values table
             */

            $this->load->model('shop/shop_product_type_meta_model');
            $metaFields = $this->shop_product_type_meta_model->getByProductTypeIds($productTypeIds);

            /**
             * Now start adding to the filters array; this is basically just the
             * field label & ID with all potential values of the result set.
             */

            foreach ($metaFields as $field) {

                //  Ignore ones which aren't set as filters
                if (empty($field->is_filter)) {

                    continue;
                }

                $temp        = new \stdClass();
                $temp->id    = $field->id;
                $temp->label = $field->label;

                $this->db->select('DISTINCT(`value`) `value`, COUNT(variation_id) product_count');
                $this->db->where('meta_field_id', $field->id);
                $this->db->where('value !=', '');
                $this->db->where_in('variation_id', $variantIds);
                $this->db->group_by('value');
                $temp->values = $this->db->get($this->table_variation_product_type_meta)->result();

                if (!empty($temp->values)) {

                    foreach ($temp->values as $v) {

                        $v->label = $v->value;
                    }

                    $filters[] = $temp;
                }

                unset($temp);
            }

            unset($metaFields);
        }

        // --------------------------------------------------------------------------

        return $filters;
    }

    // --------------------------------------------------------------------------

    /**
     * Shortcut to get filters for brands
     * @param  integer $brandId The ID of the brand
     * @param  array   $data    A data array to pass to get_all
     * @return array
     */
    public function getFiltersForProductsInBrand($brandId, $data = array())
    {
        $data['brand_id'] = $brandId;
        return $this->getFiltersForProducts($data);
    }

    // --------------------------------------------------------------------------

    /**
     * Shortcut to get filters for suppliers
     * @param  integer $supplierId The ID of the supplier
     * @param  array   $data       A data array to pass to get_all
     * @return array
     */
    public function getFiltersForProductsInSupplier($supplierId, $data = array())
    {
        $data['supplier_id'] = $supplierId;
        return $this->getFiltersForProducts($data);
    }

    // --------------------------------------------------------------------------

    /**
     * Shortcut to get filters for category
     * @param  integer $categoryId The ID of the category
     * @param  array   $data       A data array to pass to get_all
     * @return array
     */
    public function getFiltersForProductsInCategory($categoryId, $data = array())
    {
        //  Fetch this category's children also
        $this->load->model('shop/shop_category_model');
        $data['category_id'] = array_merge(array($categoryId), $this->shop_category_model->getIdsOfChildren($categoryId));
        return $this->getFiltersForProducts($data);
    }

    // --------------------------------------------------------------------------

    /**
     * Shortcut to get filters for collections
     * @param  integer $collectionId The ID of the collection
     * @param  array   $data         A data array to pass to get_all
     * @return array
     */
    public function getFiltersForProductsInCollection($collectionId, $data = array())
    {
        $data['collection_id'] = $collectionId;
        return $this->getFiltersForProducts($data);
    }

    // --------------------------------------------------------------------------

    /**
     * Shortcut to get filters for ranges
     * @param  integer $rangeId The ID of the range
     * @param  array   $data    A data array to pass to get_all
     * @return array
     */
    public function getFiltersForProductsInRange($rangeId, $data = array())
    {
        $data['range_id'] = $rangeId;
        return $this->getFiltersForProducts($data);
    }

    // --------------------------------------------------------------------------

    /**
     * Shortcut to get filters for sales
     * @param  integer sale_id The ID of the sale
     * @param  array   $data   A data array to pass to get_all
     * @return array
     */
    public function getFiltersForProductsInSale($saleId, $data = array())
    {
        $data['sale_id'] = $saleId;
        return $this->getFiltersForProducts($data);
    }

    // --------------------------------------------------------------------------

    /**
     * Shortcut to get filters for tags
     * @param  integer $tagId The ID of the tag
     * @param  array   $data  A data array to pass to get_all
     * @return array
     */
    public function getFiltersForProductsInTag($tagId, $data = array())
    {
        $data['tag_id'] = $tagId;
        return $this->getFiltersForProducts($data);
    }

    // --------------------------------------------------------------------------

    /**
     * Shortcut to get filters for a serch result
     * @param  string $keywords The keywords used in the search
     * @param  array  $data     An array of data to pass to getFiltersForProducts()
     * @return array
     */
    public function getFiltersForProductsInSearch($keywords, $data = array())
    {
        $data['keywords'] = $keywords;
        return $this->getFiltersForProducts($data);
    }

    // --------------------------------------------------------------------------

    /**
     * Formats a variation object
     * @param  stdClass $variation The variation object to format
     * @return void
     */
    protected function formatVariationObject(&$variation)
    {
        //  Type casting
        $variation->id         = (int) $variation->id;
        $variation->product_id = (int) $variation->product_id;
        $variation->order      = (int) $variation->order;
        $variation->is_deleted = (bool) $variation->is_deleted;

        if (!is_null($variation->quantity_available)) {

            $variation->quantity_available = (int) $variation->quantity_available;
        }

        //  Gallery
        if (!empty($variation->gallery) && is_array($variation->gallery)) {

            foreach ($variation->gallery as &$objectId) {

                $objectId = (int) $objectId;
            }
        }

        //  Price
        if (!empty($variation->price_raw) && is_array($variation->price_raw)) {

            foreach ($variation->price_raw as $price) {

                $price->price      = (int) $price->price;
                $price->sale_price = (int) $price->sale_price;
            }
        }

        //  Shipping data
        $variation->shipping                  = new \stdClass();
        $variation->shipping->collection_only = (bool) $variation->ship_collection_only;
        $variation->shipping->driver_data     = @unserialize($variation->ship_driver_data);

        //  Stock status
        $stockStatus = $variation->stock_status;
        $available   = $variation->quantity_available;

        if ($stockStatus == 'IN_STOCK' && !is_null($available) && $available == 0) {

            /**
             * Item is marked as IN_STOCK, but there's no stock to sell, set as out of
             * stock so the `out_of_stock_behaviour` kicks in.
             */

            $variation->stock_status = 'OUT_OF_STOCK';
        }

        if ($stockStatus == 'OUT_OF_STOCK') {

            switch ($variation->out_of_stock_behaviour) {

                case 'TO_ORDER':

                    //  Set the original values, in case they're needed
                    $variation->stock_status_original = $variation->stock_status;
                    $variation->lead_time_original    = $variation->lead_time;

                    //  And... override!
                    $variation->stock_status = 'TO_ORDER';

                    if ($variation->out_of_stock_to_order_lead_time) {

                        $variation->lead_time = $variation->out_of_stock_to_order_lead_time;

                    } else {

                        $variation->lead_time = $variation->lead_time;
                    }

                    break;

                case 'OUT_OF_STOCK':
                default:

                    //  Nothing to do.
                    break;
            }

            unset($variation->out_of_stock_behaviour);
            unset($variation->out_of_stock_to_order_lead_time);
        }
    }
}

// --------------------------------------------------------------------------

/**
 * OVERLOADING NAILS' MODELS
 *
 * The following block of code makes it simple to extend one of the core shop
 * models. Some might argue it's a little hacky but it's a simple 'fix'
 * which negates the need to massively extend the CodeIgniter Loader class
 * even further (in all honesty I just can't face understanding the whole
 * Loader class well enough to change it 'properly').
 *
 * Here's how it works:
 *
 * CodeIgniter instantiate a class with the same name as the file, therefore
 * when we try to extend the parent class we get 'cannot redeclare class X' errors
 * and if we call our overloading class something else it will never get instantiated.
 *
 * We solve this by prefixing the main class with NAILS_ and then conditionally
 * declaring this helper class below; the helper gets instantiated et voila.
 *
 * If/when we want to extend the main class we simply define NAILS_ALLOW_EXTENSION
 * before including this PHP file and extend as normal (i.e in the same way as below);
 * the helper won't be declared so we can declare our own one, app specific.
 *
 **/

if (!defined('NAILS_ALLOW_EXTENSION_SHOP_PRODUCT_MODEL')) {

    class Shop_product_model extends NAILS_Shop_product_model
    {
    }
}
