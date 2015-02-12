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

class Manage extends \AdminController
{
    /**
     * Announces this controller's navGroups
     * @return stdClass
     */
    public static function announce()
    {
        $navGroup = new \Nails\Admin\Nav('Shop');
        $navGroup->addMethod('Other Managers');
        return $navGroup;
    }

    // --------------------------------------------------------------------------

    /**
     * Construct the controller
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('shop/shop_model');
    }

    // --------------------------------------------------------------------------

    /**
     * Browse other shop managers
     * @return void
     */
    public function index()
    {
        \Nails\Admin\Helper::loadView('index');
    }

    // --------------------------------------------------------------------------

    /**
     * Manage product attributes
     * @return void
     */
    public function attribute()
    {
        if (!userHasPermission('admin.shop:0.attribute_manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load model
        $this->load->model('shop/shop_attribute_model');

        // --------------------------------------------------------------------------

        $this->routeRequest('attribute');
    }

    // --------------------------------------------------------------------------

    /**
     * Browse product attributes
     * @return void
     */
    protected function attributeIndex()
    {
        //  Fetch data
        $data = array('include_count' => true);
        $this->data['attributes'] = $this->shop_attribute_model->get_all(null, null, $data);

        // --------------------------------------------------------------------------

        //  Load views
        \Nails\Admin\Helper::loadView('attribute/index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a new product attribute
     * @return void
     */
    protected function attributeCreate()
    {
        if (!userHasPermission('admin.shop:0.attribute_create')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->load->library('form_validation');

            $this->form_validation->set_rules('label', '', 'xss_clean|required');
            $this->form_validation->set_rules('description', '', 'xss_clean');

            $this->form_validation->set_message('required', lang('fv_required'));

            if ($this->form_validation->run()) {

                $data               = new \stdClass();
                $data->label        = $this->input->post('label');
                $data->description  = $this->input->post('description');

                if ($this->shop_attribute_model->create($data)) {

                    $this->session->set_flashdata('success', 'Attribute created successfully.');
                    redirect('admin/shop/manage/attribute' . $this->data['isFancybox']);

                } else {

                    $this->data['error'] = 'There was a problem creating the Attribute. ' . $this->shop_category_model->last_error();

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
        \Nails\Admin\Helper::loadView('attribute/edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a product attribute
     * @return void
     */
    protected function attributeEdit()
    {
        if (!userHasPermission('admin.shop:0.attribute_edit')) {

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

                $data               = new \stdClass();
                $data->label        = $this->input->post('label');
                $data->description  = $this->input->post('description');

                if ($this->shop_attribute_model->update($this->data['attribute']->id, $data)) {

                    $this->session->set_flashdata('success', 'Attribute saved successfully.');
                    redirect('admin/shop/manage/attribute' . $this->data['isFancybox']);

                } else {

                    $this->data['error'] = 'There was a problem saving the Attribute. ' . $this->shop_attribute_model->last_error();

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
        \Nails\Admin\Helper::loadView('attribute/edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a product attribute
     * @return void
     */
    protected function attributeDelete()
    {
        if (!userHasPermission('admin.shop:0.attribute_delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id = $this->uri->segment(6);

        if ($this->shop_attribute_model->delete($id)) {

            $this->session->set_flashdata('success', 'Attribute was deleted successfully.');

        } else {

            $this->session->set_flashdata('error', 'There was a problem deleting the Attribute. ' . $this->shop_attribute_model->last_error());
        }

        redirect('admin/shop/manage/attribute' . $this->data['isFancybox']);
    }

    // --------------------------------------------------------------------------

    /**
     * Manage product  brands
     * @return void
     */
    public function brand()
    {
        if (!userHasPermission('admin.shop:0.brand_manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load model
        $this->load->model('shop/shop_brand_model');

        // --------------------------------------------------------------------------

        $this->routeRequest('brand');
    }

    // --------------------------------------------------------------------------

    /**
     * Browse product brands
     * @return void
     */
    protected function brandIndex()
    {
        //  Fetch data
        $data = array('include_count' => true, 'only_active' => false);
        $this->data['brands'] = $this->shop_brand_model->get_all(null, null, $data);

        // --------------------------------------------------------------------------

        //  Load views
        \Nails\Admin\Helper::loadView('brand/index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a product brand
     * @return void
     */
    protected function brandCreate()
    {
        if (!userHasPermission('admin.shop:0.brand_create')) {

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

                $data                   = new \stdClass();
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
                    $this->session->set_flashdata('success', 'Brand created successfully.');
                    redirect('admin/shop/manage/brand' . $this->data['isFancybox']);

                } else {

                    $this->data['error'] = 'There was a problem creating the Brand. ' . $this->shop_brand_model->last_error();

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
        \Nails\Admin\Helper::loadView('brand/edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a product brand
     * @return void
     */
    protected function brandEdit()
    {
        if (!userHasPermission('admin.shop:0.brand_edit')) {

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

                $data                   = new \stdClass();
                $data->label            = $this->input->post('label');
                $data->logo_id          = (int) $this->input->post('logo_id') ? (int) $this->input->post('logo_id') : null;
                $data->cover_id     = (int) $this->input->post('cover_id') ? (int) $this->input->post('cover_id') : null;
                $data->description      = $this->input->post('description');
                $data->is_active        = (bool) $this->input->post('is_active');
                $data->seo_title        = $this->input->post('seo_title');
                $data->seo_description  = $this->input->post('seo_description');
                $data->seo_keywords = $this->input->post('seo_keywords');

                if ($this->shop_brand_model->update($this->data['brand']->id, $data)) {

                    $this->session->set_flashdata('success', 'Brand saved successfully.');
                    redirect('admin/shop/manage/brand' . $this->data['isFancybox']);

                } else {

                    $this->data['error'] = 'There was a problem saving the Brand. ' . $this->shop_brand_model->last_error();

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
        $this->data['brands'] = $this->shop_brand_model->get_all();

        // --------------------------------------------------------------------------

        //  Load views
        \Nails\Admin\Helper::loadView('brand/edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a product brand
     * @return void
     */
    protected function brandDelete()
    {
        if (!userHasPermission('admin.shop:0.brand_delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id = $this->uri->segment(6);

        if ($this->shop_brand_model->delete($id)) {

            $this->session->set_flashdata('success', 'Brand was deleted successfully.');

        } else {

            $this->session->set_flashdata('error', 'There was a problem deleting the Brand. ' . $this->shop_brand_model->last_error());
        }

        redirect('admin/shop/manage/brand' . $this->data['isFancybox']);
    }

    // --------------------------------------------------------------------------

    /**
     * Manage product categories
     * @return void
     */
    public function category()
    {
        if (!userHasPermission('admin.shop:0.category_manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load model
        $this->load->model('shop/shop_category_model');

        // --------------------------------------------------------------------------

        $this->routeRequest('category');
    }

    // --------------------------------------------------------------------------

    /**
     * Browse product categories
     * @return void
     */
    protected function categoryIndex()
    {
        //  Fetch data
        $data = array('include_count' => true);
        $this->data['categories'] = $this->shop_category_model->get_all(null, null, $data);

        // --------------------------------------------------------------------------

        //  Load views
        \Nails\Admin\Helper::loadView('category/index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a product category
     * @return void
     */
    protected function categoryCreate()
    {
        if (!userHasPermission('admin.shop:0.category_create')) {

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

                $data                   = new \stdClass();
                $data->label            = $this->input->post('label');
                $data->parent_id        = $this->input->post('parent_id');
                $data->cover_id     = $this->input->post('cover_id') ? (int) $this->input->post('cover_id') : null;
                $data->description      = $this->input->post('description');
                $data->seo_title        = $this->input->post('seo_title');
                $data->seo_description  = $this->input->post('seo_description');
                $data->seo_keywords = $this->input->post('seo_keywords');

                if ($this->shop_category_model->create($data)) {

                    $this->session->set_flashdata('success', 'Category created successfully.');
                    redirect('admin/shop/manage/category' . $this->data['isFancybox']);

                } else {

                    $this->data['error'] = 'There was a problem creating the Category. ' . $this->shop_category_model->last_error();

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
        \Nails\Admin\Helper::loadView('category/edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a product category
     * @return void
     */
    protected function categoryEdit()
    {
        if (!userHasPermission('admin.shop:0.category_edit')) {

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

                $data                   = new \stdClass();
                $data->label            = $this->input->post('label');
                $data->parent_id        = $this->input->post('parent_id');
                $data->cover_id     = $this->input->post('cover_id') ? (int) $this->input->post('cover_id') : null;
                $data->description      = $this->input->post('description');
                $data->seo_title        = $this->input->post('seo_title');
                $data->seo_description  = $this->input->post('seo_description');
                $data->seo_keywords = $this->input->post('seo_keywords');

                if ($this->shop_category_model->update($this->data['category']->id, $data)) {

                    $this->session->set_flashdata('success', 'Category saved successfully.');
                    redirect('admin/shop/manage/category' . $this->data['isFancybox']);

                } else {

                    $this->data['error'] = 'There was a problem saving the Category. ' . $this->shop_category_model->last_error();

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
        \Nails\Admin\Helper::loadView('category/edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a product category
     * @return void
     */
    protected function categoryDelete()
    {
        if (!userHasPermission('admin.shop:0.category_delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id = $this->uri->segment(6);

        if ($this->shop_category_model->delete($id)) {

            $this->session->set_flashdata('success', 'Category was deleted successfully.');

        } else {

            $this->session->set_flashdata('error', 'There was a problem deleting the Category. ' . $this->shop_category_model->last_error());
        }

        redirect('admin/shop/manage/category' . $this->data['isFancybox']);
    }

    // --------------------------------------------------------------------------

    /**
     * Manage product collections
     * @return void
     */
    public function collection()
    {
        if (!userHasPermission('admin.shop:0.collection_manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load model
        $this->load->model('shop/shop_collection_model');

        // --------------------------------------------------------------------------

        $this->routeRequest('collection');
    }

    // --------------------------------------------------------------------------

    /**
     * Browse product collections
     * @return void
     */
    protected function collectionIndex()
    {
        //  Fetch data
        $data = array('include_count' => true, 'only_active' => false);
        $this->data['collections'] = $this->shop_collection_model->get_all(null, null, $data);

        // --------------------------------------------------------------------------

        //  Load views
        \Nails\Admin\Helper::loadView('collection/index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a product collection
     * @return void
     */
    protected function collectionCreate()
    {
        if (!userHasPermission('admin.shop:0.collection_create')) {

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

                $data                   = new \stdClass();
                $data->label            = $this->input->post('label');
                $data->cover_id     = $this->input->post('cover_id') ? (int) $this->input->post('cover_id') : null;
                $data->description      = $this->input->post('description');
                $data->seo_title        = $this->input->post('seo_title');
                $data->seo_description  = $this->input->post('seo_description');
                $data->seo_keywords = $this->input->post('seo_keywords');
                $data->is_active        = (bool) $this->input->post('is_active');

                if ($this->shop_collection_model->create($data)) {

                    $this->session->set_flashdata('success', 'Collection created successfully.');
                    redirect('admin/shop/manage/collection' . $this->data['isFancybox']);

                } else {

                    $this->data['error'] = 'There was a problem creating the Collection. ' . $this->shop_collection_model->last_error();

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
        \Nails\Admin\Helper::loadView('collection/edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a product collection
     * @return void
     */
    protected function collectionEdit()
    {
        if (!userHasPermission('admin.shop:0.collection_edit')) {

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

                $data                   = new \stdClass();
                $data->label            = $this->input->post('label');
                $data->cover_id     = $this->input->post('cover_id') ? (int) $this->input->post('cover_id') : null;
                $data->description      = $this->input->post('description');
                $data->seo_title        = $this->input->post('seo_title');
                $data->seo_description  = $this->input->post('seo_description');
                $data->seo_keywords = $this->input->post('seo_keywords');
                $data->is_active        = (bool) $this->input->post('is_active');

                if ($this->shop_collection_model->update($this->data['collection']->id, $data)) {

                    $this->session->set_flashdata('success', 'Collection saved successfully.');
                    redirect('admin/shop/manage/collection' . $this->data['isFancybox']);

                } else {

                    $this->data['error'] = 'There was a problem saving the Collection. ' . $this->shop_collection_model->last_error();

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
        \Nails\Admin\Helper::loadView('collection/edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a product collection
     * @return void
     */
    protected function collectionDelete()
    {
        if (!userHasPermission('admin.shop:0.collection_delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id = $this->uri->segment(6);

        if ($this->shop_collection_model->delete($id)) {

            $this->session->set_flashdata('success', 'Collection was deleted successfully.');

        } else {

            $this->session->set_flashdata('error', 'There was a problem deleting the Collection. ' . $this->shop_collection_model->last_error());
        }

        redirect('admin/shop/manage/collection' . $this->data['isFancybox']);
    }

    // --------------------------------------------------------------------------

    /**
     * Manage product ranges
     * @return void
     */
    public function range()
    {
        if (!userHasPermission('admin.shop:0.range_manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load model
        $this->load->model('shop/shop_range_model');

        // --------------------------------------------------------------------------

        $this->routeRequest('range');
    }

    // --------------------------------------------------------------------------

    /**
     * Browse product ranges
     * @return void
     */
    protected function range_Index()
    {
        //  Fetch data
        $data = array('include_count' => true);
        $this->data['ranges'] = $this->shop_range_model->get_all(null, null, $data);

        // --------------------------------------------------------------------------

        //  Load views
        \Nails\Admin\Helper::loadView('range/index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a product range
     * @return void
     */
    protected function rangeCreate()
    {
        if (!userHasPermission('admin.shop:0.range_create')) {

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

                $data                   = new \stdClass();
                $data->label            = $this->input->post('label');
                $data->cover_id     = $this->input->post('cover_id') ? (int) $this->input->post('cover_id') : null;
                $data->description      = $this->input->post('description');
                $data->seo_title        = $this->input->post('seo_title');
                $data->seo_description  = $this->input->post('seo_description');
                $data->seo_keywords = $this->input->post('seo_keywords');
                $data->is_active        = (bool) $this->input->post('is_active');

                if ($this->shop_range_model->create($data)) {

                    $this->session->set_flashdata('success', 'Range created successfully.');
                    redirect('admin/shop/manage/range' . $this->data['isFancybox']);

                } else {

                    $this->data['error'] = 'There was a problem creating the Range. ' . $this->shop_range_model->last_error();

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
        \Nails\Admin\Helper::loadView('range/edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a product range
     * @return void
     */
    protected function rangeEdit()
    {
        if (!userHasPermission('admin.shop:0.range_edit')) {

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

                $data                   = new \stdClass();
                $data->label            = $this->input->post('label');
                $data->cover_id     = $this->input->post('cover_id') ? (int) $this->input->post('cover_id') : null;
                $data->description      = $this->input->post('description');
                $data->seo_title        = $this->input->post('seo_title');
                $data->seo_description  = $this->input->post('seo_description');
                $data->seo_keywords = $this->input->post('seo_keywords');
                $data->is_active        = (bool) $this->input->post('is_active');

                if ($this->shop_range_model->update($this->data['range']->id, $data)) {

                    $this->session->set_flashdata('success', 'Range saved successfully.');
                    redirect('admin/shop/manage/range' . $this->data['isFancybox']);

                } else {

                    $this->data['error'] = 'There was a problem saving the Range. ' . $this->shop_range_model->last_error();

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
        \Nails\Admin\Helper::loadView('range/edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a product range
     * @return void
     */
    protected function rangeDelete()
    {
        if (!userHasPermission('admin.shop:0.range_delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id = $this->uri->segment(6);

        if ($this->shop_range_model->delete($id)) {

            $this->session->set_flashdata('success', 'Range was deleted successfully.');

        } else {

            $this->session->set_flashdata('error', 'There was a problem deleting the Range. ' . $this->shop_range_model->last_error());
        }

        redirect('admin/shop/manage/range' . $this->data['isFancybox']);
    }

    // --------------------------------------------------------------------------

    /**
     * Manage product tags
     * @return void
     */
    public function tag()
    {
        if (!userHasPermission('admin.shop:0.tag_manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load model
        $this->load->model('shop/shop_tag_model');

        // --------------------------------------------------------------------------

        $this->routeRequest('tag');
    }

    // --------------------------------------------------------------------------

    /**
     * Browse product tags
     * @return void
     */
    protected function tagIndex()
    {
        //  Fetch data
        $data = array('include_count' => true);
        $this->data['tags'] = $this->shop_tag_model->get_all(null,null, $data);

        // --------------------------------------------------------------------------

        //  Load views
        \Nails\Admin\Helper::loadView('tag/index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a product tag
     * @return void
     */
    protected function tagCreate()
    {
        if (!userHasPermission('admin.shop:0.tag_create')) {

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

                $data                   = new \stdClass();
                $data->label            = $this->input->post('label');
                $data->cover_id     = $this->input->post('cover_id') ? (int) $this->input->post('cover_id') : null;
                $data->description      = $this->input->post('description');
                $data->seo_title        = $this->input->post('seo_title');
                $data->seo_description  = $this->input->post('seo_description');
                $data->seo_keywords = $this->input->post('seo_keywords');

                if ($this->shop_tag_model->create($data)) {

                    $this->session->set_flashdata('success', 'Tag created successfully.');
                    redirect('admin/shop/manage/tag' . $this->data['isFancybox']);

                } else {

                    $this->data['error'] = 'There was a problem creating the Tag. ' . $this->shop_tag_model->last_error();

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
        \Nails\Admin\Helper::loadView('tag/edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a product tag
     * @return void
     */
    protected function tagEdit()
    {
        if (!userHasPermission('admin.shop:0.tag_edit')) {

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

                $data                   = new \stdClass();
                $data->label            = $this->input->post('label');
                $data->cover_id     = $this->input->post('cover_id') ? (int) $this->input->post('cover_id') : null;
                $data->description      = $this->input->post('description');
                $data->seo_title        = $this->input->post('seo_title');
                $data->seo_description  = $this->input->post('seo_description');
                $data->seo_keywords = $this->input->post('seo_keywords');

                if ($this->shop_tag_model->update($this->data['tag']->id, $data)) {

                    $this->session->set_flashdata('success', 'Tag saved successfully.');
                    redirect('admin/shop/manage/tag' . $this->data['isFancybox']);

                } else {

                    $this->data['error'] = 'There was a problem saving the Tag. ' . $this->shop_tag_model->last_error();

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
        \Nails\Admin\Helper::loadView('tag/edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a product tag
     * @return void
     */
    protected function tagDelete()
    {
        if (!userHasPermission('admin.shop:0.tag_delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id = $this->uri->segment(6);

        if ($this->shop_tag_model->delete($id)) {

            $this->session->set_flashdata('success', 'Tag was deleted successfully.');

        } else {

            $this->session->set_flashdata('error', 'There was a problem deleting the Tag. ' . $this->shop_tag_model->last_error());
        }

        redirect('admin/shop/manage/tag' . $this->data['isFancybox']);
    }

    // --------------------------------------------------------------------------

    /**
     * Manage product tax rates
     * @return void
     */
    public function taxRate()
    {
        if (!userHasPermission('admin.shop:0.tax_rate_manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load model
        $this->load->model('shop/shop_tax_rate_model');

        // --------------------------------------------------------------------------

        $this->routeRequest('taxRate');
    }

    // --------------------------------------------------------------------------

    /**
     * Browse product tax rates
     * @return void
     */
    protected function taxRateIndex()
    {
        //  Fetch data
        $data = array('include_count' => true);
        $this->data['tax_rates'] = $this->shop_tax_rate_model->get_all(null, null, $data);

        // --------------------------------------------------------------------------

        //  Load views
        \Nails\Admin\Helper::loadView('taxRate/index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a product tax rate
     * @return void
     */
    protected function taxRateCreate()
    {
        if (!userHasPermission('admin.shop:0.tax_rate_create')) {

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

                $data           = new \stdClass();
                $data->label    = $this->input->post('label');
                $data->rate = $this->input->post('rate');

                if ($this->shop_tax_rate_model->create($data)) {

                    $this->session->set_flashdata('success', 'Tax Rate created successfully.');
                    redirect('admin/shop/manage/tax_rate' . $this->data['isFancybox']);

                } else {

                    $this->data['error'] = 'There was a problem creating the Tax Rate. ' . $this->shop_tax_rate_model->last_error();

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
        \Nails\Admin\Helper::loadView('taxRate/edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a product tax rate
     * @return void
     */
    protected function taxRateEdit()
    {
        if (!userHasPermission('admin.shop:0.tax_rate_edit')) {

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

                $data           = new \stdClass();
                $data->label    = $this->input->post('label');
                $data->rate = (float) $this->input->post('rate');

                if ($this->shop_tax_rate_model->update($this->data['tax_rate']->id, $data)) {

                    $this->session->set_flashdata('success', 'Tax Rate saved successfully.');
                    redirect('admin/shop/manage/tax_rate' . $this->data['isFancybox']);

                } else {

                    $this->data['error'] = 'There was a problem saving the Tax Rate. ' . $this->shop_tax_rate_model->last_error();

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
        \Nails\Admin\Helper::loadView('taxRate/edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a product tax rate
     * @return void
     */
    protected function taxRateDelete()
    {
        if (!userHasPermission('admin.shop:0.tax_rate_delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id = $this->uri->segment(6);

        if ($this->shop_tax_rate_model->delete($id)) {

            $this->session->set_flashdata('success', 'Tax Rate was deleted successfully.');

        } else {

            $this->session->set_flashdata('error', 'There was a problem deleting the Tax Rate. ' . $this->shop_tax_rate_model->last_error());
        }

        redirect('admin/shop/manage/tax_rate' . $this->data['isFancybox']);
    }

    // --------------------------------------------------------------------------

    /**
     * Manage product types
     * @return void
     */
    public function productType()
    {
        if (!userHasPermission('admin.shop:0.product_type_manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load model
        $this->load->model('shop/shop_product_type_model');

        // --------------------------------------------------------------------------

        $this->routeRequest('productType');
    }

    // --------------------------------------------------------------------------

    /**
     * Browse product types
     * @return void
     */
    protected function productTypeIndex()
    {
        //  Fetch data
        $data = array('include_count' => true);
        $this->data['product_types'] = $this->shop_product_type_model->get_all(null, null, $data);

        // --------------------------------------------------------------------------

        //  Load views
        \Nails\Admin\Helper::loadView('productType/index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a product type
     * @return void
     */
    protected function productTypeCreate()
    {
        if (!userHasPermission('admin.shop:0.product_type_create')) {

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

                $data                   = new \stdClass();
                $data->label            = $this->input->post('label');
                $data->description      = $this->input->post('description');
                $data->is_physical      = (bool) $this->input->post('is_physical');
                $data->ipn_method       = $this->input->post('ipn_method');
                $data->max_per_order    = (int) $this->input->post('max_per_order');
                $data->max_variations   = (int) $this->input->post('max_variations');

                if ($this->shop_product_type_model->create($data)) {

                    //  Redirect to clear form
                    $this->session->set_flashdata('success', 'Product Type created successfully.');
                    redirect('admin/shop/manage/product_type' . $this->data['isFancybox']);

                } else {

                    $this->data['error'] = 'There was a problem creating the Product Type. ' . $this->shop_product_model->last_error();

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
        \Nails\Admin\Helper::loadView('productType/edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a product type
     * @return void
     */
    protected function productTypeEdit()
    {
        if (!userHasPermission('admin.shop:0.product_type_edit')) {

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

                $data                   = new \stdClass();
                $data->label            = $this->input->post('label');
                $data->description      = $this->input->post('description');
                $data->is_physical      = (bool)$this->input->post('is_physical');
                $data->ipn_method       = $this->input->post('ipn_method');
                $data->max_per_order    = (int) $this->input->post('max_per_order');
                $data->max_variations   = (int) $this->input->post('max_variations');

                if ($this->shop_product_type_model->update($this->data['product_type']->id, $data)) {

                    $this->session->set_flashdata('success', 'Product Type saved successfully.');
                    redirect('admin/shop/product_type' . $this->data['isFancybox']);

                } else {

                    $this->data['error'] = 'There was a problem saving the Product Type. ' . $this->shop_product_type_model->last_error();

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
        \Nails\Admin\Helper::loadView('productType/edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Manage product type meta data
     * @return void
     */
    protected function productTypeMeta()
    {
        if (!userHasPermission('admin.shop:0.product_type_meta__manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load model
        $this->load->model('shop/shop_product_type_model');

        // --------------------------------------------------------------------------

        $this->routeRequest('productTypeMeta');
    }

    // --------------------------------------------------------------------------

    /**
     * Browse product type meta data
     * @return void
     */
    protected function productTypeMetaIndex()
    {
        //  Fetch data
        $data = array('include_associated_product_types' => true);
        $this->data['meta_fields'] = $this->shop_product_type_meta_model->get_all(null, null, $data);

        // --------------------------------------------------------------------------

        //  Load views
        \Nails\Admin\Helper::loadView('productTypeMeta/index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create product type meta data
     * @return void
     */
    protected function productTypeMetaCreate()
    {
        if (!userHasPermission('admin.shop:0.product_type_meta_create')) {

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

                $data                               = new \stdClass();
                $data->label                        = $this->input->post('label');
                $data->admin_form_sub_label     = $this->input->post('admin_form_sub_label');
                $data->admin_form_placeholder       = $this->input->post('admin_form_placeholder');
                $data->admin_form_tip               = $this->input->post('admin_form_tip');
                $data->associated_product_types = $this->input->post('associated_product_types');
                $data->allow_multiple               = (bool) $this->input->post('allow_multiple');
                $data->is_filter                    = (bool) $this->input->post('is_filter');

                if ($this->shop_product_type_meta_model->create($data)) {

                    $this->session->set_flashdata('success', 'Product Type Meta Field created successfully.');
                    redirect('admin/shop/manage/product_type_meta' . $this->data['isFancybox']);

                } else {

                    $this->data['error'] = 'There was a problem creating the Product Type Meta Field. ' . $this->shop_product_type_meta_model->last_error();

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
        \Nails\Admin\Helper::loadView('productTypeMeta/edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit product type meta data
     * @return void
     */
    protected function productTypeMetaEdit()
    {
        if (!userHasPermission('admin.shop:0.product_type_meta_edit')) {

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

                $data                               = new \stdClass();
                $data->label                        = $this->input->post('label');
                $data->admin_form_sub_label     = $this->input->post('admin_form_sub_label');
                $data->admin_form_placeholder       = $this->input->post('admin_form_placeholder');
                $data->admin_form_tip               = $this->input->post('admin_form_tip');
                $data->associated_product_types = $this->input->post('associated_product_types');
                $data->allow_multiple               = (bool) $this->input->post('allow_multiple');
                $data->is_filter                    = (bool) $this->input->post('is_filter');

                if ($this->shop_product_type_meta_model->update($this->data['meta_field']->id, $data)) {

                    $this->session->set_flashdata('success', 'Product Type Meta Field saved successfully.');
                    redirect('admin/shop/manage/product_type_meta' . $this->data['isFancybox']);

                } else {

                    $this->data['error'] = 'There was a problem saving the Product Type Meta Field. ' . $this->shop_product_type_meta_model->last_error();

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
        \Nails\Admin\Helper::loadView('productTypeMeta/edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Delete product type meta data
     * @return void
     */
    protected function productTypeMetaDelete()
    {
        if (!userHasPermission('admin.shop:0.product_type_meta_delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id = $this->uri->segment(6);

        if ($this->shop_product_type_meta_model->delete($id)) {

            $this->session->set_flashdata('success', 'Product Type was deleted successfully.');

        } else {

            $this->session->set_flashdata('error', 'There was a problem deleting the Product Type. ' . $this->shop_product_type_model->last_error());
        }

        redirect('admin/shop/manage/product_type_meta' . $this->data['isFancybox']);
    }

    // --------------------------------------------------------------------------

    /**
     * Routes requests
     * @param  string $prefix The method prefix
     * @return void
     */
    protected function routeRequest($prefix)
    {
        $methodRaw = $this->uri->segment(4) ? $this->uri->segment(4) : 'index';
        $method    = $prefix . underscore_to_camelcase($methodRaw, false);

        if (method_exists($this, $method)) {

            $this->{$method}();

        } else {

            show_404('', true);
        }
    }
}
