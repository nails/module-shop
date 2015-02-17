<?php

/**
 * Manage the shop's inventory
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    AdminController
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Admin\Shop;

class Inventory extends \AdminController
{
    /**
     * Announces this controller's navGroups
     * @return stdClass
     */
    public static function announce()
    {
        if (userHasPermission('admin.shop:0.inventory_manage')) {

            $navGroup = new \Nails\Admin\Nav('Shop');
            $navGroup->addMethod('Manage Inventory');
            return $navGroup;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Construct the controller
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('shop/shop_model');
        $this->load->model('shop/shop_category_model');
        $this->load->model('shop/shop_product_model');
        $this->load->model('shop/shop_product_type_model');

        // --------------------------------------------------------------------------

        //  @todo Move this into a common constructor
        $this->shopName = $this->shopUrl = $this->shop_model->getShopName();
        $this->shopUrl  = $this->shopUrl = $this->shop_model->getShopUrl();

        //  Pass data to the views
        $this->data['shopName'] = $this->shopName;
        $this->data['shopUrl']  = $this->shopUrl;
    }

    // --------------------------------------------------------------------------

    /**
     * Browse the Shop's inventory
     * @return void
     */
    public function index()
    {
        if (!userHasPermission('admin.shop:0.orders_manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Set method info
        $this->data['page']->title = 'Manage Inventory';

        // --------------------------------------------------------------------------

        $tablePrefix = $this->shop_product_model->getTablePrefix();

        // --------------------------------------------------------------------------

        //  Get pagination and search/sort variables
        $page      = $this->input->get('page')      ? $this->input->get('page')      : 0;
        $perPage   = $this->input->get('perPage')   ? $this->input->get('perPage')   : 50;
        $sortOn    = $this->input->get('sortOn')    ? $this->input->get('sortOn')    : $tablePrefix . '.label';
        $sortOrder = $this->input->get('sortOrder') ? $this->input->get('sortOrder') : 'asc';
        $keywords  = $this->input->get('keywords')  ? $this->input->get('keywords')  : '';

        // --------------------------------------------------------------------------

        //  Define the sortable columns and the filters
        $sortColumns = array(
            $tablePrefix . '.id'        => 'ID',
            $tablePrefix . '.label'     => 'Label',
            $tablePrefix . '.modified'  => 'Modified Date',
            $tablePrefix . '.is_active' => 'Active State',
            'pt.label'                  => 'Type'
        );

        // --------------------------------------------------------------------------

        //  Define the $data variable for the queries
        $data = array(
            'include_inactive' => true,
            'where' => array(),
            'sort' => array(
                array($sortOn, $sortOrder)
            ),
            'keywords' => $keywords
        );

        // --------------------------------------------------------------------------

        //  If we're restricting to a certain category...
        if ($this->input->get('categoryId')) {

            $categoryId          = $this->input->get('categoryId');
            $childCategories     = $this->shop_category_model->get_ids_of_children($categoryId);
            $data['category_id'] = array($categoryId) + $childCategories;

        }

        //  Get the items for the page
        $totalRows              = $this->shop_product_model->count_all($data);
        $this->data['products'] = $this->shop_product_model->get_all($page, $perPage, $data);

        //  Set Search and Pagination objects for the view
        $this->data['search']     = \Nails\Admin\Helper::searchObject(true, $sortColumns, $sortOn, $sortOrder, $perPage, $keywords);
        $this->data['pagination'] = \Nails\Admin\Helper::paginationObject($page, $perPage, $totalRows);

        // --------------------------------------------------------------------------

        //  Fetch other required bits of data
        $this->data['productTypes']   = $this->shop_product_type_model->get_all();
        $this->data['categoriesFlat'] = $this->shop_category_model->get_all_nested_flat();

        // --------------------------------------------------------------------------

        if (userHasPermission('admin.shop:0.inventory_create')) {

            \Nails\Admin\Helper::addHeaderButton('admin/shop/inventory/import', 'Import Items', 'orange');
            \Nails\Admin\Helper::addHeaderButton('admin/shop/inventory/create', 'Add New Item');
        }

        // --------------------------------------------------------------------------

        \Nails\Admin\Helper::loadView('index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a new Shop inventory item
     * @return void
     */
    public function create()
    {
        $this->data['page']->title = 'Add new Inventory Item';

        // --------------------------------------------------------------------------

        //  Fetch data, this data is used in both the view and the form submission
        $this->data['currencies']    = $this->shop_currency_model->get_all_supported();
        $this->data['product_types'] = $this->shop_product_type_model->get_all();

        if (!$this->data['product_types']) {

            //  No Product types, some need added, yo!
            $this->session->set_flashdata('message', '<strong>Hey!</strong> No product types have been defined. You must set some before you can add inventory items.');
            redirect('admin/shop/manage/productType/create');
        }

        // --------------------------------------------------------------------------

        //  Fetch product type meta fields
        $this->data['product_types_meta'] = array();
        $this->load->model('shop/shop_product_type_meta_model');

        foreach ($this->data['product_types'] as $type) {

            $this->data['product_types_meta'][$type->id] = $this->shop_product_type_meta_model->getByProductTypeId($type->id);
        }

        // --------------------------------------------------------------------------

        //  Fetch shipping data, used in form validation
        $this->load->model('shop/shop_shipping_driver_model');
        $this->data['shipping_driver']          = $this->shop_shipping_driver_model->getEnabled();
        $this->data['shipping_options_variant'] = $this->shop_shipping_driver_model->optionsVariant();

        // --------------------------------------------------------------------------

        //  Process POST
        if ($this->input->post()) {

            //  If the product is a draft i.e. not active, go ahead and save it without any
            if (empty($this->input->post('is_active'))) {

                //  Create draft product
                $product = $this->shop_product_model->create($this->input->post());

                if ($product) {

                    $this->session->set_flashdata('success', 'Draft product was created successfully.');
                    redirect('admin/shop/inventory');

                } else {

                    $this->data['error'] = 'There was a problem creating draft product. ' . $this->shop_product_model->last_error();
                }

            } else {

                //  Form validation, this'll be fun...
                $this->load->library('form_validation');

                //  Define all the rules
                $this->__inventory_create_edit_validation_rules($this->input->post());

                // --------------------------------------------------------------------------

                if ($this->form_validation->run($this)) {

                    //  Validated!Create the product
                    $product = $this->shop_product_model->create($this->input->post());

                    if ($product) {

                        $this->session->set_flashdata('success', 'Product was created successfully.');
                        redirect('admin/shop/inventory');

                    } else {

                        $this->data['error'] = 'There was a problem creating the Product. ' . $this->shop_product_model->last_error();

                    }

                } else {

                    $this->data['error'] = lang('fv_there_were_errors');
                }
            }
        }

        // --------------------------------------------------------------------------

        //  Load additional models
        $this->load->model('shop/shop_attribute_model');
        $this->load->model('shop/shop_brand_model');
        $this->load->model('shop/shop_category_model');
        $this->load->model('shop/shop_collection_model');
        $this->load->model('shop/shop_range_model');
        $this->load->model('shop/shop_tag_model');
        $this->load->model('shop/shop_tax_rate_model');

        // --------------------------------------------------------------------------

        //  Fetch additional data
        $this->data['product_types_flat'] = $this->shop_product_type_model->get_all_flat();
        $this->data['tax_rates']          = $this->shop_tax_rate_model->get_all_flat();
        $this->data['attributes']         = $this->shop_attribute_model->get_all_flat();
        $this->data['brands']             = $this->shop_brand_model->get_all_flat();
        $this->data['categories']         = $this->shop_category_model->get_all_nested_flat();
        $this->data['collections']        = $this->shop_collection_model->get_all();
        $this->data['ranges']             = $this->shop_range_model->get_all();
        $this->data['tags']               = $this->shop_tag_model->get_all_flat();

        $this->data['tax_rates'] = array('No Tax') + $this->data['tax_rates'];

        // --------------------------------------------------------------------------

        //  Assets
        $this->asset->library('uploadify');
        $this->asset->load('mustache.js/mustache.js', 'NAILS-BOWER');
        $this->asset->load('nails.admin.shop.inventory.createEdit.min.js', 'NAILS');

        $uploadtoken = $this->cdn->generate_api_upload_token(active_user('id'));

        $this->asset->inline('var _edit = new NAILS_Admin_Shop_Inventory_Create_Edit();', 'JS');
        $this->asset->inline('_edit.init(' . json_encode($product_types) . ', "' . $uploadToken . '");', 'JS');

        // --------------------------------------------------------------------------

        //  Libraries
        $this->load->library('mustache');

        // --------------------------------------------------------------------------

        //  Load views
        \Nails\Admin\Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Form Validation: Set the validation rules for creating/editing inventory items
     * @param  array $post The $_POST array
     * @return void
     */
    protected function __inventory_create_edit_validation_rules($post)
    {
        //  Product Info
        //  ============
        $this->form_validation->set_rules('type_id', '', 'xss_clean|required');
        $this->form_validation->set_rules('label', '', 'xss_clean|required');
        $this->form_validation->set_rules('is_active', '', 'xss_clean');
        $this->form_validation->set_rules('brands', '', 'xss_clean');
        $this->form_validation->set_rules('categories', '', 'xss_clean');
        $this->form_validation->set_rules('tags', '', 'xss_clean');
        $this->form_validation->set_rules('tax_rate_id', '', 'xss_clean|required');
        $this->form_validation->set_rules('published', '', 'xss_clean|required');

        // --------------------------------------------------------------------------

        //  External product
        if (app_setting('enable_external_products', 'shop')) {

            $this->form_validation->set_rules('is_external', '', 'xss_clean');

            if (!empty($post['is_external'])) {

                $this->form_validation->set_rules('external_vendor_label', '', 'xss_clean|required');
                $this->form_validation->set_rules('external_vendor_url', '', 'xss_clean|required');

            } else {

                $this->form_validation->set_rules('external_vendor_label', '', 'xss_clean');
                $this->form_validation->set_rules('external_vendor_url', '', 'xss_clean');
            }
        }

        // --------------------------------------------------------------------------

        //  Description
        //  ===========
        $this->form_validation->set_rules('description', '', 'required');

        // --------------------------------------------------------------------------

        //  Variants - Loop variants
        //  ========================
        if (!empty($post['variation']) && is_array($post['variation'])) {

            foreach ($post['variation'] as $index => $v) {

                //  Details
                //  -------

                $this->form_validation->set_rules('variation[' . $index . '][label]', '', 'xss_clean|trim|required');

                $v_id = !empty($v['id']) ? $v['id'] : '';
                $this->form_validation->set_rules('variation[' . $index . '][sku]', '', 'xss_clean|trim|callback__callback_inventory_valid_sku[' . $v_id . ']');

                //  Stock
                //  -----

                $this->form_validation->set_rules('variation[' . $index . '][stock_status]', '', 'xss_clean|callback__callback_inventory_valid_stock_status|required');

                $stock_status = isset($v['stock_status']) ? $v['stock_status'] : '';

                switch ($stock_status) {

                    case 'IN_STOCK':

                        $this->form_validation->set_rules('variation[' . $index . '][quantity_available]', '', 'xss_clean|trim|callback__callback_inventory_valid_quantity');
                        $this->form_validation->set_rules('variation[' . $index . '][lead_time]', '', 'xss_clean|trim');
                        break;

                    case 'OUT_OF_STOCK':

                        $this->form_validation->set_rules('variation[' . $index . '][quantity_available]', '', 'xss_clean|trim');
                        $this->form_validation->set_rules('variation[' . $index . '][lead_time]', '', 'xss_clean|trim');
                        break;
                }

                //  Pricing
                //  -------
                if (isset($v['pricing'])) {

                    foreach ($v['pricing'] as $price_index => $price) {

                        $required = $price['currency'] == SHOP_BASE_CURRENCY_CODE ? '|required' : '';

                        $this->form_validation->set_rules('variation[' . $index . '][pricing][' . $price_index . '][price]', '', 'xss_clean|callback__callback_inventory_valid_price' . $required);
                        $this->form_validation->set_rules('variation[' . $index . '][pricing][' . $price_index . '][sale_price]', '', 'xss_clean|callback__callback_inventory_valid_price' . $required);
                    }
                }

                //  Gallery Associations
                //  --------------------
                if (isset($v['gallery'])) {

                    foreach ($v['gallery'] as $gallery_index => $image) {

                        $this->form_validation->set_rules('variation[' . $index . '][gallery][' . $gallery_index . ']', '', 'xss_clean');
                    }
                }

                //  Shipping
                //  --------

                //  Collect only switch
                $this->form_validation->set_rules('variation[' . $index . '][shipping][collection_only]', '', 'xss_clean');

                //  Foreach of the driver's settings and apply any rules, but if collect only is on then don't bother
                $shipping_options = $this->shop_shipping_driver_model->optionsVariant();
                foreach ($shipping_options as $option) {

                    $rules      = array();
                    $rules[]    = 'xss_clean';

                    if (empty($post['variation'][$index]['shipping']['collection_only'])) {

                        if (!empty($option['validation'])) {

                            $option_validation = explode('|', $option['validation']);
                            $rules             = array_merge($rules, $option_validation);
                        }

                        if (!empty($option['required'])) {

                            $rules[] = 'required';
                        }
                    }

                    $rules = array_filter($rules);
                    $rules = array_unique($rules);
                    $rules = implode('|', $rules);

                    $this->form_validation->set_rules('variation[' . $index . '][shipping][driver_data][' . $this->data['shipping_driver']->slug . '][' . $option['key'] . ']', $option['label'], $rules);
                }
            }
        }

        // --------------------------------------------------------------------------

        //  Gallery
        $this->form_validation->set_rules('gallery', '', 'xss_clean');

        // --------------------------------------------------------------------------

        //  Attributes
        $this->form_validation->set_rules('attributes', '', 'xss_clean');

        // --------------------------------------------------------------------------

        //  Ranges & Collections
        $this->form_validation->set_rules('ranges', '', 'xss_clean');
        $this->form_validation->set_rules('collections', '', 'xss_clean');

        // --------------------------------------------------------------------------

        //  SEO
        $this->form_validation->set_rules('seo_title', '', 'xss_clean|max_length[150]');
        $this->form_validation->set_rules('seo_description', '', 'xss_clean|max_length[300]');
        $this->form_validation->set_rules('seo_keywords', '', 'xss_clean|max_length[150]');

        // --------------------------------------------------------------------------

        //  Set messages
        $this->form_validation->set_message('required', lang('fv_required'));
        $this->form_validation->set_message('numeric', lang('fv_numeric'));
        $this->form_validation->set_message('is_natural', lang('fv_is_natural'));
        $this->form_validation->set_message('max_length', lang('fv_max_length'));
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a shop inventory item
     * @return void
     */
    public function edit()
    {
        //  Fetch item
        $this->data['item'] = $this->shop_product_model->get_by_id($this->uri->segment(5));

        if (!$this->data['item']) {

            $this->session->set_flashdata('error', 'I could not find a product by that ID.');
            redirect('admin/shop/inventory');
        }

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Edit Inventory Item "' . $this->data['item']->label . '"';

        // --------------------------------------------------------------------------

        //  Fetch data, this data is used in both the view and the form submission
        $this->data['product_types'] = $this->shop_product_type_model->get_all();

        if (!$this->data['product_types']) {

            //  No Product types, some need added, yo!
            $this->session->set_flashdata('message', '<strong>Hey!</strong> No product types have been defined. You must set some before you can add inventory items.');
            redirect('admin/shop/manage/productType/create');
        }

        $this->data['currencies'] = $this->shop_currency_model->get_all_supported();

        //  Fetch product type meta fields
        $this->data['product_types_meta'] = array();
        $this->load->model('shop/shop_product_type_meta_model');

        foreach ($this->data['product_types'] as $type) {

            $this->data['product_types_meta'][$type->id] = $this->shop_product_type_meta_model->getByProductTypeId($type->id);
        }

        // --------------------------------------------------------------------------

        //  Fetch shipping data, used in form validation
        $this->load->model('shop/shop_shipping_driver_model');
        $this->data['shipping_driver']          = $this->shop_shipping_driver_model->getEnabled();
        $this->data['shipping_options_variant'] = $this->shop_shipping_driver_model->optionsVariant();

        // --------------------------------------------------------------------------

        //  Process POST
        if ($this->input->post()) {

            //  Form validation, this'll be fun...
            $this->load->library('form_validation');

            //  Define all the rules
            $this->__inventory_create_edit_validation_rules($this->input->post());

            // --------------------------------------------------------------------------

            if ($this->form_validation->run($this)) {

                //  Validated!Create the product
                $product = $this->shop_product_model->update($this->data['item']->id, $this->input->post());

                if ($product) {

                    $this->session->set_flashdata('success', 'Product was updated successfully.');
                    redirect('admin/shop/inventory');

                } else {

                    $this->data['error'] = 'There was a problem updating the Product. ' . $this->shop_product_model->last_error();
                }

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Load additional models
        $this->load->model('shop/shop_attribute_model');
        $this->load->model('shop/shop_brand_model');
        $this->load->model('shop/shop_category_model');
        $this->load->model('shop/shop_collection_model');
        $this->load->model('shop/shop_range_model');
        $this->load->model('shop/shop_tag_model');
        $this->load->model('shop/shop_tax_rate_model');

        // --------------------------------------------------------------------------

        //  Fetch additional data
        $this->data['product_types_flat'] = $this->shop_product_type_model->get_all_flat();
        $this->data['tax_rates']          = $this->shop_tax_rate_model->get_all_flat();
        $this->data['attributes']         = $this->shop_attribute_model->get_all_flat();
        $this->data['brands']             = $this->shop_brand_model->get_all_flat();
        $this->data['categories']         = $this->shop_category_model->get_all_nested_flat();
        $this->data['collections']        = $this->shop_collection_model->get_all();
        $this->data['ranges']             = $this->shop_range_model->get_all();
        $this->data['tags']               = $this->shop_tag_model->get_all_flat();

        $this->data['tax_rates'] = array('No Tax') + $this->data['tax_rates'];

        // --------------------------------------------------------------------------

        //  Assets
        $this->asset->library('uploadify');
        $this->asset->load('mustache.js/mustache.js', 'NAILS-BOWER');
        $this->asset->load('nails.admin.shop.inventory.createEdit.min.js', 'NAILS');

        $uploadtoken = $this->cdn->generate_api_upload_token(active_user('id'));

        $this->asset->inline('var _edit = new NAILS_Admin_Shop_Inventory_Create_Edit();', 'JS');
        $this->asset->inline('_edit.init(' . json_encode($product_types) . ', "' . $uploadToken . '");', 'JS');

        // --------------------------------------------------------------------------

        //  Libraries
        $this->load->library('mustache');

        // --------------------------------------------------------------------------

        //  Load views
        \Nails\Admin\Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a shop inventory item
     * @return void
     */
    public function delete()
    {
        $product = $this->shop_product_model->get_by_id($this->uri->segment(5));

        if (!$product) {

            $status = 'error';
            $msg    = 'A product with that ID could not be found.';
            $this->session->set_flashdata($status, $msg);
            redirect('admin/shop/inventory/index');
        }

        // --------------------------------------------------------------------------

        if ($this->shop_product_model->delete($product->id)) {

            $status = 'success';
            $msg    = 'Product successfully deleted!You can restore this product by ';
            $msg   .= anchor('/admin/shop/inventory/restore/' . $product->id, 'clicking here') . '.';

        } else {

            $status = 'error';
            $msg    = 'That product could not be deleted. ';
            $msg   .= $this->shop_product_model->last_error();
        }

        $this->session->set_flashdata($status, $msg);

        // --------------------------------------------------------------------------

        redirect('admin/shop/inventory/index');
    }

    // --------------------------------------------------------------------------

    /**
     * Restore a deleted inventory item
     * @return void
     */
    public function restore()
    {
        if ($this->shop_product_model->restore($this->uri->segment(5))) {

            $this->session->set_flashdata('success', 'Product successfully restored.');

        } else {

            $this->session->set_flashdata('error', 'That product could not be restored.');
        }

        // --------------------------------------------------------------------------

        redirect('admin/shop/inventory/index');
    }

    // --------------------------------------------------------------------------

    /**
     * Manage importing into the Shop's inventory
     * @return void
     */
    public function import()
    {
        $this->load->helper('string');
        $method = $this->uri->segment(5) ? $this->uri->segment(5) : 'index';
        $method = 'import' . underscore_to_camelcase(strtolower($method), false);

        if (method_exists($this, $method)) {

            $this->{$method}();

        } else {

            show_404('', true);
        }
    }

    // --------------------------------------------------------------------------

    /**
     * First step of the inventory import
     * @return void
     */
    protected function importIndex()
    {
        $this->data['page']->title = 'Import Inventory Items';

        // --------------------------------------------------------------------------

        \Nails\Admin\Helper::loadView('import');
    }

    // --------------------------------------------------------------------------

    /**
     * Download the spreadsheet used for inventory importing
     * @return void
     */
    protected function importDownload()
    {
        //  @todo: Generate the spreadsheet for download
        echo 'TODO: Generate the spreadsheet.';
    }

    // --------------------------------------------------------------------------

    /**
     * Form Validation: Validate an inventory item's price
     * @param  string $str The price to validate
     * @return boolean
     */
    public function _callback_inventory_valid_price($str)
    {
        $str = trim($str);

        if ($str && !is_numeric($str)) {

            $this->form_validation->set_message('_callback_inventory_valid_price', 'This is not a valid price');
            return false;

        } else {

            return true;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Form Validation: Validate an inventory item's quantity
     * @param  string $str The quantity to validate
     * @return boolean
     */
    public function _callback_inventory_valid_quantity($str)
    {
        $str = trim($str);

        if ($str && !is_numeric($str)) {

            $this->form_validation->set_message('_callback_inventory_valid_quantity', 'This is not a valid quantity');
            return false;
        } elseif (($str && is_numeric($str) && $str < 0)) {

            $this->form_validation->set_message('_callback_inventory_valid_quantity', lang('fv_is_natural'));
            return false;

        } else {

            return true;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Form Validation: Validate an inventory item's SKU
     * @param  string $str The SKU to validate
     * @return boolean
     */
    public function _callback_inventory_valid_sku($str, $variation_id)
    {
        $str = trim($str);

        if (empty($str)) {

            return true;
        }

        if ($variation_id) {

            $this->db->where('id !=', $variation_id);
        }

        $this->db->where('is_deleted', false);
        $this->db->where('sku', $str);
        $result = $this->db->get(NAILS_DB_PREFIX . 'shop_product_variation')->row();

        if ($result) {

            $this->form_validation->set_message('_callback_inventory_valid_sku', 'This SKU is already in use.');
            return false;

        } else {

            return true;
        }
    }
}
