<?php

/**
 * Other shop managers
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

class Manage extends BaseAdmin
{
    protected $isModal;
    protected $oFormValidation;

    // --------------------------------------------------------------------------

    /**
     * Announces this controller's navGroups
     * @return stdClass
     */
    public static function announce()
    {
        if (userHasPermission('admin:shop:manage:.*')) {

            $oNavGroup = Factory::factory('Nav', 'nailsapp/module-admin');
            $oNavGroup->setLabel('Shop');
            $oNavGroup->setIcon('fa-shopping-cart');
            $oNavGroup->addAction('Other Managers');
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

        //  Attributes
        $permissions['attribute:manage'] = 'Attribute: Manage';
        $permissions['attribute:create'] = 'Attribute: Create';
        $permissions['attribute:edit']   = 'Attribute: Edit';
        $permissions['attribute:delete'] = 'Attribute: Delete';

        //  Brands
        $permissions['brand:manage'] = 'Brand: Manage';
        $permissions['brand:create'] = 'Brand: Create';
        $permissions['brand:edit']   = 'Brand: Edit';
        $permissions['brand:delete'] = 'Brand: Delete';

        //  Suppliers
        $permissions['supplier:manage'] = 'Supplier: Manage';
        $permissions['supplier:create'] = 'Supplier: Create';
        $permissions['supplier:edit']   = 'Supplier: Edit';
        $permissions['supplier:delete'] = 'Supplier: Delete';

        //  Categories
        $permissions['category:manage'] = 'Category: Manage';
        $permissions['category:create'] = 'Category: Create';
        $permissions['category:edit']   = 'Category: Edit';
        $permissions['category:delete'] = 'Category: Delete';

        //  Collections
        $permissions['collection:manage'] = 'Collection: Manage';
        $permissions['collection:create'] = 'Collection: Create';
        $permissions['collection:edit']   = 'Collection: Edit';
        $permissions['collection:delete'] = 'Collection: Delete';

        //  Ranges
        $permissions['range:manage'] = 'Range: Manage';
        $permissions['range:create'] = 'Range: Create';
        $permissions['range:edit']   = 'Range: Edit';
        $permissions['range:delete'] = 'Range: Delete';

        //  Tags
        $permissions['tag:manage'] = 'Tag: Manage';
        $permissions['tag:create'] = 'Tag: Create';
        $permissions['tag:edit']   = 'Tag: Edit';
        $permissions['tag:delete'] = 'Tag: Delete';

        //  Tax Rates
        $permissions['taxRate:manage'] = 'Tax Rate: Manage';
        $permissions['taxRate:create'] = 'Tax Rate: Create';
        $permissions['taxRate:edit']   = 'Tax Rate: Edit';
        $permissions['taxRate:delete'] = 'Tax Rate: Delete';

        //  Product Types
        $permissions['productType:manage'] = 'Product Type: Manage';
        $permissions['productType:create'] = 'Product Type: Create';
        $permissions['productType:edit']   = 'Product Type: Edit';
        $permissions['productType:delete'] = 'Product Type: Delete';

        //  Product Type Meta Fields
        $permissions['productTypeMeta:manage'] = 'Product Type Meta: Manage';
        $permissions['productTypeMeta:create'] = 'Product Type Meta: Create';
        $permissions['productTypeMeta:edit']   = 'Product Type Meta: Edit';
        $permissions['productTypeMeta:delete'] = 'Product Type Meta: Delete';

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
        $this->oFormValidation = Factory::service('FormValidation');

        // --------------------------------------------------------------------------

        //  Used by redirects and some views to keep the user in the modal
        $this->data['isModal'] = $this->input->get('isModal') ? '?isModal=1' : false;
        $this->isModal         = $this->data['isModal'];

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
     * Browse other shop managers
     * @return void
     */
    public function index()
    {
        Helper::loadView('index');
    }

    // --------------------------------------------------------------------------

    /**
     * Manage product attributes
     * @return void
     */
    public function attribute()
    {
        if (!userHasPermission('admin:shop:manage:attribute:manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load model
        $this->load->model('shop/shop_attribute_model');

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Manage &rsaquo; Attributes ';
        $this->routeRequest('attribute');
    }

    // --------------------------------------------------------------------------

    /**
     * Browse product attributes
     * @return void
     */
    protected function attributeIndex()
    {
        //  Get the table prefix from the model
        $tablePrefix = $this->shop_attribute_model->getTablePrefix();

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
            $tablePrefix . '.label'   => 'Label',
            $tablePrefix . '.created' => 'Created',
            $tablePrefix . '.modified' => 'Modified'
        );

        // --------------------------------------------------------------------------

        //  Define the $data variable for the queries
        $data = array(
            'include_count' => true,
            'sort' => array(
                array($sortOn, $sortOrder)
            ),
            'keywords' => $keywords
        );

        // --------------------------------------------------------------------------

        //  Get the items for the page
        $totalRows                = $this->shop_attribute_model->countAll($data);
        $this->data['attributes'] = $this->shop_attribute_model->getAll($page, $perPage, $data);

        //  Set Search and Pagination objects for the view
        $this->data['search']     = Helper::searchObject(true, $sortColumns, $sortOn, $sortOrder, $perPage, $keywords);
        $this->data['pagination'] = Helper::paginationObject($page, $perPage, $totalRows);

        // --------------------------------------------------------------------------

        //  Add header button
        if (userHasPermission('admin:shop:manage:attribute:create')) {

            Helper::addHeaderButton(
                'admin/shop/manage/attribute/create' . $this->isModal,
                'Create Attribute'
            );
        }

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('attribute/index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a new product attribute
     * @return void
     */
    protected function attributeCreate()
    {
        if (!userHasPermission('admin:shop:manage:attribute:create')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->oFormValidation->set_rules('label', '', 'xss_clean|required');
            $this->oFormValidation->set_rules('description', '', 'xss_clean');

            $this->oFormValidation->set_message('required', lang('fv_required'));

            if ($this->oFormValidation->run()) {

                $data                = array();
                $data['label']       = $this->input->post('label');
                $data['description'] = $this->input->post('description');

                if ($this->shop_attribute_model->create($data)) {

                    $this->session->set_flashdata('success', 'Attribute created successfully.');
                    redirect('admin/shop/manage/attribute' . $this->isModal);

                } else {

                    $this->data['error']  = 'There was a problem creating the Attribute. ';
                    $this->data['error'] .= $this->shop_category_model->lastError();
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
        $this->data['attributes'] = $this->shop_attribute_model->getAll();

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('attribute/edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a product attribute
     * @return void
     */
    protected function attributeEdit()
    {
        if (!userHasPermission('admin:shop:manage:attribute:edit')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $this->data['attribute'] = $this->shop_attribute_model->getById($this->uri->segment(6));

        if (empty($this->data['attribute'])) {

            show_404();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->oFormValidation->set_rules('label', '', 'xss_clean|required');
            $this->oFormValidation->set_rules('description', '', 'xss_clean');

            $this->oFormValidation->set_message('required', lang('fv_required'));

            if ($this->oFormValidation->run()) {

                $aUpdateData                = array();
                $aUpdateData['label']       = $this->input->post('label');
                $aUpdateData['description'] = $this->input->post('description');

                if ($this->shop_attribute_model->update($this->data['attribute']->id, $aUpdateData)) {

                    $this->session->set_flashdata('success', 'Attribute saved successfully.');
                    redirect('admin/shop/manage/attribute' . $this->isModal);

                } else {

                    $this->data['error']  = 'There was a problem saving the Attribute. ';
                    $this->data['error'] .= $this->shop_attribute_model->lastError();
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
        $this->data['attributes'] = $this->shop_attribute_model->getAll();

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('attribute/edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a product attribute
     * @return void
     */
    protected function attributeDelete()
    {
        if (!userHasPermission('admin:shop:manage:attribute:delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id = $this->uri->segment(6);

        if ($this->shop_attribute_model->delete($id)) {

            $status  = 'success';
            $message = 'Attribute was deleted successfully.';

        } else {

            $status  = 'error';
            $message = 'There was a problem deleting the attribute. ' . $this->shop_attribute_model->lastError();
        }

        $this->session->set_flashdata($status, $message);
        redirect('admin/shop/manage/attribute' . $this->isModal);
    }

    // --------------------------------------------------------------------------

    /**
     * Manage product  brands
     * @return void
     */
    public function brand()
    {
        if (!userHasPermission('admin:shop:manage:brand:manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load model
        $this->load->model('shop/shop_brand_model');

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Manage &rsaquo; Brands ';
        $this->routeRequest('brand');
    }

    // --------------------------------------------------------------------------

    /**
     * Browse product brands
     * @return void
     */
    protected function brandIndex()
    {
        //  Get the table prefix from the model
        $tablePrefix = $this->shop_brand_model->getTablePrefix();

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
            $tablePrefix . '.label'   => 'Label',
            $tablePrefix . '.created' => 'Created',
            $tablePrefix . '.modified' => 'Modified'
        );

        // --------------------------------------------------------------------------

        //  Define the $data variable for the queries
        $data = array(
            'include_count' => true,
            'only_active' => false,
            'sort' => array(
                array($sortOn, $sortOrder)
            ),
            'keywords' => $keywords
        );

        // --------------------------------------------------------------------------

        //  Get the items for the page
        $totalRows            = $this->shop_brand_model->countAll($data);
        $this->data['brands'] = $this->shop_brand_model->getAll($page, $perPage, $data);

        //  Set Search and Pagination objects for the view
        $this->data['search']     = Helper::searchObject(true, $sortColumns, $sortOn, $sortOrder, $perPage, $keywords);
        $this->data['pagination'] = Helper::paginationObject($page, $perPage, $totalRows);

        // --------------------------------------------------------------------------

        //  Add header button
        if (userHasPermission('admin:shop:manage:brand:create')) {

            Helper::addHeaderButton(
                'admin/shop/manage/brand/create' . $this->isModal,
                'Create Brand'
            );
        }

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('brand/index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a product brand
     * @return void
     */
    protected function brandCreate()
    {
        if (!userHasPermission('admin:shop:manage:brand:create')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->oFormValidation->set_rules('label', '', 'xss_clean|required');
            $this->oFormValidation->set_rules('logo_id', '', 'xss_clean');
            $this->oFormValidation->set_rules('cover_id', '', 'xss_clean');
            $this->oFormValidation->set_rules('description', '', 'xss_clean');
            $this->oFormValidation->set_rules('is_active', '', 'xss_clean');
            $this->oFormValidation->set_rules('seo_title', '', 'xss_clean|max_length[150]');
            $this->oFormValidation->set_rules('seo_description', '', 'xss_clean|max_length[300]');
            $this->oFormValidation->set_rules('seo_keywords', '', 'xss_clean|max_length[150]');

            $this->oFormValidation->set_message('required', lang('fv_required'));
            $this->oFormValidation->set_message('max_length', lang('fv_max_length'));

            if ($this->oFormValidation->run()) {

                $data                    = array();
                $data['label']           = $this->input->post('label');
                $data['logo_id']         = (int) $this->input->post('logo_id') ? (int) $this->input->post('logo_id') : null;
                $data['cover_id']        = (int) $this->input->post('cover_id') ? (int) $this->input->post('cover_id') : null;
                $data['description']     = $this->input->post('description');
                $data['is_active']       = (bool) $this->input->post('is_active');
                $data['seo_title']       = $this->input->post('seo_title');
                $data['seo_description'] = $this->input->post('seo_description');
                $data['seo_keywords']    = $this->input->post('seo_keywords');

                if ($this->shop_brand_model->create($data)) {

                    //  Redirect to clear form
                    $this->session->set_flashdata('success', 'Brand created successfully.');
                    redirect('admin/shop/manage/brand' . $this->isModal);

                } else {

                    $this->data['error']  = 'There was a problem creating the Brand. ';
                    $this->data['error'] .= $this->shop_brand_model->lastError();
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
        $this->data['brands'] = $this->shop_brand_model->getAll();

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('brand/edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a product brand
     * @return void
     */
    protected function brandEdit()
    {
        if (!userHasPermission('admin:shop:manage:brand:edit')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $this->data['brand'] = $this->shop_brand_model->getById($this->uri->segment(6));

        if (empty($this->data['brand'])) {

            show_404();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->oFormValidation->set_rules('label', '', 'xss_clean|required');
            $this->oFormValidation->set_rules('logo_id', '', 'xss_clean');
            $this->oFormValidation->set_rules('cover_id', '', 'xss_clean');
            $this->oFormValidation->set_rules('description', '', 'xss_clean');
            $this->oFormValidation->set_rules('is_active', '', 'xss_clean');
            $this->oFormValidation->set_rules('seo_title', '', 'xss_clean|max_length[150]');
            $this->oFormValidation->set_rules('seo_description', '', 'xss_clean|max_length[300]');
            $this->oFormValidation->set_rules('seo_keywords', '', 'xss_clean|max_length[150]');

            $this->oFormValidation->set_message('required', lang('fv_required'));
            $this->oFormValidation->set_message('max_length', lang('fv_max_length'));

            if ($this->oFormValidation->run()) {

                $data                    = array();
                $data['label']           = $this->input->post('label');
                $data['logo_id']         = (int) $this->input->post('logo_id') ? (int) $this->input->post('logo_id') : null;
                $data['cover_id']        = (int) $this->input->post('cover_id') ? (int) $this->input->post('cover_id') : null;
                $data['description']     = $this->input->post('description');
                $data['is_active']       = (bool) $this->input->post('is_active');
                $data['seo_title']       = $this->input->post('seo_title');
                $data['seo_description'] = $this->input->post('seo_description');
                $data['seo_keywords']    = $this->input->post('seo_keywords');

                if ($this->shop_brand_model->update($this->data['brand']->id, $data)) {

                    $this->session->set_flashdata('success', 'Brand saved successfully.');
                    redirect('admin/shop/manage/brand' . $this->isModal);

                } else {

                    $this->data['error']  = 'There was a problem saving the Brand. ';
                    $this->data['error'] .= $this->shop_brand_model->lastError();
                }

            } else {

                $this->data['error'] = 'There was a problem saving the Brand.';
            }
        }

        // --------------------------------------------------------------------------

        //  Page data
        $this->data['page']->title .= 'Edit &rsaquo; ' . $this->data['brand']->label;

        // --------------------------------------------------------------------------

        //  Fetch data
        $this->data['brands'] = $this->shop_brand_model->getAll();

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('brand/edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a product brand
     * @return void
     */
    protected function brandDelete()
    {
        if (!userHasPermission('admin:shop:manage:brand:delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id = $this->uri->segment(6);

        if ($this->shop_brand_model->delete($id)) {

            $status  = 'success';
            $message = 'Brand was deleted successfully.';

        } else {

            $status  = 'error';
            $message = 'There was a problem deleting the Brand. ' . $this->shop_brand_model->lastError();
        }

        $this->session->set_flashdata($status, $message);
        redirect('admin/shop/manage/brand' . $this->isModal);
    }

    // --------------------------------------------------------------------------

    /**
     * Manage product  suppliers
     * @return void
     */
    public function supplier()
    {
        if (!userHasPermission('admin:shop:manage:supplier:manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load model
        $this->load->model('shop/shop_supplier_model');

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Manage &rsaquo; Suppliers ';
        $this->routeRequest('supplier');
    }

    // --------------------------------------------------------------------------

    /**
     * Browse product suppliers
     * @return void
     */
    protected function supplierIndex()
    {
        //  Get the table prefix from the model
        $tablePrefix = $this->shop_supplier_model->getTablePrefix();

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
            $tablePrefix . '.label'   => 'Label',
            $tablePrefix . '.created' => 'Created',
            $tablePrefix . '.modified' => 'Modified'
        );

        // --------------------------------------------------------------------------

        //  Define the $data variable for the queries
        $data = array(
            'include_count' => true,
            'only_active' => false,
            'sort' => array(
                array($sortOn, $sortOrder)
            ),
            'keywords' => $keywords
        );

        // --------------------------------------------------------------------------

        //  Get the items for the page
        $totalRows            = $this->shop_supplier_model->countAll($data);
        $this->data['suppliers'] = $this->shop_supplier_model->getAll($page, $perPage, $data);

        //  Set Search and Pagination objects for the view
        $this->data['search']     = Helper::searchObject(true, $sortColumns, $sortOn, $sortOrder, $perPage, $keywords);
        $this->data['pagination'] = Helper::paginationObject($page, $perPage, $totalRows);

        // --------------------------------------------------------------------------

        //  Add header button
        if (userHasPermission('admin:shop:manage:supplier:create')) {

            Helper::addHeaderButton(
                'admin/shop/manage/supplier/create' . $this->isModal,
                'Create Supplier'
            );
        }

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('supplier/index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a product supplier
     * @return void
     */
    protected function supplierCreate()
    {
        if (!userHasPermission('admin:shop:manage:supplier:create')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->oFormValidation->set_rules('label', '', 'xss_clean|required');
            $this->oFormValidation->set_rules('is_active', '', 'xss_clean');

            $this->oFormValidation->set_message('required', lang('fv_required'));
            $this->oFormValidation->set_message('max_length', lang('fv_max_length'));

            if ($this->oFormValidation->run()) {

                $data              = array();
                $data['label']     = $this->input->post('label');
                $data['is_active'] = (bool) $this->input->post('is_active');

                if ($this->shop_supplier_model->create($data)) {

                    //  Redirect to clear form
                    $this->session->set_flashdata('success', 'Supplier created successfully.');
                    redirect('admin/shop/manage/supplier' . $this->isModal);

                } else {

                    $this->data['error']  = 'There was a problem creating the Supplier. ';
                    $this->data['error'] .= $this->shop_supplier_model->lastError();
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
        $this->data['suppliers'] = $this->shop_supplier_model->getAll();

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('supplier/edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a product supplier
     * @return void
     */
    protected function supplierEdit()
    {
        if (!userHasPermission('admin:shop:manage:supplier:edit')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $this->data['supplier'] = $this->shop_supplier_model->getById($this->uri->segment(6));

        if (empty($this->data['supplier'])) {

            show_404();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->oFormValidation->set_rules('label', '', 'xss_clean|required');
            $this->oFormValidation->set_rules('is_active', '', 'xss_clean');

            $this->oFormValidation->set_message('required', lang('fv_required'));
            $this->oFormValidation->set_message('max_length', lang('fv_max_length'));

            if ($this->oFormValidation->run()) {

                $data              = array();
                $data['label']     = $this->input->post('label');
                $data['is_active'] = (bool) $this->input->post('is_active');

                if ($this->shop_supplier_model->update($this->data['supplier']->id, $data)) {

                    $this->session->set_flashdata('success', 'Supplier saved successfully.');
                    redirect('admin/shop/manage/supplier' . $this->isModal);

                } else {

                    $this->data['error']  = 'There was a problem saving the Supplier. ';
                    $this->data['error'] .= $this->shop_supplier_model->lastError();
                }

            } else {

                $this->data['error'] = 'There was a problem saving the Supplier.';
            }
        }

        // --------------------------------------------------------------------------

        //  Page data
        $this->data['page']->title .= 'Edit &rsaquo; ' . $this->data['supplier']->label;

        // --------------------------------------------------------------------------

        //  Fetch data
        $this->data['suppliers'] = $this->shop_supplier_model->getAll();

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('supplier/edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a product supplier
     * @return void
     */
    protected function supplierDelete()
    {
        if (!userHasPermission('admin:shop:manage:supplier:delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id = $this->uri->segment(6);

        if ($this->shop_supplier_model->delete($id)) {

            $status  = 'success';
            $message = 'Supplier was deleted successfully.';

        } else {

            $status  = 'error';
            $message = 'There was a problem deleting the Supplier. ' . $this->shop_supplier_model->lastError();
        }

        $this->session->set_flashdata($status, $message);
        redirect('admin/shop/manage/supplier' . $this->isModal);
    }

    // --------------------------------------------------------------------------

    /**
     * Manage product categories
     * @return void
     */
    public function category()
    {
        if (!userHasPermission('admin:shop:manage:category:manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load model
        $this->load->model('shop/shop_category_model');

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Manage &rsaquo; Categories ';
        $this->routeRequest('category');
    }

    // --------------------------------------------------------------------------

    /**
     * Browse product categories
     * @return void
     */
    protected function categoryIndex()
    {
        //  Get the table prefix from the model
        $tablePrefix = $this->shop_category_model->getTablePrefix();

        // --------------------------------------------------------------------------

        //  Get pagination and search/sort variables
        $page      = $this->input->get('page')      ? $this->input->get('page')      : 0;
        $perPage   = $this->input->get('perPage')   ? $this->input->get('perPage')   : 50;
        $sortOn    = $this->input->get('sortOn')    ? $this->input->get('sortOn')    : $tablePrefix . '.slug';
        $sortOrder = $this->input->get('sortOrder') ? $this->input->get('sortOrder') : 'asc';
        $keywords  = $this->input->get('keywords')  ? $this->input->get('keywords')  : '';

        // --------------------------------------------------------------------------

        //  Define the sortable columns and the filters
        $sortColumns = array(
            $tablePrefix . '.slug'     => 'Label (maintain hierarchy)',
            $tablePrefix . '.label'    => 'Label',
            $tablePrefix . '.created'  => 'Created',
            $tablePrefix . '.modified' => 'Modified'
        );

        // --------------------------------------------------------------------------

        //  Define the $data variable for the queries
        $data = array(
            'include_count' => true,
            'sort' => array(
                array($sortOn, $sortOrder)
            ),
            'keywords' => $keywords
        );

        // --------------------------------------------------------------------------

        //  Get the items for the page
        $totalRows                = $this->shop_category_model->countAll($data);
        $this->data['categories'] = $this->shop_category_model->getAll($page, $perPage, $data);

        //  Set Search and Pagination objects for the view
        $this->data['search']     = Helper::searchObject(true, $sortColumns, $sortOn, $sortOrder, $perPage, $keywords);
        $this->data['pagination'] = Helper::paginationObject($page, $perPage, $totalRows);

        // --------------------------------------------------------------------------

        //  Add header button
        if (userHasPermission('admin:shop:manage:category:create')) {

            Helper::addHeaderButton(
                'admin/shop/manage/category/create' . $this->isModal,
                'Create Category'
            );
        }

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('category/index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a product category
     * @return void
     */
    protected function categoryCreate()
    {
        if (!userHasPermission('admin:shop:manage:category:create')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->oFormValidation->set_rules('label', '', 'xss_clean|required');
            $this->oFormValidation->set_rules('parent_id', '', 'xss_clean');
            $this->oFormValidation->set_rules('cover_id', '', 'xss_clean');
            $this->oFormValidation->set_rules('description', '', 'xss_clean');
            $this->oFormValidation->set_rules('seo_title', '', 'xss_clean|max_length[150]');
            $this->oFormValidation->set_rules('seo_description', '', 'xss_clean|max_length[300]');
            $this->oFormValidation->set_rules('seo_keywords', '', 'xss_clean|max_length[150]');

            $this->oFormValidation->set_message('required', lang('fv_required'));
            $this->oFormValidation->set_message('max_length', lang('fv_max_length'));

            if ($this->oFormValidation->run()) {

                $data                    = array();
                $data['label']           = $this->input->post('label');
                $data['parent_id']       = $this->input->post('parent_id');
                $data['cover_id']        = $this->input->post('cover_id');
                $data['description']     = $this->input->post('description');
                $data['seo_title']       = $this->input->post('seo_title');
                $data['seo_description'] = $this->input->post('seo_description');
                $data['seo_keywords']    = $this->input->post('seo_keywords');

                if ($this->shop_category_model->create($data)) {

                    $this->session->set_flashdata('success', 'Category created successfully.');
                    redirect('admin/shop/manage/category' . $this->isModal);

                } else {

                    $this->data['error']  = 'There was a problem creating the Category. ';
                    $this->data['error'] .= $this->shop_category_model->lastError();
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
        $this->data['categories'] = $this->shop_category_model->getAll();

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('category/edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a product category
     * @return void
     */
    protected function categoryEdit()
    {
        if (!userHasPermission('admin:shop:manage:category:edit')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $this->data['category'] = $this->shop_category_model->getById($this->uri->segment(6));

        if (empty($this->data['category'])) {

            show_404();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->oFormValidation->set_rules('label', '', 'xss_clean|required');
            $this->oFormValidation->set_rules('parent_id', '', 'xss_clean');
            $this->oFormValidation->set_rules('cover_id', '', 'xss_clean');
            $this->oFormValidation->set_rules('description', '', 'xss_clean');
            $this->oFormValidation->set_rules('seo_title', '', 'xss_clean|max_length[150]');
            $this->oFormValidation->set_rules('seo_description', '', 'xss_clean|max_length[300]');
            $this->oFormValidation->set_rules('seo_keywords', '', 'xss_clean|max_length[150]');

            $this->oFormValidation->set_message('required', lang('fv_required'));
            $this->oFormValidation->set_message('max_length', lang('fv_max_length'));

            if ($this->oFormValidation->run()) {

                $data                    = array();
                $data['label']           = $this->input->post('label');
                $data['parent_id']       = $this->input->post('parent_id');
                $data['cover_id']        = $this->input->post('cover_id');
                $data['description']     = $this->input->post('description');
                $data['seo_title']       = $this->input->post('seo_title');
                $data['seo_description'] = $this->input->post('seo_description');
                $data['seo_keywords']    = $this->input->post('seo_keywords');

                if ($this->shop_category_model->update($this->data['category']->id, $data)) {

                    $this->session->set_flashdata('success', 'Category saved successfully.');
                    redirect('admin/shop/manage/category' . $this->isModal);

                } else {

                    $this->data['error']  = 'There was a problem saving the Category. ';
                    $this->data['error'] .= $this->shop_category_model->lastError();
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
        $this->data['categories'] = $this->shop_category_model->getAll();

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('category/edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a product category
     * @return void
     */
    protected function categoryDelete()
    {
        if (!userHasPermission('admin:shop:manage:category:delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id = $this->uri->segment(6);

        if ($this->shop_category_model->delete($id)) {

            $status  = 'success';
            $message = 'Category was deleted successfully.';

        } else {

            $status  = 'error';
            $message = 'There was a problem deleting the Category. ' . $this->shop_category_model->lastError();
        }

        $this->session->set_flashdata($status, $message);
        redirect('admin/shop/manage/category' . $this->isModal);
    }

    // --------------------------------------------------------------------------

    /**
     * Manage product collections
     * @return void
     */
    public function collection()
    {
        if (!userHasPermission('admin:shop:manage:collection:manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load model
        $this->load->model('shop/shop_collection_model');

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Manage &rsaquo; Collections ';
        $this->routeRequest('collection');
    }

    // --------------------------------------------------------------------------

    /**
     * Browse product collections
     * @return void
     */
    protected function collectionIndex()
    {
        //  Get the table prefix from the model
        $tablePrefix = $this->shop_collection_model->getTablePrefix();

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
            $tablePrefix . '.label'   => 'Label',
            $tablePrefix . '.created' => 'Created',
            $tablePrefix . '.modified' => 'Modified'
        );

        // --------------------------------------------------------------------------

        //  Define the $data variable for the queries
        $data = array(
            'only_active' => false,
            'include_count' => true,
            'sort' => array(
                array($sortOn, $sortOrder)
            ),
            'keywords' => $keywords
        );

        // --------------------------------------------------------------------------

        //  Get the items for the page
        $totalRows                 = $this->shop_collection_model->countAll($data);
        $this->data['collections'] = $this->shop_collection_model->getAll($page, $perPage, $data);

        //  Set Search and Pagination objects for the view
        $this->data['search']     = Helper::searchObject(true, $sortColumns, $sortOn, $sortOrder, $perPage, $keywords);
        $this->data['pagination'] = Helper::paginationObject($page, $perPage, $totalRows);

        // --------------------------------------------------------------------------

        //  Add header button
        if (userHasPermission('admin:shop:manage:collection:create')) {

            Helper::addHeaderButton(
                'admin/shop/manage/collection/create' . $this->isModal,
                'Create Collection'
            );
        }

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('collection/index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a product collection
     * @return void
     */
    protected function collectionCreate()
    {
        if (!userHasPermission('admin:shop:manage:collection:create')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->oFormValidation->set_rules('label', '', 'xss_clean|required');
            $this->oFormValidation->set_rules('cover_id', '', 'xss_clean');
            $this->oFormValidation->set_rules('description', '', 'xss_clean');
            $this->oFormValidation->set_rules('is_active', '', 'xss_clean');
            $this->oFormValidation->set_rules('seo_title', '', 'xss_clean|max_length[150]');
            $this->oFormValidation->set_rules('seo_description', '', 'xss_clean|max_length[300]');
            $this->oFormValidation->set_rules('seo_keywords', '', 'xss_clean|max_length[150]');

            $this->oFormValidation->set_message('required', lang('fv_required'));
            $this->oFormValidation->set_message('max_length', lang('fv_max_length'));

            if ($this->oFormValidation->run()) {

                $data                    = array();
                $data['label']           = $this->input->post('label');
                $data['cover_id']        = $this->input->post('cover_id');
                $data['description']     = $this->input->post('description');
                $data['seo_title']       = $this->input->post('seo_title');
                $data['seo_description'] = $this->input->post('seo_description');
                $data['seo_keywords']    = $this->input->post('seo_keywords');
                $data['is_active']       = (bool) $this->input->post('is_active');

                if ($this->shop_collection_model->create($data)) {

                    $this->session->set_flashdata('success', 'Collection created successfully.');
                    redirect('admin/shop/manage/collection' . $this->isModal);

                } else {

                    $this->data['error']  = 'There was a problem creating the Collection. ';
                    $this->data['error'] .= $this->shop_collection_model->lastError();
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
        $this->data['collections'] = $this->shop_collection_model->getAll();

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('collection/edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a product collection
     * @return void
     */
    protected function collectionEdit()
    {
        if (!userHasPermission('admin:shop:manage:collection:edit')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $this->data['collection'] = $this->shop_collection_model->getById($this->uri->segment(6));

        if (empty($this->data['collection'])) {

            show_404();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->oFormValidation->set_rules('label', '', 'xss_clean|required');
            $this->oFormValidation->set_rules('cover_id', '', 'xss_clean');
            $this->oFormValidation->set_rules('description', '', 'xss_clean');
            $this->oFormValidation->set_rules('is_active', '', 'xss_clean');
            $this->oFormValidation->set_rules('seo_title', '', 'xss_clean|max_length[150]');
            $this->oFormValidation->set_rules('seo_description', '', 'xss_clean|max_length[300]');
            $this->oFormValidation->set_rules('seo_keywords', '', 'xss_clean|max_length[150]');

            $this->oFormValidation->set_message('required', lang('fv_required'));
            $this->oFormValidation->set_message('max_length', lang('fv_max_length'));

            if ($this->oFormValidation->run()) {

                $data                    = array();
                $data['label']           = $this->input->post('label');
                $data['cover_id']        = $this->input->post('cover_id');
                $data['description']     = $this->input->post('description');
                $data['seo_title']       = $this->input->post('seo_title');
                $data['seo_description'] = $this->input->post('seo_description');
                $data['seo_keywords']    = $this->input->post('seo_keywords');
                $data['is_active']       = (bool) $this->input->post('is_active');

                if ($this->shop_collection_model->update($this->data['collection']->id, $data)) {

                    $this->session->set_flashdata('success', 'Collection saved successfully.');
                    redirect('admin/shop/manage/collection' . $this->isModal);

                } else {

                    $this->data['error']  = 'There was a problem saving the Collection. ';
                    $this->data['error'] .= $this->shop_collection_model->lastError();
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
        $this->data['collections'] = $this->shop_collection_model->getAll();

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('collection/edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a product collection
     * @return void
     */
    protected function collectionDelete()
    {
        if (!userHasPermission('admin:shop:manage:collection:delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id = $this->uri->segment(6);

        if ($this->shop_collection_model->delete($id)) {

            $status  = 'success';
            $message = 'Collection was deleted successfully.';

        } else {

            $status  = 'error';
            $message = 'There was a problem deleting the Collection. ' . $this->shop_collection_model->lastError();
        }

        $this->session->set_flashdata($status, $message);
        redirect('admin/shop/manage/collection' . $this->isModal);
    }

    // --------------------------------------------------------------------------

    /**
     * Manage product ranges
     * @return void
     */
    public function range()
    {
        if (!userHasPermission('admin:shop:manage:range:manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load model
        $this->load->model('shop/shop_range_model');

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Manage &rsaquo; Ranges ';
        $this->routeRequest('range');
    }

    // --------------------------------------------------------------------------

    /**
     * Browse product ranges
     * @return void
     */
    protected function rangeIndex()
    {
        //  Get the table prefix from the model
        $tablePrefix = $this->shop_range_model->getTablePrefix();

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
            $tablePrefix . '.label'   => 'Label',
            $tablePrefix . '.created' => 'Created',
            $tablePrefix . '.modified' => 'Modified'
        );

        // --------------------------------------------------------------------------

        //  Define the $data variable for the queries
        $data = array(
            'include_count' => true,
            'sort' => array(
                array($sortOn, $sortOrder)
            ),
            'keywords' => $keywords
        );

        // --------------------------------------------------------------------------

        //  Get the items for the page
        $totalRows            = $this->shop_range_model->countAll($data);
        $this->data['ranges'] = $this->shop_range_model->getAll($page, $perPage, $data);

        //  Set Search and Pagination objects for the view
        $this->data['search']     = Helper::searchObject(true, $sortColumns, $sortOn, $sortOrder, $perPage, $keywords);
        $this->data['pagination'] = Helper::paginationObject($page, $perPage, $totalRows);

        // --------------------------------------------------------------------------

        //  Add header button
        if (userHasPermission('admin:shop:manage:range:create')) {

            Helper::addHeaderButton(
                'admin/shop/manage/range/create' . $this->isModal,
                'Create Range'
            );
        }

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('range/index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a product range
     * @return void
     */
    protected function rangeCreate()
    {
        if (!userHasPermission('admin:shop:manage:range:create')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->oFormValidation->set_rules('label', '', 'xss_clean|required');
            $this->oFormValidation->set_rules('cover_id', '', 'xss_clean');
            $this->oFormValidation->set_rules('description', '', 'xss_clean');
            $this->oFormValidation->set_rules('is_active', '', 'xss_clean');
            $this->oFormValidation->set_rules('seo_title', '', 'xss_clean|max_length[150]');
            $this->oFormValidation->set_rules('seo_description', '', 'xss_clean|max_length[300]');
            $this->oFormValidation->set_rules('seo_keywords', '', 'xss_clean|max_length[150]');

            $this->oFormValidation->set_message('required', lang('fv_required'));
            $this->oFormValidation->set_message('max_length', lang('fv_max_length'));

            if ($this->oFormValidation->run()) {

                $data                    = array();
                $data['label']           = $this->input->post('label');
                $data['cover_id']        = $this->input->post('cover_id');
                $data['description']     = $this->input->post('description');
                $data['seo_title']       = $this->input->post('seo_title');
                $data['seo_description'] = $this->input->post('seo_description');
                $data['seo_keywords']    = $this->input->post('seo_keywords');
                $data['is_active']       = (bool) $this->input->post('is_active');

                if ($this->shop_range_model->create($data)) {

                    $this->session->set_flashdata('success', 'Range created successfully.');
                    redirect('admin/shop/manage/range' . $this->isModal);

                } else {

                    $this->data['error']  = 'There was a problem creating the Range. ';
                    $this->data['error'] .= $this->shop_range_model->lastError();
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
        $this->data['ranges'] = $this->shop_range_model->getAll();

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('range/edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a product range
     * @return void
     */
    protected function rangeEdit()
    {
        if (!userHasPermission('admin:shop:manage:range:edit')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $this->data['range'] = $this->shop_range_model->getById($this->uri->segment(6));

        if (empty($this->data['range'])) {

            show_404();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->oFormValidation->set_rules('label', '', 'xss_clean|required');
            $this->oFormValidation->set_rules('cover_id', '', 'xss_clean');
            $this->oFormValidation->set_rules('description', '', 'xss_clean');
            $this->oFormValidation->set_rules('is_active', '', 'xss_clean');
            $this->oFormValidation->set_rules('seo_title', '', 'xss_clean|max_length[150]');
            $this->oFormValidation->set_rules('seo_description', '', 'xss_clean|max_length[300]');
            $this->oFormValidation->set_rules('seo_keywords', '', 'xss_clean|max_length[150]');

            $this->oFormValidation->set_message('required', lang('fv_required'));
            $this->oFormValidation->set_message('max_length', lang('fv_max_length'));

            if ($this->oFormValidation->run()) {

                $data                    = array();
                $data['label']           = $this->input->post('label');
                $data['cover_id']        = $this->input->post('cover_id');
                $data['description']     = $this->input->post('description');
                $data['seo_title']       = $this->input->post('seo_title');
                $data['seo_description'] = $this->input->post('seo_description');
                $data['seo_keywords']    = $this->input->post('seo_keywords');
                $data['is_active']       = (bool) $this->input->post('is_active');

                if ($this->shop_range_model->update($this->data['range']->id, $data)) {

                    $this->session->set_flashdata('success', 'Range saved successfully.');
                    redirect('admin/shop/manage/range' . $this->isModal);

                } else {

                    $this->data['error']  = 'There was a problem saving the Range. ';
                    $this->data['error'] .= $this->shop_range_model->lastError();
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
        $this->data['ranges'] = $this->shop_range_model->getAll();

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('range/edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a product range
     * @return void
     */
    protected function rangeDelete()
    {
        if (!userHasPermission('admin:shop:manage:range:delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id = $this->uri->segment(6);

        if ($this->shop_range_model->delete($id)) {

            $status  = 'success';
            $message = 'Range was deleted successfully.';

        } else {

            $status  = 'error';
            $message = 'There was a problem deleting the Range. ' . $this->shop_range_model->lastError();
        }

        $this->session->set_flashdata($status, $message);
        redirect('admin/shop/manage/range' . $this->isModal);
    }

    // --------------------------------------------------------------------------

    /**
     * Manage product tags
     * @return void
     */
    public function tag()
    {
        if (!userHasPermission('admin:shop:manage:tag:manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load model
        $this->load->model('shop/shop_tag_model');

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Manage &rsaquo; Tags ';
        $this->routeRequest('tag');
    }

    // --------------------------------------------------------------------------

    /**
     * Browse product tags
     * @return void
     */
    protected function tagIndex()
    {
        //  Get the table prefix from the model
        $tablePrefix = $this->shop_tag_model->getTablePrefix();

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
            $tablePrefix . '.label'   => 'Label',
            $tablePrefix . '.created' => 'Created',
            $tablePrefix . '.modified' => 'Modified'
        );

        // --------------------------------------------------------------------------

        //  Define the $data variable for the queries
        $data = array(
            'include_count' => true,
            'sort' => array(
                array($sortOn, $sortOrder)
            ),
            'keywords' => $keywords
        );

        // --------------------------------------------------------------------------

        //  Get the items for the page
        $totalRows          = $this->shop_tag_model->countAll($data);
        $this->data['tags'] = $this->shop_tag_model->getAll($page, $perPage, $data);

        //  Set Search and Pagination objects for the view
        $this->data['search']     = Helper::searchObject(true, $sortColumns, $sortOn, $sortOrder, $perPage, $keywords);
        $this->data['pagination'] = Helper::paginationObject($page, $perPage, $totalRows);

        // --------------------------------------------------------------------------

        //  Add header button
        if (userHasPermission('admin:shop:manage:tag:create')) {

            Helper::addHeaderButton(
                'admin/shop/manage/tag/create' . $this->isModal,
                'Create Tag'
            );
        }

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('tag/index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a product tag
     * @return void
     */
    protected function tagCreate()
    {
        if (!userHasPermission('admin:shop:manage:tag:create')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->oFormValidation->set_rules('label', '', 'xss_clean|required');
            $this->oFormValidation->set_rules('cover_id', '', 'xss_clean');
            $this->oFormValidation->set_rules('description', '', 'xss_clean');
            $this->oFormValidation->set_rules('seo_title', '', 'xss_clean|max_length[150]');
            $this->oFormValidation->set_rules('seo_description', '', 'xss_clean|max_length[300]');
            $this->oFormValidation->set_rules('seo_keywords', '', 'xss_clean|max_length[150]');

            $this->oFormValidation->set_message('required', lang('fv_required'));
            $this->oFormValidation->set_message('max_length', lang('fv_max_length'));

            if ($this->oFormValidation->run()) {

                $data                    = array();
                $data['label']           = $this->input->post('label');
                $data['cover_id']        = $this->input->post('cover_id');
                $data['description']     = $this->input->post('description');
                $data['seo_title']       = $this->input->post('seo_title');
                $data['seo_description'] = $this->input->post('seo_description');
                $data['seo_keywords']    = $this->input->post('seo_keywords');

                if ($this->shop_tag_model->create($data)) {

                    $this->session->set_flashdata('success', 'Tag created successfully.');
                    redirect('admin/shop/manage/tag' . $this->isModal);

                } else {

                    $this->data['error']  = 'There was a problem creating the Tag. ';
                    $this->data['error'] .= $this->shop_tag_model->lastError();
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
        $this->data['tags'] = $this->shop_tag_model->getAll();

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('tag/edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a product tag
     * @return void
     */
    protected function tagEdit()
    {
        if (!userHasPermission('admin:shop:manage:tag:edit')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $this->data['tag'] = $this->shop_tag_model->getById($this->uri->segment(6));

        if (empty($this->data['tag'])) {

            show_404();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->oFormValidation->set_rules('label', '', 'xss_clean|required');
            $this->oFormValidation->set_rules('cover_id', '', 'xss_clean');
            $this->oFormValidation->set_rules('description', '', 'xss_clean');
            $this->oFormValidation->set_rules('seo_title', '', 'xss_clean|max_length[150]');
            $this->oFormValidation->set_rules('seo_description', '', 'xss_clean|max_length[300]');
            $this->oFormValidation->set_rules('seo_keywords', '', 'xss_clean|max_length[150]');

            $this->oFormValidation->set_message('required', lang('fv_required'));
            $this->oFormValidation->set_message('max_length', lang('fv_max_length'));

            if ($this->oFormValidation->run()) {

                $data                    = array();
                $data['label']           = $this->input->post('label');
                $data['cover_id']        = $this->input->post('cover_id');
                $data['description']     = $this->input->post('description');
                $data['seo_title']       = $this->input->post('seo_title');
                $data['seo_description'] = $this->input->post('seo_description');
                $data['seo_keywords']    = $this->input->post('seo_keywords');

                if ($this->shop_tag_model->update($this->data['tag']->id, $data)) {

                    $this->session->set_flashdata('success', 'Tag saved successfully.');
                    redirect('admin/shop/manage/tag' . $this->isModal);

                } else {

                    $this->data['error']  = 'There was a problem saving the Tag. ';
                    $this->data['error'] .= $this->shop_tag_model->lastError();
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
        $this->data['tags'] = $this->shop_tag_model->getAll();

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('tag/edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a product tag
     * @return void
     */
    protected function tagDelete()
    {
        if (!userHasPermission('admin:shop:manage:tag:delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id = $this->uri->segment(6);

        if ($this->shop_tag_model->delete($id)) {

            $status  = 'success';
            $message = 'Tag was deleted successfully.';

        } else {

            $status  = 'error';
            $message = 'There was a problem deleting the Tag. ' . $this->shop_tag_model->lastError();
        }

        $this->session->set_flashdata($status, $message);
        redirect('admin/shop/manage/tag' . $this->isModal);
    }

    // --------------------------------------------------------------------------

    /**
     * Manage product tax rates
     * @return void
     */
    public function taxRate()
    {
        if (!userHasPermission('admin:shop:manage:tax:rate_manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load model
        $this->load->model('shop/shop_tax_rate_model');

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Manage &rsaquo; Tax Rates ';
        $this->routeRequest('taxRate');
    }

    // --------------------------------------------------------------------------

    /**
     * Browse product tax rates
     * @return void
     */
    protected function taxRateIndex()
    {
        //  Get the table prefix from the model
        $tablePrefix = $this->shop_tax_rate_model->getTablePrefix();

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
            $tablePrefix . '.label'   => 'Label',
            $tablePrefix . '.created' => 'Created',
            $tablePrefix . '.modified' => 'Modified'
        );

        // --------------------------------------------------------------------------

        //  Define the $data variable for the queries
        $data = array(
            'include_count' => true,
            'sort' => array(
                array($sortOn, $sortOrder)
            ),
            'keywords' => $keywords
        );

        // --------------------------------------------------------------------------

        //  Get the items for the page
        $totalRows              = $this->shop_tax_rate_model->countAll($data);
        $this->data['taxRates'] = $this->shop_tax_rate_model->getAll($page, $perPage, $data);

        //  Set Search and Pagination objects for the view
        $this->data['search']     = Helper::searchObject(true, $sortColumns, $sortOn, $sortOrder, $perPage, $keywords);
        $this->data['pagination'] = Helper::paginationObject($page, $perPage, $totalRows);

        // --------------------------------------------------------------------------

        //  Add header button
        if (userHasPermission('admin:shop:manage:tax:rate_create')) {

            Helper::addHeaderButton(
                'admin/shop/manage/taxRate/create' . $this->isModal,
                'Create Tax Rate'
            );
        }

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('taxRate/index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a product tax rate
     * @return void
     */
    protected function taxRateCreate()
    {
        if (!userHasPermission('admin:shop:manage:tax:rate_create')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->oFormValidation->set_rules('label', '', 'xss_clean|required');
            $this->oFormValidation->set_rules('rate', '', 'xss_clean|required|in_range[0-1]');

            $this->oFormValidation->set_message('required', lang('fv_required'));
            $this->oFormValidation->set_message('in_range', lang('fv_in_range'));

            if ($this->oFormValidation->run()) {

                $aCreateData = array(
                    'label' => $this->input->post('label'),
                    'rate'  => $this->input->post('rate')
                );

                if ($this->shop_tax_rate_model->create($aCreateData)) {

                    $this->session->set_flashdata('success', 'Tax Rate created successfully.');
                    redirect('admin/shop/manage/taxRate' . $this->isModal);

                } else {

                    $this->data['error']  = 'There was a problem creating the Tax Rate. ';
                    $this->data['error'] .= $this->shop_tax_rate_model->lastError();
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
        $this->data['taxRates'] = $this->shop_tax_rate_model->getAll();

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('taxRate/edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a product tax rate
     * @return void
     */
    protected function taxRateEdit()
    {
        if (!userHasPermission('admin:shop:manage:tax:rate_edit')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $this->data['tax_rate'] = $this->shop_tax_rate_model->getById($this->uri->segment(6));

        if (empty($this->data['tax_rate'])) {

            show_404();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->oFormValidation->set_rules('label', '', 'xss_clean|required');
            $this->oFormValidation->set_rules('rate', '', 'xss_clean|required|in_range[0-1]');

            $this->oFormValidation->set_message('required', lang('fv_required'));
            $this->oFormValidation->set_message('in_range', lang('fv_in_range'));

            if ($this->oFormValidation->run()) {

                $aUpdateData          = array();
                $aUpdateData['label'] = $this->input->post('label');
                $aUpdateData['rate']  = (float) $this->input->post('rate');

                if ($this->shop_tax_rate_model->update($this->data['tax_rate']->id, $aUpdateData)) {

                    $this->session->set_flashdata('success', 'Tax Rate saved successfully.');
                    redirect('admin/shop/manage/taxRate' . $this->isModal);

                } else {

                    $this->data['error']  = 'There was a problem saving the Tax Rate. ';
                    $this->data['error'] .= $this->shop_tax_rate_model->lastError();
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
        $this->data['taxRates'] = $this->shop_tax_rate_model->getAll();

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('taxRate/edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a product tax rate
     * @return void
     */
    protected function taxRateDelete()
    {
        if (!userHasPermission('admin:shop:manage:tax:rate_delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id = $this->uri->segment(6);

        if ($this->shop_tax_rate_model->delete($id)) {

            $status  = 'success';
            $message = 'Tax Rate was deleted successfully.';

        } else {

            $status  = 'error';
            $message = 'There was a problem deleting the Tax Rate. ' . $this->shop_tax_rate_model->lastError();
        }

        $this->session->set_flashdata($status, $message);
        redirect('admin/shop/manage/taxRate' . $this->isModal);
    }

    // --------------------------------------------------------------------------

    /**
     * Manage product types
     * @return void
     */
    public function productType()
    {
        if (!userHasPermission('admin:shop:manage:product:type_manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load model
        $this->load->model('shop/shop_product_type_model');

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Manage &rsaquo; Product Types ';
        $this->routeRequest('productType');
    }

    // --------------------------------------------------------------------------

    /**
     * Browse product types
     * @return void
     */
    protected function productTypeIndex()
    {
        //  Get the table prefix from the model
        $tablePrefix = $this->shop_product_type_model->getTablePrefix();

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
            $tablePrefix . '.label'   => 'Label',
            $tablePrefix . '.created' => 'Created',
            $tablePrefix . '.modified' => 'Modified'
        );

        // --------------------------------------------------------------------------

        //  Define the $data variable for the queries
        $data = array(
            'include_count' => true,
            'sort' => array(
                array($sortOn, $sortOrder)
            ),
            'keywords' => $keywords
        );

        // --------------------------------------------------------------------------

        //  Get the items for the page
        $totalRows                 = $this->shop_product_type_model->countAll($data);
        $this->data['productTypes'] = $this->shop_product_type_model->getAll($page, $perPage, $data);

        //  Set Search and Pagination objects for the view
        $this->data['search']     = Helper::searchObject(true, $sortColumns, $sortOn, $sortOrder, $perPage, $keywords);
        $this->data['pagination'] = Helper::paginationObject($page, $perPage, $totalRows);

        // --------------------------------------------------------------------------

        //  Add header button
        if (userHasPermission('admin:shop:manage:product:type_create')) {

            Helper::addHeaderButton(
                'admin/shop/manage/productType/create' . $this->isModal,
                'Create Product Type'
            );
        }

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('productType/index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a product type
     * @return void
     */
    protected function productTypeCreate()
    {
        if (!userHasPermission('admin:shop:manage:product:type_create')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->oFormValidation->set_rules('label', '', 'xss_clean|required|is_unique[' . NAILS_DB_PREFIX . 'shop_product_type.label]');
            $this->oFormValidation->set_rules('description', '', 'xss_clean');
            $this->oFormValidation->set_rules('is_physical', '', 'xss_clean');
            $this->oFormValidation->set_rules('ipn_method', '', 'xss_clean');
            $this->oFormValidation->set_rules('max_per_order', '', 'xss_clean');
            $this->oFormValidation->set_rules('max_variations', '', 'xss_clean');

            $this->oFormValidation->set_message('required', lang('fv_required'));
            $this->oFormValidation->set_message('is_unique', lang('fv_is_unique'));

            if ($this->oFormValidation->run()) {

                $aCreateData = array(
                    'label'          => $this->input->post('label'),
                    'description'    => $this->input->post('description'),
                    'is_physical'    => (bool) $this->input->post('is_physical'),
                    'ipn_method'     => $this->input->post('ipn_method'),
                    'max_per_order'  => (int) $this->input->post('max_per_order'),
                    'max_variations' => (int) $this->input->post('max_variations')
                );

                if ($this->shop_product_type_model->create($aCreateData)) {

                    //  Redirect to clear form
                    $this->session->set_flashdata('success', 'Product Type created successfully.');
                    redirect('admin/shop/manage/productType' . $this->isModal);

                } else {

                    $this->data['error']  = 'There was a problem creating the Product Type. ';
                    $this->data['error'] .= $this->shop_product_model->lastError();
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
        $this->data['productTypes'] = $this->shop_product_type_model->getAll();

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('productType/edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a product type
     * @return void
     */
    protected function productTypeEdit()
    {
        if (!userHasPermission('admin:shop:manage:product:type_edit')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $this->data['product_type'] = $this->shop_product_type_model->getById($this->uri->segment(6));

        if (empty($this->data['product_type'])) {

            show_404();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->oFormValidation->set_rules('label', '', 'xss_clean|required|unique_if_diff[' . NAILS_DB_PREFIX . 'shop_product_type.label.' . $this->data['product_type']->label . ']');
            $this->oFormValidation->set_rules('description', '', 'xss_clean');
            $this->oFormValidation->set_rules('is_physical', '', 'xss_clean');
            $this->oFormValidation->set_rules('ipn_method', '', 'xss_clean');
            $this->oFormValidation->set_rules('max_per_order', '', 'xss_clean');
            $this->oFormValidation->set_rules('max_variations', '', 'xss_clean');

            $this->oFormValidation->set_message('required', lang('fv_required'));

            if ($this->oFormValidation->run()) {

                $aUpdateData = array(
                    'label'          => $this->input->post('label'),
                    'description'    => $this->input->post('description'),
                    'is_physical'    => (bool) $this->input->post('is_physical'),
                    'ipn_method'     => $this->input->post('ipn_method'),
                    'max_per_order'  => (int) $this->input->post('max_per_order'),
                    'max_variations' => (int) $this->input->post('max_variations')
                );

                if ($this->shop_product_type_model->update($this->data['product_type']->id, $aUpdateData)) {

                    $this->session->set_flashdata('success', 'Product Type saved successfully.');
                    redirect('admin/shop/manage/productType' . $this->isModal);

                } else {

                    $this->data['error']  = 'There was a problem saving the Product Type. ';
                    $this->data['error'] .= $this->shop_product_type_model->lastError();
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
        $this->data['productTypes'] = $this->shop_product_type_model->getAll();

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('productType/edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a product type
     * @return void
     */
    public function productTypeDelete()
    {
        $status   = 'message';
        $message  = '<strong>Coming Soon!</strong><br />The ability to delete product types via ';
        $message .= 'the admin interface is on the roadmap and will be available soon.';
        $this->session->set_flashdata($status, $message);

        redirect('admin/shop/manage/productType' . $this->isModal);
    }

    // --------------------------------------------------------------------------

    /**
     * Manage product type meta data
     * @return void
     */
    public function productTypeMeta()
    {
        if (!userHasPermission('admin:shop:manage:product:type_meta__manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load models
        $this->load->model('shop/shop_product_type_model');
        $this->load->model('shop/shop_product_type_meta_model');

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Manage &rsaquo; Product Type Meta Fields ';
        $this->routeRequest('productTypeMeta');
    }

    // --------------------------------------------------------------------------

    /**
     * Browse product type meta data
     * @return void
     */
    protected function productTypeMetaIndex()
    {
        //  Get the table prefix from the model
        $tablePrefix = $this->shop_product_type_meta_model->getTablePrefix();

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
            $tablePrefix . '.label'   => 'Label',
            $tablePrefix . '.created' => 'Created',
            $tablePrefix . '.modified' => 'Modified'
        );

        // --------------------------------------------------------------------------

        //  Define the $data variable for the queries
        $data = array(
            'includeAssociatedProductTypes' => true,
            'sort' => array(
                array($sortOn, $sortOrder)
            ),
            'keywords' => $keywords
        );

        // --------------------------------------------------------------------------

        //  Get the items for the page
        $totalRows                = $this->shop_product_type_meta_model->countAll($data);
        $this->data['metaFields'] = $this->shop_product_type_meta_model->getAll($page, $perPage, $data);

        //  Set Search and Pagination objects for the view
        $this->data['search']     = Helper::searchObject(true, $sortColumns, $sortOn, $sortOrder, $perPage, $keywords);
        $this->data['pagination'] = Helper::paginationObject($page, $perPage, $totalRows);

        // --------------------------------------------------------------------------

        //  Add header button
        if (userHasPermission('admin:shop:manage:product:type_meta_create')) {

            Helper::addHeaderButton(
                'admin/shop/manage/productTypeMeta/create' . $this->isModal,
                'Create Product Type Meta Field'
            );
        }

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('productTypeMeta/index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create product type meta data
     * @return void
     */
    protected function productTypeMetaCreate()
    {
        if (!userHasPermission('admin:shop:manage:product:type_meta_create')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->oFormValidation->set_rules('label', '', 'xss_clean|required');
            $this->oFormValidation->set_rules('admin_form_sub_label', '', 'xss_clean');
            $this->oFormValidation->set_rules('admin_form_placeholder', '', 'xss_clean');
            $this->oFormValidation->set_rules('admin_form_tip', '', 'xss_clean');
            $this->oFormValidation->set_rules('associated_product_types', '', 'xss_clean');
            $this->oFormValidation->set_rules('allow_multiple', '', 'xss_clean');
            $this->oFormValidation->set_rules('is_filter', '', 'xss_clean');

            $this->oFormValidation->set_message('required', lang('fv_required'));

            if ($this->oFormValidation->run()) {

                $data                             = array();
                $data['label']                    = $this->input->post('label');
                $data['admin_form_sub_label']     = $this->input->post('admin_form_sub_label');
                $data['admin_form_placeholder']   = $this->input->post('admin_form_placeholder');
                $data['admin_form_tip']           = $this->input->post('admin_form_tip');
                $data['associated_product_types'] = $this->input->post('associated_product_types');
                $data['allow_multiple']           = (bool) $this->input->post('allow_multiple');
                $data['is_filter']                = (bool) $this->input->post('is_filter');

                if ($this->shop_product_type_meta_model->create($data)) {

                    $this->session->set_flashdata('success', 'Product Type Meta Field created successfully.');
                    redirect('admin/shop/manage/productTypeMeta' . $this->isModal);

                } else {

                    $this->data['error']  = 'There was a problem creating the Product Type Meta Field. ';
                    $this->data['error'] .= $this->shop_product_type_meta_model->lastError();
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
        $this->data['productTypes'] = $this->shop_product_type_model->getAll();

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('productTypeMeta/edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit product type meta data
     * @return void
     */
    protected function productTypeMetaEdit()
    {
        if (!userHasPermission('admin:shop:manage:product:type_meta_edit')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $data = array('includeAssociatedProductTypes' => true);
        $this->data['meta_field'] = $this->shop_product_type_meta_model->getById($this->uri->segment(6), $data);

        if (empty($this->data['meta_field'])) {

            show_404();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->oFormValidation->set_rules('label', '', 'xss_clean|required');
            $this->oFormValidation->set_rules('admin_form_sub_label', '', 'xss_clean');
            $this->oFormValidation->set_rules('admin_form_placeholder', '', 'xss_clean');
            $this->oFormValidation->set_rules('admin_form_tip', '', 'xss_clean');
            $this->oFormValidation->set_rules('associated_product_types', '', 'xss_clean');
            $this->oFormValidation->set_rules('allow_multiple', '', 'xss_clean');
            $this->oFormValidation->set_rules('is_filter', '', 'xss_clean');

            $this->oFormValidation->set_message('required', lang('fv_required'));

            if ($this->oFormValidation->run()) {

                $data                             = array();
                $data['label']                    = $this->input->post('label');
                $data['admin_form_sub_label']     = $this->input->post('admin_form_sub_label');
                $data['admin_form_placeholder']   = $this->input->post('admin_form_placeholder');
                $data['admin_form_tip']           = $this->input->post('admin_form_tip');
                $data['associated_product_types'] = $this->input->post('associated_product_types');
                $data['allow_multiple']           = (bool) $this->input->post('allow_multiple');
                $data['is_filter']                = (bool) $this->input->post('is_filter');

                if ($this->shop_product_type_meta_model->update($this->data['meta_field']->id, $data)) {

                    $this->session->set_flashdata('success', 'Product Type Meta Field saved successfully.');
                    redirect('admin/shop/manage/productTypeMeta' . $this->isModal);

                } else {

                    $this->data['error']  = 'There was a problem saving the Product Type Meta Field. ';
                    $this->data['error'] .= $this->shop_product_type_meta_model->lastError();
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
        $this->data['productTypes'] = $this->shop_product_type_model->getAll();

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('productTypeMeta/edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Delete product type meta data
     * @return void
     */
    protected function productTypeMetaDelete()
    {
        if (!userHasPermission('admin:shop:manage:product:type_meta_delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id = $this->uri->segment(6);

        if ($this->shop_product_type_meta_model->delete($id)) {

            $status  = 'success';
            $message = 'Product Type was deleted successfully.';

        } else {

            $status  = 'error';
            $message = 'There was a problem deleting the Product Type. ' . $this->shop_product_type_model->lastError();
        }

        $this->session->set_flashdata($status, $message);
        redirect('admin/shop/manage/productTypeMeta' . $this->isModal);
    }

    // --------------------------------------------------------------------------

    /**
     * Routes requests
     * @param  string $prefix The method prefix
     * @return void
     */
    protected function routeRequest($prefix)
    {
        $methodRaw = $this->uri->segment(5) ? $this->uri->segment(5) : 'index';
        $method    = $prefix . underscoreToCamelcase($methodRaw, false);

        if (method_exists($this, $method)) {

            $this->{$method}();

        } else {

            show_404('', true);
        }
    }
}
