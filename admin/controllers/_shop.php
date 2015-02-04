<?php

//  Include NAILS_Admin_Controller; executes common admin functionality.
require_once '_admin.php';

/**
 * Manage the shop
 *
 * @package     Nails
 * @subpackage  module-admin
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

class NAILS_Shop extends NAILS_Admin_Controller
{
    protected $reportSources;
    protected $reportFormats;

    // --------------------------------------------------------------------------

    /**
     * Announces this controller's navGroupings
     * @return stdClass
     */
    public static function announce()
    {
        if (!isModuleEnabled('nailsapp/module-shop')) {

            return false;
        }

        // --------------------------------------------------------------------------

        $d = new stdClass();

        // --------------------------------------------------------------------------

        //  Configurations
        $d->name = 'Shop';
        $d->icon = 'fa-shopping-cart';

        // --------------------------------------------------------------------------

        //  Navigation options
        $d->funcs               = array();

        if (user_has_permission('admin.shop:0.inventory_manage')) {

            $d->funcs['inventory'] = 'Manage Inventory';
        }

        if (user_has_permission('admin.shop:0.orders_manage')) {

            $d->funcs['orders'] = 'Manage Orders';
        }

        if (user_has_permission('admin.shop:0.vouchers_manage')) {

            $d->funcs['vouchers'] = 'Manage Vouchers';
        }

        if (user_has_permission('admin.shop:0.sale_manage')) {

            $d->funcs['sales'] = 'Manage Sales';
        }

        //  @TODO: Handle permissions here?
        $d->funcs['manage'] = 'Other Managers';

        if (user_has_permission('admin.shop:0.can_generate_reports')) {

            $d->funcs['reports'] = 'Generate Reports';
        }

        if (user_has_permission('admin.shop:0.notifications_manage')) {

            $d->funcs['product_availability_notifications'] = 'Product Availability Notifications';
        }

        // --------------------------------------------------------------------------

        return $d;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of notifications
     * @param  string $classIndex The class_index value, used when multiple admin instances are available
     * @return array
     */
    static function notifications($classIndex = null)
    {
        $ci =& get_instance();
        $notifications = array();

        // --------------------------------------------------------------------------

        get_instance()->load->model('shop/shop_order_model');

        $notifications['orders']            = array();
        $notifications['orders']['type']    = 'alert';
        $notifications['orders']['title']   = 'Unfulfilled orders';
        $notifications['orders']['value']   = get_instance()->shop_order_model->count_unfulfilled_orders();

        // --------------------------------------------------------------------------

        return $notifications;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of extra permissions for this controller
     * @param  string $classIndex The class_index value, used when multiple admin instances are available
     * @return array
     */
    static function permissions($classIndex = null)
    {
        $permissions = parent::permissions($classIndex);

        // --------------------------------------------------------------------------

        //  Inventory
        $permissions['inventory_manage']  = 'Inventory: Manage';
        $permissions['inventory_create']  = 'Inventory: Create';
        $permissions['inventory_edit']    = 'Inventory: Edit';
        $permissions['inventory_delete']  = 'Inventory: Delete';
        $permissions['inventory_restore'] = 'Inventory: Restore';

        //  Orders
        $permissions['orders_manage']    = 'Orders: Manage';
        $permissions['orders_view']      = 'Orders: View';
        $permissions['orders_edit']      = 'Orders: Edit';
        $permissions['orders_reprocess'] = 'Orders: Reprocess';
        $permissions['orders_process']   = 'Orders: Process';

        //  Vouchers
        $permissions['vouchers_manage']     = 'Vouchers: Manage';
        $permissions['vouchers_create']     = 'Vouchers: Create';
        $permissions['vouchers_activate']   = 'Vouchers: Activate';
        $permissions['vouchers_deactivate'] = 'Vouchers: Deactivate';

        //  Attributes
        $permissions['attribute_create'] = 'Attribute: Create';
        $permissions['attribute_create'] = 'Attribute: Create';
        $permissions['attribute_edit']   = 'Attribute: Edit';
        $permissions['attribute_delete'] = 'Attribute: Delete';

        //  Brands
        $permissions['brand_manage'] = 'Brand: Manage';
        $permissions['brand_create'] = 'Brand: Create';
        $permissions['brand_edit']   = 'Brand: Edit';
        $permissions['brand_delete'] = 'Brand: Delete';

        //  Categories
        $permissions['category_manage'] = 'Category: Manage';
        $permissions['category_create'] = 'Category: Create';
        $permissions['category_edit']   = 'Category: Edit';
        $permissions['category_delete'] = 'Category: Delete';

        //  Collections
        $permissions['collection_manage'] = 'Collection: Manage';
        $permissions['collection_create'] = 'Collection: Create';
        $permissions['collection_edit']   = 'Collection: Edit';
        $permissions['collection_delete'] = 'Collection: Delete';

        //  Ranges
        $permissions['range_manage'] = 'Range: Manage';
        $permissions['range_create'] = 'Range: Create';
        $permissions['range_edit']   = 'Range: Edit';
        $permissions['range_delete'] = 'Range: Delete';

        //  Sales
        $permissions['sale_manage'] = 'Sale: Manage';
        $permissions['sale_create'] = 'Sale: Create';
        $permissions['sale_edit']   = 'Sale: Edit';
        $permissions['sale_delete'] = 'Sale: Delete';

        //  Tags
        $permissions['tag_manage'] = 'Tag: Manage';
        $permissions['tag_create'] = 'Tag: Create';
        $permissions['tag_edit']   = 'Tag: Edit';
        $permissions['tag_delete'] = 'Tag: Delete';

        //  Tax Rates
        $permissions['tax_rate_manage'] = 'Tax Rate: Manage';
        $permissions['tax_rate_create'] = 'Tax Rate: Create';
        $permissions['tax_rate_edit']   = 'Tax Rate: Edit';
        $permissions['tax_rate_delete'] = 'Tax Rate: Delete';

        //  Product Types
        $permissions['product_type_manage'] = 'Product Type: Manage';
        $permissions['product_type_create'] = 'Product Type: Create';
        $permissions['product_type_edit']   = 'Product Type: Edit';
        $permissions['product_type_delete'] = 'Product Type: Delete';

        //  Product Type Meta Fields
        $permissions['product_type_meta_manage'] = 'Product Type Meta: Manage';
        $permissions['product_type_meta_create'] = 'Product Type Meta: Create';
        $permissions['product_type_meta_edit']   = 'Product Type Meta: Edit';
        $permissions['product_type_meta_delete'] = 'Product Type Meta: Delete';

        //  Reports
        $permissions['can_generate_reports']= 'Can generate Reports';

        //  Notifications
        $permissions['notifications_manage'] = 'Can manage Product notifications';
        $permissions['notifications_create'] = 'Can create Product notifications';
        $permissions['notifications_edit']   = 'Can edit Product notifications';
        $permissions['notifications_delete'] = 'Can delete Product notifications';

        // --------------------------------------------------------------------------

        return $permissions;
    }

    // --------------------------------------------------------------------------

    /**
     * Constructs the controller
     */
    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        //  Defaults defaults

        $this->shop_orders_group            = false;
        $this->shop_orders_where            = array();
        $this->shop_orders_actions          = array();
        $this->shop_orders_sortfields       = array();

        $this->shop_vouchers_group          = false;
        $this->shop_vouchers_where          = array();
        $this->shop_vouchers_actions        = array();
        $this->shop_vouchers_sortfields     = array();

        // --------------------------------------------------------------------------

        $this->shop_orders_sortfields[] = array('label' => 'ID', 'col' => 'o.id');
        $this->shop_orders_sortfields[] = array('label' => 'Date Placed', 'col' => 'o.created');
        $this->shop_orders_sortfields[] = array('label' => 'Last Modified', 'col' => 'o.modified');

        $this->shop_vouchers_sortfields[] = array('label' => 'ID', 'col' => 'v.id');
        $this->shop_vouchers_sortfields[] = array('label' => 'Code', 'col' => 'v.code');

        // --------------------------------------------------------------------------

        //  Load models which this controller depends on
        $this->load->model('shop/shop_model');
        $this->load->model('shop/shop_currency_model');
        $this->load->model('shop/shop_product_model');
        $this->load->model('shop/shop_product_type_model');
        $this->load->model('shop/shop_tax_rate_model');
        $this->load->model('shop/shop_product_type_meta_model');

        // --------------------------------------------------------------------------

        $this->data['shop_url'] = app_setting('url', 'shop') ? app_setting('url', 'shop') : 'shop/';
    }

    // --------------------------------------------------------------------------

    /**
     * Manage the Shop's inventory
     * @return void
     */
    public function inventory()
    {
        if (!user_has_permission('admin.shop:0.inventory_manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $method = $this->uri->segment(4) ? $this->uri->segment(4) : 'index';

        if (method_exists($this, '_inventory_' . $method)) {

            $this->{'_inventory_' . $method}();

        } else {

            show_404();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Browse the Shop's inventory
     * @return void
     */
    protected function _inventory_index()
    {
        //  Set method info
        $this->data['page']->title = 'Manage Inventory';

        //  Define the $data variable, this'll be passed to the get_all() and count_all() methods
        $data = array('where' => array(), 'sort' => array(), 'include_inactive' => true);

        // --------------------------------------------------------------------------

        //  Set useful vars
        $page       = $this->input->get('page')     ? $this->input->get('page')     : 0;
        $per_page   = $this->input->get('per_page') ? $this->input->get('per_page') : 50;
        $sort_on    = $this->input->get('sort_on')  ? $this->input->get('sort_on')  : 'p.label';
        $sort_order = $this->input->get('order')    ? $this->input->get('order')    : 'desc';
        $search     = $this->input->get('search')   ? $this->input->get('search')   : '';

        //  Set sort variables for view and for $data
        $this->data['sort_on']     = $data['sort']['column'] = $sort_on;
        $this->data['sort_order']  = $data['sort']['order']  = $sort_order;
        $this->data['search']      = $data['search']         = $search;
        $this->data['category_id'] = $data['category_id']    = $this->input->get('category');

        if (!empty($data['category_id'])) {

            $data['category_id'] = array($data['category_id']) + $this->shop_category_model->get_ids_of_children($data['category_id']);

        } else {

            unset($data['category_id']);
        }

        //  Define and populate the pagination object
        $this->data['pagination']             = new stdClass();
        $this->data['pagination']->page       = $page;
        $this->data['pagination']->per_page   = $per_page;
        $this->data['pagination']->total_rows = $this->shop_product_model->count_all($data);

        //  Fetch all the items for this page
        $this->data['products']       = $this->shop_product_model->get_all($page, $per_page, $data);
        $this->data['productTypes']   = $this->shop_product_type_model->get_all();
        $this->data['categoriesFlat'] = $this->shop_category_model->get_all_nested_flat();

        // --------------------------------------------------------------------------

        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/inventory/index', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Create a new Shop inventory item
     * @return void
     */
    protected function _inventory_create()
    {
        $this->data['page']->title = 'Add new Inventory Item';

        // --------------------------------------------------------------------------

        //  Fetch data, this data is used in both the view and the form submission
        $this->data['currencies']       = $this->shop_currency_model->get_all_supported();
        $this->data['product_types']    = $this->shop_product_type_model->get_all();

        if (!$this->data['product_types']) {

            //  No Product types, some need added, yo!
            $this->session->set_flashdata('message', '<strong>Hey!</strong> No product types have been defined. You must set some before you can add inventory items.');
            redirect('admin/shop/manage/product_type/create');
        }

        // --------------------------------------------------------------------------

        //  Fetch product type meta fields
        $this->data['product_types_meta'] = array();
        $this->load->model('shop/shop_product_type_meta_model');

        foreach ($this->data['product_types'] as $type) {

            $this->data['product_types_meta'][$type->id] = $this->shop_product_type_meta_model->get_by_product_type_id($type->id);
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

                    $this->session->set_flashdata('success', '<strong>Success!</strong> Draft product was created successfully.');
                    redirect('admin/shop/inventory');

                } else {

                    $this->data['error'] = '<strong>Sorry,</strong> there was a problem creating draft product. ' . $this->shop_product_model->last_error();

                }

            }else{

                //  Form validation, this'll be fun...
                $this->load->library('form_validation');

                //  Define all the rules
                $this->__inventory_create_edit_validation_rules($this->input->post());

                // --------------------------------------------------------------------------

                if ($this->form_validation->run($this)) {

                    //  Validated!Create the product
                    $product = $this->shop_product_model->create($this->input->post());

                    if ($product) {

                        $this->session->set_flashdata('success', '<strong>Success!</strong> Product was created successfully.');
                        redirect('admin/shop/inventory');

                    } else {

                        $this->data['error'] = '<strong>Sorry,</strong> there was a problem creating the Product. ' . $this->shop_product_model->last_error();

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

        // --------------------------------------------------------------------------

        //  Fetch additional data
        $this->data['product_types_flat']       = $this->shop_product_type_model->get_all_flat();
        $this->data['tax_rates']                = $this->shop_tax_rate_model->get_all_flat();
        $this->data['attributes']               = $this->shop_attribute_model->get_all_flat();
        $this->data['brands']                   = $this->shop_brand_model->get_all_flat();
        $this->data['categories']               = $this->shop_category_model->get_all_nested_flat();
        $this->data['collections']              = $this->shop_collection_model->get_all();
        $this->data['ranges']                   = $this->shop_range_model->get_all();
        $this->data['tags']                     = $this->shop_tag_model->get_all_flat();

        $this->data['tax_rates'] = array('No Tax') + $this->data['tax_rates'];

        // --------------------------------------------------------------------------

        //  Assets
        $this->asset->library('uploadify');
        $this->asset->load('mustache.js/mustache.js', 'NAILS-BOWER');
        $this->asset->load('nails.admin.shop.inventory.create_edit.min.js', true);

        // --------------------------------------------------------------------------

        //  Libraries
        $this->load->library('mustache');

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/inventory/edit', $this->data);
        $this->load->view('structure/footer', $this->data);
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

                            $option_validation  = explode('|', $option['validation']);
                            $rules              = array_merge($rules, $option_validation);
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
        $this->form_validation->set_message('required',         lang('fv_required'));
        $this->form_validation->set_message('numeric',              lang('fv_numeric'));
        $this->form_validation->set_message('is_natural',           lang('fv_is_natural'));
        $this->form_validation->set_message('max_length',           lang('fv_max_length'));
    }

    // --------------------------------------------------------------------------

    /**
     * Manage importing into the Shop's inventory
     * @return void
     */
    protected function _inventory_import()
    {
        $method = $this->uri->segment(5) ? $this->uri->segment(5) : 'index';

        if (method_exists($this, '_inventory_import_' . $method)) {

            $this->{'_inventory_import_' . $method}();

        } else {

            show_404();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * First step of the inventory import
     * @return void
     */
    protected function _inventory_import_index()
    {
        $this->data['page']->title = 'Import Inventory Items';

        // --------------------------------------------------------------------------

        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/inventory/import', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Download the spreadsheet used for inventory importing
     * @return void
     */
    protected function _inventory_import_download()
    {
        echo 'TODO: Generate the spreadsheet.';
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a shop inventory item
     * @return void
     */
    protected function _inventory_edit()
    {
        //  Fetch item
        $this->data['item'] = $this->shop_product_model->get_by_id($this->uri->segment(5));

        if (!$this->data['item']) {

            $this->session->set_flashdata('error', '<strong>Sorry,</strong> I could not find a product by that ID.');
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
            redirect('admin/shop/manage/product_type/create');
        }

        $this->data['currencies'] = $this->shop_currency_model->get_all_supported();

        //  Fetch product type meta fields
        $this->data['product_types_meta'] = array();
        $this->load->model('shop/shop_product_type_meta_model');

        foreach ($this->data['product_types'] as $type) {

            $this->data['product_types_meta'][$type->id] = $this->shop_product_type_meta_model->get_by_product_type_id($type->id);
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

                    $this->session->set_flashdata('success', '<strong>Success!</strong> Product was updated successfully.');
                    redirect('admin/shop/inventory');

                } else {

                    $this->data['error'] = '<strong>Sorry,</strong> there was a problem updating the Product. ' . $this->shop_product_model->last_error();

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
        $this->asset->load('nails.admin.shop.inventory.create_edit.min.js', true);

        // --------------------------------------------------------------------------

        //  Libraries
        $this->load->library('mustache');

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/inventory/edit', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a shop inventory item
     * @return void
     */
    protected function _inventory_delete()
    {
        $product = $this->shop_product_model->get_by_id($this->uri->segment(5));

        if (!$product) {

            $status = 'error';
            $msg    = '<strong>Sorry,</strong> a product with that ID could not be found.';
            $this->session->set_flashdata($status, $msg);
            redirect('admin/shop/inventory/index');
        }

        // --------------------------------------------------------------------------

        if ($this->shop_product_model->delete($product->id)) {

            $status  = 'success';
            $msg     = '<strong>Success!</strong> Product successfully deleted! You can restore this product by ';
            $msg    .= anchor('/admin/shop/inventory/restore/' . $product->id, 'clicking here') . '.';

        } else {

            $status  = 'error';
            $msg     = '<strong>Sorry,</strong> that product could not be deleted. ';
            $msg    .= $this->shop_product_model->last_error();
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
    protected function _inventory_restore()
    {
        if ($this->shop_product_model->restore($this->uri->segment(5))) {

            $this->session->set_flashdata('success', '<strong>Success!</strong> Product successfully restored.');

        } else {

            $this->session->set_flashdata('error', '<strong>Sorry,</strong> that product could not be restored.');
        }

        // --------------------------------------------------------------------------

        redirect('admin/shop/inventory/index');
    }

    // --------------------------------------------------------------------------

    /**
     * Manage Shop orders
     * @return void
     */
    public function orders()
    {
        if (!user_has_permission('admin.shop:0.orders_manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $method = $this->uri->segment(4) ? $this->uri->segment(4) : 'index';

        if (method_exists($this, '_orders_' . $method)) {

            $this->{'_orders_' . $method}();

        } else {

            show_404();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Browse shop orders
     * @return void
     */
    protected function _orders_index()
    {
        //  Set method info
        $this->data['page']->title = 'Manage Orders';

        // --------------------------------------------------------------------------

        //  Searching, sorting, ordering and paginating.
        $hash = 'search_' . md5(uri_string()) . '_';

        if ($this->input->get('reset')) {

            $this->session->unset_userdata($hash . 'per_page');
            $this->session->unset_userdata($hash . 'sort');
            $this->session->unset_userdata($hash . 'order');
        }

        $default_per_page = $this->session->userdata($hash . 'per_page') ? $this->session->userdata($hash . 'per_page') : 50;
        $default_sort     = $this->session->userdata($hash . 'sort') ?    $this->session->userdata($hash . 'sort') : 'o.id';
        $default_order    = $this->session->userdata($hash . 'order') ?   $this->session->userdata($hash . 'order') : 'desc';

        //  Define vars
        $search = array('keywords' => $this->input->get('search'), 'columns' => array());

        foreach ($this->shop_orders_sortfields as $field) {

            $search['columns'][strtolower($field['label'])] = $field['col'];
        }

        $limit      = array(
                        $this->input->get('per_page') ? $this->input->get('per_page') : $default_per_page,
                        $this->input->get('offset') ? $this->input->get('offset') : 0
                    );
        $order      = array(
                        $this->input->get('sort') ? $this->input->get('sort') : $default_sort,
                        $this->input->get('order') ? $this->input->get('order') : $default_order
                    );

        //  Set sorting and ordering info in session data so it's remembered for when user returns
        $this->session->set_userdata($hash . 'per_page', $limit[0]);
        $this->session->set_userdata($hash . 'sort', $order[0]);
        $this->session->set_userdata($hash . 'order', $order[1]);

        //  Set values for the page
        $this->data['search']               = new stdClass();
        $this->data['search']->per_page     = $limit[0];
        $this->data['search']->sort         = $order[0];
        $this->data['search']->order        = $order[1];
        $this->data['search']->show         = $this->input->get('show');
        $this->data['search']->fulfilled    = $this->input->get('fulfilled');

        /**
         * Small hack(?) - if no status has been specified, and the $GET array is
         * empty (i.e no form of searching is being done) then set a few defaults.
         */

        if (empty($GET) && empty($this->data['search']->show)) {

            $this->data['search']->show = array('paid' => true);
        }

        // --------------------------------------------------------------------------

        //  Prepare the where
        if ($this->data['search']->show || $this->data['search']->fulfilled) {

            $where = '(';

            if ($this->data['search']->show) {

                $where .= '`o`.`status` IN (';

                    $statuses = array_keys($this->data['search']->show);
                    foreach ($statuses as &$stat) {

                        $stat = strtoupper($stat);
                    }
                    $where .= "'" . implode("', '", $statuses) . "'";

                $where .= ')';
            }

            // --------------------------------------------------------------------------

            if ($this->data['search']->show && $this->data['search']->fulfilled) {

                $where .= ' AND ';
            }

            // --------------------------------------------------------------------------

            if ($this->data['search']->fulfilled) {

                $where .= '`o`.`fulfilment_status` IN (';

                    $statuses = array_keys($this->data['search']->fulfilled);
                    foreach ($statuses as &$stat) {

                        $stat = strtoupper($stat);
                    }
                    $where .= "'" . implode("', '", $statuses) . "'";

                $where .= ')';
            }

            $where .= ')';

        } else {

            $where = null;
        }

        // --------------------------------------------------------------------------

        //  Pass any extra data to the view
        $this->data['actions']      = $this->shop_orders_actions;
        $this->data['sortfields']   = $this->shop_orders_sortfields;

        // --------------------------------------------------------------------------

        //  Fetch orders
        $this->load->model('shop/shop_order_model');

        $this->data['orders']       = new stdClass();
        $this->data['orders']->data = $this->shop_order_model->get_all($order, $limit, $where, $search);

        //  Work out pagination
        $this->data['orders']->pagination                   = new stdClass();
        $this->data['orders']->pagination->total_results    = $this->shop_order_model->count_orders($where, $search);

        // --------------------------------------------------------------------------

        $this->asset->load('nails.admin.shop.order.browse.min.js', true);
        $this->asset->inline('var _SHOP_ORDER_BROWSE = new NAILS_Admin_Shop_Order_Browse()', 'JS');

        // --------------------------------------------------------------------------

        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/orders/index', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * View a single order
     * @return void
     */
    protected function _orders_view()
    {
        if (!user_has_permission('admin.shop:0.orders_view')) {

            $this->session->set_flashdata('error', '<strong>Sorry,</strong> you do not have permission to view order details.');
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        //  Fetch and check order
        $this->load->model('shop/shop_order_model');

        $this->data['order'] = $this->shop_order_model->get_by_id($this->uri->segment(5));

        if (!$this->data['order']) {

            $this->session->set_flashdata('error', '<strong>Sorry,</strong> no order exists by that ID.');
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        //  Get associated payments
        $this->load->model('shop/shop_order_payment_model');
        $this->data['payments'] = $this->shop_order_payment_model->get_for_order($this->data['order']->id);

        // --------------------------------------------------------------------------

        //  Set method info
        $this->data['page']->title = 'View Order &rsaquo; ' . $this->data['order']->ref;

        // --------------------------------------------------------------------------

        if ($this->input->get('is_fancybox')) {

            $this->data['headerOverride'] = 'structure/headerBlank';
            $this->data['footerOverride'] = 'structure/footerBlank';
        }

        // --------------------------------------------------------------------------

        $this->asset->load('nails.admin.shop.order.view.min.js', true);
        $this->asset->inline('var _SHOP_ORDER_VIEW = new NAILS_Admin_Shop_Order_View()', 'JS');

        // --------------------------------------------------------------------------

        if ($this->data['order']->fulfilment_status != 'FULFILLED' && !$this->data['order']->requires_shipping) {

            $this->data['error']  = '<strong>Do not ship this order!</strong>';

            if ($this->data['order']->delivery_type == 'COLLECT') {

                $this->data['error'] .= '<br />This order will be collected by the customer.';

            } else {

                $this->data['error'] .= '<br />This order does not require shipping.';
            }
        }

        // --------------------------------------------------------------------------

        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/orders/view', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Reprocess an order
     * @return void
     */
    protected function _orders_reprocess()
    {
        if (!user_has_permission('admin.shop:0.orders_reprocess')) {

            $this->session->set_flashdata('error', '<strong>Sorry,</strong> you do not have permission to reprocess orders.');
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        //  Check order exists
        $this->load->model('shop/shop_order_model');
        $order = $this->shop_order_model->get_by_id($this->uri->segment(5));

        if (!$order) {

            $this->session->set_flashdata('error', '<strong>Sorry,</strong> I couldn\'t find an order by that ID.');
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        //  PROCESSSSSS...
        $this->shop_order_model->process($order);

        // --------------------------------------------------------------------------

        //  Send a receipt to the customer
        $this->shop_order_model->send_receipt($order);

        // --------------------------------------------------------------------------

        //  Send a notification to the store owner(s)
        $this->shop_order_model->send_order_notification($order);

        // --------------------------------------------------------------------------

        if ($order->voucher) {

            //  Redeem the voucher, if it's there
            $this->load->model('shop/shop_voucher_model');
            $this->shop_voucher_model->redeem($order->voucher->id, $order);
        }

        // --------------------------------------------------------------------------

        $this->session->set_flashdata('success', '<strong>Success!</strong> Order was processed succesfully. The user has been sent a receipt.');
        redirect('admin/shop/orders');
    }

    // --------------------------------------------------------------------------

    /**
     * Process an order
     * @return void
     */
    protected function _orders_process()
    {
        if (!user_has_permission('admin.shop:0.orders_process')) {

            $this->session->set_flashdata('error', '<strong>Sorry,</strong> you do not have permission to process order items.');
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        $order_id       = $this->uri->segment(5);
        $product_id = $this->uri->segment(6);
        $is_fancybox    = $this->input->get('is_fancybox') ? '?is_fancybox=true' : '';

        // --------------------------------------------------------------------------

        //  Update item
        if ($this->uri->segment(7) == 'processed') {

            $this->db->set('processed', true);

        } else {

            $this->db->set('processed', false);
        }

        $this->db->where('order_id',    $order_id);
        $this->db->where('id',          $product_id);

        $this->db->update(NAILS_DB_PREFIX . 'shop_order_product');

        if ($this->db->affected_rows()) {

            //  Product updated, check if order has been fulfilled
            $this->db->where('order_id', $order_id);
            $this->db->where('processed', false);

            if (!$this->db->count_all_results(NAILS_DB_PREFIX . 'shop_order_product')) {

                //  No unprocessed items, consider order FULFILLED
                $this->load->model('shop/shop_order_model');
                $this->shop_order_model->fulfil($order_id);

            } else {

                //  Still some unprocessed items, mark as unfulfilled (in case it was already fulfilled)
                $this->load->model('shop/shop_order_model');
                $this->shop_order_model->unfulfil($order_id);
            }

            // --------------------------------------------------------------------------

            $this->session->set_flashdata('success', '<strong>Success!</strong> Product\'s status was updated successfully.');
            redirect('admin/shop/orders/view/' . $order_id . $is_fancybox);

        } else {

            $this->session->set_flashdata('error', '<strong>Sorry,</strong> I was not able to update the status of that product.');
            redirect('admin/shop/orders/view/' . $order_id . $is_fancybox);
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Download an order's invoice
     * @return void
     */
    protected function _orders_download_invoice()
    {
        if (!user_has_permission('admin.shop:0.orders_view')) {

            $this->session->set_flashdata('error', '<strong>Sorry,</strong> you do not have permission to download orders.');
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        //  Fetch and check order
        $this->load->model('shop/shop_order_model');

        $this->data['order'] = $this->shop_order_model->get_by_id($this->uri->segment(5));

        if (!$this->data['order']) {

            $this->session->set_flashdata('error', '<strong>Sorry,</strong> no order exists by that ID.');
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        //  Load up the shop's skin
        $skin = app_setting('skin_checkout', 'shop') ? app_setting('skin_checkout', 'shop') : 'shop-skin-checkout-classic';

        $this->load->model('shop/shop_skin_checkout_model');
        $skin = $this->shop_skin_checkout_model->get($skin);

        if (!$skin) {

            showFatalError('Failed to load shop skin "' . $skin . '"', 'Shop skin "' . $skin . '" failed to load at ' . APP_NAME . ', the following reason was given: ' . $this->shop_skin_checkout_model->last_error());
        }

        // --------------------------------------------------------------------------

        //  Views
        $this->data['for_user'] = 'ADMIN';
        $this->load->library('pdf/pdf');
        $this->pdf->set_paper_size('A4', 'landscape');
        $this->pdf->load_view($skin->path . 'views/order/invoice', $this->data);
        $this->pdf->download('INVOICE-' . $this->data['order']->ref . '.pdf');
    }

    // --------------------------------------------------------------------------

    /**
     * Mark an order as fulfilled
     * @return void
     */
    protected function _orders_fulfil()
    {
        if (!user_has_permission('admin.shop:0.orders_edit')) {

            $msg    = '<strong>Sorry,</strong> you do not have permission to edit orders.';
            $status = 'error';
            $this->session->set_flashdata($status, $msg);
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        //    Fetch and check order
        $this->load->model('shop/shop_order_model');

        $order = $this->shop_order_model->get_by_id($this->uri->segment(5));

        if (!$order) {

            $msg    = '<strong>Sorry,</strong> no order exists by that ID.';
            $status = 'error';
            $this->session->set_flashdata($status, $msg);
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        if ($this->shop_order_model->fulfil($order->id)) {

            $msg    = '<strong>Success!</strong> Order ' . $order->ref . ' was marked as fulfilled.';
            $status = 'success';

        } else {

            $msg    = '<strong>Sorry,</strong> failed to mark order ' . $order->ref . ' as fulfilled.';
            $status = 'error';
        }

        $this->session->set_flashdata($status, $msg);
        redirect('admin/shop/orders/view/' . $order->id);
    }

    // --------------------------------------------------------------------------

    /**
     * Batch fulfil orders
     * @return void
     */
    protected function _orders_fulfil_batch()
    {
        if (!user_has_permission('admin.shop:0.orders_edit')) {

            $msg    = '<strong>Sorry,</strong> you do not have permission to edit orders.';
            $status = 'error';
            $this->session->set_flashdata($status, $msg);
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        //    Fetch and check orders
        $this->load->model('shop/shop_order_model');

        if ($this->shop_order_model->fulfilBatch($this->input->get('ids'))) {

            $msg    = '<strong>Success!</strong> Orders were marked as fulfilled.';
            $status = 'success';

        } else {

            $msg     = '<strong>Sorry,</strong> failed to mark orders as fulfilled. ';
            $msg    .= $this->shop_order_model->last_error();
            $status  = 'error';
        }

        $this->session->set_flashdata($status, $msg);
        redirect('admin/shop/orders');
    }

    // --------------------------------------------------------------------------

    /**
     * Mark an order as unfulfilled
     * @return void
     */
    protected function _orders_unfulfil()
    {
        if (!user_has_permission('admin.shop:0.orders_edit')) {

            $msg    = '<strong>Sorry,</strong> you do not have permission to edit orders.';
            $status = 'error';
            $this->session->set_flashdata($status, $msg);
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        //    Fetch and check order
        $this->load->model('shop/shop_order_model');

        $order = $this->shop_order_model->get_by_id($this->uri->segment(5));

        if (!$order) {

            $msg    = '<strong>Sorry,</strong> no order exists by that ID.';
            $status = 'error';
            $this->session->set_flashdata($status, $msg);
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        if ($this->shop_order_model->unfulfil($order->id)) {

            $msg    = '<strong>Success!</strong> Order ' . $order->ref . ' was marked as unfulfilled.';
            $status = 'success';

        } else {

            $msg    = '<strong>Sorry,</strong> failed to mark order ' . $order->ref . ' as unfulfilled.';
            $status = 'error';
        }

        $this->session->set_flashdata($status, $msg);
        redirect('admin/shop/orders/view/' . $order->id);
    }

    //---------------------------------------------------------------------------

    /**
     * Batch unfulfil orders
     * @return [type] [description]
     */
    protected function _orders_unfulfil_batch()
    {
        if (!user_has_permission('admin.shop:0.orders_edit')) {

            $msg    = '<strong>Sorry,</strong> you do not have permission to edit orders.';
            $status = 'error';
            $this->session->set_flashdata($status, $msg);
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        //    Fetch and check orders
        $this->load->model('shop/shop_order_model');

        if ($this->shop_order_model->unfulfilBatch($this->input->get('ids'))) {

            $msg    = '<strong>Success!</strong> Orders were marked as unfulfilled.';
            $status = 'success';

        } else {

            $msg     = '<strong>Sorry,</strong> failed to mark orders as unfulfilled. ';
            $msg    .= $this->shop_order_model->last_error();
            $status  = 'error';
        }

        $this->session->set_flashdata($status, $msg);
        redirect('admin/shop/orders');
    }

    // --------------------------------------------------------------------------

    /**
     * Manage vouchers
     * @return void
     */
    public function vouchers()
    {
        if (!user_has_permission('admin.shop:0.vouchers_manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load voucher model
        $this->load->model('shop/shop_voucher_model');

        // --------------------------------------------------------------------------

        $method = $this->uri->segment(4) ? $this->uri->segment(4) : 'index';

        if (method_exists($this, '_vouchers_' . $method)) {

            $this->{'_vouchers_' . $method}();

        } else {

            show_404();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Browse voucehrs
     * @return void
     */
    protected function _vouchers_index()
    {
        //  Set method info
        $this->data['page']->title = 'Manage Vouchers';

        //  Define the $data variable, this'll be passed to the get_all() and count_all() methods
        $data = array('sort' => array());

        // --------------------------------------------------------------------------

        //  Set useful vars
        $page       = $this->input->get('page')     ? $this->input->get('page')     : 0;
        $per_page   = $this->input->get('per_page') ? $this->input->get('per_page') : 50;
        $sort_on    = $this->input->get('sort_on')  ? $this->input->get('sort_on')  : 'sv.created';
        $sort_order = $this->input->get('order')    ? $this->input->get('order')    : 'desc';
        $search     = $this->input->get('search')   ? $this->input->get('search')   : '';

        //  Set sort variables for view and for $data
        $this->data['sort_on']     = $data['sort']['column'] = $sort_on;
        $this->data['sort_order']  = $data['sort']['order']  = $sort_order;
        $this->data['search']      = $data['search']         = $search;

        //  Restrict to certain columns
        if ($this->input->get('show')) {

            $data['where_in'] = array();
            $data['where_in'][] = array('column' => 'sv.type', 'value' => $this->input->get('show'));
        }

        //  Define and populate the pagination object
        $this->data['pagination']             = new stdClass();
        $this->data['pagination']->page       = $page;
        $this->data['pagination']->per_page   = $per_page;
        $this->data['pagination']->total_rows = $this->shop_voucher_model->count_all($data);

        //  Fetch all the items for this page
        $this->data['vouchers'] = $this->shop_voucher_model->get_all($page, $per_page, $data);

        // --------------------------------------------------------------------------

        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/vouchers/index', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Create a new voucher
     * @return void
     */
    protected function _vouchers_create()
    {
        if (!user_has_permission('admin.shop:0.vouchers_create')) {

            $this->session->set_flashdata('error', '<strong>Sorry,</strong> you do not have permission to create vouchers.');
            redirect('admin/shop/vouchers');
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->load->library('form_validation');

            //  Common
            $this->form_validation->set_rules('type', '', 'required|callback__callback_voucher_valid_type');
            $this->form_validation->set_rules('code', '', 'required|is_unique[' . NAILS_DB_PREFIX . 'shop_voucher.code]|callback__callback_voucher_valid_code');
            $this->form_validation->set_rules('label', '', 'required');
            $this->form_validation->set_rules('valid_from', '', 'required|callback__callback_voucher_valid_from');
            $this->form_validation->set_rules('valid_to', '', 'callback__callback_voucher_valid_to');

            //  Voucher Type specific rules
            switch ($this->input->post('type')) {

                case 'LIMITED_USE':

                    $this->form_validation->set_rules('limited_use_limit', '', 'required|is_natural_no_zero');
                    $this->form_validation->set_rules('discount_type', '', 'required|callback__callback_voucher_valid_discount_type');
                    $this->form_validation->set_rules('discount_application', '', 'required|callback__callback_voucher_valid_discount_application');

                    $this->form_validation->set_message('is_natural_no_zero', 'Only positive integers are valid.');
                    break;

                case 'NORMAL':
                default:

                    $this->form_validation->set_rules('discount_type', '', 'required|callback__callback_voucher_valid_discount_type');
                    $this->form_validation->set_rules('discount_application', '', 'required|callback__callback_voucher_valid_discount_application');
                    break;

                case 'GIFT_CARD':

                    //  Quick hack
                    $POST['discount_type']        = 'AMOUNT';
                    $POST['discount_application'] = 'ALL';
                    break;
            }

            //  Discount Type specific rules
            switch ($this->input->post('discount_type')) {

                case 'PERCENTAGE':

                    $this->form_validation->set_rules('discount_value', '', 'required|is_natural_no_zero|greater_than[0]|less_than[101]');

                    $this->form_validation->set_message('is_natural_no_zero', 'Only positive integers are valid.');
                    $this->form_validation->set_message('greater_than', 'Must be in the range 1-100');
                    $this->form_validation->set_message('less_than', 'Must be in the range 1-100');
                    break;

                case 'AMOUNT':

                    $this->form_validation->set_rules('discount_value', '', 'required|numeric|greater_than[0]');

                    $this->form_validation->set_message('greater_than', 'Must be greater than 0');
                    break;

                default:

                    //  No specific rules
                    break;
            }

            //  Discount application specific rules
            switch ($this->input->post('discount_application')) {

                case 'PRODUCT_TYPES':

                    $this->form_validation->set_rules('product_type_id', '', 'required|callback__callback_voucher_valid_product_type');

                    $this->form_validation->set_message('greater_than', 'Must be greater than 0');
                    break;

                case 'PRODUCTS':
                case 'SHIPPING':
                case 'ALL':
                default:

                    //  No specific rules
                    break;
            }

            $this->form_validation->set_message('required',         lang('fv_required'));
            $this->form_validation->set_message('is_unique', 'Code already in use.');


            if ($this->form_validation->run($this)) {

                //  Prepare the $data variable
                $data = array();

                $data['type']                 = $this->input->post('type');
                $data['code']                 = strtoupper($this->input->post('code'));
                $data['discount_type']        = $this->input->post('discount_type');
                $data['discount_value']       = $this->input->post('discount_value');
                $data['discount_application'] = $this->input->post('discount_application');
                $data['label']                = $this->input->post('label');
                $data['valid_from']           = $this->input->post('valid_from');
                $data['is_active']            = true;

                if ($this->input->post('valid_to')) {

                    $data['valid_to'] = $this->input->post('valid_to');

                }

                //  Define specifics
                if ($this->input->post('type') == 'GIFT_CARD') {

                    $data['gift_card_balance']    = $this->input->post('discount_value');
                    $data['discount_type']        = 'AMOUNT';
                    $data['discount_application'] = 'ALL';

                }

                if ($this->input->post('type') == 'LIMITED_USE') {

                    $data['limited_use_limit'] = $this->input->post('limited_use_limit');

                }

                if ($this->input->post('discount_application') == 'PRODUCT_TYPES') {

                    $data['product_type_id'] = $this->input->post('product_type_id');

                }

                // --------------------------------------------------------------------------

                //  Attempt to create
                if ($this->shop_voucher_model->create($data)) {

                    $this->session->set_flashdata('success', '<strong>Success!</strong> Voucher "' . $data['code'] . '" was created successfully.');
                    redirect('admin/shop/vouchers');

                } else {

                    $this->data['error'] = '<strong>Sorry,</strong> there was a problem creating the voucher. '  . $this->shop_voucher_model->last_error();

                                }
            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Create Voucher';

        // --------------------------------------------------------------------------

        //  Fetch data
        $this->data['product_types'] = $this->shop_product_type_model->get_all_flat();

        // --------------------------------------------------------------------------

        //  Load assets
        $this->asset->load('nails.admin.shop.vouchers.min.js', true);

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/vouchers/create', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Activate a voucher
     * @return void
     */
    protected function _vouchers_activate()
    {
        if (!user_has_permission('admin.shop:0.vouchers_activate')) {

            $status  = 'error';
            $message = '<strong>Sorry,</strong> you do not have permission to activate vouchers.';

        } else {

            $id = $this->uri->segment(5);

            if ($this->shop_voucher_model->activate($id)) {

                $status  = 'success';
                $message = '<strong>Success!</strong> Voucher was activated successfully.';

            } else {

                $status   = 'error';
                $message  = '<strong>Sorry,</strong> There was a problem activating the voucher. ';
                $message .= $this->shop_voucher_model->last_error();
            }
        }

        $this->session->set_flashdata($status, $message);

        redirect('admin/shop/vouchers');
    }

    // --------------------------------------------------------------------------

    /**
     * Deactivate a voucher
     * @return void
     */
    protected function _vouchers_deactivate()
    {
        if (!user_has_permission('admin.shop:0.vouchers_deactivate')) {

            $status  = 'error';
            $message = '<strong>Sorry,</strong> you do not have permission to suspend vouchers.';

        } else {

            $id = $this->uri->segment(5);

            if ($this->shop_voucher_model->suspend($id)) {

                $status  = 'success';
                $message = '<strong>Success!</strong> Voucher was suspended successfully.';

            } else {

                $status   = 'error';
                $message  = '<strong>Sorry,</strong> There was a problem suspending the voucher. ';
                $message .= $this->shop_voucher_model->last_error();
            }
        }

        $this->session->set_flashdata($status, $message);

        redirect('admin/shop/vouchers');
    }

    // --------------------------------------------------------------------------

    /**
     * Manage shop sales
     * @return void
     */
    public function sales()
    {
        if (!user_has_permission('admin.shop:0.sale_manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $method = $this->uri->segment(4) ? $this->uri->segment(4) : 'index';

        if (method_exists($this, '_sales_' . $method)) {

            $this->{'_sales_' . $method}();

        } else {

            show_404();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Browse sales
     * @return void
     */
    protected function _sales_index()
    {
        $this->data['page']->title = 'Manage Sales';

        // --------------------------------------------------------------------------

        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/sales/index', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Create a new sale
     * @return void
     */
    protected function _sales_create()
    {
        $this->data['page']->title = 'Create Sale';

        // --------------------------------------------------------------------------

        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/sales/edit', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a sale
     * @return void
     */
    protected function _sales_edit()
    {
        $this->data['page']->title = 'Edit Sale "xxx"';

        // --------------------------------------------------------------------------

        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/sales/edit', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a sale
     * @return void
     */
    protected function _sales_delete()
    {
        $this->session->set_flashdata('message', '<strong>TODO:</strong> Delete a sale.');
    }

    // --------------------------------------------------------------------------

    /**
     * Manage other managers
     * @return void
     */
    public function manage()
    {
        $method = $this->uri->segment(4) ? $this->uri->segment(4) : 'index';

        if (method_exists($this, '_manage_' . $method)) {

            //  Is fancybox?
            $this->data['is_fancybox']  = $this->input->get('is_fancybox') ? '?is_fancybox=1' : '';

            //  Override the header and footer
            if ($this->data['is_fancybox']) {

                $this->data['headerOverride'] = 'structure/headerBlank';
                $this->data['footerOverride'] = 'structure/footerBlank';
            }

            //  Start the page title
            $this->data['page']->title = 'Manage &rsaquo; ';

            //  Call method
            $this->{'_manage_' . $method}();

        } else {

            show_404();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Browse other shop managers
     * @return void
     */
    protected function _manage_index()
    {
        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/manage/index', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Manage product attributes
     * @return void
     */
    protected function _manage_attribute()
    {
        if (!user_has_permission('admin.shop:0.attribute_manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load model
        $this->load->model('shop/shop_attribute_model');

        $method = $this->uri->segment(5) ? $this->uri->segment(5) : 'index';

        if (method_exists($this, '_manage_attribute_' . $method)) {

            //  Extend the title
            $this->data['page']->title .= 'Attributes ';

            $this->{'_manage_attribute_' . $method}();

        } else {

            show_404();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Browse product attributes
     * @return void
     */
    protected function _manage_attribute_index()
    {
        //  Fetch data
        $data = array('include_count' => true);
        $this->data['attributes'] = $this->shop_attribute_model->get_all(null, null, $data);

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/manage/attribute/index', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Create a new product attribute
     * @return void
     */
    protected function _manage_attribute_create()
    {
        if (!user_has_permission('admin.shop:0.attribute_create')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->load->library('form_validation');

            $this->form_validation->set_rules('label', '', 'xss_clean|required');
            $this->form_validation->set_rules('description', '', 'xss_clean');

            $this->form_validation->set_message('required', lang('fv_required'));

            if ($this->form_validation->run()) {

                $data               = new stdClass();
                $data->label        = $this->input->post('label');
                $data->description  = $this->input->post('description');

                if ($this->shop_attribute_model->create($data)) {

                    $this->session->set_flashdata('success', '<strong>Success!</strong> Attribute created successfully.');
                    redirect('admin/shop/manage/attribute' . $this->data['is_fancybox']);

                } else {

                    $this->data['error'] = '<strong>Sorry,</strong> there was a problem creating the Attribute. ' . $this->shop_category_model->last_error();

                                }
            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Page data
        $this->data['page']->title .= '&rsaquo; Create';

        // --------------------------------------------------------------------------

        //  Fetch data
        $this->data['attributes'] = $this->shop_attribute_model->get_all();

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/manage/attribute/edit', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a product attribute
     * @return void
     */
    protected function _manage_attribute_edit()
    {
        if (!user_has_permission('admin.shop:0.attribute_edit')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $this->data['attribute'] = $this->shop_attribute_model->get_by_id($this->uri->segment(6));

        if (empty($this->data['attribute'])) {

            show_404();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->load->library('form_validation');

            $this->form_validation->set_rules('label', '', 'xss_clean|required');
            $this->form_validation->set_rules('description', '', 'xss_clean');

            $this->form_validation->set_message('required', lang('fv_required'));

            if ($this->form_validation->run()) {

                $data               = new stdClass();
                $data->label        = $this->input->post('label');
                $data->description  = $this->input->post('description');

                if ($this->shop_attribute_model->update($this->data['attribute']->id, $data)) {

                    $this->session->set_flashdata('success', '<strong>Success!</strong> Attribute saved successfully.');
                    redirect('admin/shop/manage/attribute' . $this->data['is_fancybox']);

                } else {

                    $this->data['error'] = '<strong>Sorry,</strong> there was a problem saving the Attribute. ' . $this->shop_attribute_model->last_error();

                                }
            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Page data
        $this->data['page']->title .= 'Edit &rsaquo; ' . $this->data['attribute']->label;

        // --------------------------------------------------------------------------

        //  Fetch data
        $this->data['attributes'] = $this->shop_attribute_model->get_all();

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/manage/attribute/edit', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a product attribute
     * @return void
     */
    protected function _manage_attribute_delete()
    {
        if (!user_has_permission('admin.shop:0.attribute_delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id = $this->uri->segment(6);

        if ($this->shop_attribute_model->delete($id)) {

            $this->session->set_flashdata('success', '<strong>Success!</strong> Attribute was deleted successfully.');

        } else {

            $this->session->set_flashdata('error', '<strong>Sorry,</strong> there was a problem deleting the Attribute. ' . $this->shop_attribute_model->last_error());
        }

        redirect('admin/shop/manage/attribute' . $this->data['is_fancybox']);
    }

    // --------------------------------------------------------------------------

    /**
     * Manage product  brands
     * @return void
     */
    protected function _manage_brand()
    {
        if (!user_has_permission('admin.shop:0.brand_manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load model
        $this->load->model('shop/shop_brand_model');

        $method = $this->uri->segment(5) ? $this->uri->segment(5) : 'index';

        if (method_exists($this, '_manage_brand_' . $method)) {

            //  Extend the title
            $this->data['page']->title .= 'Brands ';

            $this->{'_manage_brand_' . $method}();

        } else {

            show_404();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Browse product brands
     * @return void
     */
    protected function _manage_brand_index()
    {
        //  Fetch data
        $data = array('include_count' => true, 'only_active' => false);
        $this->data['brands'] = $this->shop_brand_model->get_all(null, null, $data);

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/manage/brand/index', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Create a product brand
     * @return void
     */
    protected function _manage_brand_create()
    {
        if (!user_has_permission('admin.shop:0.brand_create')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->load->library('form_validation');

            $this->form_validation->set_rules('label', '', 'xss_clean|required');
            $this->form_validation->set_rules('logo_id', '', 'xss_clean');
            $this->form_validation->set_rules('cover_id', '', 'xss_clean');
            $this->form_validation->set_rules('description', '', 'xss_clean');
            $this->form_validation->set_rules('is_active', '', 'xss_clean');
            $this->form_validation->set_rules('seo_title', '', 'xss_clean|max_length[150]');
            $this->form_validation->set_rules('seo_description', '', 'xss_clean|max_length[300]');
            $this->form_validation->set_rules('seo_keywords', '', 'xss_clean|max_length[150]');

            $this->form_validation->set_message('required', lang('fv_required'));
            $this->form_validation->set_message('max_length',   lang('fv_max_length'));

            if ($this->form_validation->run()) {

                $data                   = new stdClass();
                $data->label            = $this->input->post('label');
                $data->logo_id          = (int) $this->input->post('logo_id') ? (int) $this->input->post('logo_id') : null;
                $data->cover_id     = (int) $this->input->post('cover_id') ? (int) $this->input->post('cover_id') : null;
                $data->description      = $this->input->post('description');
                $data->is_active        = (bool) $this->input->post('is_active');
                $data->seo_title        = $this->input->post('seo_title');
                $data->seo_description  = $this->input->post('seo_description');
                $data->seo_keywords = $this->input->post('seo_keywords');

                if ($this->shop_brand_model->create($data)) {

                    //  Redirect to clear form
                    $this->session->set_flashdata('success', '<strong>Success!</strong> Brand created successfully.');
                    redirect('admin/shop/manage/brand' . $this->data['is_fancybox']);

                } else {

                    $this->data['error'] = '<strong>Sorry,</strong> there was a problem creating the Brand. ' . $this->shop_brand_model->last_error();

                                }
            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Page data
        $this->data['page']->title .= '&rsaquo; Create';

        // --------------------------------------------------------------------------

        //  Fetch data
        $this->data['brands'] = $this->shop_brand_model->get_all();

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/manage/brand/edit', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a product brand
     * @return void
     */
    protected function _manage_brand_edit()
    {
        if (!user_has_permission('admin.shop:0.brand_edit')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $this->data['brand'] = $this->shop_brand_model->get_by_id($this->uri->segment(6));

        if (empty($this->data['brand'])) {

            show_404();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->load->library('form_validation');

            $this->form_validation->set_rules('label', '', 'xss_clean|required');
            $this->form_validation->set_rules('logo_id', '', 'xss_clean');
            $this->form_validation->set_rules('cover_id', '', 'xss_clean');
            $this->form_validation->set_rules('description', '', 'xss_clean');
            $this->form_validation->set_rules('is_active', '', 'xss_clean');
            $this->form_validation->set_rules('seo_title', '', 'xss_clean|max_length[150]');
            $this->form_validation->set_rules('seo_description', '', 'xss_clean|max_length[300]');
            $this->form_validation->set_rules('seo_keywords', '', 'xss_clean|max_length[150]');

            $this->form_validation->set_message('required', lang('fv_required'));
            $this->form_validation->set_message('max_length',   lang('fv_max_length'));

            if ($this->form_validation->run()) {

                $data                   = new stdClass();
                $data->label            = $this->input->post('label');
                $data->logo_id          = (int) $this->input->post('logo_id') ? (int) $this->input->post('logo_id') : null;
                $data->cover_id     = (int) $this->input->post('cover_id') ? (int) $this->input->post('cover_id') : null;
                $data->description      = $this->input->post('description');
                $data->is_active        = (bool) $this->input->post('is_active');
                $data->seo_title        = $this->input->post('seo_title');
                $data->seo_description  = $this->input->post('seo_description');
                $data->seo_keywords = $this->input->post('seo_keywords');

                if ($this->shop_brand_model->update($this->data['brand']->id, $data)) {

                    $this->session->set_flashdata('success', '<strong>Success!</strong> Brand saved successfully.');
                    redirect('admin/shop/manage/brand' . $this->data['is_fancybox']);

                } else {

                    $this->data['error'] = '<strong>Sorry,</strong> there was a problem saving the Brand. ' . $this->shop_brand_model->last_error();

                                }
            } else {

                $this->data['error'] = '<strong>Sorry,</strong> there was a problem saving the Brand.';
            }
        }

        // --------------------------------------------------------------------------

        //  Page data
        $this->data['page']->title .= 'Edit &rsaquo; ' . $this->data['brand']->label;

        // --------------------------------------------------------------------------

        //  Fetch data
        $this->data['brands'] = $this->shop_brand_model->get_all();

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/manage/brand/edit', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a product brand
     * @return void
     */
    protected function _manage_brand_delete()
    {
        if (!user_has_permission('admin.shop:0.brand_delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id = $this->uri->segment(6);

        if ($this->shop_brand_model->delete($id)) {

            $this->session->set_flashdata('success', '<strong>Success!</strong> Brand was deleted successfully.');

        } else {

            $this->session->set_flashdata('error', '<strong>Sorry,</strong> there was a problem deleting the Brand. ' . $this->shop_brand_model->last_error());
        }

        redirect('admin/shop/manage/brand' . $this->data['is_fancybox']);
    }

    // --------------------------------------------------------------------------

    /**
     * Manage product categories
     * @return void
     */
    protected function _manage_category()
    {
        if (!user_has_permission('admin.shop:0.category_manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load model
        $this->load->model('shop/shop_category_model');

        $method = $this->uri->segment(5) ? $this->uri->segment(5) : 'index';

        if (method_exists($this, '_manage_category_' . $method)) {

            //  Extend the title
            $this->data['page']->title .= 'Categories ';

            $this->{'_manage_category_' . $method}();

        } else {

            show_404();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Browse product categories
     * @return void
     */
    protected function _manage_category_index()
    {
        //  Fetch data
        $data = array('include_count' => true);
        $this->data['categories'] = $this->shop_category_model->get_all(null, null, $data);

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/manage/category/index', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Create a product category
     * @return void
     */
    protected function _manage_category_create()
    {
        if (!user_has_permission('admin.shop:0.category_create')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->load->library('form_validation');

            $this->form_validation->set_rules('label', '', 'xss_clean|required');
            $this->form_validation->set_rules('parent_id', '', 'xss_clean');
            $this->form_validation->set_rules('cover_id', '', 'xss_clean');
            $this->form_validation->set_rules('description', '', 'xss_clean');
            $this->form_validation->set_rules('seo_title', '', 'xss_clean|max_length[150]');
            $this->form_validation->set_rules('seo_description', '', 'xss_clean|max_length[300]');
            $this->form_validation->set_rules('seo_keywords', '', 'xss_clean|max_length[150]');

            $this->form_validation->set_message('required', lang('fv_required'));
            $this->form_validation->set_message('max_length',   lang('fv_max_length'));

            if ($this->form_validation->run()) {

                $data                   = new stdClass();
                $data->label            = $this->input->post('label');
                $data->parent_id        = $this->input->post('parent_id');
                $data->cover_id     = $this->input->post('cover_id') ? (int) $this->input->post('cover_id') : null;
                $data->description      = $this->input->post('description');
                $data->seo_title        = $this->input->post('seo_title');
                $data->seo_description  = $this->input->post('seo_description');
                $data->seo_keywords = $this->input->post('seo_keywords');

                if ($this->shop_category_model->create($data)) {

                    $this->session->set_flashdata('success', '<strong>Success!</strong> Category created successfully.');
                    redirect('admin/shop/manage/category' . $this->data['is_fancybox']);

                } else {

                    $this->data['error'] = '<strong>Sorry,</strong> there was a problem creating the Category. ' . $this->shop_category_model->last_error();

                                }
            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Page data
        $this->data['page']->title .= '&rsaquo; Create';

        // --------------------------------------------------------------------------

        //  Fetch data
        $this->data['categories'] = $this->shop_category_model->get_all();

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/manage/category/edit', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a product category
     * @return void
     */
    protected function _manage_category_edit()
    {
        if (!user_has_permission('admin.shop:0.category_edit')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $this->data['category'] = $this->shop_category_model->get_by_id($this->uri->segment(6));

        if (empty($this->data['category'])) {

            show_404();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->load->library('form_validation');

            $this->form_validation->set_rules('label', '', 'xss_clean|required');
            $this->form_validation->set_rules('parent_id', '', 'xss_clean');
            $this->form_validation->set_rules('cover_id', '', 'xss_clean');
            $this->form_validation->set_rules('description', '', 'xss_clean');
            $this->form_validation->set_rules('seo_title', '', 'xss_clean|max_length[150]');
            $this->form_validation->set_rules('seo_description', '', 'xss_clean|max_length[300]');
            $this->form_validation->set_rules('seo_keywords', '', 'xss_clean|max_length[150]');

            $this->form_validation->set_message('required', lang('fv_required'));
            $this->form_validation->set_message('max_length',   lang('fv_max_length'));

            if ($this->form_validation->run()) {

                $data                   = new stdClass();
                $data->label            = $this->input->post('label');
                $data->parent_id        = $this->input->post('parent_id');
                $data->cover_id     = $this->input->post('cover_id') ? (int) $this->input->post('cover_id') : null;
                $data->description      = $this->input->post('description');
                $data->seo_title        = $this->input->post('seo_title');
                $data->seo_description  = $this->input->post('seo_description');
                $data->seo_keywords = $this->input->post('seo_keywords');

                if ($this->shop_category_model->update($this->data['category']->id, $data)) {

                    $this->session->set_flashdata('success', '<strong>Success!</strong> Category saved successfully.');
                    redirect('admin/shop/manage/category' . $this->data['is_fancybox']);

                } else {

                    $this->data['error'] = '<strong>Sorry,</strong> there was a problem saving the Category. ' . $this->shop_category_model->last_error();

                                }
            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Page data
        $this->data['page']->title = 'Edit &rsaquo; ' . $this->data['category']->label;

        // --------------------------------------------------------------------------

        //  Fetch data
        $this->data['categories'] = $this->shop_category_model->get_all();

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/manage/category/edit', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a product category
     * @return void
     */
    protected function _manage_category_delete()
    {
        if (!user_has_permission('admin.shop:0.category_delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id = $this->uri->segment(6);

        if ($this->shop_category_model->delete($id)) {

            $this->session->set_flashdata('success', '<strong>Success!</strong> Category was deleted successfully.');

        } else {

            $this->session->set_flashdata('error', '<strong>Sorry,</strong> there was a problem deleting the Category. ' . $this->shop_category_model->last_error());
        }

        redirect('admin/shop/manage/category' . $this->data['is_fancybox']);
    }

    // --------------------------------------------------------------------------

    /**
     * Manage product collections
     * @return void
     */
    protected function _manage_collection()
    {
        if (!user_has_permission('admin.shop:0.collection_manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load model
        $this->load->model('shop/shop_collection_model');

        $method = $this->uri->segment(5) ? $this->uri->segment(5) : 'index';

        if (method_exists($this, '_manage_collection_' . $method)) {

            //  Extend the title
            $this->data['page']->title .= 'Collections ';

            $this->{'_manage_collection_' . $method}();

        } else {

            show_404();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Browse product collections
     * @return void
     */
    protected function _manage_collection_index()
    {
        //  Fetch data
        $data = array('include_count' => true, 'only_active' => false);
        $this->data['collections'] = $this->shop_collection_model->get_all(null, null, $data);

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/manage/collection/index', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Create a product collection
     * @return void
     */
    protected function _manage_collection_create()
    {
        if (!user_has_permission('admin.shop:0.collection_create')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->load->library('form_validation');

            $this->form_validation->set_rules('label', '', 'xss_clean|required');
            $this->form_validation->set_rules('cover_id', '', 'xss_clean');
            $this->form_validation->set_rules('description', '', 'xss_clean');
            $this->form_validation->set_rules('is_active', '', 'xss_clean');
            $this->form_validation->set_rules('seo_title', '', 'xss_clean|max_length[150]');
            $this->form_validation->set_rules('seo_description', '', 'xss_clean|max_length[300]');
            $this->form_validation->set_rules('seo_keywords', '', 'xss_clean|max_length[150]');

            $this->form_validation->set_message('required', lang('fv_required'));
            $this->form_validation->set_message('max_length',   lang('fv_max_length'));

            if ($this->form_validation->run()) {

                $data                   = new stdClass();
                $data->label            = $this->input->post('label');
                $data->cover_id     = $this->input->post('cover_id') ? (int) $this->input->post('cover_id') : null;
                $data->description      = $this->input->post('description');
                $data->seo_title        = $this->input->post('seo_title');
                $data->seo_description  = $this->input->post('seo_description');
                $data->seo_keywords = $this->input->post('seo_keywords');
                $data->is_active        = (bool) $this->input->post('is_active');

                if ($this->shop_collection_model->create($data)) {

                    $this->session->set_flashdata('success', '<strong>Success!</strong> Collection created successfully.');
                    redirect('admin/shop/manage/collection' . $this->data['is_fancybox']);

                } else {

                    $this->data['error'] = '<strong>Sorry,</strong> there was a problem creating the Collection. ' . $this->shop_collection_model->last_error();

                                }
            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Page data
        $this->data['page']->title .= '&rsaquo; Create';

        // --------------------------------------------------------------------------

        //  Fetch data
        $this->data['collections'] = $this->shop_collection_model->get_all();

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/manage/collection/edit', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a product collection
     * @return void
     */
    protected function _manage_collection_edit()
    {
        if (!user_has_permission('admin.shop:0.collection_edit')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $this->data['collection'] = $this->shop_collection_model->get_by_id($this->uri->segment(6));

        if (empty($this->data['collection'])) {

            show_404();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->load->library('form_validation');

            $this->form_validation->set_rules('label', '', 'xss_clean|required');
            $this->form_validation->set_rules('cover_id', '', 'xss_clean');
            $this->form_validation->set_rules('description', '', 'xss_clean');
            $this->form_validation->set_rules('is_active', '', 'xss_clean');
            $this->form_validation->set_rules('seo_title', '', 'xss_clean|max_length[150]');
            $this->form_validation->set_rules('seo_description', '', 'xss_clean|max_length[300]');
            $this->form_validation->set_rules('seo_keywords', '', 'xss_clean|max_length[150]');

            $this->form_validation->set_message('required', lang('fv_required'));
            $this->form_validation->set_message('max_length',   lang('fv_max_length'));

            if ($this->form_validation->run()) {

                $data                   = new stdClass();
                $data->label            = $this->input->post('label');
                $data->cover_id     = $this->input->post('cover_id') ? (int) $this->input->post('cover_id') : null;
                $data->description      = $this->input->post('description');
                $data->seo_title        = $this->input->post('seo_title');
                $data->seo_description  = $this->input->post('seo_description');
                $data->seo_keywords = $this->input->post('seo_keywords');
                $data->is_active        = (bool) $this->input->post('is_active');

                if ($this->shop_collection_model->update($this->data['collection']->id, $data)) {

                    $this->session->set_flashdata('success', '<strong>Success!</strong> Collection saved successfully.');
                    redirect('admin/shop/manage/collection' . $this->data['is_fancybox']);

                } else {

                    $this->data['error'] = '<strong>Sorry,</strong> there was a problem saving the Collection. ' . $this->shop_collection_model->last_error();

                                }
            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Page data
        $this->data['page']->title .= 'Edit &rsaquo; ' . $this->data['collection']->label;

        // --------------------------------------------------------------------------

        //  Fetch data
        $this->data['collections'] = $this->shop_collection_model->get_all();

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/manage/collection/edit', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a product collection
     * @return void
     */
    protected function _manage_collection_delete()
    {
        if (!user_has_permission('admin.shop:0.collection_delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id = $this->uri->segment(6);

        if ($this->shop_collection_model->delete($id)) {

            $this->session->set_flashdata('success', '<strong>Success!</strong> Collection was deleted successfully.');

        } else {

            $this->session->set_flashdata('error', '<strong>Sorry,</strong> there was a problem deleting the Collection. ' . $this->shop_collection_model->last_error());
        }

        redirect('admin/shop/manage/collection' . $this->data['is_fancybox']);
    }

    // --------------------------------------------------------------------------

    /**
     * Manage product ranges
     * @return void
     */
    protected function _manage_range()
    {
        if (!user_has_permission('admin.shop:0.range_manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load model
        $this->load->model('shop/shop_range_model');

        $method = $this->uri->segment(5) ? $this->uri->segment(5) : 'index';

        if (method_exists($this, '_manage_range_' . $method)) {

            //  Extend the title
            $this->data['page']->title .= 'Ranges ';

            $this->{'_manage_range_' . $method}();

        } else {

            show_404();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Browse product ranges
     * @return void
     */
    protected function _manage_range_index()
    {
        //  Fetch data
        $data = array('include_count' => true);
        $this->data['ranges'] = $this->shop_range_model->get_all(null, null, $data);

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/manage/range/index', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Create a product range
     * @return void
     */
    protected function _manage_range_create()
    {
        if (!user_has_permission('admin.shop:0.range_create')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->load->library('form_validation');

            $this->form_validation->set_rules('label', '', 'xss_clean|required');
            $this->form_validation->set_rules('cover_id', '', 'xss_clean');
            $this->form_validation->set_rules('description', '', 'xss_clean');
            $this->form_validation->set_rules('is_active', '', 'xss_clean');
            $this->form_validation->set_rules('seo_title', '', 'xss_clean|max_length[150]');
            $this->form_validation->set_rules('seo_description', '', 'xss_clean|max_length[300]');
            $this->form_validation->set_rules('seo_keywords', '', 'xss_clean|max_length[150]');

            $this->form_validation->set_message('required', lang('fv_required'));
            $this->form_validation->set_message('max_length',   lang('fv_max_length'));

            if ($this->form_validation->run()) {

                $data                   = new stdClass();
                $data->label            = $this->input->post('label');
                $data->cover_id     = $this->input->post('cover_id') ? (int) $this->input->post('cover_id') : null;
                $data->description      = $this->input->post('description');
                $data->seo_title        = $this->input->post('seo_title');
                $data->seo_description  = $this->input->post('seo_description');
                $data->seo_keywords = $this->input->post('seo_keywords');
                $data->is_active        = (bool) $this->input->post('is_active');

                if ($this->shop_range_model->create($data)) {

                    $this->session->set_flashdata('success', '<strong>Success!</strong> Range created successfully.');
                    redirect('admin/shop/manage/range' . $this->data['is_fancybox']);

                } else {

                    $this->data['error'] = '<strong>Sorry,</strong> there was a problem creating the Range. ' . $this->shop_range_model->last_error();

                                }
            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Page data
        $this->data['page']->title .= '&rsaquo; Create';

        // --------------------------------------------------------------------------

        //  Fetch data
        $this->data['ranges'] = $this->shop_range_model->get_all();

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/manage/range/edit', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a product range
     * @return void
     */
    protected function _manage_range_edit()
    {
        if (!user_has_permission('admin.shop:0.range_edit')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $this->data['range'] = $this->shop_range_model->get_by_id($this->uri->segment(6));

        if (empty($this->data['range'])) {

            show_404();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->load->library('form_validation');

            $this->form_validation->set_rules('label', '', 'xss_clean|required');
            $this->form_validation->set_rules('cover_id', '', 'xss_clean');
            $this->form_validation->set_rules('description', '', 'xss_clean');
            $this->form_validation->set_rules('is_active', '', 'xss_clean');
            $this->form_validation->set_rules('seo_title', '', 'xss_clean|max_length[150]');
            $this->form_validation->set_rules('seo_description', '', 'xss_clean|max_length[300]');
            $this->form_validation->set_rules('seo_keywords', '', 'xss_clean|max_length[150]');

            $this->form_validation->set_message('required', lang('fv_required'));
            $this->form_validation->set_message('max_length',   lang('fv_max_length'));

            if ($this->form_validation->run()) {

                $data                   = new stdClass();
                $data->label            = $this->input->post('label');
                $data->cover_id     = $this->input->post('cover_id') ? (int) $this->input->post('cover_id') : null;
                $data->description      = $this->input->post('description');
                $data->seo_title        = $this->input->post('seo_title');
                $data->seo_description  = $this->input->post('seo_description');
                $data->seo_keywords = $this->input->post('seo_keywords');
                $data->is_active        = (bool) $this->input->post('is_active');

                if ($this->shop_range_model->update($this->data['range']->id, $data)) {

                    $this->session->set_flashdata('success', '<strong>Success!</strong> Range saved successfully.');
                    redirect('admin/shop/manage/range' . $this->data['is_fancybox']);

                } else {

                    $this->data['error'] = '<strong>Sorry,</strong> there was a problem saving the Range. ' . $this->shop_range_model->last_error();

                                }
            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Page data
        $this->data['page']->title .= 'Edit &rsaquo; ' . $this->data['range']->label;

        // --------------------------------------------------------------------------

        //  Fetch data
        $this->data['ranges'] = $this->shop_range_model->get_all();

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/manage/range/edit', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a product range
     * @return void
     */
    protected function _manage_range_delete()
    {
        if (!user_has_permission('admin.shop:0.range_delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id = $this->uri->segment(6);

        if ($this->shop_range_model->delete($id)) {

            $this->session->set_flashdata('success', '<strong>Success!</strong> Range was deleted successfully.');

        } else {

            $this->session->set_flashdata('error', '<strong>Sorry,</strong> there was a problem deleting the Range. ' . $this->shop_range_model->last_error());
        }

        redirect('admin/shop/manage/range' . $this->data['is_fancybox']);
    }

    // --------------------------------------------------------------------------

    /**
     * Manage product tags
     * @return void
     */
    protected function _manage_tag()
    {
        if (!user_has_permission('admin.shop:0.tag_manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load model
        $this->load->model('shop/shop_tag_model');

        $method = $this->uri->segment(5) ? $this->uri->segment(5) : 'index';

        if (method_exists($this, '_manage_tag_' . $method)) {

            //  Extend the title
            $this->data['page']->title .= 'Tags ';

            $this->{'_manage_tag_' . $method}();

        } else {

            show_404();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Browse product tags
     * @return void
     */
    protected function _manage_tag_index()
    {
        //  Fetch data
        $data = array('include_count' => true);
        $this->data['tags'] = $this->shop_tag_model->get_all(null,null, $data);

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/manage/tag/index', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Create a product tag
     * @return void
     */
    protected function _manage_tag_create()
    {
        if (!user_has_permission('admin.shop:0.tag_create')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->load->library('form_validation');

            $this->form_validation->set_rules('label', '', 'xss_clean|required');
            $this->form_validation->set_rules('cover_id', '', 'xss_clean');
            $this->form_validation->set_rules('description', '', 'xss_clean');
            $this->form_validation->set_rules('seo_title', '', 'xss_clean|max_length[150]');
            $this->form_validation->set_rules('seo_description', '', 'xss_clean|max_length[300]');
            $this->form_validation->set_rules('seo_keywords', '', 'xss_clean|max_length[150]');

            $this->form_validation->set_message('required', lang('fv_required'));
            $this->form_validation->set_message('max_length',   lang('fv_max_length'));

            if ($this->form_validation->run()) {

                $data                   = new stdClass();
                $data->label            = $this->input->post('label');
                $data->cover_id     = $this->input->post('cover_id') ? (int) $this->input->post('cover_id') : null;
                $data->description      = $this->input->post('description');
                $data->seo_title        = $this->input->post('seo_title');
                $data->seo_description  = $this->input->post('seo_description');
                $data->seo_keywords = $this->input->post('seo_keywords');

                if ($this->shop_tag_model->create($data)) {

                    $this->session->set_flashdata('success', '<strong>Success!</strong> Tag created successfully.');
                    redirect('admin/shop/manage/tag' . $this->data['is_fancybox']);

                } else {

                    $this->data['error'] = '<strong>Sorry,</strong> there was a problem creating the Tag. ' . $this->shop_tag_model->last_error();

                                }
            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Page data
        $this->data['page']->title .= '&rsaquo; Create';

        // --------------------------------------------------------------------------

        //  Fetch data
        $this->data['tags'] = $this->shop_tag_model->get_all();

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/manage/tag/edit', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a product tag
     * @return void
     */
    protected function _manage_tag_edit()
    {
        if (!user_has_permission('admin.shop:0.tag_edit')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $this->data['tag'] = $this->shop_tag_model->get_by_id($this->uri->segment(6));

        if (empty($this->data['tag'])) {

            show_404();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->load->library('form_validation');

            $this->form_validation->set_rules('label', '', 'xss_clean|required');
            $this->form_validation->set_rules('cover_id', '', 'xss_clean');
            $this->form_validation->set_rules('description', '', 'xss_clean');
            $this->form_validation->set_rules('seo_title', '', 'xss_clean|max_length[150]');
            $this->form_validation->set_rules('seo_description', '', 'xss_clean|max_length[300]');
            $this->form_validation->set_rules('seo_keywords', '', 'xss_clean|max_length[150]');

            $this->form_validation->set_message('required', lang('fv_required'));
            $this->form_validation->set_message('max_length',   lang('fv_max_length'));

            if ($this->form_validation->run()) {

                $data                   = new stdClass();
                $data->label            = $this->input->post('label');
                $data->cover_id     = $this->input->post('cover_id') ? (int) $this->input->post('cover_id') : null;
                $data->description      = $this->input->post('description');
                $data->seo_title        = $this->input->post('seo_title');
                $data->seo_description  = $this->input->post('seo_description');
                $data->seo_keywords = $this->input->post('seo_keywords');

                if ($this->shop_tag_model->update($this->data['tag']->id, $data)) {

                    $this->session->set_flashdata('success', '<strong>Success!</strong> Tag saved successfully.');
                    redirect('admin/shop/manage/tag' . $this->data['is_fancybox']);

                } else {

                    $this->data['error'] = '<strong>Sorry,</strong> there was a problem saving the Tag. ' . $this->shop_tag_model->last_error();

                                }
            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Page data
        $this->data['page']->title .= 'Edit &rsaquo; ' . $this->data['tag']->label;

        // --------------------------------------------------------------------------

        //  Fetch data
        $this->data['tags'] = $this->shop_tag_model->get_all();

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/manage/tag/edit', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a product tag
     * @return void
     */
    protected function _manage_tag_delete()
    {
        if (!user_has_permission('admin.shop:0.tag_delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id = $this->uri->segment(6);

        if ($this->shop_tag_model->delete($id)) {

            $this->session->set_flashdata('success', '<strong>Success!</strong> Tag was deleted successfully.');

        } else {

            $this->session->set_flashdata('error', '<strong>Sorry,</strong> there was a problem deleting the Tag. ' . $this->shop_tag_model->last_error());
        }

        redirect('admin/shop/manage/tag' . $this->data['is_fancybox']);
    }

    // --------------------------------------------------------------------------

    /**
     * Manage product tax rates
     * @return void
     */
    protected function _manage_tax_rate()
    {
        if (!user_has_permission('admin.shop:0.tax_rate_manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load model
        $this->load->model('shop/shop_tax_rate_model');

        $method = $this->uri->segment(5) ? $this->uri->segment(5) : 'index';

        if (method_exists($this, '_manage_tax_rate_' . $method)) {

            //  Extend the title
            $this->data['page']->title .= 'Tax Rates ';

            $this->{'_manage_tax_rate_' . $method}();

        } else {

            show_404();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Browse product tax rates
     * @return void
     */
    protected function _manage_tax_rate_index()
    {
        //  Fetch data
        $data = array('include_count' => true);
        $this->data['tax_rates'] = $this->shop_tax_rate_model->get_all(null, null, $data);

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/manage/tax_rate/index', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Create a product tax rate
     * @return void
     */
    protected function _manage_tax_rate_create()
    {
        if (!user_has_permission('admin.shop:0.tax_rate_create')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->load->library('form_validation');

            $this->form_validation->set_rules('label', '', 'xss_clean|required');
            $this->form_validation->set_rules('rate', '', 'xss_clean|required|in_range[0-1]');

            $this->form_validation->set_message('required', lang('fv_required'));
            $this->form_validation->set_message('in_range', lang('fv_in_range'));

            if ($this->form_validation->run()) {

                $data           = new stdClass();
                $data->label    = $this->input->post('label');
                $data->rate = $this->input->post('rate');

                if ($this->shop_tax_rate_model->create($data)) {

                    $this->session->set_flashdata('success', '<strong>Success!</strong> Tax Rate created successfully.');
                    redirect('admin/shop/manage/tax_rate' . $this->data['is_fancybox']);

                } else {

                    $this->data['error'] = '<strong>Sorry,</strong> there was a problem creating the Tax Rate. ' . $this->shop_tax_rate_model->last_error();

                                }
            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Page data
        $this->data['page']->title .= '&rsaquo; Create';

        // --------------------------------------------------------------------------

        //  Fetch data
        $this->data['tax_rates'] = $this->shop_tax_rate_model->get_all();

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/manage/tax_rate/edit', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a product tax rate
     * @return void
     */
    protected function _manage_tax_rate_edit()
    {
        if (!user_has_permission('admin.shop:0.tax_rate_edit')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $this->data['tax_rate'] = $this->shop_tax_rate_model->get_by_id($this->uri->segment(6));

        if (empty($this->data['tax_rate'])) {

            show_404();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->load->library('form_validation');

            $this->form_validation->set_rules('label', '', 'xss_clean|required');
            $this->form_validation->set_rules('rate', '', 'xss_clean|required|in_range[0-1]');

            $this->form_validation->set_message('required', lang('fv_required'));
            $this->form_validation->set_message('in_range', lang('fv_in_range'));

            if ($this->form_validation->run()) {

                $data           = new stdClass();
                $data->label    = $this->input->post('label');
                $data->rate = (float) $this->input->post('rate');

                if ($this->shop_tax_rate_model->update($this->data['tax_rate']->id, $data)) {

                    $this->session->set_flashdata('success', '<strong>Success!</strong> Tax Rate saved successfully.');
                    redirect('admin/shop/manage/tax_rate' . $this->data['is_fancybox']);

                } else {

                    $this->data['error'] = '<strong>Sorry,</strong> there was a problem saving the Tax Rate. ' . $this->shop_tax_rate_model->last_error();

                                }
            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Page data
        $this->data['page']->title .= 'Edit &rsaquo; ' . $this->data['tax_rate']->label;

        // --------------------------------------------------------------------------

        //  Fetch data
        $this->data['tax_rates'] = $this->shop_tax_rate_model->get_all();

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/manage/tax_rate/edit', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a product tax rate
     * @return void
     */
    protected function _manage_tax_rate_delete()
    {
        if (!user_has_permission('admin.shop:0.tax_rate_delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id = $this->uri->segment(6);

        if ($this->shop_tax_rate_model->delete($id)) {

            $this->session->set_flashdata('success', '<strong>Success!</strong> Tax Rate was deleted successfully.');

        } else {

            $this->session->set_flashdata('error', '<strong>Sorry,</strong> there was a problem deleting the Tax Rate. ' . $this->shop_tax_rate_model->last_error());
        }

        redirect('admin/shop/manage/tax_rate' . $this->data['is_fancybox']);
    }

    // --------------------------------------------------------------------------

    /**
     * Manage product types
     * @return void
     */
    protected function _manage_product_type()
    {
        if (!user_has_permission('admin.shop:0.product_type_manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load model
        $this->load->model('shop/shop_product_type_model');

        $method = $this->uri->segment(5) ? $this->uri->segment(5) : 'index';

        if (method_exists($this, '_manage_product_type_' . $method)) {

            //  Extend the title
            $this->data['page']->title .= 'Product Types ';

            $this->{'_manage_product_type_' . $method}();

        } else {

            show_404();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Browse product types
     * @return void
     */
    protected function _manage_product_type_index()
    {
        //  Fetch data
        $data = array('include_count' => true);
        $this->data['product_types'] = $this->shop_product_type_model->get_all(null, null, $data);

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/manage/product_type/index', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Create a product type
     * @return void
     */
    protected function _manage_product_type_create()
    {
        if (!user_has_permission('admin.shop:0.product_type_create')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->load->library('form_validation');

            $this->form_validation->set_rules('label', '', 'xss_clean|required|is_unique[' . NAILS_DB_PREFIX . 'shop_product_type.label]');
            $this->form_validation->set_rules('description', '', 'xss_clean');
            $this->form_validation->set_rules('is_physical', '', 'xss_clean');
            $this->form_validation->set_rules('ipn_method', '', 'xss_clean');
            $this->form_validation->set_rules('max_per_order', '', 'xss_clean');
            $this->form_validation->set_rules('max_variations', '', 'xss_clean');

            $this->form_validation->set_message('required', lang('fv_required'));
            $this->form_validation->set_message('is_unique', lang('fv_is_unique'));

            if ($this->form_validation->run()) {

                $data                   = new stdClass();
                $data->label            = $this->input->post('label');
                $data->description      = $this->input->post('description');
                $data->is_physical      = (bool) $this->input->post('is_physical');
                $data->ipn_method       = $this->input->post('ipn_method');
                $data->max_per_order    = (int) $this->input->post('max_per_order');
                $data->max_variations   = (int) $this->input->post('max_variations');

                if ($this->shop_product_type_model->create($data)) {

                    //  Redirect to clear form
                    $this->session->set_flashdata('success', '<strong>Success!</strong> Product Type created successfully.');
                    redirect('admin/shop/manage/product_type' . $this->data['is_fancybox']);

                } else {

                    $this->data['error'] = '<strong>Sorry,</strong> there was a problem creating the Product Type. ' . $this->shop_product_model->last_error();

                                }
            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Page data
        $this->data['page']->title .= '&rsaquo; Create';

        // --------------------------------------------------------------------------

        //  Fetch data
        $this->data['product_types'] = $this->shop_product_type_model->get_all();

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/manage/product_type/edit', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a product type
     * @return void
     */
    protected function _manage_product_type_edit()
    {
        if (!user_has_permission('admin.shop:0.product_type_edit')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $this->data['product_type'] = $this->shop_product_type_model->get_by_id($this->uri->segment(6));

        if (empty($this->data['product_type'])) {

            show_404();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->load->library('form_validation');

            $this->form_validation->set_rules('label', '', 'xss_clean|required|unique_if_diff[' . NAILS_DB_PREFIX . 'shop_product_type.label.' . $this->input->post('label_old') . ']');
            $this->form_validation->set_rules('description', '', 'xss_clean');
            $this->form_validation->set_rules('is_physical', '', 'xss_clean');
            $this->form_validation->set_rules('ipn_method', '', 'xss_clean');
            $this->form_validation->set_rules('max_per_order', '', 'xss_clean');
            $this->form_validation->set_rules('max_variations', '', 'xss_clean');

            $this->form_validation->set_message('required', lang('fv_required'));

            if ($this->form_validation->run()) {

                $data                   = new stdClass();
                $data->label            = $this->input->post('label');
                $data->description      = $this->input->post('description');
                $data->is_physical      = (bool)$this->input->post('is_physical');
                $data->ipn_method       = $this->input->post('ipn_method');
                $data->max_per_order    = (int) $this->input->post('max_per_order');
                $data->max_variations   = (int) $this->input->post('max_variations');

                if ($this->shop_product_type_model->update($this->data['product_type']->id, $data)) {

                    $this->session->set_flashdata('success', '<strong>Success!</strong> Product Type saved successfully.');
                    redirect('admin/shop/product_type' . $this->data['is_fancybox']);

                } else {

                    $this->data['error'] = '<strong>Sorry,</strong> there was a problem saving the Product Type. ' . $this->shop_product_type_model->last_error();

                                }
            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Page data
        $this->data['page']->title .= 'Edit &rsaquo; ' . $this->data['product_type']->label;

        // --------------------------------------------------------------------------

        //  Fetch data
        $this->data['product_types'] = $this->shop_product_type_model->get_all();

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/manage/product_type/edit', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Manage product type meta data
     * @return void
     */
    protected function _manage_product_type_meta()
    {
        if (!user_has_permission('admin.shop:0.product_type_meta__manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load model
        $this->load->model('shop/shop_product_type_model');

        $method = $this->uri->segment(5) ? $this->uri->segment(5) : 'index';

        if (method_exists($this, '_manage_product_type_meta_' . $method)) {

            //  Extend the title
            $this->data['page']->title .= 'Product Type Meta ';

            $this->{'_manage_product_type_meta_' . $method}();

        } else {

            show_404();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Browse product type meta data
     * @return void
     */
    protected function _manage_product_type_meta_index()
    {
        //  Fetch data
        $data = array('include_associated_product_types' => true);
        $this->data['meta_fields'] = $this->shop_product_type_meta_model->get_all(null, null, $data);

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/manage/product_type_meta/index', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Create product type meta data
     * @return void
     */
    protected function _manage_product_type_meta_create()
    {
        if (!user_has_permission('admin.shop:0.product_type_meta_create')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->load->library('form_validation');

            $this->form_validation->set_rules('label', '', 'xss_clean|required');
            $this->form_validation->set_rules('admin_form_sub_label', '', 'xss_clean');
            $this->form_validation->set_rules('admin_form_placeholder', '', 'xss_clean');
            $this->form_validation->set_rules('admin_form_tip', '', 'xss_clean');
            $this->form_validation->set_rules('associated_product_types', '', 'xss_clean');
            $this->form_validation->set_rules('allow_multiple', '', 'xss_clean');
            $this->form_validation->set_rules('is_filter', '', 'xss_clean');

            $this->form_validation->set_message('required', lang('fv_required'));

            if ($this->form_validation->run()) {

                $data                               = new stdClass();
                $data->label                        = $this->input->post('label');
                $data->admin_form_sub_label     = $this->input->post('admin_form_sub_label');
                $data->admin_form_placeholder       = $this->input->post('admin_form_placeholder');
                $data->admin_form_tip               = $this->input->post('admin_form_tip');
                $data->associated_product_types = $this->input->post('associated_product_types');
                $data->allow_multiple               = (bool) $this->input->post('allow_multiple');
                $data->is_filter                    = (bool) $this->input->post('is_filter');

                if ($this->shop_product_type_meta_model->create($data)) {

                    $this->session->set_flashdata('success', '<strong>Success!</strong> Product Type Meta Field created successfully.');
                    redirect('admin/shop/manage/product_type_meta' . $this->data['is_fancybox']);

                } else {

                    $this->data['error'] = '<strong>Sorry,</strong> there was a problem creating the Product Type Meta Field. ' . $this->shop_product_type_meta_model->last_error();

                                }
            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Page data
        $this->data['page']->title .= '&rsaquo; Create';

        // --------------------------------------------------------------------------

        //  Fetch data
        $this->data['product_types']    = $this->shop_product_type_model->get_all();

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/manage/product_type_meta/edit', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Edit product type meta data
     * @return void
     */
    protected function _manage_product_type_meta_edit()
    {
        if (!user_has_permission('admin.shop:0.product_type_meta_edit')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $data = array('include_associated_product_types' => true);
        $this->data['meta_field'] = $this->shop_product_type_meta_model->get_by_id($this->uri->segment(6), $data);

        if (empty($this->data['meta_field'])) {

            show_404();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->load->library('form_validation');

            $this->form_validation->set_rules('label', '', 'xss_clean|required');
            $this->form_validation->set_rules('admin_form_sub_label', '', 'xss_clean');
            $this->form_validation->set_rules('admin_form_placeholder', '', 'xss_clean');
            $this->form_validation->set_rules('admin_form_tip', '', 'xss_clean');
            $this->form_validation->set_rules('associated_product_types', '', 'xss_clean');
            $this->form_validation->set_rules('allow_multiple', '', 'xss_clean');
            $this->form_validation->set_rules('is_filter', '', 'xss_clean');

            $this->form_validation->set_message('required', lang('fv_required'));

            if ($this->form_validation->run()) {

                $data                               = new stdClass();
                $data->label                        = $this->input->post('label');
                $data->admin_form_sub_label     = $this->input->post('admin_form_sub_label');
                $data->admin_form_placeholder       = $this->input->post('admin_form_placeholder');
                $data->admin_form_tip               = $this->input->post('admin_form_tip');
                $data->associated_product_types = $this->input->post('associated_product_types');
                $data->allow_multiple               = (bool) $this->input->post('allow_multiple');
                $data->is_filter                    = (bool) $this->input->post('is_filter');

                if ($this->shop_product_type_meta_model->update($this->data['meta_field']->id, $data)) {

                    $this->session->set_flashdata('success', '<strong>Success!</strong> Product Type Meta Field saved successfully.');
                    redirect('admin/shop/manage/product_type_meta' . $this->data['is_fancybox']);

                } else {

                    $this->data['error'] = '<strong>Sorry,</strong> there was a problem saving the Product Type Meta Field. ' . $this->shop_product_type_meta_model->last_error();

                                }
            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Page data
        $this->data['page']->title .= 'Edit &rsaquo; ' . $this->data['meta_field']->label;

        // --------------------------------------------------------------------------

        //  Fetch data
        $this->data['product_types']    = $this->shop_product_type_model->get_all();

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/manage/product_type_meta/edit', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Delete product type meta data
     * @return void
     */
    protected function _manage_product_type_meta_delete()
    {
        if (!user_has_permission('admin.shop:0.product_type_meta_delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id = $this->uri->segment(6);

        if ($this->shop_product_type_meta_model->delete($id)) {

            $this->session->set_flashdata('success', '<strong>Success!</strong> Product Type was deleted successfully.');

        } else {

            $this->session->set_flashdata('error', '<strong>Sorry,</strong> there was a problem deleting the Product Type. ' . $this->shop_product_type_model->last_error());
        }

        redirect('admin/shop/manage/product_type_meta' . $this->data['is_fancybox']);
    }

    // --------------------------------------------------------------------------

    /**
     * Manage reports
     * @return void
     */
    public function reports()
    {
        if (!user_has_permission('admin.shop:0.can_generate_reports')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Default report sources
        $this->reportSources = array();

        if (user_has_permission('admin.shop:0.inventory_manage')) {

            $this->reportSources[] = array('Inventory', 'Out of Stock variants', 'out_of_stock_variants');
            $this->reportSources[] = array('Sales', 'Product Sales', 'product_sales');
        }

        // --------------------------------------------------------------------------

        //  Default report formats
        $this->reportFormats      = array();
        $this->reportFormats[]    = array('CSV', 'Easily imports to many software packages, including Microsoft Excel.', 'csv');
        $this->reportFormats[]    = array('HTML', 'Produces an HTML table containing the data', 'html');
        $this->reportFormats[]    = array('PDF', 'Saves a PDF using the data from the HTML export option', 'pdf');
        $this->reportFormats[]    = array('PHP Serialize', 'Export as an object serialized using PHP\'s serialize() function', 'serialize');
        $this->reportFormats[]    = array('JSON', 'Export as a JSON array', 'json');

        // --------------------------------------------------------------------------

        $method = $this->uri->segment(4) ? $this->uri->segment(4) : 'index';

        if (method_exists($this, '_reports_' . $method)) {

            $this->{'_reports_' . $method}();

        } else {

            show_404();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Browse available reports
     * @return void
     */
    protected function _reports_index()
    {
        if ($this->input->is_cli_request()) {

            return $this->_reports_index_cli();
        }

        // --------------------------------------------------------------------------

        if (!user_has_permission('admin.shop:0.can_generate_reports')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Set method info
        $this->data['page']->title = 'Generate Reports';

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            //  Form validation and update
            $this->load->library('form_validation');

            //  Define rules
            $this->form_validation->set_rules('report', '', 'xss_clean|required');
            $this->form_validation->set_rules('format', '', 'xss_clean|required');

            //  Set Messages
            $this->form_validation->set_message('required', lang('fv_required'));

            //  Execute
            if ($this->form_validation->run() && isset($this->reportSources[$this->input->post('report')]) && isset($this->reportFormats[$this->input->post('format')])) {

                $source = $this->reportSources[$this->input->post('report')];
                $format = $this->reportFormats[$this->input->post('format')];

                if (!method_exists($this, '_report_source_' . $source[2])) {

                    $this->data['error'] = '<strong>Sorry,</strong> that data source is not available.';

                } elseif ((!method_exists($this, '_report_format_' . $format[2]))) {

                    $this->data['error'] = '<strong>Sorry,</strong> that format type is not available.';

                } else {

                    //  All seems well, generate the report!
                    $data = $this->{'_report_source_' . $source[2]}();

                    //  Anything to report?
                    if (!empty($data)) {

                        //  if $data is an array then we need to write multiple files to a zip
                        if (is_array($data)) {

                            //  Load Zip class
                            $this->load->library('zip');

                            //  Process each file
                            foreach ($data as $data) {

                                $file = $this->{'_report_format_' . $format[2]}($data, true);

                                $this->zip->add_data($file[0], $file[1]);
                            }

                            $this->zip->download('shop-report-' . $source[2] . '-' . date('Y-m-d_H-i-s'));

                        } else {

                            $this->{'_report_format_' . $format[2]}($data);
                        }

                    }

                    return;

                                }
            } elseif ((!isset($this->reportSources[ $this->input->post('source') ]))) {

                $this->data['error'] = '<strong>Sorry,</strong> invalid data source.';
            } elseif ((!isset($this->reportFormats[ $this->input->post('format') ]))) {

                $this->data['error'] = '<strong>Sorry,</strong> invalid format type.';

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        $this->data['sources'] = $this->reportSources;
        $this->data['formats'] = $this->reportFormats;

        // --------------------------------------------------------------------------

        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/reports/index', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Browse available reports (command line)
     * @return void
     */
    protected function _reports_index_cli()
    {
        //  @TODO: Complete CLI functionality for report generating
        echo 'Sorry, this functionality is not complete yet. If you are experiencing timeouts please increase the timeout limit for PHP.';
    }

    // --------------------------------------------------------------------------

    /**
     * Report soure: Out of stock variants
     * @return stdClass
     */
    protected function _report_source_out_of_stock_variants()
    {
        if (!user_has_permission('admin.shop:0.inventory_manage')) {

            return false;
        }

        // --------------------------------------------------------------------------

        $out            = new stdClass();
        $out->label = 'Out of Stock variants';
        $out->filename  = NAILS_DB_PREFIX . 'out_of_stock_variants';
        $out->fields    = array();
        $out->data      = array();

        // --------------------------------------------------------------------------

        //  Fetch all variants which are out of stock
        $this->db->select('p.id product_id, p.label product_label, v.id variation_id, v.label variation_label, v.sku, v.quantity_available');
        $this->db->select('(SELECT GROUP_CONCAT(DISTINCT `b`.`label` ORDER BY `b`.`label` SEPARATOR \', \') FROM `' . NAILS_DB_PREFIX . 'shop_product_brand` pb JOIN `' . NAILS_DB_PREFIX . 'shop_brand` b ON `b`.`id` = `pb`.`brand_id` WHERE `pb`.`product_id` = `p`.`id` GROUP BY `pb`.`product_id`) brands', false);
        $this->db->join(NAILS_DB_PREFIX . 'shop_product p', 'p.id = v.product_id', 'LEFT');
        $this->db->where('v.stock_status', 'OUT_OF_STOCK');
        $this->db->where('p.is_deleted', 0);
        $this->db->where('v.is_deleted', 0);
        $this->db->where('p.is_active', 1);
        $out->data = $this->db->get(NAILS_DB_PREFIX . 'shop_product_variation v')->result_array();

        if ($out->data) {

            $out->fields = array_keys($out->data[0]);
        }

        // --------------------------------------------------------------------------

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Report soure: Product sales
     * @return stdClass
     */
    protected function _report_source_product_sales()
    {
        if (!user_has_permission('admin.shop:0.inventory_manage')) {

            return false;
        }

        // --------------------------------------------------------------------------

        $out            = new stdClass();
        $out->label = 'Product Sales';
        $out->filename  = NAILS_DB_PREFIX . 'product_sales';
        $out->fields    = array();
        $out->data      = array();

        // --------------------------------------------------------------------------

        //  Fetch all products from the order products table
        $this->db->select('o.id, o.created, op.quantity as quantity_sold, p.id product_id, p.label product_label, v.id variation_id, v.label variation_label, v.sku, v.quantity_available');
        $this->db->select('(SELECT GROUP_CONCAT(DISTINCT `b`.`label` ORDER BY `b`.`label` SEPARATOR \', \') FROM `' . NAILS_DB_PREFIX . 'shop_product_brand` pb JOIN `' . NAILS_DB_PREFIX . 'shop_brand` b ON `b`.`id` = `pb`.`brand_id` WHERE `pb`.`product_id` = `p`.`id` GROUP BY `pb`.`product_id`) brands', false);
        $this->db->join(NAILS_DB_PREFIX . 'shop_order o', 'o.id = op.order_id', 'LEFT');
        $this->db->join(NAILS_DB_PREFIX . 'shop_product p', 'p.id = op.variant_id', 'LEFT');
        $this->db->join(NAILS_DB_PREFIX . 'shop_product_variation v', 'v.product_id = p.id', 'LEFT');
        $this->db->where('o.status', 'PAID');
        $out->data = $this->db->get(NAILS_DB_PREFIX . 'shop_order_product op')->result_array();

        if ($out->data) {

            $out->fields = array_keys($out->data[0]);
        }

        // --------------------------------------------------------------------------

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Report Format: CSV
     * @param  array   $data        The data to export
     * @param  boolean $return_data Whether or not to output data or return it to the caller
     * @return mixed
     */
    protected function _report_format_csv($data, $return_data = false)
    {
        //  Send header
        if (!$return_data) {

            $this->output->set_content_type('application/octet-stream');
            $this->output->set_header('Pragma: public');
            $this->output->set_header('Expires: 0');
            $this->output->set_header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            $this->output->set_header('Cache-Control: private', false);
            $this->output->set_header('Content-Disposition: attachment; filename=data-export-' . $data->filename . '-' . date('Y-m-d_H-i-s') . '.csv;');
            $this->output->set_header('Content-Transfer-Encoding: binary');
        }

        // --------------------------------------------------------------------------

        //  Set view data
        $this->data['label']    = $data->label;
        $this->data['fields']   = $data->fields;
        $this->data['data']     = $data->data;

        // --------------------------------------------------------------------------

            //  Load view
        if (!$return_data) {

            $this->load->view('admin/shop/reports/csv', $this->data);

        } else {

            $out    = array();
            $out[]  = $data->filename . '.csv';
            $out[]  = $this->load->view('admin/shop/reports/csv', $this->data, true);

            return $out;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Report Format: HTML
     * @param  array   $data        The data to export
     * @param  boolean $return_data Whether or not to output data or return it to the caller
     * @return mixed
     */
    protected function _report_format_html($data, $return_data = false)
    {
        //  Send header
        if (!$return_data) {

            $this->output->set_content_type('application/octet-stream');
            $this->output->set_header('Pragma: public');
            $this->output->set_header('Expires: 0');
            $this->output->set_header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            $this->output->set_header('Cache-Control: private', false);
            $this->output->set_header('Content-Disposition: attachment; filename=data-export-' . $data->filename . '-' . date('Y-m-d_H-i-s') . '.html;');
            $this->output->set_header('Content-Transfer-Encoding: binary');
        }

        // --------------------------------------------------------------------------

        //  Set view data
        $this->data['label']    = $data->label;
        $this->data['fields']   = $data->fields;
        $this->data['data']     = $data->data;

        // --------------------------------------------------------------------------

        //  Load view
        if (!$return_data) {

            $this->load->view('admin/shop/reports/html', $this->data);

        } else {

            $out    = array();
            $out[]  = $data->filename . '.html';
            $out[]  = $this->load->view('admin/shop/reports/html', $this->data, true);

            return $out;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Report Format: Serialize
     * @param  array   $data        The data to export
     * @param  boolean $return_data Whether or not to output data or return it to the caller
     * @return mixed
     */
    protected function _report_format_serialize($data, $return_data = false)
    {
        //  Send header
        if (!$return_data) {

            $this->output->set_content_type('application/octet-stream');
            $this->output->set_header('Pragma: public');
            $this->output->set_header('Expires: 0');
            $this->output->set_header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            $this->output->set_header('Cache-Control: private', false);
            $this->output->set_header('Content-Disposition: attachment; filename=data-export-' . $data->filename . '-' . date('Y-m-d_H-i-s') . '.txt;');
            $this->output->set_header('Content-Transfer-Encoding: binary');
        }

        // --------------------------------------------------------------------------

        //  Set view data
        $this->data['data'] = $data;

        // --------------------------------------------------------------------------

        //  Load view
        if (!$return_data) {

            $this->load->view('admin/shop/reports/serialize', $this->data);

        } else {

            $out    = array();
            $out[]  = $data->filename . '.txt';
            $out[]  = $this->load->view('admin/shop/reports/serialize', $this->data, true);

            return $out;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Report Format: JSON
     * @param  array   $data        The data to export
     * @param  boolean $return_data Whether or not to output data or return it to the caller
     * @return mixed
     */
    protected function _report_format_json($data, $return_data = false)
    {
        //  Send header
        if (!$return_data) {

            $this->output->set_content_type('application/octet-stream');
            $this->output->set_header('Pragma: public');
            $this->output->set_header('Expires: 0');
            $this->output->set_header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            $this->output->set_header('Cache-Control: private', false);
            $this->output->set_header('Content-Disposition: attachment; filename=data-export-' . $data->filename . '-' . date('Y-m-d_H-i-s') . '.json;');
            $this->output->set_header('Content-Transfer-Encoding: binary');
        }

        // --------------------------------------------------------------------------

        //  Set view data
        $this->data['data'] = $data;

        // --------------------------------------------------------------------------

        //  Load view
        if (!$return_data) {

            $this->load->view('admin/shop/reports/json', $this->data);

        } else {

            $out    = array();
            $out[]  = $data->filename . '.json';
            $out[]  = $this->load->view('admin/shop/reports/json', $this->data, true);

            return $out;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Report Format: PDF
     * @param  array   $data        The data to export
     * @param  boolean $return_data Whether or not to output data or return it to the caller
     * @return mixed
     */
    protected function _report_format_pdf($data, $return_data = false)
    {
        $html = $this->_report_format_html($data, true);

        // --------------------------------------------------------------------------

        $this->load->library('pdf/pdf');
        $this->pdf->set_paper_size('A4', 'landscape');
        $this->pdf->load_html($html[1]);

        //  Load view
        if (!$return_data) {

            $this->pdf->download($data->filename . '.pdf');

        } else {

            $this->pdf->render();

            $out    = array();
            $out[]  = $data->filename . '.pdf';
            $out[]  = $this->pdf->output();

            $this->pdf->reset();

            return $out;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Manage product availability notifications
     * @return void
     */
    public function product_availability_notifications()
    {
        if (!user_has_permission('admin.shop:0.notifications_manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load voucher model
        $this->load->model('shop/shop_inform_product_available_model');

        // --------------------------------------------------------------------------

        $method = $this->uri->segment(4) ? $this->uri->segment(4) : 'index';

        if (method_exists($this, '_product_availability_notifications_' . $method)) {

            $this->{'_product_availability_notifications_' . $method}();

        } else {

            show_404();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Browse product availability notifications
     * @return [type] [description]
     */
    protected function _product_availability_notifications_index()
    {
        //  Set method info
        $this->data['page']->title = 'Manage Product Availability Notifications';

        // --------------------------------------------------------------------------

        $this->data['notifications'] = $this->shop_inform_product_available_model->get_all();

        // --------------------------------------------------------------------------

        $this->asset->load('nails.admin.shop.productavailabilitynotification.browse.min.js', true);
        $this->asset->inline('var _SHOP_PRODUCT_AVAILABILITY_NOTIFICATION_BROWSE = new NAILS_Admin_Shop_Product_Availability_Notification_Browse()', 'JS');

        // --------------------------------------------------------------------------

        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/product_availability_notifications/index', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Create a product availability notification
     * @return void
     */
    protected function _product_availability_notifications_create()
    {
        if (!user_has_permission('admin.shop:0.notification_create')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->load->library('form_validation');

            $this->form_validation->set_rules('email', '', 'xss_clean|required|valid_email');
            $this->form_validation->set_rules('item', '', 'xss_clean|required');

            $this->form_validation->set_message('required', lang('fv_required'));
            $this->form_validation->set_message('valid_email',  lang('fv_valid_email'));

            if ($this->form_validation->run()) {

                $item = explode(':', $this->input->post('item'));

                $data                   = new stdClass();
                $data->email            = $this->input->post('email');
                $data->product_id       = isset($item[0]) ? (int) $item[0] : null;
                $data->variation_id = isset($item[1]) ? (int) $item[1] : null;

                if ($this->shop_inform_product_available_model->create($data)) {

                    //  Redirect to clear form
                    $this->session->set_flashdata('success', '<strong>Success!</strong> Product Availability Notification created successfully.');
                    redirect('admin/shop/product_availability_notifications');

                } else {

                    $this->data['error'] = '<strong>Sorry,</strong> there was a problem creating the Product Availability Notification. ' . $this->shop_inform_product_available_model->last_error();

                                }
            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Set method info
        $this->data['page']->title = 'Create Product Availability Notification';

        // --------------------------------------------------------------------------

        $this->data['products_variations_flat'] = $this->shop_product_model->getAllProductVariationFlat();

        // --------------------------------------------------------------------------

        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/product_availability_notifications/edit', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a product availability notification
     * @return void
     */
    protected function _product_availability_notifications_edit()
    {
        if (!user_has_permission('admin.shop:0.notification_edit')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $this->data['notification'] = $this->shop_inform_product_available_model->get_by_id($this->uri->segment(5));

        if (!$this->data['notification']) {

            show_404();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->load->library('form_validation');

            $this->form_validation->set_rules('email', '', 'xss_clean|required|valid_email');
            $this->form_validation->set_rules('item', '', 'xss_clean|required');

            $this->form_validation->set_message('required', lang('fv_required'));
            $this->form_validation->set_message('valid_email',  lang('fv_valid_email'));

            if ($this->form_validation->run()) {

                $item = explode(':', $this->input->post('item'));

                $data                   = new stdClass();
                $data->email            = $this->input->post('email');
                $data->product_id       = isset($item[0]) ? (int) $item[0] : null;
                $data->variation_id = isset($item[1]) ? (int) $item[1] : null;

                if ($this->shop_inform_product_available_model->update($this->data['notification']->id, $data)) {

                    //  Redirect to clear form
                    $this->session->set_flashdata('success', '<strong>Success!</strong> Product Availability Notification updated successfully.');
                    redirect('admin/shop/product_availability_notifications');

                } else {

                    $this->data['error'] = '<strong>Sorry,</strong> there was a problem updated the Product Availability Notification. ' . $this->shop_inform_product_available_model->last_error();

                                }
            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Set method info
        $this->data['page']->title = 'Edit Product Availability Notification';

        // --------------------------------------------------------------------------

        $this->data['products_variations_flat'] = $this->shop_product_model->getAllProductVariationFlat();

        // --------------------------------------------------------------------------

        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/shop/product_availability_notifications/edit', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a product availability notification
     * @return void
     */
    protected function _product_availability_notifications_delete()
    {
        if (!user_has_permission('admin.shop:0.notifications_delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id = $this->uri->segment(5);

        if ($this->shop_inform_product_available_model->delete($id)) {

            $this->session->set_flashdata('success', '<strong>Success!</strong> Product Availability Notification was deleted successfully.');

        } else {

            $this->session->set_flashdata('error', '<strong>Sorry,</strong> there was a problem deleting the Product availability Notification. ' . $this->shop_inform_product_available_model->last_error());
        }

        redirect('admin/shop/product_availability_notifications');
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

    // --------------------------------------------------------------------------

    /**
     * Form Validation: Validate a voucher's code
     * @param  string &$str The voucher code
     * @return boolean
     */
    public function _callback_voucher_valid_code(&$str)
    {
        $str = strtoupper($str);

        if  (preg_match('/[^a-zA-Z0-9]/', $str)) {

            $this->form_validation->set_message('_callback_voucher_valid_code', 'Invalid characters.');
            return false;

        } else {

            return true;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Form Validation: Validate a voucher's type
     * @param  string $str The voucher type
     * @return boolean
     */
    public function _callback_voucher_valid_type($str)
    {
        $valid_types = array('NORMAL', 'LIMITED_USE', 'GIFT_CARD');
        $this->form_validation->set_message('_callback_voucher_valid_type', 'Invalid voucher type.');
        return array_search($str, $valid_types) !== false;
    }

    // --------------------------------------------------------------------------

    /**
     * Form Validation: Validate a voucher's discount tye
     * @param  string $str The voucher discount type
     * @return boolean
     */
    public function _callback_voucher_valid_discount_type($str)
    {
        $valid_types = array('PERCENTAGE', 'AMOUNT');
        $this->form_validation->set_message('_callback_voucher_valid_discount_type', 'Invalid discount type.');
        return array_search($str, $valid_types) !== false;
    }

    // --------------------------------------------------------------------------

    /**
     * Form Validation: Validate a voucher's product type
     * @param  string $str The voucher product type
     * @return boolean
     */
    public function _callback_voucher_valid_product_type($str)
    {
        $this->form_validation->set_message('_callback_voucher_valid_product_type', 'Invalid product type.');
        return (bool) $this->shop_product_type_model->get_by_id($str);
    }

    // --------------------------------------------------------------------------

    /**
     * Form Validation: Validate a voucher's from date
     * @param  string $str The voucher from date
     * @return boolean
     */
    public function _callback_voucher_valid_from(&$str)
    {
        //  Check $str is a valid date
        $date = date('Y-m-d H:i:s', strtotime($str));

        //  Check format of str
        if (preg_match('/^\d\d\d\d\-\d\d-\d\d$/', trim($str))) {

            //in YYYY-MM-DD format, add the time
            $str = trim($str) . ' 00:00:00';
        }

        if ($date != $str) {

            $this->form_validation->set_message('_callback_voucher_valid_from', 'Invalid date.');
            return false;
        }

        //  If valid_to is defined make sure valid_from isn't before it
        if ($this->input->post('valid_to')) {

            $date = strtotime($this->input->post('valid_to'));

            if (strtotime($str) >= $date) {

                $this->form_validation->set_message('_callback_voucher_valid_from', 'Valid From date cannot be after Valid To date.');
                return false;
            }
        }

        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * Form Validation: Validate a voucher's to date
     * @param  string $str The voucher to date
     * @return boolean
     */
    public function _callback_voucher_valid_to(&$str)
    {
        //  If empty ignore
        if (!$str)
            return true;

        // --------------------------------------------------------------------------

        //  Check $str is a valid date
        $date = date('Y-m-d H:i:s', strtotime($str));

        //  Check format of str
        if (preg_match('/^\d\d\d\d\-\d\d\-\d\d$/', trim($str))) {

            //in YYYY-MM-DD format, add the time
            $str = trim($str) . ' 00:00:00';
        }

        if ($date != $str) {

            $this->form_validation->set_message('_callback_voucher_valid_to', 'Invalid date.');
            return false;
        }

        //  Make sure valid_from isn't before it
        $date = strtotime($this->input->post('valid_from'));

        if (strtotime($str) <= $date) {

            $this->form_validation->set_message('_callback_voucher_valid_to', 'Valid To date cannot be before Valid To date.');
            return false;
        }

        return true;
    }
}

// --------------------------------------------------------------------------

/**
 * OVERLOADING NAILS' ADMIN MODULES
 *
 * The following block of code makes it simple to extend one of the core admin
 * controllers. Some might argue it's a little hacky but it's a simple 'fix'
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
 * If/when we want to extend the main class we simply define NAILS_ALLOW_EXTENSION_CLASSNAME
 * before including this PHP file and extend as normal (i.e in the same way as below);
 * the helper won't be declared so we can declare our own one, app specific.
 *
 **/

if (!defined('NAILS_ALLOW_EXTENSION_SHOP')) {

    /**
     * Proxy class for NAILS_Shop
     */
    class Shop extends NAILS_Shop
    {
    }
}
