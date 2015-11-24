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

use Nails\Factory;
use Nails\Admin\Helper;
use Nails\Shop\Controller\BaseAdmin;

class Availability extends BaseAdmin
{
    /**
     * Announces this controller's navGroups
     * @return stdClass
     */
    public static function announce()
    {
        if (userHasPermission('admin:shop:availability:manage')) {

            //  Alerts
            $ci =& get_instance();

            //  Get all notifications, show as an info count
            $numAlertsAll = $ci->db->count_all_results(NAILS_DB_PREFIX . 'shop_inform_product_available');

            $oAlertAll = Factory::factory('NavAlert', 'nailsapp/module-admin');
            $oAlertAll->setValue($numAlertsAll);
            $oAlertAll->setLabel('All Notifications');

            //  Get notifications in the last week, add an alert count
            $ci->db->where('created >', 'ADDDATE(NOW(), INTERVAL -1 WEEK)', false);
            $numAlertsNew = $ci->db->count_all_results(NAILS_DB_PREFIX . 'shop_inform_product_available');

            $oAlertNew = Factory::factory('NavAlert', 'nailsapp/module-admin');
            $oAlertNew->setValue($numAlertsNew);
            $oAlertNew->setSeverity('danger');
            $oAlertNew->setLabel('Added within the last week');

            $navGroup = Factory::factory('Nav', 'nailsapp/module-admin');
            $navGroup->setLabel('Shop');
            $navGroup->setIcon('fa-shopping-cart');
            $navGroup->addAction('Product Availability Alerts', 'index', array($oAlertAll, $oAlertNew));

            return $navGroup;
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

        $this->data['notifications'] = $this->shop_inform_product_available_model->getAll();

        // --------------------------------------------------------------------------

        $this->asset->load('nails.admin.shop.productavailabilitynotification.browse.min.js', 'NAILS');
        $this->asset->inline('var _availabilty = new NAILS_Admin_Shop_Product_Availability_Notification_Browse()', 'JS');

        // --------------------------------------------------------------------------

        //  Add a header button
        if (userHasPermission('admin:shop:availability:create')) {

            Helper::addHeaderButton('admin/shop/availability/create', 'Add New Notification');
        }

        // --------------------------------------------------------------------------

        Helper::loadView('index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a product availability notification
     * @return void
     */
    public function create()
    {
        if (!userHasPermission('admin:shop:availability:create')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $oFormValidation = Factory::service('FormValidation');
            $oFormValidation->set_rules('email', '', 'xss_clean|required|valid_email');
            $oFormValidation->set_rules('item', '', 'xss_clean|required');

            $oFormValidation->set_message('required', lang('fv_required'));
            $oFormValidation->set_message('valid_email', lang('fv_valid_email'));

            if ($oFormValidation->run()) {

                $item = explode(':', $this->input->post('item'));

                $aInsertData               = array();
                $aInsertData->email        = $this->input->post('email');
                $aInsertData->product_id   = isset($item[0]) ? (int) $item[0] : null;
                $aInsertData->variation_id = isset($item[1]) ? (int) $item[1] : null;

                if ($this->shop_inform_product_available_model->create($aInsertData)) {

                    //  Redirect to clear form
                    $this->session->set_flashdata('success', 'Product Availability Notification created successfully.');
                    redirect('admin/shop/availability');

                } else {

                    $this->data['error']  = 'There was a problem creating the Product Availability Notification. ';
                    $this->data['error'] .= $this->shop_inform_product_available_model->lastError();
                }

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Set method info
        $this->data['page']->title = 'Create Product Availability Notification';

        // --------------------------------------------------------------------------

        $this->data['productsVariationsFlat'] = $this->shop_product_model->getAllProductVariationFlat();

        // --------------------------------------------------------------------------

        Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a product availability notification
     * @return void
     */
    public function edit()
    {
        if (!userHasPermission('admin:shop:availability:edit')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $this->data['notification'] = $this->shop_inform_product_available_model->getById($this->uri->segment(5));

        if (!$this->data['notification']) {

            show_404();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $oFormValidation = Factory::service('FormValidation');
            $oFormValidation->set_rules('email', '', 'xss_clean|required|valid_email');
            $oFormValidation->set_rules('item', '', 'xss_clean|required');

            $oFormValidation->set_message('required', lang('fv_required'));
            $oFormValidation->set_message('valid_email', lang('fv_valid_email'));

            if ($oFormValidation->run()) {

                $item = explode(':', $this->input->post('item'));

                $aUpdateData                 = array();
                $aUpdateData['email']        = $this->input->post('email');
                $aUpdateData['product_id']   = isset($item[0]) ? (int) $item[0] : null;
                $aUpdateData['variation_id'] = isset($item[1]) ? (int) $item[1] : null;

                if ($this->shop_inform_product_available_model->update($this->data['notification']->id, $aUpdateData)) {

                    //  Redirect to clear form
                    $this->session->set_flashdata('success', 'Product Availability Notification updated successfully.');
                    redirect('admin/shop/availability');

                } else {

                    $this->data['error']  = 'There was a problem updated the Product Availability Notification. ';
                    $this->data['error'] .= $this->shop_inform_product_available_model->lastError();

                }

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Set method info
        $this->data['page']->title = 'Edit Product Availability Notification';

        // --------------------------------------------------------------------------

        $this->data['productsVariationsFlat'] = $this->shop_product_model->getAllProductVariationFlat();

        // --------------------------------------------------------------------------

        Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a product availability notification
     * @return void
     */
    public function delete()
    {
        if (!userHasPermission('admin:shop:availability:delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id = $this->uri->segment(5);

        if ($this->shop_inform_product_available_model->delete($id)) {

            $this->session->set_flashdata('success', 'Product Availability Notification was deleted successfully.');

        } else {

            $this->session->set_flashdata('error', 'There was a problem deleting the Product availability Notification. ' . $this->shop_inform_product_available_model->lastError());
        }

        redirect('admin/shop/availability');
    }
}
