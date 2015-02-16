<?php

/**
 * Manage shop vouchers and gift cards
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    AdminController
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Admin\Shop;

class Vouchers extends \AdminController
{
    /**
     * Announces this controller's navGroups
     * @return stdClass
     */
    public static function announce()
    {
        if (userHasPermission('admin.shop:0.vouchers_manage')) {

            $navGroup = new \Nails\Admin\Nav('Shop');
            $navGroup->addMethod('Manage Vouchers');
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
        $this->load->model('shop/shop_voucher_model');

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
     * Browse voucehrs
     * @return void
     */
    public function index()
    {
        if (!userHasPermission('admin.shop:0.vouchers_manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Set method info
        $this->data['page']->title = 'Manage Vouchers';

        // --------------------------------------------------------------------------

        $tablePrefix = $this->shop_voucher_model->getTablePrefix();

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

        //  Filter columns
        $filters   = array();
        $filters[] = \Nails\Admin\Helper::searchFilterObject(
            $tablePrefix . '.type',
            'View only',
            array(
                array('Normal', 'NORMAL'),
                array('Limited Use', 'LIMITED_USE'),
                array('Gift Card', 'GIFT_CARD')
           )
        );

        // --------------------------------------------------------------------------

        //  Define the $data variable for the queries
        $data = array(
            'sort' => array(
                array($sortOn, $sortOrder)
            ),
            'keywords' => $keywords,
            'filters'  => $filters
        );

        // --------------------------------------------------------------------------

        //  Get the items for the page
        $totalRows              = $this->shop_voucher_model->count_all($data);
        $this->data['vouchers'] = $this->shop_voucher_model->get_all($page, $perPage, $data);

        //  Set Search and Pagination objects for the view
        $this->data['search']     = \Nails\Admin\Helper::searchObject(true, $sortColumns, $sortOn, $sortOrder, $perPage, $keywords, $filters);
        $this->data['pagination'] = \Nails\Admin\Helper::paginationObject($page, $perPage, $totalRows);

        // --------------------------------------------------------------------------

        if (userHasPermission('admin.shop:0.voucher_create')) {

            \Nails\Admin\Helper::addHeaderButton('admin/shop/voucher/create', 'Create Voucher');
        }

        // --------------------------------------------------------------------------

        \Nails\Admin\Helper::loadView('index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a new voucher
     * @return void
     */
    public function create()
    {
        if (!userHasPermission('admin.shop:0.vouchers_create')) {

            $this->session->set_flashdata('error', 'You do not have permission to create vouchers.');
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

            $this->form_validation->set_message('required', lang('fv_required'));
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

                    $this->session->set_flashdata('success', 'Voucher "' . $data['code'] . '" was created successfully.');
                    redirect('admin/shop/vouchers');

                } else {

                    $this->data['error']  = 'There was a problem creating the voucher. ';
                    $this->Data['error'] .= $this->shop_voucher_model->last_error();
                }

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Create Voucher';

        // --------------------------------------------------------------------------

        //  Fetch data
        $this->load->model('shop/shop_product_type_model');
        $this->data['product_types'] = $this->shop_product_type_model->get_all_flat();

        // --------------------------------------------------------------------------

        //  Load assets
        $this->asset->load('nails.admin.shop.vouchers.min.js', 'NAILS');
        $this->asset->inline('voucher = new NAILS_Admin_Shop_Vouchers_Edit();', 'JS');

        // --------------------------------------------------------------------------

        //  Load views
        \Nails\Admin\Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Activate a voucher
     * @return void
     */
    public function activate()
    {
        if (!userHasPermission('admin.shop:0.vouchers_activate')) {

            $status  = 'error';
            $message = 'You do not have permission to activate vouchers.';

        } else {

            $id = $this->uri->segment(5);

            if ($this->shop_voucher_model->activate($id)) {

                $status  = 'success';
                $message = 'Voucher was activated successfully.';

            } else {

                $status   = 'error';
                $message  = 'There was a problem activating the voucher. ';
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
    public function deactivate()
    {
        if (!userHasPermission('admin.shop:0.vouchers_deactivate')) {

            $status  = 'error';
            $message = 'You do not have permission to suspend vouchers.';

        } else {

            $id = $this->uri->segment(5);

            if ($this->shop_voucher_model->suspend($id)) {

                $status  = 'success';
                $message = 'Voucher was suspended successfully.';

            } else {

                $status   = 'error';
                $message  = 'There was a problem suspending the voucher. ';
                $message .= $this->shop_voucher_model->last_error();
            }
        }

        $this->session->set_flashdata($status, $message);

        redirect('admin/shop/vouchers');
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
