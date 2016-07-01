<?php

/**
 * Manage shop orders
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

class Orders extends BaseAdmin
{
    /**
     * Announces this controller's navGroups
     * @return stdClass
     */
    public static function announce()
    {
        if (userHasPermission('admin:shop:orders:manage')) {

            //  Alerts
            $ci =& get_instance();

            //  Unfulfilled orders
            $ci->db->where('fulfilment_status', 'UNFULFILLED');
            $ci->db->where('status', 'PAID');
            $iNumUnfulfilled = $ci->db->count_all_results(NAILS_DB_PREFIX . 'shop_order');

            $oAlertUnfulfilled = Factory::factory('NavAlert', 'nailsapp/module-admin');
            $oAlertUnfulfilled->setValue($iNumUnfulfilled);
            $oAlertUnfulfilled->setSeverity('warning');
            $oAlertUnfulfilled->setLabel('Unfulfilled Orders');

            //  Packed Orders
            $ci->db->where('fulfilment_status', 'PACKED');
            $iNumPacked = $ci->db->count_all_results(NAILS_DB_PREFIX . 'shop_order');

            $oAlertPacked = Factory::factory('NavAlert', 'nailsapp/module-admin');
            $oAlertPacked->setValue($iNumPacked);
            $oAlertPacked->setSeverity('info');
            $oAlertPacked->setLabel('Packed Orders');

            $oNavGroup = Factory::factory('Nav', 'nailsapp/module-admin');
            $oNavGroup->setLabel('Shop');
            $oNavGroup->setIcon('fa-shopping-cart');
            $oNavGroup->addAction('Manage Orders', 'index', array($oAlertUnfulfilled, $oAlertPacked), 0);

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

        $permissions['manage']    = 'Manage Orders';
        $permissions['view']      = 'View Orders';
        $permissions['edit']      = 'Edit Orders';
        $permissions['reprocess'] = 'Reprocess Orders';
        $permissions['process']   = 'Process Orders';

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
        $this->load->model('shop/shop_order_model');

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
     * Browse shop orders
     * @return void
     */
    public function index()
    {
        if (!userHasPermission('admin:shop:orders:manage')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Set method info
        $this->data['page']->title = 'Manage Orders';

        // --------------------------------------------------------------------------

        $tablePrefix = $this->shop_order_model->getTablePrefix();

        // --------------------------------------------------------------------------

        //  Get pagination and search/sort variables
        $page      = $this->input->get('page')      ? $this->input->get('page')      : 0;
        $perPage   = $this->input->get('perPage')   ? $this->input->get('perPage')   : 50;
        $sortOn    = $this->input->get('sortOn')    ? $this->input->get('sortOn')    : $tablePrefix . '.created';
        $sortOrder = $this->input->get('sortOrder') ? $this->input->get('sortOrder') : 'desc';
        $keywords  = $this->input->get('keywords')  ? $this->input->get('keywords')  : '';

        // --------------------------------------------------------------------------

        //  Define the sortable columns and the filters
        $sortColumns = array(
            $tablePrefix . '.created'          => 'Order Placed',
            $tablePrefix . '.total_base_grand' => 'Order Value'
        );

        // --------------------------------------------------------------------------

        //  Filter Checkboxes
        $cbFilters   = array();
        $cbFilters[] = Helper::searchFilterObject(
            $tablePrefix . '.status',
            'Status',
            array(
                array('Paid', 'PAID', true),
                array('Unpaid', 'UNPAID', true),
                array('Abandoned', 'ABANDONED'),
                array('Cancelled', 'CANCELLED'),
                array('Failed', 'FAILED'),
                array('Pending', 'PENDING')
            )
        );
        $cbFilters[] = Helper::searchFilterObject(
            $tablePrefix . '.fulfilment_status',
            'Ship Status',
            array(
                array('Unfulfilled', 'UNFULFILLED'),
                array('Packed', 'PACKED'),
                array('Fulfilled', 'FULFILLED')
            )
        );

        $this->load->model('shop/shop_shipping_driver_model');

        $aOptions       = $this->shop_shipping_driver_model->options();
        $aFilterOptions = array();

        foreach ($aOptions as $aOption) {
            $aFilterOptions[] = array($aOption['label'], $aOption['slug']);
        }


        $cbFilters[] = Helper::searchFilterObject(
            $tablePrefix . '.delivery_option',
            'Delivery Type',
            $aFilterOptions
        );

        //  @todo get all the shipping options from the driver

        // --------------------------------------------------------------------------

        //  Define the $data variable for the queries
        $data = array(
            'sort' => array(
                array($sortOn, $sortOrder)
            ),
            'keywords'  => $keywords,
            'cbFilters' => $cbFilters
        );

        // --------------------------------------------------------------------------

        //  Get the items for the page
        $totalRows            = $this->shop_order_model->countAll($data);
        $this->data['orders'] = $this->shop_order_model->getAll($page, $perPage, $data);

        //  Set Search and Pagination objects for the view
        $this->data['search']     = Helper::searchObject(true, $sortColumns, $sortOn, $sortOrder, $perPage, $keywords, $cbFilters);
        $this->data['pagination'] = Helper::paginationObject($page, $perPage, $totalRows);

        // --------------------------------------------------------------------------

        $this->asset->load('admin.order.browse.min.js', 'nailsapp/module-shop');
        $this->asset->inline('var _orders = new NAILS_Admin_Shop_Order_Browse()', 'JS');

        // --------------------------------------------------------------------------

        Helper::loadView('index');
    }

    // --------------------------------------------------------------------------

    /**
     * View a single order
     * @return void
     */
    public function view()
    {
        if (!userHasPermission('admin:shop:orders:view')) {

            $this->session->set_flashdata('error', 'You do not have permission to view order details.');
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        //  Fetch and check order
        $this->load->model('shop/shop_order_model');

        $this->data['order'] = $this->shop_order_model->getById($this->uri->segment(5));

        if (!$this->data['order']) {
            show_404();
        }

        // --------------------------------------------------------------------------

        //  Get associated payments
        $this->load->model('shop/shop_order_payment_model');
        $this->data['payments'] = $this->shop_order_payment_model->getForOrder($this->data['order']->id);

        // --------------------------------------------------------------------------

        //  Set method info
        $this->data['page']->title = 'View Order &rsaquo; ' . $this->data['order']->ref;

        // --------------------------------------------------------------------------

        $this->asset->load('admin.order.view.min.js', 'nailsapp/module-shop');
        $this->asset->inline('var _SHOP_ORDER_VIEW = new NAILS_Admin_Shop_Order_View()', 'JS');

        // --------------------------------------------------------------------------

        if ($this->data['order']->status !== 'PAID') {

            $this->data['negative']  = '<strong>Do not process this order!</strong>';
            $this->data['negative'] .= '<br />The customer has not completed payment.';

        } elseif ($this->data['order']->fulfilment_status != 'FULFILLED') {

            if ($this->data['order']->delivery_type == 'COLLECT') {

                $this->data['negative']  = '<strong>Do not ship this order!</strong>';
                $this->data['negative'] .= '<br />This order will be collected by the customer.';

            } elseif ($this->data['order']->delivery_type == 'DELIVER_COLLECT') {

                $this->data['warning']  = '<strong>Only ship part of this order!</strong>';
                $this->data['warning'] .= '<br />This order contains collect only items.';
            }
        }

        // --------------------------------------------------------------------------

        Helper::loadView('view');
    }

    // --------------------------------------------------------------------------

    /**
     * Reprocess an order
     * @return void
     */
    public function reprocess()
    {
        if (!userHasPermission('admin:shop:orders:reprocess')) {

            $this->session->set_flashdata('error', 'You do not have permission to reprocess orders.');
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        //  Check order exists
        $this->load->model('shop/shop_order_model');
        $order = $this->shop_order_model->getById($this->uri->segment(5));

        if (!$order) {

            $this->session->set_flashdata('error', 'I couldn\'t find an order by that ID.');
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        //  PROCESSSSSS...
        $this->shop_order_model->process($order->id);

        // --------------------------------------------------------------------------

        //  Send a receipt to the customer
        $this->shop_order_model->sendReceipt($order->id);

        // --------------------------------------------------------------------------

        //  Send a notification to the store owner(s)
        $this->shop_order_model->sendOrderNotification($order->id);

        // --------------------------------------------------------------------------

        $this->session->set_flashdata('success', 'Order was processed succesfully. The user has been sent a receipt.');
        redirect('admin/shop/orders');
    }

    // --------------------------------------------------------------------------

    /**
     * Process an order
     * @return void
     */
    public function process()
    {
        if (!userHasPermission('admin:shop:orders:process')) {

            $this->session->set_flashdata('error', 'You do not have permission to process order items.');
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        $order_id   = $this->uri->segment(5);
        $product_id = $this->uri->segment(6);
        $isModal = $this->input->get('isModal') ? '?isModal=true' : '';

        // --------------------------------------------------------------------------

        //  Update item
        if ($this->uri->segment(7) == 'processed') {

            $this->db->set('processed', true);

        } else {

            $this->db->set('processed', false);
        }

        $this->db->where('order_id', $order_id);
        $this->db->where('id', $product_id);

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

            $this->session->set_flashdata('success', 'Product\'s status was updated successfully.');
            redirect('admin/shop/orders/view/' . $order_id . $isModal);

        } else {

            $this->session->set_flashdata('error', 'I was not able to update the status of that product.');
            redirect('admin/shop/orders/view/' . $order_id . $isModal);
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Mark an order as fulfilled
     * @return void
     */
    public function fulfil()
    {
        if (!userHasPermission('admin:shop:orders:edit')) {

            $msg    = 'You do not have permission to edit orders.';
            $status = 'error';
            $this->session->set_flashdata($status, $msg);
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        //    Fetch and check order
        $this->load->model('shop/shop_order_model');

        $order = $this->shop_order_model->getById($this->uri->segment(5));

        if (!$order) {

            $msg    = 'No order exists by that ID.';
            $status = 'error';
            $this->session->set_flashdata($status, $msg);
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        if ($this->shop_order_model->fulfil($order->id)) {

            $msg    = 'Order ' . $order->ref . ' was marked as fulfilled.';
            $status = 'success';

        } else {

            $msg    = 'Failed to mark order ' . $order->ref . ' as fulfilled.';
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
    public function fulfil_batch()
    {
        if (!userHasPermission('admin:shop:orders:edit')) {

            $msg    = 'You do not have permission to edit orders.';
            $status = 'error';
            $this->session->set_flashdata($status, $msg);
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        //    Fetch and check orders
        $this->load->model('shop/shop_order_model');

        if ($this->shop_order_model->fulfilBatch($this->input->get('ids'))) {

            $msg    = 'Orders were marked as fulfilled.';
            $status = 'success';

        } else {

            $msg     = 'Failed to mark orders as fulfilled. ';
            $msg    .= $this->shop_order_model->lastError();
            $status  = 'error';
        }

        $this->session->set_flashdata($status, $msg);
        redirect('admin/shop/orders');
    }

    // --------------------------------------------------------------------------


    /**
     * Mark an order as fulfilled
     * @return void
     */
    public function pack()
    {
        if (!userHasPermission('admin:shop:orders:edit')) {

            $msg    = 'You do not have permission to edit orders.';
            $status = 'error';
            $this->session->set_flashdata($status, $msg);
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        //    Fetch and check order
        $this->load->model('shop/shop_order_model');

        $order = $this->shop_order_model->getById($this->uri->segment(5));

        if (!$order) {

            $msg    = 'No order exists by that ID.';
            $status = 'error';
            $this->session->set_flashdata($status, $msg);
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        if ($this->shop_order_model->pack($order->id)) {

            $msg    = 'Order ' . $order->ref . ' was marked as packed.';
            $status = 'success';

        } else {

            $msg    = 'Failed to mark order ' . $order->ref . ' as packed.';
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
    public function pack_batch()
    {
        if (!userHasPermission('admin:shop:orders:edit')) {

            $msg    = 'You do not have permission to edit orders.';
            $status = 'error';
            $this->session->set_flashdata($status, $msg);
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        //    Fetch and check orders
        $this->load->model('shop/shop_order_model');

        if ($this->shop_order_model->packBatch($this->input->get('ids'))) {

            $msg    = 'Orders were marked as packed.';
            $status = 'success';

        } else {

            $msg     = 'Failed to mark orders as packed. ';
            $msg    .= $this->shop_order_model->lastError();
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
    public function unfulfil()
    {
        if (!userHasPermission('admin:shop:orders:edit')) {

            $msg    = 'You do not have permission to edit orders.';
            $status = 'error';
            $this->session->set_flashdata($status, $msg);
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        //    Fetch and check order
        $this->load->model('shop/shop_order_model');

        $order = $this->shop_order_model->getById($this->uri->segment(5));

        if (!$order) {

            $msg    = 'No order exists by that ID.';
            $status = 'error';
            $this->session->set_flashdata($status, $msg);
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        if ($this->shop_order_model->unfulfil($order->id)) {

            $msg    = 'Order ' . $order->ref . ' was marked as unfulfilled.';
            $status = 'success';

        } else {

            $msg    = 'Failed to mark order ' . $order->ref . ' as unfulfilled.';
            $status = 'error';
        }

        $this->session->set_flashdata($status, $msg);
        redirect('admin/shop/orders/view/' . $order->id);
    }

    //---------------------------------------------------------------------------

    /**
     * Batch unfulfil orders
     * @return void
     */
    public function unfulfil_batch()
    {
        if (!userHasPermission('admin:shop:orders:edit')) {

            $msg    = 'You do not have permission to edit orders.';
            $status = 'error';
            $this->session->set_flashdata($status, $msg);
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        //    Fetch and check orders
        $this->load->model('shop/shop_order_model');

        if ($this->shop_order_model->unfulfilBatch($this->input->get('ids'))) {

            $msg    = 'Orders were marked as unfulfilled.';
            $status = 'success';

        } else {

            $msg     = 'Failed to mark orders as unfulfilled. ';
            $msg    .= $this->shop_order_model->lastError();
            $status  = 'error';
        }

        $this->session->set_flashdata($status, $msg);
        redirect('admin/shop/orders');
    }

    // --------------------------------------------------------------------------

    /**
     * Mark an order as cancelled
     * @return void
     */
    public function cancel()
    {
        if (!userHasPermission('admin:shop:orders:edit')) {

            $msg    = 'You do not have permission to edit orders.';
            $status = 'error';
            $this->session->set_flashdata($status, $msg);
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        //    Fetch and check order
        $this->load->model('shop/shop_order_model');

        $order = $this->shop_order_model->getById($this->uri->segment(5));

        if (!$order) {

            $msg    = 'No order exists by that ID.';
            $status = 'error';
            $this->session->set_flashdata($status, $msg);
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        if ($this->shop_order_model->cancel($order->id)) {

            $msg    = 'Order ' . $order->ref . ' was marked as cancelled.';
            $status = 'success';

        } else {

            $msg    = 'Failed to mark order ' . $order->ref . ' as cancelled.';
            $status = 'error';
        }

        $this->session->set_flashdata($status, $msg);
        redirect('admin/shop/orders/view/' . $order->id);
    }

    //---------------------------------------------------------------------------

    /**
     * Batch unfulfil orders
     * @return void
     */
    public function cancel_batch()
    {
        if (!userHasPermission('admin:shop:orders:edit')) {

            $msg    = 'You do not have permission to edit orders.';
            $status = 'error';
            $this->session->set_flashdata($status, $msg);
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        //    Fetch and check orders
        $this->load->model('shop/shop_order_model');

        if ($this->shop_order_model->cancelBatch($this->input->get('ids'))) {

            $msg    = 'Orders were marked as cancelled.';
            $status = 'success';

        } else {

            $msg     = 'Failed to mark orders as cancelled. ';
            $msg    .= $this->shop_order_model->lastError();
            $status  = 'error';
        }

        $this->session->set_flashdata($status, $msg);
        redirect('admin/shop/orders');
    }
}
