<?php

/**
 * Manage product availability notifications
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    AdminController
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Admin\Shop;

class Availability extends \AdminController
{
    /**
     * Announces this controller's navGroups
     * @return stdClass
     */
    public static function announce()
    {
        $navGroup = new \Nails\Admin\Nav('Shop');
        $navGroup->addMethod('Product Availability Notifications');
        return $navGroup;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of extra permissions for this controller
     * @return array
     */
    static function permissions()
    {
        $permissions = parent::permissions();

        $permissions['manage'] = 'Can manage Product notifications';
        $permissions['create'] = 'Can create Product notifications';
        $permissions['edit']   = 'Can edit Product notifications';
        $permissions['delete'] = 'Can delete Product notifications';

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
        $this->load->model('shop/shop_inform_product_available_model');

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
     * Browse product availability notifications
     * @return void
     */
    public function index()
    {
        //  Set method info
        $this->data['page']->title = 'Manage Product Availability Notifications';

        // --------------------------------------------------------------------------

        $this->data['notifications'] = $this->shop_inform_product_available_model->get_all();

        // --------------------------------------------------------------------------

        $this->asset->load('nails.admin.shop.productavailabilitynotification.browse.min.js', 'NAILS');
        $this->asset->inline('var _availabilty = new NAILS_Admin_Shop_Product_Availability_Notification_Browse()', 'JS');

        // --------------------------------------------------------------------------

        //  Add a header button
        if (userHasPermission('admin.shop:0.manage_create')) {

            \Nails\Admin\Helper::addHeaderButton('admin/shop/availability/create', 'Add New Notification');
        }

        // --------------------------------------------------------------------------

        \Nails\Admin\Helper::loadView('index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a product availability notification
     * @return void
     */
    public function create()
    {
        if (!userHasPermission('admin.shop:0.notification_create')) {

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

                $data               = new \stdClass();
                $data->email        = $this->input->post('email');
                $data->product_id   = isset($item[0]) ? (int) $item[0] : null;
                $data->variation_id = isset($item[1]) ? (int) $item[1] : null;

                if ($this->shop_inform_product_available_model->create($data)) {

                    //  Redirect to clear form
                    $this->session->set_flashdata('success', 'Product Availability Notification created successfully.');
                    redirect('admin/shop/availability');

                } else {

                    $this->data['error'] = 'There was a problem creating the Product Availability Notification. ' . $this->shop_inform_product_available_model->last_error();
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

        \Nails\Admin\Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a product availability notification
     * @return void
     */
    public function edit()
    {
        if (!userHasPermission('admin.shop:0.notification_edit')) {

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

                $data               = new \stdClass();
                $data->email        = $this->input->post('email');
                $data->product_id   = isset($item[0]) ? (int) $item[0] : null;
                $data->variation_id = isset($item[1]) ? (int) $item[1] : null;

                if ($this->shop_inform_product_available_model->update($this->data['notification']->id, $data)) {

                    //  Redirect to clear form
                    $this->session->set_flashdata('success', 'Product Availability Notification updated successfully.');
                    redirect('admin/shop/availability');

                } else {

                    $this->data['error'] = 'There was a problem updated the Product Availability Notification. ' . $this->shop_inform_product_available_model->last_error();

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

        \Nails\Admin\Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a product availability notification
     * @return void
     */
    public function delete()
    {
        if (!userHasPermission('admin.shop:0.notifications_delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id = $this->uri->segment(5);

        if ($this->shop_inform_product_available_model->delete($id)) {

            $this->session->set_flashdata('success', 'Product Availability Notification was deleted successfully.');

        } else {

            $this->session->set_flashdata('error', 'There was a problem deleting the Product availability Notification. ' . $this->shop_inform_product_available_model->last_error());
        }

        redirect('admin/shop/availability');
    }
}
