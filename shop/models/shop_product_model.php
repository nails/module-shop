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
    protected $_table;

    // --------------------------------------------------------------------------

    /**
     * Model constructor
     * @return void
     **/
    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        $this->_table                               = NAILS_DB_PREFIX . 'shop_product';
        $this->_table_prefix                        = 'p';
        $this->_table_attribute                     = NAILS_DB_PREFIX . 'shop_product_attribute';
        $this->_table_brand                         = NAILS_DB_PREFIX . 'shop_product_brand';
        $this->_table_category                      = NAILS_DB_PREFIX . 'shop_product_category';
        $this->_table_collection                    = NAILS_DB_PREFIX . 'shop_product_collection';
        $this->_table_gallery                       = NAILS_DB_PREFIX . 'shop_product_gallery';
        $this->_table_range                         = NAILS_DB_PREFIX . 'shop_product_range';
        $this->_table_sale                          = NAILS_DB_PREFIX . 'shop_sale_product';
        $this->_table_tag                           = NAILS_DB_PREFIX . 'shop_product_tag';
        $this->_table_variation                     = NAILS_DB_PREFIX . 'shop_product_variation';
        $this->_table_variation_gallery             = NAILS_DB_PREFIX . 'shop_product_variation_gallery';
        $this->_table_variation_product_type_meta   = NAILS_DB_PREFIX . 'shop_product_variation_product_type_meta';
        $this->_table_variation_price               = NAILS_DB_PREFIX . 'shop_product_variation_price';
        $this->_table_type                          = NAILS_DB_PREFIX . 'shop_product_type';
        $this->_table_tax_rate                      = NAILS_DB_PREFIX . 'shop_tax_rate';

        // --------------------------------------------------------------------------

        $this->_destructive_delete = false;

        // --------------------------------------------------------------------------

        //  Shop's base URL
        $this->shopUrl = $this->shop_model->getShopUrl();
    }

    // --------------------------------------------------------------------------

    /**
     * Creates a new object
     * @param array $data     The data to create the object with
     * @param bool  $returnObj Whether to return just the new ID or the full object
     * @return mixed
     **/
    public function create($data = array(), $returnObj = false)
    {
        //  Do all we need to do with the incoming data
        $data = $this->createUpdatePrepData($data);

        if (!$data) {

            return false;
        }

        // --------------------------------------------------------------------------

        //  Execute
        $id = $this->createUpdateExecute($data);

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
     * @param int $id The ID of the object to update
     * @param array $data The data to update the object with
     * @return bool
     **/
    public function update($id, $data = array())
    {
        $_current = $this->get_by_id($id);

        if (!$_current) {

            $this->_set_error('Invalid product ID');
            return false;

        }

        // --------------------------------------------------------------------------

        //  Do all we need to do with the incoming data
        $_data = $this->createUpdatePrepData($data, $id);

        if (!$_data) {

            return false;

        }

        $_data->id = $id;

        // --------------------------------------------------------------------------

        //  Execute
        $_id = $this->createUpdateExecute($_data);

        //  Wrap it all up
        if ($_id) {

            return true;

        } else {

            return false;

        }
    }

    // --------------------------------------------------------------------------

    /**
     * Prepares data, ready for the DB
     * @param  array $data Raw data to use for the update/create
     * @param  int   $id   If updating, the ID of the item being updated
     * @return mixed stdClass on success, false of failure
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

        $_data->slug = $this->_generate_slug($data['label'], '', '', $this->_table, null, $id);

        //  Product Info
        //  ============

        $_data->type_id = isset($data['type_id']) ? (int) $data['type_id']  : null;

        if (!$_data->type_id) {

            $this->_set_error('Product type must be defined.');
            return false;

        }

        $_data->label       = isset($data['label'])     ? trim($data['label'])      : null;
        $_data->is_active   = isset($data['is_active']) ? (bool) $data['is_active']     : false;
        $_data->is_deleted  = isset($data['is_deleted'])    ? (bool) $data['is_deleted']    : false;
        $_data->brands      = isset($data['brands'])        ? $data['brands']               : array();
        $_data->categories  = isset($data['categories'])    ? $data['categories']           : array();
        $_data->tags        = isset($data['tags'])      ? $data['tags']                 : array();

        if (app_setting('enable_external_products', 'shop')) {

            $_data->is_external             = isset($data['is_external'])               ? (bool) $data['is_external']       : false;
            $_data->external_vendor_label   = isset($data['external_vendor_label']) ? $data['external_vendor_label']    : '';
            $_data->external_vendor_url     = isset($data['external_vendor_url'])       ? $data['external_vendor_url']      : '';

        }

        $_data->tax_rate_id = isset($data['tax_rate_id']) &&    (int) $data['tax_rate_id']  ? (int) $data['tax_rate_id']    : null;

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

        $_data->variation   = array();
        $_product_type      = $this->shop_product_type_model->get_by_id($_data->type_id);

        if (!$_product_type) {

            $this->_set_error('Invalid Product Type');
            return false;

        } else {

            $_data->is_physical = $_product_type->is_physical;

        }

        $this->load->model('shop/shop_product_type_meta_model');
        $_product_type_meta = $this->shop_product_type_meta_model->get_by_product_type_id($_product_type->id);

        $_sku_tracker = array();

        foreach ($data['variation'] as $index => $v) {

            //  Details
            //  -------

            $_data->variation[$index] = new \stdClass();

            //  If there's an ID note it down, we'll be using it later as a switch between INSERT and UPDATE
            if (!empty($v['id'])) {

                $_data->variation[$index]->id = $v['id'];

            }

            $_data->variation[$index]->label    = isset($v['label'])    ? $v['label']   : null;
            $_data->variation[$index]->sku      = isset($v['sku'])  ? $v['sku']     : null;

            $_sku_tracker[] = $_data->variation[$index]->sku;

            //  Stock
            //  -----

            $_data->variation[$index]->stock_status = isset($v['stock_status']) ? $v['stock_status'] : 'OUT_OF_STOCK';

            switch ($_data->variation[$index]->stock_status) {

                case 'IN_STOCK' :

                    $_data->variation[$index]->quantity_available   = is_numeric($v['quantity_available']) ? (int) $v['quantity_available'] : null;
                    $_data->variation[$index]->lead_time            = null;

                break;

                case 'OUT_OF_STOCK' :

                    //  Shhh, be vewy qwiet, we're huntin' wabbits.
                    $_data->variation[$index]->quantity_available   = null;
                    $_data->variation[$index]->lead_time            = null;

                break;

            }

            /**
             * If the status is IN_STOCK but there is no stock, then we should forcibly set
             * as if OUT_OF_STOCK was set.
             */

            if ($_data->variation[$index]->stock_status == 'IN_STOCK' && !is_null($_data->variation[$index]->quantity_available) && $_data->variation[$index]->quantity_available <= 0) {

                $_data->variation[$index]->stock_status         = 'OUT_OF_STOCK';
                $_data->variation[$index]->quantity_available   = null;
                $_data->variation[$index]->lead_time            = null;

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

                        foreach ($_product_type_meta as $meta) {

                            if ($meta->id == $field_id) {

                                $_allow_multiple = true;
                                break;

                            }

                        }

                        if (empty($_allow_multiple)) {

                            $_temp                  = array();
                            $_temp['meta_field_id'] = $field_id;
                            $_temp['value']         = $value;
                            $_data->variation[$index]->meta[] = $_temp;

                        } else {

                            $_values = explode(',', $value);
                            foreach ($_values as $val) {

                                $_temp                  = array();
                                $_temp['meta_field_id'] = $field_id;
                                $_temp['value']         = $val;
                                $_data->variation[$index]->meta[] = $_temp;

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
                $_base_price_set = false;
                foreach ($v['pricing'] as $price_index => $price) {

                    if (empty($price['currency'])) {

                        $this->_set_error('"Currency" field is required for all variant prices.');
                        return false;

                    }

                    $_data->variation[$index]->pricing[$price_index]                = new \stdClass();
                    $_data->variation[$index]->pricing[$price_index]->currency      = $price['currency'];
                    $_data->variation[$index]->pricing[$price_index]->price         = !empty($price['price'])       ? (float) $price['price']       : null;
                    $_data->variation[$index]->pricing[$price_index]->sale_price    = !empty($price['sale_price'])  ? (float) $price['sale_price']  : null;

                    if ($price['currency'] == SHOP_BASE_CURRENCY_CODE) {

                        $_base_price_set = true;

                    }

                }

                if (!$_base_price_set) {

                    $this->_set_error('The ' . SHOP_BASE_CURRENCY_CODE . ' price must be set for all variants.');
                    return false;

                }

            }

            //  Gallery Associations
            //  --------------------
            $_data->variation[$index]->gallery = array();

            if (isset($v['gallery'])) {

                foreach ($v['gallery'] as $gallery_index => $image) {

                    $this->form_validation->set_rules('variation[' . $index . '][gallery][' . $gallery_index . ']', '', 'xss_clean');

                    if($image) {

                        $_data->variation[$index]->gallery[] = $image;

                    }

                }

            }

            //  Shipping
            //  --------

            $_data->variation[$index]->shipping = new \stdClass();

            if ($_product_type->is_physical) {

                $_data->variation[$index]->shipping->collection_only    = isset($v['shipping']['collection_only']) ? (bool) $v['shipping']['collection_only'] : false;
                $_data->variation[$index]->shipping->driver_data        = isset($v['shipping']['driver_data']) ? $v['shipping']['driver_data'] : null;

            } else {

                $_data->variation[$index]->shipping->collection_only    = false;
                $_data->variation[$index]->shipping->driver_data        = null;

            }

        }

        //  Duplicate SKUs?
        $_sku_tracker   = array_filter($_sku_tracker);
        $_count         = array_count_values($_sku_tracker);

        if (count($_count) != count($_sku_tracker)) {

            //  If only one occurance of everything then the count on both
            //  should be the same, if not then it'll vary.

            $this->_set_error('All variations which have defined SKUs must be unique.');
            return false;

        }

        // --------------------------------------------------------------------------

        //  Gallery
        $_data->gallery         = isset($data['gallery'])           ? $data['gallery']          : array();

        // --------------------------------------------------------------------------

        //  Attributes
        $_data->attributes      = isset($data['attributes'])        ? $data['attributes']       : array();

        // --------------------------------------------------------------------------

        //  Ranges & Collections
        $_data->ranges          = isset($data['ranges'])            ? $data['ranges']           : array();
        $_data->collections     = isset($data['collections'])       ? $data['collections']      : array();

        // --------------------------------------------------------------------------

        //  SEO
        $_data->seo_title       = isset($data['seo_title'])     ? $data['seo_title']        : null;
        $_data->seo_description = isset($data['seo_description'])   ? $data['seo_description']  : null;
        $_data->seo_keywords    = isset($data['seo_keywords'])  ? $data['seo_keywords']     : null;

        // --------------------------------------------------------------------------

        //  Published date
        $_data->published = isset($data['published']) ? userMysqlReverseDatetime($data['published']) : null;

        // --------------------------------------------------------------------------

        return $_data;
    }

    // --------------------------------------------------------------------------

    /**
     * Actually executes the DB Call
     * @param  stdClass $data The object returned from createUpdatePrepData();
     * @return mixed    ID (int) on success, false on failure
     */
    protected function createUpdateExecute($data)
    {
        /**
         * Fetch the current state of the item if an ID is set
         * We'll use this later on in the shipping driver section to see what data we're updating
         */

        if (!empty($data->id)) {

            $_current = $this->get_by_id($data->id);

        } else {

            $_current = false;

        }

        // --------------------------------------------------------------------------

        //  Load dependant models
        $this->load->model('shop/shop_shipping_driver_model');

        // --------------------------------------------------------------------------

        //  Start the transaction, safety first!
        $this->db->trans_begin();
        $_rollback = false;

        //  Add the product
        $this->db->set('slug',              $data->slug);
        $this->db->set('type_id',           $data->type_id);
        $this->db->set('label',         $data->label);
        $this->db->set('description',       $data->description);
        $this->db->set('seo_title',     $data->seo_title);
        $this->db->set('seo_description',   $data->seo_description);
        $this->db->set('seo_keywords',      $data->seo_keywords);
        $this->db->set('tax_rate_id',       $data->tax_rate_id);
        $this->db->set('is_active',     $data->is_active);
        $this->db->set('is_deleted',        $data->is_deleted);
        $this->db->set('published',     $data->published);

        if (app_setting('enable_external_products', 'shop')) {

            $this->db->set('is_external',               $data->is_external);
            $this->db->set('external_vendor_label', $data->external_vendor_label);
            $this->db->set('external_vendor_url',       $data->external_vendor_url);

        }

        if (empty($data->id)) {

            $this->db->set('created',           'NOW()', false);

            if ($this->user_model->is_logged_in()) {

                $this->db->set('created_by',    active_user('id'));

            }

        }

        $this->db->set('modified',          'NOW()', false);

        if ($this->user_model->is_logged_in()) {

            $this->db->set('modified_by',   active_user('id'));

        }

        if (!empty($data->id)) {

            $this->db->where('id', $data->id);
            $_result = $this->db->update($this->_table);
            $_action = 'update';

        } else {

            $_result = $this->db->insert($this->_table);
            $_action = 'create';
            $data->id = $this->db->insert_id();

        }

        if ($_result) {

            //  The following items are all handled, and error, in the [mostly] same way
            //  loopy loop for clarity and consistency.

            $_types = array();

            //                  //Items to loop         //Field name        //Plural human      //Table name
            $_types[]   = array($data->attributes,      'attribute_id',     'attributes',       $this->_table_attribute);
            $_types[]   = array($data->brands,          'brand_id',         'brands',           $this->_table_brand);
            $_types[]   = array($data->categories,      'category_id',      'categories',       $this->_table_category);
            $_types[]   = array($data->collections, 'collection_id',    'collections',      $this->_table_collection);
            $_types[]   = array($data->gallery,     'object_id',        'gallery items',    $this->_table_gallery);
            $_types[]   = array($data->ranges,          'range_id',         'ranges',           $this->_table_range);
            $_types[]   = array($data->tags,            'tag_id',           'tags',             $this->_table_tag);

            foreach ($_types as $type) {

                list($_items, $_field, $_type, $_table) = $type;

                //  Clear old items
                $this->db->where('product_id', $data->id);
                if (!$this->db->delete($_table)) {

                    $this->_set_error('Failed to clear old product ' . $_type . '.');
                    $_rollback = true;
                    break;

                }

                $_temp = array();
                switch ($_field) {

                    case 'attribute_id' :

                        foreach ($_items as $item) {

                            $_temp[] = array('product_id' => $data->id, 'attribute_id' => $item['attribute_id'], 'value' => $item['value']);

                        }

                    break;

                    case 'object_id' :

                        $_counter = 0;
                        foreach ($_items as $item_id) {

                            $_temp[] = array('product_id' => $data->id, $_field => $item_id, 'order' => $_counter);
                            $_counter++;

                        }

                    break;

                    default :

                        foreach ($_items as $item_id) {

                            $_temp[] = array('product_id' => $data->id, $_field => $item_id);

                        }

                    break;

                }

                if ($_temp) {

                    if (!$this->db->insert_batch($_table, $_temp)) {

                        $this->_set_error('Failed to add product ' . $_type . '.');
                        $_rollback = true;

                    }

                }

            }


            //  Product Variations
            //  ==================

            if (!$_rollback) {

                $_counter = 0;

                //  Keep a note of the variants we deal with, we'll
                //  want to mark any we don't deal with as deleted

                $_variant_id_tracker = array();

                foreach ($data->variation as $index => $v) {

                    //  Product Variation: Details
                    //  ==========================

                    $this->db->set('label', $v->label);
                    $this->db->set('sku',       $v->sku);
                    $this->db->set('order', $_counter);


                    //  Product Variation: Stock Status
                    //  ===============================

                    $this->db->set('stock_status',          $v->stock_status);
                    $this->db->set('quantity_available',    $v->quantity_available);
                    $this->db->set('lead_time',         $v->lead_time);

                    //  Product Variation: Out of Stock Behaviour
                    //  =========================================

                    $this->db->set('out_of_stock_behaviour',            $v->out_of_stock_behaviour);
                    $this->db->set('out_of_stock_to_order_lead_time',   $v->out_of_stock_to_order_lead_time);


                    //  Product Variation: Shipping
                    //  ===========================

                    $this->db->set('ship_collection_only',      $v->shipping->collection_only);

                    if (!empty($v->id)) {

                        //  A variation ID exists, find it and update just the specific field.
                        foreach ($_current->variations as $variation) {

                            if ($variation->id != $v->id) {

                                continue;

                            } else {

                                $_current_driver_data = $variation->shipping->driver_data;
                                break;

                            }

                        }

                    }

                    $_enabled_driver = $this->shop_shipping_driver_model->getEnabled();

                    if ($_enabled_driver) {

                        if (!empty($_current_driver_data)) {

                            //  Data exists, only update the specific bitty.
                            $_current_driver_data[$_enabled_driver->slug] = $v->shipping->driver_data[$_enabled_driver->slug];
                            $this->db->set('ship_driver_data', serialize($_current_driver_data));

                        } else {

                            //  Nothing exists, use whatever's been passed
                            $this->db->set('ship_driver_data', serialize($v->shipping->driver_data));

                        }

                    }

                    // --------------------------------------------------------------------------

                    if (!empty($v->id)) {

                        //  Existing variation, update what's there
                        $this->db->where('id', $v->id);
                        $_result = $this->db->update($this->_table_variation);
                        $_action = 'update';

                        $_variant_id_tracker[] = $v->id;

                    } else {

                        //  New variation, add it.
                        $this->db->set('product_id', $data->id);
                        $_result = $this->db->insert($this->_table_variation);
                        $_action = 'create';

                        $_variant_id_tracker[] = $this->db->insert_id();

                        $v->id = $this->db->insert_id();

                    }

                    if ($_result) {

                        //  Product Variation: Gallery
                        //  ==========================

                        $this->db->where('variation_id', $v->id);
                        if (!$this->db->delete($this->_table_variation_gallery)) {

                            $this->_set_error('Failed to clear gallery items for variant with label "' . $v->label . '"');
                            $_rollback = true;

                        }

                        if  (!$_rollback) {

                            $_temp = array();
                            foreach ($v->gallery as $object_id) {

                                $_temp[] = array(
                                    'variation_id'  => $v->id,
                                    'object_id'     => $object_id
                                );

                            }

                            if ($_temp) {

                                if (!$this->db->insert_batch($this->_table_variation_gallery, $_temp)) {

                                    $this->_set_error('Failed to update gallery items variant with label "' . $v->label . '"');
                                    $_rollback = true;

                                }

                            }

                        }


                        //  Product Variation: Meta
                        //  =======================

                        if (!$_rollback) {

                            foreach ($v->meta as &$meta) {

                                $meta['variation_id'] = $v->id;

                            }

                            $this->db->where('variation_id', $v->id);

                            if (!$this->db->delete($this->_table_variation_product_type_meta)) {

                                $this->_set_error('Failed to clear meta data for variant with label "' . $v->label . '"');
                                $_rollback = true;

                            }

                            if (!$_rollback && !empty($v->meta)) {

                                if (!$this->db->insert_batch($this->_table_variation_product_type_meta, $v->meta)) {

                                    $this->_set_error('Failed to update meta data for variant with label "' . $v->label . '"');
                                    $_rollback = true;

                                }

                            }

                        }


                        //  Product Variation: Price
                        //  ========================

                        if (!$_rollback) {

                            $this->db->where('variation_id', $v->id);
                            if (!$this->db->delete($this->_table_variation_price)) {

                                $this->_set_error('Failed to clear price data for variant with label "' . $v->label . '"');
                                $_rollback = true;

                            }

                            if (!$_rollback) {

                                foreach ($v->pricing as &$price) {

                                    $price->variation_id    = $v->id;
                                    $price->product_id      = $data->id;

                                    $price = (array) $price;

                                }

                                if ($v->pricing) {

                                    if (!$this->db->insert_batch($this->_table_variation_price, $v->pricing)) {

                                        $this->_set_error('Failed to update price data for variant with label "' . $v->label . '"');
                                        $_rollback = true;

                                    }

                                }

                            }

                        }

                    } else {

                        $this->_set_error('Unable to ' . $_action . ' variation with label "' . $v->label . '".');
                        $_rollback = true;
                        break;

                    }

                    $_counter++;

                }

                //  Mark all untouched variants as deleted
                if (!$_rollback) {

                    $this->db->set('is_deleted', true);
                    $this->db->where('product_id', $data->id);
                    $this->db->where_not_in('id', $_variant_id_tracker);

                    if (!$this->db->update($this->_table_variation)) {

                        $this->_set_error('Unable to delete old variations.');
                        $_rollback = true;

                    }

                }

            }

        } else {

            $this->_set_error('Failed to ' . $_action . ' base product.');
            $_rollback = true;

        }


        // --------------------------------------------------------------------------

        //  Wrap it all up
        if ($this->db->trans_status() === false || $_rollback) {

            $this->db->trans_rollback();
            return false;

        } else {

            $this->db->trans_commit();

            // --------------------------------------------------------------------------

            //  Inform any persons who may have subscribed to a 'keep me informed' notification
            $_variants_available = array();

            $this->db->select('id');
            $this->db->where('product_id', $data->id);
            $this->db->where('is_deleted', false);
            $this->db->where('stock_status', 'IN_STOCK');
            $this->db->where('(quantity_available IS null OR quantity_available > 0)');
            $_variants_available_raw = $this->db->get($this->_table_variation   )->result();
            $_variants_available = array();

            foreach ($_variants_available_raw as $v) {

                $_variants_available[] = $v->id;

            }

            if ($_variants_available) {

                if (!$this->load->isModelLoaded('shop_inform_product_available_model')) {

                    $this->load->model('shop/shop_inform_product_available_model');

                }

                $this->shop_inform_product_available_model->inform($data->id, $_variants_available);

            }

            // --------------------------------------------------------------------------

            return $data->id;

        }
    }

    // --------------------------------------------------------------------------

    /**
     * Marks a product as deleted
     * @param int $id The ID of the object to delete
     * @return bool
     **/
    public function delete($id)
    {
        return parent::update($id, array('is_deleted' => true));
    }

    // --------------------------------------------------------------------------

    /**
     * Restores a deleted object
     * @param int $id The ID of the object to delete
     * @return bool
     **/
    public function restore($id)
    {
        return parent::update($id, array('is_deleted' => false));
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches all products
     * @param  int   $page          The page number of the results, if null then no pagination
     * @param  int   $perPage        How many items per page of paginated results
     * @param  array   $data            Any data to pass to _getcount_common()
     * @param  boolean $includeDeleted  If non-destructive delete is enabled then this flag allows you to include deleted items
     * @param  string  $_caller      Internal flag to pass to _getcount_common(), contains the calling method
     * @return array
     */
    public function get_all($page = null, $perPage = null, $data = array(), $includeDeleted = false, $_caller = 'GET_ALL')
    {
        $this->load->model('shop/shop_category_model');

        $products = parent::get_all($page, $perPage, $data, $includeDeleted, $_caller);

        //  Handle requests for the raw query object
        if (!empty($data['RETURN_QUERY_OBJECT'])) {

            return $posts;
        }

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
            $product->attributes = $this->db->get($this->_table_attribute . ' pa')->result();

            //  Brands
            //  ======
            $this->db->select('b.id, b.slug, b.label, b.logo_id, b.is_active');
            $this->db->where('pb.product_id', $product->id);
            $this->db->join(NAILS_DB_PREFIX . 'shop_brand b', 'b.id = pb.brand_id');
            $product->brands = $this->db->get($this->_table_brand . ' pb')->result();

            //  Categories
            //  ==========
            $this->db->select('c.id, c.slug, c.label, c.breadcrumbs');
            $this->db->where('pc.product_id', $product->id);
            $this->db->join(NAILS_DB_PREFIX . 'shop_category c', 'c.id = pc.category_id');
            $product->categories = $this->db->get($this->_table_category . ' pc')->result();
            foreach ($product->categories as $category) {

                $category->url = $this->shop_category_model->format_url($category->slug);

            }

            //  Collections
            //  ===========
            $this->db->select('c.id, c.slug, c.label');
            $this->db->where('pc.product_id', $product->id);
            $this->db->join(NAILS_DB_PREFIX . 'shop_collection c', 'c.id = pc.collection_id');
            $product->collections = $this->db->get($this->_table_collection . ' pc')->result();

            //  Gallery
            //  =======
            $this->db->select('object_id');
            $this->db->where('product_id', $product->id);
            $this->db->order_by('order');
            $_temp = $this->db->get($this->_table_gallery)->result();

            $product->gallery = array();
            foreach ($_temp as $image) {

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
            $product->ranges = $this->db->get($this->_table_range . ' pr')->result();

            //  Tags
            //  ====
            $this->db->select('t.id, t.slug, t.label');
            $this->db->where('pt.product_id', $product->id);
            $this->db->join(NAILS_DB_PREFIX . 'shop_tag t', 't.id = pt.tag_id');
            $product->tags = $this->db->get($this->_table_tag . ' pt')->result();

            //  Variations
            //  ==========
            $this->db->select('pv.*');
            $this->db->where('pv.product_id', $product->id);
            if (empty($data['include_deleted_variants'])) {

                $this->db->where('pv.is_deleted', false);

            }
            $this->db->order_by('pv.order');
            $product->variations = $this->db->get($this->_table_variation . ' pv')->result();

            foreach ($product->variations as &$v) {

                //  Meta
                //  ====

                $this->db->select('a.id,a.meta_field_id,b.label,a.value,b.allow_multiple');
                $this->db->join(NAILS_DB_PREFIX . 'shop_product_type_meta_field b', 'a.meta_field_id = b.id');
                $this->db->where('variation_id', $v->id);
                $_meta_raw = $this->db->get($this->_table_variation_product_type_meta . ' a')->result();

                //  Merge `allow_multiple` fields into one
                $v->meta = array();
                foreach ($_meta_raw as $meta) {

                    if (!isset($v->meta[$meta->meta_field_id])) {

                        $v->meta[$meta->meta_field_id] = $meta;

                    }

                    if ($meta->allow_multiple) {

                        if (!is_array($v->meta[$meta->meta_field_id]->value)) {

                            //  Grab the current value and turn `value` into an array
                            $_temp = $v->meta[$meta->meta_field_id]->value;
                            $v->meta[$meta->meta_field_id]->value   = array();
                            $v->meta[$meta->meta_field_id]->value[] = $_temp;

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
                $_temp = $this->db->get($this->_table_variation_gallery)->result();
                $v->gallery = array();

                foreach ($_temp as $image) {

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
                $_price = $this->db->get($this->_table_variation_price . ' pvp')->result();

                $v->price_raw   = new \stdClass();
                $v->price       = new \stdClass();

                foreach ($_price as $price) {

                    $v->price_raw->{$price->currency} = $price;
                    $v->price_raw->{$price->currency}->currency_symbol = $this->shop_currency_model->get_by_code($price->currency)->symbol;

                }

                $this->formatVariationObject($v);

                //  Calculated Price
                //  ================

                //  Fields
                $_prototype_fields                  = new \stdClass();
                $_prototype_fields->value           = 0;
                $_prototype_fields->value_inc_tax   = 0;
                $_prototype_fields->value_ex_tax    = 0;
                $_prototype_fields->value_tax       = 0;

                //  Clone the fields for each price, we gotta use a deep copy 'hack' to avoid references.
                $v->price->price                    = new \stdClass();
                $v->price->price->base              = unserialize(serialize($_prototype_fields));
                $v->price->price->base_formatted    = unserialize(serialize($_prototype_fields));
                $v->price->price->user              = unserialize(serialize($_prototype_fields));
                $v->price->price->user_formatted    = unserialize(serialize($_prototype_fields));

                //  And an exact clone for the sale price
                $v->price->sale_price = unserialize(serialize($v->price->price));

                $_base_price = isset($v->price_raw->{SHOP_BASE_CURRENCY_CODE}) ? $v->price_raw->{SHOP_BASE_CURRENCY_CODE} : null;
                $_user_price = isset($v->price_raw->{SHOP_USER_CURRENCY_CODE}) ? $v->price_raw->{SHOP_USER_CURRENCY_CODE} : null;

                if (empty($_base_price)) {

                    $_subject = 'Product missing price for base currency (' . SHOP_BASE_CURRENCY_CODE . ')';
                    $_message = 'Product #' . $product->id . ' does not contain a price for the shop\'s base currency, ' . SHOP_BASE_CURRENCY_CODE . '.';
                    showFatalError($_subject, $_message);

                }

                if (empty($_user_price)) {

                    $_subject = 'Product missing price for currency (' . SHOP_USER_CURRENCY_CODE . ')';
                    $_message = 'Product #' . $product->id . ' does not contain a price for currency, ' . SHOP_USER_CURRENCY_CODE . '.';
                    showFatalError($_subject, $_message);

                }

                //  Define the base prices first
                $v->price->price->base->value       = $_base_price->price;
                $v->price->sale_price->base->value  = $_base_price->sale_price;

                $v->price->price->base_formatted->value         = $this->shop_currency_model->format_base($v->price->price->base->value);
                $v->price->sale_price->base_formatted->value    = $this->shop_currency_model->format_base($v->price->sale_price->base->value);

                // --------------------------------------------------------------------------

                /**
                 * If the user's currency preferences aren't the same as the
                 * base currency then we need to do some conversions
                 */

                if (SHOP_USER_CURRENCY_CODE != SHOP_BASE_CURRENCY_CODE) {

                    //  Price, first
                    if (empty($_user_price->price)) {

                        //  The user's price is empty() so we should automatically calculate it from the base price
                        $_price = $this->shop_currency_model->convert_base_to_user($_base_price->price);

                        if (!$_price) {

                            showFatalError('Failed to convert currency', 'Could not convert from ' . SHOP_BASE_CURRENCY_CODE . ' to ' . SHOP_USER_CURRENCY_CODE . '. ' . $this->shop_currency_model->last_error());

                        }

                    } else {

                        //  A price has been explicitly set for this currency, so render it as is this
                        $_price = $_user_price->price;

                    }

                    //  Formatting not for visual purposes but to get value into the proper format
                    $v->price->price->user->value = number_format($_price, SHOP_USER_CURRENCY_PRECISION, '.', '');

                    // --------------------------------------------------------------------------

                    //  Sale price, second
                    if (empty($_user_price->sale_price)) {

                        //  The user's sale_price is empty() so we should automatically calculate it from the base price
                        $_sale_price = $this->shop_currency_model->convert_base_to_user($_base_price->sale_price);

                        if (!$_price) {

                            showFatalError('Failed to convert currency', 'Could not convert from ' . SHOP_BASE_CURRENCY_CODE . ' to ' . SHOP_USER_CURRENCY_CODE . '. ' . $this->shop_currency_model->last_error());
                        }

                    } else {

                        //  A sale_price has been explicitly set for this currency, so render it as is
                        $_sale_price = $_user_price->sale_price;
                    }

                    //  Formatting not for visual purposes but to get value into the proper format
                    $v->price->sale_price->user->value = number_format($_sale_price, SHOP_USER_CURRENCY_PRECISION, '.', '');

                } else {

                    //  Formatting not for visual purposes but to get value into the proper format
                    $v->price->price->user->value       = number_format($v->price->price->base->value, SHOP_USER_CURRENCY_PRECISION, '.', '');
                    $v->price->sale_price->user->value  = number_format($v->price->sale_price->base->value, SHOP_USER_CURRENCY_PRECISION, '.', '');

                }

                // --------------------------------------------------------------------------

                //  Tax pricing
                if (app_setting('price_exclude_tax', 'shop')) {

                    //  Prices do not include any applicable taxes
                    $v->price->price->base->value_ex_tax = $v->price->price->base->value;
                    $v->price->price->user->value_ex_tax = $v->price->price->user->value;

                    //  Work out the ex-tax price by working out the tax and adding
                    if (!empty($product->tax_rate->rate)) {

                        $v->price->price->base->value_tax       = $product->tax_rate->rate * $v->price->price->base->value_ex_tax;
                        $v->price->price->base->value_tax       = round($v->price->price->base->value_tax, SHOP_BASE_CURRENCY_PRECISION);
                        $v->price->price->user->value_tax       = $product->tax_rate->rate * $v->price->price->user->value_ex_tax;
                        $v->price->price->user->value_tax       = round($v->price->price->user->value_tax, SHOP_USER_CURRENCY_PRECISION);

                        $v->price->price->base->value_inc_tax   = $v->price->price->base->value_ex_tax + $v->price->price->base->value_tax;
                        $v->price->price->user->value_inc_tax   = $v->price->price->user->value_ex_tax + $v->price->price->user->value_tax;

                    } else {

                        $v->price->price->base->value_tax       = 0;
                        $v->price->price->user->value_tax       = 0;

                        $v->price->price->base->value_inc_tax   = $v->price->price->base->value_ex_tax;
                        $v->price->price->user->value_inc_tax   = $v->price->price->user->value_ex_tax;

                    }

                    // --------------------------------------------------------------------------

                    //  Sale price next...
                    $v->price->sale_price->base->value_ex_tax = $v->price->sale_price->base->value;
                    $v->price->sale_price->user->value_ex_tax = $v->price->sale_price->user->value;

                    //  Work out the ex-tax price by working out the tax and subtracting
                    if (!empty($product->tax_rate->rate)) {

                        $v->price->sale_price->base->value_tax      = $product->tax_rate->rate * $v->price->sale_price->base->value_ex_tax;
                        $v->price->sale_price->base->value_tax      = round($v->price->sale_price->base->value_tax, SHOP_BASE_CURRENCY_PRECISION);
                        $v->price->sale_price->user->value_tax      = $product->tax_rate->rate * $v->price->sale_price->user->value_ex_tax;
                        $v->price->sale_price->user->value_tax      = round($v->price->sale_price->user->value_tax, SHOP_USER_CURRENCY_PRECISION);

                        $v->price->sale_price->base->value_inc_tax  = $v->price->sale_price->base->value_ex_tax + $v->price->sale_price->base->value_tax;
                        $v->price->sale_price->user->value_inc_tax  = $v->price->sale_price->user->value_ex_tax + $v->price->sale_price->user->value_tax;

                    } else {

                        $v->price->sale_price->base->value_tax      = 0;
                        $v->price->sale_price->user->value_tax      = 0;

                        $v->price->sale_price->base->value_inc_tax  = $v->price->sale_price->base->value_ex_tax;
                        $v->price->sale_price->user->value_inc_tax  = $v->price->sale_price->user->value_ex_tax;

                    }

                } else {

                    //  Prices are inclusive of any applicable taxes
                    $v->price->price->base->value_inc_tax = $v->price->price->base->value;
                    $v->price->price->user->value_inc_tax = $v->price->price->user->value;

                    //  Work out the ex-tax price by working out the tax and subtracting
                    if (!empty($product->tax_rate->rate)) {

                        $v->price->price->base->value_tax       = ($product->tax_rate->rate * $v->price->price->base->value_inc_tax) / (1 + $product->tax_rate->rate);
                        $v->price->price->base->value_tax       = round($v->price->price->base->value_tax, SHOP_BASE_CURRENCY_PRECISION);
                        $v->price->price->user->value_tax       = ($product->tax_rate->rate * $v->price->price->user->value_inc_tax) / (1 + $product->tax_rate->rate);
                        $v->price->price->user->value_tax       = round($v->price->price->user->value_tax, SHOP_USER_CURRENCY_PRECISION);

                        $v->price->price->base->value_ex_tax    = $v->price->price->base->value_inc_tax - $v->price->price->base->value_tax;
                        $v->price->price->user->value_ex_tax    = $v->price->price->user->value_inc_tax - $v->price->price->user->value_tax;

                    } else {

                        $v->price->price->base->value_tax       = 0;
                        $v->price->price->user->value_tax       = 0;

                        $v->price->price->base->value_ex_tax    = $v->price->price->base->value_inc_tax;
                        $v->price->price->user->value_ex_tax    = $v->price->price->user->value_inc_tax;

                    }

                    // --------------------------------------------------------------------------

                    //  Sale price next...
                    $v->price->sale_price->base->value_inc_tax = $v->price->sale_price->base->value;
                    $v->price->sale_price->user->value_inc_tax = $v->price->sale_price->user->value;

                    //  Work out the ex-tax price by working out the tax and subtracting
                    if (!empty($product->tax_rate->rate)) {

                        $v->price->sale_price->base->value_tax      = ($product->tax_rate->rate * $v->price->sale_price->base->value_inc_tax) / (1 + $product->tax_rate->rate);
                        $v->price->sale_price->base->value_tax      = round($v->price->sale_price->base->value_tax, SHOP_BASE_CURRENCY_PRECISION);
                        $v->price->sale_price->user->value_tax      = ($product->tax_rate->rate * $v->price->sale_price->user->value_inc_tax) / (1 + $product->tax_rate->rate);
                        $v->price->sale_price->user->value_tax      = round($v->price->sale_price->user->value_tax, SHOP_USER_CURRENCY_PRECISION);

                        $v->price->sale_price->base->value_ex_tax   = $v->price->sale_price->base->value_inc_tax - $v->price->sale_price->base->value_tax;
                        $v->price->sale_price->user->value_ex_tax   = $v->price->sale_price->user->value_inc_tax - $v->price->sale_price->user->value_tax;

                    } else {

                        $v->price->sale_price->base->value_tax      = 0;
                        $v->price->sale_price->user->value_tax      = 0;

                        $v->price->sale_price->base->value_ex_tax   = $v->price->sale_price->base->value_inc_tax;
                        $v->price->sale_price->user->value_ex_tax   = $v->price->sale_price->user->value_inc_tax;

                    }

                }

                // --------------------------------------------------------------------------

                //  Price Formatting
                $v->price->price->base_formatted->value         = $this->shop_currency_model->format_base($v->price->price->base->value);
                $v->price->price->base_formatted->value_inc_tax = $this->shop_currency_model->format_base($v->price->price->base->value_inc_tax);
                $v->price->price->base_formatted->value_ex_tax  = $this->shop_currency_model->format_base($v->price->price->base->value_ex_tax);
                $v->price->price->base_formatted->value_tax     = $this->shop_currency_model->format_base($v->price->price->base->value_tax);

                $v->price->price->user_formatted->value         = $this->shop_currency_model->format_user($v->price->price->user->value);
                $v->price->price->user_formatted->value_inc_tax = $this->shop_currency_model->format_user($v->price->price->user->value_inc_tax);
                $v->price->price->user_formatted->value_ex_tax  = $this->shop_currency_model->format_user($v->price->price->user->value_ex_tax);
                $v->price->price->user_formatted->value_tax     = $this->shop_currency_model->format_user($v->price->price->user->value_tax);

                $v->price->sale_price->base_formatted->value            = $this->shop_currency_model->format_base($v->price->sale_price->base->value);
                $v->price->sale_price->base_formatted->value_inc_tax    = $this->shop_currency_model->format_base($v->price->sale_price->base->value_inc_tax);
                $v->price->sale_price->base_formatted->value_ex_tax     = $this->shop_currency_model->format_base($v->price->sale_price->base->value_ex_tax);
                $v->price->sale_price->base_formatted->value_tax        = $this->shop_currency_model->format_base($v->price->sale_price->base->value_tax);

                $v->price->sale_price->user_formatted->value            = $this->shop_currency_model->format_user($v->price->sale_price->user->value);
                $v->price->sale_price->user_formatted->value_inc_tax    = $this->shop_currency_model->format_user($v->price->sale_price->user->value_inc_tax);
                $v->price->sale_price->user_formatted->value_ex_tax     = $this->shop_currency_model->format_user($v->price->sale_price->user->value_ex_tax);
                $v->price->sale_price->user_formatted->value_tax        = $this->shop_currency_model->format_user($v->price->sale_price->user->value_tax);

                // --------------------------------------------------------------------------

                //  Product User Price ranges
                if (empty($product->price)) {

                    $product->price = new \stdClass();

                }

                if (empty($product->price->user)) {

                    $product->price->user = new \stdClass();

                    $product->price->user->max_price            = null;
                    $product->price->user->max_price_inc_tax    = null;
                    $product->price->user->max_price_ex_tax     = null;

                    $product->price->user->min_price            = null;
                    $product->price->user->min_price_inc_tax    = null;
                    $product->price->user->min_price_ex_tax     = null;

                    $product->price->user->max_sale_price           = null;
                    $product->price->user->max_sale_price_inc_tax   = null;
                    $product->price->user->max_sale_price_ex_tax    = null;

                    $product->price->user->min_sale_price           = null;
                    $product->price->user->min_sale_price_inc_tax   = null;
                    $product->price->user->min_sale_price_ex_tax    = null;

                }

                if (empty($product->price->user_formatted)) {

                    $product->price->user_formatted = new \stdClass();

                    $product->price->user_formatted->max_price          = null;
                    $product->price->user_formatted->max_price_inc_tax  = null;
                    $product->price->user_formatted->max_price_ex_tax       = null;

                    $product->price->user_formatted->min_price          = null;
                    $product->price->user_formatted->min_price_inc_tax  = null;
                    $product->price->user_formatted->min_price_ex_tax   = null;

                    $product->price->user_formatted->max_sale_price         = null;
                    $product->price->user_formatted->max_sale_price_inc_tax = null;
                    $product->price->user_formatted->max_sale_price_ex_tax  = null;

                    $product->price->user_formatted->min_sale_price         = null;
                    $product->price->user_formatted->min_sale_price_inc_tax = null;
                    $product->price->user_formatted->min_sale_price_ex_tax  = null;

                }

                if (is_null($product->price->user->max_price) || $v->price->price->user->value > $product->price->user->max_price) {

                    $product->price->user->max_price            = $v->price->price->user->value;
                    $product->price->user->max_price_inc_tax    = $v->price->price->user->value_inc_tax;
                    $product->price->user->max_price_ex_tax     = $v->price->price->user->value_ex_tax;

                    $product->price->user_formatted->max_price          = $v->price->price->user_formatted->value;
                    $product->price->user_formatted->max_price_inc_tax  = $v->price->price->user_formatted->value_inc_tax;
                    $product->price->user_formatted->max_price_ex_tax   = $v->price->price->user_formatted->value_ex_tax;

                }

                if (is_null($product->price->user->min_price) || $v->price->price->user->value < $product->price->user->min_price) {

                    $product->price->user->min_price            = $v->price->price->user->value;
                    $product->price->user->min_price_inc_tax    = $v->price->price->user->value_inc_tax;
                    $product->price->user->min_price_ex_tax     = $v->price->price->user->value_ex_tax;

                    $product->price->user_formatted->min_price          = $v->price->price->user_formatted->value;
                    $product->price->user_formatted->min_price_inc_tax  = $v->price->price->user_formatted->value_inc_tax;
                    $product->price->user_formatted->min_price_ex_tax   = $v->price->price->user_formatted->value_ex_tax;

                }

                if (is_null($product->price->user->max_sale_price) || $v->price->sale_price->user->value > $product->price->user->max_sale_price) {

                    $product->price->user->max_sale_price           = $v->price->sale_price->user->value;
                    $product->price->user->max_sale_price_inc_tax   = $v->price->sale_price->user->value_inc_tax;
                    $product->price->user->max_sale_price_ex_tax    = $v->price->sale_price->user->value_ex_tax;

                    $product->price->user_formatted->max_sale_price         = $v->price->sale_price->user_formatted->value;
                    $product->price->user_formatted->max_sale_price_inc_tax = $v->price->sale_price->user_formatted->value_inc_tax;
                    $product->price->user_formatted->max_sale_price_ex_tax  = $v->price->sale_price->user_formatted->value_ex_tax;

                }

                if (is_null($product->price->user->min_sale_price) || $v->price->sale_price->user->value < $product->price->user->min_sale_price) {

                    $product->price->user->min_sale_price           = $v->price->sale_price->user->value;
                    $product->price->user->min_sale_price_inc_tax   = $v->price->sale_price->user->value_inc_tax;
                    $product->price->user->min_sale_price_ex_tax    = $v->price->sale_price->user->value_ex_tax;

                    $product->price->user_formatted->min_sale_price         = $v->price->sale_price->user_formatted->value;
                    $product->price->user_formatted->min_sale_price_inc_tax = $v->price->sale_price->user_formatted->value_inc_tax;
                    $product->price->user_formatted->min_sale_price_ex_tax  = $v->price->sale_price->user_formatted->value_ex_tax;

                }

            }

            //  Range strings
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
        $this->db->join($this->_table . ' p', 'v.product_id = p.id');
        $this->db->order_by('p.label');
        $this->db->where('v.is_deleted', false);
        $this->db->where('p.is_deleted', false);
        $_items = $this->db->get($this->_table_variation . ' v')->result();

        $_out = array();

        foreach ($_items as $item) {

            $_key = $item->p_id . ':' . $item->v_id;
            $_label = $item->p_label == $item->v_label ? $item->p_label : $item->p_label . ' - ' . $item->v_label;
            $_label .= $item->sku ? ' (SKU: ' . $item->sku . ')' : '';

            $_out[$_key] = $_label;

        }

        return $_out;
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches an item by it's ID; overriding to specify the `include_inactive` flag by default
     * @param  int   $id   The ID of the product to fetch
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

    public function getByVariantId($variant_id)
    {
        $this->db->select('product_id');
        $this->db->where('id', $variant_id);
        $this->db->where('is_deleted', false);
        $_variant = $this->db->get($this->_table_variation)->row();

        if ($_variant) {

            return $this->get_by_id($_variant->product_id);

        } else {

            return false;

        }
    }

    // --------------------------------------------------------------------------

    /**
     * This method applies the conditionals which are common across the get_*()
     * methods and the count() method.
     * @param string $data  Data passed from the calling method
     * @param string $_caller The name of the calling method
     * @return void
     **/
    protected function _getcount_common($data = array(), $_caller = null)
    {
        /**
         * If we're sorting on price or recently added then some magic needs to happen ahead
         * of calling _getcount_common();
         */

        $customSortStrings   = array();
        $customSortStrings[] = 'PRICE.ASC';
        $customSortStrings[] = 'PRICE.DESC';
        $customSortStrings[] = 'CREATED.DESC';

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

            $this->db->select($this->_table_prefix . '.*');
            $this->db->select('pt.label type_label, pt.max_per_order type_max_per_order, pt.is_physical type_is_physical');
            $this->db->select('tr.label tax_rate_label, tr.rate tax_rate_rate');

        }

        //  Joins
        $this->db->join($this->_table_type . ' pt', 'p.type_id = pt.id');
        $this->db->join($this->_table_tax_rate . ' tr', 'p.tax_rate_id = tr.id', 'LEFT');

        //  Default sort
        if (empty($customSort) && empty($data['sort'])) {

            $this->db->order_by($this->_table_prefix . '.label');

        } elseif (!empty($customSort) && $customSort[0] === 'PRICE') {

            $this->db->order_by('(SELECT MIN(`price`) FROM `' . $this->_table_variation_price . '` vp WHERE vp.product_id = p.id)', $customSort[1]);

        } elseif (!empty($customSort) && $customSort[0] === 'CREATED') {

            $this->db->order_by('p.created', 'DESC');
        }

        //  Search
        if (!empty($data['search'])) {

            //  Because fo the sub query we need to manually create the where clause,
            //  'cause Active Record is a big pile of $%!@

            $_search    = $this->db->escape_like_str($data['search']);

            $_where     = array();
            $_where[]   = $this->_table_prefix . '.id IN (SELECT product_id FROM ' . NAILS_DB_PREFIX . 'shop_product_variation WHERE label LIKE \'%' . $_search . '%\' OR sku LIKE \'%' . $_search . '%\')' ;
            $_where[]   = $this->_table_prefix . '.id LIKE \'%' . $_search  . '%\'';
            $_where[]   = $this->_table_prefix . '.label LIKE \'%' . $_search  . '%\'';
            $_where[]   = $this->_table_prefix . '.description LIKE \'%' . $_search  . '%\'';
            $_where[]   = $this->_table_prefix . '.seo_description LIKE \'%' . $_search  . '%\'';
            $_where[]   = $this->_table_prefix . '.seo_keywords LIKE \'%' . $_search  . '%\'';
            $_where     = '(' . implode(' OR ', $_where) . ')';

            $this->db->where($_where);

        }

        // --------------------------------------------------------------------------

        //  Unless told otherwise, only return active items
        if (empty($data['include_inactive'])) {

            $this->db->where($this->_table_prefix . '.is_active', true);

        }

        // --------------------------------------------------------------------------

        //  Restricting to brand, category etc?

        //  Brands
        //  ======

        //  Oh hey there, if there's a brand_id filter set then that counts too.
        if (empty($data['_ignore_filters']) && !empty($data['filter']['brand_id'])) {

            if (!empty($data['brand_id'])) {

                //  Already being set, apend the filter brand(s)
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

            $_where = $this->_table_prefix . '.id IN (SELECT product_id FROM ' . $this->_table_brand . ' WHERE brand_id ';

            if (is_array($data['brand_id'])) {

                $_brand_ids = array_map(array($this->db, 'escape'), $data['brand_id']);
                $_where .= 'IN (' . implode(',', $_brand_ids) . ')';

            } else {

                $_where .= '= ' . $this->db->escape($data['brand_id']);

            }

            $_where .= ')';

            $this->db->where($_where);

        }


        //  Categories
        //  ==========

        if (!empty($data['category_id'])) {

            $_where = $this->_table_prefix . '.id IN (SELECT product_id FROM ' . $this->_table_category . ' WHERE category_id ';

            if (is_array($data['category_id'])) {

                $categoryIds = array_map('intval', $data['category_id']);
                $categoryIds = array_map(array($this->db, 'escape'), $categoryIds);
                $_where .= 'IN (' . implode(',', $categoryIds) . ')';

            } else {

                $_where .= '= ' . $this->db->escape($data['category_id']);

            }

            $_where .= ')';

            $this->db->where($_where);

        }


        //  Collections
        //  ===========

        if (!empty($data['collection_id'])) {

            $_where = $this->_table_prefix . '.id IN (SELECT product_id FROM ' . $this->_table_collection . ' WHERE collection_id ';

            if (is_array($data['collection_id'])) {

                $collectionIds = array_map('intval', $data['collection_id']);
                $collectionIds = array_map(array($this->db, 'escape'), $collectionIds);
                $_where .= 'IN (' . implode(',', $collectionIds) . ')';

            } else {

                $_where .= '= ' . $this->db->escape($data['collection_id']);

            }

            $_where .= ')';

            $this->db->where($_where);

        }


        //  Ranges
        //  ======

        if (!empty($data['range_id'])) {

            $_where = $this->_table_prefix . '.id IN (SELECT product_id FROM ' . $this->_table_range . ' WHERE range_id ';

            if (is_array($data['range_id'])) {

                $rangeIds = array_map('intval', $data['range_id']);
                $rangeIds = array_map(array($this->db, 'escape'), $rangeIds);
                $_where .= 'IN (' . implode(',', $rangeIds) . ')';

            } else {

                $_where .= '= ' . $this->db->escape($data['range_id']);

            }

            $_where .= ')';

            $this->db->where($_where);

        }


        //  Sales
        //  =====

        if (!empty($data['sale_id'])) {

            $_where = $this->_table_prefix . '.id IN (SELECT product_id FROM ' . $this->_table_sale . ' WHERE sale_id ';

            if (is_array($data['sale_id'])) {

                $saleIds = array_map('intval', $data['sale_id']);
                $saleIds = array_map(array($this->db, 'escape'), $saleIds);
                $_where .= 'IN (' . implode(',', $saleIds) . ')';

            } else {

                $_where .= '= ' . $this->db->escape($data['sale_id']);

            }

            $_where .= ')';

            $this->db->where($_where);

        }


        //  Tags
        //  ====

        if (!empty($data['tag_id'])) {

            $_where = $this->_table_prefix . '.id IN (SELECT product_id FROM ' . $this->_table_tag . ' WHERE tag_id ';

            if (is_array($data['tag_id'])) {

                $tagIds = array_map('intval', $data['tag_id']);
                $tagIds = array_map(array($this->db, 'escape'), $tagIds);
                $_where .= 'IN (' . implode(',', $tagIds) . ')';

            } else {

                $_where .= '= ' . $this->db->escape($data['tag_id']);

            }

            $_where .= ')';

            $this->db->where($_where);

        }

        // --------------------------------------------------------------------------

        /**
         * Filtering?
         * This is a beastly one, only do stuff if it's been requested
         */

        if (empty($data['_ignore_filters']) && !empty($data['filter'])) {

            //  Join the avriation table
            $this->db->join($this->_table_variation . ' spv', $this->_table_prefix . '.id = spv.product_id');

            foreach ($data['filter'] as $meta_field_id => $values) {

                if (!is_numeric($meta_field_id)) {

                    continue;

                }

                $_values = $values;
                $_values = array_filter($_values);
                $_values = array_unique($_values);
                $_values = array_map('intval', $_values);
                $_values = array_map(array($this->db, 'escape'), $_values);
                $_values = implode(',', $_values);

                $this->db->join($this->_table_variation_product_type_meta . ' spvptm' . $meta_field_id , 'spvptm' . $meta_field_id . '.variation_id = spv.id AND spvptm' . $meta_field_id . '.meta_field_id = \'' . $meta_field_id . '\' AND spvptm' . $meta_field_id . '.value IN (' . $_values . ')');

            }

            $this->db->group_by($this->_table_prefix . '.id');

        }
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches all products which feature a particular brand
     * @param  int   $brandId       The ID of the brand
     * @param  int   $page         The page number of the results, if null then no pagination
     * @param  int   $perPage       How many items per page of paginated results
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
     * @param  int   $brandId       The ID of the brand
     * @param  array   $data           Any data to pass to _getcount_common()
     * @param  boolean $includeDeleted If non-destructive delete is enabled then this flag allows you to include deleted items
     * @return int
     */
    public function countForBrand($brandId, $data = array(), $includeDeleted = false)
    {
        $data['brand_id'] = $brandId;
        return $this->count_all($data, $includeDeleted, 'COUNT_FOR_BRAND');
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches all products which feature a particular category
     * @param  int   $categoryId     The ID of the category
     * @param  int   $page         The page number of the results, if null then no pagination
     * @param  int   $perPage       How many items per page of paginated results
     * @param  array   $data           Any data to pass to _getcount_common()
     * @param  boolean $includeDeleted If non-destructive delete is enabled then this flag allows you to include deleted items
     * @return array
     */
    public function getForCategory($categoryId, $page = null, $perPage = null, $data = array(), $includeDeleted = false)
    {
        //  Fetch this category's children also
        $this->load->model('shop/shop_category_model');
        $data['category_id'] = array_merge(array($categoryId), $this->shop_category_model->get_ids_of_children($categoryId));
        return $this->get_all($page, $perPage, $data, $includeDeleted, 'GET_FOR_CATEGORY');
    }

    // --------------------------------------------------------------------------

    /**
     * Counts all products which feature a particular category
     * @param  int   $categoryId     The ID of the category
     * @param  array   $data           Any data to pass to _getcount_common()
     * @param  boolean $includeDeleted If non-destructive delete is enabled then this flag allows you to include deleted items
     * @return int
     */
    public function countForCategory($categoryId, $data = array(), $includeDeleted = false)
    {
        //  Fetch this category's children also
        $this->load->model('shop/shop_category_model');
        $data['category_id'] = array_merge(array($categoryId), $this->shop_category_model->get_ids_of_children($categoryId));
        return $this->count_all($data, $includeDeleted, 'COUNT_FOR_CATEGORY');
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches all products which feature a particular collection
     * @param  int   $collectionId   The ID of the collection
     * @param  int   $page         The page number of the results, if null then no pagination
     * @param  int   $perPage       How many items per page of paginated results
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
     * @param  int   $collectionId   The ID of the collection
     * @param  array   $data           Any data to pass to _getcount_common()
     * @param  boolean $includeDeleted If non-destructive delete is enabled then this flag allows you to include deleted items
     * @return int
     */
    public function countForCollection($collectionId, $data = array(), $includeDeleted = false)
    {
        $data['collection_id'] = $collectionId;
        return $this->count_all($data, $includeDeleted, 'COUNT_FOR_COLLECTION');
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches all products which feature a particular range
     * @param  int   $rangeId       The ID of the range
     * @param  int   $page         The page number of the results, if null then no pagination
     * @param  int   $perPage       How many items per page of paginated results
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
     * @param  int   $rangeId       The ID of the range
     * @param  array   $data           Any data to pass to _getcount_common()
     * @param  boolean $includeDeleted If non-destructive delete is enabled then this flag allows you to include deleted items
     * @return int
     */
    public function countForRange($rangeId, $data = array(), $includeDeleted = false)
    {
        $data['range_id'] = $rangeId;
        return $this->count_all($data, $includeDeleted, 'COUNT_FOR_RANGE');
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches all products which feature a particular sale
     * @param  int   $saleId         The ID of the sale
     * @param  int   $page         The page number of the results, if null then no pagination
     * @param  int   $perPage       How many items per page of paginated results
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
     * @param  int   $saleId         The ID of the sale
     * @param  array   $data           Any data to pass to _getcount_common()
     * @param  boolean $includeDeleted If non-destructive delete is enabled then this flag allows you to include deleted items
     * @return int
     */
    public function countForSale($saleId, $data = array(), $includeDeleted = false)
    {
        $data['sale_id'] = $saleId;
        return $this->count_all($data, $includeDeleted, 'COUNT_FOR_SALE');
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches all products which feature a particular tag
     * @param  int   $tagId     The ID of the tag
     * @param  int   $page         The page number of the results, if null then no pagination
     * @param  int   $perPage       How many items per page of paginated results
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
     * @param  int   $tagId       The ID of the tag
     * @param  array   $data           Any data to pass to _getcount_common()
     * @param  boolean $includeDeleted If non-destructive delete is enabled then this flag allows you to include deleted items
     * @return int
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
     * @return string      The product's URL
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
        $product->id            = (int) $product->id;
        $product->is_active     = (bool) $product->is_active;
        $product->is_deleted    = (bool) $product->is_deleted;

        //  Product type
        $product->type                  = new \stdClass();
        $product->type->id              = (int) $product->type_id;
        $product->type->label           = $product->type_label;
        $product->type->max_per_order   = (int) $product->type_max_per_order;
        $product->type->is_physical     = $product->type_is_physical;

        unset($product->type_id);
        unset($product->type_label);
        unset($product->type_max_per_order);
        unset($product->type_is_physical);

        //  Tax Rate
        $product->tax_rate          = new \stdClass();
        $product->tax_rate->id      = (int) $product->tax_rate_id;
        $product->tax_rate->label   = $product->tax_rate_label;
        $product->tax_rate->rate    = $product->tax_rate_rate;

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
         **/

        if (empty($product->seo_description)) {

            //  Base string
            $product->seo_description = 'Buy ' . $product->label . ' at ' . APP_NAME;

            //  Add up to 3 categories
            if (!empty($product->categories)) {

                $_categories_arr    = array();
                $_counter           = 0;

                foreach ($product->categories as $category) {

                    $_categories_arr[] = $category->label;

                    $_counter++;

                    if ($_counter == 3) {

                        break;

                    }

                }

                $product->seo_description .= ' (' . implode(', ', $_categories_arr) . ')';

            }

            //  Add the first sentence of the description
            $_description = strip_tags($product->description);
            $product->seo_description .= ' - ' . substr($_description, 0, strpos($_description, '.') + 1);

            //  Encode entities
            $product->seo_description = htmlentities($product->seo_description);

        }

        if (empty($product->seo_keywords)) {

            //  Extract common keywords
            $this->lang->load('shop/shop');
            $_common = explode(',', lang('shop_common_words'));
            $_common = array_unique($_common);
            $_common = array_filter($_common);

            //  Remove them and return the most popular words
            $_description = strtolower($product->description);
            $_description = str_replace("\n", ' ', strip_tags($_description));
            $_description = str_word_count($_description, 1);
            $_description = array_count_values($_description    );
            arsort($_description);
            $_description = array_keys($_description);
            $_description = array_diff($_description, $_common);
            $_description = array_slice($_description, 0, 10);

            $product->seo_keywords = implode(',', $_description);

            //  Encode entities
            $product->seo_keywords = htmlentities($product->seo_keywords);

        }
    }

    // --------------------------------------------------------------------------

    /**
     * Adds a product as a recently viewed item and saves it to the user's meta
     * data if they're logged in.
     * @param int $productId The product's ID
     */
    public function addAsRecentlyViewed($productId)
    {
        //  Session
        $_recently_viewed = $this->session->userdata('shop_recently_viewed');

        if (empty($_recently_viewed)) {

            $_recently_viewed = array();

        }

        //  If this product is already there, remove it
        $_search = array_search($productId, $_recently_viewed);
        if ($_search !== false) {

            unset($_recently_viewed[$_search]);

        }

        //  Pop it on the end
        $_recently_viewed[] = (int) $productId;

        //  Restrict to 6 most recently viewed items
        $_recently_viewed = array_slice($_recently_viewed, -6);

        $this->session->set_userdata('shop_recently_viewed', $_recently_viewed);

        // --------------------------------------------------------------------------

        //  Logged in?
        if ($this->user_model->is_logged_in()) {

            $_data = array('shop_recently_viewed' => json_encode($_recently_viewed));
            $this->user_model->update(active_user('id'), $_data);

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
        $_recently_viewed = $this->session->userdata('shop_recently_viewed');

        // --------------------------------------------------------------------------

        //  Logged in?
        if (empty($_recently_viewed) && $this->user->is_logged_in()) {

            $_recently_viewed = active_user('shop_recently_viewed');

        }

        // --------------------------------------------------------------------------

        return array_filter((array) $_recently_viewed);
    }

    // --------------------------------------------------------------------------

    /**
     * Gets filters for products in a particular result set
     * @param  array $data A data array to pass to get_all
     * @return array
     */
    public function getFiltersForProducts($data)
    {
        if (!$this->_table) {

            show_error(get_called_class() . '::count_all() Table variable not set');

        } else {

            $_table  = $this->_table_prefix ? $this->_table . ' ' . $this->_table_prefix : $this->_table;

        }

        // --------------------------------------------------------------------------

        $_filters = array();

        // --------------------------------------------------------------------------

        /**
         * Get all variations which appear within this result set; then determine which
         * product types these variations belong too. From that we can work out which
         * filters need fetched, their values and (maybe) the number of products each
         * filter value contains.
         */

        //  Fetch the products in the result set
        $data['_do_not_select']     = true;
        $data['_ignore_filters']    = true;
        $this->_getcount_common($data, 'GET_FILTERS_FOR_PRODUCTS');
        $this->db->select('p.id, p.type_id');
        $_product_ids_raw   = $this->db->get($_table)->result();
        $_product_ids       = array();
        $_product_type_ids  = array();

        foreach ($_product_ids_raw as $pid) {

            $_product_ids[]         = $pid->id;
            $_product_type_ids[]    = $pid->type_id;

        }

        $_product_ids       = array_unique($_product_ids);
        $_product_ids       = array_filter($_product_ids);
        $_product_type_ids  = array_unique($_product_type_ids);
        $_product_type_ids  = array_filter($_product_type_ids);

        unset($_product_ids_raw);

        if (!empty($_product_ids)) {

            /**
             * Brand apply to most products, include a brand filter if we're not looking
             * at a brand page
             */

            if (!isset($data['brand_id'])) {

                $this->db->select('sb.id value, sb.label, COUNT(spb.product_id) product_count');
                $this->db->join(NAILS_DB_PREFIX . 'shop_brand sb', 'sb.id = spb.brand_id');
                $this->db->where_in('spb.product_id', $_product_ids);
                $this->db->group_by('sb.id');
                $this->db->order_by('sb.label');
                $_result = $this->db->get($this->_table_brand . ' spb')->result();

                if ($_result) {

                    $_filters[0]            = new \stdClass();
                    $_filters[0]->id        = 'brand_id';
                    $_filters[0]->label     = 'Brands';
                    $_filters[0]->values    = $_result;

                }

            }

            // --------------------------------------------------------------------------

            /**
             * Now fetch the variants in the result set, we'll use these
             * to restrict the values we show in the filters
             */

            $this->db->select('id');
            $this->db->where_in('product_id', $_product_ids);
            $_variant_ids_raw   = $this->db->get($this->_table_variation)->result();
            $_variant_ids       = array();

            foreach ($_variant_ids_raw as $vid) {

                $_variant_ids[] = $vid->id;

            }

            $_variant_ids = array_unique($_variant_ids);
            $_variant_ids = array_filter($_variant_ids);

            unset($_variant_ids_raw);

            /**
             * For each product type, get it's associated meta content and then fetch
             * the distinct values from the values table
             */

            $this->load->model('shop/shop_product_type_meta_model');
            $_meta_fields = $this->shop_product_type_meta_model->get_by_product_type_ids($_product_type_ids);

            /**
             * Now start adding to the filters array; this is basically just the
             * field label & ID with all potential values of the result set.
             */

            foreach ($_meta_fields as $field) {

                //  Ignore ones which aren't set as filters
                if (empty($field->is_filter)) {

                    continue;

                }

                $_temp = new \stdClass();
                $_temp->id      = $field->id;
                $_temp->label   = $field->label;

                $this->db->select('DISTINCT(`value`) `value`, COUNT(variation_id) product_count');
                $this->db->where('meta_field_id', $field->id);
                $this->db->where('value !=', '');
                $this->db->where_in('variation_id', $_variant_ids);
                $this->db->group_by('value');
                $_temp->values = $this->db->get($this->_table_variation_product_type_meta)->result();

                if (!empty($_temp->values)) {

                    foreach ($_temp->values as $v) {

                        $v->label = $v->value;

                    }

                    $_filters[] = $_temp;

                }

                unset($_temp);

            }

            unset($_meta_fields);

        }

        // --------------------------------------------------------------------------

        return $_filters;
    }

    // --------------------------------------------------------------------------

    /**
     * Shortcut to get filters for brands
     * @param  int  $brandId The ID of the brand
     * @param  array  $data  A data array to pass to get_all
     * @return array
     */
    public function getFiltersForProductsInBrand($brandId, $data = array())
    {
        $data['brand_id'] = $brandId;
        return $this->getFiltersForProducts($data);
    }

    // --------------------------------------------------------------------------

    /**
     * Shortcut to get filters for category
     * @param  int  $categoryId The ID of the category
     * @param  array  $data     A data array to pass to get_all
     * @return array
     */
    public function getFiltersForProductsInCategory($categoryId, $data = array())
    {
        //  Fetch this category's children also
        $this->load->model('shop/shop_category_model');
        $data['category_id'] = array_merge(array($categoryId), $this->shop_category_model->get_ids_of_children($categoryId));
        return $this->getFiltersForProducts($data);
    }

    // --------------------------------------------------------------------------

    /**
     * Shortcut to get filters for collections
     * @param  int  $collectionId The ID of the collection
     * @param  array  $data       A data array to pass to get_all
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
     * @param  int  $rangeId The ID of the range
     * @param  array  $data  A data array to pass to get_all
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
     * @param  int  sale_id The ID of the sale
     * @param  array  $data   A data array to pass to get_all
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
     * @param  int  $tagId The ID of the tag
     * @param  array  $data   A data array to pass to get_all
     * @return array
     */
    public function getFiltersForProductsInTag($tagId, $data = array())
    {
        $data['tag_id'] = $tagId;
        return $this->getFiltersForProducts($data);
    }

    // --------------------------------------------------------------------------

    /**
     * Shortcut to get filters for search result
     * @param  int  $tagId The ID of the tag
     * @param  array  $data   A data array to pass to get_all
     * @return array
     */
    public function getFiltersForProductsInSearch($search, $data = array())
    {
        $data['search'] = $search;
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
        $variation->id                  = (int) $variation->id;
        $variation->product_id          = (int) $variation->product_id;
        $variation->order               = (int) $variation->order;
        $variation->is_deleted          = (bool) $variation->is_deleted;
        $variation->quantity_available  = is_numeric($variation->quantity_available) ? (int) $variation->quantity_available : null;

        //  Gallery
        if (!empty($variation->gallery) && is_array($variation->gallery)) {

            foreach ($variation->gallery as &$object_id) {

                $object_id  = (int) $object_id;

            }

        }

        //  Price
        if (!empty($variation->price_raw) && is_array($variation->price_raw)) {

            foreach ($variation->price_raw as $price) {

                $price->price       = (float) $price->price;
                $price->sale_price  = (float) $price->sale_price;

            }

        }

        //  Shipping data
        $variation->shipping                    = new \stdClass();
        $variation->shipping->collection_only   = (bool) $variation->ship_collection_only;
        $variation->shipping->driver_data       = @unserialize($variation->ship_driver_data);

        //  Stock status
        if ($variation->stock_status == 'IN_STOCK' && !is_null($variation->quantity_available) && $variation->quantity_available <= 0) {

            //  Item is marked as IN_STOCK, but there's no stock to sell, set as out of stock so the `out_of_stock_behaviour` kicks in.
            $variation->stock_status = 'OUT_OF_STOCK';

        }

        if ($variation->stock_status == 'OUT_OF_STOCK') {

            switch ($variation->out_of_stock_behaviour) {

                case 'TO_ORDER' :

                    //  Set the original values, in case they're needed
                    $variation->stock_status_original   = $variation->stock_status;
                    $variation->lead_time_original      = $variation->lead_time;

                    //  And... override!
                    $variation->stock_status    = 'TO_ORDER';
                    $variation->lead_time       = $variation->out_of_stock_to_order_lead_time ? $variation->out_of_stock_to_order_lead_time : $variation->lead_time;

                break;

                case 'OUT_OF_STOCK' :
                default :

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
