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
            $numUnfulfilled = $ci->db->count_all_results(NAILS_DB_PREFIX . 'shop_order');

            $oAlert = Factory::factory('NavAlert', 'nailsapp/module-admin');
            $oAlert->setValue($numUnfulfilled);
            $oAlert->setSeverity('danger');
            $oAlert->setLabel('Unfulfilled Orders');

            $navGroup = Factory::factory('Nav', 'nailsapp/module-admin');
            $navGroup->setLabel('Shop');
            $navGroup->setIcon('fa-shopping-cart');
            $navGroup->addAction('Manage Orders', 'index', array($oAlert), 0);

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
            $tablePrefix . '.created'    => 'Created',
            $tablePrefix . '.code'       => 'Code',
            $tablePrefix . '.type'       => 'Type',
            $tablePrefix . '.valid_from' => 'Valid From Date'
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
            'Fulfilled',
            array(
                array('Yes', 'FULFILLED'),
                array('No', 'UNFULFILLED')
            )
        );
        $cbFilters[] = Helper::searchFilterObject(
            $tablePrefix . '.delivery_type',
            'Delivery Type',
            array(
                array('Delivery', 'DELIVER'),
                array('Collection', 'COLLECT')
            )
        );

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

        $this->asset->load('nails.admin.shop.order.browse.min.js', 'NAILS');
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

            $this->session->set_flashdata('error', 'No order exists by that ID.');
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        //  Get associated payments
        $this->load->model('shop/shop_order_payment_model');
        $this->data['payments'] = $this->shop_order_payment_model->getForOrder($this->data['order']->id);

        // --------------------------------------------------------------------------

        //  Set method info
        $this->data['page']->title = 'View Order &rsaquo; ' . $this->data['order']->ref;

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

        if ($order->voucher->id) {

            //  Redeem the voucher, if it's there
            $oVoucherModel = Factory::model('Voucher', 'nailsapp/module-shop');
            $oVoucherModel->redeem($order->voucher->id, $order);
        }

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
     * Download an order's invoice
     * @return void
     */
    public function download_invoice()
    {
        if (!userHasPermission('admin:shop:orders:view')) {

            $this->session->set_flashdata('error', 'You do not have permission to download orders.');
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        //  Fetch and check order
        $this->load->model('shop/shop_order_model');

        $this->data['order'] = $this->shop_order_model->getById($this->uri->segment(5));

        if (!$this->data['order']) {

            $this->session->set_flashdata('error', 'No order exists by that ID.');
            redirect('admin/shop/orders');
        }

        // --------------------------------------------------------------------------

        //  Load up the shop's skin
        $skin = appSetting('skin_checkout', 'shop') ? appSetting('skin_checkout', 'shop') : 'shop-skin-checkout-classic';

        $this->load->model('shop/shop_skin_checkout_model');
        $skin = $this->shop_skin_checkout_model->get($skin);

        if (!$skin) {

            showFatalError('Failed to load shop skin "' . $skin . '"', 'Shop skin "' . $skin . '" failed to load at ' . APP_NAME . ', the following reason was given: ' . $this->shop_skin_checkout_model->lastError());
        }

        // --------------------------------------------------------------------------

        //  Views
        $this->data['for_user'] = 'ADMIN';
        $this->load->library('pdf/pdf');
        $this->pdf->setPaperSize('A4', 'landscape');
        $this->pdf->loadView($skin->path . 'views/order/invoice', $this->data);
        $this->pdf->download('INVOICE-' . $this->data['order']->ref . '.pdf');
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
