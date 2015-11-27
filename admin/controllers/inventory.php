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

use Nails\Factory;
use Nails\Admin\Helper;
use Nails\Shop\Controller\BaseAdmin;

class Inventory extends BaseAdmin
{
    /**
     * Announces this controller's navGroups
     * @return stdClass
     */
    public static function announce()
    {
        if (userHasPermission('admin:shop:inventory:manage')) {

            $oNavGroup = Factory::factory('Nav', 'nailsapp/module-admin');
            $oNavGroup->setLabel('Shop');
            $oNavGroup->setIcon('fa-shopping-cart');
            $oNavGroup->addAction('Manage Inventory', 'index', array(), 0);
            return $oNavGroup;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of extra permissions for this controller
     * @return array
     */
    public static function permissions()
    {
        $permissions = parent::permissions();

        $permissions['manage']  = 'Manage inventory items';
        $permissions['create']  = 'Create inventory items';
        $permissions['edit']    = 'Edit inventory items';
        $permissions['delete']  = 'Delete inventory items';
        $permissions['restore'] = 'Restore inventory items';
        $permissions['import']  = 'Import inventory items';

        return $permissions;
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
        $this->load->model('shop/shop_brand_model');
        $this->load->model('shop/shop_supplier_model');
        $this->load->model('shop/shop_collection_model');
        $this->load->model('shop/shop_product_model');
        $this->load->model('shop/shop_product_type_model');

        $this->oFormValidation = Factory::service('FormValidation');

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
        if (!userHasPermission('admin:shop:inventory:manage')) {

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

        //  Checkbox Filters
        $cbFilters = array();
        $cbFilters['isActive'] = Helper::searchFilterObject(
            $tablePrefix . '.is_active',
            'Active',
            array(
                array('Yes', 1, true),
                array('No', 0, true)
            )
        );
        $cbFilters['stockStatus'] = Helper::searchFilterObject(
            '',
            'Status',
            array(
                array('In Stock', 'IN_STOCK', true),
                array('Out of Stock', 'OUT_OF_STOCK', true)
            )
        );

        /**
         * Dropdown Filters
         * Leaving columns blank so that getCountCommon() doesn't try and do
         * anything with these values, they will be handled below.
         */

        $data = array('only_active' => false);

        $ddFilters = array();
        $ddFilters['categoryId'] = Helper::searchFilterObject(
            '',
            'Category',
            array('Choose Category') + $this->shop_category_model->getAllNestedFlat()
        );
        $ddFilters['brandId'] = Helper::searchFilterObject(
            '',
            'Brand',
            array('Choose Brand') + $this->shop_brand_model->getAllFlat(null, null, $data)
        );
        $ddFilters['supplierId'] = Helper::searchFilterObject(
            '',
            'Supplier',
            array('Choose Supplier') + $this->shop_supplier_model->getAllFlat(null, null, $data)
        );
        $ddFilters['collectionId'] = Helper::searchFilterObject(
            '',
            'Collection',
            array('Choose Collection') + $this->shop_collection_model->getAllFlat(null, null, $data)
        );

        // --------------------------------------------------------------------------

        //  Define the $data variable for the queries
        $data = array(
            'include_inactive' => true,
            'include_inactive_variants' => true,
            'where' => array(),
            'sort'  => array(
                array($sortOn, $sortOrder)
            ),
            'keywords'  => $keywords,
            'ddFilters' => $ddFilters,
            'cbFilters' => $cbFilters
        );

        // --------------------------------------------------------------------------

        /**
         * Determine if we're restricting to a certain category, brand, supplier, or collection.
         *
         * Due to the way the search component works, we need to "listen" to the $_GET
         * array by hand. Each filter above will be  indexed in either DDF (DropDownFilter)
         * or cbF (CheckBoxFilter). For ddF values the value at the index is the
         * selected option.
         */

        if (!empty($_GET['ddF']['categoryId'])) {

            $categoryId = Helper::searchFilterGetValueAtKey(
                $ddFilters['categoryId'],
                $_GET['ddF']['categoryId']
            );

            $childCategories     = $this->shop_category_model->getIdsOfChildren($categoryId);
            $data['category_id'] = array_merge(array($categoryId), $childCategories);
        }

        if (!empty($_GET['ddF']['brandId'])) {

            $data['brand_id'] = Helper::searchFilterGetValueAtKey(
                $ddFilters['brandId'],
                $_GET['ddF']['brandId']
            );
        }

        if (!empty($_GET['ddF']['supplierId'])) {

            $data['supplier_id'] = Helper::searchFilterGetValueAtKey(
                $ddFilters['supplierId'],
                $_GET['ddF']['supplierId']
            );
        }

        if (!empty($_GET['ddF']['collectionId'])) {

            $data['collection_id'] = Helper::searchFilterGetValueAtKey(
                $ddFilters['collectionId'],
                $_GET['ddF']['collectionId']
            );
        }

        if (!empty($_GET['cbF']['stockStatus'])) {

            $data['stockStatus'] = array();

            foreach ($_GET['cbF']['stockStatus'] as $filterKey => $checked) {
                $data['stockStatus'][] = Helper::searchFilterGetValueAtKey(
                    $cbFilters['stockStatus'],
                    $filterKey
                );
            }
        }

        // --------------------------------------------------------------------------

        //  Get the items for the page
        $totalRows              = $this->shop_product_model->countAll($data);
        $this->data['products'] = $this->shop_product_model->getAll($page, $perPage, $data);

        //  Set Search and Pagination objects for the view
        $this->data['search'] = Helper::searchObject(
            true,
            $sortColumns,
            $sortOn,
            $sortOrder,
            $perPage,
            $keywords,
            $cbFilters,
            $ddFilters
        );
        $this->data['pagination'] = Helper::paginationObject($page, $perPage, $totalRows);

        // --------------------------------------------------------------------------

        if (userHasPermission('admin:shop:inventory:create')) {

            Helper::addHeaderButton('admin/shop/inventory/import', 'Import Items', 'warning');
            Helper::addHeaderButton('admin/shop/inventory/create', 'Add New Item');
        }

        // --------------------------------------------------------------------------

        //  Fetch other required bits of data
        $this->data['productTypes']   = $this->shop_product_type_model->getAll();

        // --------------------------------------------------------------------------

        //  Finally, a bit of a database integrity check.

        //  Undeleted products which have only deleted variants
        $this->db->select('p.id');
        $this->db->select('(SELECT COUNT(*) FROM ' . $this->shop_product_model->getMetaTable('variation') . ' v WHERE v.product_id = p.id) variantCount');
        $this->db->select('(SELECT COUNT(*) FROM ' . $this->shop_product_model->getMetaTable('variation') . ' v WHERE v.product_id = p.id AND v.is_deleted=1) variantCountDeleted');
        $this->db->where('p.is_deleted', false);
        $this->db->having('variantCount = variantCountDeleted');
        $this->db->group_by('p.id');
        $aResultInactive = $this->db->get($this->shop_product_model->getTableName() . ' p')->result();

        //  Undeleted and active products which only have inactive variants
        $this->db->select('p.id');
        $this->db->select('(SELECT COUNT(*) FROM ' . $this->shop_product_model->getMetaTable('variation') . ' v WHERE v.product_id = p.id) variantCount');
        $this->db->select('(SELECT COUNT(*) FROM ' . $this->shop_product_model->getMetaTable('variation') . ' v WHERE v.product_id = p.id AND v.is_active=0 AND v.is_deleted=0) variantCountInactive');
        $this->db->where('p.is_deleted', false);
        $this->db->where('p.is_active', true);
        $this->db->having('variantCount = variantCountInactive');
        $this->db->group_by('p.id');
        $aResultDeleted = $this->db->get($this->shop_product_model->getTableName() . ' p')->result();

        if (!empty($aResultInactive) || !empty($aResultInactive)) {

            $this->data['warning']  = '<strong>There are discrepancies in the database.</strong>';
            $this->data['warning'] .= '<br />The following issues were detected and should be addressed or reported:';

            if (!empty($aResultInactive)) {
                $this->data['warning'] .= '<br />&rsaquo; ' . count($aResultInactive) . ' products which do not ';
                $this->data['warning'] .= 'have any variants. ';
                $this->data['warning'] .= '<a href="#inactive-ids" class="fancybox">Click for Details</a>';
                $this->data['warning'] .= '<div id="inactive-ids" style="display: none; width: 400px;">';
                $this->data['warning'] .= 'The following product IDs are affected:<br />';
                $aAffected = array();
                foreach ($aResultInactive as $oResult) {
                    $aAffected[] = $oResult->id;
                }
                $this->data['warning'] .= '<textarea style="border: 1px solid #CCC;width:100%;height: 100px;margin: 1em 0 0 0">';
                $this->data['warning'] .= implode(', ', $aAffected);
                $this->data['warning'] .= '</textarea>';
                $this->data['warning'] .= '</div>';
            }

            if (!empty($aResultDeleted)) {
                $this->data['warning'] .= '<br />&rsaquo; ' . count($aResultDeleted) . ' active products which do not ';
                $this->data['warning'] .= 'have any active variants. ';
                $this->data['warning'] .= '<a href="#deleted-ids" class="fancybox">Click for Details</a>';
                $this->data['warning'] .= '<div id="inactive-ids" style="display: none; width: 400px;">';
                $this->data['warning'] .= 'The following product IDs are affected:<br />';
                $aAffected = array();
                foreach ($aResultDeleted as $oResult) {
                    $aAffected[] = $oResult->id;
                }
                $this->data['warning'] .= '<textarea style="border: 1px solid #CCC;width:100%;height: 100px;margin: 1em 0 0 0">';
                $this->data['warning'] .= implode(', ', $aAffected);
                $this->data['warning'] .= '</textarea>';
                $this->data['warning'] .= '</div>';
            }

            $this->data['warning'] .= $aResultDeleted ? '<br />&rsaquo; ' . count($aResultDeleted) . ' ' : '';
        }

        // --------------------------------------------------------------------------

        Helper::loadView('index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a new Shop inventory item
     * @return void
     */
    public function create()
    {
        if (!userHasPermission('admin:shop:inventory:create')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Add new Inventory Item';

        // --------------------------------------------------------------------------

        //  Fetch data, this data is used in both the view and the form submission
        $oCurrencyModel              = Factory::model('Currency', 'nailsapp/module-shop');
        $this->data['currencies']    = $oCurrencyModel->getAllSupported();
        $this->data['product_types'] = $this->shop_product_type_model->getAll();

        if (!$this->data['product_types']) {

            //  No Product types, some need added, yo!
            $this->session->set_flashdata(
                'negative',
                '<strong>Missing Product Types</strong>' .
                '<br />No product types have been defined. You must create some before you can add inventory items.'
            );
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
        $this->data['shipping_options_variant'] = $this->shop_shipping_driver_model->fieldsVariant();

        // --------------------------------------------------------------------------

        //  Process POST
        if ($this->input->post()) {

            /**
             * If not active then allow the request through, otherwise, run the
             * validation rules.
             */
            if ($this->input->post('is_active')) {

                $this->inventoryCreateEditValidationRules();
                $bAdditionalValidationError = false;
                $aVariations = $this->input->post('variation');
                $iActiveVariants = 0;

                /**
                 * If there are no active variants, then the product itself must also be
                 * marked as inactive.
                 */
                foreach ($aVariations as $aVariant) {

                    if (!empty($aVariant['is_active'])) {
                        $iActiveVariants++;
                        break;
                    }
                }

                if (!$iActiveVariants) {

                    $this->data['error'] = 'A product marked as active must have at least one active variation.';
                    $bAdditionalValidationError = true;
                }

                $bPassedValidation = $this->oFormValidation->run($this) && !$bAdditionalValidationError;

            } else {

                $bPassedValidation = true;
            }

            // --------------------------------------------------------------------------

            if ($bPassedValidation) {

                //  Prep the fields
                $this->inventoryCreateEditPrepFields();

                //  Validated!Create the product
                $aInsertData = (array) $this->input->post();
                $product = $this->shop_product_model->create($aInsertData);

                if ($product) {

                    $this->session->set_flashdata('success', 'Product was created successfully.');
                    redirect('admin/shop/inventory');

                } else {

                    $this->data['error'] = 'There was a problem creating the Product. ' . $this->shop_product_model->lastError();
                }

            } else {

                if (empty($this->data['error'])) {

                    $this->data['error'] = lang('fv_there_were_errors');
                }
            }
        }

        // --------------------------------------------------------------------------

        //  Load additional models
        $this->load->model('shop/shop_attribute_model');
        $this->load->model('shop/shop_brand_model');
        $this->load->model('shop/shop_supplier_model');
        $this->load->model('shop/shop_category_model');
        $this->load->model('shop/shop_collection_model');
        $this->load->model('shop/shop_range_model');
        $this->load->model('shop/shop_tag_model');
        $this->load->model('shop/shop_tax_rate_model');

        // --------------------------------------------------------------------------

        //  Fetch additional data
        $data = array('only_active' => false);

        $this->data['product_types_flat'] = $this->shop_product_type_model->getAllFlat();
        $this->data['tax_rates']          = $this->shop_tax_rate_model->getAllFlat();
        $this->data['attributes']         = $this->shop_attribute_model->getAllFlat();
        $this->data['brands']             = $this->shop_brand_model->getAllFlat(null, null, $data);
        $this->data['suppliers']          = $this->shop_supplier_model->getAllFlat(null, null, $data);
        $this->data['categories']         = $this->shop_category_model->getAllNestedFlat();
        $this->data['collections']        = $this->shop_collection_model->getAll(null, null, $data);
        $this->data['ranges']             = $this->shop_range_model->getAll(null, null, $data);
        $this->data['tags']               = $this->shop_tag_model->getAllFlat();

        $this->data['tax_rates'] = array('No Tax') + $this->data['tax_rates'];

        // --------------------------------------------------------------------------

        //  Assets
        $this->asset->library('uploadify');
        $this->asset->load('mustache.js/mustache.js', 'NAILS-BOWER');
        $this->asset->load('nails.admin.shop.inventory.createEdit.min.js', 'NAILS');

        $uploadToken = $this->cdn->generateApiUploadToken(activeUser('id'));

        $this->asset->inline('var _edit = new NAILS_Admin_Shop_Inventory_Create_Edit();', 'JS');
        $this->asset->inline('_edit.init(' . json_encode($this->data['product_types']) . ', "' . $uploadToken . '");', 'JS');

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a shop inventory item
     * @return void
     */
    public function edit()
    {
        if (!userHasPermission('admin:shop:inventory:edit')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Fetch item
        $productId = $this->uri->segment(5);
        $data = array(
            'include_inactive_variants' => true
        );
        $this->data['item'] = $this->shop_product_model->getById($productId, $data);

        if (!$this->data['item']) {

            $this->session->set_flashdata('error', 'I could not find a product by that ID.');
            redirect('admin/shop/inventory');
        }

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Edit Inventory Item "' . $this->data['item']->label . '"';

        // --------------------------------------------------------------------------

        //  Fetch data, this data is used in both the view and the form submission
        $this->data['product_types'] = $this->shop_product_type_model->getAll();

        if (!$this->data['product_types']) {

            //  No Product types, some need added, yo!
            $this->session->set_flashdata('message', '<strong>Hey!</strong> No product types have been defined. You must set some before you can add inventory items.');
            redirect('admin/shop/manage/productType/create');
        }

        $oCurrencyModel           = Factory::model('Currency', 'nailsapp/module-shop');
        $this->data['currencies'] = $oCurrencyModel->getAllSupported();

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
        $this->data['shipping_options_variant'] = $this->shop_shipping_driver_model->fieldsVariant();

        // --------------------------------------------------------------------------

        //  Process POST
        if ($this->input->post()) {

            /**
             * If not active then allow the request through, otherwise, run the
             * validation rules.
             */

            $bPassedValidation = true;

            if (!$this->input->post('variants')) {

                $this->data['error'] = 'At least one variation is required.';
                $bPassedValidation   = false;
            }

            if ($bPassedValidation && $this->input->post('is_active')) {

                $this->inventoryCreateEditValidationRules();
                $bAdditionalValidationError = false;
                $aVariations     = $this->input->post('variation');
                $iActiveVariants = 0;

                /**
                 * If there are no active variants, then the product itself must also be
                 * marked as inactive.
                 */
                foreach ($aVariations as $aVariant) {

                    if (!empty($aVariant['is_active'])) {
                        $iActiveVariants++;
                        break;
                    }
                }

                if (!$iActiveVariants) {

                    $this->data['error']        = 'A product marked as active must have at least one active variation.';
                    $bAdditionalValidationError = true;
                }

                $bPassedValidation = $this->oFormValidation->run($this) && !$bAdditionalValidationError;
            }

            // --------------------------------------------------------------------------

            if ($bPassedValidation) {

                //  Prep the fields
                $this->inventoryCreateEditPrepFields();

                //  Validated! Create the product
                $aUpdateData = (array) $this->input->post();
                $product     = $this->shop_product_model->update($this->data['item']->id, $aUpdateData);

                if ($product) {

                    $this->session->set_flashdata('success', 'Product was updated successfully.');
                    redirect('admin/shop/inventory');

                } else {

                    $this->data['error'] = 'There was a problem updating the Product. ' . $this->shop_product_model->lastError();
                }

            } else {

                if (empty($this->data['error'])) {

                    $this->data['error'] = lang('fv_there_were_errors');
                }
            }
        }

        // --------------------------------------------------------------------------

        //  Load additional models
        $this->load->model('shop/shop_attribute_model');
        $this->load->model('shop/shop_brand_model');
        $this->load->model('shop/shop_supplier_model');
        $this->load->model('shop/shop_category_model');
        $this->load->model('shop/shop_collection_model');
        $this->load->model('shop/shop_range_model');
        $this->load->model('shop/shop_tag_model');
        $this->load->model('shop/shop_tax_rate_model');

        // --------------------------------------------------------------------------

        //  Fetch additional data
        $data = array('only_active' => false);

        $this->data['product_types_flat'] = $this->shop_product_type_model->getAllFlat();
        $this->data['tax_rates']          = $this->shop_tax_rate_model->getAllFlat();
        $this->data['attributes']         = $this->shop_attribute_model->getAllFlat();
        $this->data['brands']             = $this->shop_brand_model->getAllFlat(null, null, $data);
        $this->data['suppliers']          = $this->shop_supplier_model->getAllFlat(null, null, $data);
        $this->data['categories']         = $this->shop_category_model->getAllNestedFlat();
        $this->data['collections']        = $this->shop_collection_model->getAll(null, null, $data);
        $this->data['ranges']             = $this->shop_range_model->getAll(null, null, $data);
        $this->data['tags']               = $this->shop_tag_model->getAllFlat();
        $this->data['relatedProducts']    = $this->shop_product_model->getRelatedProducts($this->data['item']->id);

        $this->data['tax_rates'] = array('No Tax') + $this->data['tax_rates'];

        // --------------------------------------------------------------------------

        //  Assets
        $this->asset->library('uploadify');
        $this->asset->load('mustache.js/mustache.js', 'NAILS-BOWER');
        $this->asset->load('nails.admin.shop.inventory.createEdit.min.js', 'NAILS');

        $uploadToken = $this->cdn->generateApiUploadToken(activeUser('id'));

        $this->asset->inline('var _edit = new NAILS_Admin_Shop_Inventory_Create_Edit();', 'JS');
        $this->asset->inline('_edit.init(' . json_encode($this->data['product_types']) . ', "' . $uploadToken . '");', 'JS');

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Form Validation: Set the validation rules for creating/editing inventory items
     * @return void
     */
    protected function inventoryCreateEditValidationRules()
    {
        //  Product Info
        //  ============
        $this->oFormValidation->set_rules('type_id', '', 'xss_clean|required');
        $this->oFormValidation->set_rules('label', '', 'xss_clean|required');
        $this->oFormValidation->set_rules('is_active', '', 'xss_clean');
        $this->oFormValidation->set_rules('brands', '', 'xss_clean');
        $this->oFormValidation->set_rules('suppliers', '', 'xss_clean');
        $this->oFormValidation->set_rules('categories', '', 'xss_clean');
        $this->oFormValidation->set_rules('google_category', '', 'xss_clean');
        $this->oFormValidation->set_rules('tags', '', 'xss_clean');
        $this->oFormValidation->set_rules('tax_rate_id', '', 'xss_clean|required');
        $this->oFormValidation->set_rules('published', '', 'xss_clean|required');

        // --------------------------------------------------------------------------

        //  External product
        if (appSetting('enable_external_products', 'shop')) {

            $this->oFormValidation->set_rules('is_external', '', 'xss_clean');

            if ($this->input->post('is_external')) {

                $this->oFormValidation->set_rules('external_vendor_label', '', 'xss_clean|required');
                $this->oFormValidation->set_rules('external_vendor_url', '', 'xss_clean|required');

            } else {

                $this->oFormValidation->set_rules('external_vendor_label', '', 'xss_clean');
                $this->oFormValidation->set_rules('external_vendor_url', '', 'xss_clean');
            }
        }

        // --------------------------------------------------------------------------

        //  Description
        //  ===========
        $this->oFormValidation->set_rules('description', '', 'required');

        // --------------------------------------------------------------------------

        //  Variants - Loop variants
        //  ========================
        if ($this->input->post('variation') && is_array($this->input->post('variation'))) {

            foreach ($this->input->post('variation') as $index => $v) {

                //  Details
                //  -------

                $this->oFormValidation->set_rules('variation[' . $index . '][label]', '', 'xss_clean|trim|required');

                $v_id = !empty($v['id']) ? $v['id'] : '';
                $this->oFormValidation->set_rules('variation[' . $index . '][sku]', '', 'xss_clean|trim|callback_callbackInventoryValidSku[' . $v_id . ']');
                $this->oFormValidation->set_rules('variation[' . $index . '][is_active]', '', 'xss_clean');

                //  Stock
                //  -----

                $this->oFormValidation->set_rules('variation[' . $index . '][stock_status]', '', 'xss_clean|callback_callbackInventoryValidStockStatus|required');

                $stock_status = isset($v['stock_status']) ? $v['stock_status'] : '';

                switch ($stock_status) {

                    case 'IN_STOCK':

                        $this->oFormValidation->set_rules('variation[' . $index . '][quantity_available]', '', 'xss_clean|trim|callback_callbackInventoryValidQuantity');
                        $this->oFormValidation->set_rules('variation[' . $index . '][lead_time]', '', 'xss_clean|trim');
                        break;

                    case 'OUT_OF_STOCK':

                        $this->oFormValidation->set_rules('variation[' . $index . '][quantity_available]', '', 'xss_clean|trim');
                        $this->oFormValidation->set_rules('variation[' . $index . '][lead_time]', '', 'xss_clean|trim');
                        break;
                }

                //  Pricing
                //  -------
                if (isset($v['pricing'])) {

                    foreach ($v['pricing'] as $price_index => $price) {

                        $required = $price['currency'] == SHOP_BASE_CURRENCY_CODE ? '|required' : '';

                        $this->oFormValidation->set_rules('variation[' . $index . '][pricing][' . $price_index . '][price]', '', 'xss_clean|callback_callbackInventoryValidPrice' . $required);
                    }
                }

                //  Gallery Associations
                //  --------------------
                if (isset($v['gallery'])) {

                    foreach ($v['gallery'] as $gallery_index => $image) {

                        $this->oFormValidation->set_rules('variation[' . $index . '][gallery][' . $gallery_index . ']', '', 'xss_clean');
                    }
                }

                //  Shipping
                //  --------

                //  Collect only switch
                $this->oFormValidation->set_rules('variation[' . $index . '][shipping][collection_only]', '', 'xss_clean');

                //  Foreach of the driver's settings and apply any rules, but if collect only is on then don't bother
                $shipping_options = $this->shop_shipping_driver_model->fieldsVariant();
                foreach ($shipping_options as $option) {

                    $rules      = array();
                    $rules[]    = 'xss_clean';

                    if (empty($_POST['variation'][$index]['shipping']['collection_only'])) {

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

                    $this->oFormValidation->set_rules('variation[' . $index . '][shipping][driver_data][' . $this->data['shipping_driver']->slug . '][' . $option['key'] . ']', $option['label'], $rules);
                }
            }
        }

        // --------------------------------------------------------------------------

        //  Gallery
        $this->oFormValidation->set_rules('gallery', '', 'xss_clean');

        // --------------------------------------------------------------------------

        //  Attributes
        $this->oFormValidation->set_rules('attributes', '', 'xss_clean');

        // --------------------------------------------------------------------------

        //  Ranges & Collections
        $this->oFormValidation->set_rules('ranges', '', 'xss_clean');
        $this->oFormValidation->set_rules('collections', '', 'xss_clean');

        // --------------------------------------------------------------------------

        //  Related products
        $this->oFormValidation->set_rules('related', '', 'xss_clean');

        // --------------------------------------------------------------------------

        //  SEO
        $this->oFormValidation->set_rules('seo_title', '', 'xss_clean|max_length[150]');
        $this->oFormValidation->set_rules('seo_description', '', 'xss_clean|max_length[300]');
        $this->oFormValidation->set_rules('seo_keywords', '', 'xss_clean|max_length[150]');

        // --------------------------------------------------------------------------

        //  Set messages
        $this->oFormValidation->set_message('required', lang('fv_required'));
        $this->oFormValidation->set_message('numeric', lang('fv_numeric'));
        $this->oFormValidation->set_message('is_natural', lang('fv_is_natural'));
        $this->oFormValidation->set_message('max_length', lang('fv_max_length'));
    }

    // --------------------------------------------------------------------------

    /**
     * Performs any alterations to fields prior to being passed to the shop_product_model.
     * @return Void
     */
    protected function inventoryCreateEditPrepFields()
    {
        /**
         * If the published date is set then it should be converted from the user's
         * timezone to the Nails timezone, otherwise it should be NULL.
         */
        if (empty($_POST['published']) || $_POST['published'] === '0000-00-00 00:00:00') {

            $_POST['published'] = null;

        } else {

            $_POST['published'] = toNailsDatetime($_POST['published']);
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a shop inventory item
     * @return void
     */
    public function delete()
    {
        if (!userHasPermission('admin:shop:inventory:delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $product = $this->shop_product_model->getById($this->uri->segment(5));

        if (!$product) {

            $status = 'error';
            $msg    = 'A product with that ID could not be found.';
            $this->session->set_flashdata($status, $msg);
            redirect('admin/shop/inventory/index');
        }

        // --------------------------------------------------------------------------

        if ($this->shop_product_model->delete($product->id)) {

            $status = 'success';
            $msg    = 'Product successfully deleted! You can restore this product by ';
            $msg   .= anchor('/admin/shop/inventory/restore/' . $product->id, 'clicking here') . '.';

        } else {

            $status = 'error';
            $msg    = 'That product could not be deleted. ';
            $msg   .= $this->shop_product_model->lastError();
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
        if (!userHasPermission('admin:shop:inventory:restore')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

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
        if (!userHasPermission('admin:shop:inventory:import')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        Factory::helper('string');
        $method = $this->uri->segment(5) ? $this->uri->segment(5) : 'index';
        $method = 'import' . underscoreToCamelcase(strtolower($method), false);

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

        Helper::loadView('import');
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
    public function callbackInventoryValidPrice($str)
    {
        $str = trim($str);

        if ($str && !is_numeric($str)) {

            $this->oFormValidation->set_message('callbackInventoryValidPrice', 'This is not a valid price');
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
    public function callbackInventoryValidQuantity($str)
    {
        $str = trim($str);

        //  Quantities are valid if it's either completely blank, or numeric.
        if ($str === '') {

            return true;

        } elseif (is_numeric($str)) {

            //  If numeric, must be a natural number
            if ((int) $str == $str) {

                if ((int) $str >= 0) {

                    return true;

                } else {

                    $this->oFormValidation->set_message(
                        'callbackInventoryValidQuantity',
                        lang('fv_is_natural')
                    );
                    return false;
                }

            } else {

                $this->oFormValidation->set_message(
                    'callbackInventoryValidQuantity',
                    'This must be a whole number.'
                );
                return false;
            }

            return true;

        } else {

            $this->oFormValidation->set_message(
                'callbackInventoryValidQuantity',
                'This is not a valid quantity'
            );
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Form Validation: Validate an inventory item's SKU
     * @param  string $str The SKU to validate
     * @return boolean
     */
    public function callbackInventoryValidSku($str, $variation_id)
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

            $this->oFormValidation->set_message('callbackInventoryValidSku', 'This SKU is already in use.');
            return false;

        } else {

            return true;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * @todo: complete this callback, I'm sure it used to be here.
     * @param  string  $str The string to validate
     * @return boolean
     */
    public function callbackInventoryValidStockStatus($str)
    {
        return true;
    }
}
