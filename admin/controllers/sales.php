<?php

/**
 * Manage shop sales
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    AdminController
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Admin\Shop;

class Sales extends \AdminController
{
    /**
     * Announces this controller's navGroups
     * @return stdClass
     */
    public static function announce()
    {
        if (userHasPermission('admin.shop:0.sale_manage')) {

            $navGroup = new \Nails\Admin\Nav('Shop');
            $navGroup->addMethod('Manage Sales');
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
    }

    // --------------------------------------------------------------------------

    /**
     * Browse sales
     * @return void
     */
    public function index()
    {
        $this->data['page']->title = 'Manage Sales';

        // --------------------------------------------------------------------------

        \Nails\Admin\Helper::loadView('index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a new sale
     * @return void
     */
    public function create()
    {
        $this->data['page']->title = 'Create Sale';

        // --------------------------------------------------------------------------

        \Nails\Admin\Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a sale
     * @return void
     */
    public function edit()
    {
        $this->data['page']->title = 'Edit Sale "xxx"';

        // --------------------------------------------------------------------------

        \Nails\Admin\Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a sale
     * @return void
     */
    public function delete()
    {
        $this->session->set_flashdata('message', '<strong>TODO:</strong> Delete a sale.');
        redirect('admin/shop/sales/index');
    }
}
